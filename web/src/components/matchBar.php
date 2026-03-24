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
</a><?php
}
?><style>
.match-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background-color: #e5e5e5;
    color: black;
    padding: 10px 20px;
    margin-bottom: 10px;
    border-radius: 8px;
    border: 2px solid #1e3a8a;;
    width: 60%;
    max-width: 800px;
    margin: 10px auto;
    transition: 
        transform 0.25s ease,
        border-color 0.25s ease,
        box-shadow 0.25s ease;
}

.match-bar:hover {
    border-color: #fca311;
    transform: scale(1.03);
    cursor: pointer;
}

.team {
    display: flex;
    align-items: center;
    gap: 10px;
    width: 30%;
    flex: 1;
}

.team img {
    width: 40px;
    height: 40px;
    object-fit: contain;
}

.team:first-child {
    justify-content: flex-start;
}

.team:last-child {
    justify-content: flex-end;
}

.match-center {
    text-align: center;
    width: 40%;
    flex: 1;
}

.score {
    font-size: 20px;
    font-weight: bold;
}

.date {
    font-size: 12px;
    opacity: 0.7;
}

.match-link {
    text-decoration: none;
    display: block;
    width: 100%;
}
</style>

