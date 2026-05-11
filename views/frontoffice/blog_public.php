<?php
declare(strict_types=1);
$blogUserLabel = '';
if (!empty($_SESSION['user_name'])) {
    $blogUserLabel = trim((string)$_SESSION['user_name']);
}
$navActive = 'blog_public';
$navVariant = 'doctime';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blog — DocTime · Valorys</title>
    <link href="assets/vendor/bootstrap/5.3.0/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <?php include __DIR__ . '/../partials/public_theme_styles.php'; ?>
    <style>
        /* Blog public — aligné DocTime (teal, cartes slate, même esprit que événements / back-office) */
        body.blog-feed-page.page-doctime-bg {
            font-family: 'Segoe UI', system-ui, sans-serif;
            min-height: 100vh;
        }

        .feed-outer { padding: 1.25rem 0.75rem 3.5rem; }
        .feed-inner { max-width: 720px; margin: 0 auto; }

        .blog-dt-hero {
            background: linear-gradient(135deg, rgba(27, 154, 132, 0.12) 0%, rgba(15, 23, 42, 0.04) 100%);
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 1.35rem 1.5rem;
            margin-bottom: 1.25rem;
        }
        .blog-dt-hero .dt-page-head-title { margin-bottom: 0.35rem; }
        .blog-dt-hero .dt-page-head-sub { margin-bottom: 0; max-width: 42rem; }

        .toolbar-card, .composer-card, .post-card, .filter-card {
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 4px 18px rgba(15, 23, 42, 0.08);
            border: 1px solid #e2e8f0;
            padding: 1rem 1.15rem;
            margin-bottom: 1rem;
            transition: box-shadow 0.2s ease;
        }
        .toolbar-card:hover, .composer-card:hover { box-shadow: 0 8px 26px rgba(15, 23, 42, 0.1); }

        .search-row { display: flex; gap: 0.65rem; flex-wrap: wrap; align-items: center; }
        .search-row .icon-wrap { color: #64748b; }
        .search-row input[type="search"] {
            flex: 1;
            min-width: 200px;
            border: 1px solid #cbd5e1;
            background: #fff;
            border-radius: 12px;
            padding: 0.65rem 1rem;
            font-size: 0.9375rem;
            outline: none;
        }
        .search-row input[type="search"]:focus {
            border-color: var(--dt-teal);
            box-shadow: 0 0 0 0.2rem rgba(27, 154, 132, 0.2);
        }
        .btn-search-blue {
            background: var(--dt-teal);
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: 0.65rem 1.15rem;
            font-weight: 600;
            font-size: 0.9rem;
            transition: filter 0.15s, transform 0.15s;
        }
        .btn-search-blue:hover { filter: brightness(1.06); color: #fff; transform: translateY(-1px); }
        .sort-select {
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            padding: 0.5rem 0.75rem;
            font-size: 0.875rem;
            background: #fff;
            color: #334155;
            max-width: 240px;
        }
        .sort-select:focus {
            border-color: var(--dt-teal);
            outline: none;
            box-shadow: 0 0 0 0.15rem rgba(27, 154, 132, 0.2);
        }

        .filter-toggle {
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
            font-weight: 600;
            color: #0f172a;
            user-select: none;
            font-size: 0.9375rem;
        }
        .filter-toggle .fa-sliders-h { color: var(--dt-teal); }
        .filter-panel { display: none; margin-top: 12px; padding-top: 12px; border-top: 1px solid #e2e8f0; }
        .filter-panel.open { display: block; }

        .section-articles-title {
            font-size: 1.15rem;
            font-weight: 700;
            color: #0f172a;
            margin: 1.5rem 0 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .section-articles-title i { color: var(--dt-teal); }

        .composer-card { padding: 1rem 1.1rem; }
        .composer-head { display: flex; gap: 0.75rem; align-items: center; }
        .avatar-circle {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--dt-teal) 0%, #15806e 100%);
            color: #fff;
            font-weight: 700;
            font-size: 1.05rem;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(27, 154, 132, 0.35);
        }
        .composer-fake-input {
            flex: 1;
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 0.75rem 1rem;
            color: #64748b;
            cursor: pointer;
            font-size: 0.9375rem;
            transition: border-color 0.15s, background 0.15s;
        }
        .composer-fake-input:hover {
            border-color: rgba(27, 154, 132, 0.45);
            background: #fff;
        }
        .composer-actions {
            display: flex;
            gap: 0.35rem;
            margin-top: 0.75rem;
            padding-top: 0.65rem;
            border-top: 1px solid #e2e8f0;
            flex-wrap: wrap;
        }
        .composer-actions button {
            flex: 1;
            min-width: 90px;
            border: none;
            background: transparent;
            border-radius: 10px;
            padding: 0.5rem 0.65rem;
            font-weight: 600;
            font-size: 0.875rem;
            color: #334155;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        .composer-actions button:hover { background: #f1f5f9; }
        .composer-actions .i-photo { color: var(--dt-teal); }
        .composer-actions .i-emoji { color: #f59e0b; }
        .composer-actions .i-article { color: #dc2626; }

        .post-card { padding: 0; overflow: hidden; }
        .post-card:hover { box-shadow: 0 8px 28px rgba(15, 23, 42, 0.12); }
        .post-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 10px;
            padding: 1rem 1.15rem 0;
        }
        .post-author-block { display: flex; gap: 10px; min-width: 0; }
        .post-author-text { min-width: 0; }
        .post-author-name { font-weight: 700; font-size: 0.95rem; color: #0f172a; line-height: 1.25; }
        .post-meta-line { font-size: 0.8125rem; color: #64748b; margin-top: 2px; display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
        .post-actions-top { display: flex; gap: 8px; flex-shrink: 0; flex-wrap: wrap; justify-content: flex-end; }
        .btn-modifier {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #fcd34d;
            border-radius: 999px;
            padding: 6px 14px;
            font-size: 0.8125rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-modifier:hover { filter: brightness(0.98); color: #92400e; }
        .btn-supprimer {
            background: #fef2f2;
            color: #b91c1c;
            border: 1px solid #fecaca;
            border-radius: 999px;
            padding: 6px 14px;
            font-size: 0.8125rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-supprimer:hover { background: #fee2e2; color: #b91c1c; }

        .post-body { padding: 0.65rem 1.15rem 1rem; }
        .post-title-link {
            font-size: 1.15rem;
            font-weight: 700;
            color: #0f172a;
            text-decoration: none;
            display: inline-block;
            margin-bottom: 0.5rem;
            line-height: 1.3;
        }
        .post-title-link:hover { color: var(--dt-teal); }
        .post-excerpt {
            color: #475569;
            font-size: 0.9375rem;
            line-height: 1.55;
            white-space: pre-wrap;
            word-break: break-word;
        }

        .post-footer-bar {
            border-top: 1px solid #e2e8f0;
            padding: 0.65rem 1.15rem;
            background: #fafbfc;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }
        .post-reactions { display: flex; align-items: center; gap: 10px; }
        .vote-btn {
            border: none;
            background: transparent;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.875rem;
            color: #64748b;
            font-weight: 600;
            padding: 6px 10px;
            border-radius: 10px;
            cursor: pointer;
            transition: background 0.15s, color 0.15s;
        }
        .vote-btn:hover { background: #f1f5f9; }
        .vote-btn.active-like { color: #e11d48; }
        .vote-btn.active-dis { color: #475569; }
        .post-stats { display: flex; align-items: center; gap: 16px; color: #64748b; font-size: 0.875rem; }

        .banner-success {
            background: linear-gradient(90deg, #ecfdf5 0%, #d1fae5 100%);
            color: #065f46;
            border-bottom: 1px solid #a7f3d0;
            padding: 0.75rem 1.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.9375rem;
        }
        .banner-success .close-banner { background: none; border: none; font-size: 1.25rem; cursor: pointer; opacity: 0.65; color: inherit; }

        .login-banner-soft {
            border-radius: 14px;
            padding: 1rem 1.15rem;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            border: 1px solid #bfdbfe;
            background: #eff6ff;
            color: #1e40af;
        }
        .login-banner-soft a { font-weight: 600; color: var(--dt-teal-dark); }

        .overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.55);
            z-index: 1040;
            align-items: center;
            justify-content: center;
            padding: 16px;
            backdrop-filter: blur(4px);
        }
        .overlay.open { display: flex; }
        .modal-create {
            background: #fff;
            border-radius: 16px;
            width: 100%;
            max-width: 540px;
            max-height: 92vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            box-shadow: 0 24px 64px rgba(15, 23, 42, 0.28);
            border: 1px solid #e2e8f0;
        }
        .modal-create-head {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid #e2e8f0;
            text-align: center;
            position: relative;
            background: linear-gradient(180deg, #f8fafc 0%, #fff 100%);
        }
        .modal-create-head h5 { margin: 0; font-size: 1.05rem; font-weight: 700; color: #0f172a; }
        .modal-create-head .x {
            position: absolute;
            right: 14px;
            top: 12px;
            background: #f1f5f9;
            border: none;
            width: 36px;
            height: 36px;
            border-radius: 10px;
            font-size: 1.25rem;
            color: #64748b;
            cursor: pointer;
            line-height: 1;
            transition: background 0.15s;
        }
        .modal-create-head .x:hover { background: #e2e8f0; color: #0f172a; }
        .modal-create-body { padding: 1rem 1.25rem 1.25rem; overflow-y: auto; flex: 1; }
        .modal-create-user { display: flex; align-items: center; gap: 10px; margin-bottom: 14px; }
        .modal-create-user .name { font-weight: 700; font-size: 0.95rem; color: #0f172a; }
        .field-min input, .field-min textarea {
            width: 100%;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            padding: 0.65rem 0.85rem;
            font-size: 0.9375rem;
            margin-bottom: 12px;
            outline: none;
            transition: border-color 0.15s, box-shadow 0.15s;
        }
        .field-min input:focus, .field-min textarea:focus {
            border-color: var(--dt-teal);
            box-shadow: 0 0 0 0.2rem rgba(27, 154, 132, 0.15);
        }
        .field-min textarea { min-height: 160px; resize: vertical; font-family: inherit; }
        .add-pub-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 0;
            margin-top: 6px;
            border-top: 1px solid #e2e8f0;
            font-weight: 600;
            font-size: 0.8125rem;
            color: #64748b;
        }
        .add-pub-icons { display: flex; gap: 14px; font-size: 1.35rem; }
        .btn-publish-main {
            width: 100%;
            margin-top: 12px;
            border: none;
            border-radius: 12px;
            padding: 0.85rem;
            font-size: 1rem;
            font-weight: 700;
            background: #e2e8f0;
            color: #94a3b8;
            cursor: not-allowed;
            transition: opacity 0.15s, transform 0.15s;
        }
        .btn-publish-main.enabled {
            background: linear-gradient(135deg, var(--dt-teal) 0%, #15806e 100%);
            color: #fff;
            cursor: pointer;
            box-shadow: 0 4px 14px rgba(27, 154, 132, 0.35);
        }
        .btn-publish-main.enabled:hover { opacity: 0.95; transform: translateY(-1px); }

        .toast-floating {
            position: fixed;
            bottom: 22px;
            right: 22px;
            z-index: 9999;
            background: #0f172a;
            color: #fff;
            padding: 12px 20px;
            border-radius: 12px;
            font-size: 0.875rem;
            display: none;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.35);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
        .toast-floating.show { display: block; animation: t-in 0.22s ease; }
        @keyframes t-in { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: none; } }

        .gami-toast {
            position: fixed;
            bottom: 22px;
            right: 22px;
            z-index: 20000;
            background: linear-gradient(155deg, #0f172a 0%, #1e293b 100%);
            color: #fff;
            border-radius: 16px;
            padding: 16px 40px 16px 16px;
            box-shadow: 0 14px 48px rgba(0, 0, 0, 0.35);
            max-width: 380px;
            display: none;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .gami-toast.show { display: block; animation: t-in 0.28s ease; }
        .gami-toast-inner { display: flex; gap: 12px; align-items: flex-start; }
        .gami-toast-ico { font-size: 1.6rem; line-height: 1; flex-shrink: 0; }
        .gami-toast-title { color: #fde68a; font-weight: 800; font-size: 15px; line-height: 1.35; }
        .gami-toast-sub { color: #e2e8f0; font-size: 13px; margin-top: 8px; line-height: 1.45; }
        .gami-toast-x {
            position: absolute;
            top: 10px;
            right: 12px;
            background: none;
            border: none;
            color: #94a3b8;
            font-size: 1.35rem;
            cursor: pointer;
            line-height: 1;
            padding: 4px;
        }
        .gami-toast-x:hover { color: #fff; }

        .blog-dt-loading .spinner-border {
            width: 2.5rem;
            height: 2.5rem;
            border-width: 0.2rem;
            color: var(--dt-teal);
        }

        footer.site-ft {
            background: linear-gradient(180deg, #0f172a 0%, #1e293b 100%);
            color: #e2e8f0;
            text-align: center;
            padding: 2rem 1rem;
            margin-top: 2rem;
            border-top: 3px solid var(--dt-teal);
        }
        footer.site-ft small { color: #94a3b8; }
    </style>
</head>
<body class="blog-feed-page page-doctime-bg">
<?php include __DIR__ . '/../partials/nav_public.php'; ?>

<?php if (!empty($_SESSION['success'])): ?>
<div class="banner-success" id="topSuccessBanner">
    <span><i class="fas fa-check-circle me-2"></i><?= htmlspecialchars((string)$_SESSION['success'], ENT_QUOTES, 'UTF-8') ?></span>
    <button type="button" class="close-banner" onclick="document.getElementById('topSuccessBanner').remove()" aria-label="Fermer">&times;</button>
</div>
<?php unset($_SESSION['success']); endif; ?>

<div class="feed-outer">
    <div class="feed-inner">
        <header class="blog-dt-hero">
            <h1 class="dt-page-head-title">Blog &amp; actualités</h1>
            <p class="dt-page-head-sub">Découvrez les publications de la communauté DocTime · Valorys : santé, conseils et échanges.</p>
        </header>

        <div class="toolbar-card">
            <div class="search-row mb-2">
                <span class="icon-wrap"><i class="fas fa-search"></i></span>
                <input type="search" id="searchInput" placeholder="Rechercher par titre, auteur ou contenu..." autocomplete="off">
                <button type="button" class="btn-search-blue" id="btnSearch">Rechercher</button>
                <select id="sortSelect" class="sort-select" title="Tri">
                    <option value="desc">↕ Plus récent d'abord</option>
                    <option value="asc">↕ Plus ancien d'abord</option>
                </select>
            </div>
            <div class="filter-card border-0 shadow-none p-0 m-0" style="background:transparent;">
                <div class="filter-toggle" id="filterToggle" role="button" tabindex="0">
                    <span><i class="fas fa-sliders-h me-2 text-secondary"></i>Filtres avancés</span>
                    <i class="fas fa-chevron-down" id="filterChevron"></i>
                </div>
                <div class="filter-panel" id="filterPanel">
                    <p class="small text-muted mb-2">Affinez la liste (même critère que la recherche ci-dessus).</p>
                    <input type="text" class="form-control form-control-sm" id="filterAuteur" placeholder="Filtrer par mot dans auteur…">
                </div>
            </div>
        </div>

        <?php if (empty($_SESSION['user_id'])): ?>
        <div class="login-banner-soft">
            <i class="fas fa-sign-in-alt me-2" style="color:var(--dt-teal);"></i><a href="index.php?page=login">Connectez-vous</a> pour publier, voter ou gérer vos articles.
        </div>
        <?php endif; ?>

        <h2 class="section-articles-title"><i class="fas fa-newspaper"></i> Articles (<span id="feedCount">0</span>)</h2>

        <?php if (isset($_SESSION['user_id'])): ?>
        <div class="composer-card">
            <div class="composer-head">
                <div class="avatar-circle" id="composerAvatar"><?= strtoupper(substr($blogUserLabel ?: 'U', 0, 1)) ?></div>
                <div class="composer-fake-input" id="openCreateModal"><?php
                    $greet = $blogUserLabel !== '' ? $blogUserLabel : 'vous';
                    echo 'Quoi de neuf, ' . htmlspecialchars($greet, ENT_QUOTES, 'UTF-8') . ' ?';
                ?></div>
            </div>
            <div class="composer-actions">
                <button type="button" id="btnSoonPhoto"><i class="fas fa-image i-photo"></i>Photo</button>
                <button type="button" id="btnSoonEmoji"><i class="far fa-smile i-emoji"></i>Emoji</button>
                <button type="button" id="btnOpenArticleModal"><i class="fas fa-pen i-article"></i>Article</button>
            </div>
        </div>
        <?php endif; ?>

        <div id="articlesList">
            <div class="text-center py-5 text-muted blog-dt-loading"><div class="spinner-border" role="status" aria-label="Chargement"></div><p class="mt-3 mb-0 small">Chargement des articles…</p></div>
        </div>
    </div>
</div>

<!-- Créer une publication -->
<div class="overlay" id="ovCreate">
    <div class="modal-create">
        <div class="modal-create-head">
            <button type="button" class="x" id="closeCreate" aria-label="Fermer">&times;</button>
            <h5>Créer une publication</h5>
        </div>
        <div class="modal-create-body">
            <div class="modal-create-user">
                <div class="avatar-circle"><?= strtoupper(substr($blogUserLabel ?: 'U', 0, 1)) ?></div>
                <span class="name"><?= htmlspecialchars($blogUserLabel ?: 'Utilisateur', ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <div class="field-min">
                <input type="text" id="newArtTitreModal" placeholder="Titre de l'article..." maxlength="255">
                <textarea id="newArtContenuModal" placeholder="Écrivez votre article..."></textarea>
            </div>
            <div class="add-pub-bar">
                <span>Ajouter à votre publication</span>
                <div class="add-pub-icons">
                    <i class="fas fa-image text-success" title="Photo" style="cursor:pointer" id="btnSoonPhoto2"></i>
                    <i class="far fa-smile text-warning" title="Emoji" style="cursor:pointer" id="btnSoonEmoji2"></i>
                </div>
            </div>
            <button type="button" class="btn-publish-main" id="btnPublishModal" disabled>Publier</button>
        </div>
    </div>
</div>

<!-- Modifier article -->
<div class="overlay" id="ovEditArt">
    <div class="modal-create">
        <div class="modal-create-head">
            <button type="button" class="x" onclick="closeOv('ovEditArt')">&times;</button>
            <h5 id="ovEditArtTitle">Modifier l'article</h5>
        </div>
        <div class="modal-create-body">
            <div class="field-min">
                <input type="text" id="editArtTitre" placeholder="Titre">
                <textarea id="editArtContenu" rows="8" placeholder="Contenu"></textarea>
                <div class="text-danger small" id="eEditArtTitre"></div>
                <div class="text-danger small" id="eEditArtContenu"></div>
            </div>
            <button type="button" class="btn-publish-main enabled mt-2" onclick="saveEditArt()">Enregistrer</button>
        </div>
    </div>
</div>

<!-- Suppression -->
<div class="overlay" id="ovDel">
    <div class="modal-create" style="max-width:400px;">
        <div class="modal-create-body text-center">
            <div style="font-size:2.5rem;">🗑️</div>
            <h5 class="mt-2" id="delTitle">Supprimer ?</h5>
            <p class="text-muted small" id="delSub">Action irréversible.</p>
            <div class="d-flex gap-2 justify-content-center mt-3">
                <button type="button" class="btn btn-secondary rounded-pill" onclick="closeOv('ovDel')">Annuler</button>
                <button type="button" class="btn btn-danger rounded-pill" id="delBtn">Supprimer</button>
            </div>
        </div>
    </div>
</div>

<div class="toast-floating" id="toast"></div>

<footer class="site-ft">
    <div class="container">
        <p class="mb-0">© 2026 Valorys — Tous droits réservés</p>
        <small>Plateforme médicale en ligne</small>
    </div>
</footer>

<script>
const IS_LOGGED = <?= isset($_SESSION['user_id']) ? 'true' : 'false' ?>;
const SESSION_ID = <?= isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 'null' ?>;
const API_ART_LIKE = 'index.php?page=api_article_like';

const $ = id => document.getElementById(id);
function esc(s) {
    if (s == null) return '';
    const d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
}
function trunc(s, n = 220) {
    if (!s) return '';
    const t = s.replace(/\s+/g, ' ').trim();
    return t.length > n ? t.slice(0, n) + '…' : t;
}
function fmtDT(d) {
    if (!d) return '—';
    try { return new Date(d).toLocaleString('fr-FR', { day:'2-digit', month:'2-digit', year:'numeric', hour:'2-digit', minute:'2-digit' }); } catch(e) { return '—'; }
}
function toast(msg) {
    const t = $('toast');
    t.textContent = msg;
    t.classList.add('show');
    clearTimeout(t._x);
    t._x = setTimeout(() => t.classList.remove('show'), 3200);
}

/** Bandeau vert en tête (comme après publication côté serveur). */
function showTopSuccessBanner(msg) {
    const old = document.getElementById('topSuccessBanner');
    if (old) old.remove();
    const div = document.createElement('div');
    div.id = 'topSuccessBanner';
    div.className = 'banner-success';
    div.innerHTML = '<span><i class="fas fa-check-circle me-2"></i>' + esc(msg) + '</span>';
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'close-banner';
    btn.setAttribute('aria-label', 'Fermer');
    btn.innerHTML = '&times;';
    btn.onclick = () => div.remove();
    div.appendChild(btn);
    const feed = document.querySelector('.feed-outer');
    if (feed && feed.parentNode) {
        feed.parentNode.insertBefore(div, feed);
    } else {
        document.body.prepend(div);
    }
}

function hideGamiToast() {
    const t = document.getElementById('gamiToast');
    if (!t) return;
    t.classList.remove('show');
    clearTimeout(window._gamiToastHide);
    window._gamiToastHide = setTimeout(() => { if (t.parentNode) t.remove(); }, 280);
}

/** Toast points + récompense (bas-droite). */
function showGamificationToast(g) {
    if (!g) return;
    const pts = Number(g.points_added || 0);
    const tot = Number(g.total_points || 0);
    const rewards = g.new_rewards || [];
    if (pts <= 0 && tot <= 0 && !rewards.length) return;
    hideGamiToast();
    const wrap = document.createElement('div');
    wrap.id = 'gamiToast';
    wrap.className = 'gami-toast';
    const titleText = (pts > 0 ? ('+' + pts + ' points gagnés 🎯') : 'Points mis à jour 🎯') +
        ' (' + tot + ' pts au total)';
    let inner = '<button type="button" class="gami-toast-x" aria-label="Fermer">&times;</button>';
    inner += '<div class="gami-toast-inner"><span class="gami-toast-ico">🎯</span><div class="gami-toast-body-wrap">';
    inner += '<div class="gami-toast-title"></div><div class="gami-toast-sub"></div></div></div>';
    wrap.innerHTML = inner;
    wrap.querySelector('.gami-toast-title').textContent = titleText;
    const subEl = wrap.querySelector('.gami-toast-sub');
    if (rewards.length) {
        const r = rewards[0];
        subEl.textContent = '🎈 Nouvelle récompense : ' + (r.title || '') +
            ' ! Un certificat vous a été envoyé par email 🎓';
    } else {
        subEl.textContent = '';
        subEl.style.display = 'none';
    }
    document.body.appendChild(wrap);
    wrap.querySelector('.gami-toast-x').onclick = () => hideGamiToast();
    requestAnimationFrame(() => wrap.classList.add('show'));
    clearTimeout(window._gamiToastAuto);
    window._gamiToastAuto = setTimeout(hideGamiToast, 10000);
}
function closeOv(id) { $(id).classList.remove('open'); }
function openOv(id) { $(id).classList.add('open'); }
document.querySelectorAll('.overlay').forEach(o => o.addEventListener('click', e => { if (e.target === o) o.classList.remove('open'); }));

async function apiGet(params) {
    const r = await fetch('index.php?' + new URLSearchParams(params), {
        headers: { Accept: 'application/json' },
        credentials: 'same-origin',
    });
    const text = await r.text();
    try {
        return text ? JSON.parse(text) : {};
    } catch (e) {
        return { success: false, message: 'Réponse invalide du serveur.' };
    }
}
async function apiPostJson(url, body) {
    const r = await fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
        body: JSON.stringify(body),
    });
    const text = await r.text();
    try {
        return text ? JSON.parse(text) : { success: false, message: r.ok ? 'Réponse vide' : ('Erreur ' + r.status) };
    } catch (e) {
        return {
            success: false,
            message: r.ok ? 'Réponse non JSON du serveur.' : ('Erreur serveur (' + r.status + ').'),
        };
    }
}

let articles = [];
let editArtId = null;

function sortArticles(arr) {
    const o = $('sortSelect').value;
    const cp = [...arr];
    cp.sort((a, b) => {
        const da = new Date(a.created_at || 0).getTime();
        const db = new Date(b.created_at || 0).getTime();
        return o === 'asc' ? da - db : db - da;
    });
    return cp;
}

function applyFilters(list) {
    const q = $('searchInput').value.toLowerCase().trim();
    const fa = ($('filterAuteur').value || '').toLowerCase().trim();
    return list.filter(a => {
        const matchQ = !q ||
            (a.titre || '').toLowerCase().includes(q) ||
            (a.contenu || '').toLowerCase().includes(q) ||
            (a.auteur_display || a.auteur || '').toLowerCase().includes(q);
        const matchA = !fa || (a.auteur_display || a.auteur || '').toLowerCase().includes(fa);
        return matchQ && matchA;
    });
}

function renderArticleList(list) {
    $('feedCount').textContent = String(list.length);
    if (!list.length) {
        $('articlesList').innerHTML = '<div class="post-card p-4 text-center text-muted border-dashed" style="border-style:dashed;"><i class="fas fa-inbox fa-2x mb-2 d-block opacity-50"></i>Aucun article à afficher.</div>';
        return;
    }
    $('articlesList').innerHTML = list.map(a => {
        const aid = a.id;
        const authorShow = esc(a.auteur_display || a.auteur || 'Valorys');
        const initial = (authorShow || 'V').charAt(0).toUpperCase();
        const isOwner = IS_LOGGED && SESSION_ID != null && Number(a.auteur_id) === SESSION_ID;
        const modBtns = isOwner ? `
            <div class="post-actions-top">
                <button type="button" class="btn-modifier" onclick="event.stopPropagation();openEditArtFromList(${aid})"><i class="fas fa-pencil-alt"></i> Modifier</button>
                <button type="button" class="btn-supprimer" onclick="event.stopPropagation();confirmDelArtFromList(${aid}, ${JSON.stringify(a.titre)})"><i class="fas fa-trash"></i> Supprimer</button>
            </div>` : '';
        const lk = Number(a.nb_likes || 0);
        const dk = Number(a.nb_dislikes || 0);
        const myv = a.my_vote || null;
        const likeCls = myv === 'like' ? ' vote-btn active-like' : ' vote-btn';
        const disCls = myv === 'dislike' ? ' vote-btn active-dis' : ' vote-btn';
        return `
        <article class="post-card" data-id="${aid}">
            <div class="post-head">
                <div class="post-author-block">
                    <div class="avatar-circle" style="width:40px;height:40px;font-size:1rem;">${initial}</div>
                    <div class="post-author-text">
                        <div class="post-author-name">${authorShow}</div>
                        <div class="post-meta-line">
                            <span>${fmtDT(a.created_at)}</span>
                            <i class="fas fa-globe-americas" title="Public"></i>
                        </div>
                    </div>
                </div>
                ${modBtns}
            </div>
            <div class="post-body">
                <a class="post-title-link" href="index.php?page=detail_article_public&id=${aid}">${esc(a.titre)}</a>
                <div class="post-excerpt">${esc(trunc(a.contenu || '', 380))}</div>
            </div>
            <div class="post-footer-bar">
                <div class="post-reactions">
                    <button type="button" class="${likeCls}" data-vote="like" data-aid="${aid}" ${IS_LOGGED ? '' : ' title="Connectez-vous pour voter"'}>
                        <i class="fas fa-heart"></i><span class="n-lk">${lk}</span>
                    </button>
                    <button type="button" class="${disCls}" data-vote="dislike" data-aid="${aid}" ${IS_LOGGED ? '' : ' title="Connectez-vous pour voter"'}>
                        <i class="fas fa-thumbs-down"></i><span class="n-dk">${dk}</span>
                    </button>
                </div>
                <div class="post-stats">
                    <span title="Vues"><i class="fas fa-eye me-1"></i>${Number(a.vues || 0)}</span>
                    <span title="Commentaires"><i class="fas fa-comment me-1"></i>${Number(a.nb_replies || 0)}</span>
                </div>
            </div>
        </article>`;
    }).join('');

    $('articlesList').querySelectorAll('[data-vote]').forEach(btn => {
        btn.addEventListener('click', () => voteArticle(Number(btn.getAttribute('data-aid')), btn.getAttribute('data-vote')));
    });
}

async function voteArticle(articleId, type) {
    if (!IS_LOGGED) { toast('Connectez-vous pour voter.'); return; }
    const r = await apiPostJson(API_ART_LIKE, { article_id: articleId, type });
    if (!r.success) { toast(r.message || 'Erreur'); return; }
    const card = document.querySelector('.post-card[data-id="' + articleId + '"]');
    if (card) {
        const lk = card.querySelector('.n-lk');
        const dk = card.querySelector('.n-dk');
        if (lk) lk.textContent = r.likes;
        if (dk) dk.textContent = r.dislikes;
        const bl = card.querySelector('[data-vote="like"]');
        const bd = card.querySelector('[data-vote="dislike"]');
        bl.classList.toggle('active-like', r.my_vote === 'like');
        bd.classList.toggle('active-dis', r.my_vote === 'dislike');
    }
    const ref = articles.find(x => Number(x.id) === articleId);
    if (ref) { ref.nb_likes = r.likes; ref.nb_dislikes = r.dislikes; ref.my_vote = r.my_vote; }
}

async function loadArticles() {
    const r = await apiGet({ page: 'api_article', list: 1 });
    if (!r.success) {
        $('articlesList').innerHTML = '<div class="alert alert-danger">Impossible de charger les articles.</div>';
        return;
    }
    articles = r.articles || [];
    const sorted = sortArticles(applyFilters(articles));
    renderArticleList(sorted);
}

function refreshDisplayed() {
    const sorted = sortArticles(applyFilters(articles));
    renderArticleList(sorted);
}

$('btnSearch').addEventListener('click', refreshDisplayed);
$('searchInput').addEventListener('keydown', e => { if (e.key === 'Enter') refreshDisplayed(); });
$('sortSelect').addEventListener('change', refreshDisplayed);
$('filterAuteur').addEventListener('input', refreshDisplayed);

const ft = $('filterToggle');
const fp = $('filterPanel');
const ch = $('filterChevron');
ft.addEventListener('click', () => {
    fp.classList.toggle('open');
    ch.classList.toggle('fa-chevron-down');
    ch.classList.toggle('fa-chevron-up');
});
ft.addEventListener('keydown', e => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); ft.click(); }});

/* Modal création */
function openCreate() { if (!IS_LOGGED) return; openOv('ovCreate'); }
$('openCreateModal')?.addEventListener('click', openCreate);
$('btnOpenArticleModal')?.addEventListener('click', openCreate);
$('closeCreate')?.addEventListener('click', () => closeOv('ovCreate'));

function validatePublishBtn() {
    const btn = $('btnPublishModal');
    const t = ($('newArtTitreModal').value || '').trim();
    const c = ($('newArtContenuModal').value || '').trim();
    if (t.length && c.length >= 10) {
        btn.disabled = false;
        btn.classList.add('enabled');
    } else {
        btn.disabled = true;
        btn.classList.remove('enabled');
    }
}
$('newArtTitreModal')?.addEventListener('input', validatePublishBtn);
$('newArtContenuModal')?.addEventListener('input', validatePublishBtn);

$('btnPublishModal')?.addEventListener('click', async () => {
    if (!IS_LOGGED) return;
    const titre = ($('newArtTitreModal').value || '').trim();
    const contenu = ($('newArtContenuModal').value || '').trim();
    const r = await apiPostJson('index.php?page=api_article', { titre, contenu });
    if (!r.success) {
        toast((r.errors && (r.errors.titre || r.errors.contenu)) ? (r.errors.titre || r.errors.contenu) : (r.message || 'Erreur'));
        return;
    }
    showTopSuccessBanner(r.message || 'Article publié avec succès !');
    /* gamification présent dès que l’API a comptabilisé les points */
    if (r.gamification && typeof r.gamification === 'object') {
        showGamificationToast(r.gamification);
    }
    $('newArtTitreModal').value = '';
    $('newArtContenuModal').value = '';
    validatePublishBtn();
    closeOv('ovCreate');
    loadArticles();
});

['btnSoonPhoto','btnSoonEmoji','btnSoonPhoto2','btnSoonEmoji2'].forEach(id => {
    const el = $(id);
    if (el) el.addEventListener('click', () => toast('Fonction à venir — utilisez le texte pour décrire votre média.'));
});

/* Édition / suppression */
async function openEditArtFromList(id) {
    if (!IS_LOGGED) return;
    const r = await apiGet({ page: 'api_article', id });
    if (!r.success || !r.article) { toast('Article introuvable'); return; }
    editArtId = id;
    $('editArtTitre').value = r.article.titre;
    $('editArtContenu').value = r.article.contenu;
    $('eEditArtTitre').textContent = '';
    $('eEditArtContenu').textContent = '';
    openOv('ovEditArt');
}

async function saveEditArt() {
    if (!IS_LOGGED || !editArtId) return;
    const titre = ($('editArtTitre').value || '').trim();
    const contenu = ($('editArtContenu').value || '').trim();
    $('eEditArtTitre').textContent = !titre ? 'Titre requis' : '';
    $('eEditArtContenu').textContent = !contenu ? 'Contenu requis' : '';
    if (!titre || !contenu) return;
    const r = await apiPostJson('index.php?page=api_article&id=' + encodeURIComponent(editArtId), { titre, contenu, _method: 'PUT' });
    if (!r.success) { toast(r.message || 'Erreur'); return; }
    closeOv('ovEditArt');
    toast('Article modifié !');
    loadArticles();
}

function confirmDelArtFromList(id, titre) {
    if (!IS_LOGGED) return;
    $('delTitle').textContent = 'Supprimer « ' + String(titre).slice(0, 60) + ' » ?';
    $('delSub').textContent = 'Les commentaires seront aussi supprimés.';
    $('delBtn').onclick = async () => {
        const r = await apiPostJson('index.php?page=api_article&id=' + encodeURIComponent(id), { _method: 'DELETE' });
        closeOv('ovDel');
        toast(r.success ? 'Article supprimé.' : (r.message || 'Erreur'));
        if (r.success) loadArticles();
    };
    openOv('ovDel');
}

loadArticles();
</script>
<script src="assets/vendor/bootstrap/5.3.0/bootstrap.bundle.min.js"></script>
</body>
</html>
