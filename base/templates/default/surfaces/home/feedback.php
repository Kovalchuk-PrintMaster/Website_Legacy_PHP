<?php
/* ForPrint shared home communication surface v0.6.44 */

$fpCommunicationConfig = [
    'id' => 'fp-home-feedback',
    'title' => 'Якщо ви не знайшли потрібний вам товар, напишіть нам повідомлення і ми спробуємо підібрати для вас найкращий варіант',
    'product_id' => 0,
    'product_name' => 'Пошук потрібного товару',
    'product_url' => (string)($_SERVER['REQUEST_URI'] ?? '/'),
];

include __DIR__ . '/../../include/communicationRequestForm.php';

unset($fpCommunicationConfig);
