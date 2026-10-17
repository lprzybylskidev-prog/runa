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
