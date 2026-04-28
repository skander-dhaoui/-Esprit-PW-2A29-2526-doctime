<?php
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: index.php?page=login');
    exit;
}

// La variable passée par le contrôleur s'appelle $rendezvous, pas $rdv
$rdv = $rendezvous ?? [];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détail du rendez-vous - Valorys Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f4f6f9; font-family: 'Segoe UI', sans-serif; }
        .card { border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .card-header { background: white; border-bottom: 1px solid #eee; padding: 15px 20px; font-weight: bold; border-radius: 15px 15px 0 0; }
        .info-label { font-weight: bold; width: 150px; display: inline-block; }
        .info-row { margin-bottom: 12px; }
    </style>
</head>
<body>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-calendar-check"></i> Détail du rendez-vous #<?= htmlspecialchars($rdv['id'] ?? '') ?></h2>
        <div>
            <a href="index.php?page=rendez_vous_admin" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Retour
            </a>
            <a href="index.php?page=rendez_vous_admin&action=edit&id=<?= $rdv['id'] ?? '' ?>" class="btn btn-warning">
                <i class="fas fa-edit"></i> Modifier
            </a>
            <a href="index.php?page=rendez_vous_admin&action=delete&id=<?= $rdv['id'] ?? '' ?>" class="btn btn-danger" onclick="return confirm('Supprimer ce rendez-vous ?')">
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

    <!-- Informations patient -->
    <div class="card">
        <div class="card-header">
            <i class="fas fa-user"></i> Informations patient
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="info-row">
                        <span class="info-label">Nom complet :</span>
                        <span><?= htmlspecialchars($rdv['patient_nom'] ?? 'Non renseigné') ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Email :</span>
                        <span><?= htmlspecialchars($rdv['patient_email'] ?? 'Non renseigné') ?></span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-row">
                        <span class="info-label">Téléphone :</span>
                        <span><?= htmlspecialchars($rdv['patient_telephone'] ?? 'Non renseigné') ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Informations médecin -->
    <div class="card">
        <div class="card-header">
            <i class="fas fa-user-md"></i> Informations médecin
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="info-row">
                        <span class="info-label">Nom complet :</span>
                        <span>Dr. <?= htmlspecialchars($rdv['medecin_nom'] ?? 'Non renseigné') ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Spécialité :</span>
                        <span><?= htmlspecialchars($rdv['specialite'] ?? 'Généraliste') ?></span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-row">
                        <span class="info-label">Email :</span>
                        <span><?= htmlspecialchars($rdv['medecin_email'] ?? 'Non renseigné') ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Cabinet :</span>
                        <span><?= htmlspecialchars($rdv['cabinet_adresse'] ?? 'Non renseigné') ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Détails du rendez-vous -->
    <div class="card">
        <div class="card-header">
            <i class="fas fa-info-circle"></i> Détails du rendez-vous
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="info-row">
                        <span class="info-label">Date :</span>
                        <span><?= !empty($rdv['date_rendezvous']) ? date('d/m/Y', strtotime($rdv['date_rendezvous'])) : 'Non définie' ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Heure :</span>
                        <span><?= htmlspecialchars($rdv['heure_rendezvous'] ?? 'Non définie') ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Motif :</span>
                        <span><?= nl2br(htmlspecialchars($rdv['motif'] ?? 'Non spécifié')) ?></span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-row">
                        <span class="info-label">Statut :</span>
                        <?php
                        $badgeClass = match($rdv['statut'] ?? 'en_attente') {
                            'confirmé' => 'success',
                            'en_attente' => 'warning',
                            'terminé' => 'info',
                            'annulé' => 'danger',
                            default => 'secondary'
                        };
                        ?>
                        <span class="badge bg-<?= $badgeClass ?>"><?= htmlspecialchars($rdv['statut'] ?? 'en_attente') ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Notes :</span>
                        <span><?= nl2br(htmlspecialchars($rdv['notes'] ?? 'Aucune note')) ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Dates -->
    <div class="card">
        <div class="card-header">
            <i class="fas fa-calendar"></i> Dates système
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="info-row">
                        <span class="info-label">Créé le :</span>
                        <span><?= !empty($rdv['created_at']) ? date('d/m/Y H:i', strtotime($rdv['created_at'])) : 'Non renseigné' ?></span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-row">
                        <span class="info-label">Dernière modification :</span>
                        <span><?= !empty($rdv['updated_at']) ? date('d/m/Y H:i', strtotime($rdv['updated_at'])) : 'Non renseigné' ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>// update
