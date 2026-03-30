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

header("Location: /index.php");
exit();