"""Pure target metadata executed with read-only mounts and no application boot/network."""
import json
import os
from pathlib import Path
import re
import subprocess
import uuid
from update_contracts import Blocker


def plan(root, target, runtime, image):
    directory = target / 'src/runa/modules'
    modules = [path for path in directory.iterdir() if path.name not in ('.gitkeep', '.gitignore')] if directory.is_dir() else []
    if not modules:
        if any(key.startswith('runa.') and row['registered'] for key, row in runtime.get('modules', {}).items()):
            raise Blocker('module-source-missing', 'Registered Runa module source is absent from the target release.')
        return []
    if not re.fullmatch(r'(?:sha256:[a-f0-9]{64}|[A-Za-z0-9][A-Za-z0-9._/:@-]{0,255})', image):
        raise Blocker('module-toolchain', 'Module plan needs the verified toolchain image.')
    name = 'runa-module-plan-' + uuid.uuid4().hex
    args = ['docker', 'run', '--rm', '-i', '--name', name, '--network', 'none', '--read-only', '--cap-drop=ALL', '--security-opt=no-new-privileges:true',
            '--user', str(os.getuid()) + ':' + str(os.getgid()), '--tmpfs', '/tmp:rw,nosuid,nodev,size=32m,mode=1777',
            '--mount', 'type=bind,src=' + str(root / 'src/vendor') + ',dst=/installed/vendor,readonly',
            '--mount', 'type=bind,src=' + str(target / 'src') + ',dst=/target,readonly',
            '--mount', 'type=bind,src=' + str(Path(__file__).with_name('module-plan.php')) + ',dst=/module-plan.php,readonly',
            '--entrypoint', 'php', image, '/module-plan.php']
    from update_contracts import platform
    payload = {'platform': platform(target), 'modules': runtime.get('modules', {}), 'module_steps': runtime.get('module_steps', [])}
    try:
        result = subprocess.run(args, input=json.dumps(payload), text=True, capture_output=True, timeout=60)
        if result.returncode:
            raise Blocker('module-preflight', 'Target module preflight failed; inspect dependency compatibility, migration history, declared upgrade paths and pure module checks.')
        return json.loads(result.stdout)
    except (OSError, subprocess.SubprocessError, ValueError):
        raise Blocker('module-preflight', 'Isolated target module planner is unavailable; no update was executed.') from None
    finally:
        # Only the uniquely named task-created disposable planner, including timeout cleanup.
        print('Scoped module planner cleanup: ' + name, file=__import__('sys').stderr)
        subprocess.run(['docker', 'rm', '--force', name], capture_output=True, timeout=30)
