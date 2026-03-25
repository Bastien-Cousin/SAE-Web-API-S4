<!DOCTYPE html>
<html lang="fr">

    <head>
        <meta charset="UTF-8">
        <title>CS2 - Équipes</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@400;600;700;800;900&family=Barlow:wght@300;400;500&display=swap" rel="stylesheet">

        <link rel="stylesheet" href="../../style.css">
        <link rel="stylesheet" href="../../components/ComponentNavbar/navbar.css">
        <link rel="stylesheet" href="../../components/ComponentCardTeam/cardTeam.css">
        <link rel="stylesheet" href="AccueilEquipe.css">
    </head>

    <body>

        <?php include __DIR__ . '/../../components/ComponentNavbar/navbar.php'; ?>

        <?php include __DIR__ . '/../../components/ComponentCardTeam/cardTeam.php'; ?>

            <div class="filters"> <input type="text" placeholder="Rechercher une équipe..." class="search-bar">

            <button class="filter-btn">Pays</button>
            <button class="filter-btn filter-blue">Filtrer</button>
            <button class="filter-btn filter-green">Rechercher</button>
        </div>

        <div
            class="teams"><?php

            renderTeamCard("/img/LogosEquipes/vitality.png", "Team Vitality");
            renderTeamCard("/img/LogosEquipes/faze.png", "FaZe");
            renderTeamCard("/img/LogosEquipes/mouz.png", "Mouz");
            renderTeamCard("/img/LogosEquipes/spirit.png", "Team Spirit");

            ?>
        </div>

    </body>

</html>

