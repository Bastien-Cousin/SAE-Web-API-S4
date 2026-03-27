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

        <div
            class="card-logo" style="<?= !empty($logo) ? "background-image: url('$logo');" : '' ?>">
            <?php if (empty($logo)): ?>
                <div class="logo-placeholder">?</div>
            <?php endif; ?>
        </div>
        <div class="card-info">
            <p class="info-pays">
                <img src="https://flagcdn.com/24x18/<?= strtolower($pays) ?>.png" alt="Drapeau <?= $pays ?>" title="<?= $pays ?>" onerror="this.style.display='none'">
            </p>
            <p class="info-pseudo"><?= $pseudo ?></p>
            <p class="info-nom"><?= $nom_prenom ?></p>
            <p
                class="info-age">
                <?= $age ?>
                ans
            </p>
            <div class="info-row">
                <span class="info-statut"><?= $statut ?></span>
                <span class="info-role"><?= $role ?></span>
            </div>
            <p class="info-argent"><?= $argent ?></p>
        </div>

    </div><?php
}
?>
