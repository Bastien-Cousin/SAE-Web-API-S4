<!DOCTYPE html>
<html lang="fr">

    <head>
        <meta charset="UTF-8">
        <title>Arbre Tournoi</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@400;600;700;800;900&family=Barlow:wght@300;400;500&display=swap" rel="stylesheet">

        <link rel="stylesheet" href="../../style.css">
        <link rel="stylesheet" href="../../components/ComponentNavbar/navbar.css">
        <link rel="stylesheet" href="../../components/ComponentArbre/bracket.css">
    </head>

    <body><?php include __DIR__ . '/../../components/ComponentNavbar/navbar.php'; ?>

        <?php
        require __DIR__ . '/../../components/ComponentArbre/arbre.php';

        $rounds = [ /* ... */];
        $thirdPlace = [ /* ... */];

        renderBracket('Nom du tournoi', $rounds, $thirdPlace);
        ?>

    </body>

</html>
