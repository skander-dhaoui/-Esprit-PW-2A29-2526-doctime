<?php
$pageTitle  = 'Gestion des Commandes';
$activePage = 'commandes';
require __DIR__ . '/_layout_top.php';
?>

<?php if ($flash): ?>
<div class="flash-box flash-<?= $flash['type'] === 'success' ? 'success' : 'error' ?>">
    <?= htmlspecialchars($flash['message']) ?>
</div>
<?php endif; ?>

<!-- KPI -->
<div class="row g-3 mb-4">
    <?php $kpis = [
        ['label'=>'Total',       'val'=>$stats['total'],      'color'=>'#607d8b', 'icon'=>'shopping-cart'],
        ['label'=>'En attente',  'val'=>$stats['en_attente'], 'color'=>'#FF9800', 'icon'=>'clock'],
        ['label'=>'Confirmées',  'val'=>$stats['confirmees'], 'color'=>'#2196F3', 'icon'=>'check'],
        ['label'=>'Livrées',     'val'=>$stats['livrees'],    'color'=>'#4CAF50', 'icon'=>'truck'],
        ['label'=>'Annulées',    'val'=>$stats['annulees'],   'color'=>'#f44336', 'icon'=>'times'],
        ['label'=>'CA Total',    'val'=>number_format($stats['ca_total'],2).' TND', 'color'=>'#9C27B0', 'icon'=>'coins'],
    ];
    foreach ($kpis as $k): ?>
    <div class="col-6 col-md-2">
        <div class="stat-card" style="border-color:<?= $k['color'] ?>">
            <i class="fas fa-<?= $k['icon'] ?> fa-xl" style="color:<?= $k['color'] ?>44;float:right"></i>
            <h3 style="color:<?= $k['color'] ?>;font-size:22px"><?= $k['val'] ?></h3>
            <p><?= $k['label'] ?></p>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-4">
        <div class="content-card h-100">
            <h6 style="font-weight:700;color:#1e2a3e">Répartition des statuts</h6>
            <canvas id="commandesChart" height="220"></canvas>
        </div>
    </div>
</div>

<!-- Filtres -->
<div class="content-card mb-4">
    <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;" novalidate>
        <input type="hidden" name="page" value="commandes_admin">
        <div>
            <label class="form-label">Recherche</label>
            <input type="text" id="commandeSearch" name="search" class="form-control"
                   placeholder="N° commande, client..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" style="width:230px">
        </div>
        <div>
            <label class="form-label">Statut</label>
            <select id="commandeStatus" name="statut" class="form-select" style="width:170px">
                <option value="">Tous</option>
                <?php foreach (['en_attente'=>'En attente','confirmee'=>'Confirmée','en_preparation'=>'En préparation','expediee'=>'Expédiée','livree'=>'Livrée','annulee'=>'Annulée'] as $v=>$l): ?>
                <option value="<?= $v ?>" <?= ($_GET['statut'] ?? '') === $v ? 'selected' : '' ?>><?= $l ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="form-label">Tri</label>
            <select id="commandeSort" class="form-select" style="width:180px">
                <option value="default">Par défaut</option>
                <option value="date_desc">Date récente</option>
                <option value="date_asc">Date ancienne</option>
                <option value="total_desc">Total décroissant</option>
                <option value="total_asc">Total croissant</option>
                <option value="articles_desc">Plus d’articles</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary"><i class="fas fa-search me-1"></i>Filtrer</button>
        <a href="index.php?page=commandes_admin" class="btn btn-outline-secondary">Réinitialiser</a>
        <a href="index.php?page=commandes_admin&action=export_csv&<?= http_build_query(array_filter(['search' => $_GET['search'] ?? '', 'statut' => $_GET['statut'] ?? ''])) ?>" class="btn btn-outline-success">
            <i class="fas fa-file-csv me-1"></i>Exporter CSV
        </a>
        <a href="index.php?page=commandes_admin&action=create" class="btn btn-success ms-auto">
            <i class="fas fa-plus me-1"></i>Nouvelle commande
        </a>
    </form>
</div>

<!-- Table -->
<div class="content-card">
    <h6 id="commandesCount" style="font-weight:700;margin-bottom:16px;color:#1e2a3e"><?= count($commandes) ?> commande(s)</h6>
    <?php if (empty($commandes)): ?>
    <div style="text-align:center;padding:50px;color:#999">
        <i class="fas fa-shopping-cart fa-3x mb-3" style="opacity:.3"></i>
        <p>Aucune commande trouvée.</p>
    </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="commandesTable">
            <thead style="background:#f8f9fa">
                <tr>
                    <th>N° Commande</th>
                    <th>Client</th>
                    <th>Articles</th>
                    <th>Total TTC</th>
                    <th>Paiement</th>
                    <th>Statut</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($commandes as $c): ?>
            <tr data-num="<?= strtolower(htmlspecialchars($c['numero_commande'])) ?>"
                data-client="<?= strtolower(htmlspecialchars($c['user_prenom'] . ' ' . $c['user_nom'])) ?>"
                data-statut="<?= htmlspecialchars($c['statut']) ?>"
                data-total="<?= (float)$c['total_ttc'] ?>"
                data-articles="<?= (int)$c['nb_articles'] ?>"
                data-date="<?= strtotime($c['created_at']) ?>">
                <td><strong><?= htmlspecialchars($c['numero_commande']) ?></strong></td>
                <td><?= htmlspecialchars($c['user_prenom'] . ' ' . $c['user_nom']) ?></td>
                <td><span class="badge" style="background:#e3f2fd;color:#1565c0"><?= $c['nb_articles'] ?></span></td>
                <td><strong><?= number_format($c['total_ttc'], 2) ?> TND</strong></td>
                <td><small><?= ucfirst($c['mode_paiement']) ?></small></td>
                <td>
                    <span class="badge badge-<?= $c['statut'] ?>">
                        <?= ucfirst(str_replace('_', ' ', $c['statut'])) ?>
                    </span>
                </td>
                <td><small><?= date('d/m/Y', strtotime($c['created_at'])) ?></small></td>
                <td>
                    <a href="index.php?page=commandes_admin&action=show&id=<?= $c['id'] ?>"
                       class="btn btn-sm btn-outline-info"><i class="fas fa-eye"></i></a>
                    <a href="index.php?page=commandes_admin&action=edit&id=<?= $c['id'] ?>"
                       class="btn btn-sm btn-outline-warning"><i class="fas fa-edit"></i></a>
                    <a href="index.php?page=commandes_admin&action=delete&id=<?= $c['id'] ?>"
                       class="btn btn-sm btn-outline-danger"
                       onclick="return confirm('Supprimer cette commande ?')"><i class="fas fa-trash"></i></a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="assets/js/valo-backoffice.js"></script>
<script>
(function() {
    const searchInput = document.getElementById('commandeSearch');
    const statusFilter = document.getElementById('commandeStatus');
    const sortSelect = document.getElementById('commandeSort');
    const table = document.getElementById('commandesTable');
    const countBox = document.getElementById('commandesCount');

    if (table) {
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));

        function matches(row) {
            const q = (searchInput?.value || '').trim().toLowerCase();
            const status = statusFilter?.value || '';
            if (q && !((row.dataset.num + ' ' + row.dataset.client).includes(q))) return false;
            if (status && row.dataset.statut !== status) return false;
            return true;
        }

        function sortRows(list) {
            const mode = sortSelect?.value || 'default';
            const sorted = [...list];
            if (mode === 'date_desc') sorted.sort((a, b) => Number(b.dataset.date) - Number(a.dataset.date));
            else if (mode === 'date_asc') sorted.sort((a, b) => Number(a.dataset.date) - Number(b.dataset.date));
            else if (mode === 'total_desc') sorted.sort((a, b) => Number(b.dataset.total) - Number(a.dataset.total));
            else if (mode === 'total_asc') sorted.sort((a, b) => Number(a.dataset.total) - Number(b.dataset.total));
            else if (mode === 'articles_desc') sorted.sort((a, b) => Number(b.dataset.articles) - Number(a.dataset.articles));
            return sorted;
        }

        function apply() {
            const visible = rows.filter(matches);
            const ordered = sortRows(visible);
            tbody.innerHTML = '';
            ordered.forEach(row => tbody.appendChild(row));
            if (countBox) countBox.textContent = visible.length + ' commande(s)';
        }

        [searchInput, statusFilter, sortSelect].forEach(el => {
            if (!el) return;
            el.addEventListener(el === searchInput ? 'input' : 'change', apply);
        });

        apply();
    }

    const ctx = document.getElementById('commandesChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['En attente', 'Confirmées', 'En préparation', 'Expédiées', 'Livrées', 'Annulées'],
                datasets: [{
                    data: [<?= (int)$stats['en_attente'] ?>, <?= (int)$stats['confirmees'] ?>, <?= (int)$stats['en_preparation'] ?>, <?= (int)$stats['expediees'] ?>, <?= (int)$stats['livrees'] ?>, <?= (int)$stats['annulees'] ?>],
                    backgroundColor: ['#FF9800', '#2196F3', '#9C27B0', '#00BCD4', '#4CAF50', '#f44336'],
                    borderWidth: 0
                }]
            },
            options: { plugins: { legend: { position: 'bottom' } } }
        });
    }
})();
</script>
</div></body></html>
