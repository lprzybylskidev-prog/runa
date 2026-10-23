# Fortify dependency evaluation

Reviewed 2026-10-23 for RM-2026-011 point 1. This evaluation installs nothing and
changes no Composer manifests/locks. The approved specification mandates Fortify;
point 5 owns its actual installation and exact resolved dependency review.

## Compatibility, maintenance and overlap

Laravel maintains Fortify; Packagist reports 1.41.0 released 2026-09-28. Its MIT
license and PHP ^8.2 / Illuminate ^11|^12|^13 requirements fit PHP ^8.4 and Laravel
13.37.0 here. Fortify supplies authentication pipelines, response customization,
password reset and TOTP primitives without a UI. It does not replace module
availability, lifecycle/session restrictions, our policy/step-up engine or Audit.
The project has no existing authentication package. Use framework password/session
primitives rather than inventing cryptography or adding Sanctum/Permission packages.

Sources: [release](https://github.com/laravel/fortify/releases/tag/v1.41.0),
[pinned package metadata](https://raw.githubusercontent.com/laravel/fortify/v1.41.0/composer.json),
[Laravel 13 Fortify guidance](https://laravel.com/docs/13.x/fortify).

## Candidate and dependency footprint

Latest Fortify requires laravel/passkeys even with WebAuthn disabled. That expands
an excluded feature's dependency tree. The compatible 1.36.2 release predates this
requirement and supports Illuminate 13. The initial installation candidate is
**exact 1.36.2**, subject to a fresh point-5 advisory/source/solver review; do not use
an unbounded constraint that silently adds passkeys. This is a version selection,
not approval for another direct package or for enabling WebAuthn. If a later
required security fix makes this candidate unsuitable, discuss the dependency
tradeoff before adding a different stack.

Candidate production tree, excluding already installed Illuminate/framework and
PHP extensions: Fortify -> bacon/bacon-qr-code ^3 -> dasprid/enum ^1;
Fortify -> pragmarx/google2fa ^9 -> paragonie/constant_time_encoding ^1|^2|^3.
Current compatible candidates below are all absent from the current production
lock. Source archive sizes include documentation/tests and are not installed-byte
or memory benchmarks. Only archives/metadata were read; no package scripts ran.

| Candidate                              | License      | Archive entries | Unpacked source bytes |
| -------------------------------------- | ------------ | --------------- | --------------------- |
| laravel/fortify 1.36.2                 | MIT          | 143             | 151262                |
| bacon/bacon-qr-code 3.1.1              | BSD-2-Clause | 78              | 234814                |
| pragmarx/google2fa 9.1.0               | MIT          | 26              | 49775                 |
| dasprid/enum 1.0.7                     | BSD-2-Clause | 16              | 28820                 |
| paragonie/constant_time_encoding 3.1.3 | MIT          | 16              | 72651                 |

Combined archive footprint is 537322 bytes. Dependencies cover QR generation,
TOTP and encoding rather than an additional application framework/service.
The actual solver may choose newer compatible transitives; review/lock them in
point 5 without an incidental update of unrelated packages. Native ext-iconv and
ext-json requirements must be verified against the application/production images.
Sources: [candidate composer metadata](https://raw.githubusercontent.com/laravel/fortify/v1.36.2/composer.json),
[Packagist package metadata](https://repo.packagist.org/p2/laravel/fortify.json),
[BaconQrCode](https://github.com/Bacon/BaconQrCode),
[Google2FA](https://github.com/antonioribeiro/google2fa),
[enum](https://github.com/DASPRiD/Enum),
[constant time encoding](https://github.com/paragonie/constant_time_encoding).

## Security and integration constraints

The current Packagist advisory response for Fortify lists CVE-2022-25838 affecting
versions below 1.11.1. It does not flag candidate 1.36.2. This is an evaluation of
published package history, not a passing Composer audit or an assertion that the
future lock is safe. Recheck all resolved transitives and run selected security
gates during final verification. [Published advisory](https://github.com/advisories/GHSA-6w4v-qr4m-97gg).

Use Fortify::ignoreRoutes during provider registration, before package route boot;
register only module-owned guarded named API descriptors. Adapt primitives through
Auth-owned Actions/response contracts. Deliberately implement same-origin cookie
session/CSRF transport, regenerate authentication session IDs and keep secrets out
of general ModuleApis payloads. Do not publish default users migrations, starter
UI, duplicate public auth routes, passkey endpoints or unrelated providers.
Exact package source and route suppression behavior must be reviewed again at
installation against the selected release.
