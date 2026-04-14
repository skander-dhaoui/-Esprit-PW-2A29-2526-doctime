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
    <title>Détails de l'ordonnance</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f0f2f5; font-family: 'Segoe UI', sans-serif; }
        .sidebar { width: 260px; background: #1a2035; color: white; position: fixed; height: 100%; }
        .main-content { margin-left: 260px; padding: 20px; }
        .card { border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .info-row { padding: 12px 0; border-bottom: 1px solid #eee; display: flex; }
        .info-label { width: 180px; font-weight: 600; color: #555; }
        .info-value { flex: 1; color: #333; }
        .badge-active { background: #d4edda; color: #155724; padding: 5px 15px; border-radius: 20px; }
        .badge-expired { background: #f8d7da; color: #721c24; padding: 5px 15px; border-radius: 20px; }
    </style>
</head>
<body>

<div class="sidebar p-3">
    <h4 class="text-center mb-4">Valorys Admin</h4>
    <hr>
    <a href="index.php?page=dashboard" class="text-white d-block py-2">📊 Dashboard</a>
    <a href="index.php?page=admin_rendezvous" class="text-white d-block py-2">📅 Rendez-vous</a>
    <a href="index.php?page=admin_disponibilite" class="text-white d-block py-2">⏰ Disponibilités</a>
    <a href="index.php?page=admin_ordonnance" class="text-white d-block py-2 bg-primary px-2 rounded">📋 Ordonnances</a>
    <a href="index.php?page=logout" class="text-white d-block py-2 mt-5">🚪 Déconnexion</a>
</div>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-prescription-bottle me-2"></i>Détails de l'ordonnance #<?= $ord['numero_ordonnance'] ?></h2>
        <div>
            <a href="index.php?page=admin_ordonnance" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>Retour</a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h5 class="mb-3"><i class="fas fa-user me-2"></i>Informations patient</h5>
                    <div class="info-row"><div class="info-label">Nom complet</div><div class="info-value"><?= htmlspecialchars($ord['patient_prenom'] . ' ' . $ord['patient_nom']) ?></div></div>
                    <div class="info-row"><div class="info-label">Email</div><div class="info-value"><?= htmlspecialchars($ord['patient_email'] ?? 'Non renseigné') ?></div></div>
                </div>
                <div class="col-md-6">
                    <h5 class="mb-3"><i class="fas fa-user-md me-2"></i>Informations médecin</h5>
                    <div class="info-row"><div class="info-label">Nom complet</div><div class="info-value">Dr. <?= htmlspecialchars($ord['medecin_prenom'] . ' ' . $ord['medecin_nom']) ?></div></div>
                    <div class="info-row"><div class="info-label">Spécialité</div><div class="info-value"><?= htmlspecialchars($ord['specialite'] ?? 'Généraliste') ?></div></div>
                </div>
            </div>
            <hr>
            <div class="row">
                <div class="col-md-6">
                    <h5 class="mb-3"><i class="fas fa-info-circle me-2"></i>Détails de l'ordonnance</h5>
                    <div class="info-row"><div class="info-label">Numéro</div><div class="info-value"><?= htmlspecialchars($ord['numero_ordonnance']) ?></div></div>
                    <div class="info-row"><div class="info-label">Date</div><div class="info-value"><?= date('d/m/Y', strtotime($ord['date_ordonnance'])) ?></div></div>
                    <div class="info-row"><div class="info-label">Statut</div><div class="info-value">
                        <span class="<?= $ord['status'] === 'active' ? 'badge-active' : 'badge-expired' ?>"><?= $ord['status'] === 'active' ? 'Active' : 'Expirée' ?></span>
                    </div></div>
                </div>
            </div>
            <hr>
            <div class="info-row"><div class="info-label">Diagnostic</div><div class="info-value"><?= nl2br(htmlspecialchars($ord['diagnostic'] ?? 'Non spécifié')) ?></div></div>
            <div class="info-row"><div class="info-label">Prescription</div><div class="info-value"><?= nl2br(htmlspecialchars($ord['contenu'] ?? 'Non spécifié')) ?></div></div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>