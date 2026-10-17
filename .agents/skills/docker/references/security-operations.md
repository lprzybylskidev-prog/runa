# Docker Security and Operational Safety

1. Do not mount `/var/run/docker.sock` into app/devcontainer without a very specific approved need.
2. `privileged` containers are forbidden by default.
3. Do not add Linux capabilities unless required.
4. Do not expose host devices unless needed; development GPU is a deliberate exception.
5. Do not copy host SSH keys into images.
6. If a build needs private repository access, use secure forwarding/secret mount rather than `COPY ~/.ssh`.
7. Production image must not contain `.git`.
8. Trivy should scan the actual built runtime image, not only source/Dockerfile.
9. Evaluate critical/high image CVEs by exploitability/context, but do not silently ignore them because they come from a base image.
10. Base image updates are maintenance changes, not invisible rebuilds on mutable `latest`.
11. Do not add Cosign/signing/registry pipeline as baseline without a real need.
12. Do not add custom seccomp/AppArmor profiles as baseline without a risk/deployment need.
13. Do not add Docker Bench merely to increase tool count.
14. `down -v`, volume prune, DB-volume deletion, persistent storage reset, and equivalent operations are explicitly destructive.
15. `docker system prune -a` is not a project troubleshooting step.
16. Project scripts operate only on resources belonging to the Runa Compose project; never global-prune the host.
17. A development reset command, if added, must be clearly named destructive behavior.
18. Show what will be deleted before destructive reset.
19. Production scripts always use explicit production Compose/project context.
20. Production commands must not accidentally target development stack and vice versa.
21. Docker guidance uses progressive disclosure: build/Compose, lifecycle/devcontainer, security/operations references.
22. First identify dev vs prod context before changing infrastructure.
23. Do not rewrite a working Compose/Dockerfile merely to conform to a generic skill aesthetic.
24. Runtime Docker changes must check dev/prod parity impact.
25. Validate actual resolved Compose config and health after infrastructure changes, not just YAML syntax.
26. Do not add Kubernetes, Swarm, Docker Agent/Sandboxes, or another orchestrator to the baseline.
27. Do not force distroless/Alpine as a dogma.
28. Do not auto-prune resources.
29. Do not use Docker socket/privileged mode as a convenience shortcut.
30. Production AI/RAG isolation rules remain mandatory regardless of generic Docker recommendations.
