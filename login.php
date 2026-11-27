
<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/config/api_client.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';

    try {
        $response = apiRequest('auth.php', 'POST', [
            'login' => $login,
            'password' => $password
        ]);

        $user = $response['user'] ?? null;

        if (!$user || empty($user['id'])) {
            throw new RuntimeException('API не вернул данные пользователя.');
        }

        session_regenerate_id(true);

        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['login'] = $user['login'];
        $_SESSION['email'] = $user['email'] ?? '';
        $_SESSION['role_id'] = $user['role_id'] ?? null;
        $_SESSION['role_name'] = $user['role_name'] ?? '';

        header('Location: index.php');
        exit;

    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Авторизация — GamesJustLike</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <main class="login-container">
        <h1>Авторизация</h1>

        <?php if ($error): ?>
            <p style="color:#ff6464;">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </p>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <input
                type="text"
                name="login"
                placeholder="Логин"
                required
                autocomplete="username"
            >
            <input
                type="password"
                name="password"
                placeholder="Пароль"
                required
                autocomplete="current-password"
            >
            <button type="submit">Войти</button>
        </form>
    </main>
</body>
</html>
