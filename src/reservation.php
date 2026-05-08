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

    <?php if ($uspech): ?>
        <div class="success">
            <i class="fa-solid fa-circle-check"></i> <?= $uspech ?>
            <a href="my_reservations.php" style="margin-left:12px;color:#059669;font-weight:700;">
                Zobrazit moje rezervace →
            </a>
        </div>
    <?php endif; ?>

    <?php if (!empty($chyby)): ?>
        <div class="error-list" id="js-chyby">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <ul style="margin-top:6px">
                <?php foreach ($chyby as $chyba): ?>
                    <li><?= $chyba ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="res-card">
        <div class="res-header">
            <div class="res-header-icon">
                <i class="fa-solid fa-calendar-plus"></i>
            </div>
            <div>
                <h2>Nová rezervace</h2>
                <p>Vyplň formulář a rezervuj si sportoviště</p>
            </div>
        </div>

        <form method="POST" action="reservation.php">

            <div class="res-steps">

                <!-- Krok 1 — Sportoviště -->
                <div class="res-step">
                    <div class="res-step-label">
                        <span class="res-step-num">1</span>
                        <span>Sportoviště</span>
                    </div>
                    <div class="res-sport-grid">
                        <?php foreach ($sporty as $sport): ?>
                            <label class="res-sport-option">
                                <input type="radio" name="sportoviste_id"
                                       value="<?= $sport['id'] ?>"
                                        <?= ($_POST['sportoviste_id'] ?? '') == $sport['id'] ? 'checked' : '' ?>
                                       required>
                                <div class="res-sport-card">
                                    <i class="fa-solid fa-dumbbell"></i>
                                    <span><?= htmlspecialchars($sport['nazev']) ?></span>
                                    <small><?= $sport['kapacita'] ?> osob</small>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Krok 2 — Datum a počet -->
                <div class="res-step">
                    <div class="res-step-label">
                        <span class="res-step-num">2</span>
                        <span>Datum a počet osob</span>
                    </div>
                    <div class="res-row">
                        <label class="res-label">
                            <i class="fa-solid fa-calendar"></i> Datum
                            <input type="date" name="datum" required
                                   min="<?= date('Y-m-d') ?>"
                                   value="<?= htmlspecialchars($_POST['datum'] ?? '') ?>">
                        </label>
                        <label class="res-label">
                            <i class="fa-solid fa-users"></i> Počet osob
                            <input type="number" name="pocet_osob" required
                                   min="1" max="30"
                                   value="<?= htmlspecialchars($_POST['pocet_osob'] ?? '1') ?>">
                        </label>
                    </div>
                </div>

                <!-- Krok 3 — Čas od -->
                <div class="res-step">
                    <div class="res-step-label">
                        <span class="res-step-num">3</span>
                        <span>Čas od</span>
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

                <!-- Krok 4 — Čas do -->
                <div class="res-step">
                    <div class="res-step-label">
                        <span class="res-step-num">4</span>
                        <span>Čas do</span>
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

                <!-- Krok 5 — Poznámka -->
                <div class="res-step">
                    <div class="res-step-label">
                        <span class="res-step-num">5</span>
                        <span>Poznámka (nepovinné)</span>
                    </div>
                    <textarea name="poznamka" rows="3"
                              placeholder="Napiš poznámku k rezervaci..."><?= htmlspecialchars($_POST['poznamka'] ?? '') ?></textarea>
                </div>

            </div>

            <!-- Souhlas + submit -->
            <div class="res-footer">
                <label class="souhlas-box">
                    <input type="checkbox" name="souhlas"
                            <?= isset($_POST['souhlas']) ? 'checked' : '' ?> required>
                    <span>
                            <i class="fa-solid fa-shield-halved" style="color:#6366f1"></i>
                            Souhlasím s podmínkami rezervace
                        </span>
                </label>
                <button type="submit" class="submit-btn">
                    <i class="fa-solid fa-check"></i> Vytvořit rezervaci
                </button>
            </div>

        </form>
    </div>
</div>

<script>
    document.querySelector('form').addEventListener('submit', function(e) {
        let chyby = [];

        const sportoviste = document.querySelector('input[name="sportoviste_id"]:checked');
        if (!sportoviste) chyby.push('Vyber sportoviště.');

        const datum = document.querySelector('input[name="datum"]').value;
        if (!datum) {
            chyby.push('Vyber datum.');
        } else {
            const dnes = new Date().toISOString().split('T')[0];
            if (datum < dnes) chyby.push('Datum nemůže být v minulosti.');
        }

        const pocetOsob = parseInt(document.querySelector('input[name="pocet_osob"]').value);
        if (!pocetOsob || pocetOsob < 1 || pocetOsob > 30)
            chyby.push('Počet osob musí být mezi 1 a 30.');

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
            let el = document.getElementById('js-chyby');
            if (!el) {
                el = document.createElement('div');
                el.id = 'js-chyby';
                el.className = 'error-list';
                document.querySelector('.container').insertBefore(el, document.querySelector('.res-card'));
            }
            el.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i><ul style="margin-top:6px">' +
                chyby.map(c => `<li>${c}</li>`).join('') + '</ul>';
            window.scrollTo(0, 0);
        }
    });
</script>
</body>
</html>