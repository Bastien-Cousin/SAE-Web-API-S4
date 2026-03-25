<?php
function createMapCard($nomMap, $competition, $imagePath = null)
{
    if ($imagePath) {
        $imageSection = '<img src="' . htmlspecialchars($imagePath) . '" alt="' . htmlspecialchars($nomMap) . '" class="card-image">';
    } else {
        $imageSection = '
        <div class="card-placeholder">
            <div class="placeholder-line line-1"></div>
            <div class="placeholder-line line-2"></div>
            <div class="placeholder-line line-3"></div>
            <div class="placeholder-line line-4"></div>
        </div>';
    }

    return '
    <div class="map-card">
        <div class="card-image-container">
            ' . $imageSection . '
        </div>
        <div class="card-info">
            <h3 class="card-title">' . htmlspecialchars($nomMap) . '</h3>
            <p class="card-competition">En compétition : ' . htmlspecialchars($competition) . '</p>
        </div>
    </div>';
}
?>