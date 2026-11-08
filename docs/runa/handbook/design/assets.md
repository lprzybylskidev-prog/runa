# Design Assets

Locally served, user-selected IBM Plex Mono and Tabler SVG assets; no runtime CDN or package dependency.

## Sources

- IBM Plex Mono: https://github.com/IBM/plex/tree/71f558992315a12ca1f06a2215d57962a1992162/packages/plex-mono — complete WOFF2 Regular and SemiBold, OFL-1.1, license preserved in `src/runa/resources/fonts/LICENSE.txt`.
- Tabler Icons: https://github.com/tabler/tabler-icons/tree/a4ce1404bc6d24d3c365afe7b258d6bf6f48d62d — selected outline SVGs, MIT, license preserved in `src/runa/resources/icons/LICENSE`.

Only selected assets are included. Both sources and their licenses were inspected. Updates are deliberate, with license/provenance and hashes refreshed; do not overwrite from a mutable branch.

## SHA256

| Asset                                                 | SHA256                                                             |
| ----------------------------------------------------- | ------------------------------------------------------------------ |
| `src/runa/resources/fonts/IBMPlexMono-Regular.woff2`  | `ba204497f16b6d334cee9d1e963a831b73e3a56e1d6300a8489d18df7214b350` |
| `src/runa/resources/fonts/IBMPlexMono-SemiBold.woff2` | `6a825b4824c01cbb401e829e5a066a1818411bcb3538b5a5792c5ca9b82343c3` |
| `src/runa/resources/fonts/LICENSE.txt`                | `37784b44044a4ffd9256702b7c0982c37e5c8887ba90c6dca0479aea93dc898d` |
| `src/runa/resources/icons/LICENSE`                    | `b740a1d46122672da62833e97f7e7c8a13fa85cbc7445b584b297cc00dde93db` |

`AppIcon.vue` adapts the selected SVG geometry into typed decorative Vue icons with inherited color and a 1.6px stroke. Original source SVGs remain unchanged for provenance.

The font license has line endings normalized to LF; hashes describe the committed asset bytes.

Original Tabler subset: layers-intersect, sun, moon and device-desktop; the earlier nine-icon subset added table export icons. Their original provenance remains below; the current action/feedback extension is recorded in its own section. Icon accepts explicit size/color while remaining decorative.

| Asset                                           | SHA256                                                             |
| ----------------------------------------------- | ------------------------------------------------------------------ |
| `src/runa/resources/icons/device-desktop.svg`   | `2fdd2150904a844185250f26453a02f31ee9d77a1f936a1df9c2ecd77cfc788d` |
| `src/runa/resources/icons/layers-intersect.svg` | `5bbc8a450c6b6d1bc236e9e8397670c1dc2c18cbe8de5cba7287bb3d8f517332` |
| `src/runa/resources/icons/moon.svg`             | `7cabaae40eea65310da478d3cad30b23cf1d7e99adad56378f82824338079668` |
| `src/runa/resources/icons/sun.svg`              | `6677490605af8987d55d6e4185b1f6c241eced65b63ccfece68cadb7ce90fbb5` |

## Runa logo

User-supplied original: `src/runa/resources/images/runa-logo.png`, 1254 × 1254 RGBA PNG with transparent background. Source bytes are preserved. Visible alpha bounds (threshold 128): x=435..850, y=195..1097.

`RunaLogo.vue` uses the image as an alpha mask, removing transparent padding through CSS mask sizing/position rather than rewriting the raster. The rendered mark inherits `currentColor`; its mask also permits a CSS background gradient when deliberately styled. It remains a raster mask, not a traced SVG; avoid enlarging it beyond its useful source resolution.

SHA256: `5bd3b20c783ddf15917b5f1d576028e3dca0dd22d0447ea606cb36d39af837f5`.

Native email uses `src/runa/resources/images/runa-mail-logo.png`: a 126×126 (3× density) browser snapshot of the actual light-theme MinimalLayout brand tile, displayed at 42×42 beside the text `runa.` and its accent dot. This preserves the supplied mark, violet color, border, gradient and 4px corners in email engines without CSS mask support. The original PNG remains unchanged. Its Runa branding renderer is private owner maintenance tooling; customers supply application branding through the public application manifest and presentation contracts. SHA256: `5426a2abe4a7a375cb5fec3298d41f4e5d11c0c56c15f34b792e167b8ea98989`. CID/public HTTPS delivery and visible text preserve blocked-image readability.

The user supplied this as Runa's project logo; no third-party source or license was supplied. Do not replace it with a generated approximation.

## Browser favicon

`src/public/favicon.ico` contains lossless 16×16, 32×32 and 48×48 PNG frames captured from the actual light-theme MinimalLayout brand tile. It preserves the original RunaLogo mask, violet mark, pale gradient, border and corners; no traced/replacement logo or dependency is introduced. The same light tile stays readable on light and dark browser chrome.

Runa owner tooling snapshots the existing tile and packages the frames in one ICO; the original logo bytes remain unchanged. SHA256: `d0535275ccad19fa824afd11fd084eefee4a2be18299edac00bc16c86aa0e41c`.

## Standalone HTTP error assets

The build-time MinimalLayout render emits its actual component CSS and unchanged logo/font assets into ignored `src/public/runa-error/`; provenance remains that recorded above. It uses the same RunaLogo mask and AppBrandIllustration rather than a recreated error-specific illustration or logo. The font license is included. Generated private MinimalLayout HTML shells are ignored under `.runtime/frontend/errors/templates/`; production creates fresh output, then the plain PHP finalizer emits static 503 documents with actual branding/version values. No runtime Vue/Node/JavaScript or public Vite manifest is required.

## Table export icons

Point 19 adds file-type-csv, file-type-xls, file-type-pdf, printer and adjustments-horizontal from the same pinned Tabler revision above, under the existing MIT license. Original geometry is retained; AppIcon adapts the shared stroke and sizing.

| Asset                                      | SHA256                                                             |
| ------------------------------------------ | ------------------------------------------------------------------ |
| resources/icons/file-type-csv.svg          | `66f74228aceb4473f3708f2e6c1ab26d771056834f3de6237e6060ac250e05b8` |
| resources/icons/file-type-xls.svg          | `45e583beb8710d55d07a8e0de847b46664aa607d898738dd122c1441505a3c54` |
| resources/icons/file-type-pdf.svg          | `36ef668b02762697eb2d3e360ec13580651908ded26c08facfe3b99fc96ccebf` |
| resources/icons/printer.svg                | `6f530e94836068b346f241313a6040c1d55ff1b677d326dc6c1b3c5b42c5c1eb` |
| resources/icons/adjustments-horizontal.svg | `51c876babc598cf69ad1146f0fb621919a975758df0cc7abf45052006358a353` |

## Module catalogue icons

The clipboard-list, key and shield-lock paths in AppIcon are small, locally authored
outline drawings for welcome module identity. They reuse the existing 24px viewbox,
inherited color and 1.6px stroke; no upstream asset or dependency was introduced.

## Semantic action and feedback extension

The action/feedback subset uses the same reviewed pinned Tabler revision above.
Original SVG bytes are retained unchanged; AppIcon adapts their geometry to the
shared 24px viewBox, inherited color and 1.6px stroke. No package dependency or
remote runtime asset is introduced. The existing MIT license applies.

| Asset                                          | SHA256                                                             |
| ---------------------------------------------- | ------------------------------------------------------------------ |
| `src/runa/resources/icons/plus.svg`            | `9cc497d4cc1eb38cf9daae18f41ffdc1a5e2a8431d70247b0854978f303f28d6` |
| `src/runa/resources/icons/pencil.svg`          | `422796681dab2177b958836952b0a581025013a78d8a8c9df9c1af43f62d0e97` |
| `src/runa/resources/icons/device-floppy.svg`   | `332fb64778e96e0de3ef6a4262baa8bf2c1983d478bb14d284c4a2450f9e0130` |
| `src/runa/resources/icons/check.svg`           | `e206067a7d570997b5b3a7d30ab287c0b15a492787649a8b96dff8fad2862fbe` |
| `src/runa/resources/icons/x.svg`               | `9350785360414c39450c3505f17d1745e55a7534edace8548e30cf49e3727d43` |
| `src/runa/resources/icons/trash.svg`           | `4b90fa07a6d980736951d9e83b91432972b2bcecf81be4578608a0983f2ebc9d` |
| `src/runa/resources/icons/eye.svg`             | `45bdbd35bb18e9997a86dad8db6eb90931d1d53884484baf2c65e34d6f6e4f93` |
| `src/runa/resources/icons/copy.svg`            | `3a455a299db4ec939fd0473d15c81c1d3f30f65dec81a7de816317c989b06073` |
| `src/runa/resources/icons/arrow-back-up.svg`   | `42a20a833068ac155cc2e3a4176a9e80a918cc62c8bf48d3f826a2c47bde739d` |
| `src/runa/resources/icons/rotate-2.svg`        | `0af7fb9366cfdf5f226b51b7759323c8919ca7c5da244fe1cdf4c3a2a0c86c05` |
| `src/runa/resources/icons/player-play.svg`     | `e2bff69284f1dc77224c47c03798017206a29d4ef665549db724b0a4534d8e5c` |
| `src/runa/resources/icons/send.svg`            | `b4e589d1ed7ad6f20045017728cf8d4504db5870b38214deefa03ed01fea1f57` |
| `src/runa/resources/icons/login.svg`           | `9cc362452dccfe7e803416808159ed4e3c27c8d0e9df31ca92c989b1675f2310` |
| `src/runa/resources/icons/user-plus.svg`       | `a4f4b02f49405ab616511ccf30def5f511e211ddeaba9cb023e510ac914503a8` |
| `src/runa/resources/icons/lock.svg`            | `407856048124fdd63239f75a3640ca8f20a9acbc1f49cac9de0bcdb1cb42ec5f` |
| `src/runa/resources/icons/shield-check.svg`    | `7d12a28bb5b8ba69445a94118aa96b57a513f8d45e23974665f25c597eaccc30` |
| `src/runa/resources/icons/shield-x.svg`        | `8177514d4e6ad942294b189edcccec6f4fc872689975398fe3b1d5ac0a1dc1f0` |
| `src/runa/resources/icons/mail.svg`            | `94ca7eed1a9b89c7d3345e7c62a622060a502645b4ae10b7ed160294583d3c33` |
| `src/runa/resources/icons/download.svg`        | `c13f6f9cf87deee99e331622169efd59cd764b376994540347e50d6bb5e2e0ef` |
| `src/runa/resources/icons/info-circle.svg`     | `48b7dddf34a6b87e93843a42b14781ce01794cf69274c458249ed8bfc01b6e4d` |
| `src/runa/resources/icons/circle-check.svg`    | `c4cb2e026be5f4c217c3ff9b0ccefb9a26b7a135bc82eef28a8bead0cc02a958` |
| `src/runa/resources/icons/alert-triangle.svg`  | `25b1f0c39f34647017df84a0cdc9e691c697737094a5956c360ab448a7fd5bef` |
| `src/runa/resources/icons/alert-circle.svg`    | `7aff9be6b14daf212f982513de43ad7784752c021995719d00006cad8a75bef0` |
| `src/runa/resources/icons/inbox.svg`           | `48e21bddefb5550702a404b1672615283f4d489fd4b3a0d9088d8320991ac452` |
| `src/runa/resources/icons/flask.svg`           | `28459e1fcdf7e9d8b0664aa3fe350995c4df3dda6ee9823351df3ea0f5714d17` |
| `src/runa/resources/icons/history.svg`         | `5f8a5a975a8136a4755380c18db1fe533acf7dc4e34250ceae0272c5d2cb7312` |
| `src/runa/resources/icons/power.svg`           | `2a872241a3059da7e3157fc5fbd8b8dfd40a10825934a23dd5e7f43cb4ae1df9` |
| `src/runa/resources/icons/crop.svg`            | `fc184b9acc82e67ab38db926f01a921400ce17fefc5e2a75c887a8959e9f248e` |
| `src/runa/resources/icons/arrows-exchange.svg` | `67be2e2afc04e53c70a4b81f58b18c232edd5b6124eff81a285fcd513f857016` |
| `src/runa/resources/icons/arrow-up.svg`        | `c0a58436629c59996826b7980b0cbdfff51bfa289eefb2cceee494a964c5105e` |
| `src/runa/resources/icons/arrow-down.svg`      | `34efc2d5737ffa3f29f8056e6d861ea8218d8f40858539a959a9482811f6bd05` |
| `src/runa/resources/icons/chevron-left.svg`    | `889174801ca4213379fe4e6a574546793a72ab22be35bc2f177e3d8d24576dbd` |
| `src/runa/resources/icons/chevron-right.svg`   | `83733bb6e03e1d20fd2674d06a493642c8ea6eef76c44ebb2239b9ef17dedff7` |
| `src/runa/resources/icons/home.svg`            | `069600f461f3e967685765723a851af7b35f24b715e03d41a2928152cf75644c` |
