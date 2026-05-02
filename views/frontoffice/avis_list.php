<?php
// views/frontoffice/avis_list.php - Page de gestion des avis pour patients/médecins

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php?page=login');
    exit;
}

require_once __DIR__ . '/../../models/Review.php';
require_once __DIR__ . '/../../models/User.php';

$Review = new Review();
$page = (int)($_GET['page'] ?? 1);
$search = trim($_GET['search'] ?? '');
$filter = $_GET['filter'] ?? 'my_reviews'; // my_reviews, all_approved, pending
$sort = $_GET['sort'] ?? 'newest';
$limit = 10;
$offset = ($page - 1) * $limit;

// Construire la requête de base
$query = "SELECT r.*, u.prenom, u.nom, u.email, u.role FROM avis r JOIN users u ON r.user_id = u.id";
$params = [];

// Appliquer le filtre
if ($filter === 'my_reviews') {
    $query .= " WHERE r.user_id = ?";
    $params[] = $_SESSION['user_id'];
} elseif ($filter === 'pending') {
    $query .= " WHERE r.user_id = ? AND r.is_approved = 0";
    $params[] = $_SESSION['user_id'];
} else { // all_approved
    $query .= " WHERE r.is_approved = 1";
}

// Appliquer la recherche
if (!empty($search)) {
    $query .= " AND (r.contenu LIKE ? OR u.prenom LIKE ? OR u.nom LIKE ? OR u.email LIKE ?)";
    $searchTerm = "%{$search}%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
}

// Appliquer le tri
if ($sort === 'oldest') {
    $query .= " ORDER BY r.created_at ASC";
} elseif ($sort === 'rating_high') {
    $query .= " ORDER BY r.rating DESC";
} elseif ($sort === 'rating_low') {
    $query .= " ORDER BY r.rating ASC";
} else { // newest
    $query .= " ORDER BY r.created_at DESC";
}

// Compter le total
$countQuery = "SELECT COUNT(*) as total FROM avis r JOIN users u ON r.user_id = u.id";
if ($filter === 'my_reviews') {
    $countQuery .= " WHERE r.user_id = ?";
} elseif ($filter === 'pending') {
    $countQuery .= " WHERE r.user_id = ? AND r.is_approved = 0";
} else {
    $countQuery .= " WHERE r.is_approved = 1";
}

$whereClause = ($filter === 'my_reviews' || $filter === 'pending') ? " AND " : " AND ";
if (!empty($search)) {
    $countQuery .= $whereClause . "(r.contenu LIKE ? OR u.prenom LIKE ? OR u.nom LIKE ? OR u.email LIKE ?)";
}

$db = $Review->getConnection();

// Compter total
$countParams = $filter === 'all_approved' ? [] : [$_SESSION['user_id']];
if (!empty($search)) {
    $searchTerm = "%{$search}%";
    $countParams = array_merge($countParams, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
}

$countStmt = $db->prepare($countQuery);
$countStmt->execute($countParams);
$totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
$totalPages = ceil($totalCount / $limit);

// Récupérer les avis avec pagination
$query .= " LIMIT ? OFFSET ?";
$stmt = $db->prepare($query);
$params[] = $limit;
$params[] = $offset;
$stmt->execute($params);
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ajouter les emojis aux sentiments
foreach ($reviews as &$review) {
    $review['emojis'] = $Review->getEmojis($review['id']);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Avis - MediConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .main-content {
            margin-left: 0;
            padding: 20px;
        }

        .review-card {
            background: white;
            border-left: 4px solid #4CAF50;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }

        .review-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .review-card.pending {
            border-left-color: #ff9800;
            background: #fff8f0;
        }

        .review-card.profanity {
            border-left-color: #f44336;
            background: #fff5f5;
        }

        .sentiment-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }

        .sentiment-positive {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .sentiment-negative {
            background: #fce4ec;
            color: #c2185b;
        }

        .sentiment-neutral {
            background: #f5f5f5;
            color: #616161;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }

        .status-approved {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .status-pending {
            background: #fff3e0;
            color: #f57f17;
        }

        .status-profanity {
            background: #ffebee;
            color: #c62828;
        }

        .stars {
            color: #ffc107;
            font-size: 14px;
        }

        .btn-action {
            padding: 5px 10px;
            font-size: 12px;
            margin-right: 5px;
        }

        .filter-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .stat-box {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }

        .pagination-links {
            display: flex;
            justify-content: center;
            gap: 5px;
            margin-top: 20px;
        }

        .pagination-links a, .pagination-links span {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-decoration: none;
            color: #4CAF50;
        }

        .pagination-links a:hover {
            background: #4CAF50;
            color: white;
        }

        .pagination-links span.active {
            background: #4CAF50;
            color: white;
            border-color: #4CAF50;
        }

        .pagination-links span.disabled {
            color: #999;
            border-color: #ddd;
            cursor: not-allowed;
        }

        .modal-content {
            border-radius: 8px;
        }

        .modal-header {
            background: #4CAF50;
            color: white;
            border-radius: 8px 8px 0 0;
        }

        .modal-header .btn-close {
            filter: brightness(0) invert(1);
        }

        .form-group label {
            color: #333;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .char-count {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }

        .emojis-display {
            margin-top: 10px;
            font-size: 18px;
            padding: 10px;
            background: #f5f5f5;
            border-radius: 4px;
            display: inline-block;
        }
    </style>
</head>
<body>
    <div class="main-content">
        <div class="container-lg">
            <!-- En-tête -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="fw-bold mb-1">
                        <i class="fas fa-star me-2" style="color: #ffc107;"></i>Mes Avis
                    </h1>
                    <p class="text-muted">Gérez vos avis et consultez ceux des autres utilisateurs</p>
                </div>
                <button class="btn btn-success btn-lg" data-bs-toggle="modal" data-bs-target="#addReviewModal">
                    <i class="fas fa-plus me-2"></i>Ajouter un avis
                </button>
            </div>

            <!-- Statistiques -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="stat-box">
                        <h4 style="color: #4CAF50;"><?php
                            // Compter les avis de l'utilisateur
                            $countMyQuery = "SELECT COUNT(*) as count FROM avis WHERE user_id = ?";
                            $countMyStmt = $db->prepare($countMyQuery);
                            $countMyStmt->execute([$_SESSION['user_id']]);
                            $myCount = $countMyStmt->fetch(PDO::FETCH_ASSOC)['count'];
                            echo $myCount;
                        ?></h4>
                        <p class="text-muted small">Mes avis</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-box">
                        <h4 style="color: #ffc107;"><i class="fas fa-star"></i> <?php
                            // Moyenne de mes avis
                            $avgMyQuery = "SELECT AVG(rating) as avg FROM avis WHERE user_id = ?";
                            $avgMyStmt = $db->prepare($avgMyQuery);
                            $avgMyStmt->execute([$_SESSION['user_id']]);
                            $myAvg = $avgMyStmt->fetch(PDO::FETCH_ASSOC)['avg'];
                            echo number_format($myAvg ?? 0, 1);
                        ?></h4>
                        <p class="text-muted small">Ma note moyenne</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-box">
                        <h4 style="color: #4CAF50;"><?php
                            // Mes avis approuvés
                            $countApprovedQuery = "SELECT COUNT(*) as count FROM avis WHERE user_id = ? AND is_approved = 1";
                            $countApprovedStmt = $db->prepare($countApprovedQuery);
                            $countApprovedStmt->execute([$_SESSION['user_id']]);
                            $approvedCount = $countApprovedStmt->fetch(PDO::FETCH_ASSOC)['count'];
                            echo $approvedCount;
                        ?></h4>
                        <p class="text-muted small">Approuvés</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-box">
                        <h4 style="color: #ff9800;"><?php
                            // Mes avis en attente
                            $countPendingQuery = "SELECT COUNT(*) as count FROM avis WHERE user_id = ? AND is_approved = 0";
                            $countPendingStmt = $db->prepare($countPendingQuery);
                            $countPendingStmt->execute([$_SESSION['user_id']]);
                            $pendingCount = $countPendingStmt->fetch(PDO::FETCH_ASSOC)['count'];
                            echo $pendingCount;
                        ?></h4>
                        <p class="text-muted small">En attente</p>
                    </div>
                </div>
            </div>

            <!-- Filtres et Recherche -->
            <div class="filter-section">
                <form method="GET" action="" class="row g-3">
                    <input type="hidden" name="page" value="avis">
                    
                    <div class="col-md-5">
                        <label class="form-label">Rechercher</label>
                        <input type="text" class="form-control" name="search" placeholder="Contenu, auteur, email..." 
                               value="<?= htmlspecialchars($search) ?>">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Filtre</label>
                        <select class="form-select" name="filter">
                            <option value="my_reviews" <?= $filter === 'my_reviews' ? 'selected' : '' ?>>Mes avis</option>
                            <option value="pending" <?= $filter === 'pending' ? 'selected' : '' ?>>En attente</option>
                            <option value="all_approved" <?= $filter === 'all_approved' ? 'selected' : '' ?>>Avis approuvés</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Tri</label>
                        <select class="form-select" name="sort">
                            <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Plus récents</option>
                            <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>Plus anciens</option>
                            <option value="rating_high" <?= $sort === 'rating_high' ? 'selected' : '' ?>>Notes hautes</option>
                            <option value="rating_low" <?= $sort === 'rating_low' ? 'selected' : '' ?>>Notes basses</option>
                        </select>
                    </div>

                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-success w-100">
                            <i class="fas fa-search me-2"></i>Chercher
                        </button>
                        <a href="?page=avis" class="btn btn-outline-secondary ms-2 w-100">
                            <i class="fas fa-redo me-2"></i>
                        </a>
                    </div>
                </form>
            </div>

            <!-- Liste des avis -->
            <?php if (!empty($reviews)): ?>
                <div class="reviews-list">
                    <?php foreach ($reviews as $review): ?>
                        <div class="review-card <?= $review['is_approved'] ? '' : 'pending' ?> <?= $review['has_profanity'] ? 'profanity' : '' ?>">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <h5 class="mb-1 fw-bold"><?= htmlspecialchars($review['prenom'] . ' ' . $review['nom']) ?></h5>
                                            <small class="text-muted"><?= htmlspecialchars($review['email']) ?></small>
                                        </div>
                                        <span class="stars">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="fas fa-star <?= $i <= $review['rating'] ? '' : 'fa-regular' ?>"></i>
                                            <?php endfor; ?>
                                        </span>
                                    </div>

                                    <p class="mb-2" style="color: #333;">
                                        <?= htmlspecialchars(strlen($review['contenu']) > 150 ? substr($review['contenu'], 0, 150) . '...' : $review['contenu']) ?>
                                    </p>

                                    <?php if (!empty($review['emojis'])): ?>
                                        <div class="emojis-display">
                                            <?php foreach ($review['emojis'] as $emoji): ?>
                                                <span class="me-2" title="<?= htmlspecialchars($emoji['signification']) ?>">
                                                    <?= htmlspecialchars($emoji['emoji']) ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="col-md-4 text-end">
                                    <div class="mb-2">
                                        <?php 
                                        $sentiment = $review['sentiment'] ?? 'neutral';
                                        $sentimentClass = "sentiment-" . strtolower($sentiment);
                                        $sentimentEmoji = [
                                            'positive' => '😊',
                                            'negative' => '😞',
                                            'neutral' => '😐'
                                        ][$sentiment] ?? '😐';
                                        ?>
                                        <span class="sentiment-badge <?= $sentimentClass ?>">
                                            <?= $sentimentEmoji ?> <?= ucfirst($sentiment) ?>
                                        </span>
                                    </div>

                                    <div class="mb-2">
                                        <?php if ($review['is_approved']): ?>
                                            <span class="status-badge status-approved">✅ Approuvé</span>
                                        <?php elseif ($review['has_profanity']): ?>
                                            <span class="status-badge status-profanity">⚠️ Profanité</span>
                                        <?php else: ?>
                                            <span class="status-badge status-pending">⏳ En attente</span>
                                        <?php endif; ?>
                                    </div>

                                    <small class="text-muted d-block mb-3">
                                        <?= date('d/m/Y H:i', strtotime($review['created_at'])) ?>
                                    </small>

                                    <?php if ($review['user_id'] == $_SESSION['user_id']): ?>
                                        <button class="btn btn-sm btn-primary btn-action" onclick="editReview(<?= $review['id'] ?>)">
                                            <i class="fas fa-edit me-1"></i>Éditer
                                        </button>
                                        <button class="btn btn-sm btn-danger btn-action" onclick="deleteReview(<?= $review['id'] ?>)">
                                            <i class="fas fa-trash me-1"></i>Supprimer
                                        </button>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-info btn-action" onclick="viewReview(<?= $review['id'] ?>)">
                                            <i class="fas fa-eye me-1"></i>Voir
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="pagination-links">
                        <?php if ($page > 1): ?>
                            <a href="?page=avis&search=<?= urlencode($search) ?>&filter=<?= $filter ?>&sort=<?= $sort ?>">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        <?php else: ?>
                            <span class="disabled"><i class="fas fa-chevron-left"></i></span>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <?php if ($i == $page): ?>
                                <span class="active"><?= $i ?></span>
                            <?php else: ?>
                                <a href="?page=avis&search=<?= urlencode($search) ?>&filter=<?= $filter ?>&sort=<?= $sort ?>&page=<?= $i ?>">
                                    <?= $i ?>
                                </a>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages): ?>
                            <a href="?page=avis&search=<?= urlencode($search) ?>&filter=<?= $filter ?>&sort=<?= $sort ?>&page=<?= $page + 1 ?>">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php else: ?>
                            <span class="disabled"><i class="fas fa-chevron-right"></i></span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="alert alert-info text-center py-4">
                    <i class="fas fa-inbox fa-2x mb-3 d-block" style="opacity: 0.5;"></i>
                    <p class="mb-0">Aucun avis trouvé. Soyez le premier à en partager un!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal Ajouter/Éditer un avis -->
    <div class="modal fade" id="addReviewModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ajouter un avis</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="reviewForm">
                        <div class="mb-3">
                            <label class="form-label">Note <span class="text-danger">*</span></label>
                            <div class="d-flex gap-2">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <label class="form-check">
                                        <input type="radio" name="rating" value="<?= $i ?>" class="form-check-input">
                                        <i class="fas fa-star" style="font-size: 24px; color: #ffc107; cursor: pointer;"></i>
                                    </label>
                                <?php endfor; ?>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Contenu <span class="text-danger">*</span> (10-2000 caractères)</label>
                            <textarea class="form-control" id="avisContenu" name="contenu" rows="5" 
                                      placeholder="Partagez votre expérience..." minlength="10" maxlength="2000"></textarea>
                            <div class="char-count">
                                <span id="charCount">0</span>/2000 caractères
                            </div>
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="agreeTos" name="agree_tos">
                            <label class="form-check-label" for="agreeTos">
                                J'accepte que mon avis soit publié et visible par les autres utilisateurs
                            </label>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-success" onclick="submitReview()">
                        <i class="fas fa-paper-plane me-2"></i>Publier
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Confirmation Suppression -->
    <div class="modal fade" id="deleteConfirmModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-body text-center py-4">
                    <i class="fas fa-exclamation-triangle fa-3x mb-3" style="color: #f44336;"></i>
                    <h5 class="fw-bold mb-2">Supprimer cet avis?</h5>
                    <p class="text-muted mb-4">Cette action est irréversible.</p>
                    <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-danger" onclick="confirmDelete()">
                        <i class="fas fa-trash me-2"></i>Supprimer
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let deleteReviewId = null;
        let editReviewId = null;

        // Compter les caractères
        document.getElementById('avisContenu').addEventListener('input', function() {
            document.getElementById('charCount').textContent = this.value.length;
        });

        // Sélection des étoiles
        document.querySelectorAll('input[name="rating"]').forEach((radio, index) => {
            radio.addEventListener('change', function() {
                document.querySelectorAll('input[name="rating"]').forEach((r, i) => {
                    const icon = r.parentElement.querySelector('i');
                    if (i < index + 1) {
                        icon.classList.remove('fa-regular');
                    } else {
                        icon.classList.add('fa-regular');
                    }
                });
            });
        });

        function submitReview() {
            const form = document.getElementById('reviewForm');
            const rating = document.querySelector('input[name="rating"]:checked');
            const contenu = document.getElementById('avisContenu').value;
            const agreeTos = document.getElementById('agreeTos').checked;

            if (!rating) {
                alert('Veuillez sélectionner une note');
                return;
            }
            if (contenu.length < 10) {
                alert('Votre avis doit faire au moins 10 caractères');
                return;
            }
            if (!agreeTos) {
                alert('Vous devez accepter les conditions pour publier');
                return;
            }

            const formData = {
                rating: rating.value,
                contenu: contenu,
                action: editReviewId ? 'update' : 'store'
            };

            if (editReviewId) {
                formData.id = editReviewId;
            }

            fetch('api/reviews.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(formData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(editReviewId ? 'Avis modifié avec succès! ✏️' : 'Avis publié avec succès! ✅');
                    location.reload();
                } else {
                    alert('Erreur: ' + (data.message || 'Une erreur est survenue'));
                }
            })
            .catch(error => {
                alert('Erreur: ' + error.message);
            });
        }

        function editReview(reviewId) {
            // Récupérer les données et pré-remplir le formulaire
            fetch(`api/reviews.php?action=get&id=${reviewId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const review = data.data;
                    editReviewId = reviewId;
                    document.querySelector(`input[name="rating"][value="${review.rating}"]`).checked = true;
                    document.getElementById('avisContenu').value = review.contenu;
                    document.getElementById('charCount').textContent = review.contenu.length;
                    document.querySelector('.modal-title').textContent = 'Éditer l\'avis';
                    new bootstrap.Modal(document.getElementById('addReviewModal')).show();
                }
            });
        }

        function deleteReview(reviewId) {
            deleteReviewId = reviewId;
            new bootstrap.Modal(document.getElementById('deleteConfirmModal')).show();
        }

        function confirmDelete() {
            fetch('api/reviews.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'delete',
                    id: deleteReviewId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Avis supprimé avec succès! 🗑️');
                    location.reload();
                } else {
                    alert('Erreur: ' + (data.message || 'Une erreur est survenue'));
                }
            })
            .catch(error => {
                alert('Erreur: ' + error.message);
            });
        }

        function viewReview(reviewId) {
            // Afficher les détails complets
            alert('Fonction de visualisation à implémenter');
        }
    </script>
</body>
</html>
