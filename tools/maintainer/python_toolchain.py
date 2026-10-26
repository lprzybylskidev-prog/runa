"""Owner compatibility composition over the delivered locked Python toolchain."""

from tools.application.python_toolchain import (
    PYTHON,
    ROOT,
    STATE,
    artifact_path,
    download,
    environment,
    setup,
)

if __name__ == "__main__":
    raise SystemExit(setup())

__all__ = ["ROOT", "STATE", "PYTHON", "environment", "download", "artifact_path", "setup"]
