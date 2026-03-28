<?php
require_once __DIR__ . "/../../utils/auth.php";
$user = requireAuth();

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nomEquipe = trim($_POST['nom_equipe'] ?? '');
    $logoUrl = trim($_POST['logo_url'] ?? '');
    $dateCreation = trim($_POST['date_creation'] ?? '');
    $ceo = trim($_POST['ceo'] ?? '');
    $region = trim($_POST['region'] ?? '');
    $argent = trim($_POST['argent'] ?? '');

    if ($nomEquipe === '' || $dateCreation === '' || $ceo === '' || $argent === '') {
        $error = "Tous les champs obligatoires doivent être remplis.";
    } else {
        $payload = json_encode([
            'name' => $nomEquipe,
            'logoUrl' => $logoUrl,
            'createdate' => $dateCreation,
            'ceo' => $ceo,
            'region' => $region,
            'earnings' => $argent,
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
        $result = @file_get_contents('http://api:3070/api/teams', false, $context);

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
                $error = $response['error'] ?? "Erreur lors de la création de l'équipe.";
            } else {
                header('Location: AccueilEquipe.php?created=1');
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
        <title>Ajouter une Équipe</title>
        <link rel="stylesheet" href="FormulaireEquipe.css">
    </head>

    <body>
        <a href="AccueilEquipe.php" id="bouton-retour">Retour aux équipes</a>

        <?php if ($error): ?>
            <p
                style="color:#c62828;text-align:center;font-weight:600;"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </p>
        <?php endif; ?>

        <form method="POST">
            <h1>Ajouter une Équipe</h1>

            <label for="nom_equipe">Nom de l'équipe</label>
            <input type="text" id="nom_equipe" name="nom_equipe" required value="<?= htmlspecialchars($_POST['nom_equipe'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

            <label for="logo_url">Logo (URL, optionnel)</label>
            <input type="url" id="logo_url" name="logo_url" value="<?= htmlspecialchars($_POST['logo_url'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="https://...">

            <label for="date_creation">Date de création</label>
            <input type="date" id="date_creation" name="date_creation" required value="<?= htmlspecialchars($_POST['date_creation'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

            <label for="ceo">Nom du CEO</label>
            <input type="text" id="ceo" name="ceo" required value="<?= htmlspecialchars($_POST['ceo'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

            <label for="region">Région (optionnel)</label>
            <input type="text" id="region" name="region" value="<?= htmlspecialchars($_POST['region'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

            <label for="argent">Argent généré ($)</label>
            <input type="number" id="argent" name="argent" min="0" step="1" required value="<?= htmlspecialchars($_POST['argent'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

            <button type="submit">Ajouter l'équipe</button>
        </form>
    </body>

</html>
