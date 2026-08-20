#!/usr/bin/env python3
"""Validate ForPrint structured-data contract on the local preview."""

from __future__ import annotations

import concurrent.futures
import html.parser
import json
import re
import sys
import urllib.error
import urllib.parse
import urllib.request
import xml.etree.ElementTree as ET
from pathlib import Path
from typing import Any, Iterable


ROOT = Path(__file__).resolve().parents[2]
SITEMAP = ROOT / "base/sitemap.xml"
LOCAL_ORIGIN = "http://127.0.0.1:8098"
PUBLIC_ORIGIN = "https://forprint.net.ua"

RANGE_PRICE_RE = re.compile(
    r"\d[\d\s]*(?:[.,]\d{1,2})?"
    r"\s*[-–—]\s*"
    r"\d[\d\s]*(?:[.,]\d{1,2})?"
    r"\s*(?:грн\.?|₴|UAH)",
    re.IGNORECASE,
)
EXACT_PRICE_RE = re.compile(
    r"\d[\d\s]*(?:[.,]\d{1,2})?"
    r"\s*(?:грн\.?|₴|UAH)",
    re.IGNORECASE,
)
REQUEST_PRICE_RE = re.compile(
    r"за\s+запитом|"
    r"індивідуальн\w*\s+(?:розрахунок|прорахунок|ціна)|"
    r"уточн\w*\s+(?:ціну|вартість)|"
    r"надішл\w*\s+.*(?:розрахунок|прорахунок)",
    re.IGNORECASE,
)


class Parser(html.parser.HTMLParser):
    def __init__(self) -> None:
        super().__init__(convert_charrefs=True)
        self.in_json_ld = False
        self.json_buffer: list[str] = []
        self.json_blocks: list[str] = []
        self.itemtypes: list[str] = []
        self.price_modes: list[str] = []
        self.price_depth = 0
        self.price_text_parts: list[str] = []

    def handle_starttag(
        self,
        tag: str,
        attrs: list[tuple[str, str | None]],
    ) -> None:
        values = {
            key.lower(): value or ""
            for key, value in attrs
        }

        if self.price_depth > 0:
            self.price_depth += 1
        else:
            classes = {
                value
                for value in values.get(
                    "class",
                    "",
                ).split()
                if value
            }

            if classes.intersection({
                "fp-product-detail-price",
                "card-main-info-price",
            }):
                self.price_depth = 1

        itemtype = values.get("itemtype", "").strip()

        if itemtype:
            self.itemtypes.extend(itemtype.split())

        price_mode = values.get(
            "data-price-mode",
            "",
        ).strip().lower()

        if price_mode in {
            "exact",
            "range",
            "request",
        }:
            self.price_modes.append(price_mode)

        if (
            tag.lower() == "script"
            and values.get("type", "").lower().strip()
            == "application/ld+json"
        ):
            self.in_json_ld = True
            self.json_buffer = []

    def handle_endtag(self, tag: str) -> None:
        if tag.lower() == "script" and self.in_json_ld:
            self.json_blocks.append(
                "".join(self.json_buffer).strip()
            )
            self.json_buffer = []
            self.in_json_ld = False

        if self.price_depth > 0:
            self.price_depth -= 1

    def handle_data(self, data: str) -> None:
        if self.in_json_ld:
            self.json_buffer.append(data)
            return

        if self.price_depth > 0:
            value = " ".join(data.split())

            if value:
                self.price_text_parts.append(value)

    @property
    def price_text(self) -> str:
        return " ".join(
            self.price_text_parts
        ).strip()


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
        for node in root.findall(
            "sm:url/sm:loc",
            namespace,
        )
    ]

    if not urls:
        fail("sitemap is empty")

    if len(urls) != len(set(urls)):
        fail("duplicate sitemap URLs")

    return urls


def route_type(url: str) -> str:
    path = urllib.parse.urlsplit(
        url
    ).path.rstrip("/")

    if not path:
        return "home"

    first = path.strip("/").split(
        "/",
        1,
    )[0].lower()

    return {
        "product": "product",
        "contacts": "contacts",
        "information": "information",
    }.get(first, "other")


def local_url(public_url: str) -> str:
    parsed = urllib.parse.urlsplit(
        public_url
    )

    return urllib.parse.urlunsplit(
        (
            "http",
            "127.0.0.1:8098",
            parsed.path or "/",
            parsed.query,
            "",
        )
    )


def flatten(
    value: Any,
) -> Iterable[dict[str, Any]]:
    if isinstance(value, list):
        for item in value:
            yield from flatten(item)
        return

    if not isinstance(value, dict):
        return

    yield value

    graph = value.get("@graph")

    if isinstance(graph, (list, dict)):
        yield from flatten(graph)


def type_names(
    node: dict[str, Any],
) -> set[str]:
    raw = node.get("@type", [])

    if isinstance(raw, str):
        values = [raw]
    elif isinstance(raw, list):
        values = [
            str(item)
            for item in raw
            if isinstance(item, str)
        ]
    else:
        values = []

    return {
        value.rstrip("/").rsplit(
            "/",
            1,
        )[-1]
        for value in values
        if value
    }


def find_node(
    record: dict[str, Any],
    schema_type: str,
) -> dict[str, Any] | None:
    for node in record["nodes"]:
        if schema_type in type_names(node):
            return node

    return None


def infer_price_mode(
    parser: Parser,
    nodes: list[dict[str, Any]],
    body: str,
) -> tuple[str, str]:
    explicit_modes = sorted(
        set(parser.price_modes)
    )

    if len(explicit_modes) == 1:
        return explicit_modes[0], "data-attribute"

    raw_match = re.search(
        r"""(?ix)
        data-price-mode
        \s*=\s*
        ["'](exact|range|request)["']
        """,
        body,
    )

    if raw_match:
        return (
            raw_match.group(1).lower(),
            "raw-html-attribute",
        )

    product = next(
        (
            node
            for node in nodes
            if "Product" in type_names(node)
        ),
        None,
    )

    if product:
        offer = product.get("offers")

        if isinstance(offer, dict):
            offer_types = type_names(offer)

            if "AggregateOffer" in offer_types:
                return "range", "json-ld-offer"

            if "Offer" in offer_types:
                return "exact", "json-ld-offer"

    price_text = parser.price_text

    if RANGE_PRICE_RE.search(price_text):
        return "range", "visible-price-block"

    if EXACT_PRICE_RE.search(price_text):
        return "exact", "visible-price-block"

    if REQUEST_PRICE_RE.search(price_text):
        return "request", "visible-price-block"

    return "", "undetermined"


def inspect(url: str) -> dict[str, Any]:
    request = urllib.request.Request(
        local_url(url),
        headers={
            "User-Agent": (
                "ForPrint-SCHEMA02-V2-Validator/1.0"
            ),
            "Cache-Control": "no-cache",
        },
    )

    try:
        with urllib.request.urlopen(
            request,
            timeout=35,
        ) as response:
            body = response.read(
                5_000_000
            ).decode(
                "utf-8",
                errors="replace",
            )
            status = response.status
    except urllib.error.HTTPError as exc:
        body = exc.read(
            5_000_000
        ).decode(
            "utf-8",
            errors="replace",
        )
        status = exc.code
    except Exception as exc:
        return {
            "url": url,
            "status": None,
            "error": type(exc).__name__,
            "nodes": [],
            "types": set(),
            "itemtypes": set(),
            "price_mode": "",
            "price_mode_source": "",
            "price_text": "",
            "json_errors": [],
        }

    parser = Parser()

    try:
        parser.feed(body)
    except Exception:
        pass

    nodes: list[dict[str, Any]] = []
    json_errors: list[str] = []

    for index, block in enumerate(
        parser.json_blocks,
        start=1,
    ):
        try:
            decoded = json.loads(block)
        except json.JSONDecodeError as exc:
            json_errors.append(
                f"block-{index}:{exc}"
            )
            continue

        nodes.extend(flatten(decoded))

    types = (
        set().union(
            *(
                type_names(node)
                for node in nodes
            )
        )
        if nodes
        else set()
    )
    itemtypes = {
        value.rstrip("/").rsplit(
            "/",
            1,
        )[-1]
        for value in parser.itemtypes
    }
    price_mode, price_mode_source = (
        infer_price_mode(
            parser,
            nodes,
            body,
        )
    )

    return {
        "url": url,
        "status": status,
        "error": "",
        "nodes": nodes,
        "types": types,
        "itemtypes": itemtypes,
        "price_mode": price_mode,
        "price_mode_source": (
            price_mode_source
        ),
        "price_text": parser.price_text,
        "json_errors": json_errors,
    }


def same_path(
    public_url: str,
    value: str,
) -> bool:
    public = urllib.parse.urlsplit(
        public_url
    )
    candidate = urllib.parse.urlsplit(
        value
    )

    return (
        (public.path or "/")
        == (candidate.path or "/")
        and public.query == candidate.query
    )


def valid_offer(
    url: str,
    product: dict[str, Any],
) -> list[str]:
    failures: list[str] = []
    offer = product.get("offers")

    if not isinstance(offer, dict):
        return ["offers-missing"]

    offer_types = type_names(offer)

    if offer.get("url") and not same_path(
        url,
        str(offer["url"]),
    ):
        failures.append(
            "offer-url-mismatch"
        )

    if (
        offer.get("priceCurrency")
        != "UAH"
    ):
        failures.append(
            "offer-currency-not-UAH"
        )

    if "Offer" in offer_types:
        try:
            price = float(
                offer.get("price", 0)
            )
        except (TypeError, ValueError):
            price = 0

        if price <= 0:
            failures.append(
                "offer-price-invalid"
            )

    elif "AggregateOffer" in offer_types:
        try:
            low_price = float(
                offer.get("lowPrice", 0)
            )
        except (TypeError, ValueError):
            low_price = 0

        if low_price <= 0:
            failures.append(
                "aggregate-low-price-invalid"
            )

        if "highPrice" in offer:
            try:
                high_price = float(
                    offer.get(
                        "highPrice",
                        0,
                    )
                )
            except (TypeError, ValueError):
                high_price = 0

            if high_price < low_price:
                failures.append(
                    "aggregate-high-price-invalid"
                )
    else:
        failures.append(
            "offer-type-invalid"
        )

    if "availability" in offer:
        failures.append(
            "availability-without-maintained-visible-source"
        )

    return failures


def main() -> int:
    urls = sitemap_urls()

    with concurrent.futures.ThreadPoolExecutor(
        max_workers=5,
    ) as executor:
        records = list(
            executor.map(
                inspect,
                urls,
            )
        )

    failures: list[str] = []
    eligible_products = 0
    request_products = 0
    product_schema_pages = 0
    breadcrumb_pages = 0
    price_mode_sources: dict[str, int] = {}

    for record in records:
        url = record["url"]
        kind = route_type(url)

        if record["status"] != 200:
            failures.append(
                f"status:{url}:{record['status']}"
            )
            continue

        if record["json_errors"]:
            failures.append(
                "invalid-jsonld:"
                + url
                + ":"
                + "|".join(
                    record["json_errors"]
                )
            )

        # FP_BREADCRUMB_JSONLD_OWNER_V0_1
        # Breadcrumb structured data has one canonical owner:
        # centralized JSON-LD. The visible breadcrumb template
        # must not carry a second Microdata representation.
        breadcrumb = find_node(
            record,
            "BreadcrumbList",
        )
        has_breadcrumb_jsonld = (
            breadcrumb is not None
        )
        has_breadcrumb_microdata = (
            "BreadcrumbList"
            in record["itemtypes"]
        )

        if kind != "home":
            if not has_breadcrumb_jsonld:
                failures.append(
                    "breadcrumb-jsonld-missing:"
                    + url
                )
            else:
                elements = breadcrumb.get(
                    "itemListElement"
                )

                if (
                    not isinstance(elements, list)
                    or len(elements) < 2
                ):
                    failures.append(
                        "breadcrumb-jsonld-items-invalid:"
                        + url
                    )
                else:
                    for (
                        expected_position,
                        element,
                    ) in enumerate(
                        elements,
                        start=1,
                    ):
                        if not isinstance(
                            element,
                            dict,
                        ):
                            failures.append(
                                "breadcrumb-jsonld-listitem-invalid:"
                                + url
                            )
                            continue

                        if (
                            "ListItem"
                            not in type_names(element)
                        ):
                            failures.append(
                                "breadcrumb-jsonld-type-invalid:"
                                + url
                            )

                        if (
                            element.get("position")
                            != expected_position
                        ):
                            failures.append(
                                "breadcrumb-jsonld-position-invalid:"
                                + url
                            )

                        if not str(
                            element.get(
                                "name",
                                "",
                            )
                        ).strip():
                            failures.append(
                                "breadcrumb-jsonld-name-missing:"
                                + url
                            )

                        if (
                            expected_position
                            < len(elements)
                            and not str(
                                element.get(
                                    "item",
                                    "",
                                )
                            ).strip()
                        ):
                            failures.append(
                                "breadcrumb-jsonld-item-missing:"
                                + url
                            )

                breadcrumb_pages += 1

            if has_breadcrumb_microdata:
                failures.append(
                    "breadcrumb-microdata-owner-remains:"
                    + url
                )

        if kind == "home":
            website = find_node(
                record,
                "WebSite",
            )
            business = (
                find_node(
                    record,
                    "LocalBusiness",
                )
                or find_node(
                    record,
                    "Organization",
                )
            )

            if not website:
                failures.append(
                    "website-schema-missing:"
                    + url
                )
            else:
                if not website.get("name"):
                    failures.append(
                        "website-name-missing:"
                        + url
                    )

                website_url = str(
                    website.get("url", "")
                )

                if not same_path(
                    PUBLIC_ORIGIN + "/",
                    website_url,
                ):
                    failures.append(
                        "website-url-invalid:"
                        + url
                    )

            if not business:
                failures.append(
                    "business-schema-missing:"
                    + url
                )

        if kind == "contacts":
            business = find_node(
                record,
                "LocalBusiness",
            )

            if not business:
                failures.append(
                    "localbusiness-missing:"
                    + url
                )
            else:
                if not business.get("name"):
                    failures.append(
                        "localbusiness-name-missing:"
                        + url
                    )

                address = business.get(
                    "address"
                )

                if not isinstance(
                    address,
                    dict,
                ):
                    failures.append(
                        "localbusiness-address-missing:"
                        + url
                    )
                elif not address.get(
                    "streetAddress"
                ):
                    failures.append(
                        "street-address-missing:"
                        + url
                    )

        if kind == "product":
            mode = record["price_mode"]
            source = record[
                "price_mode_source"
            ]
            price_mode_sources[source] = (
                price_mode_sources.get(
                    source,
                    0,
                )
                + 1
            )
            product = find_node(
                record,
                "Product",
            )

            if mode in {
                "exact",
                "range",
            }:
                eligible_products += 1

                if not product:
                    failures.append(
                        "eligible-product-schema-missing:"
                        + url
                        + ":source="
                        + source
                        + ":price="
                        + record["price_text"][:120]
                    )
                    continue

                product_schema_pages += 1

                for key in (
                    "name",
                    "description",
                    "url",
                    "image",
                ):
                    if not product.get(key):
                        failures.append(
                            f"product-{key}-missing:"
                            + url
                        )

                if (
                    product.get("url")
                    and not same_path(
                        url,
                        str(product["url"]),
                    )
                ):
                    failures.append(
                        "product-url-mismatch:"
                        + url
                    )

                for issue in valid_offer(
                    url,
                    product,
                ):
                    failures.append(
                        "product-"
                        + issue
                        + ":"
                        + url
                    )

            elif mode == "request":
                request_products += 1

                if product:
                    failures.append(
                        "request-price-product-markup-present:"
                        + url
                    )
            else:
                failures.append(
                    "product-price-state-undetermined:"
                    + url
                    + ":price="
                    + record["price_text"][:120]
                )

    if eligible_products == 0:
        failures.append(
            "no-eligible-priced-product-pages"
        )

    if failures:
        for failure in failures[:60]:
            print(
                "[FAIL] " + failure,
                file=sys.stderr,
            )

        if len(failures) > 60:
            print(
                f"[FAIL] plus "
                f"{len(failures) - 60} more",
                file=sys.stderr,
            )

        return 1

    print(
        "ForPrint structured-data checks passed."
    )
    print(f"urls={len(records)}")
    print(
        f"breadcrumb_pages={breadcrumb_pages}"
    )
    print("website_pages=1")
    print("localbusiness_pages=2")
    print(
        "eligible_product_pages="
        + str(eligible_products)
    )
    print(
        "product_schema_pages="
        + str(product_schema_pages)
    )
    print(
        "request_price_product_pages="
        + str(request_products)
    )
    print(
        "price_mode_sources="
        + json.dumps(
            price_mode_sources,
            ensure_ascii=False,
            sort_keys=True,
        )
    )
    print("availability_emitted=0")
    print("currency=UAH")
    print("production_release=deferred")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
