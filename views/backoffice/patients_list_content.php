<?php
// Vue de contenu pour la liste des patients
// Variables disponibles: $patients (array)
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Gestion des patients</h2>
        <a href="index.php?page=patients&action=add" class="btn btn-success">
            <i class="fas fa-plus"></i> Ajouter un patient
        </a>
    </div>

    <?php if (empty($patients)): ?>
        <div class="alert alert-info">Aucun patient trouvé.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Nom</th>
                        <th>Prénom</th>
                        <th>Email</th>
                        <th>Téléphone</th>
                        <th>Groupe sanguin</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($patients as $patient): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($patient['id']); ?></td>
                            <td><?php echo htmlspecialchars($patient['nom']); ?></td>
                            <td><?php echo htmlspecialchars($patient['prenom']); ?></td>
                            <td><?php echo htmlspecialchars($patient['email']); ?></td>
                            <td><?php echo htmlspecialchars($patient['telephone'] ?? '-'); ?></td>
                            <td>
                                <?php if (!empty($patient['groupe_sanguin'])): ?>
                                    <span class="badge bg-danger"><?php echo htmlspecialchars($patient['groupe_sanguin']); ?></span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo ($patient['statut'] === 'actif') ? 'success' : 'danger'; ?>">
                                    <?php echo htmlspecialchars($patient['statut'] ?? 'N/A'); ?>
                                </span>
                            </td>
                            <td>
                                <a href="index.php?page=patients&action=show&id=<?php echo $patient['id']; ?>" 
                                   class="btn btn-sm btn-info" title="Voir">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="index.php?page=patients&action=edit&id=<?php echo $patient['id']; ?>" 
                                   class="btn btn-sm btn-warning" title="Éditer">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button onclick="confirmDelete('index.php?page=patients&action=delete&id=<?php echo $patient['id']; ?>')" 
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
    if (confirm('Êtes-vous sûr de vouloir supprimer ce patient ?')) {
        window.location.href = url;
    }
}
</script>
