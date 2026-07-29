#!/usr/bin/env python3
from __future__ import annotations

import subprocess
import sys
import xml.etree.ElementTree as ET
from pathlib import Path
from urllib.parse import urlparse


ROOT = Path(__file__).resolve().parents[2]
GENERATOR = ROOT / "base/core/admin/controllers/CreatesitemapController.php"
SITEMAP = ROOT / "base/sitemap.xml"
ROBOTS = ROOT / "base/robots.txt"

BLOCKED = (
    "/admin",
    "/cart",
    "/search",
    "/lk",
    "/login",
    "/logout",
    "/communication-request.php",
    "/search-suggestions.php",
)


def fail(message: str) -> None:
    print(f"[FAIL] {message}", file=sys.stderr)
    raise SystemExit(1)


def main() -> int:
    for path in (GENERATOR, SITEMAP, ROBOTS):
        if not path.is_file():
            fail("missing " + path.relative_to(ROOT).as_posix())

    robots = ROBOTS.read_text(encoding="utf-8")

    if "Disallow: /\\n" in robots:
        fail("robots.txt blocks the entire site")

    if "Sitemap: https://forprint.net.ua/sitemap.xml" not in robots:
        fail("canonical Sitemap directive missing")

    tree = ET.parse(SITEMAP)
    namespace = {"sm": "http://www.sitemaps.org/schemas/sitemap/0.9"}
    urls = [
        (node.text or "").strip()
        for node in tree.getroot().findall("sm:url/sm:loc", namespace)
    ]

    if not urls:
        fail("sitemap is empty")

    if len(urls) != len(set(urls)):
        fail("duplicate sitemap URLs")

    for url in urls:
        parsed = urlparse(url)

        if parsed.scheme != "https" or parsed.hostname != "forprint.net.ua":
            fail("non-canonical URL: " + url)

        if parsed.query or parsed.fragment:
            fail("query or fragment in URL: " + url)

        path = parsed.path or "/"

        if any(
            path == prefix or path.startswith(prefix + "/")
            for prefix in BLOCKED
        ):
            fail("private route in sitemap: " + url)

    required = {
        "https://forprint.net.ua/",
        "https://forprint.net.ua/contacts/",
    }

    if not required.issubset(set(urls)):
        fail("verified baseline URLs missing")

    generator = GENERATOR.read_text(encoding="utf-8")

    for marker in (
        "FP_WEB_CANONICAL_ORIGIN",
        "sitemapCanonicalOrigin",
        "sitemapCanonicalUrl",
        "https://forprint.net.ua",
        "createTextNode",
    ):
        if marker not in generator:
            fail("generator marker missing: " + marker)

    if "addChild('loc', htmlspecialchars($item))" in generator:
        fail("legacy sitemap writer remains")

    lint = subprocess.run(
        ["php", "-l", str(GENERATOR)],
        stdout=subprocess.PIPE,
        stderr=subprocess.PIPE,
        text=True,
        check=False,
    )

    if lint.returncode != 0:
        fail("PHP lint failed: " + lint.stderr.strip())

    print("ForPrint SEO crawl-contract checks passed.")
    print(f"sitemap_urls={len(urls)}")
    print("canonical_origin=https://forprint.net.ua")
    print("robots_full_block=no")
    print("production_release=deferred")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
