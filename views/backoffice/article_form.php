<?php
// views/backoffice/article_form.php
// Variables expected from the controller:
//   $article  – array|null   – null for create, row array for edit
//   $isEdit   – bool         – true when editing
//   $title    – string       – page title
// Flash messages and old data come exclusively from $_SESSION.

if (session_status() === PHP_SESSION_NONE) session_start();

$flash = $_SESSION['flash'] ?? null;
$old   = $_SESSION['old']   ?? [];
unset($_SESSION['flash'], $_SESSION['old']);

// Fallback for standalone access (shouldn't happen in normal routing)
if (!isset($isEdit))  $isEdit  = isset($_GET['id']);
if (!isset($article)) $article = null;
if (!isset($title))   $title   = $isEdit ? "Modifier l'article" : 'Créer un article';

$id = $article['id'] ?? (int)($_GET['id'] ?? 0);

// CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

// Form action
$formAction = $isEdit
    ? "index.php?page=articles_admin&action=edit&id={$id}"
    : "index.php?page=articles_admin&action=create";
?>
<?php // Vue déprécée ?>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?> — MediConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{background:#f0f4f8;font-family:'Segoe UI',sans-serif}
        .sidebar{position:fixed;top:0;left:0;width:260px;height:100%;background:#0f2b3d;color:#fff;z-index:100;overflow-y:auto}
        .sidebar-header{padding:22px 20px;text-align:center;border-bottom:1px solid rgba(255,255,255,.1)}
        .sidebar-header .logo{font-size:20px;font-weight:700;color:#fff}
        .sidebar-header small{color:rgba(255,255,255,.5);font-size:11px}
        .sidebar-menu a{display:flex;align-items:center;gap:10px;padding:12px 22px;color:rgba(255,255,255,.7);text-decoration:none;font-size:13px;font-weight:500;transition:.2s;border-left:3px solid transparent}
        .sidebar-menu a:hover,.sidebar-menu a.active{background:rgba(76,175,80,.15);color:#fff;border-left-color:#4CAF50}
        .sidebar-menu a i{width:20px;font-size:1rem}
        .main{margin-left:260px;padding:24px 28px}
        .topbar{background:#fff;border-radius:16px;padding:14px 22px;margin-bottom:24px;display:flex;justify-content:space-between;align-items:center;box-shadow:0 2px 8px rgba(0,0,0,.05)}
        .topbar-title{font-size:17px;font-weight:700;color:#0f2b3d;display:flex;align-items:center;gap:10px}
        .topbar-title i{color:#4CAF50}
        .admin-badge{display:flex;align-items:center;gap:10px;font-size:13px;font-weight:500}
        .avatar{width:38px;height:38px;border-radius:50%;background:linear-gradient(135deg,#2A7FAA,#4CAF50);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700}
        .card-box{background:#fff;border-radius:16px;padding:28px;box-shadow:0 2px 8px rgba(0,0,0,.05);margin-bottom:24px}
        .section-label{font-size:11px;font-weight:700;color:#6c757d;text-transform:uppercase;letter-spacing:.6px;margin-bottom:14px;padding-bottom:8px;border-bottom:2px solid #f0f4f8}
        .form-label{font-weight:600;font-size:13px;color:#0f2b3d;margin-bottom:6px}
        .form-control,.form-select{border-radius:8px;border:1.5px solid #e0e6ed;padding:10px 13px;font-size:13px;transition:all .2s}
        .form-control:focus,.form-select:focus{border-color:#2A7FAA;box-shadow:0 0 0 3px rgba(42,127,170,.1);outline:none}
        .form-control.is-invalid,.form-select.is-invalid{border-color:#ef4444;box-shadow:0 0 0 3px rgba(239,68,68,.1)}
        .invalid-feedback{color:#ef4444;font-size:12px;font-weight:500;margin-top:4px;display:none}
        .required{color:#ef4444}
        .btn-save{background:linear-gradient(135deg,#2A7FAA,#4CAF50);color:#fff;border:none;border-radius:8px;padding:11px 28px;font-weight:600;font-size:14px;display:inline-flex;align-items:center;gap:8px;cursor:pointer;transition:all .2s}
        .btn-save:hover{opacity:.9;transform:translateY(-1px)}
        .flash-box{border-radius:8px;padding:13px 16px;margin-bottom:20px;font-size:13px;display:flex;align-items:center;gap:10px}
        .flash-error{background:#fdecea;color:#c62828;border:1px solid #f9a9a3}
        .flash-success{background:#e8f5e9;color:#2e7d32;border:1px solid #a5d6a7}
        textarea.form-control{resize:vertical;min-height:300px;font-family:'Courier New',monospace;font-size:13px}
        .sidebar-nav-divider{height:1px;background:rgba(255,255,255,.08);margin:8px 22px}
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
        <div class="sidebar-nav-divider"></div>
        <a href="index.php?page=medecins_admin"><i class="fas fa-user-md"></i> Médecins</a>
        <a href="index.php?page=patients"><i class="fas fa-user-injured"></i> Patients</a>
        <a href="index.php?page=users"><i class="fas fa-users"></i> Utilisateurs</a>
        <div class="sidebar-nav-divider"></div>
        <a href="index.php?page=logout"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
    </div>
</div>

<div class="main">
    <div class="topbar">
        <div class="topbar-title">
            <i class="fas fa-<?= $isEdit ? 'pen-to-square' : 'plus-circle' ?>"></i>
            <?= htmlspecialchars($title) ?>
        </div>
        <div class="admin-badge">
            <a href="index.php?page=articles_admin" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i> Retour à la liste
            </a>
            <div class="avatar ms-2"><?= strtoupper(substr($_SESSION['user_name'] ?? 'A', 0, 1)) ?></div>
        </div>
    </div>

    <?php if ($flash): ?>
        <div class="flash-box flash-<?= $flash['type'] === 'success' ? 'success' : 'error' ?>">
            <i class="fas fa-<?= $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
            <?= htmlspecialchars($flash['message']) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?= $formAction ?>" id="articleForm" novalidate>
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

        <div class="row">
            <!-- Main content -->
            <div class="col-md-8">
                <div class="card-box">
                    <div class="section-label"><i class="fas fa-align-left me-1"></i> Contenu de l'article</div>

                    <div class="mb-4">
                        <label class="form-label">Titre <span class="required">*</span></label>
                        <input type="text" id="titre" name="titre" class="form-control"
                               value="<?= htmlspecialchars($old['titre'] ?? $article['titre'] ?? '') ?>"
                               placeholder="Titre de l'article…">
                        <div class="invalid-feedback" id="titre-error"></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Contenu <span class="required">*</span></label>
                        <div style="display:flex;gap:10px;margin-bottom:12px;align-items:center;flex-wrap:wrap">
                            <button type="button" id="emojiBtn" class="btn btn-sm btn-outline-secondary" style="border-radius:6px;padding:6px 12px;font-size:14px">
                                <i class="fas fa-smile me-1"></i>Emoji
                            </button>
                            <button type="button" id="imgBtn" class="btn btn-sm btn-outline-secondary" style="border-radius:6px;padding:6px 12px;font-size:14px">
                                <i class="fas fa-image me-1"></i>Image
                            </button>
                            <input type="file" id="imgUpload" accept="image/*" style="display:none">
                            <small class="text-muted" style="flex-basis:100%">Cliquez sur les boutons pour ajouter du contenu enrichi</small>
                        </div>
                        
                        <!-- Emoji Picker -->
                        <div id="emojiPicker" style="display:none;background:#f8f9fa;border:1px solid #dee2e6;border-radius:8px;padding:12px;margin-bottom:12px;max-height:300px;overflow-y:auto">
                            <div id="emojiGrid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(30px,1fr));gap:5px"></div>
                        </div>
                        
                        <!-- Image Preview -->
                        <div id="imgPreview" style="display:none;margin-bottom:12px">
                            <img id="imgPreviewImg" src="" style="max-width:100%;max-height:300px;border-radius:8px;border:1px solid #dee2e6">
                            <button type="button" id="removeImg" class="btn btn-sm btn-danger mt-2">
                                <i class="fas fa-trash me-1"></i>Supprimer
                            </button>
                        </div>

                        <!-- Quill Editor -->
                        <div id="editor-container" style="background:white;border:1.5px solid #e0e6ed;border-radius:8px;min-height:300px"></div>
                        <input type="hidden" id="contenu" name="contenu" value="<?= htmlspecialchars($old['contenu'] ?? $article['contenu'] ?? '') ?>">
                        <small class="text-muted mt-2 d-block">✨ Texte • 🖼️ Images • 😊 Emoji • 📝 Formatage</small>
                        <div class="invalid-feedback" id="contenu-error"></div>
                    </div>
                </div>
            </div>

            <!-- Sidebar options -->
            <div class="col-md-4">
                <div class="card-box">
                    <div class="section-label"><i class="fas fa-sliders me-1"></i> Paramètres</div>

                    <div class="mb-3">
                        <label class="form-label">Statut</label>
                        <select id="status" name="status" class="form-select">
                            <?php $currentStatus = $old['status'] ?? $article['status'] ?? 'brouillon'; ?>
                            <option value="brouillon" <?= $currentStatus === 'brouillon' ? 'selected' : '' ?>>📝 Brouillon</option>
                            <option value="publié"    <?= $currentStatus === 'publié'    ? 'selected' : '' ?>>✅ Publié</option>
                            <option value="archive"   <?= $currentStatus === 'archive'   ? 'selected' : '' ?>>📦 Archivé</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Catégorie</label>
                        <input type="text" id="categorie" name="categorie" class="form-control"
                               value="<?= htmlspecialchars($old['categorie'] ?? $article['categorie'] ?? '') ?>"
                               placeholder="Ex: Santé, Actualités…">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Tags</label>
                        <input type="text" id="tags" name="tags" class="form-control"
                               value="<?= htmlspecialchars($old['tags'] ?? $article['tags'] ?? '') ?>"
                               placeholder="tag1, tag2, tag3">
                        <small class="text-muted">Séparés par des virgules</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Auteur</label>
                        <div class="form-control" style="background:#f8f9fa;color:#6c757d">
                            <i class="fas fa-user me-1"></i>
                            <?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin') ?>
                        </div>
                    </div>

                    <?php if ($isEdit && $article): ?>
                        <div class="mb-3">
                            <label class="form-label">Créé le</label>
                            <div class="form-control" style="background:#f8f9fa;color:#6c757d;font-size:12px">
                                <?= date('d/m/Y H:i', strtotime($article['created_at'] ?? 'now')) ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="card-box">
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn-save" id="submitBtn">
                            <i class="fas fa-save"></i>
                            <?= $isEdit ? 'Enregistrer les modifications' : 'Publier l\'article' ?>
                        </button>
                        <a href="index.php?page=articles_admin" class="btn btn-secondary">
                            <i class="fas fa-times me-1"></i> Annuler
                        </a>
                        <?php if ($isEdit && $article): ?>
                            <a href="index.php?page=articles_admin&action=delete&id=<?= $id ?>"
                               class="btn btn-danger"
                               onclick="return confirm('Supprimer définitivement cet article ?')">
                                <i class="fas fa-trash me-1"></i> Supprimer
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- Quill Editor -->
<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">

<script>
// === INITIALIZE QUILL EDITOR ===
const quill = new Quill('#editor-container', {
    theme: 'snow',
    placeholder: 'Rédigez votre article ici…',
    modules: {
        toolbar: [
            ['bold', 'italic', 'underline', 'strike'],
            ['blockquote', 'code-block'],
            [{ 'header': 1 }, { 'header': 2 }],
            [{ 'list': 'ordered'}, { 'list': 'bullet' }],
            ['link', 'image'],
            ['clean']
        ]
    }
});

// Load existing content if editing
const existingContent = document.getElementById('contenu').value;
if (existingContent) {
    quill.setContents(JSON.parse(existingContent) || existingContent, 'api');
}

// Update hidden field on change
quill.on('text-change', function() {
    document.getElementById('contenu').value = JSON.stringify(quill.getContents());
});

// === EMOJI PICKER ===
const emojis = ['😀', '😂', '🤣', '😃', '😄', '😁', '😆', '😅', '🤭', '😉', '😊', '😇', '🙂', '🙃', '😉', '😌', '😍', '🥰', '😘', '😗', '😚', '😙', '🥲', '😋', '😛', '😜', '🤪', '😝', '🤑', '🤗', '🤭', '🤫', '🤔', '🤐', '🤨', '😐', '😑', '😶', '😏', '😒', '🙄', '😬', '🤥', '😌', '😔', '😪', '🤤', '😴', '😷', '🤒', '🤕', '🤢', '🤮', '🤮', '🤎', '❤️', '🧡', '💛', '💚', '💙', '💜', '🖤', '🤍', '👍', '👎', '👋', '👊', '✊', '🤚', '🖐️', '✋', '🤍', '💪', '🦾', '🦿', '🦴', '👀', '👁️'];

const emojiBtn = document.getElementById('emojiBtn');
const emojiPicker = document.getElementById('emojiPicker');
const emojiGrid = document.getElementById('emojiGrid');

// Generate emoji grid
emojis.forEach(emoji => {
    const span = document.createElement('span');
    span.textContent = emoji;
    span.style.cursor = 'pointer';
    span.style.fontSize = '24px';
    span.style.padding = '8px';
    span.style.borderRadius = '4px';
    span.style.transition = 'all 0.2s';
    span.onmouseover = () => span.style.background = '#e0e0e0';
    span.onmouseout = () => span.style.background = 'transparent';
    span.onclick = () => {
        quill.insertText(quill.getSelection()?.index || quill.getLength(), emoji);
        quill.setSelection(quill.getSelection().index + emoji.length);
    };
    emojiGrid.appendChild(span);
});

// Toggle emoji picker
emojiBtn.addEventListener('click', (e) => {
    e.preventDefault();
    emojiPicker.style.display = emojiPicker.style.display === 'none' ? 'grid' : 'none';
});

// === IMAGE UPLOAD ===
const imgBtn = document.getElementById('imgBtn');
const imgUpload = document.getElementById('imgUpload');
const imgPreview = document.getElementById('imgPreview');
const imgPreviewImg = document.getElementById('imgPreviewImg');
const removeImg = document.getElementById('removeImg');

imgBtn.addEventListener('click', (e) => {
    e.preventDefault();
    imgUpload.click();
});

imgUpload.addEventListener('change', (e) => {
    const file = e.target.files[0];
    if (!file) return;

    // Validate file size (max 5MB)
    if (file.size > 5 * 1024 * 1024) {
        alert('L\'image doit faire moins de 5MB');
        return;
    }

    // Convert to base64 and insert into Quill
    const reader = new FileReader();
    reader.onload = (event) => {
        const imageDataUrl = event.target.result;
        
        // Insert image into editor
        const index = quill.getSelection()?.index || quill.getLength();
        quill.insertEmbed(index, 'image', imageDataUrl);
        quill.setSelection(index + 1);
        
        // Show preview
        imgPreviewImg.src = imageDataUrl;
        imgPreview.style.display = 'block';
    };
    reader.readAsDataURL(file);
});

removeImg.addEventListener('click', (e) => {
    e.preventDefault();
    imgPreview.style.display = 'none';
    imgPreviewImg.src = '';
    imgUpload.value = '';
});

// === FORM VALIDATION & SUBMISSION ===
document.getElementById('articleForm').addEventListener('submit', function(e) {
    let valid = true;
    document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
    document.querySelectorAll('.invalid-feedback').forEach(el => { el.textContent = ''; el.style.display = 'none'; });

    function err(inputId, errId, msg) {
        const inp = document.getElementById(inputId);
        const div = document.getElementById(errId);
        if (inp) inp.classList.add('is-invalid');
        if (div) { div.textContent = msg; div.style.display = 'block'; }
        valid = false;
    }

    const titre = document.getElementById('titre').value.trim();
    if (!titre || titre.length < 3) err('titre', 'titre-error', 'Le titre doit contenir au moins 3 caractères.');

    const contenuText = quill.getText().trim();
    if (!contenuText || contenuText.length < 10) err('contenu', 'contenu-error', 'Le contenu doit contenir au moins 10 caractères.');

    // Save content to hidden field before submission
    document.getElementById('contenu').value = JSON.stringify(quill.getContents());

    if (!valid) {
        e.preventDefault();
        const errorEl = document.querySelector('.is-invalid')?.parentElement || document.querySelector('.invalid-feedback');
        if (errorEl) errorEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
    } else {
        document.getElementById('submitBtn').innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enregistrement…';
        document.getElementById('submitBtn').disabled = true;
    }
});
</script>
</body>
</html>
