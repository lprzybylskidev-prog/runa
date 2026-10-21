#!/usr/bin/env python3
"""Security regression checks; run inside the devcontainer. Resets only guarded runa_test."""
import os
from pathlib import Path
import subprocess

ROOT = Path(__file__).resolve().parents[3]


def invoke(command, values, expected_error=None):
    environment = os.environ.copy()
    environment.update(values)
    result = subprocess.run(['php', 'artisan', *command], cwd=ROOT, env=environment,
                            capture_output=True, text=True, check=False)
    if expected_error is None:
        assert result.returncode == 0, f'Expected success: {command[0]}'
    else:
        assert result.returncode != 0, f'Unsafe command succeeded: {command[0]}'
        assert expected_error in result.stdout + result.stderr, f'Guard was not responsible for rejection: {command[0]}'


for destructive in ['db:wipe', 'migrate:fresh', 'migrate:refresh', 'migrate:reset', 'migrate:rollback']:
    invoke([destructive, '--force'], {'APP_ENV': 'local'}, 'Destructive database operations require')
for override in [{'DB_DATABASE': 'runa'}, {'DB_HOST': 'localhost'}, {'DB_USERNAME': 'runa'},
                 {'DB_DATABASE': 'runa_test_prod'}]:
    invoke(['migrate:fresh', '--force'], {'APP_ENV': 'testing', **override}, 'Destructive database operations require')
invoke(['db:wipe', '--database=other', '--force'], {'APP_ENV': 'testing'}, 'guarded pgsql connection')
invoke(['runa:test-db:guard'], {'APP_ENV': 'testing'})
invoke(['migrate:fresh', '--force'], {'APP_ENV': 'testing'})
print('PASS: destructive commands reject unsafe environments, hosts, roles, database names and connection overrides; isolated test reset succeeds.')
