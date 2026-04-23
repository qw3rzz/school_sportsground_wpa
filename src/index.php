<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['uzivatel_id'])) {
    header('Location: login.php');
    exit;
}

$db = getDB();
$stmt = $db->query("SELECT nazev FROM sportoviste");
$sporty = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <title>Školní sportoviště</title>
</head>
<body>
<h1>Vítej, <?= htmlspecialchars($_SESSION['uzivatel_jmeno']) ?>!</h1>

<nav>
    <a href="rezervace.php">Nová rezervace</a> |
    <a href="moje_rezervace.php">Moje rezervace</a> |
    <a href="logout.php">Odhlásit se</a>
</nav>

<h2>Dostupná sportoviště</h2>
<ul>
    <?php foreach ($sporty as $sport): ?>
        <li><?= htmlspecialchars($sport['nazev']) ?></li>
    <?php endforeach; ?>
</ul>
</body>
</html>