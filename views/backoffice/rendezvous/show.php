<?php
$rdv = $rendezvous ?? [];
$pageTitle = 'Rendez-vous #' . (int) ($rdv['id'] ?? 0);
require_once __DIR__ . '/../layout_header.php';
?>

<?php if (!empty($flash) && isset($flash['type'])): ?>
    <?php $bt = (($flash['type'] ?? '') === 'error' || ($flash['type'] ?? '') === 'danger') ? 'danger' : (($flash['type'] ?? '') === 'warning' ? 'warning' : 'success'); ?>
    <div class="alert alert-<?= htmlspecialchars($bt, ENT_QUOTES, 'UTF-8') ?> alert-dismissible fade show mb-4">
        <i class="fas fa-info-circle me-2"></i><?= htmlspecialchars((string) ($flash['message'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
    </div>
<?php endif; ?>

<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h1 class="h4 mb-1 fw-bold" style="color:#1e293b;">Détail du rendez-vous #<?= htmlspecialchars((string) ($rdv['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?></h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item"><a href="index.php?page=rendez_vous_admin">Rendez-vous</a></li>
                <li class="breadcrumb-item active" aria-current="page">Détail</li>
            </ol>
        </nav>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="index.php?page=rendez_vous_admin" class="btn btn-outline-secondary btn-sm rounded-pill"><i class="fas fa-arrow-left me-1"></i>Liste</a>
        <a href="index.php?page=rendez_vous_admin&amp;action=edit&amp;id=<?= (int) ($rdv['id'] ?? 0) ?>" class="btn btn-warning btn-sm rounded-pill"><i class="fas fa-edit me-1"></i>Modifier</a>
        <a href="index.php?page=rendez_vous_admin&amp;action=delete&amp;id=<?= (int) ($rdv['id'] ?? 0) ?>" class="btn btn-outline-danger btn-sm rounded-pill" onclick="return confirm('Supprimer ce rendez-vous ?');"><i class="fas fa-trash me-1"></i>Supprimer</a>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-3 border-bottom">
        <h6 class="mb-0 fw-bold"><i class="fas fa-user me-2 text-primary"></i>Patient</h6>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <p class="mb-2"><span class="text-muted small d-block">Nom complet</span><?= htmlspecialchars($rdv['patient_nom'] ?? 'Non renseigné', ENT_QUOTES, 'UTF-8') ?></p>
                <p class="mb-0"><span class="text-muted small d-block">Email</span><?= htmlspecialchars($rdv['patient_email'] ?? 'Non renseigné', ENT_QUOTES, 'UTF-8') ?></p>
            </div>
            <div class="col-md-6">
                <p class="mb-0"><span class="text-muted small d-block">Téléphone</span><?= htmlspecialchars($rdv['patient_telephone'] ?? 'Non renseigné', ENT_QUOTES, 'UTF-8') ?></p>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-3 border-bottom">
        <h6 class="mb-0 fw-bold"><i class="fas fa-user-md me-2 text-primary"></i>Médecin</h6>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <p class="mb-2"><span class="text-muted small d-block">Nom</span>Dr. <?= htmlspecialchars($rdv['medecin_nom'] ?? 'Non renseigné', ENT_QUOTES, 'UTF-8') ?></p>
                <p class="mb-0"><span class="text-muted small d-block">Spécialité</span><?= htmlspecialchars($rdv['specialite'] ?? 'Généraliste', ENT_QUOTES, 'UTF-8') ?></p>
            </div>
            <div class="col-md-6">
                <p class="mb-2"><span class="text-muted small d-block">Email</span><?= htmlspecialchars($rdv['medecin_email'] ?? 'Non renseigné', ENT_QUOTES, 'UTF-8') ?></p>
                <p class="mb-0"><span class="text-muted small d-block">Cabinet</span><?= htmlspecialchars($rdv['cabinet_adresse'] ?? 'Non renseigné', ENT_QUOTES, 'UTF-8') ?></p>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-3 border-bottom">
        <h6 class="mb-0 fw-bold"><i class="fas fa-info-circle me-2 text-primary"></i>Rendez-vous</h6>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <p class="mb-2"><span class="text-muted small d-block">Date</span><?= !empty($rdv['date_rendezvous']) ? date('d/m/Y', strtotime((string) $rdv['date_rendezvous'])) : 'Non définie' ?></p>
                <p class="mb-2"><span class="text-muted small d-block">Heure</span><?= htmlspecialchars((string) ($rdv['heure_rendezvous'] ?? 'Non définie'), ENT_QUOTES, 'UTF-8') ?></p>
                <p class="mb-0"><span class="text-muted small d-block">Motif</span><?= nl2br(htmlspecialchars((string) ($rdv['motif'] ?? 'Non spécifié'), ENT_QUOTES, 'UTF-8')) ?></p>
            </div>
            <div class="col-md-6">
                <p class="mb-2"><span class="text-muted small d-block">Statut</span>
                    <?php
                    $badgeClass = match ($rdv['statut'] ?? 'en_attente') {
                        'confirmé' => 'success',
                        'en_attente' => 'warning',
                        'terminé' => 'info',
                        'annulé' => 'danger',
                        default => 'secondary'
                    };
                    ?>
                    <span class="badge bg-<?= $badgeClass ?>"><?= htmlspecialchars((string) ($rdv['statut'] ?? 'en_attente'), ENT_QUOTES, 'UTF-8') ?></span>
                </p>
                <p class="mb-0"><span class="text-muted small d-block">Notes</span><?= nl2br(htmlspecialchars((string) ($rdv['notes'] ?? 'Aucune note'), ENT_QUOTES, 'UTF-8')) ?></p>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-3 border-bottom">
        <h6 class="mb-0 fw-bold"><i class="fas fa-clock me-2 text-primary"></i>Système</h6>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <p class="mb-0"><span class="text-muted small d-block">Créé le</span><?= !empty($rdv['created_at']) ? date('d/m/Y H:i', strtotime((string) $rdv['created_at'])) : 'Non renseigné' ?></p>
            </div>
            <div class="col-md-6">
                <p class="mb-0"><span class="text-muted small d-block">Dernière modification</span><?= !empty($rdv['updated_at']) ? date('d/m/Y H:i', strtotime((string) $rdv['updated_at'])) : 'Non renseigné' ?></p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layout_footer.php'; ?>
