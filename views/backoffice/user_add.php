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
                            <label class="form-label">Nom *</label>
                            <input type="text" name="nom" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Prénom *</label>
                            <input type="text" name="prenom" class="form-control" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email *</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Téléphone</label>
                            <input type="tel" name="telephone" class="form-control">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Mot de passe *</label>
                            <input type="password" name="password" class="form-control" required>
                            <small class="text-muted">Minimum 6 caractères</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Rôle *</label>
                            <select name="role" id="role" class="form-select" required>
                                <option value="patient">Patient</option>
                                <option value="medecin">Médecin</option>
                                <option value="admin">Administrateur</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Adresse</label>
                        <textarea name="adresse" class="form-control" rows="2"></textarea>
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
</html>