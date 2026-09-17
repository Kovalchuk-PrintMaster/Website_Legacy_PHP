# ForPrint Website — Browser visual inspection with Playwright v0.1

**ID:** `FP-WEB-DEV-BROWSER-001`
**Version:** `v0.1`
**Date:** `2026-09-17`
**Status:** `active`
**Scope:** development and read-only browser inspection tooling

## Purpose

ForPrint uses a real Playwright-managed Chromium browser as a repeatable
inspection layer between HTTP smoke checks and human visual acceptance.

The browser evaluates actual HTML, CSS, JavaScript and responsive layout before
screenshots and DOM geometry are recorded.

Canonical entrypoint:

```text
scripts/inspection/browser_visual_inspection.py
```

This is development/QA infrastructure, not part of the PHP production runtime.

## Global role

Use the browser inspection layer for:

- responsive acceptance at baseline widths;
- screenshot evidence;
- DOM geometry and horizontal-overflow checks;
- JavaScript-rendered UI validation;
- local preview checks before release;
- read-only public/hosting checks after deployment or during parity review.

It complements rather than replaces HTTP smoke, syntax checks, focused
application inspections and human review.

## Local preview

Default target:

```text
http://127.0.0.1:8098
```

Run:

```bash
.venv_website/bin/python scripts/inspection/browser_visual_inspection.py
```

## Public hosting

The same tool can inspect a network-accessible public URL such as:

```text
https://forprint.net.ua
```

Remote mode must be explicit:

```bash
.venv_website/bin/python scripts/inspection/browser_visual_inspection.py \
  --base-url https://forprint.net.ua \
  --allow-remote \
  --output-dir tmp/browser_visual_inspection/production
```

Remote safety contract:

- only `GET`, `HEAD` and `OPTIONS` are allowed;
- mutating HTTP methods are aborted;
- no clicks or form submissions;
- no Admin authentication;
- no production file/DB/config mutation.

State-changing browser automation requires a separate explicit authorization.

## Python dependency

Repository-local Python runtime:

```text
.venv_website/
```

Tracked requirement:

```text
config/python/requirements-browser-inspection.txt
```

Current pin:

```text
playwright==1.63.0
```

Install:

```bash
.venv_website/bin/python -m pip install \
  -r config/python/requirements-browser-inspection.txt
```

## Chromium binary

Playwright-managed Chromium is local runtime state:

```text
.runtime/playwright-browsers/
```

Install:

```bash
PLAYWRIGHT_BROWSERS_PATH="$PWD/.runtime/playwright-browsers" \
  .venv_website/bin/python -m playwright install chromium
```

## Debian 12 runtime dependencies

Canonical manifest:

```text
config/system/debian12-playwright-chromium-runtime-packages.txt
```

Top-level packages:

- `libnspr4`
- `libnss3`
- `libatk1.0-0`
- `libatk-bridge2.0-0`
- `libatspi2.0-0`
- `libxcomposite1`
- `libxdamage1`
- `libxfixes3`
- `libxrandr2`
- `libgbm1`
- `libxkbcommon0`
- `libasound2`

Install on a Debian 12 development/inspection host:

```bash
apt-get install -y --no-install-recommends \
  $(grep -Ev '^\s*(#|$)' \
    config/system/debian12-playwright-chromium-runtime-packages.txt)
```

These packages are required only on a host that runs Chromium inspection. A
normal production web host does not need them unless browser inspection is
intentionally executed there.

## New-server bootstrap

For a new Debian 12 development/inspection server:

1. create/restore the normal `.venv_website`;
2. install `config/python/requirements-browser-inspection.txt`;
3. install the Debian system package manifest;
4. download Chromium into `.runtime/playwright-browsers`;
5. run a local browser inspection;
6. retain screenshots/result JSON as acceptance evidence.

## Responsive baseline

Default widths:

```text
1920x1080
1600x1000
1366x900
1024x900
768x900
390x844
```

Default routes:

```text
/
/catalog/
/technical-requirements/tsyfrovyi-druk/
```

Additional routes can be supplied with repeated `--route` arguments.

## Evidence

A run records:

- screenshots;
- `result.json`;
- browser version;
- HTTP status;
- document horizontal overflow;
- selected component geometry;
- contact sheet;
- blocked mutating network requests.

Evidence is ignored runtime state under `tmp/`.

## Non-goals

This tool is not a deployment system, not a general crawler, not Search Console,
not Admin automation, and not an automatic release decision-maker.
