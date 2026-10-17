# Laravel Boost Policy

## Why Boost exists in Runa

Laravel Boost is installed as a development dependency for:
- MCP/project inspection,
- version-aware Laravel documentation search,
- Laravel-aware coding assistance.

Boost is not the source of truth for Runa architecture or agent rules.

## Runa skill precedence

Runa maintains its own adapted skills under `.agents/skills/`.

If Boost generates skills/instruction files during installation or setup:
- do not keep them merely because Boost created them,
- do not merge them blindly,
- delete the Boost-generated skill copies after identifying them,
- preserve Runa's own skills,
- verify Boost MCP remains functional.

The accepted Runa Laravel skill already incorporates selected useful Laravel/Boost guidance and project overrides.

## Installation safety

Before running any Boost command that may generate AI files:
1. snapshot/hash the existing `.agents/skills` tree and relevant agent instructions,
2. run the official setup using current documentation,
3. inspect the exact generated diff,
4. remove all Boost-generated skill copies, and remove generated instruction material that conflicts with or duplicates Runa,
5. restore any overwritten Runa files,
6. verify no unexpected file remains.

Do not run future Boost updates with a mode that silently overwrites local Runa policy.

## Acceptance

A successful installation is not proven by package presence alone.

Test:
- Boost is installed,
- MCP is registered for Codex using the current official mechanism,
- the actual Codex agent session can discover and invoke it (CLI configuration or a subprocess test alone is insufficient),
- execute a real version-aware Boost operation such as Search Docs/project inspection,
- verify returned guidance matches the installed Laravel/tool versions.

## Current-doc rule

When Laravel API/version behavior is uncertain, use Boost version-aware documentation before relying on remembered framework behavior.

Project-specific Runa rules still override generic Boost recommendations.

During the first environment bootstrap, Boost cannot be used before Laravel/Boost exists. For that setup only, verify installation/version requirements with current official documentation, then prove agent-visible MCP access as soon as configured. Later framework work uses the installed Boost tools. Do not claim unavailable tools were called.
