# ForPrint Website — CURRENT_PROJECT_STATE

<!-- FP_CURRENT_PROJECT_STATE_V0_1 -->

**Checkpoint date:** 2026-09-06
**Status:** living current state
**Repository:** `/srv/software_development/forprint-project/forprint_website`
**Branch:** `main`
**HEAD:** `9dc6d12cb12c12007c42d83c760b2479819a6506`
**Local origin/main at audit:** same commit; `0/0` divergence; no network fetch performed
**Primary assistant entrypoint:** root `AGENTS.md`

## Active workstream

`Admin UI modernization — Phase 8 visual refinement`

Phase 1–7 structural modernization is complete. Accepted structural checkpoint:

```text
eb5f0a314f633ab0a7f33af529e7b9f0072ae26c
admin: complete structural UI modernization through phase 7
```

The current Phase 8 work is **not yet a committed checkpoint**.

## Working-tree state

The 2026-09-05 entrypoint audit observed 89 porcelain entries across several
parallel domains. The worktree is intentionally broad and dirty.

Active Phase 8 files currently include, among others:

```text
base/core/admin/views/add.php
base/core/admin/views/css/forprint-admin.css
base/core/admin/views/css/forprint-admin-login.css
base/core/admin/views/login.php
base/core/admin/views/js/forprint-admin-footer-child-form.js
base/core/admin/views/js/forprint-admin-footer-collections.js
base/core/admin/views/js/forprint-admin-login.js
```

Other dirty public/marketing/hosting/documentation work is **not automatically
part of the admin slice** and must not be broad-staged, reset or normalized.

## Live production / growth override — 2026-09-06

The canonical production full sync completed successfully on 2026-09-06.

Accepted production health:

- Telegram: **PASS**;
- Email / SMTP: **PASS**;
- Public HTTP: **PASS**;
- Google Ads measurement: **PASS**;
- protected hosting runtime: **preserved**.

Current strategic development project:
**supplier catalog / partner channel**, starting locally with Totobi.

Search Console/fresh production search evidence remains P1 and runs in parallel;
it is not a blocker for local supplier-adapter development.

## Completed / accepted milestones

- repository/runtime/deployment foundation exists;
- local PHP preview is served by `forprint-website-preview.service`;
- admin structural modernization Phase 1–7 is complete;
- one canonical project-owned admin CSS ownership model is established;
- Goods is the practical visual-reference direction for shared card/control
  rhythm;
- Phase 8 has progressed through Goods and into System settings → Footer;
- Footer links/phones use a shared bounded child-form presentation contract in
  the current dirty tree;
- admin login password reveal exists in the current dirty tree.

These working-tree capabilities are not equivalent to a final Git checkpoint.

## Current UI direction

The admin should remain:

- light, calm and block-based;
- rounded with subtle borders/shadows;
- spatially efficient;
- consistent in shared field/control geometry;
- two-column by default where useful;
- accessible and responsive.

Canonical ownership remains:

```text
main.css                         legacy fallback only
forprint-admin.css               shared admin tokens/primitives; Footer shared owner
forprint-admin-goods-form.css    Goods-specific presentation
forprint-admin-gallery.css       gallery presentation
forprint-admin-ordering.css      ordering/save-state
forprint-admin-collections.css   bounded collection presentation
```

Do not add a new generic admin style owner.

## Current Footer/login state

Current working surfaces include:

```text
/admin/edit/footer_settings/1
/admin/add/footer_links
/admin/add/footer_phones
/admin/login
```

Recent evidence at the entrypoint audit:

```text
tmp/admin_refactor/20260905_1945_phase8_footer_child_geometry_exact_resolver_v01.md
tmp/admin_refactor/20260905_2018_phase8_footer_child_geometry_centering_patch_v01.md
```

The current browser direction is materially improved but Footer/child-form
visual closure is still active. Do not label the whole Footer stage complete.

The login reveal control is implemented in the working tree; it still belongs
to the uncommitted Phase 8 checkpoint until Git/acceptance closure.

## Known issues / debt

- broad dirty worktree makes exact scope control mandatory;
- legacy `main.css` remains substantial fallback debt;
- admin CSS still contains legacy-isolation priority declarations;
- duplicate/overlapping legacy geometry must be resolved structurally rather
  than hidden by endless override generations;
- current active plan and historical snapshots contain older entry points;
  `AGENTS.md` and this file override stale current-state wording;
- no project-wide optimistic-locking contract is documented;
- CSRF/mutation behavior must be resolved per route rather than assumed.
- production Google Ads measurement runtime is active and accepted; release-health verifies it before/after hosting updates;
- release-health registry now covers Telegram, Email/SMTP, public HTTP, Google Ads measurement and manual browser/consent E2E.

## Immediate next sequence

<!-- FP_CURRENT_EVENT_HORIZON_2026_09_06_V2 -->
1. Start the local supplier-catalog pilot with Totobi: Python feed adapter,
   normalized model and read-only inspection first.
2. In parallel, produce fresh production crawl/Search Console readiness evidence.
3. Do not publish supplier images/descriptions to production until commercial
   reuse permission is confirmed.
4. Resolve the existing ForPrint catalog/data owners before supplier DB writes.
5. After the first supplier backend slice, add a small local-only navigation
   entry/page for visual integration.
6. Resume Admin Phase 8 main Footer settings and responsive/accessibility closure
   without reopening accepted child forms.
7. Google Ads: diagnostics at +3–7 days; evidence review at +7–14 days; strategy
   review on/after 2026-10-06 or after meaningful valid conversion volume.
8. Keep Git closeout/commit/push separate and explicit.

No currently verified P0/fatal condition blocks local supplier-channel
development.
<!-- /FP_CURRENT_EVENT_HORIZON_2026_09_06_V2 -->

## Safety status

- production deployment: **not part of the current checkpoint**
- production DB mutation: **not authorized by this state**
- provider mutation/billing: **not authorized by this state**
- broad Git cleanup/reset: **forbidden**
- local preview service: active at audit, HTTP 200 on `127.0.0.1:8098`

<!-- FP_ACTIVE_DEVELOPMENT_HOSTING_MIRROR_STATE_V1 -->
## Active hosting authority mode — development mirror

Current lifecycle: **active local development**.

Until an explicit replacement decision is accepted:

- local application code, project-managed media/userfiles and the full local
  database are authoritative;
- production hosting is a disposable mirror for those application-owned parts;
- `make hosting-sync-full` intentionally replaces the hosting database from
  local;
- hosting runtime/environment/secrets/configuration remain hosting-owned and
  must survive sync;
- Telegram, Email/SMTP and Google measurement remain release-health concerns;
- do not introduce production operational-row preservation into the canonical
  full development sync merely because rows exist on hosting.

Decision:
`docs/decisions/2026-09-06__active_development_hosting_mirror_authority.md`.

Permanent check:
`make development-mirror-policy-check`.
<!-- /FP_ACTIVE_DEVELOPMENT_HOSTING_MIRROR_STATE_V1 -->

<!-- FP_MEASUREMENT_BROWSER_ACCEPTED_2026_09_06 -->
## Production measurement browser acceptance — 2026-09-06

Server/release-health acceptance:

```text
Telegram readiness       PASS
Email / SMTP readiness   PASS
Public HTTP              PASS
Google Ads measurement   PASS
```

Browser consent acceptance confirmed:

```text
enabled=true
testMode=false
consent=granted
ready=true
granted=true
gtag=function
loader=true
```

No real lead conversion was fired during this checkpoint.

Consent affirmative copy is standardized to `Прийняти cookies`; detailed
measurement disclosure remains in the privacy banner body.

Canonical sync troubleshooting:
`docs/runbooks/hosting_sync_known_failure_points_v0_1.md`.
<!-- /FP_MEASUREMENT_BROWSER_ACCEPTED_2026_09_06 -->

<!-- FP_GOOGLE_ADS_WORKSTREAM_CLOSED_2026_09_06 -->
## Google Ads workstream — closed 2026-09-06

Current status: **closed / reopen only on explicit trigger**.

Accepted state:

- production Google measurement runtime: **active**;
- Google tag `AW-959055246`: **active**;
- conversion destination `AW-959055246/3ccOCP6mntocEI6LqMkD`: **active**;
- browser consent -> loader acceptance: **PASS**;
- release-health Google measurement: **PASS**;
- latest canonical full hosting sync: **PASS**;
- real lead-conversion E2E: **not fired during this closeout**;
- campaign bidding baseline: **Maximize clicks** until a later explicit Ads
  review has valid conversion evidence.

Do not automatically resume Ads optimization, change bidding/network/budget,
or infer account-side Editor/Post state that was not explicitly confirmed.

Closeout evidence:
`marketing/reports/2026-09-06__google_ads_workstream_closeout_v0_1.md`.
<!-- /FP_GOOGLE_ADS_WORKSTREAM_CLOSED_2026_09_06 -->

<!-- FP_FRONTEND_RELEASE_CURRENT_STATE_V0_1_START -->
## Current public-frontend release state — 2026-09-13

Read first:
`docs/project_state/current/FRONTEND_RELEASE_HANDOFF.md`

Execution roadmap:
`docs/plans/frontend_release_completion_roadmap_v0_1.md`

Status summary:

- local preview remains the development source of truth;
- production/hosting remains a controlled mirror and is not mutated without an
  explicit release instruction;
- Technical Requirements public routes exist and return successfully locally;
- Technical Requirements Admin has been moved toward the canonical
  server-rendered Admin lifecycle and is no longer the immediate release blocker;
- broad Admin visual normalization is deferred until the public frontend is
  release-ready;
- current blocking public work is header/quote-control cleanup plus exact
  catalog-tree parity for the Technical Requirements left rail;
- after those fixes, run responsive/public acceptance and then the normal release
  health + hosting mirror workflow.

Do not restart broad Admin redesign before the frontend release blockers above are
closed unless the operator explicitly changes priority.
<!-- FP_FRONTEND_RELEASE_CURRENT_STATE_V0_1_END -->

<!-- FP_PUBLIC_ASSET_RELEASE_CURRENT_STATE_V0_1_START -->
## Public asset release safety — 2026-09-17

Production CSS HTTP 403 was recovered by normalizing public CSS from `0600` to
`0644` and rerunning canonical full sync. Preventive hardening also found
`forprint-technical-requirements.js` at `0600`.

Permanent guard: `make public-assets-permission-check`.
<!-- FP_PUBLIC_ASSET_RELEASE_CURRENT_STATE_V0_1_END -->
