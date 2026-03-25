<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CS2 - Projet</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@400;600;700;800;900&family=Barlow:wght@300;400;500&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>

<body>

    <?php include 'components/navbar.php'; ?>

    <!-- HERO -->
    <section class="hero">
        <div class="container hero__content">
            <h1 class="hero__title">Bienvenue sur CS2 - Projet</h1>
            <p class="hero__subtitle">Votre source ultime pour les données, analyses et prédictions du monde compétitif
                de Counter-Strike 2.</p>
            <br>
            <a href="#features" class="btn btn--primary">Découvrir</a>
        </div>

        <div class="hero__scroll-hint">
            <span></span>
            <p>Scroll</p>
        </div>
    </section>

    <div class="statsband">
        <div class="statsband__inner">
            <?php
            $stats = [
                ['value' => '1.2M', 'label' => 'Joueurs en ligne'],
                ['value' => '32', 'label' => 'Maps actives'],
                ['value' => '128', 'label' => 'Tick rate'],
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

    <!-- PROCHAINS MATCHS -->
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