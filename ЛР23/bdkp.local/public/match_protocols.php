<?php
// match_protocols.php — Протоколы матчей

include('db_connect.php');
include('auth.php');

$conn->set_charset("utf8mb4");



// Load teams for dropdowns
$teams = [];
$tr = $conn->query("SELECT id, name, city FROM Teams ORDER BY name");
while ($t = $tr->fetch_assoc()) $teams[] = $t;

// ==================== DELETE MATCH ====================
if (isset($_GET['delete']) && isAdmin()) {
    $del = intval($_GET['delete']);
    $conn->query("DELETE FROM MatchSets WHERE match_id = $del");
    $conn->query("DELETE FROM MatchProtocols WHERE id = $del");
    header("Location: match_protocols.php?success=deleted");
    exit();
}

// ==================== ADD MATCH ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_match']) && isAdmin()) {
    $match_datetime = $_POST['match_datetime'] ?? '';
    $home_team_id   = intval($_POST['home_team_id'] ?? 0);
    $away_team_id   = intval($_POST['away_team_id'] ?? 0);
    $result         = trim($_POST['result']   ?? '');
    $city           = trim($_POST['city']     ?? '');
    $hall           = trim($_POST['hall']     ?? '');
    $duration       = trim($_POST['duration'] ?? '');

    if (!$match_datetime || !$home_team_id || !$away_team_id) {
        $error = "Заполните обязательные поля: дата/время, команды.";
    } elseif ($home_team_id === $away_team_id) {
        $error = "Команда хозяев и гостей не могут совпадать.";
    } else {
        $stmt = $conn->prepare("INSERT INTO MatchProtocols (match_datetime, home_team_id, away_team_id, result, city, hall, duration) VALUES (?,?,?,?,?,?,?)");
        $stmt->bind_param("siissss", $match_datetime, $home_team_id, $away_team_id, $result, $city, $hall, $duration);
        if ($stmt->execute()) {
            $new_match_id = $conn->insert_id;

            // Save sets
            $set_numbers  = $_POST['set_number']  ?? [];
            $home_scores  = $_POST['home_score']  ?? [];
            $away_scores  = $_POST['away_score']  ?? [];
            $set_durations = $_POST['set_duration'] ?? [];

            $sStmt = $conn->prepare("INSERT INTO MatchSets (match_id, set_number, home_score, away_score, duration) VALUES (?,?,?,?,?)");
            for ($i = 0; $i < count($set_numbers); $i++) {
                $sn = intval($set_numbers[$i]);
                $hs = intval($home_scores[$i]);
                $as_ = intval($away_scores[$i]);
                $sd = trim($set_durations[$i] ?? '');
                if ($sn > 0) {
                    $sStmt->bind_param("iiiss", $new_match_id, $sn, $hs, $as_, $sd);
                    $sStmt->execute();
                }
            }
            header("Location: match_protocols.php?success=added");
            exit();
        } else {
            $error = "Ошибка добавления: " . $conn->error;
        }
    }
}

// ==================== EDIT MATCH (load) ====================
$editMatch = null;
$editSets  = [];
if (isset($_GET['edit_id']) && isAdmin()) {
    $eid = intval($_GET['edit_id']);
    $r = $conn->prepare("SELECT * FROM MatchProtocols WHERE id=?");
    $r->bind_param("i", $eid);
    $r->execute();
    $editMatch = $r->get_result()->fetch_assoc();
    if ($editMatch) {
        $sr = $conn->prepare("SELECT * FROM MatchSets WHERE match_id=? ORDER BY set_number");
        $sr->bind_param("i", $eid);
        $sr->execute();
        $editSets = $sr->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}

// ==================== UPDATE MATCH ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_match']) && isAdmin()) {
    $upd_id         = intval($_POST['match_id']);
    $match_datetime = $_POST['match_datetime'] ?? '';
    $home_team_id   = intval($_POST['home_team_id'] ?? 0);
    $away_team_id   = intval($_POST['away_team_id'] ?? 0);
    $result         = trim($_POST['result']   ?? '');
    $city           = trim($_POST['city']     ?? '');
    $hall           = trim($_POST['hall']     ?? '');
    $duration       = trim($_POST['duration'] ?? '');

    if ($home_team_id === $away_team_id) {
        $error = "Команды не могут совпадать.";
    } else {
        $stmt = $conn->prepare("UPDATE MatchProtocols SET match_datetime=?, home_team_id=?, away_team_id=?, result=?, city=?, hall=?, duration=? WHERE id=?");
        $stmt->bind_param("siissssi", $match_datetime, $home_team_id, $away_team_id, $result, $city, $hall, $duration, $upd_id);
        if ($stmt->execute()) {
            // Rebuild sets
            $conn->query("DELETE FROM MatchSets WHERE match_id = $upd_id");
            $set_numbers   = $_POST['set_number']  ?? [];
            $home_scores   = $_POST['home_score']  ?? [];
            $away_scores   = $_POST['away_score']  ?? [];
            $set_durations = $_POST['set_duration'] ?? [];
            $sStmt = $conn->prepare("INSERT INTO MatchSets (match_id, set_number, home_score, away_score, duration) VALUES (?,?,?,?,?)");
            for ($i = 0; $i < count($set_numbers); $i++) {
                $sn  = intval($set_numbers[$i]);
                $hs  = intval($home_scores[$i]);
                $as_ = intval($away_scores[$i]);
                $sd  = trim($set_durations[$i] ?? '');
                if ($sn > 0) {
                    $sStmt->bind_param("iiiss", $upd_id, $sn, $hs, $as_, $sd);
                    $sStmt->execute();
                }
            }
            header("Location: match_protocols.php?success=updated");
            exit();
        } else {
            $error = "Ошибка обновления: " . $conn->error;
        }
    }
}

// ==================== LIST MATCHES ====================
$matches = $conn->query("
    SELECT mp.*,
           th.name AS home_name,
           ta.name AS away_name
    FROM MatchProtocols mp
    JOIN Teams th ON mp.home_team_id = th.id
    JOIN Teams ta ON mp.away_team_id = ta.id
    ORDER BY mp.match_datetime DESC
");

// ==================== LOAD SETS for display ====================
$setsMap = [];
$allSets = $conn->query("SELECT * FROM MatchSets ORDER BY match_id, set_number");
while ($s = $allSets->fetch_assoc()) {
    $setsMap[$s['match_id']][] = $s;
}
?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Протоколы матчей</title>
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

        /* ---- Cards ---- */
        .card {
            border: none !important;
            border-radius: 16px !important;
            box-shadow: 0 4px 24px rgba(60, 80, 158, .12) !important;
            overflow: hidden;
        }

        .card-header {
            border-radius: 0 !important;
            font-weight: 700;
            letter-spacing: .5px;
            font-size: 15px;
            padding: 18px 24px !important;
        }

        .hdr-add {
            background: var(--yellow) !important;
            color: var(--navy) !important;
            border-top: 4px solid var(--navy) !important;
        }

        .hdr-edit {
            background: var(--navy) !important;
            color: var(--cream) !important;
            border-top: 4px solid var(--yellow) !important;
        }

        .card-body {
            background: #fff;
            padding: 24px 28px !important;
        }

        /* ---- Match card ---- */
        .match-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(60, 80, 158, .10);
            overflow: hidden;
            margin-bottom: 24px;
            transition: transform .2s, box-shadow .2s;
        }

        .match-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 32px rgba(60, 80, 158, .18);
        }

        .match-card-header {
            background: var(--navy);
            color: var(--cream);
            padding: 14px 22px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
            border-top: 4px solid var(--yellow);
        }

        .match-date {
            font-weight: 700;
            font-size: 13px;
            opacity: .85;
            letter-spacing: .3px;
        }

        .match-meta {
            font-size: 12px;
            opacity: .7;
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
        }

        .match-meta span {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        /* ---- Scoreboard ---- */
        .scoreboard {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0;
            padding: 20px 24px;
            background: #fff;
        }

        .team-block {
            flex: 1;
            text-align: center;
        }

        .team-name {
            font-weight: 800;
            font-size: 1.05rem;
            color: var(--navy);
            line-height: 1.2;
        }

        .team-label {
            font-size: 11px;
            font-weight: 600;
            color: #aaa;
            text-transform: uppercase;
            letter-spacing: .5px;
            margin-top: 4px;
        }

        .score-block {
            padding: 0 28px;
            text-align: center;
            flex-shrink: 0;
        }

        .score-value {
            font-size: 3rem;
            font-weight: 800;
            color: var(--navy);
            line-height: 1;
            letter-spacing: 2px;
        }

        .score-divider {
            width: 3px;
            height: 40px;
            background: var(--yellow);
            border-radius: 2px;
            margin: 0 auto;
            display: none;
        }

        .score-label {
            font-size: 11px;
            font-weight: 600;
            color: #bbb;
            text-transform: uppercase;
            letter-spacing: .5px;
            margin-top: 4px;
        }

        .no-result {
            font-size: 1.6rem;
            font-weight: 700;
            color: #ccc;
            letter-spacing: 4px;
        }

        /* ---- Sets table inside match card ---- */
        .sets-section {
            padding: 0 22px 18px;
        }

        .sets-title {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .6px;
            color: #aaa;
            margin-bottom: 10px;
        }

        .sets-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .sets-table th {
            background: var(--navy);
            color: var(--cream);
            padding: 8px 12px;
            font-weight: 700;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .4px;
        }

        .sets-table th:first-child {
            border-radius: 8px 0 0 0;
        }

        .sets-table th:last-child {
            border-radius: 0 8px 0 0;
        }

        .sets-table td {
            padding: 8px 12px;
            text-align: center;
            border-bottom: 1px solid rgba(60, 80, 158, .08);
            font-weight: 600;
        }

        .sets-table tr:last-child td {
            border-bottom: none;
        }

        .sets-table tr:hover td {
            background: rgba(242, 204, 57, .10);
        }

        .set-score-h {
            color: var(--navy);
        }

        .set-score-a {
            color: #888;
        }

        .set-winner-h {
            font-weight: 800;
            color: var(--navy);
        }

        .set-winner-a {
            font-weight: 800;
            color: var(--navy);
        }

        /* ---- Match card actions ---- */
        .match-card-footer {
            border-top: 1px solid rgba(60, 80, 158, .08);
            padding: 12px 22px;
            display: flex;
            gap: 8px;
            justify-content: flex-end;
        }

        /* ---- Inline sets builder ---- */
        .sets-builder {
            border: 2px dashed #e0dcc4;
            border-radius: 12px;
            padding: 18px;
            background: var(--cream);
        }

        .set-row {
            display: grid;
            grid-template-columns: 60px 1fr 1fr 1fr auto;
            gap: 10px;
            align-items: end;
            margin-bottom: 10px;
        }

        .set-row:last-child {
            margin-bottom: 0;
        }

        .set-row-label {
            font-weight: 700;
            font-size: 12px;
            color: var(--navy);
            text-align: center;
            background: var(--navy);
            color: var(--cream);
            border-radius: 8px;
            padding: 10px 6px;
            line-height: 1.2;
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

        .form-control-sm {
            padding: 7px 10px !important;
            font-size: 13px !important;
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

        .btn-outline-navy {
            background: transparent;
            border: 2px solid rgba(255, 255, 255, .4);
            color: var(--cream);
            border-radius: 8px !important;
            font-size: 12px;
            padding: 4px 12px !important;
            font-weight: 700;
        }

        .btn-outline-navy:hover {
            background: rgba(255, 255, 255, .15);
        }

        /* ---- Empty state ---- */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #bbb;
        }

        .empty-state-icon {
            font-size: 3.5rem;
            margin-bottom: 16px;
        }

        .empty-state p {
            font-size: 15px;
            font-weight: 600;
        }

        /* ---- Divider ---- */
        .section-divider {
            height: 2px;
            background: linear-gradient(90deg, var(--yellow), transparent);
            border-radius: 2px;
            margin: 8px 0 24px;
        }
    </style>
</head>

<body>
    <div class="container py-4 mb-5">

        <!-- Page header -->
        <div class="d-flex align-items-start justify-content-between mb-4 flex-wrap gap-3">
            <div>
                <div class="page-title">Протоколы матчей</div>
                <p class="mt-3 mb-0" style="font-size:14px;color:#666;">Результаты игр и статистика по сетам</p>
            </div>
            <a href="index.php" class="btn btn-secondary">← На главную</a>
        </div>

        <!-- Alerts -->
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <?php $msgs = ['added' => 'Протокол добавлен', 'deleted' => 'Протокол удалён', 'updated' => 'Протокол обновлён'];
                echo $msgs[$_GET['success']] ?? 'Готово'; ?>
            </div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php

        // ====================================================
        // ФОРМА ДОБАВЛЕНИЯ
        // ====================================================
        if (isAdmin() && !$editMatch):
        ?>
            <div class="card mb-5">
                <div class="card-header hdr-add">Добавить протокол матча</div>
                <div class="card-body">
                    <form method="POST" id="addForm">
                        <input type="hidden" name="add_match" value="1">

                        <!-- Main fields -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-3">
                                <label class="form-label">Дата и время</label>
                                <input type="datetime-local" name="match_datetime" class="form-control" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Команда хозяев</label>
                                <select name="home_team_id" class="form-select" required>
                                    <option value="">— Выберите —</option>
                                    <?php foreach ($teams as $t): ?>
                                        <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Команда гостей</label>
                                <select name="away_team_id" class="form-select" required>
                                    <option value="">— Выберите —</option>
                                    <?php foreach ($teams as $t): ?>
                                        <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Результат</label>
                                <input type="text" name="result" class="form-control" placeholder="">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Город</label>
                                <input type="text" name="city" class="form-control" placeholder="">
                            </div>
                            <div class="col-md-5">
                                <label class="form-label">Зал</label>
                                <input type="text" name="hall" class="form-control" placeholder="">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Продолжительность</label>
                                <input type="text" name="duration" class="form-control" placeholder="">
                            </div>
                        </div>

                        <!-- Sets builder -->
                        <div class="mb-3">
                            <label class="form-label mb-2">Сеты</label>
                            <div class="sets-builder" id="setsBuilder">
                                <div class="row g-2 mb-2" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:#aaa;">
                                    <div class="col-1 text-center">Сет</div>
                                    <div class="col-3">Счёт хозяев</div>
                                    <div class="col-3">Счёт гостей</div>
                                    <div class="col-3">Продолжительность</div>
                                    <div class="col-2"></div>
                                </div>
                                <div id="setRows"></div>
                                <button type="button" class="btn btn-secondary btn-sm mt-2" onclick="addSetRow()">+ Добавить сет</button>
                            </div>
                        </div>

                        <div class="d-flex gap-2 mt-3">
                            <button type="submit" class="btn btn-success">Добавить</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <?php
        // ====================================================
        // ФОРМА РЕДАКТИРОВАНИЯ
        // ====================================================
        if ($editMatch && isAdmin()):
        ?>
            <div class="card mb-5">
                <div class="card-header hdr-edit">Редактирование протокола #<?= $editMatch['id'] ?></div>
                <div class="card-body">
                    <form method="POST" id="editForm">
                        <input type="hidden" name="update_match" value="1">
                        <input type="hidden" name="match_id" value="<?= $editMatch['id'] ?>">

                        <div class="row g-3 mb-4">
                            <div class="col-md-3">
                                <label class="form-label">Дата и время </label>
                                <input type="datetime-local" name="match_datetime" class="form-control"
                                    value="<?= date('Y-m-d\TH:i', strtotime($editMatch['match_datetime'])) ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Команда хозяев</label>
                                <select name="home_team_id" class="form-select" required>
                                    <?php foreach ($teams as $t): ?>
                                        <option value="<?= $t['id'] ?>" <?= $t['id'] == $editMatch['home_team_id'] ? 'selected' : '' ?>><?= htmlspecialchars($t['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Команда гостей</label>
                                <select name="away_team_id" class="form-select" required>
                                    <?php foreach ($teams as $t): ?>
                                        <option value="<?= $t['id'] ?>" <?= $t['id'] == $editMatch['away_team_id'] ? 'selected' : '' ?>><?= htmlspecialchars($t['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Результат</label>
                                <input type="text" name="result" class="form-control"
                                    value="<?= htmlspecialchars($editMatch['result'] ?? '') ?>" placeholder="">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Город</label>
                                <input type="text" name="city" class="form-control"
                                    value="<?= htmlspecialchars($editMatch['city'] ?? '') ?>">
                            </div>
                            <div class="col-md-5">
                                <label class="form-label">Зал</label>
                                <input type="text" name="hall" class="form-control"
                                    value="<?= htmlspecialchars($editMatch['hall'] ?? '') ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Продолжительность</label>
                                <input type="text" name="duration" class="form-control"
                                    value="<?= htmlspecialchars($editMatch['duration'] ?? '') ?>" placeholder="">
                            </div>
                        </div>

                        <!-- Edit sets -->
                        <div class="mb-3">
                            <label class="form-label mb-2">Сеты</label>
                            <div class="sets-builder">
                                <div class="row g-2 mb-2" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:#aaa;">
                                    <div class="col-1 text-center">Сет</div>
                                    <div class="col-3">Счёт хозяев</div>
                                    <div class="col-3">Счёт гостей</div>
                                    <div class="col-3">Продолжительность</div>
                                    <div class="col-2"></div>
                                </div>
                                <div id="editSetRows">
                                    <?php foreach ($editSets as $s): ?>
                                        <div class="row g-2 mb-2 set-row-item">
                                            <div class="col-1 text-center">
                                                <div class="set-row-label"><?= $s['set_number'] ?></div>
                                                <input type="hidden" name="set_number[]" value="<?= $s['set_number'] ?>">
                                            </div>
                                            <div class="col-3">
                                                <input type="number" name="home_score[]" class="form-control form-control-sm"
                                                    value="<?= $s['home_score'] ?>" min="0" max="99">
                                            </div>
                                            <div class="col-3">
                                                <input type="number" name="away_score[]" class="form-control form-control-sm"
                                                    value="<?= $s['away_score'] ?>" min="0" max="99">
                                            </div>
                                            <div class="col-3">
                                                <input type="text" name="set_duration[]" class="form-control form-control-sm"
                                                    value="<?= htmlspecialchars($s['duration'] ?? '') ?>" placeholder="">
                                            </div>
                                            <div class="col-2">
                                                <button type="button" class="btn btn-danger btn-sm w-100"
                                                    onclick="this.closest('.set-row-item').remove(); renumberSets('editSetRows')">✕</button>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <button type="button" class="btn btn-secondary btn-sm mt-2"
                                    onclick="addSetRow('editSetRows')">+ Добавить сет</button>
                            </div>
                        </div>

                        <div class="d-flex gap-2 mt-3">
                            <button type="submit" class="btn btn-success">Сохранить изменения</button>
                            <a href="match_protocols.php" class="btn btn-secondary">Отмена</a>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <!-- ====================================================
         LIST OF MATCHES
    ==================================================== -->
        <div class="section-divider"></div>
        <h5 style="font-weight:800;font-size:1.05rem;margin-bottom:20px;">
            Все протоколы
            <span style="font-size:13px;font-weight:500;color:#aaa;margin-left:8px;"><?= $matches->num_rows ?> матч(ей)</span>
        </h5>

        <?php if ($matches->num_rows === 0): ?>
            <div class="empty-state">
                <div class="empty-state-icon">🏐</div>
                <p>Протоколов пока нет<?= isAdmin() ? ' — добавьте первый матч выше' : '' ?></p>
            </div>
        <?php else: ?>
            <?php while ($m = $matches->fetch_assoc()):
                $sets = $setsMap[$m['id']] ?? [];
                $dt = new DateTime($m['match_datetime']);
                $dayNames = ['Mon' => 'Пн', 'Tue' => 'Вт', 'Wed' => 'Ср', 'Thu' => 'Чт', 'Fri' => 'Пт', 'Sat' => 'Сб', 'Sun' => 'Вс'];
                $dayAbbr = $dayNames[$dt->format('D')] ?? '';
            ?>
                <div class="match-card">
                    <!-- Card header: date + meta -->
                    <div class="match-card-header">
                        <div>
                            <div class="match-date">
                                <?= $dt->format('d.m.Y') ?> <?= $dayAbbr ?> · <?= $dt->format('H:i') ?>
                            </div>
                        </div>

                        <div class="match-meta">
                            <?php if ($m['city']): ?>
                                <span><?= htmlspecialchars($m['city']) ?></span>
                            <?php endif; ?>
                            <?php if ($m['hall']): ?>
                                <span><?= htmlspecialchars($m['hall']) ?></span>
                            <?php endif; ?>
                            <?php if ($m['duration']): ?>
                                <span><?= htmlspecialchars($m['duration']) ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="d-flex gap-2 flex-wrap">
                            <!-- Кнопка печати — доступна всем пользователям -->
                            <button type="button" class="btn btn-outline-navy btn-sm"
                                onclick="window.print();"
                                title="Печать протокола этого матча">
                                Печать
                            </button>

                            <?php if (isAdmin()): ?>
                                <a href="?edit_id=<?= $m['id'] ?>" class="btn btn-outline-navy btn-sm">Изменить</a>
                                <a href="?delete=<?= $m['id'] ?>" class="btn btn-outline-navy btn-sm"
                                    onclick="return confirm('Удалить протокол матча?')"
                                    style="background:rgba(231,76,60,.25);">
                                    Удалить
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <style>
                        @media print {

                            /* Скрываем всё, кроме текущей карточки и её содержимого */
                            body>*:not(.container) {
                                display: none;
                            }

                            .container>*:not(.match-card) {
                                display: none;
                            }

                            .match-card {
                                page-break-after: always;
                                margin: 0;
                                box-shadow: none;
                                border: 1px solid #ddd;
                            }

                            .match-card-header,
                            .scoreboard,
                            .sets-section {
                                page-break-inside: avoid;
                            }

                            /* Убираем ненужные элементы при печати */
                            .match-card-footer,
                            button,
                            a.btn {
                                display: none !important;
                            }

                            /* Делаем текст читаемым */
                            body {
                                background: white !important;
                                color: black !important;
                            }
                        }
                    </style>
                    <!-- Scoreboard -->
                    <div class="scoreboard">
                        <div class="team-block" style="text-align:right; padding-right:20px;">
                            <div class="team-name"><?= htmlspecialchars($m['home_name']) ?></div>
                            <div class="team-label">Хозяева</div>
                        </div>

                        <div class="score-block">
                            <?php if ($m['result']): ?>
                                <div class="score-value"><?= htmlspecialchars($m['result']) ?></div>
                                <div class="score-label">Счёт</div>
                            <?php else: ?>
                                <div class="no-result">vs</div>
                            <?php endif; ?>
                        </div>

                        <div class="team-block" style="text-align:left; padding-left:20px;">
                            <div class="team-name"><?= htmlspecialchars($m['away_name']) ?></div>
                            <div class="team-label">Гости</div>
                        </div>
                    </div>

                    <!-- Sets table -->
                    <?php if (!empty($sets)): ?>
                        <div class="sets-section">
                            <div class="sets-title">Сеты</div>
                            <table class="sets-table">
                                <thead>
                                    <tr>
                                        <th style="text-align:left;border-radius:8px 0 0 0;">Сет</th>
                                        <th><?= htmlspecialchars($m['home_name']) ?></th>
                                        <th><?= htmlspecialchars($m['away_name']) ?></th>
                                        <th style="border-radius:0 8px 0 0;">Время</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($sets as $s):
                                        $hw = $s['home_score'] > $s['away_score'];
                                        $aw = $s['away_score'] > $s['home_score'];
                                    ?>
                                        <tr>
                                            <td style="text-align:left;font-weight:700;color:#aaa;">Партия <?= $s['set_number'] ?></td>
                                            <td class="<?= $hw ? 'set-winner-h' : 'set-score-h' ?>"><?= $s['home_score'] ?></td>
                                            <td class="<?= $aw ? 'set-winner-a' : 'set-score-a' ?>"><?= $s['away_score'] ?></td>
                                            <td style="color:#aaa;"><?= $s['duration'] ?: '—' ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        <?php endif; ?>

    </div>



    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        var setCount = 0;

        function addSetRow(containerId) {
            containerId = containerId || 'setRows';
            var container = document.getElementById(containerId);
            setCount++;
            var num = container.querySelectorAll('.set-row-item').length + 1;
            var div = document.createElement('div');
            div.className = 'row g-2 mb-2 set-row-item';
            div.innerHTML = `
            <div class="col-1 text-center">
                <div class="set-row-label">${num}</div>
                <input type="hidden" name="set_number[]" value="${num}">
            </div>
            <div class="col-3">
                <input type="number" name="home_score[]" class="form-control form-control-sm"
                       value="0" min="0" max="99" placeholder="0">
            </div>
            <div class="col-3">
                <input type="number" name="away_score[]" class="form-control form-control-sm"
                       value="0" min="0" max="99" placeholder="0">
            </div>
            <div class="col-3">
                <input type="text" name="set_duration[]" class="form-control form-control-sm"
                       placeholder="">
            </div>
            <div class="col-2">
                <button type="button" class="btn btn-danger btn-sm w-100"
                        onclick="this.closest('.set-row-item').remove(); renumberSets('${containerId}')">✕</button>
            </div>
        `;
            container.appendChild(div);
        }

        function renumberSets(containerId) {
            var rows = document.getElementById(containerId).querySelectorAll('.set-row-item');
            rows.forEach(function(row, i) {
                var label = row.querySelector('.set-row-label');
                var hidden = row.querySelector('input[name="set_number[]"]');
                if (label) label.textContent = i + 1;
                if (hidden) hidden.value = i + 1;
            });
        }

        // Pre-add 3 sets for new form
        <?php if (isAdmin() && !$editMatch): ?>
            addSetRow('setRows');
            addSetRow('setRows');
            addSetRow('setRows');
        <?php endif; ?>
    </script>
</body>

</html>