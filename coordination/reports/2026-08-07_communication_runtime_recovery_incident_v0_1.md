# ForPrint communication runtime recovery incident — 2026-08-07

**Status:** completed evidence

## Outcome

Production Telegram and email enquiry forms were restored. Final browser requests to `communication-request.php` returned HTTP 200.

## Root-cause chain

1. The preserved runtime lacked newly required security secret/directory values.
2. The canonical bootstrap initially did not expose those new keys.
3. Product-page CSRF issuers did not load the same production runtime as the endpoint verifier, causing HTTP 403.
4. Historical boolean normalization was lost during bootstrap refactoring, so SMTP could be semantically enabled without satisfying the endpoint's strict `=== '1'` predicate.

## Permanent safeguards

- security keys recognized by the runtime checker;
- full non-sending communication acceptance;
- deployment pre/post gate upgraded;
- full reset post-install communication gate;
- CSRF issuer/verifier parity check;
- email/Telegram predicate checks;
- temporary deployment authorization wrapper;
- dedicated recovery workflow.

No secret values, contact details or private message content belong in this report.
