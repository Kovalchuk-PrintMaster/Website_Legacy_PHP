#!/usr/bin/env python3
"""ForPrint production release health checker.

Read-only production acceptance for known fragile release capabilities.

The checker:
- reuses the existing non-sending communication acceptance;
- checks key public HTTP routes;
- checks the server-rendered Google Ads measurement configuration;
- prints a colored terminal table;
- persists machine-readable evidence;
- links to the manual browser/provider runbook.

It never posts a real enquiry, sends a real message, changes production,
changes the database, or changes Google Ads.
"""

from __future__ import annotations

import argparse
import datetime as dt
import json
import os
import re
import subprocess
import sys
import urllib.error
import urllib.request
from dataclasses import asdict, dataclass
from pathlib import Path
from typing import Iterable

ROOT = Path("/srv/software_development/forprint-project/forprint_website")
PRODUCTION_ORIGIN = "https://forprint.net.ua"
EXPECTED_GOOGLE_TAG_ID = "AW-959055246"
EXPECTED_CONVERSION_DESTINATION = "AW-959055246/3ccOCP6mntocEI6LqMkD"

MANUAL_RUNBOOK = (
    ROOT / "docs/runbooks/production_release_manual_checks_v0_1.md"
)
REPORT_ROOT = ROOT / "tmp/operator_reports/hosting_health"
RUNTIME_ROOT = ROOT / "tmp/operator_runtime/hosting_health"
LATEST_PRE = RUNTIME_ROOT / "latest_pre.json"
LATEST_POST = RUNTIME_ROOT / "latest_post.json"

HTTP_PATHS = ("/", "/catalog/", "/contacts/", "/search/")


@dataclass
class CheckResult:
    key: str
    name: str
    status: str
    gate: str
    detail: str
    phase: str


def color_enabled() -> bool:
    return sys.stdout.isatty() and not os.environ.get("NO_COLOR")


def color(text: str, code: str) -> str:
    if not color_enabled():
        return text
    return f"\033[{code}m{text}\033[0m"


def status_visual(status: str) -> str:
    return {
        "PASS": color("✓ PASS", "1;32"),
        "WARN": color("⚠ WARN", "1;33"),
        "FAIL": color("✗ FAIL", "1;31"),
        "MANUAL": color("• MANUAL", "1;36"),
        "—": color("—", "2"),
    }.get(status, status)


def terminal_link(label: str, path: Path) -> str:
    absolute = path.resolve()
    if sys.stdout.isatty() and os.environ.get("TERM", "") != "dumb":
        return (
            f"\033]8;;{absolute.as_uri()}\033\\"
            f"{label}"
            f"\033]8;;\033\\"
            f" ({absolute})"
        )
    return f"{label}: {absolute}"


def ensure_dirs() -> None:
    REPORT_ROOT.mkdir(parents=True, exist_ok=True)
    RUNTIME_ROOT.mkdir(parents=True, exist_ok=True)


def new_report_dir(phase: str) -> Path:
    ensure_dirs()
    stamp = dt.datetime.now().strftime("%Y%m%d_%H%M%S_%f")
    path = REPORT_ROOT / f"{stamp}_{phase}"
    path.mkdir(parents=True, exist_ok=False)
    return path


def fetch(path: str) -> tuple[int, str]:
    request = urllib.request.Request(
        PRODUCTION_ORIGIN + path,
        headers={
            "User-Agent": "ForPrint-release-health/1.0",
            "Cache-Control": "no-cache",
            "Pragma": "no-cache",
        },
    )
    try:
        with urllib.request.urlopen(request, timeout=30) as response:
            return (
                int(response.status),
                response.read(2_000_000).decode("utf-8", errors="replace"),
            )
    except urllib.error.HTTPError as exc:
        return (
            int(exc.code),
            exc.read(200_000).decode("utf-8", errors="replace"),
        )
    except Exception as exc:
        return 0, f"{type(exc).__name__}: {exc}"


def communication_results(
    report_dir: Path,
    phase: str,
) -> list[CheckResult]:
    result = subprocess.run(
        ["make", "--no-print-directory", "hosting-communication-check"],
        cwd=ROOT,
        text=True,
        stdout=subprocess.PIPE,
        stderr=subprocess.STDOUT,
        check=False,
        timeout=180,
    )
    output = result.stdout or ""
    raw = report_dir / "communication_acceptance.log"
    raw.write_text(output, encoding="utf-8")
    communication_meta = report_dir / "communication_acceptance.meta.json"
    communication_meta.write_text(
        json.dumps(
            {
                "health_run_id": report_dir.name,
                "phase": phase,
                "checker_return_code": result.returncode,
                "evidence_path": str(raw.relative_to(ROOT)),
                "secret_values_recorded": False,
            },
            ensure_ascii=False,
            indent=2,
        )
        + "\\n",
        encoding="utf-8",
    )

    if result.returncode == 0:
        status = "PASS"
        detail = (
            "existing non-sending acceptance passed; "
            "SMTP + Telegram readiness predicates covered"
        )
    else:
        status = "FAIL"
        detail = (
            "combined non-sending communication acceptance failed; "
            "diagnose exact run: make hosting-health-diagnose "
            f"HEALTH_REPORT={report_dir.relative_to(ROOT)}; "
            f"see {raw.relative_to(ROOT)}"
        )

    return [
        CheckResult(
            "telegram",
            "Telegram readiness",
            status,
            "BLOCKING",
            detail,
            phase,
        ),
        CheckResult(
            "email",
            "Email / SMTP readiness",
            status,
            "BLOCKING",
            detail,
            phase,
        ),
    ]


def http_result(phase: str) -> CheckResult:
    failures = []
    statuses = []
    for path in HTTP_PATHS:
        status, _body = fetch(path)
        statuses.append(f"{path}={status}")
        if status != 200:
            failures.append(path)

    return CheckResult(
        "http",
        "Public HTTP",
        "FAIL" if failures else "PASS",
        "BLOCKING",
        ", ".join(statuses),
        phase,
    )


def parse_measurement_config(html_text: str) -> dict:
    match = re.search(
        r"window\.ForPrintMeasurementConfig\s*=\s*Object\.freeze\s*"
        r"\(\s*(\{.*?\})\s*\)\s*;",
        html_text,
        flags=re.S,
    )
    if not match:
        raise ValueError(
            "ForPrintMeasurementConfig not found in production HTML"
        )

    payload = json.loads(match.group(1))
    if not isinstance(payload, dict):
        raise ValueError(
            "ForPrintMeasurementConfig is not a JSON object"
        )
    return payload


def measurement_result(phase: str) -> CheckResult:
    http_status, html_text = fetch("/")
    is_pre = phase == "pre"

    if http_status != 200:
        return CheckResult(
            "measurement",
            "Google Ads measurement",
            "WARN" if is_pre else "FAIL",
            "ADVISORY" if is_pre else "BLOCKING",
            f"homepage HTTP {http_status}; measurement config unavailable",
            phase,
        )

    try:
        config = parse_measurement_config(html_text)
    except Exception as exc:
        return CheckResult(
            "measurement",
            "Google Ads measurement",
            "WARN" if is_pre else "FAIL",
            "ADVISORY" if is_pre else "BLOCKING",
            f"{type(exc).__name__}: {exc}",
            phase,
        )

    expected = {
        "enabled": True,
        "testMode": False,
        "provider": "google-tag",
        "googleTagId": EXPECTED_GOOGLE_TAG_ID,
        "conversionDestination": EXPECTED_CONVERSION_DESTINATION,
    }
    mismatches = [
        f"{key}={config.get(key)!r}"
        for key, expected_value in expected.items()
        if config.get(key) != expected_value
    ]

    if mismatches:
        return CheckResult(
            "measurement",
            "Google Ads measurement",
            "WARN" if is_pre else "FAIL",
            "ADVISORY" if is_pre else "BLOCKING",
            "expected production config not active: "
            + ", ".join(mismatches),
            phase,
        )

    return CheckResult(
        "measurement",
        "Google Ads measurement",
        "PASS",
        "ADVISORY" if is_pre else "BLOCKING",
        (
            f"{EXPECTED_GOOGLE_TAG_ID}; "
            "conversion destination active"
        ),
        phase,
    )


def manual_result(phase: str) -> CheckResult:
    return CheckResult(
        "browser_e2e",
        "Browser / consent E2E",
        "MANUAL",
        "MANUAL",
        (
            "consent + loader + gtag + controlled lead test "
            "when explicitly approved"
        ),
        phase,
    )


def collect(report_dir: Path, phase: str) -> list[CheckResult]:
    results: list[CheckResult] = []

    try:
        results.extend(
            communication_results(report_dir, phase)
        )
    except Exception as exc:
        detail = f"{type(exc).__name__}: {exc}"
        results.extend(
            [
                CheckResult(
                    "telegram",
                    "Telegram readiness",
                    "FAIL",
                    "BLOCKING",
                    detail,
                    phase,
                ),
                CheckResult(
                    "email",
                    "Email / SMTP readiness",
                    "FAIL",
                    "BLOCKING",
                    detail,
                    phase,
                ),
            ]
        )

    results.append(http_result(phase))
    results.append(measurement_result(phase))
    results.append(manual_result(phase))
    return results


def table_widths() -> list[int]:
    return [26, 12, 12, 11, 52]


def shorten(value: str, width: int) -> str:
    value = value.replace("\n", " ")
    if len(value) <= width:
        return value
    if width <= 4:
        return value[:width]
    return value[: width - 3] + "..."


def render_combined_table(
    before: list[CheckResult] | None,
    after: list[CheckResult],
    title: str,
) -> None:
    widths = table_widths()
    total = sum(widths) + len(widths) - 1
    before_map = {item.key: item for item in (before or [])}

    print()
    print(color("╭" + "─" * total + "╮", "1;36"))
    print(
        color(
            "│" + title[:total].center(total) + "│",
            "1;36",
        )
    )
    print(
        color(
            "├" + "┬".join("─" * width for width in widths) + "┤",
            "1;36",
        )
    )
    print(
        "│"
        + "Check".ljust(widths[0])
        + "│"
        + "Before".ljust(widths[1])
        + "│"
        + "After".ljust(widths[2])
        + "│"
        + "Gate".ljust(widths[3])
        + "│"
        + "Details".ljust(widths[4])
        + "│"
    )
    print(
        color(
            "├" + "┼".join("─" * width for width in widths) + "┤",
            "1;36",
        )
    )

    for item in after:
        previous = before_map.get(item.key)
        before_status = previous.status if previous else "—"
        after_status = item.status

        before_visual = status_visual(before_status)
        after_visual = status_visual(after_status)

        before_pad = " " * max(
            0,
            widths[1] - len(before_status) - 2,
        )
        after_pad = " " * max(
            0,
            widths[2] - len(after_status) - 2,
        )

        detail = shorten(item.detail, widths[4])
        print(
            "│"
            + shorten(item.name, widths[0]).ljust(widths[0])
            + "│ "
            + before_visual
            + before_pad
            + "│ "
            + after_visual
            + after_pad
            + "│"
            + item.gate[: widths[3]].ljust(widths[3])
            + "│"
            + detail.ljust(widths[4])
            + "│"
        )

    print(
        color(
            "╰" + "┴".join("─" * width for width in widths) + "╯",
            "1;36",
        )
    )


def serialize_results(
    phase: str,
    results: list[CheckResult],
) -> dict:
    return {
        "generated_at": dt.datetime.now(
            dt.timezone.utc
        ).isoformat(),
        "phase": phase,
        "origin": PRODUCTION_ORIGIN,
        "results": [asdict(item) for item in results],
    }


def save_latest_pre(results: list[CheckResult]) -> None:
    ensure_dirs()
    LATEST_PRE.write_text(
        json.dumps(
            serialize_results("pre", results),
            ensure_ascii=False,
            indent=2,
        )
        + "\n",
        encoding="utf-8",
    )


def load_latest_pre() -> list[CheckResult] | None:
    if not LATEST_PRE.is_file():
        return None

    try:
        payload = json.loads(
            LATEST_PRE.read_text(encoding="utf-8")
        )
        raw_results = payload.get("results", [])
        return [
            CheckResult(**item)
            for item in raw_results
            if isinstance(item, dict)
        ]
    except Exception:
        return None


def save_latest_post(
    before: list[CheckResult] | None,
    after: list[CheckResult],
) -> None:
    ensure_dirs()
    payload = serialize_results("post", after)
    payload["before_results"] = [
        asdict(item)
        for item in (before or [])
    ]
    LATEST_POST.write_text(
        json.dumps(
            payload,
            ensure_ascii=False,
            indent=2,
        )
        + "\n",
        encoding="utf-8",
    )


def load_latest_post(
) -> tuple[list[CheckResult] | None, list[CheckResult]] | None:
    if not LATEST_POST.is_file():
        return None

    try:
        payload = json.loads(
            LATEST_POST.read_text(encoding="utf-8")
        )
        raw_before = payload.get("before_results", [])
        raw_after = payload.get("results", [])

        before = [
            CheckResult(**item)
            for item in raw_before
            if isinstance(item, dict)
        ]
        after = [
            CheckResult(**item)
            for item in raw_after
            if isinstance(item, dict)
        ]

        if not after:
            return None

        return before, after
    except Exception:
        return None


def write_reports(
    report_dir: Path,
    phase: str,
    before: list[CheckResult] | None,
    after: list[CheckResult],
) -> None:
    payload = serialize_results(phase, after)
    payload["before_results"] = [
        asdict(item) for item in (before or [])
    ]

    (report_dir / "health.json").write_text(
        json.dumps(
            payload,
            ensure_ascii=False,
            indent=2,
        )
        + "\n",
        encoding="utf-8",
    )

    before_map = {
        item.key: item for item in (before or [])
    }
    lines = [
        "# ForPrint production release health",
        "",
        f"- phase: `{phase}`",
        f"- origin: `{PRODUCTION_ORIGIN}`",
        "",
        "| Check | Before | After | Gate | Detail |",
        "|---|---|---|---|---|",
    ]
    for item in after:
        previous = before_map.get(item.key)
        before_status = previous.status if previous else "—"
        lines.append(
            f"| {item.name} | {before_status} | "
            f"{item.status} | {item.gate} | "
            f"{item.detail.replace('|', '/')} |"
        )

    lines += [
        "",
        (
            "Manual runbook: "
            f"`{MANUAL_RUNBOOK.relative_to(ROOT)}`"
        ),
    ]

    (report_dir / "summary.md").write_text(
        "\n".join(lines).rstrip() + "\n",
        encoding="utf-8",
    )


def has_blocking_failure(
    results: Iterable[CheckResult],
) -> bool:
    return any(
        item.status == "FAIL"
        and item.gate == "BLOCKING"
        for item in results
    )


def self_test() -> int:
    sample = """
    <script>
    window.ForPrintMeasurementConfig = Object.freeze(
      {"enabled":true,"testMode":false,"provider":"google-tag",
       "googleTagId":"AW-959055246",
       "conversionDestination":
       "AW-959055246/3ccOCP6mntocEI6LqMkD"}
    );
    </script>
    """
    config = parse_measurement_config(sample)
    assert config["enabled"] is True
    assert (
        config["googleTagId"]
        == EXPECTED_GOOGLE_TAG_ID
    )

    before = [
        CheckResult(
            "telegram",
            "Telegram readiness",
            "PASS",
            "BLOCKING",
            "self-test",
            "pre",
        ),
        CheckResult(
            "email",
            "Email / SMTP readiness",
            "PASS",
            "BLOCKING",
            "self-test",
            "pre",
        ),
        CheckResult(
            "http",
            "Public HTTP",
            "PASS",
            "BLOCKING",
            "self-test",
            "pre",
        ),
        CheckResult(
            "measurement",
            "Google Ads measurement",
            "WARN",
            "ADVISORY",
            "self-test repair candidate",
            "pre",
        ),
        CheckResult(
            "browser_e2e",
            "Browser / consent E2E",
            "MANUAL",
            "MANUAL",
            "self-test",
            "pre",
        ),
    ]

    after = [
        CheckResult(
            "telegram",
            "Telegram readiness",
            "PASS",
            "BLOCKING",
            "self-test",
            "post",
        ),
        CheckResult(
            "email",
            "Email / SMTP readiness",
            "PASS",
            "BLOCKING",
            "self-test",
            "post",
        ),
        CheckResult(
            "http",
            "Public HTTP",
            "PASS",
            "BLOCKING",
            "self-test",
            "post",
        ),
        CheckResult(
            "measurement",
            "Google Ads measurement",
            "PASS",
            "BLOCKING",
            "self-test repaired",
            "post",
        ),
        CheckResult(
            "browser_e2e",
            "Browser / consent E2E",
            "MANUAL",
            "MANUAL",
            "self-test",
            "post",
        ),
    ]

    render_combined_table(
        before,
        after,
        "FORPRINT — RELEASE HEALTH SELF-TEST",
    )
    print("RELEASE_HEALTH_SELF_TEST_OK")
    return 0


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument(
        "--phase",
        choices=("pre", "post", "standalone"),
        default="standalone",
    )
    parser.add_argument("--self-test", action="store_true")
    parser.add_argument(
        "--render-latest-post",
        action="store_true",
        help="re-render latest accepted POST evidence without new production checks",
    )
    args = parser.parse_args()

    if args.self_test:
        return self_test()

    if args.render_latest_post:
        latest = load_latest_post()
        if not latest:
            print("RELEASE_HEALTH_SUMMARY=UNAVAILABLE")
            print(f"Missing or invalid latest POST evidence: {LATEST_POST}")
            return 2

        before, after = latest
        render_combined_table(
            before,
            after,
            "FORPRINT — FINAL PRODUCTION RELEASE HEALTH",
        )
        print()
        print(
            terminal_link(
                "Manual browser checks",
                MANUAL_RUNBOOK,
            )
        )

        if has_blocking_failure(after):
            print(color("RELEASE_HEALTH_SUMMARY=FAIL", "1;31"))
            return 1

        print(color("RELEASE_HEALTH_SUMMARY=PASS", "1;32"))
        return 0

    phase = args.phase
    report_dir = new_report_dir(phase)
    current = collect(report_dir, phase)

    if phase == "pre":
        before = None
        save_latest_pre(current)
    elif phase == "post":
        before = load_latest_pre()
    else:
        before = None

    render_combined_table(
        before,
        current,
        (
            "FORPRINT — PRODUCTION RELEASE HEALTH / "
            + phase.upper()
        ),
    )

    print()
    print(
        terminal_link(
            "Manual browser checks",
            MANUAL_RUNBOOK,
        )
    )
    print(f"Health report: {report_dir / 'summary.md'}")

    write_reports(
        report_dir,
        phase,
        before,
        current,
    )

    if has_blocking_failure(current):
        print(color("RELEASE_HEALTH=FAIL", "1;31"))
        return 1

    if phase == "post":
        save_latest_post(
            before,
            current,
        )

    if any(
        item.status == "WARN"
        for item in current
    ):
        print(
            color(
                "RELEASE_HEALTH=PASS_WITH_WARNINGS",
                "1;33",
            )
        )
    else:
        print(color("RELEASE_HEALTH=PASS", "1;32"))
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
