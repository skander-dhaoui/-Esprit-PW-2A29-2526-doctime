<?php
// Vue de contenu pour le formulaire d'ajout d'utilisateur
// Variables disponibles: $errors (array), $_POST (superglobal)

// Messages d'erreur par défaut
$fieldErrors = [
    'nom' => 'Le nom est obligatoire',
    'prenom' => 'Le prénom est obligatoire', 
    'email' => 'L\'email est obligatoire et doit être valide',
    'password' => 'Le mot de passe est obligatoire (minimum 6 caractères)',
    'role' => 'Le rôle est obligatoire'
];
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-lg-6">
            <h2>Ajouter un utilisateur</h2>
            <p class="text-muted">Les champs marqués d'un astérisque (*) sont obligatoires.</p>
            
            <?php /* if (!empty($errors)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong>Erreurs :</strong>
                    <ul class="mb-0">
                        <?php foreach ($errors as $field => $message): ?>
                            <li><?php echo htmlspecialchars($message); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; */ ?>

            <form method="POST" action="index.php?page=users&action=create">
                <div class="mb-3">
                    <label for="nom" class="form-label">Nom * <small class="text-muted">(obligatoire)</small></label>
                    <input type="text" class="form-control <?= !empty($errors['nom']) ? 'is-invalid' : '' ?>" id="nom" name="nom" 
                           value="<?php echo htmlspecialchars($_POST['nom'] ?? ''); ?>">
                    <?php if (!empty($errors['nom'])): ?>
                        <div class="invalid-feedback d-block">
                            <i class="bi bi-exclamation-triangle"></i> <?= $fieldErrors['nom'] ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label for="prenom" class="form-label">Prénom * <small class="text-muted">(obligatoire)</small></label>
                    <input type="text" class="form-control <?= !empty($errors['prenom']) ? 'is-invalid' : '' ?>" id="prenom" name="prenom" 
                           value="<?php echo htmlspecialchars($_POST['prenom'] ?? ''); ?>">
                    <?php if (!empty($errors['prenom'])): ?>
                        <div class="invalid-feedback d-block">
                            <i class="bi bi-exclamation-triangle"></i> <?= $fieldErrors['prenom'] ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email * <small class="text-muted">(obligatoire)</small></label>
                    <input type="text" class="form-control <?= !empty($errors['email']) ? 'is-invalid' : '' ?>" id="email" name="email" 
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    <?php if (!empty($errors['email'])): ?>
                        <div class="invalid-feedback d-block">
                            <i class="bi bi-exclamation-triangle"></i> <?= $fieldErrors['email'] ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label for="telephone" class="form-label">Téléphone</label>
                    <input type="tel" class="form-control <?= !empty($errors['telephone']) ? 'is-invalid' : '' ?>" id="telephone" name="telephone" 
                           value="<?php echo htmlspecialchars($_POST['telephone'] ?? ''); ?>">
                    <?php if (!empty($errors['telephone'])): ?>
                        <div class="invalid-feedback d-block">
                            <i class="bi bi-exclamation-triangle"></i> <?= $fieldErrors['telephone'] ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label for="adresse" class="form-label">Adresse</label>
                    <textarea class="form-control <?= !empty($errors['adresse']) ? 'is-invalid' : '' ?>" id="adresse" name="adresse"><?php echo htmlspecialchars($_POST['adresse'] ?? ''); ?></textarea>
                    <?php if (!empty($errors['adresse'])): ?>
                        <div class="invalid-feedback d-block">
                            <i class="bi bi-exclamation-triangle"></i> <?= $fieldErrors['adresse'] ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Mot de passe * <small class="text-muted">(obligatoire)</small></label>
                    <input type="password" class="form-control <?= !empty($errors['password']) ? 'is-invalid' : '' ?>" id="password" name="password">
                    <small class="form-text text-muted">Minimum 6 caractères</small>
                    <?php if (!empty($errors['password'])): ?>
                        <div class="invalid-feedback d-block">
                            <i class="bi bi-exclamation-triangle"></i> <?= $fieldErrors['password'] ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label for="role" class="form-label">Rôle * <small class="text-muted">(obligatoire)</small></label>
                    <select class="form-control <?= !empty($errors['role']) ? 'is-invalid' : '' ?>" id="role" name="role" onchange="updateRoleFields()">
                        <option value="">Sélectionner un rôle</option>
                        <option value="patient" <?php echo ($_POST['role'] ?? '') === 'patient' ? 'selected' : ''; ?>>Patient</option>
                        <option value="medecin" <?php echo ($_POST['role'] ?? '') === 'medecin' ? 'selected' : ''; ?>>Médecin</option>
                        <option value="admin" <?php echo ($_POST['role'] ?? '') === 'admin' ? 'selected' : ''; ?>>Administrateur</option>
                    </select>
                    <?php if (!empty($errors['role'])): ?>
                        <div class="invalid-feedback d-block">
                            <i class="bi bi-exclamation-triangle"></i> <?= $fieldErrors['role'] ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div id="patient-fields" style="display: <?php echo ($_POST['role'] ?? '') === 'patient' ? 'block' : 'none'; ?>;">
                    <div class="mb-3">
                        <label for="groupe_sanguin" class="form-label">Groupe sanguin</label>
                        <select class="form-control <?= !empty($errors['groupe_sanguin']) ? 'is-invalid' : '' ?>" id="groupe_sanguin" name="groupe_sanguin">
                            <option value="">-- Non renseigné --</option>
                            <option value="O+" <?php echo ($_POST['groupe_sanguin'] ?? '') === 'O+' ? 'selected' : ''; ?>>O+</option>
                            <option value="O-" <?php echo ($_POST['groupe_sanguin'] ?? '') === 'O-' ? 'selected' : ''; ?>>O-</option>
                            <option value="A+" <?php echo ($_POST['groupe_sanguin'] ?? '') === 'A+' ? 'selected' : ''; ?>>A+</option>
                            <option value="A-" <?php echo ($_POST['groupe_sanguin'] ?? '') === 'A-' ? 'selected' : ''; ?>>A-</option>
                            <option value="B+" <?php echo ($_POST['groupe_sanguin'] ?? '') === 'B+' ? 'selected' : ''; ?>>B+</option>
                            <option value="B-" <?php echo ($_POST['groupe_sanguin'] ?? '') === 'B-' ? 'selected' : ''; ?>>B-</option>
                            <option value="AB+" <?php echo ($_POST['groupe_sanguin'] ?? '') === 'AB+' ? 'selected' : ''; ?>>AB+</option>
                            <option value="AB-" <?php echo ($_POST['groupe_sanguin'] ?? '') === 'AB-' ? 'selected' : ''; ?>>AB-</option>
                        </select>
                    </div>
                </div>

                <div id="medecin-fields" style="display: <?php echo ($_POST['role'] ?? '') === 'medecin' ? 'block' : 'none'; ?>;">
                    <div class="mb-3">
                        <label for="specialite" class="form-label">Spécialité</label>
                        <input type="text" class="form-control" id="specialite" name="specialite" 
                               value="<?php echo htmlspecialchars($_POST['specialite'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="numero_ordre" class="form-label">Numéro d'ordre</label>
                        <input type="text" class="form-control" id="numero_ordre" name="numero_ordre" 
                               value="<?php echo htmlspecialchars($_POST['numero_ordre'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="adresse_cabinet" class="form-label">Adresse du cabinet</label>
                        <textarea class="form-control" id="adresse_cabinet" name="adresse_cabinet"><?php echo htmlspecialchars($_POST['adresse_cabinet'] ?? ''); ?></textarea>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="statut" class="form-label">Statut</label>
                    <select class="form-control" id="statut" name="statut">
                        <option value="actif" <?php echo ($_POST['statut'] ?? 'actif') === 'actif' ? 'selected' : ''; ?>>Actif</option>
                        <option value="inactif" <?php echo ($_POST['statut'] ?? '') === 'inactif' ? 'selected' : ''; ?>>Inactif</option>
                    </select>
                </div>

                <div class="mb-3">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Créer l'utilisateur
                    </button>
                    <a href="index.php?page=users" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Annuler
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function updateRoleFields() {
    const role = document.getElementById('role').value;
    document.getElementById('patient-fields').style.display = role === 'patient' ? 'block' : 'none';
    document.getElementById('medecin-fields').style.display = role === 'medecin' ? 'block' : 'none';
}
</script>

