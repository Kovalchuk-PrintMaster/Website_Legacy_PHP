# Public Web Asset Release Contract v0.1

**Status:** active
**Date:** 2026-09-17

A public web asset is a project-managed file under
`base/templates/default/assets/` intended to be served over HTTP(S).

Filesystem contract:

```text
regular public asset   0644
public asset directory 0755
```

Critical frontend asset release acceptance includes:

```text
present in intended HTML
HTTP 200 from production
intended version/query registration
SHA-256 parity with local where expected
browser rendering verification
```

SSH-side existence or SHA-256 alone does not prove HTTP readability.

Persistent guard:

```text
scripts/inspection/check_public_web_asset_permissions.py
make public-assets-permission-check
```
