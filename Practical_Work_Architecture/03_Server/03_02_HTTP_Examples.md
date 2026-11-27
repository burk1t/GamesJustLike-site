
# Примеры HTTP-запросов и ответов GamesJustLike

## 1. Авторизация

Запрос:

POST /api/auth/login

Content-Type: application/json

{
  "login": "user01",
  "password": "ExamplePassword123"
}

Ответ: 200 OK

{
  "success": true,
  "user": {
    "id": 1,
    "login": "user01",
    "role": "buyer"
  },
  "message": "Авторизация успешна"
}

## 2. Получение рекомендаций

Запрос:

GET /api/recommendations

Authorization: Bearer example_session_token

Ответ: 200 OK

{
  "success": true,
  "recommendations": [
    {
      "game_id": 1,
      "title": "The Witcher 3",
      "score": 0.95
    },
    {
      "game_id": 2,
      "title": "Strategy Game",
      "score": 0.70
    }
  ]
}

## 3. Регистрация события

Запрос:

POST /api/events

Authorization: Bearer example_session_token
Content-Type: application/json

{
  "game_id": 1,
  "event_type": "view",
  "weight": 1
}

Ответ: 201 Created

{
  "success": true,
  "event_id": 101,
  "message": "Событие зарегистрировано"
}

## 4. Добавление игры в корзину

Запрос:

POST /api/cart/items

Authorization: Bearer example_session_token
Content-Type: application/json

{
  "game_id": 1,
  "quantity": 1
}

Ответ: 201 Created

{
  "success": true,
  "cart_item": {
    "game_id": 1,
    "quantity": 1
  },
  "message": "Игра добавлена в корзину"
}

## 5. Изменение предпочтения

Запрос:

PATCH /api/preferences/1

Authorization: Bearer example_session_token
Content-Type: application/json

{
  "value": 0.99
}

Ответ: 200 OK

{
  "success": true,
  "preference": {
    "id": 1,
    "name": "RPG",
    "value": 0.99
  }
}

## 6. Оформление заказа

Запрос:

POST /api/orders

Authorization: Bearer example_session_token
Content-Type: application/json

{
  "cart_id": 1,
  "payment_method": "demo"
}

Ответ: 201 Created

{
  "success": true,
  "order": {
    "id": 15,
    "status": "created",
    "total_amount": 29.99
  },
  "message": "Заказ оформлен"
}

## Примеры ошибочных ответов

### Неверные данные — 400 Bad Request

{
  "success": false,
  "error": {
    "code": "INVALID_INPUT",
    "message": "Проверьте введённые данные"
  }
}

### Не выполнена авторизация — 401 Unauthorized

{
  "success": false,
  "error": {
    "code": "UNAUTHORIZED",
    "message": "Необходимо войти в аккаунт"
  }
}

### Недостаточно прав — 403 Forbidden

{
  "success": false,
  "error": {
    "code": "FORBIDDEN",
    "message": "Недостаточно прав для выполнения операции"
  }
}

### Ресурс не найден — 404 Not Found

{
  "success": false,
  "error": {
    "code": "NOT_FOUND",
    "message": "Запрашиваемый ресурс не найден"
  }
}

### Конфликт данных — 409 Conflict

{
  "success": false,
  "error": {
    "code": "CONFLICT",
    "message": "Недостаточное количество товара"
  }
}

### Внутренняя ошибка — 500 Internal Server Error

{
  "success": false,
  "error": {
    "code": "INTERNAL_ERROR",
    "message": "Не удалось выполнить операцию"
  }
}
