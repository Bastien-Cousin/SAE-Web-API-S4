<!DOCTYPE html>
<html lang="fr">

    <head>
        <meta charset="UTF-8">
        <title>Description Match</title>
        <link rel="stylesheet" href="DescriptionMatch.css">
    </head>

    <body>

        <?php include '../components/navbar.php'; ?>

        <?php
        $team1 = $_GET['team1'] ?? 'Team A';
        $team2 = $_GET['team2'] ?? 'Team B';
        $score = $_GET['score'] ?? '0-0';
        $mapsParam = $_GET['maps'] ?? '';

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

        $mapsData = [];

        if (!empty($mapsParam)) {
            $mapsArray = explode(';', $mapsParam);

            foreach ($mapsArray as $mapString) {
                list($mapName, $scores) = explode(':', $mapString);
                list($s1, $s2) = explode('-', $scores);

                $mapsData[] = [
                    "name" => $mapName,
                    "team1" => (int) $s1,
                    "team2" => (int) $s2
                ];
            }
        }
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
                class="maps">

                <?php foreach ($mapsData as $map): ?>

                    <div class="map">
                        <span class="score-left"><?= $map["team1"] ?></span>
                        <span class="map-name"><?= $map["name"] ?></span>
                        <span class="score-right"><?= $map["team2"] ?></span>
                    </div>

                <?php endforeach; ?>

            </div>

        </div>

    </body>

</html>
