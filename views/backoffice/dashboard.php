<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - DOCtime Back Office</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <?php require_once __DIR__ . '/../partials/backoffice_shell_styles.php'; ?>
    <style>
        body.bo-shell-body { background: #f4f6f9; font-family: 'Segoe UI', sans-serif; }
        .navbar-top { background: white; border-radius: 12px; padding: 15px 25px; margin-bottom: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; }
        .admin-info { display: flex; align-items: center; gap: 15px; }
        .admin-avatar { width: 45px; height: 45px; background: #4CAF50; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 18px; cursor: pointer; }
        .stat-card { background: white; border-radius: 15px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); transition: transform 0.3s; border-left: 4px solid; }
        .stat-card:hover { transform: translateY(-5px); }
        .stat-card h3 { font-size: 32px; margin: 10px 0 5px; font-weight: bold; }
        .stat-card p { color: #666; margin: 0; }
        .stat-icon { font-size: 45px; opacity: 0.3; float: right; }
        .recent-table { background: white; border-radius: 15px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .recent-table h5 { margin-bottom: 20px; color: #1e2a3e; }
        .badge-active { background: #d4edda; color: #155724; padding: 5px 12px; border-radius: 20px; font-size: 12px; }
        .badge-pending { background: #fff3cd; color: #856404; padding: 5px 12px; border-radius: 20px; font-size: 12px; }
        .badge-inactive { background: #f8d7da; color: #721c24; padding: 5px 12px; border-radius: 20px; font-size: 12px; }
        .chart-container { background: white; border-radius: 15px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    </style>
</head>
<body class="bo-shell-body">
<?php require_once __DIR__ . '/sidebar.php'; ?>

    <div class="main-content">
        <div class="navbar-top">
            <div style="display:flex;align-items:center;gap:12px;">
                <strong style="color:#1e2a3e;font-size:1.05rem;"><i class="fas fa-tachometer-alt me-2" style="color:#4CAF50"></i> Tableau de bord</strong>
            </div>
            <div class="admin-info">
                <a href="index.php?page=mes_notifications" style="color:#1e2a3e;">
                    <i class="fas fa-bell"></i>
                </a>
                <a href="index.php?page=profil" style="text-decoration:none;">
                    <div class="admin-avatar" title="Mon profil">
                        <?= strtoupper(substr($_SESSION['user_name'] ?? 'A', 0, 1)) ?>
                    </div>
                </a>
                <span><?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin') ?></span>
            </div>
        </div>

        <!-- Statistiques -->
        <div class="row">
            <div class="col-md-3">
                <div class="stat-card" style="border-left-color: #4CAF50;">
                    <i class="fas fa-users stat-icon"></i>
                    <p>Total utilisateurs</p>
                    <h3><?= $stats['total_users'] ?? '—' ?></h3>
                    <small class="text-success"><i class="fas fa-arrow-up"></i> +12% ce mois</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card" style="border-left-color: #2A7FAA;">
                    <i class="fas fa-user-md stat-icon"></i>
                    <p>Médecins</p>
                    <h3><?= $stats['total_medecins'] ?? '—' ?></h3>
                    <small class="text-success"><i class="fas fa-arrow-up"></i> +8% ce mois</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card" style="border-left-color: #ffc107;">
                    <i class="fas fa-user stat-icon"></i>
                    <p>Patients</p>
                    <h3><?= $stats['total_patients'] ?? '—' ?></h3>
                    <small class="text-success"><i class="fas fa-arrow-up"></i> +15% ce mois</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card" style="border-left-color: #dc3545;">
                    <i class="fas fa-clock stat-icon"></i>
                    <p>En attente validation</p>
                    <h3><?= $stats['pending_medecins'] ?? '—' ?></h3>
                    <small class="text-warning">
                        <i class="fas fa-exclamation-triangle"></i> À traiter
                    </small>
                </div>
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
        <div class="recent-table">
            <h5><i class="fas fa-user-plus me-2"></i> Derniers utilisateurs inscrits</h5>
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
                                <td><?= htmlspecialchars($u['prenom'] . ' ' . $u['nom']) ?></td>
                                <td><?= htmlspecialchars($u['email']) ?></td>
                                <td>
                                    <span class="badge <?= $u['role'] === 'medecin' ? 'bg-success' : 'bg-info' ?>">
                                        <?= ucfirst($u['role']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($u['statut'] === 'actif'): ?>
                                        <span class="badge-active">Actif</span>
                                    <?php elseif ($u['statut'] === 'en_attente'): ?>
                                        <span class="badge-pending">En validation</span>
                                    <?php else: ?>
                                        <span class="badge-inactive">Inactif</span>
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
            <div class="text-end mt-2">
                <a href="index.php?page=users" class="btn btn-primary btn-sm">
                    Voir tous les utilisateurs <i class="fas fa-arrow-right ms-1"></i>
                </a>
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
