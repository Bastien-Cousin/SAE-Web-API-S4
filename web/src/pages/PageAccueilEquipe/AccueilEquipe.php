<!DOCTYPE html>
<html lang="fr">

    <head>
        <meta charset="UTF-8">
        <title>CS2 - Équipes</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@400;600;700;800;900&family=Barlow:wght@300;400;500&display=swap" rel="stylesheet">

        <link rel="stylesheet" href="../../style.css">
        <link rel="stylesheet" href="../../components/ComponentNavbar/navbar.css">
        <link rel="stylesheet" href="../../components/ComponentCardTeam/cardTeam.css">
        <link rel="stylesheet" href="AccueilEquipe.css">
    </head>

    <body>

        <?php include __DIR__ . '/../../components/ComponentNavbar/navbar.php'; ?>

        <?php include __DIR__ . '/../../components/ComponentCardTeam/cardTeam.php'; ?>

            <form method="GET" class="filters"> <input type="text" name="search" placeholder="Rechercher une équipe..." class="search-bar" value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">

            <select name="country" class="filter-btn">
                <option value="">Tous les pays</option>
                <option value="fr" <?= (($_GET['country'] ?? '') === 'fr') ? 'selected' : '' ?>>France</option>
                <option value="eu" <?= (($_GET['country'] ?? '') === 'eu') ? 'selected' : '' ?>>Europe</option>
                <option value="ru" <?= (($_GET['country'] ?? '') === 'ru') ? 'selected' : '' ?>>Russie</option>
            </select>

            <button type="submit" class="filter-btn filter-blue">Filtrer</button>

            <button class="filter-btn filter-green" type="submit">Rechercher</button>
        </form>

        <div
            class="teams"><?php

            $search = $_GET['search'] ?? '';
            $country = $_GET['country'] ?? '';

            $teams = [
                ["img" => "/img/LogosEquipes/vitality.png", "name" => "Team Vitality", "slug" => "vitality", "country" => "fr"],
                ["img" => "/img/LogosEquipes/faze.png", "name" => "FaZe", "slug" => "faze", "country" => "eu"],
                ["img" => "/img/LogosEquipes/mouz.png", "name" => "Mouz", "slug" => "mouz", "country" => "eu"],
                ["img" => "/img/LogosEquipes/spirit.png", "name" => "Team Spirit", "slug" => "spirit", "country" => "ru"],
            ];

            $found = false;

            foreach ($teams as $team) {

                $matchSearch = ($search === '' || stripos($team['name'], $search) !== false);
                $matchCountry = ($country === '' || $team['country'] === $country);

                if ($matchSearch && $matchCountry) {
                    renderTeamCard($team['img'], $team['name'], $team['slug']);
                    $found = true;
                }
            }

            if (!$found) {
                echo "<p>Aucune équipe trouvée.</p>";
            }

            ?>
        </div>

    </body>

</html>

