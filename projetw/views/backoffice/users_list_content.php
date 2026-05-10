<?php
// Vue de contenu pour la liste des utilisateurs
// Variables disponibles: $users (array)
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Gestion des utilisateurs</h2>
        <a href="index.php?page=users&action=create" class="btn btn-success">
            <i class="fas fa-plus"></i> Ajouter un utilisateur
        </a>
    </div>

    <?php if (empty($users)): ?>
        <div class="alert alert-info">Aucun utilisateur trouvé.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Nom</th>
                        <th>Prénom</th>
                        <th>Email</th>
                        <th>Rôle</th>
                        <th>Statut</th>
                        <th>Date création</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['id']); ?></td>
                            <td><?php echo htmlspecialchars($user['nom']); ?></td>
                            <td><?php echo htmlspecialchars($user['prenom']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>
                                <span class="badge bg-info">
                                    <?php echo htmlspecialchars($user['role'] ?? 'N/A'); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo ($user['statut'] === 'actif') ? 'success' : 'danger'; ?>">
                                    <?php echo htmlspecialchars($user['statut'] ?? 'N/A'); ?>
                                </span>
                            </td>
                            <td><?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?></td>
                            <td>
                                <a href="index.php?page=users&action=show&id=<?php echo $user['id']; ?>" 
                                   class="btn btn-sm btn-info" title="Voir">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="index.php?page=users&action=edit&id=<?php echo $user['id']; ?>" 
                                   class="btn btn-sm btn-warning" title="Éditer">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button onclick="confirmDelete('index.php?page=users&action=delete&id=<?php echo $user['id']; ?>')" 
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
    if (confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?')) {
        window.location.href = url;
    }
}
</script>
