<?php
function task2()
{
    // Устанавливаем часовой пояс
    date_default_timezone_set('Europe/Moscow');
    $current_datetime = new DateTime();

    echo "<div>";
    echo "<p><strong>1 формат:</strong> " . $current_datetime->format('Y-m-d') . "</p>";
    echo "<p><strong>2 формат:</strong> " . $current_datetime->format('d.m.Y') . "</p>";
    echo "<p><strong>3 формат:</strong> " . $current_datetime->format('d.m.y') . "</p>";
    echo "<p><strong>4 формат:</strong> " . $current_datetime->format('H:i:s') . "</p>";
    echo "</div>";
}

