#!/usr/bin/env python3
"""Native cold operator Artisan -> executor -> integrity; no app/database required."""
import importlib.util
import json
import os
from pathlib import Path
import subprocess
import sys
import tempfile
import uuid

sys.dont_write_bytecode = True
ROOT = Path(__file__).resolve().parents[2]


def main():
    if Path('/.dockerenv').exists():
        raise SystemExit('Run this acceptance from the native Docker host.')
    spec = importlib.util.spec_from_file_location('installation_fixture', ROOT / 'tools/testing/installation-host.py')
    fixture = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(fixture)
    evidence = {'result': 'RUNNING', 'checks': [], 'persistent_containers_networks_volumes': []}
    def check(condition, text):
        if not condition:
            raise AssertionError(text)
        print('PASS: ' + text, flush=True)
        evidence['checks'].append(text)
    with tempfile.TemporaryDirectory(prefix='runa-integrity-operator-') as directory:
        root = Path(directory) / 'dev'
        fixture.snapshot(root)
        sha = fixture.commit(root)
        fixture.project_customer(root, sha)
        launcher = str(root / 'bin/artisan')
        baseline = (root / '.git/runa-ownership.json').read_bytes()
        try:
            fixture.call([launcher, '--recover-host'], cwd=root)
            identity = json.loads((root / '.runtime/host/client.json').read_text())['identity']
            image = 'runa-management:' + identity[:24]
            evidence['management_image'] = image
            def artisan(*arguments):
                name = 'runa-integrity-operator-' + uuid.uuid4().hex[:12]
                return fixture.call(['docker', 'run', '--rm', '--name', name, '--network', 'none', '--read-only', '--tmpfs', '/tmp',
                                     '--cap-drop=ALL', '--security-opt=no-new-privileges:true', '--user', str(os.getuid()) + ':' + str(os.getgid()),
                                     '--mount', 'type=bind,src=' + str(root / '.runtime/host') + ',dst=/run/runa-host',
                                     image, '--management', 'runa:integrity', *arguments], capture=True, check=False)
            result = artisan('--json')
            data = json.loads(result.stdout)
            check(result.returncode == 0 and data['ok'] and data['composition_valid'], 'cold operator PHP executes final trusted-source integrity without app/vendor/key/DB/host PHP or Docker socket')
            evidence['pristine'] = data
            customer = root / 'src/app/customer-sentinel.txt'
            customer.write_text('preserve customer business code\n')
            protected = root / 'src/runa/platform.json'
            protected.write_text('{"synthetic":"modified platform source"}\n')
            before = protected.read_bytes()
            result = artisan('--json')
            data = json.loads(result.stdout)
            check(result.returncode == 1 and not data['protected_ok'] and data['update_blocked'], 'real host operation propagates protected-source failure as nonzero structured report')
            check(any(row['path'] == 'src/runa/platform.json' and row['change'] == 'modified' for row in data['changes']), 'report identifies modified protected source separately from customer code')
            check(protected.read_bytes() == before and customer.read_text() == 'preserve customer business code\n' and (root / '.git/runa-ownership.json').read_bytes() == baseline, 'integrity never repairs source, overwrites customer sentinel or accepts a new baseline')
            result = artisan('--repair')
            check(result.returncode != 0 and 'Host operation:' not in result.stderr, 'repair/exclusion options are rejected before host execution')
            evidence['result'] = 'PASS'
        except BaseException:
            evidence['result'] = 'FAIL'
            raise
        finally:
            if (root / '.runtime/host/client.json').exists():
                print('Scoped fixture executor shutdown: ' + str(root), flush=True)
                fixture.call([launcher, '--stop-host'], cwd=root)
            print('Scoped temporary source cleanup: ' + str(root) + '; ephemeral operator containers used --rm; no networks/volumes created.', flush=True)
            (ROOT / '.runtime/ownership/RM-2026-005-point-9-operator.json').write_text(json.dumps(evidence, indent=2) + '\n')


if __name__ == '__main__':
    main()
