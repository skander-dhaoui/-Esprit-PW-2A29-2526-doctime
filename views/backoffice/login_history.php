<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-history text-primary me-2"></i>Historique des Connexions</h1>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Journal des tentatives de connexion</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Utilisateur / Email</th>
                            <th>Navigateur (User Agent)</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($history)): ?>
                            <?php 
                            $currentDate = '';
                            foreach ($history as $log): 
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
                                            <strong><?= htmlspecialchars($log['prenom'] . ' ' . $log['nom']) ?></strong><br>
                                            <small class="text-muted"><?= htmlspecialchars($log['email_attempted']) ?></small>
                                        <?php else: ?>
                                            <span class="text-danger">Utilisateur inconnu</span><br>
                                            <small class="text-muted"><?= htmlspecialchars($log['email_attempted']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><small title="<?= htmlspecialchars($log['user_agent']) ?>"><?= htmlspecialchars(substr($log['user_agent'], 0, 50)) ?>...</small></td>
                                    <td>
                                        <?php if ($log['status'] === 'success'): ?>
                                            <span class="badge bg-success">Succès</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Échec</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">Aucun historique de connexion trouvé.</td>
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
                            <a class="page-link" href="?page=login_history&p=<?= $page - 1 ?>" tabindex="-1">Précédent</a>
                        </li>
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                                <a class="page-link" href="?page=login_history&p=<?= $i ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=login_history&p=<?= $page + 1 ?>">Suivant</a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

