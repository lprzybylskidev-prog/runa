#!/usr/bin/env python3
"""Install approved pinned scanners with release checksum verification; explicit network operation."""
import hashlib
import io
from pathlib import Path
import tarfile
import subprocess
import platform
import urllib.request

ROOT = Path(__file__).resolve().parents[2]
TOOLS = ROOT / '.runtime/tools'


def install():
    if platform.machine() not in ('x86_64', 'AMD64'):
        raise ValueError('Scanner installer currently requires the selected x86_64 development platform.')
    TOOLS.mkdir(parents=True, exist_ok=True)
    existing = TOOLS / 'gitleaks'
    if existing.exists() and subprocess.run([str(existing), 'version'], check=True, capture_output=True, text=True).stdout.strip() == '8.30.1':
        return
    for project, version, asset, sums in [
        ('gitleaks/gitleaks', '8.30.1', 'gitleaks_8.30.1_linux_x64.tar.gz', 'gitleaks_8.30.1_checksums.txt'),
    ]:
        base = f'https://github.com/{project}/releases/download/v{version}/'
        checks = urllib.request.urlopen(base + sums).read().decode()
        expected = next(line.split()[0] for line in checks.splitlines() if line.split()[-1].lstrip('*') == asset)
        data = urllib.request.urlopen(base + asset).read()
        if expected != '551f6fc83ea457d62a0d98237cbad105af8d557003051f41f3e7ca7b3f2470eb':
            raise ValueError('Scanner release checksum differs from the reviewed pin.')
        if hashlib.sha256(data).hexdigest() != expected:
            raise ValueError('Scanner release checksum mismatch.')
        with tarfile.open(fileobj=io.BytesIO(data), mode='r:gz') as archive:
            member = archive.extractfile('gitleaks')
            if member is None:
                raise ValueError('Scanner binary absent.')
            target = TOOLS / 'gitleaks'
            target.write_bytes(member.read())
            target.chmod(0o755)
        print(f'Installed gitleaks {version}; SHA256 {expected}')


if __name__ == '__main__':
    install()
