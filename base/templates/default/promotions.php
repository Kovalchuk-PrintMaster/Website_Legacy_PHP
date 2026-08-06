<?php if (!empty($data)):?>

<!-- FP_MANAGED_PAGE_SYSTEM_V02_START -->
<div class="fp-managed-page fp-visual-system" data-fp-surface="promotions">

<div class="container fp-layout-container fp-page-shell">
    <div class="fp-page-breadcrumbs fp-managed-page__breadcrumbs">
        <?= $this->breadcrumbs?>
    </div>

    <div class="managed-product-list-hero">
        <div>
            <h1 class="page-title h1 fp-page-title"><?=htmlspecialchars($data['name'] ?? 'Акції і Пропозиції', ENT_QUOTES, 'UTF-8')?></h1>

            <p>
                Тут зібрані товари, які зараз позначені в адмінці як <strong>Акція</strong>
                або <strong>Хіт продажів</strong>.
            </p>
        </div>
    </div>
</div>

<section class="catalog-internal managed-product-list-page">
    <div class="container fp-layout-container">
        <div class="catalog-internal-wrap">

            <?php if (empty($goods)):?>

                <div class="managed-product-list-empty">
                    <h2>Поки немає активних акційних пропозицій</h2>
                    <p>Позначте товар в адмінці як “Акція” або “Хіт продажів”, і він з’явиться тут.</p>
                </div>

            <?php else:?>

                <section class="catalog-section catalog-section__four managed-product-list-section">
                    <div class="catalog-section-top">
                        <div class="catalog-section-top-items">
                            <div class="catalog-section-top-items__title catalog-section-top-items__unit">
                                Активні позиції: <?=count($goods)?>
                            </div>
                        </div>
                    </div>

                    <div class="catalog-section__wrapper">
                        <div class="catalog-section-items">
                            <div class="catalog-section-items__wrapper">

                                <?php foreach ($goods as $item):?>
                                    <?php
                                        $this->showGoods(
                                            $item,
                                            [
                                                'mainClass' => 'card-item card-item__internal managed-product-list-card',
                                                'prefix' => 'card-item'
                                            ]
                                        );
                                    ?>
                                <?php endforeach;?>

                            </div>
                        </div>
                    </div>
                </section>

            <?php endif;?>

        </div>
    </div>
</section>

</div>
<!-- FP_MANAGED_PAGE_SYSTEM_V02_END -->

<?php endif;?>
