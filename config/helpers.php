<?php
declare(strict_types=1);

/**
 * Fichier de compatibilité pour les anciennes fonctions
 * Le code doit utiliser la classe Helper pour les nouvelles implémentations
 * 
 * Exemple:
 *   ❌ Ancien: temps_ecoule_fr($date)
 *   ✅ Nouveau: Helper::tempsEcouleFr($date)
 */

require_once __DIR__ . '/Helper.php';

// ═══════════════════════════════════════════════════════════════════
// WRAPPERS DE COMPATIBILITÉ (À MIGRER VERS HELPER::)
// ═══════════════════════════════════════════════════════════════════

/**
 * @deprecated Utiliser Helper::tempsEcouleFr() à la place
 */
function temps_ecoule_fr(?string $dateTime): string {
    return Helper::tempsEcouleFr($dateTime);
}

/**
 * @deprecated Utiliser Helper::dureeEvenementJours() à la place
 */
function duree_evenement_jours(string $dateDebut, string $dateFin): int {
    return Helper::dureeEvenementJours($dateDebut, $dateFin);
}
