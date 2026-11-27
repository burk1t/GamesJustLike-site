
<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function respond(array $data, int $status = 200): void {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

$config = require __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? '';

if (!hash_equals(
    'Bearer ' . $config['api_token'],
    $authHeader
)) {
    respond([
        'ok' => false,
        'error' => ['message' => 'Доступ запрещён']
    ], 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond([
        'ok' => false,
        'error' => ['message' => 'Метод не поддерживается']
    ], 405);
}

$input = json_decode(file_get_contents('php://input'), true);

$identity = trim((string)($input['login'] ?? ''));
$password = (string)($input['password'] ?? '');

if ($identity === '' || $password === '') {
    respond([
        'ok' => false,
        'error' => ['message' => 'Введите логин и пароль']
    ], 400);
}

try {
    $pdo = getDatabaseConnection();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$stmt = $pdo->prepare(
    'SELECT
        u.id,
        u.login,
        u.email,
        u.password_hash,
        u.group_id,
        u.is_admin,
        u.is_active,
        u.role_id,
        r.name AS role_name
     FROM users u
     LEFT JOIN roles r ON r.id = u.role_id
     WHERE u.login = :login OR u.email = :email
     LIMIT 1'
);

    $stmt->execute([
        ':login' => $identity,
        ':email' => $identity
    ]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (
        !$user ||
        !password_verify($password, $user['password_hash']) ||
        !(int)$user['is_active']
    ) {
        respond([
            'ok' => false,
            'error' => ['message' => 'Неверный логин или пароль']
        ], 401);
    }

    unset($user['password_hash']);

    respond([
        'ok' => true,
        'data' => ['user' => $user]
    ]);

} catch (Throwable $e) {
    respond([
        'ok' => false,
        'error' => ['message' => 'Ошибка авторизации']
    ], 500);
}
