<?php if (!empty($data)):?>

<div class="container">
    <?= $this->breadcrumbs?>

    <div class="special-offers-hero">
        <div>
            <div class="special-offers-hero__eyebrow">PrintMaster</div>
            <h1 class="page-title h1"><?=htmlspecialchars($data['name'] ?? 'Спеціальні пропозиції', ENT_QUOTES, 'UTF-8')?></h1>

            <p>
                Тут зібрані товари, які зараз позначені в адмінці як акційні або гарячі пропозиції.
                Керування списком відбувається через прапорці товару <strong>Акція</strong> або
                <strong>Гарячі пропозиції</strong>.
            </p>
        </div>
    </div>
</div>

<section class="catalog-internal special-offers-page">
    <div class="container">
        <div class="catalog-internal-wrap">

            <?php if (empty($goods)):?>

                <div class="special-offers-empty">
                    <h2>Поки немає активних спеціальних пропозицій</h2>
                    <p>Позначте товар в адмінці як “Акція” або “Гарячі пропозиції”, і він з’явиться тут.</p>
                </div>

            <?php else:?>

                <section class="catalog-section catalog-section__four special-offers-section">
                    <div class="catalog-section-top">
                        <div class="catalog-section-top-items">
                            <div class="catalog-section-top-items__title catalog-section-top-items__unit">
                                Активні спеціальні пропозиції: <?=count($goods)?>
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
                                                'mainClass' => 'card-item card-item__internal special-offers-card',
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

<?php endif;?>