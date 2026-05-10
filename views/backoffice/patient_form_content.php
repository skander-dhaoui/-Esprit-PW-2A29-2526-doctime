<?php
// Vue de contenu pour ajouter/éditer un patient
// Variables disponibles: $errors (array), $patient (array optionnel), $isEdit (bool)
$isEdit = isset($patient);
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-lg-6">
            <h2><?php echo $isEdit ? 'Modifier le patient' : 'Ajouter un patient'; ?></h2>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong>Erreurs :</strong>
                    <ul class="mb-0">
                        <?php foreach ($errors as $field => $message): ?>
                            <li><?php echo htmlspecialchars($message); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?php echo $isEdit ? 'index.php?page=patients&action=edit&id=' . $patient['id'] : 'index.php?page=patients&action=add'; ?>">
                <div class="mb-3">
                    <label for="nom" class="form-label">Nom *</label>
                    <input type="text" class="form-control" id="nom" name="nom" 
                           value="<?php echo htmlspecialchars($isEdit ? $patient['nom'] : ($_POST['nom'] ?? '')); ?>" required>
                </div>

                <div class="mb-3">
                    <label for="prenom" class="form-label">Prénom *</label>
                    <input type="text" class="form-control" id="prenom" name="prenom" 
                           value="<?php echo htmlspecialchars($isEdit ? $patient['prenom'] : ($_POST['prenom'] ?? '')); ?>" required>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email *</label>
                    <input type="email" class="form-control" id="email" name="email" 
                           value="<?php echo htmlspecialchars($isEdit ? $patient['email'] : ($_POST['email'] ?? '')); ?>" required>
                </div>

                <div class="mb-3">
                    <label for="telephone" class="form-label">Téléphone</label>
                    <input type="tel" class="form-control" id="telephone" name="telephone" 
                           value="<?php echo htmlspecialchars($isEdit ? $patient['telephone'] : ($_POST['telephone'] ?? '')); ?>">
                </div>

                <div class="mb-3">
                    <label for="adresse" class="form-label">Adresse</label>
                    <textarea class="form-control" id="adresse" name="adresse"><?php echo htmlspecialchars($isEdit ? $patient['adresse'] : ($_POST['adresse'] ?? '')); ?></textarea>
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
