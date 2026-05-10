<?php
// views/backoffice/participations/edit.php
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

$page_title = 'Éditer Participation';
$current_page = 'participations';

// Traiter la mise à jour
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'statut' => $_POST['statut'] ?? 'inscrit'
    ];

    if ($participationModel->update((int)$id, $data)) {
        header('Location: show.php?id=' . $id . '&success=1');
        exit;
    } else {
        $error = 'Erreur lors de la mise à jour';
    }
}

$success = $_GET['success'] ?? null;
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
            max-width: 100%;
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
            padding: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #1a2035;
            font-size: 14px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            font-family: inherit;
            transition: border-color 0.2s;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #4CAF50;
            outline: none;
            box-shadow: 0 0 0 3px rgba(76,175,80,0.1);
        }

        .form-group input:disabled,
        .form-group textarea:disabled {
            background: #f8f9fa;
            color: #6c757d;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d1e7dd;
            color: #0f5132;
            border: 1px solid #badbcc;
        }

        .alert-error {
            background: #f8d7da;
            color: #842029;
            border: 1px solid #f5c2c7;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 30px;
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
        }

        .btn-cancel {
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

        .btn-cancel:hover {
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
            <h1><i class="fas fa-edit"></i> Éditer Participation</h1>
            <a href="show.php?id=<?= $id ?>" class="btn-back">
                <i class="fas fa-arrow-left"></i> Retour
            </a>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> Participation mise à jour avec succès
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?= $error ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="card">
                <div class="card-header">
                    <h4>Informations de la Participation</h4>
                </div>
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Participant</label>
                            <input type="text" disabled value="<?php 
                                $name = '';
                                if (!empty($participation['user_nom'])) {
                                    $name = htmlspecialchars($participation['user_nom'] . ' ' . $participation['user_prenom']);
                                } elseif (!empty($participation['client_nom'])) {
                                    $name = htmlspecialchars($participation['client_nom'] . ' ' . $participation['client_prenom']);
                                }
                                echo $name ?: 'N/A';
                            ?>">
                        </div>
                        <div class="form-group">
                            <label>Événement</label>
                            <input type="text" disabled value="<?= htmlspecialchars($participation['evenement_titre'] ?? 'N/A') ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="statut">Statut *</label>
                        <select name="statut" id="statut" required>
                            <option value="inscrit" <?= $participation['statut'] === 'inscrit' ? 'selected' : '' ?>>Inscrit</option>
                            <option value="confirmé" <?= $participation['statut'] === 'confirmé' ? 'selected' : '' ?>>Confirmé</option>
                            <option value="présent" <?= $participation['statut'] === 'présent' ? 'selected' : '' ?>>Présent</option>
                            <option value="absent" <?= $participation['statut'] === 'absent' ? 'selected' : '' ?>>Absent</option>
                            <option value="annulé" <?= $participation['statut'] === 'annulé' ? 'selected' : '' ?>>Annulé</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="action-buttons">
                <button type="submit" class="btn-primary">
                    <i class="fas fa-save"></i> Enregistrer
                </button>
                <a href="show.php?id=<?= $id ?>" class="btn-cancel">
                    <i class="fas fa-times"></i> Annuler
                </a>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
