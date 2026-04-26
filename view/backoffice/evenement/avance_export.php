<?php $pageTitle = 'Export participants – ' . htmlspecialchars($evenement['titre']); ?>
<?php require __DIR__ . '/../layout_header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0 fw-semibold">Export CSV — <?= htmlspecialchars($evenement['titre']) ?></h5>
        <p class="text-muted small mb-0">
            <?= date('d/m/Y', strtotime($evenement['date_debut'])) ?> →
            <?= date('d/m/Y', strtotime($evenement['date_fin'])) ?>
            &nbsp;|&nbsp; <?= htmlspecialchars($evenement['lieu']) ?>
        </p>
    </div>
    <a href="index.php?controller=evenementavance&action=stats&id=<?= $evenement['id'] ?>"
       class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Retour aux stats
    </a>
</div>

<!-- Filtre statut avant export -->
<div class="card mb-4">
    <div class="card-header fw-semibold">
        <i class="bi bi-funnel me-1 text-primary"></i> Options d'export
    </div>
    <div class="card-body">
        <form method="GET" action="index.php" class="row g-3 align-items-end">
            <input type="hidden" name="controller" value="evenementavance">
            <input type="hidden" name="action"     value="exportPreview">
            <input type="hidden" name="id"         value="<?= $evenement['id'] ?>">

            <div class="col-md-4">
                <label class="form-label small fw-semibold">Filtrer par statut</label>
                <select name="statut" class="form-select form-select-sm">
                    <option value="">— Tous les statuts —</option>
                    <option value="en_attente" <?= $statut === 'en_attente' ? 'selected' : '' ?>>En attente</option>
                    <option value="confirme"   <?= $statut === 'confirme'   ? 'selected' : '' ?>>Confirmés seulement</option>
                    <option value="annule"     <?= $statut === 'annule'     ? 'selected' : '' ?>>Annulés seulement</option>
                </select>
            </div>

            <div class="col-auto">
                <button type="submit" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-eye me-1"></i> Prévisualiser
                </button>
            </div>

            <div class="col-auto">
                <a href="index.php?controller=evenementavance&action=exportCsv&id=<?= $evenement['id'] ?><?= $statut ? '&statut='.urlencode($statut) : '' ?>"
                   class="btn btn-success btn-sm">
                    <i class="bi bi-file-earmark-spreadsheet me-1"></i>
                    Télécharger CSV (<?= count($participants) ?> ligne<?= count($participants)>1?'s':'' ?>)
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Prévisualisation -->
<div class="card">
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
                        <th>Téléphone</th>
                        <th>Profession</th>
                        <th>Statut</th>
                        <th>Date inscription</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $statutColor = ['en_attente'=>'warning','confirme'=>'success','annule'=>'danger'];
                $statutLabel = ['en_attente'=>'En attente','confirme'=>'Confirmé','annule'=>'Annulé'];
                foreach ($participants as $i => $p):
                ?>
                    <tr>
                        <td class="text-muted"><?= $i+1 ?></td>
                        <td class="fw-semibold"><?= htmlspecialchars($p['nom']) ?></td>
                        <td><?= htmlspecialchars($p['prenom']) ?></td>
                        <td><?= htmlspecialchars($p['email']) ?></td>
                        <td><?= htmlspecialchars($p['telephone']) ?></td>
                        <td><?= htmlspecialchars($p['profession']) ?></td>
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

<?php require __DIR__ . '/../layout_footer.php'; ?>
