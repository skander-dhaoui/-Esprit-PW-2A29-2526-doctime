<?php $pageTitle = 'Nouvelle participation'; ?>
<?php
$errors = $errors ?? [];
$old = $old ?? [];
$evenements = $evenements ?? [];
$statuts = $statuts ?? ['inscrit', 'présent', 'absent'];
$csrfToken = $csrfToken ?? '';
?>
<?php require __DIR__ . '/../layout_header.php'; ?>

<div class="bo-create-page">
    <?php if (!empty($_SESSION['flash'])): ?>
        <?php $f = $_SESSION['flash']; unset($_SESSION['flash']); ?>
        <div class="alert alert-<?= (($f['type'] ?? '') === 'danger' || ($f['type'] ?? '') === 'error') ? 'danger' : 'success' ?> alert-dismissible fade show mb-4">
            <?= htmlspecialchars($f['message'] ?? '') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
        </div>
    <?php endif; ?>

    <div class="bo-create-header mb-4">
        <h1 class="bo-create-title">Nouvelle participation</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="index.php?page=participations">Participations</a></li>
                <li class="breadcrumb-item active" aria-current="page">Nouvelle</li>
            </ol>
        </nav>
    </div>

    <div class="card bo-create-card shadow-sm">
        <div class="card-header bo-create-card-head py-3">
            <h6 class="mb-0"><i class="bi bi-person-plus me-2"></i>Informations du participant</h6>
        </div>
        <div class="card-body">
            <form class="bo-create-form"
                  action="index.php?page=participations&amp;action=create" method="POST"
                  id="form-participation" novalidate>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

                <div class="row g-3">

                    <div class="col-md-6">
                        <label class="form-label" for="nom">Nom <span class="text-danger">*</span></label>
                        <input type="text" id="nom" name="nom"
                               class="form-control <?= isset($errors['nom']) ? 'is-invalid' : '' ?>"
                               value="<?= htmlspecialchars($old['nom'] ?? '') ?>">
                        <?php if (isset($errors['nom'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['nom']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="prenom">Prénom <span class="text-danger">*</span></label>
                        <input type="text" id="prenom" name="prenom"
                               class="form-control <?= isset($errors['prenom']) ? 'is-invalid' : '' ?>"
                               value="<?= htmlspecialchars($old['prenom'] ?? '') ?>">
                        <?php if (isset($errors['prenom'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['prenom']) ?></div>
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
                               placeholder="Ex : 20 123 456">
                        <?php if (isset($errors['telephone'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['telephone']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="profession">Profession <span class="text-danger">*</span></label>
                        <input type="text" id="profession" name="profession"
                               class="form-control <?= isset($errors['profession']) ? 'is-invalid' : '' ?>"
                               value="<?= htmlspecialchars($old['profession'] ?? '') ?>"
                               placeholder="Ex : Médecin cardiologue">
                        <?php if (isset($errors['profession'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['profession']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="evenement_id">Événement <span class="text-danger">*</span></label>
                        <select id="evenement_id" name="evenement_id"
                                class="form-select <?= isset($errors['evenement_id']) ? 'is-invalid' : '' ?>">
                            <option value="">-- Choisir un événement --</option>
                            <?php foreach ($evenements as $ev): ?>
                                <option value="<?= (int)$ev['id'] ?>"
                                    <?= ((string)($old['evenement_id'] ?? '')) === (string)$ev['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($ev['titre']) ?>
                                    (<?= date('d/m/Y', strtotime($ev['date_debut'] ?? 'now')) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['evenement_id'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['evenement_id']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="statut">Statut <span class="text-danger">*</span></label>
                        <select id="statut" name="statut"
                                class="form-select <?= isset($errors['statut']) ? 'is-invalid' : '' ?>">
                            <?php
                            $statutLabels = [
                                'en_attente' => 'En attente',
                                'confirme' => 'Confirmé',
                                'annule' => 'Annulé',
                                'inscrit' => 'Inscrit',
                                'présent' => 'Présent',
                                'absent' => 'Absent',
                            ];
                            foreach ($statuts as $st): ?>
                                <option value="<?= htmlspecialchars($st) ?>" <?= ($old['statut'] ?? 'inscrit') === $st ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($statutLabels[$st] ?? $st) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['statut'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['statut']) ?></div>
                        <?php endif; ?>
                    </div>

                </div>

                <div class="bo-create-actions d-flex flex-wrap gap-2 mt-4">
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="bi bi-check-lg me-1"></i> Enregistrer
                    </button>
                    <a href="index.php?page=participations" class="btn btn-outline-secondary">
                        Annuler
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../layout_footer.php'; ?>
