<?php
// Vue de contenu pour la liste des médecins
// Variables disponibles: $medecins (array)
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Gestion des médecins</h2>
        <a href="index.php?page=medecins_admin&action=add" class="btn btn-success">
            <i class="fas fa-plus"></i> Ajouter un médecin
        </a>
    </div>

    <?php if (empty($medecins)): ?>
        <div class="alert alert-info">Aucun médecin trouvé.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Nom</th>
                        <th>Prénom</th>
                        <th>Email</th>
                        <th>Spécialité</th>
                        <th>Numéro d'ordre</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($medecins as $medecin): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($medecin['nom']); ?></td>
                            <td><?php echo htmlspecialchars($medecin['prenom']); ?></td>
                            <td><?php echo htmlspecialchars($medecin['email']); ?></td>
                            <td><?php echo htmlspecialchars($medecin['specialite'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($medecin['numero_ordre'] ?? '-'); ?></td>
                            <td>
                                <span class="badge bg-<?php 
                                    $statut = $medecin['statut_validation'] ?? 'non_valide';
                                    echo ($statut === 'valide') ? 'success' : (($statut === 'en_attente') ? 'warning' : 'danger');
                                ?>">
                                    <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $statut))); ?>
                                </span>
                            </td>
                            <td>
                                <a href="index.php?page=medecins&action=show&id=<?php echo $medecin['user_id']; ?>" 
                                   class="btn btn-sm btn-info" title="Voir">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="index.php?page=medecins&action=edit&id=<?php echo $medecin['user_id']; ?>" 
                                   class="btn btn-sm btn-warning" title="Éditer">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php if (($medecin['statut_validation'] ?? 'non_valide') === 'en_attente'): ?>
                                    <a href="index.php?page=medecins&action=validate&id=<?php echo $medecin['user_id']; ?>" 
                                       class="btn btn-sm btn-success" title="Valider">
                                        <i class="fas fa-check"></i>
                                    </a>
                                <?php endif; ?>
                                <button onclick="confirmDelete('index.php?page=medecins&action=delete&id=<?php echo $medecin['user_id']; ?>')" 
                                        class="btn btn-sm btn-danger" title="Supprimer">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script>
function confirmDelete(url) {
    if (confirm('Êtes-vous sûr de vouloir supprimer ce médecin ?')) {
        window.location.href = url;
    }
}
</script>
