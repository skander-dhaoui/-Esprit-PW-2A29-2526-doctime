<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($user) ? 'Modifier' : 'Créer' ?> un utilisateur - MediConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f4f6f9; font-family: 'Segoe UI', sans-serif; }
        .sidebar { position: fixed; top: 0; left: 0; width: 260px; height: 100%; background: #1e2a3e; color: white; z-index: 100; }
        .sidebar-header { padding: 25px 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-header i { font-size: 40px; color: #4CAF50; }
        .sidebar-header h3 { margin: 10px 0 0; font-size: 20px; }
        .sidebar-menu a { display: block; padding: 12px 25px; color: rgba(255,255,255,0.7); text-decoration: none; transition: all 0.3s; }
        .sidebar-menu a:hover, .sidebar-menu a.active { background: rgba(255,255,255,0.1); color: white; border-left: 4px solid #4CAF50; }
        .sidebar-menu i { width: 25px; margin-right: 10px; }
        .main-content { margin-left: 260px; padding: 25px; }
        .navbar-top { background: white; border-radius: 12px; padding: 15px 25px; margin-bottom: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: center; }
        .content-card { background: white; border-radius: 15px; padding: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .section-title { font-size: 15px; font-weight: bold; margin: 20px 0 15px; color: #2A7FAA; border-left: 4px solid #4CAF50; padding-left: 10px; }
        .form-control, .form-select { border-radius: 10px; padding: 10px 15px; border: 1px solid #ddd; }
        .form-control:focus, .form-select:focus { border-color: #4CAF50; box-shadow: 0 0 0 3px rgba(76,175,80,0.1); }
        .btn-save { background: #4CAF50; color: white; border-radius: 10px; padding: 10px 25px; border: none; }
        .btn-save:hover { background: #388E3C; }
        .alert-box { border-radius: 10px; padding: 12px 15px; margin-bottom: 20px; }
        .alert-error { background: #f8d7da; color: #721c24; }
        .medecin-fields { display: none; }
    </style>
</head>
<body>
<div class="sidebar">
    <div class="sidebar-header">
        <i class="fas fa-stethoscope"></i>
        <h3>MediConnect</h3>
        <small>Back Office</small>
    </div>
    <div class="sidebar-menu">
        <a href="index.php?page=dashboard"><i class="fas fa-tachometer-alt"></i> Tableau de bord</a>
        <a href="index.php?page=users" class="active"><i class="fas fa-users"></i> Utilisateurs</a>
        <a href="index.php?page=medecins"><i class="fas fa-user-md"></i> Médecins</a>
        <a href="index.php?page=logout"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
    </div>
</div>

<div class="main-content">
    <div class="navbar-top">
        <h4>
            <i class="fas fa-user-<?= isset($user) ? 'edit' : 'plus' ?> me-2"></i>
            <?= isset($user) ? 'Modifier l\'utilisateur' : 'Créer un utilisateur' ?>
        </h4>
        <a href="index.php?page=users" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i> Retour
        </a>
    </div>

    <div class="content-card">

        <?php if (!empty($error)): ?>
            <div class="alert-box alert-error">
                <i class="fas fa-exclamation-circle me-2"></i><?= $error ?>
            </div>
        <?php endif; ?>

        <?php
            // Valeurs à afficher : priorité aux données soumises ($old), sinon données existantes ($user/$extra)
            $v = $old ?? [];
            $currentRole = $v['role'] ?? ($user['role'] ?? 'patient');
        ?>

        <form method="POST"
              action="index.php?page=users&action=<?= isset($user) ? 'edit&id='.$user['id'] : 'create' ?>">

            <?php if (isset($user)): ?>
                <input type="hidden" name="id" value="<?= $user['id'] ?>">
            <?php endif; ?>

            <div class="section-title"><i class="fas fa-user-circle me-2"></i>Informations personnelles</div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Nom <span class="text-danger">*</span></label>
                    <input type="text" id="nom" name="nom" class="form-control"
                           value="<?= htmlspecialchars($v['nom'] ?? $user['nom'] ?? '') ?>">
                    <div class="invalid-feedback" id="nom-error"></div>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Prénom <span class="text-danger">*</span></label>
                    <input type="text" id="prenom" name="prenom" class="form-control"
                           value="<?= htmlspecialchars($v['prenom'] ?? $user['prenom'] ?? '') ?>">
                    <div class="invalid-feedback" id="prenom-error"></div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Email <span class="text-danger">*</span></label>
                    <input type="text" id="email" name="email" class="form-control"
                           value="<?= htmlspecialchars($v['email'] ?? $user['email'] ?? '') ?>">
                    <div class="invalid-feedback" id="email-error"></div>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Téléphone</label>
                    <input type="text" id="telephone" name="telephone" class="form-control"
                           value="<?= htmlspecialchars($v['telephone'] ?? $user['telephone'] ?? '') ?>">
                    <div class="invalid-feedback" id="telephone-error"></div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Date de naissance</label>
                    <input type="date" name="date_naissance" class="form-control"
                           value="<?= htmlspecialchars($v['date_naissance'] ?? $user['date_naissance'] ?? '') ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Rôle</label>
                    <select name="role" id="roleSelect" class="form-select">
                        <?php foreach (['patient','medecin','admin'] as $r): ?>
                            <option value="<?= $r ?>" <?= $currentRole===$r ? 'selected':'' ?>>
                                <?= ucfirst($r) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Statut</label>
                    <select name="statut" class="form-select">
                        <?php
                            $currentStatut = $v['statut'] ?? $user['statut'] ?? 'actif';
                            foreach (['actif','inactif','en_validation'] as $s):
                        ?>
                            <option value="<?= $s ?>" <?= $currentStatut===$s ? 'selected':'' ?>>
                                <?= ucfirst(str_replace('_',' ',$s)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if (!isset($user)): // Mot de passe seulement à la création ?>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Mot de passe <span class="text-danger">*</span></label>
                    <input type="password" id="password" name="password" class="form-control" placeholder="Min. 6 caractères">
                    <div class="invalid-feedback" id="password-error"></div>
                </div>
                <?php endif; ?>
            </div>
            <div class="mb-3">
                <label class="form-label">Adresse</label>
                <textarea name="adresse" class="form-control" rows="2"><?= htmlspecialchars($v['adresse'] ?? $user['adresse'] ?? '') ?></textarea>
            </div>

            <!-- Champs Patient -->
            <div id="patientFields" <?= $currentRole !== 'patient' ? 'style="display:none"' : '' ?>>
                <div class="section-title"><i class="fas fa-heartbeat me-2"></i>Informations patient</div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Groupe sanguin</label>
                    <select name="groupe_sanguin" class="form-select">
                        <option value="">--</option>
                        <?php
                            $groupes = ['A+','A-','B+','B-','AB+','AB-','O+','O-'];
                            $gs = $v['groupe_sanguin'] ?? $extra['groupe_sanguin'] ?? '';
                            foreach ($groupes as $g):
                        ?>
                            <option value="<?= $g ?>" <?= $gs===$g ? 'selected':'' ?>><?= $g ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Champs Médecin -->
            <div id="medecinFields" <?= $currentRole !== 'medecin' ? 'style="display:none"' : '' ?>>
                <div class="section-title"><i class="fas fa-stethoscope me-2"></i>Informations médecin</div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Spécialité</label>
                        <select name="specialite" class="form-select">
                            <option value="">Sélectionner...</option>
                            <?php
                                $specialites = ['Cardiologue','Dermatologue','Gynécologue','Pédiatre',
                                                'Généraliste','Ophtalmologue','Orthopédiste','Neurologue',
                                                'Psychiatre','Dentiste'];
                                $sp = $v['specialite'] ?? $extra['specialite'] ?? '';
                                foreach ($specialites as $s):
                            ?>
                                <option value="<?= $s ?>" <?= $sp===$s ? 'selected':'' ?>><?= $s ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">N° ordre</label>
                        <input type="text" name="numero_ordre" class="form-control"
                               value="<?= htmlspecialchars($v['numero_ordre'] ?? $extra['numero_ordre'] ?? '') ?>">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Tarif (DT)</label>
                        <input type="text" name="tarif" class="form-control"
                               value="<?= htmlspecialchars($v['tarif'] ?? $extra['tarif'] ?? '') ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Expérience (ans)</label>
                        <input type="text" name="experience" class="form-control"
                               value="<?= htmlspecialchars($v['experience'] ?? $extra['experience'] ?? '') ?>">
                    </div>
                </div>
            </div>

            <div class="d-flex gap-3 mt-4">
                <button type="submit" class="btn-save">
                    <i class="fas fa-save me-2"></i>
                    <?= isset($user) ? 'Enregistrer les modifications' : 'Créer l\'utilisateur' ?>
                </button>
                <?php if (isset($user) && $user['id'] != $_SESSION['user_id']): ?>
                <a href="index.php?page=users&action=delete&id=<?= $user['id'] ?>"
                   class="btn btn-danger ms-auto"
                   onclick="return confirm('Supprimer définitivement ?')">
                    <i class="fas fa-trash me-2"></i> Supprimer
                </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Role toggle
    document.getElementById('roleSelect').addEventListener('change', function() {
        const role = this.value;
        document.getElementById('patientFields').style.display = role === 'patient' ? 'block' : 'none';
        document.getElementById('medecinFields').style.display = role === 'medecin' ? 'block' : 'none';
    });

    // Form validation
    document.querySelector('form').addEventListener('submit', function(e) {
        let valid = true;
        document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        document.querySelectorAll('.invalid-feedback').forEach(el => { el.textContent = ''; el.style.display = 'none'; });

        function err(id, inputId, msg) {
            const inp = document.getElementById(inputId);
            const div = document.getElementById(id);
            if (inp) inp.classList.add('is-invalid');
            if (div) { div.textContent = msg; div.style.display = 'block'; }
            valid = false;
        }

        const nom = document.getElementById('nom').value.trim();
        if (!nom || nom.length < 2) err('nom-error', 'nom', 'Le nom doit contenir au moins 2 caractères.');

        const prenom = document.getElementById('prenom').value.trim();
        if (!prenom || prenom.length < 2) err('prenom-error', 'prenom', 'Le prénom doit contenir au moins 2 caractères.');

        const email = document.getElementById('email').value.trim();
        if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) err('email-error', 'email', 'Veuillez entrer un email valide.');

        const pwdEl = document.getElementById('password');
        if (pwdEl) {
            const pwd = pwdEl.value;
            if (!pwd || pwd.length < 6) err('password-error', 'password', 'Le mot de passe doit contenir au moins 6 caractères.');
        }

        if (!valid) {
            e.preventDefault();
            document.querySelector('.is-invalid')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    });
</script>
</body>
</html>