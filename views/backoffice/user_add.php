<?php
/**
 * Création utilisateur (admin) — gabarit DocTime
 */
$pageTitle = 'Nouvel utilisateur';
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
        <h1 class="bo-create-title">Nouvel utilisateur</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="index.php?page=users">Utilisateurs</a></li>
                <li class="breadcrumb-item active" aria-current="page">Créer</li>
            </ol>
        </nav>
    </div>

    <div class="card bo-create-card shadow-sm">
        <div class="card-header bo-create-card-head py-3">
            <h6 class="mb-0"><i class="bi bi-person-plus me-2"></i>Compte et profil</h6>
        </div>
        <div class="card-body">
            <form class="bo-create-form" method="POST" action="index.php?page=users&action=create" novalidate>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="ua_nom">Nom <span class="text-danger">*</span></label>
                        <input type="text" id="ua_nom" name="nom" autocomplete="family-name"
                               class="form-control <?= isset($errors['nom']) ? 'is-invalid' : '' ?>"
                               value="<?= htmlspecialchars($_POST['nom'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                        <?php if (!empty($errors['nom'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['nom'], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="ua_prenom">Prénom <span class="text-danger">*</span></label>
                        <input type="text" id="ua_prenom" name="prenom" autocomplete="given-name"
                               class="form-control <?= isset($errors['prenom']) ? 'is-invalid' : '' ?>"
                               value="<?= htmlspecialchars($_POST['prenom'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                        <?php if (!empty($errors['prenom'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['prenom'], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="ua_email">Email <span class="text-danger">*</span></label>
                        <input type="email" id="ua_email" name="email" autocomplete="email"
                               class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                               value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                        <?php if (!empty($errors['email'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['email'], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="ua_tel">Téléphone</label>
                        <input type="tel" id="ua_tel" name="telephone" inputmode="tel" autocomplete="tel"
                               class="form-control <?= isset($errors['telephone']) ? 'is-invalid' : '' ?>"
                               value="<?= htmlspecialchars($_POST['telephone'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                               placeholder="Ex. 71 234 567">
                        <?php if (!empty($errors['telephone'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['telephone'], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="ua_pass">Mot de passe <span class="text-danger">*</span></label>
                        <input type="password" id="ua_pass" name="password" autocomplete="new-password"
                               class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                               minlength="6" required>
                        <div class="form-text">Au moins 6 caractères.</div>
                        <?php if (!empty($errors['password'])): ?>
                            <div class="invalid-feedback d-block"><?= htmlspecialchars($errors['password'], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="ua_role">Rôle <span class="text-danger">*</span></label>
                        <select id="ua_role" name="role" class="form-select <?= isset($errors['role']) ? 'is-invalid' : '' ?>" required>
                            <?php $r = $_POST['role'] ?? 'patient'; ?>
                            <option value="patient" <?= $r === 'patient' ? 'selected' : '' ?>>Patient</option>
                            <option value="medecin" <?= $r === 'medecin' ? 'selected' : '' ?>>Médecin</option>
                            <option value="admin" <?= $r === 'admin' ? 'selected' : '' ?>>Administrateur</option>
                        </select>
                        <?php if (!empty($errors['role'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['role'], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="ua_dn">Date de naissance</label>
                        <input type="date" id="ua_dn" name="date_naissance" class="form-control"
                               value="<?= htmlspecialchars($_POST['date_naissance'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="ua_statut">Statut</label>
                        <?php $st = $_POST['statut'] ?? 'actif'; ?>
                        <select id="ua_statut" name="statut" class="form-select">
                            <option value="actif" <?= $st === 'actif' ? 'selected' : '' ?>>Actif</option>
                            <option value="inactif" <?= $st === 'inactif' ? 'selected' : '' ?>>Inactif</option>
                            <option value="en_attente" <?= $st === 'en_attente' ? 'selected' : '' ?>>En attente</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="ua_adr">Adresse</label>
                        <textarea id="ua_adr" name="adresse" class="form-control" rows="2"><?= htmlspecialchars($_POST['adresse'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>
                </div>

                <div id="ua_patient_block" class="mt-4 pt-3 border-top" style="display:none;">
                    <h6 class="text-secondary mb-3"><i class="bi bi-heart-pulse me-2"></i>Informations patient</h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label" for="ua_gs">Groupe sanguin</label>
                            <?php $gs = $_POST['groupe_sanguin'] ?? ''; ?>
                            <select id="ua_gs" name="groupe_sanguin" class="form-select">
                                <option value="">— Non renseigné —</option>
                                <?php foreach (['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $g): ?>
                                    <option value="<?= $g ?>" <?= $gs === $g ? 'selected' : '' ?>><?= $g ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div id="ua_medecin_block" class="mt-4 pt-3 border-top" style="display:none;">
                    <h6 class="text-secondary mb-3"><i class="bi bi-hospital me-2"></i>Informations médecin</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="ua_spec">Spécialité</label>
                            <?php
                            $specialites = ['Généraliste','Cardiologue','Dermatologue','Gynécologue','Pédiatre','Ophtalmologue','Orthopédiste','Neurologue','Psychiatre','Dentiste','Autre'];
                            $spSel = $_POST['specialite'] ?? '';
                            ?>
                            <select id="ua_spec" name="specialite" class="form-select">
                                <option value="">— Choisir —</option>
                                <?php foreach ($specialites as $s): ?>
                                    <option value="<?= htmlspecialchars($s, ENT_QUOTES, 'UTF-8') ?>" <?= $spSel === $s ? 'selected' : '' ?>><?= htmlspecialchars($s, ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="ua_ordre">N° d’ordre</label>
                            <input type="text" id="ua_ordre" name="numero_ordre" class="form-control"
                                   value="<?= htmlspecialchars($_POST['numero_ordre'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="ua_tarif">Tarif consultation (TND)</label>
                            <input type="number" id="ua_tarif" name="tarif" class="form-control" step="0.01" min="0"
                                   value="<?= htmlspecialchars($_POST['tarif'] ?? '50', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="ua_exp">Années d’expérience</label>
                            <input type="number" id="ua_exp" name="experience" class="form-control" min="0" step="1"
                                   value="<?= htmlspecialchars($_POST['experience'] ?? '0', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="ua_cab">Adresse du cabinet</label>
                            <textarea id="ua_cab" name="adresse_cabinet" class="form-control" rows="2"><?= htmlspecialchars($_POST['adresse_cabinet'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="bo-create-actions d-flex flex-wrap gap-2 mt-4">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Créer l’utilisateur</button>
                    <a href="index.php?page=users" class="btn btn-outline-secondary">Annuler</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function () {
    var role = document.getElementById('ua_role');
    var pb = document.getElementById('ua_patient_block');
    var mb = document.getElementById('ua_medecin_block');
    function sync() {
        var v = role.value;
        pb.style.display = v === 'patient' ? 'block' : 'none';
        mb.style.display = v === 'medecin' ? 'block' : 'none';
    }
    role.addEventListener('change', sync);
    sync();
})();
</script>

<?php require __DIR__ . '/layout_footer.php'; ?>
