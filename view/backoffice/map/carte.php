<?php $pageTitle = 'Carte Interactive – Événements en Tunisie'; ?>
<?php require __DIR__ . '/../layout_header.php'; ?>

<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css"/>
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css"/>

<style>
  /* ── Palette carte ── */
  :root {
    --map-blue:   #1a7fa8;
    --map-teal:   #1db88e;
    --map-gold:   #f59e0b;
    --map-red:    #ef4444;
    --map-purple: #7c3aed;
  }

  #map-container {
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 8px 32px rgba(26,127,168,.18);
    border: 2px solid rgba(26,127,168,.15);
  }
  #map { height: 540px; width: 100%; }

  /* Panneau latéral */
  .map-sidebar {
    display: flex; flex-direction: column; gap: 1rem; height: 540px; overflow-y: auto;
  }
  .gov-card {
    background: #fff;
    border-radius: 10px;
    padding: .75rem 1rem;
    border-left: 4px solid var(--map-teal);
    box-shadow: 0 2px 8px rgba(0,0,0,.06);
    transition: transform .15s, box-shadow .15s;
    cursor: pointer;
  }
  .gov-card:hover { transform: translateX(4px); box-shadow: 0 4px 16px rgba(26,127,168,.15); }
  .gov-card h6 { font-size: .82rem; font-weight: 700; color: var(--map-blue); margin: 0 0 .2rem; }
  .gov-card .badge-count {
    background: linear-gradient(135deg, var(--map-blue), var(--map-teal));
    color: #fff; font-size: .68rem; padding: 2px 8px;
    border-radius: 20px; font-weight: 600;
  }
  .gov-card small { color: #6b7280; font-size: .72rem; }

  /* Filtre statut */
  .filter-btn { border-radius: 20px; font-size: .78rem; padding: 4px 14px; border-width: 1.5px; }
  .filter-btn.active { color: #fff !important; }

  /* Légende */
  .legend-dot { width:12px; height:12px; border-radius:50%; display:inline-block; }

  /* Popup custom */
  .leaflet-popup-content-wrapper { border-radius: 12px !important; box-shadow: 0 4px 20px rgba(0,0,0,.15) !important; }
  .popup-ev h6 { color: var(--map-blue); font-weight: 700; margin-bottom: .3rem; font-size: .9rem; }
  .popup-ev .badge { font-size: .68rem; }
  .popup-ev table { font-size: .78rem; }
  .popup-ev td:first-child { color: #6b7280; padding-right: .5rem; }

  /* Heatmap overlay (simulé) */
  .heatmap-info { font-size: .75rem; color: #6b7280; }
</style>

<!-- ── En-tête page ── -->
<div class="d-flex align-items-center justify-content-between mb-4">
  <div>
    <h4 class="fw-bold mb-0">
      <i class="bi bi-map-fill me-2" style="color:var(--primary)"></i>
      Carte Interactive – Tunisie
    </h4>
    <small class="text-muted">Répartition géographique des événements médicaux</small>
  </div>
  <a href="?controller=map&action=metiers"
     class="btn btn-sm btn-outline-primary rounded-pill">
    <i class="bi bi-stars me-1"></i>Assistant IA Métiers
  </a>
</div>

<!-- ── Stat cards ── -->
<div class="row g-3 mb-4">
  <?php
  $statCards = [
    ['label'=>'Total Événements', 'value'=>$statsGlobales['total'],    'icon'=>'bi-calendar-event', 'grad'=>'#1a7fa8,#1db88e'],
    ['label'=>'Planifiés',        'value'=>$statsGlobales['planifie'], 'icon'=>'bi-clock',           'grad'=>'#3b82f6,#60a5fa'],
    ['label'=>'En cours',         'value'=>$statsGlobales['en_cours'], 'icon'=>'bi-play-circle',     'grad'=>'#f59e0b,#fbbf24'],
    ['label'=>'Terminés',         'value'=>$statsGlobales['termine'],  'icon'=>'bi-check-circle',    'grad'=>'#10b981,#34d399'],
  ];
  foreach($statCards as $c): ?>
  <div class="col-6 col-xl-3">
    <div class="stat-card" style="background:linear-gradient(135deg,<?= $c['grad'] ?>)">
      <p><?= $c['label'] ?></p>
      <h3><?= $c['value'] ?></h3>
      <i class="bi <?= $c['icon'] ?> stat-icon"></i>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- ── Filtres ── -->
<div class="card mb-4">
  <div class="card-body py-2 d-flex align-items-center gap-2 flex-wrap">
    <span class="text-muted fw-semibold me-2" style="font-size:.82rem"><i class="bi bi-funnel me-1"></i>Filtrer :</span>
    <button class="btn btn-outline-secondary filter-btn active" data-filter="all">Tous</button>
    <button class="btn btn-outline-primary  filter-btn" data-filter="planifie"  style="--bs-btn-active-bg:#3b82f6">Planifiés</button>
    <button class="btn btn-outline-warning  filter-btn" data-filter="en_cours"  style="--bs-btn-active-bg:#f59e0b">En cours</button>
    <button class="btn btn-outline-success  filter-btn" data-filter="termine"   style="--bs-btn-active-bg:#10b981">Terminés</button>
    <button class="btn btn-outline-danger   filter-btn" data-filter="annule"    style="--bs-btn-active-bg:#ef4444">Annulés</button>
    <div class="ms-auto d-flex align-items-center gap-3">
      <!-- Légende -->
      <span class="heatmap-info"><span class="legend-dot me-1" style="background:#3b82f6"></span>Planifié</span>
      <span class="heatmap-info"><span class="legend-dot me-1" style="background:#f59e0b"></span>En cours</span>
      <span class="heatmap-info"><span class="legend-dot me-1" style="background:#10b981"></span>Terminé</span>
      <span class="heatmap-info"><span class="legend-dot me-1" style="background:#ef4444"></span>Annulé</span>
    </div>
  </div>
</div>

<!-- ── Carte + Sidebar ── -->
<div class="row g-4 mb-4">

  <!-- Carte Leaflet -->
  <div class="col-lg-9">
    <div id="map-container">
      <div id="map"></div>
    </div>
  </div>

  <!-- Sidebar gouvernorats -->
  <div class="col-lg-3">
    <div class="card h-100">
      <div class="card-header py-3">
        <h6 class="mb-0 fw-bold">
          <i class="bi bi-geo-alt-fill me-2 text-primary"></i>
          Par Gouvernorat
        </h6>
      </div>
      <div class="card-body p-2 map-sidebar" id="govList">
        <?php foreach($statsGouvernorat as $gov => $stat): ?>
        <div class="gov-card" onclick="flyToGov('<?= htmlspecialchars($gov) ?>')">
          <div class="d-flex justify-content-between align-items-start">
            <h6><?= htmlspecialchars($gov) ?></h6>
            <span class="badge-count"><?= $stat['total'] ?> evt</span>
          </div>
          <small>
            <i class="bi bi-people-fill me-1 text-muted"></i><?= $stat['participants'] ?> participants
            <?php if(!empty($stat['specialites'])): ?>
            <br><i class="bi bi-tag me-1 text-muted"></i><?= htmlspecialchars(implode(', ', array_slice($stat['specialites'],0,2))) ?>
            <?php endif; ?>
          </small>
        </div>
        <?php endforeach; ?>
        <?php if(empty($statsGouvernorat)): ?>
        <div class="text-center text-muted p-3">
          <i class="bi bi-geo-alt" style="font-size:2rem"></i>
          <p class="mt-2 mb-0">Aucun événement</p>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- ── Tableau récap ── -->
<div class="card">
  <div class="card-header py-3 d-flex align-items-center justify-content-between">
    <h6 class="mb-0 fw-bold">
      <i class="bi bi-table me-2 text-primary"></i>
      Liste des Événements Géolocalisés
    </h6>
    <span class="badge bg-primary rounded-pill"><?= count($evenementsGeo) ?></span>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0" style="font-size:.83rem">
        <thead class="table-light">
          <tr>
            <th>Titre</th><th>Gouvernorat</th><th>Lieu</th>
            <th>Spécialité</th><th>Date</th><th>Statut</th><th>Participants</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($evenementsGeo as $ev): ?>
          <tr>
            <td class="fw-semibold"><?= htmlspecialchars($ev['titre']) ?></td>
            <td><i class="bi bi-geo-alt-fill text-primary me-1"></i><?= htmlspecialchars($ev['gouvernorat']) ?></td>
            <td class="text-muted"><?= htmlspecialchars($ev['lieu']) ?></td>
            <td><?= htmlspecialchars($ev['specialite']) ?></td>
            <td><?= date('d/m/Y', strtotime($ev['date_debut'])) ?></td>
            <td>
              <?php
              $badges = [
                'planifie' => 'bg-primary','en_cours' => 'bg-warning text-dark',
                'termine'  => 'bg-success', 'annule'  => 'bg-danger'
              ];
              $labels = [
                'planifie'=>'Planifié','en_cours'=>'En cours',
                'termine'=>'Terminé','annule'=>'Annulé'
              ];
              $b = $badges[$ev['statut']] ?? 'bg-secondary';
              $l = $labels[$ev['statut']] ?? $ev['statut'];
              ?>
              <span class="badge <?= $b ?>"><?= $l ?></span>
            </td>
            <td><?= $ev['nb_participants'] ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- ── Données PHP → JS ── -->
<script>
const evenementsData = <?= json_encode($evenementsGeo, JSON_UNESCAPED_UNICODE) ?>;
const gouvernoratsCoords = {
  "Tunis":       [36.8065, 10.1815],
  "Ariana":      [36.8625, 10.1956],
  "Ben Arous":   [36.7531, 10.2282],
  "Manouba":     [36.8089, 10.0956],
  "Nabeul":      [36.4561, 10.7376],
  "Zaghouan":    [36.4021, 10.1426],
  "Bizerte":     [37.2744,  9.8739],
  "Béja":        [36.7333,  9.1833],
  "Jendouba":    [36.5011,  8.7803],
  "Le Kef":      [36.1675,  8.7049],
  "Siliana":     [36.0844,  9.3704],
  "Sousse":      [35.8256, 10.6369],
  "Monastir":    [35.7643, 10.8113],
  "Mahdia":      [35.5047, 11.0622],
  "Sfax":        [34.7400, 10.7600],
  "Kairouan":    [35.6712, 10.1007],
  "Kasserine":   [35.1676,  8.8365],
  "Sidi Bouzid": [35.0382,  9.4858],
  "Gabès":       [33.8833, 10.0982],
  "Médenine":    [33.3549, 10.5055],
  "Tataouine":   [32.9211, 10.4511],
  "Gafsa":       [34.4250,  8.7842],
  "Tozeur":      [33.9197,  8.1335],
  "Kébili":      [33.7046,  8.9688]
};
</script>

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>

<script>
// ── Initialisation Leaflet ───────────────────────────────────────────────────
const map = L.map('map', {
  center: [33.8869, 9.5375],
  zoom: 6,
  zoomControl: true
});

// Fond de carte OpenStreetMap
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
  attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
  maxZoom: 18
}).addTo(map);

// Couleurs par statut
const statutColors = {
  planifie:  '#3b82f6',
  en_cours:  '#f59e0b',
  termine:   '#10b981',
  annule:    '#ef4444'
};

// ── Création des marqueurs ───────────────────────────────────────────────────
const markerCluster = L.markerClusterGroup({ maxClusterRadius: 40 });
let allMarkers = [];
let currentFilter = 'all';

function buildIcon(statut) {
  const color = statutColors[statut] || '#6b7280';
  return L.divIcon({
    className: '',
    iconSize: [32, 32],
    iconAnchor: [16, 32],
    popupAnchor: [0, -32],
    html: `<div style="
      width:28px;height:28px;border-radius:50% 50% 50% 0;
      background:${color};transform:rotate(-45deg);
      box-shadow:0 3px 10px rgba(0,0,0,.3);
      border:2px solid #fff;
    "></div>`
  });
}

function buildPopup(ev) {
  const statutLabel = {planifie:'Planifié',en_cours:'En cours',termine:'Terminé',annule:'Annulé'};
  const b = statutColors[ev.statut] || '#6b7280';
  return `
    <div class="popup-ev" style="min-width:200px">
      <h6>${ev.titre}</h6>
      <span class="badge rounded-pill" style="background:${b};font-size:.68rem">${statutLabel[ev.statut]||ev.statut}</span>
      <table class="mt-2 w-100">
        <tr><td><i class="bi bi-geo-alt"></i> Lieu</td><td>${ev.lieu}</td></tr>
        <tr><td><i class="bi bi-tag"></i> Spécialité</td><td>${ev.specialite}</td></tr>
        <tr><td><i class="bi bi-calendar"></i> Début</td><td>${ev.date_debut}</td></tr>
        <tr><td><i class="bi bi-people"></i> Participants</td><td>${ev.nb_participants}</td></tr>
        <tr><td><i class="bi bi-currency-exchange"></i> Prix</td><td>${parseFloat(ev.prix)===0?'Gratuit':ev.prix+' TND'}</td></tr>
      </table>
    </div>`;
}

evenementsData.forEach(ev => {
  const marker = L.marker([ev.lat, ev.lng], { icon: buildIcon(ev.statut) })
    .bindPopup(buildPopup(ev))
    .bindTooltip(ev.titre, { direction: 'top', offset: [0,-10] });
  marker._statut = ev.statut;
  marker._gouvernorat = ev.gouvernorat;
  allMarkers.push(marker);
  markerCluster.addLayer(marker);
});

map.addLayer(markerCluster);

// ── Filtre par statut ────────────────────────────────────────────────────────
document.querySelectorAll('.filter-btn').forEach(btn => {
  btn.addEventListener('click', function() {
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    this.classList.add('active');
    currentFilter = this.dataset.filter;
    markerCluster.clearLayers();
    allMarkers.forEach(m => {
      if (currentFilter === 'all' || m._statut === currentFilter) {
        markerCluster.addLayer(m);
      }
    });
  });
});

// ── Fly to gouvernorat ───────────────────────────────────────────────────────
function flyToGov(gov) {
  const coords = gouvernoratsCoords[gov];
  if (coords) {
    map.flyTo(coords, 10, { duration: 1.2 });
  }
}
</script>

<?php require __DIR__ . '/../layout_footer.php'; ?>
