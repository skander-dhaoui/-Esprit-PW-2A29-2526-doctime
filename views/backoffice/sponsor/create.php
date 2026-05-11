<?php $pageTitle = 'Nouveau Sponsor'; ?>
<?php require __DIR__ . '/../layout_header.php'; ?>

<div class="bo-create-page">
    <?php if (!empty($_SESSION['flash'])): ?>
        <?php $f = $_SESSION['flash']; unset($_SESSION['flash']); ?>
        <div class="alert alert-<?= (($f['type'] ?? '') === 'error' || ($f['type'] ?? '') === 'danger') ? 'danger' : 'success' ?> alert-dismissible fade show mb-4">
            <?= htmlspecialchars($f['message'] ?? '') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
        </div>
    <?php endif; ?>

    <div class="bo-create-header mb-4">
        <h1 class="bo-create-title">Nouveau Sponsor</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="index.php?page=sponsors_admin">Sponsors</a></li>
                <li class="breadcrumb-item active" aria-current="page">Nouveau</li>
            </ol>
        </nav>
    </div>

    <div class="card bo-create-card shadow-sm">
        <div class="card-header bo-create-card-head py-3">
            <h6 class="mb-0"><i class="bi bi-building me-2"></i>Informations du sponsor</h6>
        </div>
        <div class="card-body">
            <form class="bo-create-form"
                  action="index.php?page=sponsors_admin&action=create" method="POST"
                  id="form-sponsor" novalidate>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">
                <div class="row g-3">

                    <div class="col-md-6">
                        <label class="form-label" for="nom">Nom de l'entreprise <span class="text-danger">*</span></label>
                        <input type="text" id="nom" name="nom"
                               class="form-control <?= isset($errors['nom']) ? 'is-invalid' : '' ?>"
                               value="<?= htmlspecialchars($old['nom'] ?? '') ?>">
                        <?php if (isset($errors['nom'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['nom']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="email">Email <span class="text-danger">*</span></label>
                        <input type="email" id="email" name="email" autocomplete="email"
                               class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                               value="<?= htmlspecialchars($old['email'] ?? '') ?>">
                        <?php if (isset($errors['email'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['email']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="telephone">Téléphone <span class="text-danger">*</span></label>
                        <input type="text" id="telephone" name="telephone" inputmode="tel"
                               class="form-control <?= isset($errors['telephone']) ? 'is-invalid' : '' ?>"
                               value="<?= htmlspecialchars($old['telephone'] ?? '') ?>"
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
                               placeholder="https://exemple.com">
                        <?php if (isset($errors['site_web'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['site_web']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="niveau">Niveau <span class="text-danger">*</span></label>
                        <select id="niveau" name="niveau"
                                class="form-select <?= isset($errors['niveau']) ? 'is-invalid' : '' ?>">
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
                        <input type="text" id="montant" name="montant" inputmode="decimal"
                               class="form-control <?= isset($errors['montant']) ? 'is-invalid' : '' ?>"
                               value="<?= htmlspecialchars($old['montant'] ?? '') ?>"
                               placeholder="Ex : 5000">
                        <?php if (isset($errors['montant'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['montant']) ?></div>
                        <?php endif; ?>
                    </div>

                </div>

                <div class="bo-create-actions d-flex flex-wrap gap-2 mt-4">
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="bi bi-check-lg me-1"></i> Enregistrer
                    </button>
                    <a href="index.php?page=sponsors_admin" class="btn btn-outline-secondary">
                        Annuler
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../layout_footer.php'; ?>
