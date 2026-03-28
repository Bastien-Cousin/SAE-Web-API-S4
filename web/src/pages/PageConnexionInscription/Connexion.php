<?php

$error = null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email    = trim($_POST["email"] ?? '');
    $password = $_POST["password"] ?? '';

    if (!$email || !$password) {
        $error = "Veuillez remplir tous les champs.";
    } else {

        $data = json_encode([
            "email"    => $email,
            "password" => $password,
        ]);

        $options = [
            "http" => [
                "header"  => implode("\r\n", [
                    "Content-Type: application/json",
                    "Content-Length: " . strlen($data),
                    "Accept: application/json",
                ]),
                "method"        => "POST",
                "content"       => $data,
                "ignore_errors" => true,
            ]
        ];

        $context = stream_context_create($options);
        $url     = "http://api:3070/api/auth/sign-in";
        $result  = @file_get_contents($url, false, $context);

        if ($result === false) {
            $error = "Impossible de contacter le serveur. Veuillez réessayer.";
        } else {
            $response = json_decode($result, true);

            $statusCode = 200;
            if (!empty($http_response_header)) {
                preg_match('/HTTP\/\d\.\d\s+(\d+)/', $http_response_header[0], $matches);
                $statusCode = isset($matches[1]) ? (int)$matches[1] : 200;
            }

            if ($statusCode >= 400) {
                $error = $response["error"] ?? "Une erreur est survenue (code $statusCode).";
            } else {
                if (isset($response["token"])) {

                    setcookie(
                        "auth_token",
                        $response["token"],
                        [
                            "expires" => time() + (7 * 24 * 60 * 60),
                            "path" => "/",
                            "secure" => false, // mettre true en HTTPS
                            "httponly" => true,
                            "samesite" => "Strict"
                        ]
                    );
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
    <title>Connexion</title>
    <link rel="stylesheet" href="Connexion.css">
</head>

<body>
    <a href="../../index.php" id="bouton-retour">Retourner à l'accueil</a>

    <?php if ($error): ?>
        <p style="color:red;"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
    <?php endif; ?>

    <form method="POST" action="">
        <h1>Connexion</h1>
        <label for="email">Email</label>
        <input type="email" name="email" id="email" placeholder="votremail@gmail.com" required>
        <label for="password">Mot de passe</label>
        <input type="password" name="password" id="password" placeholder="**********" required>
        <p>Pas encore de compte ? <a href="./Inscription.php">Inscrivez-vous</a></p>
        <button type="submit">Se connecter</button>
    </form>
</body>

</html>