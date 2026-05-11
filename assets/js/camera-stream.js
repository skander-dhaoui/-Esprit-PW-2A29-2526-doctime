/**
 * Ouverture caméra avec stratégies de secours (Chrome « Could not start video source », mauvaise webcam par défaut, etc.)
 */
(function (global) {
    'use strict';

    function formatHint(err) {
        if (!err) return '';
        var name = err.name ? String(err.name) : '';
        var msg = err.message ? String(err.message) : '';
        if (name === 'NotAllowedError' || name === 'PermissionDeniedError') {
            return ' Autorisez la caméra pour ce site (icône dans la barre d’adresse ou Paramètres du site).';
        }
        if (name === 'NotReadableError' || name === 'TrackStartError' || /could not start/i.test(msg)) {
            return ' Souvent une autre app utilise la caméra (Zoom, Teams, OBS, caméra virtuelle). Fermez-la ou redémarrez le navigateur. Sur portable, essayez aussi une webcam USB ou les paramètres caméra/micro du site.';
        }
        if (name === 'NotFoundError' || name === 'DevicesNotFoundError') {
            return ' Aucune caméra détectée.';
        }
        if (name === 'OverconstrainedError' || name === 'ConstraintNotSatisfiedError') {
            return ' Essayez une autre caméra dans les paramètres du navigateur pour ce site.';
        }
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            return ' Contexte non sécurisé ou navigateur incompatible (HTTPS ou localhost requis pour la caméra).';
        }
        return '';
    }

    async function acquireVideoStream() {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            throw new Error('MediaDevices indisponible');
        }
        var tries = [
            { video: { facingMode: 'user' } },
            { video: { facingMode: { ideal: 'user' } } },
            { video: true },
            { video: { width: { ideal: 640 }, height: { ideal: 480 } } },
            { video: { width: { max: 1280 }, height: { max: 720 } } }
        ];
        var lastErr = null;
        for (var i = 0; i < tries.length; i++) {
            try {
                return await navigator.mediaDevices.getUserMedia(tries[i]);
            } catch (e) {
                lastErr = e;
            }
        }

        var devices = await navigator.mediaDevices.enumerateDevices();
        var cams = devices.filter(function (d) {
            return d.kind === 'videoinput';
        });
        for (var j = 0; j < cams.length; j++) {
            var id = cams[j].deviceId;
            if (!id) {
                continue;
            }
            try {
                return await navigator.mediaDevices.getUserMedia({
                    video: { deviceId: { exact: id } }
                });
            } catch (e2) {
                lastErr = e2;
            }
            try {
                return await navigator.mediaDevices.getUserMedia({
                    video: { deviceId: id }
                });
            } catch (e3) {
                lastErr = e3;
            }
        }

        throw lastErr || new Error('Aucune caméra utilisable');
    }

    global.DoctimeCamera = {
        acquireVideoStream: acquireVideoStream,
        formatHint: formatHint
    };
})(typeof window !== 'undefined' ? window : this);
