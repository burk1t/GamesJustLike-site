
<?php

$dbDirectory = __DIR__ . '/database';

// Создаём папку базы данных, если её нет
if (!is_dir($dbDirectory)) {
    mkdir($dbDirectory, 0777, true);
}

$dbPath = $dbDirectory . '/gamesjustlike.sqlite';

try {
    // Подключение к SQLite
    $pdo = new PDO('sqlite:' . $dbPath);

    // Режим обработки ошибок
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Кодировка
    $pdo->exec("PRAGMA encoding = 'UTF-8'");

    // Создание таблицы обращений
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS messages (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            email TEXT NOT NULL,
            subject TEXT NOT NULL,
            message TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");

} catch (PDOException $e) {
    die('Ошибка подключения к базе данных: ' . $e->getMessage());
}
