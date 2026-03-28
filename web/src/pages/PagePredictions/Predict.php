<?php
header('Content-Type: application/json');

$host = getenv('POSTGRES_HOST') ?: 'db';
$port = getenv('POSTGRES_PORT') ?: '5432';
$dbname = getenv('POSTGRES_DB') ?: 'sae_db';
$user = getenv('POSTGRES_USER') ?: 'sae_user';
$password = getenv('POSTGRES_PASSWORD') ?: 'sae_password';

$team1_id = (int) ($_GET['team1_id'] ?? 0);
$team2_id = (int) ($_GET['team2_id'] ?? 0);

if (!$team1_id || !$team2_id) {
    http_response_code(400);
    echo json_encode(['error' => 'IDs d\'equipes manquants']);
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

function tierWeight(?string $tier): float
{
    $normalizedTier = strtolower(trim($tier ?? ''));

    if (str_contains($normalizedTier, 'premier') || $normalizedTier === 's-tier') {
        return 3.0;
    }
    if ($normalizedTier === 'a-tier') {
        return 2.0;
    }
    if ($normalizedTier === 'b-tier') {
        return 1.5;
    }

    return 1.0;
}

function normalizeTeamName(?string $name): string
{
    $normalized = strtolower(trim((string) $name));
    $normalized = preg_replace('/^team\s+/i', '', $normalized);
    $normalized = preg_replace('/[^a-z0-9]+/', ' ', $normalized);
    $normalized = preg_replace('/\s+/', ' ', (string) $normalized);

    return trim((string) $normalized);
}

function sameTeamName(?string $left, ?string $right): bool
{
    $normalizedLeft = normalizeTeamName($left);
    $normalizedRight = normalizeTeamName($right);

    if ($normalizedLeft === '' || $normalizedRight === '') {
        return false;
    }

    if ($normalizedLeft === $normalizedRight) {
        return true;
    }

    return str_contains($normalizedLeft, $normalizedRight) || str_contains($normalizedRight, $normalizedLeft);
}

function decodeJsonArray($value): array
{
    if (is_array($value)) {
        return $value;
    }

    $decoded = json_decode((string) $value, true);
    return is_array($decoded) ? $decoded : [];
}

function extractParticipantNames(array $match): array
{
    $participants = [];

    if (!empty($match['team1_name'])) {
        $participants[] = $match['team1_name'];
    }
    if (!empty($match['team2_name'])) {
        $participants[] = $match['team2_name'];
    }

    if (count($participants) >= 2) {
        return [$participants[0], $participants[1]];
    }

    $opponents = decodeJsonArray($match['match2opponents'] ?? '[]');
    $names = [];
    foreach ($opponents as $opponent) {
        $name = $opponent['name'] ?? null;
        if ($name) {
            $names[] = $name;
        }
    }

    return [
        $participants[0] ?? ($names[0] ?? null),
        $participants[1] ?? ($names[1] ?? null),
    ];
}

function matchHasTeam(array $match, int $teamId, string $teamName): bool
{
    if ((string) ($match['team1_id'] ?? '') === (string) $teamId || (string) ($match['team2_id'] ?? '') === (string) $teamId) {
        return true;
    }

    [$participant1, $participant2] = extractParticipantNames($match);
    return sameTeamName($participant1, $teamName) || sameTeamName($participant2, $teamName);
}

function getTeamSide(array $match, int $teamId, string $teamName): ?int
{
    if ((string) ($match['team1_id'] ?? '') === (string) $teamId) {
        return 1;
    }
    if ((string) ($match['team2_id'] ?? '') === (string) $teamId) {
        return 2;
    }

    [$participant1, $participant2] = extractParticipantNames($match);
    if (sameTeamName($participant1, $teamName)) {
        return 1;
    }
    if (sameTeamName($participant2, $teamName)) {
        return 2;
    }

    return null;
}

function didTeamWin(array $match, int $teamId, string $teamName): ?bool
{
    $side = getTeamSide($match, $teamId, $teamName);
    $winner = (string) ($match['winner'] ?? '');

    if ($side === null || ($winner !== '1' && $winner !== '2')) {
        return null;
    }

    return (string) $side === $winner;
}

function formatEarnings(int $earnings): string
{
    if ($earnings >= 1000000) {
        return round($earnings / 1000000, 2) . 'M$';
    }
    if ($earnings >= 1000) {
        return round($earnings / 1000, 1) . 'k$';
    }

    return $earnings > 0 ? $earnings . '$' : 'N/A';
}

function buildRecentForm(array $matches, int $teamId, string $teamName): array
{
    $recentMatches = array_slice($matches, 0, 10);
    $weightedWins = 0.0;
    $weightedTotal = 0.0;
    $results = [];

    foreach ($recentMatches as $match) {
        $didWin = didTeamWin($match, $teamId, $teamName);
        if ($didWin === null) {
            continue;
        }

        $weight = tierWeight($match['liquipediatier'] ?? null);
        $weightedTotal += $weight;
        if ($didWin) {
            $weightedWins += $weight;
        }
        $results[] = $didWin ? 'W' : 'L';
    }

    return [
        'score' => $weightedTotal > 0 ? $weightedWins / $weightedTotal : 0.5,
        'results' => $results,
        'count' => count($results),
    ];
}

function buildGlobalWr(array $matches, int $teamId, string $teamName): float
{
    $total = 0;
    $wins = 0;

    foreach ($matches as $match) {
        $didWin = didTeamWin($match, $teamId, $teamName);
        if ($didWin === null) {
            continue;
        }

        $total++;
        if ($didWin) {
            $wins++;
        }
    }

    return $total > 0 ? $wins / $total : 0.5;
}

function buildElo(float $winRate, int $matchCount): float
{
    if ($matchCount === 0) {
        return 1500.0;
    }

    return 1500 + ($winRate - 0.5) * 800;
}

$teamStmt = $pdo->prepare(
    'SELECT pageid, name, earnings, region
     FROM team
     WHERE pageid = :team1_id OR pageid = :team2_id'
);
$teamStmt->execute([
    ':team1_id' => $team1_id,
    ':team2_id' => $team2_id,
]);
$teamRows = $teamStmt->fetchAll(PDO::FETCH_ASSOC);

$teamInfo = [];
foreach ($teamRows as $row) {
    $teamInfo[(string) $row['pageid']] = $row;
}

$team1Name = (string) ($teamInfo[(string) $team1_id]['name'] ?? '');
$team2Name = (string) ($teamInfo[(string) $team2_id]['name'] ?? '');
$team1ShortName = normalizeTeamName($team1Name);
$team2ShortName = normalizeTeamName($team2Name);

$matchStmt = $pdo->prepare(
    "SELECT
        m.pageid,
        m.team1_id,
        m.team2_id,
        m.winner,
        m.finished,
        m.status,
        m.date,
        m.liquipediatier,
        m.match2opponents,
        t1.name AS team1_name,
        t2.name AS team2_name
     FROM match m
     LEFT JOIN team t1 ON t1.pageid = m.team1_id
     LEFT JOIN team t2 ON t2.pageid = m.team2_id
     WHERE COALESCE(m.status, '') <> 'prediction_only'
       AND COALESCE(m.finished, false) = true
       AND (
            m.team1_id = :team1_id
         OR m.team2_id = :team1_id
         OR m.team1_id = :team2_id
         OR m.team2_id = :team2_id
         OR LOWER(COALESCE(m.match2opponents::text, '')) LIKE :team1_name_like
         OR LOWER(COALESCE(m.match2opponents::text, '')) LIKE :team1_short_like
         OR LOWER(COALESCE(m.match2opponents::text, '')) LIKE :team2_name_like
         OR LOWER(COALESCE(m.match2opponents::text, '')) LIKE :team2_short_like
       )
     ORDER BY m.date DESC NULLS LAST"
);
$matchStmt->execute([
    ':team1_id' => $team1_id,
    ':team2_id' => $team2_id,
    ':team1_name_like' => '%' . strtolower($team1Name) . '%',
    ':team1_short_like' => '%' . $team1ShortName . '%',
    ':team2_name_like' => '%' . strtolower($team2Name) . '%',
    ':team2_short_like' => '%' . $team2ShortName . '%',
]);
$allCandidateMatches = $matchStmt->fetchAll(PDO::FETCH_ASSOC);

$team1Matches = [];
$team2Matches = [];
$h2hMatches = [];

foreach ($allCandidateMatches as $match) {
    $hasTeam1 = matchHasTeam($match, $team1_id, $team1Name);
    $hasTeam2 = matchHasTeam($match, $team2_id, $team2Name);

    if ($hasTeam1) {
        $team1Matches[] = $match;
    }
    if ($hasTeam2) {
        $team2Matches[] = $match;
    }
    if ($hasTeam1 && $hasTeam2) {
        $h2hMatches[] = $match;
    }
}

$earnings1 = (int) ($teamInfo[(string) $team1_id]['earnings'] ?? 0);
$earnings2 = (int) ($teamInfo[(string) $team2_id]['earnings'] ?? 0);
$region1 = $teamInfo[(string) $team1_id]['region'] ?? null;
$region2 = $teamInfo[(string) $team2_id]['region'] ?? null;
$totalEarnings = $earnings1 + $earnings2;
$earningsScore1 = $totalEarnings > 0 ? $earnings1 / $totalEarnings : 0.5;

$h2hWeightedWins1 = 0.0;
$h2hWeightedTotal = 0.0;
$h2hWins1Raw = 0;
$h2hWins2Raw = 0;

foreach ($h2hMatches as $match) {
    $didTeam1Win = didTeamWin($match, $team1_id, $team1Name);
    if ($didTeam1Win === null) {
        continue;
    }

    $weight = tierWeight($match['liquipediatier'] ?? null);
    $h2hWeightedTotal += $weight;
    if ($didTeam1Win) {
        $h2hWeightedWins1 += $weight;
        $h2hWins1Raw++;
    } else {
        $h2hWins2Raw++;
    }
}

$h2hCount = $h2hWins1Raw + $h2hWins2Raw;
$h2hScore1 = $h2hWeightedTotal > 0 ? $h2hWeightedWins1 / $h2hWeightedTotal : 0.5;

$recent1 = buildRecentForm($team1Matches, $team1_id, $team1Name);
$recent2 = buildRecentForm($team2Matches, $team2_id, $team2Name);
$globalWR1 = buildGlobalWr($team1Matches, $team1_id, $team1Name);
$globalWR2 = buildGlobalWr($team2Matches, $team2_id, $team2Name);

$recentRelative1 = ($recent1['score'] + (1 - $recent2['score'])) / 2;
$globalRelative1 = ($globalWR1 + (1 - $globalWR2)) / 2;

if ($h2hCount >= 5) {
    $wH2H = 0.40;
    $wRecent = 0.30;
    $wGlobal = 0.20;
    $wEarnings = 0.10;
} elseif ($h2hCount >= 2) {
    $wH2H = 0.20;
    $wRecent = 0.40;
    $wGlobal = 0.25;
    $wEarnings = 0.15;
} else {
    $wH2H = 0.00;
    $wRecent = 0.50;
    $wGlobal = 0.30;
    $wEarnings = 0.20;
    $h2hScore1 = 0.5;
}

$elo1 = buildElo($globalWR1, count($team1Matches));
$elo2 = buildElo($globalWR2, count($team2Matches));

$prob1 = $wH2H * $h2hScore1
    + $wRecent * $recentRelative1
    + $wGlobal * $globalRelative1
    + $wEarnings * $earningsScore1;
$prob1 = max(0.05, min(0.95, $prob1));
$prob2 = 1 - $prob1;

echo json_encode([
    'prob1' => round($prob1 * 100, 1),
    'prob2' => round($prob2 * 100, 1),
    'h2h' => [
        'count' => $h2hCount,
        'wins1' => $h2hWins1Raw,
        'wins2' => $h2hWins2Raw,
        'weight' => $wH2H,
    ],
    'recent1' => [
        'wr' => round($recent1['score'] * 100, 1),
        'count' => $recent1['count'],
        'results' => $recent1['results'],
    ],
    'recent2' => [
        'wr' => round($recent2['score'] * 100, 1),
        'count' => $recent2['count'],
        'results' => $recent2['results'],
    ],
    'global_wr1' => round($globalWR1 * 100, 1),
    'global_wr2' => round($globalWR2 * 100, 1),
    'earnings' => [
        'team1' => formatEarnings($earnings1),
        'team2' => formatEarnings($earnings2),
        'weight' => $wEarnings,
    ],
    'region' => [
        'team1' => $region1,
        'team2' => $region2,
    ],
    'weights' => [
        'h2h' => $wH2H,
        'recent' => $wRecent,
        'global' => $wGlobal,
        'earnings' => $wEarnings,
    ],
    'elo' => [
        'team1' => round($elo1),
        'team2' => round($elo2),
    ],
]);
