<?php

class FrontController {
    
    private function requireLogin(): void {
        if (empty($_SESSION['user_id'])) {
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
            $_SESSION['error'] = 'Veuillez vous connecter pour accéder à cette page.';
            header('Location: index.php?page=login');
            exit;
        }
    }
    
    private function requireAdmin(): void {
        $this->requireLogin();
        if (($_SESSION['user_role'] ?? '') !== 'admin') {
            $this->page403();
            exit;
        }
    }

    private function requireMedecin(): void {
        if (($_SESSION['user_role'] ?? '') !== 'medecin') {
            $_SESSION['error'] = 'Accès réservé aux médecins.';
            header('Location: index.php?page=accueil');
            exit;
        }
    }

    // =============================================
    // PAGES PUBLIQUES
    // =============================================

    public function accueil(): void {
        if (file_exists(__DIR__ . '/../index.html')) {
            readfile(__DIR__ . '/../index.html');
        } else {
            $this->accueilPublic();
        }
    }

    public function accueilPublic(): void {
        $content = $this->getPublicDashboardHTML();
        $this->renderPublicView('Accueil', $content);
    }

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

public function listeMedecins(): void {
    require_once __DIR__ . '/../models/Medecin.php';
    $medecinModel = new Medecin();
    $medecins = $medecinModel->getAllWithUsers();

    if (empty($medecins)) {
        $content = '<div class="alert alert-info">Aucun médecin disponible pour le moment.</div>';
    } else {
        $content = '
        <style>
            .medecins-container { padding: 20px 0; }
            .medecins-header { text-align: center; margin-bottom: 40px; }
            .medecins-header h1 { color: #2A7FAA; font-size: 2.5rem; margin-bottom: 10px; }
            .medecins-header p { color: #6c757d; font-size: 1.1rem; }
            .medecin-card { background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 5px 20px rgba(0,0,0,0.08); transition: all 0.3s ease; margin-bottom: 25px; border: none; }
            .medecin-card:hover { transform: translateY(-5px); box-shadow: 0 15px 35px rgba(42,127,170,0.15); }
            .medecin-card-header { background: linear-gradient(135deg, #2A7FAA 0%, #4CAF50 100%); padding: 20px; text-align: center; color: white; }
            .medecin-card-header i { font-size: 3rem; margin-bottom: 10px; }
            .medecin-card-header h3 { margin: 0; font-size: 1.3rem; }
            .medecin-card-body { padding: 20px; }
            .medecin-info { margin-bottom: 15px; }
            .medecin-info i { width: 25px; color: #2A7FAA; margin-right: 10px; }
            .medecin-info span { color: #555; }
            .specialite-badge { display: inline-block; background: #e8f4f8; color: #2A7FAA; padding: 5px 12px; border-radius: 20px; font-size: 0.8rem; margin-top: 10px; }
            .btn-voir { background: linear-gradient(135deg, #2A7FAA 0%, #4CAF50 100%); color: white; border: none; padding: 10px 25px; border-radius: 25px; width: 100%; transition: all 0.3s; }
            .btn-voir:hover { transform: scale(1.02); opacity: 0.9; color: white; }
            .empty-state { text-align: center; padding: 60px; background: white; border-radius: 15px; }
        </style>
        <div class="medecins-container">
            <div class="medecins-header">
                <h1><i class="fas fa-user-md me-2"></i>Nos Médecins</h1>
                <p>Découvrez notre équipe de professionnels de santé</p>
            </div>
            <div class="row">';
        
        foreach ($medecins as $medecin) {
            $userId = $medecin['user_id'] ?? $medecin['id'] ?? 0;
            $content .= '
                <div class="col-md-6 col-lg-4">
                    <div class="medecin-card">
                        <div class="medecin-card-header">
                            <i class="fas fa-user-md"></i>
                            <h3>Dr. ' . htmlspecialchars($medecin['prenom'] . ' ' . $medecin['nom']) . '</h3>
                        </div>
                        <div class="medecin-card-body">
                            <div class="medecin-info">
                                <i class="fas fa-envelope"></i>
                                <span>' . htmlspecialchars($medecin['email']) . '</span>
                            </div>
                            <div class="medecin-info">
                                <i class="fas fa-stethoscope"></i>
                                <span>' . htmlspecialchars($medecin['specialite']) . '</span>
                            </div>
                            <div class="medecin-info">
                                <i class="fas fa-phone"></i>
                                <span>' . htmlspecialchars($medecin['telephone'] ?? 'Non renseigné') . '</span>
                            </div>
                            <a href="index.php?page=detail_medecin&id=' . $userId . '" class="btn btn-voir">
                                <i class="fas fa-calendar-check me-2"></i>Prendre rendez-vous
                            </a>
                        </div>
                    </div>
                </div>';
        }
        
        $content .= '
            </div>
        </div>';
    }
    
    $this->renderPublicView('Nos Médecins', $content);
}

    public function detailMedecin($id): void {
        require_once __DIR__ . '/../models/Medecin.php';
        $medecinModel = new Medecin();
        $medecin = $medecinModel->findByUserId($id);

        if (!$medecin) {
            $this->page404();
            return;
        }

        $content = '
            <div style="background:linear-gradient(135deg,#2A7FAA 0%,#4CAF50 100%);padding:40px 0;margin-bottom:40px;border-radius:15px;color:white;">
                <div class="container">
                    <div class="row align-items-center">
                        <div class="col-md-3 text-center">
                            <div style="width:150px;height:150px;margin:0 auto;background:white;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:60px;color:#2A7FAA;box-shadow:0 4px 15px rgba(0,0,0,0.2);">
                                <i class="fas fa-user-md"></i>
                            </div>
                        </div>
                        <div class="col-md-9">
                            <h1 style="margin-bottom:10px;font-size:2.5rem;font-weight:700;">Dr. ' . htmlspecialchars($medecin['prenom'] . ' ' . $medecin['nom']) . '</h1>
                            <p style="font-size:1.2rem;margin-bottom:0;opacity:0.95;">
                                <i class="fas fa-stethoscope me-2"></i>' . htmlspecialchars($medecin['specialite']) . '
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ═══════════════════════════════════════════════════════ -->
            <!--     SECTION JOINTURES - TESTS PEDAGOGIQUES           -->
            <!-- ═══════════════════════════════════════════════════════ -->
            <div style="background: linear-gradient(135deg, #e8f4f8 0%, #f0f9e8 100%); padding: 28px 0; margin-bottom: 32px; border-bottom: 2px solid #4CAF50;">
                <div class="container">
                    <div style="text-align: center; margin-bottom: 20px;">
                        <h3 style="color: #1a2035; margin-bottom: 8px;">
                            <i class="fas fa-database" style="color: #2A7FAA;"></i> Tester les JOINTURES SQL
                        </h3>
                        <p style="color: #666; font-size: 14px; margin: 0;">Démonstrations pédagogiques des jointures (INNER JOIN + LEFT JOIN)</p>
                    </div>
                    
                    <div style="display: flex; gap: 20px; justify-content: center; flex-wrap: wrap;">
                        <!-- JOINTURE 1: Produits ↔ Catégories -->
                        <a href="index.php?page=jointure_produits" style="text-decoration: none; flex: 1; min-width: 280px; max-width: 380px;">
                            <div style="
                                background: white;
                                border-radius: 12px;
                                padding: 20px;
                                box-shadow: 0 4px 12px rgba(0,0,0,0.08);
                                border-left: 4px solid #2A7FAA;
                                transition: all 0.3s;
                                cursor: pointer;
                            " 
                            onmouseover="this.style.transform=\"translateY(-3px)\"; this.style.boxShadow=\"0 8px 20px rgba(0,0,0,0.12)\""
                            onmouseout="this.style.transform=\"translateY(0)\"; this.style.boxShadow=\"0 4px 12px rgba(0,0,0,0.08)\"">
                                <div style="font-size: 24px; color: #2A7FAA; margin-bottom: 10px;">
                                    <i class="fas fa-pills"></i>
                                </div>
                                <h5 style="color: #1a2035; margin-bottom: 8px;">Produits ↔ Catégories</h5>
                                <p style="font-size: 13px; color: #666; margin: 0 0 10px 0;">
                                    Relation many-to-one avec INNER JOIN et LEFT JOIN
                                </p>
                                <div style="
                                    background: #f0f7ff;
                                    padding: 8px 12px;
                                    border-radius: 6px;
                                    font-size: 12px;
                                    color: #1565c0;
                                    margin-bottom: 10px;
                                ">
                                    <strong>Méthodes:</strong> getProduitsByCategorie(), getAllCategories()
                                </div>
                                <div style="display: inline-block; background: #4CAF50; color: white; padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: 600;">
                                    Tester →
                                </div>
                            </div>
                        </a>

                        <!-- JOINTURE 2: Articles ↔ Replies -->
                        <a href="index.php?page=jointure_articles" style="text-decoration: none; flex: 1; min-width: 280px; max-width: 380px;">
                            <div style="
                                background: white;
                                border-radius: 12px;
                                padding: 20px;
                                box-shadow: 0 4px 12px rgba(0,0,0,0.08);
                                border-left: 4px solid #4CAF50;
                                transition: all 0.3s;
                                cursor: pointer;
                            "
                            onmouseover="this.style.transform=\"translateY(-3px)\"; this.style.boxShadow=\"0 8px 20px rgba(0,0,0,0.12)\""
                            onmouseout="this.style.transform=\"translateY(0)\"; this.style.boxShadow=\"0 4px 12px rgba(0,0,0,0.08)\"">
                                <div style="font-size: 24px; color: #4CAF50; margin-bottom: 10px;">
                                    <i class="fas fa-comments"></i>
                                </div>
                                <h5 style="color: #1a2035; margin-bottom: 8px;">Articles ↔ Commentaires</h5>
                                <p style="font-size: 13px; color: #666; margin: 0 0 10px 0;">
                                    Relation one-to-many avec INNER JOIN et LEFT JOIN
                                </p>
                                <div style="
                                    background: #f0f9e8;
                                    padding: 8px 12px;
                                    border-radius: 6px;
                                    font-size: 12px;
                                    color: #2e7d32;
                                    margin-bottom: 10px;
                                ">
                                    <strong>Méthodes:</strong> getRepliesByArticle(), getArticlesWithReplyCount()
                                </div>
                                <div style="display: inline-block; background: #2A7FAA; color: white; padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: 600;">
                                    Tester →
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>

            <div class="container mb-5">
                <div class="row">
                    <div class="col-md-6">
                        <div style="background:white;border-radius:12px;padding:30px;box-shadow:0 2px 10px rgba(0,0,0,0.08);margin-bottom:20px;">
                            <h5 style="color:#2A7FAA;font-weight:700;margin-bottom:20px;">
                                <i class="fas fa-info-circle me-2"></i>Informations Professionnelles
                            </h5>
                            <div style="margin-bottom:15px;">
                                <p style="color:#666;font-size:0.95rem;margin-bottom:5px;">📧 Email</p>
                                <p style="font-weight:600;color:#333;">' . htmlspecialchars($medecin['email']) . '</p>
                            </div>
                            <div style="margin-bottom:15px;">
                                <p style="color:#666;font-size:0.95rem;margin-bottom:5px;">📞 Téléphone</p>
                                <p style="font-weight:600;color:#333;">' . htmlspecialchars($medecin['telephone'] ?? 'Non renseigné') . '</p>
                            </div>
                            <div>
                                <p style="color:#666;font-size:0.95rem;margin-bottom:5px;">💰 Tarif Consultation</p>
                                <p style="font-weight:600;color:#2A7FAA;font-size:1.3rem;">' . ($medecin['consultation_prix'] ?? '50') . ' €</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div style="background:white;border-radius:12px;padding:30px;box-shadow:0 2px 10px rgba(0,0,0,0.08);margin-bottom:20px;">
                            <h5 style="color:#2A7FAA;font-weight:700;margin-bottom:20px;">
                                <i class="fas fa-map-location-dot me-2"></i>Localisation
                            </h5>
                            <div style="margin-bottom:15px;">
                                <p style="color:#666;font-size:0.95rem;margin-bottom:5px;">🏥 Adresse Cabinet</p>
                                <p style="font-weight:600;color:#333;">' . htmlspecialchars($medecin['cabinet_adresse'] ?? 'Non renseignée') . '</p>
                            </div>
                            ' . (!empty($medecin['numero_ordre']) ? '
                            <div style="margin-bottom:15px;">
                                <p style="color:#666;font-size:0.95rem;margin-bottom:5px;">📋 Numéro Ordre</p>
                                <p style="font-weight:600;color:#333;">' . htmlspecialchars($medecin['numero_ordre']) . '</p>
                            </div>' : '') . '
                            ' . (!empty($medecin['annee_experience']) ? '
                            <div>
                                <p style="color:#666;font-size:0.95rem;margin-bottom:5px;">📅 Expérience</p>
                                <p style="font-weight:600;color:#333;">' . htmlspecialchars($medecin['annee_experience']) . ' ans</p>
                            </div>' : '') . '
                        </div>
                    </div>
                </div>

                <div style="background:linear-gradient(135deg,rgba(42,127,170,0.05) 0%,rgba(76,175,80,0.05) 100%);border-radius:12px;padding:25px;margin:30px 0;border-left:4px solid #2A7FAA;">
                    <h5 style="color:#2A7FAA;font-weight:700;margin-bottom:15px;">
                        <i class="fas fa-clipboard-check me-2"></i>Prendre un rendez-vous
                    </h5>
                    <p style="color:#666;margin-bottom:0;">Sélectionnez le bouton ci-dessous pour réserver une consultation avec ce médecin. Choisissez la date et l\'heure qui vous convient le mieux.</p>
                </div>

                <div style="display:flex;gap:15px;justify-content:center;flex-wrap:wrap;">
                    <a href="index.php?page=prendre_rendez_vous&id=' . $id . '" style="background:linear-gradient(135deg,#2A7FAA 0%,#4CAF50 100%);color:white;padding:15px 40px;border-radius:25px;text-decoration:none;font-weight:600;font-size:1.1rem;box-shadow:0 4px 15px rgba(42,127,170,0.3);transition:all 0.3s;display:inline-block;">
                        <i class="fas fa-calendar-check me-2"></i>Prendre un rendez-vous
                    </a>
                    <a href="index.php?page=medecins" style="background:#f0f0f0;color:#333;padding:15px 40px;border-radius:25px;text-decoration:none;font-weight:600;font-size:1.1rem;box-shadow:0 2px 8px rgba(0,0,0,0.1);transition:all 0.3s;display:inline-block;">
                        <i class="fas fa-arrow-left me-2"></i>Retour à la liste
                    </a>
                </div>
            </div>';
        $this->renderTemporaryView('Détail du médecin', $content);
    }

    // =============================================
    // BLOG - PAGES PUBLIQUES
    // =============================================

 public function blogList(): void {
    try {
        require_once __DIR__ . '/../models/Article.php';
        if (!class_exists('Article')) {
            throw new Exception("La classe Article n'existe pas");
        }
        
        $articleModel = new Article();
        
        // Gestion de la recherche
        $searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
        $categoryFilter = isset($_GET['categorie']) ? trim($_GET['categorie']) : '';
        
        if (!empty($searchTerm)) {
            $articles = $articleModel->search($searchTerm);
        } elseif (!empty($categoryFilter)) {
            $articles = $articleModel->getByCategorie($categoryFilter);
        } else {
            $articles = $articleModel->getAll();
        }
        
        $isLoggedIn = isset($_SESSION['user_id']);
        $userId = $_SESSION['user_id'] ?? null;
        $userRole = $_SESSION['user_role'] ?? '';
        $isAdmin = ($userRole === 'admin');

        // Barre de recherche HTML
        $searchHtml = '
        <style>
            .search-section {
                background: white;
                border-radius: 15px;
                padding: 20px;
                margin-bottom: 30px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            }
            .search-input-group {
                display: flex;
                gap: 10px;
                flex-wrap: wrap;
            }
            .search-input-group .form-control {
                flex: 1;
                border-radius: 10px;
                border: 1px solid #d0e4f7;
                padding: 12px 20px;
            }
            .search-input-group .btn-search {
                background: linear-gradient(135deg, #2A7FAA 0%, #4CAF50 100%);
                color: white;
                border: none;
                border-radius: 10px;
                padding: 12px 30px;
                transition: all 0.3s;
            }
            .search-input-group .btn-search:hover {
                transform: translateY(-2px);
                box-shadow: 0 5px 15px rgba(42,127,170,0.3);
            }
            .search-input-group .btn-reset {
                background: #6c757d;
                color: white;
                border: none;
                border-radius: 10px;
                padding: 12px 30px;
            }
            .category-filters {
                display: flex;
                gap: 10px;
                flex-wrap: wrap;
                margin-top: 15px;
            }
            .category-badge {
                background: #e8f4f8;
                color: #2A7FAA;
                padding: 6px 15px;
                border-radius: 20px;
                text-decoration: none;
                font-size: 0.85rem;
                transition: all 0.3s;
            }
            .category-badge:hover, .category-badge.active {
                background: linear-gradient(135deg, #2A7FAA 0%, #4CAF50 100%);
                color: white;
            }
            .search-info {
                margin-top: 15px;
                padding: 10px;
                background: #e3f2fd;
                border-radius: 10px;
                color: #2A7FAA;
            }
        </style>
        
        <div class="search-section">
            <form method="GET" action="index.php">
                <input type="hidden" name="page" value="blog_public">
                <div class="search-input-group">
                    <input type="text" name="search" class="form-control" 
                           placeholder="Rechercher un article..." 
                           value="' . htmlspecialchars($searchTerm) . '">
                    <button type="submit" class="btn-search">
                        <i class="fas fa-search me-2"></i>Rechercher
                    </button>
                    <a href="index.php?page=blog_public" class="btn-reset text-decoration-none">
                        <i class="fas fa-times me-2"></i>Réinitialiser
                    </a>
                </div>
            </form>
            
            <div class="category-filters">
                <a href="index.php?page=blog_public" class="category-badge ' . (empty($categoryFilter) ? 'active' : '') . '">
                    <i class="fas fa-newspaper me-1"></i>Tous
                </a>
                <a href="index.php?page=blog_public&categorie=actualite" class="category-badge ' . ($categoryFilter == 'actualite' ? 'active' : '') . '">
                    <i class="fas fa-bullhorn me-1"></i>Actualité
                </a>
                <a href="index.php?page=blog_public&categorie=conseil" class="category-badge ' . ($categoryFilter == 'conseil' ? 'active' : '') . '">
                    <i class="fas fa-heartbeat me-1"></i>Conseil santé
                </a>
                <a href="index.php?page=blog_public&categorie=temoignage" class="category-badge ' . ($categoryFilter == 'temoignage' ? 'active' : '') . '">
                    <i class="fas fa-comment-dots me-1"></i>Témoignage
                </a>
                <a href="index.php?page=blog_public&categorie=evenement" class="category-badge ' . ($categoryFilter == 'evenement' ? 'active' : '') . '">
                    <i class="fas fa-calendar-alt me-1"></i>Événement
                </a>
            </div>';
        
        if (!empty($searchTerm)) {
            $searchInfo = '<div class="search-info">
                <i class="fas fa-search me-2"></i>
                Résultats pour : <strong>' . htmlspecialchars($searchTerm) . '</strong> 
                (' . count($articles) . ' article(s) trouvé(s))
            </div>';
            $searchHtml .= $searchInfo;
        }
        
        if (!empty($categoryFilter)) {
            $searchHtml .= '<div class="search-info">
                <i class="fas fa-filter me-2"></i>
                Catégorie : <strong>' . htmlspecialchars($categoryFilter) . '</strong>
            </div>';
        }
        
        $searchHtml .= '</div>';
        
        // Suite de votre code existant pour l'affichage des articles...
        if ($isAdmin) {
            // Votre code admin existant...
            $totalArticles = count($articles);
            $totalVues = array_sum(array_column($articles, 'vues'));
            $totalComments = array_sum(array_column($articles, 'nb_replies'));
            $statsHtml = '
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card card-stats bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div><h6 class="card-title">Total articles</h6><h2 class="mb-0">' . $totalArticles . '</h2></div>
                                <i class="fas fa-newspaper fa-3x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card card-stats bg-success text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div><h6 class="card-title">Total vues</h6><h2 class="mb-0">' . $totalVues . '</h2></div>
                                <i class="fas fa-eye fa-3x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card card-stats bg-info text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div><h6 class="card-title">Total commentaires</h6><h2 class="mb-0">' . $totalComments . '</h2></div>
                                <i class="fas fa-comments fa-3x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>';
            
            $addButton = '
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0"><i class="fas fa-newspaper me-2"></i>Liste des articles</h4>
                <a href="index.php?page=articles_admin&action=create" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i> Nouvel article
                </a>
            </div>';
            
            if (empty($articles)) {
                $content = '<div class="alert alert-info">Aucun article trouvé.</div>';
            } else {
                $content = '
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-primary">
                            <tr><th>ID</th><th>Titre</th><th>Auteur</th><th>Date</th><th>Vues</th><th>Commentaires</th><th>Actions</th></tr>
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
                                    <a href="index.php?page=detail_article_public&id=' . $article['id'] . '" class="btn btn-sm btn-info"><i class="fas fa-eye"></i></a>
                                    <a href="index.php?page=articles_admin&action=edit&id=' . $article['id'] . '" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></a>
                                    <button type="button" class="btn btn-sm btn-danger" onclick="confirmDeleteArticle(' . $article['id'] . ', \'' . addslashes($article['titre']) . '\')"><i class="fas fa-trash"></i></button>
                                </div>
                            </td>
                        </tr>';
                }
                $content .= '</tbody></table></div>';
            }
            $fullContent = $searchHtml . $statsHtml . $addButton . $content . $this->getDeleteScript();
            $this->renderAdminLayout('Gestion des articles', $fullContent, 'articles');
            return;
        }
        
        // Affichage normal (non admin)
        $addButton = '';
        if ($isLoggedIn) {
            $addButton = '
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
                <h2><i class="fas fa-newspaper"></i> Nos articles</h2>
                <a href="index.php?page=articles_admin&action=create" class="btn btn-success" style="background:#28a745;color:white;padding:10px 20px;border-radius:5px;text-decoration:none;">
                    <i class="fas fa-plus"></i> Nouvel article
                </a>
            </div>';
        } else {
            $addButton = '<h2 class="mb-4"><i class="fas fa-newspaper"></i> Nos articles</h2>';
        }
        
        if (empty($articles)) {
            $content = '<div class="alert alert-info">Aucun article trouvé.</div>';
        } else {
            $content = '<div style="display:flex;flex-wrap:wrap;gap:20px;margin:0 -10px;">';
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
                    <div style="position:absolute;top:15px;right:15px;display:flex;gap:8px;z-index:100;">
                        ' . ($canEdit ? '<a href="index.php?page=articles_admin&action=edit&id=' . $article['id'] . '" style="background:#ffc107;color:#000;padding:6px 12px;border-radius:5px;text-decoration:none;font-size:12px;"><i class="fas fa-edit"></i> Modifier</a>' : '') . '
                        ' . ($canDelete ? '<button type="button" onclick="confirmDeleteArticle(' . $article['id'] . ', \'' . addslashes($article['titre']) . '\')" style="background:#dc3545;color:#fff;border:none;padding:6px 12px;border-radius:5px;cursor:pointer;font-size:12px;"><i class="fas fa-trash"></i> Supprimer</button>' : '') . '
                    </div>';
                }
                $articleImage = !empty($article['image']) ? '<img src="' . htmlspecialchars($article['image']) . '" style="width:100%;height:180px;object-fit:cover;border-radius:8px;margin-bottom:15px;">' : '';
                $content .= '
                <div style="flex:0 0 calc(50% - 20px);min-width:280px;border:1px solid #ddd;border-radius:8px;padding:20px;margin-bottom:20px;background:white;position:relative;">
                    ' . $crudButtons . '
                    ' . $articleImage . '
                    <h3 style="margin-top:0;padding-right:150px;">' . htmlspecialchars($article['titre']) . '</h3>
                    <div style="color:#666;font-size:13px;margin-bottom:15px;">
                        <span><i class="fas fa-user"></i> ' . htmlspecialchars($article['auteur_name'] ?? 'Valorys') . '</span>
                        <span style="margin-left:15px;"><i class="fas fa-calendar"></i> ' . date('d/m/Y', strtotime($article['created_at'])) . '</span>
                        <span style="margin-left:15px;"><i class="fas fa-eye"></i> ' . ($article['vues'] ?? 0) . ' vues</span>
                        <span style="margin-left:15px;"><i class="fas fa-comment"></i> ' . ($article['nb_replies'] ?? 0) . ' commentaire(s)</span>
                    </div>
                    <p>' . htmlspecialchars(substr(strip_tags($article['contenu']), 0, 150)) . '...</p>
                    <a href="index.php?page=detail_article_public&id=' . $article['id'] . '" style="display:inline-block;background:#2A7FAA;color:white;padding:8px 20px;border-radius:5px;text-decoration:none;">Lire la suite →</a>
                </div>';
            }
            $content .= '</div>';
        }
        
        $fullContent = $searchHtml . $addButton . $content . $this->getDeleteScript();
        $this->renderPublicView('Blog Valorys', $fullContent);
        
    } catch (Exception $e) {
        error_log('Erreur blogList: ' . $e->getMessage());
        $content = '<div class="alert alert-danger">Erreur: ' . htmlspecialchars($e->getMessage()) . '</div>';
        $this->renderPublicView('Blog Valorys', $content);
    }
}

    // =============================================
    // DISPONIBILITÉS
    // =============================================

    public function patientDisponibilites(): void {
        $this->requireLogin();
        
        // Traiter les actions (toggle, delete)
        $action = $_GET['action'] ?? null;
        $id = (int)($_GET['id'] ?? 0);
        
        if ($action === 'toggle' && $id > 0) {
            $this->patientToggleDisponibilite($id);
            return;
        }
        
        if ($action === 'delete' && $id > 0) {
            $this->patientDeleteDisponibilite($id);
            return;
        }
        
        if ($action === 'store') {
            $this->patientStoreDisponibilite();
            return;
        }
        
        require_once __DIR__ . '/../models/Disponibilite.php';
        require_once __DIR__ . '/../models/Medecin.php';
        $disponibiliteModel = new Disponibilite();
        $medecinModel = new Medecin();
        $medecins = $medecinModel->getAllWithUsers();
        $filters = [];
        if (!empty($_GET['medecin_id'])) $filters['medecin_id'] = (int)$_GET['medecin_id'];
        if (!empty($_GET['jour'])) $filters['jour'] = $_GET['jour'];
        $disponibilites = $disponibiliteModel->getDisponibilitesFront($filters);
        $content = $this->getPatientDisponibilitesHTML($disponibilites, $medecins);
        $this->renderPublicView('Disponibilités des médecins', $content);
    }

    public function medecinDisponibilites(): void {
        $this->requireLogin();
        $this->requireMedecin();
        require_once __DIR__ . '/../models/Disponibilite.php';
        $disponibiliteModel = new Disponibilite();
        $medecinId = (int)$_SESSION['user_id'];
        $dispos = $disponibiliteModel->getByMedecin($medecinId);
        $content = $this->getMedecinDisponibilitesHTML($dispos);
        $this->renderPublicView('Mes disponibilités', $content);
    }

    public function medecinStoreDisponibilite(): void {
        $this->requireLogin();
        $this->requireMedecin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=medecin_disponibilites');
            exit;
        }
        require_once __DIR__ . '/../models/Disponibilite.php';
        
        // Convertir le jour de semaine (texte) en numéro (1-7)
        $joursMap = ['Lundi' => 1, 'Mardi' => 2, 'Mercredi' => 3, 'Jeudi' => 4, 'Vendredi' => 5, 'Samedi' => 6, 'Dimanche' => 7];
        $jourTexte = $_POST['jour_semaine'] ?? '';
        $jourNumero = $joursMap[$jourTexte] ?? null;
        
        if ($jourNumero === null) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Jour invalide.'];
            header('Location: index.php?page=medecin_disponibilites');
            exit;
        }
        
        $data = [
            'medecin_id'   => (int)$_SESSION['user_id'],
            'jour_semaine' => $jourNumero,
            'heure_debut'  => $_POST['heure_debut'],
            'heure_fin'    => $_POST['heure_fin'],
            'actif'        => 1
        ];
        $disponibiliteModel = new Disponibilite();
        $result = $disponibiliteModel->create($data);
        $_SESSION['flash'] = $result
            ? ['type' => 'success', 'message' => 'Disponibilité ajoutée avec succès.']
            : ['type' => 'error',   'message' => 'Erreur lors de l\'ajout.'];
        header('Location: index.php?page=medecin_disponibilites');
        exit;
    }

public function medecinToggleDisponibilite(int $id): void {
    if (empty($_SESSION['user_id'])) {
        header('Location: index.php?page=login');
        exit;
    }
    if (($_SESSION['user_role'] ?? '') !== 'medecin') {
        $_SESSION['flash'] = ['type' => 'error', 'message' => 'Accès réservé aux médecins.'];
        header('Location: index.php?page=accueil');
        exit;
    }

    require_once __DIR__ . '/../models/Disponibilite.php';
    $disponibiliteModel = new Disponibilite();
    $dispo = $disponibiliteModel->getById($id);

    if (!$dispo) {
        $_SESSION['flash'] = ['type' => 'error', 'message' => "Disponibilité #$id introuvable."];
        header('Location: index.php?page=medecin_disponibilites');
        exit;
    }

    if ((int)$dispo['user_id'] !== (int)$_SESSION['user_id']) {
        $_SESSION['flash'] = ['type' => 'error', 'message' => 'Action non autorisée.'];
        header('Location: index.php?page=medecin_disponibilites');
        exit;
    }

    $newStatus = $dispo['actif'] ? 0 : 1;
    $label     = $newStatus ? 'activée' : 'désactivée';
    $result    = $disponibiliteModel->update($id, ['actif' => $newStatus]);

    $_SESSION['flash'] = $result
        ? ['type' => 'success', 'message' => "Disponibilité $label avec succès."]
        : ['type' => 'error',   'message' => "Erreur lors de la mise à jour (id=$id)."];

    header('Location: index.php?page=medecin_disponibilites');
    exit;
}

public function medecinDeleteDisponibilite(int $id): void {
    if (empty($_SESSION['user_id'])) {
        header('Location: index.php?page=login');
        exit;
    }
    if (($_SESSION['user_role'] ?? '') !== 'medecin') {
        $_SESSION['flash'] = ['type' => 'error', 'message' => 'Accès réservé aux médecins.'];
        header('Location: index.php?page=accueil');
        exit;
    }

    require_once __DIR__ . '/../models/Disponibilite.php';
    $disponibiliteModel = new Disponibilite();
    $dispo = $disponibiliteModel->getById($id);

    if (!$dispo) {
        $_SESSION['flash'] = ['type' => 'error', 'message' => "Disponibilité #$id introuvable."];
        header('Location: index.php?page=medecin_disponibilites');
        exit;
    }

if ((int)$dispo['medecin_id'] !== (int)$_SESSION['user_id']) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Vous n\'êtes pas autorisé à supprimer cette disponibilité.'];
        header('Location: index.php?page=medecin_disponibilites');
        exit;
    }

    $result = $disponibiliteModel->delete($id);

    $_SESSION['flash'] = $result
        ? ['type' => 'success', 'message' => 'Disponibilité supprimée avec succès.']
        : ['type' => 'error',   'message' => "Erreur lors de la suppression (id=$id)."];

    header('Location: index.php?page=medecin_disponibilites');
    exit;
}

// ===== FONCTIONS PATIENT DISPONIBILITES =====

public function patientStoreDisponibilite(): void {
    // Les patients ne peuvent pas ajouter de disponibilités
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Action non autorisée.'];
    header('Location: index.php?page=patient_disponibilites');
    exit;
}

public function patientToggleDisponibilite(int $id): void {
    // Les patients ne peuvent pas modifier les disponibilités
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Action non autorisée.'];
    header('Location: index.php?page=patient_disponibilites');
    exit;
}

public function patientDeleteDisponibilite(int $id): void {
    // Les patients ne peuvent pas supprimer les disponibilités
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Action non autorisée.'];
    header('Location: index.php?page=patient_disponibilites');
    exit;
}

    // =============================================
    // RENDU ADMIN AVEC SIDEBAR
    // =============================================

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
                body { background: #f4f6f9; font-family: 'Segoe UI', sans-serif; }
                .sidebar { background: #2c3e50; min-height: 100vh; color: white; box-shadow: 2px 0 10px rgba(0,0,0,0.1); }
                .sidebar .nav-link { color: rgba(255,255,255,0.8); padding: 12px 20px; transition: all 0.3s; border-radius: 8px; margin: 4px 10px; }
                .sidebar .nav-link:hover { background: rgba(255,255,255,0.1); color: white; }
                .sidebar .nav-link.active { background: #2A7FAA; color: white; }
                .sidebar .nav-link i { margin-right: 10px; width: 20px; text-align: center; }
                .sidebar .navbar-brand { padding: 20px 15px; font-size: 1.3rem; font-weight: bold; border-bottom: 1px solid rgba(255,255,255,0.1); margin-bottom: 15px; }
                .main-content { padding: 20px; }
                .top-bar { background: white; border-radius: 10px; padding: 15px 20px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
            </style>
        </head>
        <body>
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-2 col-lg-2 px-0 sidebar">
                        <div class="navbar-brand text-center">
                            <i class="fas fa-hospital-user me-2"></i> Valorys Admin
                        </div>
                        <nav class="nav flex-column">
                            <a class="nav-link <?= $activePage == 'dashboard'      ? 'active' : '' ?>" href="index.php?page=dashboard"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                            <a class="nav-link <?= $activePage == 'users'          ? 'active' : '' ?>" href="index.php?page=users"><i class="fas fa-users"></i> Utilisateurs</a>
                            <a class="nav-link <?= $activePage == 'patients'       ? 'active' : '' ?>" href="index.php?page=patients"><i class="fas fa-user-injured"></i> Patients</a>
                            <a class="nav-link <?= $activePage == 'medecins'       ? 'active' : '' ?>" href="index.php?page=medecins_admin"><i class="fas fa-user-md"></i> Médecins</a>
                            <a class="nav-link <?= $activePage == 'disponibilites' ? 'active' : '' ?>" href="index.php?page=disponibilites_admin"><i class="fas fa-clock"></i> Disponibilités</a>
                            <a class="nav-link <?= $activePage == 'rendezvous'     ? 'active' : '' ?>" href="index.php?page=rendez_vous_admin"><i class="fas fa-calendar-check"></i> Rendez-vous</a>
                            <a class="nav-link <?= $activePage == 'ordonnances'    ? 'active' : '' ?>" href="index.php?page=ordonnances"><i class="fas fa-prescription-bottle"></i> Ordonnances</a>
                            <a class="nav-link <?= $activePage == 'articles'       ? 'active' : '' ?>" href="index.php?page=articles_admin"><i class="fas fa-newspaper"></i> Articles</a>
                            <a class="nav-link <?= $activePage == 'evenements'     ? 'active' : '' ?>" href="index.php?page=evenements_admin"><i class="fas fa-calendar-alt"></i> Événements</a>
                            <a class="nav-link <?= $activePage == 'produits'       ? 'active' : '' ?>" href="index.php?page=produits_admin"><i class="fas fa-box"></i> Produits</a>
                            <a class="nav-link <?= $activePage == 'stats'          ? 'active' : '' ?>" href="index.php?page=stats"><i class="fas fa-chart-line"></i> Statistiques</a>
                            <a class="nav-link <?= $activePage == 'settings'       ? 'active' : '' ?>" href="index.php?page=settings"><i class="fas fa-cog"></i> Paramètres</a>
                            <hr class="mx-3 my-2" style="border-color: rgba(255,255,255,0.1);">
                            <a class="nav-link" href="index.php?page=accueil"><i class="fas fa-home"></i> Voir le site</a>
                            <a class="nav-link text-danger" href="index.php?page=logout"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
                        </nav>
                    </div>
                    <div class="col-md-10 col-lg-10 main-content">
                        <div class="top-bar d-flex justify-content-between align-items-center">
                            <h4 class="mb-0"><?= htmlspecialchars($title) ?></h4>
                            <div class="d-flex align-items-center">
                                <span class="me-3"><i class="fas fa-user-circle"></i> <?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin') ?></span>
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
            <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
                <div class="container">
                    <a class="navbar-brand fw-bold" href="index.php?page=accueil"><i class="fas fa-hospital-user"></i> Valorys Admin</a>
                    <div class="collapse navbar-collapse">
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
                            <div class="card-header bg-white"><h3 class="mb-0"><?= htmlspecialchars($title) ?></h3></div>
                            <div class="card-body"><?= $content ?></div>
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

    // =============================================
    // DETAIL ARTICLE
    // =============================================

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
        if (!$article) { $this->page404(); return; }
        $replies    = $replyModel->getByArticle($id);
        $isLoggedIn = isset($_SESSION['user_id']);
        $userId     = $_SESSION['user_id'] ?? null;
        $userRole   = $_SESSION['user_role'] ?? '';
        $isAdmin    = ($userRole === 'admin');
        if ($isAdmin) {
            $content = $this->getAdminArticleDetailHTML($article, $replies, $id);
            $this->renderAdminLayout('Détail de l\'article - Administration', $content, 'articles');
            return;
        }
        $isAuthor = ($isLoggedIn && isset($article['auteur_id']) && $userId == $article['auteur_id']);
        $articleButtons = $isAuthor ? '
        <div class="mb-3 d-flex gap-2">
            <a href="index.php?page=articles_admin&action=edit&id=' . $id . '" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i> Modifier mon article</a>
            <button type="button" class="btn btn-danger btn-sm" onclick="confirmDeleteArticle(' . $id . ', \'' . addslashes($article['titre']) . '\')"><i class="fas fa-trash"></i> Supprimer mon article</button>
            <a href="index.php?page=blog_public" class="btn btn-secondary btn-sm ms-auto"><i class="fas fa-arrow-left"></i> Retour au blog</a>
        </div>'
        : '<div class="mb-3"><a href="index.php?page=blog_public" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Retour au blog</a></div>';
        $articleImage = !empty($article['image'])
            ? '<img src="' . htmlspecialchars($article['image']) . '" style="width:100%;max-height:400px;object-fit:cover;border-radius:8px;margin-bottom:20px;">'
            : '';
        $content = '
        <style>
            .reply-item { border-bottom:1px solid #eee;padding:15px 0;display:flex;gap:15px; }
            .reply-avatar { width:45px;height:45px;border-radius:50%;background:linear-gradient(135deg,#2A7FAA,#4CAF50);display:flex;align-items:center;justify-content:center;color:white;font-weight:bold;flex-shrink:0; }
            .reply-content { flex:1; }
            .reply-author { font-weight:bold;color:#333; }
            .reply-date { font-size:11px;color:#999;margin-bottom:8px; }
            .reply-text { color:#555;line-height:1.6;margin-bottom:8px; }
            .reply-emoji { font-size:2rem;margin-bottom:8px; }
            .reply-photo { max-width:100%;border-radius:8px;margin-top:5px;max-height:200px; }
            .reply-actions { margin-top:10px;display:flex;gap:10px; }
            .btn-edit-reply { background:#ffc107;color:#000;border:none;padding:4px 10px;border-radius:4px;cursor:pointer;font-size:11px; }
            .btn-delete-reply { background:#dc3545;color:#fff;border:none;padding:4px 10px;border-radius:4px;cursor:pointer;font-size:11px; }
#editDispoModal, #addDispoModal {
        display: none;
        position: fixed;
        top: 0; left: 0;
        width: 100%; height: 100%;
        background: transparent;
        z-index: 9999;
        align-items: center;
        justify-content: center;
    }            .modal-content { background-color:#fff;margin:10% auto;padding:20px;border-radius:10px;width:90%;max-width:500px;position:relative; }
            .close { position:absolute;right:15px;top:10px;font-size:25px;cursor:pointer; }
            .form-group { margin-bottom:15px; }
            .form-group label { display:block;margin-bottom:5px;font-weight:bold; }
            .form-group input,.form-group textarea,.form-group select { width:100%;padding:10px;border:1px solid #ddd;border-radius:6px; }
            .btn-submit { background:linear-gradient(135deg,#2A7FAA,#4CAF50);color:white;border:none;padding:10px 25px;border-radius:25px;cursor:pointer; }
        </style>
        <div style="background:white;border-radius:12px;padding:30px;margin-bottom:30px;box-shadow:0 2px 8px rgba(0,0,0,0.1);">
            ' . $articleButtons . '
            ' . $articleImage . '
            <h1 style="font-size:2rem;margin-bottom:15px;">' . htmlspecialchars($article['titre']) . '</h1>
            <div style="color:#666;font-size:14px;margin-bottom:20px;padding-bottom:15px;border-bottom:1px solid #eee;">
                <span><i class="fas fa-user"></i> ' . htmlspecialchars($article['auteur_name'] ?? 'Valorys') . '</span>
                <span style="margin-left:20px;"><i class="fas fa-calendar"></i> ' . date('d/m/Y H:i', strtotime($article['created_at'])) . '</span>
                <span style="margin-left:20px;"><i class="fas fa-eye"></i> ' . ($article['vues'] ?? 0) . ' vues</span>
                <span style="margin-left:20px;"><i class="fas fa-comment"></i> ' . count($replies) . ' commentaire(s)</span>
            </div>
            <div style="line-height:1.8;color:#333;">' . nl2br(htmlspecialchars($article['contenu'])) . '</div>
        </div>';
        $content .= '
        <div style="background:white;border-radius:12px;padding:25px;margin-bottom:30px;box-shadow:0 2px 8px rgba(0,0,0,0.1);">
            <h3 style="margin-bottom:20px;padding-bottom:10px;border-bottom:2px solid #2A7FAA;"><i class="fas fa-comments"></i> Commentaires (' . count($replies) . ')</h3>
            <div id="replies-container">';
        if (empty($replies)) {
            $content .= '<p style="text-align:center;color:#999;padding:20px;">Aucun commentaire pour le moment. Soyez le premier à réagir !</p>';
        } else {
            foreach ($replies as $reply) {
                $canEditReply   = ($isLoggedIn && !empty($reply['user_id']) && $userId == $reply['user_id']);
                $canDeleteReply = $canEditReply;
                $replyContent = '';
                if (!empty($reply['emoji']))        $replyContent .= '<div class="reply-emoji">' . htmlspecialchars($reply['emoji']) . '</div>';
                if (!empty($reply['contenu_text'])) $replyContent .= '<div class="reply-text">' . nl2br(htmlspecialchars($reply['contenu_text'])) . '</div>';
                if (!empty($reply['photo']))         $replyContent .= '<img src="' . htmlspecialchars($reply['photo']) . '" class="reply-photo" alt="Photo">';
                $replyButtons = '';
                if ($canEditReply || $canDeleteReply) {
                    $replyButtons = '
                    <div class="reply-actions">
                        ' . ($canEditReply   ? '<button onclick="openEditReplyModal(' . $reply['id_reply'] . ')" class="btn-edit-reply"><i class="fas fa-edit"></i> Modifier</button>'   : '') . '
                        ' . ($canDeleteReply ? '<button onclick="confirmDeleteReply(' . $reply['id_reply'] . ')" class="btn-delete-reply"><i class="fas fa-trash"></i> Supprimer</button>' : '') . '
                    </div>';
                }
                $content .= '
                <div class="reply-item" id="reply-' . $reply['id_reply'] . '">
                    <div class="reply-avatar">' . strtoupper(substr($reply['auteur'] ?? 'A', 0, 1)) . '</div>
                    <div class="reply-content">
                        <div class="reply-author">' . htmlspecialchars($reply['auteur'] ?? 'Anonyme') . '</div>
                        <div class="reply-date"><i class="fas fa-clock"></i> ' . date('d/m/Y H:i', strtotime($reply['date_reply'])) . '</div>
                        ' . $replyContent . $replyButtons . '
                    </div>
                </div>';
            }
        }
        $content .= '</div></div>';
        if ($isLoggedIn) {
            $replyErrors = $_SESSION['reply_errors'] ?? [];
            $replyData   = $_SESSION['reply_data']   ?? [];
            $textValue   = htmlspecialchars($replyData['text']  ?? '');
            $emojiValue  = htmlspecialchars($replyData['emoji'] ?? '');
            $content .= '
            <div id="comment-form" style="background:white;border-radius:12px;padding:25px;box-shadow:0 2px 8px rgba(0,0,0,0.1);">
                <h4 style="margin-bottom:20px;"><i class="fas fa-pen"></i> Laisser un commentaire</h4>
                <form method="POST" action="index.php?page=detail_article_public&id=' . $id . '" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>Votre commentaire (texte)</label>
                        <textarea name="reply_text" rows="4" placeholder="Écrivez votre commentaire..."
                                  style="width:100%;padding:10px;border:1px solid ' . (isset($replyErrors['general']) || isset($replyErrors['text']) ? '#dc3545' : '#ddd') . ';border-radius:6px;">' . $textValue . '</textarea>
                        ' . (isset($replyErrors['text']) ? '<div style="color:#dc3545;font-size:12px;margin-top:5px;"><i class="fas fa-exclamation-circle"></i> ' . htmlspecialchars($replyErrors['text']) . '</div>' : '') . '
                    </div>
                    <div class="form-group">
                        <label>Emoji (optionnel)</label>
                        <input type="text" name="reply_emoji" placeholder="😊 😢 👍" value="' . $emojiValue . '"
                               style="width:100%;padding:10px;border:1px solid ' . (isset($replyErrors['emoji']) ? '#dc3545' : '#ddd') . ';border-radius:6px;">
                        ' . (isset($replyErrors['emoji']) ? '<div style="color:#dc3545;font-size:12px;margin-top:5px;"><i class="fas fa-exclamation-circle"></i> ' . htmlspecialchars($replyErrors['emoji']) . '</div>' : '') . '
                    </div>
                    <div class="form-group">
                        <label>Image (optionnel)</label>
                        <input type="file" name="reply_image" accept="image/*" class="form-control"
                               style="border:1px solid ' . (isset($replyErrors['image']) ? '#dc3545' : '#ddd') . ';border-radius:6px;">
                        <small class="text-muted">Formats acceptés : JPG, PNG, GIF. Max 2 Mo.</small>
                        ' . (isset($replyErrors['image']) ? '<div style="color:#dc3545;font-size:12px;margin-top:5px;"><i class="fas fa-exclamation-circle"></i> ' . htmlspecialchars($replyErrors['image']) . '</div>' : '') . '
                    </div>
                    ' . (isset($replyErrors['general']) ? '<div style="color:#dc3545;font-size:13px;margin-bottom:15px;padding:10px;background:#ffe6e6;border-radius:8px;"><i class="fas fa-exclamation-circle"></i> ' . htmlspecialchars($replyErrors['general']) . '</div>' : '') . '
                    <button type="submit" name="submit_reply" class="btn-submit"><i class="fas fa-paper-plane"></i> Publier le commentaire</button>
                </form>
            </div>';
            unset($_SESSION['reply_errors'], $_SESSION['reply_data']);
        } else {
            $content .= '
            <div style="background:#e3f2fd;border-left:4px solid #2196f3;padding:15px;border-radius:8px;">
                <i class="fas fa-info-circle"></i>
                <a href="index.php?page=login" style="color:#1976d2;">Connectez-vous</a> pour laisser un commentaire.
            </div>';
        }
        $content .= '
        <div id="editReplyModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeEditModal()">&times;</span>
                <h3>Modifier le commentaire</h3>
                <input type="hidden" id="edit_reply_id">
                <div class="form-group"><label>Texte</label><textarea id="edit_reply_text" rows="4"></textarea></div>
                <div class="form-group"><label>Emoji</label><input type="text" id="edit_reply_emoji"></div>
                <div class="form-group"><label>URL Photo</label><input type="text" id="edit_reply_photo"></div>
                <button onclick="saveReplyEdit()" class="btn-submit">Enregistrer</button>
            </div>
        </div>
        <div id="deleteReplyModal" class="modal">
            <div class="modal-content" style="text-align:center;">
                <span class="close" onclick="closeDeleteModal()">&times;</span>
                <h3>Confirmer la suppression</h3>
                <p>Êtes-vous sûr de vouloir supprimer ce commentaire ?</p>
                <p style="color:red;font-size:12px;">Cette action est irréversible.</p>
                <div style="display:flex;gap:10px;justify-content:center;margin-top:20px;">
                    <button onclick="closeDeleteModal()" style="padding:8px 20px;background:#6c757d;color:white;border:none;border-radius:5px;cursor:pointer;">Annuler</button>
                    <button id="confirmDeleteBtn" style="padding:8px 20px;background:#dc3545;color:white;border:none;border-radius:5px;cursor:pointer;">Supprimer</button>
                </div>
            </div>
        </div>';
        $content .= <<<'JS'
        <script>
        var currentDeleteId = null;
        function openEditReplyModal(replyId) {
            fetch("index.php?page=api_reply&id=" + replyId).then(r => r.json()).then(data => {
                if (data.success) {
                    document.getElementById("edit_reply_id").value    = data.reply.id_reply;
                    document.getElementById("edit_reply_text").value  = data.reply.contenu_text || "";
                    document.getElementById("edit_reply_emoji").value = data.reply.emoji || "";
                    document.getElementById("edit_reply_photo").value = data.reply.photo || "";
                    document.getElementById("editReplyModal").style.display = "block";
                } else { alert("Erreur : " + data.message); }
            }).catch(() => alert("Erreur de chargement du commentaire"));
        }
        function saveReplyEdit() {
            var id   = document.getElementById("edit_reply_id").value;
            var data = { contenu_text: document.getElementById("edit_reply_text").value, emoji: document.getElementById("edit_reply_emoji").value, photo: document.getElementById("edit_reply_photo").value, type_reply: "mixte", _method: "PUT" };
            fetch("index.php?page=api_reply&id=" + id, { method: "POST", headers: { "Content-Type": "application/json" }, body: JSON.stringify(data) }).then(r => r.json()).then(data => { if (data.success) location.reload(); else alert("Erreur : " + (data.message || "Impossible de modifier")); });
        }
        function confirmDeleteReply(replyId) { currentDeleteId = replyId; document.getElementById("deleteReplyModal").style.display = "block"; }
        function closeEditModal()   { document.getElementById("editReplyModal").style.display   = "none"; }
        function closeDeleteModal() { document.getElementById("deleteReplyModal").style.display = "none"; currentDeleteId = null; }
        document.getElementById("confirmDeleteBtn").onclick = function () {
            if (currentDeleteId) {
                fetch("index.php?page=api_reply&id=" + currentDeleteId, { method: "POST", headers: { "Content-Type": "application/json" }, body: JSON.stringify({ _method: "DELETE" }) }).then(r => r.json()).then(data => { if (data.success) location.reload(); else alert("Erreur lors de la suppression"); });
            }
            closeDeleteModal();
        };
        window.onclick = function (event) { if (event.target.classList.contains("modal")) event.target.style.display = "none"; };
        </script>
JS;
        $this->renderPublicView(htmlspecialchars($article['titre']), $content . $this->getDeleteScript());
    }

    private function getAdminArticleDetailHTML($article, $replies, $id): string {
        $replyErrors = $_SESSION['reply_errors'] ?? [];
        $replyData   = $_SESSION['reply_data']   ?? [];
        $textValue   = htmlspecialchars($replyData['text']  ?? '');
        $emojiValue  = htmlspecialchars($replyData['emoji'] ?? '');

        $content = '
        <style>
            .article-header { background:#f8f9fa;padding:20px;border-radius:10px;margin-bottom:20px; }
            .article-meta { display:flex;gap:20px;flex-wrap:wrap;margin:15px 0;padding:10px 0;border-bottom:1px solid #eee; }
            .article-stats { display:flex;gap:15px; }
            .stat-badge { background:#e9ecef;padding:5px 12px;border-radius:20px;font-size:13px; }
            .comment-item { border-bottom:1px solid #eee;padding:15px 0; }
            .comment-avatar { width:40px;height:40px;border-radius:50%;background:#2A7FAA;display:flex;align-items:center;justify-content:center;color:white; }
            .reply-actions { margin-top:10px;display:flex;gap:10px; }
            .btn-edit-reply { background:#ffc107;color:#000;border:none;padding:4px 10px;border-radius:4px;cursor:pointer;font-size:11px; }
            .btn-delete-reply { background:#dc3545;color:#fff;border:none;padding:4px 10px;border-radius:4px;cursor:pointer;font-size:11px; }
            .modal { display:none;position:fixed;z-index:1000;left:0;top:0;width:100%;height:100%;background-color:rgba(0,0,0,0.5); }
            .modal-content { background-color:#fff;margin:10% auto;padding:20px;border-radius:10px;width:90%;max-width:500px;position:relative; }
            .close { position:absolute;right:15px;top:10px;font-size:25px;cursor:pointer; }
            .form-group { margin-bottom:15px; }
            .form-group label { display:block;margin-bottom:5px;font-weight:bold; }
            .form-group input,.form-group textarea { width:100%;padding:10px;border:1px solid #ddd;border-radius:6px; }
            .btn-submit { background:linear-gradient(135deg,#2A7FAA,#4CAF50);color:white;border:none;padding:10px 25px;border-radius:25px;cursor:pointer; }
            .add-comment-section { background:#f8f9fa;padding:20px;border-radius:10px;margin-top:20px; }
        </style>
        <div class="row"><div class="col-md-12">
            <div class="mb-3">
                <a href="index.php?page=blog_public" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Retour</a>
                <a href="index.php?page=articles_admin&action=edit&id=' . $id . '" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i> Modifier</a>
                <button type="button" class="btn btn-danger btn-sm" onclick="confirmDeleteArticle(' . $id . ', \'' . addslashes($article['titre']) . '\')"><i class="fas fa-trash"></i> Supprimer</button>
            </div>
            <div class="article-header">
                <h1>' . htmlspecialchars($article['titre']) . '</h1>
                <div class="article-meta">
                    <span><i class="fas fa-user"></i> <strong>' . htmlspecialchars($article['auteur_name'] ?? 'Valorys') . '</strong></span>
                    <span><i class="fas fa-calendar"></i> ' . date('d/m/Y H:i', strtotime($article['created_at'])) . '</span>
                </div>
                <div class="article-stats">
                    <span class="stat-badge"><i class="fas fa-eye"></i> ' . ($article['vues'] ?? 0) . ' vues</span>
                    <span class="stat-badge"><i class="fas fa-comments"></i> ' . count($replies) . ' commentaires</span>
                </div>
            </div>
            ' . (!empty($article['image']) ? '<div class="text-center mb-4"><img src="' . htmlspecialchars($article['image']) . '" style="max-width:100%;max-height:400px;border-radius:10px;"></div>' : '') . '
            <div class="card mb-4">
                <div class="card-header bg-white"><strong><i class="fas fa-file-alt"></i> Contenu</strong></div>
                <div class="card-body"><div style="line-height:1.8;">' . nl2br(htmlspecialchars($article['contenu'])) . '</div></div>
            </div>
            <div class="add-comment-section">
                <h4><i class="fas fa-plus-circle"></i> Ajouter un commentaire</h4>
                <form method="POST" action="index.php?page=detail_article_public&id=' . $id . '" enctype="multipart/form-data">
                    <div class="form-group"><label>Commentaire</label><textarea name="reply_text" rows="3" style="width:100%;padding:10px;border:1px solid #ddd;border-radius:6px;">' . $textValue . '</textarea></div>
                    <div class="form-group"><label>Emoji (optionnel)</label><input type="text" name="reply_emoji" value="' . $emojiValue . '" style="width:100%;padding:10px;border:1px solid #ddd;border-radius:6px;"></div>
                    <div class="form-group"><label>Image (optionnel)</label><input type="file" name="reply_image" accept="image/*" class="form-control"><small class="text-muted">JPG, PNG, GIF. Max 2 Mo.</small></div>
                    <button type="submit" name="submit_reply" class="btn-submit"><i class="fas fa-paper-plane"></i> Publier</button>
                </form>
            </div>
            <div class="card mt-4">
                <div class="card-header bg-white"><strong><i class="fas fa-comments"></i> Commentaires (' . count($replies) . ')</strong></div>
                <div class="card-body">';

        if (empty($replies)) {
            $content .= '<p class="text-muted text-center">Aucun commentaire pour le moment.</p>';
        } else {
            foreach ($replies as $reply) {
                $replyContent = '';
                if (!empty($reply['emoji']))        $replyContent .= '<span style="font-size:2rem;margin-right:10px;">' . htmlspecialchars($reply['emoji']) . '</span>';
                if (!empty($reply['contenu_text'])) $replyContent .= '<div>' . nl2br(htmlspecialchars($reply['contenu_text'])) . '</div>';
                if (!empty($reply['photo']))         $replyContent .= '<img src="' . htmlspecialchars($reply['photo']) . '" style="max-width:200px;border-radius:8px;margin-top:10px;">';
                $content .= '
                <div class="comment-item d-flex gap-3">
                    <div class="comment-avatar flex-shrink-0">' . strtoupper(substr($reply['auteur'] ?? 'A', 0, 1)) . '</div>
                    <div class="flex-grow-1">
                        <div class="fw-bold">' . htmlspecialchars($reply['auteur'] ?? 'Anonyme') . '</div>
                        <div class="small text-muted">' . date('d/m/Y H:i', strtotime($reply['date_reply'])) . '</div>
                        <div class="mt-2">' . $replyContent . '</div>
                        <div class="reply-actions">
                            <button onclick="openEditReplyModal(' . $reply['id_reply'] . ')" class="btn-edit-reply"><i class="fas fa-edit"></i> Modifier</button>
                            <button onclick="confirmDeleteReply(' . $reply['id_reply'] . ')" class="btn-delete-reply"><i class="fas fa-trash"></i> Supprimer</button>
                        </div>
                    </div>
                </div>';
            }
        }

        $content .= '</div></div></div></div>
        <div id="editReplyModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeEditModal()">&times;</span>
                <h3>Modifier le commentaire</h3>
                <input type="hidden" id="edit_reply_id">
                <div class="form-group"><label>Texte</label><textarea id="edit_reply_text" rows="4"></textarea></div>
                <div class="form-group"><label>Emoji</label><input type="text" id="edit_reply_emoji"></div>
                <div class="form-group"><label>URL Photo</label><input type="text" id="edit_reply_photo"></div>
                <button onclick="saveReplyEdit()" class="btn-submit">Enregistrer</button>
            </div>
        </div>
        <div id="deleteReplyModal" class="modal">
            <div class="modal-content" style="text-align:center;">
                <span class="close" onclick="closeDeleteModal()">&times;</span>
                <h3>Confirmer la suppression</h3>
                <p>Êtes-vous sûr de vouloir supprimer ce commentaire ?</p>
                <div style="display:flex;gap:10px;justify-content:center;margin-top:20px;">
                    <button onclick="closeDeleteModal()" class="btn btn-secondary">Annuler</button>
                    <button id="confirmDeleteReplyBtn" class="btn btn-danger">Supprimer</button>
                </div>
            </div>
        </div>';

        $content .= <<<'JS'
        <script>
        function openEditReplyModal(replyId) {
            fetch("index.php?page=api_reply&id=" + replyId).then(r => r.json()).then(data => {
                if (data.success) {
                    document.getElementById("edit_reply_id").value    = data.reply.id_reply;
                    document.getElementById("edit_reply_text").value  = data.reply.contenu_text || "";
                    document.getElementById("edit_reply_emoji").value = data.reply.emoji || "";
                    document.getElementById("edit_reply_photo").value = data.reply.photo || "";
                    document.getElementById("editReplyModal").style.display = "block";
                } else { alert("Erreur : " + data.message); }
            });
        }
        function saveReplyEdit() {
            var id = document.getElementById("edit_reply_id").value;
            var data = { contenu_text: document.getElementById("edit_reply_text").value, emoji: document.getElementById("edit_reply_emoji").value, photo: document.getElementById("edit_reply_photo").value, type_reply: "mixte", _method: "PUT" };
            fetch("index.php?page=api_reply&id=" + id, { method: "POST", headers: { "Content-Type": "application/json" }, body: JSON.stringify(data) }).then(r => r.json()).then(data => { if (data.success) location.reload(); else alert("Erreur : " + (data.message || "Impossible de modifier")); });
        }
        function confirmDeleteReply(replyId) {
            var btn = document.getElementById("confirmDeleteReplyBtn");
            btn.onclick = function() {
                fetch("index.php?page=api_reply&id=" + replyId, { method: "POST", headers: { "Content-Type": "application/json" }, body: JSON.stringify({ _method: "DELETE" }) }).then(r => r.json()).then(data => { if (data.success) location.reload(); else alert("Erreur lors de la suppression"); });
                closeDeleteModal();
            };
            document.getElementById("deleteReplyModal").style.display = "block";
        }
        function closeEditModal()   { document.getElementById("editReplyModal").style.display   = "none"; }
        function closeDeleteModal() { document.getElementById("deleteReplyModal").style.display = "none"; }
        window.onclick = function(event) { if (event.target.classList.contains("modal")) event.target.style.display = "none"; };
        </script>
JS;
        unset($_SESSION['reply_errors'], $_SESSION['reply_data']);
        return $content;
    }

    private function uploadReplyImage($file): ?string {
        if ($file['error'] !== UPLOAD_ERR_OK) return null;
        $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        if (!in_array($mimeType, $allowedTypes)) return null;
        if ($file['size'] > 2 * 1024 * 1024) return null;
        $uploadDir = __DIR__ . '/../uploads/replies/';
        if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
        $extension    = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename     = 'reply_' . time() . '_' . uniqid() . '.' . $extension;
        $relativePath = 'uploads/replies/' . $filename;
        return move_uploaded_file($file['tmp_name'], $uploadDir . $filename) ? $relativePath : null;
    }

    private function addReply($articleId): void {
        require_once __DIR__ . '/../models/Reply.php';
        $replyModel  = new Reply();
        $auteur      = $_SESSION['user_name'] ?? 'Anonyme';
        $userId      = $_SESSION['user_id']   ?? null;
        $contenuText = trim($_POST['reply_text']  ?? '');
        $emoji       = trim($_POST['reply_emoji'] ?? '');
        $imagePath   = null;
        $errors      = [];
        if (isset($_FILES['reply_image']) && $_FILES['reply_image']['error'] === UPLOAD_ERR_OK) {
            $imagePath = $this->uploadReplyImage($_FILES['reply_image']);
            if (!$imagePath) $errors['image'] = "Format d'image non supporté. Formats acceptés : JPG, PNG, GIF. Max 2 Mo.";
        }
        if (empty($contenuText) && empty($emoji) && empty($imagePath)) {
            $errors['general'] = 'Veuillez écrire un commentaire, ajouter un emoji ou une image.';
        }
        if (!empty($errors)) {
            $_SESSION['reply_errors'] = $errors;
            $_SESSION['reply_data']   = ['text' => $contenuText, 'emoji' => $emoji];
            header("Location: index.php?page=detail_article_public&id=$articleId#comment-form");
            exit;
        }
        $result = $replyModel->createMixte($articleId, $contenuText, $emoji, $imagePath, $auteur, $userId);
        if ($result) {
            $_SESSION['success'] = 'Commentaire ajouté avec succès !';
            unset($_SESSION['reply_errors'], $_SESSION['reply_data']);
        } else {
            $_SESSION['error'] = 'Erreur lors de l\'ajout du commentaire.';
        }
        header("Location: index.php?page=detail_article_public&id=$articleId");
        exit;
    }

    private function getDeleteScript(): string {
        $script = '
        <style>
            .custom-modal { display:none;position:fixed;z-index:10000;left:0;top:0;width:100%;height:100%;background-color:rgba(0,0,0,0.5);backdrop-filter:blur(3px);align-items:center;justify-content:center; }
            .custom-modal.show { display:flex; }
            .custom-modal-content { background:white;border-radius:20px;width:90%;max-width:450px;box-shadow:0 20px 60px rgba(0,0,0,0.3);animation:modalSlideIn 0.3s ease;overflow:hidden; }
            @keyframes modalSlideIn { from { opacity:0;transform:translateY(-50px); } to { opacity:1;transform:translateY(0); } }
            .custom-modal-header { background:linear-gradient(135deg,#dc3545 0%,#c82333 100%);color:white;padding:20px;text-align:center; }
            .custom-modal-header i { font-size:50px;margin-bottom:10px; }
            .custom-modal-header h3 { margin:0;font-size:1.5rem; }
            .custom-modal-body { padding:25px;text-align:center; }
            .custom-modal-body p { margin:10px 0;color:#555; }
            .custom-modal-body .warning-text { color:#dc3545;font-size:13px;background:#ffe6e6;padding:10px;border-radius:8px;margin-top:15px; }
            .custom-modal-footer { padding:15px 20px;display:flex;gap:10px;justify-content:center;border-top:1px solid #eee; }
            .custom-modal-footer button { padding:10px 25px;border:none;border-radius:25px;cursor:pointer;font-size:14px;font-weight:600;transition:all 0.3s; }
            .custom-modal-footer .btn-confirm { background:linear-gradient(135deg,#dc3545 0%,#c82333 100%);color:white; }
            .custom-modal-footer .btn-cancel  { background:#f0f0f0;color:#666; }
            .article-title-delete { font-weight:bold;color:#2A7FAA;background:#e8f4f8;padding:8px;border-radius:8px;margin:10px 0; }
        </style>
        <div id="deleteModal" class="custom-modal">
            <div class="custom-modal-content">
                <div class="custom-modal-header"><i class="fas fa-trash-alt"></i><h3>Confirmer la suppression</h3></div>
                <div class="custom-modal-body">
                    <p>Êtes-vous sûr de vouloir supprimer l\'article :</p>
                    <div class="article-title-delete" id="deleteArticleTitle"></div>
                    <div class="warning-text"><i class="fas fa-exclamation-triangle"></i> Cette action est irréversible et supprimera également tous les commentaires associés.</div>
                </div>
                <div class="custom-modal-footer">
                    <button class="btn-cancel" onclick="closeDeleteModal()"><i class="fas fa-times"></i> Annuler</button>
                    <button class="btn-confirm" id="confirmDeleteBtn"><i class="fas fa-trash"></i> Supprimer</button>
                </div>
            </div>
        </div>';
        $script .= <<<'JS'
        <script>
        let currentDeleteId = null;
        function confirmDeleteArticle(articleId, articleTitle) {
            currentDeleteId = articleId;
            document.getElementById("deleteArticleTitle").textContent = articleTitle;
            document.getElementById("deleteModal").classList.add("show");
        }
        function closeDeleteModal() { document.getElementById("deleteModal").classList.remove("show"); currentDeleteId = null; }
        document.getElementById("confirmDeleteBtn").onclick = function() {
            if (currentDeleteId) window.location.href = "index.php?page=articles_admin&action=delete&id=" + currentDeleteId;
            closeDeleteModal();
        };
        document.getElementById("deleteModal").onclick = function(e) { if (e.target === this) closeDeleteModal(); };
        </script>
JS;
        return $script;
    }

    public function listeArticles(): void { $this->blogList(); }
    public function detailArticle($id): void { $this->blogDetail($id); }

    // =============================================
    // CRUD ARTICLES
    // =============================================

public function adminArticleCreate(): void {
    $this->requireLogin();
    
    require_once __DIR__ . '/../models/Article.php';
    $articleModel = new Article();
    $userRole = $_SESSION['user_role'] ?? '';
    $errors   = [];
    $oldData  = [];
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $titre     = trim($_POST['titre']   ?? '');
        $contenu   = trim($_POST['contenu'] ?? '');
        $auteur_id = $_SESSION['user_id'] ?? null;
        $oldData   = ['titre' => $titre, 'contenu' => $contenu];
        
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
        
        $imagePath = null;
        if (isset($_FILES['article_image']) && $_FILES['article_image']['error'] === UPLOAD_ERR_OK) {
            $imagePath = $this->uploadArticleImage($_FILES['article_image']);
            if (!$imagePath) {
                $errors['image'] = 'Erreur lors de l\'upload. Formats acceptés : JPG, PNG, GIF. Max 2 Mo.';
            }
        }
        
        if (empty($errors)) {
            $result = $articleModel->create([
                'titre' => $titre, 
                'contenu' => $contenu, 
                'auteur_id' => $auteur_id, 
                'image' => $imagePath,
                'status' => 'publié'
            ]);
            if ($result > 0) { 
                $_SESSION['success'] = 'Article créé avec succès !'; 
                header('Location: index.php?page=blog_public'); 
                exit; 
            } else {
                $errors['general'] = 'Erreur lors de la création de l\'article.';
            }
        }
    }
    
    // Selon le rôle, afficher le bon formulaire
    if ($userRole === 'admin') {
        // Admin : formulaire avec sidebar (backoffice)
        $content = $this->getAdminArticleFormHTML('Créer un article', 'admin_article_create', null, $errors, $oldData);
        $this->renderAdminLayout('Créer un article', $content, 'articles');
    } else {
        // Médecins et patients : formulaire public stylisé
        $content = $this->getUserArticleFormHTML('Créer un article', 'admin_article_create', null, $errors, $oldData);
        $this->renderPublicView('Créer un article', $content);
    }
}
    public function adminArticleEdit($id): void {
        $this->requireLogin();
        require_once __DIR__ . '/../models/Article.php';
        $articleModel = new Article();
        $article = $articleModel->getById($id);
        if (!$article) { $_SESSION['error'] = 'Article non trouvé.'; header('Location: index.php?page=blog_public'); exit; }
        $userId   = $_SESSION['user_id']   ?? null;
        $userRole = $_SESSION['user_role'] ?? '';
        $isAdmin  = ($userRole === 'admin');
        $isAuthor = ($userId && isset($article['auteur_id']) && $userId == $article['auteur_id']);
        if (!$isAdmin && !$isAuthor) { $_SESSION['error'] = "Vous n'êtes pas autorisé à modifier cet article."; header('Location: index.php?page=blog_public'); exit; }
        $errors = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $titre     = trim($_POST['titre']   ?? '');
            $contenu   = trim($_POST['contenu'] ?? '');
            $categorie = $isAdmin ? trim($_POST['categorie'] ?? '') : $article['categorie'];
            $tags      = $isAdmin ? trim($_POST['tags']      ?? '') : $article['tags'];
            $status    = $isAdmin ? trim($_POST['status']    ?? 'publié') : $article['status'];
            if (empty($titre))                $errors['titre']   = 'Le titre est obligatoire.';
            elseif (mb_strlen($titre) > 255)  $errors['titre']   = 'Le titre ne doit pas dépasser 255 caractères.';
            if (empty($contenu))              $errors['contenu'] = 'Le contenu est obligatoire.';
            elseif (mb_strlen($contenu) < 10) $errors['contenu'] = 'Le contenu doit contenir au moins 10 caractères.';
            $imagePath = $article['image'];
            if (isset($_POST['delete_image']) && $_POST['delete_image'] == '1') {
                if ($imagePath && file_exists(__DIR__ . '/../' . $imagePath)) unlink(__DIR__ . '/../' . $imagePath);
                $imagePath = null;
            }
            if (isset($_FILES['article_image']) && $_FILES['article_image']['error'] === UPLOAD_ERR_OK) {
                if ($imagePath && file_exists(__DIR__ . '/../' . $imagePath)) unlink(__DIR__ . '/../' . $imagePath);
                $imagePath = $this->uploadArticleImage($_FILES['article_image']);
                if (!$imagePath) $errors['image'] = 'Erreur lors de l\'upload de l\'image.';
            }
            if (empty($errors)) {
                $result = $articleModel->updateFull($id, $titre, $contenu, $article['auteur_id'], $imagePath, $categorie, $tags, $status);
                if ($result) { $_SESSION['success'] = 'Article modifié avec succès !'; header('Location: index.php?page=blog_public'); exit; }
                else $errors['general'] = 'Erreur lors de la modification.';
            }
        }
        if ($isAdmin) {
            $content = $this->getAdminArticleFormHTML('Modifier l\'article', 'admin_article_edit&id=' . $id, $article, $errors);
            $this->renderAdminLayout('Modifier un article', $content, 'articles');
        } else {
            $content = $this->getUserArticleFormHTML('Modifier mon article', 'admin_article_edit&id=' . $id, $article, $errors);
            $this->renderPublicView('Modifier mon article', $content);
        }
    }
    private function getAdminArticleFormHTML($title, $action, $article = null, $errors = [], $oldData = []): string {
    $isEdit         = $article !== null;
    $titreValue     = $isEdit ? htmlspecialchars($article['titre']     ?? '') : htmlspecialchars($oldData['titre']     ?? '');
    $contenuValue   = $isEdit ? htmlspecialchars($article['contenu']   ?? '') : htmlspecialchars($oldData['contenu']   ?? '');
    $imageValue     = $isEdit ? htmlspecialchars($article['image']     ?? '') : '';
    $categorieValue = $isEdit ? htmlspecialchars($article['categorie'] ?? '') : htmlspecialchars($oldData['categorie'] ?? '');
    $tagsValue      = $isEdit ? htmlspecialchars($article['tags']      ?? '') : htmlspecialchars($oldData['tags']      ?? '');
    $statusValue    = $isEdit ? ($article['status'] ?? 'publié') : ($oldData['status'] ?? 'publié');
    $buttonText     = $isEdit ? 'Mettre à jour' : 'Publier';
    
    return '
    <style>
        .form-container {
            background: white;
            border-radius: 16px;
            overflow: hidden;
        }
        .form-header {
            background: linear-gradient(135deg, #2A7FAA 0%, #4CAF50 100%);
            padding: 20px 25px;
            color: white;
        }
        .form-header h4 {
            margin: 0;
            font-size: 1.3rem;
            font-weight: 600;
        }
        .form-header p {
            margin: 8px 0 0;
            opacity: 0.9;
            font-size: 0.85rem;
        }
        .form-body {
            padding: 25px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-label {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 6px;
            display: block;
            font-size: 0.9rem;
        }
        .form-label .required {
            color: #dc3545;
            margin-left: 3px;
        }
        .form-control-custom {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
        }
        .form-control-custom:focus {
            border-color: #2A7FAA;
            outline: none;
            box-shadow: 0 0 0 3px rgba(42,127,170,0.1);
        }
        .form-control-custom.error {
            border-color: #dc3545;
            background-color: #fff8f8;
        }
        textarea.form-control-custom {
            resize: vertical;
            min-height: 200px;
        }
        .image-upload-area {
            border: 1px dashed #ddd;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            background: #fafbfc;
        }
        .image-upload-area:hover {
            border-color: #2A7FAA;
            background: #f0f6ff;
        }
        .image-upload-area i {
            font-size: 1.5rem;
            color: #2A7FAA;
            margin-bottom: 8px;
        }
        .image-upload-area p {
            margin: 0;
            font-size: 0.8rem;
            color: #666;
        }
        .image-preview {
            margin-top: 10px;
            text-align: center;
        }
        .image-preview img {
            max-width: 150px;
            border-radius: 8px;
            border: 1px solid #ddd;
        }
        .field-error {
            font-size: 11px;
            margin-top: 5px;
            color: #dc3545;
        }
        .row-2cols {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        @media (max-width: 768px) {
            .row-2cols {
                grid-template-columns: 1fr;
                gap: 15px;
            }
        }
        .tag-hint {
            font-size: 11px;
            color: #999;
            margin-top: 5px;
        }
        .btn-cancel {
            background: #6c757d;
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 8px;
            font-weight: 500;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
        }
        .btn-cancel:hover {
            background: #5a6268;
            color: white;
        }
        .btn-submit {
            background: linear-gradient(135deg, #2A7FAA 0%, #4CAF50 100%);
            color: white;
            border: none;
            padding: 10px 30px;
            border-radius: 8px;
            font-weight: 500;
            font-size: 14px;
            cursor: pointer;
        }
        .btn-submit:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(42,127,170,0.3);
        }
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        hr {
            margin: 20px 0;
            border-color: #eee;
        }
        .image-checkbox {
            margin-top: 10px;
            font-size: 13px;
        }
    </style>
    
    <div class="form-container">
        <div class="form-header">
            <h4><i class="fas fa-' . ($isEdit ? 'edit' : 'plus-circle') . ' me-2"></i>' . htmlspecialchars($title) . '</h4>
            <p>Remplissez le formulaire pour ' . ($isEdit ? 'modifier' : 'créer') . ' un article</p>
        </div>
        <div class="form-body">
            ' . (isset($errors['general']) ? '<div class="alert alert-danger">' . htmlspecialchars($errors['general']) . '</div>' : '') . '
            
            <form method="POST" action="index.php?page=' . $action . '" enctype="multipart/form-data">
                <div class="row-2cols">
                    <div class="form-group">
                        <label class="form-label">Titre <span class="required">*</span></label>
                        <input type="text" class="form-control-custom ' . (isset($errors['titre']) ? 'error' : '') . '" 
                               name="titre" value="' . $titreValue . '" 
                               placeholder="Entrez le titre de votre article">
                        ' . (isset($errors['titre']) ? '<div class="field-error">' . htmlspecialchars($errors['titre']) . '</div>' : '') . '
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Tags</label>
                        <input type="text" class="form-control-custom" name="tags" 
                               value="' . $tagsValue . '" 
                               placeholder="santé, bien-être, médecine">
                        <div class="tag-hint">Séparez les tags par des virgules</div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Image à la une</label>
                    <div class="image-upload-area" onclick="document.getElementById(\'imageInput\').click()">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <p>Cliquez ou glissez une image ici</p>
                        <small>JPG, PNG, GIF. Max 2 Mo.</small>
                        <input type="file" id="imageInput" name="article_image" accept="image/*" style="display:none" onchange="previewImage(this)">
                    </div>
                    ' . ($imageValue ? '
                    <div class="image-preview" id="imagePreview">
                        <img src="' . $imageValue . '" alt="Image actuelle">
                        <div class="image-checkbox">
                            <label><input type="checkbox" name="delete_image" value="1"> Supprimer l\'image actuelle</label>
                        </div>
                    </div>' : '<div class="image-preview" id="imagePreview" style="display:none"></div>') . '
                    ' . (isset($errors['image']) ? '<div class="field-error">' . htmlspecialchars($errors['image']) . '</div>' : '') . '
                </div>
                
                <div class="form-group">
                    <label class="form-label">Contenu <span class="required">*</span></label>
                    <textarea class="form-control-custom ' . (isset($errors['contenu']) ? 'error' : '') . '" 
                              name="contenu" rows="10" 
                              placeholder="Écrivez le contenu de votre article...">' . $contenuValue . '</textarea>
                    ' . (isset($errors['contenu']) ? '<div class="field-error">' . htmlspecialchars($errors['contenu']) . '</div>' : '') . '
                </div>
                
                <div class="row-2cols">
                    <div class="form-group">
                        <label class="form-label">Catégorie</label>
                        <select name="categorie" class="form-control-custom">
                            <option value="">-- Sélectionner une catégorie --</option>
                            <option value="actualite"  ' . ($categorieValue == 'actualite'  ? 'selected' : '') . '>Actualité</option>
                            <option value="conseil"    ' . ($categorieValue == 'conseil'    ? 'selected' : '') . '>Conseil santé</option>
                            <option value="temoignage" ' . ($categorieValue == 'temoignage' ? 'selected' : '') . '>Témoignage</option>
                            <option value="evenement"  ' . ($categorieValue == 'evenement'  ? 'selected' : '') . '>Événement</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Statut</label>
                        <select name="status" class="form-control-custom">
                            <option value="brouillon" ' . ($statusValue == 'brouillon' ? 'selected' : '') . '>Brouillon</option>
                            <option value="publié"    ' . ($statusValue == 'publié'    ? 'selected' : '') . '>Publié</option>
                            <option value="archive"   ' . ($statusValue == 'archive'   ? 'selected' : '') . '>Archivé</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-actions">
                    <a href="index.php?page=blog_public" class="btn-cancel">Annuler</a>
                    <button type="submit" class="btn-submit">' . $buttonText . '</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
    function previewImage(input) {
        var preview = document.getElementById("imagePreview");
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                preview.innerHTML = \'<img src="\' + e.target.result + \'" alt="Aperçu">\';
                preview.style.display = "block";
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
    </script>';
}

    public function adminArticleDelete($id): void {
        $this->requireLogin();
        require_once __DIR__ . '/../models/Article.php';
        $articleModel = new Article();
        $article = $articleModel->getById($id);
        if (!$article) {
            $_SESSION['error'] = 'Article non trouvé.';
            header('Location: index.php?page=blog_public');
            exit;
        }
        $userId   = $_SESSION['user_id']   ?? null;
        $userRole = $_SESSION['user_role'] ?? '';
        $isAdmin  = ($userRole === 'admin');
        $isAuthor = ($userId && isset($article['auteur_id']) && $userId == $article['auteur_id']);
        if (!$isAdmin && !$isAuthor) {
            $_SESSION['error'] = 'Vous n\'êtes pas autorisé à supprimer cet article.';
            header('Location: index.php?page=blog_public');
            exit;
        }
        $result = $articleModel->delete($id);
        $_SESSION[$result ? 'success' : 'error'] = $result
            ? 'Article supprimé avec succès !'
            : 'Erreur lors de la suppression de l\'article.';
        header('Location: index.php?page=blog_public');
        exit;
    }

    private function uploadArticleImage($file): ?string {
        if ($file['error'] !== UPLOAD_ERR_OK) return null;
        $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        if (!in_array($mimeType, $allowedTypes)) return null;
        if ($file['size'] > 2 * 1024 * 1024) return null;
        $uploadDir = __DIR__ . '/../uploads/articles/';
        if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
        $extension    = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename     = 'article_' . time() . '_' . uniqid() . '.' . $extension;
        $relativePath = 'uploads/articles/' . $filename;
        return move_uploaded_file($file['tmp_name'], $uploadDir . $filename) ? $relativePath : null;
    }

private function getUserArticleFormHTML($title, $action, $article = null, $errors = [], $oldData = []): string {
    $isEdit       = $article !== null;
    $titreValue   = $isEdit ? htmlspecialchars($article['titre']   ?? '') : htmlspecialchars($oldData['titre']   ?? '');
    $contenuValue = $isEdit ? htmlspecialchars($article['contenu'] ?? '') : htmlspecialchars($oldData['contenu'] ?? '');
    $imageValue   = $isEdit ? htmlspecialchars($article['image']   ?? '') : '';
    $tagsValue    = $isEdit ? htmlspecialchars($article['tags']    ?? '') : '';
    $emojiValue   = $isEdit ? htmlspecialchars($article['emoji']   ?? '') : htmlspecialchars($oldData['emoji'] ?? '');
    $buttonText   = $isEdit ? 'Mettre à jour' : 'Publier';
    
    return '
    <style>
        .form-container {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .form-header {
            background: linear-gradient(135deg, #2A7FAA 0%, #4CAF50 100%);
            padding: 20px 25px;
            color: white;
        }
        .form-header h4 {
            margin: 0;
            font-size: 1.3rem;
            font-weight: 600;
        }
        .form-header p {
            margin: 8px 0 0;
            opacity: 0.9;
            font-size: 0.85rem;
        }
        .form-body {
            padding: 25px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-label {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 6px;
            display: block;
            font-size: 0.9rem;
        }
        .form-label .required {
            color: #dc3545;
            margin-left: 3px;
        }
        .form-control-custom {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
        }
        .form-control-custom:focus {
            border-color: #2A7FAA;
            outline: none;
            box-shadow: 0 0 0 3px rgba(42,127,170,0.1);
        }
        .form-control-custom.error {
            border-color: #dc3545;
            background-color: #fff8f8;
        }
        textarea.form-control-custom {
            resize: vertical;
            min-height: 200px;
        }
        .image-upload-area {
            border: 1px dashed #ddd;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            background: #fafbfc;
        }
        .image-upload-area:hover {
            border-color: #2A7FAA;
            background: #f0f6ff;
        }
        .image-upload-area i {
            font-size: 1.5rem;
            color: #2A7FAA;
            margin-bottom: 8px;
        }
        .image-upload-area p {
            margin: 0;
            font-size: 0.8rem;
            color: #666;
        }
        .image-preview {
            margin-top: 10px;
            text-align: center;
        }
        .image-preview img {
            max-width: 150px;
            border-radius: 8px;
            border: 1px solid #ddd;
        }
        .field-error {
            font-size: 11px;
            margin-top: 5px;
            color: #dc3545;
        }
        .row-2cols {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .row-3cols {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
        }
        @media (max-width: 768px) {
            .row-2cols, .row-3cols {
                grid-template-columns: 1fr;
                gap: 15px;
            }
        }
        .tag-hint {
            font-size: 11px;
            color: #999;
            margin-top: 5px;
        }
        .btn-cancel {
            background: #6c757d;
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 8px;
            font-weight: 500;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
        }
        .btn-cancel:hover {
            background: #5a6268;
            color: white;
        }
        .btn-submit {
            background: linear-gradient(135deg, #2A7FAA 0%, #4CAF50 100%);
            color: white;
            border: none;
            padding: 10px 30px;
            border-radius: 8px;
            font-weight: 500;
            font-size: 14px;
            cursor: pointer;
        }
        .btn-submit:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(42,127,170,0.3);
        }
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        .image-checkbox {
            margin-top: 10px;
            font-size: 13px;
        }
        .emoji-picker {
            position: relative;
        }
        .emoji-btn {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            font-size: 1.2rem;
            cursor: pointer;
            padding: 5px;
        }
        .emoji-list {
            position: absolute;
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 10px;
            display: none;
            grid-template-columns: repeat(8, 1fr);
            gap: 5px;
            z-index: 100;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            max-width: 300px;
        }
        .emoji-list span {
            cursor: pointer;
            font-size: 1.2rem;
            padding: 5px;
            text-align: center;
            border-radius: 5px;
        }
        .emoji-list span:hover {
            background: #f0f0f0;
        }
    </style>
    
    <div class="form-container">
        <div class="form-header">
            <h4><i class="fas fa-' . ($isEdit ? 'edit' : 'plus-circle') . ' me-2"></i>' . htmlspecialchars($title) . '</h4>
            <p>Remplissez le formulaire pour ' . ($isEdit ? 'modifier' : 'créer') . ' un article</p>
        </div>
        <div class="form-body">
            ' . (isset($errors['general']) ? '<div class="alert alert-danger">' . htmlspecialchars($errors['general']) . '</div>' : '') . '
            
            <form method="POST" action="index.php?page=' . $action . '" enctype="multipart/form-data">
                <div class="row-3cols">
                    <div class="form-group">
                        <label class="form-label">Titre <span class="required">*</span></label>
                        <input type="text" class="form-control-custom ' . (isset($errors['titre']) ? 'error' : '') . '" 
                               name="titre" value="' . $titreValue . '" 
                               placeholder="Entrez le titre de votre article">
                        ' . (isset($errors['titre']) ? '<div class="field-error">' . htmlspecialchars($errors['titre']) . '</div>' : '') . '
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Tags</label>
                        <input type="text" class="form-control-custom" name="tags" 
                               value="' . $tagsValue . '" 
                               placeholder="santé, bien-être, médecine">
                        <div class="tag-hint">Séparez les tags par des virgules</div>
                    </div>
                    
                    <div class="form-group emoji-picker">
                        <label class="form-label">Emoji</label>
                        <div style="position: relative;">
                            <input type="text" class="form-control-custom" name="emoji" 
                                   id="emojiInput" value="' . $emojiValue . '" 
                                   placeholder="😊 🏥 💊" style="padding-right: 40px;">
                            <button type="button" class="emoji-btn" onclick="toggleEmojiPicker()">😀</button>
                            <div id="emojiList" class="emoji-list">
                                <span onclick="addEmoji(\'😊\')">😊</span>
                                <span onclick="addEmoji(\'😂\')">😂</span>
                                <span onclick="addEmoji(\'❤️\')">❤️</span>
                                <span onclick="addEmoji(\'👍\')">👍</span>
                                <span onclick="addEmoji(\'🎉\')">🎉</span>
                                <span onclick="addEmoji(\'🏥\')">🏥</span>
                                <span onclick="addEmoji(\'💊\')">💊</span>
                                <span onclick="addEmoji(\'🩺\')">🩺</span>
                                <span onclick="addEmoji(\'👨‍⚕️\')">👨‍⚕️</span>
                                <span onclick="addEmoji(\'👩‍⚕️\')">👩‍⚕️</span>
                                <span onclick="addEmoji(\'💉\')">💉</span>
                                <span onclick="addEmoji(\'🩹\')">🩹</span>
                                <span onclick="addEmoji(\'📋\')">📋</span>
                                <span onclick="addEmoji(\'✅\')">✅</span>
                                <span onclick="addEmoji(\'⭐\')">⭐</span>
                                <span onclick="addEmoji(\'🔥\')">🔥</span>
                                <span onclick="addEmoji(\'💡\')">💡</span>
                                <span onclick="addEmoji(\'📢\')">📢</span>
                                <span onclick="addEmoji(\'🔔\')">🔔</span>
                                <span onclick="addEmoji(\'💪\')">💪</span>
                                <span onclick="addEmoji(\'🤝\')">🤝</span>
                                <span onclick="addEmoji(\'🙏\')">🙏</span>
                                <span onclick="addEmoji(\'😷\')">😷</span>
                                <span onclick="addEmoji(\'🤒\')">🤒</span>
                            </div>
                        </div>
                        <div class="tag-hint">Ajoutez un emoji pour illustrer votre article</div>
                        ' . (isset($errors['emoji']) ? '<div class="field-error">' . htmlspecialchars($errors['emoji']) . '</div>' : '') . '
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Image à la une</label>
                    <div class="image-upload-area" onclick="document.getElementById(\'imageInput\').click()">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <p>Cliquez ou glissez une image ici</p>
                        <small>JPG, PNG, GIF. Max 2 Mo.</small>
                        <input type="file" id="imageInput" name="article_image" accept="image/*" style="display:none" onchange="previewImage(this)">
                    </div>
                    ' . ($imageValue ? '
                    <div class="image-preview" id="imagePreview">
                        <img src="' . $imageValue . '" alt="Image actuelle">
                        <div class="image-checkbox">
                            <label><input type="checkbox" name="delete_image" value="1"> Supprimer l\'image actuelle</label>
                        </div>
                    </div>' : '<div class="image-preview" id="imagePreview" style="display:none"></div>') . '
                    ' . (isset($errors['image']) ? '<div class="field-error">' . htmlspecialchars($errors['image']) . '</div>' : '') . '
                </div>
                
                <div class="form-group">
                    <label class="form-label">Contenu <span class="required">*</span></label>
                    <textarea class="form-control-custom ' . (isset($errors['contenu']) ? 'error' : '') . '" 
                              name="contenu" rows="10" 
                              placeholder="Écrivez le contenu de votre article...">' . $contenuValue . '</textarea>
                    ' . (isset($errors['contenu']) ? '<div class="field-error">' . htmlspecialchars($errors['contenu']) . '</div>' : '') . '
                </div>
                
                <div class="form-actions">
                    <a href="index.php?page=blog_public" class="btn-cancel">Annuler</a>
                    <button type="submit" class="btn-submit">' . $buttonText . '</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
    function previewImage(input) {
        var preview = document.getElementById("imagePreview");
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                preview.innerHTML = \'<img src="\' + e.target.result + \'" alt="Aperçu">\';
                preview.style.display = "block";
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
    
    function toggleEmojiPicker() {
        var picker = document.getElementById("emojiList");
        if (picker.style.display === "grid") {
            picker.style.display = "none";
        } else {
            picker.style.display = "grid";
        }
    }
    
    function addEmoji(emoji) {
        var input = document.getElementById("emojiInput");
        input.value += emoji;
        document.getElementById("emojiList").style.display = "none";
    }
    
    document.addEventListener("click", function(event) {
        var picker = document.getElementById("emojiList");
        var btn = event.target.closest(".emoji-btn");
        if (!btn && picker && picker.style.display === "grid") {
            picker.style.display = "none";
        }
    });
    </script>';
}


    // =============================================
    // ÉVÉNEMENTS / CONTACT / À PROPOS
    // =============================================

    public function listeEvenements(): void {
        require_once __DIR__ . '/../models/Event.php';
        require_once __DIR__ . '/../models/Participation.php';
        
        $eventModel = new Event();
        $participationModel = new Participation();
        
        $allEvents = $eventModel->getAll();
        
        // Récupérer les catégories uniques
        $categories = [];
        foreach ($allEvents as $event) {
            if (!empty($event['contenu']) && strpos($event['contenu'], 'categorie') !== false) {
                // Extraction simple - on peut améliorer
            }
        }
        
        // Filtrer par catégorie si demandé
        $selectedCategory = $_GET['category'] ?? null;
        $events = $allEvents;
        
        // Compter les événements pour l'utilisateur
        $myEventCount = 0;
        if (!empty($_SESSION['user_id'])) {
            $myEvents = $participationModel->getByUser($_SESSION['user_id']);
            $myEventCount = count($myEvents);
        }
        
        $content = '
        <style>
            .events-header {
                margin-bottom: 30px;
            }
            .events-title {
                font-size: 2.2rem;
                font-weight: bold;
                color: #1a1a1a;
                margin-bottom: 10px;
            }
            .events-count {
                color: #666;
                font-size: 1rem;
                margin-bottom: 20px;
            }
            .filters {
                display: flex;
                gap: 10px;
                flex-wrap: wrap;
                margin-bottom: 25px;
            }
            .filter-btn {
                padding: 8px 18px;
                border: 1px solid #ddd;
                background: white;
                border-radius: 25px;
                cursor: pointer;
                font-size: 0.95rem;
                transition: all 0.3s;
                color: #666;
            }
            .filter-btn.active {
                background: #17a2b8;
                color: white;
                border-color: #17a2b8;
            }
            .filter-btn:hover {
                border-color: #17a2b8;
                color: #17a2b8;
            }
            .my-events-banner {
                background: #e8f4f9;
                border-left: 4px solid #17a2b8;
                padding: 15px 20px;
                border-radius: 8px;
                margin-bottom: 25px;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            .my-events-banner i {
                color: #17a2b8;
                font-size: 1.3rem;
                margin-right: 15px;
            }
            .my-events-text {
                flex: 1;
            }
            .my-events-text p {
                margin: 0;
                color: #333;
                font-size: 0.95rem;
            }
            .my-events-btn {
                background: #17a2b8;
                color: white;
                border: none;
                padding: 10px 25px;
                border-radius: 25px;
                cursor: pointer;
                font-weight: 600;
                white-space: nowrap;
                margin-left: 15px;
                text-decoration: none;
                display: inline-block;
                transition: all 0.3s;
            }
            .my-events-btn:hover {
                background: #138496;
                color: white;
            }
            .events-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
                gap: 25px;
                margin-bottom: 30px;
            }
            .event-card {
                background: white;
                border-radius: 10px;
                overflow: hidden;
                box-shadow: 0 2px 10px rgba(0,0,0,0.08);
                transition: all 0.3s;
                display: flex;
                flex-direction: column;
            }
            .event-card:hover {
                box-shadow: 0 5px 20px rgba(0,0,0,0.12);
                transform: translateY(-3px);
            }
            .event-image-container {
                position: relative;
                width: 100%;
                height: 180px;
                background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
                overflow: hidden;
            }
            .event-image {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }
            .event-category-badge {
                position: absolute;
                top: 12px;
                right: 12px;
                background: #28a745;
                color: white;
                padding: 6px 14px;
                border-radius: 20px;
                font-size: 0.8rem;
                font-weight: 600;
            }
            .event-card-content {
                padding: 18px;
                flex: 1;
                display: flex;
                flex-direction: column;
            }
            .event-title {
                font-size: 1.15rem;
                font-weight: bold;
                color: #1a1a1a;
                margin-bottom: 10px;
            }
            .event-description {
                color: #666;
                font-size: 0.9rem;
                margin-bottom: 12px;
                line-height: 1.4;
            }
            .event-meta {
                display: flex;
                align-items: center;
                margin-bottom: 8px;
                color: #666;
                font-size: 0.9rem;
            }
            .event-meta i {
                color: #17a2b8;
                width: 18px;
                margin-right: 10px;
            }
            .event-places-price {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-top: 12px;
                padding-top: 12px;
                border-top: 1px solid #f0f0f0;
            }
            .event-places {
                color: #666;
                font-size: 0.9rem;
            }
            .event-places i {
                color: #17a2b8;
                margin-right: 5px;
            }
            .event-price {
                font-size: 1.1rem;
                font-weight: bold;
                color: #17a2b8;
            }
            .event-sponsor {
                color: #999;
                font-size: 0.85rem;
                margin-top: 8px;
            }
            .event-sponsor i {
                margin-right: 5px;
                color: #17a2b8;
            }
            .event-button {
                background: #17a2b8;
                color: white;
                border: none;
                padding: 10px;
                border-radius: 6px;
                cursor: pointer;
                font-size: 0.95rem;
                font-weight: 600;
                margin-top: 12px;
                width: 100%;
                transition: all 0.3s;
                text-decoration: none;
                display: inline-block;
                text-align: center;
            }
            .event-button:hover {
                background: #138496;
                color: white;
            }
            .empty-state {
                text-align: center;
                padding: 60px 20px;
                color: #999;
            }
            .empty-state i {
                font-size: 3rem;
                color: #ddd;
                margin-bottom: 15px;
            }
        </style>
        
        <div class="events-header">
            <div class="events-title">Événements médicaux</div>
            <div class="events-count">' . count($events) . ' événement(s) disponible(s)</div>
            
            <div class="filters">
                <button class="filter-btn ' . (!$selectedCategory ? 'active' : '') . '" onclick="window.location=\'index.php?page=evenements\'">Toutes</button>
                <button class="filter-btn">Dermatologie</button>
                <button class="filter-btn">Cardiologie</button>
                <button class="filter-btn">Médecine Générale</button>
                <button class="filter-btn">Pédiatrie</button>
            </div>
        </div>';
        
        // Afficher le banneau "Mes inscriptions" si connecté
        if (!empty($_SESSION['user_id'])) {
            $content .= '
            <div class="my-events-banner">
                <div style="display: flex; align-items: center;">
                    <i class="fas fa-user-circle"></i>
                    <div class="my-events-text">
                        <p><strong>Déjà inscrit(e) à un événement ?</strong> Consultez, modifiez ou annulez vos inscriptions depuis votre espace personnel.</p>
                    </div>
                </div>
                <a href="index.php?page=mes_inscriptions_evenements" class="my-events-btn">
                    Mes Inscriptions <i class="fas fa-arrow-right" style="margin-left: 8px;"></i>
                </a>
            </div>';
        }
        
        if (empty($events)) {
            $content .= '
            <div class="empty-state">
                <i class="fas fa-calendar-times"></i>
                <p>Aucun événement disponible pour le moment.</p>
            </div>';
        } else {
            $content .= '<div class="events-grid">';
            foreach ($events as $event) {
                $dateDebut = new DateTime($event['date_debut']);
                $dateFin = new DateTime($event['date_fin']);
                
                $dateText = $dateDebut->format('d/m/Y');
                if ($dateDebut->format('d/m/Y') !== $dateFin->format('d/m/Y')) {
                    $dateText .= ' – ' . $dateFin->format('d/m/Y');
                }
                
                $content .= '
                <div class="event-card">
                    <div class="event-image-container">
                        ' . (!empty($event['image']) ? '<img src="' . htmlspecialchars($event['image']) . '" alt="' . htmlspecialchars($event['titre']) . '" class="event-image">' : '') . '
                        <div class="event-category-badge">Planifié</div>
                    </div>
                    <div class="event-card-content">
                        <div class="event-title">' . htmlspecialchars($event['titre']) . '</div>
                        <div class="event-description">' . htmlspecialchars(substr($event['description'] ?? '', 0, 80)) . (strlen($event['description'] ?? '') > 80 ? '....' : '') . '</div>
                        
                        <div class="event-meta">
                            <i class="fas fa-map-marker-alt"></i>
                            <span>' . htmlspecialchars($event['lieu'] ?? 'Lieu non spécifié') . '</span>
                        </div>
                        <div class="event-meta">
                            <i class="fas fa-calendar-alt"></i>
                            <span>' . $dateText . '</span>
                        </div>
                        
                        <div class="event-places-price">
                            <div class="event-places">
                                <i class="fas fa-users"></i>
                                <span>' . ($event['capacite_max'] ?? 0) . ' places</span>
                            </div>
                            <div class="event-price">' . ($event['prix'] ?? '0') . ',00 TND</div>
                        </div>
                        
                        ' . (!empty($event['sponsor']) ? '<div class="event-sponsor"><i class="fas fa-link"></i>Sponsor : ' . htmlspecialchars($event['sponsor']) . '</div>' : '') . '
                        
                        <a href="index.php?page=detail_evenement&id=' . $event['id'] . '" class="event-button">
                            Voir les détails <i class="fas fa-arrow-right" style="margin-left: 5px;"></i>
                        </a>
                    </div>
                </div>';
            }
            $content .= '</div>';
        }
        
        $this->renderPublicView('Événements', $content);
    }
    public function detailEvenement($id): void {
        if (!$id) {
            $_SESSION['error'] = 'Événement non trouvé.';
            header('Location: index.php?page=evenements');
            exit;
        }

        require_once __DIR__ . '/../models/Event.php';
        $eventModel = new Event();
        $event = $eventModel->getById($id);

        if (!$event) {
            $_SESSION['error'] = 'Événement non trouvé.';
            header('Location: index.php?page=evenements');
            exit;
        }

        // Récupérer les participants si connecté
        $participants = [];
        $isParticipant = false;
        if (!empty($_SESSION['user_id'])) {
            require_once __DIR__ . '/../models/Participation.php';
            $participationModel = new Participation();
            $participants = $participationModel->getByEvenement($id, 0, 100);
            
            // Vérifier si l'utilisateur participe
            foreach ($participants as $p) {
                if ($p['user_id'] == $_SESSION['user_id']) {
                    $isParticipant = true;
                    break;
                }
            }
        }

        // Traiter l'inscription
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register') {
            if (empty($_SESSION['user_id'])) {
                $_SESSION['error'] = 'Veuillez vous connecter pour vous inscrire.';
                header('Location: index.php?page=login');
                exit;
            }

            if ($isParticipant) {
                $_SESSION['warning'] = 'Vous êtes déjà inscrit à cet événement.';
            } else if ($event['places_restantes'] <= 0) {
                $_SESSION['error'] = 'Désolé, cet événement est complet.';
            } else {
                require_once __DIR__ . '/../models/Participation.php';
                $participationModel = new Participation();
                
                try {
                    $result = $participationModel->create([
                        'evenement_id' => $id,
                        'user_id' => $_SESSION['user_id'],
                        'statut' => 'inscrit'
                    ]);
                    
                    if ($result) {
                        // Mettre à jour les places restantes
                        $eventModel->update($id, ['places_restantes' => $event['places_restantes'] - 1]);
                        $_SESSION['success'] = 'Vous avez été inscrit à cet événement avec succès!';
                        
                        // Rafraîchir les données
                        $event = $eventModel->getById($id);
                        $participants = $participationModel->getByEvenement($id, 0, 100);
                        $isParticipant = true;
                    } else {
                        $_SESSION['error'] = 'Erreur lors de l\'inscription (Create returned null).';
                    }
                } catch (Exception $e) {
                    $_SESSION['error'] = 'Erreur: ' . $e->getMessage();
                    error_log('Inscription Event Error: ' . $e->getMessage());
                }
            }
        }

        // Traiter la désinscription
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'unregister') {
            if (!$isParticipant) {
                $_SESSION['error'] = 'Vous n\'êtes pas inscrit à cet événement.';
            } else {
                require_once __DIR__ . '/../models/Participation.php';
                $participationModel = new Participation();
                
                foreach ($participants as $p) {
                    if ($p['user_id'] == $_SESSION['user_id']) {
                        if ($participationModel->delete($p['id'])) {
                            $eventModel->update($id, ['places_restantes' => $event['places_restantes'] + 1]);
                            $_SESSION['success'] = 'Vous avez été désinscrit de cet événement.';
                            $isParticipant = false;
                            $event = $eventModel->getById($id);
                            $participants = $participationModel->getByEvenement($id, 0, 100);
                            break;
                        }
                    }
                }
            }
        }

        $dateDebut = new DateTime($event['date_debut']);
        $dateFin = new DateTime($event['date_fin']);
        $formatted = $dateDebut->format('d/m/Y H:i');
        $formatted .= ($dateDebut->format('d/m/Y') !== $dateFin->format('d/m/Y')) 
            ? ' - ' . $dateFin->format('d/m/Y H:i') 
            : ' - ' . $dateFin->format('H:i');

        $placesStatus = ($event['places_restantes'] <= 0) ? 'COMPLET' : $event['places_restantes'] . ' places';
        $statusClass = ($event['places_restantes'] <= 0) ? 'badge bg-danger' : 'badge bg-success';

        $content = '
        <style>
            .event-detail-hero {
                background: linear-gradient(135deg, #2A7FAA 0%, #4CAF50 100%);
                color: white;
                padding: 30px;
                border-radius: 12px;
                margin-bottom: 30px;
            }
            .event-detail-hero h1 {
                font-size: 2.5rem;
                margin-bottom: 15px;
            }
            .event-detail-image {
                width: 100%;
                max-height: 400px;
                object-fit: cover;
                border-radius: 12px;
                margin-bottom: 30px;
                box-shadow: 0 5px 20px rgba(0,0,0,0.15);
            }
            .event-info-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 20px;
                margin-bottom: 30px;
            }
            .info-card {
                background: #f8f9fa;
                padding: 20px;
                border-radius: 10px;
                border-left: 5px solid #2A7FAA;
            }
            .info-card-title {
                color: #2A7FAA;
                font-weight: bold;
                margin-bottom: 10px;
                font-size: 0.9rem;
                text-transform: uppercase;
            }
            .info-card-value {
                color: #333;
                font-size: 1.1rem;
            }
            .event-description {
                background: white;
                padding: 25px;
                border-radius: 10px;
                margin-bottom: 30px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            }
            .event-description h3 {
                color: #2A7FAA;
                margin-bottom: 15px;
                padding-bottom: 10px;
                border-bottom: 2px solid #e9ecef;
            }
            .event-description p {
                line-height: 1.8;
                color: #555;
            }
            .event-actions {
                display: flex;
                gap: 15px;
                margin-bottom: 30px;
            }
            .btn-action {
                background: linear-gradient(135deg, #2A7FAA 0%, #4CAF50 100%);
                color: white;
                border: none;
                padding: 12px 30px;
                border-radius: 25px;
                font-size: 1rem;
                cursor: pointer;
                transition: all 0.3s;
                text-decoration: none;
                display: inline-block;
            }
            .btn-action:hover {
                transform: scale(1.05);
                color: white;
            }
            .btn-danger-outline {
                background: white;
                color: #dc3545;
                border: 2px solid #dc3545;
            }
            .btn-danger-outline:hover {
                background: #dc3545;
                color: white;
            }
            .participants-section {
                background: white;
                padding: 25px;
                border-radius: 10px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            }
            .participants-section h3 {
                color: #2A7FAA;
                margin-bottom: 20px;
                padding-bottom: 10px;
                border-bottom: 2px solid #e9ecef;
            }
            .participant-list {
                list-style: none;
                padding: 0;
            }
            .participant-item {
                padding: 12px;
                background: #f8f9fa;
                margin-bottom: 10px;
                border-radius: 8px;
                display: flex;
                align-items: center;
                gap: 10px;
            }
            .participant-item i {
                color: #2A7FAA;
            }
            .event-price-big {
                font-size: 2.5rem;
                font-weight: bold;
                color: #28a745;
            }
            .event-status-badge {
                display: inline-block;
                padding: 8px 16px;
                border-radius: 25px;
                font-weight: bold;
                margin-top: 10px;
            }
        </style>

        ' . (empty($event['image']) ? '' : '<img src="' . htmlspecialchars($event['image']) . '" alt="' . htmlspecialchars($event['titre']) . '" class="event-detail-image">') . '

        <div class="event-detail-hero">
            <h1><i class="fas fa-calendar-alt me-2"></i>' . htmlspecialchars($event['titre']) . '</h1>
            <p style="font-size: 1.1rem; margin: 0;">
                <i class="fas fa-map-marker-alt me-2"></i>' . htmlspecialchars($event['lieu'] ?? 'Lieu non spécifié') . '
            </p>
        </div>

        <div class="event-info-grid">
            <div class="info-card">
                <div class="info-card-title"><i class="fas fa-calendar me-2"></i>Date et Heure</div>
                <div class="info-card-value">' . $formatted . '</div>
            </div>
            
            ' . (!empty($event['adresse']) ? '<div class="info-card"><div class="info-card-title"><i class="fas fa-map-pin me-2"></i>Adresse</div><div class="info-card-value">' . htmlspecialchars($event['adresse']) . '</div></div>' : '') . '
            
            <div class="info-card">
                <div class="info-card-title"><i class="fas fa-users me-2"></i>Participants</div>
                <div class="info-card-value">' . count($participants) . ' / ' . $event['capacite_max'] . '</div>
            </div>
            
            <div class="info-card">
                <div class="info-card-title"><i class="fas fa-ticket-alt me-2"></i>Statut</div>
                <div class="info-card-value">
                    <span class="' . $statusClass . '">' . htmlspecialchars($placesStatus) . '</span>
                </div>
            </div>
            
            ' . (!empty($event['prix']) ? '<div class="info-card"><div class="info-card-title"><i class="fas fa-tag me-2"></i>Prix</div><div class="event-price-big">' . $event['prix'] . '€</div></div>' : '') . '
        </div>

        ' . (!empty($event['description']) || !empty($event['contenu']) ? '<div class="event-description">
            <h3>Description</h3>
            <p>' . htmlspecialchars(!empty($event['description']) ? $event['description'] : $event['contenu']) . '</p>
            ' . (!empty($event['contenu']) ? '<p style="margin-top: 15px;">' . nl2br(htmlspecialchars($event['contenu'])) . '</p>' : '') . '
        </div>' : '') . '

        <div class="event-actions">
            ' . (empty($_SESSION['user_id']) ? '<a href="index.php?page=login" class="btn-action"><i class="fas fa-sign-in-alt me-2"></i>Se connecter pour s\'inscrire</a>' : ($isParticipant ? '<form method="POST" style="display: inline;"><input type="hidden" name="action" value="unregister"><button type="submit" class="btn-action btn-danger-outline"><i class="fas fa-times me-2"></i>Se désinscrire</button></form>' : ($event['places_restantes'] > 0 ? '<form method="POST" style="display: inline;"><input type="hidden" name="action" value="register"><button type="submit" class="btn-action"><i class="fas fa-check me-2"></i>S\'inscrire</button></form>' : '<button class="btn-action" disabled><i class="fas fa-lock me-2"></i>Événement Complet</button>'))) . '
            <a href="index.php?page=evenements" class="btn-action" style="background: #6c757d; margin-left: auto;"><i class="fas fa-arrow-left me-2"></i>Retour</a>
        </div>

        ' . (count($participants) > 0 ? '<div class="participants-section">
            <h3><i class="fas fa-users me-2"></i>Participants Inscrits</h3>
            <ul class="participant-list">
                ' . implode('', array_map(function($p) {
                    $nom = '';
                    if (!empty($p['user_prenom'])) $nom = $p['user_prenom'];
                    if (!empty($p['user_nom'])) $nom .= (!empty($nom) ? ' ' : '') . $p['user_nom'];
                    if (empty($nom)) $nom = 'Participant';
                    return '<li class="participant-item"><i class="fas fa-user-circle"></i><span>' . htmlspecialchars($nom) . '</span></li>';
                }, $participants)) . '
            </ul>
        </div>' : '') . '
        ';

        $this->renderPublicView('Détail de l\'événement', $content);
    }

    public function mesInscriptionsEvenements(): void {
        require_once __DIR__ . '/../models/Participation.php';
        require_once __DIR__ . '/../models/User.php';
        
        $participationModel = new Participation();
        $userModel = new User();
        
        $searchEmail = $_POST['email'] ?? null;
        $inscriptions = [];
        $userEmail = '';
        
        // Traiter la recherche
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($searchEmail)) {
            $userEmail = trim($searchEmail);
            
            // Chercher l'utilisateur par email
            $user = $userModel->getByEmail($userEmail);
            if ($user) {
                // Récupérer ses inscriptions
                $inscriptions = $participationModel->getByUser($user['id'], 0, 100);
            }
        }
        
        $content = '
        <style>
            .inscriptions-header {
                display: flex;
                align-items: center;
                margin-bottom: 30px;
                padding-bottom: 20px;
                border-bottom: 2px solid #f0f0f0;
            }
            .inscriptions-header-icon {
                font-size: 2.5rem;
                color: #17a2b8;
                margin-right: 20px;
                background: #e8f4f9;
                width: 60px;
                height: 60px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 10px;
            }
            .inscriptions-header-text h1 {
                font-size: 2rem;
                font-weight: bold;
                color: #1a1a1a;
                margin: 0;
            }
            .inscriptions-header-text p {
                color: #666;
                margin: 5px 0 0;
                font-size: 0.95rem;
            }
            .search-box {
                background: white;
                padding: 25px;
                border-radius: 10px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.08);
                margin-bottom: 30px;
            }
            .search-label {
                display: block;
                margin-bottom: 15px;
                font-weight: 600;
                color: #1a1a1a;
                font-size: 0.95rem;
            }
            .search-input-group {
                display: flex;
                gap: 10px;
            }
            .search-input {
                flex: 1;
                padding: 12px 15px;
                border: 1px solid #ddd;
                border-radius: 6px;
                font-size: 1rem;
                transition: all 0.3s;
            }
            .search-input:focus {
                outline: none;
                border-color: #17a2b8;
                box-shadow: 0 0 0 3px rgba(23, 162, 184, 0.1);
            }
            .search-input::placeholder {
                color: #999;
            }
            .search-btn {
                background: #17a2b8;
                color: white;
                border: none;
                padding: 12px 30px;
                border-radius: 6px;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.3s;
                white-space: nowrap;
                display: flex;
                align-items: center;
                gap: 8px;
            }
            .search-btn:hover {
                background: #138496;
            }
            .inscriptions-list {
                background: white;
                border-radius: 10px;
                overflow: hidden;
                box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            }
            .inscriptions-table {
                width: 100%;
                border-collapse: collapse;
            }
            .inscriptions-table thead {
                background: #f8f9fa;
                border-bottom: 2px solid #e9ecef;
            }
            .inscriptions-table th {
                padding: 15px;
                text-align: left;
                font-weight: 600;
                color: #1a1a1a;
                font-size: 0.95rem;
            }
            .inscriptions-table td {
                padding: 15px;
                border-bottom: 1px solid #f0f0f0;
                color: #666;
                font-size: 0.95rem;
            }
            .inscriptions-table tbody tr:hover {
                background: #f8f9fa;
            }
            .status-badge {
                display: inline-block;
                padding: 6px 12px;
                border-radius: 20px;
                font-weight: 600;
                font-size: 0.85rem;
            }
            .status-inscrit {
                background: #e8f4f9;
                color: #17a2b8;
            }
            .status-present {
                background: #e8f5e9;
                color: #28a745;
            }
            .status-absent {
                background: #fce4ec;
                color: #c2185b;
            }
            .action-buttons {
                display: flex;
                gap: 8px;
            }
            .action-btn {
                padding: 6px 12px;
                border-radius: 6px;
                border: none;
                cursor: pointer;
                font-size: 0.85rem;
                font-weight: 600;
                transition: all 0.3s;
                text-decoration: none;
                display: inline-block;
            }
            .btn-modify {
                background: #17a2b8;
                color: white;
            }
            .btn-modify:hover {
                background: #138496;
                color: white;
            }
            .btn-cancel {
                background: #f8d7da;
                color: #721c24;
            }
            .btn-cancel:hover {
                background: #f5c6cb;
            }
            .empty-state {
                text-align: center;
                padding: 60px 20px;
                color: #999;
            }
            .empty-state i {
                font-size: 3rem;
                color: #ddd;
                margin-bottom: 15px;
            }
        </style>

        <div class="inscriptions-header">
            <div class="inscriptions-header-icon">
                <i class="fas fa-check-square"></i>
            </div>
            <div class="inscriptions-header-text">
                <h1>Mes Inscriptions</h1>
                <p>Retrouvez, modifiez ou annulez vos inscriptions en saisissant votre adresse e-mail.</p>
            </div>
        </div>

        <div class="search-box">
            <form method="POST" action="index.php?page=mes_inscriptions_evenements">
                <label for="email" class="search-label">
                    <i class="fas fa-envelope" style="margin-right: 8px; color: #17a2b8;"></i>
                    Votre adresse e-mail
                </label>
                <div class="search-input-group">
                    <input 
                        type="email" 
                        id="email"
                        name="email" 
                        class="search-input" 
                        placeholder="votre@email.com"
                        value="' . htmlspecialchars($userEmail) . '"
                        required
                    >
                    <button type="submit" class="search-btn">
                        <i class="fas fa-search"></i>
                        Rechercher
                    </button>
                </div>
            </form>
        </div>

        ' . (empty($searchEmail) ? '' : '
            ' . (empty($inscriptions) ? '
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <p>Aucune inscription trouvée pour cet e-mail.</p>
                </div>
            ' : '
                <div class="inscriptions-list">
                    <table class="inscriptions-table">
                        <thead>
                            <tr>
                                <th>Événement</th>
                                <th>Date</th>
                                <th>Lieu</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            ' . implode('', array_map(function($inscription) {
                                $eventDate = new DateTime($inscription['evenement_date'] ?? date('Y-m-d'));
                                $dateFormatted = $eventDate->format('d/m/Y');
                                
                                $statusClass = 'status-' . ($inscription['statut'] ?? 'inscrit');
                                $statusLabel = ucfirst($inscription['statut'] ?? 'inscrit');
                                
                                return '
                                <tr>
                                    <td><strong>' . htmlspecialchars($inscription['evenement_titre'] ?? 'N/A') . '</strong></td>
                                    <td>' . $dateFormatted . '</td>
                                    <td>' . htmlspecialchars($inscription['lieu'] ?? 'N/A') . '</td>
                                    <td><span class="status-badge ' . $statusClass . '">' . $statusLabel . '</span></td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="index.php?page=detail_evenement&id=' . $inscription['event_id'] . '" class="action-btn btn-modify">
                                                <i class="fas fa-eye"></i> Voir
                                            </a>
                                            ' . ($inscription['statut'] === 'inscrit' ? '
                                            <button class="action-btn btn-cancel" onclick="if(confirm(\'Êtes-vous sûr de vouloir vous désinscrire ?\')) window.location=\'index.php?page=detail_evenement&id=' . $inscription['event_id'] . '&action=unregister\'">
                                                <i class="fas fa-times"></i> Annuler
                                            </button>
                                            ' : '') . '
                                        </div>
                                    </td>
                                </tr>';
                            }, $inscriptions)) . '
                        </tbody>
                    </table>
                </div>
            ') . '
        ') . '
        ';

        $this->renderPublicView('Mes Inscriptions', $content);
    }

    public function contact(): void {
        $this->renderTemporaryView('Contact', '
            <form method="POST">
                <div class="mb-3"><label class="form-label">Nom</label><input type="text" class="form-control" name="nom" required></div>
                <div class="mb-3"><label class="form-label">Email</label><input type="email" class="form-control" name="email" required></div>
                <div class="mb-3"><label class="form-label">Message</label><textarea class="form-control" name="message" rows="5" required></textarea></div>
                <button type="submit" class="btn btn-primary">Envoyer</button>
            </form>');
    }
    public function about(): void {
        $this->renderTemporaryView('À propos', '
            <h3>Valorys - Votre plateforme médicale</h3>
            <p>Valorys vous permet de prendre rendez-vous avec des médecins qualifiés facilement.</p>
            <p>Notre mission : faciliter l\'accès aux soins pour tous.</p>');
    }

    public function sponsors(): void {
        require_once __DIR__ . '/../models/Sponsor.php';
        $sponsorModel = new Sponsor();
        $sponsors = $sponsorModel->getAll(0, 100, 'all');

        $content = '
        <style>
            .sponsors-header {
                margin-bottom: 40px;
            }
            .sponsors-header h1 {
                font-size: 2rem;
                font-weight: bold;
                color: #1a1a1a;
                margin-bottom: 10px;
            }
            .sponsors-header p {
                color: #666;
                font-size: 0.95rem;
            }
            .sponsors-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
                gap: 25px;
                margin-bottom: 40px;
            }
            .sponsor-card {
                background: white;
                border-radius: 8px;
                padding: 25px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                transition: all 0.3s ease;
            }
            .sponsor-card:hover {
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            }
            .sponsor-card-top {
                display: flex;
                align-items: flex-start;
                gap: 15px;
                margin-bottom: 20px;
            }
            .sponsor-card-icon {
                width: 50px;
                height: 50px;
                background: #e8f4f9;
                border-radius: 8px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 1.8rem;
                color: #2A7FAA;
                flex-shrink: 0;
            }
            .sponsor-card-header {
                flex: 1;
            }
            .sponsor-card-name {
                font-size: 1.1rem;
                font-weight: bold;
                color: #1a1a1a;
                margin-bottom: 8px;
            }
            .sponsor-card-sector {
                display: inline-block;
                background: #e8f4f9;
                color: white;
                padding: 4px 12px;
                border-radius: 12px;
                font-size: 0.75rem;
                font-weight: 600;
                text-transform: uppercase;
            }
            .sector-argent { background: #a9a9a9; }
            .sector-platine { background: #e5e4e2; color: #333; }
            .sector-or { background: #ffd700; color: #333; }
            .sector-bronze { background: #cd7f32; }
            .sponsor-card-details {
                border-top: 1px solid #f0f0f0;
                padding-top: 15px;
            }
            .sponsor-detail-line {
                display: flex;
                align-items: center;
                gap: 10px;
                margin-bottom: 10px;
                color: #666;
                font-size: 0.9rem;
            }
            .sponsor-detail-line:last-child {
                margin-bottom: 0;
            }
            .sponsor-detail-line i {
                color: #2A7FAA;
                width: 16px;
            }
            .sponsor-detail-link {
                color: #2A7FAA;
                text-decoration: none;
                transition: color 0.3s;
            }
            .sponsor-detail-link:hover {
                color: #4CAF50;
                text-decoration: underline;
            }
            .empty-state {
                text-align: center;
                padding: 60px 20px;
                color: #999;
            }
            .empty-state i {
                font-size: 3rem;
                color: #ddd;
                margin-bottom: 15px;
            }
        </style>

        <div class="sponsors-header">
            <h1>Nos Sponsors</h1>
            <p>Partenaires qui soutiennent les événements médicaux.</p>
        </div>

        ' . (empty($sponsors) ? '
            <div class="empty-state">
                <i class="fas fa-handshake"></i>
                <p>Aucun sponsor disponible pour le moment.</p>
            </div>
        ' : '
            <div class="sponsors-grid">
                ' . implode('', array_map(function($sponsor) {
                    $secteur = strtolower($sponsor['secteur'] ?? 'argent');
                    $sectorClass = 'sector-' . str_replace(' ', '', strtolower($sponsor['secteur'] ?? 'argent'));
                    
                    return '
                    <div class="sponsor-card">
                        <div class="sponsor-card-top">
                            <div class="sponsor-card-icon">
                                <i class="fas fa-building"></i>
                            </div>
                            <div class="sponsor-card-header">
                                <div class="sponsor-card-name">' . htmlspecialchars($sponsor['nom']) . '</div>
                                ' . (!empty($sponsor['secteur']) ? '<span class="sponsor-card-sector ' . $sectorClass . '">' . htmlspecialchars($sponsor['secteur']) . '</span>' : '') . '
                            </div>
                        </div>
                        <div class="sponsor-card-details">
                            ' . (!empty($sponsor['email']) ? '<div class="sponsor-detail-line"><i class="fas fa-envelope"></i><span>' . htmlspecialchars($sponsor['email']) . '</span></div>' : '') . '
                            ' . (!empty($sponsor['telephone']) ? '<div class="sponsor-detail-line"><i class="fas fa-phone"></i><span>' . htmlspecialchars($sponsor['telephone']) . '</span></div>' : '') . '
                            ' . (!empty($sponsor['site_web']) ? '<div class="sponsor-detail-line"><i class="fas fa-globe"></i><a href="' . htmlspecialchars($sponsor['site_web']) . '" target="_blank" class="sponsor-detail-link">' . htmlspecialchars($sponsor['site_web']) . '</a></div>' : '') . '
                        </div>
                    </div>';
                }, $sponsors)) . '
            </div>
        ') . '
        ';

        $this->renderPublicView('Nos Sponsors', $content);
    }

    // =============================================
    // PAGES PROTÉGÉES
    // =============================================

    public function prendreRendezVous($id = null): void {
        $this->requireLogin();
        require_once __DIR__ . '/../models/Medecin.php';
        $medecinModel = new Medecin();
        $medecins = $medecinModel->getAllWithUsers();
        $errors = $_SESSION['errors'] ?? [];
        $old    = $_SESSION['old']    ?? [];
        $success = $_SESSION['success'] ?? null;
        $error   = $_SESSION['error']   ?? null;
        unset($_SESSION['errors'], $_SESSION['old'], $_SESSION['success'], $_SESSION['error']);
        $selectedMedecinId = $id ?? ($old['medecin_id'] ?? null);
        $content = '
        <style>
            .rdv-form-container { background:white;border-radius:12px;padding:30px;box-shadow:0 2px 8px rgba(0,0,0,0.1); }
            .form-group { margin-bottom:20px; }
            .form-group label { display:block;font-weight:bold;margin-bottom:8px;color:#333; }
            .form-group .required { color:#dc3545; }
            .form-control,.form-select { width:100%;padding:10px 12px;border:1px solid #ddd;border-radius:8px;font-size:14px;transition:all 0.3s; }
            .form-control:focus,.form-select:focus { border-color:#2A7FAA;outline:none;box-shadow:0 0 0 3px rgba(42,127,170,0.1); }
            .form-control.error,.form-select.error { border-color:#dc3545; }
            .field-error { font-size:12px;margin-top:5px;color:#dc3545; }
            .btn-submit { background:linear-gradient(135deg,#2A7FAA,#4CAF50);color:white;border:none;padding:12px 30px;border-radius:25px;cursor:pointer;font-size:16px;font-weight:bold;transition:all 0.3s; }
            .btn-submit:hover { transform:translateY(-2px);box-shadow:0 5px 15px rgba(0,0,0,0.1); }
            .btn-cancel { background:#6c757d;color:white;border:none;padding:12px 30px;border-radius:25px;cursor:pointer;font-size:16px;text-decoration:none;display:inline-block; }
            .alert-success { background:#d4edda;color:#155724;padding:12px 20px;border-radius:8px;margin-bottom:20px;border-left:4px solid #28a745; }
            .alert-danger  { background:#f8d7da;color:#721c24;padding:12px 20px;border-radius:8px;margin-bottom:20px;border-left:4px solid #dc3545; }
            .form-row { display:flex;gap:20px;flex-wrap:wrap; }
            .form-row .form-group { flex:1;min-width:200px; }
        </style>
        <div class="rdv-form-container">
            <h2 class="mb-4"><i class="fas fa-calendar-plus me-2" style="color:#2A7FAA;"></i>Prendre rendez-vous</h2>
            ' . ($success ? '<div class="alert-success"><i class="fas fa-check-circle me-2"></i>' . htmlspecialchars($success) . '</div>' : '') . '
            ' . ($error   ? '<div class="alert-danger"><i class="fas fa-exclamation-circle me-2"></i>' . htmlspecialchars($error) . '</div>' : '') . '
            <form method="POST" action="index.php?page=prendre_rendez_vous" id="rdvForm">
                <div class="form-row">
                    <div class="form-group">
                        <label>Médecin <span class="required">*</span></label>
                        <select name="medecin_id" id="medecin_id" class="form-select ' . (isset($errors['medecin_id']) ? 'error' : '') . '">
                            <option value="">-- Sélectionner un médecin --</option>';
        if (empty($medecins)) {
            $content .= '<option value="" disabled>Aucun médecin disponible pour le moment</option>';
        } else {
            foreach ($medecins as $medecin) {
                $selected = ($selectedMedecinId && $selectedMedecinId == $medecin['user_id']) ? 'selected' : '';
                $content .= '<option value="' . $medecin['user_id'] . '" ' . $selected . '>Dr. ' . htmlspecialchars($medecin['prenom'] . ' ' . $medecin['nom']) . ' - ' . htmlspecialchars($medecin['specialite'] ?? 'Généraliste') . '</option>';
            }
        }
        $content .= '
                        </select>
                        <div class="field-error">' . (isset($errors['medecin_id']) ? '<i class="fas fa-exclamation-circle"></i> ' . htmlspecialchars($errors['medecin_id']) : '') . '</div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Date <span class="required">*</span></label>
                        <input type="date" name="date_rendezvous" class="form-control ' . (isset($errors['date_rendezvous']) ? 'error' : '') . '" value="' . htmlspecialchars($old['date_rendezvous'] ?? '') . '" min="' . date('Y-m-d') . '">
                        <div class="field-error">' . (isset($errors['date_rendezvous']) ? '<i class="fas fa-exclamation-circle"></i> ' . htmlspecialchars($errors['date_rendezvous']) : '') . '</div>
                    </div>
                    <div class="form-group">
                        <label>Heure <span class="required">*</span></label>
                        <input type="time" name="heure_rendezvous" class="form-control ' . (isset($errors['heure_rendezvous']) ? 'error' : '') . '" value="' . htmlspecialchars($old['heure_rendezvous'] ?? '') . '">
                        <div class="field-error">' . (isset($errors['heure_rendezvous']) ? '<i class="fas fa-exclamation-circle"></i> ' . htmlspecialchars($errors['heure_rendezvous']) : '') . '</div>
                    </div>
                </div>
                <div class="form-group">
                    <label>Motif de la consultation</label>
                    <textarea name="motif" class="form-control" rows="4" placeholder="Décrivez brièvement le motif de votre consultation...">' . htmlspecialchars($old['motif'] ?? '') . '</textarea>
                </div>
                <div class="d-flex justify-content-end gap-3 mt-4">
                    <a href="index.php?page=mes_rendez_vous" class="btn-cancel"><i class="fas fa-times me-2"></i>Annuler</a>
                    <button type="submit" name="submit_rdv" class="btn-submit"><i class="fas fa-calendar-check me-2"></i>Confirmer le rendez-vous</button>
                </div>
            </form>
        </div>';
        $this->renderPublicView('Prendre rendez-vous', $content);
    }

    public function mesRendezVous(): void {
        $this->requireLogin();
        $userRole = $_SESSION['user_role'] ?? '';
        $userId   = (int)$_SESSION['user_id'];
        require_once __DIR__ . '/../models/RendezVous.php';
        $rendezVousModel = new RendezVous();
        if ($userRole === 'medecin') {
            $rendezVous = $rendezVousModel->getByMedecin($userId);
            $title = 'Mes consultations';
        } elseif ($userRole === 'patient') {
            $rendezVous = $rendezVousModel->getByPatient($userId);
            $title = 'Mes rendez-vous';
        } else {
            $this->page403(); return;
        }
        $content = $this->getRendezVousHTML($rendezVous, $userRole, $title);
        $this->renderPublicView($title, $content);
    }

    private function getRendezVousHTML($rendezVous, $userRole, $title): string {
        $isMedecin = ($userRole === 'medecin');
        $html = '
        <style>
            .rdv-card { background:white;border-radius:12px;padding:20px;margin-bottom:20px;box-shadow:0 2px 8px rgba(0,0,0,0.08);border-left:4px solid #2A7FAA;position:relative; }
            .rdv-header { display:flex;justify-content:space-between;align-items:center;margin-bottom:15px;padding-bottom:10px;border-bottom:1px solid #eee; }
            .rdv-title { font-size:1.1rem;font-weight:bold;color:#2A7FAA; }
            .rdv-info { display:flex;gap:20px;flex-wrap:wrap;margin-bottom:15px; }
            .rdv-info-item { display:flex;align-items:center;gap:8px;color:#555;font-size:14px; }
            .badge-statut { padding:5px 12px;border-radius:20px;font-size:12px; }
            .badge-confirme { background:#d4edda;color:#155724; }
            .badge-attente  { background:#fff3cd;color:#856404; }
            .badge-termine  { background:#cfe2ff;color:#084298; }
            .badge-annule   { background:#f8d7da;color:#721c24; }
            .btn-action    { padding:5px 15px;border-radius:20px;font-size:13px;margin-right:8px;text-decoration:none;display:inline-block; }
            .btn-confirmer { background:#28a745;color:white; }
            .btn-annuler   { background:#dc3545;color:white; }
            .btn-terminer  { background:#17a2b8;color:white; }
            .btn-details   { background:#6c757d;color:white; }
            .btn-modifier  { background:#ffc107;color:#000; }
            .btn-supprimer { background:#dc3545;color:white; }
            .empty-state { text-align:center;padding:50px;background:white;border-radius:12px; }
            .filter-section { background:white;border-radius:12px;padding:20px;margin-bottom:25px;box-shadow:0 2px 8px rgba(0,0,0,0.08); }
            .action-buttons { display:flex;gap:8px;margin-top:15px;flex-wrap:wrap; }
            .modal { display:none;position:fixed;z-index:1000;left:0;top:0;width:100%;height:100%;background-color:rgba(0,0,0,0.5);align-items:center;justify-content:center; }
            .modal.show { display:flex; }
            .modal-content { background:white;border-radius:15px;width:90%;max-width:500px;animation:slideDown 0.3s ease; }
            .modal-header { background:linear-gradient(135deg,#2A7FAA 0%,#4CAF50 100%);color:white;padding:15px 20px;border-radius:15px 15px 0 0;display:flex;justify-content:space-between;align-items:center; }
            .modal-header .close { background:none;border:none;color:white;font-size:24px;cursor:pointer; }
            .modal-body { padding:20px; }
            .modal-footer { padding:15px 20px;border-top:1px solid #eee;display:flex;justify-content:flex-end;gap:10px; }
            @keyframes slideDown { from { opacity:0;transform:translateY(-50px); } to { opacity:1;transform:translateY(0); } }
            .form-group { margin-bottom:15px; }
            .form-group label { display:block;font-weight:bold;margin-bottom:5px; }
            .form-control { width:100%;padding:8px 12px;border:1px solid #ddd;border-radius:6px; }
            .field-error { color:#dc3545;font-size:12px;margin-top:5px; }
        </style>
        <div class="filter-section">
            <h5><i class="fas fa-filter me-2"></i>Filtrer</h5>
            <form method="GET" class="row g-3">
                <input type="hidden" name="page" value="mes_rendez_vous">
                <div class="col-md-3">
                    <select name="statut" class="form-select">
                        <option value="">Tous les statuts</option>
                        <option value="en_attente" ' . ((($_GET['statut'] ?? '') === 'en_attente') ? 'selected' : '') . '>En attente</option>
                        <option value="confirmé"   ' . ((($_GET['statut'] ?? '') === 'confirmé')   ? 'selected' : '') . '>Confirmé</option>
                        <option value="terminé"    ' . ((($_GET['statut'] ?? '') === 'terminé')    ? 'selected' : '') . '>Terminé</option>
                        <option value="annulé"     ' . ((($_GET['statut'] ?? '') === 'annulé')     ? 'selected' : '') . '>Annulé</option>
                    </select>
                </div>
                <div class="col-md-3"><input type="date" name="date" class="form-control" value="' . ($_GET['date'] ?? '') . '"></div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary">Filtrer</button>
                    <a href="index.php?page=mes_rendez_vous" class="btn btn-secondary">Réinitialiser</a>
                </div>
            </form>
        </div>';

        if (empty($rendezVous)) {
            $html .= '<div class="empty-state"><i class="fas fa-calendar-times fa-3x text-muted mb-3"></i><h4>Aucun rendez-vous</h4>';
            if (!$isMedecin) {
                $html .= '<a href="index.php?page=prendre_rendez_vous" class="btn btn-primary mt-3"><i class="fas fa-calendar-plus me-2"></i>Prendre un rendez-vous</a>';
            }
            $html .= '</div>';
        } else {
            foreach ($rendezVous as $rdv) {
                $badgeClass = match($rdv['statut']) {
                    'confirmé'   => 'badge-confirme',
                    'en_attente' => 'badge-attente',
                    'terminé'    => 'badge-termine',
                    'annulé'     => 'badge-annule',
                    default      => 'badge-attente'
                };
                $showEditDelete = ($rdv['statut'] !== 'terminé' && $rdv['statut'] !== 'annulé');
                $html .= '
                <div class="rdv-card">
                    <div class="rdv-header">
                        <span class="rdv-title"><i class="fas ' . ($isMedecin ? 'fa-user' : 'fa-user-md') . ' me-2"></i>'
                            . ($isMedecin ? htmlspecialchars($rdv['patient_nom']) : 'Dr. ' . htmlspecialchars($rdv['medecin_nom'])) . '</span>
                        <span class="badge-statut ' . $badgeClass . '">' . ucfirst($rdv['statut']) . '</span>
                    </div>
                    <div class="rdv-info">
                        <div class="rdv-info-item"><i class="fas fa-calendar"></i><span>' . date('d/m/Y', strtotime($rdv['date_rendezvous'])) . '</span></div>
                        <div class="rdv-info-item"><i class="fas fa-clock"></i><span>' . $rdv['heure_rendezvous'] . '</span></div>'
                    . ($isMedecin
                        ? '<div class="rdv-info-item"><i class="fas fa-phone"></i><span>' . htmlspecialchars($rdv['patient_telephone'] ?? 'Non renseigné') . '</span></div>'
                        : '<div class="rdv-info-item"><i class="fas fa-stethoscope"></i><span>' . htmlspecialchars($rdv['specialite'] ?? 'Généraliste') . '</span></div>') . '
                    </div>'
                    . (!empty($rdv['motif']) ? '<div style="background:#f8f9fa;padding:10px;border-radius:8px;margin:10px 0;font-size:14px;"><i class="fas fa-file-alt me-2"></i>' . nl2br(htmlspecialchars($rdv['motif'])) . '</div>' : '')
                    . '<div class="action-buttons">
                        <a href="index.php?page=detail_rendez_vous&id=' . $rdv['id'] . '" class="btn-action btn-details"><i class="fas fa-eye me-1"></i>Détails</a>';
                if (!$isMedecin && $showEditDelete) {
                    $html .= '
                        <button onclick="openEditRdvModal(' . $rdv['id'] . ', \'' . $rdv['date_rendezvous'] . '\', \'' . $rdv['heure_rendezvous'] . '\', \'' . addslashes($rdv['motif'] ?? '') . '\')" class="btn-action btn-modifier"><i class="fas fa-edit me-1"></i>Modifier</button>
                        <button onclick="confirmDeleteRdv(' . $rdv['id'] . ')" class="btn-action btn-supprimer"><i class="fas fa-trash me-1"></i>Supprimer</button>';
                }
                if ($isMedecin && $rdv['statut'] === 'en_attente') {
                    $html .= '<a href="index.php?page=confirmer_rendez_vous&id=' . $rdv['id'] . '" class="btn-action btn-confirmer" onclick="return confirm(\'Confirmer ce rendez-vous ?\')"><i class="fas fa-check me-1"></i>Confirmer</a>';
                }
                if ($isMedecin && $rdv['statut'] === 'confirmé') {
                    $html .= '<a href="index.php?page=terminer_rendez_vous&id=' . $rdv['id'] . '" class="btn-action btn-terminer" onclick="return confirm(\'Terminer ce rendez-vous ?\')"><i class="fas fa-check-double me-1"></i>Terminer</a>';
                }
                $html .= '</div></div>';
            }
        }

        $html .= '
        <div id="editRdvModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h5><i class="fas fa-edit me-2"></i>Modifier le rendez-vous</h5>
                    <button class="close" onclick="closeModal(\'editRdvModal\')">&times;</button>
                </div>
                <form method="POST" action="index.php?page=modifier_rendez_vous" id="editRdvForm">
                    <div class="modal-body">
                        <input type="hidden" name="rdv_id" id="edit_rdv_id">
                        <div class="form-group"><label>Date <span style="color:red;">*</span></label><input type="date" name="date_rendezvous" id="edit_date" class="form-control" min="' . date('Y-m-d') . '" required><div class="field-error" id="edit_date_error"></div></div>
                        <div class="form-group"><label>Heure <span style="color:red;">*</span></label><input type="time" name="heure_rendezvous" id="edit_heure" class="form-control" required><div class="field-error" id="edit_heure_error"></div></div>
                        <div class="form-group"><label>Motif</label><textarea name="motif" id="edit_motif" class="form-control" rows="3"></textarea></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" style="background:#6c757d;color:white;border:none;padding:8px 20px;border-radius:6px;cursor:pointer;" onclick="closeModal(\'editRdvModal\')">Annuler</button>
                        <button type="submit" style="background:linear-gradient(135deg,#2A7FAA,#4CAF50);color:white;border:none;padding:8px 20px;border-radius:6px;cursor:pointer;">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
        <div id="deleteRdvModal" class="modal">
            <div class="modal-content">
                <div class="modal-header" style="background:linear-gradient(135deg,#dc3545 0%,#c82333 100%);">
                    <h5><i class="fas fa-trash-alt me-2"></i>Confirmer la suppression</h5>
                    <button class="close" onclick="closeModal(\'deleteRdvModal\')">&times;</button>
                </div>
                <div class="modal-body"><p>Êtes-vous sûr de vouloir supprimer ce rendez-vous ?</p><p style="color:red;font-size:12px;">⚠️ Cette action est irréversible.</p></div>
                <div class="modal-footer">
                    <button type="button" style="background:#6c757d;color:white;border:none;padding:8px 20px;border-radius:6px;cursor:pointer;" onclick="closeModal(\'deleteRdvModal\')">Annuler</button>
                    <button type="button" style="background:#dc3545;color:white;border:none;padding:8px 20px;border-radius:6px;cursor:pointer;" id="confirmDeleteRdvBtn">Supprimer</button>
                </div>
            </div>
        </div>';

        $html .= <<<'JS'
        <script>
        var currentDeleteId = null;
        function openEditRdvModal(id, date, heure, motif) {
            document.getElementById("edit_rdv_id").value  = id;
            document.getElementById("edit_date").value    = date;
            document.getElementById("edit_heure").value   = heure;
            document.getElementById("edit_motif").value   = motif;
            document.getElementById("editRdvModal").classList.add("show");
        }
        function confirmDeleteRdv(id) {
            currentDeleteId = id;
            document.getElementById("deleteRdvModal").classList.add("show");
        }
        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove("show");
            if (modalId === "deleteRdvModal") currentDeleteId = null;
        }
        document.getElementById("confirmDeleteRdvBtn").onclick = function() {
            if (currentDeleteId) window.location.href = "index.php?page=supprimer_rendez_vous&id=" + currentDeleteId;
        };
        document.getElementById("editRdvForm").addEventListener("submit", function(e) {
            let isValid = true;
            document.getElementById("edit_date_error").innerHTML  = "";
            document.getElementById("edit_heure_error").innerHTML = "";
            if (!document.getElementById("edit_date").value)  { document.getElementById("edit_date_error").innerHTML  = "Veuillez sélectionner une date.";  isValid = false; }
            if (!document.getElementById("edit_heure").value) { document.getElementById("edit_heure_error").innerHTML = "Veuillez sélectionner une heure."; isValid = false; }
            if (!isValid) e.preventDefault();
        });
        window.onclick = function(event) {
            if (event.target.classList.contains("modal")) event.target.classList.remove("show");
        };
        </script>
JS;
        return $html;
    }

    public function annulerRendezVous($id): void { $this->requireLogin(); $this->renderTemporaryView('Annuler rendez-vous', '<p>Rendez-vous #' . htmlspecialchars($id) . ' annulé</p>'); }
    public function confirmerRendezVous($id): void { $this->requireLogin(); $this->renderTemporaryView('Confirmer rendez-vous', '<p>Rendez-vous #' . htmlspecialchars($id) . ' confirmé</p>'); }
    public function mesOrdonnances(): void { $this->requireLogin(); $this->renderTemporaryView('Mes ordonnances', '<p>Liste de vos ordonnances</p>'); }
    public function mesNotifications(): void { $this->requireLogin(); $this->renderTemporaryView('Mes notifications', '<p>Aucune notification</p>'); }

    // =============================================
    // MODIFIER PROFIL — version corrigée (NOWDOC)
    // =============================================

    public function modifierProfil(): void {
        $this->requireLogin();
        $userName      = htmlspecialchars($_SESSION['user_name']      ?? '');
        $userEmail     = htmlspecialchars($_SESSION['user_email']     ?? '');
        $userTelephone = htmlspecialchars($_SESSION['user_telephone'] ?? '');
        $userId        = (int)($_SESSION['user_id'] ?? 0);
        $hasFaceId     = false;
        try {
            $db   = Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT face_descriptor FROM users WHERE id = :id");
            $stmt->execute([':id' => $userId]);
            $user      = $stmt->fetch(PDO::FETCH_ASSOC);
            $hasFaceId = !empty($user['face_descriptor']);
        } catch (Exception $e) {
            error_log('Erreur vérification Face ID: ' . $e->getMessage());
        }

        $faceButtons = $hasFaceId
            ? '
            <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i> Vous avez déjà enregistré votre visage.</div>
            <button type="button" class="btn btn-warning btn-lg" onclick="updateFaceId()"><i class="fas fa-sync-alt me-2"></i>Mettre à jour mon visage</button>
            <button type="button" class="btn btn-danger btn-lg ms-2" onclick="deleteFaceId()"><i class="fas fa-trash me-2"></i>Supprimer mon visage</button>'
            : '<button type="button" class="btn btn-success btn-lg" onclick="registerFaceId()"><i class="fas fa-camera me-2"></i>Enregistrer mon visage</button>';

        $content = '
        <div class="row g-4">
            <div class="col-md-8 mx-auto">
                <div class="card shadow">
                    <div class="card-header"><h5 class="mb-0"><i class="fas fa-edit text-primary me-2"></i>Modifier mes informations</h5></div>
                    <div class="card-body">
                        <form method="POST" id="profileForm">
                            <div class="mb-4"><label class="form-label"><i class="fas fa-user text-primary me-2"></i>Nom complet</label><input type="text" class="form-control form-control-lg" name="nom" value="' . $userName . '" required></div>
                            <div class="mb-4"><label class="form-label"><i class="fas fa-envelope text-primary me-2"></i>Adresse email</label><input type="email" class="form-control form-control-lg" name="email" value="' . $userEmail . '" required></div>
                            <div class="mb-4"><label class="form-label"><i class="fas fa-phone text-primary me-2"></i>Numéro de téléphone</label><input type="tel" class="form-control form-control-lg" name="telephone" value="' . $userTelephone . '"></div>
                            <div class="mb-4"><label class="form-label"><i class="fas fa-lock text-primary me-2"></i>Mot de passe (optionnel)</label><input type="password" class="form-control form-control-lg" name="password" placeholder="Laisser vide pour ne pas changer"><small class="text-muted">Minimum 6 caractères</small></div>
                            <div class="alert alert-info"><i class="fas fa-info-circle me-2"></i><strong>Conseil:</strong> Mettez à jour vos informations pour une meilleure expérience.</div>
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="index.php?page=mon_profil" class="btn btn-secondary btn-lg"><i class="fas fa-arrow-left me-2"></i>Annuler</a>
                                <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-save me-2"></i>Enregistrer les modifications</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="row g-4 mt-3">
            <div class="col-md-8 mx-auto">
                <div class="card shadow">
                    <div class="card-header"><h5 class="mb-0"><i class="fas fa-key text-primary me-2"></i>Changer le mot de passe</h5></div>
                    <div class="card-body">
                        <form method="POST" id="passwordForm">
                            <input type="hidden" name="action" value="change_password">
                            <div class="mb-4"><label class="form-label">Mot de passe actuel <span class="text-danger">*</span></label><input type="password" class="form-control form-control-lg" name="current_password" required><div id="currentPassword-error"></div></div>
                            <div class="mb-4">
                                <label class="form-label">Nouveau mot de passe <span class="text-danger">*</span></label>
                                <input type="password" id="newPassword" name="new_password" class="form-control form-control-lg" required>
                                <div class="mt-2">
                                    <span id="reqLength" class="requirement-invalid"><i class="fas fa-circle me-1"></i> Au moins 8 caractères</span><br>
                                    <span id="reqUpper"  class="requirement-invalid"><i class="fas fa-circle me-1"></i> Au moins une majuscule</span><br>
                                    <span id="reqNumber" class="requirement-invalid"><i class="fas fa-circle me-1"></i> Au moins un chiffre</span>
                                </div>
                                <div id="newPassword-error"></div>
                            </div>
                            <div class="mb-4"><label class="form-label">Confirmer le nouveau mot de passe <span class="text-danger">*</span></label><input type="password" id="confirmPassword" name="confirm_password" class="form-control form-control-lg" required><div id="confirmPassword-error"></div></div>
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <button type="button" class="btn btn-secondary btn-lg" onclick="cancelPassword()">Annuler</button>
                                <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-save me-2"></i>Enregistrer le nouveau mot de passe</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="row g-4 mt-3">
            <div class="col-md-8 mx-auto">
                <div class="card shadow">
                    <div class="card-header"><h5 class="mb-0"><i class="fas fa-face-smile text-primary me-2"></i>Reconnaissance faciale (Face ID)</h5></div>
                    <div class="card-body">
                        <div class="text-center mb-3">
                            <i class="fas fa-camera fa-3x text-primary mb-2"></i>
                            <p class="mb-3">Activez la reconnaissance faciale pour vous connecter plus facilement.</p>
                            ' . $faceButtons . '
                        </div>
                        <div id="faceIdStatus" class="text-center mt-3"></div>
                    </div>
                </div>
            </div>
        </div>
        <style>
            .requirement-valid   { color:#28a745; }
            .requirement-invalid { color:#dc3545; }
            .field-error { color:#dc3545;font-size:12px;margin-top:5px; }
        </style>';

        // Tout le JavaScript en NOWDOC pour éviter tout problème d'échappement
        $content .= <<<JS
        <script>
        var userId = {$userId};

        function updatePasswordRequirements() {
            var password = document.getElementById("newPassword").value;
            var checks = {
                Length: password.length >= 8,
                Upper:  /[A-Z]/.test(password),
                Number: /[0-9]/.test(password)
            };
            var labels = {
                Length: "Au moins 8 caractères",
                Upper:  "Au moins une majuscule",
                Number: "Au moins un chiffre"
            };
            Object.keys(checks).forEach(function(k) {
                var el = document.getElementById("req" + k);
                if (el) {
                    var ok = checks[k];
                    el.className = ok ? "requirement-valid" : "requirement-invalid";
                    el.innerHTML = '<i class="fas fa-' + (ok ? "check-circle" : "circle") + ' me-1"></i> ' + labels[k];
                }
            });
        }

        function cancelPassword() {
            document.querySelector("#passwordForm input[name=current_password]").value = "";
            document.querySelector("#passwordForm input[name=new_password]").value = "";
            document.querySelector("#passwordForm input[name=confirm_password]").value = "";
            updatePasswordRequirements();
            document.querySelectorAll("#passwordForm .field-error").forEach(function(el) { el.remove(); });
        }

        function showFieldError(fieldId, message) {
            var container = document.getElementById(fieldId + "-error");
            if (container) {
                var errorDiv = document.createElement("div");
                errorDiv.className = "field-error";
                errorDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + message;
                container.appendChild(errorDiv);
            }
        }

        document.getElementById("passwordForm").addEventListener("submit", function(e) {
            var isValid = true;
            document.querySelectorAll("#passwordForm .field-error").forEach(function(el) { el.remove(); });
            var currentPwd  = document.querySelector("#passwordForm input[name=current_password]").value;
            var newPwd      = document.getElementById("newPassword").value;
            var confirmPwd  = document.getElementById("confirmPassword").value;
            if (!currentPwd) { showFieldError("currentPassword", "Le mot de passe actuel est obligatoire."); isValid = false; }
            if (!newPwd) { showFieldError("newPassword", "Le nouveau mot de passe est obligatoire."); isValid = false; }
            else if (newPwd.length < 8) { showFieldError("newPassword", "Le mot de passe doit contenir au moins 8 caractères."); isValid = false; }
            else if (!/[A-Z]/.test(newPwd)) { showFieldError("newPassword", "Le mot de passe doit contenir au moins une majuscule."); isValid = false; }
            else if (!/[0-9]/.test(newPwd)) { showFieldError("newPassword", "Le mot de passe doit contenir au moins un chiffre."); isValid = false; }
            if (!confirmPwd) { showFieldError("confirmPassword", "La confirmation est obligatoire."); isValid = false; }
            else if (newPwd !== confirmPwd) { showFieldError("confirmPassword", "Les mots de passe ne correspondent pas."); isValid = false; }
            if (!isValid) e.preventDefault();
        });

        function registerFaceId() {
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                document.getElementById("faceIdStatus").innerHTML = '<div class="alert alert-danger">Votre navigateur ne supporte pas la caméra.</div>';
                return;
            }
            document.getElementById("faceIdStatus").innerHTML = '<div class="alert alert-info"><i class="fas fa-spinner fa-spin me-2"></i>Accès à la caméra...</div>';
            navigator.mediaDevices.getUserMedia({ video: true })
                .then(function(stream) {
                    stream.getTracks().forEach(function(track) { track.stop(); });
                    window.location.href = "index.php?page=register_face";
                })
                .catch(function(err) {
                    document.getElementById("faceIdStatus").innerHTML = '<div class="alert alert-danger">Erreur d\'accès à la caméra: ' + err.message + '</div>';
                });
        }

        function updateFaceId() {
            if (confirm("Voulez-vous mettre à jour votre visage ?")) {
                window.location.href = "index.php?page=register_face";
            }
        }

        function deleteFaceId() {
            if (confirm("Êtes-vous sûr de vouloir supprimer votre visage enregistré ?")) {
                fetch("index.php?page=api&action=delete_face", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ user_id: userId })
                })
                .then(function(response) { return response.json(); })
                .then(function(data) {
                    if (data.success) {
                        document.getElementById("faceIdStatus").innerHTML = '<div class="alert alert-success">Visage supprimé avec succès.</div>';
                        setTimeout(function() { location.reload(); }, 1500);
                    } else {
                        document.getElementById("faceIdStatus").innerHTML = '<div class="alert alert-danger">Erreur: ' + data.message + '</div>';
                    }
                });
            }
        }

        if (document.getElementById("newPassword")) {
            document.getElementById("newPassword").addEventListener("input", updatePasswordRequirements);
            updatePasswordRequirements();
        }
        </script>
JS;

        $this->renderTemporaryView('Modifier mon profil', $content);
    }

public function monProfil(): void {
    $this->requireLogin();
    
    $userId = $_SESSION['user_id'];
    $userRole = $_SESSION['user_role'];
    
    // Récupérer les infos utilisateur
    $userModel = new User();
    $user = $userModel->getUserById($userId);
    
    // Statistiques
    $rdvModel = new RendezVous();
    $stats = [
        'total_rdv' => $rdvModel->countByPatient($userId),
        'rdv_avenir' => $rdvModel->countFutureByPatient($userId),
        'note_moyenne' => $rdvModel->getAverageNoteByPatient($userId)
    ];
    
    // Traitement des formulaires
    $success = '';
    $error = '';
    $successPassword = '';
    $errorPassword = '';
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        
        // Mise à jour du profil
        if ($action === 'update_profile') {
            $nom = trim($_POST['nom'] ?? '');
            $prenom = trim($_POST['prenom'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $telephone = trim($_POST['telephone'] ?? '');
            $date_naissance = trim($_POST['date_naissance'] ?? '');
            $groupe_sanguin = trim($_POST['groupe_sanguin'] ?? '');
            $adresse = trim($_POST['adresse'] ?? '');
            
            // Validation
            $errors = [];
            if (empty($nom)) $errors[] = "Le nom est obligatoire";
            if (empty($prenom)) $errors[] = "Le prénom est obligatoire";
            if (empty($email)) $errors[] = "L'email est obligatoire";
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email invalide";
            
            if (empty($errors)) {
                if ($userModel->updateProfile($userId, $nom, $prenom, $email, $telephone, $date_naissance, $groupe_sanguin, $adresse)) {
                    $success = "Profil mis à jour avec succès";
                    // Recharger les données
                    $user = $userModel->getUserById($userId);
                    $_SESSION['user_name'] = $prenom . ' ' . $nom;
                    $_SESSION['user_email'] = $email;
                } else {
                    $error = "Erreur lors de la mise à jour du profil";
                }
            } else {
                $error = implode(", ", $errors);
            }
        }
        
        // Changement de mot de passe
        elseif ($action === 'change_password') {
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            
            $errors = [];
            if (empty($currentPassword)) $errors[] = "Mot de passe actuel requis";
            if (empty($newPassword)) $errors[] = "Nouveau mot de passe requis";
            if ($newPassword !== $confirmPassword) $errors[] = "Les mots de passe ne correspondent pas";
            if (strlen($newPassword) < 8) $errors[] = "8 caractères minimum";
            if (!preg_match('/[A-Z]/', $newPassword)) $errors[] = "Une majuscule requise";
            if (!preg_match('/[0-9]/', $newPassword)) $errors[] = "Un chiffre requis";
            
            if (empty($errors)) {
                if ($userModel->changePassword($userId, $currentPassword, $newPassword)) {
                    $successPassword = "Mot de passe modifié avec succès";
                } else {
                    $errorPassword = "Mot de passe actuel incorrect";
                }
            } else {
                $errorPassword = implode(", ", $errors);
            }
        }
        
        // Mise à jour de l'avatar (traitement séparé)
        elseif ($action === 'update_avatar' && isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/valorys_Copie/uploads/avatars/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            
            $file = $_FILES['avatar'];
            $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/webp'];
            
            if (!in_array($file['type'], $allowedTypes)) {
                $error = "Format d'image non supporté";
            } elseif ($file['size'] > 2 * 1024 * 1024) {
                $error = "Image trop volumineuse (max 2Mo)";
            } else {
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = 'avatar_' . $userId . '_' . time() . '.' . $extension;
                $filepath = $uploadDir . $filename;
                
                if (move_uploaded_file($file['tmp_name'], $filepath)) {
                    if ($userModel->updateAvatar($userId, 'uploads/avatars/' . $filename)) {
                        $success = "Avatar mis à jour avec succès";
                        $user = $userModel->getUserById($userId);
                    } else {
                        $error = "Erreur lors de l'enregistrement";
                    }
                } else {
                    $error = "Erreur lors de l'upload";
                }
            }
        }
        
        // Enregistrement facial
        elseif ($action === 'register_face') {
            $input = json_decode(file_get_contents('php://input'), true);
            $imageData = $input['image'] ?? '';
            
            if (empty($imageData)) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Aucune image reçue']);
                return;
            }
            
            // Décoder l'image base64
            if (preg_match('/^data:image\/(\w+);base64,/', $imageData, $matches)) {
                $imageData = substr($imageData, strpos($imageData, ',') + 1);
                $imageData = base64_decode($imageData);
                $extension = $matches[1];
                
                $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/valorys_Copie/uploads/faces/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                
                $filename = 'face_' . $userId . '_' . time() . '.' . $extension;
                $filepath = $uploadDir . $filename;
                
                if (file_put_contents($filepath, $imageData)) {
                    if ($userModel->updateFaceEncoding($userId, 'uploads/faces/' . $filename)) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => true, 'message' => 'Visage enregistré avec succès']);
                        return;
                    }
                }
            }
            
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'enregistrement']);
            return;
        }
    }
    
    // Afficher la vue
    include __DIR__ . '/../views/frontoffice/profil.php';
}

    // =============================================
    // PAGES D'ERREUR
    // =============================================

    public function page404(): void {
        http_response_code(404);
        $content = '
        <div class="text-center py-5">
            <i class="fas fa-exclamation-triangle fa-4x text-warning mb-3 d-block"></i>
            <h1 class="display-1 text-danger fw-bold">404</h1>
            <h2 class="mb-3">Page non trouvée</h2>
            <p class="lead text-muted mb-4">La page que vous recherchez n\'existe pas ou a été supprimée.</p>
            <a href="index.php?page=accueil" class="btn btn-primary btn-lg"><i class="fas fa-home"></i> Retour à l\'accueil</a>
        </div>';
        $this->renderErrorView('Erreur 404', $content);
    }

    public function page403(): void {
        http_response_code(403);
        $content = '
        <div class="text-center py-5">
            <i class="fas fa-lock fa-4x text-danger mb-3 d-block"></i>
            <h1 class="display-1 text-danger fw-bold">403</h1>
            <h2 class="mb-3">Accès refusé</h2>
            <p class="lead text-muted mb-4">Vous n\'avez pas les permissions nécessaires.</p>
            <a href="index.php?page=accueil" class="btn btn-primary btn-lg"><i class="fas fa-home"></i> Retour à l\'accueil</a>
        </div>';
        $this->renderErrorView('Erreur 403', $content);
    }

    // =============================================
    // VUES
    // =============================================

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
                            <div class="card-header bg-white"><h3 class="mb-0"><?= htmlspecialchars($title) ?></h3></div>
                            <div class="card-body"><?= $content ?></div>
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
                    <a class="navbar-brand" href="index.php?page=accueil"><i class="fas fa-hospital-user"></i> Valorys</a>
                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"><span class="navbar-toggler-icon"></span></button>
                    <div class="collapse navbar-collapse" id="navbarNav">
                        <ul class="navbar-nav me-auto">
                            <li class="nav-item"><a class="nav-link" href="index.php?page=accueil">Accueil</a></li>
                            <li class="nav-item"><a class="nav-link" href="index.php?page=medecins">Médecins</a></li>
                            <li class="nav-item"><a class="nav-link" href="index.php?page=blog_public">Blog</a></li>
                            <li class="nav-item"><a class="nav-link" href="index.php?page=evenements">Événements</a></li>
                            <li class="nav-item"><a class="nav-link" href="index.php?page=pharmacie"><i class="fas fa-pills me-1"></i>Pharmacie</a></li>
                            <li class="nav-item"><a class="nav-link" href="index.php?page=sponsors"><i class="fas fa-handshake me-1"></i>Sponsors</a></li>
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
                                    <?php if (($_SESSION['user_role'] ?? '') === 'admin'): ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="index.php?page=dashboard"><i class="fas fa-tachometer-alt"></i> Administration</a></li>
                                    <?php endif; ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item text-danger" href="index.php?page=logout"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
                                </ul>
                            </li>
                            <?php else: ?>
                            <li class="nav-item"><a class="nav-link" href="index.php?page=login">Connexion</a></li>
                            <li class="nav-item"><a class="nav-link" href="index.php?page=register">Inscription</a></li>
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
                            <div class="card-header bg-white"><h3 class="mb-0"><?= htmlspecialchars($title) ?></h3></div>
                            <div class="card-body"><?= $content ?></div>
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
                <div class="row justify-content-center">
                    <div class="col-md-6">
                        <div class="card shadow">
                            <div class="card-body text-center py-5"><?= $content ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </body>
        </html>
        <?php
    }

private function getFlashMessages(): string {
    $html = '';
 
    // Flash simple (success / error)
    if (isset($_SESSION['success'])) {
        $html .= '<div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle me-2"></i>'
            . htmlspecialchars($_SESSION['success'])
            . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
        unset($_SESSION['success']);
    }
    if (isset($_SESSION['error'])) {
        $html .= '<div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle me-2"></i>'
            . htmlspecialchars($_SESSION['error'])
            . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
        unset($_SESSION['error']);
    }
 
    // Flash structuré ['type' => ..., 'message' => ...]
    if (isset($_SESSION['flash'])) {
        $f   = $_SESSION['flash'];
        $map = ['success' => 'success', 'error' => 'danger', 'warning' => 'warning', 'info' => 'info'];
        $ico = ['success' => 'check-circle', 'error' => 'exclamation-circle', 'warning' => 'exclamation-triangle', 'info' => 'info-circle'];
        $bc  = $map[$f['type']]  ?? 'secondary';
        $ic  = $ico[$f['type']]  ?? 'info-circle';
        $html .= '<div class="alert alert-' . $bc . ' alert-dismissible fade show">
            <i class="fas fa-' . $ic . ' me-2"></i>'
            . htmlspecialchars($f['message'])
            . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
        unset($_SESSION['flash']);
    }
 
    return $html;
}

    // =============================================
    // COMPOSANTS HTML
    // =============================================

    private function getCustomStyles(): string {
        return '
        <style>
            :root {
                --primary:#2A7FAA;--primary-dark:#1e5f80;--primary-light:#e0f0f5;
                --secondary:#4CAF50;--secondary-dark:#3d8b40;
                --text-dark:#1a3a6b;--bg-light:#f0f6ff;--border:#d0e4f7;
                --shadow:0 4px 12px rgba(42,127,170,0.15);--shadow-lg:0 10px 30px rgba(42,127,170,0.2);
            }
            body { font-family:"Segoe UI",Tahoma,Geneva,Verdana,sans-serif;background:var(--bg-light);color:var(--text-dark); }
            .navbar-custom { background:linear-gradient(135deg,#2A7FAA 0%,#4CAF50 100%);box-shadow:var(--shadow);padding:0.8rem 2rem; }
            .navbar-custom .navbar-brand { font-size:1.5rem;font-weight:700; }
            .dropdown-menu { border:none;border-radius:12px;box-shadow:var(--shadow-lg); }
            .dropdown-item { padding:0.75rem 1rem;transition:all 0.2s; }
            .dropdown-item:hover { background:var(--primary-light);color:var(--primary); }
            .btn-primary { background:linear-gradient(135deg,var(--primary) 0%,var(--secondary) 100%);border:none;border-radius:10px;font-weight:500;padding:0.6rem 1.2rem;transition:all 0.3s; }
            .btn-primary:hover { transform:translateY(-2px);box-shadow:0 8px 16px rgba(42,127,170,0.3); }
            .card { border:1px solid var(--border);border-radius:15px;transition:all 0.3s; }
            .card:hover { transform:translateY(-5px);box-shadow:var(--shadow-lg); }
            .card-header { background:linear-gradient(135deg,var(--primary-light) 0%,rgba(76,175,80,0.1) 100%);border-bottom:2px solid var(--border); }
            .table thead th { background:linear-gradient(135deg,var(--primary) 0%,var(--secondary) 100%);color:white;border:none;padding:1rem; }
            .table tbody tr:hover { background:var(--bg-light); }
            .form-control { border:1px solid var(--border);border-radius:8px;padding:0.6rem 1rem;transition:all 0.3s; }
            .form-control:focus { border-color:var(--primary);box-shadow:0 0 0 0.2rem rgba(42,127,170,0.1); }
            .avatar { width:48px;height:48px;border-radius:50%;background:linear-gradient(135deg,var(--primary) 0%,var(--secondary) 100%);display:flex;align-items:center;justify-content:center;color:white;font-weight:bold;font-size:1.2rem; }
        </style>';
    }

    private function getPublicNavbar(): string {
        $isLoggedIn = !empty($_SESSION['user_id']);
        $userName   = htmlspecialchars($_SESSION['user_name'] ?? 'Compte');
        $userRole   = $_SESSION['user_role'] ?? 'guest';
        $rightLinks = $isLoggedIn ? '
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                <span class="avatar me-2" style="width:32px;height:32px;font-size:0.9rem;">' . strtoupper(substr($userName, 0, 1)) . '</span>' . $userName . '
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="index.php?page=mon_profil"><i class="fas fa-user me-2"></i>Mon profil</a></li>
                <li><a class="dropdown-item" href="index.php?page=modifier_profil"><i class="fas fa-edit me-2"></i>Modifier le profil</a></li>
                <li><a class="dropdown-item" href="index.php?page=mes_rendez_vous"><i class="fas fa-calendar me-2"></i>Mes rendez-vous</a></li>
                ' . ($userRole === 'admin' ? '<li><hr class="dropdown-divider"></li><li><a class="dropdown-item" href="index.php?page=dashboard"><i class="fas fa-cog me-2"></i>Administration</a></li>' : '') . '
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-danger" href="index.php?page=logout"><i class="fas fa-sign-out-alt me-2"></i>Déconnexion</a></li>
            </ul>
        </li>'
        : '
        <li class="nav-item"><a class="nav-link" href="index.php?page=login"><i class="fas fa-sign-in-alt me-1"></i>Connexion</a></li>
        <li class="nav-item"><a class="nav-link btn btn-light ms-2" href="index.php?page=register"><i class="fas fa-user-plus me-1"></i>Inscription</a></li>';
        return '
        <nav class="navbar navbar-expand-lg navbar-dark navbar-custom sticky-top">
            <div class="container">
                <a class="navbar-brand fw-bold" href="index.php?page=accueil"><i class="fas fa-hospital-user"></i> Valorys</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"><span class="navbar-toggler-icon"></span></button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav mx-auto">
                        <li class="nav-item"><a class="nav-link" href="index.php?page=accueil"><i class="fas fa-home me-1"></i>Accueil</a></li>
                        <li class="nav-item"><a class="nav-link" href="index.php?page=medecins"><i class="fas fa-user-md me-1"></i>Médecins</a></li>
                        <li class="nav-item"><a class="nav-link" href="index.php?page=blog_public"><i class="fas fa-blog me-1"></i>Blog</a></li>
                        <li class="nav-item"><a class="nav-link" href="index.php?page=evenements"><i class="fas fa-calendar-alt me-1"></i>Événements</a></li>
                        <li class="nav-item"><a class="nav-link" href="index.php?page=pharmacie"><i class="fas fa-pills me-1"></i>Pharmacie</a></li>
                        <li class="nav-item"><a class="nav-link" href="index.php?page=sponsors"><i class="fas fa-handshake me-1"></i>Sponsors</a></li>
                        <li class="nav-item"><a class="nav-link" href="index.php?page=contact"><i class="fas fa-envelope me-1"></i>Contact</a></li>
                    </ul>
                    <ul class="navbar-nav ms-auto">' . $rightLinks . '</ul>
                </div>
            </div>
        </nav>';
    }

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
    // PAGE D'ACCUEIL
    // =============================================

    private function getPublicDashboardHTML(): string {
        $isLoggedIn = !empty($_SESSION['user_id']);
        $userRole   = $_SESSION['user_role'] ?? '';
        $userName   = htmlspecialchars($_SESSION['user_name'] ?? 'Utilisateur');
        if ($isLoggedIn) {
            $roleContent = '';
            if ($userRole === 'admin') {
                $roleContent .= '<div class="col-md-3"><div class="card h-100 text-center p-3"><div class="card-body"><i class="fas fa-tachometer-alt fa-3x text-danger mb-3"></i><h5>Administration</h5><a href="index.php?page=dashboard" class="btn btn-danger btn-sm">Tableau de bord</a></div></div></div>';
            }
            if (in_array($userRole, ['patient', 'admin'])) {
                $roleContent .= '
                <div class="col-md-3"><div class="card h-100 text-center p-3"><div class="card-body"><i class="fas fa-calendar-check fa-3x text-primary mb-3"></i><h5>Mes rendez-vous</h5><a href="index.php?page=mes_rendez_vous" class="btn btn-primary btn-sm">Voir</a></div></div></div>
                <div class="col-md-3"><div class="card h-100 text-center p-3"><div class="card-body"><i class="fas fa-prescription fa-3x text-success mb-3"></i><h5>Mes ordonnances</h5><a href="index.php?page=mes_ordonnances" class="btn btn-success btn-sm">Voir</a></div></div></div>';
            }
            if ($userRole === 'medecin') {
                $roleContent .= '
                <div class="col-md-3"><div class="card h-100 text-center p-3"><div class="card-body"><i class="fas fa-calendar-alt fa-3x text-primary mb-3"></i><h5>Mes rendez-vous</h5><a href="index.php?page=mes_rendez_vous" class="btn btn-primary btn-sm">Voir</a></div></div></div>
                <div class="col-md-3"><div class="card h-100 text-center p-3"><div class="card-body"><i class="fas fa-clock fa-3x text-info mb-3"></i><h5>Disponibilités</h5><a href="index.php?page=disponibilites" class="btn btn-info btn-sm">Gérer</a></div></div></div>';
            }
            $roleContent .= '
            <div class="col-md-3"><div class="card h-100 text-center p-3"><div class="card-body"><i class="fas fa-blog fa-3x text-warning mb-3"></i><h5>Blog médical</h5><a href="index.php?page=blog_public" class="btn btn-warning btn-sm">Lire le blog</a></div></div></div>
            <div class="col-md-3"><div class="card h-100 text-center p-3"><div class="card-body"><i class="fas fa-user-circle fa-3x text-secondary mb-3"></i><h5>Mon profil</h5><a href="index.php?page=mon_profil" class="btn btn-secondary btn-sm">Mon profil</a></div></div></div>
            <div class="col-md-3"><div class="card h-100 text-center p-3"><div class="card-body"><i class="fas fa-pills fa-3x mb-3" style="color:#2A7FAA"></i><h5>Pharmacie</h5><p class="text-muted small">Achetez vos médicaments</p><a href="index.php?page=pharmacie" class="btn btn-sm" style="background:linear-gradient(135deg,#2A7FAA,#4CAF50);color:white;border:none">Découvrir</a></div></div></div>';
            return '
            <div class="text-center mb-5"><h1 class="display-4 mb-3">Bonjour, ' . $userName . '&nbsp;!</h1><p class="lead text-muted">Bienvenue sur votre espace Valorys</p></div>
            <div class="row g-4 mb-5">' . $roleContent . '</div>
            <div class="row g-4">
                <div class="col-md-6"><div class="card"><div class="card-header bg-white"><h5 class="mb-0"><i class="fas fa-calendar-alt text-primary me-2"></i>Prochain Rendez-vous</h5></div><div class="card-body text-center py-4"><p class="text-muted">Aucun rendez-vous planifié</p><a href="index.php?page=medecins" class="btn btn-primary btn-sm">Prendre un rendez-vous</a></div></div></div>
                <div class="col-md-6"><div class="card"><div class="card-header bg-white"><h5 class="mb-0"><i class="fas fa-ticket-alt text-success me-2"></i>Événements à Venir</h5></div><div class="card-body"><div class="d-flex justify-content-between mb-2"><span>🏥 Conférence sur la cardiologie</span><small class="text-muted">15 Avril 2026</small></div><div class="d-flex justify-content-between"><span>🍎 Atelier bien-être</span><small class="text-muted">22 Avril 2026</small></div><div class="text-center mt-3"><a href="index.php?page=evenements" class="btn btn-sm btn-outline-primary">Voir tous</a></div></div></div></div>
            </div>';
        }
        return '
        <div class="text-center mb-5"><h1 class="display-4 mb-3">Bienvenue sur Valorys!</h1><p class="lead text-muted">Connectez-vous pour accéder à tous nos services</p></div>
        <div class="row g-4 mb-5">
            <div class="col-md-3"><div class="card h-100 text-center p-3"><div class="card-body"><i class="fas fa-calendar-check fa-3x text-primary mb-3"></i><h5>Prendre Rendez-vous</h5><a href="index.php?page=login" class="btn btn-primary btn-sm">Se connecter</a></div></div></div>
            <div class="col-md-3"><div class="card h-100 text-center p-3"><div class="card-body"><i class="fas fa-prescription fa-3x text-success mb-3"></i><h5>Ordonnances</h5><a href="index.php?page=login" class="btn btn-primary btn-sm">Se connecter</a></div></div></div>
            <div class="col-md-3"><div class="card h-100 text-center p-3"><div class="card-body"><i class="fas fa-blog fa-3x text-warning mb-3"></i><h5>Blog médical</h5><a href="index.php?page=blog_public" class="btn btn-warning btn-sm">Lire le blog</a></div></div></div>
            <div class="col-md-3"><div class="card h-100 text-center p-3"><div class="card-body"><i class="fas fa-exclamation-circle fa-3x text-warning mb-3"></i><h5>Réclamations</h5><a href="index.php?page=login" class="btn btn-primary btn-sm">Se connecter</a></div></div></div>
        </div>
        <div class="row g-4">
            <div class="col-md-6"><div class="card"><div class="card-header bg-white"><h5 class="mb-0"><i class="fas fa-calendar-alt text-primary me-2"></i>Prochain Rendez-vous</h5></div><div class="card-body text-center py-4"><p class="text-muted">Connectez-vous pour voir vos rendez-vous</p><a href="index.php?page=login" class="btn btn-primary">Se connecter</a></div></div></div>
            <div class="col-md-6"><div class="card"><div class="card-header bg-white"><h5 class="mb-0"><i class="fas fa-ticket-alt text-success me-2"></i>Événements à Venir</h5></div><div class="card-body"><div class="d-flex justify-content-between mb-2"><span>🏥 Conférence sur la cardiologie</span><small class="text-muted">15 Avril 2026</small></div><div class="d-flex justify-content-between"><span>🍎 Atelier bien-être</span><small class="text-muted">22 Avril 2026</small></div><div class="text-center mt-3"><a href="index.php?page=evenements" class="btn btn-sm btn-outline-primary">Voir tous</a></div></div></div></div>
        </div>';
    }






        // =============================================
    // MÉTHODES POUR LES DISPONIBILITÉS
    // =============================================

    private function getPatientDisponibilitesHTML($disponibilites, $medecins): string {
        $html = '
        <div class="filter-section mb-4">
            <h5><i class="fas fa-filter me-2"></i>Filtrer</h5>
            <form method="GET" class="row g-3">
                <input type="hidden" name="page" value="patient_disponibilites">
                <div class="col-md-4">
                    <select name="medecin_id" class="form-select">
                        <option value="">Tous les médecins</option>';
        
        foreach ($medecins as $medecin) {
            $selected = (isset($_GET['medecin_id']) && $_GET['medecin_id'] == $medecin['user_id']) ? 'selected' : '';
            $html .= '<option value="' . $medecin['user_id'] . '" ' . $selected . '>
                        Dr. ' . htmlspecialchars($medecin['prenom'] . ' ' . $medecin['nom']) . ' - ' . htmlspecialchars($medecin['specialite'] ?? 'Généraliste') . '
                    </option>';
        }
        
        $html .= '
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="jour" class="form-select">
                        <option value="">Tous les jours</option>
                        <option value="Lundi" ' . ((isset($_GET['jour']) && $_GET['jour'] == 'Lundi') ? 'selected' : '') . '>Lundi</option>
                        <option value="Mardi" ' . ((isset($_GET['jour']) && $_GET['jour'] == 'Mardi') ? 'selected' : '') . '>Mardi</option>
                        <option value="Mercredi" ' . ((isset($_GET['jour']) && $_GET['jour'] == 'Mercredi') ? 'selected' : '') . '>Mercredi</option>
                        <option value="Jeudi" ' . ((isset($_GET['jour']) && $_GET['jour'] == 'Jeudi') ? 'selected' : '') . '>Jeudi</option>
                        <option value="Vendredi" ' . ((isset($_GET['jour']) && $_GET['jour'] == 'Vendredi') ? 'selected' : '') . '>Vendredi</option>
                        <option value="Samedi" ' . ((isset($_GET['jour']) && $_GET['jour'] == 'Samedi') ? 'selected' : '') . '>Samedi</option>
                        <option value="Dimanche" ' . ((isset($_GET['jour']) && $_GET['jour'] == 'Dimanche') ? 'selected' : '') . '>Dimanche</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary">Filtrer</button>
                    <a href="index.php?page=patient_disponibilites" class="btn btn-secondary ms-2">Réinitialiser</a>
                </div>
            </form>
        </div>
        <div class="row">';
        
        if (empty($disponibilites)) {
            $html .= '<div class="col-12"><div class="alert alert-info text-center py-4">Aucune disponibilité pour le moment.</div></div>';
        } else {
            foreach ($disponibilites as $dispo) {
                $html .= '
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title text-primary">Dr. ' . htmlspecialchars($dispo['medecin_nom'] ?? 'N/A') . '</h5>
                            <p class="card-text text-muted">' . htmlspecialchars($dispo['specialite'] ?? 'Généraliste') . '</p>
                            <hr>
                            <p><i class="fas fa-calendar me-2 text-success"></i> ' . htmlspecialchars($dispo['jour_semaine']) . '</p>
                            <p><i class="fas fa-clock me-2 text-success"></i> ' . date('H:i', strtotime($dispo['heure_debut'])) . ' - ' . date('H:i', strtotime($dispo['heure_fin'])) . '</p>
                            <a href="index.php?page=prendre_rendez_vous&id=' . $dispo['medecin_id'] . '" class="btn btn-primary btn-sm w-100">
                                <i class="fas fa-calendar-check me-2"></i>Prendre rendez-vous
                            </a>
                        </div>
                    </div>
                </div>';
            }
        }
        
        $html .= '</div>';
        return $html;
    }

private function getMedecinDisponibilitesHTML($dispos): string {
    $html = '
    <style>
    .dispo-overlay {
        display: none;
        position: fixed;
        top: 0; left: 0;
        width: 100%; height: 100%;
        background: rgba(0,0,0,0.4);
        backdrop-filter: blur(4px);
        z-index: 9998;
    }
    .dispo-modal {
        display: none;
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 90%;
        max-width: 480px;
        z-index: 9999;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 25px 60px rgba(0,0,0,0.3);
        animation: popIn 0.25s ease;
    }
    @keyframes popIn {
        from { opacity: 0; transform: translate(-50%, -48%) scale(0.96); }
        to   { opacity: 1; transform: translate(-50%, -50%) scale(1); }
    }
    .dispo-modal .modal-header {
        padding: 20px 24px;
        border: none;
    }
    .dispo-modal .modal-header h5 {
        font-size: 1.1rem;
        font-weight: 700;
        margin: 0;
    }
    .dispo-modal .modal-body {
        background: #fff;
        padding: 24px;
    }
    .dispo-modal .modal-footer {
        background: #f8f9fa;
        padding: 16px 24px;
        border-top: 1px solid #eee;
        display: flex;
        justify-content: flex-end;
        gap: 10px;
    }
    .dispo-modal .form-label {
        font-weight: 600;
        color: #444;
        font-size: 0.875rem;
        margin-bottom: 6px;
    }
    .dispo-modal .form-control,
    .dispo-modal .form-select {
        border-radius: 10px;
        border: 1.5px solid #dee2e6;
        padding: 10px 14px;
        font-size: 0.95rem;
        transition: border-color 0.2s;
    }
    .dispo-modal .form-control:focus,
    .dispo-modal .form-select:focus {
        border-color: #2A7FAA;
        box-shadow: 0 0 0 3px rgba(42,127,170,0.12);
    }
    .dispo-modal .btn {
        border-radius: 25px;
        padding: 8px 24px;
        font-weight: 600;
        font-size: 0.9rem;
    }
    .dispo-card {
        border: none;
        border-radius: 14px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.07);
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .dispo-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 10px 25px rgba(42,127,170,0.15);
    }
    .dispo-card .card-body {
        padding: 20px;
    }
    .dispo-time {
        font-size: 1.3rem;
        font-weight: 700;
        color: #2A7FAA;
        margin: 10px 0;
    }
    .dispo-actions {
        display: flex;
        gap: 8px;
        margin-top: 15px;
    }
    .dispo-actions .btn {
        border-radius: 20px;
        font-size: 0.8rem;
        padding: 6px 16px;
        font-weight: 600;
    }
    </style>

    <div id="dispoOverlay" class="dispo-overlay" onclick="closeAllModals()"></div>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 style="color:#2A7FAA;font-weight:700;">
            <i class="fas fa-clock me-2"></i>Mes disponibilités
        </h2>
        <button class="btn btn-success px-4" style="border-radius:25px;font-weight:600;" onclick="openAddModal()">
            <i class="fas fa-plus me-2"></i>Ajouter un créneau
        </button>
    </div>';

    if (empty($dispos)) {
        $html .= '
        <div class="text-center py-5">
            <i class="fas fa-calendar-times fa-4x mb-3" style="color:#ccc;"></i>
            <h5 class="text-muted">Aucune disponibilité définie</h5>
            <p class="text-muted">Cliquez sur "Ajouter un créneau" pour commencer.</p>
        </div>';
    } else {
        $html .= '<div class="row g-4">';
        foreach ($dispos as $dispo) {
            $badgeColor = $dispo['actif'] ? 'bg-success' : 'bg-secondary';
            $badgeLabel = $dispo['actif'] ? 'Actif' : 'Inactif';
            $html .= '
            <div class="col-md-6 col-lg-4">
                <div class="card dispo-card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 fw-bold text-dark">' . htmlspecialchars($dispo['jour_semaine']) . '</h5>
                            <span class="badge ' . $badgeColor . ' rounded-pill px-3">' . $badgeLabel . '</span>
                        </div>
                        <div class="dispo-time">
                            <i class="fas fa-clock me-1" style="font-size:1rem;"></i>
                            ' . substr($dispo['heure_debut'], 0, 5) . ' – ' . substr($dispo['heure_fin'], 0, 5) . '
                        </div>
                        <div class="dispo-actions">
                            <button type="button" class="btn btn-warning"
                                onclick="openEditModal(
                                    ' . $dispo['id'] . ',
                                    \'' . htmlspecialchars($dispo['jour_semaine']) . '\',
                                    \'' . substr($dispo['heure_debut'], 0, 5) . '\',
                                    \'' . substr($dispo['heure_fin'], 0, 5) . '\'
                                )">
                                <i class="fas fa-edit me-1"></i>Modifier
                            </button>
                            <a href="index.php?page=medecin_disponibilites&action=delete&id=' . $dispo['id'] . '"
                               class="btn btn-danger"
                               onclick="return confirm(\'Supprimer ce créneau ?\')">
                                <i class="fas fa-trash me-1"></i>Supprimer
                            </a>
                        </div>
                    </div>
                </div>
            </div>';
        }
        $html .= '</div>';
    }

    // Modal Modifier
    $html .= '
    <div id="editDispoModal" class="dispo-modal">
        <div class="modal-header" style="background:linear-gradient(135deg,#f6a623,#f0810f);color:white;">
            <h5><i class="fas fa-edit me-2"></i>Modifier la disponibilité</h5>
            <button type="button" class="btn-close btn-close-white ms-auto" onclick="closeModal(\'editDispoModal\')"></button>
        </div>
        <form method="POST" action="index.php?page=medecin_disponibilites&action=update">
            <div class="modal-body">
                <input type="hidden" name="dispo_id" id="edit_dispo_id">
                <div class="mb-3">
                    <label class="form-label">Jour <span class="text-danger">*</span></label>
                    <select name="jour_semaine" id="edit_jour" class="form-select" required>
                        <option value="Lundi">Lundi</option>
                        <option value="Mardi">Mardi</option>
                        <option value="Mercredi">Mercredi</option>
                        <option value="Jeudi">Jeudi</option>
                        <option value="Vendredi">Vendredi</option>
                        <option value="Samedi">Samedi</option>
                        <option value="Dimanche">Dimanche</option>
                    </select>
                </div>
                <div class="row g-3">
                    <div class="col-6">
                        <label class="form-label">Heure début <span class="text-danger">*</span></label>
                        <input type="time" name="heure_debut" id="edit_heure_debut" class="form-control" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Heure fin <span class="text-danger">*</span></label>
                        <input type="time" name="heure_fin" id="edit_heure_fin" class="form-control" required>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal(\'editDispoModal\')">Annuler</button>
                <button type="submit" class="btn btn-warning text-white">
                    <i class="fas fa-save me-1"></i>Enregistrer
                </button>
            </div>
        </form>
    </div>

    <!-- Modal Ajouter -->
    <div id="addDispoModal" class="dispo-modal">
        <div class="modal-header" style="background:linear-gradient(135deg,#2A7FAA,#4CAF50);color:white;">
            <h5><i class="fas fa-plus-circle me-2"></i>Ajouter une disponibilité</h5>
            <button type="button" class="btn-close btn-close-white ms-auto" onclick="closeModal(\'addDispoModal\')"></button>
        </div>
        <form method="POST" action="index.php?page=medecin_disponibilites&action=store">
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Jour <span class="text-danger">*</span></label>
                    <select name="jour_semaine" class="form-select" required>
                        <option value="">— Sélectionner un jour —</option>
                        <option value="Lundi">Lundi</option>
                        <option value="Mardi">Mardi</option>
                        <option value="Mercredi">Mercredi</option>
                        <option value="Jeudi">Jeudi</option>
                        <option value="Vendredi">Vendredi</option>
                        <option value="Samedi">Samedi</option>
                        <option value="Dimanche">Dimanche</option>
                    </select>
                </div>
                <div class="row g-3">
                    <div class="col-6">
                        <label class="form-label">Heure début <span class="text-danger">*</span></label>
                        <input type="time" name="heure_debut" class="form-control" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Heure fin <span class="text-danger">*</span></label>
                        <input type="time" name="heure_fin" class="form-control" required>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal(\'addDispoModal\')">Annuler</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i>Ajouter
                </button>
            </div>
        </form>
    </div>

    <script>
    function openEditModal(id, jour, heureDebut, heureFin) {
        document.getElementById("edit_dispo_id").value = id;
        document.getElementById("edit_jour").value = jour;
        document.getElementById("edit_heure_debut").value = heureDebut;
        document.getElementById("edit_heure_fin").value = heureFin;
        document.getElementById("editDispoModal").style.display = "block";
        document.getElementById("dispoOverlay").style.display = "block";
    }
    function openAddModal() {
        document.getElementById("addDispoModal").style.display = "block";
        document.getElementById("dispoOverlay").style.display = "block";
    }
    function closeModal(id) {
        document.getElementById(id).style.display = "none";
        document.getElementById("dispoOverlay").style.display = "none";
    }
    function closeAllModals() {
        ["editDispoModal", "addDispoModal"].forEach(function(id) {
            document.getElementById(id).style.display = "none";
        });
        document.getElementById("dispoOverlay").style.display = "none";
    }
    </script>';

    return $html;
}

    /**
 * Afficher la page d'enregistrement facial
 */

    
} // FIN DE LA CLASSE FrontController
