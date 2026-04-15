<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistiques - MediConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { background: #f4f6f9; font-family: 'Segoe UI', sans-serif; }
        .sidebar { position: fixed; top: 0; left: 0; width: 260px; height: 100%; background: #1e2a3e; color: white; z-index: 100; }
        .sidebar-header { padding: 25px 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-header i { font-size: 40px; color: #4CAF50; }
        .sidebar-header h3 { margin: 10px 0 0; font-size: 20px; }
        .sidebar-menu a { display: block; padding: 12px 25px; color: rgba(255,255,255,0.7); text-decoration: none; transition: all 0.3s; }
        .sidebar-menu a:hover, .sidebar-menu a.active { background: rgba(255,255,255,0.1); color: white; border-left: 4px solid #4CAF50; }
        .sidebar-menu i { width: 25px; margin-right: 10px; }
        .main-content { margin-left: 260px; padding: 25px; }
        .navbar-top { background: white; border-radius: 12px; padding: 15px 25px; margin-bottom: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: center; }
        .stat-card { background: white; border-radius: 15px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 5px solid; }
        .chart-container { background: white; border-radius: 15px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    </style>
</head>
<body>
<div class="sidebar">
    <div class="sidebar-header">
        <i class="fas fa-stethoscope"></i>
        <h3>MediConnect</h3>
        <small>Back Office</small>
    </div>
    <div class="sidebar-menu">
        <a href="index.php?page=dashboard"><i class="fas fa-tachometer-alt"></i> Tableau de bord</a>
        <a href="index.php?page=users"><i class="fas fa-users"></i> Utilisateurs</a>
        <a href="index.php?page=medecins"><i class="fas fa-user-md"></i> Médecins</a>
        <a href="index.php?page=stats" class="active"><i class="fas fa-chart-line"></i> Statistiques</a>
        <a href="index.php?page=logs"><i class="fas fa-history"></i> Historique</a>
        <a href="index.php?page=logout"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
    </div>
</div>
<div class="main-content">
    <div class="navbar-top">
        <h4><i class="fas fa-chart-line me-2"></i> Statistiques avancées</h4>
        <span>👤 <?= htmlspecialchars($_SESSION['user_prenom'] . ' ' . $_SESSION['user_nom']) ?></span>
    </div>

    <div class="row">
        <div class="col-md-3">
            <div class="stat-card" style="border-left-color:#4CAF50;">
                <p>Total utilisateurs</p>
                <h3><?= $stats['total'] ?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card" style="border-left-color:#2A7FAA;">
                <p>Médecins</p>
                <h3><?= $stats['medecins'] ?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card" style="border-left-color:#ffc107;">
                <p>Patients</p>
                <h3><?= $stats['patients'] ?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card" style="border-left-color:#dc3545;">
                <p>En attente validation</p>
                <h3><?= $stats['en_attente'] ?></h3>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="chart-container">
                <h5><i class="fas fa-chart-pie me-2"></i> Répartition par rôle</h5>
                <canvas id="roleChart" height="250"></canvas>
            </div>
        </div>
        <div class="col-md-6">
            <div class="chart-container">
                <h5><i class="fas fa-chart-bar me-2"></i> Top spécialités médecins</h5>
                <canvas id="specChart" height="250"></canvas>
            </div>
        </div>
    </div>
</div>
<script>
new Chart(document.getElementById('roleChart'), {
    type: 'doughnut',
    data: {
        labels: ['Patients', 'Médecins', 'Admins'],
        datasets: [{ data: [<?= $stats['patients'] ?>, <?= $stats['medecins'] ?>, 1],
            backgroundColor: ['#4CAF50','#2A7FAA','#ffc107'], borderWidth: 0 }]
    },
    options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
});

const specialites = <?= json_encode(array_column($specialiteStats, 'specialite')) ?>;
const totaux = <?= json_encode(array_column($specialiteStats, 'total')) ?>;
new Chart(document.getElementById('specChart'), {
    type: 'bar',
    data: {
        labels: specialites.length ? specialites : ['Aucune donnée'],
        datasets: [{ label: 'Médecins', data: totaux.length ? totaux : [0],
            backgroundColor: '#2A7FAA', borderRadius: 8 }]
    },
    options: { responsive: true, plugins: { legend: { display: false } } }
});
</script>
</body>
</html>