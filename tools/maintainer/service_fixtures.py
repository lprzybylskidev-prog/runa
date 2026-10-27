"""Immutable owner acceptance inputs; these are not baseline product services."""

from __future__ import annotations

import json
import subprocess

REDIS_IMAGE = (
    "redis:8.4.7-alpine3.22@sha256:0514fa59e3d84e2f3be66731c5401fc2a85b8ea71b49c0116c850f6a558076f4"
)


def redis_image() -> str:
    arguments = ["docker", "image", "inspect", REDIS_IMAGE]
    result = subprocess.run(arguments, capture_output=True, text=True)
    if result.returncode:
        subprocess.run(["docker", "pull", REDIS_IMAGE], check=True)
        result = subprocess.run(arguments, capture_output=True, text=True, check=True)
    identity = json.loads(result.stdout)
    expected = "redis@" + REDIS_IMAGE.split("@", 1)[1]
    if len(identity) != 1 or expected not in identity[0].get("RepoDigests", []):
        raise ValueError("Custom-service fixture differs from its pinned Redis artifact.")
    return REDIS_IMAGE
