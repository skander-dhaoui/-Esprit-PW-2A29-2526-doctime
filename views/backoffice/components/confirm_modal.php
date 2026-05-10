<?php
if (defined('VALORYS_CONFIRM_MODAL_INCLUDED')) {
    return;
}
define('VALORYS_CONFIRM_MODAL_INCLUDED', true);
?>

<style>
    .valorys-confirm-icon {
        width: 46px;
        height: 46px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #e8f5e9;
        color: #2e7d32;
        font-size: 20px;
        flex: 0 0 auto;
    }

    .valorys-confirm-title {
        margin: 0;
        color: #1a2035;
        font-size: 18px;
        font-weight: 700;
    }

    .valorys-confirm-message {
        margin: 0;
        color: #5b6475;
        line-height: 1.5;
    }
</style>

<div class="modal fade" id="valorysConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 14px;">
            <div class="modal-body p-4">
                <div class="d-flex gap-3 align-items-start">
                    <div class="valorys-confirm-icon">
                        <i id="valorysConfirmIcon" class="fas fa-question-circle"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h5 id="valorysConfirmTitle" class="valorys-confirm-title">Confirmer l'action</h5>
                        <p id="valorysConfirmMessage" class="valorys-confirm-message mt-2">
                            Voulez-vous continuer ?
                        </p>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0 px-4 pb-4">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                <a id="valorysConfirmButton" href="#" class="btn btn-success">Confirmer</a>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    function text(value, fallback) {
        return value && String(value).trim() !== '' ? String(value) : fallback;
    }

    function openConfirm(options) {
        const modalEl = document.getElementById('valorysConfirmModal');
        const titleEl = document.getElementById('valorysConfirmTitle');
        const messageEl = document.getElementById('valorysConfirmMessage');
        const iconEl = document.getElementById('valorysConfirmIcon');
        const confirmBtn = document.getElementById('valorysConfirmButton');

        if (!modalEl || !confirmBtn || typeof bootstrap === 'undefined') {
            if (options.href) window.location.href = options.href;
            return;
        }

        titleEl.textContent = text(options.title, "Confirmer l'action");
        messageEl.textContent = text(options.message, 'Voulez-vous continuer ?');
        iconEl.className = 'fas ' + text(options.icon, 'fa-question-circle');
        confirmBtn.textContent = text(options.confirmText, 'Confirmer');
        confirmBtn.href = text(options.href, '#');
        confirmBtn.className = 'btn ' + text(options.confirmClass, 'btn-success');

        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }

    window.ValorysConfirm = { open: openConfirm };

    document.addEventListener('click', function (event) {
        const trigger = event.target.closest('[data-confirm-action]');
        if (!trigger) return;

        event.preventDefault();
        openConfirm({
            href: trigger.getAttribute('href'),
            title: trigger.dataset.confirmTitle,
            message: trigger.dataset.confirmMessage,
            confirmText: trigger.dataset.confirmText,
            confirmClass: trigger.dataset.confirmClass,
            icon: trigger.dataset.confirmIcon
        });
    });
})();
</script>

