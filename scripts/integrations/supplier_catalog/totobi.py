from __future__ import annotations

# FP_TOTOBI_DISCOVERY_HARDENING_V1
import html
from html.parser import HTMLParser
import urllib.parse
import urllib.request
from pathlib import Path


DEFAULT_DOC_URL = "https://totobi.com.ua/opis-vigruzok/"
_USER_AGENT = (
    "Mozilla/5.0 (X11; Linux x86_64) "
    "AppleWebKit/537.36 (KHTML, like Gecko) "
    "Chrome/140.0 Safari/537.36 "
    "ForPrint-Supplier-Catalog/0.2"
)


class _YmlLinkParser(HTMLParser):
    def __init__(self) -> None:
        super().__init__(convert_charrefs=True)
        self.links: list[str] = []

    def handle_starttag(
        self,
        tag: str,
        attrs: list[tuple[str, str | None]],
    ) -> None:
        if tag.lower() != "a":
            return

        href = ""
        for key, value in attrs:
            if key.lower() == "href" and value:
                href = value.strip()
                break

        if not href:
            return

        candidate = html.unescape(href)
        lowered = candidate.lower()

        if (
            "dispatch=yml.get" in lowered
            or "dispatch=yml.get_prom" in lowered
        ):
            self.links.append(candidate)


def sanitized_url(url: str) -> str:
    parts = urllib.parse.urlsplit(url)

    if not parts.query:
        return url

    pairs = urllib.parse.parse_qsl(
        parts.query,
        keep_blank_values=True,
    )
    safe_pairs = []

    for key, value in pairs:
        if key.lower() in {
            "access_key",
            "token",
            "key",
            "secret",
        }:
            safe_pairs.append((key, "<redacted>"))
        else:
            safe_pairs.append((key, value))

    return urllib.parse.urlunsplit(
        (
            parts.scheme,
            parts.netloc,
            parts.path,
            urllib.parse.urlencode(safe_pairs),
            parts.fragment,
        )
    )


def _open(
    url: str,
    *,
    accept: str,
    timeout: int,
):
    request = urllib.request.Request(
        url,
        headers={
            "User-Agent": _USER_AGENT,
            "Accept": accept,
            "Accept-Language": "uk,en;q=0.8",
            "Cache-Control": "no-cache",
        },
    )
    return urllib.request.urlopen(
        request,
        timeout=timeout,
    )


def discover_yml_url(
    doc_url: str = DEFAULT_DOC_URL,
) -> str:
    with _open(
        doc_url,
        accept="text/html,application/xhtml+xml;q=0.9,*/*;q=0.8",
        timeout=30,
    ) as response:
        body = response.read(8_000_000).decode(
            "utf-8",
            errors="replace",
        )

    parser = _YmlLinkParser()
    parser.feed(body)

    if not parser.links:
        raise RuntimeError(
            "Totobi public documentation page contains no YML download link"
        )

    ordinary = [
        link
        for link in parser.links
        if "dispatch=yml.get_prom" not in link.lower()
    ]
    chosen = ordinary[0] if ordinary else parser.links[0]

    return urllib.parse.urljoin(
        doc_url,
        chosen,
    )


def download_yml(
    destination: Path,
    *,
    source_url: str,
    max_bytes: int = 150_000_000,
) -> int:
    destination.parent.mkdir(
        parents=True,
        exist_ok=True,
    )

    written = 0
    first_bytes = bytearray()

    with _open(
        source_url,
        accept="application/xml,text/xml,application/octet-stream,*/*;q=0.8",
        timeout=90,
    ) as response:
        with destination.open("wb") as handle:
            while True:
                chunk = response.read(1024 * 1024)
                if not chunk:
                    break

                if len(first_bytes) < 8192:
                    remaining = 8192 - len(first_bytes)
                    first_bytes.extend(chunk[:remaining])

                written += len(chunk)
                if written > max_bytes:
                    raise RuntimeError(
                        f"feed exceeds safety cap ({max_bytes} bytes)"
                    )

                handle.write(chunk)

    prefix = bytes(first_bytes).lstrip().lower()
    if not (
        prefix.startswith(b"<?xml")
        or prefix.startswith(b"<yml_catalog")
    ):
        preview = bytes(first_bytes[:240]).decode(
            "utf-8",
            errors="replace",
        )
        raise RuntimeError(
            "Totobi endpoint did not return YML/XML; "
            f"prefix={preview!r}"
        )

    return written
