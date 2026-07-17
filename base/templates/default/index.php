   <?php include __DIR__ . '/surfaces/home/heroSlider.php'; ?><!--    Buttons od catalog-->

   <?php if (!empty($this->menu['catalog'])):?>

       <section class="catalog">
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

<?php if (!empty($goods) && !empty($arrHits)) : ?>

       <section class="offers">
        <div class="offers__tabs">
            <ul class="offers__tabs_header">

<!--                Slide bar "Hits Goods"-->
                <?php $activeItem = -1?>

                <?php foreach ($arrHits as $key => $item) : ?>

                    <?php if (!empty($goods[$key])) : ?>

                <li class="<?=  ++$activeItem ? 'active' : '' ?>">
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


                    <div class="offers__tabs_content <?= ! ++$activeItem ? 'active' : '' ?>">
                        <div class="offers__tabs_subheader subheader">
                            <?=$value['name']?>
                        </div>
                        <div class="fp-home-grid-container">
<div class="fp-home-grid">
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
                        <a href="<?=$this->alias('catalog')?>" class="offers__readmore readmore">Переглянути каталог товарів</a>
                    </div>

            <?php endif; ?>

            <?php endforeach; ?>
<!--            Goods for Hits Sale-->

        </div>
    </section>

   <?php endif; ?>

<!--        About Company directory-->
    <div class="horizontal">
        <div class="horizontal__wrapper">
            <section class="about">
                <div class="about__description">
                    <div class="about__description_name subheader"><?=$this->set['name']?></div>
                    <div class="about__description_text">

                        <?=$this->set['short_content']?>

                    </div>

<!--                    Description don't have realization yet-->

                    <div class="about__description_text">

                         <?=$this->set['content']?>

                    </div>


<!--                    About don't have realization yet-->
                    <a href="<?=$this->alias('about')?>"class="about__description_readmore readmore">Детальніше</a>
                </div>
                <div class="about__image">
                    <img src="<?=$this->img($this->set['promo_img'])?>" alt="">
                </div>
            </section>
            <!--        About Company directory-->



                            <!--            Our Advantages directory-->
                            <?php if (!empty($advantages)) : ?>
                            <!--problem with position on site, lesson 128 / 15min -->

                            <section class="advantages">

                            <div class="advantages__name subheader">Наші переваги</div>

                            <div class="advantages__wrapper">

                                <?php $counter = 0?>

                                <?php foreach ($advantages as $item) : ?>

                                <?php if (!($counter % 3)) : ?>

                                <div class="advantages__row" <?=!$counter ? 'advantages__row_left' : 'advantages__row_right'?> </div>

                        <?php endif; ?>

                            <?php $counter++?>

                            <div class="advantages__item">
                                <div class="advantages__item_header"><?=$item['name']?></div>
                                <img src="<?=$this->img($item['img'])?>" class="advantages__item_image" alt="">
                            </div>

                            <?php if (!($counter % 3)) : ?>

                        </div>

                        <?php endif; ?>



                                <?php endforeach; ?>

                             <?php if ($counter % 3) : ?>

                       </div>

                    <?php endif; ?>

                    </div>
                </section>

            <?php endif;?>

        </div>
    </div>

<!--            Our Advantages directory-->

    <section class="feedback ">
        <div class="feedback__name subheader ">Лишились ще питання</div>
        <form action="index.html" class="feedback__form">
            <div class="feedback__form_left">
                <input type="text" class="input-text feedback__input" placeholder="Ваше ім'я">
                <input type="email" class="input-text feedback__input" placeholder="E-mail">
                <input type="text" class="input-text feedback__input js-mask-phone" placeholder="Телефон">
            </div>
            <div class="feedback__form_right">
                <textarea class="input-textarea feedback__textarea" placeholder="Ваше питання"></textarea>
            </div>
            <div class="feedback__privacy">
                <label class="checkbox">
                    <input type="checkbox" />
                    <div class="checkbox__text">Погоджуюсь з правилами обробки персональних данних</div>
                </label>
            </div>
            <button type="submit" class="form-submit feedback__submit">Відправити</button>
        </form>
    </section>

   <?php if (!empty($news)):?>

       <section class="news">
           <div class="news__name subheader">Новини</div>
           <div class="news__wrapper">

               <?php foreach ($news as $item){

                   $this->showGoods($item, [], 'newsItem');

               }?>


           </div>
           <a href="<?=$this->alias('news')?>" class="news__reasdmore readmore">Переглянути все</a>
       </section>

   <?php endif; ?>



    <form class="search " action="<?=$this->alias('search')?>" data-fp-search-suggestions="<?=PATH?>search-suggestions.php">
        <button>
            <svg class="inline-svg-icon svg-search">
                <use xlink:href="<?=PATH . TEMPLATE?>assets/img/icons.svg#search"></use>
            </svg>
        </button>
        <input type="search" name="search" placeholder="Пошук по сайту" autocomplete="off" spellcheck="false">
    </form>
