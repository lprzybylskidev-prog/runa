# Design Assets

Locally served, user-selected IBM Plex Mono and Tabler SVG assets; no runtime CDN or package dependency.

## Sources

- IBM Plex Mono: https://github.com/IBM/plex/tree/71f558992315a12ca1f06a2215d57962a1992162/packages/plex-mono — complete WOFF2 Regular and SemiBold, OFL-1.1, license preserved in `resources/fonts/LICENSE.txt`.
- Tabler Icons: https://github.com/tabler/tabler-icons/tree/a4ce1404bc6d24d3c365afe7b258d6bf6f48d62d — four outline SVGs, MIT, license preserved in `resources/icons/LICENSE`.

Only selected assets are included. Both sources and their licenses were inspected. Updates are deliberate, with license/provenance and hashes refreshed; do not overwrite from a mutable branch.

## SHA256

| Asset                                        | SHA256                                                             |
| -------------------------------------------- | ------------------------------------------------------------------ |
| `resources/fonts/IBMPlexMono-Regular.woff2`  | `ba204497f16b6d334cee9d1e963a831b73e3a56e1d6300a8489d18df7214b350` |
| `resources/fonts/IBMPlexMono-SemiBold.woff2` | `6a825b4824c01cbb401e829e5a066a1818411bcb3538b5a5792c5ca9b82343c3` |
| `resources/fonts/LICENSE.txt`                | `37784b44044a4ffd9256702b7c0982c37e5c8887ba90c6dca0479aea93dc898d` |
| `resources/icons/LICENSE`                    | `b740a1d46122672da62833e97f7e7c8a13fa85cbc7445b584b297cc00dde93db` |

`AppIcon.vue` adapts the selected SVG geometry into typed decorative Vue icons with inherited color and a 1.6px stroke. Original source SVGs remain unchanged for provenance.

The font license has line endings normalized to LF; hashes describe the committed asset bytes.

Current Tabler subset: layers-intersect, sun, moon and device-desktop. Point 8 rechecked these local original SVGs and their preserved MIT attribution/hash records; no new upstream geometry or package is added. Icon now accepts explicit size/color while remaining decorative.

| Asset                                  | SHA256                                                             |
| -------------------------------------- | ------------------------------------------------------------------ |
| `resources/icons/device-desktop.svg`   | `2fdd2150904a844185250f26453a02f31ee9d77a1f936a1df9c2ecd77cfc788d` |
| `resources/icons/layers-intersect.svg` | `5bbc8a450c6b6d1bc236e9e8397670c1dc2c18cbe8de5cba7287bb3d8f517332` |
| `resources/icons/moon.svg`             | `7cabaae40eea65310da478d3cad30b23cf1d7e99adad56378f82824338079668` |
| `resources/icons/sun.svg`              | `6677490605af8987d55d6e4185b1f6c241eced65b63ccfece68cadb7ce90fbb5` |

## Runa logo

User-supplied original: `resources/images/runa-logo.png`, 1254 × 1254 RGBA PNG with transparent background. Source bytes are preserved. Visible alpha bounds (threshold 128): x=435..850, y=195..1097.

`RunaLogo.vue` uses the image as an alpha mask, removing transparent padding through CSS mask sizing/position rather than rewriting the raster. The rendered mark inherits `currentColor`; its mask also permits a CSS background gradient when deliberately styled. It remains a raster mask, not a traced SVG; avoid enlarging it beyond its useful source resolution.

SHA256: `5bd3b20c783ddf15917b5f1d576028e3dca0dd22d0447ea606cb36d39af837f5`.

Native email uses `resources/images/runa-mail-logo.png`: a 126×126 (3× density) browser snapshot of the actual light-theme MinimalLayout brand tile, displayed at 42×42 beside the text `runa.` and its accent dot. This preserves the supplied mark, violet color, border, gradient and 4px corners in email engines without CSS mask support. The original PNG remains unchanged. Regenerate in the development container with `./bin/pnpm exec node tools/assets/render-mail-logo.mjs` after building the root entry; the script captures the existing Vue component from nginx-test, without tracing or redrawing it. SHA256: `5426a2abe4a7a375cb5fec3298d41f4e5d11c0c56c15f34b792e167b8ea98989`. CID/public HTTPS delivery and visible text preserve blocked-image readability.

The user supplied this as Runa's project logo; no third-party source or license was supplied. Do not replace it with a generated approximation.

## Browser favicon

`public/favicon.ico` contains lossless 16×16, 32×32 and 48×48 PNG frames captured from the actual light-theme MinimalLayout brand tile. It preserves the original RunaLogo mask, violet mark, pale gradient, border and corners; no traced/replacement logo or dependency is introduced. The same light tile stays readable on light and dark browser chrome.

Regenerate after building the root entry with `./bin/pnpm exec node tools/assets/render-favicon.mjs` against nginx-test. The script snapshots the existing tile and packages the frames in one ICO; the original logo bytes remain unchanged. SHA256: `d0535275ccad19fa824afd11fd084eefee4a2be18299edac00bc16c86aa0e41c`.

Root/review documents and native/static emergency error documents reference `/favicon.ico`. Production PHP and Nginx images both explicitly copy the committed asset; Nginx serves it while PHP is unavailable. This is a browser icon only, without a web-app manifest or installation icons. The owner visually accepted the favicon on 2026-10-18 in RM-2026-004 point 6 and requested its use in both MinimalLayout and ApplicationLayout. Their shared HTML entries already declare the same icon.

## Standalone HTTP error assets

The build-time MinimalLayout render emits its actual component CSS and unchanged logo/font assets into ignored `public/runa-error/`; provenance remains that recorded above. It uses the same RunaLogo mask and AppBrandIllustration rather than a recreated error-specific illustration or logo. The font license is included. Generated private MinimalLayout HTML shells are ignored under `resources/views/errors/generated/`; production creates fresh output, then the plain PHP finalizer emits static 503 documents with actual branding/version values. No runtime Vue/Node/JavaScript or public Vite manifest is required.
