<!DOCTYPE html>
<html lang="fr">

    <head>
        <meta charset="UTF-8">
        <title>CS2 - Arbre du Tournoi</title>

        <link rel="stylesheet" href="../../style.css">
        <link rel="stylesheet" href="../../components/ComponentNavbar/navbar.css">
        <link rel="stylesheet" href="ArbreTournoi.css">
    </head>

    <body>

        <?php include __DIR__ . '/../../components/ComponentNavbar/navbar.php'; ?>

        <div
            class="tournament-container"><?php
            try {
                // Récupérer le nom du tournoi depuis l'URL
                $tournamentName = urldecode($_GET['name'] ?? '');

                if (!$tournamentName) {
                    echo '<p class="error">Aucun tournoi sélectionné</p>';
                    exit;
                }

                // Connexion à la base de données
                $pdo = new PDO(
                    "pgsql:host=" . (getenv('POSTGRES_HOST') ?: 'db') .
                    ";port=" . (getenv('POSTGRES_PORT') ?: '5432') .
                    ";dbname=" . (getenv('POSTGRES_DB') ?: 'sae_db'),
                    getenv('POSTGRES_USER') ?: 'sae_user',
                    getenv('POSTGRES_PASSWORD') ?: 'sae_password',
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                );

                // Récupérer les infos du tournoi
                $stmtTournament = $pdo->prepare("
                SELECT pageid, name, bannerurl 
                FROM tournament 
                WHERE LOWER(name) = LOWER(:name) 
                LIMIT 1
            ");
                $stmtTournament->execute([':name' => $tournamentName]);
                $tournament = $stmtTournament->fetch(PDO::FETCH_ASSOC);

                if (!$tournament) {
                    echo '<p class="error">Tournoi non trouvé</p>';
                    exit;
                }

                // Afficher le header du tournoi
                echo '<div class="tournament-header">';
                if (!empty($tournament['bannerurl'])) {
                    echo '<img src="' . htmlspecialchars($tournament['bannerurl']) . '" alt="' . htmlspecialchars($tournament['name']) . '" class="tournament-banner">';
                }
                echo '<h1>' . htmlspecialchars($tournament['name']) . '</h1>';
                echo '</div>';

                // Récupérer tous les matches du tournoi
                $stmtMatches = $pdo->prepare("
                SELECT 
                    pageid,
                    objectname,
                    match2bracketdata,
                    team1_id,
                    team2_id,
                    date,
                    status,
                    winner
                FROM match
                WHERE tournament = :tournament
                                    AND COALESCE(status, '') <> 'prediction_only'
                ORDER BY date ASC
            ");
                $stmtMatches->execute([':tournament' => $tournament['name']]);
                $matches = $stmtMatches->fetchAll(PDO::FETCH_ASSOC);

                if (empty($matches)) {
                    echo '<p class="no-data">Aucun match disponible pour ce tournoi</p>';
                    exit;
                }

                // Fonction pour décoder le JSON
                function decodeJson($data)
                {
                    if (is_array($data))
                        return $data;
                    $decoded = json_decode($data, true);
                    return json_last_error() === JSON_ERROR_NONE ? $decoded : [];
                }

                // Fonction pour obtenir le nom de l'équipe
                function getTeamName($pdo, $teamId)
                {
                    if (!$teamId)
                        return 'TBD';
                    $stmt = $pdo->prepare("SELECT name FROM team WHERE pageid = :id LIMIT 1");
                    $stmt->execute([':id' => $teamId]);
                    $team = $stmt->fetch(PDO::FETCH_ASSOC);
                    return $team ? htmlspecialchars($team['name']) : 'Unknown';
                }

                // Grouper les matches par phase
                $phases = [];
                $groupStageMatches = [];
                $playoffMatches = [];

                foreach ($matches as $match) {
                    $bracketData = decodeJson($match['match2bracketdata']);
                    $sectionHeader = $bracketData['sectionheader'] ?? 'Unknown';

                    // Séparer group stage et playoffs
                    if ($sectionHeader === 'Group Stage') {
                        $groupStageMatches[] = $match;
                    } else {
                        // Brackets de playoff
                        $bracketSection = $bracketData['bracketsection'] ?? 'upper';
                        $phaseKey = $sectionHeader . ' (' . $bracketSection . ' bracket)';

                        if (!isset($playoffMatches[$phaseKey])) {
                            $playoffMatches[$phaseKey] = [];
                        }
                        $playoffMatches[$phaseKey][] = $match;
                    }
                }

                // Afficher Group Stage s'il existe
                if (!empty($groupStageMatches)) {
                    echo '<section class="phase-section">';
                    echo '<h2>Group Stage</h2>';
                    echo '<div class="matches-container">';

                    // Grouper par groupRoundIndex
                    $roundedMatches = [];
                    foreach ($groupStageMatches as $match) {
                        $bracketData = decodeJson($match['match2bracketdata']);
                        $roundIndex = $bracketData['groupRoundIndex'] ?? 0;

                        if (!isset($roundedMatches[$roundIndex])) {
                            $roundedMatches[$roundIndex] = [];
                        }
                        $roundedMatches[$roundIndex][] = $match;
                    }

                    // Afficher chaque round
                    foreach ($roundedMatches as $roundIndex => $roundMatches) {
                        echo '<div class="round">';
                        echo '<h3>Round ' . ($roundIndex + 1) . '</h3>';

                        foreach ($roundMatches as $match) {
                            $team1Name = getTeamName($pdo, $match['team1_id']);
                            $team2Name = getTeamName($pdo, $match['team2_id']);

                            echo '<div class="match-card">';
                            echo '<div class="match-teams">';
                            echo '<div class="team">' . $team1Name . '</div>';
                            echo '<div class="match-separator">vs</div>';
                            echo '<div class="team">' . $team2Name . '</div>';
                            echo '</div>';

                            if ($match['status'] === 'Completed' && $match['winner']) {
                                echo '<div class="match-result">Winner: ' . htmlspecialchars($match['winner']) . '</div>';
                            } else {
                                echo '<div class="match-status">' . ($match['status'] ?? 'Pending') . '</div>';
                            }

                            echo '</div>';
                        }

                        echo '</div>';
                    }

                    echo '</div>';
                    echo '</section>';
                }

                // Afficher les Playoffs
                if (!empty($playoffMatches)) {
                    echo '<section class="phase-section">';
                    echo '<h2>Playoffs</h2>';

                    foreach ($playoffMatches as $phaseKey => $matches) {
                        echo '<div class="playoff-phase">';
                        echo '<h3>' . htmlspecialchars($phaseKey) . '</h3>';
                        echo '<div class="matches-container">';

                        // Trier par coordinates pour respecter l'ordre de l'arbre
                        usort($matches, function ($a, $b) {
                            $aData = decodeJson($a['match2bracketdata']);
                            $bData = decodeJson($b['match2bracketdata']);

                            $aCoords = $aData['coordinates'] ?? [];
                            $bCoords = $bData['coordinates'] ?? [];

                            // Trier par roundIndex, puis par matchIndexInRound
                            $aRound = $aCoords['roundIndex'] ?? 0;
                            $bRound = $bCoords['roundIndex'] ?? 0;

                            if ($aRound !== $bRound)
                                return $aRound - $bRound;

                            $aMatch = $aCoords['matchIndexInRound'] ?? 0;
                            $bMatch = $bCoords['matchIndexInRound'] ?? 0;

                            return $aMatch - $bMatch;
                        });

                        foreach ($matches as $match) {
                            $team1Name = getTeamName($pdo, $match['team1_id']);
                            $team2Name = getTeamName($pdo, $match['team2_id']);

                            echo '<div class="match-card">';
                            echo '<div class="match-teams">';
                            echo '<div class="team">' . $team1Name . '</div>';
                            echo '<div class="match-separator">vs</div>';
                            echo '<div class="team">' . $team2Name . '</div>';
                            echo '</div>';

                            if ($match['status'] === 'Completed' && $match['winner']) {
                                echo '<div class="match-result">Winner: ' . htmlspecialchars($match['winner']) . '</div>';
                            } else {
                                echo '<div class="match-status">' . ($match['status'] ?? 'Pending') . '</div>';
                            }

                            echo '</div>';
                        }

                        echo '</div>';
                        echo '</div>';
                    }

                    echo '</section>';
                }

            } catch (Exception $e) {
                echo '<p class="error">Erreur: ' . htmlspecialchars($e->getMessage()) . '</p>';
                error_log("Error in ArbreTournoi.php: " . $e->getMessage());
            }
            ?>
        </div>

    </body>

</html>
