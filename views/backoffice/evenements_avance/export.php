<?php
// views/backoffice/evenements_avance/export.php
// Prévisualisation export CSV — porté depuis DOCTIME_advanced

$current_page = 'evenements_avance_admin';
$statutColor  = ['inscrit'=>'primary','présent'=>'success','absent'=>'danger'];
$statutLabel  = ['inscrit'=>'Inscrit','présent'=>'Présent','absent'=>'Absent'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Export CSV — <?= htmlspecialchars($evenement['titre']) ?></title>
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
                <i class="bi bi-file-earmark-spreadsheet me-2 text-success"></i>Export CSV
            </h4>
            <p class="text-muted small mb-0">
                <strong><?= htmlspecialchars($evenement['titre']) ?></strong>
                &nbsp;|&nbsp;
                <?= date('d/m/Y', strtotime($evenement['date_debut'])) ?> →
                <?= date('d/m/Y', strtotime($evenement['date_fin'])) ?>
                &nbsp;|&nbsp;<?= htmlspecialchars($evenement['lieu'] ?? '') ?>
            </p>
        </div>
        <a href="index.php?page=evenements_avance_admin&action=stats&id=<?= $evenement['id'] ?>"
           class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Retour aux stats
        </a>
    </div>

    <!-- Options d'export -->
    <div class="card mb-4" style="border-radius:15px;box-shadow:0 2px 10px rgba(0,0,0,.05)">
        <div class="card-header fw-semibold">
            <i class="bi bi-funnel me-1 text-primary"></i> Options d'export
        </div>
        <div class="card-body">
            <form method="GET" action="index.php" class="row g-3 align-items-end">
                <input type="hidden" name="page"   value="evenements_avance_admin">
                <input type="hidden" name="action" value="exportPreview">
                <input type="hidden" name="id"     value="<?= $evenement['id'] ?>">

                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Filtrer par statut</label>
                    <select name="statut" class="form-select form-select-sm">
                        <option value="">— Tous les statuts —</option>
                        <option value="inscrit"  <?= $statut === 'inscrit'  ? 'selected' : '' ?>>Inscrits</option>
                        <option value="présent"  <?= $statut === 'présent'  ? 'selected' : '' ?>>Présents</option>
                        <option value="absent"   <?= $statut === 'absent'   ? 'selected' : '' ?>>Absents</option>
                    </select>
                </div>

                <div class="col-auto">
                    <button type="submit" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-eye me-1"></i> Prévisualiser
                    </button>
                </div>

                <div class="col-auto">
                    <a href="index.php?page=evenements_avance_admin&action=exportCsv&id=<?= $evenement['id'] ?><?= $statut ? '&statut='.urlencode($statut) : '' ?>"
                       class="btn btn-success btn-sm">
                        <i class="bi bi-file-earmark-spreadsheet me-1"></i>
                        Télécharger CSV (<?= count($participants) ?> ligne<?= count($participants)>1?'s':'' ?>)
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Prévisualisation -->
    <div class="card" style="border-radius:15px;box-shadow:0 2px 10px rgba(0,0,0,.05)">
        <div class="card-header fw-semibold">
            <i class="bi bi-table me-1"></i> Aperçu — <?= count($participants) ?> participant(s)
        </div>
        <div class="card-body p-0">
            <?php if (empty($participants)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                    Aucun participant trouvé pour ce filtre.
                </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Nom</th>
                            <th>Prénom</th>
                            <th>Email</th>
                            <th>Statut</th>
                            <th>Date inscription</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($participants as $i => $p): ?>
                        <tr>
                            <td class="text-muted"><?= $i + 1 ?></td>
                            <td class="fw-semibold"><?= htmlspecialchars($p['nom']) ?></td>
                            <td><?= htmlspecialchars($p['prenom']) ?></td>
                            <td><?= htmlspecialchars($p['email']) ?></td>
                            <td>
                                <span class="badge bg-<?= $statutColor[$p['statut']] ?? 'secondary' ?>">
                                    <?= $statutLabel[$p['statut']] ?? $p['statut'] ?>
                                </span>
                            </td>
                            <td class="text-muted">
                                <?= date('d/m/Y H:i', strtotime($p['date_inscription'])) ?>
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
