<?php
include __DIR__ . '/../../components/ComponentCardPlayer/cardPlayer.php';

$host = getenv('POSTGRES_HOST') ?: 'db';
$port = getenv('POSTGRES_PORT') ?: '5432';
$dbname = getenv('POSTGRES_DB') ?: 'sae_db';
$user = getenv('POSTGRES_USER') ?: 'sae_user';
$password = getenv('POSTGRES_PASSWORD') ?: 'sae_password';

$pdo = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $user, $password);

$search = $_GET['search'] ?? '';
$region = $_GET['region'] ?? '';

$limit = 50;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$sql = "SELECT p.pageid, p.objectname, p.localizedname, p.name, p.birthdate, p.status, p.type, p.earnings, p.nationality, p.region, p.teampagename, t.logourl, t.textlesslogourl
        FROM player p
        LEFT JOIN team t ON LOWER(REPLACE(REPLACE(p.teampagename, '_', ' '), '-', '')) = LOWER(REPLACE(REPLACE(SUBSTRING(t.objectname FROM POSITION('_team_' IN t.objectname) + 6), '_', ' '), '-', ''))
        WHERE 1=1";

$params = [];

if ($search !== '') {
    $sql .= " AND (p.name ILIKE :search OR p.objectname ILIKE :search OR p.localizedname ILIKE :search)";
    $params[':search'] = "%$search%";
}

if ($region !== '') {
    $sql .= " AND p.region = :region";
    $params[':region'] = $region;
}

$sql .= " ORDER BY p.name ASC LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($sql);

foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}

$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

$stmt->execute();

$playersData = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($playersData as $player) {

    $pseudo = trim((string) ($player['localizedname'] ?? ''));

    if ($pseudo === '') {
        $objectname = (string) ($player['objectname'] ?? '');

        if (preg_match('/^player_(.+)_\d{10,}$/', $objectname, $matches)) {
            $pseudo = $matches[1];
        } elseif (strpos($objectname, '_') !== false) {
            $parts = explode('_', $objectname);
            $pseudo = (string) end($parts);
        } else {
            $pseudo = $objectname;
        }
    }

    if ($pseudo === '') {
        $pseudo = 'N/A';
    }

    $joueur = [
        'pays' => $player['nationality'] ?? 'N/A',
        'pseudo' => $pseudo,
        'nom_prenom' => $player['name'] ?? 'N/A',
        'age' => $player['birthdate'] ? date('Y') - date('Y', strtotime($player['birthdate'])) : 'N/A',
        'statut' => $player['status'] ?? 'Actif',
        'role' => $player['type'] ?? 'N/A',
        'argent_gagne' => isset($player['earnings']) ? number_format($player['earnings']) . ' €' : 'N/A',
        'logo' => $player['logourl'] ?? $player['textlesslogourl'] ?? '',
        'couleur_logo' => '#cccccc',
    ];

    afficherCarteJoueur($joueur);
}