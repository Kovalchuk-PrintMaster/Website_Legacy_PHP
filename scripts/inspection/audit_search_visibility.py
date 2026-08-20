#!/usr/bin/env python3
from __future__ import annotations

import argparse
from collections import Counter, defaultdict, deque
import csv
from dataclasses import asdict, dataclass, field
from datetime import datetime, timezone
import gzip
from html.parser import HTMLParser
import json
from pathlib import Path
import re
import time
from typing import Iterable
from urllib.error import HTTPError, URLError
from urllib.parse import (
    parse_qsl,
    urlencode,
    urldefrag,
    urljoin,
    urlsplit,
    urlunsplit,
)
from urllib.request import (
    HTTPRedirectHandler,
    Request,
    build_opener,
)
from urllib.robotparser import RobotFileParser
import xml.etree.ElementTree as ET


DEFAULT_UA = "ForPrintSEOAudit/1.0 (+https://forprint.net.ua/)"
ROBOT_UA = "ForPrintSEOAudit"
GOOGLEBOT_UA = "Googlebot"

RESOURCE_EXTENSIONS = {
    ".7z", ".avi", ".avif", ".bmp", ".css", ".csv", ".doc", ".docx",
    ".eot", ".gif", ".gz", ".ico", ".jpeg", ".jpg", ".js", ".json",
    ".map", ".mov", ".mp3", ".mp4", ".mpeg", ".ogg", ".otf", ".pdf",
    ".png", ".rar", ".rss", ".svg", ".tar", ".tgz", ".tif", ".tiff",
    ".ttf", ".txt", ".wav", ".webm", ".webp", ".woff", ".woff2", ".xls",
    ".xlsx", ".xml", ".zip",
}

SAFE_SKIP_PREFIXES = (
    "/admin",
    "/administrator",
    "/account",
    "/cart",
    "/checkout",
    "/cgi-bin",
    "/communication-request.php",
    "/lk",
    "/test-home/",
)

GENERIC_TITLES = {
    "index",
    "home",
    "homepage",
    "головна",
    "главная",
}

SEVERITY_ORDER = {
    "P0": 0,
    "P1": 1,
    "P2": 2,
    "INFO": 3,
}


def utc_now() -> str:
    return datetime.now(timezone.utc).isoformat(timespec="seconds")


def clean_space(value: str) -> str:
    return re.sub(r"\s+", " ", value or "").strip()


def normalize_origin(url: str) -> tuple[str, str]:
    parts = urlsplit(url)
    scheme = parts.scheme.lower()
    host = (parts.hostname or "").lower()
    port = parts.port

    if port is not None:
        default = (scheme == "https" and port == 443) or (
            scheme == "http" and port == 80
        )
        if not default:
            host = f"{host}:{port}"

    return scheme, host


def normalize_url(
    url: str,
    *,
    base: str | None = None,
    keep_query: bool = True,
) -> str:
    if base:
        url = urljoin(base, url)

    url, _fragment = urldefrag(url)
    parts = urlsplit(url)

    if parts.scheme.lower() not in {"http", "https"}:
        return ""

    scheme, host = normalize_origin(url)
    path = parts.path or "/"

    # Do not normalize trailing slash or case: those are SEO audit signals.
    query = parts.query if keep_query else ""

    return urlunsplit((scheme, host, path, query, ""))


def path_without_query(url: str) -> str:
    parts = urlsplit(url)
    return urlunsplit(
        (parts.scheme, parts.netloc, parts.path or "/", "", "")
    )


def is_resource_url(url: str) -> bool:
    path = urlsplit(url).path.lower()
    suffix = Path(path).suffix
    return suffix in RESOURCE_EXTENSIONS


def page_type(url: str, base_url: str) -> str:
    path = urlsplit(url).path.rstrip("/") or "/"

    if path == urlsplit(base_url).path.rstrip("/") or (
        path == "/" and urlsplit(base_url).path in {"", "/"}
    ):
        return "home"
    if path.startswith("/product/"):
        return "product"
    if path == "/catalog" or path.startswith("/catalog/"):
        return "catalog"
    if path == "/search" or path.startswith("/search/"):
        return "search"
    if path == "/contacts" or path.startswith("/contacts/"):
        return "contacts"
    if path.startswith("/news/"):
        return "news"
    if path.startswith("/article/") or path.startswith("/stati/"):
        return "article"
    if path.startswith("/services/") or path.startswith("/nashi-posluhy"):
        return "service"
    return "other"


class RedirectRecorder(HTTPRedirectHandler):
    def __init__(self) -> None:
        super().__init__()
        self.chain: list[dict] = []

    def reset(self) -> None:
        self.chain = []

    def redirect_request(
        self,
        req,
        fp,
        code,
        msg,
        headers,
        newurl,
    ):
        self.chain.append(
            {
                "status": int(code),
                "from": req.full_url,
                "to": newurl,
            }
        )
        return super().redirect_request(
            req,
            fp,
            code,
            msg,
            headers,
            newurl,
        )


class Fetcher:
    def __init__(
        self,
        *,
        user_agent: str,
        timeout: float,
        max_bytes: int,
        delay: float,
    ) -> None:
        self.user_agent = user_agent
        self.timeout = timeout
        self.max_bytes = max_bytes
        self.delay = delay
        self.redirect_handler = RedirectRecorder()
        self.opener = build_opener(self.redirect_handler)
        self.last_request_at = 0.0

    def _pace(self) -> None:
        if self.delay <= 0:
            return

        elapsed = time.monotonic() - self.last_request_at
        if elapsed < self.delay:
            time.sleep(self.delay - elapsed)

    def fetch(
        self,
        url: str,
        *,
        accept: str = "text/html,application/xhtml+xml,*/*;q=0.2",
    ) -> dict:
        self._pace()
        self.redirect_handler.reset()

        request = Request(
            url,
            headers={
                "User-Agent": self.user_agent,
                "Accept": accept,
                "Accept-Encoding": "identity",
            },
            method="GET",
        )

        started = time.monotonic()
        body = b""
        headers = None
        final_url = url
        status = None
        error = ""
        truncated = False

        try:
            response = self.opener.open(
                request,
                timeout=self.timeout,
            )
            self.last_request_at = time.monotonic()
            status = int(response.getcode())
            headers = response.headers
            final_url = response.geturl()
            body = response.read(self.max_bytes + 1)
            if len(body) > self.max_bytes:
                body = body[: self.max_bytes]
                truncated = True
        except HTTPError as exc:
            self.last_request_at = time.monotonic()
            status = int(exc.code)
            headers = exc.headers
            final_url = exc.geturl()
            try:
                body = exc.read(self.max_bytes + 1)
                if len(body) > self.max_bytes:
                    body = body[: self.max_bytes]
                    truncated = True
            except Exception:
                body = b""
        except (URLError, TimeoutError, OSError) as exc:
            self.last_request_at = time.monotonic()
            error = str(exc)

        elapsed_ms = int(
            round((time.monotonic() - started) * 1000)
        )

        header_dict = {}

        if headers is not None:
            for key in headers.keys():
                values = headers.get_all(key) or []
                header_dict[key.lower()] = ", ".join(values)

        return {
            "requested_url": url,
            "final_url": normalize_url(final_url) or final_url,
            "status": status,
            "headers": header_dict,
            "body": body,
            "redirects": list(self.redirect_handler.chain),
            "error": error,
            "response_time_ms": elapsed_ms,
            "truncated": truncated,
        }


def decode_body(body: bytes, content_type: str) -> str:
    charset = ""

    match = re.search(
        r"charset\s*=\s*['\"]?([A-Za-z0-9._-]+)",
        content_type or "",
        re.IGNORECASE,
    )
    if match:
        charset = match.group(1)

    for candidate in (charset, "utf-8", "windows-1251"):
        if not candidate:
            continue
        try:
            return body.decode(candidate)
        except (LookupError, UnicodeDecodeError):
            continue

    return body.decode("utf-8", errors="replace")


class PageParser(HTMLParser):
    def __init__(self, document_url: str) -> None:
        super().__init__(convert_charrefs=True)
        self.document_url = document_url

        self.title_parts: list[str] = []
        self.in_title = False

        self.heading_level: int | None = None
        self.heading_parts: list[str] = []
        self.headings: list[tuple[int, str]] = []

        self.meta: list[dict[str, str]] = []
        self.links: list[dict[str, str]] = []
        self.anchors: list[dict[str, str]] = []
        self.images: list[dict[str, str]] = []

        self.html_lang = ""
        self.base_href = ""

        self.in_ld_json = False
        self.ld_json_parts: list[str] = []
        self.jsonld_blocks: list[str] = []

    def handle_starttag(self, tag: str, attrs) -> None:
        attrs_dict = {
            (key or "").lower(): value or ""
            for key, value in attrs
        }
        tag = tag.lower()

        if tag == "html":
            self.html_lang = clean_space(attrs_dict.get("lang", ""))

        elif tag == "title":
            self.in_title = True
            self.title_parts = []

        elif re.fullmatch(r"h[1-6]", tag):
            self.heading_level = int(tag[1])
            self.heading_parts = []

        elif tag == "meta":
            self.meta.append(attrs_dict)

        elif tag == "link":
            self.links.append(attrs_dict)

        elif tag == "a":
            self.anchors.append(attrs_dict)

        elif tag == "img":
            self.images.append(attrs_dict)

        elif tag == "base":
            self.base_href = attrs_dict.get("href", "")

        elif tag == "script":
            if (
                attrs_dict.get("type", "").lower()
                == "application/ld+json"
            ):
                self.in_ld_json = True
                self.ld_json_parts = []

    def handle_endtag(self, tag: str) -> None:
        tag = tag.lower()

        if tag == "title":
            self.in_title = False

        elif (
            self.heading_level is not None
            and tag == f"h{self.heading_level}"
        ):
            text = clean_space("".join(self.heading_parts))
            self.headings.append((self.heading_level, text))
            self.heading_level = None
            self.heading_parts = []

        elif tag == "script" and self.in_ld_json:
            self.jsonld_blocks.append(
                "".join(self.ld_json_parts).strip()
            )
            self.in_ld_json = False
            self.ld_json_parts = []

    def handle_data(self, data: str) -> None:
        if self.in_title:
            self.title_parts.append(data)

        if self.heading_level is not None:
            self.heading_parts.append(data)

        if self.in_ld_json:
            self.ld_json_parts.append(data)

    def title(self) -> str:
        return clean_space("".join(self.title_parts))


def first_meta(
    meta: list[dict[str, str]],
    *,
    name: str | None = None,
    prop: str | None = None,
) -> str:
    for item in meta:
        if name and item.get("name", "").lower() == name.lower():
            return clean_space(item.get("content", ""))
        if prop and item.get("property", "").lower() == prop.lower():
            return clean_space(item.get("content", ""))
    return ""


def link_rel_values(item: dict[str, str]) -> set[str]:
    return {
        value.strip().lower()
        for value in re.split(r"\s+", item.get("rel", ""))
        if value.strip()
    }


def first_link_rel(
    links: list[dict[str, str]],
    rel: str,
    document_url: str,
) -> str:
    for item in links:
        if rel.lower() in link_rel_values(item):
            href = item.get("href", "")
            return normalize_url(href, base=document_url)
    return ""


def parse_hreflang(
    links: list[dict[str, str]],
    document_url: str,
) -> list[dict[str, str]]:
    output = []

    for item in links:
        if "alternate" not in link_rel_values(item):
            continue

        lang = clean_space(item.get("hreflang", "")).lower()
        href = normalize_url(
            item.get("href", ""),
            base=document_url,
        )

        if lang and href:
            output.append(
                {
                    "hreflang": lang,
                    "url": href,
                }
            )

    return output


def jsonld_types(value) -> set[str]:
    output: set[str] = set()

    if isinstance(value, dict):
        raw_type = value.get("@type")
        if isinstance(raw_type, str):
            output.add(raw_type)
        elif isinstance(raw_type, list):
            for item in raw_type:
                if isinstance(item, str):
                    output.add(item)

        for child in value.values():
            output.update(jsonld_types(child))

    elif isinstance(value, list):
        for child in value:
            output.update(jsonld_types(child))

    return output


def parse_jsonld(blocks: list[str]) -> tuple[list[str], int]:
    types: set[str] = set()
    errors = 0

    for block in blocks:
        if not block:
            continue

        try:
            value = json.loads(block)
        except json.JSONDecodeError:
            errors += 1
            continue

        types.update(jsonld_types(value))

    return sorted(types), errors


def heading_gap_count(headings: list[tuple[int, str]]) -> int:
    gaps = 0
    previous = None

    for level, _text in headings:
        if previous is not None and level > previous + 1:
            gaps += 1
        previous = level

    return gaps


def parse_robots_directives(
    meta_robots: str,
    x_robots: str,
) -> set[str]:
    combined = f"{meta_robots},{x_robots}".lower()
    return {
        token.strip()
        for token in re.split(r"[,;\s]+", combined)
        if token.strip()
    }


@dataclass
class PageRecord:
    url: str
    requested_url: str = ""
    final_url: str = ""
    status: int | None = None
    final_status: int | None = None
    content_type: str = ""
    response_time_ms: int | None = None
    body_bytes: int = 0
    truncated: bool = False
    fetch_error: str = ""

    page_type: str = ""
    discovery: str = ""
    sitemap_member: bool = False
    inbound_internal_links: int = 0

    robots_allowed_audit: bool | None = None
    robots_allowed_googlebot: bool | None = None
    blocked_by_safe_policy: bool = False

    title: str = ""
    title_length: int = 0
    description: str = ""
    description_length: int = 0
    html_lang: str = ""

    canonical: str = ""
    meta_robots: str = ""
    meta_googlebot: str = ""
    x_robots_tag: str = ""
    indexability: str = ""
    indexability_reason: str = ""

    h1_count: int = 0
    h1_text: str = ""
    h2_count: int = 0
    h3_count: int = 0
    heading_gap_count: int = 0

    og_type: str = ""
    og_title: str = ""
    og_description: str = ""
    og_url: str = ""
    og_image: str = ""
    og_site_name: str = ""
    og_locale: str = ""
    twitter_card: str = ""

    hreflang_count: int = 0
    hreflang: str = ""

    jsonld_types: str = ""
    jsonld_parse_errors: int = 0

    internal_links: int = 0
    external_links: int = 0
    query_links: int = 0
    resource_links: int = 0

    images: int = 0
    images_missing_alt: int = 0
    images_empty_alt: int = 0
    images_missing_dimensions: int = 0

    redirect_count: int = 0
    redirect_chain: str = ""


@dataclass
class Issue:
    severity: str
    code: str
    url: str
    message: str
    evidence: str = ""


class Audit:
    def __init__(self, args) -> None:
        self.args = args
        self.base_url = normalize_url(
            args.base_url,
            keep_query=False,
        )
        if not self.base_url:
            raise ValueError("Invalid --base-url")

        if not self.base_url.endswith("/"):
            self.base_url += "/"

        self.origin = normalize_origin(self.base_url)
        self.fetcher = Fetcher(
            user_agent=args.user_agent,
            timeout=args.timeout,
            max_bytes=args.max_bytes,
            delay=args.delay,
        )

        self.records: dict[str, PageRecord] = {}
        self.issues: list[Issue] = []
        self.redirects: list[dict] = []
        self.edges: list[dict] = []
        self.images: list[dict] = []
        self.hreflang_rows: list[dict] = []
        self.query_links: Counter[str] = Counter()
        self.resources: Counter[str] = Counter()

        self.inbound = Counter()
        self.discovery = defaultdict(set)

        self.sitemap_urls: set[str] = set()
        self.sitemap_documents: list[dict] = []
        self.sitemap_external_urls: set[str] = set()

        self.robots_raw = ""
        self.robots_status: int | None = None
        self.robots_error = ""
        self.robot_parser = RobotFileParser()
        self.googlebot_parser = RobotFileParser()

        self.fetched_html = 0

    def same_origin(self, url: str) -> bool:
        return normalize_origin(url) == self.origin

    def safe_skip(self, url: str) -> bool:
        path = urlsplit(url).path
        return any(
            path == prefix.rstrip("/")
            or path.startswith(prefix)
            for prefix in SAFE_SKIP_PREFIXES
        )

    def fetch_robots(self) -> None:
        robots_url = urljoin(self.base_url, "/robots.txt")
        response = self.fetcher.fetch(
            robots_url,
            accept="text/plain,*/*;q=0.2",
        )
        self.robots_status = response["status"]
        self.robots_error = response["error"]

        if response["status"] == 200:
            content_type = response["headers"].get(
                "content-type",
                "text/plain",
            )
            self.robots_raw = decode_body(
                response["body"],
                content_type,
            )
            lines = self.robots_raw.splitlines()
            self.robot_parser.set_url(robots_url)
            self.robot_parser.parse(lines)
            self.googlebot_parser.set_url(robots_url)
            self.googlebot_parser.parse(lines)
        else:
            self.robot_parser.set_url(robots_url)
            self.robot_parser.parse([])
            self.googlebot_parser.set_url(robots_url)
            self.googlebot_parser.parse([])

    def robots_allowed(self, url: str, user_agent: str) -> bool:
        if self.robots_status != 200:
            return True

        parser = (
            self.googlebot_parser
            if user_agent == GOOGLEBOT_UA
            else self.robot_parser
        )
        return parser.can_fetch(user_agent, url)

    def sitemap_hints(self) -> list[str]:
        hints = []

        for line in self.robots_raw.splitlines():
            if line.lower().startswith("sitemap:"):
                value = line.split(":", 1)[1].strip()
                normalized = normalize_url(
                    value,
                    base=self.base_url,
                )
                if normalized:
                    hints.append(normalized)

        default = urljoin(self.base_url, "/sitemap.xml")
        if default not in hints:
            hints.append(default)

        for explicit in self.args.sitemap:
            normalized = normalize_url(
                explicit,
                base=self.base_url,
            )
            if normalized and normalized not in hints:
                hints.append(normalized)

        return hints

    def parse_sitemap_document(
        self,
        url: str,
        *,
        depth: int,
        seen: set[str],
    ) -> None:
        if depth > 5 or url in seen:
            return
        seen.add(url)

        response = self.fetcher.fetch(
            url,
            accept="application/xml,text/xml,*/*;q=0.2",
        )

        entry = {
            "url": url,
            "status": response["status"],
            "error": response["error"],
            "kind": "",
            "urls": 0,
        }
        self.sitemap_documents.append(entry)

        if response["status"] != 200:
            return

        body = response["body"]

        if urlsplit(url).path.lower().endswith(".gz") or body[:2] == b"\x1f\x8b":
            try:
                body = gzip.decompress(body)
            except OSError:
                entry["error"] = "invalid gzip sitemap"
                return

        try:
            root = ET.fromstring(body)
        except ET.ParseError as exc:
            entry["error"] = f"XML parse error: {exc}"
            return

        root_name = root.tag.rsplit("}", 1)[-1].lower()
        entry["kind"] = root_name

        locs = []
        for element in root.iter():
            if element.tag.rsplit("}", 1)[-1].lower() == "loc":
                value = clean_space(element.text or "")
                if value:
                    locs.append(value)

        entry["urls"] = len(locs)

        if root_name == "sitemapindex":
            for child in locs:
                normalized = normalize_url(
                    child,
                    base=self.base_url,
                )
                if normalized:
                    self.parse_sitemap_document(
                        normalized,
                        depth=depth + 1,
                        seen=seen,
                    )
            return

        if root_name == "urlset":
            for child in locs:
                normalized = normalize_url(
                    child,
                    base=self.base_url,
                )
                if not normalized:
                    continue
                if self.same_origin(normalized):
                    self.sitemap_urls.add(normalized)
                else:
                    self.sitemap_external_urls.add(normalized)

    def discover_sitemaps(self) -> None:
        seen: set[str] = set()
        for hint in self.sitemap_hints():
            self.parse_sitemap_document(
                hint,
                depth=0,
                seen=seen,
            )

    def queue_candidate(
        self,
        queue: deque[str],
        queued: set[str],
        url: str,
        *,
        source: str,
    ) -> None:
        normalized = normalize_url(url)
        if not normalized or not self.same_origin(normalized):
            return

        self.discovery[normalized].add(source)

        if normalized in queued or normalized in self.records:
            return

        if is_resource_url(normalized):
            self.resources[normalized] += 1
            return

        if "?" in normalized:
            self.query_links[normalized] += 1
            normalized = path_without_query(normalized)
            self.discovery[normalized].add("query-base")

        if normalized not in queued and normalized not in self.records:
            queue.append(normalized)
            queued.add(normalized)

    def analyze_page(
        self,
        url: str,
        response: dict,
    ) -> tuple[PageRecord, list[str]]:
        redirects = response["redirects"]
        requested_status = (
            int(redirects[0]["status"])
            if redirects
            else response["status"]
        )

        record = PageRecord(
            url=url,
            requested_url=response["requested_url"],
            final_url=response["final_url"],
            status=requested_status,
            final_status=response["status"],
            content_type=response["headers"].get(
                "content-type",
                "",
            ),
            response_time_ms=response["response_time_ms"],
            body_bytes=len(response["body"]),
            truncated=response["truncated"],
            fetch_error=response["error"],
            page_type=page_type(url, self.base_url),
            discovery="; ".join(
                sorted(self.discovery.get(url, {"crawl"}))
            ),
            sitemap_member=url in self.sitemap_urls,
            robots_allowed_audit=self.robots_allowed(
                url,
                ROBOT_UA,
            ),
            robots_allowed_googlebot=self.robots_allowed(
                url,
                GOOGLEBOT_UA,
            ),
        )

        # SEO_REDIRECT_SEMANTICS_V0_2
        # `status` is the status of the requested URL. `final_status` is the
        # final response after redirects. This prevents a 301 source URL from
        # being treated as an indexable 200 duplicate.
        record.redirect_count = len(redirects)
        record.redirect_chain = " | ".join(
            f'{item["status"]}:{item["from"]}->{item["to"]}'
            for item in redirects
        )

        for item in redirects:
            self.redirects.append(
                {
                    "source_url": url,
                    **item,
                }
            )

        status = response["status"]
        content_type = record.content_type.lower()

        if (
            status != 200
            or "html" not in content_type
        ):
            return record, []

        self.fetched_html += 1
        html = decode_body(
            response["body"],
            record.content_type,
        )
        parser = PageParser(
            response["final_url"] or url
        )

        try:
            parser.feed(html)
            parser.close()
        except Exception as exc:
            record.fetch_error = (
                record.fetch_error
                + f" HTML parser error: {exc}"
            ).strip()

        document_url = response["final_url"] or url
        base_for_links = (
            normalize_url(parser.base_href, base=document_url)
            if parser.base_href
            else document_url
        )

        record.title = parser.title()
        record.title_length = len(record.title)

        record.description = first_meta(
            parser.meta,
            name="description",
        )
        record.description_length = len(record.description)
        record.html_lang = parser.html_lang

        record.canonical = first_link_rel(
            parser.links,
            "canonical",
            document_url,
        )
        record.meta_robots = first_meta(
            parser.meta,
            name="robots",
        )
        record.meta_googlebot = first_meta(
            parser.meta,
            name="googlebot",
        )
        record.x_robots_tag = response["headers"].get(
            "x-robots-tag",
            "",
        )

        directives = parse_robots_directives(
            record.meta_robots + "," + record.meta_googlebot,
            record.x_robots_tag,
        )

        if record.redirect_count:
            record.indexability = "redirect"
            record.indexability_reason = (
                f"requested URL redirects to {record.final_url}"
            )
        elif (
            record.robots_allowed_googlebot is False
        ):
            record.indexability = "not-indexable"
            record.indexability_reason = "robots.txt blocks Googlebot"
        elif "noindex" in directives or "none" in directives:
            record.indexability = "not-indexable"
            record.indexability_reason = "noindex"
        else:
            record.indexability = "indexable"
            record.indexability_reason = "200 HTML; no noindex detected"

        h1 = [
            text
            for level, text in parser.headings
            if level == 1
        ]
        record.h1_count = len(h1)
        record.h1_text = " | ".join(h1)
        record.h2_count = sum(
            1 for level, _text in parser.headings
            if level == 2
        )
        record.h3_count = sum(
            1 for level, _text in parser.headings
            if level == 3
        )
        record.heading_gap_count = heading_gap_count(
            parser.headings
        )

        record.og_type = first_meta(
            parser.meta,
            prop="og:type",
        )
        record.og_title = first_meta(
            parser.meta,
            prop="og:title",
        )
        record.og_description = first_meta(
            parser.meta,
            prop="og:description",
        )
        record.og_url = first_meta(
            parser.meta,
            prop="og:url",
        )
        if record.og_url:
            record.og_url = normalize_url(
                record.og_url,
                base=document_url,
            )
        record.og_image = first_meta(
            parser.meta,
            prop="og:image",
        )
        if record.og_image:
            record.og_image = normalize_url(
                record.og_image,
                base=document_url,
            )
        record.og_site_name = first_meta(
            parser.meta,
            prop="og:site_name",
        )
        record.og_locale = first_meta(
            parser.meta,
            prop="og:locale",
        )
        record.twitter_card = first_meta(
            parser.meta,
            name="twitter:card",
        )

        hreflang = parse_hreflang(
            parser.links,
            document_url,
        )
        record.hreflang_count = len(hreflang)
        record.hreflang = " | ".join(
            f'{item["hreflang"]}:{item["url"]}'
            for item in hreflang
        )
        for item in hreflang:
            self.hreflang_rows.append(
                {
                    "source_url": url,
                    **item,
                }
            )

        types, ld_errors = parse_jsonld(
            parser.jsonld_blocks
        )
        record.jsonld_types = " | ".join(types)
        record.jsonld_parse_errors = ld_errors

        crawl_candidates = []

        for anchor in parser.anchors:
            raw_href = clean_space(anchor.get("href", ""))
            if not raw_href:
                continue

            if re.match(
                r"^(?:mailto|tel|javascript|data):",
                raw_href,
                re.IGNORECASE,
            ):
                continue

            full = normalize_url(
                raw_href,
                base=base_for_links,
            )
            if not full:
                continue

            if self.same_origin(full):
                if is_resource_url(full):
                    record.resource_links += 1
                    self.resources[full] += 1
                    continue

                if "?" in full:
                    record.query_links += 1
                    self.query_links[full] += 1

                record.internal_links += 1
                target = (
                    path_without_query(full)
                    if "?" in full
                    else full
                )
                self.inbound[target] += 1
                self.edges.append(
                    {
                        "source_url": url,
                        "target_url": full,
                        "crawl_target": target,
                    }
                )
                crawl_candidates.append(full)
            else:
                record.external_links += 1

        for image in parser.images:
            raw_src = (
                image.get("src")
                or image.get("data-src")
                or image.get("data-lazy-src")
                or ""
            )
            src = normalize_url(
                raw_src,
                base=base_for_links,
            )
            alt_present = "alt" in image
            alt_value = image.get("alt", "")
            width = clean_space(image.get("width", ""))
            height = clean_space(image.get("height", ""))

            record.images += 1
            if not alt_present:
                record.images_missing_alt += 1
            elif alt_value == "":
                record.images_empty_alt += 1

            if not width or not height:
                record.images_missing_dimensions += 1

            self.images.append(
                {
                    "page_url": url,
                    "image_url": src or raw_src,
                    "alt_present": alt_present,
                    "alt": alt_value,
                    "width": width,
                    "height": height,
                    "loading": image.get("loading", ""),
                    "fetchpriority": image.get(
                        "fetchpriority",
                        "",
                    ),
                }
            )

        return record, crawl_candidates

    def crawl(self) -> None:
        queue: deque[str] = deque()
        queued: set[str] = set()

        self.queue_candidate(
            queue,
            queued,
            self.base_url,
            source="base",
        )

        for seed in self.args.seed:
            normalized = normalize_url(
                seed,
                base=self.base_url,
            )
            if normalized:
                self.queue_candidate(
                    queue,
                    queued,
                    normalized,
                    source="explicit-seed",
                )

        for sitemap_url in sorted(self.sitemap_urls):
            self.queue_candidate(
                queue,
                queued,
                sitemap_url,
                source="sitemap",
            )

        while queue and len(self.records) < self.args.max_pages:
            url = queue.popleft()

            if url in self.records:
                continue

            if self.safe_skip(url):
                self.records[url] = PageRecord(
                    url=url,
                    page_type=page_type(
                        url,
                        self.base_url,
                    ),
                    discovery="; ".join(
                        sorted(self.discovery.get(url, {"crawl"}))
                    ),
                    sitemap_member=url in self.sitemap_urls,
                    robots_allowed_audit=self.robots_allowed(
                        url,
                        ROBOT_UA,
                    ),
                    robots_allowed_googlebot=self.robots_allowed(
                        url,
                        GOOGLEBOT_UA,
                    ),
                    blocked_by_safe_policy=True,
                    indexability="not-crawled",
                    indexability_reason="safe audit skip policy",
                )
                continue

            audit_allowed = self.robots_allowed(
                url,
                ROBOT_UA,
            )

            if not audit_allowed and not self.args.ignore_robots:
                self.records[url] = PageRecord(
                    url=url,
                    page_type=page_type(
                        url,
                        self.base_url,
                    ),
                    discovery="; ".join(
                        sorted(self.discovery.get(url, {"crawl"}))
                    ),
                    sitemap_member=url in self.sitemap_urls,
                    robots_allowed_audit=False,
                    robots_allowed_googlebot=self.robots_allowed(
                        url,
                        GOOGLEBOT_UA,
                    ),
                    indexability="not-crawled",
                    indexability_reason="robots.txt blocks audit crawler",
                )
                continue

            response = self.fetcher.fetch(url)
            record, candidates = self.analyze_page(
                url,
                response,
            )
            self.records[url] = record

            for candidate in candidates:
                self.queue_candidate(
                    queue,
                    queued,
                    candidate,
                    source=f"link:{url}",
                )

        for url, record in self.records.items():
            record.inbound_internal_links = self.inbound[url]

        if queue:
            self.issues.append(
                Issue(
                    severity="P1",
                    code="crawl_limit_reached",
                    url=self.base_url,
                    message=(
                        f"Crawl stopped at max-pages={self.args.max_pages}; "
                        f"{len(queue)} URL(s) remained queued."
                    ),
                )
            )

    def issue(
        self,
        severity: str,
        code: str,
        record: PageRecord,
        message: str,
        evidence: str = "",
    ) -> None:
        self.issues.append(
            Issue(
                severity=severity,
                code=code,
                url=record.url,
                message=message,
                evidence=evidence,
            )
        )

    def evaluate(self) -> None:
        if self.robots_status != 200:
            self.issues.append(
                Issue(
                    "P1",
                    "robots_missing_or_unreachable",
                    urljoin(self.base_url, "/robots.txt"),
                    "robots.txt did not return HTTP 200.",
                    f"status={self.robots_status}; error={self.robots_error}",
                )
            )

        for sitemap_url in sorted(self.sitemap_urls):
            if urlsplit(sitemap_url).query:
                self.issues.append(
                    Issue(
                        "P1",
                        "sitemap_contains_query_url",
                        sitemap_url,
                        "Sitemap contains a query-parameter URL; canonical sitemap policy expects stable preferred URLs.",
                    )
                )

        for external_url in sorted(self.sitemap_external_urls):
            self.issues.append(
                Issue(
                    "INFO",
                    "sitemap_external_origin_url",
                    external_url,
                    "Sitemap references a URL outside the audited origin; verify this is intentional.",
                )
            )

        valid_sitemaps = [
            item
            for item in self.sitemap_documents
            if item["status"] == 200
            and not item["error"]
        ]
        if not valid_sitemaps:
            self.issues.append(
                Issue(
                    "P1",
                    "sitemap_missing_or_invalid",
                    self.base_url,
                    "No valid XML sitemap was discovered.",
                )
            )

        for record in self.records.values():
            status = record.status

            if record.blocked_by_safe_policy:
                if record.sitemap_member:
                    self.issue(
                        "P0",
                        "sitemap_contains_safe_skipped_private_surface",
                        record,
                        "Sitemap contains a URL in the audit private/diagnostic skip policy.",
                    )
                continue

            if record.indexability == "not-crawled":
                if record.sitemap_member:
                    self.issue(
                        "P0",
                        "sitemap_url_blocked_by_robots",
                        record,
                        "Sitemap URL cannot be crawled by the audit crawler/robots policy.",
                    )
                continue

            if status is None:
                self.issue(
                    "P0",
                    "fetch_failed",
                    record,
                    "URL could not be fetched.",
                    record.fetch_error,
                )
                continue

            if status >= 500:
                self.issue(
                    "P0",
                    "server_error",
                    record,
                    f"Server returned HTTP {status}.",
                )
                continue

            if status in {404, 410}:
                severity = (
                    "P1"
                    if record.inbound_internal_links > 0
                    or record.sitemap_member
                    else "INFO"
                )
                self.issue(
                    severity,
                    "missing_url",
                    record,
                    f"URL returned HTTP {status}.",
                )
                continue

            if 400 <= status < 500:
                self.issue(
                    "P1",
                    "client_error",
                    record,
                    f"URL returned HTTP {status}.",
                )
                continue

            if record.redirect_count:
                if record.sitemap_member:
                    self.issue(
                        "P1",
                        "sitemap_contains_redirect",
                        record,
                        "Sitemap URL redirects instead of returning its canonical 200 URL.",
                        record.redirect_chain,
                    )
                if record.redirect_count > 1:
                    self.issue(
                        "P2",
                        "redirect_chain",
                        record,
                        f"URL has {record.redirect_count} redirects.",
                        record.redirect_chain,
                    )
                continue

            if status != 200 or "html" not in record.content_type.lower():
                continue

            if record.sitemap_member and record.indexability != "indexable":
                self.issue(
                    "P0",
                    "sitemap_contains_nonindexable",
                    record,
                    "Sitemap contains a non-indexable HTML page.",
                    record.indexability_reason,
                )

            if (
                record.indexability == "indexable"
                and valid_sitemaps
                and not record.sitemap_member
            ):
                self.issue(
                    "P1",
                    "indexable_url_missing_from_sitemap",
                    record,
                    "Indexable 200 HTML URL is absent from the discovered sitemap.",
                )

            if not record.title:
                self.issue(
                    "P1",
                    "missing_title",
                    record,
                    "Indexable HTML page has no title.",
                )
            elif record.title.lower() in GENERIC_TITLES:
                self.issue(
                    "P1",
                    "generic_title",
                    record,
                    "Page title is generic and not descriptive.",
                    record.title,
                )
            elif record.title_length < 15 or record.title_length > 70:
                self.issue(
                    "P2",
                    "title_length_heuristic",
                    record,
                    "Title length is outside the audit review range 15–70 characters.",
                    f"length={record.title_length}",
                )

            if (
                record.page_type == "search"
                and record.indexability == "indexable"
            ):
                self.issue(
                    "P1",
                    "internal_search_indexable",
                    record,
                    "Internal search surface is indexable; project search policy expects search results to be non-indexable.",
                )

            if record.indexability == "indexable":
                if not record.description:
                    self.issue(
                        "P1",
                        "missing_meta_description",
                        record,
                        "Indexable page has no meta description.",
                    )
                elif (
                    record.description_length < 50
                    or record.description_length > 180
                ):
                    self.issue(
                        "P2",
                        "description_length_heuristic",
                        record,
                        "Meta description is outside the audit review range 50–180 characters.",
                        f"length={record.description_length}",
                    )

                if not record.canonical:
                    self.issue(
                        "P1",
                        "missing_canonical",
                        record,
                        "Indexable page has no canonical URL.",
                    )
                elif not self.same_origin(record.canonical):
                    self.issue(
                        "P1",
                        "external_canonical",
                        record,
                        "Canonical points outside the audited origin.",
                        record.canonical,
                    )
                elif (
                    record.sitemap_member
                    and record.canonical != record.url
                ):
                    self.issue(
                        "P1",
                        "sitemap_url_canonical_mismatch",
                        record,
                        "Sitemap URL canonical points to another URL.",
                        record.canonical,
                    )

                if record.h1_count == 0:
                    self.issue(
                        "P1",
                        "missing_h1",
                        record,
                        "Indexable page has no H1.",
                    )
                elif record.h1_count > 1:
                    self.issue(
                        "P1",
                        "multiple_h1",
                        record,
                        f"Indexable page has {record.h1_count} H1 elements.",
                        record.h1_text,
                    )

                if not record.html_lang:
                    self.issue(
                        "P1",
                        "missing_html_lang",
                        record,
                        "HTML document has no lang attribute.",
                    )

                if record.url == self.base_url and self.args.primary_lang:
                    lang = record.html_lang.lower()
                    expected = self.args.primary_lang.lower()
                    if not (
                        lang == expected
                        or lang.startswith(expected + "-")
                    ):
                        self.issue(
                            "P1",
                            "home_lang_mismatch",
                            record,
                            "Homepage lang does not match configured primary language.",
                            f"expected={expected}; actual={lang or '(missing)'}",
                        )

                if record.heading_gap_count:
                    self.issue(
                        "P2",
                        "heading_level_gap",
                        record,
                        "Heading hierarchy skips one or more levels.",
                        f"gaps={record.heading_gap_count}",
                    )

                og_missing = [
                    name
                    for name, value in (
                        ("og:type", record.og_type),
                        ("og:title", record.og_title),
                        ("og:description", record.og_description),
                        ("og:url", record.og_url),
                        ("og:image", record.og_image),
                        ("og:site_name", record.og_site_name),
                    )
                    if not value
                ]
                if og_missing:
                    self.issue(
                        "P2",
                        "open_graph_incomplete",
                        record,
                        "Open Graph baseline is incomplete.",
                        ", ".join(og_missing),
                    )

            if record.jsonld_parse_errors:
                self.issue(
                    "P1",
                    "jsonld_parse_error",
                    record,
                    "One or more JSON-LD blocks could not be parsed.",
                    f"errors={record.jsonld_parse_errors}",
                )

            if record.images_missing_alt:
                self.issue(
                    "P2",
                    "images_missing_alt_attribute",
                    record,
                    "One or more images have no alt attribute.",
                    f"count={record.images_missing_alt}",
                )

            if record.images_missing_dimensions:
                self.issue(
                    "P2",
                    "images_missing_dimensions",
                    record,
                    "One or more images lack explicit width and/or height attributes.",
                    f"count={record.images_missing_dimensions}",
                )

            if (
                record.sitemap_member
                and record.url != self.base_url
                and record.inbound_internal_links == 0
            ):
                self.issue(
                    "P1",
                    "orphan_like_sitemap_url",
                    record,
                    "Sitemap URL received no crawlable internal inbound links in this crawl.",
                )

        self.evaluate_duplicates()
        self.evaluate_broken_links()
        self.evaluate_hreflang()

        self.issues.sort(
            key=lambda item: (
                SEVERITY_ORDER.get(item.severity, 9),
                item.code,
                item.url,
            )
        )

    def evaluate_duplicates(self) -> None:
        fields = {
            "title": defaultdict(list),
            "description": defaultdict(list),
            "canonical": defaultdict(list),
        }

        for record in self.records.values():
            if (
                record.status != 200
                or record.redirect_count
                or record.indexability != "indexable"
            ):
                continue

            if record.title:
                fields["title"][record.title].append(record.url)
            if record.description:
                fields["description"][record.description].append(
                    record.url
                )
            if record.canonical:
                fields["canonical"][record.canonical].append(
                    record.url
                )

        for field_name, groups in fields.items():
            for value, urls in groups.items():
                if len(urls) < 2:
                    continue

                code = f"duplicate_{field_name}"
                severity = "P1"
                message = (
                    f"{len(urls)} indexable URLs share the same "
                    f"{field_name}."
                )

                for url in urls:
                    self.issues.append(
                        Issue(
                            severity,
                            code,
                            url,
                            message,
                            value[:500],
                        )
                    )

    def evaluate_broken_links(self) -> None:
        by_target = defaultdict(set)

        for edge in self.edges:
            by_target[edge["crawl_target"]].add(
                edge["source_url"]
            )

        for target, sources in by_target.items():
            record = self.records.get(target)
            if not record or record.status is None:
                continue

            if record.status >= 400:
                for source in sorted(sources):
                    self.issues.append(
                        Issue(
                            "P1",
                            "broken_internal_link",
                            source,
                            f"Internal link points to HTTP {record.status}.",
                            target,
                        )
                    )
            elif record.redirect_count:
                for source in sorted(sources):
                    self.issues.append(
                        Issue(
                            "P2",
                            "internal_link_to_redirect",
                            source,
                            "Internal link points to a redirect instead of the final preferred URL.",
                            target,
                        )
                    )

    def evaluate_hreflang(self) -> None:
        pair_map = defaultdict(set)

        for row in self.hreflang_rows:
            pair_map[row["source_url"]].add(row["url"])

        for row in self.hreflang_rows:
            source = row["source_url"]
            target = row["url"]

            if not self.same_origin(target):
                continue

            target_record = self.records.get(target)
            if not target_record:
                continue

            if (
                target_record.status == 200
                and source not in pair_map.get(target, set())
            ):
                self.issues.append(
                    Issue(
                        "P1",
                        "hreflang_not_reciprocal",
                        source,
                        "hreflang target does not link back to the source URL.",
                        f'{row["hreflang"]}:{target}',
                    )
                )

    def output_dir(self) -> Path:
        if self.args.output:
            return Path(self.args.output)

        stamp = datetime.now().strftime("%Y-%m-%d__seo_baseline_%H%M%S")
        return Path("marketing/reports") / stamp

    def write_csv(
        self,
        path: Path,
        rows: Iterable[dict],
        fieldnames: list[str],
    ) -> None:
        with path.open(
            "w",
            encoding="utf-8-sig",
            newline="",
        ) as handle:
            writer = csv.DictWriter(
                handle,
                fieldnames=fieldnames,
                extrasaction="ignore",
            )
            writer.writeheader()
            for row in rows:
                writer.writerow(row)

    def write_outputs(self) -> Path:
        output = self.output_dir()
        if not output.is_absolute():
            output = Path.cwd() / output

        if output.exists() and any(output.iterdir()) and not self.args.force:
            raise RuntimeError(
                f"Output directory is not empty: {output}. "
                "Use --force or choose another --output."
            )

        output.mkdir(parents=True, exist_ok=True)

        record_rows = [
            asdict(record)
            for record in sorted(
                self.records.values(),
                key=lambda item: item.url,
            )
        ]

        issue_rows = [
            asdict(issue)
            for issue in self.issues
        ]

        (output / "urls.json").write_text(
            json.dumps(
                record_rows,
                ensure_ascii=False,
                indent=2,
            )
            + "\n",
            encoding="utf-8",
        )

        if record_rows:
            self.write_csv(
                output / "urls.csv",
                record_rows,
                list(record_rows[0].keys()),
            )

        self.write_csv(
            output / "issues.csv",
            issue_rows,
            ["severity", "code", "url", "message", "evidence"],
        )

        self.write_csv(
            output / "redirects.csv",
            self.redirects,
            ["source_url", "status", "from", "to"],
        )

        self.write_csv(
            output / "internal_links.csv",
            self.edges,
            ["source_url", "target_url", "crawl_target"],
        )

        self.write_csv(
            output / "images.csv",
            self.images,
            [
                "page_url",
                "image_url",
                "alt_present",
                "alt",
                "width",
                "height",
                "loading",
                "fetchpriority",
            ],
        )

        self.write_csv(
            output / "hreflang.csv",
            self.hreflang_rows,
            ["source_url", "hreflang", "url"],
        )

        self.write_csv(
            output / "query_links.csv",
            (
                {"url": url, "occurrences": count}
                for url, count in self.query_links.most_common()
            ),
            ["url", "occurrences"],
        )

        self.write_csv(
            output / "linked_resources.csv",
            (
                {"url": url, "occurrences": count}
                for url, count in self.resources.most_common()
            ),
            ["url", "occurrences"],
        )

        self.write_csv(
            output / "sitemaps.csv",
            self.sitemap_documents,
            ["url", "status", "error", "kind", "urls"],
        )

        validation_samples = {}
        for record in sorted(
            self.records.values(),
            key=lambda item: item.url,
        ):
            if (
                record.status == 200
                and "html" in record.content_type.lower()
                and record.page_type not in validation_samples
            ):
                validation_samples[record.page_type] = record.url

        self.write_csv(
            output / "html_validation_candidates.csv",
            (
                {"page_type": key, "url": value}
                for key, value in sorted(
                    validation_samples.items()
                )
            ),
            ["page_type", "url"],
        )

        (output / "robots.txt").write_text(
            self.robots_raw,
            encoding="utf-8",
        )

        config = {
            "generated_at": utc_now(),
            "base_url": self.base_url,
            "max_pages": self.args.max_pages,
            "delay": self.args.delay,
            "timeout": self.args.timeout,
            "max_bytes": self.args.max_bytes,
            "user_agent": self.args.user_agent,
            "primary_lang": self.args.primary_lang,
            "ignore_robots": self.args.ignore_robots,
            "robots_status": self.robots_status,
            "sitemap_urls": len(self.sitemap_urls),
            "html_pages_fetched": self.fetched_html,
            "crawler_version": "0.2",
            "redirect_status_semantics": (
                "status=requested URL; final_status=post-redirect response"
            ),
        }
        (output / "config.json").write_text(
            json.dumps(config, indent=2)
            + "\n",
            encoding="utf-8",
        )

        summary = self.summary_markdown(output)
        (output / "summary.md").write_text(
            summary,
            encoding="utf-8",
        )

        return output

    def summary_markdown(self, output: Path) -> str:
        severity = Counter(
            issue.severity for issue in self.issues
        )
        codes = Counter(
            issue.code for issue in self.issues
        )
        statuses = Counter(
            str(record.status)
            if record.status is not None
            else "not-fetched"
            for record in self.records.values()
        )
        page_types = Counter(
            record.page_type
            for record in self.records.values()
        )
        indexability = Counter(
            record.indexability or "unknown"
            for record in self.records.values()
        )

        top_codes = "\n".join(
            f"- `{code}`: {count}"
            for code, count in codes.most_common(20)
        ) or "- none"

        return f"""# ForPrint search visibility baseline audit

Generated: `{utc_now()}`
Base URL: `{self.base_url}`
Mode: **read-only HTTP crawl**

## Result overview

- URLs recorded: **{len(self.records)}**
- HTML pages fetched: **{self.fetched_html}**
- sitemap canonical candidates: **{len(self.sitemap_urls)}**
- internal link edges: **{len(self.edges)}**
- images observed: **{len(self.images)}**
- query-bearing internal links observed (not crawled as variants): **{sum(self.query_links.values())}**
- issues P0: **{severity.get("P0", 0)}**
- issues P1: **{severity.get("P1", 0)}**
- issues P2: **{severity.get("P2", 0)}**
- informational findings: **{severity.get("INFO", 0)}**

## HTTP status distribution

```json
{json.dumps(dict(statuses), ensure_ascii=False, indent=2)}
```

## Indexability distribution

```json
{json.dumps(dict(indexability), ensure_ascii=False, indent=2)}
```

## Page-type distribution

```json
{json.dumps(dict(page_types), ensure_ascii=False, indent=2)}
```

## Most frequent issue classes

{top_codes}

## Output files

- `urls.csv` / `urls.json` — one row per crawled/discovered URL;
- `issues.csv` — P0/P1/P2/INFO findings;
- `redirects.csv` — observed redirect hops;
- `internal_links.csv` — crawlable internal link graph;
- `images.csv` — image alt/dimension/loading observations;
- `hreflang.csv` — language alternate declarations;
- `query_links.csv` — query-bearing internal links, intentionally not expanded;
- `linked_resources.csv` — linked non-HTML/resource URLs;
- `sitemaps.csv` — discovered sitemap documents and parse state;
- `html_validation_candidates.csv` — representative page classes for the next
  W3C/Nu HTML-validation phase;
- `robots.txt` — fetched production robots file;
- `config.json` — reproducibility configuration.

## Interpretation boundary

This tool reports technical evidence and conservative heuristics. It does not
claim that title/description character ranges are Google ranking rules, and it
does not treat HTML validity, Open Graph, or structured data as ranking scores.

Redirect semantics: `status` is the HTTP status of the requested URL while
`final_status` records the post-redirect response. Redirect source URLs are not
counted as indexable duplicate-title/description/canonical pages.

The next step is to review P0/P1 findings, verify intent against the actual
ForPrint route/language model, and only then change PHP/templates/metadata.
"""

    def run(self) -> Path:
        print("ForPrint search visibility audit v0.2")
        print("=" * 80)
        print(f"base_url={self.base_url}")
        print("mode=READ ONLY")
        print(
            "query variants are recorded but not expanded; "
            "resource URLs are recorded but not crawled"
        )

        print("\n== robots.txt ==")
        self.fetch_robots()
        print(
            f"robots_status={self.robots_status} "
            f"error={self.robots_error or '-'}"
        )

        print("\n== sitemap discovery ==")
        self.discover_sitemaps()
        print(
            f"sitemap_documents={len(self.sitemap_documents)} "
            f"sitemap_urls={len(self.sitemap_urls)}"
        )

        print("\n== crawl ==")
        self.crawl()
        print(
            f"urls_recorded={len(self.records)} "
            f"html_fetched={self.fetched_html}"
        )

        print("\n== evaluation ==")
        self.evaluate()
        counts = Counter(
            issue.severity for issue in self.issues
        )
        print(
            f"P0={counts.get('P0', 0)} "
            f"P1={counts.get('P1', 0)} "
            f"P2={counts.get('P2', 0)} "
            f"INFO={counts.get('INFO', 0)}"
        )

        output = self.write_outputs()

        print("\n" + "=" * 80)
        print("SEO BASELINE AUDIT COMPLETE")
        print("=" * 80)
        print(f"report={output}")
        print("No production mutation was performed.")
        return output


def self_test() -> None:
    html = """<!doctype html>
<html lang="uk">
<head>
<title>Друк візиток на замовлення | ForPrint</title>
<meta name="description" content="Тестовий опис сторінки друку візиток.">
<link rel="canonical" href="https://forprint.net.ua/product/vizytky/">
<meta property="og:title" content="Візитки">
<script type="application/ld+json">
{"@context":"https://schema.org","@type":"Product","name":"Візитки"}
</script>
</head>
<body>
<h1>Візитки</h1>
<h2>Матеріали</h2>
<a href="/catalog/">Каталог</a>
<img src="/userfiles/a.jpg" alt="Візитки" width="400" height="300">
</body>
</html>"""

    parser = PageParser(
        "https://forprint.net.ua/product/vizytky/"
    )
    parser.feed(html)
    parser.close()

    assert parser.title() == "Друк візиток на замовлення | ForPrint"
    assert parser.html_lang == "uk"
    assert len(parser.headings) == 2
    assert first_link_rel(
        parser.links,
        "canonical",
        "https://forprint.net.ua/product/vizytky/",
    ) == "https://forprint.net.ua/product/vizytky/"
    types, errors = parse_jsonld(parser.jsonld_blocks)
    assert types == ["Product"]
    assert errors == 0
    assert normalize_url(
        "/catalog/",
        base="https://forprint.net.ua/product/vizytky/",
    ) == "https://forprint.net.ua/catalog/"
    print("[OK] parser/URL/JSON-LD self-test passed")


def build_parser() -> argparse.ArgumentParser:
    parser = argparse.ArgumentParser(
        description=(
            "Read-only ForPrint technical SEO/search visibility crawler."
        )
    )
    parser.add_argument(
        "--base-url",
        default="https://forprint.net.ua/",
    )
    parser.add_argument(
        "--output",
        default="",
    )
    parser.add_argument(
        "--max-pages",
        type=int,
        default=1500,
    )
    parser.add_argument(
        "--delay",
        type=float,
        default=0.08,
        help="minimum delay between HTTP requests, seconds",
    )
    parser.add_argument(
        "--timeout",
        type=float,
        default=20.0,
    )
    parser.add_argument(
        "--max-bytes",
        type=int,
        default=5_000_000,
    )
    parser.add_argument(
        "--user-agent",
        default=DEFAULT_UA,
    )
    parser.add_argument(
        "--primary-lang",
        default="uk",
        help="homepage language expectation only",
    )
    parser.add_argument(
        "--seed",
        action="append",
        default=[],
    )
    parser.add_argument(
        "--sitemap",
        action="append",
        default=[],
    )
    parser.add_argument(
        "--ignore-robots",
        action="store_true",
        help="internal diagnostics only; safe-skip routes still remain excluded",
    )
    parser.add_argument(
        "--force",
        action="store_true",
        help="allow writing into an existing non-empty output directory",
    )
    parser.add_argument(
        "--self-test",
        action="store_true",
    )
    return parser


def main() -> int:
    args = build_parser().parse_args()

    if args.self_test:
        self_test()
        return 0

    if args.max_pages < 1:
        raise SystemExit("--max-pages must be >= 1")
    if args.delay < 0:
        raise SystemExit("--delay must be >= 0")
    if args.max_bytes < 1024:
        raise SystemExit("--max-bytes must be >= 1024")

    audit = Audit(args)
    audit.run()
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
