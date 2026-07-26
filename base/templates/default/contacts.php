<?php
/* ForPrint managed contacts surface v0.6.43 */

$fpContactsValue = static function (
    array $settings,
    string $key,
    string $fallback = ''
): string {
    $value = trim((string)($settings[$key] ?? ''));

    return $value !== '' ? $value : $fallback;
};

$fpContactsTitle = $fpContactsValue(
    $this->set,
    'contacts_title',
    trim((string)($contactsPage['name'] ?? 'Контакти'))
);
$fpContactsIntro = trim(strip_tags($fpContactsValue(
    $this->set,
    'contacts_intro',
    "Зв'яжіться з нами, щоб обговорити друк, рекламно-інформаційні продукти, макети, терміни виготовлення або індивідуальне замовлення."
)));
$fpContactsPhone = $fpContactsValue(
    $this->set,
    'contacts_phone',
    trim((string)($this->set['phone'] ?? ''))
);
$fpContactsEmail = $fpContactsValue(
    $this->set,
    'contacts_email',
    trim((string)($this->set['email'] ?? ''))
);
$fpContactsAddress = trim(strip_tags($fpContactsValue(
    $this->set,
    'contacts_address',
    trim(strip_tags((string)($this->set['address'] ?? '')))
)));
$fpContactsCallbackLabel = $fpContactsValue(
    $this->set,
    'contacts_callback_label',
    "Зв'язатися з нами"
);
$fpContactsContent = $fpContactsValue(
    $this->set,
    'contacts_content',
    trim((string)($contactsPage['content'] ?? ''))
);

$fpContactsSchedule = [
    'weekly' => [],
    'exceptions' => [],
];
$fpContactsScheduleRaw = trim((string)($this->set['contacts_schedule'] ?? ''));

if ($fpContactsScheduleRaw !== '') {
    $fpContactsScheduleDecoded = json_decode($fpContactsScheduleRaw, true);

    if (is_array($fpContactsScheduleDecoded)) {
        $fpContactsSchedule['weekly'] = is_array(
            $fpContactsScheduleDecoded['weekly'] ?? null
        ) ? $fpContactsScheduleDecoded['weekly'] : [];
        $fpContactsSchedule['exceptions'] = is_array(
            $fpContactsScheduleDecoded['exceptions'] ?? null
        ) ? $fpContactsScheduleDecoded['exceptions'] : [];
    }
}

$fpContactsScheduleVisible = !empty($fpContactsSchedule['weekly'])
    || !empty($fpContactsSchedule['exceptions']);

$fpContactsMapUrl = $fpContactsAddress !== ''
    ? 'https://www.google.com/maps?q='
        . rawurlencode($fpContactsAddress)
        . '&output=embed'
    : '';
?>
<section class="contacts-page" data-fp-surface="contacts">
    <div class="contacts-page__inner fp-layout-container">
        <?=$this->breadcrumbs?>

        <div class="contacts-page__hero">
            <div>
                <div class="contacts-page__eyebrow">ForPrint</div>
                <h1><?=htmlspecialchars($fpContactsTitle, ENT_QUOTES, 'UTF-8')?></h1>
                <?php if ($fpContactsIntro !== ''): ?>
                    <p><?=nl2br(htmlspecialchars($fpContactsIntro, ENT_QUOTES, 'UTF-8'))?></p>
                <?php endif; ?>
            </div>

            <?php if ($fpContactsCallbackLabel !== ''): ?>
                <button class="contacts-page__callback js-callback" type="button">
                    <?=htmlspecialchars($fpContactsCallbackLabel, ENT_QUOTES, 'UTF-8')?>
                </button>
            <?php endif; ?>
        </div>

        <div class="contacts-page__grid">
            <?php if ($fpContactsPhone !== ''): ?>
                <a
                    class="contacts-page__card"
                    href="tel:<?=htmlspecialchars(
                        (string)preg_replace('/[^+\d]/', '', $fpContactsPhone),
                        ENT_QUOTES,
                        'UTF-8'
                    )?>"
                >
                    <span>Телефон</span>
                    <strong><?=htmlspecialchars($fpContactsPhone, ENT_QUOTES, 'UTF-8')?></strong>
                </a>
            <?php endif; ?>

            <?php if ($fpContactsEmail !== ''): ?>
                <a
                    class="contacts-page__card"
                    href="mailto:<?=htmlspecialchars($fpContactsEmail, ENT_QUOTES, 'UTF-8')?>"
                >
                    <span>Email</span>
                    <strong><?=htmlspecialchars($fpContactsEmail, ENT_QUOTES, 'UTF-8')?></strong>
                </a>
            <?php endif; ?>

            <?php if ($fpContactsAddress !== ''): ?>
                <div class="contacts-page__card">
                    <span>Адреса</span>
                    <strong><?=htmlspecialchars($fpContactsAddress, ENT_QUOTES, 'UTF-8')?></strong>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($fpContactsScheduleVisible || $fpContactsMapUrl !== ''): ?>
            <div class="contacts-page__operations<?=$fpContactsMapUrl === '' ? ' contacts-page__operations--schedule-only' : ''?>">
                <?php if ($fpContactsScheduleVisible): ?>
                    <section class="contacts-page__schedule" aria-labelledby="fp-contacts-schedule-title">
                        <h2 id="fp-contacts-schedule-title">Графік роботи</h2>

                        <?php if (!empty($fpContactsSchedule['weekly'])): ?>
                            <div class="contacts-page__schedule-grid">
                                <?php foreach ($fpContactsSchedule['weekly'] as $fpScheduleRow): ?>
                                    <?php
                                    if (!is_array($fpScheduleRow)) {
                                        continue;
                                    }

                                    $fpScheduleLabel = trim((string)($fpScheduleRow['label'] ?? ''));
                                    $fpScheduleStatus = trim((string)($fpScheduleRow['status'] ?? 'open'));
                                    $fpScheduleOpen = trim((string)($fpScheduleRow['open'] ?? ''));
                                    $fpScheduleClose = trim((string)($fpScheduleRow['close'] ?? ''));
                                    $fpScheduleValue = $fpScheduleStatus === 'closed'
                                        ? 'Вихідний'
                                        : trim($fpScheduleOpen . ($fpScheduleOpen !== '' && $fpScheduleClose !== '' ? '–' : '') . $fpScheduleClose);

                                    if ($fpScheduleLabel === '' || $fpScheduleValue === '') {
                                        continue;
                                    }
                                    ?>
                                    <div class="contacts-page__schedule-row<?=$fpScheduleStatus === 'closed' ? ' is-closed' : ''?>">
                                        <span><?=htmlspecialchars($fpScheduleLabel, ENT_QUOTES, 'UTF-8')?></span>
                                        <strong><?=htmlspecialchars($fpScheduleValue, ENT_QUOTES, 'UTF-8')?></strong>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($fpContactsSchedule['exceptions'])): ?>
                            <div class="contacts-page__exceptions">
                                <h3>Зміни у графіку</h3>
                                <?php foreach ($fpContactsSchedule['exceptions'] as $fpException): ?>
                                    <?php
                                    if (!is_array($fpException)) {
                                        continue;
                                    }

                                    $fpExceptionDate = trim((string)($fpException['date'] ?? ''));
                                    $fpExceptionStatus = trim((string)($fpException['status'] ?? 'closed'));
                                    $fpExceptionOpen = trim((string)($fpException['open'] ?? ''));
                                    $fpExceptionClose = trim((string)($fpException['close'] ?? ''));
                                    $fpExceptionNote = trim((string)($fpException['note'] ?? ''));

                                    if ($fpExceptionDate === '') {
                                        continue;
                                    }

                                    $fpExceptionTimestamp = strtotime($fpExceptionDate);
                                    $fpExceptionDateLabel = $fpExceptionTimestamp !== false
                                        ? date('d.m.Y', $fpExceptionTimestamp)
                                        : $fpExceptionDate;
                                    $fpExceptionHours = $fpExceptionStatus === 'closed'
                                        ? 'Вихідний'
                                        : trim($fpExceptionOpen . ($fpExceptionOpen !== '' && $fpExceptionClose !== '' ? '–' : '') . $fpExceptionClose);
                                    ?>
                                    <div class="contacts-page__exception<?=$fpExceptionStatus === 'closed' ? ' is-closed' : ''?>">
                                        <strong><?=htmlspecialchars($fpExceptionDateLabel, ENT_QUOTES, 'UTF-8')?></strong>
                                        <span><?=htmlspecialchars($fpExceptionHours, ENT_QUOTES, 'UTF-8')?></span>
                                        <?php if ($fpExceptionNote !== ''): ?>
                                            <small><?=htmlspecialchars($fpExceptionNote, ENT_QUOTES, 'UTF-8')?></small>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </section>
                <?php endif; ?>

                <?php if ($fpContactsMapUrl !== ''): ?>
                    <section class="contacts-page__map" aria-label="Карта розташування">
                        <iframe
                            src="<?=htmlspecialchars($fpContactsMapUrl, ENT_QUOTES, 'UTF-8')?>"
                            title="Карта розташування ForPrint"
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade"
                            allowfullscreen
                        ></iframe>
                    </section>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($fpContactsContent !== ''): ?>
            <div class="contacts-page__content">
                <?=$fpContactsContent?>
            </div>
        <?php endif; ?>
    </div>
</section>
