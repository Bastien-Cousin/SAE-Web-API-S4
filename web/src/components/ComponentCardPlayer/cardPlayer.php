<?php
function afficherCarteJoueur(array $joueur): void
{
    $pays = htmlspecialchars($joueur['pays'] ?? 'N/A');
    $pseudo = htmlspecialchars($joueur['pseudo'] ?? 'N/A');
    $nom_prenom = htmlspecialchars($joueur['nom_prenom'] ?? 'N/A');
    $age = htmlspecialchars($joueur['age'] ?? 'N/A');
    $statut = htmlspecialchars($joueur['statut'] ?? 'N/A');
    $role = htmlspecialchars($joueur['role'] ?? 'N/A');
    $argent = htmlspecialchars($joueur['argent_gagne'] ?? 'N/A');
    $logo = htmlspecialchars($joueur['logo'] ?? '');
    $couleur = htmlspecialchars($joueur['couleur_logo'] ?? '#cccccc');
    ?>
    <div class="player-card" style="--card-color: <?= $couleur ?>;">

        <div class="card-logo">
            <?php if (!empty($logo)): ?>
                <img src="<?= $logo ?>" alt="Logo équipe">
            <?php else: ?>
                <div class="logo-placeholder">?</div>
            <?php endif; ?>
        </div>
        <div class="card-info">
            <p class="info-pays">
                <img src="https://flagcdn.com/24x18/<?= strtolower($pays) ?>.png" alt="Drapeau <?= $pays ?>"
                    title="<?= $pays ?>" onerror="this.style.display='none'">
            </p>
            <p class="info-pseudo"><?= $pseudo ?></p>
            <p class="info-nom"><?= $nom_prenom ?></p>
            <p class="info-age"><?= $age ?> ans</p>
            <div class="info-row">
                <span class="info-statut"><?= $statut ?></span>
                <span class="info-role"><?= $role ?></span>
            </div>
            <p class="info-argent"><?= $argent ?></p>
        </div>

    </div>
    <?php
}
?>

<style>
    .player-card {
        position: relative;
        width: 200px;
        border: 2px solid #ccc;
        border-radius: 16px;
        overflow: hidden;
        background: #fff;
        box-shadow: 2px 4px 10px rgba(0, 0, 0, 0.1);
    }

    .card-logo {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0.3;
        pointer-events: none;
    }

    .card-logo img {
        width: 130px;
        height: 130px;
        object-fit: contain;
    }

    .logo-placeholder {
        font-size: 60px;
        color: var(--card-color);
    }

    .card-info {
        position: relative;
        z-index: 1;
        padding: 14px 12px;
        text-align: center;
    }

    .card-info p {
        margin: 4px 0;
    }

    .info-pays {
        font-size: 12px;
        color: #666;
        text-align: left;
    }

    .info-pseudo {
        font-size: 15px;
        font-weight: bold;
    }

    .info-nom {
        font-size: 13px;
        color: #333;
    }

    .info-age {
        font-size: 13px;
        color: #555;
    }

    .info-row {
        display: flex;
        justify-content: space-around;
        margin: 6px 0;
        font-size: 12px;
        color: #444;
        gap: 8px;
    }

    .info-argent {
        font-size: 13px;
        font-weight: bold;
        color: #2a7d2a;
    }
</style>