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
