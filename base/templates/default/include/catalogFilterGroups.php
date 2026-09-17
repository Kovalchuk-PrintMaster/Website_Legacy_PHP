<?php
/**
 * ForPrint public catalog filter values grouped by filters_categories.
 *
 * Expected variables:
 * - $catalogFilters
 * - $fpSelectedFilters
 *
 * Contract:
 * - one global "Вибрати всі" control preserves existing filters[] behavior;
 * - filter values stay ordered by filter-category position, then value position;
 * - >=1920px may expose the owning filter-category heading and its entity-owned
 *   thumbnail; narrower viewports retain the compact flat visual list;
 * - thumbnail media comes only from filters_categories.img/gallery_img.
 */

$fpCatalogFilterGroups = is_array($catalogFilters ?? null)
    ? array_values($catalogFilters)
    : [];

$fpSelectedFilters = is_array($fpSelectedFilters ?? null)
    ? array_values(array_unique(array_map('intval', $fpSelectedFilters)))
    : [];

usort(
    $fpCatalogFilterGroups,
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

$fpCatalogRenderableFilterGroups = [];
$fpCatalogSeenFilterIds = [];

foreach ($fpCatalogFilterGroups as $fpCatalogFilterGroup) {
    $fpCatalogFilterValues = is_array(
        $fpCatalogFilterGroup['values'] ?? null
    )
        ? array_values($fpCatalogFilterGroup['values'])
        : [];

    usort(
        $fpCatalogFilterValues,
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

    $fpCatalogRenderableFilterValues = [];

    foreach ($fpCatalogFilterValues as $fpCatalogFilterValue) {
        $fpCatalogFilterId = (int)(
            $fpCatalogFilterValue['id'] ?? 0
        );
        $fpCatalogFilterName = trim(
            (string)($fpCatalogFilterValue['name'] ?? '')
        );

        if (
            $fpCatalogFilterId < 1
            || $fpCatalogFilterName === ''
            || isset($fpCatalogSeenFilterIds[$fpCatalogFilterId])
        ) {
            continue;
        }

        $fpCatalogSeenFilterIds[$fpCatalogFilterId] = true;
        $fpCatalogRenderableFilterValues[] = $fpCatalogFilterValue;
    }

    if (!$fpCatalogRenderableFilterValues) {
        continue;
    }

    $fpCatalogRenderableFilterGroups[] = [
        'group' => $fpCatalogFilterGroup,
        'values' => $fpCatalogRenderableFilterValues,
    ];
}
?>
<?php if ($fpCatalogRenderableFilterGroups): ?>
    <div
        class="fp-catalog-filter-set"
        data-fp-filter-scope
    >
        <label class="fp-catalog-filter-select-all">
            <input
                type="checkbox"
                data-fp-filter-select-all
            >
            <span>Вибрати всі</span>
        </label>

        <ul class="fp-catalog-filter-values">
            <?php foreach ($fpCatalogRenderableFilterGroups as $fpCatalogRenderableFilterGroup): ?>
                <?php
                $fpCatalogFilterGroup = $fpCatalogRenderableFilterGroup['group'];
                $fpCatalogFilterGroupName = trim(
                    (string)($fpCatalogFilterGroup['name'] ?? '')
                );
                $fpCatalogFilterGroupThumbnailSources = [];

                if ((int)($fpCatalogFilterGroup['show_thumbnail'] ?? 1) === 1) {
                    $fpCatalogFilterGroupMainImage = trim(
                        (string)($fpCatalogFilterGroup['img'] ?? '')
                    );

                    if ($fpCatalogFilterGroupMainImage !== '') {
                        $fpCatalogFilterGroupMainUrl = trim(
                            (string)$this->img($fpCatalogFilterGroupMainImage)
                        );

                        if ($fpCatalogFilterGroupMainUrl !== '') {
                            $fpCatalogFilterGroupThumbnailSources[] =
                                $fpCatalogFilterGroupMainUrl;
                        }
                    }

                    $fpCatalogFilterGroupGalleryRaw =
                        $fpCatalogFilterGroup['gallery_img'] ?? [];
                    $fpCatalogFilterGroupGallery = is_array(
                        $fpCatalogFilterGroupGalleryRaw
                    )
                        ? $fpCatalogFilterGroupGalleryRaw
                        : json_decode(
                            (string)$fpCatalogFilterGroupGalleryRaw,
                            true
                        );

                    if (is_array($fpCatalogFilterGroupGallery)) {
                        foreach (
                            $fpCatalogFilterGroupGallery
                            as $fpCatalogFilterGroupGalleryImage
                        ) {
                            $fpCatalogFilterGroupGalleryImage = trim(
                                (string)$fpCatalogFilterGroupGalleryImage
                            );

                            if ($fpCatalogFilterGroupGalleryImage === '') {
                                continue;
                            }

                            $fpCatalogFilterGroupGalleryUrl = trim(
                                (string)$this->img(
                                    $fpCatalogFilterGroupGalleryImage
                                )
                            );

                            if (
                                $fpCatalogFilterGroupGalleryUrl !== ''
                                && !in_array(
                                    $fpCatalogFilterGroupGalleryUrl,
                                    $fpCatalogFilterGroupThumbnailSources,
                                    true
                                )
                            ) {
                                $fpCatalogFilterGroupThumbnailSources[] =
                                    $fpCatalogFilterGroupGalleryUrl;
                            }
                        }
                    }
                }

                $fpCatalogFilterGroupThumbnailReady =
                    !empty($fpCatalogFilterGroupThumbnailSources);
                $fpCatalogFilterGroupThumbnailJson =
                    $fpCatalogFilterGroupThumbnailReady
                        ? json_encode(
                            array_values(
                                $fpCatalogFilterGroupThumbnailSources
                            ),
                            JSON_UNESCAPED_UNICODE
                            | JSON_UNESCAPED_SLASHES
                        )
                        : '[]';

                if (!is_string($fpCatalogFilterGroupThumbnailJson)) {
                    $fpCatalogFilterGroupThumbnailJson = '[]';
                    $fpCatalogFilterGroupThumbnailReady = false;
                }
                ?>
                <?php if ($fpCatalogFilterGroupName !== ''): ?>
                    <li class="fp-catalog-filter-group-header">
                        <div class="fp-catalog-filter-group-header__content">
                            <?php if ($fpCatalogFilterGroupThumbnailReady): ?>
                                <span
                                    class="fp-catalog-filter-group-thumbnail"
                                    data-fp-catalog-filter-group-thumbnail
                                    data-fp-catalog-filter-group-thumbnail-sources="<?=htmlspecialchars(
                                        $fpCatalogFilterGroupThumbnailJson,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    )?>"
                                    aria-hidden="true"
                                ></span>
                            <?php endif; ?>
                            <span class="fp-catalog-filter-group-header__name">
                                <?=htmlspecialchars(
                                    $fpCatalogFilterGroupName,
                                    ENT_QUOTES,
                                    'UTF-8'
                                )?>
                            </span>
                        </div>
                    </li>
                <?php endif; ?>

                <?php foreach ($fpCatalogRenderableFilterGroup['values'] as $fpCatalogFilterValue): ?>
                    <?php
                    $fpCatalogFilterId = (int)(
                        $fpCatalogFilterValue['id'] ?? 0
                    );
                    ?>
                    <li class="fp-catalog-filter-value">
                        <label class="fp-catalog-filter-value__label">
                            <input
                                type="checkbox"
                                name="filters[]"
                                value="<?=$fpCatalogFilterId?>"
                                <?=in_array(
                                    $fpCatalogFilterId,
                                    $fpSelectedFilters,
                                    true
                                ) ? 'checked' : ''?>
                            >
                            <span
                                class="fp-catalog-filter-value__box"
                                aria-hidden="true"
                            ></span>
                            <span class="fp-catalog-filter-value__name">
                                <?=htmlspecialchars(
                                    (string)(
                                        $fpCatalogFilterValue['name'] ?? ''
                                    ),
                                    ENT_QUOTES,
                                    'UTF-8'
                                )?>
                            </span>
                            <?php if (isset($fpCatalogFilterValue['count'])): ?>
                                <small>
                                    (<?=(int)$fpCatalogFilterValue['count']?>)
                                </small>
                            <?php endif; ?>
                        </label>
                    </li>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>
