# Platform update planning

Runa platform installation/update is performed externally by the Runa owner using separately supplied tooling. It is separate from customer application deployment; `runa:deploy` fetches only the configured customer repository and runs only application migrations/seeds.

The current external updater can inspect an installation and produce a read-only preflight/plan. It retrieves the original installed and selected target releases independently, compares protected source, checks application compatibility and preserved dependency locks, and inspects the running services/database. Modified, missing or unexpected protected files block; there is no repair or consent override. Adapt customer behavior through application/configuration extension points.

Planning changes no installation source, configuration, cache, database data or services. Temporary source downloads and isolated validation containers stay outside the application state and are cleaned up. Reports identify blockers, required platform data steps and uncertainties. Lock consistency does not replace build-time supply-chain policies or release security audits. Infrastructure/input changes require concrete review and cannot waive protected-source differences.

Applying platform updates and resumable recovery are not delivered yet. Coordinate the reviewed release and operational procedure with the owner. Application identity/version, source, dependency locks and both durable storage trees must remain preserved. Backups/recovery are externally managed by the customer; code rollback cannot reverse schema/data. See [application deployment](deployment.md) and [database ownership](../architecture/database.md).
