<?php
function createMapCard($nomMap, $competition, $imagePath = null, $matchCount = 0)
{
    if ($imagePath) {
        $imageSection = '<img src="' . htmlspecialchars($imagePath) . '" alt="' . htmlspecialchars($nomMap) . '" class="card-image">';
    } else {
        $imageSection = '';
    }

    return '
    <div class="map-card">
        <div class="card-image-container">
            ' . $imageSection . '
        </div>
        <div class="card-info">
            <h3 class="card-title">' . htmlspecialchars($nomMap) . '</h3>
            <p class="card-competition">Créée le : ' . htmlspecialchars($competition) . '</p>
            <p class="card-match-count">' . (
        $matchCount > 0
        ? htmlspecialchars($matchCount) . ' matchs joués'
        : 'Non joué en compétition'
    ) . '</p>
        </div>
    </div>';
}
?>