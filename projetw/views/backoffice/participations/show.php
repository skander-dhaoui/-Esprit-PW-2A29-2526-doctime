<?php
// views/backoffice/participations/show.php
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../../../index.php?page=login');
    exit;
}

require_once __DIR__ . '/../../../models/Participation.php';
require_once __DIR__ . '/../../../config/database.php';

$id = $_GET['id'] ?? null;

if (!$id || !is_numeric($id)) {
    header('Location: list.php');
    exit;
}

$participationModel = new Participation();
$participation = $participationModel->getById((int)$id);

if (!$participation) {
    header('Location: list.php');
    exit;
}

$page_title = 'Détails de la Participation';
$current_page = 'participations';
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

        .badge-confirmé {
            background: #d1e7dd;
            color: #0f5132;
        }

        .badge-inscrit {
            background: #cfe2ff;
            color: #084298;
        }

        .badge-présent {
            background: #d1e7dd;
            color: #0f5132;
        }

        .badge-absent {
            background: #f8d7da;
            color: #842029;
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
    </style>
</head>
<body>
    <!-- Sidebar -->
    <?php include '../sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <div class="page-header">
            <h1><i class="fas fa-user-check"></i> Détails de la Participation</h1>
            <a href="list.php" class="btn-back">
                <i class="fas fa-arrow-left"></i> Retour
            </a>
        </div>

        <!-- Participation Info -->
        <div class="card">
            <div class="card-header">
                <h4>Informations Générales</h4>
            </div>
            <div class="card-body">
                <div class="info-row">
                    <div class="info-group">
                        <div class="info-label">ID Participation</div>
                        <div class="info-value">#<?= $participation['id'] ?></div>
                    </div>
                    <div class="info-group">
                        <div class="info-label">Statut</div>
                        <div class="info-value">
                            <?php
                                $statut = $participation['statut'] ?? 'inscrit';
                                $badge_class = 'badge-' . strtolower($statut);
                            ?>
                            <span class="badge <?= $badge_class ?>">
                                <?= ucfirst($statut) ?>
                            </span>
                        </div>
                    </div>
                    <div class="info-group">
                        <div class="info-label">Date d'Inscription</div>
                        <div class="info-value">
                            <?= isset($participation['date_inscription']) ? 
                                date('d/m/Y H:i', strtotime($participation['date_inscription'])) : 'N/A' ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Participant Info -->
        <div class="card">
            <div class="card-header">
                <h4>Informations du Participant</h4>
            </div>
            <div class="card-body">
                <div class="info-row">
                    <div class="info-group">
                        <div class="info-label">Nom Complet</div>
                        <div class="info-value">
                            <?php 
                                $name = '';
                                if (!empty($participation['user_nom'])) {
                                    $name = htmlspecialchars($participation['user_nom'] . ' ' . $participation['user_prenom']);
                                } elseif (!empty($participation['client_nom'])) {
                                    $name = htmlspecialchars($participation['client_nom'] . ' ' . $participation['client_prenom']);
                                }
                                echo $name ?: 'N/A';
                            ?>
                        </div>
                    </div>
                    <div class="info-group">
                        <div class="info-label">Email</div>
                        <div class="info-value">
                            <a href="mailto:<?= $participation['client_email'] ?? $participation['user_email'] ?>">
                                <?= htmlspecialchars($participation['client_email'] ?? $participation['user_email'] ?? 'N/A') ?>
                            </a>
                        </div>
                    </div>
                    <div class="info-group">
                        <div class="info-label">Téléphone</div>
                        <div class="info-value">
                            <?= htmlspecialchars($participation['client_telephone'] ?? $participation['user_telephone'] ?? 'N/A') ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Event Info -->
        <div class="card">
            <div class="card-header">
                <h4>Informations de l'Événement</h4>
            </div>
            <div class="card-body">
                <div class="info-row">
                    <div class="info-group">
                        <div class="info-label">Titre de l'Événement</div>
                        <div class="info-value">
                            <?= htmlspecialchars($participation['evenement_titre'] ?? 'N/A') ?>
                        </div>
                    </div>
                    <div class="info-group">
                        <div class="info-label">Date de l'Événement</div>
                        <div class="info-value">
                            <?= isset($participation['evenement_date']) ? 
                                date('d/m/Y', strtotime($participation['evenement_date'])) : 'N/A' ?>
                        </div>
                    </div>
                    <div class="info-group">
                        <div class="info-label">Prix Unitaire</div>
                        <div class="info-value">
                            <?= $participation['prix_unitaire'] ?? 'Gratuit' ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="action-buttons">
            <a href="edit.php?id=<?= $participation['id'] ?>" class="btn-warning">
                <i class="fas fa-edit"></i> Éditer
            </a>
            <a href="javascript:;" class="btn-danger" onclick="confirmDelete(<?= $participation['id'] ?>)">
                <i class="fas fa-trash"></i> Supprimer
            </a>
        </div>
    </div>

    <script>
        function confirmDelete(id) {
            if (confirm('Êtes-vous sûr de vouloir supprimer cette participation ?')) {
                fetch('delete.php?id=' + id, {
                    method: 'DELETE'
                }).then(response => {
                    if (response.ok) {
                        window.location.href = 'list.php';
                    }
                });
            }
        }
    </script>
</body>
</html>
