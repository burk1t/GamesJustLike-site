
# Реестр HTTP-маршрутов GamesJustLike

| № | Метод | Маршрут | Роль | Назначение |
|---:|---|---|---|---|
| 1 | POST | /api/visit | Гость, покупатель | Начало посещения |
| 2 | POST | /api/auth/login | Все | Авторизация |
| 3 | POST | /api/auth/logout | Покупатель, администратор | Выход |
| 4 | POST | /api/auth/register | Гость | Регистрация |
| 5 | GET | /api/games | Все | Получение каталога |
| 6 | GET | /api/games/{id} | Все | Карточка игры |
| 7 | GET | /api/games/search | Все | Поиск игр |
| 8 | POST | /api/events | Покупатель | Регистрация события |
| 9 | GET | /api/preferences | Покупатель | Получение предпочтений |
| 10 | PATCH | /api/preferences/{id} | Покупатель | Изменение предпочтения |
| 11 | GET | /api/recommendations | Все | Получение рекомендаций |
| 12 | GET | /api/cart | Покупатель | Просмотр корзины |
| 13 | POST | /api/cart/items | Покупатель | Добавление игры |
| 14 | PATCH | /api/cart/items/{id} | Покупатель | Изменение количества |
| 15 | DELETE | /api/cart/items/{id} | Покупатель | Удаление позиции |
| 16 | POST | /api/orders | Покупатель | Оформление заказа |
| 17 | GET | /api/orders | Покупатель | История заказов |
| 18 | GET | /api/orders/{id} | Покупатель | Просмотр заказа |
| 19 | GET | /api/admin/users | Администратор | Управление пользователями |
| 20 | POST | /api/admin/games | Администратор | Добавление игры |
| 21 | PATCH | /api/admin/games/{id} | Администратор | Изменение игры |
| 22 | DELETE | /api/admin/games/{id} | Администратор | Удаление игры |
