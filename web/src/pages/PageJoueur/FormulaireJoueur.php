<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pseudo = $_POST['pseudo'] ?? '';
    $nomReel = $_POST['nom_reel'] ?? '';
    $nationalite = $_POST['nationalite'] ?? '';
    $age = $_POST['age'] ?? '';
    $equipe = $_POST['equipe'] ?? '';
    $actif = $_POST['actif'] ?? '';
    $roles = $_POST['roles'] ?? [];
    $gains = $_POST['gains'] ?? '';

    if (count($roles) > 2) {
        die("Vous ne pouvez sélectionner que 2 rôles maximum.");
    }

    header('Location: Joueurs.php');
    exit;
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

    <form method="POST">
        <h1>Ajouter un Joueur</h1>

        <label for="pseudo">Pseudo de jeu</label>
        <input type="text" id="pseudo" name="pseudo" required>

        <label for="nom_reel">Nom et prénom</label>
        <input type="text" id="nom_reel" name="nom_reel" required>

        <label for="nationalite">Nationalité</label>
        <input type="text" id="nationalite" name="nationalite" required>

        <label for="age">Âge</label>
        <input type="number" id="age" name="age" min="10" max="100" required>

        <label for="equipe">Équipe</label>
        <input type="text" id="equipe" name="equipe">

        <label for="actif">Statut</label>
        <select id="actif" name="actif" required>
            <option value="actif">Actif</option>
            <option value="inactif">Inactif</option>
        </select>

        <label for="actif">Statut</label>
        <select id="actif" name="actif" required>
            <option value="Rifle">Rifle</option>
            <option value="AWPer">AWPer</option>
            <option value="IGL">IGL</option>
            <option value="Entry">Entry</option>
            <option value="Lurker">Lurker</option>
            <option value="Support">Support</option>
        </select>


        <label for="actif">Statut 2 (optionnel)</label>
        <select id="actif" name="actif">
            <option value="Rifle">Rifle</option>
            <option value="AWPer">AWPer</option>
            <option value="IGL">IGL</option>
            <option value="Entry">Entry</option>
            <option value="Lurker">Lurker</option>
            <option value="Support">Support</option>
        </select>


        <label for="gains">Gains ($)</label>
        <input type="number" id="gains" name="gains" min="0" step="1000" required>

        <button type="submit">Ajouter le joueur</button>
    </form>
</body>

</html>