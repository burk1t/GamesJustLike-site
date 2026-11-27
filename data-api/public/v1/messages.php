<?php

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$config = require __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

function messagesRespond(int $status, array $data): never
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$token = '';

if (preg_match('/Bearer\s+(.+)/i', $authHeader, $matches)) {
    $token = trim($matches[1]);
}

if (
    $token === '' ||
    !hash_equals((string)$config['api_token'], $token)
) {
    messagesRespond(401, ['ok' => false, 'error' => 'Unauthorized']);
}

try {
    $pdo = getDatabaseConnection();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);

        if (!is_array($input)) {
            messagesRespond(400, ['ok' => false, 'error' => 'Invalid JSON']);
        }

        $name = trim((string)($input['name'] ?? ''));
        $email = trim((string)($input['email'] ?? ''));
        $subject = trim((string)($input['subject'] ?? ''));
        $message = trim((string)($input['message'] ?? ''));

        if (
            $name === '' || $email === '' ||
            $subject === '' || $message === ''
        ) {
            messagesRespond(422, ['ok' => false, 'error' => 'Заполните все поля']);
        }

        if (
            mb_strlen($name) > 100 ||
            mb_strlen($email) > 150 ||
            mb_strlen($subject) > 150 ||
            mb_strlen($message) > 3000 ||
            !filter_var($email, FILTER_VALIDATE_EMAIL)
        ) {
            messagesRespond(422, ['ok' => false, 'error' => 'Некорректные данные']);
        }

        $stmt = $pdo->prepare(
            'INSERT INTO messages (name, email, subject, message)
             VALUES (:name, :email, :subject, :message)'
        );

        $stmt->execute([
            ':name' => $name,
            ':email' => $email,
            ':subject' => $subject,
            ':message' => $message
        ]);

        messagesRespond(201, [
            'ok' => true,
            'data' => ['id' => (int)$pdo->lastInsertId()]
        ]);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $login = trim((string)($_GET['login'] ?? ''));

        if ($login === '') {
            messagesRespond(400, ['ok' => false, 'error' => 'Login required']);
        }

        $check = $pdo->prepare(
            'SELECT is_admin FROM users WHERE login = :login LIMIT 1'
        );
        $check->execute([':login' => $login]);
        $user = $check->fetch(PDO::FETCH_ASSOC);

        if (!$user || (int)$user['is_admin'] !== 1) {
            messagesRespond(403, ['ok' => false, 'error' => 'Forbidden']);
        }

        $stmt = $pdo->query(
            'SELECT id, name, email, subject, message, created_at
             FROM messages ORDER BY id DESC'
        );

        messagesRespond(200, [
            'ok' => true,
            'data' => ['messages' => $stmt->fetchAll(PDO::FETCH_ASSOC)]
        ]);
    }

    header('Allow: GET, POST');
    messagesRespond(405, ['ok' => false, 'error' => 'Method not allowed']);

} catch (Throwable $e) {
    messagesRespond(500, ['ok' => false, 'error' => 'Internal server error']);
}
