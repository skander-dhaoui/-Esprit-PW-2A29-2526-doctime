<?php
// Vue de contenu pour ajouter/éditer un médecin
// Variables disponibles: $errors (array), $medecin (array optionnel), $isEdit (bool)
$isEdit = isset($medecin);
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-lg-8">
            <h2><?php echo $isEdit ? 'Modifier le médecin' : 'Ajouter un médecin'; ?></h2>
            
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

            <form method="POST" action="<?php echo $isEdit ? 'index.php?page=medecins_admin&action=edit&id=' . $medecin['user_id'] : 'index.php?page=medecins_admin&action=add'; ?>">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="nom" class="form-label">Nom *</label>
                            <input type="text" class="form-control" id="nom" name="nom" 
                                   value="<?php echo htmlspecialchars($isEdit ? $medecin['nom'] : ($_POST['nom'] ?? '')); ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="prenom" class="form-label">Prénom *</label>
                            <input type="text" class="form-control" id="prenom" name="prenom" 
                                   value="<?php echo htmlspecialchars($isEdit ? $medecin['prenom'] : ($_POST['prenom'] ?? '')); ?>" required>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email *</label>
                    <input type="email" class="form-control" id="email" name="email" 
                           value="<?php echo htmlspecialchars($isEdit ? $medecin['email'] : ($_POST['email'] ?? '')); ?>" required>
                </div>

                <div class="mb-3">
                    <label for="telephone" class="form-label">Téléphone</label>
                    <input type="tel" class="form-control" id="telephone" name="telephone" 
                           value="<?php echo htmlspecialchars($isEdit ? $medecin['telephone'] : ($_POST['telephone'] ?? '')); ?>">
                </div>

                <div class="mb-3">
                    <label for="specialite" class="form-label">Spécialité *</label>
                    <input type="text" class="form-control" id="specialite" name="specialite" 
                           value="<?php echo htmlspecialchars($isEdit ? $medecin['specialite'] : ($_POST['specialite'] ?? '')); ?>" required>
                </div>

                <div class="mb-3">
                    <label for="numero_ordre" class="form-label">Numéro d'ordre</label>
                    <input type="text" class="form-control" id="numero_ordre" name="numero_ordre" 
                           value="<?php echo htmlspecialchars($isEdit ? $medecin['numero_ordre'] : ($_POST['numero_ordre'] ?? '')); ?>">
                </div>

                <div class="mb-3">
                    <label for="annee_experience" class="form-label">Années d'expérience</label>
                    <input type="number" class="form-control" id="annee_experience" name="annee_experience" 
                           value="<?php echo htmlspecialchars($isEdit ? $medecin['annee_experience'] : ($_POST['annee_experience'] ?? '')); ?>">
                </div>

                <div class="mb-3">
                    <label for="consultation_prix" class="form-label">Prix de consultation (€)</label>
                    <input type="number" step="0.01" class="form-control" id="consultation_prix" name="consultation_prix" 
                           value="<?php echo htmlspecialchars($isEdit ? $medecin['consultation_prix'] : ($_POST['consultation_prix'] ?? '')); ?>">
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
                    <a href="index.php?page=medecins" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Annuler
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
