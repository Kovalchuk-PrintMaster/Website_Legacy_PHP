<?php

declare(strict_types=1);

/**
 * Stateless CSRF, filesystem-backed rate limiting and idempotency
 * for the public communication request endpoint.
 */
final class ForPrintCommunicationRequestSecurity
{
    private const CSRF_VERSION = 1;
    private const CSRF_TTL_SECONDS = 14400;
    private const RATE_LIMIT = 10;
    private const RATE_WINDOW_SECONDS = 600;
    private const IDEMPOTENCY_TTL_SECONDS = 1800;

    private static bool $cleanupAttempted = false;

    public static function issueCsrfToken(): string
    {
        $payload = json_encode(
            [
                'v' => self::CSRF_VERSION,
                'iat' => time(),
                'nonce' => self::base64UrlEncode(random_bytes(18)),
            ],
            JSON_UNESCAPED_SLASHES
        );

        if (!is_string($payload)) {
            throw new RuntimeException('Unable to create CSRF token.');
        }

        $encoded = self::base64UrlEncode($payload);
        $signature = hash_hmac(
            'sha256',
            $encoded,
            self::secret(),
            true
        );

        return $encoded . '.' . self::base64UrlEncode($signature);
    }

    public static function verifyCsrfToken(string $token): bool
    {
        $parts = explode('.', trim($token));

        if (count($parts) !== 2) {
            return false;
        }

        [$encoded, $encodedSignature] = $parts;
        $signature = self::base64UrlDecode($encodedSignature);

        if ($signature === null) {
            return false;
        }

        $expected = hash_hmac(
            'sha256',
            $encoded,
            self::secret(),
            true
        );

        if (!hash_equals($expected, $signature)) {
            return false;
        }

        $payload = self::base64UrlDecode($encoded);

        if ($payload === null) {
            return false;
        }

        $data = json_decode($payload, true);

        if (!is_array($data)) {
            return false;
        }

        $version = (int)($data['v'] ?? 0);
        $issuedAt = (int)($data['iat'] ?? 0);
        $nonce = (string)($data['nonce'] ?? '');
        $now = time();

        if ($version !== self::CSRF_VERSION) {
            return false;
        }

        if ($issuedAt < ($now - self::CSRF_TTL_SECONDS)) {
            return false;
        }

        if ($issuedAt > ($now + 300)) {
            return false;
        }

        return preg_match(
            '/^[A-Za-z0-9_-]{20,64}$/',
            $nonce
        ) === 1;
    }

    public static function issueIdempotencyKey(): string
    {
        return self::base64UrlEncode(random_bytes(24));
    }

    public static function isValidIdempotencyKey(string $key): bool
    {
        return preg_match(
            '/^[A-Za-z0-9_-]{20,128}$/',
            trim($key)
        ) === 1;
    }

    public static function checkRateLimit(): array
    {
        self::maybeCleanup();

        $now = time();
        $windowStart = $now - self::RATE_WINDOW_SECONDS;
        $path = self::runtimePath(
            'rate_'
            . hash_hmac(
                'sha256',
                self::clientFingerprint(),
                self::secret()
            )
            . '.json'
        );

        $handle = fopen($path, 'c+');

        if ($handle === false) {
            throw new RuntimeException(
                'Communication rate-limit storage unavailable.'
            );
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                throw new RuntimeException(
                    'Communication rate-limit lock unavailable.'
                );
            }

            rewind($handle);
            $raw = stream_get_contents($handle);
            $decoded = is_string($raw)
                ? json_decode($raw, true)
                : null;
            $timestamps = is_array($decoded)
                ? ($decoded['timestamps'] ?? [])
                : [];

            $timestamps = array_values(
                array_filter(
                    is_array($timestamps) ? $timestamps : [],
                    static fn ($value): bool =>
                        is_int($value)
                        && $value >= $windowStart
                        && $value <= ($now + 300)
                )
            );

            if (count($timestamps) >= self::RATE_LIMIT) {
                sort($timestamps);
                $retryAfter = max(
                    1,
                    ((int)$timestamps[0]
                        + self::RATE_WINDOW_SECONDS)
                    - $now
                );

                flock($handle, LOCK_UN);

                return [
                    'allowed' => false,
                    'retry_after' => $retryAfter,
                    'limit' => self::RATE_LIMIT,
                    'window' => self::RATE_WINDOW_SECONDS,
                ];
            }

            $timestamps[] = $now;
            rewind($handle);
            ftruncate($handle, 0);
            fwrite(
                $handle,
                json_encode(
                    ['timestamps' => $timestamps],
                    JSON_UNESCAPED_SLASHES
                )
            );
            fflush($handle);
            flock($handle, LOCK_UN);

            return [
                'allowed' => true,
                'retry_after' => 0,
                'limit' => self::RATE_LIMIT,
                'window' => self::RATE_WINDOW_SECONDS,
            ];
        } finally {
            fclose($handle);
        }
    }

    public static function beginIdempotentRequest(
        string $key
    ): array {
        self::maybeCleanup();

        $key = trim($key);

        if (!self::isValidIdempotencyKey($key)) {
            return ['state' => 'invalid'];
        }

        $path = self::runtimePath(
            'idem_'
            . hash_hmac('sha256', $key, self::secret())
            . '.json'
        );
        $now = time();

        for ($attempt = 0; $attempt < 2; $attempt++) {
            $handle = @fopen($path, 'x');

            if ($handle !== false) {
                $record = [
                    'state' => 'pending',
                    'created_at' => $now,
                    'expires_at' => $now
                        + self::IDEMPOTENCY_TTL_SECONDS,
                ];

                fwrite(
                    $handle,
                    json_encode(
                        $record,
                        JSON_UNESCAPED_UNICODE
                        | JSON_UNESCAPED_SLASHES
                    )
                );
                fclose($handle);

                return [
                    'state' => 'new',
                    'path' => $path,
                ];
            }

            $record = self::readJsonFile($path);
            $expiresAt = (int)($record['expires_at'] ?? 0);

            if ($expiresAt > 0 && $expiresAt < $now) {
                @unlink($path);
                continue;
            }

            if (
                ($record['state'] ?? '') === 'completed'
                && is_array($record['response'] ?? null)
            ) {
                return [
                    'state' => 'completed',
                    'response' => $record['response'],
                ];
            }

            return ['state' => 'pending'];
        }

        return ['state' => 'pending'];
    }

    public static function completeIdempotentRequest(
        array $reservation,
        array $response
    ): void {
        $path = (string)($reservation['path'] ?? '');

        if (($reservation['state'] ?? '') !== 'new' || $path === '') {
            return;
        }

        $safeResponse = [
            'ok' => (bool)($response['ok'] ?? true),
            'message' => (string)($response['message'] ?? ''),
            'request_id' => (int)($response['request_id'] ?? 0),
            'delivery_status' => (string)(
                $response['delivery_status'] ?? ''
            ),
            'delivery_completed' => (bool)(
                $response['delivery_completed'] ?? false
            ),
        ];

        $record = [
            'state' => 'completed',
            'created_at' => time(),
            'expires_at' => time()
                + self::IDEMPOTENCY_TTL_SECONDS,
            'response' => $safeResponse,
        ];

        $handle = @fopen($path, 'c+');

        if ($handle === false) {
            return;
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                return;
            }

            rewind($handle);
            ftruncate($handle, 0);
            fwrite(
                $handle,
                json_encode(
                    $record,
                    JSON_UNESCAPED_UNICODE
                    | JSON_UNESCAPED_SLASHES
                )
            );
            fflush($handle);
            flock($handle, LOCK_UN);
        } finally {
            fclose($handle);
        }
    }

    public static function releaseIdempotentRequest(
        array $reservation
    ): void {
        $path = (string)($reservation['path'] ?? '');

        if (($reservation['state'] ?? '') === 'new' && $path !== '') {
            @unlink($path);
        }
    }

    private static function maybeCleanup(): void
    {
        if (self::$cleanupAttempted) {
            return;
        }

        self::$cleanupAttempted = true;

        try {
            if (random_int(1, 100) !== 1) {
                return;
            }
        } catch (Throwable $e) {
            return;
        }

        $directory = self::runtimeDirectory();
        $cutoff = time() - 86400;
        $checked = 0;

        foreach (['rate_*.json', 'idem_*.json'] as $pattern) {
            $paths = glob($directory . '/' . $pattern);

            if (!is_array($paths)) {
                continue;
            }

            foreach ($paths as $path) {
                $checked++;

                if ($checked > 500) {
                    return;
                }

                $modified = @filemtime($path);

                if (is_int($modified) && $modified < $cutoff) {
                    @unlink($path);
                }
            }
        }
    }

    private static function secret(): string
    {
        $environmentSecret = trim((string)getenv(
            'FP_WEB_COMMUNICATION_SECURITY_SECRET'
        ));

        if ($environmentSecret !== '') {
            return hash('sha256', $environmentSecret, true);
        }

        if (
            defined('HOST')
            && defined('USER')
            && defined('PASSWORD')
            && defined('DB_NAME')
        ) {
            $material = implode(
                "\0",
                [
                    (string)HOST,
                    (string)USER,
                    (string)PASSWORD,
                    (string)DB_NAME,
                    'forprint-communication-security-v1',
                ]
            );

            return hash('sha256', $material, true);
        }

        throw new RuntimeException(
            'Communication security secret is unavailable.'
        );
    }

    private static function runtimeDirectory(): string
    {
        $configured = trim((string)getenv(
            'FP_WEB_COMMUNICATION_SECURITY_DIR'
        ));

        if ($configured !== '' && str_starts_with($configured, '/')) {
            $directory = rtrim($configured, '/');
        } else {
            $scope = substr(
                hash('sha256', dirname(__DIR__)),
                0,
                16
            );
            $directory = rtrim(sys_get_temp_dir(), '/')
                . '/forprint-communication-security-'
                . $scope;
        }

        if (
            !is_dir($directory)
            && !mkdir($directory, 0700, true)
            && !is_dir($directory)
        ) {
            throw new RuntimeException(
                'Communication security directory unavailable.'
            );
        }

        @chmod($directory, 0700);

        if (!is_writable($directory)) {
            throw new RuntimeException(
                'Communication security directory is not writable.'
            );
        }

        return $directory;
    }

    private static function runtimePath(string $name): string
    {
        return self::runtimeDirectory() . '/' . $name;
    }

    private static function clientFingerprint(): string
    {
        $address = trim((string)(
            $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ));
        $agent = substr(
            trim((string)(
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            )),
            0,
            255
        );

        return $address . "\0" . $agent;
    }

    private static function readJsonFile(string $path): array
    {
        $raw = @file_get_contents($path);

        if (!is_string($raw) || $raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    private static function base64UrlEncode(string $value): string
    {
        return rtrim(
            strtr(base64_encode($value), '+/', '-_'),
            '='
        );
    }

    private static function base64UrlDecode(
        string $value
    ): ?string {
        if (
            $value === ''
            || preg_match('/^[A-Za-z0-9_-]+$/', $value) !== 1
        ) {
            return null;
        }

        $padding = strlen($value) % 4;

        if ($padding > 0) {
            $value .= str_repeat('=', 4 - $padding);
        }

        $decoded = base64_decode(
            strtr($value, '-_', '+/'),
            true
        );

        return is_string($decoded) ? $decoded : null;
    }
}
