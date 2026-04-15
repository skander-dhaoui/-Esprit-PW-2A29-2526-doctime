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
    
    /**
     * Liste des articles (publique)
     */
    public function listeArticles(): void {
        $this->renderTemporaryView('Articles du blog', '<p>Page des articles en construction...</p>');
    }
    
    /**
     * Détail d'un article (publique)
     */
    public function detailArticle($id): void {
        $this->renderTemporaryView('Détail de l\'article', '<p>Article ID: ' . htmlspecialchars($id) . '</p>');
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
     * Modifier profil (protégé) - Design amélioré
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
     * Mon profil (protégé) - Design amélioré
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
                            <li class="nav-item"><a class="nav-link" href="index.php?page=articles">Blog</a></li>
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
        // ── Contenu personnalisé selon le rôle ──
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

        // Carte commune : profil
        $roleContent .= '
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

    // ── Page publique (non connecté) ──────────────────────────
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
                    <i class="fas fa-exclamation-circle fa-3x text-warning mb-3"></i>
                    <h5>Réclamations</h5>
                    <p class="small text-muted">Signalez un problème</p>
                    <a href="index.php?page=login" class="btn btn-primary btn-sm">Se connecter</a>
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