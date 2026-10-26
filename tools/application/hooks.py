"""Fast staged-content checks; no full test/static-analysis suite."""

import os
import re
import subprocess
import tempfile
from pathlib import Path

from tools.application.processes import ApplicationProcesses
from tools.core.process import SubprocessRunner


def git_command(root, *args, data=None, processes):
    return processes.run(
        ["git", *args], cwd=root, input=data, check=True, capture_output=True
    ).stdout


def commit_message(root, path):
    lines = Path(path).read_text().splitlines()
    first = lines[0] if lines else ""
    match = re.fullmatch(
        r"(feat|fix|docs|style|refactor|perf|test|build|chore|revert|merge)(?:\(([A-Za-z0-9_.-]+)\))?!?: [^\s].*",
        first,
    )
    if not match:
        raise ValueError("Use Conventional Commits: type(RM-YYYY-NNN): description")
    from tools.application.documentation import roadmap_root

    roadmaps = roadmap_root(root)
    active = list((roadmaps / "active").glob("*.md"))
    if len(active) > 1:
        raise ValueError("At most one active roadmap is allowed.")
    if active:
        roadmap = re.search(r"^id: (RM-\d{4}-\d{3})$", active[0].read_text(), re.M)
        if not roadmap or match[2] != roadmap[1]:
            raise ValueError("Commit scope must match the active roadmap ID.")


def pre_commit(root, tools_root=None, check_knowledge=True, *, history=None, processes=None):
    from functools import partial

    processes = processes or ApplicationProcesses(SubprocessRunner(), dict(os.environ))
    command = processes.run
    git = partial(git_command, processes=processes)
    tools_root = tools_root or root
    entries = (
        git(root, "diff", "--cached", "--name-only", "--diff-filter=ACMR", "-z")
        .decode()
        .split("\0")
    )
    entries = [name for name in entries if name]
    deleted = (
        git(root, "diff", "--cached", "--no-renames", "--name-only", "--diff-filter=D", "-z")
        .decode()
        .split("\0")
    )
    for name in filter(None, deleted):
        if re.fullmatch(r"docs/app/roadmaps/closed/[^/]+\.md", name) or (
            history is not None and history(root, name, None)
        ):
            raise ValueError("Immutable historical source deleted: " + name)
    transformed = []
    with tempfile.TemporaryDirectory(prefix="runa-staged-") as directory:
        snapshot = Path(directory) / "content"
        snapshot.mkdir()
        for name in entries:
            parts = Path(name).parts
            if any(
                part == ".env" or (part.startswith(".env.") and not part.endswith(".example"))
                for part in parts
            ):
                raise ValueError(f"Environment files cannot be committed: {name}")
            row = git(root, "ls-files", "-s", "--", name).decode().split()[0]
            if row not in ("100644", "100755", "120000"):
                raise ValueError(f"Unsupported staged file mode: {name}")
            data = git(root, "show", ":" + name)
            if len(data) > 10 * 1024 * 1024 or (b"\0" in data and len(data) > 1024 * 1024):
                raise ValueError(f"Unwanted large file: {name}")
            if re.search(rb"-----BEGIN (?:RSA |EC |OPENSSH |DSA )?PRIVATE KEY-----", data):
                raise ValueError(f"Private key material is forbidden: {name}")
            target = snapshot / name
            target.parent.mkdir(parents=True, exist_ok=True)
            target.write_bytes(data)
            if row == "120000":
                continue
            historical = bool(re.fullmatch(r"docs/app/roadmaps/closed/[^/]+\.md", name))
            if history is not None:
                historical = history(root, name, data) or historical
            if historical:
                previous = command(["git", "show", "HEAD:" + name], cwd=root, capture_output=True)
                if previous.returncode == 0 and previous.stdout != data:
                    raise ValueError("Immutable historical source changed: " + name)
                # Preserve archived source bytes while still scanning staged content for secrets.
                continue
            formatted = data
            suffix = Path(name).suffix
            if suffix == ".php" and name == "src/runa/lang/pl/validation.php":
                # Imported snapshot bytes are audited by the localization contract.
                command(["php", "-l", str(target)], cwd=tools_root, check=True, capture_output=True)
            elif suffix == ".php":
                command(
                    [
                        str(tools_root / "src/vendor/bin/pint"),
                        "--config",
                        str(tools_root / "src/pint.json"),
                        str(target),
                    ],
                    cwd=tools_root,
                    check=True,
                    capture_output=True,
                )
                formatted = target.read_bytes()
            elif (
                suffix in (".md", ".json", ".yaml", ".yml", ".vue", ".ts", ".js", ".mjs", ".css")
                and name != "src/pnpm-lock.yaml"
            ):
                formatted = command(
                    [
                        "pnpm",
                        "--dir",
                        str(tools_root / "src"),
                        "exec",
                        "prettier",
                        "--stdin-filepath",
                        str(root / name),
                    ],
                    cwd=tools_root,
                    input=data,
                    capture_output=True,
                    check=True,
                ).stdout
            if suffix in (".vue", ".ts", ".js", ".mjs") and (
                name.startswith(
                    (
                        "src/app/resources/",
                        "src/runa/resources/",
                        "src/runa/tests/frontend/",
                        "src/runa/tests/e2e/",
                    )
                )
                or (name.startswith("src/") and name.count("/") == 1)
            ):
                command(
                    [
                        "pnpm",
                        "--dir",
                        str(tools_root / "src"),
                        "exec",
                        "eslint",
                        "--stdin",
                        "--stdin-filename",
                        str(root / name),
                        "--max-warnings=0",
                    ],
                    cwd=tools_root,
                    input=formatted,
                    check=True,
                )
            elif suffix == ".py" or data.startswith(b"#!/usr/bin/env python3"):
                python = tools_root / ".runtime/python/venv/bin/python"
                if not python.is_file():
                    raise ValueError("Pinned Ruff is missing. Run ./bin/artisan runa:python:setup.")
                config = tools_root / "tools/application/python/ruff.toml"
                formatted = command(
                    [
                        str(python),
                        "-m",
                        "ruff",
                        "format",
                        "--config",
                        str(config),
                        "--stdin-filename",
                        name,
                        "-",
                    ],
                    cwd=tools_root,
                    input=data,
                    capture_output=True,
                    check=True,
                ).stdout
                command(
                    [
                        str(python),
                        "-m",
                        "ruff",
                        "check",
                        "--config",
                        str(config),
                        "--stdin-filename",
                        name,
                        "-",
                    ],
                    cwd=tools_root,
                    input=formatted,
                    capture_output=True,
                    check=True,
                )
                compile(formatted, name, "exec")
            elif data.startswith(b"#!/bin/sh"):
                command(["sh", "-n"], cwd=tools_root, input=formatted, check=True)
            if formatted != data:
                path = root / name
                if path.is_symlink() or not path.exists() or path.read_bytes() != data:
                    raise ValueError(
                        f"Formatter would change partially staged file {name}. Format it and stage the intended hunks explicitly."
                    )
                transformed.append((name, formatted, row))
        from tools.application.secret_scanner import SecretScanner

        SecretScanner(tools_root / ".runtime/tools/gitleaks", processes).scan(snapshot)
    if check_knowledge:
        command(
            ["php", str(root / "artisan"), "runa:knowledge:stale"],
            cwd=root,
            check=True,
            stdout=subprocess.DEVNULL,
        )
    # Do not mutate index/worktree until all checks and partial-staging checks pass.
    for name, formatted, mode in transformed:
        path = root / name
        # Recheck in case an editor changed the worktree during the checks.
        if path.is_symlink() or path.read_bytes() != git(root, "show", ":" + name):
            raise ValueError(
                f"File changed during checks: {name}; retry after staging intended content."
            )
        path.write_bytes(formatted)
        blob = git(root, "hash-object", "-w", "--stdin", data=formatted).decode().strip()
        git(root, "update-index", "--cacheinfo", f"{mode},{blob},{name}")
    if transformed and check_knowledge:
        # Formatting indexed docs can make knowledge stale; instruct explicit indexing, never reindex in a hook.
        command(
            ["php", str(root / "artisan"), "runa:knowledge:stale"],
            cwd=root,
            check=True,
            stdout=subprocess.DEVNULL,
        )
    print(
        "PASS: staged secrets, formatting, quick lint, repository sanity and knowledge freshness."
    )


def git(root, *args, data=None):
    return git_command(
        root, *args, data=data, processes=ApplicationProcesses(SubprocessRunner(), dict(os.environ))
    )
