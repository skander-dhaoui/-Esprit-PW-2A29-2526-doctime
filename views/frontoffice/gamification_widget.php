<?php
/** Petit bloc profil : points & niveau (JSON via api_gamification). */
?>
<div id="gamification-widget" class="mb-4 p-3 rounded-3" style="background: linear-gradient(135deg, #f0f7ff 0%, #e8f5e9 100%); border: 1px solid #cfe2ff;">
    <div class="d-flex align-items-center gap-2 mb-2">
        <span style="font-size: 1.5rem;">🏅</span>
        <strong style="color: #1c1e21;">Progression</strong>
    </div>
    <div id="gamification-widget-body" class="small text-muted">Chargement…</div>
    <div id="gamification-widget-bar" class="progress mt-2 d-none" style="height: 8px;">
        <div id="gamification-widget-bar-fill" class="progress-bar" role="progressbar" style="background: #2A7FAA;"></div>
    </div>
</div>
<script>
(function () {
    var el = document.getElementById('gamification-widget-body');
    var barWrap = document.getElementById('gamification-widget-bar');
    var barFill = document.getElementById('gamification-widget-bar-fill');
    if (!el) return;
    fetch('index.php?page=api_gamification&action=stats', { headers: { 'Accept': 'application/json' } })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (!data.success || !data.stats) {
                el.textContent = data.message || 'Indisponible pour le moment.';
                return;
            }
            var s = data.stats;
            var lvl = s.level || {};
            var line = '<span style="font-size:1.25rem;">' + (lvl.badge || '') + '</span> ' +
                '<strong>' + (lvl.name || '') + '</strong> — ' + (s.total_points != null ? s.total_points : 0) + ' pts';
            el.innerHTML = line;
            if (lvl.next_level && barWrap && barFill) {
                barWrap.classList.remove('d-none');
                var p = typeof lvl.progress === 'number' ? lvl.progress : 0;
                barFill.style.width = Math.min(100, p) + '%';
                barFill.setAttribute('aria-valuenow', String(p));
            }
        })
        .catch(function () { el.textContent = 'Impossible de charger la progression.'; });
})();
</script>
