#!/usr/bin/env bash
# Actual-image verification in the existing isolated production simulation only.
set -euo pipefail
cd -- "$(dirname -- "${BASH_SOURCE[0]}")/../../.."

python3 - <<'PY'
import json
from pathlib import Path
state = json.loads(Path('.runtime/production/deployment.json').read_text())
if state.get('current', {}).get('simulation') is not True:
    raise SystemExit('This acceptance command requires the existing managed simulation. No changes made.')
PY

target_sha="$(git rev-parse HEAD)"
./bin/deploy "$target_sha" --simulation
./bin/production self-test

# Collect all reports without downgrading the full security gate.
scan_status=0
scan_started="$(python3 -c 'import time; print(time.time())')"
./bin/security trivy || scan_status=$?
python3 - "$target_sha" "$scan_started" <<'PY'
import hashlib
import json
from pathlib import Path
import sys
image = 'runa-production:' + sys.argv[1]
report = Path('.runtime/security') / ('image-' + hashlib.sha256(image.encode()).hexdigest()[:12] + '.json')
if not report.is_file() or report.stat().st_mtime < float(sys.argv[2]):
    raise SystemExit('FAIL: no fresh PHP final-image scan report.')
data = json.loads(report.read_text())
if image not in data.get('Metadata', {}).get('RepoTags', []) or 'Results' not in data:
    raise SystemExit('FAIL: final-image report is incomplete or has a different identity.')
findings = [item for result in data['Results'] for key in ('Vulnerabilities', 'Secrets') for item in result.get(key, [])]
if findings:
    raise SystemExit('FAIL: the PHP final-image report has HIGH/CRITICAL findings. Inspect its private JSON report.')
print('PASS: actual Alpine PHP image build/runtime, simulated application/background health, and final-image HIGH/CRITICAL vulnerability/secret scan.')
PY
if [ "$scan_status" -ne 0 ]; then
    echo 'Full security gate remains FAILED; PostgreSQL assessment/decision is separate. No finding was suppressed.'
    exit "$scan_status"
fi
