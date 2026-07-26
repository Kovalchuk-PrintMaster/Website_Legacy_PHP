<?php

declare(strict_types=1);

namespace libraries;

final class MediaProcessingSettings
{
    private string $storagePath;

    /** @var array<string, int|string> */
    private array $defaults = [
        'jpeg_quality' => 94,
        'jpeg_min_quality' => 94,
        'entity_max_edge' => 1600,
        'entity_max_kb' => 5120,
        'news_max_edge' => 1600,
        'news_max_kb' => 5120,
        'slider_max_edge' => 1600,
        'slider_max_kb' => 5120,
        'settings_max_edge' => 1600,
        'settings_max_kb' => 5120,
        'social_max_edge' => 768,
        'social_max_kb' => 800,
        'png_palette_mode' => 'quality',
    ];

    // FP_MEDIA_BUDGET_RANGE_FIX_05D1_6B
    /** @var array<string, array{min: int, max: int}> */
    private array $integerRanges = [
        'jpeg_quality' => ['min' => 80, 'max' => 98],
        'jpeg_min_quality' => ['min' => 72, 'max' => 96],
        'entity_max_edge' => ['min' => 800, 'max' => 2800],
        'entity_max_kb' => ['min' => 400, 'max' => 5120],
        'news_max_edge' => ['min' => 1000, 'max' => 3200],
        'news_max_kb' => ['min' => 500, 'max' => 5120],
        'slider_max_edge' => ['min' => 1200, 'max' => 3840],
        'slider_max_kb' => ['min' => 700, 'max' => 5120],
        'settings_max_edge' => ['min' => 1000, 'max' => 3840],
        'settings_max_kb' => ['min' => 500, 'max' => 5120],
        'social_max_edge' => ['min' => 128, 'max' => 1200],
        'social_max_kb' => ['min' => 100, 'max' => 1536],
    ];

    /** @var array<string, list<int>> */
    private array $paletteModes = [
        'quality' => [256, 240, 224, 208, 192],
        'balanced' => [256, 224, 192, 160],
        'compact' => [224, 192, 160, 128],
    ];

    public function __construct(?string $storagePath = null)
    {
        $environmentPath = getenv(
            'FP_MEDIA_PROCESSING_SETTINGS_PATH'
        );

        $this->storagePath = $storagePath
            ?? (
                is_string($environmentPath)
                && trim($environmentPath) !== ''
                    ? $environmentPath
                    : dirname(__DIR__, 2)
                        . '/var/media_processing/settings.json'
            );
    }

    /** @return array<string, int|string> */
    public function all(): array
    {
        if (!is_file($this->storagePath)) {
            return $this->defaults;
        }

        $raw = @file_get_contents($this->storagePath);

        if (!is_string($raw) || trim($raw) === '') {
            return $this->defaults;
        }

        $decoded = json_decode($raw, true);

        if (!is_array($decoded)) {
            return $this->defaults;
        }

        return $this->normalize($decoded, false)['values'];
    }

    /** @return list<int> */
    public function pngPaletteSteps(): array
    {
        $mode = (string)($this->all()['png_palette_mode'] ?? 'quality');

        return $this->paletteModes[$mode]
            ?? $this->paletteModes['quality'];
    }

    public function storagePath(): string
    {
        return $this->storagePath;
    }

    public function ensureCsrfToken(): string
    {
        if (
            empty($_SESSION['fp_media_processing_csrf'])
            || !is_string($_SESSION['fp_media_processing_csrf'])
        ) {
            $_SESSION['fp_media_processing_csrf'] =
                bin2hex(random_bytes(32));
        }

        return $_SESSION['fp_media_processing_csrf'];
    }

    /**
     * @param array<string, mixed> $input
     * @return array{values: array<string, int|string>, errors: list<string>}
     */
    public function saveFromAdmin(
        array $input,
        string $csrfToken
    ): array {
        $expected = $this->ensureCsrfToken();

        if (
            $csrfToken === ''
            || !hash_equals($expected, $csrfToken)
        ) {
            return [
                'values' => $this->all(),
                'errors' => [
                    'Налаштування зображень не збережено: '
                    . 'перевірка запиту не пройдена. '
                    . 'Оновіть сторінку та повторіть спробу.',
                ],
            ];
        }

        $normalized = $this->normalize($input, true);

        if ($normalized['errors'] !== []) {
            return $normalized;
        }

        try {
            $this->writeAtomic($normalized['values']);
        } catch (\Throwable $error) {
            return [
                'values' => $this->all(),
                'errors' => [
                    'Налаштування зображень не збережено: '
                    . $this->safeMessage($error),
                ],
            ];
        }

        $_SESSION['fp_media_processing_csrf'] =
            bin2hex(random_bytes(32));

        return $normalized;
    }

    /**
     * Installation/runtime smoke helper with a private temporary path.
     *
     * @param array<string, mixed> $input
     * @return array{values: array<string, int|string>, errors: list<string>}
     */
    public function saveValidated(array $input): array
    {
        $normalized = $this->normalize($input, true);

        if ($normalized['errors'] === []) {
            $this->writeAtomic($normalized['values']);
        }

        return $normalized;
    }

    /**
     * @param array<string, mixed> $input
     * @return array{values: array<string, int|string>, errors: list<string>}
     */
    private function normalize(
        array $input,
        bool $strict
    ): array {
        $current = $this->defaults;
        $errors = [];

        foreach ($this->integerRanges as $key => $range) {
            $candidate = $input[$key] ?? $current[$key];

            if (
                !is_int($candidate)
                && !(
                    is_string($candidate)
                    && preg_match('/^-?\d+$/', trim($candidate))
                )
            ) {
                if ($strict) {
                    $errors[] =
                        'Поле «'
                        . $this->label($key)
                        . '» повинно бути цілим числом.';
                }

                continue;
            }

            $value = (int)$candidate;

            if (
                $value < $range['min']
                || $value > $range['max']
            ) {
                if ($strict) {
                    $errors[] =
                        'Поле «'
                        . $this->label($key)
                        . '» має бути в межах '
                        . $range['min']
                        . '–'
                        . $range['max']
                        . '.';
                }

                $value = max(
                    $range['min'],
                    min($range['max'], $value)
                );
            }

            $current[$key] = $value;
        }

        $palette = (string)(
            $input['png_palette_mode']
            ?? $current['png_palette_mode']
        );

        if (!array_key_exists($palette, $this->paletteModes)) {
            if ($strict) {
                $errors[] =
                    'Оберіть дозволений режим палітри PNG.';
            }

            $palette = (string)$this->defaults['png_palette_mode'];
        }

        $current['png_palette_mode'] = $palette;

        if (
            (int)$current['jpeg_min_quality']
            > (int)$current['jpeg_quality']
        ) {
            if ($strict) {
                $errors[] =
                    'Мінімальна якість JPEG не може бути '
                    . 'вищою за початкову.';
            }

            $current['jpeg_min_quality'] =
                (int)$current['jpeg_quality'];
        }

        return [
            'values' => $current,
            'errors' => $errors,
        ];
    }

    /** @param array<string, int|string> $values */
    private function writeAtomic(array $values): void
    {
        $directory = dirname($this->storagePath);

        if (
            !is_dir($directory)
            && !mkdir($directory, 0770, true)
            && !is_dir($directory)
        ) {
            throw new \RuntimeException(
                'не вдалося створити runtime-директорію'
            );
        }

        $json = json_encode(
            $values,
            JSON_PRETTY_PRINT
            | JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
        );

        if (!is_string($json)) {
            throw new \RuntimeException(
                'не вдалося сформувати JSON'
            );
        }

        $temporary = $this->storagePath
            . '.tmp-'
            . bin2hex(random_bytes(6));

        try {
            if (
                file_put_contents(
                    $temporary,
                    $json . PHP_EOL,
                    LOCK_EX
                ) === false
            ) {
                throw new \RuntimeException(
                    'не вдалося записати тимчасовий файл'
                );
            }

            @chmod($temporary, 0660);

            if (!@rename($temporary, $this->storagePath)) {
                throw new \RuntimeException(
                    'не вдалося атомарно встановити налаштування'
                );
            }
        } finally {
            if (is_file($temporary)) {
                @unlink($temporary);
            }
        }
    }

    private function label(string $key): string
    {
        $labels = [
            'jpeg_quality' => 'Якість JPEG',
            'jpeg_min_quality' => 'Мінімальна якість JPEG',
            'entity_max_edge' => 'Картки: максимальна сторона',
            'entity_max_kb' => 'Картки: максимальна вага',
            'news_max_edge' => 'Новини: максимальна сторона',
            'news_max_kb' => 'Новини: максимальна вага',
            'slider_max_edge' => 'Слайдер: максимальна сторона',
            'slider_max_kb' => 'Слайдер: максимальна вага',
            'settings_max_edge' => 'Системні зображення: сторона',
            'settings_max_kb' => 'Системні зображення: вага',
            'social_max_edge' => 'Іконки: максимальна сторона',
            'social_max_kb' => 'Іконки: максимальна вага',
        ];

        return $labels[$key] ?? $key;
    }

    private function safeMessage(\Throwable $error): string
    {
        $message = trim($error->getMessage());

        if ($message === '') {
            return 'невідома помилка запису';
        }

        return preg_replace(
            '/[\r\n\t]+/',
            ' ',
            $message
        ) ?? 'невідома помилка запису';
    }
}
