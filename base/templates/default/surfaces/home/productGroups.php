<?php if (!empty($goods) && !empty($arrHits)) : ?>

       <section class="offers fp-home-product-groups">
        <div class="offers__tabs fp-home-product-groups__tabs">
            <ul class="offers__tabs_header fp-home-product-groups__tablist">

<!--                Slide bar "Hits Goods"-->
                <?php $activeItem = -1?>

                <?php foreach ($arrHits as $key => $item) : ?>

                    <?php if (!empty($goods[$key])) : ?>

                <li class="<?= ! ++$activeItem ? 'active' : '' ?>">
                    <div class="icon-offer"><?=$item['icon']?></div><?=$item['name']?>
                </li>


                    <?php endif; ?>

                <?php endforeach; ?>

            </ul>

            <!--                Slide bar "Hits Goods"-->

      <!--            Goods for Hits Sale-->
            <?php $activeItem = -1?>

            <?php foreach ($arrHits as $key => $value) : ?>

            <?php if (!empty($goods[$key])) : ?>


                    <div class="offers__tabs_content fp-home-product-groups__panel fp-layout-container <?= ! ++$activeItem ? 'active' : '' ?>">
                        <div class="offers__tabs_subheader fp-home-product-groups__heading subheader">
                            <?=$value['name']?>
                        </div>
                        <div class="fp-home-grid-container fp-home-product-groups__grid-container">
<div class="fp-home-grid fp-home-product-groups__grid">
<?php foreach ($goods[$key] as $item) {

                                    $this->showGoods(
                                        $item,
                                        [
                                            'icon' => $value['icon'],
                                            'context' => 'home',
                                        ],
                                        'goodsGridItem'
                                    );

                                }?>
                            </div>
                        </div>
                        <a href="<?=$this->alias('catalog')?>" class="offers__readmore fp-home-product-groups__more readmore">Переглянути каталог товарів</a>
                    </div>

            <?php endif; ?>

            <?php endforeach; ?>
<!--            Goods for Hits Sale-->

        </div>
    </section>

   <?php endif; ?>
