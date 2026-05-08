<?php $pageTitle = 'Tableau de bord'; ?>
<?php require __DIR__ . '/layout_header.php'; ?>

<!-- ── Stat cards ── -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card" style="background: linear-gradient(135deg,#1a7fa8,#1db88e)">
            <p>Événements</p>
            <h3><?= $totalEvenements ?></h3>
            <i class="bi bi-calendar-event stat-icon"></i>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card" style="background: linear-gradient(135deg,#7c3aed,#a855f7)">
            <p>Sponsors</p>
            <h3><?= $totalSponsors ?></h3>
            <i class="bi bi-building stat-icon"></i>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card" style="background: linear-gradient(135deg,#f59e0b,#ef4444)">
            <p>Participations</p>
            <h3><?= $totalParticipations ?></h3>
            <i class="bi bi-people stat-icon"></i>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card" style="background: linear-gradient(135deg,#0ea5e9,#06b6d4)">
            <p>Total sponsors (TND)</p>
            <h3><?= number_format($totalMontant, 0, ',', ' ') ?></h3>
            <i class="bi bi-cash-stack stat-icon"></i>
        </div>
    </div>
</div>

<!-- ── Graphiques ligne 1 ── -->
<div class="row g-4 mb-4">

    <!-- Logigramme : Montant par sponsor -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header py-3 d-flex align-items-center justify-content-between">
                <h6 class="mb-0 fw-bold">
                    <i class="bi bi-pie-chart-fill me-2 text-primary"></i>
                    Montant des Sponsors (TND)
                </h6>
                <span class="badge bg-primary rounded-pill"><?= count($sponsorsData) ?></span>
            </div>
            <div class="card-body d-flex align-items-center justify-content-center">
                <canvas id="chartSponsors" style="max-height:300px"></canvas>
            </div>
        </div>
    </div>

    <!-- Répartition des participations par statut -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header py-3 d-flex align-items-center justify-content-between">
                <h6 class="mb-0 fw-bold">
                    <i class="bi bi-pie-chart me-2 text-warning"></i>
                    Répartition des Participations
                </h6>
                <span class="badge bg-warning text-dark rounded-pill"><?= $totalParticipations ?></span>
            </div>
            <div class="card-body d-flex align-items-center justify-content-center">
                <canvas id="chartParticipStatut" style="max-height:300px"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- ── Graphiques ligne 2 ── -->
<div class="row g-4">

    <!-- Participations par événement (barres) -->
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header py-3">
                <h6 class="mb-0 fw-bold">
                    <i class="bi bi-bar-chart-fill me-2 text-success"></i>
                    Participations par Événement
                </h6>
            </div>
            <div class="card-body">
                <canvas id="chartParticipEvenement" style="max-height:280px"></canvas>
            </div>
        </div>
    </div>

    <!-- Montant total par niveau de sponsor (barres) -->
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header py-3">
                <h6 class="mb-0 fw-bold">
                    <i class="bi bi-bar-chart-steps me-2 text-info"></i>
                    Montant Total par Niveau
                </h6>
            </div>
            <div class="card-body">
                <canvas id="chartMontantNiveau" style="max-height:280px"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- ── Données PHP → JS ── -->
<script>
const sponsorsLabels  = <?= json_encode(array_column($sponsorsData, 'nom')) ?>;
const sponsorsMontants = <?= json_encode(array_map('floatval', array_column($sponsorsData, 'montant'))) ?>;

const participStatutLabels = <?= json_encode(array_map(function($r){
    return match($r['statut']) { 'en_attente'=>'En attente','confirme'=>'Confirmé','annule'=>'Annulé', default=>$r['statut'] };
}, $participStatut)) ?>;
const participStatutData = <?= json_encode(array_map('intval', array_column($participStatut, 'total'))) ?>;

const participEvtLabels = <?= json_encode(array_map(function($r){
    return mb_strlen($r['titre']) > 28 ? mb_substr($r['titre'],0,28).'…' : $r['titre'];
}, $participEvenement)) ?>;
const participEvtData = <?= json_encode(array_map('intval', array_column($participEvenement, 'total'))) ?>;

const niveauLabels = <?= json_encode(array_map(fn($r)=>ucfirst($r['niveau']), $montantNiveau)) ?>;
const niveauData   = <?= json_encode(array_map('floatval', array_column($montantNiveau, 'total'))) ?>;
</script>

<?php require __DIR__ . '/layout_footer.php'; ?>
