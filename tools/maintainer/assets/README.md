# Runa branding renderers

Owner-only Playwright tools capture the supplied brand tile. They are excluded
from customer projection and production; static assets and their licenses remain
with runtime resources. They resolve the existing src packages without another
installation. These scripts are not a customer branding API.

After building the frontend and provisioning the testing Nginx/PHP-FPM endpoint
at http://nginx-test:8080 through the documented development/testing workflow, run
from the host checkout using the application-container transport:

```sh
./bin/pnpm exec node ../tools/maintainer/assets/render-mail-logo.mjs
./bin/pnpm exec node ../tools/maintainer/assets/render-favicon.mjs
```

The transport selects src as cwd. The first writes
src/runa/resources/images/runa-mail-logo.png; the second writes
src/public/favicon.ico. Review both rendered results and update the provenance
hashes in the public design assets guide only after deliberately regenerating.
The scripts close their own browser and do not provision or remove an endpoint.
The endpoint remains owned by its provisioning workflow.
