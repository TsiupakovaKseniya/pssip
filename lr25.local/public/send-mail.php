<?php
// send-mail.php — исправленная версия с правильной обработкой ошибок
error_reporting(0); // Отключаем вывод ошибок в браузер
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');

// Разрешаем только POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode([
        "status" => "error",
        "message" => "Метод не поддерживается. Используйте POST."
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // ==================== ПОЛУЧЕНИЕ И ОЧИСТКА ДАННЫХ ====================
    $name  = isset($_POST["name"])  ? trim(strip_tags($_POST["name"])) : '';
    $phone = isset($_POST["phone"]) ? trim(strip_tags($_POST["phone"])) : '';
    $email = isset($_POST["email"]) ? trim(strip_tags($_POST["email"])) : '';

    // ==================== ВАЛИДАЦИЯ ====================
    $errors = [];

    if (empty($name)) {
        $errors[] = "Поле «Имя» обязательно.";
    } elseif (mb_strlen($name, 'UTF-8') < 2) {
        $errors[] = "Имя должно содержать минимум 2 символа.";
    }

    if (empty($phone)) {
        $errors[] = "Поле «Телефон» обязательно.";
    } else {
        $phoneClean = preg_replace('/[\s\-\(\)\+]/', '', $phone);
        if (!preg_match('/^7\d{10}$|^8\d{10}$/', $phoneClean)) {
            $errors[] = "Некорректный номер телефона.";
        }
    }

    if (empty($email)) {
        $errors[] = "Поле «Email» обязательно.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Некорректный email.";
    }

    if (!empty($errors)) {
        echo json_encode([
            "status" => "error",
            "message" => implode("\n", $errors)
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ==================== ОТПРАВКА ПИСЬМА (с проверкой) ====================
    $to = "kssuww5@gmail.com";
    $subject = "=?UTF-8?B?" . base64_encode("Заявка с сайта Кухни Микс") . "?=";

    $message = "Имя: $name\n";
    $message .= "Телефон: $phone\n";
    $message .= "Email: $email\n";
    $message .= "IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'неизвестно') . "\n";
    $message .= "Дата: " . date("d.m.Y H:i:s");

    $headers = "From: no-reply@kuhni-mix.ru\r\n";
    $headers .= "Reply-To: $email\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $headers .= "Content-Transfer-Encoding: 8bit\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();

    // Проверяем, работает ли mail()
    if (function_exists('mail')) {
        $sent = mail($to, $subject, $message, $headers);
    } else {
        $sent = false;
    }

    // Для отладки - сохраняем в файл, если почта не работает
    if (!$sent) {
        // Логируем в файл для проверки
        $logFile = __DIR__ . '/applications.log';
        $logData = date('Y-m-d H:i:s') . " | $name | $phone | $email\n";
        file_put_contents($logFile, $logData, FILE_APPEND);
    }

    // Всегда возвращаем успех пользователю (даже если почта не ушла, но мы сохранили)
    echo json_encode([
        "status" => "success",
        "message" => "Заявка принята! Мы свяжемся с вами в ближайшее время."
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    // Ловим любые ошибки
    error_log("Ошибка в send-mail.php: " . $e->getMessage());

    echo json_encode([
        "status" => "error",
        "message" => "Техническая ошибка. Пожалуйста, позвоните нам: +7 (3466) 68-15-98"
    ], JSON_UNESCAPED_UNICODE);
}
