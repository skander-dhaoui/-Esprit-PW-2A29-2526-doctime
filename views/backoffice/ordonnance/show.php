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
    <title>Détail de l'ordonnance - Valorys Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f4f6f9; font-family: 'Segoe UI', sans-serif; }
        .container { margin-top: 30px; }
        .card { border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .card-header { background: white; border-bottom: 1px solid #eee; padding: 15px 20px; font-weight: bold; border-radius: 15px 15px 0 0; }
        .badge-active { background: #d4edda; color: #155724; padding: 5px 12px; border-radius: 20px; }
        .badge-expired { background: #f8d7da; color: #721c24; padding: 5px 12px; border-radius: 20px; }
        .info-row { margin-bottom: 10px; }
        .info-label { font-weight: bold; width: 150px; display: inline-block; }
    </style>
</head>
<body>
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-prescription-bottle"></i> Détail de l'ordonnance #<?= $ordonnance['id'] ?? '' ?></h2>
        <div>
            <a href="index.php?page=ordonnances" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Retour
            </a>
            <a href="index.php?page=ordonnances&action=edit&id=<?= $ordonnance['id'] ?? '' ?>" class="btn btn-warning">
                <i class="fas fa-edit"></i> Modifier
            </a>
            <a href="index.php?page=ordonnances&action=delete&id=<?= $ordonnance['id'] ?? '' ?>" class="btn btn-danger" onclick="return confirm('Supprimer cette ordonnance ?')">
                <i class="fas fa-trash"></i> Supprimer
            </a>
        </div>
    </div>

    <?php if (isset($flash)): ?>
        <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : $flash['type'] ?> alert-dismissible fade show">
            <?= htmlspecialchars($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Informations générales -->
    <div class="card">
        <div class="card-header">
            <i class="fas fa-info-circle"></i> Informations générales
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="info-row">
                        <span class="info-label">N° Ordonnance :</span>
                        <span><?= htmlspecialchars($ordonnance['numero_ordonnance'] ?? 'N/A') ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Patient :</span>
                        <span><?= htmlspecialchars($ordonnance['patient_nom'] ?? 'N/A') ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Email patient :</span>
                        <span><?= htmlspecialchars($ordonnance['patient_email'] ?? 'N/A') ?></span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-row">
                        <span class="info-label">Médecin :</span>
                        <span>Dr. <?= htmlspecialchars($ordonnance['medecin_nom'] ?? 'N/A') ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Spécialité :</span>
                        <span><?= htmlspecialchars($ordonnance['specialite'] ?? 'N/A') ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Statut :</span>
                        <span class="badge <?= ($ordonnance['status'] ?? '') === 'active' ? 'badge-active' : 'badge-expired' ?>">
                            <?= $ordonnance['status'] ?? 'N/A' ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Dates -->
    <div class="card">
        <div class="card-header">
            <i class="fas fa-calendar"></i> Dates
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="info-row">
                        <span class="info-label">Date de prescription :</span>
                        <span><?= date('d/m/Y', strtotime($ordonnance['date_ordonnance'] ?? 'now')) ?></span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-row">
                        <span class="info-label">Date d'expiration :</span>
                        <span><?= !empty($ordonnance['date_expiration']) ? date('d/m/Y', strtotime($ordonnance['date_expiration'])) : 'N/A' ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Diagnostic -->
    <div class="card">
        <div class="card-header">
            <i class="fas fa-stethoscope"></i> Diagnostic
        </div>
        <div class="card-body">
            <p><?= nl2br(htmlspecialchars($ordonnance['diagnostic'] ?? '')) ?></p>
        </div>
    </div>

    <!-- Médicaments / Contenu -->
    <div class="card">
        <div class="card-header">
            <i class="fas fa-pills"></i> Prescription
        </div>
        <div class="card-body">
            <?php if (!empty($medicaments)): ?>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Médicament</th>
                                <th>Dosage</th>
                                <th>Fréquence</th>
                                <th>Durée</th>
                                <th>Indication</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($medicaments as $med): ?>
                            <tr>
                                <td><?= htmlspecialchars($med['nom'] ?? '') ?></td>
                                <td><?= htmlspecialchars($med['dosage'] ?? '') ?></td>
                                <td><?= htmlspecialchars($med['frequence'] ?? '') ?></td>
                                <td><?= htmlspecialchars($med['duree_jours'] ?? '') ?> <?= !empty($med['duree_jours']) ? 'jours' : '' ?></td>
                                <td><?= htmlspecialchars($med['indication'] ?? '') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p><?= nl2br(htmlspecialchars($ordonnance['contenu'] ?? '')) ?></p>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>