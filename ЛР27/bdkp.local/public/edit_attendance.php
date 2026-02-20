<?php
include('db_connect.php');
include('auth.php');
requireAdmin();

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Ошибка: ID записи не указан.");
}

$id = $_GET['id'];

// Получение данных записи
$query = "SELECT * FROM CurrentEmployees WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$record = $result->fetch_assoc();

if (!$record) {
    die("Ошибка: Запись не найдена.");
}

// Обновление записи
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $time_in = $_POST['time_in'];
    $time_out = $_POST['time_out'];

    // Пересчет часов
    $update_query = "UPDATE CurrentEmployees 
    SET time_in = ?, 
        time_out = ?, 
        total_hours = ROUND(TIME_TO_SEC(TIMEDIFF(?, ?)) / 3600, 2) 
    WHERE id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("ssssi", $time_in, $time_out, $time_out, $time_in, $id);




    if ($stmt->execute()) {
        echo "<script>alert('Запись обновлена!'); window.location.href='attendance.php';</script>";
    } else {
        echo "<script>alert('Ошибка: " . $stmt->error . "');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактирование записи</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --cream: #FFFCE6;
            --yellow: #F2CC39;
            --yellow-dark: #e6be20;
            --navy: #3C509E;
            --navy-dark: #2a3a75;
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }

        body {
            font-family: 'Montserrat', sans-serif !important;
            background: var(--cream) !important;
            color: var(--navy) !important;
            min-height: 100vh;
        }

        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background-image:
                radial-gradient(circle at 10% 20%, rgba(242, 204, 57, .10) 0%, transparent 50%),
                radial-gradient(circle at 90% 80%, rgba(60, 80, 158, .07) 0%, transparent 50%);
            pointer-events: none;
            z-index: 0;
        }

        .container,
        .container-fluid {
            position: relative;
            z-index: 1;
        }

        /* ---- Card ---- */
        .card {
            border: none !important;
            border-radius: 20px !important;
            box-shadow: 0 8px 40px rgba(60, 80, 158, .14) !important;
            overflow: hidden;
        }

        .card-header {
            border-radius: 0 !important;
            font-family: 'Montserrat', sans-serif !important;
            font-weight: 700;
            letter-spacing: .5px;
            font-size: 16px;
            padding: 20px 28px !important;
        }

        .card-header.bg-primary {
            background: var(--navy) !important;
            color: var(--cream) !important;
            border-top: 5px solid var(--yellow) !important;
        }

        .card-header h3,
        .card-header h4 {
            margin: 0;
            font-weight: 800;
            font-size: inherit;
        }

        .card-body {
            background: #fff;
            padding: 32px 36px !important;
        }

        /* ---- Typography ---- */
        h2.text-center {
            font-family: 'Montserrat', sans-serif !important;
            font-weight: 800;
            color: var(--navy);
            margin-bottom: 24px;
            position: relative;
        }

        h2.text-center::after {
            content: '';
            display: block;
            width: 50px;
            height: 4px;
            background: var(--yellow);
            border-radius: 2px;
            margin: 10px auto 0;
        }

        /* ---- Form controls ---- */
        .form-control,
        .form-select {
            font-family: 'Montserrat', sans-serif !important;
            font-size: 14px !important;
            font-weight: 500;
            border: 2px solid #e0dcc4 !important;
            border-radius: 10px !important;
            background: var(--cream) !important;
            color: var(--navy) !important;
            padding: 11px 14px !important;
            transition: border-color .2s, box-shadow .2s;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--yellow) !important;
            box-shadow: 0 0 0 3px rgba(242, 204, 57, .28) !important;
            outline: none;
        }

        .form-control:disabled {
            background: #f0eddb !important;
            opacity: .7;
        }

        .form-label,
        label {
            font-family: 'Montserrat', sans-serif !important;
            font-weight: 700;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .5px;
            color: var(--navy);
            margin-bottom: 6px;
            display: block;
        }

        /* ---- Alerts ---- */
        .alert-danger {
            background: rgba(231, 76, 60, .10) !important;
            border-color: #e74c3c !important;
            color: #c0392b !important;
            font-weight: 600;
            border-radius: 10px;
            font-family: 'Montserrat', sans-serif !important;
        }

        .alert-success {
            background: rgba(242, 204, 57, .25) !important;
            border-color: var(--yellow) !important;
            color: var(--navy) !important;
            font-weight: 600;
            border-radius: 10px;
            font-family: 'Montserrat', sans-serif !important;
        }

        /* ---- Buttons ---- */
        .btn {
            font-family: 'Montserrat', sans-serif !important;
            font-weight: 700;
            letter-spacing: .5px;
            border-radius: 10px !important;
            padding: 11px 28px !important;
            transition: all .22s ease;
            font-size: 14px !important;
        }

        .btn-success,
        .btn-success.btn-lg {
            background: var(--yellow) !important;
            border-color: var(--yellow) !important;
            color: var(--navy) !important;
            box-shadow: 0 4px 14px rgba(242, 204, 57, .40);
        }

        .btn-success:hover {
            background: var(--yellow-dark) !important;
            border-color: var(--yellow-dark) !important;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(242, 204, 57, .55);
        }

        .btn-secondary,
        .btn-secondary.btn-lg,
        .btn-outline-secondary {
            background: transparent !important;
            border: 2px solid var(--navy) !important;
            color: var(--navy) !important;
        }

        .btn-secondary:hover,
        .btn-outline-secondary:hover {
            background: var(--navy) !important;
            color: var(--cream) !important;
            transform: translateY(-2px);
        }

        .btn-lg {
            font-size: 15px !important;
            padding: 13px 34px !important;
        }

        /* ---- shadow-lg override ---- */
        .shadow-lg,
        .shadow {
            box-shadow: 0 8px 40px rgba(60, 80, 158, .14) !important;
        }
    </style>
</head>

<body class="bg-light">
    <div class="container py-5">
        <div class="card shadow-lg p-4">
            <h2 class="text-center">Редактирование записи</h2>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Дата</label>
                    <input type="date" class="form-control" value="<?= $record['date'] ?>" disabled>
                </div>
                <div class="mb-3">
                    <label class="form-label">Время прихода</label>
                    <input type="time" name="time_in" class="form-control" value="<?= $record['time_in'] ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Время ухода</label>
                    <input type="time" name="time_out" class="form-control" value="<?= $record['time_out'] ?>" required>
                </div>
                <button type="submit" class="btn btn-success">Сохранить</button>
                <a href="attendance.php" class="btn btn-secondary">Отмена</a>
            </form>
        </div>
    </div>
</body>

</html>