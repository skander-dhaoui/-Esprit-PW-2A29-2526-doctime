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
        header('Location: index.php?page=blog_public');
        exit;
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
            $content = '<div class="alert alert-info py-5 text-center shadow-sm" style="border-radius: 15px;"><i class="fas fa-user-md fa-4x text-muted mb-3"></i><br><h4 class="text-muted">Aucun médecin disponible pour le moment.</h4></div>';
        } else {
            $content = '
            <style>
                .medecin-grid { display: flex; flex-wrap: wrap; gap: 30px; justify-content: center; padding: 20px 0; }
                .medecin-card { background: rgba(255,255,255,0.95); border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); width: 320px; overflow: hidden; transition: all 0.4s; position: relative; }
                .medecin-card:hover { transform: translateY(-10px); box-shadow: 0 15px 40px rgba(42,127,170,0.2); }
                .medecin-avatar-wrapper { background: linear-gradient(135deg,#2A7FAA,#4CAF50); height: 120px; display: flex; justify-content: center; align-items: flex-end; }
                .medecin-avatar { width: 90px; height: 90px; background: white; border-radius: 50%; border: 4px solid white; box-shadow: 0 5px 15px rgba(0,0,0,0.1); display: flex; align-items: center; justify-content: center; font-size: 2.5rem; color: #2A7FAA; margin-bottom: -45px; font-weight: bold; }
                .medecin-info { padding: 60px 25px 30px; text-align: center; }
                .medecin-name { font-size: 1.3rem; font-weight: 800; color: #2c3e50; margin-bottom: 8px; }
                .medecin-specialty { background: #e3f2fd; color: #1976d2; padding: 6px 15px; border-radius: 20px; font-size: 0.85rem; font-weight: 700; display: inline-block; margin-bottom: 20px; }
                .medecin-contact { color: #6c757d; font-size: 0.95rem; line-height: 1.6; margin-bottom: 25px; background: #f8f9fa; padding: 15px; border-radius: 12px; }
                .medecin-contact div { display: flex; align-items: center; margin-bottom: 8px; }
                .medecin-contact i { width: 25px; color: #4CAF50; }
                .btn-view-profile { background: linear-gradient(135deg,#2A7FAA,#175476); color: white; border: none; border-radius: 25px; padding: 12px 25px; font-weight: 600; text-transform: uppercase; font-size: 0.85rem; text-decoration: none; display: inline-block; width: 100%; }
                .btn-view-profile:hover { background: linear-gradient(135deg,#175476,#0d3851); color: white; }
            </style>
            <div style="text-align:center;margin-bottom:3rem;">
                <h2 style="color:#2c3e50;font-weight:800;font-size:2.5rem;"><i class="fas fa-user-md text-primary me-3"></i>Nos Médecins Spécialistes</h2>
                <p style="color:#6c757d;font-size:1.2rem;">Découvrez notre équipe de professionnels de santé à votre écoute</p>
            </div>
            <div class="medecin-grid">';
            
            foreach ($medecins as $medecin) {
                $userId = $medecin['user_id'] ?? $medecin['id'] ?? 0;
                $initialKey = strtoupper(substr($medecin['prenom'], 0, 1) . substr($medecin['nom'], 0, 1));
                $content .= '
                <div class="medecin-card">
                    <div class="medecin-avatar-wrapper"><div class="medecin-avatar">' . htmlspecialchars($initialKey) . '</div></div>
                    <div class="medecin-info">
                        <h3 class="medecin-name">Dr. ' . htmlspecialchars($medecin['prenom'] . ' ' . $medecin['nom']) . '</h3>
                        <div class="medecin-specialty">' . htmlspecialchars($medecin['specialite']) . '</div>
                        <div class="medecin-contact text-start">
                            <div><i class="fas fa-envelope"></i> ' . htmlspecialchars($medecin['email']) . '</div>
                            <div><i class="fas fa-phone-alt"></i> ' . htmlspecialchars($medecin['telephone'] ?? 'Non renseigné') . '</div>
                        </div>
                        <a href="index.php?page=detail_medecin&id=' . $userId . '" class="btn-view-profile">
                            <i class="fas fa-user-circle me-2"></i> Voir le profil
                        </a>
                    </div>
                </div>';
            }
            $content .= '</div>';
        }
        $this->renderTemporaryView('Nos Médecins', $content);
    }

    public function detailMedecin($id): void {
        require_once __DIR__ . '/../models/Medecin.php';
        $medecinModel = new Medecin();
        $medecin = $medecinModel->findByUserId($id);
        if (!$medecin) { $this->page404(); return; }
        $avatarText = strtoupper(substr($medecin['prenom'] ?? 'M', 0, 1) . substr($medecin['nom'] ?? 'D', 0, 1));
        $specialite = htmlspecialchars($medecin['specialite'] ?? 'Généraliste');
        $userRole = $_SESSION['user_role'] ?? '';
        $showAppointmentBtn = ($userRole === '' || $userRole === 'patient');
        $content = '
        <div class="container mt-5 mb-5">
            <div style="background:linear-gradient(135deg,rgba(42,127,170,0.1),rgba(76,175,80,0.05));border-radius:20px;padding:40px;margin-bottom:30px;display:flex;align-items:center;gap:30px;">
                <div style="width:120px;height:120px;border-radius:50%;background:linear-gradient(135deg,#2A7FAA,#4CAF50);display:flex;align-items:center;justify-content:center;color:#fff;font-size:48px;font-weight:700;border:4px solid #fff;flex-shrink:0;">' . $avatarText . '</div>
                <div>
                    <div style="display:inline-flex;align-items:center;padding:6px 15px;background:rgba(42,127,170,0.1);color:#2A7FAA;border-radius:20px;font-size:14px;font-weight:600;margin-bottom:15px;"><i class="fas fa-certificate me-2"></i> Professionnel vérifié</div>
                    <h2 style="color:#0f2b3d;font-weight:700;margin-bottom:5px;">Dr. ' . htmlspecialchars($medecin['prenom'] . ' ' . $medecin['nom']) . '</h2>
                    <p class="text-muted mb-0"><i class="fas fa-stethoscope me-1"></i> ' . $specialite . '</p>
                </div>
            </div>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:20px;margin-bottom:30px;">
                <div style="background:#fff;padding:20px;border-radius:16px;box-shadow:0 4px 15px rgba(0,0,0,0.03);display:flex;align-items:flex-start;gap:15px;">
                    <div style="width:40px;height:40px;border-radius:10px;background:rgba(42,127,170,0.1);color:#2A7FAA;display:flex;align-items:center;justify-content:center;"><i class="fas fa-envelope"></i></div>
                    <div><div style="font-size:12px;text-transform:uppercase;color:#6c757d;font-weight:700;">Email</div><p style="margin:0;font-weight:500;">' . htmlspecialchars($medecin['email']) . '</p></div>
                </div>
                <div style="background:#fff;padding:20px;border-radius:16px;box-shadow:0 4px 15px rgba(0,0,0,0.03);display:flex;align-items:flex-start;gap:15px;">
                    <div style="width:40px;height:40px;border-radius:10px;background:rgba(76,175,80,0.1);color:#4CAF50;display:flex;align-items:center;justify-content:center;"><i class="fas fa-phone"></i></div>
                    <div><div style="font-size:12px;text-transform:uppercase;color:#6c757d;font-weight:700;">Téléphone</div><p style="margin:0;font-weight:500;">' . htmlspecialchars($medecin['telephone'] ?? 'Non renseigné') . '</p></div>
                </div>
            </div>
            <div style="display:flex;gap:15px;justify-content:center;margin-top:30px;">
                <a href="index.php?page=medecins" style="background:#fff;color:#6c757d;padding:12px 30px;border-radius:50px;font-weight:600;text-decoration:none;border:2px solid #e9ecef;"><i class="fas fa-arrow-left"></i> Retour</a>
                ' . ($showAppointmentBtn ? '<a href="index.php?page=prendre_rendez_vous&id=' . $id . '" style="background:linear-gradient(135deg,#2A7FAA,#4CAF50);color:#fff;padding:12px 30px;border-radius:50px;font-weight:600;text-decoration:none;"><i class="fas fa-calendar-check"></i> Prendre rendez-vous</a>' : '') . '
            </div>
        </div>';
        $this->renderPublicView('Détail du médecin', $content);
    }

    // =============================================
    // BLOG - PAGES PUBLIQUES
    // =============================================

    public function blogList(): void {
        try {
            require_once __DIR__ . '/../models/Article.php';
            $articleModel = new Article();

            $isLoggedIn = isset($_SESSION['user_id']);
            $userId     = $_SESSION['user_id'] ?? null;
            $userRole   = $_SESSION['user_role'] ?? '';
            $isAdmin    = ($userRole === 'admin');

            if ($isAdmin) { header('Location: index.php?page=articles_admin'); exit; }

            // ── RECHERCHE ────────────────────────────────────
            $q         = trim($_GET['q']        ?? '');
            $categorie = trim($_GET['categorie'] ?? '');
            $date_min  = trim($_GET['date_min']  ?? '');
            $tag       = trim($_GET['tag']       ?? '');
            $sort      = $_GET['sort'] ?? 'desc';
            if (!in_array($sort, ['asc','desc'])) $sort = 'desc';

            if ($q !== '') {
                $articles = $articleModel->search($q);
            } elseif ($categorie !== '' || $date_min !== '' || $tag !== '') {
                $articles = $articleModel->advancedSearch(['categorie' => $categorie, 'date_min' => $date_min, 'tag' => $tag]);
            } else {
                $articles = $articleModel->getAll();
            }

            // ── TRI PAR DATE ─────────────────────────────────
            usort($articles, function($a, $b) use ($sort) {
                $ta = strtotime($a['created_at'] ?? '');
                $tb = strtotime($b['created_at'] ?? '');
                return $sort === 'asc' ? $ta - $tb : $tb - $ta;
            });

            // ── BARRE RECHERCHE + TRI ─────────────────────────
            $sortToggle = $sort === 'asc' ? 'desc' : 'asc';
            $sortLabel  = $sort === 'asc'  ? '↑ Plus ancien d\'abord' : '↓ Plus récent d\'abord';

            $searchForm = '
            <div style="background:white;border-radius:12px;padding:15px;margin-bottom:15px;box-shadow:0 1px 3px rgba(0,0,0,0.1);">
                <form method="GET" action="index.php" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
                    <input type="hidden" name="page" value="blog_public">
                    <input type="hidden" name="sort" value="' . htmlspecialchars($sort) . '">
                    <div style="flex:1;min-width:200px;position:relative;">
                        <i class="fas fa-search" style="position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#65676b;"></i>
                        <input type="text" name="q" value="' . htmlspecialchars($q) . '"
                            placeholder="Rechercher par titre, auteur ou contenu..."
                            style="width:100%;padding:12px 14px 12px 40px;border:2px solid #e4e6eb;border-radius:25px;font-size:15px;outline:none;background:#f0f2f5;box-sizing:border-box;">
                    </div>
                    <button type="submit" style="background:#1877f2;color:white;border:none;border-radius:25px;padding:11px 22px;cursor:pointer;font-size:15px;white-space:nowrap;font-weight:600;">
                        <i class="fas fa-search"></i> Rechercher
                    </button>
                    <a href="index.php?page=blog_public&sort=' . $sortToggle . '&q=' . urlencode($q) . '" style="background:white;border:2px solid #e4e6eb;color:#1c1e21;border-radius:25px;padding:10px 18px;text-decoration:none;font-size:14px;white-space:nowrap;font-weight:600;">
                        <i class="fas fa-sort"></i> ' . $sortLabel . '
                    </a>
                    ' . ($q ? '<a href="index.php?page=blog_public&sort=' . $sort . '" style="background:#fee;color:#dc3545;border-radius:25px;padding:10px 14px;text-decoration:none;font-size:14px;">✕ Effacer</a>' : '') . '
                </form>
                ' . ($q ? '<div style="padding:8px 5px 0;font-size:14px;color:#65676b;"><i class="fas fa-info-circle"></i> ' . count($articles) . ' résultat(s) pour "<strong>' . htmlspecialchars($q) . '</strong>"</div>' : '') . '
            </div>

            <!-- FILTRES AVANCÉS -->
            <div style="background:#f8f9fa;border-radius:8px;padding:12px 15px;margin-bottom:20px;border:1px solid #e4e6eb;">
                <div onclick="var f=document.getElementById(\'sf\');f.style.display=(f.style.display===\'none\'||f.style.display===\'\'?\'block\':\'none\')" style="cursor:pointer;color:#2A7FAA;display:flex;justify-content:space-between;align-items:center;font-weight:600;font-size:14px;">
                    <span><i class="fas fa-sliders-h"></i> Filtres avancés</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div id="sf" style="display:' . ($categorie || $date_min || $tag ? 'block' : 'none') . ';margin-top:12px;">
                    <form method="GET" action="index.php">
                        <input type="hidden" name="page" value="blog_public">
                        <input type="hidden" name="sort" value="' . htmlspecialchars($sort) . '">
                        <div style="display:flex;gap:12px;flex-wrap:wrap;">
                            <div style="flex:1;min-width:160px;"><label style="font-size:13px;font-weight:600;">Catégorie</label><input type="text" name="categorie" value="' . htmlspecialchars($categorie) . '" style="width:100%;padding:8px;border:1px solid #ccc;border-radius:6px;margin-top:4px;" placeholder="Ex: Santé"></div>
                            <div style="flex:1;min-width:160px;"><label style="font-size:13px;font-weight:600;">Tag</label><input type="text" name="tag" value="' . htmlspecialchars($tag) . '" style="width:100%;padding:8px;border:1px solid #ccc;border-radius:6px;margin-top:4px;" placeholder="Ex: nutrition"></div>
                            <div style="flex:1;min-width:160px;"><label style="font-size:13px;font-weight:600;">Date minimum</label><input type="date" name="date_min" value="' . htmlspecialchars($date_min) . '" style="width:100%;padding:8px;border:1px solid #ccc;border-radius:6px;margin-top:4px;"></div>
                            <div style="width:100%;display:flex;justify-content:flex-end;gap:10px;margin-top:8px;">
                                <a href="index.php?page=blog_public" style="background:#6c757d;color:white;text-decoration:none;padding:8px 18px;border-radius:20px;font-size:14px;"><i class="fas fa-times"></i> Reset</a>
                                <button type="submit" style="background:#2A7FAA;color:white;border:none;padding:8px 18px;border-radius:20px;cursor:pointer;font-size:14px;"><i class="fas fa-filter"></i> Filtrer</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>';

            // ── ZONE FACEBOOK ─────────────────────────────────
            $userAvatar = strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1));
            $userName   = htmlspecialchars($_SESSION['user_name'] ?? '');

            if ($isLoggedIn) {
                $addButton = '
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:15px;">
                    <h2 style="margin:0;"><i class="fas fa-newspaper"></i> Nos articles (' . count($articles) . ')</h2>
                </div>
                <div style="background:white;border-radius:12px;padding:15px;margin-bottom:20px;box-shadow:0 2px 8px rgba(0,0,0,0.1);">
                    <div style="display:flex;align-items:center;gap:12px;">
                        <div style="width:42px;height:42px;border-radius:50%;background:linear-gradient(135deg,#2A7FAA,#4CAF50);display:flex;align-items:center;justify-content:center;color:white;font-weight:bold;font-size:18px;flex-shrink:0;">' . $userAvatar . '</div>
                        <div onclick="document.getElementById(\'articleModal\').style.display=\'flex\'" style="flex:1;background:#f0f2f5;border:none;border-radius:25px;padding:12px 20px;color:#65676b;font-size:15px;cursor:pointer;">
                            Quoi de neuf, ' . $userName . ' ?
                        </div>
                    </div>
                    <hr style="margin:12px 0;border:none;border-top:1px solid #e4e6eb;">
                    <div style="display:flex;justify-content:space-around;">
                        <div onclick="document.getElementById(\'articleModal\').style.display=\'flex\';setTimeout(function(){document.getElementById(\'fbImgInput\').click();},100)" style="display:flex;align-items:center;gap:8px;cursor:pointer;padding:8px 15px;border-radius:8px;color:#45bd62;font-weight:600;font-size:14px;">
                            <i class="fas fa-image" style="font-size:20px;"></i> Photo
                        </div>
                        <div onclick="document.getElementById(\'articleModal\').style.display=\'flex\';fbToggleEmoji()" style="display:flex;align-items:center;gap:8px;cursor:pointer;padding:8px 15px;border-radius:8px;color:#f7b928;font-weight:600;font-size:14px;">
                            <i class="fas fa-smile" style="font-size:20px;"></i> Emoji
                        </div>
                        <div onclick="document.getElementById(\'articleModal\').style.display=\'flex\';setTimeout(function(){document.getElementById(\'fb_titre\').focus();},100)" style="display:flex;align-items:center;gap:8px;cursor:pointer;padding:8px 15px;border-radius:8px;color:#f3425f;font-weight:600;font-size:14px;">
                            <i class="fas fa-pen" style="font-size:20px;"></i> Article
                        </div>
                    </div>
                </div>

                <!-- MODAL -->
                <div id="articleModal" style="display:none;position:fixed;z-index:9999;left:0;top:0;width:100%;height:100%;background:rgba(0,0,0,0.6);align-items:center;justify-content:center;" onclick="if(event.target===this)this.style.display=\'none\'">
                    <div style="background:white;border-radius:12px;width:95%;max-width:560px;max-height:90vh;overflow-y:auto;box-shadow:0 4px 30px rgba(0,0,0,0.3);" onclick="event.stopPropagation()">
                        <div style="padding:16px;border-bottom:1px solid #e4e6eb;position:relative;text-align:center;">
                            <h2 style="margin:0;font-size:20px;font-weight:700;">Créer une publication</h2>
                            <span onclick="document.getElementById(\'articleModal\').style.display=\'none\'" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:#e4e6eb;border-radius:50%;width:36px;height:36px;cursor:pointer;font-size:20px;display:flex;align-items:center;justify-content:center;">×</span>
                        </div>
                        <form method="POST" action="index.php?page=admin_article_create" enctype="multipart/form-data">
                            <div style="padding:16px;display:flex;align-items:center;gap:10px;">
                                <div style="width:42px;height:42px;border-radius:50%;background:linear-gradient(135deg,#2A7FAA,#4CAF50);display:flex;align-items:center;justify-content:center;color:white;font-weight:bold;font-size:18px;">' . $userAvatar . '</div>
                                <strong>' . $userName . '</strong>
                            </div>
                            <div style="padding:0 16px 10px;">
                                <input type="text" name="titre" id="fb_titre" placeholder="Titre de l\'article..." required oninput="fbCheckBtn()"
                                    style="width:100%;border:none;border-bottom:2px solid #e4e6eb;outline:none;font-size:16px;font-weight:600;padding:5px 0;box-sizing:border-box;">
                            </div>
                            <div style="padding:0 16px 10px;position:relative;">
                                <textarea name="contenu" id="fb_contenu" placeholder="Écrivez votre article..." rows="5" required oninput="fbCheckBtn()"
                                    style="width:100%;border:none;outline:none;font-size:16px;resize:none;box-sizing:border-box;"></textarea>
                                <span onclick="fbToggleEmoji()" style="position:absolute;right:20px;bottom:15px;cursor:pointer;font-size:22px;">😊</span>
                            </div>
                            <div id="fbEmojiZone" style="display:none;padding:10px 16px;border-top:1px solid #e4e6eb;flex-wrap:wrap;gap:6px;"></div>
                            <div id="fbImgPreview" style="display:none;padding:0 16px 10px;position:relative;">
                                <img id="fbPreviewImg" src="" style="width:100%;border-radius:8px;max-height:250px;object-fit:cover;">
                                <span onclick="fbRemoveImg()" style="position:absolute;top:10px;right:25px;background:rgba(0,0,0,0.6);color:white;border-radius:50%;width:30px;height:30px;cursor:pointer;display:flex;align-items:center;justify-content:center;">×</span>
                            </div>
                            <div style="margin:0 16px 12px;border:1px solid #e4e6eb;border-radius:10px;padding:10px 16px;display:flex;justify-content:space-between;align-items:center;">
                                <span style="font-weight:600;font-size:14px;">Ajouter à votre publication</span>
                                <div style="display:flex;gap:4px;">
                                    <label style="cursor:pointer;padding:8px;display:flex;align-items:center;">
                                        <i class="fas fa-image" style="font-size:22px;color:#45bd62;"></i>
                                        <input type="file" name="article_image" id="fbImgInput" accept="image/*" style="display:none;" onchange="fbPreviewImg(this)">
                                    </label>
                                    <span onclick="fbToggleEmoji()" style="cursor:pointer;padding:8px;display:flex;align-items:center;">
                                        <i class="fas fa-smile" style="font-size:22px;color:#f7b928;"></i>
                                    </span>
                                </div>
                            </div>
                            <div style="padding:0 16px 16px;">
                                <button type="submit" id="fbPublishBtn" disabled
                                    style="width:100%;background:#e4e6eb;color:#bcc0c4;border:none;border-radius:8px;padding:11px;font-size:16px;font-weight:600;cursor:not-allowed;">
                                    Publier
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <script>
                var _fbEmojis = ["😀","😂","😍","🥰","😎","😢","😡","👍","👎","❤️","🔥","✅","⭐","💪","🙏","🤔","😷","🏥","💊","🩺","🎉"];
                var _fbEmojiInit = false;
                function fbCheckBtn() {
                    var t = document.getElementById("fb_titre");
                    var c = document.getElementById("fb_contenu");
                    var f = document.getElementById("fbImgInput");
                    var b = document.getElementById("fbPublishBtn");
                    if (!b) return;
                    var ok = t && t.value.trim() && (c && c.value.trim() || f && f.files && f.files.length > 0);
                    b.disabled = !ok;
                    b.style.background = ok ? "#1877f2" : "#e4e6eb";
                    b.style.color = ok ? "white" : "#bcc0c4";
                    b.style.cursor = ok ? "pointer" : "not-allowed";
                }
                function fbToggleEmoji() {
                    var z = document.getElementById("fbEmojiZone");
                    if (!_fbEmojiInit && z) {
                        z.style.display = "flex";
                        _fbEmojis.forEach(function(em) {
                            var s = document.createElement("span");
                            s.textContent = em;
                            s.style.cssText = "font-size:22px;cursor:pointer;padding:4px;border-radius:6px;";
                            s.onmouseover = function(){this.style.background="#f0f2f5";};
                            s.onmouseout = function(){this.style.background="none";};
                            s.onclick = function(){
                                var ta = document.getElementById("fb_contenu");
                                if(ta){ta.value+=em;fbCheckBtn();}
                            };
                            z.appendChild(s);
                        });
                        _fbEmojiInit = true;
                    } else if (z) {
                        z.style.display = z.style.display === "none" || z.style.display === "" ? "flex" : "none";
                    }
                }
                function fbPreviewImg(input) {
                    if (input.files && input.files[0]) {
                        var r = new FileReader();
                        r.onload = function(ev) {
                            document.getElementById("fbPreviewImg").src = ev.target.result;
                            document.getElementById("fbImgPreview").style.display = "block";
                        };
                        r.readAsDataURL(input.files[0]);
                        fbCheckBtn();
                    }
                }
                function fbRemoveImg() {
                    document.getElementById("fbImgInput").value = "";
                    document.getElementById("fbImgPreview").style.display = "none";
                    document.getElementById("fbPreviewImg").src = "";
                    fbCheckBtn();
                }
                </script>';
            } else {
                $addButton = '
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:15px;">
                    <h2 style="margin:0;"><i class="fas fa-newspaper"></i> Nos articles (' . count($articles) . ')</h2>
                </div>';
            }

            // ── LISTE DES ARTICLES ────────────────────────────
            if (empty($articles)) {
                $content = '<div style="background:white;border-radius:12px;padding:40px;text-align:center;color:#65676b;box-shadow:0 1px 3px rgba(0,0,0,0.1);">
                    <i class="fas fa-newspaper fa-3x mb-3 d-block" style="opacity:0.3;"></i>
                    Aucun article trouvé' . ($q ? ' pour "<strong>' . htmlspecialchars($q) . '</strong>"' : '') . '
                </div>';
            } else {
                $content = '<div style="max-width:680px;margin:0 auto;">';
                foreach ($articles as $article) {
                    $canEdit = $isLoggedIn && isset($article['auteur_id']) && $userId == $article['auteur_id'];
                    $initial = strtoupper(substr($article['auteur_name'] ?? 'V', 0, 1));
                    $resume  = substr(strip_tags($this->quillToHtml($article['contenu'])), 0, 200);

                    $crudButtons = '';
                    if ($canEdit) {
                        $crudButtons = '
                        <div style="display:flex;gap:8px;">
                            <a href="index.php?page=admin_article_edit&id=' . $article['id'] . '" style="background:#ffc107;color:#000;padding:6px 14px;border-radius:20px;text-decoration:none;font-size:13px;font-weight:600;"><i class="fas fa-edit"></i> Modifier</a>
                            <a href="index.php?page=admin_article_delete&id=' . $article['id'] . '" onclick="return confirm(\'Supprimer cet article ?\')" style="background:#dc3545;color:#fff;padding:6px 14px;border-radius:20px;text-decoration:none;font-size:13px;font-weight:600;"><i class="fas fa-trash"></i> Supprimer</a>
                        </div>';
                    }

                    $content .= '
                    <div style="background:white;border-radius:12px;margin-bottom:20px;box-shadow:0 1px 3px rgba(0,0,0,0.12);overflow:hidden;">
                        <div style="padding:16px 16px 0;display:flex;justify-content:space-between;align-items:center;">
                            <div style="display:flex;align-items:center;gap:10px;">
                                <div style="width:42px;height:42px;border-radius:50%;background:linear-gradient(135deg,#2A7FAA,#4CAF50);display:flex;align-items:center;justify-content:center;color:white;font-weight:bold;font-size:18px;flex-shrink:0;">' . $initial . '</div>
                                <div>
                                    <div style="font-weight:700;font-size:15px;color:#1c1e21;">' . htmlspecialchars($article['auteur_name'] ?? 'Valorys') . '</div>
                                    <div style="font-size:12px;color:#65676b;">' . date('d/m/Y H:i', strtotime($article['created_at'])) . ' · <i class="fas fa-globe-africa"></i></div>
                                </div>
                            </div>
                            ' . $crudButtons . '
                        </div>
                        <div style="padding:12px 16px;">
                            <div style="font-size:17px;font-weight:700;color:#1c1e21;margin-bottom:6px;">' . htmlspecialchars($article['titre']) . '</div>
                            <div style="color:#444;font-size:15px;line-height:1.5;">' . $resume . ($resume ? '...' : '') . '</div>
                        </div>
                        ' . (!empty($article['image']) ? '<img src="' . htmlspecialchars($article['image']) . '" style="width:100%;max-height:400px;object-fit:cover;">' : '') . '
                        <div style="padding:8px 16px;display:flex;justify-content:space-between;align-items:center;border-top:1px solid #e4e6eb;color:#65676b;font-size:14px;">
                            <span>
                                <span style="background:#e7f3ff;color:#1877f2;border-radius:20px;padding:2px 10px;font-size:13px;">👍 ' . (int)($article['nb_likes'] ?? 0) . '</span>
                                <span style="background:#fff0f0;color:#dc3545;border-radius:20px;padding:2px 10px;font-size:13px;margin-left:4px;">👎 ' . (int)($article['nb_dislikes'] ?? 0) . '</span>
                            </span>
                            <span><i class="fas fa-eye"></i> ' . (int)($article['vues'] ?? 0) . ' · <i class="fas fa-comment"></i> ' . (int)($article['nb_replies'] ?? 0) . '</span>
                        </div>
                        <div style="padding:4px 8px;border-top:1px solid #e4e6eb;display:flex;">
                            ' . ($isLoggedIn ? '
                            <button id="like-art-' . $article['id'] . '" onclick="toggleLikeArticle(' . $article['id'] . ',\'like\')" style="flex:1;padding:8px;border:none;background:none;color:#65676b;font-weight:600;font-size:14px;cursor:pointer;border-radius:8px;">
                                👍 J\'aime (' . (int)($article['nb_likes'] ?? 0) . ')
                            </button>
                            <button id="dislike-art-' . $article['id'] . '" onclick="toggleLikeArticle(' . $article['id'] . ',\'dislike\')" style="flex:1;padding:8px;border:none;background:none;color:#65676b;font-weight:600;font-size:14px;cursor:pointer;border-radius:8px;">
                                👎 Je n\'aime pas (' . (int)($article['nb_dislikes'] ?? 0) . ')
                            </button>
                            ' : '') . '
                            <a href="index.php?page=detail_article_public&id=' . $article['id'] . '" style="flex:1;display:flex;align-items:center;justify-content:center;gap:6px;padding:8px;border-radius:8px;text-decoration:none;color:#65676b;font-weight:600;font-size:14px;">
                                <i class="fas fa-comment"></i> Commenter
                            </a>
                        </div>
                    </div>';
                }
                $content .= '</div>';
            }

            // ── SCRIPTS LIKE + DELETE ─────────────────────────
            $scripts = '
            <script>
            function toggleLikeArticle(articleId, type) {
                var url = "index.php?page=api_article_like";
                var xhr = new XMLHttpRequest();
                xhr.open("POST", url, true);
                xhr.setRequestHeader("Content-Type", "application/json");
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4) {
                        if (xhr.status === 200) {
                            try {
                                var d = JSON.parse(xhr.responseText);
                                if (d.success) {
                                    var lb = document.getElementById("like-art-" + articleId);
                                    var db = document.getElementById("dislike-art-" + articleId);
                                    if (lb) { lb.innerHTML = "👍 J\'aime (" + d.likes + ")"; lb.style.color = (type==="like" && d.action!=="removed") ? "#1877f2" : "#65676b"; }
                                    if (db) { db.innerHTML = "👎 Je n\'aime pas (" + d.dislikes + ")"; db.style.color = (type==="dislike" && d.action!=="removed") ? "#dc3545" : "#65676b"; }
                                } else {
                                    alert(d.message || "Erreur");
                                }
                            } catch(e) { console.error("Parse error:", xhr.responseText); }
                        } else {
                            console.error("HTTP error:", xhr.status);
                        }
                    }
                };
                xhr.send(JSON.stringify({article_id: parseInt(articleId), type: type}));
            }
            function toggleLikeReply(replyId, type) {
                var xhr = new XMLHttpRequest();
                xhr.open("POST", "index.php?page=api_reply_like", true);
                xhr.setRequestHeader("Content-Type", "application/json");
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4 && xhr.status === 200) {
                        try {
                            var d = JSON.parse(xhr.responseText);
                            if (d.success) {
                                var le = document.getElementById("rl-like-" + replyId);
                                var de = document.getElementById("rl-dislike-" + replyId);
                                if (le) le.textContent = "👍 " + d.likes;
                                if (de) de.textContent = "👎 " + d.dislikes;
                            }
                        } catch(e) { console.error(e); }
                    }
                };
                xhr.send(JSON.stringify({reply_id: parseInt(replyId), type: type}));
            }
            </script>';

            $infoMsg = !$isLoggedIn ? '<div style="background:#e3f2fd;border-left:4px solid #2196f3;padding:12px 20px;margin-bottom:20px;border-radius:5px;font-size:14px;"><i class="fas fa-info-circle"></i> <a href="index.php?page=login" style="color:#1976d2;">Connectez-vous</a> pour créer, modifier ou supprimer vos articles.</div>' : '';

            $fullContent = $infoMsg . $searchForm . $addButton . $content . $this->getDeleteScript();
            $this->renderPublicView('Blog Valorys', $fullContent);

        } catch (Exception $e) {
            error_log('Erreur blogList: ' . $e->getMessage());
            $this->renderPublicView('Blog Valorys', '<div class="alert alert-danger">Erreur: ' . htmlspecialchars($e->getMessage()) . '</div>');
        }
    }


    // =============================================
    // DETAIL ARTICLE — CRUD + COMMENTAIRES AVEC REPLY SUR REPLY
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
            header('Location: index.php?page=articles_admin&action=show&id=' . $id);
            exit;
        }

        $isAuthor = ($isLoggedIn && isset($article['auteur_id']) && $userId == $article['auteur_id']);

        ob_start();
        ?>
        <style>
            .reply-wrap { margin-bottom:20px; }
            .reply-box { background:#f8f9fa;border-radius:12px;padding:15px;border-left:3px solid #2A7FAA; }
            .reply-box.child { background:#fff;border-left:3px solid #4CAF50;margin-left:40px;margin-top:10px; }
            .reply-avatar { width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,#2A7FAA,#4CAF50);display:flex;align-items:center;justify-content:center;color:white;font-weight:bold;flex-shrink:0; }
            .reply-avatar.small { width:32px;height:32px;font-size:12px; }
            .reply-form-inline { background:#f0f7ff;border-radius:10px;padding:15px;margin-top:10px;display:none; }
            .emoji-bar { display:flex;flex-wrap:wrap;gap:5px;margin-bottom:10px; }
            .emoji-btn { font-size:20px;cursor:pointer;padding:4px;border-radius:6px;border:1px solid #ddd;background:white;transition:all 0.2s; }
            .emoji-btn:hover { background:#e3f2fd;transform:scale(1.2); }
            .img-preview { max-width:100%;max-height:200px;border-radius:8px;margin-top:8px;display:none; }
            .btn-reply-submit { background:linear-gradient(135deg,#2A7FAA,#4CAF50);color:white;border:none;padding:8px 20px;border-radius:20px;cursor:pointer;font-size:14px; }
            .btn-reply-toggle { background:none;border:1px solid #2A7FAA;color:#2A7FAA;padding:4px 12px;border-radius:20px;cursor:pointer;font-size:12px;margin-top:8px; }
            .btn-edit-reply { background:#ffc107;color:#000;border:none;padding:4px 10px;border-radius:4px;cursor:pointer;font-size:11px; }
            .btn-delete-reply { background:#dc3545;color:#fff;border:none;padding:4px 10px;border-radius:4px;cursor:pointer;font-size:11px; }
            .modal-overlay { display:none;position:fixed;z-index:9999;left:0;top:0;width:100%;height:100%;background:rgba(0,0,0,0.5); }
            .modal-overlay.show { display:flex;align-items:center;justify-content:center; }
            .modal-box { background:white;border-radius:16px;padding:25px;width:90%;max-width:500px; }
        </style>

        <!-- ARTICLE -->
        <div style="background:white;border-radius:12px;padding:30px;margin-bottom:30px;box-shadow:0 2px 8px rgba(0,0,0,0.1);">
            <div class="mb-3" style="display:flex;gap:10px;flex-wrap:wrap;">
                <a href="index.php?page=blog_public" class="btn btn-secondary btn-sm">← Retour au blog</a>
                <?php if ($isAuthor): ?>
                <a href="index.php?page=admin_article_edit&id=<?= $id ?>" class="btn btn-warning btn-sm">✏️ Modifier</a>
                <a href="index.php?page=admin_article_delete&id=<?= $id ?>" onclick="return confirm('Supprimer cet article ?')" class="btn btn-danger btn-sm">🗑 Supprimer</a>
                <?php endif; ?>
            </div>

            <?php if (!empty($article['image'])): ?>
            <img src="<?= htmlspecialchars($article['image']) ?>" style="width:100%;max-height:400px;object-fit:cover;border-radius:8px;margin-bottom:20px;">
            <?php endif; ?>

            <h1 style="font-size:2rem;margin-bottom:15px;"><?= htmlspecialchars($article['titre']) ?></h1>
            <div style="color:#666;font-size:14px;margin-bottom:20px;padding-bottom:15px;border-bottom:1px solid #eee;">
                <span>👤 <?= htmlspecialchars($article['auteur_name'] ?? 'Valorys') ?></span>
                <span style="margin-left:15px;">📅 <?= date('d/m/Y', strtotime($article['created_at'])) ?></span>
                <span style="margin-left:15px;">👁 <?= $article['vues'] ?? 0 ?> vues</span>
                <span style="margin-left:15px;">💬 <?= count($replies) ?> commentaire(s)</span>
            </div>
            <div style="line-height:1.8;color:#333;"><?= $this->quillToHtml($article['contenu']) ?></div>
        </div>

        <!-- COMMENTAIRES -->
        <div style="background:white;border-radius:12px;padding:25px;margin-bottom:30px;box-shadow:0 2px 8px rgba(0,0,0,0.1);">
            <h3 style="border-bottom:2px solid #2A7FAA;padding-bottom:10px;margin-bottom:20px;">
                💬 Commentaires (<?= count($replies) ?>)
            </h3>
            <?php if (empty($replies)): ?>
            <p style="color:#999;text-align:center;padding:20px;">Aucun commentaire. Soyez le premier !</p>
            <?php else: ?>
            <?php foreach ($replies as $reply): ?>
                <?= $this->renderReply($reply, $userId, $isLoggedIn, $id) ?>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- FORMULAIRE PRINCIPAL -->
        <?php if ($isLoggedIn): ?>
        <div style="background:white;border-radius:12px;padding:25px;box-shadow:0 2px 8px rgba(0,0,0,0.1);">
            <h4 style="margin-bottom:15px;">✍️ Laisser un commentaire</h4>
            <?= $this->getReplyForm($id, null) ?>
        </div>
        <?php else: ?>
        <div style="background:#e3f2fd;border-left:4px solid #2196f3;padding:15px;border-radius:8px;">
            <a href="index.php?page=login" style="color:#1976d2;">Connectez-vous</a> pour commenter.
        </div>
        <?php endif; ?>

        <!-- MODAL MODIFIER COMMENTAIRE -->
        <div id="editModal" class="modal-overlay">
            <div class="modal-box">
                <h4 style="margin-bottom:15px;">✏️ Modifier le commentaire</h4>
                <input type="hidden" id="edit_id">
                <div class="emoji-bar" id="edit-emoji-bar" style="margin-bottom:10px;"></div>
                <textarea id="edit_text" rows="4" style="width:100%;padding:10px;border:1px solid #ddd;border-radius:8px;margin-bottom:10px;resize:vertical;font-size:14px;"></textarea>
                <!-- IMAGE UPLOAD -->
                <div style="margin-bottom:10px;">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;background:#f0f2f5;border-radius:8px;padding:8px 12px;width:fit-content;">
                        <i class="fas fa-image" style="color:#45bd62;font-size:18px;"></i>
                        <span style="font-size:14px;color:#333;">Ajouter une image</span>
                        <input type="file" id="edit_image" accept="image/*" style="display:none;" onchange="editPreviewImg(this)">
                    </label>
                    <div id="edit_img_preview" style="display:none;margin-top:8px;position:relative;">
                        <img id="edit_img_preview_src" src="" style="max-width:100%;max-height:150px;border-radius:8px;object-fit:cover;">
                        <span onclick="editRemoveImg()" style="position:absolute;top:4px;right:4px;background:rgba(0,0,0,0.6);color:white;border-radius:50%;width:24px;height:24px;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:14px;">×</span>
                    </div>
                </div>
                <div style="display:flex;gap:10px;justify-content:flex-end;">
                    <button onclick="closeEdit()" style="background:#6c757d;color:white;border:none;padding:8px 20px;border-radius:8px;cursor:pointer;">Annuler</button>
                    <button onclick="saveEdit()" style="background:#2A7FAA;color:white;border:none;padding:8px 20px;border-radius:8px;cursor:pointer;">Enregistrer</button>
                </div>
            </div>
        </div>

        <!-- MODAL SUPPRIMER COMMENTAIRE -->
        <div id="deleteReplyModal" class="modal-overlay">
            <div class="modal-box" style="text-align:center;">
                <div style="font-size:48px;margin-bottom:15px;">🗑</div>
                <h4>Confirmer la suppression</h4>
                <p style="color:#666;margin-bottom:20px;">Cette action est irréversible.</p>
                <div style="display:flex;gap:10px;justify-content:center;">
                    <button onclick="closeDeleteReply()" style="background:#6c757d;color:white;border:none;padding:8px 20px;border-radius:8px;cursor:pointer;">Annuler</button>
                    <button id="confirmDeleteReplyBtn" style="background:#dc3545;color:white;border:none;padding:8px 20px;border-radius:8px;cursor:pointer;">Supprimer</button>
                </div>
            </div>
        </div>

        <!-- MODAL SUPPRIMER ARTICLE -->
        <div id="deleteModal" class="modal-overlay">
            <div class="modal-box" style="text-align:center;">
                <div style="font-size:48px;margin-bottom:15px;">⚠️</div>
                <h4>Supprimer l'article</h4>
                <p id="deleteArticleTitle" style="font-weight:bold;color:#2A7FAA;"></p>
                <p style="color:#dc3545;font-size:13px;margin-bottom:20px;">Supprimera aussi tous les commentaires.</p>
                <div style="display:flex;gap:10px;justify-content:center;">
                    <button onclick="closeDeleteModal()" style="background:#6c757d;color:white;border:none;padding:8px 20px;border-radius:8px;cursor:pointer;">Annuler</button>
                    <button id="confirmDeleteBtn" style="background:#dc3545;color:white;border:none;padding:8px 20px;border-radius:8px;cursor:pointer;">Supprimer</button>
                </div>
            </div>
        </div>

        <script>
        const emojis = ['😀','😂','😍','🥰','😎','😢','😡','👍','👎','❤️','🔥','✅','⭐','💪','🙏','🤔','😷','🏥','💊','🩺','🎉','😴','🤩','😬','🥳','😅','🤗','😐','🙁','😤'];

        // Emoji bars dans les formulaires inline
        document.querySelectorAll('.emoji-bar-inline').forEach(function(bar) {
            emojis.forEach(function(e) {
                var btn = document.createElement('button');
                btn.type = 'button'; btn.className = 'emoji-btn'; btn.textContent = e;
                btn.onclick = function() { bar.closest('form').querySelector('textarea').value += e; };
                bar.appendChild(btn);
            });
        });

        // Emoji bar modal edit
        var editBar = document.getElementById('edit-emoji-bar');
        emojis.forEach(function(e) {
            var btn = document.createElement('button');
            btn.type = 'button'; btn.className = 'emoji-btn'; btn.textContent = e;
            btn.onclick = function() { document.getElementById('edit_text').value += e; };
            editBar.appendChild(btn);
        });

        // Preview image
        document.querySelectorAll('.img-input').forEach(function(input) {
            input.addEventListener('change', function() {
                var preview = this.closest('form').querySelector('.img-preview');
                if (this.files && this.files[0]) {
                    var reader = new FileReader();
                    reader.onload = function(ev) { preview.src = ev.target.result; preview.style.display = 'block'; };
                    reader.readAsDataURL(this.files[0]);
                }
            });
        });

        function toggleReplyForm(id) {
            var form = document.getElementById('reply-form-' + id);
            if (form) form.style.display = form.style.display === 'none' ? 'block' : 'none';
        }

        // Edit
        function openEdit(id, text) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_text').value = text;
            document.getElementById('editModal').classList.add('show');
        }
        function editPreviewImg(input) {
            if (input.files && input.files[0]) {
                var r = new FileReader();
                r.onload = function(ev) {
                    document.getElementById('edit_img_preview_src').src = ev.target.result;
                    document.getElementById('edit_img_preview').style.display = 'block';
                };
                r.readAsDataURL(input.files[0]);
            }
        }
        function editRemoveImg() {
            document.getElementById('edit_image').value = '';
            document.getElementById('edit_img_preview').style.display = 'none';
            document.getElementById('edit_img_preview_src').src = '';
        }
        function closeEdit() { document.getElementById('editModal').classList.remove('show'); editRemoveImg(); }
        function saveEdit() {
            var id   = document.getElementById('edit_id').value;
            var text = document.getElementById('edit_text').value;
            var imgInput = document.getElementById('edit_image');
            if (!text.trim() && (!imgInput.files || imgInput.files.length === 0)) {
                alert('Le commentaire ne peut pas être vide.');
                return;
            }
            // Use FormData to support image
            var formData = new FormData();
            formData.append('reply_text', text);
            formData.append('_method', 'PUT');
            if (imgInput.files && imgInput.files[0]) {
                formData.append('reply_image', imgInput.files[0]);
            }
            fetch('index.php?page=api_reply_update&id=' + id, {
                method: 'POST',
                body: formData
            }).then(function(r) { return r.json(); })
            .then(function(d) {
                if (d.success) location.reload();
                else alert('Erreur: ' + (d.message || 'Impossible de modifier'));
            }).catch(function(e) { console.error(e); alert('Erreur réseau'); });
        }

        // Delete reply
        var deleteReplyId = null;
        function openDeleteReply(id) { deleteReplyId = id; document.getElementById('deleteReplyModal').classList.add('show'); }
        function closeDeleteReply() { document.getElementById('deleteReplyModal').classList.remove('show'); deleteReplyId = null; }
        document.getElementById('confirmDeleteReplyBtn').onclick = function() {
            if (!deleteReplyId) return;
            fetch('index.php?page=api_reply&id=' + deleteReplyId, {
                method:'POST', headers:{'Content-Type':'application/json'},
                body: JSON.stringify({_method:'DELETE'})
            }).then(r=>r.json()).then(d=>{ if(d.success) location.reload(); else alert('Erreur'); });
            closeDeleteReply();
        };

        // Delete article
        var deleteArticleId = null;
        function confirmDeleteArticle(id, title) {
            deleteArticleId = id;
            document.getElementById('deleteArticleTitle').textContent = title;
            document.getElementById('deleteModal').classList.add('show');
        }
        function closeDeleteModal() { document.getElementById('deleteModal').classList.remove('show'); deleteArticleId = null; }
        document.getElementById('confirmDeleteBtn').onclick = function() {
            if (deleteArticleId) window.location.href = 'index.php?page=admin_article_delete&id=' + deleteArticleId;
            closeDeleteModal();
        };

        document.querySelectorAll('.modal-overlay').forEach(function(m) {
            m.addEventListener('click', function(e) { if (e.target === this) this.classList.remove('show'); });
        });
        </script>
        <?php
        $content = ob_get_clean();
        $this->renderPublicView(htmlspecialchars($article['titre']), $content);
    }

    // =============================================
    // RENDU RÉCURSIF D'UN COMMENTAIRE
    // =============================================

    private function renderReply(array $reply, $userId, bool $isLoggedIn, int $articleId, int $depth = 0): string
    {
        $canEdit = $isLoggedIn && !empty($reply['user_id']) && $reply['user_id'] == $userId;
        $initial = strtoupper(substr($reply['auteur'] ?? 'A', 0, 1));
        $isChild = $depth > 0;

        ob_start();
        ?>
        <div class="reply-wrap">
            <div class="reply-box <?= $isChild ? 'child' : '' ?>">
                <div style="display:flex;gap:12px;align-items:flex-start;">
                    <div class="reply-avatar <?= $isChild ? 'small' : '' ?>"><?= htmlspecialchars($initial) ?></div>
                    <div style="flex:1;">
                        <div style="font-weight:bold;font-size:14px;color:#2c3e50;"><?= htmlspecialchars($reply['auteur'] ?? 'Anonyme') ?></div>
                        <div style="font-size:11px;color:#999;margin-bottom:8px;"><?= date('d/m/Y H:i', strtotime($reply['date_reply'])) ?></div>

                        <?php if (!empty($reply['emoji'])): ?>
                        <div style="font-size:30px;margin-bottom:6px;"><?= htmlspecialchars($reply['emoji']) ?></div>
                        <?php endif; ?>

                        <?php if (!empty($reply['contenu_text'])): ?>
                        <div style="color:#333;line-height:1.6;margin-bottom:8px;"><?= nl2br(htmlspecialchars($reply['contenu_text'])) ?></div>
                        <?php endif; ?>

                        <?php if (!empty($reply['photo'])): ?>
                        <img src="<?= htmlspecialchars($reply['photo']) ?>" style="max-width:100%;max-height:250px;border-radius:8px;margin-bottom:8px;">
                        <?php endif; ?>

                        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:8px;align-items:center;">
                            <?php if ($isLoggedIn && $depth < 3): ?>
                            <button class="btn-reply-toggle" onclick="toggleReplyForm(<?= $reply['id'] ?>)">💬 Répondre</button>
                            <?php endif; ?>
                            <?php if ($canEdit): ?>
                            <button class="btn-edit-reply" onclick="openEdit(<?= $reply['id'] ?>, '<?= addslashes(htmlspecialchars($reply['contenu_text'] ?? '')) ?>')">✏️ Modifier</button>
                            <button class="btn-delete-reply" onclick="openDeleteReply(<?= $reply['id'] ?>)">🗑 Supprimer</button>
                            <?php endif; ?>
                            <?php if ($isLoggedIn): ?>
                            <span style="margin-left:auto;display:flex;gap:6px;">
                                <button onclick="toggleLikeReply(<?= $reply['id'] ?>,'like')" style="background:none;border:1px solid #e4e6eb;border-radius:20px;padding:2px 10px;cursor:pointer;font-size:13px;" id="rl-like-<?= $reply['id'] ?>">👍 <?= (int)($reply['nb_likes'] ?? 0) ?></button>
                                <button onclick="toggleLikeReply(<?= $reply['id'] ?>,'dislike')" style="background:none;border:1px solid #e4e6eb;border-radius:20px;padding:2px 10px;cursor:pointer;font-size:13px;" id="rl-dislike-<?= $reply['id'] ?>">👎 <?= (int)($reply['nb_dislikes'] ?? 0) ?></button>
                            </span>
                            <?php endif; ?>
                        </div>

                        <?php if ($isLoggedIn && $depth < 3): ?>
                        <div id="reply-form-<?= $reply['id'] ?>" class="reply-form-inline">
                            <div style="font-size:13px;color:#2A7FAA;margin-bottom:8px;font-weight:bold;">
                                ↩ Répondre à <?= htmlspecialchars($reply['auteur'] ?? 'ce commentaire') ?>
                            </div>
                            <?= $this->getReplyForm($articleId, $reply['id']) ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php if (!empty($reply['children'])): ?>
            <div style="margin-left:<?= $depth < 2 ? 40 : 20 ?>px;">
                <?php foreach ($reply['children'] as $child): ?>
                    <?= $this->renderReply($child, $userId, $isLoggedIn, $articleId, $depth + 1) ?>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    // =============================================
    // FORMULAIRE DE COMMENTAIRE
    // =============================================

    private function getReplyForm(int $articleId, ?int $parentId): string
    {
        ob_start();
        ?>
        <form method="POST" action="index.php?page=detail_article_public&id=<?= $articleId ?>" enctype="multipart/form-data" style="margin-top:10px;">
            <input type="hidden" name="parent_id" value="<?= $parentId ?? '' ?>">
            <div class="emoji-bar emoji-bar-inline" style="margin-bottom:10px;"></div>
            <textarea name="reply_text" rows="3"
                placeholder="<?= $parentId ? 'Écrire une réponse...' : 'Écrire un commentaire...' ?>"
                style="width:100%;padding:10px;border:1px solid #ddd;border-radius:8px;margin-bottom:10px;resize:vertical;font-size:14px;"></textarea>
            <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                <label style="cursor:pointer;background:#f0f0f0;padding:7px 14px;border-radius:20px;font-size:13px;border:1px solid #ddd;display:flex;align-items:center;gap:6px;">
                    🖼 Image
                    <input type="file" name="reply_image" accept="image/*" class="img-input" style="display:none;">
                </label>
                <button type="submit" name="submit_reply" class="btn-reply-submit">📤 Publier</button>
            </div>
            <img class="img-preview" src="" alt="preview">
        </form>
        <?php
        return ob_get_clean();
    }

    // =============================================
    // AJOUTER UN COMMENTAIRE (avec support parent_id)
    // =============================================

    private function addReply($articleId): void {
        require_once __DIR__ . '/../models/Reply.php';
        require_once __DIR__ . '/../models/AdminNotification.php';

        $replyModel = new Reply();
        $notifModel = new AdminNotification();
        $userId      = $_SESSION['user_id']   ?? null;
        $auteur      = $_SESSION['user_name'] ?? 'Anonyme';
        $contenuText = trim($_POST['reply_text'] ?? '');
        $parentId    = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
        $imagePath   = null;

        if (isset($_FILES['reply_image']) && $_FILES['reply_image']['error'] === UPLOAD_ERR_OK) {
            $imagePath = $this->uploadReplyImage($_FILES['reply_image']);
        }

        if (empty($contenuText) && empty($imagePath)) {
            $_SESSION['error'] = 'Le commentaire ne peut pas être vide.';
            header("Location: index.php?page=detail_article_public&id=$articleId");
            exit;
        }

        // Créer le commentaire
        $replyId = $replyModel->createMixte($articleId, $contenuText ?: null, null, $imagePath, $auteur, $userId, $parentId);

        // Modération IA
        $modPath = file_exists(__DIR__ . '/../services/ModerationIA.php')
            ? __DIR__ . '/../services/ModerationIA.php'
            : (file_exists(__DIR__ . '/../models/ModerationIA.php') ? __DIR__ . '/../models/ModerationIA.php' : null);

        $approved = true;
        $rejectReason = '';

        if ($modPath && $replyId > 0) {
            require_once $modPath;
            if (class_exists('ModerationIA')) {
                $moderation = new ModerationIA();
                $moderationResult = $moderation->moderateReply($contenuText ?: 'Image partagée');
                if (($moderationResult['decision'] ?? 'approved') === 'rejected') {
                    $approved = false;
                    $rejectReason = $moderationResult['raison'] ?? 'Contenu inapproprié';
                }
            }
        }

        if (!$approved && $replyId > 0) {
            $replyModel->setModerationStatus($replyId, 'rejected', $rejectReason);
            $_SESSION['error'] = '⚠️ Votre commentaire a été refusé : ' . $rejectReason;
        } else {
            if ($replyId > 0) {
                $replyModel->setModerationStatus($replyId, 'approved');
            }
            $_SESSION['success'] = 'Commentaire ajouté avec succès !';
            // Notification admin
            try {
                $notifModel->add(
                    'new_reply',
                    '💬 Nouveau commentaire',
                    $auteur . ' a commenté : "' . substr($contenuText ?: 'Image', 0, 80) . '"',
                    $replyId,
                    'reply'
                );
            } catch (Exception $e) { /* ignore */ }
        }

        header("Location: index.php?page=detail_article_public&id=$articleId");
        exit;
    }

    // =============================================
    // UPLOAD IMAGE COMMENTAIRE
    // =============================================

    private function uploadReplyImage($file): ?string {
        if ($file['error'] !== UPLOAD_ERR_OK) return null;
        $allowedTypes = ['image/jpeg','image/png','image/jpg','image/gif','image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        if (!in_array($mimeType, $allowedTypes)) return null;
        if ($file['size'] > 2 * 1024 * 1024) return null;
        $uploadDir = __DIR__ . '/../uploads/replies/';
        if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename  = 'reply_' . time() . '_' . uniqid() . '.' . $extension;
        return move_uploaded_file($file['tmp_name'], $uploadDir . $filename) ? 'uploads/replies/' . $filename : null;
    }

    // =============================================
    // UPLOAD IMAGE ARTICLE
    // =============================================

    private function uploadArticleImage($file): ?string {
        if ($file['error'] !== UPLOAD_ERR_OK) return null;
        $allowedTypes = ['image/jpeg','image/png','image/jpg','image/gif','image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        if (!in_array($mimeType, $allowedTypes)) return null;
        if ($file['size'] > 2 * 1024 * 1024) return null;
        $uploadDir = __DIR__ . '/../uploads/articles/';
        if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename  = 'article_' . time() . '_' . uniqid() . '.' . $extension;
        return move_uploaded_file($file['tmp_name'], $uploadDir . $filename) ? 'uploads/articles/' . $filename : null;
    }

    // =============================================
    // SCRIPT SUPPRESSION ARTICLE
    // =============================================

    private function getDeleteScript(): string {
        return '
        <div id="deleteModal" style="display:none;position:fixed;z-index:10000;left:0;top:0;width:100%;height:100%;background:rgba(0,0,0,0.5);align-items:center;justify-content:center;">
            <div style="background:white;border-radius:20px;width:90%;max-width:450px;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,0.3);">
                <div style="background:linear-gradient(135deg,#dc3545,#c82333);color:white;padding:20px;text-align:center;">
                    <i class="fas fa-trash-alt" style="font-size:50px;margin-bottom:10px;display:block;"></i>
                    <h3 style="margin:0;">Confirmer la suppression</h3>
                </div>
                <div style="padding:25px;text-align:center;">
                    <p style="color:#555;">Êtes-vous sûr de vouloir supprimer :</p>
                    <div id="deleteArticleTitle" style="font-weight:bold;color:#2A7FAA;background:#e8f4f8;padding:8px;border-radius:8px;margin:10px 0;"></div>
                    <div style="color:#dc3545;font-size:13px;background:#ffe6e6;padding:10px;border-radius:8px;">
                        <i class="fas fa-exclamation-triangle"></i> Cette action est irréversible.
                    </div>
                </div>
                <div style="padding:15px 20px;display:flex;gap:10px;justify-content:center;border-top:1px solid #eee;">
                    <button onclick="document.getElementById(\'deleteModal\').style.display=\'none\'" style="padding:10px 25px;border:none;border-radius:25px;cursor:pointer;background:#f0f0f0;color:#666;font-weight:600;">Annuler</button>
                    <button onclick="doDeleteArticle()" style="padding:10px 25px;border:none;border-radius:25px;cursor:pointer;background:linear-gradient(135deg,#dc3545,#c82333);color:white;font-weight:600;">Supprimer</button>
                </div>
            </div>
        </div>
        <script>
        var _delId = null;
        function confirmDeleteArticle(articleId, articleTitle) {
            _delId = articleId;
            var titleEl = document.getElementById("deleteArticleTitle");
            if (titleEl) titleEl.textContent = articleTitle;
            document.getElementById("deleteModal").style.display = "flex";
        }
        function doDeleteArticle() {
            if (_delId) {
                window.location.href = "index.php?page=admin_article_delete&id=" + _delId;
            }
        }
        document.getElementById("deleteModal").onclick = function(e) {
            if (e.target === this) this.style.display = "none";
        };
        <\/script>';
    }

    public function listeArticles(): void { $this->blogList(); }
    public function detailArticle($id): void { $this->blogDetail($id); }

    // =============================================
    // CRUD ARTICLES
    // =============================================

    public function adminArticleCreate(): void {
        $this->requireLogin();
        require_once __DIR__ . '/../models/Article.php';
        require_once __DIR__ . '/../models/AdminNotification.php';

        // Charger ModerationIA depuis services/ ou models/
        $modPath = file_exists(__DIR__ . '/../services/ModerationIA.php')
            ? __DIR__ . '/../services/ModerationIA.php'
            : __DIR__ . '/../models/ModerationIA.php';
        if (file_exists($modPath)) require_once $modPath;

        $articleModel = new Article();
        $notifModel   = new AdminNotification();
        $userRole = $_SESSION['user_role'] ?? '';
        $isAdmin  = ($userRole === 'admin');

        if ($isAdmin) { header('Location: index.php?page=articles_admin&action=create'); exit; }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $titre     = trim($_POST['titre']   ?? '');
            $contenu   = trim($_POST['contenu'] ?? '');
            $auteur_id = $_SESSION['user_id'] ?? null;
            $errors    = [];

            if (empty($titre))               $errors[] = 'Le titre est obligatoire.';
            elseif (mb_strlen($titre) > 255) $errors[] = 'Le titre ne doit pas dépasser 255 caractères.';
            if (empty($contenu))             $errors[] = 'Le contenu est obligatoire.';

            $imagePath = null;
            if (isset($_FILES['article_image']) && $_FILES['article_image']['error'] === UPLOAD_ERR_OK) {
                $imagePath = $this->uploadArticleImage($_FILES['article_image']);
            }

            if (empty($errors)) {
                $articleId = $articleModel->create([
                    'titre'     => $titre,
                    'contenu'   => $contenu,
                    'auteur_id' => $auteur_id,
                    'image'     => $imagePath,
                    'status'    => 'publié'
                ]);

                if ($articleId > 0) {
                    // Modération IA
                    $modPath = file_exists(__DIR__ . '/../services/ModerationIA.php')
                        ? __DIR__ . '/../services/ModerationIA.php'
                        : (file_exists(__DIR__ . '/../models/ModerationIA.php') ? __DIR__ . '/../models/ModerationIA.php' : null);

                    $approved = true;
                    $rejectReason = '';

                    if ($modPath) {
                        require_once $modPath;
                        if (class_exists('ModerationIA')) {
                            $moderation = new ModerationIA();
                            $result = $moderation->moderateArticle($titre, $contenu);
                            if (($result['decision'] ?? 'approved') === 'rejected') {
                                $approved = false;
                                $rejectReason = $result['raison'] ?? 'Contenu inapproprié';
                            }
                        }
                    }

                    if (!$approved) {
                        $articleModel->setModerationStatus($articleId, 'rejected', $rejectReason);
                        $_SESSION['error'] = '⚠️ Votre article a été refusé : ' . $rejectReason;
                        try {
                            $notifModel->add('moderation_rejected', '🚫 Article rejeté',
                                'Article "' . $titre . '" rejeté. Raison : ' . $rejectReason, $articleId, 'article');
                        } catch (Exception $e) {}
                    } else {
                        $articleModel->setModerationStatus($articleId, 'approved');
                        $_SESSION['success'] = '✅ Article publié avec succès !';
                        try {
                            $notifModel->add('new_article', '📝 Nouvel article',
                                ($_SESSION['user_name'] ?? 'Un utilisateur') . ' a publié : "' . $titre . '"',
                                $articleId, 'article');
                        } catch (Exception $e) {}
                    }
                } else {
                    $_SESSION['error'] = 'Erreur lors de la création.';
                }
            } else {
                $_SESSION['error'] = implode(' ', $errors);
            }
            header('Location: index.php?page=blog_public');
            exit;
        }

        header('Location: index.php?page=blog_public');
        exit;
    }

    public function adminArticleEdit($id): void {
        $this->requireLogin();
        require_once __DIR__ . '/../models/Article.php';
        $articleModel = new Article();
        $article  = $articleModel->getById($id);
        if (!$article) { $_SESSION['error'] = 'Article non trouvé.'; header('Location: index.php?page=blog_public'); exit; }

        $userId   = $_SESSION['user_id']   ?? null;
        $userRole = $_SESSION['user_role'] ?? '';
        $isAdmin  = ($userRole === 'admin');
        $isAuthor = ($userId && isset($article['auteur_id']) && $userId == $article['auteur_id']);

        if (!$isAdmin && !$isAuthor) { $_SESSION['error'] = "Vous n'êtes pas autorisé."; header('Location: index.php?page=blog_public'); exit; }

        $errors = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $titre   = trim($_POST['titre']   ?? '');
            $contenu = trim($_POST['contenu'] ?? '');

            if (empty($titre))                $errors['titre']   = 'Le titre est obligatoire.';
            elseif (mb_strlen($titre) > 255)  $errors['titre']   = 'Le titre ne doit pas dépasser 255 caractères.';
            if (empty($contenu))              $errors['contenu'] = 'Le contenu est obligatoire.';

            $imagePath = $article['image'];
            if (isset($_POST['delete_image']) && $_POST['delete_image'] == '1') {
                if ($imagePath && file_exists(__DIR__ . '/../' . $imagePath)) unlink(__DIR__ . '/../' . $imagePath);
                $imagePath = null;
            }
            if (isset($_FILES['article_image']) && $_FILES['article_image']['error'] === UPLOAD_ERR_OK) {
                $newImage = $this->uploadArticleImage($_FILES['article_image']);
                if ($newImage) { if ($imagePath && file_exists(__DIR__ . '/../' . $imagePath)) unlink(__DIR__ . '/../' . $imagePath); $imagePath = $newImage; }
                else $errors['image'] = 'Erreur lors de l\'upload.';
            }

            if (empty($errors)) {
                $result = $articleModel->updateFull($id, $titre, $contenu, $article['auteur_id'], $imagePath, $article['categorie'], $article['tags'], $article['status']);
                if ($result) {
                    $_SESSION['success'] = 'Article modifié !';
                    $redirect = $isAdmin ? 'articles_admin' : 'blog_public';
                    header('Location: index.php?page=' . $redirect);
                    exit;
                } else $errors['general'] = 'Erreur lors de la modification.';
            }
        }

        $content = $this->getUserArticleFormHTML('Modifier mon article', 'admin_article_edit&id=' . $id, $article, $errors);
        $this->renderPublicView('Modifier mon article', $content);
    }

    public function adminArticleDelete($id): void {
        $this->requireLogin();
        require_once __DIR__ . '/../models/Article.php';
        $articleModel = new Article();
        $article  = $articleModel->getById($id);
        if (!$article) { $_SESSION['error'] = 'Article non trouvé.'; header('Location: index.php?page=blog_public'); exit; }

        $userId   = $_SESSION['user_id']   ?? null;
        $userRole = $_SESSION['user_role'] ?? '';
        $isAdmin  = ($userRole === 'admin');
        $isAuthor = ($userId && isset($article['auteur_id']) && $userId == $article['auteur_id']);

        if (!$isAdmin && !$isAuthor) { $_SESSION['error'] = 'Non autorisé.'; header('Location: index.php?page=blog_public'); exit; }

        $result = $articleModel->delete($id);
        $_SESSION[$result ? 'success' : 'error'] = $result ? 'Article supprimé !' : 'Erreur lors de la suppression.';
        header('Location: index.php?page=blog_public');
        exit;
    }

    // =============================================
    // FORMULAIRE ARTICLE (UTILISATEUR)
    // =============================================

    private function getUserArticleFormHTML($title, $action, $article = null, $errors = [], $oldData = []): string {
        $isEdit       = $article !== null;
        $titreValue   = $isEdit ? ($article['titre']   ?? '') : ($oldData['titre']   ?? '');
        $contenuValue = $isEdit ? ($article['contenu'] ?? '') : ($oldData['contenu'] ?? '');
        $imageValue   = $isEdit ? ($article['image']   ?? '') : '';
        $buttonText   = $isEdit ? 'Enregistrer' : 'Publier';
        $userName     = htmlspecialchars($_SESSION['user_name'] ?? 'Vous');
        $userInitial  = strtoupper(substr($_SESSION['user_name'] ?? 'V', 0, 1));

        $html = '
        <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">

        <!-- MODAL FACEBOOK STYLE -->
        <div style="position:fixed;inset:0;background:rgba(0,0,0,0.6);z-index:9999;display:flex;align-items:center;justify-content:center;" id="editModal" onclick="if(event.target===this)window.history.back()">
        <div style="background:white;border-radius:12px;width:95%;max-width:560px;max-height:90vh;overflow-y:auto;box-shadow:0 4px 30px rgba(0,0,0,0.3);" onclick="event.stopPropagation()">

            <!-- HEADER -->
            <div style="padding:16px;border-bottom:1px solid #e4e6eb;position:relative;text-align:center;">
                <h2 style="margin:0;font-size:20px;font-weight:700;">' . htmlspecialchars($title) . '</h2>
                <a href="index.php?page=blog_public" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:#e4e6eb;border-radius:50%;width:36px;height:36px;display:flex;align-items:center;justify-content:center;text-decoration:none;color:#333;font-size:20px;font-weight:bold;">×</a>
            </div>

            <!-- AUTEUR -->
            <div style="padding:14px 16px;display:flex;align-items:center;gap:10px;border-bottom:1px solid #f0f0f0;">
                <div style="width:42px;height:42px;border-radius:50%;background:linear-gradient(135deg,#2A7FAA,#4CAF50);display:flex;align-items:center;justify-content:center;color:white;font-weight:bold;font-size:18px;">' . $userInitial . '</div>
                <strong style="font-size:15px;color:#1c1e21;">' . $userName . '</strong>
            </div>

            ' . (isset($errors['general']) ? '<div style="background:#ffe6e6;border-left:4px solid #dc3545;padding:12px 16px;color:#dc3545;font-size:14px;">' . htmlspecialchars($errors['general']) . '</div>' : '') . '

            <!-- FORMULAIRE -->
            <form method="POST" action="index.php?page=' . $action . '" enctype="multipart/form-data" id="articleForm">

                <!-- TITRE -->
                <div style="padding:12px 16px 0;">
                    <input type="text" name="titre" id="fb_titre" value="' . htmlspecialchars($titreValue) . '"
                        placeholder="Titre de l\'article..."
                        oninput="fbCheckBtn()"
                        style="width:100%;border:none;border-bottom:2px solid #e4e6eb;outline:none;font-size:16px;font-weight:600;padding:5px 0;box-sizing:border-box;background:transparent;">
                </div>

                <!-- IMAGE COUVERTURE -->
                ' . ($imageValue ? '<div style="padding:8px 16px;"><img src="' . htmlspecialchars($imageValue) . '" style="max-width:100%;max-height:150px;object-fit:cover;border-radius:8px;"><label style="font-size:12px;color:#65676b;"><input type="checkbox" name="delete_image" value="1"> Supprimer l\'image</label></div>' : '') . '

                <!-- IMAGE PREVIEW -->
                <div id="fbImgPreview" style="display:none;padding:0 16px 10px;position:relative;">
                    <img id="fbPreviewImg" src="" style="width:100%;border-radius:8px;max-height:200px;object-fit:cover;">
                    <span onclick="fbRemoveImg()" style="position:absolute;top:5px;right:20px;background:rgba(0,0,0,0.6);color:white;border-radius:50%;width:28px;height:28px;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:16px;">×</span>
                </div>

                <!-- QUILL EDITOR -->
                <div style="padding:8px 16px;">
                    <div id="editor-container" style="border:none;min-height:150px;font-size:16px;"></div>
                    <input type="hidden" id="contenu" name="contenu" value="">
                    <style>
                    .ql-toolbar{border:none!important;border-bottom:1px solid #e4e6eb!important;padding:8px 0!important;}
                    .ql-container{border:none!important;font-size:16px!important;}
                    .ql-editor{padding:8px 0!important;min-height:120px!important;}
                    .ql-editor.ql-blank::before{color:#bbb!important;font-style:normal!important;}
                    </style>
                </div>

                <!-- EMOJI PICKER -->
                <div id="fbEmojiZone" style="display:none;padding:8px 16px;border-top:1px solid #e4e6eb;flex-wrap:wrap;gap:4px;"></div>

                <!-- BARRE OUTILS -->
                <div style="margin:0 16px 12px;border:1px solid #e4e6eb;border-radius:10px;padding:10px 16px;display:flex;justify-content:space-between;align-items:center;">
                    <span style="font-weight:600;font-size:14px;color:#1c1e21;">Ajouter à votre publication</span>
                    <div style="display:flex;gap:4px;">
                        <label style="cursor:pointer;padding:6px 10px;border-radius:8px;background:#f0f2f5;" title="Photo">
                            <i class="fas fa-image" style="font-size:20px;color:#45bd62;"></i>
                            <input type="file" name="article_image" id="fbImgInput" accept="image/*" style="display:none;" onchange="fbPreviewImg(this)">
                        </label>
                        <span onclick="fbToggleEmoji()" style="cursor:pointer;padding:6px 10px;border-radius:8px;background:#f0f2f5;" title="Emoji">
                            <i class="fas fa-smile" style="font-size:20px;color:#f7b928;"></i>
                        </span>
                    </div>
                </div>

                <!-- BOUTON PUBLIER -->
                <div style="padding:0 16px 16px;">
                    <button type="submit" id="fbPublishBtn"
                        style="width:100%;background:#e4e6eb;color:#bcc0c4;border:none;border-radius:8px;padding:11px;font-size:16px;font-weight:600;cursor:not-allowed;">
                        ' . $buttonText . '
                    </button>
                </div>
            </form>
        </div>
        </div>

        <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
        <script>
        // QUILL INIT
        var quill = new Quill("#editor-container", {
            theme: "snow",
            placeholder: "Écrivez votre article...",
            modules: { toolbar: [["bold","italic","underline","strike"],["blockquote"],[{"header":1},{"header":2}],[{"list":"ordered"},{"list":"bullet"}],["link","image"],["clean"]] }
        });

        // Charger contenu existant
        var raw = ' . json_encode($contenuValue) . ';
        if (raw && raw.trim()) {
            try {
                var p = JSON.parse(raw);
                if (p && p.ops) quill.setContents(p);
                else quill.setText(raw);
            } catch(e) { quill.setText(raw); }
        }

        quill.on("text-change", function() {
            document.getElementById("contenu").value = JSON.stringify(quill.getContents());
            fbCheckBtn();
        });

        function fbCheckBtn() {
            var t = document.getElementById("fb_titre");
            var b = document.getElementById("fbPublishBtn");
            var txt = quill.getText().trim();
            var ok = t && t.value.trim().length > 0 && txt.length > 0;
            b.disabled = !ok;
            b.style.background = ok ? "#1877f2" : "#e4e6eb";
            b.style.color = ok ? "white" : "#bcc0c4";
            b.style.cursor = ok ? "pointer" : "not-allowed";
        }
        fbCheckBtn();

        // EMOJIS
        var _emojis = ["😀","😂","😍","🥰","😎","😢","😡","👍","👎","❤️","🔥","✅","⭐","💪","🙏","🤔","😷","🏥","💊","🩺","🎉","📝","💬"];
        var _emojiInit = false;
        function fbToggleEmoji() {
            var z = document.getElementById("fbEmojiZone");
            if (!_emojiInit) {
                z.style.display = "flex";
                _emojis.forEach(function(em) {
                    var s = document.createElement("span");
                    s.textContent = em;
                    s.style.cssText = "font-size:24px;cursor:pointer;padding:4px;border-radius:6px;";
                    s.onmouseover = function(){this.style.background="#f0f2f5";};
                    s.onmouseout = function(){this.style.background="none";};
                    s.onclick = function(){
                        var idx = quill.getSelection() ? quill.getSelection().index : quill.getLength();
                        quill.insertText(idx, em);
                        quill.setSelection(idx + em.length);
                        fbCheckBtn();
                    };
                    z.appendChild(s);
                });
                _emojiInit = true;
            } else {
                z.style.display = z.style.display === "none" || z.style.display === "" ? "flex" : "none";
            }
        }

        // IMAGE PREVIEW
        function fbPreviewImg(input) {
            if (input.files && input.files[0]) {
                var r = new FileReader();
                r.onload = function(ev) {
                    document.getElementById("fbPreviewImg").src = ev.target.result;
                    document.getElementById("fbImgPreview").style.display = "block";
                };
                r.readAsDataURL(input.files[0]);
                fbCheckBtn();
            }
        }
        function fbRemoveImg() {
            document.getElementById("fbImgInput").value = "";
            document.getElementById("fbImgPreview").style.display = "none";
            document.getElementById("fbPreviewImg").src = "";
        }

        // SUBMIT
        document.getElementById("articleForm").onsubmit = function() {
            document.getElementById("contenu").value = JSON.stringify(quill.getContents());
        };
        </script>';
        return $html;
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
        $medecinId = (int)$_SESSION['user_id'];
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM disponibilites WHERE medecin_id = :medecin_id ORDER BY jour_semaine ASC, heure_debut ASC");
        $stmt->execute([':medecin_id' => $medecinId]);
        $dispos = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $content = $this->getMedecinDisponibilitesHTML($dispos);
        $this->renderPublicView('Mes disponibilités', $content);
    }

    public function medecinStoreDisponibilite(): void {
        $this->requireLogin();
        $this->requireMedecin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: index.php?page=medecin_disponibilites'); exit; }
        require_once __DIR__ . '/../models/Disponibilite.php';
        $data = ['medecin_id' => (int)$_SESSION['user_id'], 'jour_semaine' => $_POST['jour_semaine'], 'heure_debut' => $_POST['heure_debut'], 'heure_fin' => $_POST['heure_fin'], 'actif' => 1];
        $disponibiliteModel = new Disponibilite();
        $result = $disponibiliteModel->create($data);
        $_SESSION['flash'] = $result ? ['type' => 'success', 'message' => 'Disponibilité ajoutée.'] : ['type' => 'error', 'message' => 'Erreur.'];
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
            $disponibiliteModel->update($id, ['actif' => $dispo['actif'] ? 0 : 1]);
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
    // ÉVÉNEMENTS
    // =============================================

    public function listeEvenements(): void {
        require_once __DIR__ . '/../models/Event.php';
        $eventModel     = new Event();
        $upcomingEvents = $eventModel->getUpcoming();
        $isLoggedIn     = isset($_SESSION['user_id']);

        ob_start();
        ?>
        <style>
            .event-card { background:white;border-radius:16px;margin-bottom:25px;box-shadow:0 5px 15px rgba(0,0,0,0.08);transition:transform 0.3s;overflow:hidden;position:relative; }
            .event-card:hover { transform:translateY(-5px); }
            .event-image { height:200px;background-size:cover;background-position:center;background-color:#e9ecef; }
            .event-body { padding:20px; }
            .event-title { font-size:1.25rem;font-weight:700;margin-bottom:8px; }
            .event-title a { color:#1a2035;text-decoration:none; }
            .event-title a:hover { color:#2A7FAA; }
            .event-meta { font-size:13px;color:#6c757d;margin-bottom:8px;display:flex;align-items:center;gap:10px; }
            .event-footer { display:flex;justify-content:space-between;align-items:center;padding-top:15px;border-top:1px solid #eee; }
            .event-price { font-size:18px;font-weight:bold;color:#2A7FAA; }
            .btn-register { background:linear-gradient(135deg,#2A7FAA,#4CAF50);color:white;border:none;border-radius:25px;padding:8px 20px;font-size:13px;text-decoration:none;display:inline-block; }
        </style>
        <div style="background:linear-gradient(135deg,#2A7FAA,#4CAF50);color:white;padding:60px 0;text-align:center;margin-bottom:40px;border-radius:12px;">
            <h1 style="font-size:2.5rem;"><i class="fas fa-calendar-alt me-3"></i>Événements médicaux</h1>
            <p style="font-size:1.2rem;opacity:0.9;">Conférences, ateliers et rencontres médicales</p>
        </div>
        <div class="row" id="eventsList">
        <?php if (empty($upcomingEvents)): ?>
        <div class="col-12"><div style="text-align:center;padding:40px;color:#6c757d;"><i class="fas fa-calendar-check fa-3x mb-3"></i><p>Aucun événement disponible.</p></div></div>
        <?php else: ?>
        <?php foreach ($upcomingEvents as $event):
            $prix = $event['prix'] ?? 0;
            $prixText = $prix > 0 ? $prix . ' DT' : 'GRATUIT';
            $image = htmlspecialchars($event['image'] ?? 'https://via.placeholder.com/400x200?text=Event');
        ?>
        <div class="col-md-6 col-lg-4">
            <div class="event-card">
                <div class="event-image" style="background-image:url('<?= $image ?>')"></div>
                <div class="event-body">
                    <h3 class="event-title"><a href="index.php?page=detail_evenement&slug=<?= htmlspecialchars($event['slug']) ?>"><?= htmlspecialchars($event['titre']) ?></a></h3>
                    <div class="event-meta"><i class="fas fa-calendar"></i><?= date('d/m/Y', strtotime($event['date_debut'])) ?></div>
                    <div class="event-meta"><i class="fas fa-map-marker-alt"></i><?= htmlspecialchars($event['lieu'] ?? 'À déterminer') ?></div>
                    <div class="event-footer">
                        <div class="event-price"><?= $prixText ?></div>
                        <a href="index.php?page=detail_evenement&slug=<?= htmlspecialchars($event['slug']) ?>" class="btn-register"><i class="fas fa-info-circle"></i> Détails</a>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
        </div>
        <?php
        $content = ob_get_clean();
        $this->renderPublicView('Événements', $content);
    }

    public function detailEvenement($id = null): void {
        require_once __DIR__ . '/../models/Event.php';
        $eventModel = new Event();
        $slug  = isset($_GET['slug']) ? preg_replace('/[^a-z0-9-]/', '', trim($_GET['slug'])) : null;
        $event = $slug ? $eventModel->getBySlug($slug) : ($id ? $eventModel->getById($id) : null);
        if (!$event) { $this->page404(); return; }
        $isLoggedIn = isset($_SESSION['user_id']);
        $prix       = $event['prix'] ?? 0;
        $prixText   = $prix > 0 ? number_format($prix, 2, ',', ' ') . ' DT' : 'GRATUIT';

        ob_start();
        ?>
        <div style="background:linear-gradient(135deg,#2A7FAA,#4CAF50);color:white;padding:40px;margin-bottom:30px;border-radius:12px;">
            <a href="index.php?page=evenements" style="color:white;text-decoration:none;"><i class="fas fa-arrow-left me-2"></i>Retour</a>
            <h1 style="font-size:2.5rem;margin-top:15px;"><?= htmlspecialchars($event['titre']) ?></h1>
            <p><?= date('d F Y', strtotime($event['date_debut'])) ?></p>
        </div>
        <div class="row">
            <div class="col-lg-8">
                <?php if (!empty($event['image'])): ?>
                <img src="<?= htmlspecialchars($event['image']) ?>" style="width:100%;max-height:400px;object-fit:cover;border-radius:12px;margin-bottom:30px;">
                <?php endif; ?>
                <div style="background:white;border-radius:12px;padding:30px;margin-bottom:30px;box-shadow:0 2px 10px rgba(0,0,0,0.08);">
                    <h3 style="color:#2A7FAA;margin-bottom:15px;">Description</h3>
                    <?= nl2br(htmlspecialchars($event['description'] ?? 'Aucune description.')) ?>
                </div>
            </div>
            <div class="col-lg-4">
                <div style="background:linear-gradient(135deg,#2A7FAA,#4CAF50);color:white;border-radius:12px;padding:25px;text-align:center;">
                    <div>Tarif</div>
                    <div style="font-size:36px;font-weight:bold;margin-bottom:10px;"><?= $prixText ?></div>
                    <button onclick="<?= $isLoggedIn ? 'registerForEvent(' . (int)$event['id'] . ')' : "window.location.href='index.php?page=login'" ?>" style="background:white;color:#2A7FAA;border:none;border-radius:25px;padding:12px 35px;font-size:16px;font-weight:600;cursor:pointer;margin-top:15px;">
                        <?= $isLoggedIn ? "S'inscrire" : 'Se connecter' ?>
                    </button>
                </div>
            </div>
        </div>
        <script>
        function registerForEvent(eventId) {
            fetch("index.php?page=event_register", { method:"POST", headers:{"Content-Type":"application/x-www-form-urlencoded"}, credentials:"include", body:"event_id="+eventId })
            .then(r=>r.json()).then(d=>{ if(d.success) alert("Inscription confirmée!"); else alert("Erreur: "+(d.message||"Impossible")); });
        }
        </script>
        <?php
        $content = ob_get_clean();
        $this->renderPublicView(htmlspecialchars($event['titre']), $content);
    }

    public function registerEventAction(): void {
        header('Content-Type: application/json');
        if (empty($_SESSION['user_id'])) { echo json_encode(['success' => false, 'message' => 'Veuillez vous connecter.']); exit; }
        $eventId = (int)($_POST['event_id'] ?? 0);
        $userId  = $_SESSION['user_id'];
        if (!$eventId) { echo json_encode(['success' => false, 'message' => 'ID invalide.']); exit; }
        require_once __DIR__ . '/../models/Event.php';
        require_once __DIR__ . '/../models/Participation.php';
        $eventModel         = new Event();
        $participationModel = new Participation();
        $event = $eventModel->getById($eventId);
        if (!$event) { echo json_encode(['success' => false, 'message' => 'Événement non trouvé.']); exit; }
        if ($participationModel->checkUserEvent($userId, $eventId)) { echo json_encode(['success' => false, 'message' => 'Déjà inscrit.']); exit; }
        $result = $participationModel->create(['event_id' => $eventId, 'user_id' => $userId, 'statut' => 'inscrit']);
        echo json_encode($result ? ['success' => true, 'message' => 'Inscription confirmée!'] : ['success' => false, 'message' => 'Erreur.']);
        exit;
    }

    public function listSponsors(): void {
        require_once __DIR__ . '/../models/Sponsor.php';
        $sponsorModel = new Sponsor();
        $sponsors = $sponsorModel->getAll(0, 100, 'actif');
        $levelMap = ['platinium' => 'Platine', 'gold' => 'Or', 'silver' => 'Argent', 'bronze' => 'Bronze'];
        $sponsorsByLevel = [];
        foreach ($sponsors as $sponsor) {
            $levelKey = $levelMap[$sponsor['niveau']] ?? 'autre';
            $sponsorsByLevel[$levelKey][] = $sponsor;
        }

        ob_start();
        ?>
        <div style="background:linear-gradient(135deg,#2A7FAA,#4CAF50);color:white;padding:60px 0;text-align:center;margin-bottom:40px;border-radius:12px;">
            <h1><i class="fas fa-handshake me-3"></i>Nos Sponsors</h1>
            <p style="font-size:1.1rem;opacity:0.9;">Partenaires qui soutiennent nos événements</p>
        </div>
        <?php if (empty($sponsors)): ?>
        <div class="alert alert-info text-center" style="padding:40px;"><p>Aucun sponsor disponible.</p></div>
        <?php else: ?>
        <?php foreach (['Platine','Or','Argent','Bronze'] as $level):
            if (empty($sponsorsByLevel[$level])) continue; ?>
        <h3 style="font-size:1.8rem;font-weight:700;margin-top:40px;margin-bottom:30px;padding-bottom:15px;border-bottom:3px solid #2A7FAA;"><?= $level ?></h3>
        <div class="row">
            <?php foreach ($sponsorsByLevel[$level] as $sponsor): ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div style="background:white;border-radius:12px;padding:30px 20px;box-shadow:0 2px 10px rgba(0,0,0,0.08);text-align:center;">
                    <div style="font-size:18px;font-weight:700;margin-bottom:10px;"><?= htmlspecialchars($sponsor['nom']) ?></div>
                    <?php if (!empty($sponsor['email'])): ?><div style="font-size:13px;color:#2A7FAA;"><?= htmlspecialchars($sponsor['email']) ?></div><?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
        <?php
        $content = ob_get_clean();
        $this->renderPublicView('Nos Sponsors', $content);
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
        $this->renderTemporaryView('À propos', '<h3>Valorys - Votre plateforme médicale</h3><p>Valorys vous permet de prendre rendez-vous avec des médecins qualifiés facilement.</p>');
    }

    // =============================================
    // RENDEZ-VOUS
    // =============================================

    public function prendreRendezVous($id = null): void {
        $this->requireLogin();
        require_once __DIR__ . '/../models/Medecin.php';
        $medecinModel = new Medecin();
        $medecins  = $medecinModel->getAllWithUsers();
        $errors    = $_SESSION['errors'] ?? [];
        $old       = $_SESSION['old']    ?? [];
        $success   = $_SESSION['success'] ?? null;
        $error     = $_SESSION['error']   ?? null;
        unset($_SESSION['errors'], $_SESSION['old'], $_SESSION['success'], $_SESSION['error']);
        $selectedMedecinId = $id ?? ($old['medecin_id'] ?? null);

        ob_start();
        ?>
        <div style="background:white;border-radius:12px;padding:30px;box-shadow:0 2px 8px rgba(0,0,0,0.1);">
            <h2 style="margin-bottom:25px;color:#2A7FAA;"><i class="fas fa-calendar-plus me-2"></i>Prendre rendez-vous</h2>
            <?php if ($success): ?><div style="background:#d4edda;color:#155724;padding:12px 20px;border-radius:8px;margin-bottom:20px;"><?= htmlspecialchars($success) ?></div><?php endif; ?>
            <?php if ($error): ?><div style="background:#f8d7da;color:#721c24;padding:12px 20px;border-radius:8px;margin-bottom:20px;"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <form method="POST" action="index.php?page=prendre_rendez_vous">
                <div style="margin-bottom:20px;">
                    <label style="display:block;font-weight:bold;margin-bottom:8px;">Médecin *</label>
                    <select name="medecin_id" style="width:100%;padding:10px;border:1px solid #ddd;border-radius:8px;">
                        <option value="">-- Sélectionner un médecin --</option>
                        <?php foreach ($medecins as $medecin):
                            $selected = ($selectedMedecinId && $selectedMedecinId == $medecin['user_id']) ? 'selected' : ''; ?>
                        <option value="<?= $medecin['user_id'] ?>" <?= $selected ?>>Dr. <?= htmlspecialchars($medecin['prenom'] . ' ' . $medecin['nom']) ?> - <?= htmlspecialchars($medecin['specialite'] ?? 'Généraliste') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="display:flex;gap:20px;flex-wrap:wrap;">
                    <div style="flex:1;min-width:200px;">
                        <label style="display:block;font-weight:bold;margin-bottom:8px;">Date *</label>
                        <input type="date" name="date_rendezvous" min="<?= date('Y-m-d') ?>" value="<?= htmlspecialchars($old['date_rendezvous'] ?? '') ?>" style="width:100%;padding:10px;border:1px solid #ddd;border-radius:8px;">
                    </div>
                    <div style="flex:1;min-width:200px;">
                        <label style="display:block;font-weight:bold;margin-bottom:8px;">Heure *</label>
                        <input type="time" name="heure_rendezvous" value="<?= htmlspecialchars($old['heure_rendezvous'] ?? '') ?>" style="width:100%;padding:10px;border:1px solid #ddd;border-radius:8px;">
                    </div>
                </div>
                <div style="margin-top:20px;margin-bottom:20px;">
                    <label style="display:block;font-weight:bold;margin-bottom:8px;">Motif</label>
                    <textarea name="motif" rows="4" style="width:100%;padding:10px;border:1px solid #ddd;border-radius:8px;" placeholder="Décrivez le motif..."><?= htmlspecialchars($old['motif'] ?? '') ?></textarea>
                </div>
                <div style="display:flex;justify-content:flex-end;gap:15px;">
                    <a href="index.php?page=mes_rendez_vous" style="background:#6c757d;color:white;padding:12px 30px;border-radius:25px;text-decoration:none;">Annuler</a>
                    <button type="submit" name="submit_rdv" style="background:linear-gradient(135deg,#2A7FAA,#4CAF50);color:white;border:none;padding:12px 30px;border-radius:25px;cursor:pointer;font-weight:bold;"><i class="fas fa-calendar-check me-2"></i>Confirmer</button>
                </div>
            </form>
        </div>
        <?php
        $content = ob_get_clean();
        $this->renderPublicView('Prendre rendez-vous', $content);
    }

    public function mesRendezVous(): void {
        $this->requireLogin();
        $userRole   = $_SESSION['user_role'] ?? '';
        $userId     = (int)$_SESSION['user_id'];
        $db         = Database::getInstance()->getConnection();
        $rendezVous = [];

        if ($userRole === 'medecin') {
            $stmt = $db->prepare("SELECT * FROM rendez_vous WHERE medecin_id = :id ORDER BY date_rendezvous DESC");
            $stmt->execute([':id' => $userId]);
            $title = 'Mes consultations';
        } elseif ($userRole === 'patient') {
            $stmt = $db->prepare("SELECT * FROM rendez_vous WHERE patient_id = :id ORDER BY date_rendezvous DESC");
            $stmt->execute([':id' => $userId]);
            $title = 'Mes rendez-vous';
        } else {
            $this->page403(); return;
        }
        $rendezVous = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $content = $this->getRendezVousHTML($rendezVous, $userRole, $title);
        $this->renderPublicView($title, $content);
    }

    private function getRendezVousHTML($rendezVous, $userRole, $title): string {
        $isMedecin = ($userRole === 'medecin');
        $html = '<style>
            .rdv-card { background:white;border-radius:12px;padding:20px;margin-bottom:20px;box-shadow:0 2px 8px rgba(0,0,0,0.08);border-left:4px solid #2A7FAA; }
            .badge-confirme { background:#d4edda;color:#155724;padding:5px 12px;border-radius:20px;font-size:12px; }
            .badge-attente { background:#fff3cd;color:#856404;padding:5px 12px;border-radius:20px;font-size:12px; }
            .badge-termine { background:#cfe2ff;color:#084298;padding:5px 12px;border-radius:20px;font-size:12px; }
            .badge-annule { background:#f8d7da;color:#721c24;padding:5px 12px;border-radius:20px;font-size:12px; }
            .btn-action { padding:5px 15px;border-radius:20px;font-size:13px;margin-right:8px;text-decoration:none;display:inline-block; }
        </style>';

        if (empty($rendezVous)) {
            $html .= '<div style="text-align:center;padding:50px;background:white;border-radius:12px;"><i class="fas fa-calendar-times fa-3x text-muted mb-3"></i><h4>Aucun rendez-vous</h4>';
            if (!$isMedecin) $html .= '<a href="index.php?page=prendre_rendez_vous" class="btn btn-primary mt-3"><i class="fas fa-calendar-plus me-2"></i>Prendre un rendez-vous</a>';
            $html .= '</div>';
        } else {
            foreach ($rendezVous as $rdv) {
                $badgeClass = match($rdv['statut']) { 'confirmé' => 'badge-confirme', 'terminé' => 'badge-termine', 'annulé' => 'badge-annule', default => 'badge-attente' };
                $html .= '
                <div class="rdv-card">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:15px;">
                        <span style="font-size:1.1rem;font-weight:bold;color:#2A7FAA;"><i class="fas fa-user-md me-2"></i>Rendez-vous #' . $rdv['id'] . '</span>
                        <span class="' . $badgeClass . '">' . ucfirst($rdv['statut']) . '</span>
                    </div>
                    <div style="display:flex;gap:20px;flex-wrap:wrap;margin-bottom:15px;">
                        <span><i class="fas fa-calendar"></i> ' . date('d/m/Y', strtotime($rdv['date_rendezvous'])) . '</span>
                        <span><i class="fas fa-clock"></i> ' . $rdv['heure_rendezvous'] . '</span>
                    </div>
                    ' . (!empty($rdv['motif']) ? '<div style="background:#f8f9fa;padding:10px;border-radius:8px;margin-bottom:15px;font-size:14px;">' . nl2br(htmlspecialchars($rdv['motif'])) . '</div>' : '') . '
                    <div style="display:flex;gap:8px;flex-wrap:wrap;">
                        <a href="index.php?page=detail_rendez_vous&id=' . $rdv['id'] . '" style="background:#6c757d;color:white;" class="btn-action">Détails</a>
                        ' . ($rdv['statut'] !== 'annulé' && $rdv['statut'] !== 'terminé' ? '<a href="index.php?page=annuler_rendez_vous&id=' . $rdv['id'] . '" style="background:#dc3545;color:white;" class="btn-action" onclick="return confirm(\'Annuler ?\')">Annuler</a>' : '') . '
                        ' . ($isMedecin && $rdv['statut'] === 'en_attente' ? '<a href="index.php?page=confirmer_rendez_vous&id=' . $rdv['id'] . '" style="background:#28a745;color:white;" class="btn-action" onclick="return confirm(\'Confirmer ?\')">Confirmer</a>' : '') . '
                    </div>
                </div>';
            }
        }
        return $html;
    }

    public function annulerRendezVous($id): void { $this->requireLogin(); $this->renderTemporaryView('Annuler rendez-vous', '<p>Rendez-vous #' . htmlspecialchars($id) . ' annulé</p>'); }
    public function confirmerRendezVous($id): void { $this->requireLogin(); $this->renderTemporaryView('Confirmer rendez-vous', '<p>Rendez-vous #' . htmlspecialchars($id) . ' confirmé</p>'); }
    public function mesOrdonnances(): void { $this->requireLogin(); $this->renderTemporaryView('Mes ordonnances', '<p>Liste de vos ordonnances</p>'); }
    public function mesNotifications(): void { $this->requireLogin(); $this->renderTemporaryView('Mes notifications', '<p>Aucune notification</p>'); }

    // =============================================
    // PROFIL
    // =============================================

    public function modifierProfil(): void {
        $this->requireLogin();
        $userName      = htmlspecialchars($_SESSION['user_name']      ?? '');
        $userEmail     = htmlspecialchars($_SESSION['user_email']     ?? '');
        $userTelephone = htmlspecialchars($_SESSION['user_telephone'] ?? '');

        $content = '
        <div style="background:white;border-radius:20px;padding:35px;box-shadow:0 10px 30px rgba(0,0,0,0.05);margin-bottom:25px;">
            <h4 style="color:#2c3e50;font-weight:700;margin-bottom:25px;"><i style="background:rgba(42,127,170,0.1);color:#2A7FAA;padding:12px;border-radius:12px;" class="fas fa-user-edit me-2"></i> Informations personnelles</h4>
            <form method="POST">
                <div class="row g-3">
                    <div class="col-md-12 mb-3"><label style="font-weight:600;display:block;margin-bottom:8px;">Nom complet</label><input type="text" class="form-control" name="nom" value="' . $userName . '" required></div>
                    <div class="col-md-6 mb-3"><label style="font-weight:600;display:block;margin-bottom:8px;">Email</label><input type="email" class="form-control" name="email" value="' . $userEmail . '" required></div>
                    <div class="col-md-6 mb-3"><label style="font-weight:600;display:block;margin-bottom:8px;">Téléphone</label><input type="tel" class="form-control" name="telephone" value="' . $userTelephone . '"></div>
                </div>
                <div style="display:flex;justify-content:flex-end;gap:15px;margin-top:20px;">
                    <a href="index.php?page=mon_profil" class="btn btn-secondary">Annuler</a>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Enregistrer</button>
                </div>
            </form>
        </div>';
        $this->renderTemporaryView('Modifier mon profil', $content);
    }

    public function monProfil(): void {
        $this->requireLogin();
        require_once __DIR__ . '/../models/User.php';
        $userModel = new User();
        $user = $userModel->getUserById($_SESSION['user_id']);
        include __DIR__ . '/../views/frontoffice/profil.php';
    }

    // =============================================
    // PAGES D'ERREUR
    // =============================================

    public function page404(): void {
        http_response_code(404);
        $content = '<div class="text-center py-5"><i class="fas fa-exclamation-triangle fa-4x text-warning mb-3 d-block"></i><h1 class="display-1 text-danger fw-bold">404</h1><h2 class="mb-3">Page non trouvée</h2><a href="index.php?page=accueil" class="btn btn-primary btn-lg"><i class="fas fa-home"></i> Retour</a></div>';
        $this->renderErrorView('Erreur 404', $content);
    }

    public function page403(): void {
        http_response_code(403);
        $content = '<div class="text-center py-5"><i class="fas fa-lock fa-4x text-danger mb-3 d-block"></i><h1 class="display-1 text-danger fw-bold">403</h1><h2 class="mb-3">Accès refusé</h2><a href="index.php?page=accueil" class="btn btn-primary btn-lg"><i class="fas fa-home"></i> Retour</a></div>';
        $this->renderErrorView('Erreur 403', $content);
    }

    // =============================================
    // VUES
    // =============================================

    private function renderPublicView($title, $content): void { ?>
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?= htmlspecialchars($title) ?> - Valorys</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
            <?= $this->getCustomStyles() ?>
            <script>
            function toggleLikeArticle(articleId, type) {
                var xhr = new XMLHttpRequest();
                xhr.open("POST", "index.php?page=api_article_like", true);
                xhr.setRequestHeader("Content-Type", "application/json");
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4 && xhr.status === 200) {
                        try {
                            var d = JSON.parse(xhr.responseText);
                            if (d.success) {
                                var lb = document.getElementById("like-art-" + articleId);
                                var db = document.getElementById("dislike-art-" + articleId);
                                if (lb) { lb.innerHTML = "👍 J'aime (" + d.likes + ")"; lb.style.color = (type==="like" && d.action!=="removed") ? "#1877f2" : "#65676b"; }
                                if (db) { db.innerHTML = "👎 Je n'aime pas (" + d.dislikes + ")"; db.style.color = (type==="dislike" && d.action!=="removed") ? "#dc3545" : "#65676b"; }
                            }
                        } catch(e) {}
                    }
                };
                xhr.send(JSON.stringify({article_id: parseInt(articleId), type: type}));
            }
            function toggleLikeReply(replyId, type) {
                var xhr = new XMLHttpRequest();
                xhr.open("POST", "index.php?page=api_reply_like", true);
                xhr.setRequestHeader("Content-Type", "application/json");
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4 && xhr.status === 200) {
                        try {
                            var d = JSON.parse(xhr.responseText);
                            if (d.success) {
                                var le = document.getElementById("rl-like-" + replyId);
                                var de = document.getElementById("rl-dislike-" + replyId);
                                if (le) le.textContent = "👍 " + d.likes;
                                if (de) de.textContent = "👎 " + d.dislikes;
                            }
                        } catch(e) {}
                    }
                };
                xhr.send(JSON.stringify({reply_id: parseInt(replyId), type: type}));
            }
            function checkFbBtn() {
                var t = document.getElementById("fb_titre");
                var c = document.getElementById("fb_contenu");
                var i = document.getElementById("fbImgInput");
                var b = document.getElementById("fbPublishBtn");
                if (!b) return;
                var ok = t && t.value.trim() && (c && c.value.trim() || i && i.files && i.files.length > 0);
                b.disabled = !ok;
                b.style.background = ok ? "#1877f2" : "#e4e6eb";
                b.style.color = ok ? "white" : "#bcc0c4";
                b.style.cursor = ok ? "pointer" : "not-allowed";
            }
            </script>
            <script>
            // Stubs globaux — seront surchargés par getFbModalScript si la modal est présente
            function openArticleModal(type) {
                var modal = document.getElementById("articleModal");
                if (modal) {
                    modal.style.display = "flex";
                    document.body.style.overflow = "hidden";
                    if (type === "image") {
                        var inp = document.getElementById("fbImgInput");
                        if (inp) inp.click();
                    }
                    if (type === "emoji") {
                        if (typeof toggleFbEmoji === "function") toggleFbEmoji();
                    }
                    setTimeout(function() {
                        var ta = document.getElementById("fb_contenu");
                        if (ta) ta.focus();
                    }, 100);
                }
            }
            function closeArticleModal() {
                var modal = document.getElementById("articleModal");
                if (modal) modal.style.display = "none";
                document.body.style.overflow = "auto";
            }
            function checkFbBtn() {
                var t = document.getElementById("fb_titre");
                var c = document.getElementById("fb_contenu");
                var i = document.getElementById("fbImgInput");
                var b = document.getElementById("fbPublishBtn");
                if (!b) return;
                var hasTitle   = t && t.value.trim().length > 0;
                var hasContent = c && c.value.trim().length > 0;
                var hasFile    = i && i.files && i.files.length > 0;
                if (hasTitle && (hasContent || hasFile)) {
                    b.style.cssText = "width:100%;background:#1877f2;color:white;border:none;border-radius:8px;padding:10px;font-size:17px;font-weight:600;cursor:pointer;font-family:inherit;";
                    b.disabled = false;
                } else {
                    b.style.cssText = "width:100%;background:#e4e6eb;color:#bcc0c4;border:none;border-radius:8px;padding:10px;font-size:17px;font-weight:600;cursor:not-allowed;font-family:inherit;";
                    b.disabled = true;
                }
            }
            </script>
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
    <?php }

    private function renderTemporaryView($title, $content): void { ?>
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
    <?php }

    private function renderAdminLayout($title, $content, $activePage = 'articles'): void { ?>
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?= htmlspecialchars($title) ?> - Valorys Admin</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
            <style>
                body { background:#f4f6f9;font-family:'Segoe UI',sans-serif; }
                .sidebar { background:#2c3e50;min-height:100vh;color:white;box-shadow:2px 0 10px rgba(0,0,0,0.1); }
                .sidebar .nav-link { color:rgba(255,255,255,0.8);padding:12px 20px;transition:all 0.3s;border-radius:8px;margin:4px 10px; }
                .sidebar .nav-link:hover { background:rgba(255,255,255,0.1);color:white; }
                .sidebar .nav-link.active { background:#2A7FAA;color:white; }
                .sidebar .nav-link i { margin-right:10px;width:20px;text-align:center; }
                .sidebar .navbar-brand { padding:20px 15px;font-size:1.3rem;font-weight:bold;border-bottom:1px solid rgba(255,255,255,0.1);margin-bottom:15px; }
                .main-content { padding:20px; }
                .top-bar { background:white;border-radius:10px;padding:15px 20px;margin-bottom:20px;box-shadow:0 1px 3px rgba(0,0,0,0.1); }
            </style>
        </head>
        <body>
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-2 px-0 sidebar">
                        <div class="navbar-brand text-center"><i class="fas fa-hospital-user me-2"></i> Valorys Admin</div>
                        <nav class="nav flex-column">
                            <a class="nav-link <?= $activePage=='dashboard'?'active':'' ?>" href="index.php?page=dashboard"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                            <a class="nav-link <?= $activePage=='users'?'active':'' ?>" href="index.php?page=users"><i class="fas fa-users"></i> Utilisateurs</a>
                            <a class="nav-link <?= $activePage=='medecins'?'active':'' ?>" href="index.php?page=medecins_admin"><i class="fas fa-user-md"></i> Médecins</a>
                            <a class="nav-link <?= $activePage=='rendezvous'?'active':'' ?>" href="index.php?page=rendez_vous_admin"><i class="fas fa-calendar-check"></i> Rendez-vous</a>
                            <a class="nav-link <?= $activePage=='articles'?'active':'' ?>" href="index.php?page=articles_admin"><i class="fas fa-newspaper"></i> Articles</a>
                            <a class="nav-link <?= $activePage=='evenements'?'active':'' ?>" href="index.php?page=evenements_admin"><i class="fas fa-calendar-alt"></i> Événements</a>
                            <a class="nav-link <?= $activePage=='produits'?'active':'' ?>" href="index.php?page=produits_admin"><i class="fas fa-box"></i> Produits</a>
                            <hr class="mx-3 my-2" style="border-color:rgba(255,255,255,0.1);">
                            <a class="nav-link" href="index.php?page=accueil"><i class="fas fa-home"></i> Voir le site</a>
                            <a class="nav-link text-danger" href="index.php?page=logout"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
                        </nav>
                    </div>
                    <div class="col-md-10 main-content">
                        <div class="top-bar d-flex justify-content-between align-items-center">
                            <h4 class="mb-0"><?= htmlspecialchars($title) ?></h4>
                            <div><span class="me-3"><i class="fas fa-user-circle"></i> <?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin') ?></span></div>
                        </div>
                        <?= $this->getFlashMessages() ?>
                        <?= $content ?>
                    </div>
                </div>
            </div>
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        </body>
        </html>
    <?php }

    private function renderErrorView($title, $content): void { ?>
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <title><?= htmlspecialchars($title) ?> - Valorys</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        </head>
        <body class="bg-light">
            <div class="container mt-5"><div class="row justify-content-center"><div class="col-md-6"><div class="card shadow"><div class="card-body text-center py-5"><?= $content ?></div></div></div></div></div>
        </body>
        </html>
    <?php }

    private function getFlashMessages(): string {
        $html = '';
        if (isset($_SESSION['success'])) { $html .= '<div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle"></i> ' . htmlspecialchars($_SESSION['success']) . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>'; unset($_SESSION['success']); }
        if (isset($_SESSION['error']))   { $html .= '<div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-circle"></i> ' . htmlspecialchars($_SESSION['error']) . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>'; unset($_SESSION['error']); }
        return $html;
    }

    private function getCustomStyles(): string {
        return '<style>
            :root { --primary:#2A7FAA;--secondary:#4CAF50;--bg-light:#f0f6ff;--border:#d0e4f7;--shadow:0 4px 12px rgba(42,127,170,0.15); }
            body { font-family:"Segoe UI",sans-serif;background:var(--bg-light); }
            .navbar-custom { background:linear-gradient(135deg,var(--primary) 0%,var(--secondary) 100%);box-shadow:var(--shadow);padding:0.8rem 2rem; }
            .card { border:1px solid var(--border);border-radius:15px; }
            .card-header { background:linear-gradient(135deg,#e0f0f5 0%,rgba(76,175,80,0.1) 100%);border-bottom:2px solid var(--border); }
            .btn-primary { background:linear-gradient(135deg,var(--primary) 0%,var(--secondary) 100%);border:none;border-radius:10px; }
            .avatar { width:48px;height:48px;border-radius:50%;background:linear-gradient(135deg,var(--primary) 0%,var(--secondary) 100%);display:flex;align-items:center;justify-content:center;color:white;font-weight:bold;font-size:1.2rem; }
        </style>';
    }

    private function getPublicNavbar(): string {
        $isLoggedIn = !empty($_SESSION['user_id']);
        $userName   = htmlspecialchars($_SESSION['user_name'] ?? 'Compte');
        $userRole   = $_SESSION['user_role'] ?? 'guest';
        $rightLinks = $isLoggedIn
            ? '<li class="nav-item dropdown">
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
            : '<li class="nav-item"><a class="nav-link" href="index.php?page=login"><i class="fas fa-sign-in-alt me-1"></i>Connexion</a></li>
               <li class="nav-item"><a class="nav-link btn btn-light ms-2" href="index.php?page=register"><i class="fas fa-user-plus me-1"></i>Inscription</a></li>';

        return '<nav class="navbar navbar-expand-lg navbar-dark navbar-custom sticky-top">
            <div class="container">
                <a class="navbar-brand fw-bold" href="index.php?page=accueil"><i class="fas fa-hospital-user"></i> Valorys</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"><span class="navbar-toggler-icon"></span></button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav mx-auto">
                        <li class="nav-item"><a class="nav-link" href="index.php?page=accueil"><i class="fas fa-home me-1"></i>Accueil</a></li>
                        <li class="nav-item"><a class="nav-link" href="index.php?page=medecins"><i class="fas fa-user-md me-1"></i>Médecins</a></li>
                        <li class="nav-item"><a class="nav-link" href="index.php?page=metiers"><i class="fas fa-briefcase-medical me-1"></i>Métiers</a></li>
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
        return '<footer class="mt-5 py-4 bg-dark text-white text-center"><div class="container"><p class="mb-0">&copy; 2024 Valorys - Tous droits réservés</p><small class="text-muted">Plateforme médicale en ligne</small></div></footer>';
    }

    private function getPublicDashboardHTML(): string {
        $isLoggedIn = !empty($_SESSION['user_id']);
        $userRole   = $_SESSION['user_role'] ?? '';
        $userName   = htmlspecialchars($_SESSION['user_name'] ?? 'Utilisateur');

        if ($isLoggedIn) {
            $roleContent = '';
            if ($userRole === 'admin') $roleContent .= '<div class="col-md-3"><div class="card h-100 text-center p-3"><div class="card-body"><i class="fas fa-tachometer-alt fa-3x text-danger mb-3"></i><h5>Administration</h5><a href="index.php?page=dashboard" class="btn btn-danger btn-sm">Tableau de bord</a></div></div></div>';
            if (in_array($userRole, ['patient','admin'])) $roleContent .= '<div class="col-md-3"><div class="card h-100 text-center p-3"><div class="card-body"><i class="fas fa-calendar-check fa-3x text-primary mb-3"></i><h5>Mes rendez-vous</h5><a href="index.php?page=mes_rendez_vous" class="btn btn-primary btn-sm">Voir</a></div></div></div>';
            if ($userRole === 'medecin') $roleContent .= '<div class="col-md-3"><div class="card h-100 text-center p-3"><div class="card-body"><i class="fas fa-calendar-alt fa-3x text-primary mb-3"></i><h5>Mes rendez-vous</h5><a href="index.php?page=mes_rendez_vous" class="btn btn-primary btn-sm">Voir</a></div></div></div>';
            $roleContent .= '<div class="col-md-3"><div class="card h-100 text-center p-3"><div class="card-body"><i class="fas fa-blog fa-3x text-warning mb-3"></i><h5>Blog médical</h5><a href="index.php?page=blog_public" class="btn btn-warning btn-sm">Lire le blog</a></div></div></div>';
            $roleContent .= '<div class="col-md-3"><div class="card h-100 text-center p-3"><div class="card-body"><i class="fas fa-user-circle fa-3x text-secondary mb-3"></i><h5>Mon profil</h5><a href="index.php?page=mon_profil" class="btn btn-secondary btn-sm">Mon profil</a></div></div></div>';
            return '<div class="text-center mb-5"><h1 class="display-4 mb-3">Bonjour, ' . $userName . ' !</h1><p class="lead text-muted">Bienvenue sur votre espace Valorys</p></div><div class="row g-4 mb-5">' . $roleContent . '</div>';
        }

        return '<div class="text-center mb-5"><h1 class="display-4 mb-3">Bienvenue sur Valorys!</h1><p class="lead text-muted">Connectez-vous pour accéder à tous nos services</p></div>
        <div class="row g-4 mb-5">
            <div class="col-md-3"><div class="card h-100 text-center p-3"><div class="card-body"><i class="fas fa-calendar-check fa-3x text-primary mb-3"></i><h5>Rendez-vous</h5><a href="index.php?page=login" class="btn btn-primary btn-sm">Se connecter</a></div></div></div>
            <div class="col-md-3"><div class="card h-100 text-center p-3"><div class="card-body"><i class="fas fa-blog fa-3x text-warning mb-3"></i><h5>Blog médical</h5><a href="index.php?page=blog_public" class="btn btn-warning btn-sm">Lire le blog</a></div></div></div>
            <div class="col-md-3"><div class="card h-100 text-center p-3"><div class="card-body"><i class="fas fa-calendar-alt fa-3x text-success mb-3"></i><h5>Événements</h5><a href="index.php?page=evenements" class="btn btn-success btn-sm">Voir</a></div></div></div>
            <div class="col-md-3"><div class="card h-100 text-center p-3"><div class="card-body"><i class="fas fa-user-md fa-3x text-info mb-3"></i><h5>Médecins</h5><a href="index.php?page=medecins" class="btn btn-info btn-sm">Voir</a></div></div></div>
        </div>';
    }

    // =============================================
    // DISPONIBILITÉS HTML
    // =============================================

    private function getPatientDisponibilitesHTML($disponibilites, $medecins): string {
        $html = '<div style="background:white;border-radius:12px;padding:20px;margin-bottom:25px;box-shadow:0 2px 8px rgba(0,0,0,0.08);">
            <h5><i class="fas fa-filter me-2"></i>Filtrer</h5>
            <form method="GET" class="row g-3">
                <input type="hidden" name="page" value="patient_disponibilites">
                <div class="col-md-4">
                    <select name="medecin_id" class="form-select">
                        <option value="">Tous les médecins</option>';
        foreach ($medecins as $medecin) {
            $selected = (isset($_GET['medecin_id']) && $_GET['medecin_id'] == $medecin['user_id']) ? 'selected' : '';
            $html .= '<option value="' . $medecin['user_id'] . '" ' . $selected . '>Dr. ' . htmlspecialchars($medecin['prenom'] . ' ' . $medecin['nom']) . '</option>';
        }
        $html .= '</select></div>
                <div class="col-md-3">
                    <select name="jour" class="form-select">
                        <option value="">Tous les jours</option>';
        foreach (['Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi','Dimanche'] as $jour) {
            $selected = (isset($_GET['jour']) && $_GET['jour'] == $jour) ? 'selected' : '';
            $html .= '<option value="' . $jour . '" ' . $selected . '>' . $jour . '</option>';
        }
        $html .= '</select></div>
                <div class="col-md-3"><button type="submit" class="btn btn-primary">Filtrer</button> <a href="index.php?page=patient_disponibilites" class="btn btn-secondary ms-2">Réinitialiser</a></div>
            </form>
        </div><div class="row">';

        if (empty($disponibilites)) {
            $html .= '<div class="col-12"><div class="alert alert-info text-center py-4">Aucune disponibilité.</div></div>';
        } else {
            $userRole = $_SESSION['user_role'] ?? '';
            $showBtn  = ($userRole === '' || $userRole === 'patient');
            foreach ($disponibilites as $dispo) {
                $html .= '<div class="col-md-6 col-lg-4 mb-4"><div class="card h-100 shadow-sm"><div class="card-body">
                    <h5 class="card-title text-primary">Dr. ' . htmlspecialchars($dispo['medecin_nom'] ?? 'N/A') . '</h5>
                    <p class="card-text text-muted">' . htmlspecialchars($dispo['specialite'] ?? 'Généraliste') . '</p>
                    <hr>
                    <p><i class="fas fa-calendar me-2 text-success"></i> ' . htmlspecialchars($dispo['jour_semaine']) . '</p>
                    <p><i class="fas fa-clock me-2 text-success"></i> ' . date('H:i', strtotime($dispo['heure_debut'])) . ' - ' . date('H:i', strtotime($dispo['heure_fin'])) . '</p>'
                    . ($showBtn ? '<a href="index.php?page=prendre_rendez_vous&id=' . $dispo['medecin_id'] . '" class="btn btn-primary btn-sm w-100"><i class="fas fa-calendar-check me-2"></i>Prendre rendez-vous</a>' : '') .
                '</div></div></div>';
            }
        }
        $html .= '</div>';
        return $html;
    }

    private function getMedecinDisponibilitesHTML($dispos): string {
        $html = '<div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-clock me-2"></i>Mes disponibilités</h2>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addDispoModal"><i class="fas fa-plus me-2"></i>Ajouter</button>
        </div>';

        if (empty($dispos)) {
            $html .= '<div class="alert alert-info text-center py-5">Aucune disponibilité définie.</div>';
        } else {
            $html .= '<div class="row">';
            foreach ($dispos as $dispo) {
                $html .= '<div class="col-md-6 col-lg-4 mb-4"><div class="card h-100 shadow-sm"><div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <h5 class="card-title text-primary">' . htmlspecialchars($dispo['jour_semaine']) . '</h5>
                        <span class="badge ' . ($dispo['actif'] ? 'bg-success' : 'bg-secondary') . '">' . ($dispo['actif'] ? 'Actif' : 'Inactif') . '</span>
                    </div>
                    <p class="card-text mt-3"><i class="fas fa-clock me-2"></i>' . date('H:i', strtotime($dispo['heure_debut'])) . ' - ' . date('H:i', strtotime($dispo['heure_fin'])) . '</p>
                    <div class="mt-3">
                        <a href="index.php?page=medecin_disponibilites&action=toggle&id=' . $dispo['id'] . '" class="btn btn-sm btn-warning">' . ($dispo['actif'] ? 'Désactiver' : 'Activer') . '</a>
                        <a href="index.php?page=medecin_disponibilites&action=delete&id=' . $dispo['id'] . '" class="btn btn-sm btn-danger" onclick="return confirm(\'Supprimer ?\')">Supprimer</a>
                    </div>
                </div></div></div>';
            }
            $html .= '</div>';
        }

        $html .= '<div class="modal fade" id="addDispoModal" tabindex="-1">
            <div class="modal-dialog"><div class="modal-content">
                <div class="modal-header bg-primary text-white"><h5 class="modal-title">Ajouter une disponibilité</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
                <form method="POST" action="index.php?page=medecin_disponibilites&action=store">
                    <div class="modal-body">
                        <div class="mb-3"><label class="form-label">Jour *</label>
                            <select name="jour_semaine" class="form-select" required>
                                <option value="">Sélectionner</option>';
        foreach (['Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi','Dimanche'] as $j) {
            $html .= '<option value="' . $j . '">' . $j . '</option>';
        }
        $html .= '</select></div>
                        <div class="row">
                            <div class="col-md-6 mb-3"><label class="form-label">Début *</label><input type="time" name="heure_debut" class="form-control" required></div>
                            <div class="col-md-6 mb-3"><label class="form-label">Fin *</label><input type="time" name="heure_fin" class="form-control" required></div>
                        </div>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button><button type="submit" class="btn btn-primary">Ajouter</button></div>
                </form>
            </div></div>
        </div>';
        return $html;
    }

    // =============================================
    // MODAL FACEBOOK — CRÉER UN ARTICLE
    // =============================================

    private function getFbModalScript(): string
    {
        $userAvatar = strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1));
        $userName   = htmlspecialchars($_SESSION['user_name'] ?? '');

        return '
        <!-- MODAL FACEBOOK ARTICLE -->
        <div id="articleModal" style="display:none;position:fixed;z-index:9999;left:0;top:0;width:100%;height:100%;background:rgba(0,0,0,0.6);align-items:center;justify-content:center;">
            <div style="background:white;border-radius:12px;width:95%;max-width:560px;max-height:90vh;overflow-y:auto;box-shadow:0 4px 30px rgba(0,0,0,0.3);">

                <div style="padding:16px;border-bottom:1px solid #e4e6eb;position:relative;text-align:center;">
                    <h2 style="margin:0;font-size:20px;font-weight:700;color:#1c1e21;">Créer une publication</h2>
                    <button onclick="closeArticleModal()" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:#e4e6eb;border:none;border-radius:50%;width:36px;height:36px;cursor:pointer;font-size:20px;line-height:1;">×</button>
                </div>

                <form method="POST" action="index.php?page=admin_article_create" enctype="multipart/form-data" id="fbArticleForm">

                    <div style="padding:16px;display:flex;align-items:center;gap:10px;">
                        <div style="width:42px;height:42px;border-radius:50%;background:linear-gradient(135deg,#2A7FAA,#4CAF50);display:flex;align-items:center;justify-content:center;color:white;font-weight:bold;font-size:18px;flex-shrink:0;">' . $userAvatar . '</div>
                        <div>
                            <div style="font-weight:700;font-size:15px;color:#1c1e21;">' . $userName . '</div>
                            <div style="background:#e4e6eb;border-radius:6px;padding:2px 10px;font-size:12px;color:#1c1e21;display:inline-flex;align-items:center;gap:5px;">
                                <i class="fas fa-users" style="font-size:11px;"></i> Amis ▼
                            </div>
                        </div>
                    </div>

                    <div style="padding:0 16px 10px;">
                        <input type="text" name="titre" id="fb_titre" placeholder="Titre de l\'article..." required
                            oninput="checkFbBtn()"
                            style="width:100%;border:none;border-bottom:2px solid #e4e6eb;outline:none;font-size:16px;font-weight:600;color:#1c1e21;padding:5px 0;font-family:inherit;box-sizing:border-box;">
                    </div>

                    <div style="padding:0 16px 10px;position:relative;">
                        <textarea name="contenu" id="fb_contenu" placeholder="Quoi de neuf ?" rows="5" required
                            oninput="checkFbBtn()"
                            style="width:100%;border:none;outline:none;font-size:18px;color:#1c1e21;resize:none;font-family:inherit;box-sizing:border-box;"></textarea>
                        <button type="button" onclick="toggleFbEmoji()" style="position:absolute;right:20px;bottom:15px;background:none;border:none;cursor:pointer;font-size:24px;">😊</button>
                    </div>

                    <div id="fbEmojiZone" style="display:none;padding:10px 16px;border-top:1px solid #e4e6eb;">
                        <div id="fbEmojiGrid" style="display:flex;flex-wrap:wrap;gap:6px;"></div>
                    </div>

                    <div id="fbImgPreview" style="display:none;padding:0 16px 10px;position:relative;">
                        <img id="fbPreviewImg" src="" style="width:100%;border-radius:8px;max-height:300px;object-fit:cover;">
                        <button onclick="removeFbImg()" type="button" style="position:absolute;top:10px;right:25px;background:rgba(0,0,0,0.6);color:white;border:none;border-radius:50%;width:32px;height:32px;cursor:pointer;font-size:18px;line-height:1;">×</button>
                    </div>

                    <div style="margin:0 16px 12px;border:1px solid #e4e6eb;border-radius:10px;padding:10px 16px;display:flex;justify-content:space-between;align-items:center;">
                        <span style="font-weight:600;font-size:15px;color:#1c1e21;">Ajouter à votre publication</span>
                        <div style="display:flex;gap:4px;">
                            <label style="cursor:pointer;padding:8px;border-radius:50%;display:flex;align-items:center;justify-content:center;" title="Photo">
                                <i class="fas fa-image" style="font-size:24px;color:#45bd62;"></i>
                                <input type="file" name="article_image" id="fbImgInput" accept="image/*" style="display:none;" onchange="previewFbImg(this)">
                            </label>
                            <button type="button" onclick="toggleFbEmoji()" style="background:none;border:none;cursor:pointer;padding:8px;" title="Emoji">
                                <i class="fas fa-smile" style="font-size:24px;color:#f7b928;"></i>
                            </button>
                        </div>
                    </div>

                    <div style="padding:0 16px 16px;">
                        <button type="submit" id="fbPublishBtn" disabled
                            style="width:100%;background:#e4e6eb;color:#bcc0c4;border:none;border-radius:8px;padding:10px;font-size:17px;font-weight:600;cursor:not-allowed;font-family:inherit;">
                            Publier
                        </button>
                    </div>

                </form>
            </div>
        </div>

        <script>
        var fbEmojis = ["😀","😂","😍","🥰","😎","😢","😡","👍","👎","❤️","🔥","✅","⭐","💪","🙏","🤔","😷","🏥","💊","🩺","🎉","😴","🤩","🥳","😅","🤗","😐","🙁","😤"];
        var fbEmojiLoaded = false;

        function openArticleModal(type) {
            var modal = document.getElementById("articleModal");
            if (!modal) return;
            modal.style.display = "flex";
            document.body.style.overflow = "hidden";
            if (type === "image") {
                var inp = document.getElementById("fbImgInput");
                if (inp) inp.click();
            }
            if (type === "emoji") {
                loadFbEmojis();
                var ez = document.getElementById("fbEmojiZone");
                if (ez) ez.style.display = "block";
            }
            setTimeout(function() {
                var ta = document.getElementById("fb_contenu");
                if (ta) ta.focus();
            }, 100);
        }

        function closeArticleModal() {
            var modal = document.getElementById("articleModal");
            if (modal) modal.style.display = "none";
            document.body.style.overflow = "auto";
        }

        function toggleFbEmoji() {
            loadFbEmojis();
            var z = document.getElementById("fbEmojiZone");
            if (!z) return;
            z.style.display = (z.style.display === "none" || z.style.display === "") ? "block" : "none";
        }

        function loadFbEmojis() {
            if (fbEmojiLoaded) return;
            var grid = document.getElementById("fbEmojiGrid");
            if (!grid) return;
            fbEmojis.forEach(function(em) {
                var b = document.createElement("button");
                b.type = "button";
                b.textContent = em;
                b.style.cssText = "font-size:22px;background:none;border:none;cursor:pointer;padding:4px;border-radius:6px;";
                b.onmouseover = function() { this.style.background = "#f0f2f5"; };
                b.onmouseout  = function() { this.style.background = "none"; };
                b.onclick = function() {
                    var ta = document.getElementById("fb_contenu");
                    if (ta) { ta.value += em; checkFbBtn(); }
                };
                grid.appendChild(b);
            });
            fbEmojiLoaded = true;
        }

        function previewFbImg(input) {
            if (input.files && input.files[0]) {
                var r = new FileReader();
                r.onload = function(ev) {
                    var pi = document.getElementById("fbPreviewImg");
                    var pz = document.getElementById("fbImgPreview");
                    if (pi) pi.src = ev.target.result;
                    if (pz) pz.style.display = "block";
                };
                r.readAsDataURL(input.files[0]);
                checkFbBtn();
            }
        }

        function removeFbImg() {
            var inp = document.getElementById("fbImgInput");
            var pz  = document.getElementById("fbImgPreview");
            var pi  = document.getElementById("fbPreviewImg");
            if (inp) inp.value = "";
            if (pz)  pz.style.display = "none";
            if (pi)  pi.src = "";
            checkFbBtn();
        }

        function checkFbBtn() {
            var titreEl  = document.getElementById("fb_titre");
            var contenuEl = document.getElementById("fb_contenu");
            var inpEl    = document.getElementById("fbImgInput");
            var btn      = document.getElementById("fbPublishBtn");
            if (!btn) return;
            var titre   = titreEl  ? titreEl.value.trim()  : "";
            var contenu = contenuEl ? contenuEl.value.trim() : "";
            var hasFile = inpEl && inpEl.files && inpEl.files.length > 0;
            if (titre.length > 0 && (contenu.length > 0 || hasFile)) {
                btn.style.cssText = "width:100%;background:#1877f2;color:white;border:none;border-radius:8px;padding:10px;font-size:17px;font-weight:600;cursor:pointer;font-family:inherit;";
                btn.disabled = false;
            } else {
                btn.style.cssText = "width:100%;background:#e4e6eb;color:#bcc0c4;border:none;border-radius:8px;padding:10px;font-size:17px;font-weight:600;cursor:not-allowed;font-family:inherit;";
                btn.disabled = true;
            }
        }

        // Fermer en cliquant sur le fond
        document.getElementById("articleModal").addEventListener("click", function(e) {
            if (e.target === this) closeArticleModal();
        });
        </script>';
    }

    // =============================================
    // CRUD COMMENTAIRES ADMIN — AVEC JOINTURE
    // =============================================

    public function repliesAdmin(): void {
        if (($_SESSION['user_role'] ?? '') !== 'admin') {
            header('Location: index.php?page=login'); exit;
        }

        require_once __DIR__ . '/../config/database.php';
        $db = Database::getInstance()->getConnection();

        $action  = $_GET['action'] ?? 'list';
        $replyId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $flash   = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);

        // ── DELETE ───────────────────────────────
        if ($action === 'delete' && $replyId) {
            try {
                $db->prepare("DELETE FROM replies WHERE id = ?")->execute([$replyId]);
                $_SESSION['flash'] = ['type'=>'success','message'=>'Commentaire supprimé.'];
            } catch(Exception $e) {
                $_SESSION['flash'] = ['type'=>'error','message'=>$e->getMessage()];
            }
            header('Location: index.php?page=replies_admin'); exit;
        }

        // ── APPROVE ──────────────────────────────
        if ($action === 'approve' && $replyId) {
            try {
                $db->prepare("UPDATE replies SET moderation_status='approved', moderated_at=NOW() WHERE id=?")->execute([$replyId]);
                $_SESSION['flash'] = ['type'=>'success','message'=>'Commentaire approuvé.'];
            } catch(Exception $e) { $_SESSION['flash'] = ['type'=>'error','message'=>$e->getMessage()]; }
            header('Location: index.php?page=replies_admin'); exit;
        }

        // ── REJECT ───────────────────────────────
        if ($action === 'reject' && $replyId) {
            try {
                $db->prepare("UPDATE replies SET moderation_status='rejected', moderated_at=NOW() WHERE id=?")->execute([$replyId]);
                $_SESSION['flash'] = ['type'=>'success','message'=>'Commentaire rejeté.'];
            } catch(Exception $e) { $_SESSION['flash'] = ['type'=>'error','message'=>$e->getMessage()]; }
            header('Location: index.php?page=replies_admin'); exit;
        }

        // ── EDIT POST ────────────────────────────
        if ($action === 'edit' && $replyId && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $replay = trim($_POST['replay'] ?? '');
            try {
                require_once __DIR__ . '/../config/database.php';
                $db2 = Database::getInstance()->getConnection();
                // Get current image
                $stmt = $db->prepare("SELECT image FROM replies WHERE id=?");
                $stmt->execute([$replyId]);
                $cur = $stmt->fetch(PDO::FETCH_ASSOC);
                $imagePath = $cur['image'] ?? null;
                // Delete image if requested
                if (isset($_POST['delete_image']) && $_POST['delete_image'] == '1') {
                    if ($imagePath && file_exists(__DIR__ . '/../' . $imagePath)) unlink(__DIR__ . '/../' . $imagePath);
                    $imagePath = null;
                }
                // Upload new image
                if (isset($_FILES['reply_image']) && $_FILES['reply_image']['error'] === UPLOAD_ERR_OK) {
                    $newImg = $this->uploadReplyImage($_FILES['reply_image']);
                    if ($newImg) $imagePath = $newImg;
                }
                $db->prepare("UPDATE replies SET replay=?, image=? WHERE id=?")->execute([$replay ?: null, $imagePath, $replyId]);
                $_SESSION['flash'] = ['type'=>'success','message'=>'Commentaire modifié.'];
                header('Location: index.php?page=replies_admin'); exit;
            } catch(Exception $e) {
                $flash = ['type'=>'error','message'=>$e->getMessage()];
            }
        }

        // ── EDIT FORM ────────────────────────────
        $editReply = null;
        if ($action === 'edit' && $replyId && $_SERVER['REQUEST_METHOD'] === 'GET') {
            $stmt = $db->prepare("
                SELECT r.*, a.titre AS article_titre, a.id AS article_id,
                       CONCAT(u.prenom,' ',u.nom) AS auteur_nom, u.email AS auteur_email
                FROM replies r
                LEFT JOIN articles a ON a.id = r.article_id
                LEFT JOIN users u ON u.id = r.user_id
                WHERE r.id = ?
            ");
            $stmt->execute([$replyId]);
            $editReply = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        // ── FILTRES ──────────────────────────────
        $search    = trim($_GET['q'] ?? '');
        $filterArt = (int)($_GET['article_id'] ?? 0);
        $filterMod = $_GET['moderation'] ?? '';
        $sortOrder = in_array($_GET['sort'] ?? 'desc', ['asc','desc']) ? ($_GET['sort'] ?? 'desc') : 'desc';

        // ── REQUÊTE JOINTURE ─────────────────────
        $where = ['1=1']; $params = [];
        if ($search !== '') {
            $where[] = "(r.replay LIKE ? OR CONCAT(u.prenom,' ',u.nom) LIKE ? OR a.titre LIKE ?)";
            $q = '%'.$search.'%';
            $params = array_merge($params, [$q, $q, $q]);
        }
        if ($filterArt > 0) { $where[] = "r.article_id = ?"; $params[] = $filterArt; }
        if (in_array($filterMod, ['approved','rejected','pending'])) {
            $where[] = "r.moderation_status = ?"; $params[] = $filterMod;
        }

        $sql = "
            SELECT r.id AS reply_id, r.replay AS contenu, r.emoji, r.image AS photo,
                   r.status, r.moderation_status, r.moderation_reason, r.created_at, r.parent_id,
                   a.id AS article_id, a.titre AS article_titre, a.status AS article_status,
                   CONCAT(u.prenom,' ',u.nom) AS auteur_nom, u.email AS auteur_email,
                   pr.replay AS parent_contenu, CONCAT(pu.prenom,' ',pu.nom) AS parent_auteur,
                   (SELECT COUNT(*) FROM reply_likes rl WHERE rl.reply_id=r.id AND rl.type='like') AS nb_likes,
                   (SELECT COUNT(*) FROM reply_likes rl WHERE rl.reply_id=r.id AND rl.type='dislike') AS nb_dislikes
            FROM replies r
            LEFT JOIN articles a ON a.id = r.article_id
            LEFT JOIN users u ON u.id = r.user_id
            LEFT JOIN replies pr ON pr.id = r.parent_id
            LEFT JOIN users pu ON pu.id = pr.user_id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY r.created_at " . strtoupper($sortOrder);

        try {
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $replies = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(Exception $e) { $replies = []; }

        $articles = [];
        try { $articles = $db->query("SELECT id, titre FROM articles ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC); } catch(Exception $e) {}

        $total    = count($replies);
        $approved = count(array_filter($replies, fn($r) => ($r['moderation_status']??'approved') === 'approved'));
        $rejected = count(array_filter($replies, fn($r) => ($r['moderation_status']??'') === 'rejected'));
        $pending  = count(array_filter($replies, fn($r) => ($r['moderation_status']??'') === 'pending'));

        // ── RENDU HTML ───────────────────────────
        ob_start();
        ?>
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <title>Gestion commentaires — Valorys</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
            <style>
                body{background:#f0f2f5;font-family:'Segoe UI',sans-serif;}
                .reply-card{background:white;border-radius:12px;padding:20px;margin-bottom:15px;box-shadow:0 1px 4px rgba(0,0,0,0.08);transition:transform 0.2s;}
                .reply-card:hover{transform:translateY(-2px);box-shadow:0 4px 12px rgba(0,0,0,0.12);}
                .avatar{width:42px;height:42px;border-radius:50%;background:linear-gradient(135deg,#2A7FAA,#4CAF50);display:flex;align-items:center;justify-content:center;color:white;font-weight:bold;font-size:18px;flex-shrink:0;}
                .mod-badge{padding:4px 12px;border-radius:20px;font-size:12px;font-weight:600;}
                .mod-approved{background:#d4edda;color:#155724;}
                .mod-rejected{background:#f8d7da;color:#721c24;}
                .mod-pending{background:#fff3cd;color:#856404;}
                .stat-card{background:white;border-radius:12px;padding:20px;text-align:center;box-shadow:0 1px 4px rgba(0,0,0,0.08);}
                .search-bar{background:white;border-radius:12px;padding:15px;box-shadow:0 1px 4px rgba(0,0,0,0.08);margin-bottom:20px;}
                .search-bar input,.search-bar select{border:2px solid #e4e6eb;border-radius:25px;padding:10px 16px;font-size:14px;outline:none;}
                .search-bar input:focus,.search-bar select:focus{border-color:#2A7FAA;}
                .btn-act{width:32px;height:32px;border-radius:8px;border:none;display:inline-flex;align-items:center;justify-content:center;font-size:13px;cursor:pointer;text-decoration:none;margin:0 2px;}
                .btn-act:hover{opacity:0.8;}
                .img-thumb{width:60px;height:60px;object-fit:cover;border-radius:8px;}
                .article-link{color:#2A7FAA;text-decoration:none;font-weight:600;font-size:13px;}
                .article-link:hover{text-decoration:underline;}
                .parent-badge{background:#e3f2fd;color:#1565c0;border-radius:8px;padding:4px 10px;font-size:11px;}
            </style>
        </head>
        <body>
        <!-- NAVBAR -->
        <nav style="background:linear-gradient(135deg,#1e2a3a,#2A7FAA);padding:12px 20px;display:flex;justify-content:space-between;align-items:center;margin-bottom:0;">
            <div style="color:white;font-weight:700;font-size:18px;display:flex;align-items:center;gap:10px;">
                <i class="fas fa-comments"></i> Valorys — Commentaires
            </div>
            <div style="display:flex;gap:10px;">
                <a href="index.php?page=articles_admin" style="background:rgba(255,255,255,0.15);color:white;border:1px solid rgba(255,255,255,0.3);border-radius:20px;padding:6px 16px;text-decoration:none;font-size:13px;"><i class="fas fa-newspaper"></i> Articles</a>
                <a href="index.php?page=dashboard" style="background:rgba(255,255,255,0.15);color:white;border:1px solid rgba(255,255,255,0.3);border-radius:20px;padding:6px 16px;text-decoration:none;font-size:13px;"><i class="fas fa-home"></i> Dashboard</a>
                <a href="index.php?page=blog_public" style="background:rgba(255,255,255,0.15);color:white;border:1px solid rgba(255,255,255,0.3);border-radius:20px;padding:6px 16px;text-decoration:none;font-size:13px;"><i class="fas fa-eye"></i> Site</a>
                <a href="index.php?page=logout" style="background:#dc3545;color:white;border-radius:20px;padding:6px 16px;text-decoration:none;font-size:13px;">Déconnexion</a>
            </div>
        </nav>

        <div style="max-width:1200px;margin:25px auto;padding:0 20px;">

        <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type']==='success'?'success':'danger' ?> alert-dismissible mb-3">
            <?= htmlspecialchars($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if ($editReply): ?>
        <!-- FORMULAIRE MODIFICATION STYLE FRONT OFFICE -->
        <div style="max-width:680px;margin:0 auto;">
            <div style="background:white;border-radius:12px;padding:25px;box-shadow:0 2px 8px rgba(0,0,0,0.1);">
                <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;padding-bottom:15px;border-bottom:2px solid #f0f0f0;">
                    <div class="avatar"><?= strtoupper(substr($editReply['auteur_nom']??'U',0,1)) ?></div>
                    <div>
                        <div style="font-weight:700;font-size:15px;color:#1c1e21;"><?= htmlspecialchars($editReply['auteur_nom']??'') ?></div>
                        <div style="font-size:12px;color:#65676b;"><?= htmlspecialchars($editReply['auteur_email']??'') ?></div>
                    </div>
                </div>
                <div style="background:#e3f2fd;border-radius:8px;padding:12px;margin-bottom:15px;font-size:13px;">
                    <i class="fas fa-newspaper text-primary"></i>
                    <strong>Article :</strong>
                    <a href="index.php?page=detail_article_public&id=<?= $editReply['article_id'] ?>" style="color:#2A7FAA;"><?= htmlspecialchars($editReply['article_titre']) ?></a>
                    &nbsp;·&nbsp; <i class="fas fa-calendar"></i> <?= date('d/m/Y H:i', strtotime($editReply['created_at'])) ?>
                </div>
                <form method="POST" action="index.php?page=replies_admin&action=edit&id=<?= $replyId ?>" enctype="multipart/form-data">
                    <div style="margin-bottom:15px;">
                        <label style="font-weight:600;font-size:14px;color:#1c1e21;display:block;margin-bottom:8px;">✏️ Modifier le commentaire</label>
                        <textarea name="replay" rows="5" required
                            style="width:100%;border:2px solid #e4e6eb;border-radius:12px;padding:12px;font-size:15px;outline:none;resize:vertical;font-family:inherit;box-sizing:border-box;"
                            onfocus="this.style.borderColor='#2A7FAA'" onblur="this.style.borderColor='#e4e6eb'"><?= htmlspecialchars($editReply['replay'] ?? '') ?></textarea>
                    </div>
                    <!-- IMAGE UPLOAD -->
                    <div style="margin-bottom:15px;">
                        <?php if (!empty($editReply['photo'])): ?>
                        <div style="margin-bottom:10px;">
                            <img src="<?= htmlspecialchars($editReply['photo']) ?>" style="max-width:200px;border-radius:8px;">
                            <div style="margin-top:5px;"><label style="font-size:13px;color:#65676b;cursor:pointer;"><input type="checkbox" name="delete_image" value="1"> Supprimer l'image actuelle</label></div>
                        </div>
                        <?php endif; ?>
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;background:#f0f2f5;border-radius:8px;padding:8px 14px;width:fit-content;">
                            <i class="fas fa-image" style="color:#45bd62;font-size:18px;"></i>
                            <span style="font-size:14px;color:#333;">Ajouter une image</span>
                            <input type="file" name="reply_image" accept="image/*" style="display:none;" onchange="previewReplyImg(this)">
                        </label>
                        <div id="replyImgPreview" style="display:none;margin-top:8px;position:relative;">
                            <img id="replyImgPreviewSrc" src="" style="max-width:100%;max-height:150px;border-radius:8px;object-fit:cover;">
                            <span onclick="removeReplyImg()" style="position:absolute;top:4px;right:4px;background:rgba(0,0,0,0.6);color:white;border-radius:50%;width:24px;height:24px;cursor:pointer;display:flex;align-items:center;justify-content:center;">×</span>
                        </div>
                    </div>
                    <div style="display:flex;gap:10px;">
                        <button type="submit" style="background:linear-gradient(135deg,#2A7FAA,#4CAF50);color:white;border:none;border-radius:25px;padding:10px 25px;font-weight:600;cursor:pointer;font-size:14px;">
                            <i class="fas fa-save"></i> Enregistrer
                        </button>
                        <a href="index.php?page=replies_admin" style="background:#e4e6eb;color:#333;border-radius:25px;padding:10px 20px;text-decoration:none;font-size:14px;font-weight:600;">
                            Annuler
                        </a>
                    </div>
                </form>
                <script>
                function previewReplyImg(input) {
                    if (input.files && input.files[0]) {
                        var r = new FileReader();
                        r.onload = function(ev) {
                            document.getElementById('replyImgPreviewSrc').src = ev.target.result;
                            document.getElementById('replyImgPreview').style.display = 'block';
                        };
                        r.readAsDataURL(input.files[0]);
                    }
                }
                function removeReplyImg() {
                    document.querySelector('input[name="reply_image"]').value = '';
                    document.getElementById('replyImgPreview').style.display = 'none';
                }
                </script>
            </div>
        </div>

        <?php else: ?>
        <!-- STATS -->
        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:15px;margin-bottom:20px;">
            <div class="stat-card" style="border-top:4px solid #6c757d;">
                <div style="font-size:2rem;font-weight:800;color:#6c757d;"><?= $total ?></div>
                <div style="color:#65676b;font-size:14px;">Total</div>
            </div>
            <div class="stat-card" style="border-top:4px solid #28a745;">
                <div style="font-size:2rem;font-weight:800;color:#28a745;"><?= $approved ?></div>
                <div style="color:#65676b;font-size:14px;">✅ Approuvés</div>
            </div>
            <div class="stat-card" style="border-top:4px solid #dc3545;">
                <div style="font-size:2rem;font-weight:800;color:#dc3545;"><?= $rejected ?></div>
                <div style="color:#65676b;font-size:14px;">🚫 Rejetés</div>
            </div>
            <div class="stat-card" style="border-top:4px solid #ffc107;">
                <div style="font-size:2rem;font-weight:800;color:#ffc107;"><?= $pending ?></div>
                <div style="color:#65676b;font-size:14px;">⏳ En attente</div>
            </div>
        </div>

        <!-- FILTRES -->
        <div class="search-bar">
            <form method="GET" action="index.php" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
                <input type="hidden" name="page" value="replies_admin">
                <div style="flex:1;min-width:200px;position:relative;">
                    <i class="fas fa-search" style="position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#65676b;"></i>
                    <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Rechercher..." style="width:100%;padding-left:40px;box-sizing:border-box;">
                </div>
                <select name="article_id" style="min-width:180px;" onchange="this.form.submit()">
                    <option value="">📰 Tous les articles</option>
                    <?php foreach($articles as $art): ?>
                    <option value="<?= $art['id'] ?>" <?= $filterArt==$art['id']?'selected':'' ?>><?= htmlspecialchars(substr($art['titre'],0,35)) ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="moderation" style="min-width:150px;" onchange="this.form.submit()">
                    <option value="">🤖 Modération</option>
                    <option value="approved" <?= $filterMod==='approved'?'selected':'' ?>>✅ Approuvés</option>
                    <option value="rejected" <?= $filterMod==='rejected'?'selected':'' ?>>🚫 Rejetés</option>
                    <option value="pending"  <?= $filterMod==='pending'?'selected':'' ?>>⏳ En attente</option>
                </select>
                <select name="sort" style="min-width:150px;" onchange="this.form.submit()">
                    <option value="desc" <?= $sortOrder==='desc'?'selected':'' ?>>↓ Plus récent</option>
                    <option value="asc"  <?= $sortOrder==='asc'?'selected':'' ?>>↑ Plus ancien</option>
                </select>
                <button type="submit" style="background:#1877f2;color:white;border:none;border-radius:25px;padding:10px 20px;cursor:pointer;font-weight:600;font-size:14px;">
                    <i class="fas fa-search"></i> Filtrer
                </button>
                <?php if($search||$filterArt||$filterMod): ?>
                <a href="index.php?page=replies_admin" style="background:#e4e6eb;color:#333;border-radius:25px;padding:10px 14px;text-decoration:none;font-size:14px;">✕</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- TITRE + JOINTURE INFO -->
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:15px;">
            <h4 style="margin:0;color:#1c1e21;"><i class="fas fa-comments text-primary"></i> Commentaires <span style="background:#1877f2;color:white;border-radius:20px;padding:2px 10px;font-size:14px;"><?= $total ?></span></h4>
            <small style="color:#65676b;font-size:12px;"><code>replies LEFT JOIN articles LEFT JOIN users</code></small>
        </div>

        <!-- LISTE CARTES STYLE FRONT OFFICE -->
        <?php if (empty($replies)): ?>
        <div style="background:white;border-radius:12px;padding:50px;text-align:center;color:#65676b;">
            <i class="fas fa-comments fa-3x mb-3 d-block" style="opacity:0.2;"></i>
            Aucun commentaire trouvé.
        </div>
        <?php else: ?>
        <?php foreach ($replies as $r):
            $ms = $r['moderation_status'] ?? 'approved';
            $modClass = ['approved'=>'mod-approved','rejected'=>'mod-rejected','pending'=>'mod-pending'];
            $modLabel = ['approved'=>'✅ Approuvé','rejected'=>'🚫 Rejeté','pending'=>'⏳ En attente'];
            $initial = strtoupper(substr($r['auteur_nom']??'U',0,1));
        ?>
        <div class="reply-card">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;">
                <!-- AVATAR + AUTEUR -->
                <div style="display:flex;gap:10px;align-items:flex-start;flex:1;min-width:0;">
                    <div class="avatar"><?= $initial ?></div>
                    <div style="flex:1;min-width:0;">
                        <div style="font-weight:700;font-size:14px;color:#1c1e21;"><?= htmlspecialchars($r['auteur_nom']??'Anonyme') ?></div>
                        <div style="font-size:11px;color:#65676b;"><?= htmlspecialchars($r['auteur_email']??'') ?></div>
                        <div style="font-size:11px;color:#999;margin-top:2px;"><i class="fas fa-clock"></i> <?= date('d/m/Y H:i',strtotime($r['created_at'])) ?></div>
                    </div>
                </div>
                <!-- ACTIONS -->
                <div style="display:flex;gap:4px;flex-shrink:0;">
                    <a href="index.php?page=replies_admin&action=edit&id=<?= $r['reply_id'] ?>" class="btn-act" style="background:#fff3cd;color:#856404;" title="Modifier"><i class="fas fa-edit"></i></a>
                    <?php if ($ms !== 'approved'): ?>
                    <a href="index.php?page=replies_admin&action=approve&id=<?= $r['reply_id'] ?>" class="btn-act" style="background:#d4edda;color:#155724;" title="Approuver"><i class="fas fa-check"></i></a>
                    <?php endif; ?>
                    <?php if ($ms !== 'rejected'): ?>
                    <a href="index.php?page=replies_admin&action=reject&id=<?= $r['reply_id'] ?>" class="btn-act" style="background:#e2e8f0;color:#475569;" title="Rejeter"><i class="fas fa-ban"></i></a>
                    <?php endif; ?>
                    <a href="index.php?page=replies_admin&action=delete&id=<?= $r['reply_id'] ?>" onclick="return confirm('Supprimer ?')" class="btn-act" style="background:#f8d7da;color:#721c24;" title="Supprimer"><i class="fas fa-trash"></i></a>
                </div>
            </div>

            <!-- CONTENU -->
            <div style="margin:12px 0 12px 52px;">
                <?php if (!empty($r['photo'])): ?>
                <img src="<?= htmlspecialchars($r['photo']) ?>" class="img-thumb mb-2">
                <?php endif; ?>
                <?php if (!empty($r['emoji'])): ?>
                <span style="font-size:24px;"><?= $r['emoji'] ?></span>
                <?php endif; ?>
                <div style="font-size:15px;color:#1c1e21;line-height:1.5;"><?= htmlspecialchars($r['contenu']??'') ?></div>
                <?php if ($r['parent_id']): ?>
                <div style="margin-top:8px;">
                    <span class="parent-badge">↩ Réponse à: <?= htmlspecialchars(substr($r['parent_contenu']??'',0,40)) ?> (<?= htmlspecialchars($r['parent_auteur']??'') ?>)</span>
                </div>
                <?php endif; ?>
            </div>

            <!-- FOOTER : ARTICLE + LIKES + MODERATION -->
            <div style="margin-left:52px;padding-top:10px;border-top:1px solid #f0f0f0;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;">
                <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                    <!-- JOINTURE ARTICLE -->
                    <a href="index.php?page=detail_article_public&id=<?= $r['article_id'] ?>" class="article-link">
                        <i class="fas fa-newspaper" style="font-size:11px;"></i>
                        <?= htmlspecialchars(substr($r['article_titre']??'Sans titre',0,35)) ?>
                    </a>
                    <span style="background:#e8f5e9;color:#2e7d32;border-radius:12px;padding:2px 8px;font-size:11px;"><?= htmlspecialchars($r['article_status']??'') ?></span>
                </div>
                <div style="display:flex;align-items:center;gap:8px;">
                    <button onclick="likeReplyAdmin(<?= $r['reply_id'] ?>,'like')" id="rl-like-<?= $r['reply_id'] ?>"
                        style="background:#e7f3ff;color:#1877f2;border:none;border-radius:20px;padding:4px 12px;font-size:13px;font-weight:600;cursor:pointer;">
                        👍 <?= $r['nb_likes'] ?>
                    </button>
                    <button onclick="likeReplyAdmin(<?= $r['reply_id'] ?>,'dislike')" id="rl-dislike-<?= $r['reply_id'] ?>"
                        style="background:#fff0f0;color:#dc3545;border:none;border-radius:20px;padding:4px 12px;font-size:13px;font-weight:600;cursor:pointer;">
                        👎 <?= $r['nb_dislikes'] ?>
                    </button>
                    <span class="mod-badge <?= $modClass[$ms]??'mod-approved' ?>"><?= $modLabel[$ms]??'✅' ?></span>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>

        <?php endif; ?>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script>
        function likeReplyAdmin(replyId, type) {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'index.php?page=api_reply_like', true);
            xhr.setRequestHeader('Content-Type', 'application/json');
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    try {
                        var d = JSON.parse(xhr.responseText);
                        if (d.success) {
                            var lb = document.getElementById('rl-like-' + replyId);
                            var db = document.getElementById('rl-dislike-' + replyId);
                            if (lb) lb.textContent = '👍 ' + d.likes;
                            if (db) db.textContent = '👎 ' + d.dislikes;
                        } else { alert(d.message || 'Erreur'); }
                    } catch(e) { console.error(e); }
                }
            };
            xhr.send(JSON.stringify({reply_id: parseInt(replyId), type: type}));
        }
        </script>
        </body></html>
        <?php
        ob_end_flush();
    }

    // =============================================
    // MÉTIERS MÉDICAUX
    // =============================================

    public function listeMetiers(): void {
        require_once __DIR__ . '/../models/Metier.php';
        $metierModel = new Metier();
        $search  = trim($_GET['q'] ?? '');
        $metiers = $search ? $metierModel->search($search) : $metierModel->getAll();

        $grouped = [];
        foreach ($metiers as $m) {
            $grouped[$m['categorie']][] = $m;
        }

        $icons = [
            'Médecine générale'       => 'fa-user-md',
            'Spécialités médicales'   => 'fa-stethoscope',
            'Chirurgie'               => 'fa-cut',
            'Spécialités sensorielles'=> 'fa-eye',
            'Imagerie & Biologie'     => 'fa-x-ray',
            'Urgences & Réanimation'  => 'fa-ambulance',
            'Santé féminine'          => 'fa-venus',
            'Rééducation'             => 'fa-hands',
            'Santé mentale'           => 'fa-brain',
            'Soins infirmiers'        => 'fa-syringe',
            'Pharmacie'               => 'fa-pills',
            'Nutrition'               => 'fa-apple-alt',
            'Dentisterie'             => 'fa-tooth',
            'Médecine du travail'     => 'fa-hard-hat',
            'Médecine légale'         => 'fa-balance-scale',
        ];

        ob_start();
        ?>
        <style>
            .metier-card { background:white;border-radius:12px;margin-bottom:20px;box-shadow:0 2px 8px rgba(0,0,0,0.08);overflow:hidden;transition:transform 0.2s; }
            .metier-card:hover { transform:translateY(-2px); }
            .metier-header { padding:15px 20px;display:flex;align-items:center;gap:12px; }
            .metier-icon { width:40px;height:40px;border-radius:50%;background:rgba(255,255,255,0.2);display:flex;align-items:center;justify-content:center;font-size:18px; }
            .metier-badge { background:#f0f7ff;border:1px solid #d0e4f7;border-radius:25px;padding:8px 16px;font-size:14px;color:#2A7FAA;display:inline-flex;align-items:center;gap:6px;transition:all 0.2s; }
            .metier-badge:hover { background:#2A7FAA;color:white;border-color:#2A7FAA; }
            .search-input:focus { border-color:#2A7FAA;box-shadow:0 0 0 3px rgba(42,127,170,0.1);outline:none; }
        </style>

        <!-- HERO -->
        <div style="background:linear-gradient(135deg,#2A7FAA,#4CAF50);border-radius:12px;padding:30px;margin-bottom:25px;text-align:center;color:white;">
            <h2 style="margin:0 0 8px;font-size:28px;"><i class="fas fa-briefcase-medical me-2"></i>Métiers Médicaux</h2>
            <p style="margin:0;opacity:0.9;font-size:16px;"><?= count($metiers) ?> métiers dans <?= count($grouped) ?> spécialités</p>
        </div>

        <!-- RECHERCHE -->
        <div style="background:white;border-radius:12px;padding:20px;margin-bottom:20px;box-shadow:0 2px 8px rgba(0,0,0,0.08);">
            <form method="GET" action="index.php" style="display:flex;gap:10px;align-items:center;">
                <input type="hidden" name="page" value="metiers">
                <div style="flex:1;position:relative;">
                    <i class="fas fa-search" style="position:absolute;left:16px;top:50%;transform:translateY(-50%);color:#65676b;"></i>
                    <input type="text" name="q" value="<?= htmlspecialchars($search) ?>"
                        class="search-input"
                        placeholder="Rechercher un métier médical..."
                        style="width:100%;padding:12px 16px 12px 44px;border:2px solid #e4e6eb;border-radius:25px;font-size:15px;box-sizing:border-box;transition:all 0.3s;">
                </div>
                <button type="submit" style="background:#2A7FAA;color:white;border:none;border-radius:25px;padding:12px 24px;cursor:pointer;font-size:15px;white-space:nowrap;font-weight:600;">
                    <i class="fas fa-search"></i> Rechercher
                </button>
                <?php if ($search): ?>
                <a href="index.php?page=metiers" style="background:#e4e6eb;color:#333;border-radius:25px;padding:12px 16px;text-decoration:none;font-size:14px;">✕ Effacer</a>
                <?php endif; ?>
            </form>
            <?php if ($search): ?>
            <div style="margin-top:10px;color:#65676b;font-size:14px;">
                <i class="fas fa-info-circle"></i> <?= count($metiers) ?> résultat(s) pour "<strong><?= htmlspecialchars($search) ?></strong>"
            </div>
            <?php endif; ?>
        </div>

        <!-- LISTE PAR CATÉGORIE -->
        <?php if (empty($grouped)): ?>
        <div style="text-align:center;padding:50px;background:white;border-radius:12px;">
            <i class="fas fa-search fa-3x" style="color:#ccc;margin-bottom:15px;display:block;"></i>
            <p style="color:#65676b;font-size:16px;">Aucun métier trouvé pour "<?= htmlspecialchars($search) ?>"</p>
            <a href="index.php?page=metiers" style="color:#2A7FAA;">Voir tous les métiers</a>
        </div>
        <?php else: ?>
        <?php foreach ($grouped as $categorie => $items):
            $icon = $icons[$categorie] ?? 'fa-stethoscope'; ?>
        <div class="metier-card">
            <div class="metier-header" style="background:linear-gradient(135deg,#2A7FAA,#4CAF50);">
                <div class="metier-icon">
                    <i class="fas <?= $icon ?>" style="color:white;"></i>
                </div>
                <div style="flex:1;">
                    <h3 style="margin:0;color:white;font-size:17px;font-weight:700;"><?= htmlspecialchars($categorie) ?></h3>
                    <span style="color:rgba(255,255,255,0.8);font-size:13px;"><?= count($items) ?> métier(s)</span>
                </div>
            </div>
            <div style="padding:20px;display:flex;flex-wrap:wrap;gap:10px;">
                <?php foreach ($items as $metier): ?>
                <span class="metier-badge">
                    <i class="fas fa-user-md" style="font-size:12px;"></i>
                    <?= htmlspecialchars($metier['nom']) ?>
                </span>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
        <?php
        $content = ob_get_clean();
        $this->renderPublicView('Métiers médicaux', $content);
    }

    // =============================================
    // API — LIKE/DISLIKE ARTICLE
    // =============================================

    public function apiArticleLike(): void {
        header('Content-Type: application/json');
        if (empty($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Non connecté']);
            exit;
        }
        require_once __DIR__ . '/../models/Article.php';
        $data      = json_decode(file_get_contents('php://input'), true) ?? [];
        $articleId = (int)($data['article_id'] ?? 0);
        $type      = $data['type'] ?? '';
        if (!$articleId || !in_array($type, ['like','dislike'])) {
            echo json_encode(['success' => false, 'message' => 'Paramètres invalides']);
            exit;
        }
        $articleModel = new Article();
        $result = $articleModel->toggleLike($articleId, (int)$_SESSION['user_id'], $type);
        echo json_encode(['success' => true] + $result);
        exit;
    }

    // =============================================
    // API — LIKE/DISLIKE COMMENTAIRE
    // =============================================

    public function apiReplyLike(): void {
        header('Content-Type: application/json');
        if (empty($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Non connecté']);
            exit;
        }
        require_once __DIR__ . '/../models/Reply.php';
        $data    = json_decode(file_get_contents('php://input'), true) ?? [];
        $replyId = (int)($data['reply_id'] ?? 0);
        $type    = $data['type'] ?? '';
        if (!$replyId || !in_array($type, ['like','dislike'])) {
            echo json_encode(['success' => false, 'message' => 'Paramètres invalides']);
            exit;
        }
        $replyModel = new Reply();
        $result = $replyModel->toggleLike($replyId, (int)$_SESSION['user_id'], $type);
        echo json_encode(['success' => true] + $result);
        exit;
    }

    public function apiReplyUpdate(): void {
        header('Content-Type: application/json');
        if (empty($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Non connecté']);
            exit;
        }
        $replyId = (int)($_GET['id'] ?? 0);
        if (!$replyId) {
            echo json_encode(['success' => false, 'message' => 'ID invalide']);
            exit;
        }
        require_once __DIR__ . '/../models/Reply.php';
        $replyModel = new Reply();
        $reply = $replyModel->getById($replyId);
        if (!$reply) {
            echo json_encode(['success' => false, 'message' => 'Commentaire introuvable']);
            exit;
        }
        // Vérifier propriété ou admin
        $isAdmin = ($_SESSION['user_role'] ?? '') === 'admin';
        if (!$isAdmin && $reply['user_id'] != $_SESSION['user_id']) {
            echo json_encode(['success' => false, 'message' => 'Non autorisé']);
            exit;
        }
        $text = trim($_POST['reply_text'] ?? '');
        $imagePath = $reply['image'] ?? null;
        // Upload image si fournie
        if (isset($_FILES['reply_image']) && $_FILES['reply_image']['error'] === UPLOAD_ERR_OK) {
            $newImage = $this->uploadReplyImage($_FILES['reply_image']);
            if ($newImage) $imagePath = $newImage;
        }
        if (empty($text) && empty($imagePath)) {
            echo json_encode(['success' => false, 'message' => 'Commentaire vide']);
            exit;
        }
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("UPDATE replies SET replay=?, image=? WHERE id=?");
            $stmt->execute([$text ?: null, $imagePath, $replyId]);
            echo json_encode(['success' => true, 'message' => 'Commentaire modifié']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    // =============================================
    // HELPER — CONVERTIR JSON QUILL EN HTML
    // =============================================

    private function quillToHtml(?string $contenu): string
    {
        if (empty($contenu)) return '';

        // Essayer de décoder le JSON Quill
        $decoded = json_decode($contenu, true);

        if (json_last_error() === JSON_ERROR_NONE && isset($decoded['ops']) && is_array($decoded['ops'])) {
            $html = '';
            foreach ($decoded['ops'] as $op) {
                if (!isset($op['insert'])) continue;

                if (is_string($op['insert'])) {
                    $text  = htmlspecialchars($op['insert'], ENT_QUOTES, 'UTF-8');
                    $attrs = $op['attributes'] ?? [];

                    // Formatage
                    if (!empty($attrs['bold']))        $text = '<strong>' . $text . '</strong>';
                    if (!empty($attrs['italic']))       $text = '<em>' . $text . '</em>';
                    if (!empty($attrs['underline']))    $text = '<u>' . $text . '</u>';
                    if (!empty($attrs['strike']))       $text = '<s>' . $text . '</s>';
                    if (!empty($attrs['code']))         $text = '<code>' . $text . '</code>';
                    if (!empty($attrs['link']))         $text = '<a href="' . htmlspecialchars($attrs['link']) . '" target="_blank">' . $text . '</a>';
                    if (!empty($attrs['color']))        $text = '<span style="color:' . htmlspecialchars($attrs['color']) . '">' . $text . '</span>';
                    if (!empty($attrs['background']))   $text = '<span style="background:' . htmlspecialchars($attrs['background']) . '">' . $text . '</span>';

                    // Header
                    if (!empty($attrs['header'])) {
                        $level = (int)$attrs['header'];
                        $text  = '<h' . $level . '>' . $text . '</h' . $level . '>';
                    }

                    // List
                    if (!empty($attrs['list'])) {
                        $tag  = ($attrs['list'] === 'ordered') ? 'ol' : 'ul';
                        $text = '<' . $tag . '><li>' . $text . '</li></' . $tag . '>';
                    }

                    // Remplacer les sauts de ligne
                    $text = str_replace("\n", '<br>', $text);
                    $html .= $text;

                } elseif (is_array($op['insert'])) {
                    // Image embarquée (base64 ou URL)
                    if (isset($op['insert']['image'])) {
                        $src   = $op['insert']['image'];
                        $html .= '<img src="' . $src . '" style="max-width:100%;border-radius:8px;margin:10px 0;display:block;">';
                    }
                    // Video
                    if (isset($op['insert']['video'])) {
                        $src   = htmlspecialchars($op['insert']['video']);
                        $html .= '<video src="' . $src . '" controls style="max-width:100%;border-radius:8px;margin:10px 0;"></video>';
                    }
                }
            }
            return $html;
        }

        // Si c'est du texte simple ou HTML
        return nl2br(htmlspecialchars($contenu, ENT_QUOTES, 'UTF-8'));
    }

} // FIN DE LA CLASSE FrontController