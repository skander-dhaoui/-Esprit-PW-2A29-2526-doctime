<?php
// views/backoffice/evenements_avance/recherche.php
// Recherche et filtrage avancé — porté depuis DOCTIME_advanced

$current_page = 'evenements_avance_admin';
$statutBadge  = ['à venir'=>'primary','en_cours'=>'info','terminé'=>'secondary','annulé'=>'danger'];
$statutLabels = ['à venir'=>'À venir','en_cours'=>'En cours','terminé'=>'Terminé','annulé'=>'Annulé'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recherche avancée — Événements</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        * { margin:0;padding:0;box-sizing:border-box; }
        body { background:#f4f6f9;font-family:'Segoe UI',sans-serif; }
        .main-content { margin-left:260px;padding:25px; }
        @media(max-width:768px){ .main-content{margin-left:0;} }
    </style>
</head>
<body>
<?php require_once __DIR__ . '/../sidebar.php'; ?>

<div class="main-content">

    <!-- En-tête -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0 fw-bold" style="color:#1e2a3e">
                <i class="bi bi-funnel me-2 text-primary"></i>Recherche avancée des événements
            </h4>
            <p class="text-muted small mb-0"><?= count($evenements) ?> résultat(s) trouvé(s)</p>
        </div>
        <a href="index.php?page=evenements_avance_admin"
           class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Vue d'ensemble
        </a>
    </div>

    <!-- Formulaire de filtrage -->
    <div class="card mb-4" style="border-radius:15px;box-shadow:0 2px 10px rgba(0,0,0,.05)">
        <div class="card-header fw-semibold">
            <i class="bi bi-sliders me-1 text-primary"></i> Filtres
        </div>
        <div class="card-body">
            <form method="GET" action="index.php">
                <input type="hidden" name="page"   value="evenements_avance_admin">
                <input type="hidden" name="action" value="recherche">

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Mot-clé</label>
                        <input type="text" name="q" class="form-control form-control-sm"
                               placeholder="Titre, description, lieu..."
                               value="<?= htmlspecialchars($filtres['q']) ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Statut</label>
                        <select name="statut" class="form-select form-select-sm">
                            <option value="">— Tous —</option>
                            <?php foreach ($statuts as $st): ?>
                                <option value="<?= $st ?>"
                                    <?= $filtres['statut'] === $st ? 'selected' : '' ?>>
                                    <?= $statutLabels[$st] ?? $st ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Sponsor</label>
                        <select name="sponsor_id" class="form-select form-select-sm">
                            <option value="">— Tous —</option>
                            <?php foreach ($sponsors as $sp): ?>
                                <option value="<?= $sp['id'] ?>"
                                    <?= (string)$filtres['sponsor_id'] === (string)$sp['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($sp['nom']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Date début (à partir de)</label>
                        <input type="date" name="date_debut_min" class="form-control form-control-sm"
                               value="<?= htmlspecialchars($filtres['date_debut_min']) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Date début (jusqu'à)</label>
                        <input type="date" name="date_debut_max" class="form-control form-control-sm"
                               value="<?= htmlspecialchars($filtres['date_debut_max']) ?>">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label small fw-semibold">Prix min (TND)</label>
                        <input type="number" name="prix_min" step="0.01" min="0"
                               class="form-control form-control-sm"
                               value="<?= htmlspecialchars($filtres['prix_min']) ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-semibold">Prix max (TND)</label>
                        <input type="number" name="prix_max" step="0.01" min="0"
                               class="form-control form-control-sm"
                               value="<?= htmlspecialchars($filtres['prix_max']) ?>">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label small fw-semibold">Trier par</label>
                        <div class="input-group input-group-sm">
                            <select name="tri" class="form-select form-select-sm">
                                <?php foreach (['date_debut'=>'Date','titre'=>'Titre','prix'=>'Prix','capacite_max'=>'Capacité'] as $v=>$l): ?>
                                    <option value="<?= $v ?>" <?= $filtres['tri']===$v?'selected':'' ?>><?= $l ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select name="ordre" class="form-select form-select-sm">
                                <option value="ASC"  <?= $filtres['ordre']==='ASC' ?'selected':'' ?>>↑</option>
                                <option value="DESC" <?= $filtres['ordre']==='DESC'?'selected':'' ?>>↓</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-12 d-flex align-items-center gap-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="avec_places" value="1"
                                   id="chkPlaces" <?= $filtres['avec_places'] ? 'checked' : '' ?>>
                            <label class="form-check-label small" for="chkPlaces">
                                Avec places disponibles uniquement
                            </label>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="bi bi-search me-1"></i> Rechercher
                        </button>
                        <a href="index.php?page=evenements_avance_admin&action=recherche"
                           class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-x-circle me-1"></i> Réinitialiser
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Résultats -->
    <div class="card" style="border-radius:15px;box-shadow:0 2px 10px rgba(0,0,0,.05)">
        <div class="card-body p-0">
            <?php if (empty($evenements)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-search fs-1 d-block mb-2"></i>
                    Aucun événement ne correspond à ces critères.
                </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small">
                    <thead class="table-light">
                        <tr>
                            <th>Titre</th><th>Lieu</th><th>Dates</th>
                            <th>Prix</th><th>Inscrits</th><th>Places</th>
                            <th>Statut</th><th>Sponsor</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($evenements as $e):
                        $places = max(0, (int)($e['places_restantes'] ?? ($e['capacite_max'] - $e['nb_inscrits'])));
                    ?>
                        <tr>
                            <td class="fw-semibold" style="max-width:180px">
                                <?= htmlspecialchars($e['titre']) ?>
                            </td>
                            <td><?= htmlspecialchars($e['lieu'] ?? '') ?></td>
                            <td>
                                <?= date('d/m/Y', strtotime($e['date_debut'])) ?><br>
                                <span class="text-muted">→ <?= date('d/m/Y', strtotime($e['date_fin'])) ?></span>
                            </td>
                            <td>
                                <?= $e['prix'] > 0
                                    ? number_format($e['prix'],2).' TND'
                                    : '<span class="badge bg-success">Gratuit</span>' ?>
                            </td>
                            <td><?= $e['nb_inscrits'] ?></td>
                            <td>
                                <span class="badge bg-<?= $places == 0 ? 'danger' : 'success' ?>">
                                    <?= $places == 0 ? 'Complet' : $places ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-<?= $statutBadge[$e['status']] ?? 'secondary' ?>">
                                    <?= $statutLabels[$e['status']] ?? $e['status'] ?>
                                </span>
                            </td>
                            <td><?= !empty($e['sponsor_nom']) ? htmlspecialchars($e['sponsor_nom']) : '<span class="text-muted">—</span>' ?></td>
                            <td class="text-center">
                                <a href="index.php?page=evenements_avance_admin&action=stats&id=<?= $e['id'] ?>"
                                   class="btn btn-sm btn-outline-info" title="Statistiques">
                                    <i class="bi bi-bar-chart"></i>
                                </a>
                                <a href="index.php?page=evenements_avance_admin&action=exportPreview&id=<?= $e['id'] ?>"
                                   class="btn btn-sm btn-outline-success" title="Export CSV">
                                    <i class="bi bi-download"></i>
                                </a>
                                <a href="index.php?page=evenements_admin&action=edit&id=<?= $e['id'] ?>"
                                   class="btn btn-sm btn-outline-primary" title="Modifier">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
