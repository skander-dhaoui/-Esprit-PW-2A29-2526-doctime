<?php
// Vue déprécée - voir layout.php et medecins_list_content.php
?>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> - Valorys</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #f0f2f5; font-family: 'Segoe UI', sans-serif; display: flex; min-height: 100vh; }

        /* Sidebar */
        .sidebar {
            width: 260px;
            min-height: 100vh;
            background: #1a2035;
            color: white;
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 100;
        }

        .sidebar-brand {
            padding: 25px 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }

        .brand-icon {
            width: 55px;
            height: 55px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 12px;
            font-size: 24px;
            color: #4CAF50;
        }

        .sidebar-brand h4 {
            font-size: 18px;
            font-weight: 700;
            margin: 0;
            color: white;
        }

        .sidebar-brand small {
            color: rgba(255,255,255,0.5);
            font-size: 11px;
        }

        .sidebar-nav {
            padding: 20px 0;
            flex: 1;
        }

        .sidebar-nav a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 22px;
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s;
            border-left: 3px solid transparent;
        }

        .sidebar-nav a:hover {
            background: rgba(255,255,255,0.07);
            color: white;
        }

        .sidebar-nav a.active {
            background: rgba(255,255,255,0.1);
            color: white;
            border-left-color: #4CAF50;
        }

        .sidebar-nav a i {
            width: 20px;
            text-align: center;
            font-size: 16px;
        }

        .nav-divider {
            height: 1px;
            background: rgba(255,255,255,0.07);
            margin: 10px 22px;
        }

        /* Main content */
        .main-content {
            margin-left: 260px;
            flex: 1;
            padding: 25px;
            min-height: 100vh;
        }

        .page-header {
            background: white;
            border-radius: 12px;
            padding: 18px 25px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 1px 6px rgba(0,0,0,0.06);
        }

        .page-header h4 {
            font-size: 18px;
            font-weight: 700;
            color: #1a2035;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .page-header h4 i {
            color: #4CAF50;
        }

        .admin-avatar {
            width: 40px;
            height: 40px;
            background: #4CAF50;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 16px;
            font-weight: bold;
            text-decoration: none;
        }

        .admin-avatar:hover {
            background: #2A7FAA;
            color: white;
        }

        .content-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 1px 6px rgba(0,0,0,0.06);
        }

        .card-title-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .card-title-row h5 {
            font-size: 16px;
            font-weight: 600;
            color: #1a2035;
            margin: 0;
        }

        .flash-box {
            border-radius: 10px;
            padding: 12px 16px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .flash-success {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .flash-error {
            background: #fdecea;
            color: #c62828;
        }

        /* Table styles */
        .table thead th {
            background: #1a2035;
            color: white;
            font-weight: 600;
            font-size: 13px;
            padding: 12px 14px;
            border: none;
        }

        .table tbody td {
            vertical-align: middle;
            font-size: 14px;
            padding: 13px 14px;
            color: #333;
        }

        .table tbody tr:hover {
            background: #f8f9ff;
        }

        .badge-actif {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-inactif {
            background: #fdecea;
            color: #c62828;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-attente {
            background: #fff8e1;
            color: #f57f17;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-specialite {
            background: #d1f5e0;
            color: #0d6b37;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .btn-action {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            border: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            cursor: pointer;
            transition: opacity 0.2s;
            text-decoration: none;
            margin: 0 2px;
        }

        .btn-action:hover { opacity: 0.8; }
        .btn-edit { background: #e3f0ff; color: #1565c0; }
        .btn-delete { background: #fdecea; color: #c62828; }
        .btn-view { background: #f3e5f5; color: #6a1b9a; }
        .btn-validate { background: #e8f5e9; color: #2e7d32; }

        .filter-form {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr auto auto;
            gap: 12px;
            margin-bottom: 20px;
            align-items: end;
        }

        .filter-form .form-label {
            font-size: 13px;
            font-weight: 600;
            color: #1a2035;
            margin-bottom: 6px;
        }

        @media (max-width: 992px) {
            .filter-form {
                grid-template-columns: 1fr;
            }
        }

        .pagination-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-top: 20px;
            flex-wrap: wrap;
        }

        .pagination-info {
            font-size: 14px;
            color: #5b6475;
        }

        .pagination-links {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
    </style>
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
        <a href="index.php?page=dashboard">
            <i class="fas fa-th-large"></i> Tableau de bord
        </a>
        <a href="index.php?page=users">
            <i class="fas fa-users"></i> Utilisateurs
        </a>
        <a href="index.php?page=medecins_admin" class="active">
            <i class="fas fa-user-md"></i> Médecins
        </a>
        <a href="index.php?page=patients">
            <i class="fas fa-user-injured"></i> Patients
        </a>
        <a href="index.php?page=rendez_vous_admin">
            <i class="fas fa-calendar-check"></i> Rendez-vous
        </a>
        <a href="index.php?page=ordonnances">
            <i class="fas fa-prescription-bottle"></i> Ordonnances
        </a>
        <a href="index.php?page=produits_admin">
            <i class="fas fa-box"></i> Produits
        </a>
        <a href="index.php?page=articles_admin">
            <i class="fas fa-blog"></i> Blog
        </a>
        <a href="index.php?page=evenements_admin">
            <i class="fas fa-calendar-day"></i> Événements
        </a>
        <div class="nav-divider"></div>
        <a href="index.php?page=stats">
            <i class="fas fa-chart-line"></i> Statistiques
        </a>
        <a href="index.php?page=logs">
            <i class="fas fa-history"></i> Historique
        </a>
        <a href="index.php?page=settings">
            <i class="fas fa-cog"></i> Paramètres
        </a>
        <div class="nav-divider"></div>
        <a href="index.php?page=logout">
            <i class="fas fa-sign-out-alt"></i> Déconnexion
        </a>
    </nav>
</div>

<!-- Main Content -->
<div class="main-content">
    <div class="page-header">
        <h4><i class="fas fa-user-md"></i> Gestion des médecins</h4>
        <a href="index.php?page=mon_profil" class="admin-avatar" title="Mon profil">
            <?= strtoupper(substr($_SESSION['user_name'] ?? 'A', 0, 1)) ?>
        </a>
    </div>

    <?php if (isset($_SESSION['flash'])): ?>
        <div class="flash-box flash-<?= $_SESSION['flash']['type'] === 'error' ? 'error' : 'success' ?>">
            <i class="fas fa-<?= $_SESSION['flash']['type'] === 'error' ? 'times-circle' : 'check-circle' ?> me-2"></i>
            <?= htmlspecialchars($_SESSION['flash']['message']) ?>
        </div>
        <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>

    <div class="content-card">
        <div class="card-title-row">
            <h5><i class="fas fa-list"></i> Liste des médecins (<?= (int) ($pagination['total_items'] ?? count($medecins)) ?>)</h5>
            <a href="index.php?page=medecins_admin&action=add" class="btn btn-success btn-sm">
                <i class="fas fa-plus me-1"></i> Ajouter un médecin
            </a>
        </div>

        <form method="get" class="filter-form">
            <input type="hidden" name="page" value="medecins_admin">
            <div>
                <label class="form-label" for="medecins-q">Recherche</label>
                <input id="medecins-q" type="text" name="q" class="form-control" placeholder="Nom, email, téléphone, spécialité..." value="<?= htmlspecialchars($filters['q'] ?? '') ?>">
            </div>
            <div>
                <label class="form-label" for="medecins-sort">Trier par</label>
                <select id="medecins-sort" name="sort" class="form-select">
                    <option value="created_at" <?= ($filters['sort'] ?? '') === 'created_at' ? 'selected' : '' ?>>Date d'inscription</option>
                    <option value="nom" <?= ($filters['sort'] ?? '') === 'nom' ? 'selected' : '' ?>>Nom</option>
                    <option value="email" <?= ($filters['sort'] ?? '') === 'email' ? 'selected' : '' ?>>Email</option>
                    <option value="telephone" <?= ($filters['sort'] ?? '') === 'telephone' ? 'selected' : '' ?>>Téléphone</option>
                    <option value="specialite" <?= ($filters['sort'] ?? '') === 'specialite' ? 'selected' : '' ?>>Spécialité</option>
                    <option value="consultation_prix" <?= ($filters['sort'] ?? '') === 'consultation_prix' ? 'selected' : '' ?>>Tarif</option>
                    <option value="statut" <?= ($filters['sort'] ?? '') === 'statut' ? 'selected' : '' ?>>Statut</option>
                </select>
            </div>
            <div>
                <label class="form-label" for="medecins-direction">Ordre</label>
                <select id="medecins-direction" name="direction" class="form-select">
                    <option value="asc" <?= ($filters['direction'] ?? '') === 'asc' ? 'selected' : '' ?>>Croissant</option>
                    <option value="desc" <?= ($filters['direction'] ?? 'desc') === 'desc' ? 'selected' : '' ?>>Décroissant</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Appliquer</button>
            <a href="index.php?page=medecins_admin" class="btn btn-outline-secondary">Réinitialiser</a>
        </form>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Nom complet</th>
                        <th>Email</th>
                        <th>Téléphone</th>
                        <th>Spécialité</th>
                        <th>Tarif</th>
                        <th>Statut</th>
                        <th>Inscrit le</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($medecins)): ?>
                    <?php foreach ($medecins as $m): ?>
                    <tr>
                        <td><strong>Dr. <?= htmlspecialchars($m['prenom'] . ' ' . $m['nom']) ?></strong></td>
                        <td><?= htmlspecialchars($m['email']) ?></td>
                        <td><?= htmlspecialchars($m['telephone'] ?? '—') ?></td>
                        <td>
                            <?php if (!empty($m['specialite'])): ?>
                                <span class="badge-specialite"><?= htmlspecialchars($m['specialite']) ?></span>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td><?= !empty($m['consultation_prix']) ? htmlspecialchars($m['consultation_prix']) . ' €' : '—' ?></td>
                        <td>
                            <?php if ($m['statut'] === 'actif'): ?>
                                <span class="badge-actif">Actif</span>
                            <?php elseif ($m['statut'] === 'inactif'): ?>
                                <span class="badge-inactif">Inactif</span>
                            <?php else: ?>
                                <span class="badge-attente">En attente</span>
                            <?php endif; ?>
                        </td>
                        <td><?= date('d/m/Y', strtotime($m['created_at'])) ?></td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="index.php?page=medecins_admin&action=show&id=<?= $m['id'] ?>" class="btn-action btn-view" title="Voir">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="index.php?page=medecins_admin&action=edit&id=<?= $m['id'] ?>" class="btn-action btn-edit" title="Modifier">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="index.php?page=medecins_admin&action=delete&id=<?= $m['id'] ?>" class="btn-action btn-delete" title="Supprimer" onclick="return confirm('Supprimer définitivement ce médecin ?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted py-5">
                            <i class="fas fa-user-md fa-2x mb-2 d-block opacity-25"></i>
                            Aucun médecin trouvé
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if (($pagination['total_pages'] ?? 1) > 1): ?>
            <?php $medecinsQuery = ['page' => 'medecins_admin', 'q' => $filters['q'] ?? '', 'sort' => $filters['sort'] ?? 'created_at', 'direction' => $filters['direction'] ?? 'desc']; ?>
            <div class="pagination-bar">
                <div class="pagination-info">
                    Affichage de <?= (int) ($pagination['start_item'] ?? 0) ?> à <?= (int) ($pagination['end_item'] ?? 0) ?> sur <?= (int) ($pagination['total_items'] ?? 0) ?> médecins
                </div>
                <div class="pagination-links">
                    <?php if (!empty($pagination['has_previous'])): ?>
                        <a class="btn btn-outline-primary btn-sm" href="index.php?<?= htmlspecialchars(http_build_query($medecinsQuery + ['p' => $pagination['previous_page']])) ?>">Précédent</a>
                    <?php endif; ?>
                    <?php for ($i = 1; $i <= (int) $pagination['total_pages']; $i++): ?>
                        <a class="btn btn-sm <?= $i === (int) $pagination['current_page'] ? 'btn-primary' : 'btn-outline-primary' ?>" href="index.php?<?= htmlspecialchars(http_build_query($medecinsQuery + ['p' => $i])) ?>"><?= $i ?></a>
                    <?php endfor; ?>
                    <?php if (!empty($pagination['has_next'])): ?>
                        <a class="btn btn-outline-primary btn-sm" href="index.php?<?= htmlspecialchars(http_build_query($medecinsQuery + ['p' => $pagination['next_page']])) ?>">Suivant</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
