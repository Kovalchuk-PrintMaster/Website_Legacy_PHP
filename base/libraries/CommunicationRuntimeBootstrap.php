<?php
declare(strict_types=1);

defined('VG_ACCESS') or die('Access denied');

if (!function_exists('fp_load_communication_runtime')) {
    /**
     * Load production-only communication settings without making
     * hosting the application source of truth.
     *
     * Existing process environment values win. The private runtime
     * file is optional locally and expected on hosting.
     */
    function fp_load_communication_runtime(string $webroot): array
    {
        $allowed = [
            'FP_WEB_ENABLE_PHP_MAIL',
            'FP_WEB_ENABLE_SMTP',
            'FP_WEB_NOTIFICATION_THEME',
            'FP_WEB_PUBLIC_ORIGIN',
            'FP_WEB_SMTP_ENCRYPTION',
            'FP_WEB_SMTP_FROM',
            'FP_WEB_SMTP_FROM_NAME',
            'FP_WEB_SMTP_HOST',
            'FP_WEB_SMTP_PASS',
            'FP_WEB_SMTP_PORT',
            'FP_WEB_SMTP_TIMEOUT',
            'FP_WEB_SMTP_TO',
            'FP_WEB_SMTP_USER',
            'FP_WEB_TELEGRAM_BOT_TOKEN',
            'FP_WEB_TELEGRAM_CHAT_ID',
            'FP_WEB_COMMUNICATION_SECURITY_SECRET',
            'FP_WEB_COMMUNICATION_SECURITY_DIR',
        ];

        $candidates = [];
        $configured = getenv('FP_WEB_RUNTIME_CONFIG');

        if (is_string($configured) && trim($configured) !== '') {
            $candidates[] = trim($configured);
        }

        if (
            defined('FP_WEB_RUNTIME_CONFIG')
            && is_string(FP_WEB_RUNTIME_CONFIG)
            && trim(FP_WEB_RUNTIME_CONFIG) !== ''
        ) {
            $candidates[] = trim(FP_WEB_RUNTIME_CONFIG);
        }

        $normalizedWebroot = rtrim(
            $webroot,
            DIRECTORY_SEPARATOR
        );
        $candidates[] = (
            dirname($normalizedWebroot, 2)
            . DIRECTORY_SEPARATOR
            . '.forprint-secrets'
            . DIRECTORY_SEPARATOR
            . 'communication_runtime.php'
        );

        $runtimePath = null;

        foreach (array_unique($candidates) as $candidate) {
            if (
                is_string($candidate)
                && $candidate !== ''
                && is_file($candidate)
                && is_readable($candidate)
            ) {
                $runtimePath = $candidate;
                break;
            }
        }

        $loaded = 0;

        if ($runtimePath !== null) {
            $runtime = require $runtimePath;

            if (!is_array($runtime)) {
                throw new RuntimeException(
                    'Communication runtime must return an array'
                );
            }

            $allowedMap = array_fill_keys($allowed, true);

            foreach ($runtime as $name => $value) {
                $name = (string)$name;

                if (!isset($allowedMap[$name])) {
                    continue;
                }

                $current = getenv($name);

                if (
                    is_string($current)
                    && trim($current) !== ''
                ) {
                    continue;
                }

                if (is_bool($value)) {
                    $normalized = $value ? '1' : '0';
                } elseif (
                    is_string($value)
                    || is_int($value)
                    || is_float($value)
                ) {
                    $normalized = (string)$value;
                } else {
                    continue;
                }

                /* FP_COMMUNICATION_RUNTIME_BOOLEAN_NORMALIZATION_V0_1 */
                if (
                    in_array(
                        $name,
                        [
                        'FP_WEB_ENABLE_SMTP',
                        'FP_WEB_ENABLE_PHP_MAIL'
                        ],
                        true
                    )
                ) {
                    $fpRuntimeBooleanValue = strtolower(
                        trim((string) $normalized)
                    );

                    if (
                        in_array(
                            $fpRuntimeBooleanValue,
                            ['1', 'true', 'yes', 'on'],
                            true
                        )
                    ) {
                        $normalized = '1';
                    } elseif (
                        in_array(
                            $fpRuntimeBooleanValue,
                            ['0', 'false', 'no', 'off', ''],
                            true
                        )
                    ) {
                        $normalized = '0';
                    }

                    unset($fpRuntimeBooleanValue);
                }
                /* FP_COMMUNICATION_RUNTIME_BOOLEAN_NORMALIZATION_V0_1_END */

                putenv($name . '=' . $normalized);
                $_ENV[$name] = $normalized;
                $_SERVER[$name] = $normalized;
                $loaded++;
            }
        }

        return [
            'runtime_path_loaded' => $runtimePath !== null,
            'allowed_keys_loaded' => $loaded,
        ];
    }
}
