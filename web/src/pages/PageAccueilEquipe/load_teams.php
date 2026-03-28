<?php
include __DIR__ . '/../../components/ComponentCardTeam/cardTeam.php';

$host     = getenv('POSTGRES_HOST')     ?: 'db';
$port     = getenv('POSTGRES_PORT')     ?: '5432';
$dbname   = getenv('POSTGRES_DB')       ?: 'sae_db';
$user     = getenv('POSTGRES_USER')     ?: 'sae_user';
$password = getenv('POSTGRES_PASSWORD') ?: 'sae_password';

$pdo = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $user, $password);

$search  = $_GET['search']  ?? '';
$country = $_GET['country'] ?? '';
$sort    = $_GET['sort']    ?? 'name_asc';

$limit  = 30;
$page   = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

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

foreach ($teams as $team) {
    $logo = $team['logourl'] ?? $team['textlesslogourl'] ?? '';
    if (empty($logo)) {
        $logo = "../../img/ImagesDivers/placeholder.jpg";
    }
    $name = htmlspecialchars($team['name'] ?? 'Unknown');
    $key  = htmlspecialchars($team['objectname'] ?? '');
    $logo = htmlspecialchars($logo);

    renderTeamCard($logo, $name, $key);
}