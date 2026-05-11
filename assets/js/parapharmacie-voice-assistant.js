/**
 * Parapharmacie — reconnaissance vocale (Chrome : besoin Internet)
 * + saisie texte (sans réseau vocal).
 */
(function () {
    'use strict';

    var HAS_SR = !!(window.SpeechRecognition || window.webkitSpeechRecognition);
    var SpeechRecognition = HAS_SR ? (window.SpeechRecognition || window.webkitSpeechRecognition) : null;

    function normalize(str) {
        return String(str || '')
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/\s+/g, ' ')
            .trim();
    }

    function toast(title, msg, type) {
        if (typeof Valo !== 'undefined') {
            if (type === 'e') Valo.error(msg);
            else if (type === 'w') Valo.warning(msg);
            else Valo.success(msg);
            return;
        }
        if (title) window.console.log(title, msg);
    }

    function speak(text) {
        if (!('speechSynthesis' in window) || !text) return;
        try {
            window.speechSynthesis.cancel();
            var u = new SpeechSynthesisUtterance(text);
            u.lang = 'fr-FR';
            u.rate = 1;
            window.speechSynthesis.speak(u);
        } catch (e) { /* ignore */ }
    }

    function routeCommand(raw) {
        var n = normalize(raw);
        if (!n) return;

        if (/\b(accueil|home)\b/.test(n) || /va (a|à) l.?accueil/.test(n) || /aller (a|à) l.?accueil/.test(n) || /page d.?accueil/.test(n)) {
            speak('Ouverture de l’accueil');
            window.location.href = 'index.php?page=accueil';
            return;
        }
        if (/\b(catalogue|parapharmacie|pharmacie|liste des produits|voir les produits)\b/.test(n)) {
            speak('Ouverture du catalogue');
            window.location.href = 'index.php?page=parapharmacie';
            return;
        }
        if (/\b(panier|mon panier)\b/.test(n)) {
            speak('Ouverture du panier');
            window.location.href = 'index.php?page=panier';
            return;
        }
        if (/\b(mes commandes|ma commande)\b/.test(n)) {
            speak('Ouverture de vos commandes');
            window.location.href = 'index.php?page=mes_commandes';
            return;
        }
        if (/\b(retour au catalogue|retour catalogue)\b/.test(n)) {
            speak('Retour au catalogue');
            window.location.href = 'index.php?page=pharmacie';
            return;
        }

        var rm = n.match(/\b(chercher|recherche|rechercher|trouve|trouver|montre)\s+(.+)$/);
        if (rm) {
            var q = rm[2].replace(/\.$/, '').trim();
            var inp = document.getElementById('frontSearchInput');
            if (inp) {
                inp.value = q;
                inp.dispatchEvent(new Event('input', { bubbles: true }));
                var form = inp.closest('form');
                if (form && String(form.method || '').toLowerCase() === 'get') {
                    speak('Recherche : ' + q);
                    form.submit();
                    return;
                }
                speak('Recherche : ' + q);
                return;
            }
            speak('Recherche : ' + q);
            window.location.href = 'index.php?page=parapharmacie&search=' + encodeURIComponent(q);
            return;
        }

        if (/\b(conseiller|chatbot|conseil produit|demande au conseiller)\b/.test(n)) {
            var rest = n.replace(/^.*?\b(conseiller|chatbot|conseil produit|demande au conseiller)\b\s*/i, '').trim();
            var ta = document.querySelector('#chatbot-conseiller textarea[name="chatbot_query"], textarea[name="chatbot_query"]');
            var cform = ta && ta.closest('form');
            if (ta && cform) {
                if (rest) ta.value = rest;
                speak(rest ? 'Envoi au conseiller' : 'Ouverture du conseiller');
                cform.submit();
                return;
            }
            speak('Ouverture du conseiller');
            window.location.href = 'index.php?page=parapharmacie#chatbot-conseiller';
            return;
        }

        if (/\b(ajouter au panier|ajoute au panier|mettre au panier|dans le panier)\b/.test(n)) {
            var formAdd = document.querySelector('form[action*="panier"][action*="action=add"]');
            if (formAdd) {
                speak('Ajout au panier');
                formAdd.submit();
                return;
            }
            toast('', 'Ouvrez une fiche produit pour ajouter au panier.', 'w');
            speak('Ouvrez une fiche produit pour ajouter au panier');
            return;
        }

        var qm = n.match(/\b(quantite|quantité)\s+(\d+)\b/);
        if (qm) {
            var qinp = document.querySelector('input[name="quantite"]');
            if (qinp) {
                qinp.value = qm[2];
                qinp.dispatchEvent(new Event('change', { bubbles: true }));
                speak('Quantité ' + qm[2]);
                return;
            }
        }

        if (/\b(aide|help|que peux tu faire|commandes vocales)\b/.test(n)) {
            var help = 'Tapez ou dites : catalogue, panier, mes commandes, recherche suivi d’un mot, conseiller, ajouter au panier sur une fiche. Sans Internet, utilisez le bouton clavier.';
            toast('Assistant', help, 'w');
            speak(help);
            return;
        }

        toast('', 'Commande non reconnue. Dites « aide ».', 'w');
        speak('Je n’ai pas compris. Dites aide pour la liste.');
    }

    function openTextPanel(panel, inp) {
        panel.hidden = false;
        inp.focus();
    }

    function messageForSpeechError(ev) {
        var code = (ev && ev.error) ? ev.error : '';
        if (code === 'aborted') return '';
        switch (code) {
            case 'not-allowed':
                return 'Micro refusé. Autorisez le microphone dans la barre d’adresse.';
            case 'no-speech':
                return 'Aucune parole entendue. Réessayez.';
            case 'audio-capture':
                return 'Micro indisponible.';
            case 'network':
                return 'Sans Internet, la voix ne fonctionne pas dans Chrome. Utilisez le bouton clavier (icône sous le micro) pour taper la commande.';
            case 'service-not-allowed':
                return 'Reconnaissance vocale bloquée. Utilisez le bouton clavier.';
            default:
                return code ? ('Erreur « ' + code + ' ». Essayez la saisie clavier.') : 'Erreur vocale.';
        }
    }

    function ensureUi() {
        if (document.getElementById('pharma-voice-assistant-root')) return;

        var root = document.createElement('div');
        root.id = 'pharma-voice-assistant-root';
        root.innerHTML =
            '<div class="pharma-voice-fallback-panel" id="pharma-voice-fallback-panel" hidden>' +
            '<div class="small text-muted mb-1 fw-semibold">Commande sans micro / sans Internet</div>' +
            '<div class="d-flex gap-2">' +
            '<input type="text" id="pharma-voice-text-cmd" class="form-control form-control-sm" placeholder="Ex. catalogue, panier, aide…" autocomplete="off" />' +
            '<button type="button" class="btn btn-sm btn-primary flex-shrink-0" id="pharma-voice-text-send">OK</button>' +
            '</div></div>' +
            '<div class="pharma-voice-stack">' +
            '<button type="button" id="pharma-voice-btn" class="pharma-voice-fab" title="Parler (nécessite Internet)">' +
            '<i class="fas fa-microphone" aria-hidden="true"></i></button>' +
            '<button type="button" id="pharma-voice-kbd" class="pharma-voice-fab pharma-voice-fab--kbd" title="Taper une commande">' +
            '<i class="fas fa-keyboard" aria-hidden="true"></i></button>' +
            '</div>' +
            '<div id="pharma-voice-status" class="pharma-voice-status" role="status" aria-live="polite"></div>';
        document.body.appendChild(root);

        var panel = document.getElementById('pharma-voice-fallback-panel');
        var txtInp = document.getElementById('pharma-voice-text-cmd');
        var txtSend = document.getElementById('pharma-voice-text-send');
        var btn = document.getElementById('pharma-voice-btn');
        var kbd = document.getElementById('pharma-voice-kbd');
        var statusEl = document.getElementById('pharma-voice-status');

        function submitText() {
            var t = (txtInp.value || '').trim();
            if (!t) return;
            statusEl.textContent = '« ' + t + ' »';
            statusEl.style.display = 'block';
            routeCommand(t);
            txtInp.value = '';
        }

        txtSend.addEventListener('click', submitText);
        txtInp.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                submitText();
            }
        });

        kbd.addEventListener('click', function () {
            panel.hidden = !panel.hidden;
            if (!panel.hidden) txtInp.focus();
        });

        if (!HAS_SR) {
            btn.disabled = true;
            btn.classList.add('pharma-voice-fab--disabled');
            btn.title = 'Voix non disponible dans ce navigateur';
        }

        var rec = HAS_SR ? new SpeechRecognition() : null;
        if (rec) {
            rec.lang = 'fr-FR';
            rec.interimResults = true;
            rec.continuous = false;
            rec.maxAlternatives = 1;
        }

        var listening = false;

        function setListening(on) {
            listening = on;
            btn.classList.toggle('is-listening', on);
            btn.setAttribute('aria-pressed', on ? 'true' : 'false');
            statusEl.textContent = on ? 'Écoute… parlez maintenant.' : '';
            statusEl.style.display = on ? 'block' : 'none';
        }

        if (rec) {
            rec.onstart = function () { setListening(true); };
            rec.onend = function () { setListening(false); };
            rec.onerror = function (ev) {
                setListening(false);
                var msg = messageForSpeechError(ev);
                if (!msg) return;
                toast('Assistant vocal', msg, 'e');
                speak(msg.length > 130 ? msg.substring(0, 130) + '…' : msg);
                if (ev.error === 'network' || ev.error === 'service-not-allowed') {
                    openTextPanel(panel, txtInp);
                }
            };
            rec.onresult = function (ev) {
                var text = '';
                for (var i = ev.resultIndex; i < ev.results.length; i++) {
                    text += ev.results[i][0].transcript;
                }
                text = text.trim();
                if (!text) return;
                if (ev.results[0].isFinal) {
                    statusEl.textContent = '« ' + text + ' »';
                    routeCommand(text);
                }
            };
        }

        btn.addEventListener('click', function () {
            if (!HAS_SR || !rec) return;
            if (listening) {
                rec.stop();
                return;
            }
            if (!window.isSecureContext) {
                toast('Assistant vocal', 'Utilisez localhost ou HTTPS. Sinon utilisez le bouton clavier.', 'e');
                openTextPanel(panel, txtInp);
                return;
            }
            function startRecognition() {
                try {
                    rec.start();
                } catch (e) {
                    toast('Assistant vocal', 'Impossible de démarrer.', 'e');
                    openTextPanel(panel, txtInp);
                }
            }
            if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
                navigator.mediaDevices.getUserMedia({ audio: true })
                    .then(function (stream) {
                        stream.getTracks().forEach(function (t) { t.stop(); });
                        startRecognition();
                    })
                    .catch(function () {
                        toast('Assistant vocal', 'Micro inaccessible. Utilisez le bouton clavier.', 'e');
                        openTextPanel(panel, txtInp);
                    });
            } else {
                startRecognition();
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', ensureUi);
    } else {
        ensureUi();
    }
})();
