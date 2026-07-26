<?php
/**
 * ForPrint public catalog filter values.
 *
 * Expected variables:
 * - $catalogFilters
 * - $fpSelectedFilters
 *
 * Public rendering deliberately omits filter-group headings. The active
 * catalog category owns one visible filter scope containing:
 *
 * - one "Вибрати всі" control;
 * - all valid filter values ordered first by group position and then by value
 *   position;
 * - stable filters[] request values and optional product counts.
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

$fpCatalogFilterValuesById = [];

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

    foreach ($fpCatalogFilterValues as $fpCatalogFilterValue) {
        $fpCatalogFilterId = (int)($fpCatalogFilterValue['id'] ?? 0);

        if (
            $fpCatalogFilterId < 1
            || isset($fpCatalogFilterValuesById[$fpCatalogFilterId])
        ) {
            continue;
        }

        $fpCatalogFilterValuesById[$fpCatalogFilterId]
            = $fpCatalogFilterValue;
    }
}

$fpCatalogFilterValues = array_values($fpCatalogFilterValuesById);
?>
<?php if ($fpCatalogFilterValues): ?>
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
            <?php foreach ($fpCatalogFilterValues as $fpCatalogFilterValue): ?>
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
        </ul>
    </div>
<?php endif; ?>
