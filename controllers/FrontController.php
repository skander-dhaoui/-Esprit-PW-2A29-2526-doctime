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

    private static bool $docTimeSchemaEnsured = false;

    /** Colonnes optionnelles (UI DocTime) : categorie, sponsor_id sur events ; email, telephone sur sponsors */
    private function ensureDocTimeSchema(\PDO $pdo): void {
        if (self::$docTimeSchemaEnsured) {
            return;
        }
        self::$docTimeSchemaEnsured = true;
        $try = static function (string $sql) use ($pdo): void {
            try {
                $pdo->exec($sql);
            } catch (Throwable $e) {
                // colonne ou index déjà présent
            }
        };
        $try('ALTER TABLE events ADD COLUMN categorie VARCHAR(100) NULL AFTER lieu');
        $try('ALTER TABLE events ADD COLUMN sponsor_id INT NULL AFTER categorie');
        $try('ALTER TABLE events ADD INDEX idx_events_sponsor (sponsor_id)');
        $try('ALTER TABLE sponsors ADD COLUMN email VARCHAR(255) NULL AFTER nom');
        $try('ALTER TABLE sponsors ADD COLUMN telephone VARCHAR(50) NULL AFTER email');
        $this->repairEventsStatusEnumIfNeeded($pdo);
    }

    /**
     * Bases importées avec mauvais encodage : ENUM peut contenir '? venir' au lieu de 'à venir',
     * ou des valeurs vides. Normalisation en VARCHAR puis ré-ENUM UTF-8 via PDO (UTF-8 réel).
     */
    private function repairEventsStatusEnumIfNeeded(\PDO $pdo): void {
        try {
            $pdo->exec('ALTER TABLE events MODIFY COLUMN status VARCHAR(32) NOT NULL');
        } catch (Throwable $e) {
            // déjà VARCHAR ou indisponible
        }
        $avenir = 'à venir';
        $term   = 'terminé';
        $annul  = 'annulé';
        try {
            $run = static function (\PDO $pdo, string $sql, string $val): void {
                $st = $pdo->prepare($sql);
                $st->execute([$val]);
            };
            $run($pdo, 'UPDATE events SET status = ? WHERE status IN (\'termin?\', \'terminé\')', $term);
            $run($pdo, 'UPDATE events SET status = ? WHERE status IN (\'annul?\', \'annulé\')', $annul);
            $run($pdo, 'UPDATE events SET status = ? WHERE status IN (\'? venir\', \'?\', \'\') OR status IS NULL OR TRIM(COALESCE(status, \'\')) = \'\'', $avenir);
            $run($pdo, 'UPDATE events SET status = ? WHERE status NOT IN (\'à venir\',\'en_cours\',\'terminé\',\'annulé\')', $avenir);
        } catch (Throwable $e) {
            error_log('repairEventsStatusEnumIfNeeded (updates): ' . $e->getMessage());
        }
        try {
            $pdo->exec('ALTER TABLE events MODIFY COLUMN status ENUM(\'à venir\',\'en_cours\',\'terminé\',\'annulé\') NOT NULL DEFAULT \'à venir\'');
        } catch (Throwable $e) {
            error_log('repairEventsStatusEnumIfNeeded (enum): ' . $e->getMessage());
        }
    }

    private function guessEventCategoryFromTitle(string $titre): string {
        $t = mb_strtolower($titre, 'UTF-8');
        $map = [
            'dermat' => 'Dermatologie',
            'cardio'  => 'Cardiologie',
            'pédiat'  => 'Pédiatrie',
            'pediat'  => 'Pédiatrie',
            'nutrition' => 'Nutrition',
            'diabète' => 'Endocrinologie',
            'diabete' => 'Endocrinologie',
            'sommeil' => 'Médecine du sommeil',
            'coeur'   => 'Cardiologie',
        ];
        foreach ($map as $needle => $label) {
            if ($needle !== '' && str_contains($t, $needle)) {
                return $label;
            }
        }
        return 'Général';
    }

    private function eventCategoryForRow(array $event): string {
        $c = trim((string)($event['categorie'] ?? ''));
        if ($c !== '') {
            return $c;
        }
        return $this->guessEventCategoryFromTitle((string)($event['titre'] ?? ''));
    }

    /** Libellés + classes badge pour l’affichage public */
    private function eventStatusPublicBadge(string $status): array {
        return match ($status) {
            'en_cours' => ['En cours', 'dt-badge-encours'],
            'terminé' => ['Terminé', 'dt-badge-term'],
            'annulé' => ['Annulé', 'dt-badge-annul'],
            default => ['Planifié', 'dt-badge-planif'],
        };
    }

    private function truncatePlain(?string $text, int $maxLen): string {
        $t = trim(strip_tags((string)$text));
        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            if (mb_strlen($t, 'UTF-8') <= $maxLen) {
                return $t;
            }
            return rtrim(mb_substr($t, 0, $maxLen - 1, 'UTF-8')) . '…';
        }
        return strlen($t) <= $maxLen ? $t : substr($t, 0, $maxLen - 1) . '…';
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
            $articles = $articleModel->getAll();
            $isLoggedIn = isset($_SESSION['user_id']);
            $userId = $_SESSION['user_id'] ?? null;
            $userRole = $_SESSION['user_role'] ?? '';
            $isAdmin = ($userRole === 'admin');

            if ($isAdmin) {
                $totalArticles = count($articles);
                $totalVues = (int) array_sum(array_map('intval', array_column($articles, 'vues')));
                $totalComments = (int) array_sum(array_map('intval', array_column($articles, 'nb_replies')));
                $statsHtml = '
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="stat-card" style="background:linear-gradient(135deg,#1a7fa8,#1db88e)">
                            <p>Total articles</p>
                            <h3>' . $totalArticles . '</h3>
                            <i class="fas fa-newspaper stat-icon"></i>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card" style="background:linear-gradient(135deg,#22c55e,#15803d)">
                            <p>Total vues</p>
                            <h3>' . $totalVues . '</h3>
                            <i class="fas fa-eye stat-icon"></i>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card" style="background:linear-gradient(135deg,#0ea5e9,#0891b2)">
                            <p>Total commentaires</p>
                            <h3>' . $totalComments . '</h3>
                            <i class="fas fa-comments stat-icon"></i>
                        </div>
                    </div>
                </div>';
                $cardHead = '
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3 d-flex flex-wrap justify-content-between align-items-center gap-2 border-bottom">
                        <h6 class="mb-0 fw-bold"><i class="fas fa-list me-2" style="color:#1a7fa8"></i>Liste des articles</h6>
                        <a href="index.php?page=articles_admin&action=create" class="btn btn-primary btn-sm rounded-pill px-3">
                            <i class="fas fa-plus me-1"></i>Nouvel article
                        </a>
                    </div>';
                if (empty($articles)) {
                    $content = $cardHead . '
                    <div class="card-body">
                        <div class="alert alert-info border-0 mb-0" style="border-radius:12px;background:#e8f4f8;color:#0c4a6e;">
                            <i class="fas fa-info-circle me-2"></i>Aucun article pour le moment. Créez le premier avec le bouton ci-dessus.
                        </div>
                    </div></div>';
                } else {
                    $content = $cardHead . '
                    <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th style="width:72px">ID</th>
                                    <th>Titre</th>
                                    <th>Auteur</th>
                                    <th>Date</th>
                                    <th class="text-center">Vues</th>
                                    <th class="text-center">Commentaires</th>
                                    <th class="text-end" style="min-width:140px">Actions</th>
                                </tr>
                            </thead>
                            <tbody>';
                    foreach ($articles as $article) {
                        $content .= '
                        <tr>
                            <td class="text-muted small">' . (int) $article['id'] . '</td>
                            <td><strong>' . htmlspecialchars($this->truncateArticleTitleForAdminList((string) ($article['titre'] ?? ''))) . '</strong></td>
                            <td>' . htmlspecialchars($article['auteur_name'] ?? 'Valorys') . '</td>
                            <td class="text-nowrap small">' . date('d/m/Y H:i', strtotime($article['created_at'])) . '</td>
                            <td class="text-center"><span class="badge rounded-pill" style="background:#e0f2fe;color:#0369a1;">' . (int) ($article['vues'] ?? 0) . '</span></td>
                            <td class="text-center"><span class="badge rounded-pill bg-light text-dark border">' . (int) ($article['nb_replies'] ?? 0) . '</span></td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="index.php?page=detail_article_public&id=' . (int) $article['id'] . '" class="btn btn-outline-primary" title="Voir"><i class="fas fa-eye"></i></a>
                                    <a href="index.php?page=articles_admin&action=edit&id=' . (int) $article['id'] . '" class="btn btn-outline-warning" title="Modifier"><i class="fas fa-edit"></i></a>
                                    <button type="button" class="btn btn-outline-danger" title="Supprimer" onclick="confirmDeleteArticle(' . (int) $article['id'] . ', \'' . addslashes((string) ($article['titre'] ?? '')) . '\')"><i class="fas fa-trash"></i></button>
                                </div>
                            </td>
                        </tr>';
                    }
                    $content .= '</tbody></table></div></div></div>';
                }
                $fullContent = $statsHtml . $content . $this->getDeleteScript();
                $this->renderAdminLayout('Gestion des articles', $fullContent, 'articles');
            return;
        }

        require __DIR__ . '/../views/frontoffice/blog_public.php';
    } catch (Exception $e) {
        error_log('Erreur blogList: ' . $e->getMessage());
        $content = '<div class="alert alert-danger">Erreur: ' . htmlspecialchars($e->getMessage()) . '</div>';
        $this->renderPublicView('Blog Valorys', $content);
    }
}

    // =============================================
    // DISPONIBILITÉS — suite du fichier original
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
        $data = [
            'user_id'      => (int)$_SESSION['user_id'],
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

    /** Titre tronqué pour la liste admin (UTF-8 si mbstring disponible). */
    private function truncateArticleTitleForAdminList(string $titre, int $max = 60): string {
        if ($titre === '') {
            return '';
        }
        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            if (mb_strlen($titre, 'UTF-8') > $max) {
                return mb_substr($titre, 0, $max, 'UTF-8') . '…';
            }
            return $titre;
        }
        if (strlen($titre) > $max) {
            return substr($titre, 0, $max) . '…';
        }
        return $titre;
    }

    /**
     * Shell back-office DocTime : même bandeau, sidebar et pied que le reste de l’admin.
     */
    private function renderAdminLayout(string $title, string $content, string $activePage = 'articles'): void {
        $pageTitle = $title;
        require __DIR__ . '/../views/backoffice/layout_header.php';
        echo $this->getFlashMessages();
        echo $content;
        require __DIR__ . '/../views/backoffice/layout_footer.php';
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
        $isLoggedIn = isset($_SESSION['user_id']);
        $userId     = $_SESSION['user_id'] ?? null;
        $articleModel = new Article();
        $replyModel   = new Reply();
        $articleModel->incrementViews($id);
        $viewerForArt = $isLoggedIn ? (int)$userId : null;
        $article = $articleModel->getById($id, $viewerForArt);
        if (!$article) { $this->page404(); return; }
        $replies    = $replyModel->getByArticle($id);
        $userRole   = $_SESSION['user_role'] ?? '';
        $isAdmin    = ($userRole === 'admin');
        if ($isAdmin) {
            $content = $this->getAdminArticleDetailHTML($article, $replies, $id);
            $this->renderAdminLayout('Détail de l\'article - Administration', $content, 'articles');
            return;
        }
        $isAuthor = ($isLoggedIn && isset($article['auteur_id']) && (int)$userId === (int)$article['auteur_id']);
        $authorDisplay = trim(
            ((string)($article['auteur_prenom'] ?? '')) . ' ' . ((string)($article['auteur_name'] ?? 'Valorys'))
        ) ?: ($article['auteur_name'] ?? 'Valorys');
        $initial = strtoupper(substr($authorDisplay, 0, 1));
        $nbLk = (int)($article['nb_likes'] ?? 0);
        $nbDk = (int)($article['nb_dislikes'] ?? 0);
        $myVote = $article['my_vote'] ?? '';
        $likeActive = $myVote === 'like' ? ' active-like' : '';
        $disActive = $myVote === 'dislike' ? ' active-dis' : '';

        $topActions = $isAuthor ? '
            <div class="post-actions-top">
                <a href="index.php?page=articles_admin&action=edit&id=' . $id . '" class="btn-modifier" style="text-decoration:none;display:inline-flex;align-items:center;gap:6px;">
                    <i class="fas fa-pencil-alt"></i> Modifier
                </a>
                <button type="button" class="btn-supprimer" onclick="confirmDeleteArticle(' . $id . ', \'' . addslashes($article['titre']) . '\')">
                    <i class="fas fa-trash"></i> Supprimer
                </button>
            </div>' : '';

        $articleImage = !empty($article['image'])
            ? '<img src="' . htmlspecialchars($article['image']) . '" style="width:100%;max-height:420px;object-fit:cover;border-radius:12px;margin-bottom:16px;" alt="">'
            : '';

        $articleI18nJson = json_encode([
            'title' => (string)($article['titre'] ?? ''),
            'body'  => (string)($article['contenu'] ?? ''),
        ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        $translateBar = '
        <div class="translate-bar mb-3 pb-3 border-bottom">
            <span class="small fw-bold text-muted me-2">Traduire :</span>
            <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill me-1" data-translate-article="en">English</button>
            <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill me-1" data-translate-article="ar">العربية</button>
            <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill" id="article-translate-original">Original</button>
            <span id="article-translate-status" class="small text-muted ms-2 d-none"></span>
        </div>';

        $content = '
        <style>
            .detail-wrap-inner { max-width:680px;margin:0 auto;padding:0 10px 40px; }
            .back-blog { display:inline-flex;align-items:center;gap:8px;color:#2A7FAA;font-weight:600;margin-bottom:16px;text-decoration:none;font-size:15px; }
            .back-blog:hover { text-decoration:underline; }
            .post-card-d { background:#fff;border-radius:14px;border:1px solid #e4e6eb;box-shadow:0 1px 3px rgba(0,0,0,.08);overflow:hidden;margin-bottom:16px; }
            .post-head-d { display:flex;align-items:flex-start;justify-content:space-between;gap:10px;padding:14px 16px 0; }
            .post-author-block { display:flex;gap:10px;min-width:0; }
            .avatar-circle-d { width:40px;height:40px;border-radius:50%;background:linear-gradient(145deg,#4CAF50,#2e9b4a);color:#fff;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0; }
            .post-author-name { font-weight:700;font-size:15px;color:#1c1e21; }
            .post-meta-line { font-size:13px;color:#65676b;margin-top:2px;display:flex;align-items:center;gap:6px;flex-wrap:wrap; }
            .post-actions-top { display:flex;gap:8px;flex-shrink:0;flex-wrap:wrap;justify-content:flex-end; }
            .btn-modifier { background:#ffc107;color:#1c1e21;border:none;border-radius:999px;padding:6px 14px;font-size:13px;font-weight:700;display:inline-flex;align-items:center;gap:6px; }
            .btn-supprimer { background:#dc3545;color:#fff;border:none;border-radius:999px;padding:6px 14px;font-size:13px;font-weight:700;display:inline-flex;align-items:center;gap:6px; }
            .post-body-d { padding:12px 16px 16px; }
            .post-title-d { font-size:1.35rem;font-weight:800;color:#1c1e21;margin-bottom:12px; }
            .post-text-d { line-height:1.65;color:#1c1e21;font-size:15px;white-space:pre-wrap;word-break:break-word; }
            .post-footer-d { border-top:1px solid #e4e6eb;padding:10px 16px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px; }
            .vote-btn { border:none;background:transparent;display:inline-flex;align-items:center;gap:6px;font-size:14px;color:#65676b;font-weight:600;padding:4px 8px;border-radius:8px;cursor:pointer; }
            .vote-btn:hover { background:#f0f2f5; }
            .vote-btn.active-like { color:#e41e3f; }
            .vote-btn.active-dis { color:#65676b; }
            .comments-card-d { background:#fff;border-radius:14px;border:1px solid #e4e6eb;padding:20px;margin-bottom:16px;box-shadow:0 1px 3px rgba(0,0,0,.08); }
            .comments-card-d h3 { font-size:1.1rem;font-weight:800;margin-bottom:16px;color:#1c1e21;border-bottom:2px solid #2A7FAA;padding-bottom:10px; }
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
            .reply-thread-node { margin-bottom: 10px; }
            .reply-nested { margin-left: 8px; padding-left: 14px; border-left: 3px solid #e4e6eb; margin-top: 8px; }
            .btn-reply-to { background: transparent; border: 1px solid #e4e6eb; border-radius: 999px; padding: 4px 12px; font-size: 12px; color: #65676b; cursor: pointer; margin-top: 8px; }
            .btn-reply-to:hover { background: #f0f2f5; color: #1c1e21; }
            #editReplyModal, #deleteReplyModal { display:none;position:fixed;z-index:1050;left:0;top:0;width:100%;height:100%;background-color:rgba(0,0,0,0.5); }
            .modal-content { background-color:#fff;margin:10% auto;padding:20px;border-radius:12px;width:90%;max-width:500px;position:relative; }
            .close { position:absolute;right:15px;top:10px;font-size:25px;cursor:pointer; }
            .form-group { margin-bottom:15px; }
            .form-group label { display:block;margin-bottom:5px;font-weight:bold; }
            .form-group input,.form-group textarea { width:100%;padding:10px;border:1px solid #ddd;border-radius:6px; }
            .btn-submit { background:linear-gradient(135deg,#2A7FAA,#4CAF50);color:white;border:none;padding:10px 25px;border-radius:25px;cursor:pointer; }
        </style>
        <div class="detail-wrap-inner">
            <a href="index.php?page=blog_public" class="back-blog"><i class="fas fa-arrow-left"></i> Retour au blog</a>
            <div class="post-card-d">
                <div class="post-head-d">
                    <div class="post-author-block">
                        <div class="avatar-circle-d">' . $initial . '</div>
                        <div>
                            <div class="post-author-name">' . htmlspecialchars($authorDisplay) . '</div>
                            <div class="post-meta-line">
                                <span>' . date('d/m/Y H:i', strtotime($article['created_at'] ?? 'now')) . '</span>
                                <i class="fas fa-globe-americas" title="Public"></i>
                            </div>
                        </div>
                    </div>
                    ' . $topActions . '
                </div>
                <div class="post-body-d">
                    ' . $articleImage . '
                    ' . $translateBar . '
                    <h1 class="post-title-d" id="article-title-display">' . htmlspecialchars($article['titre']) . '</h1>
                    <div class="post-text-d" id="article-body-display">' . nl2br(htmlspecialchars($article['contenu'])) . '</div>
                </div>
                <div class="post-footer-d">
                    <div style="display:flex;gap:14px;align-items:center;">
                        <button type="button" class="vote-btn' . $likeActive . '" id="detVoteLike" data-type="like"><i class="fas fa-heart"></i><span id="detLk">' . $nbLk . '</span></button>
                        <button type="button" class="vote-btn' . $disActive . '" id="detVoteDis" data-type="dislike"><i class="fas fa-thumbs-down"></i><span id="detDk">' . $nbDk . '</span></button>
                    </div>
                    <div style="color:#65676b;font-size:14px;display:flex;gap:16px;">
                        <span><i class="fas fa-eye me-1"></i>' . (int)($article['vues'] ?? 0) . '</span>
                        <span><i class="fas fa-comment me-1"></i>' . count($replies) . '</span>
                    </div>
                </div>
            </div>';
        $content .= '
        <div class="comments-card-d">
            <h3><i class="fas fa-comments me-2" style="color:#2A7FAA"></i>Commentaires (' . count($replies) . ')</h3>
            <div id="replies-container">';
        if (empty($replies)) {
            $content .= '<p style="text-align:center;color:#999;padding:20px;">Aucun commentaire pour le moment. Soyez le premier à réagir !</p>';
        } else {
            $byParent = [];
            foreach ($replies as $reply) {
                $pid = !empty($reply['parent_reply_id']) ? (int)$reply['parent_reply_id'] : 0;
                if (!isset($byParent[$pid])) {
                    $byParent[$pid] = [];
                }
                $byParent[$pid][] = $reply;
            }
            $content .= $this->renderReplyBranchHtml($byParent, 0, $isLoggedIn, $userId);
        }
        $content .= '</div></div>';
        if ($isLoggedIn) {
            $replyErrors = $_SESSION['reply_errors'] ?? [];
            $replyData   = $_SESSION['reply_data']   ?? [];
            $textValue   = htmlspecialchars($replyData['text']  ?? '');
            $emojiValue  = htmlspecialchars($replyData['emoji'] ?? '');
            $parentReplyField = (int)($replyData['parent_reply_id'] ?? 0);
            $parentReplyLabel = '';
            if ($parentReplyField > 0) {
                foreach ($replies as $rr) {
                    if ((int)($rr['id_reply'] ?? 0) === $parentReplyField) {
                        $parentReplyLabel = (string)($rr['auteur'] ?? '');
                        break;
                    }
                }
            }
            $parentHintDisplay = ($parentReplyField > 0) ? 'block' : 'none';
            $parentHintText = $parentReplyField > 0
                ? ('Réponse à ' . htmlspecialchars($parentReplyLabel !== '' ? $parentReplyLabel : ('commentaire #' . $parentReplyField)))
                : '';
            $content .= '
            <div id="comment-form" class="comments-card-d">
                <h4 style="margin-bottom:20px;font-weight:800;"><i class="fas fa-pen me-2" style="color:#2A7FAA"></i>Laisser un commentaire</h4>
                <form method="POST" action="index.php?page=detail_article_public&id=' . $id . '" enctype="multipart/form-data">
                    <input type="hidden" name="parent_reply_id" id="parent_reply_id" value="' . ($parentReplyField > 0 ? (string)(int)$parentReplyField : '') . '">
                    <div id="reply-to-hint" class="mb-3 p-2 rounded border" style="display:' . $parentHintDisplay . ';background:#f0f7ff;border-color:#b8daff!important;">
                        <i class="fas fa-reply me-2 text-primary"></i><span id="reply-to-text">' . $parentHintText . '</span>
                        <button type="button" class="btn btn-sm btn-outline-secondary ms-2" onclick="cancelReplyTo()">Annuler</button>
                    </div>
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
            <div class="comments-card-d" style="background:#e7f3ff;border-color:#b8daff;">
                <i class="fas fa-info-circle me-2"></i>
                <a href="index.php?page=login" style="color:#1976d2;font-weight:600;">Connectez-vous</a> pour laisser un commentaire.
            </div>';
        }
        $content .= '</div>';

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
                <span class="close" onclick="closeDeleteReplyModal()">&times;</span>
                <h3>Confirmer la suppression</h3>
                <p>Êtes-vous sûr de vouloir supprimer ce commentaire ?</p>
                <p style="color:red;font-size:12px;">Cette action est irréversible.</p>
                <div style="display:flex;gap:10px;justify-content:center;margin-top:20px;">
                    <button type="button" onclick="closeDeleteReplyModal()" style="padding:8px 20px;background:#6c757d;color:white;border:none;border-radius:5px;cursor:pointer;">Annuler</button>
                    <button type="button" id="confirmDeleteReplyBtn" style="padding:8px 20px;background:#dc3545;color:white;border:none;border-radius:5px;cursor:pointer;">Supprimer</button>
                </div>
            </div>
        </div>';
        $content .= '
        <script>window.__ARTICLE_I18N__ = ' . $articleI18nJson . ';</script>
        <script>
        (function(){
            var aid = ' . (int)$id . ';
            var logged = ' . ($isLoggedIn ? 'true' : 'false') . ';
            function nl2brEsc(s) {
                var d = document.createElement("div");
                d.textContent = s;
                return d.innerHTML.replace(/\\n/g, "<br>");
            }
            function applyOriginal() {
                var o = window.__ARTICLE_I18N__ || {};
                var t = document.getElementById("article-title-display");
                var b = document.getElementById("article-body-display");
                if (t) t.textContent = o.title || "";
                if (b) b.innerHTML = nl2brEsc(o.body || "");
            }
            function setTranslateStatus(msg, loading) {
                var st = document.getElementById("article-translate-status");
                if (!st) return;
                if (!msg && !loading) { st.classList.add("d-none"); st.textContent = ""; return; }
                st.classList.remove("d-none");
                st.textContent = loading ? "Traduction…" : msg;
            }
            document.addEventListener("click", function (e) {
                var btn = e.target.closest("[data-translate-article]");
                if (!btn) return;
                var lang = btn.getAttribute("data-translate-article");
                var o = window.__ARTICLE_I18N__ || {};
                setTranslateStatus("", true);
                fetch("index.php?page=api_translate", {
                    method: "POST",
                    headers: { "Content-Type": "application/json", "Accept": "application/json" },
                    body: JSON.stringify({ title: o.title || "", body: o.body || "", lang: lang })
                }).then(function (r) { return r.json(); }).then(function (data) {
                    setTranslateStatus("", false);
                    if (!data.success) { alert(data.message || "Traduction indisponible."); return; }
                    var t = document.getElementById("article-title-display");
                    var b = document.getElementById("article-body-display");
                    if (t) t.textContent = data.title || "";
                    if (b) b.innerHTML = nl2brEsc(data.body || "");
                }).catch(function () { setTranslateStatus("", false); alert("Erreur réseau."); });
            });
            var origBtn = document.getElementById("article-translate-original");
            if (origBtn) origBtn.addEventListener("click", function () { setTranslateStatus("", false); applyOriginal(); });
            function upd(r) {
                if (!r.success) return;
                var lk = document.getElementById("detLk");
                var dk = document.getElementById("detDk");
                if (lk) lk.textContent = r.likes;
                if (dk) dk.textContent = r.dislikes;
                var bl = document.getElementById("detVoteLike");
                var bd = document.getElementById("detVoteDis");
                if (bl) bl.classList.toggle("active-like", r.my_vote === "like");
                if (bd) bd.classList.toggle("active-dis", r.my_vote === "dislike");
            }
            function sendVote(type) {
                if (!logged) { alert("Connectez-vous pour voter."); return; }
                fetch("index.php?page=api_article_like", {
                    method: "POST",
                    headers: { "Content-Type": "application/json", "Accept": "application/json" },
                    body: JSON.stringify({ article_id: aid, type: type })
                }).then(function(x){ return x.json(); }).then(upd).catch(function(){});
            }
            var bl = document.getElementById("detVoteLike");
            var bd = document.getElementById("detVoteDis");
            if (bl) bl.addEventListener("click", function(){ sendVote("like"); });
            if (bd) bd.addEventListener("click", function(){ sendVote("dislike"); });
        })();
        </script>';
        $content .= <<<'JS'
        <script>
        var currentDeleteReplyId = null;
        function setReplyTo(id, authorName) {
            var hid = document.getElementById("parent_reply_id");
            var hint = document.getElementById("reply-to-hint");
            var tx = document.getElementById("reply-to-text");
            if (!hid || !hint || !tx) return;
            hid.value = String(id);
            hint.style.display = "block";
            tx.textContent = "Réponse à " + (authorName || "");
            var cf = document.getElementById("comment-form");
            if (cf) cf.scrollIntoView({ behavior: "smooth" });
        }
        document.addEventListener("click", function (ev) {
            var rbtn = ev.target.closest(".btn-reply-to");
            if (!rbtn) return;
            var pid = rbtn.getAttribute("data-parent-id");
            var auth = rbtn.getAttribute("data-parent-author") || "";
            if (pid) setReplyTo(pid, auth);
        });
        function cancelReplyTo() {
            var hid = document.getElementById("parent_reply_id");
            var hint = document.getElementById("reply-to-hint");
            var tx = document.getElementById("reply-to-text");
            if (hid) hid.value = "";
            if (hint) hint.style.display = "none";
            if (tx) tx.textContent = "";
        }
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
        function confirmDeleteReply(replyId) { currentDeleteReplyId = replyId; document.getElementById("deleteReplyModal").style.display = "block"; }
        function closeEditModal()   { document.getElementById("editReplyModal").style.display   = "none"; }
        function closeDeleteReplyModal() { document.getElementById("deleteReplyModal").style.display = "none"; currentDeleteReplyId = null; }
        (function () {
            var cbtn = document.getElementById("confirmDeleteReplyBtn");
            if (cbtn) cbtn.onclick = function () {
                if (currentDeleteReplyId) {
                    fetch("index.php?page=api_reply&id=" + currentDeleteReplyId, { method: "POST", headers: { "Content-Type": "application/json" }, body: JSON.stringify({ _method: "DELETE" }) }).then(r => r.json()).then(data => { if (data.success) location.reload(); else alert("Erreur lors de la suppression"); });
                }
                closeDeleteReplyModal();
            };
        })();
        window.addEventListener("click", function (event) { if (event.target.classList.contains("modal")) event.target.style.display = "none"; });
        </script>
JS;
        $this->renderPublicViewFeed(htmlspecialchars($article['titre']), $content . $this->getDeleteScript());
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

    /**
     * Rend les commentaires en arbre (réponse à un commentaire).
     *
     * @param array<int, list<array<string,mixed>>> $byParent
     */
    private function renderReplyBranchHtml(array $byParent, int $parentKey, bool $isLoggedIn, $userId): string {
        if (empty($byParent[$parentKey])) {
            return '';
        }
        $html = '';
        foreach ($byParent[$parentKey] as $reply) {
            $rid = (int)$reply['id_reply'];
            $canEditReply   = ($isLoggedIn && !empty($reply['user_id']) && (int)$userId === (int)$reply['user_id']);
            $canDeleteReply = $canEditReply;
            $replyContent = '';
            if (!empty($reply['emoji'])) {
                $replyContent .= '<div class="reply-emoji">' . htmlspecialchars((string)$reply['emoji']) . '</div>';
            }
            if (!empty($reply['contenu_text'])) {
                $replyContent .= '<div class="reply-text">' . nl2br(htmlspecialchars((string)$reply['contenu_text'])) . '</div>';
            }
            if (!empty($reply['photo'])) {
                $replyContent .= '<img src="' . htmlspecialchars((string)$reply['photo']) . '" class="reply-photo" alt="Photo">';
            }
            $replyButtons = '';
            if ($canEditReply || $canDeleteReply) {
                $replyButtons = '
                    <div class="reply-actions">
                        ' . ($canEditReply ? '<button onclick="openEditReplyModal(' . $rid . ')" class="btn-edit-reply"><i class="fas fa-edit"></i> Modifier</button>' : '') . '
                        ' . ($canDeleteReply ? '<button onclick="confirmDeleteReply(' . $rid . ')" class="btn-delete-reply"><i class="fas fa-trash"></i> Supprimer</button>' : '') . '
                    </div>';
            }
            $authorAttr = htmlspecialchars((string)($reply['auteur'] ?? 'Anonyme'), ENT_QUOTES, 'UTF-8');
            $replyBtn = $isLoggedIn
                ? '<button type="button" class="btn-reply-to" data-parent-id="' . $rid . '" data-parent-author="' . $authorAttr . '"><i class="fas fa-reply me-1"></i>Répondre</button>'
                : '';
            $html .= '
                <div class="reply-thread-node">
                    <div class="reply-item" id="reply-' . $rid . '">
                        <div class="reply-avatar">' . strtoupper(substr((string)($reply['auteur'] ?? 'A'), 0, 1)) . '</div>
                        <div class="reply-content">
                            <div class="reply-author">' . htmlspecialchars((string)($reply['auteur'] ?? 'Anonyme')) . '</div>
                            <div class="reply-date"><i class="fas fa-clock"></i> ' . date('d/m/Y H:i', strtotime((string)($reply['date_reply'] ?? 'now'))) . '</div>
                            ' . $replyContent . $replyButtons . $replyBtn . '
                        </div>
                    </div>';
            $nested = $this->renderReplyBranchHtml($byParent, $rid, $isLoggedIn, $userId);
            if ($nested !== '') {
                $html .= '<div class="reply-nested">' . $nested . '</div>';
            }
            $html .= '</div>';
        }
        return $html;
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
            $_SESSION['reply_data']   = ['text' => $contenuText, 'emoji' => $emoji, 'parent_reply_id' => $_POST['parent_reply_id'] ?? ''];
            header("Location: index.php?page=detail_article_public&id=$articleId#comment-form");
            exit;
        }
        $parentReplyId = isset($_POST['parent_reply_id']) ? (int)$_POST['parent_reply_id'] : 0;
        if ($parentReplyId > 0) {
            $parentRow = $replyModel->getById($parentReplyId);
            if (!$parentRow || (int)$parentRow['id_article'] !== (int)$articleId) {
                $parentReplyId = 0;
            }
        }
        $newReplyId = $replyModel->createMixte($articleId, $contenuText, $emoji, $imagePath, $auteur, $userId, $parentReplyId > 0 ? $parentReplyId : null);
        if ($newReplyId > 0) {
            $gamification = null;
            if (!empty($userId) && class_exists('GamificationController')) {
                $gRes = GamificationController::grantPoints((int) $userId, 'comment_created', $newReplyId);
                $gamification = GamificationController::formatGrantForClient($gRes);
            }
            if (($_SESSION['user_role'] ?? '') === 'patient' && is_file(__DIR__ . '/../models/AdminNotification.php')) {
                require_once __DIR__ . '/../models/Article.php';
                require_once __DIR__ . '/../models/AdminNotification.php';
                $artRow = (new Article())->getById((int) $articleId);
                $artTitle = is_array($artRow) ? (string) ($artRow['titre'] ?? 'Article') : 'Article';
                $nm = trim($_SESSION['user_name'] ?? '') ?: 'Patient';
                AdminNotification::notifyPatientComment((int) $articleId, $artTitle, $nm);
            }
            $_SESSION['success'] = 'Commentaire ajouté avec succès !';
            if ($gamification !== null && ($gamification['points_added'] > 0 || $gamification['total_points'] > 0 || !empty($gamification['new_rewards']))) {
                $_SESSION['gamification_toast'] = $gamification;
            }
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
                if ($result > 0) {
                    if (!empty($auteur_id) && class_exists('GamificationController')) {
                        GamificationController::grantPoints((int) $auteur_id, 'article_created', (int) $result);
                    }
                    if (($userRole ?? '') === 'patient' && is_file(__DIR__ . '/../models/AdminNotification.php')) {
                        require_once __DIR__ . '/../models/AdminNotification.php';
                        $nm = trim($_SESSION['user_name'] ?? '') ?: 'Patient';
                        AdminNotification::notifyPatientPublishedArticle((int) $result, $titre, $nm);
                    }
                    $_SESSION['success'] = 'Article créé avec succès !';
                    header('Location: index.php?page=blog_public');
                    exit;
                }
                else $errors['general'] = 'Erreur lors de la création de l\'article.';
            }
        }
        if ($isAdmin) {
            $content = $this->getAdminArticleFormHTML('Créer un article', 'admin_article_create', null, $errors, $oldData);
            $this->renderAdminLayout('Créer un article', $content, 'articles');
        } else {
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
    // ÉVÉNEMENTS / SPONSORS / CONTACT / À PROPOS
    // =============================================

    public function listeEvenements(): void {
        $pdo = Database::getInstance()->getConnection();
        $this->ensureDocTimeSchema($pdo);
        $sql = "
            SELECT e.*,
                   (SELECT COUNT(*) FROM participations WHERE event_id = e.id) AS nb_participants,
                   s.nom AS sponsor_nom
            FROM events e
            LEFT JOIN sponsors s ON s.id = e.sponsor_id
            WHERE e.date_debut >= NOW()
            AND (
                e.status IN ('à venir', 'en_cours', '? venir')
                OR e.status IS NULL
                OR TRIM(COALESCE(CAST(e.status AS CHAR), '')) = ''
            )
            ORDER BY e.date_debut ASC
            LIMIT 50
        ";
        try {
            $upcomingEvents = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            error_log('listeEvenements: ' . $e->getMessage());
            require_once __DIR__ . '/../models/Event.php';
            $upcomingEvents = (new Event())->getUpcoming();
            foreach ($upcomingEvents as &$ev) {
                $ev['sponsor_nom'] = null;
            }
            unset($ev);
        }

        $catSet = [];
        foreach ($upcomingEvents as $ev) {
            $catSet[$this->eventCategoryForRow($ev)] = true;
        }
        $sortedCats = array_keys($catSet);
        sort($sortedCats, SORT_STRING);
        $categories = array_merge(['Toutes'], $sortedCats);

        $isLoggedIn = !empty($_SESSION['user_id']);
        $inscrUrl    = $isLoggedIn ? 'index.php?page=mes_inscriptions' : 'index.php?page=login';
        $countEvents = count($upcomingEvents);

        ob_start();
        ?>
        <div class="mb-3">
                <h1 class="dt-page-head-title mb-1">Événements médicaux</h1>
                <p class="dt-page-head-sub mb-0"><?= (int)$countEvents ?> événement<?= $countEvents > 1 ? 's' : '' ?> disponible<?= $countEvents > 1 ? 's' : '' ?></p>
            </div>
        <div class="dt-toolbar-row">
            <div class="dt-toolbar-filters" id="dtCatFilters">
            <?php foreach ($categories as $idx => $cat): ?>
            <button type="button" class="dt-filter-pill<?= $idx === 0 ? ' active' : '' ?>" data-cat="<?= htmlspecialchars($cat, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($cat, ENT_QUOTES, 'UTF-8') ?></button>
            <?php endforeach; ?>
            </div>
            <form class="d-flex dt-evt-search-form flex-shrink-0" role="search" onsubmit="return false;">
                <input type="search" id="dtEvtSearch" class="form-control rounded-end-0 border-end-0" placeholder="Rechercher un événement..." aria-label="Rechercher un événement">
                <button type="button" class="btn dt-btn-teal rounded-start-0 px-3" id="dtEvtSearchBtn" aria-label="Rechercher"><i class="fas fa-search"></i></button>
            </form>
        </div>
        <div class="dt-inscription-banner mb-4">
            <div class="d-flex align-items-center gap-2 text-primary flex-shrink-0"><i class="fas fa-user-check fa-lg"></i></div>
            <p class="mb-0 flex-grow-1 small" style="color:#1e3a5f;">Déjà inscrit(e) à un événement ? Consultez, modifiez ou annulez vos inscriptions depuis votre espace personnel.</p>
            <a href="<?= htmlspecialchars($inscrUrl, ENT_QUOTES, 'UTF-8') ?>" class="dt-btn-teal text-decoration-none d-inline-flex align-items-center gap-2 ms-auto">Mes inscriptions <i class="fas fa-arrow-right"></i></a>
        </div>
        <div class="row g-4" id="dtEventsGrid">
        <?php if (empty($upcomingEvents)): ?>
        <div class="col-12 text-center py-5 text-muted">
                <div style="font-size:3.5rem;opacity:.35;line-height:1;"><i class="fas fa-calendar-check"></i></div>
                <p class="mb-0 mt-3">Aucun événement disponible.</p>
        </div>
        <?php else: ?>
        <?php foreach ($upcomingEvents as $event):
            $cat        = $this->eventCategoryForRow($event);
            [$stLabel, $stClass] = $this->eventStatusPublicBadge((string)($event['status'] ?? 'à venir'));
            $slug       = htmlspecialchars($event['slug'] ?? '', ENT_QUOTES, 'UTF-8');
            $detailUrl  = 'index.php?page=detail_evenement&slug=' . $slug;
            $imageRaw   = trim((string)($event['image'] ?? ''));
            $imageStyle = $imageRaw !== ''
                ? "background-image:url('" . htmlspecialchars($imageRaw, ENT_QUOTES, 'UTF-8') . "')"
                : '';
            $capMax     = (int)($event['capacite_max'] ?? 0);
            $placesTxt  = $capMax > 0 ? $capMax . ' place' . ($capMax > 1 ? 's' : '') : '—';
            $prix       = (float)($event['prix'] ?? 0);
            $prixTxt    = $prix > 0 ? number_format($prix, 2, ',', ' ') . ' TND' : 'Gratuit';
            $d1         = date('d/m/Y', strtotime($event['date_debut']));
            $d2         = date('d/m/Y', strtotime($event['date_fin']));
            $dateRange  = $d1 === $d2 ? $d1 : $d1 . ' → ' . $d2;
            $sponsorNom = trim((string)($event['sponsor_nom'] ?? ''));
            $searchBlob = mb_strtolower(
                ($event['titre'] ?? '') . ' ' . ($event['description'] ?? '') . ' ' . $cat . ' ' . ($event['lieu'] ?? ''),
                'UTF-8'
            );
            ?>
        <div class="col-md-6 col-lg-4 dt-evt-col" data-category="<?= htmlspecialchars($cat, ENT_QUOTES, 'UTF-8') ?>" data-search="<?= htmlspecialchars($searchBlob, ENT_QUOTES, 'UTF-8') ?>">
            <div class="dt-card-event">
                <div class="dt-img-wrap" style="<?= $imageStyle ?>">
                    <span class="dt-badge-status <?= htmlspecialchars($stClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($stLabel, ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <span class="dt-cat-pill"><?= htmlspecialchars($cat, ENT_QUOTES, 'UTF-8') ?></span>
                <div class="dt-event-body">
                    <h2 class="dt-event-title"><?= htmlspecialchars($event['titre'] ?? '', ENT_QUOTES, 'UTF-8') ?></h2>
                    <p class="dt-event-desc"><?= htmlspecialchars($this->truncatePlain($event['description'] ?? '', 220), ENT_QUOTES, 'UTF-8') ?></p>
                    <div class="dt-meta-row"><i class="fas fa-map-marker-alt"></i><span><?= htmlspecialchars($event['lieu'] ?? 'À déterminer', ENT_QUOTES, 'UTF-8') ?></span></div>
                    <div class="dt-meta-row"><i class="fas fa-calendar-alt"></i><span><?= htmlspecialchars($dateRange, ENT_QUOTES, 'UTF-8') ?></span></div>
                    <div class="dt-meta-row"><i class="fas fa-users"></i><span><?= htmlspecialchars($placesTxt, ENT_QUOTES, 'UTF-8') ?></span></div>
                    <div class="dt-meta-row"><i class="fas fa-money-bill-wave"></i><span><?= htmlspecialchars($prixTxt, ENT_QUOTES, 'UTF-8') ?></span></div>
                    <?php if ($sponsorNom !== ''): ?>
                    <div class="dt-meta-row"><i class="fas fa-building"></i><span><strong>Sponsors :</strong> <?= htmlspecialchars($sponsorNom, ENT_QUOTES, 'UTF-8') ?></span></div>
                    <?php endif; ?>
                    <a href="<?= htmlspecialchars($detailUrl, ENT_QUOTES, 'UTF-8') ?>" class="dt-btn-teal dt-btn-detail text-decoration-none mt-3">Voir les détails <i class="fas fa-arrow-right"></i></a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
        </div>
        <script>
        (function(){
            var grid = document.getElementById('dtEventsGrid');
            if (!grid) return;
            var inp = document.getElementById('dtEvtSearch');
            var pills = document.querySelectorAll('#dtCatFilters .dt-filter-pill');
            var activeCat = 'Toutes';
            function norm(s){ return (s||'').toLowerCase().trim(); }
            function apply(){
                var q = norm(inp ? inp.value : '');
                grid.querySelectorAll('.dt-evt-col').forEach(function(col){
                    var okCat = activeCat === 'Toutes' || col.getAttribute('data-category') === activeCat;
                    var blob = norm(col.getAttribute('data-search'));
                    var okSearch = !q || blob.indexOf(q) !== -1;
                    col.style.display = (okCat && okSearch) ? '' : 'none';
                });
            }
            if (inp) { inp.addEventListener('input', apply); }
            var sb = document.getElementById('dtEvtSearchBtn');
            if (sb) sb.addEventListener('click', apply);
            pills.forEach(function(p){
                p.addEventListener('click', function(){
                    pills.forEach(function(x){ x.classList.remove('active'); });
                    p.classList.add('active');
                    activeCat = p.getAttribute('data-cat') || 'Toutes';
                    apply();
                });
            });
        })();
        </script>
        <?php
        $content = ob_get_clean();
        $this->renderPublicView('Événements', $content, true, 'doctime');
    }

    public function detailEvenement($id = null): void {
        require_once __DIR__ . '/../models/Event.php';
        $eventModel = new Event();
        $slug  = isset($_GET['slug']) ? preg_replace('/[^a-z0-9-]/', '', trim($_GET['slug'])) : null;
        $event = $slug ? $eventModel->getBySlug($slug) : ($id ? $eventModel->getById((int)$id) : null);
        if (!$event) {
            $this->page404();
            return;
        }
        $isLoggedIn = isset($_SESSION['user_id']);
        $userId     = $isLoggedIn ? (int)$_SESSION['user_id'] : 0;
        $eventId    = (int)$event['id'];
        $alreadyIn  = $isLoggedIn && $eventModel->isParticipant($eventId, $userId);
        $regHint    = '';
        $canRegister = false;
        if ($isLoggedIn && !$alreadyIn) {
            $chk = $eventModel->canUserRegister($eventId, $userId);
            $canRegister = $chk['ok'];
            $regHint     = $chk['ok'] ? '' : $chk['message'];
        }
        $prix       = $event['prix'] ?? 0;
        $prixText   = $prix > 0 ? number_format((float)$prix, 2, ',', ' ') . ' TND' : 'Gratuit';
        $cap        = (int)($event['capacite_max'] ?? 0);
        $nbPart     = (int)($event['nb_participants'] ?? 0);
        $restCol    = $event['places_restantes'] ?? null;
        if ($cap > 0) {
            $placesLeft = $restCol !== null && $restCol !== '' ? (int)$restCol : max(0, $cap - $nbPart);
            $placesLabel = $placesLeft . ' place' . ($placesLeft > 1 ? 's' : '') . ' restante' . ($placesLeft > 1 ? 's' : '') . ' sur ' . $cap;
        } else {
            $placesLabel = 'Capacité ouverte';
        }
        $ddeb = strtotime((string)$event['date_debut']);
        $dfin = strtotime((string)$event['date_fin']);
        $dateLong = $ddeb && $dfin
            ? (date('d/m/Y H:i', $ddeb) . ($dfin !== $ddeb ? ' → ' . date('d/m/Y H:i', $dfin) : ''))
            : '';

        ob_start();
        ?>
        <div class="dt-detail-hero">
            <a href="index.php?page=evenements" class="back-link"><i class="fas fa-arrow-left me-2"></i>Retour aux événements</a>
            <h1 style="font-size:2.5rem;margin-top:15px;"><?= htmlspecialchars($event['titre']) ?></h1>
            <p class="mb-0 opacity-90"><i class="fas fa-calendar-alt me-2"></i><?= htmlspecialchars($dateLong) ?></p>
        </div>
        <div class="row g-4">
            <div class="col-lg-8">
                <?php if (!empty($event['image'])): ?>
                <img src="<?= htmlspecialchars($event['image']) ?>" alt="" class="w-100 rounded-3 shadow-sm mb-4" style="max-height:400px;object-fit:cover;">
                <?php endif; ?>
                <div class="bg-white rounded-3 p-4 mb-4 shadow-sm border" style="border-color:#e2e8f0!important;">
                    <h3 class="dt-detail-accent mb-3" style="font-weight:700;">Description</h3>
                    <?= nl2br(htmlspecialchars($event['description'] ?? 'Aucune description.')) ?>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="dt-detail-cta mb-3">
                    <div class="small text-uppercase opacity-75 mb-1">Tarif</div>
                    <div style="font-size:2rem;font-weight:800;line-height:1.2;"><?= htmlspecialchars($prixText) ?></div>
                </div>
                <div class="dt-reg-meta card border-0 shadow-sm mb-3 rounded-3" style="border:1px solid #e2e8f0;">
                    <div class="card-body">
                        <ul class="list-unstyled mb-0 small dt-reg-meta-list">
                            <?php if (!empty($event['lieu'])): ?>
                            <li class="mb-2"><i class="fas fa-map-marker-alt text-secondary me-2 w-1"></i><?= htmlspecialchars((string)$event['lieu']) ?></li>
                            <?php endif; ?>
                            <li class="mb-2"><i class="fas fa-users text-secondary me-2"></i><?= htmlspecialchars($placesLabel) ?></li>
                            <li><i class="fas fa-clipboard-check text-secondary me-2"></i>Inscriptions en ligne</li>
                        </ul>
                    </div>
                </div>
                <div id="evtRegAlert" class="alert alert-dismissible fade d-none mb-3" role="alert">
                    <span id="evtRegAlertMsg"></span>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
                </div>
                <?php if ($alreadyIn): ?>
                <div class="alert alert-success mb-3 border-0 rounded-3 shadow-sm">
                    <i class="fas fa-check-circle me-2"></i><strong>Vous êtes inscrit.</strong>
                    <p class="small mb-2 mt-2">Retrouvez le détail et l’annulation éventuelle dans votre espace.</p>
                    <a href="index.php?page=mes_inscriptions" class="dt-btn-teal text-decoration-none d-inline-flex align-items-center gap-2">Mes inscriptions <i class="fas fa-arrow-right"></i></a>
                </div>
                <?php elseif (!$isLoggedIn): ?>
                <p class="small text-muted mb-3">Connectez-vous pour réserver votre place à cet événement.</p>
                <a href="index.php?page=login" class="dt-btn-teal text-decoration-none d-block text-center py-2 rounded-3 fw-semibold">Se connecter pour s’inscrire</a>
                <?php elseif ($canRegister): ?>
                <button type="button" id="evtRegisterBtn" class="dt-register-btn w-100" data-event-id="<?= $eventId ?>">
                    <i class="fas fa-user-plus me-2"></i>S’inscrire à cet événement
                </button>
                <p id="evtRegisterHint" class="small text-muted mt-2 mb-0">En vous inscrivant, vous acceptez de recevoir des informations relatives à cet événement.</p>
                <?php else: ?>
                <div class="alert alert-warning mb-0 rounded-3 border-0">
                    <i class="fas fa-info-circle me-2"></i><?= htmlspecialchars($regHint ?: 'Inscription impossible pour le moment.') ?>
                </div>
                <?php endif; ?>
                <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 10800;">
                    <div id="evtRegToast" class="toast align-items-center border-0 text-bg-success" role="alert" aria-live="assertive" aria-atomic="true">
                        <div class="d-flex">
                            <div class="toast-body" id="evtRegToastBody"></div>
                            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Fermer"></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php if ($isLoggedIn && $canRegister): ?>
        <script>
        (function(){
            var btn = document.getElementById('evtRegisterBtn');
            if (!btn) return;
            var toastEl = document.getElementById('evtRegToast');
            var toastBody = document.getElementById('evtRegToastBody');
            var alertEl = document.getElementById('evtRegAlert');
            var alertMsg = document.getElementById('evtRegAlertMsg');
            function showToast(isSuccess, msg) {
                if (!toastEl || !toastBody || typeof bootstrap === 'undefined') {
                    alert(msg);
                    return;
                }
                toastEl.classList.toggle('text-bg-success', isSuccess);
                toastEl.classList.toggle('text-bg-danger', !isSuccess);
                toastBody.textContent = msg;
                var t = bootstrap.Toast.getOrCreateInstance(toastEl, { delay: 4500 });
                t.show();
            }
            function showInline(isSuccess, msg) {
                if (!alertEl || !alertMsg) { showToast(isSuccess, msg); return; }
                alertEl.classList.remove('d-none', 'alert-success', 'alert-danger');
                alertEl.classList.add('show', isSuccess ? 'alert-success' : 'alert-danger');
                alertMsg.textContent = msg;
            }
            btn.addEventListener('click', function() {
                var id = btn.getAttribute('data-event-id');
                btn.disabled = true;
                var spin = document.createElement('span');
                spin.className = 'spinner-border spinner-border-sm me-2';
                spin.setAttribute('role', 'status');
                btn.insertBefore(spin, btn.firstChild);
                fetch('index.php?page=event_register', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                    body: 'event_id=' + encodeURIComponent(id)
                })
                .then(function(r) { return r.json().catch(function() { throw new Error('Réponse invalide'); }); })
                .then(function(d) {
                    if (d.success) {
                        showToast(true, d.message || 'Inscription enregistrée.');
                        btn.replaceWith(function() {
                            var a = document.createElement('a');
                            a.href = d.mes_inscriptions || 'index.php?page=mes_inscriptions';
                            a.className = 'dt-btn-teal text-decoration-none d-block text-center py-2 rounded-3 fw-semibold';
                            a.innerHTML = '<i class="fas fa-clipboard-check me-2"></i>Voir mes inscriptions';
                            return a;
                        }());
                        var hint = document.getElementById('evtRegisterHint');
                        if (hint) hint.remove();
                    } else {
                        showInline(false, d.message || 'Erreur.');
                        showToast(false, d.message || 'Impossible de s’inscrire.');
                        btn.disabled = false;
                        var sp = btn.querySelector('.spinner-border');
                        if (sp) sp.remove();
                    }
                })
                .catch(function() {
                    showInline(false, 'Erreur réseau. Vérifiez votre connexion.');
                    btn.disabled = false;
                    var sp = btn.querySelector('.spinner-border');
                    if (sp) sp.remove();
                });
            });
        })();
        </script>
        <?php endif; ?>
        <?php
        $content = ob_get_clean();
        $this->renderPublicView(htmlspecialchars($event['titre']), $content, true, 'doctime');
    }

    /** Inscription AJAX aux événements (places + transaction via Event::addParticipant) */
    public function registerEventAction(): void {
        header('Content-Type: application/json; charset=utf-8');
        if (empty($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'code' => 'auth', 'message' => 'Veuillez vous connecter pour vous inscrire.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $eventId = (int)($_POST['event_id'] ?? 0);
        $userId  = (int)$_SESSION['user_id'];
        if (!$eventId) {
            echo json_encode(['success' => false, 'code' => 'invalid', 'message' => 'Requête invalide.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        require_once __DIR__ . '/../models/Event.php';
        $eventModel = new Event();
        $check = $eventModel->canUserRegister($eventId, $userId);
        if (!$check['ok']) {
            echo json_encode(['success' => false, 'code' => $check['code'], 'message' => $check['message']], JSON_UNESCAPED_UNICODE);
            exit;
        }
        if (!$eventModel->addParticipant($eventId, $userId)) {
            echo json_encode(['success' => false, 'code' => 'save', 'message' => 'Impossible d’enregistrer votre inscription. Réessayez ou contactez le support.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        echo json_encode([
            'success' => true,
            'code'    => 'ok',
            'message' => 'Inscription confirmée ! Retrouvez cet événement dans « Mes inscriptions ».',
            'mes_inscriptions' => 'index.php?page=mes_inscriptions',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** Désinscription événement (formulaire POST) */
    public function unregisterEventAction(): void {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            header('Location: index.php?page=mes_inscriptions');
            exit;
        }
        $this->requireLogin();
        $eventId = (int)($_POST['event_id'] ?? 0);
        if (!$eventId) {
            $_SESSION['error'] = 'Événement invalide.';
            header('Location: index.php?page=mes_inscriptions');
            exit;
        }
        require_once __DIR__ . '/../models/Event.php';
        $eventModel = new Event();
        if ($eventModel->removeParticipant($eventId, (int)$_SESSION['user_id'])) {
            $_SESSION['success'] = 'Inscription annulée.';
        } else {
            $_SESSION['error'] = 'Impossible d\'annuler cette inscription.';
        }
        header('Location: index.php?page=mes_inscriptions');
        exit;
    }

    /** Liste des inscriptions événements de l’utilisateur connecté (style DocTime) */
    public function mesInscriptionsEvenements(): void {
        $this->requireLogin();
        $userId = (int)$_SESSION['user_id'];
        $pdo    = Database::getInstance()->getConnection();
        $this->ensureDocTimeSchema($pdo);
        $rows = [];
        try {
            $sql = "
                SELECT p.id AS participation_id, p.statut AS participation_statut, p.date_inscription,
                       e.id AS event_id, e.titre, e.slug, e.description, e.date_debut, e.date_fin,
                       e.lieu, e.image, e.prix, e.status, e.capacite_max,
                       s.nom AS sponsor_nom
                FROM participations p
                INNER JOIN events e ON e.id = p.event_id
                LEFT JOIN sponsors s ON s.id = e.sponsor_id
                WHERE p.user_id = :u
                ORDER BY e.date_debut DESC
            ";
            $st = $pdo->prepare($sql);
            $st->execute([':u' => $userId]);
            $rows = $st->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            error_log('mesInscriptionsEvenements: ' . $e->getMessage());
            try {
                $sql2 = "
                    SELECT p.id AS participation_id, p.statut AS participation_statut, p.date_inscription,
                           e.id AS event_id, e.titre, e.slug, e.description, e.date_debut, e.date_fin,
                           e.lieu, e.image, e.prix, e.status, e.capacite_max, NULL AS sponsor_nom
                    FROM participations p
                    INNER JOIN events e ON e.id = p.event_id
                    WHERE p.user_id = :u
                    ORDER BY e.date_debut DESC
                ";
                $st2 = $pdo->prepare($sql2);
                $st2->execute([':u' => $userId]);
                $rows = $st2->fetchAll(PDO::FETCH_ASSOC);
            } catch (Throwable $e2) {
                $rows = [];
            }
        }

        ob_start();
        ?>
        <h1 class="dt-page-head-title mb-1">Mes inscriptions</h1>
        <p class="dt-page-head-sub mb-4">Consultez et gérez vos inscriptions aux événements médicaux.</p>
        <div class="mb-4">
            <a href="index.php?page=evenements" class="dt-btn-teal text-decoration-none d-inline-flex align-items-center gap-2"><i class="fas fa-arrow-left"></i> Voir les événements</a>
        </div>
        <?php if (empty($rows)): ?>
        <div class="dt-inscription-banner mb-0">
            <div class="d-flex align-items-center gap-2 text-primary"><i class="fas fa-info-circle fa-lg"></i></div>
            <p class="mb-0 small" style="color:#1e3a5f;">Vous n’êtes inscrit à aucun événement pour le moment.</p>
            <a href="index.php?page=evenements" class="dt-btn-teal text-decoration-none ms-auto">Parcourir les événements <i class="fas fa-arrow-right"></i></a>
        </div>
        <?php else: ?>
        <div class="row g-4">
            <?php foreach ($rows as $row):
                $cat        = $this->eventCategoryForRow($row);
                [$stLabel, $stClass] = $this->eventStatusPublicBadge((string)($row['status'] ?? 'à venir'));
                $slug       = htmlspecialchars((string)($row['slug'] ?? ''), ENT_QUOTES, 'UTF-8');
                $detailUrl  = 'index.php?page=detail_evenement&slug=' . $slug;
                $imageRaw   = trim((string)($row['image'] ?? ''));
                $imageStyle = $imageRaw !== '' ? "background-image:url('" . htmlspecialchars($imageRaw, ENT_QUOTES, 'UTF-8') . "')" : '';
                $prix       = (float)($row['prix'] ?? 0);
                $prixTxt    = $prix > 0 ? number_format($prix, 2, ',', ' ') . ' TND' : 'Gratuit';
                $d1         = date('d/m/Y', strtotime((string)$row['date_debut']));
                $d2         = date('d/m/Y', strtotime((string)$row['date_fin']));
                $dateRange  = $d1 === $d2 ? $d1 : $d1 . ' → ' . $d2;
                $sponsorNom = trim((string)($row['sponsor_nom'] ?? ''));
                $pStatRaw   = (string)($row['participation_statut'] ?? '');
                $pStatLabel = match ($pStatRaw) {
                    'présent', 'present' => ['Présence confirmée', 'success'],
                    'absent' => ['Absent', 'secondary'],
                    'inscrit' => ['Inscrit', 'info'],
                    default => ['Inscrit', 'info'],
                };
                ?>
            <div class="col-md-6 col-lg-4">
                <div class="dt-card-event">
                    <div class="dt-img-wrap" style="<?= $imageStyle ?>"><span class="dt-badge-status <?= htmlspecialchars($stClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($stLabel, ENT_QUOTES, 'UTF-8') ?></span></div>
                    <span class="dt-cat-pill"><?= htmlspecialchars($cat, ENT_QUOTES, 'UTF-8') ?></span>
                    <div class="dt-event-body">
                        <h2 class="dt-event-title"><?= htmlspecialchars((string)($row['titre'] ?? ''), ENT_QUOTES, 'UTF-8') ?></h2>
                        <p class="small mb-2">
                            <span class="badge bg-<?= htmlspecialchars($pStatLabel[1], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($pStatLabel[0], ENT_QUOTES, 'UTF-8') ?></span>
                            <?php if (!empty($row['date_inscription'])): ?>
                            <span class="text-muted"> · <?= htmlspecialchars(date('d/m/Y à H:i', strtotime((string)$row['date_inscription'])), ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                        </p>
                        <p class="dt-event-desc"><?= htmlspecialchars($this->truncatePlain($row['description'] ?? '', 180), ENT_QUOTES, 'UTF-8') ?></p>
                        <div class="dt-meta-row"><i class="fas fa-map-marker-alt"></i><span><?= htmlspecialchars((string)($row['lieu'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></span></div>
                        <div class="dt-meta-row"><i class="fas fa-calendar-alt"></i><span><?= htmlspecialchars($dateRange, ENT_QUOTES, 'UTF-8') ?></span></div>
                        <div class="dt-meta-row"><i class="fas fa-money-bill-wave"></i><span><?= htmlspecialchars($prixTxt, ENT_QUOTES, 'UTF-8') ?></span></div>
                        <?php if ($sponsorNom !== ''): ?>
                        <div class="dt-meta-row"><i class="fas fa-building"></i><span><strong>Sponsors :</strong> <?= htmlspecialchars($sponsorNom, ENT_QUOTES, 'UTF-8') ?></span></div>
                        <?php endif; ?>
                        <a href="<?= htmlspecialchars($detailUrl, ENT_QUOTES, 'UTF-8') ?>" class="dt-btn-teal dt-btn-detail text-decoration-none">Voir les détails <i class="fas fa-arrow-right"></i></a>
                        <?php if (($row['status'] ?? '') === 'à venir'): ?>
                        <form method="post" action="index.php?page=event_unregister" class="mt-2" onsubmit="return confirm('Annuler votre inscription à cet événement ?');">
                            <input type="hidden" name="event_id" value="<?= (int)($row['event_id'] ?? 0) ?>">
                            <button type="submit" class="btn btn-outline-danger w-100 rounded-3 fw-semibold">Annuler l’inscription</button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <?php
        $content = ob_get_clean();
        $this->renderPublicView('Mes inscriptions', $content, true, 'doctime');
    }

    /** Page publique sponsors (schéma database.sql : niveau, actif) */
    public function listSponsors(): void {
        $pdo = Database::getInstance()->getConnection();
        $this->ensureDocTimeSchema($pdo);
        try {
            $stmt = $pdo->query("
                SELECT id, nom, email, telephone, logo, site_web, description, niveau, actif
                FROM sponsors
                WHERE actif = 1
                ORDER BY FIELD(niveau,'platinium','gold','silver','bronze'), nom
            ");
            $sponsors = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (Throwable $e) {
            error_log('listSponsors: ' . $e->getMessage());
            try {
                $stmt = $pdo->query("SELECT id, nom, logo, site_web, description, niveau, actif FROM sponsors WHERE actif = 1 ORDER BY FIELD(niveau,'platinium','gold','silver','bronze'), nom");
                $sponsors = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
            } catch (Throwable $e2) {
                $sponsors = [];
            }
        }

        $levelBadge = static function (string $nk): array {
            return match ($nk) {
                'platinium' => ['Platine', 'dt-level-plat'],
                'gold' => ['Or', 'dt-level-or'],
                'silver' => ['Argent', 'dt-level-argent'],
                'bronze' => ['Bronze', 'dt-level-bronze'],
                default => ['Partenaire', 'dt-level-argent'],
            };
        };

        ob_start();
        ?>
        <h1 class="dt-page-head-title mb-1">Nos Sponsors</h1>
        <p class="dt-page-head-sub mb-4">Partenaires qui soutiennent les événements médicaux.</p>
        <?php if (empty($sponsors)): ?>
        <div class="alert alert-info text-center rounded-3 py-5">Aucun sponsor disponible.</div>
        <?php else: ?>
        <div class="row g-4">
            <?php foreach ($sponsors as $sponsor):
                $nk = (string)($sponsor['niveau'] ?? 'bronze');
                [$lvlLabel, $lvlClass] = $levelBadge($nk);
                $email = trim((string)($sponsor['email'] ?? ''));
                $tel   = trim((string)($sponsor['telephone'] ?? ''));
                $site  = trim((string)($sponsor['site_web'] ?? ''));
            ?>
            <div class="col-md-6 col-lg-4">
                <div class="dt-sponsor-card">
                    <div class="dt-sponsor-head">
                        <div class="dt-sponsor-ico"><i class="fas fa-building"></i></div>
                        <div class="flex-grow-1 min-w-0">
                            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                <h2 class="dt-sponsor-name mb-0"><?= htmlspecialchars($sponsor['nom'] ?? '', ENT_QUOTES, 'UTF-8') ?></h2>
                                <span class="dt-level-badge <?= htmlspecialchars($lvlClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($lvlLabel, ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="dt-meta-row"><i class="fas fa-envelope"></i>
                        <?php if ($email !== ''): ?>
                        <a href="mailto:<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?></a>
                        <?php else: ?><span>—</span><?php endif; ?>
                    </div>
                    <div class="dt-meta-row"><i class="fas fa-phone"></i>
                        <span><?= $tel !== '' ? htmlspecialchars($tel, ENT_QUOTES, 'UTF-8') : '—' ?></span>
                    </div>
                    <div class="dt-meta-row"><i class="fas fa-globe"></i>
                        <?php if ($site !== ''): ?>
                        <a href="<?= htmlspecialchars($site, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer"><?= htmlspecialchars($site, ENT_QUOTES, 'UTF-8') ?></a>
                        <?php else: ?><span>—</span><?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <?php
        $content = ob_get_clean();
        $this->renderPublicView('Nos Sponsors', $content, true, 'doctime');
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
        ob_start();
        include __DIR__ . '/../views/partials/rendezvous_chatbot.php';
        $content .= ob_get_clean();
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
        require_once __DIR__ . '/../config/rendez_vous_labels.php';
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
            .rdv-page-toolbar { width:100%; }
            a.btn-prendre-rdv {
                display:inline-flex;align-items:center;justify-content:center;
                background:linear-gradient(135deg,#2A7FAA 0%,#4CAF50 100%);
                color:#fff !important;font-weight:600;padding:10px 22px;border-radius:999px;
                text-decoration:none;border:none;box-shadow:0 2px 10px rgba(42,127,170,0.25);
                font-size:0.95rem;transition:filter .15s ease,transform .15s ease;
            }
            a.btn-prendre-rdv:hover { filter:brightness(1.06); color:#fff !important; transform:translateY(-1px); }
            a.btn-prendre-rdv:focus-visible { outline:2px solid #4CAF50; outline-offset:2px; }
        </style>';

        if (!$isMedecin) {
            $html .= '
        <div class="rdv-page-toolbar mb-3 d-flex flex-wrap justify-content-end align-items-center gap-2">
            <a href="index.php?page=prendre_rendez_vous" class="btn-prendre-rdv"><i class="fas fa-calendar-plus me-2"></i>Prendre un nouveau rendez-vous</a>
        </div>';
        }

        $html .= '
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
                $html .= '<a href="index.php?page=prendre_rendez_vous" class="btn-prendre-rdv mt-3"><i class="fas fa-calendar-plus me-2"></i>Prendre un nouveau rendez-vous</a>';
            }
            $html .= '</div>';
        } else {
            foreach ($rendezVous as $rdv) {
                $st = rendez_vous_statut_canonical($rdv['statut'] ?? '');
                $badgeClass = match ($st) {
                    'confirmé'   => 'badge-confirme',
                    'en_attente' => 'badge-attente',
                    'terminé'    => 'badge-termine',
                    'annulé'     => 'badge-annule',
                    default      => 'badge-attente',
                };
                $showEditDelete = ($st !== 'terminé' && $st !== 'annulé');
                $statutLibelle  = htmlspecialchars(rendez_vous_statut_libelle($rdv['statut'] ?? ''), ENT_QUOTES, 'UTF-8');
                $html .= '
                <div class="rdv-card">
                    <div class="rdv-header">
                        <span class="rdv-title"><i class="fas ' . ($isMedecin ? 'fa-user' : 'fa-user-md') . ' me-2"></i>'
                            . ($isMedecin ? htmlspecialchars($rdv['patient_nom']) : 'Dr. ' . htmlspecialchars($rdv['medecin_nom'])) . '</span>
                        <span class="badge-statut ' . $badgeClass . '">' . $statutLibelle . '</span>
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
                if ($isMedecin && $st === 'en_attente') {
                    $html .= '<a href="index.php?page=confirmer_rendez_vous&id=' . $rdv['id'] . '" class="btn-action btn-confirmer" onclick="return confirm(\'Confirmer ce rendez-vous ?\')"><i class="fas fa-check me-1"></i>Confirmer</a>';
                }
                if ($isMedecin && $st === 'confirmé') {
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
        ob_start();
        include __DIR__ . '/../views/partials/rendezvous_chatbot.php';
        $html .= ob_get_clean();

        return $html;
    }

    public function annulerRendezVous($id): void { $this->requireLogin(); $this->renderTemporaryView('Annuler rendez-vous', '<p>Rendez-vous #' . htmlspecialchars($id) . ' annulé</p>'); }
    public function confirmerRendezVous($id): void { $this->requireLogin(); $this->renderTemporaryView('Confirmer rendez-vous', '<p>Rendez-vous #' . htmlspecialchars($id) . ' confirmé</p>'); }
    public function mesOrdonnances(): void {
        $this->requireLogin();
        require_once __DIR__ . '/../models/Ordonnance.php';
        $ordModel    = new Ordonnance();
        $ordonnances = $ordModel->getAllByPatient((int)($_SESSION['user_id'] ?? 0));
        foreach ($ordonnances as &$o) {
            $full = trim((string)($o['medecin_nom'] ?? ''));
            $full = preg_replace('/^Dr\.\s*/iu', '', $full);
            $parts             = preg_split('/\s+/', $full, 2, PREG_SPLIT_NO_EMPTY);
            $o['medecin_prenom'] = $parts[0] ?? '';
            $o['medecin_nom']    = $parts[1] ?? ($parts[0] ?? '');
        }
        unset($o);
        require __DIR__ . '/../views/frontoffice/patient/mes_ordonnances.php';
    }
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

        $content .= '<script src="assets/js/camera-stream.js"></script>';

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
            if (!window.DoctimeCamera || typeof window.DoctimeCamera.acquireVideoStream !== "function") {
                document.getElementById("faceIdStatus").innerHTML = '<div class="alert alert-danger">Script caméra indisponible. Rechargez la page.</div>';
                return;
            }
            document.getElementById("faceIdStatus").innerHTML = '<div class="alert alert-info"><i class="fas fa-spinner fa-spin me-2"></i>Accès à la caméra...</div>';
            window.DoctimeCamera.acquireVideoStream().then(function(stream) {
                stream.getTracks().forEach(function(track) { track.stop(); });
                window.location.href = "index.php?page=register_face";
            }).catch(function(err) {
                var hint = window.DoctimeCamera.formatHint(err);
                var msg = (err && err.message) ? err.message : "Accès refusé";
                document.getElementById("faceIdStatus").innerHTML = '<div class="alert alert-danger">Erreur d\'accès à la caméra : ' + msg +
                    (hint ? '<br><small class="d-block mt-2">' + hint + '</small>' : '') + '</div>';
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
        $jsonInput = null;
        $ct = strtolower($_SERVER['CONTENT_TYPE'] ?? '');
        if (str_contains($ct, 'application/json')) {
            $decoded = json_decode((string) file_get_contents('php://input'), true);
            $jsonInput = is_array($decoded) ? $decoded : null;
        }
        $action = $_POST['action'] ?? '';
        if ($action === '' && $jsonInput && !empty($jsonInput['action'])) {
            $action = (string) $jsonInput['action'];
        }

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
            $uploadDir = __DIR__ . '/../uploads/avatars/';
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
            $input = $jsonInput ?? [];
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
                
                $uploadDir = __DIR__ . '/../uploads/faces/';
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

    private function renderPublicView(string $title, string $content, bool $fullBleedLayout = false, string $navVariant = 'valorys'): void {
        if (!headers_sent()) {
            header('Content-Type: text/html; charset=UTF-8');
        }
        $bodyClass = $fullBleedLayout ? 'page-doctime-bg' : '';
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
        <body<?= $bodyClass !== '' ? ' class="' . htmlspecialchars($bodyClass, ENT_QUOTES, 'UTF-8') . '"' : '' ?>>
            <?= $this->getPublicNavbar(null, $navVariant) ?>
            <?php if ($fullBleedLayout): ?>
            <div class="container py-4">
                <?= $this->getFlashMessages() ?>
                <?= $content ?>
            </div>
            <?php else: ?>
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
            <?php endif; ?>
            <?= $this->getFooter() ?>
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        </body>
        </html>
        <?php
    }

    /** Page publique type « fil » : fond gris, sans carte Bootstrap englobante (blog article) */
    private function renderPublicViewFeed(string $htmlTitle, string $content): void {
        ?>
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?= htmlspecialchars($htmlTitle) ?> - Valorys</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
            <?= $this->getCustomStyles() ?>
        </head>
        <body style="background:#f0f2f5;">
            <?= $this->getPublicNavbar() ?>
            <div class="px-2 py-3">
                <div class="container" style="max-width:700px;">
                    <?= $this->getFlashMessages() ?>
                </div>
                <?= $content ?>
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

    if (!empty($_SESSION['gamification_toast'])) {
        $gt = $_SESSION['gamification_toast'];
        unset($_SESSION['gamification_toast']);
        if (is_array($gt)) {
            $json = json_encode($gt, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
            if ($json !== false) {
                $html .= '<style>
                    .gami-toast{position:fixed;bottom:22px;right:22px;z-index:20000;background:linear-gradient(155deg,#0f1b28 0%,#1a2c40 100%);color:#fff;border-radius:16px;padding:16px 40px 16px 16px;max-width:380px;box-shadow:0 14px 48px rgba(0,0,0,.4);border:1px solid rgba(255,255,255,.1);display:none}
                    .gami-toast.show{display:block;animation:valoGamiIn .28s ease}
                    @keyframes valoGamiIn{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:none}}
                    .gami-toast-inner{display:flex;gap:12px;align-items:flex-start}
                    .gami-toast-ico{font-size:1.6rem;line-height:1;flex-shrink:0}
                    .gami-toast-title{color:#ffe082;font-weight:800;font-size:15px;line-height:1.35}
                    .gami-toast-sub{color:#e8eaed;font-size:13px;margin-top:8px;line-height:1.45}
                    .gami-toast-wrap{position:relative}
                    .gami-toast-x{position:absolute;top:10px;right:12px;background:none;border:none;color:#9aa0a6;font-size:1.35rem;cursor:pointer;line-height:1;padding:4px}
                    .gami-toast-x:hover{color:#fff}
                </style>';
                $html .= '<script>(function(){var g=' . $json . ';if(!g)return;var pts=+g.points_added||0,tot=+g.total_points||0,rew=g.new_rewards||[];if(pts<=0&&tot<=0&&(!rew||!rew.length))return;var w=document.createElement("div");w.id="gamiToast";w.className="gami-toast gami-toast-wrap show";var title=(pts>0?"+"+pts+" points gagnés 🎯":"Points mis à jour 🎯")+" ("+tot+" pts au total)";var sub="";if(rew&&rew.length){sub="🎈 Nouvelle récompense : "+(rew[0].title||"")+" ! Un certificat vous a été envoyé par email 🎓"}w.innerHTML="<button type=\\"button\\" class=\\"gami-toast-x\\" aria-label=\\"Fermer\\">&times;</button><div class=\\"gami-toast-inner\\"><span class=\\"gami-toast-ico\\">🎯</span><div><div class=\\"gami-toast-title\\"></div><div class=\\"gami-toast-sub\\"></div></div></div>";w.querySelector(".gami-toast-title").textContent=title;var se=w.querySelector(".gami-toast-sub");if(sub){se.textContent=sub}else{se.style.display="none"}document.body.appendChild(w);w.querySelector(".gami-toast-x").onclick=function(){w.remove()};setTimeout(function(){if(w.parentNode)w.remove()},10000);})();</script>';
            }
        }
    }
 
    return $html;
}

    // =============================================
    // COMPOSANTS HTML
    // =============================================

    private function getCustomStyles(): string {
        ob_start();
        include __DIR__ . '/../views/partials/public_theme_styles.php';
        return ob_get_clean();
    }

    /** Barre nav publique unique : views/partials/nav_public.php */
    private function getPublicNavbar(?string $navActiveOverride = null, string $navVariant = 'valorys'): string {
        $navActive = $navActiveOverride ?? ($_GET['page'] ?? '');
        ob_start();
        include __DIR__ . '/../views/partials/nav_public.php';
        return ob_get_clean();
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

    /** Bloc « Avis patients » (formulaire + liste) pour l’accueil. */
    private function getHomeReviewsSectionHTML(): string {
        if (!is_file(__DIR__ . '/../views/frontoffice/reviews_section.php')) {
            return '';
        }
        require_once __DIR__ . '/../models/Review.php';
        $reviewModel = new Review();
        $reviews = $reviewModel->getApproved(8, 0);
        foreach ($reviews as &$r) {
            $r['emojis'] = $reviewModel->getEmojis((int)$r['id']);
        }
        unset($r);
        $stats          = $reviewModel->getStats();
        $reviewsApiBase = 'api/reviews.php';
        ob_start();
        include __DIR__ . '/../views/frontoffice/reviews_section.php';
        return (string) ob_get_clean();
    }

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
            <div class="col-md-3"><div class="card h-100 text-center p-3"><div class="card-body"><i class="fas fa-pills fa-3x mb-3" style="color:#2A7FAA"></i><h5>Parapharmacie</h5><p class="text-muted small">Achetez vos produits de soin</p><a href="index.php?page=parapharmacie" class="btn btn-sm" style="background:linear-gradient(135deg,#2A7FAA,#4CAF50);color:white;border:none">Découvrir</a></div></div></div>';
            return '
            <div class="text-center mb-5"><h1 class="display-4 mb-3">Bonjour, ' . $userName . '&nbsp;!</h1><p class="lead text-muted">Bienvenue sur votre espace Valorys</p></div>
            <div class="row g-4 mb-5">' . $roleContent . '</div>
            <div class="row g-4">
                <div class="col-md-6"><div class="card"><div class="card-header bg-white"><h5 class="mb-0"><i class="fas fa-calendar-alt text-primary me-2"></i>Prochain Rendez-vous</h5></div><div class="card-body text-center py-4"><p class="text-muted">Aucun rendez-vous planifié</p><a href="index.php?page=medecins" class="btn btn-primary btn-sm">Prendre un rendez-vous</a></div></div></div>
                <div class="col-md-6"><div class="card"><div class="card-header bg-white"><h5 class="mb-0"><i class="fas fa-ticket-alt text-success me-2"></i>Événements à Venir</h5></div><div class="card-body"><div class="d-flex justify-content-between mb-2"><span>🏥 Conférence sur la cardiologie</span><small class="text-muted">15 Avril 2026</small></div><div class="d-flex justify-content-between"><span>🍎 Atelier bien-être</span><small class="text-muted">22 Avril 2026</small></div><div class="text-center mt-3"><a href="index.php?page=evenements" class="btn btn-sm btn-outline-primary">Voir tous</a></div></div></div></div>
            </div>' . $this->getHomeReviewsSectionHTML();
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
        </div>' . $this->getHomeReviewsSectionHTML();
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
