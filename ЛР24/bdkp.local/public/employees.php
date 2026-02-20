<?php
include('db_connect.php');
include('auth.php');

// ==================== ДОБАВЛЕНИЕ СОТРУДНИКА ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_employee'])) {
    requireAdmin();

    $full_name         = trim($_POST['full_name']);
    $birth_date        = $_POST['birth_date'];
    $gender            = $_POST['gender'];
    $department_id     = (int)$_POST['department_id'];
    $position_id       = (int)$_POST['position_id'];
    $rate              = (float)$_POST['rate'];
    $contract_number   = trim($_POST['contract_number']);
    $hire_date         = $_POST['hire_date'];
    $contract_end_date = $_POST['contract_end_date'];
    $education         = $_POST['education'];

    if (
        empty($full_name) || empty($birth_date) || empty($gender) || $department_id <= 0 ||
        $position_id <= 0 || $rate <= 0 || empty($contract_number) || empty($hire_date) ||
        empty($contract_end_date) || empty($education)
    ) {
        $error = "Все поля обязательны.";
    } elseif (strtotime($contract_end_date) <= strtotime($hire_date)) {
        $error = "Дата окончания контракта должна быть позже даты приёма!";
    } else {
        $check = $conn->prepare("SELECT id FROM Employees WHERE contract_number = ?");
        $check->bind_param("s", $contract_number);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $error = "Номер контракта уже существует!";
        } else {
            $stmt = $conn->prepare("
                INSERT INTO Employees 
                (full_name, birth_date, gender, department_id, position_id, rate, 
                 contract_number, hire_date, contract_end_date, education)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param(
                "sssiidssss",
                $full_name,
                $birth_date,
                $gender,
                $department_id,
                $position_id,
                $rate,
                $contract_number,
                $hire_date,
                $contract_end_date,
                $education
            );

            if ($stmt->execute()) {
                header("Location: employees.php?success=added");
                exit();
            } else {
                $error = "Ошибка добавления: " . $conn->error;
            }
        }
    }
}

// ==================== УДАЛЕНИЕ ====================
if (isset($_POST['delete_id']) && isAdmin()) {
    $deleteId = (int)$_POST['delete_id'];
    $conn->query("DELETE FROM Employees WHERE id = $deleteId");
    header("Location: employees.php?success=deleted");
    exit();
}

// ==================== ВЫВОД СПИСКА ====================
$search = trim($_GET['search'] ?? '');
$positionFilter   = $_GET['position_id'] ?? '';
$departmentFilter = $_GET['department_id'] ?? '';

$query = "
    SELECT e.*, 
           d.name AS department_name, 
           p.name AS position_name
    FROM Employees e
    JOIN Departments d ON e.department_id = d.id
    JOIN Positions p ON e.position_id = p.id
    WHERE 1=1";

$params = [];
$types = "";

if ($search) {
    $query .= " AND (e.full_name LIKE ? OR e.contract_number LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= "ss";
}
if ($positionFilter) {
    $query .= " AND e.position_id = ?";
    $params[] = (int)$positionFilter;
    $types .= "i";
}
if ($departmentFilter) {
    $query .= " AND e.department_id = ?";
    $params[] = (int)$departmentFilter;
    $types .= "i";
}

$query .= " ORDER BY e.full_name";

$stmt = $conn->prepare($query);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Сотрудники</title>
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

    <div class="container py-4">
        <h1 class="text-center text-primary mb-4">Сотрудники</h1>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <?= $_GET['success'] === 'added' ? 'Сотрудник успешно добавлен' : 'Сотрудник удалён' ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="mb-3 text-end no-print">
            <button onclick="window.print()" class="btn btn-primary"> Печать отчета</button>
        </div>

        <!-- Форма поиска -->
        <form method="GET" class="mb-4 no-print">
            <div class="row g-3">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control"
                        placeholder="Поиск по ФИО или номеру контракта"
                        value="<?= htmlspecialchars($search) ?>">
                </div>

                <div class="col-md-3">
                    <select name="department_id" class="form-select">
                        <option value="">Все подразделения</option>
                        <?php
                        $depts = $conn->query("SELECT id, name FROM Departments ORDER BY name");
                        while ($d = $depts->fetch_assoc()):
                            $sel = $d['id'] == $departmentFilter ? 'selected' : '';
                        ?>
                            <option value="<?= $d['id'] ?>" <?= $sel ?>><?= htmlspecialchars($d['name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="position_id" class="form-select">
                        <option value="">Все должности</option>
                        <?php
                        $poss = $conn->query("SELECT id, name FROM Positions ORDER BY name");
                        while ($p = $poss->fetch_assoc()):
                            $sel = $p['id'] == $positionFilter ? 'selected' : '';
                        ?>
                            <option value="<?= $p['id'] ?>" <?= $sel ?>><?= htmlspecialchars($p['name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Применить</button>
                </div>
            </div>
        </form>

        <!-- Форма добавления -->
        <?php if (isAdmin()): ?>
            <div class="card mb-5 shadow no-print">
                <div class="card-header bg-success text-white">
                    <h5>Добавить сотрудника</h5>
                </div>
                <div class="card-body">
                    <form method="POST" class="row g-3">
                        <input type="hidden" name="add_employee" value="1">

                        <div class="col-md-6">
                            <label class="form-label">ФИО</label>
                            <input type="text" name="full_name" class="form-control" required>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Дата рождения</label>
                            <input type="date" name="birth_date" class="form-control" required>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Пол</label>
                            <select name="gender" class="form-select" required>
                                <option value="M">Мужской</option>
                                <option value="F">Женский</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Подразделение</label>
                            <select name="department_id" class="form-select" required>
                                <option value="">— Выберите —</option>
                                <?php
                                $depts = $conn->query("SELECT id, name FROM Departments ORDER BY name");
                                while ($d = $depts->fetch_assoc()) {
                                    echo "<option value='{$d['id']}'>" . htmlspecialchars($d['name']) . "</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Должность</label>
                            <select name="position_id" class="form-select" required>
                                <option value="">— Выберите —</option>
                                <?php
                                $poss = $conn->query("SELECT id, name FROM Positions ORDER BY name");
                                while ($p = $poss->fetch_assoc()) {
                                    echo "<option value='{$p['id']}'>" . htmlspecialchars($p['name']) . "</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Ставка</label>
                            <input type="number" step="0.01" min="0.01" name="rate" class="form-control" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Номер контракта</label>
                            <input type="text" name="contract_number" class="form-control" required>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Дата приёма на работу</label>
                            <input type="date" name="hire_date" class="form-control" required>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Дата окончания контракта</label>
                            <input type="date" name="contract_end_date" class="form-control" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Образование</label>
                            <select name="education" class="form-select" required>
                                <option value="высшее">Высшее</option>
                                <option value="среднее">Среднее</option>
                                <option value="базовое">Базовое</option>
                                <option value="среднее специальное">Среднее специальное</option>
                            </select>
                        </div>

                        <div class="col-12 text-center">
                            <button type="submit" class="btn btn-success btn-lg">Добавить сотрудника</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <!-- Таблица -->
        <div class="table-responsive">
            <table class="table table-bordered table-hover table-striped">
                <thead class="table-primary">
                    <tr>
                        <th>ID</th>
                        <th>ФИО</th>
                        <th>Дата рождения</th>
                        <th>Пол</th>
                        <th>Подразделение</th>
                        <th>Должность</th>
                        <th>Ставка</th>
                        <th>Номер контракта</th>
                        <th>Дата приёма</th>
                        <th>Дата окончания контракта</th>
                        <th>Образование</th>
                        <?php if (isAdmin()): ?><th class="no-print">Действия</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= $row['id'] ?></td>
                            <td><?= htmlspecialchars($row['full_name']) ?></td>
                            <td><?= $row['birth_date'] ?></td>
                            <td><?= $row['gender'] === 'M' ? 'М' : 'Ж' ?></td>
                            <td><?= htmlspecialchars($row['department_name']) ?></td>
                            <td><?= htmlspecialchars($row['position_name']) ?></td>
                            <td><?= number_format($row['rate'], 2) ?></td>
                            <td><?= htmlspecialchars($row['contract_number']) ?></td>
                            <td><?= $row['hire_date'] ?></td>
                            <td><?= $row['contract_end_date'] ?? '—' ?></td>
                            <td><?= htmlspecialchars($row['education']) ?></td>
                            <?php if (isAdmin()): ?>
                                <td class="no-print">
                                    <a href="edit_employee.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-warning">Изменить</a>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Удалить?')">
                                        <input type="hidden" name="delete_id" value="<?= $row['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">Удалить</button>
                                    </form>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div class="text-center mt-4 no-print">
            <a href="index.php" class="btn btn-secondary btn-lg">← На главную</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>