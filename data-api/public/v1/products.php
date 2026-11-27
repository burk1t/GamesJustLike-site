
<?php

header('Content-Type: application/json; charset=utf-8');

$config = require __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

function respond(int $status, array $data): void
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

$auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

if (!preg_match('/^Bearer\s+(.+)$/i', $auth, $matches) ||
    !hash_equals($config['api_token'], trim($matches[1]))) {
    respond(401, [
        'ok' => false,
        'error' => [
            'code' => 'UNAUTHORIZED',
            'message' => 'Требуется авторизация API'
        ]
    ]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Allow: GET');
    respond(405, [
        'ok' => false,
        'error' => [
            'code' => 'METHOD_NOT_ALLOWED',
            'message' => 'Разрешён только GET'
        ]
    ]);
}

try {
    $pdo = getDatabaseConnection();

    $stmt = $pdo->query(
        'SELECT * FROM products ORDER BY id ASC'
    );

    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    respond(200, [
        'ok' => true,
        'data' => [
            'count' => count($products),
            'products' => $products
        ]
    ]);

} catch (Throwable $e) {
    respond(500, [
        'ok' => false,
        'error' => [
            'code' => 'INTERNAL_ERROR',
            'message' => 'Не удалось получить каталог'
        ]
    ]);
}
