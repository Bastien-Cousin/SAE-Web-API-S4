<?php

$user = null;

if (isset($_COOKIE['auth_token'])) {

    $token = $_COOKIE['auth_token'];

    $options = [
        "http" => [
            "header" => "Authorization: Bearer $token"
        ]
    ];

    $context = stream_context_create($options);

    $result = @file_get_contents("http://api:3070/api/auth/me", false, $context);

    if ($result !== false) {
        $data = json_decode($result, true);
        if (isset($data["user"])) {
            $user = $data["user"];
        }
    }
}
?>
<nav class="navbar">
    <div class="logo_area"><img src='https://i.postimg.cc/FK3c9qmv/Gemini-Generated-Image-ip05muip05muip05-removebg-preview.png' alt='Logo du site'></div>
    <div class="nav_buttons">
        <ul>
            <li>
                <a href="/index.php">Accueil</a>
            </li>

            <li class="dropdown">
                <a href="#">Données</a>
                <ul class="dropdown-menu">
                    <li>
                        <a href="/pages/PageAccueilEquipe/AccueilEquipe.php">Équipes</a>
                    </li>
                    <li>
                        <a href="/pages/PageJoueur/Joueur.php">Joueurs</a>
                    </li>
                    <li>
                        <a href="/pages/PageMaps/Map.php">Maps</a>
                    </li>
                </ul>
            </li>

            <li class="dropdown">
                <a href="#">Rencontres</a>
                <ul class="dropdown-menu">
                    <li>
                        <a href="/pages/PageAccueilTournoi/AccueilTournoi.php">Tournois</a>
                    </li>
                    <li>
                        <a href="/pages/PageAccueilMatch/AccueilMatch.php">Matchs</a>
                    </li>
                </ul>
            </li>

            <li>
                <a href="/pages/PagePredictions/Prediction.php">Prédiction</a>
            </li>
        </ul>
    </div>
    <?php if ($user): ?>
        <div class="connected">
            <span>Bonjour, <?= htmlspecialchars($user["username"]) ?></span>
            <a href="/components/ComponentNavbar/logout.php">
                <button>Se déconnecter</button>
            </a>
        </div>
    <?php else: ?>
        <div class="auth_logo">
            <a href="/pages/PageConnexionInscription/Connexion.php">
                <img src="https://i.postimg.cc/mryDpGRm/login.png" alt="S'authentifier">
            </a>
        </div>
    <?php endif; ?>
</nav>

