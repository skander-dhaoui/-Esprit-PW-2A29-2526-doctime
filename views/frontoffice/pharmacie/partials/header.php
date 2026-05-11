<?php
$pageTitle  = $pageTitle  ?? 'Parapharmacie - Valorys';
$activePage = $activePage ?? '';
$isLoggedIn = isset($_SESSION['user_id']);
$userName   = htmlspecialchars($_SESSION['user_name'] ?? 'Compte');
$userRole   = $_SESSION['user_role'] ?? 'guest';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <?php include __DIR__ . '/../../../partials/public_theme_styles.php'; ?>
    <style>
        /* Fond global (cohérent thème clair/sombre injecté par index.php) */
        body { font-family: 'Segoe UI', sans-serif; background: var(--bg-light, #f0f6ff); }
        #chatbot-conseiller { scroll-margin-top: 88px; }
        /* Bandeau optionnel (fiches produit / commandes module pharma) */
        .pharma-header {
            background: linear-gradient(135deg, #2A7FAA 0%, #4CAF50 100%);
            color: white;
            padding: 28px 0;
            text-align: center;
            margin-bottom: 24px;
        }

        /* ── Système de notifications intégré (sans popup browser) */
        #valo-toasts {
            position: fixed; top: 80px; right: 18px; z-index: 9999;
            display: flex; flex-direction: column; gap: 10px;
            max-width: 370px; pointer-events: none;
        }
        .valo-toast {
            display: flex; align-items: flex-start; gap: 12px;
            background: white; border-radius: 14px; padding: 14px 16px;
            box-shadow: 0 6px 24px rgba(0,0,0,.13); border-left: 5px solid;
            pointer-events: all; position: relative; overflow: hidden;
            animation: valo-in .3s cubic-bezier(.22,1,.36,1);
        }
        .valo-toast-s { border-color: #4CAF50; }
        .valo-toast-e { border-color: #f44336; }
        .valo-toast-w { border-color: #FF9800; }
        .valo-toast-icon { font-size: 20px; margin-top: 1px; }
        .valo-toast-s .valo-toast-icon { color: #4CAF50; }
        .valo-toast-e .valo-toast-icon { color: #f44336; }
        .valo-toast-w .valo-toast-icon { color: #FF9800; }
        .valo-toast-body { flex: 1; }
        .valo-toast-title { font-weight: 700; font-size: .88rem; color: #1a2035; }
        .valo-toast-msg   { font-size: .82rem; color: #555; margin-top: 2px; line-height: 1.4; }
        .valo-toast-x { background:none;border:none;cursor:pointer;color:#bbb;font-size:16px;padding:0;line-height:1; }
        .valo-toast-x:hover { color: #888; }
        .valo-toast-bar { position:absolute;bottom:0;left:5px;right:0;height:3px;animation:valo-bar linear forwards; }
        .valo-toast-s .valo-toast-bar { background: #4CAF50; }
        .valo-toast-e .valo-toast-bar { background: #f44336; }
        .valo-toast-w .valo-toast-bar { background: #FF9800; }

        /* Dialogue de confirmation */
        .valo-overlay { position:fixed;inset:0;z-index:10000;background:rgba(26,32,53,.5);backdrop-filter:blur(3px);display:flex;align-items:center;justify-content:center;animation:valo-fade .2s ease; }
        .valo-dialog  { background:white;border-radius:18px;padding:32px 28px;max-width:420px;width:90%;box-shadow:0 20px 60px rgba(0,0,0,.22);text-align:center;animation:valo-in .3s ease; }
        .valo-dialog-icon { width:56px;height:56px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:24px;margin:0 auto 16px; }
        .valo-dialog h5 { font-weight:700;color:#1a2035;margin-bottom:10px; }
        .valo-dialog p  { color:#555;font-size:.92rem;margin-bottom:24px; }
        .valo-dialog-btns { display:flex;gap:12px;justify-content:center; }
        .valo-btn-cancel { border:2px solid #e0e0e0;background:white;border-radius:10px;padding:9px 22px;font-weight:600;color:#666;cursor:pointer; }
        .valo-btn-ok     { border:none;border-radius:10px;padding:9px 22px;font-weight:600;color:white;cursor:pointer; }

        @keyframes valo-in  { from{opacity:0;transform:translateY(12px)} to{opacity:1;transform:translateY(0)} }
        @keyframes valo-fade{ from{opacity:0} to{opacity:1} }
        @keyframes valo-bar { from{width:100%} to{width:0} }
        @keyframes valo-out { to{opacity:0;transform:translateX(20px);max-height:0;padding:0;margin:0} }

        /* Assistant vocal : pile micro + clavier + panneau saisie */
        .pharma-voice-stack {
            position: fixed; bottom: 1.5rem; left: 1.5rem; z-index: 1030;
            display: flex; flex-direction: column; gap: 8px; align-items: center;
        }
        .pharma-voice-fab {
            width: 56px; height: 56px; border-radius: 50% !important;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.35rem; border: none; cursor: pointer;
            background: linear-gradient(135deg, #1a7fa8, #1db88e);
            color: #fff; box-shadow: 0 4px 18px rgba(26, 127, 168, 0.45);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .pharma-voice-fab--kbd {
            background: linear-gradient(135deg, #475569, #64748b);
            box-shadow: 0 4px 12px rgba(71, 85, 105, 0.35);
        }
        .pharma-voice-fab--disabled { opacity: 0.45; cursor: not-allowed; }
        .pharma-voice-fallback-panel {
            position: fixed; bottom: 9.5rem; left: 1rem; z-index: 1029;
            width: min(340px, calc(100vw - 2rem));
            background: #fff; border: 1px solid #e2e8f0; border-radius: 12px;
            padding: 12px 14px; box-shadow: 0 10px 30px rgba(0,0,0,.12);
        }
        .pharma-voice-fab:hover:not(:disabled) { transform: scale(1.05); color: #fff; }
        .pharma-voice-fab.is-listening {
            animation: pharma-voice-pulse 1.2s ease-in-out infinite;
            box-shadow: 0 0 0 0 rgba(29, 184, 142, 0.6);
        }
        @keyframes pharma-voice-pulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(29, 184, 142, 0.5); }
            50% { box-shadow: 0 0 0 12px rgba(29, 184, 142, 0); }
        }
        .pharma-voice-status {
            position: fixed; bottom: 5.2rem; left: 1rem; right: auto; max-width: 280px;
            z-index: 1029; display: none;
            background: rgba(26, 32, 53, 0.92); color: #f1f5f9; font-size: 0.85rem;
            padding: 10px 14px; border-radius: 12px; line-height: 1.4;
            box-shadow: 0 6px 20px rgba(0,0,0,.2);
        }

        <?php if (!empty($extraStyles)): ?><?= $extraStyles ?><?php endif; ?>
    </style>
</head>
<body>

<div id="valo-toasts"></div>

<?php
$navActive = $activePage ?? ($_GET['page'] ?? '');
include __DIR__ . '/../../../partials/nav_public.php';
?>

<script>
/* Système Valo — remplace confirm() et alert() browser */
const Valo = {
    _show(type, title, msg, ms=5000) {
        const icons={s:'fa-check-circle',e:'fa-times-circle',w:'fa-exclamation-triangle'};
        const c=document.getElementById('valo-toasts');
        const t=document.createElement('div');
        t.className=`valo-toast valo-toast-${type}`;
        t.innerHTML=`<span class="valo-toast-icon"><i class="fas ${icons[type]}"></i></span><div class="valo-toast-body"><div class="valo-toast-title">${title}</div><div class="valo-toast-msg">${msg}</div></div><button class="valo-toast-x" onclick="Valo._dismiss(this.closest('.valo-toast'))">×</button><div class="valo-toast-bar" style="animation-duration:${ms}ms"></div>`;
        c.appendChild(t);
        if(ms>0) setTimeout(()=>this._dismiss(t),ms);
    },
    _dismiss(el){if(!el||el._out)return;el._out=true;el.style.animation='valo-out .3s forwards';setTimeout(()=>el.remove(),290);},
    success(m,ms){this._show('s','Succès',m,ms);},
    error(m,ms){this._show('e','Erreur',m,ms);},
    warning(m,ms){this._show('w','Attention',m,ms);},
    confirm(msg, opts={}) {
        return new Promise(resolve => {
            const ov=document.createElement('div');
            ov.className='valo-overlay';
            ov.innerHTML=`<div class="valo-dialog"><div class="valo-dialog-icon" style="background:${opts.iconBg||'#fdecea'};color:${opts.iconColor||'#f44336'}"><i class="fas ${opts.icon||'fa-exclamation-triangle'}"></i></div><h5>${opts.title||'Confirmation'}</h5><p>${msg}</p><div class="valo-dialog-btns"><button class="valo-btn-cancel">${opts.cancelLabel||'Annuler'}</button><button class="valo-btn-ok" style="background:${opts.okBg||'#f44336'}">${opts.okLabel||'Confirmer'}</button></div></div>`;
            document.body.appendChild(ov);
            ov.querySelector('.valo-btn-cancel').onclick=()=>{ov.remove();resolve(false);};
            ov.querySelector('.valo-btn-ok').onclick=()=>{ov.remove();resolve(true);};
        });
    }
};

/* Intercept onclick="return confirm(...)" automatiquement */
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[onsubmit]').forEach(form => {
        const orig = form.getAttribute('onsubmit') || '';
        const m = orig.match(/return\s+confirm\(['"](.+?)['"]\)/);
        if (!m) return;
        const msg = m[1];
        form.removeAttribute('onsubmit');
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            Valo.confirm(msg, {title:'Confirmation', okLabel:'Oui', cancelLabel:'Non'})
                .then(ok => { if (ok) form.submit(); });
        });
    });
    document.querySelectorAll('a[onclick], button[onclick]').forEach(el => {
        const orig = el.getAttribute('onclick') || '';
        const m = orig.match(/return\s+confirm\(['"](.+?)['"]\)/);
        if (!m) return;
        const msg = m[1];
        const href = el.getAttribute('href');
        el.removeAttribute('onclick');
        el.addEventListener('click', e => {
            e.preventDefault();
            Valo.confirm(msg, {title:'Confirmation', okLabel:'Oui', cancelLabel:'Non'})
                .then(ok => { if (ok && href) window.location.href = href; });
        });
    });
});
</script>
