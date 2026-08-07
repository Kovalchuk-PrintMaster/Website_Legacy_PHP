# Decision: production operational database ownership

**ID:** `FP-WEB-ADR-2026-08-07-004`
**Date:** 2026-08-07
**Status:** accepted

## Context

Production gained real `communication_requests` rows while local development remained behind. Their schema matched. Existing reset/database-sync logic used `clear → import local`, which could erase real production enquiries.

## Decision

1. Database ownership is explicit per table.
2. `communication_requests` is production-owned for row content.
3. Its schema remains strict local-canonical.
4. Normal full reset and database-only sync preserve the production table.
5. Operational content drift is informational.
6. Operational schema drift is blocking.
7. Complete production backup remains rollback authority.
8. Destructive operational replacement requires separately named commands and separate authorization.
9. Future operational tables are added through the versioned ownership policy.

Policy owner:

```text
config/deployment/database_ownership_policy_v0_1.json
```
