<?php
// views/backoffice/patients_list.php
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../../index.php?page=login');
    exit;
}

$page_title = 'Gestion des patients';
$current_page = 'patients';
?>
<?php
// Vue déprécée - voir layout.php et patients_list_content.php
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

        .badge-actif { background: #d4edda; color: #155724; padding: 4px 12px; border-radius: 20px; font-size: 12px; }
        .badge-inactif { background: #f8d7da; color: #721c24; padding: 4px 12px; border-radius: 20px; font-size: 12px; }

        .table thead th {
            background: #1a2035;
            color: white;
            font-weight: 600;
            font-size: 13px;
            padding: 12px 14px;
            border: none;
        }

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
        <a href="index.php?page=dashboard"><i class="fas fa-th-large"></i> Tableau de bord</a>
        <a href="index.php?page=users"><i class="fas fa-users"></i> Utilisateurs</a>
        <a href="index.php?page=medecins_admin"><i class="fas fa-user-md"></i> Médecins</a>
        <a href="index.php?page=patients" class="active"><i class="fas fa-user-injured"></i> Patients</a>
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

<!-- Main Content -->
<div class="main-content">
    <div class="page-header">
        <h4><i class="fas fa-user-injured"></i> Gestion des patients</h4>
        <a href="index.php?page=mon_profil" class="admin-avatar">
            <?= strtoupper(substr($_SESSION['user_name'] ?? 'A', 0, 1)) ?>
        </a>
    </div>

    <div class="content-card">
        <div class="card-title-row">
            <h5><i class="fas fa-list"></i> Liste des patients (<?= (int) ($pagination['total_items'] ?? count($patients ?? [])) ?>)</h5>
            <a href="index.php?page=users&action=create" class="btn btn-success btn-sm">
                <i class="fas fa-plus me-1"></i> Ajouter un patient
            </a>
        </div>

        <form method="get" class="filter-form">
            <input type="hidden" name="page" value="patients">
            <div>
                <label class="form-label" for="patients-q">Recherche</label>
                <input id="patients-q" type="text" name="q" class="form-control" placeholder="Nom, email, téléphone, groupe sanguin..." value="<?= htmlspecialchars($filters['q'] ?? '') ?>">
            </div>
            <div>
                <label class="form-label" for="patients-sort">Trier par</label>
                <select id="patients-sort" name="sort" class="form-select">
                    <option value="created_at" <?= ($filters['sort'] ?? '') === 'created_at' ? 'selected' : '' ?>>Date d'inscription</option>
                    <option value="nom" <?= ($filters['sort'] ?? '') === 'nom' ? 'selected' : '' ?>>Nom</option>
                    <option value="email" <?= ($filters['sort'] ?? '') === 'email' ? 'selected' : '' ?>>Email</option>
                    <option value="telephone" <?= ($filters['sort'] ?? '') === 'telephone' ? 'selected' : '' ?>>Téléphone</option>
                    <option value="groupe_sanguin" <?= ($filters['sort'] ?? '') === 'groupe_sanguin' ? 'selected' : '' ?>>Groupe sanguin</option>
                    <option value="statut" <?= ($filters['sort'] ?? '') === 'statut' ? 'selected' : '' ?>>Statut</option>
                </select>
            </div>
            <div>
                <label class="form-label" for="patients-direction">Ordre</label>
                <select id="patients-direction" name="direction" class="form-select">
                    <option value="asc" <?= ($filters['direction'] ?? '') === 'asc' ? 'selected' : '' ?>>Croissant</option>
                    <option value="desc" <?= ($filters['direction'] ?? 'desc') === 'desc' ? 'selected' : '' ?>>Décroissant</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Appliquer</button>
            <a href="index.php?page=patients" class="btn btn-outline-secondary">Réinitialiser</a>
        </form>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Nom complet</th><th>Email</th>
                        <th>Téléphone</th><th>Groupe sanguin</th><th>Statut</th><th>Inscrit le</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($patients)): ?>
                    <?php foreach ($patients as $p): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($p['prenom'] . ' ' . $p['nom']) ?></strong></td>
                        <td><?= htmlspecialchars($p['email']) ?></td>
                        <td><?= htmlspecialchars($p['telephone'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($p['groupe_sanguin'] ?? '—') ?></td>
                        <td>
                            <span class="badge-actif"><?= $p['statut'] === 'actif' ? 'Actif' : 'Inactif' ?></span>
                        </td>
                        <td><?= date('d/m/Y', strtotime($p['created_at'])) ?></td>
                        <td>
                            <a href="index.php?page=patients&action=edit&id=<?= $p['id'] ?>" class="btn btn-sm btn-primary"><i class="fas fa-edit"></i></a>
                            <a href="index.php?page=patients&action=delete&id=<?= $p['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Supprimer ?')"><i class="fas fa-trash"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7" class="text-center">Aucun patient trouvé</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if (($pagination['total_pages'] ?? 1) > 1): ?>
            <?php $patientsQuery = ['page' => 'patients', 'q' => $filters['q'] ?? '', 'sort' => $filters['sort'] ?? 'created_at', 'direction' => $filters['direction'] ?? 'desc']; ?>
            <div class="pagination-bar">
                <div class="pagination-info">
                    Affichage de <?= (int) ($pagination['start_item'] ?? 0) ?> à <?= (int) ($pagination['end_item'] ?? 0) ?> sur <?= (int) ($pagination['total_items'] ?? 0) ?> patients
                </div>
                <div class="pagination-links">
                    <?php if (!empty($pagination['has_previous'])): ?>
                        <a class="btn btn-outline-primary btn-sm" href="index.php?<?= htmlspecialchars(http_build_query($patientsQuery + ['p' => $pagination['previous_page']])) ?>">Précédent</a>
                    <?php endif; ?>
                    <?php for ($i = 1; $i <= (int) $pagination['total_pages']; $i++): ?>
                        <a class="btn btn-sm <?= $i === (int) $pagination['current_page'] ? 'btn-primary' : 'btn-outline-primary' ?>" href="index.php?<?= htmlspecialchars(http_build_query($patientsQuery + ['p' => $i])) ?>"><?= $i ?></a>
                    <?php endfor; ?>
                    <?php if (!empty($pagination['has_next'])): ?>
                        <a class="btn btn-outline-primary btn-sm" href="index.php?<?= htmlspecialchars(http_build_query($patientsQuery + ['p' => $pagination['next_page']])) ?>">Suivant</a>
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
