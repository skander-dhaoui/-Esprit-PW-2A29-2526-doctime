<?php
require_once __DIR__ . '/../../models/Event.php';

$eventModel = new Event();
$events = $eventModel->getAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Événements</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #f4f6f9; font-family: 'Segoe UI', sans-serif; }
        .main-content { margin-left: 260px; padding: 25px; }
        .navbar-custom { background: linear-gradient(135deg, #17a2b8 0%, #4CAF50 100%); color: white; }
        .table thead { background-color: #f8f9fa; border-bottom: 2px solid #dee2e6; }
        .table thead th { font-weight: 600; color: #495057; text-transform: uppercase; font-size: 0.85rem; }
        .table tbody tr { border-bottom: 1px solid #dee2e6; }
        .table tbody tr:hover { background-color: #f8f9fa; }
        .badge-statut { font-weight: 600; padding: 6px 12px; }
        .badge-sponsor { background: #ffc107; color: #333; font-weight: 600; }
        .btn-icon { width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center; }
        .card { border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,.05); }
        @media(max-width:768px){ .main-content{ margin-left:0; } }
    </style>
</head>
<body>
<?php require_once __DIR__ . '/sidebar.php'; ?>

<div class="main-content">

    <!-- En-tête -->
    <div class="mb-4">
        <h4 class="mb-2 fw-bold" style="color:#1e2a3e">
            <i class="bi bi-calendar-event me-2 text-primary"></i>Gestion des Événements
        </h4>
        <p class="text-muted small mb-0"><?= count($events) ?> événement(s)</p>
    </div>

    <!-- Boutons d'action -->
    <div class="mb-4 d-flex gap-2 justify-content-end">
        <a href="index.php?page=evenements_avance_admin" class="btn btn-outline-info btn-sm">
            <i class="bi bi-search me-1"></i> Recherche avancée
        </a>
        <a href="index.php?page=evenements_avance_admin" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-bar-chart me-1"></i> Vue avancée
        </a>
        <a href="index.php?page=evenements_admin&action=create" class="btn btn-success btn-sm">
            <i class="bi bi-plus-circle me-1"></i> Nouvel événement
        </a>
    </div>

    <!-- Table des événements -->
    <div class="card">
        <div class="card-body p-0">
            <?php if (empty($events)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-calendar-x fs-1 d-block mb-2"></i>
                    Aucun événement pour le moment.
                </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th>TITRE</th>
                            <th>SPÉCIALITÉ</th>
                            <th>LIEU</th>
                            <th>DATES</th>
                            <th>CAPACITÉ</th>
                            <th>STATUT</th>
                            <th>SPONSOR</th>
                            <th style="width: 150px; text-align: center;">ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($events as $e): 
                        $inscrits = $e['nb_participants'] ?? 0;
                        $capacite = $e['capacite_max'] ?? 0;
                        $places = max(0, $capacite - $inscrits);
                        $taux = $capacite > 0 ? round(($inscrits / $capacite) * 100) : 0;
                        
                        $statusBadges = [
                            'à venir' => 'primary',
                            'en_cours' => 'info',
                            'terminé' => 'secondary',
                            'annulé' => 'danger'
                        ];
                        $statusColor = $statusBadges[$e['status']] ?? 'secondary';
                        $statusLabel = ucfirst($e['status']);
                    ?>
                        <tr>
                            <td class="text-muted">
                                <strong><?= $e['id'] ?></strong>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($e['titre']) ?></strong>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark"><?= htmlspecialchars($e['description'] ? substr($e['description'], 0, 20) : '—') ?></span>
                            </td>
                            <td><?= htmlspecialchars($e['lieu'] ?? 'Non spécifié') ?></td>
                            <td>
                                <small>
                                    <?= date('d/m/Y', strtotime($e['date_debut'])) ?><br>
                                    <span class="text-muted">→ <?= date('d/m/Y', strtotime($e['date_fin'])) ?></span>
                                </small>
                            </td>
                            <td>
                                <strong><?= $capacite ?></strong>
                            </td>
                            <td>
                                <span class="badge badge-statut bg-<?= $statusColor ?>">
                                    <?= $statusLabel ?>
                                </span>
                            </td>
                            <td>
                                <?php if (!empty($e['sponsor_nom'])): ?>
                                    <span class="badge badge-sponsor">
                                        <?= htmlspecialchars($e['sponsor_nom']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center;">
                                <a href="index.php?page=evenements_admin&action=edit&id=<?= $e['id'] ?>"
                                   class="btn btn-sm btn-outline-primary btn-icon" title="Modifier">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <button type="button" class="btn btn-sm btn-outline-danger btn-icon" 
                                        data-bs-toggle="modal" data-bs-target="#deleteModal<?= $e['id'] ?>"
                                        title="Supprimer">
                                    <i class="bi bi-trash"></i>
                                </button>
                                <a href="index.php?page=evenements_avance_admin&action=stats&id=<?= $e['id'] ?>"
                                   class="btn btn-sm btn-outline-info btn-icon" title="Statistiques">
                                    <i class="bi bi-bar-chart"></i>
                                </a>
                                <a href="index.php?page=evenements_avance_admin&action=exportPreview&id=<?= $e['id'] ?>"
                                   class="btn btn-sm btn-outline-success btn-icon" title="Exporter">
                                    <i class="bi bi-download"></i>
                                </a>
                            </td>

                            <!-- Modal de suppression -->
                            <div class="modal fade" id="deleteModal<?= $e['id'] ?>" tabindex="-1">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content">
                                        <div class="modal-header border-bottom">
                                            <h5 class="modal-title">
                                                <i class="bi bi-exclamation-triangle text-danger me-2"></i>Supprimer l'événement
                                            </h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p class="mb-0">Êtes-vous sûr de vouloir supprimer cet événement ?</p>
                                            <p class="text-muted small mt-2">
                                                <strong><?= htmlspecialchars($e['titre']) ?></strong><br>
                                                Cette action est irréversible.
                                            </p>
                                        </div>
                                        <div class="modal-footer border-top">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                Annuler
                                            </button>
                                            <a href="index.php?page=evenements_admin&action=delete&id=<?= $e['id'] ?>"
                                               class="btn btn-danger">
                                                <i class="bi bi-trash me-1"></i>Supprimer
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
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
