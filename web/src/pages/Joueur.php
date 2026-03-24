<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Joueurs</title>
    <?php require __DIR__ . '/cardPlayer.php'; ?>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: sans-serif;
            background-color: #f0f2f5;
            min-height: 100vh;
        }

        .page-header {
            text-align: center;
            padding: 40px 20px 20px;
        }

        .page-header h1 {
            font-size: 2rem;
            color: #1b265c;
            letter-spacing: 2px;
            text-transform: uppercase;
            border-bottom: 3px solid #fca311;
            display: inline-block;
            padding-bottom: 8px;
        }

        .cards-grid {
            display: grid;
            grid-template-columns: repeat(5, calc(20% - 16px));
            gap: 20px;
            justify-content: center;
            padding: 30px 40px 60px;
        }
    </style>
</head>

<body>

    <?php include __DIR__ . '/../components/navbar.php'; ?>

    <div class="page-header">
        <h1>Joueurs</h1>
    </div>

    <?php
    // DOnnées temporaire
    $joueurs = [
        [
            'pays' => 'ua',
            'pseudo' => 's1mple',
            'nom_prenom' => 'Oleksandr Kostyliev',
            'age' => 26,
            'statut' => 'Actif',
            'role' => 'Sniper',
            'argent_gagne' => '1 200 000 €',
            'logo' => '',
            'couleur_logo' => '#f0c040',
        ],
        [
            'pays' => 'fr',
            'pseudo' => 'ZywOo',
            'nom_prenom' => 'Mathieu Herbaut',
            'age' => 23,
            'statut' => 'Actif',
            'role' => 'Sniper',
            'argent_gagne' => '900 000 €',
            'logo' => '',
            'couleur_logo' => '#40c0f0',
        ],
        [
            'pays' => 'dk',
            'pseudo' => 'dev1ce',
            'nom_prenom' => 'Nicolai Reedtz',
            'age' => 28,
            'statut' => 'Inactif',
            'role' => 'Sniper',
            'argent_gagne' => '850 000 €',
            'logo' => '',
            'couleur_logo' => '#e05050',
        ],
        [
            'pays' => 'au',
            'pseudo' => 'Twistzz',
            'nom_prenom' => 'Russel Van Dulken',
            'age' => 24,
            'statut' => 'Actif',
            'role' => 'Rifler',
            'argent_gagne' => '750 000 €',
            'logo' => '',
            'couleur_logo' => '#a040f0',
        ],
        [
            'pays' => 'br',
            'pseudo' => 'FalleN',
            'nom_prenom' => 'Gabriel Toledo',
            'age' => 32,
            'statut' => 'Actif',
            'role' => 'IGL',
            'argent_gagne' => '600 000 €',
            'logo' => '',
            'couleur_logo' => '#40f080',
        ],
        [
            'pays' => 'se',
            'pseudo' => 'f0rest',
            'nom_prenom' => 'Patrik Lindberg',
            'age' => 34,
            'statut' => 'Retraité',
            'role' => 'Rifler',
            'argent_gagne' => '500 000 €',
            'logo' => '',
            'couleur_logo' => '#f06040',
        ],
    ];
    ?>

    <div class="cards-grid">
        <?php foreach ($joueurs as $joueur): ?>
            <?php afficherCarteJoueur($joueur); ?>
        <?php endforeach; ?>
    </div>

</body>

</html>