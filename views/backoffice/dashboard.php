<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord — Gestion | Valorys</title>
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
        .gestion-tile { display: block; background: white; border-radius: 12px; padding: 16px 18px; margin-bottom: 0; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border: 1px solid rgba(0,0,0,0.06); border-left: 4px solid #22c55e; text-decoration: none; color: inherit; transition: transform 0.2s, box-shadow 0.2s; height: 100%; }
        .gestion-tile:hover { transform: translateY(-3px); box-shadow: 0 6px 16px rgba(0,0,0,0.08); color: inherit; }
        .gestion-tile i { color: #16a34a; font-size: 1.25rem; width: 1.5rem; text-align: center; }
        .gestion-tile span.title { font-weight: 600; font-size: 0.95rem; display: block; margin-top: 6px; color: #1e293b; }
        .gestion-tile span.desc { font-size: 0.78rem; color: #64748b; margin-top: 4px; display: block; line-height: 1.35; }

        /* Assistant vocal (aligné layout_header BO : micro + clavier + panneau) */
        .bo-voice-stack {
            position: fixed; bottom: 1.25rem; left: 1.25rem; z-index: 1040;
            display: flex; flex-direction: column; gap: 8px; align-items: center;
        }
        .bo-voice-fab {
            width: 52px; height: 52px; border-radius: 50% !important;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.2rem; border: none; cursor: pointer;
            background: linear-gradient(135deg, #1a7fa8, #1db88e);
            color: #fff; box-shadow: 0 4px 16px rgba(26, 127, 168, 0.4);
            transition: transform 0.2s;
        }
        .bo-voice-fab--kbd {
            background: linear-gradient(135deg, #475569, #64748b);
            box-shadow: 0 4px 12px rgba(71, 85, 105, 0.35);
        }
        .bo-voice-fab--disabled { opacity: 0.45; cursor: not-allowed; }
        .bo-voice-fallback-panel {
            position: fixed; bottom: 9rem; left: 1rem; z-index: 1039;
            width: min(340px, calc(100vw - 2rem));
            background: #fff; border: 1px solid #e2e8f0; border-radius: 12px;
            padding: 12px 14px; box-shadow: 0 10px 30px rgba(0,0,0,.12);
        }
        .bo-voice-fab:hover:not(:disabled) { transform: scale(1.06); color: #fff; }
        .bo-voice-fab.is-listening { animation: bo-voice-pulse 1.15s ease-in-out infinite; }
        @keyframes bo-voice-pulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(29, 184, 142, 0.45); }
            50% { box-shadow: 0 0 0 10px rgba(29, 184, 142, 0); }
        }
        .bo-voice-status {
            position: fixed; bottom: 5rem; left: 1rem; max-width: min(320px, calc(100vw - 2rem));
            z-index: 1039; display: none;
            background: rgba(15, 23, 42, 0.92); color: #f1f5f9; font-size: 0.82rem;
            padding: 10px 14px; border-radius: 12px; line-height: 1.45;
            box-shadow: 0 8px 24px rgba(0,0,0,.18);
        }
        .bo-voice-toast {
            position: fixed; bottom: 5rem; left: 50%; transform: translateX(-50%);
            z-index: 1041; max-width: min(420px, 94vw);
            padding: 12px 18px; border-radius: 12px; font-size: 0.85rem; line-height: 1.45;
            box-shadow: 0 8px 28px rgba(0,0,0,.2); display: none;
        }
        .bo-voice-toast--info { background: #0f172a; color: #e2e8f0; }
        .bo-voice-toast--warn { background: #7c2d12; color: #fff7ed; }
    </style>
</head>
<body class="bo-shell-body">
<?php require_once __DIR__ . '/sidebar.php'; ?>

    <div class="main-content">
        <div class="navbar-top">
            <div style="display:flex;align-items:center;gap:12px;">
                <strong style="color:#1e2a3e;font-size:1.05rem;"><i class="fas fa-tachometer-alt me-2" style="color:#22c55e"></i> Tableau de bord — Gestion</strong>
            </div>
            <div class="admin-info d-flex align-items-center gap-2 flex-wrap">
                <?php require_once __DIR__ . '/../partials/backoffice_notifications_bell.php'; ?>
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
                    <h3><?= isset($stats['en_validation']) ? (int)$stats['en_validation'] : '—' ?></h3>
                    <small class="text-warning">
                        <i class="fas fa-exclamation-triangle"></i> À traiter
                    </small>
                </div>
            </div>
        </div>

        <!-- Accès rapide : tous les modules de gestion -->
        <div class="chart-container">
            <h5 class="mb-3"><i class="fas fa-th-large me-2" style="color:#22c55e;"></i>Accès rapide — gestion</h5>
            <p class="text-muted small mb-4">Raccourcis vers les espaces d’administration (utilisateurs, santé, parapharmacie, contenu, carte).</p>
            <div class="row g-3">
                <div class="col-md-6 col-lg-4 col-xl-3">
                    <a class="gestion-tile" href="index.php?page=users"><i class="fas fa-users"></i><span class="title">Utilisateurs</span><span class="desc">Comptes, rôles et statuts</span></a>
                </div>
                <div class="col-md-6 col-lg-4 col-xl-3">
                    <a class="gestion-tile" href="index.php?page=medecins_admin"><i class="fas fa-user-md"></i><span class="title">Médecins</span><span class="desc">Fiches et validations</span></a>
                </div>
                <div class="col-md-6 col-lg-4 col-xl-3">
                    <a class="gestion-tile" href="index.php?page=patients"><i class="fas fa-user-injured"></i><span class="title">Patients</span><span class="desc">Dossiers administratifs</span></a>
                </div>
                <div class="col-md-6 col-lg-4 col-xl-3">
                    <a class="gestion-tile" href="index.php?page=rendez_vous_admin"><i class="fas fa-calendar-check"></i><span class="title">Rendez-vous</span><span class="desc">Planning global</span></a>
                </div>
                <div class="col-md-6 col-lg-4 col-xl-3">
                    <a class="gestion-tile" href="index.php?page=disponibilites_admin"><i class="fas fa-clock"></i><span class="title">Disponibilités</span><span class="desc">Créneaux médecins</span></a>
                </div>
                <div class="col-md-6 col-lg-4 col-xl-3">
                    <a class="gestion-tile" href="index.php?page=ordonnances"><i class="fas fa-prescription-bottle"></i><span class="title">Ordonnances</span><span class="desc">Liste et suivi</span></a>
                </div>
                <div class="col-md-6 col-lg-4 col-xl-3">
                    <a class="gestion-tile" href="index.php?page=produits_admin"><i class="fas fa-pills"></i><span class="title">Produits</span><span class="desc">Parapharmacie</span></a>
                </div>
                <div class="col-md-6 col-lg-4 col-xl-3">
                    <a class="gestion-tile" href="index.php?page=categories_admin"><i class="fas fa-tags"></i><span class="title">Catégories</span><span class="desc">Catalogue</span></a>
                </div>
                <div class="col-md-6 col-lg-4 col-xl-3">
                    <a class="gestion-tile" href="index.php?page=commandes_admin"><i class="fas fa-shopping-cart"></i><span class="title">Commandes</span><span class="desc">E‑commerce</span></a>
                </div>
                <div class="col-md-6 col-lg-4 col-xl-3">
                    <a class="gestion-tile" href="index.php?page=blog"><i class="fas fa-newspaper"></i><span class="title">Articles</span><span class="desc">Blog & actualités</span></a>
                </div>
                <div class="col-md-6 col-lg-4 col-xl-3">
                    <a class="gestion-tile" href="index.php?page=evenements_admin"><i class="fas fa-calendar-day"></i><span class="title">Événements</span><span class="desc">Salons & journées</span></a>
                </div>
                <div class="col-md-6 col-lg-4 col-xl-3">
                    <a class="gestion-tile" href="index.php?page=sponsors_admin"><i class="fas fa-handshake"></i><span class="title">Sponsors</span><span class="desc">Partenaires</span></a>
                </div>
                <div class="col-md-6 col-lg-4 col-xl-3">
                    <a class="gestion-tile" href="index.php?page=participations"><i class="fas fa-users-line"></i><span class="title">Participations</span><span class="desc">Inscriptions événements</span></a>
                </div>
                <div class="col-md-6 col-lg-4 col-xl-3">
                    <a class="gestion-tile" href="index.php?page=carte"><i class="fas fa-map-marked-alt"></i><span class="title">Carte Tunisie</span><span class="desc">Carte interactive (page dédiée)</span></a>
                </div>
                <div class="col-md-6 col-lg-4 col-xl-3">
                    <a class="gestion-tile" href="index.php?page=carte&action=metiers"><i class="fas fa-robot"></i><span class="title">IA Métiers créatifs</span><span class="desc">Assistant &amp; données BDD</span></a>
                </div>
                <div class="col-md-6 col-lg-4 col-xl-3">
                    <a class="gestion-tile" href="index.php?page=stats"><i class="fas fa-chart-line"></i><span class="title">Statistiques</span><span class="desc">Analyses détaillées</span></a>
                </div>
                <div class="col-md-6 col-lg-4 col-xl-3">
                    <a class="gestion-tile" href="index.php?page=logs"><i class="fas fa-history"></i><span class="title">Historique / logs</span><span class="desc">Traçabilité</span></a>
                </div>
                <div class="col-md-6 col-lg-4 col-xl-3">
                    <a class="gestion-tile" href="index.php?page=settings"><i class="fas fa-cog"></i><span class="title">Paramètres</span><span class="desc">Configuration plateforme</span></a>
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
                        <?= (int)($stats['total_patients'] ?? 0) ?>,
                        <?= (int)($stats['total_medecins'] ?? 0) ?>,
                        <?= (int)($stats['total_admins'] ?? 0) ?>
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
    <script src="assets/js/backoffice-voice-assistant.js" defer></script>
</body>
</html>
