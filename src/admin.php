<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['uzivatel_id']) || $_SESSION['uzivatel_role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

$db = getDB();

if (isset($_GET['stav']) && isset($_GET['id']) && is_numeric($_GET['id'])) {
    $novy_stav = in_array($_GET['stav'], ['potvrzena', 'zrusena', 'cekajici']) ? $_GET['stav'] : 'cekajici';
    $stmt = $db->prepare("UPDATE rezervace SET stav = ? WHERE id = ?");
    $stmt->execute([$novy_stav, $_GET['id']]);
    header('Location: admin.php');
    exit;
}

$kde    = [];
$params = [];

if (!empty($_GET['sportoviste_id'])) {
    $kde[]    = 'r.sportoviste_id = ?';
    $params[] = (int)$_GET['sportoviste_id'];
}
if (!empty($_GET['stav_filter'])) {
    $kde[]    = 'r.stav = ?';
    $params[] = $_GET['stav_filter'];
}
if (!empty($_GET['datum_od'])) {
    $kde[]    = 'r.datum >= ?';
    $params[] = $_GET['datum_od'];
}
if (!empty($_GET['datum_do'])) {
    $kde[]    = 'r.datum <= ?';
    $params[] = $_GET['datum_do'];
}

$where = count($kde) > 0 ? 'WHERE ' . implode(' AND ', $kde) : '';

$rezervace = $db->prepare("
    SELECT r.*,
           s.nazev as sportoviste_nazev,
           u.jmeno, u.prijmeni, u.email
    FROM rezervace r
    JOIN sportoviste s ON r.sportoviste_id = s.id
    JOIN uzivatele u ON r.uzivatel_id = u.id
    $where
    ORDER BY r.datum DESC, r.cas_od DESC
");
$rezervace->execute($params);
$rezervace = $rezervace->fetchAll();

$sporty = $db->query("SELECT id, nazev FROM sportoviste")->fetchAll();
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <title>SpotBook — Admin</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div class="grid-bg"></div>

<header>
    <div>
        <div class="logo"><span class="spot">Spot</span><span class="book">Book</span></div>
        <p>Rezervační systém školních sportovišť</p>
    </div>
</header>

<nav>
    <div class="nav-left">
        <a href="index.php"><i class="fa-solid fa-house"></i> Domů</a>
        <a href="reservation.php"><i class="fa-solid fa-plus"></i> Nová rezervace</a>
        <a href="my_reservations.php"><i class="fa-solid fa-list"></i> Moje rezervace</a>
        <a href="admin.php"><i class="fa-solid fa-gear"></i> Admin</a>
    </div>
    <div class="nav-right">
        <a href="logout.php" class="nav-logout">
            <i class="fa-solid fa-right-from-bracket"></i> Odhlásit se
        </a>
    </div>
</nav>

<div class="container">

    <div class="res-card">
        <div class="res-header">
            <div class="res-header-icon">
                <i class="fa-solid fa-gear"></i>
            </div>
            <div>
                <h2>Správa rezervací</h2>
                <p>Celkem rezervací: <?= count($rezervace) ?></p>
            </div>
        </div>

        <!-- Filtrování -->
        <div style="padding:24px 32px;border-bottom:1px solid #f1f5f9">
            <form method="GET" action="admin.php">
                <div class="form-grid">
                    <label>Sportoviště
                        <select name="sportoviste_id">
                            <option value="">-- Všechna --</option>
                            <?php foreach ($sporty as $sport): ?>
                                <option value="<?= $sport['id'] ?>"
                                        <?= ($_GET['sportoviste_id'] ?? '') == $sport['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($sport['nazev']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <label>Stav
                        <select name="stav_filter">
                            <option value="">-- Všechny --</option>
                            <option value="cekajici"  <?= ($_GET['stav_filter'] ?? '') === 'cekajici'  ? 'selected' : '' ?>>Čekající</option>
                            <option value="potvrzena" <?= ($_GET['stav_filter'] ?? '') === 'potvrzena' ? 'selected' : '' ?>>Potvrzená</option>
                            <option value="zrusena"   <?= ($_GET['stav_filter'] ?? '') === 'zrusena'   ? 'selected' : '' ?>>Zrušená</option>
                        </select>
                    </label>

                    <label>Datum od
                        <input type="date" name="datum_od" value="<?= htmlspecialchars($_GET['datum_od'] ?? '') ?>">
                    </label>

                    <label>Datum do
                        <input type="date" name="datum_do" value="<?= htmlspecialchars($_GET['datum_do'] ?? '') ?>">
                    </label>
                </div>
                <div style="margin-top:16px;display:flex;gap:8px">
                    <button type="submit"><i class="fa-solid fa-filter"></i> Filtrovat</button>
                    <a href="admin.php" class="btn btn-secondary"><i class="fa-solid fa-xmark"></i> Reset</a>
                </div>
            </form>
        </div>

        <!-- Tabulka -->
        <?php if (empty($rezervace)): ?>
            <div style="padding:48px;text-align:center;color:#94a3b8">
                <i class="fa-solid fa-calendar-xmark" style="font-size:48px;margin-bottom:16px;display:block"></i>
                <p>Žádné rezervace nenalezeny.</p>
            </div>
        <?php else: ?>
            <div style="padding:24px 32px">
                <table>
                    <thead>
                    <tr>
                        <th>Uživatel</th>
                        <th>Sportoviště</th>
                        <th>Datum</th>
                        <th>Čas</th>
                        <th>Osob</th>
                        <th>Stav</th>
                        <th>Akce</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($rezervace as $r): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($r['jmeno'] . ' ' . $r['prijmeni']) ?></strong><br>
                                <small style="color:#94a3b8"><?= htmlspecialchars($r['email']) ?></small>
                            </td>
                            <td><?= htmlspecialchars($r['sportoviste_nazev']) ?></td>
                            <td><?= htmlspecialchars($r['datum']) ?></td>
                            <td><?= htmlspecialchars($r['cas_od']) ?> — <?= htmlspecialchars($r['cas_do']) ?></td>
                            <td><?= htmlspecialchars($r['pocet_osob'] ?? '—') ?></td>
                            <td>
                                        <span class="badge badge-<?= $r['stav'] ?>">
                                            <?= $r['stav'] ?>
                                        </span>
                            </td>
                            <td style="display:flex;gap:6px">
                                <a href="admin.php?id=<?= $r['id'] ?>&stav=potvrzena"
                                   class="btn btn-success"
                                   style="font-size:11px;padding:6px 12px">
                                    <i class="fa-solid fa-check"></i> Potvrdit
                                </a>
                                <a href="admin.php?id=<?= $r['id'] ?>&stav=zrusena"
                                   class="btn btn-danger"
                                   style="font-size:11px;padding:6px 12px">
                                    <i class="fa-solid fa-xmark"></i> Zrušit
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>