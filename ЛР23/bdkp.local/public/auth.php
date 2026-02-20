<?php
// auth.php — центральный контроль доступа
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: index.php");
    exit();
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function requireAdmin($msg = "Доступ запрещён. Только для администратора.") {
    if (!isAdmin()) {
        http_response_code(403);
        die("<div style='text-align:center;padding:60px;font-family:Arial;'>
                <h2 style='color:#d32f2f;'>403 — Доступ запрещён</h2>
                <p>$msg</p>
                <a href='index.php'>← На главную</a>
             </div>");
    }
}
?>