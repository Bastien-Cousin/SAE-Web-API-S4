<?php
require_once '../../components/ComponentCardMaps/cardMaps.php';

function getMapCounts($pdo)
{
    $mapCounts = [
        'Ancient' => 0,
        'Anubis' => 0,
        'Dust II' => 0,
        'Inferno' => 0,
        'Mirage' => 0,
        'Nuke' => 0,
        'Overpass' => 0,
        'Train' => 0,
        'Vertigo' => 0,
        'Office' => 0,
        'Italy' => 0
    ];

    try {
        $stmt = $pdo->query('SELECT match2games FROM "match" WHERE match2games IS NOT NULL');
        $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($matches as $match) {
            $match2games = $match['match2games'];

            if (is_string($match2games)) {
                $decoded = json_decode($match2games, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    continue;
                }
                $match2games = $decoded;
            }

            if (!is_array($match2games)) {
                continue;
            }

            foreach ($match2games as $game) {
                if (!is_array($game)) {
                    continue;
                }

                $mapName = $game['map'] ?? $game['name'] ?? null;

                if ($mapName && isset($mapCounts[$mapName])) {
                    $mapCounts[$mapName]++;
                }
            }
        }
    } catch (PDOException $e) {
        error_log("Erreur lors du comptage des maps : " . $e->getMessage());
    }

    return $mapCounts;
}

$host = getenv('POSTGRES_HOST') ?: 'db';
$port = getenv('POSTGRES_PORT') ?: '5432';
$dbname = getenv('POSTGRES_DB') ?: 'sae_db';
$user = getenv('POSTGRES_USER') ?: 'sae_user';
$password = getenv('POSTGRES_PASSWORD') ?: 'sae_password';

$mapCounts = [
    'Ancient' => 0,
    'Anubis' => 0,
    'Dust II' => 0,
    'Inferno' => 0,
    'Mirage' => 0,
    'Nuke' => 0,
    'Overpass' => 0,
    'Train' => 0,
    'Vertigo' => 0,
    'Office' => 0,
    'Italy' => 0
];

try {
    $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $mapCounts = getMapCounts($pdo);
} catch (PDOException $e) {
    error_log("Erreur de connexion à la base de données : " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ma Page avec les Cartes</title>
    <link rel="stylesheet" href="../../components/ComponentNavbar/navbar.css">
    <link rel="stylesheet" href="../../components/ComponentCardMaps/cardMaps.css">
    <link rel="stylesheet" href="../../components/ComponentFooter/footer.css">
    <link rel="stylesheet" href="Map.css">
</head>

<body>
    <?php include '../../components/ComponentNavbar/navbar.php'; ?>

    <div class="main-content">
        <div class="header-section">
            <h1>Maps CS2</h1>
            <a href="FormulaireMap.php" class="btn-add-map">+ Nouvelle Map</a>
        </div>

        <div class="container">
            <?php echo createMapCard(
                'Ancient',
                '3 décembre 2020',
                'https://i.postimg.cc/VsHhCJMs/800px-CS2-de-ancient.png',
                $mapCounts['Ancient']
            ); ?>
            <?php echo createMapCard(
                'Anubis',
                '31 mars 2020',
                'https://i.postimg.cc/rwN2w9f9/800px-CS2-de-anubis.png',
                $mapCounts['Anubis']
            ); ?>
            <?php echo createMapCard(
                'Dust II',
                'mars 2001',
                'https://i.postimg.cc/1X8ygN8D/800px-CS2-Dust-2-A-Site.jpg',
                $mapCounts['Dust II']
            ); ?>
            <?php echo createMapCard(
                'Inferno',
                '2001',
                'https://i.postimg.cc/5tt9cGfS/800px-CS2-de-inferno.png',
                $mapCounts['Inferno']
            ); ?>
            <?php echo createMapCard(
                'Mirage',
                '12 juin 2013',
                'https://i.postimg.cc/MKG9QmZs/800px-CS2-de-mirage.png',
                $mapCounts['Mirage']
            ); ?>
            <?php echo createMapCard(
                'Nuke',
                'novembre 1999',
                'https://i.postimg.cc/NM4xy53Q/800px-CS2-de-nuke.png',
                $mapCounts['Nuke']
            ); ?>
            <?php echo createMapCard(
                'Overpass',
                '18 décembre 2013',
                'https://i.postimg.cc/yxRcmx58/800px-CS2-de-overpass.png',
                $mapCounts['Overpass']
            ); ?>
            <?php echo createMapCard(
                'Train',
                '1999',
                'https://i.postimg.cc/zBgVMMkH/800px-CS2-de-train.png',
                $mapCounts['Train']
            ); ?>
            <?php echo createMapCard(
                'Vertigo',
                '1999',
                'https://i.postimg.cc/YS49GVTt/800px-CS2-de-vertigo.png',
                $mapCounts['Vertigo']
            ); ?>
            <?php echo createMapCard(
                'Office ',
                '2000',
                'https://i.postimg.cc/52DJGtkW/image-2026-03-26-151546491.png',
                $mapCounts['Office']
            ); ?>
            <?php echo createMapCard(
                'Italy',
                '8 juin 2000',
                'https://i.postimg.cc/q7ffw5gZ/800px-CS2-Italy-CT-Spawn.jpg',
                $mapCounts['Italy']
            ); ?>

        </div>
    </div>

    <?php include '../../components/ComponentFooter/footer.php'; ?>
</body>

</html>