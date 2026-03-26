<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">

    <link rel="stylesheet" href="../../style.css">
    <link rel="stylesheet" href="../../components/ComponentNavbar/navbar.css">
    <link rel="stylesheet" href="../../components/ComponentCardTeam/cardTeam.css">
    <link rel="stylesheet" href="AccueilEquipe.css">
</head>

<body>

    <?php include __DIR__ . '/../../components/ComponentNavbar/navbar.php'; ?>

    <?php include __DIR__ . '/../../components/ComponentCardTeam/cardTeam.php'; ?>

    <div class="header-section">
        <h1>Equipes CS2</h1>
        <a href="FormulaireEquipe.php" class="btn-add-map">+ Nouvelle Equipe</a>
    </div>

    <form method="GET" class="filters"> <input type="text" name="search" placeholder="Rechercher une équipe..."
            class="search-bar" value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">

        <select name="country" class="filter-btn">
            <option value="">Tous les pays</option>
            <option value="fr" <?= (($_GET['country'] ?? '') === 'fr') ? 'selected' : '' ?>>France</option>
            <option value="eu" <?= (($_GET['country'] ?? '') === 'eu') ? 'selected' : '' ?>>Europe</option>
            <option value="ru" <?= (($_GET['country'] ?? '') === 'ru') ? 'selected' : '' ?>>Russie</option>
        </select>

        <button type="submit" class="filter-btn filter-blue">Filtrer</button>

        <button class="filter-btn filter-green" type="submit">Rechercher</button>
    </form>

    <div class="teams"><?php

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