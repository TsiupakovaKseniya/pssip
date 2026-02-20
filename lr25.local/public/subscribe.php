<?php
// subscribe.php — обработчик подписки на рассылку
header('Content-Type: application/json; charset=utf-8');

error_reporting(0);
ini_set('display_errors', 0);

// Разрешаем только POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode([
        "status" => "error",
        "message" => "Метод не поддерживается"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // Получаем email
    $email = isset($_POST["email"]) ? trim(strip_tags($_POST["email"])) : '';

    // Валидация
    if (empty($email)) {
        throw new Exception("Введите email");
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Некорректный email");
    }

    // Сохраняем в файл (или БД)
    $subscribersFile = __DIR__ . '/subscribers.txt';
    $date = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    // Проверяем, не подписан ли уже
    $existing = file_exists($subscribersFile) ? file($subscribersFile, FILE_IGNORE_NEW_LINES) : [];
    foreach ($existing as $line) {
        if (strpos($line, $email) !== false) {
            throw new Exception("Этот email уже подписан на рассылку");
        }
    }

    // Сохраняем
    $data = "$date | $email | $ip\n";
    file_put_contents($subscribersFile, $data, FILE_APPEND | LOCK_EX);

    // Отправляем уведомление админу (опционально)
    $to = "kssuww5@gmail.com";
    $subject = "=?UTF-8?B?" . base64_encode("Новая подписка на сайте") . "?=";
    $message = "Новый подписчик!\nEmail: $email\nДата: $date\nIP: $ip";
    $headers = "From: no-reply@kuhni-mix.ru\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    @mail($to, $subject, $message, $headers); // игнорируем ошибки

    // Успех
    echo json_encode([
        "status" => "success",
        "message" => "Спасибо за подписку! Теперь вы будете получать новости первыми."
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
