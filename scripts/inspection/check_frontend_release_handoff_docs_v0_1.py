#!/usr/bin/env python3
from pathlib import Path
import sys

ROOT = Path(__file__).resolve().parents[2]

required = {
    "AGENTS.md": [
        "FP_WEBSITE_OPERATOR_COMMUNICATION_AND_WORKFRONT_V0_1_START",
        "FRONTEND_RELEASE_HANDOFF.md",
    ],
    "docs/project_state/current/CURRENT_PROJECT_STATE.md": [
        "FP_FRONTEND_RELEASE_CURRENT_STATE_V0_1_START",
        "frontend_release_completion_roadmap_v0_1.md",
    ],
    "docs/project_state/current/FRONTEND_RELEASE_HANDOFF.md": [
        "FR-01",
        "FR-02",
        "Deferred Admin workstream",
        "assistant uses feminine self-reference",
    ],
    "docs/plans/frontend_release_completion_roadmap_v0_1.md": [
        "FR-01",
        "FR-05",
        "AR-01",
        "Status:** active",
    ],
    "docs/status/snapshots/2026-09-13_frontend_technical_requirements_admin_working_state_v0_1.md": [
        "historical snapshot",
        "Defer broad Admin visual normalization",
    ],
    "docs/README.md": [
        "FP_FRONTEND_RELEASE_HANDOFF_INDEX_V0_1_START",
        "FRONTEND_RELEASE_HANDOFF.md",
    ],
}

failures = []

for rel, needles in required.items():
    path = ROOT / rel

    if not path.is_file():
        failures.append(f"missing: {rel}")
        continue

    text = path.read_text(encoding="utf-8", errors="replace")

    for needle in needles:
        if needle not in text:
            failures.append(f"{rel}: missing marker/text: {needle}")

optional_workflow = [
    (
        "docs/assistant_workflow/SINGLE_COMMAND_OPERATOR_PROTOCOL.md",
        "FP_SINGLE_COMMAND_BATCHING_V0_1_START",
    ),
    (
        "docs/workflow/operator_assistant_workflow_v0_1.md",
        "FP_OPERATOR_COMMUNICATION_CONVENTION_V0_1_START",
    ),
]

for rel, needle in optional_workflow:
    path = ROOT / rel
    if path.is_file():
        text = path.read_text(encoding="utf-8", errors="replace")
        if needle not in text:
            failures.append(f"{rel}: missing optional-current marker: {needle}")

if failures:
    print("FP_FRONTEND_RELEASE_HANDOFF_DOCS=FAIL")
    for failure in failures:
        print("- " + failure)
    sys.exit(2)

print("FP_FRONTEND_RELEASE_HANDOFF_DOCS=PASS")
