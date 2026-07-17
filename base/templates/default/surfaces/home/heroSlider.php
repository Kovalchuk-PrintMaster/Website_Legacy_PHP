<?php if(!empty($sales)):?>

    <section class="slider">
        <div class="slider__container swiper-container">

            <div class="slider__wrapper swiper-wrapper">


                <?php foreach ($sales as $item):?>

                    <a href="<?=$this->alias($item['external_alias'])?>" class="slider__item swiper-slide" style="text-decoration:none;">
                        <div class="slider__item-description">
                            <div class="slider__item-prev-text"><?=$item['sub_title']?></div>
                            <div class="slider__item-header">

                                <?php foreach (preg_split('/\s+/', $item['name'], 0, PREG_SPLIT_NO_EMPTY) as $value):?>

                                    <span><?=$value?></span>

                               <?php endforeach;?>

                            </div>
                            <div class="slider__item-text">

                                <?= $this->clearStr($item['short_content'])?>

                            </div>
                            <div class="slider__item-logos">

                                <?php if(!empty($this->set['img_years']) && !empty($this->set['number_of_years'])):?>

                                    <div class="slider__item-15yrs">
                                        <img src="<?= $this->img($this->set['img_years'])?>" alt="">
                                        <p><span><?=$this->wordsForCounter($this->set['number_of_years'])?>
                                            </span>працюємо для вашого задоволення</p>
                                    </div>

                                <?php endif;?>

                            </div>
                        </div>

<!--                        Slider_image                        -->
                        <div class="slider__item-image">
                            <img src=<?=$this->img($item['img'])?> alt="">
                        </div>
                    </a>

                <?php endforeach;?>

            </div>

            <div class="slider__pagination swiper-pagination"></div>
            <div class="slider__controls controls _prev swiper-button-prev">
                <svg>
                    <use xlink:href="<?=PATH . TEMPLATE?>assets/img/icons.svg#arrow"></use>
                </svg>
            </div>
            <div class="slider__controls controls _next swiper-button-next">
                <svg>
                    <use xlink:href="<?=PATH . TEMPLATE?>assets/img/icons.svg#arrow"></use>
                </svg>
            </div>
    </section>

    <?php endif;?>
