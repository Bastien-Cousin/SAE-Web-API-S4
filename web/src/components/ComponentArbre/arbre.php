<?php
function renderBracket(string $tournamentName, array $rounds, ?array $thirdPlace = null): void
{
?>
    <div class="bracket__wrapper">
        <h2 class="bracket__title"><?= htmlspecialchars($tournamentName) ?></h2>

        <div class="bracket">
            <?php foreach ($rounds as $round): ?>
                <div class="round">
                    <div class="round__name"><?= htmlspecialchars($round['name']) ?></div>
                    <div class="round__matches">
                        <?php
                        $matches = $round['matches'];
                        // Grouper les matchs par paires
                        for ($i = 0; $i < count($matches); $i += 2):
                            $hasSecond = isset($matches[$i + 1]);
                        ?>
                            <div class="match-pair">
                                <?php renderMatch($matches[$i]) ?>
                                <?php if ($hasSecond): ?>
                                    <?php renderMatch($matches[$i + 1]) ?>
                                <?php endif; ?>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($thirdPlace): ?>
            <div class="bracket__third">
                <div class="round__name">Petite Finale</div>
                <?php renderMatch($thirdPlace) ?>
            </div>
        <?php endif; ?>
    </div>
<?php
}

function renderMatch(array $match): void
{
    $played   = $match['played'] ?? false;
    $home     = $match['home'];
    $away     = $match['away'];

    $homeWon  = $played && $home['score'] > $away['score'];
    $awayWon  = $played && $away['score'] > $home['score'];
?>
    <div class="match">
        <div class="team <?= $homeWon ? 'winner' : '' ?>">
            <span class="team__name"><?= htmlspecialchars($home['name']) ?></span>
            <?php if ($played): ?>
                <span class="team__score"><?= (int)$home['score'] ?></span>
            <?php endif; ?>
        </div>
        <div class="team <?= $awayWon ? 'winner' : '' ?>">
            <span class="team__name"><?= htmlspecialchars($away['name']) ?></span>
            <?php if ($played): ?>
                <span class="team__score"><?= (int)$away['score'] ?></span>
            <?php endif; ?>
        </div>
    </div>
<?php
}
