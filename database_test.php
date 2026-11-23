
<?php
require_once 'db.php';

echo '<h1>GamesJustLike — Database Test</h1>';
echo '<p style="color:green">Подключение к SQLite успешно!</p>';

$stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'");

echo '<h3>Таблицы базы данных:</h3>';

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo '<p>' . htmlspecialchars($row['name']) . '</p>';
}
?>
