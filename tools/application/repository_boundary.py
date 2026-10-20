"""Explicit installation/staging ownership; upstream is read-only release input."""
import json
import os
from pathlib import Path
import subprocess

OWNER_PATHS = ('distribution', 'tools/maintainer', 'bootstrap.py', 'bin/project', 'bin/install-prod',
               'src/runa/management/InstallCommand.php', 'src/runa/management/ProjectCommand.php')


class RepositoryBoundaryError(ValueError):
    """Fixed diagnostics for an unapproved acquisition/maintenance target."""


class DevelopmentContextError(RepositoryBoundaryError):
    """A fixed, nonsecret development-mode denial safe to report to an operator."""


def receipt(root):
    root = Path(root)
    if any(key.startswith('GIT_') and key != 'GIT_PAGER' for key in os.environ):
        raise RepositoryBoundaryError('Inherited Git overrides are not an installation ownership context; use a clean operator environment.')
    if root.resolve() != root or (root / '.git').is_symlink():
        raise RepositoryBoundaryError('Installation repository must not traverse symlinks.')
    path = root / '.git/runa-bootstrap.json'
    if path.is_symlink() or not path.is_file():
        raise RepositoryBoundaryError('Missing trusted installation acquisition receipt. Refusing the Runa maintenance/unknown checkout.')
    data = json.loads(path.read_text())
    if data.get('schema_version') != 1 or data.get('projection') != {'version': 1, 'excluded': list(OWNER_PATHS)}:
        raise RepositoryBoundaryError('Unsupported installation source projection; request owner migration rather than repairing silently.')
    if any((root / name).exists() or (root / name).is_symlink() for name in OWNER_PATHS):
        raise RepositoryBoundaryError('Owner distribution source is materialized in a customer installation; inspect source projection.')
    return data


def assert_installation(root):
    data = receipt(root)
    # Never replace or repoint a preexisting customer remote/history.
    origin = subprocess.run(['git', '-C', str(root), 'remote', 'get-url', '--all', 'origin'], capture_output=True, text=True, check=True).stdout.strip()
    expected = 'https://github.com/' + data['repository'] + '.git'
    if origin != expected:
        raise RepositoryBoundaryError('Recorded read-only release upstream differs; preserve customer remotes and inspect ownership.')
    push = subprocess.run(['git', '-C', str(root), 'remote', 'get-url', '--push', '--all', 'origin'], capture_output=True, text=True, check=True).stdout.strip()
    if push != 'disabled://runa-read-only-upstream':
        raise RepositoryBoundaryError('Release upstream is not fenced against accidental writes.')
    return data


def assert_production_installation(root):
    """Bind operations to installer-selected target mode, never the caller's APP_ENV."""
    data = assert_installation(root)
    if data.get('mode') != 'prod':
        raise RepositoryBoundaryError('Production commands require a production installation (mode=prod). Development or unknown installation mode is blocked; APP_ENV and --simulation do not override it.')
    return data


def assert_development_context(root):
    """Deny installed production even when a caller supplies a development APP_ENV."""
    root = Path(root)
    marker = root / '.git/runa-bootstrap.json'
    if marker.exists() or marker.is_symlink():
        data = receipt(root)
        if data.get('mode') != 'dev':
            raise DevelopmentContextError('Development tooling is blocked in production or unknown installation mode.')
    environment = root / '.env'
    configured_production = False
    if environment.is_file():
        for line in environment.read_text().splitlines():
            key, sep, value = line.strip().removeprefix('export ').partition('=')
            if sep and key.strip() == 'APP_ENV' and value.split('#', 1)[0].strip().strip("\"'") == 'production':
                configured_production = True
    if os.environ.get('APP_ENV') == 'production' or configured_production:
        raise DevelopmentContextError('Development tooling is blocked in the production environment.')


def write_staging_receipt(root, data):
    path = Path(root) / '.git/runa-bootstrap.json'
    if path.is_symlink():
        raise RepositoryBoundaryError('Staging acquisition receipt must not be a symlink.')
    path.write_text(json.dumps(data, indent=2) + '\n')
