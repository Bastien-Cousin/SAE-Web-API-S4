<?php
session_start();

$host = getenv('POSTGRES_HOST') ?: 'db';
$port = getenv('POSTGRES_PORT') ?: '5432';
$dbname = getenv('POSTGRES_DB') ?: 'sae_db';
$user = getenv('POSTGRES_USER') ?: 'sae_user';
$password = getenv('POSTGRES_PASSWORD') ?: 'sae_password';

try {
    $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("❌ Erreur de connexion : " . $e->getMessage());
}

$teamsStmt = $pdo->query("SELECT pageid, name FROM team ORDER BY name ASC");
$teams = $teamsStmt->fetchAll(PDO::FETCH_ASSOC);

$wrStmt = $pdo->query("
    SELECT team_id,
            COUNT(*) AS total,
            SUM(CASE WHEN is_winner THEN 1 ELSE 0 END) AS wins
    FROM (
        SELECT team1_id AS team_id, (winner = '1') AS is_winner
        FROM match WHERE finished = true AND team1_id IS NOT NULL
        UNION ALL
        SELECT team2_id AS team_id, (winner = '2') AS is_winner
        FROM match WHERE finished = true AND team2_id IS NOT NULL
    ) sub
    GROUP BY team_id
");
$wrMap = [];
foreach ($wrStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $pct = $row['total'] > 0 ? round(($row['wins'] / $row['total']) * 100) : 0;
    $wrMap[$row['team_id']] = $pct . '%';
}

$statsData = [];
foreach ($teams as $t) {
    $statsData[$t['pageid']] = [
        'name' => $t['name'],
        'wr' => $wrMap[$t['pageid']] ?? 'N/A',
    ];
}
$statsJson = json_encode($statsData, JSON_HEX_TAG);


$h2hStmt = $pdo->query("
    SELECT 
        team1_id,
        team2_id,
        SUM(CASE WHEN winner = '1' THEN 1 ELSE 0 END) AS team1_wins,
        SUM(CASE WHEN winner = '2' THEN 1 ELSE 0 END) AS team2_wins
    FROM match
    WHERE finished = true
    AND team1_id IS NOT NULL 
    AND team2_id IS NOT NULL
    GROUP BY team1_id, team2_id
");

$h2h = [];
foreach ($h2hStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $h2h[$row['team1_id']][$row['team2_id']] = [
        'wins' => $row['team1_wins'],
        'losses' => $row['team2_wins']
    ];
    $h2h[$row['team2_id']][$row['team1_id']] = [
        'wins' => $row['team2_wins'],
        'losses' => $row['team1_wins']
    ];
}

$h2hJson = json_encode($h2h, JSON_HEX_TAG);
$calStmt = $pdo->query("
    SELECT m.date, m.tournament, t1.name AS team1, t2.name AS team2
    FROM match m
    LEFT JOIN team t1 ON t1.pageid = m.team1_id
    LEFT JOIN team t2 ON t2.pageid = m.team2_id
    WHERE m.date BETWEEN NOW() - INTERVAL '30 days' AND NOW() + INTERVAL '90 days'
        AND COALESCE(m.status, '') <> 'prediction_only'
        AND m.team1_id IS NOT NULL AND m.team2_id IS NOT NULL
    ORDER BY m.date ASC LIMIT 200
");
$calEvents = [];
foreach ($calStmt->fetchAll(PDO::FETCH_ASSOC) as $match) {
    if (!$match['date'])
        continue;
    $calEvents[] = [
        'title' => $match['team1'] . ' vs ' . $match['team2'],
        'date' => (new DateTime($match['date']))->format('Y-m-d'),
        'extendedProps' => ['tournament' => $match['tournament'] ?? ''],
    ];
}
$calEventsJson = json_encode($calEvents, JSON_HEX_TAG);

$userPredictions = [];
$predictionMessage = null;

if (isset($_GET['saved']) && $_GET['saved'] === '1') {
    $predictionMessage = 'Prédiction enregistrée avec succès.';
}

$predictionUserId = null;
if (isset($_SESSION['user']['id'])) {
    $predictionUserId = (int) $_SESSION['user']['id'];
} elseif (isset($_SESSION['guest_prediction_user_id'])) {
    $predictionUserId = (int) $_SESSION['guest_prediction_user_id'];
}

if ($predictionUserId !== null) {
    $predStmt = $pdo->prepare(
        "SELECT
            p.id,
            p.predicted_winner,
            p.created_at,
            m.finished,
            m.winner,
            COALESCE(t1.name, 'Équipe 1') AS team1,
            COALESCE(t2.name, 'Équipe 2') AS team2
        FROM prediction p
        INNER JOIN match m ON m.pageid = p.match_id
        LEFT JOIN team t1 ON t1.pageid = m.team1_id
        LEFT JOIN team t2 ON t2.pageid = m.team2_id
        WHERE p.user_id = :user_id
        ORDER BY p.created_at DESC
        LIMIT 20"
    );
    $predStmt->execute([':user_id' => $predictionUserId]);
    $predRows = $predStmt->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($predRows)) {
        $predictionIds = array_column($predRows, 'id');
        $placeholders = implode(',', array_fill(0, count($predictionIds), '?'));
        $gamesStmt = $pdo->prepare(
            "SELECT prediction_id, game_number, map, team1_score, team2_score, predicted_winner
             FROM prediction_game
             WHERE prediction_id IN ($placeholders)
             ORDER BY game_number ASC"
        );
        $gamesStmt->execute($predictionIds);
        $gamesByPrediction = [];
        foreach ($gamesStmt->fetchAll(PDO::FETCH_ASSOC) as $gameRow) {
            $gamesByPrediction[$gameRow['prediction_id']][] = $gameRow;
        }

        foreach ($predRows as $row) {
            $games = $gamesByPrediction[$row['id']] ?? [];
            $predWins1 = 0;
            $predWins2 = 0;
            $maps = [];

            foreach ($games as $game) {
                if (!empty($game['map'])) {
                    $maps[] = $game['map'];
                }

                if ($game['predicted_winner'] == 1) {
                    $predWins1++;
                } elseif ($game['predicted_winner'] == 2) {
                    $predWins2++;
                } elseif ($game['team1_score'] !== null && $game['team2_score'] !== null) {
                    if ((int) $game['team1_score'] > (int) $game['team2_score']) {
                        $predWins1++;
                    } elseif ((int) $game['team2_score'] > (int) $game['team1_score']) {
                        $predWins2++;
                    }
                }
            }

            if ($predWins1 === 0 && $predWins2 === 0) {
                $predictedScore = 'Score non renseigné';
            } else {
                $predictedScore = $predWins1 . ' - ' . $predWins2;
            }

            $statusClass = 'pending';
            $realScore = 'Match non terminé';
            if ($row['finished']) {
                $realScore = $row['winner'] === '1'
                    ? 'Victoire ' . $row['team1']
                    : ($row['winner'] === '2' ? 'Victoire ' . $row['team2'] : 'Résultat indisponible');

                $statusClass = ((string) $row['winner'] === (string) $row['predicted_winner']) ? 'correct' : 'wrong';
            }

            $userPredictions[] = [
                'team1' => $row['team1'],
                'team2' => $row['team2'],
                'predicted_score' => $predictedScore,
                'real_score' => $realScore,
                'correct' => $statusClass === 'correct',
                'status_class' => $statusClass,
                'maps' => implode(', ', array_unique($maps)),
                'created_at' => $row['created_at'],
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Prédictions</title>
        <link rel="stylesheet" href="Predictions.css">
        <link rel="stylesheet" href="../../components/ComponentNavbar/navbar.css">
        <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet">
        <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
        <link href="https://cdn.jsdelivr.net/npm/tom-select/dist/css/tom-select.css" rel="stylesheet">
        <script src="https://cdn.jsdelivr.net/npm/tom-select/dist/js/tom-select.complete.min.js"></script>
    </head>

    <body>
        <?php include '../../components/ComponentNavbar/navbar.php'; ?>

        <nav class="nav-predic">
            <a href="#compo">Composition</a>
            <a href="#pred">Prédiction</a>
            <a href="#cal">Calendrier</a>
        </nav>

        <div class="wrapper">

            <div class="section-title" id="compo">Votre composition</div>

            <div class="filters">
                <select id="team1">
                    <option value="">Équipe 1</option>
                    <?php foreach ($teams as $t): ?>
                        <option value="<?= $t['pageid'] ?>"><?= htmlspecialchars($t['name']) ?></option>
                    <?php endforeach; ?>
                </select>

                <select id="team2">
                    <option value="">Équipe 2</option>
                    <?php foreach ($teams as $t): ?>
                        <option value="<?= $t['pageid'] ?>"><?= htmlspecialchars($t['name']) ?></option>
                    <?php endforeach; ?>
                </select>

                <select id="bo">
                    <option value="">Best of</option>
                    <option value="1">BO1</option>
                    <option value="3">BO3</option>
                    <option value="5">BO5</option>
                </select>

                <input type="time" id="heure">
            </div>

            <div class="comp-grid">
                <div class="team-box left">
                    <span id="name1">équipe 1</span>
                </div>
                <div class="vs-block">
                    <div class="bestof-badge">Best of</div>
                    <div class="heure-badge">heure</div>
                </div>
                <div class="team-box right">
                    <span id="name2">équipe 2</span>
                </div>
            </div>

            <div class="stats-row">
                <div class="stats-side left-stats">
                    <div class="stat-tag">WR</div>
                </div>
                <div class="stats-side right-stats">
                    <div class="stat-tag">WR</div>
                </div>
            </div>

            <div id="maps-container"></div>

            <div class="comp-footer">
                <button id="btn-lancer">Lancer</button>
            </div>


            <div class="section-title" id="pred">Vos prédictions</div>

            <?php if ($predictionMessage): ?>
                <p style="text-align:center;color:#2f7f34;font-weight:600;"><?= htmlspecialchars($predictionMessage) ?></p>
            <?php endif; ?>

            <?php if (empty($userPredictions)): ?>
                <p style="text-align:center;opacity:.6;">Aucune prédiction enregistrée.</p>
            <?php else: ?>
                <?php foreach ($userPredictions as $pred): ?>
                    <div class="pred-card <?= htmlspecialchars($pred['status_class']) ?>">
                        <div class="pred-label">Préd.</div>
                        <div class="pred-scores">
                            <div class="pred-score"><?= htmlspecialchars($pred['predicted_score']) ?></div>
                        </div>
                        <div
                            class="pred-logos">
                            <?= htmlspecialchars($pred['team1']) ?>
                            vs
                            <?= htmlspecialchars($pred['team2']) ?>
                        </div>
                        <div class="real-label">Réalité</div>
                        <div class="real-scores">
                            <div class="real-score"><?= htmlspecialchars($pred['real_score']) ?></div>
                        </div>
                    </div>
                    <?php if (!empty($pred['maps'])): ?>
                        <p style="margin:-6px 0 14px;color:#666;font-size:.9rem;">Maps :
                            <?= htmlspecialchars($pred['maps']) ?>
                        </p>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
                <div class="section-title" id="cal"> Calendrier</div>
            <div class="calendar-box">
                <div id="calendar"></div>
            </div>

        </div>

        <script>
            const stats = <?= $statsJson ?>;
                                                        const h2h = <?= $h2hJson ?>;
                                                
                                                        const MAPS = ['Mirage', 'Inferno', 'Nuke', 'Ancient', 'Anubis', 'Dust2', 'Vertigo'];
                                                
                                                        const selTeam1 = document.getElementById('team1');
                                                        const selTeam2 = document.getElementById('team2');
                                                        const bo = document.getElementById('bo');
                                                        const heure = document.getElementById('heure');
                                                        const name1 = document.getElementById('name1');
                                                        const name2 = document.getElementById('name2');
                                                        const boBadge = document.querySelector('.bestof-badge');
                                                        const heureBadge = document.querySelector('.heure-badge');
                                                        const leftStats = document.querySelector('.left-stats');
                                                        const rightStats = document.querySelector('.right-stats');
                                                        const mapsContainer = document.getElementById('maps-container');
                                                
                                                        let selectedTeam1 = '';
                                                        let selectedTeam2 = '';
                                                
                                                        function applyTeamDisplay(id, nameEl, statsEl, opponentId = null) {
                                                            const s = stats[id];
                                                            if (!s) return;
                                                            nameEl.textContent = s.name;
                                                
                                                            let html = `<div class="stat-tag">WR : ${s.wr ?? 'N/A'}</div>`;
                                                
                                                            if (opponentId && h2h[id]?.[opponentId]) {
                                                                const {
                                                                    wins,
                                                                    losses
                                                                } = h2h[id][opponentId];
                                                                html += `<div class="stat-tag">H2H : ${wins}W / ${losses}L</div>`;
                                                            }
                                                
                                                            statsEl.innerHTML = html;
                                                        }
                                                
                                                        function clearTeamDisplay(nameEl, statsEl, placeholder) {
                                                            nameEl.textContent = placeholder;
                                                            statsEl.innerHTML = '<div class="stat-tag">WR</div>';
                                                        }
                                                
                                                        function refreshDisabled(ts, toDisable) {
                                                            Object.values(ts.options).forEach(opt => {
                                                                ts.updateOption(opt.value, {
                                                                    value: opt.value,
                                                                    text: stats[opt.value]?.name ?? opt.text,
                                                                    disabled: !!(toDisable && opt.value === toDisable),
                                                                });
                                                            });
                                                            ts.refreshOptions(false);
                                                        }
                                                
                                                        const tsTeam1 = new TomSelect('#team1', {
                                                            sortField: {
                                                                field: 'text',
                                                                direction: 'asc'
                                                            },
                                                            onChange(val) {
                                                                selectedTeam1 = val;
                                                                val ? applyTeamDisplay(val, name1, leftStats, selectedTeam2) :
                                                                    clearTeamDisplay(name1, leftStats, 'équipe 1');
                                                                refreshDisabled(tsTeam2, val);
                                                                updateMapLabels();
                                                            }
                                                        });
                                                
                                                        const tsTeam2 = new TomSelect('#team2', {
                                                            sortField: {
                                                                field: 'text',
                                                                direction: 'asc'
                                                            },
                                                            onChange(val) {
                                                                selectedTeam2 = val;
                                                                val ? applyTeamDisplay(val, name2, rightStats, selectedTeam1) :
                                                                    clearTeamDisplay(name2, rightStats, 'équipe 2');
                                                                refreshDisabled(tsTeam1, val);
                                                                updateMapLabels();
                                                            }
                                                        });
                                                
                                                        const tsBO = new TomSelect('#bo', {});
                                                        tsBO.on('change', val => {
                                                            boBadge.textContent = val ? 'BO' + val : 'Best of';
                                                            buildMapRows(parseInt(val) || 0);
                                                        });
                                                
                                                        heure.onchange = () => {
                                                            heureBadge.textContent = heure.value || 'heure';
                                                        };
                                                
                                                        function buildMapRows(count) {
                                                            mapsContainer.innerHTML = '';
                                                            if (!count) return;
                                                
                                                            for (let i = 1; i <= count; i++) {
                                                                const t1 = stats[selectedTeam1]?.name || 'Équipe 1';
                                                                const t2 = stats[selectedTeam2]?.name || 'Équipe 2';
                                                
                                                                const row = document.createElement('div');
                                                                row.className = 'map-row';
                                                                row.dataset.mapIndex = i;
                                                
                                                                const mapOptions = MAPS.map(m =>
                                                                    `<option value="${m}">${m}</option>`
                                                                ).join('');
                                                
                                                                row.innerHTML = `
                                                                    <div class="map-num">M${i}</div>
                                                                    <select class="map-select" data-index="${i}">
                                                                        <option value="">— Map —</option>
                                                                        ${mapOptions}
                                                                    </select>
                                                                    <div class="score-block">
                                                                        <input type="number" class="score-input score-t1" min="0" max="30"
                                                                                placeholder="${t1.split(' ')[0]}" title="Score ${t1}">
                                                                        <span class="score-sep">–</span>
                                                                        <input type="number" class="score-input score-t2" min="0" max="30"
                                                                                placeholder="${t2.split(' ')[0]}" title="Score ${t2}">
                                                                    </div>
                                                                `;
                                                                mapsContainer.appendChild(row);
                                                            }
                                                
                                                            mapsContainer.querySelectorAll('.map-select').forEach(sel =>
                                                                sel.addEventListener('change', syncMapOptions)
                                                            );
                                                            mapsContainer.querySelectorAll('.score-input').forEach(inp =>
                                                                inp.addEventListener('input', checkWinner)
                                                            );
                                                        }
                                                
                                                        function syncMapOptions() {
                                                            const rows = mapsContainer.querySelectorAll('.map-row:not(.map-locked)');
                                                            const chosen = {};
                                                            rows.forEach(row => {
                                                                const val = row.querySelector('.map-select').value;
                                                                if (val) chosen[val] = true;
                                                            });
                                                            rows.forEach(row => {
                                                                const sel = row.querySelector('.map-select');
                                                                const current = sel.value;
                                                                Array.from(sel.options).forEach(opt => {
                                                                    if (!opt.value) return;
                                                                    opt.disabled = !!(chosen[opt.value] && opt.value !== current);
                                                                });
                                                            });
                                                        }
                                                
                                                        function checkWinner() {
                                                            const boVal = parseInt(tsBO.getValue()) || 0;
                                                            if (!boVal) return;
                                                
                                                            const needed = Math.ceil(boVal / 2);
                                                            const rows = mapsContainer.querySelectorAll('.map-row');
                                                            let wins1 = 0,
                                                                wins2 = 0;
                                                
                                                            rows.forEach(row => {
                                                                row.classList.remove('map-locked');
                                                
                                                                if (wins1 >= needed || wins2 >= needed) {
                                                                    row.classList.add('map-locked');
                                                                    return;
                                                                }
                                                
                                                                const t1Val = row.querySelector('.score-t1').value;
                                                                const t2Val = row.querySelector('.score-t2').value;
                                                                const mapVal = row.querySelector('.map-select').value;
                                                
                                                                if (mapVal && t1Val !== '' && t2Val !== '') {
                                                                    const t1 = parseInt(t1Val);
                                                                    const t2 = parseInt(t2Val);
                                                                    if (t1 > t2) wins1++;
                                                                    else if (t2 > t1) wins2++;
                                                                }
                                                            });
                                                
                                                            syncMapOptions();
                                                        }
                                                
                                                
                                                        function updateMapLabels() {
                                                            const t1 = stats[selectedTeam1]?.name.split(' ')[0] || 'Éq.1';
                                                            const t2 = stats[selectedTeam2]?.name.split(' ')[0] || 'Éq.2';
                                                            document.querySelectorAll('.score-t1').forEach(el => el.placeholder = t1);
                                                            document.querySelectorAll('.score-t2').forEach(el => el.placeholder = t2);
                                                            syncMapOptions();
                                                        }
                                                
                                                        function getMapsData() {
                                                            const rows = mapsContainer.querySelectorAll('.map-row:not(.map-locked)');
                                                            return Array.from(rows).map(row => ({
                                                                map: row.querySelector('.map-select').value,
                                                                score_t1: row.querySelector('.score-t1').value,
                                                                score_t2: row.querySelector('.score-t2').value,
                                                            }));
                                                        }
                                                
                                                        document.getElementById('btn-lancer').addEventListener('click', () => {
                                                            const t1 = selectedTeam1;
                                                            const t2 = selectedTeam2;
                                                            const boV = tsBO.getValue();
                                                            const h = heure.value;
                                                
                                                            if (!t1 || !t2) {
                                                                alert('Sélectionne les deux équipes !');
                                                                return;
                                                            }
                                                            if (!boV) {
                                                                alert('Choisis un format Best of !');
                                                                return;
                                                            }
                                                
                                                            const mapsData = getMapsData();
                                                            const allMapsChosen = mapsData.every(m => m.map !== '');
                                                            if (!allMapsChosen) {
                                                                alert('Sélectionne toutes les maps !');
                                                                return;
                                                            }
                                                
                                                            const params = new URLSearchParams({
                                                                team1_id: t1,
                                                                team1_name: stats[t1]?.name ?? '',
                                                                team2_id: t2,
                                                                team2_name: stats[t2]?.name ?? '',
                                                                bo: 'BO' + boV,
                                                                heure: h,
                                                                team1_wr: stats[t1]?.wr ?? '',
                                                                team2_wr: stats[t2]?.wr ?? '',
                                                                maps: JSON.stringify(mapsData),
                                                            });
                                                            window.location.href = 'userPrediction.php?' + params.toString();
                                                        });
                                                
                                                        document.querySelectorAll('nav a').forEach(link => {
                                                            link.addEventListener('click', () => {
                                                                document.querySelectorAll('nav a').forEach(a => a.classList.remove('active'));
                                                                link.classList.add('active');
                                                            });
                                                        });
                                                        document.addEventListener('DOMContentLoaded', function() {
                                                            const calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
                                                                initialView: 'dayGridMonth',
                                                                locale: 'fr',
                                                                headerToolbar: {
                                                                    left: 'prev,next today',
                                                                    center: 'title',
                                                                    right: 'dayGridMonth,timeGridWeek'
                                                                },
                                                                events: <?= $calEventsJson ?>,
                                                                eventClick: function(info) {
                                                                    const t = info.event.extendedProps.tournament;
                                                                    alert(info.event.title + (t ? '\n🏆 ' + t : ''));
                                                                }
                                                            });
                                                            calendar.render();
                                                        });
                                                    </script>
                                                </body>
                                                
                                                </html>

