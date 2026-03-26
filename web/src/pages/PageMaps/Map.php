<?php
require_once '../../components/ComponentCardMaps/cardMaps.php';
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ma Page avec les Cartes</title>
    <link rel="stylesheet" href="../../components/ComponentNavbar/navbar.css">
    <link rel="stylesheet" href="cardMaps.css">
    <link rel="stylesheet" href="Map.css">
</head>

<body>
    <?php include '../../components/ComponentNavbar/navbar.php'; ?>

    <div class="main-content">
        <h1>Liste des Maps</h1>

        <div class="container">
            <?php echo createMapCard('Dust II', '2001 - "current"'); ?>

            <?php echo createMapCard('Mirage', '2013 - "current"'); ?>

            <?php echo createMapCard('Inferno', '1999 - "current"'); ?>

            <?php echo createMapCard('Nuke', '2000 - "current"'); ?>
        </div>
    </div>

</body>

</html>