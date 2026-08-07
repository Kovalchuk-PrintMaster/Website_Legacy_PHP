# ForPrint_Web_Site_Base — Legacy Base Inventory v0.4.1

## Module

`forprint_website`

## Website base

`base/`

## Status

`legacy_base_inventory_v0_4_1_prepared`

## Purpose

Inventory the inherited PHP website base before any broad repository tracking. This report does not approve `git add base/`.

## Safety boundary

- No PHP website code was modified by this inventory step.
- No deployment was performed.
- No production services were connected.
- No secrets were intentionally printed into this report.
- `base/` remains inherited legacy PHP code and is not approved for broad commit.

## Generated files

- `coordination/inventory/base_inventory_v0_4_1.tsv`
- `coordination/reports/forprint_website_legacy_base_inventory_v0_4_1.md`

## Inventory summary

- Total files scanned: `525`
- Total size scanned: `170.04 MB`

## Classification counts

| Category | Files | Size |
|---|---:|---:|
| `generated_files` | 3 | 38.24 KB |
| `local_config` | 1 | 303 B |
| `runtime_logs` | 5 | 3.92 MB |
| `runtime_uploads` | 186 | 143.82 MB |
| `source_code` | 247 | 18.95 MB |
| `vendor_dependencies` | 83 | 3.31 MB |

## Top file extensions

| Extension | Files |
|---|---:|
| `.php` | 158 |
| `.png` | 122 |
| `.js` | 74 |
| `.jpg` | 39 |
| `.css` | 22 |
| `.svg` | 16 |
| `.woff` | 14 |
| `.eot` | 12 |
| `.eot@` | 12 |
| `.ttf` | 12 |
| `.woff2` | 12 |
| `[no_ext]` | 9 |
| `.txt` | 8 |
| `.json` | 3 |
| `.md` | 3 |
| `.jpeg` | 3 |
| `.html` | 2 |
| `.lock` | 1 |
| `.phar` | 1 |
| `.ts` | 1 |

## Classification examples

### `generated_files`

- `base/sitemap.xml`
- `base/temp/cart.html`
- `base/temp/mce.txt`

### `local_config`

- `base/config.php`

### `runtime_logs`

- `base/log/db_log.txt`
- `base/log/log.txt`
- `base/log/log_user.txt`
- `base/log/parsing_log.txt`
- `base/log/user_log.txt`

### `runtime_uploads`

- `base/userfiles/advantages/img_9620.JPG`
- `base/userfiles/advantages/img_9628.JPG`
- `base/userfiles/advantages/img_9635.JPG`
- `base/userfiles/advantages/img_9640.JPG`
- `base/userfiles/advantages/promo_img.svg`
- `base/userfiles/advantages/work_402cb94f.png`
- `base/userfiles/advantages/work_7fbfdf94.png`
- `base/userfiles/advantages/work_87f85af9.png`
- `base/userfiles/catalog/20250519_1117_kreativniy-druk-futbolka_simple_compose_01jvksv2d9e9aa5g0xh30mrq2p.png`
- `base/userfiles/catalog/t-shirt.png`
- `base/userfiles/catalog/t-shirt_4be45689.png`
- `base/userfiles/catalog/t-shirt_a5d29cac.png`
- `base/userfiles/catalog/t-shirt_e359fb49.png`
- `base/userfiles/default_images/default.jpg`
- `base/userfiles/default_images/default_inx.jpg`
- `base/userfiles/filters/20250519_1117_kreativniy-druk-futbolka_simple_compose_01jvksv2d9e9aa5g0xh30mrq2p.png`
- `base/userfiles/filters_categories/20250519_1117_kreativniy-druk-futbolka_simple_compose_01jvksv2d6e4ttey7mz4jmcph4.png`
- `base/userfiles/filters_categories/bussines-card_colorfull.jpg`
- `base/userfiles/filters_categories/notepad_ring3.png`
- `base/userfiles/fromtend/assets/css/animate.css`

### `source_code`

- `base/.htaccess`
- `base/composer.json`
- `base/composer.lock`
- `base/core/admin/controllers/AddController.php`
- `base/core/admin/controllers/AjaxController.php`
- `base/core/admin/controllers/BaseAdmin.php`
- `base/core/admin/controllers/CreatesitemapController.php`
- `base/core/admin/controllers/DeleteController.php`
- `base/core/admin/controllers/EditController.php`
- `base/core/admin/controllers/IndexController.php`
- `base/core/admin/controllers/LoginController.php`
- `base/core/admin/controllers/SearchController.php`
- `base/core/admin/controllers/ShowController.php`
- `base/core/admin/expansions/TeachersExpansion.php`
- `base/core/admin/models/Model.php`
- `base/core/admin/models/UserModel.php`
- `base/core/admin/views/add.php`
- `base/core/admin/views/css/main.css`
- `base/core/admin/views/img/menu-button.png`
- `base/core/admin/views/img/out.png`

### `vendor_dependencies`

- `base/composer.phar`
- `base/vendor/autoload.php`
- `base/vendor/composer/ClassLoader.php`
- `base/vendor/composer/InstalledVersions.php`
- `base/vendor/composer/LICENSE`
- `base/vendor/composer/autoload_classmap.php`
- `base/vendor/composer/autoload_namespaces.php`
- `base/vendor/composer/autoload_psr4.php`
- `base/vendor/composer/autoload_real.php`
- `base/vendor/composer/autoload_static.php`
- `base/vendor/composer/installed.json`
- `base/vendor/composer/installed.php`
- `base/vendor/composer/platform_check.php`
- `base/vendor/phpmailer/phpmailer/.editorconfig`
- `base/vendor/phpmailer/phpmailer/COMMITMENT`
- `base/vendor/phpmailer/phpmailer/LICENSE`
- `base/vendor/phpmailer/phpmailer/README.md`
- `base/vendor/phpmailer/phpmailer/SECURITY.md`
- `base/vendor/phpmailer/phpmailer/VERSION`
- `base/vendor/phpmailer/phpmailer/composer.json`

## Tracking policy draft

### Candidate source code

Can be considered for future selected tracking after review:

- `base/index.php`
- `base/.htaccess` after hardening
- `base/core/`
- `base/libraries/`
- `base/templates/`
- `base/composer.json`
- `base/composer.lock`
- `base/mail.example.php`

### Must remain untracked or replaced before tracking

- `base/config.php` until config split is approved
- `base/config.local.php`
- `base/mail.local.php`
- `base/log/`
- `base/temp/`
- local temporary/scratch state such as root `tmp.py`, root `tmp.php`, and the `tmp/` directory

### Needs explicit owner/Blueprint decision

- `base/vendor/`
- `base/composer.phar`
- `base/userfiles/`
- `base/sitemap.xml`

## Required next decisions

1. Decide whether `vendor/` is tracked or regenerated by Composer.
2. Decide whether any `userfiles/` content is seed media or runtime upload data.
3. Split real DB config from trackable example config.
4. Keep website DB/order/client/product data non-canonical.
5. Continue to forbid broad `git add base/` until selected tracking policy is approved.

## Next recommended step

`ForPrint_Web_Site_Base — Safe Tracking Policy and Config Split v0.4.2`

Recommended scope:

- define selected source paths from `base/`;
- define ignored runtime paths;
- create config example strategy;
- do not deploy;
- do not connect production services;
- do not approve broad `git add base/`;
- do not make website data canonical.
