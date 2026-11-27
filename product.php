
<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/config/api_client.php';

$productId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$productId || $productId < 1) {
    http_response_code(404);
    exit('Игра не найдена');
}

try {
    $data = apiRequest('products.php');
    $products = $data['products'] ?? [];

    $game = null;

    foreach ($products as $product) {
        if ((int)$product['id'] === $productId) {
            $game = $product;
            break;
        }
    }

    if (!$game) {
        http_response_code(404);
        exit('Игра не найдена');
    }

    // Получаем список подкатегорий через отдельный API
    $subcategoriesData = apiRequest('subcategories.php');
    $subcategories = $subcategoriesData['subcategories'] ?? [];

    $subcategoryName = '';
    $subcategoryId = (int)($game['subcategory_id'] ?? 0);

    foreach ($subcategories as $subcategory) {
        if ((int)$subcategory['id'] === $subcategoryId) {
            $subcategoryName = $subcategory['name'];
            break;
        }
    }

    $genre = htmlspecialchars(
        $subcategoryName ?: 'Не указан',
        ENT_QUOTES,
        'UTF-8'
    );

} catch (Throwable $e) {
    error_log('Product page error: ' . $e->getMessage());
    http_response_code(502);
    exit('Не удалось загрузить информацию об игре через API.');
}

$name = htmlspecialchars($game['name'] ?? '', ENT_QUOTES, 'UTF-8');
$description = htmlspecialchars($game['description'] ?? 'Описание отсутствует.', ENT_QUOTES, 'UTF-8');
$image = htmlspecialchars($game['image_url'] ?? '', ENT_QUOTES, 'UTF-8');
$developer = htmlspecialchars($game['developer'] ?? 'Не указан', ENT_QUOTES, 'UTF-8');
$platforms = htmlspecialchars($game['platforms'] ?? 'Не указаны', ENT_QUOTES, 'UTF-8');
$price = (float)($game['price'] ?? 0);
$rating = (float)($game['rating'] ?? 0);
$year = htmlspecialchars((string)($game['release_year'] ?? 'Не указан'), ENT_QUOTES, 'UTF-8');
$age = htmlspecialchars($game['age_rating'] ?? 'Не указан', ENT_QUOTES, 'UTF-8');
$gameType = htmlspecialchars($game['game_type'] ?? 'Не указан', ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $name ?> — GameJustLike</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header>
    <nav class="site-navbar">
        <div class="site-navbar-inner">
            <a class="site-logo" href="index.php">
                <img src="images/gamesjustlike-logo.svg" alt="GameJustLike" width="216" height="40">
            </a>

            <div class="site-nav">
                <a href="index.php" class="site-nav-link">🎮 Каталог игр</a>
                <span class="site-user">👤 <?= htmlspecialchars($_SESSION['login'] ?? 'Пользователь', ENT_QUOTES, 'UTF-8') ?></span>
                <a href="logout.php" class="site-logout-link">Выйти</a>
            </div>
        </div>
    </nav>
</header>

<main class="catalog-page">
    <p><a href="index.php">← Вернуться в каталог</a></p>

    <section class="game-card" style="max-width:900px;margin:30px auto;padding:25px;">
        <?php if ($image): ?>
            <img src="<?= $image ?>" alt="<?= $name ?>"
                 style="width:100%;max-height:400px;object-fit:cover;border-radius:12px;">
        <?php endif; ?>

        <div class="game-card-content">
            <h1><?= $name ?></h1>

            <p><?= nl2br($description) ?></p>

            <p>🎭 Жанр: <?= $genre ?></p>
            <p>👨‍💻 Разработчик: <?= $developer ?></p>
            <p>📅 Год выпуска: <?= $year ?></p>
            <p>⭐ Рейтинг: <?= number_format($rating, 1) ?> / 5</p>
            <p>💻 Платформы: <?= $platforms ?></p>
            <p>🎮 Тип игры: <?= $gameType ?></p>
            <p>🔞 Возрастной рейтинг: <?= $age ?></p>

            <div class="game-price">
                <?php if ($price <= 0): ?>
                    <strong>Бесплатно</strong>
                <?php else: ?>
                    <strong><?= number_format($price, 2) ?> USD</strong>
                <?php endif; ?>
            </div>

            <div class="game-actions">
                <button type="button" class="game-action-btn"
                        data-event="view" data-game-id="<?= $productId ?>">
                    👁 Просмотр
                </button>

                <button type="button" class="game-action-btn"
                        data-event="cart" data-game-id="<?= $productId ?>">
                    🛒 В корзину
                </button>

                <button type="button" class="game-action-btn"
                        data-event="purchase" data-game-id="<?= $productId ?>">
                    💳 Купить
                </button>
            </div>
        </div>
    </section>
<?php
// Похожие игры
$similarGames = [];

try {
    $matrices = apiRequest('product-matrices.php');
    $relations = $matrices['product_relations'] ?? [];

    $similarRelations = [];
    foreach ($relations as $rel) {
        if ((int)($rel['source_product_id'] ?? 0) !== $productId) {
            continue;
        }
        if (($rel['relation_type'] ?? '') !== 'similar') {
            continue;
        }
        $similarRelations[] = $rel;
    }

    usort($similarRelations, function ($a, $b) {
        return (float)($b['coefficient'] ?? 0) <=> (float)($a['coefficient'] ?? 0);
    });

    $productsById = [];
    foreach ($products as $p) {
        $productsById[(int)$p['id']] = $p;
    }

    foreach (array_slice($similarRelations, 0, 6) as $rel) {
        $targetId = (int)($rel['target_product_id'] ?? 0);
        if (isset($productsById[$targetId])) {
            $similarGames[] = $productsById[$targetId];
        }
    }
} catch (Throwable $e) {
    error_log('Similar games error: ' . $e->getMessage());
    $similarGames = [];
}
?>

<?php if (!empty($similarGames)): ?>
    <section class="similar-games" style="max-width:900px;margin:30px auto;padding:25px;background:rgba(27,40,56,0.85);border-radius:12px;">
        <h2 style="margin-top:0;color:#66c0f4;">🎮 Похожие игры</h2>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:16px;">
            <?php foreach ($similarGames as $similar): ?>
                <?php
                $sId = (int)$similar['id'];
                $sName = htmlspecialchars($similar['name'] ?? '', ENT_QUOTES, 'UTF-8');
                $sImage = htmlspecialchars($similar['image_url'] ?? '', ENT_QUOTES, 'UTF-8');
                $sPrice = (float)($similar['price'] ?? 0);
                ?>
                <a href="product.php?id=<?= $sId ?>" style="display:block;text-decoration:none;color:inherit;background:#16202d;border-radius:10px;overflow:hidden;">
                    <?php if ($sImage !== ''): ?>
                        <img src="<?= $sImage ?>" alt="<?= $sName ?>" style="width:100%;height:120px;object-fit:cover;display:block;">
                    <?php endif; ?>
                    <div style="padding:10px;">
                        <div style="font-weight:600;margin-bottom:6px;"><?= $sName ?></div>
                        <div style="color:#a4d007;">
                            <?php if ($sPrice <= 0): ?>
                                Бесплатно
                            <?php else: ?>
                                <?= number_format($sPrice, 2) ?> USD
                            <?php endif; ?>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>
</main>

<script src="js/user_events.js"></script>
</body>
</html>
