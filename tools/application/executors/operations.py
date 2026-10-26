#!/usr/bin/env python3
"""Internal locked production mechanics; only Artisan defines the public surface."""
from pathlib import Path
import subprocess
import sys
ROOT = Path(__file__).resolve().parents[3]
if __name__ == '__main__' and not __package__ and Path(sys.argv[0]).resolve() == Path(__file__).resolve():
    raise SystemExit(subprocess.call([sys.executable, '-m', 'tools.application.executors.operations', *sys.argv[1:]], cwd=ROOT))
from tools.application.operations import main
try:
    main(sys.argv[1], sys.argv[2:])
except (ValueError, OSError, EOFError, KeyError, subprocess.CalledProcessError) as error:
    print('Operation failed: ' + (f'child command exited {error.returncode}.' if isinstance(error, subprocess.CalledProcessError) else str(error)), file=sys.stderr)
    sys.exit(getattr(error, 'returncode', 1))
