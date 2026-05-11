<?php
$pageTitle = 'Modifier un médecin';
$errors = $errors ?? [];

if (!isset($medecin) || !is_array($medecin)) {
    $pageTitle = 'Médecin';
    require __DIR__ . '/layout_header.php';
    echo '<div class="alert alert-danger">Données médecin indisponibles.</div>';
    require __DIR__ . '/layout_footer.php';
    return;
}

$m = empty($_POST) ? $medecin : array_merge($medecin, $_POST);

require __DIR__ . '/layout_header.php';
?>

<div class="bo-create-page">
    <div class="bo-create-header mb-4">
        <h1 class="bo-create-title">Modifier le médecin</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="index.php?page=medecins_admin">Médecins</a></li>
                <li class="breadcrumb-item active" aria-current="page">#<?= (int) ($m['id'] ?? 0) ?></li>
            </ol>
        </nav>
    </div>

    <div class="card bo-create-card shadow-sm">
        <div class="card-header bo-create-card-head py-3">
            <h6 class="mb-0"><i class="bi bi-person-badge me-2"></i>Fiche médecin</h6>
        </div>
        <div class="card-body">
            <form class="bo-create-form" method="POST" action="index.php?page=medecins_admin&action=edit&id=<?= (int) ($m['id'] ?? 0) ?>" novalidate>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="me_nom">Nom <span class="text-danger">*</span></label>
                        <input type="text" id="me_nom" name="nom" class="form-control <?= isset($errors['nom']) ? 'is-invalid' : '' ?>"
                               value="<?= htmlspecialchars((string) ($m['nom'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
                        <?php if (!empty($errors['nom'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['nom'], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="me_prenom">Prénom <span class="text-danger">*</span></label>
                        <input type="text" id="me_prenom" name="prenom" class="form-control <?= isset($errors['prenom']) ? 'is-invalid' : '' ?>"
                               value="<?= htmlspecialchars((string) ($m['prenom'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
                        <?php if (!empty($errors['prenom'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['prenom'], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="me_email">Email <span class="text-danger">*</span></label>
                        <input type="email" id="me_email" name="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                               value="<?= htmlspecialchars((string) ($m['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
                        <?php if (!empty($errors['email'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['email'], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="me_tel">Téléphone</label>
                        <input type="tel" id="me_tel" name="telephone" class="form-control <?= isset($errors['telephone']) ? 'is-invalid' : '' ?>"
                               value="<?= htmlspecialchars((string) ($m['telephone'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        <?php if (!empty($errors['telephone'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['telephone'], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="me_pass">Nouveau mot de passe</label>
                        <input type="password" id="me_pass" name="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                               autocomplete="new-password" minlength="6" placeholder="Laisser vide pour ne pas changer">
                        <div class="form-text">Min. 6 caractères si renseigné.</div>
                        <?php if (!empty($errors['password'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['password'], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="me_spec">Spécialité <span class="text-danger">*</span></label>
                        <?php
                        $specialites = ['Généraliste','Cardiologue','Dermatologue','Gynécologue','Pédiatre','Ophtalmologue','Orthopédiste','Neurologue','Psychiatre','Dentiste','Autre'];
                        $spCur = (string) ($m['specialite'] ?? '');
                        ?>
                        <select id="me_spec" name="specialite" class="form-select <?= isset($errors['specialite']) ? 'is-invalid' : '' ?>" required>
                            <option value="">— Choisir —</option>
                            <?php foreach ($specialites as $s): ?>
                                <option value="<?= htmlspecialchars($s, ENT_QUOTES, 'UTF-8') ?>" <?= $spCur === $s ? 'selected' : '' ?>><?= htmlspecialchars($s, ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                            <?php if ($spCur !== '' && !in_array($spCur, $specialites, true)): ?>
                                <option value="<?= htmlspecialchars($spCur, ENT_QUOTES, 'UTF-8') ?>" selected><?= htmlspecialchars($spCur, ENT_QUOTES, 'UTF-8') ?> (actuel)</option>
                            <?php endif; ?>
                        </select>
                        <?php if (!empty($errors['specialite'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['specialite'], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="me_ordre">N° d’ordre</label>
                        <input type="text" id="me_ordre" name="numero_ordre" class="form-control"
                               value="<?= htmlspecialchars((string) ($m['numero_ordre'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="me_prix">Tarif consultation (TND)</label>
                        <input type="number" id="me_prix" name="consultation_prix" class="form-control" step="0.01" min="0"
                               value="<?= htmlspecialchars((string) ($m['consultation_prix'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="me_exp">Années d’expérience</label>
                        <input type="number" id="me_exp" name="annee_experience" class="form-control" min="0" step="1"
                               value="<?= htmlspecialchars((string) ($m['annee_experience'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="me_statut">Statut</label>
                        <?php $st = (string) ($m['statut'] ?? 'actif'); ?>
                        <select id="me_statut" name="statut" class="form-select">
                            <option value="actif" <?= $st === 'actif' ? 'selected' : '' ?>>Actif</option>
                            <option value="inactif" <?= $st === 'inactif' ? 'selected' : '' ?>>Inactif</option>
                            <option value="en_attente" <?= $st === 'en_attente' ? 'selected' : '' ?>>En attente</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="me_cab">Adresse du cabinet</label>
                        <textarea id="me_cab" name="cabinet_adresse" class="form-control" rows="3"><?= htmlspecialchars((string) ($m['cabinet_adresse'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>
                </div>

                <div class="bo-create-actions d-flex flex-wrap gap-2 mt-4">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Enregistrer</button>
                    <a href="index.php?page=medecins_admin" class="btn btn-outline-secondary">Retour à la liste</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require __DIR__ . '/layout_footer.php'; ?>
