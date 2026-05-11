/**
 * DocTime — force du mot de passe + génération sécurisée (crypto.getRandomValues)
 */
(function (global) {
    'use strict';

    function generateStrongPassword(len) {
        var length = len || 16;
        var lower = 'abcdefghjkmnpqrstuvwxyz';
        var upper = 'ABCDEFGHJKMNPQRSTUVWXYZ';
        var num = '23456789';
        var sym = '!@#$%&*_-+=';
        var all = lower + upper + num + sym;
        var buf = new Uint32Array(Math.max(length, 12));
        global.crypto.getRandomValues(buf);
        var out = '';
        out += lower.charAt(buf[0] % lower.length);
        out += upper.charAt(buf[1] % upper.length);
        out += num.charAt(buf[2] % num.length);
        out += sym.charAt(buf[3] % sym.length);
        for (var i = 4; i < length; i++) {
            out += all.charAt(buf[i % buf.length] % all.length);
        }
        var arr = out.split('');
        for (var j = arr.length - 1; j > 0; j--) {
            var k = buf[j % buf.length] % (j + 1);
            var t = arr[j];
            arr[j] = arr[k];
            arr[k] = t;
        }
        return arr.join('');
    }

    function scorePassword(p) {
        var score = 0;
        if (!p) return 0;
        if (p.length >= 8) score++;
        if (p.length >= 12) score++;
        if (/[a-z]/.test(p)) score++;
        if (/[A-Z]/.test(p)) score++;
        if (/[0-9]/.test(p)) score++;
        if (/[^A-Za-z0-9]/.test(p)) score++;
        return score;
    }

    /** Règle minimale DocTime : 8+, majuscule, chiffre */
    function strengthLabel(p) {
        if (!p) {
            return { key: 'empty', label: '', cls: '', pct: 0 };
        }
        var okBasic = p.length >= 8 && /[A-Z]/.test(p) && /[0-9]/.test(p);
        var s = scorePassword(p);
        if (!okBasic || s <= 2) {
            return { key: 'weak', label: 'Faible', cls: 'pw-strength-weak', pct: 33 };
        }
        if (s <= 4 || p.length < 12) {
            return { key: 'medium', label: 'Moyen', cls: 'pw-strength-medium', pct: 66 };
        }
        return { key: 'strong', label: 'Fort', cls: 'pw-strength-strong', pct: 100 };
    }

    function bindMeter(inputId, fillId, labelId) {
        var input = document.getElementById(inputId);
        var fill = document.getElementById(fillId);
        var label = document.getElementById(labelId);
        if (!input || !fill || !label) return;

        function refresh() {
            if (!input.value) {
                fill.style.width = '0%';
                fill.className = 'pw-strength-empty';
                label.textContent = '';
                label.className = 'pw-strength-text';
                return;
            }
            var st = strengthLabel(input.value);
            fill.style.width = st.pct + '%';
            fill.className = st.cls;
            label.textContent = st.label;
            label.className = 'pw-strength-text ' + st.cls;
        }

        input.addEventListener('input', refresh);
        refresh();
        return refresh;
    }

    function wireGenerator(btnId, pwdId, confirmId, refreshMeter) {
        var btn = document.getElementById(btnId);
        if (!btn || !global.crypto || !global.crypto.getRandomValues) return;
        btn.addEventListener('click', function () {
            var p = generateStrongPassword(16);
            var pwd = document.getElementById(pwdId);
            if (pwd) pwd.value = p;
            var c = confirmId ? document.getElementById(confirmId) : null;
            if (c) c.value = p;
            if (typeof refreshMeter === 'function') refreshMeter();
        });
    }

    global.DoctimePassword = {
        generateStrongPassword: generateStrongPassword,
        strengthLabel: strengthLabel,
        scorePassword: scorePassword,
        bindMeter: bindMeter,
        wireGenerator: wireGenerator
    };
})(typeof window !== 'undefined' ? window : this);
