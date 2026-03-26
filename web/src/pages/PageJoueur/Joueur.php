<?php
include __DIR__ . '/../../components/ComponentCardPlayer/cardPlayer.php';

$host = getenv('POSTGRES_HOST') ?: 'db';
$port = getenv('POSTGRES_PORT') ?: '5432';
$dbname = getenv('POSTGRES_DB') ?: 'sae_db';
$user = getenv('POSTGRES_USER') ?: 'sae_user';
$password = getenv('POSTGRES_PASSWORD') ?: 'sae_password';

try {
    $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("❌ Erreur de connexion à la base de données : " . $e->getMessage());
}

$search = $_GET['search'] ?? '';
$region = $_GET['region'] ?? '';

$stmtRegions = $pdo->query("SELECT DISTINCT region FROM player ORDER BY region ASC");
$regions = $stmtRegions->fetchAll(PDO::FETCH_COLUMN);

$sql = "SELECT pageid, objectname, name, birthdate, status, type, earnings, nationality, region
        FROM player
        WHERE 1=1";

$params = [];
if ($search !== '') {
    $sql .= " AND (name ILIKE :search OR objectname ILIKE :search)";
    $params[':search'] = "%$search%";
}
if ($region !== '') {
    $sql .= " AND region = :region";
    $params[':region'] = $region;
}

$sql .= " ORDER BY name ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$playersData = $stmt->fetchAll(PDO::FETCH_ASSOC);

$joueurs = array_map(function ($player) {
    $pseudo = $player['objectname'] ?? 'N/A';
    if (strpos($pseudo, '_') !== false) {
        $parts = explode('_', $pseudo);
        $pseudo = end($parts);
    }

    return [
        'pays' => $player['nationality'] ?? 'N/A',
        'pseudo' => $pseudo,
        'nom_prenom' => $player['name'] ?? 'N/A',
        'age' => $player['birthdate'] ? date('Y') - date('Y', strtotime($player['birthdate'])) : 'N/A',
        'statut' => $player['status'] ?? 'Actif',
        'role' => $player['type'] ?? 'N/A',
        'argent_gagne' => isset($player['earnings']) ? number_format($player['earnings']) . ' €' : 'N/A',
        'logo' => '',
        'couleur_logo' => '#cccccc',
    ];
}, $playersData);
?>
<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="UTF-8">
        <title>Joueurs</title>
        <link rel="stylesheet" href="../../style.css">
        <link rel="stylesheet" href="Joueur.css">
        <link rel="stylesheet" href="../../components/ComponentNavbar/navbar.css">
        <link rel="stylesheet" href="../../components/ComponentCardPlayer/cardPlayer.css">
    </head>
    <body>

    <?php include __DIR__ . '/../../components/ComponentCardPlayer/cardPlayer.php'; ?>

        <div class="page-header">
            <h1>Joueurs</h1>
        </div>
    </div>

        <form method="GET" class="filters">
            <input type="text" name="search" placeholder="Rechercher un joueur..." class="search-bar" value="<?= htmlspecialchars($search) ?>">

            <select name="region" class="filter-btn">
                <option value="">Toutes les régions / zones</option>
                <?php foreach ($regions as $regionOption): ?>
                    <option
                        value="<?= htmlspecialchars($regionOption) ?>" <?= ($region === $regionOption) ? 'selected' : '' ?>><?= htmlspecialchars(strtoupper($regionOption)) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit" class="filter-btn filter-blue">Filtrer</button>
            <button type="submit" class="filter-btn filter-green">Rechercher</button>
        </form>

        <div
            class="cards-grid">
            <?php if (empty($joueurs)): ?>
                <p>Aucun joueur trouvé.</p>
            <?php else: ?>
            <?php foreach ($joueurs as $joueur): ?>
                    <?php afficherCarteJoueur($joueur); ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </body>
</html>

</html>