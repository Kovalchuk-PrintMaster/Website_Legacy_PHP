#!/usr/bin/env python3
from __future__ import annotations

import hashlib
import os
import subprocess
import sys
import time
import urllib.error
import urllib.request
from pathlib import Path


ROOT = Path(__file__).resolve().parents[2]
WEBROOT = ROOT / "base"
ROUTER = ROOT / "scripts" / "inspection" / "local_http_smoke_router.php"

HOST = os.environ.get("FP_WEB_LOCAL_HTTP_HOST", "127.0.0.1")
PORT = int(os.environ.get("FP_WEB_LOCAL_HTTP_PORT", "8098"))
BASE_URL = f"http://{HOST}:{PORT}"

ROUTES: dict[str, set[int]] = {
    "/": {200},
    "/catalog": {301, 302},
    "/search": {301, 302},
    "/contacts/": {200},
    "/information/contacts/": {301, 302},
    "/information/oplata-i-dostavka/": {200},
    "/special-offers/": {200},
    "/promotions/": {200},
    "/information/promotions/": {301, 302},
    "/news/": {200},
    "/information/news/": {301, 302},
    "/information/special-offers/": {301, 302},
}


class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, req, fp, code, msg, headers, newurl):
        return None


def fetch(path: str, timeout: int = 10) -> tuple[int, str, bytes]:
    opener = urllib.request.build_opener(NoRedirect)
    req = urllib.request.Request(BASE_URL + path, headers={"User-Agent": "ForPrintWebsiteSmoke/1.0"})

    try:
        with opener.open(req, timeout=timeout) as response:
            return (
                int(response.status),
                response.headers.get("Content-Type", ""),
                response.read(),
            )
    except urllib.error.HTTPError as exc:
        return (
            int(exc.code),
            exc.headers.get("Content-Type", ""),
            exc.read(),
        )


def wait_until_ready(proc: subprocess.Popen[str]) -> None:
    deadline = time.time() + 8

    while time.time() < deadline:
        if proc.poll() is not None:
            raise RuntimeError(f"PHP local server exited early with code {proc.returncode}")

        try:
            status, _, _ = fetch("/", timeout=2)
            if status in {200, 301, 302}:
                return
        except Exception:
            time.sleep(0.2)

    raise RuntimeError(f"PHP local server did not become ready at {BASE_URL}/")


def main() -> int:
    print("== ForPrint Website local HTTP smoke ==")
    print(f"root: {ROOT}")
    print(f"webroot: {WEBROOT}")
    print(f"router: {ROUTER}")
    print(f"url: {BASE_URL}/")
    print()

    if not WEBROOT.exists():
        print(f"[FAIL] webroot not found: {WEBROOT}")
        return 3

    if not ROUTER.exists():
        print(f"[FAIL] router not found: {ROUTER}")
        return 3

    cmd = [
        "php",
        "-S",
        f"{HOST}:{PORT}",
        "-t",
        str(WEBROOT),
        str(ROUTER),
    ]

    env = os.environ.copy()

    proc = subprocess.Popen(
        cmd,
        cwd=ROOT,
        stdout=subprocess.DEVNULL,
        stderr=subprocess.DEVNULL,
        text=True,
        env=env,
    )

    failed = False

    try:
        wait_until_ready(proc)

        print("== Route smoke ==")

        for route, expected_statuses in ROUTES.items():
            try:
                status, content_type, body = fetch(route, timeout=10)
                sha = hashlib.sha256(body).hexdigest()[:16]
                print(
                    f"{route}: status={status} "
                    f"content_type='{content_type}' "
                    f"bytes={len(body)} sha256_16={sha}"
                )

                if status not in expected_statuses:
                    print(f"  [FAIL] expected status in {sorted(expected_statuses)}")
                    failed = True

                if status == 200 and len(body) == 0:
                    print("  [FAIL] empty body for HTTP 200 route")
                    failed = True

            except Exception as exc:
                print(f"{route}: [FAIL] {exc}")
                failed = True

    finally:
        proc.terminate()
        try:
            proc.wait(timeout=3)
        except subprocess.TimeoutExpired:
            proc.kill()
            proc.wait(timeout=3)

    print()
    if failed:
        print("status: LOCAL_WEBSITE_HTTP_SMOKE_FAILED")
        return 3

    print("status: LOCAL_WEBSITE_HTTP_SMOKE_OK")
    return 0


if __name__ == "__main__":
    sys.exit(main())