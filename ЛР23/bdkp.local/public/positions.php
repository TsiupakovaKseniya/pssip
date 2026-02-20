<?php
// positions.php — только название должности

include('db_connect.php');
include('auth.php');

$conn->set_charset("utf8mb4");

// ==================== ДОБАВЛЕНИЕ (только admin) ====================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add'])) {
    requireAdmin();

    $name = trim($_POST['name'] ?? '');

    if (empty($name)) {
        $error = "Название должности обязательно";
    } else {
        $stmt = $conn->prepare("INSERT INTO Positions (name) VALUES (?)");
        $stmt->bind_param("s", $name);
        if ($stmt->execute()) {
            header("Location: positions.php?success=added");
            exit();
        } else {
            $error = "Ошибка добавления: " . $conn->error;
        }
    }
}

// ==================== УДАЛЕНИЕ (только admin) ====================
if (isset($_GET['delete']) && isAdmin()) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM Positions WHERE id = $id");
    header("Location: positions.php?success=deleted");
    exit();
}

// ==================== ПОИСК И СОРТИРОВКА ====================
$search    = trim($_GET['search'] ?? '');
$sortBy    = $_GET['sort_by']    ?? 'id';
$sortOrder = ($_GET['sort_order'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';

$allowed = ['id', 'name'];
if (!in_array($sortBy, $allowed)) $sortBy = 'id';

$where = $search ? "WHERE name LIKE ?" : "";
$like  = $search ? "%$search%" : null;

$query = "SELECT id, name FROM Positions $where ORDER BY $sortBy $sortOrder";

$stmt = $conn->prepare($query);
if ($search) $stmt->bind_param("s", $like);
$stmt->execute();
$result = $stmt->get_result();

// ==================== ЭКСПОРТ В CSV ====================
function exportToCSV($result, $filename)
{
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo "\xEF\xBB\xBF";

    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID', 'Название должности']);

    while ($row = $result->fetch_assoc()) {
        fputcsv($out, [$row['id'], $row['name']]);
    }
    fclose($out);
    exit;
}

if (isset($_GET['export_all'])) {
    $allStmt = $conn->prepare("SELECT id, name FROM Positions ORDER BY name");
    $allStmt->execute();
    exportToCSV($allStmt->get_result(), "positions_all_" . date("Y-m-d") . ".csv");
}

if (isset($_GET['export_search']) && $search) {
    exportToCSV($result, "positions_search_" . date("Y-m-d") . ".csv");
}
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Должности</title>
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
        <h1 class="text-center mb-4 text-primary">Должности</h1>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <?= $_GET['success'] === 'added' ? 'Должность добавлена' : 'Должность удалена' ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Поиск и сортировка -->
        <form method="GET" class="mb-4">
            <div class="row g-3 align-items-end">
                <div class="col-md-6">
                    <input type="text" name="search" class="form-control"
                        placeholder="Поиск по названию должности..."
                        value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-3">
                    <select name="sort_by" class="form-select">
                        <option value="id" <?= $sortBy === 'id'   ? 'selected' : '' ?>>По ID</option>
                        <option value="name" <?= $sortBy === 'name' ? 'selected' : '' ?>>По названию</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100">Применить</button>
                </div>
            </div>
        </form>

        <!-- Экспорт -->
        <div class="mb-4 text-center">
            <?php if ($search): ?>
                <a href="?export_search=1&search=<?= urlencode($search) ?>" class="btn btn-outline-warning me-3">
                    Экспорт результатов поиска
                </a>
            <?php endif; ?>
            <a href="?export_all=1" class="btn btn-outline-success">
                Экспорт всех должностей
            </a>
        </div>

        <?php if (isAdmin()): ?>
            <!-- Форма добавления -->
            <div class="card mb-4 border-success">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Добавить должность</h5>
                </div>
                <div class="card-body">
                    <form method="POST" class="row g-3">
                        <div class="col-md-9">
                            <input type="text" name="name" class="form-control"
                                placeholder="Название должности" required>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" name="add" class="btn btn-success w-100">Добавить</button>
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
                        <th style="width:80px">ID</th>
                        <th>Название должности</th>
                        <?php if (isAdmin()): ?>
                            <th style="width:160px" class="text-center">Действия</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows === 0): ?>
                        <tr>
                            <td colspan="<?= isAdmin() ? 3 : 2 ?>" class="text-center py-4 text-muted">
                                Должности не найдены
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td class="text-center"><?= $row['id'] ?></td>
                                <td><?= htmlspecialchars($row['name']) ?></td>
                                <?php if (isAdmin()): ?>
                                    <td class="text-center">
                                        <a href="edit_position.php?id=<?= $row['id'] ?>"
                                            class="btn btn-sm btn-warning">Изменить</a>
                                        <a href="?delete=<?= $row['id'] ?>"
                                            class="btn btn-sm btn-danger"
                                            onclick="return confirm('Удалить должность «<?= htmlspecialchars(addslashes($row['name'])) ?>»?')">
                                            Удалить
                                        </a>
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