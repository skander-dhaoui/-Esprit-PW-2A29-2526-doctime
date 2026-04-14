<?php
// APRÈS - accessible à tout utilisateur connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php?page=login');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($event) ? 'Modifier' : 'Ajouter' ?> un événement</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f0f2f5; font-family: 'Segoe UI', sans-serif; }
        .sidebar { width: 260px; background: #1a2035; color: white; position: fixed; height: 100%; }
        .main-content { margin-left: 260px; padding: 20px; }
        .card { border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .form-label { font-weight: 600; }
        .form-control:focus, .form-select:focus { border-color: #4CAF50; box-shadow: 0 0 0 3px rgba(76,175,80,0.1); }
        .sponsor-item { background: #f8f9fa; border: 1px solid #dee2e6; transition: all 0.2s; }
        .sponsor-item:hover { background: #fff; border-color: #4CAF50; }
        .btn-remove-sponsor { background: #dc3545; color: white; border: none; padding: 8px 12px; border-radius: 6px; }
        .btn-remove-sponsor:hover { background: #c82333; }
    </style>
</head>
<body>

<div class="sidebar p-3">
    <h4 class="text-center mb-4">Valorys Admin</h4>
    <hr>
    <a href="index.php?page=dashboard" class="text-white d-block py-2">📊 Dashboard</a>
    <a href="index.php?page=admin_events" class="text-white d-block py-2 bg-primary px-2 rounded">📅 Événements</a>
    <a href="index.php?page=logout" class="text-white d-block py-2 mt-5">🚪 Déconnexion</a>
</div>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-<?= isset($event) ? 'edit' : 'plus' ?> me-2"></i><?= isset($event) ? 'Modifier' : 'Ajouter' ?> un événement</h2>
        <a href="index.php?page=admin_events" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>Retour</a>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="index.php?page=admin_events&action=<?= isset($event) ? 'update&id=' . $event['id'] : 'store' ?>" id="eventForm">
                <div class="row">
                    <div class="col-md-8 mb-3">
                        <label class="form-label">Titre *</label>
                        <input type="text" name="titre" class="form-control" value="<?= isset($event) ? htmlspecialchars($event['titre']) : '' ?>" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Image (URL)</label>
                        <input type="url" name="image" class="form-control" value="<?= isset($event) ? htmlspecialchars($event['image']) : '' ?>" placeholder="https://...">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Date de début *</label>
                        <input type="datetime-local" name="date_debut" class="form-control" value="<?= isset($event) ? date('Y-m-d\TH:i', strtotime($event['date_debut'])) : '' ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Date de fin *</label>
                        <input type="datetime-local" name="date_fin" class="form-control" value="<?= isset($event) ? date('Y-m-d\TH:i', strtotime($event['date_fin'])) : '' ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Lieu</label>
                        <input type="text" name="lieu" class="form-control" value="<?= isset($event) ? htmlspecialchars($event['lieu']) : '' ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Adresse complète</label>
                        <input type="text" name="adresse" class="form-control" value="<?= isset($event) ? htmlspecialchars($event['adresse']) : '' ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Capacité maximale</label>
                        <input type="number" name="capacite_max" class="form-control" value="<?= isset($event) ? $event['capacite_max'] : '' ?>" min="0">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Prix (€)</label>
                        <input type="number" name="prix" class="form-control" step="0.01" value="<?= isset($event) ? $event['prix'] : '0' ?>" min="0">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Statut</label>
                        <select name="status" class="form-select">
                            <option value="à venir" <?= (isset($event) && $event['status'] === 'à venir') ? 'selected' : '' ?>>À venir</option>
                            <option value="terminé" <?= (isset($event) && $event['status'] === 'terminé') ? 'selected' : '' ?>>Terminé</option>
                            <option value="annulé" <?= (isset($event) && $event['status'] === 'annulé') ? 'selected' : '' ?>>Annulé</option>
                        </select>
                    </div>
                    <div class="col-12 mb-3">
                        <label class="form-label">Description courte</label>
                        <textarea name="description" class="form-control" rows="3"><?= isset($event) ? htmlspecialchars($event['description']) : '' ?></textarea>
                    </div>
                    <div class="col-12 mb-3">
                        <label class="form-label">Contenu détaillé</label>
                        <textarea name="contenu" class="form-control" rows="6"><?= isset($event) ? htmlspecialchars($event['contenu']) : '' ?></textarea>
                    </div>
                </div>

                <!-- SECTION SPONSORS -->
                <div class="card mt-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-handshake me-2"></i>Sponsors et Partenaires</h5>
                    </div>
                    <div class="card-body">
                        <div id="sponsors-container">
                            <?php if (isset($event) && !empty($event['id'])): ?>
                                <?php if (!empty($sponsors)): ?>
                                    <?php foreach ($sponsors as $index => $sponsor): ?>
                                    <div class="sponsor-item card mb-3 p-3">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <label class="form-label">Nom du sponsor *</label>
                                                <input type="text" name="sponsors[<?= $index ?>][sponsor_name]" class="form-control" value="<?= htmlspecialchars($sponsor['sponsor_name']) ?>" required>
                                                <input type="hidden" name="sponsors[<?= $index ?>][id]" value="<?= $sponsor['id'] ?>">
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label">Montant (€)</label>
                                                <input type="number" name="sponsors[<?= $index ?>][amount]" class="form-control" step="0.01" value="<?= $sponsor['amount'] ?>">
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label">Type</label>
                                                <select name="sponsors[<?= $index ?>][contribution_type]" class="form-select">
                                                    <option value="financier" <?= $sponsor['contribution_type'] == 'financier' ? 'selected' : '' ?>>Financier</option>
                                                    <option value="materiel" <?= $sponsor['contribution_type'] == 'materiel' ? 'selected' : '' ?>>Matériel</option>
                                                    <option value="prestation" <?= $sponsor['contribution_type'] == 'prestation' ? 'selected' : '' ?>>Prestation</option>
                                                    <option value="autre" <?= $sponsor['contribution_type'] == 'autre' ? 'selected' : '' ?>>Autre</option>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Site web</label>
                                                <input type="url" name="sponsors[<?= $index ?>][sponsor_website]" class="form-control" value="<?= htmlspecialchars($sponsor['sponsor_website'] ?? '') ?>">
                                            </div>
                                            <div class="col-md-1 d-flex align-items-end">
                                                <button type="button" class="btn-remove-sponsor" onclick="this.closest('.sponsor-item').remove()">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="row mt-2">
                                            <div class="col-md-12">
                                                <label class="form-label">Logo URL</label>
                                                <input type="text" name="sponsors[<?= $index ?>][sponsor_logo]" class="form-control" value="<?= htmlspecialchars($sponsor['sponsor_logo'] ?? '') ?>" placeholder="https://...">
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="alert alert-info">Aucun sponsor pour le moment. Cliquez sur "Ajouter un sponsor" pour en ajouter.</div>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="alert alert-info">Vous pourrez ajouter des sponsors après avoir créé l'événement.</div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (isset($event) && !empty($event['id'])): ?>
                        <button type="button" class="btn btn-outline-primary mt-2" onclick="addSponsor()">
                            <i class="fas fa-plus me-2"></i>Ajouter un sponsor
                        </button>
                        <?php endif; ?>
                    </div>
                </div>

                <hr class="mt-4">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i><?= isset($event) ? 'Mettre à jour' : 'Créer' ?></button>
                <a href="index.php?page=admin_events" class="btn btn-secondary">Annuler</a>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
let sponsorCounter = <?= isset($sponsors) ? count($sponsors) : 0 ?>;

function addSponsor() {
    const container = document.getElementById('sponsors-container');
    const newIndex = sponsorCounter++;
    const html = `
        <div class="sponsor-item card mb-3 p-3">
            <div class="row">
                <div class="col-md-4">
                    <label class="form-label">Nom du sponsor *</label>
                    <input type="text" name="sponsors[${newIndex}][sponsor_name]" class="form-control" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Montant (€)</label>
                    <input type="number" name="sponsors[${newIndex}][amount]" class="form-control" step="0.01">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Type</label>
                    <select name="sponsors[${newIndex}][contribution_type]" class="form-select">
                        <option value="financier">Financier</option>
                        <option value="materiel">Matériel</option>
                        <option value="prestation">Prestation</option>
                        <option value="autre">Autre</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Site web</label>
                    <input type="url" name="sponsors[${newIndex}][sponsor_website]" class="form-control">
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button type="button" class="btn-remove-sponsor" onclick="this.closest('.sponsor-item').remove()">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-md-12">
                    <label class="form-label">Logo URL</label>
                    <input type="text" name="sponsors[${newIndex}][sponsor_logo]" class="form-control" placeholder="https://...">
                </div>
            </div>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', html);
    
    // Supprimer le message d'alerte s'il existe
    const alertInfo = container.querySelector('.alert-info');
    if (alertInfo) {
        alertInfo.remove();
    }
}

// Validation avant soumission
document.getElementById('eventForm')?.addEventListener('submit', function(e) {
    let hasError = false;
    let errorMessage = '';
    
    // Vérifier les dates
    const dateDebut = document.querySelector('input[name="date_debut"]')?.value;
    const dateFin = document.querySelector('input[name="date_fin"]')?.value;
    
    if (dateDebut && dateFin && dateDebut > dateFin) {
        errorMessage += 'La date de début doit être antérieure à la date de fin.\n';
        hasError = true;
    }
    
    if (hasError) {
        e.preventDefault();
        alert(errorMessage);
    }
});
</script>
</body>
</html>