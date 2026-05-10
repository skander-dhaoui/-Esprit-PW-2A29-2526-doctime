<?php
// views/backoffice/patient_add.php
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../../index.php?page=login');
    exit;
}
$page_title = 'Ajouter un patient';
$current_page = 'patients';
$errors = isset($errors) ? $errors : [];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?= $page_title ?> - Valorys</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <?php require_once __DIR__ . '/../partials/backoffice_shell_styles.php'; ?>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body.bo-shell-body { background: #f0f2f5; font-family: 'Segoe UI', sans-serif; min-height: 100vh; }
        .page-header { background: white; border-radius: 12px; padding: 18px 25px; margin-bottom: 25px; display: flex; align-items: center; justify-content: space-between; }
        .page-header h4 { font-size: 18px; font-weight: 700; color: #1a2035; margin: 0; }
        .content-card { background: white; border-radius: 12px; padding: 25px; box-shadow: 0 1px 6px rgba(0,0,0,0.06); }
        .form-label { font-weight: 600; }
        .btn-submit { background: #4CAF50; color: white; border: none; padding: 10px 25px; border-radius: 8px; }
        .btn-submit:hover { background: #2A7FAA; }
        .field-error { font-size: 12px; margin-top: 6px; color: #c62828; font-weight: 500; }
        .field-error i { margin-right: 5px; }
        .form-control.error { border-color: #dc3545 !important; }
    </style>
</head>
<body class="bo-shell-body">
<?php require_once __DIR__ . '/sidebar.php'; ?>

<div class="main-content">
    <div class="page-header">
        <h4><i class="fas fa-user-plus"></i> Ajouter un patient</h4>
        <a href="index.php?page=patients" class="btn btn-secondary">Retour</a>
    </div>

    <div class="content-card">
        <form method="POST" action="index.php?page=patients&action=add">
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
                    <?php if(isset($errors['password'])): ?>
                        <div class="field-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($errors['password']); ?></div>
                    <?php endif; ?>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Groupe sanguin</label>
                    <select name="groupe_sanguin" class="form-control<?php echo isset($errors['groupe_sanguin']) ? ' error' : ''; ?>">
                        <option value="">Non renseigné</option>
                        <option value="A+" <?php echo ($_POST['groupe_sanguin'] ?? '') === 'A+' ? 'selected' : ''; ?>>A+</option><option value="A-" <?php echo ($_POST['groupe_sanguin'] ?? '') === 'A-' ? 'selected' : ''; ?>>A-</option>
                        <option value="B+" <?php echo ($_POST['groupe_sanguin'] ?? '') === 'B+' ? 'selected' : ''; ?>>B+</option><option value="B-" <?php echo ($_POST['groupe_sanguin'] ?? '') === 'B-' ? 'selected' : ''; ?>>B-</option>
                        <option value="AB+" <?php echo ($_POST['groupe_sanguin'] ?? '') === 'AB+' ? 'selected' : ''; ?>>AB+</option><option value="AB-" <?php echo ($_POST['groupe_sanguin'] ?? '') === 'AB-' ? 'selected' : ''; ?>>AB-</option>
                        <option value="O+" <?php echo ($_POST['groupe_sanguin'] ?? '') === 'O+' ? 'selected' : ''; ?>>O+</option><option value="O-" <?php echo ($_POST['groupe_sanguin'] ?? '') === 'O-' ? 'selected' : ''; ?>>O-</option>
                    </select>
                    <?php if(isset($errors['groupe_sanguin'])): ?>
                        <div class="field-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($errors['groupe_sanguin']); ?></div>
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
            <hr>
            <button type="submit" class="btn-submit"><i class="fas fa-save"></i> Enregistrer</button>
            <a href="index.php?page=patients" class="btn btn-secondary">Annuler</a>
        </form>
    </div>
</div>
</body>
</html>
