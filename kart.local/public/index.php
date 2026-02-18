<?php
session_start();
$servername = "MySQL-8.4";
$username = "root";
$password = "";
$dbname = "phones_variant6";
// Если форма отправлена методом POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name     = trim($_POST["name"] ?? '');
    $gender   = $_POST["gender"] ?? '';
    $maiden   = trim($_POST["maiden"] ?? '');

    $errors = [];

    if (empty($name)) {
        $errors[] = "Введите ваше имя";
    }
    if (!in_array($gender, ['М', 'Ж'])) {
        $errors[] = "Выберите пол";
    }
    if (empty($maiden)) {
        $errors[] = "Введите девичью фамилию";
    }

    if (empty($errors)) {
        // Сохраняем в сессию
        $_SESSION["user_name"]   = $name;
        $_SESSION["user_gender"] = $gender;
        $_SESSION["maiden_name"] = $maiden;

        // Переход на страницу 2
        header("Location: 2.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <title>Вариант 6 — Задание 1</title>
    <style>
        body {
            font-family: sans-serif;
            max-width: 600px;
            margin: 40px auto;
        }

        .error {
            color: red;
        }

        label {
            display: block;
            margin: 12px 0 4px;
        }
    </style>
</head>

<body>

    <h2>Задание 1. Форма (метод POST)</h2>

    <?php if (!empty($errors)): ?>
        <div class="error">
            <strong>Ошибки:</strong><br>
            <?= implode("<br>", $errors) ?>
        </div>
    <?php endif; ?>

    <form method="post" action="">
        <label>Ваше имя *</label>
        <input type="text" name="name" value="<?= htmlspecialchars($name ?? '') ?>" required>

        <label>Ваш пол *</label>
        <input type="radio" name="gender" value="М" id="m" <?= ($gender ?? '') === 'М' ? 'checked' : '' ?> required>
        <label for="m">М</label>
        <input type="radio" name="gender" value="Ж" id="f" <?= ($gender ?? '') === 'Ж' ? 'checked' : '' ?>>
        <label for="f">Ж</label>

        <label>Ваша девичья фамилия</label>
        <input type="text" name="maiden" value="<?= htmlspecialchars($maiden ?? '') ?>" required>

        <br><br>
        <button type="submit">Submit</button>
    </form>
    <ul>
        <li><a href="3.php">задание 3</a></li>
        <li><a href="db.php">задание 4</a></li>
    </ul>
</body>

</html>