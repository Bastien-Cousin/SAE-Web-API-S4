<?php
/**
 * Exemple d'utilisation du ApiClient dans tes pages
 */

// 1. Importe le helper au début de ta page
require_once __DIR__ . '/../includes/ApiClient.php';
$api = new ApiClient();

// ============ RÉCUPÉRER DES JOUEURS ============
$joueurs = $api->getPlayersByTeam(1);
$tous_joueurs = $api->getAllPlayers(0, 50);
$recherche = $api->searchPlayers('flusha');

// ============ RÉCUPÉRER DES ÉQUIPES ============
$toutes_equipes = $api->getAllTeams(0, 50);
$fnatic = $api->getTeamById(1);
$recherche_equipe = $api->searchTeams('fnatic');
$equipes_eu = $api->searchTeams('', 'eu');  // Toutes les équipes EU

// ============ RÉCUPÉRER DES MATCHS ============
$tous_matchs = $api->getAllMatches(0, 50);
$matchs_recents = $api->getRecentMatches(10);
$un_match = $api->getMatchById(123);
$matchs_fnatic = $api->getMatchesByTeam(1);
$recherche_matchs = $api->searchMatches('Finals');

// ============ RÉCUPÉRER DES TOURNOIS ============
$tous_tournois = $api->getAllTournaments(0, 50);
$un_tournoi = $api->getTournamentById(1000);
$tournois_2024 = $api->getTournamentsByYear(2024);
$stats_tournoi = $api->getTournamentStats(1000);

// ============ UTILISER LES DONNÉES DANS HTML ============
?>

<?php if ($joueurs): ?>
    <div class="joueurs-list">
        <?php foreach ($joueurs as $joueur): ?>
            <div class="joueur-card">
                <h3><?php echo htmlspecialchars($joueur['name']); ?></h3>
                <p>Nationalité: <?php echo htmlspecialchars($joueur['nationality']); ?></p>
                <p>Équipe: <?php echo htmlspecialchars($joueur['team_id'] ?? 'N/A'); ?></p>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <p>Erreur lors du chargement des joueurs</p>
<?php endif; ?>

<?php if ($matchs_recents): ?>
    <div class="matchs-list">
        <?php foreach ($matchs_recents as $match): ?>
            <div class="match-card">
                <h4><?php echo htmlspecialchars($match['tournament'] ?? 'N/A'); ?></h4>
                <p>Statut: <?php echo htmlspecialchars($match['status']); ?></p>
                <p>Best of: <?php echo htmlspecialchars($match['bestof'] ?? 'N/A'); ?></p>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
