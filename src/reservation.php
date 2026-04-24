<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['uzivatel_id'])) {
    header('Location: login.php');
    exit;
}

$chyby = [];
$uspech = '';

$db = getDB();
$sporty = $db->query("SELECT id, nazev FROM sportoviste WHERE aktivni = 1")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sportoviste_id = (int)($_POST['sportoviste_id'] ?? 0);
    $datum          = trim($_POST['datum'] ?? '');
    $cas_od         = trim($_POST['cas_od'] ?? '');
    $cas_do         = trim($_POST['cas_do'] ?? '');
    $poznamka       = trim(htmlspecialchars($_POST['poznamka'] ?? ''));
    $souhlas        = isset($_POST['souhlas']);

    // Validace
    if ($sportoviste_id === 0)   $chyby[] = 'Vyber sportoviště.';
    if (empty($datum))           $chyby[] = 'Vyber datum.';
    if (empty($cas_od))          $chyby[] = 'Vyber čas od.';
    if (empty($cas_do))          $chyby[] = 'Vyber čas do.';
    if ($cas_od >= $cas_do)      $chyby[] = 'Čas od musí být před časem do.';
    if (!$souhlas)               $chyby[] = 'Musíš souhlasit s podmínkami.';
    if (strtotime($datum) < strtotime('today')) $chyby[] = 'Datum nemůže být v minulosti.';

    if (empty($chyby)) {
        // Kontrola kolize
        $stmt = $db->prepare("
            SELECT id FROM rezervace
            WHERE sportoviste_id = ?
            AND datum = ?
            AND stav != 'zrusena'
            AND cas_od < ?
            AND cas_do > ?
        ");
        $stmt->execute([$sportoviste_id, $datum, $cas_do, $cas_od]);

        if ($stmt->fetch()) {
            $chyby[] = 'Tento termín je již obsazený. Vyber jiný čas.';
        } else {
            $stmt = $db->prepare("
                INSERT INTO rezervace (uzivatel_id, sportoviste_id, datum, cas_od, cas_do, poznamka)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $_SESSION['uzivatel_id'],
                $sportoviste_id,
                $datum,
                $cas_od,
                $cas_do,
                $poznamka
            ]);
            $uspech = 'Rezervace byla úspěšně vytvořena!';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="style.css">
    <title>SpotBook — Nová rezervace</title>
</head>
<body>
<h1>Nová rezervace</h1>
<nav>
    <a href="index.php">← Zpět</a> |
    <a href="logout.php">Odhlásit se</a>
</nav>

<?php if ($uspech): ?>
    <p style="color:green"><?= $uspech ?></p>
<?php endif; ?>

<?php if (!empty($chyby)): ?>
    <ul style="color:red">
        <?php foreach ($chyby as $chyba): ?>
            <li><?= $chyba ?></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<form method="POST" action="reservation.php">

    <!-- SELECT - výběr sportoviště -->
    <label>Sportoviště:
        <select name="sportoviste_id" required>
            <option value="">-- Vyber sportoviště --</option>
            <?php foreach ($sporty as $sport): ?>
                <option value="<?= $sport['id'] ?>"
                    <?= ($_POST['sportoviste_id'] ?? '') == $sport['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($sport['nazev']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label><br><br>

    <!-- DATE - výběr data -->
    <label>Datum:
        <input type="date" name="datum" required
               min="<?= date('Y-m-d') ?>"
               value="<?= htmlspecialchars($_POST['datum'] ?? '') ?>">
    </label><br><br>

    <!-- RADIO - výběr času od -->
    <label>Čas od:</label><br>
    <?php
    $casy = ['08:00', '09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00', '18:00'];
    foreach ($casy as $cas):
        ?>
        <label>
            <input type="radio" name="cas_od" value="<?= $cas ?>"
                <?= ($_POST['cas_od'] ?? '') === $cas ? 'checked' : '' ?> required>
            <?= $cas ?>
        </label>
    <?php endforeach; ?>
    <br><br>

    <!-- RADIO - výběr času do -->
    <label>Čas do:</label><br>
    <?php foreach ($casy as $cas): ?>
        <label>
            <input type="radio" name="cas_do" value="<?= $cas ?>"
                <?= ($_POST['cas_do'] ?? '') === $cas ? 'checked' : '' ?> required>
            <?= $cas ?>
        </label>
    <?php endforeach; ?>
    <br><br>

    <!-- TEXT - poznámka -->
    <label>Poznámka (nepovinné):
        <textarea name="poznamka" rows="3" cols="40"><?= htmlspecialchars($_POST['poznamka'] ?? '') ?></textarea>
    </label><br><br>

    <!-- CHECKBOX - souhlas -->
    <label>
        <input type="checkbox" name="souhlas" <?= isset($_POST['souhlas']) ? 'checked' : '' ?> required>
        Souhlasím s podmínkami rezervace
    </label><br><br>

    <button type="submit">Vytvořit rezervaci</button>
</form>

<script>
    document.querySelector('form').addEventListener('submit', function(e) {
        let chyby = [];

        // Kontrola sportoviště
        const sportoviste = document.querySelector('select[name="sportoviste_id"]').value;
        if (sportoviste === '') {
            chyby.push('Vyber sportoviště.');
        }

        // Kontrola data
        const datum = document.querySelector('input[name="datum"]').value;
        if (datum === '') {
            chyby.push('Vyber datum.');
        } else {
            const dnes = new Date().toISOString().split('T')[0];
            if (datum < dnes) {
                chyby.push('Datum nemůže být v minulosti.');
            }
        }

        // Kontrola času od
        const casOd = document.querySelector('input[name="cas_od"]:checked');
        if (!casOd) {
            chyby.push('Vyber čas od.');
        }

        // Kontrola času do
        const casDo = document.querySelector('input[name="cas_do"]:checked');
        if (!casDo) {
            chyby.push('Vyber čas do.');
        }

        // Kontrola že čas od je před časem do
        if (casOd && casDo && casOd.value >= casDo.value) {
            chyby.push('Čas od musí být před časem do.');
        }

        // Kontrola souhlasu
        const souhlas = document.querySelector('input[name="souhlas"]').checked;
        if (!souhlas) {
            chyby.push('Musíš souhlasit s podmínkami.');
        }

        // Zobraz chyby nebo odešli formulář
        if (chyby.length > 0) {
            e.preventDefault();

            // Smaž staré chyby
            const stareChyby = document.getElementById('js-chyby');
            if (stareChyby) stareChyby.remove();

            // Vytvoř nový seznam chyb
            const div = document.createElement('div');
            div.id = 'js-chyby';
            div.style.color = 'red';

            const ul = document.createElement('ul');
            chyby.forEach(function(chyba) {
                const li = document.createElement('li');
                li.textContent = chyba;
                ul.appendChild(li);
            });

            div.appendChild(ul);
            document.querySelector('form').insertBefore(div, document.querySelector('form').firstChild);
            window.scrollTo(0, 0);
        }
    });
</script>

</body>
</html>