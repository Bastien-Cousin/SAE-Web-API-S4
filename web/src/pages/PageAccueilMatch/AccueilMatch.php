<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>CS2 - Accueil Match</title>

    <link rel="stylesheet" href="../../style.css">
    <link rel="stylesheet" href="../../components/ComponentNavbar/navbar.css">
    <link rel="stylesheet" href="../../components/componentMatchBar/matchBar.css">
    <link rel="stylesheet" href="AccueilMatch.css">
</head>

<body>

    <?php include __DIR__ . '/../../components/ComponentNavbar/navbar.php'; ?>

    <?php include __DIR__ . '/../../components/componentMatchBar/matchBar.php'; ?>

    <?php
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
        if (is_array($data)) return $data;
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
                $logo = $team['logourl'] ?? $team['textlesslogourl'] ?? '';
                return $logo ?: null;
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
        if ($team1 && $team2) return [$team1, $team2];

        $opponents = decodeJson($match['match2opponents'] ?? '');
        $names = [];
        foreach ($opponents as $opp) {
            if (!empty($opp['name'])) $names[] = $opp['name'];
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
            if (($map['status'] ?? '') === 'notplayed') continue;
            if (isset($map['score']) && preg_match('/(\d+)-(\d+)/', $map['score'], $m)) {
                if ($m[1] > $m[2]) $t1++;
                elseif ($m[2] > $m[1]) $t2++;
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

    $search      = $_GET['search'] ?? '';
    $mapsFilter  = $_GET['maps']   ?? '';
    $scoreFilter = $_GET['score']  ?? '';
    $dateFilter  = $_GET['date']   ?? '';

    $limit  = 30;
    $offset = 0;

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
    $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($scoreFilter !== '') {
        $matches = array_filter($matches, function ($match) use ($scoreFilter) {
            return computeScore($match) === $scoreFilter;
        });
    }
    ?>

    <div class="header-section">
        <h1>Matchs CS2</h1>
        <a href="FormulaireMap.php" class="btn-add-map">+ Nouveau Match</a>
    </div>

    <form method="GET" class="filters">
        <input type="text" name="search" placeholder="Rechercher une équipe..." class="search-bar"
            value="<?= htmlspecialchars($search) ?>">

        <select name="maps" class="filter-btn">
            <option value="">Toutes les maps</option>
            <?php
            $mapsList = ["Ancient", "Dust II", "Inferno", "Mirage", "Nuke", "Vertigo", "Anubis"];
            foreach ($mapsList as $map) {
                $selected = ($mapsFilter === $map) ? 'selected' : '';
                echo "<option value=\"$map\" $selected>$map</option>";
            }
            ?>
        </select>

        <select name="score" class="filter-btn">
            <option value="">Tous les scores</option>
            <?php
            $scores = ["3-0", "3-1", "3-2", "2-0", "2-1", "2-3", "1-2", "1-3", "0-2", "0-3"];
            foreach ($scores as $s) {
                $selected = ($scoreFilter === $s) ? 'selected' : '';
                echo "<option value=\"$s\" $selected>$s</option>";
            }
            ?>
        </select>

        <input type="date" name="date" class="filter-btn" value="<?= htmlspecialchars($dateFilter) ?>">

        <button type="submit" class="filter-btn filter-green">Rechercher</button>
    </form>

    <div class="matches" id="matches-container">
        <?php if (empty($matches)): ?>
            <p>Aucun match trouvé.</p>
        <?php else: ?>
            <?php foreach ($matches as $match):
                list($team1, $team2) = extractTeams($match);
                $score     = computeScore($match);
                $team1Logo = safeLogo(getTeamLogoByName($pdo, $team1));
                $team2Logo = safeLogo(getTeamLogoByName($pdo, $team2));
                $date      = !empty($match['date']) ? date('Y-m-d', strtotime($match['date'])) : 'N/A';
                $link      = "/pages/PageAccueilMatch/DescriptionMatch.php?id=" . urlencode($match['pageid']);
                renderMatchBar($team1Logo, $team1, $score, $team2, $team2Logo, $date, $link);
            endforeach; ?>
        <?php endif; ?>
    </div>

    <script>
        let page = 1;
        let loading = false;

        const container = document.getElementById('matches-container');

        function animateCards() {
            const bars = document.querySelectorAll('.match-link:not(.visible)');
            const trigger = window.innerHeight;

            bars.forEach(bar => {
                const rect = bar.getBoundingClientRect();
                if (rect.top < trigger - 50) {
                    bar.classList.add('visible');
                }
            });
        }

        async function loadMoreMatches() {
            if (loading) return;
            loading = true;
            page++;

            const params = new URLSearchParams(window.location.search);
            params.set('page', page);

            const res = await fetch('load_matches.php?' + params.toString());
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
                loadMoreMatches();
            }
        }

        window.addEventListener('scroll', onScroll);
        window.addEventListener('scroll', animateCards);
        window.addEventListener('load', animateCards);
    </script>

</body>

</html>