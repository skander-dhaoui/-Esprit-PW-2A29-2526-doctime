<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/Participation.php';

// Récupérer les participations avec requête simplifiée
$db = Database::getInstance();
$sql = "SELECT p.id, p.event_id, p.user_id, p.statut,
               e.titre as event_titre,
               u.nom, u.prenom, u.email, u.telephone, u.role
        FROM participations p
        LEFT JOIN events e ON p.event_id = e.id
        LEFT JOIN users u ON p.user_id = u.id
        ORDER BY p.id DESC
        LIMIT 100";

try {
    $participations = $db->query($sql, []);
    if (!is_array($participations)) {
        $participations = [];
    }
} catch (Exception $e) {
    error_log('Erreur récupération participations: ' . $e->getMessage());
    $participations = [];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Participations</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #f0f4f8; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .main-content { margin-left: 260px; padding: 30px; }
        
        .page-header { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 30px; 
            padding: 20px 0;
        }
        .page-title { 
            font-size: 2rem; 
            font-weight: 700; 
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .page-title i { color: #17a2b8; font-size: 2.2rem; }
        
        .btn-new { 
            background: linear-gradient(135deg, #17a2b8, #4CAF50); 
            color: white; 
            border: none; 
            padding: 10px 20px; 
            border-radius: 6px; 
            cursor: pointer; 
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            transition: all 0.3s;
        }
        .btn-new:hover { color: white; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(23,162,184,0.3); }
        
        .list-header { 
            margin-bottom: 20px;
            padding-left: 15px;
        }
        .list-title { 
            font-size: 1.3rem; 
            font-weight: 600; 
            color: #2c3e50;
            margin-bottom: 5px;
        }
        .list-subtitle { 
            font-size: 0.95rem; 
            color: #7f8c8d;
        }
        
        .table-container { 
            background: white; 
            border-radius: 8px; 
            box-shadow: 0 2px 8px rgba(0,0,0,0.08); 
            overflow: hidden;
        }
        
        .table { 
            margin: 0; 
            font-size: 0.95rem;
        }
        .table thead { 
            background: #f8f9fa; 
            border-bottom: 2px solid #e9ecef;
        }
        .table thead th { 
            color: #495057; 
            font-weight: 600; 
            padding: 15px; 
            border: none; 
            text-transform: uppercase; 
            font-size: 0.85rem; 
            letter-spacing: 0.5px;
        }
        .table tbody td { 
            padding: 12px 15px; 
            vertical-align: middle; 
            border-color: #e9ecef; 
        }
        .table tbody tr:hover { background: #f8f9fa; }
        .table tbody tr:last-child td { border-bottom: none; }
        
        .participant-name { 
            font-weight: 600; 
            color: #2c3e50;
        }
        
        .status-badge { 
            padding: 6px 12px; 
            border-radius: 4px; 
            font-size: 0.85rem; 
            font-weight: 600; 
            display: inline-block;
        }
        .status-pending { 
            background: #fff3cd; 
            color: #856404; 
        }
        .status-confirmed { 
            background: #d4edda; 
            color: #155724; 
        }
        .status-cancelled { 
            background: #f8d7da; 
            color: #721c24; 
        }
        
        .action-buttons { 
            display: flex; 
            gap: 8px; 
        }
        .btn-action { 
            width: 35px; 
            height: 35px; 
            padding: 0; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            border: 1px solid; 
            border-radius: 4px; 
            cursor: pointer; 
            transition: all 0.2s; 
            font-size: 1rem;
        }
        .btn-edit { 
            background: #e3f2fd; 
            color: #1976d2; 
            border-color: #90caf9;
        }
        .btn-edit:hover { 
            background: #1976d2; 
            color: white;
        }
        .btn-delete { 
            background: #ffebee; 
            color: #d32f2f; 
            border-color: #ef9a9a;
        }
        .btn-delete:hover { 
            background: #d32f2f; 
            color: white;
        }
        
        .no-data { 
            text-align: center; 
            padding: 60px 20px; 
            color: #999;
        }
        .no-data i { 
            font-size: 3rem; 
            color: #ddd; 
            margin-bottom: 15px; 
        }
        
        @media(max-width:768px){ 
            .main-content{ margin-left:0; } 
            .page-header { flex-direction: column; gap: 15px; align-items: flex-start; }
        }
    </style>
</head>
<body>
<?php require_once __DIR__ . '/sidebar.php'; ?>

<div class="main-content">
    
    <!-- Header -->
    <div class="page-header">
        <h1 class="page-title">
            <i class="bi bi-people"></i>Gestion des Participations
        </h1>
        <a href="index.php?page=participations_admin&action=create" class="btn-new">
            <i class="bi bi-plus-lg"></i>Nouvelle participation
        </a>
    </div>

    <!-- List -->
    <div class="list-header">
        <h2 class="list-title">Liste des Participations</h2>
        <p class="list-subtitle"><?= count($participations) ?> participant(s)</p>
    </div>

    <!-- Tableau -->
    <div class="table-container">
        <?php if (count($participations) > 0): ?>
            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 5%;">#</th>
                        <th style="width: 18%;">PARTICIPANT</th>
                        <th style="width: 16%;">EMAIL</th>
                        <th style="width: 12%;">TÉLÉPHONE</th>
                        <th style="width: 12%;">PROFESSION</th>
                        <th style="width: 15%;">ÉVÉNEMENT</th>
                        <th style="width: 10%;">STATUT</th>
                        <th style="width: 12%;">DATE INSCRIPTION</th>
                        <th style="width: 8%;">ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($participations as $index => $p): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td>
                                <span class="participant-name">
                                    <?= htmlspecialchars(($p['prenom'] ?? '') . ' ' . ($p['nom'] ?? 'Utilisateur')) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($p['email'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($p['telephone'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($p['role'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($p['event_titre'] ?? 'N/A') ?></td>
                            <td>
                                <?php 
                                $statusClass = 'status-pending';
                                $statusText = 'En attente';
                                
                                if ($p['statut'] === 'confirmé' || strpos($p['statut'] ?? '', 'confirm') !== false) {
                                    $statusClass = 'status-confirmed';
                                    $statusText = 'Confirmé';
                                } elseif ($p['statut'] === 'annulé') {
                                    $statusClass = 'status-cancelled';
                                    $statusText = 'Annulé';
                                }
                                ?>
                                <span class="status-badge <?= $statusClass ?>">
                                    <?= $statusText ?>
                                </span>
                            </td>
                            <td><?= date('d/m/Y') ?></td>
                            <td>
                                <div class="action-buttons">
                                    <a href="index.php?page=participations_admin&action=update&id=<?= $p['id'] ?>" class="btn-action btn-edit" title="Éditer">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button class="btn-action btn-delete" title="Supprimer" data-bs-toggle="modal" data-bs-target="#deleteModal<?= $p['id'] ?>">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="no-data">
                <i class="bi bi-inbox"></i>
                <p><strong>Aucune participation trouvée</strong></p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modales de suppression -->
    <?php if (count($participations) > 0): ?>
        <?php foreach ($participations as $p): ?>
            <div class="modal fade" id="deleteModal<?= $p['id'] ?>" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content" style="border-radius: 10px; border: none; box-shadow: 0 10px 40px rgba(0,0,0,0.2);">
                        <div class="modal-header" style="background: linear-gradient(135deg, #d32f2f, #f44336); color: white; border: none;">
                            <h5 class="modal-title" style="font-weight: 700; font-size: 1.3rem;">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>Supprimer la participation
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body" style="padding: 25px; text-align: center;">
                            <div style="background: #ffebee; width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px; font-size: 1.8rem; color: #d32f2f;">
                                <i class="bi bi-trash"></i>
                            </div>
                            <h6 style="color: #2c3e50; font-weight: 600; margin-bottom: 10px;">Êtes-vous sûr?</h6>
                            <p style="color: #7f8c8d; font-size: 0.95rem; margin: 0;">
                                Cette action va supprimer la participation de <strong><?= htmlspecialchars($p['prenom'] . ' ' . $p['nom']) ?></strong> à l'événement <strong><?= htmlspecialchars($p['event_titre'] ?? 'N/A') ?></strong>. Cette opération est irréversible.
                            </p>
                        </div>
                        <div class="modal-footer" style="border-top: 1px solid #e9ecef; padding: 15px; background: #f8f9fa;">
                            <button type="button" class="btn" style="background: #e0e0e0; color: #2c3e50; font-weight: 600; border: none; padding: 8px 20px; border-radius: 6px;" data-bs-dismiss="modal">
                                Annuler
                            </button>
                            <a href="index.php?page=participations_admin&action=delete&id=<?= $p['id'] ?>" class="btn" style="background: #d32f2f; color: white; font-weight: 600; border: none; padding: 8px 20px; border-radius: 6px;">
                                <i class="bi bi-trash me-2"></i>Supprimer
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
