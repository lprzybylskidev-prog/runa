# Application localization

User UI defaults to Polish, supports English and light/dark themes. Administration UI also supports Polish by default and English. Technical source/API/CLI content stays English.

Application translations belong in src/app/lang, with namespaced application messages where appropriate. Override only documented namespaced platform translations through application paths; do not edit the protected Runa/vendor translation snapshot. Use the shared frontend preference provider and translation contracts described in [frontend architecture](../architecture/frontend.md), preserving language/theme persistence and accessible control names.

Runa supplies its reviewed validation catalogs and public PL/EN UI. Application-specific additions and backend validation messages should use Laravel translations with immediate frontend handling of known UX errors; backend validation remains authoritative. Test both languages at representative viewport sizes and verify longer content/reflow. Platform catalog refresh and provenance audits are owner maintenance tasks, not customer commands.

Shared UI copy uses the current application locale for feedback, loading, tags,
page navigation and footer defaults. Controls with an existing locale prop use it
for built-in errors and actions, including files, numbers, colors and date/time
fields; explicit copy overrides remain supported. ModulePage localizes its
unavailable fallback. Native/static errors localize the request identifier label,
while HTTP codes and request identifier values remain technical identifiers.
See the [component catalogue](frontend/components.md#localized-defaults).
