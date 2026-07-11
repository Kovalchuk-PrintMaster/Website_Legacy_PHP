<?php

define('VG_ACCESS', true);

$root = realpath(__DIR__ . '/../..');
require $root . '/base/config.php';

$expectedDb = getenv('FP_WEB_EXPECTED_DB_NAME') ?: 'forprint_website_legacy_local';

if (DB_NAME !== $expectedDb) {
    fwrite(STDERR, "[FAIL] Refusing to write outside expected local database.\n");
    exit(1);
}

mysqli_report(MYSQLI_REPORT_OFF);

$db = new mysqli(HOST, USER, PASSWORD, DB_NAME);

if ($db->connect_errno) {
    fwrite(STDERR, "[FAIL] DB connection failed.\n");
    exit(1);
}

$db->set_charset('utf8mb4');

$alias = 'contacts';

$content = '<p>Напишіть або зателефонуйте нам — допоможемо підібрати оптимальний варіант виготовлення рекламно-інформаційної продукції.</p>';

$stmt = $db->prepare("SELECT id FROM information WHERE alias = ? LIMIT 1");
$stmt->bind_param('s', $alias);
$stmt->execute();
$res = $stmt->get_result();
$row = $res ? $res->fetch_assoc() : null;

if ($row) {
    $stmt = $db->prepare(
        "UPDATE information
         SET name = 'Контакти',
             visible = 1,
             show_top_menu = 1,
             menu_position = 4
         WHERE alias = ?"
    );
    $stmt->bind_param('s', $alias);
    $stmt->execute();

    echo "status: CONTACTS_INFORMATION_PAGE_UPDATED\n";
    exit(0);
}

$stmt = $db->prepare(
    "INSERT INTO information
        (name, alias, keywords, description, visible, menu_position, show_top_menu, content)
     VALUES
        (?, ?, ?, ?, 1, 4, 1, ?)"
);

$name = 'Контакти';
$keywords = 'контакти, друк, PrintMaster';
$description = 'Контакти PrintMaster';
$stmt->bind_param('sssss', $name, $alias, $keywords, $description, $content);
$stmt->execute();

echo "status: CONTACTS_INFORMATION_PAGE_CREATED\n";