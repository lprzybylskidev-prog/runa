# Owner procedure and historical evidence

Source classification: `docs/runa/handbook/tooling/host-executor.md`. These owner-only procedures and dated execution records are retained separately from customer guidance. Historical claims keep their original context; current public operations are documented in the handbook.

The development workspace and host operator are trusted: code able to read the
private development credentials can request its allowlisted developer operations.
This is not isolation against malicious developer code running as that same user.
Production application processes do not receive the transport or its credentials;
only the separate authorized operator context can request production operations.
Owner installation, release and maintenance are excluded from every Artisan registry and management image. There is no maintainer management build context. Customer hooks remain a development capability. External distribution scripts operate outside the customer registry. See [bootstrap](../../maintainer/tooling/bootstrap.md).

Offline contracts: `python3 tools/maintainer/testing/host-executor.py`. Native host acceptance:
`python3 tools/maintainer/testing/host-executor-host.py` uses isolated process fixtures,
actual PHP -> Unix socket -> Docker execution, self-restart/disconnection,
operator recovery and scoped container/network cleanup with no data volumes.
