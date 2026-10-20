#!/usr/bin/env python3
"""Authorized host-only selective dev PostgreSQL switch; preserve app session and old volume."""
import importlib.util
import json
from pathlib import Path
import subprocess
import sys

ROOT = Path(__file__).resolve().parents[2]
sys.path.insert(0, str(ROOT / 'tools/project'))
from runtime import compose, inside

IMAGE = 'postgres:18.6-alpine3.24@sha256:77f585114c32fbca283dc835b0596f4e52b51b4c6662d7810b2f4084f60a1873'


def run(args, **kwargs):
    if 'input' not in kwargs:
        kwargs.setdefault('stdin', subprocess.DEVNULL)
    return subprocess.run(args, cwd=ROOT, check=True, **kwargs)


def output(args):
    return run(args, capture_output=True, text=True).stdout.strip()


def inspect(container):
    return json.loads(output(['docker', 'inspect', container]))[0]


def volume(container):
    mounts = [m for m in container['Mounts'] if m['Destination'] == '/var/lib/postgresql']
    if len(mounts) != 1 or mounts[0]['Type'] != 'volume':
        raise ValueError('Expected one named PostgreSQL data volume.')
    return mounts[0]['Name']


def fingerprint(name, image):
    # No existing volume is mounted read-write; names come only from inspected Docker metadata.
    return output(['docker', 'run', '--rm', '--read-only', '--tmpfs', '/tmp:rw,nosuid,nodev,size=16m,mode=1777', '--network', 'none', '--cap-drop=ALL', '--user', 'postgres', '--security-opt=no-new-privileges:true', '--mount', 'type=volume,src=' + name + ',dst=/old,readonly', '--entrypoint', 'sh', image, '-ec', 'find /old -type f -exec sha256sum {} + > /tmp/checks && sort /tmp/checks | sha256sum'])


def main():
    if inside():
        raise ValueError('Run this script on the Docker host. Never mount the Docker socket into the devcontainer.')
    dc = compose()
    evidence = ROOT / '.runtime/postgres-icu-switch'
    if evidence.exists():
        raise ValueError('Switch evidence already exists. Inspect its state; do not restart a partially completed switch blindly.')
    config = json.loads(output(dc + ['config', '--format', 'json']))
    if config['services']['postgres']['image'] != IMAGE:
        raise ValueError('Unexpected candidate image.')
    target = config['volumes']['postgres-icu-data']['name']
    old_id = output(dc + ['ps', '--quiet', 'postgres'])
    app_id = output(dc + ['ps', '--quiet', 'app'])
    if not old_id or not app_id:
        raise ValueError('Existing development PostgreSQL and app must be running.')
    original = inspect(old_id)
    if original['Config']['Image'] != 'postgres:18.6-bookworm':
        raise ValueError('Expected the previously audited Bookworm cluster; inspect unexpected image before switching.')
    old_volume = volume(original)
    if old_volume == target:
        raise ValueError('Already using the new volume; no switch performed.')
    if original['Config']['Labels'].get('com.docker.compose.project') != config['name']:
        raise ValueError('Compose project mismatch.')
    present = subprocess.run(['docker', 'volume', 'inspect', target], stdin=subprocess.DEVNULL, stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
    if present.returncode == 0:
        raise ValueError('Target volume already exists. Refusing to reuse unknown data.')
    unaffected = {}
    for name in config['services']:
        if name in ('postgres', 'queue', 'scheduler', 'setup'):
            continue
        cid = output(dc + ['ps', '--quiet', name])
        if cid:
            info = inspect(cid)
            unaffected[name] = {'id': cid, 'started': info['State']['StartedAt']}
    app_started = inspect(app_id)['State']['StartedAt']
    evidence.mkdir(parents=True, mode=0o700)
    state = {'phase': 'prepared', 'old_container': old_id, 'old_volume': old_volume, 'new_volume': target, 'app_container': app_id, 'app_started': app_started, 'unaffected': unaffected}
    def save():
        (evidence / 'state.json').write_text(json.dumps(state, indent=2) + '\n')
    save()
    print('Preserving rollback volume: ' + old_volume, flush=True)
    print('Creating new ICU volume: ' + target, flush=True)
    # Pull/build work happens before stopping database-dependent processes.
    run(['docker', 'pull', IMAGE])
    run(dc + ['stop', 'queue', 'scheduler'])
    run(dc + ['stop', 'postgres'])
    state['phase'] = 'old-cluster-stopped'
    save()
    # Normal clean PostgreSQL shutdown checkpoints the old cluster. From this point it is read-only.
    state['old_content_sha256'] = fingerprint(old_volume, original['Image'])
    save()
    run(dc + ['up', '-d', '--no-deps', '--no-build', '--wait', '--force-recreate', 'postgres'])
    if volume(inspect(output(dc + ['ps', '--quiet', 'postgres']))) != target:
        raise ValueError('New PostgreSQL is not mounted on the approved new volume.')
    state['phase'] = 'new-cluster-started'
    save()
    run(dc + ['exec', '-T', 'app', 'php', 'artisan', 'migrate', '--no-interaction'])
    run(dc + ['exec', '-T', 'app', './bin/seed', 'default'])
    run(dc + ['exec', '-T', 'app', './bin/test', '--parallel'])
    run(dc + ['exec', '-T', 'app', './bin/doctor', 'database-migrations'])
    spec = importlib.util.spec_from_file_location('pg_probe', ROOT / 'tools/testing/postgres-alpine.py')
    probe = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(probe)
    names = output(dc + ['exec', '-T', 'postgres', 'psql', '-U', 'postgres', '-d', 'postgres', '-qAt', '-c', "SELECT datname FROM pg_database WHERE datname = 'runa' OR datname LIKE 'runa_test%' ORDER BY datname"]).splitlines()
    if not {'runa', 'runa_test', 'runa_test_test_1', 'runa_test_test_2', 'runa_test_test_3', 'runa_test_test_4'}.issubset(names):
        raise ValueError('Expected development/base/parallel test databases are missing.')
    for name in names:
        print('Checking locale and index semantics: ' + name, flush=True)
        result = run(dc + ['exec', '-T', 'postgres', 'psql', '-X', '-qAt', '-U', 'postgres', '-d', name, '-v', 'ON_ERROR_STOP=1'], input=probe.SQL, capture_output=True, text=True)
        probe.verify(probe.decode(result.stdout))
    run(dc + ['up', '-d', '--no-deps', '--no-build', '--wait', 'queue', 'scheduler'])
    run(dc + ['exec', '-T', 'app', './bin/test', 'frontend'])
    run(dc + ['exec', '-T', 'app', './bin/analyse'])
    run(dc + ['exec', '-T', 'app', './bin/doctor', 'development'])
    if fingerprint(old_volume, original['Image']) != state['old_content_sha256']:
        raise ValueError('Rollback-volume content changed after clean shutdown.')
    for name, prior in unaffected.items():
        cid = output(dc + ['ps', '--quiet', name])
        if cid != prior['id'] or inspect(cid)['State']['StartedAt'] != prior['started']:
            raise ValueError('Unrelated container was replaced/restarted: ' + name)
    state['phase'] = 'verified-dev-switch'
    state['databases'] = names
    save()
    print('PASS: new dev/test ICU cluster, migrations/test guards/tests/health; existing app session retained; old volume retained with unchanged contents since clean shutdown.')
    print('Production simulation security and installer verification remain separate pending work.')


if __name__ == '__main__':
    try:
        main()
    except (ValueError, OSError, subprocess.CalledProcessError) as error:
        print('Switch stopped: ' + (f'child exited {error.returncode}.' if isinstance(error, subprocess.CalledProcessError) else str(error)), file=sys.stderr)
        print('Do not remove volumes or repeat blindly. Share this output and .runtime/postgres-icu-switch/state.json if created.', file=sys.stderr)
        sys.exit(1)
