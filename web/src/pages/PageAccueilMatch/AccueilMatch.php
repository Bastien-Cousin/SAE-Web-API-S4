<!DOCTYPE html>
<html lang="fr">

    <head>
        <meta charset="UTF-8">
        <title>CS2 - Accueil Tournoi</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@400;600;700;800;900&family=Barlow:wght@300;400;500&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="../../style.css">
        <link rel="stylesheet" href="../../components/ComponentNavbar/navbar.css">
        <link rel="stylesheet" href="../../components/componentMatchBar/matchBar.css">
        <link rel="stylesheet" href="AccueilMatch.css">
    </head>

    <body>

        <?php include __DIR__ . '/../../components/ComponentNavbar/navbar.php'; ?>

        <form method="GET" class="filters">
            <button type="button" class="filter-btn" onclick="document.getElementById('mapsFilter').style.display='inline-block'">Maps</button>
            <button type="button" class="filter-btn" onclick="document.getElementById('teamFilter').style.display='inline-block'">Équipe</button>
            <button type="button" class="filter-btn" onclick="document.getElementById('scoreFilter').style.display='inline-block'">Score</button>
            <button type="button" class="filter-btn" onclick="document.getElementById('dateFilter').style.display='inline-block'">Date</button>

            <input type="text" name="team" id="teamFilter" placeholder="Nom équipe..." value="<?= htmlspecialchars($_GET['team'] ?? '') ?>" style="display:none;" class="search-bar">
            <select name="maps" id="mapsFilter" style="display:none;">
                <option value="">Toutes les maps</option>
                <option value="Inferno" <?= (($_GET['maps'] ?? '') === 'Inferno') ? 'selected' : '' ?>>Inferno</option>
                <option value="Mirage" <?= (($_GET['maps'] ?? '') === 'Mirage') ? 'selected' : '' ?>>Mirage</option>
                <option value="Nuke" <?= (($_GET['maps'] ?? '') === 'Nuke') ? 'selected' : '' ?>>Nuke</option>
            </select>
            <select name="score" id="scoreFilter" style="display:none;">
                <option value="">Tous les scores</option>
                <option value="2-0" <?= (($_GET['score'] ?? '') === '2-0') ? 'selected' : '' ?>>2 - 0</option>
                <option value="2-1" <?= (($_GET['score'] ?? '') === '2-1') ? 'selected' : '' ?>>2 - 1</option>
                <option value="1-2" <?= (($_GET['score'] ?? '') === '1-2') ? 'selected' : '' ?>>1 - 2</option>
            </select>
            <input type="date" name="date" id="dateFilter" value="<?= $_GET['date'] ?? '' ?>" style="display:none;">

            <button type="submit" class="filter-btn filter-blue">Filtrer</button>
            <button type="submit" class="filter-btn filter-green">Rechercher</button>
        </form>

        <?php include __DIR__ . '/../../components/componentMatchBar/matchBar.php'; ?>

        <?php
        $matches = [
            ['team1_logo' => "/img/LogosEquipes/vitality.png", 'team1_name' => "Team Vitality", 'score' => "2-0", 'team2_name' => "Faze", 'team2_logo' => "/img/LogosEquipes/faze.png", 'date' => "2026-03-24", 'maps' => "Inferno:13-9;Mirage:13-8", 'link' => "/pages/PageAccueilMatch/DescriptionMatch.php?team1=Team Vitality&team2=Faze&score=2-0&maps=Inferno:13-9;Mirage:13-8"],
            ['team1_logo' => "/img/LogosEquipes/mouz.png", 'team1_name' => "Mouz", 'score' => "1-2", 'team2_name' => "Team Spirit", 'team2_logo' => "/img/LogosEquipes/spirit.png", 'date' => "2026-03-23", 'maps' => "Inferno:9-13;Mirage:16-13;Nuke:8-13", 'link' => "/pages/PageAccueilMatch/DescriptionMatch.php?team1=Mouz&team2=Team Spirit&score=1-2&maps=Inferno:9-13;Mirage:16-13;Nuke:8-13"],
            ['team1_logo' => "/img/LogosEquipes/vitality.png", 'team1_name' => "Team Vitality", 'score' => "2-1", 'team2_name' => "Mouz", 'team2_logo' => "/img/LogosEquipes/mouz.png", 'date' => "2026-03-23", 'maps' => "Inferno:13-9;Mirage:10-13;Nuke:13-8", 'link' => "/pages/PageAccueilMatch/DescriptionMatch.php?team1=Team Vitality&team2=Mouz&score=2-1&maps=Inferno:13-9;Mirage:10-13;Nuke:13-8"],
            ['team1_logo' => "/img/LogosEquipes/faze.png", 'team1_name' => "Faze", 'score' => "1-2", 'team2_name' => "Team Spirit", 'team2_logo' => "/img/LogosEquipes/spirit.png", 'date' => "2026-03-23", 'maps' => "Inferno:11-13;Mirage:13-10;Nuke:9-13", 'link' => "/pages/PageAccueilMatch/DescriptionMatch.php?team1=Faze&team2=Team Spirit&score=1-2&maps=Inferno:11-13;Mirage:13-10;Nuke:9-13"]
        ];

        $teamFilter = $_GET['team'] ?? '';
        $mapsFilter = $_GET['maps'] ?? '';
        $scoreFilter = $_GET['score'] ?? '';
        $dateFilter = $_GET['date'] ?? '';

        $filteredMatches = array_filter($matches, function ($match) use ($teamFilter, $mapsFilter, $scoreFilter, $dateFilter) {
            $teamMatch = $teamFilter === '' || stripos($match['team1_name'], $teamFilter) !== false || stripos($match['team2_name'], $teamFilter) !== false;
            $mapsMatch = $mapsFilter === '' || stripos($match['maps'], $mapsFilter) !== false;
            $scoreMatch = $scoreFilter === '' || $match['score'] === $scoreFilter;
            $dateMatch = $dateFilter === '' || $match['date'] === $dateFilter;
            return $teamMatch && $mapsMatch && $scoreMatch && $dateMatch;
        });
        ?>

        <div
            class="matches"><?php
            if (empty($filteredMatches)) {
                echo "<p>Aucun match trouvé.</p>";
            } else {
                foreach ($filteredMatches as $match) {
                    renderMatchBar(
                        $match['team1_logo'],
                        $match['team1_name'],
                        $match['score'],
                        $match['team2_name'],
                        $match['team2_logo'],
                        $match['date'],
                        $match['link']
                    );
                }
            }
            ?>
        </div>

        <script>
            ['maps', 'team', 'score', 'date'].forEach(id => {
const btn = document.querySelector(`button[onclick*="${id}Filter"]`);
const input = document.getElementById(id + 'Filter');
btn ?. addEventListener('click', () => {
input.style.display = 'inline-block';
});
});
        </script>

    </body>
</html>

