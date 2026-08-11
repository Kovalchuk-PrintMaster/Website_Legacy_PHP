# Install ForPrint mail operations documentation v0.1

Run from the ForPrint Website repository root:

```bash
unzip -n forprint_mail_operations_documentation_v0_1_bundle.zip -d . && sha256sum -c forprint_mail_operations_documentation_v0_1_SHA256SUMS && python3 scripts/inspection/check_website_mail_operations_docs.py && git diff --check -- docs scripts/inspection/check_website_mail_operations_docs.py
```

`unzip -n` does not overwrite an existing file. The package adds versioned
documentation and one read-only Python validator. It does not modify website
runtime files, secrets, database, mail server, DNS or Git state.
