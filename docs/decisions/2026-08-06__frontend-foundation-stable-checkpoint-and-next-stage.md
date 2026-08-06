# Decision: frontend foundation stable checkpoint and controlled next stage

**ID:** `FP-WEB-ADR-2026-08-06-001`
**Date:** 2026-08-06
**Status:** accepted
**Scope:** local frontend/runtime checkpoint, documentation and Git boundary

## Context

The local worktree contains several parallel streams: public frontend and
runtime work, documentation, repository-hygiene work, and a large Google Ads
and SEO research workspace. A broad commit would mix reviewed application
changes with account exports, research data and scratch artifacts.

The public site has reached a useful intermediate state that is suitable for
a reproducible source checkpoint even though final responsive acceptance and
minor animation tuning remain open.

## Decision

1. Create a dated historical snapshot for the accepted local frontend state.
2. Commit the complete reviewed application/runtime source set needed to
   reproduce that state.
3. Include the related frontend inspection tools and canonical documentation.
4. Use an explicit staging manifest; do not use `git add .` or another broad
   staging command.
5. Exclude SEO/Ads research, account exports, local secrets, environment
   values, backups and scratch files.
6. Push only after PHP lint, CSS structure checks, staged diff checks and
   local HTTP route checks pass.
7. Keep deployment separate from this source checkpoint.
8. Treat footer logo cadence as later visual tuning rather than a blocker for
   this checkpoint.

## Consequences

- `origin/main` receives a reproducible frontend/runtime checkpoint.
- Parallel SEO/Ads and repository-hygiene changes remain in the local
  worktree for separate review and commits.
- The working tree may remain dirty after the push by design.
- The next frontend task starts from a named, documented source state.
