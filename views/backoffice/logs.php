<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-history text-primary me-2"></i>Historique des Actions (Logs)</h2>
        <a href="index.php?page=logs&action=export" class="btn btn-sm btn-primary shadow-sm">
            <i class="fas fa-download fa-sm text-white-50"></i> Exporter
        </a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Journal des événements</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Utilisateur</th>
                            <th>Action</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($logs)): ?>
                            <?php 
                            $currentDate = '';
                            foreach ($logs as $log): 
                                $logDate = date('d/m/Y', strtotime($log['created_at']));
                                if ($logDate !== $currentDate):
                                    $currentDate = $logDate;
                            ?>
                                <tr class="table-secondary">
                                    <td colspan="4" class="fw-bold"><i class="fas fa-calendar-day me-2"></i><?= $currentDate ?></td>
                                </tr>
                            <?php endif; ?>
                                <tr>
                                    <td><?= date('H:i:s', strtotime($log['created_at'])) ?></td>
                                    <td>
                                        <?php if ($log['user_id']): ?>
                                            <strong><?= htmlspecialchars($log['prenom'] . ' ' . $log['nom']) ?></strong>
                                        <?php else: ?>
                                            <span class="text-muted">Système</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        // Couleurs par défaut basées sur l'action
                                        $actionColor = 'bg-secondary';
                                        if (stripos($log['action'], 'connexion') !== false) $actionColor = 'bg-info';
                                        if (stripos($log['action'], 'create') !== false || stripos($log['action'], 'ajout') !== false) $actionColor = 'bg-success';
                                        if (stripos($log['action'], 'delete') !== false || stripos($log['action'], 'suppr') !== false) $actionColor = 'bg-danger';
                                        if (stripos($log['action'], 'update') !== false || stripos($log['action'], 'modif') !== false) $actionColor = 'bg-warning text-dark';
                                        ?>
                                        <span class="badge <?= $actionColor ?>"><?= htmlspecialchars($log['action']) ?></span>
                                    </td>
                                    <td><?= htmlspecialchars($log['description']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">Aucun log trouvé pour le moment.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if (isset($totalPages) && $totalPages > 1): ?>
                <nav aria-label="Page navigation" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=logs&p=<?= $page - 1 ?>">Précédent</a>
                        </li>
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                                <a class="page-link" href="?page=logs&p=<?= $i ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=logs&p=<?= $page + 1 ?>">Suivant</a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>
