<?php
// views/backoffice/articles_list_content.php
// Contenu seulement, pas de HTML complet
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Gestion des Articles</h2>
        <a href="index.php?page=articles_admin&action=create" class="btn btn-success">
            <i class="fas fa-plus"></i> Nouvel Article
        </a>
    </div>

    <?php if (empty($articles)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> Aucun article disponible pour le moment.
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Titre</th>
                        <th>Auteur</th>
                        <th>Date</th>
                        <th>Vues</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($articles as $article): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars(substr($article['titre'] ?? '', 0, 60)) ?></strong></td>
                            <td><?= htmlspecialchars(($article['prenom'] ?? '') . ' ' . ($article['nom'] ?? '')) ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($article['created_at'] ?? '')) ?></td>
                            <td>
                                <span class="badge bg-info">
                                    <?= htmlspecialchars($article['vues'] ?? 0) ?>
                                </span>
                            </td>
                            <td>
                                <?php 
                                $status = $article['status'] ?? 'brouillon';
                                $badgeClass = match($status) {
                                    'publié' => 'bg-success',
                                    'archive' => 'bg-secondary',
                                    default => 'bg-warning'
                                };
                                ?>
                                <span class="badge <?= $badgeClass ?>">
                                    <?= htmlspecialchars(ucfirst($status)) ?>
                                </span>
                            </td>
                            <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="index.php?page=articles_admin&action=edit&id=<?= $article['id'] ?>" 
                                           class="btn btn-outline-warning" title="Modifier">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button type="button" class="btn btn-outline-danger" 
                                                onclick="confirmDelete(<?= $article['id'] ?>, '<?= addslashes($article['titre']) ?>')" 
                                                title="Supprimer">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function confirmDelete(id, title) {
    if (confirm('Êtes-vous sûr de vouloir supprimer "' + title + '" ?')) {
        window.location.href = 'index.php?page=articles_admin&action=delete&id=' + id;
    }
}
</script>
