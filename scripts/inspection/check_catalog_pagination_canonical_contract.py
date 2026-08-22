#!/usr/bin/env python3
from __future__ import annotations

import json
import urllib.error
import urllib.parse
import urllib.request
from html.parser import HTMLParser
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]

BASE_USER = ROOT / "base/core/user/controllers/BaseUser.php"
CATALOG = ROOT / "base/core/user/controllers/CatalogController.php"
HEADER = ROOT / "base/templates/default/include/header.php"

PREVIEW = "http://127.0.0.1:8098"


class HeadParser(HTMLParser):
    def __init__(self) -> None:
        super().__init__()
        self.canonical = ""
        self.robots = []

    def handle_starttag(self, tag, attrs):
        attr = {k.lower(): (v or "") for k, v in attrs}
        tag = tag.lower()

        if tag == "link" and "canonical" in attr.get("rel", "").lower().split():
            self.canonical = attr.get("href", "").strip()

        if tag == "meta" and attr.get("name", "").lower() in {"robots", "googlebot"}:
            content = attr.get("content", "").strip()

            if content:
                self.robots.append(content)


def fail(message: str) -> None:
    raise SystemExit("FAIL: " + message)


def fetch(path: str):
    url = PREVIEW + path
    request = urllib.request.Request(
        url,
        headers={
            "User-Agent": "ForPrintPaginationCanonicalContract/1.0",
        },
    )

    try:
        with urllib.request.urlopen(request, timeout=25) as response:
            body = response.read()
            status = int(getattr(response, "status", 200))
            final = response.geturl()
    except urllib.error.HTTPError as exc:
        body = exc.read()
        status = int(exc.code)
        final = url

    parser = HeadParser()

    try:
        parser.feed(body.decode("utf-8", errors="replace"))
    except Exception:
        pass

    return {
        "url": url,
        "status": status,
        "final": final,
        "canonical": parser.canonical,
        "robots": ",".join(parser.robots).lower(),
    }


def main() -> int:
    base_user = BASE_USER.read_text(encoding="utf-8")
    catalog = CATALOG.read_text(encoding="utf-8")
    header = HEADER.read_text(encoding="utf-8")

    required = [
        ("BaseUser canonical metadata marker", "FP_CANONICAL_QUERY_METADATA_V0_1", base_user),
        ("BaseUser canonicalQuery property", "protected $canonicalQuery = [];", base_user),
        ("Catalog pagination marker", "FP_CATALOG_CANONICAL_PAGINATION_V0_1", catalog),
        ("Catalog clean query count guard", "count($_GET) === 1", catalog),
        ("Catalog page key guard", "array_key_exists('page', $_GET)", catalog),
        ("Catalog non-empty goods guard", "&& !empty($goods)", catalog),
        ("Catalog canonicalQuery assignment", "$this->canonicalQuery = [", catalog),
        ("Header render marker", "FP_CANONICAL_QUERY_RENDER_V0_1", header),
        ("Header structured query read", "is_array($this->canonicalQuery)", header),
        ("Header RFC3986 serialization", "PHP_QUERY_RFC3986", header),
    ]

    missing = [
        label
        for label, token, text in required
        if token not in text
    ]

    if missing:
        fail("missing contract tokens: " + repr(missing))

    if catalog.find("FP_CATALOG_CANONICAL_PAGINATION_V0_1") < catalog.find("$pages = $this->model->getPagination();"):
        fail("pagination canonical logic must execute after pagination resolution")

    if header.find("FP_CANONICAL_QUERY_RENDER_V0_1") < header.find("$fpCanonicalUrl ="):
        fail("canonical query rendering must execute after base canonical URL construction")

    clean = fetch("/catalog/konverti/")

    if clean["status"] != 200 or not clean["canonical"]:
        fail("clean category baseline failed: " + json.dumps(clean, ensure_ascii=False))

    clean_canonical = clean["canonical"]

    valid_cases = [
        "/catalog/konverti/?page=2",
        "/catalog/beydzh/?page=2",
        "/catalog/shirokoformatniy-druk/?page=2",
        "/catalog/nterrne-oformlennya-ta-konstrukts/?page=2",
        "/catalog/zovnshnya-reklama/?page=2",
    ]

    valid_results = []

    for path in valid_cases:
        row = fetch(path)
        parsed = urllib.parse.urlsplit(row["canonical"])
        requested = urllib.parse.urlsplit(row["url"])

        if row["status"] != 200:
            fail("valid pagination HTTP failure: " + json.dumps(row, ensure_ascii=False))

        if "noindex" in row["robots"]:
            fail("valid pagination unexpectedly noindex: " + json.dumps(row, ensure_ascii=False))

        if parsed.path != requested.path or parsed.query != "page=2":
            fail("valid pagination not self-canonical: " + json.dumps(row, ensure_ascii=False))

        valid_results.append(row)

    dedup_cases = [
        (
            "/catalog/konverti/?page=1",
            "/catalog/konverti/",
        ),
        (
            "/catalog/konverti/?page=50",
            "/catalog/konverti/",
        ),
        (
            "/catalog/?quantity=3&order=name_asc&page=50",
            "/catalog/",
        ),
        (
            "/catalog/beydzh/?page=2&order=name_asc",
            "/catalog/beydzh/",
        ),
        (
            "/catalog/beydzh/?page=2&quantity=3",
            "/catalog/beydzh/",
        ),
    ]

    dedup_results = []

    for path, expected_path in dedup_cases:
        row = fetch(path)
        canonical = urllib.parse.urlsplit(row["canonical"])

        if row["status"] != 200:
            fail("dedup variant HTTP failure: " + json.dumps(row, ensure_ascii=False))

        if canonical.path != expected_path or canonical.query:
            fail("dedup variant canonical policy changed: " + json.dumps(row, ensure_ascii=False))

        dedup_results.append(row)

    print("PASS")
    print("valid_page_gt_1_self_canonical=5/5")
    print("page1_queryless_canonical=PASS")
    print("out_of_range_queryless_canonical=PASS")
    print("sort_filter_queryless_canonical=PASS")
    print("quantity_queryless_canonical=PASS")
    print("valid_pagination_noindex=NO")
    print("clean_category_canonical=" + clean_canonical)
    print(
        "valid_results="
        + json.dumps(valid_results, ensure_ascii=False)
    )
    print(
        "dedup_results="
        + json.dumps(dedup_results, ensure_ascii=False)
    )

    return 0


if __name__ == "__main__":
    raise SystemExit(main())
