<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>CS2 - Accueil Tournoi</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <?php include 'components/tournament-card.php'; ?>

    <section class="tournaments">
        <h2>Tournois</h2>

        <div class="cards">

            <?php
            renderTournament("img/major.jpg", "CS2 Major", "$1,000,000", "ArbreTournoi.php");
            renderTournament("img/blast.jpg", "BLAST Premier", "$425,000", "ArbreTournoi.php");
            renderTournament("img/iem.jpg", "IEM Katowice", "$1,000,000", "ArbreTournoi.php");
            ?>

        </div>
    </section>
</body>


</html>