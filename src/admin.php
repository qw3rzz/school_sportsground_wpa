<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['uzivatel_id']) || $_SESSION['uzivatel_role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

$db = getDB();

// Změna stavu rezervace
if (isset($_GET['stav']) && isset($_GET['id']) && is_numeric($_GET['id'])) {
    $novy_stav = in_array($_GET['stav'], ['potvrzena', 'zrusena', 'cekajici']) ? $_GET['stav'] : 'cekajici';
    $stmt = $db->prepare("UPDATE rezervace SET stav = ? WHERE id = ?");
    $stmt->execute([$novy_stav, $_GET['id']]);
    header('Location: admin.php');
    exit;
}

// Filtrování
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
    <link rel="stylesheet" href="style.css">
    <title>SpotBook — Admin</title>
</head>
<body>
<h1>Admin — správa rezervací</h1>
<nav>
    <a href="index.php">← Zpět</a> |
    <a href="logout.php">Odhlásit se</a>
</nav>

<h2>Filtrovat rezervace</h2>
<form method="GET" action="admin.php">
    <label>Sportoviště:
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

    <label>Stav:
        <select name="stav_filter">
            <option value="">-- Všechny --</option>
            <option value="cekajici"  <?= ($_GET['stav_filter'] ?? '') === 'cekajici'  ? 'selected' : '' ?>>Čekající</option>
            <option value="potvrzena" <?= ($_GET['stav_filter'] ?? '') === 'potvrzena' ? 'selected' : '' ?>>Potvrzená</option>
            <option value="zrusena"   <?= ($_GET['stav_filter'] ?? '') === 'zrusena'   ? 'selected' : '' ?>>Zrušená</option>
        </select>
    </label>

    <label>Datum od:
        <input type="date" name="datum_od" value="<?= htmlspecialchars($_GET['datum_od'] ?? '') ?>">
    </label>

    <label>Datum do:
        <input type="date" name="datum_do" value="<?= htmlspecialchars($_GET['datum_do'] ?? '') ?>">
    </label>

    <button type="submit">Filtrovat</button>
    <a href="admin.php">Resetovat</a>
</form>

<h2>Rezervace (<?= count($rezervace) ?>)</h2>

<?php if (empty($rezervace)): ?>
    <p>Žádné rezervace nenalezeny.</p>
<?php else: ?>
    <table border="1" cellpadding="8">
        <thead>
        <tr>
            <th>ID</th>
            <th>Uživatel</th>
            <th>Sportoviště</th>
            <th>Datum</th>
            <th>Čas od</th>
            <th>Čas do</th>
            <th>Stav</th>
            <th>Poznámka</th>
            <th>Akce</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($rezervace as $r): ?>
            <tr>
                <td><?= $r['id'] ?></td>
                <td><?= htmlspecialchars($r['jmeno'] . ' ' . $r['prijmeni']) ?><br>
                    <small><?= htmlspecialchars($r['email']) ?></small>
                </td>
                <td><?= htmlspecialchars($r['sportoviste_nazev']) ?></td>
                <td><?= htmlspecialchars($r['datum']) ?></td>
                <td><?= htmlspecialchars($r['cas_od']) ?></td>
                <td><?= htmlspecialchars($r['cas_do']) ?></td>
                <td><?= htmlspecialchars($r['stav']) ?></td>
                <td><?= htmlspecialchars($r['poznamka']) ?></td>
                <td>
                    <a href="admin.php?id=<?= $r['id'] ?>&stav=potvrzena">✅ Potvrdit</a><br>
                    <a href="admin.php?id=<?= $r['id'] ?>&stav=zrusena">❌ Zrušit</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
</body>
</html>