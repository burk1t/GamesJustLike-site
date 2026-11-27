<?php
declare(strict_types=1);

require_once __DIR__ . '/config/api_client.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: contacts.php');
    exit;
}

$name = trim((string)($_POST['name'] ?? ''));
$email = trim((string)($_POST['email'] ?? ''));
$subject = trim((string)($_POST['subject'] ?? ''));
$message = trim((string)($_POST['message'] ?? ''));

function showResult(string $title, string $text, bool $success): never
{
    $color = $success ? '#55c46a' : '#ff6464';
    $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $safeText = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

    echo '<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>' . $safeTitle . '</title>
<style>
body{margin:0;background:#171a21;color:#eee;font-family:Arial,sans-serif;
display:flex;justify-content:center;align-items:center;min-height:100vh}
.box{max-width:500px;margin:20px;padding:35px;background:#202630;
border-radius:12px;text-align:center}
h1{color:' . $color . '}
p{line-height:1.6}
a{display:inline-block;margin-top:15px;color:#66c0f4;text-decoration:none}
</style>
</head>
<body><div class="box">
<h1>' . $safeTitle . '</h1>
<p>' . $safeText . '</p>
<a href="contacts.php">Вернуться к контактам</a><br>
<a href="index.php">На главную</a>
</div></body></html>';
    exit;
}

if (
    $name === '' || $email === '' ||
    $subject === '' || $message === ''
) {
    showResult('Ошибка', 'Заполните все поля формы.', false);
}

try {
    apiRequest('messages.php', 'POST', [
        'name' => $name,
        'email' => $email,
        'subject' => $subject,
        'message' => $message
    ]);

    showResult('Сообщение отправлено', 'Спасибо! Ваше сообщение успешно сохранено.', true);

} catch (Throwable $e) {
    error_log('Contact form error: ' . $e->getMessage());
    showResult('Ошибка отправки', 'Не удалось отправить сообщение. Попробуйте позже.', false);
}