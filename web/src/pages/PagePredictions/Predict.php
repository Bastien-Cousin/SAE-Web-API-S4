<?php
header('Content-Type: application/json');

// ─── Connexion BDD ───────────────────────────────────────────────────────────
$host     = getenv('POSTGRES_HOST')     ?: 'db';
$port     = getenv('POSTGRES_PORT')     ?: '5432';
$dbname   = getenv('POSTGRES_DB')       ?: 'sae_db';
$user     = getenv('POSTGRES_USER')     ?: 'sae_user';
$password = getenv('POSTGRES_PASSWORD') ?: 'sae_password';

$team1_id = intval($_GET['team1_id'] ?? 0);
$team2_id = intval($_GET['team2_id'] ?? 0);

if (!$team1_id || !$team2_id) {
    http_response_code(400);
    echo json_encode(['error' => 'IDs d\'équipes manquants']);
    exit;
}

try {
    $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}

// ─── Poids par tier de tournoi ───────────────────────────────────────────────
function tierWeight(?string $tier): float
{
    $t = strtolower(trim($tier ?? ''));
    if (str_contains($t, 'premier') || $t === 's-tier') return 3.0;
    if ($t === 'a-tier')                                  return 2.0;
    if ($t === 'b-tier')                                  return 1.5;
    return 1.0;
}

function getElo(PDO $pdo, int $teamId): float
{
    $stmt = $pdo->prepare("
        SELECT
            COUNT(*) AS total,
            SUM(CASE
                WHEN (team1_id = :tid AND winner = '1')
                  OR (team2_id = :tid AND winner = '2')
                THEN 1 ELSE 0 END) AS wins
        FROM match
        WHERE finished = true
          AND (team1_id = :tid OR team2_id = :tid)
    ");
    $stmt->execute([':tid' => $teamId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row['total'] == 0) return 1500;

    $wr = $row['wins'] / $row['total'];
    return 1500 + ($wr - 0.5) * 800;
}

function eloProbability(float $elo1, float $elo2): float
{
    return 1 / (1 + pow(10, ($elo2 - $elo1) / 400));
}

// ─── 0. Infos équipes (earnings, region) ────────────────────────────────────
$teamStmt = $pdo->prepare("
    SELECT pageid, name, earnings, region
    FROM team
    WHERE pageid IN (:t1, :t2)
");
$teamStmt->execute([':t1' => $team1_id, ':t2' => $team2_id]);
$teamRows = $teamStmt->fetchAll(PDO::FETCH_ASSOC);

$teamInfo = [];
foreach ($teamRows as $row) {
    $teamInfo[$row['pageid']] = $row;
}

$earnings1 = (int)($teamInfo[$team1_id]['earnings'] ?? 0);
$earnings2 = (int)($teamInfo[$team2_id]['earnings'] ?? 0);
$region1   = $teamInfo[$team1_id]['region'] ?? null;
$region2   = $teamInfo[$team2_id]['region'] ?? null;

$totalEarnings  = $earnings1 + $earnings2;
$earningsScore1 = $totalEarnings > 0 ? $earnings1 / $totalEarnings : 0.5;

function formatEarnings(int $e): string
{
    if ($e >= 1_000_000) return round($e / 1_000_000, 2) . 'M$';
    if ($e >= 1_000)     return round($e / 1_000, 1)     . 'k$';
    return $e > 0 ? $e . '$' : 'N/A';
}

// ─── 1. Historique H2H ───────────────────────────────────────────────────────
$h2hStmt = $pdo->prepare("
    SELECT
        winner,
        liquipediatier,
        CASE WHEN team1_id = :t1 THEN '1' ELSE '2' END AS perspective,
        date
    FROM match
    WHERE finished = true
      AND (
          (team1_id = :t1 AND team2_id = :t2)
       OR (team1_id = :t2 AND team2_id = :t1)
      )
    ORDER BY date DESC
");
$h2hStmt->execute([':t1' => $team1_id, ':t2' => $team2_id]);
$h2hMatches = $h2hStmt->fetchAll(PDO::FETCH_ASSOC);

$h2hWW1      = 0.0;
$h2hWT       = 0.0;
$h2hWins1Raw = 0;
$h2hWins2Raw = 0;

foreach ($h2hMatches as $m) {
    $w       = tierWeight($m['liquipediatier']);
    $h2hWT  += $w;
    $team1Won = ($m['perspective'] === '1' && $m['winner'] === '1')
        || ($m['perspective'] === '2' && $m['winner'] === '2');
    if ($team1Won) {
        $h2hWW1 += $w;
        $h2hWins1Raw++;
    } else {
        $h2hWins2Raw++;
    }
}

$h2hCount  = count($h2hMatches);
$h2hScore1 = $h2hWT > 0 ? $h2hWW1 / $h2hWT : 0.5;

// ─── 2. Forme récente (10 derniers matchs, pondérés par tier) ────────────────
function getRecentForm(PDO $pdo, int $teamId): array
{
    $stmt = $pdo->prepare("
        SELECT
            CASE WHEN team1_id = :tid THEN '1' ELSE '2' END AS side,
            winner,
            liquipediatier,
            date
        FROM match
        WHERE finished = true
          AND (team1_id = :tid OR team2_id = :tid)
        ORDER BY date DESC
        LIMIT 10
    ");
    $stmt->execute([':tid' => $teamId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $wWins  = 0.0;
    $wTotal = 0.0;
    $results = [];
    foreach ($rows as $r) {
        $w       = tierWeight($r['liquipediatier']);
        $wTotal += $w;
        $won     = ($r['side'] === $r['winner']);
        if ($won) $wWins += $w;
        $results[] = $won ? 'W' : 'L';
    }
    return [
        'score'   => $wTotal > 0 ? $wWins / $wTotal : 0.5,
        'results' => $results,
        'count'   => count($rows),
    ];
}

$recent1 = getRecentForm($pdo, $team1_id);
$recent2 = getRecentForm($pdo, $team2_id);

// ─── 3. Win Rate global ──────────────────────────────────────────────────────
function getGlobalWR(PDO $pdo, int $teamId): float
{
    $stmt = $pdo->prepare("
        SELECT
            COUNT(*) AS total,
            SUM(CASE
                WHEN (team1_id = :tid AND winner = '1')
                  OR (team2_id = :tid AND winner = '2') THEN 1 ELSE 0
            END) AS wins
        FROM match
        WHERE finished = true
          AND (team1_id = :tid OR team2_id = :tid)
    ");
    $stmt->execute([':tid' => $teamId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return (int)$row['total'] > 0 ? (float)$row['wins'] / (float)$row['total'] : 0.5;
}

$globalWR1 = getGlobalWR($pdo, $team1_id);
$globalWR2 = getGlobalWR($pdo, $team2_id);

// ─── Scores relatifs (ramenés à la perspective "team1 gagne") ────────────────
$recentRelative1 = ($recent1['score'] + (1 - $recent2['score'])) / 2;
$globalRelative1 = ($globalWR1        + (1 - $globalWR2))        / 2;

// ─── Pondération dynamique selon la quantité de H2H disponible ───────────────
if ($h2hCount >= 5) {
    $wH2H      = 0.40;
    $wRecent   = 0.30;
    $wGlobal   = 0.20;
    $wEarnings = 0.10;
} elseif ($h2hCount >= 2) {
    $wH2H      = 0.20;
    $wRecent   = 0.40;
    $wGlobal   = 0.25;
    $wEarnings = 0.15;
} else {
    $wH2H      = 0.00;
    $wRecent   = 0.50;
    $wGlobal   = 0.30;
    $wEarnings = 0.20;
    $h2hScore1 = 0.5; // neutre
}

// ─── Calcul final — fusion pondérée de tous les signaux ──────────────────────
// CORRECTION : on utilise réellement les poids calculés ci-dessus
// (avant : seul eloProbability() était utilisé, les poids étaient ignorés)
$elo1 = getElo($pdo, $team1_id);
$elo2 = getElo($pdo, $team2_id);

$prob1 = $wH2H      * $h2hScore1
    + $wRecent   * $recentRelative1
    + $wGlobal   * $globalRelative1
    + $wEarnings * $earningsScore1;

$prob1 = max(0.05, min(0.95, $prob1));
$prob2 = 1 - $prob1;

// ─── Réponse JSON ─────────────────────────────────────────────────────────────
echo json_encode([
    'prob1' => round($prob1 * 100, 1),
    'prob2' => round($prob2 * 100, 1),

    'h2h' => [
        'count'  => $h2hCount,
        'wins1'  => $h2hWins1Raw,
        'wins2'  => $h2hWins2Raw,
        'weight' => $wH2H,
    ],

    'recent1' => [
        'wr'      => round($recent1['score'] * 100, 1),
        'count'   => $recent1['count'],
        'results' => $recent1['results'],
    ],
    'recent2' => [
        'wr'      => round($recent2['score'] * 100, 1),
        'count'   => $recent2['count'],
        'results' => $recent2['results'],
    ],

    'global_wr1' => round($globalWR1 * 100, 1),
    'global_wr2' => round($globalWR2 * 100, 1),

    'earnings' => [
        'team1'  => formatEarnings($earnings1),
        'team2'  => formatEarnings($earnings2),
        'weight' => $wEarnings,
    ],

    'region' => [
        'team1' => $region1,
        'team2' => $region2,
    ],

    'weights' => [
        'h2h'      => $wH2H,
        'recent'   => $wRecent,
        'global'   => $wGlobal,
        'earnings' => $wEarnings,
    ],

    'elo' => [
        'team1' => round($elo1),
        'team2' => round($elo2),
    ],
]);
