<?php
/* ForPrint services overview surface v0.1 */

$fpServicesEscape = static function ($value): string {
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
};

$fpServicesGroups = is_array($serviceGroups ?? null)
    ? $serviceGroups
    : [];

$fpServicesContact = is_array($serviceContact ?? null)
    ? $serviceContact
    : [];

$fpServicesPhone = trim((string)(
    $fpServicesContact['phone'] ?? ''
));

$fpServicesPhoneHref = preg_replace(
    '/[^+\d]/',
    '',
    $fpServicesPhone
);

$fpServicesEmail = trim((string)(
    $fpServicesContact['email'] ?? ''
));

$fpServicesAddress = trim((string)(
    $fpServicesContact['address'] ?? ''
));

$fpServicesMapUrl = trim((string)(
    $fpServicesContact['map_url'] ?? ''
));

$fpServicesBreadcrumbs = trim((string)(
    $this->breadcrumbs ?? ''
));
?>

<section
    class="fp-services-page fp-visual-system"
    data-fp-surface="services"
    aria-labelledby="fp-services-title"
>
    <div class="fp-services-page__inner fp-layout-container fp-page-shell">
        <div class="fp-page-breadcrumbs">
            <?php if ($fpServicesBreadcrumbs !== ''): ?>
                <?=$fpServicesBreadcrumbs?>
            <?php else: ?>
                <nav
                    class="breadcrumbs fp-breadcrumbs fp-services-breadcrumb-fallback"
                    aria-label="Хлібні крихти"
                >
                    <ol class="breadcrumbs__list fp-breadcrumbs__list">
                        <li class="breadcrumbs__item fp-breadcrumbs__item">
                            <a
                                class="breadcrumbs__link fp-breadcrumbs__link"
                                href="<?=$this->alias()?>"
                            >
                                Головна
                            </a>
                        </li>
                        <li class="breadcrumbs__item fp-breadcrumbs__item">
                            <span
                                class="fp-breadcrumbs__current"
                                aria-current="page"
                            >
                                Наші послуги
                            </span>
                        </li>
                    </ol>
                </nav>
            <?php endif; ?>
        </div>

        <header class="fp-services-page__hero fp-page-header fp-page-header--with-action">
            <div class="fp-services-page__hero-copy fp-page-header__copy">
                <h1
                    id="fp-services-title"
                    class="fp-page-title"
                >
                    Наші послуги
                </h1>

                <p class="fp-page-lead">
                    Допомагаємо з поліграфією, рекламною продукцією,
                    персоналізацією та брендуванням — від підготовки
                    макета до виготовлення готового виробу.
                </p>
            </div>

            <button
                class="fp-services-page__primary-action fp-button fp-button--primary js-callback"
                type="button"
            >
                Отримати прорахунок
            </button>
        </header>

        <section
            class="fp-services-page__section"
            aria-labelledby="fp-services-directions-title"
        >
            <div class="fp-services-page__section-heading">
                <h2
                    id="fp-services-directions-title"
                    class="fp-section-title"
                >
                    Основні напрями
                </h2>

                <p class="fp-body-copy">
                    Оберіть потрібний напрям або надішліть
                    індивідуальний запит.
                </p>
            </div>

            <div class="fp-services-page__grid">
                <?php foreach ($fpServicesGroups as $fpServicesGroup): ?>
                    <?php
                    if (!is_array($fpServicesGroup)) {
                        continue;
                    }

                    $fpServicesGroupId = trim((string)(
                        $fpServicesGroup['id'] ?? ''
                    ));

                    $fpServicesGroupTitle = trim((string)(
                        $fpServicesGroup['title'] ?? ''
                    ));

                    $fpServicesGroupDescription = trim((string)(
                        $fpServicesGroup['description'] ?? ''
                    ));

                    $fpServicesGroupUrl = trim((string)(
                        $fpServicesGroup['url'] ?? ''
                    ));

                    $fpServicesGroupLabel = trim((string)(
                        $fpServicesGroup['link_label']
                        ?? 'Детальніше'
                    ));

                    if (
                        $fpServicesGroupId === ''
                        || $fpServicesGroupTitle === ''
                        || $fpServicesGroupUrl === ''
                    ) {
                        continue;
                    }
                    ?>

                    <article
                        id="<?=$fpServicesEscape($fpServicesGroupId)?>"
                        class="fp-services-page__card"
                    >
                        <h3 class="fp-card-title">
                            <?=$fpServicesEscape($fpServicesGroupTitle)?>
                        </h3>

                        <?php if ($fpServicesGroupDescription !== ''): ?>
                            <p class="fp-body-copy">
                                <?=$fpServicesEscape(
                                    $fpServicesGroupDescription
                                )?>
                            </p>
                        <?php endif; ?>

                        <a
                            class="fp-inline-link"
                            href="<?=$fpServicesEscape(
                                $this->alias(
                                    trim($fpServicesGroupUrl, '/')
                                )
                            )?>"
                        >
                            <span>
                                <?=$fpServicesEscape(
                                    $fpServicesGroupLabel
                                )?>
                            </span>
                            <span aria-hidden="true">→</span>
                        </a>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section
            class="fp-services-page__local"
            aria-labelledby="fp-services-local-title"
        >
            <div>
                <div class="fp-services-page__eyebrow fp-eyebrow">
                    Локальна студія
                </div>

                <h2
                    id="fp-services-local-title"
                    class="fp-section-title"
                >
                    Працюємо у Києві
                </h2>

                <p class="fp-body-copy">
                    Можна обговорити замовлення, передати матеріали,
                    уточнити технологію виготовлення та погодити
                    отримання готової продукції.
                </p>

                <?php if ($fpServicesAddress !== ''): ?>
                    <address>
                        <?=$fpServicesEscape($fpServicesAddress)?>
                    </address>
                <?php endif; ?>
            </div>

            <div class="fp-services-page__contacts">
                <?php if ($fpServicesPhone !== ''): ?>
                    <a
                        href="tel:<?=$fpServicesEscape(
                            $fpServicesPhoneHref
                        )?>"
                    >
                        <span>Телефон</span>
                        <strong>
                            <?=$fpServicesEscape($fpServicesPhone)?>
                        </strong>
                    </a>
                <?php endif; ?>

                <?php if ($fpServicesEmail !== ''): ?>
                    <a
                        href="mailto:<?=$fpServicesEscape(
                            $fpServicesEmail
                        )?>"
                    >
                        <span>Email</span>
                        <strong>
                            <?=$fpServicesEscape($fpServicesEmail)?>
                        </strong>
                    </a>
                <?php endif; ?>

                <?php if ($fpServicesMapUrl !== ''): ?>
                    <a
                        href="<?=$fpServicesEscape($fpServicesMapUrl)?>"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        <span>Маршрут</span>
                        <strong>Відкрити карту</strong>
                    </a>
                <?php endif; ?>
            </div>
        </section>

        <section
            class="fp-services-page__cta"
            aria-labelledby="fp-services-cta-title"
        >
            <div>
                <h2
                    id="fp-services-cta-title"
                    class="fp-section-title"
                >
                    Потрібен нестандартний виріб?
                </h2>

                <p class="fp-body-copy">
                    Надішліть опис, орієнтовний тираж, розміри,
                    макет або приклад. Ми перевіримо можливість
                    виготовлення та підготуємо прорахунок.
                </p>
            </div>

            <button
                class="fp-services-page__primary-action fp-button fp-button--primary js-callback"
                type="button"
            >
                Обговорити замовлення
            </button>
        </section>
    </div>
</section>
