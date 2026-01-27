<?php
function task6($x)
{
    try {
        if ($x == 0) {
            throw new Exception("деление на ноль");
        }
        return (2 * $x - 7) / (5 * $x);
    } catch (Exception $e) {
        return $e->getMessage();
    }
}
echo "<h2>Результат вычисления функции</h2>";
$values = [0, 1, 2, 3.5, -1, 7];

echo "Формула: y = (2x - 7) / (5x)<br><br>";

foreach ($values as $x) {
    $result = task6($x);

    echo "Для x = $x:<br>";
    echo "y = (2×$x - 7) / (5×$x)<br>";

    if (is_numeric($result)) {
        echo "y = " . round($result, 4) . "<br><br>";
    } else {
        echo "Ошибка: $result<br><br>";
    }
}
