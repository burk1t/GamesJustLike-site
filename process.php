
<?php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: contacts.php');
    exit;
}

// Получение данных
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');

// Проверка заполнения
if (
    $name === '' ||
    $email === '' ||
    $subject === '' ||
    $message === ''
) {
    die('Ошибка: заполните все поля.');
}

// Проверка длины
if (
    mb_strlen($name) > 100 ||
    mb_strlen($email) > 150 ||
    mb_strlen($subject) > 150 ||
    mb_strlen($message) > 3000
) {
    die('Ошибка: превышена допустимая длина текста.');
}

// Проверка email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die('Ошибка: неправильный формат email.');
}

try {
    // Подготовленный SQL-запрос
    $stmt = $pdo->prepare("
        INSERT INTO messages (name, email, subject, message)
        VALUES (:name, :email, :subject, :message)
    ");

    $stmt->execute([
        ':name' => $name,
        ':email' => $email,
        ':subject' => $subject,
        ':message' => $message
    ]);

    echo '<!DOCTYPE html>
    <html lang="ru">
    <head>
        <meta charset="UTF-8">
        <title>Успешно</title>
        <style>
            body {
                background: #0f141b;
                color: white;
                font-family: Arial;
                text-align: center;
                padding: 100px 20px;
            }
            h1 { color: #66c0f4; }
            a { color: #66c0f4; }
        </style>
    </head>
    <body>
        <h1>Сообщение успешно отправлено!</h1>
        <p>Спасибо за обращение в GamesJustLike.</p>
        <a href="index.html">На главную</a>
    </body>
    </html>';

} catch (PDOException $e) {
    http_response_code(500);
    echo 'Ошибка сохранения сообщения.';
}
?>
