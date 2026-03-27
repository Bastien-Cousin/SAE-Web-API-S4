<?php session_start(); ?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CS2 - Projet</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="components/ComponentNavbar/navbar.css">
    <link rel="stylesheet" href="components/ComponentFooter/footer.css">
</head>

<body>

    <?php include 'components/ComponentNavbar/navbar.php'; ?>

    <section class="hero">
        <div class="container hero__content">
            <h1 class="hero__title">Bienvenue sur Counter Data</h1>
            <p class="hero__subtitle">
                Retrouvez l'intégralité des statistiques concernant les équipes, les joueurs et les matchs de la scène
                professionnelle Counter-Strike 2.
            </p>
        </div>
    </section>

    <div class="statsband">
        <div class="statsband__inner">
            <?php
            $players_count = 'XX';
            $teams_count = 'XX';
            $tournaments_count = 'XX';

            $host = getenv('POSTGRES_HOST') ?: 'db';
            $port = getenv('POSTGRES_PORT') ?: '5432';
            $dbname = getenv('POSTGRES_DB') ?: 'sae_db';
            $user = getenv('POSTGRES_USER') ?: 'sae_user';
            $password = getenv('POSTGRES_PASSWORD') ?: 'sae_password';

            try {
                $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $user, $password);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                $stmt_players = $pdo->query('SELECT COUNT(*) as count FROM player');
                $result_players = $stmt_players->fetch(PDO::FETCH_ASSOC);
                $players_count = $result_players['count'] ?? 'XX';

                $stmt_teams = $pdo->query('SELECT COUNT(*) as count FROM team');
                $result_teams = $stmt_teams->fetch(PDO::FETCH_ASSOC);
                $teams_count = $result_teams['count'] ?? 'XX';

                $stmt_tournaments = $pdo->query('SELECT COUNT(*) as count FROM tournament');
                $result_tournaments = $stmt_tournaments->fetch(PDO::FETCH_ASSOC);
                $tournaments_count = $result_tournaments['count'] ?? 'XX';
            } catch (PDOException $e) {
                error_log("Erreur stats : " . $e->getMessage());
            }

            $stats = [
                ['value' => $players_count, 'label' => 'Joueurs'],
                ['value' => $teams_count, 'label' => 'Equipes'],
                ['value' => '11', 'label' => 'Maps'],
                ['value' => $tournaments_count, 'label' => 'Tournois'],
            ];
            foreach ($stats as $s): ?>
                <div class="statsband__item">
                    <strong><?= $s['value'] ?></strong>
                    <span><?= $s['label'] ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <section class="upcoming">
        <div class="container">
            <h2 class="section-title">Matchs récents</h2>

            <?php
            $matches = [];

            try {
                $stmt_matches = $pdo->query("
                    SELECT 
                        m.date,
                        m.tournament,
                        m.match2opponents,
                        t1.name AS team1_name,
                        t2.name AS team2_name
                    FROM match m
                    LEFT JOIN team t1 ON m.team1_id = t1.pageid
                    LEFT JOIN team t2 ON m.team2_id = t2.pageid
                    WHERE m.finished = true
                    ORDER BY m.date DESC
                    LIMIT 3
                ");

                $matches = $stmt_matches->fetchAll(PDO::FETCH_ASSOC);

            } catch (PDOException $e) {
                error_log("Erreur matchs : " . $e->getMessage());
            }
            ?>

            <div class="upcoming__list">
                <?php foreach ($matches as $m):

                    $team1 = $m['team1_name'];
                    $team2 = $m['team2_name'];

                    if ((!$team1 || !$team2) && !empty($m['match2opponents'])) {
                        $opponents = json_decode($m['match2opponents'], true);

                        if (isset($opponents[0]['name']))
                            $team1 = $opponents[0]['name'];
                        if (isset($opponents[1]['name']))
                            $team2 = $opponents[1]['name'];
                    }

                    $team1 = $team1 ?: 'N/A';
                    $team2 = $team2 ?: 'N/A';

                    $date = $m['date']
                        ? date('d/m/Y H:i', strtotime($m['date']))
                        : 'Date inconnue';

                    ?>
                    <div class="upcoming__row">
                        <span class="upcoming__event">
                            <?= htmlspecialchars($m['tournament'] ?? 'Inconnu') ?>
                        </span>

                        <div class="upcoming__teams">
                            <span class="upcoming__team">
                                <?= htmlspecialchars($team1) ?>
                            </span>

                            <span class="upcoming__vs">VS</span>

                            <span class="upcoming__team">
                                <?= htmlspecialchars($team2) ?>
                            </span>
                        </div>

                        <span class="upcoming__date"><?= $date ?></span>

                        <a href="#" class="btn btn--xs">Voir</a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <?php include 'components/ComponentFooter/footer.php'; ?>

    <script>
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(e => {
                if (e.isIntersecting) {
                    e.target.classList.add('is-visible');
                }
            });
        }, { threshold: 0.15 });

        document.querySelectorAll('.upcoming__row')
            .forEach(el => observer.observe(el));
    </script>

</body>

</html>