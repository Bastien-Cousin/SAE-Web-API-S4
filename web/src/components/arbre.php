<?php

function renderMatch(array $match, string $size = 'md'): void
{
    $home   = $match['home']   ?? null;
    $away   = $match['away']   ?? null;
    $played = $match['played'] ?? false;

    $isEmpty = ($home === null && $away === null);
    $cls     = 'match match--' . htmlspecialchars($size);
    if ($isEmpty)  $cls .= ' match--empty';
    if ($played)   $cls .= ' match--played';
?>
    <div class="<?= $cls ?>">
        <?php if ($isEmpty): ?>
            <div class="match__slot match__slot--placeholder"></div>
            <div class="match__slot match__slot--placeholder"></div>
        <?php else: ?>
            <?php renderTeamSlot($home, $away, $played); ?>
            <?php renderTeamSlot($away, $home, $played); ?>
        <?php endif; ?>
    </div>
<?php
}

function renderTeamSlot(?array $team, ?array $opponent, bool $played): void
{
    if ($team === null) {
        echo '<div class="match__slot match__slot--tbd"><span>TBD</span></div>';
        return;
    }

    $winner = $played && isset($team['score'], $opponent['score'])
        && $team['score'] > $opponent['score'];

    $cls = 'match__slot';
    if ($played) $cls .= $winner ? ' match__slot--winner' : ' match__slot--loser';

    $name  = htmlspecialchars($team['name']  ?? '???');
    $logo  = htmlspecialchars($team['logo']  ?? '');
    $score = $team['score'] ?? null;
?>
    <div class="<?= $cls ?>">
        <div class="match__team">
            <?php if ($logo): ?>
                <img class="match__logo" src="<?= $logo ?>" alt="<?= $name ?>" loading="lazy">
            <?php else: ?>
                <span class="match__logo match__logo--placeholder"><?= mb_substr($name, 0, 1) ?></span>
            <?php endif; ?>
            <span class="match__name"><?= $name ?></span>
        </div>
        <?php if ($played && $score !== null): ?>
            <span class="match__score <?= $winner ? 'match__score--winner' : '' ?>"><?= (int)$score ?></span>
        <?php endif; ?>
    </div>
<?php
}

function renderBracket(string $tournamentName, array $rounds, ?array $thirdPlace = null): void
{
    $totalRounds = count($rounds);
?>
    <section class="bracket">
        <header class="bracket__header">
            <h2 class="bracket__title"><?= htmlspecialchars($tournamentName) ?></h2>
        </header>

        <div class="bracket__body">
            <div class="bracket__rounds" style="--total-rounds: <?= $totalRounds ?>">

                <?php foreach ($rounds as $roundIndex => $round):
                    $roundName    = htmlspecialchars($round['name'] ?? 'Round ' . ($roundIndex + 1));
                    $matches      = $round['matches'] ?? [];
                    $isFinal      = ($roundIndex === $totalRounds - 1);
                    $roundCls     = 'bracket__round' . ($isFinal ? ' bracket__round--final' : '');
                ?>
                    <div class="<?= $roundCls ?>">
                        <h3 class="bracket__round-name"><?= $roundName ?></h3>

                        <div class="bracket__matches">
                            <?php foreach ($matches as $match):
                                renderMatch($match, $isFinal ? 'lg' : 'md');
                            ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

            </div><!-- /.bracket__rounds -->

            <?php if ($thirdPlace !== null): ?>
                <div class="bracket__third-place">
                    <h3 class="bracket__round-name">Match pour la 3<sup>ème</sup> place</h3>
                    <?php renderMatch($thirdPlace, 'sm'); ?>
                </div>
            <?php endif; ?>
        </div><!-- /.bracket__body -->

    </section>
<?php
}

function bracketStyles(): void
{
?>
    <style>
        /* ── Variables ─────────────────────────────────────────── */
        :root {
            --bracket-bg: #d4d8e2;
            --bracket-header-bg: #1a2d6b;
            --bracket-header-fg: #ffffff;
            --round-name-color: #1a2d6b;

            --match-bg: #4a4f5a;
            --match-border: #5e6470;
            --match-radius: 5px;

            --slot-height: 34px;
            --slot-gap: 2px;
            --slot-pad-x: 8px;

            --winner-bg: #2a6b2a;
            --winner-fg: #ffffff;
            --loser-fg: #aaaaaa;
            --score-fg: #f0c040;
            --score-winner-fg: #ffffff;
            --tbd-bg: #3a3f4a;
            --tbd-fg: #777;

            --connector-color: #888;
            --connector-width: 2px;

            --font-ui: 'Segoe UI', system-ui, sans-serif;
        }

        /* ── Layout global ──────────────────────────────────────── */
        .bracket {
            background: var(--bracket-bg);
            border-radius: 8px;
            overflow: hidden;
            font-family: var(--font-ui);
            font-size: 13px;
        }

        .bracket__header {
            background: var(--bracket-header-bg);
            padding: 14px 24px;
        }

        .bracket__title {
            margin: 0;
            color: var(--bracket-header-fg);
            font-size: 1.2rem;
            font-weight: 600;
            letter-spacing: .5px;
            text-align: center;
        }

        .bracket__body {
            padding: 28px 24px 24px;
        }

        /* ── Rounds ─────────────────────────────────────────────── */
        .bracket__rounds {
            display: flex;
            align-items: center;
            gap: 0;
        }

        .bracket__round {
            display: flex;
            flex-direction: column;
            align-items: stretch;
            flex: 1;
            min-width: 170px;
        }

        .bracket__round-name {
            font-size: .7rem;
            font-weight: 700;
            color: var(--round-name-color);
            text-transform: uppercase;
            letter-spacing: 1px;
            text-align: center;
            margin: 0 0 12px;
        }

        .bracket__matches {
            display: flex;
            flex-direction: column;
            justify-content: space-around;
            flex: 1;
            gap: 0;
        }

        /* ── Connectors (lignes entre rounds) ───────────────────── */
        /*
   On simule les lignes via des pseudo-éléments sur les .match
   selon la position dans le bracket.
*/
        .bracket__round:not(:last-child) .bracket__matches {
            position: relative;
        }

        /* ── Match card ─────────────────────────────────────────── */
        .match {
            display: flex;
            flex-direction: column;
            gap: var(--slot-gap);
            margin: 12px 0;
            position: relative;
        }

        /* Ligne de sortie à droite */
        .match::after {
            content: '';
            position: absolute;
            right: 0;
            top: 50%;
            width: 16px;
            height: var(--connector-width);
            background: var(--connector-color);
            transform: translateY(-50%);
        }

        /* Ligne d'entrée à gauche (sauf 1er round) */
        .bracket__round:not(:first-child) .match::before {
            content: '';
            position: absolute;
            left: -16px;
            top: 50%;
            width: 16px;
            height: var(--connector-width);
            background: var(--connector-color);
            transform: translateY(-50%);
        }

        /* Pas de ligne sortante sur la finale */
        .bracket__round--final .match::after {
            display: none;
        }

        /* ── Slots ──────────────────────────────────────────────── */
        .match__slot {
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: var(--slot-height);
            padding: 0 var(--slot-pad-x);
            background: var(--match-bg);
            border: 1px solid var(--match-border);
            border-radius: var(--match-radius);
            color: #ddd;
            gap: 6px;
            transition: background .15s;
        }

        .match__slot--winner {
            background: var(--winner-bg);
            color: var(--winner-fg);
            border-color: #3a8a3a;
        }

        .match__slot--loser {
            opacity: .65;
            color: var(--loser-fg);
        }

        .match__slot--tbd {
            color: var(--tbd-fg);
            background: var(--tbd-bg);
            font-style: italic;
            justify-content: center;
        }

        .match__slot--placeholder {
            background: #3a3f4a;
            border: 1px dashed #555;
            opacity: .4;
        }

        /* ── Team info ──────────────────────────────────────────── */
        .match__team {
            display: flex;
            align-items: center;
            gap: 7px;
            min-width: 0;
        }

        .match__logo {
            width: 22px;
            height: 22px;
            object-fit: contain;
            flex-shrink: 0;
        }

        .match__logo--placeholder {
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background: #666;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 11px;
            flex-shrink: 0;
        }

        .match__name {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            font-weight: 500;
            font-size: .8rem;
        }

        /* ── Score ──────────────────────────────────────────────── */
        .match__score {
            font-weight: 700;
            font-size: .85rem;
            color: var(--score-fg);
            min-width: 14px;
            text-align: right;
            flex-shrink: 0;
        }

        .match__score--winner {
            color: var(--score-winner-fg);
        }

        /* ── Tailles ────────────────────────────────────────────── */
        .match--lg .match__slot {
            height: 40px;
            font-size: .9rem;
        }

        .match--sm .match__slot {
            height: 30px;
            font-size: .75rem;
        }

        /* ── Finale ─────────────────────────────────────────────── */
        .bracket__round--final {
            min-width: 200px;
        }

        /* ── 3ème place ─────────────────────────────────────────── */
        .bracket__third-place {
            margin-top: 24px;
            padding-top: 18px;
            border-top: 1px dashed #aaa;
            max-width: 220px;
            margin-left: auto;
        }

        .bracket__third-place .match::before,
        .bracket__third-place .match::after {
            display: none;
        }

        .bracket__third-place .match {
            margin: 6px 0 0;
        }

        /* ── Responsive ─────────────────────────────────────────── */
        @media (max-width: 700px) {
            .bracket__rounds {
                flex-direction: column;
                align-items: stretch;
            }

            .bracket__round {
                min-width: auto;
            }

            .match::before,
            .match::after {
                display: none;
            }

            .bracket__third-place {
                margin-left: 0;
                max-width: 100%;
            }
        }
    </style>
<?php
}


$tournament = [
    'name'   => 'CS2 Championship',
    'rounds' => [
        [
            'name'    => 'Quarts de finale',
            'matches' => [
                [
                    'home'   => ['name' => 'Vitality',  'logo' => '', 'score' => 2],
                    'away'   => ['name' => 'NaVi',      'logo' => '', 'score' => 0],
                    'played' => true,
                ],
                [
                    'home'   => ['name' => 'ENCE',      'logo' => '', 'score' => 2],
                    'away'   => ['name' => 'FaZe',      'logo' => '', 'score' => 0],
                    'played' => true,
                ],
                [
                    'home'   => ['name' => 'Spirit',    'logo' => '', 'score' => null],
                    'away'   => null,
                    'played' => false,
                ],
                [
                    'home'   => null,
                    'away'   => null,
                    'played' => false,
                ],
            ],
        ],
        [
            'name'    => 'Demi-finales',
            'matches' => [
                [
                    'home'   => ['name' => 'Vitality', 'logo' => '', 'score' => 2],
                    'away'   => ['name' => 'ENCE',     'logo' => '', 'score' => 0],
                    'played' => true,
                ],
                [
                    'home'   => null,
                    'away'   => null,
                    'played' => false,
                ],
            ],
        ],
        [
            'name'    => 'Finale',
            'matches' => [
                [
                    'home'   => ['name' => 'Vitality', 'logo' => '', 'score' => 2],
                    'away'   => ['name' => 'Spirit',   'logo' => '', 'score' => 0],
                    'played' => true,
                ],
            ],
        ],
    ],
    'third_place_match' => [
        'home'   => ['name' => 'FaZe', 'logo' => '', 'score' => 2],
        'away'   => ['name' => 'ENCE', 'logo' => '', 'score' => 0],
        'played' => true,
    ],
];

// ── Rendu ──────────────────────────────────────────────────
bracketStyles();
renderBracket(
    $tournament['name'],
    $tournament['rounds'],
    $tournament['third_place_match'] ?? null
);
?>