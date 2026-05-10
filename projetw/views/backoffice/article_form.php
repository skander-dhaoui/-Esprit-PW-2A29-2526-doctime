<?php
// views/backoffice/article_form.php
if (session_status() === PHP_SESSION_NONE) session_start();

$flash = $_SESSION['flash'] ?? null;
$old   = $_SESSION['old']   ?? [];
unset($_SESSION['flash'], $_SESSION['old']);

if (!isset($isEdit))  $isEdit  = isset($_GET['id']);
if (!isset($article)) $article = null;
if (!isset($title))   $title   = $isEdit ? "Modifier l'article" : 'Créer un article';

$id = $article['id'] ?? (int)($_GET['id'] ?? 0);

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

$formAction = $isEdit
    ? "index.php?page=articles_admin&action=edit&id={$id}"
    : "index.php?page=articles_admin&action=create";

// Préparer le contenu existant
$existingContent = $old['contenu'] ?? $article['contenu'] ?? '';
$existingTitre   = htmlspecialchars($old['titre'] ?? $article['titre'] ?? '');
?>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?> — Valorys</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
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
        .sidebar-nav-divider{height:1px;background:rgba(255,255,255,.08);margin:8px 22px}
        .main{margin-left:260px;padding:24px 28px}
        .topbar{background:#fff;border-radius:16px;padding:14px 22px;margin-bottom:24px;display:flex;justify-content:space-between;align-items:center;box-shadow:0 2px 8px rgba(0,0,0,.05)}
        .topbar-title{font-size:17px;font-weight:700;color:#0f2b3d;display:flex;align-items:center;gap:10px}
        .topbar-title i{color:#4CAF50}
        .avatar{width:38px;height:38px;border-radius:50%;background:linear-gradient(135deg,#2A7FAA,#4CAF50);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700}
        .card-box{background:#fff;border-radius:16px;padding:28px;box-shadow:0 2px 8px rgba(0,0,0,.05);margin-bottom:24px}
        .section-label{font-size:11px;font-weight:700;color:#6c757d;text-transform:uppercase;letter-spacing:.6px;margin-bottom:14px;padding-bottom:8px;border-bottom:2px solid #f0f4f8}
        .form-label{font-weight:600;font-size:13px;color:#0f2b3d;margin-bottom:6px}
        .form-control,.form-select{border-radius:8px;border:1.5px solid #e0e6ed;padding:10px 13px;font-size:13px;transition:all .2s}
        .form-control:focus,.form-select:focus{border-color:#2A7FAA;box-shadow:0 0 0 3px rgba(42,127,170,.1);outline:none}
        .required{color:#ef4444}
        .btn-save{background:linear-gradient(135deg,#2A7FAA,#4CAF50);color:#fff;border:none;border-radius:8px;padding:11px 28px;font-weight:600;font-size:14px;display:inline-flex;align-items:center;gap:8px;cursor:pointer;transition:all .2s;width:100%}
        .btn-save:hover{opacity:.9;transform:translateY(-1px)}
        .flash-box{border-radius:8px;padding:13px 16px;margin-bottom:20px;font-size:13px;display:flex;align-items:center;gap:10px}
        .flash-error{background:#fdecea;color:#c62828;border:1px solid #f9a9a3}
        .flash-success{background:#e8f5e9;color:#2e7d32;border:1px solid #a5d6a7}
        #editor-container{background:white;border:1.5px solid #e0e6ed;border-radius:0 0 8px 8px;min-height:300px}
        .ql-toolbar{border:1.5px solid #e0e6ed;border-radius:8px 8px 0 0}
        .emoji-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(36px,1fr));gap:4px;max-height:200px;overflow-y:auto;padding:10px}
        .emoji-btn{font-size:22px;cursor:pointer;padding:4px;border-radius:6px;text-align:center;border:none;background:none}
        .emoji-btn:hover{background:#e4e6eb;}
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
        <a href="index.php?page=replies_admin"><i class="fas fa-comments"></i> Commentaires</a>
        <a href="index.php?page=evenements_admin"><i class="fas fa-calendar"></i> Événements</a>
        <div class="sidebar-nav-divider"></div>
        <a href="index.php?page=medecins_admin"><i class="fas fa-user-md"></i> Médecins</a>
        <a href="index.php?page=patients"><i class="fas fa-user-injured"></i> Patients</a>
        <a href="index.php?page=users"><i class="fas fa-users"></i> Utilisateurs</a>
        <div class="sidebar-nav-divider"></div>
        <a href="index.php?page=blog_public"><i class="fas fa-eye"></i> Voir le site</a>
        <a href="index.php?page=logout"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
    </div>
</div>

<div class="main">
    <div class="topbar">
        <div class="topbar-title">
            <i class="fas fa-<?= $isEdit ? 'pen-to-square' : 'plus-circle' ?>"></i>
            <?= htmlspecialchars($title) ?>
        </div>
        <div style="display:flex;align-items:center;gap:10px;">
            <a href="index.php?page=articles_admin" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i> Retour
            </a>
            <div class="avatar"><?= strtoupper(substr($_SESSION['user_name'] ?? 'A', 0, 1)) ?></div>
        </div>
    </div>

    <?php if ($flash): ?>
    <div class="flash-box flash-<?= $flash['type'] === 'success' ? 'success' : 'error' ?>">
        <i class="fas fa-<?= $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
        <?= htmlspecialchars($flash['message']) ?>
    </div>
    <?php endif; ?>

    <form method="POST" action="<?= $formAction ?>" id="articleForm" enctype="multipart/form-data" novalidate>
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
        <input type="hidden" id="contenu" name="contenu" value="">

        <div class="row">
            <!-- CONTENU PRINCIPAL -->
            <div class="col-md-8">
                <div class="card-box">
                    <div class="section-label"><i class="fas fa-align-left me-1"></i> Contenu de l'article</div>

                    <!-- TITRE -->
                    <div class="mb-4">
                        <label class="form-label">Titre <span class="required">*</span></label>
                        <input type="text" id="titre" name="titre" class="form-control"
                               value="<?= $existingTitre ?>"
                               placeholder="Titre de l'article…" required>
                    </div>

                    <!-- IMAGE COUVERTURE -->
                    <div class="mb-3">
                        <label class="form-label">Image de couverture (optionnel)</label>
                        <input type="file" name="article_image" id="coverImg" accept="image/*" class="form-control"
                               onchange="previewCover(this)">
                        <small class="text-muted">JPG, PNG, GIF. Max 2 Mo.</small>
                        <div id="coverPreview" style="display:none;margin-top:10px;">
                            <img id="coverPreviewImg" src="" style="max-width:100%;max-height:200px;border-radius:8px;">
                            <button type="button" onclick="removeCover()" class="btn btn-sm btn-danger mt-2">
                                <i class="fas fa-trash"></i> Supprimer
                            </button>
                        </div>
                        <?php if (!empty($article['image'])): ?>
                        <div style="margin-top:10px;">
                            <img src="<?= htmlspecialchars($article['image']) ?>" style="max-width:200px;border-radius:8px;">
                            <small class="text-muted d-block">Image actuelle</small>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- CONTENU QUILL -->
                    <div class="mb-3">
                        <label class="form-label">Contenu <span class="required">*</span></label>

                        <!-- BARRE OUTILS -->
                        <div style="display:flex;gap:8px;margin-bottom:10px;flex-wrap:wrap;">
                            <button type="button" id="emojiBtn" class="btn btn-sm btn-outline-warning" style="border-radius:20px;">
                                😊 Emoji
                            </button>
                            <button type="button" id="imgBtn" class="btn btn-sm btn-outline-success" style="border-radius:20px;">
                                🖼️ Image
                            </button>
                            <input type="file" id="imgUpload" accept="image/*" style="display:none">
                        </div>

                        <!-- EMOJI PICKER -->
                        <div id="emojiPicker" style="display:none;background:#f8f9fa;border:1px solid #dee2e6;border-radius:10px;margin-bottom:10px;">
                            <div class="emoji-grid" id="emojiGrid"></div>
                        </div>

                        <!-- IMAGE PREVIEW -->
                        <div id="imgPreview" style="display:none;margin-bottom:10px;">
                            <img id="imgPreviewImg" src="" style="max-width:100%;max-height:200px;border-radius:8px;">
                            <button type="button" onclick="removeImg()" class="btn btn-sm btn-danger mt-2">
                                <i class="fas fa-trash"></i> Supprimer
                            </button>
                        </div>

                        <!-- QUILL EDITOR -->
                        <div id="editor-container"></div>
                        <small class="text-muted mt-1 d-block">✨ Texte riche • 🖼️ Images • 😊 Emoji • B I U formatage</small>
                    </div>
                </div>
            </div>

            <!-- PARAMÈTRES -->
            <div class="col-md-4">
                <div class="card-box">
                    <div class="section-label"><i class="fas fa-sliders me-1"></i> Paramètres</div>

                    <div class="mb-3">
                        <label class="form-label">Statut</label>
                        <select name="status" class="form-select">
                            <?php $currentStatus = $old['status'] ?? $article['status'] ?? 'brouillon'; ?>
                            <option value="brouillon" <?= $currentStatus==='brouillon'?'selected':'' ?>>📝 Brouillon</option>
                            <option value="publié"    <?= $currentStatus==='publié'?'selected':'' ?>>✅ Publié</option>
                            <option value="archive"   <?= $currentStatus==='archive'?'selected':'' ?>>📦 Archivé</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Catégorie</label>
                        <input type="text" name="categorie" class="form-control"
                               value="<?= htmlspecialchars($old['categorie'] ?? $article['categorie'] ?? '') ?>"
                               placeholder="Ex: Santé, Actualités…">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Tags</label>
                        <input type="text" name="tags" class="form-control"
                               value="<?= htmlspecialchars($old['tags'] ?? $article['tags'] ?? '') ?>"
                               placeholder="tag1, tag2, tag3">
                        <small class="text-muted">Séparés par des virgules</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Auteur</label>
                        <div class="form-control" style="background:#f8f9fa;color:#6c757d;">
                            <i class="fas fa-user me-1"></i>
                            <?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin') ?>
                        </div>
                    </div>

                    <?php if ($isEdit && $article): ?>
                    <div class="mb-3">
                        <label class="form-label">Créé le</label>
                        <div class="form-control" style="background:#f8f9fa;color:#6c757d;font-size:12px;">
                            <?= date('d/m/Y H:i', strtotime($article['created_at'] ?? 'now')) ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="card-box">
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn-save" id="submitBtn">
                            <i class="fas fa-save"></i>
                            <?= $isEdit ? 'Enregistrer les modifications' : "Publier l'article" ?>
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
<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
<script>
// ── QUILL INIT ────────────────────────────────────
var quill = new Quill('#editor-container', {
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

// ── CHARGER LE CONTENU EXISTANT ───────────────────
var rawContent = <?= json_encode($existingContent) ?>;
if (rawContent && rawContent.trim() !== '') {
    try {
        var parsed = JSON.parse(rawContent);
        if (parsed && parsed.ops) {
            quill.setContents(parsed);
        } else {
            quill.setText(rawContent);
        }
    } catch(e) {
        // Contenu texte brut
        quill.setText(rawContent);
    }
}

// Sync hidden field
quill.on('text-change', function() {
    document.getElementById('contenu').value = JSON.stringify(quill.getContents());
});

// ── EMOJIS ────────────────────────────────────────
var emojis = ['😀','😂','😍','🥰','😎','😢','😡','👍','👎','❤️','🔥','✅','⭐','💪','🙏','🤔','😷','🏥','💊','🩺','🎉','📝','💬','🌟','👏','🎯','💡','🔔','📢','🌿'];
var emojiGrid = document.getElementById('emojiGrid');
emojis.forEach(function(em) {
    var btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'emoji-btn';
    btn.textContent = em;
    btn.onclick = function() {
        var idx = quill.getSelection() ? quill.getSelection().index : quill.getLength();
        quill.insertText(idx, em);
        quill.setSelection(idx + em.length);
        document.getElementById('emojiPicker').style.display = 'none';
    };
    emojiGrid.appendChild(btn);
});

document.getElementById('emojiBtn').onclick = function(e) {
    e.preventDefault();
    var p = document.getElementById('emojiPicker');
    p.style.display = p.style.display === 'none' || p.style.display === '' ? 'block' : 'none';
};

// ── IMAGE DANS QUILL ──────────────────────────────
document.getElementById('imgBtn').onclick = function(e) {
    e.preventDefault();
    document.getElementById('imgUpload').click();
};

document.getElementById('imgUpload').onchange = function(e) {
    var file = e.target.files[0];
    if (!file) return;
    if (file.size > 5*1024*1024) { alert('Image trop grande (max 5Mo)'); return; }
    var reader = new FileReader();
    reader.onload = function(ev) {
        var idx = quill.getSelection() ? quill.getSelection().index : quill.getLength();
        quill.insertEmbed(idx, 'image', ev.target.result);
        quill.setSelection(idx + 1);
        document.getElementById('imgPreviewImg').src = ev.target.result;
        document.getElementById('imgPreview').style.display = 'block';
    };
    reader.readAsDataURL(file);
};

function removeImg() {
    document.getElementById('imgUpload').value = '';
    document.getElementById('imgPreview').style.display = 'none';
    document.getElementById('imgPreviewImg').src = '';
}

// ── IMAGE COUVERTURE ──────────────────────────────
function previewCover(input) {
    if (input.files && input.files[0]) {
        var r = new FileReader();
        r.onload = function(ev) {
            document.getElementById('coverPreviewImg').src = ev.target.result;
            document.getElementById('coverPreview').style.display = 'block';
        };
        r.readAsDataURL(input.files[0]);
    }
}

function removeCover() {
    document.getElementById('coverImg').value = '';
    document.getElementById('coverPreview').style.display = 'none';
}

// ── SOUMISSION ────────────────────────────────────
document.getElementById('articleForm').onsubmit = function(e) {
    var titre = document.getElementById('titre').value.trim();
    if (!titre || titre.length < 2) {
        e.preventDefault();
        alert('Le titre est obligatoire.');
        return;
    }
    var txt = quill.getText().trim();
    if (!txt || txt.length < 3) {
        e.preventDefault();
        alert('Le contenu est obligatoire.');
        return;
    }
    document.getElementById('contenu').value = JSON.stringify(quill.getContents());
    document.getElementById('submitBtn').innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enregistrement…';
    document.getElementById('submitBtn').disabled = true;
};
</script>
</body>
</html>