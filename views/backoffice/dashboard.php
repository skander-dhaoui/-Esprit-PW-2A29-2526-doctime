<?php
// Vue déprécée - voir layout.php
?>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - DOCtime Back Office</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="assets/css/backoffice-polish.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #f0f2f5; font-family: 'Segoe UI', sans-serif; display: flex; min-height: 100vh; }

        /* Sidebar */
        .sidebar {
            width: 260px;
            min-height: 100vh;
            background: #1a2035;
            color: white;
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 100;
        }

        .sidebar-brand {
            padding: 25px 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }

        .brand-icon {
            width: 55px;
            height: 55px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 12px;
            font-size: 24px;
            color: #4CAF50;
        }

        .sidebar-brand h4 {
            font-size: 18px;
            font-weight: 700;
            margin: 0;
            color: white;
        }

        .sidebar-brand small {
            color: rgba(255,255,255,0.5);
            font-size: 11px;
        }

        .sidebar-nav {
            padding: 20px 0;
            flex: 1;
        }

        .sidebar-nav a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 22px;
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s;
            border-left: 3px solid transparent;
        }

        .sidebar-nav a:hover {
            background: rgba(255,255,255,0.07);
            color: white;
        }

        .sidebar-nav a.active {
            background: rgba(255,255,255,0.1);
            color: white;
            border-left-color: #4CAF50;
        }

        .sidebar-nav a i {
            width: 20px;
            text-align: center;
            font-size: 16px;
        }

        .nav-divider {
            height: 1px;
            background: rgba(255,255,255,0.07);
            margin: 10px 22px;
        }

        /* Main content */
        .main-content {
            margin-left: 260px;
            flex: 1;
            padding: 25px;
            min-height: 100vh;
        }

        .page-header {
            background: white;
            border-radius: 12px;
            padding: 18px 25px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 1px 6px rgba(0,0,0,0.06);
        }

        .page-header h4 {
            font-size: 18px;
            font-weight: 700;
            color: #1a2035;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .page-header h4 i {
            color: #4CAF50;
        }

        .admin-avatar {
            width: 40px;
            height: 40px;
            background: #4CAF50;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 16px;
            font-weight: bold;
            text-decoration: none;
        }

        .admin-avatar:hover {
            background: #2A7FAA;
            color: white;
        }

        .content-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 1px 6px rgba(0,0,0,0.06);
        }

        .card-title-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .card-title-row h5 {
            font-size: 16px;
            font-weight: 600;
            color: #1a2035;
            margin: 0;
        }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 1px 6px rgba(0,0,0,0.06);
            border-left: 4px solid;
            transition: transform 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-2px);
        }

        .stat-card p {
            font-size: 13px;
            color: #5b6475;
            margin-bottom: 8px;
        }

        .stat-card h3 {
            font-size: 28px;
            font-weight: 700;
            color: #1a2035;
            margin: 0 0 8px 0;
        }

        .stat-card small {
            font-size: 12px;
        }

        .chart-container {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 1px 6px rgba(0,0,0,0.06);
            margin-bottom: 25px;
        }

        .chart-container h5 {
            font-size: 16px;
            font-weight: 600;
            color: #1a2035;
            margin-bottom: 20px;
        }

        .badge-actif { background: #d4edda; color: #155724; padding: 4px 12px; border-radius: 20px; font-size: 12px; }
        .badge-inactif { background: #f8d7da; color: #721c24; padding: 4px 12px; border-radius: 20px; font-size: 12px; }
        .badge-validation { background: #fff3cd; color: #856404; padding: 4px 12px; border-radius: 20px; font-size: 12px; }

        .table thead th {
            background: #1a2035;
            color: white;
            font-weight: 600;
            font-size: 13px;
            padding: 12px 14px;
            border: none;
        }

        .table tbody td {
            vertical-align: middle;
            font-size: 14px;
            padding: 13px 14px;
            color: #333;
        }

        .table tbody tr:hover {
            background: #f8f9ff;
        }

        .btn-sm { padding: 5px 10px; margin: 2px; }

        .pagination-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-top: 20px;
            flex-wrap: wrap;
        }

        .pagination-info {
            font-size: 14px;
            color: #5b6475;
        }

        .pagination-links {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        @media (max-width: 992px) {
            .sidebar {
                width: 260px;
            }

            .main-content {
                margin-left: 260px;
            }

            .stats-row {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 80px;
            }

            .main-content {
                margin-left: 80px;
                padding: 15px;
            }

            .sidebar-brand {
                padding: 15px 10px;
            }

            .brand-icon {
                width: 40px;
                height: 40px;
                font-size: 18px;
                margin-bottom: 8px;
            }

            .sidebar-brand h4 {
                font-size: 14px;
            }

            .sidebar-brand small {
                display: none;
            }

            .sidebar-nav a {
                padding: 12px 15px;
                justify-content: center;
                font-size: 0;
            }

            .sidebar-nav a i {
                font-size: 20px;
                margin-right: 0;
            }

            .nav-divider {
                margin: 10px 15px;
            }

            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            .page-header h4 {
                font-size: 16px;
            }

            .stats-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<!-- Sidebar -->
<div class="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon"><i class="fas fa-stethoscope"></i></div>
        <h4>MediConnect</h4>
        <small>Back Office</small>
    </div>
    <nav class="sidebar-nav">
        <a href="index.php?page=dashboard" class="active">
            <i class="fas fa-th-large"></i> Tableau de bord
        </a>
        <a href="index.php?page=users">
            <i class="fas fa-users"></i> Utilisateurs
        </a>
        <a href="index.php?page=medecins_admin">
            <i class="fas fa-user-md"></i> Médecins
        </a>
        <a href="index.php?page=patients">
            <i class="fas fa-user-injured"></i> Patients
        </a>
        <a href="index.php?page=avis_admin"><i class="fas fa-star"></i> Avis</a>
        <a href="index.php?page=rendez_vous_admin">
            <i class="fas fa-calendar-check"></i> Rendez-vous
        </a>
        <a href="index.php?page=ordonnances">
            <i class="fas fa-prescription-bottle"></i> Ordonnances
        </a>
        <a href="index.php?page=produits_admin">
            <i class="fas fa-box"></i> Produits
        </a>
        <a href="index.php?page=articles_admin">
            <i class="fas fa-blog"></i> Blog
        </a>
        <a href="index.php?page=evenements_admin">
            <i class="fas fa-calendar-day"></i> Événements
        </a>
        <div class="nav-divider"></div>
        <a href="index.php?page=stats">
            <i class="fas fa-chart-line"></i> Statistiques
        </a>
        <a href="index.php?page=logs">
            <i class="fas fa-history"></i> Historique
        </a>
        <a href="index.php?page=settings">
            <i class="fas fa-cog"></i> Paramètres
        </a>
        <div class="nav-divider"></div>
        <a href="index.php?page=logout">
            <i class="fas fa-sign-out-alt"></i> Déconnexion
        </a>
    </nav>
</div>

<!-- Main Content -->
<div class="main-content">
    <div class="page-header">
        <h4><i class="fas fa-th-large"></i> Tableau de bord</h4>
        <a href="index.php?page=mon_profil" class="admin-avatar" title="Mon profil">
            <?= strtoupper(substr($_SESSION['user_name'] ?? 'A', 0, 1)) ?>
        </a>
    </div>

    <!-- Statistiques -->
    <div class="stats-row">
        <div class="stat-card" style="border-left-color: #4CAF50;">
            <p><i class="fas fa-users me-2"></i>Total utilisateurs</p>
            <h3><?= $stats['total_users'] ?? '—' ?></h3>
            <small class="text-success"><i class="fas fa-arrow-up"></i> +12% ce mois</small>
        </div>
        <div class="stat-card" style="border-left-color: #2A7FAA;">
            <p><i class="fas fa-user-md me-2"></i>Médecins</p>
            <h3><?= $stats['total_medecins'] ?? '—' ?></h3>
            <small class="text-success"><i class="fas fa-arrow-up"></i> +8% ce mois</small>
        </div>
        <div class="stat-card" style="border-left-color: #ffc107;">
            <p><i class="fas fa-user me-2"></i>Patients</p>
            <h3><?= $stats['total_patients'] ?? '—' ?></h3>
            <small class="text-success"><i class="fas fa-arrow-up"></i> +15% ce mois</small>
        </div>
        <div class="stat-card" style="border-left-color: #dc3545;">
            <p><i class="fas fa-clock me-2"></i>En attente validation</p>
            <h3><?= $stats['pending_medecins'] ?? '—' ?></h3>
            <small class="text-warning">
                <i class="fas fa-exclamation-triangle"></i> À traiter
            </small>
        </div>
    </div>

    <!-- Graphiques -->
    <div class="row">
        <div class="col-md-8">
            <div class="chart-container">
                <h5><i class="fas fa-chart-line me-2"></i> Évolution des inscriptions</h5>
                <canvas id="evolutionChart" height="250"></canvas>
            </div>
        </div>
        <div class="col-md-4">
            <div class="chart-container">
                <h5><i class="fas fa-chart-pie me-2"></i> Répartition utilisateurs</h5>
                <canvas id="repartitionChart" height="250"></canvas>
            </div>
        </div>
    </div>

    <!-- Derniers utilisateurs -->
    <div class="content-card">
        <div class="card-title-row">
            <h5><i class="fas fa-user-plus me-2"></i> Derniers utilisateurs inscrits</h5>
            <a href="index.php?page=users" class="btn btn-primary btn-sm">
                <i class="fas fa-arrow-right me-1"></i> Tous les utilisateurs
            </a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Nom complet</th>
                        <th>Email</th>
                        <th>Rôle</th>
                        <th>Statut</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($recentUsers)): ?>
                        <?php foreach ($recentUsers as $u): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($u['prenom'] . ' ' . $u['nom']) ?></strong></td>
                            <td><?= htmlspecialchars($u['email']) ?></td>
                            <td>
                                <span class="badge <?= $u['role'] === 'medecin' ? 'bg-success' : 'bg-info' ?>">
                                    <?= ucfirst($u['role']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($u['statut'] === 'actif'): ?>
                                    <span class="badge-actif">Actif</span>
                                <?php elseif ($u['statut'] === 'en_attente'): ?>
                                    <span class="badge-validation">En attente</span>
                                <?php else: ?>
                                    <span class="badge-inactif">Inactif</span>
                                <?php endif; ?>
                            </td>
                            <td><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
                            <td>
                                <?php if ($u['role'] === 'medecin' && $u['statut'] === 'en_attente'): ?>
                                    <a href="index.php?page=medecins_admin&action=validate&id=<?= $u['id'] ?>"
                                       class="btn btn-sm btn-warning">
                                        <i class="fas fa-check-circle"></i>
                                    </a>
                                <?php else: ?>
                                    <a href="index.php?page=users&action=edit&id=<?= $u['id'] ?>"
                                       class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                <?php endif; ?>
                                <a href="index.php?page=users&action=show&id=<?= $u['id'] ?>"
                                   class="btn btn-sm btn-secondary">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                Aucun utilisateur récent
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

    <script>
        const ctx1 = document.getElementById('evolutionChart').getContext('2d');
        new Chart(ctx1, {
            type: 'line',
            data: {
                labels: ['Jan','Fév','Mar','Avr','Mai','Juin','Juil','Aoû','Sep','Oct','Nov','Déc'],
                datasets: [{
                    label: 'Nouveaux utilisateurs',
                    data: [65,78,89,102,118,145,167,189,210,245,278,312],
                    borderColor: '#4CAF50',
                    backgroundColor: 'rgba(76,175,80,0.1)',
                    tension: 0.3,
                    fill: true
                }]
            },
            options: { responsive: true, maintainAspectRatio: true }
        });

        const ctx2 = document.getElementById('repartitionChart').getContext('2d');
        new Chart(ctx2, {
            type: 'doughnut',
            data: {
                labels: ['Patients','Médecins','Administrateurs'],
                datasets: [{
                    data: [
                        <?= $stats['total_patients'] ?? 1078 ?>,
                        <?= $stats['total_medecins'] ?? 156 ?>,
                        <?= $stats['total_admins']   ?? 3 ?>
                    ],
                    backgroundColor: ['#4CAF50','#2A7FAA','#ffc107'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: { legend: { position: 'bottom' } }
            }
        });
    </script>
</body>
</html>