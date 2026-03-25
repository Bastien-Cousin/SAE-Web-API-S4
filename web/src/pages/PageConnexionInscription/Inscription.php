<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=*, initial-scale=1.0">
    <title>Inscription</title>
    <link rel="stylesheet" href="Inscription.css">
</head>

<body>
    <a href="../../index.php" id="bouton-retour">Retourner à l'accueil</a>
    <form bindsubmit="handleSignup">
        <h1>Inscription</h1>
        <label for="email">Email</label>
        <input type="email" id="email" placeholder="votremail@gmail.com">
        <label for="username">Nom d'utilisateur</label>
        <input type="text" id="username" placeholder="Nom d'utilisateur">
        <label for="password">Mot de passe</label>
        <input type="password" id="password" placeholder="**********">
        <label for="confirm_password">Confirmer votre mot de passe</label>
        <input type="password" id="confirm_password" placeholder="**********">
        <p>Déjà un compte ? <a href="./Connexion.php">Connectez-vous</a></p>
        <button type="submit">S'inscrire</button>
    </form>
</body>

</html>