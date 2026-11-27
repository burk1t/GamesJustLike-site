
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
$groupId = $_GET['group_id'] ?? null;

foreach (['user_id' => $userId, 'group_id' => $groupId] as $key => $value) {
    if ($value !== null &&
        (!ctype_digit((string)$value) || (int)$value < 1)) {
        respond(400, ['ok' => false, 'error' => [
            'code' => 'INVALID_INPUT',
            'message' => 'Некорректный параметр ' . $key
        ]]);
    }
}

try {
    $pdo = getDatabaseConnection();

    $sql = 'SELECT id, login, email, group_id, is_admin,
                   is_active, created_at, role_id
            FROM users WHERE 1=1';

    $params = [];

    if ($userId !== null) {
        $sql .= ' AND id = :user_id';
        $params[':user_id'] = (int)$userId;
    }

    if ($groupId !== null) {
        $sql .= ' AND group_id = :group_id';
        $params[':group_id'] = (int)$groupId;
    }

    $sql .= ' ORDER BY id ASC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    respond(200, [
        'ok' => true,
        'data' => [
            'count' => count($users),
            'users' => $users
        ]
    ]);

} catch (Throwable $e) {
    respond(500, ['ok' => false, 'error' => [
        'code' => 'INTERNAL_ERROR',
        'message' => 'Не удалось получить пользователей'
    ]]);
}
