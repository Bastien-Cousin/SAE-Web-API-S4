<?php
function renderTournament($image, $name, $cashprize, $link = "#")
{
    $urlName = urlencode($name);
    ?>
    <a href="<?= $link ?>?name=<?= $urlName ?>" class="tournament-card">
        <div class="card-image">
            <img src="<?= htmlspecialchars($image) ?>" alt="Image du tournoi">
        </div>
        <div class="card-content">
            <h3><?= $name ?></h3>
            <p><?= $cashprize ?></p>
        </div>
    </a><?php
}
?>

