<?php
$pageTitle = 'Modifier un patient';
$errors = $errors ?? [];
$p = isset($patient) && is_array($patient)
    ? (empty($_POST) ? $patient : array_merge($patient, $_POST))
    : [];
require __DIR__ . '/layout_header.php';
?>

<div class="bo-create-page">
    <div class="bo-create-header mb-4">
        <h1 class="bo-create-title">Modifier le patient</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="index.php?page=patients">Patients</a></li>
                <li class="breadcrumb-item active" aria-current="page">#<?= (int) ($p['id'] ?? 0) ?></li>
            </ol>
        </nav>
    </div>

    <div class="card bo-create-card shadow-sm">
        <div class="card-header bo-create-card-head py-3">
            <h6 class="mb-0"><i class="bi bi-person-lines-fill me-2"></i>Informations</h6>
        </div>
        <div class="card-body">
            <form class="bo-create-form" method="POST" action="index.php?page=patients&action=edit&id=<?= (int) ($p['id'] ?? 0) ?>" novalidate>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="pe_nom">Nom <span class="text-danger">*</span></label>
                        <input type="text" id="pe_nom" name="nom" class="form-control <?= isset($errors['nom']) ? 'is-invalid' : '' ?>"
                               value="<?= htmlspecialchars((string) ($p['nom'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
                        <?php if (!empty($errors['nom'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['nom'], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="pe_prenom">Prénom <span class="text-danger">*</span></label>
                        <input type="text" id="pe_prenom" name="prenom" class="form-control <?= isset($errors['prenom']) ? 'is-invalid' : '' ?>"
                               value="<?= htmlspecialchars((string) ($p['prenom'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
                        <?php if (!empty($errors['prenom'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['prenom'], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="pe_email">Email <span class="text-danger">*</span></label>
                        <input type="email" id="pe_email" name="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                               value="<?= htmlspecialchars((string) ($p['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
                        <?php if (!empty($errors['email'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['email'], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="pe_tel">Téléphone</label>
                        <input type="tel" id="pe_tel" name="telephone" class="form-control <?= isset($errors['telephone']) ? 'is-invalid' : '' ?>"
                               value="<?= htmlspecialchars((string) ($p['telephone'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        <?php if (!empty($errors['telephone'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['telephone'], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="pe_pass">Nouveau mot de passe</label>
                        <input type="password" id="pe_pass" name="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                               autocomplete="new-password" minlength="6" placeholder="Laisser vide pour ne pas changer">
                        <div class="form-text">Min. 6 caractères si renseigné.</div>
                        <?php if (!empty($errors['password'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['password'], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="pe_gs">Groupe sanguin</label>
                        <?php $gpb = (string) ($p['groupe_sanguin'] ?? ''); ?>
                        <select id="pe_gs" name="groupe_sanguin" class="form-select <?= isset($errors['groupe_sanguin']) ? 'is-invalid' : '' ?>">
                            <option value="">Non renseigné</option>
                            <?php foreach (['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $g): ?>
                                <option value="<?= $g ?>" <?= $gpb === $g ? 'selected' : '' ?>><?= $g ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (!empty($errors['groupe_sanguin'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['groupe_sanguin'], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="pe_statut">Statut</label>
                        <?php $st = (string) ($p['statut'] ?? 'actif'); ?>
                        <select id="pe_statut" name="statut" class="form-select <?= isset($errors['statut']) ? 'is-invalid' : '' ?>">
                            <option value="actif" <?= $st === 'actif' ? 'selected' : '' ?>>Actif</option>
                            <option value="inactif" <?= $st === 'inactif' ? 'selected' : '' ?>>Inactif</option>
                            <option value="en_attente" <?= $st === 'en_attente' ? 'selected' : '' ?>>En attente</option>
                        </select>
                        <?php if (!empty($errors['statut'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['statut'], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="pe_adr">Adresse</label>
                        <textarea id="pe_adr" name="adresse" class="form-control <?= isset($errors['adresse']) ? 'is-invalid' : '' ?>" rows="2"><?= htmlspecialchars((string) ($p['adresse'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                        <?php if (!empty($errors['adresse'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['adresse'], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="bo-create-actions d-flex flex-wrap gap-2 mt-4">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Enregistrer</button>
                    <a href="index.php?page=patients" class="btn btn-outline-secondary">Retour à la liste</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require __DIR__ . '/layout_footer.php'; ?>
