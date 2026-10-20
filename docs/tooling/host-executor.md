# Normal Artisan host operations

In the configured development terminal use `php artisan` for local and host work:

```sh
php artisan runa:dev up
php artisan runa:dev logs app
php artisan runa:test
php artisan runa:quality show
php artisan runa:production status
php artisan runa:deploy <exact-version>
```

Local commands keep their PHP Actions. Host operations use the private workspace
executor automatically; only explicit `--plan` prints a nonexecuting preview.
There is no host PHP requirement, Docker socket in the container or TCP listener.
The internal executor runs as the native host operator and validates the command
again in the pinned, isolated Artisan registry before invoking existing mechanics.

Initial dev prepare/up starts the executor. On production, the separate operator
PHP environment receives the private transport mount; HTTP/worker/scheduler
runtime images/services receive neither management commands nor transport state.
Use the host-only `./bin/artisan --operator` to enter the prepared operator shell,
then run normal `php artisan` commands there, including when the app is stopped.
This shell has no network or Docker socket and uses the pinned management PHP
runtime, not host PHP. First production installation prepares the executor too.

## Recovery and lifecycle

Use these transports from the owning native host checkout only for setup/recovery:

```sh
./bin/artisan --recover-host
./bin/artisan --operator
```

`--recover-host` gracefully stops an old executor and prepares a fresh one. It
waits for active operations to drain; it does not kill a deployment to make an
upgrade of the executor appear successful. `./bin/artisan --stop-host` provides
host-side shutdown if PHP is unavailable. Normally use `php artisan runa:host stop`
and `php artisan runa:host status [operation-id]`. Registry/source changes require
explicit recovery rather than silently trusting changed host capabilities.

The process is detached from the application and can recover/restart a stopped
target. Devcontainer initialization keeps its pre-environment host prepare
transport; the missing-autoloader setup bridge remains an initial setup exception.
Stopping the application does not stop the host executor. Stop the executor
explicitly when retiring the workspace; active application/database volumes remain.

## Local trust and protocol

`.runtime/host` and its operations directory are mode 700 and operator-owned;
client credentials, socket, receipts and logs are private. The daemon is bound to
the canonical workspace selected at startup and to the inspected immutable
management image/source identity. Linux peer credentials must match the operator
UID. A private random workspace token plus fresh per-connection challenge binds
the request; atomically reserved request IDs reject replay. Frames/arguments,
validation time/output, concurrent connections and per-operation logs are bounded.

Only command names and literal arguments cross the transport. No caller-selected
executable, shell, cwd, mounts or environment is accepted; independent Artisan
parsing reuses the same registry. The executor retains established operational
locks, first-install/update fences, image identities and destructive confirmation
rules. Standard input/output/error streams and exit codes are forwarded. Private
operation logs may include ordinary application/tool output; keep them private and
out of knowledge ingestion or public issue attachments.

The development workspace and host operator are trusted: code able to read the
private development credentials can request its allowlisted developer operations.
This is not isolation against malicious developer code running as that same user.
Production application processes do not receive the transport or its credentials;
only the separate authorized operator context can request production operations.
Owner installation, release and maintenance are excluded from every Artisan registry and management image. There is no maintainer management build context. Customer hooks remain a development capability. External distribution scripts operate outside the customer registry. See [bootstrap](bootstrap.md).

## Disconnection, cancellation and results

Accepted commands print a fresh `Host operation: <id>` and write a private durable
receipt. If a command restarts/stops its invoking container, the host operation
continues and records its actual result. Recover the terminal/operator context and
run `php artisan runa:host status <id>` before deciding whether anything needs
resuming. A lost connection is never automatic permission to retry a mutation.
Disconnect closes stdin, so a confirmation cannot be manufactured after the
operator disappears. Use explicit noninteractive confirmation/settings options
for automation; no default approval is invented.

Ctrl-C requests process-group interruption. Existing deployment/restore markers
remain the authority for safe recovery; cancellation is not schema rollback.
Malformed transport input/output overflow also fails the operation and leaves an
inspectable receipt. A daemon/host crash may leave running receipts; inspect the
existing operation locks, processes and domain recovery evidence before retrying.
No receipt falsely attests completion after the executor itself is killed.
The existing background quality contract remains unchanged: after starting a
background gate, return control without polling; use its normal later status.

Offline contracts: `python3 tools/maintainer/testing/host-executor.py`. Native host acceptance:
`python3 tools/maintainer/testing/host-executor-host.py` uses isolated process fixtures,
actual PHP -> Unix socket -> Docker execution, self-restart/disconnection,
operator recovery and scoped container/network cleanup with no data volumes.
