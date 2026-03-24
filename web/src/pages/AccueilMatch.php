<!DOCTYPE html>
<html lang="fr">

    <head>
        <meta charset="UTF-8">
        <title>CS2 - Accueil Tournoi</title>
        <link rel="stylesheet" href="../style.css">
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

        <div
            class="matches"><?php
            renderMatchBar(
                "/img/vitality.png",
                "Team Vitality",
                "2 - 0",
                "Faze",
                "/img/faze.png",
                "24/03/2026"
            );

            renderMatchBar(
                "/img/mouz.png",
                "Mouz",
                "1 - 2",
                "Team Spirit",
                "/img/spirit.png",
                "23/03/2026"
            );

            renderMatchBar(
                "/img/vitality.png",
                "Team Vitality",
                "1 - 2",
                "Mouz",
                "/img/mouz.png",
                "23/03/2026"
            );

            renderMatchBar(
                "/img/faze.png",
                "Faze",
                "1 - 2",
                "Team Spirit",
                "/img/spirit.png",
                "23/03/2026"
            );
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
            flex-wrap: wrap;
            justify-content: center;
        }

        .filter-btn {
            padding: 8px 14px;
            border: 2px solid black;
            border-radius: 5px;
            cursor: pointer;
            background-color: #1b265c;
            color: white;
            transition: 0.2s;
            margin: 20px 0 20px 0;
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
    </style>

</html>

