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
                        </td>
                    </tr>';
            }
            $content .= '</tbody></table></div>';
        }
        $this->renderTemporaryView('Nos Médecins', $content);
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
            $keyword = $_GET['keyword'] ?? '';
            $categorie = $_GET['categorie'] ?? '';
            $date_min = $_GET['date_min'] ?? '';
            $tag = $_GET['tag'] ?? '';
            $isSearch = ($keyword !== '' || $categorie !== '' || $date_min !== '' || $tag !== '');

            if ($isSearch && method_exists($articleModel, 'advancedSearch')) {
                $articles = $articleModel->advancedSearch([
                    'keyword' => $keyword,
                    'categorie' => $categorie,
                    'date_min' => $date_min,
                    'tag' => $tag
                ]);
            } else {
                $articles = $articleModel->getAll();
            }

            $searchForm = '
            <div style="background:#f8f9fa; border-radius:8px; padding:20px; margin-bottom:20px; border:1px solid #ddd;">
                <h4 style="margin-top:0; margin-bottom:15px; cursor:pointer; color:#2A7FAA; display:flex; justify-content:space-between;" onclick="var f=document.getElementById(\'advanced-search-form\'); f.style.display=(f.style.display===\'none\'?\'block\':\'none\');">
                    <span><i class="fas fa-search"></i> Recherche Avancée</span>
                    <i class="fas fa-chevron-down" style="font-size:16px;"></i>
                </h4>
                <div id="advanced-search-form" style="display:' . ($isSearch ? 'block' : 'none') . ';">
                    <form method="GET" action="index.php">
                        <input type="hidden" name="page" value="blog_public">
                        <div style="display:flex; gap:15px; flex-wrap:wrap;">
                            <div style="flex:1; min-width:200px;">
                                <label style="display:block; margin-bottom:5px; font-weight:bold;">Mot clé</label>
                                <input type="text" name="keyword" value="' . htmlspecialchars($keyword) . '" style="width:100%; padding:10px; border:1px solid #ccc; border-radius:5px;" placeholder="Rechercher dans le titre ou le contenu...">
                            </div>
                            <div style="flex:1; min-width:200px;">
                                <label style="display:block; margin-bottom:5px; font-weight:bold;">Catégorie</label>
                                <input type="text" name="categorie" value="' . htmlspecialchars($categorie) . '" style="width:100%; padding:10px; border:1px solid #ccc; border-radius:5px;" placeholder="Ex: Santé, Sport...">
                            </div>
                            <div style="flex:1; min-width:200px;">
                                <label style="display:block; margin-bottom:5px; font-weight:bold;">Tag</label>
                                <input type="text" name="tag" value="' . htmlspecialchars($tag) . '" style="width:100%; padding:10px; border:1px solid #ccc; border-radius:5px;" placeholder="Ex: nutrition...">
                            </div>
                            <div style="flex:1; min-width:200px;">
                                <label style="display:block; margin-bottom:5px; font-weight:bold;">Date (à partir de)</label>
                                <input type="date" name="date_min" value="' . htmlspecialchars($date_min) . '" style="width:100%; padding:10px; border:1px solid #ccc; border-radius:5px;">
                            </div>
                            <div style="width:100%; display:flex; justify-content:flex-end; gap:10px; margin-top:10px;">
                                <a href="index.php?page=blog_public" style="background:#6c757d; color:white; text-decoration:none; padding:10px 20px; border-radius:5px;"><i class="fas fa-times"></i> Réinitialiser</a>
                                <button type="submit" style="background:#2A7FAA; color:white; border:none; padding:10px 20px; border-radius:5px; cursor:pointer;"><i class="fas fa-search"></i> Filtrer les articles</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>';
            $isLoggedIn = isset($_SESSION['user_id']);
            $userId = $_SESSION['user_id'] ?? null;
            $userRole = $_SESSION['user_role'] ?? '';
            $isAdmin = ($userRole === 'admin');

            // Les admins sont redirigés vers la page de gestion des articles
            if ($isAdmin) {
                header('Location: index.php?page=articles_admin');
                exit;
            }

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
                $content = '<div class="alert alert-info">Aucun article disponible pour le moment.</div>';
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

            if (!$isLoggedIn) {
                $infoMessage = '
                <div style="background:#e3f2fd;border-left:4px solid #2196f3;padding:12px 20px;margin-bottom:20px;border-radius:5px;">
                    <i class="fas fa-info-circle"></i>
                    <a href="index.php?page=login" style="color:#1976d2;">Connectez-vous</a> pour créer, modifier ou supprimer vos propres articles.
                </div>';
                $fullContent = $infoMessage . $searchForm . $addButton . $content . $this->getDeleteScript();
            } else {
                $fullContent = $searchForm . $addButton . $content . $this->getDeleteScript();
            }
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
        $data = [
            'medecin_id'   => (int)$_SESSION['user_id'],
            'jour_semaine' => $_POST['jour_semaine'],
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
        $this->requireLogin();
        $this->requireMedecin();
        require_once __DIR__ . '/../models/Disponibilite.php';
        $disponibiliteModel = new Disponibilite();
        $dispo = $disponibiliteModel->getById($id);
        if ($dispo && $dispo['medecin_id'] == $_SESSION['user_id']) {
            $newStatus = $dispo['actif'] ? 0 : 1;
            $disponibiliteModel->update($id, ['actif' => $newStatus]);
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Statut mis à jour.'];
        } else {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Accès non autorisé.'];
        }
        header('Location: index.php?page=medecin_disponibilites');
        exit;
    }

    public function medecinDeleteDisponibilite(int $id): void {
        $this->requireLogin();
        $this->requireMedecin();
        require_once __DIR__ . '/../models/Disponibilite.php';
        $disponibiliteModel = new Disponibilite();
        $dispo = $disponibiliteModel->getById($id);
        if ($dispo && $dispo['medecin_id'] == $_SESSION['user_id']) {
            $disponibiliteModel->delete($id);
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Disponibilité supprimée.'];
        } else {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Accès non autorisé.'];
        }
        header('Location: index.php?page=medecin_disponibilites');
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
        
        // Les admins sont redirigés vers la page de détail admin
        if ($isAdmin) {
            header('Location: index.php?page=articles_admin&action=show&id=' . $id);
            exit;
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
            .modal { display:none;position:fixed;z-index:1000;left:0;top:0;width:100%;height:100%;background-color:rgba(0,0,0,0.5); }
            .modal-content { background-color:#fff;margin:10% auto;padding:20px;border-radius:10px;width:90%;max-width:500px;position:relative; }
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
        $isAdmin  = ($userRole === 'admin');
        $errors   = [];
        $oldData  = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $titre     = trim($_POST['titre']   ?? '');
            $contenu   = trim($_POST['contenu'] ?? '');
            $auteur_id = $_SESSION['user_id'] ?? null;
            $oldData   = ['titre' => $titre, 'contenu' => $contenu];
            $categorie = $isAdmin ? trim($_POST['categorie'] ?? '') : null;
            $tags      = $isAdmin ? trim($_POST['tags']      ?? '') : null;
            $status    = $isAdmin ? trim($_POST['status']    ?? 'publié') : 'publié';
            if (empty($titre))                $errors['titre']   = 'Le titre est obligatoire.';
            elseif (mb_strlen($titre) > 255)  $errors['titre']   = 'Le titre ne doit pas dépasser 255 caractères.';
            if (empty($contenu))              $errors['contenu'] = 'Le contenu est obligatoire.';
            elseif (mb_strlen($contenu) < 10) $errors['contenu'] = 'Le contenu doit contenir au moins 10 caractères.';
            $imagePath = null;
            if (isset($_FILES['article_image']) && $_FILES['article_image']['error'] === UPLOAD_ERR_OK) {
                $imagePath = $this->uploadArticleImage($_FILES['article_image']);
                if (!$imagePath) $errors['image'] = 'Erreur lors de l\'upload. Formats acceptés : JPG, PNG, GIF. Max 2 Mo.';
            }
            if (empty($errors)) {
                $result = $articleModel->create(['titre' => $titre, 'contenu' => $contenu, 'auteur_id' => $auteur_id, 'image' => $imagePath, 'categorie' => $categorie, 'tags' => $tags, 'status' => $status]);
                if ($result > 0) { $_SESSION['success'] = 'Article créé avec succès !'; header('Location: index.php?page=blog_public'); exit; }
                else $errors['general'] = 'Erreur lors de la création de l\'article.';
            }
        }
        if ($isAdmin) {
            header('Location: index.php?page=articles_admin&action=create');
            exit;
        }
        $content = $this->getUserArticleFormHTML('Créer un article', 'admin_article_create', null, $errors, $oldData);
        $this->renderPublicView('Créer un article', $content);
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
            header('Location: index.php?page=articles_admin&action=edit&id=' . $id);
            exit;
        }
        $content = $this->getUserArticleFormHTML('Modifier mon article', 'admin_article_edit&id=' . $id, $article, $errors);
        $this->renderPublicView('Modifier mon article', $content);
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
        <div class="row"><div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="fas fa-newspaper me-2"></i>' . htmlspecialchars($title) . '</h4>
                </div>
                <div class="card-body">
                    ' . (isset($errors['general']) ? '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> ' . htmlspecialchars($errors['general']) . '</div>' : '') . '
                    <form method="POST" action="index.php?page=' . $action . '" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label class="form-label">Titre <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="titre" value="' . $titreValue . '" style="border-color:' . (isset($errors['titre']) ? '#dc3545' : '#ddd') . ';">
                                    ' . (isset($errors['titre']) ? '<div class="text-danger small mt-1"><i class="fas fa-exclamation-circle"></i> ' . htmlspecialchars($errors['titre']) . '</div>' : '') . '
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Contenu <span class="text-danger">*</span></label>
                                    <textarea class="form-control" name="contenu" rows="15" style="border-color:' . (isset($errors['contenu']) ? '#dc3545' : '#ddd') . ';">' . $contenuValue . '</textarea>
                                    ' . (isset($errors['contenu']) ? '<div class="text-danger small mt-1"><i class="fas fa-exclamation-circle"></i> ' . htmlspecialchars($errors['contenu']) . '</div>' : '') . '
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Image à la une</label>
                                    ' . ($imageValue ? '<div class="mb-2"><img src="' . $imageValue . '" style="max-width:100%;border-radius:8px;"></div>' : '') . '
                                    <input type="file" class="form-control" name="article_image" accept="image/*">
                                    <small class="text-muted">JPG, PNG, GIF. Max 2 Mo.</small>
                                    ' . (isset($errors['image']) ? '<div class="text-danger small mt-1"><i class="fas fa-exclamation-circle"></i> ' . htmlspecialchars($errors['image']) . '</div>' : '') . '
                                    ' . ($imageValue ? '<div class="form-check mt-2"><input class="form-check-input" type="checkbox" name="delete_image" value="1" id="deleteImage"><label class="form-check-label" for="deleteImage">Supprimer l\'image</label></div>' : '') . '
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Catégorie</label>
                                    <select name="categorie" class="form-select">
                                        <option value="">-- Sélectionner --</option>
                                        <option value="actualite"  ' . ($categorieValue == 'actualite'  ? 'selected' : '') . '>Actualité</option>
                                        <option value="conseil"    ' . ($categorieValue == 'conseil'    ? 'selected' : '') . '>Conseil santé</option>
                                        <option value="temoignage" ' . ($categorieValue == 'temoignage' ? 'selected' : '') . '>Témoignage</option>
                                        <option value="evenement"  ' . ($categorieValue == 'evenement'  ? 'selected' : '') . '>Événement</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Tags</label>
                                    <input type="text" class="form-control" name="tags" value="' . $tagsValue . '" placeholder="santé, bien-être, médecine">
                                    <small class="text-muted">Séparez les tags par des virgules</small>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Statut</label>
                                    <select name="status" class="form-select">
                                        <option value="brouillon" ' . ($statusValue == 'brouillon' ? 'selected' : '') . '>📝 Brouillon</option>
                                        <option value="publié"    ' . ($statusValue == 'publié'    ? 'selected' : '') . '>✅ Publié</option>
                                        <option value="archive"   ' . ($statusValue == 'archive'   ? 'selected' : '') . '>📦 Archivé</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between">
                            <a href="index.php?page=blog_public" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Annuler</a>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> ' . $buttonText . '</button>
                        </div>
                    </form>
                </div>
            </div>
        </div></div>';
    }

    private function getUserArticleFormHTML($title, $action, $article = null, $errors = [], $oldData = []): string {
        $isEdit       = $article !== null;
        $titreValue   = $isEdit ? htmlspecialchars($article['titre']   ?? '') : htmlspecialchars($oldData['titre']   ?? '');
        $contenuValue = $isEdit ? htmlspecialchars($article['contenu'] ?? '') : htmlspecialchars($oldData['contenu'] ?? '');
        $imageValue   = $isEdit ? htmlspecialchars($article['image']   ?? '') : '';
        $buttonText   = $isEdit ? 'Mettre à jour' : 'Publier';
        $html = '
        <div style="background:white;border-radius:12px;padding:30px;box-shadow:0 2px 8px rgba(0,0,0,0.1);">
            <h3 style="margin-bottom:25px;color:#2A7FAA;">' . htmlspecialchars($title) . '</h3>
            ' . (isset($errors['general']) ? '<div style="background:#ffe6e6;border-left:4px solid #dc3545;padding:12px;border-radius:8px;margin-bottom:20px;color:#dc3545;"><i class="fas fa-exclamation-circle"></i> ' . htmlspecialchars($errors['general']) . '</div>' : '') . '
            <form method="POST" action="index.php?page=' . $action . '" enctype="multipart/form-data">
                <div style="margin-bottom:20px;">
                    <label style="display:block;font-weight:bold;margin-bottom:8px;">Titre <span style="color:red;">*</span></label>
                    <input type="text" name="titre" value="' . $titreValue . '" style="width:100%;padding:10px;border:1px solid ' . (isset($errors['titre']) ? '#dc3545' : '#ddd') . ';border-radius:8px;font-size:15px;">
                    ' . (isset($errors['titre']) ? '<div style="color:#dc3545;font-size:12px;margin-top:5px;"><i class="fas fa-exclamation-circle"></i> ' . htmlspecialchars($errors['titre']) . '</div>' : '') . '
                </div>
                <div style="margin-bottom:20px;">
                    <label style="display:block;font-weight:bold;margin-bottom:8px;">Image (optionnel)</label>
                    ' . ($imageValue ? '<div style="margin-bottom:10px;"><img src="' . $imageValue . '" style="max-width:200px;border-radius:8px;"></div>' : '') . '
                    <input type="file" name="article_image" accept="image/*" style="width:100%;padding:8px;border:1px solid ' . (isset($errors['image']) ? '#dc3545' : '#ddd') . ';border-radius:8px;">
                    <small style="color:#999;">Formats acceptés : JPG, PNG, GIF. Max 2 Mo.</small>
                    ' . (isset($errors['image']) ? '<div style="color:#dc3545;font-size:12px;margin-top:5px;"><i class="fas fa-exclamation-circle"></i> ' . htmlspecialchars($errors['image']) . '</div>' : '') . '
                    ' . ($imageValue ? '<div style="margin-top:8px;"><label><input type="checkbox" name="delete_image" value="1"> Supprimer l\'image actuelle</label></div>' : '') . '
                </div>
                <div style="margin-bottom:20px;">
                    <label style="display:block;font-weight:bold;margin-bottom:8px;">Contenu <span style="color:red;">*</span></label>
                    <textarea name="contenu" rows="12" style="width:100%;padding:10px;border:1px solid ' . (isset($errors['contenu']) ? '#dc3545' : '#ddd') . ';border-radius:8px;font-size:14px;resize:vertical;">' . $contenuValue . '</textarea>
                    <small style="color:#999;">Vous pouvez utiliser du HTML pour formater votre contenu.</small>
                    ' . (isset($errors['contenu']) ? '<div style="color:#dc3545;font-size:12px;margin-top:5px;"><i class="fas fa-exclamation-circle"></i> ' . htmlspecialchars($errors['contenu']) . '</div>' : '') . '
                </div>
                <div style="display:flex;justify-content:space-between;margin-top:20px;">
                    <a href="index.php?page=blog_public" style="background:#6c757d;color:white;padding:10px 25px;border-radius:8px;text-decoration:none;"><i class="fas fa-arrow-left"></i> Annuler</a>
                    <button type="submit" style="background:linear-gradient(135deg,#2A7FAA,#4CAF50);color:white;border:none;padding:10px 25px;border-radius:8px;cursor:pointer;font-size:15px;"><i class="fas fa-save"></i> ' . $buttonText . '</button>
                </div>
            </form>
        </div>';
        $html .= <<<'JS'
        <script>
        document.querySelectorAll("input[type=text], textarea").forEach(function(field) {
            field.addEventListener("input", function() {
                this.style.borderColor = "#ddd";
                var err = this.parentElement.querySelector("[style*=dc3545]");
                if (err) err.style.display = "none";
            });
        });
        </script>
JS;
        return $html;
    }

    // =============================================
    // ÉVÉNEMENTS / CONTACT / À PROPOS
    // =============================================

    public function listeEvenements(): void {
        require_once __DIR__ . '/../models/Event.php';
        $eventModel = new Event();
        $events = $eventModel->getAll();
        $upcomingEvents = $eventModel->getUpcoming();
        $featuredEvents = $eventModel->getFeatured();
        
        $isLoggedIn = isset($_SESSION['user_id']);
        
        $content = '
        <style>
            body { background: #f5f7fb; font-family: \'Segoe UI\', sans-serif; }
            .navbar { background: #1a2035; }
            .navbar-brand, .nav-link { color: white !important; }
            .event-header {
                background: linear-gradient(135deg, #2A7FAA 0%, #4CAF50 100%);
                color: white; padding: 60px 0; text-align: center; margin-bottom: 40px;
            }
            .event-header h1 { font-size: 2.5rem; margin-bottom: 10px; }
            .event-header .lead { font-size: 1.2rem; opacity: 0.9; }
            .event-card {
                background: white; border-radius: 16px; padding: 0;
                margin-bottom: 25px; box-shadow: 0 5px 15px rgba(0,0,0,0.08);
                transition: transform 0.3s; overflow: hidden; position: relative;
            }
            .event-card:hover { transform: translateY(-5px); box-shadow: 0 8px 25px rgba(0,0,0,0.15); }
            .event-image { height: 200px; background-size: cover; background-position: center; background-color: #e9ecef; }
            .event-status-badge {
                position: absolute; top: 10px; left: 10px; background: #4CAF50;
                color: white; padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: bold;
            }
            .event-body { padding: 20px; }
            .event-title { font-size: 1.25rem; font-weight: 700; margin-bottom: 8px; }
            .event-title a { color: #1a2035; text-decoration: none; }
            .event-title a:hover { color: #2A7FAA; }
            .event-meta { font-size: 13px; color: #6c757d; margin-bottom: 8px; display: flex; align-items: center; gap: 10px; }
            .event-meta i { color: #2A7FAA; width: 16px; }
            .event-excerpt { color: #555; font-size: 14px; margin-bottom: 15px; line-height: 1.5; }
            .event-footer { display: flex; justify-content: space-between; align-items: center; padding-top: 15px; border-top: 1px solid #eee; }
            .event-price { font-size: 18px; font-weight: bold; color: #2A7FAA; }
            .event-price .tnf { font-size: 12px; color: #6c757d; }
            .btn-register {
                background: linear-gradient(135deg, #2A7FAA, #4CAF50);
                color: white; border: none; border-radius: 25px;
                padding: 8px 20px; font-size: 13px; text-decoration: none; display: inline-block;
                transition: opacity 0.3s;
            }
            .btn-register:hover { opacity: 0.9; color: white; }
            .no-events { text-align: center; padding: 40px 20px; color: #6c757d; }
            footer { background: #1a2035; color: white; text-align: center; padding: 30px; margin-top: 50px; }
        </style>

        <!-- EN-TÊTE -->
        <div class="event-header">
            <div class="container">
                <h1><i class="fas fa-calendar-alt me-3"></i>Événements médicaux</h1>
                <p class="lead">Conférences, ateliers et rencontres médicales</p>
            </div>
        </div>

        <!-- SPONSORS SHOWCASE -->
        <div style="background: white; padding: 30px 0; border-bottom: 1px solid #e0e0e0;">
            <div class="container">
                <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 20px;">
                    <div>
                        <h5 style="color: #2A7FAA; font-weight: 700; margin-bottom: 5px;"><i class="fas fa-handshake me-2"></i>Nos Sponsors</h5>
                        <p style="margin: 0; color: #666; font-size: 14px;">Partenaires qui soutiennent nos événements</p>
                    </div>
                    <a href="index.php?page=sponsors" class="btn btn-primary" style="background: linear-gradient(135deg, #2A7FAA, #4CAF50); border: none; border-radius: 25px; padding: 10px 25px; font-weight: 600; text-decoration: none;">
                        <i class="fas fa-arrow-right me-2"></i>Voir tous les sponsors
                    </a>
                </div>
            </div>
        </div>

        <!-- CONTENU -->
        <div class="container mb-5">
            <div class="row" id="eventsList">';

        if (empty($upcomingEvents)) {
            $content .= '<div class="col-12"><div class="no-events"><i class="fas fa-calendar-check fa-3x mb-3"></i><p>Aucun événement disponible pour le moment.</p></div></div>';
        } else {
            foreach ($upcomingEvents as $event) {
                $dateDebut = date('d/m/Y', strtotime($event['date_debut']));
                $dateFin = date('d/m/Y', strtotime($event['date_fin']));
                $prix = $event['prix'] ?? 0;
                $prixText = $prix > 0 ? $prix . ' DT' : 'GRATUIT';
                $lieu = $event['lieu'] ?? 'À déterminer';
                $description = htmlspecialchars(substr($event['description'] ?? '', 0, 150)) . '...';
                $image = htmlspecialchars($event['image'] ?? 'https://via.placeholder.com/400x200?text=Event');
                
                $content .= '
                <div class="col-md-6 col-lg-4 event-item">
                    <div class="event-card">
                        <div class="event-status-badge">Planifié</div>
                        <div class="event-image" style="background-image: url(\'' . $image . '\')"></div>
                        <div class="event-body">
                            <h3 class="event-title">
                                <a href="index.php?page=detail_evenement&slug=' . htmlspecialchars($event['slug']) . '">
                                    ' . htmlspecialchars($event['titre']) . '
                                </a>
                            </h3>
                            <div class="event-meta">
                                <i class="fas fa-calendar"></i>
                                <span>' . $dateDebut . ' - ' . $dateFin . '</span>
                            </div>
                            <div class="event-meta">
                                <i class="fas fa-map-marker-alt"></i>
                                <span>' . $lieu . '</span>
                            </div>
                            <p class="event-excerpt">' . $description . '</p>
                            ' . (!empty($event['sponsor_nom']) ? '<div class="event-meta"><i class="fas fa-handshake"></i><span>Sponsor: ' . htmlspecialchars($event['sponsor_nom']) . '</span></div>' : '') . '
                            <div class="event-footer">
                                <div class="event-price">' . $prixText . '</div>
                                <a href="index.php?page=detail_evenement&slug=' . htmlspecialchars($event['slug']) . '" class="btn-register">
                                    <i class="fas fa-info-circle"></i> Plus de détails
                                </a>
                            </div>
                        </div>
                    </div>
                </div>';
            }
        }

        $content .= '
            </div>
        </div>

        <footer>
            <div class="container">
                <p>&copy; 2026 Valorys - Tous droits réservés</p>
            </div>
        </footer>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        ';

        $this->renderPublicView('Événements', $content);
    }
    
    public function detailEvenement($id = null): void {
        require_once __DIR__ . '/../models/Event.php';
        $eventModel = new Event();
        
        // Get slug from URL parameter
        $slug = isset($_GET['slug']) ? preg_replace('/[^a-z0-9-]/', '', trim($_GET['slug'])) : null;
        
        // Try to fetch event by slug first, then by ID
        $event = null;
        if ($slug) {
            $event = $eventModel->getBySlug($slug);
        } elseif ($id) {
            $event = $eventModel->getById($id);
        }
        
        if (!$event) {
            $this->page404();
            return;
        }
        
        $isLoggedIn = isset($_SESSION['user_id']);
        $userId = $_SESSION['user_id'] ?? null;
        
        // Format dates
        $dateDebut = date('d/m/Y H:i', strtotime($event['date_debut']));
        $dateFin = date('d/m/Y H:i', strtotime($event['date_fin']));
        $dateDebutFull = date('d F Y', strtotime($event['date_debut']));
        
        // Format pricing
        $prix = $event['prix'] ?? 0;
        $prixText = $prix > 0 ? number_format($prix, 2, ',', ' ') . ' DT' : 'GRATUIT';
        
        // Get sponsor info
        $sponsor = $event['sponsor_nom'] ?? null;
        
        // Participants
        $participants = $event['nb_participants'] ?? 0;
        $capacite = $event['capacite_max'] ?? 0;
        $placesDisponibles = $capacite > 0 ? max(0, $capacite - $participants) : '∞';
        
        $content = '
        <style>
            body { background: #f5f7fb; font-family: \'Segoe UI\', sans-serif; }
            .navbar { background: #1a2035; }
            .navbar-brand, .nav-link { color: white !important; }
            .event-hero { background: linear-gradient(135deg, #2A7FAA 0%, #4CAF50 100%); color: white; padding: 40px 0; margin-bottom: 30px; }
            .event-hero h1 { font-size: 2.5rem; margin-bottom: 10px; }
            .event-image { width: 100%; max-height: 400px; object-fit: cover; border-radius: 12px; margin-bottom: 30px; }
            .event-header-section { background: white; border-radius: 12px; padding: 30px; margin-bottom: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
            .event-meta-row { display: flex; gap: 30px; flex-wrap: wrap; margin-bottom: 20px; }
            .event-meta-item { display: flex; align-items: center; gap: 10px; }
            .event-meta-item i { color: #2A7FAA; font-size: 20px; width: 30px; text-align: center; }
            .event-meta-item span { font-size: 16px; color: #333; }
            .event-meta-label { color: #6c757d; font-size: 13px; }
            .event-price-section { background: linear-gradient(135deg, #2A7FAA 0%, #4CAF50 100%); color: white; border-radius: 12px; padding: 25px; text-align: center; }
            .event-price { font-size: 36px; font-weight: bold; margin-bottom: 10px; }
            .register-btn { background: white; color: #2A7FAA; border: none; border-radius: 25px; padding: 12px 35px; font-size: 16px; font-weight: 600; cursor: pointer; transition: all 0.3s; margin-top: 15px; }
            .register-btn:hover { transform: scale(1.05); box-shadow: 0 5px 15px rgba(0,0,0,0.2); }
            .event-description { background: white; border-radius: 12px; padding: 30px; margin-bottom: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); line-height: 1.8; color: #333; }
            .event-description h3 { color: #2A7FAA; margin-bottom: 15px; font-size: 1.5rem; }
            .info-card { background: #f8f9fa; border-left: 4px solid #2A7FAA; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
            .info-card h4 { color: #2A7FAA; margin-bottom: 10px; }
            .back-btn { display: inline-block; margin-bottom: 20px; }
            .back-btn a { color: #2A7FAA; text-decoration: none; font-weight: 600; transition: all 0.3s; }
            .back-btn a:hover { color: #4CAF50; }
            footer { background: #1a2035; color: white; text-align: center; padding: 30px; margin-top: 50px; }
        </style>

        <!-- NAVBAR -->
        <nav class="navbar navbar-expand-lg navbar-dark">
            <div class="container">
                <a class="navbar-brand" href="index.php?page=accueil">
                    <i class="fas fa-stethoscope me-2"></i>Valorys
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item"><a class="nav-link" href="index.php?page=accueil">Accueil</a></li>
                        <li class="nav-item"><a class="nav-link" href="index.php?page=medecins">Médecins</a></li>
                        <li class="nav-item"><a class="nav-link" href="index.php?page=blog_public">Blog</a></li>
                        <li class="nav-item"><a class="nav-link active" href="index.php?page=evenements">Événements</a></li>
                        <li class="nav-item"><a class="nav-link" href="index.php?page=sponsors">Sponsors</a></li>
                        <li class="nav-item"><a class="nav-link" href="index.php?page=contact">Contact</a></li>
                        ' . ($isLoggedIn ? '
                        <li class="nav-item"><a class="nav-link" href="index.php?page=logout">Déconnexion</a></li>
                        ' : '
                        <li class="nav-item"><a class="nav-link" href="index.php?page=login">Connexion</a></li>
                        <li class="nav-item"><a class="nav-link" href="index.php?page=register">Inscription</a></li>
                        ') . '
                    </ul>
                </div>
            </div>
        </nav>

        <!-- HERO SECTION -->
        <div class="event-hero">
            <div class="container">
                <div class="back-btn">
                    <a href="index.php?page=evenements"><i class="fas fa-arrow-left me-2"></i>Retour aux événements</a>
                </div>
                <h1><i class="fas fa-calendar-alt me-3"></i>' . htmlspecialchars($event['titre']) . '</h1>
                <p class="lead">' . date('d F Y', strtotime($event['date_debut'])) . '</p>
            </div>
        </div>

        <!-- CONTENU PRINCIPAL -->
        <div class="container mb-5">
            <div class="row">
                <div class="col-lg-8">
                    ' . (!empty($event['image']) ? '<img src="' . htmlspecialchars($event['image']) . '" alt="' . htmlspecialchars($event['titre']) . '" class="event-image">' : '') . '

                    <!-- Description -->
                    <div class="event-description">
                        <h3><i class="fas fa-file-alt me-2"></i>Description</h3>
                        ' . nl2br(htmlspecialchars($event['description'] ?? 'Aucune description disponible.')) . '
                    </div>

                    <!-- Détails -->
                    <div class="event-header-section">
                        <h3 class="mb-4"><i class="fas fa-info-circle me-2"></i>Détails de l\'événement</h3>
                        
                        <div class="event-meta-row">
                            <div class="event-meta-item">
                                <i class="fas fa-calendar"></i>
                                <div>
                                    <div class="event-meta-label">Date de début</div>
                                    <span>' . $dateDebut . '</span>
                                </div>
                            </div>
                            <div class="event-meta-item">
                                <i class="fas fa-calendar-check"></i>
                                <div>
                                    <div class="event-meta-label">Date de fin</div>
                                    <span>' . $dateFin . '</span>
                                </div>
                            </div>
                        </div>

                        <div class="event-meta-row">
                            <div class="event-meta-item">
                                <i class="fas fa-map-marker-alt"></i>
                                <div>
                                    <div class="event-meta-label">Lieu</div>
                                    <span>' . htmlspecialchars($event['lieu'] ?? 'À déterminer') . '</span>
                                </div>
                            </div>
                            <div class="event-meta-item">
                                <i class="fas fa-location-dot"></i>
                                <div>
                                    <div class="event-meta-label">Adresse</div>
                                    <span>' . htmlspecialchars($event['adresse'] ?? 'Non renseignée') . '</span>
                                </div>
                            </div>
                        </div>

                        ' . ($sponsor ? '<div class="event-meta-row">
                            <div class="event-meta-item">
                                <i class="fas fa-handshake"></i>
                                <div>
                                    <div class="event-meta-label">Sponsor</div>
                                    <span>' . htmlspecialchars($sponsor) . '</span>
                                </div>
                            </div>
                        </div>' : '') . '
                    </div>

                    <!-- Places disponibles -->
                    <div class="info-card">
                        <h4><i class="fas fa-chair me-2"></i>Capacité & Inscription</h4>
                        <p><strong>Capacité maximale:</strong> ' . ($capacite > 0 ? $capacite . ' personnes' : 'Illimitée') . '</p>
                        <p><strong>Participants actuels:</strong> ' . $participants . '</p>
                        <p><strong>Places disponibles:</strong> ' . ($placesDisponibles === '∞' ? 'Illimitées' : $placesDisponibles) . '</p>
                    </div>
                </div>

                <!-- SIDEBAR -->
                <div class="col-lg-4">
                    <!-- Prix & Inscription -->
                    <div class="event-price-section">
                        <div>Tarif</div>
                        <div class="event-price">' . $prixText . '</div>
                        <button class="register-btn">
                            <i class="fas fa-check-circle me-2"></i>' . ($isLoggedIn ? 'S\'inscrire' : 'Se connecter pour s\'inscrire') . '
                        </button>
                    </div>

                    <!-- Informations supplémentaires -->
                    <div style="background: white; border-radius: 12px; padding: 20px; margin-top: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.08);">
                        <h4 class="mb-3"><i class="fas fa-star me-2"></i>À propos de cet événement</h4>
                        
                        <div class="info-card" style="border-left-color: #4CAF50;">
                            <p><strong>Status:</strong> <span style="background: #4CAF50; color: white; padding: 2px 8px; border-radius: 12px; font-size: 12px;">' . ucfirst($event['status'] ?? 'à venir') . '</span></p>
                        </div>

                        <div class="info-card" style="border-left-color: #2A7FAA;">
                            <p><small><i class="fas fa-question-circle me-2"></i>Vous avez une question sur cet événement? <a href="index.php?page=contact" style="color: #2A7FAA; font-weight: 600;">Contactez-nous</a></small></p>
                        </div>
                    </div>

                    <!-- Partage social -->
                    <div style="background: white; border-radius: 12px; padding: 20px; margin-top: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.08);">
                        <h4 class="mb-3"><i class="fas fa-share-alt me-2"></i>Partager</h4>
                        <div style="display: flex; gap: 10px;">
                            <a href="https://www.facebook.com/sharer/sharer.php?u=' . urlencode($_SERVER['REQUEST_URI']) . '" target="_blank" class="btn btn-sm btn-primary" style="flex: 1; text-align: center;">
                                <i class="fab fa-facebook"></i> Facebook
                            </a>
                            <a href="https://twitter.com/intent/tweet?url=' . urlencode($_SERVER['REQUEST_URI']) . '&text=' . urlencode($event['titre']) . '" target="_blank" class="btn btn-sm btn-info" style="flex: 1; text-align: center;">
                                <i class="fab fa-twitter"></i> Twitter
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <footer>
            <div class="container">
                <p>&copy; 2026 Valorys - Tous droits réservés</p>
            </div>
        </footer>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script>
            const eventId = ' . (int)$event['id'] . ';
            const isLoggedIn = ' . ($isLoggedIn ? 'true' : 'false') . ';
            
            document.querySelector(".register-btn").addEventListener("click", function() {
                if (isLoggedIn) {
                    registerForEvent(eventId);
                } else {
                    window.location.href = "index.php?page=login";
                }
            });
            
            function registerForEvent(eventId) {
                const btn = document.querySelector(".register-btn");
                btn.disabled = true;
                const originalText = btn.innerHTML;
                btn.innerHTML = \'<i class="fas fa-spinner fa-spin me-2"></i>Inscription en cours...\';
                
                fetch("index.php?page=event_register", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: "event_id=" + eventId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        btn.innerHTML = \'<i class="fas fa-check-circle me-2"></i>Inscription confirmée!\';
                        btn.style.background = "#4CAF50";
                        setTimeout(() => location.reload(), 2000);
                    } else {
                        alert("Erreur: " + (data.message || "Impossible de s\'inscrire"));
                        btn.disabled = false;
                        btn.innerHTML = originalText;
                    }
                })
                .catch(err => {
                    console.error("Erreur:", err);
                    alert("Erreur de connexion. Veuillez réessayer.");
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                });
            }
        </script>
        ';

        $this->renderPublicView(htmlspecialchars($event['titre']), $content);
    }
    
    public function registerEventAction(): void {
        header('Content-Type: application/json');
        
        // Check if user is logged in
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Veuillez vous connecter.']);
            exit;
        }
        
        $eventId = isset($_POST['event_id']) ? (int)$_POST['event_id'] : null;
        $userId = $_SESSION['user_id'];
        
        if (!$eventId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID d\'événement invalide.']);
            exit;
        }
        
        require_once __DIR__ . '/../models/Event.php';
        require_once __DIR__ . '/../models/Participation.php';
        
        $eventModel = new Event();
        $participationModel = new Participation();
        
        // Check if event exists
        $event = $eventModel->getById($eventId);
        if (!$event) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Événement non trouvé.']);
            exit;
        }
        
        // Check if user is already registered
        $alreadyRegistered = $participationModel->checkUserEvent($userId, $eventId);
        if ($alreadyRegistered) {
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => 'Vous êtes déjà inscrit à cet événement.']);
            exit;
        }
        
        // Register user
        $result = $participationModel->create([
            'event_id' => $eventId,
            'user_id' => $userId,
            'statut' => 'inscrit'
        ]);
        
        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'Inscription confirmée!',
                'participation_id' => $result
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'inscription.']);
        }
        exit;
    }
    
    public function listSponsors(): void {
        require_once __DIR__ . '/../models/Sponsor.php';
        $sponsorModel = new Sponsor();
        $sponsors = $sponsorModel->getAll(0, 100, 'actif');
        
        // Map niveau enum values to French display names
        $levelMap = [
            'platinium' => 'Platine',
            'gold' => 'Or',
            'silver' => 'Argent',
            'bronze' => 'Bronze'
        ];
        
        // Group by niveau (level) using mapped names
        $sponsorsByLevel = [];
        foreach ($sponsors as $sponsor) {
            $levelKey = $levelMap[$sponsor['niveau']] ?? 'autre';
            if (!isset($sponsorsByLevel[$levelKey])) {
                $sponsorsByLevel[$levelKey] = [];
            }
            $sponsorsByLevel[$levelKey][] = $sponsor;
        }
        
        $content = '
        <style>
            body { background: #f5f7fb; font-family: \'Segoe UI\', sans-serif; }
            .navbar { background: #1a2035; }
            .navbar-brand, .nav-link { color: white !important; }
            .sponsors-header {
                background: linear-gradient(135deg, #2A7FAA 0%, #4CAF50 100%);
                color: white; padding: 60px 0; text-align: center; margin-bottom: 40px;
            }
            .sponsors-header h1 { font-size: 2.5rem; margin-bottom: 10px; }
            .sponsors-header .lead { font-size: 1.1rem; opacity: 0.9; }
            .sponsor-card {
                background: white; border-radius: 12px; padding: 30px 20px;
                margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.08);
                transition: transform 0.3s, box-shadow 0.3s;
                display: flex; flex-direction: column; align-items: center; text-align: center;
            }
            .sponsor-card:hover { transform: translateY(-5px); box-shadow: 0 8px 25px rgba(0,0,0,0.15); }
            .sponsor-icon {
                width: 80px; height: 80px; background: linear-gradient(135deg, #2A7FAA, #4CAF50);
                border-radius: 12px; display: flex; align-items: center; justify-content: center;
                color: white; font-size: 40px; flex-shrink: 0; margin-bottom: 15px;
            }
            .sponsor-info { width: 100%; }
            .sponsor-name { font-size: 18px; font-weight: 700; color: #333; margin-bottom: 10px; }
            .sponsor-level {
                display: inline-block; padding: 5px 14px; border-radius: 20px;
                font-size: 12px; font-weight: bold; margin-bottom: 12px;
            }
            .level-platine { background: #e0d4f7; color: #6f4e8f; }
            .level-or { background: #ffd700; color: #8b7500; }
            .level-argent { background: #e8e8e8; color: #5a5a5a; }
            .level-bronze { background: #f0a875; color: #7a4d2f; }
            .sponsor-contact { display: flex; flex-direction: column; gap: 8px; font-size: 13px; }
            .sponsor-contact a { color: #2A7FAA; text-decoration: none; transition: color 0.3s; display: flex; align-items: center; justify-content: center; gap: 8px; }
            .sponsor-contact a:hover { color: #4CAF50; }
            .section-title {
                font-size: 1.8rem; font-weight: 700; color: #333;
                margin-top: 40px; margin-bottom: 30px; padding-bottom: 15px;
                border-bottom: 3px solid #2A7FAA;
            }
            .row { margin-bottom: 30px; }
            footer { background: #1a2035; color: white; text-align: center; padding: 30px; margin-top: 50px; }
        </style>

        <!-- HEADER -->
        <div class="sponsors-header">
            <div class="container">
                <h1><i class="fas fa-handshake me-3"></i>Nos Sponsors</h1>
                <p class="lead">Partenaires qui soutiennent les événements médicaux</p>
            </div>
        </div>

        <!-- SPONSORS LIST -->
        <div class="container mb-5">';

        // Platinum sponsors
        if (!empty($sponsorsByLevel['Platine'])) {
            $content .= '<div class="section-title"><i class="fas fa-crown me-2" style="color: #e0d4f7;"></i>Sponsors Platine</div>
            <div class="row">';
            foreach ($sponsorsByLevel['Platine'] as $sponsor) {
                $content .= $this->getSponsorCard($sponsor, 'level-platine');
            }
            $content .= '</div>';
        }

        // Gold sponsors
        if (!empty($sponsorsByLevel['Or'])) {
            $content .= '<div class="section-title"><i class="fas fa-medal me-2" style="color: #ffd700;"></i>Sponsors Or</div>
            <div class="row">';
            foreach ($sponsorsByLevel['Or'] as $sponsor) {
                $content .= $this->getSponsorCard($sponsor, 'level-or');
            }
            $content .= '</div>';
        }

        // Silver sponsors
        if (!empty($sponsorsByLevel['Argent'])) {
            $content .= '<div class="section-title"><i class="fas fa-medal me-2" style="color: #a9a9a9;"></i>Sponsors Argent</div>
            <div class="row">';
            foreach ($sponsorsByLevel['Argent'] as $sponsor) {
                $content .= $this->getSponsorCard($sponsor, 'level-argent');
            }
            $content .= '</div>';
        }

        // Bronze sponsors
        if (!empty($sponsorsByLevel['Bronze'])) {
            $content .= '<div class="section-title"><i class="fas fa-medal me-2" style="color: #cd7f32;"></i>Sponsors Bronze</div>
            <div class="row">';
            foreach ($sponsorsByLevel['Bronze'] as $sponsor) {
                $content .= $this->getSponsorCard($sponsor, 'level-bronze');
            }
            $content .= '</div>';
        }

        // Other sponsors
        if (!empty($sponsorsByLevel['autre']) || (count($sponsorsByLevel) === 1 && !empty($sponsorsByLevel['autre']))) {
            $content .= '<div class="section-title"><i class="fas fa-building me-2"></i>Autres Partenaires</div>
            <div class="row">';
            $otherSponsors = $sponsorsByLevel['autre'] ?? [];
            foreach ($otherSponsors as $sponsor) {
                $content .= $this->getSponsorCard($sponsor, 'level-autre');
            }
            $content .= '</div>';
        }

        if (empty($sponsors)) {
            $content .= '<div class="alert alert-info text-center" style="padding: 40px;">
                <i class="fas fa-info-circle fa-2x mb-3 d-block"></i>
                <p>Aucun sponsor disponible pour le moment.</p>
            </div>';
        }

        $content .= '
        </div>

        <footer>
            <div class="container">
                <p>&copy; 2026 Valorys - Tous droits réservés</p>
            </div>
        </footer>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        ';

        $this->renderPublicView('Nos Sponsors', $content);
    }
    
    private function getSponsorCard($sponsor, $levelClass): string {
        $logo = $sponsor['logo'] ?? 'https://via.placeholder.com/80?text=' . htmlspecialchars(substr($sponsor['nom'], 0, 2));
        $email = $sponsor['email'] ?? null;
        $phone = $sponsor['telephone'] ?? null;
        $website = $sponsor['site_web'] ?? null;
        $niveau = $sponsor['niveau'] ?? 'Partenaire';
        
        return '
        <div class="col-md-6 col-lg-4">
            <div class="sponsor-card">
                <div class="sponsor-icon" style="background: url(\'' . htmlspecialchars($logo) . '\') center/contain no-repeat, linear-gradient(135deg, #2A7FAA, #4CAF50);"><i class="fas fa-building"></i></div>
                <div class="sponsor-info">
                    <div class="sponsor-name">' . htmlspecialchars($sponsor['nom']) . '</div>
                    <div class="sponsor-level ' . $levelClass . '">' . ucfirst($niveau) . '</div>
                    <div class="sponsor-contact">
                        ' . ($email ? '<a href="mailto:' . htmlspecialchars($email) . '" title="Email"><i class="fas fa-envelope"></i>' . htmlspecialchars($email) . '</a>' : '') . '
                        ' . ($phone ? '<a href="tel:' . htmlspecialchars($phone) . '" title="Téléphone"><i class="fas fa-phone"></i>' . htmlspecialchars($phone) . '</a>' : '') . '
                        ' . ($website ? '<a href="' . htmlspecialchars($website) . '" target="_blank" title="Site web"><i class="fas fa-link"></i></a>' : '') . '
                    </div>
                </div>
            </div>
        </div>';
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
                if ($rdv['statut'] !== 'annulé' && $rdv['statut'] !== 'terminé') {
                    $html .= '<a href="index.php?page=annuler_rendez_vous&id=' . $rdv['id'] . '" class="btn-action btn-annuler" onclick="return confirm(\'Annuler ce rendez-vous ?\')"><i class="fas fa-times me-1"></i>Annuler</a>';
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
        if (isset($_SESSION['success'])) {
            $html .= '<div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle"></i> ' . htmlspecialchars($_SESSION['success']) . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
            unset($_SESSION['success']);
        }
        if (isset($_SESSION['error'])) {
            $html .= '<div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-circle"></i> ' . htmlspecialchars($_SESSION['error']) . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
            unset($_SESSION['error']);
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
            .navbar-custom { background:linear-gradient(135deg,var(--primary) 0%,var(--secondary) 100%);box-shadow:var(--shadow);padding:0.8rem 2rem; }
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
            <div class="col-md-3"><div class="card h-100 text-center p-3"><div class="card-body"><i class="fas fa-user-circle fa-3x text-secondary mb-3"></i><h5>Mon profil</h5><a href="index.php?page=mon_profil" class="btn btn-secondary btn-sm">Mon profil</a></div></div></div>';
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
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-clock me-2"></i>Mes disponibilités</h2>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addDispoModal">
                <i class="fas fa-plus me-2"></i>Ajouter un créneau
            </button>
        </div>';
        
        if (empty($dispos)) {
            $html .= '<div class="alert alert-info text-center py-5">Aucune disponibilité définie.</div>';
        } else {
            $html .= '<div class="row">';
            foreach ($dispos as $dispo) {
                $html .= '
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <h5 class="card-title text-primary">' . htmlspecialchars($dispo['jour_semaine']) . '</h5>
                                <span class="badge ' . ($dispo['actif'] ? 'bg-success' : 'bg-secondary') . '">
                                    ' . ($dispo['actif'] ? 'Actif' : 'Inactif') . '
                                </span>
                            </div>
                            <p class="card-text mt-3">
                                <i class="fas fa-clock me-2"></i> ' . date('H:i', strtotime($dispo['heure_debut'])) . ' - ' . date('H:i', strtotime($dispo['heure_fin'])) . '
                            </p>
                            <div class="mt-3">
                                <a href="index.php?page=medecin_disponibilites&action=toggle&id=' . $dispo['id'] . '" class="btn btn-sm btn-warning">
                                    <i class="fas fa-exchange-alt me-1"></i>' . ($dispo['actif'] ? 'Désactiver' : 'Activer') . '
                                </a>
                                <a href="index.php?page=medecin_disponibilites&action=delete&id=' . $dispo['id'] . '" class="btn btn-sm btn-danger" onclick="return confirm(\'Supprimer ce créneau ?\')">
                                    <i class="fas fa-trash me-1"></i>Supprimer
                                </a>
                            </div>
                        </div>
                    </div>
                </div>';
            }
            $html .= '</div>';
        }
        
        // Modal pour ajouter une disponibilité
        $html .= '
        <div class="modal fade" id="addDispoModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Ajouter une disponibilité</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST" action="index.php?page=medecin_disponibilites&action=store">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Jour <span class="text-danger">*</span></label>
                                <select name="jour_semaine" class="form-select" required>
                                    <option value="">Sélectionner un jour</option>
                                    <option value="Lundi">Lundi</option>
                                    <option value="Mardi">Mardi</option>
                                    <option value="Mercredi">Mercredi</option>
                                    <option value="Jeudi">Jeudi</option>
                                    <option value="Vendredi">Vendredi</option>
                                    <option value="Samedi">Samedi</option>
                                    <option value="Dimanche">Dimanche</option>
                                </select>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Heure début <span class="text-danger">*</span></label>
                                    <input type="time" name="heure_debut" class="form-control" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Heure fin <span class="text-danger">*</span></label>
                                    <input type="time" name="heure_fin" class="form-control" required>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-primary">Ajouter</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>';
        
        return $html;
    }



    /**
 * Afficher la page d'enregistrement facial
 */

    
} // FIN DE LA CLASSE FrontController
