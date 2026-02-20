<?php
// attendance.php

include('db_connect.php');
include('auth.php');

$conn->set_charset("utf8mb4");

// ==================== ОТМЕТКА ПРИХОДА / УХОДА (только admin) ====================
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    requireAdmin("У вас нет прав на отметку времени");

    if (isset($_POST['check_in'])) {
        $employee_id = intval($_POST['employee_id']);
        $date = $_POST['date'] ?? date('Y-m-d');
        $time_in = $_POST['time_in'] ?? date('H:i:s');

        // Проверка, не отмечался ли уже сегодня
        $check = $conn->prepare("SELECT id FROM CurrentEmployees WHERE employee_id = ? AND date = ?");
        $check->bind_param("is", $employee_id, $date);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows >= 4) {
            $msg = "Сотрудник уже отмечен на приход в этот день!";
        } else {
            $stmt = $conn->prepare("INSERT INTO CurrentEmployees (employee_id, date, time_in) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $employee_id, $date, $time_in);
            $stmt->execute();
            $msg = "Приход успешно зафиксирован!";
        }
        echo "<script>alert('$msg'); window.location='attendance.php';</script>";
        exit();
    }

    if (isset($_POST['check_out'])) {
        $employee_id = intval($_POST['employee_id']);
        $date = $_POST['date'] ?? date('Y-m-d');
        $time_out = $_POST['time_out'] ?? date('H:i:s');

        $stmt = $conn->prepare("UPDATE CurrentEmployees SET time_out = ? WHERE employee_id = ? AND date = ? AND time_out IS NULL ORDER BY id DESC LIMIT 1");
        $stmt->bind_param("sis", $time_out, $employee_id, $date);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            // Пересчитываем часы
            $conn->query("UPDATE CurrentEmployees 
                          SET total_hours = ROUND(TIME_TO_SEC(TIMEDIFF(time_out, time_in)) / 3600, 2) 
                          WHERE employee_id = $employee_id AND date = '$date' AND time_out IS NOT NULL");
            $msg = "Уход зафиксирован!";
        } else {
            $msg = "Не найдена запись о приходе или уход уже отмечен";
        }
        echo "<script>alert('$msg'); window.location='attendance.php';</script>";
        exit();
    }
}

// ==================== ПОИСК И ФИЛЬТРЫ (доступно всем) ====================
$search_name = trim($_GET['search_name'] ?? '');
$search_date = $_GET['search_date'] ?? '';
$departmentFilter = $_GET['department_id'] ?? '';

$where = "WHERE 1=1";
$params = [];
$types = "";

if ($search_name !== '') {
    $where .= " AND e.full_name LIKE ?";
    $params[] = "%$search_name%";
    $types .= "s";
}
if ($search_date !== '') {
    $where .= " AND c.date = ?";
    $params[] = $search_date;
    $types .= "s";
}

if ($departmentFilter !== '') {
    $where .= " AND e.department_id = ?";
    $params[] = (int)$departmentFilter;
    $types .= "i";
}

$query = "SELECT c.id, e.full_name, c.date, c.time_in, c.time_out, c.total_hours
          FROM CurrentEmployees c
          JOIN Employees e ON c.employee_id = e.id
          JOIN Departments d ON e.department_id = d.id
          $where
          ORDER BY c.date DESC, e.full_name";

$stmt = $conn->prepare($query);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// ==================== ЭКСПОРТ В CSV (доступен всем) ====================
if (isset($_GET['export_all'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="attendance_all_' . date("Y-m-d") . '.csv"');
    echo "\xEF\xBB\xBF";

    $out = fopen('php://output', 'w');
    fputcsv($out, ['Сотрудник', 'Дата', 'Приход', 'Уход', 'Часы']);

    $all = $conn->query("SELECT e.full_name, c.date, c.time_in, c.time_out, c.total_hours
                         FROM CurrentEmployees c
                         JOIN Employees e ON c.employee_id = e.id
                         ORDER BY c.date DESC");
    while ($row = $all->fetch_assoc()) {
        fputcsv($out, [
            $row['full_name'],
            $row['date'],
            $row['time_in'] ?? '—',
            $row['time_out'] ?? '—',
            $row['total_hours'] ? round($row['total_hours'], 2) : '—'
        ]);
    }
    fclose($out);
    exit();
}
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Учёт рабочего времени</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* ===== LEGION THEME — shared styles ===== */
        :root {
            --cream: #FFFCE6;
            --yellow: #F2CC39;
            --yellow-dark: #e6be20;
            --navy: #3C509E;
            --navy-dark: #2a3a75;
            --navy-mid: rgba(60, 80, 158, 0.10);
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

        /* ---- page wrapper ---- */
        .container,
        .container-fluid {
            position: relative;
            z-index: 1;
        }

        /* ---- headings ---- */
        h1.text-primary,
        h2.text-primary,
        h3.text-primary {
            color: var(--navy) !important;
            font-weight: 800;
            letter-spacing: .5px;
            position: relative;
            display: inline-block;
        }

        h1.text-primary::after {
            content: '';
            display: block;
            width: 60px;
            height: 4px;
            background: var(--yellow);
            border-radius: 2px;
            margin: 10px auto 0;
        }

        /* ---- buttons: Bootstrap overrides ---- */
        .btn {
            font-family: 'Montserrat', sans-serif !important;
            font-weight: 700;
            letter-spacing: .4px;
            border-radius: 10px !important;
            transition: all .22s ease;
        }

        .btn-primary {
            background: var(--navy) !important;
            border-color: var(--navy) !important;
            color: var(--cream) !important;
        }

        .btn-primary:hover {
            background: var(--navy-dark) !important;
            border-color: var(--navy-dark) !important;
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(60, 80, 158, .30);
        }

        .btn-success {
            background: var(--yellow) !important;
            border-color: var(--yellow) !important;
            color: var(--navy) !important;
        }

        .btn-success:hover {
            background: var(--yellow-dark) !important;
            border-color: var(--yellow-dark) !important;
            color: var(--navy) !important;
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(242, 204, 57, .45);
        }

        .btn-secondary {
            background: transparent !important;
            border: 2px solid var(--navy) !important;
            color: var(--navy) !important;
        }

        .btn-secondary:hover {
            background: var(--navy) !important;
            color: var(--cream) !important;
            transform: translateY(-2px);
        }

        .btn-warning {
            background: #fff3b0 !important;
            border-color: var(--yellow) !important;
            color: var(--navy) !important;
        }

        .btn-warning:hover {
            background: var(--yellow) !important;
            color: var(--navy) !important;
        }

        .btn-danger {
            background: #e74c3c !important;
            border-color: #c0392b !important;
            color: #fff !important;
        }

        .btn-danger:hover {
            background: #c0392b !important;
            border-color: #a93226 !important;
            transform: translateY(-2px);
        }

        .btn-outline-success {
            border-color: var(--navy) !important;
            color: var(--navy) !important;
            background: transparent !important;
        }

        .btn-outline-success:hover {
            background: var(--navy) !important;
            color: var(--cream) !important;
        }

        .btn-outline-warning {
            border-color: var(--yellow) !important;
            color: var(--navy) !important;
            background: transparent !important;
        }

        .btn-outline-warning:hover {
            background: var(--yellow) !important;
            color: var(--navy) !important;
        }

        /* ---- alerts ---- */
        .alert-success {
            background: rgba(242, 204, 57, .25) !important;
            border-color: var(--yellow) !important;
            color: var(--navy) !important;
            font-weight: 600;
            border-radius: 10px;
        }

        .alert-danger {
            background: rgba(231, 76, 60, .12) !important;
            border-color: #e74c3c !important;
            color: #c0392b !important;
            font-weight: 600;
            border-radius: 10px;
        }

        /* ---- form controls ---- */
        .form-control,
        .form-select {
            font-family: 'Montserrat', sans-serif !important;
            font-size: 14px !important;
            font-weight: 500;
            border: 2px solid #e0dcc4 !important;
            border-radius: 10px !important;
            background: #fff !important;
            color: var(--navy) !important;
            transition: border-color .2s, box-shadow .2s;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--yellow) !important;
            box-shadow: 0 0 0 3px rgba(242, 204, 57, .28) !important;
        }

        .form-label {
            font-weight: 600;
            color: var(--navy);
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: .4px;
            margin-bottom: 5px;
        }

        /* ---- cards ---- */
        .card {
            border-radius: 16px !important;
            border: none !important;
            box-shadow: 0 4px 20px rgba(60, 80, 158, .12) !important;
        }

        .card-header {
            border-radius: 16px 16px 0 0 !important;
            font-weight: 700;
            letter-spacing: .4px;
            font-size: 15px;
        }

        .card-header.bg-success {
            background: var(--yellow) !important;
            color: var(--navy) !important;
        }

        .card-header.bg-danger {
            background: #e74c3c !important;
            color: #fff !important;
        }

        .card-header.bg-primary {
            background: var(--navy) !important;
            color: var(--cream) !important;
        }

        .card.border-success {
            border: none !important;
        }

        .card.border-danger {
            border: none !important;
        }

        /* ---- tables ---- */
        .table {
            font-family: 'Montserrat', sans-serif !important;
            font-size: 14px;
            border-radius: 14px;
            overflow: hidden;
        }

        .table-bordered> :not(caption)>*>* {
            border-color: rgba(60, 80, 158, .12) !important;
        }

        .table-primary,
        .thead-primary,
        .table>thead {
            background: var(--navy) !important;
            color: var(--cream) !important;
        }

        .table-primary th {
            background: var(--navy) !important;
            color: var(--cream) !important;
            font-weight: 700;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: .4px;
        }

        .table-striped>tbody>tr:nth-of-type(odd)>* {
            background-color: rgba(60, 80, 158, .04) !important;
        }

        .table-hover tbody tr:hover>* {
            background-color: rgba(242, 204, 57, .18) !important;
        }

        .table-responsive {
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(60, 80, 158, .10);
        }

        /* ---- back button fixed ---- */
        .back-btn {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
        }

        /* ---- print ---- */
        @media print {
            .no-print {
                display: none !important;
            }
        }
    </style>

</head>

<body class="bg-light">

    <div class="container mt-4 mb-5">
        <h1 class="text-center mb-4 text-primary">Учёт рабочего времени</h1>

        <!-- Поиск -->
        <form method="GET" class="mb-4">
            <div class="row g-3">
                <div class="col-md-5">
                    <input type="text" name="search_name" class="form-control" placeholder="ФИО сотрудника" value="<?= htmlspecialchars($search_name) ?>">
                </div>

                <div class="col-md-3">
                    <select name="department_id" class="form-select">
                        <option value="">Все подразделения</option>
                        <?php
                        $depts = $conn->query("SELECT id, name FROM Departments ORDER BY name");
                        while ($d = $depts->fetch_assoc()):
                            $selected = ($d['id'] == $departmentFilter) ? 'selected' : '';
                        ?>
                            <option value="<?= $d['id'] ?>" <?= $selected ?>><?= htmlspecialchars($d['name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <input type="date" name="search_date" class="form-control" value="<?= htmlspecialchars($search_date) ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Найти</button>
                </div>
                <div class="col-md-2">
                    <a href="attendance.php" class="btn btn-secondary w-100">Сбросить</a>
                </div>
            </div>
        </form>

        <div class="text-center mb-4">
            <a href="?export_all=1" class="btn btn-success">Экспорт всей истории в CSV</a>
        </div>

        <?php if (isAdmin()): ?>
            <!-- Формы отметки времени — только для admin -->
            <div class="row mb-5">
                <div class="col-md-6">
                    <div class="card border-success">
                        <div class="card-header bg-success text-white">Отметить приход</div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="mb-3">
                                    <select name="employee_id" class="form-select" required>
                                        <option value="">— Выберите сотрудника —</option>
                                        <?php
                                        $emps = $conn->query("SELECT id, full_name FROM Employees ORDER BY full_name");
                                        while ($e = $emps->fetch_assoc()) {
                                            echo "<option value='{$e['id']}'>" . htmlspecialchars($e['full_name']) . "</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="row">
                                    <div class="col-6"><input type="date" name="date" class="form-control" value="<?= date('Y-m-d') ?>"></div>
                                    <div class="col-6"><input type="time" name="time_in" class="form-control" value="<?= date('H:i') ?>"></div>
                                </div>
                                <button type="submit" name="check_in" class="btn btn-success mt-3 w-100">Пришёл</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card border-danger">
                        <div class="card-header bg-danger text-white">Отметить уход</div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="mb-3">
                                    <select name="employee_id" class="form-select" required>
                                        <option value="">— Выберите сотрудника —</option>
                                        <?php
                                        $emps->data_seek(0);
                                        while ($e = $emps->fetch_assoc()) {
                                            echo "<option value='{$e['id']}'>" . htmlspecialchars($e['full_name']) . "</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="row">
                                    <div class="col-6"><input type="date" name="date" class="form-control" value="<?= date('Y-m-d') ?>"></div>
                                    <div class="col-6"><input type="time" name="time_out" class="form-control" value="<?= date('H:i') ?>"></div>
                                </div>
                                <button type="submit" name="check_out" class="btn btn-danger mt-3 w-100">Ушёл</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- История посещений -->
        <h3 class="text-center mb-3">История посещений</h3>
        <div class="table-responsive">
            <table class="table table-bordered table-hover table-striped">
                <thead class="table-primary">
                    <tr>
                        <th>Сотрудник</th>
                        <th>Дата</th>
                        <th>Приход</th>
                        <th>Уход</th>
                        <th>Часы</th>
                        <?php if (isAdmin()): ?>
                            <th>Действие</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows === 0): ?>
                        <tr>
                            <td colspan="<?= isAdmin() ? 6 : 5 ?>" class="text-center text-muted py-4">Записей не найдено</td>
                        </tr>
                    <?php else: ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['full_name']) ?></td>
                                <td><?= $row['date'] ?></td>
                                <td><?= $row['time_in'] ?? '—' ?></td>
                                <td><?= $row['time_out'] ?? '—' ?></td>
                                <td><?= $row['total_hours'] ? round($row['total_hours'], 2) : '—' ?></td>
                                <?php if (isAdmin()): ?>
                                    <td>
                                        <a href="edit_attendance.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-warning">Редактировать</a>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="text-center mt-5">
            <a href="index.php" class="btn btn-lg btn-secondary px-5">← На главную</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>