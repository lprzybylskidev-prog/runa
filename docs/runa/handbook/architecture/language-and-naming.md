# Language and Naming

## Language

English-only:

- source code,
- classes, methods, variables,
- database identifiers,
- API machine contracts,
- technical exceptions,
- logs,
- CLI commands/help/output,
- scripts,
- configuration,
- deployment/doctor/quality output,
- documentation,
- Git commit messages,
- agent skills/guidance.

User-facing frontend:

- Polish default,
- English secondary.

Administration panel:

- English only.

## Naming conventions

- PHP classes: `PascalCase`
- PHP methods/variables: `camelCase`
- configuration keys: `snake_case`
- environment variables: `UPPER_SNAKE_CASE`
- database schemas/tables/columns/index names: `snake_case`
- route names: dot notation
- API URLs: plural/kebab-case where sensible
- filesystem/project technical names: English

Routes are always named.

Avoid unexplained abbreviations. Prefer meaningful domain terms over `data`, `result`, `tmp`, or other vague names when the value has a real concept.

Use one stable term for one concept throughout code, API, UI, and documentation.
