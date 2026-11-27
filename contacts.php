<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Обратная связь — GamesJustLike</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            padding: 30px 16px;
            background: #171a21;
            color: #e5e5e5;
            font-family: Arial, sans-serif;
        }
        .container {
            max-width: 650px;
            margin: 30px auto;
            padding: 30px;
            background: #202630;
            border: 1px solid #343b49;
            border-radius: 12px;
        }
        h1 { color: #66c0f4; margin-top: 0; }
        p { color: #b8c4d2; line-height: 1.6; }
        label {
            display: block;
            margin: 18px 0 7px;
            color: #dce3eb;
        }
        input, textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #465365;
            border-radius: 6px;
            background: #171a21;
            color: #fff;
            font: inherit;
        }
        textarea { min-height: 150px; resize: vertical; }
        input:focus, textarea:focus {
            outline: 2px solid #66c0f4;
            border-color: transparent;
        }
        button {
            margin-top: 22px;
            padding: 13px 24px;
            border: 0;
            border-radius: 6px;
            background: #1a9fff;
            color: white;
            font-size: 16px;
            cursor: pointer;
        }
        button:hover { background: #087acb; }
        .back {
            display: inline-block;
            margin-bottom: 22px;
            color: #66c0f4;
            text-decoration: none;
        }
        .back:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <main class="container">
        <a class="back" href="index.php">← Вернуться в каталог</a>

        <h1>Обратная связь</h1>
        <p>
            Есть вопрос или предложение? Заполните форму.
            Ваше сообщение будет сохранено и передано администрации сайта.
        </p>

        <form action="process.php" method="POST">
            <label for="name">Ваше имя</label>
            <input
                type="text"
                id="name"
                name="name"
                maxlength="100"
                required
            >

            <label for="email">Электронная почта</label>
            <input
                type="email"
                id="email"
                name="email"
                maxlength="150"
                required
            >

            <label for="subject">Тема сообщения</label>
            <input
                type="text"
                id="subject"
                name="subject"
                maxlength="150"
                required
            >

            <label for="message">Сообщение</label>
            <textarea
                id="message"
                name="message"
                maxlength="3000"
                required
            ></textarea>

            <button type="submit">Отправить сообщение</button>
        </form>
    </main>
</body>
</html>