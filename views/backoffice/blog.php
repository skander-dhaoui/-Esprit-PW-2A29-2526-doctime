<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Valorys — Gestion Blog</title>
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
        .stat-row{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:24px}
        .stat{background:#fff;border-radius:16px;padding:18px 20px;box-shadow:0 2px 8px rgba(0,0,0,.05);border-left:4px solid}
        .stat h3{font-size:28px;font-weight:700;color:#0f2b3d;margin:8px 0 2px}
        .stat p{font-size:12px;color:#6c757d}
        .stat i.big{float:right;font-size:36px;opacity:.15}
        .tab-bar{display:flex;gap:8px;margin-bottom:20px}
        .tab-btn{padding:9px 20px;border-radius:40px;border:2px solid #e9ecef;background:#fff;font-size:13px;font-weight:600;cursor:pointer;transition:.2s;color:#6c757d}
        .tab-btn.active{background:#2A7FAA;border-color:#2A7FAA;color:#fff}
        .card-box{background:#fff;border-radius:16px;padding:20px;box-shadow:0 2px 8px rgba(0,0,0,.05);margin-bottom:24px}
        .card-box-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:16px}
        .card-box-header h5{font-size:15px;font-weight:700;color:#0f2b3d}
        .table thead th{font-size:11px;font-weight:700;color:#6c757d;background:#f8f9fc;padding:10px 14px;white-space:nowrap;text-transform:uppercase;letter-spacing:.5px}
        .table tbody td{padding:12px 14px;font-size:13px;vertical-align:middle}
        .table tbody tr{border-bottom:1px solid #f0f4f8;transition:.1s}
        .table tbody tr:hover{background:#f8f9fc}
        .badge-type{padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700}
        .badge-text{background:#e3f2fd;color:#1565c0}
        .badge-emoji{background:#fff3e0;color:#e65100}
        .badge-photo{background:#e8f5e9;color:#2e7d32}
        .action-btns{display:flex;gap:6px}
        .btn-act{border:none;border-radius:8px;padding:5px 10px;font-size:12px;cursor:pointer;transition:.2s;font-weight:600}
        .btn-edit{background:#e3f2fd;color:#1565c0}.btn-edit:hover{background:#1565c0;color:#fff}
        .btn-del{background:#fce4ec;color:#c62828}.btn-del:hover{background:#c62828;color:#fff}
        .btn-view{background:#e8f5e9;color:#2e7d32}.btn-view:hover{background:#2e7d32;color:#fff}
        .btn-primary-custom{background:linear-gradient(135deg,#2A7FAA,#4CAF50);color:#fff;border:none;border-radius:30px;padding:9px 20px;font-size:13px;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:6px;transition:opacity .2s;box-shadow:0 4px 10px rgba(42,127,170,.3)}
        .btn-primary-custom:hover{opacity:.9}
        .overlay{display:none;position:fixed;inset:0;background:rgba(15,43,61,.6);z-index:500;align-items:center;justify-content:center;padding:16px}
        .overlay.open{display:flex}
        .modal-box{background:#fff;border-radius:20px;width:100%;max-width:540px;max-height:90vh;overflow-y:auto;animation:pop .22s ease;box-shadow:0 20px 60px rgba(0,0,0,.2)}
        @keyframes pop{from{opacity:0;transform:scale(.95)}to{opacity:1;transform:scale(1)}}
        .mh{padding:18px 22px;border-bottom:1px solid #eee;display:flex;justify-content:space-between;align-items:center}
        .mh h5{font-size:15px;font-weight:700;color:#0f2b3d}
        .mh button{background:none;border:none;font-size:18px;color:#999;cursor:pointer}
        .mb{padding:20px 22px;display:flex;flex-direction:column;gap:14px}
        .mf{padding:14px 22px;border-top:1px solid #eee;display:flex;justify-content:flex-end;gap:8px}
        .fg{display:flex;flex-direction:column;gap:4px}
        .fg label{font-size:11px;font-weight:700;color:#6c757d;text-transform:uppercase}
        .fg input,.fg textarea,.fg select{padding:9px 12px;border:1.5px solid #e9ecef;border-radius:10px;font-size:13px;font-family:inherit;outline:none;transition:.15s;background:#f8f9fc;color:#0f2b3d}
        .fg input:focus,.fg textarea:focus,.fg select:focus{border-color:#2A7FAA;background:#fff}
        .fg textarea{min-height:90px;resize:vertical}
        .fg .err-msg{font-size:11px;color:#dc2626;min-height:14px}
        .fg input.err,.fg textarea.err,.fg select.err{border-color:#dc2626}
        .btn-cancel{padding:9px 18px;border-radius:30px;border:1.5px solid #e9ecef;background:#fff;font-size:13px;font-weight:600;color:#6c757d;cursor:pointer}
        .btn-save{padding:9px 22px;border-radius:30px;border:none;background:linear-gradient(135deg,#2A7FAA,#4CAF50);color:#fff;font-size:13px;font-weight:600;cursor:pointer}
        .btn-save:disabled{opacity:.6;cursor:not-allowed}
        .btn-danger{padding:9px 20px;border-radius:30px;border:none;background:#dc3545;color:#fff;font-size:13px;font-weight:600;cursor:pointer}
        .confirm-body{text-align:center;padding:28px 22px}
        .confirm-body .ico{font-size:40px;margin-bottom:10px}
        .confirm-body h5{font-size:15px;font-weight:700;color:#0f2b3d;margin-bottom:4px}
        .confirm-body p{font-size:13px;color:#6c757d}
        .back-link{display:inline-flex;align-items:center;gap:6px;font-size:13px;font-weight:600;color:#2A7FAA;cursor:pointer;background:#e3f2fd;border:none;padding:7px 16px;border-radius:30px;transition:.2s}
        .back-link:hover{background:#bbdefb}
        .spinner{display:inline-block;width:16px;height:16px;border:2.5px solid #e9ecef;border-top-color:#2A7FAA;border-radius:50%;animation:spin .7s linear infinite;vertical-align:middle;margin-right:6px}
        @keyframes spin{to{transform:rotate(360deg)}}
        .loader-row td{text-align:center;padding:40px;color:#6c757d;font-size:13px}
        .toast{position:fixed;bottom:20px;right:20px;background:#0f2b3d;color:#fff;padding:10px 20px;border-radius:12px;font-size:13px;font-weight:500;display:none;z-index:999;box-shadow:0 8px 24px rgba(0,0,0,.2)}
        .toast.show{display:block;animation:slideup .25s ease}
        .toast.err{background:#991b1b}
        .toast.ok{background:#166534}
        @keyframes slideup{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:translateY(0)}}
        @media(max-width:992px){.sidebar{width:70px}.sidebar-header .logo,.sidebar-header small,.sidebar-menu a span{display:none}.sidebar-menu a{justify-content:center;padding:14px 0}.main{margin-left:70px}}
    </style>
</head>
<body>
<div class="sidebar">
    <div class="sidebar-header"><div class="logo">Valorys</div><small>Back Office</small></div>
    <div class="sidebar-menu">
        <a href="index.php?page=dashboard"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a>
        <a href="index.php?page=users"><i class="fas fa-users"></i><span>Utilisateurs</span></a>
        <a href="index.php?page=medecins_admin"><i class="fas fa-user-md"></i><span>Médecins</span></a>
        <a href="index.php?page=patients"><i class="fas fa-user-injured"></i><span>Patients</span></a>
        <a href="index.php?page=rendez_vous_admin"><i class="fas fa-calendar-check"></i><span>Rendez-vous</span></a>
        <a href="index.php?page=blog" class="active"><i class="fas fa-blog"></i><span>Blog / Forum</span></a>
        <a href="index.php?page=stats"><i class="fas fa-chart-line"></i><span>Statistiques</span></a>
        <a href="index.php?page=settings"><i class="fas fa-cog"></i><span>Paramètres</span></a>
        <a href="index.php?page=logout" style="margin-top:10px"><i class="fas fa-sign-out-alt"></i><span>Déconnexion</span></a>
    </div>
</div>

<div class="main">
    <div class="topbar">
        <div class="topbar-title"><i class="fas fa-blog me-2" style="color:#2A7FAA"></i>Gestion Blog & Forum</div>
        <div class="admin-badge">
            <div class="avatar"><?= strtoupper(substr($_SESSION['user_name'] ?? 'A', 0, 1)) ?></div>
            <span><?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin') ?></span>
            <a href="index.php?page=blog_public" target="_blank" class="btn-act btn-view ms-2"><i class="fas fa-eye me-1"></i>Voir le blog</a>
        </div>
    </div>

    <div class="stat-row">
        <div class="stat" style="border-left-color:#2A7FAA"><i class="fas fa-newspaper big"></i><p>Total articles</p><h3 id="sTotal"><span class="spinner"></span></h3></div>
        <div class="stat" style="border-left-color:#4CAF50"><i class="fas fa-comments big"></i><p>Total commentaires</p><h3 id="sReplies"><span class="spinner"></span></h3></div>
        <div class="stat" style="border-left-color:#ffc107"><i class="fas fa-calendar big"></i><p>Ce mois</p><h3 id="sMonth"><span class="spinner"></span></h3></div>
    </div>

    <div class="tab-bar">
        <button class="tab-btn active" id="tabArt" onclick="switchTab('articles')"><i class="fas fa-newspaper me-1"></i>Articles</button>
        <button class="tab-btn" id="tabRep" onclick="switchTab('replies')"><i class="fas fa-comments me-1"></i>Commentaires</button>
    </div>

    <!-- PANEL ARTICLES -->
    <div id="panelArticles">
        <div class="card-box">
            <div class="card-box-header">
                <h5><i class="fas fa-list me-2"></i>Liste des articles</h5>
                <button class="btn-primary-custom" onclick="openArtModal()"><i class="fas fa-plus"></i> Nouvel article</button>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Titre</th><th>Contenu</th><th>Auteur</th><th>Date</th><th>Commentaires</th><th>Actions</th></tr></thead>
                    <tbody id="artBody"><tr class="loader-row"><td colspan="6"><span class="spinner"></span>Chargement...</td></tr></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- PANEL COMMENTAIRES -->
    <div id="panelReplies" style="display:none">
        <div class="card-box">
            <div class="card-box-header">
                <h5 id="repTitle"><i class="fas fa-comments me-2"></i>Tous les commentaires</h5>
                <div class="d-flex gap-2">
                    <button class="back-link" id="backBtn" style="display:none" onclick="showAllReplies()"><i class="fas fa-arrow-left"></i> Tous</button>
                    <button class="btn-primary-custom" onclick="openRepModal(null)"><i class="fas fa-plus"></i> Nouveau commentaire</button>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Article</th><th>Type</th><th>Contenu</th><th>Auteur</th><th>Date</th><th>Actions</th></tr></thead>
                    <tbody id="repBody"><tr class="loader-row"><td colspan="6"><span class="spinner"></span>Chargement...</td></tr></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- MODAL ARTICLE -->
<div class="overlay" id="ovArt">
    <div class="modal-box">
        <div class="mh"><h5 id="mArtTitle">Nouvel article</h5><button onclick="closeOv('ovArt')">✕</button></div>
        <div class="mb">
            <input type="hidden" id="artId">
            <div class="fg"><label>Titre *</label><input id="artTitre" placeholder="Titre de l'article"><span class="err-msg" id="eArtTitre"></span></div>
            <div class="fg"><label>Contenu *</label><textarea id="artContenu" placeholder="Contenu de l'article..."></textarea><span class="err-msg" id="eArtContenu"></span></div>
            <div class="fg"><label>Auteur</label><input id="artAuteur" placeholder="Nom de l'auteur (optionnel)"></div>
        </div>
        <div class="mf">
            <button class="btn-cancel" onclick="closeOv('ovArt')">Annuler</button>
            <button class="btn-save" id="btnSaveArt" onclick="saveArt()"><i class="fas fa-save me-1"></i>Enregistrer</button>
        </div>
    </div>
</div>

<!-- MODAL COMMENTAIRE -->
<div class="overlay" id="ovRep">
    <div class="modal-box">
        <div class="mh"><h5 id="mRepTitle">Nouveau commentaire</h5><button onclick="closeOv('ovRep')">✕</button></div>
        <div class="mb">
            <input type="hidden" id="repId">
            <div class="fg"><label>Article *</label><select id="repArticle"><option value="">-- Choisir --</option></select><span class="err-msg" id="eRepArticle"></span></div>
            <div class="fg"><label>Type *</label><select id="repType" onchange="toggleRepFields()"><option value="text">💬 Texte</option><option value="emoji">😊 Emoji</option><option value="photo">🖼️ Photo URL</option></select></div>
            <div class="fg" id="fRepText"><label>Commentaire *</label><textarea id="repText" placeholder="Votre commentaire..."></textarea><span class="err-msg" id="eRepText"></span></div>
            <div class="fg" id="fRepEmoji" style="display:none"><label>Emoji *</label><input id="repEmoji" placeholder="😊"><span class="err-msg" id="eRepEmoji"></span></div>
            <div class="fg" id="fRepPhoto" style="display:none"><label>URL Photo *</label><input id="repPhoto" placeholder="https://..."><span class="err-msg" id="eRepPhoto"></span></div>
            <div class="fg"><label>Auteur</label><input id="repAuteur" placeholder="Nom (optionnel)"></div>
        </div>
        <div class="mf">
            <button class="btn-cancel" onclick="closeOv('ovRep')">Annuler</button>
            <button class="btn-save" id="btnSaveRep" onclick="saveRep()"><i class="fas fa-paper-plane me-1"></i>Publier</button>
        </div>
    </div>
</div>

<!-- MODAL CONFIRM -->
<div class="overlay" id="ovDel">
    <div class="modal-box" style="max-width:360px">
        <div class="confirm-body"><div class="ico">🗑️</div><h5 id="delTitle">Supprimer ?</h5><p id="delSub">Cette action est irréversible.</p></div>
        <div class="mf"><button class="btn-cancel" onclick="closeOv('ovDel')">Annuler</button><button class="btn-danger" id="delBtn"><i class="fas fa-trash me-1"></i>Supprimer</button></div>
    </div>
</div>

<div class="toast" id="toast"></div>

<script>
const $=id=>document.getElementById(id);
const esc=s=>{if(!s)return'';const d=document.createElement('div');d.textContent=s;return d.innerHTML};
const trunc=(s,n=50)=>s&&s.length>n?s.slice(0,n)+'…':(s||'—');
const fmtDate=d=>d?new Date(d).toLocaleDateString('fr-FR'):'—';
const fmtDT=d=>d?new Date(d).toLocaleString('fr-FR'):'—';

function toast(msg,type='ok'){const t=$('toast');t.textContent=msg;t.className='toast show '+type;clearTimeout(t._t);t._t=setTimeout(()=>t.classList.remove('show'),3000)}
function closeOv(id){$(id).classList.remove('open')}
function openOv(id){$(id).classList.add('open')}
document.querySelectorAll('.overlay').forEach(o=>o.addEventListener('click',e=>{if(e.target===o)o.classList.remove('open')}));
function clrErr(){document.querySelectorAll('.err-msg').forEach(e=>e.textContent='');document.querySelectorAll('.err').forEach(e=>e.classList.remove('err'))}
function setErr(id,msg){const e=$(id);if(e)e.textContent=msg}
function setInvalid(id){const e=$(id);if(e)e.classList.add('err')}

async function apiGet(params){const r=await fetch('index.php?'+new URLSearchParams(params),{headers:{Accept:'application/json'}});return r.json()}
async function apiPost(params,body){const r=await fetch('index.php?'+new URLSearchParams(params),{method:'POST',headers:{'Content-Type':'application/json',Accept:'application/json'},body:JSON.stringify(body)});return r.json()}

// TABS
function switchTab(tab){
    $('tabArt').classList.toggle('active',tab==='articles');
    $('tabRep').classList.toggle('active',tab==='replies');
    $('panelArticles').style.display=tab==='articles'?'':'none';
    $('panelReplies').style.display=tab==='replies'?'':'none';
    if(tab==='replies')loadAllReplies();
}

// STATS
async function loadStats(){
    const r=await apiGet({page:'api_article',list:1});
    if(r.success){$('sTotal').textContent=r.total??0;$('sMonth').textContent=r.month??0}
    const r2=await apiGet({page:'api_reply',all:1});
    $('sReplies').textContent=r2.success?(r2.total??0):'—';
}

// ════ ARTICLES ════
let allArticles=[];
async function loadArticles(){
    $('artBody').innerHTML='<tr class="loader-row"><td colspan="6"><span class="spinner"></span>Chargement...</td></tr>';
    const r=await apiGet({page:'api_article',list:1});
    if(!r.success){$('artBody').innerHTML='<tr class="loader-row"><td colspan="6">Erreur de chargement.</td></tr>';return}
    allArticles=r.articles||[];
    if(!allArticles.length){$('artBody').innerHTML='<tr class="loader-row"><td colspan="6">Aucun article.</td></tr>';return}
    $('artBody').innerHTML=allArticles.map(a=>`
        <tr>
            <td style="font-weight:600">${trunc(esc(a.titre),40)}</td>
            <td style="color:#6c757d;font-size:12px">${trunc(esc(a.contenu),60)}</td>
            <td style="font-size:12px;color:#6c757d">${esc(a.auteur)||'—'}</td>
            <td style="font-size:12px;color:#6c757d">${fmtDate(a.date_creation)}</td>
            <td><button class="btn-act btn-view" onclick="viewRepliesOf(${a.id_article},'${esc(a.titre).replace(/'/g,"\\'")}')"><i class="fas fa-comment me-1"></i>${a.nb_replies||0}</button></td>
            <td><div class="action-btns">
                <button class="btn-act btn-edit" onclick="openArtModal(${a.id_article})"><i class="fas fa-edit"></i></button>
                <button class="btn-act btn-del" onclick="confirmDel('article',${a.id_article},'${esc(a.titre).replace(/'/g,"\\'")}')"><i class="fas fa-trash"></i></button>
            </div></td>
        </tr>`).join('');
}

async function openArtModal(id=null){
    clrErr();
    ['artId','artTitre','artContenu','artAuteur'].forEach(x=>$(x).value='');
    if(id){
        $('mArtTitle').textContent="Modifier l'article";
        const r=await apiGet({page:'api_article',id});
        if(!r.success){toast('Article introuvable','err');return}
        const a=r.article;
        $('artId').value=a.id_article;$('artTitre').value=a.titre;$('artContenu').value=a.contenu;$('artAuteur').value=a.auteur||'';
    } else {
        $('mArtTitle').textContent='Nouvel article';
    }
    openOv('ovArt');
}

async function saveArt(){
    clrErr();
    const titre=$('artTitre').value.trim();
    const contenu=$('artContenu').value.trim();
    const auteur=$('artAuteur').value.trim()||null;
    const id=$('artId').value;
    let ok=true;
    if(!titre){setErr('eArtTitre','Le titre est obligatoire.');setInvalid('artTitre');ok=false}
    if(!contenu){setErr('eArtContenu','Le contenu est obligatoire.');setInvalid('artContenu');ok=false}
    if(!ok)return;
    const btn=$('btnSaveArt');btn.disabled=true;btn.innerHTML='<span class="spinner"></span>';
    const payload={titre,contenu,auteur};
    const r=id?await apiPost({page:'api_article',id},{...payload,_method:'PUT'}):await apiPost({page:'api_article'},payload);
    btn.disabled=false;btn.innerHTML='<i class="fas fa-save me-1"></i>Enregistrer';
    if(!r.success){toast(r.message||'Erreur','err');if(r.errors)Object.entries(r.errors).forEach(([k,v])=>setErr('eArt'+k[0].toUpperCase()+k.slice(1),v));return}
    closeOv('ovArt');toast(r.message||'Succès','ok');loadArticles();loadStats();
}

// ════ COMMENTAIRES ════
let currentArtFilter=null;
async function loadAllReplies(){
    currentArtFilter=null;
    $('repTitle').innerHTML='<i class="fas fa-comments me-2"></i>Tous les commentaires';
    $('backBtn').style.display='none';
    $('repBody').innerHTML='<tr class="loader-row"><td colspan="6"><span class="spinner"></span>Chargement...</td></tr>';
    const r=await apiGet({page:'api_reply',all:1});
    if(!r.success){$('repBody').innerHTML='<tr class="loader-row"><td colspan="6">Erreur.</td></tr>';return}
    renderReplies(r.replies||[]);
}

async function showAllReplies(){await loadAllReplies()}

async function viewRepliesOf(artId,artTitle){
    switchTab('replies');
    currentArtFilter=artId;
    $('repTitle').innerHTML=`<i class="fas fa-comments me-2"></i>Commentaires — ${artTitle}`;
    $('backBtn').style.display='';
    $('repBody').innerHTML='<tr class="loader-row"><td colspan="6"><span class="spinner"></span>Chargement...</td></tr>';
    const r=await apiGet({page:'api_article',id:artId});
    if(!r.success){$('repBody').innerHTML='<tr class="loader-row"><td colspan="6">Erreur.</td></tr>';return}
    renderReplies(r.replies||[]);
}

function renderReplies(replies){
    if(!replies.length){$('repBody').innerHTML='<tr class="loader-row"><td colspan="6">Aucun commentaire.</td></tr>';return}
    $('repBody').innerHTML=replies.map(r=>{
        const badge=r.type_reply==='emoji'?'<span class="badge-type badge-emoji">Emoji</span>':r.type_reply==='photo'?'<span class="badge-type badge-photo">Photo</span>':'<span class="badge-type badge-text">Texte</span>';
        const content=r.type_reply==='emoji'?`<span style="font-size:20px">${esc(r.emoji)}</span>`:r.type_reply==='photo'?`<img src="${esc(r.photo)}" style="max-height:40px;border-radius:6px">`:trunc(esc(r.contenu_text||''),60);
        const art=allArticles.find(a=>a.id_article==r.id_article);
        const artLabel=art?trunc(esc(art.titre),30):`#${r.id_article}`;
        return`<tr>
            <td style="font-size:12px">${artLabel}</td>
            <td>${badge}</td>
            <td>${content}</td>
            <td style="font-size:12px;color:#6c757d">${esc(r.auteur)||'—'}</td>
            <td style="font-size:12px;color:#6c757d">${fmtDT(r.date_reply)}</td>
            <td><div class="action-btns">
                <button class="btn-act btn-edit" onclick="openRepModal(${r.id_reply})"><i class="fas fa-edit"></i></button>
                <button class="btn-act btn-del" onclick="confirmDel('reply',${r.id_reply},'ce commentaire')"><i class="fas fa-trash"></i></button>
            </div></td>
        </tr>`;
    }).join('');
}

async function openRepModal(id=null){
    clrErr();
    const sel=$('repArticle');
    sel.innerHTML='<option value="">-- Choisir --</option>';
    if(!allArticles.length){const r=await apiGet({page:'api_article',list:1});if(r.success)allArticles=r.articles||[]}
    allArticles.forEach(a=>{const o=document.createElement('option');o.value=a.id_article;o.textContent=`#${a.id_article} — ${a.titre.substring(0,50)}`;sel.appendChild(o)});
    ['repId','repText','repEmoji','repPhoto','repAuteur'].forEach(x=>$(x).value='');
    $('repType').value='text';toggleRepFields();
    if(currentArtFilter)sel.value=currentArtFilter;
    if(id){
        $('mRepTitle').textContent='Modifier le commentaire';
        const r=await apiGet({page:'api_reply',id});
        if(r.success&&r.reply){const rp=r.reply;$('repId').value=rp.id_reply;sel.value=rp.id_article;$('repType').value=rp.type_reply;$('repText').value=rp.contenu_text||'';$('repEmoji').value=rp.emoji||'';$('repPhoto').value=rp.photo||'';$('repAuteur').value=rp.auteur||'';toggleRepFields()}
    } else {
        $('mRepTitle').textContent='Nouveau commentaire';
    }
    openOv('ovRep');
}

function toggleRepFields(){const t=$('repType').value;$('fRepText').style.display=t==='text'?'':'none';$('fRepEmoji').style.display=t==='emoji'?'':'none';$('fRepPhoto').style.display=t==='photo'?'':'none'}

async function saveRep(){
    clrErr();
    const artId=$('repArticle').value,type=$('repType').value,auteur=$('repAuteur').value.trim()||null,id=$('repId').value;
    let ok=true,contenu_text=null,emoji=null,photo=null;
    if(!artId){setErr('eRepArticle','Choisissez un article.');setInvalid('repArticle');ok=false}
    if(type==='text'){contenu_text=$('repText').value.trim();if(!contenu_text){setErr('eRepText','Le texte est obligatoire.');setInvalid('repText');ok=false}}
    else if(type==='emoji'){emoji=$('repEmoji').value.trim();if(!emoji){setErr('eRepEmoji',"L'emoji est obligatoire.");setInvalid('repEmoji');ok=false}}
    else{photo=$('repPhoto').value.trim();if(!photo){setErr('eRepPhoto',"L'URL est obligatoire.");setInvalid('repPhoto');ok=false}}
    if(!ok)return;
    const btn=$('btnSaveRep');btn.disabled=true;btn.innerHTML='<span class="spinner"></span>';
    const payload={id_article:parseInt(artId),type_reply:type,contenu_text,emoji,photo,auteur};
    const r=id?await apiPost({page:'api_reply',id},{...payload,_method:'PUT'}):await apiPost({page:'api_reply'},payload);
    btn.disabled=false;btn.innerHTML='<i class="fas fa-paper-plane me-1"></i>Publier';
    if(!r.success){toast(r.message||'Erreur','err');return}
    closeOv('ovRep');toast(r.message||'Succès','ok');
    if(currentArtFilter)viewRepliesOf(currentArtFilter,'');else loadAllReplies();
    loadArticles();loadStats();
}

// SUPPRESSION
function confirmDel(type,id,label){
    $('delTitle').textContent=type==='article'?`Supprimer "${label}" ?`:`Supprimer ${label} ?`;
    $('delSub').textContent=type==='article'?'Tous les commentaires liés seront supprimés.':'Cette action est irréversible.';
    $('delBtn').onclick=async()=>{
        const r=await apiPost({page:type==='article'?'api_article':'api_reply',id},{_method:'DELETE'});
        closeOv('ovDel');toast(r.success?r.message:(r.message||'Erreur'),r.success?'ok':'err');
        if(!r.success)return;
        if(type==='article'){loadArticles()}else{if(currentArtFilter)viewRepliesOf(currentArtFilter,'');else loadAllReplies()}
        loadStats();
    };
    openOv('ovDel');
}

loadStats();loadArticles();
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>// update
