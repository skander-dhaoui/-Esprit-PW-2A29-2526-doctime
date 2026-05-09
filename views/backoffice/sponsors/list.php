<?php
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: index.php?page=login');
    exit;
}

$page_title = 'Gestion des Sponsors';
$current_page = 'sponsors';

// Get counts
$totalSponsors = count($sponsors ?? []);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> - DocTime</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --green:         #4CAF50;
            --green-dark:    #388E3C;
            --navy:          #1a2035;
            --teal:          #0fa99b;
            --teal-dark:     #0d8a7d;
            --orange:        #f59e0b;
            --blue:          #3b82f6;
            --red:           #ef4444;
            --gray-50:       #f8fafc;
            --gray-100:      #f1f5f9;
            --gray-200:      #e2e8f0;
            --gray-500:      #64748b;
            --gray-700:      #334155;
            --gray-900:      #0f172a;
            --white:         #ffffff;
            --shadow-sm:     0 1px 2px rgba(0,0,0,.05);
            --shadow:        0 1px 6px rgba(0,0,0,.07);
            --radius:        12px;
            --radius-sm:     8px;
        }

        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: var(--gray-50);
            color: var(--gray-900);
            min-height: 100vh;
            display: flex;
        }

        /* Topbar & Search Component */
        .top-navbar {
            background: var(--white);
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--gray-200);
            box-shadow: var(--shadow-sm);
        }

        .header-title-box {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .header-title-box h2 {
            font-size: 18px;
            font-weight: 600;
            margin: 0;
            color: var(--navy);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .time-badge {
            background: var(--teal);
            color: var(--white);
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        /* Main Wrapper */
        .main-content {
            margin-left: 260px;
            flex: 1;
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        .page-container {
            padding: 30px;
            flex: 1;
        }

        /* Card Setup */
        .card {
            background: var(--white);
            border: none;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            margin-bottom: 25px;
            overflow: visible;
        }

        .card-toolbar {
            padding: 24px;
            border-bottom: 1px solid var(--gray-100);
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .card-toolbar-left h4 {
            font-size: 20px;
            font-weight: 600;
            color: var(--navy);
            margin: 0 0 6px 0;
        }

        .card-toolbar-left p {
            font-size: 14px;
            color: var(--gray-500);
            margin: 0;
        }

        .btn-primary {
            background: var(--teal);
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }
        .btn-primary:hover {
            background: var(--teal-dark);
            transform: translateY(-1px);
        }

        /* Table Design */
        .table-responsive {
            padding: 0 24px 24px 24px;
            margin-top: 20px;
        }

        .table {
            margin-bottom: 0;
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .table th {
            background: transparent;
            border-bottom: 1px solid var(--gray-200);
            font-size: 12px;
            font-weight: 600;
            color: var(--gray-500);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 16px 20px;
            white-space: nowrap;
        }

        .table td {
            padding: 16px 20px;
            vertical-align: middle;
            border-bottom: 1px solid var(--gray-100);
            font-size: 14px;
            color: var(--gray-700);
        }

        .table tbody tr:hover td {
            background-color: var(--gray-50);
        }

        .sponsor-name {
            font-weight: 500;
            color: var(--gray-900);
        }

        /* Badges */
        .badge-niveau {
            padding: 6px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
            color: white;
            display: inline-block;
        }

        .badge-niveau.argent { background-color: var(--gray-500); }
        .badge-niveau.platine { background-color: #00bcd4; }
        .badge-niveau.or { background-color: var(--orange); }
        .badge-niveau.bronze { background-color: #d1b48c; }

        .site-link {
            color: var(--blue);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .site-link:hover { text-decoration: underline; }

        /* Action Buttons */
        .actions {
            display: flex;
            gap: 8px;
        }

        .btn-icon {
            width: 32px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            border: 1px solid var(--gray-200);
            background: var(--white);
            color: var(--gray-500);
            transition: all 0.2s;
            cursor: pointer;
            text-decoration: none;
        }

        .btn-icon:hover {
            background: var(--gray-50);
        }

        .bi-edit { color: var(--blue); border-color: rgba(59, 130, 246, 0.2); background: rgba(59, 130, 246, 0.05); }
        .bi-edit:hover { background: rgba(59, 130, 246, 0.1); color: var(--blue); }

        .bi-delete { color: var(--red); border-color: rgba(239, 68, 68, 0.2); background: rgba(239, 68, 68, 0.05); }
        .bi-delete:hover { background: rgba(239, 68, 68, 0.1); color: var(--red); }

    </style>
</head>
<body>

    <?php include __DIR__ . '/../sidebar.php'; ?>

    <div class="main-content">
        <!-- Top Navbar -->
        <div class="top-navbar">
            <div class="header-title-box">
                <h2>
                    <i class="fas fa-chart-line" style="color:var(--green)"></i>
                    Gestion des Sponsors
                </h2>
            </div>
            <div class="time-badge">
                <i class="far fa-clock"></i>
                <span id="currentTime"><?= date('d/m/Y H:i') ?></span>
            </div>
        </div>

        <div class="page-container">
            <div class="card">
                <div class="card-toolbar">
                    <div class="card-toolbar-left">
                        <h4>Liste des Sponsors</h4>
                        <p><?= $totalSponsors ?> sponsor(s) enregistré(s)</p>
                    </div>
                    <div class="card-toolbar-right">
                        <a href="index.php?page=sponsors&action=create" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Nouveau sponsor
                        </a>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>NOM</th>
                                <th>EMAIL</th>
                                <th>TÉLÉPHONE</th>
                                <th>NIVEAU</th>
                                <th>MONTANT (TND)</th>
                                <th>SITE WEB</th>
                                <th>ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($sponsors)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">Aucun sponsor trouvé.</td>
                            </tr>
                            <?php else: ?>
                                <?php foreach($sponsors as $s): 
                                    $niveauClass = match(strtolower($s['niveau'] ?? '')) {
                                        'or' => 'or',
                                        'argent' => 'argent',
                                        'platine' => 'platine',
                                        'bronze' => 'bronze',
                                        default => 'argent'
                                    };
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($s['id']) ?></td>
                                    <td class="sponsor-name"><?= htmlspecialchars($s['nom']) ?></td>
                                    <td><?= htmlspecialchars($s['email'] ?? '--') ?></td>
                                    <td><?= htmlspecialchars($s['telephone'] ?? '--') ?></td>
                                    <td>
                                        <span class="badge-niveau <?= $niveauClass ?>">
                                            <?= htmlspecialchars(ucfirst($s['niveau'] ?? 'Argent')) ?>
                                        </span>
                                    </td>
                                    <td><?= number_format($s['budget'] ?? 0, 2, ',', ' ') ?></td>
                                    <td>
                                        <?php if (!empty($s['site_web'])): ?>
                                            <a href="<?= htmlspecialchars($s['site_web']) ?>" target="_blank" class="site-link">
                                                <i class="fas fa-link"></i> Voir
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="actions">
                                            <a href="index.php?page=sponsors&action=edit&id=<?= $s['id'] ?>" class="btn-icon bi-edit" title="Modifier">
                                                <i class="fas fa-pen"></i>
                                            </a>
                                            <a href="#" onclick="confirmDelete(event, <?= $s['id'] ?>)" class="btn-icon bi-delete" title="Supprimer">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <form id="delete-form" method="POST" style="display:none;">
        <input type="hidden" name="action" value="delete">
        <!-- We link to index.php?page=sponsors&action=delete&id=X in JS -->
    </form>

    <?php if (isset($flash)): ?>
        <script>
            Swal.fire({
                title: '<?= $flash['type'] === 'success' ? 'Succès!' : 'Erreur!' ?>',
                text: '<?= addslashes($flash['message']) ?>',
                icon: '<?= $flash['type'] ?>',
                confirmButtonColor: '#0fa99b'
            });
        </script>
    <?php endif; ?>

    <script>
        // Update time
        setInterval(() => {
            const now = new Date();
            const timeStr = now.toLocaleDateString('fr-FR') + ' ' + 
                            now.getHours().toString().padStart(2, '0') + ':' + 
                            now.getMinutes().toString().padStart(2, '0');
            document.getElementById('currentTime').textContent = timeStr;
        }, 60000);

        function confirmDelete(e, id) {
            e.preventDefault();
            Swal.fire({
                title: 'Êtes-vous sûr ?',
                text: "Cette action supprimera définitivement le sponsor.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Oui, supprimer',
                cancelButtonText: 'Annuler'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `index.php?page=sponsors&action=delete&id=${id}`;
                }
            });
        }
    </script>
</body>
</html>
