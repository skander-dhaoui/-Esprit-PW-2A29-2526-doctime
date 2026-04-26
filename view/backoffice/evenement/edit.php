<?php $pageTitle = 'Modifier l\'Événement'; ?>
<?php require __DIR__ . '/../layout_header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0 fw-semibold">Modifier : <?= htmlspecialchars($old['titre'] ?? '') ?></h5>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item"><a href="index.php?controller=evenement&action=index">Événements</a></li>
                <li class="breadcrumb-item active">Modifier</li>
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
        <h6 class="mb-0"><i class="bi bi-calendar-event me-2"></i>Informations de l'événement</h6>
    </div>
    <div class="card-body">
        <form action="index.php?controller=evenement&action=update" method="POST"
              id="form-evenement" novalidate>
            <input type="hidden" name="id" value="<?= (int)($old['id'] ?? 0) ?>">

            <div class="row g-3">

                <div class="col-12">
                    <label class="form-label" for="titre">Titre <span class="text-danger">*</span></label>
                    <input type="text" id="titre" name="titre"
                           class="form-control <?= isset($errors['titre']) ? 'is-invalid' : '' ?>"
                           value="<?= htmlspecialchars($old['titre'] ?? '') ?>">
                    <?php if (isset($errors['titre'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errors['titre']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="col-12">
                    <label class="form-label" for="description">Description <span class="text-danger">*</span></label>
                    <textarea id="description" name="description" rows="4"
                              class="form-control <?= isset($errors['description']) ? 'is-invalid' : '' ?>"><?= htmlspecialchars($old['description'] ?? '') ?></textarea>
                    <?php if (isset($errors['description'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errors['description']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="specialite">Spécialité médicale <span class="text-danger">*</span></label>
                    <select id="specialite" name="specialite"
                            class="form-select <?= isset($errors['specialite']) ? 'is-invalid' : '' ?>">
                        <option value="">-- Choisir --</option>
                        <?php foreach ($specialites as $sp): ?>
                            <option value="<?= $sp ?>" <?= ($old['specialite'] ?? '') === $sp ? 'selected' : '' ?>><?= $sp ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['specialite'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errors['specialite']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="lieu">Lieu <span class="text-danger">*</span></label>
                    <input type="text" id="lieu" name="lieu"
                           class="form-control <?= isset($errors['lieu']) ? 'is-invalid' : '' ?>"
                           value="<?= htmlspecialchars($old['lieu'] ?? '') ?>">
                    <?php if (isset($errors['lieu'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errors['lieu']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="date_debut">Date de début <span class="text-danger">*</span></label>
                    <input type="text" id="date_debut" name="date_debut"
                           class="form-control <?= isset($errors['date_debut']) ? 'is-invalid' : '' ?>"
                           value="<?= htmlspecialchars($old['date_debut'] ?? '') ?>"
                           placeholder="AAAA-MM-JJ">
                    <?php if (isset($errors['date_debut'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errors['date_debut']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="date_fin">Date de fin <span class="text-danger">*</span></label>
                    <input type="text" id="date_fin" name="date_fin"
                           class="form-control <?= isset($errors['date_fin']) ? 'is-invalid' : '' ?>"
                           value="<?= htmlspecialchars($old['date_fin'] ?? '') ?>"
                           data-validate="required|date|dateafter:date_debut"
                           data-label="Date de fin" placeholder="AAAA-MM-JJ">
                    <?php if (isset($errors['date_fin'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errors['date_fin']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="col-md-4">
                    <label class="form-label" for="capacite">Capacité <span class="text-danger">*</span></label>
                    <input type="text" id="capacite" name="capacite"
                           class="form-control <?= isset($errors['capacite']) ? 'is-invalid' : '' ?>"
                           value="<?= htmlspecialchars($old['capacite'] ?? '') ?>">
                    <?php if (isset($errors['capacite'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errors['capacite']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="col-md-4">
                    <label class="form-label" for="prix">Prix (TND)</label>
                    <input type="text" id="prix" name="prix"
                           class="form-control <?= isset($errors['prix']) ? 'is-invalid' : '' ?>"
                           value="<?= htmlspecialchars($old['prix'] ?? '0') ?>"
                           data-validate="numeric" data-label="Prix">
                    <?php if (isset($errors['prix'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errors['prix']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="col-md-4">
                    <label class="form-label" for="statut">Statut <span class="text-danger">*</span></label>
                    <select id="statut" name="statut"
                            class="form-select <?= isset($errors['statut']) ? 'is-invalid' : '' ?>"
                            data-validate="required" data-label="Statut">
                        <?php $statutLabels = ['planifie'=>'Planifié','en_cours'=>'En cours','termine'=>'Terminé','annule'=>'Annulé']; ?>
                        <?php foreach ($statuts as $st): ?>
                            <option value="<?= $st ?>" <?= ($old['statut'] ?? '') === $st ? 'selected' : '' ?>>
                                <?= $statutLabels[$st] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['statut'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errors['statut']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="sponsor_ids">Sponsors (optionnel)</label>
                    <select id="sponsor_ids" name="sponsor_ids[]" class="form-select" multiple>
                        <?php foreach ($sponsors as $sp): ?>
                            <option value="<?= $sp['id'] ?>"
                                <?= in_array($sp['id'], $old['sponsor_ids'] ?? []) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($sp['nom']) ?> (<?= ucfirst($sp['niveau']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Maintenez Ctrl (ou Cmd) pour sélectionner plusieurs sponsors.</div>
                </div>

            </div>

            <hr class="my-4">
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary px-4">
                    <i class="bi bi-check-lg me-1"></i> Mettre à jour
                </button>
                <a href="index.php?controller=evenement&action=index" class="btn btn-outline-secondary">
                    Annuler
                </a>
            </div>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../layout_footer.php'; ?>
