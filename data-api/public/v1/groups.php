
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

try {
    $pdo = getDatabaseConnection();

    $groups = $pdo->query(
        'SELECT * FROM customer_groups ORDER BY id'
    )->fetchAll(PDO::FETCH_ASSOC);

    $groupPreferences = $pdo->query(
        'SELECT
            gp.group_id,
            gp.preference_id,
            p.code,
            p.name AS preference_name,
            gp.coefficient
         FROM group_preferences gp
         JOIN preferences p ON p.id = gp.preference_id
         ORDER BY gp.group_id, gp.preference_id'
    )->fetchAll(PDO::FETCH_ASSOC);

    $groupCategories = $pdo->query(
        'SELECT
            gc.group_id,
            gc.category_id,
            c.name AS category_name,
            gc.coefficient
         FROM group_categories gc
         JOIN categories c ON c.id = gc.category_id
         ORDER BY gc.group_id, gc.category_id'
    )->fetchAll(PDO::FETCH_ASSOC);

    respond(200, [
        'ok' => true,
        'data' => [
            'groups' => $groups,
            'group_preferences' => $groupPreferences,
            'group_categories' => $groupCategories,
            'counts' => [
                'groups' => count($groups),
                'group_preferences' => count($groupPreferences),
                'group_categories' => count($groupCategories)
            ]
        ]
    ]);

} catch (Throwable $e) {
    respond(500, ['ok' => false, 'error' => [
        'code' => 'INTERNAL_ERROR',
        'message' => 'Не удалось получить данные групп'
    ]]);
}
