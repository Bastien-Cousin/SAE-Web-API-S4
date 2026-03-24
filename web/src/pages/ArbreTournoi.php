<?php
require '../components/arbre.php'; // le composant bracket
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Arbre Tournoi</title>
    <link rel="stylesheet" href="../style.css">
</head>

<body>

    <?php include '../components/navbar.php'; ?>

    <?php
    $rounds = [ /* ... */];
    $thirdPlace = [ /* ... */];

    bracketStyles();
    renderBracket("CS2 Championship", $rounds, $thirdPlace);
    ?>

</body>

</html>