<?php
session_start();

$error = null;
$success = null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = $_POST["email"] ?? null;
    $username = $_POST["username"] ?? null;
    $password = $_POST["password"] ?? null;
    $confirm_password = $_POST["confirm_password"] ?? null;

    // 1. Vérification basique
    if (!$email || !$username || !$password) {
        $error = "Veuillez remplir tous les champs.";
    } elseif ($password !== $confirm_password) {
        $error = "Les mots de passe ne correspondent pas.";
    } else {

        // 2. Préparer la requête vers ton API Node
        $data = [
            "email" => $email,
            "username" => $username,
            "password" => $password
        ];

        $options = [
            "http" => [
                "header"  => "Content-Type: application/json",
                "method"  => "POST",
                "content" => json_encode($data),
            ]
        ];

        $context = stream_context_create($options);

        // ⚠️ adapte l'URL selon ton serveur
        $url = "http://api:3070/api/auth/sign-up";

        $result = @file_get_contents($url, false, $context);

        if ($result === FALSE) {
            $error = "Erreur lors de la connexion à l'API.";
        } else {
            $response = json_decode($result, true);

            if (isset($response["error"])) {
                $error = $response["error"];
            } else {
                $success = "Inscription réussie !";

                // Optionnel : stocker le token
                if (isset($response["token"])) {
                    $_SESSION["token"] = $response["token"];
                }

                header("Location: ../../index.php");
                exit();
            }
        }
    }
}
?>

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
    <?php if ($error): ?>
        <p style="color:red;"><?php echo $error; ?></p>
    <?php endif; ?>

    <?php if ($success): ?>
        <p style="color:green;"><?php echo $success; ?></p>
    <?php endif; ?>
    <form method="POST" action="">
        <h1>Inscription</h1>
        <label for="email">Email</label>
        <input type="email" name="email" id="email" placeholder="votremail@gmail.com" required>
        <label for="username">Nom d'utilisateur</label>
        <input type="text" name="username" id="username" placeholder="Nom d'utilisateur" required>
        <label for="password">Mot de passe</label>
        <input type="password" name="password" id="password" placeholder="**********" required>
        <label for="confirm_password">Confirmer votre mot de passe</label>
        <input type="password" name="confirm_password" id="confirm_password" placeholder="**********" required>
        <p>Déjà un compte ? <a href="./Connexion.php">Connectez-vous</a></p>
        <button type="submit">S'inscrire</button>
    </form>
</body>

</html>