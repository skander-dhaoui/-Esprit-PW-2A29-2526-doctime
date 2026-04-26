<?php require __DIR__ . '/layout_header.php'; ?>

<div class="container py-5">

    <!-- En-tête + barre de recherche -->
    <div class="d-flex flex-wrap justify-content-between align-items-start mb-4 gap-3">
        <div>
            <h2 class="fw-bold mb-0">Événements médicaux</h2>
            <p class="text-muted mb-0"><?= count($evenements) ?> événement(s) disponible(s)</p>
        </div>
        <form class="d-flex gap-2" method="GET" action="index.php">
            <input type="hidden" name="controller" value="evenements">
            <input type="hidden" name="action" value="list">
            <input type="text" name="q" class="form-control form-control-sm"
                   placeholder="Rechercher un événement…"
                   value="<?= htmlspecialchars($_GET['q'] ?? '') ?>"
                   style="min-width:200px;">
            <button type="submit" class="btn btn-green btn-sm px-3">
                <i class="bi bi-search"></i>
            </button>
        </form>
    </div>

    <!-- Filtres par spécialité -->
    <?php
    $specialites = array_unique(array_column($evenements, 'specialite'));
    $filtreActif = $_GET['specialite'] ?? '';
    ?>
    <?php if (!empty($specialites)): ?>
    <div class="mb-4 d-flex flex-wrap gap-2">
        <a href="index.php?controller=evenements&action=list"
           class="btn btn-sm <?= $filtreActif === '' ? 'btn-green' : 'btn-outline-secondary' ?>">
            Toutes
        </a>
        <?php foreach ($specialites as $sp): ?>
        <a href="index.php?controller=evenements&action=list&specialite=<?= urlencode($sp) ?>"
           class="btn btn-sm <?= $filtreActif === $sp ? 'btn-green' : 'btn-outline-secondary' ?>">
            <?= htmlspecialchars($sp) ?>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Bannière "Déjà inscrit ?" -->
    <div class="alert d-flex align-items-center gap-3 mb-4"
         style="background:var(--green-light); border:1px solid #b6e3f0; border-radius:12px;">
        <i class="bi bi-person-check-fill fs-4" style="color:var(--green);"></i>
        <div class="flex-grow-1">
            <strong>Déjà inscrit(e) à un événement ?</strong>
            Consultez, modifiez ou annulez vos inscriptions depuis votre espace personnel.
        </div>
        <a href="index.php?controller=mesinscriptions&action=search"
           class="btn btn-green btn-sm px-4 text-nowrap">
            Mes Inscriptions <i class="bi bi-arrow-right ms-1"></i>
        </a>
    </div>

    <!-- Grille des événements -->
    <?php
    $liste = $evenements;
    if ($filtreActif !== '') {
        $liste = array_filter($evenements, fn($e) => $e['specialite'] === $filtreActif);
    }
    ?>

    <?php if (empty($liste)): ?>
        <div class="text-center py-5 text-muted">
            <i class="bi bi-calendar-x display-4 d-block mb-3"></i>
            <p>Aucun événement pour cette spécialité.</p>
            <a href="index.php?controller=evenements&action=list" class="btn btn-outline-secondary btn-sm">
                Voir tous les événements
            </a>
        </div>
    <?php else: ?>
    <div class="row g-4">
        <?php foreach ($liste as $e): ?>
        <?php
            $statutClass = match($e['statut'] ?? '') {
                'annule'   => 'danger',
                'termine'  => 'secondary',
                default    => 'success',
            };
            $statutLabel = match($e['statut'] ?? '') {
                'annule'   => 'Annulé',
                'termine'  => 'Terminé',
                'planifie' => 'Planifié',
                default    => ucfirst($e['statut'] ?? ''),
            };
        ?>
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 position-relative">
                <span class="badge bg-<?= $statutClass ?> position-absolute top-0 end-0 m-2"
                      style="font-size:.7rem;">
                    <?= $statutLabel ?>
                </span>
                <div class="card-body d-flex flex-column pt-4">
                    <div class="mb-2 d-flex flex-wrap gap-1">
                        <span class="badge badge-specialite">
                            <?= htmlspecialchars($e['specialite']) ?>
                        </span>
                        <?php if ((float)$e['prix'] == 0): ?>
                            <span class="badge bg-success bg-opacity-75">Gratuit</span>
                        <?php endif; ?>
                    </div>
                    <h5 class="card-title fw-bold"><?= htmlspecialchars($e['titre']) ?></h5>
                    <p class="card-text text-muted small flex-grow-1">
                        <?= htmlspecialchars(mb_substr($e['description'], 0, 110)) ?>…
                    </p>
                    <ul class="list-unstyled small text-muted mt-2 mb-3">
                        <li class="mb-1"><i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($e['lieu']) ?></li>
                        <li class="mb-1">
                            <i class="bi bi-calendar3 me-1"></i>
                            <?= date('d/m/Y', strtotime($e['date_debut'])) ?>
                            <?php if ($e['date_debut'] !== $e['date_fin']): ?>
                                → <?= date('d/m/Y', strtotime($e['date_fin'])) ?>
                            <?php endif; ?>
                        </li>
                        <li class="mb-1"><i class="bi bi-people me-1"></i><?= (int)$e['capacite'] ?> places</li>
                        <?php if ((float)$e['prix'] > 0): ?>
                        <li class="mb-1">
                            <i class="bi bi-cash me-1"></i>
                            <strong><?= number_format((float)$e['prix'], 2, ',', ' ') ?> TND</strong>
                        </li>
                        <?php endif; ?>
                        <?php if (!empty($e['sponsors_nom'])): ?>
                        <li class="mb-1">
                            <i class="bi bi-building me-1"></i>
                            Sponsors : <?= htmlspecialchars($e['sponsors_nom']) ?>
                        </li>
                        <?php endif; ?>
                    </ul>
                    <a href="index.php?controller=evenement&action=detail&id=<?= $e['id'] ?>"
                       class="btn btn-green mt-auto <?= $e['statut'] === 'annule' ? 'disabled' : '' ?>">
                        Voir les détails <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

</div>

<?php require __DIR__ . '/layout_footer.php'; ?>
