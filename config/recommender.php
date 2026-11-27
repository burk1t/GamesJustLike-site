
<?php

require_once __DIR__ . '/api_client.php';

function computeRecommendations(
    ?int $userId,
    bool $includeUnavailable = false
): array {

    // 1. Получение данных через отдельный API
    $products = apiRequest('products.php')['products'] ?? [];
    $subcategories = apiRequest('subcategories.php')['subcategories'] ?? [];
    $matrices = apiRequest('product-matrices.php');

    $productPreferences = $matrices['product_preferences'] ?? [];
    $productRelations = $matrices['product_relations'] ?? [];

    $users = [];
    $userPrefs = [];
    $userEvents = [];
    $userGroupId = null;

    // 2. Данные пользователя
    if ($userId !== null) {
        $users = apiRequest('users.php?user_id=' . $userId)['users'] ?? [];

        foreach ($users as $user) {
            if ((int)$user['id'] === $userId) {
                $userGroupId = !empty($user['group_id'])
                    ? (int)$user['group_id']
                    : null;
                break;
            }
        }

        $preferences = apiRequest(
            'preferences.php?user_id=' . $userId
        )['preferences'] ?? [];

        foreach ($preferences as $row) {
            $userPrefs[(int)$row['preference_id']] = (float)$row['value'];
        }

        $events = apiRequest(
            'events.php?user_id=' . $userId
        )['events'] ?? [];

        $limit = strtotime('-30 days');

        foreach ($events as $event) {
            if (empty($event['product_id'])) {
                continue;
            }

            $created = strtotime($event['created_at'] ?? '');

            if ($created === false || $created < $limit) {
                continue;
            }

            $id = (int)$event['product_id'];

            $weight = match ($event['event_type']) {
                'view' => 0.2,
                'cart_add' => 0.6,
                'purchase' => 1.0,
                default => 0.0
            };

            if ($weight > ($userEvents[$id] ?? 0)) {
                $userEvents[$id] = $weight;
            }
        }
    }

    // 3. Групповые предпочтения
    $groupPrefs = [];
    $groupCategories = [];

    if ($userGroupId !== null) {
        $groups = apiRequest('groups.php');

        foreach ($groups['group_preferences'] ?? [] as $row) {
            if ((int)$row['group_id'] === $userGroupId) {
                $groupPrefs[(int)$row['preference_id']] =
                    (float)$row['coefficient'];
            }
        }

        foreach ($groups['group_categories'] ?? [] as $row) {
            if ((int)$row['group_id'] === $userGroupId) {
                $groupCategories[(int)$row['category_id']] =
                    (float)$row['coefficient'];
            }
        }
    }

    // 4. Категории подкатегорий
    $subcategoryCategories = [];

    foreach ($subcategories as $subcategory) {
        $subcategoryCategories[(int)$subcategory['id']] =
            (int)$subcategory['category_id'];
    }

    // 5. Матрица предпочтений товаров
    $productPrefs = [];

    foreach ($productPreferences as $row) {
        $productPrefs[(int)$row['product_id']]
                     [(int)$row['preference_id']] =
            (float)$row['coefficient'];
    }

    // 6. Связи товаров
    $relations = [];

    foreach ($productRelations as $row) {
        $relations[(int)$row['source_product_id']][] = [
            'target' => (int)$row['target_product_id'],
            'coeff' => (float)$row['coefficient']
        ];
    }

    // 7. Расчёт рекомендаций
    $result = [];

    foreach ($products as $p) {

        $pid = (int)$p['id'];

        if (!$includeUnavailable && (int)$p['stock'] <= 0) {
            continue;
        }

        $prefs = $productPrefs[$pid] ?? [];

        $categoryId = $subcategoryCategories[
            (int)($p['subcategory_id'] ?? 0)
        ] ?? 0;

        // Personal
        $personal = 0.0;

        if ($userPrefs && $prefs) {
            $sum = 0.0;
            $sumWeight = 0.0;

            foreach ($prefs as $prefId => $coef) {
                $sum += ($userPrefs[$prefId] ?? 0.0) * $coef;
                $sumWeight += $coef;
            }

            if ($sumWeight > 0) {
                $personal = $sum / $sumWeight;
            }
        }

        // Group
        $groupScore = 0.0;

        if ($userGroupId !== null) {
            $gpSum = 0.0;
            $gpWeight = 0.0;

            foreach ($prefs as $prefId => $coef) {
                $gpSum += ($groupPrefs[$prefId] ?? 0.0) * $coef;
                $gpWeight += $coef;
            }

            $gpMatch = $gpWeight > 0
                ? $gpSum / $gpWeight
                : 0.0;

            $gc = $groupCategories[$categoryId] ?? 0.0;

            $groupScore = 0.6 * $gpMatch + 0.4 * $gc;
        }

        // Relations
        $relScore = 0.0;

        if (!empty($relations[$pid]) && $userEvents) {
            foreach ($relations[$pid] as $rel) {
                if (isset($userEvents[$rel['target']])) {
                    $relScore +=
                        $rel['coeff'] * $userEvents[$rel['target']];
                }
            }

            $relScore = min(1.0, $relScore);
        }

        // Baseline
        $baseline = (float)($p['rating'] ?? 0) / 5.0;

        // Events: косинусное сходство
        $eventsScore = 0.0;
        $eventWeightSum = 0.0;
        $eventSimilaritySum = 0.0;

        foreach ($userEvents as $eventProductId => $eventWeight) {
            $eventPrefs = $productPrefs[$eventProductId] ?? [];

            if (!$eventPrefs || !$prefs) {
                continue;
            }

            $dot = 0.0;
            $normA = 0.0;
            $normB = 0.0;

            foreach ($prefs as $prefId => $coef) {
                $a = (float)$coef;
                $b = (float)($eventPrefs[$prefId] ?? 0.0);

                $dot += $a * $b;
                $normA += $a * $a;
                $normB += $b * $b;
            }

            if ($normA > 0 && $normB > 0) {
                $similarity = $dot / (sqrt($normA) * sqrt($normB));

                $eventSimilaritySum += $similarity * $eventWeight;
                $eventWeightSum += $eventWeight;
            }
        }

        if ($eventWeightSum > 0) {
            $eventsScore = $eventSimilaritySum / $eventWeightSum;
        }

        $eventsScore = max(0.0, min(1.0, $eventsScore));

        // Итоговая оценка — исходная формула
        $total = 0.40 * $personal
               + 0.25 * $groupScore
               + 0.15 * $relScore
               + 0.15 * $baseline
               + 0.05 * $eventsScore;

        $result[] = [
            'id' => $pid,
            'name' => $p['name'],
            'price' => $p['price'],
            'stock' => $p['stock'],
            'rating' => $p['rating'],
            'image_url' => $p['image_url'],
            'genre_text' => $p['genre_text'],
            'game_type' => $p['game_type'],
            'platforms' => $p['platforms'],
            'age_rating' => $p['age_rating'],
            'category_id' => $categoryId,
            'personal' => round($personal, 4),
            'group' => round($groupScore, 4),
            'relations' => round($relScore, 4),
            'baseline' => round($baseline, 4),
            'events' => round($eventsScore, 4),
            'total' => round($total, 4)
        ];
    }

    // 8. Сортировка
    usort($result, function ($a, $b) {
        if (abs($b['total'] - $a['total']) > 0.00001) {
            return $b['total'] <=> $a['total'];
        }

        return $a['id'] <=> $b['id'];
    });

    return $result;
}
