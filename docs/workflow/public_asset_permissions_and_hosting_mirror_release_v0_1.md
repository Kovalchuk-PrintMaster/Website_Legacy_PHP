# Public Asset Permissions and Hosting Mirror Release v0.1

**Status:** active
**Date:** 2026-09-17

## Canonical release sequence

```text
make public-assets-permission-check
make check
make hosting-health-pre
make hosting-sync-full-dry-run
make hosting-sync-full
```

Canonical modes:

```text
regular public asset   0644
public asset directory 0755
```

Local project state is authoritative for normal application/frontend assets.
Hosting runtime protection remains narrow and explicit: Telegram delivery,
Email/SMTP delivery, Google measurement/provider runtime/secrets, and true
server-runtime files already protected by the canonical sync contract.

If page HTML is HTTP 200 but styling/behavior is missing, request the exact
CSS/JS URL first:

- 403 -> permission/traversal/server access;
- 404 -> deployment/path registration;
- 200 + wrong SHA -> mirror/cache/content mismatch;
- 200 + same SHA -> cascade/runtime/browser state.

Communication/measurement failures use exact-run diagnostics:

```text
make hosting-health-diagnose HEALTH_REPORT=<exact failed health run>
```
