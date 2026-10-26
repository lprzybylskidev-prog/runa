# Tool ownership

bin contains thin customer launchers/setup/recovery transports. tools/application contains implementation of shared customer application development/operations, hooks, security and development-only agent knowledge. tools/maintainer contains Runa owner development/test/release entrypoints and is excluded from customer delivery. External installer/updater sources belong to distribution, also excluded from delivery.

Do not add owner Artisan aliases. Reuse application primitives internally, while keeping owner-specific orchestration and checks outside the delivered application tool tree. Customer production images continue to exclude development and AI tooling.

Python package ownership, language and generated-state rules are documented in the
[public Python guide](../docs/runa/handbook/development/python-tooling.md).
Existing command families retain their transports during their package migration.
