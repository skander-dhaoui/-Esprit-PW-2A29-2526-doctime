<?php
$pageTitle = 'Nouveau médecin';
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
        <h1 class="bo-create-title">Nouveau médecin</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="index.php?page=medecins_admin">Médecins</a></li>
                <li class="breadcrumb-item active" aria-current="page">Ajouter</li>
            </ol>
        </nav>
    </div>

    <div class="card bo-create-card shadow-sm">
        <div class="card-header bo-create-card-head py-3">
            <h6 class="mb-0"><i class="bi bi-person-badge me-2"></i>Identité et cabinet</h6>
        </div>
        <div class="card-body">
            <form class="bo-create-form" method="POST" action="index.php?page=medecins_admin&action=add" novalidate>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="ma_nom">Nom <span class="text-danger">*</span></label>
                        <input type="text" id="ma_nom" name="nom" class="form-control" required
                               value="<?= htmlspecialchars($_POST['nom'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="ma_prenom">Prénom <span class="text-danger">*</span></label>
                        <input type="text" id="ma_prenom" name="prenom" class="form-control" required
                               value="<?= htmlspecialchars($_POST['prenom'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="ma_email">Email <span class="text-danger">*</span></label>
                        <input type="email" id="ma_email" name="email" class="form-control" required autocomplete="email"
                               value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="ma_tel">Téléphone</label>
                        <input type="tel" id="ma_tel" name="telephone" class="form-control" inputmode="tel"
                               value="<?= htmlspecialchars($_POST['telephone'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Ex. 71 234 567">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="ma_pass">Mot de passe <span class="text-danger">*</span></label>
                        <input type="password" id="ma_pass" name="password" class="form-control" autocomplete="new-password" required minlength="6">
                        <div class="form-text">Au moins 6 caractères.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="ma_spec">Spécialité <span class="text-danger">*</span></label>
                        <?php
                        $specialites = ['Généraliste','Cardiologue','Dermatologue','Gynécologue','Pédiatre','Ophtalmologue','Orthopédiste','Neurologue','Psychiatre','Dentiste','Autre'];
                        $sp = $_POST['specialite'] ?? '';
                        ?>
                        <select id="ma_spec" name="specialite" class="form-select" required>
                            <option value="">— Choisir —</option>
                            <?php foreach ($specialites as $s): ?>
                                <option value="<?= htmlspecialchars($s, ENT_QUOTES, 'UTF-8') ?>" <?= $sp === $s ? 'selected' : '' ?>><?= htmlspecialchars($s, ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="ma_ordre">N° d’ordre</label>
                        <input type="text" id="ma_ordre" name="numero_ordre" class="form-control"
                               value="<?= htmlspecialchars($_POST['numero_ordre'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                               placeholder="Unique si renseigné">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="ma_prix">Tarif consultation (TND)</label>
                        <input type="number" id="ma_prix" name="consultation_prix" class="form-control" step="0.01" min="0"
                               value="<?= htmlspecialchars($_POST['consultation_prix'] ?? '50', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="ma_exp">Années d’expérience</label>
                        <input type="number" id="ma_exp" name="annee_experience" class="form-control" min="0" step="1"
                               value="<?= htmlspecialchars($_POST['annee_experience'] ?? '0', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="ma_cab">Adresse du cabinet</label>
                        <textarea id="ma_cab" name="cabinet_adresse" class="form-control" rows="3"><?= htmlspecialchars($_POST['cabinet_adresse'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>
                </div>

                <div class="bo-create-actions d-flex flex-wrap gap-2 mt-4">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Créer le médecin</button>
                    <a href="index.php?page=medecins_admin" class="btn btn-outline-secondary">Annuler</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require __DIR__ . '/layout_footer.php'; ?>
