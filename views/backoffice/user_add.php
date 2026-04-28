<?php
// views/backoffice/user_add.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../../index.php?page=login');
    exit;
}

// Générer un token CSRF si nécessaire
$csrf_token = bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $csrf_token;
$errors = isset($errors) ? $errors : [];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter un utilisateur - Valorys</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: #f0f2f5;
            font-family: 'Segoe UI', sans-serif;
        }
        .form-container {
            max-width: 800px;
            margin: 30px auto;
        }
        .card {
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            border: none;
        }
        .card-header {
            background: linear-gradient(135deg, #2A7FAA 0%, #4CAF50 100%);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            padding: 20px;
        }
        .btn-submit {
            background: #4CAF50;
            border: none;
            padding: 12px;
            border-radius: 10px;
            font-weight: bold;
        }
        .btn-submit:hover {
            background: #2A7FAA;
        }
        .form-label {
            font-weight: 600;
            color: #333;
        }
        .form-control, .form-select {
            border-radius: 10px;
            padding: 10px 15px;
            border: 1px solid #ddd;
        }
        .form-control:focus, .form-select:focus {
            border-color: #4CAF50;
            box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.1);
        }
        .field-error { font-size: 12px; margin-top: 6px; color: #c62828; font-weight: 500; }
        .field-error i { margin-right: 5px; }
        .form-control.error, .form-select.error { border-color: #dc3545 !important; }
    </style>
</head>
<body>
    <div class="container form-container">
        <div class="card">
            <div class="card-header">
                <h3 class="mb-0">
                    <i class="fas fa-user-plus me-2"></i> Ajouter un utilisateur
                </h3>
                <p class="mb-0 mt-2 opacity-75">Créer un nouveau compte utilisateur</p>
            </div>
            <div class="card-body p-4">
                <!-- Affichage des messages flash -->
                <?php if (isset($_SESSION['flash'])): ?>
                    <div class="alert alert-<?= $_SESSION['flash']['type'] === 'error' ? 'danger' : 'success' ?> alert-dismissible fade show">
                        <i class="fas fa-<?= $_SESSION['flash']['type'] === 'error' ? 'exclamation-circle' : 'check-circle' ?> me-2"></i>
                        <?= htmlspecialchars($_SESSION['flash']['message']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['flash']); ?>
                <?php endif; ?>

                <form method="POST" action="index.php?page=users&action=create">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nom <span class="text-danger">*</span></label>
                            <input type="text" name="nom" class="form-control<?php echo isset($errors['nom']) ? ' error' : ''; ?>" value="<?php echo htmlspecialchars($_POST['nom'] ?? ''); ?>" required>
                            <?php if(isset($errors['nom'])): ?>
                                <div class="field-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($errors['nom']); ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Prénom <span class="text-danger">*</span></label>
                            <input type="text" name="prenom" class="form-control<?php echo isset($errors['prenom']) ? ' error' : ''; ?>" value="<?php echo htmlspecialchars($_POST['prenom'] ?? ''); ?>" required>
                            <?php if(isset($errors['prenom'])): ?>
                                <div class="field-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($errors['prenom']); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control<?php echo isset($errors['email']) ? ' error' : ''; ?>" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                            <?php if(isset($errors['email'])): ?>
                                <div class="field-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($errors['email']); ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Téléphone</label>
                            <input type="tel" name="telephone" class="form-control<?php echo isset($errors['telephone']) ? ' error' : ''; ?>" value="<?php echo htmlspecialchars($_POST['telephone'] ?? ''); ?>">
                            <?php if(isset($errors['telephone'])): ?>
                                <div class="field-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($errors['telephone']); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Mot de passe <span class="text-danger">*</span></label>
                            <input type="password" name="password" class="form-control<?php echo isset($errors['password']) ? ' error' : ''; ?>" required>
                            <small class="text-muted">Minimum 6 caractères</small>
                            <?php if(isset($errors['password'])): ?>
                                <div class="field-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($errors['password']); ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Rôle <span class="text-danger">*</span></label>
                            <select name="role" id="role" class="form-select<?php echo isset($errors['role']) ? ' error' : ''; ?>" required>
                                <option value="patient" <?php echo ($_POST['role'] ?? '') === 'patient' ? 'selected' : ''; ?>>Patient</option>
                                <option value="medecin" <?php echo ($_POST['role'] ?? '') === 'medecin' ? 'selected' : ''; ?>>Médecin</option>
                                <option value="admin" <?php echo ($_POST['role'] ?? '') === 'admin' ? 'selected' : ''; ?>>Administrateur</option>
                            </select>
                            <?php if(isset($errors['role'])): ?>
                                <div class="field-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($errors['role']); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Adresse</label>
                        <textarea name="adresse" class="form-control<?php echo isset($errors['adresse']) ? ' error' : ''; ?>" rows="2"><?php echo htmlspecialchars($_POST['adresse'] ?? ''); ?></textarea>
                        <?php if(isset($errors['adresse'])): ?>
                            <div class="field-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($errors['adresse']); ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Date de naissance</label>
                            <input type="date" name="date_naissance" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Statut</label>
                            <select name="statut" class="form-select">
                                <option value="actif">Actif</option>
                                <option value="inactif">Inactif</option>
                            </select>
                        </div>
                    </div>

                    <!-- Champs spécifiques patient -->
                    <div id="patientFields" style="display: none;">
                        <div class="alert alert-info mt-2">
                            <i class="fas fa-info-circle me-2"></i> Informations médicales
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Groupe sanguin</label>
                            <select name="groupe_sanguin" class="form-select">
                                <option value="">Non renseigné</option>
                                <option value="A+">A+</option>
                                <option value="A-">A-</option>
                                <option value="B+">B+</option>
                                <option value="B-">B-</option>
                                <option value="AB+">AB+</option>
                                <option value="AB-">AB-</option>
                                <option value="O+">O+</option>
                                <option value="O-">O-</option>
                            </select>
                        </div>
                    </div>

                    <!-- Champs spécifiques médecin -->
                    <div id="medecinFields" style="display: none;">
                        <div class="alert alert-info mt-2">
                            <i class="fas fa-stethoscope me-2"></i> Informations professionnelles
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Spécialité *</label>
                                <input type="text" name="specialite" class="form-control" placeholder="Ex: Cardiologie">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Numéro d'ordre</label>
                                <input type="text" name="numero_ordre" class="form-control">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tarif consultation (€)</label>
                                <input type="number" name="tarif" class="form-control" step="1" value="50">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Années d'expérience</label>
                                <input type="number" name="experience" class="form-control" value="0">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Adresse du cabinet</label>
                            <textarea name="adresse_cabinet" class="form-control" rows="2"></textarea>
                        </div>
                    </div>

                    <hr>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-submit text-white flex-grow-1">
                            <i class="fas fa-save me-2"></i> Créer l'utilisateur
                        </button>
                        <a href="index.php?page=users" class="btn btn-secondary">
                            <i class="fas fa-times me-2"></i> Annuler
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Afficher/masquer les champs selon le rôle sélectionné
        const roleSelect = document.getElementById('role');
        const patientFields = document.getElementById('patientFields');
        const medecinFields = document.getElementById('medecinFields');

        function toggleFields() {
            const role = roleSelect.value;
            patientFields.style.display = 'none';
            medecinFields.style.display = 'none';
            
            if (role === 'patient') {
                patientFields.style.display = 'block';
            } else if (role === 'medecin') {
                medecinFields.style.display = 'block';
            }
        }

        roleSelect.addEventListener('change', toggleFields);
        toggleFields(); // Initialisation
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>// update
