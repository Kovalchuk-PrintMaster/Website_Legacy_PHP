<?php

declare(strict_types=1);

/**
 * Canonical international phone validation for the product communication form.
 *
 * Statuses:
 * - empty: no phone supplied;
 * - valid: libphonenumber recognizes the number pattern;
 * - unusual: syntax is plausible, but metadata does not confirm the number;
 * - invalid: malformed syntax or impossible basic length.
 */
final class ForPrintInternationalPhoneValidator
{
    public const STATUS_EMPTY = 'empty';
    public const STATUS_VALID = 'valid';
    public const STATUS_UNUSUAL = 'unusual';
    public const STATUS_INVALID = 'invalid';

    private const MIN_DIGITS = 7;
    private const MAX_DIGITS = 15;

    /**
     * @return array{
     *     status:string,
     *     raw:string,
     *     normalized:string,
     *     possible:bool,
     *     valid:bool,
     *     message:string
     * }
     */
    public static function classify(
        string $phone,
        string $defaultRegion = 'UA'
    ): array {
        self::loadComposer();

        $raw = trim(str_replace("\xc2\xa0", ' ', $phone));

        if ($raw === '') {
            return self::result(self::STATUS_EMPTY, '', '', false, false, '');
        }

        if (!preg_match('/^[0-9+\s().\-\/]+$/u', $raw)) {
            return self::invalid(
                $raw,
                'У номері телефону дозволені цифри, пробіли, дужки, дефіси, коса риска та один знак «+» на початку.'
            );
        }

        $plusCount = substr_count($raw, '+');

        if ($plusCount > 1 || ($plusCount === 1 && !str_starts_with($raw, '+'))) {
            return self::invalid(
                $raw,
                'Знак «+» у номері телефону можна використати лише один раз і тільки на початку.'
            );
        }

        $prepared = preg_replace('/[\s().\-\/]+/u', '', $raw);

        if (!is_string($prepared) || $prepared === '' || $prepared === '+') {
            return self::invalid($raw, 'Вкажіть повний номер телефону.');
        }

        if (str_starts_with($prepared, '00')) {
            $prepared = '+' . substr($prepared, 2);
        } elseif (
            !str_starts_with($prepared, '+')
            && preg_match('/^380\d{9}$/', $prepared)
        ) {
            $prepared = '+' . $prepared;
        }

        $digits = preg_replace('/\D+/', '', $prepared);

        if (!is_string($digits)) {
            return self::invalid($raw, 'Не вдалося прочитати номер телефону.');
        }

        $digitCount = strlen($digits);

        if ($digitCount < self::MIN_DIGITS || $digitCount > self::MAX_DIGITS) {
            return self::invalid(
                $raw,
                'Номер телефону має містити від 7 до 15 цифр.'
            );
        }

        if (str_starts_with($prepared, '+') && str_starts_with($digits, '0')) {
            return self::invalid(
                $raw,
                'Після знака «+» міжнародний код країни не може починатися з нуля.'
            );
        }

        $explicitInternational = str_starts_with($prepared, '+');
        $fallback = $explicitInternational
            ? '+' . $digits
            : $prepared;

        $repeatedDigits = preg_match('/^(\d)\1{6,}$/', $digits) === 1;

        try {
            $phoneUtil = \libphonenumber\PhoneNumberUtil::getInstance();
            $phoneObject = $phoneUtil->parse(
                $prepared,
                $explicitInternational ? null : strtoupper($defaultRegion)
            );

            $possible = $phoneUtil->isPossibleNumber($phoneObject);
            $valid = $phoneUtil->isValidNumber($phoneObject);
            $e164 = $phoneUtil->format(
                $phoneObject,
                \libphonenumber\PhoneNumberFormat::E164
            );

            if ($valid && !$repeatedDigits) {
                return self::result(
                    self::STATUS_VALID,
                    $raw,
                    $e164,
                    $possible,
                    true,
                    ''
                );
            }

            return self::result(
                self::STATUS_UNUSUAL,
                $raw,
                $possible ? $e164 : $fallback,
                $possible,
                false,
                self::unusualMessage()
            );
        } catch (\libphonenumber\NumberParseException) {
            return self::result(
                self::STATUS_UNUSUAL,
                $raw,
                $fallback,
                false,
                false,
                self::unusualMessage()
            );
        }
    }

    private static function loadComposer(): void
    {
        if (class_exists(\libphonenumber\PhoneNumberUtil::class)) {
            return;
        }

        $autoload = dirname(__DIR__) . '/vendor/autoload.php';

        if (!is_file($autoload)) {
            throw new RuntimeException(
                'Composer autoload.php is missing for international phone validation.'
            );
        }

        require_once $autoload;

        if (!class_exists(\libphonenumber\PhoneNumberUtil::class)) {
            throw new RuntimeException(
                'libphonenumber PhoneNumberUtil is not available.'
            );
        }
    }

    private static function unusualMessage(): string
    {
        return 'Введений номер виглядає незвично або не відповідає відомому міжнародному формату. Перевірте його. Якщо номер правильний, натисніть «Відправити запит» ще раз.';
    }

    /**
     * @return array{
     *     status:string,
     *     raw:string,
     *     normalized:string,
     *     possible:bool,
     *     valid:bool,
     *     message:string
     * }
     */
    private static function invalid(string $raw, string $message): array
    {
        return self::result(
            self::STATUS_INVALID,
            $raw,
            '',
            false,
            false,
            $message
        );
    }

    /**
     * @return array{
     *     status:string,
     *     raw:string,
     *     normalized:string,
     *     possible:bool,
     *     valid:bool,
     *     message:string
     * }
     */
    private static function result(
        string $status,
        string $raw,
        string $normalized,
        bool $possible,
        bool $valid,
        string $message
    ): array {
        return [
            'status' => $status,
            'raw' => $raw,
            'normalized' => $normalized,
            'possible' => $possible,
            'valid' => $valid,
            'message' => $message,
        ];
    }
}
