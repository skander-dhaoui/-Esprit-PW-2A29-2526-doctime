/**
 * pharmacie-popup.js
 * Remplace les confirm() et alert() natifs du browser
 * par des modals Bootstrap élégants.
 */

(function () {
  'use strict';

  /* ─── Injection du HTML du modal ──────────────────────────── */
  function injectModal() {
    if (document.getElementById('pharmaPopupModal')) return;

    const html = `
    <!-- Modal popup pharmacie -->
    <div class="modal fade" id="pharmaPopupModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius:16px;overflow:hidden;">
          <div class="modal-header border-0 pb-0" id="pharmaPopupHeader" style="background:linear-gradient(135deg,#4CAF50,#2e7d32);padding:20px 24px 12px;">
            <div style="display:flex;align-items:center;gap:12px;">
              <div id="pharmaPopupIcon" style="width:40px;height:40px;border-radius:50%;background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;font-size:20px;color:#fff;"></div>
              <h5 class="modal-title mb-0" id="pharmaPopupTitle" style="color:#fff;font-weight:700;font-size:1.1rem;"></h5>
            </div>
          </div>
          <div class="modal-body px-4 py-3">
            <p id="pharmaPopupMessage" class="mb-0" style="color:#374151;font-size:.97rem;line-height:1.6;"></p>
          </div>
          <div class="modal-footer border-0 px-4 pb-4 pt-2 gap-2" id="pharmaPopupFooter">
            <!-- boutons injectés dynamiquement -->
          </div>
        </div>
      </div>
    </div>`;

    document.body.insertAdjacentHTML('beforeend', html);
  }

  /* ─── Utilitaire d'affichage ───────────────────────────────── */
  function showModal({ type = 'info', title, message, buttons }) {
    injectModal();

    const colors = {
      danger:  { gradient: 'linear-gradient(135deg,#f44336,#b71c1c)', icon: '⚠️' },
      success: { gradient: 'linear-gradient(135deg,#4CAF50,#2e7d32)', icon: '✅' },
      info:    { gradient: 'linear-gradient(135deg,#2196F3,#0d47a1)', icon: 'ℹ️' },
      warning: { gradient: 'linear-gradient(135deg,#FF9800,#e65100)', icon: '⚡' },
    };

    const cfg = colors[type] || colors.info;
    document.getElementById('pharmaPopupHeader').style.background = cfg.gradient;
    document.getElementById('pharmaPopupIcon').textContent   = cfg.icon;
    document.getElementById('pharmaPopupTitle').textContent  = title;
    document.getElementById('pharmaPopupMessage').textContent = message;

    const footer = document.getElementById('pharmaPopupFooter');
    footer.innerHTML = '';

    buttons.forEach(btn => {
      const el = document.createElement('button');
      el.type = 'button';
      el.className = `btn ${btn.class || 'btn-secondary'}`;
      el.style.cssText = 'border-radius:10px;padding:8px 22px;font-weight:600;';
      el.textContent = btn.label;
      el.addEventListener('click', () => {
        bootstrap.Modal.getInstance(document.getElementById('pharmaPopupModal'))?.hide();
        if (typeof btn.onClick === 'function') btn.onClick();
      });
      footer.appendChild(el);
    });

    const modal = new bootstrap.Modal(document.getElementById('pharmaPopupModal'));
    modal.show();
  }

  /* ─── API publique ─────────────────────────────────────────── */

  /**
   * Remplace confirm() — renvoie une Promise<boolean>
   * Usage : pharmaConfirm('Supprimer ce produit ?').then(ok => { if(ok) window.location.href = url; });
   */
  window.pharmaConfirm = function (message, options = {}) {
    return new Promise((resolve) => {
      showModal({
        type:    options.type    || 'danger',
        title:   options.title   || 'Confirmation',
        message: message,
        buttons: [
          {
            label:   options.cancelLabel  || 'Annuler',
            class:   'btn-outline-secondary',
            onClick: () => resolve(false),
          },
          {
            label:   options.confirmLabel || 'Confirmer',
            class:   options.confirmClass || 'btn-danger',
            onClick: () => resolve(true),
          },
        ],
      });
    });
  };

  /**
   * Remplace alert() — renvoie une Promise (résolue après fermeture)
   */
  window.pharmaAlert = function (message, options = {}) {
    return new Promise((resolve) => {
      showModal({
        type:    options.type  || 'info',
        title:   options.title || 'Information',
        message: message,
        buttons: [
          {
            label:   options.okLabel || 'OK',
            class:   options.okClass || 'btn-primary',
            onClick: () => resolve(),
          },
        ],
      });
    });
  };

  /* ─── Remplacement automatique des confirm() inline ───────────
     Intercepte tous les liens/forms qui utilisent encore
     onclick="return confirm(...)" ou onsubmit="return confirm(...)"
  ─────────────────────────────────────────────────────────────── */
  document.addEventListener('DOMContentLoaded', () => {
    // Liens avec onclick="return confirm(...)"
    document.querySelectorAll('a[onclick], button[onclick]').forEach(el => {
      const original = el.getAttribute('onclick') || '';
      const match    = original.match(/return\s+confirm\(['"](.+?)['"]\)/);
      if (!match) return;

      const msg  = match[1];
      const href = el.getAttribute('href') || null;

      el.removeAttribute('onclick');
      el.addEventListener('click', function (e) {
        e.preventDefault();
        pharmaConfirm(msg).then(ok => {
          if (!ok) return;
          if (href) window.location.href = href;
          else el.closest('form')?.submit();
        });
      });
    });

    // Forms avec onsubmit="return confirm(...)"
    document.querySelectorAll('form[onsubmit]').forEach(form => {
      const original = form.getAttribute('onsubmit') || '';
      const match    = original.match(/return\s+confirm\(['"](.+?)['"]\)/);
      if (!match) return;

      const msg = match[1];
      form.removeAttribute('onsubmit');
      form.addEventListener('submit', function (e) {
        e.preventDefault();
        pharmaConfirm(msg).then(ok => { if (ok) form.submit(); });
      });
    });

    // Remplace les alert() inline dans les scripts
    // (pour les validations JS qui appellent alert('Veuillez...'))
    const _nativeAlert = window.alert;
    window.alert = function (msg) {
      if (typeof bootstrap !== 'undefined' && document.body) {
        pharmaAlert(msg, { type: 'warning', title: 'Attention' });
      } else {
        _nativeAlert(msg);
      }
    };
  });

})();
