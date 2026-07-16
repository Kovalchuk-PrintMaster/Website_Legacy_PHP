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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    fp_comm_response(false, 'Метод не підтримується.');
}

if (fp_comm_post('website', 255) !== '') {
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
    $mailEnabled = getenv('FP_WEB_ENABLE_PHP_MAIL') === '1';

    if ($mailEnabled && filter_var($target, FILTER_VALIDATE_EMAIL)) {
        $subject = 'Запит з сайту ForPrint';
        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
        ];

        $sent = @mail($target, $subject, $plainMessage, implode("\r\n", $headers));

        $deliveryStatus = $sent ? 'sent_email' : 'stored_email_failed';
    }
}

$update = $db->prepare("UPDATE `communication_requests` SET `delivery_status` = ? WHERE `id` = ?");
$update->bind_param('si', $deliveryStatus, $requestId);
$update->execute();

$db->close();

fp_comm_response(true, 'Заявку прийнято. Ми звʼяжемося з вами найближчим часом.', [
    'request_id' => $requestId,
    'delivery_status' => $deliveryStatus,
]);