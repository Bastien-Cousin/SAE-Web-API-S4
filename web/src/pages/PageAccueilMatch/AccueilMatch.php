<!DOCTYPE html>
<html lang="fr">

    <head>
        <meta charset="UTF-8">
        <title>CS2 - Accueil Tournoi</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@400;600;700;800;900&family=Barlow:wght@300;400;500&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="../../style.css">
        <link rel="stylesheet" href="../../components/ComponentNavbar/navbar.css">
        <link rel="stylesheet" href="../../components/componentMatchBar/matchBar.css">
        <link rel="stylesheet" href="AccueilMatch.css">
    </head>

    <body>

        <?php include __DIR__ . '/../../components/ComponentNavbar/navbar.php'; ?>

        <div class="filters">
            <button class="filter-btn">Maps</button>
            <button class="filter-btn">Équipe</button>
            <button class="filter-btn">Score</button>
            <button class="filter-btn">Date</button>

            <button class="filter-btn filter-blue">Filtrer</button>
            <button class="filter-btn filter-green">Rechercher</button>
        </div>

        <?php include __DIR__ . '/../../components/componentMatchBar/matchBar.php'; ?>

        <div
            class="matches"><?php

            renderMatchBar(
                "/img/LogosEquipes/vitality.png",
                "Team Vitality",
                "2 - 0",
                "Faze",
                "/img/LogosEquipes/faze.png",
                "24/03/2026",
                "/pages/PageAccueilMatch/DescriptionMatch.php?team1=Team Vitality&team2=Faze&score=2-0&maps=Inferno:13-9;Mirage:13-8"
            );

            renderMatchBar(
                "/img/LogosEquipes/mouz.png",
                "Mouz",
                "1 - 2",
                "Team Spirit",
                "/img/LogosEquipes/spirit.png",
                "23/03/2026",
                "/pages/PageAccueilMatch/DescriptionMatch.php?team1=Mouz&team2=Team Spirit&score=1-2&maps=Inferno:9-13;Mirage:16-13;Nuke:8-13"
            );

            renderMatchBar(
                "/img/LogosEquipes/vitality.png",
                "Team Vitality",
                "2 - 1",
                "Mouz",
                "/img/LogosEquipes/mouz.png",
                "23/03/2026",
                "/pages/PageAccueilMatch/DescriptionMatch.php?team1=Team Vitality&team2=Mouz&score=2-1&maps=Inferno:13-9;Mirage:10-13;Nuke:13-8"
            );

            renderMatchBar(
                "/img/LogosEquipes/faze.png",
                "Faze",
                "1 - 2",
                "Team Spirit",
                "/img/LogosEquipes/spirit.png",
                "23/03/2026",
                "/pages/PageAccueilMatch/DescriptionMatch.php?team1=Faze&team2=Team Spirit&score=1-2&maps=Inferno:11-13;Mirage:13-10;Nuke:9-13"
            );

            ?>
        </div>

    </body>

</html>

