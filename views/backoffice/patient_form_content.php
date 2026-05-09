<?php
// Vue de contenu pour ajouter/éditer un patient
// Variables disponibles: $errors (array), $patient (array optionnel), $isEdit (bool)
$isEdit = isset($patient);
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-lg-6">
            <h2><?php echo $isEdit ? 'Modifier le patient' : 'Ajouter un patient'; ?></h2>
            
            <form method="POST" action="<?php echo $isEdit ? 'index.php?page=patients&action=edit&id=' . $patient['id'] : 'index.php?page=patients&action=add'; ?>">
                <div class="mb-3">
                    <label for="nom" class="form-label">Nom <span class="text-danger">*</span></label>
                    <input type="text" class="form-control <?php echo !empty($errors['nom']) ? 'is-invalid' : ''; ?>" id="nom" name="nom" 
                           value="<?php echo htmlspecialchars($isEdit ? $patient['nom'] : ($_POST['nom'] ?? '')); ?>">
                    <?php if (!empty($errors['nom'])): ?>
                        <div class="invalid-feedback d-block"><?php echo htmlspecialchars($errors['nom']); ?></div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label for="prenom" class="form-label">Prénom <span class="text-danger">*</span></label>
                    <input type="text" class="form-control <?php echo !empty($errors['prenom']) ? 'is-invalid' : ''; ?>" id="prenom" name="prenom" 
                           value="<?php echo htmlspecialchars($isEdit ? $patient['prenom'] : ($_POST['prenom'] ?? '')); ?>">
                    <?php if (!empty($errors['prenom'])): ?>
                        <div class="invalid-feedback d-block"><?php echo htmlspecialchars($errors['prenom']); ?></div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                    <input type="text" class="form-control <?php echo !empty($errors['email']) ? 'is-invalid' : ''; ?>" id="email" name="email" 
                           value="<?php echo htmlspecialchars($isEdit ? $patient['email'] : ($_POST['email'] ?? '')); ?>">
                    <?php if (!empty($errors['email'])): ?>
                        <div class="invalid-feedback d-block"><?php echo htmlspecialchars($errors['email']); ?></div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label for="telephone" class="form-label">Téléphone</label>
                    <input type="tel" class="form-control <?php echo !empty($errors['telephone']) ? 'is-invalid' : ''; ?>" id="telephone" name="telephone" 
                           value="<?php echo htmlspecialchars($isEdit ? $patient['telephone'] : ($_POST['telephone'] ?? '')); ?>">
                    <?php if (!empty($errors['telephone'])): ?>
                        <div class="invalid-feedback d-block"><?php echo htmlspecialchars($errors['telephone']); ?></div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label for="adresse" class="form-label">Adresse</label>
                    <textarea class="form-control" id="adresse" name="adresse"><?php echo htmlspecialchars($isEdit ? $patient['adresse'] : ($_POST['adresse'] ?? '')); ?></textarea>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label"><?php echo $isEdit ? 'Nouveau mot de passe' : 'Mot de passe'; ?> <span class="text-danger">*</span></label>
                    <input type="password" class="form-control <?php echo !empty($errors['password']) ? 'is-invalid' : ''; ?>" id="password" name="password">
                    <?php if (!empty($errors['password'])): ?>
                        <div class="invalid-feedback d-block"><?php echo htmlspecialchars($errors['password']); ?></div>
                    <?php endif; ?>
                    <small class="form-text text-muted">
                        <?php echo $isEdit ? 'Laisser vide pour conserver le mot de passe actuel.' : 'Minimum 6 caract&egrave;res.'; ?>
                    </small>
                </div>

                <div class="mb-3">
                    <label for="groupe_sanguin" class="form-label">Groupe sanguin</label>
                    <select class="form-control" id="groupe_sanguin" name="groupe_sanguin">
                        <option value="">-- Non renseigné --</option>
                        <option value="O+" <?php echo ($isEdit && $patient['groupe_sanguin'] === 'O+') || ($_POST['groupe_sanguin'] ?? '') === 'O+' ? 'selected' : ''; ?>>O+</option>
                        <option value="O-" <?php echo ($isEdit && $patient['groupe_sanguin'] === 'O-') || ($_POST['groupe_sanguin'] ?? '') === 'O-' ? 'selected' : ''; ?>>O-</option>
                        <option value="A+" <?php echo ($isEdit && $patient['groupe_sanguin'] === 'A+') || ($_POST['groupe_sanguin'] ?? '') === 'A+' ? 'selected' : ''; ?>>A+</option>
                        <option value="A-" <?php echo ($isEdit && $patient['groupe_sanguin'] === 'A-') || ($_POST['groupe_sanguin'] ?? '') === 'A-' ? 'selected' : ''; ?>>A-</option>
                        <option value="B+" <?php echo ($isEdit && $patient['groupe_sanguin'] === 'B+') || ($_POST['groupe_sanguin'] ?? '') === 'B+' ? 'selected' : ''; ?>>B+</option>
                        <option value="B-" <?php echo ($isEdit && $patient['groupe_sanguin'] === 'B-') || ($_POST['groupe_sanguin'] ?? '') === 'B-' ? 'selected' : ''; ?>>B-</option>
                        <option value="AB+" <?php echo ($isEdit && $patient['groupe_sanguin'] === 'AB+') || ($_POST['groupe_sanguin'] ?? '') === 'AB+' ? 'selected' : ''; ?>>AB+</option>
                        <option value="AB-" <?php echo ($isEdit && $patient['groupe_sanguin'] === 'AB-') || ($_POST['groupe_sanguin'] ?? '') === 'AB-' ? 'selected' : ''; ?>>AB-</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="statut" class="form-label">Statut</label>
                    <select class="form-control" id="statut" name="statut">
                        <option value="actif" <?php echo ($isEdit && $patient['statut'] === 'actif') || ($_POST['statut'] ?? 'actif') === 'actif' ? 'selected' : ''; ?>>Actif</option>
                        <option value="inactif" <?php echo ($isEdit && $patient['statut'] === 'inactif') || ($_POST['statut'] ?? '') === 'inactif' ? 'selected' : ''; ?>>Inactif</option>
                    </select>
                </div>

                <div class="mb-3">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> <?php echo $isEdit ? 'Mettre à jour' : 'Créer'; ?>
                    </button>
                    <a href="index.php?page=patients" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Annuler
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>


