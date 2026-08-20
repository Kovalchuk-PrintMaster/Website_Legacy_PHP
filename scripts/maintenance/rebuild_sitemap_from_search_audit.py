#!/usr/bin/env python3
from __future__ import annotations

import argparse
import csv
from pathlib import Path
from urllib.parse import urlsplit
import xml.etree.ElementTree as ET


PUBLIC_ORIGIN = "https://forprint.net.ua"

BLOCKED_PREFIXES = (
    "/admin",
    "/account",
    "/cart",
    "/checkout",
    "/communication-request.php",
    "/lk",
    "/search",
    "/test-home",
)

REQUIRED_URLS = {
    "https://forprint.net.ua/",
    "https://forprint.net.ua/catalog/",
    "https://forprint.net.ua/contacts/",
    "https://forprint.net.ua/product/brenduvannya-avto/",
    "https://forprint.net.ua/product/eko-vzitki/",
}

FORBIDDEN_URLS = {
    "https://forprint.net.ua/product/obmna-vishivka-na-kepkah/",
}


def load_rows(path: Path) -> list[dict[str, str]]:
    with path.open("r", encoding="utf-8-sig", newline="") as handle:
        return list(csv.DictReader(handle))


def is_blocked_path(path: str) -> bool:
    normalized = path.rstrip("/") or "/"

    for prefix in BLOCKED_PREFIXES:
        candidate = prefix.rstrip("/") or "/"
        if (
            normalized == candidate
            or normalized.startswith(candidate + "/")
        ):
            return True

    return False


def candidate_urls(rows: list[dict[str, str]]) -> list[str]:
    result = set()

    for row in rows:
        url = (row.get("url") or "").strip()
        canonical = (row.get("canonical") or "").strip()

        if not url or not canonical:
            continue

        status = (row.get("status") or "").strip()
        final_status = (row.get("final_status") or status).strip()
        redirect_count = (row.get("redirect_count") or "0").strip()
        indexability = (row.get("indexability") or "").strip()

        if status != "200" or final_status != "200":
            continue

        try:
            if int(redirect_count or "0") != 0:
                continue
        except ValueError:
            continue

        if indexability != "indexable":
            continue

        if canonical != url:
            continue

        parsed = urlsplit(url)

        if (
            parsed.scheme != "https"
            or parsed.hostname != "forprint.net.ua"
            or parsed.query
            or parsed.fragment
        ):
            continue

        if is_blocked_path(parsed.path or "/"):
            continue

        result.add(url)

    missing_required = sorted(REQUIRED_URLS - result)
    if missing_required:
        raise RuntimeError(
            "Required canonical URLs missing from audit candidate set: "
            + ", ".join(missing_required)
        )

    forbidden_present = sorted(FORBIDDEN_URLS & result)
    if forbidden_present:
        raise RuntimeError(
            "Forbidden redirect/legacy URLs entered candidate set: "
            + ", ".join(forbidden_present)
        )

    def sort_key(url: str):
        if url == PUBLIC_ORIGIN + "/":
            return (0, "")
        return (1, urlsplit(url).path)

    return sorted(result, key=sort_key)


def build_xml(urls: list[str]) -> bytes:
    ET.register_namespace(
        "",
        "http://www.sitemaps.org/schemas/sitemap/0.9",
    )

    root = ET.Element(
        "{http://www.sitemaps.org/schemas/sitemap/0.9}urlset"
    )

    for url in urls:
        url_node = ET.SubElement(
            root,
            "{http://www.sitemaps.org/schemas/sitemap/0.9}url",
        )
        loc = ET.SubElement(
            url_node,
            "{http://www.sitemaps.org/schemas/sitemap/0.9}loc",
        )
        loc.text = url

    tree = ET.ElementTree(root)
    ET.indent(tree, space="  ")

    import io

    buffer = io.BytesIO()
    tree.write(
        buffer,
        encoding="utf-8",
        xml_declaration=True,
    )
    return buffer.getvalue()


def main() -> int:
    parser = argparse.ArgumentParser(
        description=(
            "Build deterministic ForPrint sitemap.xml from a completed "
            "read-only search audit URL inventory."
        )
    )
    parser.add_argument("--report", required=True)
    parser.add_argument("--output", required=True)

    mode = parser.add_mutually_exclusive_group(required=True)
    mode.add_argument("--dry-run", action="store_true")
    mode.add_argument("--apply", action="store_true")

    args = parser.parse_args()

    report = Path(args.report)
    urls_csv = report / "urls.csv"
    output = Path(args.output)

    if not urls_csv.is_file():
        raise SystemExit(f"Missing audit inventory: {urls_csv}")

    rows = load_rows(urls_csv)
    urls = candidate_urls(rows)
    xml_bytes = build_xml(urls)

    print("ForPrint canonical sitemap builder")
    print("=" * 72)
    print(f"audit={report}")
    print(f"candidate_urls={len(urls)}")
    print(f"output={output}")
    print("policy=200 + indexable + self-canonical + same-origin + no-query")
    print("internal-search/private/redirect surfaces=excluded")

    if args.dry_run:
        print("[DRY RUN] sitemap was not written")
        return 0

    output.parent.mkdir(parents=True, exist_ok=True)
    temporary = output.with_suffix(output.suffix + ".tmp")
    temporary.write_bytes(xml_bytes)

    try:
        parsed = ET.parse(temporary)
        root = parsed.getroot()

        if not root.tag.endswith("urlset"):
            raise RuntimeError("Generated XML root is not urlset.")

        temporary.replace(output)
    finally:
        if temporary.exists():
            temporary.unlink()

    print(f"[WRITE OK] {output}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
