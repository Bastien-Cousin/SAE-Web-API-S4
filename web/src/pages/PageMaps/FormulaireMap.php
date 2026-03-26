<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nomMap = $_POST['nom_map'] ?? '';
    $competition = $_POST['competition'] ?? '';
    $imageUrl = $_POST['image_url'] ?? '';
    header('Location: Map.php');
    exit;
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