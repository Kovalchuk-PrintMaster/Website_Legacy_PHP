# Single-command operator protocol

<!-- FP_SINGLE_COMMAND_OPERATOR_PROTOCOL_V0_1 -->

**ID:** `FP-WEB-WF-AI-001`
**Status:** active
**Primary assistant entrypoint:** root `AGENTS.md`

## Purpose

For substantial assistant/operator work the normal execution model is:

```text
download unique Python artifact
→ replace repository-root tmp.py
→ python tmp.py
→ script performs the full bounded operation
→ script prints report handoff fields
```

Repository-root `tmp.py` is persistent local scratch state and must not be
automatically deleted.

## Artifact handoff

The assistant provides a uniquely named downloadable `.py` artifact. Corrected
versions always get a new filename so browser/client caching cannot return an
older script.

Before handoff, the assistant syntax-checks the artifact.

The operator replaces the complete contents of root `tmp.py` and runs only:

```text
python tmp.py
```

A substantial task must not require the operator to manually assemble a long
shell block.

## What a generated tmp.py must do

A self-contained script should, as applicable:

- resolve and `chdir` to the exact project root;
- fail closed on wrong branch/HEAD/anchors/dirty scope;
- create report and raw-runtime directories;
- capture concise terminal output;
- capture verbose subprocess output separately;
- enforce timeouts and return-code handling;
- inspect `git status --porcelain=v1 --untracked-files=all`;
- preserve exact pre-run bytes of owned files before mutation;
- mutate only its declared owned paths;
- run focused syntax/checker gates;
- run `git diff --check`;
- inspect protected-path invariants;
- perform local preview restart/readiness when the active web/runtime slice
  requires it;
- rollback exact owned bytes on post-write failure;
- return a normal process exit code;
- never silently stage, commit, push, deploy, restore or perform provider writes.

## Reports

<!-- FP_REPORT_HANDOFF_PROTOCOL_V0_1 -->

Concise/shareable reports:

```text
tmp/operator_reports/<workstream>/
```

Raw/verbose runtime evidence:

```text
tmp/operator_runtime/<workstream>/
```

The report contains decisions, checks, changed paths and concise evidence.
Large command output belongs in runtime/raw logs.

Every reporting script finishes with:

```text
RESULT=PASS|FAIL
REPORT_READY=<relative/path/to/report>
REPORT_HANDOFF=REQUIRED|OPTIONAL|NONE
REPORT_INSTRUCTION=<short instruction>
```

Semantics:

- `REQUIRED` — return the report to the assistant before the next decision;
- `OPTIONAL` — PASS is sufficient unless evidence is requested;
- `NONE` — no report handoff is needed.

If there are several artifacts, the script may additionally print fields such
as `BUNDLE_READY=` or `SCREENSHOT_REQUIRED=`.

## Git preflight

Before write:

- verify repository root;
- verify expected branch and HEAD where the patch depends on an exact state;
- inspect local upstream/remote-tracking divergence where relevant;
- inspect concrete untracked files;
- verify exact hashes or semantic anchors for dirty files the script intends to
  modify;
- reject unknown dirty-scope drift when the patch was prepared against a
  specific audit bundle.

Use:

```text
git status --porcelain=v1 --untracked-files=all
```

Do not let Git collapse a new directory into an ambiguous untracked entry.

## Exact-byte rollback

<!-- FP_EXACT_BYTE_ROLLBACK_V0_1 -->

Before touching an owned path:

- if it exists, store the exact bytes;
- if it does not exist, store that fact.

Rollback:

- existing file → restore exact original bytes;
- originally absent file → remove only that newly created owned file.

Do not normalize trailing newlines/whitespace during rollback.

Never use broad repository rollback commands as a substitute for owned-file
restore:

```text
git reset --hard
git checkout .
git clean -fd
```

Unrelated dirty work is never part of rollback.

## Focused gates vs full suite

For a development slice, prefer:

```text
syntax/build check
→ relevant focused checker
→ canonical/ownership checker
→ git diff --check
```

For a major coherent integration or before checkpoint commit:

```text
all relevant focused checks
→ full project suite (`make check`)
→ protected-artifact review
→ exact Git diff/status scope review
```

Do not run expensive unrelated suites after every tiny visual adjustment.

## Local web/service routine

Canonical preview:

```text
service: forprint-website-preview.service
URL:     http://127.0.0.1:8098/
```

When a web/static/runtime patch requires reload:

```text
restart fixed preview service
→ bounded readiness loop
→ localhost HTTP success check
→ on failure capture service status
→ RESULT=FAIL
```

Use the existing project Makefile routine where possible.

A preview restart does not authorize production deployment, scheduler/job
activation, destructive DB work or provider mutations.

Docs/checker-only changes do not need a pointless service restart.

## Patch scripts vs closeout scripts

Normal patch script:

```text
mutate owned files
→ validate
→ report
→ stop
```

It does not stage/commit/push.

Only an explicitly requested closeout script may:

```text
run full suite
→ inspect exact diff
→ stage exact files
→ cached diff check
→ commit
→ push
→ verify origin sync
→ verify final status
```

## Reusable helper

Future generated scripts should use:

```text
scripts/operator_script_support.py
```

for common root/report/raw-log/subprocess/hash/Git-status/exact-byte rollback
boilerplate where practical.

The helper is not an authorization layer and does not implicitly perform
deployment or privileged actions.

## Exceptions

A genuinely trivial one-line read-only command may still be given directly when
that is clearer. Substantial audit/patch/migration/checker/context/closeout work
uses the single-command artifact model by default.

<!-- FP_SINGLE_COMMAND_BATCHING_V0_1_START -->
## Coherent-slice batching preference

The operator prefers fewer, more complete bounded artifacts.

When source ownership and the required contract are already known:

- combine the correction, focused checker, rollback, validation and report in one
  operator script;
- do not insert an extra read-only probe merely as ritual;
- use a probe first only when a real unknown would otherwise force guessing;
- keep each script bounded to one coherent work slice and preserve existing
  safety/release gates.

The operator continues to replace repository-root `tmp.py`, run only
`python tmp.py`, and return the generated report/evidence.
<!-- FP_SINGLE_COMMAND_BATCHING_V0_1_END -->
