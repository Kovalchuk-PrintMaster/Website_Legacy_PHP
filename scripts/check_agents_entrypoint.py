#!/usr/bin/env python3
"""Semantic integrity checker for the repository AI/operator entrypoint."""

from __future__ import annotations

import re
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]

REQUIRED_FILES = [
    "AGENTS.md",
    "docs/project_state/current/CURRENT_PROJECT_STATE.md",
    "docs/assistant_workflow/SINGLE_COMMAND_OPERATOR_PROTOCOL.md",
    "scripts/operator_script_support.py",
]

AGENTS_MARKERS = [
    "FP_AGENTS_ENTRYPOINT_V0_1",
    "FP_CURRENT_CHECKPOINT_POLICY_V0_1",
    "FP_PROTECTED_BOUNDARY_POLICY_V0_1",
    "FP_ADMIN_UI_POLICY_V0_1",
    "FP_SINGLE_COMMAND_POLICY_V0_1",
    "FP_REPORT_HANDOFF_POLICY_V0_1",
    "FP_GIT_SAFETY_POLICY_V0_1",
]

PROTOCOL_MARKERS = [
    "FP_SINGLE_COMMAND_OPERATOR_PROTOCOL_V0_1",
    "FP_REPORT_HANDOFF_PROTOCOL_V0_1",
    "FP_EXACT_BYTE_ROLLBACK_V0_1",
]


def fail(message: str) -> None:
    print(f"[FAIL] {message}", file=sys.stderr)


def main() -> int:
    errors: list[str] = []

    for relative in REQUIRED_FILES:
        if not (ROOT / relative).is_file():
            errors.append(f"missing required file: {relative}")

    if errors:
        for item in errors:
            fail(item)
        return 1

    agents = (ROOT / "AGENTS.md").read_text(encoding="utf-8")
    state = (ROOT / "docs/project_state/current/CURRENT_PROJECT_STATE.md").read_text(encoding="utf-8")
    protocol = (ROOT / "docs/assistant_workflow/SINGLE_COMMAND_OPERATOR_PROTOCOL.md").read_text(encoding="utf-8")
    helper = (ROOT / "scripts/operator_script_support.py").read_text(encoding="utf-8")
    readme = (ROOT / "README.md").read_text(encoding="utf-8")
    docs_readme = (ROOT / "docs/README.md").read_text(encoding="utf-8")
    workflow = (ROOT / "docs/workflow/operator_assistant_workflow_v0_1.md").read_text(encoding="utf-8")
    tmp_legacy = (ROOT / "docs/workflow/tmp_php_tmp_py_protocol_v0_1.md").read_text(encoding="utf-8")
    plan = (ROOT / "docs/plans/admin_ui_modernization_plan_v0_2.md").read_text(encoding="utf-8")
    makefile = (ROOT / "Makefile").read_text(encoding="utf-8")

    for marker in AGENTS_MARKERS:
        if marker not in agents:
            errors.append(f"AGENTS.md missing semantic marker: {marker}")

    for marker in PROTOCOL_MARKERS:
        if marker not in protocol:
            errors.append(f"single-command protocol missing marker: {marker}")

    required_agents_terms = [
        "python tmp.py",
        "git status --porcelain=v1 --untracked-files=all",
        "git reset --hard",
        "git clean -fd",
        "git add -A",
        "REPORT_HANDOFF=REQUIRED|OPTIONAL|NONE",
        "forprint-website-preview.service",
        "127.0.0.1:8098",
        "main.css",
        "!important",
    ]
    for term in required_agents_terms:
        if term not in agents:
            errors.append(f"AGENTS.md missing required contract term: {term}")

    if "FP_CURRENT_PROJECT_STATE_V0_1" not in state:
        errors.append("current-state document missing semantic marker")
    if "Phase 8" not in state or "9dc6d12cb12c12007c42d83c760b2479819a6506" not in state:
        errors.append("current-state document missing checkpoint identity")

    required_protocol_terms = [
        "python tmp.py",
        "tmp/operator_reports/<workstream>/",
        "tmp/operator_runtime/<workstream>/",
        "REPORT_READY=",
        "REPORT_HANDOFF=REQUIRED|OPTIONAL|NONE",
        "git status --porcelain=v1 --untracked-files=all",
        "exact original bytes",
        "make check",
    ]
    for term in required_protocol_terms:
        if term not in protocol:
            errors.append(f"single-command protocol missing contract term: {term}")

    helper_terms = [
        "class OperatorScriptSession",
        "git_status_lines",
        "--untracked-files=all",
        "snapshot_owned",
        "rollback_owned",
        "REPORT_READY=",
    ]
    for term in helper_terms:
        if term not in helper:
            errors.append(f"operator helper missing support contract: {term}")

    reference_phrase = "Primary assistant entrypoint: root `AGENTS.md`"
    if reference_phrase not in readme:
        errors.append("root README missing primary assistant entrypoint reference")
    if reference_phrase not in docs_readme:
        errors.append("docs README missing primary assistant entrypoint reference")

    if "AGENTS.md" not in workflow or "SINGLE_COMMAND_OPERATOR_PROTOCOL.md" not in workflow:
        errors.append("operator workflow does not delegate to new canonical entrypoint/protocol")

    if "**Статус:** superseded" not in tmp_legacy or "SINGLE_COMMAND_OPERATOR_PROTOCOL.md" not in tmp_legacy:
        errors.append("legacy tmp protocol is not explicitly superseded")

    if "CURRENT_PROJECT_STATE.md" not in plan:
        errors.append("active admin roadmap missing live current-state reference")

    if "AGENTS_CHECK_TOOL := scripts/check_agents_entrypoint.py" not in makefile:
        errors.append("Makefile missing AGENTS checker tool registration")
    if not re.search(r"^agents-entrypoint-check:\s*$", makefile, flags=re.M):
        errors.append("Makefile missing agents-entrypoint-check target")
    check_line = re.search(r"^check:\s*(.+)$", makefile, flags=re.M)
    if not check_line or "agents-entrypoint-check" not in check_line.group(1).split():
        errors.append("default/full make check does not include agents-entrypoint-check")

    protected_terms = [
        "base/config.php",
        "base/userfiles/",
        "database_dumps/",
        "production deployment",
    ]
    for term in protected_terms:
        if term not in agents:
            errors.append(f"AGENTS.md missing protected-boundary term: {term}")

    if errors:
        for item in errors:
            fail(item)
        return 1

    print("[OK] AGENTS.md and single-command operator entrypoint contract")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
