# Production operational database ownership evidence — 2026-08-07

**Status:** completed analysis

The audit confirmed that `communication_requests` had different local/production content while its logical schema matched. It also confirmed that normal full reset and database-only sync cleared the database and imported the local dump.

The accepted correction is to preserve production operational rows by default, keep schema parity strict, treat row/content drift as informational, retain complete rollback, and expose destructive replacement only as an explicitly named high-risk operation.
