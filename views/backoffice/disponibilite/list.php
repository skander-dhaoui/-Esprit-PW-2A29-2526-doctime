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
    <title>Gestion des disponibilités - Valorys</title>
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
        .badge-actif { background: #d4edda; color: #155724; padding: 5px 12px; border-radius: 20px; font-size: 12px; }
        .badge-inactif { background: #f8d7da; color: #721c24; padding: 5px 12px; border-radius: 20px; font-size: 12px; }
        .btn-sm { margin: 2px; }
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

<!-- Sidebar identique au dashboard -->
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
        <a href="index.php?page=admin_rendezvous">
            <i class="fas fa-calendar-check"></i> <span>Rendez-vous</span>
        </a>
        <a href="index.php?page=admin_disponibilite" class="active">
            <i class="fas fa-clock"></i> <span>Disponibilités</span>
        </a>
        <a href="index.php?page=admin_ordonnance">
            <i class="fas fa-prescription-bottle"></i> <span>Ordonnances</span>
        </a>
        <a href="index.php?page=produits_admin">
            <i class="fas fa-box"></i> <span>Produits</span>
        </a>
        <a href="index.php?page=blog">
            <i class="fas fa-blog"></i> <span>Blog / Forum</span>
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
    <!-- Navbar top identique au dashboard -->
    <div class="navbar-top">
        <div class="navbar-left">
            <img src="assets/images/logo_doctime.png" alt="DocTime Logo" class="navbar-logo"
                 onerror="this.style.display='none'">
            <div class="navbar-menu-items">
                <a href="index.php?page=admin_rendezvous" class="nav-link-custom">
                    <i class="fas fa-calendar-check"></i> Rendez-vous
                </a>
                <a href="index.php?page=admin_disponibilite" class="nav-link-custom active-nav">
                    <i class="fas fa-clock"></i> Disponibilités
                </a>
                <a href="index.php?page=admin_ordonnance" class="nav-link-custom">
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
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-clock me-2"></i>Gestion des disponibilités</h2>
        <a href="index.php?page=admin_disponibilite&action=create" class="btn btn-success">
            <i class="fas fa-plus me-2"></i>Ajouter un créneau
        </a>
    </div>

    <!-- Tableau -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="dispoTable" class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Médecin</th>
                            <th>Jour</th>
                            <th>Heure début</th>
                            <th>Heure fin</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($dispos)): ?>
                            <?php foreach ($dispos as $dispo): ?>
                            <tr>
                                <td><?= $dispo['id'] ?></td>
                                <td>Dr. <?= htmlspecialchars($dispo['prenom'] . ' ' . $dispo['nom']) ?></td>
                                <td><?= $dispo['jour_semaine'] ?></td>
                                <td><?= $dispo['heure_debut'] ?></td>
                                <td><?= $dispo['heure_fin'] ?></td>
                                <td>
                                    <span class="badge <?= $dispo['actif'] ? 'badge-actif' : 'badge-inactif' ?> px-3 py-2">
                                        <?= $dispo['actif'] ? 'Actif' : 'Inactif' ?>
                                    </span>
                                 </div>
                                <td>
                                    <a href="index.php?page=admin_disponibilite&action=edit&id=<?= $dispo['id'] ?>" class="btn btn-sm btn-warning" title="Modifier"><i class="fas fa-edit"></i></a>
                                    <a href="index.php?page=admin_disponibilite&action=toggle&id=<?= $dispo['id'] ?>" class="btn btn-sm <?= $dispo['actif'] ? 'btn-secondary' : 'btn-success' ?>" title="<?= $dispo['actif'] ? 'Désactiver' : 'Activer' ?>"><i class="fas <?= $dispo['actif'] ? 'fa-ban' : 'fa-check' ?>"></i></a>
                                    <a href="index.php?page=admin_disponibilite&action=delete&id=<?= $dispo['id'] ?>" class="btn btn-sm btn-danger" title="Supprimer" onclick="return confirm('Supprimer ce créneau ?')"><i class="fas fa-trash"></i></a>
                                 </div>
                             </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="text-center text-muted py-4">Aucune disponibilité trouvée</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script>
$(document).ready(function() {
    $('#dispoTable').DataTable({
        language: { url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/fr-FR.json' },
        pageLength: 10,
        order: [[0, 'desc']]
    });
});
</script>
</body>
</html>