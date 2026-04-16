<?php

class FrontController {
    
    /**
     * Vérifier si l'utilisateur est connecté
     * Rediriger vers login si pas connecté
     */
    private function requireLogin(): void {
        if (empty($_SESSION['user_id'])) {
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
            $_SESSION['error'] = 'Veuillez vous connecter pour accéder à cette page.';
            header('Location: index.php?page=login');
            exit;
        }
    }
    
    /**
     * Vérifier si l'utilisateur est admin
     */
    private function requireAdmin(): void {
        $this->requireLogin();
        if (($_SESSION['user_role'] ?? '') !== 'admin') {
            $this->page403();
            exit;
        }
    }
    
    // =============================================
    // PAGES PUBLIQUES
    // =============================================
    
    /**
     * Page d'accueil publique
     */
    public function accueil(): void {
        if (file_exists(__DIR__ . '/../index.html')) {
            readfile(__DIR__ . '/../index.html');
        } else {
            $this->accueilPublic();
        }
    }
    
    /**
     * Page d'accueil publique avec design
     */
    public function accueilPublic(): void {
        $content = $this->getPublicDashboardHTML();
        $this->renderPublicView('Accueil', $content);
    }
    
    /**
     * Page d'accès refusé
     */
    public function showAccessDenied(): void {
        $content = '
        <div class="text-center py-5">
            <div class="alert alert-warning py-4">
                <i class="fas fa-exclamation-triangle fa-4x mb-3 d-block text-warning"></i>
                <h2>Accès refusé</h2>
                <p class="lead">Veuillez vous connecter pour accéder à cette page.</p>
                <hr>
                <div class="mt-4">
                    <a href="index.php?page=login" class="btn btn-primary btn-lg mx-2">
                        <i class="fas fa-sign-in-alt"></i> Se connecter
                    </a>
                    <a href="index.php?page=register" class="btn btn-success btn-lg mx-2">
                        <i class="fas fa-user-plus"></i> S\'inscrire
                    </a>
                </div>
            </div>
        </div>';
        
        $this->renderPublicView('Accès refusé', $content);
    }
    
    /**
     * Liste des médecins (publique)
     */
    public function listeMedecins(): void {
        require_once __DIR__ . '/../models/Medecin.php';
        $medecinModel = new Medecin();
        $medecins = $medecinModel->getAllWithUsers();
        
        if (empty($medecins)) {
            $content = '<div class="alert alert-info">Aucun médecin disponible pour le moment.</div>';
        } else {
            $content = '
            <div class="table-responsive">
                <table class="table table-striped table-hover" id="medecinsTable">
                    <thead class="table-primary">
                        <tr>
                            <th>ID</th>
                            <th>Nom complet</th>
                            <th>Email</th>
                            <th>Spécialité</th>
                            <th>Téléphone</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>';
            
            foreach ($medecins as $medecin) {
                $userId = $medecin['user_id'] ?? $medecin['id'] ?? 0;
                $content .= '
                    <tr>
                        <td>' . htmlspecialchars($userId) . '</td>
                        <td>Dr. ' . htmlspecialchars($medecin['prenom'] . ' ' . $medecin['nom']) . '</td>
                        <td>' . htmlspecialchars($medecin['email']) . '</td>
                        <td>' . htmlspecialchars($medecin['specialite']) . '</td>
                        <td>' . htmlspecialchars($medecin['telephone'] ?? 'Non renseigné') . '</td>
                        <td>
                            <a href="index.php?page=detail_medecin&id=' . $userId . '" class="btn btn-sm btn-primary">
                                <i class="fas fa-eye"></i> Voir
                            </a>
                         </div>
                        </td>
                    </tr>';
            }
            
            $content .= '
                    </tbody>
                </table>
            </div>';
        }
        
        $this->renderTemporaryView('Nos Médecins', $content);
    }
    
    /**
     * Détail d'un médecin (publique)
     */
    public function detailMedecin($id): void {
        require_once __DIR__ . '/../models/Medecin.php';
        $medecinModel = new Medecin();
        $medecin = $medecinModel->findByUserId($id);
        
        if (!$medecin) {
            $this->page404();
            return;
        }
        
        $content = '
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0">Dr. ' . htmlspecialchars($medecin['prenom'] . ' ' . $medecin['nom']) . '</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong><i class="fas fa-stethoscope"></i> Spécialité:</strong> ' . htmlspecialchars($medecin['specialite']) . '</p>
                            <p><strong><i class="fas fa-envelope"></i> Email:</strong> ' . htmlspecialchars($medecin['email']) . '</p>
                            <p><strong><i class="fas fa-phone"></i> Téléphone:</strong> ' . htmlspecialchars($medecin['telephone'] ?? 'Non renseigné') . '</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong><i class="fas fa-map-marker-alt"></i> Adresse cabinet:</strong> ' . htmlspecialchars($medecin['cabinet_adresse'] ?? 'Non renseignée') . '</p>
                            <p><strong><i class="fas fa-euro-sign"></i> Tarif:</strong> ' . ($medecin['consultation_prix'] ?? '50') . ' €</p>
                        </div>
                    </div>
                    <hr>
                    <div class="text-center">
                        <a href="index.php?page=prendre_rendez_vous&id=' . $id . '" class="btn btn-success btn-lg">
                            <i class="fas fa-calendar-check"></i> Prendre rendez-vous
                        </a>
                        <a href="index.php?page=medecins" class="btn btn-secondary btn-lg">
                            <i class="fas fa-arrow-left"></i> Retour à la liste
                        </a>
                    </div>
                </div>
            </div>
        ';
        
        $this->renderTemporaryView('Détail du médecin', $content);
    }
    
    // =============================================
    // BLOG - PAGES PUBLIQUES
    // =============================================
    
/**
 * Liste des articles du blog (publique) avec boutons CRUD pour admin
 */
/**
 * Liste des articles du blog (publique) avec boutons CRUD pour admin
 */
/**
 * Liste des articles du blog (publique) avec boutons CRUD pour admin
 */
/**
 * Liste des articles du blog (publique) avec boutons CRUD pour les auteurs connectés
 */
public function blogList(): void {
    try {
        require_once __DIR__ . '/../models/Article.php';
        
        if (!class_exists('Article')) {
            throw new Exception("La classe Article n'existe pas");
        }
        
        $articleModel = new Article();
        $articles = $articleModel->getAll();
        
        // Vérifier si l'utilisateur est connecté et son rôle
        $isLoggedIn = isset($_SESSION['user_id']);
        $userId = $_SESSION['user_id'] ?? null;
        $userRole = $_SESSION['user_role'] ?? '';
        $isAdmin = ($userRole === 'admin');
        
        // =============================================
        // INTERFACE BACKOFFICE (pour ADMIN) - avec sidebar
        // =============================================
        if ($isAdmin) {
            // Cartes de statistiques
            $totalArticles = count($articles);
            $totalVues = array_sum(array_column($articles, 'vues'));
            $totalComments = array_sum(array_column($articles, 'nb_replies'));
            
            $statsHtml = '
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card card-stats bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title">Total articles</h6>
                                    <h2 class="mb-0">' . $totalArticles . '</h2>
                                </div>
                                <i class="fas fa-newspaper fa-3x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card card-stats bg-success text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title">Total vues</h6>
                                    <h2 class="mb-0">' . $totalVues . '</h2>
                                </div>
                                <i class="fas fa-eye fa-3x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card card-stats bg-info text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title">Total commentaires</h6>
                                    <h2 class="mb-0">' . $totalComments . '</h2>
                                </div>
                                <i class="fas fa-comments fa-3x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>';
            
            // Bouton Ajouter
            $addButton = '
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0"><i class="fas fa-newspaper me-2"></i>Liste des articles</h4>
                <a href="index.php?page=articles_admin&action=create" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i> Nouvel article
                </a>
            </div>';
            
            if (empty($articles)) {
                $content = '<div class="alert alert-info">Aucun article disponible pour le moment.</div>';
            } else {
                $content = '
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-primary">
                            <tr>
                                <th>ID</th>
                                <th>Titre</th>
                                <th>Auteur</th>
                                <th>Date</th>
                                <th>Vues</th>
                                <th>Commentaires</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>';
                
                foreach ($articles as $article) {
                    $content .= '
                    <tr>
                        <td>' . $article['id'] . '</td>
                        <td><strong>' . htmlspecialchars(substr($article['titre'], 0, 50)) . (strlen($article['titre']) > 50 ? '...' : '') . '</strong></td>
                        <td>' . htmlspecialchars($article['auteur_name'] ?? 'Valorys') . '</td>
                        <td>' . date('d/m/Y H:i', strtotime($article['created_at'])) . '</td>
                        <td><span class="badge bg-info">' . ($article['vues'] ?? 0) . '</span></td>
                        <td><span class="badge bg-secondary">' . ($article['nb_replies'] ?? 0) . '</span></td>
                        <td>
                            <div class="btn-group" role="group">
                                <a href="index.php?page=detail_article_public&id=' . $article['id'] . '" class="btn btn-sm btn-info btn-action" title="Voir">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="index.php?page=articles_admin&action=edit&id=' . $article['id'] . '" class="btn btn-sm btn-warning btn-action" title="Modifier">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button type="button" class="btn btn-sm btn-danger btn-action" title="Supprimer" onclick="confirmDeleteArticle(' . $article['id'] . ', \'' . addslashes($article['titre']) . '\')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                         </div>
                        </td>
                    </tr>';
                }
                
                $content .= '
                        </tbody>
                    </table>
                </div>';
            }
            
            $fullContent = $statsHtml . $addButton . $content . $this->getDeleteScript();
            $this->renderAdminLayout('Gestion des articles', $fullContent, 'articles');
            return;
        }
        
        // =============================================
        // INTERFACE FRONT OFFICE (pour patients, médecins et non connectés)
        // =============================================
        
        // Bouton Ajouter (visible pour tous les utilisateurs connectés)
        $addButton = '';
        if ($isLoggedIn) {
            $addButton = '
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2><i class="fas fa-newspaper"></i> Nos articles</h2>
                <a href="index.php?page=articles_admin&action=create" class="btn btn-success" style="background: #28a745; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none;">
                    <i class="fas fa-plus"></i> Nouvel article
                </a>
            </div>';
        } else {
            $addButton = '<h2 class="mb-4"><i class="fas fa-newspaper"></i> Nos articles</h2>';
        }
        
        if (empty($articles)) {
            $content = '<div class="alert alert-info">Aucun article disponible pour le moment.</div>';
        } else {
            $content = '<div style="display: flex; flex-wrap: wrap; gap: 20px; margin: 0 -10px;">';
            
            foreach ($articles as $article) {
                $canEdit = false;
                $canDelete = false;
                
                if ($isLoggedIn && isset($article['auteur_id']) && $userId == $article['auteur_id']) {
                    $canEdit = true;
                    $canDelete = true;
                }
                
                $crudButtons = '';
                if ($canEdit || $canDelete) {
                    $crudButtons = '
                    <div style="position: absolute; top: 15px; right: 15px; display: flex; gap: 8px; z-index: 100;">
                        ' . ($canEdit ? '<a href="index.php?page=articles_admin&action=edit&id=' . $article['id'] . '" style="background: #ffc107; color: #000; padding: 6px 12px; border-radius: 5px; text-decoration: none; font-size: 12px;"><i class="fas fa-edit"></i> Modifier</a>' : '') . '
                        ' . ($canDelete ? '<button type="button" onclick="confirmDeleteArticle(' . $article['id'] . ', \'' . addslashes($article['titre']) . '\')" style="background: #dc3545; color: #fff; border: none; padding: 6px 12px; border-radius: 5px; cursor: pointer; font-size: 12px;"><i class="fas fa-trash"></i> Supprimer</button>' : '') . '
                    </div>';
                }
                
                $articleImage = !empty($article['image']) ? '<img src="' . htmlspecialchars($article['image']) . '" style="width: 100%; height: 180px; object-fit: cover; border-radius: 8px; margin-bottom: 15px;">' : '';
                
                $content .= '
                <div style="flex: 0 0 calc(50% - 20px); min-width: 280px; border: 1px solid #ddd; border-radius: 8px; padding: 20px; margin-bottom: 20px; background: white; position: relative;">
                    ' . $crudButtons . '
                    ' . $articleImage . '
                    <h3 style="margin-top: 0; padding-right: 150px;">' . htmlspecialchars($article['titre']) . '</h3>
                    <div style="color: #666; font-size: 13px; margin-bottom: 15px;">
                        <span><i class="fas fa-user"></i> ' . htmlspecialchars($article['auteur_name'] ?? 'Valorys') . '</span>
                        <span style="margin-left: 15px;"><i class="fas fa-calendar"></i> ' . date('d/m/Y', strtotime($article['created_at'])) . '</span>
                        <span style="margin-left: 15px;"><i class="fas fa-eye"></i> ' . ($article['vues'] ?? 0) . ' vues</span>
                        <span style="margin-left: 15px;"><i class="fas fa-comment"></i> ' . ($article['nb_replies'] ?? 0) . ' commentaire(s)</span>
                    </div>
                    <p>' . htmlspecialchars(substr(strip_tags($article['contenu']), 0, 150)) . '...</p>
                    <a href="index.php?page=detail_article_public&id=' . $article['id'] . '" style="display: inline-block; background: #2A7FAA; color: white; padding: 8px 20px; border-radius: 5px; text-decoration: none;">Lire la suite →</a>
                </div>';
            }
            $content .= '</div>';
        }
        
        if (!$isLoggedIn) {
            $infoMessage = '
            <div style="background: #e3f2fd; border-left: 4px solid #2196f3; padding: 12px 20px; margin-bottom: 20px; border-radius: 5px;">
                <i class="fas fa-info-circle"></i> 
                <a href="index.php?page=login" style="color: #1976d2;">Connectez-vous</a> pour créer, modifier ou supprimer vos propres articles.
            </div>';
            $fullContent = $infoMessage . $addButton . $content . $this->getDeleteScript();
        } else {
            $fullContent = $addButton . $content . $this->getDeleteScript();
        }
        
        $this->renderPublicView('Blog Valorys', $fullContent);
        
    } catch (Exception $e) {
        error_log('Erreur blogList: ' . $e->getMessage());
        $content = '<div class="alert alert-danger">Erreur: ' . htmlspecialchars($e->getMessage()) . '</div>';
        $this->renderPublicView('Blog Valorys', $content);
    }
}


/**
 * Rendu des vues admin avec sidebar (comme page users)
 */
private function renderAdminLayout($title, $content, $activePage = 'articles'): void {
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= htmlspecialchars($title) ?> - Valorys Admin</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <style>
            body {
                background: #f4f6f9;
                font-family: 'Segoe UI', sans-serif;
            }
            .sidebar {
                background: #2c3e50;
                min-height: 100vh;
                color: white;
                box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            }
            .sidebar .nav-link {
                color: rgba(255,255,255,0.8);
                padding: 12px 20px;
                transition: all 0.3s;
                border-radius: 8px;
                margin: 4px 10px;
            }
            .sidebar .nav-link:hover {
                background: rgba(255,255,255,0.1);
                color: white;
            }
            .sidebar .nav-link.active {
                background: #2A7FAA;
                color: white;
            }
            .sidebar .nav-link i {
                margin-right: 10px;
                width: 20px;
                text-align: center;
            }
            .sidebar .navbar-brand {
                padding: 20px 15px;
                font-size: 1.3rem;
                font-weight: bold;
                border-bottom: 1px solid rgba(255,255,255,0.1);
                margin-bottom: 15px;
            }
            .main-content {
                padding: 20px;
            }
            .top-bar {
                background: white;
                border-radius: 10px;
                padding: 15px 20px;
                margin-bottom: 20px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            }
            .card-stats {
                border: none;
                border-radius: 10px;
                transition: transform 0.2s;
            }
            .card-stats:hover {
                transform: translateY(-5px);
            }
            .btn-action {
                margin: 0 2px;
            }
            @media (max-width: 768px) {
                .sidebar {
                    min-height: auto;
                }
            }
        </style>
    </head>
    <body>
        <div class="container-fluid">
            <div class="row">
                <!-- Sidebar -->
                <div class="col-md-2 col-lg-2 px-0 sidebar">
                    <div class="navbar-brand text-center">
                        <i class="fas fa-hospital-user me-2"></i> Valorys Admin
                    </div>
                    <nav class="nav flex-column">
                        <a class="nav-link <?= $activePage == 'dashboard' ? 'active' : '' ?>" href="index.php?page=dashboard">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                        <a class="nav-link <?= $activePage == 'users' ? 'active' : '' ?>" href="index.php?page=users">
                            <i class="fas fa-users"></i> Utilisateurs
                        </a>
                        <a class="nav-link <?= $activePage == 'patients' ? 'active' : '' ?>" href="index.php?page=patients">
                            <i class="fas fa-user-injured"></i> Patients
                        </a>
                        <a class="nav-link <?= $activePage == 'medecins' ? 'active' : '' ?>" href="index.php?page=medecins_admin">
                            <i class="fas fa-user-md"></i> Médecins
                        </a>
                        <a class="nav-link <?= $activePage == 'articles' ? 'active' : '' ?>" href="index.php?page=blog_public">
                            <i class="fas fa-newspaper"></i> Articles
                        </a>
                        <a class="nav-link <?= $activePage == 'rendezvous' ? 'active' : '' ?>" href="index.php?page=rendez_vous_admin">
                            <i class="fas fa-calendar-check"></i> Rendez-vous
                        </a>
                        <a class="nav-link <?= $activePage == 'evenements' ? 'active' : '' ?>" href="index.php?page=evenements_admin">
                            <i class="fas fa-calendar-alt"></i> Événements
                        </a>
                        <a class="nav-link <?= $activePage == 'produits' ? 'active' : '' ?>" href="index.php?page=produits_admin">
                            <i class="fas fa-box"></i> Produits
                        </a>
                        <a class="nav-link <?= $activePage == 'stats' ? 'active' : '' ?>" href="index.php?page=stats">
                            <i class="fas fa-chart-line"></i> Statistiques
                        </a>
                        <a class="nav-link <?= $activePage == 'settings' ? 'active' : '' ?>" href="index.php?page=settings">
                            <i class="fas fa-cog"></i> Paramètres
                        </a>
                        <hr class="mx-3 my-2" style="border-color: rgba(255,255,255,0.1);">
                        <a class="nav-link" href="index.php?page=accueil">
                            <i class="fas fa-home"></i> Voir le site
                        </a>
                        <a class="nav-link text-danger" href="index.php?page=logout">
                            <i class="fas fa-sign-out-alt"></i> Déconnexion
                        </a>
                    </nav>
                </div>
                
                <!-- Main Content -->
                <div class="col-md-10 col-lg-10 main-content">
                    <div class="top-bar d-flex justify-content-between align-items-center">
                        <h4 class="mb-0"><?= htmlspecialchars($title) ?></h4>
                        <div class="d-flex align-items-center">
                            <span class="me-3">
                                <i class="fas fa-user-circle"></i> <?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin') ?>
                            </span>
                            <span class="badge bg-primary"><?= ucfirst($_SESSION['user_role'] ?? 'admin') ?></span>
                        </div>
                    </div>
                    
                    <?= $this->getFlashMessages() ?>
                    <?= $content ?>
                </div>
            </div>
        </div>
        
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </body>
    </html>
    <?php
}


/**
 * Rendu des vues admin (backoffice)
 */
private function renderAdminView($title, $content): void {
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= htmlspecialchars($title) ?> - Valorys Admin</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <?= $this->getCustomStyles() ?>
    </head>
    <body>
        <!-- Navbar admin (bleue/différente) -->
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
            <div class="container">
                <a class="navbar-brand fw-bold" href="index.php?page=accueil">
                    <i class="fas fa-hospital-user"></i> Valorys Admin
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item"><a class="nav-link" href="index.php?page=dashboard"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link active" href="index.php?page=blog_public"><i class="fas fa-blog"></i> Articles</a></li>
                        <li class="nav-item"><a class="nav-link" href="index.php?page=logout"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
                    </ul>
                </div>
            </div>
        </nav>

        <div class="container mt-4">
            <?= $this->getFlashMessages() ?>
            <div class="row">
                <div class="col-12">
                    <div class="card shadow">
                        <div class="card-header bg-white">
                            <h3 class="mb-0"><?= htmlspecialchars($title) ?></h3>
                        </div>
                        <div class="card-body">
                            <?= $content ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?= $this->getFooter() ?>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </body>
    </html>
    <?php
}

/**
 * Détail d'un article (publique) avec boutons CRUD pour admin
 */
/**
 * Détail d'un article avec système de commentaires complet (CRUD)
 */
/**
 * Détail d'un article avec système de commentaires complet (CRUD)
 */
public function blogDetail($id): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_reply'])) {
        $this->addReply($id);
        return;
    }

    require_once __DIR__ . '/../models/Article.php';
    require_once __DIR__ . '/../models/Reply.php';

    $articleModel = new Article();
    $replyModel   = new Reply();

    $articleModel->incrementViews($id);
    $article = $articleModel->getById($id);

    if (!$article) {
        $this->page404();
        return;
    }

    $replies     = $replyModel->getByArticle($id);
    $isLoggedIn  = isset($_SESSION['user_id']);
    $userId      = $_SESSION['user_id'] ?? null;
    $userRole    = $_SESSION['user_role'] ?? '';
    $isAdmin     = ($userRole === 'admin');

    // =============================================
    // INTERFACE ADMIN (BACKOFFICE)
    // =============================================
    if ($isAdmin) {
        $content = $this->getAdminArticleDetailHTML($article, $replies, $id);
        $this->renderAdminLayout('Détail de l\'article - Administration', $content, 'articles');
        return;
    }

    // =============================================
    // INTERFACE FRONT OFFICE (Patients, Médecins, Non connectés)
    // =============================================
    
    $isAuthor = ($isLoggedIn && isset($article['auteur_id']) && $userId == $article['auteur_id']);
    
    // Boutons CRUD article (seulement si auteur)
    $articleButtons = '';
    if ($isAuthor) {
        $articleButtons = '
        <div class="mb-3 d-flex gap-2">
            <a href="index.php?page=articles_admin&action=edit&id=' . $id . '" class="btn btn-warning btn-sm">
                <i class="fas fa-edit"></i> Modifier mon article
            </a>
            <button type="button" class="btn btn-danger btn-sm" onclick="confirmDeleteArticle(' . $id . ', \'' . addslashes($article['titre']) . '\')">
                <i class="fas fa-trash"></i> Supprimer mon article
            </button>
            <a href="index.php?page=blog_public" class="btn btn-secondary btn-sm ms-auto">
                <i class="fas fa-arrow-left"></i> Retour au blog
            </a>
        </div>';
    } else {
        $articleButtons = '
        <div class="mb-3">
            <a href="index.php?page=blog_public" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Retour au blog
            </a>
        </div>';
    }

    // Affichage de l'image si présente
    $articleImage = '';
    if (!empty($article['image'])) {
        $articleImage = '<img src="' . htmlspecialchars($article['image']) . '" style="width: 100%; max-height: 400px; object-fit: cover; border-radius: 8px; margin-bottom: 20px;">';
    }

    // ── Affichage article FRONT ─────────────────────────────────────────
    $content = '
    <style>
        .reply-item        { border-bottom: 1px solid #eee; padding: 15px 0; display: flex; gap: 15px; }
        .reply-avatar      { width: 45px; height: 45px; border-radius: 50%; background: linear-gradient(135deg, #2A7FAA, #4CAF50);
                             display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; flex-shrink: 0; }
        .reply-content     { flex: 1; }
        .reply-author      { font-weight: bold; color: #333; }
        .reply-date        { font-size: 11px; color: #999; margin-bottom: 8px; }
        .reply-text        { color: #555; line-height: 1.6; margin-bottom: 8px; }
        .reply-emoji       { font-size: 2rem; margin-bottom: 8px; }
        .reply-photo       { max-width: 100%; border-radius: 8px; margin-top: 5px; max-height: 200px; }
        .reply-actions     { margin-top: 10px; display: flex; gap: 10px; }
        .btn-edit-reply    { background: #ffc107; color: #000; border: none; padding: 4px 10px; border-radius: 4px; cursor: pointer; font-size: 11px; }
        .btn-delete-reply  { background: #dc3545; color: #fff; border: none; padding: 4px 10px; border-radius: 4px; cursor: pointer; font-size: 11px; }
        .modal             { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%;
                             background-color: rgba(0,0,0,0.5); }
        .modal-content     { background-color: #fff; margin: 10% auto; padding: 20px; border-radius: 10px;
                             width: 90%; max-width: 500px; position: relative; }
        .close             { position: absolute; right: 15px; top: 10px; font-size: 25px; cursor: pointer; }
        .form-group        { margin-bottom: 15px; }
        .form-group label  { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input,
        .form-group textarea,
        .form-group select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; }
        .btn-submit        { background: linear-gradient(135deg, #2A7FAA, #4CAF50); color: white; border: none;
                             padding: 10px 25px; border-radius: 25px; cursor: pointer; }
    </style>

    <div style="background:white; border-radius:12px; padding:30px; margin-bottom:30px; box-shadow:0 2px 8px rgba(0,0,0,0.1);">
        ' . $articleButtons . '
        ' . $articleImage . '
        <h1 style="font-size:2rem; margin-bottom:15px;">' . htmlspecialchars($article['titre']) . '</h1>
        <div style="color:#666; font-size:14px; margin-bottom:20px; padding-bottom:15px; border-bottom:1px solid #eee;">
            <span><i class="fas fa-user"></i> '    . htmlspecialchars($article['auteur_name'] ?? 'Valorys') . '</span>
            <span style="margin-left:20px;"><i class="fas fa-calendar"></i> ' . date('d/m/Y H:i', strtotime($article['created_at'])) . '</span>
            <span style="margin-left:20px;"><i class="fas fa-eye"></i> '      . ($article['vues'] ?? 0)  . ' vues</span>
            <span style="margin-left:20px;"><i class="fas fa-comment"></i> '  . count($replies)          . ' commentaire(s)</span>
        </div>
        <div style="line-height:1.8; color:#333;">
            ' . nl2br(htmlspecialchars($article['contenu'])) . '
        </div>
    </div>';

    // ── Section commentaires ──────────────────────────────────────
    $content .= '
    <div style="background:white; border-radius:12px; padding:25px; margin-bottom:30px; box-shadow:0 2px 8px rgba(0,0,0,0.1);">
        <h3 style="margin-bottom:20px; padding-bottom:10px; border-bottom:2px solid #2A7FAA;">
            <i class="fas fa-comments"></i> Commentaires (' . count($replies) . ')
        </h3>
        <div id="replies-container">';

    if (empty($replies)) {
        $content .= '<p style="text-align:center; color:#999; padding:20px;">Aucun commentaire pour le moment. Soyez le premier à réagir !</p>';
    } else {
        foreach ($replies as $reply) {
            // Droits sur le commentaire
            $canEditReply   = ($isLoggedIn && !empty($reply['user_id']) && $userId == $reply['user_id']);
            $canDeleteReply = $canEditReply;

            // Contenu MIXTE (texte + emoji + image possible)
            $replyContent = '';
            
            // Ajouter l'emoji si présent
            if (!empty($reply['emoji'])) {
                $replyContent .= '<div class="reply-emoji">' . htmlspecialchars($reply['emoji']) . '</div>';
            }
            
            // Ajouter le texte si présent
            if (!empty($reply['contenu_text'])) {
                $replyContent .= '<div class="reply-text">' . nl2br(htmlspecialchars($reply['contenu_text'])) . '</div>';
            }
            
            // Ajouter l'image si présente
            if (!empty($reply['photo'])) {
                $replyContent .= '<img src="' . htmlspecialchars($reply['photo']) . '" class="reply-photo" alt="Photo">';
            }

            // Boutons modifier / supprimer
            $replyButtons = '';
            if ($canEditReply || $canDeleteReply) {
                $replyButtons = '
                <div class="reply-actions">
                    ' . ($canEditReply   ? '<button onclick="openEditReplyModal('  . $reply['id_reply'] . ')" class="btn-edit-reply"><i class="fas fa-edit"></i> Modifier</button>'   : '') . '
                    ' . ($canDeleteReply ? '<button onclick="confirmDeleteReply(' . $reply['id_reply'] . ')" class="btn-delete-reply"><i class="fas fa-trash"></i> Supprimer</button>' : '') . '
                </div>';
            }

            $content .= '
            <div class="reply-item" id="reply-' . $reply['id_reply'] . '">
                <div class="reply-avatar">' . strtoupper(substr($reply['auteur'] ?? 'A', 0, 1)) . '</div>
                <div class="reply-content">
                    <div class="reply-author">' . htmlspecialchars($reply['auteur'] ?? 'Anonyme') . '</div>
                    <div class="reply-date"><i class="fas fa-clock"></i> ' . date('d/m/Y H:i', strtotime($reply['date_reply'])) . '</div>
                    ' . $replyContent . '
                    ' . $replyButtons . '
                </div>
            </div>';
        }
    }

    $content .= '</div></div>';

    // ── Formulaire ajout commentaire MIXTE ──────────────────────────────
    if ($isLoggedIn) {
        // Récupérer les erreurs et données précédentes
        $replyErrors = $_SESSION['reply_errors'] ?? [];
        $replyData = $_SESSION['reply_data'] ?? [];
        $textValue = htmlspecialchars($replyData['text'] ?? '');
        $emojiValue = htmlspecialchars($replyData['emoji'] ?? '');
        
        $content .= '
        <div id="comment-form" style="background:white; border-radius:12px; padding:25px; box-shadow:0 2px 8px rgba(0,0,0,0.1);">
            <h4 style="margin-bottom:20px;"><i class="fas fa-pen"></i> Laisser un commentaire</h4>
            
            <form method="POST" action="index.php?page=detail_article_public&id=' . $id . '" enctype="multipart/form-data">
                <!-- Champ Texte -->
                <div class="form-group">
                    <label>Votre commentaire (texte)</label>
                    <textarea name="reply_text" rows="4" placeholder="Écrivez votre commentaire..." 
                              style="width:100%; padding:10px; border:1px solid ' . (isset($replyErrors['general']) || isset($replyErrors['text']) ? '#dc3545' : '#ddd') . '; border-radius:6px;">' . $textValue . '</textarea>
                    ' . (isset($replyErrors['text']) ? '<div class="field-error" style="color:#dc3545; font-size:12px; margin-top:5px;"><i class="fas fa-exclamation-circle"></i> ' . htmlspecialchars($replyErrors['text']) . '</div>' : '') . '
                </div>
                
                <!-- Champ Emoji -->
                <div class="form-group">
                    <label>Emoji (optionnel)</label>
                    <input type="text" name="reply_emoji" placeholder="😊 😢 👍" value="' . $emojiValue . '" 
                           style="width:100%; padding:10px; border:1px solid ' . (isset($replyErrors['emoji']) ? '#dc3545' : '#ddd') . '; border-radius:6px;">
                    ' . (isset($replyErrors['emoji']) ? '<div class="field-error" style="color:#dc3545; font-size:12px; margin-top:5px;"><i class="fas fa-exclamation-circle"></i> ' . htmlspecialchars($replyErrors['emoji']) . '</div>' : '') . '
                </div>
                
                <!-- Champ Image -->
                <div class="form-group">
                    <label>Image (optionnel)</label>
                    <input type="file" name="reply_image" accept="image/*" class="form-control"
                           style="border:1px solid ' . (isset($replyErrors['image']) ? '#dc3545' : '#ddd') . '; border-radius:6px;">
                    <small class="text-muted">Formats acceptés : JPG, PNG, GIF. Max 2 Mo.</small>
                    ' . (isset($replyErrors['image']) ? '<div class="field-error" style="color:#dc3545; font-size:12px; margin-top:5px;"><i class="fas fa-exclamation-circle"></i> ' . htmlspecialchars($replyErrors['image']) . '</div>' : '') . '
                </div>
                
                <!-- Message d\'erreur général -->
                ' . (isset($replyErrors['general']) ? '<div class="field-error" style="color:#dc3545; font-size:13px; margin-bottom:15px; padding:10px; background:#ffe6e6; border-radius:8px;"><i class="fas fa-exclamation-circle"></i> ' . htmlspecialchars($replyErrors['general']) . '</div>' : '') . '
                
                <button type="submit" name="submit_reply" class="btn-submit">
                    <i class="fas fa-paper-plane"></i> Publier le commentaire
                </button>
            </form>
        </div>';
        
        // Nettoyer les erreurs après affichage
        unset($_SESSION['reply_errors']);
        unset($_SESSION['reply_data']);
    } else {
        $content .= '
        <div style="background:#e3f2fd; border-left:4px solid #2196f3; padding:15px; border-radius:8px;">
            <i class="fas fa-info-circle"></i>
            <a href="index.php?page=login" style="color:#1976d2;">Connectez-vous</a> pour laisser un commentaire.
        </div>';
    }

    // ── Modales ───────────────────────────────────────────────────
    $content .= '
    <!-- Modal Modifier Commentaire -->
    <div id="editReplyModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeEditModal()">&times;</span>
            <h3>Modifier le commentaire</h3>
            <input type="hidden" id="edit_reply_id">
            <div class="form-group">
                <label>Texte</label>
                <textarea id="edit_reply_text" rows="4" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px;"></textarea>
            </div>
            <div class="form-group">
                <label>Emoji</label>
                <input type="text" id="edit_reply_emoji" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px;">
            </div>
            <div class="form-group">
                <label>URL Photo</label>
                <input type="text" id="edit_reply_photo" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px;">
            </div>
            <button onclick="saveReplyEdit()" class="btn-submit">Enregistrer</button>
        </div>
    </div>

    <!-- Modal Confirmation Suppression -->
    <div id="deleteReplyModal" class="modal">
        <div class="modal-content" style="text-align:center;">
            <span class="close" onclick="closeDeleteModal()">&times;</span>
            <h3>Confirmer la suppression</h3>
            <p>Êtes-vous sûr de vouloir supprimer ce commentaire ?</p>
            <p style="color:red; font-size:12px;">Cette action est irréversible.</p>
            <div style="display:flex; gap:10px; justify-content:center; margin-top:20px;">
                <button onclick="closeDeleteModal()" style="padding:8px 20px; background:#6c757d; color:white; border:none; border-radius:5px; cursor:pointer;">Annuler</button>
                <button id="confirmDeleteBtn" style="padding:8px 20px; background:#dc3545; color:white; border:none; border-radius:5px; cursor:pointer;">Supprimer</button>
            </div>
        </div>
    </div>

    <script>
    var currentDeleteId = null;

    function openEditReplyModal(replyId) {
        fetch("index.php?page=api_reply&id=" + replyId)
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    document.getElementById("edit_reply_id").value    = data.reply.id_reply;
                    document.getElementById("edit_reply_text").value  = data.reply.contenu_text || "";
                    document.getElementById("edit_reply_emoji").value = data.reply.emoji || "";
                    document.getElementById("edit_reply_photo").value = data.reply.photo || "";
                    document.getElementById("editReplyModal").style.display = "block";
                } else {
                    alert("Erreur : " + data.message);
                }
            })
            .catch(() => alert("Erreur de chargement du commentaire"));
    }

    function saveReplyEdit() {
        var id = document.getElementById("edit_reply_id").value;
        var data = {
            contenu_text: document.getElementById("edit_reply_text").value,
            emoji: document.getElementById("edit_reply_emoji").value,
            photo: document.getElementById("edit_reply_photo").value,
            type_reply: "mixte",
            _method: "PUT"
        };

        fetch("index.php?page=api_reply&id=" + id, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(data)
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) location.reload();
            else alert("Erreur : " + (data.message || "Impossible de modifier"));
        });
    }

    function confirmDeleteReply(replyId) {
        currentDeleteId = replyId;
        document.getElementById("deleteReplyModal").style.display = "block";
    }

    function closeEditModal()   { document.getElementById("editReplyModal").style.display   = "none"; }
    function closeDeleteModal() { document.getElementById("deleteReplyModal").style.display = "none"; currentDeleteId = null; }

    document.getElementById("confirmDeleteBtn").onclick = function () {
        if (currentDeleteId) {
            fetch("index.php?page=api_reply&id=" + currentDeleteId, {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ _method: "DELETE" })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) location.reload();
                else alert("Erreur lors de la suppression");
            });
        }
        closeDeleteModal();
    };

    window.onclick = function (event) {
        if (event.target.classList.contains("modal")) event.target.style.display = "none";
    };
    </script>';

    $fullContent = $content . $this->getDeleteScript();
    
    // Utilisation de renderPublicView pour le design vert (front office)
    $this->renderPublicView(htmlspecialchars($article['titre']), $fullContent);
}

private function getAdminArticleDetailHTML($article, $replies, $id): string {
    $isLoggedIn = isset($_SESSION['user_id']);
    $userId = $_SESSION['user_id'] ?? null;
    $isAdmin = true;
    
    // Récupérer les erreurs et données précédentes
    $replyErrors = $_SESSION['reply_errors'] ?? [];
    $replyData = $_SESSION['reply_data'] ?? [];
    $textValue = htmlspecialchars($replyData['text'] ?? '');
    $emojiValue = htmlspecialchars($replyData['emoji'] ?? '');
    
    $content = '
    <style>
        .article-header { background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 20px; }
        .article-meta { display: flex; gap: 20px; flex-wrap: wrap; margin: 15px 0; padding: 10px 0; border-bottom: 1px solid #eee; }
        .article-stats { display: flex; gap: 15px; }
        .stat-badge { background: #e9ecef; padding: 5px 12px; border-radius: 20px; font-size: 13px; }
        .comment-item { border-bottom: 1px solid #eee; padding: 15px 0; }
        .comment-avatar { width: 40px; height: 40px; border-radius: 50%; background: #2A7FAA; display: flex; align-items: center; justify-content: center; color: white; }
        .btn-action { margin: 0 2px; }
        .reply-actions { margin-top: 10px; display: flex; gap: 10px; }
        .btn-edit-reply { background: #ffc107; color: #000; border: none; padding: 4px 10px; border-radius: 4px; cursor: pointer; font-size: 11px; }
        .btn-delete-reply { background: #dc3545; color: #fff; border: none; padding: 4px 10px; border-radius: 4px; cursor: pointer; font-size: 11px; }
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); }
        .modal-content { background-color: #fff; margin: 10% auto; padding: 20px; border-radius: 10px; width: 90%; max-width: 500px; position: relative; }
        .close { position: absolute; right: 15px; top: 10px; font-size: 25px; cursor: pointer; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; }
        .btn-submit { background: linear-gradient(135deg, #2A7FAA, #4CAF50); color: white; border: none; padding: 10px 25px; border-radius: 25px; cursor: pointer; }
        .field-error { color: #dc3545; font-size: 12px; margin-top: 5px; }
        .add-comment-section { background: #f8f9fa; padding: 20px; border-radius: 10px; margin-top: 20px; }
        .add-comment-section h4 { margin-bottom: 15px; color: #2A7FAA; }
    </style>
    
    <div class="row">
        <div class="col-md-12">
            <!-- Actions article -->
            <div class="mb-3">
                <a href="index.php?page=blog_public" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-left"></i> Retour à la liste
                </a>
                <a href="index.php?page=articles_admin&action=edit&id=' . $id . '" class="btn btn-warning btn-sm">
                    <i class="fas fa-edit"></i> Modifier l\'article
                </a>
                <button type="button" class="btn btn-danger btn-sm" onclick="confirmDeleteArticle(' . $id . ', \'' . addslashes($article['titre']) . '\')">
                    <i class="fas fa-trash"></i> Supprimer l\'article
                </button>
            </div>
            
            <!-- En-tête article -->
            <div class="article-header">
                <h1>' . htmlspecialchars($article['titre']) . '</h1>
                <div class="article-meta">
                    <span><i class="fas fa-user"></i> Auteur: <strong>' . htmlspecialchars($article['auteur_name'] ?? 'Valorys') . '</strong></span>
                    <span><i class="fas fa-calendar"></i> Créé le: ' . date('d/m/Y H:i', strtotime($article['created_at'])) . '</span>
                    <span><i class="fas fa-edit"></i> Modifié le: ' . date('d/m/Y H:i', strtotime($article['updated_at'] ?? $article['created_at'])) . '</span>
                </div>
                <div class="article-stats">
                    <span class="stat-badge"><i class="fas fa-eye"></i> ' . ($article['vues'] ?? 0) . ' vues</span>
                    <span class="stat-badge"><i class="fas fa-comments"></i> ' . count($replies) . ' commentaires</span>
                    ' . (!empty($article['categorie']) ? '<span class="stat-badge"><i class="fas fa-tag"></i> ' . htmlspecialchars($article['categorie']) . '</span>' : '') . '
                    ' . (!empty($article['status']) ? '<span class="stat-badge"><i class="fas fa-flag"></i> ' . htmlspecialchars($article['status']) . '</span>' : '') . '
                </div>
            </div>
            
            <!-- Image -->
            ' . (!empty($article['image']) ? '<div class="text-center mb-4"><img src="' . htmlspecialchars($article['image']) . '" style="max-width: 100%; max-height: 400px; border-radius: 10px;"></div>' : '') . '
            
            <!-- Contenu -->
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <strong><i class="fas fa-file-alt"></i> Contenu de l\'article</strong>
                </div>
                <div class="card-body">
                    <div style="line-height: 1.8;">' . nl2br(htmlspecialchars($article['contenu'])) . '</div>
                </div>
            </div>
            
            <!-- Formulaire AJOUTER un commentaire -->
            <div class="add-comment-section">
                <h4><i class="fas fa-plus-circle"></i> Ajouter un commentaire</h4>
                <form method="POST" action="index.php?page=detail_article_public&id=' . $id . '" enctype="multipart/form-data">
                    <!-- Champ Texte -->
                    <div class="form-group">
                        <label>Commentaire (texte)</label>
                        <textarea name="reply_text" rows="3" placeholder="Écrivez votre commentaire..." 
                                  style="width:100%; padding:10px; border:1px solid ' . (isset($replyErrors['general']) || isset($replyErrors['text']) ? '#dc3545' : '#ddd') . '; border-radius:6px;">' . $textValue . '</textarea>
                        ' . (isset($replyErrors['text']) ? '<div class="field-error"><i class="fas fa-exclamation-circle"></i> ' . htmlspecialchars($replyErrors['text']) . '</div>' : '') . '
                    </div>
                    
                    <!-- Champ Emoji -->
                    <div class="form-group">
                        <label>Emoji (optionnel)</label>
                        <input type="text" name="reply_emoji" placeholder="😊 😢 👍" value="' . $emojiValue . '" 
                               style="width:100%; padding:10px; border:1px solid ' . (isset($replyErrors['emoji']) ? '#dc3545' : '#ddd') . '; border-radius:6px;">
                        ' . (isset($replyErrors['emoji']) ? '<div class="field-error"><i class="fas fa-exclamation-circle"></i> ' . htmlspecialchars($replyErrors['emoji']) . '</div>' : '') . '
                    </div>
                    
                    <!-- Champ Image -->
                    <div class="form-group">
                        <label>Image (optionnel)</label>
                        <input type="file" name="reply_image" accept="image/*" class="form-control"
                               style="border:1px solid ' . (isset($replyErrors['image']) ? '#dc3545' : '#ddd') . '; border-radius:6px;">
                        <small class="text-muted">Formats acceptés : JPG, PNG, GIF. Max 2 Mo.</small>
                        ' . (isset($replyErrors['image']) ? '<div class="field-error"><i class="fas fa-exclamation-circle"></i> ' . htmlspecialchars($replyErrors['image']) . '</div>' : '') . '
                    </div>
                    
                    <!-- Message d\'erreur général -->
                    ' . (isset($replyErrors['general']) ? '<div class="field-error" style="background:#ffe6e6; padding:10px; border-radius:8px; margin-bottom:15px;"><i class="fas fa-exclamation-circle"></i> ' . htmlspecialchars($replyErrors['general']) . '</div>' : '') . '
                    
                    <button type="submit" name="submit_reply" class="btn-submit">
                        <i class="fas fa-paper-plane"></i> Publier le commentaire
                    </button>
                </form>
            </div>
            
            <!-- Liste des commentaires -->
            <div class="card mt-4">
                <div class="card-header bg-white">
                    <strong><i class="fas fa-comments"></i> Commentaires (' . count($replies) . ')</strong>
                </div>
                <div class="card-body">';
    
    if (empty($replies)) {
        $content .= '<p class="text-muted text-center">Aucun commentaire pour le moment. Soyez le premier à commenter !</p>';
    } else {
        foreach ($replies as $reply) {
            // Contenu du commentaire
            $replyContent = '';
            if (!empty($reply['emoji'])) {
                $replyContent .= '<span style="font-size: 2rem; margin-right: 10px;">' . htmlspecialchars($reply['emoji']) . '</span>';
            }
            if (!empty($reply['contenu_text'])) {
                $replyContent .= '<div>' . nl2br(htmlspecialchars($reply['contenu_text'])) . '</div>';
            }
            if (!empty($reply['photo'])) {
                $replyContent .= '<img src="' . htmlspecialchars($reply['photo']) . '" style="max-width: 200px; border-radius: 8px; margin-top: 10px;">';
            }
            
            // Boutons Modifier/Supprimer pour chaque commentaire
            $replyButtons = '
            <div class="reply-actions">
                <button onclick="openEditReplyModal(' . $reply['id_reply'] . ')" class="btn-edit-reply">
                    <i class="fas fa-edit"></i> Modifier
                </button>
                <button onclick="confirmDeleteReply(' . $reply['id_reply'] . ')" class="btn-delete-reply">
                    <i class="fas fa-trash"></i> Supprimer
                </button>
            </div>';
            
            $content .= '
            <div class="comment-item d-flex gap-3">
                <div class="comment-avatar flex-shrink-0">' . strtoupper(substr($reply['auteur'] ?? 'A', 0, 1)) . '</div>
                <div class="flex-grow-1">
                    <div class="fw-bold">' . htmlspecialchars($reply['auteur'] ?? 'Anonyme') . '</div>
                    <div class="small text-muted">' . date('d/m/Y H:i', strtotime($reply['date_reply'])) . '</div>
                    <div class="mt-2">' . $replyContent . '</div>
                    ' . $replyButtons . '
                </div>
            </div>';
        }
    }
    
    $content .= '
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal Modifier Commentaire -->
    <div id="editReplyModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeEditModal()">&times;</span>
            <h3>Modifier le commentaire</h3>
            <input type="hidden" id="edit_reply_id">
            <div class="form-group">
                <label>Texte</label>
                <textarea id="edit_reply_text" rows="4" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px;"></textarea>
            </div>
            <div class="form-group">
                <label>Emoji</label>
                <input type="text" id="edit_reply_emoji" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px;">
            </div>
            <div class="form-group">
                <label>URL Photo</label>
                <input type="text" id="edit_reply_photo" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px;">
            </div>
            <button onclick="saveReplyEdit()" class="btn-submit">Enregistrer</button>
        </div>
    </div>
    
    <!-- Modal Confirmation Suppression -->
    <div id="deleteReplyModal" class="modal">
        <div class="modal-content" style="text-align:center;">
            <span class="close" onclick="closeDeleteModal()">&times;</span>
            <h3>Confirmer la suppression</h3>
            <p>Êtes-vous sûr de vouloir supprimer ce commentaire ?</p>
            <p style="color:red; font-size:12px;">Cette action est irréversible.</p>
            <div style="display:flex; gap:10px; justify-content:center; margin-top:20px;">
                <button onclick="closeDeleteModal()" class="btn btn-secondary">Annuler</button>
                <button id="confirmDeleteReplyBtn" class="btn btn-danger">Supprimer</button>
            </div>
        </div>
    </div>
    
    <script>
    function openEditReplyModal(replyId) {
        fetch("index.php?page=api_reply&id=" + replyId)
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    document.getElementById("edit_reply_id").value = data.reply.id_reply;
                    document.getElementById("edit_reply_text").value = data.reply.contenu_text || "";
                    document.getElementById("edit_reply_emoji").value = data.reply.emoji || "";
                    document.getElementById("edit_reply_photo").value = data.reply.photo || "";
                    document.getElementById("editReplyModal").style.display = "block";
                } else {
                    alert("Erreur : " + data.message);
                }
            });
    }
    
    function saveReplyEdit() {
        var id = document.getElementById("edit_reply_id").value;
        var data = {
            contenu_text: document.getElementById("edit_reply_text").value,
            emoji: document.getElementById("edit_reply_emoji").value,
            photo: document.getElementById("edit_reply_photo").value,
            type_reply: "mixte",
            _method: "PUT"
        };
        
        fetch("index.php?page=api_reply&id=" + id, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(data)
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) location.reload();
            else alert("Erreur : " + (data.message || "Impossible de modifier"));
        });
    }
    
    function confirmDeleteReply(replyId) {
        var confirmBtn = document.getElementById("confirmDeleteReplyBtn");
        confirmBtn.onclick = function() {
            fetch("index.php?page=api_reply&id=" + replyId, {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ _method: "DELETE" })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) location.reload();
                else alert("Erreur lors de la suppression");
            });
            closeDeleteModal();
        };
        document.getElementById("deleteReplyModal").style.display = "block";
    }
    
    function closeEditModal() {
        document.getElementById("editReplyModal").style.display = "none";
    }
    
    function closeDeleteModal() {
        document.getElementById("deleteReplyModal").style.display = "none";
    }
    
    window.onclick = function(event) {
        if (event.target.classList.contains("modal")) {
            event.target.style.display = "none";
        }
    };
    </script>';
    
    // Nettoyer les erreurs après affichage
    unset($_SESSION['reply_errors']);
    unset($_SESSION['reply_data']);
    
    return $content;
}


private function getUserArticleDetailHTML($article, $replies, $id, $isLoggedIn, $userId, $isAdmin, $isAuthor): string {
    // Boutons CRUD pour l'article (si auteur)
    $articleButtons = '';
    if ($isAuthor) {
        $articleButtons = '
        <div class="mb-3 d-flex gap-2">
            <a href="index.php?page=articles_admin&action=edit&id=' . $id . '" class="btn btn-warning btn-sm">
                <i class="fas fa-edit"></i> Modifier mon article
            </a>
            <button type="button" class="btn btn-danger btn-sm" onclick="confirmDeleteArticle(' . $id . ', \'' . addslashes($article['titre']) . '\')">
                <i class="fas fa-trash"></i> Supprimer mon article
            </button>
            <a href="index.php?page=blog_public" class="btn btn-secondary btn-sm ms-auto">
                <i class="fas fa-arrow-left"></i> Retour au blog
            </a>
        </div>';
    } else {
        $articleButtons = '
        <div class="mb-3">
            <a href="index.php?page=blog_public" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Retour au blog
            </a>
        </div>';
    }
    
    $articleImage = !empty($article['image']) ? '<img src="' . htmlspecialchars($article['image']) . '" style="width: 100%; max-height: 400px; object-fit: cover; border-radius: 12px; margin-bottom: 20px;">' : '';
    
    $content = '
    <div style="background:white; border-radius:12px; padding:30px; margin-bottom:30px; box-shadow:0 2px 8px rgba(0,0,0,0.1);">
        ' . $articleButtons . '
        ' . $articleImage . '
        <h1 style="font-size:2rem; margin-bottom:15px;">' . htmlspecialchars($article['titre']) . '</h1>
        <div style="color:#666; font-size:14px; margin-bottom:20px; padding-bottom:15px; border-bottom:1px solid #eee;">
            <span><i class="fas fa-user"></i> ' . htmlspecialchars($article['auteur_name'] ?? 'Valorys') . '</span>
            <span style="margin-left:20px;"><i class="fas fa-calendar"></i> ' . date('d/m/Y H:i', strtotime($article['created_at'])) . '</span>
            <span style="margin-left:20px;"><i class="fas fa-eye"></i> ' . ($article['vues'] ?? 0) . ' vues</span>
            <span style="margin-left:20px;"><i class="fas fa-comment"></i> ' . count($replies) . ' commentaire(s)</span>
        </div>
        <div style="line-height:1.8; color:#333;">
            ' . nl2br(htmlspecialchars($article['contenu'])) . '
        </div>
    </div>
    
    <div style="background:white; border-radius:12px; padding:25px; margin-bottom:30px; box-shadow:0 2px 8px rgba(0,0,0,0.1);">
        <h3 style="margin-bottom:20px; padding-bottom:10px; border-bottom:2px solid #2A7FAA;">
            <i class="fas fa-comments"></i> Commentaires (' . count($replies) . ')
        </h3>';
    
    if (empty($replies)) {
        $content .= '<p style="text-align:center; color:#999; padding:20px;">Aucun commentaire pour le moment. Soyez le premier à réagir !</p>';
    } else {
        foreach ($replies as $reply) {
            $canEditReply = ($isAdmin || ($isLoggedIn && !empty($reply['user_id']) && $userId == $reply['user_id']));
            
            $replyContent = '';
            if ($reply['type_reply'] === 'emoji') {
                $replyContent = '<div style="font-size:2rem;">' . htmlspecialchars($reply['emoji']) . '</div>';
            } elseif ($reply['type_reply'] === 'photo') {
                $replyContent = '<img src="' . htmlspecialchars($reply['photo']) . '" style="max-width:100%; border-radius:8px; max-height:200px;">';
            } else {
                $replyContent = '<div>' . nl2br(htmlspecialchars($reply['contenu_text'] ?? '')) . '</div>';
            }
            
            $replyButtons = '';
            if ($canEditReply) {
                $replyButtons = '
                <div style="margin-top:10px;">
                    <button onclick="openEditReplyModal(' . $reply['id_reply'] . ')" class="btn-edit-reply" style="background:#ffc107; border:none; padding:4px 10px; border-radius:4px; font-size:11px;"><i class="fas fa-edit"></i> Modifier</button>
                    <button onclick="confirmDeleteReply(' . $reply['id_reply'] . ')" class="btn-delete-reply" style="background:#dc3545; border:none; padding:4px 10px; border-radius:4px; font-size:11px; color:white;"><i class="fas fa-trash"></i> Supprimer</button>
                </div>';
            }
            
            $content .= '
            <div style="border-bottom:1px solid #eee; padding:15px 0; display:flex; gap:15px;">
                <div style="width:45px; height:45px; border-radius:50%; background:linear-gradient(135deg,#2A7FAA,#4CAF50); display:flex; align-items:center; justify-content:center; color:white; font-weight:bold;">' . strtoupper(substr($reply['auteur'] ?? 'A', 0, 1)) . '</div>
                <div style="flex:1;">
                    <div style="font-weight:bold;">' . htmlspecialchars($reply['auteur'] ?? 'Anonyme') . '</div>
                    <div style="font-size:11px; color:#999;">' . date('d/m/Y H:i', strtotime($reply['date_reply'])) . '</div>
                    ' . $replyContent . '
                    ' . $replyButtons . '
                </div>
            </div>';
        }
    }
    
    $content .= '</div>';
    
    // Formulaire d'ajout de commentaire
    if ($isLoggedIn) {
        $content .= '
        <div style="background:white; border-radius:12px; padding:25px; box-shadow:0 2px 8px rgba(0,0,0,0.1);">
            <h4><i class="fas fa-pen"></i> Laisser un commentaire</h4>
            <form method="POST" action="index.php?page=detail_article_public&id=' . $id . '" enctype="multipart/form-data">
                <textarea name="reply_text" rows="4" placeholder="Votre commentaire..." style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px;"></textarea>
                <div style="margin-top:10px;">
                    <input type="text" name="reply_emoji" placeholder="Emoji (optionnel)" style="padding:8px; border:1px solid #ddd; border-radius:6px;">
                    <input type="file" name="reply_image" accept="image/*" style="margin-left:10px;">
                </div>
                <button type="submit" name="submit_reply" class="btn-submit" style="margin-top:15px; background:linear-gradient(135deg,#2A7FAA,#4CAF50); color:white; border:none; padding:10px 25px; border-radius:25px;">
                    <i class="fas fa-paper-plane"></i> Publier
                </button>
            </form>
        </div>';
    } else {
        $content .= '
        <div style="background:#e3f2fd; border-left:4px solid #2196f3; padding:15px; border-radius:8px;">
            <i class="fas fa-info-circle"></i>
            <a href="index.php?page=login">Connectez-vous</a> pour laisser un commentaire.
        </div>';
    }
    
    return $content;
}



/**
 * Upload d'image pour un commentaire
 */
private function uploadReplyImage($file): ?string {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    
    $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        return null;
    }
    
    if ($file['size'] > 2 * 1024 * 1024) {
        return null;
    }
    
    $uploadDir = __DIR__ . '/../uploads/replies/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'reply_' . time() . '_' . uniqid() . '.' . $extension;
    $filepath = $uploadDir . $filename;
    $relativePath = 'uploads/replies/' . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return $relativePath;
    }
    
    return null;
}


/**
 * Ajouter un commentaire à un article
 */
/**
 * Ajouter un commentaire à un article
 */
private function addReply($articleId): void {
    require_once __DIR__ . '/../models/Reply.php';
    
    $replyModel = new Reply();
    $auteur = $_SESSION['user_name'] ?? 'Anonyme';
    $userId = $_SESSION['user_id'] ?? null;
    
    $contenuText = trim($_POST['reply_text'] ?? '');
    $emoji = trim($_POST['reply_emoji'] ?? '');
    $imagePath = null;
    
    $errors = [];
    
    // Gestion de l'upload d'image
    if (isset($_FILES['reply_image']) && $_FILES['reply_image']['error'] === UPLOAD_ERR_OK) {
        $imagePath = $this->uploadReplyImage($_FILES['reply_image']);
        if (!$imagePath) {
            $errors['image'] = "Format d'image non supporté. Formats acceptés : JPG, PNG, GIF. Max 2 Mo.";
        }
    }
    
    // Vérifier qu'au moins un contenu est fourni
    if (empty($contenuText) && empty($emoji) && empty($imagePath)) {
        $errors['general'] = 'Veuillez écrire un commentaire, ajouter un emoji ou une image.';
    }
    
    // Stocker les erreurs en session
    if (!empty($errors)) {
        $_SESSION['reply_errors'] = $errors;
        $_SESSION['reply_data'] = [
            'text' => $contenuText,
            'emoji' => $emoji
        ];
        header("Location: index.php?page=detail_article_public&id=$articleId#comment-form");
        exit;
    }
    
    $result = $replyModel->createMixte($articleId, $contenuText, $emoji, $imagePath, $auteur, $userId);
    
    if ($result) {
        $_SESSION['success'] = 'Commentaire ajouté avec succès !';
        unset($_SESSION['reply_errors']);
        unset($_SESSION['reply_data']);
    } else {
        $_SESSION['error'] = 'Erreur lors de l\'ajout du commentaire.';
    }
    
    header("Location: index.php?page=detail_article_public&id=$articleId");
    exit;
}
/**
 * Script JavaScript pour la confirmation de suppression
 */
/**
 * Script JavaScript pour la confirmation de suppression
 */
/**
 * Script JavaScript pour la confirmation de suppression
 */
private function getDeleteScript(): string {
    return '
    <style>
        /* Modal personnalisée pour la suppression */
        .custom-modal {
            display: none;
            position: fixed;
            z-index: 10000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            backdrop-filter: blur(3px);
            align-items: center;
            justify-content: center;
        }
        
        .custom-modal.show {
            display: flex;
        }
        
        .custom-modal-content {
            background: white;
            border-radius: 20px;
            width: 90%;
            max-width: 450px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: modalSlideIn 0.3s ease;
            overflow: hidden;
        }
        
        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .custom-modal-header {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            padding: 20px;
            text-align: center;
        }
        
        .custom-modal-header i {
            font-size: 50px;
            margin-bottom: 10px;
        }
        
        .custom-modal-header h3 {
            margin: 0;
            font-size: 1.5rem;
        }
        
        .custom-modal-body {
            padding: 25px;
            text-align: center;
        }
        
        .custom-modal-body p {
            margin: 10px 0;
            color: #555;
        }
        
        .custom-modal-body .warning-text {
            color: #dc3545;
            font-size: 13px;
            background: #ffe6e6;
            padding: 10px;
            border-radius: 8px;
            margin-top: 15px;
        }
        
        .custom-modal-footer {
            padding: 15px 20px;
            display: flex;
            gap: 10px;
            justify-content: center;
            border-top: 1px solid #eee;
        }
        
        .custom-modal-footer button {
            padding: 10px 25px;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .custom-modal-footer .btn-confirm {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
        }
        
        .custom-modal-footer .btn-confirm:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(220,53,69,0.3);
        }
        
        .custom-modal-footer .btn-cancel {
            background: #f0f0f0;
            color: #666;
        }
        
        .custom-modal-footer .btn-cancel:hover {
            background: #e0e0e0;
        }
        
        .article-title-delete {
            font-weight: bold;
            color: #2A7FAA;
            background: #e8f4f8;
            padding: 8px;
            border-radius: 8px;
            margin: 10px 0;
        }
    </style>
    
    <!-- Modal de confirmation personnalisée -->
    <div id="deleteModal" class="custom-modal">
        <div class="custom-modal-content">
            <div class="custom-modal-header">
                <i class="fas fa-trash-alt"></i>
                <h3>Confirmer la suppression</h3>
            </div>
            <div class="custom-modal-body">
                <p>Êtes-vous sûr de vouloir supprimer l\'article :</p>
                <div class="article-title-delete" id="deleteArticleTitle"></div>
                <div class="warning-text">
                    <i class="fas fa-exclamation-triangle"></i> Cette action est irréversible et supprimera également tous les commentaires associés.
                </div>
            </div>
            <div class="custom-modal-footer">
                <button class="btn-cancel" onclick="closeDeleteModal()">
                    <i class="fas fa-times"></i> Annuler
                </button>
                <button class="btn-confirm" id="confirmDeleteBtn">
                    <i class="fas fa-trash"></i> Supprimer
                </button>
            </div>
        </div>
    </div>
    
    <script>
    let currentDeleteId = null;
    
    function confirmDeleteArticle(articleId, articleTitle) {
        currentDeleteId = articleId;
        document.getElementById("deleteArticleTitle").textContent = articleTitle;
        document.getElementById("deleteModal").classList.add("show");
    }
    
    function closeDeleteModal() {
        document.getElementById("deleteModal").classList.remove("show");
        currentDeleteId = null;
    }
    
    document.getElementById("confirmDeleteBtn").onclick = function() {
        if (currentDeleteId) {
            window.location.href = "index.php?page=articles_admin&action=delete&id=" + currentDeleteId;
        }
        closeDeleteModal();
    };
    
    // Fermer la modale en cliquant en dehors
    document.getElementById("deleteModal").onclick = function(e) {
        if (e.target === this) {
            closeDeleteModal();
        }
    };
    
    // Fonction legacy pour compatibilité
    function confirmDelete(articleId) {
        if (confirm("Êtes-vous sûr de vouloir supprimer cet article ?")) {
            window.location.href = "index.php?page=articles_admin&action=delete&id=" + articleId;
        }
    }
    </script>';
}
    
    /**
     * Liste des articles (publique) - alias
     */
    public function listeArticles(): void {
        $this->blogList();
    }
    
    /**
     * Détail d'un article (publique) - alias
     */
    public function detailArticle($id): void {
        $this->blogDetail($id);
    }
    
    // =============================================
    // ADMIN - GESTION DES ARTICLES (CRUD)
    // =============================================
    
    /**
     * Créer un article (admin uniquement)
     */
/**
 * Créer un article (pour tous les utilisateurs connectés)
 */
public function adminArticleCreate(): void {
    $this->requireLogin();
    
    require_once __DIR__ . '/../models/Article.php';
    $articleModel = new Article();
    
    $userRole = $_SESSION['user_role'] ?? '';
    $isAdmin = ($userRole === 'admin');
    
    $errors = [];
    $oldData = [];
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $titre = trim($_POST['titre'] ?? '');
        $contenu = trim($_POST['contenu'] ?? '');
        $auteur_id = $_SESSION['user_id'] ?? null;
        $oldData = ['titre' => $titre, 'contenu' => $contenu];
        
        // Champs spécifiques admin
        $categorie = $isAdmin ? trim($_POST['categorie'] ?? '') : null;
        $tags = $isAdmin ? trim($_POST['tags'] ?? '') : null;
        $status = $isAdmin ? trim($_POST['status'] ?? 'publié') : 'publié';
        
        // Validation du titre
        if (empty($titre)) {
            $errors['titre'] = 'Le titre est obligatoire.';
        } elseif (mb_strlen($titre) > 255) {
            $errors['titre'] = 'Le titre ne doit pas dépasser 255 caractères.';
        }
        
        // Validation du contenu
        if (empty($contenu)) {
            $errors['contenu'] = 'Le contenu est obligatoire.';
        } elseif (mb_strlen($contenu) < 10) {
            $errors['contenu'] = 'Le contenu doit contenir au moins 10 caractères.';
        }
        
        // Gestion de l'upload d'image
        $imagePath = null;
        if (isset($_FILES['article_image']) && $_FILES['article_image']['error'] === UPLOAD_ERR_OK) {
            $imagePath = $this->uploadArticleImage($_FILES['article_image']);
            if (!$imagePath) {
                $errors['image'] = 'Erreur lors de l\'upload de l\'image. Formats acceptés : JPG, PNG, GIF. Max 2 Mo.';
            }
        }
        
        // S'il n'y a pas d'erreurs, on crée l'article
        if (empty($errors)) {
            $data = [
                'titre' => $titre,
                'contenu' => $contenu,
                'auteur_id' => $auteur_id,
                'image' => $imagePath,
                'categorie' => $categorie,
                'tags' => $tags,
                'status' => $status
            ];
            $result = $articleModel->create($data);
            if ($result > 0) {
                $_SESSION['success'] = 'Article créé avec succès !';
                header('Location: index.php?page=blog_public');
                exit;
            } else {
                $errors['general'] = 'Erreur lors de la création de l\'article.';
            }
        }
    }
    
    // Choisir le formulaire selon le rôle
    if ($isAdmin) {
        $content = $this->getAdminArticleFormHTML('Créer un article', 'admin_article_create', null, $errors, $oldData);
        $this->renderAdminLayout('Créer un article', $content, 'articles');
    } else {
        $content = $this->getUserArticleFormHTML('Créer un article', 'admin_article_create', null, $errors, $oldData);
        $this->renderPublicView('Créer un article', $content);
    }
}


private function getAdminArticleFormHTML($title, $action, $article = null, $errors = [], $oldData = []): string {
    $isEdit = $article !== null;
    
    $titreValue = $isEdit ? htmlspecialchars($article['titre'] ?? '') : htmlspecialchars($oldData['titre'] ?? '');
    $contenuValue = $isEdit ? htmlspecialchars($article['contenu'] ?? '') : htmlspecialchars($oldData['contenu'] ?? '');
    $imageValue = $isEdit ? htmlspecialchars($article['image'] ?? '') : '';
    $categorieValue = $isEdit ? htmlspecialchars($article['categorie'] ?? '') : htmlspecialchars($oldData['categorie'] ?? '');
    $tagsValue = $isEdit ? htmlspecialchars($article['tags'] ?? '') : htmlspecialchars($oldData['tags'] ?? '');
    $statusValue = $isEdit ? ($article['status'] ?? 'publié') : ($oldData['status'] ?? 'publié');
    $buttonText = $isEdit ? 'Mettre à jour' : 'Publier';
    
    return '
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="fas fa-newspaper me-2"></i>' . htmlspecialchars($title) . '</h4>
                </div>
                <div class="card-body">
                    ' . (isset($errors['general']) ? '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> ' . htmlspecialchars($errors['general']) . '</div>' : '') . '
                    
                    <form method="POST" action="index.php?page=' . $action . '" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-8">
                                <!-- Titre -->
                                <div class="mb-3">
                                    <label class="form-label">Titre <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="titre" value="' . $titreValue . '" 
                                           style="border-color: ' . (isset($errors['titre']) ? '#dc3545' : '#ddd') . ';">
                                    ' . (isset($errors['titre']) ? '<div class="field-error text-danger small mt-1"><i class="fas fa-exclamation-circle"></i> ' . htmlspecialchars($errors['titre']) . '</div>' : '') . '
                                </div>
                                
                                <!-- Contenu -->
                                <div class="mb-3">
                                    <label class="form-label">Contenu <span class="text-danger">*</span></label>
                                    <textarea class="form-control" name="contenu" rows="15" 
                                              style="border-color: ' . (isset($errors['contenu']) ? '#dc3545' : '#ddd') . ';">' . $contenuValue . '</textarea>
                                    <small class="text-muted">Vous pouvez utiliser du HTML pour formater votre contenu.</small>
                                    ' . (isset($errors['contenu']) ? '<div class="field-error text-danger small mt-1"><i class="fas fa-exclamation-circle"></i> ' . htmlspecialchars($errors['contenu']) . '</div>' : '') . '
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <!-- Image -->
                                <div class="mb-3">
                                    <label class="form-label">Image à la une</label>
                                    ' . ($imageValue ? '<div class="mb-2"><img src="' . $imageValue . '" style="max-width: 100%; border-radius: 8px;"></div>' : '') . '
                                    <input type="file" class="form-control" name="article_image" accept="image/*">
                                    <small class="text-muted">JPG, PNG, GIF. Max 2 Mo.</small>
                                    ' . (isset($errors['image']) ? '<div class="field-error text-danger small mt-1"><i class="fas fa-exclamation-circle"></i> ' . htmlspecialchars($errors['image']) . '</div>' : '') . '
                                    ' . ($imageValue ? '<div class="form-check mt-2"><input class="form-check-input" type="checkbox" name="delete_image" value="1" id="deleteImage"><label class="form-check-label" for="deleteImage">Supprimer l\'image</label></div>' : '') . '
                                </div>
                                
                                <!-- Catégorie -->
                                <div class="mb-3">
                                    <label class="form-label">Catégorie</label>
                                    <select name="categorie" class="form-select">
                                        <option value="">-- Sélectionner --</option>
                                        <option value="actualite" ' . ($categorieValue == 'actualite' ? 'selected' : '') . '>Actualité</option>
                                        <option value="conseil" ' . ($categorieValue == 'conseil' ? 'selected' : '') . '>Conseil santé</option>
                                        <option value="temoignage" ' . ($categorieValue == 'temoignage' ? 'selected' : '') . '>Témoignage</option>
                                        <option value="evenement" ' . ($categorieValue == 'evenement' ? 'selected' : '') . '>Événement</option>
                                    </select>
                                </div>
                                
                                <!-- Tags -->
                                <div class="mb-3">
                                    <label class="form-label">Tags</label>
                                    <input type="text" class="form-control" name="tags" value="' . $tagsValue . '" placeholder="santé, bien-être, médecine">
                                    <small class="text-muted">Séparez les tags par des virgules</small>
                                </div>
                                
                                <!-- Statut -->
                                <div class="mb-3">
                                    <label class="form-label">Statut</label>
                                    <select name="status" class="form-select">
                                        <option value="brouillon" ' . ($statusValue == 'brouillon' ? 'selected' : '') . '>📝 Brouillon</option>
                                        <option value="publié" ' . ($statusValue == 'publié' ? 'selected' : '') . '>✅ Publié</option>
                                        <option value="archive" ' . ($statusValue == 'archive' ? 'selected' : '') . '>📦 Archivé</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <div class="d-flex justify-content-between">
                            <a href="index.php?page=blog_public" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Annuler
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> ' . $buttonText . '
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    // Aperçu en direct
    const textarea = document.querySelector("textarea[name=contenu]");
    if(textarea) {
        textarea.addEventListener("input", function() {});
    }
    </script>';
}


   /**
 * Upload d'image pour un article
 */
private function uploadArticleImage($file): ?string {
    // Vérifier les erreurs
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    
    // Types MIME autorisés
    $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        return null;
    }
    
    // Taille maximale : 2 Mo
    if ($file['size'] > 2 * 1024 * 1024) {
        return null;
    }
    
    // Créer le dossier s'il n'existe pas
    $uploadDir = __DIR__ . '/../uploads/articles/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    // Générer un nom unique
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'article_' . time() . '_' . uniqid() . '.' . $extension;
    $filepath = $uploadDir . $filename;
    $relativePath = 'uploads/articles/' . $filename;
    
    // Déplacer le fichier
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return $relativePath;
    }
    
    return null;
} 
    /**
     * Modifier un article (admin uniquement)
     */
/**
 * Modifier un article (pour l'auteur ou l'admin)
 */
/**
 * Modifier un article (pour l'auteur ou l'admin)
 */
public function adminArticleEdit($id): void {
    $this->requireLogin();
    
    require_once __DIR__ . '/../models/Article.php';
    $articleModel = new Article();
    
    $article = $articleModel->getById($id);
    if (!$article) {
        $_SESSION['error'] = 'Article non trouvé.';
        header('Location: index.php?page=blog_public');
        exit;
    }
    
    // Vérification des droits
    $userId = $_SESSION['user_id'] ?? null;
    $userRole = $_SESSION['user_role'] ?? '';
    $isAdmin = ($userRole === 'admin');
    $isAuthor = ($userId && isset($article['auteur_id']) && $userId == $article['auteur_id']);
    
    if (!$isAdmin && !$isAuthor) {
        $_SESSION['error'] = "Vous n'êtes pas autorisé à modifier cet article.";
        header('Location: index.php?page=blog_public');
        exit;
    }
    
    $errors = [];
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $titre = trim($_POST['titre'] ?? '');
        $contenu = trim($_POST['contenu'] ?? '');
        $auteur_id = $article['auteur_id'];
        
        // Champs spécifiques admin
        $categorie = $isAdmin ? trim($_POST['categorie'] ?? '') : $article['categorie'];
        $tags = $isAdmin ? trim($_POST['tags'] ?? '') : $article['tags'];
        $status = $isAdmin ? trim($_POST['status'] ?? 'publié') : $article['status'];
        
        // Validation
        if (empty($titre)) {
            $errors['titre'] = 'Le titre est obligatoire.';
        } elseif (mb_strlen($titre) > 255) {
            $errors['titre'] = 'Le titre ne doit pas dépasser 255 caractères.';
        }
        
        if (empty($contenu)) {
            $errors['contenu'] = 'Le contenu est obligatoire.';
        } elseif (mb_strlen($contenu) < 10) {
            $errors['contenu'] = 'Le contenu doit contenir au moins 10 caractères.';
        }
        
        // Gestion de l'image
        $imagePath = $article['image'];
        
        if (isset($_POST['delete_image']) && $_POST['delete_image'] == '1') {
            if ($imagePath && file_exists(__DIR__ . '/../' . $imagePath)) {
                unlink(__DIR__ . '/../' . $imagePath);
            }
            $imagePath = null;
        }
        
        if (isset($_FILES['article_image']) && $_FILES['article_image']['error'] === UPLOAD_ERR_OK) {
            if ($imagePath && file_exists(__DIR__ . '/../' . $imagePath)) {
                unlink(__DIR__ . '/../' . $imagePath);
            }
            $imagePath = $this->uploadArticleImage($_FILES['article_image']);
            if (!$imagePath) {
                $errors['image'] = 'Erreur lors de l\'upload de l\'image.';
            }
        }
        
        if (empty($errors)) {
            $result = $articleModel->updateFull($id, $titre, $contenu, $auteur_id, $imagePath, $categorie, $tags, $status);
            if ($result) {
                $_SESSION['success'] = 'Article modifié avec succès !';
                header('Location: index.php?page=blog_public');
                exit;
            } else {
                $errors['general'] = 'Erreur lors de la modification.';
            }
        }
    }
    
    // Choisir le formulaire selon le rôle
    if ($isAdmin) {
        $content = $this->getAdminArticleFormHTML('Modifier l\'article', 'admin_article_edit&id=' . $id, $article, $errors);
        $this->renderAdminLayout('Modifier un article', $content, 'articles');
    } else {
        $content = $this->getUserArticleFormHTML('Modifier mon article', 'admin_article_edit&id=' . $id, $article, $errors);
        $this->renderPublicView('Modifier mon article', $content);
    }
}
    
    /**
     * Supprimer un article (admin uniquement)
     */
/**
 * Supprimer un article (pour l'auteur ou l'admin)
 */
public function adminArticleDelete($id): void {
    // Vérifier que l'utilisateur est connecté
    $this->requireLogin();
    
    require_once __DIR__ . '/../models/Article.php';
    $articleModel = new Article();
    
    // Récupérer l'article pour vérifier l'auteur
    $article = $articleModel->getById($id);
    if (!$article) {
        $_SESSION['error'] = 'Article non trouvé.';
        header('Location: index.php?page=blog_public');
        exit;
    }
    
    // Vérifier que l'utilisateur est l'auteur OU admin
    $userId = $_SESSION['user_id'] ?? null;
    $userRole = $_SESSION['user_role'] ?? '';
    $isAdmin = ($userRole === 'admin');
    $isAuthor = ($userId && isset($article['auteur_id']) && $userId == $article['auteur_id']);
    
    if (!$isAdmin && !$isAuthor) {
        $_SESSION['error'] = 'Vous n\'êtes pas autorisé à supprimer cet article.';
        header('Location: index.php?page=blog_public');
        exit;
    }
    
    $result = $articleModel->delete($id);
    
    if ($result) {
        $_SESSION['success'] = 'Article supprimé avec succès !';
    } else {
        $_SESSION['error'] = 'Erreur lors de la suppression de l\'article.';
    }
    
    header('Location: index.php?page=blog_public');
    exit;
}
    
    /**
     * Générer le formulaire HTML pour les articles
     */
private function getArticleFormHTML($title, $action, $article = null, $errors = []): string {
    $isEdit = $article !== null;
    
    $titreValue = $isEdit ? htmlspecialchars($article['titre'] ?? '') : htmlspecialchars($_POST['titre'] ?? '');
    $contenuValue = $isEdit ? htmlspecialchars($article['contenu'] ?? '') : htmlspecialchars($_POST['contenu'] ?? '');
    $imageValue = $isEdit ? htmlspecialchars($article['image'] ?? '') : '';
    $auteurValue = htmlspecialchars($_SESSION['user_name'] ?? 'Admin');
    $buttonText = $isEdit ? 'Mettre à jour' : 'Publier';
    
    return '
    <div class="row">
        <div class="col-md-10 mx-auto">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">' . htmlspecialchars($title) . '</h4>
                </div>
                <div class="card-body">
                    <!-- Message d\'erreur général -->
                    ' . (isset($errors['general']) ? '<div class="alert alert-danger mb-3"><i class="fas fa-exclamation-circle"></i> ' . htmlspecialchars($errors['general']) . '</div>' : '') . '
                    
                    <form method="POST" action="index.php?page=' . $action . '" enctype="multipart/form-data">
                        <!-- Champ Titre -->
                        <div class="mb-3">
                            <label class="form-label">Titre <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="titre" 
                                   value="' . $titreValue . '" 
                                   style="border-color: ' . (isset($errors['titre']) ? '#dc3545' : '#ddd') . ';">
                            ' . (isset($errors['titre']) ? '<div class="field-error" style="color:#dc3545; font-size:12px; margin-top:5px;"><i class="fas fa-exclamation-circle"></i> ' . htmlspecialchars($errors['titre']) . '</div>' : '') . '
                        </div>
                        
                        <!-- Champ Auteur -->
                        <div class="mb-3">
                            <label class="form-label">Auteur</label>
                            <input type="text" class="form-control" name="auteur" value="' . $auteurValue . '">
                            <small class="text-muted">Laissez vide pour utiliser votre nom d\'utilisateur.</small>
                        </div>
                        
                        <!-- Champ Image -->
                        <div class="mb-3">
                            <label class="form-label">Image de l\'article (optionnel)</label>
                            ' . ($imageValue ? '<div class="mb-2"><img src="' . $imageValue . '" style="max-width: 200px; max-height: 150px; border-radius: 8px;"></div>' : '') . '
                            <input type="file" class="form-control" name="article_image" accept="image/*"
                                   style="border-color: ' . (isset($errors['image']) ? '#dc3545' : '#ddd') . ';">
                            <small class="text-muted">Formats acceptés : JPG, PNG, GIF. Max 2 Mo.</small>
                            ' . (isset($errors['image']) ? '<div class="field-error" style="color:#dc3545; font-size:12px; margin-top:5px;"><i class="fas fa-exclamation-circle"></i> ' . htmlspecialchars($errors['image']) . '</div>' : '') . '
                            ' . ($imageValue ? '<div class="form-check mt-2"><input class="form-check-input" type="checkbox" name="delete_image" value="1" id="deleteImage"><label class="form-check-label" for="deleteImage">Supprimer l\'image actuelle</label></div>' : '') . '
                        </div>
                        
                        <!-- Champ Contenu -->
                        <div class="mb-3">
                            <label class="form-label">Contenu <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="contenu" rows="12" 
                                      style="border-color: ' . (isset($errors['contenu']) ? '#dc3545' : '#ddd') . ';">' . $contenuValue . '</textarea>
                            <small class="text-muted">Vous pouvez utiliser du HTML pour formater votre contenu. Pour insérer une image : &lt;img src="url_de_votre_image"&gt;</small>
                            ' . (isset($errors['contenu']) ? '<div class="field-error" style="color:#dc3545; font-size:12px; margin-top:5px;"><i class="fas fa-exclamation-circle"></i> ' . htmlspecialchars($errors['contenu']) . '</div>' : '') . '
                        </div>
                        
                        <!-- Aperçu -->
                        <div class="mb-3">
                            <label class="form-label">Aperçu du contenu</label>
                            <div id="preview" style="border: 1px solid #ddd; padding: 15px; border-radius: 8px; background: #f9f9f9; max-height: 300px; overflow-y: auto;"></div>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="index.php?page=blog_public" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Annuler
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> ' . $buttonText . '
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    // Aperçu en direct du contenu
    const textarea = document.querySelector("textarea[name=contenu]");
    const preview = document.getElementById("preview");
    
    function updatePreview() {
        if (preview) {
            preview.innerHTML = textarea.value;
        }
    }
    
    if (textarea) {
        textarea.addEventListener("input", updatePreview);
        updatePreview();
    }
    
    // Supprimer les messages d\'erreur quand l\'utilisateur corrige
    document.querySelectorAll("input, textarea").forEach(field => {
        field.addEventListener("input", function() {
            this.style.borderColor = "#ddd";
            const errorDiv = this.parentElement.querySelector(".field-error");
            if (errorDiv) errorDiv.remove();
        });
    });
    </script>';
}
    

    
    /**
     * Liste des événements (publique)
     */
    public function listeEvenements(): void {
        $this->renderTemporaryView('Événements', '<p>Page des événements en construction...</p>');
    }
    
    /**
     * Détail d'un événement (publique)
     */
    public function detailEvenement($id): void {
        $this->renderTemporaryView('Détail de l\'événement', '<p>Événement ID: ' . htmlspecialchars($id) . '</p>');
    }
    
    /**
     * Page de contact (publique)
     */
    public function contact(): void {
        $this->renderTemporaryView('Contact', '
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Nom</label>
                    <input type="text" class="form-control" name="nom" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" name="email" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Message</label>
                    <textarea class="form-control" name="message" rows="5" required></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Envoyer</button>
            </form>
        ');
    }
    
    /**
     * Page À propos (publique)
     */
    public function about(): void {
        $this->renderTemporaryView('À propos', '
            <h3>Valorys - Votre plateforme médicale</h3>
            <p>Valorys vous permet de prendre rendez-vous avec des médecins qualifiés facilement.</p>
            <p>Notre mission : faciliter l\'accès aux soins pour tous.</p>
        ');
    }
    
    // =============================================
    // PAGES PROTÉGÉES (NÉCESSITENT CONNEXION)
    // =============================================
    
    /**
     * Prendre rendez-vous (protégé)
     */
    public function prendreRendezVous($id = null): void {
        $this->requireLogin();
        $this->renderTemporaryView('Prendre rendez-vous', '
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Médecin</label>
                    <select class="form-control" name="medecin_id" required>
                        <option value="">Sélectionner un médecin</option>
                        <option value="' . htmlspecialchars($id) . '" selected>Médecin sélectionné</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Date</label>
                    <input type="date" class="form-control" name="date" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Heure</label>
                    <input type="time" class="form-control" name="heure" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Motif</label>
                    <textarea class="form-control" name="motif" rows="3"></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Confirmer</button>
            </form>
        ');
    }
    
    /**
     * Mes rendez-vous (protégé)
     */
    public function mesRendezVous(): void {
        $this->requireLogin();
        $this->renderTemporaryView('Mes rendez-vous', '<p>Liste de vos rendez-vous</p>');
    }
    
    /**
     * Annuler rendez-vous (protégé)
     */
    public function annulerRendezVous($id): void {
        $this->requireLogin();
        $this->renderTemporaryView('Annuler rendez-vous', '<p>Rendez-vous #' . htmlspecialchars($id) . ' annulé</p>');
    }
    
    /**
     * Confirmer rendez-vous (protégé)
     */
    public function confirmerRendezVous($id): void {
        $this->requireLogin();
        $this->renderTemporaryView('Confirmer rendez-vous', '<p>Rendez-vous #' . htmlspecialchars($id) . ' confirmé</p>');
    }
    
    /**
     * Mes ordonnances (protégé)
     */
    public function mesOrdonnances(): void {
        $this->requireLogin();
        $this->renderTemporaryView('Mes ordonnances', '<p>Liste de vos ordonnances</p>');
    }
    
    /**
     * Mes notifications (protégé)
     */
    public function mesNotifications(): void {
        $this->requireLogin();
        $this->renderTemporaryView('Mes notifications', '<p>Aucune notification</p>');
    }
    
    /**
     * Modifier profil (protégé)
     */
    public function modifierProfil(): void {
        $this->requireLogin();
        
        $userName = htmlspecialchars($_SESSION['user_name'] ?? '');
        $userEmail = htmlspecialchars($_SESSION['user_email'] ?? '');
        $userTelephone = htmlspecialchars($_SESSION['user_telephone'] ?? '');
        
        $content = '
        <div class="row g-4">
            <div class="col-md-8 mx-auto">
                <div class="card shadow">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-edit text-primary me-2"></i>Modifier mes informations</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="profileForm">
                            <div class="mb-4">
                                <label class="form-label"><i class="fas fa-user text-primary me-2"></i>Nom complet</label>
                                <input type="text" class="form-control form-control-lg" name="nom" placeholder="Votre nom" value="' . $userName . '" required>
                                <small class="text-muted">Votre nom complet sera visible sur vos profils</small>
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label"><i class="fas fa-envelope text-primary me-2"></i>Adresse email</label>
                                <input type="email" class="form-control form-control-lg" name="email" placeholder="votreemail@example.com" value="' . $userEmail . '" required>
                                <small class="text-muted">Utilisee pour se connecter et recevoir les notifications</small>
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label"><i class="fas fa-phone text-primary me-2"></i>Numero de telephone</label>
                                <input type="tel" class="form-control form-control-lg" name="telephone" placeholder="+33 6 XX XX XX XX" value="' . $userTelephone . '">
                                <small class="text-muted">Utile pour que les professionnels puissent vous contacter</small>
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label"><i class="fas fa-lock text-primary me-2"></i>Mot de passe (optionnel)</label>
                                <input type="password" class="form-control form-control-lg" name="password" placeholder="Laisser vide pour ne pas changer">
                                <small class="text-muted">Minimum 6 caracteres</small>
                            </div>
                            
                            <div class="alert alert-info" role="alert">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Conseil:</strong> Mettez a jour vos informations pour une meilleure experience.
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="index.php?page=mon_profil" class="btn btn-secondary btn-lg"><i class="fas fa-arrow-left me-2"></i>Annuler</a>
                                <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-save me-2"></i>Enregistrer les modifications</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>';
        
        $this->renderTemporaryView('Modifier mon profil', $content);
    }
    
    /**
     * Mon profil (protégé)
     */
    public function monProfil(): void {
        $this->requireLogin();
        
        $userName = htmlspecialchars($_SESSION['user_name'] ?? 'Utilisateur');
        $userEmail = htmlspecialchars($_SESSION['user_email'] ?? 'Non renseigne');
        $userRole = $_SESSION['user_role'] ?? 'guest';
        
        $roleLabel = match($userRole) {
            'admin' => '<span class="badge bg-danger"><i class="fas fa-shield-alt me-1"></i>Administrateur</span>',
            'medecin' => '<span class="badge bg-info"><i class="fas fa-stethoscope me-1"></i>Medecin</span>',
            'patient' => '<span class="badge bg-success"><i class="fas fa-user me-1"></i>Patient</span>',
            default => '<span class="badge bg-secondary">Utilisateur</span>'
        };
        
        $content = '
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body p-4">
                        <div class="avatar mx-auto mb-3" style="width: 100px; height: 100px; font-size: 3rem;">' . strtoupper(substr($userName, 0, 1)) . '</div>
                        <h3 class="card-title">' . $userName . '</h3>
                        <div class="mb-3">' . $roleLabel . '</div>
                        <p class="text-muted mb-4">Membre depuis le ' . date('d/m/Y') . '</p>
                        <a href="index.php?page=modifier_profil" class="btn btn-primary w-100"><i class="fas fa-edit me-2"></i>Modifier mon profil</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-info-circle text-primary me-2"></i>Informations personnelles</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label text-muted">Nom complet</label>
                                <p class="h6">' . $userName . '</p>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted">Email</label>
                                <p class="h6">' . $userEmail . '</p>
                            </div>
                        </div>
                        <hr>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label text-muted">Role</label>
                                <p class="h6">' . ucfirst($userRole) . '</p>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted">Statut</label>
                                <p class="h6"><span class="badge bg-success">Actif</span></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card mt-3">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-history text-primary me-2"></i>Activite recente</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-light border" role="alert">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            Connexion aujourd"hui
                        </div>
                        <div class="alert alert-light border" role="alert">
                            <i class="fas fa-calendar text-primary me-2"></i>
                            Profil cree le ' . date('d/m/Y') . '
                        </div>
                    </div>
                </div>
            </div>
        </div>';
        
        $this->renderTemporaryView('Mon profil', $content);
    }
    
    // =============================================
    // PAGES D'ERREUR
    // =============================================
    
    /**
     * Page 404
     */
    public function page404(): void {
        http_response_code(404);
        $content = '
            <div class="text-center py-5">
                <i class="fas fa-exclamation-triangle fa-4x text-warning mb-3 d-block"></i>
                <h1 class="display-1 text-danger fw-bold">404</h1>
                <h2 class="mb-3">Page non trouvée</h2>
                <p class="lead text-muted mb-4">La page que vous recherchez n\'existe pas ou a été supprimée.</p>
                <div class="mt-4">
                    <a href="index.php?page=accueil" class="btn btn-primary btn-lg">
                        <i class="fas fa-home"></i> Retour à l\'accueil
                    </a>
                </div>
            </div>';
        $this->renderErrorView('Erreur 404', $content);
    }
    
    /**
     * Page 403
     */
    public function page403(): void {
        http_response_code(403);
        $content = '
            <div class="text-center py-5">
                <i class="fas fa-lock fa-4x text-danger mb-3 d-block"></i>
                <h1 class="display-1 text-danger fw-bold">403</h1>
                <h2 class="mb-3">Accès refusé</h2>
                <p class="lead text-muted mb-4">Vous n\'avez pas les permissions nécessaires.</p>
                <div class="mt-4">
                    <a href="index.php?page=accueil" class="btn btn-primary btn-lg">
                        <i class="fas fa-home"></i> Retour à l\'accueil
                    </a>
                </div>
            </div>';
        $this->renderErrorView('Erreur 403', $content);
    }
    
    // =============================================
    // MÉTHODES PRIVÉES (RENDU DES VUES)
    // =============================================
    
    /**
     * Rendu des vues publiques
     */
    private function renderPublicView($title, $content): void {
        ?>
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?= htmlspecialchars($title) ?> - Valorys</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
            <?= $this->getCustomStyles() ?>
        </head>
        <body>
            <?= $this->getPublicNavbar() ?>
            <div class="container mt-4">
                <?= $this->getFlashMessages() ?>
                <div class="row">
                    <div class="col-md-10 mx-auto">
                        <div class="card shadow">
                            <div class="card-header bg-white">
                                <h3 class="mb-0"><?= htmlspecialchars($title) ?></h3>
                            </div>
                            <div class="card-body">
                                <?= $content ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?= $this->getFooter() ?>
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        </body>
        </html>
        <?php
    }
    
    /**
     * Rendu des vues temporaires (avec navbar complète)
     */
    private function renderTemporaryView($title, $content): void {
        ?>
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?= htmlspecialchars($title) ?> - Valorys</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        </head>
        <body>
            <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
                <div class="container">
                    <a class="navbar-brand" href="index.php?page=accueil">
                        <i class="fas fa-hospital-user"></i> Valorys
                    </a>
                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    <div class="collapse navbar-collapse" id="navbarNav">
                        <ul class="navbar-nav me-auto">
                            <li class="nav-item"><a class="nav-link" href="index.php?page=accueil">Accueil</a></li>
                            <li class="nav-item"><a class="nav-link" href="index.php?page=medecins">Médecins</a></li>
                            <li class="nav-item"><a class="nav-link" href="index.php?page=blog_public">Blog</a></li>
                            <li class="nav-item"><a class="nav-link" href="index.php?page=evenements">Événements</a></li>
                            <li class="nav-item"><a class="nav-link" href="index.php?page=contact">Contact</a></li>
                        </ul>
                        <ul class="navbar-nav">
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                                        <i class="fas fa-user-circle"></i> <?= htmlspecialchars($_SESSION['user_name'] ?? 'Compte') ?>
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><a class="dropdown-item" href="index.php?page=mon_profil"><i class="fas fa-id-card"></i> Mon profil</a></li>
                                        <li><a class="dropdown-item" href="index.php?page=mes_rendez_vous"><i class="fas fa-calendar-check"></i> Mes rendez-vous</a></li>
                                        <li><a class="dropdown-item" href="index.php?page=mes_ordonnances"><i class="fas fa-prescription"></i> Mes ordonnances</a></li>
                                        <?php if (($_SESSION['user_role'] ?? '') === 'admin'): ?>
                                            <li><hr class="dropdown-divider"></li>
                                            <li><a class="dropdown-item" href="index.php?page=dashboard"><i class="fas fa-tachometer-alt"></i> Administration</a></li>
                                        <?php endif; ?>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item text-danger" href="index.php?page=logout"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
                                    </ul>
                                </li>
                            <?php else: ?>
                                <li class="nav-item"><a class="nav-link" href="index.php?page=login"><i class="fas fa-sign-in-alt"></i> Connexion</a></li>
                                <li class="nav-item"><a class="nav-link btn btn-outline-light ms-2" href="index.php?page=register"><i class="fas fa-user-plus"></i> Inscription</a></li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </nav>

            <div class="container mt-4">
                <?= $this->getFlashMessages() ?>
                <div class="row">
                    <div class="col-md-10 mx-auto">
                        <div class="card shadow">
                            <div class="card-header bg-white">
                                <h3 class="mb-0"><?= htmlspecialchars($title) ?></h3>
                            </div>
                            <div class="card-body">
                                <?= $content ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?= $this->getFooter() ?>
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        </body>
        </html>
        <?php
    }
    
    /**
     * Rendu des pages d'erreur
     */
    private function renderErrorView($title, $content): void {
        ?>
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?= htmlspecialchars($title) ?> - Valorys</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        </head>
        <body class="bg-light">
            <div class="container mt-5">
                <?= $this->getFlashMessages() ?>
                <div class="row justify-content-center">
                    <div class="col-md-6">
                        <div class="card shadow">
                            <div class="card-body text-center py-5">
                                <?= $content ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </body>
        </html>
        <?php
    }
    
    /**
     * Affiche les messages flash (succès/erreur)
     */
    private function getFlashMessages(): string {
        $html = '';
        if (isset($_SESSION['success'])) {
            $html .= '<div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle"></i> ' . htmlspecialchars($_SESSION['success']) . '
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                      </div>';
            unset($_SESSION['success']);
        }
        if (isset($_SESSION['error'])) {
            $html .= '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle"></i> ' . htmlspecialchars($_SESSION['error']) . '
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                      </div>';
            unset($_SESSION['error']);
        }
        return $html;
    }
    
    // =============================================
    // COMPOSANTS HTML RÉUTILISABLES
    // =============================================
    
    /**
     * Styles CSS personnalisés avec design moderne
     */
    private function getCustomStyles(): string {
        return '
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            :root {
                --primary: #2A7FAA;
                --primary-dark: #1e5f80;
                --primary-light: #e0f0f5;
                --secondary: #4CAF50;
                --secondary-dark: #3d8b40;
                --danger: #ff6b6b;
                --warning: #ffa94d;
                --success: #51cf66;
                --info: #4ecdc4;
                --text-dark: #1a3a6b;
                --text-gray: #6b8ab0;
                --bg-light: #f0f6ff;
                --bg-white: #ffffff;
                --border: #d0e4f7;
                --shadow: 0 4px 12px rgba(42, 127, 170, 0.15);
                --shadow-lg: 0 10px 30px rgba(42, 127, 170, 0.2);
            }
            html, body {
                font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
                background: var(--bg-light);
                color: var(--text-dark);
            }
            ::-webkit-scrollbar { width: 10px; }
            ::-webkit-scrollbar-track { background: var(--bg-light); }
            ::-webkit-scrollbar-thumb { background: var(--primary); border-radius: 5px; }
            ::-webkit-scrollbar-thumb:hover { background: var(--primary-dark); }
            
            .navbar-custom {
                background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
                box-shadow: var(--shadow);
                padding: 0.8rem 2rem;
            }
            .navbar-custom .navbar-brand {
                font-size: 1.5rem;
                font-weight: 700;
                letter-spacing: -0.5px;
                transition: transform 0.2s;
            }
            .navbar-custom .navbar-brand:hover { transform: scale(1.05); }
            
            .dropdown-menu {
                border: none;
                border-radius: 12px;
                box-shadow: var(--shadow-lg);
                min-width: 220px;
            }
            .dropdown-item {
                padding: 0.75rem 1rem;
                transition: all 0.2s;
                color: var(--text-dark);
            }
            .dropdown-item:focus, .dropdown-item:hover {
                background: var(--primary-light);
                color: var(--primary);
                transform: translateX(4px);
            }
            
            .btn-primary {
                background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
                border: none;
                border-radius: 10px;
                font-weight: 500;
                padding: 0.6rem 1.2rem;
                transition: all 0.3s ease;
                box-shadow: 0 4px 8px rgba(42, 127, 170, 0.2);
            }
            .btn-primary:hover {
                background: linear-gradient(135deg, var(--primary-dark) 0%, var(--secondary-dark) 100%);
                transform: translateY(-2px);
                box-shadow: 0 8px 16px rgba(42, 127, 170, 0.3);
            }
            .btn-sm { padding: 0.4rem 0.8rem; font-size: 0.85rem; }
            
            .card {
                border: 1px solid var(--border);
                border-radius: 15px;
                transition: all 0.3s ease;
                box-shadow: 0 2px 8px rgba(0,0,0,0.05);
                overflow: hidden;
            }
            .card:hover {
                transform: translateY(-5px);
                box-shadow: var(--shadow-lg);
                border-color: var(--primary-light);
            }
            .card-header {
                background: linear-gradient(135deg, var(--primary-light) 0%, rgba(76, 175, 80, 0.1) 100%);
                border-bottom: 2px solid var(--border);
            }
            .card-body { padding: 1.5rem; }
            
            .table {
                margin-bottom: 0;
                border-collapse: collapse;
            }
            .table thead th {
                background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
                color: white;
                border: none;
                padding: 1rem;
                font-weight: 600;
            }
            .table tbody td { padding: 0.75rem 1rem; }
            .table tbody tr { border-bottom: 1px solid var(--border); transition: all 0.2s; }
            .table tbody tr:hover {
                background: var(--bg-light);
                box-shadow: inset 0 2px 4px rgba(42, 127, 170, 0.1);
            }
            
            .form-control {
                border: 1px solid var(--border);
                border-radius: 8px;
                padding: 0.6rem 1rem;
                transition: all 0.3s;
            }
            .form-control:focus {
                border-color: var(--primary);
                box-shadow: 0 0 0 0.2rem rgba(42, 127, 170, 0.1);
            }
            .form-label {
                font-weight: 600;
                color: var(--text-dark);
                margin-bottom: 0.5rem;
            }
            
            .alert {
                border: none;
                border-radius: 10px;
                padding: 1.2rem;
            }
            .alert-info { background: rgba(78, 205, 196, 0.1); color: var(--info); border-left: 4px solid var(--info); }
            
            .badge {
                padding: 0.5rem 1rem;
                font-weight: 500;
                border-radius: 20px;
            }
            
            .avatar {
                width: 48px; height: 48px;
                border-radius: 50%;
                background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
                display: flex;
                align-items: center;
                justify-content: center;
                color: white;
                font-weight: bold;
                font-size: 1.2rem;
            }
            
            @media (max-width: 768px) {
                .navbar-custom { padding: 0.5rem 1rem; }
                .card { margin-bottom: 1rem; }
            }
        </style>';
    }
    
    /**
     * Navbar publique avec dropdown profil
     */
private function getPublicNavbar(): string {
    $isLoggedIn = !empty($_SESSION['user_id']);
    if (!$isLoggedIn) {
        return '
        <nav class="navbar navbar-expand-lg navbar-dark navbar-custom sticky-top">
            <div class="container">
                <a class="navbar-brand fw-bold" href="index.php?page=accueil">
                    <i class="fas fa-hospital-user"></i> Valorys
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav mx-auto">
                        <li class="nav-item"><a class="nav-link" href="index.php?page=accueil"><i class="fas fa-home me-1"></i>Accueil</a></li>
                        <li class="nav-item"><a class="nav-link" href="index.php?page=medecins"><i class="fas fa-user-md me-1"></i>Médecins</a></li>
                        <li class="nav-item"><a class="nav-link" href="index.php?page=blog_public"><i class="fas fa-blog me-1"></i>Blog</a></li>
                        <li class="nav-item"><a class="nav-link" href="index.php?page=evenements"><i class="fas fa-calendar-alt me-1"></i>Événements</a></li>
                        <li class="nav-item"><a class="nav-link" href="index.php?page=contact"><i class="fas fa-envelope me-1"></i>Contact</a></li>
                    </ul>
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item"><a class="nav-link" href="index.php?page=login"><i class="fas fa-sign-in-alt me-1"></i>Connexion</a></li>
                        <li class="nav-item"><a class="nav-link btn btn-light ms-2" href="index.php?page=register"><i class="fas fa-user-plus me-1"></i>Inscription</a></li>
                    </ul>
                </div>
            </div>
        </nav>';
    }
    
    // Version pour utilisateur connecté
    $userName = htmlspecialchars($_SESSION['user_name'] ?? 'Compte');
    $userRole = $_SESSION['user_role'] ?? 'guest';
    
    return '
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php?page=accueil">
                <i class="fas fa-hospital-user"></i> Valorys
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item"><a class="nav-link" href="index.php?page=accueil"><i class="fas fa-home me-1"></i>Accueil</a></li>
                    <li class="nav-item"><a class="nav-link" href="index.php?page=medecins"><i class="fas fa-user-md me-1"></i>Médecins</a></li>
                    <li class="nav-item"><a class="nav-link" href="index.php?page=blog_public"><i class="fas fa-blog me-1"></i>Blog</a></li>
                    <li class="nav-item"><a class="nav-link" href="index.php?page=evenements"><i class="fas fa-calendar-alt me-1"></i>Événements</a></li>
                    <li class="nav-item"><a class="nav-link" href="index.php?page=contact"><i class="fas fa-envelope me-1"></i>Contact</a></li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="avatar me-2" style="width: 32px; height: 32px; font-size: 0.9rem;">' . strtoupper(substr($userName, 0, 1)) . '</span>
                            ' . $userName . '
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="index.php?page=mon_profil"><i class="fas fa-user me-2"></i>Mon profil</a></li>
                            <li><a class="dropdown-item" href="index.php?page=modifier_profil"><i class="fas fa-edit me-2"></i>Modifier le profil</a></li>
                            <li><a class="dropdown-item" href="index.php?page=mes_rendez_vous"><i class="fas fa-calendar me-2"></i>Mes rendez-vous</a></li>
                            ' . ($userRole === 'admin' ? '<li><hr class="dropdown-divider"></li><li><a class="dropdown-item" href="index.php?page=dashboard"><i class="fas fa-cog me-2"></i>Administration</a></li>' : '') . '
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="index.php?page=logout"><i class="fas fa-sign-out-alt me-2"></i>Déconnexion</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>';
}
    
    /**
     * Footer
     */
    private function getFooter(): string {
        return '
        <footer class="mt-5 py-4 bg-dark text-white text-center">
            <div class="container">
                <p class="mb-0">&copy; 2024 Valorys - Tous droits réservés</p>
                <small class="text-muted">Plateforme médicale en ligne</small>
            </div>
        </footer>';
    }
    
    // =============================================
    // DESIGN DE LA PAGE D'ACCUEIL PUBLIQUE
    // =============================================
    
    /**
     * Génère le HTML du dashboard public
     */
    private function getPublicDashboardHTML(): string {
        $isLoggedIn = !empty($_SESSION['user_id']);
        $userRole   = $_SESSION['user_role'] ?? '';
        $userName   = htmlspecialchars($_SESSION['user_name'] ?? 'Utilisateur');

        if ($isLoggedIn) {
            $roleContent = '';

            if ($userRole === 'admin') {
                $roleContent = '
                <div class="col-md-3">
                    <div class="card h-100 text-center p-3">
                        <div class="card-body">
                            <i class="fas fa-tachometer-alt fa-3x text-danger mb-3"></i>
                            <h5>Administration</h5>
                            <p class="small text-muted">Gérer les utilisateurs et le contenu</p>
                            <a href="index.php?page=dashboard" class="btn btn-danger btn-sm">Tableau de bord</a>
                        </div>
                    </div>
                </div>';
            }

            if (in_array($userRole, ['patient', 'admin'])) {
                $roleContent .= '
                <div class="col-md-3">
                    <div class="card h-100 text-center p-3">
                        <div class="card-body">
                            <i class="fas fa-calendar-check fa-3x text-primary mb-3"></i>
                            <h5>Mes rendez-vous</h5>
                            <p class="small text-muted">Voir et gérer vos rendez-vous</p>
                            <a href="index.php?page=mes_rendez_vous" class="btn btn-primary btn-sm">Voir</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card h-100 text-center p-3">
                        <div class="card-body">
                            <i class="fas fa-prescription fa-3x text-success mb-3"></i>
                            <h5>Mes ordonnances</h5>
                            <p class="small text-muted">Consultez vos ordonnances</p>
                            <a href="index.php?page=mes_ordonnances" class="btn btn-success btn-sm">Voir</a>
                        </div>
                    </div>
                </div>';
            }

            if ($userRole === 'medecin') {
                $roleContent .= '
                <div class="col-md-3">
                    <div class="card h-100 text-center p-3">
                        <div class="card-body">
                            <i class="fas fa-calendar-alt fa-3x text-primary mb-3"></i>
                            <h5>Mes rendez-vous</h5>
                            <p class="small text-muted">Consulter vos consultations</p>
                            <a href="index.php?page=mes_rendez_vous" class="btn btn-primary btn-sm">Voir</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card h-100 text-center p-3">
                        <div class="card-body">
                            <i class="fas fa-clock fa-3x text-info mb-3"></i>
                            <h5>Disponibilités</h5>
                            <p class="small text-muted">Gérer vos créneaux</p>
                            <a href="index.php?page=disponibilites" class="btn btn-info btn-sm">Gérer</a>
                        </div>
                    </div>
                </div>';
            }

            $roleContent .= '
            <div class="col-md-3">
                <div class="card h-100 text-center p-3">
                    <div class="card-body">
                        <i class="fas fa-blog fa-3x text-warning mb-3"></i>
                        <h5>Blog médical</h5>
                        <p class="small text-muted">Actualités et conseils santé</p>
                        <a href="index.php?page=blog_public" class="btn btn-warning btn-sm">Lire le blog</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card h-100 text-center p-3">
                    <div class="card-body">
                        <i class="fas fa-user-circle fa-3x text-secondary mb-3"></i>
                        <h5>Mon profil</h5>
                        <p class="small text-muted">Consulter et modifier vos informations</p>
                        <a href="index.php?page=mon_profil" class="btn btn-secondary btn-sm">Mon profil</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card h-100 text-center p-3">
                    <div class="card-body">
                        <i class="fas fa-box fa-3x text-info mb-3"></i>
                        <h5>Pharmacie</h5>
                        <p class="small text-muted">Achetez vos médicaments</p>
                        <a href="index.php?page=produits" class="btn btn-outline-primary btn-sm">Découvrir</a>
                    </div>
                </div>
            </div>';

            return '
            <div class="text-center mb-5">
                <h1 class="display-4 mb-3">Bonjour, ' . $userName . '&nbsp;!</h1>
                <p class="lead text-muted">Bienvenue sur votre espace Valorys</p>
            </div>
            <div class="row g-4 mb-5">' . $roleContent . '</div>
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-white">
                            <h5 class="mb-0"><i class="fas fa-calendar-alt text-primary me-2"></i> Prochain Rendez-vous</h5>
                        </div>
                        <div class="card-body text-center py-4">
                            <p class="text-muted">Aucun rendez-vous planifié</p>
                            <a href="index.php?page=medecins" class="btn btn-primary btn-sm">Prendre un rendez-vous</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-white">
                            <h5 class="mb-0"><i class="fas fa-ticket-alt text-success me-2"></i> Événements à Venir</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span>🏥 Conférence sur la cardiologie</span>
                                <small class="text-muted">15 Avril 2026</small>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span>🍎 Atelier bien-être</span>
                                <small class="text-muted">22 Avril 2026</small>
                            </div>
                            <div class="text-center mt-3">
                                <a href="index.php?page=evenements" class="btn btn-sm btn-outline-primary">Voir tous</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>';
        }

        // Page publique (non connecté)
        return '
        <div class="text-center mb-5">
            <h1 class="display-4 mb-3">Bienvenue sur Valorys!</h1>
            <p class="lead text-muted">Connectez-vous pour accéder à tous nos services</p>
        </div>
        <div class="row g-4 mb-5">
            <div class="col-md-3">
                <div class="card h-100 text-center p-3">
                    <div class="card-body">
                        <i class="fas fa-calendar-check fa-3x text-primary mb-3"></i>
                        <h5>Prendre Rendez-vous</h5>
                        <p class="small text-muted">Demandez un rendez-vous en ligne</p>
                        <a href="index.php?page=login" class="btn btn-primary btn-sm">Se connecter</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card h-100 text-center p-3">
                    <div class="card-body">
                        <i class="fas fa-prescription fa-3x text-success mb-3"></i>
                        <h5>Ordonnances</h5>
                        <p class="small text-muted">Consultez vos ordonnances</p>
                        <a href="index.php?page=login" class="btn btn-primary btn-sm">Se connecter</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card h-100 text-center p-3">
                    <div class="card-body">
                        <i class="fas fa-blog fa-3x text-warning mb-3"></i>
                        <h5>Blog médical</h5>
                        <p class="small text-muted">Actualités et conseils santé</p>
                        <a href="index.php?page=blog_public" class="btn btn-warning btn-sm">Lire le blog</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card h-100 text-center p-3">
                    <div class="card-body">
                        <i class="fas fa-exclamation-circle fa-3x text-warning mb-3"></i>
                        <h5>Réclamations</h5>
                        <p class="small text-muted">Signalez un problème</p>
                        <a href="index.php?page=login" class="btn btn-primary btn-sm">Se connecter</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="row g-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-calendar-alt text-primary me-2"></i> Prochain Rendez-vous</h5>
                    </div>
                    <div class="card-body text-center py-4">
                        <p class="text-muted">Connectez-vous pour voir vos rendez-vous</p>
                        <a href="index.php?page=login" class="btn btn-primary">Se connecter</a>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-ticket-alt text-success me-2"></i> Événements à Venir</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span>🏥 Conférence sur la cardiologie</span>
                            <small class="text-muted">15 Avril 2026</small>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span>🍎 Atelier bien-être</span>
                            <small class="text-muted">22 Avril 2026</small>
                        </div>
                        <div class="text-center mt-3">
                            <a href="index.php?page=evenements" class="btn btn-sm btn-outline-primary">Voir tous</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>';
    }
}
?>