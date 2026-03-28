<?php session_start(); ?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>CS2 - Tournois</title>

    <link rel="stylesheet" href="../../style.css">
    <link rel="stylesheet" href="../../components/ComponentNavbar/navbar.css">
    <link rel="stylesheet" href="../../components/ComponentCardTournament/cardTournament.css">
    <link rel="stylesheet" href="AccueilTournoi.css">
</head>

<body>

    <?php include __DIR__ . '/../../components/ComponentNavbar/navbar.php'; ?>

    <?php include __DIR__ . '/../../components/ComponentCardTournament/cardTournament.php'; ?>

    <section class="tournaments">
        <h2>Tournois</h2>

        <div class="cards"><?php
        try {
            $pdo = new PDO(
                "pgsql:host=" . (getenv('POSTGRES_HOST') ?: 'db') .
                ";port=" . (getenv('POSTGRES_PORT') ?: '5432') .
                ";dbname=" . (getenv('POSTGRES_DB') ?: 'sae_db'),
                getenv('POSTGRES_USER') ?: 'sae_user',
                getenv('POSTGRES_PASSWORD') ?: 'sae_password',
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );

            // Récupérer les tournois depuis la base de données
            $stmt = $pdo->prepare("
                SELECT pageid, name, prizepool, bannerurl, iconurl, startdate 
                FROM tournament 
                WHERE name IS NOT NULL 
                ORDER BY startdate DESC 
            ");
            $stmt->execute();
            $tournaments = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($tournaments)) {
                echo '<p class="no-data">Aucun tournoi disponible pour le moment.</p>';
            } else {
                foreach ($tournaments as $tournament) {
                    $name = htmlspecialchars($tournament['name'] ?? 'Tournoi sans nom');
                    $prizepool = $tournament['prizepool'] ? '$' . number_format($tournament['prizepool'], 0, '.', ',') : 'N/A';
                    $link = "/pages/PageAccueilTournoi/ArbreTournoi.php";
                    
                    // Déterminer l'image à afficher
                    $image = '/img/ImagesDivers/capsule_616x353.jpg'; // fallback par défaut
                    
                    if (!empty($tournament['bannerurl'])) {
                        $image = $tournament['bannerurl'];
                    } elseif (!empty($tournament['iconurl'])) {
                        $image = $tournament['iconurl'];
                    }

                    renderTournament($image, $name, $prizepool, $link);
                }
            }
        } catch (Exception $e) {
            echo '<p class="error">Erreur lors du chargement des tournois: ' . htmlspecialchars($e->getMessage()) . '</p>';
            error_log("Error loading tournaments: " . $e->getMessage());
        }
        ?>
        </div>
    </section>

</body>

</html>