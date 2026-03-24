<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=*, initial-scale=1.0">
    <title>Inscription</title>
</head>
<body>
    <a href="../index.php" id="bouton-retour">Retourner à l'accueil</a>
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
        <p>Déjà un compte ? <a href="/pages/Connexion.php">Connectez-vous</a></p>
        <button type="submit">S'inscrire</button>
    </form>
</body>
</html>

<style>
    #bouton-retour {
        position: absolute;
        top: 0px;
        left: 0px;
        text-decoration: none;
        color: black;
        border-bottom: 1px solid #ccc;
        border-right: 1px solid #ccc;
        padding: 20px 25px;
        border-bottom-right-radius: 10px;
    }

    #bouton-retour:hover {
        border-bottom: 1px solid black;
        border-right: 1px solid black;
        background-color: #ccc;
        transition: all 0.3s ease;
    }

    body {
        margin: 0;
        height: 100vh;
        display: flex;
        justify-content: center;
        align-items: center;
        background-color: #fafafa;
        font-family: Arial, sans-serif;
    }

    form {
        display: flex;
        flex-direction: column;
        width: 100%;
        max-width: 400px;
        padding: 25px;
        border: 1px solid #ccc;
        border-radius: 10px;
        background-color: white;
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }

    h1 {
        text-align: center;
        margin-bottom: 20px;
    }

    p {
        text-align: center;
        margin-top: 10px;
    }

    input {
        margin-bottom: 15px;
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 5px;
        font-size: 14px;
    }

    button {
        padding: 10px;
        border: 1px solid black;
        border-radius: 5px;
        background-color: #4CAF50;
        color: white;
        font-size: 16px;
        cursor: pointer;
    }

    button:hover {
        background-color: #45a049;
        transition: all 0.3s ease;
    }
</style>