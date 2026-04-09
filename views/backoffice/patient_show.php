<?php
// views/backoffice/patient_show.php
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../../index.php?page=login');
    exit;
}
$page_title = 'Détails du patient';
$current_page = 'patients';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?= $page_title ?> - Valorys</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #f0f2f5; font-family: 'Segoe UI', sans-serif; display: flex; min-height: 100vh; }
        .sidebar { width: 260px; min-height: 100vh; background: #1a2035; color: white; position: fixed; top: 0; left: 0; }
        .sidebar-brand { padding: 25px 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.08); }
        .brand-icon { width: 55px; height: 55px; background: rgba(255,255,255,0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 12px; font-size: 24px; color: #4CAF50; }
        .sidebar-brand h4 { font-size: 18px; font-weight: 700; margin: 0; color: white; }
        .sidebar-brand small { color: rgba(255,255,255,0.5); font-size: 11px; }
        .sidebar-nav { padding: 20px 0; flex: 1; }
        .sidebar-nav a { display: flex; align-items: center; gap: 12px; padding: 12px 22px; color: rgba(255,255,255,0.7); text-decoration: none; font-size: 14px; font-weight: 500; transition: all 0.2s; border-left: 3px solid transparent; }
        .sidebar-nav a:hover { background: rgba(255,255,255,0.07); color: white; }
        .sidebar-nav a.active { background: rgba(255,255,255,0.1); color: white; border-left-color: #4CAF50; }
        .sidebar-nav a i { width: 20px; text-align: center; font-size: 16px; }
        .nav-divider { height: 1px; background: rgba(255,255,255,0.07); margin: 10px 22px; }
        .main-content { margin-left: 260px; flex: 1; padding: 25px; min-height: 100vh; }
        .page-header { background: white; border-radius: 12px; padding: 18px 25px; margin-bottom: 25px; display: flex; align-items: center; justify-content: space-between; }
        .page-header h4 { font-size: 18px; font-weight: 700; color: #1a2035; margin: 0; }
        .content-card { background: white; border-radius: 12px; padding: 25px; box-shadow: 0 1px 6px rgba(0,0,0,0.06); }
        .info-row { padding: 12px 0; border-bottom: 1px solid #eee; display: flex; }
        .info-label { width: 200px; font-weight: 600; color: #555; }
        .info-value { flex: 1; color: #333; }
        .badge-actif { background: #d4edda; color: #155724; padding: 4px 12px; border-radius: 20px; }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon"><i class="fas fa-stethoscope"></i></div>
        <h4>MediConnect</h4>
        <small>Back Office</small>
    </div>
    <nav class="sidebar-nav">
        <a href="index.php?page=dashboard"><i class="fas fa-th-large"></i> Tableau de bord</a>
        <a href="index.php?page=users"><i class="fas fa-users"></i> Utilisateurs</a>
        <a href="index.php?page=medecins_admin"><i class="fas fa-user-md"></i> Médecins</a>
        <a href="index.php?page=patients" class="active"><i class="fas fa-user-injured"></i> Patients</a>
        <div class="nav-divider"></div>
        <a href="index.php?page=logout"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
    </nav>
</div>

<div class="main-content">
    <div class="page-header">
        <h4><i class="fas fa-user-injured"></i> Détails du patient</h4>
        <div>
            <a href="index.php?page=patients&action=edit&id=<?= $patient['id'] ?>" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i> Modifier</a>
            <a href="index.php?page=patients" class="btn btn-secondary btn-sm">Retour</a>
        </div>
    </div>

    <div class="content-card">
        <div class="info-row"><div class="info-label">ID</div><div class="info-value"><?= $patient['id'] ?></div></div>
        <div class="info-row"><div class="info-label">Nom complet</div><div class="info-value"><?= htmlspecialchars($patient['prenom'] . ' ' . $patient['nom']) ?></div></div>
        <div class="info-row"><div class="info-label">Email</div><div class="info-value"><?= htmlspecialchars($patient['email']) ?></div></div>
        <div class="info-row"><div class="info-label">Téléphone</div><div class="info-value"><?= htmlspecialchars($patient['telephone'] ?? 'Non renseigné') ?></div></div>
        <div class="info-row"><div class="info-label">Groupe sanguin</div><div class="info-value"><?= htmlspecialchars($patient['groupe_sanguin'] ?? 'Non renseigné') ?></div></div>
        <div class="info-row"><div class="info-label">Adresse</div><div class="info-value"><?= nl2br(htmlspecialchars($patient['adresse'] ?? 'Non renseignée')) ?></div></div>
        <div class="info-row"><div class="info-label">Statut</div><div class="info-value"><span class="badge-actif"><?= $patient['statut'] === 'actif' ? 'Actif' : 'Inactif' ?></span></div></div>
        <div class="info-row"><div class="info-label">Inscrit le</div><div class="info-value"><?= date('d/m/Y H:i', strtotime($patient['created_at'])) ?></div></div>
    </div>
</div>
</body>
</html>