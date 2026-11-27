
<?php

const SHOP_API_URL = 'http://127.0.0.1:8081/v1/';
const SHOP_API_TOKEN = 'YOUR_API_TOKEN_HERE';

function apiRequest(string $endpoint, string $method = 'GET', ?array $body = null): array
{
    $url = SHOP_API_URL . ltrim($endpoint, '/');

    $ch = curl_init($url);

    $headers = [
        'Accept: application/json',
        'Authorization: Bearer ' . SHOP_API_TOKEN
    ];

    $options = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_HTTPHEADER => $headers
    ];

    if ($method === 'POST') {
        $headers[] = 'Content-Type: application/json';

        $options[CURLOPT_HTTPHEADER] = $headers;
        $options[CURLOPT_POST] = true;
        $options[CURLOPT_POSTFIELDS] = json_encode(
            $body ?? [],
            JSON_UNESCAPED_UNICODE
        );
    }

    curl_setopt_array($ch, $options);

    $response = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);

    curl_close($ch);

    if ($response === false) {
        throw new RuntimeException('Ошибка подключения к API: ' . $error);
    }

    $data = json_decode($response, true);

    if (!is_array($data)) {
        throw new RuntimeException('API вернул некорректный JSON.');
    }

    if ($httpCode < 200 || $httpCode >= 300 || ($data['ok'] ?? false) !== true) {
        $message = $data['error']['message'] ?? 'Ошибка API';
        throw new RuntimeException($message . ' (HTTP ' . $httpCode . ')');
    }

    return $data['data'] ?? [];
}
