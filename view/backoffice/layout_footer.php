
</div><!-- /main-content -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const PALETTE = ['#1a7fa8','#1db88e','#7c3aed','#f59e0b','#ef4444','#06b6d4','#10b981','#f97316'];

    if (document.getElementById('chartSponsors') && typeof sponsorsLabels !== 'undefined') {
        new Chart(document.getElementById('chartSponsors'), {
            type: 'doughnut',
            data: { labels: sponsorsLabels, datasets: [{ data: sponsorsMontants, backgroundColor: PALETTE, borderWidth: 2, borderColor: '#fff', hoverOffset: 8 }] },
            options: { responsive: true, plugins: { legend: { position: 'bottom' }, tooltip: { callbacks: { label: ctx => ' '+ctx.label+' : '+ctx.parsed.toLocaleString('fr-TN')+' TND' } } } }
        });
    }

    if (document.getElementById('chartParticipStatut') && typeof participStatutLabels !== 'undefined') {
        new Chart(document.getElementById('chartParticipStatut'), {
            type: 'pie',
            data: { labels: participStatutLabels, datasets: [{ data: participStatutData, backgroundColor: ['#f59e0b','#1db88e','#ef4444'], borderWidth: 2, borderColor: '#fff', hoverOffset: 8 }] },
            options: { responsive: true, plugins: { legend: { position: 'bottom' }, tooltip: { callbacks: { label: ctx => ' '+ctx.label+' : '+ctx.parsed+' participant(s)' } } } }
        });
    }

    if (document.getElementById('chartParticipEvenement') && typeof participEvtLabels !== 'undefined') {
        new Chart(document.getElementById('chartParticipEvenement'), {
            type: 'bar',
            data: { labels: participEvtLabels, datasets: [{ label: 'Participants', data: participEvtData, backgroundColor: '#1a7fa8', borderRadius: 6, borderSkipped: false }] },
            options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: '#f0f4f8' } }, x: { grid: { display: false } } } }
        });
    }

    if (document.getElementById('chartMontantNiveau') && typeof niveauLabels !== 'undefined') {
        const nc = { 'Bronze':'#cd7f32','Argent':'#9ca3af','Or':'#f59e0b','Platine':'#06b6d4' };
        new Chart(document.getElementById('chartMontantNiveau'), {
            type: 'bar',
            data: { labels: niveauLabels, datasets: [{ label: 'Montant (TND)', data: niveauData, backgroundColor: niveauLabels.map(n => nc[n]||'#1a7fa8'), borderRadius: 8, borderSkipped: false }] },
            options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { callback: v => v.toLocaleString('fr-TN')+' TND' }, grid: { color: '#f0f4f8' } }, x: { grid: { display: false } } } }
        });
    }

    document.querySelectorAll('.js-confirm-delete').forEach(btn => {
        btn.addEventListener('click', function(e) {
            if (!window.confirm(this.dataset.msg || 'Confirmer la suppression ?')) e.preventDefault();
        });
    });
});
</script>
</body>
</html>
