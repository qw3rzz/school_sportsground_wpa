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
$sporty = $db->query("SELECT id, nazev, kapacita FROM sportoviste WHERE aktivni = 1")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sportoviste_id = (int)($_POST['sportoviste_id'] ?? 0);
    $datum          = trim($_POST['datum'] ?? '');
    $cas_od         = trim($_POST['cas_od'] ?? '');
    $cas_do         = trim($_POST['cas_do'] ?? '');
    $poznamka       = trim(htmlspecialchars($_POST['poznamka'] ?? ''));
    $pocet_osob     = (int)($_POST['pocet_osob'] ?? 1);
    $souhlas        = isset($_POST['souhlas']);

    if ($sportoviste_id === 0)   $chyby[] = 'Vyber sportoviště.';
    if (empty($datum))           $chyby[] = 'Vyber datum.';
    if (empty($cas_od))          $chyby[] = 'Vyber čas od.';
    if (empty($cas_do))          $chyby[] = 'Vyber čas do.';
    if ($cas_od >= $cas_do)      $chyby[] = 'Čas od musí být před časem do.';
    if ($pocet_osob < 1 || $pocet_osob > 30) $chyby[] = 'Počet osob musí být mezi 1 a 30.';
    if (!$souhlas)               $chyby[] = 'Musíš souhlasit s podmínkami.';
    if (!empty($datum) && strtotime($datum) < strtotime('today')) $chyby[] = 'Datum nemůže být v minulosti.';

    if (empty($chyby)) {
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
                INSERT INTO rezervace (uzivatel_id, sportoviste_id, datum, cas_od, cas_do, poznamka, pocet_osob)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                    $_SESSION['uzivatel_id'],
                    $sportoviste_id,
                    $datum,
                    $cas_od,
                    $cas_do,
                    $poznamka,
                    $pocet_osob
            ]);
            $uspech = 'Rezervace byla úspěšně vytvořena!';
        }
    }
}

$casy = ['08:00','09:00','10:00','11:00','12:00','13:00','14:00','15:00','16:00','17:00','18:00'];
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <title>SpotBook — Nová rezervace</title>
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

    <?php if ($uspech): ?>
        <div class="success">
            <i class="fa-solid fa-circle-check"></i> <?= $uspech ?>
            <a href="my_reservations.php" style="margin-left:12px;color:#059669;font-weight:700;">
                Zobrazit moje rezervace →
            </a>
        </div>
    <?php endif; ?>

    <?php if (!empty($chyby)): ?>
        <div class="error-list">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <ul style="margin-top:6px">
                <?php foreach ($chyby as $chyba): ?>
                    <li><?= $chyba ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="card">
        <h2><i class="fa-solid fa-calendar-plus"></i> Nová rezervace</h2>

        <form method="POST" action="reservation.php">

            <!-- Krok 1 -->
            <div class="section-box">
                <div class="step">
                    <div class="step-number">1</div>
                    <div class="step-title">Vyber sportoviště, datum a počet osob</div>
                </div>
                <div class="form-grid">
                    <label>Sportoviště
                        <select name="sportoviste_id" required>
                            <option value="">-- Vyber sportoviště --</option>
                            <?php foreach ($sporty as $sport): ?>
                                <option value="<?= $sport['id'] ?>"
                                        <?= ($_POST['sportoviste_id'] ?? '') == $sport['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($sport['nazev']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <label>Datum
                        <input type="date" name="datum" required
                               min="<?= date('Y-m-d') ?>"
                               value="<?= htmlspecialchars($_POST['datum'] ?? '') ?>">
                    </label>

                    <label>Počet osob
                        <input type="number" name="pocet_osob" required
                               min="1" max="30"
                               value="<?= htmlspecialchars($_POST['pocet_osob'] ?? '1') ?>">
                    </label>
                </div>
            </div>

            <!-- Krok 2 -->
            <div class="section-box">
                <div class="step">
                    <div class="step-number">2</div>
                    <div class="step-title">Vyber čas od</div>
                </div>
                <div class="time-grid">
                    <?php foreach ($casy as $cas): ?>
                        <input type="radio" name="cas_od"
                               id="od_<?= str_replace(':', '', $cas) ?>"
                               value="<?= $cas ?>"
                                <?= ($_POST['cas_od'] ?? '') === $cas ? 'checked' : '' ?> required>
                        <label for="od_<?= str_replace(':', '', $cas) ?>"><?= $cas ?></label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Krok 3 -->
            <div class="section-box">
                <div class="step">
                    <div class="step-number">3</div>
                    <div class="step-title">Vyber čas do</div>
                </div>
                <div class="time-grid">
                    <?php foreach ($casy as $cas): ?>
                        <input type="radio" name="cas_do"
                               id="do_<?= str_replace(':', '', $cas) ?>"
                               value="<?= $cas ?>"
                                <?= ($_POST['cas_do'] ?? '') === $cas ? 'checked' : '' ?> required>
                        <label for="do_<?= str_replace(':', '', $cas) ?>"><?= $cas ?></label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Krok 4 -->
            <div class="section-box">
                <div class="step">
                    <div class="step-number">4</div>
                    <div class="step-title">Poznámka (nepovinné)</div>
                </div>
                <textarea name="poznamka" rows="3"
                          placeholder="Napiš poznámku k rezervaci..."><?= htmlspecialchars($_POST['poznamka'] ?? '') ?></textarea>
            </div>

            <!-- Souhlas -->
            <label class="souhlas-box">
                <input type="checkbox" name="souhlas"
                        <?= isset($_POST['souhlas']) ? 'checked' : '' ?> required>
                <span><i class="fa-solid fa-shield-halved" style="color:#6366f1"></i> Souhlasím s podmínkami rezervace sportoviště</span>
            </label>

            <button type="submit" class="submit-btn">
                <i class="fa-solid fa-check"></i> Vytvořit rezervaci
            </button>

        </form>
    </div>
</div>

<script>
    document.querySelector('form').addEventListener('submit', function(e) {
        let chyby = [];

        const sportoviste = document.querySelector('select[name="sportoviste_id"]').value;
        if (sportoviste === '') chyby.push('Vyber sportoviště.');

        const datum = document.querySelector('input[name="datum"]').value;
        if (datum === '') {
            chyby.push('Vyber datum.');
        } else {
            const dnes = new Date().toISOString().split('T')[0];
            if (datum < dnes) chyby.push('Datum nemůže být v minulosti.');
        }

        const pocetOsob = parseInt(document.querySelector('input[name="pocet_osob"]').value);
        if (!pocetOsob || pocetOsob < 1 || pocetOsob > 30) chyby.push('Počet osob musí být mezi 1 a 30.');

        const casOd = document.querySelector('input[name="cas_od"]:checked');
        if (!casOd) chyby.push('Vyber čas od.');

        const casDo = document.querySelector('input[name="cas_do"]:checked');
        if (!casDo) chyby.push('Vyber čas do.');

        if (casOd && casDo && casOd.value >= casDo.value)
            chyby.push('Čas od musí být před časem do.');

        const souhlas = document.querySelector('input[name="souhlas"]').checked;
        if (!souhlas) chyby.push('Musíš souhlasit s podmínkami.');

        if (chyby.length > 0) {
            e.preventDefault();
            const stare = document.getElementById('js-chyby');
            if (stare) stare.remove();

            const div = document.createElement('div');
            div.id = 'js-chyby';
            div.className = 'error-list';

            const ul = document.createElement('ul');
            chyby.forEach(function(chyba) {
                const li = document.createElement('li');
                li.textContent = chyba;
                ul.appendChild(li);
            });

            div.appendChild(ul);
            document.querySelector('.container').insertBefore(div, document.querySelector('.card'));
            window.scrollTo(0, 0);
        }
    });
</script>
</body>
</html>