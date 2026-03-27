<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>CS2 - Tournois</title>

    <link rel="stylesheet" href="../../style.css">
    <link rel="stylesheet" href="../../components/ComponentNavbar/navbar.css">
    <link rel="stylesheet" href="../../components/ComponentCardTournament/cardTournament.css">
    <link rel="stylesheet" href="AccueilTournoi.css">
</head>

<body>

    <?php include __DIR__ . '/../../components/ComponentNavbar/navbar.php'; ?>

    <?php include __DIR__ . '/../../components/ComponentCardTournament/cardTournament.php'; ?>

    <section class="tournaments">
        <h2>Tournois</h2>

        <div class="cards"><?php

        renderTournament(
            "/img/ImagesDivers/capsule_616x353.jpg",
            "CS2 Major",
            "$1,000,000",
            "/pages/PageAccueilTournoi/ArbreTournoi.php"
        );

        renderTournament(
            "/img/ImagesDivers/capsule_616x353.jpg",
            "BLAST Premier",
            "$425,000",
            "/pages/PageAccueilTournoi/ArbreTournoi.php"
        );

        renderTournament(
            "/img/ImagesDivers/capsule_616x353.jpg",
            "IEM Katowice",
            "$1,000,000",
            "/pages/PageAccueilTournoi/ArbreTournoi.php"
        );

        ?>
        </div>
    </section>

</body>

</html>