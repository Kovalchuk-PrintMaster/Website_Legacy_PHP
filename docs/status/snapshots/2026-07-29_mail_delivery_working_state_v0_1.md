# ForPrint mail delivery working state — 2026-07-29

**ID:** `FP-WEB-STATUS-MAIL-2026-07-29-001`
**Status:** historical snapshot
**Machine-readable source:** `2026-07-29_mail_delivery_working_state_v0_1.yaml`

## Confirmed result

```text
communication-request.php: HTTP 200
website form message: arrived
SMTP submission: working
Exim acceptance: working
local delivery: working
IMAP mailbox access: working
blockers: none
warnings: none
```

Checkpoint `06D.39 v2` applied successfully. The endpoint and security component
were installed with backups, validated through the production LiteSpeed runtime,
and left active without rollback.

Active hashes:

```text
communication-request.php
1c7e156ed8541ff79593d478a0f4f125cc7238b30dcd06c45cdf69a53d975f91

CommunicationRequestSecurity.php
fe20952a3cbba429c63bb718cfb06f1e7d7f8e87b156816f5b9eea605fe92693
```

Backup:

```text
/var/www/825163-nikolay.k/data/.forprint-backups/communication_runtime_fix_06d39_v2_20260729_102703
```

The Network screenshot shows HTTP `200` for `communication-request.php`, and the
operator confirmed that the resulting message arrived in the mailbox. The
DevTools issue counters were not expanded, so unrelated console issues are not
classified by this snapshot.

## Remaining documentation work

Record MX, SPF, DKIM, DMARC, PTR ownership and a tested mailbox restore
procedure before the next infrastructure migration.
