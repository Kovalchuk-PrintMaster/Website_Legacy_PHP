<?php
declare(strict_types=1);

final class ForPrintSlug
{
    public static function uk(string $value, string $fallback = 'item'): string
    {
        $value = trim(mb_strtolower($value, 'UTF-8'));

        if ($value === '') {
            return self::normalizeAsciiFallback($fallback);
        }

        $baseMap = [
            'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'h', 'ґ' => 'g',
            'д' => 'd', 'е' => 'e', 'ж' => 'zh', 'з' => 'z', 'и' => 'y',
            'і' => 'i', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n',
            'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't',
            'у' => 'u', 'ф' => 'f', 'х' => 'kh', 'ц' => 'ts', 'ч' => 'ch',
            'ш' => 'sh', 'щ' => 'shch', 'ь' => '',
        ];

        $contextMap = [
            'є' => ['ye', 'ie'],
            'ї' => ['yi', 'i'],
            'й' => ['y', 'i'],
            'ю' => ['yu', 'iu'],
            'я' => ['ya', 'ia'],
        ];

        $tokens = preg_split(
            '~[^\p{L}\p{N}\x{0027}\x{0060}\x{02BC}\x{2019}]+~u',
            $value,
            -1,
            PREG_SPLIT_NO_EMPTY
        );

        $slugTokens = [];

        foreach ($tokens ?: [] as $token) {
            $chars = preg_split('//u', $token, -1, PREG_SPLIT_NO_EMPTY) ?: [];
            $out = '';
            $logicalPosition = 0;
            $count = count($chars);

            for ($index = 0; $index < $count; $index++) {
                $char = $chars[$index];

                if (in_array($char, ["'", '`', 'ʼ', '’'], true)) {
                    continue;
                }

                if (
                    $char === 'з'
                    && $index + 1 < $count
                    && $chars[$index + 1] === 'г'
                ) {
                    $out .= 'zgh';
                    $index++;
                    $logicalPosition += 2;
                    continue;
                }

                if (isset($baseMap[$char])) {
                    $out .= $baseMap[$char];
                    $logicalPosition++;
                    continue;
                }

                if (isset($contextMap[$char])) {
                    $out .= $contextMap[$char][$logicalPosition === 0 ? 0 : 1];
                    $logicalPosition++;
                    continue;
                }

                if (preg_match('/^[a-z0-9]$/', $char)) {
                    $out .= $char;
                    $logicalPosition++;
                }
            }

            $out = preg_replace('~[^a-z0-9]+~', '-', $out) ?: '';
            $out = trim($out, '-');

            if ($out !== '') {
                $slugTokens[] = $out;
            }
        }

        $slug = preg_replace('~-{2,}~', '-', implode('-', $slugTokens)) ?: '';
        $slug = trim($slug, '-');

        return $slug !== ''
            ? $slug
            : self::normalizeAsciiFallback($fallback);
    }

    private static function normalizeAsciiFallback(string $fallback): string
    {
        $fallback = strtolower(trim($fallback));
        $fallback = preg_replace('~[^a-z0-9]+~', '-', $fallback) ?: '';
        $fallback = trim($fallback, '-');

        return $fallback !== '' ? $fallback : 'item';
    }
}
