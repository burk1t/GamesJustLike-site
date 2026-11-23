
<?php
echo "<h1>GamesJustLike — PHP работает!</h1>";

echo "<p>Версия PHP: " . phpversion() . "</p>";

echo "<p>Сервер Apache работает.</p>";

if (extension_loaded('pdo_sqlite')) {
    echo "<p style='color:green'>SQLite подключён!</p>";
} else {
    echo "<p style='color:red'>SQLite не подключён!</p>";
}
?>
