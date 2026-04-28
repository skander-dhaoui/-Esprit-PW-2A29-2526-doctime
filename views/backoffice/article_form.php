<?php
require_once __DIR__ . '/../../models/Article.php';
require_once __DIR__ . '/sidebar.php';

$action = $_GET['action'] ?? 'create';
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$article = null;
$title = 'Créer un Article';
$isEdit = false;
$message = '';
$messageType = '';
$errors = [];

if($action === 'edit' && $id) {
    $articleModel = new Article();
    $article = $articleModel->getById($id);
    if(!$article) {
        $message = 'Article non trouvé.';
        $messageType = 'danger';
    } else {
        $title = 'Modifier l\'Article';
        $isEdit = true;
    }
}

// Handle form submission
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $articleModel = new Article();
    
    $titre = trim($_POST['titre'] ?? '');
    $contenu = trim($_POST['contenu'] ?? '');
    $categorie = trim($_POST['categorie'] ?? '');
    $status = $_POST['status'] ?? 'brouillon';
    
    if($titre === '') {
        $errors['titre'] = 'Le titre est obligatoire.';
    }
    if($contenu === '') {
        $errors['contenu'] = 'Le contenu est obligatoire.';
    }

    $article = $article ?? [];
    $article['titre'] = $titre;
    $article['contenu'] = $contenu;
    $article['categorie'] = $categorie;
    $article['status'] = $status;

    if(empty($errors)) {
        try {
            if($isEdit && $id) {
                // Update article
                $result = $articleModel->update($id, $titre, $contenu, $_SESSION['user_id']);
                if($result) {
                    $message = 'Article modifié avec succès !';
                    $messageType = 'success';
                    echo '<script>setTimeout(() => window.location.href = "index.php?page=articles_admin", 1500);</script>';
                }
            } else {
                // Create new article
                $newId = $articleModel->create([
                    'titre' => $titre,
                    'contenu' => $contenu,
                    'auteur_id' => $_SESSION['user_id'] ?? 1
                ]);
                
                if($newId > 0) {
                    $message = 'Article créé avec succès !';
                    $messageType = 'success';
                    echo '<script>setTimeout(() => window.location.href = "index.php?page=articles_admin", 1500);</script>';
                }
            }
        } catch(Exception $e) {
            $errors['enregistrement'] = 'Erreur : ' . htmlspecialchars($e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Valorys — Gestion Articles</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{background:#f0f4f8;font-family:'Segoe UI',sans-serif}
        .sidebar{position:fixed;top:0;left:0;width:260px;height:100%;background:#0f2b3d;color:#fff;z-index:100;overflow-y:auto}
        .sidebar-header{padding:22px 20px;text-align:center;border-bottom:1px solid rgba(255,255,255,.1)}
        .sidebar-header .logo{font-size:20px;font-weight:700;color:#fff}
        .sidebar-header small{color:rgba(255,255,255,.5);font-size:11px}
        .sidebar-menu a{display:flex;align-items:center;gap:10px;padding:12px 22px;color:rgba(255,255,255,.7);text-decoration:none;font-size:13px;font-weight:500;transition:.2s}
        .sidebar-menu a:hover,.sidebar-menu a.active{background:rgba(76,175,80,.15);color:#fff;border-left:3px solid #4CAF50}
        .sidebar-menu a i{width:20px;font-size:1rem}
        .main{margin-left:260px;padding:24px 28px}
        .topbar{background:#fff;border-radius:16px;padding:14px 22px;margin-bottom:24px;display:flex;justify-content:space-between;align-items:center;box-shadow:0 2px 8px rgba(0,0,0,.05)}
        .topbar-title{font-size:17px;font-weight:700;color:#0f2b3d}
        .admin-badge{display:flex;align-items:center;gap:10px;font-size:13px;font-weight:500}
        .avatar{width:38px;height:38px;border-radius:50%;background:linear-gradient(135deg,#2A7FAA,#4CAF50);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700}
        .card-box{background:#fff;border-radius:16px;padding:20px;box-shadow:0 2px 8px rgba(0,0,0,.05);margin-bottom:24px}
        .card-box-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:16px}
        .card-box-header h5{font-size:15px;font-weight:700;color:#0f2b3d}
        .form-label{font-weight:600;font-size:13px;color:#0f2b3d;margin-bottom:8px}
        .form-control{border-radius:8px;border:1px solid #e0e6ed;padding:10px 12px;font-size:13px}
        .form-control:focus{border-color:#2A7FAA;box-shadow:0 0 0 3px rgba(42,127,170,.1)}
        .form-control-plaintext{font-size:13px;color:#0f2b3d;padding-top:10px}
        .form-actions{display:flex;gap:10px;justify-content:flex-end;margin-top:24px;padding-top:20px;border-top:1px solid #e0e6ed}
        .btn-primary{background:linear-gradient(135deg,#2A7FAA,#4CAF50);border:none;border-radius:8px;padding:10px 20px;font-weight:600;transition:.2s}
        .btn-primary:hover{opacity:.9}
        .btn-secondary{background:#e9ecef;color:#0f2b3d;border:none;border-radius:8px;padding:10px 20px;font-weight:600;transition:.2s}
        .btn-secondary:hover{background:#dee2e6}
        .alert{padding:12px 16px;border-radius:8px;margin-bottom:16px;border-left:4px solid}
        .alert-success{background:#e8f5e9;color:#2e7d32;border-left-color:#2e7d32}
        .alert-danger{background:#ffebee;color:#c62828;border-left-color:#c62828}
        .field-error{font-size:12px;margin-top:6px;color:#c62828;font-weight:500}
        .field-error i{margin-right:5px}
        .form-control.error,.form-select.error{border-color:#dc3545!important}
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="logo">Valorys</div>
            <small>Plateforme Médicale</small>
        </div>
        <div class="sidebar-menu">
            <a href="index.php?page=dashboard"><i class="fas fa-chart-line"></i> Dashboard</a>
            <a href="index.php?page=articles_admin" class="active"><i class="fas fa-newspaper"></i> Articles</a>
            <a href="index.php?page=evenements_admin"><i class="fas fa-calendar"></i> Événements</a>
            <a href="index.php?page=medecins_admin"><i class="fas fa-stethoscope"></i> Médecins</a>
            <a href="index.php?page=patients_admin"><i class="fas fa-user-injured"></i> Patients</a>
            <a href="index.php?page=users_admin"><i class="fas fa-users"></i> Utilisateurs</a>
        </div>
    </div>
    
    <div class="main">
        <div class="topbar">
            <div class="topbar-title"><i class="fas fa-pen-to-square"></i> <?php echo $title; ?></div>
            <div class="admin-badge">
                <div class="avatar"><?php echo substr($_SESSION['user_name'] ?? 'A', 0, 1); ?></div>
                <div><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?></div>
            </div>
        </div>
        
        <div class="card-box">
            <?php if($message): ?>
                <div class="alert alert-<?php echo $messageType; ?>">
                    <i class="fas fa-<?php echo ($messageType === 'success') ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="form-article">
                <div class="row">
                    <div class="col-md-8">
                        <div class="mb-3">
                            <label class="form-label">Titre <span class="text-danger">*</span></label>
                            <input type="text" class="form-control<?php echo isset($errors['titre']) ? ' error' : ''; ?>" name="titre" 
                                   value="<?php echo htmlspecialchars($article['titre'] ?? ''); ?>" required>
                            <?php if(isset($errors['titre'])): ?>
                                <div class="field-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($errors['titre']); ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Contenu <span class="text-danger">*</span></label>
                            <textarea class="form-control<?php echo isset($errors['contenu']) ? ' error' : ''; ?>" name="contenu" rows="12" required
                                      style="font-family: monospace; font-size: 13px;"><?php echo htmlspecialchars($article['contenu'] ?? ''); ?></textarea>
                            <?php if(isset($errors['contenu'])): ?>
                                <div class="field-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($errors['contenu']); ?></div>
                            <?php endif; ?>
                            <small class="text-muted">Vous pouvez utiliser du HTML pour formater votre contenu.</small>
                            <?php if(isset($errors['enregistrement'])): ?>
                                <div class="field-error mt-2"><i class="fas fa-exclamation-circle"></i> <?php echo $errors['enregistrement']; ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Catégorie</label>
                            <input type="text" class="form-control" name="categorie"
                                   value="<?php echo htmlspecialchars($article['categorie'] ?? ''); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Statut</label>
                            <select class="form-control" name="status">
                                <option value="brouillon" <?php echo (($article['status'] ?? 'brouillon') === 'brouillon') ? 'selected' : ''; ?>>Brouillon</option>
                                <option value="publié" <?php echo (($article['status'] ?? 'brouillon') === 'publié') ? 'selected' : ''; ?>>Publié</option>
                                <option value="archive" <?php echo (($article['status'] ?? 'brouillon') === 'archive') ? 'selected' : ''; ?>>Archive</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Auteur</label>
                            <div class="form-control-plaintext">
                                <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="form-actions">
                    <a href="index.php?page=articles_admin" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Annuler
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> <?php echo $isEdit ? 'Mettre à jour' : 'Créer'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
// update
