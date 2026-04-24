<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['uzivatel_id'])) {
    header('Location: login.php');
    exit;
}

$db = getDB();

// Zrušení rezervace
if (isset($_GET['zrusit']) && is_numeric($_GET['zrusit'])) {
    $stmt = $db->prepare("
        UPDATE rezervace SET stav = 'zrusena'
        WHERE id = ? AND uzivatel_id = ?
    ");
    $stmt->execute([$_GET['zrusit'], $_SESSION['uzivatel_id']]);
    header('Location: my_reservations.php');
    exit;
}

// Načti rezervace uživatele
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
    <link rel="stylesheet" href="style.css">
    <title>Moje rezervace</title>
</head>
<body>
<h1>Moje rezervace</h1>
<nav>
    <a href="index.php">← Zpět</a> |
    <a href="reservation.php">Nová rezervace</a> |
    <a href="logout.php">Odhlásit se</a>
</nav>

<?php if (empty($rezervace)): ?>
    <p>Nemáš žádné rezervace. <a href="reservation.php">Vytvoř první rezervaci</a></p>
<?php else: ?>
    <table border="1" cellpadding="8">
        <thead>
        <tr>
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
                <td><?= htmlspecialchars($r['sportoviste_nazev']) ?></td>
                <td><?= htmlspecialchars($r['datum']) ?></td>
                <td><?= htmlspecialchars($r['cas_od']) ?></td>
                <td><?= htmlspecialchars($r['cas_do']) ?></td>
                <td><?= htmlspecialchars($r['stav']) ?></td>
                <td><?= htmlspecialchars($r['poznamka']) ?></td>
                <td>
                    <?php if ($r['stav'] !== 'zrusena'): ?>
                        <a href="my_reservations.php?zrusit=<?= $r['id'] ?>"
                           onclick="return confirm('Opravdu chceš zrušit rezervaci?')">
                            Zrušit
                        </a>
                    <?php else: ?>
                        <span style="color:gray">Zrušeno</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
</body>
</html>