<?php
// views/backoffice/evenements/show.php
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../../../index.php?page=login');
    exit;
}

require_once __DIR__ . '/../../../models/Event.php';
require_once __DIR__ . '/../../../config/database.php';

$id = $_GET['id'] ?? null;

if (!$id || !is_numeric($id)) {
    header('Location: list.php');
    exit;
}

$eventModel = new Event();
$event = $eventModel->getById((int)$id);

if (!$event) {
    header('Location: list.php');
    exit;
}

$page_title = 'Détails de l\'Événement';
$current_page = 'evenements';
?>
<!DOCTYPE html>
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

        .main-content {
            margin-left: 260px;
            flex: 1;
            padding: 30px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .page-header h1 {
            font-size: 28px;
            font-weight: 700;
            color: #1a2035;
            margin: 0;
        }

        .btn-back {
            background: #6c757d;
            border: none;
            color: white;
            padding: 10px 24px;
            border-radius: 30px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: opacity 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-back:hover {
            opacity: 0.9;
            color: white;
        }

        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border: none;
            margin-bottom: 20px;
        }

        .card-header {
            background: linear-gradient(135deg, #2A7FAA, #4CAF50);
            border-radius: 12px 12px 0 0;
            padding: 20px;
            color: white;
        }

        .card-header h4 {
            margin: 0;
            font-size: 18px;
            font-weight: 700;
        }

        .card-body {
            padding: 20px;
        }

        .event-image {
            width: 100%;
            max-width: 400px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .info-group {
            margin-bottom: 20px;
        }

        .info-label {
            font-weight: 600;
            color: #6c757d;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }

        .info-value {
            font-size: 16px;
            color: #1a2035;
            font-weight: 500;
        }

        .info-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
        }

        .badge-à-venir {
            background: #cfe2ff;
            color: #084298;
        }

        .badge-terminé {
            background: #d1e7dd;
            color: #0f5132;
        }

        .badge-annulé {
            background: #f8d7da;
            color: #842029;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #2A7FAA, #4CAF50);
            border: none;
            color: white;
            padding: 10px 24px;
            border-radius: 30px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: opacity 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-primary:hover {
            opacity: 0.9;
            color: white;
        }

        .btn-warning {
            background: #ffc107;
            border: none;
            color: #000;
            padding: 10px 24px;
            border-radius: 30px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: opacity 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-warning:hover {
            opacity: 0.9;
        }

        .btn-danger {
            background: #dc3545;
            border: none;
            color: white;
            padding: 10px 24px;
            border-radius: 30px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: opacity 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-danger:hover {
            opacity: 0.9;
        }

        .description {
            line-height: 1.6;
            color: #555;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <?php include __DIR__ . '/../sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <div class="page-header">
            <h1><i class="fas fa-calendar-check"></i> Détails de l'Événement</h1>
            <a href="index.php?page=evenements_admin" class="btn-back">
                <i class="fas fa-arrow-left"></i> Retour
            </a>
        </div>

        <!-- Event Info -->
        <div class="card">
            <div class="card-header">
                <h4><?= htmlspecialchars($event['titre'] ?? '') ?></h4>
            </div>
            <div class="card-body">
                <?php if (!empty($event['image'])): ?>
                    <img src="<?= htmlspecialchars($event['image']) ?>" alt="" class="event-image">
                <?php endif; ?>

                <div class="info-row">
                    <div class="info-group">
                        <div class="info-label">Statut</div>
                        <div class="info-value">
                            <?php
                                $status = $event['status'] ?? 'à venir';
                                $badge_class = 'badge-' . strtolower(str_replace(' ', '-', $status));
                            ?>
                            <span class="badge <?= $badge_class ?>">
                                <?= ucfirst($status) ?>
                            </span>
                        </div>
                    </div>
                    <div class="info-group">
                        <div class="info-label">Participants</div>
                        <div class="info-value">
                            <?= $event['nb_participants'] ?? 0 ?>
                        </div>
                    </div>
                    <div class="info-group">
                        <div class="info-label">Capacité Maximale</div>
                        <div class="info-value">
                            <?= $event['capacite_max'] ?? 'Illimitée' ?>
                        </div>
                    </div>
                </div>

                <div class="info-group" style="margin-top: 20px;">
                    <div class="info-label">Description</div>
                    <div class="info-value description">
                        <?= htmlspecialchars($event['description'] ?? 'N/A') ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dates and Details -->
        <div class="card">
            <div class="card-header">
                <h4>Informations Générales</h4>
            </div>
            <div class="card-body">
                <div class="info-row">
                    <div class="info-group">
                        <div class="info-label">Date Début</div>
                        <div class="info-value">
                            <?= isset($event['date_debut']) ? 
                                date('d/m/Y H:i', strtotime($event['date_debut'])) : 'N/A' ?>
                        </div>
                    </div>
                    <div class="info-group">
                        <div class="info-label">Date Fin</div>
                        <div class="info-value">
                            <?= isset($event['date_fin']) ? 
                                date('d/m/Y H:i', strtotime($event['date_fin'])) : 'N/A' ?>
                        </div>
                    </div>
                    <div class="info-group">
                        <div class="info-label">Lieu</div>
                        <div class="info-value">
                            <?= htmlspecialchars($event['lieu'] ?? 'N/A') ?>
                        </div>
                    </div>
                </div>

                <div class="info-row">
                    <div class="info-group">
                        <div class="info-label">Adresse</div>
                        <div class="info-value">
                            <?= htmlspecialchars($event['adresse'] ?? 'N/A') ?>
                        </div>
                    </div>
                    <div class="info-group">
                        <div class="info-label">Prix</div>
                        <div class="info-value">
                            <?= $event['prix'] ?? 'Gratuit' ?> TND
                        </div>
                    </div>
                    <div class="info-group">
                        <div class="info-label">Sponsor</div>
                        <div class="info-value">
                            <?= htmlspecialchars($event['sponsor_nom'] ?? 'N/A') ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="action-buttons">
            <a href="index.php?page=evenements_admin&action=edit&id=<?= $event['id'] ?>" class="btn-warning">
                <i class="fas fa-edit"></i> Éditer
            </a>
            <a href="javascript:;" class="btn-danger" onclick="confirmDelete(<?= $event['id'] ?>)">
                <i class="fas fa-trash"></i> Supprimer
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function confirmDelete(id) {
            Swal.fire({
                title: 'Êtes-vous sûr ?',
                text: "Cette action est irréversible et supprimera cet événement !",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: '<i class="fas fa-trash"></i> Oui, supprimer !',
                cancelButtonText: '<i class="fas fa-ban"></i> Annuler'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'index.php?page=evenements_admin&action=delete&id=' + id;
                }
            });
        }
    </script>
</body>
</html>
