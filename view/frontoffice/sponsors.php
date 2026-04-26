<?php require __DIR__ . '/layout_header.php'; ?>

<div class="container py-5">
    <h2 class="fw-bold mb-2">Nos Sponsors</h2>
    <p class="text-muted mb-5">Partenaires qui soutiennent les événements médicaux.</p>

    <?php if (empty($sponsors)): ?>
        <div class="text-center text-muted py-5">
            <i class="bi bi-building display-4 d-block mb-3"></i>
            <p>Aucun sponsor enregistré pour le moment.</p>
        </div>
    <?php else: ?>
    <div class="row g-4">
        <?php
        $nivBadge = ['bronze'=>'warning','argent'=>'secondary','or'=>'warning','platine'=>'info'];
        foreach ($sponsors as $s): ?>
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 p-3">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center"
                         style="width:50px;height:50px;background:#e8f5ee">
                        <i class="bi bi-building text-success fs-4"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-0"><?= htmlspecialchars($s['nom']) ?></h6>
                        <span class="badge text-bg-<?= $nivBadge[$s['niveau']] ?? 'secondary' ?>">
                            <?= ucfirst($s['niveau']) ?>
                        </span>
                    </div>
                </div>
                <ul class="list-unstyled small text-muted">
                    <li><i class="bi bi-envelope me-1"></i><?= htmlspecialchars($s['email']) ?></li>
                    <li><i class="bi bi-telephone me-1"></i><?= htmlspecialchars($s['telephone']) ?></li>
                    <?php if ($s['site_web']): ?>
                    <li>
                        <i class="bi bi-globe me-1"></i>
                        <a href="<?= htmlspecialchars($s['site_web']) ?>" target="_blank">
                            <?= htmlspecialchars($s['site_web']) ?>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/layout_footer.php'; ?>
