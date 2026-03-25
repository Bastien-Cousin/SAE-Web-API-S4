<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Joueurs</title>
    <?php require __DIR__ . '/cardPlayer.php'; ?>
    <link rel="stylesheet" href="Joueur.css">
</head>

<body>

    <?php include __DIR__ . '/../components/navbar.php'; ?>

    <div class="page-header">
        <h1>Joueurs</h1>
    </div>

    <?php
    // Données temporaire
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
            'logo' => 'https://cdn.tradeit.gg/team-logos%2Fteam-vitality_200x200.webp',
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