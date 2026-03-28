<?php
require_once __DIR__ . "/../../utils/auth.php";
$user = requireAuth();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nomMap = trim($_POST['nom_map'] ?? '');
    $competition = trim($_POST['competition'] ?? '');
    $imageUrl = trim($_POST['image_url'] ?? '');

    // Validation basique
    if (empty($nomMap) || empty($competition)) {
        $error = "Le nom de la map et la période de compétition sont requis.";
    } else {
        try {
            // Connexion à la base de données
            $pdo = new PDO(
                "pgsql:host=" . (getenv('POSTGRES_HOST') ?: 'db') .
                ";port=" . (getenv('POSTGRES_PORT') ?: '5432') .
                ";dbname=" . (getenv('POSTGRES_DB') ?: 'sae_db'),
                getenv('POSTGRES_USER') ?: 'sae_user',
                getenv('POSTGRES_PASSWORD') ?: 'sae_password',
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );

            // Vérifier si la map existe déjà
            $stmt = $pdo->prepare("SELECT id FROM map WHERE name = ?");
            $stmt->execute([$nomMap]);
            if ($stmt->fetch()) {
                $error = "Une map avec ce nom existe déjà.";
            } else {
                // Insérer la nouvelle map
                $stmt = $pdo->prepare("INSERT INTO map (name, competition_date, image_url, created_at, updated_at) VALUES (?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
                $stmt->execute([$nomMap, $competition, $imageUrl ?: null]);
                $success = "La map '$nomMap' a été ajoutée avec succès !";
            }
        } catch (PDOException $e) {
            $error = "Erreur lors de l'ajout de la map : " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter une Map</title>
    <link rel="stylesheet" href="FormulaireMap.css">
</head>

<body>
    <a href="Map.php" id="bouton-retour">Retour aux maps</a>

    <?php if (isset($error)): ?>
        <div class="message error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (isset($success)): ?>
        <div class="message success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="POST">
        <h1>Ajouter une Map</h1>

        <label for="nom_map">Nom de la map</label>
        <input type="text" id="nom_map" name="nom_map" placeholder="Ex: Dust II" required>

        <label for="competition">Période de compétition</label>
        <input type="text" id="competition" name="competition" placeholder="Ex: 2001 - actuel" required>

        <label for="image_url">URL de l'image</label>
        <input type="url" id="image_url" name="image_url" placeholder="https://i.postimg.cc/...">

        <button type="submit">Ajouter la map</button>
    </form>
</body>

</html>