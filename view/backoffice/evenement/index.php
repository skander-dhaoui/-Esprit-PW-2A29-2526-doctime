<?php $pageTitle = 'Gestion des Événements'; ?>
<?php require __DIR__ . '/../layout_header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0 fw-semibold">Liste des Événements</h5>
        <p class="text-muted small mb-0"><?= count($evenements) ?> événement(s)</p>
    </div>
    <div class="d-flex gap-2">
        <!-- ── Lien Métiers avancés (ajout sans modifier l'existant) ── -->
        <a href="index.php?controller=evenementavance&action=recherche" class="btn btn-outline-info btn-sm">
            <i class="bi bi-search me-1"></i> Recherche avancée
        </a>
        <a href="index.php?controller=evenementavance&action=index" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-stars me-1"></i> Vue avancée
        </a>
        <a href="index.php?controller=evenement&action=create" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Nouvel événement
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <?php if (empty($evenements)): ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-calendar-x fs-1 d-block mb-2"></i>
                Aucun événement enregistré.
            </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Titre</th>
                        <th>Spécialité</th>
                        <th>Lieu</th>
                        <th>Dates</th>
                        <th>Capacité</th>
                        <th>Statut</th>
                        <th>Sponsor</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($evenements as $e): ?>
                    <?php
                    $statutBadge = [
                        'planifie' => 'primary',
                        'en_cours' => 'success',
                        'termine'  => 'secondary',
                        'annule'   => 'danger',
                    ];
                    $statutLabel = [
                        'planifie' => 'Planifié',
                        'en_cours' => 'En cours',
                        'termine'  => 'Terminé',
                        'annule'   => 'Annulé',
                    ];
                    ?>
                    <tr>
                        <td class="text-muted small"><?= $e['id'] ?></td>
                        <td class="fw-semibold" style="max-width:200px">
                            <?= htmlspecialchars($e['titre']) ?>
                        </td>
                        <td><span class="badge text-bg-light text-dark border"><?= htmlspecialchars($e['specialite']) ?></span></td>
                        <td><?= htmlspecialchars($e['lieu']) ?></td>
                        <td class="small">
                            <?= date('d/m/Y', strtotime($e['date_debut'])) ?><br>
                            <span class="text-muted">→ <?= date('d/m/Y', strtotime($e['date_fin'])) ?></span>
                        </td>
                        <td><?= $e['capacite'] ?></td>
                        <td>
                            <span class="badge text-bg-<?= $statutBadge[$e['statut']] ?? 'secondary' ?>">
                                <?= $statutLabel[$e['statut']] ?? $e['statut'] ?>
                            </span>
                        </td>
                        <td><?= $e['sponsors_nom'] ? htmlspecialchars($e['sponsors_nom']) : '<span class="text-muted">—</span>' ?></td>
                        <td class="text-center">
                            <a href="index.php?controller=evenement&action=edit&id=<?= $e['id'] ?>"
                               class="btn btn-sm btn-outline-primary me-1" title="Modifier">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <a href="index.php?controller=evenement&action=delete&id=<?= $e['id'] ?>"
                               class="btn btn-sm btn-outline-danger js-confirm-delete" title="Supprimer"
                               data-msg="Supprimer l'événement « <?= htmlspecialchars($e['titre']) ?> » ? Toutes les participations seront également supprimées.">
                                <i class="bi bi-trash"></i>
                            </a>
                            <!-- ── Métiers avancés (ajout sans modifier l'existant) ── -->
                            <a href="index.php?controller=evenementavance&action=stats&id=<?= $e['id'] ?>"
                               class="btn btn-sm btn-outline-info ms-1" title="Statistiques avancées">
                                <i class="bi bi-bar-chart"></i>
                            </a>
                            <a href="index.php?controller=evenementavance&action=exportPreview&id=<?= $e['id'] ?>"
                               class="btn btn-sm btn-outline-success" title="Exporter participants CSV">
                                <i class="bi bi-download"></i>
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
