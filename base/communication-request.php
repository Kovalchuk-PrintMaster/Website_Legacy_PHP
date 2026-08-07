<?php

declare(strict_types=1);


define('VG_ACCESS', true);

/* FP_COMMUNICATION_RUNTIME_BOOTSTRAP_V0_1_START */
require_once __DIR__ . '/libraries/CommunicationRuntimeBootstrap.php';
fp_load_communication_runtime(__DIR__);
/* FP_COMMUNICATION_RUNTIME_BOOTSTRAP_V0_1_END */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');
header('X-Robots-Tag: noindex, nofollow, noarchive');

require_once __DIR__ . '/libraries/InternationalPhoneValidator.php';
require_once __DIR__ . '/libraries/CommunicationRequestSecurity.php';
require_once __DIR__ . '/libraries/CommunicationRequestMessageFormatter.php';

function fp_comm_response(bool $ok, string $message, array $extra = []): void
{
    echo json_encode(
        array_merge(['ok' => $ok, 'message' => $message], $extra),
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
}

function fp_comm_post(string $key, int $limit = 1000): string
{
    $raw = $_POST[$key] ?? '';

    if (!is_scalar($raw)) {
        return '';
    }

    $value = trim((string)$raw);

    $length = function_exists('mb_strlen')
        ? mb_strlen($value)
        : strlen($value);

    if ($length > $limit) {
        $value = function_exists('mb_substr')
            ? mb_substr($value, 0, $limit)
            : substr($value, 0, $limit);
    }

    return $value;
}


function fp_comm_plain_message(array $data): string
{
    $lines = [
        'Нова заявка з сайту ForPrint',
        '',
        'Тип: ' . $data['mode'],
        'Товар: ' . $data['product_name'],
        'URL: ' . $data['product_url'],
        '',
        'Основний контакт: ' . ($data['primary_contact'] !== '' ? $data['primary_contact'] : '-'),
        'Телефон: ' . ($data['phone'] !== '' ? $data['phone'] : '-'),
        'Кількість: ' . ($data['quantity_requested'] !== '' ? $data['quantity_requested'] : '-'),
        '',
        'Коментар:',
        $data['message'] !== '' ? $data['message'] : '-',
    ];

    return implode("\n", $lines);
}


function fp_comm_smtp_read($socket): array
{
    $response = '';

    while (($line = fgets($socket, 515)) !== false) {
        $response .= $line;

        if (strlen($line) >= 4 && $line[3] === ' ') {
            break;
        }
    }

    $code = (int)substr($response, 0, 3);

    return [$code, $response];
}

function fp_comm_smtp_expect($socket, array $expected, string $context): string
{
    [$code, $response] = fp_comm_smtp_read($socket);

    if (!in_array($code, $expected, true)) {
        throw new RuntimeException($context . ' failed with SMTP code ' . $code . ': ' . trim($response));
    }

    return $response;
}

function fp_comm_smtp_command($socket, string $command, array $expected, string $context): string
{
    fwrite($socket, $command . "\r\n");

    return fp_comm_smtp_expect($socket, $expected, $context);
}

function fp_comm_smtp_addr(string $email): string
{
    $email = trim($email);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('Invalid email address: ' . $email);
    }

    return $email;
}

function fp_comm_smtp_header_text(string $text): string
{
    $text = trim($text);

    if ($text === '') {
        return '';
    }

    return '=?UTF-8?B?' . base64_encode($text) . '?=';
}

function fp_comm_smtp_send_message(string $to, string $subject, string $body): bool
{
    $host = trim((string)getenv('FP_WEB_SMTP_HOST'));
    $port = (int)(getenv('FP_WEB_SMTP_PORT') ?: 25);
    $encryption = strtolower(trim((string)(getenv('FP_WEB_SMTP_ENCRYPTION') ?: 'starttls')));
    $user = trim((string)getenv('FP_WEB_SMTP_USER'));
    $pass = (string)getenv('FP_WEB_SMTP_PASS');
    $from = trim((string)(getenv('FP_WEB_SMTP_FROM') ?: $user));
    $fromName = trim((string)(getenv('FP_WEB_SMTP_FROM_NAME') ?: 'ForPrint Website'));

    if ($host === '' || $user === '' || $pass === '' || $from === '') {
        throw new RuntimeException('SMTP env is incomplete');
    }

    $to = fp_comm_smtp_addr($to);
    $from = fp_comm_smtp_addr($from);

    $transport = $encryption === 'ssl'
        ? 'ssl://' . $host . ':' . $port
        : 'tcp://' . $host . ':' . $port;

    $timeout = max(3, min(20, (int)(getenv('FP_WEB_SMTP_TIMEOUT') ?: 7)));

    $socket = @stream_socket_client(
        $transport,
        $errno,
        $errstr,
        $timeout,
        STREAM_CLIENT_CONNECT
    );

    if (!$socket) {
        throw new RuntimeException('SMTP connect failed: ' . $errstr . ' (' . $errno . ')');
    }

    stream_set_timeout($socket, $timeout);

    try {
        fp_comm_smtp_expect($socket, [220], 'SMTP greeting');

        $ehloName = gethostname() ?: 'localhost';
        fp_comm_smtp_command($socket, 'EHLO ' . $ehloName, [250], 'SMTP EHLO');

        if ($encryption === 'starttls') {
            fp_comm_smtp_command($socket, 'STARTTLS', [220], 'SMTP STARTTLS');

            $cryptoOk = @stream_socket_enable_crypto(
                $socket,
                true,
                STREAM_CRYPTO_METHOD_TLS_CLIENT
            );

            if ($cryptoOk !== true) {
                throw new RuntimeException('SMTP STARTTLS crypto negotiation failed');
            }

            fp_comm_smtp_command($socket, 'EHLO ' . $ehloName, [250], 'SMTP EHLO after STARTTLS');
        }

        fp_comm_smtp_command($socket, 'AUTH LOGIN', [334], 'SMTP AUTH LOGIN');
        fp_comm_smtp_command($socket, base64_encode($user), [334], 'SMTP AUTH username');
        fp_comm_smtp_command($socket, base64_encode($pass), [235], 'SMTP AUTH password');

        fp_comm_smtp_command($socket, 'MAIL FROM:<' . $from . '>', [250], 'SMTP MAIL FROM');
        fp_comm_smtp_command($socket, 'RCPT TO:<' . $to . '>', [250, 251], 'SMTP RCPT TO');
        fp_comm_smtp_command($socket, 'DATA', [354], 'SMTP DATA');

        $encodedSubject = fp_comm_smtp_header_text($subject);
        $encodedFromName = fp_comm_smtp_header_text($fromName);

        $headers = [
            'Date: ' . date(DATE_RFC2822),
            'From: ' . ($encodedFromName !== '' ? $encodedFromName . ' ' : '') . '<' . $from . '>',
            'To: <' . $to . '>',
            'Subject: ' . $encodedSubject,
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: quoted-printable',
        ];

        $message = implode("\r\n", $headers)
            . "\r\n\r\n"
            . quoted_printable_encode($body);

        $message = str_replace(["\r\n", "\r"], "\n", $message);
        $message = str_replace("\n.", "\n..", $message);
        $message = str_replace("\n", "\r\n", $message);

        fwrite($socket, $message . "\r\n.\r\n");
        fp_comm_smtp_expect($socket, [250], 'SMTP message body');

        @fwrite($socket, "QUIT\r\n");
        fclose($socket);

        return true;
    } catch (Throwable $e) {
        @fwrite($socket, "QUIT\r\n");
        fclose($socket);
        throw $e;
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    fp_comm_response(false, 'Метод не підтримується.');
}

require_once __DIR__ . '/config.php';

if (
    !defined('HOST') ||
    !defined('USER') ||
    !defined('PASSWORD') ||
    !defined('DB_NAME')
) {
    http_response_code(500);
    fp_comm_response(false, 'Налаштування сервісу недоступні.');
}

if (function_exists('mysqli_report')) {
    mysqli_report(MYSQLI_REPORT_OFF);
}

try {
    $csrfValid = ForPrintCommunicationRequestSecurity::verifyCsrfToken(
        fp_comm_post('csrf_token', 2048)
    );
} catch (Throwable $e) {
    error_log(
        'ForPrint communication security configuration error ['
        . get_class($e)
        . ']'
    );
    http_response_code(500);
    fp_comm_response(false, 'Сервіс тимчасово недоступний.');
}

if (!$csrfValid) {
    http_response_code(403);
    fp_comm_response(
        false,
        'Сесію форми завершено. Оновіть сторінку та повторіть спробу.'
    );
}

if (fp_comm_post('fp_request_company_url_confirm', 255) !== '') {
    fp_comm_response(true, 'Заявку прийнято.');
}

try {
    $rateLimit = ForPrintCommunicationRequestSecurity::checkRateLimit();
} catch (Throwable $e) {
    error_log(
        'ForPrint communication rate-limit error ['
        . get_class($e)
        . ']'
    );
    http_response_code(500);
    fp_comm_response(false, 'Сервіс тимчасово недоступний.');
}

if (!$rateLimit['allowed']) {
    $retryAfter = (int)$rateLimit['retry_after'];
    http_response_code(429);
    header('Retry-After: ' . $retryAfter);
    fp_comm_response(
        false,
        'Забагато запитів. Спробуйте повторити трохи пізніше.',
        ['retry_after' => $retryAfter]
    );
}

$mode = fp_comm_post('mode', 64);

if (!in_array($mode, ['telegram', 'email'], true)) {
    http_response_code(422);
    fp_comm_response(false, 'Некоректний тип заявки.');
}

$data = [
    'mode' => $mode,
    'product_id' => (int)($_POST['product_id'] ?? 0),
    'product_name' => fp_comm_post('product_name', 255),
    'product_url' => fp_comm_post('product_url', 500),
    'primary_contact' => fp_comm_post('primary_contact', 255),
    'phone' => fp_comm_post('phone', 100),
    'quantity_requested' => fp_comm_post('quantity_requested', 255),
    'message' => fp_comm_post('message', 2000),
];

/* FP_COMMUNICATION_ABSOLUTE_URL_V01 */
$data['product_url'] =
    CommunicationRequestMessageFormatter::absolutePublicUrl(
        $data['product_url'],
        $_SERVER,
        (string)(getenv('FP_WEB_PUBLIC_ORIGIN') ?: '')
    );

$idempotencyKey = fp_comm_post('idempotency_key', 128);

if (
    !ForPrintCommunicationRequestSecurity::isValidIdempotencyKey(
        $idempotencyKey
    )
) {
    http_response_code(422);
    fp_comm_response(
        false,
        'Ідентифікатор форми недійсний. Оновіть сторінку.'
    );
}

$phoneRaw = $data['phone'];
$phoneConfirmed = fp_comm_post('phone_confirmed', 8) === '1';
$phoneResult = ForPrintInternationalPhoneValidator::classify($phoneRaw);

if ($phoneResult['status'] === ForPrintInternationalPhoneValidator::STATUS_INVALID) {
    http_response_code(422);
    fp_comm_response(
        false,
        $phoneResult['message'] !== ''
            ? $phoneResult['message']
            : 'Перевірте номер телефону.'
    );
}

if (
    $phoneResult['status'] === ForPrintInternationalPhoneValidator::STATUS_UNUSUAL
    && !$phoneConfirmed
) {
    http_response_code(409);
    fp_comm_response(
        false,
        $phoneResult['message'],
        [
            'phone_confirmation_required' => true,
            'phone_normalized' => $phoneResult['normalized'],
        ]
    );
}

$data['phone'] = $phoneResult['normalized'];

if ($data['primary_contact'] === '' && $data['phone'] === '') {
    http_response_code(422);
    fp_comm_response(false, 'Вкажіть хоча б один контакт для звʼязку.');
}

try {
    $idempotency = ForPrintCommunicationRequestSecurity::beginIdempotentRequest(
        $idempotencyKey
    );
} catch (Throwable $e) {
    error_log(
        'ForPrint communication idempotency error ['
        . get_class($e)
        . ']'
    );
    http_response_code(500);
    fp_comm_response(false, 'Сервіс тимчасово недоступний.');
}

if (($idempotency['state'] ?? '') === 'completed') {
    $previous = $idempotency['response'];

    fp_comm_response(
        (bool)($previous['ok'] ?? true),
        (string)($previous['message'] ?? 'Заявку вже прийнято.'),
        [
            'request_id' => (int)($previous['request_id'] ?? 0),
            'delivery_status' => (string)(
                $previous['delivery_status'] ?? ''
            ),
            'delivery_completed' => (bool)(
                $previous['delivery_completed'] ?? false
            ),
            'duplicate' => true,
        ]
    );
}

if (($idempotency['state'] ?? '') !== 'new') {
    http_response_code(409);
    fp_comm_response(
        false,
        'Цей запит уже обробляється. Зачекайте кілька секунд.',
        ['duplicate' => true]
    );
}

$db = @new mysqli(HOST, USER, PASSWORD, DB_NAME);

if ($db->connect_errno) {
    ForPrintCommunicationRequestSecurity::releaseIdempotentRequest(
        $idempotency
    );
    http_response_code(500);
    fp_comm_response(false, 'Не вдалося підключитися до бази.');
}

$db->set_charset('utf8mb4');

$requestTable = $db->query(
    "SHOW TABLES LIKE 'communication_requests'"
);
$quantityColumn = $db->query(
    "SHOW COLUMNS FROM `communication_requests` "
    . "LIKE 'quantity_requested'"
);

if (
    !$requestTable
    || $requestTable->num_rows === 0
    || !$quantityColumn
    || $quantityColumn->num_rows === 0
) {
    ForPrintCommunicationRequestSecurity::releaseIdempotentRequest(
        $idempotency
    );
    $db->close();
    error_log('ForPrint communication schema readiness check failed');
    http_response_code(500);
    fp_comm_response(false, 'Сервіс тимчасово недоступний.');
}

$stmt = $db->prepare(
    "SELECT `target` FROM `communication_buttons` "
    . "WHERE `alias` = ? AND `visible` = 1 LIMIT 1"
);

if (!$stmt) {
    ForPrintCommunicationRequestSecurity::releaseIdempotentRequest(
        $idempotency
    );
    $db->close();
    error_log('ForPrint communication target query prepare failed');
    http_response_code(500);
    fp_comm_response(false, 'Сервіс тимчасово недоступний.');
}

$stmt->bind_param('s', $mode);

if (!$stmt->execute()) {
    ForPrintCommunicationRequestSecurity::releaseIdempotentRequest(
        $idempotency
    );
    $stmt->close();
    $db->close();
    error_log('ForPrint communication target query execute failed');
    http_response_code(500);
    fp_comm_response(false, 'Сервіс тимчасово недоступний.');
}

$result = $stmt->get_result();

$target = '';
if ($result && $row = $result->fetch_assoc()) {
    $target = trim((string)($row['target'] ?? ''));
}

$deliveryStatus = 'stored';

$insert = $db->prepare("
    INSERT INTO `communication_requests` (
        `mode`,
        `product_id`,
        `product_name`,
        `product_url`,
        `primary_contact`,
        `phone`,
        `quantity_requested`,
        `message`,
        `delivery_target`,
        `delivery_status`
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

if (!$insert) {
    ForPrintCommunicationRequestSecurity::releaseIdempotentRequest(
        $idempotency
    );
    $stmt->close();
    $db->close();
    error_log('ForPrint communication insert prepare failed');
    http_response_code(500);
    fp_comm_response(false, 'Не вдалося зберегти заявку.');
}

$insert->bind_param(
    'sissssssss',
    $data['mode'],
    $data['product_id'],
    $data['product_name'],
    $data['product_url'],
    $data['primary_contact'],
    $data['phone'],
    $data['quantity_requested'],
    $data['message'],
    $target,
    $deliveryStatus
);

if (!$insert->execute() || $insert->errno) {
    ForPrintCommunicationRequestSecurity::releaseIdempotentRequest(
        $idempotency
    );
    $insert->close();
    $stmt->close();
    $db->close();
    http_response_code(500);
    fp_comm_response(false, 'Не вдалося зберегти заявку.');
}

$requestId = $insert->insert_id;
$insert->close();
$stmt->close();
$plainMessage = fp_comm_plain_message($data);

/* FP_TELEGRAM_PRESENTATION_V01 */
$telegramPayload =
    CommunicationRequestMessageFormatter::telegram(
        $data,
        (string)(
            getenv('FP_WEB_NOTIFICATION_THEME')
            ?: 'default'
        )
    );

// Close DB before slow external delivery; reconnect later only to update delivery status.
$db->close();

/* ForPrint delivery diagnostics v0.6.32 */

if ($mode === 'telegram') {
    $token = getenv('FP_WEB_TELEGRAM_BOT_TOKEN') ?: '';
    $chatId = getenv('FP_WEB_TELEGRAM_CHAT_ID') ?: '';

    if ($token !== '' && $chatId !== '') {
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'timeout' => 6,
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => http_build_query([
                    'chat_id' => $chatId,
                    'text' => $telegramPayload['text'],
                    'parse_mode' => $telegramPayload['parse_mode'],
                    'disable_web_page_preview' => 'true',
                ]),
            ],
        ]);

        $response = @file_get_contents(
            'https://api.telegram.org/bot' . rawurlencode($token) . '/sendMessage',
            false,
            $context
        );

        $deliveryStatus = is_string($response) && str_contains($response, '"ok":true')
            ? 'sent_telegram'
            : 'stored_telegram_failed';
    } else {
        $deliveryStatus = 'stored_telegram_not_configured';
    }
}

if ($mode === 'email') {
    $smtpEnabled = getenv('FP_WEB_ENABLE_SMTP') === '1';
    $smtpFallbackTarget = trim((string)getenv('FP_WEB_SMTP_TO'));
    $emailTarget = filter_var($target, FILTER_VALIDATE_EMAIL)
        ? $target
        : $smtpFallbackTarget;

    $emailDelivered = false;
    $emailConfigured = false;

    if ($smtpEnabled && filter_var($emailTarget, FILTER_VALIDATE_EMAIL)) {
        $emailConfigured = true;

        try {
            fp_comm_smtp_send_message(
                $emailTarget,
                'Запит з сайту ForPrint',
                $plainMessage
            );

            $deliveryStatus = 'sent_smtp_email';
            $emailDelivered = true;
        } catch (Throwable $e) {
            error_log(
                'ForPrint SMTP delivery failed ['
                . get_class($e)
                . ']'
            );
            $deliveryStatus = 'stored_smtp_failed';
        }
    }

    if (!$emailDelivered) {
        $mailEnabled = getenv('FP_WEB_ENABLE_PHP_MAIL') === '1';

        if ($mailEnabled && filter_var($emailTarget, FILTER_VALIDATE_EMAIL)) {
            $emailConfigured = true;
            $subject = 'Запит з сайту ForPrint';
            $fromEmail = trim((string)(
                getenv('FP_WEB_SMTP_FROM')
                ?: 'office@forprint.net.ua'
            ));
            $fromName = trim((string)(
                getenv('FP_WEB_SMTP_FROM_NAME')
                ?: 'ForPrint Website'
            ));
            $replyTo = filter_var(
                $data['primary_contact'],
                FILTER_VALIDATE_EMAIL
            )
                ? $data['primary_contact']
                : $fromEmail;

            $headers = [
                'MIME-Version: 1.0',
                'Content-Type: text/plain; charset=UTF-8',
                'From: '
                    . fp_comm_smtp_header_text($fromName)
                    . ' <' . $fromEmail . '>',
                'Reply-To: ' . $replyTo,
            ];

            $sent = @mail(
                $emailTarget,
                $subject,
                $plainMessage,
                implode("\r\n", $headers)
            );

            if ($sent) {
                $deliveryStatus = $smtpEnabled
                    ? 'sent_email_after_smtp_failed'
                    : 'sent_email';
                $emailDelivered = true;
            } elseif ($deliveryStatus === 'stored') {
                $deliveryStatus = 'stored_email_failed';
            }
        }
    }

    if (
        !$emailDelivered
        && !$emailConfigured
        && $deliveryStatus === 'stored'
    ) {
        $deliveryStatus = 'stored_email_not_configured';
    }
}

$statusDb = @new mysqli(HOST, USER, PASSWORD, DB_NAME);

if ($statusDb->connect_errno) {
    error_log(
        'ForPrint delivery status update DB reconnect failed'
    );
} else {
    $statusDb->set_charset('utf8mb4');

    $update = $statusDb->prepare("UPDATE `communication_requests` SET `delivery_status` = ? WHERE `id` = ?");

    if ($update) {
        $update->bind_param('si', $deliveryStatus, $requestId);
        $update->execute();
    } else {
        error_log(
            'ForPrint delivery status update prepare failed'
        );
    }

    $statusDb->close();
}

$deliveryCompleted = str_starts_with(
    $deliveryStatus,
    'sent_'
);

$responseMessage = $deliveryCompleted
    ? 'Заявку прийнято. Ми звʼяжемося з вами найближчим часом.'
    : 'Заявку збережено. Зовнішня доставка тимчасово '
        . 'недоступна, але звернення не втрачено.';

$responsePayload = [
    'ok' => true,
    'message' => $responseMessage,
    'request_id' => $requestId,
    'delivery_status' => $deliveryStatus,
    'delivery_completed' => $deliveryCompleted,
];

ForPrintCommunicationRequestSecurity::completeIdempotentRequest(
    $idempotency,
    $responsePayload
);

fp_comm_response(
    true,
    $responseMessage,
    [
        'request_id' => $requestId,
        'delivery_status' => $deliveryStatus,
        'delivery_completed' => $deliveryCompleted,
    ]
);
