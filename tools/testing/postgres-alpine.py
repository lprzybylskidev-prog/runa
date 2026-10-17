#!/usr/bin/env python3
"""Host-only disposable official PostgreSQL candidate probe; never mounts an existing volume."""
import json
import os
from pathlib import Path
import secrets
import signal
import subprocess
import sys
import tempfile
import time

ROOT = Path(__file__).resolve().parents[2]
sys.path.insert(0, str(ROOT / 'tools/project'))
from runtime import inside

IMAGE = 'postgres:18.6-alpine3.24@sha256:77f585114c32fbca283dc835b0596f4e52b51b4c6662d7810b2f4084f60a1873'
SQL = r'''
BEGIN;
SELECT json_build_object('kind', 'metadata', 'version', version(), 'encoding', pg_encoding_to_char(encoding), 'provider', datlocprovider, 'locale', datlocale, 'recorded_collation', datcollversion, 'actual_collation', pg_database_collation_actual_version(oid)) FROM pg_database WHERE datname = current_database();
CREATE TEMP TABLE collation_probe (value text NOT NULL);
INSERT INTO collation_probe VALUES ('ż'),('ź'),('z'),('ś'),('s'),('ó'),('o'),('ń'),('n'),('ł'),('l'),('ę'),('e'),('ć'),('c'),('ą'),('a');
CREATE INDEX collation_probe_order ON collation_probe(value);
SET LOCAL enable_seqscan = off;
SELECT json_build_object('kind', 'indexed_order', 'values', array_agg(value)) FROM (SELECT value FROM collation_probe ORDER BY value) AS ordered;
SELECT json_build_object('kind', 'range', 'values', array_agg(value)) FROM (SELECT value FROM collation_probe WHERE value >= 'l' AND value < 'n' ORDER BY value) AS ordered;
EXPLAIN (FORMAT JSON) SELECT value FROM collation_probe ORDER BY value;
SET LOCAL enable_seqscan = on;
SET LOCAL enable_indexscan = off;
SET LOCAL enable_indexonlyscan = off;
SET LOCAL enable_bitmapscan = off;
SELECT json_build_object('kind', 'sequential_order', 'values', array_agg(value)) FROM (SELECT value FROM collation_probe ORDER BY value) AS ordered;
SELECT json_build_object('kind', 'semantics', 'lower', lower('ĄĆĘŁŃÓŚŹŻ'), 'accent_equal', 'a' = 'ą', 'case_equal', 'A' = 'a', 'normalized_equal', U&'a\0328' = 'ą');
ROLLBACK;
'''


def run(args, **kwargs):
    kwargs.setdefault('stdin', subprocess.DEVNULL)
    if 'input' in kwargs:
        kwargs.pop('stdin')
    return subprocess.run(args, cwd=ROOT, check=True, **kwargs)


def decode(text):
    decoder = json.JSONDecoder()
    items = []
    while text.strip():
        value, end = decoder.raw_decode(text.lstrip())
        items.append(value)
        text = text.lstrip()[end:]
    return items


def verify(items):
    rows = {item['kind']: item for item in items if isinstance(item, dict)}
    meta = rows['metadata']
    if meta['provider'] != 'i' or meta['locale'] != 'pl-PL' or meta['encoding'] != 'UTF8' or not meta['recorded_collation'] or meta['recorded_collation'] != meta['actual_collation']:
        raise ValueError('Unexpected database locale/provider/encoding or collation-version mismatch.')
    expected = ['a', 'ą', 'c', 'ć', 'e', 'ę', 'l', 'ł', 'n', 'ń', 'o', 'ó', 's', 'ś', 'z', 'ź', 'ż']
    if rows['indexed_order']['values'] != expected or rows['sequential_order']['values'] != expected or rows['range']['values'] != ['l', 'ł']:
        raise ValueError('Polish order/range differs between index and sequential paths.')
    semantics = rows['semantics']
    if semantics['lower'] != 'ąćęłńóśźż' or any(semantics[key] for key in ('accent_equal', 'case_equal', 'normalized_equal')):
        raise ValueError('Unexpected Unicode casing or deterministic equality semantics.')
    plans = [item for item in items if isinstance(item, list)]
    if len(plans) != 1 or not any(kind in json.dumps(plans[0]) for kind in ('"Index Only Scan"', '"Index Scan"')):
        raise ValueError('Index use was not verified.')
    print('PASS: official PostgreSQL candidate, ICU pl-PL, Polish casing/order/ranges, deterministic equality and index/sequence agreement.')
    print(json.dumps(meta, ensure_ascii=False))


def main():
    if inside():
        raise ValueError('Run this disposable Docker probe on the host; no Docker socket is required in the devcontainer.')
    name = 'runa-pg-alpine-assessment-' + secrets.token_hex(6)
    runtime = ROOT / '.runtime/security'
    runtime.mkdir(parents=True, exist_ok=True, mode=0o700)
    # The official entrypoint and gosu remain intact. There are no ports or persistent mounts.
    with tempfile.TemporaryDirectory(prefix='pg-assessment-', dir=runtime) as folder:
        env = Path(folder) / 'postgres.env'
        env.write_text('POSTGRES_PASSWORD=' + secrets.token_urlsafe(36) + '\nPOSTGRES_INITDB_ARGS=--encoding=UTF8 --locale=C.UTF-8 --locale-provider=icu --icu-locale=pl-PL\n')
        env.chmod(0o600)
        try:
            run(['docker', 'run', '-d', '--rm', '--name', name, '--label', 'org.runa.assessment=postgres-alpine', '--network', 'none', '--memory', '512m', '--cpus', '2', '--security-opt=no-new-privileges:true', '--tmpfs', '/var/lib/postgresql:rw,size=256m', '--env-file', str(env), IMAGE])
            print('Disposable candidate container: ' + name, flush=True)
            while True:
                status = run(['docker', 'inspect', '--format', '{{.State.Running}}', name], capture_output=True, text=True).stdout.strip()
                if status != 'true':
                    raise ValueError('Candidate container stopped before readiness.')
                ready = subprocess.run(['docker', 'exec', name, 'pg_isready', '-h', '127.0.0.1', '-U', 'postgres'], stdin=subprocess.DEVNULL, stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
                if ready.returncode == 0:
                    break
                time.sleep(1)
            result = run(['docker', 'exec', '-i', name, 'psql', '-X', '-qAt', '-U', 'postgres', '-d', 'postgres', '-v', 'ON_ERROR_STOP=1'], input=SQL, capture_output=True, text=True)
            verify(decode(result.stdout))
        finally:
            subprocess.run(['docker', 'rm', '-f', name], stdin=subprocess.DEVNULL, stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)


if __name__ == '__main__':
    signal.signal(signal.SIGTERM, lambda *_: sys.exit(130))
    try:
        main()
    except (ValueError, OSError, subprocess.CalledProcessError) as error:
        print('Candidate probe failed: ' + (f'child exited {error.returncode}.' if isinstance(error, subprocess.CalledProcessError) else str(error)), file=sys.stderr)
        sys.exit(1)
