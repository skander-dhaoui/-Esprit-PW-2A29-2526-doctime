<?php require __DIR__ . '/layout_header.php'; ?>

<div class="container py-5">

    <div class="row justify-content-center">
        <div class="col-lg-9">

            <h2 class="fw-bold mb-1"><i class="bi bi-calendar-check me-2" style="color:var(--green);"></i>Mes Inscriptions</h2>
            <p class="text-muted mb-4">Retrouvez, modifiez ou annulez vos inscriptions en saisissant votre adresse e-mail.</p>

            <!-- Alertes succès -->
            <?php if (!empty($_GET['success'])): ?>
                <?php if ($_GET['success'] === 'update'): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="bi bi-check-circle me-2"></i>
                    <strong>Inscription mise à jour avec succès !</strong>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php elseif ($_GET['success'] === 'delete'): ?>
                <div class="alert alert-info alert-dismissible fade show">
                    <i class="bi bi-trash me-2"></i>
                    <strong>Inscription supprimée.</strong>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
            <?php endif; ?>

            <!-- Formulaire de recherche par email -->
            <div class="card mb-4">
                <div class="card-body p-4">
                    <?php if (!empty($errors['email'])): ?>
                    <div class="alert alert-danger py-2 mb-3">
                        <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($errors['email']) ?>
                    </div>
                    <?php endif; ?>
                    <form method="GET" action="index.php" class="row g-3 align-items-end">
                        <input type="hidden" name="controller" value="mesinscriptions">
                        <input type="hidden" name="action" value="search">
                        <div class="col-md-8">
                            <label class="form-label fw-semibold" for="email">
                                <i class="bi bi-envelope me-1"></i>Votre adresse e-mail
                            </label>
                            <input type="text" id="email" name="email"
                                   class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                                   placeholder="votre@email.com"
                                   value="<?= htmlspecialchars($email) ?>"
                                   autocomplete="email">
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-green w-100">
                                <i class="bi bi-search me-1"></i>Rechercher
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Résultats -->
            <?php if ($searched): ?>
                <?php if (empty($participations)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-inbox display-4 d-block mb-3"></i>
                    <p>Aucune inscription trouvée pour <strong><?= htmlspecialchars($email) ?></strong>.</p>
                    <a href="index.php?controller=evenements&action=list" class="btn btn-green btn-sm mt-2">
                        <i class="bi bi-calendar2-event me-1"></i>Voir les événements
                    </a>
                </div>
                <?php else: ?>
                <p class="text-muted mb-3">
                    <strong><?= count($participations) ?></strong> inscription(s) trouvée(s) pour
                    <strong><?= htmlspecialchars($email) ?></strong>
                </p>

                <div class="row g-3">
                    <?php foreach ($participations as $p): ?>
                    <?php
                        $statutClass = match($p['statut']) {
                            'confirme'   => 'success',
                            'annule'     => 'danger',
                            default      => 'warning',
                        };
                        $statutLabel = match($p['statut']) {
                            'confirme'   => 'Confirmé',
                            'annule'     => 'Annulé',
                            default      => 'En attente',
                        };
                    ?>
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="row align-items-start g-3">
                                    <!-- Infos événement -->
                                    <div class="col-md-7">
                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            <span class="badge badge-specialite">
                                                <?= htmlspecialchars($p['specialite']) ?>
                                            </span>
                                            <span class="badge bg-<?= $statutClass ?>">
                                                <?= $statutLabel ?>
                                            </span>
                                        </div>
                                        <h5 class="fw-bold mb-1"><?= htmlspecialchars($p['evenement_titre']) ?></h5>
                                        <ul class="list-unstyled small text-muted mb-0">
                                            <li><i class="bi bi-person me-1"></i>
                                                <?= htmlspecialchars($p['prenom']) ?> <?= htmlspecialchars($p['nom']) ?>
                                            </li>
                                            <li><i class="bi bi-briefcase me-1"></i>
                                                <?= htmlspecialchars($p['profession']) ?>
                                            </li>
                                            <li><i class="bi bi-telephone me-1"></i>
                                                <?= htmlspecialchars($p['telephone']) ?>
                                            </li>
                                            <li><i class="bi bi-geo-alt me-1"></i>
                                                <?= htmlspecialchars($p['lieu']) ?>
                                            </li>
                                            <li><i class="bi bi-calendar3 me-1"></i>
                                                <?= date('d/m/Y', strtotime($p['date_debut'])) ?>
                                                → <?= date('d/m/Y', strtotime($p['date_fin'])) ?>
                                            </li>
                                            <?php
                                                $dateIns = $p['date_inscription'] ?? null;
                                                $rel     = $dateIns ? temps_ecoule_fr((string) $dateIns) : '';
                                            ?>
                                            <?php if ($dateIns): ?>
                                            <li class="mt-1 text-muted" style="font-size:.78rem;">
                                                <i class="bi bi-clock-history me-1"></i>
                                                Inscrit le <?= date('d/m/Y à H:i', strtotime((string) $dateIns)) ?>
                                                <?php if ($rel !== ''): ?>
                                                    <span class="text-secondary"> — <?= htmlspecialchars($rel) ?></span>
                                                <?php endif; ?>
                                            </li>
                                            <?php endif; ?>
                                        </ul>
                                    </div>

                                    <!-- Actions -->
                                    <div class="col-md-5 d-flex flex-column gap-2 align-items-md-end">
                                        <?php if ($p['statut'] !== 'annule' && $p['evenement_statut'] !== 'annule'): ?>
                                        <a href="index.php?controller=mesinscriptions&action=frontEdit&id=<?= $p['id'] ?>&email=<?= urlencode($email) ?>"
                                           class="btn btn-outline-primary btn-sm w-100" style="max-width:200px;">
                                            <i class="bi bi-pencil me-1"></i>Modifier
                                        </a>
                                        <?php endif; ?>

                                        <button type="button"
                                                class="btn btn-outline-danger btn-sm w-100"
                                                style="max-width:200px;"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modalSuppr<?= $p['id'] ?>">
                                            <i class="bi bi-trash me-1"></i>Supprimer
                                        </button>

                                        <!-- Modal confirmation suppression -->
                                        <div class="modal fade" id="modalSuppr<?= $p['id'] ?>" tabindex="-1">
                                            <div class="modal-dialog modal-sm">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h6 class="modal-title fw-bold">Confirmer la suppression</h6>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body small text-muted">
                                                        Voulez-vous supprimer votre inscription à
                                                        <strong><?= htmlspecialchars($p['evenement_titre']) ?></strong> ?
                                                        Cette action est irréversible.
                                                    </div>
                                                    <div class="modal-footer gap-2">
                                                        <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Annuler</button>
                                                        <a href="index.php?controller=mesinscriptions&action=frontDelete&id=<?= $p['id'] ?>&email=<?= urlencode($email) ?>"
                                                           class="btn btn-danger btn-sm">Supprimer</a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            <?php endif; ?>

        </div>
    </div>
</div>

<?php require __DIR__ . '/layout_footer.php'; ?>
