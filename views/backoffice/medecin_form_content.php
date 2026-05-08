<?php
// Vue de contenu pour ajouter/éditer un médecin
// Variables disponibles: $errors (array), $medecin (array optionnel), $isEdit (bool)
$isEdit = isset($medecin);
$medecinId = $medecin['user_id'] ?? $medecin['id'] ?? null;
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-lg-8">
            <h2><?php echo $isEdit ? 'Modifier le médecin' : 'Ajouter un médecin'; ?></h2>
            
            <form method="POST" action="<?php echo $isEdit ? 'index.php?page=medecins_admin&action=edit&id=' . $medecinId : 'index.php?page=medecins_admin&action=add'; ?>">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="nom" class="form-label">Nom <span class="text-danger">*</span></label>
                            <input type="text" class="form-control <?php echo !empty($errors['nom']) ? 'is-invalid' : ''; ?>" id="nom" name="nom" 
                                   value="<?php echo htmlspecialchars($isEdit ? $medecin['nom'] : ($_POST['nom'] ?? '')); ?>">
                            <?php if (!empty($errors['nom'])): ?>
                                <div class="invalid-feedback d-block"><?php echo htmlspecialchars($errors['nom']); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="prenom" class="form-label">Prénom <span class="text-danger">*</span></label>
                            <input type="text" class="form-control <?php echo !empty($errors['prenom']) ? 'is-invalid' : ''; ?>" id="prenom" name="prenom" 
                                   value="<?php echo htmlspecialchars($isEdit ? $medecin['prenom'] : ($_POST['prenom'] ?? '')); ?>">
                            <?php if (!empty($errors['prenom'])): ?>
                                <div class="invalid-feedback d-block"><?php echo htmlspecialchars($errors['prenom']); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                    <input type="text" class="form-control <?php echo !empty($errors['email']) ? 'is-invalid' : ''; ?>" id="email" name="email" 
                           value="<?php echo htmlspecialchars($isEdit ? $medecin['email'] : ($_POST['email'] ?? '')); ?>">
                    <?php if (!empty($errors['email'])): ?>
                        <div class="invalid-feedback d-block"><?php echo htmlspecialchars($errors['email']); ?></div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label for="telephone" class="form-label">Téléphone</label>
                    <input type="tel" class="form-control <?php echo !empty($errors['telephone']) ? 'is-invalid' : ''; ?>" id="telephone" name="telephone" 
                           value="<?php echo htmlspecialchars($isEdit ? $medecin['telephone'] : ($_POST['telephone'] ?? '')); ?>">
                    <?php if (!empty($errors['telephone'])): ?>
                        <div class="invalid-feedback d-block"><?php echo htmlspecialchars($errors['telephone']); ?></div>
                    <?php endif; ?>
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
                    <label for="specialite" class="form-label">Spécialité <span class="text-danger">*</span></label>
                    <input type="text" class="form-control <?php echo !empty($errors['specialite']) ? 'is-invalid' : ''; ?>" id="specialite" name="specialite" 
                           value="<?php echo htmlspecialchars($isEdit ? $medecin['specialite'] : ($_POST['specialite'] ?? '')); ?>">
                    <?php if (!empty($errors['specialite'])): ?>
                        <div class="invalid-feedback d-block"><?php echo htmlspecialchars($errors['specialite']); ?></div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label for="numero_ordre" class="form-label">Numéro d'ordre</label>
                    <input type="text" class="form-control" id="numero_ordre" name="numero_ordre" 
                           value="<?php echo htmlspecialchars($isEdit ? $medecin['numero_ordre'] : ($_POST['numero_ordre'] ?? '')); ?>">
                </div>

                <div class="mb-3">
                    <label for="annee_experience" class="form-label">Années d'expérience <span class="text-danger">*</span></label>
                    <input type="text" class="form-control <?php echo !empty($errors['annee_experience']) ? 'is-invalid' : ''; ?>" id="annee_experience" name="annee_experience" 
                           value="<?php echo htmlspecialchars($isEdit ? $medecin['annee_experience'] : ($_POST['annee_experience'] ?? '')); ?>">
                    <?php if (!empty($errors['annee_experience'])): ?>
                        <div class="invalid-feedback d-block"><?php echo htmlspecialchars($errors['annee_experience']); ?></div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label for="consultation_prix" class="form-label">Prix de consultation (€) <span class="text-danger">*</span></label>
                    <input type="text" class="form-control <?php echo !empty($errors['consultation_prix']) ? 'is-invalid' : ''; ?>" id="consultation_prix" name="consultation_prix" 
                           value="<?php echo htmlspecialchars($isEdit ? $medecin['consultation_prix'] : ($_POST['consultation_prix'] ?? '')); ?>">
                    <?php if (!empty($errors['consultation_prix'])): ?>
                        <div class="invalid-feedback d-block"><?php echo htmlspecialchars($errors['consultation_prix']); ?></div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label for="cabinet_adresse" class="form-label">Adresse du cabinet</label>
                    <textarea class="form-control" id="cabinet_adresse" name="cabinet_adresse"><?php echo htmlspecialchars($isEdit ? $medecin['cabinet_adresse'] : ($_POST['cabinet_adresse'] ?? '')); ?></textarea>
                </div>

                <div class="mb-3">
                    <label for="statut" class="form-label">Statut</label>
                    <select class="form-control" id="statut" name="statut">
                        <option value="actif" <?php echo ($isEdit && $medecin['statut'] === 'actif') || ($_POST['statut'] ?? 'actif') === 'actif' ? 'selected' : ''; ?>>Actif</option>
                        <option value="inactif" <?php echo ($isEdit && $medecin['statut'] === 'inactif') || ($_POST['statut'] ?? '') === 'inactif' ? 'selected' : ''; ?>>Inactif</option>
                    </select>
                </div>

                <div class="mb-3">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> <?php echo $isEdit ? 'Mettre à jour' : 'Créer'; ?>
                    </button>
                    <a href="index.php?page=medecins_admin" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Annuler
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

