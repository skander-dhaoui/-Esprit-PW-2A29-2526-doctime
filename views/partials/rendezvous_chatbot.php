<?php
$rdvChatRole = $_SESSION['user_role'] ?? 'patient';
if ($rdvChatRole !== 'patient') {
    return;
}
$rdvChatTitle = 'Assistant rendez-vous';
?>
<style>
    .rdv-chatbot-toggle {
        position: fixed;
        right: 24px;
        bottom: 24px;
        width: 58px;
        height: 58px;
        border: 0;
        border-radius: 50%;
        background: linear-gradient(135deg, #2A7FAA, #4CAF50);
        color: #fff;
        box-shadow: 0 12px 28px rgba(42, 127, 170, 0.28);
        z-index: 1050;
    }
    .rdv-chatbot-panel {
        position: fixed;
        right: 24px;
        bottom: 92px;
        width: min(380px, calc(100vw - 32px));
        background: #fff;
        border: 1px solid rgba(30, 42, 62, 0.08);
        border-radius: 12px;
        box-shadow: 0 18px 42px rgba(30, 42, 62, 0.18);
        overflow: hidden;
        z-index: 1050;
        display: none;
    }
    .rdv-chatbot-panel.open { display: block; }
    .rdv-chatbot-header {
        background: #1e2a3e;
        color: #fff;
        padding: 14px 16px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .rdv-chatbot-close {
        border: 0;
        background: transparent;
        color: #fff;
        font-size: 18px;
        line-height: 1;
    }
    .rdv-chatbot-messages {
        height: 320px;
        overflow-y: auto;
        padding: 14px;
        background: #f5f7fb;
    }
    .rdv-chatbot-message {
        max-width: 86%;
        padding: 10px 12px;
        border-radius: 10px;
        margin-bottom: 10px;
        font-size: 14px;
        line-height: 1.4;
        white-space: pre-wrap;
    }
    .rdv-chatbot-message.bot {
        background: #fff;
        color: #1e2a3e;
        border: 1px solid rgba(30, 42, 62, 0.08);
    }
    .rdv-chatbot-message.user {
        margin-left: auto;
        background: #2A7FAA;
        color: #fff;
    }
    .rdv-chatbot-action {
        border: 0;
        border-radius: 8px;
        background: #1e2a3e;
        color: #fff;
        padding: 8px 12px;
        margin: -2px 0 10px;
        font-size: 13px;
    }
    .rdv-chatbot-action.danger { background: #dc3545; }
    .rdv-chatbot-form {
        display: flex;
        gap: 8px;
        padding: 12px;
        border-top: 1px solid rgba(30, 42, 62, 0.08);
        background: #fff;
    }
    .rdv-chatbot-input {
        flex: 1;
        border: 1px solid #d7dde8;
        border-radius: 8px;
        padding: 10px 12px;
        min-width: 0;
    }
    .rdv-chatbot-send {
        width: 44px;
        border: 0;
        border-radius: 8px;
        background: #4CAF50;
        color: #fff;
    }
    .rdv-chatbot-hint {
        padding: 0 12px 12px;
        color: #6c757d;
        font-size: 12px;
        background: #fff;
    }
    @media (max-width: 576px) {
        .rdv-chatbot-toggle { right: 16px; bottom: 16px; }
        .rdv-chatbot-panel { right: 16px; bottom: 82px; }
    }
</style>

<button type="button" class="rdv-chatbot-toggle" id="rdvChatbotToggle" title="Ouvrir le chatbot">
    <i class="fas fa-robot"></i>
</button>

<div class="rdv-chatbot-panel" id="rdvChatbotPanel" aria-live="polite">
    <div class="rdv-chatbot-header">
        <strong><i class="fas fa-robot me-2"></i><?= htmlspecialchars($rdvChatTitle) ?></strong>
        <button type="button" class="rdv-chatbot-close" id="rdvChatbotClose" title="Fermer">&times;</button>
    </div>
    <div class="rdv-chatbot-messages" id="rdvChatbotMessages">
        <div class="rdv-chatbot-message bot">Bonjour, je peux vous aider pour les rendez-vous : disponibilites, statut, annulation, modification ou organisation de la journee.</div>
    </div>
    <form class="rdv-chatbot-form" id="rdvChatbotForm">
        <input class="rdv-chatbot-input" id="rdvChatbotInput" type="text" maxlength="600" placeholder="Ecrivez votre question..." autocomplete="off">
        <button class="rdv-chatbot-send" type="submit" title="Envoyer"><i class="fas fa-paper-plane"></i></button>
    </form>
    <div class="rdv-chatbot-hint">AI gratuite : Ollama local si disponible, sinon assistant integre.</div>
</div>

<script>
(function () {
    const panel = document.getElementById('rdvChatbotPanel');
    const toggle = document.getElementById('rdvChatbotToggle');
    const closeBtn = document.getElementById('rdvChatbotClose');
    const form = document.getElementById('rdvChatbotForm');
    const input = document.getElementById('rdvChatbotInput');
    const messages = document.getElementById('rdvChatbotMessages');

    function addMessage(text, type) {
        const item = document.createElement('div');
        item.className = 'rdv-chatbot-message ' + type;
        item.textContent = text;
        messages.appendChild(item);
        messages.scrollTop = messages.scrollHeight;
    }

    function runAction(action) {
        if (!action || !action.url) return;

        if (action.type === 'confirm_redirect') {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'rdv-chatbot-action danger';
            button.textContent = action.label || 'Confirmer';
            button.addEventListener('click', function () {
                window.location.href = action.url;
            });
            messages.appendChild(button);
            messages.scrollTop = messages.scrollHeight;
            return;
        }

        if (action.type === 'redirect') {
            addMessage(action.label || 'Ouverture de la page...', 'bot');
            window.setTimeout(function () {
                window.location.href = action.url;
            }, 700);
        }
    }

    toggle.addEventListener('click', function () {
        panel.classList.toggle('open');
        if (panel.classList.contains('open')) input.focus();
    });

    closeBtn.addEventListener('click', function () {
        panel.classList.remove('open');
    });

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        const message = input.value.trim();
        if (!message) return;

        addMessage(message, 'user');
        input.value = '';
        input.disabled = true;
        addMessage('Je reflechis...', 'bot');
        const loading = messages.lastElementChild;

        fetch('index.php?page=api_rdv_chatbot', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ message: message })
        })
            .then(response => response.json())
            .then(data => {
                loading.remove();
                addMessage(data.reply || data.message || 'Desole, je n ai pas pu repondre.', 'bot');
                runAction(data.action);
            })
            .catch(() => {
                loading.remove();
                addMessage('Le chatbot est indisponible pour le moment. Reessayez dans quelques instants.', 'bot');
            })
            .finally(() => {
                input.disabled = false;
                input.focus();
            });
    });
})();
</script>
