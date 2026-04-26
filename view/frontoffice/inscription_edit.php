<?php require __DIR__ . '/layout_header.php'; ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-7">

            <div class="mb-4">
                <a href="index.php?controller=mesinscriptions&action=search&email=<?= urlencode($participation['email']) ?>"
                   class="text-muted small">
                    <i class="bi bi-arrow-left me-1"></i>Retour à mes inscriptions
                </a>
            </div>

            <div class="card">
                <div class="card-body p-4">
                    <h3 class="fw-bold mb-1"><i class="bi bi-pencil-square me-2" style="color:var(--green);"></i>Modifier mon inscription</h3>
                    <p class="text-muted mb-4">
                        <i class="bi bi-calendar-event me-1"></i>
                        <strong><?= htmlspecialchars($participation['evenement_titre']) ?></strong>
                    </p>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <strong>Veuillez corriger les erreurs :</strong>
                            <ul class="mb-0 mt-1">
                                <?php foreach ($errors as $e): ?>
                                    <li><?= htmlspecialchars($e) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form action="index.php?controller=mesinscriptions&action=frontUpdate" method="POST" novalidate>
                        <input type="hidden" name="id" value="<?= (int)$old['id'] ?>">
                        <input type="hidden" name="email_original" value="<?= htmlspecialchars($participation['email']) ?>">

                        <div class="row g-3">

                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="nom">Nom <span class="text-danger">*</span></label>
                                <input type="text" id="nom" name="nom"
                                       class="form-control <?= isset($errors['nom']) ? 'is-invalid' : '' ?>"
                                       value="<?= htmlspecialchars($old['nom'] ?? '') ?>">
                                <?php if (isset($errors['nom'])): ?>
                                    <div class="invalid-feedback"><?= htmlspecialchars($errors['nom']) ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="prenom">Prénom <span class="text-danger">*</span></label>
                                <input type="text" id="prenom" name="prenom"
                                       class="form-control <?= isset($errors['prenom']) ? 'is-invalid' : '' ?>"
                                       value="<?= htmlspecialchars($old['prenom'] ?? '') ?>">
                                <?php if (isset($errors['prenom'])): ?>
                                    <div class="invalid-feedback"><?= htmlspecialchars($errors['prenom']) ?></div>
                                <?php endif; ?>
                            </div>

                            <!-- Email en lecture seule -->
                            <div class="col-12">
                                <label class="form-label fw-semibold">Email</label>
                                <input type="text" class="form-control bg-light"
                                       value="<?= htmlspecialchars($participation['email']) ?>" readonly>
                                <div class="form-text">L'adresse e-mail ne peut pas être modifiée.</div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="telephone">Téléphone <span class="text-danger">*</span></label>
                                <input type="text" id="telephone" name="telephone"
                                       class="form-control <?= isset($errors['telephone']) ? 'is-invalid' : '' ?>"
                                       value="<?= htmlspecialchars($old['telephone'] ?? '') ?>"
                                       placeholder="20 123 456">
                                <?php if (isset($errors['telephone'])): ?>
                                    <div class="invalid-feedback"><?= htmlspecialchars($errors['telephone']) ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="profession">Profession <span class="text-danger">*</span></label>
                                <input type="text" id="profession" name="profession"
                                       class="form-control <?= isset($errors['profession']) ? 'is-invalid' : '' ?>"
                                       value="<?= htmlspecialchars($old['profession'] ?? '') ?>"
                                       placeholder="Ex : Médecin cardiologue">
                                <?php if (isset($errors['profession'])): ?>
                                    <div class="invalid-feedback"><?= htmlspecialchars($errors['profession']) ?></div>
                                <?php endif; ?>
                            </div>

                            <!-- Événement en lecture seule -->
                            <div class="col-12">
                                <label class="form-label fw-semibold">Événement</label>
                                <input type="text" class="form-control bg-light"
                                       value="<?= htmlspecialchars($participation['evenement_titre']) ?>" readonly>
                                <div class="form-text">L'événement ne peut pas être modifié.</div>
                            </div>

                        </div>

                        <hr class="my-4">
                        <div class="d-flex gap-3">
                            <button type="submit" class="btn btn-green flex-grow-1 py-2 fw-semibold">
                                <i class="bi bi-check-circle me-2"></i>Enregistrer les modifications
                            </button>
                            <a href="index.php?controller=mesinscriptions&action=search&email=<?= urlencode($participation['email']) ?>"
                               class="btn btn-outline-secondary px-4">
                                Annuler
                            </a>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require __DIR__ . '/layout_footer.php'; ?>
