<?php $pageTitle = 'Nouvel événement'; ?>
<?php
$errors = $errors ?? [];
$old = $old ?? [];
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

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger bo-alert-errors mb-4" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <strong>Veuillez corriger les erreurs suivantes :</strong>
            <ul class="mt-2 mb-0">
                <?php foreach ($errors as $msg): ?>
                    <li><?= htmlspecialchars(is_string($msg) ? $msg : '') ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="bo-create-header mb-4">
        <h1 class="bo-create-title">Nouvel événement</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="index.php?page=evenements_admin">Événements</a></li>
                <li class="breadcrumb-item active" aria-current="page">Nouveau</li>
            </ol>
        </nav>
    </div>

    <div class="card bo-create-card shadow-sm">
        <div class="card-header bo-create-card-head py-3">
            <h6 class="mb-0"><i class="bi bi-calendar-event me-2"></i>Informations de l'événement</h6>
        </div>
        <div class="card-body">
            <form class="bo-create-form"
                  action="index.php?page=evenements_admin&amp;action=create" method="POST"
                  id="form-evenement" novalidate>
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
                        <textarea id="description" name="description" rows="5"
                                  class="form-control <?= isset($errors['description']) ? 'is-invalid' : '' ?>"
                                  placeholder="Décrivez l'événement…"><?= htmlspecialchars($old['description'] ?? '') ?></textarea>
                        <?php if (isset($errors['description'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['description']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="specialite">Spécialité médicale <span class="text-danger">*</span></label>
                        <select id="specialite" name="specialite"
                                class="form-select <?= isset($errors['specialite']) ? 'is-invalid' : '' ?>">
                            <option value="">-- Choisir une spécialité --</option>
                            <?php foreach ($specialites as $sp): ?>
                                <option value="<?= htmlspecialchars($sp) ?>" <?= ($old['specialite'] ?? '') === $sp ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($sp) ?>
                                </option>
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
                        <input type="date" id="date_debut" name="date_debut"
                               class="form-control <?= isset($errors['date_debut']) ? 'is-invalid' : '' ?>"
                               value="<?= htmlspecialchars(substr((string)($old['date_debut'] ?? ''), 0, 10)) ?>">
                        <div class="form-text">La date de début ne peut pas être antérieure à aujourd’hui.</div>
                        <?php if (isset($errors['date_debut'])): ?>
                            <div class="invalid-feedback d-block"><?= htmlspecialchars($errors['date_debut']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="date_fin">Date de fin <span class="text-danger">*</span></label>
                        <input type="date" id="date_fin" name="date_fin"
                               class="form-control <?= isset($errors['date_fin']) ? 'is-invalid' : '' ?>"
                               value="<?= htmlspecialchars(substr((string)($old['date_fin'] ?? ''), 0, 10)) ?>">
                        <div class="form-text">La date de fin doit être postérieure à la date de début.</div>
                        <?php if (isset($errors['date_fin'])): ?>
                            <div class="invalid-feedback d-block"><?= htmlspecialchars($errors['date_fin']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="capacite">Capacité <span class="text-danger">*</span></label>
                        <input type="number" id="capacite" name="capacite" min="1" step="1"
                               class="form-control <?= isset($errors['capacite']) ? 'is-invalid' : '' ?>"
                               value="<?= htmlspecialchars((string)($old['capacite'] ?? '')) ?>">
                        <?php if (isset($errors['capacite'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['capacite']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="prix">Prix (TND)</label>
                        <input type="text" id="prix" name="prix" inputmode="decimal"
                               class="form-control <?= isset($errors['prix']) ? 'is-invalid' : '' ?>"
                               value="<?= htmlspecialchars((string)($old['prix'] ?? '0')) ?>">
                        <div class="form-text">Laisser 0 pour un événement gratuit.</div>
                        <?php if (isset($errors['prix'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['prix']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="event_image">Image</label>
                        <input type="file" id="event_image" class="form-control" accept="image/jpeg,image/png,image/gif,image/webp" disabled aria-describedby="event-image-help">
                        <div id="event-image-help" class="form-text">Formats acceptés : JPEG, PNG, GIF, WebP. Max 2&nbsp;Mo — envoi fichier prévu dans une prochaine version.</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="statut">Statut <span class="text-danger">*</span></label>
                        <select id="statut" name="statut"
                                class="form-select <?= isset($errors['statut']) ? 'is-invalid' : '' ?>">
                            <option value="">-- Choisir --</option>
                            <?php
                            $statutLabels = [
                                'planifie' => 'Planifié',
                                'en_cours' => 'En cours',
                                'termine' => 'Terminé',
                                'annule' => 'Annulé',
                            ];
                            foreach ($statuts as $st): ?>
                                <option value="<?= htmlspecialchars($st) ?>" <?= ($old['statut'] ?? 'planifie') === $st ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($statutLabels[$st] ?? $st) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['statut'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['statut']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="sponsor_id">Sponsor (optionnel)</label>
                        <select id="sponsor_id" name="sponsor_id" class="form-select">
                            <option value="">-- Aucun sponsor --</option>
                            <?php foreach ($sponsors as $sp): ?>
                                <option value="<?= (int)$sp['id'] ?>"
                                    <?= ((string)($old['sponsor_id'] ?? '')) === (string)$sp['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($sp['nom']) ?> (<?= htmlspecialchars(ucfirst((string)($sp['niveau'] ?? ''))) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                </div>

                <div class="bo-create-actions d-flex flex-wrap gap-2 mt-4">
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="bi bi-check-lg me-1"></i> Enregistrer
                    </button>
                    <a href="index.php?page=evenements_admin" class="btn btn-outline-secondary">
                        Annuler
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../layout_footer.php'; ?>
