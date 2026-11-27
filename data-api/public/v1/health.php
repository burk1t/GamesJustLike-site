
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

// Проверка внутреннего токена
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

// Проверка метода
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

// Проверка базы данных
try {
    $pdo = getDatabaseConnection();

    $pdo->query('SELECT 1');

    respond(200, [
        'ok' => true,
        'data' => [
            'service' => 'GamesJustLike Data API',
            'status' => 'running',
            'database' => 'connected'
        ]
    ]);

} catch (Throwable $e) {
    respond(503, [
        'ok' => false,
        'error' => [
            'code' => 'DATABASE_UNAVAILABLE',
            'message' => 'База данных временно недоступна'
        ]
    ]);
}
