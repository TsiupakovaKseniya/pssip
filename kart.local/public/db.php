<?php

// =========== Настройки подключения ===========
$servername = "MySQL-8.4";   // ← это ваш хост (имя сервера в OSPanel)
$username   = "root";
$password   = "";
$dbname     = "phones_variant6";

try {
    $pdo = new PDO(
        "mysql:host=$servername;dbname=$dbname;charset=utf8mb4",
        $username,
        $password
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);  // опционально
} catch (PDOException $e) {
    die("Ошибка подключения к БД: " . $e->getMessage());
}

// =========== 4.2 Добавление нового абонента (если форма отправлена) ===========
$message = '';
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["add"])) {
    $fam     = trim($_POST["fam"]     ?? '');
    $im      = trim($_POST["im"]      ?? '');
    $otch    = trim($_POST["otch"]    ?? '');
    $birth   = $_POST["birth"]   ?? '';
    $phone   = trim($_POST["phone"]   ?? '');
    $pasport = trim($_POST["pasport"] ?? '');

    if ($fam && $im && $phone) {
        $stmt = $pdo->prepare("
            INSERT INTO Abonenty 
            (familiya, imya, otchestvo, data_rozhdeniya, telefon, nomer_pasporta)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$fam, $im, $otch, $birth ?: null, $phone, $pasport ?: null]);

        $message = "Новый абонент успешно добавлен!";
    } else {
        $message = "Ошибка: заполните хотя бы Фамилию, Имя и Телефон";
    }
}

// =========== Выборка всех записей ===========
$stmt = $pdo->query("SELECT * FROM Abonenty ORDER BY id");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <title>Вариант 6 — Задание 4 (База данных)</title>
    <style>
        body {
            font-family: sans-serif;
            margin: 30px;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        th,
        td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: left;
        }

        th {
            background: #f0f0f0;
        }

        .success {
            color: green;
        }

        .error {
            color: red;
        }
    </style>
</head>

<body>

    <h2>База данных «Телефоны» — таблица Abonenty</h2>

    <?php if ($message): ?>
        <p class="<?= strpos($message, 'Ошибка') === false ? 'success' : 'error' ?>">
            <?= htmlspecialchars($message) ?>
        </p>
    <?php endif; ?>

    <h3>Добавить нового абонента</h3>

    <form method="post">
        <p>
            <label>Фамилия *<br>
                <input name="fam" required></label>
        </p>
        <p>
            <label>Имя *<br>
                <input name="im" required></label>
        </p>
        <p>
            <label>Отчество<br>
                <input name="otch"></label>
        </p>
        <p>
            <label>Дата рождения<br>
                <input type="date" name="birth"></label>
        </p>
        <p>
            <label>Телефон *<br>
                <input name="phone" required placeholder="+7..."></label>
        </p>
        <p>
            <label>Номер паспорта<br>
                <input name="pasport"></label>
        </p>
        <button type="submit" name="add">Добавить</button>
    </form>

    <hr>

    <h3>Содержимое таблицы после добавления</h3>

    <?php if (count($rows) > 0): ?>
        <table>
            <tr>
                <th>ID</th>
                <th>Фамилия</th>
                <th>Имя</th>
                <th>Отчество</th>
                <th>Дата рождения</th>
                <th>Телефон</th>
                <th>Паспорт</th>
            </tr>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <td><?= htmlspecialchars($row['familiya']) ?></td>
                    <td><?= htmlspecialchars($row['imya']) ?></td>
                    <td><?= htmlspecialchars($row['otchestvo'] ?: '—') ?></td>
                    <td><?= $row['data_rozhdeniya'] ?: '—' ?></td>
                    <td><?= htmlspecialchars($row['telefon']) ?></td>
                    <td><?= htmlspecialchars($row['nomer_pasporta'] ?: '—') ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p>В таблице пока нет записей.</p>
    <?php endif; ?>

    <p><small>Создано: <?= date('d.m.Y H:i') ?></small></p>

</body>

</html>