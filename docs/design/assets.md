# Design Assets

Locally served, user-selected IBM Plex Mono and Tabler SVG assets; no runtime CDN or package dependency.

## Sources

- IBM Plex Mono: https://github.com/IBM/plex/tree/71f558992315a12ca1f06a2215d57962a1992162/packages/plex-mono — complete WOFF2 Regular and SemiBold, OFL-1.1, license preserved in `resources/fonts/LICENSE.txt`.
- Tabler Icons: https://github.com/tabler/tabler-icons/tree/a4ce1404bc6d24d3c365afe7b258d6bf6f48d62d — six outline SVGs, MIT, license preserved in `resources/icons/LICENSE`.

Only selected assets are included. Both sources and their licenses were inspected. Updates are deliberate, with license/provenance and hashes refreshed; do not overwrite from a mutable branch.

## SHA256

| Asset                                        | SHA256                                                             |
| -------------------------------------------- | ------------------------------------------------------------------ |
| `resources/fonts/IBMPlexMono-Regular.woff2`  | `ba204497f16b6d334cee9d1e963a831b73e3a56e1d6300a8489d18df7214b350` |
| `resources/fonts/IBMPlexMono-SemiBold.woff2` | `6a825b4824c01cbb401e829e5a066a1818411bcb3538b5a5792c5ca9b82343c3` |
| `resources/fonts/LICENSE.txt`                | `37784b44044a4ffd9256702b7c0982c37e5c8887ba90c6dca0479aea93dc898d` |
| `resources/icons/refresh.svg`                | `9dd8ab8cac268489501cbb0a106d93cc3f367add493d20c47ec1e1d9804c760a` |
| `resources/icons/circle-check.svg`           | `c4cb2e026be5f4c217c3ff9b0ccefb9a26b7a135bc82eef28a8bead0cc02a958` |
| `resources/icons/alert-circle.svg`           | `7aff9be6b14daf212f982513de43ad7784752c021995719d00006cad8a75bef0` |
| `resources/icons/loader-2.svg`               | `82639ca5a04efc951caef3d274c9e302f1c09dcdaa9a1b8e094c16890bfd1d41` |
| `resources/icons/layers-intersect.svg`       | `5bbc8a450c6b6d1bc236e9e8397670c1dc2c18cbe8de5cba7287bb3d8f517332` |
| `resources/icons/sparkles.svg`               | `fb229226d919ddddc8c09d3269b748f437b5f0dd46072209cb8f3046e84b38fe` |
| `resources/icons/LICENSE`                    | `b740a1d46122672da62833e97f7e7c8a13fa85cbc7445b584b297cc00dde93db` |

`AppIcon.vue` adapts the selected SVG geometry into typed decorative Vue icons with inherited color and a 1.6px stroke. Original source SVGs remain unchanged for provenance.

The font license has line endings normalized to LF; hashes describe the committed asset bytes.
