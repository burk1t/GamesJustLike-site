
<?php

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

$config = require __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

function respond(int $status, array $data): void {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// РџСЂРѕРІРµСЂРєР° API-С‚РѕРєРµРЅР°
$auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

if (
    !preg_match('/^Bearer\s+(.+)$/i', $auth, $matches) ||
    !hash_equals($config['api_token'], trim($matches[1]))
) {
    respond(401, [
        'ok' => false,
        'error' => ['message' => 'РўСЂРµР±СѓРµС‚СЃСЏ Р°РІС‚РѕСЂРёР·Р°С†РёСЏ API']
    ]);
}

$method = $_SERVER['REQUEST_METHOD'];

if (!in_array($method, ['GET', 'POST'], true)) {
    header('Allow: GET, POST');
    respond(405, [
        'ok' => false,
        'error' => ['message' => 'Р Р°Р·СЂРµС€РµРЅС‹ GET Рё POST']
    ]);
}

try {
    $pdo = getDatabaseConnection();

    // ==========================================
    // GET вЂ” РїРѕР»СѓС‡РµРЅРёРµ РёСЃС‚РѕСЂРёРё СЃРѕР±С‹С‚РёР№
    // ==========================================
    if ($method === 'GET') {
        $userId = $_GET['user_id'] ?? null;

        if (
            $userId !== null &&
            (!ctype_digit((string)$userId) || (int)$userId < 1)
        ) {
            respond(400, [
                'ok' => false,
                'error' => ['message' => 'РќРµРєРѕСЂСЂРµРєС‚РЅС‹Р№ user_id']
            ]);
        }

        $sql = '
            SELECT id, user_id, product_id, event_type,
                   event_key, created_at
            FROM user_events
        ';

        $params = [];

        if ($userId !== null) {
            $sql .= ' WHERE user_id = :user_id';
            $params[':user_id'] = (int)$userId;
        }

        $sql .= ' ORDER BY created_at DESC, id DESC';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

        respond(200, [
            'ok' => true,
            'data' => [
                'count' => count($events),
                'events' => $events
            ]
        ]);
    }

    // ==========================================
    // POST вЂ” СЂРµРіРёСЃС‚СЂР°С†РёСЏ СЃРѕР±С‹С‚РёСЏ
    // ==========================================
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

    if (stripos($contentType, 'application/json') === false) {
        respond(400, [
            'ok' => false,
            'error' => ['message' => 'РўСЂРµР±СѓРµС‚СЃСЏ application/json']
        ]);
    }

    $input = json_decode(file_get_contents('php://input'), true);

    if (!is_array($input)) {
        respond(400, [
            'ok' => false,
            'error' => ['message' => 'РќРµРєРѕСЂСЂРµРєС‚РЅС‹Р№ JSON']
        ]);
    }

    $userId = filter_var(
        $input['user_id'] ?? null,
        FILTER_VALIDATE_INT
    );

    $productId = filter_var(
        $input['product_id'] ?? null,
        FILTER_VALIDATE_INT
    );

    $eventType = (string)($input['event_type'] ?? 'view');

    if ($eventType === 'cart') {
        $eventType = 'cart_add';
    }

    $allowed = ['view', 'cart_add', 'purchase'];

    if (
        !$userId || $userId < 1 ||
        !$productId || $productId < 1 ||
        !in_array($eventType, $allowed, true)
    ) {
        respond(400, [
            'ok' => false,
            'error' => ['message' => 'РќРµРєРѕСЂСЂРµРєС‚РЅС‹Рµ РґР°РЅРЅС‹Рµ СЃРѕР±С‹С‚РёСЏ']
        ]);
    }

    $eventKey = $input['event_key'] ?? '';

    if ($eventKey === '') {
        $eventKey = bin2hex(random_bytes(16));
    }

    if (
        !is_string($eventKey) ||
        !preg_match('/^[A-Za-z0-9_-]{16,64}$/', $eventKey)
    ) {
        respond(400, [
            'ok' => false,
            'error' => ['message' => 'РќРµРєРѕСЂСЂРµРєС‚РЅС‹Р№ event_key']
        ]);
    }

    // РЎС‚Р°СЂС‹Рµ РєРѕСЌС„С„РёС†РёРµРЅС‚С‹ РѕР±РЅРѕРІР»РµРЅРёСЏ РїСЂРµРґРїРѕС‡С‚РµРЅРёР№
    $weights = [
        'view' => 0.05,
        'cart_add' => 0.15,
        'purchase' => 0.30
    ];

    $weight = $weights[$eventType];

    $pdo->beginTransaction();

    // РџСЂРѕРІРµСЂСЏРµРј РїРѕР»СЊР·РѕРІР°С‚РµР»СЏ
    $check = $pdo->prepare(
        'SELECT id FROM users WHERE id = ?'
    );
    $check->execute([$userId]);

    if (!$check->fetchColumn()) {
        $pdo->rollBack();
        respond(404, [
            'ok' => false,
            'error' => ['message' => 'РџРѕР»СЊР·РѕРІР°С‚РµР»СЊ РЅРµ РЅР°Р№РґРµРЅ']
        ]);
    }

    // РџСЂРѕРІРµСЂСЏРµРј С‚РѕРІР°СЂ
    $check = $pdo->prepare(
        'SELECT id FROM products WHERE id = ?'
    );
    $check->execute([$productId]);

    if (!$check->fetchColumn()) {
        $pdo->rollBack();
        respond(404, [
            'ok' => false,
            'error' => ['message' => 'РўРѕРІР°СЂ РЅРµ РЅР°Р№РґРµРЅ']
        ]);
    }

    // Р—Р°С‰РёС‚Р° РѕС‚ РїРѕРІС‚РѕСЂРЅРѕР№ РѕР±СЂР°Р±РѕС‚РєРё РѕРґРЅРѕРіРѕ СЃРѕР±С‹С‚РёСЏ
    $check = $pdo->prepare(
        'SELECT id FROM user_events
         WHERE event_key = ?
         LIMIT 1'
    );
    $check->execute([$eventKey]);

    $existingId = $check->fetchColumn();

    if ($existingId) {
        $pdo->commit();

        respond(200, [
            'ok' => true,
            'data' => [
                'event_id' => (int)$existingId,
                'duplicate' => true,
                'message' => 'РЎРѕР±С‹С‚РёРµ СѓР¶Рµ РѕР±СЂР°Р±РѕС‚Р°РЅРѕ'
            ]
        ]);
    }

    // РџСЂРѕСЃРјРѕС‚СЂ РЅРµ С‡Р°С‰Рµ РѕРґРЅРѕРіРѕ СЂР°Р·Р° Р·Р° 5 РјРёРЅСѓС‚
    if ($eventType === 'view') {
        $check = $pdo->prepare(
            "SELECT id FROM user_events
             WHERE user_id = :user_id
               AND product_id = :product_id
               AND event_type = 'view'
               AND created_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
             LIMIT 1"
        );

        $check->execute([
            ':user_id' => $userId,
            ':product_id' => $productId
        ]);

        if ($check->fetchColumn()) {
            $pdo->commit();

            respond(200, [
                'ok' => true,
                'data' => [
                    'skipped' => 'recent_view',
                    'duplicate' => false,
                    'message' => 'РџСЂРѕСЃРјРѕС‚СЂ СѓР¶Рµ Р·Р°СЂРµРіРёСЃС‚СЂРёСЂРѕРІР°РЅ РЅРµРґР°РІРЅРѕ'
                ]
            ]);
        }
    }

    // Р—Р°РїРёСЃС‹РІР°РµРј СЃРѕР±С‹С‚РёРµ
    $stmt = $pdo->prepare(
        'INSERT INTO user_events
         (user_id, product_id, event_type, event_key, created_at)
         VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)'
    );

    $stmt->execute([
        $userId,
        $productId,
        $eventType,
        $eventKey
    ]);

    $eventId = (int)$pdo->lastInsertId();

    // РџРѕР»СѓС‡Р°РµРј РїСЂРёР·РЅР°РєРё С‚РѕРІР°СЂР°
    $stmt = $pdo->prepare(
        'SELECT preference_id, coefficient
         FROM product_preferences
         WHERE product_id = ?'
    );

    $stmt->execute([$productId]);
    $productPrefs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // РћР±РЅРѕРІР»СЏРµРј РёРЅРґРёРІРёРґСѓР°Р»СЊРЅС‹Рµ РїСЂРµРґРїРѕС‡С‚РµРЅРёСЏ
    $upsert = $pdo->prepare(
        'INSERT INTO user_preferences
         (user_id, preference_id, value, updated_at)
         VALUES (?, ?, MIN(1.0, MAX(0.0, ?)), CURRENT_TIMESTAMP)
         ON CONFLICT(user_id, preference_id)
         DO UPDATE SET
            value = MIN(
                1.0,
                MAX(0.0, user_preferences.value + excluded.value)
            ),
            updated_at = CURRENT_TIMESTAMP'
    );

    foreach ($productPrefs as $pref) {
        $delta = (float)$pref['coefficient'] * $weight;

        $upsert->execute([
            $userId,
            (int)$pref['preference_id'],
            $delta
        ]);
    }

    // Р”РѕР±Р°РІР»РµРЅРёРµ С‚РѕРІР°СЂР° РІ РєРѕСЂР·РёРЅСѓ
    if ($eventType === 'cart_add') {
        $check = $pdo->prepare(
            'SELECT quantity FROM cart_items
             WHERE user_id = ? AND product_id = ?'
        );

        $check->execute([$userId, $productId]);
        $quantity = $check->fetchColumn();

        if ($quantity === false) {
            $add = $pdo->prepare(
                'INSERT INTO cart_items
                 (user_id, product_id, quantity, updated_at)
                 VALUES (?, ?, 1, CURRENT_TIMESTAMP)'
            );

            $add->execute([$userId, $productId]);
        } else {
            $add = $pdo->prepare(
                'UPDATE cart_items
                 SET quantity = quantity + 1,
                     updated_at = CURRENT_TIMESTAMP
                 WHERE user_id = ? AND product_id = ?'
            );

            $add->execute([$userId, $productId]);
        }
    }

    // РџРѕРєСѓРїРєР° вЂ” СѓРґР°Р»СЏРµРј С‚РѕРІР°СЂ РёР· РєРѕСЂР·РёРЅС‹
    if ($eventType === 'purchase') {
        $del = $pdo->prepare(
            'DELETE FROM cart_items
             WHERE user_id = ? AND product_id = ?'
        );

        $del->execute([$userId, $productId]);
    }

    $pdo->commit();

    respond(201, [
        'ok' => true,
        'data' => [
            'event_id' => $eventId,
            'duplicate' => false,
            'event_type' => $eventType,
            'product_id' => $productId,
            'weight' => $weight,
            'updated_preferences' => count($productPrefs)
        ]
    ]);

} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    respond(500, [
        'ok' => false,
        'error' => [
            'code' => 'INTERNAL_ERROR',
            'message' => 'РќРµ СѓРґР°Р»РѕСЃСЊ РѕР±СЂР°Р±РѕС‚Р°С‚СЊ СЃРѕР±С‹С‚РёРµ'
        ]
    ]);
}

