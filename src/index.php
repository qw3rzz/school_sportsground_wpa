<?php
require_once 'config/database.php';

$db = getDB();
$stmt = $db->query("SELECT nazev FROM sportoviste");
$sporty = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <title>Sportovní rezervace</title>
</head>
<body>
<h1>Školní sportoviště</h1>
<ul>
    <?php foreach ($sporty as $sport): ?>
        <li><?= htmlspecialchars($sport['nazev']) ?></li>
    <?php endforeach; ?>
</ul>
</body>
</html>