# Public asset permission incident and recovery — 2026-09-17

**Status:** historical snapshot

Production returned HTTP 403 for:

```text
forprint-catalog.css
forprint-shell.css
forprint-technical-requirements.css
```

The files had mode `0600`. Their bytes were correct.

Recovery normalized the CSS to `0644` without byte changes and reran canonical
`make hosting-sync-full`, restoring production rendering.

Preventive hardening then found the same `0600` mode on:

```text
base/templates/default/assets/js/forprint-technical-requirements.js
```

The permanent rule covers the full project-managed public asset tree:
regular files `0644`, directories `0755`.
