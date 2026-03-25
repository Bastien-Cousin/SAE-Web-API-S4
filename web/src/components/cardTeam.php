<?php
function renderTeamCard($logo, $name)
{
    ?>
    <div class="team-card">
        <img src="<?php echo $logo; ?>" alt="<?php echo $name; ?>">
        <span><?php echo $name; ?></span>
    </div><?php
}
?><style>
.team-card {
    width: 175px;
    height: 200px;
    background-color: white;
    border: 2px solid black;
    border-radius: 10px;
    padding: 35px;
    text-align: center;
    color: black;
    transition: 0.25s ease;
}

.team-card img {
    width: 100px;
    height: 100px;
    object-fit: contain;
    margin-bottom: 10px;
}

.team-card:hover {
    transform: scale(1.05);
    border-color: #fca311;
    cursor: pointer;
}</style>

