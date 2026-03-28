<?php
session_start();

// ─── Récupération sécurisée ──────────────────────────────────────────────────
$team1_id   = $_GET['team1_id'] ?? '';
$team2_id   = $_GET['team2_id'] ?? '';
$team1_name = htmlspecialchars($_GET['team1_name'] ?? 'Équipe 1');
$team2_name = htmlspecialchars($_GET['team2_name'] ?? 'Équipe 2');
$team1_wr   = htmlspecialchars($_GET['team1_wr']   ?? 'N/A');
$team2_wr   = htmlspecialchars($_GET['team2_wr']   ?? 'N/A');
$bo         = htmlspecialchars($_GET['bo']         ?? '');
$heure      = htmlspecialchars($_GET['heure']      ?? '');
$mapsRaw    = $_GET['maps'] ?? '[]';
$maps       = json_decode($mapsRaw, true);
if (!is_array($maps)) $maps = [];
$isEdit     = isset($_GET['edit']);

// ─── BDD ─────────────────────────────────────────────────────────────────────
$host     = getenv('POSTGRES_HOST')     ?: 'db';
$port     = getenv('POSTGRES_PORT')     ?: '5432';
$dbname   = getenv('POSTGRES_DB')       ?: 'sae_db';
$user     = getenv('POSTGRES_USER')     ?: 'sae_user';
$password = getenv('POSTGRES_PASSWORD') ?: 'sae_password';

try {
    $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("❌ Erreur de connexion : " . $e->getMessage());
}

$teamsStmt = $pdo->query("SELECT pageid, name FROM team ORDER BY name ASC");
$teams     = $teamsStmt->fetchAll(PDO::FETCH_ASSOC);
$teamsList = array_column($teams, 'name');
$boOptions = ['BO1', 'BO3', 'BO5'];
$allMaps   = ['Dust2', 'Inferno', 'Mirage', 'Nuke', 'Overpass', 'Vertigo', 'Ancient', 'Anubis'];

$params = http_build_query([
    'team1_id'   => $team1_id,
    'team2_id'   => $team2_id,
    'team1_name' => $team1_name,
    'team2_name' => $team2_name,
    'team1_wr'   => $team1_wr,
    'team2_wr'   => $team2_wr,
    'bo'         => $bo,
    'heure'      => $heure,
    'maps'       => $mapsRaw,
]);
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

            <!-- ── Matchup ─────────────────────────────────────────────────────── -->
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
                            <?php foreach ($boOptions as $opt): ?>
                                <option value="<?= $opt ?>" <?= $opt == $bo ? 'selected' : '' ?>><?= $opt ?></option>
                            <?php endforeach; ?>
                        </select>
                    <?php else: ?>
                        <?php if ($bo): ?><div class="bo-pill"><?= $bo ?></div><?php endif; ?>
                        <?php if ($heure): ?><div class="heure-pill"><?= $heure ?></div><?php endif; ?>
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

            <!-- ── Tableau des maps ─────────────────────────────────────────────── -->
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
                                        M<?= $i + 1 ?> – <?= htmlspecialchars($m['map'] ?? '—') ?>
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

            <!-- ── Section probabilités (injectée en JS, uniquement en lecture) ── -->
            <?php if ($team1_id && $team2_id && !$isEdit): ?>
                <div class="proba-section" id="proba-section">
                    <div class="proba-loading">⏳ Analyse des matchs en cours…</div>
                </div>
            <?php endif; ?>

            <!-- ── Actions ──────────────────────────────────────────────────────── -->
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
        const TEAM1_ID = <?= json_encode($team1_id) ?>;
        const TEAM2_ID = <?= json_encode($team2_id) ?>;
        const TEAM1_NAME = <?= json_encode($team1_name) ?>;
        const TEAM2_NAME = <?= json_encode($team2_name) ?>;
        const MAPS_DATA = <?= json_encode($maps) ?>;

        // ── CORRECTION : lit les valeurs réelles du DOM en priorité ────────────
        // Avant, MAPS_DATA (figé au chargement) était toujours utilisé,
        // donc les modifications de l'utilisateur n'étaient jamais prises en compte.
        function computeUserBias() {
            let wins1 = 0,
                wins2 = 0;

            const rows = document.querySelectorAll('#maps-tbody tr');

            if (rows.length > 0) {
                // Mode lecture : les scores sont dans des <td> en texte brut
                // Mode édition : les scores sont dans des <input> (classe score_t1 / score_t2)
                rows.forEach(row => {
                    const inputT1 = row.querySelector('.score_t1');
                    const inputT2 = row.querySelector('.score_t2');

                    let s1, s2;

                    if (inputT1 && inputT2) {
                        // Mode édition — lire les inputs
                        s1 = parseInt(inputT1.value);
                        s2 = parseInt(inputT2.value);
                    } else {
                        // Mode lecture — lire les cellules (index 1 et 3, séparateur "–" en index 2)
                        s1 = parseInt(row.cells[1]?.textContent?.trim());
                        s2 = parseInt(row.cells[3]?.textContent?.trim());
                    }

                    if (!isNaN(s1) && !isNaN(s2)) {
                        if (s1 > s2) wins1++;
                        else if (s2 > s1) wins2++;
                    }
                });
            } else {
                // Fallback sur les données PHP si le tableau est absent
                MAPS_DATA.forEach(m => {
                    const s1 = parseInt(m.score_t1);
                    const s2 = parseInt(m.score_t2);
                    if (!isNaN(s1) && !isNaN(s2)) {
                        if (s1 > s2) wins1++;
                        else if (s2 > s1) wins2++;
                    }
                });
            }

            const total = wins1 + wins2;
            return total > 0 ? wins1 / total : 0.5;
        }

        // ── Chargement & rendu des probabilités ────────────────────────────────
        async function loadProbabilities() {
            const section = document.getElementById('proba-section');
            if (!section) return;

            try {
                const res = await fetch(`predict.php?team1_id=${TEAM1_ID}&team2_id=${TEAM2_ID}`);
                if (!res.ok) throw new Error(`HTTP ${res.status}`);
                const d = await res.json();
                if (d.error) throw new Error(d.error);

                // Pondération selon BO
                let userWeight = 0.3;
                if ("<?= $bo ?>" === "BO1") userWeight = 0.2;
                if ("<?= $bo ?>" === "BO5") userWeight = 0.4;

                const eloProb1 = d.prob1 / 100;
                const userBias = computeUserBias();

                // Fusion proba serveur + biais utilisateur
                let finalProb1 =
                    (1 - userWeight) * eloProb1 +
                    userWeight * userBias;

                finalProb1 = Math.max(0.05, Math.min(0.95, finalProb1));
                const finalProb2 = 1 - finalProb1;

                // Pastilles forme
                const dots = (results, side) =>
                    `<div class="form-row ${side}">${results.map(r => `<span class="dot ${r}">${r}</span>`).join('')}</div>`;

                // H2H
                const h2hVal1 = d.h2h.count > 0 ?
                    `${d.h2h.wins1}V – ${d.h2h.wins2}D <small style="opacity:.45">(${d.h2h.count} matchs)</small>` :
                    '<span style="opacity:.35">Aucun face-à-face</span>';
                const h2hVal2 = d.h2h.count > 0 ?
                    `${d.h2h.wins2}V – ${d.h2h.wins1}D` :
                    '<span style="opacity:.35">–</span>';

                // Regions
                const reg1 = d.region?.team1 ? `<span class="region-tag">${d.region.team1}</span>` : '';
                const reg2 = d.region?.team2 ? `<span class="region-tag">${d.region.team2}</span>` : '';

                // Poids sous forme de pills
                const wLabels = [];
                if (d.weights.h2h > 0) wLabels.push(`H2H ${Math.round(d.weights.h2h * 100)}%`);
                wLabels.push(`Forme ${Math.round(d.weights.recent * 100)}%`);
                wLabels.push(`WR global ${Math.round(d.weights.global * 100)}%`);
                wLabels.push(`Gains ${Math.round(d.weights.earnings * 100)}%`);

                section.innerHTML = `
                <div class="proba-header">
                    <span style="font-size:1.1rem">📊</span>
                    <span class="proba-header-title">Analyse & probabilités de victoire</span>
                </div>

                <div class="proba-bar-wrap">
                    <div class="proba-team-label t1">
                        ${TEAM1_NAME}${reg1}
                    </div>
                    <div class="proba-bar">
                        <div class="proba-fill1" style="width:${(finalProb1*100).toFixed(1)}%">
                            ${(finalProb1*100).toFixed(1)}%
                        </div>
                        <div class="proba-fill2">
                            ${(finalProb2*100).toFixed(1)}%
                        </div>
                    </div>
                    <div class="proba-team-label t2">
                        ${TEAM2_NAME}${reg2}
                    </div>
                </div>

                <hr class="proba-divider">

                <div class="proba-grid">

                    <!-- H2H -->
                    <div class="pg-val1">${h2hVal1}</div>
                    <div class="pg-label">H2H</div>
                    <div class="pg-val2">${h2hVal2}</div>

                    <!-- Forme récente -->
                    <div class="pg-val1">
                        <strong>${d.recent1.wr}%</strong>
                        <small style="opacity:.5"> / ${d.recent1.count} matchs</small>
                        ${dots(d.recent1.results, 'left')}
                    </div>
                    <div class="pg-label">Forme<br>récente</div>
                    <div class="pg-val2">
                        <strong>${d.recent2.wr}%</strong>
                        <small style="opacity:.5"> / ${d.recent2.count} matchs</small>
                        ${dots(d.recent2.results, 'right')}
                    </div>

                    <!-- WR global -->
                    <div class="pg-val1"><strong>${d.global_wr1}%</strong></div>
                    <div class="pg-label">WR<br>global</div>
                    <div class="pg-val2"><strong>${d.global_wr2}%</strong></div>

                    <!-- Gains en tournois -->
                    <div class="pg-val1">
                        <span class="earnings-badge">${d.earnings.team1}</span>
                    </div>
                    <div class="pg-label">Gains<br>tournois</div>
                    <div class="pg-val2">
                        <span class="earnings-badge">${d.earnings.team2}</span>
                    </div>

                </div>

                <div class="proba-weights">
                    ${wLabels.map(l => `<span class="weight-pill">${l}</span>`).join('')}
                </div>
            `;
            } catch (err) {
                const s = document.getElementById('proba-section');
                if (s) s.innerHTML = `<div class="proba-error">⚠️ Impossible de calculer les probabilités (${err.message})</div>`;
            }
        }

        if (TEAM1_ID && TEAM2_ID) loadProbabilities();

        // ── Mode édition : gestion du BO ────────────────────────────────────────
        const boEl = document.getElementById("bo");
        if (boEl) {
            const boMapsCount = {
                'BO1': 1,
                'BO3': 3,
                'BO5': 5
            };
            const allMapsOpts = <?= json_encode($allMaps) ?>;

            boEl.addEventListener("change", function() {
                const nbMaps = boMapsCount[this.value] ?? 1;
                const tbody = document.getElementById("maps-tbody");
                const currentRows = tbody.querySelectorAll("tr").length;

                for (let i = currentRows; i < nbMaps; i++) {
                    const row = document.createElement("tr");
                    row.innerHTML = `
                        <td>
                            <select class="map">
                                ${allMapsOpts.map(m => `<option value="${m}">${m}</option>`).join('')}
                            </select>
                        </td>
                        <td><input type="number" class="score_t1" min="0" max="30"></td>
                        <td>–</td>
                        <td><input type="number" class="score_t2" min="0" max="30"></td>
                    `;
                    tbody.appendChild(row);
                }
                for (let i = currentRows - 1; i >= nbMaps; i--) {
                    tbody.removeChild(tbody.querySelectorAll("tr")[i]);
                }
            });
        }

        // ── Enregistrer (mode édition) ────────────────────────────────────────
        function saveEdit() {
            const params = new URLSearchParams(window.location.search);
            params.set("team1_name", document.getElementById("team1_name").value);
            params.set("team2_name", document.getElementById("team2_name").value);
            params.set("bo", document.getElementById("bo").value);
            const heureInput = document.getElementById("heure");
            if (heureInput) params.set("heure", heureInput.value);

            const maps = [];
            document.querySelectorAll("tbody tr").forEach(row => {
                maps.push({
                    map: row.querySelector(".map")?.value,
                    score_t1: row.querySelector(".score_t1")?.value,
                    score_t2: row.querySelector(".score_t2")?.value,
                });
            });
            params.set("maps", JSON.stringify(maps));
            params.delete("edit");
            window.location.href = "userPrediction.php?" + params.toString();
        }

        // ── Confirmer ──────────────────────────────────────────────────────────
        function confirmPrediction() {
            alert("Prédiction enregistrée !");
            window.location.href = "Prediction.php";
        }
    </script>

</body>

</html>