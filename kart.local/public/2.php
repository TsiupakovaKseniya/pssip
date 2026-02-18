<?php
session_start();

if (!isset($_SESSION["user_name"]) || !isset($_SESSION["maiden_name"])) {
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <title>Вариант 6 — Страница 2</title>
    <style>
        body {
            font-family: sans-serif;
            max-width: 600px;
            margin: 40px auto;
        }
    </style>
</head>

<body>

    <h2>Задание 2. Сессии</h2>

    <p>Здравствуйте, <strong><?= htmlspecialchars($_SESSION["user_name"]) ?></strong>!</p>
    <p>Ваша девичья фамилия: <strong><?= htmlspecialchars($_SESSION["maiden_name"]) ?></strong></p>

    <?php if ($_SESSION["user_gender"] === "Ж"): ?>
        <p>Девичья фамилия актуальна :)</p>
    <?php else: ?>
        <p>Девичья фамилия указана для справки.</p>
    <?php endif; ?>

    <hr>

    <p><strong>Идентификатор сессии (session_id):</strong><br>
        <?= session_id() ?></p>

    <p><a href="index.php">← Вернуться к форме</a></p>

</body>

</html>