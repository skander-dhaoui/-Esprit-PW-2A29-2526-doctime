<?php
// VÃ©rification de l'authentification - Admin seulement
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: index.php?page=login');
    exit;
}

require_once __DIR__ . '/../../models/Review.php';

$reviewModel = new Review();

// RÃ©cupÃ©ration des paramÃ¨tres
$search = trim($_GET['search'] ?? '');
$filter = $_GET['filter'] ?? 'all'; // all, approved, pending, profanity
$sort = $_GET['sort'] ?? 'newest'; // newest, oldest, rating_high, rating_low
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 10;
$offset = ($page - 1) * $per_page;

// RequÃªte pour les avis
try {
    $db = $reviewModel->getConnection();
    
    // Construction de la requÃªte
    $where = [];
    $params = [];
    
    // Filtre
    if ($filter === 'approved') {
        $where[] = 'r.is_approved = 1';
    } elseif ($filter === 'pending') {
        $where[] = 'r.is_approved = 0';
    } elseif ($filter === 'profanity') {
        $where[] = 'r.has_profanity = 1';
    }
    
    // Recherche
    if (!empty($search)) {
        $where[] = '(r.content LIKE :search OR u.prenom LIKE :search OR u.nom LIKE :search OR u.email LIKE :search)';
        $params[':search'] = '%' . $search . '%';
    }
    
    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    
    // Tri
    $orderBy = match($sort) {
        'oldest' => 'ORDER BY r.created_at ASC',
        'rating_high' => 'ORDER BY r.rating DESC, r.created_at DESC',
        'rating_low' => 'ORDER BY r.rating ASC, r.created_at DESC',
        default => 'ORDER BY r.created_at DESC'
    };
    
    // Total des avis
    $countStmt = $db->prepare("
        SELECT COUNT(*) as total
        FROM reviews r
        JOIN users u ON r.user_id = u.id
        $whereClause
    ");
    $countStmt->execute($params);
    $countResult = $countStmt->fetch(PDO::FETCH_ASSOC);
    $total = $countResult['total'] ?? 0;
    $totalPages = max(1, ceil($total / $per_page));
    
    // Avis
    $stmt = $db->prepare("
        SELECT r.*, u.prenom, u.nom, u.email, u.role
        FROM reviews r
        JOIN users u ON r.user_id = u.id
        $whereClause
        $orderBy
        LIMIT :limit OFFSET :offset
    ");
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $avis = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Enrichir avec les emojis
    foreach ($avis as &$review) {
        $review['emojis'] = $reviewModel->getEmojis($review['id']);
    }
    
} catch (Exception $e) {
    error_log('Erreur avis_admin: ' . $e->getMessage());
    $avis = [];
    $total = 0;
    $totalPages = 1;
}

// Statistiques
try {
    $stats = $reviewModel->getStats();
} catch (Exception $e) {
    $stats = [];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Avis - Valorys</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #f0f2f5; font-family: 'Segoe UI', sans-serif; display: flex; min-height: 100vh; }

        /* Sidebar */
        .sidebar { width: 260px; min-height: 100vh; background: #1a2035; color: white; display: flex; flex-direction: column; position: fixed; top: 0; left: 0; z-index: 100; }
        .sidebar-brand { padding: 25px 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.08); }
        .brand-icon { width: 55px; height: 55px; background: rgba(255,255,255,0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 12px; font-size: 24px; color: #4CAF50; }
        .sidebar-brand h4 { font-size: 18px; font-weight: 700; margin: 0; color: white; }
        .sidebar-brand small { color: rgba(255,255,255,0.5); font-size: 11px; }
        .sidebar-nav { padding: 20px 0; flex: 1; }
        .sidebar-nav a { display: flex; align-items: center; gap: 12px; padding: 12px 22px; color: rgba(255,255,255,0.7); text-decoration: none; font-size: 14px; font-weight: 500; transition: all 0.2s; border-left: 3px solid transparent; }
        .sidebar-nav a:hover { background: rgba(255,255,255,0.07); color: white; }
        .sidebar-nav a.active { background: rgba(255,255,255,0.1); color: white; border-left-color: #4CAF50; }
        .sidebar-nav a i { width: 20px; text-align: center; font-size: 16px; }
        .nav-divider { height: 1px; background: rgba(255,255,255,0.07); margin: 10px 22px; }

        /* Main content */
        .main-content { margin-left: 260px; flex: 1; padding: 25px; min-height: 100vh; }
        .page-header { background: white; border-radius: 12px; padding: 18px 25px; margin-bottom: 25px; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 1px 6px rgba(0,0,0,0.06); }
        .page-header h4 { font-size: 18px; font-weight: 700; color: #1a2035; margin: 0; display: flex; align-items: center; gap: 10px; }
        .page-header h4 i { color: #4CAF50; }
        .admin-avatar { width: 40px; height: 40px; background: #4CAF50; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 16px; font-weight: bold; text-decoration: none; }
        .admin-avatar:hover { background: #2A7FAA; color: white; }

        .content-card { background: white; border-radius: 12px; padding: 25px; box-shadow: 0 1px 6px rgba(0,0,0,0.06); }
        .card-title-row { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; }
        .card-title-row h5 { font-size: 16px; font-weight: 600; color: #1a2035; margin: 0; }

        .stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 25px; }
        .stat-card { background: white; border-radius: 12px; padding: 20px; box-shadow: 0 1px 6px rgba(0,0,0,0.06); border-left: 4px solid; transition: transform 0.2s; }
        .stat-card:hover { transform: translateY(-2px); }
        .stat-card p { font-size: 13px; color: #5b6475; margin-bottom: 8px; text-transform: uppercase; font-weight:600;}
        .stat-card h3 { font-size: 28px; font-weight: 700; margin: 0 0 8px 0; color: #1a2035; }

        .badge-actif { background: #d4edda; color: #155724; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight:600;}
        .badge-inactif { background: #f8d7da; color: #721c24; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight:600;}
        .badge-validation { background: #fff3cd; color: #856404; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight:600;}

        .table thead th { background: #1a2035; color: white; font-weight: 600; font-size: 13px; padding: 12px 14px; border: none; }
        .table tbody td { vertical-align: middle; font-size: 14px; padding: 13px 14px; color: #333; border-bottom: 1px solid #eee; }
        .table tbody tr:hover { background: #f8f9ff; }
        .btn-sm { padding: 5px 10px; margin: 2px; }

        .filter-form { display: grid; grid-template-columns: 2fr 1fr 1fr auto auto; gap: 12px; margin-bottom: 20px; align-items: end; }
        .filter-form .form-label { font-size: 13px; font-weight: 600; color: #1a2035; margin-bottom: 6px; }
        @media (max-width: 992px) { .filter-form { grid-template-columns: 1fr; } }
        .pagination-bar { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-top: 20px; flex-wrap: wrap; }
        .pagination-info { font-size: 14px; color: #5b6475; }
        .pagination-links { display: flex; gap: 8px; flex-wrap: wrap; }
    </style>
    <link rel="stylesheet" href="assets/css/backoffice-polish.css">
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon"><i class="fas fa-stethoscope"></i></div>
        <h4>MediConnect</h4>
        <small>Back Office</small>
    </div>
    <nav class="sidebar-nav">
        <a href="index.php?page=dashboard"><i class="fas fa-th-large"></i> Tableau de bord</a>
        <a href="index.php?page=users"><i class="fas fa-users"></i> Utilisateurs</a>
        <a href="index.php?page=medecins_admin"><i class="fas fa-user-md"></i> Médecins</a>
        <a href="index.php?page=patients"><i class="fas fa-user-injured"></i> Patients</a>
        <a href="index.php?page=avis_admin" class="active"><i class="fas fa-star"></i> Avis</a>
        <a href="index.php?page=rendez_vous_admin"><i class="fas fa-calendar-check"></i> Rendez-vous</a>
        <a href="index.php?page=ordonnances"><i class="fas fa-prescription-bottle"></i> Ordonnances</a>
        <a href="index.php?page=produits_admin"><i class="fas fa-box"></i> Produits</a>
        <a href="index.php?page=articles_admin"><i class="fas fa-blog"></i> Blog</a>
        <a href="index.php?page=evenements_admin"><i class="fas fa-calendar-day"></i> Événements</a>
        <div class="nav-divider"></div>
        <a href="index.php?page=stats"><i class="fas fa-chart-line"></i> Statistiques</a>
        <a href="index.php?page=logs"><i class="fas fa-history"></i> Historique</a>
        <a href="index.php?page=settings"><i class="fas fa-cog"></i> Paramètres</a>
        <div class="nav-divider"></div>
        <a href="index.php?page=logout"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
    </nav>
</div>

<div class="main-content">
    <div class="page-header">
        <h4><i class="fas fa-star"></i> Gestion des Avis</h4>
        <a href="index.php?page=mon_profil" class="admin-avatar" title="Mon profil">
            <?= strtoupper(substr($_SESSION['user_name'] ?? 'A', 0, 1)) ?>
        </a>
    </div>
    
    <!-- Statistiques -->
    <div class="stats-row">
        <div class="stat-card" style="border-left-color: #4CAF50;">
            <p><i class="fas fa-check-circle me-2"></i>Avis approuvés</p>
            <h3 style="color: #4CAF50;"><?= count(array_filter($avis, fn($a) => $a['is_approved'])) ?></h3>
        </div>
        <div class="stat-card" style="border-left-color: #ffc107;">
            <p><i class="fas fa-clock me-2"></i>En attente</p>
            <h3 style="color: #ffc107;"><?= count(array_filter($avis, fn($a) => !$a['is_approved'])) ?></h3>
        </div>
        <div class="stat-card" style="border-left-color: #dc3545;">
            <p><i class="fas fa-exclamation-triangle me-2"></i>Avec profanité</p>
            <h3 style="color: #dc3545;"><?= count(array_filter($avis, fn($a) => $a['has_profanity'])) ?></h3>
        </div>
        <div class="stat-card" style="border-left-color: #2A7FAA;">
            <p><i class="fas fa-star-half-alt me-2"></i>Note moyenne</p>
            <h3 style="color: #2A7FAA;"><?= number_format($stats['average_rating'] ?? 0, 1) ?>/5 ⭐</h3>
        </div>
    </div>
    
    <div class="content-card">
        <div class="card-title-row">
            <h5><i class="fas fa-list"></i> Liste des avis (<?= $total ?>)</h5>
            <button id="addAvisBtn" class="btn btn-success btn-sm">
                <i class="fas fa-plus me-1"></i> Ajouter un avis
            </button>
        </div>

        <form method="GET" class="filter-form">
            <input type="hidden" name="page" value="avis_admin">
            <div>
                <label class="form-label" for="search">Rechercher</label>
                <input type="text" id="search" name="search" class="form-control" value="<?= htmlspecialchars($search) ?>" placeholder="Contenu, auteur, email...">
            </div>
            <div>
                <label class="form-label" for="filter">Statut</label>
                <select id="filter" name="filter" class="form-select">
                    <option value="all" <?= $filter === 'all' ? 'selected' : '' ?>>Tous les avis</option>
                    <option value="approved" <?= $filter === 'approved' ? 'selected' : '' ?>>Approuvés</option>
                    <option value="pending" <?= $filter === 'pending' ? 'selected' : '' ?>>En attente</option>
                    <option value="profanity" <?= $filter === 'profanity' ? 'selected' : '' ?>>Avec profanité</option>
                </select>
            </div>
            <div>
                <label class="form-label" for="sort">Tri</label>
                <select id="sort" name="sort" class="form-select">
                    <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Plus récents</option>
                    <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>Plus anciens</option>
                    <option value="rating_high" <?= $sort === 'rating_high' ? 'selected' : '' ?>>Notes hautes</option>
                    <option value="rating_low" <?= $sort === 'rating_low' ? 'selected' : '' ?>>Notes basses</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary" style="height: 38px;"><i class="fas fa-search"></i> Chercher</button>
            <a href="index.php?page=avis_admin" class="btn btn-outline-secondary" style="height: 38px; display:flex; align-items:center; justify-content:center;"><i class="fas fa-redo"></i></a>
        </form>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Auteur</th>
                        <th>Contenu</th>
                        <th>Note</th>
                        <th>Sentiment</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($avis)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-5"><i class="fas fa-inbox fa-2x mb-2 d-block opacity-25"></i>Aucun avis trouvé</td></tr>
                    <?php else: ?>
                        <?php foreach ($avis as $review): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($review['prenom'] . ' ' . $review['nom']) ?></strong><br>
                                <small class="text-muted"><?= htmlspecialchars($review['email']) ?></small><br>
                                <span class="badge bg-<?= $review['role'] === 'medecin' ? 'success' : 'info' ?> mt-1">
                                    <i class="fas fa-user-<?= $review['role'] === 'medecin' ? 'md' : 'injured' ?>"></i> <?= ucfirst($review['role']) ?>
                                </span>
                            </td>
                            <td style="max-width: 300px; white-space: normal; word-break: break-word;">
                                <?= substr(htmlspecialchars($review['content']), 0, 100) ?>...
                                <?php if (!empty($review['emojis'])): ?>
                                    <div class="mt-1">
                                        <?php foreach ($review['emojis'] as $emoji): ?>
                                            <span class="me-1"><?= $emoji ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="font-size: 14px; color: #ffc107;">
                                    <?php for($i=1; $i<=5; $i++): ?>
                                        <i class="<?= $i <= $review['rating'] ? 'fas' : 'far' ?> fa-star"></i>
                                    <?php endfor; ?>
                                </div>
                            </td>
                            <td>
                                <?php 
                                $sentimentStyles = [
                                    'positive' => ['class' => 'badge-actif', 'emoji' => '😊'],
                                    'negative' => ['class' => 'badge-inactif', 'emoji' => '😞'],
                                    'neutral' => ['class' => 'badge bg-secondary text-white', 'emoji' => '😐'],
                                ];
                                $sentiment = $review['sentiment'] ?? 'neutral';
                                $style = $sentimentStyles[$sentiment] ?? $sentimentStyles['neutral'];
                                ?>
                                <span class="<?= $style['class'] ?>">
                                    <?= $style['emoji'] ?> <?= ucfirst($sentiment) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($review['has_profanity']): ?>
                                    <span class="badge-inactif">⚠️ Profanité</span>
                                <?php elseif ($review['is_approved']): ?>
                                    <span class="badge-actif">✅ Approuvé</span>
                                <?php else: ?>
                                    <span class="badge-validation">⏳ En attente</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-info view-avis-btn text-white" data-id="<?= $review['id'] ?>" title="Voir">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-primary edit-avis-btn" data-id="<?= $review['id'] ?>" title="Éditer">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-danger delete-avis-btn" data-id="<?= $review['id'] ?>" title="Supprimer">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <?php if (!$review['is_approved'] && !$review['has_profanity']): ?>
                                    <button class="btn btn-sm btn-success approve-avis-btn" data-id="<?= $review['id'] ?>" title="Approuver">
                                        <i class="fas fa-check"></i>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($totalPages > 1): ?>
        <div class="pagination-bar">
            <div class="pagination-info">
                Affichage page <?= $page ?> sur <?= $totalPages ?>
            </div>
            <div class="pagination-links">
                <?php if ($page > 1): ?>
                    <a href="index.php?page=avis_admin&search=<?= urlencode($search) ?>&filter=<?= $filter ?>&sort=<?= $sort ?>&page=<?= $page - 1 ?>" class="btn btn-outline-primary btn-sm">Précédent</a>
                <?php endif; ?>
                
                <?php 
                $start = max(1, $page - 2);
                $end = min($totalPages, $page + 2);
                for ($i = $start; $i <= $end; $i++):
                ?>
                    <a href="index.php?page=avis_admin&search=<?= urlencode($search) ?>&filter=<?= $filter ?>&sort=<?= $sort ?>&page=<?= $i ?>" class="btn btn-sm <?= $i === $page ? 'btn-primary' : 'btn-outline-primary' ?>"><?= $i ?></a>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                    <a href="index.php?page=avis_admin&search=<?= urlencode($search) ?>&filter=<?= $filter ?>&sort=<?= $sort ?>&page=<?= $page + 1 ?>" class="btn btn-outline-primary btn-sm">Suivant</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
<!-- Modal pour ajouter/Ã©diter un avis -->
<div id="avisFormModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 12px; max-width: 600px; width: 90%; padding: 30px; box-shadow: 0 10px 40px rgba(0,0,0,0.15); animation: slideInUp 0.3s ease; max-height: 90vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2 id="formTitle" style="margin: 0; color: #333;">Ajouter un avis</h2>
            <button id="closeFormBtn" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #666;">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form id="avisForm">
            <input type="hidden" id="reviewId" name="id">
            
            <!-- Utilisateur -->
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin: 0 0 8px; color: #333; font-weight: 500;">Utilisateur *</label>
                <select id="userId" name="user_id" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
                    <option value="">Sélectionner un utilisateur...</option>
                    <?php 
                    try {
                        $db = $reviewModel->getConnection();
                        $userStmt = $db->query("SELECT id, CONCAT(prenom, ' ', nom) as name, email FROM users WHERE role IN ('patient', 'medecin') ORDER BY nom, prenom");
                        while ($user = $userStmt->fetch(PDO::FETCH_ASSOC)): ?>
                            <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['name']) ?> (<?= $user['email'] ?>)</option>
                        <?php endwhile;
                    } catch (Exception $e) {}
                    ?>
                </select>
            </div>
            
            <!-- Note -->
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin: 0 0 8px; color: #333; font-weight: 500;">Note *</label>
                <div style="display: flex; gap: 10px;">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <label style="cursor: pointer;">
                            <input type="radio" name="rating" value="<?= $i ?>" required style="margin-right: 5px;">
                            <span style="font-size: 24px;">â­</span>
                        </label>
                    <?php endfor; ?>
                </div>
            </div>
            
            <!-- Contenu -->
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin: 0 0 8px; color: #333; font-weight: 500;">Contenu (10-2000 caractères) *</label>
                <textarea id="content" name="content" required minlength="10" maxlength="2000" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; font-family: inherit; resize: vertical; min-height: 120px;"></textarea>
                <div style="font-size: 12px; color: #999; margin-top: 5px;">
                    <span id="charCount">0</span>/2000 caractères
                </div>
            </div>
            
            <!-- Approbation -->
            <div style="margin-bottom: 20px;">
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="checkbox" id="isApproved" name="is_approved" style="width: 16px; height: 16px;">
                    <span style="color: #333; font-weight: 500;">Approuver cet avis</span>
                </label>
            </div>
            
            <!-- Boutons -->
            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button type="button" id="cancelFormBtn" style="background: #6c757d; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-weight: 500;">
                    Annuler
                </button>
                <button type="submit" style="background: #4CAF50; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-weight: 500;">
                    <i class="fas fa-save"></i> Enregistrer
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal pour voir un avis complet -->
<div id="viewAvisModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 12px; max-width: 600px; width: 90%; padding: 30px; box-shadow: 0 10px 40px rgba(0,0,0,0.15); animation: slideInUp 0.3s ease; max-height: 90vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2 style="margin: 0; color: #333;">Détails de l'avis</h2>
            <button id="closeViewBtn" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #666;">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div id="viewContent">
            <!-- Contenu chargÃ© dynamiquement -->
        </div>
    </div>
</div>

<!-- Modal de suppression -->
<div id="deleteConfirmModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 12px; max-width: 400px; width: 90%; padding: 30px; box-shadow: 0 10px 40px rgba(0,0,0,0.15); animation: slideInUp 0.3s ease; text-align: center;">
        <i class="fas fa-exclamation-triangle" style="font-size: 48px; color: #f44336; margin-bottom: 15px; display: block;"></i>
        <h3 style="margin: 0 0 10px; color: #333;">Supprimer cet avis?</h3>
        <p style="margin: 0 0 20px; color: #666;">Cette action est irrÃ©versible.</p>
        
        <div style="display: flex; gap: 10px;">
            <button id="cancelDeleteBtn" style="flex: 1; padding: 10px 15px; background: #6c757d; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 500; transition: all 0.2s;">
                Annuler
            </button>
            <button id="confirmDeleteBtn" style="flex: 1; padding: 10px 15px; background: #f44336; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 500; transition: all 0.2s;">
                <i class="fas fa-trash"></i> Supprimer
            </button>
        </div>
    </div>
</div>

<style>
@keyframes slideInUp {
    from {
        transform: translateY(30px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

button:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

button:active {
    transform: translateY(0);
}

#avisFormModal > div, #viewAvisModal > div, #deleteConfirmModal > div {
    display: flex;
    flex-direction: column;
}

tr:hover {
    background: #f9f9f9;
}
</style>

<script>
// Modal pour ajouter un avis
document.getElementById('addAvisBtn').addEventListener('click', function() {
    document.getElementById('reviewId').value = '';
    document.getElementById('avisForm').reset();
    document.getElementById('formTitle').textContent = 'Ajouter un avis';
    document.getElementById('avisFormModal').style.display = 'flex';
    document.getElementById('charCount').textContent = '0';
});

// Fermer le modal du formulaire
document.getElementById('closeFormBtn').addEventListener('click', function() {
    document.getElementById('avisFormModal').style.display = 'none';
});

document.getElementById('cancelFormBtn').addEventListener('click', function() {
    document.getElementById('avisFormModal').style.display = 'none';
});

// Compteur de caractères
document.getElementById('content').addEventListener('input', function() {
    document.getElementById('charCount').textContent = this.value.length;
});

// Soumettre le formulaire
document.getElementById('avisForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const reviewId = document.getElementById('reviewId').value;
    const action = reviewId ? 'update' : 'create';
    
    const payload = {
        user_id: parseInt(document.getElementById('userId').value),
        rating: parseInt(document.querySelector('input[name="rating"]:checked').value),
        content: document.getElementById('content').value,
        is_approved: document.getElementById('isApproved').checked ? 1 : 0
    };
    
    if (reviewId) {
        payload.id = parseInt(reviewId);
    }
    
    try {
        const response = await fetch('/valorys_Copie/api/reviews.php?action=' + action, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert(action === 'create' ? 'Avis crÃ©Ã© avec succÃ¨s! âœ…' : 'Avis modifiÃ© avec succÃ¨s! âœï¸');
            document.getElementById('avisFormModal').style.display = 'none';
            location.reload();
        } else {
            alert('âŒ Erreur: ' + (data.message || 'Impossible de sauvegarder'));
        }
    } catch (error) {
        alert('âŒ Erreur: ' + error.message);
    }
});

// Voir un avis
document.querySelectorAll('.view-avis-btn').forEach(btn => {
    btn.addEventListener('click', async function() {
        const reviewId = this.getAttribute('data-id');
        
        try {
            const response = await fetch('/valorys_Copie/api/reviews.php?action=get&id=' + reviewId);
            const data = await response.json();
            
            if (data.success) {
                const review = data.review;
                let content = `
                    <div style="margin-bottom: 20px;">
                        <h4 style="margin: 0 0 10px; color: #333;">Auteur</h4>
                        <p style="margin: 0; color: #666;">${review.prenom} ${review.nom}</p>
                        <p style="margin: 5px 0 0; font-size: 13px; color: #999;">${review.email}</p>
                    </div>
                    
                    <div style="margin-bottom: 20px;">
                        <h4 style="margin: 0 0 10px; color: #333;">Note</h4>
                        <p style="margin: 0; font-size: 18px;">${'â­'.repeat(review.rating)}<span style="color: #ddd;">${'â­'.repeat(5 - review.rating)}</span></p>
                    </div>
                    
                    <div style="margin-bottom: 20px;">
                        <h4 style="margin: 0 0 10px; color: #333;">Sentiment</h4>
                        <p style="margin: 0; font-size: 13px; color: #666;">${review.sentiment ? review.sentiment.charAt(0).toUpperCase() + review.sentiment.slice(1) : 'Non analysÃ©'}</p>
                    </div>
                    
                    <div style="margin-bottom: 20px;">
                        <h4 style="margin: 0 0 10px; color: #333;">Contenu</h4>
                        <p style="margin: 0; color: #666; line-height: 1.6;">${review.content}</p>
                    </div>
                    
                    <div style="margin-bottom: 20px; padding-top: 20px; border-top: 1px solid #eee;">
                        <h4 style="margin: 0 0 10px; color: #333;">Statut</h4>
                        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                            ${review.is_approved ? '<span style="background: #4CAF50; color: white; padding: 6px 12px; border-radius: 4px; font-size: 12px;">âœ… ApprouvÃ©</span>' : '<span style="background: #ff9800; color: white; padding: 6px 12px; border-radius: 4px; font-size: 12px;">â³ En attente</span>'}
                            ${review.has_profanity ? '<span style="background: #f44336; color: white; padding: 6px 12px; border-radius: 4px; font-size: 12px;">âš ï¸ ProfanitÃ© dÃ©tectÃ©e</span>' : ''}
                        </div>
                    </div>
                    
                    <div style="margin-bottom: 20px; padding-top: 20px; border-top: 1px solid #eee;">
                        <h4 style="margin: 0 0 10px; color: #333;">Date</h4>
                        <p style="margin: 0; font-size: 13px; color: #666;">${new Date(review.created_at).toLocaleString('fr-FR')}</p>
                    </div>
                `;
                
                document.getElementById('viewContent').innerHTML = content;
                document.getElementById('viewAvisModal').style.display = 'flex';
            }
        } catch (error) {
            alert('âŒ Erreur: ' + error.message);
        }
    });
});

document.getElementById('closeViewBtn').addEventListener('click', function() {
    document.getElementById('viewAvisModal').style.display = 'none';
});

// Ã‰diter un avis
document.querySelectorAll('.edit-avis-btn').forEach(btn => {
    btn.addEventListener('click', async function() {
        const reviewId = this.getAttribute('data-id');
        
        try {
            const response = await fetch('/valorys_Copie/api/reviews.php?action=get&id=' + reviewId);
            const data = await response.json();
            
            if (data.success) {
                const review = data.review;
                document.getElementById('reviewId').value = review.id;
                document.getElementById('userId').value = review.user_id;
                document.getElementById('content').value = review.content;
                document.querySelector(`input[name="rating"][value="${review.rating}"]`).checked = true;
                document.getElementById('isApproved').checked = review.is_approved;
                document.getElementById('formTitle').textContent = 'Ã‰diter l\'avis';
                document.getElementById('charCount').textContent = review.content.length;
                document.getElementById('avisFormModal').style.display = 'flex';
            }
        } catch (error) {
            alert('âŒ Erreur: ' + error.message);
        }
    });
});

// Supprimer un avis
let pendingDeleteId = null;

document.querySelectorAll('.delete-avis-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        pendingDeleteId = this.getAttribute('data-id');
        document.getElementById('deleteConfirmModal').style.display = 'flex';
    });
});

document.getElementById('cancelDeleteBtn').addEventListener('click', function() {
    document.getElementById('deleteConfirmModal').style.display = 'none';
    pendingDeleteId = null;
});

document.getElementById('confirmDeleteBtn').addEventListener('click', async function() {
    if (!pendingDeleteId) return;
    
    try {
        const response = await fetch('/valorys_Copie/api/reviews.php?action=delete', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: parseInt(pendingDeleteId) })
        });
        
        const data = await response.json();
        
        if (data.success) {
            document.getElementById('deleteConfirmModal').style.display = 'none';
            alert('Avis supprimÃ© avec succÃ¨s! ðŸ—‘ï¸');
            location.reload();
        } else {
            alert('âŒ Erreur: ' + (data.message || 'Impossible de supprimer'));
        }
    } catch (error) {
        alert('âŒ Erreur: ' + error.message);
    }
});

// Approuver un avis
document.querySelectorAll('.approve-avis-btn').forEach(btn => {
    btn.addEventListener('click', async function() {
        const reviewId = this.getAttribute('data-id');
        
        try {
            const response = await fetch('/valorys_Copie/api/reviews.php?action=approve', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: parseInt(reviewId) })
            });
            
            const data = await response.json();
            
            if (data.success) {
                alert('Avis approuvÃ© avec succÃ¨s! âœ…');
                location.reload();
            } else {
                alert('âŒ Erreur: ' + (data.message || 'Impossible d\'approuver'));
            }
        } catch (error) {
            alert('âŒ Erreur: ' + error.message);
        }
    });
});

// Fermer les modals avec Echap
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.getElementById('avisFormModal').style.display = 'none';
        document.getElementById('viewAvisModal').style.display = 'none';
        document.getElementById('deleteConfirmModal').style.display = 'none';
        pendingDeleteId = null;
    }
});
</script>
    </div>
</body>
</html>
