<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CS2 - Projet</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@400;600;700;800;900&family=Barlow:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>

<body>

    <?php include 'components/navbar.php'; ?>

    <!-- HERO -->
    <section class="hero">
        <div class="container hero__content">
            <h1 class="hero__title">Bienvenue sur CS2 - Projet</h1>
            <p class="hero__subtitle">Votre source ultime pour les données, analyses et prédictions du monde compétitif de Counter-Strike 2.</p>
            <br>
            <a href="#features" class="btn btn--primary">Découvrir</a>
        </div>

        <div class="hero__scroll-hint">
            <span></span>
            <p>Scroll</p>
        </div>
    </section>

    <!-- STATS BAND -->
    <div class="statsband">
        <div class="statsband__inner">
            <?php
            $stats = [
                ['value' => '1.2M',  'label' => 'Joueurs en ligne'],
                ['value' => '32',    'label' => 'Maps actives'],
                ['value' => '128',   'label' => 'Tick rate'],
                ['value' => 'TOP 5', 'label' => 'Tournois suivis'],
            ];
            foreach ($stats as $s): ?>
                <div class="statsband__item">
                    <strong><?= $s['value'] ?></strong>
                    <span><?= $s['label'] ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- FEATURES -->
    <section class="features" id="features">
        <div class="container">
            <h2 class="section-title">Ce que vous trouverez ici</h2>
            <p>Bienvenue sur notre plateforme, un espace pensé pour tous ceux qui veulent analyser, comprendre et anticiper les résultats des matchs avec précision.
                Ici, vous ne trouverez pas de prédictions aléatoires ou de simples intuitions : notre système vous permet de générer des prévisions personnalisées, basées sur les paramètres que vous choisissez.

                Que vous soyez passionné de sport, amateur de statistiques ou simplement curieux, notre outil vous aide à visualiser les tendances d’un match selon différents critères : forme des équipes, performances récentes, historique des confrontations, style de jeu, et bien plus encore.

                Notre objectif est simple :
                vous offrir une manière claire, intuitive et intelligente d’explorer les scénarios possibles d’une rencontre sportive.

                Analysez. Comparez. Prédisez.
                Votre vision du match commence ici.</p>
            <br>

            <?php
            $features = [
                [
                    'icon'  => '🏆',
                    'title' => 'Tournois',
                    'desc'  => 'Suivez l\'arbre complet des tournois professionnels en temps réel, round par round.',
                    'link'  => './pages/ArbreTournoi.php',
                    'cta'   => 'Voir l\'arbre',
                ],
                [
                    'icon'  => '📊',
                    'title' => 'Données',
                    'desc'  => 'Stats détaillées par équipe, joueur et map. Win rates, clutch rates, ratings HLTV.',
                    'link'  => '#',
                    'cta'   => 'Explorer',
                ],
                [
                    'icon'  => '🤖',
                    'title' => 'Prédictions',
                    'desc'  => 'Algorithmes de prédiction basés sur les performances récentes et l\'historique des confrontations.',
                    'link'  => '#',
                    'cta'   => 'Prédire',
                ],
            ];
            ?>

            <div class="features__grid">
                <?php foreach ($features as $i => $f): ?>
                    <article class="card" style="--i: <?= $i ?>">
                        <div class="card__icon"><?= $f['icon'] ?></div>
                        <h3 class="card__title"><?= htmlspecialchars($f['title']) ?></h3>
                        <p class="card__desc"><?= htmlspecialchars($f['desc']) ?></p>
                        <a href="<?= htmlspecialchars($f['link']) ?>" class="card__link">
                            <?= htmlspecialchars($f['cta']) ?> <span>→</span>
                        </a>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- PROCHAINS MATCHS -->
    <section class="upcoming">
        <div class="container">
            <h2 class="section-title">Prochains matchs</h2>

            <?php
            $matches = [
                ['t1' => 'Vitality',  't2' => 'FaZe Clan', 'event' => 'ESL Pro League', 'date' => "Aujourd'hui 19:00"],
                ['t1' => 'NaVi',      't2' => 'G2',         'event' => 'BLAST Premier',  'date' => 'Demain 17:30'],
                ['t1' => 'Spirit',    't2' => 'ENCE',        'event' => 'IEM Cologne',    'date' => '26 Mar 20:00'],
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

    <?php include 'components/footer.php'; ?>

    <script>
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(e => {
                if (e.isIntersecting) e.target.classList.add('is-visible');
            });
        }, {
            threshold: 0.15
        });
        document.querySelectorAll('.card, .upcoming__row').forEach(el => observer.observe(el));
    </script>

</body>

</html>