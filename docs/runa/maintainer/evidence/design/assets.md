# Owner procedure and historical evidence

Source classification: `docs/runa/handbook/design/assets.md`. These owner-only procedures and dated execution records are retained separately from customer guidance. Historical claims keep their original context; current public operations are documented in the handbook.

Application/test-fixture documents and native/static emergency error documents reference `/favicon.ico`. Production PHP and Nginx images both explicitly copy the committed asset; Nginx serves it while PHP is unavailable. This is a browser icon only, without a web-app manifest or installation icons. The owner visually accepted the favicon on 2026-10-18 in RM-2026-004 point 6 and requested its use in both MinimalLayout and ApplicationLayout. Their shared HTML entries already declare the same icon.
