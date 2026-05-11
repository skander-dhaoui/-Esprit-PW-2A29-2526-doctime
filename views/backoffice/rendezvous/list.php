<?php
/**
 * Liste des rendez-vous (admin) — shell DocTime comme le reste du back-office
 */
$pageTitle = 'Gestion des rendez-vous';
require_once __DIR__ . '/../layout_header.php';
?>

<?php if (!empty($_SESSION['flash'])): ?>
    <?php $f = $_SESSION['flash']; unset($_SESSION['flash']); ?>
    <?php $bt = (($f['type'] ?? '') === 'error' || ($f['type'] ?? '') === 'danger') ? 'danger' : (($f['type'] ?? '') === 'warning' ? 'warning' : 'success'); ?>
    <div class="alert alert-<?= htmlspecialchars($bt, ENT_QUOTES, 'UTF-8') ?> alert-dismissible fade show mb-4">
        <i class="fas fa-info-circle me-2"></i><?= htmlspecialchars((string) ($f['message'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
    </div>
<?php endif; ?>

<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h1 class="h4 mb-1 fw-bold" style="color:#1e293b;">Planning des rendez-vous</h1>
        <p class="text-muted small mb-0">Filtrez par date, statut ou recherche patient / médecin.</p>
    </div>
    <a href="index.php?page=rendez_vous_admin&amp;action=create" class="btn btn-primary btn-sm rounded-pill px-3">
        <i class="fas fa-plus me-1"></i>Nouveau rendez-vous
    </a>
</div>

<!-- Statistiques (jeu filtré affiché) -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card" style="background:linear-gradient(135deg,#1a7fa8,#1db88e)">
            <p>Total (liste)</p>
            <h3><?= (int) ($stats['total'] ?? 0) ?></h3>
            <i class="fas fa-calendar-check stat-icon"></i>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card" style="background:linear-gradient(135deg,#f59e0b,#ea580c)">
            <p>En attente</p>
            <h3><?= (int) ($stats['en_attente'] ?? 0) ?></h3>
            <i class="fas fa-clock stat-icon"></i>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card" style="background:linear-gradient(135deg,#22c55e,#15803d)">
            <p>Confirmés</p>
            <h3><?= (int) ($stats['confirmes'] ?? 0) ?></h3>
            <i class="fas fa-check-circle stat-icon"></i>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card" style="background:linear-gradient(135deg,#64748b,#475569)">
            <p>Terminés</p>
            <h3><?= (int) ($stats['termines'] ?? 0) ?></h3>
            <i class="fas fa-flag-checkered stat-icon"></i>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-3 border-bottom">
        <h6 class="mb-0 fw-bold"><i class="fas fa-filter me-2 text-primary"></i>Filtres</h6>
    </div>
    <div class="card-body">
        <form method="get" class="row g-3 align-items-end">
            <input type="hidden" name="page" value="rendez_vous_admin">
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1">Date</label>
                <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($_GET['date'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1">Statut</label>
                <select name="statut" class="form-select">
                    <option value="">Tous les statuts</option>
                    <option value="en_attente" <?= ($_GET['statut'] ?? '') === 'en_attente' ? 'selected' : '' ?>>En attente</option>
                    <option value="confirmé" <?= ($_GET['statut'] ?? '') === 'confirmé' ? 'selected' : '' ?>>Confirmé</option>
                    <option value="terminé" <?= ($_GET['statut'] ?? '') === 'terminé' ? 'selected' : '' ?>>Terminé</option>
                    <option value="annulé" <?= ($_GET['statut'] ?? '') === 'annulé' ? 'selected' : '' ?>>Annulé</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label small text-muted mb-1">Recherche</label>
                <input type="text" name="search" class="form-control" placeholder="Nom ou prénom patient / médecin…" value="<?= htmlspecialchars($_GET['search'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search me-1"></i>Filtrer</button>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Patient</th>
                        <th>Médecin</th>
                        <th>Spécialité</th>
                        <th>Date</th>
                        <th>Heure</th>
                        <th>Motif</th>
                        <th>Statut</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($rdvs)): ?>
                        <?php foreach ($rdvs as $rdv): ?>
                            <?php
                            $st = (string) ($rdv['statut'] ?? '');
                            $badgeClass = match ($st) {
                                'confirmé' => 'success',
                                'en_attente' => 'warning text-dark',
                                'terminé' => 'info text-dark',
                                'annulé' => 'danger',
                                default => 'secondary',
                            };
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($rdv['patient_prenom'] . ' ' . $rdv['patient_nom']) ?></td>
                                <td>Dr. <?= htmlspecialchars($rdv['medecin_prenom'] . ' ' . $rdv['medecin_nom']) ?></td>
                                <td><?= htmlspecialchars($rdv['specialite'] ?? '—') ?></td>
                                <td class="text-nowrap"><?= !empty($rdv['date_rendezvous']) ? date('d/m/Y', strtotime($rdv['date_rendezvous'])) : '—' ?></td>
                                <td class="text-nowrap"><?= htmlspecialchars((string) ($rdv['heure_rendezvous'] ?? '')) ?></td>
                                <td><span class="small text-muted"><?php
                                    $mot = (string) ($rdv['motif'] ?? '');
                                    if (function_exists('mb_strimwidth')) {
                                        echo htmlspecialchars(mb_strimwidth($mot, 0, 42, '…', 'UTF-8'));
                                    } else {
                                        echo htmlspecialchars(strlen($mot) > 40 ? (substr($mot, 0, 40) . '…') : $mot);
                                    }
                                ?></span></td>
                                <td><span class="badge rounded-pill bg-<?= $badgeClass ?>"><?= htmlspecialchars($st ?: '—') ?></span></td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="index.php?page=rendez_vous_admin&amp;action=show&amp;id=<?= (int) $rdv['id'] ?>" class="btn btn-outline-primary" title="Voir"><i class="fas fa-eye"></i></a>
                                        <a href="index.php?page=rendez_vous_admin&amp;action=edit&amp;id=<?= (int) $rdv['id'] ?>" class="btn btn-outline-warning" title="Modifier"><i class="fas fa-edit"></i></a>
                                        <a href="index.php?page=rendez_vous_admin&amp;action=delete&amp;id=<?= (int) $rdv['id'] ?>" class="btn btn-outline-danger" title="Supprimer" onclick="return confirm('Supprimer ce rendez-vous ?');"><i class="fas fa-trash"></i></a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                <i class="fas fa-calendar-times fa-2x d-block mb-2 opacity-50"></i>
                                Aucun rendez-vous ne correspond aux critères.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layout_footer.php'; ?>
