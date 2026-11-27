
<?php
session_start();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

function respond(array $data, int $status = 200): void {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Проверяем авторизацию пользователя
if (!isset($_SESSION['user_id'])) {
    respond([
        'success' => false,
        'message' => 'Не авторизован'
    ], 401);
}

// Получаем JSON от браузера
$input = json_decode(file_get_contents('php://input'), true);

if (!is_array($input)) {
    respond([
        'success' => false,
        'message' => 'Некорректный JSON'
    ], 400);
}

$productId = filter_var(
    $input['product_id'] ?? null,
    FILTER_VALIDATE_INT
);

$eventType = (string)($input['event_type'] ?? 'view');

if ($eventType === 'cart') {
    $eventType = 'cart_add';
}

if (
    !$productId ||
    $productId < 1 ||
    !in_array($eventType, ['view', 'cart_add', 'purchase'], true)
) {
    respond([
        'success' => false,
        'message' => 'Некорректное событие'
    ], 400);
}

try {
    require_once __DIR__ . '/../config/api_client.php';

    // Ключ создаётся на сервере магазина, а не браузером
    $eventKey = bin2hex(random_bytes(16));

    // user_id берётся только из серверной сессии
    $result = apiRequest('events.php', 'POST', [
        'user_id' => (int)$_SESSION['user_id'],
        'product_id' => (int)$productId,
        'event_type' => $eventType,
        'event_key' => $eventKey
    ]);

    respond([
        'success' => true,
        'event_type' => $eventType,
        'product_id' => (int)$productId,
        'event_id' => $result['event_id'] ?? null,
        'duplicate' => $result['duplicate'] ?? false,
        'skipped' => $result['skipped'] ?? null,
        'message' => $result['message'] ?? 'Событие обработано'
    ]);

} catch (Throwable $e) {
    error_log('Event API error: ' . $e->getMessage());

    respond([
        'success' => false,
        'message' => 'Не удалось обработать событие через API'
    ], 502);
}
