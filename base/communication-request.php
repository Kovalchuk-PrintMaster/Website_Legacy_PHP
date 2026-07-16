<?php

declare(strict_types=1);


define('VG_ACCESS', true);

header('Content-Type: application/json; charset=utf-8');

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
    $value = trim((string)($_POST[$key] ?? ''));

    if (mb_strlen($value) > $limit) {
        $value = mb_substr($value, 0, $limit);
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
    fp_comm_response(false, 'Метод не підтримується.');
}

if (fp_comm_post('fp_request_company_url_confirm', 255) !== '') {
    fp_comm_response(true, 'Заявку прийнято.');
}

require_once __DIR__ . '/config.php';

if (
    !defined('HOST') ||
    !defined('USER') ||
    !defined('PASSWORD') ||
    !defined('DB_NAME')
) {
    http_response_code(500);
    fp_comm_response(false, 'Налаштування бази недоступні.');
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

if ($data['primary_contact'] === '' && $data['phone'] === '') {
    http_response_code(422);
    fp_comm_response(false, 'Вкажіть хоча б один контакт для звʼязку.');
}

$db = @new mysqli(HOST, USER, PASSWORD, DB_NAME);

if ($db->connect_errno) {
    http_response_code(500);
    fp_comm_response(false, 'Не вдалося підключитися до бази.');
}

$db->set_charset('utf8mb4');

$db->query("
    CREATE TABLE IF NOT EXISTS `communication_requests` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `mode` varchar(64) NOT NULL DEFAULT '',
        `product_id` int(11) NOT NULL DEFAULT 0,
        `product_name` varchar(255) NOT NULL DEFAULT '',
        `product_url` varchar(500) NOT NULL DEFAULT '',
        `primary_contact` varchar(255) NOT NULL DEFAULT '',
        `phone` varchar(100) NOT NULL DEFAULT '',
        `quantity_requested` varchar(255) NOT NULL DEFAULT '',
        `message` text,
        `delivery_target` varchar(255) NOT NULL DEFAULT '',
        `delivery_status` varchar(64) NOT NULL DEFAULT 'stored',
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8
");

$quantityColumn = $db->query("SHOW COLUMNS FROM `communication_requests` LIKE 'quantity_requested'");
if (!$quantityColumn || $quantityColumn->num_rows === 0) {
    $db->query("ALTER TABLE `communication_requests` ADD `quantity_requested` varchar(255) NOT NULL DEFAULT '' AFTER `phone`");
}

$stmt = $db->prepare("SELECT `target` FROM `communication_buttons` WHERE `alias` = ? AND `visible` = 1 LIMIT 1");
$stmt->bind_param('s', $mode);
$stmt->execute();
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

$insert->execute();

if ($insert->errno) {
    http_response_code(500);
    fp_comm_response(false, 'Не вдалося зберегти заявку.');
}

$requestId = $insert->insert_id;
$plainMessage = fp_comm_plain_message($data);

// Close DB before slow external delivery; reconnect later only to update delivery status.
$db->close();

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
                    'text' => $plainMessage,
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
    }
}

if ($mode === 'email') {
    $smtpEnabled = getenv('FP_WEB_ENABLE_SMTP') === '1';
    $smtpFallbackTarget = trim((string)getenv('FP_WEB_SMTP_TO'));
    $emailTarget = filter_var($target, FILTER_VALIDATE_EMAIL)
        ? $target
        : $smtpFallbackTarget;

    $emailDelivered = false;

    if ($smtpEnabled && filter_var($emailTarget, FILTER_VALIDATE_EMAIL)) {
        try {
            fp_comm_smtp_send_message(
                $emailTarget,
                'Запит з сайту ForPrint',
                $plainMessage
            );

            $deliveryStatus = 'sent_smtp_email';
            $emailDelivered = true;
        } catch (Throwable $e) {
            error_log('ForPrint SMTP delivery failed: ' . $e->getMessage());
            $deliveryStatus = 'stored_smtp_failed';
        }
    }

    if (!$emailDelivered) {
        $mailEnabled = getenv('FP_WEB_ENABLE_PHP_MAIL') === '1';

        if ($mailEnabled && filter_var($emailTarget, FILTER_VALIDATE_EMAIL)) {
            $subject = 'Запит з сайту ForPrint';
            $fromEmail = trim((string)(getenv('FP_WEB_SMTP_FROM') ?: 'office@forprint.net.ua'));
            $fromName = trim((string)(getenv('FP_WEB_SMTP_FROM_NAME') ?: 'ForPrint Website'));
            $replyTo = filter_var($data['primary_contact'], FILTER_VALIDATE_EMAIL)
                ? $data['primary_contact']
                : $fromEmail;

            $headers = [
                'MIME-Version: 1.0',
                'Content-Type: text/plain; charset=UTF-8',
                'From: ' . fp_comm_smtp_header_text($fromName) . ' <' . $fromEmail . '>',
                'Reply-To: ' . $replyTo,
            ];

            $sent = @mail($emailTarget, $subject, $plainMessage, implode("\r\n", $headers));

            if ($sent) {
                $deliveryStatus = $smtpEnabled ? 'sent_email_after_smtp_failed' : 'sent_email';
                $emailDelivered = true;
            } elseif ($deliveryStatus === 'stored') {
                $deliveryStatus = 'stored_email_failed';
            }
        }
    }
}

$statusDb = @new mysqli(HOST, USER, PASSWORD, DB_NAME);

if ($statusDb->connect_errno) {
    error_log('ForPrint delivery status update DB reconnect failed: ' . $statusDb->connect_error);
} else {
    $statusDb->set_charset('utf8mb4');

    $update = $statusDb->prepare("UPDATE `communication_requests` SET `delivery_status` = ? WHERE `id` = ?");

    if ($update) {
        $update->bind_param('si', $deliveryStatus, $requestId);
        $update->execute();
    } else {
        error_log('ForPrint delivery status update prepare failed: ' . $statusDb->error);
    }

    $statusDb->close();
}

fp_comm_response(true, 'Заявку прийнято. Ми звʼяжемося з вами найближчим часом.', [
    'request_id' => $requestId,
'delivery_status' => $deliveryStatus,
]);