<?php
declare(strict_types=1);

/**
 * Temps écoulé depuis une date passée, en français (ex. « il y a 3 jours »).
 */
function temps_ecoule_fr(?string $dateTime): string {
    if ($dateTime === null || trim($dateTime) === '') {
        return '';
    }
    $ts = strtotime($dateTime);
    if ($ts === false) {
        return '';
    }
    $sec = time() - $ts;
    if ($sec < 0) {
        return '';
    }
    if ($sec < 60) {
        return 'à l\'instant';
    }
    $min = (int) floor($sec / 60);
    if ($min < 60) {
        return $min === 1 ? 'il y a 1 minute' : "il y a {$min} minutes";
    }
    $h = (int) floor($min / 60);
    if ($h < 24) {
        return $h === 1 ? 'il y a 1 heure' : "il y a {$h} heures";
    }
    $d = (int) floor($h / 24);
    if ($d < 7) {
        return $d === 1 ? 'il y a 1 jour' : "il y a {$d} jours";
    }
    $w = (int) floor($d / 7);
    if ($w < 5) {
        return $w === 1 ? 'il y a 1 semaine' : "il y a {$w} semaines";
    }
    $m = (int) floor($d / 30);
    if ($m < 12) {
        return $m <= 1 ? 'il y a 1 mois' : "il y a {$m} mois";
    }
    $y = (int) floor($d / 365);
    return $y <= 1 ? 'il y a plus d\'un an' : "il y a {$y} ans";
}

/**
 * Nombre de jours calendaires entre deux dates (inclus).
 */
function duree_evenement_jours(string $dateDebut, string $dateFin): int {
    $d1 = DateTime::createFromFormat('Y-m-d', $dateDebut);
    $d2 = DateTime::createFromFormat('Y-m-d', $dateFin);
    if (!$d1 || !$d2) {
        return 0;
    }
    $d1->setTime(0, 0, 0);
    $d2->setTime(0, 0, 0);
    return (int) $d1->diff($d2)->days + 1;
}
