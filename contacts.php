
<?php
require_once 'db.php';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Контакты — GameJustLike</title>

    <link rel="icon" href="images/gamesjustlike-icon.svg">
    <link rel="stylesheet" href="style.css">

    <style>
        .contact-container {
            max-width: 750px;
            margin: 60px auto;
            padding: 30px;
            background: #1b2838;
            border: 1px solid #34465a;
            border-radius: 12px;
        }

        .contact-container h1 {
            color: #66c0f4;
            margin-bottom: 12px;
        }

        .contact-description {
            color: #acb2b8;
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .contact-form label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }

        .contact-form input,
        .contact-form textarea {
            display: block;
            width: 100%;
            padding: 13px;
            margin-bottom: 20px;
            background: #0f141b;
            color: white;
            border: 1px solid #34465a;
            border-radius: 6px;
            font-size: 15px;
        }

        .contact-form textarea {
            min-height: 150px;
            resize: vertical;
        }

        .contact-form button {
            padding: 13px 25px;
            background: #1a9fff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 15px;
        }

        .contact-form button:hover {
            background: #1685d2;
        }

        .contact-back {
            display: inline-block;
            margin-top: 25px;
            color: #66c0f4;
            text-decoration: none;
        }
    </style>
</head>

<body>

<header>
    <nav class="site-navbar">
        <div class="site-navbar-inner">

            <a class="site-logo" href="index.html">
                <img src="images/gamesjustlike-logo.svg"
                     alt="GameJustLike"
                     width="216"
                     height="40">
            </a>

            <div class="site-nav">
                <a href="index.html" class="site-nav-link">
                    🎮 Каталог игр
                </a>

                <a href="games.html" class="site-nav-link">
                    🌐 Онлайн игры
                </a>

                <a href="contacts.php" class="site-nav-link active">
                    ✉ Контакты
                </a>
            </div>

        </div>
    </nav>
</header>

<main>

    <div class="contact-container">

        <h1>✉ Обратная связь</h1>

        <p class="contact-description">
            Есть вопросы, предложения или замечания по GameJustLike?
            Заполни форму ниже, и твоё обращение будет сохранено.
        </p>

        <form class="contact-form"
              action="process.php"
              method="POST">

            <label for="name">Ваше имя</label>

            <input
                type="text"
                id="name"
                name="name"
                maxlength="100"
                required
                placeholder="Введите ваше имя">

            <label for="email">Электронная почта</label>

            <input
                type="email"
                id="email"
                name="email"
                maxlength="150"
                required
                placeholder="example@mail.com">

            <label for="subject">Тема обращения</label>

            <input
                type="text"
                id="subject"
                name="subject"
                maxlength="150"
                required
                placeholder="Тема сообщения">

            <label for="message">Сообщение</label>

            <textarea
                id="message"
                name="message"
                maxlength="3000"
                required
                placeholder="Напишите ваше сообщение..."></textarea>

            <button type="submit">
                Отправить сообщение
            </button>

        </form>

        <a href="index.html" class="contact-back">
            ← Вернуться в каталог
        </a>

    </div>

</main>

<footer class="site-footer">
    <div>© 2026 - GameJustLike. Все права защищены.</div>
</footer>

</body>
</html>
