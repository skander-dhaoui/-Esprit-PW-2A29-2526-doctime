<?php
// Messages d'erreur par défaut
$fieldErrors = [
    'nom' => 'Le nom est obligatoire',
    'prenom' => 'Le prénom est obligatoire', 
    'email' => 'L\'email est obligatoire et doit être valide',
    'password' => 'Le mot de passe est obligatoire (minimum 6 caractères)',
    'role' => 'Le rôle est obligatoire'
];

require_once __DIR__ . '/layout_header_simple.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-lg-8">
            <h2>Détails de l'utilisateur</h2>
            
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

            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-person-circle me-2"></i>
                        Informations personnelles
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nom</label>
                            <p class="form-control-plaintext"><?= htmlspecialchars($user['nom'] ?? '') ?></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Prénom</label>
                            <p class="form-control-plaintext"><?= htmlspecialchars($user['prenom'] ?? '') ?></p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <p class="form-control-plaintext"><?= htmlspecialchars($user['email'] ?? '') ?></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Téléphone</label>
                            <p class="form-control-plaintext"><?= htmlspecialchars($user['telephone'] ?? '') ?></p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Date de naissance</label>
                            <p class="form-control-plaintext"><?= htmlspecialchars($user['date_naissance'] ?? '') ?></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Rôle</label>
                            <p class="form-control-plaintext">
                                <span class="badge bg-primary"><?= ucfirst($user['role'] ?? '') ?></span>
                            </p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Statut</label>
                            <p class="form-control-plaintext">
                                <span class="badge bg-<?= $user['statut'] === 'actif' ? 'success' : ($user['statut'] === 'inactif' ? 'danger' : 'warning') ?>">
                                    <?= ucfirst(str_replace('_', ' ', $user['statut'] ?? '')) ?>
                                </span>
                            </p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Date d'inscription</label>
                            <p class="form-control-plaintext"><?= htmlspecialchars($user['created_at'] ?? '') ?></p>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Adresse</label>
                        <p class="form-control-plaintext"><?= htmlspecialchars($user['adresse'] ?? '') ?></p>
                    </div>
                </div>
            </div>

            <!-- Informations spécifiques selon le rôle -->
            <?php if ($user['role'] === 'patient'): ?>
                <div class="card mt-3">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-heartbeat me-2"></i>
                            Informations patient
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Groupe sanguin</label>
                                <p class="form-control-plaintext"><?= htmlspecialchars($extras['groupe_sanguin'] ?? '') ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($user['role'] === 'medecin'): ?>
                <div class="card mt-3">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-stethoscope me-2"></i>
                            Informations médecin
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Spécialité</label>
                                <p class="form-control-plaintext"><?= htmlspecialchars($extras['specialite'] ?? '') ?></p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Numéro d'ordre</label>
                                <p class="form-control-plaintext"><?= htmlspecialchars($extras['numero_ordre'] ?? '') ?></p>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tarif (DT)</label>
                                <p class="form-control-plaintext"><?= htmlspecialchars($extras['tarif'] ?? '') ?></p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Expérience (ans)</label>
                                <p class="form-control-plaintext"><?= htmlspecialchars($extras['experience'] ?? '') ?></p>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Adresse du cabinet</label>
                            <p class="form-control-plaintext"><?= htmlspecialchars($extras['adresse_cabinet'] ?? '') ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Actions -->
            <div class="d-flex gap-2 mt-4">
                <a href="index.php?page=users&action=edit&id=<?= $user['id'] ?>" class="btn btn-primary">
                    <i class="bi bi-pencil me-2"></i> Modifier
                </a>
                <a href="index.php?page=users" class="btn btn-secondary">
                    <i class="bi bi-arrow-left me-2"></i> Retour à la liste
                </a>
                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                    <a href="index.php?page=users&action=delete&id=<?= $user['id'] ?>" 
                       class="btn btn-danger"
                       onclick="return confirm('Supprimer définitivement cet utilisateur ?')">
                        <i class="bi bi-trash me-2"></i> Supprimer
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/layout_footer.php'; ?>
