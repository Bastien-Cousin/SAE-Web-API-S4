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

$teamObjectName = $_GET['team'] ?? null;
if (!$teamObjectName)
    die("Équipe non trouvée");

$stmtTeam = $pdo->prepare("SELECT * FROM team WHERE objectname = :objectname");
$stmtTeam->execute([':objectname' => $teamObjectName]);
$teamData = $stmtTeam->fetch(PDO::FETCH_ASSOC);

if (!$teamData)
    die("Équipe non trouvée");

$teamExtradata = [];
if (!empty($teamData['extradata'])) {
    if (is_array($teamData['extradata'])) {
        $teamExtradata = $teamData['extradata'];
    } else {
        $decodedExtradata = json_decode((string) $teamData['extradata'], true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decodedExtradata)) {
            $teamExtradata = $decodedExtradata;
        }
    }
}

$ceoName = trim((string) ($teamExtradata['ceo'] ?? ''));

$stmtPlayers = $pdo->prepare("
    SELECT p.*, t.logourl, t.textlesslogourl 
    FROM player p 
    LEFT JOIN team t ON LOWER(REPLACE(REPLACE(p.teampagename, '_', ' '), '-', '')) = LOWER(REPLACE(REPLACE(SUBSTRING(t.objectname FROM POSITION('_team_' IN t.objectname) + 6), '_', ' '), '-', ''))
    WHERE LOWER(REPLACE(REPLACE(p.teampagename, '_', ' '), '-', '')) = LOWER(REPLACE(REPLACE(SUBSTRING(:teamObjectName FROM POSITION('_team_' IN :teamObjectName) + 6), '_', ' '), '-', ''))
    ORDER BY p.name ASC
");
$stmtPlayers->execute([':teamObjectName' => $teamObjectName]);
$playersData = $stmtPlayers->fetchAll(PDO::FETCH_ASSOC);

$equipe = [
    'nom' => $teamData['name'] ?? 'Équipe inconnue',
    'logo' => $teamData['logourl'] ?? $teamData['logo'] ?? '/img/default_logo.png',
    'ceo' => $ceoName !== '' ? $ceoName : 'Non disponible',
    'creation' => $teamData['createdate'] ? date('Y', strtotime($teamData['createdate'])) : 'Non disponible',
    'argent' => number_format($teamData['earnings'] ?? 0) . ' €',
];

function getPseudoFromObjectName($objectname)
{
    $objectname = (string) $objectname;

    // Format des joueurs créés manuellement: player_pseudo_1774714877740
    if (preg_match('/^player_(.+)_\d{10,}$/', $objectname, $matches)) {
        return $matches[1];
    }

    if (strpos($objectname, '_') !== false) {
        $parts = explode('_', $objectname);
        $lastPart = end($parts);
        return $lastPart !== false ? (string) $lastPart : $objectname;
    }

    return $objectname;
}

$joueursEquipe = array_map(function ($player) {
    $pseudo = trim((string) ($player['localizedname'] ?? ''));
    if ($pseudo === '') {
        $pseudo = getPseudoFromObjectName($player['objectname'] ?? 'N/A');
    }

    return [
        'pays' => $player['nationality'] ?? 'N/A',
        'pseudo' => $pseudo,
        'nom_prenom' => $player['name'] ?? 'N/A',
        'age' => $player['birthdate'] ? date('Y') - date('Y', strtotime($player['birthdate'])) : 'N/A',
        'statut' => $player['status'] ?? 'Actif',
        'role' => $player['type'] ?? 'player',
        'argent_gagne' => isset($player['earnings']) ? number_format($player['earnings']) . ' €' : '0 €',
        'logo' => $player['logourl'] ?? $player['textlesslogourl'] ?? '/img/default-logo.png',
        'couleur_logo' => '#cccccc',
    ];
}, $playersData);
?>

<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="UTF-8">
        <title><?= htmlspecialchars($equipe['nom']) ?></title>

        <link rel="stylesheet" href="../../style.css">
        <link rel="stylesheet" href="../../components/ComponentNavbar/navbar.css">
        <link rel="stylesheet" href="../../components/ComponentCardPlayer/cardPlayer.css">
        <link rel="stylesheet" href="DescriptionEquipe.css">
    </head>
    <body>

        <?php include __DIR__ . '/../../components/ComponentNavbar/navbar.php'; ?>

        <div class="top-bar">
            <a href="AccueilEquipe.php" class="back-btn">← Retour aux équipes</a>
        </div>

        <div class="team-header">
            <div class="team-left">
                <img src="<?= htmlspecialchars($equipe['logo']) ?>" class="team-logo" alt="<?= htmlspecialchars($equipe['nom']) ?>">
                <h1><?= htmlspecialchars($equipe['nom']) ?></h1>
            </div>

            <div class="team-info">
                <div class="info-left">
                    <p>
                        <strong>CEO :</strong>
                        <?= htmlspecialchars($equipe['ceo']) ?>
                    </p>
                    <p>
                        <strong>Création :</strong>
                        <?= htmlspecialchars($equipe['creation']) ?>
                    </p>
                </div>
                <div class="info-right">
                    <p>
                        <strong>Argent gagné :</strong>
                    </p>
                    <p class="money"><?= $equipe['argent'] ?></p>
                </div>
            </div>
        </div>

        <div class="players-section">
            <h2>Joueurs</h2>
            <div
                class="cards-grid">
                <?php if (empty($joueursEquipe)): ?>
                    <p>Aucun joueur disponible.</p>
                <?php else: ?>
                <?php foreach ($joueursEquipe as $joueur): ?>
                        <?php afficherCarteJoueur($joueur); ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

    </body>
</html>

