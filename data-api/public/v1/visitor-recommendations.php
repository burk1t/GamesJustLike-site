
<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

function respond(array $data, int $status = 200): void
{
    http_response_code($status);
    echo json_encode(
        $data,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
}

// Проверка внутреннего токена API
$config = require __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

$headers = function_exists('getallheaders') ? getallheaders() : [];
$authorization = $headers['Authorization']
    ?? $headers['authorization']
    ?? $_SERVER['HTTP_AUTHORIZATION']
    ?? '';

$expectedToken = 'Bearer ' . ($config['api_token'] ?? '');

if (
    $expectedToken === 'Bearer ' ||
    !hash_equals($expectedToken, $authorization)
) {
    respond([
        'ok' => false,
        'error' => ['message' => 'Доступ запрещён']
    ], 401);
}

try {
    $pdo = getDatabaseConnection();

    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    // POST: запись действия анонимного посетителя
    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);

        if (!is_array($input)) {
            respond([
                'ok' => false,
                'error' => ['message' => 'Некорректный JSON']
            ], 400);
        }

        $eventKey = trim((string)($input['event_key'] ?? ''));
        $productId = filter_var(
            $input['product_id'] ?? null,
            FILTER_VALIDATE_INT
        );
        $eventType = (string)($input['event_type'] ?? 'view');

       $eventType = match ($eventType) {
    'click' => 'view',
    'cart' => 'cart_add',
    default => $eventType
};

$allowedTypes = ['view', 'cart_add', 'purchase'];

        if (!preg_match('/^[a-zA-Z0-9_-]{16,80}$/', $eventKey)) {
            respond([
                'ok' => false,
                'error' => ['message' => 'Некорректный идентификатор посетителя']
            ], 400);
        }

        if (
            !$productId ||
            $productId < 1 ||
            !in_array($eventType, $allowedTypes, true)
        ) {
            respond([
                'ok' => false,
                'error' => ['message' => 'Некорректное событие']
            ], 400);
        }

        $check = $pdo->prepare(
            'SELECT id FROM products WHERE id = :id LIMIT 1'
        );
        $check->execute([':id' => $productId]);

        if (!$check->fetchColumn()) {
            respond([
                'ok' => false,
                'error' => ['message' => 'Игра не найдена']
            ], 404);
        }

        $insert = $pdo->prepare(
            'INSERT INTO user_events
                (user_id, product_id, event_type, event_key, created_at)
             VALUES
                (NULL, :product_id, :event_type, :event_key, CURRENT_TIMESTAMP)'
        );

        $insert->execute([
            ':product_id' => $productId,
            ':event_type' => $eventType,
            ':event_key' => $eventKey
        ]);

        respond([
            'ok' => true,
            'data' => ['success' => true]
        ]);
    }

    if ($method !== 'GET') {
        header('Allow: GET, POST');

        respond([
            'ok' => false,
            'error' => ['message' => 'Метод не поддерживается']
        ], 405);
    }

    // GET: расчёт рекомендаций
    $eventKey = trim((string)($_GET['event_key'] ?? ''));

    if (!preg_match('/^[a-zA-Z0-9_-]{16,80}$/', $eventKey)) {
        respond([
            'ok' => false,
            'error' => ['message' => 'Некорректный идентификатор посетителя']
        ], 400);
    }

    $sql = <<<'SQL'
WITH visitor_preferences AS (
    SELECT
        pp.preference_id,
        SUM(pp.coefficient * CASE e.event_type
            WHEN 'purchase' THEN 3.0
            WHEN 'cart' THEN 2.0
            WHEN 'click' THEN 1.5
            ELSE 1.0
        END) AS preference_weight
    FROM user_events e
    INNER JOIN product_preferences pp
        ON pp.product_id = e.product_id
    WHERE e.event_key = :event_key_preferences
      AND e.created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)
    GROUP BY pp.preference_id
),
scored_products AS (
    SELECT
        p.id,
        p.name,
        p.description,
        p.developer,
        p.release_year,
        p.price,
        p.age_rating,
        p.image_url,
        p.genre_text,
        p.game_type,
        p.platforms,
        p.rating,
        p.is_free,
        COALESCE((
            SELECT SUM(vp.preference_weight * pp.coefficient)
            FROM product_preferences pp
            INNER JOIN visitor_preferences vp
                ON vp.preference_id = pp.preference_id
            WHERE pp.product_id = p.id
        ), 0) AS preference_score,
        COALESCE((
            SELECT SUM(pr.coefficient * CASE e.event_type
                WHEN 'purchase' THEN 3.0
                WHEN 'cart' THEN 2.0
                WHEN 'click' THEN 1.5
                ELSE 1.0
            END)
            FROM product_relations pr
            INNER JOIN user_events e
                ON e.product_id = pr.target_product_id
            WHERE pr.source_product_id = p.id
              AND e.event_key = :event_key_relations
              AND e.created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)
        ), 0) AS relation_score
    FROM products p
)
SELECT
    id,
    name,
    description,
    developer,
    release_year,
    price,
    age_rating,
    image_url,
    genre_text,
    game_type,
    platforms,
    rating,
    is_free,
    ROUND(
        preference_score +
        relation_score +
        COALESCE(rating, 0) * 0.05,
        4
    ) AS recommendation_score
FROM scored_products
ORDER BY
    recommendation_score DESC,
    COALESCE(rating, 0) DESC,
    name ASC
LIMIT 12
SQL;

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':event_key_preferences' => $eventKey,
        ':event_key_relations' => $eventKey
    ]);

    $games = $stmt->fetchAll();

    $historyStmt = $pdo->prepare(
        "SELECT COUNT(*)
         FROM user_events
         WHERE event_key = :event_key
           AND created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)"
    );

    $historyStmt->execute([':event_key' => $eventKey]);

    $hasHistory = (int)$historyStmt->fetchColumn() > 0;

    respond([
        'ok' => true,
        'data' => [
            'success' => true,
            'mode' => $hasHistory ? 'personalized' : 'rating_fallback',
            'count' => count($games),
            'items' => $games
        ]
    ]);

} catch (Throwable $e) {
    error_log('Visitor recommendations API: ' . $e->getMessage());

    respond([
        'ok' => false,
        'error' => ['message' => 'Ошибка рекомендательной системы']
    ], 500);
}
