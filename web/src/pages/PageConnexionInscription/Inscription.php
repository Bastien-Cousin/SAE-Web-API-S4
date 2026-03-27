<?php
session_start();

$error = null;
$success = null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? '');
    $username = trim($_POST["username"] ?? '');
    $password = $_POST["password"] ?? '';
    $confirm_password = $_POST["confirm_password"] ?? '';

    // 1. Vérification basique
    if (!$email || !$username || !$password) {
        $error = "Veuillez remplir tous les champs.";
    } elseif ($password !== $confirm_password) {
        $error = "Les mots de passe ne correspondent pas.";
    } else {

        // 2. Préparer la requête vers l'API Node
        $data = json_encode([
            "email"    => $email,
            "username" => $username,
            "password" => $password,
        ]);

        $options = [
            "http" => [
                "header"  => implode("\r\n", [
                    "Content-Type: application/json",
                    "Content-Length: " . strlen($data),   // CORRECTION : header manquant
                    "Accept: application/json",
                ]),
                "method"          => "POST",
                "content"         => $data,
                "ignore_errors"   => true,  // CORRECTION : récupérer la réponse même en cas d'erreur HTTP (4xx/5xx)
            ]
        ];

        $context = stream_context_create($options);

        $url = "http://api:3070/api/auth/sign-up";

        $result = @file_get_contents($url, false, $context);

        if ($result === false) {
            $error = "Impossible de contacter le serveur. Veuillez réessayer.";
        } else {
            $response = json_decode($result, true);

            // CORRECTION : inspecter aussi le code de statut HTTP retourné
            // $http_response_header est une variable magique PHP remplie par file_get_contents
            $statusCode = 200;
            if (!empty($http_response_header)) {
                // La première ligne est du type "HTTP/1.1 201 Created"
                preg_match('/HTTP\/\d\.\d\s+(\d+)/', $http_response_header[0], $matches);
                $statusCode = isset($matches[1]) ? (int)$matches[1] : 200;
            }

            if ($statusCode >= 400) {
                // Afficher le message d'erreur renvoyé par l'API
                $error = $response["error"] ?? "Une erreur est survenue (code $statusCode).";
            } else {
                $success = "Inscription réussie !";

                if (isset($response["token"])) {
                    $_SESSION["token"] = $response["token"];
                }
                if (isset($response["user"])) {
                    $_SESSION["user"] = $response["user"];
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
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Inscription</title>
        <link rel="stylesheet" href="Inscription.css">
    </head>

    <body>
        <a href="../../index.php" id="bouton-retour">Retourner à l'accueil</a>

        <?php if ($error): ?>
            <!-- CORRECTION : htmlspecialchars pour éviter les failles XSS -->
            <p style="color:red;"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>

        <?php if ($success): ?>
            <p style="color:green;"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></p>
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
            <p>Déjà un compte ?
                <a href="./Connexion.php">Connectez-vous</a>
            </p>
            <button type="submit">S'inscrire</button>
        </form>
    </body>

</html>