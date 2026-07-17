<?php

if (!function_exists('fp_product_comm_html')) {
    function fp_product_comm_html(mixed $value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('fp_product_comm_public_path')) {
    function fp_product_comm_public_path(string $path = ''): string
    {
        $base = defined('PATH') ? (string)PATH : '/';

        if ($path === '') {
            return $base;
        }

        return rtrim($base, '/') . '/' . ltrim($path, '/');
    }
}

if (!function_exists('fp_product_comm_upload_url')) {
    function fp_product_comm_upload_url(string $path): string
    {
        $uploadDir = defined('UPLOAD_DIR') ? (string)UPLOAD_DIR : 'userfiles/';

        return fp_product_comm_public_path(rtrim($uploadDir, '/') . '/' . ltrim($path, '/'));
    }
}

if (!function_exists('fp_product_comm_safe_url')) {
    function fp_product_comm_safe_url(string $url): string
    {
        $url = trim($url);

        if ($url === '') {
            return '';
        }

        if (!preg_match('/^https?:\/\//i', $url)) {
            return '';
        }

        return $url;
    }
}

if (!function_exists('fp_product_comm_telegram_url_from_target')) {
    function fp_product_comm_telegram_url_from_target(string $target): string
    {
        $target = trim($target);

        if ($target === '') {
            return '';
        }

        if (preg_match('/^https?:\/\//i', $target)) {
            return fp_product_comm_safe_url($target);
        }

        if (str_starts_with($target, '@')) {
            return 'https://t.me/' . rawurlencode(ltrim($target, '@'));
        }

        return '';
    }
}

if (!function_exists('fp_product_comm_load_buttons')) {
    function fp_product_comm_load_buttons(): array
    {
        if (
            !defined('HOST') ||
            !defined('USER') ||
            !defined('PASSWORD') ||
            !defined('DB_NAME')
        ) {
            return [];
        }

        $db = @new mysqli(HOST, USER, PASSWORD, DB_NAME);

        if ($db->connect_errno) {
            return [];
        }

        $db->set_charset('utf8mb4');

        $exists = $db->query("SHOW TABLES LIKE 'communication_buttons'");
        if (!$exists || $exists->num_rows === 0) {
            $db->close();
            return [];
        }

        $rows = $db->query("
            SELECT *
            FROM `communication_buttons`
            WHERE `visible` = 1
            ORDER BY `position`, `id`
        ");

        $buttons = [];

        if ($rows) {
            while ($row = $rows->fetch_assoc()) {
                $alias = trim((string)($row['alias'] ?? ''));

                if ($alias === '') {
                    continue;
                }

                $buttons[] = $row;
            }
        }

        $db->close();

        return $buttons;
    }
}

if (!function_exists('fp_product_comm_default_buttons')) {
    function fp_product_comm_default_buttons(): array
    {
        return [
            [
                'name' => 'Telegram чат',
                'alias' => 'telegram',
                'button_label' => 'Запит у Telegram',
                'target' => '@forprint_printshop',
                'direct_url' => 'https://t.me/forprint_printshop',
                'primary_contact_label' => 'Ваш Telegram username',
                'phone_label' => 'Телефон для звʼязку',
                'intro' => 'Ця форма підготує запит у Telegram. Якщо бажаєте продовжити діалог у Telegram, вкажіть username. Якщо бажаєте, щоб з вами звʼязалися телефоном, вкажіть номер телефону.',
                'img' => '',
            ],
            [
                'name' => 'Email запит',
                'alias' => 'email',
                'button_label' => 'Запит на Email',
                'target' => 'office@forprint.net.ua',
                'direct_url' => '',
                'primary_contact_label' => 'Ваш email',
                'phone_label' => 'Телефон для звʼязку',
                'intro' => 'Ця форма підготує запит на email. Якщо бажаєте продовжити спілкування через email, вкажіть пошту. Якщо бажаєте, щоб з вами звʼязалися телефоном, вкажіть номер телефону.',
                'img' => '',
            ],
        ];
    }
}

if (!function_exists('fp_product_comm_icon_html')) {
    function fp_product_comm_icon_html(array $button, string $fallbackText): string
    {
        $icon = trim((string)($button['img'] ?? ''));

        if ($icon !== '') {
            return '<img class="fp-product-communication__icon" src="'
                . fp_product_comm_html(fp_product_comm_upload_url($icon))
                . '" alt="" loading="lazy">';
        }

        return '<span class="fp-product-communication__icon-placeholder">'
            . fp_product_comm_html($fallbackText)
            . '</span>';
    }
}

if (!function_exists('fp_render_product_communication_buttons')) {
    function fp_render_product_communication_buttons(array $product): string
    {
        $buttons = fp_product_comm_load_buttons();

        if (!$buttons) {
            $buttons = fp_product_comm_default_buttons();
        }

        $telegramButton = null;
        foreach ($buttons as $button) {
            if (($button['alias'] ?? '') === 'telegram') {
                $telegramButton = $button;
                break;
            }
        }

        $telegramDirectUrl = '';
        if (is_array($telegramButton)) {
            $telegramDirectUrl = fp_product_comm_safe_url((string)($telegramButton['direct_url'] ?? ''));

            if ($telegramDirectUrl === '') {
                $telegramDirectUrl = fp_product_comm_telegram_url_from_target((string)($telegramButton['target'] ?? ''));
            }
        }

        $productId = (int)($product['id'] ?? 0);
        $productName = (string)($product['name'] ?? '');
        $productUrl = (string)($_SERVER['REQUEST_URI'] ?? '');

        ob_start();
        ?>
        <section class="fp-product-communication" data-fp-product-communication>
            <div class="fp-product-communication__buttons">
                <?php if ($telegramDirectUrl !== '' && is_array($telegramButton)): ?>
                    <a
                        class="fp-product-communication__button fp-product-communication__button--direct-chat"
                        href="<?= fp_product_comm_html($telegramDirectUrl) ?>"
                        target="_blank"
                        rel="noopener"
                    >
                        <?= fp_product_comm_icon_html($telegramButton, 'TG') ?>
                        <span>Почати чат</span>
                    </a>
                <?php endif; ?>

                <?php foreach ($buttons as $button): ?>
                    <?php
                    $alias = trim((string)($button['alias'] ?? ''));
                    $label = trim((string)($button['button_label'] ?? $button['name'] ?? 'Запит'));
                    ?>
                    <button
                        type="button"
                        class="fp-product-communication__button fp-product-communication__button--<?= fp_product_comm_html($alias) ?>"
                        data-fp-comm-open="<?= fp_product_comm_html($alias) ?>"
                    >
                        <?= fp_product_comm_icon_html($button, mb_strtoupper(mb_substr($alias, 0, 2))) ?>
                        <span><?= fp_product_comm_html($label) ?></span>
                    </button>
                <?php endforeach; ?>
            </div>

            <?php foreach ($buttons as $button): ?>
                <?php
                $alias = trim((string)($button['alias'] ?? ''));
                $title = trim((string)($button['name'] ?? $button['button_label'] ?? 'Запит'));
                $intro = trim((string)($button['intro'] ?? ''));
                $primaryLabel = trim((string)($button['primary_contact_label'] ?? 'Контакт'));
                $phoneLabel = trim((string)($button['phone_label'] ?? 'Телефон'));
                ?>
                <div
                    class="fp-product-communication-modal"
                    data-fp-comm-modal="<?= fp_product_comm_html($alias) ?>"
                    hidden
                >
                    <div class="fp-product-communication-modal__backdrop" data-fp-comm-close></div>

                    <div class="fp-product-communication-modal__dialog" role="dialog" aria-modal="true">
                        <button
                            type="button"
                            class="fp-product-communication-modal__close"
                            data-fp-comm-close
                            aria-label="Закрити"
                        >
                            ×
                        </button>

                        <h3 class="fp-product-communication-modal__title">
                            <?= fp_product_comm_html($title) ?>
                        </h3>

                        <?php if ($intro !== ''): ?>
                            <p class="fp-product-communication-modal__intro">
                                <?= nl2br(fp_product_comm_html($intro)) ?>
                            </p>
                        <?php endif; ?>

                        <form
                            class="fp-product-communication-form"
                            method="post"
                            action="<?= fp_product_comm_html(fp_product_comm_public_path('communication-request.php')) ?>"
                            data-fp-comm-form
                        >
                            <input type="hidden" name="mode" value="<?= fp_product_comm_html($alias) ?>">
                            <input type="hidden" name="product_id" value="<?= $productId ?>">
                            <input type="hidden" name="product_name" value="<?= fp_product_comm_html($productName) ?>">
                            <input type="hidden" name="product_url" value="<?= fp_product_comm_html($productUrl) ?>">
                            <input type="text" name="fp_request_company_url_confirm" value="" class="fp-product-communication-form__trap" tabindex="-1" autocomplete="new-password" aria-hidden="true" inputmode="none">

                            <label class="fp-product-communication-form__field">
                                <span><?= fp_product_comm_html($primaryLabel) ?></span>
                                <input type="text" name="primary_contact" autocomplete="off">
                            </label>

                            <label class="fp-product-communication-form__field">
                                <span><?= fp_product_comm_html($phoneLabel) ?></span>
                                <input type="tel" name="phone" autocomplete="tel" inputmode="tel" placeholder="+380 67 123 45 67" data-fp-phone-international>
                                <small class="fp-product-communication-form__phone-warning" data-fp-phone-warning hidden></small>
                            </label>

                            <label class="fp-product-communication-form__field">
                                <span>Кількість товару</span>
                                <input type="text" name="quantity_requested" autocomplete="off" placeholder="Наприклад: 100 шт.">
                            </label>

                            <label class="fp-product-communication-form__field">
                                <span>Коментар до запиту</span>
                                <textarea name="message" rows="4"></textarea>
                            </label>

                            <button type="submit" class="fp-product-communication-form__submit">
                                Відправити запит
                            </button>

                            <div class="fp-product-communication-form__status" data-fp-comm-status></div>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </section>
        <?php
        return trim((string)ob_get_clean());
    }
}