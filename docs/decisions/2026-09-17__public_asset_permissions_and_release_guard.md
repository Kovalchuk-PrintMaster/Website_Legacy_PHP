# Decision: public asset permissions are part of the release artifact

**ID:** `FP-WEB-ADR-2026-09-17-001`
**Date:** 2026-09-17
**Status:** accepted

## Context

Production referenced the intended modern CSS, but these files returned HTTP 403:

```text
forprint-catalog.css
forprint-shell.css
forprint-technical-requirements.css
```

Their bytes were correct; their Unix mode was `0600`. During preventive
hardening, `forprint-technical-requirements.js` was also found at `0600`.

## Decision

- Regular project-managed public assets under
  `base/templates/default/assets/` use mode `0644`.
- Public asset directories use mode `0755`.
- Public asset permissions are release metadata.
- `make public-assets-permission-check` is blocking.
- The guard is a prerequisite of `make check`,
  `make hosting-sync-full-dry-run`, and `make hosting-sync-full`.
- Canonical publication remains `make hosting-sync-full`.
- Normal CSS/JS/templates are local-authoritative deployable state.
- Hosting-owned protection remains limited to established Telegram,
  Email/SMTP, Google measurement/provider runtime/secrets, and true
  server-runtime files.

A private public asset such as `0600` cannot pass canonical publication even if
deployment-side hashes are correct.
