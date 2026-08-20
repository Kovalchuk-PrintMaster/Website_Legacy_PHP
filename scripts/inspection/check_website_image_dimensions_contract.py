#!/usr/bin/env python3
# FP_IMAGE_DIMENSIONS_CONTRACT_V0_1

from __future__ import annotations

import re
import xml.etree.ElementTree as ET
from html.parser import HTMLParser
from pathlib import Path
from urllib.parse import urlparse
from urllib.request import Request, urlopen


ROOT = Path("/srv/software_development/forprint-project/forprint_website")
SITEMAP = ROOT / "base/sitemap.xml"
ORIGIN = "http://127.0.0.1:8098"
EXPECTED_URLS = 191


class Parser(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.images = []

    def handle_starttag(self, tag, attrs):
        if tag.lower() == "img":
            self.images.append({
                str(k).lower(): "" if v is None else str(v)
                for k, v in attrs
            })


def paths():
    root = ET.fromstring(SITEMAP.read_bytes())
    result = []

    for el in root.iter():
        if el.tag.rsplit("}", 1)[-1] != "loc" or not el.text:
            continue
        path = urlparse(el.text.strip()).path or "/"
        if path not in result:
            result.append(path)

    return result


def fetch(path):
    req = Request(
        ORIGIN + path,
        headers={"User-Agent": "ForPrintImageDimensionsContract/1.0"},
    )
    with urlopen(req, timeout=30) as response:
        return (
            int(response.status),
            response.headers.get("Content-Type", ""),
            response.read(10_000_000),
        )


def main():
    route_paths = paths()

    if len(route_paths) != EXPECTED_URLS:
        raise RuntimeError(
            f"sitemap_urls={len(route_paths)} expected={EXPECTED_URLS}"
        )

    pages = 0
    images = 0
    failures = []

    for path in route_paths:
        status, content_type, body = fetch(path)

        if status != 200:
            failures.append(f"http:{path}:{status}")
            continue

        if "text/html" not in content_type.lower():
            continue

        parser = Parser()
        parser.feed(body.decode("utf-8", errors="replace"))
        parser.close()

        pages += 1

        for attrs in parser.images:
            images += 1
            width = attrs.get("width", "").strip()
            height = attrs.get("height", "").strip()

            if (
                re.fullmatch(r"[1-9][0-9]*", width) is None
                or re.fullmatch(r"[1-9][0-9]*", height) is None
            ):
                failures.append(
                    f"{path}:{attrs.get('src','')}:"
                    f"width={width!r}:height={height!r}"
                )

    if failures:
        print("ForPrint image dimensions contract FAILED")
        print(f"failures={len(failures)}")
        for item in failures[:100]:
            print(item)
        raise SystemExit(1)

    print("[OK] ForPrint image dimensions contract")
    print(f"urls={len(route_paths)}")
    print(f"html_pages={pages}")
    print(f"img_instances={images}")
    print("missing_dimensions=0")
    print("production_release=deferred")


if __name__ == "__main__":
    main()
