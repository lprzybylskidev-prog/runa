#!/usr/bin/env python3
"""Quick global and focused deterministic project diagnostics."""
from pathlib import Path
import subprocess
import os
import sys

ROOT = Path(__file__).resolve().parents[3]
sys.path.insert(0, str(ROOT / 'tools/application'))
from runtime import app_command, host_stack_health, inside, run
import checks

AREAS = ('repository', 'config', 'maintenance', 'hooks', 'frontend', 'database-migrations', 'database', 'seeding', 'boost', 'tooling', 'docs', 'development', 'production')
try:
    args = sys.argv[1:]
    if args == ['--help']:
        print('Usage: ./bin/artisan runa:doctor [area]\nNo area: quick whole-development diagnostic. Focused areas: ' + ', '.join(AREAS) + '.\nChecks never repair or deploy. Database-migrations resets only the guarded testing database.')
    elif args == ['production']:
        run([str(ROOT / 'bin/production'), 'self-test'])
    elif not inside():
        if not args or args == ['development']:
            host_stack_health()
        raise SystemExit(subprocess.run(app_command(['php', 'artisan', 'runa:doctor', *args]), cwd=ROOT).returncode)
    else:
        if len(args) > 1 or (args and args[0] not in AREAS):
            raise ValueError('Unknown doctor area. See ./bin/artisan runa:doctor --help.')
        area = args[0] if args else 'global'
        if area == 'global':
            checks.repository(); checks.maintenance(); checks.docs(); checks.tooling(); checks.development()
        elif hasattr(checks, area):
            getattr(checks, area)()
        else:
            commands = {
                'config': ['php', 'artisan', 'runa:config:validate', '--templates'],
                'hooks': ['python3', 'tools/application/testing/hooks.py'],
                'frontend': ['php', 'artisan', 'runa:typecheck'],
                'database-migrations': ['php', 'artisan', 'runa:test-db:guard', '--env=testing'],
                'database': ['php', 'artisan', 'runa:test', '--filter=Database'],
                'seeding': ['php', 'artisan', 'runa:test', '--filter=Seeding'],
            }
            run(commands[area], **({'env': dict(os.environ, APP_ENV='testing')} if area == 'database-migrations' else {}))
except (ValueError, OSError, KeyError, TypeError, subprocess.CalledProcessError) as error:
    print(f'Doctor failed: {error}', file=sys.stderr)
    sys.exit(getattr(error, 'returncode', 1))
