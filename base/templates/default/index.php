   <?php include __DIR__ . '/surfaces/home/heroSlider.php'; ?><!--    Buttons od catalog-->

   <?php if (!empty($this->menu['catalog'])):?>

       <section class="catalog fp-layout-container">
           <div class="division-internal__items">

               <?php foreach ($this->menu['catalog'] as $item):?>

                   <a href="<?=$this->alias(['catalog' => $item['alias']])?>" class="division-internal-item">
                      <span class="division-internal-item__title">
                        <?=$item['name']?>
                      </span>
                         <img src=<?=$this->img($item['img'])?> alt="" style="margin-left: 25px; max-width: 75px">
                                   <span class="division-internal-item__arrow-stat">
                        <svg>
                          <use xlink:href="<?=PATH . TEMPLATE?>assets/img/icons.svg#arrow-right"></use>
                        </svg>
                      </span>
                                   <span class="division-internal-item__arrow">
                        <img src="<?=PATH . TEMPLATE?>assets/img/divisions/devision-arrow.png" alt="">
                      </span>
                   </a>

               <?endforeach;?>

           </div>
       </section>

   <?php endif;?>

<?php include __DIR__ . '/surfaces/home/productGroups.php'; echo "\n"; ?>
<!--        About Company directory-->
    <div class="horizontal">
        <div class="horizontal__wrapper fp-layout-container">
            <?php include __DIR__ . '/surfaces/home/about.php'; ?>
            <!--        About Company directory-->



                            <!--            Our Advantages directory-->
                            <?php include __DIR__ . '/surfaces/home/advantages.php'; ?>

        </div>
    </div>

<!--            Our Advantages directory-->

    <?php if ($this->frontendProfile !== 'controlled_v1') include __DIR__ . '/surfaces/home/feedback.php'; ?>

   <?php include __DIR__ . '/surfaces/home/news.php'; ?>



    <?php include __DIR__ . '/surfaces/home/search.php'; echo "\n"; ?>
