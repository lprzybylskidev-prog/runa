# Git Workflow

## Repository

Remote:

`https://github.com/lprzybylskidev-prog/runa`

Default branch:

`main`

Project Git identity:

- name: `Łukasz Przybylski`
- email: `lprzybylskidev@gmail.com`

## Source ownership

Runa maintenance commits/releases/state automation belongs only to the owner checkout.
Owner-only development/release entrypoints live under tools/maintainer; installation/update belongs to distribution. Neither root is delivered to the customer. Shared customer hooks live under tools/application.
Explicitly configure maintenance hooks with `git config --local core.hooksPath .githooks`.
Customer installation mode never authorizes Runa Git writes. Customer deployment
evidence stays local; backups are externally managed by the customer.
Verified acquisition records a fixed sparse projection and disables upstream pushes;
unknown targets are refused without repair/repointing. Customer clones prepare their own local dev/prod role and select their existing customer remote; they do not inherit an acquisition receipt. Explicit local `runa.applicationRemote` selection permits an existing customer origin; deployment validates that customer URL and preserves all remotes/history. Without selection, the original read-only acquisition upstream boundary remains mandatory.
See [bootstrap](../tooling/bootstrap.md) for the receipt and delivery boundary.

## Branching

Application implementation occurs on roadmap branches:

`roadmap/RM-YYYY-NNN-slug`

Branches start from an updated `main`.

Planning-only draft roadmap/doc changes may occur on `main` when no application implementation is included.

Do not make application fixes directly on `main`. Create an appropriate roadmap.

## Commits

Use Conventional Commits.

Roadmap-related commits include the roadmap ID in the scope:

- `feat(RM-2026-003): add customer search`
- `fix(RM-2026-004): prevent stale invoice update`
- `chore(RM-2026-005): update runtime base image`

The agent decides logical commit boundaries.

Avoid:

- giant unrelated commits,
- one-line-one-commit mechanical history,
- squash-by-default workflows that erase useful roadmap history.

## Merge

Default merge style: merge commit.

Example:

`merge(RM-2026-003): complete user management`

Preserve meaningful branch commits.

Rebase only as a conscious repository-maintenance decision, not an automatic habit.

## Main branch invariant

`main` must remain healthy and deployable.

After roadmap merge:

- run a short sanity check on `main`,
- do not leave known branch-only assumptions,
- delete the roadmap branch after release finalization.

## Git hooks

Hooks are versioned in `.githooks/`.

Repository setup configures:

`git config core.hooksPath .githooks`

Do not use Husky.

### pre-commit

Fast/preventive checks only:

- Gitleaks on staged content,
- formatting changed files,
- quick lint changed files,
- knowledge-index staleness check,
- basic repository sanity,
- block `.env`,
- block private keys/suspicious secrets,
- block unwanted large binaries.

Do not run the entire test/static/security suite on every commit.

### commit-msg

Validate:

- Conventional Commit structure,
- required roadmap ID scope when a roadmap is active.

## Unsafe Git behavior

Do not:

- force-push `main`,
- rewrite closed roadmap history,
- blindly rebase a paused branch,
- delete unmerged work without explicit user intent,
- bypass hooks merely to obtain a green-looking commit.

Any hook suppression must be exceptional, understood, and repaired before roadmap closure.

## Staged-content integrity and hook acceptance

Formatting/lint checks must preserve intentional partial staging. If an automatic formatter would rewrite a partially staged file, stop with an actionable instruction rather than staging unrelated work. Secret checks inspect the actual staged content. Do not indiscriminately `git add` an entire file/tree to repair a hook failure.

Verify installed hooks with safe temporary fixtures, including rejection of a secret/environment file and an invalid commit message. Keep fixtures out of permanent history and never use real credentials. There is no heavy pre-push hook in the baseline.

After all closure gates pass, the agent merges, pushes, tags and publishes the release autonomously under this workflow. Do not repeatedly ask for merge permission already granted by the project contract; actual failed gates or missing access remain blockers.
