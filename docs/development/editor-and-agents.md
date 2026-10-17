# Editor, debugger, and coding agents

## VS Code

Open the repository with **Dev Containers: Reopen in Container**. The devcontainer installs the extensions declared in `.devcontainer/devcontainer.json`, including PHP Debug, Microsoft Python (`ms-python.python`) and Pylance (`ms-python.vscode-pylance`), and enables format-on-save. Vue/TypeScript/JavaScript/JSON use the locked project Prettier; PHP uses Intelephense for interactive formatting. `./bin/format` remains the canonical Pint/Prettier repository formatter. Run it before committing.

Install the OpenAI/Codex extension manually inside the devcontainer. It is deliberately absent from automatic extension installation. Sign in interactively; do not put authentication in repository configuration or images.

Database Client is installed automatically. Create a PostgreSQL connection manually inside the container:

- Name: `Runa development`
- Host: `postgres`
- Port: `5432`
- User/database: `runa`
- Password: copy `DB_PASSWORD` from the local ignored `.env` into the extension's private credential field.
- SSL: disabled for this isolated development network.

Do not export a connection containing a password to workspace files. Database ports remain internal. Use the separate `runa_test` role/database only for isolated testing.

## Xdebug

Xdebug 3.5.3 exists only in the `development` Docker target. It starts with `xdebug.mode=off` and is absent from the production runtime. Run from the host or devcontainer:

```sh
./bin/xdebug status
./bin/xdebug on
./bin/xdebug off
```

The wrapper updates development PHP configuration and gracefully reloads the app's PHP-FPM master. CLI PHP reads the new configuration on its next invocation. Recreation resets the mode to off. Queue/scheduler containers keep their default off configuration.

In the attached devcontainer, select **Listen for Xdebug (devcontainer)** and press F5. Set a breakpoint, then visit `http://localhost:8080/api/health?XDEBUG_TRIGGER=1` or trigger the desired request with an Xdebug browser helper. For CLI debugging inside the app container:

```sh
XDEBUG_TRIGGER=1 php artisan about
```

PHP connects to the VS Code debug adapter at `127.0.0.1:9003` inside the app container. Port 9003 is not published to the host. Source mapping is `/workspace` to the opened workspace. See the [Xdebug step debugging documentation](https://xdebug.org/docs/step_debug).

## Codex CLI and Boost

The development image includes pinned Codex CLI 0.163.0. Check `codex --version` and authenticate with `codex login` interactively inside the container. Authentication is local to that container unless the developer deliberately configures private persistent credential storage.

Laravel Boost 2.10.3 is a Composer development dependency. `.codex/config.toml` registers the stdio MCP server with `./bin/boost-mcp`. The idempotent `./bin/dev setup` also registers the same launcher with `codex mcp add laravel-boost -- /workspace/bin/boost-mcp` in the container user configuration, making it available before project trust is established. The launcher uses PHP directly inside the devcontainer and `docker compose exec -T app` on the host. Start Codex from the repository root. Trust this project when prompted so its project MCP configuration can load. `codex mcp list` confirms registration; it does not prove agent access.

The setup uses the official `php artisan boost:install --mcp --no-interaction` feature flag. Runa guidance and skills were hashed before setup and verified identical afterward. MCP-only setup generated no skills or agent instructions; the unrelated auto-detected Copilot MCP configuration was removed. `boost.json` records Codex only, guidelines disabled, and no generated skills. Do not run unrestricted `boost:install` or `boost:update`; follow `docs/ai/laravel-boost.md` for any future setup or update.

After rebuilding/reopening, start a new Codex session and have the agent invoke Boost `application-info` and `search-docs`. Verify Laravel 13.37.0 and installed package versions. A subprocess MCP smoke test is only transport evidence.

Real-session acceptance passed on 2026-10-17 inside the devcontainer: the actual Codex agent successfully invoked both tools. Application inspection returned PHP 8.4, Laravel 13.37.0, PostgreSQL and Boost 2.10.3; documentation search returned health-route guidance for `laravel/framework@13.x`. The user also confirmed format-on-save and the VS Code breakpoint/resume HTTP debugging flow inside the devcontainer on the same date. Xdebug was returned to `mode=off` after acceptance. Point 8 acceptance is complete.

Official setup references: [Codex CLI](https://developers.openai.com/codex/cli), [Codex MCP](https://developers.openai.com/codex/mcp), and [Laravel Boost](https://laravel.com/docs/13.x/boost).
