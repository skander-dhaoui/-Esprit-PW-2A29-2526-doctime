<?php
// Views: Carte interactive Tunisie

if (!isset($pageTitle)) $pageTitle = 'Carte Interactive – Événements en Tunisie';
if (!isset($evenementsGeo)) $evenementsGeo = [];
if (!isset($statsGouvernorat)) $statsGouvernorat = [];
if (!isset($statsGlobales)) $statsGlobales = ['total' => 0, 'planifie' => 0, 'en_cours' => 0, 'termine' => 0];

// Ensure user is authenticated and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: index.php?page=login');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/theme-mode.css">
    <style>
        .main-content { margin-left: 260px; padding-top: 80px; }
        @media (max-width: 768px) { .main-content { margin-left: 0; } }
    </style>
</head>
<body>
<?php require __DIR__ . '/../sidebar.php'; ?>
<?php require __DIR__ . '/../layout_header.php'; ?>
<div class="main-content">

<div class="container-fluid mt-4">
    <!-- Titre -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h2><i class="fas fa-map me-2"></i><?= htmlspecialchars($pageTitle) ?></h2>
            <p class="text-muted">Répartition géographique des événements médicaux</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="index.php?page=carte&action=metiers" class="btn btn-info">
                <i class="fas fa-brain me-2"></i>Assistant IA Métiers
            </a>
        </div>
    </div>

    <!-- Statistiques globales -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body d-flex align-items-center">
                    <div style="flex: 1;">
                        <h5 class="card-title">Total Événements</h5>
                        <h2><?= $statsGlobales['total'] ?></h2>
                    </div>
                    <i class="fas fa-calendar fa-3x" style="opacity: 0.3;"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body d-flex align-items-center">
                    <div style="flex: 1;">
                        <h5 class="card-title">Planifiés</h5>
                        <h2><?= $statsGlobales['planifie'] ?></h2>
                    </div>
                    <i class="fas fa-clock fa-3x" style="opacity: 0.3;"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body d-flex align-items-center">
                    <div style="flex: 1;">
                        <h5 class="card-title">En cours</h5>
                        <h2><?= $statsGlobales['en_cours'] ?></h2>
                    </div>
                    <i class="fas fa-hourglass-half fa-3x" style="opacity: 0.3;"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body d-flex align-items-center">
                    <div style="flex: 1;">
                        <h5 class="card-title">Terminés</h5>
                        <h2><?= $statsGlobales['termine'] ?></h2>
                    </div>
                    <i class="fas fa-check-circle fa-3x" style="opacity: 0.3;"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtres et légende -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="fas fa-filter me-2"></i>Filtrer :
                    </h5>
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-outline-secondary active" data-status="all">
                            <i class="fas fa-globe me-1"></i>Tous
                        </button>
                        <button type="button" class="btn btn-outline-info" data-status="à venir">
                            <i class="fas fa-clock me-1"></i>À venir
                        </button>
                        <button type="button" class="btn btn-outline-warning" data-status="en_cours">
                            <i class="fas fa-hourglass-half me-1"></i>En cours
                        </button>
                        <button type="button" class="btn btn-outline-success" data-status="terminé">
                            <i class="fas fa-check-circle me-1"></i>Terminés
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="fas fa-key me-2"></i>Légende
                    </h5>
                    <small>
                        <span class="badge bg-info me-2">●</span>Planifié<br>
                        <span class="badge bg-warning me-2">●</span>En cours<br>
                        <span class="badge bg-success me-2">●</span>Terminé
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- Carte et Statistiques par gouvernorat -->
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0">
                        <i class="fas fa-map-location-dot me-2"></i>Carte Interactive – Tunisie
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div id="map" style="height: 500px; border-radius: 0 0 0.25rem 0.25rem;"></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-pie me-2"></i>Par Gouvernorat
                    </h5>
                </div>
                <div class="card-body" style="max-height: 520px; overflow-y: auto;">
                    <?php if (!empty($statsGouvernorat)): ?>
                        <?php foreach ($statsGouvernorat as $gov => $stats): ?>
                            <div class="mb-3 pb-3 border-bottom">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1">
                                            <i class="fas fa-location-dot text-danger me-2"></i>
                                            <?= htmlspecialchars($gov) ?>
                                        </h6>
                                        <small class="text-muted">
                                            📊 <?= $stats['total'] ?> événement<?= $stats['total'] > 1 ? 's' : '' ?>
                                        </small>
                                    </div>
                                    <span class="badge bg-primary"><?= $stats['total'] ?></span>
                                </div>
                                <div class="mt-2">
                                    <small>
                                        👥 <strong><?= $stats['participants'] ?></strong> participants
                                    </small>
                                </div>
                                <?php if (!empty($stats['specialites'])): ?>
                                    <div class="mt-2">
                                        <small class="text-muted">
                                            Spécialités: <?= htmlspecialchars(implode(', ', $stats['specialites'])) ?>
                                        </small>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>Aucun événement pour le moment.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Tableau détail des événements -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0">
                        <i class="fas fa-list me-2"></i>Détail des Événements
                    </h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Titre</th>
                                <th>Lieu</th>
                                <th>Gouvernorat</th>
                                <th>Date</th>
                                <th>Statut</th>
                                <th>Participants</th>
                                <th>Sponsors</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($evenementsGeo)): ?>
                                <?php foreach ($evenementsGeo as $ev): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($ev['titre'] ?? $ev['nom'] ?? 'Sans titre') ?></strong>
                                        </td>
                                        <td><?= htmlspecialchars($ev['lieu'] ?? '-') ?></td>
                                        <td>
                                            <span class="badge bg-light text-dark">
                                                <?= htmlspecialchars($ev['gouvernorat'] ?? '-') ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php 
                                            $dateDebut = $ev['date_debut'] ?? '';
                                            echo $dateDebut ? date('d/m/Y', strtotime($dateDebut)) : '-';
                                            ?>
                                        </td>
                        <td>
                            <?php
                            $statut = $ev['status'] ?? 'à venir';
                            $badges = [
                                'à venir' => 'info',
                                'en_cours' => 'warning',
                                'terminé' => 'success',
                                'annulé' => 'danger'
                            ];
                            $color = $badges[$statut] ?? 'secondary';
                            ?>
                            <span class="badge bg-<?= $color ?>">
                                <?= htmlspecialchars(ucfirst($statut)) ?>
                            </span>
                        </td>
                                        <td>
                                            <span class="badge bg-primary">
                                                <?= $ev['nb_participants'] ?? 0 ?>
                                            </span>
                                        </td>
                                        <td>
                                            <small><?= htmlspecialchars($ev['sponsor_nom'] ?? '-') ?></small>
                                        </td>
                                        <td>
                                            <a href="index.php?page=evenements_admin&action=show&id=<?= $ev['id'] ?>" 
                                               class="btn btn-sm btn-outline-primary" title="Voir">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                        Aucun événement pour le moment.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Leaflet CSS & JS pour la carte -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet-markercluster/1.5.1/leaflet.markercluster.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet-markercluster/1.5.1/MarkerCluster.min.css" />

<style>
    #map {
        border-radius: 0 0 0.25rem 0.25rem;
    }
    
    .card {
        border: 1px solid #e9ecef;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    }
    
    .badge {
        font-size: 0.75rem;
        padding: 0.35rem 0.65rem;
    }
    
    .table-responsive {
        font-size: 0.9rem;
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/theme-mode.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialiser la carte Leaflet
    const map = L.map('map').setView([34.8, 9.5], 6);
    
    // Ajouter la couche OpenStreetMap
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '© OpenStreetMap contributors',
        minZoom: 5
    }).addTo(map);
    
    // Récupérer les événements depuis l'API
    fetch('index.php?page=api_map&action=carte')
        .then(response => response.json())
        .then(data => {
            if (Array.isArray(data)) {
                data.forEach(event => {
                    if (event.lat && event.lng) {
                        const color = event.status === 'à venir' ? '#0dcaf0' : 
                                     event.status === 'en_cours' ? '#ffc107' : 
                                     '#198754';
                        
                        const marker = L.circleMarker([event.lat, event.lng], {
                            radius: 8,
                            fillColor: color,
                            color: '#000',
                            weight: 2,
                            opacity: 0.8,
                            fillOpacity: 0.7
                        }).addTo(map);
                        
                        const popupContent = `
                            <strong>${event.titre || event.nom}</strong><br>
                            <small>${event.lieu || ''}</small><br>
                            <span class="badge bg-${
                                event.status === 'à venir' ? 'info' : 
                                event.status === 'en_cours' ? 'warning' : 'success'
                            }">
                                ${event.status}
                            </span>
                        `;
                        
                        marker.bindPopup(popupContent);
                    }
                });
            }
        })
        .catch(error => console.error('Erreur lors du chargement de la carte:', error));
    
    // Filtres par statut
    document.querySelectorAll('[data-status]').forEach(btn => {
        btn.addEventListener('click', function() {
            const status = this.dataset.status;
            document.querySelectorAll('[data-status]').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            // Réimplémenter le filtrage si nécessaire
        });
    });
});
</script>
</div>
</body>
</html>
