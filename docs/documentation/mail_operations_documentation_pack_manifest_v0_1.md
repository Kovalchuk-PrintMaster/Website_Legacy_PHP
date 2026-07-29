# ForPrint mail operations documentation pack manifest v0.1

**ID:** `FP-WEB-DOC-PACK-MAIL-001`
**Date:** 2026-07-29
**Status:** working architecture package

## Purpose

This package records the verified mail architecture, client settings, working
state, incident triage and migration/rollback procedure. Machine-readable
documents use the YAML 1.2 JSON-compatible subset and can be parsed with the
Python standard `json` module.

## Canonical machine-readable documents

```text
docs/architecture/mail_delivery_and_hosting_architecture_v0_1.yaml
docs/reference/mail_client_and_application_configuration_v0_1.yaml
docs/workflow/mail_operations_and_hosting_migration_runbook_v0_1.yaml
docs/status/snapshots/2026-07-29_mail_delivery_working_state_v0_1.yaml
```

## Validation

```bash
python3 scripts/inspection/check_website_mail_operations_docs.py
git diff --check -- docs scripts/inspection/check_website_mail_operations_docs.py
```

The package does not run `git add`, commit, push or deployment. It contains no
passwords, private keys, tokens, raw queue identifiers or mailbox contents.
