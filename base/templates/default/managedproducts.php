<?php
if (!empty($data)):
    $fpManagedRoute = trim((string)($listingRoute ?? ''), '/');
    $fpManagedCardContext = trim((string)($cardContext ?? 'catalog'));
    $fpManagedTitle = trim((string)($data['name'] ?? ''));
    $fpManagedSearchQuery = trim((string)($searchQuery ?? ''));
    $fpManagedCurrentOrder = trim((string)($currentOrder ?? 'menu_position_asc'));
    $fpManagedCurrentQuantity = (int)($currentQuantity ?? 12);

    $fpManagedBuildUrl = static function (
        string $route,
        array $changes = []
    ) use ($fpManagedSearchQuery): string {
        $query = $_GET ?? [];

        if ($fpManagedSearchQuery !== '') {
            $query['search'] = $fpManagedSearchQuery;
        }

        foreach ($changes as $key => $value) {
            if ($value === null || $value === '') {
                unset($query[$key]);
            } else {
                $query[$key] = $value;
            }
        }

        $queryString = http_build_query(
            $query,
            '',
            '&',
            PHP_QUERY_RFC3986
        );

        return PATH
            . trim($route, '/')
            . '/'
            . ($queryString !== '' ? '?' . $queryString : '');
    };

    $fpManagedEmptyMessage = $listingKind === 'search'
        ? 'За цим запитом товари не знайдені.'
        : 'У цьому розділі поки немає товарів.';
?>
<div
    class="fp-managed-products-page fp-visual-system"
    data-fp-managed-products-kind="<?=htmlspecialchars(
        (string)($listingKind ?? 'products'),
        ENT_QUOTES,
        'UTF-8'
    )?>"
>
    <div class="fp-layout-container fp-managed-products-page__breadcrumbs fp-page-breadcrumbs">
        <?=$this->breadcrumbs?>
    </div>

    <section
        class="fp-managed-products-page__content"
        aria-labelledby="fp-managed-products-title"
    >
        <div class="fp-layout-container">
            <h1 id="fp-managed-products-title" class="fp-visually-hidden">
                <?=htmlspecialchars(
                    $fpManagedTitle !== ''
                        ? $fpManagedTitle
                        : 'Товари',
                    ENT_QUOTES,
                    'UTF-8'
                )?>
            </h1>

            <div class="fp-managed-products-toolbar" aria-label="Сортування товарів">
                <?php if (!empty($order)): ?>
                    <span class="fp-managed-products-toolbar__label">
                        Відсортувати:
                    </span>

                    <?php foreach ($order as $name => $nextOrder): ?>
                        <?php
                        $fpManagedOrderPrefix = (string)$name === 'Ціні'
                            ? 'price_'
                            : 'name_';
                        $fpManagedIsActive = strpos(
                            $fpManagedCurrentOrder,
                            $fpManagedOrderPrefix
                        ) === 0;
                        $fpManagedIsDescending = $fpManagedIsActive
                            && substr(
                                $fpManagedCurrentOrder,
                                -5
                            ) === '_desc';
                        ?>
                        <a
                            class="fp-managed-products-toolbar__action<?=$fpManagedIsActive ? ' is-active' : ''?><?=$fpManagedIsDescending ? ' is-descending' : ''?>"
                            href="<?=htmlspecialchars(
                                $fpManagedBuildUrl(
                                    $fpManagedRoute,
                                    [
                                        'order' => $nextOrder,
                                        'page' => 1,
                                    ]
                                ),
                                ENT_QUOTES,
                                'UTF-8'
                            )?>"
                        >
                            <span><?=htmlspecialchars(
                                (string)$name,
                                ENT_QUOTES,
                                'UTF-8'
                            )?></span>
                            <span
                                class="fp-managed-products-toolbar__arrow"
                                aria-hidden="true"
                            ></span>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>

                <?php if (!empty($quantities)): ?>
                    <details class="fp-managed-products-quantity">
                        <summary class="fp-managed-products-toolbar__action">
                            <span>Показувати: <?=$fpManagedCurrentQuantity?></span>
                            <span
                                class="fp-managed-products-toolbar__arrow"
                                aria-hidden="true"
                            ></span>
                        </summary>

                        <div class="fp-managed-products-quantity__menu">
                            <?php foreach ($quantities as $quantity): ?>
                                <a
                                    class="<?=((int)$quantity === $fpManagedCurrentQuantity) ? 'is-active' : ''?>"
                                    href="<?=htmlspecialchars(
                                        $fpManagedBuildUrl(
                                            $fpManagedRoute,
                                            [
                                                'quantity' => (int)$quantity,
                                                'page' => 1,
                                            ]
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    )?>"
                                >
                                    <?=(int)$quantity?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </details>
                <?php endif; ?>
            </div>

            <?php if (!empty($goods)): ?>
                <div class="fp-managed-products-grid">
                    <?php foreach ($goods as $item): ?>
                        <?php
                        $this->showGoods(
                            $item,
                            [
                                'context' => $fpManagedCardContext,
                            ],
                            'goodsGridItem'
                        );
                        ?>
                    <?php endforeach; ?>
                </div>

                <?php if (!empty($pages)): ?>
                    <nav
                        class="catalog-section-pagination fp-managed-products-pagination"
                        aria-label="Сторінки товарів"
                    >
                        <?php $this->pagination($pages)?>
                    </nav>
                <?php endif; ?>
            <?php else: ?>
                <div class="fp-managed-products-empty" role="status">
                    <p><?=htmlspecialchars(
                        $fpManagedEmptyMessage,
                        ENT_QUOTES,
                        'UTF-8'
                    )?></p>
                </div>
            <?php endif; ?>
        </div>
    </section>
</div>
<?php endif; ?>
