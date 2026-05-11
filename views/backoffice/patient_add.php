<?php
$pageTitle = 'Nouveau patient';
$errors = $errors ?? [];
require __DIR__ . '/layout_header.php';
?>

<div class="bo-create-page">
    <?php if (!empty($_SESSION['flash'])): ?>
        <?php $f = $_SESSION['flash']; unset($_SESSION['flash']); ?>
        <div class="alert alert-<?= (($f['type'] ?? '') === 'error' || ($f['type'] ?? '') === 'danger') ? 'danger' : 'success' ?> alert-dismissible fade show mb-4">
            <?= htmlspecialchars($f['message'] ?? '', ENT_QUOTES, 'UTF-8') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
        </div>
    <?php endif; ?>

    <div class="bo-create-header mb-4">
        <h1 class="bo-create-title">Nouveau patient</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="index.php?page=patients">Patients</a></li>
                <li class="breadcrumb-item active" aria-current="page">Ajouter</li>
            </ol>
        </nav>
    </div>

    <div class="card bo-create-card shadow-sm">
        <div class="card-header bo-create-card-head py-3">
            <h6 class="mb-0"><i class="bi bi-person-heart me-2"></i>Fiche patient</h6>
        </div>
        <div class="card-body">
            <?php if (!empty($errors['enregistrement'])): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($errors['enregistrement'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <form class="bo-create-form" method="POST" action="index.php?page=patients&action=add" novalidate>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="pa_nom">Nom <span class="text-danger">*</span></label>
                        <input type="text" id="pa_nom" name="nom" class="form-control <?= isset($errors['nom']) ? 'is-invalid' : '' ?>"
                               value="<?= htmlspecialchars($_POST['nom'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                        <?php if (!empty($errors['nom'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['nom'], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="pa_prenom">Prénom <span class="text-danger">*</span></label>
                        <input type="text" id="pa_prenom" name="prenom" class="form-control <?= isset($errors['prenom']) ? 'is-invalid' : '' ?>"
                               value="<?= htmlspecialchars($_POST['prenom'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                        <?php if (!empty($errors['prenom'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['prenom'], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="pa_email">Email <span class="text-danger">*</span></label>
                        <input type="email" id="pa_email" name="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                               value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                        <?php if (!empty($errors['email'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['email'], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="pa_tel">Téléphone</label>
                        <input type="tel" id="pa_tel" name="telephone" class="form-control <?= isset($errors['telephone']) ? 'is-invalid' : '' ?>"
                               value="<?= htmlspecialchars($_POST['telephone'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Ex. 71 234 567">
                        <?php if (!empty($errors['telephone'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['telephone'], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="pa_pass">Mot de passe <span class="text-danger">*</span></label>
                        <input type="password" id="pa_pass" name="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                               autocomplete="new-password" minlength="6" required>
                        <div class="form-text">Au moins 6 caractères.</div>
                        <?php if (!empty($errors['password'])): ?>
                            <div class="invalid-feedback d-block"><?= htmlspecialchars($errors['password'], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="pa_gs">Groupe sanguin</label>
                        <?php $gs = $_POST['groupe_sanguin'] ?? ''; ?>
                        <select id="pa_gs" name="groupe_sanguin" class="form-select <?= isset($errors['groupe_sanguin']) ? 'is-invalid' : '' ?>">
                            <option value="">Non renseigné</option>
                            <?php foreach (['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $g): ?>
                                <option value="<?= $g ?>" <?= $gs === $g ? 'selected' : '' ?>><?= $g ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (!empty($errors['groupe_sanguin'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['groupe_sanguin'], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="pa_adr">Adresse</label>
                        <textarea id="pa_adr" name="adresse" class="form-control <?= isset($errors['adresse']) ? 'is-invalid' : '' ?>" rows="2"><?= htmlspecialchars($_POST['adresse'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                        <?php if (!empty($errors['adresse'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['adresse'], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="bo-create-actions d-flex flex-wrap gap-2 mt-4">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Enregistrer</button>
                    <a href="index.php?page=patients" class="btn btn-outline-secondary">Annuler</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require __DIR__ . '/layout_footer.php'; ?>
