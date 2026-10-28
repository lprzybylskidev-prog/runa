# Tool ownership

bin contains thin customer launchers/setup/recovery transports. tools/application contains implementation of shared customer application development/operations, hooks, security and development-only agent knowledge. tools/maintainer contains Runa owner development/test/release entrypoints and is excluded from customer delivery. External installer/updater sources belong to distribution, also excluded from delivery.

Do not add owner Artisan aliases. Reuse application primitives internally, while keeping owner-specific orchestration and checks outside the delivered application tool tree. Customer production images continue to exclude development and AI tooling.

Python package ownership, language and generated-state rules are documented in the
[public Python guide](../docs/runa/handbook/development/python-tooling.md).
Command families use qualified package services and fixed thin transports;
public CLI names and native PHP/JS runners retain their documented contracts.

Private frontend suite configurations live in tools/maintainer/frontend and
branding renderers in tools/maintainer/assets. They use the existing src Node
installation. Delivered PHP operational tooling lives in src/runa/Tooling/Management;
module generators/templates in src/runa/Development/Modules; static-error build
executables in src/runa/build/errors. Runtime resources remain under resources.
