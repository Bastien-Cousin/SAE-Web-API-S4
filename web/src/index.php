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
            <h1 class="hero__title">Bienvenue sur CS2 - Projet</h1>
            <p class="hero__subtitle">
                Votre source ultime pour les données, analyses et prédictions du monde compétitif de Counter-Strike 2.
            </p>
        </div>
    </section>

    <div class="statsband">
        <div class="statsband__inner">
            <?php
            $stats = [
                ['value' => 'XX', 'label' => 'Joueurs'],
                ['value' => 'XX', 'label' => 'Equipes'],
                ['value' => 'XX', 'label' => 'Maps'],
                ['value' => 'XX', 'label' => 'Tournois'],
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