# Політика документації v0.1

**ID:** `FP-WEB-DOC-001`
**Статус:** active

## Типи

| Type | Directory |
|---|---|
| Architecture | `architecture/` |
| Workflow | `workflow/` |
| Snapshot | `status/snapshots/` |
| Plan | `plans/` |
| Decision | `decisions/` |
| Reference | `reference/` |
| Feature note | `development/` |
| Readiness | `launch_readiness/` |
| Evidence | `coordination/reports/` |

## Naming

```text
descriptive_name_v0_1.md
YYYY-MM-DD_development_state_v0_1.md
YYYY_MM_DD_feature_name_v0_1.sql
```

## Metadata

Назва, ID, version, date, status і за потреби `supersedes`.

## Statuses

`draft`, `active`, `accepted`, `planned`, `completed`, `superseded`, `historical snapshot`.

## Незмінність

Historical snapshot і completed plan не переписуються під новий стан. Створюється новий file. Дозволені лише очевидні corrections із приміткою.

## Mutable indexes

Section `README.md` може оновлюватися як index, але не повинен містити єдину копію critical decision.

## Не дублювати

- architecture пояснює модель;
- snapshot фіксує факт;
- plan задає next steps;
- report підтверджує виконання.

Новий великий documentation pack потрібен перед release, після major architecture change або після stabilization, але не після кожного CSS fix.

<!-- FP-FRONTEND-DOCS-V02-START -->
## Frontend package v0.2 application note

The 2026-07-20 frontend architecture checkpoint adds new versioned canonical documents rather than rewriting the historical v0.1/v0.2 records.

Supersession is explicit:

- `frontend_css_ownership_and_layout_strategy_v0_3.md` supersedes the v0.2 strategy;
- the earlier document remains historical evidence;
- status snapshots are date-bound and are not edited into later states;
- package manifests are versioned independently;
- bounded index-marker blocks may be updated idempotently without deleting unrelated index content.
<!-- FP-FRONTEND-DOCS-V02-END -->


<!-- FP_CURRENT_STATE_FIRST_POLICY_V0_1 -->
## Current-state-first rule

Active architecture, workflow, reference and index documents describe the
current accepted project state. They must not retain obsolete implementation
or ownership assumptions merely because those assumptions were documented
earlier.

```text
same architecture + newer facts
    -> update the active document in place

material architecture / ownership / contract change
    -> create a new versioned canonical document
    -> mark the previous document superseded
    -> remove it from the current reading path

snapshot / completed plan / incident / coordination evidence
    -> preserve as historical evidence
    -> do not rewrite it into current state
```

Superseded documents remain available for historical traceability but are not
canonical instructions for current implementation or operations.

When documentation and implementation disagree, code, schema and effective
runtime configuration remain the factual authority. Update or supersede the
affected active documentation.

Repository indexes such as `docs/README.md` must prefer the latest canonical
documents over historical predecessors.

<!-- FP_LIVING_DOCUMENTATION_LIFECYCLE_V0_1_START -->
## Living documentation lifecycle

The repository optimizes for a correct current model, not for preserving every
old wording as active documentation.

### Document lifecycle classes

`living_current`
: Architecture, workflow, policy, runbook and reference documents that explain
  the current accepted system.

`mutable_index`
: README/index/register documents whose purpose is to point to current
  canonical material.

`decision_record`
: ADRs. The historical decision body is preserved; status and explicit
  supersession links may be updated. A materially different decision gets a new
  ADR.

`historical_evidence`
: Snapshots, incidents, completed plans, release evidence and coordination
  reports. These remain date-bound and are not rewritten into current state.

`transitional_compatibility`
: Old material retained only because a real consumer, migration, compatibility
  boundary or active procedure still needs it.

### Update-versus-revision rule

```text
same accepted architecture / ownership / contract
+ newer facts, corrections or clearer explanation
    -> update the living document in place
    -> substantial editorial rewrite is allowed

material architecture / ownership / contract / lifecycle change
    -> create a newer canonical revision
    -> mark the predecessor superseded
    -> update indexes, registries and dependent procedures

historical snapshot / incident / completed plan / release evidence
    -> preserve as historical evidence
    -> create new evidence for the new state

obsolete material with a real compatibility consumer
    -> mark transitional
    -> record compatibility reason and retirement condition

obsolete material with no current, historical or compatibility value
    -> remove it from the active documentation tree after references are
       migrated; Git history remains the audit trail
```

A higher revision number is not automatically more correct. Canonical status
comes from the current documentation registry and current-state indexes.

### No zombie documentation

Every durable document should have an explainable lifecycle role.

An obsolete document must not remain ambiguously `active` merely because it
exists or because an older procedure once linked to it.

Old material retained for compatibility must define:

- the current consumer or reason;
- the canonical replacement when one exists;
- the retirement condition or next review trigger.

### Freshness is event-driven

Freshness is not inferred only from file modification time.

Living current documents are reviewed when their facts or contracts may have
changed, including:

- architecture/ownership changes;
- runtime or deployment contract changes;
- API/provider integration changes;
- schema/control-plane changes;
- path/repository-layout changes;
- release/stabilization checkpoints;
- migration completion;
- discovery that implementation and documentation disagree.

A periodic review may supplement these triggers, but does not replace them.

### Authority and conflict rule

When an active document conflicts with verified implementation facts, schema,
effective runtime configuration or a newer accepted ADR, the active document
is stale until it is updated or superseded.

An older agreement does not override a newer verified current state merely
because the older text is more detailed.

### Reference migration

When a canonical document is superseded:

1. identify inbound references;
2. move normative/current references to the successor;
3. retain historical references only where history is intentional;
4. update README/index/current-document registry;
5. validate that the predecessor is no longer on the current reading path.

Compatibility is explicit; it is never inferred from a stale link.
<!-- FP_LIVING_DOCUMENTATION_LIFECYCLE_V0_1_END -->
