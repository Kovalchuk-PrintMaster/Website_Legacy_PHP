# Install ForPrint notification and release documentation v0.1

Run from the ForPrint Website repository root:

```bash
unzip -n forprint_notification_and_release_operations_documentation_v0_1_bundle.zip -d . && sha256sum -c forprint_notification_and_release_operations_documentation_v0_1_SHA256SUMS && python3 -m py_compile scripts/inspection/check_website_notification_release_docs.py && python3 scripts/inspection/check_website_notification_release_docs.py && git diff --check -- docs scripts/inspection/check_website_notification_release_docs.py
```

`unzip -n` does not overwrite existing files. The package adds versioned
documentation and one read-only validator. It does not modify website
runtime files, secrets, database, Telegram, mail service, DNS or Git state.
