<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>CS2 - Accueil Tournoi</title>
    <link rel="stylesheet" href="AccueilMatch.css">
</head>

<body>
    <?php include '../components/navbar.php'; ?>
    <div class="filters">
        <button class="filter-btn">Maps</button>
        <button class="filter-btn">Équipe</button>
        <button class="filter-btn">Score</button>
        <button class="filter-btn">Date</button>

        <button class="filter-btn filter-blue">Filtrer</button>
        <button class="filter-btn filter-green">Rechercher</button>
    </div>

    <?php include '../components/matchBar.php'; ?>

    <div class="matches">

        <?php

        renderMatchBar(
            "/img/vitality.png",
            "Team Vitality",
            "2 - 0",
            "Faze",
            "/img/faze.png",
            "24/03/2026",
            "/pages/DescriptionMatch.php?team1=Team Vitality&team2=Faze&score=2-0&maps=Inferno:13-9;Mirage:13-8"
        );

        renderMatchBar(
            "/img/mouz.png",
            "Mouz",
            "1 - 2",
            "Team Spirit",
            "/img/spirit.png",
            "23/03/2026",
            "/pages/DescriptionMatch.php?team1=Mouz&team2=Team Spirit&score=1-2&maps=Inferno:9-13;Mirage:16-13;Nuke:8-13"
        );

        renderMatchBar(
            "/img/vitality.png",
            "Team Vitality",
            "2 - 1",
            "Mouz",
            "/img/mouz.png",
            "23/03/2026",
            "/pages/DescriptionMatch.php?team1=Team Vitality&team2=Mouz&score=2-1&maps=Inferno:13-9;Mirage:10-13;Nuke:13-8"
        );

        renderMatchBar(
            "/img/faze.png",
            "Faze",
            "1 - 2",
            "Team Spirit",
            "/img/spirit.png",
            "23/03/2026",
            "/pages/DescriptionMatch.php?team1=Faze&team2=Team Spirit&score=1-2&maps=Inferno:11-13;Mirage:13-10;Nuke:9-13"
        );

        ?>

    </div>
</body>

</html>