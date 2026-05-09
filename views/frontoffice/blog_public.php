<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blog Valorys</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f0f5fb; font-family: 'Segoe UI', sans-serif; }

        /* ── NAVBAR ── */
        nav.navbar { background: #1a2035 !important; }

        /* ── HEADER ── */
        .blog-header {
            background: linear-gradient(135deg, #2A7FAA 0%, #4CAF50 100%);
            color: #fff; padding: 55px 0; text-align: center; margin-bottom: 36px;
        }
        .blog-header h1 { font-size: 2.2rem; font-weight: 700; }
        .blog-header p  { opacity: .9; }

        /* ── ARTICLE CARD ── */
        .art-card {
            background: #fff; border-radius: 16px; padding: 26px 28px;
            margin-bottom: 22px; box-shadow: 0 4px 18px rgba(0,0,0,.07);
            border-left: 4px solid #2A7FAA; position: relative;
            transition: transform .25s, box-shadow .25s;
        }
        .art-card:hover { transform: translateY(-3px); box-shadow: 0 10px 30px rgba(0,0,0,.11); }

        /* Boutons CRUD flottants en haut à droite de la card */
        .art-crud {
            position: absolute; top: 16px; right: 16px;
            display: flex; gap: 6px; opacity: 0; transition: opacity .2s;
        }
        .art-card:hover .art-crud { opacity: 1; }

        .art-title   { font-size: 1.2rem; font-weight: 700; color: #1a2035; margin-bottom: 8px; padding-right: 90px; }
        .art-title a { color: #1a2035; text-decoration: none; }
        .art-title a:hover { color: #2A7FAA; }
        .art-meta    { font-size: 12px; color: #6c757d; display: flex; gap: 16px; flex-wrap: wrap; margin-bottom: 12px; }
        .art-meta i  { color: #2A7FAA; }
        .art-excerpt { color: #555; line-height: 1.7; font-size: 14px; margin-bottom: 16px; }
        .art-footer  { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; }

        .btn-read { background: #2A7FAA; color: #fff; border: none; border-radius: 25px; padding: 7px 18px; font-size: 13px; cursor: pointer; text-decoration: none; display: inline-block; transition: background .2s; }
        .btn-read:hover { background: #1e5f80; color: #fff; }

        .btn-edit { background: #e3f2fd; color: #1565c0; border: none; border-radius: 20px; padding: 5px 11px; font-size: 12px; cursor: pointer; font-weight: 600; white-space: nowrap; transition: .2s; }
        .btn-edit:hover { background: #1565c0; color: #fff; }
        .btn-del  { background: #fce4ec; color: #c62828; border: none; border-radius: 20px; padding: 5px 11px; font-size: 12px; cursor: pointer; font-weight: 600; white-space: nowrap; transition: .2s; }
        .btn-del:hover  { background: #c62828; color: #fff; }

        /* ── DETAIL ── */
        .detail-header { background: linear-gradient(135deg,#2A7FAA 0%,#4CAF50 100%); color:#fff; padding:48px 0; margin-bottom:32px; }
        .detail-header h1 { font-size:1.9rem; font-weight:700; margin-bottom:10px; }
        .detail-meta { font-size:13px; opacity:.85; display:flex; gap:20px; flex-wrap:wrap; }
        .back-btn { display:inline-flex; align-items:center; gap:6px; background:rgba(255,255,255,.2); color:#fff; border:none; border-radius:25px; padding:8px 18px; font-size:13px; cursor:pointer; margin-bottom:14px; transition:.2s; }
        .back-btn:hover { background:rgba(255,255,255,.35); }
        .article-body-box { background:#fff; border-radius:16px; padding:32px; box-shadow:0 4px 16px rgba(0,0,0,.07); margin-bottom:26px; line-height:1.85; color:#333; font-size:15px; }

        /* Actions article dans le détail */
        .art-detail-actions { display:flex; gap:8px; margin-bottom:18px; flex-wrap:wrap; }

        /* ── COMMENTAIRES ── */
        .comments-box { background:#fff; border-radius:16px; padding:26px; box-shadow:0 4px 16px rgba(0,0,0,.07); margin-bottom:26px; }
        .comments-box h4 { font-size:1.05rem; font-weight:700; color:#1a2035; margin-bottom:18px; }
        .comment-item { border-bottom:1px solid #f0f4f8; padding:14px 0; display:flex; gap:12px; }
        .comment-item:last-child { border-bottom:none; }
        .c-avatar { width:40px; height:40px; border-radius:50%; background:linear-gradient(135deg,#2A7FAA,#4CAF50); display:flex; align-items:center; justify-content:center; color:#fff; font-weight:700; font-size:15px; flex-shrink:0; }
        .c-body  { flex:1; }
        .c-author { font-weight:700; font-size:13px; color:#1a2035; margin-bottom:2px; }
        .c-date  { font-size:11px; color:#94a3b8; margin-bottom:6px; }
        .c-content { font-size:14px; color:#555; line-height:1.6; }
        .c-actions { display:flex; gap:6px; margin-top:7px; }

        /* ── FORM COMMENTAIRE ── */
        .comment-form { background:#fff; border-radius:16px; padding:26px; box-shadow:0 4px 16px rgba(0,0,0,.07); margin-bottom:26px; }
        .comment-form h5 { font-size:.95rem; font-weight:700; color:#1a2035; margin-bottom:14px; }
        .fg { margin-bottom:12px; }
        .fg label { font-size:11px; font-weight:700; color:#6c757d; text-transform:uppercase; margin-bottom:4px; display:block; }
        .fg input, .fg textarea, .fg select { width:100%; padding:9px 12px; border:1.5px solid #e9ecef; border-radius:10px; font-size:13px; font-family:inherit; outline:none; background:#f8f9fc; color:#1a2035; transition:.15s; }
        .fg input:focus, .fg textarea:focus, .fg select:focus { border-color:#2A7FAA; background:#fff; }
        .fg textarea { min-height:85px; resize:vertical; }
        .fg .err-msg { font-size:11px; color:#dc2626; margin-top:3px; }
        .btn-comment { background:linear-gradient(135deg,#2A7FAA,#4CAF50); color:#fff; border:none; border-radius:25px; padding:9px 22px; font-size:13px; font-weight:600; cursor:pointer; transition:opacity .2s; }
        .btn-comment:hover { opacity:.88; }

        /* ── SIDEBAR ── */
        .sidebar-card { background:#fff; border-radius:16px; padding:20px; margin-bottom:18px; box-shadow:0 4px 12px rgba(0,0,0,.06); }
        .sidebar-card h5 { font-size:14px; font-weight:700; color:#1a2035; margin-bottom:14px; border-left:3px solid #4CAF50; padding-left:10px; }
        .stat-item { display:flex; justify-content:space-between; margin-bottom:7px; font-size:13px; }
        .stat-item strong { color:#2A7FAA; }

        /* ── CONNEXION PROMPT ── */
        .login-prompt { background:#e3f2fd; border-radius:12px; padding:14px 18px; margin-bottom:18px; font-size:13px; color:#1565c0; display:flex; align-items:center; gap:10px; }
        .login-prompt a { color:#2A7FAA; font-weight:700; }
        .logged-prompt { background:#e8f5e9; color:#2e7d32; border-radius:12px; padding:14px 18px; margin-bottom:18px; font-size:13px; display:flex; align-items:center; gap:10px; }

        /* ── MODALS ── */
        .overlay { display:none; position:fixed; inset:0; background:rgba(15,43,61,.55); z-index:900; align-items:center; justify-content:center; padding:16px; }
        .overlay.open { display:flex; }
        .modal-box { background:#fff; border-radius:20px; width:100%; max-width:520px; max-height:90vh; overflow-y:auto; animation:pop .22s ease; box-shadow:0 20px 60px rgba(0,0,0,.22); }
        .modal-box.sm { max-width:380px; }
        @keyframes pop { from { opacity:0; transform:scale(.95) } to { opacity:1; transform:scale(1) } }
        .mh { padding:16px 20px; border-bottom:1px solid #eee; display:flex; justify-content:space-between; align-items:center; }
        .mh h5 { font-size:15px; font-weight:700; color:#1a2035; margin:0; }
        .mh button { background:none; border:none; font-size:18px; color:#999; cursor:pointer; line-height:1; }
        .mb { padding:20px; }
        .mf { padding:14px 20px; border-top:1px solid #eee; display:flex; justify-content:flex-end; gap:8px; }
        .btn-cancel { padding:8px 18px; border-radius:25px; border:1.5px solid #e9ecef; background:#fff; font-size:13px; font-weight:600; color:#6c757d; cursor:pointer; }
        .btn-save   { padding:8px 20px; border-radius:25px; border:none; background:linear-gradient(135deg,#2A7FAA,#4CAF50); color:#fff; font-size:13px; font-weight:600; cursor:pointer; }
        .btn-danger { padding:8px 18px; border-radius:25px; border:none; background:#dc3545; color:#fff; font-size:13px; font-weight:600; cursor:pointer; }
        .confirm-body { text-align:center; padding:28px 20px 10px; }
        .confirm-body .ico { font-size:44px; margin-bottom:10px; }
        .confirm-body h5 { font-size:16px; font-weight:700; color:#1a2035; margin-bottom:6px; }
        .confirm-body p { font-size:13px; color:#6c757d; }

        /* ── DIVERS ── */
        .spinner { display:inline-block; width:14px; height:14px; border:2.5px solid #e9ecef; border-top-color:#2A7FAA; border-radius:50%; animation:spin .7s linear infinite; vertical-align:middle; margin-right:5px; }
        @keyframes spin { to { transform:rotate(360deg) } }
        .badge-count { background:#2A7FAA; color:#fff; border-radius:20px; padding:2px 8px; font-size:11px; font-weight:700; }
        .toast { position:fixed; bottom:20px; right:20px; background:#0f2b3d; color:#fff; padding:10px 20px; border-radius:12px; font-size:13px; display:none; z-index:9999; min-width:200px; }
        .toast.show { display:block; animation:su .25s ease; }
        .toast.ok  { background:#166534; }
        .toast.err { background:#991b1b; }
        @keyframes su { from { opacity:0; transform:translateY(10px) } to { opacity:1; transform:translateY(0) } }
        footer { background:#1a2035; color:#fff; text-align:center; padding:28px; margin-top:48px; }
    </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php?page=accueil">
            <i class="fas fa-stethoscope me-2"></i>Valorys
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="nav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="index.php?page=accueil">Accueil</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php?page=medecins">Médecins</a></li>
                <li class="nav-item"><a class="nav-link active" href="index.php?page=blog_public">Blog</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php?page=contact">Contact</a></li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item"><a class="nav-link" href="index.php?page=dashboard">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="index.php?page=logout">Déconnexion</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="index.php?page=login">Connexion</a></li>
                    <li class="nav-item"><a class="nav-link" href="index.php?page=register">Inscription</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- HEADER -->
<div class="blog-header">
    <div class="container">
        <h1><i class="fas fa-blog me-3"></i>Blog Valorys</h1>
        <p class="lead">Actualités, conseils santé et informations médicales</p>
    </div>
</div>

<div class="container mb-5">
    <div class="row">

        <!-- ═══════ VUE LISTE ═══════ -->
        <div class="col-lg-8" id="viewList">
            <?php if (!isset($_SESSION['user_id'])): ?>
            <div class="login-prompt">
                <i class="fas fa-info-circle fa-lg"></i>
                <span><a href="index.php?page=login">Connectez-vous</a> pour commenter, créer, modifier ou supprimer des articles.</span>
            </div>
            <?php else: ?>
            <div class="logged-prompt">
                <i class="fas fa-check-circle fa-lg"></i>
                <span>Connecté en tant que <strong><?= htmlspecialchars($_SESSION['user_name'] ?? 'Utilisateur') ?></strong></span>
            </div>
            <?php endif; ?>

            <div id="articlesList">
                <div class="text-center py-5"><div class="spinner"></div><p class="mt-2 text-muted">Chargement...</p></div>
            </div>
        </div>

        <!-- ═══════ VUE DETAIL ═══════ -->
        <div class="col-lg-8" id="viewDetail" style="display:none">
            <div class="detail-header" id="detailHeaderBg">
                <div class="container">
                    <button class="back-btn" onclick="showList()">
                        <i class="fas fa-arrow-left me-2"></i>Retour au blog
                    </button>
                    <h1 id="detailTitle"></h1>
                    <div class="detail-meta">
                        <span><i class="fas fa-user me-1"></i><span id="detailAuteur"></span></span>
                        <span><i class="fas fa-calendar me-1"></i><span id="detailDate"></span></span>
                        <span><i class="fas fa-eye me-1"></i><span id="detailVues"></span> vue(s)</span>
                        <span><i class="fas fa-comment me-1"></i><span id="detailNbRep"></span> commentaire(s)</span>
                    </div>
                </div>
            </div>

            <!-- Actions article (connecté seulement) -->
            <div id="artOwnerActions" class="art-detail-actions" style="display:none">
                <button class="btn-edit" onclick="openEditArtModal()">
                    <i class="fas fa-edit me-1"></i>Modifier l'article
                </button>
                <button class="btn-del" onclick="confirmDelArt()">
                    <i class="fas fa-trash me-1"></i>Supprimer l'article
                </button>
            </div>

            <div class="article-body-box" id="detailBody"></div>

            <!-- Commentaires -->
            <div class="comments-box">
                <h4><i class="fas fa-comments me-2" style="color:#2A7FAA"></i>Commentaires <span class="badge-count" id="repCount">0</span></h4>
                <div id="commentsContainer"><p class="text-muted text-center py-2">Aucun commentaire pour le moment.</p></div>
            </div>

            <!-- Formulaire commentaire -->
            <?php if (isset($_SESSION['user_id'])): ?>
            <div class="comment-form">
                <h5><i class="fas fa-pen me-2"></i>Laisser un commentaire</h5>
                <div class="fg">
                    <label>Type</label>
                    <select id="newRepType" onchange="toggleNewRepFields()">
                        <option value="text">💬 Texte</option>
                        <option value="emoji">😊 Emoji</option>
                        <option value="photo">🖼️ Photo (URL)</option>
                    </select>
                </div>
                <div class="fg" id="nfText">
                    <label>Commentaire *</label>
                    <textarea id="newRepText" placeholder="Votre commentaire..." rows="3"></textarea>
                    <div class="err-msg" id="eNewRepText"></div>
                </div>
                <div class="fg" id="nfEmoji" style="display:none">
                    <label>Emoji *</label>
                    <input id="newRepEmoji" placeholder="😊">
                    <div class="err-msg" id="eNewRepEmoji"></div>
                </div>
                <div class="fg" id="nfPhoto" style="display:none">
                    <label>URL Photo *</label>
                    <input id="newRepPhoto" placeholder="https://...">
                    <div class="err-msg" id="eNewRepPhoto"></div>
                </div>
                <button class="btn-comment" onclick="postComment()">
                    <i class="fas fa-paper-plane me-2"></i>Publier
                </button>
            </div>
            <?php else: ?>
            <div class="login-prompt">
                <i class="fas fa-lock"></i>
                <span><a href="index.php?page=login">Connectez-vous</a> pour laisser un commentaire.</span>
            </div>
            <?php endif; ?>
        </div>

        <!-- ═══════ SIDEBAR ═══════ -->
        <div class="col-lg-4">
            <!-- Recherche -->
            <div class="sidebar-card">
                <h5><i class="fas fa-search me-2"></i>Rechercher</h5>
                <div class="input-group">
                    <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="Rechercher...">
                    <button class="btn btn-primary btn-sm" onclick="searchArticles()"><i class="fas fa-search"></i></button>
                </div>
            </div>

            <!-- Stats -->
            <div class="sidebar-card">
                <h5><i class="fas fa-chart-line me-2"></i>Statistiques</h5>
                <div class="stat-item"><span>Total articles</span><strong id="statTotal">—</strong></div>
                <div class="stat-item"><span>Ce mois</span><strong id="statMonth">—</strong></div>
            </div>

            <!-- Publier un article (connectés uniquement) -->
            <?php if (isset($_SESSION['user_id'])): ?>
            <div class="sidebar-card">
                <h5><i class="fas fa-plus-circle me-2"></i>Publier un article</h5>
                <div class="fg">
                    <label>Titre *</label>
                    <input id="newArtTitre" placeholder="Titre de l'article">
                    <div class="err-msg" id="eNewArtTitre"></div>
                </div>
                <div class="fg">
                    <label>Contenu *</label>
                    <textarea id="newArtContenu" placeholder="Contenu..." rows="5"></textarea>
                    <div class="err-msg" id="eNewArtContenu"></div>
                </div>
                <button class="btn-comment w-100 mt-1" onclick="postArticle()">
                    <i class="fas fa-upload me-2"></i>Publier l'article
                </button>
            </div>
            <?php endif; ?>
        </div>

    </div><!-- /row -->
</div><!-- /container -->

<!-- ══ MODAL CRÉER / MODIFIER ARTICLE ══ -->
<div class="overlay" id="ovEditArt">
    <div class="modal-box">
        <div class="mh">
            <h5 id="ovEditArtTitle">Modifier l'article</h5>
            <button onclick="closeOv('ovEditArt')">✕</button>
        </div>
        <div class="mb">
            <div class="fg">
                <label>Titre *</label>
                <input id="editArtTitre" placeholder="Titre de l'article">
                <div class="err-msg" id="eEditArtTitre"></div>
            </div>
            <div class="fg">
                <label>Contenu *</label>
                <textarea id="editArtContenu" rows="8" placeholder="Contenu de l'article..."></textarea>
                <div class="err-msg" id="eEditArtContenu"></div>
            </div>
        </div>
        <div class="mf">
            <button class="btn-cancel" onclick="closeOv('ovEditArt')">Annuler</button>
            <button class="btn-save" onclick="saveEditArt()"><i class="fas fa-save me-1"></i>Enregistrer</button>
        </div>
    </div>
</div>

<!-- ══ MODAL MODIFIER COMMENTAIRE ══ -->
<div class="overlay" id="ovEditRep">
    <div class="modal-box">
        <div class="mh"><h5>Modifier le commentaire</h5><button onclick="closeOv('ovEditRep')">✕</button></div>
        <div class="mb">
            <input type="hidden" id="editRepId">
            <div class="fg">
                <label>Type</label>
                <select id="editRepType" onchange="toggleEditRepFields()">
                    <option value="text">💬 Texte</option>
                    <option value="emoji">😊 Emoji</option>
                    <option value="photo">🖼️ Photo URL</option>
                </select>
            </div>
            <div class="fg" id="efText">
                <label>Commentaire *</label>
                <textarea id="editRepText" rows="4"></textarea>
                <div class="err-msg" id="eEditRepText"></div>
            </div>
            <div class="fg" id="efEmoji" style="display:none">
                <label>Emoji *</label>
                <input id="editRepEmoji">
                <div class="err-msg" id="eEditRepEmoji"></div>
            </div>
            <div class="fg" id="efPhoto" style="display:none">
                <label>URL Photo *</label>
                <input id="editRepPhoto">
                <div class="err-msg" id="eEditRepPhoto"></div>
            </div>
        </div>
        <div class="mf">
            <button class="btn-cancel" onclick="closeOv('ovEditRep')">Annuler</button>
            <button class="btn-save" onclick="saveEditRep()"><i class="fas fa-save me-1"></i>Enregistrer</button>
        </div>
    </div>
</div>

<!-- ══ MODAL CONFIRMATION SUPPRESSION ══ -->
<div class="overlay" id="ovDel">
    <div class="modal-box sm">
        <div class="confirm-body">
            <div class="ico">🗑️</div>
            <h5 id="delTitle">Supprimer ?</h5>
            <p id="delSub">Cette action est irréversible.</p>
        </div>
        <div class="mf">
            <button class="btn-cancel" onclick="closeOv('ovDel')">Annuler</button>
            <button class="btn-danger" id="delBtn">Supprimer</button>
        </div>
    </div>
</div>

<div class="toast" id="toast"></div>

<footer>
    <div class="container">
        <p>© 2024 Valorys — Tous droits réservés</p>
        <small>Plateforme médicale en ligne</small>
    </div>
</footer>

<script>
// ═══════════════════════════════════════════
//  CONFIG SESSION (injectée par PHP)
// ═══════════════════════════════════════════
const IS_LOGGED    = <?= isset($_SESSION['user_id']) ? 'true' : 'false' ?>;
const SESSION_NAME = <?= isset($_SESSION['user_name']) ? json_encode($_SESSION['user_name']) : 'null' ?>;
const SESSION_ID   = <?= isset($_SESSION['user_id'])   ? (int)$_SESSION['user_id']          : 'null' ?>;

// ═══════════════════════════════════════════
//  UTILITAIRES
// ═══════════════════════════════════════════
const $ = id => document.getElementById(id);

function esc(s) {
    if (!s) return '';
    const d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
}

function fmtDate(d) { return d ? new Date(d).toLocaleDateString('fr-FR') : '—'; }
function fmtDT(d)   { return d ? new Date(d).toLocaleString('fr-FR')    : '—'; }
function trunc(s, n = 200) { return s && s.length > n ? s.slice(0, n) + '…' : (s || ''); }

function toast(msg, type = 'ok') {
    const t = $('toast');
    t.textContent = msg;
    t.className = 'toast show ' + type;
    clearTimeout(t._t);
    t._t = setTimeout(() => t.classList.remove('show'), 3200);
}

function closeOv(id) { $(id).classList.remove('open'); }
function openOv(id)  { $(id).classList.add('open'); }

document.querySelectorAll('.overlay').forEach(o =>
    o.addEventListener('click', e => { if (e.target === o) o.classList.remove('open'); })
);

function setErr(id, msg) { const e = $(id); if (e) e.textContent = msg; }
function clrErr(ids) { ids.forEach(id => { const e = $(id); if (e) { e.textContent = ''; } }); }

// ═══════════════════════════════════════════
//  APPELS API
// ═══════════════════════════════════════════
async function apiGet(params) {
    const r = await fetch('index.php?' + new URLSearchParams(params), { headers: { Accept: 'application/json' } });
    return r.json();
}
async function apiPost(params, body) {
    const r = await fetch('index.php?' + new URLSearchParams(params), {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
        body: JSON.stringify(body),
    });
    return r.json();
}

// ═══════════════════════════════════════════
//  ÉTAT GLOBAL
// ═══════════════════════════════════════════
let articles   = [];
let currentArt = null;
let editArtId  = null;
let editRepBuf = null;

// ═══════════════════════════════════════════
//  LISTE DES ARTICLES
// ═══════════════════════════════════════════
async function loadArticles() {
    $('articlesList').innerHTML = '<div class="text-center py-5"><div class="spinner"></div></div>';
    const r = await apiGet({ page: 'api_article', list: 1 });
    if (!r.success) {
        $('articlesList').innerHTML = '<div class="alert alert-danger">Erreur de chargement des articles.</div>';
        return;
    }
    articles = r.articles || [];
    $('statTotal').textContent = r.total  || 0;
    $('statMonth').textContent = r.month  || 0;

    if (!articles.length) {
        $('articlesList').innerHTML = '<div class="text-center py-5 text-muted"><i class="fas fa-newspaper fa-3x mb-3"></i><p>Aucun article disponible.</p></div>';
        return;
    }
    renderArticleList(articles);
}

function renderArticleList(list) {
    $('articlesList').innerHTML = list.map(a => `
        <div class="art-card">
            ${IS_LOGGED ? `<div class="art-crud">
                <button class="btn-edit" title="Modifier" onclick="openEditArtFromList(${a.id}, event)">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn-del" title="Supprimer" onclick="confirmDelArtFromList(${a.id}, ${JSON.stringify(esc(a.titre))}, event)">
                    <i class="fas fa-trash"></i>
                </button>
            </div>` : ''}
            <h2 class="art-title">
                <a href="#" onclick="openArticle(${a.id}); return false">${esc(a.titre)}</a>
            </h2>
            <div class="art-meta">
                <span><i class="fas fa-user"></i> ${esc(a.auteur) || 'Valorys'}</span>
                <span><i class="fas fa-calendar-alt"></i> ${fmtDate(a.created_at)}</span>
                <span><i class="fas fa-eye"></i> ${a.vues || 0} vue(s)</span>
                <span><i class="fas fa-comment"></i> ${a.nb_replies || 0} commentaire(s)</span>
            </div>
            <div class="art-excerpt">${trunc(esc(a.contenu))}</div>
            <div class="art-footer">
                <a href="#" class="btn-read" onclick="openArticle(${a.id}); return false">
                    Lire la suite <i class="fas fa-arrow-right ms-1"></i>
                </a>
            </div>
        </div>
    `).join('');
}

// Recherche locale
function searchArticles() {
    const q = $('searchInput').value.toLowerCase().trim();
    if (!q) { loadArticles(); return; }
    const filtered = articles.filter(a =>
        (a.titre   || '').toLowerCase().includes(q) ||
        (a.contenu || '').toLowerCase().includes(q)
    );
    if (!filtered.length) {
        $('articlesList').innerHTML = '<div class="text-center text-muted py-4">Aucun résultat pour cette recherche.</div>';
        return;
    }
    renderArticleList(filtered);
}
$('searchInput').addEventListener('keypress', e => { if (e.key === 'Enter') searchArticles(); });

// ═══════════════════════════════════════════
//  DÉTAIL D'UN ARTICLE
// ═══════════════════════════════════════════
async function openArticle(id) {
    const r = await apiGet({ page: 'api_article', id });
    if (!r.success) { toast('Article introuvable', 'err'); return; }

    currentArt = r.article;
    const replies = r.replies || [];

    $('viewList').style.display   = 'none';
    $('viewDetail').style.display = '';

    $('detailTitle').textContent  = currentArt.titre;
    $('detailAuteur').textContent = currentArt.auteur || 'Valorys';
    $('detailDate').textContent   = fmtDate(currentArt.created_at);
    $('detailVues').textContent   = currentArt.vues || 0;
    $('detailNbRep').textContent  = replies.length;
    $('detailBody').innerHTML     = esc(currentArt.contenu).replace(/\n/g, '<br>');

    // Boutons modifier / supprimer article
    $('artOwnerActions').style.display = IS_LOGGED ? 'flex' : 'none';

    renderComments(replies);
    window.scrollTo(0, 0);
}

function showList() {
    $('viewList').style.display   = '';
    $('viewDetail').style.display = 'none';
    currentArt = null;
    loadArticles();
}

// ═══════════════════════════════════════════
//  COMMENTAIRES
// ═══════════════════════════════════════════
function renderComments(replies) {
    $('repCount').textContent = replies.length;
    if (!replies.length) {
        $('commentsContainer').innerHTML = '<p class="text-muted text-center py-3">Aucun commentaire. Soyez le premier !</p>';
        return;
    }
    $('commentsContainer').innerHTML = replies.map(r => {
        let content = '';
        if      (r.type_reply === 'emoji') content = `<span style="font-size:26px">${esc(r.emoji)}</span>`;
        else if (r.type_reply === 'photo') content = `<img src="${esc(r.photo)}" style="max-width:100%;border-radius:10px;margin-top:6px">`;
        else                               content = esc(r.contenu_text || '').replace(/\n/g, '<br>');

        const actions = IS_LOGGED ? `<div class="c-actions">
            <button class="btn-edit" onclick="openEditRepModal(${r.id_reply})"><i class="fas fa-edit me-1"></i>Modifier</button>
            <button class="btn-del"  onclick="confirmDelRep(${r.id_reply})"><i class="fas fa-trash me-1"></i>Supprimer</button>
        </div>` : '';

        return `<div class="comment-item">
            <div class="c-avatar">${(r.auteur || 'A').charAt(0).toUpperCase()}</div>
            <div class="c-body">
                <div class="c-author">${esc(r.auteur || 'Anonyme')}</div>
                <div class="c-date"><i class="fas fa-clock me-1"></i>${fmtDT(r.date_reply)}</div>
                <div class="c-content">${content}</div>
                ${actions}
            </div>
        </div>`;
    }).join('');
}

// Poster un commentaire
function toggleNewRepFields() {
    const t = $('newRepType').value;
    $('nfText').style.display  = t === 'text'  ? '' : 'none';
    $('nfEmoji').style.display = t === 'emoji' ? '' : 'none';
    $('nfPhoto').style.display = t === 'photo' ? '' : 'none';
}

async function postComment() {
    if (!IS_LOGGED || !currentArt) return;
    clrErr(['eNewRepText', 'eNewRepEmoji', 'eNewRepPhoto']);

    const type = $('newRepType').value;
    let contenu_text = null, emoji = null, photo = null, ok = true;

    if      (type === 'text')  { contenu_text = $('newRepText').value.trim();  if (!contenu_text) { setErr('eNewRepText', 'Le texte est obligatoire.');   ok = false; } }
    else if (type === 'emoji') { emoji        = $('newRepEmoji').value.trim(); if (!emoji)        { setErr('eNewRepEmoji', "L'emoji est obligatoire.");    ok = false; } }
    else                       { photo        = $('newRepPhoto').value.trim(); if (!photo)        { setErr('eNewRepPhoto', "L'URL est obligatoire.");       ok = false; } }
    if (!ok) return;

    const r = await apiPost({ page: 'api_reply' }, { id_article: currentArt.id, type_reply: type, contenu_text, emoji, photo, auteur: SESSION_NAME });
    if (!r.success) { toast(r.message || 'Erreur', 'err'); return; }

    toast('Commentaire publié !', 'ok');
    $('newRepText').value = ''; $('newRepEmoji').value = ''; $('newRepPhoto').value = '';
    const r2 = await apiGet({ page: 'api_article', id: currentArt.id });
    if (r2.success) renderComments(r2.replies || []);
}

// Modifier un commentaire
function toggleEditRepFields() {
    const t = $('editRepType').value;
    $('efText').style.display  = t === 'text'  ? '' : 'none';
    $('efEmoji').style.display = t === 'emoji' ? '' : 'none';
    $('efPhoto').style.display = t === 'photo' ? '' : 'none';
}

async function openEditRepModal(repId) {
    if (!IS_LOGGED) return;
    const r = await apiGet({ page: 'api_reply', id: repId });
    if (!r.success || !r.reply) { toast('Commentaire introuvable', 'err'); return; }

    editRepBuf = r.reply;
    $('editRepId').value   = repId;
    $('editRepType').value = r.reply.type_reply;
    $('editRepText').value  = r.reply.contenu_text || '';
    $('editRepEmoji').value = r.reply.emoji || '';
    $('editRepPhoto').value = r.reply.photo || '';
    clrErr(['eEditRepText', 'eEditRepEmoji', 'eEditRepPhoto']);
    toggleEditRepFields();
    openOv('ovEditRep');
}

async function saveEditRep() {
    if (!IS_LOGGED || !editRepBuf) return;
    const id   = $('editRepId').value;
    const type = $('editRepType').value;
    let contenu_text = null, emoji = null, photo = null, ok = true;
    clrErr(['eEditRepText', 'eEditRepEmoji', 'eEditRepPhoto']);

    if      (type === 'text')  { contenu_text = $('editRepText').value.trim();  if (!contenu_text) { setErr('eEditRepText', 'Le texte est obligatoire.');   ok = false; } }
    else if (type === 'emoji') { emoji        = $('editRepEmoji').value.trim(); if (!emoji)        { setErr('eEditRepEmoji', "L'emoji est obligatoire.");    ok = false; } }
    else                       { photo        = $('editRepPhoto').value.trim(); if (!photo)        { setErr('eEditRepPhoto', "L'URL est obligatoire.");       ok = false; } }
    if (!ok) return;

    const r = await apiPost({ page: 'api_reply', id }, { id_article: editRepBuf.id_article, type_reply: type, contenu_text, emoji, photo, auteur: editRepBuf.auteur, _method: 'PUT' });
    if (!r.success) { toast(r.message || 'Erreur', 'err'); return; }

    closeOv('ovEditRep');
    toast('Commentaire modifié !', 'ok');
    const r2 = await apiGet({ page: 'api_article', id: currentArt.id });
    if (r2.success) renderComments(r2.replies || []);
}

// Supprimer un commentaire
function confirmDelRep(repId) {
    if (!IS_LOGGED) return;
    $('delTitle').textContent = 'Supprimer ce commentaire ?';
    $('delSub').textContent   = 'Cette action est irréversible.';
    $('delBtn').onclick = async () => {
        const r = await apiPost({ page: 'api_reply', id: repId }, { _method: 'DELETE' });
        closeOv('ovDel');
        toast(r.success ? 'Commentaire supprimé.' : (r.message || 'Erreur'), r.success ? 'ok' : 'err');
        if (!r.success) return;
        const r2 = await apiGet({ page: 'api_article', id: currentArt.id });
        if (r2.success) renderComments(r2.replies || []);
    };
    openOv('ovDel');
}

// ═══════════════════════════════════════════
//  PUBLIER UN ARTICLE (sidebar)
// ═══════════════════════════════════════════
async function postArticle() {
    if (!IS_LOGGED) return;
    clrErr(['eNewArtTitre', 'eNewArtContenu']);

    const titre   = $('newArtTitre').value.trim();
    const contenu = $('newArtContenu').value.trim();
    let ok = true;
    if (!titre)   { setErr('eNewArtTitre', 'Le titre est obligatoire.');   ok = false; }
    if (!contenu) { setErr('eNewArtContenu', 'Le contenu est obligatoire.'); ok = false; }
    if (!ok) return;

    const r = await apiPost({ page: 'api_article' }, { titre, contenu });
    if (!r.success) { toast(r.message || 'Erreur lors de la création', 'err'); return; }

    toast('Article publié avec succès !', 'ok');
    $('newArtTitre').value   = '';
    $('newArtContenu').value = '';
    loadArticles();
}

// ═══════════════════════════════════════════
//  MODIFIER UN ARTICLE
// ═══════════════════════════════════════════
function openEditArtModal() {
    if (!IS_LOGGED || !currentArt) return;
    editArtId = currentArt.id;
    $('ovEditArtTitle').textContent = 'Modifier l\'article';
    $('editArtTitre').value   = currentArt.titre;
    $('editArtContenu').value = currentArt.contenu;
    clrErr(['eEditArtTitre', 'eEditArtContenu']);
    openOv('ovEditArt');
}

async function openEditArtFromList(id, e) {
    e.stopPropagation();
    if (!IS_LOGGED) return;
    const r = await apiGet({ page: 'api_article', id });
    if (!r.success) { toast('Article introuvable', 'err'); return; }

    editArtId = id;
    $('ovEditArtTitle').textContent = 'Modifier l\'article';
    $('editArtTitre').value   = r.article.titre;
    $('editArtContenu').value = r.article.contenu;
    clrErr(['eEditArtTitre', 'eEditArtContenu']);
    openOv('ovEditArt');
}

async function saveEditArt() {
    if (!IS_LOGGED || !editArtId) return;
    clrErr(['eEditArtTitre', 'eEditArtContenu']);

    const titre   = $('editArtTitre').value.trim();
    const contenu = $('editArtContenu').value.trim();
    let ok = true;
    if (!titre)   { setErr('eEditArtTitre', 'Le titre est obligatoire.');   ok = false; }
    if (!contenu) { setErr('eEditArtContenu', 'Le contenu est obligatoire.'); ok = false; }
    if (!ok) return;

    const r = await apiPost({ page: 'api_article', id: editArtId }, { titre, contenu, _method: 'PUT' });
    if (!r.success) { toast(r.message || 'Erreur lors de la modification', 'err'); return; }

    closeOv('ovEditArt');
    toast('Article modifié avec succès !', 'ok');

    // Si on est dans le détail, mettre à jour l'affichage
    if (currentArt && currentArt.id === editArtId) {
        const r2 = await apiGet({ page: 'api_article', id: editArtId });
        if (r2.success) {
            currentArt = r2.article;
            $('detailTitle').textContent = currentArt.titre;
            $('detailBody').innerHTML    = esc(currentArt.contenu).replace(/\n/g, '<br>');
        }
    }
    loadArticles();
}

// ═══════════════════════════════════════════
//  SUPPRIMER UN ARTICLE
// ═══════════════════════════════════════════
function confirmDelArt() {
    if (!IS_LOGGED || !currentArt) return;
    $('delTitle').textContent = `Supprimer "${currentArt.titre}" ?`;
    $('delSub').textContent   = 'Tous les commentaires liés seront également supprimés.';
    $('delBtn').onclick = async () => {
        const r = await apiPost({ page: 'api_article', id: currentArt.id }, { _method: 'DELETE' });
        closeOv('ovDel');
        toast(r.success ? 'Article supprimé.' : (r.message || 'Erreur'), r.success ? 'ok' : 'err');
        if (r.success) showList();
    };
    openOv('ovDel');
}

function confirmDelArtFromList(id, titre, e) {
    e.stopPropagation();
    $('delTitle').textContent = `Supprimer "${titre}" ?`;
    $('delSub').textContent   = 'Tous les commentaires liés seront également supprimés.';
    $('delBtn').onclick = async () => {
        const r = await apiPost({ page: 'api_article', id }, { _method: 'DELETE' });
        closeOv('ovDel');
        toast(r.success ? 'Article supprimé.' : (r.message || 'Erreur'), r.success ? 'ok' : 'err');
        if (r.success) loadArticles();
    };
    openOv('ovDel');
}

// ═══════════════════════════════════════════
//  INIT
// ═══════════════════════════════════════════
loadArticles();
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>