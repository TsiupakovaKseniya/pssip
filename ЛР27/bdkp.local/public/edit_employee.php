<?php
include('db_connect.php');
include('auth.php');
requireAdmin();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID сотрудника не указан.");
}

$id = (int)$_GET['id'];

$stmt = $conn->prepare("SELECT * FROM Employees WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$employee = $stmt->get_result()->fetch_assoc();

if (!$employee) die("Сотрудник не найден.");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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

    $stmt = $conn->prepare("
        UPDATE Employees SET 
            full_name = ?, 
            birth_date = ?, 
            gender = ?, 
            department_id = ?, 
            position_id = ?, 
            rate = ?, 
            contract_number = ?, 
            hire_date = ?,
            contract_end_date = ?,
            education = ?
        WHERE id = ?
    ");
    $stmt->bind_param(
        "sssiidsssssi",
        $full_name,
        $birth_date,
        $gender,
        $department_id,
        $position_id,
        $rate,
        $contract_number,
        $hire_date,
        $contract_end_date,
        $education,
        $id
    );

    if ($stmt->execute()) {
        header("Location: employees.php?success=updated");
        exit();
    } else {
        $error = "Ошибка обновления: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактирование сотрудника</title>
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
        <div class="card shadow mx-auto" style="max-width: 950px;">
            <div class="card-header bg-primary text-white text-center">
                <h3>Редактирование сотрудника</h3>
            </div>
            <div class="card-body">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST" class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">ФИО</label>
                        <input type="text" name="full_name" class="form-control"
                            value="<?= htmlspecialchars($employee['full_name']) ?>" required>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Дата рождения</label>
                        <input type="date" name="birth_date" class="form-control"
                            value="<?= $employee['birth_date'] ?>" required>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Пол</label>
                        <select name="gender" class="form-select" required>
                            <option value="M" <?= $employee['gender'] == 'M' ? 'selected' : '' ?>>Мужской</option>
                            <option value="F" <?= $employee['gender'] == 'F' ? 'selected' : '' ?>>Женский</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Подразделение</label>
                        <select name="department_id" class="form-select" required>
                            <?php
                            $depts = $conn->query("SELECT id, name FROM Departments ORDER BY name");
                            while ($d = $depts->fetch_assoc()):
                                $sel = $d['id'] == $employee['department_id'] ? 'selected' : '';
                            ?>
                                <option value="<?= $d['id'] ?>" <?= $sel ?>><?= htmlspecialchars($d['name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Должность</label>
                        <select name="position_id" class="form-select" required>
                            <?php
                            $poss = $conn->query("SELECT id, name FROM Positions ORDER BY name");
                            while ($p = $poss->fetch_assoc()):
                                $sel = $p['id'] == $employee['position_id'] ? 'selected' : '';
                            ?>
                                <option value="<?= $p['id'] ?>" <?= $sel ?>><?= htmlspecialchars($p['name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Ставка</label>
                        <input type="number" step="0.01" min="0.01" name="rate" class="form-control"
                            value="<?= $employee['rate'] ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Номер контракта</label>
                        <input type="text" name="contract_number" class="form-control"
                            value="<?= htmlspecialchars($employee['contract_number']) ?>" required>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Дата приёма</label>
                        <input type="date" name="hire_date" class="form-control"
                            value="<?= $employee['hire_date'] ?>" required>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Дата окончания контракта</label>
                        <input type="date" name="contract_end_date" class="form-control"
                            value="<?= $employee['contract_end_date'] ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Образование</label>
                        <select name="education" class="form-select" required>
                            <option value="высшее" <?= $employee['education'] == 'высшее' ? 'selected' : '' ?>>Высшее</option>
                            <option value="среднее" <?= $employee['education'] == 'среднее' ? 'selected' : '' ?>>Среднее</option>
                            <option value="базовое" <?= $employee['education'] == 'базовое' ? 'selected' : '' ?>>Базовое</option>
                            <option value="среднее специальное" <?= $employee['education'] == 'среднее специальное' ? 'selected' : '' ?>>Среднее специальное</option>
                        </select>
                    </div>

                    <div class="col-12 text-center mt-4">
                        <button type="submit" class="btn btn-success btn-lg">Сохранить изменения</button>
                        <a href="employees.php" class="btn btn-secondary btn-lg">Отмена</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>