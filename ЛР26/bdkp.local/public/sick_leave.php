<?php
// sick_leave.php

include('db_connect.php');
include('auth.php');

$conn->set_charset("utf8mb4");

// ==================== ЭКСПОРТ ВСЕХ БОЛЬНИЧНЫХ (доступен всем) ====================
function exportSickLeavesToCSV($conn, $filename = "sick_leaves_report_")
{
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . date("Y-m-d") . '.csv"');
    echo "\xEF\xBB\xBF"; // BOM для корректной кириллицы

    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID', 'Сотрудник', 'Номер больничного', 'Дата начала', 'Дата окончания']);

    $result = $conn->query("SELECT * FROM SickLeaves ORDER BY start_date ASC");
    while ($row = $result->fetch_assoc()) {
        fputcsv($out, [
            $row['id'],
            $row['full_name'] ?? '(не указано)',
            $row['sick_leave_number'] ?? '—',
            $row['start_date'],
            $row['end_date']
        ]);
    }
    fclose($out);
    exit;
}

if (isset($_GET['export_all_sick_leaves'])) {
    exportSickLeavesToCSV($conn);
}

// ==================== ДОБАВЛЕНИЕ БОЛЬНИЧНОГО (только admin) ====================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add_sick_leave'])) {
    requireAdmin("У вас нет прав на добавление больничных листов");

    $employee_id       = intval($_POST['employee_id'] ?? 0);
    $sick_leave_number = trim($_POST['sick_leave_number'] ?? '');
    $start_date        = trim($_POST['start_date'] ?? '');
    $end_date          = trim($_POST['end_date'] ?? '');

    if ($employee_id <= 0 || empty($sick_leave_number) || empty($start_date) || empty($end_date)) {
        $error = "Все поля обязательны";
    } elseif (strtotime($end_date) < strtotime($start_date)) {
        $error = "Дата окончания не может быть раньше даты начала";
    } else {
        $stmt = $conn->prepare("SELECT full_name FROM Employees WHERE id = ?");
        $stmt->bind_param("i", $employee_id);
        $stmt->execute();
        $stmt->bind_result($full_name);
        if (!$stmt->fetch()) {
            $error = "Сотрудник не найден";
        }
        $stmt->close();

        if (!isset($error)) {
            $stmt = $conn->prepare("
                INSERT INTO SickLeaves 
                (employee_id, full_name, sick_leave_number, start_date, end_date) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("issss", $employee_id, $full_name, $sick_leave_number, $start_date, $end_date);

            if ($stmt->execute()) {
                header("Location: sick_leave.php?success=add");
                exit;
            } else {
                $error = "Ошибка добавления: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// ==================== УДАЛЕНИЕ (только admin) ====================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_sick_leave'])) {
    requireAdmin("У вас нет прав на удаление больничных листов");

    $id = intval($_POST['sick_leave_id'] ?? 0);
    if ($id > 0) {
        $stmt = $conn->prepare("DELETE FROM SickLeaves WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        if ($stmt->affected_rows > 0) {
            header("Location: sick_leave.php?success=delete");
            exit;
        } else {
            $error = "Больничный лист не найден";
        }
        $stmt->close();
    }
}

// ==================== РЕДАКТИРОВАНИЕ (только admin) ====================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['edit_sick_leave'])) {
    requireAdmin("У вас нет прав на редактирование больничных листов");

    $id                = intval($_POST['sick_leave_id'] ?? 0);
    $sick_leave_number = trim($_POST['sick_leave_number'] ?? '');
    $start_date        = trim($_POST['start_date'] ?? '');
    $end_date          = trim($_POST['end_date'] ?? '');

    if ($id <= 0 || empty($sick_leave_number) || empty($start_date) || empty($end_date)) {
        $error = "Все поля обязательны";
    } elseif (strtotime($end_date) < strtotime($start_date)) {
        $error = "Дата окончания не может быть раньше начала";
    } else {
        $stmt = $conn->prepare("
            UPDATE SickLeaves 
            SET sick_leave_number = ?, start_date = ?, end_date = ? 
            WHERE id = ?
        ");
        $stmt->bind_param("sssi", $sick_leave_number, $start_date, $end_date, $id);

        if ($stmt->execute() && $stmt->affected_rows > 0) {
            header("Location: sick_leave.php?success=edit");
            exit;
        } else {
            $error = "Не удалось обновить запись";
        }
        $stmt->close();
    }
}

// ==================== ФИЛЬТРЫ И ВЫВОД (доступно всем) ====================
$start_filter = $_POST['start_date_filter'] ?? '';
$end_filter   = $_POST['end_date_filter']   ?? '';
$search_name  = trim($_POST['search_name']  ?? '');

$where  = "";
$params = [];
$types  = "";

$has_date_filter = !empty($start_filter) && !empty($end_filter);

if ($has_date_filter) {
    $where .= "WHERE start_date >= ? AND end_date <= ?";
    $params = [$start_filter, $end_filter];
    $types  = "ss";
} else {
    $where = "WHERE 1=1";   // без ограничения по периоду
}

if ($search_name !== '') {
    $where .= " AND full_name LIKE ?";
    $params[] = "%$search_name%";
    $types   .= "s";
}

$query = "
    SELECT 
        GROUP_CONCAT(id ORDER BY start_date SEPARATOR ',') AS ids,
        full_name,
        COUNT(*) AS sick_count,
        GROUP_CONCAT(sick_leave_number ORDER BY start_date SEPARATOR ', ') AS numbers,
        GROUP_CONCAT(start_date ORDER BY start_date SEPARATOR ',') AS start_dates,
        GROUP_CONCAT(end_date ORDER BY end_date SEPARATOR ',') AS end_dates
    FROM SickLeaves 
    $where 
    GROUP BY full_name
    ORDER BY full_name
";

$stmt = $conn->prepare($query);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Больничные листы</title>
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

    <div class="container py-5">
        <div class="card shadow-lg p-4">
            <h1 class="text-center text-primary mb-4">Больничные листы</h1>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    <?php
                    echo match ($_GET['success']) {
                        'add'    => 'Больничный лист успешно добавлен',
                        'delete' => 'Больничный лист успешно удалён',
                        'edit'   => 'Больничный лист успешно обновлён',
                        default  => 'Действие выполнено'
                    };
                    ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <!-- Экспорт -->
            <div class="mb-4 text-center">
                <a href="?export_all_sick_leaves=1" class="btn btn-success"> Экспортировать все больничные в CSV</a>
            </div>

            <?php if (isAdmin()): ?>
                <!-- Форма добавления -->
                <h3 class="mt-4">Добавить больничный лист</h3>
                <form method="POST" class="mb-5">
                    <input type="hidden" name="add_sick_leave" value="1">

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Сотрудник</label>
                            <select name="employee_id" class="form-select" required>
                                <option value="">— Выберите сотрудника —</option>
                                <?php
                                $emps = $conn->query("SELECT id, full_name FROM Employees ORDER BY full_name");
                                while ($emp = $emps->fetch_assoc()) {
                                    echo "<option value='{$emp['id']}'>" . htmlspecialchars($emp['full_name']) . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Номер листа нетрудоспособности</label>
                            <input type="text" name="sick_leave_number" class="form-control" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Дата начала</label>
                            <input type="date" name="start_date" class="form-control" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Дата окончания</label>
                            <input type="date" name="end_date" class="form-control" required>
                        </div>
                        <div class="col-md-1 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">Добавить</button>
                        </div>
                    </div>
                </form>
            <?php endif; ?>

            <!-- Фильтр -->
            <h3 class="mt-5">Фильтрация больничных</h3>
            <form method="POST" class="mb-4">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Начало периода</label>
                        <input type="date" name="start_date_filter" class="form-control" value="<?= htmlspecialchars($start_filter) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Конец периода</label>
                        <input type="date" name="end_date_filter" class="form-control" value="<?= htmlspecialchars($end_filter) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Поиск по ФИО</label>
                        <input type="text" name="search_name" class="form-control" placeholder="Фамилия или имя" value="<?= htmlspecialchars($search_name) ?>">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">Фильтровать</button>
                    </div>
                </div>
            </form>

            <!-- Таблица -->
            <div class="table-responsive">
                <table class="table table-bordered table-hover table-striped">
                    <thead class="table-primary">
                        <tr>
                            <th>Сотрудник</th>
                            <th>Номер листа нетрудоспособности</th>
                            <th>Дата начала</th>
                            <th>Дата окончания</th>
                            <?php if (isAdmin()): ?>
                                <th style="width:160px">Действия</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows === 0): ?>
                            <tr>
                                <td colspan="<?= isAdmin() ? 5 : 4 ?>" class="text-center py-4 text-muted">Больничных листов не найдено</td>
                            </tr>
                        <?php else: ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['full_name']) ?></td>

                                    <td><?= htmlspecialchars($row['numbers']) ?></td>
                                    <td><?= nl2br(htmlspecialchars(str_replace(',', "\n", $row['start_dates']))) ?></td>
                                    <td><?= nl2br(htmlspecialchars(str_replace(',', "\n", $row['end_dates']))) ?></td>
                                    <?php if (isAdmin()): ?>
                                        <td class="text-center">
                                            <button class="btn btn-sm btn-warning edit-btn mb-1"
                                                data-bs-toggle="modal" data-bs-target="#editModal"
                                                data-ids="<?= htmlspecialchars($row['ids']) ?>"
                                                data-startdates="<?= htmlspecialchars($row['start_dates']) ?>"
                                                data-enddates="<?= htmlspecialchars($row['end_dates']) ?>"
                                                data-numbers="<?= htmlspecialchars($row['numbers']) ?>">
                                                Изменить
                                            </button>

                                            <button class="btn btn-sm btn-danger delete-btn"
                                                data-bs-toggle="modal" data-bs-target="#deleteModal"
                                                data-ids="<?= htmlspecialchars($row['ids']) ?>"
                                                data-startdates="<?= htmlspecialchars($row['start_dates']) ?>"
                                                data-enddates="<?= htmlspecialchars($row['end_dates']) ?>"
                                                data-numbers="<?= htmlspecialchars($row['numbers']) ?>">
                                                Удалить
                                            </button>
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
    </div>

    <!-- Модальное окно удаления -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Удаление больничного листа</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="delete_sick_leave" value="1">
                        <div class="mb-3">
                            <label class="form-label">Выберите больничный лист для удаления</label>
                            <select name="sick_leave_id" id="delete_select_sick" class="form-select" required></select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                        <button type="submit" class="btn btn-danger">Удалить</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Модальное окно редактирования -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Редактирование больничного листа</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="edit_sick_leave" value="1">
                        <input type="hidden" name="sick_leave_id" id="edit_sick_id">
                        <div class="mb-3">
                            <label class="form-label">Выберите больничный лист</label>
                            <select id="edit_select_sick" class="form-select" required></select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Номер больничного листа</label>
                            <input type="text" name="sick_leave_number" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Дата начала</label>
                            <input type="date" name="start_date" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Дата окончания</label>
                            <input type="date" name="end_date" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                        <button type="submit" class="btn btn-primary">Сохранить</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

  

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Заполнение модальных окон
        document.addEventListener("DOMContentLoaded", function() {
            // Удаление
            document.querySelectorAll(".delete-btn").forEach(btn => {
                btn.addEventListener("click", function() {
                    const ids = this.dataset.ids.split(",");
                    const starts = this.dataset.startdates.split(",");
                    const ends = this.dataset.enddates.split(",");
                    const numbers = this.dataset.numbers.split(", ");

                    const select = document.getElementById("delete_select_sick");
                    select.innerHTML = "";

                    ids.forEach((id, i) => {
                        const opt = document.createElement("option");
                        opt.value = id.trim();
                        opt.text = `№${numbers[i]?.trim() || '—'}  (${starts[i].trim()} – ${ends[i].trim()})`;
                        select.appendChild(opt);
                    });
                });
            });

            // Редактирование
            document.querySelectorAll(".edit-btn").forEach(btn => {
                btn.addEventListener("click", function() {
                    const ids = this.dataset.ids.split(",");
                    const starts = this.dataset.startdates.split(",");
                    const ends = this.dataset.enddates.split(",");
                    const numbers = this.dataset.numbers.split(", ");

                    const select = document.getElementById("edit_select_sick");
                    select.innerHTML = "";

                    ids.forEach((id, i) => {
                        const opt = document.createElement("option");
                        opt.value = id.trim();
                        opt.text = `№${numbers[i]?.trim() || '—'}  (${starts[i].trim()} – ${ends[i].trim()})`;
                        select.appendChild(opt);
                    });

                    // По умолчанию первый
                    if (ids.length > 0) {
                        document.getElementById("edit_sick_id").value = ids[0].trim();
                        // Можно также заполнить поля формы первым значением, если нужно
                    }
                });
            });
        });
    </script>

</body>

</html>