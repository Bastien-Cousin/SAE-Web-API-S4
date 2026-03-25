<?php
function renderMatchBar($team1Logo, $team1Name, $score, $team2Name, $team2Logo, $date, $link)
{
    ?>
        <a href="<?php echo $link; ?>" class="match-link">
            <div class="match-bar">

                <div class="team">
                    <img src="<?php echo $team1Logo; ?>" alt="">
                    <span><?php echo $team1Name; ?></span>
                </div>

                <div class="match-center">
                    <div class="score"><?php echo $score; ?></div>
                    <div class="date"><?php echo $date; ?></div>
                </div>

                <div class="team">
                    <span><?php echo $team2Name; ?></span>
                    <img src="<?php echo $team2Logo; ?>" alt="">
                </div>

            </div>
        </a>
    <?php
}
?>

