<?php
/**
 * ApiClient - Helper pour faire des appels à l'API Esports
 * 
 * Centralize tous les appels API pour éviter la duplication de code
 * et ne jamais accéder directement à la base de données depuis le frontend.
 */
class ApiClient
{
    private $baseUrl = "http://api:3070/api";
    private $timeout = 10;
    private $token = null;

    /**
     * Effectue une requête HTTP GET
     */
    private function get($endpoint)
    {
        $url = $this->baseUrl . $endpoint;
        $opts = [
            'http' => [
                'method' => 'GET',
                'timeout' => $this->timeout,
                'header' => 'User-Agent: Esports-Web/1.0'
            ]
        ];

        try {
            $context = stream_context_create($opts);
            $response = file_get_contents($url, false, $context);
            return json_decode($response, true);
        } catch (Exception $e) {
            error_log("API Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Effectue une requête HTTP POST
     */
    private function post($endpoint, $data = [])
    {
        $url = $this->baseUrl . $endpoint;
        $payload = json_encode($data);
        $headers = [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($payload)
        ];

        // Ajouter le token d'authentification s'il existe
        if ($this->token) {
            $headers[] = 'Authorization: Bearer ' . $this->token;
        }

        $opts = [
            'http' => [
                'method' => 'POST',
                'header' => $headers,
                'content' => $payload,
                'timeout' => $this->timeout
            ]
        ];

        try {
            $context = stream_context_create($opts);
            $response = file_get_contents($url, false, $context);
            return json_decode($response, true);
        } catch (Exception $e) {
            error_log("API Error: " . $e->getMessage());
            return null;
        }
    }

    // ============ PLAYERS ============

    /**
     * Récupérer tous les joueurs
     * @param int $skip Nombre de joueurs à sauter
     * @param int $take Nombre de joueurs à retourner
     */
    public function getAllPlayers($skip = 0, $take = 20)
    {
        return $this->get("/players?skip=$skip&take=$take");
    }

    /**
     * Récupérer un joueur par ID
     * @param string $id ID du joueur
     */
    public function getPlayerById($id)
    {
        return $this->get("/players/$id");
    }

    /**
     * Récupérer les joueurs d'une équipe
     * @param string $teamId ID de l'équipe
     */
    public function getPlayersByTeam($teamId)
    {
        return $this->get("/players/team/$teamId");
    }

    /**
     * Rechercher des joueurs
     * @param string $query Terme de recherche
     * @param string|null $nationality Filtrer par nationalité (optional)
     */
    public function searchPlayers($query, $nationality = null)
    {
        $endpoint = "/players/search?q=" . urlencode($query);
        if ($nationality) {
            $endpoint .= "&nationality=" . urlencode($nationality);
        }
        return $this->get($endpoint);
    }

    // ============ TEAMS ============

    /**
     * Récupérer toutes les équipes
     * @param int $skip Nombre d'équipes à sauter
     * @param int $take Nombre d'équipes à retourner
     */
    public function getAllTeams($skip = 0, $take = 20)
    {
        return $this->get("/teams?skip=$skip&take=$take");
    }

    /**
     * Récupérer une équipe par ID
     * @param string $id ID de l'équipe
     */
    public function getTeamById($id)
    {
        return $this->get("/teams/$id");
    }

    /**
     * Rechercher des équipes
     * @param string $query Terme de recherche
     * @param string|null $region Filtrer par région (optional)
     */
    public function searchTeams($query, $region = null)
    {
        $endpoint = "/teams/search?q=" . urlencode($query);
        if ($region) {
            $endpoint .= "&region=" . urlencode($region);
        }
        return $this->get($endpoint);
    }

    // ============ MATCHES ============

    /**
     * Récupérer tous les matchs
     * @param int $skip Nombre de matchs à sauter
     * @param int $take Nombre de matchs à retourner
     */
    public function getAllMatches($skip = 0, $take = 20)
    {
        return $this->get("/matches?skip=$skip&take=$take");
    }

    /**
     * Récupérer un match par ID
     * @param string $id ID du match
     */
    public function getMatchById($id)
    {
        return $this->get("/matches/$id");
    }

    /**
     * Récupérer les matchs récents
     * @param int $limit Nombre de matchs à retourner
     */
    public function getRecentMatches($limit = 10)
    {
        return $this->get("/matches/recent?limit=$limit");
    }

    /**
     * Récupérer les matchs d'une équipe
     * @param string $teamId ID de l'équipe
     */
    public function getMatchesByTeam($teamId)
    {
        return $this->get("/matches/team/$teamId");
    }

    /**
     * Rechercher des matchs
     * @param string $query Terme de recherche (tournoi, équipe)
     */
    public function searchMatches($query)
    {
        return $this->get("/matches/search?q=" . urlencode($query));
    }

    // ============ TOURNAMENTS ============

    /**
     * Récupérer tous les tournois
     * @param int $skip Nombre de tournois à sauter
     * @param int $take Nombre de tournois à retourner
     */
    public function getAllTournaments($skip = 0, $take = 20)
    {
        return $this->get("/tournaments?skip=$skip&take=$take");
    }

    /**
     * Récupérer un tournoi par ID
     * @param string $id ID du tournoi
     */
    public function getTournamentById($id)
    {
        return $this->get("/tournaments/$id");
    }

    /**
     * Rechercher des tournois
     * @param string $query Terme de recherche
     */
    public function searchTournaments($query)
    {
        return $this->get("/tournaments/search?q=" . urlencode($query));
    }

    /**
     * Récupérer les tournois d'une année
     * @param string $year Année (YYYY)
     */
    public function getTournamentsByYear($year)
    {
        return $this->get("/tournaments/year/$year");
    }

    /**
     * Récupérer les statistiques d'un tournoi
     * @param string $id ID du tournoi
     */
    public function getTournamentStats($id)
    {
        return $this->get("/tournaments/stats/$id");
    }

    // ============ PREDICTIONS ============

    /**
     * Créer une nouvelle prédiction
     * @param int $userId ID utilisateur
     * @param string $matchId ID du match
     * @param int $predictedWinner Équipe gagnante (1 ou 2)
     * @param array $games Tableau des 5 jeux avec leurs prédictions
     * @param int|null $confidence Confiance de la prédiction (0-100)
     */
    public function createPrediction($userId, $matchId, $predictedWinner, $games, $confidence = null)
    {
        $data = [
            'user_id' => $userId,
            'match_id' => $matchId,
            'predicted_winner' => $predictedWinner,
            'games' => $games,
            'confidence' => $confidence
        ];
        return $this->post("/predictions", $data);
    }

    /**
     * Récupérer une prédiction par ID
     * @param int $id ID de la prédiction
     */
    public function getPredictionById($id)
    {
        return $this->get("/predictions/$id");
    }

    /**
     * Récupérer les prédictions d'un utilisateur
     * @param int $userId ID utilisateur
     */
    public function getPredictionsByUser($userId)
    {
        return $this->get("/predictions/user/$userId");
    }

    /**
     * Récupérer les prédictions pour un match
     * @param string $matchId ID du match
     */
    public function getPredictionsByMatch($matchId)
    {
        return $this->get("/predictions/match/$matchId");
    }

    /**
     * Mettre à jour une prédiction
     * @param int $id ID de la prédiction
     * @param array $data Données à mettre à jour
     */
    public function updatePrediction($id, $data)
    {
        // Implementation du PATCH via POST avec override
        $opts = [
            'http' => [
                'method' => 'PATCH',
                'header' => 'Content-Type: application/json',
                'content' => json_encode($data),
                'timeout' => $this->timeout
            ]
        ];

        try {
            $context = stream_context_create($opts);
            $response = file_get_contents($this->baseUrl . "/predictions/$id", false, $context);
            return json_decode($response, true);
        } catch (Exception $e) {
            error_log("API Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Supprimer une prédiction
     * @param int $id ID de la prédiction
     */
    public function deletePrediction($id)
    {
        $opts = [
            'http' => [
                'method' => 'DELETE',
                'timeout' => $this->timeout
            ]
        ];

        try {
            $context = stream_context_create($opts);
            $response = file_get_contents($this->baseUrl . "/predictions/$id", false, $context);
            return json_decode($response, true);
        } catch (Exception $e) {
            error_log("API Error: " . $e->getMessage());
            return null;
        }
    }

    // ============ AUTHENTICATION ============

    /**
     * Définir le token JWT pour les requêtes authentifiées
     * @param string $token Token JWT
     */
    public function setToken($token)
    {
        $this->token = $token;
    }

    /**
     * Récupérer le token JWT actuel
     * @return string|null Token JWT ou null
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * Inscription - créer un nouvel utilisateur
     * @param string $email Email de l'utilisateur
     * @param string $username Nom d'utilisateur
     * @param string $password Mot de passe
     * @return array Réponse de l'API avec token et user
     */
    public function signUp($email, $username, $password)
    {
        return $this->post("/auth/sign-up", [
            'email' => $email,
            'username' => $username,
            'password' => $password
        ]);
    }

    /**
     * Connexion - authentifier un utilisateur
     * @param string $email Email de l'utilisateur
     * @param string $password Mot de passe
     * @return array Réponse de l'API avec token et user
     */
    public function signIn($email, $password)
    {
        return $this->post("/auth/sign-in", [
            'email' => $email,
            'password' => $password
        ]);
    }

    /**
     * Récupérer les informations de l'utilisateur connecté
     * @return array Données utilisateur
     */
    public function getMe()
    {
        $authHeader = 'Authorization: Bearer ' . $this->token;
        $opts = [
            'http' => [
                'method' => 'GET',
                'header' => $authHeader,
                'timeout' => $this->timeout
            ]
        ];

        try {
            $context = stream_context_create($opts);
            $response = file_get_contents($this->baseUrl . "/auth/me", false, $context);
            return json_decode($response, true);
        } catch (Exception $e) {
            error_log("API Error: " . $e->getMessage());
            return null;
        }
    }
}
?>

