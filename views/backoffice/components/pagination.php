<?php
/**
 * Composant de pagination réutilisable
 * Variables requises: $pagination (array)
 * Variables optionnelles: $page_param (string, défaut: 'p')
 * 
 * La variable $pagination doit contenir:
 * - total_items, per_page, current_page, total_pages
 * - has_previous, has_next, previous_page, next_page
 * - start_item, end_item
 */

$page_param = $page_param ?? 'p';

// Générer une URL en gardant les filtres GET existants.
$getPaginationUrl = function (int $page_num) use ($page_param): string {
    $params = $_GET;
    $params[$page_param] = $page_num;
    return 'index.php?' . htmlspecialchars(http_build_query($params), ENT_QUOTES, 'UTF-8');
};
?>

<div class="mt-4 d-flex justify-content-between align-items-center">
    <div class="text-muted small">
        Affichage <?php echo $pagination['start_item']; ?> à <?php echo $pagination['end_item']; ?> 
        sur <?php echo $pagination['total_items']; ?> résultats
    </div>
    
    <?php if ($pagination['total_pages'] > 1): ?>
        <nav aria-label="Pagination" class="pagination-nav">
            <ul class="pagination pagination-sm mb-0">
                <!-- Bouton Précédent -->
                <?php if ($pagination['has_previous']): ?>
                    <li class="page-item">
                        <a class="page-link" href="<?php echo $getPaginationUrl((int) $pagination['previous_page']); ?>" title="Page précédente">
                            <i class="fas fa-chevron-left"></i> Précédent
                        </a>
                    </li>
                <?php else: ?>
                    <li class="page-item disabled">
                        <span class="page-link">
                            <i class="fas fa-chevron-left"></i> Précédent
                        </span>
                    </li>
                <?php endif; ?>

                <!-- Numéros de pages -->
                <?php
                $start_page = max(1, $pagination['current_page'] - 2);
                $end_page = min($pagination['total_pages'], $pagination['current_page'] + 2);
                
                // Afficher la première page si nécessaire
                if ($start_page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="<?php echo $getPaginationUrl(1); ?>">1</a>
                    </li>
                    <?php if ($start_page > 2): ?>
                        <li class="page-item disabled">
                            <span class="page-link">...</span>
                        </li>
                    <?php endif;
                endif;

                // Afficher les pages au milieu
                for ($i = $start_page; $i <= $end_page; $i++): ?>
                    <li class="page-item <?php echo ($i === $pagination['current_page']) ? 'active' : ''; ?>">
                        <?php if ($i === $pagination['current_page']): ?>
                            <span class="page-link">
                                <?php echo $i; ?>
                                <span class="visually-hidden">(current)</span>
                            </span>
                        <?php else: ?>
                            <a class="page-link" href="<?php echo $getPaginationUrl($i); ?>"><?php echo $i; ?></a>
                        <?php endif; ?>
                    </li>
                <?php endfor;

                // Afficher la dernière page si nécessaire
                if ($end_page < $pagination['total_pages']): ?>
                    <?php if ($end_page < $pagination['total_pages'] - 1): ?>
                        <li class="page-item disabled">
                            <span class="page-link">...</span>
                        </li>
                    <?php endif; ?>
                    <li class="page-item">
                        <a class="page-link" href="<?php echo $getPaginationUrl((int) $pagination['total_pages']); ?>">
                            <?php echo $pagination['total_pages']; ?>
                        </a>
                    </li>
                <?php endif; ?>

                <!-- Bouton Suivant -->
                <?php if ($pagination['has_next']): ?>
                    <li class="page-item">
                        <a class="page-link" href="<?php echo $getPaginationUrl((int) $pagination['next_page']); ?>" title="Page suivante">
                            Suivant <i class="fas fa-chevron-right"></i>
                        </a>
                    </li>
                <?php else: ?>
                    <li class="page-item disabled">
                        <span class="page-link">
                            Suivant <i class="fas fa-chevron-right"></i>
                        </span>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    <?php endif; ?>
</div>

<style>
.pagination-nav {
    display: flex;
    justify-content: center;
}

.pagination {
    gap: 5px;
}

.page-item.active .page-link {
    background-color: #4CAF50;
    border-color: #4CAF50;
}

.page-link {
    color: #333;
    border: 1px solid #dee2e6;
    padding: 0.5rem 0.75rem;
    border-radius: 4px;
    transition: all 0.2s;
}

.page-link:hover:not(.page-item.disabled .page-link) {
    background-color: #f0f0f0;
    color: #4CAF50;
}

.page-item.disabled .page-link {
    color: #ccc;
    cursor: not-allowed;
    background-color: transparent;
}
</style>
