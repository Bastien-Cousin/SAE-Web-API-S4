<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nomEquipe = $_POST['nom_equipe'] ?? '';
    $logo = $_FILES['logo'] ?? null;
    $dateCreation = $_POST['date_creation'] ?? '';
    $ceo = $_POST['ceo'] ?? '';
    $argent = $_POST['argent'] ?? '';

    if (empty($nomEquipe)) {
        die("Le nom de l'équipe est obligatoire !");
    }
    if (empty($dateCreation)) {
        die("La date de création est obligatoire !");
    }
    if (empty($ceo)) {
        die("Le nom du CEO est obligatoire !");
    }
    if (empty($argent)) {
        die("Le montant d'argent généré est obligatoire !");
    }

    // Gestion du fichier logo
    $logoPath = '';
    if ($logo && $logo['tmp_name'] != '') {
        $targetDir = "uploads/";
        if (!is_dir($targetDir))
            mkdir($targetDir, 0755, true);
        $logoPath = $targetDir . basename($logo['name']);
        move_uploaded_file($logo['tmp_name'], $logoPath);
    }

    header('Location: Equipes.php');
    exit;
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

    <form method="POST" enctype="multipart/form-data">
        <h1>Ajouter une Équipe</h1>

        <label for="nom_equipe">Nom de l'équipe</label>
        <input type="text" id="nom_equipe" name="nom_equipe" required>

        <label for="logo">Logo de l'équipe (optionnel)</label>
        <input type="file" id="logo" name="logo" accept="image/*">

        <label for="date_creation">Date de création</label>
        <input type="date" id="date_creation" name="date_creation" required>

        <label for="ceo">Nom du CEO</label>
        <input type="text" id="ceo" name="ceo" required>

        <label for="argent">Argent généré ($)</label>
        <input type="number" id="argent" name="argent" min="0" step="1000" required>

        <button type="submit">Ajouter l'équipe</button>
    </form>
</body>

</html>