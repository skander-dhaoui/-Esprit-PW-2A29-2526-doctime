<?php
/**
 * Historique / logs — shell DocTime (layout_header / footer)
 *
 * Variables attendues : $logs, $totalPages, $logPageNum (contrôleur AdminController::logs)
 */
$pageTitle = 'Historique / Logs';
$logs = $logs ?? [];
$totalPages = isset($totalPages) ? (int) $totalPages : 1;
$totalRows = isset($totalRows) ? (int) $totalRows : 0;
$logsTableMissing = $logsTableMissing ?? false;
$logPageNum = isset($logPageNum) ? max(1, (int) $logPageNum) : max(1, (int) ($_GET['p'] ?? 1));

require_once __DIR__ . '/layout_header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="h4 mb-1 fw-bold" style="color:#1e293b;"><i class="fas fa-history me-2 text-primary"></i>Historique des actions</h1>
        <p class="text-muted small mb-0">Connexions et actions enregistrées dans la base (table <code>logs</code>).</p>
    </div>
    <a href="index.php?page=logs&amp;action=export" class="btn btn-primary btn-sm rounded-pill px-3">
        <i class="fas fa-download me-1"></i>Exporter CSV
    </a>
</div>

<?php if (!empty($logsTableMissing)): ?>
    <div class="alert alert-warning border-0 shadow-sm mb-4">
        <i class="fas fa-database me-2"></i>
        La table <strong>logs</strong> n’existe pas dans la base. Exécutez le script
        <code>database/logs_table.sql</code> (ou réimportez <code>database/doctime_full.sql</code> mis à jour) dans phpMyAdmin.
    </div>
<?php endif; ?>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-3 border-bottom">
        <h6 class="mb-0 fw-bold"><i class="fas fa-list me-2 text-secondary"></i>Journal des événements</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="small text-uppercase text-muted">Date</th>
                        <th class="small text-uppercase text-muted">Utilisateur</th>
                        <th class="small text-uppercase text-muted">Action</th>
                        <th class="small text-uppercase text-muted">Description</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($logs)): ?>
                        <?php
                        $currentDate = '';
                        foreach ($logs as $log):
                            $created = $log['created_at'] ?? '';
                            $logDate = $created ? date('d/m/Y', strtotime((string) $created)) : '—';
                            if ($logDate !== $currentDate):
                                $currentDate = $logDate;
                        ?>
                        <tr class="table-secondary">
                            <td colspan="4" class="fw-bold small py-2"><i class="fas fa-calendar-day me-2"></i><?= htmlspecialchars($currentDate, ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                        <?php endif; ?>
                        <tr>
                            <td class="text-nowrap small"><?= $created ? date('H:i:s', strtotime((string) $created)) : '—' ?></td>
                            <td>
                                <?php if (!empty($log['user_id'])): ?>
                                    <strong><?= htmlspecialchars(trim(($log['prenom'] ?? '') . ' ' . ($log['nom'] ?? '')), ENT_QUOTES, 'UTF-8') ?></strong>
                                    <?php if (!empty($log['role'])): ?>
                                        <span class="badge bg-light text-secondary border ms-1"><?= htmlspecialchars((string) $log['role'], ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted">Système</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $act = (string) ($log['action'] ?? '');
                                $actionColor = 'bg-secondary';
                                if (stripos($act, 'connexion') !== false) {
                                    $actionColor = 'bg-info';
                                }
                                if (stripos($act, 'create') !== false || stripos($act, 'ajout') !== false) {
                                    $actionColor = 'bg-success';
                                }
                                if (stripos($act, 'delete') !== false || stripos($act, 'suppr') !== false) {
                                    $actionColor = 'bg-danger';
                                }
                                if (stripos($act, 'update') !== false || stripos($act, 'modif') !== false) {
                                    $actionColor = 'bg-warning text-dark';
                                }
                                ?>
                                <span class="badge <?= $actionColor ?>"><?= htmlspecialchars($act, ENT_QUOTES, 'UTF-8') ?></span>
                            </td>
                            <td class="small"><?= htmlspecialchars((string) ($log['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center py-5 text-muted">
                                <i class="fas fa-inbox fa-2x d-block mb-2 opacity-50"></i>
                                Aucun log pour le moment.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if ($totalPages > 1): ?>
    <nav aria-label="Pagination logs" class="mt-3">
        <ul class="pagination justify-content-center flex-wrap">
            <li class="page-item <?= $logPageNum <= 1 ? 'disabled' : '' ?>">
                <a class="page-link rounded-pill" href="index.php?page=logs&amp;p=<?= max(1, $logPageNum - 1) ?>">Précédent</a>
            </li>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?= $logPageNum === $i ? 'active' : '' ?>">
                    <a class="page-link" href="index.php?page=logs&amp;p=<?= $i ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
            <li class="page-item <?= $logPageNum >= $totalPages ? 'disabled' : '' ?>">
                <a class="page-link rounded-pill" href="index.php?page=logs&amp;p=<?= min($totalPages, $logPageNum + 1) ?>">Suivant</a>
            </li>
        </ul>
    </nav>
<?php endif; ?>

<?php require_once __DIR__ . '/layout_footer.php'; ?>
