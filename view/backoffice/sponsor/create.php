<?php $pageTitle = 'Nouveau Sponsor'; ?>
<?php require __DIR__ . '/../layout_header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0 fw-semibold">Nouveau Sponsor</h5>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item"><a href="index.php?controller=sponsor&action=index">Sponsors</a></li>
                <li class="breadcrumb-item active">Nouveau</li>
            </ol>
        </nav>
    </div>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <i class="bi bi-exclamation-triangle me-2"></i>
        <strong>Veuillez corriger les erreurs suivantes :</strong>
        <ul class="mb-0 mt-1">
            <?php foreach ($errors as $e): ?>
                <li><?= htmlspecialchars($e) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header py-3">
        <h6 class="mb-0"><i class="bi bi-building me-2"></i>Informations du sponsor</h6>
    </div>
    <div class="card-body">
        <form action="index.php?controller=sponsor&action=store" method="POST"
              id="form-sponsor" novalidate>
            <div class="row g-3">

                <div class="col-md-6">
                    <label class="form-label" for="nom">Nom de l'entreprise <span class="text-danger">*</span></label>
                    <input type="text" id="nom" name="nom"
                           class="form-control <?= isset($errors['nom']) ? 'is-invalid' : '' ?>"
                           value="<?= htmlspecialchars($old['nom'] ?? '') ?>"
                           data-validate="required|minlength:2|maxlength:100"
                           data-label="Nom">
                    <?php if (isset($errors['nom'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errors['nom']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="email">Email <span class="text-danger">*</span></label>
                    <input type="text" id="email" name="email"
                           class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                           value="<?= htmlspecialchars($old['email'] ?? '') ?>"
                           data-validate="required|email"
                           data-label="Email">
                    <?php if (isset($errors['email'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errors['email']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="telephone">Téléphone <span class="text-danger">*</span></label>
                    <input type="text" id="telephone" name="telephone"
                           class="form-control <?= isset($errors['telephone']) ? 'is-invalid' : '' ?>"
                           value="<?= htmlspecialchars($old['telephone'] ?? '') ?>"
                           data-validate="required|phone"
                           data-label="Téléphone"
                           placeholder="Ex : 71 234 567">
                    <?php if (isset($errors['telephone'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errors['telephone']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="site_web">Site web</label>
                    <input type="text" id="site_web" name="site_web"
                           class="form-control <?= isset($errors['site_web']) ? 'is-invalid' : '' ?>"
                           value="<?= htmlspecialchars($old['site_web'] ?? '') ?>"
                           data-validate="url"
                           data-label="Site web"
                           placeholder="https://exemple.com">
                    <?php if (isset($errors['site_web'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errors['site_web']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="niveau">Niveau <span class="text-danger">*</span></label>
                    <select id="niveau" name="niveau"
                            class="form-select <?= isset($errors['niveau']) ? 'is-invalid' : '' ?>"
                            data-validate="required"
                            data-label="Niveau">
                        <option value="">-- Choisir --</option>
                        <?php foreach (['bronze','argent','or','platine'] as $n): ?>
                            <option value="<?= $n ?>" <?= ($old['niveau'] ?? '') === $n ? 'selected' : '' ?>>
                                <?= ucfirst($n) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['niveau'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errors['niveau']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="montant">Montant (TND) <span class="text-danger">*</span></label>
                    <input type="text" id="montant" name="montant"
                           class="form-control <?= isset($errors['montant']) ? 'is-invalid' : '' ?>"
                           value="<?= htmlspecialchars($old['montant'] ?? '') ?>"
                           data-validate="required|positive"
                           data-label="Montant"
                           placeholder="Ex : 5000">
                    <?php if (isset($errors['montant'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errors['montant']) ?></div>
                    <?php endif; ?>
                </div>

            </div><!-- /row -->

            <hr class="my-4">
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary px-4">
                    <i class="bi bi-check-lg me-1"></i> Enregistrer
                </button>
                <a href="index.php?controller=sponsor&action=index" class="btn btn-outline-secondary">
                    Annuler
                </a>
            </div>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../layout_footer.php'; ?>
