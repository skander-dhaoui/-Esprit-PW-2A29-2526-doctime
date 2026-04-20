<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer un article - Blog Valorys</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js?v=<?php echo time(); ?>"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: linear-gradient(135deg, #f5f7fb 0%, #e8f2f8 100%); font-family: 'Segoe UI', 'Roboto', sans-serif; min-height: 100vh; }
        nav.navbar { background: linear-gradient(180deg, #1a2035 0%, #0f1620 100%); box-shadow: 0 4px 12px rgba(0,0,0,.15); }
        .blog-header { background: linear-gradient(135deg, #2A7FAA 0%, #4CAF50 100%); color: #fff; padding: 60px 0; text-align: center; margin-bottom: 40px; box-shadow: 0 8px 24px rgba(42,127,170,.2); }
        .blog-header h1 { font-size: 2.3rem; font-weight: 800; margin-bottom: 10px; }
        .blog-header p { opacity: .95; font-size: 1rem; }
        .container { max-width: 900px; }
        .card-box { background: #fff; border-radius: 16px; padding: 40px; box-shadow: 0 4px 16px rgba(0,0,0,.08); margin-bottom: 32px; }
        .form-label { font-weight: 700; font-size: 14px; color: #0f2b3d; margin-bottom: 10px; text-transform: uppercase; letter-spacing: .5px; }
        .form-control, .form-select { border-radius: 8px; border: 2px solid #e0e6ed; padding: 12px 14px; font-size: 14px; font-family: inherit; transition: all .2s; }
        .form-control:focus, .form-select:focus { border-color: #4CAF50; box-shadow: 0 0 0 4px rgba(76,175,80,.1); outline: none; }
        .mb-3 { margin-bottom: 22px; }
        .form-actions { display: flex; gap: 12px; justify-content: flex-end; margin-top: 32px; padding-top: 24px; border-top: 2px solid #e0e6ed; }
        .btn-primary { background: linear-gradient(135deg, #2A7FAA, #4CAF50); border: none; border-radius: 8px; padding: 12px 28px; font-weight: 600; font-size: 14px; color: #fff; cursor: pointer; transition: all .3s; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 8px rgba(42,127,170,.2); }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 12px rgba(42,127,170,.3); }
        .btn-secondary { background: #f0f4f8; color: #0f2b3d; border: 2px solid #e0e6ed; border-radius: 8px; padding: 12px 28px; font-weight: 600; font-size: 14px; cursor: pointer; transition: all .3s; display: inline-flex; align-items: center; gap: 8px; }
        .btn-secondary:hover { background: #e0e6ed; border-color: #d0d8e0; }
        .alert { padding: 14px 18px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid; display: flex; align-items: center; gap: 10px; }
        .alert-success { background: #e8f5e9; color: #2e7d32; border-left-color: #4CAF50; }
        .alert-danger { background: #ffebee; color: #c62828; border-left-color: #dc3545; }
        .field-error { font-size: 12px; margin-top: 8px; color: #c62828; font-weight: 600; display: flex; align-items: center; gap: 6px; }
        .form-control.error, .form-select.error { border-color: #dc3545 !important; background-color: #fff5f5; }
        textarea.form-control { resize: vertical; min-height: 60px; font-family: 'Fira Code', 'Courier New', monospace; }
        .form-text { color: #6c757d; font-size: 13px; margin-top: 8px; display: block; }
        footer { background: #1a2035; color: #fff; text-align: center; padding: 30px; margin-top: 50px; }
        .row { display: grid; grid-template-columns: 2fr 1fr; gap: 28px; }
        .col-md-8, .col-md-4 { width: auto; }
        @media(max-width:1200px) { .row { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php?page=accueil"><i class="fas fa-stethoscope me-2"></i>Valorys</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav"><span class="navbar-toggler-icon"></span></button>
        <div class="collapse navbar-collapse" id="nav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="index.php?page=accueil">Accueil</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php?page=medecins">Médecins</a></li>
                <li class="nav-item"><a class="nav-link active" href="index.php?page=blog_public">Blog</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php?page=contact">Contact</a></li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php?page=dashboard" style="color:#ffc107 !important;font-weight:bold;">
                            <i class="fas fa-cogs"></i> Backoffice
                        </a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item"><a class="nav-link" href="index.php?page=logout">Déconnexion</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="index.php?page=login">Connexion</a></li>
                    <li class="nav-item"><a class="nav-link" href="index.php?page=register">Inscription</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<div class="blog-header">
    <div class="container">
        <h1>✍️ Créer un nouvel article</h1>
        <p>Partagez vos connaissances et expériences avec la communauté</p>
    </div>
</div>

<div class="container mb-5">
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
                        <label class="form-label">📝 Titre <span class="text-danger">*</span></label>
                        <input type="text" class="form-control<?php echo isset($errors['titre']) ? ' error' : ''; ?>" name="titre" 
                               placeholder="Entrez le titre de votre article..."
                               value="<?php echo htmlspecialchars($article['titre'] ?? ''); ?>" required>
                        <?php if(isset($errors['titre'])): ?>
                            <div class="field-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($errors['titre']); ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Contenu avec TinyMCE -->
                    <div class="mb-3">
                        <label class="form-label">🖋️ Contenu <span class="text-danger">*</span></label>
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
                        <label class="form-label">🏷️ Catégorie</label>
                        <input type="text" class="form-control" name="categorie"
                               placeholder="Ex: Santé, Médecine, etc..."
                               value="<?php echo htmlspecialchars($article['categorie'] ?? ''); ?>">
                    </div>
                    
                    <!-- Tags -->
                    <div class="mb-3">
                        <label class="form-label">🏷️ Tags</label>
                        <input type="text" class="form-control" name="tags"
                               placeholder="Ex: santé, bien-être..."
                               value="<?php echo htmlspecialchars($article['tags'] ?? ''); ?>">
                    </div>
                    
                    <!-- Statut -->
                    <div class="mb-3">
                        <label class="form-label">⚙️ Statut</label>
                        <select class="form-control" name="status">
                            <option value="brouillon" <?php echo (($article['status'] ?? 'brouillon') === 'brouillon') ? 'selected' : ''; ?>>📝 Brouillon</option>
                            <option value="publié" <?php echo (($article['status'] ?? 'brouillon') === 'publié') ? 'selected' : ''; ?>>🚀 Publié</option>
                        </select>
                    </div>
                    
                    <!-- Auteur -->
                    <div class="mb-3">
                        <label class="form-label">👤 Auteur</label>
                        <div class="form-control-plaintext" style="background:#f8f9fa;padding:12px 14px;border-radius:8px;border:2px solid #e0e6ed;font-weight:600">
                            ✅ <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Anonyme'); ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="form-actions">
                <a href="index.php?page=blog_public" class="btn btn-secondary">
                    <i class="fas fa-times"></i> ❌ Annuler
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> 🚀 Publier l'article
                </button>
            </div>
        </form>
    </div>
</div>

<footer><div class="container"><p>© 2024 Valorys — Tous droits réservés</p><small>Plateforme médicale en ligne</small></div></footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof tinymce !== 'undefined') {
        tinymce.init({
            selector: '#editor',
            height: 400,
            width: '100%',
            language: 'fr_FR',
            theme: 'silver',
            toolbar: 'undo redo | bold italic underline strikethrough | forecolor backcolor | link image media | alignleft aligncenter alignright alignjustify | bullist numlist | emoticons | removeformat | fullscreen',
            plugins: 'link image media lists emoticons paste',
            menubar: 'file edit view insert format',
            relative_urls: false,
            remove_script_host: false,
            convert_urls: false,
            paste_as_text: false,
            image_advtab: true,
            image_title: true,
            automatic_uploads: true,
            file_picker_types: 'image',
            emoticons_database: 'emojis',
            entity_encoding: 'raw',
            valid_elements: '+*[*]',
            setup: function(editor) {
                editor.on('change', function() {
                    tinymce.triggerSave();
                });
            },
            init_instance_callback: function(editor) {
                console.log('TinyMCE initialized successfully for blog article creation');
            }
        });
    }
});
</script>

</body>
</html>
