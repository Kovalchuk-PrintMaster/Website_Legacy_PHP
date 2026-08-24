# ForPrint admin UI visual refinement entry state — 2026-08-24

**ID:** `FP-WEB-STATUS-ADMIN-2026-08-24-002`
**Version:** `v0.1`
**Date:** `2026-08-24`
**Status:** `historical snapshot`

## Purpose

This snapshot is the context-recovery point between the completed structural
admin modernization stage and the new visual-refinement stage.

A new assistant can use it to resume work after a context-window reset without
reconstructing the entire Phase 1–7 history.

## Git checkpoint

```text
repository:
/srv/software_development/forprint-project/forprint_website

branch:
main

accepted structural commit:
eb5f0a314f633ab0a7f33af529e7b9f0072ae26c

origin/main:
eb5f0a314f633ab0a7f33af529e7b9f0072ae26c
```

Commit subject:

```text
admin: complete structural UI modernization through phase 7
```

Production deployment of this admin modernization was not performed as part of
the structural closure.

## Stage state

```text
ADMIN_UI_STRUCTURAL_MODERNIZATION = COMPLETE
ADMIN_UI_VISUAL_REFINEMENT = ACTIVE
CURRENT_PHASE = 8
```

Phase 8 is intentionally treated as the second large phase of admin work:
stylistic convergence, visual rhythm and UI polish on top of the stabilized
ownership/runtime foundation.

## Current visual direction

The project owner wants the admin to become:

- light;
- friendly;
- block-based;
- consistently rounded;
- readable;
- spatially efficient;
- visually calm.

The main observed Goods issues at this checkpoint:

- Save and Delete do not yet look like one control family;
- some blocks have good rounded modern surfaces while others retain square or
  legacy-looking presentation;
- equivalent vertical gaps are inconsistent;
- some labels/hints became too small;
- filter count `16` is too visually aggressive;
- two-column composition is useful and should remain the default;
- compact data may use three/four columns when appropriate;
- image actions should occupy less space so the preview can dominate;
- large sections may later become collapsible, default-open.

Media Processing is considered a useful composition reference, especially for
clear labelled blocks, but not a literal template.

## Canonical visual ownership

Read:

```text
docs/reference/admin_ui_visual_refinement_contract_v0_1.md
docs/plans/admin_ui_modernization_plan_v0_2.md
docs/decisions/2026-08-23__canonical_admin_css_ownership_and_migration_order.md
```

Key owners:

```text
forprint-admin.css              shared tokens/primitives/actions/fields
forprint-admin-goods-form.css   Goods-specific layout/presentation
forprint-admin-gallery.css      gallery presentation
forprint-admin-ordering.css     ordering/save-state presentation
main.css                        legacy fallback only
```

Do not create another generic admin stylesheet.

## Latest Phase 8 evidence

```text
121_phase8_goods_visual_system_baseline_audit_20260824_1756.md
122_phase8_goods_visual_contract_exact_owner_resolver_20260824_1802.md
```

Resolver 122 is the immediate implementation authority for the first visual
slice.

It proved the existing semantic classes are already sufficient for:

- Save/Delete;
- action bars;
- field/card surfaces;
- label/hint typography;
- image upload/delete actions.

No PHP markup mutation is currently required for that first slice.

## Exact next action

```text
Build one bounded Phase 8 visual patch that edits canonical current rules:
1. shared design tokens;
2. shared Save/Delete geometry;
3. shared field/card surface;
4. shared label/hint typography;
5. shared spacing rhythm;
6. Goods neutral filter-count badge;
7. compact shared image actions.
```

Then:

```text
lint/diff/smoke
→ screenshot Goods top
→ refine same canonical rules if needed
→ accept
→ continue downward
```

Do not begin broad cross-admin restyling before the first Goods slice is
visually accepted.

## Collaboration style

Communication is deliberately simple, friendly and informal-professional.

The assistant should:

- communicate primarily in Ukrainian;
- avoid bureaucratic or overly formal wording;
- explain the next action plainly;
- refer to her own actions in the feminine grammatical form, for example:
  `я перевірила`, `я підготувала`, `я бачу`, `я пропоную`;
- analyze pasted reports directly instead of asking the project owner to
  restate them;
- treat screenshots and the owner's visual observations as acceptance evidence;
- ask clarifying questions only when the evidence is genuinely insufficient;
- keep terminal commands short and practical.

The project owner often supplies:

```text
screenshots
stdout
markdown reports
git/diff output
visual observations
```

The assistant normally responds by turning those observations into a bounded
audit, exact patch or next acceptance step.

## Script and report protocol

For nontrivial work:

```text
unique Python filename
→ run from repository root
→ tee stdout to tmp/report.txt
→ report under tmp/admin_refactor/
→ user pastes report
→ assistant analyses it
```

Never reuse a generated script filename. This avoids browser/client cache
confusion and accidental reruns of already-applied mutation installers.

Mutation scripts should:

- fail closed;
- guard current state;
- validate the transform in memory where practical;
- rollback their own writes on post-write failure;
- avoid production/DB mutation unless explicitly in scope.

## Visual iteration protocol

Work top-to-bottom.

For a visible group:

```text
observe screenshot
→ identify shared vs surface-specific concept
→ resolve exact owner
→ patch canonical rule
→ local validation
→ new screenshot
→ accept/refine
```

Do not create a new override block every time a screenshot changes. Merge the
accepted result into the existing canonical owner.

## Frozen / deferred boundaries

Do not reopen casually:

- backend field names;
- generic CRUD semantics;
- authentication/routing;
- TinyMCE data contract;
- gallery upload/FileList legacy compatibility;
- broad `main.css` retirement;
- footer bootstrap migration;
- isolated login runtime modernization;
- production deployment.

These can become separate projects later when justified.

## Dirty-worktree rule

The repository may contain unrelated dirty marketing, documentation, hosting or
operations work.

Never use:

```text
git add -A
git add .
```

Use exact staging after the visual slice is accepted.

## New-assistant startup checklist

A new assistant should report these before proposing the next mutation:

```text
1. branch and HEAD
2. origin/main state if push/release matters
3. whether the relevant admin files differ from HEAD
4. latest Phase 8 report read
5. current roadmap item
6. exact visual group currently under review
7. canonical owner(s)
8. forbidden/frozen mutation classes
```

If the checkpoint above still matches the repository, resume directly from the
first Phase 8 visual patch. Do not repeat the completed Phase 1–7 structural
program.
