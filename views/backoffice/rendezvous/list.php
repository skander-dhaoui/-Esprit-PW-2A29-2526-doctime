<?php
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
    <title>Gestion des rendez-vous - Valorys</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #f4f6f9; font-family: 'Segoe UI', sans-serif; }
        .sidebar { position: fixed; top: 0; left: 0; width: 280px; height: 100%; background: #1e2a3e; color: white; transition: all 0.3s; z-index: 100; }
        .sidebar-header { padding: 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-header .logo-img { width: 130px; height: auto; object-fit: contain; margin-bottom: 6px; filter: brightness(0) invert(1); }
        .sidebar-header small { color: rgba(255,255,255,0.6); font-size: 12px; }
        .sidebar-menu { padding: 20px 0; }
        .sidebar-menu a { display: block; padding: 12px 25px; color: rgba(255,255,255,0.7); text-decoration: none; transition: all 0.3s; font-weight: 500; }
        .sidebar-menu a:hover, .sidebar-menu a.active { background: rgba(255,255,255,0.1); color: white; border-left: 4px solid #4CAF50; }
        .sidebar-menu i { width: 25px; margin-right: 12px; }
        .main-content { margin-left: 280px; padding: 20px; }
        .navbar-top { background: white; border-radius: 12px; padding: 15px 25px; margin-bottom: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; }
        .navbar-left { display: flex; align-items: center; gap: 20px; }
        .navbar-logo { height: 40px; margin-right: 15px; }
        .navbar-menu-items { display: flex; gap: 10px; flex-wrap: wrap; }
        .navbar-menu-items .nav-link-custom { color: #1e2a3e; text-decoration: none; padding: 8px 16px; border-radius: 8px; transition: all 0.3s; font-size: 14px; font-weight: 500; }
        .navbar-menu-items .nav-link-custom:hover { background: #f0f0f0; color: #4CAF50; }
        .navbar-menu-items .nav-link-custom i { margin-right: 6px; }
        .admin-info { display: flex; align-items: center; gap: 15px; }
        .admin-avatar { width: 45px; height: 45px; background: #4CAF50; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 18px; cursor: pointer; }
        .stat-card { background: white; border-radius: 15px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); transition: transform 0.3s; border-left: 4px solid; }
        .stat-card:hover { transform: translateY(-5px); }
        .stat-card h3 { font-size: 32px; margin: 10px 0 5px; font-weight: bold; }
        .stat-card p { color: #666; margin: 0; }
        .stat-icon { font-size: 45px; opacity: 0.3; float: right; }
        .recent-table { background: white; border-radius: 15px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .recent-table h5 { margin-bottom: 20px; color: #1e2a3e; }
        .badge-confirme { background: #d4edda; color: #155724; padding: 5px 12px; border-radius: 20px; font-size: 12px; }
        .badge-attente { background: #fff3cd; color: #856404; padding: 5px 12px; border-radius: 20px; font-size: 12px; }
        .badge-termine { background: #cfe2ff; color: #084298; padding: 5px 12px; border-radius: 20px; font-size: 12px; }
        .badge-annule { background: #f8d7da; color: #721c24; padding: 5px 12px; border-radius: 20px; font-size: 12px; }
        .btn-action { padding: 5px 10px; margin: 2px; border-radius: 5px; }
        .chart-container { background: white; border-radius: 15px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        
        /* Vue Avancée Styles */
        .view-toggle { display: flex; gap: 10px; align-items: center; margin-bottom: 20px; }
        .view-toggle .btn-toggle { padding: 8px 16px; border-radius: 8px; border: 2px solid #ddd; background: white; cursor: pointer; transition: all 0.3s; font-weight: 600; }
        .view-toggle .btn-toggle.active { background: #4CAF50; color: white; border-color: #4CAF50; }
        .view-toggle .btn-toggle:hover { border-color: #4CAF50; }
        
        #tableView { display: block; }
        #cardView { display: none; padding: 0; }
        
        .rdv-cards-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .rdv-card { 
            background: white; border-radius: 12px; padding: 20px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.05); 
            border-left: 4px solid #4CAF50; 
            transition: all 0.3s;
            display: flex;
            flex-direction: column;
            min-height: 280px;
            overflow: visible;
        }
        .rdv-card:hover { box-shadow: 0 4px 20px rgba(0,0,0,0.1); transform: translateY(-3px); }
        
        .rdv-card-header { 
            display: flex; justify-content: space-between; align-items: flex-start; 
            margin-bottom: 15px; padding-bottom: 12px; 
            border-bottom: 1px solid #f0f0f0;
            gap: 10px;
            flex-wrap: wrap;
        }
        .rdv-card-title { font-weight: 700; color: #1e2a3e; font-size: 14px; line-height: 1.4; }
        .rdv-card-status { 
            padding: 4px 10px; border-radius: 15px; font-size: 11px; font-weight: 600;
            white-space: nowrap;
            flex-shrink: 0;
        }
        
        .rdv-card-info { 
            margin-bottom: 12px; display: flex; 
            align-items: flex-start; gap: 10px;
            flex-wrap: wrap;
        }
        .rdv-card-icon { 
            width: 20px; min-width: 20px; text-align: center; color: #4CAF50; font-size: 16px;
        }
        .rdv-card-content {
            flex: 1;
            min-width: 0;
        }
        .rdv-card-text { 
            font-size: 13px; color: #333; 
            word-wrap: break-word;
            word-break: break-word;
            overflow-wrap: break-word;
            line-height: 1.3;
        }
        .rdv-card-label { 
            font-size: 11px; color: #999; font-weight: 600; 
            text-transform: uppercase; letter-spacing: 0.5px;
            margin-bottom: 2px;
        }
        
        .rdv-card-actions { 
            display: flex; gap: 8px; margin-top: auto; padding-top: 12px; 
            border-top: 1px solid #f0f0f0;
            flex-wrap: wrap;
        }
        .rdv-card-actions a { 
            flex: 1; min-width: 70px; text-align: center; padding: 8px; 
            border-radius: 6px; font-size: 12px; text-decoration: none; 
            transition: all 0.3s;
            white-space: nowrap;
        }
        .rdv-card-actions .btn-view { background: #e3f2fd; color: #1565c0; }
        .rdv-card-actions .btn-view:hover { background: #1565c0; color: white; }
        .rdv-card-actions .btn-edit { background: #fff3e0; color: #f57c00; }
        .rdv-card-actions .btn-edit:hover { background: #f57c00; color: white; }
        .rdv-card-actions .btn-delete { background: #fce4ec; color: #c2185b; }
        .rdv-card-actions .btn-delete:hover { background: #c2185b; color: white; }
        
        @media (max-width: 992px) {
            .sidebar { width: 80px; }
            .sidebar-header .logo-img { width: 50px; }
            .sidebar-header small { display: none; }
            .sidebar-menu a span { display: none; }
            .sidebar-menu a { text-align: center; padding: 15px; }
            .sidebar-menu i { margin-right: 0; font-size: 20px; }
            .main-content { margin-left: 80px; }
            .navbar-menu-items { justify-content: center; }
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <div class="sidebar-header">
        <img src="assets/images/logo_doctime.png" alt="DocTime Logo" class="logo-img"
             onerror="this.style.display='none'">
        <br><small>Back Office</small>
    </div>
    <div class="sidebar-menu">
        <a href="index.php?page=dashboard">
            <i class="fas fa-tachometer-alt"></i> <span>Tableau de bord</span>
        </a>
        <a href="index.php?page=users">
            <i class="fas fa-users"></i> <span>Utilisateurs</span>
        </a>
        <a href="index.php?page=medecins_admin">
            <i class="fas fa-user-md"></i> <span>Médecins</span>
        </a>
        <a href="index.php?page=disponibilites_admin">
            <i class="fas fa-clock"></i> <span>Disponibilités</span>
        </a>
        <a href="index.php?page=admin_rendezvous" class="active">
            <i class="fas fa-calendar-check"></i> <span>Rendez vous</span>
        </a>
        <a href="index.php?page=ordonnances">
            <i class="fas fa-prescription-bottle"></i> <span>Ordonnances</span>
        </a>
        <a href="index.php?page=produits_admin">
            <i class="fas fa-box"></i> <span>Produits</span>
        </a>
        <a href="index.php?page=articles_admin">
            <i class="fas fa-blog"></i> <span>Blog</span>
        </a>
        <a href="index.php?page=evenements_admin">
            <i class="fas fa-calendar-day"></i> <span>Événements</span>
        </a>
        <a href="index.php?page=stats">
            <i class="fas fa-chart-line"></i> <span>Statistiques</span>
        </a>
        <a href="index.php?page=logs">
            <i class="fas fa-history"></i> <span>Historique</span>
        </a>
        <a href="index.php?page=settings">
            <i class="fas fa-cog"></i> <span>Paramètres</span>
        </a>
        <a href="index.php?page=logout">
            <i class="fas fa-sign-out-alt"></i> <span>Déconnexion</span>
        </a>
    </div>
</div>

<!-- Main Content -->
<div class="main-content">
    <!-- Navbar top -->
    <div class="navbar-top">
        <div class="navbar-left">
            <img src="assets/images/logo_doctime.png" alt="DocTime Logo" class="navbar-logo"
                 onerror="this.style.display='none'">
            <div class="navbar-menu-items">
                <a href="index.php?page=disponibilites_admin" class="nav-link-custom">
                    <i class="fas fa-clock"></i> Disponibilités
                </a>
                <a href="index.php?page=admin_rendezvous" class="nav-link-custom active-nav">
                    <i class="fas fa-calendar-check"></i> Rendez-vous
                </a>
                <a href="index.php?page=ordonnances" class="nav-link-custom">
                    <i class="fas fa-prescription-bottle"></i> Ordonnances
                </a>
                <a href="index.php?page=produits_admin" class="nav-link-custom">
                    <i class="fas fa-box"></i> Produits
                </a>
                <a href="index.php?page=articles_admin" class="nav-link-custom">
                    <i class="fas fa-blog"></i> Blog
                </a>
                <a href="index.php?page=evenements_admin" class="nav-link-custom">
                    <i class="fas fa-calendar-day"></i> Événements
                </a>
            </div>
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

    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h2><i class="fas fa-calendar-check me-2"></i>Gestion des rendez-vous</h2>
        <div>
            <a href="index.php?page=rendez_vous_admin&action=advanced" class="btn btn-info" title="Vue avancée avec statistiques">
                <i class="fas fa-chart-bar me-2"></i>Vue avancée
            </a>
            <a href="index.php?page=rendez_vous_admin&action=create" class="btn btn-success">
                <i class="fas fa-plus me-2"></i>Nouveau rendez-vous
            </a>
        </div>
    </div>

    <!-- Statistiques -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card" style="border-left-color: #4CAF50;">
                <i class="fas fa-calendar-check stat-icon"></i>
                <p>Total RDV</p>
                <h3><?= $stats['total'] ?? 0 ?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card" style="border-left-color: #ffc107;">
                <i class="fas fa-clock stat-icon"></i>
                <p>En attente</p>
                <h3><?= $stats['en_attente'] ?? 0 ?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card" style="border-left-color: #2A7FAA;">
                <i class="fas fa-check-circle stat-icon"></i>
                <p>Confirmés</p>
                <h3><?= $stats['confirmes'] ?? 0 ?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card" style="border-left-color: #6c757d;">
                <i class="fas fa-check-double stat-icon"></i>
                <p>Terminés</p>
                <h3><?= $stats['termines'] ?? 0 ?></h3>
            </div>
        </div>
    </div>

    <!-- Filtres -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <input type="hidden" name="page" value="admin_rendezvous">
                <div class="col-md-3">
                    <input type="date" name="date" class="form-control" value="<?= $_GET['date'] ?? '' ?>" placeholder="Date">
                </div>
                <div class="col-md-3">
                    <select name="statut" class="form-select">
                        <option value="">Tous les statuts</option>
                        <option value="en_attente" <?= ($_GET['statut'] ?? '') === 'en_attente' ? 'selected' : '' ?>>En attente</option>
                        <option value="confirmé" <?= ($_GET['statut'] ?? '') === 'confirmé' ? 'selected' : '' ?>>Confirmé</option>
                        <option value="terminé" <?= ($_GET['statut'] ?? '') === 'terminé' ? 'selected' : '' ?>>Terminé</option>
                        <option value="annulé" <?= ($_GET['statut'] ?? '') === 'annulé' ? 'selected' : '' ?>>Annulé</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" placeholder="Rechercher..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Filtrer</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Bouton Toggle Vue -->
    <div class="view-toggle">
        <button class="btn-toggle active" onclick="switchView('table')">
            <i class="fas fa-table me-2"></i>Vue Tableau
        </button>
        <button class="btn-toggle" onclick="switchView('card')">
            <i class="fas fa-th-large me-2"></i>Vue Avancée
        </button>
    </div>

    <!-- Vue Tableau -->
    <div id="tableView">
        <div class="card-body">
            <div class="table-responsive">
                <table id="rdvTable" class="table table-hover">
                    <thead>
                        <tr>
                            <th>Patient</th>
                            <th>Médecin</th>
                            <th>Spécialité</th>
                            <th>Date</th>
                            <th>Heure</th>
                            <th>Motif</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($rdvs)): ?>
                            <?php foreach ($rdvs as $rdv): ?>
                            <tr>
                                <td><?= htmlspecialchars($rdv['patient_prenom'] . ' ' . $rdv['patient_nom']) ?></td>
                                <td>Dr. <?= htmlspecialchars($rdv['medecin_prenom'] . ' ' . $rdv['medecin_nom']) ?></td>
                                <td><?= htmlspecialchars($rdv['specialite'] ?? '-') ?></td>
                                <td><?= date('d/m/Y', strtotime($rdv['date_rendezvous'])) ?></td>
                                <td><?= $rdv['heure_rendezvous'] ?></td>
                                <td><?= htmlspecialchars(substr($rdv['motif'] ?? '', 0, 30)) ?>...</div>
                                <td>
                                    <?php
                                    $badgeClass = match($rdv['statut']) {
                                        'confirmé' => 'badge-confirme',
                                        'en_attente' => 'badge-attente',
                                        'terminé' => 'badge-termine',
                                        'annulé' => 'badge-annule',
                                        default => 'badge-secondary'
                                    };
                                    ?>
                                    <span class="badge <?= $badgeClass ?> px-3 py-2"><?= $rdv['statut'] ?></span>
                                 </div>
                                <td>
                                    <a href="index.php?page=admin_rendezvous&action=view&id=<?= $rdv['id'] ?>" class="btn btn-sm btn-info" title="Voir détails"><i class="fas fa-eye"></i></a>
                                    <a href="index.php?page=admin_rendezvous&action=edit&id=<?= $rdv['id'] ?>" class="btn btn-sm btn-warning" title="Modifier"><i class="fas fa-edit"></i></a>
                                    <a href="index.php?page=admin_rendezvous&action=delete&id=<?= $rdv['id'] ?>" class="btn btn-sm btn-danger" title="Supprimer" onclick="return confirm('Supprimer ce rendez-vous ?')"><i class="fas fa-trash"></i></a>
                                 </div>
                             </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="8" class="text-center text-muted py-4">Aucun rendez-vous trouvé</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    </div>

    <!-- Vue Avancée (Cartes) -->
    <div id="cardView">
        <div class="rdv-cards-grid">
        <?php if (!empty($rdvs)): ?>
            <?php foreach ($rdvs as $rdv): ?>
            <div class="rdv-card">
                <div class="rdv-card-header">
                    <div>
                        <div class="rdv-card-label">RDV #<?= $rdv['id'] ?></div>
                        <div class="rdv-card-title">Patient</div>
                        <div class="rdv-card-text"><?= htmlspecialchars($rdv['patient_prenom'] . ' ' . $rdv['patient_nom']) ?></div>
                    </div>
                    <?php
                    $badgeClass = match($rdv['statut']) {
                        'confirmé' => 'badge-confirme',
                        'en_attente' => 'badge-attente',
                        'terminé' => 'badge-termine',
                        'annulé' => 'badge-annule',
                        default => 'badge-secondary'
                    };
                    ?>
                    <span class="rdv-card-status <?= $badgeClass ?>"><?= $rdv['statut'] ?></span>
                </div>
                
                <div class="rdv-card-info">
                    <div class="rdv-card-icon"><i class="fas fa-user-md"></i></div>
                    <div class="rdv-card-content">
                        <div class="rdv-card-label">Médecin</div>
                        <div class="rdv-card-text">Dr. <?= htmlspecialchars($rdv['medecin_prenom'] . ' ' . $rdv['medecin_nom']) ?></div>
                    </div>
                </div>
                
                <div class="rdv-card-info">
                    <div class="rdv-card-icon"><i class="fas fa-stethoscope"></i></div>
                    <div class="rdv-card-content">
                        <div class="rdv-card-label">Spécialité</div>
                        <div class="rdv-card-text"><?= htmlspecialchars($rdv['specialite'] ?? '-') ?></div>
                    </div>
                </div>
                
                <div class="rdv-card-info">
                    <div class="rdv-card-icon"><i class="fas fa-calendar"></i></div>
                    <div class="rdv-card-content">
                        <div class="rdv-card-label">Date & Heure</div>
                        <div class="rdv-card-text"><?= date('d/m/Y', strtotime($rdv['date_rendezvous'])) ?> à <?= $rdv['heure_rendezvous'] ?></div>
                    </div>
                </div>
                
                <div class="rdv-card-info">
                    <div class="rdv-card-icon"><i class="fas fa-file-alt"></i></div>
                    <div class="rdv-card-content">
                        <div class="rdv-card-label">Motif</div>
                        <div class="rdv-card-text"><?= htmlspecialchars(substr($rdv['motif'] ?? '', 0, 80)) ?><?= strlen($rdv['motif'] ?? '') > 80 ? '...' : '' ?></div>
                    </div>
                </div>
                
                <div class="rdv-card-actions">
                    <a href="index.php?page=admin_rendezvous&action=view&id=<?= $rdv['id'] ?>" class="btn-view" title="Voir détails">
                        <i class="fas fa-eye me-1"></i><span>Voir</span>
                    </a>
                    <a href="index.php?page=admin_rendezvous&action=edit&id=<?= $rdv['id'] ?>" class="btn-edit" title="Modifier">
                        <i class="fas fa-edit me-1"></i><span>Éditer</span>
                    </a>
                    <a href="index.php?page=admin_rendezvous&action=delete&id=<?= $rdv['id'] ?>" class="btn-delete" onclick="return confirm('Supprimer ce rendez-vous ?')" title="Supprimer">
                        <i class="fas fa-trash me-1"></i><span>Supprimer</span>
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="grid-column: 1/-1; text-align: center; padding: 40px; color: #999;">
                <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                <p>Aucun rendez-vous trouvé</p>
            </div>
        <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>

<script>
// === VUE TOGGLE ===
function switchView(view) {
    const tableView = document.getElementById('tableView');
    const cardView = document.getElementById('cardView');
    const buttons = document.querySelectorAll('.view-toggle .btn-toggle');
    
    buttons.forEach(btn => btn.classList.remove('active'));
    
    if (view === 'table') {
        tableView.style.display = 'block';
        cardView.style.display = 'none';
        event.target.closest('.btn-toggle').classList.add('active');
        localStorage.setItem('rdvViewMode', 'table');
    } else {
        tableView.style.display = 'none';
        cardView.style.display = 'block';
        event.target.closest('.btn-toggle').classList.add('active');
        localStorage.setItem('rdvViewMode', 'card');
    }
}

// === CHARGER VUE SAUVEGARDÉE ===
document.addEventListener('DOMContentLoaded', function() {
    const savedView = localStorage.getItem('rdvViewMode') || 'table';
    if (savedView === 'card') {
        switchView('card');
    }
    
    // DataTable initialization
    if (document.getElementById('rdvTable')) {
        $('#rdvTable').DataTable({
            language: { url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/fr-FR.json' },
            pageLength: 10,
            order: [[3, 'desc']],
            searching: false,
            paging: true
        });
    }
});
</script>
</body>
</html>