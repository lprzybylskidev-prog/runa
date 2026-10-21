#!/usr/bin/env python3
"""Standalone Linux source acquisition. Network/disk writes; optional installer handoff.

No third-party modules or host PHP/Node. Deliver this reviewed file separately.
"""
import argparse
import base64
import ctypes
import errno
import getpass
import hashlib
import http.client
import json
import os
from pathlib import Path, PurePosixPath
import re
import shutil
import shlex
import signal
import stat
import subprocess
import sys
import tempfile
import urllib.error
import urllib.request

REPOSITORY = 'lprzybylskidev-prog/runa'
TAG = r'v(?:0|[1-9][0-9]*)\.(?:0|[1-9][0-9]*)\.(?:0|[1-9][0-9]*)'
SHA = r'[a-f0-9]{40}'
MIN_FREE = 5 * 1024 ** 3
MAX_SOURCE = 512 * 1024 ** 2
# Fixed release projection, not server-provided exclusions or customer config.
OWNER_PATHS = ('distribution', 'tools/maintainer', 'bootstrap.py', 'bin/project', 'bin/install-prod',
               'src/runa/tests', 'src/runa/tools', 'docs/runa/maintainer', 'src/runa/management/InstallCommand.php', 'src/runa/management/ProjectCommand.php')


class BootstrapError(Exception):
    """Only fixed, credential-free messages cross the public error boundary."""


class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, req, fp, code, msg, headers, newurl):
        raise BootstrapError('GitHub redirected the request. Verify the canonical repository; redirects are refused.')


class GitHub:
    def __init__(self, repository, token=''):
        if not re.fullmatch(r'[A-Za-z0-9][A-Za-z0-9-]{0,38}/[A-Za-z0-9][A-Za-z0-9_.-]{0,99}', repository) or repository.endswith('.git') or '..' in repository:
            raise BootstrapError('Repository must be a canonical GitHub owner/name, without a URL or credentials.')
        self.repository = repository
        self.token = token
        self.url = 'https://github.com/' + repository + '.git'
        self.opener = urllib.request.build_opener(urllib.request.ProxyHandler({}), NoRedirect())

    def get(self, suffix=''):
        headers = {'Accept': 'application/vnd.github+json', 'User-Agent': 'Runa-bootstrap', 'X-GitHub-Api-Version': '2022-11-28'}
        if self.token:
            headers['Authorization'] = 'Bearer ' + self.token
        request = urllib.request.Request('https://api.github.com/repos/' + self.repository + suffix, headers=headers)
        try:
            with self.opener.open(request, timeout=30) as response:
                raw = response.read(2 * 1024 ** 2 + 1)
            if len(raw) > 2 * 1024 ** 2:
                raise BootstrapError('GitHub metadata exceeds the supported size.')
            return json.loads(raw)
        except urllib.error.HTTPError as error:
            messages = {401: 'GitHub rejected the credential.', 403: 'GitHub denied access or the API rate limit was reached.',
                        404: 'GitHub repository/release was not found or the credential has no access.'}
            raise BootstrapError(messages.get(error.code, 'GitHub metadata request failed.')) from None
        except (OSError, ValueError, urllib.error.URLError, http.client.HTTPException):
            raise BootstrapError('GitHub TLS/network/metadata failure. Check connectivity and retry.') from None

    def verify_repository(self):
        data = self.get()
        if data.get('full_name', '').lower() != self.repository.lower() or data.get('clone_url', '').lower() != self.url.lower() or not isinstance(data.get('id'), int):
            raise BootstrapError('GitHub repository identity differs from the authorized owner/name.')
        return data['id']

    def releases(self):
        # Construct pagination locally; never follow credential-bearing Link URLs.
        tags = []
        for page in range(1, 11):
            releases = self.get('/releases?per_page=100&page=' + str(page))
            if not isinstance(releases, list):
                raise BootstrapError('Invalid GitHub release listing.')
            tags.extend(item['tag_name'] for item in releases if not item.get('draft') and not item.get('prerelease') and re.fullmatch(TAG, item.get('tag_name', '')))
            if len(releases) < 100:
                return tags
        raise BootstrapError('Release listing exceeds 1000 entries; supply an explicit tag.')

    def resolve(self, tag):
        if not re.fullmatch(TAG, tag):
            raise BootstrapError('Select an exact stable vX.Y.Z release tag.')
        release = self.get('/releases/tags/' + tag)
        if release.get('tag_name') != tag or release.get('draft') or release.get('prerelease') or not release.get('published_at'):
            raise BootstrapError('Selected tag is not a published stable GitHub release.')
        ref = self.get('/git/ref/tags/' + tag)
        obj = ref.get('object', {})
        if ref.get('ref') != 'refs/tags/' + tag or obj.get('type') != 'tag' or not re.fullmatch(SHA, obj.get('sha', '')):
            raise BootstrapError('Release must point to an annotated Git tag.')
        annotation = self.get('/git/tags/' + obj['sha'])
        commit = annotation.get('object', {})
        if annotation.get('sha') != obj['sha'] or annotation.get('tag') != tag or commit.get('type') != 'commit' or not re.fullmatch(SHA, commit.get('sha', '')):
            raise BootstrapError('Annotated tag/commit identity is invalid.')
        return {'release_id': release['id'], 'tag_object': obj['sha'], 'commit': commit['sha']}


def token_from_file(path):
    try:
        descriptor = os.open(path, os.O_RDONLY | os.O_NOFOLLOW | os.O_NONBLOCK)
        with os.fdopen(descriptor, 'rb') as stream:
            info = os.fstat(stream.fileno())
            if not stat.S_ISREG(info.st_mode) or info.st_uid != os.getuid() or info.st_mode & 0o077 or info.st_size > 4096:
                raise BootstrapError('Credential file must be a private regular file owned by the current user (mode 600 or 400, at most 4 KiB).')
            return validate_token(stream.read().decode('ascii').strip())
    except (OSError, UnicodeError):
        raise BootstrapError('Cannot read the private credential file.') from None


def validate_token(token):
    if not token or len(token) > 4096 or not re.fullmatch(r'[A-Za-z0-9_]+', token):
        raise BootstrapError('Invalid GitHub token format.')
    return token


def process(arguments, *, cwd=None, env=None, timeout=600, input_data=None):
    try:
        child = subprocess.Popen(arguments, cwd=cwd, env=env, stdin=subprocess.PIPE if input_data is not None else subprocess.DEVNULL,
                                 stdout=subprocess.PIPE, stderr=subprocess.DEVNULL, start_new_session=True)
        try:
            output, _ = child.communicate(input=input_data.encode() if input_data is not None else None, timeout=timeout)
        except BaseException:
            # Include Git's HTTPS/index-pack children; interrupted downloads leave no workers.
            try:
                os.killpg(child.pid, signal.SIGKILL)
            except ProcessLookupError:
                pass
            child.communicate()
            raise
    except (OSError, subprocess.TimeoutExpired):
        raise BootstrapError('Host tool failed or timed out; check prerequisites/connectivity and retry.') from None
    if child.returncode:
        raise BootstrapError('Host tool failed. For GitHub downloads check access, credential scope, connectivity and disk space.')
    return output


def host_checks():
    if sys.platform != 'linux' or sys.version_info < (3, 11):
        raise BootstrapError('Require Linux and Python >=3.11.')
    for command, minimum in ((['git', '--version'], (2, 39)), (['docker', 'version', '--format', '{{.Server.Version}}'], (26, 0)), (['docker', 'compose', 'version', '--short'], (2, 35))):
        output = process(command, timeout=30).decode('ascii', errors='replace')
        match = re.search(r'(\d+)\.(\d+)', output)
        if not match or tuple(map(int, match.groups())) < minimum:
            raise BootstrapError('Require Git >=2.39, Docker Engine >=26 and Compose >=2.35 with daemon access.')


def destination_path(value):
    path = Path(os.path.abspath(os.path.expanduser(value)))
    if path.parent.resolve() != path.parent or path.parent.stat().st_uid != os.getuid() or not os.access(path.parent, os.W_OK | os.X_OK):
        raise BootstrapError('Destination parent must exist, have no symlink components and be writable/owned by the current user.')
    if path.exists() or path.is_symlink():
        raise BootstrapError('Destination already exists. Choose a new path; existing customer files are never overwritten.')
    if shutil.disk_usage(path.parent).free < MIN_FREE:
        raise BootstrapError('Destination filesystem requires at least 5 GiB free space (image builds may require more).')
    return path


def git_environment(home, token=''):
    # Do not inherit Git config, tracing, helpers, proxies or TLS overrides.
    env = {'PATH': os.defpath, 'HOME': str(home), 'LC_ALL': 'C', 'GIT_CONFIG_NOSYSTEM': '1',
           'GIT_CONFIG_GLOBAL': '/dev/null', 'GIT_TERMINAL_PROMPT': '0', 'GIT_ALLOW_PROTOCOL': 'https'}
    if token:
        # Secret is transient process environment, never argv/config/source. Git tracing is absent.
        header = base64.b64encode(('x-access-token:' + token).encode()).decode()
        env.update(GIT_CONFIG_COUNT='1', GIT_CONFIG_KEY_0='http.https://github.com/.extraHeader', GIT_CONFIG_VALUE_0='Authorization: Basic ' + header)
    return env


def git(source, home, *arguments, token='', input_data=None):
    return process(['git', '-c', 'http.followRedirects=false', '-c', 'http.sslVerify=true',
                    '-c', 'credential.helper=', '-c', 'core.hooksPath=/dev/null', '-c', 'core.attributesFile=/dev/null',
                    '-c', 'fetch.fsckObjects=true', '-c', 'transfer.fsckObjects=true',
                    '-C', str(source), *arguments], env=git_environment(home, token), input_data=input_data)


def inspect_tree(source, home, commit):
    entries = []
    total = 0
    for record in git(source, home, 'ls-tree', '-r', '-z', '-l', commit).split(b'\0'):
        if not record:
            continue
        metadata, raw_path = record.split(b'\t', 1)
        mode, kind, oid, size = metadata.split()
        path = raw_path.decode('utf-8')
        parts = PurePosixPath(path).parts
        if not parts or PurePosixPath(path).as_posix() != path or path.startswith('/') or any(part in ('.', '..') or part.lower() == '.git' for part in parts) or '\\' in path or any(ord(char) < 32 or ord(char) == 127 for char in path):
            raise BootstrapError('Release contains an unsafe source path.')
        if mode not in (b'100644', b'100755', b'120000') or kind != b'blob':
            raise BootstrapError('Release contains unsupported objects/submodules.')
        total += int(size)
        if total > MAX_SOURCE or len(entries) >= 50000:
            raise BootstrapError('Release source exceeds 512 MiB or 50000 files.')
        entries.append((path, mode.decode(), oid.decode(), int(size)))
    if not entries:
        raise BootstrapError('Release source is empty.')
    return entries


def verify_files(source, entries):
    digest = hashlib.sha256()
    names = {path for path, _, _, _ in entries}
    for path, mode, oid, size in entries:
        file = source / path
        # Git normally refuses linked parent paths too; enforce independently.
        if any(parent.is_symlink() for parent in file.parents if parent != source.parent):
            raise BootstrapError('Release contains a linked source directory.')
        if mode == '120000':
            if not file.is_symlink():
                raise BootstrapError('Expected a source symlink.')
            target = os.readlink(file)
            if target.startswith('/') or '\\' in target or any(ord(char) < 32 for char in target):
                raise BootstrapError('Release contains an unsafe symlink.')
            try:
                resolved = file.resolve(strict=True).relative_to(source)
            except (OSError, ValueError, RuntimeError):
                raise BootstrapError('Release symlink escapes source, is dangling or cyclic.') from None
            if resolved.as_posix() not in names:
                raise BootstrapError('Release symlink target is not tracked source.')
            content = target.encode()
        else:
            if file.is_symlink() or not file.is_file() or file.stat().st_size != size:
                raise BootstrapError('Release file type or size differs from its Git object.')
            if bool(file.stat().st_mode & 0o111) != (mode == '100755'):
                raise BootstrapError('Release executable mode differs from its Git object.')
            content = file.read_bytes()
        if hashlib.sha1(b'blob ' + str(len(content)).encode() + b'\0' + content).hexdigest() != oid:
            raise BootstrapError('Release file hash differs from its verified Git object.')
        value = hashlib.sha256(content).hexdigest()
        digest.update(json.dumps([path, mode, value], separators=(',', ':')).encode() + b'\n')
    return digest.hexdigest()


def publish(source, destination):
    # Linux no-replace rename: never replace even a concurrently created empty directory.
    libc = ctypes.CDLL(None, use_errno=True)
    try:
        rename = libc.renameat2
    except AttributeError:
        raise BootstrapError('Host libc must provide renameat2 for atomic publication without replacement.') from None
    rename.argtypes = [ctypes.c_int, ctypes.c_char_p, ctypes.c_int, ctypes.c_char_p, ctypes.c_uint]
    rename.restype = ctypes.c_int
    if rename(-100, os.fsencode(source), -100, os.fsencode(destination), 1):
        if ctypes.get_errno() == errno.EEXIST:
            raise BootstrapError('Destination appeared during download; it has been preserved. Choose a new path.')
        raise BootstrapError('Atomic source publication failed; destination was not replaced.')


def acquire(client, tag, destination, mode, expected_commit=None, expected_digest=None):
    if mode != 'dev':
        raise BootstrapError('Runa release acquisition is development-only; initialize production from the customer repository.')
    identity = client.resolve(tag)
    repository_id = client.verify_repository()
    if expected_commit and identity['commit'] != expected_commit:
        raise BootstrapError('Release differs from the independently supplied expected commit.')
    with tempfile.TemporaryDirectory(prefix='.runa-bootstrap-', dir=destination.parent) as staging:
        home = Path(staging)
        source = home / 'source'
        source.mkdir(mode=0o700)
        git(source, home, 'init', '--quiet', '--template=')
        git(source, home, 'remote', 'add', 'origin', client.url)
        git(source, home, 'fetch', '--quiet', '--depth=1', '--no-tags', 'origin', 'refs/tags/' + tag + ':refs/tags/' + tag, token=client.token)
        actual_tag = git(source, home, 'rev-parse', 'refs/tags/' + tag).decode().strip()
        actual_commit = git(source, home, 'rev-parse', 'refs/tags/' + tag + '^{commit}').decode().strip()
        if (actual_tag, actual_commit) != (identity['tag_object'], identity['commit']):
            raise BootstrapError('Fetched tag/commit differs from resolved GitHub identity; retry after investigation.')
        git(source, home, 'fsck', '--strict', '--no-reflogs')
        entries = inspect_tree(source, home, actual_commit)
        git(source, home, 'checkout', '--quiet', '--detach', actual_commit)
        digest = verify_files(source, entries)
        if expected_digest and digest != expected_digest:
            raise BootstrapError('Release differs from the independently supplied source digest.')
        if (source / 'VERSION').read_text().strip() != tag[1:]:
            raise BootstrapError('Release VERSION differs from the selected tag.')
        if client.resolve(tag) != identity or client.verify_repository() != repository_id:
            raise BootstrapError('GitHub release/tag/repository changed during acquisition; no source published.')
        receipt = dict(schema_version=1, repository=client.repository, repository_id=repository_id,
                       tag=tag, **identity, source_sha256=digest, mode=mode, state='downloaded', installed_profile='source',
                       projection={'version': 1, 'excluded': list(OWNER_PATHS)},
                       trust='GitHub HTTPS + annotated tag and Git object hashes; no independent signature')
        patterns = ['/*', *['!/' + name + ('/' if name in ('distribution', 'tools/maintainer') else '') for name in OWNER_PATHS]]
        git(source, home, 'sparse-checkout', 'init', '--no-cone')
        git(source, home, 'sparse-checkout', 'set', '--no-cone', '--stdin', input_data='\n'.join(patterns) + '\n')
        if any((source / name).exists() or (source / name).is_symlink() for name in OWNER_PATHS):
            raise BootstrapError('Owner distribution sources remain in the projected installation.')
        # Keep origin as a credential-free upstream read source. An impossible
        # push URL prevents accidental publishing without repointing client history.
        git(source, home, 'config', 'remote.origin.pushurl', 'disabled://runa-read-only-upstream')
        (source / '.git/runa-bootstrap.json').write_text(json.dumps(receipt, indent=2) + '\n')
        publish(source, destination)
    return receipt


def install_command(destination, mode, commit):
    helper = Path(__file__).resolve().with_name('installation.py')
    if not helper.is_file():
        raise BootstrapError('Owner installation helper is missing beside the external launcher; obtain the complete owner tool directory.')
    # The child imports installed operational primitives but executes only the external owner helper.
    program = 'import runpy,sys; sys.path.insert(0,"tools/application"); path=sys.argv.pop(1); runpy.run_path(path,run_name="__main__")'
    return [sys.executable, '-c', program, str(helper), mode, commit]


def main(argv=None):
    parser = argparse.ArgumentParser(description='Acquire a verified Runa release for first development; production initializes from the customer repository.')
    parser.add_argument('--repository', help='Authorized GitHub owner/name (public access needs no token)')
    parser.add_argument('--tag', help='Exact published annotated vX.Y.Z release')
    parser.add_argument('--destination', help='New directory under an existing owned parent')
    parser.add_argument('--install', choices=('now', 'later'))
    parser.add_argument('--non-interactive', action='store_true', help='Require every selection; never prompt')
    parser.add_argument('--profile', choices=('low', 'standard', 'high'), help='Unified development install resource profile')
    credentials = parser.add_mutually_exclusive_group()
    credentials.add_argument('--token-file', help='Private GitHub read credential file (never pass a token as an argument)')
    credentials.add_argument('--ask-token', action='store_true', help='Read private access token without echo')
    parser.add_argument('--expected-commit', help='Independently delivered full lowercase commit SHA')
    parser.add_argument('--expected-digest', help='Independently delivered SHA-256 source digest')
    args = parser.parse_args(argv)
    interactive = not args.non_interactive and sys.stdin.isatty() and sys.stderr.isatty()
    if not interactive and (args.ask_token or not all((args.repository, args.tag, args.destination, args.install))):
        raise BootstrapError('Automation requires --repository, --tag, --destination and --install; use --token-file for private access.')
    for value, pattern in ((args.expected_commit, SHA), (args.expected_digest, r'[a-f0-9]{64}')):
        if value is not None and not re.fullmatch(pattern, value):
            raise BootstrapError('Expected commit/digest must be a full lowercase hexadecimal identity.')
    args.repository = args.repository or input('Authorized GitHub repository [' + REPOSITORY + ']: ').strip() or REPOSITORY
    token = token_from_file(args.token_file) if args.token_file else ''
    if args.ask_token or (interactive and not token and input('Private GitHub access? [y/N]: ').strip().lower() == 'y'):
        token = validate_token(getpass.getpass('GitHub read token (not saved): '))
    client = GitHub(args.repository, token)
    host_checks()
    client.verify_repository()
    if not args.tag:
        print('Accessible stable releases: ' + ', '.join(client.releases()))
        args.tag = input('Exact release tag: ').strip()
    args.destination = args.destination or input('New destination directory: ').strip()
    args.install = args.install or input('Install now or later (now/later): ').strip()
    if args.install not in ('now', 'later'):
        raise BootstrapError('Choose installation now/later.')
    destination = destination_path(args.destination)
    receipt = acquire(client, args.tag, destination, 'dev', args.expected_commit, args.expected_digest)
    command = install_command(destination, 'dev', receipt['commit'])
    if args.profile:
        command += ['--profile=' + args.profile]
    if not interactive:
        command.append('--non-interactive')
    print('Source verified and published. Installation has not run. Receipt: .git/runa-bootstrap.json')
    print('Source SHA-256: ' + receipt['source_sha256'])
    print('From the destination directory run: ' + shlex.join(command))
    if args.install == 'now':
        print('Starting containerized development installation.', flush=True)
        try:
            result = subprocess.run(command, cwd=destination, stdin=None if interactive else subprocess.DEVNULL, check=False)
        except OSError:
            raise BootstrapError('Source is ready; installer could not start. Use the printed command from the destination.') from None
        if result.returncode:
            raise BootstrapError('Source is ready; installation failed. Inspect installer diagnostics and resume with the printed command. Bootstrap will not overwrite this directory.')
        print('Containerized installation command completed successfully.')
    return 0


if __name__ == '__main__':
    def interrupted(signum, frame):
        raise KeyboardInterrupt

    signal.signal(signal.SIGTERM, interrupted)
    try:
        raise SystemExit(main())
    except (BootstrapError, OSError, ValueError, KeyError, TypeError, UnicodeError):
        error = sys.exc_info()[1]
        print('ERROR: ' + (str(error) if isinstance(error, BootstrapError) else 'Bootstrap failed; check metadata, source and destination. No credentials are included in diagnostics.'), file=sys.stderr)
        raise SystemExit(1)
    except (KeyboardInterrupt, EOFError):
        print('Bootstrap interrupted. Staging is removed; published source, if any, is preserved. Retry downloads with a new destination or use the printed installer command.', file=sys.stderr)
        raise SystemExit(130)
