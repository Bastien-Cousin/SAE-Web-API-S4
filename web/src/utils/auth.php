<?php

function requireAuth() {

    if (!isset($_COOKIE['auth_token'])) {
        header("Location: /pages/PageConnexionInscription/Connexion.php");
        exit();
    }

    $token = $_COOKIE['auth_token'];

    $options = [
        "http" => [
            "header" => "Authorization: Bearer $token"
        ]
    ];

    $context = stream_context_create($options);

    $result = @file_get_contents("http://api:3070/api/auth/me", false, $context);

    if ($result === false) {
        setcookie("auth_token", "", time() - 3600, "/");
        header("Location: /pages/PageConnexionInscription/Connexion.php");
        exit();
    }

    $data = json_decode($result, true);

    if (!isset($data["user"])) {
        header("Location: /pages/PageConnexionInscription/Connexion.php");
        exit();
    }

    return $data["user"];
}