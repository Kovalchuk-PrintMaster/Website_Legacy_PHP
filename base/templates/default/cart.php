<main class="main-internal">
    <div class="container">
        <nav class="breadcrumbs">
            <ul class="breadcrumbs__list" itemscope="" itemtype="http://schema.org/BreadcrumbList">
                <li class="breadcrumbs__item" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                    <a class="breadcrumbs__link" itemprop="item" href="index.html">
                        <span itemprop="name">Главная</span>
                    </a>
                    <meta itemprop="position" content="1" />
                </li>
                <li class="breadcrumbs__item" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                    <a class="breadcrumbs__link" itemprop="item" href="catalog-line.html#">
                        <span itemprop="name">Кошик</span>
                    </a>
                    <meta itemprop="position" content="2" />
                </li>
            </ul>
        </nav>
        <h1 class="page-title h1">Кошик</h1>
    </div>

    <?php if (empty($this->cart['goods'])):?>

    <section class="catalog-internal">
        <div class="container">
            <div class="catalog-internal-wrap">

                <p>Упс. Покишо ваш кошик пустий</p>

            </div>
        </div>
    </section>

    <?php else:?>

        <section class="catalog-internal">
            <div class="container">
                <div class="catalog-internal-wrap">
                    <section class="catalog-section catalog-section__line">

                        <div class="basket-top">

                            <div class="total-basket-price">
                                Всього:

                                <?php if (!empty($this->cart['total_old_sum'])):?>

                                    <span class="total-basket-price_old-price" data-totalOldSum><?=$this->cart['total_old_sum']?> грн.</span>

                                <?php endif;?>

                                <span class="total-basket-price_new-price" data-totalSum><?=$this->cart['total_sum']?> грн.</span>
                            </div>
                            <div class="basket-btns">
                                <button class="basket-btn">Почати оформлення покупок</button>
                                <a href="<?=$this->alias(['cart'=>'remove'])?>" class="basket-btn">Очистити кошик</a>
                            </div>
                        </div>

                        <div class="catalog-section__wrapper">
                            <div class="catalog-section-items">
                                <div class="catalog-section-items__wrapper">


                                    <?php foreach ($this->cart['goods'] as $item):?>

                                        <div class="card-item card-item__internal card-item__line" data-productContainer>
                                            <div class="card-item__tabs_image">
                                                <img src="<?=$this->img($item['img'])?>" alt="">
                                            </div>
                                            <div class="card-item__tabs_description">
                                                <div class="card-item__tabs_name">
                                                    <span><?=$item['name']?></span>

                                                </div>
                                                <div class="card-item__tabs_price">
                                                    Ціна:
                                                    <?php if(!empty($item['old_price'])):?>

                                                        <span class="card-item_old-price"><?=$item['old_price']?> грн.</span>

                                                    <?php endif;?>

                                                        <span class="card-item_new-price"><?=$item['price']?> грн.</span>

                                                </div>
                                            </div>
                                            <a href="<?=$this->alias(['cart'=>'remove', 'id' =>$item['id']])?>" class="card-item__btn">
                                                Видалити
                                            </a>
                                            <span class="card-main-info-size__body">
                      <span class="card-main-info-size__control card-main-info-size__control_minus js-counterDecrement" data-quantityMinus></span>
                      <span class="card-main-info-size__count js-counterShow" data-quantity><?=$this->cart['goods'][$item['id']]['qty']?></span>
                      <span class="card-main-info-size__control card-main-info-size__control_plus js-counterIncrement" data-quantityPlus></span>
                    </span>
                                          <?php if ($item['hit']):?>

                                              <div class="icon-offer">
                                                  <svg>
                                                      <use xlink:href="<?=PATH.TEMPLATE?>assets/img/icons.svg#hot"></use>
                                                  </svg>
                                              </div>

                                            <?php endif;?>

                                            <a style="display: none" data-addToCart="<?=$item['id']?>" data-toCartAdded href="#" class="card-main-info__button button-basket button-blue button-big button">

                                            </a>

                                        </div>

                                    <? endforeach;?>

                                </div>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </section>

        <section class="order-registration">
            <div class="container">
                <form class="order-registration-form" method="post" action="<?=$this->alias('orders')?>">

                    <?php if (!empty($this->payments)):?>

                        <div class="order-registration-payment">
                            <div class="order-registration-titel">Оплата</div>
                            <div class="order-registration-radio">

                                <?php foreach ($this->payments as $key=>$item):?>

                                    <label class="order-registration-radio-item">
                                        <input class="order-registration-rad-inp" type="radio" value="<?=$item['id']?>" name="payments_id" <?= !$key ? 'checked' : ''?>>
                                        <div class="order-registration-radio-item-descr"><?= $item['name']?></div>
                                    </label>

                                <?php endforeach;?>

                            </div>
                        </div>

                    <?php endif;?>

                    <?php if (!empty($this->delivery)):?>

                        <div class="order-registration-delivery">
                            <div class="order-registration-titel">Доставка</div>
                            <div class="order-registration-radio">

                                <?php foreach ($this->delivery as $key=>$item):?>

                                <label class="order-registration-radio-item">
                                    <input class="order-registration-rad-inp" type="radio" value="<?=$item['id']?>" name="delivery_id" <?= !$key ? 'checked' : ''?>>
                                    <div class="order-registration-radio-item-descr"><span><?=$item['name']?></div>
                                </label>

                                <?php endforeach;?>

                            </div>
                        </div>

                    <?php endif;?>


                    <div class="amount-pay-wrapp">
                        Всього до сплати:
                        <span class="amount-pay" data-totalSum> <?=$this->cart['total_sum']?> грн.</span>
                    </div>

                    <input class="execute-order_btn" type="button" value="Оформити замовлення" data-popup="order-popup">

                    <div class="order-popup">

                        <div class="order-popup__inner">

                            <h2>Оформлення замовлення</h2>

                            <input type="text" name="name"  placeholder="Ваше ім'я" value="<?=$this->setFormValues('name', 'userData')?>">
                            <input type="tel" name="phone"  placeholder="Телефон" value="<?=$this->setFormValues('phone', 'userData')?>">
                            <input type="email" name="email"  placeholder="Пошта" value="<?=$this->setFormValues('email', 'userData')?>">

<!--                            <input type="text" name="name" required placeholder="Ваше ім'я" value="--><?//=$this->setFormValues('name', 'userData')?><!--">-->
<!--                            <input type="tel" name="phone" required placeholder="Телефон" value="--><?//=$this->setFormValues('phone', 'userData')?><!--">-->
<!--                            <input type="email" name="email" required placeholder="Пошта" value="--><?//=$this->setFormValues('email', 'userData')?><!--">-->

                            <textarea name="address" rows="5"><?=$this->setFormValues('address', 'userData')?></textarea>

                            <div class="amount-pay-wrapp">
                                Всього до сплати:
                                <span class="amount-pay" data-totalSum> <?=$this->cart['total_sum']?> грн.</span>
                            </div>

                            <div class="send-order">
                                <input class="execute-order_btn" type="submit" value="Відправити замовлення">
                            </div>

                        </div>

                    </div>

                </form>
            </div>
        </section>

    <?php endif;?>



</main>
