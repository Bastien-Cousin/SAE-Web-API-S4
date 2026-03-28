<?php
include __DIR__ . '/../../components/componentMatchBar/matchBar.php';

$pdo = new PDO(
    "pgsql:host=" . (getenv('POSTGRES_HOST') ?: 'db') .
    ";port=" . (getenv('POSTGRES_PORT') ?: '5432') .
    ";dbname=" . (getenv('POSTGRES_DB') ?: 'sae_db'),
    getenv('POSTGRES_USER') ?: 'sae_user',
    getenv('POSTGRES_PASSWORD') ?: 'sae_password',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

function decodeJson($data)
{
    if (is_array($data))
        return $data;
    $decoded = json_decode($data, true);
    return json_last_error() === JSON_ERROR_NONE ? $decoded : [];
}

function getTeamLogoByName($pdo, $teamName)
{
    try {
        $stmt = $pdo->prepare("SELECT logourl, textlesslogourl FROM team WHERE LOWER(name) = LOWER(:name) LIMIT 1");
        $stmt->execute([':name' => $teamName]);
        $team = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($team) {
            return $team['logourl'] ?? $team['textlesslogourl'] ?? null;
        }
    } catch (Exception $e) {
        error_log("Error looking up team logo for: $teamName - " . $e->getMessage());
    }
    return null;
}

function safeLogo($logo)
{
    return !empty($logo) ? $logo : '/img/LogosEquipes/default.png';
}

function extractTeams($match)
{
    $team1 = $match['team1_name'] ?? '';
    $team2 = $match['team2_name'] ?? '';
    if ($team1 && $team2)
        return [$team1, $team2];

    $opponents = decodeJson($match['match2opponents'] ?? '');
    $names = [];
    foreach ($opponents as $opp) {
        if (!empty($opp['name']))
            $names[] = $opp['name'];
    }
    return [
        $team1 ?: ($names[0] ?? 'Team 1'),
        $team2 ?: ($names[1] ?? 'Team 2')
    ];
}

function computeScore($match)
{
    $games = decodeJson($match['match2games'] ?? '');
    $t1 = 0;
    $t2 = 0;

    foreach ($games as $map) {
        if (($map['status'] ?? '') === 'notplayed')
            continue;
        if (isset($map['score']) && preg_match('/(\d+)-(\d+)/', $map['score'], $m)) {
            if ($m[1] > $m[2])
                $t1++;
            elseif ($m[2] > $m[1])
                $t2++;
        }
    }

    if ($t1 === 0 && $t2 === 0) {
        $opponents = decodeJson($match['match2opponents'] ?? '');
        if (count($opponents) >= 2) {
            return ($opponents[0]['score'] ?? 0) . '-' . ($opponents[1]['score'] ?? 0);
        }
    }

    return "$t1-$t2";
}

$search = $_GET['search'] ?? '';
$mapsFilter = $_GET['maps'] ?? '';
$scoreFilter = $_GET['score'] ?? '';
$dateFilter = $_GET['date'] ?? '';

$limit = 30;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$sql = "
    SELECT DISTINCT m.*,
        t1.name AS team1_name,
        t2.name AS team2_name
    FROM match m
    LEFT JOIN team t1 ON m.team1_id = t1.pageid
    LEFT JOIN team t2 ON m.team2_id = t2.pageid
    WHERE 1=1
    AND COALESCE(m.status, '') <> 'prediction_only'
";

$params = [];

if ($search !== '') {
    $sql .= "
        AND (
            t1.name ILIKE :search
            OR t2.name ILIKE :search
            OR EXISTS (
                SELECT 1
                FROM jsonb_array_elements(
                    CASE jsonb_typeof(m.match2opponents::jsonb)
                        WHEN 'array' THEN m.match2opponents::jsonb
                        ELSE '[]'::jsonb
                    END
                ) AS opp
                WHERE opp->>'name' ILIKE :search
            )
        )
    ";
    $params[':search'] = "%$search%";
}

if ($mapsFilter !== '') {
    $sql .= " AND m.match2games::text ILIKE :maps";
    $params[':maps'] = "%$mapsFilter%";
}

if ($dateFilter !== '') {
    $sql .= " AND DATE(m.date) = :date";
    $params[':date'] = $dateFilter;
}

$sql .= " ORDER BY m.date DESC LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$matches = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($scoreFilter !== '') {
    $matches = array_filter($matches, function ($match) use ($scoreFilter) {
        return computeScore($match) === $scoreFilter;
    });
}

foreach ($matches as $match) {
    list($team1, $team2) = extractTeams($match);
    $score = computeScore($match);
    $team1Logo = safeLogo(getTeamLogoByName($pdo, $team1));
    $team2Logo = safeLogo(getTeamLogoByName($pdo, $team2));
    $date = !empty($match['date']) ? date('Y-m-d', strtotime($match['date'])) : 'N/A';
    $link = "/pages/PageAccueilMatch/DescriptionMatch.php?id=" . urlencode($match['pageid']);

    renderMatchBar($team1Logo, $team1, $score, $team2, $team2Logo, $date, $link);
}