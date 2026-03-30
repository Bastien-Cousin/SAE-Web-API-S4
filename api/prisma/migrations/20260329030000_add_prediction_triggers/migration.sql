-- =====================================================
-- FONCTION: Valider une nouvelle prédiction
-- =====================================================
CREATE OR REPLACE FUNCTION validate_prediction_insert()
RETURNS TRIGGER AS $$
BEGIN
  -- Vérifier que l'utilisateur existe
  IF NOT EXISTS (SELECT 1 FROM "user" WHERE id = NEW.user_id) THEN
    RAISE EXCEPTION 'L''utilisateur (user_id: %) n''existe pas', NEW.user_id;
  END IF;
  
  -- Vérifier que le match existe
  IF NOT EXISTS (SELECT 1 FROM "match" WHERE pageid = NEW.match_id) THEN
    RAISE EXCEPTION 'Le match (match_id: %) n''existe pas', NEW.match_id;
  END IF;
  
  -- Vérifier que le match a 2 équipes valides
  IF NOT EXISTS (SELECT 1 FROM "match" WHERE pageid = NEW.match_id AND team1_id IS NOT NULL AND team2_id IS NOT NULL) THEN
    RAISE EXCEPTION 'Le match n''a pas d''équipes valides assignées';
  END IF;
  
  -- Vérifier que predicted_winner est 1 ou 2
  IF NEW.predicted_winner NOT IN (1, 2) THEN
    RAISE EXCEPTION 'predicted_winner doit être 1 ou 2, reçu: %', NEW.predicted_winner;
  END IF;
  
  -- Vérifier que confiance est entre 0-100 (si défini)
  IF NEW.confidence IS NOT NULL AND (NEW.confidence < 0 OR NEW.confidence > 100) THEN
    RAISE EXCEPTION 'La confiance doit être entre 0 et 100, reçue: %', NEW.confidence;
  END IF;
  
  -- Vérifier que le match n'est pas déjà terminé
  IF EXISTS (SELECT 1 FROM "match" WHERE pageid = NEW.match_id AND finished = TRUE) THEN
    RAISE EXCEPTION 'Impossible de prédire un match déjà terminé';
  END IF;
  
  -- Vérifier qu'il n'existe pas déjà une prédiction pour cet utilisateur et ce match
  IF EXISTS (SELECT 1 FROM "prediction" WHERE user_id = NEW.user_id AND match_id = NEW.match_id) THEN
    RAISE EXCEPTION 'L''utilisateur a déjà une prédiction pour ce match';
  END IF;
  
  RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- =====================================================
-- FONCTION: Empêcher la modification d'une prédiction si le match est terminé
-- =====================================================
CREATE OR REPLACE FUNCTION prevent_prediction_update_finished_match()
RETURNS TRIGGER AS $$
BEGIN
  -- Empêcher la modification si le match est terminé
  IF EXISTS (SELECT 1 FROM "match" WHERE pageid = NEW.match_id AND finished = TRUE) THEN
    RAISE EXCEPTION 'Impossible de modifier une prédiction si le match est terminé';
  END IF;
  
  -- Vérifier que predicted_winner reste valide (1 ou 2)
  IF NEW.predicted_winner NOT IN (1, 2) THEN
    RAISE EXCEPTION 'predicted_winner doit être 1 ou 2, reçu: %', NEW.predicted_winner;
  END IF;
  
  -- Vérifier que confiance reste valide (0-100)
  IF NEW.confidence IS NOT NULL AND (NEW.confidence < 0 OR NEW.confidence > 100) THEN
    RAISE EXCEPTION 'La confiance doit être entre 0 et 100, reçue: %', NEW.confidence;
  END IF;
  
  RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- =====================================================
-- TRIGGERS: INSERT et UPDATE pour PREDICTION
-- =====================================================
CREATE TRIGGER trigger_prediction_insert_validate
BEFORE INSERT ON "prediction"
FOR EACH ROW
EXECUTE FUNCTION validate_prediction_insert();

CREATE TRIGGER trigger_prediction_update_validate
BEFORE UPDATE ON "prediction"
FOR EACH ROW
WHEN (OLD.predicted_winner IS DISTINCT FROM NEW.predicted_winner OR OLD.confidence IS DISTINCT FROM NEW.confidence)
EXECUTE FUNCTION prevent_prediction_update_finished_match();
