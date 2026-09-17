# AGENTS.md — ForPrint Website AI / operator contract

<!-- FP_AGENTS_ENTRYPOINT_V0_1 -->

This file is the **primary assistant entrypoint** for this repository. A new AI
assistant must read it before proposing repository mutations.

Repository truth must live in Git, code, runtime evidence, current-state
documentation and checks. Chat memory is supplementary only.

## 1. Project identity

- Project: **ForPrint Website**
- Repository root: `/srv/software_development/forprint-project/forprint_website`
- Canonical branch: `main`
- Public website/webroot source: `base/`
- Website runtime: inherited **PHP 8.2** application, progressively modernized in place
- Engineering/tooling language: **Python** for inspection, orchestration,
  maintenance, reporting, deployment tooling and AI-generated operator scripts
- Local Python environment: `.venv_website/`
- Local preview service: `forprint-website-preview.service`
- Preview bind / URL: `127.0.0.1:8098`, `http://127.0.0.1:8098/`
- Production model: the Git repository is authoritative for versioned
  application source; production is a controlled runtime mirror, not a backup
  repository.

Do not create a parallel framework merely because the inherited runtime is PHP.
Use the existing architecture and internationally established web,
accessibility, Git, HTTP, security and database practices.

<!-- FP_ACTIVE_DEVELOPMENT_HOSTING_MIRROR_POLICY_V1 -->

## 1.1 ACTIVE DEVELOPMENT HOSTING MIRROR — READ BEFORE ANY SYNC

**Current authority mode is intentionally development-first.**

While the website remains under active local development:

- local code is authoritative;
- local project-managed `userfiles`/media are authoritative;
- the **FULL production database** is replaced from the validated local database
  during `make hosting-sync-full`;
- production hosting is a disposable publication mirror for application-owned
  code, media and DB content;
- there is currently no production-side catalog/order/accounting workflow whose
  rows must be preserved across the canonical full development sync;
- hosting-owned runtime/environment/secrets/configuration must still be
  preserved and release-health checked.

Do **not** reinterpret the direct full DB import in
`scripts/maintenance/sync_local_to_hosting_full.py` as a bug while this policy
is active. Do not silently replace it with operational-row preservation.

Canonical decision:
`docs/decisions/2026-09-06__active_development_hosting_mirror_authority.md`.

Permanent checker:

```text
make development-mirror-policy-check
```

This authority model changes only through an explicit replacement decision when
the project later moves from active local development to production/admin
maintenance.

## 2. Mandatory reading order

For a new assistant:

1. `AGENTS.md`
2. `docs/project_state/current/CURRENT_PROJECT_STATE.md`
3. `docs/assistant_workflow/SINGLE_COMMAND_OPERATOR_PROTOCOL.md`
4. `docs/README.md`
5. `docs/plans/admin_ui_modernization_plan_v0_2.md` when the active work is admin UI
6. `docs/reference/admin_ui_visual_refinement_contract_v0_1.md` when the active work is admin UI
7. `docs/decisions/2026-08-23__canonical_admin_css_ownership_and_migration_order.md` when the active work is admin UI
8. `docs/workflow/operator_assistant_workflow_v0_1.md`
9. `Makefile` and the relevant permanent checker(s)
10. current `git status --porcelain=v1 --untracked-files=all`, branch, HEAD,
    local upstream state and relevant diff
11. relevant source code and the latest evidence for the active slice

Historical snapshots are evidence, not current instructions. When a historical
snapshot disagrees with verified current code/runtime/current-state docs, the
verified current state wins.

## 3. Product / domain vocabulary

Do not invent duplicate entities because the UI wording differs from inherited
internal names.

- **Public website** — the website served from `base/`.
- **Admin / Web Admin** — the authenticated PHP administration surface under
  `/admin/...`; it edits existing inherited records and settings.
- **Managed entity / table / record** — an existing backend data model exposed
  through the generic admin. A UI label does not create a new canonical entity.
- **System settings** — admin-managed settings records such as the current
  Footer settings surface.
- **Main image** — the primary image owned by an existing record contract.
- **Gallery / additional images** — only images supported by the existing
  storage/schema/gallery owner. Never invent `gallery_img`, a new table, or a
  parallel media endpoint to satisfy a visual idea.
- **Marketing control plane** — tracked definitions under `config/marketing/`
  plus repository-owned Python tooling and documentation. Definition/preview is
  distinct from provider mutation.
- **Production operational data** — data whose current content may be
  production-owned even while schema/application source remain local/Git-owned.
  Ownership is defined by the existing database/deployment policy, not guessed.
- **Preview** — local runtime verification. It is not deployment.
- **Release / deployment** — explicit controlled mirroring to hosting. It is not
  implied by a passing patch.
- **Workstream / slice** — a bounded unit of operator/assistant work with its own
  evidence, checks and acceptance.

Generic terms such as Source, Target, Job, Task, Plan or Schedule are not new
website-domain entities unless an existing subsystem explicitly defines them.

## 4. Current checkpoint

<!-- FP_CURRENT_CHECKPOINT_POLICY_V0_1 -->

Canonical live state:
`docs/project_state/current/CURRENT_PROJECT_STATE.md`.

At the 2026-09-05 checkpoint:

- branch: `main`
- repository HEAD: `9dc6d12cb12c12007c42d83c760b2479819a6506`
- local `origin/main` remote-tracking ref at audit: same commit, `0/0` divergence
  (no network fetch was performed by the audit)
- accepted structural admin checkpoint:
  `eb5f0a314f633ab0a7f33af529e7b9f0072ae26c`
- admin Phase 1–7 structural modernization: **complete**
- Phase 8 visual refinement: **active**
- current working tree: intentionally broad and dirty; 89 porcelain entries at
  the entrypoint audit
- current Phase 8 working-tree focus: Footer settings, Footer link/phone child
  forms, shared admin geometry and the login password-reveal control
- current Goods page remains the practical visual-reference direction, but its
  working-tree state is not a separate committed checkpoint
- production deployment is **not** part of the current admin visual checkpoint

Do not call Footer/login work completed merely because it exists in the dirty
working tree. Browser acceptance, focused checks and a later explicit Git
checkpoint are still required.

## 5. Safety boundaries

<!-- FP_PROTECTED_BOUNDARY_POLICY_V0_1 -->

Default assistant posture is **read-only evidence before mutation**.

Protected/high-risk state includes:

- `base/config.php` and other local/production credential-bearing config
- `.runtime/env/`, `.runtime/recovery/`, `.runtime/backups/`
- `base/userfiles/`
- `base/log/`, `base/temp/`
- `database_dumps/` and other DB import/export artifacts
- production databases and production operational rows
- provider credentials, OAuth tokens, private keys and credential stores
- production hosting environment state
- backup/restore destinations
- live provider campaigns/billing/financial actions

The assistant may implement or improve a backend/UI **pathway** for an operation
without being authorized to execute the real destructive/provider/production
operation.

Explicit authorization is required before any of these classes are executed:

- production deployment or hosting sync
- destructive DB import/replace/migration
- restore/reset
- deletion of production/runtime data
- scheduler/job activation
- provider write/apply
- billing/financial action
- live communication/send action when a safe non-sending acceptance path exists

A local preview restart is routine only when a web/static/runtime patch actually
needs it. It never authorizes production deployment.

Do not expose secrets in reports, bundles or chat output.

## 6. Authentication and mutation model

The admin authentication/routing model is inherited PHP and is a protected
contract. Do not modernize auth/session/routing incidentally while changing UI.

Operational mutation rules:

- assistant/operator workflow starts read-only;
- authenticated Web Admin mutation must preserve the existing route, record
  identity, field names and submission contract unless the active slice
  explicitly changes that contract;
- destructive UI actions require an explicit confirmation pattern;
- CSRF coverage is route-specific: do not assume a project-wide admin CSRF
  contract without resolving the actual route;
- a global optimistic-locking contract is **not currently documented**; do not
  claim one exists;
- database transactions are operation-specific; do not assume them;
- Git/evidence reports provide engineering auditability, but are not a
  substitute for an application-level audit log;
- risky data/runtime changes require the relevant snapshot/backup/recovery
  policy before apply.

## 7. UI standards

<!-- FP_ADMIN_UI_POLICY_V0_1 -->

For the current admin modernization:

- `main.css` is legacy fallback only; **no new generic admin presentation**
  belongs there;
- `forprint-admin.css` owns shared admin tokens/primitives and shared Footer
  presentation;
- `forprint-admin-goods-form.css` owns Goods-specific presentation;
- `forprint-admin-gallery.css` owns gallery presentation;
- `forprint-admin-ordering.css` owns ordering/save-state presentation;
- use one project-owned geometry/presentation contract per component;
- do not accumulate blind append-only override generations;
- resolve duplicate selectors, legacy classes and runtime DOM ownership before
  adding another patch;
- `!important` is temporary legacy isolation, not the normal cascade model;
  do not mass-remove it and do not add it casually;
- shared semantic primitives should be fixed at the shared owner rather than
  copied across pages;
- preserve server-side/backend contracts; use JavaScript for bounded progressive
  enhancement/relocation only when the existing runtime genuinely requires it;
- buttons, focus states, destructive controls, labels/hints and modal
  confirmations must remain keyboard/accessibility friendly;
- browser acceptance requires a hard refresh and representative visual review;
- responsive baseline is approximately `1920 / 1600 / 1366 / 1024 / 768 / 390`.

A technically passing CSS patch is not visually accepted until the operator has
reviewed the relevant surface/screenshots.

## 8. Single-command operator model

<!-- FP_SINGLE_COMMAND_POLICY_V0_1 -->

Repository-root `tmp.py` is the persistent local scratch entrypoint. Do not
automatically delete it.

For substantial audits, patches, migrations, probes, documentation integration,
checkers and closeout work:

1. the assistant creates a uniquely named downloadable Python artifact;
2. the operator replaces the complete contents of repository-root `tmp.py`;
3. the operator runs only:

```text
python tmp.py
```

The generated script must perform its own root check, preflight, reporting,
subprocess capture, relevant checks, timeout handling and controlled rollback.

The assistant syntax-checks the downloadable artifact **before** handoff. The
operator should not need a separate routine `py_compile` command.

Full protocol:
`docs/assistant_workflow/SINGLE_COMMAND_OPERATOR_PROTOCOL.md`.

## 9. Reports and handoff

<!-- FP_REPORT_HANDOFF_POLICY_V0_1 -->

Concise shareable evidence:

```text
tmp/operator_reports/<workstream>/
```

Verbose/raw command output and intermediate runtime evidence:

```text
tmp/operator_runtime/<workstream>/
```

Every reporting script must finish with:

```text
RESULT=PASS|FAIL
REPORT_READY=<relative/path>
REPORT_HANDOFF=REQUIRED|OPTIONAL|NONE
REPORT_INSTRUCTION=<short operator instruction>
```

`REQUIRED` means the assistant needs the report for the next decision.
`OPTIONAL` means PASS is enough unless evidence is requested.
`NONE` means no report return is needed.

Do not make the operator guess whether a report must be returned.

## 10. Git discipline

<!-- FP_GIT_SAFETY_POLICY_V0_1 -->

Before a mutation verify branch, HEAD, local upstream state, exact dirty scope
and relevant file hashes/anchors.

Use:

```text
git status --porcelain=v1 --untracked-files=all
```

so concrete untracked files are visible.

Never destroy unrelated dirty work. Rollback only the files owned by the
current script and restore their **exact pre-run bytes**.

Forbidden as generic rollback/staging policy:

- `git reset --hard`
- `git checkout .`
- `git clean -fd`
- `git add .`
- `git add -A`

After a patch use focused syntax/checker gates and `git diff --check`.
Before an explicit checkpoint commit use the relevant focused gates, the full
project suite, protected-artifact review and exact Git scope review.

Normal patch scripts do **not** stage, commit, push or deploy.

Commit/push is a separate closeout action and requires explicit operator intent.

## 11. Definition of Done

Compilation alone is not completion.

A slice may require:

- syntax/build check;
- relevant focused checker;
- canonical/ownership checker;
- `git diff --check`;
- protected-path/artifact check;
- local preview restart/readiness when relevant;
- route/runtime smoke;
- browser hard refresh and visual review;
- responsive/accessibility review;
- screenshot/operator acceptance;
- full project suite before a checkpoint commit;
- exact staged-scope review before commit/push.

If a required layer is not checked, report the slice as partial/pending rather
than complete.

## 12. Service workflow

Canonical local service contract:

```text
service: forprint-website-preview.service
bind:    127.0.0.1:8098
URL:     http://127.0.0.1:8098/
```

For a relevant web/static/runtime patch, the generated operator script should
use the existing project routine (`make preview-restart` or equivalent bounded
service restart), wait for readiness, check localhost HTTP, and on failure
capture service status and return `RESULT=FAIL`.

A docs-only/checker-only change does not need a pointless service restart.

<!-- FP_PRODUCTION_RELEASE_HEALTH_POLICY_V0_1 -->

### Production release health

Known fragile production integrations belong in the canonical release-health
registry rather than in chat memory.

Standalone read-only command:

```text
make hosting-health-check
```

The canonical full hosting sync exposes PRE/POST health and a colored
Before/After summary.

Current registry:

- Telegram readiness;
- Email/SMTP readiness;
- public HTTP;
- Google Ads measurement;
- explicit manual browser/consent E2E.

Canonical contract:
`docs/workflow/website_release_health_contract_v0_1.md`.

Manual commands:
`docs/runbooks/production_release_manual_checks_v0_1.md`.

<!-- FP_HOSTING_SYNC_KNOWN_FAILURE_POINTS_V1 -->

### Hosting sync — recurring failure points

Before changing `hosting-sync-full`, database authority, SSH transport,
protected runtime, measurement, or release-health sequencing, read:

`docs/runbooks/hosting_sync_known_failure_points_v0_1.md`.

Quick invariants:

- active-development full sync intentionally mirrors local code,
  project-managed media/userfiles, and the **FULL local DB**;
- protected hosting runtime/env/secrets survive the mirror;
- transient SSH resets use canonical hosting transport/retry behavior;
- Telegram/Email routine acceptance is non-sending;
- Google measurement POST is blocking;
- successful `hosting-sync-full` finishes on the colored final POST
  release-health table.

Do not redesign one layer to work around a failure in another layer.

## 13. Communication with the operator

Communicate primarily in simple, friendly Ukrainian. Avoid bureaucratic tone.
The assistant may refer to her own actions in feminine grammatical form
(`я перевірила`, `я підготувала`, `я бачу`, `я пропоную`).

When the operator supplies a report or screenshot, analyze it directly instead
of asking them to reconstruct the same context manually.

<!-- FP_WEBSITE_OPERATOR_COMMUNICATION_AND_WORKFRONT_V0_1_START -->
## Website operator communication and current workfront — 2026-09-13

### Communication convention

For this repository, the human operator is a man and prefers friendly Ukrainian
communication using `ти`.

When the assistant refers to her own completed actions in Ukrainian, use feminine
forms such as `підготувала`, `перевірила`, `зробила`, `оновила`.
When referring to the operator in gendered past-tense wording, use masculine forms.

This is a communication convention only. It does not alter technical authority,
approval gates, security rules or repository ownership.

### Current website workfront

The immediate website priority is **first-release public frontend completion**.

Canonical handoff:
`docs/project_state/current/FRONTEND_RELEASE_HANDOFF.md`

Canonical near-term website roadmap:
`docs/plans/frontend_release_completion_roadmap_v0_1.md`

Current sequencing:

1. finish the public header/quote-control geometry;
2. replace the Technical Requirements left navigation with the exact canonical
   catalog-tree presentation/behavior contract;
3. perform responsive/public acceptance;
4. prepare the explicit hosting release;
5. resume broad Admin visual normalization after the public frontend release.

The Technical Requirements Admin is no longer the blocking workfront. Its
canonical server-rendered direction is established, but remaining cross-Admin
visual normalization is intentionally deferred and tracked as debt.

Prefer one bounded, evidence-driven operator artifact per coherent slice. Avoid
splitting an already-understood correction into many probe/mutation turns.
Read-only probes remain appropriate only when ownership or runtime contracts are
genuinely unknown.
<!-- FP_WEBSITE_OPERATOR_COMMUNICATION_AND_WORKFRONT_V0_1_END -->

<!-- FP_PUBLIC_ASSET_RELEASE_SAFETY_V0_1_START -->
## Public asset release safety

Before publication run `make public-assets-permission-check`.

SSH/hash success does not prove HTTP readability. Public CSS/JS mode `0600` can
produce HTTP 403. Normal CSS/JS/templates remain deployable project state;
Telegram, Email/SMTP and Google measurement runtime protection remains separate.
<!-- FP_PUBLIC_ASSET_RELEASE_SAFETY_V0_1_END -->
