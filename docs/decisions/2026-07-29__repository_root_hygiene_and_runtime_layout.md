# Repository-root hygiene and runtime layout

**ID:** `FP-WEB-ADR-2026-07-29-ROOT-001`
**Date:** 2026-07-29
**Status:** accepted

## Decision

The repository root contains stable entry points and structured namespaces.
Documentation installers, generated package artifacts, local runtime files
and operator scratch files move to owned subdirectories.

The active preview service already reads
`/etc/forprint/website-preview.env`; this path remains unchanged.

The local fallback `.env.website.local` moves to
`.runtime/env/website.local`; its tracked example moves to
`config/env/website.local.example`.

`.venv_website/` remains because current Makefile, documentation and
inspection tools explicitly depend on it.

Root `tmp.py`, root `tmp.php` and the `tmp/` directory are local ignored
temporary development/operator state. They are not canonical repository
content and are not included in releases.

This change does not modify `base/`, systemd, hosting, database, mail,
Telegram or secret values.
