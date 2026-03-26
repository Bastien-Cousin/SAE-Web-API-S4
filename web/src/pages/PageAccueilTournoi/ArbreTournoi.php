<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Arbre Tournoi</title>

    <link rel="stylesheet" href="../../style.css">
    <link rel="stylesheet" href="../../components/ComponentNavbar/navbar.css">
    <link rel="stylesheet" href="../../components/ComponentArbre/arbre.css">
    <link rel="stylesheet" href="../../pages/PageAccueilTournoi/ArbreTournoi.css">
</head>

<body><?php include __DIR__ . '/../../components/ComponentNavbar/navbar.php'; ?>

    <a href="javascript:history.back()" class="btn-back">
        <span class="arrow">←</span> Retour
    </a>

    <?php
    require __DIR__ . '/../../components/ComponentArbre/arbre.php';

    $rounds = [
        [
            'name' => 'Quarts',
            'matches' => [
                [
                    'home' => ['name' => 'Vitality', 'score' => 2],
                    'away' => ['name' => 'NaVi', 'score' => 0],
                    'played' => true,
                ],
                [
                    'home' => ['name' => 'ENCE', 'score' => 2],
                    'away' => ['name' => 'FaZe', 'score' => 1],
                    'played' => true,
                ],
                [
                    'home' => ['name' => 'G2', 'score' => 0],
                    'away' => ['name' => 'Spirit', 'score' => 2],
                    'played' => true,
                ],
                [
                    'home' => ['name' => 'MOUZ', 'score' => 1],
                    'away' => ['name' => 'Heroic', 'score' => 2],
                    'played' => true,
                ],
            ]
        ],
        [
            'name' => 'Demi',
            'matches' => [
                [
                    'home' => ['name' => 'Vitality', 'score' => 2],
                    'away' => ['name' => 'ENCE', 'score' => 0],
                    'played' => true,
                ],
                [
                    'home' => ['name' => 'Spirit', 'score' => 2],
                    'away' => ['name' => 'Heroic', 'score' => 1],
                    'played' => true,
                ],
            ]
        ],
        [
            'name' => 'Finale',
            'matches' => [
                [
                    'home' => ['name' => 'Vitality', 'score' => 2],
                    'away' => ['name' => 'Spirit', 'score' => 1],
                    'played' => true,
                ],
            ]
        ]
    ];

    $thirdPlace = null;

    renderBracket('Nom du tournoi', $rounds, $thirdPlace);
    ?>

</body>

</html>