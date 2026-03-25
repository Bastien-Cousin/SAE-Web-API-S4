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

    <style>
        body {
            background-color: white;
        }

        .filters {
            display: flex;
            gap: 10px;
            margin: 20px 0;
            justify-content: center;
            align-items: center;
            flex-wrap: wrap;
        }

        .search-bar {
            padding: 8px 12px;
            border-radius: 5px;
            border: 2px solid #1b265c;
            outline: none;
        }

        .filter-btn {
            padding: 8px 14px;
            margin: 20px 0;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            background-color: #1b265c;
            color: white;
            transition: 0.2s;
        }

        .filter-btn:hover {
            opacity: 0.8;
        }

        .filter-blue {
            background-color: #1e3a8a;
        }

        .filter-green {
            background-color: #16a34a;
        }

        .teams {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 40px;
        }
    </style>

</html>

