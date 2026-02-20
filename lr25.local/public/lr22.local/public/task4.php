<?php
function task4()
{
    // Создаем массив из 5 целых элементов
    $array = [17, 47, 7, 87, 27]; // Пример значений

    // Выводим исходный массив
    echo "<strong>Исходный массив:</strong> " . implode(", ", $array) . "<br>";

    // Находим минимальный элемент и его индекс
    $min_value = min($array);
    $min_index = array_search($min_value, $array);
    echo "<strong>Минимальный элемент:</strong> $min_value <br>";

    // Переставляем минимальный элемент с последним
    $last_index = count($array) - 1;

    // Меняем местами
    $temp = $array[$min_index];
    $array[$min_index] = $array[$last_index];
    $array[$last_index] = $temp;

    // Выводим измененный массив
    echo "<strong>Измененный массив:</strong> " . implode(", ", $array) . "<br>";
}
