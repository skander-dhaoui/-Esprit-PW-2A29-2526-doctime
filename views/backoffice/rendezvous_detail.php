<?php
// views/backoffice/rendezvous_detail.php

if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($rdv) || !$rdv) {
    header('Location: index.php?page=rendez_vous_admin');
    exit;
}

// Ensure CSRF token exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$id = $rdv['id'] ?? 0;
$patient_nom = htmlspecialchars(($rdv['patient_prenom'] ?? '') . ' ' . ($rdv['patient_nom'] ?? ''));
$medecin_nom = htmlspecialchars(($rdv['medecin_prenom'] ?? '') . ' ' . ($rdv['medecin_nom'] ?? ''));
$date_rdv = date('d/m/Y', strtotime($rdv['date_rendezvous'] ?? 'now'));
$heure_rdv = $rdv['heure_rendezvous'] ?? '--:--';
$statut = $rdv['statut'] ?? 'en_attente';
$motif = htmlspecialchars($rdv['motif'] ?? 'N/A');
$notes = htmlspecialchars($rdv['notes_medecin'] ?? 'Aucune note');
$duree = $rdv['duree'] ?? 30;
$specialite = htmlspecialchars($rdv['specialite'] ?? 'N/A');

$badgeClass = match($statut) {
    'confirmé'  => 'bg-success',
    'terminé'   => 'bg-info',
    'annulé'    => 'bg-danger',
    default     => 'bg-warning',
};

$badgeText = match($statut) {
    'confirmé'  => '✅ Confirmé',
    'terminé'   => '✓ Terminé',
    'annulé'    => '❌ Annulé',
    default     => '⏳ En attente',
};
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RDV #<?= $id ?> - MediConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #f0f2f5; font-family: 'Segoe UI', sans-serif; display: flex; min-height: 100vh; }
        .main-content { margin-left: 260px; flex: 1; padding: 25px; }
        .page-header {
            background: white; border-radius: 12px; padding: 18px 25px; margin-bottom: 25px;
            display: flex; align-items: center; justify-content: space-between;
            box-shadow: 0 1px 6px rgba(0,0,0,0.06);
        }
        .page-header h4 { font-size: 18px; font-weight: 700; color: #1a2035; margin: 0; display: flex; align-items: center; gap: 10px; }
        .page-header h4 i { color: #0fa99b; }
        .content-card { background: white; border-radius: 12px; padding: 28px; box-shadow: 0 1px 6px rgba(0,0,0,0.06); margin-bottom: 20px; }
        
        .rdv-title { font-size: 24px; font-weight: 700; color: #1a2035; margin-bottom: 18px; }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }
        .info-box {
            background: #f8f9fa;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 16px;
        }
        .info-label { font-size: 12px; font-weight: 700; color: #6c757d; text-transform: uppercase; letter-spacing: 0.5px; }
        .info-value { font-size: 16px; font-weight: 600; color: #1a2035; margin-top: 8px; }
        .badge { padding: 6px 12px; font-size: 13px; font-weight: 600; }
        
        .action-buttons {
            display: flex;
            gap: 12px;
            margin-top: 24px;
            padding-top: 24px;
            border-top: 1px solid #e2e8f0;
        }
        .btn-custom {
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
            text-decoration: none;
        }
        .btn-edit { background: #e3f0ff; color: #1565c0; }
        .btn-edit:hover { background: #bbdefb; }
        .btn-delete { background: #fdecea; color: #c62828; }
        .btn-delete:hover { background: #ffcdd2; }
        .btn-back { background: #e2e8f0; color: #334155; }
        .btn-back:hover { background: #cbd5e1; }
        
        .sidebar { position: fixed; top: 0; left: 0; width: 260px; height: 100vh; background: #1a2035; }
    </style>
</head>
<body>

<?php include __DIR__ . '/sidebar.php'; ?>

<div class="main-content">
    <div class="page-header">
        <h4><i class="fas fa-calendar-check"></i> Détails du Rendez-vous</h4>
        <a href="index.php?page=rendez_vous_admin" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i> Retour
        </a>
    </div>

    <div class="content-card">
        <h2 class="rdv-title">Rendez-vous #<?= $id ?></h2>

        <div class="info-grid">
            <div class="info-box">
                <div class="info-label"><i class="fas fa-user me-2"></i>Patient</div>
                <div class="info-value"><?= $patient_nom ?></div>
                <small style="color: #6c757d; margin-top: 4px; display: block;">
                    📧 <?= htmlspecialchars($rdv['patient_email'] ?? '') ?>
                </small>
            </div>

            <div class="info-box">
                <div class="info-label"><i class="fas fa-stethoscope me-2"></i>Médecin</div>
                <div class="info-value"><?= $medecin_nom ?></div>
                <small style="color: #6c757d; margin-top: 4px; display: block;">
                    🏥 <?= $specialite ?>
                </small>
            </div>

            <div class="info-box">
                <div class="info-label"><i class="fas fa-calendar me-2"></i>Date & Heure</div>
                <div class="info-value"><?= $date_rdv ?> à <?= $heure_rdv ?></div>
                <small style="color: #6c757d; margin-top: 4px; display: block;">
                    ⏱️ Durée : <?= $duree ?> min
                </small>
            </div>

            <div class="info-box">
                <div class="info-label"><i class="fas fa-flag me-2"></i>Statut</div>
                <span class="badge <?= $badgeClass ?>" style="margin-top: 8px;">
                    <?= $badgeText ?>
                </span>
            </div>

            <div class="info-box">
                <div class="info-label"><i class="fas fa-note-sticky me-2"></i>Motif</div>
                <div class="info-value"><?= $motif ?></div>
            </div>

            <div class="info-box">
                <div class="info-label"><i class="fas fa-pen me-2"></i>Notes Médecin</div>
                <div class="info-value"><?= $notes ?></div>
            </div>
        </div>

        <div class="action-buttons">
            <a href="index.php?page=rendez_vous_admin&action=edit&id=<?= $id ?>" class="btn-custom btn-edit">
                <i class="fas fa-edit"></i> Modifier
            </a>
            <a href="index.php?page=rendez_vous_admin&action=delete&id=<?= $id ?>" class="btn-custom btn-delete" onclick="return confirm('Supprimer ce rendez-vous ?')">
                <i class="fas fa-trash"></i> Supprimer
            </a>
            <a href="index.php?page=rendez_vous_admin" class="btn-custom btn-back">
                <i class="fas fa-arrow-left"></i> Retour à la liste
            </a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
