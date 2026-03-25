<?php
function renderTournament($image, $name, $cashprize, $link = "#")
{
    $urlName = urlencode($name);
    ?>
    <a href="<?= $link ?>?name=<?= $urlName ?>" class="tournament-card">
        <div class="card-image" style="background-image: url('<?= $image ?>');"></div>
        <div class="card-content">
            <h3><?= $name ?></h3>
            <p><?= $cashprize ?></p>
        </div>
    </a>
    <?php
}
?>