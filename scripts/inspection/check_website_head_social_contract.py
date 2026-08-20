#!/usr/bin/env python3
from html.parser import HTMLParser
from urllib.request import Request, build_opener

ORIGIN = "http://127.0.0.1:8098"
ROUTES = ["/", "/contacts/", "/catalog/", "/nashi-posluhy/", "/product/eko-vzitki/"]

class Parser(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.links = []
        self.meta = []
    def handle_starttag(self, tag, attrs):
        values = {str(k).lower(): (v or "") for k, v in attrs}
        if tag.lower() == "link":
            self.links.append(values)
        elif tag.lower() == "meta":
            self.meta.append(values)

def fetch(route):
    request = Request(ORIGIN + route, headers={"User-Agent": "ForPrintHeadSocialContract/1.0"})
    with build_opener().open(request, timeout=20) as response:
        if response.getcode() != 200:
            raise RuntimeError(f"{route}: HTTP {response.getcode()}")
        return response.read(4000000).decode("utf-8", errors="replace")

def main():
    failures = []
    for route in ROUTES:
        parser = Parser()
        parser.feed(fetch(route))
        parser.close()

        icons = [
            x for x in parser.links
            if "icon" in x.get("rel", "").lower() and x.get("href", "").strip()
        ]
        if len(icons) != 1:
            failures.append(f"{route}:favicon-count:{len(icons)}")

        canonicals = [
            x.get("href", "").strip()
            for x in parser.links
            if "canonical" in x.get("rel", "").lower()
        ]
        canonical = canonicals[0] if len(canonicals) == 1 else ""
        if len(canonicals) != 1 or not canonical:
            failures.append(f"{route}:canonical-count:{len(canonicals)}")

        props = {}
        counts = {}
        for x in parser.meta:
            key = x.get("property", "").strip().lower()
            if key.startswith("og:"):
                counts[key] = counts.get(key, 0) + 1
                props[key] = x.get("content", "").strip()

        for key in ["og:title", "og:description", "og:url", "og:type", "og:site_name", "og:locale"]:
            if counts.get(key, 0) != 1:
                failures.append(f"{route}:{key}-count:{counts.get(key, 0)}")
            elif not props.get(key):
                failures.append(f"{route}:{key}-empty")

        if canonical and props.get("og:url") and props["og:url"] != canonical:
            failures.append(f"{route}:og-url-canonical-mismatch")

        if route.startswith("/product/") and counts.get("og:image", 0) != 1:
            failures.append(f"{route}:og:image-count:{counts.get('og:image', 0)}")

    if failures:
        for failure in failures:
            print("[FAIL] " + failure)
        return 1

    print("[OK] website head/social contract")
    print("favicon_owner=single")
    print("open_graph=canonical")
    return 0

if __name__ == "__main__":
    raise SystemExit(main())
