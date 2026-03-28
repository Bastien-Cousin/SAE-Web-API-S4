<?php
require_once __DIR__ . "/../../utils/auth.php";
$user = requireAuth();

$error = null;
$teams = [];

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
    $pseudo = trim($_POST['pseudo'] ?? '');
    $nomReel = trim($_POST['nom_reel'] ?? '');
    $nationalite = trim($_POST['nationalite'] ?? '');
    $age = trim($_POST['age'] ?? '');
    $teamId = trim($_POST['team_id'] ?? '');
    $actif = trim($_POST['actif'] ?? 'actif');
    $role1 = trim($_POST['role_1'] ?? '');
    $role2 = trim($_POST['role_2'] ?? '');
    $gains = trim($_POST['gains'] ?? '');

    $roles = array_values(array_filter(array_unique([$role1, $role2])));

    if ($pseudo === '' || $nomReel === '' || $nationalite === '' || $age === '' || $gains === '') {
        $error = "Tous les champs obligatoires doivent être remplis.";
    } else {
        $payload = json_encode([
            'pseudo' => $pseudo,
            'name' => $nomReel,
            'nationality' => $nationalite,
            'age' => $age,
            'teamId' => $teamId !== '' ? $teamId : null,
            'status' => $actif,
            'roles' => $roles,
            'earnings' => $gains,
        ]);

        $options = [
            'http' => [
                'method' => 'POST',
                'header' => implode("\r\n", [
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($payload),
                    'Accept: application/json',
                ]),
                'content' => $payload,
                'ignore_errors' => true,
            ],
        ];

        $context = stream_context_create($options);
        $result = @file_get_contents('http://api:3070/api/players', false, $context);

        if ($result === false) {
            $error = "Impossible de contacter le serveur API.";
        } else {
            $response = json_decode($result, true);
            $statusCode = 200;
            if (!empty($http_response_header)) {
                preg_match('/HTTP\/\d\.\d\s+(\d+)/', $http_response_header[0], $matches);
                $statusCode = isset($matches[1]) ? (int) $matches[1] : 200;
            }

            if ($statusCode >= 400) {
                $error = $response['error'] ?? "Erreur lors de la création du joueur.";
            } else {
                header('Location: Joueur.php?created=1');
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
        <title>Ajouter un Joueur</title>
        <link rel="stylesheet" href="FormulaireJoueur.css">
    </head>

    <body>
        <a href="Joueur.php" id="bouton-retour">Retour aux joueurs</a>

        <?php if ($error): ?>
            <p
                style="color:#c62828;text-align:center;font-weight:600;"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </p>
        <?php endif; ?>

        <form method="POST">
            <h1>Ajouter un Joueur</h1>

            <label for="pseudo">Pseudo de jeu</label>
            <input type="text" id="pseudo" name="pseudo" required value="<?= htmlspecialchars($_POST['pseudo'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

            <label for="nom_reel">Nom et prénom</label>
            <input type="text" id="nom_reel" name="nom_reel" required value="<?= htmlspecialchars($_POST['nom_reel'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

            <label for="nationalite">Nationalité</label>
            <input type="text" id="nationalite" name="nationalite" required value="<?= htmlspecialchars($_POST['nationalite'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

            <label for="age">Âge</label>
            <input type="number" id="age" name="age" min="10" max="100" required value="<?= htmlspecialchars($_POST['age'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

            <label for="team_id">Équipe (optionnel)</label>
            <input type="text" id="team_search" placeholder="Rechercher une équipe...">
            <select id="team_id" name="team_id">
                <option value="">Aucune équipe</option>
                <?php foreach ($teams as $team): ?>
                    <option
                        value="<?= htmlspecialchars((string) ($team['pageid'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" <?= (($_POST['team_id'] ?? '') === (string) ($team['pageid'] ?? '')) ? 'selected' : '' ?>><?= htmlspecialchars($team['name'] ?? 'Équipe inconnue', ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="actif">Statut</label>
            <select id="actif" name="actif" required>
                <option value="actif" <?= (($_POST['actif'] ?? 'actif') === 'actif') ? 'selected' : '' ?>>Actif</option>
                <option value="inactif" <?= (($_POST['actif'] ?? '') === 'inactif') ? 'selected' : '' ?>>Inactif</option>
            </select>

            <label for="role_1">Rôle principal</label>
            <select id="role_1" name="role_1" required>
                <option value="">Sélectionnez un rôle</option>
                <option value="Rifle" <?= (($_POST['role_1'] ?? '') === 'Rifle') ? 'selected' : '' ?>>Rifle</option>
                <option value="AWPer" <?= (($_POST['role_1'] ?? '') === 'AWPer') ? 'selected' : '' ?>>AWPer</option>
                <option value="IGL" <?= (($_POST['role_1'] ?? '') === 'IGL') ? 'selected' : '' ?>>IGL</option>
                <option value="Entry" <?= (($_POST['role_1'] ?? '') === 'Entry') ? 'selected' : '' ?>>Entry</option>
                <option value="Lurker" <?= (($_POST['role_1'] ?? '') === 'Lurker') ? 'selected' : '' ?>>Lurker</option>
                <option value="Support" <?= (($_POST['role_1'] ?? '') === 'Support') ? 'selected' : '' ?>>Support</option>
            </select>

            <label for="role_2">Rôle secondaire (optionnel)</label>
            <select id="role_2" name="role_2">
                <option value="">Aucun</option>
                <option value="Rifle" <?= (($_POST['role_2'] ?? '') === 'Rifle') ? 'selected' : '' ?>>Rifle</option>
                <option value="AWPer" <?= (($_POST['role_2'] ?? '') === 'AWPer') ? 'selected' : '' ?>>AWPer</option>
                <option value="IGL" <?= (($_POST['role_2'] ?? '') === 'IGL') ? 'selected' : '' ?>>IGL</option>
                <option value="Entry" <?= (($_POST['role_2'] ?? '') === 'Entry') ? 'selected' : '' ?>>Entry</option>
                <option value="Lurker" <?= (($_POST['role_2'] ?? '') === 'Lurker') ? 'selected' : '' ?>>Lurker</option>
                <option value="Support" <?= (($_POST['role_2'] ?? '') === 'Support') ? 'selected' : '' ?>>Support</option>
            </select>


            <label for="gains">Gains ($)</label>
            <input type="number" id="gains" name="gains" min="0" step="1" required value="<?= htmlspecialchars($_POST['gains'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

            <button type="submit">Ajouter le joueur</button>
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

            const teamSelect = document.getElementById('team_id');
            const teamSearch = document.getElementById('team_search');

            const teamOptionsCache = buildOptionsCache(teamSelect);

            teamSearch.addEventListener('input', (e) => {
                applyFilter(teamSelect, teamOptionsCache, e.target.value);
            });
        </script>
    </body>

</html>
