<?php
/**
 * Modification utilisateur (admin) — gabarit DocTime
 */
$pageTitle = 'Modifier un utilisateur';
$errors = $errors ?? [];
$extras = $extras ?? [];
$pv = array_merge($user ?? [], $_POST ?? []);

/** Valeur affichée : POST si présent, sinon extra métier, sinon colonne users. */
function ue_val(array $pv, array $extras, string $postKey, ?string $extraKey = null): string {
    if (array_key_exists($postKey, $_POST)) {
        return (string) $_POST[$postKey];
    }
    $ek = $extraKey ?? $postKey;
    if (isset($extras[$ek])) {
        return (string) $extras[$ek];
    }
    return (string) ($pv[$postKey] ?? '');
}

$currentRole = $pv['role'] ?? 'patient';
$currentStatut = $pv['statut'] ?? 'actif';

require __DIR__ . '/layout_header.php';
?>

<div class="bo-create-page">
    <div class="bo-create-header mb-4">
        <h1 class="bo-create-title">Modifier l’utilisateur</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="index.php?page=users">Utilisateurs</a></li>
                <li class="breadcrumb-item active" aria-current="page">#<?= (int) ($user['id'] ?? 0) ?></li>
            </ol>
        </nav>
    </div>

    <div class="card bo-create-card shadow-sm">
        <div class="card-header bo-create-card-head py-3">
            <h6 class="mb-0"><i class="bi bi-person-gear me-2"></i>Informations du compte</h6>
        </div>
        <div class="card-body">
            <form class="bo-create-form" method="POST"
                  action="index.php?page=users&action=edit&id=<?= (int) $user['id'] ?>" novalidate>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="ue_nom">Nom <span class="text-danger">*</span></label>
                        <input type="text" id="ue_nom" name="nom" class="form-control <?= isset($errors['nom']) ? 'is-invalid' : '' ?>"
                               value="<?= htmlspecialchars((string) ($pv['nom'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
                        <?php if (!empty($errors['nom'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['nom'], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="ue_prenom">Prénom <span class="text-danger">*</span></label>
                        <input type="text" id="ue_prenom" name="prenom" class="form-control <?= isset($errors['prenom']) ? 'is-invalid' : '' ?>"
                               value="<?= htmlspecialchars((string) ($pv['prenom'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
                        <?php if (!empty($errors['prenom'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['prenom'], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="ue_email">Email <span class="text-danger">*</span></label>
                        <input type="email" id="ue_email" name="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                               value="<?= htmlspecialchars((string) ($pv['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
                        <?php if (!empty($errors['email'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['email'], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="ue_tel">Téléphone</label>
                        <input type="tel" id="ue_tel" name="telephone" class="form-control <?= isset($errors['telephone']) ? 'is-invalid' : '' ?>"
                               value="<?= htmlspecialchars((string) ($pv['telephone'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        <?php if (!empty($errors['telephone'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['telephone'], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="ue_pass">Nouveau mot de passe</label>
                        <input type="password" id="ue_pass" name="password" autocomplete="new-password"
                               class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                               placeholder="Laisser vide pour ne pas changer" minlength="6">
                        <div class="form-text">Min. 6 caractères si renseigné.</div>
                        <?php if (!empty($errors['password'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['password'], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="ue_dn">Date de naissance</label>
                        <input type="date" id="ue_dn" name="date_naissance" class="form-control"
                               value="<?= htmlspecialchars((string) ($pv['date_naissance'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="ue_role">Rôle</label>
                        <select id="ue_role" name="role" class="form-select">
                            <?php foreach (['patient', 'medecin', 'admin'] as $r): ?>
                                <option value="<?= $r ?>" <?= $currentRole === $r ? 'selected' : '' ?>><?= ucfirst($r) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="ue_statut">Statut</label>
                        <select id="ue_statut" name="statut" class="form-select">
                            <?php foreach (['actif', 'inactif', 'en_attente'] as $s): ?>
                                <option value="<?= $s ?>" <?= $currentStatut === $s ? 'selected' : '' ?>><?= $s === 'en_attente' ? 'En attente' : ucfirst($s) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="ue_adr">Adresse</label>
                        <textarea id="ue_adr" name="adresse" class="form-control" rows="2"><?= htmlspecialchars((string) ($pv['adresse'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>
                </div>

                <div id="ue_patient_fields" class="mt-4 pt-3 border-top" style="display:<?= $currentRole === 'patient' ? 'block' : 'none' ?>;">
                    <h6 class="text-secondary mb-3"><i class="bi bi-heart-pulse me-2"></i>Patient</h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label" for="ue_gs">Groupe sanguin</label>
                            <?php
                            $gsPost = $_POST['groupe_sanguin'] ?? null;
                            $gs = $gsPost !== null ? (string) $gsPost : (string) ($extras['groupe_sanguin'] ?? '');
                            ?>
                            <select id="ue_gs" name="groupe_sanguin" class="form-select">
                                <option value="">—</option>
                                <?php foreach (['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $g): ?>
                                    <option value="<?= $g ?>" <?= $gs === $g ? 'selected' : '' ?>><?= $g ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div id="ue_medecin_fields" class="mt-4 pt-3 border-top" style="display:<?= $currentRole === 'medecin' ? 'block' : 'none' ?>;">
                    <h6 class="text-secondary mb-3"><i class="bi bi-hospital me-2"></i>Médecin</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="ue_spec">Spécialité</label>
                            <?php
                            $specialites = ['Généraliste','Cardiologue','Dermatologue','Gynécologue','Pédiatre','Ophtalmologue','Orthopédiste','Neurologue','Psychiatre','Dentiste','Autre'];
                            $spVal = ue_val($pv, $extras, 'specialite');
                            ?>
                            <select id="ue_spec" name="specialite" class="form-select">
                                <option value="">— Choisir —</option>
                                <?php foreach ($specialites as $s): ?>
                                    <option value="<?= htmlspecialchars($s, ENT_QUOTES, 'UTF-8') ?>" <?= $spVal === $s ? 'selected' : '' ?>><?= htmlspecialchars($s, ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                                <?php if ($spVal !== '' && !in_array($spVal, $specialites, true)): ?>
                                    <option value="<?= htmlspecialchars($spVal, ENT_QUOTES, 'UTF-8') ?>" selected><?= htmlspecialchars($spVal, ENT_QUOTES, 'UTF-8') ?> (actuel)</option>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="ue_ordre">N° d’ordre</label>
                            <input type="text" id="ue_ordre" name="numero_ordre" class="form-control"
                                   value="<?= htmlspecialchars(ue_val($pv, $extras, 'numero_ordre'), ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="ue_tarif">Tarif consultation (TND)</label>
                            <input type="number" id="ue_tarif" name="tarif" class="form-control" step="0.01" min="0"
                                   value="<?= htmlspecialchars(ue_val($pv, $extras, 'tarif', 'consultation_prix'), ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="ue_exp">Années d’expérience</label>
                            <input type="number" id="ue_exp" name="experience" class="form-control" min="0" step="1"
                                   value="<?= htmlspecialchars(ue_val($pv, $extras, 'experience', 'annee_experience'), ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="ue_cab">Adresse du cabinet</label>
                            <textarea id="ue_cab" name="adresse_cabinet" class="form-control" rows="2"><?= htmlspecialchars(ue_val($pv, $extras, 'adresse_cabinet', 'cabinet_adresse'), ENT_QUOTES, 'UTF-8') ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="bo-create-actions d-flex flex-wrap gap-2 mt-4">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Enregistrer</button>
                    <a href="index.php?page=users" class="btn btn-outline-secondary">Retour à la liste</a>
                    <?php if (isset($user['id']) && (int) $user['id'] !== (int) ($_SESSION['user_id'] ?? 0)): ?>
                        <a href="index.php?page=users&action=delete&id=<?= (int) $user['id'] ?>"
                           class="btn btn-outline-danger ms-auto js-confirm-delete" data-msg="Supprimer définitivement cet utilisateur ?">Supprimer</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function () {
    var sel = document.getElementById('ue_role');
    var pf = document.getElementById('ue_patient_fields');
    var mf = document.getElementById('ue_medecin_fields');
    function sync() {
        var role = sel.value;
        pf.style.display = role === 'patient' ? 'block' : 'none';
        mf.style.display = role === 'medecin' ? 'block' : 'none';
    }
    sel.addEventListener('change', sync);
})();
</script>

<?php require __DIR__ . '/layout_footer.php'; ?>
