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
    <script src="https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js?v=<?php echo time(); ?>"></script>
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{background:linear-gradient(135deg,#f0f4f8 0%,#e8f2f8 100%);font-family:'Segoe UI','Roboto',sans-serif;min-height:100vh}
        .sidebar{position:fixed;top:0;left:0;width:260px;height:100%;background:linear-gradient(180deg,#0f2b3d 0%,#132840 100%);color:#fff;z-index:100;overflow-y:auto;box-shadow:2px 0 8px rgba(0,0,0,.1)}
        .sidebar-header{padding:24px 20px;text-align:center;border-bottom:1px solid rgba(255,255,255,.1);background:rgba(0,0,0,.15)}
        .sidebar-header .logo{font-size:22px;font-weight:800;color:#fff;letter-spacing:.5px}
        .sidebar-header small{color:rgba(255,255,255,.6);font-size:11px;display:block;margin-top:6px}
        .sidebar-menu a{display:flex;align-items:center;gap:12px;padding:14px 20px;color:rgba(255,255,255,.7);text-decoration:none;font-size:14px;font-weight:500;transition:all .3s;border-left:3px solid transparent}
        .sidebar-menu a:hover{background:rgba(255,255,255,.08);color:#fff;border-left-color:#4CAF50;padding-left:22px}
        .sidebar-menu a.active{background:rgba(76,175,80,.2);color:#fff;border-left-color:#4CAF50;padding-left:22px}
        .sidebar-menu a i{width:20px;font-size:1.1rem}
        .main{margin-left:260px;padding:32px}
        .topbar{background:#fff;border-radius:12px;padding:18px 26px;margin-bottom:32px;display:flex;justify-content:space-between;align-items:center;box-shadow:0 4px 12px rgba(0,0,0,.08);border-left:4px solid #4CAF50}
        .topbar-title{font-size:18px;font-weight:700;color:#0f2b3d;display:flex;align-items:center;gap:12px}
        .topbar-title i{color:#4CAF50}
        .admin-badge{display:flex;align-items:center;gap:12px;font-size:13px;font-weight:600;color:#0f2b3d}
        .avatar{width:42px;height:42px;border-radius:50%;background:linear-gradient(135deg,#2A7FAA,#4CAF50);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;box-shadow:0 2px 6px rgba(42,127,170,.2)}
        .card-box{background:#fff;border-radius:12px;padding:28px;box-shadow:0 4px 12px rgba(0,0,0,.08);margin-bottom:24px}
        .form-label{font-weight:700;font-size:14px;color:#0f2b3d;margin-bottom:10px;text-transform:uppercase;letter-spacing:.5px}
        .form-label .text-danger{color:#dc3545;margin-left:4px}
        .form-control,.form-select{border-radius:8px;border:2px solid #e0e6ed;padding:12px 14px;font-size:14px;font-family:inherit;transition:all .3s}
        .form-control:focus,.form-select:focus{border-color:#4CAF50;box-shadow:0 0 0 4px rgba(76,175,80,.1);outline:none}
        .form-control-plaintext{font-size:14px;color:#0f2b3d;padding-top:12px;font-weight:600}
        .mb-3{margin-bottom:22px}
        .form-article{display:flex;flex-direction:column}
        .row{display:grid;grid-template-columns:2fr 1fr;gap:28px}
        .col-md-8,.col-md-4{width:auto}
        .form-actions{display:flex;gap:12px;justify-content:flex-end;margin-top:32px;padding-top:24px;border-top:2px solid #e0e6ed}
        .btn-primary{background:linear-gradient(135deg,#2A7FAA,#4CAF50);border:none;border-radius:8px;padding:12px 28px;font-weight:600;font-size:14px;color:#fff;cursor:pointer;transition:all .3s;display:flex;align-items:center;gap:8px;box-shadow:0 4px 8px rgba(42,127,170,.2)}
        .btn-primary:hover{transform:translateY(-2px);box-shadow:0 6px 12px rgba(42,127,170,.3)}
        .btn-primary i{font-size:1.1rem}
        .btn-secondary{background:#f0f4f8;color:#0f2b3d;border:2px solid #e0e6ed;border-radius:8px;padding:12px 28px;font-weight:600;font-size:14px;cursor:pointer;transition:all .3s;display:flex;align-items:center;gap:8px}
        .btn-secondary:hover{background:#e0e6ed;border-color:#d0d8e0}
        .btn-secondary i{font-size:1.1rem}
        .alert{padding:14px 18px;border-radius:8px;margin-bottom:20px;border-left:4px solid;display:flex;align-items:center;gap:10px;animation:slideIn .3s ease}
        .alert i{font-size:1.2rem;flex-shrink:0}
        .alert-success{background:#e8f5e9;color:#2e7d32;border-left-color:#4CAF50}
        .alert-danger{background:#ffebee;color:#c62828;border-left-color:#dc3545}
        .field-error{font-size:12px;margin-top:8px;color:#c62828;font-weight:600;display:flex;align-items:center;gap:6px}
        .form-control.error,.form-select.error{border-color:#dc3545 !important;background-color:#fff5f5}
        textarea.form-control{resize:vertical;min-height:60px;font-family:'Fira Code','Courier New',monospace}
        .form-text{color:#6c757d;font-size:13px;margin-top:8px;display:block}
        @keyframes slideIn{from{opacity:0;transform:translateY(-10px)}to{opacity:1;transform:translateY(0)}}
        @media(max-width:1200px){.row{grid-template-columns:1fr}.main{padding:24px}}
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
                        <!-- Titre -->
                        <div class="mb-3">
                            <label class="form-label">
                                📝 Titre <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control<?php echo isset($errors['titre']) ? ' error' : ''; ?>" name="titre" 
                                   placeholder="Entrez le titre de votre article..."
                                   value="<?php echo htmlspecialchars($article['titre'] ?? ''); ?>" required>
                            <?php if(isset($errors['titre'])): ?>
                                <div class="field-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($errors['titre']); ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Contenu avec TinyMCE -->
                        <div class="mb-3">
                            <label class="form-label">
                                🖋️ Contenu <span class="text-danger">*</span>
                            </label>
                            <textarea id="editor" class="form-control<?php echo isset($errors['contenu']) ? ' error' : ''; ?>" name="contenu" required><?php echo htmlspecialchars($article['contenu'] ?? ''); ?></textarea>
                            <?php if(isset($errors['contenu'])): ?>
                                <div class="field-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($errors['contenu']); ?></div>
                            <?php endif; ?>
                            <small class="form-text">
                                💡 Éditeur riche : texte, images 🖼️, formatage, listes, tableaux, et bien plus...
                            </small>
                            <?php if(isset($errors['enregistrement'])): ?>
                                <div class="field-error mt-3"><i class="fas fa-exclamation-circle"></i> <?php echo $errors['enregistrement']; ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <!-- Catégorie -->
                        <div class="mb-3">
                            <label class="form-label">
                                🏷️ Catégorie
                            </label>
                            <input type="text" class="form-control" name="categorie"
                                   placeholder="Ex: Santé, Médecine, etc..."
                                   value="<?php echo htmlspecialchars($article['categorie'] ?? ''); ?>">
                        </div>
                        
                        <!-- Statut -->
                        <div class="mb-3">
                            <label class="form-label">
                                ⚙️ Statut
                            </label>
                            <select class="form-control" name="status">
                                <option value="brouillon" <?php echo (($article['status'] ?? 'brouillon') === 'brouillon') ? 'selected' : ''; ?>>📝 Brouillon</option>
                                <option value="publié" <?php echo (($article['status'] ?? 'brouillon') === 'publié') ? 'selected' : ''; ?>>🚀 Publié</option>
                                <option value="archive" <?php echo (($article['status'] ?? 'brouillon') === 'archive') ? 'selected' : ''; ?>>📦 Archive</option>
                            </select>
                        </div>
                        
                        <!-- Auteur -->
                        <div class="mb-3">
                            <label class="form-label">
                                👤 Auteur
                            </label>
                            <div class="form-control-plaintext" style="background:#f8f9fa;padding:12px 14px;border-radius:8px;border:2px solid #e0e6ed">
                                ✅ <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="form-actions">
                    <a href="index.php?page=articles_admin" class="btn btn-secondary">
                        <i class="fas fa-times"></i> ❌ Annuler
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> 🚀 <?php echo $isEdit ? 'Mettre à jour' : 'Publier'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Attendre que TinyMCE soit chargé
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof tinymce === 'undefined') {
                console.error('TinyMCE not loaded');
                return;
            }

            tinymce.init({
                selector: '#editor',
                height: 500,
                width: '100%',
                language: 'fr_FR',
                theme: 'silver',
                
                // Toolbar avec support emojis et images
                toolbar: 'undo redo | bold italic underline strikethrough | forecolor backcolor | link image media | alignleft aligncenter alignright alignjustify | bullist numlist | table | emoticons | blockquote code | removeformat | fullscreen',
                
                // Plugins nécessaires
                plugins: 'link image media table lists fullscreen emoticons code paste',
                
                // Menu bar
                menubar: 'file edit view insert format tools table',
                
                // Configuration de base
                relative_urls: false,
                remove_script_host: false,
                convert_urls: false,
                paste_as_text: false,
                
                // Configuration des images
                image_advtab: true,
                image_title: true,
                automatic_uploads: true,
                file_picker_types: 'image',
                
                // Configuration des emojis
                emoticons_database: 'emojis',
                
                // Nettoyage
                nonbreaking_force_tab: true,
                entity_encoding: 'raw',
                valid_elements: '+*[*]',
                
                // Formats personnalisés
                style_formats: [
                    { title: 'Titre 1', format: 'h1' },
                    { title: 'Titre 2', format: 'h2' },
                    { title: 'Titre 3', format: 'h3' },
                    { title: 'Titre 4', format: 'h4' },
                    { title: 'Paragraphe', format: 'p' },
                    { title: 'Blockquote', format: 'blockquote' },
                    { title: 'Code', format: 'code' }
                ],
                
                // Avant soumettre le formulaire, sauvegarder le contenu
                setup: function(editor) {
                    editor.on('change', function() {
                        tinymce.triggerSave();
                    });
                },
                
                // Callback après initialisation
                init_instance_callback: function(editor) {
                    console.log('TinyMCE initialized successfully');
                }
            });
        });
    </script>
</body>
</html>
