# Application documentation

Record this application's domain, architecture decisions, operational inputs and user-approved plans here. Follow the [Runa handbook](../runa/handbook/README.md) and [documentation placement rules](../README.md).

Application roadmap lifecycle directories are `roadmaps/draft`, `roadmaps/active`, `roadmaps/paused` and `roadmaps/closed`. They initially contain only empty-directory markers, with no inherited Runa roadmap or release history. Use the shared [roadmap template](../runa/handbook/workflows/roadmap-template.md); the application owner approves activation.

Application code lives in `src/app`. Application version and identity live in `src/app/application.json`; platform versions are independent. Add application release notes and dependency decisions here when work establishes them.
