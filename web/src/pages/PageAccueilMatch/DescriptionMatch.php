<!DOCTYPE html>
<html lang="fr">

    <head>
        <meta charset="UTF-8">
        <title>Description Match</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

        <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@400;600;700;800;900&family=Barlow:wght@300;400;500&display=swap" rel="stylesheet">

        <link rel="stylesheet" href="../../style.css">
        <link rel="stylesheet" href="../../components/ComponentNavbar/navbar.css">
        <link rel="stylesheet" href="DescriptionMatch.css">
    </head>

    <body>

        <?php include __DIR__ . '/../../components/ComponentNavbar/navbar.php'; ?>

        <?php
        $team1 = $_GET['team1'] ?? 'Team A';
        $team2 = $_GET['team2'] ?? 'Team B';
        $score = $_GET['score'] ?? '0-0';
        $mapsParam = $_GET['maps'] ?? '';

        list($score1, $score2) = explode('-', $score);

        // Mapping logos
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

        // Chemin cohérent avec AccueilMatch
        $baseLogoPath = "/img/LogosEquipes/";

        $logo1 = $baseLogoPath . ($logoMap[$team1] ?? "default.png");
        $logo2 = $baseLogoPath . ($logoMap[$team2] ?? "default.png");

        // Maps parsing
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

        <!-- Bouton retour --><a href="/pages/PageAccueilMatch/AccueilMatch.php" class="back-btn"> ← Retour</a>

        <div class="match-details">

            <div class="match-header">

                <div class="team">
                    <img src="<?php echo $logo1; ?>" alt="Logo équipe 1">
                    <h2><?php echo $team1; ?></h2>
                </div>

                <div class="score-center">
                    <h1><?php echo "$score1 - $score2"; ?></h1>
                </div>

                <div class="team">
                    <h2><?php echo $team2; ?></h2>
                    <img src="<?php echo $logo2; ?>" alt="Logo équipe 2">
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
