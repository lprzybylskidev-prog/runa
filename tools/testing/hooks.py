#!/usr/bin/env python3
"""Invoke installed hook scripts in an isolated repository; no fixture commits or real credentials."""
from pathlib import Path
import os
import subprocess
import tempfile

ROOT = Path(__file__).resolve().parents[2]


def main():
    actual = subprocess.run(['git', 'config', 'core.hooksPath'], cwd=ROOT, check=True, capture_output=True, text=True).stdout.strip()
    assert actual == '.githooks', 'Configure git core.hooksPath .githooks'
    with tempfile.TemporaryDirectory(prefix='runa-hook-contract-') as directory:
        root = Path(directory)
        env = {key: value for key, value in os.environ.items() if key not in ('GIT_INDEX_FILE', 'GIT_DIR', 'GIT_WORK_TREE')}
        def git(*args):
            return subprocess.run(['git', *args], cwd=root, env=env, check=True, capture_output=True).stdout
        def invoke(hook, *args, expected=None):
            result = subprocess.run([str(ROOT / '.githooks' / hook), *args], cwd=root, env=env, capture_output=True, text=True)
            if expected is None:
                assert result.returncode == 0, result.stdout + result.stderr
            else:
                assert result.returncode != 0 and expected in result.stdout + result.stderr, result.stdout + result.stderr
        def clear():
            git('read-tree', '--empty')
        git('init', '-q', '-b', 'roadmap/RM-2026-999-fixture')
        (root / 'bin').mkdir()
        (root / 'bin/git-hook').symlink_to(ROOT / 'bin/git-hook')
        knowledge = root / 'bin/knowledge'
        knowledge.write_text('#!/bin/sh\nexit 0\n'); knowledge.chmod(0o755)
        roadmap = root / 'docs/roadmaps/active/fixture.md'
        roadmap.parent.mkdir(parents=True)
        roadmap.write_text('---\nid: RM-2026-999\nstatus: active\n---\n')
        message = root / 'message.txt'
        for text, expected in [('bad message', 'Conventional'), ('feat: missing scope', 'scope'), ('feat(RM-2026-001): wrong scope', 'scope'), ('feat(RM-2026-999): fixture', None)]:
            message.write_text(text + '\n'); invoke('commit-msg', str(message), expected=expected)
        secret = root / '.env'
        secret.write_text('SAFE_FIXTURE=not-a-real-secret\n'); git('add', '.env')
        invoke('pre-commit', expected='Environment'); clear()
        secret = root / 'fixture.txt'
        # Synthetic AWS-shaped value, never a real credential; assembled to avoid storing a fixture secret in the project.
        secret.write_text('aws_access_key_id = ' + 'AKIA' + 'Q7M2N3R4T6V5X3W5' + '\n'); git('add', 'fixture.txt')
        secret.write_text('Unstaged safe content.\n')
        invoke('pre-commit', expected='returned non-zero'); clear()
        target = root / 'fixture.json'
        target.write_text('{"staged":true}\n'); git('add', 'fixture.json')
        target.write_text('{"unstaged":true}\n')
        before = git('show', ':fixture.json')
        invoke('pre-commit', expected='partially staged')
        assert git('show', ':fixture.json') == before and target.read_text() == '{"unstaged":true}\n'
        target.write_text('{"staged":true}\n'); git('add', 'fixture.json')
        invoke('pre-commit')
        assert git('show', ':fixture.json') == target.read_bytes() and b'"staged": true' in target.read_bytes()
        clear()
        vendored = root / 'src/runa/lang/pl/validation.php'
        vendored.parent.mkdir(parents=True)
        imported = b"<?php\nreturn array ('required' => 'Fixture message');\n"
        vendored.write_bytes(imported)
        git('add', str(vendored.relative_to(root)))
        invoke('pre-commit')
        assert git('show', ':' + str(vendored.relative_to(root))) == imported
        assert vendored.read_bytes() == imported
        clear()
        lockfile = root / 'src/pnpm-lock.yaml'
        locked = b"value:    [1,2]\n"
        lockfile.write_bytes(locked)
        git('add', str(lockfile.relative_to(root)))
        invoke('pre-commit')
        assert git('show', ':' + str(lockfile.relative_to(root))) == locked
        assert lockfile.read_bytes() == locked
        clear()
        knowledge.write_text('#!/bin/sh\necho "Index stale; run ./bin/knowledge index" >&2\nexit 1\n')
        invoke('pre-commit', expected='Index stale')
    print('PASS: installed hooks reject env/staged synthetic secrets/invalid messages/stale knowledge; partial staging preserved; full-file formatting succeeds; fixtures removed without commits.')


if __name__ == '__main__':
    main()
