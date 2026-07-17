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
