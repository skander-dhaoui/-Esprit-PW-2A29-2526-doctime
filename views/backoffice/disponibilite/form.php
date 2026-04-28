<?php
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: index.php?page=login');
    exit;
}
$errors = $errors ?? [];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($disponibilite) ? 'Modifier' : 'Ajouter' ?> une disponibilité - Valorys Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .field-error { font-size: 12px; margin-top: 5px; color: #dc3545; font-weight: normal; }
        .field-error i { margin-right: 5px; }
        .form-control.error, .form-select.error { border-color: #dc3545 !important; }
        .error-container { min-height: 32px; }
    </style>
</head>
<body>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-clock"></i> <?= isset($disponibilite) ? 'Modifier' : 'Ajouter' ?> une disponibilité</h2>
        <a href="index.php?page=disponibilites_admin" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Retour
        </a>
    </div>

    <?php if (isset($flash) && isset($flash['type']) && $flash['type'] !== 'field'): ?>
        <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : $flash['type'] ?> alert-dismissible fade show">
            <?= htmlspecialchars($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="POST" novalidate action="index.php?page=disponibilites_admin&action=<?= isset($disponibilite) ? 'edit&id=' . (int)$disponibilite['id'] : 'create' ?>">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Médecin <span class="text-danger">*</span></label>
                        <select name="medecin_id" class="form-select <?= isset($errors['medecin_id']) ? 'error' : '' ?>">
                            <option value="">-- Sélectionner un médecin --</option>
                            <?php foreach ($medecins as $medecin): ?>
                                <option value="<?= $medecin['id'] ?>" <?= (isset($old['medecin_id']) && $old['medecin_id'] == $medecin['id']) || (isset($disponibilite) && $disponibilite['medecin_id'] == $medecin['id']) ? 'selected' : '' ?>>
                                    Dr. <?= htmlspecialchars($medecin['prenom'] . ' ' . $medecin['nom']) ?> - <?= htmlspecialchars($medecin['specialite'] ?? '') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="error-container" id="medecin_id-error">
                            <?php if (isset($errors['medecin_id'])): ?>
                                <div class="field-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($errors['medecin_id']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Jour de la semaine <span class="text-danger">*</span></label>
                        <select name="jour_semaine" class="form-select <?= isset($errors['jour_semaine']) ? 'error' : '' ?>">
                            <option value="">-- Sélectionner un jour --</option>
                            <option value="Lundi" <?= (isset($old['jour_semaine']) && $old['jour_semaine'] == 'Lundi') || (isset($disponibilite) && $disponibilite['jour_semaine'] == 'Lundi') ? 'selected' : '' ?>>Lundi</option>
                            <option value="Mardi" <?= (isset($old['jour_semaine']) && $old['jour_semaine'] == 'Mardi') || (isset($disponibilite) && $disponibilite['jour_semaine'] == 'Mardi') ? 'selected' : '' ?>>Mardi</option>
                            <option value="Mercredi" <?= (isset($old['jour_semaine']) && $old['jour_semaine'] == 'Mercredi') || (isset($disponibilite) && $disponibilite['jour_semaine'] == 'Mercredi') ? 'selected' : '' ?>>Mercredi</option>
                            <option value="Jeudi" <?= (isset($old['jour_semaine']) && $old['jour_semaine'] == 'Jeudi') || (isset($disponibilite) && $disponibilite['jour_semaine'] == 'Jeudi') ? 'selected' : '' ?>>Jeudi</option>
                            <option value="Vendredi" <?= (isset($old['jour_semaine']) && $old['jour_semaine'] == 'Vendredi') || (isset($disponibilite) && $disponibilite['jour_semaine'] == 'Vendredi') ? 'selected' : '' ?>>Vendredi</option>
                            <option value="Samedi" <?= (isset($old['jour_semaine']) && $old['jour_semaine'] == 'Samedi') || (isset($disponibilite) && $disponibilite['jour_semaine'] == 'Samedi') ? 'selected' : '' ?>>Samedi</option>
                            <option value="Dimanche" <?= (isset($old['jour_semaine']) && $old['jour_semaine'] == 'Dimanche') || (isset($disponibilite) && $disponibilite['jour_semaine'] == 'Dimanche') ? 'selected' : '' ?>>Dimanche</option>
                        </select>
                        <div class="error-container" id="jour_semaine-error">
                            <?php if (isset($errors['jour_semaine'])): ?>
                                <div class="field-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($errors['jour_semaine']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Heure début <span class="text-danger">*</span></label>
                        <input type="time" name="heure_debut" class="form-control <?= isset($errors['heure_debut']) ? 'error' : '' ?>"
                               value="<?= htmlspecialchars($old['heure_debut'] ?? ($disponibilite['heure_debut'] ?? '')) ?>">
                        <div class="error-container" id="heure_debut-error">
                            <?php if (isset($errors['heure_debut'])): ?>
                                <div class="field-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($errors['heure_debut']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Heure fin <span class="text-danger">*</span></label>
                        <input type="time" name="heure_fin" class="form-control <?= isset($errors['heure_fin']) ? 'error' : '' ?>"
                               value="<?= htmlspecialchars($old['heure_fin'] ?? ($disponibilite['heure_fin'] ?? '')) ?>">
                        <div class="error-container" id="heure_fin-error">
                            <?php if (isset($errors['heure_fin'])): ?>
                                <div class="field-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($errors['heure_fin']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Pause début (optionnel)</label>
                        <input type="time" name="pause_debut" class="form-control"
                               value="<?= htmlspecialchars($old['pause_debut'] ?? ($disponibilite['pause_debut'] ?? '')) ?>">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Pause fin (optionnel)</label>
                        <input type="time" name="pause_fin" class="form-control"
                               value="<?= htmlspecialchars($old['pause_fin'] ?? ($disponibilite['pause_fin'] ?? '')) ?>">
                    </div>
                </div>

                <div class="mb-3 form-check">
                    <input type="checkbox" name="actif" class="form-check-input" id="actif" value="1" <?= !empty($old['actif']) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="actif">Disponibilité active</label>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="index.php?page=disponibilites_admin" class="btn btn-secondary">Annuler</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> <?= isset($disponibilite) ? 'Mettre à jour' : 'Créer' ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
// update
