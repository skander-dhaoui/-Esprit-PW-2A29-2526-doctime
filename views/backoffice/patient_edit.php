<?php
// views/backoffice/patient_edit.php
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../../index.php?page=login');
    exit;
}
$page_title = 'Modifier un patient';
$current_page = 'patients';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?= $page_title ?> - Valorys</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #f0f2f5; font-family: 'Segoe UI', sans-serif; display: flex; min-height: 100vh; }
        .sidebar { width: 260px; min-height: 100vh; background: #1a2035; color: white; position: fixed; top: 0; left: 0; }
        .sidebar-brand { padding: 25px 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.08); }
        .brand-icon { width: 55px; height: 55px; background: rgba(255,255,255,0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 12px; font-size: 24px; color: #4CAF50; }
        .sidebar-brand h4 { font-size: 18px; font-weight: 700; margin: 0; color: white; }
        .sidebar-brand small { color: rgba(255,255,255,0.5); font-size: 11px; }
        .sidebar-nav { padding: 20px 0; flex: 1; }
        .sidebar-nav a { display: flex; align-items: center; gap: 12px; padding: 12px 22px; color: rgba(255,255,255,0.7); text-decoration: none; font-size: 14px; font-weight: 500; transition: all 0.2s; border-left: 3px solid transparent; }
        .sidebar-nav a:hover { background: rgba(255,255,255,0.07); color: white; }
        .sidebar-nav a.active { background: rgba(255,255,255,0.1); color: white; border-left-color: #4CAF50; }
        .sidebar-nav a i { width: 20px; text-align: center; font-size: 16px; }
        .nav-divider { height: 1px; background: rgba(255,255,255,0.07); margin: 10px 22px; }
        .main-content { margin-left: 260px; flex: 1; padding: 25px; min-height: 100vh; }
        .page-header { background: white; border-radius: 12px; padding: 18px 25px; margin-bottom: 25px; display: flex; align-items: center; justify-content: space-between; }
        .page-header h4 { font-size: 18px; font-weight: 700; color: #1a2035; margin: 0; }
        .content-card { background: white; border-radius: 12px; padding: 25px; box-shadow: 0 1px 6px rgba(0,0,0,0.06); }
        .form-label { font-weight: 600; }
        .btn-submit { background: #4CAF50; color: white; border: none; padding: 10px 25px; border-radius: 8px; }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon"><i class="fas fa-stethoscope"></i></div>
        <h4>MediConnect</h4>
        <small>Back Office</small>
    </div>
    <nav class="sidebar-nav">
        <a href="index.php?page=dashboard"><i class="fas fa-th-large"></i> Tableau de bord</a>
        <a href="index.php?page=users"><i class="fas fa-users"></i> Utilisateurs</a>
        <a href="index.php?page=medecins_admin"><i class="fas fa-user-md"></i> Médecins</a>
        <a href="index.php?page=patients" class="active"><i class="fas fa-user-injured"></i> Patients</a>
        <div class="nav-divider"></div>
        <a href="index.php?page=logout"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
    </nav>
</div>

<div class="main-content">
    <div class="page-header">
        <h4><i class="fas fa-user-edit"></i> Modifier le patient</h4>
        <a href="index.php?page=patients" class="btn btn-secondary">Retour</a>
    </div>

    <div class="content-card">
        <form method="POST" action="index.php?page=patients&action=edit&id=<?= $patient['id'] ?>">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Nom *</label>
                    <input type="text" name="nom" class="form-control" value="<?= htmlspecialchars($patient['nom']) ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Prénom *</label>
                    <input type="text" name="prenom" class="form-control" value="<?= htmlspecialchars($patient['prenom']) ?>" required>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Email *</label>
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($patient['email']) ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Téléphone</label>
                    <input type="tel" name="telephone" class="form-control" value="<?= htmlspecialchars($patient['telephone'] ?? '') ?>">
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Nouveau mot de passe</label>
                    <input type="password" name="password" class="form-control" placeholder="Laisser vide pour ne pas changer">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Groupe sanguin</label>
                    <select name="groupe_sanguin" class="form-control">
                        <option value="">Non renseigné</option>
                        <option value="A+" <?= ($patient['groupe_sanguin'] ?? '') === 'A+' ? 'selected' : '' ?>>A+</option>
                        <option value="A-" <?= ($patient['groupe_sanguin'] ?? '') === 'A-' ? 'selected' : '' ?>>A-</option>
                        <option value="B+" <?= ($patient['groupe_sanguin'] ?? '') === 'B+' ? 'selected' : '' ?>>B+</option>
                        <option value="B-" <?= ($patient['groupe_sanguin'] ?? '') === 'B-' ? 'selected' : '' ?>>B-</option>
                        <option value="AB+" <?= ($patient['groupe_sanguin'] ?? '') === 'AB+' ? 'selected' : '' ?>>AB+</option>
                        <option value="AB-" <?= ($patient['groupe_sanguin'] ?? '') === 'AB-' ? 'selected' : '' ?>>AB-</option>
                        <option value="O+" <?= ($patient['groupe_sanguin'] ?? '') === 'O+' ? 'selected' : '' ?>>O+</option>
                        <option value="O-" <?= ($patient['groupe_sanguin'] ?? '') === 'O-' ? 'selected' : '' ?>>O-</option>
                    </select>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Statut</label>
                    <select name="statut" class="form-control">
                        <option value="actif" <?= ($patient['statut'] ?? '') === 'actif' ? 'selected' : '' ?>>Actif</option>
                        <option value="inactif" <?= ($patient['statut'] ?? '') === 'inactif' ? 'selected' : '' ?>>Inactif</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Adresse</label>
                    <textarea name="adresse" class="form-control" rows="2"><?= htmlspecialchars($patient['adresse'] ?? '') ?></textarea>
                </div>
            </div>
            <hr>
            <button type="submit" class="btn-submit"><i class="fas fa-save"></i> Enregistrer</button>
            <a href="index.php?page=patients" class="btn btn-secondary">Annuler</a>
        </form>
    </div>
</div>
</body>
</html>