/**
 * Assistant admin — reconnaissance vocale (Chrome/Edge, besoin Internet)
 * + saisie texte (sans micro, hors ligne OK).
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

    function notify(title, msg, kind) {
        var el = document.getElementById('bo-voice-toast');
        if (!el) return;
        el.textContent = msg || title || '';
        el.className = 'bo-voice-toast bo-voice-toast--' + (kind || 'info');
        el.style.display = 'block';
        clearTimeout(el._t);
        el._t = setTimeout(function () {
            el.style.display = 'none';
        }, 6500);
    }

    function speak(text) {
        if (!('speechSynthesis' in window) || !text) return;
        try {
            window.speechSynthesis.cancel();
            var u = new SpeechSynthesisUtterance(text);
            u.lang = 'fr-FR';
            window.speechSynthesis.speak(u);
        } catch (e) { /* ignore */ }
    }

    function go(url, phrase) {
        if (phrase) speak(phrase);
        window.location.href = url;
    }

    function routeCommand(raw) {
        var n = normalize(raw);
        if (!n) return;

        if (/\b(aide|help|commandes vocales|que peux tu faire)\b/.test(n)) {
            var h = 'Commandes : tableau de bord, utilisateurs, médecins, patients, blog, rendez-vous, ordonnances, disponibilités, produits, catégories, commandes, statistiques, logs, paramètres, événements, sponsors, participations, carte, ou recherche suivi d’un mot.';
            notify('Aide', h, 'info');
            speak(h);
            return;
        }

        if (/\b(tableau de bord|dashboard)\b/.test(n)) {
            go('index.php?page=dashboard', 'Ouverture du tableau de bord');
            return;
        }
        if (/\b(utilisateurs|users)\b/.test(n)) {
            go('index.php?page=users', 'Utilisateurs');
            return;
        }
        if (/\bmedecins\b/.test(n)) {
            go('index.php?page=medecins_admin', 'Médecins');
            return;
        }
        if (/\bpatients\b/.test(n)) {
            go('index.php?page=patients', 'Patients');
            return;
        }
        if (/\b(blog|articles)\b/.test(n)) {
            go('index.php?page=articles_admin', 'Blog et articles');
            return;
        }
        if (/\b(rendez vous|rendez-vous|rdv)\b/.test(n)) {
            go('index.php?page=rendez_vous_admin', 'Rendez-vous');
            return;
        }
        if (/\bordonnances\b/.test(n)) {
            go('index.php?page=ordonnances', 'Ordonnances');
            return;
        }
        if (/\b(disponibilites|disponibilités)\b/.test(n)) {
            go('index.php?page=disponibilites_admin', 'Disponibilités');
            return;
        }
        if (/\bproduits\b/.test(n)) {
            go('index.php?page=produits_admin', 'Produits');
            return;
        }
        if (/\b(categories|catégories)\b/.test(n)) {
            go('index.php?page=categories_admin', 'Catégories');
            return;
        }
        if (/\bcommandes\b/.test(n)) {
            go('index.php?page=commandes_admin', 'Commandes');
            return;
        }
        if (/\b(statistiques|stats)\b/.test(n)) {
            go('index.php?page=stats', 'Statistiques');
            return;
        }
        if (/\b(logs|historique)\b/.test(n)) {
            go('index.php?page=logs', 'Historique');
            return;
        }
        if (/\b(parametres|paramètres|réglages|settings)\b/.test(n)) {
            go('index.php?page=settings', 'Paramètres');
            return;
        }
        if (/\b(evenements|événements)\b/.test(n)) {
            go('index.php?page=evenements_admin', 'Événements');
            return;
        }
        if (/\bsponsors\b/.test(n)) {
            go('index.php?page=sponsors_admin', 'Sponsors');
            return;
        }
        if (/\bparticipations\b/.test(n)) {
            go('index.php?page=participations', 'Participations');
            return;
        }
        if (/\bcarte tunisie\b/.test(n) || (/carte/.test(n) && !/carte bancaire/.test(n))) {
            go('index.php?page=carte', 'Carte Tunisie');
            return;
        }
        if (/\b(metiers créatifs|ia metiers|metiers creatifs)\b/.test(n)) {
            go('index.php?page=carte&action=metiers', 'IA métiers créatifs');
            return;
        }

        if (/\b(voir le site|site public|accueil site)\b/.test(n)) {
            speak('Ouverture du site');
            window.open('index.php?page=accueil', '_blank', 'noopener');
            return;
        }
        if (/\b(deconnexion|déconnexion|logout)\b/.test(n)) {
            speak('Déconnexion');
            window.location.href = 'index.php?page=logout';
            return;
        }

        var rm = n.match(/\b(recherche|rechercher|chercher|trouver)\s+(.+)$/);
        if (rm) {
            var q = rm[2].replace(/\.$/, '').trim();
            var inp = document.querySelector(
                'input[name="search"], input[name="q"], input[type="search"], #search, #adminSearch, .form-control[name="search"]'
            );
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
                notify('Recherche', q, 'info');
                return;
            }
            notify('Recherche', 'Aucun champ de recherche sur cette page.', 'warn');
            speak('Pas de recherche sur cette page');
            return;
        }

        notify('Assistant', 'Commande non reconnue. Tapez « aide ».', 'warn');
        speak('Je n’ai pas compris. Tapez aide pour la liste des commandes.');
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
                return 'Micro refusé. Dans la barre d’adresse, cliquez sur le cadenas ou « i », puis autorisez le microphone pour ce site.';
            case 'no-speech':
                return 'Aucune parole entendue. Réessayez et parlez dès que le bouton pulse, ou vérifiez le micro par défaut dans les paramètres Windows.';
            case 'audio-capture':
                return 'Aucun micro disponible (débranché, ou utilisé par une autre application).';
            case 'network':
                return 'Sans Internet, Chrome ne peut pas envoyer la voix au service de reconnaissance. Utilisez le bouton clavier (en dessous du micro) pour taper la commande — pas besoin de connexion.';
            case 'service-not-allowed':
                return 'La reconnaissance vocale est bloquée (stratégie navigateur ou entreprise). Utilisez la saisie texte (bouton clavier).';
            default:
                return code
                    ? ('Erreur reconnaissance vocale : « ' + code + ' ». Essayez la saisie texte (bouton clavier).')
                    : 'Reconnaissance vocale indisponible. Utilisez la saisie texte.';
        }
    }

    function ensureUi() {
        if (document.getElementById('bo-voice-assistant-root')) return;

        var toast = document.createElement('div');
        toast.id = 'bo-voice-toast';
        toast.className = 'bo-voice-toast';
        toast.style.display = 'none';
        toast.setAttribute('role', 'status');
        toast.setAttribute('aria-live', 'polite');
        document.body.appendChild(toast);

        var root = document.createElement('div');
        root.id = 'bo-voice-assistant-root';
        root.innerHTML =
            '<div class="bo-voice-fallback-panel" id="bo-voice-fallback-panel" hidden>' +
            '<div class="small text-muted mb-1 fw-semibold">Commande sans micro</div>' +
            '<div class="d-flex gap-2">' +
            '<input type="text" id="bo-voice-text-cmd" class="form-control form-control-sm" placeholder="Ex. utilisateurs, aide…" autocomplete="off" />' +
            '<button type="button" class="btn btn-sm btn-primary flex-shrink-0" id="bo-voice-text-send">OK</button>' +
            '</div></div>' +
            '<div class="bo-voice-stack">' +
            '<button type="button" id="bo-voice-btn" class="bo-voice-fab" title="Parler (nécessite Internet sous Chrome)" aria-label="Assistant vocal">' +
            '<i class="fas fa-microphone" aria-hidden="true"></i></button>' +
            '<button type="button" id="bo-voice-kbd" class="bo-voice-fab bo-voice-fab--kbd" title="Taper une commande — fonctionne hors ligne" aria-label="Saisie texte">' +
            '<i class="fas fa-keyboard" aria-hidden="true"></i></button>' +
            '</div>' +
            '<div id="bo-voice-status" class="bo-voice-status" role="status" aria-live="polite"></div>';
        document.body.appendChild(root);

        var panel = document.getElementById('bo-voice-fallback-panel');
        var txtInp = document.getElementById('bo-voice-text-cmd');
        var txtSend = document.getElementById('bo-voice-text-send');
        var btn = document.getElementById('bo-voice-btn');
        var kbd = document.getElementById('bo-voice-kbd');
        var statusEl = document.getElementById('bo-voice-status');

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
            if (panel.hidden) {
                openTextPanel(panel, txtInp);
            } else {
                panel.hidden = true;
            }
        });

        if (!HAS_SR) {
            btn.disabled = true;
            btn.classList.add('bo-voice-fab--disabled');
            btn.title = 'Reconnaissance vocale non disponible dans ce navigateur';
            notify('Assistant', 'Voix non supportée ici. Utilisez le bouton clavier pour taper vos commandes.', 'warn');
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
            rec.onstart = function () {
                setListening(true);
            };

            rec.onend = function () {
                setListening(false);
            };

            rec.onerror = function (ev) {
                setListening(false);
                var msg = messageForSpeechError(ev);
                if (!msg) return;
                notify('Assistant vocal', msg, 'warn');
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
                notify('Assistant vocal', 'Ouvrez le site en https:// ou via localhost pour le microphone. Sinon utilisez le bouton clavier.', 'warn');
                openTextPanel(panel, txtInp);
                return;
            }
            function startRecognition() {
                try {
                    rec.start();
                } catch (e) {
                    notify('Assistant', 'Impossible de démarrer : ' + (e && e.message ? e.message : 'erreur inconnue'), 'warn');
                    openTextPanel(panel, txtInp);
                }
            }
            if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
                navigator.mediaDevices.getUserMedia({ audio: true })
                    .then(function (stream) {
                        stream.getTracks().forEach(function (t) { t.stop(); });
                        startRecognition();
                    })
                    .catch(function (err) {
                        var name = err && err.name ? err.name : '';
                        var hint = 'Impossible d’accéder au micro.';
                        if (name === 'NotAllowedError' || name === 'PermissionDeniedError') {
                            hint = 'Micro refusé : autorisez l’accès dans la barre d’adresse du navigateur.';
                        } else if (name === 'NotFoundError') {
                            hint = 'Aucun microphone trouvé sur cet ordinateur.';
                        }
                        notify('Assistant vocal', hint + ' Vous pouvez utiliser le bouton clavier.', 'warn');
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
