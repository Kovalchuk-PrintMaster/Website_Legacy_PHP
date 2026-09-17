<!-- FP_SUPPLIER_CANONICAL_BINDING_V1 -->
<!-- FP_SUPPLIER_SHARED_CATALOG_UI_V1 -->
<?php
$fpSupplierCatalogTitle = 'Каталог товарів для брендування';
$fpSupplierCatalogUrl = $this->alias('supplier-catalog');

$fpSupplierEsc = static function ($value): string {
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
};

$fpSupplierProductActive = (
    !empty($supplierProduct)
    && is_array($supplierProduct)
);

$fpSupplierProductName = (
    $fpSupplierProductActive
    ? trim((string)($supplierProduct['name'] ?? ''))
    : ''
);

$fpSupplierCategoryById = [];
$fpSupplierCategoryChildren = [];

foreach (($supplierCategories ?? []) as $fpSupplierCategoryRow) {
    $fpSupplierCategoryId = trim(
        (string)($fpSupplierCategoryRow['external_id'] ?? '')
    );
    $fpSupplierCategoryName = trim(
        (string)($fpSupplierCategoryRow['name'] ?? '')
    );

    if (
        $fpSupplierCategoryId === ''
        || $fpSupplierCategoryName === ''
    ) {
        continue;
    }

    $fpSupplierCategoryById[$fpSupplierCategoryId] =
        $fpSupplierCategoryRow;
}

foreach (
    $fpSupplierCategoryById
    as $fpSupplierCategoryId
    => $fpSupplierCategoryRow
) {
    $fpSupplierCategoryParentId = trim(
        (string)(
            $fpSupplierCategoryRow['parent_external_id']
            ?? ''
        )
    );

    if (
        $fpSupplierCategoryParentId === ''
        || !isset(
            $fpSupplierCategoryById[
                $fpSupplierCategoryParentId
            ]
        )
    ) {
        $fpSupplierCategoryParentId = '';
    }

    $fpSupplierCategoryChildren[
        $fpSupplierCategoryParentId
    ][] = $fpSupplierCategoryId;
}

$fpSupplierSelectedRootId = (
    (string)($selectedCategory ?? '')
);
$fpSupplierCategoryGuard = 0;

while (
    $fpSupplierSelectedRootId !== ''
    && isset(
        $fpSupplierCategoryById[
            $fpSupplierSelectedRootId
        ]
    )
    && $fpSupplierCategoryGuard < 10
) {
    $fpSupplierSelectedParentId = trim(
        (string)(
            $fpSupplierCategoryById[
                $fpSupplierSelectedRootId
            ]['parent_external_id']
            ?? ''
        )
    );

    if (
        $fpSupplierSelectedParentId === ''
        || !isset(
            $fpSupplierCategoryById[
                $fpSupplierSelectedParentId
            ]
        )
    ) {
        break;
    }

    $fpSupplierSelectedRootId =
        $fpSupplierSelectedParentId;
    $fpSupplierCategoryGuard++;
}

$fpSupplierRootCategoryIds = (
    $fpSupplierCategoryChildren['']
    ?? []
);

$fpSupplierSelectedName = '';

/* FP_SUPPLIER_FILTER_ENTRY_PARITY_V1 */
/* FP_SUPPLIER_QUOTE_DIRECT_VIEW_V1 */
$fpSupplierRequestedView = (
    isset($_GET['view'])
    && is_string($_GET['view'])
)
    ? trim($_GET['view'])
    : '';

$fpSupplierQuoteViewRequested = (
    $fpSupplierRequestedView === 'quote-list'
);

$fpSupplierFiltersInitiallyOpen = (
    (string)($selectedCategory ?? '') === ''
    && !$fpSupplierQuoteViewRequested
);

if (
    (string)($selectedCategory ?? '') !== ''
    && isset(
        $fpSupplierCategoryById[
            (string)$selectedCategory
        ]
    )
) {
    $fpSupplierSelectedName = trim(
        (string)(
            $fpSupplierCategoryById[
                (string)$selectedCategory
            ]['name']
            ?? ''
        )
    );
}

$fpSupplierBaseQuery = [];

if ((string)($selectedCategory ?? '') !== '') {
    $fpSupplierBaseQuery['category'] =
        (string)$selectedCategory;
}

$fpSupplierSortQuery = $fpSupplierBaseQuery;
$fpSupplierSortQuery['per_page'] =
    (int)($selectedPerPage ?? 12);

$fpSupplierQuantityQuery = $fpSupplierBaseQuery;
$fpSupplierQuantityQuery['sort'] =
    (string)($selectedSort ?? 'name_asc');
?>
<main
    class="main fp-supplier-catalog"
    data-fp-surface="supplier-catalog"
    data-fp-indexation="noindex"
>
<?php if ($fpSupplierProductActive): ?>
    <?php
    $fpSupplierProductImages = (
        is_array($supplierProduct['_fp_images'] ?? null)
        ? $supplierProduct['_fp_images']
        : []
    );

    $fpSupplierProductImages = array_values(
        array_unique(
            array_filter(
                array_map(
                    static function ($value): string {
                        return trim((string)$value);
                    },
                    $fpSupplierProductImages
                ),
                static function (string $value): bool {
                    return $value !== '';
                }
            )
        )
    );

    $fpSupplierProductImage = (
        $fpSupplierProductImages[0]
        ?? ''
    );

    $fpSupplierGalleryHasThumbs =
        count($fpSupplierProductImages) > 1;
    $fpSupplierGalleryHasMoreThumbs =
        count($fpSupplierProductImages) > 3;

    $fpSupplierProductSku = trim(
        (string)(
            $supplierProduct['vendor_code']
            ?? $supplierProduct['external_id']
            ?? ''
        )
    );
    $fpSupplierProductExternalId = trim(
        (string)(
            $supplierProduct['external_id']
            ?? ''
        )
    );
    $fpSupplierProductCategory = trim(
        (string)(
            $supplierProduct['_fp_category_name']
            ?? ''
        )
    );
    $fpSupplierProductBrand = trim(
        (string)(
            $supplierProduct['brand']
            ?? ''
        )
    );

    if (
        mb_strtolower(
            $fpSupplierProductBrand,
            'UTF-8'
        ) === 'totobi'
    ) {
        $fpSupplierProductBrand = '';
    }

    $fpSupplierProductCurrency = trim(
        (string)(
            $supplierProduct['currency']
            ?? ''
        )
    );
    $fpSupplierProductCurrencyCode = mb_strtoupper(
        $fpSupplierProductCurrency,
        'UTF-8'
    );
    $fpSupplierProductCurrencyDisplay = (
        in_array(
            $fpSupplierProductCurrencyCode,
            ['UAH', 'ГРН', 'ГРН.'],
            true
        )
        ? 'грн.'
        : $fpSupplierProductCurrency
    );

    $fpSupplierProductPrice = (
        is_numeric($supplierProduct['price'] ?? null)
        ? (float)$supplierProduct['price']
        : 0.0
    );

    $fpSupplierProductMethods = (
        is_array(
            $supplierProduct['_fp_branding_methods']
            ?? null
        )
        ? array_values(
            array_filter(
                array_map(
                    static function ($value): string {
                        return trim((string)$value);
                    },
                    $supplierProduct[
                        '_fp_branding_methods'
                    ]
                )
            )
        )
        : []
    );

    $fpSupplierProductMethodsJson = json_encode(
        $fpSupplierProductMethods,
        JSON_UNESCAPED_UNICODE
        | JSON_UNESCAPED_SLASHES
    ) ?: '[]';

    $fpSupplierProductAttributes = (
        is_array(
            $supplierProduct['_fp_attributes']
            ?? null
        )
        ? $supplierProduct['_fp_attributes']
        : []
    );

    $fpSupplierProductVariants = (
        is_array($supplierProductVariants ?? null)
        ? $supplierProductVariants
        : []
    );

    $fpSupplierSummaryFeatureGroups = [];

    if ($fpSupplierProductMethods) {
        $fpSupplierSummaryFeatureGroups[] = [
            'name' => 'Рекомендовані методи нанесення',
            'values' => $fpSupplierProductMethods,
        ];
    }

    if ($fpSupplierProductSku !== '') {
        $fpSupplierSummaryFeatureGroups[] = [
            'name' => 'Артикул',
            'values' => [$fpSupplierProductSku],
        ];
    }

    if ($fpSupplierProductBrand !== '') {
        $fpSupplierSummaryFeatureGroups[] = [
            'name' => 'Бренд',
            'values' => [$fpSupplierProductBrand],
        ];
    }

    $fpSupplierSpecRows = [];

    foreach (
        $fpSupplierProductAttributes
        as $fpSupplierAttributeName
        => $fpSupplierAttributeValue
    ) {
        if (count($fpSupplierSpecRows) >= 30) {
            break;
        }

        if (
            is_array($fpSupplierAttributeValue)
            || is_object($fpSupplierAttributeValue)
        ) {
            continue;
        }

        $fpSupplierAttributeName = trim(
            (string)$fpSupplierAttributeName
        );
        $fpSupplierAttributeValue = trim(
            (string)$fpSupplierAttributeValue
        );

        if (
            $fpSupplierAttributeName === ''
            || $fpSupplierAttributeValue === ''
            || mb_strtolower(
                $fpSupplierAttributeName,
                'UTF-8'
            ) === 'група нанесення'
        ) {
            continue;
        }

        $fpSupplierSpecRows[] = [
            'name' => $fpSupplierAttributeName,
            'values' => [$fpSupplierAttributeValue],
        ];
    }

    $fpSupplierBackUrl = (
        $fpSupplierProductCategory !== ''
        && !empty(
            $supplierProduct['category_external_id']
        )
        ? $this->alias(
            'supplier-catalog',
            [
                'category'
                => (string)$supplierProduct[
                    'category_external_id'
                ],
            ]
        )
        : $fpSupplierCatalogUrl
    );
    ?>
    <?php
    $breadcrumbItems = [
        [
            'label' => 'ГОЛОВНА',
            'url' => $this->alias(),
        ],
        [
            'label' => 'КАТАЛОГ ТОВАРІВ ДЛЯ БРЕНДУВАННЯ',
            'url' => $fpSupplierCatalogUrl,
        ],
        [
            'label' => $fpSupplierProductName,
            'url' => null,
        ],
    ];
    ?>
    <div class="container fp-layout-container fp-page-heading">
        <?php include __DIR__ . '/include/breadcrumbs.php'; ?>
    </div>

    <section
        class="card-main fp-product-detail-page"
        data-fp-supplier-product-detail
    >
        <div class="container fp-product-detail__container">
            <div class="fp-product-detail">
                <h1 class="fp-product-detail__title page-title h1">
                    <?=$fpSupplierEsc(
                        $fpSupplierProductName
                    )?>
                </h1>

                <div
                    class="fp-product-detail__gallery<?=$fpSupplierGalleryHasThumbs
                        ? ''
                        : ' fp-product-detail__gallery_no-thumbs'?>"
                >
                    <?php if ($fpSupplierGalleryHasThumbs): ?>
                        <div
                            class="fp-product-detail__thumbs card-main-gallery-thumb<?=$fpSupplierGalleryHasMoreThumbs
                                ? ' card-main-gallery-thumb_has-more'
                                : ''?>"
                        >
                            <?php if (
                                $fpSupplierGalleryHasMoreThumbs
                            ): ?>
                                <span
                                    class="card-main-gallery-thumb__hint card-main-gallery-thumb__hint_up"
                                    aria-hidden="true"
                                ></span>
                                <span
                                    class="card-main-gallery-thumb__hint card-main-gallery-thumb__hint_down"
                                    aria-hidden="true"
                                ></span>
                            <?php endif; ?>

                            <div
                                class="card-main-gallery-thumb__container swiper-container"
                            >
                                <div class="swiper-wrapper">
                                    <?php foreach (
                                        $fpSupplierProductImages
                                        as $fpSupplierImage
                                    ): ?>
                                        <div
                                            class="card-main-gallery-thumb__slide swiper-slide"
                                        >
                                            <picture
                                                class="card-main-gallery-thumb__img"
                                            >
                                                <img
                                                    src="<?=$fpSupplierEsc(
                                                        $fpSupplierImage
                                                    )?>"
                                                    alt=""
                                                    loading="lazy"
                                                    decoding="async"
                                                    referrerpolicy="no-referrer"
                                                >
                                            </picture>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div
                        class="fp-product-detail__main-image card-main-gallery-slider"
                    >
                        <div
                            class="card-main-gallery-slider__container swiper-container"
                        >
                            <div class="swiper-wrapper">
                                <?php if (
                                    $fpSupplierProductImages
                                ): ?>
                                    <?php foreach (
                                        $fpSupplierProductImages
                                        as $fpSupplierImageIndex
                                        => $fpSupplierImage
                                    ): ?>
                                        <div
                                            class="card-main-gallery-slider__slide swiper-slide"
                                        >
                                            <a
                                                href="<?=$fpSupplierEsc(
                                                    $fpSupplierImage
                                                )?>"
                                                class="card-main-gallery-slider__img"
                                                data-fancybox="gallery"
                                                aria-label="Фото товару <?=$fpSupplierImageIndex + 1?>"
                                            >
                                                <img
                                                    src="<?=$fpSupplierEsc(
                                                        $fpSupplierImage
                                                    )?>"
                                                    alt="<?=$fpSupplierEsc(
                                                        $fpSupplierProductName
                                                    )?>"
                                                    <?=$fpSupplierImageIndex === 0
                                                        ? 'loading="eager"'
                                                        : 'loading="lazy"'?>
                                                    decoding="async"
                                                    referrerpolicy="no-referrer"
                                                >
                                            </a>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div
                                        class="card-main-gallery-slider__slide swiper-slide"
                                    >
                                        <div
                                            class="card-main-gallery-slider__img fp-supplier-catalog__media-placeholder"
                                            aria-hidden="true"
                                        >FP</div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="fp-product-detail__info">
                    <div
                        class="fp-product-detail-price card-main-info-price"
                        data-price-mode="<?=$fpSupplierProductPrice > 0
                            ? 'exact'
                            : 'request'?>"
                    >
                        <div
                            class="fp-product-detail-price__label card-main-info-price__text"
                        >
                            ціна:
                        </div>
                        <div
                            class="fp-product-detail-price__value card-main-info-price__num"
                        >
                            <span>
                                <?php if (
                                    $fpSupplierProductPrice > 0
                                ): ?>
                                    <?=$fpSupplierEsc(
                                        rtrim(
                                            rtrim(
                                                number_format(
                                                    $fpSupplierProductPrice,
                                                    2,
                                                    '.',
                                                    ' '
                                                ),
                                                '0'
                                            ),
                                            '.'
                                        )
                                    )?>
                                    <?=$fpSupplierEsc(
                                        $fpSupplierProductCurrencyDisplay
                                    )?>
                                <?php else: ?>
                                    Ціна за запитом
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>

                    <?php if (
                        $fpSupplierSummaryFeatureGroups
                    ): ?>
                        <div
                            class="fp-product-detail-features"
                            aria-label="Основна інформація про товар"
                        >
                            <?php foreach (
                                $fpSupplierSummaryFeatureGroups
                                as $fpSupplierFeatureGroup
                            ): ?>
                                <div
                                    class="fp-product-detail-features__row"
                                >
                                    <div
                                        class="fp-product-detail-features__group"
                                    >
                                        <?=$fpSupplierEsc(
                                            $fpSupplierFeatureGroup[
                                                'name'
                                            ]
                                        )?>
                                    </div>
                                    <div
                                        class="fp-product-detail-features__values"
                                    >
                                        <?php foreach (
                                            $fpSupplierFeatureGroup[
                                                'values'
                                            ]
                                            as $fpSupplierFeatureValue
                                        ): ?>
                                            <div
                                                class="fp-product-detail-features__value"
                                            >
                                                <?=$fpSupplierEsc(
                                                    $fpSupplierFeatureValue
                                                )?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div
                    class="fp-product-detail__actions card-main-info__sale"
                >
                    <section class="fp-product-communication">
                        <div
                            class="fp-product-communication__buttons"
                        >
                            <button
                                class="fp-product-communication__button fp-product-communication__button--text-only fp-supplier-catalog__quote-action"
                                type="button"
                                aria-pressed="false"
                                data-fp-quote-add
                                data-fp-quote-supplier="totobi"
                                data-fp-quote-external-id="<?=$fpSupplierEsc(
                                    $fpSupplierProductExternalId
                                )?>"
                                data-fp-quote-sku="<?=$fpSupplierEsc(
                                    $fpSupplierProductSku
                                )?>"
                                data-fp-quote-name="<?=$fpSupplierEsc(
                                    $fpSupplierProductName
                                )?>"
                                data-fp-quote-category="<?=$fpSupplierEsc(
                                    $fpSupplierProductCategory
                                )?>"
                                data-fp-quote-image="<?=$fpSupplierEsc(
                                    $fpSupplierProductImage
                                )?>"
                                data-fp-quote-methods="<?=$fpSupplierEsc(
                                    $fpSupplierProductMethodsJson
                                )?>"
                            >
                                <span>На прорахунок</span>
                            </button>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </section>

    <?php if (
        $fpSupplierSpecRows
        || $fpSupplierProductVariants
    ): ?>
        <section
            class="fp-product-details-tabs"
            data-fp-product-tabs
        >
            <div
                class="fp-product-details-tabs__nav"
                role="tablist"
                aria-label="Інформація про товар"
            >
                <?php if ($fpSupplierSpecRows): ?>
                    <button
                        type="button"
                        class="fp-product-details-tabs__tab is-active"
                        data-fp-product-tab-button
                        data-fp-product-tab-target="fp-supplier-specs"
                        role="tab"
                        aria-selected="true"
                        aria-controls="fp-supplier-specs"
                    >
                        Характеристики
                    </button>
                <?php endif; ?>

                <?php if (
                    $fpSupplierProductVariants
                ): ?>
                    <button
                        type="button"
                        class="fp-product-details-tabs__tab<?=$fpSupplierSpecRows
                            ? ''
                            : ' is-active'?>"
                        data-fp-product-tab-button
                        data-fp-product-tab-target="fp-supplier-variants"
                        role="tab"
                        aria-selected="<?=$fpSupplierSpecRows
                            ? 'false'
                            : 'true'?>"
                        aria-controls="fp-supplier-variants"
                    >
                        Варіанти та наявність
                    </button>
                <?php endif; ?>
            </div>

            <div class="fp-product-details-tabs__panels">
                <?php if ($fpSupplierSpecRows): ?>
                    <div
                        id="fp-supplier-specs"
                        class="fp-product-details-tabs__panel is-active"
                        data-fp-product-tab-panel
                        role="tabpanel"
                        aria-hidden="false"
                    >
                        <div
                            class="fp-product-details-tabs__content-body"
                        >
                            <div
                                class="fp-product-detail-features fp-product-details-tabs__features"
                                aria-label="Характеристики товару"
                            >
                                <?php foreach (
                                    $fpSupplierSpecRows
                                    as $fpSupplierSpecRow
                                ): ?>
                                    <div
                                        class="fp-product-detail-features__row"
                                    >
                                        <div
                                            class="fp-product-detail-features__group"
                                        >
                                            <?=$fpSupplierEsc(
                                                $fpSupplierSpecRow[
                                                    'name'
                                                ]
                                            )?>
                                        </div>
                                        <div
                                            class="fp-product-detail-features__values"
                                        >
                                            <?php foreach (
                                                $fpSupplierSpecRow[
                                                    'values'
                                                ]
                                                as $fpSupplierSpecValue
                                            ): ?>
                                                <div
                                                    class="fp-product-detail-features__value"
                                                >
                                                    <?=$fpSupplierEsc(
                                                        $fpSupplierSpecValue
                                                    )?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (
                    $fpSupplierProductVariants
                ): ?>
                    <div
                        id="fp-supplier-variants"
                        class="fp-product-details-tabs__panel<?=$fpSupplierSpecRows
                            ? ''
                            : ' is-active'?>"
                        data-fp-product-tab-panel
                        role="tabpanel"
                        aria-hidden="<?=$fpSupplierSpecRows
                            ? 'true'
                            : 'false'?>"
                        <?=$fpSupplierSpecRows ? 'hidden' : ''?>
                    >
                        <div
                            class="fp-product-details-tabs__content-body"
                        >
                            <div
                                class="fp-supplier-variant-table"
                                role="table"
                                aria-label="Варіанти та наявність"
                            >
                                <div
                                    class="fp-supplier-variant-table__row fp-supplier-variant-table__row--head"
                                    role="row"
                                >
                                    <span role="columnheader">
                                        Варіант
                                    </span>
                                    <span role="columnheader">
                                        Код
                                    </span>
                                    <span role="columnheader">
                                        Наявність
                                    </span>
                                </div>

                                <?php foreach (
                                    array_slice(
                                        $fpSupplierProductVariants,
                                        0,
                                        50
                                    )
                                    as $fpSupplierVariant
                                ): ?>
                                    <?php
                                    $fpSupplierVariantName = trim(
                                        (string)(
                                            $fpSupplierVariant['name']
                                            ?? $fpSupplierVariant[
                                                'external_id'
                                            ]
                                            ?? ''
                                        )
                                    );
                                    $fpSupplierVariantSku = trim(
                                        (string)(
                                            $fpSupplierVariant[
                                                'vendor_code'
                                            ]
                                            ?? ''
                                        )
                                    );
                                    $fpSupplierVariantStock = (
                                        $fpSupplierVariant['stock']
                                        ?? null
                                    );
                                    $fpSupplierVariantAvailable = (
                                        (int)(
                                            $fpSupplierVariant[
                                                'available'
                                            ]
                                            ?? 0
                                        ) === 1
                                    );
                                    ?>
                                    <div
                                        class="fp-supplier-variant-table__row"
                                        role="row"
                                    >
                                        <span role="cell">
                                            <?=$fpSupplierEsc(
                                                $fpSupplierVariantName !== ''
                                                    ? $fpSupplierVariantName
                                                    : 'Варіант'
                                            )?>
                                        </span>
                                        <span role="cell">
                                            <?=$fpSupplierEsc(
                                                $fpSupplierVariantSku !== ''
                                                    ? $fpSupplierVariantSku
                                                    : '—'
                                            )?>
                                        </span>
                                        <span role="cell">
                                            <?php if (
                                                $fpSupplierVariantStock
                                                !== null
                                                && $fpSupplierVariantStock
                                                !== ''
                                            ): ?>
                                                <?=$fpSupplierEsc(
                                                    $fpSupplierVariantStock
                                                )?>
                                            <?php else: ?>
                                                <?=$fpSupplierVariantAvailable
                                                    ? 'Доступно'
                                                    : 'Уточнюється'?>
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    <?php endif; ?>
<?php else: ?>
    <div
        class="fp-catalog-page fp-visual-system"
        data-fp-catalog-ui="1"
        data-fp-mobile-category-navigation="native"
        data-fp-catalog-url="<?=$fpSupplierEsc(
            $fpSupplierCatalogUrl
        )?>"
        data-fp-catalog-alias="<?=$fpSupplierEsc(
            (string)($selectedCategory ?? '')
        )?>"
    >
        <?php
        $breadcrumbItems = [
            [
                'label' => 'ГОЛОВНА',
                'url' => $this->alias(),
            ],
            [
                'label' => 'КАТАЛОГ ТОВАРІВ ДЛЯ БРЕНДУВАННЯ',
                'url' => null,
            ],
        ];
        ?>
        <div
            class="fp-layout-container fp-catalog-page__breadcrumbs fp-page-breadcrumbs"
        >
            <?php include __DIR__ . '/include/breadcrumbs.php'; ?>
        </div>

        <header
            class="fp-layout-container fp-catalog-page__header fp-page-header"
        >
            <h1 class="fp-page-title">
                <?=$fpSupplierEsc(
                    $fpSupplierCatalogTitle
                )?>
            </h1>
        </header>

        <section
            class="catalog-internal fp-catalog-page__content"
        >
            <div class="container fp-layout-container">
                <div class="catalog-internal-wrap">
                    <aside
                        class="catalog-aside"
                        aria-label="Фільтри каталогу"
                    >
                        <div class="catalog-aside__wrap">
                            <div class="catalog-aside-block">
                                <div
                                    class="catalog-aside-block__top"
                                >
                                    <div
                                        class="catalog-aside-block__title h2<?=$fpSupplierFiltersInitiallyOpen ? ' catalog-aside-block__title_open' : ''?>"
                                    >
                                        Фільтри
                                    </div>
                                    <div
                                        class="catalog-aside-sort-mobile"
                                    >
                                        <div
                                            class="catalog-aside-sort-mobile__button h2"
                                        >
                                            Сортування
                                        </div>
                                    </div>
                                    <a
                                        class="catalog-filter-wrap__remove"
                                        href="<?=$fpSupplierCatalogUrl?>"
                                        data-fp-catalog-clear-url="<?=$fpSupplierCatalogUrl?>"
                                    >
                                        <span>Очистити все</span>
                                        <span
                                            class="fp-catalog-clear__icon"
                                            aria-hidden="true"
                                        >×</span>
                                    </a>
                                </div>

                                <div
                                    class="catalog-aside-block__content catalog-aside-block__drop<?=$fpSupplierFiltersInitiallyOpen ? ' is-open' : ''?>"
                                >
                                    <button
                                        class="catalog-aside-block__drop-close"
                                        type="button"
                                        aria-label="Закрити фільтри"
                                    >
                                        <svg
                                            viewBox="0 0 27.33 27.01"
                                            width="100%"
                                            height="100%"
                                            aria-hidden="true"
                                        >
                                            <path
                                                d="M26.69.32a1.08 1.08 0 0 0-1.54 0L.32 25.15a1.08 1.08 0 0 0 0 1.54 1.09 1.09 0 0 0 1.54 0L26.69 1.86a1.08 1.08 0 0 0 0-1.54z"
                                            ></path>
                                            <path
                                                d="M27 25.15L1.88.32a1.1 1.1 0 0 0-1.56 0 1.08 1.08 0 0 0 0 1.54l25.12 24.83a1.13 1.13 0 0 0 .78.32 1.11 1.11 0 0 0 .78-.32 1.08 1.08 0 0 0 0-1.54z"
                                            ></path>
                                        </svg>
                                    </button>

                                    <details
                                        class="fp-catalog-filter-section"
                                        open
                                    >
                                        <summary
                                            class="fp-catalog-filter-section__summary"
                                        >
                                            <span>
                                                Категорії товарів
                                            </span>
                                            <span
                                                class="fp-catalog-filter-section__arrow"
                                                aria-hidden="true"
                                            ></span>
                                        </summary>

                                        <ul
                                            class="fp-catalog-category-list"
                                        >
                                            <li
                                                class="fp-catalog-category-item"
                                            >
                                                <a
                                                    class="fp-catalog-category-link<?=((string)($selectedCategory ?? '') === '') ? ' is-active' : ''?>"
                                                    href="<?=$fpSupplierCatalogUrl?>"
                                                >
                                                    <span>
                                                        Усі товари
                                                    </span>
                                                </a>
                                            </li>

                                            <?php foreach (
                                                $fpSupplierRootCategoryIds
                                                as $fpSupplierRootCategoryId
                                            ): ?>
                                                <?php
                                                $fpSupplierRootCategory = (
                                                    $fpSupplierCategoryById[
                                                        $fpSupplierRootCategoryId
                                                    ]
                                                    ?? []
                                                );
                                                $fpSupplierRootCategoryName =
                                                    trim(
                                                        (string)(
                                                            $fpSupplierRootCategory[
                                                                'name'
                                                            ]
                                                            ?? ''
                                                        )
                                                    );
                                                $fpSupplierRootChildren = (
                                                    $fpSupplierCategoryChildren[
                                                        $fpSupplierRootCategoryId
                                                    ]
                                                    ?? []
                                                );
                                                $fpSupplierRootActive = (
                                                    (string)(
                                                        $selectedCategory
                                                        ?? ''
                                                    )
                                                    === $fpSupplierRootCategoryId
                                                );
                                                $fpSupplierRootOpen = (
                                                    $fpSupplierSelectedRootId
                                                    === $fpSupplierRootCategoryId
                                                );

                                                if (
                                                    $fpSupplierRootCategoryName
                                                    === ''
                                                ) {
                                                    continue;
                                                }
                                                ?>
                                                <li
                                                    class="fp-catalog-category-item<?=$fpSupplierRootOpen ? ' is-active' : ''?>"
                                                >
                                                    <?php if (
                                                        $fpSupplierRootChildren
                                                    ): ?>
                                                        <details
                                                            class="fp-catalog-category-node"
                                                            <?=$fpSupplierRootOpen
                                                                ? 'open'
                                                                : ''?>
                                                        >
                                                            <summary
                                                                class="fp-catalog-category-node__summary"
                                                            >
                                                                <span
                                                                    class="fp-catalog-category-node__name"
                                                                >
                                                                    <?=$fpSupplierEsc(
                                                                        $fpSupplierRootCategoryName
                                                                    )?>
                                                                </span>
                                                                <span
                                                                    class="fp-catalog-category-node__arrow"
                                                                    aria-hidden="true"
                                                                ></span>
                                                            </summary>

                                                            <div
                                                                class="fp-catalog-category-filter-panel"
                                                            >
                                                                <ul
                                                                    class="fp-catalog-category-list"
                                                                >
                                                                    <li
                                                                        class="fp-catalog-category-item"
                                                                    >
                                                                        <a
                                                                            class="fp-catalog-category-link<?=$fpSupplierRootActive ? ' is-active' : ''?>"
                                                                            href="<?=$this->alias(
                                                                                'supplier-catalog',
                                                                                array_merge(
                                                                                    $fpSupplierSortQuery,
                                                                                    [
                                                                                        'category'
                                                                                        => $fpSupplierRootCategoryId,
                                                                                        'sort'
                                                                                        => (string)($selectedSort ?? 'name_asc'),
                                                                                    ]
                                                                                )
                                                                            )?>"
                                                                        >
                                                                            <span>
                                                                                Усі в розділі
                                                                            </span>
                                                                        </a>
                                                                    </li>

                                                                    <?php foreach (
                                                                        $fpSupplierRootChildren
                                                                        as $fpSupplierChildCategoryId
                                                                    ): ?>
                                                                        <?php
                                                                        $fpSupplierChildCategory = (
                                                                            $fpSupplierCategoryById[
                                                                                $fpSupplierChildCategoryId
                                                                            ]
                                                                            ?? []
                                                                        );
                                                                        $fpSupplierChildCategoryName =
                                                                            trim(
                                                                                (string)(
                                                                                    $fpSupplierChildCategory[
                                                                                        'name'
                                                                                    ]
                                                                                    ?? ''
                                                                                )
                                                                            );
                                                                        $fpSupplierChildActive = (
                                                                            (string)(
                                                                                $selectedCategory
                                                                                ?? ''
                                                                            )
                                                                            === $fpSupplierChildCategoryId
                                                                        );

                                                                        if (
                                                                            $fpSupplierChildCategoryName
                                                                            === ''
                                                                        ) {
                                                                            continue;
                                                                        }
                                                                        ?>
                                                                        <li
                                                                            class="fp-catalog-category-item"
                                                                        >
                                                                            <a
                                                                                class="fp-catalog-category-link<?=$fpSupplierChildActive ? ' is-active' : ''?>"
                                                                                href="<?=$this->alias(
                                                                                    'supplier-catalog',
                                                                                    array_merge(
                                                                                        $fpSupplierSortQuery,
                                                                                        [
                                                                                            'category'
                                                                                            => $fpSupplierChildCategoryId,
                                                                                            'sort'
                                                                                            => (string)($selectedSort ?? 'name_asc'),
                                                                                        ]
                                                                                    )
                                                                                )?>"
                                                                            >
                                                                                <span>
                                                                                    <?=$fpSupplierEsc(
                                                                                        $fpSupplierChildCategoryName
                                                                                    )?>
                                                                                </span>
                                                                            </a>
                                                                        </li>
                                                                    <?php endforeach; ?>
                                                                </ul>
                                                            </div>
                                                        </details>
                                                    <?php else: ?>
                                                        <a
                                                            class="fp-catalog-category-link<?=$fpSupplierRootActive ? ' is-active' : ''?>"
                                                            href="<?=$this->alias(
                                                                'supplier-catalog',
                                                                array_merge(
                                                                    $fpSupplierSortQuery,
                                                                    [
                                                                        'category'
                                                                        => $fpSupplierRootCategoryId,
                                                                        'sort'
                                                                        => (string)($selectedSort ?? 'name_asc'),
                                                                    ]
                                                                )
                                                            )?>"
                                                        >
                                                            <span>
                                                                <?=$fpSupplierEsc(
                                                                    $fpSupplierRootCategoryName
                                                                )?>
                                                            </span>
                                                            <span
                                                                class="fp-catalog-category-link__arrow"
                                                                aria-hidden="true"
                                                            ></span>
                                                        </a>
                                                    <?php endif; ?>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </details>
                                </div>
                            </div>
                        </div>
                    </aside>

                    <section
                        class="catalog-section catalog-section__four"
                    >
                        <div class="catalog-section-top">
                            <div
                                class="catalog-section-top-items"
                            >
                                <div
                                    class="catalog-section-top-items__title catalog-section-top-items__unit"
                                >
                                    Відсортувати:
                                </div>

                                <?php
                                $fpSupplierPriceSort = (
                                    ($selectedSort ?? '')
                                    === 'price_asc'
                                    ? 'price_desc'
                                    : 'price_asc'
                                );
                                $fpSupplierNameSort = (
                                    ($selectedSort ?? '')
                                    === 'name_asc'
                                    ? 'name_desc'
                                    : 'name_asc'
                                );
                                ?>

                                <a
                                    class="catalog-section-top-items__unit catalog-section-top-items__toggle<?=($selectedSort ?? '') === 'price_desc' ? ' order_desc' : ''?>"
                                    href="<?=$this->alias(
                                        'supplier-catalog',
                                        array_merge(
                                            $fpSupplierSortQuery,
                                            [
                                                'sort'
                                                => $fpSupplierPriceSort,
                                            ]
                                        )
                                    )?>"
                                >
                                    <span>Ціні</span>
                                    <span
                                        class="fp-catalog-toolbar__arrow"
                                        aria-hidden="true"
                                    ></span>
                                </a>

                                <a
                                    class="catalog-section-top-items__unit catalog-section-top-items__toggle<?=($selectedSort ?? '') === 'name_desc' ? ' order_desc' : ''?>"
                                    href="<?=$this->alias(
                                        'supplier-catalog',
                                        array_merge(
                                            $fpSupplierSortQuery,
                                            [
                                                'sort'
                                                => $fpSupplierNameSort,
                                            ]
                                        )
                                    )?>"
                                >
                                    <span>Назві</span>
                                    <span
                                        class="fp-catalog-toolbar__arrow"
                                        aria-hidden="true"
                                    ></span>
                                </a>

                                <details
                                    class="fp-catalog-quantity"
                                >
                                    <summary
                                        class="catalog-section-top-items__unit catalog-section-top-items__toggle"
                                    >
                                        <span>
                                            Показувати:
                                            <?=intval(
                                                $selectedPerPage
                                                ?? 12
                                            )?>
                                        </span>
                                        <span
                                            class="fp-catalog-toolbar__arrow"
                                            aria-hidden="true"
                                        ></span>
                                    </summary>
                                    <div class="qtyItems">
                                        <?php foreach (
                                            [12, 24, 48]
                                            as $fpSupplierPerPageOption
                                        ): ?>
                                            <a
                                                href="<?=$this->alias(
                                                    'supplier-catalog',
                                                    array_merge(
                                                        $fpSupplierQuantityQuery,
                                                        [
                                                            'per_page'
                                                            => $fpSupplierPerPageOption,
                                                        ]
                                                    )
                                                )?>"
                                            >
                                                <?=$fpSupplierPerPageOption?>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                </details>
                            </div>
                        </div>

                        <?php if (
                            empty($supplierOffers)
                        ): ?>
                            <div class="fp-catalog-empty">
                                <h2>
                                    У цій категорії зараз немає
                                    доступних позицій
                                </h2>
                                <p>
                                    Оберіть іншу категорію або
                                    поверніться до всього каталогу.
                                </p>
                                <a
                                    class="fp-catalog-button"
                                    href="<?=$fpSupplierCatalogUrl?>"
                                >
                                    Показати всі товари
                                </a>
                            </div>
                        <?php else: ?>
                            <div
                                class="catalog-section__wrapper"
                            >
                                <div
                                    class="catalog-section-items"
                                >
                                    <div
                                        class="catalog-section-items__wrapper"
                                    >
                                        <?php foreach (
                                            $supplierOffers
                                            as $fpSupplierOffer
                                        ): ?>
                                            <?php
                                            $fpSupplierOfferName = trim(
                                                (string)(
                                                    $fpSupplierOffer['name']
                                                    ?? ''
                                                )
                                            );
                                            $fpSupplierOfferExternalId =
                                                trim(
                                                    (string)(
                                                        $fpSupplierOffer[
                                                            'external_id'
                                                        ]
                                                        ?? ''
                                                    )
                                                );
                                            $fpSupplierOfferSku = trim(
                                                (string)(
                                                    $fpSupplierOffer[
                                                        'vendor_code'
                                                    ]
                                                    ?? $fpSupplierOfferExternalId
                                                )
                                            );
                                            $fpSupplierOfferDetailUrl =
                                                $this->alias(
                                                    'supplier-catalog',
                                                    [
                                                        'product'
                                                        => $fpSupplierOfferExternalId,
                                                    ]
                                                );
                                            $fpSupplierOfferImage = trim(
                                                (string)(
                                                    $fpSupplierOffer[
                                                        '_fp_preview_image'
                                                    ]
                                                    ?? ''
                                                )
                                            );
                                            $fpSupplierOfferCategory = trim(
                                                (string)(
                                                    $fpSupplierOffer[
                                                        '_fp_category_name'
                                                    ]
                                                    ?? ''
                                                )
                                            );
                                            $fpSupplierOfferCurrency = trim(
                                                (string)(
                                                    $fpSupplierOffer[
                                                        'currency'
                                                    ]
                                                    ?? ''
                                                )
                                            );
                                            $fpSupplierOfferPriceNumber = (
                                                is_numeric(
                                                    $fpSupplierOffer[
                                                        'price'
                                                    ]
                                                    ?? null
                                                )
                                                ? (float)$fpSupplierOffer[
                                                    'price'
                                                ]
                                                : 0.0
                                            );
                                            $fpSupplierOfferMethods = (
                                                is_array(
                                                    $fpSupplierOffer[
                                                        '_fp_branding_methods'
                                                    ]
                                                    ?? null
                                                )
                                                ? array_values(
                                                    array_filter(
                                                        array_map(
                                                            static function (
                                                                $value
                                                            ): string {
                                                                return trim(
                                                                    (string)$value
                                                                );
                                                            },
                                                            $fpSupplierOffer[
                                                                '_fp_branding_methods'
                                                            ]
                                                        )
                                                    )
                                                )
                                                : []
                                            );
                                            $fpSupplierOfferMethodsJson =
                                                json_encode(
                                                    $fpSupplierOfferMethods,
                                                    JSON_UNESCAPED_UNICODE
                                                    | JSON_UNESCAPED_SLASHES
                                                ) ?: '[]';
                                            $fpSupplierOfferMethodsText =
                                                implode(
                                                    ', ',
                                                    array_slice(
                                                        $fpSupplierOfferMethods,
                                                        0,
                                                        3
                                                    )
                                                );
                                            $fpSupplierOfferCurrencyCode =
                                                mb_strtoupper(
                                                    $fpSupplierOfferCurrency,
                                                    'UTF-8'
                                                );
                                            $fpSupplierOfferCurrencyDisplay = (
                                                in_array(
                                                    $fpSupplierOfferCurrencyCode,
                                                    [
                                                        'UAH',
                                                        'ГРН',
                                                        'ГРН.',
                                                    ],
                                                    true
                                                )
                                                ? 'грн.'
                                                : $fpSupplierOfferCurrency
                                            );
                                            ?>
                                            <article
                                                class="fp-product-card fp-grid-card fp-product-card--catalog"
                                                data-fp-supplier-offer
                                            >
                                                <a
                                                    class="fp-product-card__image"
                                                    href="<?=$fpSupplierOfferDetailUrl?>"
                                                    data-fp-product-detail-link
                                                    aria-label="Переглянути <?=$fpSupplierEsc(
                                                        $fpSupplierOfferName
                                                    )?>"
                                                >
                                                    <?php if (
                                                        $fpSupplierOfferImage
                                                        !== ''
                                                    ): ?>
                                                        <img
                                                            src="<?=$fpSupplierEsc(
                                                                $fpSupplierOfferImage
                                                            )?>"
                                                            alt="<?=$fpSupplierEsc(
                                                                $fpSupplierOfferName
                                                            )?>"
                                                            loading="lazy"
                                                            decoding="async"
                                                            referrerpolicy="no-referrer"
                                                        >
                                                    <?php else: ?>
                                                        <span
                                                            class="fp-supplier-catalog__media-placeholder"
                                                            aria-hidden="true"
                                                        >FP</span>
                                                    <?php endif; ?>
                                                </a>

                                                <div
                                                    class="fp-product-card__body"
                                                >
                                                    <h2
                                                        class="fp-product-card__title"
                                                    >
                                                        <a
                                                            href="<?=$fpSupplierOfferDetailUrl?>"
                                                            data-fp-product-title-link
                                                        >
                                                            <?=$fpSupplierEsc(
                                                                $fpSupplierOfferName
                                                            )?>
                                                        </a>
                                                    </h2>

                                                    <div
                                                        class="fp-product-card__excerpt"
                                                    >
                                                        <?php if (
                                                            $fpSupplierOfferMethodsText
                                                            !== ''
                                                        ): ?>
                                                            <strong>
                                                                Рекомендовані методи нанесення:
                                                            </strong>
                                                            <?=$fpSupplierEsc(
                                                                $fpSupplierOfferMethodsText
                                                            )?>
                                                        <?php endif; ?>
                                                    </div>

                                                    <div
                                                        class="fp-product-card__price"
                                                        data-price-mode="<?=$fpSupplierOfferPriceNumber > 0
                                                            ? 'exact'
                                                            : 'request'?>"
                                                    >
                                                        <span
                                                            class="fp-product-card__price-label"
                                                        >
                                                            ціна:
                                                        </span>
                                                        <span
                                                            class="fp-product-card__current-price"
                                                        >
                                                            <?php if (
                                                                $fpSupplierOfferPriceNumber
                                                                > 0
                                                            ): ?>
                                                                <?=$fpSupplierEsc(
                                                                    rtrim(
                                                                        rtrim(
                                                                            number_format(
                                                                                $fpSupplierOfferPriceNumber,
                                                                                2,
                                                                                '.',
                                                                                ' '
                                                                            ),
                                                                            '0'
                                                                        ),
                                                                        '.'
                                                                    )
                                                                )?>
                                                                <?=$fpSupplierEsc(
                                                                    $fpSupplierOfferCurrencyDisplay
                                                                )?>
                                                            <?php else: ?>
                                                                Ціна за запитом
                                                            <?php endif; ?>
                                                        </span>
                                                    </div>
                                                </div>

                                                <button
                                                    class="fp-product-card__button fp-supplier-catalog__quote-action"
                                                    type="button"
                                                    aria-pressed="false"
                                                    data-fp-quote-add
                                                    data-fp-quote-supplier="totobi"
                                                    data-fp-quote-external-id="<?=$fpSupplierEsc(
                                                        $fpSupplierOfferExternalId
                                                    )?>"
                                                    data-fp-quote-sku="<?=$fpSupplierEsc(
                                                        $fpSupplierOfferSku
                                                    )?>"
                                                    data-fp-quote-name="<?=$fpSupplierEsc(
                                                        $fpSupplierOfferName
                                                    )?>"
                                                    data-fp-quote-category="<?=$fpSupplierEsc(
                                                        $fpSupplierOfferCategory
                                                    )?>"
                                                    data-fp-quote-image="<?=$fpSupplierEsc(
                                                        $fpSupplierOfferImage
                                                    )?>"
                                                    data-fp-quote-methods="<?=$fpSupplierEsc(
                                                        $fpSupplierOfferMethodsJson
                                                    )?>"
                                                >
                                                    На прорахунок
                                                </button>
                                            </article>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </section>
                </div>
            </div>
        </section>
    </div>
<?php endif; ?>

    <section
        id="fp-quote-list"
        class="fp-layout-container fp-supplier-quote"
        data-fp-quote-panel
    >
        <div class="fp-supplier-quote__header">
            <div>
                <p class="fp-supplier-quote__eyebrow">
                    Підбірка товарів
                </p>
                <h2>Товари на прорахунок</h2>
                <p>
                    Додайте кілька товарів, вкажіть орієнтовну
                    кількість і спосіб нанесення, а потім
                    передайте список менеджеру.
                </p>
            </div>

            <button
                class="fp-supplier-quote__clear"
                type="button"
                data-fp-quote-clear
                hidden
            >
                Очистити список
            </button>
        </div>

        <div
            class="fp-supplier-quote__empty"
            data-fp-quote-empty
        >
            Список поки порожній. Додайте потрібні позиції
            з каталогу вище.
        </div>

        <div
            class="fp-supplier-quote__items"
            data-fp-quote-items
            aria-live="polite"
        ></div>

        <div
            class="fp-supplier-quote__request"
            data-fp-quote-request
            hidden
        >
            <?php
            $fpCommunicationConfig = [
                'id' => 'fp-supplier-quote-request',
                'title' => 'Передати список на прорахунок',
                'product_id' => 0,
                'product_name' => 'Список на прорахунок',
                'product_url' => (
                    $_SERVER['REQUEST_URI']
                    ?? '/supplier-catalog/'
                ),
                'variant' => 'panel',
            ];

            include __DIR__
                . '/include/communicationRequestForm.php';

            unset($fpCommunicationConfig);
            ?>
        </div>
    </section>
</main>
