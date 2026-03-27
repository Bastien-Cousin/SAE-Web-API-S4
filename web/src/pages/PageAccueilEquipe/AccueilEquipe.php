<?php
// Récupérer les paramètres de filtrage
$search = $_GET['search'] ?? '';
$country = $_GET['country'] ?? '';
$sort = $_GET['sort'] ?? 'name_asc';

// Connexion à la base de données
$host = getenv('POSTGRES_HOST') ?: 'db';
$port = getenv('POSTGRES_PORT') ?: '5432';
$dbname = getenv('POSTGRES_DB') ?: 'sae_db';
$user = getenv('POSTGRES_USER') ?: 'sae_user';
$password = getenv('POSTGRES_PASSWORD') ?: 'sae_password';

$teams = [];
$regions = [];

try {
    $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Récupérer toutes les équipes
    $stmt = $pdo->prepare("SELECT * FROM team ORDER BY name ASC");
    $stmt->execute();
    $allTeams = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Extraire les régions uniques
    $regionsSet = array_unique(array_column($allTeams, 'region'));
    $regions = array_values(array_filter($regionsSet));
    sort($regions);
    
    // Filtrer les équipes
    $teams = array_filter($allTeams, function ($team) use ($search, $country) {
        if (!empty($search) && stripos($team['name'] ?? '', $search) === false) {
            return false;
        }
        if (!empty($country) && ($team['region'] ?? '') !== $country) {
            return false;
        }
        return true;
    });
    
    // Trier les équipes selon le paramètre
    usort($teams, function ($a, $b) use ($sort) {
        switch ($sort) {
            case 'name_asc':
                return strcasecmp($a['name'] ?? '', $b['name'] ?? '');
            case 'name_desc':
                return strcasecmp($b['name'] ?? '', $a['name'] ?? '');
            default:
                return strcasecmp($a['name'] ?? '', $b['name'] ?? '');
        }
    });
} catch (PDOException $e) {
    die("❌ Erreur de connexion à la base de données : " . htmlspecialchars($e->getMessage()));
}

function renderTeamCard($team)
{
    $logo = $team['logourl'] ?? $team['textlesslogourl'] ?? '';

    if (empty($logo)) {
        $logo = "../../img/ImagesDivers/placeholder.jpg";
    }

    $name = htmlspecialchars($team['name'] ?? 'Unknown');
    $slug = htmlspecialchars($team['objectname'] ?? '');
    $logo = htmlspecialchars($logo);

    echo <<<HTML
    <a href="DescriptionEquipe.php?team=$slug" class="team-card">
        <img src="$logo" alt="$name">
        <span>$name</span>
    </a>
    HTML;
}
?>

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

    <div class="header-section">
        <h1>Equipe CS2</h1>
        <a href="FormulaireEquipe.php" class="btn-add-map">+ Nouvelle Equipe</a>
    </div>

    <form method="GET" class="filters">
        <input type="text" name="search" placeholder="Rechercher une équipe..." class="search-bar"
            value="<?= htmlspecialchars($search) ?>">

        <select name="country" class="filter-btn">
            <option value="">Toutes les régions / zones</option>
            <?php foreach ($regions as $regionOption): ?>
                <option value="<?= htmlspecialchars($regionOption) ?>" <?= ($country === $regionOption) ? 'selected' : '' ?>>
                    <?= htmlspecialchars(strtoupper($regionOption)) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select name="sort" class="filter-btn">
            <option value="name_asc" <?= $sort === 'name_asc' ? 'selected' : '' ?>>Nom A → Z</option>
            <option value="name_desc" <?= $sort === 'name_desc' ? 'selected' : '' ?>>Nom Z → A</option>
        </select>

        <button type="submit" class="filter-btn filter-blue">Appliquer</button>
    </form>

    <div class="teams"><?php
    if (empty($teams)) {
        echo "<p>Aucune équipe trouvée.</p>";
    } else {
        foreach ($teams as $team) {
            renderTeamCard($team);
        }
    }
    ?>
    </div>

</body>

</html>