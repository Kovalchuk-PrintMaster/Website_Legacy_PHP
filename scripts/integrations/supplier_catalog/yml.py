from __future__ import annotations

import html
import re
import xml.etree.ElementTree as ET
from decimal import Decimal, InvalidOperation
from pathlib import Path
from typing import Iterator

from .model import SupplierCategory, SupplierOffer, SupplierVariant


_TAG_RE = re.compile(r"<[^>]+>")


def _text(node: ET.Element, name: str) -> str | None:
    child = node.find(name)
    if child is None or child.text is None:
        return None
    value = child.text.strip()
    return value or None


def _decimal(value: str | None) -> Decimal | None:
    if value is None:
        return None
    value = value.strip().replace(",", ".")
    if not value:
        return None
    try:
        return Decimal(value)
    except InvalidOperation:
        return None


def _bool(value: str | None) -> bool | None:
    if value is None:
        return None
    normalized = value.strip().lower()
    if normalized in {"true", "1", "yes", "y"}:
        return True
    if normalized in {"false", "0", "no", "n"}:
        return False
    return None


def _clean_description(value: str | None) -> str | None:
    if not value:
        return None
    value = html.unescape(value)
    value = _TAG_RE.sub(" ", value)
    value = re.sub(r"\s+", " ", value).strip()
    return value or None


def iter_categories(path: Path, supplier: str) -> Iterator[SupplierCategory]:
    for _event, elem in ET.iterparse(path, events=("end",)):
        if elem.tag != "category":
            continue
        external_id = str(elem.attrib.get("id", "")).strip()
        name = (elem.text or "").strip()
        if external_id and name:
            yield SupplierCategory(
                supplier=supplier,
                external_id=external_id,
                name=name,
                parent_external_id=(
                    str(elem.attrib.get("parentId", "")).strip() or None
                ),
            )
        elem.clear()


def _params(offer: ET.Element) -> dict[str, str]:
    values: dict[str, str] = {}
    for param in offer.findall("param"):
        name = str(param.attrib.get("name", "")).strip()
        value = (param.text or "").strip()
        if name and value:
            values[name] = value
    return values


def _split_methods(value: str | None) -> tuple[str, ...]:
    if not value:
        return ()
    chunks = re.split(r"[,;/]+", value)
    return tuple(part.strip() for part in chunks if part.strip())


def _variant_from_size(size: ET.Element) -> SupplierVariant:
    attributes: dict[str, str] = {}
    for child in list(size):
        if child.tag in {
            "modifier",
            "product_code",
            "amount",
            "in_stock",
            "reserve",
            "wait",
            "date_delivery",
            "box",
        }:
            continue
        value = (child.text or "").strip()
        if value:
            attributes[child.tag] = value

    external_id = (
        str(size.attrib.get("id", "")).strip()
        or _text(size, "product_code")
        or _text(size, "name")
        or "unknown"
    )

    return SupplierVariant(
        external_id=external_id,
        name=_text(size, "name") or (size.text or "").strip() or None,
        vendor_code=_text(size, "product_code"),
        available=_bool(size.attrib.get("available")),
        stock=_decimal(_text(size, "in_stock") or _text(size, "amount")),
        incoming_stock=_decimal(_text(size, "wait")),
        incoming_date=_text(size, "date_delivery"),
        price_modifier=_decimal(_text(size, "modifier")),
        attributes=attributes,
    )


def _variants(offer: ET.Element) -> tuple[SupplierVariant, ...]:
    values = []
    sizes = offer.find("sizes")
    if sizes is not None:
        for size in sizes.findall("size"):
            values.append(_variant_from_size(size))
    return tuple(values)


def iter_offers(path: Path, supplier: str) -> Iterator[SupplierOffer]:
    for _event, elem in ET.iterparse(path, events=("end",)):
        if elem.tag != "offer":
            continue

        external_id = str(elem.attrib.get("id", "")).strip()
        if not external_id:
            elem.clear()
            continue

        params = _params(elem)
        images = tuple(
            (node.text or "").strip()
            for node in elem.findall("picture")
            if (node.text or "").strip()
        )

        stock = _decimal(
            _text(elem, "quantity_in_stock")
            or _text(elem, "in_stock")
            or _text(elem, "amount")
        )
        incoming = _decimal(
            _text(elem, "wait")
            or _text(elem, "expected")
            or _text(elem, "incoming")
        )
        incoming_date = (
            _text(elem, "date_delivery")
            or _text(elem, "delivery_date")
        )

        brand = (
            params.get("ТМ")
            or params.get("Бренд")
            or _text(elem, "vendor")
        )
        branding_value = (
            params.get("Група нанесення")
            or params.get("Группа нанесения")
        )

        yield SupplierOffer(
            supplier=supplier,
            external_id=external_id,
            group_id=(
                str(elem.attrib.get("group_id", "")).strip() or None
            ),
            vendor_code=_text(elem, "vendorCode"),
            name=_text(elem, "name") or f"Offer {external_id}",
            category_external_id=_text(elem, "categoryId"),
            source_url=_text(elem, "url"),
            price=_decimal(_text(elem, "price")),
            old_price=_decimal(_text(elem, "oldprice")),
            currency=_text(elem, "currencyId"),
            available=_bool(elem.attrib.get("available")),
            stock=stock,
            incoming_stock=incoming,
            incoming_date=incoming_date,
            brand=brand,
            branding_methods=_split_methods(branding_value),
            images=images,
            attributes=params,
            variants=_variants(elem),
            description=_clean_description(_text(elem, "description")),
        )

        elem.clear()
