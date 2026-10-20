#!/usr/bin/env python3
"""Record the one-way real production deployment transition from host evidence."""
import argparse
from datetime import datetime, timezone
import json
from pathlib import Path
import re
import subprocess
import sys

ROOT = Path(__file__).resolve().parents[3]
sys.path.insert(0, str(ROOT / 'tools/project'))
from runtime import run
import checks

parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('action', choices=['mark-production-deployed'])
parser.add_argument('--evidence', required=True, help='Private first-deploy.json copied from the real production host')
try:
    options = parser.parse_args()
    checks.repository()
    active = list((ROOT / 'docs/roadmaps/active').glob('*.md'))
    if len(active) != 1:
        raise ValueError('Activate a roadmap before changing versioned production state.')
    if run(['git', 'status', '--porcelain'], capture_output=True, text=True).stdout.strip():
        raise ValueError('Commit/reconcile the working tree before recording production state.')
    evidence = json.loads(Path(options.evidence).read_text())
    if evidence.get('simulation') is not False or not re.fullmatch('[a-f0-9]{40}', evidence.get('sha', '')) or not evidence.get('images'):
        raise ValueError('Evidence must identify a successful real deployment and exact image identities.')
    deployed = datetime.fromisoformat(evidence['deployed_at'])
    if deployed.tzinfo is None or deployed > datetime.now(timezone.utc):
        raise ValueError('Deployment timestamp must include a timezone and cannot be in the future.')
    required_images = {'runa-production:' + evidence['sha'], 'runa-nginx:' + evidence['sha']}
    if not required_images.issubset(evidence['images']) or any(not re.fullmatch(r'sha256:[a-f0-9]{64}', value) for value in evidence['images'].values()):
        raise ValueError('Evidence requires exact application/Nginx SHA image tags and full image IDs.')
    run(['git', 'cat-file', '-e', evidence['sha'] + '^{commit}'])
    version = run(['git', 'show', evidence['sha'] + ':VERSION'], capture_output=True, text=True).stdout.strip()
    if version != evidence.get('version'):
        raise ValueError('Evidence version/commit mismatch.')
    path = ROOT / 'docs/project/state.yml'
    data = checks.yaml(path)
    if data['production']['ever_deployed']:
        print('Production already recorded; the first-deployment date remains unchanged.')
        raise SystemExit(0)
    text = path.read_text().replace('ever_deployed: false', 'ever_deployed: true').replace('first_deployed_at: null', 'first_deployed_at: ' + deployed.isoformat())
    if text == path.read_text():
        raise ValueError('Unexpected versioned production-state format.')
    path.write_text(text)
    run(['php', 'artisan', 'runa:knowledge', 'index'])
    run(['git', 'add', '--', str(path.relative_to(ROOT))])
    identifier = re.search(r'^id: (RM-\d{4}-\d{3})$', active[0].read_text(), re.M)[1]
    run(['git', 'commit', '-m', f'chore({identifier}): record first real production deployment'])
    run(['git', 'push', '-u', 'origin', 'HEAD'])
    print('PASS: one-way production state recorded, committed and pushed.')
except (ValueError, KeyError, OSError, subprocess.CalledProcessError) as error:
    print('Project state failed: ' + (f'child command exited {error.returncode}.' if isinstance(error, subprocess.CalledProcessError) else str(error)), file=sys.stderr)
    sys.exit(1)
