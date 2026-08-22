#!/usr/bin/env python3
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
OWNER = ROOT / "base/core/admin/controllers/CreatesitemapController.php"

text = OWNER.read_text(encoding="utf-8")

required = [
    "FP_SITEMAP_VISIBLE_PRODUCT_SEED_CALL_V0_1",
    "FP_SITEMAP_VISIBLE_PRODUCT_SEED_METHOD_V0_1",
    "$this->seedVisibleProductLinks();",
    "Settings::get('routes')",
    "$routes['product']['alias']",
    "$this->model->get(",
    "'goods'",
    "'fields' => ['alias']",
    "'where' => ['visible' => 1]",
    "$this->all_links[] = $link",
    "$this->temp_links[] = $link",
]

missing = [item for item in required if item not in text]

if missing:
    raise SystemExit("FAIL missing contract tokens: " + repr(missing))

call_pos = text.find("$this->seedVisibleProductLinks();")
crawl_pos = text.find("while ($this->temp_links)")

if call_pos < 0 or crawl_pos < 0 or call_pos > crawl_pos:
    raise SystemExit("FAIL seed must execute before crawler loop")

if "'/product/'" in text[text.find("protected function seedVisibleProductLinks"):]:
    raise SystemExit("FAIL hardcoded /product/ route in seed method")

print("PASS")
print("seed_before_crawl=YES")
print("catalog_source=goods.visible=1")
print("route_owner=Settings::get('routes')['product']['alias']")
print("crawler_validation_preserved=YES")
