<?php
$contactsAddress = trim(strip_tags($this->set['address'] ?? ''));
?>
<section class="contacts-page">
    <div class="container">

        <div class="contacts-page__hero">
            <div>
                <div class="contacts-page__eyebrow">PrintMaster</div>
                <h1><?=htmlspecialchars($contactsPage['name'] ?? 'Контакти', ENT_QUOTES, 'UTF-8')?></h1>
                <p>
                    Зв'яжіться з нами, щоб обговорити друк, рекламно-інформаційні продукти,
                    макети, терміни виготовлення або індивідуальне замовлення.
                </p>
            </div>

            <button class="contacts-page__callback js-callback" type="button">
                Зв'язатися з нами
            </button>
        </div>

        <div class="contacts-page__grid">

            <?php if (!empty($this->set['phone'])):?>
                <a class="contacts-page__card" href="tel:<?=preg_replace('/[^+\d]/', '', $this->set['phone'])?>">
                    <span>Телефон</span>
                    <strong><?=htmlspecialchars($this->set['phone'], ENT_QUOTES, 'UTF-8')?></strong>
                </a>
            <?php endif;?>

            <?php if (!empty($this->set['email'])):?>
                <a class="contacts-page__card" href="mailto:<?=htmlspecialchars($this->set['email'], ENT_QUOTES, 'UTF-8')?>">
                    <span>Email</span>
                    <strong><?=htmlspecialchars($this->set['email'], ENT_QUOTES, 'UTF-8')?></strong>
                </a>
            <?php endif;?>

            <?php if ($contactsAddress !== ''):?>
                <div class="contacts-page__card">
                    <span>Адреса</span>
                    <strong><?=htmlspecialchars($contactsAddress, ENT_QUOTES, 'UTF-8')?></strong>
                </div>
            <?php endif;?>

        </div>

        <?php if (!empty($contactsPage['content'])):?>
            <div class="contacts-page__content">
                <?=$contactsPage['content']?>
            </div>
        <?php endif;?>

    </div>
</section>