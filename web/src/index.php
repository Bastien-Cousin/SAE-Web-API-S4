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
            <h2 class="section-title">Prochains matchs</h2>

            <?php
            $matches = [
                ['t1' => 'Vitality', 't2' => 'FaZe Clan', 'event' => 'ESL Pro League', 'date' => "Aujourd'hui 19:00"],
                ['t1' => 'NaVi', 't2' => 'G2', 'event' => 'BLAST Premier', 'date' => 'Demain 17:30'],
                ['t1' => 'Spirit', 't2' => 'ENCE', 'event' => 'IEM Cologne', 'date' => '26 Mar 20:00'],
            ];
            ?>

            <div class="upcoming__list">
                <?php foreach ($matches as $m): ?>
                    <div class="upcoming__row">
                        <span class="upcoming__event"><?= htmlspecialchars($m['event']) ?></span>

                        <div class="upcoming__teams">
                            <span class="upcoming__team"><?= htmlspecialchars($m['t1']) ?></span>
                            <span class="upcoming__vs">VS</span>
                            <span class="upcoming__team"><?= htmlspecialchars($m['t2']) ?></span>
                        </div>

                        <span class="upcoming__date"><?= htmlspecialchars($m['date']) ?></span>
                        <a href="#" class="btn btn--xs">Prédire</a>
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