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
        $host = getenv('POSTGRES_HOST') ?: 'db';
        $port = getenv('POSTGRES_PORT') ?: '5432';
        $dbname = getenv('POSTGRES_DB') ?: 'sae_db';
        $user = getenv('POSTGRES_USER') ?: 'sae_user';
        $password = getenv('POSTGRES_PASSWORD') ?: 'sae_password';

        try {
            $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $user, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("❌ Erreur de connexion à la base de données : " . $e->getMessage());
        }

        $matchId = $_GET['id'] ?? null;
        if (!$matchId) {
            die("❌ ID de match manquant.");
        }

        function getTeamLogoByName($pdo, $teamName)
        {
            try {
                $stmt = $pdo->prepare("SELECT logourl, textlesslogourl FROM team WHERE LOWER(name) = LOWER(:name) LIMIT 1");
                $stmt->execute([':name' => $teamName]);
                $team = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($team) {
                    $logo = $team['logourl'] ?? $team['textlesslogourl'] ?? '';
                    return $logo ?: null;
                }
            } catch (Exception $e) {
                error_log("Error looking up team logo for: $teamName - " . $e->getMessage());
            }
            return null;
        }

        function safeLogo($logo)
        {
            return !empty($logo) ? $logo : '/img/LogosEquipes/default.png';
        }

        function parseMapsData($match2games)
        {
            if (empty($match2games)) {
                return [];
            }
            if (!is_array($match2games)) {
                $decoded = json_decode($match2games, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    return [];
                }
                $match2games = $decoded;
            }
            if (!is_array($match2games)) {
                return [];
            }

            $maps = [];
            foreach ($match2games as $map) {
                if (!is_array($map))
                    continue;

                $mapName = $map['map'] ?? $map['name'] ?? null;

                $s1 = null;
                $s2 = null;

                if (isset($map['score']) && preg_match('/^(\d+)-(\d+)$/', (string) $map['score'], $m)) {
                    $s1 = intval($m[1]);
                    $s2 = intval($m[2]);
                } elseif (isset($map['result']) && preg_match('/^(\d+)-(\d+)$/', (string) $map['result'], $m)) {
                    $s1 = intval($m[1]);
                    $s2 = intval($m[2]);
                } elseif (isset($map['scores']) && is_array($map['scores']) && count($map['scores']) >= 2) {
                    $s1 = intval($map['scores'][0] ?? 0);
                    $s2 = intval($map['scores'][1] ?? 0);
                } elseif (isset($map['team1_score']) && isset($map['team2_score'])) {
                    $s1 = intval($map['team1_score']);
                    $s2 = intval($map['team2_score']);
                }

                if (!$mapName || $s1 === null || $s2 === null)
                    continue;

                if (($map['status'] ?? '') !== 'notplayed') {
                    $maps[] = [
                        'name' => (string) $mapName,
                        'team1' => $s1,
                        'team2' => $s2,
                    ];
                }
            }

            return $maps;
        }

        function parseScoreFromOpponents($match2opponents)
        {
            if (empty($match2opponents)) {
                return null;
            }
            if (!is_array($match2opponents)) {
                $decoded = json_decode($match2opponents, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    return null;
                }
                $match2opponents = $decoded;
            }
            if (!is_array($match2opponents)) {
                return null;
            }

            $scores = [];
            $names = [];

            foreach ($match2opponents as $opp) {
                if (!is_array($opp)) {
                    continue;
                }
                if (isset($opp['score'])) {
                    $scores[] = intval($opp['score']);
                } elseif (isset($opp['scores']) && is_array($opp['scores'])) {
                    $scores[] = intval($opp['scores'][0] ?? 0);
                }
                if (isset($opp['name'])) {
                    $names[] = $opp['name'];
                } elseif (isset($opp['team'])) {
                    $names[] = $opp['team'];
                }
            }

            if (count($scores) >= 2) {
                return [intval($scores[0]), intval($scores[1])];
            }

            if (isset($match2opponents['team1']) && isset($match2opponents['team2'])) {
                $s1 = intval($match2opponents['team1']['score'] ?? $match2opponents['team1']['result'] ?? 0);
                $s2 = intval($match2opponents['team2']['score'] ?? $match2opponents['team2']['result'] ?? 0);
                return [$s1, $s2];
            }

            return null;
        }

        $stmt = $pdo->prepare("SELECT m.pageid, m.date, m.match2games, m.winner, m.match2opponents, m.team1_id, m.team2_id, t1.name AS team1_name, t2.name AS team2_name FROM match m LEFT JOIN team t1 ON t1.pageid = m.team1_id LEFT JOIN team t2 ON t2.pageid = m.team2_id WHERE m.pageid = ? AND COALESCE(m.status, '') <> 'prediction_only'");
        $stmt->execute([$matchId]);
        $match = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$match) {
            die("❌ Match non trouvé.");
        }

        $team1Name = (string) ($match['team1_name'] ?? '');
        $team2Name = (string) ($match['team2_name'] ?? '');
        $date = !empty($match['date']) ? date('Y-m-d', strtotime($match['date'])) : 'N/A';

        $match2opponents = $match['match2opponents'] ?? '';
        if (!is_array($match2opponents)) {
            $decodedOpponents = json_decode($match2opponents, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $match2opponents = $decodedOpponents;
            }
        }

        if (is_array($match2opponents) && ($team1Name === '' || $team2Name === '')) {
            $names = [];
            foreach ($match2opponents as $opp) {
                if (is_array($opp)) {
                    if (!empty($opp['name'])) {
                        $names[] = $opp['name'];
                    } elseif (!empty($opp['team'])) {
                        $names[] = $opp['team'];
                    }
                }
            }
            if (!empty($names[0])) {
                $team1Name = $names[0];
            }
            if (!empty($names[1])) {
                $team2Name = $names[1];
            }
        }

        $team1Logo = safeLogo(getTeamLogoByName($pdo, $team1Name));
        $team2Logo = safeLogo(getTeamLogoByName($pdo, $team2Name));

        $mapsData = parseMapsData($match['match2games'] ?? '');

        $score1 = 0;
        $score2 = 0;

        $scoreFromOpponents = parseScoreFromOpponents($match2opponents);
        if ($scoreFromOpponents !== null) {
            $score1 = $scoreFromOpponents[0];
            $score2 = $scoreFromOpponents[1];
        } elseif (!empty($mapsData)) {
            foreach ($mapsData as $mapRow) {
                if ($mapRow['team1'] > $mapRow['team2']) {
                    $score1++;
                } elseif ($mapRow['team2'] > $mapRow['team1']) {
                    $score2++;
                }
            }
        } elseif (!empty($match['winner'])) {
            if ((string) $match['winner'] === '1') {
                $score1 = 1;
                $score2 = 0;
            } elseif ((string) $match['winner'] === '2') {
                $score1 = 0;
                $score2 = 1;
            }
        }
        ?>

            <a href="/pages/PageAccueilMatch/AccueilMatch.php" class="back-btn"> ← Retour</a>

        <div class="match-details">

            <div class="match-header">

                <div class="team">
                    <img src="<?php echo $team1Logo; ?>" alt="Logo équipe 1">
                    <h2><?php echo $team1Name; ?></h2>
                </div>

                <div class="score-center">
                    <h1><?php echo "$score1 - $score2"; ?></h1>
                </div>

                <div class="team">
                    <h2><?php echo $team2Name; ?></h2>
                    <img src="<?php echo $team2Logo; ?>" alt="Logo équipe 2">
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

