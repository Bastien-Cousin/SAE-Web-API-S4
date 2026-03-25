<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion</title>
    <link rel="stylesheet" href="Connexion.css">
</head>

<body>
    <a href="../../index.php" id="bouton-retour">Retourner à l'accueil</a>
    <form bindsubmit="handleLogin">
        <h1>Connexion</h1>
        <label for="email">Email</label>
        <input type="email" id="email" placeholder="votremail@gmail.com">
        <label for="password">Mot de passe</label>
        <input type="password" id="password" placeholder="**********">
        <p>Pas encore de compte ? <a href="./Inscription.php">Inscrivez-vous</a></p>
        <button type="submit">Se connecter</button>
    </form>
</body>

</html>