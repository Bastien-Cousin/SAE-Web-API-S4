<?php
setcookie(
    "auth_token",
    "",
    [
        "expires" => time() - 3600,
        "path" => "/",
        "secure" => false, // mettre true en HTTPS
        "httponly" => true,
        "samesite" => "Strict"
    ]
);

session_start();
$_SESSION = [];
session_destroy();

header("Location: /index.php");
exit();