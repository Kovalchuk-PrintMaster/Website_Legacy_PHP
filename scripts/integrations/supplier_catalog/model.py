from __future__ import annotations

from dataclasses import asdict, dataclass, field
from decimal import Decimal
from typing import Any


@dataclass(frozen=True)
class SupplierCategory:
    supplier: str
    external_id: str
    name: str
    parent_external_id: str | None = None


@dataclass(frozen=True)
class SupplierVariant:
    external_id: str
    name: str | None = None
    vendor_code: str | None = None
    available: bool | None = None
    stock: Decimal | None = None
    incoming_stock: Decimal | None = None
    incoming_date: str | None = None
    price_modifier: Decimal | None = None
    attributes: dict[str, str] = field(default_factory=dict)


@dataclass(frozen=True)
class SupplierOffer:
    supplier: str
    external_id: str
    group_id: str | None
    vendor_code: str | None
    name: str
    category_external_id: str | None
    source_url: str | None
    price: Decimal | None
    old_price: Decimal | None
    currency: str | None
    available: bool | None
    stock: Decimal | None
    incoming_stock: Decimal | None
    incoming_date: str | None
    brand: str | None
    branding_methods: tuple[str, ...]
    images: tuple[str, ...]
    attributes: dict[str, str]
    variants: tuple[SupplierVariant, ...] = ()
    description: str | None = None

    def to_json_dict(self) -> dict[str, Any]:
        data = asdict(self)
        for key in ("price", "old_price", "stock", "incoming_stock"):
            value = data.get(key)
            if value is not None:
                data[key] = str(value)
        for variant in data.get("variants", []):
            for key in ("stock", "incoming_stock", "price_modifier"):
                value = variant.get(key)
                if value is not None:
                    variant[key] = str(value)
        return data
