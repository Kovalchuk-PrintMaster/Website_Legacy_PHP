</main>
<?php
/* ForPrint managed footer v0.6.37 */

$fpFooterSettings = is_array($this->footerSettings ?? null)
    ? $this->footerSettings
    : [];
$fpFooterVisible = !array_key_exists('visible', $fpFooterSettings)
    || (int)$fpFooterSettings['visible'] === 1;

$fpFooterLinks = !empty($this->footerLinks) && is_array($this->footerLinks)
    ? $this->footerLinks
    : [
        ['name' => 'Каталог', 'url' => 'catalog', 'target_blank' => 0],
        ['name' => 'Про нас', 'url' => 'about', 'target_blank' => 0],
        ['name' => 'Доставка і оплата', 'url' => 'information/oplata-i-dostavka', 'target_blank' => 0],
        ['name' => 'Контакти', 'url' => 'contacts', 'target_blank' => 0],
        ['name' => 'Як нас знайти', 'url' => 'https://maps.app.goo.gl/9qVWMQqJbTaJEoLh8', 'target_blank' => 1],
        ['name' => 'Карта сайту', 'url' => '#', 'target_blank' => 0],
    ];

/* FP_SERVICES_FOOTER_LINK_START */
$fpServicesFooterLinkExists = false;

foreach ($fpFooterLinks as $fpExistingFooterLink) {
    $fpExistingFooterUrl = trim(
        (string)($fpExistingFooterLink['url'] ?? ''),
        '/'
    );

    if ($fpExistingFooterUrl === 'nashi-posluhy') {
        $fpServicesFooterLinkExists = true;
        break;
    }
}

if (!$fpServicesFooterLinkExists) {
    $fpServicesFooterLink = [
        'name' => 'Наші послуги',
        'url' => 'nashi-posluhy',
        'target_blank' => 0,
    ];

    $fpServicesFooterPosition = min(
        2,
        count($fpFooterLinks)
    );

    array_splice(
        $fpFooterLinks,
        $fpServicesFooterPosition,
        0,
        [$fpServicesFooterLink]
    );
}
/* FP_SERVICES_FOOTER_LINK_END */

/* FP_CONSENT_FOOTER_LINK_START */
$fpConsentFooterLinkExists = false;

foreach ($fpFooterLinks as $fpExistingFooterLink) {
    $fpExistingFooterUrl = trim((string)(
        $fpExistingFooterLink['url'] ?? ''
    ));

    if ($fpExistingFooterUrl === '#fp-consent-settings') {
        $fpConsentFooterLinkExists = true;
        break;
    }
}

if (!$fpConsentFooterLinkExists) {
    $fpFooterLinks[] = [
        'name' => 'Налаштування cookies',
        'url' => '#fp-consent-settings',
        'target_blank' => 0,
    ];
}
/* FP_CONSENT_FOOTER_LINK_END */

$fpFooterPhones = !empty($this->footerPhones) && is_array($this->footerPhones)
    ? $this->footerPhones
    : [
        ['name' => '', 'phone' => '+380 96 053 00 51'],
    ];

$fpFooterResolveUrl = function (string $url): string {
    $url = trim($url);

    if ($url === '') {
        return '#';
    }

    if (
        $url[0] === '#'
        || preg_match('/^(?:https?:|mailto:|tel:)/i', $url)
    ) {
        return $url;
    }

    return $this->alias(trim($url, '/'));
};

$fpFooterLogo = trim((string)($fpFooterSettings['logo_img'] ?? ''));
if ($fpFooterLogo === '') {
    $fpFooterLogo = 'logo/Mast_LogN_square.png';
}

$fpFooterEmail = trim((string)($fpFooterSettings['email'] ?? 'druk.smile@gmail.com'));
$fpFooterEmailLabel = trim((string)($fpFooterSettings['email_label'] ?? 'work.printmaster@gmail.com'));
if ($fpFooterEmailLabel === '') {
    $fpFooterEmailLabel = $fpFooterEmail;
}

$fpFooterCallbackLabel = trim((string)($fpFooterSettings['callback_label'] ?? "Зв'язатися з нами"));
$fpFooterCallbackUrl = trim((string)($fpFooterSettings['callback_url'] ?? ''));
$fpFooterCopyright = trim((string)($fpFooterSettings['copyright_text'] ?? 'Copyright - Print Master 2025'));
?>

<?php if ($fpFooterVisible): ?>
<footer class="footer fp-site-footer" xmlns="http://www.w3.org/1999/html">
    <div class="container fp-site-footer__container fp-layout-container">
        <div class="footer__wrapper">
            <div class="footer__top">
                <div class="footer__top_logo">
                    <a
                        href="<?=$this->alias()?>"
                        class="fp-site-footer__logo-link"
                        aria-label="На головну сторінку"
                    >
                        <img
                            src="<?=htmlspecialchars($this->img($fpFooterLogo), ENT_QUOTES, 'UTF-8')?>"
                            alt=""
                            loading="lazy"
                        >
                    </a>
                </div>

                <nav class="footer__top_menu" aria-label="Навігація футера">
                    <ul>
                        <?php foreach ($fpFooterLinks as $fpFooterLink): ?>
                            <?php
                            $fpFooterLinkName = trim((string)($fpFooterLink['name'] ?? ''));
                            $fpFooterLinkUrl = $fpFooterResolveUrl((string)($fpFooterLink['url'] ?? ''));
                            $fpFooterLinkImage = trim((string)($fpFooterLink['img'] ?? ''));
                            $fpFooterBlank = (int)($fpFooterLink['target_blank'] ?? 0) === 1;
                            ?>
                            <?php if ($fpFooterLinkName !== ''): ?>
                                <li>
                                    <a
                                        href="<?=htmlspecialchars($fpFooterLinkUrl, ENT_QUOTES, 'UTF-8')?>"
                                        <?=$fpFooterBlank ? 'target="_blank" rel="noopener noreferrer"' : ''?>
                                    >
                                        <?php if ($fpFooterLinkImage !== ''): ?>
                                            <img
                                                class="fp-site-footer__link-icon"
                                                src="<?=htmlspecialchars($this->img($fpFooterLinkImage), ENT_QUOTES, 'UTF-8')?>"
                                                alt=""
                                                loading="lazy"
                                            >
                                        <?php endif; ?>
                                        <span><?=htmlspecialchars($fpFooterLinkName, ENT_QUOTES, 'UTF-8')?></span>
                                    </a>
                                </li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                </nav>

                <?php /* ForPrint compact footer composition v0.6.40 */ ?>
                <div class="footer__top_contacts">
                    <?php if ($fpFooterEmail !== ''): ?>
                        <div class="footer__contact footer__contact--email">
                            <a href="mailto:<?=htmlspecialchars($fpFooterEmail, ENT_QUOTES, 'UTF-8')?>">
                                <?=htmlspecialchars($fpFooterEmailLabel, ENT_QUOTES, 'UTF-8')?>
                            </a>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($fpFooterPhones)): ?>
                        <div class="footer__contact footer__contact--phones">
                            <?php foreach ($fpFooterPhones as $fpFooterPhone): ?>
                                <?php
                                $fpFooterPhoneValue = trim((string)($fpFooterPhone['phone'] ?? ''));
                                $fpFooterPhoneLabel = trim((string)($fpFooterPhone['name'] ?? ''));
                                if ($fpFooterPhoneLabel === '') {
                                    $fpFooterPhoneLabel = $fpFooterPhoneValue;
                                }
                                $fpFooterPhoneHref = preg_replace('/[^+\d]/', '', $fpFooterPhoneValue);
                                ?>
                                <?php if ($fpFooterPhoneValue !== ''): ?>
                                    <a href="tel:<?=htmlspecialchars($fpFooterPhoneHref, ENT_QUOTES, 'UTF-8')?>">
                                        <?=htmlspecialchars($fpFooterPhoneLabel, ENT_QUOTES, 'UTF-8')?>
                                    </a>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($fpFooterCallbackLabel !== '' || $fpFooterCopyright !== ''): ?>
                        <div class="footer__contact footer__contact--action">
                            <?php if ($fpFooterCallbackLabel !== ''): ?>
                                <a
                                    <?=$fpFooterCallbackUrl === ''
                                        ? 'href="#" class="footer__callback-link js-callback"'
                                        : 'href="' . htmlspecialchars(
                                            $fpFooterResolveUrl($fpFooterCallbackUrl),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) . '" class="footer__callback-link"'?>
                                ><?=htmlspecialchars($fpFooterCallbackLabel, ENT_QUOTES, 'UTF-8')?></a>
                            <?php endif; ?>

                            <?php if ($fpFooterCopyright !== ''): ?>
                                <div class="footer__bottom_copy">
                                    <?=htmlspecialchars($fpFooterCopyright, ENT_QUOTES, 'UTF-8')?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</footer>
<?php endif; ?>

<!-- FP_CONSENT_BANNER_START -->
<div
    class="fp-consent fp-visual-system"
    data-fp-consent-root
    hidden
>
    <div
        class="fp-consent__panel"
        role="dialog"
        aria-modal="false"
        aria-labelledby="fp-consent-title"
        aria-describedby="fp-consent-description"
    >
        <div class="fp-consent__copy">
            <h2
                id="fp-consent-title"
                class="fp-card-title"
            >
                Налаштування приватності
            </h2>

            <p
                id="fp-consent-description"
                class="fp-body-copy"
            >
                Необхідні дані використовуються для роботи сайту,
                кошика, безпеки форм і збереження вашого вибору.
                Google Ads measurement завантажується лише після
                вашого дозволу. Персональні дані з форм до Google
                не передаються.
            </p>
        </div>

        <div class="fp-consent__actions">
            <button
                class="fp-consent__button fp-consent__button--secondary fp-button"
                type="button"
                data-fp-consent-deny
            >
                Лише необхідні
            </button>

            <button
                class="fp-consent__button fp-consent__button--primary fp-button fp-button--primary"
                type="button"
                data-fp-consent-allow
            >
                Дозволити вимірювання
            </button>
        </div>
    </div>
</div>
<!-- FP_CONSENT_BANNER_END -->

<div class="hide-elems">
    <svg>
        <defs>
            <linearGradient id="rainbow" x1="0" y1="0" x2="50%" y2="50%">
                <stop offset="0%" stop-color="#7282bc" />s
                <stop offset="100%" stop-color="#7abfcc" />
            </linearGradient>
        </defs>
    </svg>
</div>


    <div class="login-popup">

        <div class="order-popup__inner">

            <h2><span>Реєстрація/</span><span>Вхід</span></h2>

            <form action="<?=$this->alias(['login' => 'registration'])?>" method="post" >
                <input type="text" name="name" required placeholder="Введіть, будь ласка, ваш ім'я" value="<?=$this->setFormValues('name', 'userData')?>">

                <input type="tel" name="phone" required placeholder="Введіть, будь ласка, телефон у форматі +38 0ХХ ХХХ ХХ ХХ" value="<?=$this->setFormValues('phone', 'userData')?>">
                <input type="email" name="email" required placeholder="Введіть, будь ласка, ваш e-mail" value="<?=$this->setFormValues('email', 'userData')?>">

                <input type="password" name="password" required placeholder="Введіть ваш пароль">
                <input type="password" name="confirm_password" required placeholder="Підтвердження пароля">

                <div class="send-order">
                    <input class="execute-order_btn" type="submit" value="Зареєструватися">
                </div>
            </form>

            <form action="<?=$this->alias(['login' => 'login'])?>" method="post" style="display: none">

                <input type="text" name="login" required placeholder="Введіть, будь ласка, ваш e-mail" value="<?=$this->setFormValues('email')?>">

                <input type="password" name="password" required placeholder="Введіть ваш пароль">

                <div class="send-order">
                    <input class="execute-order_btn" type="submit" value="Увійти">
                </div>

            </form>



        </div>

    </div>


<?php $this->getScripts()?>

<!-- убрать -->
<!--<script src="assets/js/freeHost.js"></script>-->
<!-- убрать -->

<?php if (!empty($_SESSION['res']['answer'])):?>

    <div class="wq-message__wrap"><?=$_SESSION['res']['answer']?></div>

<?php endif;?>

<?php unset($_SESSION['res']);?>

</body>

</html>
