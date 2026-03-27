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
    <link rel="stylesheet" href="../../components/ComponentCardMaps/cardMaps.css">
    <link rel="stylesheet" href="Map.css">
</head>

<body>
    <?php include '../../components/ComponentNavbar/navbar.php'; ?>

    <div class="main-content">
        <div class="header-section">
            <h1>Maps CS2</h1>
            <a href="FormulaireMap.php" class="btn-add-map">+ Nouvelle Map</a>
        </div>

        <div class="container">
            <?php echo createMapCard(
                'Ancient',
                '3 décembre 2020',
                'https://i.postimg.cc/VsHhCJMs/800px-CS2-de-ancient.png'
            ); ?>
            <?php echo createMapCard(
                'Anubis',
                '31 mars 2020',
                'https://i.postimg.cc/rwN2w9f9/800px-CS2-de-anubis.png'
            ); ?>
            <?php echo createMapCard(
                'Dust II',
                'mars 2001',
                'https://i.postimg.cc/1X8ygN8D/800px-CS2-Dust-2-A-Site.jpg'
            ); ?>
            <?php echo createMapCard(
                'Inferno',
                '2001',
                'https://i.postimg.cc/5tt9cGfS/800px-CS2-de-inferno.png'
            ); ?>
            <?php echo createMapCard(
                'Mirage',
                '12 juin 2013',
                'https://i.postimg.cc/MKG9QmZs/800px-CS2-de-mirage.png'
            ); ?>
            <?php echo createMapCard(
                'Nuke',
                'novembre 1999',
                'https://i.postimg.cc/NM4xy53Q/800px-CS2-de-nuke.png'
            ); ?>
            <?php echo createMapCard(
                'Overpass',
                '18 décembre 2013',
                'https://i.postimg.cc/yxRcmx58/800px-CS2-de-overpass.png'
            ); ?>
            <?php echo createMapCard(
                'Train',
                '1999',
                'https://i.postimg.cc/zBgVMMkH/800px-CS2-de-train.png'
            ); ?>
            <?php echo createMapCard(
                'Vertigo',
                '1999',
                'https://i.postimg.cc/YS49GVTt/800px-CS2-de-vertigo.png'
            ); ?>
            <?php echo createMapCard(
                'Office ',
                '2000',
                'https://i.postimg.cc/52DJGtkW/image-2026-03-26-151546491.png'
            ); ?>
            <?php echo createMapCard(
                'Italy',
                '8 juin 2000',
                'https://i.postimg.cc/q7ffw5gZ/800px-CS2-Italy-CT-Spawn.jpg'
            ); ?>

        </div>
    </div>

</body>

</html>