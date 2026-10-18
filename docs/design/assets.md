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

Current Tabler subset: layers-intersect, sun, moon and device-desktop.

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

Native email uses the same original PNG as an inline CID part (or a configured public HTTPS logo), with explicit dimensions and a separate visible application name for blocked-image readability. No derivative bitmap was added.

The user supplied this as Runa's project logo; no third-party source or license was supplied. Do not replace it with a generated approximation.
