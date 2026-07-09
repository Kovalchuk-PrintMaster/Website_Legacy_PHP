#!/usr/bin/env python
from pathlib import Path
import hashlib
import http.client
import os
import subprocess
import time

ROOT = Path(__file__).resolve().parents[2]
HOST = "127.0.0.1"
PORT = int(os.environ.get("FP_WEB_LOCAL_HTTP_PORT", "8098"))

ROUTES = [
    "/",
    "/catalog",
    "/search",
]


def fetch(path: str) -> tuple[int, str, int, str]:
    conn = http.client.HTTPConnection(HOST, PORT, timeout=10)
    conn.request("GET", path, headers={"Host": "localhost"})
    response = conn.getresponse()
    body = response.read()
    content_type = response.getheader("Content-Type", "")
    digest = hashlib.sha256(body).hexdigest()[:16]
    conn.close()
    return response.status, content_type, len(body), digest


def main() -> int:
    router = ROOT / "scripts/inspection/local_http_smoke_router.php"
    webroot = ROOT / "base"

    print("== ForPrint Website local HTTP smoke ==")
    print(f"root: {ROOT}")
    print(f"webroot: {webroot}")
    print(f"router: {router}")
    print(f"url: http://{HOST}:{PORT}/")

    if not router.exists():
        print("[FAIL] router script is missing")
        print("status: LOCAL_WEBSITE_HTTP_SMOKE_NOT_READY")
        return 1

    cmd = [
        "php",
        "-S",
        f"{HOST}:{PORT}",
        "-t",
        str(webroot),
        str(router),
    ]

    env = os.environ.copy()
    env["FP_WEB_EXPECTED_DB_NAME"] = env.get(
        "FP_WEB_EXPECTED_DB_NAME",
        "forprint_website_legacy_local",
    )

    proc = subprocess.Popen(
        cmd,
        cwd=ROOT,
        stdout=subprocess.PIPE,
        stderr=subprocess.PIPE,
        text=True,
        env=env,
    )

    try:
        time.sleep(1.5)

        if proc.poll() is not None:
            stderr = proc.stderr.read() if proc.stderr else ""
            print("[FAIL] PHP built-in server exited early")
            if stderr:
                print("server_stderr:")
                print(stderr[-1200:])
            print("status: LOCAL_WEBSITE_HTTP_SERVER_FAILED")
            return 2

        print()
        print("== Route smoke ==")

        failures = 0

        for route in ROUTES:
            try:
                status, content_type, size, digest = fetch(route)
                print(f"{route}: status={status} content_type={content_type!r} bytes={size} sha256_16={digest}")

                if status >= 500:
                    failures += 1
            except Exception as exc:
                failures += 1
                print(f"{route}: [FAIL] {exc}")

        if failures:
            print()
            print("status: LOCAL_WEBSITE_HTTP_SMOKE_FAILED")
            return 3

        print()
        print("status: LOCAL_WEBSITE_HTTP_SMOKE_OK")
        return 0

    finally:
        proc.terminate()
        try:
            proc.wait(timeout=5)
        except subprocess.TimeoutExpired:
            proc.kill()


if __name__ == "__main__":
    raise SystemExit(main())