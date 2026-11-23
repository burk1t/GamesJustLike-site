
<?php
require_once 'db.php';

// Получаем все обращения из SQLite
$stmt = $pdo->query("
    SELECT id, name, email, subject, message, created_at
    FROM messages
    ORDER BY id DESC
");

$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Обращения — GamesJustLike</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 30px;
            background: #0f141b;
            color: white;
            font-family: Arial, sans-serif;
        }

        .container {
            max-width: 1000px;
            margin: 30px auto;
        }

        h1 {
            color: #66c0f4;
        }

        .message {
            background: #1b2838;
            padding: 22px;
            margin-bottom: 20px;
            border-radius: 10px;
            border: 1px solid #34465a;
        }

        .message h2 {
            color: #66c0f4;
            margin-top: 0;
        }

        .meta {
            color: #aab4c0;
            font-size: 14px;
        }

        .content {
            white-space: pre-wrap;
            overflow-wrap: anywhere;
            line-height: 1.6;
        }

        a {
            color: #66c0f4;
            text-decoration: none;
        }

        .empty {
            background: #1b2838;
            padding: 25px;
            border-radius: 10px;
        }
    </style>
</head>

<body>

<div class="container">

    <h1>Обращения пользователей</h1>

    <p>GamesJustLike — панель просмотра сообщений</p>

    <p>
        Всего обращений: <strong><?= count($messages) ?></strong>
    </p>

    <?php if (count($messages) > 0): ?>

        <?php foreach ($messages as $item): ?>

            <div class="message">

                <h2>
                    #<?= (int)$item['id'] ?>
                    — <?= htmlspecialchars($item['subject'], ENT_QUOTES, 'UTF-8') ?>
                </h2>

                <p class="meta">
                    Имя:
                    <?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?>
                </p>

                <p class="meta">
                    Email:
                    <?= htmlspecialchars($item['email'], ENT_QUOTES, 'UTF-8') ?>
                </p>

                <p class="meta">
                    Дата:
                    <?= htmlspecialchars($item['created_at'], ENT_QUOTES, 'UTF-8') ?>
                </p>

                <hr>

                <div class="content"><?=
                    htmlspecialchars($item['message'], ENT_QUOTES, 'UTF-8')
                ?></div>

            </div>

        <?php endforeach; ?>

    <?php else: ?>

        <div class="empty">
            Пока обращений нет.
        </div>

    <?php endif; ?>

    <p>
        <a href="contacts.php">← Вернуться к форме обратной связи</a>
    </p>

    <p>
        <a href="index.html">← На главную</a>
    </p>

</div>

</body>
</html>
