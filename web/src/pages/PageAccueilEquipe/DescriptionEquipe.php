<?php
include __DIR__ . '/../../components/ComponentCardPlayer/cardPlayer.php';

$equipes = [
    'vitality' => [
        'nom' => 'Team Vitality',
        'logo' => '/img/LogosEquipes/vitality.png',
        'ceo' => 'Fabien "Neo" Devide',
        'creation' => '2013',
        'argent' => '5 000 000 €',
    ],
    'faze' => [
        'nom' => 'FaZe',
        'logo' => '/img/LogosEquipes/faze.png',
        'ceo' => 'Lee Trink',
        'creation' => '2016',
        'argent' => '7 500 000 €',
    ],
    'mouz' => [
        'nom' => 'MOUZ',
        'logo' => '/img/LogosEquipes/mouz.png',
        'ceo' => 'Ádám "Freddy" Pusztai',
        'creation' => '2002',
        'argent' => '3 200 000 €',
    ],
    'spirit' => [
        'nom' => 'Team Spirit',
        'logo' => '/img/LogosEquipes/spirit.png',
        'ceo' => 'Nikolay Petrossian',
        'creation' => '2015',
        'argent' => '2 800 000 €',
    ],
];

$joueurs = [
    [
        'equipe' => 'vitality',
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
        'equipe' => 'faze',
        'pays' => 'dk',
        'pseudo' => 'rain',
        'nom_prenom' => 'Håvard Nygaard',
        'age' => 29,
        'statut' => 'Actif',
        'role' => 'Rifler',
        'argent_gagne' => '1 000 000 €',
        'logo' => '',
        'couleur_logo' => '#e05050',
    ],
    [
        'equipe' => 'spirit',
        'pays' => 'ru',
        'pseudo' => 'donk',
        'nom_prenom' => 'Danil Kryshkovets',
        'age' => 17,
        'statut' => 'Actif',
        'role' => 'Rifler',
        'argent_gagne' => '300 000 €',
        'logo' => '',
        'couleur_logo' => '#a040f0',
    ],
];

$teamKey = $_GET['team'] ?? null;

if (!$teamKey || !isset($equipes[$teamKey])) {
    die("Équipe non trouvée");
}

$equipe = $equipes[$teamKey];

$joueursEquipe = array_filter($joueurs, function ($joueur) use ($teamKey) {
    return $joueur['equipe'] === $teamKey;
});
?>

<!DOCTYPE html>
<html lang="fr">

    <head>
        <meta charset="UTF-8">
        <title><?php echo $equipe['nom']; ?></title>

        <link rel="stylesheet" href="../../style.css">
        <link rel="stylesheet" href="../../components/ComponentNavbar/navbar.css">
        <link rel="stylesheet" href="../../components/ComponentCardPlayer/cardPlayer.css">
        <link rel="stylesheet" href="DescriptionEquipe.css">
    </head>

    <body>

        <?php include __DIR__ . '/../../components/ComponentNavbar/navbar.php'; ?>

        <div class="team-header">
            <div class="team-left">
                <img src="<?php echo $equipe['logo']; ?>" class="team-logo">
                <h1><?php echo $equipe['nom']; ?></h1>
            </div>

            <div class="team-info">

                <div class="info-left">
                    <p>
                        <strong>CEO :</strong>
                        <?php echo $equipe['ceo']; ?>
                    </p>
                    <p>
                        <strong>Création :</strong>
                        <?php echo $equipe['creation']; ?>
                    </p>
                </div>

                <div class="info-right">
                    <p>
                        <strong>Argent gagné :</strong>
                    </p>
                    <p class="money"><?php echo $equipe['argent']; ?></p>
                </div>

            </div>

        </div>

        <div class="players-section">
            <h2>Joueurs</h2>

            <div class="cards-grid"><?php foreach ($joueursEquipe as $joueur): ?>
                    <?php afficherCarteJoueur($joueur); ?>
                <?php endforeach; ?>
            </div>

        </div>

    </body>
</html>

