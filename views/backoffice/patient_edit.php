<?php
// views/backoffice/patient_edit.php
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: index.php?page=login'); exit;
}
$current_page = 'patients';
$flash = $_SESSION['flash'] ?? null;
$old   = $_SESSION['old']   ?? [];
unset($_SESSION['flash'], $_SESSION['old']);

if (!isset($patient) || !is_array($patient)) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Patient introuvable.'];
    header('Location: index.php?page=patients'); exit;
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier un patient - MediConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{background:#f0f2f5;font-family:'Segoe UI',sans-serif;display:flex;min-height:100vh}
        .sidebar{width:260px;min-height:100vh;background:#1a2035;color:white;position:fixed;top:0;left:0;z-index:100}
        .sidebar-brand{padding:25px 20px;text-align:center;border-bottom:1px solid rgba(255,255,255,.08)}
        .brand-icon{width:55px;height:55px;background:rgba(255,255,255,.1);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;font-size:24px;color:#4CAF50}
        .sidebar-brand h4{font-size:18px;font-weight:700;margin:0;color:white}
        .sidebar-brand small{color:rgba(255,255,255,.5);font-size:11px}
        .sidebar-nav{padding:20px 0}
        .sidebar-nav a{display:flex;align-items:center;gap:12px;padding:12px 22px;color:rgba(255,255,255,.7);text-decoration:none;font-size:14px;font-weight:500;transition:all .2s;border-left:3px solid transparent}
        .sidebar-nav a:hover{background:rgba(255,255,255,.07);color:white}
        .sidebar-nav a.active{background:rgba(255,255,255,.1);color:white;border-left-color:#0fa99b}
        .sidebar-nav a i{width:20px;text-align:center}
        .nav-divider{height:1px;background:rgba(255,255,255,.07);margin:10px 22px}
        .main-content{margin-left:260px;flex:1;padding:25px}
        .page-header{background:white;border-radius:12px;padding:18px 25px;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;box-shadow:0 1px 6px rgba(0,0,0,.06)}
        .page-header h4{font-size:18px;font-weight:700;color:#1a2035;margin:0;display:flex;align-items:center;gap:10px}
        .page-header h4 i{color:#0fa99b}
        .content-card{background:white;border-radius:12px;padding:28px;box-shadow:0 1px 6px rgba(0,0,0,.06)}
        .section-title{font-size:13px;font-weight:700;color:#1a2035;text-transform:uppercase;letter-spacing:.5px;border-bottom:2px solid #e2e8f0;padding-bottom:8px;margin:24px 0 18px;display:flex;align-items:center;gap:8px}
        .section-title i{color:#0fa99b}
        .form-label{font-weight:600;font-size:13px;color:#334155;margin-bottom:6px}
        .form-control,.form-select{border-radius:8px;padding:10px 14px;border:1.5px solid #e2e8f0;font-size:14px;transition:all .2s}
        .form-control:focus,.form-select:focus{border-color:#0fa99b;box-shadow:0 0 0 3px rgba(15,169,155,.1);outline:none}
        .form-control.is-invalid,.form-select.is-invalid{border-color:#ef4444;box-shadow:0 0 0 3px rgba(239,68,68,.1)}
        .invalid-feedback{color:#ef4444;font-size:12px;margin-top:4px;font-weight:500;display:none}
        .required{color:#ef4444}
        .btn-save{background:#0fa99b;color:white;border:none;padding:11px 28px;border-radius:8px;font-weight:600;font-size:14px;display:inline-flex;align-items:center;gap:8px;cursor:pointer;transition:all .2s}
        .btn-save:hover{background:#0d8a7d;color:white;transform:translateY(-1px)}
        .flash-box{border-radius:8px;padding:13px 16px;margin-bottom:20px;font-size:14px;display:flex;align-items:center;gap:10px}
        .flash-error{background:#fdecea;color:#c62828;border:1px solid #f9a9a3}
        .flash-success{background:#e8f5e9;color:#2e7d32;border:1px solid #a5d6a7}
        .patient-banner{background:linear-gradient(135deg,#1a2035,#0fa99b);border-radius:12px;padding:20px 24px;margin-bottom:20px;color:white;display:flex;align-items:center;gap:16px}
        .patient-avatar{width:54px;height:54px;background:rgba(255,255,255,.15);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:22px;font-weight:700;flex-shrink:0}
        .patient-banner h5{margin:0;font-size:17px;font-weight:700}
        .patient-banner small{opacity:.8;font-size:13px}
    </style>
</head>
<body>
<div class="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon"><i class="fas fa-stethoscope"></i></div>
        <h4>MediConnect</h4><small>Back Office</small>
    </div>
    <nav class="sidebar-nav">
        <a href="index.php?page=dashboard"><i class="fas fa-th-large"></i> Tableau de bord</a>
        <a href="index.php?page=users"><i class="fas fa-users"></i> Utilisateurs</a>
        <a href="index.php?page=medecins_admin"><i class="fas fa-user-md"></i> Médecins</a>
        <a href="index.php?page=patients" class="active"><i class="fas fa-user-injured"></i> Patients</a>
        <a href="index.php?page=rendez_vous_admin"><i class="fas fa-calendar-check"></i> Rendez-vous</a>
        <a href="index.php?page=produits_admin"><i class="fas fa-box"></i> Produits</a>
        <div class="nav-divider"></div>
        <a href="index.php?page=logout"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
    </nav>
</div>

<div class="main-content">
    <div class="page-header">
        <h4><i class="fas fa-user-edit"></i> Modifier le patient</h4>
        <a href="index.php?page=patients" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i> Retour
        </a>
    </div>

    <div class="patient-banner">
        <div class="patient-avatar"><?= strtoupper(substr($patient['prenom'] ?? 'P', 0, 1)) ?></div>
        <div>
            <h5><?= htmlspecialchars(($patient['prenom'] ?? '') . ' ' . ($patient['nom'] ?? '')) ?></h5>
            <small><?= htmlspecialchars($patient['email'] ?? '') ?> &bull; #<?= $patient['id'] ?></small>
        </div>
    </div>

    <?php if ($flash): ?>
        <div class="flash-box flash-<?= $flash['type'] ?>">
            <i class="fas fa-<?= $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
            <?= htmlspecialchars($flash['message']) ?>
        </div>
    <?php endif; ?>

    <div class="content-card">
        <form method="POST" action="index.php?page=patients&action=edit&id=<?= $patient['id'] ?>" novalidate id="patientEditForm">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

            <div class="section-title"><i class="fas fa-user-circle"></i> Informations personnelles</div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Nom <span class="required">*</span></label>
                    <input type="text" id="nom" name="nom" class="form-control"
                           value="<?= htmlspecialchars($old['nom'] ?? $patient['nom'] ?? '') ?>">
                    <div class="invalid-feedback" id="nom-error"></div>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Prénom <span class="required">*</span></label>
                    <input type="text" id="prenom" name="prenom" class="form-control"
                           value="<?= htmlspecialchars($old['prenom'] ?? $patient['prenom'] ?? '') ?>">
                    <div class="invalid-feedback" id="prenom-error"></div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Email <span class="required">*</span></label>
                    <input type="text" id="email" name="email" class="form-control"
                           value="<?= htmlspecialchars($old['email'] ?? $patient['email'] ?? '') ?>">
                    <div class="invalid-feedback" id="email-error"></div>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Téléphone</label>
                    <input type="tel" id="telephone" name="telephone" class="form-control"
                           value="<?= htmlspecialchars($old['telephone'] ?? $patient['telephone'] ?? '') ?>">
                    <div class="invalid-feedback" id="telephone-error"></div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Nouveau mot de passe</label>
                    <input type="password" id="password" name="password" class="form-control"
                           placeholder="Laisser vide pour ne pas changer">
                    <small class="text-muted" style="font-size:11px">Minimum 6 caractères si renseigné</small>
                    <div class="invalid-feedback" id="password-error"></div>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Groupe sanguin</label>
                    <select id="groupe_sanguin" name="groupe_sanguin" class="form-select">
                        <option value="">Non renseigné</option>
                        <?php foreach (['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $g): ?>
                            <option value="<?= $g ?>"
                                <?= ($old['groupe_sanguin'] ?? $patient['groupe_sanguin'] ?? '') === $g ? 'selected' : '' ?>>
                                <?= $g ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Statut</label>
                    <select name="statut" class="form-select">
                        <?php $st = $old['statut'] ?? $patient['statut'] ?? 'actif'; ?>
                        <option value="actif"    <?= $st === 'actif'    ? 'selected' : '' ?>>Actif</option>
                        <option value="inactif"  <?= $st === 'inactif'  ? 'selected' : '' ?>>Inactif</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Adresse</label>
                    <textarea name="adresse" class="form-control" rows="2"><?= htmlspecialchars($old['adresse'] ?? $patient['adresse'] ?? '') ?></textarea>
                </div>
            </div>

            <hr class="my-4">
            <div class="d-flex gap-3 flex-wrap align-items-center">
                <button type="submit" class="btn-save" id="submitBtn">
                    <i class="fas fa-save"></i> Enregistrer les modifications
                </button>
                <a href="index.php?page=patients" class="btn btn-secondary">
                    <i class="fas fa-times me-1"></i> Annuler
                </a>
                <a href="index.php?page=patients&action=delete&id=<?= $patient['id'] ?>"
                   class="btn btn-danger ms-auto"
                   onclick="return confirm('Supprimer définitivement ce patient ?')">
                    <i class="fas fa-trash me-1"></i> Supprimer
                </a>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('patientEditForm').addEventListener('submit', function(e) {
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

    const pwd = document.getElementById('password').value;
    if (pwd && pwd.length < 6) err('password-error', 'password', 'Le mot de passe doit contenir au moins 6 caractères.');

    if (!valid) {
        e.preventDefault();
        document.querySelector('.is-invalid')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    } else {
        document.getElementById('submitBtn').innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enregistrement…';
        document.getElementById('submitBtn').disabled = true;
    }
});
</script>
</body>
</html>