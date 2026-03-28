<?php
function renderTeamCard($logo, $name, $key)
{
    ?>
    <a href="/pages/PageAccueilEquipe/DescriptionEquipe.php?team=<?php echo $key; ?>" class="team-card">
        <img src="<?php echo $logo; ?>" alt="<?php echo $name; ?>">
        <span><?php echo $name; ?></span>
    </a><?php
}
?>

