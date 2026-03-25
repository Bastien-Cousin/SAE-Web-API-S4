<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Arbre Tournoi</title>
    <link rel="stylesheet" href="../style.css">
</head>
<?php include '../components/navbar.php'; ?>

<body>

    <?php
    require '../components/arbre.php'; // le composant bracket
    ?>

    <?php
    $rounds = [ /* ... */];
    $thirdPlace = [ /* ... */];

    bracketStyles();
    ?>


</body>

</html>