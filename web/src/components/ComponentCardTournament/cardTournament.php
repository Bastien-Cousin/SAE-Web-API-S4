<?php
function renderTournament($image, $name, $cashprize, $link = "#")
{
    $urlName = urlencode($name);
    ?>
    <a href="<?= $link ?>?name=<?= $urlName ?>" class="tournament-card">
        <div class="card-image">
            <img src="https://i.postimg.cc/WzVwqT3n/fond-CS.jpg" alt="Image du tournoi">
        </div>
        <div class="card-content">
            <h3><?= $name ?></h3>
            <p><?= $cashprize ?></p>
        </div>
    </a><?php
}
?>

