# Owner procedure and historical evidence

Source classification: `docs/runa/handbook/data/seeding.md`. These owner-only procedures and dated execution records are retained separately from customer guidance. Historical claims keep their original context; current public operations are documented in the handbook.

No application `DatabaseSeeder`, `db:seed`, `runa:seed` or reset command invokes Runa seeders. Native `db:seed --class` also validates application ownership. Private `python3 tools/maintainer/seed 'Runa\Database\Seeders\ClassName'` manually runs one selected platform seeder; loose platform seeders are not discovered. The platform `DatabaseSeeder` explicitly lists only required platform initialization (currently empty).
