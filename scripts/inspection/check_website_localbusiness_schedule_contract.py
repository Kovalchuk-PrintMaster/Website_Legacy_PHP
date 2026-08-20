#!/usr/bin/env python3
from __future__ import annotations

from html.parser import HTMLParser
import json
from urllib.request import Request, build_opener

ORIGIN = "http://127.0.0.1:8098"
ROUTE = "/contacts/"

EXPECTED = {
    "Monday": ("10:00", "19:30"),
    "Tuesday": ("10:00", "19:30"),
    "Wednesday": ("10:00", "19:30"),
    "Thursday": ("10:00", "19:30"),
    "Friday": ("10:00", "19:30"),
    "Saturday": ("12:00", "18:00"),
    "Sunday": ("12:00", "18:00"),
}


class Parser(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.blocks = []
        self.inside = False
        self.parts = []

    def handle_starttag(self, tag, attrs):
        values = {str(k).lower(): (v or "") for k, v in attrs}
        if (
            tag.lower() == "script"
            and values.get("type", "").lower()
            == "application/ld+json"
        ):
            self.inside = True
            self.parts = []

    def handle_data(self, data):
        if self.inside:
            self.parts.append(data)

    def handle_endtag(self, tag):
        if tag.lower() == "script" and self.inside:
            self.blocks.append("".join(self.parts).strip())
            self.inside = False
            self.parts = []


def walk(value, output):
    if isinstance(value, dict):
        if value.get("@type") == "LocalBusiness":
            output.append(value)
        for child in value.values():
            walk(child, output)
    elif isinstance(value, list):
        for child in value:
            walk(child, output)


def main():
    request = Request(
        ORIGIN + ROUTE,
        headers={"User-Agent": "ForPrintLocalBusinessScheduleContract/1.0"},
    )
    with build_opener().open(request, timeout=20) as response:
        if response.getcode() != 200:
            print(f"[FAIL] contacts HTTP {response.getcode()}")
            return 1
        html = response.read(5000000).decode("utf-8", errors="replace")

    parser = Parser()
    parser.feed(html)
    parser.close()

    nodes = []
    for raw in parser.blocks:
        walk(json.loads(raw), nodes)

    if len(nodes) != 1:
        print(f"[FAIL] LocalBusiness count={len(nodes)}")
        return 1

    hours = nodes[0].get("openingHoursSpecification")
    if not isinstance(hours, list):
        print("[FAIL] openingHoursSpecification missing")
        return 1

    actual = {}
    prefix = "https://schema.org/"

    for row in hours:
        if not isinstance(row, dict):
            continue
        if row.get("@type") != "OpeningHoursSpecification":
            continue

        day = str(row.get("dayOfWeek", ""))
        if day.startswith(prefix):
            day = day[len(prefix):]

        opens = str(row.get("opens", ""))
        closes = str(row.get("closes", ""))

        if day:
            actual[day] = (opens, closes)

    if actual != EXPECTED:
        print("[FAIL] LocalBusiness schedule mismatch")
        print("expected=" + json.dumps(EXPECTED, sort_keys=True))
        print("actual=" + json.dumps(actual, sort_keys=True))
        return 1

    print("[OK] LocalBusiness opening-hours contract")
    print("days=7")
    print("mon_fri=10:00-19:30")
    print("sat_sun=12:00-18:00")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
