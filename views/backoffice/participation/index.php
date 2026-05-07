<?php $pageTitle = 'Gestion des Participations'; ?>
<?php require __DIR__ . '/../layout_header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0 fw-semibold">Liste des Participations</h5>
        <p class="text-muted small mb-0"><?= count($participations) ?> participant(s)</p>
    </div>
    <a href="index.php?controller=participation&action=create" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> Nouvelle participation
    </a>
</div>

<div class="card">
    <div class="card-body p-0">
        <?php if (empty($participations)): ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-people fs-1 d-block mb-2"></i>
                Aucune participation enregistrée.
            </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Participant</th>
                        <th>Email</th>
                        <th>Téléphone</th>
                        <th>Profession</th>
                        <th>Événement</th>
                        <th>Statut</th>
                        <th>Date inscription</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($participations as $p): ?>
                    <?php
                    $badgeMap = ['en_attente'=>'warning','confirme'=>'success','annule'=>'danger'];
                    $labelMap = ['en_attente'=>'En attente','confirme'=>'Confirmé','annule'=>'Annulé'];
                    ?>
                    <tr>
                        <td class="text-muted small"><?= $p['id'] ?></td>
                        <td class="fw-semibold">
                            <?= htmlspecialchars($p['prenom'] . ' ' . $p['nom']) ?>
                        </td>
                        <td><?= htmlspecialchars($p['email']) ?></td>
                        <td><?= htmlspecialchars($p['telephone']) ?></td>
                        <td><?= htmlspecialchars($p['profession']) ?></td>
                        <td class="small"><?= htmlspecialchars($p['evenement_titre']) ?></td>
                        <td>
                            <span class="badge text-bg-<?= $badgeMap[$p['statut']] ?? 'secondary' ?>">
                                <?= $labelMap[$p['statut']] ?? $p['statut'] ?>
                            </span>
                        </td>
                        <td class="small"><?= date('d/m/Y', strtotime($p['date_inscription'])) ?></td>
                        <td class="text-center">
                            <a href="index.php?controller=participation&action=edit&id=<?= $p['id'] ?>"
                               class="btn btn-sm btn-outline-primary me-1" title="Modifier">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <a href="index.php?controller=participation&action=delete&id=<?= $p['id'] ?>"
                               class="btn btn-sm btn-outline-danger js-confirm-delete" title="Supprimer"
                               data-msg="Supprimer la participation de « <?= htmlspecialchars($p['prenom'].' '.$p['nom']) ?> » ?">
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
