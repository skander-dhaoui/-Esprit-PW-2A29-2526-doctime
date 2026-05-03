<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-chart-line text-primary me-2"></i>Statistiques Globales</h1>
    </div>

    <!-- Key Metrics Row -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Utilisateurs Inscrits</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($statsData['total_users']) ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Rendez-vous</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($statsData['total_rdv']) ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar-check fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Articles du Blog
                            </div>
                            <div class="row no-gutters align-items-center">
                                <div class="col-auto">
                                    <div class="h5 mb-0 mr-3 font-weight-bold text-gray-800"><?= number_format($statsData['total_articles']) ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-newspaper fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Avis Patients</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($statsData['total_avis']) ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-star fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Area Chart - Inscriptions -->
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Inscriptions par mois (Année en cours)</h6>
                </div>
                <div class="card-body">
                    <div class="chart-area" style="height: 320px;">
                        <canvas id="registrationsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pie Chart - Roles -->
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Répartition par Rôle</h6>
                </div>
                <div class="card-body">
                    <div class="chart-pie pt-4 pb-2" style="height: 320px;">
                        <canvas id="rolesChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Bar Chart - RDV -->
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-success">Rendez-vous par mois (Année en cours)</h6>
                </div>
                <div class="card-body">
                    <div class="chart-area" style="height: 320px;">
                        <canvas id="rdvChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- List - Top Specialities -->
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Top 5 Spécialités Médicales</h6>
                </div>
                <div class="card-body">
                    <?php if (!empty($statsData['top_specialites'])): ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($statsData['top_specialites'] as $index => $spec): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span>
                                        <span class="badge bg-primary rounded-pill me-2"><?= $index + 1 ?></span>
                                        <?= htmlspecialchars($spec['specialite']) ?>
                                    </span>
                                    <span class="badge bg-secondary rounded-pill"><?= $spec['count'] ?> médecins</span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="text-center text-muted mt-4">Aucune donnée disponible</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Labels pour les mois
    const monthLabels = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc'];

    // --- Chart 1: Inscriptions ---
    const ctxRegistrations = document.getElementById('registrationsChart');
    if (ctxRegistrations) {
        new Chart(ctxRegistrations, {
            type: 'line',
            data: {
                labels: monthLabels,
                datasets: [{
                    label: 'Nouvelles Inscriptions',
                    lineTension: 0.3,
                    backgroundColor: "rgba(78, 115, 223, 0.05)",
                    borderColor: "rgba(78, 115, 223, 1)",
                    pointRadius: 3,
                    pointBackgroundColor: "rgba(78, 115, 223, 1)",
                    pointBorderColor: "rgba(78, 115, 223, 1)",
                    pointHoverRadius: 3,
                    pointHoverBackgroundColor: "rgba(78, 115, 223, 1)",
                    pointHoverBorderColor: "rgba(78, 115, 223, 1)",
                    pointHitRadius: 10,
                    pointBorderWidth: 2,
                    data: <?= json_encode($statsData['inscriptions_par_mois']) ?>,
                    fill: true
                }]
            },
            options: {
                maintainAspectRatio: false,
                responsive: true,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0 } }
                }
            }
        });
    }

    // --- Chart 2: Répartition des Rôles ---
    const ctxRoles = document.getElementById('rolesChart');
    if (ctxRoles) {
        <?php
            $rolesLabels = [];
            $rolesData = [];
            foreach ($statsData['repartition_roles'] as $role) {
                $rolesLabels[] = ucfirst($role['role']);
                $rolesData[] = $role['count'];
            }
        ?>
        new Chart(ctxRoles, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($rolesLabels) ?>,
                datasets: [{
                    data: <?= json_encode($rolesData) ?>,
                    backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b'],
                    hoverBackgroundColor: ['#2e59d9', '#17a673', '#2c9faf', '#dda20a', '#be2617'],
                    hoverBorderColor: "rgba(234, 236, 244, 1)",
                }],
            },
            options: {
                maintainAspectRatio: false,
                responsive: true,
                cutout: '70%',
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    }

    // --- Chart 3: Rendez-vous ---
    const ctxRdv = document.getElementById('rdvChart');
    if (ctxRdv) {
        new Chart(ctxRdv, {
            type: 'bar',
            data: {
                labels: monthLabels,
                datasets: [{
                    label: 'Nombre de Rendez-vous',
                    backgroundColor: "#1cc88a",
                    hoverBackgroundColor: "#17a673",
                    borderColor: "#1cc88a",
                    data: <?= json_encode($statsData['rdv_par_mois']) ?>,
                }]
            },
            options: {
                maintainAspectRatio: false,
                responsive: true,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0 } }
                }
            }
        });
    }
});
</script>
<style>
/* Utilities inspired by SB Admin 2 for clean stat cards */
.border-left-primary { border-left: 4px solid #4e73df !important; }
.border-left-success { border-left: 4px solid #1cc88a !important; }
.border-left-info { border-left: 4px solid #36b9cc !important; }
.border-left-warning { border-left: 4px solid #f6c23e !important; }
.text-primary { color: #4e73df !important; }
.text-success { color: #1cc88a !important; }
.text-info { color: #36b9cc !important; }
.text-warning { color: #f6c23e !important; }
.text-gray-300 { color: #dddfeb !important; }
.text-gray-800 { color: #5a5c69 !important; }
</style>
