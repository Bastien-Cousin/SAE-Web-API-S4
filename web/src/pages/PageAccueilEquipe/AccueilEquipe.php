<!DOCTYPE html>
<html lang="fr">

    <head>
        <meta charset="UTF-8">
        <title>CS2 - Équipes</title>
        <link rel="stylesheet" href="../style.css">
    </head>

    <body>

        <?php include '../components/navbar.php'; ?>
        <?php include '../components/cardTeam.php'; ?>

            <div class="filters"> <input type="text" placeholder="Rechercher une équipe..." class="search-bar">

            <button class="filter-btn">Pays</button>
            <button class="filter-btn filter-blue">Filtrer</button>
            <button class="filter-btn filter-green">Rechercher</button>

        </div>

        <div
            class="teams"><?php
            renderTeamCard("/img/vitality.png", "Team Vitality");
            renderTeamCard("/img/faze.png", "FaZe");
            renderTeamCard("/img/mouz.png", "Mouz");
            renderTeamCard("/img/spirit.png", "Team Spirit");
            ?>

        </div>

    </body>

</html>

