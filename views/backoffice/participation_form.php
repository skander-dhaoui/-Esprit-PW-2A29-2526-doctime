<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/Participation.php';
require_once __DIR__ . '/../../models/Event.php';
require_once __DIR__ . '/../../models/User.php';

$participationModel = new Participation();
$eventModel = new Event();
$userModel = new User();

$participation = null;
$isEdit = false;
$title = "Nouvelle Participation";

// Si on édite
if (!empty($_GET['id'])) {
    $isEdit = true;
    $db = Database::getInstance();
    $result = $db->query("SELECT * FROM participations WHERE id = ?", [$_GET['id']]);
    if (!empty($result)) {
        $participation = $result[0];
        $title = "Modifier Participation";
    }
}

// Récupérer les événements et utilisateurs pour les dropdowns
$events = $eventModel->getAll();
$db = Database::getInstance();
$users = $db->query("SELECT id, nom, prenom, email FROM users WHERE role != 'admin' ORDER BY nom", []);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #f0f4f8; font-family: 'Segoe UI', sans-serif; }
        .main-content { margin-left: 260px; padding: 30px; }
        
        .page-header { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 30px; 
        }
        .page-title { 
            font-size: 1.8rem; 
            font-weight: 700; 
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .page-title i { color: #17a2b8; }
        
        .form-container { 
            background: white; 
            border-radius: 8px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.08); 
            padding: 30px;
            max-width: 800px;
        }
        
        .form-group { margin-bottom: 20px; }
        .form-label { 
            font-weight: 600; 
            color: #2c3e50; 
            margin-bottom: 8px; 
            display: block;
        }
        .form-control, .form-select { 
            border: 1px solid #e0e0e0; 
            border-radius: 6px; 
            padding: 10px 12px;
            font-size: 0.95rem;
        }
        .form-control:focus, .form-select:focus { 
            border-color: #17a2b8; 
            box-shadow: 0 0 0 3px rgba(23,162,184,0.1);
        }
        
        .error-message { 
            color: #d32f2f; 
            font-size: 0.85rem; 
            margin-top: 5px; 
            display: none;
        }
        .form-control.is-invalid, .form-select.is-invalid { 
            border-color: #d32f2f; 
        }
        .form-control.is-invalid ~ .error-message, 
        .form-select.is-invalid ~ .error-message { 
            display: block; 
        }
        
        .form-actions { 
            display: flex; 
            gap: 10px; 
            margin-top: 30px;
            justify-content: flex-end;
        }
        
        .btn-submit { 
            background: linear-gradient(135deg, #17a2b8, #4CAF50); 
            color: white; 
            border: none; 
            padding: 10px 25px; 
            border-radius: 6px; 
            font-weight: 600; 
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-submit:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 4px 12px rgba(23,162,184,0.3);
            color: white;
        }
        
        .btn-cancel { 
            background: #e0e0e0; 
            color: #2c3e50; 
            border: none; 
            padding: 10px 25px; 
            border-radius: 6px; 
            font-weight: 600; 
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s;
        }
        .btn-cancel:hover { 
            background: #bdbdbd; 
            color: #2c3e50;
        }
        
        .alert { 
            border-radius: 6px; 
            margin-bottom: 20px; 
        }
        
        @media(max-width:768px){ 
            .main-content{ margin-left:0; } 
            .form-actions { flex-direction: column; }
        }
    </style>
</head>
<body>
<?php require_once __DIR__ . '/sidebar.php'; ?>

<div class="main-content">
    
    <!-- Header -->
    <div class="page-header">
        <h1 class="page-title">
            <i class="bi bi-person-plus"></i><?= $title ?>
        </h1>
    </div>

    <!-- Messages -->
    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <i class="bi bi-check-circle"></i> <?= $_SESSION['success'] ?>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <!-- Formulaire -->
    <div class="form-container">
        <form method="POST" action="index.php?page=participations_admin&action=<?= $isEdit && $participation ? 'update&id=' . $participation['id'] : 'create' ?>" novalidate>
            
            <?php if ($isEdit && $participation): ?>
                <input type="hidden" name="id" value="<?= $participation['id'] ?>">
            <?php endif; ?>

            <!-- Événement -->
            <div class="form-group">
                <label class="form-label" for="event_id">Événement *</label>
                <select class="form-select" id="event_id" name="event_id" required>
                    <option value="">-- Sélectionner un événement --</option>
                    <?php foreach ($events as $event): ?>
                        <option value="<?= $event['id'] ?>" 
                            <?= ($isEdit && $participation && $participation['event_id'] == $event['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($event['titre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="error-message">Veuillez sélectionner un événement</div>
            </div>

            <!-- Utilisateur -->
            <div class="form-group">
                <label class="form-label" for="user_id">Participant *</label>
                <select class="form-select" id="user_id" name="user_id" required>
                    <option value="">-- Sélectionner un participant --</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?= $user['id'] ?>" 
                            <?= ($isEdit && $participation && $participation['user_id'] == $user['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?> (<?= htmlspecialchars($user['email']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="error-message">Veuillez sélectionner un participant</div>
            </div>

            <!-- Statut -->
            <div class="form-group">
                <label class="form-label" for="statut">Statut *</label>
                <select class="form-select" id="statut" name="statut" required>
                    <option value="inscrit" <?= (!$isEdit || ($participation && $participation['statut'] === 'inscrit')) ? 'selected' : '' ?>>En attente</option>
                    <option value="confirmé" <?= ($isEdit && $participation && $participation['statut'] === 'confirmé') ? 'selected' : '' ?>>Confirmé</option>
                    <option value="annulé" <?= ($isEdit && $participation && $participation['statut'] === 'annulé') ? 'selected' : '' ?>>Annulé</option>
                </select>
                <div class="error-message">Veuillez sélectionner un statut</div>
            </div>

            <!-- Actions -->
            <div class="form-actions">
                <a href="index.php?page=participations_admin" class="btn-cancel">
                    <i class="bi bi-x"></i> Annuler
                </a>
                <button type="submit" class="btn-submit">
                    <i class="bi bi-check"></i> <?= ($isEdit && $participation) ? 'Modifier' : 'Ajouter' ?>
                </button>
            </div>

        </form>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
