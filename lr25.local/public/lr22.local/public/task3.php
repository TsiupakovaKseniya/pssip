<?php
function task3($n)
{
    $fullName = "Тюпакова Ксения";
    $count = $n + 5;
    // Используем цикл for 
    for ($i = 1; $i <= $count; $i++) {
        echo "<div>($i) $fullName</div>";
    }

    // Выводим дополнительную информацию
    echo "<div><strong>Номер варианта (n):</strong> $n</div>";
    echo "<div><strong>Количество повторений (n + 5):</strong> $count</div>";
}
?>
