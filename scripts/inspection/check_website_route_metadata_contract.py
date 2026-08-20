#!/usr/bin/env python3
from __future__ import annotations

import concurrent.futures
import html.parser
import sys
import urllib.error
import urllib.parse
import urllib.request
import xml.etree.ElementTree as ET
from pathlib import Path
from typing import Any


ROOT = Path(__file__).resolve().parents[2]
SITEMAP = ROOT / "base/sitemap.xml"


class Parser(html.parser.HTMLParser):
    def __init__(self) -> None:
        super().__init__(convert_charrefs=True)
        self.in_title = False
        self.in_h1 = False
        self.title_parts: list[str] = []
        self.h1_parts: list[str] = []
        self.description = ""
        self.canonical = ""
        self.lang = ""

    def handle_starttag(
        self,
        tag: str,
        attrs: list[tuple[str, str | None]],
    ) -> None:
        values = {
            key.lower(): value or ""
            for key, value in attrs
        }
        tag = tag.lower()

        if tag == "html":
            self.lang = values.get("lang", "").strip()
        elif tag == "title":
            self.in_title = True
        elif tag == "h1":
            self.in_h1 = True
        elif tag == "meta":
            if values.get("name", "").lower() == "description":
                self.description = values.get("content", "").strip()
        elif tag == "link":
            rel = values.get("rel", "").lower().split()

            if "canonical" in rel:
                self.canonical = values.get("href", "").strip()

    def handle_endtag(self, tag: str) -> None:
        if tag.lower() == "title":
            self.in_title = False
        elif tag.lower() == "h1":
            self.in_h1 = False

    def handle_data(self, data: str) -> None:
        value = " ".join(data.split())

        if not value:
            return

        if self.in_title:
            self.title_parts.append(value)

        if self.in_h1:
            self.h1_parts.append(value)

    @property
    def title(self) -> str:
        return " ".join(self.title_parts).strip()

    @property
    def h1(self) -> str:
        return " ".join(self.h1_parts).strip()


def fail(message: str) -> None:
    print(f"[FAIL] {message}", file=sys.stderr)
    raise SystemExit(1)


def sitemap_urls() -> list[str]:
    try:
        root = ET.parse(SITEMAP).getroot()
    except ET.ParseError as exc:
        fail(f"invalid sitemap XML: {exc}")

    namespace = {
        "sm": "http://www.sitemaps.org/schemas/sitemap/0.9"
    }
    urls = [
        (node.text or "").strip()
        for node in root.findall("sm:url/sm:loc", namespace)
    ]

    if not urls:
        fail("sitemap is empty")

    if len(urls) != len(set(urls)):
        fail("duplicate sitemap URLs")

    return urls


def canonical_matches(
    public_url: str,
    canonical_url: str,
) -> bool:
    public = urllib.parse.urlsplit(public_url)
    canonical = urllib.parse.urlsplit(canonical_url)

    if (
        (canonical.path or "/") != (public.path or "/")
        or canonical.query != public.query
    ):
        return False

    if (
        canonical.scheme == "https"
        and canonical.hostname == "forprint.net.ua"
    ):
        return True

    return (
        canonical.scheme in {"http", "https"}
        and canonical.hostname in {"127.0.0.1", "localhost"}
    )


def inspect(public_url: str) -> dict[str, Any]:
    parsed = urllib.parse.urlsplit(public_url)
    local_url = urllib.parse.urlunsplit(
        (
            "http",
            "127.0.0.1:8098",
            parsed.path or "/",
            parsed.query,
            "",
        )
    )
    request = urllib.request.Request(
        local_url,
        headers={
            "User-Agent": "ForPrint-Metadata-Validator/1.0",
            "Cache-Control": "no-cache",
        },
    )

    try:
        with urllib.request.urlopen(
            request,
            timeout=30,
        ) as response:
            body = response.read(4_000_000).decode(
                "utf-8",
                errors="replace",
            )
            status = response.status
    except urllib.error.HTTPError as exc:
        body = exc.read(4_000_000).decode(
            "utf-8",
            errors="replace",
        )
        status = exc.code
    except Exception as exc:
        return {
            "url": public_url,
            "status": None,
            "error": type(exc).__name__,
            "title": "",
            "description": "",
            "h1": "",
            "canonical": "",
            "lang": "",
        }

    parser = Parser()

    try:
        parser.feed(body)
    except Exception:
        pass

    return {
        "url": public_url,
        "status": status,
        "error": "",
        "title": parser.title,
        "description": parser.description,
        "h1": parser.h1,
        "canonical": parser.canonical,
        "lang": parser.lang,
    }


def main() -> int:
    urls = sitemap_urls()

    with concurrent.futures.ThreadPoolExecutor(
        max_workers=6
    ) as executor:
        records = list(executor.map(inspect, urls))

    failures: list[str] = []

    for item in records:
        if item["status"] != 200:
            failures.append(
                f"status:{item['url']}:{item['status']}"
            )

        if not item["title"]:
            failures.append("title-missing:" + item["url"])

        if not item["description"]:
            failures.append(
                "description-missing:" + item["url"]
            )

        if not item["h1"]:
            failures.append("h1-missing:" + item["url"])

        if not canonical_matches(
            item["url"],
            item["canonical"],
        ):
            failures.append(
                "canonical-mismatch:"
                + item["url"]
                + ":"
                + item["canonical"]
            )

        if item["lang"] != "uk":
            failures.append(
                "lang-mismatch:"
                + item["url"]
                + ":"
                + item["lang"]
            )

    titles = [item["title"] for item in records]
    descriptions = [
        item["description"]
        for item in records
    ]
    h1_values = [item["h1"] for item in records]

    if len(set(titles)) != len(titles):
        failures.append(
            "duplicate-titles:"
            + str(len(titles) - len(set(titles)))
        )

    if len(set(descriptions)) != len(descriptions):
        failures.append(
            "duplicate-descriptions:"
            + str(
                len(descriptions)
                - len(set(descriptions))
            )
        )

    if failures:
        for failure in failures[:40]:
            print("[FAIL] " + failure, file=sys.stderr)

        if len(failures) > 40:
            print(
                f"[FAIL] plus {len(failures) - 40} more",
                file=sys.stderr,
            )

        return 1

    print("ForPrint route metadata checks passed.")
    print(f"urls={len(records)}")
    print(f"unique_titles={len(set(titles))}")
    print(
        "unique_descriptions="
        + str(len(set(descriptions)))
    )
    print(f"unique_h1={len(set(h1_values))}")
    print("language=uk")
    print("canonical_origin=https://forprint.net.ua")
    print("production_release=deferred")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
