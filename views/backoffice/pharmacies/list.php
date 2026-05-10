<?php
// views/backoffice/pharmacies/list.php - Liste des pharmacies avec fusion User+Event

?>
<div class="main-content">
    <div class="page-header">
        <h4><i class="fas fa-hospital"></i> Pharmacies - Fusion User+Event</h4>
        <a href="index.php?page=pharmacies_admin&action=create" class="btn btn-primary">
            <i class="fas fa-plus"></i> Ajouter une pharmacie
        </a>
    </div>

    <?php if ($flash): ?>
        <div class="flash-box flash-<?= $flash['type'] ?>">
            <i class="fas fa-<?= $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
            <?= htmlspecialchars($flash['message']) ?>
        </div>
    <?php endif; ?>

    <!-- Filtres -->
    <div class="content-card" style="margin-bottom: 25px;">
        <h5 style="margin-top: 0; color: #1a2035;">Filtres</h5>
        <form method="GET" class="filter-form" style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 15px;">
            <input type="hidden" name="page" value="pharmacies_admin">
            
            <div class="form-group">
                <label>Rechercher</label>
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                       placeholder="Nom ou adresse..." class="form-control">
            </div>

            <div class="form-group">
                <label>Ville</label>
                <select name="ville" class="form-control">
                    <option value="">-- Toutes les villes --</option>
                    <?php foreach ($villes as $v): ?>
                        <option value="<?= htmlspecialchars($v) ?>" <?= $ville === $v ? 'selected' : '' ?>>
                            <?= htmlspecialchars($v) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Statut</label>
                <select name="statut" class="form-control">
                    <option value="">-- Tous les statuts --</option>
                    <option value="actif" <?= $statut === 'actif' ? 'selected' : '' ?>>Actif</option>
                    <option value="inactif" <?= $statut === 'inactif' ? 'selected' : '' ?>>Inactif</option>
                    <option value="fermé" <?= $statut === 'fermé' ? 'selected' : '' ?>>Fermé</option>
                </select>
            </div>

            <div class="form-group" style="display: flex; align-items: flex-end; gap: 10px;">
                <button type="submit" class="btn btn-primary" style="flex: 1;">
                    <i class="fas fa-search"></i> Filtrer
                </button>
                <a href="index.php?page=pharmacies_admin" class="btn btn-secondary" style="flex: 1;">
                    <i class="fas fa-redo"></i> Réinitialiser
                </a>
            </div>
        </form>
    </div>

    <!-- Tableau des pharmacies -->
    <div class="content-card">
        <div class="card-title-row">
            <h5>Liste des Pharmacies (<?= count($pharmacies) ?>)</h5>
        </div>

        <?php if (empty($pharmacies)): ?>
            <p style="text-align: center; color: #999; padding: 30px;">
                <i class="fas fa-inbox" style="font-size: 32px; margin-bottom: 10px; display: block;"></i>
                Aucune pharmacie trouvée.
            </p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Adresse</th>
                            <th>Ville</th>
                            <th>Téléphone</th>
                            <th>Utilisateurs</th>
                            <th>Événements</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pharmacies as $pharmacie): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($pharmacie['nom']) ?></strong>
                                </td>
                                <td>
                                    <?= htmlspecialchars(substr($pharmacie['adresse'], 0, 40)) ?>...
                                </td>
                                <td><?= htmlspecialchars($pharmacie['ville']) ?></td>
                                <td><?= htmlspecialchars($pharmacie['telephone']) ?></td>
                                <td>
                                    <span class="badge badge-info">
                                        <?= count($pharmacie['utilisateurs']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-success">
                                        <?= count($pharmacie['evenements']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-<?= $pharmacie['statut'] === 'actif' ? 'success' : 'warning' ?>">
                                        <?= ucfirst($pharmacie['statut']) ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="index.php?page=pharmacies_admin&action=show&id=<?= $pharmacie['id'] ?>" 
                                       class="btn btn-sm btn-info" title="Voir">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="index.php?page=pharmacies_admin&action=edit&id=<?= $pharmacie['id'] ?>" 
                                       class="btn btn-sm btn-warning" title="Éditer">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form method="POST" action="index.php?page=pharmacies_admin&action=delete" 
                                          style="display: inline;" onsubmit="return confirm('Êtes-vous sûr ?');">
                                        <input type="hidden" name="id" value="<?= $pharmacie['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-danger" title="Supprimer">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.table {
    width: 100%;
    border-collapse: collapse;
}

.table thead {
    background: #f5f5f5;
    border-bottom: 2px solid #e0e0e0;
}

.table th {
    padding: 12px;
    text-align: left;
    font-weight: 600;
    color: #333;
}

.table td {
    padding: 12px;
    border-bottom: 1px solid #e0e0e0;
}

.table tr:hover {
    background: #fafafa;
}

.badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
}

.badge-info {
    background: #e3f2fd;
    color: #1976d2;
}

.badge-success {
    background: #e8f5e9;
    color: #388e3c;
}

.badge-warning {
    background: #fff3e0;
    color: #f57c00;
}

.btn {
    display: inline-block;
    padding: 8px 12px;
    border-radius: 4px;
    text-decoration: none;
    font-size: 13px;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-primary {
    background: #4CAF50;
    color: white;
}

.btn-primary:hover {
    background: #45a049;
}

.btn-secondary {
    background: #999;
    color: white;
}

.btn-info {
    background: #2196F3;
    color: white;
}

.btn-warning {
    background: #ff9800;
    color: white;
}

.btn-danger {
    background: #dc3545;
    color: white;
}

.btn-sm {
    padding: 4px 8px;
    font-size: 12px;
}

.filter-form {
    margin-bottom: 15px;
}

.form-control {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.table-responsive {
    overflow-x: auto;
}
</style>
