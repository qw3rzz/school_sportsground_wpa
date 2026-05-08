<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['uzivatel_id'])) {
    header('Location: login.php');
    exit;
}

$db = getDB();
$stmt = $db->query("SELECT * FROM sportoviste WHERE aktivni = 1");
$sporty = $stmt->fetchAll();

// Příští rezervace
$stmt = $db->prepare("
    SELECT r.*, s.nazev as sportoviste_nazev
    FROM rezervace r
    JOIN sportoviste s ON r.sportoviste_id = s.id
    WHERE r.uzivatel_id = ?
    AND r.stav != 'zrusena'
    AND CONCAT(r.datum, ' ', r.cas_do) > NOW()
    ORDER BY r.datum ASC, r.cas_od ASC
    LIMIT 1
");
$stmt->execute([$_SESSION['uzivatel_id']]);
$pristi = $stmt->fetch();

$icons = [
        'fa-solid fa-dumbbell',
        'fa-solid fa-basketball',
        'fa-solid fa-person-running',
        'fa-solid fa-volleyball',
        'fa-solid fa-table-tennis-paddle-ball',
        'fa-solid fa-futbol'
];
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <title>SpotBook</title>
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
        <?php if ($_SESSION['uzivatel_role'] === 'admin'): ?>
            <a href="admin.php"><i class="fa-solid fa-gear"></i> Admin</a>
        <?php endif; ?>
    </div>
    <div class="nav-right">
        <a href="logout.php" class="nav-logout">
            <i class="fa-solid fa-right-from-bracket"></i> Odhlásit se
        </a>
    </div>
</nav>

<div class="container">

    <div class="hero">
        <div>
            <h2>Vítej, <?= htmlspecialchars($_SESSION['uzivatel_jmeno']) ?>!</h2>
            <p>Rezervuj si sportoviště rychle a jednoduše.</p>
        </div>
        <div class="hero-emoji">
            <i class="fa-solid fa-trophy" style="font-size:56px;color:rgba(255,255,255,0.9)"></i>
        </div>
    </div>

    <?php if ($pristi): ?>
        <div class="countdown-card">
            <div class="countdown-left">
                <div class="countdown-icon">
                    <i class="fa-solid fa-calendar-check"></i>
                </div>
                <div>
                    <div class="countdown-label">Příští rezervace</div>
                    <div class="countdown-sport"><?= htmlspecialchars($pristi['sportoviste_nazev']) ?></div>
                    <div class="countdown-time">
                        <i class="fa-solid fa-clock"></i>
                        <?= htmlspecialchars($pristi['datum']) ?> &bull;
                        <?= htmlspecialchars($pristi['cas_od']) ?> — <?= htmlspecialchars($pristi['cas_do']) ?>
                    </div>
                </div>
            </div>
            <div class="countdown-right">
                <div class="countdown-timer" id="countdown"
                     data-datum="<?= $pristi['datum'] ?>"
                     data-cas-od="<?= $pristi['cas_od'] ?>"
                     data-cas-do="<?= $pristi['cas_do'] ?>">
                    <div class="countdown-unit">
                        <span id="cnt-a">--</span>
                        <small id="lbl-a">dní</small>
                    </div>
                    <div class="countdown-sep">:</div>
                    <div class="countdown-unit">
                        <span id="cnt-b">--</span>
                        <small id="lbl-b">hod</small>
                    </div>
                    <div class="countdown-sep">:</div>
                    <div class="countdown-unit">
                        <span id="cnt-c">--</span>
                        <small id="lbl-c">min</small>
                    </div>
                </div>
                <div id="countdown-label" class="countdown-status"></div>
            </div>
        </div>
    <?php endif; ?>

    <div class="section-title">Dostupná sportoviště</div>

    <div class="facilities-grid">
        <?php foreach ($sporty as $i => $sport): ?>
            <a href="reservation.php" class="facility-card">
                <div class="icon">
                    <i class="<?= $icons[$i % count($icons)] ?>"></i>
                </div>
                <h3><?= htmlspecialchars($sport['nazev']) ?></h3>
                <p><?= htmlspecialchars($sport['popis']) ?></p>
                <p style="margin-top:8px;color:#6366f1;font-weight:600;font-size:12px;">
                    <i class="fa-solid fa-users"></i> Kapacita: <?= $sport['kapacita'] ?> osob
                </p>
            </a>
        <?php endforeach; ?>
    </div>

</div>

<script>
    const el = document.getElementById('countdown');
    if (el) {
        const datum  = el.dataset.datum;
        const casOd  = el.dataset.casOd;
        const casDo  = el.dataset.casDo;
        const start  = new Date(datum + 'T' + casOd);
        const end    = new Date(datum + 'T' + casDo);
        const label  = document.getElementById('countdown-label');

        function pad(n) { return String(n).padStart(2, '0'); }

        function update() {
            const now = new Date();
            let diff, text;

            if (now < start) {
                diff = start - now;
                text = 'do začátku';
            } else if (now < end) {
                diff = end - now;
                text = 'do konce rezervace';
            } else {
                document.getElementById('cnt-a').textContent = '00';
                document.getElementById('cnt-b').textContent = '00';
                document.getElementById('cnt-c').textContent = '00';
                label.textContent = 'Rezervace skončila';
                return;
            }

            const days    = Math.floor(diff / 86400000);
            const hours   = Math.floor((diff % 86400000) / 3600000);
            const minutes = Math.floor((diff % 3600000) / 60000);
            const seconds = Math.floor((diff % 60000) / 1000);

            if (days >= 1) {
                document.getElementById('cnt-a').textContent = pad(days);
                document.getElementById('cnt-b').textContent = pad(hours);
                document.getElementById('cnt-c').textContent = pad(minutes);
                document.getElementById('lbl-a').textContent = 'dní';
                document.getElementById('lbl-b').textContent = 'hod';
                document.getElementById('lbl-c').textContent = 'min';
            } else {
                document.getElementById('cnt-a').textContent = pad(hours);
                document.getElementById('cnt-b').textContent = pad(minutes);
                document.getElementById('cnt-c').textContent = pad(seconds);
                document.getElementById('lbl-a').textContent = 'hod';
                document.getElementById('lbl-b').textContent = 'min';
                document.getElementById('lbl-c').textContent = 'sec';
            }

            label.textContent = text;
        }

        update();
        setInterval(update, 1000);
    }
</script>
</body>
</html>