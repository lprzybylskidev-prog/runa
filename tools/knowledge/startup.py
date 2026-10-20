#!/usr/bin/env python3
"""Optional knowledge startup: bounded readiness and restartable first indexing."""
import json
from pathlib import Path
import subprocess
import sys
import time
import urllib.error
import urllib.request

ROOT = Path(__file__).resolve().parents[2]


def probe():
    config = json.loads((ROOT / 'tools/knowledge/config.json').read_text())
    opener = urllib.request.build_opener(urllib.request.ProxyHandler({}))
    unavailable = []
    for service, endpoint in (('qdrant', config['qdrant_url'] + '/readyz'),
                              ('embeddings', config['cpu_url'] + '/health')):
        try:
            with opener.open(endpoint, timeout=1) as response:
                if response.status != 200:
                    unavailable.append(service)
        except (urllib.error.URLError, TimeoutError, ConnectionError):
            unavailable.append(service)
    local = ROOT / '.env.dev.local'
    if local.exists() and 'EMBEDDINGS_CUDA=1' in local.read_text().splitlines():
        try:
            with opener.open(config['cuda_url'] + '/health', timeout=1) as response:
                gpu_ready = response.status == 200
        except (urllib.error.URLError, TimeoutError, ConnectionError):
            gpu_ready = False
        if not gpu_ready:
            print('DEGRADED: optional CUDA unavailable; CPU fallback will be used when ready.', flush=True)
    if unavailable:
        print('DEGRADED: optional knowledge services unavailable: ' + ', '.join(unavailable), flush=True)
        return 1
    print('Optional knowledge services reachable; strict model/retrieval checks: ./bin/knowledge doctor.', flush=True)
    return 0


def initialize():
    try:
        ready = subprocess.run([sys.executable, str(Path(__file__).resolve()), 'probe'], cwd=ROOT,
                               timeout=5, check=False).returncode == 0
        if ready:
            result = subprocess.run([sys.executable, str(ROOT / 'tools/project/executors/knowledge.py'), 'initialize'], cwd=ROOT, check=False)
            if result.returncode == 0:
                return 0
    except subprocess.TimeoutExpired:
        print('DEGRADED: optional knowledge readiness timed out.', flush=True)
    print('Knowledge initialization will retry independently; application setup is unaffected.', flush=True)
    time.sleep(10)
    return 1


if __name__ == '__main__':
    if sys.argv[1:] == ['probe']:
        sys.exit(probe())
    if sys.argv[1:] == ['initialize']:
        sys.exit(initialize())
    raise SystemExit('Usage: startup.py probe|initialize')
