<?php
session_start();

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

function getCustomMaps($pdo)
{
    $customMaps = [];
    try {
        $stmt = $pdo->query('SELECT id, name, competition_date, image_url, created_at, updated_at FROM map ORDER BY name');
        $customMaps = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des maps personnalisées : " . $e->getMessage());
    }
    return $customMaps;
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
    $customMaps = getCustomMaps($pdo);
} catch (PDOException $e) {
    error_log("Erreur de connexion à la base de données : " . $e->getMessage());
    $customMaps = [];
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CS - Maps</title>
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

            <?php if (!empty($customMaps)): ?>
                <?php foreach ($customMaps as $map): ?>
                    <?php echo createMapCard(
                        $map['name'],
                        $map['competition_date'],
                        $map['image_url'] ?: 'https://i.postimg.cc/52DJGtkW/image-2026-03-26-151546491.png',
                        0 // Les maps personnalisées n'ont pas encore été jouées
                    ); ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <div class="stats-section">
            <h2 class="stats-title">Statistiques</h2>
            <p class="stats-subtitle">Répartition des maps jouées</p>
            <div class="chart-wrapper">
                <div class="chart-legend">
                    <?php
                    $mapColors = [
                        'Ancient' => '#4a7c4e',
                        'Anubis' => '#c9a84c',
                        'Dust II' => '#c8a96e',
                        'Inferno' => '#c0522a',
                        'Mirage' => '#d4a844',
                        'Nuke' => '#7a9bb5',
                        'Overpass' => '#6b8f5e',
                        'Train' => '#8a7f72',
                        'Vertigo' => '#4a6fa5',
                        'Office' => '#7a9e7e',
                        'Italy' => '#e8b84b',
                    ];
                    $total = array_sum($mapCounts);
                    foreach ($mapCounts as $name => $count) {
                        $pct = $total > 0 ? round(($count / $total) * 100, 1) : 0;
                        $color = $mapColors[$name] ?? '#aaaaaa';
                        echo '<span class="legend-item">
        <span class="legend-dot" style="background:' . $color . '"></span>
        ' . htmlspecialchars($name) . ' &mdash; ' . $pct . '%
    </span>';
                    }
                    ?>
                    <canvas id="mapsChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <?php include '../../components/ComponentFooter/footer.php'; ?>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.js"></script>
    <script>
        const mapData = <?php echo json_encode($mapCounts); ?>;

        const labels = Object.keys(mapData);
        const values = Object.values(mapData);
        const total = values.reduce((a, b) => a + b, 0);

        const mapColors = {
            'Ancient': '#4a7c4e',
            'Anubis': '#c9a84c',
            'Dust II': '#c8a96e',
            'Inferno': '#c0522a',
            'Mirage': '#d4a844',
            'Nuke': '#7a9bb5',
            'Overpass': '#6b8f5e',
            'Train': '#8a7f72',
            'Vertigo': '#4a6fa5',
            'Office': '#7a9e7e',
            'Italy': '#e8b84b',
        };

        const colors = labels.map(l => mapColors[l] ?? '#aaaaaa');

        new Chart(document.getElementById('mapsChart'), {
            type: 'pie',
            data: {
                labels: labels,
                datasets: [{
                    data: values,
                    backgroundColor: colors,
                    borderColor: '#f0f2f6',
                    borderWidth: 3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: (ctx) => {
                                const pct = total > 0 ? ((ctx.parsed / total) * 100).toFixed(1) : '0.0';
                                return ` ${ctx.label} : ${ctx.parsed} parties (${pct}%)`;
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>

</html>