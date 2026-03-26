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

$limit = 50;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$stmtRegions = $pdo->query("SELECT DISTINCT region FROM player ORDER BY region ASC");
$regions = $stmtRegions->fetchAll(PDO::FETCH_COLUMN);

$sql = "SELECT pageid, objectname, name, birthdate, status, type, earnings, nationality, region 
        FROM player WHERE 1=1";

$params = [];

if ($search !== '') {
    $sql .= " AND (name ILIKE :search OR objectname ILIKE :search)";
    $params[':search'] = "%$search%";
}

if ($region !== '') {
    $sql .= " AND region = :region";
    $params[':region'] = $region;
}

$sql .= " ORDER BY name ASC LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($sql);

foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}

$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

$stmt->execute();

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

<?php include __DIR__ . '/../../components/ComponentNavbar/navbar.php'; ?>

<div class="page-header">
    <h1>Joueurs</h1>
</div>

<form method="GET" class="filters">
    <input type="text" name="search" placeholder="Rechercher..." value="<?= htmlspecialchars($search) ?>">
    
    <select name="region">
        <option value="">Toutes les régions</option>
        <?php foreach ($regions as $regionOption): ?>
            <option value="<?= htmlspecialchars($regionOption) ?>" <?= ($region === $regionOption) ? 'selected' : '' ?>>
                <?= htmlspecialchars($regionOption) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <button type="submit">Filtrer</button>
</form>

<div class="cards-grid" id="cards-container">
    <?php foreach ($joueurs as $joueur): ?>
        <?php afficherCarteJoueur($joueur); ?>
    <?php endforeach; ?>
</div>

<script>
let page = 1;
let loading = false;

const container = document.getElementById('cards-container');

function animateCards() {
    const cards = document.querySelectorAll('.player-card:not(.visible)');
    const trigger = window.innerHeight;

    cards.forEach(card => {
        const rect = card.getBoundingClientRect();
        if (rect.top < trigger - 50) {
            card.classList.add('visible');
        }
    });
}

async function loadMorePlayers() {
    if (loading) return;
    loading = true;
    page++;

    const params = new URLSearchParams(window.location.search);
    params.set('page', page);

    const res = await fetch('load_players.php?' + params.toString());
    const html = await res.text();

    if (html.trim() === "") {
        window.removeEventListener('scroll', onScroll);
        return;
    }

    container.insertAdjacentHTML('beforeend', html);
    animateCards();

    loading = false;
}

function onScroll() {
    if (window.innerHeight + window.scrollY >= document.body.offsetHeight - 200) {
        loadMorePlayers();
    }
}

window.addEventListener('scroll', onScroll);
window.addEventListener('scroll', animateCards);
window.addEventListener('load', animateCards);
</script>

</body>
</html>