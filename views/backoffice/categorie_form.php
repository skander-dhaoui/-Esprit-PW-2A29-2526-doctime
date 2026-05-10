<?php
// views/backoffice/categorie_form.php
// Used for both create ($isEdit = false) and edit ($isEdit = true)
$current_page = 'categories_admin';
$isEdit = $isEdit ?? false;
$categorie = $categorie ?? [];
$old = $old ?? [];
?>
<?php // Vue déprécée ?>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $isEdit ? 'Modifier' : 'Nouvelle' ?> Catégorie - DocTime Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        :root {
            --navy:      #1a2035;
            --teal:      #0fa99b;
            --teal-dark: #0d8a7d;
            --red:       #ef4444;
            --gray-50:   #f8fafc;
            --gray-200:  #e2e8f0;
            --gray-700:  #334155;
            --gray-900:  #0f172a;
            --shadow:    0 1px 6px rgba(0,0,0,.07);
            --radius:    12px;
        }
        body { font-family:'Segoe UI',system-ui,sans-serif; background:var(--gray-50); color:var(--gray-900); min-height:100vh; display:flex; }
        .main-content { margin-left:260px; flex:1; display:flex; flex-direction:column; }

        .page-header { padding:25px 30px 10px; display:flex; justify-content:space-between; align-items:center; }
        .page-header h4 { font-size:22px; font-weight:700; color:var(--navy); display:flex; align-items:center; gap:10px; margin:0; }
        .page-header h4 i { color:var(--teal); }

        .breadcrumb-bar { padding:0 30px; margin-top:6px; margin-bottom:4px; font-size:12px; color:var(--gray-700); }
        .breadcrumb-bar a { color:var(--teal); text-decoration:none; }
        .breadcrumb-bar a:hover { text-decoration:underline; }

        /* Info banner (edit only) */
        .cat-banner { background:linear-gradient(135deg,var(--navy) 0%,#2a3554 100%); border-radius:var(--radius); padding:18px 22px; margin:12px 30px 0; display:flex; align-items:center; gap:16px; color:white; }
        .cat-banner .cat-ico { width:52px; height:52px; background:rgba(255,255,255,.15); border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:22px; flex-shrink:0; }
        .cat-banner h6 { font-size:16px; font-weight:700; margin:0 0 4px; }
        .cat-banner small { font-size:12px; opacity:.75; }

        .content-card { background:#fff; border-radius:var(--radius); box-shadow:var(--shadow); padding:30px; margin:18px 30px; max-width:860px; }

        .form-section { margin-bottom:28px; }
        .form-section h5 { font-size:15px; font-weight:700; color:var(--navy); margin-bottom:18px; border-bottom:2px solid var(--gray-200); padding-bottom:8px; display:flex; align-items:center; gap:8px; }
        .form-section h5 i { color:var(--teal); }

        .form-row      { display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:20px; }
        .form-row.full { grid-template-columns:1fr; }

        .form-group label { display:block; font-weight:600; margin-bottom:7px; font-size:12px; color:var(--gray-700); text-transform:uppercase; letter-spacing:.5px; }
        .form-group label .req { color:var(--red); }

        .form-group input,.form-group select,.form-group textarea {
            width:100%; padding:11px 14px; border:1.5px solid var(--gray-200);
            border-radius:8px; font-size:14px; transition:all .2s; background:white; color:var(--gray-900);
        }
        .form-group input:focus,.form-group select:focus,.form-group textarea:focus {
            border-color:var(--teal); outline:none; box-shadow:0 0 0 3px rgba(15,169,155,.1);
        }
        .form-group textarea { resize:vertical; min-height:100px; }
        .hint { font-size:11px; color:var(--gray-700); margin-top:4px; font-style:italic; }

        .is-invalid { border-color:var(--red)!important; box-shadow:0 0 0 3px rgba(239,68,68,.1)!important; }
        .error-text  { color:var(--red); font-size:12px; margin-top:5px; display:none; font-weight:500; }

        /* Slug preview */
        .slug-preview { margin-top:5px; font-size:12px; color:var(--teal); background:#e0f5f3; padding:4px 10px; border-radius:6px; display:inline-block; }

        /* Image preview */
        .img-preview-box { width:80px; height:80px; border-radius:10px; border:2px dashed var(--gray-200); display:flex; align-items:center; justify-content:center; font-size:28px; color:var(--gray-700); overflow:hidden; flex-shrink:0; }
        .img-preview-box img { width:100%; height:100%; object-fit:cover; }

        .action-buttons { display:flex; gap:12px; margin-top:30px; padding-top:20px; border-top:1px solid var(--gray-200); flex-wrap:wrap; }
        .btn { display:inline-flex; align-items:center; gap:8px; padding:12px 24px; border-radius:8px; font-size:14px; font-weight:600; cursor:pointer; border:none; text-decoration:none; transition:all .2s; }
        .btn-teal      { background:var(--teal); color:white; }
        .btn-teal:hover { background:var(--teal-dark); transform:translateY(-1px); color:white; }
        .btn-secondary { background:var(--gray-200); color:var(--gray-700); }
        .btn-secondary:hover { background:#cbd5e1; }
        .btn-danger    { background:var(--red); color:white; margin-left:auto; }
        .btn-danger:hover { background:#dc2626; color:white; }

        .flash-box { border-radius:8px; padding:14px 16px; margin:14px 30px 0; font-size:14px; display:flex; align-items:center; gap:10px; }
        .flash-error   { background:#fdecea; color:#c62828; border:1px solid #f9a9a3; }
        .flash-success { background:#e8f5e9; color:#2e7d32; border:1px solid #a5d6a7; }

        .meta-info { background:var(--gray-50); border-radius:8px; padding:12px 16px; margin-bottom:20px; font-size:12px; color:var(--gray-700); display:flex; gap:20px; flex-wrap:wrap; }
        .meta-info span i { margin-right:5px; color:var(--teal); }
    </style>
</head>
<body>

<?php include __DIR__ . '/sidebar.php'; ?>

<div class="main-content">

    <div class="page-header">
        <h4>
            <i class="fas fa-<?= $isEdit ? 'pen' : 'plus-circle' ?>"></i>
            <?= $isEdit ? 'Modifier la Catégorie' : 'Nouvelle Catégorie' ?>
        </h4>
        <a href="index.php?page=categories_admin" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Retour à la liste
        </a>
    </div>

    <div class="breadcrumb-bar">
        <a href="index.php?page=dashboard">Tableau de bord</a>
        &rsaquo; <a href="index.php?page=categories_admin">Catégories</a>
        &rsaquo; <strong><?= $isEdit ? 'Modifier #'.($categorie['id'] ?? '') : 'Nouvelle' ?></strong>
    </div>

    <?php if ($isEdit && !empty($categorie)): ?>
    <div class="cat-banner">
        <div class="cat-ico">
            <?php if (!empty($categorie['image'])): ?>
                <img src="<?= htmlspecialchars($categorie['image']) ?>" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:12px;">
            <?php else: ?>
                <i class="fas fa-tag"></i>
            <?php endif; ?>
        </div>
        <div>
            <h6><?= htmlspecialchars($categorie['nom']) ?></h6>
            <small>
                Slug : <?= htmlspecialchars($categorie['slug']) ?>
                &bull; Produits : <?= (int)($categorie['nb_produits'] ?? 0) ?>
                <?php if (!empty($categorie['parent_nom'])): ?>
                    &bull; Parente : <?= htmlspecialchars($categorie['parent_nom']) ?>
                <?php endif; ?>
            </small>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($flash)): ?>
    <div class="flash-box flash-<?= $flash['type'] ?>">
        <i class="fas fa-<?= $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
        <?= $flash['message'] ?>
    </div>
    <?php endif; ?>

    <div class="content-card">

        <?php if ($isEdit && !empty($categorie)): ?>
        <div class="meta-info">
            <span><i class="fas fa-hashtag"></i> ID : #<?= $categorie['id'] ?></span>
            <?php if (!empty($categorie['created_at'])): ?>
            <span><i class="fas fa-calendar-plus"></i> Créé le : <?= date('d/m/Y', strtotime($categorie['created_at'])) ?></span>
            <?php endif; ?>
            <?php if (!empty($categorie['updated_at'])): ?>
            <span><i class="fas fa-edit"></i> Modifié le : <?= date('d/m/Y H:i', strtotime($categorie['updated_at'])) ?></span>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <form method="POST" id="catForm"
              action="index.php?page=categories_admin&action=<?= $isEdit ? 'edit&id='.($categorie['id'] ?? 0) : 'create' ?>"
              novalidate>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">

            <!-- Section : Informations -->
            <div class="form-section">
                <h5><i class="fas fa-info-circle"></i> Informations</h5>
                <div class="form-row">
                    <div class="form-group">
                        <label>Nom <span class="req">*</span></label>
                        <input type="text" id="nom" name="nom" maxlength="100"
                               value="<?= htmlspecialchars($old['nom'] ?? $categorie['nom'] ?? '') ?>"
                               placeholder="Ex: Antibiotiques"
                               oninput="updateSlugPreview(this.value)">
                        <div class="slug-preview" id="slugPreview">
                            <?= htmlspecialchars($old['slug'] ?? $categorie['slug'] ?? '') ?>
                        </div>
                        <div class="error-text" id="nom-error"></div>
                    </div>
                    <div class="form-group">
                        <label>Catégorie parente</label>
                        <select name="parent_id" id="parent_id">
                            <option value="">— Aucune (niveau racine) —</option>
                            <?php foreach ($categories ?? [] as $cat): ?>
                            <option value="<?= $cat['id'] ?>"
                                <?= (int)($old['parent_id'] ?? $categorie['parent_id'] ?? 0) === (int)$cat['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['nom']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="hint">Laissez vide pour une catégorie de premier niveau.</div>
                    </div>
                </div>

                <div class="form-row full">
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" id="description" maxlength="500"
                                  placeholder="Description courte de la catégorie…"><?= htmlspecialchars($old['description'] ?? $categorie['description'] ?? '') ?></textarea>
                        <div class="char-counter" id="desc-counter" style="font-size:11px;color:var(--gray-700);text-align:right;margin-top:4px;">0 / 500</div>
                    </div>
                </div>
            </div>

            <!-- Section : Options -->
            <div class="form-section">
                <h5><i class="fas fa-sliders-h"></i> Options</h5>
                <div class="form-row full">
                    <div class="form-group">
                        <label>Image (URL)</label>
                        <div style="display:flex;gap:12px;align-items:flex-start;">
                            <div class="img-preview-box" id="imgPreviewBox">
                                <?php if (!empty($old['image'] ?? $categorie['image'] ?? '')): ?>
                                    <img id="imgPreview" src="<?= htmlspecialchars($old['image'] ?? $categorie['image']) ?>" alt="">
                                <?php else: ?>
                                    <i class="fas fa-image" id="imgPlaceholder"></i>
                                <?php endif; ?>
                            </div>
                            <div style="flex:1;">
                                <input type="text" name="image" id="image"
                                       value="<?= htmlspecialchars($old['image'] ?? $categorie['image'] ?? '') ?>"
                                       placeholder="https://exemple.com/image.png"
                                       oninput="previewImage(this.value)">
                                <div class="hint">URL directe vers une image (png, jpg, webp…)</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="action-buttons">
                <button type="submit" class="btn btn-teal" id="submitBtn">
                    <i class="fas fa-save"></i>
                    <?= $isEdit ? 'Enregistrer les modifications' : 'Créer la catégorie' ?>
                </button>
                <a href="index.php?page=categories_admin" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Annuler
                </a>
                <?php if ($isEdit && !empty($categorie) && (int)($categorie['nb_produits'] ?? 0) === 0): ?>
                <a href="#"
                   onclick="return confirm('Supprimer définitivement cette catégorie ?')"
                   onclick2="window.location='index.php?page=categories_admin&action=delete&id=<?= $categorie['id'] ?>'"
                   class="btn btn-danger"
                   id="deleteBtn">
                    <i class="fas fa-trash"></i> Supprimer
                </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<script>
// ── Slug preview ──────────────────────────────
function slugify(text) {
    const map = {'é':'e','è':'e','ê':'e','ë':'e','à':'a','â':'a','ù':'u','û':'u','î':'i','ï':'i','ô':'o','ç':'c','œ':'oe','æ':'ae'};
    return text.toLowerCase().replace(/[éèêëàâùûîïôçœæ]/g, m => map[m]||m)
               .replace(/[^a-z0-9]+/g,'-').replace(/^-|-$/g,'');
}
function updateSlugPreview(val) {
    const slug = slugify(val);
    document.getElementById('slugPreview').textContent = slug || '—';
}

// ── Image preview ─────────────────────────────
function previewImage(url) {
    const box = document.getElementById('imgPreviewBox');
    if (!url) {
        box.innerHTML = '<i class="fas fa-image" style="color:var(--gray-700);font-size:28px;"></i>';
        return;
    }
    const img = new Image();
    img.onload  = () => box.innerHTML = `<img src="${url}" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:8px;">`;
    img.onerror = () => box.innerHTML = '<i class="fas fa-image" style="color:var(--red);font-size:28px;" title="URL invalide"></i>';
    img.src = url;
}

// ── Character counter ─────────────────────────
const desc = document.getElementById('description');
const ctr  = document.getElementById('desc-counter');
desc.addEventListener('input', () => ctr.textContent = desc.value.length + ' / 500');
// init
ctr.textContent = desc.value.length + ' / 500';

// ── Fix delete button ─────────────────────────
const delBtn = document.getElementById('deleteBtn');
if (delBtn) {
    delBtn.addEventListener('click', function(e) {
        e.preventDefault();
        if (confirm('Supprimer définitivement cette catégorie ?')) {
            window.location = 'index.php?page=categories_admin&action=delete&id=<?= $categorie['id'] ?? 0 ?>';
        }
    });
}

// ── Form validation ───────────────────────────
document.getElementById('catForm').addEventListener('submit', function(e) {
    let valid = true;
    document.querySelectorAll('.error-text').forEach(el => { el.textContent=''; el.style.display='none'; });
    document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));

    function err(inputId, errId, msg) {
        const inp = document.getElementById(inputId);
        const erEl = document.getElementById(errId);
        if (inp)  inp.classList.add('is-invalid');
        if (erEl) { erEl.textContent = msg; erEl.style.display = 'block'; }
        valid = false;
    }

    const nom = document.getElementById('nom').value.trim();
    if (!nom || nom.length < 2) err('nom','nom-error','Le nom doit contenir au moins 2 caractères.');
    else if (nom.length > 100)  err('nom','nom-error','Le nom ne peut pas dépasser 100 caractères.');

    if (!valid) {
        e.preventDefault();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    } else {
        document.getElementById('submitBtn').innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enregistrement…';
        document.getElementById('submitBtn').disabled = true;
    }
});

// init slug preview and image preview
updateSlugPreview(document.getElementById('nom').value);
previewImage(document.getElementById('image').value);
</script>
</body>
</html>
