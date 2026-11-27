<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
require __DIR__ . '/config/recommender.php';

$userId = (int)$_SESSION['user_id'];
$diag   = isset($_GET['diag']) && $_GET['diag'] === '1';

$items = computeRecommendations($userId);

// Словарь категорий: id => name
$categories = [];
foreach (apiRequest('categories.php')['categories'] ?? [] as $c) {
    $categories[(int)$c['id']] = $c['name'];
}

$totalGames = count($items);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Каталог игр — GameJustLike</title>
    <link rel="icon" type="image/svg+xml" href="images/gamesjustlike-icon.svg" />
    <link rel="stylesheet" href="style.css" />
</head>
<body>
    <header>
        <nav class="site-navbar">
            <div class="site-navbar-inner">
                <a class="site-logo" href="index.php" aria-label="GameJustLike — на главную">
                    <img src="images/gamesjustlike-logo.svg" alt="GameJustLike" width="216" height="40" />
                </a>
                <div class="site-nav">
                    <a href="index.php" class="site-nav-link active">🎮 Каталог игр</a>
                    <a href="games.html" class="site-nav-link">🌐 Онлайн игры</a>
                    <a href="contacts.php" class="site-nav-link">✉ Контакты</a>
                    <span class="site-user">
                        👤 <?= htmlspecialchars($_SESSION['login'] ?? 'Гость') ?>
                    </span>
                    <a href="logout.php" class="site-logout-link">Выйти</a>
                </div>
            </div>
        </nav>
    </header>

    <main>
        <div class="catalog-page">
            <div class="catalog-header">
                <h1>GameJustLike</h1>
                <p>Найди игру, которая подходит именно тебе</p>
                <form class="search-form" id="searchForm" autocomplete="off">
                    <div class="search-input-wrap">
                        <input type="text" name="search" id="searchInput" placeholder="🔍 Поиск игры по названию..." />
                        <div id="searchHistoryBox" class="search-history-box hidden"></div>
                    </div>
                    <button type="submit">Найти</button>
                </form>
            </div>

            <?php
            // Блок персональных рекомендаций (топ-6 из уже вычисленных $items)
            $recommendedGames = array_slice($items, 0, 6);
            ?>
            <?php if (!empty($recommendedGames)): ?>
                <section class="recommended-block" style="margin:10px 0 30px;padding:20px;background:rgba(27,40,56,0.85);border-radius:12px;">
                    <h2 style="margin-top:0;color:#66c0f4;">✨ Рекомендуем для вас</h2>
                    <p style="margin-top:-6px;color:#8f98a0;">Подобрано на основе ваших предпочтений, группы и истории событий</p>
                    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:16px;">
                        <?php foreach ($recommendedGames as $rec): ?>
                            <?php
                            $rId = (int)$rec['id'];
                            $rName = htmlspecialchars($rec['name'] ?? '', ENT_QUOTES, 'UTF-8');
                            $rImage = htmlspecialchars($rec['image_url'] ?? '', ENT_QUOTES, 'UTF-8');
                            $rPrice = (float)($rec['price'] ?? 0);
                            ?>
                            <a href="product.php?id=<?= $rId ?>" style="display:block;text-decoration:none;color:inherit;background:#16202d;border-radius:10px;overflow:hidden;">
                                <?php if ($rImage !== ''): ?>
                                    <img src="<?= $rImage ?>" alt="<?= $rName ?>" loading="lazy" style="width:100%;height:120px;object-fit:cover;display:block;">
                                <?php endif; ?>
                                <div style="padding:10px;">
                                    <div style="font-weight:600;margin-bottom:6px;"><?= $rName ?></div>
                                    <div style="color:#a4d007;">
                                        <?php if ($rPrice <= 0): ?>
                                            Бесплатно
                                        <?php else: ?>
                                            <?= number_format($rPrice, 2) ?> USD
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>
            <div class="catalog-layout">
                <main class="games-area">
                    <div class="games-toolbar">
                        <span>Найдено игр: <strong id="gamesCount"><?= $totalGames ?></strong></span>
                        <div class="sort-buttons">
                            <span>Сортировка:</span>
                            <a href="#" data-sort="rating">⭐ Рейтинг</a>
                            <a href="#" data-sort="name">🔤 Название</a>
                        </div>
                    </div>

                    <div id="comparePanel" class="compare-panel hidden">
                        <span>⚖ Выбрано для сравнения: <strong id="compareCount">0</strong> / 3</span>
                        <a id="compareGoBtn" href="#" class="arcade-btn arcade-btn-primary">Сравнить</a>
                        <button id="compareClearBtn" class="arcade-btn">Очистить</button>
                    </div>

                    <div class="games" id="gamesGrid">
                        <?php foreach ($items as $it): ?>
                            <?php
                                $catName  = $categories[$it['category_id']] ?? '';
                                $platform = htmlspecialchars($it['platforms'] ?? '');
                                $gameType = htmlspecialchars($it['game_type'] ?? '');
                                $ageRat   = htmlspecialchars($it['age_rating'] ?? '');
                                $tags     = ''; // теги товара не запрашиваем в модуле, оставим пустыми
                                $price    = (float)$it['price'];
                                $rating   = (float)$it['rating'];
                                $name     = htmlspecialchars($it['name']);
                            ?>
                            <div class="game-card-wrapper"
                                 data-name="<?= $name ?>"
                                 data-genre="<?= htmlspecialchars($catName) ?>"
                                 data-rating="<?= $rating ?>"
                                 data-game-id="<?= (int)$it['id'] ?>"
                                 data-tags="<?= $tags ?>"
                                 data-platforms="<?= $platform ?>"
                                 data-gametype="<?= $gameType ?>"
                                 data-agerating="<?= $ageRat ?>"
                                 data-total="<?= $it['total'] ?>">
                                <a href="product.php?id=<?= (int)$it['id'] ?>" class="game-link">
                                    <div class="game-card">
                                        <div class="game-card-image">
                                            <?php if (!empty($it['image_url'])): ?>
                                                <img src="<?= htmlspecialchars($it['image_url']) ?>" alt="<?= $name ?>" loading="lazy" />
                                            <?php else: ?>
                                                🎮
                                            <?php endif; ?>
                                        </div>
                                        <div class="game-card-content">
                                            <h2><?= $name ?></h2>
                                            <p class="game-genre"><?= htmlspecialchars($catName) ?></p>
                                            <p class="game-rating">⭐ <?= number_format($rating, 1, '.', '') ?> / 5</p>
                                            <?php if ($gameType): ?><p>🎮 <?= $gameType ?></p><?php endif; ?>
                                            <?php if ($platform): ?><p>💻 <?= $platform ?></p><?php endif; ?>
                                            <?php if ($ageRat): ?><p>🔞 <?= $ageRat ?></p><?php endif; ?>
                                            <div class="game-price">
                                                <?php if ($price <= 0): ?>
                                                    <strong>Бесплатно</strong>
                                                <?php else: ?>
                                                    <strong><?= number_format($price, 2, '.', '') ?> USD</strong>
                                                <?php endif; ?>
                                            </div>
                                            <?php if ($diag): ?>
                                                <div class="rec-diag">
                                                    <div>ID: <b><?= (int)$it['id'] ?></b> | Total: <b><?= $it['total'] ?></b></div>
                                                    <div>Personal: <?= $it['personal'] ?></div>
                                                    <div>Group: <?= $it['group'] ?></div>
                                                    <div>Relations: <?= $it['relations'] ?></div>
                                                    <div>Baseline: <?= $it['baseline'] ?></div>
                                                    <div>Events: <?= $it['events'] ?></div>
                                                </div>
                                            <?php endif; ?>
                                            <div class="game-actions">
                                                <button type="button" class="game-action-btn"
                                                        data-event="view" data-game-id="<?= (int)$it['id'] ?>">
                                                    👁 Просмотр
                                                </button>
                                                <button type="button" class="game-action-btn"
                                                        data-event="cart" data-game-id="<?= (int)$it['id'] ?>">
                                                    🛒 В корзину
                                                </button>
                                                <button type="button" class="game-action-btn"
                                                        data-event="purchase" data-game-id="<?= (int)$it['id'] ?>">
                                                    💳 Купить
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                                <button class="compare-add-btn" data-game-id="<?= (int)$it['id'] ?>" title="Добавить к сравнению">⚖</button>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="no-games hidden" id="noGames">
                        <h2>Игры не найдены</h2>
                        <p>Попробуйте изменить параметры поиска или фильтры.</p>
                    </div>
                </main>

                <aside class="filters-sidebar">
                    <form class="filters-form" id="filtersForm">
                        <details open>
                            <summary>Жанры</summary>
                            <div class="filter-options">
                                <label class="filter-option"><input type="checkbox" name="genre" value="Экшен" /><span>Экшен</span></label>
                                <label class="filter-option"><input type="checkbox" name="genre" value="RPG" /><span>RPG</span></label>
                                <label class="filter-option"><input type="checkbox" name="genre" value="Песочница" /><span>Песочница</span></label>
                                <label class="filter-option"><input type="checkbox" name="genre" value="Инди" /><span>Инди</span></label>
                                <label class="filter-option"><input type="checkbox" name="genre" value="Симулятор" /><span>Симулятор</span></label>
                                <label class="filter-option"><input type="checkbox" name="genre" value="Стратегия" /><span>Стратегия</span></label>
                            </div>
                        </details>

                        <details>
                            <summary>Тип игры</summary>
                            <div class="filter-options">
                                <label class="filter-option"><input type="checkbox" name="gameType" value="Одиночная" /><span>Одиночная</span></label>
                                <label class="filter-option"><input type="checkbox" name="gameType" value="Кооператив" /><span>Кооператив</span></label>
                                <label class="filter-option"><input type="checkbox" name="gameType" value="Мультиплеер" /><span>Мультиплеер</span></label>
                            </div>
                        </details>

                        <details>
                            <summary>Платформа</summary>
                            <div class="filter-options">
                                <label class="filter-option"><input type="checkbox" name="platform" value="PC" /><span>PC</span></label>
                                <label class="filter-option"><input type="checkbox" name="platform" value="PlayStation" /><span>PlayStation</span></label>
                                <label class="filter-option"><input type="checkbox" name="platform" value="Xbox" /><span>Xbox</span></label>
                                <label class="filter-option"><input type="checkbox" name="platform" value="Nintendo Switch" /><span>Nintendo Switch</span></label>
                            </div>
                        </details>

                        <details>
                            <summary>Возрастной рейтинг</summary>
                            <div class="filter-options">
                                <label class="filter-option"><input type="checkbox" name="ageRating" value="6+" /><span>6+</span></label>
                                <label class="filter-option"><input type="checkbox" name="ageRating" value="12+" /><span>12+</span></label>
                                <label class="filter-option"><input type="checkbox" name="ageRating" value="16+" /><span>16+</span></label>
                                <label class="filter-option"><input type="checkbox" name="ageRating" value="18+" /><span>18+</span></label>
                            </div>
                        </details>

                        <button type="submit" class="apply-filters-button">Применить фильтры</button>
                        <a href="index.php" class="reset-filters-button">Сбросить фильтры</a>
                    </form>
                </aside>
            </div>
        </div>
    </main>

    <footer class="site-footer">
        <div>© 2026 - GameJustLike. Все права защищены.</div>
    </footer>

    <script src="js/catalog.js"></script>
 <script src="js/user_events.js"></script>
</body>
</html>