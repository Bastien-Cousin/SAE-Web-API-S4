<!DOCTYPE html>
<html lang="fr">

    <head>
        <meta charset="UTF-8">
        <title>Arbre Tournoi</title>
        <link rel="stylesheet" href="../style.css">
        <link rel="stylesheet" href="../components/componentArbre/bracket.css">
    </head>

    <body><?php include '../components/navbar.php'; ?>

        <?php
        require '../components/componentArbre/arbre.php';

        $rounds = [ /* ... */];
        $thirdPlace = [ /* ... */];

        renderBracket('Nom du tournoi', $rounds, $thirdPlace);
        ?>

    </body>

</html>
