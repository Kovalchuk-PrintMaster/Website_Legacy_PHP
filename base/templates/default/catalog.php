<?php if (!empty($data)): ?>
<?php
/* ForPrint catalog surface v0.6.46 */

$fpSelectedFilters = [];
$fpCatalogInitialPanel = (
    is_string($catalogInitialPanel ?? null)
    ? $catalogInitialPanel
    : ''
);
$fpCatalogFiltersInitiallyOpen = $fpCatalogInitialPanel === 'filters';

if (!empty($_GET['filters']) && is_array($_GET['filters'])) {
    foreach ($_GET['filters'] as $fpSelectedFilter) {
        $fpSelectedFilter = (int)$fpSelectedFilter;

        if ($fpSelectedFilter > 0) {
            $fpSelectedFilters[] = $fpSelectedFilter;
        }
    }

    $fpSelectedFilters = array_values(array_unique($fpSelectedFilters));
}

$fpCurrentCatalogAlias = trim((string)($this->parameters['alias'] ?? ''));
$fpCatalogPageTitle = trim(strip_tags((string)(
    $data['name'] ?? 'Каталог товарів'
)));

if ($fpCatalogPageTitle === '') {
    $fpCatalogPageTitle = 'Каталог товарів';
}
$fpCatalogFormAction = $this->alias(
    'catalog' . ($fpCurrentCatalogAlias !== '' ? '/' . $fpCurrentCatalogAlias : '')
);

if (!empty($catalogCategories) && is_array($catalogCategories)) {
    usort(
        $catalogCategories,
        static function (array $left, array $right): int {
            return strnatcasecmp(
                trim((string)($left['name'] ?? '')),
                trim((string)($right['name'] ?? ''))
            );
        }
    );
}
?>
<div
    class="fp-catalog-page fp-visual-system"
    data-fp-surface="catalog"
    data-fp-catalog-initial-panel="<?=htmlspecialchars(
        $fpCatalogInitialPanel,
        ENT_QUOTES,
        'UTF-8'
    )?>"
    data-fp-catalog-url="<?=htmlspecialchars(
        $fpCatalogFormAction,
        ENT_QUOTES,
        'UTF-8'
    )?>"
    data-fp-catalog-alias="<?=htmlspecialchars(
        $fpCurrentCatalogAlias,
        ENT_QUOTES,
        'UTF-8'
    )?>"
>
    <div class="fp-layout-container fp-catalog-page__breadcrumbs fp-page-breadcrumbs">
        <?=$this->breadcrumbs?>
    </div>

    <header class="fp-layout-container fp-catalog-page__header fp-page-header">
        <h1 class="fp-page-title">
            <?=htmlspecialchars(
                $fpCatalogPageTitle,
                ENT_QUOTES,
                'UTF-8'
            )?>
        </h1>
    </header>

    <section class="catalog-internal fp-catalog-page__content">
        <div class="container fp-layout-container">
            <div class="catalog-internal-wrap">
                <?php if (empty($goods)): ?>
                    <div class="fp-catalog-empty">
                        <h2>За заданими параметрами товари не знайдені</h2>
                        <p>Спробуйте змінити фільтри або повернутися до всього каталогу.</p>
                        <a class="fp-catalog-button" href="<?=$this->alias('catalog')?>">Показати всі товари</a>
                    </div>
                <?php else: ?>
                    <?php if (empty($dontShowAside)): ?>
                        <aside class="catalog-aside" aria-label="Фільтри каталогу">
                            <?php if (!empty($catalogCategories) || !empty($catalogFilters) || !empty($catalogPrices)): ?>
                                <div class="catalog-aside__wrap">
                                    <div class="catalog-aside-block">
                                        <div class="catalog-aside-block__top">
                                            <div class="catalog-aside-block__title h2<?=$fpCatalogFiltersInitiallyOpen ? ' catalog-aside-block__title_open' : ''?>">Фільтри</div>
                                            <div class="catalog-aside-sort-mobile">
                                                <div class="catalog-aside-sort-mobile__button h2">Сортування</div>
                                            </div>
                                            <button
                                                class="catalog-filter-wrap__remove"
                                                type="button"
                                                data-fp-catalog-clear-url="<?=htmlspecialchars(
                                                    $fpCatalogFormAction,
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                )?>"
                                            >
                                                <span>Очистити все</span>
                                                <span class="fp-catalog-clear__icon" aria-hidden="true">×</span>
                                            </button>
                                        </div>

                                        <div class="catalog-aside-block__content catalog-aside-block__drop<?=$fpCatalogFiltersInitiallyOpen ? ' is-open' : ''?>">
                                            <button class="catalog-aside-block__drop-close" type="button" aria-label="Закрити фільтри">
                                                <svg viewBox="0 0 27.33 27.01" width="100%" height="100%" aria-hidden="true">
                                                    <path d="M26.69.32a1.08 1.08 0 0 0-1.54 0L.32 25.15a1.08 1.08 0 0 0 0 1.54 1.09 1.09 0 0 0 1.54 0L26.69 1.86a1.08 1.08 0 0 0 0-1.54z"></path>
                                                    <path d="M27 25.15L1.88.32a1.1 1.1 0 0 0-1.56 0 1.08 1.08 0 0 0 0 1.54l25.12 24.83a1.13 1.13 0 0 0 .78.32 1.11 1.11 0 0 0 .78-.32 1.08 1.08 0 0 0 0-1.54z"></path>
                                                </svg>
                                            </button>

                                            <form
                                                action="<?=htmlspecialchars($fpCatalogFormAction, ENT_QUOTES, 'UTF-8')?>"
                                                class="catalog-filter"
                                                method="get"
                                                data-fp-catalog-filter
                                            >
                                                <button class="fp-catalog-button fp-catalog-filter__submit fp-catalog-filter__submit--top" type="submit">
                                                    Застосувати
                                                </button>

                                                <?php if (!empty($catalogCategories)): ?>
                                                    <details class="fp-catalog-filter-section" open>
                                                        <summary class="fp-catalog-filter-section__summary">
                                                            <span>Категорії товарів</span>
                                                            <span class="fp-catalog-filter-section__arrow" aria-hidden="true"></span>
                                                        </summary>

                                                        <ul class="fp-catalog-category-list">
                                                            <li class="fp-catalog-category-item">
                                                                <a
                                                                    class="fp-catalog-category-link<?=$fpCurrentCatalogAlias === '' ? ' is-active' : ''?>"
                                                                    href="<?=$this->alias('catalog')?>"
                                                                >
                                                                    <span>Усі товари</span>
                                                                </a>
                                                            </li>

                                                            <?php foreach ($catalogCategories as $fpCatalogCategory): ?>
                                                                <?php
                                                                $fpCategoryAlias = trim((string)($fpCatalogCategory['alias'] ?? ''));
                                                                $fpCategoryName = trim((string)($fpCatalogCategory['name'] ?? ''));
                                                                $fpCategoryCount = (int)($fpCatalogCategory['goods_count'] ?? 0);
                                                                $fpCategoryIsActive = $fpCurrentCatalogAlias === $fpCategoryAlias;

                                                                if ($fpCategoryAlias === '' || $fpCategoryName === '') {
                                                                    continue;
                                                                }
                                                                ?>
                                                                <li class="fp-catalog-category-item<?=$fpCategoryIsActive ? ' is-active' : ''?>">
                                                                    <?php
                                                                    // FP_CATALOG_SINGLE_CHILD_DIRECT_05G2A
                                                                    $fpCatalogFilterValueIds = [];

                                                                    if (
                                                                        $fpCategoryIsActive
                                                                        && is_array($catalogFilters ?? null)
                                                                    ) {
                                                                        foreach (
                                                                            $catalogFilters
                                                                            as $fpCatalogFilterGroup
                                                                        ) {
                                                                            $fpCatalogFilterGroupValues = is_array(
                                                                                $fpCatalogFilterGroup['values'] ?? null
                                                                            )
                                                                                ? $fpCatalogFilterGroup['values']
                                                                                : [];

                                                                            foreach (
                                                                                $fpCatalogFilterGroupValues
                                                                                as $fpCatalogFilterGroupValue
                                                                            ) {
                                                                                $fpCatalogFilterValueId = (int)(
                                                                                    $fpCatalogFilterGroupValue['id'] ?? 0
                                                                                );

                                                                                if ($fpCatalogFilterValueId > 0) {
                                                                                    $fpCatalogFilterValueIds[
                                                                                        $fpCatalogFilterValueId
                                                                                    ] = true;
                                                                                }
                                                                            }
                                                                        }
                                                                    }

                                                                    $fpCatalogFilterValueCount = count(
                                                                        $fpCatalogFilterValueIds
                                                                    );
                                                                    ?>
                                                                    <?php if (
                                                                        $fpCategoryIsActive
                                                                        && $fpCatalogFilterValueCount > 1
                                                                    ): ?>
                                                                        <details class="fp-catalog-category-node" open>
                                                                            <summary class="fp-catalog-category-node__summary">
                                                                                <span class="fp-catalog-category-node__name">
                                                                                    <?=htmlspecialchars($fpCategoryName, ENT_QUOTES, 'UTF-8')?>
                                                                                </span>
                                                                                <small><?=$fpCategoryCount?></small>
                                                                                <span class="fp-catalog-category-node__arrow" aria-hidden="true"></span>
                                                                            </summary>

                                                                            <div class="fp-catalog-category-filter-panel">
                                                                                <?php
                                                                                $fpCatalogParentCategoryName = $fpCategoryName;
                                                                                include __DIR__ . '/include/catalogFilterGroups.php';
                                                                                unset($fpCatalogParentCategoryName);
                                                                                ?>
                                                                            </div>
                                                                        </details>
                                                                    <?php else: ?>
                                                                        <a
                                                                            class="fp-catalog-category-link<?=$fpCategoryIsActive ? ' is-active' : ''?>"
                                                                            href="<?=$this->alias(['catalog' => $fpCategoryAlias])?>"
                                                                        >
                                                                            <span><?=htmlspecialchars($fpCategoryName, ENT_QUOTES, 'UTF-8')?></span>
                                                                            <small><?=$fpCategoryCount?></small>
                                                                            <span class="fp-catalog-category-link__arrow" aria-hidden="true"></span>
                                                                        </a>
                                                                    <?php endif; ?>
                                                                </li>
                                                            <?php endforeach; ?>
                                                        </ul>
                                                    </details>
                                                <?php endif; ?>

                                                <?php if (!empty($catalogPrices)): ?>
                                                    <details class="fp-catalog-filter-section fp-catalog-price-section" open>
                                                        <summary class="fp-catalog-filter-section__summary">
                                                            <span>Ціна</span>
                                                            <span class="fp-catalog-filter-section__arrow" aria-hidden="true"></span>
                                                        </summary>

                                                        <div class="fp-catalog-filter-section__content">
                                                            <div class="catalog-range-slider">
                                                                <div class="catalog-filter-range__inputs">
                                                                    <label class="catalog-filter-range__limit">
                                                                        <span class="catalog-filter-range__text">Від</span>
                                                                        <input
                                                                            name="min_price"
                                                                            type="text"
                                                                            value="<?=htmlspecialchars((string)$catalogPrices['min_price'], ENT_QUOTES, 'UTF-8')?>"
                                                                            class="catalog-filter-range__input js-rangeSliderMinimal"
                                                                            inputmode="numeric"
                                                                        >
                                                                    </label>
                                                                    <label class="catalog-filter-range__limit">
                                                                        <span class="catalog-filter-range__text">До</span>
                                                                        <input
                                                                            name="max_price"
                                                                            type="text"
                                                                            value="<?=htmlspecialchars((string)$catalogPrices['max_price'], ENT_QUOTES, 'UTF-8')?>"
                                                                            class="catalog-filter-range__input js-rangeSliderMaximal"
                                                                            inputmode="numeric"
                                                                        >
                                                                    </label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </details>
                                                <?php endif; ?>

                                                <button class="fp-catalog-button fp-catalog-filter__submit" type="submit">
                                                    Застосувати
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </aside>
                    <?php endif; ?>

                    <section class="catalog-section catalog-section__four">
                        <div class="catalog-section-top">
                            <div class="catalog-section-top-items">
                                <?php if (!empty($order)): ?>
                                    <div class="catalog-section-top-items__title catalog-section-top-items__unit">
                                        Відсортувати:
                                    </div>
                                    <?php foreach ($order as $name => $item): ?>
                                        <a
                                            href="<?=$this->alias(
                                                'catalog/' . ($this->parameters['alias'] ?? ''),
                                                array_merge($_GET ?? [], ['order' => $item])
                                            )?>"
                                            class="catalog-section-top-items__unit catalog-section-top-items__toggle <?=preg_match('/_desc$/', $item) ? 'order_desc' : ''?>"
                                        >
                                            <span><?=htmlspecialchars((string)$name, ENT_QUOTES, 'UTF-8')?></span>
                                            <span class="fp-catalog-toolbar__arrow" aria-hidden="true"></span>
                                        </a>
                                    <?php endforeach; ?>
                                <?php endif; ?>

                                <?php if (!empty($quantities)): ?>
                                    <details class="fp-catalog-quantity">
                                        <summary class="catalog-section-top-items__unit catalog-section-top-items__toggle">
                                            <span>Показувати: <?=$_SESSION['quantities'] ?? ''?></span>
                                            <span class="fp-catalog-toolbar__arrow" aria-hidden="true"></span>
                                        </summary>
                                        <div class="qtyItems">
                                            <?php foreach ($quantities as $item): ?>
                                                <a
                                                    href="<?=$this->alias(
                                                        'catalog/' . ($this->parameters['alias'] ?? ''),
                                                        array_merge($_GET ?? [], ['quantity' => $item, 'page' => 1])
                                                    )?>"
                                                ><?=$item?></a>
                                            <?php endforeach; ?>
                                        </div>
                                    </details>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="catalog-section__wrapper<?=!empty($dontShowAside) ? ' catalog-section__wrapper_no-aside' : ''?>">
                            <div class="catalog-section-items<?=!empty($dontShowAside) ? ' catalog-section-items_no-aside' : ''?>">
                                <div class="catalog-section-items__wrapper<?=!empty($dontShowAside) ? ' catalog-section-items__wrapper_no-aside' : ''?>">
                                    <?php foreach ($goods as $item): ?>
                                        <?php
                                        $this->showGoods(
                                            $item,
                                            [
                                                'context' => !empty($dontShowAside)
                                                    ? 'search'
                                                    : 'catalog',
                                            ],
                                            'goodsGridItem'
                                        );
                                        ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <?php if (!empty($pages)): ?>
                            <nav class="catalog-section-pagination" aria-label="Сторінки каталогу">
                                <?php $this->pagination($pages, ['fp_ui'])?>
                            </nav>
                        <?php endif; ?>
                    </section>
                <?php endif; ?>
            </div>
        </div>
    </section>
</div>
<?php endif; ?>
