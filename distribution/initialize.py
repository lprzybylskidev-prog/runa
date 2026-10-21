#!/usr/bin/env python3
"""Initialize production from a complete customer repository, never a Runa release."""
import argparse
import json
import os
from pathlib import Path
import posixpath
import re
import signal
import subprocess
import sys
import tempfile
from urllib.parse import unquote, urlsplit

import bootstrap


def customer_url(value):
    if not value or value.startswith('-') or any(c.isspace() or ord(c) < 32 for c in value):
        raise bootstrap.BootstrapError('Select a credential-free customer HTTPS/SSH URL or absolute local repository.')
    parsed = urlsplit(value)
    if parsed.scheme in ('https', 'ssh'):
        if not parsed.hostname or parsed.password or (parsed.scheme == 'https' and parsed.username) or parsed.query or parsed.fragment:
            raise bootstrap.BootstrapError('Customer repository URLs must not contain credentials/query/fragment.')
        host, path = parsed.hostname, parsed.path
    elif re.fullmatch(r'[A-Za-z0-9_.-]+@[A-Za-z0-9.-]+:[A-Za-z0-9_./-]+', value):
        host, path = value.split('@', 1)[1].split(':', 1)
    elif Path(value).is_absolute():
        host, path = '', value
    else:
        raise bootstrap.BootstrapError('Unsupported customer repository transport.')
    if host.rstrip('.').lower() in ('github.com', 'www.github.com', 'ssh.github.com') and posixpath.normpath(unquote(path)).strip('/').lower().removesuffix('.git') == bootstrap.REPOSITORY.lower():
        raise bootstrap.BootstrapError('Production initializes from the customer repository, never the Runa release repository.')
    return value


def git(source, *args):
    environment = {key: value for key, value in os.environ.items() if not key.startswith('GIT_')}
    environment['GIT_TERMINAL_PROMPT'] = '0'
    return bootstrap.process(['git', '-c', 'core.hooksPath=/dev/null', '-c', 'protocol.ext.allow=never',
                              '-c', 'http.followRedirects=false', '-C', str(source), *args], env=environment)


def acquire(repository, target, destination):
    customer_url(repository)
    if (not re.fullmatch(r'[A-Za-z0-9][A-Za-z0-9._/-]{0,254}', target)
            or any(value in target for value in ('..', '//', '@{')) or target.endswith(('/', '.', '.lock'))
            or (target.startswith('refs/') and not target.startswith(('refs/heads/', 'refs/tags/')))):
        raise bootstrap.BootstrapError('Select a customer branch/tag/full lowercase commit SHA.')
    with tempfile.TemporaryDirectory(prefix='.runa-customer-', dir=destination.parent) as directory:
        home = Path(directory)
        source = home / 'source'
        git(home, 'clone', '--quiet', '--no-checkout', '--no-hardlinks', '--no-recurse-submodules', '--', repository, str(source))
        source.chmod(0o700)
        if re.fullmatch(bootstrap.SHA, target):
            git(source, 'fetch', '--quiet', '--no-tags', '--no-recurse-submodules', 'origin', target)
            ref = 'FETCH_HEAD'
        else:
            refs = ([target.replace('refs/heads/', 'refs/remotes/origin/', 1)] if target.startswith('refs/') else
                    ['refs/remotes/origin/' + target, 'refs/tags/' + target])
            present = []
            for value in refs:
                try:
                    git(source, 'show-ref', '--verify', value)
                    present.append(value)
                except bootstrap.BootstrapError:
                    pass
            if len(present) != 1:
                raise bootstrap.BootstrapError('Missing or ambiguous customer target; use refs/heads/... or refs/tags/... explicitly.')
            ref = present[0]
        sha = git(source, 'rev-parse', '--verify', ref + '^{commit}').decode().strip()
        if not re.fullmatch(bootstrap.SHA, sha) or (re.fullmatch(bootstrap.SHA, target) and target != sha):
            raise bootstrap.BootstrapError('Customer source commit identity is inconsistent.')
        git(source, 'fsck', '--strict', '--no-reflogs')
        entries = bootstrap.inspect_tree(source, home, sha)
        if any(any(name == prefix or name.startswith(prefix + '/') for prefix in bootstrap.OWNER_PATHS) for name, *_ in entries):
            raise bootstrap.BootstrapError('Customer revision contains owner-only source; version only the delivered customer project.')
        git(source, 'checkout', '--quiet', '--detach', sha)
        bootstrap.verify_files(source, entries)
        git(source, 'config', '--local', 'runa.applicationRemote', 'origin')
        receipt = {'schema_version': 1, 'repository': bootstrap.REPOSITORY, 'source': 'customer-repository',
                   'mode': 'prod', 'commit': sha, 'projection': {'version': 1, 'excluded': list(bootstrap.OWNER_PATHS)}}
        marker = source / '.git/runa-bootstrap.json'
        marker.write_text(json.dumps(receipt, indent=2) + '\n'); marker.chmod(0o600)
        bootstrap.publish(source, destination)
        return sha


def main(arguments=None):
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--repository', required=True, help='Customer repository URL; operator Git credentials remain external')
    parser.add_argument('--destination', required=True)
    parser.add_argument('--target', default='main')
    parser.add_argument('--install', choices=('now', 'later'), default='later')
    parser.add_argument('--settings-file')
    parser.add_argument('--simulation', action='store_true')
    args = parser.parse_args(arguments)
    bootstrap.host_checks()
    destination = bootstrap.destination_path(args.destination)
    sha = acquire(args.repository, args.target, destination)
    command = bootstrap.install_command(destination, 'prod', sha)
    command.append('--non-interactive')
    if args.settings_file: command.append('--settings-file=' + str(Path(args.settings_file).resolve()))
    if args.simulation: command.append('--simulation')
    import shlex
    print('Customer source prepared at ' + sha + '. From the destination run: ' + shlex.join(command), flush=True)
    if args.install == 'now':
        return subprocess.run(command, cwd=destination, stdin=subprocess.DEVNULL, check=False).returncode
    return 0


if __name__ == '__main__':
    def interrupted(signum, frame):
        raise KeyboardInterrupt
    signal.signal(signal.SIGTERM, interrupted)
    try:
        raise SystemExit(main())
    except (bootstrap.BootstrapError, OSError, ValueError, KeyError):
        error = sys.exc_info()[1]
        print(str(error) if isinstance(error, bootstrap.BootstrapError) else 'Customer initialization failed; inspect repository/source/prerequisites. Credentials are not included in diagnostics.', file=sys.stderr)
        raise SystemExit(1)
    except KeyboardInterrupt:
        print('Customer initialization interrupted; external staging cleaned and published source preserved.', file=sys.stderr)
        raise SystemExit(130)
