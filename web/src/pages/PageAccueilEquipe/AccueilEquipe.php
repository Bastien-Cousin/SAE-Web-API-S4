<?php
session_start();

include __DIR__ . '/../../components/ComponentCardTeam/cardTeam.php';

$search  = $_GET['search']  ?? '';
$country = $_GET['country'] ?? '';
$sort    = $_GET['sort']    ?? 'name_asc';

$host     = getenv('POSTGRES_HOST')     ?: 'db';
$port     = getenv('POSTGRES_PORT')     ?: '5432';
$dbname   = getenv('POSTGRES_DB')       ?: 'sae_db';
$user     = getenv('POSTGRES_USER')     ?: 'sae_user';
$password = getenv('POSTGRES_PASSWORD') ?: 'sae_password';

$teams   = [];
$regions = [];

$limit  = 30;
$offset = 0;

try {
    $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Régions pour le filtre
    $stmtRegions = $pdo->query("SELECT DISTINCT region FROM team WHERE region IS NOT NULL ORDER BY region ASC");
    $regions = $stmtRegions->fetchAll(PDO::FETCH_COLUMN);

    // Requête avec filtres dans le SQL
    $orderDir = ($sort === 'name_desc') ? 'DESC' : 'ASC';

    $sql = "SELECT * FROM team WHERE 1=1";
    $params = [];

    if ($search !== '') {
        $sql .= " AND name ILIKE :search";
        $params[':search'] = "%$search%";
    }

    if ($country !== '') {
        $sql .= " AND region = :country";
        $params[':country'] = $country;
    }

    $sql .= " ORDER BY name $orderDir LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($sql);

    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $teams = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("❌ Erreur de connexion à la base de données : " . htmlspecialchars($e->getMessage()));
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
            <option value="name_asc"  <?= $sort === 'name_asc'  ? 'selected' : '' ?>>Nom A → Z</option>
            <option value="name_desc" <?= $sort === 'name_desc' ? 'selected' : '' ?>>Nom Z → A</option>
        </select>

        <button type="submit" class="filter-btn filter-blue">Appliquer</button>
    </form>

    <div class="teams" id="teams-container">
        <?php if (empty($teams)): ?>
            <p>Aucune équipe trouvée.</p>
        <?php else: ?>
            <?php foreach ($teams as $team): ?>
                <?php
                $logo = $team['logourl'] ?? $team['textlesslogourl'] ?? '';
                if (empty($logo)) $logo = "../../img/ImagesDivers/placeholder.jpg";
                $name = htmlspecialchars($team['name'] ?? 'Unknown');
                $key  = htmlspecialchars($team['objectname'] ?? '');
                $logo = htmlspecialchars($logo);
                renderTeamCard($logo, $name, $key);
                ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script>
        let page = 1;
        let loading = false;

        const container = document.getElementById('teams-container');

        function animateCards() {
            const cards = document.querySelectorAll('.team-card:not(.visible)');
            const trigger = window.innerHeight;

            cards.forEach(card => {
                const rect = card.getBoundingClientRect();
                if (rect.top < trigger - 50) {
                    card.classList.add('visible');
                }
            });
        }

        async function loadMoreTeams() {
            if (loading) return;
            loading = true;
            page++;

            const params = new URLSearchParams(window.location.search);
            params.set('page', page);

            const res = await fetch('load_teams.php?' + params.toString());
            const html = await res.text();

            if (html.trim() === '') {
                window.removeEventListener('scroll', onScroll);
                return;
            }

            container.insertAdjacentHTML('beforeend', html);
            animateCards();

            loading = false;
        }

        function onScroll() {
            if (window.innerHeight + window.scrollY >= document.body.offsetHeight - 200) {
                loadMoreTeams();
            }
        }

        window.addEventListener('scroll', onScroll);
        window.addEventListener('scroll', animateCards);
        window.addEventListener('load', animateCards);
    </script>

</body>

</html>