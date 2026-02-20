<?php
// schedules.php — Графики работы

include('db_connect.php');
include('auth.php');

$conn->set_charset("utf8mb4");






// ==================== ТЕКТ. ТИП ====================
$filterType = $_GET['type'] ?? 'players';
$allowed_types = ['players', 'staff', 'coaches', 'admin'];
if (!in_array($filterType, $allowed_types)) $filterType = 'players';

$typeLabels = [
    'players' => 'График игроков',
    'coaches' => 'График тренерского штаба',
    'staff'   => 'График персонала',
    'admin'   => 'График администрации',
];
$days = ['Понедельник', 'Вторник', 'Среда', 'Четверг', 'Пятница', 'Суббота', 'Воскресенье'];

// ==================== Загрузить подразделения ====================
$depts = [];
$dr = $conn->query("SELECT id, name FROM Departments ORDER BY name");
while ($d = $dr->fetch_assoc()) $depts[] = $d;

// ==================== ДОБАВЛЕНИЕ WorkSchedules ====================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add']) && isAdmin() && $filterType !== 'admin') {
    $schedule_type = $_POST['schedule_type'] ?? '';
    $day_of_week   = trim($_POST['day_of_week']   ?? '');
    $training_1    = trim($_POST['training_1']    ?? '');
    $training_2    = trim($_POST['training_2']    ?? '');
    $department_id = !empty($_POST['department_id']) ? intval($_POST['department_id']) : null;

    if (!in_array($schedule_type, ['players', 'staff', 'coaches']) || empty($day_of_week) || !in_array($day_of_week, $days)) {
        $error = "Выберите корректный день недели.";
    } else {
        $stmt = $conn->prepare("
            INSERT INTO WorkSchedules 
            (schedule_type, department_id, day_of_week, training_1, training_2) 
            VALUES (?,?,?,?,?)
        ");
        $stmt->bind_param("sisss", $schedule_type, $department_id, $day_of_week, $training_1, $training_2);

        if ($stmt->execute()) {
            header("Location: schedules.php?type=$filterType&success=added");
            exit();
        } else {
            $error = "Ошибка добавления: " . $stmt->error;
        }
    }
}

// ==================== РЕДАКТИРОВАНИЕ WorkSchedules ====================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['edit']) && isAdmin() && $filterType !== 'admin') {
    $edit_id       = intval($_POST['edit_id']);
    $training_1    = trim($_POST['training_1'] ?? '');
    $training_2    = trim($_POST['training_2'] ?? '');
    $day_of_week   = trim($_POST['day_of_week'] ?? '');
    $department_id = !empty($_POST['department_id']) ? intval($_POST['department_id']) : null;

    if (empty($day_of_week)) {
        $error = "День недели обязателен.";
    } else {
        $stmt = $conn->prepare("
            UPDATE WorkSchedules 
            SET department_id = ?, day_of_week = ?, training_1 = ?, training_2 = ? 
            WHERE id = ?
        ");
        $stmt->bind_param("isssi", $department_id, $day_of_week, $training_1, $training_2, $edit_id);

        if ($stmt->execute()) {
            header("Location: schedules.php?type=$filterType&success=updated");
            exit();
        } else {
            $error = "Ошибка обновления: " . $stmt->error;
        }
    }
}

// ==================== УДАЛЕНИЕ WorkSchedules ====================
if (isset($_GET['delete']) && isAdmin() && $filterType !== 'admin') {
    $del_id = intval($_GET['delete']);
    $conn->query("DELETE FROM WorkSchedules WHERE id = $del_id");
    header("Location: schedules.php?type=$filterType&success=deleted");
    exit();
}

// ==================== ДОБАВЛЕНИЕ AdminSchedules ====================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add_admin']) && isAdmin()) {
    $sdate  = $_POST['schedule_date'] ?? '';
    $status = $_POST['status'] ?? 'Рабочий';
    if (!in_array($status, ['Рабочий', 'Выходной']) || empty($sdate)) {
        $error = "Заполните все поля.";
    } else {
        $stmt = $conn->prepare("INSERT INTO AdminSchedules (schedule_date, status) VALUES (?,?)");
        $stmt->bind_param("ss", $sdate, $status);
        if ($stmt->execute()) {
            header("Location: schedules.php?type=admin&success=added");
            exit();
        } else $error = "Ошибка добавления: " . $conn->error;
    }
}

// ==================== РЕДАКТИРОВАНИЕ AdminSchedules ====================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['edit_admin']) && isAdmin()) {
    $edit_id = intval($_POST['edit_id']);
    $sdate   = $_POST['schedule_date'] ?? '';
    $status  = $_POST['status'] ?? 'Рабочий';
    $stmt = $conn->prepare("UPDATE AdminSchedules SET schedule_date=?, status=? WHERE id=?");
    $stmt->bind_param("ssi", $sdate, $status, $edit_id);
    if ($stmt->execute()) {
        header("Location: schedules.php?type=admin&success=updated");
        exit();
    } else $error = "Ошибка обновления: " . $conn->error;
}

// ==================== УДАЛЕНИЕ AdminSchedules ====================
if (isset($_GET['delete_admin']) && isAdmin()) {
    $del_id = intval($_GET['delete_admin']);
    $conn->query("DELETE FROM AdminSchedules WHERE id = $del_id");
    header("Location: schedules.php?type=admin&success=deleted");
    exit();
}

// ==================== ВЫБОРКА WorkSchedules ====================
$filterDept = intval($_GET['dept'] ?? 0);
$result = null;
if ($filterType !== 'admin') {
    $where  = "WHERE schedule_type = ?";
    $params = [$filterType];
    $ptypes = "s";
    if ($filterDept > 0) {
        $where .= " AND department_id = ?";
        $params[] = $filterDept;
        $ptypes .= "i";
    }
    $stmt = $conn->prepare("
        SELECT ws.*, d.name AS dept_name
        FROM WorkSchedules ws
        LEFT JOIN Departments d ON ws.department_id = d.id
        $where
        ORDER BY FIELD(ws.day_of_week,'Понедельник','Вторник','Среда','Четверг','Пятница','Суббота','Воскресенье')
    ");
    $stmt->bind_param($ptypes, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
}

// ==================== ВЫБОРКА AdminSchedules ====================
$adminResult = null;
if ($filterType === 'admin') {
    $adminResult = $conn->query("SELECT * FROM AdminSchedules ORDER BY schedule_date ASC");
}

// Форма редактирования
$editRow = null;
if (isset($_GET['edit_id']) && isAdmin()) {
    $eid = intval($_GET['edit_id']);
    if ($filterType === 'admin') {
        $r = $conn->prepare("SELECT * FROM AdminSchedules WHERE id=?");
    } else {
        $r = $conn->prepare("SELECT * FROM WorkSchedules WHERE id=?");
    }
    $r->bind_param("i", $eid);
    $r->execute();
    $editRow = $r->get_result()->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Графики работы</title>
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

        .container {
            position: relative;
            z-index: 1;
        }

        /* ---- Page title ---- */
        .page-title {
            font-weight: 800;
            font-size: 2rem;
            color: var(--navy);
            letter-spacing: .5px;
        }

        .page-title::after {
            content: '';
            display: block;
            width: 60px;
            height: 4px;
            background: var(--yellow);
            border-radius: 2px;
            margin-top: 10px;
        }

        /* ---- Tab switcher ---- */
        .schedule-tabs {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 28px;
        }

        .schedule-tab {
            padding: 10px 22px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 13px;
            letter-spacing: .4px;
            text-transform: uppercase;
            text-decoration: none;
            border: 2px solid var(--navy);
            color: var(--navy);
            background: transparent;
            transition: all .22s ease;
        }

        .schedule-tab:hover,
        .schedule-tab.active {
            background: var(--navy);
            color: var(--cream);
            box-shadow: 0 4px 16px rgba(60, 80, 158, .28);
            transform: translateY(-2px);
        }

        /* ---- Filter bar ---- */
        .filter-bar {
            background: #fff;
            border-radius: 14px;
            padding: 18px 24px;
            box-shadow: 0 4px 20px rgba(60, 80, 158, .10);
            margin-bottom: 24px;
        }

        /* ---- Cards ---- */
        .card {
            border: none !important;
            border-radius: 16px !important;
            box-shadow: 0 4px 20px rgba(60, 80, 158, .11) !important;
            overflow: hidden;
        }

        .card-header {
            border-radius: 0 !important;
            font-weight: 700;
            letter-spacing: .5px;
            font-size: 15px;
            padding: 18px 24px !important;
        }

        .card-header.hdr-add {
            background: var(--yellow) !important;
            color: var(--navy) !important;
            border-top: 4px solid var(--navy) !important;
        }

        .card-header.hdr-edit {
            background: var(--navy) !important;
            color: var(--cream) !important;
            border-top: 4px solid var(--yellow) !important;
        }

        .card-body {
            background: #fff;
            padding: 24px 28px !important;
        }

        /* ---- Table ---- */
        .table {
            font-family: 'Montserrat', sans-serif !important;
            font-size: 14px;
        }

        .table>thead th {
            background: var(--navy) !important;
            color: var(--cream) !important;
            font-weight: 700;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .5px;
            border: none !important;
            padding: 14px 16px !important;
        }

        .table-responsive {
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(60, 80, 158, .10);
        }

        .table-bordered> :not(caption)>*>* {
            border-color: rgba(60, 80, 158, .10) !important;
        }

        .table tbody tr:hover>* {
            background-color: rgba(242, 204, 57, .15) !important;
        }

        .table tbody tr:nth-of-type(odd)>* {
            background-color: rgba(60, 80, 158, .03);
        }

        /* ---- Badges ---- */
        .day-badge {
            display: inline-block;
            background: var(--navy);
            color: var(--cream);
            padding: 4px 12px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 700;
        }

        .day-badge.weekend {
            background: var(--yellow);
            color: var(--navy);
        }

        .status-badge {
            display: inline-block;
            padding: 5px 14px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .3px;
        }

        .badge-work {
            background: rgba(60, 80, 158, .12);
            color: var(--navy);
        }

        .badge-holiday {
            background: rgba(242, 204, 57, .45);
            color: var(--navy);
        }

        .dept-badge {
            display: inline-block;
            background: rgba(60, 80, 158, .10);
            color: var(--navy);
            padding: 3px 10px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
        }

        .training-chip {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            margin: 2px 0;
        }

        .chip-1 {
            background: rgba(60, 80, 158, .12);
            color: var(--navy);
        }

        .chip-2 {
            background: rgba(242, 204, 57, .35);
            color: var(--navy);
        }

        .chip-empty {
            color: #bbb;
            font-style: italic;
            font-size: 12px;
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
            padding: 10px 14px !important;
            transition: border-color .2s, box-shadow .2s;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--yellow) !important;
            box-shadow: 0 0 0 3px rgba(242, 204, 57, .28) !important;
            outline: none;
        }

        .form-label {
            font-weight: 700;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .5px;
            color: var(--navy);
            margin-bottom: 5px;
        }

        /* ---- Alerts ---- */
        .alert-success {
            background: rgba(242, 204, 57, .25) !important;
            border-color: var(--yellow) !important;
            color: var(--navy) !important;
            font-weight: 600;
            border-radius: 10px;
        }

        .alert-danger {
            background: rgba(231, 76, 60, .10) !important;
            border-color: #e74c3c !important;
            color: #c0392b !important;
            font-weight: 600;
            border-radius: 10px;
        }

        /* ---- Buttons ---- */
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
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(60, 80, 158, .30);
        }

        .btn-success {
            background: var(--yellow) !important;
            border-color: var(--yellow) !important;
            color: var(--navy) !important;
            box-shadow: 0 4px 12px rgba(242, 204, 57, .35);
        }

        .btn-success:hover {
            background: var(--yellow-dark) !important;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: transparent !important;
            border: 2px solid var(--navy) !important;
            color: var(--navy) !important;
        }

        .btn-secondary:hover {
            background: var(--navy) !important;
            color: var(--cream) !important;
        }

        .btn-warning {
            background: #fff3b0 !important;
            border-color: var(--yellow) !important;
            color: var(--navy) !important;
        }

        .btn-warning:hover {
            background: var(--yellow) !important;
        }

        .btn-danger {
            background: #e74c3c !important;
            border-color: #c0392b !important;
            color: #fff !important;
        }

        .btn-danger:hover {
            background: #c0392b !important;
            transform: translateY(-2px);
        }

        .btn-sm {
            padding: 5px 12px !important;
            font-size: 12px !important;
        }
    </style>
</head>

<body>
    <div class="container py-4 mb-5">

        <!-- Page header -->
        <div class="d-flex align-items-start justify-content-between mb-4 flex-wrap gap-3">
            <div>
                <div class="page-title">Графики работы</div>
            </div>
            <a href="index.php" class="btn btn-secondary">← На главную</a>
        </div>

        <!-- Alerts -->
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <?php $msgs = ['added' => 'Запись добавлена', 'deleted' => 'Запись удалена', 'updated' => 'Запись обновлена'];
                echo $msgs[$_GET['success']] ?? 'Готово'; ?>
            </div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Schedule type tabs -->
        <div class="schedule-tabs">
            <?php foreach ($typeLabels as $key => $label): ?>
                <a href="?type=<?= $key ?>"
                    class="schedule-tab <?= $filterType === $key ? 'active' : '' ?>">
                    <?= $label ?>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- БЛОК: ИГРОКИ / ПЕРСОНАЛ / ТРЕНЕРЫ -->
        <?php if ($filterType !== 'admin'): ?>

            <!-- Filter by department -->
            <div class="filter-bar">
                <form method="GET" class="row g-3 align-items-end">
                    <input type="hidden" name="type" value="<?= htmlspecialchars($filterType) ?>">
                    <div class="col-md-5">
                        <label class="form-label">Фильтр по подразделению</label>
                        <select name="dept" class="form-select">
                            <option value="0">Все подразделения</option>
                            <?php foreach ($depts as $d): ?>
                                <option value="<?= $d['id'] ?>" <?= $filterDept == $d['id'] ? 'selected' : '' ?>><?= htmlspecialchars($d['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary w-100">Применить</button>
                    </div>
                    <div class="col-md-2">
                        <a href="?type=<?= $filterType ?>" class="btn btn-secondary w-100">Сбросить</a>
                    </div>
                </form>
            </div>

            <!-- ADD form -->
            <?php if (isAdmin() && !$editRow): ?>
                <div class="card mb-4">
                    <div class="card-header hdr-add">Добавить запись — <?= $typeLabels[$filterType] ?></div>
                    <div class="card-body">
                        <form method="POST" class="row g-3">
                            <input type="hidden" name="add" value="1">
                            <input type="hidden" name="schedule_type" value="<?= htmlspecialchars($filterType) ?>">

                            <div class="col-md-3">
                                <label class="form-label">Подразделение</label>
                                <select name="department_id" class="form-select">
                                    <option value="">— Выберите —</option>
                                    <?php foreach ($depts as $d): ?>
                                        <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">День недели</label>
                                <select name="day_of_week" class="form-select">
                                    <option value="">— Выберите —</option>
                                    <?php foreach ($days as $d): ?>
                                        <option value="<?= $d ?>"><?= $d ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Тренировка 1</label>
                                <input type="text" name="training_1" class="form-control" placeholder="напр. 09:00 – 10:30">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Тренировка 2</label>
                                <input type="text" name="training_2" class="form-control" placeholder="напр. 18:00 – 20:00">
                            </div>

                            <div class="col-12">
                                <button type="submit" class="btn btn-success">Добавить</button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

            <!-- EDIT form -->
            <?php if ($editRow && isAdmin()): ?>
                <div class="card mb-4">
                    <div class="card-header hdr-edit">Редактирование записи</div>
                    <div class="card-body">
                        <form method="POST" class="row g-3">
                            <input type="hidden" name="edit" value="1">
                            <input type="hidden" name="edit_id" value="<?= $editRow['id'] ?>">

                            <div class="col-md-3">
                                <label class="form-label">Подразделение</label>
                                <select name="department_id" class="form-select">
                                    <option value="">— Выберите —</option>
                                    <?php foreach ($depts as $d): ?>
                                        <option value="<?= $d['id'] ?>" <?= ($editRow['department_id'] == $d['id']) ? 'selected' : '' ?>><?= htmlspecialchars($d['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">День недели</label>
                                <select name="day_of_week" class="form-select">
                                    <?php foreach ($days as $d): ?>
                                        <option value="<?= $d ?>" <?= $editRow['day_of_week'] === $d ? 'selected' : '' ?>><?= $d ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Тренировка 1</label>
                                <input type="text" name="training_1" class="form-control" value="<?= htmlspecialchars($editRow['training_1'] ?? '') ?>">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Тренировка 2</label>
                                <input type="text" name="training_2" class="form-control" value="<?= htmlspecialchars($editRow['training_2'] ?? '') ?>">
                            </div>

                            <div class="col-12 d-flex gap-2">
                                <button type="submit" class="btn btn-success">Сохранить изменения</button>
                                <a href="?type=<?= $filterType ?>" class="btn btn-secondary">Отмена</a>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

            <!-- TABLE: игроки/персонал/тренеры -->
            <h5 style="font-weight:800;font-size:1.05rem;margin-bottom:14px;">
                <?= $typeLabels[$filterType] ?>
                <?php if ($filterDept > 0):
                    $dn = '';
                    foreach ($depts as $dd) {
                        if ($dd['id'] == $filterDept) {
                            $dn = $dd['name'];
                            break;
                        }
                    }
                ?>
                    — <span style="background:var(--navy);color:var(--cream);padding:2px 12px;border-radius:50px;font-size:13px;"><?= htmlspecialchars($dn) ?></span>
                <?php endif; ?>
            </h5>

            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0">
                    <thead>
                        <tr>
                            <th style="width:50px">#</th>
                            <th>Подразделение</th>
                            <th>День недели</th>
                            <th>Тренировка 1</th>
                            <th>Тренировка 2</th>
                            <?php if (isAdmin()): ?>
                                <th style="width:160px" class="text-center">Действия</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows === 0): ?>
                            <tr>
                                <td colspan="<?= isAdmin() ? 6 : 5 ?>" class="text-center py-5" style="color:#aaa;font-size:14px;">
                                    Нет записей<?= isAdmin() ? ' — добавьте первую запись выше' : '' ?>
                                </td>
                            </tr>
                            <?php else: while ($row = $result->fetch_assoc()):
                                $isWeekend = in_array($row['day_of_week'], ['Суббота', 'Воскресенье']); ?>
                                <tr>
                                    <td class="text-center" style="font-weight:700;color:#aaa;"><?= $row['id'] ?></td>
                                    <td>
                                        <?php if (!empty($row['dept_name'])): ?>
                                            <span class="dept-badge"><?= htmlspecialchars($row['dept_name']) ?></span>
                                        <?php else: ?>
                                            <span class="chip-empty">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="day-badge <?= $isWeekend ? 'weekend' : '' ?>">
                                            <?= htmlspecialchars($row['day_of_week']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?= !empty($row['training_1'])
                                            ? '<span class="training-chip chip-1">🏐 ' . htmlspecialchars($row['training_1']) . '</span>'
                                            : '<span class="chip-empty">—</span>' ?>
                                    </td>
                                    <td>
                                        <?= !empty($row['training_2'])
                                            ? '<span class="training-chip chip-2">🏐 ' . htmlspecialchars($row['training_2']) . '</span>'
                                            : '<span class="chip-empty">—</span>' ?>
                                    </td>
                                    <?php if (isAdmin()): ?>
                                        <td class="text-center">
                                            <a href="?type=<?= $filterType ?>&edit_id=<?= $row['id'] ?>" class="btn btn-sm btn-warning me-1">Изменить</a>
                                            <a href="?type=<?= $filterType ?>&delete=<?= $row['id'] ?>" class="btn btn-sm btn-danger"
                                                onclick="return confirm('Удалить эту запись?')">Удалить</a>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                        <?php endwhile;
                        endif; ?>
                    </tbody>
                </table>
            </div>

        <?php endif; /* end non-admin types */ ?>


        <!-- БЛОК: ГРАФИК АДМИНИСТРАЦИИ -->
        <?php if ($filterType === 'admin'): ?>

            <!-- ADD admin -->
            <?php if (isAdmin() && !$editRow): ?>
                <div class="card mb-4">
                    <div class="card-header hdr-add">Добавить запись — График администрации</div>
                    <div class="card-body">
                        <form method="POST" class="row g-3">
                            <input type="hidden" name="add_admin" value="1">
                            <div class="col-md-4">
                                <label class="form-label">Дата</label>
                                <input type="date" name="schedule_date" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Значение</label>
                                <select name="status" class="form-select" required>
                                    <option value="Рабочий">Рабочий</option>
                                    <option value="Выходной">Выходной</option>
                                </select>
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button type="submit" class="btn btn-success w-100">Добавить</button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

            <!-- EDIT admin -->
            <?php if ($editRow && isAdmin()): ?>
                <div class="card mb-4">
                    <div class="card-header hdr-edit">Редактирование записи</div>
                    <div class="card-body">
                        <form method="POST" class="row g-3">
                            <input type="hidden" name="edit_admin" value="1">
                            <input type="hidden" name="edit_id" value="<?= $editRow['id'] ?>">
                            <div class="col-md-4">
                                <label class="form-label">Дата</label>
                                <input type="date" name="schedule_date" class="form-control" value="<?= $editRow['schedule_date'] ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Значение</label>
                                <select name="status" class="form-select">
                                    <option value="Рабочий" <?= $editRow['status'] === 'Рабочий' ? 'selected' : '' ?>>Рабочий</option>
                                    <option value="Выходной" <?= $editRow['status'] === 'Выходной' ? 'selected' : '' ?>>Выходной</option>
                                </select>
                            </div>
                            <div class="col-md-4 d-flex align-items-end gap-2">
                                <button type="submit" class="btn btn-success">Сохранить</button>
                                <a href="?type=admin" class="btn btn-secondary">Отмена</a>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

            <!-- TABLE: admin -->
            <h5 style="font-weight:800;font-size:1.05rem;margin-bottom:14px;">График администрации</h5>

            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0">
                    <thead>
                        <tr>
                            <th style="width:50px">#</th>
                            <th>Дата</th>
                            <th>Значение</th>
                            <?php if (isAdmin()): ?>
                                <th style="width:160px" class="text-center">Действия</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($adminResult->num_rows === 0): ?>
                            <tr>
                                <td colspan="<?= isAdmin() ? 4 : 3 ?>" class="text-center py-5" style="color:#aaa;font-size:14px;">
                                    Нет записей<?= isAdmin() ? ' — добавьте первую запись выше' : '' ?>
                                </td>
                            </tr>
                            <?php else: while ($row = $adminResult->fetch_assoc()): ?>
                                <tr>
                                    <td class="text-center" style="font-weight:700;color:#aaa;"><?= $row['id'] ?></td>
                                    <td style="font-weight:600;">
                                        <?= date('d.m.Y', strtotime($row['schedule_date'])) ?>
                                        <span style="font-size:11px;color:#888;margin-left:6px;">
                                            <?= ['Mon' => 'Пн', 'Tue' => 'Вт', 'Wed' => 'Ср', 'Thu' => 'Чт', 'Fri' => 'Пт', 'Sat' => 'Сб', 'Sun' => 'Вс'][date('D', strtotime($row['schedule_date']))] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge <?= $row['status'] === 'Рабочий' ? 'badge-work' : 'badge-holiday' ?>">
                                            <?= $row['status'] === 'Рабочий' ? ' Рабочий' : ' Выходной' ?>
                                        </span>
                                    </td>
                                    <?php if (isAdmin()): ?>
                                        <td class="text-center">
                                            <a href="?type=admin&edit_id=<?= $row['id'] ?>" class="btn btn-sm btn-warning me-1">Изменить</a>
                                            <a href="?type=admin&delete_admin=<?= $row['id'] ?>" class="btn btn-sm btn-danger"
                                                onclick="return confirm('Удалить эту запись?')">Удалить</a>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                        <?php endwhile;
                        endif; ?>
                    </tbody>
                </table>
            </div>

        <?php endif; /* end admin block */ ?>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>