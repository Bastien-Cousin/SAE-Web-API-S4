<?php
session_start();

// ─── Récupération sécurisée ─────────────────────────────
$team1_id   = $_GET['team1_id'] ?? '';
$team2_id   = $_GET['team2_id'] ?? '';

$team1_name = htmlspecialchars($_GET['team1_name'] ?? 'Équipe 1');
$team2_name = htmlspecialchars($_GET['team2_name'] ?? 'Équipe 2');

$team1_wr   = htmlspecialchars($_GET['team1_wr'] ?? 'N/A');
$team2_wr   = htmlspecialchars($_GET['team2_wr'] ?? 'N/A');

$bo         = htmlspecialchars($_GET['bo'] ?? '');
$heure      = htmlspecialchars($_GET['heure'] ?? '');

$mapsRaw = $_GET['maps'] ?? '[]';
$maps = json_decode($mapsRaw, true);
if (!is_array($maps)) $maps = [];

// ─── Mode édition ──────────────────────────────────────
$isEdit = isset($_GET['edit']);

$host     = getenv('POSTGRES_HOST')     ?: 'db';
$port     = getenv('POSTGRES_PORT')     ?: '5432';
$dbname   = getenv('POSTGRES_DB')       ?: 'sae_db';
$user     = getenv('POSTGRES_USER')     ?: 'sae_user';
$password = getenv('POSTGRES_PASSWORD') ?: 'sae_password';
// ─── Liste des équipes et BO possibles ─────────────────
try {
    $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("❌ Erreur de connexion : " . $e->getMessage());
}

$teamsStmt = $pdo->query("SELECT pageid, name FROM team ORDER BY name ASC");
$teams = $teamsStmt->fetchAll(PDO::FETCH_ASSOC);


$teamsList = array_column($teams, 'name');
$boOptions = ['BO1', 'BO3', 'BO5'];

// ─── Recréer URL retour avec params ─────────────────────
$params = http_build_query([
    'team1_id' => $team1_id,
    'team2_id' => $team2_id,
    'team1_name' => $team1_name,
    'team2_name' => $team2_name,
    'team1_wr' => $team1_wr,
    'team2_wr' => $team2_wr,
    'bo' => $bo,
    'heure' => $heure,
    'maps' => $mapsRaw
]);

$backUrl = "Prediction.php?$params";
$allMaps = ['Dust2', 'Inferno', 'Mirage', 'Nuke', 'Overpass', 'Vertigo', 'Ancient'];
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Récap prédiction</title>
    <link rel="stylesheet" href="userPrediction.css">
    <link rel="stylesheet" href="../../components/ComponentNavbar/navbar.css">
</head>

<body>

    <?php include '../../components/ComponentNavbar/navbar.php'; ?>

    <div class="wrapper">
        <div class="section-title">Récapitulatif de votre prédiction</div>

        <div class="recap-card">
            <div class="recap-title">Votre composition</div>

            <!-- Matchup -->
            <div class="recap-matchup">

                <div class="recap-team">
                    <?php if ($isEdit): ?>
                        <select id="team1_name">
                            <?php foreach ($teamsList as $team): ?>
                                <option value="<?= $team ?>" <?= $team == $team1_name ? 'selected' : '' ?>><?= $team ?></option>
                            <?php endforeach; ?>
                        </select>
                    <?php else: ?>
                        <div class="team-name"><?= $team1_name ?></div>
                    <?php endif; ?>
                    <div class="wr-pill">WR <?= $team1_wr ?></div>
                </div>

                <div class="recap-center">
                    <div class="vs-text">VS</div>

                    <?php if ($isEdit): ?>
                        <select id="bo">
                            <?php foreach ($boOptions as $option): ?>
                                <option value="<?= $option ?>" <?= $option == $bo ? 'selected' : '' ?>><?= $option ?></option>
                            <?php endforeach; ?>
                        </select>
                    <?php else: ?>
                        <?php if ($bo): ?><div class="bo-pill"><?= $bo ?></div><?php endif; ?>
                    <?php endif; ?>
                </div>

                <div class="recap-team">
                    <?php if ($isEdit): ?>
                        <select id="team2_name">
                            <?php foreach ($teamsList as $team): ?>
                                <option value="<?= $team ?>" <?= $team == $team2_name ? 'selected' : '' ?>><?= $team ?></option>
                            <?php endforeach; ?>
                        </select>
                    <?php else: ?>
                        <div class="team-name"><?= $team2_name ?></div>
                    <?php endif; ?>
                    <div class="wr-pill">WR <?= $team2_wr ?></div>
                </div>

            </div>

            <!-- Maps -->
            <?php if (!empty($maps)): ?>
                <table class="maps-table">
                    <thead>
                        <tr>
                            <th>Map</th>
                            <th><?= $team1_name ?></th>
                            <th></th>
                            <th><?= $team2_name ?></th>
                        </tr>
                    </thead>
                    <tbody id="maps-tbody">
                        <?php foreach ($maps as $i => $m): ?>
                            <tr>
                                <td>
                                    <?php if ($isEdit): ?>
                                        <select class="map">
                                            <?php foreach ($allMaps as $mapOption): ?>
                                                <option value="<?= $mapOption ?>" <?= ($mapOption == ($m['map'] ?? '')) ? 'selected' : '' ?>><?= $mapOption ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    <?php else: ?>
                                        M<?= $i + 1 ?> - <?= htmlspecialchars($m['map'] ?? '—') ?>
                                    <?php endif; ?>
                                </td>
                                <?php if ($isEdit): ?>
                                    <td><input type="number" class="score_t1" value="<?= $m['score_t1'] ?? '' ?>"></td>
                                    <td>–</td>
                                    <td><input type="number" class="score_t2" value="<?= $m['score_t2'] ?? '' ?>"></td>
                                <?php else: ?>
                                    <td><?= $m['score_t1'] ?? '?' ?></td>
                                    <td>–</td>
                                    <td><?= $m['score_t2'] ?? '?' ?></td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <!-- Actions -->
            <div class="recap-actions">

                <?php if (!$isEdit): ?>
                    <a href="?<?= $params ?>&edit=1" class="btn-back">✏️ Modifier</a>
                <?php else: ?>
                    <button class="btn-back" onclick="saveEdit()">💾 Enregistrer</button>
                <?php endif; ?>

                <button class="btn-confirm" onclick="confirmPrediction()">Confirmer ✓</button>

            </div>
        </div>
    </div>

    <script>
        const boMapsCount = {
            'BO1': 1,
            'BO3': 3,
            'BO5': 5
        };
        document.getElementById("bo").addEventListener("change", function() {
            const selectedBO = this.value;
            const nbMaps = boMapsCount[selectedBO];
            const tbody = document.getElementById("maps-tbody");
            const currentRows = tbody.querySelectorAll("tr").length;

            // Ajouter des lignes si nécessaire
            for (let i = currentRows; i < nbMaps; i++) {
                const row = document.createElement("tr");
                row.innerHTML = `
            <td>
                <select class="map">
                    <?php foreach ($allMaps as $mapOption): ?>
                        <option value="<?= $mapOption ?>"><?= $mapOption ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
            <td><input type="number" class="score_t1"></td>
            <td>–</td>
            <td><input type="number" class="score_t2"></td>
        `;
                tbody.appendChild(row);
            }

            // Supprimer des lignes si nécessaire
            for (let i = currentRows - 1; i >= nbMaps; i--) {
                tbody.removeChild(tbody.querySelectorAll("tr")[i]);
            }
        });

        function saveEdit() {
            const params = new URLSearchParams(window.location.search);

            params.set("team1_name", document.getElementById("team1_name").value);
            params.set("team2_name", document.getElementById("team2_name").value);
            params.set("bo", document.getElementById("bo").value);
            const heureInput = document.getElementById("heure");
            if (heureInput) {
                params.set("heure", heureInput.value);
            }

            const maps = [];
            document.querySelectorAll("tbody tr").forEach(row => {
                maps.push({
                    map: row.querySelector(".map")?.value,
                    score_t1: row.querySelector(".score_t1")?.value,
                    score_t2: row.querySelector(".score_t2")?.value
                });
            });

            params.set("maps", JSON.stringify(maps));
            params.delete("edit"); // sortir du mode édition
            window.location.href = "userPrediction.php?" + params.toString();
        }

        function confirmPrediction() {
            alert("Prédiction enregistrée !");
            window.location.href = "Prediction.php";
        }
    </script>

</body>

</html>