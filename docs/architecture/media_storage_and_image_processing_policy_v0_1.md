# Media Storage and Image Processing Policy v0.1

**Document ID:** `FP-WEB-MEDIA-001`
**Status:** active baseline
**Scope:** uploaded and managed website images

## Purpose

Prevent `base/userfiles/` and other high-accumulation directories from becoming unstructured storage.

Every image must have:

1. an owning domain or frontend surface;
2. a predictable directory;
3. a defined processing profile;
4. a relative database path where applicable;
5. a documented cleanup and replacement policy.

## Current product-image implementation

Canonical implementation:

```text
base/libraries/GoodsImageUploadOptimizer.php
```

Current upload flow:

```text
admin file field
→ FileEdit
→ GoodsImageUploadOptimizer
→ category directory
→ relative database path
```

### Main product image profile

- target canvas: `700 × 525 px`;
- output format: JPEG;
- JPEG quality: `98`;
- aspect ratio preserved;
- white background added where required by fit mode;
- storage:

```text
base/userfiles/goods/<catalog-alias>/
```

### Product gallery profile

- longest side: maximum `1600 px`;
- output format: JPEG;
- JPEG quality: `94`;
- aspect ratio preserved;
- storage:

```text
base/userfiles/goods/<catalog-alias>/
```

Database paths remain relative to `base/userfiles/`, for example:

```text
goods/operatuvna-poligrafiya/product-name_01.jpg
```

## Ownership before directory selection

Classify each image before storing it.

### Domain/entity-owned media

Images managed as records of business entities remain in their domain directory:

```text
base/userfiles/goods/
base/userfiles/catalog/
base/userfiles/sales/
base/userfiles/news/
base/userfiles/advantages/
base/userfiles/settings/
base/userfiles/socials/
```

Example: an image belonging to an `advantages` database record belongs to `userfiles/advantages/`, even when rendered on the home page.

### Frontend-presentation-owned media

Images that exist only to compose a frontend surface belong to:

```text
base/userfiles/frontend/home/
base/userfiles/frontend/catalog/
base/userfiles/frontend/search/
base/userfiles/frontend/product/
```

Recommended home subdivisions:

```text
base/userfiles/frontend/home/slider/
base/userfiles/frontend/home/sections/
base/userfiles/frontend/home/backgrounds/
base/userfiles/frontend/home/icons/
base/userfiles/frontend/home/decorations/
```

A visual decoration for the “Our advantages” home section may use:

```text
base/userfiles/frontend/home/advantages/
```

A database-managed advantage record must still use:

```text
base/userfiles/advantages/
```

## Directory rules

- no new files directly in `base/userfiles/`;
- no new files directly in `base/userfiles/frontend/`;
- use lowercase ASCII machine-safe slugs;
- use `kebab-case` directory names;
- avoid spaces and timestamps as the only semantic name;
- create subdivisions only when ownership or accumulation justifies them;
- do not move legacy files opportunistically during unrelated work.

## File naming

Preferred pattern:

```text
<semantic-name>_<variant-or-sequence>.<extension>
```

Examples:

```text
hero-print-services_desktop.jpg
hero-print-services_mobile.jpg
delivery-map_01.jpg
catalog-placeholder_neutral.jpg
```

The final naming helper must normalize unsafe characters and avoid collisions.

## Processing rule for future frontend uploads

Do not copy files directly into final frontend directories.

Future frontend uploads must use one centralized optimizer with named profiles, for example:

```text
home_slider
home_section
home_background
catalog_presentation
search_presentation
product_presentation
```

The future implementation may be a dedicated `FrontendImageUploadOptimizer` or a generalized managed optimizer. The final class name is not yet approved.

Each profile must define:

- accepted source formats;
- maximum dimensions;
- crop or fit behavior;
- output format;
- quality;
- metadata stripping;
- naming rule;
- destination ownership;
- failure behavior.

## Format policy

Current approved product output is JPEG through `GoodsImageUploadOptimizer`.

Frontend output format must be selected by a separate implementation decision based on transparency, photographic content, browser support and operational simplicity. Do not introduce WebP or AVIF inconsistently per component before that decision.

## Upload failure rule

When an image upload fails:

- do not silently save an empty image field;
- show a visible administrator error;
- do not update the affected record as though the image succeeded;
- remove partial temporary results where safe;
- retain the previously stored image on edit unless deletion was explicitly requested.

## Responsive delivery

The controlled frontend must:

- prevent intrinsic image dimensions from breaking layout;
- provide appropriate width and height information where practical;
- use lazy loading for non-critical images;
- avoid loading oversized desktop images on narrow screens when responsive variants exist;
- keep the main above-the-fold image loading strategy explicit.

## Cleanup policy

Root and legacy-directory cleanup is a separate controlled task.

Before moving or deleting a file:

1. locate database and template references;
2. identify the owner;
3. verify the replacement path;
4. update references atomically;
5. run HTTP image checks;
6. record the migration;
7. only then remove the old file.
