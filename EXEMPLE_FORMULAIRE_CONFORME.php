<?php
/**
 * EXEMPLE: Formulaire avec Validations Serveur (Conforme aux exigences)
 * 
 * ❌ À ÉVITER: required, type="email", pattern, minlength, etc.
 * ✅ À FAIRE: Validation côté serveur PHP uniquement
 */
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exemple: Inscription Conforme</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <h1 class="mb-4">Inscription Utilisateur</h1>

            <!-- ✅ AFFICHAGE DES ERREURS (si validations serveur échouent) -->
            <?php if (!empty($_SESSION['form_errors'])): ?>
                <div class="alert alert-danger">
                    <h5>Erreurs de formulaire:</h5>
                    <ul class="mb-0">
                        <?php foreach ($_SESSION['form_errors'] as $field => $messages): ?>
                            <?php foreach ((array)$messages as $message): ?>
                                <li><?= htmlspecialchars($message) ?></li>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php unset($_SESSION['form_errors']); // Effacer après affichage ?>
            <?php endif; ?>

            <!-- ✅ FORMULAIRE SANS ATTRIBUTS HTML5 DE VALIDATION -->
            <form method="POST" action="index.php?page=inscription">
                
                <!-- Email -->
                <div class="mb-3">
                    <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                    <!-- ❌ INTERDIT: type="email", required -->
                    <!-- ✅ CORREC: type="text" uniquement, validation serveur -->
                    <input 
                        type="text" 
                        class="form-control" 
                        id="email" 
                        name="email"
                        placeholder="exemple@mail.com"
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                    >
                    <small class="form-text text-muted">Validation: Doit être un email valide</small>
                </div>

                <!-- Mot de passe -->
                <div class="mb-3">
                    <label for="password" class="form-label">Mot de passe <span class="text-danger">*</span></label>
                    <!-- ❌ INTERDIT: type="password", required, minlength -->
                    <!-- ✅ CORRECT: type="password" (masquer seulement), validation serveur -->
                    <input 
                        type="password" 
                        class="form-control" 
                        id="password" 
                        name="password"
                        placeholder="Votre mot de passe"
                    >
                    <small class="form-text text-muted">
                        Validation: Min 8 caractères, majuscule, minuscule, chiffre, caractère spécial
                    </small>
                </div>

                <!-- Confirmation mot de passe -->
                <div class="mb-3">
                    <label for="password_confirm" class="form-label">Confirmez le mot de passe <span class="text-danger">*</span></label>
                    <input 
                        type="password" 
                        class="form-control" 
                        id="password_confirm" 
                        name="password_confirm"
                        placeholder="Confirmez votre mot de passe"
                    >
                    <small class="form-text text-muted">Validation: Doit correspondre au mot de passe</small>
                </div>

                <!-- Nom -->
                <div class="mb-3">
                    <label for="nom" class="form-label">Nom <span class="text-danger">*</span></label>
                    <!-- ❌ INTERDIT: required, minlength, maxlength -->
                    <!-- ✅ CORRECT: Pas d'attributs de validation -->
                    <input 
                        type="text" 
                        class="form-control" 
                        id="nom" 
                        name="nom"
                        placeholder="Votre nom"
                        value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>"
                    >
                    <small class="form-text text-muted">Validation: 2-50 caractères</small>
                </div>

                <!-- Prénom -->
                <div class="mb-3">
                    <label for="prenom" class="form-label">Prénom <span class="text-danger">*</span></label>
                    <input 
                        type="text" 
                        class="form-control" 
                        id="prenom" 
                        name="prenom"
                        placeholder="Votre prénom"
                        value="<?= htmlspecialchars($_POST['prenom'] ?? '') ?>"
                    >
                    <small class="form-text text-muted">Validation: 2-50 caractères</small>
                </div>

                <!-- Téléphone -->
                <div class="mb-3">
                    <label for="telephone" class="form-label">Téléphone</label>
                    <!-- ❌ INTERDIT: type="tel", pattern -->
                    <!-- ✅ CORRECT: type="text", validation serveur -->
                    <input 
                        type="text" 
                        class="form-control" 
                        id="telephone" 
                        name="telephone"
                        placeholder="+216 XX XXX XXX"
                        value="<?= htmlspecialchars($_POST['telephone'] ?? '') ?>"
                    >
                    <small class="form-text text-muted">Validation: Numéro valide (8-15 chiffres)</small>
                </div>

                <!-- Rôle -->
                <div class="mb-3">
                    <label for="role" class="form-label">Rôle <span class="text-danger">*</span></label>
                    <!-- ✅ CORRECT: Validation serveur sur valeurs acceptées -->
                    <select class="form-select" id="role" name="role">
                        <option value="">-- Sélectionnez un rôle --</option>
                        <option value="patient" <?= ($_POST['role'] ?? '') === 'patient' ? 'selected' : '' ?>>Patient</option>
                        <option value="medecin" <?= ($_POST['role'] ?? '') === 'medecin' ? 'selected' : '' ?>>Médecin</option>
                        <option value="sponsor" <?= ($_POST['role'] ?? '') === 'sponsor' ? 'selected' : '' ?>>Sponsor</option>
                    </select>
                    <small class="form-text text-muted">Validation: Doit être patient, medecin ou sponsor</small>
                </div>

                <!-- Conditions -->
                <div class="mb-3 form-check">
                    <input 
                        type="checkbox" 
                        class="form-check-input" 
                        id="terms" 
                        name="terms"
                        <?= isset($_POST['terms']) ? 'checked' : '' ?>
                    >
                    <label class="form-check-label" for="terms">
                        J'accepte les <a href="#">conditions d'utilisation</a>
                    </label>
                    <small class="form-text text-muted d-block">Validation: Doit être accepté</small>
                </div>

                <!-- Bouton Submit -->
                <button type="submit" class="btn btn-primary w-100">
                    S'inscrire
                </button>
            </form>

            <p class="text-center mt-3">
                Déjà inscrit? <a href="index.php?page=login">Connectez-vous</a>
            </p>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
