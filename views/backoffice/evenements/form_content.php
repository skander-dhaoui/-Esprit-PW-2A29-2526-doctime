<?php
// Vue de contenu pour ajouter/éditer un événement
// Variables disponibles: $csrfToken, $categories (array), $old (array), $flash (array), $isEdit (bool), $event (array), $errors (array)
$isEdit = isset($event) && !empty($event);
$errors = $errors ?? [];
?>

<div class="event-form-container">
    <!-- Page Header -->
    <div class="event-form-header">
        <h2>
            <i class="fas fa-calendar-alt me-2"></i>
            <?php echo $isEdit ? 'Modifier l\'événement' : 'Ajouter un événement'; ?>
        </h2>
        <p><?php echo $isEdit ? 'Modifiez les informations de l\'événement' : 'Remplissez le formulaire pour créer un nouvel événement'; ?></p>
    </div>

                <?php if (!empty($flash)): ?>
                    <div class="event-alert event-alert-<?php echo $flash['type'] === 'success' ? 'success' : 'danger'; ?>">
                        <i class="fas fa-<?php echo $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle'; ?> me-2"></i>
                        <?php echo htmlspecialchars($flash['message']); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?php echo $isEdit ? 'index.php?page=evenements_admin&action=edit&id=' . ($event['id'] ?? '') : 'index.php?page=evenements_admin&action=create'; ?>" enctype="multipart/form-data" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

                    <div class="row">
                        <!-- Informations générales -->
                        <div class="col-lg-8">
                            <div class="event-section">
                                <h6 class="event-section-title">
                                    <i class="fas fa-info-circle me-2"></i>Informations générales
                                </h6>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="event-form-label">Titre <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control event-form-input <?php echo !empty($errors['titre']) ? 'is-invalid' : ''; ?>" id="titre" name="titre"
                                               value="<?php echo htmlspecialchars($old['titre'] ?? ($event['titre'] ?? '')); ?>"
                                               placeholder="Entrez le titre de l'événement">
                                        <?php if (!empty($errors['titre'])): ?>
                                            <div class="text-danger small mt-1"><i class="fas fa-exclamation-circle me-1"></i><?php echo htmlspecialchars($errors['titre']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="event-form-label">Type d'événement</label>
                                        <select class="form-select event-form-input <?php echo !empty($errors['type']) ? 'is-invalid' : ''; ?>" id="type" name="type">
                                            <option value="">Sélectionner...</option>
                                            <option value="webinaire" <?php echo ($old['type'] ?? ($event['type'] ?? '')) === 'webinaire' ? 'selected' : ''; ?>>Webinaire</option>
                                            <option value="atelier" <?php echo ($old['type'] ?? ($event['type'] ?? '')) === 'atelier' ? 'selected' : ''; ?>>Atelier</option>
                                            <option value="sensibilisation" <?php echo ($old['type'] ?? ($event['type'] ?? '')) === 'sensibilisation' ? 'selected' : ''; ?>>Sensibilisation</option>
                                            <option value="conference" <?php echo ($old['type'] ?? ($event['type'] ?? '')) === 'conference' ? 'selected' : ''; ?>>Conférence</option>
                                        </select>
                                        <?php if (!empty($errors['type'])): ?>
                                            <div class="text-danger small mt-1"><i class="fas fa-exclamation-circle me-1"></i><?php echo htmlspecialchars($errors['type']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="event-form-label">Description <span class="text-danger">*</span></label>
                                    <textarea class="form-control event-form-input <?php echo !empty($errors['description']) ? 'is-invalid' : ''; ?>" id="description" name="description" rows="5"
                                              placeholder="Décrivez l'événement en détail..."><?php echo htmlspecialchars($old['description'] ?? ($event['description'] ?? '')); ?></textarea>
                                    <?php if (!empty($errors['description'])): ?>
                                        <div class="text-danger small mt-1"><i class="fas fa-exclamation-circle me-1"></i><?php echo htmlspecialchars($errors['description']); ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Date et heure -->
                            <div class="event-section">
                                <h6 class="event-section-title">
                                    <i class="fas fa-clock me-2"></i>Date et heure
                                </h6>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="event-form-label">Date de début <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control event-form-input <?php echo !empty($errors['date_debut']) ? 'is-invalid' : ''; ?>" id="date_debut" name="date_debut"
                                               value="<?php echo htmlspecialchars($old['date_debut'] ?? ($event['date_debut'] ?? '')); ?>">
                                        <?php if (!empty($errors['date_debut'])): ?>
                                            <div class="text-danger small mt-1"><i class="fas fa-exclamation-circle me-1"></i><?php echo htmlspecialchars($errors['date_debut']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="event-form-label">Heure de début <span class="text-danger">*</span></label>
                                        <input type="time" class="form-control event-form-input <?php echo !empty($errors['heure_debut']) ? 'is-invalid' : ''; ?>" id="heure_debut" name="heure_debut"
                                               value="<?php echo htmlspecialchars($old['heure_debut'] ?? ''); ?>">
                                        <?php if (!empty($errors['heure_debut'])): ?>
                                            <div class="text-danger small mt-1"><i class="fas fa-exclamation-circle me-1"></i><?php echo htmlspecialchars($errors['heure_debut']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="event-form-label">Date de fin</label>
                                        <input type="date" class="form-control event-form-input <?php echo !empty($errors['date_fin']) ? 'is-invalid' : ''; ?>" id="date_fin" name="date_fin"
                                               value="<?php echo htmlspecialchars($old['date_fin'] ?? ($event['date_fin'] ?? '')); ?>">
                                        <?php if (!empty($errors['date_fin'])): ?>
                                            <div class="text-danger small mt-1"><i class="fas fa-exclamation-circle me-1"></i><?php echo htmlspecialchars($errors['date_fin']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="event-form-label">Heure de fin</label>
                                        <input type="time" class="form-control event-form-input <?php echo !empty($errors['heure_fin']) ? 'is-invalid' : ''; ?>" id="heure_fin" name="heure_fin"
                                               value="<?php echo htmlspecialchars($old['heure_fin'] ?? ''); ?>">
                                        <?php if (!empty($errors['heure_fin'])): ?>
                                            <div class="text-danger small mt-1"><i class="fas fa-exclamation-circle me-1"></i><?php echo htmlspecialchars($errors['heure_fin']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Lieu et lien -->
                            <div class="event-section">
                                <h6 class="event-section-title">
                                    <i class="fas fa-map-marker-alt me-2"></i>Lieu et lien
                                </h6>
                                <div class="mb-3">
                                    <label class="event-form-label">Lieu</label>
                                    <input type="text" class="form-control event-form-input <?php echo !empty($errors['lieu']) ? 'is-invalid' : ''; ?>" id="lieu" name="lieu"
                                           value="<?php echo htmlspecialchars($old['lieu'] ?? ($event['lieu'] ?? '')); ?>"
                                           placeholder="Adresse ou nom du lieu">
                                    <?php if (!empty($errors['lieu'])): ?>
                                        <div class="text-danger small mt-1"><i class="fas fa-exclamation-circle me-1"></i><?php echo htmlspecialchars($errors['lieu']); ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="mb-3">
                                    <label class="event-form-label">Lien visioconférence</label>
                                    <input type="url" class="form-control event-form-input <?php echo !empty($errors['lien_visioconference']) ? 'is-invalid' : ''; ?>" id="lien_visioconference" name="lien_visioconference"
                                           value="<?php echo htmlspecialchars($old['lien_visioconference'] ?? ($event['lien_visioconference'] ?? '')); ?>"
                                           placeholder="https://zoom.us/...">
                                    <?php if (!empty($errors['lien_visioconference'])): ?>
                                        <div class="text-danger small mt-1"><i class="fas fa-exclamation-circle me-1"></i><?php echo htmlspecialchars($errors['lien_visioconference']); ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Sidebar -->
                        <div class="col-lg-4">
                            <div class="event-section">
                                <h6 class="event-section-title">
                                    <i class="fas fa-cog me-2"></i>Paramètres
                                </h6>
                                <div class="mb-3">
                                    <label class="event-form-label">Places maximum</label>
                                    <input type="number" class="form-control event-form-input <?php echo !empty($errors['nombre_places_max']) ? 'is-invalid' : ''; ?>" id="nombre_places_max" name="nombre_places_max" min="0"
                                           value="<?php echo htmlspecialchars($old['nombre_places_max'] ?? ($event['capacite_max'] ?? 0)); ?>"
                                           placeholder="0 = illimité">
                                    <?php if (!empty($errors['nombre_places_max'])): ?>
                                        <div class="text-danger small mt-1"><i class="fas fa-exclamation-circle me-1"></i><?php echo htmlspecialchars($errors['nombre_places_max']); ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="event-section">
                                <h6 class="event-section-title">
                                    <i class="fas fa-image me-2"></i>Image
                                </h6>
                                <div class="mb-3">
                                    <label class="event-form-label">Image de l'événement</label>
                                    <input type="file" class="form-control event-form-input" id="image" name="image" accept="image/*">
                                    <small class="text-muted">Formats acceptés: JPG, PNG, GIF</small>
                                </div>
                                <?php if (!empty($event['image'])): ?>
                                    <div class="mt-2">
                                        <p class="small text-muted mb-1">Image actuelle:</p>
                                        <img src="<?php echo htmlspecialchars($event['image']); ?>" alt="Image actuelle" class="img-fluid rounded" style="max-height: 150px;">
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="event-section">
                                <button type="submit" class="btn event-form-btn-primary w-100 mb-2">
                                    <i class="fas fa-save me-2"></i> <?php echo $isEdit ? 'Mettre à jour' : 'Créer l\'événement'; ?>
                                </button>
                                <a href="index.php?page=evenements_admin" class="btn event-form-btn-secondary w-100">
                                    <i class="fas fa-times me-2"></i> Annuler
                                </a>
                            </div>
                        </div>
                    </div>
            </form>
        </div>
    </div>
</div>
