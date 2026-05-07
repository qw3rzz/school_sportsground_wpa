<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['uzivatel_id'])) {
    header('Location: login.php');
    exit;
}

$db = getDB();

if (isset($_GET['zrusit']) && is_numeric($_GET['zrusit'])) {
    $stmt = $db->prepare("
        UPDATE rezervace SET stav = 'zrusena'
        WHERE id = ? AND uzivatel_id = ?
    ");
    $stmt->execute([$_GET['zrusit'], $_SESSION['uzivatel_id']]);
    header('Location: my_reservations.php');
    exit;
}

$stmt = $db->prepare("
    SELECT r.*, s.nazev as sportoviste_nazev
    FROM rezervace r
    JOIN sportoviste s ON r.sportoviste_id = s.id
    WHERE r.uzivatel_id = ?
    ORDER BY r.datum DESC, r.cas_od DESC
");
$stmt->execute([$_SESSION['uzivatel_id']]);
$rezervace = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <title>SpotBook — Moje rezervace</title>
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
    <a href="index.php"><i class="fa-solid fa-house"></i> Domů</a>
    <a href="reservation.php"><i class="fa-solid fa-plus"></i> Nová rezervace</a>
    <a href="my_reservations.php"><i class="fa-solid fa-list"></i> Moje rezervace</a>
    <?php if ($_SESSION['uzivatel_role'] === 'admin'): ?>
        <a href="admin.php"><i class="fa-solid fa-gear"></i> Admin</a>
    <?php endif; ?>
    <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Odhlásit se</a>
</nav>

<div class="container">

    <div class="res-card">
        <div class="res-header">
            <div class="res-header-icon">
                <i class="fa-solid fa-list"></i>
            </div>
            <div>
                <h2>Moje rezervace</h2>
                <p>Přehled všech tvých rezervací</p>
            </div>
            <a href="reservation.php" class="btn" style="margin-left:auto">
                <i class="fa-solid fa-plus"></i> Nová rezervace
            </a>
        </div>

        <?php if (empty($rezervace)): ?>
            <div style="padding:48px;text-align:center;color:#94a3b8">
                <i class="fa-solid fa-calendar-xmark" style="font-size:48px;margin-bottom:16px;display:block"></i>
                <p style="font-size:16px;font-weight:600;margin-bottom:8px">Žádné rezervace</p>
                <p style="font-size:13px">Zatím nemáš žádné rezervace.</p>
                <a href="reservation.php" class="btn" style="margin-top:20px">
                    <i class="fa-solid fa-plus"></i> Vytvořit rezervaci
                </a>
            </div>
        <?php else: ?>
            <div style="padding:24px 32px">
                <div class="rezervace-grid">
                    <?php foreach ($rezervace as $r): ?>
                        <div class="rezervace-item <?= $r['stav'] === 'zrusena' ? 'zrusena' : '' ?>">
                            <div class="rezervace-item-header">
                                <div class="rezervace-sport">
                                    <div class="rezervace-icon">
                                        <i class="fa-solid fa-dumbbell"></i>
                                    </div>
                                    <div>
                                        <div class="rezervace-nazev"><?= htmlspecialchars($r['sportoviste_nazev']) ?></div>
                                        <div class="rezervace-datum">
                                            <i class="fa-solid fa-calendar"></i>
                                            <?= htmlspecialchars($r['datum']) ?>
                                        </div>
                                    </div>
                                </div>
                                <span class="badge badge-<?= $r['stav'] ?>">
                                        <?php if ($r['stav'] === 'cekajici'): ?>
                                            <i class="fa-solid fa-clock"></i> Čekající
                                        <?php elseif ($r['stav'] === 'potvrzena'): ?>
                                            <i class="fa-solid fa-check"></i> Potvrzená
                                        <?php else: ?>
                                            <i class="fa-solid fa-xmark"></i> Zrušená
                                        <?php endif; ?>
                                    </span>
                            </div>
                            <div class="rezervace-info">
                                <div class="rezervace-info-item">
                                    <i class="fa-solid fa-clock"></i>
                                    <?= htmlspecialchars($r['cas_od']) ?> — <?= htmlspecialchars($r['cas_do']) ?>
                                </div>
                                <div class="rezervace-info-item">
                                    <i class="fa-solid fa-users"></i>
                                    <?= htmlspecialchars($r['pocet_osob'] ?? '—') ?> osob
                                </div>
                                <?php if ($r['poznamka']): ?>
                                    <div class="rezervace-info-item">
                                        <i class="fa-solid fa-note-sticky"></i>
                                        <?= htmlspecialchars($r['poznamka']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <?php if ($r['stav'] !== 'zrusena'): ?>
                                <div class="rezervace-footer">
                                    <a href="my_reservations.php?zrusit=<?= $r['id'] ?>"
                                       class="btn btn-danger"
                                       onclick="return confirm('Opravdu chceš zrušit rezervaci?')"
                                       style="font-size:12px;padding:8px 16px">
                                        <i class="fa-solid fa-xmark"></i> Zrušit
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>