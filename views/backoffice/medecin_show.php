<?php
// views/backoffice/medecin_show.php
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../../index.php?page=login');
    exit;
}
$page_title = 'Détails du médecin';
$current_page = 'medecins_admin';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?= $page_title ?> - Valorys</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <?php require_once __DIR__ . '/../partials/backoffice_shell_styles.php'; ?>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body.bo-shell-body { background: #f0f2f5; font-family: 'Segoe UI', sans-serif; min-height: 100vh; }
        .page-header { background: white; border-radius: 12px; padding: 18px 25px; margin-bottom: 25px; display: flex; align-items: center; justify-content: space-between; }
        .page-header h4 { font-size: 18px; font-weight: 700; color: #1a2035; margin: 0; }
        .content-card { background: white; border-radius: 12px; padding: 25px; box-shadow: 0 1px 6px rgba(0,0,0,0.06); }
        .info-row { padding: 12px 0; border-bottom: 1px solid #eee; display: flex; }
        .info-label { width: 200px; font-weight: 600; color: #555; }
        .info-value { flex: 1; color: #333; }
        .badge-actif { background: #d4edda; color: #155724; padding: 4px 12px; border-radius: 20px; }
    </style>
</head>
<body class="bo-shell-body">
<?php require_once __DIR__ . '/sidebar.php'; ?>

<div class="main-content">
    <div class="page-header">
        <h4><i class="fas fa-user-md"></i> Détails du médecin</h4>
        <div>
            <a href="index.php?page=medecins_admin&action=edit&id=<?= $medecin['id'] ?>" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i> Modifier</a>
            <a href="index.php?page=medecins_admin" class="btn btn-secondary btn-sm">Retour</a>
        </div>
    </div>

    <div class="content-card">
        <div class="info-row"><div class="info-label">Nom complet</div><div class="info-value">Dr. <?= htmlspecialchars($medecin['prenom'] . ' ' . $medecin['nom']) ?></div></div>
        <div class="info-row"><div class="info-label">Email</div><div class="info-value"><?= htmlspecialchars($medecin['email']) ?></div></div>
        <div class="info-row"><div class="info-label">Téléphone</div><div class="info-value"><?= htmlspecialchars($medecin['telephone'] ?? 'Non renseigné') ?></div></div>
        <div class="info-row"><div class="info-label">Spécialité</div><div class="info-value"><?= htmlspecialchars($medecin['specialite'] ?? 'Non renseignée') ?></div></div>
        <div class="info-row"><div class="info-label">Numéro d'ordre</div><div class="info-value"><?= htmlspecialchars($medecin['numero_ordre'] ?? 'Non renseigné') ?></div></div>
        <div class="info-row"><div class="info-label">Tarif consultation</div><div class="info-value"><?= $medecin['consultation_prix'] ?? 'Non renseigné' ?> €</div></div>
        <div class="info-row"><div class="info-label">Années d'expérience</div><div class="info-value"><?= $medecin['annee_experience'] ?? '0' ?> ans</div></div>
        <div class="info-row"><div class="info-label">Adresse cabinet</div><div class="info-value"><?= nl2br(htmlspecialchars($medecin['cabinet_adresse'] ?? 'Non renseignée')) ?></div></div>
        <div class="info-row"><div class="info-label">Statut</div><div class="info-value"><span class="badge-actif"><?= $medecin['statut'] === 'actif' ? 'Actif' : 'Inactif' ?></span></div></div>
        <div class="info-row"><div class="info-label">Inscrit le</div><div class="info-value"><?= date('d/m/Y H:i', strtotime($medecin['created_at'])) ?></div></div>
    </div>
</div>
</body>
</html>
