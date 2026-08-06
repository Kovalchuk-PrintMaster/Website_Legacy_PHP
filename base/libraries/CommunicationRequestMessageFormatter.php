<?php

declare(strict_types=1);

/**
 * Formats communication requests without coupling transport code
 * to presentation details.
 */
final class CommunicationRequestMessageFormatter
{
    private const THEMES = [
        'default' => [
            'header_icon' => '🖨',
        ],
        'winter' => [
            'header_icon' => '❄️',
        ],
        'easter' => [
            'header_icon' => '🌿',
        ],
    ];

    public static function absolutePublicUrl(
        string $value,
        array $server,
        string $configuredOrigin = ''
    ): string {
        $value = trim(
            preg_replace(
                '/[\x00-\x1F\x7F]/u',
                '',
                $value
            ) ?? ''
        );

        if ($value === '') {
            $value = '/';
        }

        if (self::isHttpUrl($value)) {
            return $value;
        }

        if (str_starts_with($value, '//')) {
            return '';
        }

        $origin = self::normaliseOrigin(
            $configuredOrigin
        );

        if ($origin === '') {
            $origin = self::originFromServer(
                $server
            );
        }

        if ($origin === '') {
            return str_starts_with($value, '/')
                ? $value
                : '/' . ltrim($value, '/');
        }

        return $origin
            . '/'
            . ltrim($value, '/');
    }

    /**
     * @return array{text:string, parse_mode:string}
     */
    public static function telegram(
        array $data,
        string $theme = 'default'
    ): array {
        $profile = self::THEMES[
            array_key_exists($theme, self::THEMES)
                ? $theme
                : 'default'
        ];

        $modeLabel = match (
            (string)($data['mode'] ?? '')
        ) {
            'telegram' => 'Telegram',
            'email' => 'Email',
            default => 'Сайт',
        };

        $productName = self::htmlValue(
            (string)($data['product_name'] ?? '')
        );

        $productUrl = self::safeHttpUrl(
            (string)($data['product_url'] ?? '')
        );

        $primaryContact = self::htmlValueOrDash(
            (string)($data['primary_contact'] ?? '')
        );

        $phone = self::htmlValueOrDash(
            (string)($data['phone'] ?? '')
        );

        $quantity = self::htmlValueOrDash(
            (string)($data['quantity_requested'] ?? '')
        );

        $message = self::htmlValueOrDash(
            (string)($data['message'] ?? '')
        );

        $urlLine = '—';

        if ($productUrl !== '') {
            $escapedUrl = self::htmlValue(
                $productUrl
            );

            $urlLine = '<a href="'
                . $escapedUrl
                . '">'
                . $escapedUrl
                . '</a>';
        }

        $lines = [
            $profile['header_icon']
                . ' <b>Нова заявка ForPrint</b>',
            '',
            '📨 <b>Канал:</b> '
                . self::htmlValue($modeLabel),
            '🧾 <b>Товар:</b> '
                . (
                    $productName !== ''
                        ? $productName
                        : '—'
                ),
            '🔗 <b>Посилання:</b> '
                . $urlLine,
            '',
            '👤 <b>Основний контакт:</b> '
                . $primaryContact,
            '📞 <b>Телефон:</b> '
                . $phone,
            '🔢 <b>Кількість:</b> '
                . $quantity,
            '',
            '💬 <b>Коментар:</b>',
            $message,
        ];

        return [
            'text' => implode("\n", $lines),
            'parse_mode' => 'HTML',
        ];
    }

    private static function normaliseOrigin(
        string $origin
    ): string {
        $origin = rtrim(
            trim($origin),
            '/'
        );

        return self::isHttpUrl($origin)
            ? $origin
            : '';
    }

    private static function originFromServer(
        array $server
    ): string {
        $host = trim(
            (string)($server['HTTP_HOST'] ?? '')
        );

        if (
            $host === ''
            || preg_match(
                '/^[A-Za-z0-9.-]+(?::[0-9]{1,5})?$/D',
                $host
            ) !== 1
        ) {
            return '';
        }

        $https = strtolower(
            (string)($server['HTTPS'] ?? '')
        );

        $scheme = (
            $https !== ''
            && $https !== 'off'
            && $https !== '0'
        )
            || (string)($server['SERVER_PORT'] ?? '')
                === '443'
            ? 'https'
            : 'http';

        return $scheme . '://' . $host;
    }

    private static function safeHttpUrl(
        string $value
    ): string {
        $value = trim($value);

        return self::isHttpUrl($value)
            ? $value
            : '';
    }

    private static function isHttpUrl(
        string $value
    ): bool {
        if (
            filter_var(
                $value,
                FILTER_VALIDATE_URL
            ) === false
        ) {
            return false;
        }

        $scheme = strtolower(
            (string)parse_url(
                $value,
                PHP_URL_SCHEME
            )
        );

        return in_array(
            $scheme,
            ['http', 'https'],
            true
        );
    }

    private static function htmlValueOrDash(
        string $value
    ): string {
        $value = trim($value);

        return $value !== ''
            ? self::htmlValue($value)
            : '—';
    }

    private static function htmlValue(
        string $value
    ): string {
        return htmlspecialchars(
            trim($value),
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );
    }
}
