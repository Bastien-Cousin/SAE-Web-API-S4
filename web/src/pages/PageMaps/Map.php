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
        <h1>Maps CS2</h1>

        <div class="container">
            <?php echo createMapCard(
                'Ancient',
                '2020 - "current"',
                'https://i.postimg.cc/VsHhCJMs/800px-CS2-de-ancient.png'
            ); ?>
            <?php echo createMapCard(
                'Anubis',
                '2020 - "current"',
                'https://i.postimg.cc/VsHhCJMs/800px-CS2-de-ancient.png'
            ); ?>
            <?php echo createMapCard(
                'Dust II',
                '2001 - "current"',
                'https://i.postimg.cc/VsHhCJMs/800px-CS2-de-ancient.png'
            ); ?>
            <?php echo createMapCard(
                'Dust II',
                '2001 - "current"',
                'https://i.postimg.cc/VsHhCJMs/800px-CS2-de-ancient.png'
            ); ?>
            <?php echo createMapCard(
                'Dust II',
                '2001 - "current"',
                'https://i.postimg.cc/VsHhCJMs/800px-CS2-de-ancient.png'
            ); ?>
            <?php echo createMapCard(
                'Dust II',
                '2001 - "current"',
                'https://i.postimg.cc/VsHhCJMs/800px-CS2-de-ancient.png'
            ); ?>
            <?php echo createMapCard(
                'Dust II',
                '2001 - "current"',
                'https://i.postimg.cc/VsHhCJMs/800px-CS2-de-ancient.png'
            ); ?>
            <?php echo createMapCard(
                'Dust II',
                '2001 - "current"',
                'https://i.postimg.cc/VsHhCJMs/800px-CS2-de-ancient.png'
            ); ?>
            <?php echo createMapCard(
                'Dust II',
                '2001 - "current"',
                'https://i.postimg.cc/VsHhCJMs/800px-CS2-de-ancient.png'
            ); ?>
            <?php echo createMapCard(
                'Dust II',
                '2001 - "current"',
                'https://i.postimg.cc/VsHhCJMs/800px-CS2-de-ancient.png'
            ); ?>
            <?php echo createMapCard(
                'Dust II',
                '2001 - "current"',
                'https://i.postimg.cc/VsHhCJMs/800px-CS2-de-ancient.png'
            ); ?>

        </div>
    </div>

</body>

</html>