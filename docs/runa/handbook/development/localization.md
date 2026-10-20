# Application localization

User UI defaults to Polish, supports English and light/dark themes. Administration UI is English-only. Technical source/API/CLI content stays English.

Application translations belong in src/app/lang, with namespaced application messages where appropriate. Override only documented namespaced platform translations through application paths; do not edit the protected Runa/vendor translation snapshot. Use the shared frontend preference provider and translation contracts described in [frontend architecture](../architecture/frontend.md), preserving language/theme persistence and accessible control names.

Runa supplies its reviewed validation catalogs and public PL/EN UI. Application-specific additions and backend validation messages should use Laravel translations with immediate frontend handling of known UX errors; backend validation remains authoritative. Test both languages at representative viewport sizes and verify longer content/reflow. Platform catalog refresh and provenance audits are owner maintenance tasks, not customer commands.
