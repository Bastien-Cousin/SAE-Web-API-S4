<!DOCTYPE html>
<html lang="fr">

    <head>
        <meta charset="UTF-8">
        <title>Description Match</title>
        <link rel="stylesheet" href="../style.css">
    </head>

    <body>

        <?php include '../components/navbar.php'; ?>

        <?php
        $team1 = $_GET['team1'] ?? 'Team A';
        $team2 = $_GET['team2'] ?? 'Team B';
        $score = $_GET['score'] ?? '0-0';

        list($score1, $score2) = explode('-', $score);

        $logoMap = [
            "Team Vitality" => "vitality.png",
            "Vitality" => "vitality.png",

            "Team Spirit" => "spirit.png",
            "Spirit" => "spirit.png",

            "Mouz" => "mouz.png",
            "MOUZ" => "mouz.png",

            "Faze" => "faze.png",
            "FaZe" => "faze.png"
        ];

        $logo1 = "/img/" . ($logoMap[$team1] ?? "default.png");
        $logo2 = "/img/" . ($logoMap[$team2] ?? "default.png");

        $nbMaps = ($score1 == 2 && $score2 == 1) || ($score2 == 2 && $score1 == 1) ? 3 : 2;

        $maps = ["Inferno", "Mirage", "Nuke"];
        ?>

            <a href="/pages/AccueilMatch.php" class="back-btn"> ← Retour</a>

        <div class="match-details">

            <div class="match-header">

                <div class="team">
                    <img src="<?php echo $logo1; ?>" alt="">
                    <h2><?php echo $team1; ?></h2>
                </div>

                <div class="score-center">
                    <h1><?php echo "$score1 - $score2"; ?></h1>
                </div>

                <div class="team">
                    <h2><?php echo $team2; ?></h2>
                    <img src="<?php echo $logo2; ?>" alt="">
                </div>

            </div>

            <div
                class="maps"><?php
                for ($i = 0; $i < $nbMaps; $i++) {

                    if ($i % 2 == 0) {
                        $left = 13;
                        $right = 9;
                    } else {
                        $left = 9;
                        $right = 13;
                    }

                    echo "
                <div class='map'>
                    <span class='score-left'>$left</span>
                    <span class='map-name'>{$maps[$i]}</span>
                    <span class='score-right'>$right</span>
                </div>
                ";
                }
                ?>

            </div>

        </div>

    </body>

    <style>
        body {
            background-color: white;
        }

        .match-details {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-top: 40px;
        }

        .match-header {
            display: flex;
            align-items: center;
            justify-content: space-between;

            background-color: #1b265c;
            padding: 20px 40px;
            border-radius: 12px;
            border: 2px solid #1e3a8a;

            width: 60%;
            max-width: 900px;
            margin: 20px auto;
        }

        .match-header .team {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .match-header img {
            width: 60px;
            height: 60px;
            object-fit: contain;
        }

        .score-center {
            flex: 1;
            text-align: center;
        }

        .match-header .team {
            display: flex;
            align-items: center;
            gap: 15px;
            flex: 1;
        }

        .match-header .team:first-child {
            justify-content: flex-start;
        }

        .match-header .team:last-child {
            justify-content: flex-end;
            text-align: right;
        }

        .score-center h1 {
            font-size: 32px;
            margin: 0;
        }

        .maps {
            width: 60%;
            max-width: 900px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin: auto;
        }

        .map {
            display: flex;
            align-items: center;
            justify-content: space-between;

            background-color: #1b265c;
            padding: 10px 20px;
            border-radius: 8px;
            border: 1px solid #1e3a8a;
        }

        .score-left,
        .score-right {
            width: 50px;
            text-align: center;
            font-weight: bold;
        }

        .map-name {
            flex: 1;
            text-align: center;
        }

        .map:hover {
            border-color: #fca311;
        }

        .back-btn {
            display: inline-block;
            margin: 20px;
            padding: 8px 14px;
            background-color: #1b265c;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: 0.2s;
        }

        .back-btn:hover {
            background-color: #fca311;
            color: black;
        }
    </style>
</html>
