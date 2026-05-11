<?php
// Carte Tunisie — même shell back-office que le reste de Valorys (layout_header / layout_footer)

if (!isset($pageTitle)) {
    $pageTitle = 'Carte Interactive – Événements en Tunisie';
}
if (!isset($evenementsGeo)) {
    $evenementsGeo = [];
}
if (!isset($evenementsGeoJson)) {
    $evenementsGeoJson = '[]';
}
if (!isset($statsGouvernorat)) {
    $statsGouvernorat = [];
}
if (!isset($statsGlobales)) {
    $statsGlobales = ['total' => 0, 'planifie' => 0, 'en_cours' => 0, 'termine' => 0, 'annule' => 0];
}

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    header('Location: index.php?page=login');
    exit;
}

$boBodyClass = 'page-carte-adm';
require __DIR__ . '/../layout_header.php';
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" crossorigin="anonymous" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" crossorigin="anonymous" />
<style>
    #map { min-height: 500px; border-radius: 0 0 12px 12px; z-index: 1; position: relative; }
    /* Calques Leaflet au-dessus des tuiles (évite une carte « vide » si z-index global trop bas). */
    #map .leaflet-pane.leaflet-marker-pane { z-index: 650; }
    #map .leaflet-pane.leaflet-overlay-pane { z-index: 550; }
    .bo-map-filter .btn.active { box-shadow: inset 0 0 0 2px rgba(21,128,61,.45); font-weight: 600; }
    /* Pastille horaire : vert seulement (sans bleu/cyan du dégradé global). */
    body.page-carte-adm .topbar .badge-time {
        background: #15803d !important;
        background-image: none !important;
    }
    .bo-leg-plan { background-color: #059669 !important; color: #fff !important; }
</style>

<div class="container-fluid pb-4">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3 mb-lg-4">
        <p class="text-muted small mb-0">Répartition géographique des événements médicaux</p>
        <a href="index.php?page=carte&action=metiers" class="btn btn-sm btn-outline-success"><i class="fas fa-wand-magic-sparkles me-1"></i>Assistant IA Métiers</a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card stat-card border-0" style="background:linear-gradient(135deg,#52525b,#3f3f46);">
                <div class="stat-icon"><i class="fas fa-calendar"></i></div>
                <p>Total événements</p>
                <h3><?= (int)($statsGlobales['total'] ?? 0) ?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card border-0" style="background:linear-gradient(135deg,#059669,#047857);">
                <div class="stat-icon"><i class="fas fa-clock"></i></div>
                <p>Planifiés</p>
                <h3><?= (int)($statsGlobales['planifie'] ?? 0) ?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card border-0" style="background:linear-gradient(135deg,#ffc107,#e0a800);">
                <div class="stat-icon"><i class="fas fa-hourglass-half"></i></div>
                <p>En cours</p>
                <h3><?= (int)($statsGlobales['en_cours'] ?? 0) ?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card border-0" style="background:linear-gradient(135deg,#198754,#157347);">
                <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                <p>Terminés</p>
                <h3><?= (int)($statsGlobales['termine'] ?? 0) ?></h3>
            </div>
        </div>
    </div>
    <?php if (($statsGlobales['annule'] ?? 0) > 0): ?>
        <p class="small text-muted mb-3"><i class="fas fa-ban me-1 text-danger"></i><?= (int)$statsGlobales['annule'] ?> événement(s) annulé(s) — visible via le filtre « Annulés ».</p>
    <?php endif; ?>

    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body py-3">
                    <div class="row g-3 align-items-center">
                        <div class="col-lg-8">
                            <h6 class="card-title mb-2 mb-lg-3"><i class="fas fa-filter me-2"></i>Filtrer</h6>
                            <div class="bo-map-filter d-flex flex-wrap gap-2" role="group">
                                <button type="button" class="btn btn-outline-secondary active" data-status="all"><i class="fas fa-globe me-1"></i>Tous</button>
                                <button type="button" class="btn btn-outline-dark" data-status="à venir"><i class="fas fa-clock me-1"></i>Planifiés</button>
                                <button type="button" class="btn btn-outline-warning" data-status="en_cours"><i class="fas fa-play me-1"></i>En cours</button>
                                <button type="button" class="btn btn-outline-success" data-status="terminé"><i class="fas fa-check me-1"></i>Terminés</button>
                                <button type="button" class="btn btn-outline-danger" data-status="annulé"><i class="fas fa-ban me-1"></i>Annulés</button>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <h6 class="text-muted text-uppercase small mb-2"><i class="fas fa-key me-2"></i>Légende</h6>
                            <div class="d-flex flex-wrap gap-2 gap-lg-3 small">
                                <span><span class="badge bo-leg-plan me-1">●</span>Planifié</span>
                                <span><span class="badge bg-warning text-dark me-1">●</span>En cours</span>
                                <span><span class="badge bg-success me-1">●</span>Terminé</span>
                                <span><span class="badge bg-danger me-1">●</span>Annulé</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0"><i class="fas fa-map-location-dot me-2"></i>Vue Tunisie</h5>
                </div>
                <div class="card-body p-0">
                    <div id="map"></div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Par gouvernorat</h5>
                </div>
                <div class="card-body" style="max-height:540px;overflow-y:auto;">
                    <?php if (!empty($statsGouvernorat)): ?>
                        <?php foreach ($statsGouvernorat as $gov => $stats): ?>
                            <div class="mb-3 pb-3 border-bottom">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1"><i class="fas fa-location-dot text-danger me-2"></i><?= htmlspecialchars((string)$gov) ?></h6>
                                        <small class="text-muted"><?= (int)$stats['total'] ?> événement<?= $stats['total'] > 1 ? 's' : '' ?></small>
                                    </div>
                                    <span class="badge rounded-pill bg-secondary"><?= (int)$stats['total'] ?></span>
                                </div>
                                <div class="mt-2 small"><strong><?= (int)($stats['participants'] ?? 0) ?></strong> participant(s)</div>
                                <?php if (!empty($stats['specialites'])): ?>
                                    <div class="mt-2 small text-muted">Spécialités : <?= htmlspecialchars(implode(', ', $stats['specialites'])) ?></div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="alert alert-secondary mb-0"><i class="fas fa-info-circle me-2"></i>Aucun événement géolocalisé pour le moment.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0"><i class="fas fa-list me-2"></i>Détail des événements</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Titre</th>
                                <th>Lieu</th>
                                <th>Gouvernorat</th>
                                <th>Date</th>
                                <th>Statut</th>
                                <th>Participants</th>
                                <th>Sponsor</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($evenementsGeo)): ?>
                                <?php foreach ($evenementsGeo as $ev): ?>
                                    <?php
                                    $statut = $ev['status'] ?? 'à venir';
                                    $badges = [
                                        'à venir' => 'secondary',
                                        'en_cours' => 'warning',
                                        'terminé' => 'success',
                                        'annulé' => 'danger',
                                    ];
                                    $color = $badges[$statut] ?? 'secondary';
                                    ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($ev['titre'] ?? $ev['nom'] ?? 'Sans titre') ?></strong></td>
                                        <td><?= htmlspecialchars((string)($ev['lieu'] ?? '-')) ?></td>
                                        <td><span class="badge bg-light text-dark"><?= htmlspecialchars((string)($ev['gouvernorat'] ?? '-')) ?></span></td>
                                        <td><?php $d = $ev['date_debut'] ?? ''; echo $d ? date('d/m/Y', strtotime((string)$d)) : '-'; ?></td>
                                        <td><span class="badge bg-<?= $color ?>"><?= htmlspecialchars($statut) ?></span></td>
                                        <td><span class="badge bg-secondary"><?= (int)($ev['nb_participants'] ?? 0) ?></span></td>
                                        <td><small><?= htmlspecialchars((string)($ev['sponsor_nom'] ?? '-')) ?></small></td>
                                        <td>
                                            <a href="index.php?page=evenements_admin&action=show&id=<?= (int)$ev['id'] ?>" class="btn btn-sm btn-outline-secondary" title="Voir"><i class="fas fa-eye"></i></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="8" class="text-center text-muted py-4">Aucun événement.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="https://cdn.jsdelivr.net/npm/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.min.js" crossorigin="anonymous"></script>
<script>
(function () {
    var EVENTS = <?= (isset($evenementsGeoJson) && is_string($evenementsGeoJson) && $evenementsGeoJson !== '') ? $evenementsGeoJson : '[]' ?>;
    if (!Array.isArray(EVENTS)) {
        EVENTS = [];
    }

    var map = L.map('map', { scrollWheelZoom: false }).setView([34.8, 9.5], 6);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 18,
        attribution: '© OpenStreetMap'
    }).addTo(map);

    var cluster;
    if (typeof L.markerClusterGroup === 'function') {
        cluster = L.markerClusterGroup({ maxClusterRadius: 55 });
    } else {
        cluster = L.layerGroup();
    }
    map.addLayer(cluster);

    function statusColor(st) {
        if (st === 'à venir') return '#059669';
        if (st === 'en_cours') return '#ffc107';
        if (st === 'terminé') return '#198754';
        if (st === 'annulé') return '#dc3545';
        return '#6c757d';
    }

    var markers = [];

    function clearMarkers() {
        markers.forEach(function (m) { cluster.removeLayer(m); });
        markers = [];
    }

    function renderMarkers(filter) {
        clearMarkers();
        EVENTS.forEach(function (event) {
            if (event.lat == null || event.lng == null) return;
            var la = Number(event.lat), ln = Number(event.lng);
            if (!Number.isFinite(la) || !Number.isFinite(ln)) return;
            if (filter !== 'all' && event.status !== filter) return;
            var color = statusColor(event.status);
            var marker = L.circleMarker([la, ln], {
                radius: 9,
                fillColor: color,
                color: '#1e293b',
                weight: 2,
                opacity: 0.9,
                fillOpacity: 0.82
            });
            var gov = String(event.gouvernorat || '').replace(/</g, '&lt;');
            var spec = String(event.specialite || '').replace(/</g, '&lt;');
            var np = parseInt(event.nb_participants, 10) || 0;
            var popup = '<strong>' + String(event.titre || '').replace(/</g, '&lt;') + '</strong><br>' +
                '<small class="text-muted">' + String(event.lieu || '').replace(/</g, '&lt;') + '</small><br>' +
                (gov ? '<small><i class="fas fa-map-marker-alt"></i> ' + gov + '</small><br>' : '') +
                (spec ? '<small>Catégorie : ' + spec + '</small><br>' : '') +
                '<span class="badge bg-secondary mt-1">' + String(event.status || '').replace(/</g, '&lt;') + '</span> ' +
                '<span class="badge bg-light text-dark mt-1">' + np + ' part.</span>';
            marker.bindPopup(popup);
            cluster.addLayer(marker);
            markers.push(marker);
        });
        var tunisia = [[30.12, 7.30], [37.68, 12.00]];
        if (markers.length === 0) {
            map.fitBounds(tunisia, { padding: [14, 14] });
        } else {
            try {
                var b = cluster.getBounds();
                if (b && b.isValid && b.isValid()) {
                    map.fitBounds(b.pad(0.12), { maxZoom: markers.length === 1 ? 11 : 10, padding: [40, 40] });
                } else {
                    map.fitBounds(tunisia, { padding: [14, 14] });
                }
            } catch (err) {
                map.fitBounds(tunisia, { padding: [14, 14] });
            }
        }
    }

    renderMarkers('all');

    document.querySelectorAll('.bo-map-filter [data-status]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.bo-map-filter [data-status]').forEach(function (b) { b.classList.remove('active'); });
            btn.classList.add('active');
            renderMarkers(btn.getAttribute('data-status'));
        });
    });

    setTimeout(function () { map.invalidateSize(); }, 300);
})();
</script>

<?php require __DIR__ . '/../layout_footer.php'; ?>
