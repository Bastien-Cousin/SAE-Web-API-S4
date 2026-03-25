<?php
function renderTeamCard($logo, $name)
{
    ?>
    <div class="team-card">
        <img src="<?php echo $logo; ?>" alt="<?php echo $name; ?>">
        <span><?php echo $name; ?></span>
    </div><?php
}
?>

