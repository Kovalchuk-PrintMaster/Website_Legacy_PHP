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

/* FP_CONTACTS_SCHEDULE_COMPAT_START */
$fpContactsSchedule = [
    'weekly' => [],
    'exceptions' => [],
];

$fpContactsScheduleText = '';

$fpContactsScheduleSource = (
    $this->set['contacts_schedule']
    ?? $this->set['contacts_working_hours']
    ?? $this->set['working_hours']
    ?? $this->set['work_schedule']
    ?? ''
);

$fpContactsScheduleDecoded = null;
$fpContactsScheduleRaw = '';

if (is_array($fpContactsScheduleSource)) {
    $fpContactsScheduleDecoded = $fpContactsScheduleSource;
} elseif (is_scalar($fpContactsScheduleSource)) {
    $fpContactsScheduleRaw = html_entity_decode(
        trim((string)$fpContactsScheduleSource),
        ENT_QUOTES | ENT_HTML5,
        'UTF-8'
    );

    $fpContactsScheduleRaw = preg_replace(
        '/^\xEF\xBB\xBF/',
        '',
        $fpContactsScheduleRaw
    ) ?? $fpContactsScheduleRaw;

    if ($fpContactsScheduleRaw !== '') {
        $fpContactsScheduleDecoded = json_decode(
            $fpContactsScheduleRaw,
            true
        );

        /*
         * Some historical admin flows stored JSON as a JSON string.
         * Decode that representation once more when necessary.
         */
        if (is_string($fpContactsScheduleDecoded)) {
            $fpNestedSchedule = trim(
                $fpContactsScheduleDecoded
            );

            if (
                $fpNestedSchedule !== ''
                && in_array(
                    $fpNestedSchedule[0] ?? '',
                    ['{', '['],
                    true
                )
            ) {
                $fpContactsScheduleDecoded = json_decode(
                    $fpNestedSchedule,
                    true
                );
            } elseif ($fpNestedSchedule !== '') {
                $fpContactsScheduleText = trim(
                    strip_tags($fpNestedSchedule)
                );
            }
        }

        /*
         * Preserve an older human-readable schedule without interpreting
         * or inventing its business hours.
         */
        if (
            !is_array($fpContactsScheduleDecoded)
            && $fpContactsScheduleText === ''
            && !in_array(
                $fpContactsScheduleRaw[0] ?? '',
                ['{', '['],
                true
            )
        ) {
            $fpContactsScheduleText = trim(
                strip_tags($fpContactsScheduleRaw)
            );
        }
    }
}

$fpContactsNormalizeWeekly = static function (
    $source
): array {
    if (!is_array($source)) {
        return [];
    }

    $normalized = [];

    foreach ($source as $key => $row) {
        if (is_string($row)) {
            $label = is_string($key)
                ? trim($key)
                : '';
            $hours = trim($row);

            if ($label === '' || $hours === '') {
                continue;
            }

            $normalized[] = [
                'label' => $label,
                'status' => (
                    mb_stripos($hours, 'вихід') !== false
                        ? 'closed'
                        : 'open'
                ),
                'hours' => $hours,
                'open' => '',
                'close' => '',
            ];

            continue;
        }

        if (!is_array($row)) {
            continue;
        }

        $fallbackLabel = is_string($key)
            ? trim($key)
            : '';

        $label = trim((string)(
            $row['label']
            ?? $row['day']
            ?? $row['name']
            ?? $fallbackLabel
        ));

        $status = strtolower(trim((string)(
            $row['status']
            ?? 'open'
        )));

        if (
            in_array(
                $status,
                ['off', 'day_off', 'weekend'],
                true
            )
        ) {
            $status = 'closed';
        }

        $hours = trim((string)(
            $row['hours']
            ?? $row['value']
            ?? $row['time']
            ?? ''
        ));

        $open = trim((string)(
            $row['open']
            ?? $row['from']
            ?? ''
        ));

        $close = trim((string)(
            $row['close']
            ?? $row['to']
            ?? ''
        ));

        if (
            $label === ''
            || (
                $status !== 'closed'
                && $hours === ''
                && $open === ''
                && $close === ''
            )
        ) {
            continue;
        }

        $normalized[] = [
            'label' => $label,
            'status' => $status,
            'hours' => $hours,
            'open' => $open,
            'close' => $close,
        ];
    }

    return $normalized;
};

if (is_array($fpContactsScheduleDecoded)) {
    $fpWeeklySource = (
        $fpContactsScheduleDecoded['weekly']
        ?? $fpContactsScheduleDecoded['days']
        ?? $fpContactsScheduleDecoded['schedule']
        ?? null
    );

    $fpExceptionsSource = (
        $fpContactsScheduleDecoded['exceptions']
        ?? $fpContactsScheduleDecoded['special_days']
        ?? $fpContactsScheduleDecoded['holidays']
        ?? []
    );

    if (
        $fpWeeklySource === null
        && function_exists('array_is_list')
        && array_is_list($fpContactsScheduleDecoded)
    ) {
        $fpWeeklySource = $fpContactsScheduleDecoded;
    }

    $fpContactsSchedule['weekly'] = (
        $fpContactsNormalizeWeekly($fpWeeklySource)
    );

    $fpContactsSchedule['exceptions'] = (
        is_array($fpExceptionsSource)
            ? $fpExceptionsSource
            : []
    );
}

$fpContactsScheduleVisible = (
    !empty($fpContactsSchedule['weekly'])
    || !empty($fpContactsSchedule['exceptions'])
    || $fpContactsScheduleText !== ''
);
/* FP_CONTACTS_SCHEDULE_COMPAT_END */


$fpContactsMapUrl = $fpContactsAddress !== ''
    ? 'https://www.google.com/maps?q='
        . rawurlencode($fpContactsAddress)
        . '&output=embed'
    : '';
?>
<section
    class="contacts-page fp-visual-system"
    data-fp-surface="contacts"
>
    <div class="contacts-page__inner fp-layout-container fp-page-shell">
        <div class="fp-page-breadcrumbs">
            <?=$this->breadcrumbs?>
        </div>

        <div class="contacts-page__hero fp-page-header fp-page-header--with-action">
            <div class="fp-page-header__copy">
                <h1 class="fp-page-title"><?=htmlspecialchars($fpContactsTitle, ENT_QUOTES, 'UTF-8')?></h1>
                <?php if ($fpContactsIntro !== ''): ?>
                    <p class="fp-page-lead"><?=nl2br(htmlspecialchars($fpContactsIntro, ENT_QUOTES, 'UTF-8'))?></p>
                <?php endif; ?>
            </div>

            <?php if ($fpContactsCallbackLabel !== ''): ?>
                <button class="contacts-page__callback js-callback fp-button fp-button--primary" type="button">
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

                        <?php if ($fpContactsScheduleText !== ''): ?>
                            <p class="contacts-page__schedule-text">
                                <?=nl2br(htmlspecialchars(
                                    $fpContactsScheduleText,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ))?>
                            </p>
                        <?php endif; ?>

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
                                    $fpScheduleHours = trim((string)(
                                        $fpScheduleRow['hours'] ?? ''
                                    ));
                                    $fpScheduleValue = $fpScheduleStatus === 'closed'
                                        ? 'Вихідний'
                                        : (
                                            $fpScheduleHours !== ''
                                                ? $fpScheduleHours
                                                : trim(
                                                    $fpScheduleOpen
                                                    . (
                                                        $fpScheduleOpen !== ''
                                                        && $fpScheduleClose !== ''
                                                            ? '–'
                                                            : ''
                                                    )
                                                    . $fpScheduleClose
                                                )
                                        );

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
