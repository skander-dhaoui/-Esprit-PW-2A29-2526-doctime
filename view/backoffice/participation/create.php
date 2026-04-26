<?php $pageTitle = 'Nouvelle Participation'; ?>
<?php require __DIR__ . '/../layout_header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0 fw-semibold">Nouvelle Participation</h5>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item"><a href="index.php?controller=participation&action=index">Participations</a></li>
                <li class="breadcrumb-item active">Nouvelle</li>
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
        <h6 class="mb-0"><i class="bi bi-person-plus me-2"></i>Informations du participant</h6>
    </div>
    <div class="card-body">
        <form action="index.php?controller=participation&action=store" method="POST"
              id="form-participation" novalidate>
            <div class="row g-3">

                <div class="col-md-6">
                    <label class="form-label" for="nom">Nom <span class="text-danger">*</span></label>
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
                    <input type="text" id="email" name="email"
                           class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                           value="<?= htmlspecialchars($old['email'] ?? '') ?>">
                    <?php if (isset($errors['email'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errors['email']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="telephone">Téléphone <span class="text-danger">*</span></label>
                    <input type="text" id="telephone" name="telephone"
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
                            <option value="<?= $ev['id'] ?>"
                                <?= ($old['evenement_id'] ?? '') == $ev['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($ev['titre']) ?>
                                (<?= date('d/m/Y', strtotime($ev['date_debut'])) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['evenement_id'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errors['evenement_id']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="statut">Statut <span class="text-danger">*</span></label>
                    <select id="statut" name="statut"
                            class="form-select <?= isset($errors['statut']) ? 'is-invalid' : '' ?>">
                        <?php $statutLabels = ['en_attente'=>'En attente','confirme'=>'Confirmé','annule'=>'Annulé']; ?>
                        <?php foreach ($statuts as $st): ?>
                            <option value="<?= $st ?>" <?= ($old['statut'] ?? 'en_attente') === $st ? 'selected' : '' ?>>
                                <?= $statutLabels[$st] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['statut'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errors['statut']) ?></div>
                    <?php endif; ?>
                </div>

            </div>

            <hr class="my-4">
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary px-4">
                    <i class="bi bi-check-lg me-1"></i> Enregistrer
                </button>
                <a href="index.php?controller=participation&action=index" class="btn btn-outline-secondary">
                    Annuler
                </a>
            </div>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../layout_footer.php'; ?>
