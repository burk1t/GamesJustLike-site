-- 1. Поиск пользователя по логину
SELECT id, login, email, role_id, password_hash
FROM users
WHERE login = :login;