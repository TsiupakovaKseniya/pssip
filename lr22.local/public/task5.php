<?php
function task5()
{
    // Создаем строковые переменные
    $s1 = "Я люблю Беларусь";
    $s2 = "Я учусь в Политехническом колледже";

    // Номер варианта
    $n = 15;

    echo "s1: $s1<br>";
    echo "s2: $s2<br><br>";

    // 1. Определить длину строки s2
    echo "1. Длина строки s2: " . iconv_strlen($s2, 'UTF-8') . " символов<br>";

    // 2. Определить встречается ли в строке s1 слово "Гродно"
    if (iconv_strpos($s1, "Гродно", 0, 'UTF-8') !== false) {
        echo "2. Слово 'Гродно' найдено в строке s1<br>";
    } else {
        echo "2. Слово 'Гродно' не найдено в строке s1<br>";
    }

    // 3. Выделить n-ый символ в строке s2
    if ($n <= iconv_strlen($s2, 'UTF-8')) {
        $char = iconv_substr($s2, $n - 1, 1, 'UTF-8');

        echo "3. $n-ый символ в s2: '$char'<br>";

        $char = mb_substr($s1, $n - 1, 1, 'UTF-8');
        $char_cp866 = mb_convert_encoding($char, 'CP866', 'UTF-8');

        $ascii_code =  ord($char_cp866);

        echo "ASCII-код: {$ascii_code}<br>";
    } else {
        echo "3. Ошибка: строка слишком короткая для получения $n-го символа<br>";
    }
}
