<?php

function renderMatch(array $match, string $size = 'md'): void
{
    $home = $match['home'] ?? null;
    $away = $match['away'] ?? null;
    $played = $match['played'] ?? false;

    $isEmpty = ($home === null && $away === null);
    $cls = 'match match--' . htmlspecialchars($size);
    if ($isEmpty)
        $cls .= ' match--empty';
    if ($played)
        $cls .= ' match--played';
    ?>
    <div
        class="<?= $cls ?>">
        <?php if ($isEmpty): ?>
            <div class="match__slot match__slot--placeholder"></div>
            <div class="match__slot match__slot--placeholder"></div>
        <?php else: ?>
        <?php renderTeamSlot($home, $away, $played); ?>
            <?php renderTeamSlot($away, $home, $played); ?>
        <?php endif; ?>
    </div><?php
}

function renderTeamSlot(?array $team, ?array $opponent, bool $played): void
{
    if ($team === null) {
        echo '<div class="match__slot match__slot--tbd"><span>TBD</span></div>';
        return;
    }

    $winner = $played
        && isset($team['score'], $opponent['score'])
        && $team['score'] > $opponent['score'];

    $cls = 'match__slot';
    if ($played)
        $cls .= $winner ? ' match__slot--winner' : ' match__slot--loser';

    $name = htmlspecialchars($team['name'] ?? '???');
    $logo = htmlspecialchars($team['logo'] ?? '');
    $score = $team['score'] ?? null;
    ?><div class="<?= $cls ?>">
        <div
            class="match__team">
            <?php if ($logo): ?>
                <img
                class="match__logo" src="<?= $logo ?>" alt="<?= $name ?>" loading="lazy">
            <?php else: ?>
                <span class="match__logo match__logo--placeholder"><?= mb_substr($name, 0, 1) ?></span>
            <?php endif; ?>
            <span class="match__name"><?= $name ?></span>
        </div>
        <?php if ($played && $score !== null): ?>
            <span class="match__score <?= $winner ? 'match__score--winner' : '' ?>"><?= (int) $score ?></span>
        <?php endif; ?>
    </div><?php
}

function renderBracket(string $tournamentName, array $rounds, ?array $thirdPlace = null): void
{
    $totalRounds = count($rounds);
    ?><section class="bracket">
        <div class="bracket__body">
            <div
                class="bracket__rounds" style="--total-rounds: <?= $totalRounds ?>">

                <?php foreach ($rounds as $roundIndex => $round):
                    $roundName = htmlspecialchars($round['name'] ?? 'Round ' . ($roundIndex + 1));
                    $matches = $round['matches'] ?? [];
                    $isFinal = ($roundIndex === $totalRounds - 1);
                    $roundCls = 'bracket__round' . ($isFinal ? ' bracket__round--final' : '');
                    ?>
                    <div class="<?= $roundCls ?>">
                        <h3 class="bracket__round-name"><?= $roundName ?></h3>

                        <div
                            class="bracket__matches"><?php foreach ($matches as $match):
                                renderMatch($match, $isFinal ? 'lg' : 'md');
                            endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

            </div>

            <?php if ($thirdPlace !== null): ?>
                <div class="bracket__third-place">
                    <h3 class="bracket__round-name">Match pour la 3<sup>ème</sup>
                        place</h3>
                    <?php renderMatch($thirdPlace, 'sm'); ?>
                </div>
            <?php endif; ?>
        </div>
    </section><?php
}

$tournament = [
    'name' => 'CS2 Championship',
    'rounds' => [
        [
            'name' => 'Quarts de finale',
            'matches' => [
                [
                    'home' => ['name' => 'Vitality', 'logo' => '', 'score' => 2],
                    'away' => ['name' => 'NaVi', 'logo' => '', 'score' => 0],
                    'played' => true,
                ],
                [
                    'home' => ['name' => 'ENCE', 'logo' => '', 'score' => 2],
                    'away' => ['name' => 'FaZe', 'logo' => '', 'score' => 0],
                    'played' => true,
                ],
                [
                    'home' => ['name' => 'Spirit', 'logo' => '', 'score' => null],
                    'away' => null,
                    'played' => false,
                ],
                [
                    'home' => null,
                    'away' => null,
                    'played' => false,
                ],
            ],
        ],
        [
            'name' => 'Demi-finales',
            'matches' => [
                [
                    'home' => ['name' => 'Vitality', 'logo' => '', 'score' => 2],
                    'away' => ['name' => 'ENCE', 'logo' => '', 'score' => 0],
                    'played' => true,
                ],
                [
                    'home' => null,
                    'away' => null,
                    'played' => false,
                ],
            ],
        ],
        [
            'name' => 'Finale',
            'matches' => [
                [
                    'home' => ['name' => 'Vitality', 'logo' => '', 'score' => 2],
                    'away' => ['name' => 'Spirit', 'logo' => '', 'score' => 0],
                    'played' => true,
                ],
            ],
        ],
    ],
    'third_place_match' => [
        'home' => ['name' => 'FaZe', 'logo' => '', 'score' => 2],
        'away' => ['name' => 'ENCE', 'logo' => '', 'score' => 0],
        'played' => true,
    ],
];

?><!DOCTYPE html><html lang="fr">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title><?= htmlspecialchars($tournament['name']) ?></title><link rel="stylesheet" href="arbre.css"></head>
<body><?php
renderBracket(
    $tournament['name'],
    $tournament['rounds'],
    $tournament['third_place_match'] ?? null
);
?></body></html>

