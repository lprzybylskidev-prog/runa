"""Immutable image probes share containers while keeping role isolation and native exits."""

from __future__ import annotations

import subprocess
from pathlib import Path
from unittest.mock import patch

import pytest

from tests.tools.runa.support.deployment import deployment

ROOT = Path(__file__).resolve().parents[4]


def probes(root: Path) -> list[list[str]]:
    calls: list[list[str]] = []
    instance = deployment(root)

    def run(arguments: list[str], **kwargs: object) -> subprocess.CompletedProcess[str]:
        calls.append(arguments)
        if arguments[0] == "compose":
            value = "runa-production:fixture\nruna-nginx:fixture\n"
        elif arguments[:3] == ["docker", "image", "inspect"]:
            value = "nginx" if arguments[-1].startswith("runa-nginx:") else "runa"
        else:
            value = ""
        return subprocess.CompletedProcess(arguments, 0, value)

    with patch.object(instance.context, "run", side_effect=run):
        instance.runtime.production.verify_images(["compose"], {}, ROOT)
    return [argv for argv in calls if argv[:2] == ["docker", "run"]]


def test_php_probes_share_only_equal_context_and_keep_nginx_role_separate(tmp_path: Path) -> None:
    commands = probes(tmp_path)
    assert len(commands) == 2
    php, nginx = commands
    assert php[php.index("--entrypoint") + 1] == "sh"
    assert "runa-production:fixture" in php
    assert "runa-nginx:fixture" in nginx
    assert "app:127.0.0.1" in nginx and nginx[-1] == "-t"
    for argv in commands:
        assert {
            "--rm",
            "--read-only",
            "--cap-drop=ALL",
            "--security-opt=no-new-privileges:true",
        } <= set(argv)
    assert php[-1].startswith("declare(strict_types=1);")


@pytest.mark.parametrize(
    "php_exit,fpm_exit,tool,expected",
    [(0, 0, False, 0), (19, 0, False, 19), (0, 23, False, 23), (0, 0, True, 1)],
)
def test_grouped_shell_retains_policy_tool_and_fpm_failure_status(
    tmp_path: Path,
    php_exit: int,
    fpm_exit: int,
    tool: bool,
    expected: int,
) -> None:
    php = probes(tmp_path)[0]
    executable = tmp_path / "executables"
    executable.mkdir()
    for name, variable in (("php", "PHP_EXIT"), ("php-fpm", "FPM_EXIT")):
        path = executable / name
        path.write_text(
            '#!/bin/sh\nprintf "%s\\n" "' + name + '" >> "$TRACE"\nexit "$' + variable + '"\n'
        )
        path.chmod(0o700)
    if tool:
        (executable / "composer").write_text("#!/bin/sh\nexit 0\n")
        (executable / "composer").chmod(0o700)
    trace = tmp_path / "trace"
    result = subprocess.run(
        ["/bin/sh", *php[php.index("-c") :]],
        env={
            "PATH": str(executable),
            "TRACE": str(trace),
            "PHP_EXIT": str(php_exit),
            "FPM_EXIT": str(fpm_exit),
        },
        capture_output=True,
    )
    assert result.returncode == expected
    assert trace.read_text().splitlines() == (["php"] if php_exit or tool else ["php", "php-fpm"])
