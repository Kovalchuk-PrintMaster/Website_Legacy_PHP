# ForPrint_Web_Site_Base — Selected Source Checkpoint v0.4.4 Blocked

## Status

`selected_source_checkpoint_v0_4_4_blocked`

## Purpose

Attempt to stage selected legacy website source files for the first controlled source checkpoint.

## Result

The selected source checkpoint is blocked and must not be committed yet.

## Blockers found

### 1. CRLF / line-ending normalization required

`git diff --cached --check` produced mass trailing-whitespace errors caused by CRLF `^M` line endings in inherited legacy source files.

Mitigation:

- add `.gitattributes`;
- normalize source/text files to LF in git;
- keep binary assets marked as binary;
- rerun `git diff --cached --check`.

### 2. Hardcoded default admin seed risk

Sensitive keyword scan found a hardcoded default admin seed in the legacy admin user creation path.

Mitigation:

- do not commit the selected source checkpoint until this is neutralized;
- replace hardcoded admin seed with a local-only configuration requirement or disable automatic default admin creation;
- keep public admin blocked.

### 3. Known inherited launch blockers remain

The scan also confirms known inherited risks:

- legacy `md5` password handling;
- direct `$_GET` / `$_POST` / `$_COOKIE` / `$_FILES` usage;
- dynamic SQL construction;
- upload handling through `move_uploaded_file`;
- public launch remains blocked.

## Safety decision

Do not commit selected legacy source yet.

Do not run:

```text
git add base/

                              Next recommended step

ForPrint_Web_Site_Base — Line Ending Normalization and Admin Seed Neutralization v0.4.4a

Recommended scope:

keep .gitattributes;
neutralize hardcoded default admin seed;
normalize line endings for selected source/text files only;
rerun PHP syntax;
rerun git diff --cached --check;
rerun sensitive keyword scan;
only then create selected source checkpoint.
