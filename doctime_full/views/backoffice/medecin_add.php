<?php
// views/backoffice/medecin_add.php
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: index.php?page=login'); exit;
}
$current_page = 'medecins_admin';
$old   = $_SESSION['old']   ?? [];
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['old'], $_SESSION['flash']);

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ajouter un médecin - MediConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{background:#f0f2f5;font-family:'Segoe UI',sans-serif;display:flex;min-height:100vh}
        .main-content{margin-left:260px;flex:1;padding:25px}
        .page-header{background:white;border-radius:12px;padding:18px 25px;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;box-shadow:0 1px 6px rgba(0,0,0,.06)}
        .page-header h4{font-size:18px;font-weight:700;color:#1a2035;margin:0;display:flex;align-items:center;gap:10px}
        .page-header h4 i{color:#0fa99b}
        .content-card{background:white;border-radius:12px;padding:28px;box-shadow:0 1px 6px rgba(0,0,0,.06)}
        .section-title{font-size:13px;font-weight:700;color:#1a2035;text-transform:uppercase;letter-spacing:.5px;border-bottom:2px solid #e2e8f0;padding-bottom:8px;margin:24px 0 18px;display:flex;align-items:center;gap:8px}
        .section-title:first-child{margin-top:0}
        .section-title i{color:#0fa99b}
        .form-label{font-weight:600;font-size:13px;color:#334155;margin-bottom:6px}
        .form-control,.form-select{border-radius:8px;padding:10px 14px;border:1.5px solid #e2e8f0;font-size:14px;transition:all .2s}
        .form-control:focus,.form-select:focus{border-color:#0fa99b;box-shadow:0 0 0 3px rgba(15,169,155,.1);outline:none}
        .form-control.is-invalid,.form-select.is-invalid{border-color:#ef4444;box-shadow:0 0 0 3px rgba(239,68,68,.1)}
        .invalid-feedback{color:#ef4444;font-size:12px;margin-top:4px;font-weight:500}
        .required{color:#ef4444}
        .btn-save{background:linear-gradient(135deg,#0fa99b,#0d8a7d);color:white;border:none;border-radius:8px;padding:11px 28px;font-weight:600;font-size:14px;display:inline-flex;align-items:center;gap:8px;cursor:pointer;transition:all .2s}
        .btn-save:hover{opacity:.9;transform:translateY(-1px);color:white}
        .btn-back{background:#e2e8f0;color:#334155;border:none;border-radius:8px;padding:10px 20px;font-weight:600;font-size:13px;text-decoration:none;display:inline-flex;align-items:center;gap:6px;transition:all .2s}
        .btn-back:hover{background:#cbd5e1;color:#1a2035}
        .flash-box{border-radius:10px;padding:13px 16px;margin-bottom:20px;font-size:14px;display:flex;align-items:center;gap:10px}
        .flash-error{background:#fdecea;color:#c62828;border:1px solid #f9a9a3}
        .flash-success{background:#e8f5e9;color:#2e7d32;border:1px solid #a5d6a7}
    </style>
</head>
<body>

<?php include __DIR__ . '/sidebar.php'; ?>

<div class="main-content">
    <div class="page-header">
        <h4><i class="fas fa-user-md"></i> Ajouter un médecin</h4>
        <a href="index.php?page=medecins_admin" class="btn-back">
            <i class="fas fa-arrow-left"></i> Retour à la liste
        </a>
    </div>

    <?php if ($flash): ?>
        <div class="flash-box flash-<?= $flash['type'] === 'success' ? 'success' : 'error' ?>">
            <i class="fas fa-<?= $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
            <?= htmlspecialchars($flash['message']) ?>
        </div>
    <?php endif; ?>

    <div class="content-card">
        <form method="POST" action="index.php?page=medecins_admin&action=add" novalidate id="medecinAddForm">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

            <div class="section-title"><i class="fas fa-user-circle"></i> Informations personnelles</div>
            <div class="row mb-3">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Nom <span class="required">*</span></label>
                    <input type="text" id="nom" name="nom" class="form-control"
                           value="<?= htmlspecialchars($old['nom'] ?? '') ?>" placeholder="Nom de famille">
                    <div class="invalid-feedback" id="nom-error"></div>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Prénom <span class="required">*</span></label>
                    <input type="text" id="prenom" name="prenom" class="form-control"
                           value="<?= htmlspecialchars($old['prenom'] ?? '') ?>" placeholder="Prénom">
                    <div class="invalid-feedback" id="prenom-error"></div>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Email <span class="required">*</span></label>
                    <input type="email" id="email" name="email" class="form-control"
                           value="<?= htmlspecialchars($old['email'] ?? '') ?>" placeholder="email@exemple.com">
                    <div class="invalid-feedback" id="email-error"></div>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Téléphone</label>
                    <input type="text" id="telephone" name="telephone" class="form-control"
                           value="<?= htmlspecialchars($old['telephone'] ?? '') ?>" placeholder="+216 XX XXX XXX">
                    <div class="invalid-feedback" id="telephone-error"></div>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Mot de passe <span class="required">*</span></label>
                    <input type="password" id="password" name="password" class="form-control"
                           placeholder="Min. 6 caractères">
                    <div class="invalid-feedback" id="password-error"></div>
                </div>
            </div>

            <div class="section-title"><i class="fas fa-stethoscope"></i> Informations professionnelles</div>
            <div class="row mb-3">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Spécialité <span class="required">*</span></label>
                    <select id="specialite" name="specialite" class="form-select">
                        <option value="">-- Sélectionner --</option>
                        <?php
                        $specs = ['Cardiologie','Dermatologie','Endocrinologie','Gastro-entérologie',
                                  'Gynécologie','Neurologie','Ophtalmologie','ORL','Pédiatrie',
                                  'Psychiatrie','Radiologie','Rhumatologie','Urologie','Médecine générale'];
                        foreach ($specs as $s):
                        ?>
                            <option value="<?= $s ?>" <?= ($old['specialite'] ?? '') === $s ? 'selected' : '' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="invalid-feedback" id="specialite-error"></div>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Numéro d'ordre</label>
                    <input type="text" id="numero_ordre" name="numero_ordre" class="form-control"
                           value="<?= htmlspecialchars($old['numero_ordre'] ?? '') ?>" placeholder="N° ordre médical">
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Tarif consultation (DT)</label>
                    <input type="text" id="consultation_prix" name="consultation_prix" class="form-control"
                           step="1" min="0" value="<?= htmlspecialchars($old['consultation_prix'] ?? '50') ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Années d'expérience</label>
                    <input type="text" id="annee_experience" name="annee_experience" class="form-control"
                           min="0" value="<?= htmlspecialchars($old['annee_experience'] ?? '0') ?>">
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Adresse du cabinet</label>
                <textarea name="cabinet_adresse" class="form-control" rows="2"
                          placeholder="Adresse complète du cabinet"><?= htmlspecialchars($old['cabinet_adresse'] ?? '') ?></textarea>
            </div>

            <div class="d-flex gap-3 mt-4">
                <button type="submit" class="btn-save" id="submitBtn">
                    <i class="fas fa-save"></i> Enregistrer le médecin
                </button>
                <a href="index.php?page=medecins_admin" class="btn-back">
                    <i class="fas fa-times"></i> Annuler
                </a>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('medecinAddForm').addEventListener('submit', function(e) {
    let valid = true;
    document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
    document.querySelectorAll('.invalid-feedback').forEach(el => { el.textContent = ''; el.style.display = 'none'; });

    function err(id, errId, msg) {
        const inp = document.getElementById(id);
        const div = document.getElementById(errId);
        if (inp) inp.classList.add('is-invalid');
        if (div) { div.textContent = msg; div.style.display = 'block'; }
        valid = false;
    }

    const nom = document.getElementById('nom').value.trim();
    if (!nom || nom.length < 2) err('nom', 'nom-error', 'Le nom est obligatoire (min. 2 caractères).');

    const prenom = document.getElementById('prenom').value.trim();
    if (!prenom || prenom.length < 2) err('prenom', 'prenom-error', 'Le prénom est obligatoire (min. 2 caractères).');

    const email = document.getElementById('email').value.trim();
    if (!email) err('email', 'email-error', 'L\'email est obligatoire.');
    else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) err('email', 'email-error', 'Format d\'email invalide.');

    const password = document.getElementById('password').value;
    if (!password || password.length < 6) err('password', 'password-error', 'Le mot de passe doit contenir au moins 6 caractères.');

    const specialite = document.getElementById('specialite').value;
    if (!specialite) err('specialite', 'specialite-error', 'La spécialité est obligatoire.');

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