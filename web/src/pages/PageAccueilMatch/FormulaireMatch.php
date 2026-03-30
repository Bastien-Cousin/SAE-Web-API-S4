<?php
require_once __DIR__ . "/../../utils/auth.php";
$user = requireAuth();

$error = null;
$teams = [];

// Charger toutes les équipes, pas seulement la première page.
$skip = 0;
$take = 500;

while (true) {
    $teamsResult = @file_get_contents("http://api:3070/api/teams?skip=$skip&take=$take");
    if ($teamsResult === false) {
        break;
    }

    $teamsResponse = json_decode($teamsResult, true);
    $chunk = (isset($teamsResponse['data']) && is_array($teamsResponse['data']))
        ? $teamsResponse['data']
        : [];

    if (empty($chunk)) {
        break;
    }

    $teams = array_merge($teams, $chunk);

    if (count($chunk) < $take) {
        break;
    }

    $skip += $take;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $team1Id = trim($_POST['team1_id'] ?? '');
    $team2Id = trim($_POST['team2_id'] ?? '');
    $date = trim($_POST['date_match'] ?? '');
    $tournament = trim($_POST['tournament'] ?? '');
    $bestof = trim($_POST['bestof'] ?? '');
    $scoreTeam1 = trim($_POST['score_team1'] ?? '');
    $scoreTeam2 = trim($_POST['score_team2'] ?? '');

    $mapsPayload = [];
    $mapsRaw = json_decode($_POST['maps_payload'] ?? '[]', true);
    if (is_array($mapsRaw)) {
        foreach ($mapsRaw as $mapRow) {
            if (!is_array($mapRow)) {
                continue;
            }
            $mapName = trim((string) ($mapRow['map'] ?? ''));
            $mapScore1 = trim((string) ($mapRow['team1_score'] ?? ''));
            $mapScore2 = trim((string) ($mapRow['team2_score'] ?? ''));

            if ($mapName === '' || $mapScore1 === '' || $mapScore2 === '') {
                continue;
            }

            if (!ctype_digit($mapScore1) || !ctype_digit($mapScore2)) {
                continue;
            }

            $mapsPayload[] = [
                'map' => $mapName,
                'team1_score' => (int) $mapScore1,
                'team2_score' => (int) $mapScore2,
            ];
        }
    }

    if ($team1Id === '' || $team2Id === '' || $date === '') {
        $error = 'Les équipes et la date du match sont obligatoires.';
    } elseif ($team1Id === $team2Id) {
        $error = 'Veuillez sélectionner deux équipes différentes.';
    } else {
        $payload = json_encode([
            'team1Id' => $team1Id,
            'team2Id' => $team2Id,
            'date' => $date,
            'tournament' => $tournament !== '' ? $tournament : null,
            'bestof' => $bestof !== '' ? (int) $bestof : null,
            'scoreTeam1' => $scoreTeam1 !== '' ? (int) $scoreTeam1 : null,
            'scoreTeam2' => $scoreTeam2 !== '' ? (int) $scoreTeam2 : null,
            'maps' => $mapsPayload,
        ]);

        $token = $_COOKIE['auth_token'] ?? null;
        
        $headers = [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($payload),
            'Accept: application/json',
        ];
        
        if ($token) {
            $headers[] = "Authorization: Bearer $token";
        }
        
        $options = [
            'http' => [
                'method' => 'POST',
                'header' => implode("\r\n", $headers),
                'content' => $payload,
                'ignore_errors' => true,
            ],
        ];

        $context = stream_context_create($options);
        $result = @file_get_contents('http://api:3070/api/matches', false, $context);

        if ($result === false) {
            $error = 'Impossible de contacter le serveur API.';
        } else {
            $response = json_decode($result, true);
            $statusCode = 200;
            if (!empty($http_response_header)) {
                preg_match('/HTTP\/\d\.\d\s+(\d+)/', $http_response_header[0], $matches);
                $statusCode = isset($matches[1]) ? (int) $matches[1] : 200;
            }

            if ($statusCode >= 400) {
                $error = $response['error'] ?? 'Erreur lors de la création du match.';
            } else {
                header('Location: AccueilMatch.php?created=1');
                exit;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Ajouter un Match</title>
        <link rel="stylesheet" href="FormulaireMatch.css">
    </head>

    <body>
        <a href="AccueilMatch.php" id="bouton-retour">Retour aux matchs</a>

        <?php if ($error): ?>
            <p
                style="color:#c62828;text-align:center;font-weight:600;"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </p>
        <?php endif; ?>

        <form method="POST">
            <h1>Ajouter un Match</h1>

            <input type="hidden" id="maps_payload" name="maps_payload" value="[]">

            <label for="team1_search">Rechercher équipe 1</label>
            <input type="text" id="team1_search" placeholder="Tapez le nom d'une équipe...">

            <label for="team1_id">Équipe 1</label>
            <select id="team1_id" name="team1_id" required>
                <option value="">Sélectionnez une équipe</option>
                <?php foreach ($teams as $team): ?>
                    <option
                        value="<?= htmlspecialchars((string) ($team['pageid'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" <?= (($_POST['team1_id'] ?? '') === (string) ($team['pageid'] ?? '')) ? 'selected' : '' ?>><?= htmlspecialchars($team['name'] ?? 'Équipe inconnue', ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="team2_search">Rechercher équipe 2</label>
            <input type="text" id="team2_search" placeholder="Tapez le nom d'une équipe...">

            <label for="team2_id">Équipe 2</label>
            <select id="team2_id" name="team2_id" required>
                <option value="">Sélectionnez une équipe</option>
                <?php foreach ($teams as $team): ?>
                    <option
                        value="<?= htmlspecialchars((string) ($team['pageid'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" <?= (($_POST['team2_id'] ?? '') === (string) ($team['pageid'] ?? '')) ? 'selected' : '' ?>><?= htmlspecialchars($team['name'] ?? 'Équipe inconnue', ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="date_match">Date et heure</label>
            <input type="datetime-local" id="date_match" name="date_match" required value="<?= htmlspecialchars($_POST['date_match'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

            <label for="tournament">Tournoi (optionnel)</label>
            <input type="text" id="tournament" name="tournament" value="<?= htmlspecialchars($_POST['tournament'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

            <label for="bestof">Format</label>
            <select id="bestof" name="bestof">
                <option value="">Non précisé</option>
                <option value="1" <?= (($_POST['bestof'] ?? '') === '1') ? 'selected' : '' ?>>BO1</option>
                <option value="3" <?= (($_POST['bestof'] ?? '') === '3') ? 'selected' : '' ?>>BO3</option>
                <option value="5" <?= (($_POST['bestof'] ?? '') === '5') ? 'selected' : '' ?>>BO5</option>
            </select>

            <div class="score-row">
                <div>
                    <label for="score_team1">Score équipe 1</label>
                    <input type="number" id="score_team1" name="score_team1" min="0" value="<?= htmlspecialchars($_POST['score_team1'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div>
                    <label for="score_team2">Score équipe 2</label>
                    <input type="number" id="score_team2" name="score_team2" min="0" value="<?= htmlspecialchars($_POST['score_team2'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
            </div>

            <div class="maps-header">
                <h2>Maps et scores</h2>
                <button type="button" class="secondary-btn" id="add-map-btn">+ Ajouter une map</button>
            </div>

            <div id="maps-list"></div>

            <button type="submit">Ajouter le match</button>
        </form>

        <script>
            function buildOptionsCache(selectEl) {
                return Array.from(selectEl.options).map((opt) => ({
                    value: opt.value,
                    text: opt.text,
                    selected: opt.selected,
                }));
            }

            function applyFilter(selectEl, cache, term) {
                const query = term.trim().toLowerCase();
                const currentValue = selectEl.value;

                selectEl.innerHTML = '';

                cache.forEach((opt) => {
                    if (!query || opt.text.toLowerCase().includes(query) || opt.value === '') {
                        const option = document.createElement('option');
                        option.value = opt.value;
                        option.textContent = opt.text;
                        selectEl.appendChild(option);
                    }
                });

                // Restaurer la valeur sélectionnée si elle est toujours visible.
                selectEl.value = currentValue;
            }

            const team1Select = document.getElementById('team1_id');
            const team2Select = document.getElementById('team2_id');
            const team1Search = document.getElementById('team1_search');
            const team2Search = document.getElementById('team2_search');

            const team1OptionsCache = buildOptionsCache(team1Select);
            const team2OptionsCache = buildOptionsCache(team2Select);

            team1Search.addEventListener('input', (e) => {
                applyFilter(team1Select, team1OptionsCache, e.target.value);
            });

            team2Search.addEventListener('input', (e) => {
                applyFilter(team2Select, team2OptionsCache, e.target.value);
            });

            const mapsList = document.getElementById('maps-list');
            const addMapBtn = document.getElementById('add-map-btn');
            const mapsPayloadInput = document.getElementById('maps_payload');

            const MAP_POOL = ['Ancient', 'Anubis', 'Dust2', 'Inferno', 'Mirage', 'Nuke', 'Train', 'Vertigo'];

            function createMapRow(initial = {}) {
                const row = document.createElement('div');
                row.className = 'map-row';

                const options = ['<option value="">Map</option>']
                    .concat(MAP_POOL.map((map) => `<option value="${map}">${map}</option>`))
                    .join('');

                row.innerHTML = `
                    <select class="map-name">${options}</select>
                    <input type="number" class="map-score1" min="0" placeholder="Score équipe 1">
                    <input type="number" class="map-score2" min="0" placeholder="Score équipe 2">
                    <button type="button" class="secondary-btn danger">Supprimer</button>
                `;

                const select = row.querySelector('.map-name');
                const score1 = row.querySelector('.map-score1');
                const score2 = row.querySelector('.map-score2');
                const removeBtn = row.querySelector('.danger');

                if (initial.map) select.value = initial.map;
                if (initial.team1_score !== undefined && initial.team1_score !== null) score1.value = initial.team1_score;
                if (initial.team2_score !== undefined && initial.team2_score !== null) score2.value = initial.team2_score;

                removeBtn.addEventListener('click', () => {
                    row.remove();
                    syncMapsPayload();
                });

                select.addEventListener('change', syncMapsPayload);
                score1.addEventListener('input', syncMapsPayload);
                score2.addEventListener('input', syncMapsPayload);

                mapsList.appendChild(row);
            }

            function syncMapsPayload() {
                const rows = Array.from(document.querySelectorAll('.map-row'));
                const payload = rows.map((row) => {
                    const map = row.querySelector('.map-name').value;
                    const team1Score = row.querySelector('.map-score1').value;
                    const team2Score = row.querySelector('.map-score2').value;

                    return {
                        map,
                        team1_score: team1Score,
                        team2_score: team2Score,
                    };
                });

                mapsPayloadInput.value = JSON.stringify(payload);
            }

            addMapBtn.addEventListener('click', () => {
                createMapRow();
                syncMapsPayload();
            });

            // Initialise avec 3 lignes de map pour la saisie rapide.
            createMapRow();
            createMapRow();
            createMapRow();
            syncMapsPayload();

            document.querySelector('form').addEventListener('submit', () => {
                syncMapsPayload();
            });
        </script>
    </body>

</html>

