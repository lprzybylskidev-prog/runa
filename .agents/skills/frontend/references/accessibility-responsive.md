# Accessibility and responsive review

Read [the accessibility baseline](../../../../docs/runa/handbook/architecture/frontend.md#accessibility-baseline)
and only the applicable criteria/patterns. Use the canonical content-robustness and
acceptance sections for long/empty content, PL/EN, themes, narrow layouts and
actual verification limits. This reference selects the review; the handbook owns
technical standards.

For a changed control inspect its semantic name, keyboard/pointer states and
focus visibility. For a modal inspect focus entry/containment/restoration and
portalled feedback through the public Dialog contract. For a dense table inspect
its bounded horizontal viewport, sticky header and accessible controls. Review
actual rendered behavior in the roadmap's acceptance phase; source/role attributes
alone do not establish conformance.

Auth step changes may focus a noninteractive reading heading with tabindex=-1.
Retain the documented scoped reading-focus treatment while interactive controls
keep visible focus. This does not authorize removing focus indicators globally.
