<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blog Valorys</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js?v=<?php echo time(); ?>"></script>
    <style>
        body{background:linear-gradient(135deg,#f5f7fb 0%,#e8f2f8 100%);font-family:'Segoe UI','Roboto',sans-serif;min-height:100vh}
        .blog-header{background:linear-gradient(135deg,#2A7FAA 0%,#4CAF50 100%);color:#fff;padding:80px 0;text-align:center;margin-bottom:50px;box-shadow:0 8px 24px rgba(42,127,170,.2)}
        .blog-header h1{font-size:2.5rem;font-weight:800;letter-spacing:-1px;margin-bottom:12px}
        .blog-header i{font-size:2rem;margin-right:15px}
        .blog-header p{opacity:.95;font-size:1.1rem;font-weight:500}
        nav.navbar{background:linear-gradient(180deg,#1a2035 0%,#0f1620 100%);box-shadow:0 4px 12px rgba(0,0,0,.15)!important}
        .art-card{background:#fff;border-radius:16px;padding:28px;margin-bottom:24px;box-shadow:0 4px 16px rgba(0,0,0,.08);border-left:5px solid #4CAF50;transition:all .3s;position:relative;overflow:hidden}
        .art-card:hover{transform:translateY(-4px);box-shadow:0 12px 32px rgba(42,127,170,.15);border-left-color:#2A7FAA}
        .art-title{font-size:1.3rem;font-weight:700;color:#1a2035;margin-bottom:10px}
        .art-title a{color:#1a2035;text-decoration:none;transition:.2s}
        .art-title a:hover{color:#2A7FAA}
        .art-meta{font-size:13px;color:#6c757d;display:flex;gap:18px;flex-wrap:wrap;margin-bottom:14px}
        .art-meta i{color:#4CAF50;width:16px;text-align:center}
        .art-excerpt{color:#555;line-height:1.8;font-size:14px;margin-bottom:18px;font-weight:500}
        .art-footer{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px}
        .btn-read{background:linear-gradient(135deg,#2A7FAA,#4CAF50);color:#fff;border:none;border-radius:25px;padding:10px 24px;font-size:13px;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:6px;font-weight:600;transition:.3s;box-shadow:0 4px 12px rgba(42,127,170,.2)}
        .btn-read:hover{transform:translateY(-2px);box-shadow:0 6px 16px rgba(42,127,170,.3);color:#fff}
        .art-actions{display:flex;gap:8px;position:absolute;top:20px;right:20px}
        .btn-sm-edit{background:#e3f2fd;color:#1565c0;border:none;border-radius:20px;padding:7px 14px;font-size:12px;cursor:pointer;font-weight:600;transition:.2s}
        .btn-sm-edit:hover{background:#1565c0;color:#fff;transform:scale(1.05)}
        .btn-sm-del{background:#fce4ec;color:#c62828;border:none;border-radius:20px;padding:7px 14px;font-size:12px;cursor:pointer;font-weight:600;transition:.2s}
        .btn-sm-del:hover{background:#c62828;color:#fff;transform:scale(1.05)}
        .detail-header{background:linear-gradient(135deg,#2A7FAA 0%,#4CAF50 100%);color:#fff;padding:60px 0;margin-bottom:40px;box-shadow:0 8px 24px rgba(42,127,170,.2)}
        .detail-header h1{font-size:2.1rem;font-weight:800;margin-bottom:14px}
        .detail-meta{font-size:13px;opacity:.9;display:flex;gap:24px;flex-wrap:wrap;font-weight:500}
        .article-body-box{background:#fff;border-radius:16px;padding:40px;box-shadow:0 4px 16px rgba(0,0,0,.08);margin-bottom:32px;line-height:1.9;color:#333;font-size:15px}
        .article-body-box img{max-width:100%;height:auto;border-radius:12px;margin:20px 0}
        .article-body-box h2{color:#2A7FAA;margin-top:30px;margin-bottom:15px;font-weight:700}
        .back-btn{display:inline-flex;align-items:center;gap:8px;background:rgba(255,255,255,.2);color:#fff;border:none;border-radius:25px;padding:10px 20px;font-size:13px;cursor:pointer;margin-bottom:18px;font-weight:600;transition:.2s}
        .back-btn:hover{background:rgba(255,255,255,.35)}
        .comments-box{background:#fff;border-radius:16px;padding:32px;box-shadow:0 4px 16px rgba(0,0,0,.08);margin-bottom:32px}
        .comments-box h4{font-size:1.15rem;font-weight:700;color:#1a2035;margin-bottom:24px;display:flex;align-items:center;gap:12px}
        .comment-item{border-bottom:1px solid #f0f4f8;padding:20px 0;display:flex;gap:16px;position:relative;transition:.2s}
        .comment-item:hover{background:rgba(42,127,170,.02);border-radius:8px;padding:20px;margin:0 -20px}
        .comment-item:last-child{border-bottom:none}
        .c-avatar{width:48px;height:48px;border-radius:50%;background:linear-gradient(135deg,#2A7FAA,#4CAF50);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:18px;flex-shrink:0;box-shadow:0 2px 8px rgba(42,127,170,.15)}
        .c-body{flex:1}
        .c-author{font-weight:700;font-size:13px;color:#1a2035;margin-bottom:3px}
        .c-date{font-size:12px;color:#94a3b8;margin-bottom:10px}
        .c-content{font-size:14px;color:#555;line-height:1.7}
        .c-content img{max-width:100%;border-radius:8px;margin-top:10px}
        .c-actions{display:flex;gap:8px;margin-top:10px}
        .comment-form{background:#fff;border-radius:16px;padding:32px;box-shadow:0 4px 16px rgba(0,0,0,.08);margin-bottom:32px}
        .comment-form h5{font-size:1.05rem;font-weight:700;color:#1a2035;margin-bottom:20px;display:flex;align-items:center;gap:8px}
        .form-group-custom{margin-bottom:18px}
        .form-group-custom label{font-size:12px;font-weight:700;color:#0f2b3d;text-transform:uppercase;margin-bottom:8px;display:block;letter-spacing:.5px}
        .form-group-custom input,.form-group-custom textarea,.form-group-custom select{width:100%;padding:12px 14px;border:2px solid #e9ecef;border-radius:10px;font-size:13px;font-family:inherit;outline:none;background:#f8f9fc;color:#1a2035;transition:all .2s}
        .form-group-custom input:focus,.form-group-custom textarea:focus,.form-group-custom select:focus{border-color:#4CAF50;background:#fff;box-shadow:0 0 0 3px rgba(76,175,80,.1)}
        .form-group-custom textarea{min-height:100px;resize:vertical}
        .form-group-custom input.err,.form-group-custom textarea.err{border-color:#dc2626;background:#fff5f5}
        .err-msg{font-size:12px;color:#dc2626;margin-top:5px;font-weight:600;display:flex;align-items:center;gap:4px}
        .btn-comment{background:linear-gradient(135deg,#2A7FAA,#4CAF50);color:#fff;border:none;border-radius:25px;padding:11px 26px;font-size:13px;font-weight:700;cursor:pointer;transition:all .3s;display:inline-flex;align-items:center;gap:6px;box-shadow:0 4px 12px rgba(42,127,170,.2)}
        .btn-comment:hover{transform:translateY(-2px);box-shadow:0 6px 16px rgba(42,127,170,.3)}
        .sidebar-card{background:#fff;border-radius:16px;padding:24px;margin-bottom:24px;box-shadow:0 4px 16px rgba(0,0,0,.08);border-top:4px solid #4CAF50}
        .sidebar-card h5{font-size:14px;font-weight:700;color:#1a2035;margin-bottom:16px;display:flex;align-items:center;gap:8px}
        .sidebar-card h5 i{color:#4CAF50}
        .stat-item{display:flex;justify-content:space-between;margin-bottom:12px;font-size:13px;padding:8px;background:#f8f9fc;border-radius:8px}
        .stat-item strong{color:#2A7FAA;font-weight:700}
        .login-prompt{background:linear-gradient(135deg,#e3f2fd 0%,#f0f7ff 100%);border-radius:12px;padding:16px 20px;margin-bottom:24px;font-size:13px;color:#1565c0;display:flex;align-items:center;gap:12px;border-left:4px solid #2A7FAA}
        .login-prompt a{color:#2A7FAA;font-weight:700;text-decoration:none}
        .login-prompt a:hover{text-decoration:underline}
        .overlay{display:none;position:fixed;inset:0;background:rgba(15,43,61,.55);z-index:500;align-items:center;justify-content:center;padding:16px}
        .overlay.open{display:flex}
        .modal-box{background:#fff;border-radius:20px;width:100%;max-width:500px;max-height:90vh;overflow-y:auto;animation:pop .22s ease}
        @keyframes pop{from{opacity:0;transform:scale(.95)}to{opacity:1;transform:scale(1)}}
        .mh{padding:16px 20px;border-bottom:1px solid #eee;display:flex;justify-content:space-between;align-items:center}
        .mh h5{font-size:15px;font-weight:700;color:#1a2035;margin:0}
        .mh button{background:none;border:none;font-size:18px;color:#999;cursor:pointer}
        .mb{padding:20px}
        .mf{padding:14px 20px;border-top:1px solid #eee;display:flex;justify-content:flex-end;gap:8px}
        .btn-cancel{padding:8px 18px;border-radius:25px;border:1.5px solid #e9ecef;background:#fff;font-size:13px;font-weight:600;color:#6c757d;cursor:pointer}
        .btn-save{padding:8px 20px;border-radius:25px;border:none;background:linear-gradient(135deg,#2A7FAA,#4CAF50);color:#fff;font-size:13px;font-weight:600;cursor:pointer}
        .btn-danger{padding:8px 18px;border-radius:25px;border:none;background:#dc3545;color:#fff;font-size:13px;font-weight:600;cursor:pointer}
        .confirm-body{text-align:center;padding:24px 20px}
        .confirm-body .ico{font-size:40px;margin-bottom:10px}
        .confirm-body h5{font-size:15px;font-weight:700;color:#1a2035;margin-bottom:4px}
        .confirm-body p{font-size:13px;color:#6c757d}
        .spinner{display:inline-block;width:15px;height:15px;border:2.5px solid #e9ecef;border-top-color:#2A7FAA;border-radius:50%;animation:spin .7s linear infinite;vertical-align:middle;margin-right:5px}
        @keyframes spin{to{transform:rotate(360deg)}}
        .badge-count{background:#2A7FAA;color:#fff;border-radius:20px;padding:2px 8px;font-size:11px;font-weight:700}
        .toast{position:fixed;bottom:20px;right:20px;background:#0f2b3d;color:#fff;padding:10px 20px;border-radius:12px;font-size:13px;display:none;z-index:999}
        .toast.show{display:block;animation:su .25s ease}
        .toast.ok{background:#166534}.toast.err{background:#991b1b}
        @keyframes su{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:translateY(0)}}
        footer{background:#1a2035;color:#fff;text-align:center;padding:30px;margin-top:50px}
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
        <h1><i class="fas fa-blog me-3"></i>Blog Valorys</h1>
        <p class="lead">Actualités, conseils santé et informations médicales</p>
    </div>
</div>

<div class="container mb-5">
    <div class="row">
        <div class="col-lg-8" id="viewList">
            <?php if (!isset($_SESSION['user_id'])): ?>
            <div class="login-prompt">
                <i class="fas fa-info-circle fa-lg"></i>
                <span><a href="index.php?page=login">Connectez-vous</a> pour commenter, modifier ou supprimer vos contributions.</span>
            </div>
            <?php else: ?>
            <div class="login-prompt" style="background:#e8f5e9;color:#2e7d32">
                <i class="fas fa-check-circle fa-lg"></i>
                <span>Connecté en tant que <strong><?= htmlspecialchars($_SESSION['user_name'] ?? 'Utilisateur') ?></strong> — Vous pouvez créer, modifier et supprimer vos articles et commentaires.</span>
            </div>
            <?php endif; ?>
            <div id="articlesList">
                <div class="text-center py-5"><div class="spinner"></div><p class="mt-2 text-muted">Chargement...</p></div>
            </div>
        </div>

        <div class="col-lg-8" id="viewDetail" style="display:none">
            <div class="detail-header">
                <div class="container">
                    <button class="back-btn" onclick="showList()"><i class="fas fa-arrow-left me-2"></i>Retour au blog</button>
                    <h1 id="detailTitle"></h1>
                    <div class="detail-meta">
                        <span><i class="fas fa-user me-1"></i><span id="detailAuteur"></span></span>
                        <span><i class="fas fa-calendar me-1"></i><span id="detailDate"></span></span>
                        <span><i class="fas fa-comment me-1"></i><span id="detailNbRep"></span> commentaire(s)</span>
                    </div>
                </div>
            </div>
            <div class="article-body-box" id="detailBody"></div>

            <div id="artOwnerActions" style="display:none;margin-bottom:20px">
                <button class="btn-sm-edit" onclick="openEditArtModal()"><i class="fas fa-edit me-1"></i>Modifier l'article</button>
                <button class="btn-sm-del ms-2" onclick="confirmDelArt()"><i class="fas fa-trash me-1"></i>Supprimer l'article</button>
            </div>

            <div class="comments-box">
                <h4><i class="fas fa-comments me-2" style="color:#2A7FAA"></i>Commentaires <span class="badge-count" id="repCount">0</span></h4>
                <div id="commentsContainer"><p class="text-muted text-center">Aucun commentaire pour le moment.</p></div>
            </div>

            <?php if (isset($_SESSION['user_id'])): ?>
            <div class="comment-form">
                <h5><i class="fas fa-pen me-2"></i>💬 Laisser un commentaire</h5>
                <div class="form-group-custom">
                    <label>📋 Type</label>
                    <select id="newRepType" onchange="toggleNewRepFields()">
                        <option value="text">💬 Texte</option>
                        <option value="emoji">😊 Emoji</option>
                        <option value="photo">🖼️ Photo (URL)</option>
                    </select>
                </div>
                <div class="form-group-custom" id="nfText">
                    <label>💭 Commentaire *</label>
                    <textarea id="newRepText" placeholder="Votre commentaire..." rows="3"></textarea>
                    <div class="err-msg" id="eNewRepText"></div>
                </div>
                <div class="form-group-custom" id="nfEmoji" style="display:none">
                    <label>😊 Emoji *</label>
                    <input id="newRepEmoji" placeholder="😊">
                    <div class="err-msg" id="eNewRepEmoji"></div>
                </div>
                <div class="form-group-custom" id="nfPhoto" style="display:none">
                    <label>🖼️ URL Photo *</label>
                    <input id="newRepPhoto" placeholder="https://...">
                    <div class="err-msg" id="eNewRepPhoto"></div>
                </div>
                <button class="btn-comment" onclick="postComment()"><i class="fas fa-paper-plane me-2"></i>✈️ Publier</button>
            </div>
            <?php else: ?>
            <div class="login-prompt">
                <i class="fas fa-lock"></i>
                <span><a href="index.php?page=login">Connectez-vous</a> pour laisser un commentaire.</span>
            </div>
            <?php endif; ?>
        </div>

        <div class="col-lg-4">
            <div class="sidebar-card">
                <h5><i class="fas fa-search me-2"></i>Rechercher</h5>
                <div class="input-group">
                    <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="Rechercher...">
                    <button class="btn btn-primary btn-sm" onclick="searchArticles()"><i class="fas fa-search"></i></button>
                </div>
            </div>
            <div class="sidebar-card">
                <h5><i class="fas fa-chart-line me-2"></i>Statistiques</h5>
                <div class="stat-item"><span>Total articles</span><strong id="statTotal">—</strong></div>
                <div class="stat-item"><span>Ce mois</span><strong id="statMonth">—</strong></div>
            </div>
            <?php if (isset($_SESSION['user_id'])): ?>
            <div class="sidebar-card">
                <h5><i class="fas fa-feather me-2"></i>Créer un article</h5>
                <p style="font-size:13px;color:#6c757d;margin-bottom:16px">Accédez au formulaire complet pour créer votre article avec tous les détails.</p>
                <a href="index.php?page=blog_public&action=create" class="btn-comment w-100" style="text-align:center;text-decoration:none;display:inline-block"><i class="fas fa-paper-plane me-2"></i>🚀 Créer un article</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="overlay" id="ovEditArt">
    <div class="modal-box">
        <div class="mh"><h5>✏️ Modifier l'article</h5><button onclick="closeOv('ovEditArt')">✕</button></div>
        <div class="mb">
            <div class="form-group-custom"><label>📝 Titre *</label><input id="editArtTitre" class="form-control"><div class="err-msg" id="eEditArtTitre"></div></div>
            <div class="form-group-custom"><label>🖋️ Contenu *</label><textarea id="editArtContenu" class="form-control" rows="5"></textarea><div class="err-msg" id="eEditArtContenu"></div></div>
        </div>
        <div class="mf">
            <button class="btn-cancel" onclick="closeOv('ovEditArt')">❌ Annuler</button>
            <button class="btn-save" onclick="saveEditArt()"><i class="fas fa-save me-1"></i>💾 Enregistrer</button>
        </div>
    </div>
</div>

<div class="overlay" id="ovEditRep">
    <div class="modal-box">
        <div class="mh"><h5>✏️ Modifier le commentaire</h5><button onclick="closeOv('ovEditRep')">✕</button></div>
        <div class="mb">
            <input type="hidden" id="editRepId">
            <div class="form-group-custom"><label>📋 Type</label>
                <select id="editRepType" class="form-select" onchange="toggleEditRepFields()">
                    <option value="text">💬 Texte</option>
                    <option value="emoji">😊 Emoji</option>
                    <option value="photo">🖼️ Photo URL</option>
                </select>
            </div>
            <div class="form-group-custom" id="efText"><label>💭 Commentaire *</label><textarea id="editRepText" class="form-control" rows="3"></textarea><div class="err-msg" id="eEditRepText"></div></div>
            <div class="form-group-custom" id="efEmoji" style="display:none"><label>😊 Emoji *</label><input id="editRepEmoji" class="form-control"><div class="err-msg" id="eEditRepEmoji"></div></div>
            <div class="form-group-custom" id="efPhoto" style="display:none"><label>🖼️ URL Photo *</label><input id="editRepPhoto" class="form-control"><div class="err-msg" id="eEditRepPhoto"></div></div>
        </div>
        <div class="mf">
            <button class="btn-cancel" onclick="closeOv('ovEditRep')">❌ Annuler</button>
            <button class="btn-save" onclick="saveEditRep()"><i class="fas fa-save me-1"></i>💾 Enregistrer</button>
        </div>
    </div>
</div>

<div class="overlay" id="ovDel">
    <div class="modal-box" style="max-width:360px">
        <div class="confirm-body"><div class="ico">🗑️</div><h5 id="delTitle">Supprimer ?</h5><p id="delSub">Cette action est irréversible.</p></div>
        <div class="mf"><button class="btn-cancel" onclick="closeOv('ovDel')">Annuler</button><button class="btn-danger" id="delBtn">Supprimer</button></div>
    </div>
</div>

<div class="toast" id="toast"></div>

<footer><div class="container"><p>© 2024 Valorys — Tous droits réservés</p><small>Plateforme médicale en ligne</small></div></footer>

<script>
const IS_LOGGED = <?= isset($_SESSION['user_id']) ? 'true' : 'false' ?>;
const SESSION_NAME = <?= isset($_SESSION['user_name']) ? json_encode($_SESSION['user_name']) : 'null' ?>;
const SESSION_ID = <?= isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'null' ?>;

const $ = id => document.getElementById(id);
const esc = s => { if(!s) return ''; const d = document.createElement('div'); d.textContent = s; return d.innerHTML; };
const fmtDate = d => d ? new Date(d).toLocaleDateString('fr-FR') : '—';
const fmtDT = d => d ? new Date(d).toLocaleString('fr-FR') : '—';
const trunc = (s, n=180) => s && s.length > n ? s.slice(0,n)+'…' : (s||'');

function toast(msg, type='ok'){
    const t = $('toast');
    t.textContent = msg;
    t.className = 'toast show ' + type;
    clearTimeout(t._t);
    t._t = setTimeout(() => t.classList.remove('show'), 3000);
}
function closeOv(id){ $(id).classList.remove('open'); }
function openOv(id){ $(id).classList.add('open'); }
document.querySelectorAll('.overlay').forEach(o => o.addEventListener('click', e => { if(e.target === o) o.classList.remove('open'); }));
function setErr(id, msg){ const e = $(id); if(e) e.textContent = msg; }
function clrErrMsg(ids){ ids.forEach(id => { const e = $(id); if(e) e.textContent = ''; }); }

async function apiGet(params){
    const r = await fetch('index.php?' + new URLSearchParams(params), { headers: { Accept: 'application/json' } });
    return r.json();
}
async function apiPost(params, body){
    const r = await fetch('index.php?' + new URLSearchParams(params), {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
        body: JSON.stringify(body)
    });
    return r.json();
}

let articles = [];
let currentArt = null;

async function loadArticles(){
    $('articlesList').innerHTML = '<div class="text-center py-5"><div class="spinner"></div></div>';
    const r = await apiGet({ page: 'api_article', list: 1 });
    if(!r.success){ $('articlesList').innerHTML = '<div class="alert alert-danger">Erreur de chargement.</div>'; return; }
    articles = r.articles || [];
    $('statTotal').textContent = r.total || 0;
    $('statMonth').textContent = r.month || 0;
    if(!articles.length){ $('articlesList').innerHTML = '<div class="text-center py-5 text-muted"><i class="fas fa-newspaper fa-3x mb-3"></i><p>Aucun article disponible.</p></div>'; return; }
    
    $('articlesList').innerHTML = articles.map(a => `
        <div class="art-card">
            ${IS_LOGGED ? `<div class="art-actions">
                <button class="btn-sm-edit" onclick="openEditArtFromList(${a.id_article}, event)"><i class="fas fa-edit"></i></button>
                <button class="btn-sm-del" onclick="confirmDelArtFromList(${a.id_article}, '${esc(a.titre).replace(/'/g, "\\'")}', event)"><i class="fas fa-trash"></i></button>
            </div>` : ''}
            <h2 class="art-title"><a href="#" onclick="openArticle(${a.id_article}); return false">${esc(a.titre)}</a></h2>
            <div class="art-meta">
                <span><i class="fas fa-user"></i> ${esc(a.auteur) || 'Valorys'}</span>
                <span><i class="fas fa-calendar-alt"></i> ${fmtDate(a.date_creation)}</span>
                <span><i class="fas fa-comment"></i> ${a.nb_replies || 0} commentaire(s)</span>
            </div>
            <div class="art-excerpt">${trunc(esc(a.contenu), 200)}</div>
            <div class="art-footer">
                <a href="#" class="btn-read" onclick="openArticle(${a.id_article}); return false">Lire la suite <i class="fas fa-arrow-right ms-1"></i></a>
            </div>
        </div>
    `).join('');
}

function searchArticles(){
    const q = $('searchInput').value.toLowerCase();
    if(!q){ loadArticles(); return; }
    const filtered = articles.filter(a => (a.titre||'').toLowerCase().includes(q) || (a.contenu||'').toLowerCase().includes(q));
    if(!filtered.length){ $('articlesList').innerHTML = '<div class="text-center text-muted py-4">Aucun résultat.</div>'; return; }
    $('articlesList').innerHTML = filtered.map(a => `
        <div class="art-card">
            ${IS_LOGGED ? `<div class="art-actions">
                <button class="btn-sm-edit" onclick="openEditArtFromList(${a.id_article}, event)"><i class="fas fa-edit"></i></button>
                <button class="btn-sm-del" onclick="confirmDelArtFromList(${a.id_article}, '${esc(a.titre).replace(/'/g, "\\'")}', event)"><i class="fas fa-trash"></i></button>
            </div>` : ''}
            <h2 class="art-title"><a href="#" onclick="openArticle(${a.id_article}); return false">${esc(a.titre)}</a></h2>
            <div class="art-meta"><span><i class="fas fa-user"></i> ${esc(a.auteur) || 'Valorys'}</span><span><i class="fas fa-calendar-alt"></i> ${fmtDate(a.date_creation)}</span></div>
            <div class="art-excerpt">${trunc(esc(a.contenu), 200)}</div>
            <div class="art-footer"><a href="#" class="btn-read" onclick="openArticle(${a.id_article}); return false">Lire <i class="fas fa-arrow-right ms-1"></i></a></div>
        </div>
    `).join('');
}

$('searchInput').addEventListener('keypress', e => { if(e.key === 'Enter') searchArticles(); });

async function openArticle(id){
    const r = await apiGet({ page: 'api_article', id });
    if(!r.success){ toast('Article introuvable', 'err'); return; }
    currentArt = r.article;
    const replies = r.replies || [];
    
    $('viewList').style.display = 'none';
    $('viewDetail').style.display = '';
    $('detailTitle').textContent = currentArt.titre;
    $('detailAuteur').textContent = currentArt.auteur || 'Valorys';
    $('detailDate').textContent = fmtDate(currentArt.date_creation);
    $('detailNbRep').textContent = replies.length;
    $('detailBody').innerHTML = esc(currentArt.contenu).replace(/\n/g, '<br>');
    
    $('artOwnerActions').style.display = IS_LOGGED ? 'block' : 'none';
    renderComments(replies);
    window.scrollTo(0,0);
}

function showList(){
    $('viewList').style.display = '';
    $('viewDetail').style.display = 'none';
    currentArt = null;
    loadArticles();
}

function renderComments(replies){
    $('repCount').textContent = replies.length;
    if(!replies.length){ $('commentsContainer').innerHTML = '<p class="text-muted text-center py-3">Aucun commentaire. Soyez le premier !</p>'; return; }
    $('commentsContainer').innerHTML = replies.map(r => {
        let content = '';
        if(r.type_reply === 'emoji') content = `<span style="font-size:28px">${esc(r.emoji)}</span>`;
        else if(r.type_reply === 'photo') content = `<img src="${esc(r.photo)}" style="max-width:100%;border-radius:10px;margin-top:6px">`;
        else content = esc(r.contenu_text || '').replace(/\n/g, '<br>');
        
        const actions = IS_LOGGED ? `<div class="c-actions">
            <button class="btn-sm-edit" onclick="openEditRepModal(${r.id_reply})"><i class="fas fa-edit me-1"></i>Modifier</button>
            <button class="btn-sm-del" onclick="confirmDelRep(${r.id_reply})"><i class="fas fa-trash me-1"></i>Supprimer</button>
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

function toggleNewRepFields(){
    const t = $('newRepType').value;
    $('nfText').style.display = t === 'text' ? '' : 'none';
    $('nfEmoji').style.display = t === 'emoji' ? '' : 'none';
    $('nfPhoto').style.display = t === 'photo' ? '' : 'none';
}

async function postComment(){
    if(!IS_LOGGED || !currentArt) return;
    clrErrMsg(['eNewRepText','eNewRepEmoji','eNewRepPhoto']);
    const type = $('newRepType').value;
    let contenu_text = null, emoji = null, photo = null, ok = true;
    if(type === 'text'){ contenu_text = $('newRepText').value.trim(); if(!contenu_text){ setErr('eNewRepText','Le texte est obligatoire.'); ok=false; } }
    else if(type === 'emoji'){ emoji = $('newRepEmoji').value.trim(); if(!emoji){ setErr('eNewRepEmoji',"L'emoji est obligatoire."); ok=false; } }
    else{ photo = $('newRepPhoto').value.trim(); if(!photo){ setErr('eNewRepPhoto',"L'URL est obligatoire."); ok=false; } }
    if(!ok) return;
    
    const r = await apiPost({ page: 'api_reply' }, { id_article: currentArt.id_article, type_reply: type, contenu_text, emoji, photo, auteur: SESSION_NAME });
    if(!r.success){ toast(r.message || 'Erreur', 'err'); return; }
    toast('Commentaire publié !', 'ok');
    $('newRepText').value = ''; $('newRepEmoji').value = ''; $('newRepPhoto').value = '';
    const r2 = await apiGet({ page: 'api_article', id: currentArt.id_article });
    if(r2.success) renderComments(r2.replies || []);
}

function toggleEditRepFields(){
    const t = $('editRepType').value;
    $('efText').style.display = t === 'text' ? '' : 'none';
    $('efEmoji').style.display = t === 'emoji' ? '' : 'none';
    $('efPhoto').style.display = t === 'photo' ? '' : 'none';
}

async function openEditRepModal(repId){
    if(!IS_LOGGED) return;
    const r = await apiGet({ page: 'api_reply', id: repId });
    if(!r.success || !r.reply){ toast('Commentaire introuvable', 'err'); return; }
    $('editRepId').value = repId;
    $('editRepType').value = r.reply.type_reply;
    $('editRepText').value = r.reply.contenu_text || '';
    $('editRepEmoji').value = r.reply.emoji || '';
    $('editRepPhoto').value = r.reply.photo || '';
    clrErrMsg(['eEditRepText','eEditRepEmoji','eEditRepPhoto']);
    toggleEditRepFields();
    openOv('ovEditRep');
}

async function saveEditRep(){
    if(!IS_LOGGED) return;
    const id = $('editRepId').value;
    const type = $('editRepType').value;
    let contenu_text = null, emoji = null, photo = null, ok = true;
    clrErrMsg(['eEditRepText','eEditRepEmoji','eEditRepPhoto']);
    if(type === 'text'){ contenu_text = $('editRepText').value.trim(); if(!contenu_text){ setErr('eEditRepText','Le texte est obligatoire.'); ok=false; } }
    else if(type === 'emoji'){ emoji = $('editRepEmoji').value.trim(); if(!emoji){ setErr('eEditRepEmoji',"L'emoji est obligatoire."); ok=false; } }
    else{ photo = $('editRepPhoto').value.trim(); if(!photo){ setErr('eEditRepPhoto',"L'URL est obligatoire."); ok=false; } }
    if(!ok) return;
    
    const r = await apiPost({ page: 'api_reply', id }, { id_article: currentArt.id_article, type_reply: type, contenu_text, emoji, photo, auteur: SESSION_NAME, _method: 'PUT' });
    if(!r.success){ toast(r.message || 'Erreur', 'err'); return; }
    closeOv('ovEditRep');
    toast('Commentaire modifié !', 'ok');
    const r2 = await apiGet({ page: 'api_article', id: currentArt.id_article });
    if(r2.success) renderComments(r2.replies || []);
}

function confirmDelRep(repId){
    if(!IS_LOGGED) return;
    $('delTitle').textContent = 'Supprimer ce commentaire ?';
    $('delSub').textContent = 'Cette action est irréversible.';
    $('delBtn').onclick = async () => {
        const r = await apiPost({ page: 'api_reply', id: repId }, { _method: 'DELETE' });
        closeOv('ovDel');
        toast(r.success ? 'Commentaire supprimé.' : (r.message || 'Erreur'), r.success ? 'ok' : 'err');
        if(!r.success) return;
        const r2 = await apiGet({ page: 'api_article', id: currentArt.id_article });
        if(r2.success) renderComments(r2.replies || []);
    };
    openOv('ovDel');
}

async function postArticle(){
    if(!IS_LOGGED) return;
    clrErrMsg(['eNewArtTitre','eNewArtContenu']);
    const titre = $('newArtTitre').value.trim();
    const contenu = $('newArtContenu').value.trim();
    let ok = true;
    if(!titre){ setErr('eNewArtTitre','Le titre est obligatoire.'); ok=false; }
    if(!contenu){ setErr('eNewArtContenu','Le contenu est obligatoire.'); ok=false; }
    if(!ok) return;
    
    const r = await apiPost({ page: 'api_article' }, { titre, contenu, auteur: SESSION_NAME });
    if(!r.success){ toast(r.message || 'Erreur', 'err'); return; }
    toast('Article publié !', 'ok');
    $('newArtTitre').value = '';
    $('newArtContenu').value = '';
    loadArticles();
}

let editArtId = null;

function openEditArtModal(){
    if(!IS_LOGGED || !currentArt) return;
    editArtId = currentArt.id_article;
    $('editArtTitre').value = currentArt.titre;
    $('editArtContenu').value = currentArt.contenu;
    clrErrMsg(['eEditArtTitre','eEditArtContenu']);
    openOv('ovEditArt');
}

async function openEditArtFromList(id, e){
    e.stopPropagation();
    if(!IS_LOGGED) return;
    const r = await apiGet({ page: 'api_article', id });
    if(!r.success){ toast('Introuvable', 'err'); return; }
    editArtId = id;
    $('editArtTitre').value = r.article.titre;
    $('editArtContenu').value = r.article.contenu;
    clrErrMsg(['eEditArtTitre','eEditArtContenu']);
    openOv('ovEditArt');
}

async function saveEditArt(){
    if(!IS_LOGGED || !editArtId) return;
    clrErrMsg(['eEditArtTitre','eEditArtContenu']);
    const titre = $('editArtTitre').value.trim();
    const contenu = $('editArtContenu').value.trim();
    let ok = true;
    if(!titre){ setErr('eEditArtTitre','Le titre est obligatoire.'); ok=false; }
    if(!contenu){ setErr('eEditArtContenu','Le contenu est obligatoire.'); ok=false; }
    if(!ok) return;
    
    const r = await apiPost({ page: 'api_article', id: editArtId }, { titre, contenu, _method: 'PUT' });
    if(!r.success){ toast(r.message || 'Erreur', 'err'); return; }
    closeOv('ovEditArt');
    toast('Article modifié !', 'ok');
    if(currentArt && currentArt.id_article === editArtId){
        const r2 = await apiGet({ page: 'api_article', id: editArtId });
        if(r2.success){ currentArt = r2.article; $('detailTitle').textContent = currentArt.titre; $('detailBody').innerHTML = esc(currentArt.contenu).replace(/\n/g, '<br>'); }
    }
    loadArticles();
}

function confirmDelArt(){
    if(!IS_LOGGED || !currentArt) return;
    $('delTitle').textContent = `Supprimer "${currentArt.titre}" ?`;
    $('delSub').textContent = 'Tous les commentaires liés seront supprimés.';
    $('delBtn').onclick = async () => {
        const r = await apiPost({ page: 'api_article', id: currentArt.id_article }, { _method: 'DELETE' });
        closeOv('ovDel');
        toast(r.success ? 'Article supprimé.' : (r.message || 'Erreur'), r.success ? 'ok' : 'err');
        if(r.success) showList();
    };
    openOv('ovDel');
}

function confirmDelArtFromList(id, titre, e){
    e.stopPropagation();
    $('delTitle').textContent = `Supprimer "${titre}" ?`;
    $('delSub').textContent = 'Tous les commentaires liés seront supprimés.';
    $('delBtn').onclick = async () => {
        const r = await apiPost({ page: 'api_article', id }, { _method: 'DELETE' });
        closeOv('ovDel');
        toast(r.success ? 'Article supprimé.' : (r.message || 'Erreur'), r.success ? 'ok' : 'err');
        if(r.success) loadArticles();
    };
    openOv('ovDel');
}

loadArticles();
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof tinymce !== 'undefined') {
        tinymce.init({
            selector: '#editArtContenu',
            height: 320,
            width: '100%',
            language: 'fr_FR',
            theme: 'silver',
            toolbar: 'undo redo | bold italic underline strikethrough | forecolor backcolor | link image media | alignleft aligncenter alignright alignjustify | bullist numlist | emoticons | removeformat | fullscreen',
            plugins: 'link image media lists emoticons paste',
            menubar: false,
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
            }
        });
    }
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>