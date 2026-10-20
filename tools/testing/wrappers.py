#!/usr/bin/env python3
"""Wrapper context/argument contracts without needing Docker-socket privileges."""
import contextlib
import io
from pathlib import Path
import runpy
import sys
import subprocess
import unittest
from unittest.mock import patch

ROOT = Path(__file__).resolve().parents[2]
sys.path.insert(0, str(ROOT / 'tools/project'))
import runtime


class WrapperContracts(unittest.TestCase):
    def invoke(self, name, args, inside):
        with patch.object(sys, 'argv', [str(ROOT / 'bin' / name), *args]), patch.object(runtime, 'inside', return_value=inside), patch.object(runtime, 'run') as call, contextlib.redirect_stdout(io.StringIO()):
            runpy.run_path(str(ROOT / 'bin' / name), run_name='__main__')
        return call

    def test_host_arguments_preserved_without_shell_interpretation(self):
        arguments = ['list', 'literal $(false); message', 'line\nbreak']
        for name, prefix in [('artisan', ['php', 'artisan']), ('composer', ['composer']), ('pnpm', ['pnpm']), ('seed', ['php', 'artisan', 'runa:seed'])]:
            call = self.invoke(name, arguments, False)
            self.assertEqual(call.call_args.args[0], runtime.compose() + ['exec', '-T', 'app', *prefix, *arguments])

    def test_devcontainer_uses_installed_tool_directly(self):
        call = self.invoke('artisan', ['list'], True)
        self.assertEqual(call.call_args.args[0], ['php', 'artisan', 'list'])

    def test_artisan_preserves_child_failure_exit_code(self):
        for inside in (False, True):
            with self.subTest(inside=inside), patch.object(sys, 'argv', [str(ROOT / 'bin/artisan'), 'list']), patch.object(runtime, 'inside', return_value=inside), patch.object(runtime, 'run', side_effect=subprocess.CalledProcessError(7, ['php', 'artisan', 'list'])), contextlib.redirect_stderr(io.StringIO()):
                with self.assertRaises(SystemExit) as result:
                    runpy.run_path(str(ROOT / 'bin/artisan'), run_name='__main__')
                self.assertEqual(result.exception.code, 7)

    def test_logs_default_and_help_are_nonmutating(self):
        call = self.invoke('logs', [], False)
        self.assertEqual(call.call_args.args[0], runtime.compose() + ['logs', '--tail', '100'])
        self.assertFalse(self.invoke('prod', ['--help'], False).called)


if __name__ == '__main__':
    unittest.main()
