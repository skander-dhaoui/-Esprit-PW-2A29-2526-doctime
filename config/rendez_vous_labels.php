<?php

/**
 * Statuts RDV en base (enum) → valeur canonique pour match / boutons.
 * Tolère variantes d’encodage (ex. suffixe mal décodé après « confir… », « termin… »).
 */
function rendez_vous_statut_canonical(?string $statut): string {
    $raw = trim((string)$statut);
    if ($raw === '') {
        return 'en_attente';
    }

    $lower = mb_strtolower($raw, 'UTF-8');
    $compact = str_replace(['_', ' ', '-'], '', $lower);

    if ($compact === 'enattente') {
        return 'en_attente';
    }

    if (preg_match('/^confir/u', $lower)) {
        return 'confirmé';
    }
    if (preg_match('/^termin/u', $lower)) {
        return 'terminé';
    }
    if (preg_match('/^annul/u', $lower)) {
        return 'annulé';
    }

    return match ($lower) {
        'confirmé' => 'confirmé',
        'terminé' => 'terminé',
        'annulé' => 'annulé',
        'en_attente' => 'en_attente',
        default => 'en_attente',
    };
}

/** Libellé français pour affichage HTML (toujours UTF-8 correct dans le code source). */
function rendez_vous_statut_libelle(?string $statut): string {
    return match (rendez_vous_statut_canonical($statut)) {
        'en_attente' => 'En attente',
        'confirmé' => 'Confirmé',
        'terminé' => 'Terminé',
        'annulé' => 'Annulé',
        default => 'En attente',
    };
}
