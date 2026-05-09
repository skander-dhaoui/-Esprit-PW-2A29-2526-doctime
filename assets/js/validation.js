/**
 * validation.js
 * Validation côté client (complément de la validation PHP serveur).
 * Aucune règle HTML5 (required, type="email", etc.) n'est utilisée.
 *
 * Règles disponibles via data-validate="règle1|règle2:param" :
 *   required, minlength:N, maxlength:N, email, url, phone,
 *   numeric, integer, positive, date, dateafter:autreChampId
 */
(function () {
  'use strict';

  /* ── Règles de validation ──────────────────────────────────────── */
  const RULES = {
    required(val) {
      return val.trim() !== '' ? null : 'Ce champ est obligatoire.';
    },
    minlength(val, n) {
      return val.trim().length >= parseInt(n)
        ? null
        : `Ce champ doit contenir au moins ${n} caractères.`;
    },
    maxlength(val, n) {
      return val.trim().length <= parseInt(n)
        ? null
        : `Ce champ ne doit pas dépasser ${n} caractères.`;
    },
    email(val) {
      if (val.trim() === '') return null;
      return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val.trim())
        ? null
        : 'Veuillez saisir une adresse e-mail valide.';
    },
    url(val) {
      if (val.trim() === '') return null;
      try {
        const u = new URL(val.trim());
        return ['http:', 'https:'].includes(u.protocol)
          ? null
          : 'L\'URL doit commencer par http:// ou https://';
      } catch {
        return 'Veuillez saisir une URL valide (ex : https://exemple.com).';
      }
    },
    phone(val) {
      if (val.trim() === '') return null;
      const cleaned = val.replace(/[\s\-\.\(\)\+]/g, '');
      return /^\d{8,15}$/.test(cleaned)
        ? null
        : 'Veuillez saisir un numéro de téléphone valide (8 à 15 chiffres).';
    },
    numeric(val) {
      if (val.trim() === '') return null;
      return isNaN(Number(val.trim())) ? 'Ce champ doit être un nombre.' : null;
    },
    integer(val) {
      if (val.trim() === '') return null;
      return /^\d+$/.test(val.trim())
        ? null
        : 'Ce champ doit être un entier positif.';
    },
    positive(val) {
      if (val.trim() === '') return null;
      const n = parseFloat(val.trim());
      return !isNaN(n) && n > 0 ? null : 'Ce champ doit être un nombre strictement positif.';
    },
    date(val) {
      if (val.trim() === '') return null;
      const match = val.match(/^(\d{4})-(\d{2})-(\d{2})$/);
      if (!match) return 'Format de date invalide (attendu : AAAA-MM-JJ).';
      const d = new Date(val);
      return isNaN(d.getTime()) ? 'Date invalide.' : null;
    },
    dateafter(val, otherId) {
      if (val.trim() === '') return null;
      const other = document.getElementById(otherId);
      if (!other || other.value.trim() === '') return null;
      const d1 = new Date(val.trim());
      const d2 = new Date(other.value.trim());
      return d1 > d2
        ? null
        : `Cette date doit être postérieure à celle du champ "${other.labels?.[0]?.textContent?.trim() || otherId}".`;
    },
  };

  /* ── Utilitaires DOM ──────────────────────────────────────────── */
  function showError(field, msg) {
    field.classList.add('is-invalid');
    field.classList.remove('is-valid');
    let fb = field.nextElementSibling;
    if (!fb || !fb.classList.contains('invalid-feedback')) {
      fb = document.createElement('div');
      fb.className = 'invalid-feedback';
      field.insertAdjacentElement('afterend', fb);
    }
    fb.textContent = msg;
  }

  function showValid(field) {
    field.classList.remove('is-invalid');
    field.classList.add('is-valid');
    const fb = field.nextElementSibling;
    if (fb && fb.classList.contains('invalid-feedback')) fb.textContent = '';
  }

  function clearState(field) {
    field.classList.remove('is-invalid', 'is-valid');
    const fb = field.nextElementSibling;
    if (fb && fb.classList.contains('invalid-feedback')) fb.textContent = '';
  }

  /* ── Valide un champ ──────────────────────────────────────────── */
  function validateField(field) {
    const ruleStr = field.dataset.validate;
    if (!ruleStr) return true;
    const val = field.tagName === 'SELECT' ? field.value : field.value;
    const rules = ruleStr.split('|');
    for (const ruleExpr of rules) {
      const [name, param] = ruleExpr.split(':');
      const fn = RULES[name.toLowerCase()];
      if (!fn) continue;
      const err = fn(val, param);
      if (err) {
        const label = field.dataset.label || field.name || 'Ce champ';
        showError(field, err.replace('Ce champ', `« ${label} »`));
        return false;
      }
    }
    showValid(field);
    return true;
  }

  /* ── Validation à la soumission ───────────────────────────────── */
  function attachForm(form) {
    const fields = form.querySelectorAll('[data-validate]');
    // Live feedback on blur
    fields.forEach(field => {
      field.addEventListener('blur', () => validateField(field));
      field.addEventListener('input', () => {
        if (field.classList.contains('is-invalid')) validateField(field);
      });
    });
    form.addEventListener('submit', function (e) {
      let valid = true;
      fields.forEach(field => {
        if (!validateField(field)) valid = false;
      });
      if (!valid) {
        e.preventDefault();
        // Scroll to first error
        const first = form.querySelector('.is-invalid');
        if (first) first.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }
    });
  }

  /* ── Confirmation de suppression ──────────────────────────────── */
  function attachDeleteConfirm() {
    document.querySelectorAll('.js-confirm-delete').forEach(btn => {
      btn.addEventListener('click', function (e) {
        const msg = this.dataset.msg || 'Confirmer la suppression ?';
        if (!window.confirm(msg)) e.preventDefault();
      });
    });
  }

  /* ── Init ─────────────────────────────────────────────────────── */
  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('form[id]').forEach(attachForm);
    attachDeleteConfirm();
  });
})();
