<?php $pageTitle = 'Gestion des Sponsors'; ?>
<?php require __DIR__ . '/../layout_header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0 fw-semibold">Liste des Sponsors</h5>
        <p class="text-muted small mb-0"><?= count($sponsors) ?> sponsor(s) enregistré(s)</p>
    </div>
    <a href="index.php?controller=sponsor&action=create" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> Nouveau sponsor
    </a>
</div>

<div class="card">
    <div class="card-body p-0">
        <?php if (empty($sponsors)): ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-building fs-1 d-block mb-2"></i>
                Aucun sponsor enregistré.
            </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nom</th>
                        <th>Email</th>
                        <th>Téléphone</th>
                        <th>Niveau</th>
                        <th>Montant (TND)</th>
                        <th>Site web</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($sponsors as $s): ?>
                    <tr>
                        <td class="text-muted small"><?= $s['id'] ?></td>
                        <td class="fw-semibold"><?= htmlspecialchars($s['nom']) ?></td>
                        <td><?= htmlspecialchars($s['email']) ?></td>
                        <td><?= htmlspecialchars($s['telephone']) ?></td>
                        <td>
                            <?php $badges = ['bronze'=>'warning','argent'=>'secondary','or'=>'warning','platine'=>'info']; ?>
                            <span class="badge text-bg-<?= $badges[$s['niveau']] ?? 'secondary' ?>">
                                <?= ucfirst($s['niveau']) ?>
                            </span>
                        </td>
                        <td><?= number_format($s['montant'], 2, ',', ' ') ?></td>
                        <td>
                            <?php if ($s['site_web']): ?>
                                <a href="<?= htmlspecialchars($s['site_web']) ?>" target="_blank" class="small">
                                    <i class="bi bi-link-45deg"></i> Voir
                                </a>
                            <?php else: ?>
                                <span class="text-muted small">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <a href="index.php?controller=sponsor&action=edit&id=<?= $s['id'] ?>"
                               class="btn btn-sm btn-outline-primary me-1" title="Modifier">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <a href="index.php?controller=sponsor&action=delete&id=<?= $s['id'] ?>"
                               class="btn btn-sm btn-outline-danger js-confirm-delete" title="Supprimer"
                               data-msg="Supprimer le sponsor « <?= htmlspecialchars($s['nom']) ?> » ?">
                                <i class="bi bi-trash"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/../layout_footer.php'; ?>
