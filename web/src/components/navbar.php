<nav class="navbar">
    <div class="logo_area"><img src="../img/logo_site.png" alt="Logo du site"></div>
    <div class="nav_buttons">
        <ul>
            <li>
                <a href="/index.php">Accueil</a>
            </li>

            <li class="dropdown">
                <a href="#">Données</a>
                <ul class="dropdown-menu">
                    <li>
                        <a href="/pages/AccueilEquipe.php">Équipes</a>
                    </li>
                    <li>
                        <a href="/pages/Joueur.php">Joueurs</a>
                    </li>
                    <li>
                        <a href="/pages/Map.php">Maps</a>
                    </li>
                </ul>
            </li>

            <li class="dropdown">
                <a href="#">Rencontres</a>
                <ul class="dropdown-menu">
                    <li>
                        <a href="/pages/AccueilTournoi.php">Tournois</a>
                    </li>
                    <li>
                        <a href="/pages/AccueilMatch.php">Matchs</a>
                    </li>
                </ul>
            </li>

            <li>
                <a href="/pages/Prediction.php">Prédiction</a>
            </li>
        </ul>
    </div>
    <div class="auth_logo">
        <a href="/pages/Connexion.php">
            <img src="../img/login.png" alt="S'authentifier">
        </a>
    </div>
</nav>


<style>
    .navbar {
        display: flex;
        justify-content: space-between;
        align-items: stretch;
        padding: 0;
        background-color: #1b265c;
        height: 80px;
        position: relative;
        overflow: visible;
    }

    .navbar .logo_area {
        position: relative;
        width: 15%;
        height: 100%;
        background-color: #fca311;
        clip-path: polygon(0 0, 100% 0, 85% 100%, 0% 100%);
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .navbar .logo_area img {
        max-height: 100px;
        width: auto;
        object-fit: contain;
        transform: translateX(-10px);
    }

    .navbar ul {
        display: flex;
        gap: 75px;
        list-style: none;
        margin: 0;
        padding: 0;
        align-items: center;
        font-size: 150%;
    }

    .navbar a {
        color: #e5e5e5;
        text-decoration: none;
    }

    .navbar a:hover {
        color: #fca311;
    }

    .nav_buttons {
        flex: 1;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .navbar .auth_logo {
        width: 80px;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .navbar .auth_logo img {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        cursor: pointer;
        transition: 0.2s;
    }

    .navbar .auth_logo img:hover {
        transform: scale(1.1);
    }

    .navbar .dropdown {
        position: relative;
    }

    .navbar .dropdown-menu {
        position: absolute;
        top: 100%;
        left: -35px;
        background-color: #1b265c;
        display: none;
        flex-direction: column;
        min-width: 180px;
        padding: 5px 0;
        border-radius: 5px;
        z-index: 1000;
        gap: 10px;
    }

    .navbar .dropdown-menu li a {
        font-size: 20px;
        display: block;
        padding: 4px 0;
    }

    .navbar .dropdown:hover .dropdown-menu {
        display: flex;
    }

    .dropdown > a::after {
        right: 35px;
        content: " ▼";
        font-size: 12px;
    }
</style>

