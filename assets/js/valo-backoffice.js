/* Valo - remplace confirm/alert dans le backoffice pharmacie */
const Valo = {
    _show(type, title, msg, ms=5000) {
        const wrap = document.createElement('div');
        wrap.style.cssText = 'position:fixed;top:80px;right:18px;z-index:9999;max-width:360px';
        const icons = {s:'fa-check-circle',e:'fa-times-circle',w:'fa-exclamation-triangle'};
        const colors = {s:'#4CAF50',e:'#f44336',w:'#FF9800'};
        wrap.innerHTML = `<div style="display:flex;gap:10px;background:white;border-radius:12px;
            padding:14px 16px;box-shadow:0 6px 24px rgba(0,0,0,.13);
            border-left:5px solid ${colors[type]};animation:valo_in .3s ease">
            <span style="font-size:18px;color:${colors[type]}"><i class="fas ${icons[type]}"></i></span>
            <div style="flex:1"><div style="font-weight:700;font-size:.85rem;color:#1a2035">${title}</div>
            <div style="font-size:.8rem;color:#555">${msg}</div></div>
            <button onclick="this.closest('div[style]').parentNode.remove()"
                    style="background:none;border:none;cursor:pointer;color:#aaa;font-size:14px">×</button>
        </div>`;
        document.body.appendChild(wrap);
        if (ms > 0) setTimeout(() => wrap.remove(), ms);
    },
    success(m, ms) { this._show('s', 'Succès', m, ms); },
    error(m, ms)   { this._show('e', 'Erreur', m, ms); },
    warning(m, ms) { this._show('w', 'Attention', m, ms); },

    confirm(msg, opts = {}) {
        return new Promise(resolve => {
            const ov = document.createElement('div');
            ov.style.cssText = 'position:fixed;inset:0;z-index:10000;background:rgba(26,32,53,.5);display:flex;align-items:center;justify-content:center;';
            ov.innerHTML = `
                <div style="background:white;border-radius:18px;padding:32px;max-width:400px;
                            width:90%;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,.2)">
                    <div style="width:52px;height:52px;border-radius:50%;
                                background:${opts.iconBg||'#fdecea'};color:${opts.iconColor||'#f44336'};
                                display:flex;align-items:center;justify-content:center;
                                font-size:22px;margin:0 auto 14px">
                        <i class="fas ${opts.icon||'fa-exclamation-triangle'}"></i>
                    </div>
                    <h5 style="font-weight:700;color:#1a2035;margin-bottom:8px">${opts.title||'Confirmation'}</h5>
                    <p style="color:#555;font-size:.9rem;margin-bottom:22px">${msg}</p>
                    <div style="display:flex;gap:10px;justify-content:center">
                        <button id="_vc" style="border:2px solid #e0e0e0;background:white;border-radius:10px;
                                padding:8px 20px;font-weight:600;color:#666;cursor:pointer">
                            ${opts.cancelLabel||'Annuler'}
                        </button>
                        <button id="_vok" style="border:none;border-radius:10px;padding:8px 20px;
                                font-weight:600;color:white;cursor:pointer;
                                background:${opts.okBg||'#f44336'}">
                            ${opts.okLabel||'Confirmer'}
                        </button>
                    </div>
                </div>`;
            document.body.appendChild(ov);
            ov.querySelector('#_vc').onclick  = () => { ov.remove(); resolve(false); };
            ov.querySelector('#_vok').onclick = () => { ov.remove(); resolve(true);  };
        });
    }
};

/* Auto-intercept confirm() dans onclick/onsubmit */
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[onsubmit]').forEach(form => {
        const m = (form.getAttribute('onsubmit') || '').match(/return\s+confirm\(['"](.+?)['"]\)/);
        if (!m) return;
        const msg = m[1];
        form.removeAttribute('onsubmit');
        form.addEventListener('submit', e => {
            e.preventDefault();
            Valo.confirm(msg, { okLabel: 'Oui', cancelLabel: 'Non' }).then(ok => { if (ok) form.submit(); });
        });
    });
    document.querySelectorAll('a[onclick], button[onclick]').forEach(el => {
        const m = (el.getAttribute('onclick') || '').match(/return\s+confirm\(['"](.+?)['"]\)/);
        if (!m) return;
        const msg = m[1];
        const href = el.getAttribute('href');
        el.removeAttribute('onclick');
        el.addEventListener('click', e => {
            e.preventDefault();
            Valo.confirm(msg, { okLabel: 'Oui', cancelLabel: 'Non' }).then(ok => { if (ok && href) window.location.href = href; });
        });
    });
});
