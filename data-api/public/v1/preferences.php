
<?php

header('Content-Type: application/json; charset=utf-8');

$config = require __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

function respond(int $status, array $data): void {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

$auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

if (!preg_match('/^Bearer\s+(.+)$/i', $auth, $matches) ||
    !hash_equals($config['api_token'], trim($matches[1]))) {
    respond(401, ['ok' => false, 'error' => [
        'code' => 'UNAUTHORIZED',
        'message' => 'Требуется авторизация API'
    ]]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Allow: GET');
    respond(405, ['ok' => false, 'error' => [
        'code' => 'METHOD_NOT_ALLOWED',
        'message' => 'Разрешён только GET'
    ]]);
}

$userId = $_GET['user_id'] ?? null;

if ($userId !== null &&
    (!ctype_digit((string)$userId) || (int)$userId < 1)) {
    respond(400, ['ok' => false, 'error' => [
        'code' => 'INVALID_INPUT',
        'message' => 'Некорректный user_id'
    ]]);
}

try {
    $pdo = getDatabaseConnection();

    $sql = '
        SELECT
            up.user_id,
            up.preference_id,
            p.code,
            p.name AS preference_name,
            up.value,
            up.updated_at
        FROM user_preferences up
        JOIN preferences p ON p.id = up.preference_id
    ';

    $params = [];

    if ($userId !== null) {
        $sql .= ' WHERE up.user_id = :user_id';
        $params[':user_id'] = (int)$userId;
    }

    $sql .= ' ORDER BY up.user_id, up.preference_id';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    respond(200, [
        'ok' => true,
        'data' => [
            'count' => count($items),
            'preferences' => $items
        ]
    ]);

} catch (Throwable $e) {
    respond(500, ['ok' => false, 'error' => [
        'code' => 'INTERNAL_ERROR',
        'message' => 'Не удалось получить предпочтения'
    ]]);
}
