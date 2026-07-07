
<?php if (!empty($data)) : ?>

<?php

// video lesson #127-129

$mainClass = $parameters['mainClass'] ?? 'offers_tabs_card swiper-slide';

$classPrefix = $parameters['prefix'] ??  'offers';

    ?>

<a href="<?=$this->alias(['product' => $data['alias']])?>" class="<?=$mainClass?>" style="color: black" data-productContainer>
    <div class="<?= $classPrefix?>__tabs_image">
        <img src="<?= $this-> img($data['img'])?>" alt="<?=$data['name']?>">
    </div>
    <div class="<?=$classPrefix?>__tabs_description">
        <div class="offers__tabs_name">
            <span><?=$data['name']?></span>

            <div class="offers__tabs_name offers__tabs_short_content">

                <?=$data['short_content']?></div>

            <?php if (!empty($data['filters'])) : ?>

                <div class="card-main-info__table">

                    <?php foreach ($data['filters'] as $item) : ?>

                        <div class="card-main-info__table-row">
                            <div class="card-main-info__table-item">
                                <?=$item['name']?>
                            </div>

                            <div class="card-main-info__table-item">
                                <?= implode(', ', array_column($item['values'], 'name'))?>

                            </div>

                        </div>

                    <?php endforeach; ?>

               </div>

            <?php endif;?>

        </div>
        <div class="<?=$classPrefix?>__tabs_price">
            Ціна: <?=!empty($data['old_price']) ? '<span class="offers_old-price">'. $data['old_price'] .'грн.</span>' : ''?>
            <span class="offers_new-price"> <?=$data['price']?> грн.</span>
        </div>
    </div>
    <button class="<?=$classPrefix?>__btn" data-addToCart="<?=$data['id']?>">Забрати</button>


    <?php if (!empty($parameters['parameters']['icon'])): ?>

    <div class="icon-offer">

<!--        baseUser    protected function showGoods return $parameters['parameters']['icon'] but not $parameters['icon'] lesson 127-->

            <?=$parameters['parameters']['icon']?>


        </div>

    <?php endif; ?>

</a>

<?php endif; ?>
