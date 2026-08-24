<?php
/* ForPrint admin search/cards/spacing v0.6.30.1 */
/* ForPrint managed footer v0.6.37 */
/* ForPrint ordered settings section cards v0.6.39 */
/* ForPrint grouped admin collections v0.6.46 */

$fpAdminSettingsIndex = ($this->table ?? '') === 'settings';
$fpAdminSettingsData = (
    $fpAdminSettingsIndex
    && !empty($this->data)
    && is_array($this->data[0] ?? null)
) ? $this->data[0] : null;

$fpAdminFooterSettingsData = null;
$fpAdminGoodsIndex = ($this->table ?? '') === 'goods';
$fpAdminGoodsGroups = [];
$fpAdminGoodsCategoryOrder = [];

$fpAdminFiltersIndex = ($this->table ?? '') === 'filters';
$fpAdminFilterGroups = [];
$fpAdminFilterCategoryOrder = [];

if ($fpAdminGoodsIndex && !empty($this->data)) {
    try {
        $fpAdminCatalogRows = $this->model->get('catalog', [
            'fields' => ['id', 'name', 'menu_position'],
            'order' => ['menu_position', 'id'],
            'order_direction' => ['ASC', 'ASC'],
        ]);

        foreach ($fpAdminCatalogRows ?: [] as $fpAdminCatalogRow) {
            $fpAdminCatalogId = (int)($fpAdminCatalogRow['id'] ?? 0);

            if ($fpAdminCatalogId < 1) {
                continue;
            }

            $fpAdminGoodsCategoryOrder[] = $fpAdminCatalogId;
            $fpAdminGoodsGroups[$fpAdminCatalogId] = [
                'name' => trim((string)($fpAdminCatalogRow['name'] ?? '')),
                'items' => [],
            ];
        }
    } catch (\Throwable $error) {
        $fpAdminGoodsGroups = [];
        $fpAdminGoodsCategoryOrder = [];
    }

    foreach ($this->data as $fpAdminGoodsItem) {
        $fpAdminGoodsParentId = (int)($fpAdminGoodsItem['parent_id'] ?? 0);

        if (!isset($fpAdminGoodsGroups[$fpAdminGoodsParentId])) {
            $fpAdminGoodsGroups[$fpAdminGoodsParentId] = [
                'name' => $fpAdminGoodsParentId > 0
                    ? 'Інший розділ #' . $fpAdminGoodsParentId
                    : 'Без розділу',
                'items' => [],
            ];
            $fpAdminGoodsCategoryOrder[] = $fpAdminGoodsParentId;
        }

        $fpAdminGoodsGroups[$fpAdminGoodsParentId]['items'][] = $fpAdminGoodsItem;
    }
}

if ($fpAdminFiltersIndex && !empty($this->data)) {

    try {
        $fpAdminFilterCategoryRows = $this->model->get('filters_categories', [
            'fields' => ['id', 'name', 'menu_position'],
            'order' => ['menu_position', 'id'],
            'order_direction' => ['ASC', 'ASC'],
        ]);

        foreach ($fpAdminFilterCategoryRows ?: [] as $fpAdminFilterCategoryRow) {
            $fpAdminFilterCategoryId = (int)($fpAdminFilterCategoryRow['id'] ?? 0);

            if ($fpAdminFilterCategoryId < 1) {
                continue;
            }

            $fpAdminFilterCategoryOrder[] = $fpAdminFilterCategoryId;
            $fpAdminFilterGroups[$fpAdminFilterCategoryId] = [
                'name' => trim((string)($fpAdminFilterCategoryRow['name'] ?? '')),
                'items' => [],
            ];
        }
    } catch (\Throwable $error) {
        $fpAdminFilterGroups = [];
        $fpAdminFilterCategoryOrder = [];
    }

    foreach ($this->data as $fpAdminFilterItem) {
        $fpAdminFilterParentId = (int)($fpAdminFilterItem['parent_id'] ?? 0);

        if (!isset($fpAdminFilterGroups[$fpAdminFilterParentId])) {
            $fpAdminFilterGroups[$fpAdminFilterParentId] = [
                'name' => $fpAdminFilterParentId > 0
                    ? 'Інша категорія #' . $fpAdminFilterParentId
                    : 'Без категорії',
                'items' => [],
            ];
            $fpAdminFilterCategoryOrder[] = $fpAdminFilterParentId;
        }

        $fpAdminFilterGroups[$fpAdminFilterParentId]['items'][] = $fpAdminFilterItem;
    }

    foreach ($fpAdminFilterGroups as &$fpAdminFilterGroup) {
        usort(
            $fpAdminFilterGroup['items'],
            static function (array $left, array $right): int {
                $positionComparison = ((int)($left['menu_position'] ?? 0))
                    <=> ((int)($right['menu_position'] ?? 0));

                if ($positionComparison !== 0) {
                    return $positionComparison;
                }

                return ((int)($left['id'] ?? 0))
                    <=> ((int)($right['id'] ?? 0));
            }
        );
    }
    unset($fpAdminFilterGroup);
}

if ($fpAdminSettingsIndex) {
    try {
        $fpAdminTables = $this->model->showTables();

        if (in_array('footer_settings', $fpAdminTables, true)) {
            $fpAdminFooterRows = $this->model->get('footer_settings', [
                'order' => ['menu_position', 'id'],
                'limit' => 1,
            ]);

            if (!empty($fpAdminFooterRows[0]) && is_array($fpAdminFooterRows[0])) {
                $fpAdminFooterSettingsData = $fpAdminFooterRows[0];
            }
        }
    } catch (\Throwable $error) {
        $fpAdminFooterSettingsData = null;
    }
}
?>
<?php
$fpAdminTableClass = preg_replace(
    '/[^a-z0-9_-]+/i',
    '-',
    trim((string)($this->table ?? 'index'))
);
?>

<div
    class="vg-wrap vg-element vg-ninteen-of-twenty fp-admin-index fp-admin-index--<?=htmlspecialchars($fpAdminTableClass, ENT_QUOTES, 'UTF-8')?><?=$fpAdminSettingsData ? ' fp-admin-settings-index' : ''?>"
    data-fp-admin-table="<?=htmlspecialchars((string)($this->table ?? ''), ENT_QUOTES, 'UTF-8')?>"
>

    <div class="vg-element vg-fourth fp-admin-add-card">
        <a href="<?=$this->adminPath?>add/<?=$this->table?>"
           class="vg-wrap vg-element vg-full vg-firm-background-color3 vg-box-shadow">
            <div class="vg-element vg-half vg-center">
                <img src="<?=PATH.ADMIN_TEMPLATE?>img/plus.png" alt="plus">
            </div>
            <div class="vg-element vg-half vg-center vg-firm-background-color1">
                <span class="vg-text vg-firm-color3">Add</span>
            </div>
        </a>
    </div>

    <?php if ($fpAdminSettingsData): ?>
        <?php
        $fpAdminSettingsId = (int)($fpAdminSettingsData['id'] ?? 0);
        $fpAdminSettingsEditUrl =
            $this->adminPath
            . 'edit/settings/'
            . $fpAdminSettingsId;

        $fpAdminHeaderSubtitle = trim((string)($fpAdminSettingsData['name'] ?? ''));
        if ($fpAdminHeaderSubtitle === '') {
            $fpAdminHeaderSubtitle = 'Основні параметри сайту';
        }

        $fpAdminAboutSubtitle = trim((string)($fpAdminSettingsData['about_name'] ?? ''));
        if ($fpAdminAboutSubtitle === '') {
            $fpAdminAboutSubtitle = 'Про нас';
        }

        /*
         * The settings-card preview is navigation copy, not page content.
         * Keep the editable contacts_intro field inside the Contacts editor.
         */
        $fpAdminContactsSubtitle =
            'Сторінка контактів, реквізити та структурований графік роботи';

        $fpAdminSettingsCards = [
            [
                'key' => 'header',
                'title' => 'Шапка сайту',
                'subtitle' => $fpAdminHeaderSubtitle,
                'image' => trim((string)($fpAdminSettingsData['img'] ?? '')),
                'url' => $fpAdminSettingsEditUrl . '?section=header',
                'order' => (int)($fpAdminSettingsData['header_menu_position'] ?? 10),
            ],
            [
                'key' => 'controls',
                'title' => 'Права панель',
                'subtitle' => 'Кошик, авторизація та соціальні мережі',
                'image' => '',
                'url' => $fpAdminSettingsEditUrl . '?section=controls',
                'order' => 15,
            ],
            [
                'key' => 'about',
                'title' => 'Про нас',
                'subtitle' => $fpAdminAboutSubtitle,
                'image' => trim((string)($fpAdminSettingsData['promo_img'] ?? '')),
                'url' => $fpAdminSettingsEditUrl . '?section=about',
                'order' => (int)($fpAdminSettingsData['about_menu_position'] ?? 20),
            ],
            [
                'key' => 'home-groups',
                'title' => 'Товарні групи головної',
                'subtitle' => 'Кількість товарів у вкладках головної сторінки',
                'image' => trim((string)($fpAdminSettingsData['home_groups_img'] ?? '')),
                'url' => $fpAdminSettingsEditUrl . '?section=home-groups',
                'order' => (int)($fpAdminSettingsData['home_groups_menu_position'] ?? 30),
            ],
            [
                'key' => 'catalog',
                'title' => 'Каталог',
                'subtitle' => 'Сортування та кількість товарів на сторінці',
                'image' => trim((string)($fpAdminSettingsData['catalog_img'] ?? '')),
                'url' => $fpAdminSettingsEditUrl . '?section=catalog',
                'order' => (int)($fpAdminSettingsData['catalog_menu_position'] ?? 35),
            ],
            [
                'key' => 'contacts',
                'title' => 'Контакти',
                'subtitle' => $fpAdminContactsSubtitle,
                'image' => '',
                'url' => $fpAdminSettingsEditUrl . '?section=contacts',
                'order' => (int)($fpAdminSettingsData['contacts_menu_position'] ?? 40),
            ],
            [
                'key' => 'visual-assets',
                'title' => 'Візуальне оформлення',
                'subtitle' => 'Керовані візуальні активи сайту',
                'image' => '',
                'url' => $this->adminPath . 'show/visual_assets',
                'order' => 42,
            ],
            [
                // FP_MEDIA_PROCESSING_SETTINGS_INDEX_CARD_05D1_3
                'key' => 'media-processing',
                'title' => 'Обробка зображень',
                'subtitle' => 'Профіль товарної галереї: 1600 px, JPEG 94',
                'image' => '',
                'url' => $fpAdminSettingsEditUrl
                    . '?section=media-processing',
                'order' => 45,
            ],
            [
                'key' => 'security',
                'title' => 'Безпека адмінки',
                'subtitle' => 'Адміністратори, логіни, паролі та захищений вхід',
                'image' => trim((string)($this->userId['img'] ?? '')),
                'url' => $this->adminPath . 'show/user',
                'order' => 90,
            ],
            [
                'key' => 'footer',
                'title' => 'Футер',
                'subtitle' => trim((string)($fpAdminFooterSettingsData['name'] ?? 'Контакти, навігація та логотип')),
                'image' => trim((string)($fpAdminFooterSettingsData['logo_img'] ?? '')),
                'url' => $this->adminPath . 'edit/footer_settings/' . (int)($fpAdminFooterSettingsData['id'] ?? 1),
                'order' => (int)($fpAdminFooterSettingsData['menu_position'] ?? 50),
            ],
        ];

        usort(
            $fpAdminSettingsCards,
            static function (array $left, array $right): int {
                $orderComparison = ((int)($left['order'] ?? 100))
                    <=> ((int)($right['order'] ?? 100));

                if ($orderComparison !== 0) {
                    return $orderComparison;
                }

                return strcmp(
                    (string)($left['key'] ?? ''),
                    (string)($right['key'] ?? '')
                );
            }
        );
        ?>

        <?php foreach ($fpAdminSettingsCards as $fpAdminCard): ?>
            <div
                class="vg-element vg-fourth fp-admin-settings-section-card"
                data-fp-admin-settings-key="<?=htmlspecialchars((string)($fpAdminCard['key'] ?? ''), ENT_QUOTES, 'UTF-8')?>"
                data-fp-admin-settings-order="<?= (int)($fpAdminCard['order'] ?? 100) ?>"
            >
                <a
                    href="<?=htmlspecialchars($fpAdminCard['url'], ENT_QUOTES, 'UTF-8')?>"
                    class="vg-wrap vg-element vg-full vg-firm-background-color4 vg-box-shadow show_element fp-admin-index-card__link fp-admin-settings-section-card__link"
                >
                    <div class="vg-element vg-half vg-center fp-admin-index-card__caption fp-admin-settings-section-card__caption">
                        <span class="vg-text vg-firm-color1 fp-admin-index-card__title fp-admin-settings-section-card__title">
                            <?=htmlspecialchars($fpAdminCard['title'], ENT_QUOTES, 'UTF-8')?>
                        </span>
                        <span class="fp-admin-index-card__subtitle fp-admin-settings-section-card__subtitle">
                            <?=htmlspecialchars($fpAdminCard['subtitle'], ENT_QUOTES, 'UTF-8')?>
                        </span>
                    </div>

                    <div class="vg-element vg-half vg-center fp-admin-index-card__media fp-admin-settings-section-card__media">
                        <?php if ($fpAdminCard['image'] !== ''): ?>
                            <img
                                src="<?=PATH . UPLOAD_DIR . htmlspecialchars(
                                    $fpAdminCard['image'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                )?>"
                                alt=""
                                loading="lazy"
                            >
                        <?php else: ?>
                            <img
                                src="<?=PATH.ADMIN_TEMPLATE?>img/pages.png"
                                alt=""
                                loading="lazy"
                            >
                        <?php endif; ?>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>

    <?php elseif ($fpAdminGoodsIndex && $this->data): ?>
        <div class="fp-admin-goods-accordion">
            <?php foreach ($fpAdminGoodsCategoryOrder as $fpAdminGoodsCategoryId): ?>
                <?php
                $fpAdminGoodsGroup = $fpAdminGoodsGroups[$fpAdminGoodsCategoryId] ?? null;

                if (
                    !$fpAdminGoodsGroup
                    || empty($fpAdminGoodsGroup['items'])
                ) {
                    continue;
                }

                $fpAdminGoodsGroupName = trim((string)($fpAdminGoodsGroup['name'] ?? ''));
                if ($fpAdminGoodsGroupName === '') {
                    $fpAdminGoodsGroupName = 'Розділ товарів';
                }
                ?>
                <details
                    class="fp-admin-goods-group"
                    data-fp-admin-collection-group
                    data-fp-admin-collection="goods"
                    data-fp-admin-group-id="<?=$fpAdminGoodsCategoryId?>"
                >
                    <summary class="fp-admin-goods-group__summary">
                        <span class="fp-admin-goods-group__arrow" aria-hidden="true"></span>
                        <span class="fp-admin-goods-group__count">
                            <?=count($fpAdminGoodsGroup['items'])?>
                        </span>
                        <span class="fp-admin-goods-group__title">
                            <?=htmlspecialchars($fpAdminGoodsGroupName, ENT_QUOTES, 'UTF-8')?>
                        </span>
                    </summary>

                    <div
                        class="fp-admin-goods-group__items"
                        data-fp-admin-sortable-entity-group
                        data-table="goods"
                        data-parent-id="<?=$fpAdminGoodsCategoryId?>"
                    >
                        <?php foreach ($fpAdminGoodsGroup['items'] as $data): ?>
                            <?php
                            $fpAdminEntityTitle = trim((string)($data['name'] ?? ''));
                            $fpAdminEntityImage = trim((string)($data['img'] ?? ''));
                            ?>
                            <div
                                class="vg-element fp-admin-entity-card fp-admin-orderable-card"
                                data-fp-admin-entity-id="<?=(int)$data['id']?>"
                            >
                                <a
                                    href="<?=$this->adminPath . 'edit/' . $this->table . '/' . (int)$data['id']?>"
                                    class="vg-wrap vg-element vg-full vg-firm-background-color4 vg-box-shadow show_element fp-admin-index-card__link fp-admin-entity-card__link"
                                >
                                    <div class="vg-element vg-half vg-center fp-admin-index-card__caption fp-admin-entity-card__caption">
                                        <span class="vg-text vg-firm-color1 fp-admin-index-card__title">
                                            <?=htmlspecialchars($fpAdminEntityTitle, ENT_QUOTES, 'UTF-8')?>
                                        </span>
                                    </div>

                                    <div class="vg-element vg-half vg-center fp-admin-index-card__media fp-admin-entity-card__media">
                                        <img
                                            src="<?=$fpAdminEntityImage !== ''
                                                ? PATH . UPLOAD_DIR . htmlspecialchars(
                                                    $fpAdminEntityImage,
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                )
                                                : PATH . ADMIN_TEMPLATE . 'img/pages.png'?>"
                                            alt=""
                                            loading="lazy"
                                        >
                                    </div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </details>
            <?php endforeach; ?>
        </div>
    <?php elseif ($fpAdminFiltersIndex && $this->data): ?>
        <div class="fp-admin-filter-accordion">
            <?php foreach ($fpAdminFilterCategoryOrder as $fpAdminFilterCategoryId): ?>
                <?php
                $fpAdminFilterGroup = $fpAdminFilterGroups[$fpAdminFilterCategoryId] ?? null;

                if (
                    !$fpAdminFilterGroup
                    || empty($fpAdminFilterGroup['items'])
                ) {
                    continue;
                }

                $fpAdminFilterGroupName = trim((string)($fpAdminFilterGroup['name'] ?? ''));
                if ($fpAdminFilterGroupName === '') {
                    $fpAdminFilterGroupName = 'Категорія фільтрів';
                }
                ?>
                <details
                    class="fp-admin-goods-group fp-admin-filter-group"
                    data-fp-admin-collection-group
                    data-fp-admin-collection="filters"
                    data-fp-admin-group-id="<?=$fpAdminFilterCategoryId?>"
                >
                    <summary class="fp-admin-goods-group__summary">
                        <span class="fp-admin-goods-group__arrow" aria-hidden="true"></span>
                        <span class="fp-admin-goods-group__count">
                            <?=count($fpAdminFilterGroup['items'])?>
                        </span>
                        <span class="fp-admin-goods-group__title">
                            <?=htmlspecialchars($fpAdminFilterGroupName, ENT_QUOTES, 'UTF-8')?>
                        </span>
                    </summary>

                    <div
                        class="fp-admin-goods-group__items fp-admin-filter-group__items"
                        <?php if ($fpAdminFilterCategoryId > 0): ?>
                            data-fp-admin-sortable-filter-group
                            data-parent-id="<?=$fpAdminFilterCategoryId?>"
                        <?php endif; ?>
                    >
                        <?php foreach ($fpAdminFilterGroup['items'] as $data): ?>
                            <?php
                            $fpAdminEntityTitle = trim((string)($data['name'] ?? ''));
                            $fpAdminEntityImage = trim((string)($data['img'] ?? ''));
                            ?>
                            <div
                                class="vg-element fp-admin-entity-card fp-admin-filter-card"
                                draggable="<?=$fpAdminFilterCategoryId > 0 ? 'true' : 'false'?>"
                                data-fp-admin-filter-id="<?=(int)$data['id']?>"
                            >
                                <span class="fp-admin-filter-card__drag" aria-hidden="true">⋮⋮</span>
                                <a
                                    href="<?=$this->adminPath . 'edit/' . $this->table . '/' . (int)$data['id']?>"
                                    class="vg-wrap vg-element vg-full vg-firm-background-color4 vg-box-shadow show_element fp-admin-index-card__link fp-admin-entity-card__link"
                                >
                                    <div class="vg-element vg-half vg-center fp-admin-index-card__caption fp-admin-entity-card__caption">
                                        <span class="vg-text vg-firm-color1 fp-admin-index-card__title">
                                            <?=htmlspecialchars($fpAdminEntityTitle, ENT_QUOTES, 'UTF-8')?>
                                        </span>
                                    </div>

                                    <div class="vg-element vg-half vg-center fp-admin-index-card__media fp-admin-entity-card__media">
                                        <img
                                            src="<?=$fpAdminEntityImage !== ''
                                                ? PATH . UPLOAD_DIR . htmlspecialchars(
                                                    $fpAdminEntityImage,
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                )
                                                : PATH . ADMIN_TEMPLATE . 'img/pages.png'?>"
                                            alt=""
                                            loading="lazy"
                                        >
                                    </div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </details>
            <?php endforeach; ?>
        </div>
    <?php elseif ($this->data): ?>
        <?php foreach ($this->data as $data): ?>
            <?php
            $fpAdminEntityTitle = trim((string)($data['name'] ?? ''));

            if (
                ($this->table ?? '') === 'information'
                && in_array($fpAdminEntityTitle, ['Новини', 'Контакти'], true)
            ) {
                continue;
            }

            $fpAdminEntityImage = trim((string)($data['img'] ?? ''));
            ?>
            <div class="vg-element vg-fourth fp-admin-entity-card">
                <a
                    href="<?=!empty($data['alias'])
                        ? $data['alias']
                        : $this->adminPath . 'edit/' . $this->table . '/' . $data['id']?>"
                    class="vg-wrap vg-element vg-full vg-firm-background-color4 vg-box-shadow show_element fp-admin-index-card__link fp-admin-entity-card__link"
                >
                    <div class="vg-element vg-half vg-center fp-admin-index-card__caption fp-admin-entity-card__caption">
                        <span class="vg-text vg-firm-color1 fp-admin-index-card__title">
                            <?=htmlspecialchars($fpAdminEntityTitle, ENT_QUOTES, 'UTF-8')?>
                        </span>
                    </div>

                    <div class="vg-element vg-half vg-center fp-admin-index-card__media fp-admin-entity-card__media">
                        <img
                            src="<?=$fpAdminEntityImage !== ''
                                ? PATH . UPLOAD_DIR . htmlspecialchars(
                                    $fpAdminEntityImage,
                                    ENT_QUOTES,
                                    'UTF-8'
                                )
                                : PATH . ADMIN_TEMPLATE . 'img/pages.png'?>"
                            alt=""
                            loading="lazy"
                        >
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

</div>
