#!/usr/bin/env python3
# -*- coding: utf-8 -*-

from __future__ import annotations

import argparse
import json
import os
import re
import sys
from pathlib import Path
from urllib.parse import urljoin, urlparse

ROOT = Path(__file__).resolve().parents[2]
BROWSER_CACHE = ROOT / ".runtime/playwright-browsers"

DEFAULT_ROUTES = [
    "/",
    "/catalog/",
    "/technical-requirements/tsyfrovyi-druk/",
]
DEFAULT_VIEWPORTS = [
    (1920, 1080),
    (1600, 1000),
    (1366, 900),
    (1024, 900),
    (768, 900),
    (390, 844),
]


def parse_viewport(raw):
    m = re.fullmatch(r"(\d{2,5})x(\d{2,5})", raw.lower().strip())
    if not m:
        raise argparse.ArgumentTypeError("viewport must be WIDTHxHEIGHT")
    return int(m.group(1)), int(m.group(2))


def is_local(url):
    host = (urlparse(url).hostname or "").lower()
    return host in {"127.0.0.1", "localhost", "::1"}


def safe_name(route):
    if route == "/":
        return "home"
    return re.sub(r"[^a-zA-Z0-9_-]+", "-", route.strip("/").replace("/", "__"))


def main():
    ap = argparse.ArgumentParser(
        description="ForPrint real-browser visual inspection"
    )
    ap.add_argument("--base-url", default="http://127.0.0.1:8098")
    ap.add_argument("--allow-remote", action="store_true")
    ap.add_argument(
        "--output-dir",
        default="tmp/browser_visual_inspection/latest",
    )
    ap.add_argument("--route", action="append", dest="routes")
    ap.add_argument("--viewport", action="append", type=parse_viewport)
    ap.add_argument("--full-page", action="store_true")
    args = ap.parse_args()

    base = args.base_url.rstrip("/") + "/"
    remote = not is_local(base)
    if remote and not args.allow_remote:
        print(
            "ERROR: remote URL requires --allow-remote; "
            "remote mode is inspection-only",
            file=sys.stderr,
        )
        return 2

    out_arg = Path(args.output_dir)
    out = out_arg.resolve() if out_arg.is_absolute() else (ROOT / out_arg).resolve()
    out.mkdir(parents=True, exist_ok=True)
    shots = out / "screenshots"
    shots.mkdir(parents=True, exist_ok=True)

    routes = args.routes or DEFAULT_ROUTES
    viewports = args.viewport or DEFAULT_VIEWPORTS

    os.environ.setdefault("PLAYWRIGHT_BROWSERS_PATH", str(BROWSER_CACHE))

    try:
        from playwright.sync_api import sync_playwright
    except Exception as exc:
        print(f"ERROR: Playwright import failed: {exc}", file=sys.stderr)
        return 2

    result = {
        "base_url": base,
        "remote": remote,
        "remote_policy": "GET_HEAD_OPTIONS_only_no_clicks_no_forms_no_auth",
        "screenshots": [],
        "metrics": [],
        "blocked_mutating_requests": [],
        "findings": [],
        "automated_status": "PASS",
    }

    def intercept(route):
        method = route.request.method.upper()
        if method not in {"GET", "HEAD", "OPTIONS"}:
            result["blocked_mutating_requests"].append({
                "method": method,
                "url": route.request.url,
            })
            route.abort()
        else:
            route.continue_()

    try:
        with sync_playwright() as pw:
            browser = pw.chromium.launch(
                headless=True,
                args=["--no-sandbox"],
            )
            result["browser_version"] = browser.version
            page = browser.new_page()
            page.route("**/*", intercept)

            for route_path in routes:
                route_path = route_path if route_path.startswith("/") else "/" + route_path
                url = urljoin(base, route_path.lstrip("/"))
                name = safe_name(route_path)

                for width, height in viewports:
                    page.set_viewport_size({"width": width, "height": height})
                    response = page.goto(
                        url,
                        wait_until="networkidle",
                        timeout=45000,
                    )
                    page.wait_for_timeout(300)

                    shot = shots / f"{name}_{width}x{height}.png"
                    page.screenshot(path=str(shot), full_page=args.full_page)

                    metric = page.evaluate(
                        '''() => {
                            const d = document.documentElement;
                            const b = document.body;
                            const maxW = Math.max(
                                d.scrollWidth,
                                b ? b.scrollWidth : 0
                            );
                            const rect = (sel) => {
                                const el = document.querySelector(sel);
                                if (!el) return null;
                                const r = el.getBoundingClientRect();
                                return {
                                    x: Math.round(r.x * 100) / 100,
                                    y: Math.round(r.y * 100) / 100,
                                    width: Math.round(r.width * 100) / 100,
                                    height: Math.round(r.height * 100) / 100,
                                    display: getComputedStyle(el).display
                                };
                            };
                            return {
                                clientWidth: d.clientWidth,
                                scrollWidth: d.scrollWidth,
                                bodyScrollWidth: b ? b.scrollWidth : null,
                                horizontalOverflow:
                                    Math.max(0, maxW - d.clientWidth),
                                header: rect('.fp-site-header'),
                                slogan: rect('.fp-site-header__slogan'),
                                nav: rect('.fp-site-header__nav-list'),
                                main: rect('main.main'),
                                catalogAside: rect('.catalog-aside-block'),
                                catalogTree: rect('.fp-catalog-category-list'),
                                quoteRail: rect('.fp-quote-rail-utility')
                            };
                        }'''
                    )
                    metric.update({
                        "route": route_path,
                        "url": page.url,
                        "http_status": response.status if response else None,
                        "viewport": f"{width}x{height}",
                        "screenshot": str(shot.relative_to(ROOT)),
                    })
                    result["metrics"].append(metric)
                    result["screenshots"].append(str(shot.relative_to(ROOT)))

            browser.close()

    except Exception as exc:
        result["automated_status"] = "ERROR"
        result["findings"].append(f"{type(exc).__name__}: {exc}")
        result_path = out / "result.json"
        result_path.write_text(
            json.dumps(result, ensure_ascii=False, indent=2),
            encoding="utf-8",
        )
        print(f"RESULT_JSON={result_path}")
        print("CAPTURE_STATUS=ERROR")
        return 2

    overflow = [
        {
            "route": x["route"],
            "viewport": x["viewport"],
            "horizontalOverflow": x["horizontalOverflow"],
        }
        for x in result["metrics"]
        if (x.get("horizontalOverflow") or 0) > 2
    ]
    non200 = [
        {
            "route": x["route"],
            "viewport": x["viewport"],
            "status": x["http_status"],
        }
        for x in result["metrics"]
        if x.get("http_status") != 200
    ]
    if overflow:
        result["automated_status"] = "NEEDS_REVIEW"
        result["findings"].append({"horizontal_overflow_over_2px": overflow})
    if non200:
        result["automated_status"] = "NEEDS_REVIEW"
        result["findings"].append({"non_200": non200})

    result_path = out / "result.json"
    result_path.write_text(
        json.dumps(result, ensure_ascii=False, indent=2),
        encoding="utf-8",
    )

    figures = []
    for rel in result["screenshots"]:
        img = ROOT / rel
        figures.append(
            "<figure><figcaption>" + img.stem
            + "</figcaption><img src='" + img.as_uri() + "'></figure>"
        )

    contact_html = out / "contact_sheet.html"
    contact_html.write_text(
        '<!doctype html><html><head><meta charset="utf-8"><style>'
        '*{box-sizing:border-box}body{margin:0;padding:24px;background:#f2f3f5;'
        'font-family:Arial,sans-serif}.grid{display:grid;'
        'grid-template-columns:repeat(3,360px);gap:18px}'
        'figure{margin:0;background:#fff;border:1px solid #ccd2d8;padding:10px}'
        'figcaption{font-size:13px;font-weight:700;margin-bottom:8px}'
        'img{display:block;width:338px;height:auto;border:1px solid #e1e4e8}'
        '</style></head><body><h1>ForPrint browser visual inspection</h1>'
        '<div class="grid">'
        + "".join(figures)
        + "</div></body></html>",
        encoding="utf-8",
    )

    with sync_playwright() as pw:
        browser = pw.chromium.launch(headless=True, args=["--no-sandbox"])
        page = browser.new_page(viewport={"width": 1160, "height": 900})
        page.goto(contact_html.as_uri(), wait_until="load", timeout=30000)
        contact_png = out / "contact_sheet.png"
        page.screenshot(path=str(contact_png), full_page=True)
        browser.close()

    result["contact_sheet_png"] = str(contact_png.relative_to(ROOT))
    result_path.write_text(
        json.dumps(result, ensure_ascii=False, indent=2),
        encoding="utf-8",
    )

    print(f"RESULT_JSON={result_path}")
    print(f"CAPTURE_STATUS={result['automated_status']}")
    print(f"CONTACT_SHEET={contact_png}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
