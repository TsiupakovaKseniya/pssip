<?php
session_start();
$servername = "MySQL-8.4";
$username = "root";
$password = "";
$dbname = "sotrudniki";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Ошибка подключения: " . $conn->connect_error);
}

$login_error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['logout'])) {
        session_destroy();
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }

    $username_input = $_POST["username"]; // Получаем логин из формы
    $password_input = $_POST["password"];

    // Проверка пароля
    // После подготовки запроса
    $sql = "SELECT password, role FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username_input);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();

        if ($password_input === $row['password']) {
            $_SESSION['loggedin'] = true;
            $_SESSION['username'] = $username_input;
            $_SESSION['role'] = $row['role'];               // ← КЛЮЧЕВАЯ СТРОКА

            header("Location: index.php");
            exit();
        } else {
            $login_error = "Неверный пароль!";
        }
    }
}


$loggedin = isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true;
?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Учёт сотрудников волейбольного клуба "Легион"</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --cream: #FFFCE6;
            --yellow: #F2CC39;
            --navy: #3C509E;
            --navy-dark: #2a3a75;
            --navy-light: rgba(60, 80, 158, 0.08);
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }

        body {
            font-family: 'Montserrat', sans-serif;
            margin: 0;
            padding: 0;
            background: var(--cream);
            color: var(--navy);
            text-align: center;
            min-height: 100vh;
        }

        /* Subtle geometric background pattern */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background-image:
                radial-gradient(circle at 15% 25%, rgba(242, 204, 57, 0.12) 0%, transparent 50%),
                radial-gradient(circle at 85% 75%, rgba(60, 80, 158, 0.08) 0%, transparent 50%);
            pointer-events: none;
            z-index: 0;
        }

        .header {
            background-color: var(--navy);
            color: var(--cream);
            padding: 18px 40px;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 28px;
            min-height: 120px;
        }

        .header-logo {
            height: 90px;
            width: auto;
            flex-shrink: 0;
            position: relative;
            z-index: 1;
            filter: drop-shadow(0 2px 8px rgba(0, 0, 0, 0.35));
        }

        /* Decorative yellow bar at top */
        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: var(--yellow);
        }

        /* Abstract geometric accent */
        .header::after {
            content: '';
            position: absolute;
            right: -60px;
            bottom: -60px;
            width: 260px;
            height: 260px;
            border-radius: 50%;
            border: 40px solid rgba(242, 204, 57, 0.12);
            pointer-events: none;
        }

        .header h1 {
            margin: 0;
            font-weight: 800;
            font-size: 2.2rem;
            letter-spacing: 1px;
            line-height: 1.25;
            position: relative;
            z-index: 1;
            text-align: left;
        }

        /* Yellow underline accent on title */
        .header h1::after {
            content: '';
            display: block;
            width: 70px;
            height: 4px;
            background: var(--yellow);
            border-radius: 2px;
            margin: 12px 0 0;
        }

        .container {
            padding: 56px 20px 100px;
            position: relative;
            z-index: 1;
        }

        .button-container {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin: 20px 0;
        }

        .button {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 22px 28px;
            font-family: 'Montserrat', sans-serif;
            font-size: 15px;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-align: center;
            color: var(--cream);
            background-color: var(--navy);
            border-radius: 14px;
            box-shadow: 0 4px 16px rgba(60, 80, 158, 0.22);
            transition: all 0.28s ease;
            text-decoration: none;
            text-transform: uppercase;
            position: relative;
            overflow: hidden;
        }

        .button::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--yellow);
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.28s ease;
        }

        .button:hover {
            background-color: var(--navy-dark);
            box-shadow: 0 8px 28px rgba(60, 80, 158, 0.35);
            transform: translateY(-4px);
        }

        .button:hover::before {
            transform: scaleX(1);
        }

        .login-box {
            background: #fff;
            padding: 44px 40px 40px;
            border-radius: 20px;
            box-shadow:
                0 2px 0 var(--yellow),
                0 8px 40px rgba(60, 80, 158, 0.14);
            text-align: center;
            width: 340px;
            margin: auto;
            margin-top: -20px;
            border-top: 5px solid var(--yellow);
            position: relative;
        }

        .login-box h2 {
            font-weight: 800;
            font-size: 1.5rem;
            color: var(--navy);
            margin: 0 0 6px;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .login-box input {
            width: 100%;
            padding: 13px 16px;
            margin: 8px 0;
            border: 2px solid #e8e4d0;
            border-radius: 10px;
            font-family: 'Montserrat', sans-serif;
            font-size: 14px;
            color: var(--navy);
            background: var(--cream);
            outline: none;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .login-box input:focus {
            border-color: var(--yellow);
            box-shadow: 0 0 0 3px rgba(242, 204, 57, 0.25);
        }

        .login-box input::placeholder {
            color: #aaa;
            font-weight: 500;
        }

        .login-box button {
            width: 100%;
            padding: 14px;
            background: var(--yellow);
            color: var(--navy);
            border: none;
            border-radius: 10px;
            font-family: 'Montserrat', sans-serif;
            font-size: 15px;
            font-weight: 800;
            letter-spacing: 1px;
            text-transform: uppercase;
            cursor: pointer;
            margin-top: 8px;
            transition: background 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease;
            box-shadow: 0 4px 14px rgba(242, 204, 57, 0.45);
        }

        .login-box button:hover {
            background: #e6be20;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(242, 204, 57, 0.55);
        }

        .logout-container {
            margin-top: 36px;
        }

        .logout-button {
            background: transparent;
            padding: 12px 36px;
            font-family: 'Montserrat', sans-serif;
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            border: 2px solid var(--navy);
            color: var(--navy);
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.25s ease;
        }

        .logout-button:hover {
            background: var(--navy);
            color: var(--cream);
            box-shadow: 0 4px 18px rgba(60, 80, 158, 0.3);
        }

        .footer {
            background-color: var(--navy);
            color: var(--cream);
            text-align: center;
            padding: 18px 20px;
            position: fixed;
            bottom: 0;
            width: 100%;
            font-size: 13px;
            font-weight: 500;
            letter-spacing: 0.5px;
            border-top: 3px solid var(--yellow);
        }

        .footer p {
            margin: 0;
            opacity: 0.85;
        }
    </style>
</head>

<body>
    <div class="header">
        <img src="ГУ.png" alt="Логотип Легион" class="header-logo">
        <h1>Учёт сотрудников и игроков волейбольного клуба "Легион"</h1>
    </div>

    <div class="container">
        <?php if (!$loggedin): ?>
            <div class="login-box">
                <h2>Вход</h2>
                <img src="Сотрудники.png" alt="Сотрудники" style="width: 100px; height: auto; margin-top: 10px;">
                <form method="POST">
                    <input type="text" name="username" placeholder="Логин" required>
                    <input type="password" name="password" placeholder="Пароль" required>
                    <div style="color: red; font-size: 14px;"><?php echo $login_error; ?></div>
                    <button type="submit">Войти</button>
                </form>
            </div>
        <?php else: ?>
            <div class="button-container">
                <a href="employees.php" class="button">Сотрудники</a>
                <a href="positions.php" class="button">Должности</a>
                <a href="departments.php" class="button">Подразделения</a>
                <a href="vacation.php" class="button">Отпуск</a>
                <a href="schedules.php" class="button">Графики работы</a>
                <a href="sick_leave.php" class="button">Больничный лист</a>
                <a href="attendance.php" class="button">Учёт рабочего времени</a>
                <a href="match_protocols.php" class="button">Протоколы матчей</a>
            </div>
            <div class="logout-container">
                <form method="POST">
                    <button type="submit" name="logout" class="logout-button">Выйти</button>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <div class="footer">
        <p>© 2026 Учёт сотрудников ВК "Легион"</p>
    </div>
</body>

</html>