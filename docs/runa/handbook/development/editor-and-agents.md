# Editor, debugger, and coding agents

## VS Code

Open the repository with **Dev Containers: Reopen in Container**. The devcontainer installs the extensions declared in `.devcontainer/devcontainer.json`, including PHP Debug, Microsoft Python (`ms-python.python`) and Pylance (`ms-python.vscode-pylance`), and enables format-on-save. Vue/TypeScript/JavaScript/JSON use the locked project Prettier; PHP uses Intelephense for interactive formatting. `./bin/artisan runa:format` remains the canonical Pint/Prettier repository formatter. Run it before committing.

Install the OpenAI/Codex extension manually inside the devcontainer. It is deliberately absent from automatic extension installation. Sign in interactively; do not put authentication in repository configuration or images.

Database Client is installed automatically. Create a PostgreSQL connection manually inside the container:

- Name: `Runa development`
- Host: `postgres`
- Port: `5432`
- User/database: `runa`
- Password: copy `DB_PASSWORD` from the local ignored `.env` into the extension's private credential field.
- SSL: disabled for this isolated development network.

Do not export a connection containing a password to workspace files. Database ports remain internal. Use the separate `runa_test` role/database only for isolated testing.

## Reopen recovery and manual verification

Run Docker lifecycle commands in a host terminal. If reopening fails because `setup` exits with a missing tool that exists in the current development image, run `./bin/artisan runa:dev:up`, then `./bin/artisan runa:doctor:development`. Compose recreates outdated containers; restarting alone retains their old images. VS Code may reuse existing containers with `--no-recreate`. Preserve named volumes and local credentials during recovery. If the current image itself is outdated, use `./bin/artisan runa:dev:build` before `./bin/artisan runa:dev:up`.

After selective app recreation outside VS Code, run `python3 -m tools.application.development setup --if-needed` in the recreated app container before the host doctor. The dependency/build marker lives in the workspace while container-user MCP registration is ephemeral; the fast setup path restores registration without reinstalling current dependencies. Reopening in VS Code runs this same setup through postCreateCommand.

After the host doctor passes:

1. Select **Dev Containers: Reopen in Container** from the VS Code Command Palette. Confirm the workspace is `/workspace` and the terminal reports user `runa` with `whoami`.
2. In Extensions, install the OpenAI/Codex extension in **Dev Container: Runa** if needed, open its panel and sign in interactively. Start a session from `/workspace` and ask it to call Boost `application-info` and `search-docs`; check the installed versions. A successful CLI check alone does not verify the extension.
3. Open Database Client, add the PostgreSQL connection described above, connect and run `SELECT current_database(), current_user;`. Both values should be `runa`. Keep the password in the extension's private credential field.
4. Run `./bin/artisan runa:doctor:development` in the container terminal. Report editor-specific failures separately from the host service checks.

## Python editing

Devcontainer customization selects `/workspace/.runtime/python/venv/bin/python`
and the pinned Ruff binary from that environment. Pylance uses strict typing;
Python format-on-save and explicit Ruff import/fix actions share the container
configuration. Reopening applies these editor settings after development setup
prepares the frozen toolchain. Host Python does not need a venv or formatter.

Use the [Python commands and application configuration contract](python-tooling.md)
for formatting, lint and typing. PHP keeps the existing formatter/Pint workflow;
Vue/TypeScript/JavaScript use Prettier, ESLint and their native type/test tools.
The editor settings select tools; CLI checks and rendered/test acceptance still
provide verification. No GUI/editor acceptance is inferred from configuration.

## Xdebug

Xdebug 3.5.3 exists only in the `development` Docker target. It starts with `xdebug.mode=off` and is absent from the production runtime. Run from the host or devcontainer:

```sh
./bin/artisan runa:xdebug:status
./bin/artisan runa:xdebug:enable
./bin/artisan runa:xdebug:disable
```

The wrapper updates development PHP configuration and gracefully reloads the app's PHP-FPM master. CLI PHP reads the new configuration on its next invocation. Recreation resets the mode to off. Queue/scheduler containers keep their default off configuration.

In the attached devcontainer, select **Listen for Xdebug (devcontainer)** and press F5. Set a breakpoint, then visit `http://localhost:8080/api/health?XDEBUG_TRIGGER=1` or trigger the desired request with an Xdebug browser helper. For CLI debugging inside the app container:

```sh
XDEBUG_TRIGGER=1 php artisan about
```

PHP connects to the VS Code debug adapter at `127.0.0.1:9003` inside the app container. Port 9003 is not published to the host. Source mapping is `/workspace` to the opened workspace. See the [Xdebug step debugging documentation](https://xdebug.org/docs/step_debug).

## Codex CLI and Boost

The development image includes pinned Codex CLI 0.163.0. Check `codex --version` and authenticate with `codex login` interactively inside the container. Authentication is local to that container unless the developer deliberately configures private persistent credential storage.

Laravel Boost 2.10.3 is a Composer development dependency. `.codex/config.toml` registers the stdio MCP server with `./bin/boost-mcp`. The idempotent `./bin/artisan runa:dev:setup` also registers the same launcher with `codex mcp add laravel-boost -- /workspace/bin/boost-mcp` in the container user configuration, making it available before project trust is established. The launcher uses PHP directly inside the devcontainer and `docker compose exec -T app` on the host. Start Codex from the repository root. Trust this project when prompted so its project MCP configuration can load. `codex mcp list` confirms registration; it does not prove agent access.

The setup uses the official `php artisan boost:install --mcp --no-interaction` feature flag. Runa guidance and skills were hashed before setup and verified identical afterward. MCP-only setup generated no skills or agent instructions; the unrelated auto-detected Copilot MCP configuration was removed. `boost.json` records Codex only, guidelines disabled, and no generated skills. Do not run unrestricted `boost:install` or `boost:update`; follow `docs/runa/handbook/ai/laravel-boost.md` for any future setup or update.

After rebuilding/reopening, start a new Codex session and have the agent invoke Boost `application-info` and `search-docs`. Verify Laravel 13.37.0 and installed package versions. A subprocess MCP smoke test is only transport evidence.

Real-session acceptance passed on 2026-10-17 inside the devcontainer: the actual Codex agent successfully invoked both tools. Application inspection returned PHP 8.4, Laravel 13.37.0, PostgreSQL and Boost 2.10.3; documentation search returned health-route guidance for `laravel/framework@13.x`. The user also confirmed format-on-save and the VS Code breakpoint/resume HTTP debugging flow inside the devcontainer on the same date. Xdebug was returned to `mode=off` after acceptance. Point 8 acceptance is complete.

Official setup references: [Codex CLI](https://developers.openai.com/codex/cli), [Codex MCP](https://developers.openai.com/codex/mcp), and [Laravel Boost](https://laravel.com/docs/13.x/boost).

## Moving work between container and host

Development is supported on native Linux with Docker Engine and the Compose plugin; no distribution-specific host paths are required. Keep the repository bind-mounted as `.:/workspace`. `/workspace` is the container path, not evidence of the host checkout location. Host prerequisites and startup belong to [environment.md](environment.md).

For a small host-only task, the agent supplies roughly 2–3 ordered commands, the verified repository location (or asks the user to locate it), their effects and expected output. The user runs them in a host terminal and returns output or reliable confirmation. Review the result before dependent work; do not assume completion from elapsed time.

For substantial Docker work:

For sibling-only work, prefer `./bin/artisan runa:dev:restart <service>` when only a process restart is needed. Restart retains the existing image/configuration. Use `./bin/artisan runa:dev:build <service>` for a selected local image build, then `./bin/artisan runa:dev:recreate <service>` for image/configuration changes after dependency review; recreation uses `--no-deps` in the same Compose context and does not certify readiness. Check application/knowledge health as appropriate; record app container ID/start time before and after to verify it stayed running when no app change was necessary. `./bin/artisan runa:dev:build` without services builds all configured development images; `./bin/artisan runa:dev:down` affects the whole project and preserves volumes. Restart without a service affects the whole stack. Recreating the attached app needs a planned session transition.

For generated/private inputs, template naming and credential mismatch recovery, use [environment file responsibilities](environment-files.md). Never repair an existing role by regenerating settings or deleting its volume.
