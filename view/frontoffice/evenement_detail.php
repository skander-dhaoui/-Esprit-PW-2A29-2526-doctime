<?php require __DIR__ . '/layout_header.php'; ?>

<div class="container py-5">

    <?php if (!empty($_GET['success']) && $_GET['success'] === 'inscrit'): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle me-2"></i>
            <strong>Inscription enregistrée !</strong> Vous recevrez une confirmation prochainement.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Détail principal -->
        <div class="col-lg-8">
            <div class="card p-4">
                <span class="badge badge-specialite mb-3 d-inline-block">
                    <?= htmlspecialchars($evenement['specialite']) ?>
                </span>
                <h2 class="fw-bold"><?= htmlspecialchars($evenement['titre']) ?></h2>
                <p class="text-muted mt-3" style="line-height: 1.8">
                    <?= nl2br(htmlspecialchars($evenement['description'])) ?>
                </p>
            </div>
        </div>

        <!-- Sidebar info -->
        <div class="col-lg-4">
            <div class="card p-4 mb-3">
                <h6 class="fw-bold mb-3 text-muted text-uppercase small">Informations</h6>
                <ul class="list-unstyled">
                    <li class="mb-2 d-flex gap-2">
                        <i class="bi bi-geo-alt-fill text-success mt-1"></i>
                        <span><?= htmlspecialchars($evenement['lieu']) ?></span>
                    </li>
                    <li class="mb-2 d-flex gap-2">
                        <i class="bi bi-calendar-event-fill text-success mt-1"></i>
                        <span>
                            Du <?= date('d/m/Y', strtotime($evenement['date_debut'])) ?>
                            au <?= date('d/m/Y', strtotime($evenement['date_fin'])) ?>
                            <?php
                                $nj = duree_evenement_jours((string) $evenement['date_debut'], (string) $evenement['date_fin']);
                                if ($nj > 0):
                            ?>
                                <span class="d-block small text-muted mt-1">
                                    Durée : <?= $nj === 1 ? '1 jour' : "{$nj} jours" ?> (calendaire)
                                </span>
                            <?php endif; ?>
                        </span>
                    </li>
                    <li class="mb-2 d-flex gap-2">
                        <i class="bi bi-people-fill text-success mt-1"></i>
                        <span><?= $evenement['capacite'] ?> places au total</span>
                    </li>
                    <li class="mb-2 d-flex gap-2">
                        <i class="bi bi-ticket-fill text-success mt-1"></i>
                        <span>
                            <?php if ($placesRestantes > 0): ?>
                                <strong class="text-success"><?= $placesRestantes ?> place(s) disponible(s)</strong>
                            <?php else: ?>
                                <strong class="text-danger">Complet</strong>
                            <?php endif; ?>
                        </span>
                    </li>
                    <li class="mb-2 d-flex gap-2">
                        <i class="bi bi-cash-stack text-success mt-1"></i>
                        <span>
                            <?= $evenement['prix'] > 0
                                ? number_format($evenement['prix'], 2, ',', ' ') . ' TND'
                                : '<strong class="text-success">Gratuit</strong>' ?>
                        </span>
                    </li>
                    <?php if ($evenement['sponsors_nom']): ?>
                    <li class="mb-2 d-flex gap-2">
                        <i class="bi bi-building-fill text-success mt-1"></i>
                        <span>Sponsors : <strong><?= htmlspecialchars($evenement['sponsors_nom']) ?></strong></span>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>

            <?php if ($placesRestantes > 0 && $evenement['statut'] === 'planifie'): ?>
            <a href="index.php?controller=participation&action=inscrire&evenement_id=<?= $evenement['id'] ?>"
               class="btn btn-green w-100 btn-lg">
                <i class="bi bi-person-plus me-2"></i>S'inscrire
            </a>
            <?php elseif ($placesRestantes <= 0): ?>
            <button class="btn btn-secondary w-100 btn-lg" disabled>
                <i class="bi bi-x-circle me-2"></i>Complet
            </button>
            <?php endif; ?>

            <a href="index.php?controller=evenement&action=list" class="btn btn-outline-secondary w-100 mt-2">
                <i class="bi bi-arrow-left me-1"></i>Retour aux événements
            </a>
        </div>
    </div>
</div>

<?php require __DIR__ . '/layout_footer.php'; ?>
