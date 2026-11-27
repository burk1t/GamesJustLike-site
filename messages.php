<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (empty($_SESSION['login']) || ($_SESSION['role_name'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('Доступ запрещён. Эта страница доступна только администратору.');
}

require_once __DIR__ . '/config/api_client.php';

$messages = [];
$error = '';

try {
    $login = rawurlencode((string)$_SESSION['login']);
    $result = apiRequest('messages.php?login=' . $login);
    $messages = $result['messages'] ?? [];
} catch (Throwable $e) {
    error_log('Messages page error: ' . $e->getMessage());
    $error = 'Не удалось загрузить сообщения. Проверьте доступность API.';
}

function h(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Сообщения пользователей</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 30px 16px;
            background: #171a21;
            color: #e5e5e5;
            font-family: Arial, sans-serif;
        }
        .container {
            max-width: 1000px;
            margin: auto;
        }
        h1 { color: #66c0f4; }
        .top-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #66c0f4;
            text-decoration: none;
        }
        .message {
            background: #202630;
            border: 1px solid #343b49;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 16px;
        }
        .meta {
            color: #aeb8c5;
            font-size: 14px;
            line-height: 1.7;
        }
        .subject {
            font-size: 19px;
            color: #fff;
            margin: 12px 0;
        }
        .body {
            white-space: pre-wrap;
            overflow-wrap: anywhere;
            line-height: 1.6;
        }
        .notice {
            background: #30252a;
            border-radius: 8px;
            padding: 15px;
            margin: 15px 0;
        }
        .empty { color: #aeb8c5; }
    </style>
</head>
<body>
<div class="container">
    <a class="top-link" href="index.php">← Вернуться на главную</a>
    <h1>Сообщения пользователей</h1>

    <?php if ($error !== ''): ?>
        <div class="notice"><?= h($error) ?></div>
    <?php elseif (empty($messages)): ?>
        <p class="empty">Пока нет сообщений.</p>
    <?php else: ?>
        <p>Всего сообщений: <?= count($messages) ?></p>

        <?php foreach ($messages as $item): ?>
            <article class="message">
                <div class="meta">
                    <strong>От:</strong> <?= h($item['name'] ?? '') ?><br>
                    <strong>Email:</strong> <?= h($item['email'] ?? '') ?><br>
                    <strong>Дата:</strong> <?= h($item['created_at'] ?? '') ?><br>
                    <strong>ID:</strong> <?= h($item['id'] ?? '') ?>
                </div>
                <div class="subject"><?= h($item['subject'] ?? '') ?></div>
                <div class="body"><?= h($item['message'] ?? '') ?></div>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
</body>
</html>