<?php
$a = 5;
$b = 7;

$sum        = $a + $b;
$product    = $a * $b;

$content = "a = $a\nb = $b\nСумма = $sum\nПроизведение = $product\n";

$file = '1.txt';

if (file_put_contents($file, $content) !== false) {
    $status = "Файл успешно записан";
} else {
    $status = "Ошибка записи файла";
}

$read_content = file_exists($file) ? file_get_contents($file) : "Файл не найден";
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <title>Вариант 6 — Задание 3 (Файлы)</title>
    <style>
        body {
            font-family: monospace;
            margin: 40px;
        }

        pre {
            background: #f8f8f8;
            padding: 16px;
            border: 1px solid #ddd;
        }
    </style>
</head>

<body>

    <h2>Задание 3. Файлы</h2>

    <p>a = <?= $a ?>, b = <?= $b ?></p>
    <p>Сумма = <?= $sum ?></p>
    <p>Произведение = <?= $product ?></p>

    <h3>Статус:</h3>
    <p><?= htmlspecialchars($status) ?></p>

    <h3>Содержимое файла 1.txt:</h3>
    <pre><?= htmlspecialchars($read_content) ?></pre>

</body>

</html>