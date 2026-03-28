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

        <div class="tournament-filters">
            <label for="year-filter">Année:</label>
            <select id="year-filter">
                <option value="">Toutes les années</option>
            </select>
        </div>

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

            // Récupérer les années disponibles
            $yearsStmt = $pdo->prepare("
                SELECT DISTINCT EXTRACT(YEAR FROM startdate) as year 
                FROM tournament 
                WHERE startdate IS NOT NULL 
                ORDER BY year DESC
            ");
            $yearsStmt->execute();
            $years = $yearsStmt->fetchAll(PDO::FETCH_ASSOC);

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
                    $year = $tournament['startdate'] ? date('Y', strtotime($tournament['startdate'])) : '';
                    
                    // Déterminer l'image à afficher
                    $image = '/img/ImagesDivers/capsule_616x353.jpg'; // fallback par défaut
                    
                    if (!empty($tournament['bannerurl'])) {
                        $image = $tournament['bannerurl'];
                    } elseif (!empty($tournament['iconurl'])) {
                        $image = $tournament['iconurl'];
                    }

                    echo "<div class='tournament-item' data-year='$year'>";
                    renderTournament($image, $name, $prizepool, $link);
                    echo "</div>";
                }
            }
        } catch (Exception $e) {
            echo '<p class="error">Erreur lors du chargement des tournois: ' . htmlspecialchars($e->getMessage()) . '</p>';
            error_log("Error loading tournaments: " . $e->getMessage());
        }
        ?>
        </div>
    </section>

    <script>
        // Remplir le sélecteur d'années
        const years = <?php echo json_encode(array_column($years, 'year')); ?>;
        const yearFilter = document.getElementById('year-filter');
        
        years.forEach(year => {
            const option = document.createElement('option');
            option.value = Math.floor(year);
            option.textContent = Math.floor(year);
            yearFilter.appendChild(option);
        });

        // Filtrer les tournois par année
        yearFilter.addEventListener('change', function() {
            const selectedYear = this.value;
            const items = document.querySelectorAll('.tournament-item');
            
            items.forEach(item => {
                if (selectedYear === '' || item.dataset.year === selectedYear) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    </script>

</body>

</html>