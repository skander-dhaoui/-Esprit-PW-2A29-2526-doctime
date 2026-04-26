<?php require __DIR__ . '/layout_header.php'; ?>

<!-- Hero -->
<section style="background: linear-gradient(135deg, #1a6b3c 0%, #2d9e60 100%); color: #fff; padding: 80px 0;">
    <div class="container text-center">
        <i class="bi bi-hospital display-3 mb-3 d-block"></i>
        <h1 class="fw-bold display-5">Événements Médicaux en Tunisie</h1>
        <p class="lead mt-3 mb-4 opacity-75">
            Congrès, symposiums et journées scientifiques dédiés aux professionnels de santé.
        </p>
        <a href="index.php?controller=evenement&action=list" class="btn btn-light btn-lg fw-semibold px-5">
            <i class="bi bi-calendar-event me-2"></i>Voir les événements
        </a>
    </div>
</section>

<!-- Stats -->
<section class="py-5 bg-white">
    <div class="container">
        <div class="row g-4 text-center">
            <div class="col-md-4">
                <div class="py-4">
                    <i class="bi bi-calendar-check text-success fs-1"></i>
                    <h3 class="fw-bold mt-2">+20</h3>
                    <p class="text-muted">Événements planifiés</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="py-4">
                    <i class="bi bi-people text-primary fs-1"></i>
                    <h3 class="fw-bold mt-2">+500</h3>
                    <p class="text-muted">Participants attendus</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="py-4">
                    <i class="bi bi-building text-warning fs-1"></i>
                    <h3 class="fw-bold mt-2">+10</h3>
                    <p class="text-muted">Sponsors partenaires</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Spécialités -->
<section class="py-5">
    <div class="container">
        <h2 class="fw-bold text-center mb-5">Spécialités couvertes</h2>
        <div class="row g-3 justify-content-center">
            <?php
            $specialites = [
                ['icon'=>'bi-heart-pulse','label'=>'Cardiologie'],
                ['icon'=>'bi-bandaid','label'=>'Dermatologie'],
                ['icon'=>'bi-activity','label'=>'Oncologie'],
                ['icon'=>'bi-brain','label'=>'Neurologie'],
                ['icon'=>'bi-person-hearts','label'=>'Pédiatrie'],
                ['icon'=>'bi-scissors','label'=>'Chirurgie'],
            ];
            foreach ($specialites as $sp): ?>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="card text-center p-3 h-100">
                    <i class="bi <?= $sp['icon'] ?> fs-2 mb-2" style="color:var(--green)"></i>
                    <small class="fw-semibold"><?= $sp['label'] ?></small>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="py-5 text-center" style="background: #e8f5ee;">
    <div class="container">
        <h3 class="fw-bold">Prêt à vous inscrire ?</h3>
        <p class="text-muted mt-2 mb-4">Consultez les prochains événements et réservez votre place.</p>
        <a href="index.php?controller=evenement&action=list" class="btn btn-green btn-lg px-5">
            S'inscrire à un événement
        </a>
    </div>
</section>

<?php require __DIR__ . '/layout_footer.php'; ?>
