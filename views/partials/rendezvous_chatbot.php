<?php
declare(strict_types=1);
/**
 * Widget assistant Valorys — RDV, ordonnances, disponibilités (api_rdv_chatbot).
 */
$assistantTitle = $assistantTitle ?? 'Assistant Valorys';
$assistantWelcome = $assistantWelcome ?? 'Bonjour ! Je peux vous aider sur vos rendez-vous, ordonnances et créneaux de disponibilité. Que souhaitez-vous savoir ?';
$assistantApiUrl = $assistantApiUrl ?? 'index.php?page=api_rdv_chatbot';
?>
<div id="rdv-chat-root" class="rdv-chat-root" aria-label="<?= htmlspecialchars($assistantTitle, ENT_QUOTES, 'UTF-8') ?>">
    <div id="rdv-chat-panel" class="rdv-chat-panel" hidden>
        <div class="rdv-chat-header">
            <span class="rdv-chat-title"><i class="fas fa-robot rdv-chat-icon" aria-hidden="true"></i> <?= htmlspecialchars($assistantTitle, ENT_QUOTES, 'UTF-8') ?></span>
            <button type="button" id="rdv-chat-close" class="rdv-chat-close" aria-label="Fermer">&times;</button>
        </div>
        <div id="rdv-chat-messages" class="rdv-chat-messages" tabindex="-1"></div>
        <div class="rdv-chat-input-row">
            <input type="text" id="rdv-chat-input" class="rdv-chat-input" maxlength="600"
                   placeholder="Posez votre question…" autocomplete="off" aria-label="Votre question" />
            <button type="button" id="rdv-chat-send" class="rdv-chat-send" aria-label="Envoyer">
                <i class="fas fa-paper-plane"></i>
            </button>
        </div>
    </div>
    <button type="button" id="rdv-chat-fab" class="rdv-chat-fab" aria-expanded="false" aria-controls="rdv-chat-panel" title="Ouvrir l’assistant">
        <i class="fas fa-comments"></i>
    </button>
</div>
<style>
.rdv-chat-root { font-family: 'Segoe UI', system-ui, sans-serif; }
.rdv-chat-fab {
    position: fixed;
    bottom: 22px;
    right: 22px;
    width: 56px;
    height: 56px;
    border-radius: 50%;
    border: none;
    cursor: pointer;
    z-index: 1060;
    background: linear-gradient(135deg, #2A7FAA 0%, #4CAF50 100%);
    color: #fff;
    font-size: 22px;
    box-shadow: 0 6px 20px rgba(42, 127, 170, 0.45);
    display: flex;
    align-items: center;
    justify-content: center;
    transition: transform 0.15s ease;
}
.rdv-chat-fab:hover { transform: scale(1.06); }
.rdv-chat-panel {
    position: fixed;
    bottom: 92px;
    right: 22px;
    width: min(380px, calc(100vw - 32px));
    max-height: min(520px, calc(100vh - 120px));
    z-index: 1059;
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 12px 40px rgba(0,0,0,0.18);
    display: flex;
    flex-direction: column;
    overflow: hidden;
}
.rdv-chat-header {
    background: linear-gradient(135deg, #2A7FAA 0%, #4CAF50 100%);
    color: #fff;
    padding: 12px 14px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-weight: 600;
    font-size: 15px;
}
.rdv-chat-icon { color: #e9d5ff; margin-right: 8px; }
.rdv-chat-close {
    background: transparent;
    border: none;
    color: #fff;
    font-size: 22px;
    line-height: 1;
    cursor: pointer;
    padding: 0 4px;
}
.rdv-chat-messages {
    flex: 1;
    overflow-y: auto;
    padding: 14px;
    background: #f5f7fb;
    min-height: 220px;
    max-height: 340px;
}
.rdv-chat-bubble {
    max-width: 92%;
    padding: 10px 12px;
    border-radius: 12px;
    margin-bottom: 10px;
    font-size: 14px;
    line-height: 1.45;
    word-break: break-word;
}
.rdv-chat-bubble.bot {
    background: #fff;
    border: 1px solid #e3e8ef;
    margin-right: auto;
}
.rdv-chat-bubble.user {
    background: linear-gradient(135deg, #2A7FAA 0%, #3d9cbe 100%);
    color: #fff;
    margin-left: auto;
}
.rdv-chat-bubble .rdv-chat-action {
    margin-top: 10px;
    display: block;
    font-size: 13px;
}
.rdv-chat-input-row {
    display: flex;
    gap: 8px;
    padding: 10px 12px;
    border-top: 1px solid #e9ecef;
    background: #fff;
}
.rdv-chat-input {
    flex: 1;
    border: 1px solid #ced4da;
    border-radius: 22px;
    padding: 10px 14px;
    font-size: 14px;
}
.rdv-chat-send {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    border: none;
    cursor: pointer;
    background: linear-gradient(135deg, #2A7FAA 0%, #4CAF50 100%);
    color: #fff;
    font-size: 16px;
}
.rdv-chat-send:disabled { opacity: 0.55; cursor: not-allowed; }
.rdv-chat-typing { font-size: 12px; color: #6c757d; padding: 0 14px 8px; }
</style>
<script>
(function () {
    var root = document.getElementById('rdv-chat-root');
    if (!root) return;

    var panel = document.getElementById('rdv-chat-panel');
    var fab = document.getElementById('rdv-chat-fab');
    var btnClose = document.getElementById('rdv-chat-close');
    var box = document.getElementById('rdv-chat-messages');
    var input = document.getElementById('rdv-chat-input');
    var btnSend = document.getElementById('rdv-chat-send');

    var apiUrl = <?= json_encode($assistantApiUrl, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES) ?>;
    var welcomeMsg = <?= json_encode($assistantWelcome, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>;

    function escapeHtml(s) {
        var d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }

    function appendBubble(text, who) {
        var div = document.createElement('div');
        div.className = 'rdv-chat-bubble ' + (who === 'user' ? 'user' : 'bot');
        div.innerHTML = escapeHtml(text).replace(/\n/g, '<br>');
        box.appendChild(div);
        box.scrollTop = box.scrollHeight;
    }

    function appendActionHtml(html) {
        var wrap = document.createElement('div');
        wrap.className = 'rdv-chat-bubble bot';
        wrap.innerHTML = html;
        box.appendChild(wrap);
        box.scrollTop = box.scrollHeight;
    }

    function showTyping(on) {
        var id = 'rdv-chat-typing-el';
        var el = document.getElementById(id);
        if (on) {
            if (!el) {
                el = document.createElement('div');
                el.id = id;
                el.className = 'rdv-chat-typing';
                el.textContent = 'Réflexion…';
                box.parentNode.insertBefore(el, box.nextSibling);
            }
        } else if (el) {
            el.remove();
        }
    }

    function setOpen(open) {
        panel.hidden = !open;
        fab.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (open) {
            input.focus();
            box.scrollTop = box.scrollHeight;
        }
    }

    fab.addEventListener('click', function () { setOpen(panel.hidden); });
    btnClose.addEventListener('click', function () { setOpen(false); });

    appendBubble(welcomeMsg, 'bot');

    function handleAction(act) {
        if (!act || typeof act !== 'object') return;
        var t = act.type;
        var url = act.url || '';
        var label = act.label || 'Continuer';
        if (!url) return;
        if (t === 'redirect') {
            appendActionHtml('<span class="rdv-chat-action"><a href="' + escapeHtml(url) + '">' + escapeHtml(label) + '</a></span>');
        } else if (t === 'confirm_redirect') {
            var wrapId = 'rdv-act-' + Date.now();
            appendActionHtml(
                '<span class="rdv-chat-action" id="' + wrapId + '"><button type="button" class="btn btn-sm btn-primary rdv-chat-act-btn">' +
                escapeHtml(label) + '</button></span>'
            );
            var w = document.getElementById(wrapId);
            var b = w ? w.querySelector('.rdv-chat-act-btn') : null;
            if (b) {
                b.addEventListener('click', function () {
                    if (window.confirm('Confirmer cette action ?')) {
                        window.location.href = url;
                    }
                });
            }
        }
    }

    function send() {
        var text = (input.value || '').trim();
        if (!text) return;

        appendBubble(text, 'user');
        input.value = '';
        btnSend.disabled = true;
        showTyping(true);

        fetch(apiUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({ message: text })
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                showTyping(false);
                btnSend.disabled = false;
                if (data.success && data.reply) {
                    appendBubble(data.reply, 'bot');
                    handleAction(data.action);
                } else {
                    appendBubble(data.reply || 'Désolé, une erreur est survenue.', 'bot');
                }
            })
            .catch(function () {
                showTyping(false);
                btnSend.disabled = false;
                appendBubble('Erreur réseau. Réessayez dans un instant.', 'bot');
            });
    }

    btnSend.addEventListener('click', send);
    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            send();
        }
    });
})();
</script>
