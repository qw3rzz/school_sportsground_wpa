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
    <a href="reservation.php"><i class="fa-solid fa-plus"></i> Nová rezervace</a>
    <a href="my_reservations.php"><i class="fa-solid fa-list"></i> Moje rezervace</a>
    <?php if ($_SESSION['uzivatel_role'] === 'admin'): ?>
        <a href="admin.php"><i class="fa-solid fa-gear"></i> Admin</a>
    <?php endif; ?>
    <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Odhlásit se</a>
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
</body>
</html>