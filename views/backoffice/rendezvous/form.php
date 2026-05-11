<?php
$errors = $errors ?? [];
$isEdit = isset($rendezvous) && is_array($rendezvous);
$pageTitle = $isEdit ? 'Modifier un rendez-vous' : 'Nouveau rendez-vous';
require_once __DIR__ . '/../layout_header.php';
?>

<?php if (!empty($flash) && isset($flash['type'])): ?>
    <?php $bt = (($flash['type'] ?? '') === 'error' || ($flash['type'] ?? '') === 'danger') ? 'danger' : (($flash['type'] ?? '') === 'warning' ? 'warning' : 'success'); ?>
    <div class="alert alert-<?= htmlspecialchars($bt, ENT_QUOTES, 'UTF-8') ?> alert-dismissible fade show mb-4">
        <i class="fas fa-info-circle me-2"></i><?= htmlspecialchars((string) ($flash['message'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
    </div>
<?php endif; ?>

<div class="bo-create-page">
    <div class="bo-create-header mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
            <div>
                <h1 class="bo-create-title"><?= $isEdit ? 'Modifier le rendez-vous' : 'Nouveau rendez-vous' ?></h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="index.php?page=rendez_vous_admin">Rendez-vous</a></li>
                        <li class="breadcrumb-item active" aria-current="page"><?= $isEdit ? 'Modifier' : 'Créer' ?></li>
                    </ol>
                </nav>
            </div>
            <a href="index.php?page=rendez_vous_admin" class="btn btn-outline-secondary btn-sm rounded-pill"><i class="fas fa-arrow-left me-1"></i>Liste</a>
        </div>
    </div>

    <div class="card bo-create-card shadow-sm border-0">
        <div class="card-header bo-create-card-head py-3">
            <h6 class="mb-0"><i class="bi bi-calendar-event me-2"></i>Patient, médecin et créneau</h6>
        </div>
        <div class="card-body">
            <form class="bo-create-form" method="post" novalidate
                  action="<?= $isEdit
                      ? 'index.php?page=rendez_vous_admin&amp;action=edit&amp;id=' . (int) ($rendezvous['id'] ?? 0)
                      : 'index.php?page=rendez_vous_admin&amp;action=create' ?>">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="patient_id">Patient <span class="text-danger">*</span></label>
                        <select name="patient_id" id="patient_id" class="form-select <?= isset($errors['patient_id']) ? 'is-invalid' : '' ?>">
                            <option value="">— Sélectionner un patient —</option>
                            <?php foreach ($patients as $patient): ?>
                                <option value="<?= (int) $patient['id'] ?>" <?= (isset($old['patient_id']) && (string) $old['patient_id'] === (string) $patient['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($patient['prenom'] . ' ' . $patient['nom'], ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="error-container invalid-feedback d-block" id="patient_id-error">
                            <?php if (isset($errors['patient_id'])): ?>
                                <span class="small text-danger d-block"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($errors['patient_id'], ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="medecin_id">Médecin <span class="text-danger">*</span></label>
                        <select name="medecin_id" id="medecin_id" class="form-select <?= isset($errors['medecin_id']) ? 'is-invalid' : '' ?>">
                            <option value="">— Sélectionner un médecin —</option>
                            <?php foreach ($medecins as $medecin): ?>
                                <option value="<?= (int) $medecin['id'] ?>" <?= (isset($old['medecin_id']) && (string) $old['medecin_id'] === (string) $medecin['id']) ? 'selected' : '' ?>>
                                    Dr. <?= htmlspecialchars($medecin['prenom'] . ' ' . $medecin['nom'], ENT_QUOTES, 'UTF-8') ?> — <?= htmlspecialchars($medecin['specialite'] ?? 'Généraliste', ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="error-container invalid-feedback d-block" id="medecin_id-error">
                            <?php if (isset($errors['medecin_id'])): ?>
                                <span class="small text-danger d-block"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($errors['medecin_id'], ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="date_rendezvous">Date <span class="text-danger">*</span></label>
                        <input type="date" name="date_rendezvous" id="date_rendezvous" class="form-control <?= isset($errors['date_rendezvous']) ? 'is-invalid' : '' ?>"
                               value="<?= htmlspecialchars($old['date_rendezvous'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        <div class="error-container invalid-feedback d-block" id="date_rendezvous-error">
                            <?php if (isset($errors['date_rendezvous'])): ?>
                                <span class="small text-danger d-block"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($errors['date_rendezvous'], ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="heure_rendezvous">Heure <span class="text-danger">*</span></label>
                        <input type="time" name="heure_rendezvous" id="heure_rendezvous" class="form-control <?= isset($errors['heure_rendezvous']) ? 'is-invalid' : '' ?>"
                               value="<?= htmlspecialchars($old['heure_rendezvous'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        <div class="error-container invalid-feedback d-block" id="heure_rendezvous-error">
                            <?php if (isset($errors['heure_rendezvous'])): ?>
                                <span class="small text-danger d-block"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($errors['heure_rendezvous'], ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="motif">Motif</label>
                        <textarea name="motif" id="motif" class="form-control" rows="3" placeholder="Motif de la consultation…"><?= htmlspecialchars($old['motif'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                        <div class="error-container" id="motif-error"></div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="statut">Statut</label>
                        <?php $st = $old['statut'] ?? 'en_attente'; ?>
                        <select name="statut" id="statut" class="form-select">
                            <option value="en_attente" <?= $st === 'en_attente' ? 'selected' : '' ?>>En attente</option>
                            <option value="confirmé" <?= $st === 'confirmé' ? 'selected' : '' ?>>Confirmé</option>
                            <option value="terminé" <?= $st === 'terminé' ? 'selected' : '' ?>>Terminé</option>
                            <option value="annulé" <?= $st === 'annulé' ? 'selected' : '' ?>>Annulé</option>
                        </select>
                        <div class="error-container" id="statut-error"></div>
                    </div>
                </div>

                <div class="bo-create-actions d-flex flex-wrap gap-2 mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i><?= $isEdit ? 'Mettre à jour' : 'Créer' ?>
                    </button>
                    <a href="index.php?page=rendez_vous_admin" class="btn btn-outline-secondary">Annuler</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.querySelector('.bo-create-form').addEventListener('submit', function(e) {
    let isValid = true;

    ['patient_id-error', 'medecin_id-error', 'date_rendezvous-error', 'heure_rendezvous-error'].forEach(id => {
        const box = document.getElementById(id);
        if (box) box.innerHTML = '';
    });
    document.querySelectorAll('#patient_id, #medecin_id, #date_rendezvous, #heure_rendezvous').forEach(el => el.classList.remove('is-invalid'));

    const patient = document.getElementById('patient_id');
    if (!patient.value) {
        showError('patient_id', 'Veuillez sélectionner un patient.');
        isValid = false;
    }

    const medecin = document.getElementById('medecin_id');
    if (!medecin.value) {
        showError('medecin_id', 'Veuillez sélectionner un médecin.');
        isValid = false;
    }

    const dateEl = document.getElementById('date_rendezvous');
    if (!dateEl.value) {
        showError('date_rendezvous', 'Veuillez sélectionner une date.');
        isValid = false;
    }

    const heure = document.getElementById('heure_rendezvous');
    if (!heure.value) {
        showError('heure_rendezvous', 'Veuillez sélectionner une heure.');
        isValid = false;
    }

    if (!isValid) {
        e.preventDefault();
        const firstInv = document.querySelector('.is-invalid');
        if (firstInv) firstInv.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
});

function showError(fieldId, message) {
    const field = document.getElementById(fieldId);
    if (field) field.classList.add('is-invalid');

    const errorContainer = document.getElementById(fieldId + '-error');
    if (errorContainer) {
        const errorDiv = document.createElement('div');
        errorDiv.className = 'invalid-feedback d-block';
        errorDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + message;
        errorContainer.appendChild(errorDiv);
    }
}

document.querySelectorAll('#patient_id, #medecin_id, #date_rendezvous, #heure_rendezvous').forEach(field => {
    if (field) {
        field.addEventListener('change', function() {
            this.classList.remove('is-invalid');
            const errorContainer = document.getElementById(this.id + '-error');
            if (errorContainer) errorContainer.innerHTML = '';
        });
    }
});
</script>

<?php require_once __DIR__ . '/../layout_footer.php'; ?>
