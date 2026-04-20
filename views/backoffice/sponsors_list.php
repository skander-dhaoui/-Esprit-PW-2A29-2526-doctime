<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Sponsors</title>
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
        
        .sponsor-name { 
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
        .status-actif { 
            background: #d4edda; 
            color: #155724; 
        }
        .status-inactif { 
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
            <i class="bi bi-handshake"></i>Gestion des Sponsors
        </h1>
        <a href="index.php?page=sponsors_admin&action=create" class="btn-new">
            <i class="bi bi-plus-lg"></i>Nouveau sponsor
        </a>
    </div>

    <!-- List -->
    <div class="list-header">
        <h2 class="list-title">Liste des Sponsors</h2>
        <p class="list-subtitle"><?= count($sponsors) ?> sponsor(s)</p>
    </div>

    <!-- Tableau -->
    <div class="table-container">
        <?php if (count($sponsors) > 0): ?>
            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 5%;">#</th>
                        <th style="width: 18%;">SPONSOR</th>
                        <th style="width: 16%;">EMAIL</th>
                        <th style="width: 12%;">TÉLÉPHONE</th>
                        <th style="width: 12%;">SECTEUR</th>
                        <th style="width: 12%;">BUDGET</th>
                        <th style="width: 10%;">STATUT</th>
                        <th style="width: 8%;">ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sponsors as $index => $s): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td>
                                <span class="sponsor-name">
                                    <?= htmlspecialchars($s['nom'] ?? 'Sponsor') ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($s['email'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($s['telephone'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($s['secteur'] ?? '-') ?></td>
                            <td>
                                <strong><?= !empty($s['budget']) ? number_format($s['budget'], 0, ',', ' ') . ' €' : '-' ?></strong>
                            </td>
                            <td>
                                <?php 
                                $statusClass = 'status-inactif';
                                $statusText = 'Inactif';
                                
                                if ($s['statut'] === 'actif') {
                                    $statusClass = 'status-actif';
                                    $statusText = 'Actif';
                                }
                                ?>
                                <span class="status-badge <?= $statusClass ?>">
                                    <?= $statusText ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="index.php?page=sponsors_admin&action=edit&id=<?= $s['id'] ?>" class="btn-action btn-edit" title="Éditer">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button type="button" class="btn-action btn-delete" title="Supprimer" data-bs-toggle="modal" data-bs-target="#deleteModal<?= $s['id'] ?>">
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
                <p><strong>Aucun sponsor trouvé</strong></p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modales de suppression -->
    <?php if (count($sponsors) > 0): ?>
        <?php foreach ($sponsors as $s): ?>
            <div class="modal fade" id="deleteModal<?= $s['id'] ?>" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content" style="border-radius: 10px; border: none; box-shadow: 0 10px 40px rgba(0,0,0,0.2);">
                        <div class="modal-header" style="background: linear-gradient(135deg, #d32f2f, #f44336); color: white; border: none;">
                            <h5 class="modal-title" style="font-weight: 700; font-size: 1.3rem;">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>Supprimer le sponsor
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body" style="padding: 25px; text-align: center;">
                            <div style="background: #ffebee; width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px; font-size: 1.8rem; color: #d32f2f;">
                                <i class="bi bi-trash"></i>
                            </div>
                            <h6 style="color: #2c3e50; font-weight: 600; margin-bottom: 10px;">Êtes-vous sûr?</h6>
                            <p style="color: #7f8c8d; font-size: 0.95rem; margin: 0;">
                                Cette action va supprimer le sponsor <strong><?= htmlspecialchars($s['nom']) ?></strong>. Cette opération est irréversible.
                            </p>
                        </div>
                        <div class="modal-footer" style="border-top: 1px solid #e9ecef; padding: 15px; background: #f8f9fa;">
                            <button type="button" class="btn" style="background: #e0e0e0; color: #2c3e50; font-weight: 600; border: none; padding: 8px 20px; border-radius: 6px;" data-bs-dismiss="modal">
                                Annuler
                            </button>
                            <a href="index.php?page=sponsors_admin&action=delete&id=<?= $s['id'] ?>" class="btn" style="background: #d32f2f; color: white; font-weight: 600; border: none; padding: 8px 20px; border-radius: 6px;">
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
