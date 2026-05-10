<?php
/**
 * Formulaire de création de disponibilité (médecin)
 */
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'medecin') {
    header('Location: ../../index.php?page=login');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer une disponibilité - Valorys</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #f0f2f5; font-family: 'Segoe UI', sans-serif; display: flex; min-height: 100vh; }
        .sidebar { 
            width: 280px; background: #1a2035; color: white; padding: 20px 0; position: fixed; 
            height: 100vh; overflow-y: auto; 
        }
        .sidebar-header { padding: 20px 25px; border-bottom: 1px solid #2a3f5f; margin-bottom: 20px; }
        .sidebar-header h5 { margin: 0; color: white; }
        .sidebar-menu { list-style: none; padding: 0; }
        .sidebar-menu li { margin: 0; }
        .sidebar-menu a { 
            display: block; color: #b8bcc4; text-decoration: none; padding: 12px 25px; 
            border-left: 3px solid transparent; transition: all 0.3s; 
        }
        .sidebar-menu a:hover { background: #2a3f5f; border-left-color: #2A7FAA; color: white; }
        .sidebar-menu a.active { background: #2a3f5f; border-left-color: #2A7FAA; color: white; }
        .main-content { margin-left: 280px; padding: 30px; }
        .form-container { background: white; border-radius: 12px; padding: 30px; max-width: 600px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .form-title { font-size: 24px; font-weight: 700; color: #333; margin-bottom: 30px; }
        .form-group { margin-bottom: 24px; }
        .form-label { display: block; margin-bottom: 8px; font-weight: 600; color: #333; }
        .form-control { 
            width: 100%; padding: 12px 15px; border: 1px solid #ddd; border-radius: 6px; 
            font-size: 14px; transition: border-color 0.3s, box-shadow 0.3s;
        }
        .form-control:focus { outline: none; border-color: #2A7FAA; box-shadow: 0 0 0 3px rgba(42, 127, 170, 0.1); }
        .form-control.is-invalid { border-color: #dc3545; }
        .form-control.is-invalid:focus { box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.1); }
        .invalid-feedback { 
            display: block; color: #dc3545; font-size: 13px; margin-top: 6px; 
        }
        .form-check { margin-bottom: 20px; }
        .form-check input { margin-right: 10px; }
        .form-check label { color: #333; margin-bottom: 0; }
        .btn-submit { 
            background: linear-gradient(135deg, #2A7FAA, #4CAF50); color: white; border: none; 
            padding: 12px 35px; border-radius: 6px; font-weight: 600; font-size: 14px; 
            cursor: pointer; transition: all 0.3s; width: 100%;
        }
        .btn-submit:hover { opacity: 0.9; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.2); }
        .btn-cancel { 
            background: #e9ecef; color: #333; border: none; padding: 12px 35px; border-radius: 6px; 
            font-weight: 600; font-size: 14px; cursor: pointer; transition: all 0.3s; margin-top: 10px; width: 100%;
        }
        .btn-cancel:hover { background: #dee2e6; }
        .alert { padding: 15px; border-radius: 6px; margin-bottom: 20px; }
        .alert-error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        .alert-success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .time-range-note { font-size: 13px; color: #666; margin-top: 4px; }
        .form-section-title { font-size: 16px; font-weight: 600; color: #333; margin-top: 25px; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 2px solid #2A7FAA; }
    </style>
</head>
<body>
    <!-- SIDEBAR -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h5><i class="fas fa-user-md me-2"></i>Dr. <?= htmlspecialchars($_SESSION['user_name'] ?? 'Médecin') ?></h5>
        </div>
        <ul class="sidebar-menu">
            <li><a href="index.php?page=medecin_dashboard"><i class="fas fa-home me-2"></i>Tableau de bord</a></li>
            <li><a href="index.php?page=disponibilites" class="active"><i class="fas fa-clock me-2"></i>Disponibilités</a></li>
            <li><a href="index.php?page=mes_rendez_vous"><i class="fas fa-calendar me-2"></i>Rendez-vous</a></li>
            <li><a href="index.php?page=mes_ordonnances"><i class="fas fa-prescription-bottle me-2"></i>Ordonnances</a></li>
            <li><hr style="margin: 15px 0; border-color: #2a3f5f;"></li>
            <li><a href="index.php?page=logout"><i class="fas fa-sign-out-alt me-2"></i>Déconnexion</a></li>
        </ul>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        <div class="form-container">
            <div class="form-title">
                <i class="fas fa-plus-circle me-2" style="color: #2A7FAA;"></i>Créer un créneau horaire
            </div>

            <!-- Messages Flash -->
            <?php if (isset($flash['error'])): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($flash['error']) ?>
                </div>
            <?php endif; ?>
            <?php if (isset($flash['success'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($flash['success']) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="index.php?page=disponibilites&action=store" novalidate>
                <!-- CSRF Token -->
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">

                <!-- Jour de la semaine -->
                <div class="form-group">
                    <label for="jour_semaine" class="form-label">
                        <i class="fas fa-calendar me-2"></i>Jour de la semaine <span style="color: #dc3545;">*</span>
                    </label>
                    <select class="form-control <?= isset($errors['jour_semaine']) ? 'is-invalid' : '' ?>" 
                            id="jour_semaine" name="jour_semaine" required>
                        <option value="">-- Sélectionner un jour --</option>
                        <option value="Lundi" <?= ($old['jour_semaine'] ?? '') === 'Lundi' ? 'selected' : '' ?>>Lundi</option>
                        <option value="Mardi" <?= ($old['jour_semaine'] ?? '') === 'Mardi' ? 'selected' : '' ?>>Mardi</option>
                        <option value="Mercredi" <?= ($old['jour_semaine'] ?? '') === 'Mercredi' ? 'selected' : '' ?>>Mercredi</option>
                        <option value="Jeudi" <?= ($old['jour_semaine'] ?? '') === 'Jeudi' ? 'selected' : '' ?>>Jeudi</option>
                        <option value="Vendredi" <?= ($old['jour_semaine'] ?? '') === 'Vendredi' ? 'selected' : '' ?>>Vendredi</option>
                        <option value="Samedi" <?= ($old['jour_semaine'] ?? '') === 'Samedi' ? 'selected' : '' ?>>Samedi</option>
                        <option value="Dimanche" <?= ($old['jour_semaine'] ?? '') === 'Dimanche' ? 'selected' : '' ?>>Dimanche</option>
                    </select>
                    <?php if (isset($errors['jour_semaine'])): ?>
                        <div class="invalid-feedback" style="display: block;">
                            <i class="fas fa-times-circle me-1"></i><?= htmlspecialchars($errors['jour_semaine']) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Horaires -->
                <div class="form-section-title">Horaires de consultation</div>

                <!-- Heure de début -->
                <div class="form-group">
                    <label for="heure_debut" class="form-label">
                        <i class="fas fa-clock me-2"></i>Heure de début <span style="color: #dc3545;">*</span>
                    </label>
                    <input type="time" 
                           class="form-control <?= isset($errors['heure_debut']) ? 'is-invalid' : '' ?>" 
                           id="heure_debut" name="heure_debut" value="<?= htmlspecialchars($old['heure_debut'] ?? '') ?>" 
                           required>
                    <div class="time-range-note">Exemple: 09:00</div>
                    <?php if (isset($errors['heure_debut'])): ?>
                        <div class="invalid-feedback" style="display: block;">
                            <i class="fas fa-times-circle me-1"></i><?= htmlspecialchars($errors['heure_debut']) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Heure de fin -->
                <div class="form-group">
                    <label for="heure_fin" class="form-label">
                        <i class="fas fa-clock me-2"></i>Heure de fin <span style="color: #dc3545;">*</span>
                    </label>
                    <input type="time" 
                           class="form-control <?= isset($errors['heure_fin']) ? 'is-invalid' : '' ?>" 
                           id="heure_fin" name="heure_fin" value="<?= htmlspecialchars($old['heure_fin'] ?? '') ?>" 
                           required>
                    <div class="time-range-note">Exemple: 17:00 (doit être après l'heure de début)</div>
                    <?php if (isset($errors['heure_fin'])): ?>
                        <div class="invalid-feedback" style="display: block;">
                            <i class="fas fa-times-circle me-1"></i><?= htmlspecialchars($errors['heure_fin']) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Pauses (optionnel) -->
                <div class="form-section-title">Pauses (optionnel)</div>

                <!-- Pause début -->
                <div class="form-group">
                    <label for="pause_debut" class="form-label">
                        <i class="fas fa-pause-circle me-2"></i>Heure de début de pause
                    </label>
                    <input type="time" 
                           class="form-control <?= isset($errors['pause_debut']) ? 'is-invalid' : '' ?>" 
                           id="pause_debut" name="pause_debut" 
                           value="<?= htmlspecialchars($old['pause_debut'] ?? '') ?>">
                    <div class="time-range-note">Laissez vide s'il n'y a pas de pause</div>
                    <?php if (isset($errors['pause_debut'])): ?>
                        <div class="invalid-feedback" style="display: block;">
                            <i class="fas fa-times-circle me-1"></i><?= htmlspecialchars($errors['pause_debut']) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Pause fin -->
                <div class="form-group">
                    <label for="pause_fin" class="form-label">
                        <i class="fas fa-pause-circle me-2"></i>Heure de fin de pause
                    </label>
                    <input type="time" 
                           class="form-control <?= isset($errors['pause_fin']) ? 'is-invalid' : '' ?>" 
                           id="pause_fin" name="pause_fin" 
                           value="<?= htmlspecialchars($old['pause_fin'] ?? '') ?>">
                    <div class="time-range-note">Laissez vide s'il n'y a pas de pause</div>
                    <?php if (isset($errors['pause_fin'])): ?>
                        <div class="invalid-feedback" style="display: block;">
                            <i class="fas fa-times-circle me-1"></i><?= htmlspecialchars($errors['pause_fin']) ?>
                        </div>
                    <?php elseif (isset($errors['pause'])): ?>
                        <div class="invalid-feedback" style="display: block;">
                            <i class="fas fa-times-circle me-1"></i><?= htmlspecialchars($errors['pause']) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Statut -->
                <div class="form-group">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="actif" name="actif" value="1" 
                               <?= (isset($old['actif']) && $old['actif'] == 1) || !isset($old) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="actif">
                            Créneau actif (disponible pour les rendez-vous)
                        </label>
                    </div>
                </div>

                <!-- Boutons -->
                <button type="submit" class="btn-submit">
                    <i class="fas fa-save me-2"></i>Créer le créneau
                </button>
                <button type="button" class="btn-cancel" onclick="history.back();">
                    <i class="fas fa-times me-2"></i>Annuler
                </button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Validation client-side
        const form = document.querySelector('form');
        const heureDebut = document.getElementById('heure_debut');
        const heureFin = document.getElementById('heure_fin');
        const pauseDebut = document.getElementById('pause_debut');
        const pauseFin = document.getElementById('pause_fin');

        form.addEventListener('submit', function(e) {
            let isValid = true;
            let errors = {};

            // Vérifier heure de fin > heure de début
            if (heureDebut.value && heureFin.value) {
                if (heureFin.value <= heureDebut.value) {
                    isValid = false;
                    errors['heure_fin'] = 'L\'heure de fin doit être après l\'heure de début';
                }
            }

            // Vérifier les pauses si présentes
            if ((pauseDebut.value || pauseFin.value)) {
                if (!pauseDebut.value || !pauseFin.value) {
                    isValid = false;
                    errors['pause'] = 'Les deux horaires de pause doivent être renseignés';
                } else if (pauseFin.value <= pauseDebut.value) {
                    isValid = false;
                    errors['pause'] = 'L\'heure de fin de pause doit être après l\'heure de début';
                } else if (pauseDebut.value <= heureDebut.value || pauseFin.value >= heureFin.value) {
                    isValid = false;
                    errors['pause'] = 'Les pauses doivent être dans l\'intervalle des horaires de consultation';
                }
            }

            if (!isValid) {
                e.preventDefault();
                alert('Veuillez vérifier les erreurs de saisie:\n\n' + Object.values(errors).join('\n'));
            }
        });

        // Clear error class on input change
        [heureDebut, heureFin, pauseDebut, pauseFin].forEach(input => {
            input.addEventListener('change', function() {
                this.classList.remove('is-invalid');
            });
        });
    </script>
</body>
</html>
