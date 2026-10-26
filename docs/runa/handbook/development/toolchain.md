# Supplied development toolchain

Use the pinned dependencies in src/composer.lock and src/pnpm-lock.yaml and the development Docker image. Laravel/PHP, Vue/TypeScript/Tailwind, Playwright/Vitest, Composer/pnpm, Boost and the scanners are supplied by the foundation. Run PHP/Composer/Node and application tests in project containers, with host Git/Python/Docker only for orchestration.

Use frozen installs and existing wrappers; do not update dependencies implicitly. Application additions require explicit approval, compatibility/activity/license/security/tree review and a deliberate roadmap. Platform toolchain updates are owner-managed. See [environment](environment.md), [testing](../testing/strategy.md), [security](../security/baseline.md) and [commands](../tooling/commands.md).

Operational Python stays stdlib-only on the host; reusable package direction,
supported interpreter contexts and generated-state rules are in
[Python tooling](python-tooling.md). Private owner test dependencies are excluded
from customer delivery and production images.
