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
        .field-error { font-size: 12px; margin-top: 6px; color: #c62828; font-weight: 500; }
        .field-error i { margin-right: 5px; }
        .form-control.error, .form-select.error { border-color: #dc3545 !important; }
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
            $errors = isset($errors) ? $errors : [];
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
                    <input type="text" name="nom" class="form-control<?php echo isset($errors['nom']) ? ' error' : ''; ?>"
                           value="<?= htmlspecialchars($v['nom'] ?? $user['nom'] ?? '') ?>">
                    <?php if(isset($errors['nom'])): ?>
                        <div class="field-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($errors['nom']); ?></div>
                    <?php endif; ?>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Prénom <span class="text-danger">*</span></label>
                    <input type="text" name="prenom" class="form-control<?php echo isset($errors['prenom']) ? ' error' : ''; ?>"
                           value="<?= htmlspecialchars($v['prenom'] ?? $user['prenom'] ?? '') ?>">
                    <?php if(isset($errors['prenom'])): ?>
                        <div class="field-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($errors['prenom']); ?></div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Email <span class="text-danger">*</span></label>
                    <input type="text" name="email" class="form-control<?php echo isset($errors['email']) ? ' error' : ''; ?>"
                           value="<?= htmlspecialchars($v['email'] ?? $user['email'] ?? '') ?>">
                    <?php if(isset($errors['email'])): ?>
                        <div class="field-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($errors['email']); ?></div>
                    <?php endif; ?>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Téléphone <span class="text-danger">*</span></label>
                    <input type="text" name="telephone" class="form-control<?php echo isset($errors['telephone']) ? ' error' : ''; ?>"
                           value="<?= htmlspecialchars($v['telephone'] ?? $user['telephone'] ?? '') ?>">
                    <?php if(isset($errors['telephone'])): ?>
                        <div class="field-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($errors['telephone']); ?></div>
                    <?php endif; ?>
                </div>
            </div>
                           value="<?= htmlspecialchars($v['telephone'] ?? $user['telephone'] ?? '') ?>">
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
                    <input type="password" name="password" class="form-control" placeholder="Min. 8 car., 1 maj., 1 chiffre">
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
    document.getElementById('roleSelect').addEventListener('change', function() {
        const role = this.value;
        document.getElementById('patientFields').style.display = role === 'patient' ? 'block' : 'none';
        document.getElementById('medecinFields').style.display = role === 'medecin' ? 'block' : 'none';
    });
</script>
</body>
</html>// update
