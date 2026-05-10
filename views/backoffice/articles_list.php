<?php
require_once __DIR__ . '/../../models/Article.php';

$articleModel = new Article();
$articles = $articleModel->getAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Valorys — Gestion Articles</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <?php require_once __DIR__ . '/../partials/backoffice_shell_styles.php'; ?>
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body.bo-shell-body{background:#f0f4f8;font-family:'Segoe UI',sans-serif}
        .topbar{background:#fff;border-radius:16px;padding:14px 22px;margin-bottom:24px;display:flex;justify-content:space-between;align-items:center;box-shadow:0 2px 8px rgba(0,0,0,.05)}
        .topbar-title{font-size:17px;font-weight:700;color:#0f2b3d}
        .admin-badge{display:flex;align-items:center;gap:10px;font-size:13px;font-weight:500}
        .avatar{width:38px;height:38px;border-radius:50%;background:linear-gradient(135deg,#2A7FAA,#4CAF50);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700}
        .card-box{background:#fff;border-radius:16px;padding:20px;box-shadow:0 2px 8px rgba(0,0,0,.05);margin-bottom:24px}
        .card-box-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:16px}
        .card-box-header h5{font-size:15px;font-weight:700;color:#0f2b3d}
        .table thead th{font-size:11px;font-weight:700;color:#6c757d;background:#f8f9fc;padding:10px 14px;white-space:nowrap;text-transform:uppercase;letter-spacing:.5px}
        .table tbody td{padding:12px 14px;font-size:13px;vertical-align:middle}
        .table tbody tr{border-bottom:1px solid #f0f4f8;transition:.1s}
        .table tbody tr:hover{background:#f8f9fc}
        .action-btns{display:flex;gap:6px}
        .btn-act{border:none;border-radius:8px;padding:5px 10px;font-size:12px;cursor:pointer;transition:.2s;font-weight:600}
        .btn-edit{background:#e3f2fd;color:#1565c0}.btn-edit:hover{background:#1565c0;color:#fff}
        .btn-del{background:#fce4ec;color:#c62828}.btn-del:hover{background:#c62828;color:#fff}
        .btn-primary-custom{background:linear-gradient(135deg,#2A7FAA,#4CAF50);color:#fff;border:none;border-radius:30px;padding:9px 20px;font-size:13px;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:6px;transition:opacity .2s;box-shadow:0 4px 10px rgba(42,127,170,.3)}
        .btn-primary-custom:hover{opacity:.9}
        .alert{padding:12px 16px;border-radius:8px;margin-bottom:16px}
        .alert-info{background:#e3f2fd;color:#1565c0;border-left:4px solid #1565c0}
    </style>
</head>
<body class="bo-shell-body">
<?php require_once __DIR__ . '/sidebar.php'; ?>

    <div class="main">
        <div class="topbar">
            <div class="topbar-title"><i class="fas fa-newspaper"></i> Gestion des Articles</div>
            <div class="admin-badge">
                <div class="avatar"><?php echo substr($_SESSION['user_name'] ?? 'A', 0, 1); ?></div>
                <div><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?></div>
            </div>
        </div>
        
        <div class="card-box">
            <div class="card-box-header">
                <h5><i class="fas fa-newspaper"></i> Liste des Articles</h5>
                <a href="index.php?page=articles_admin&action=create" class="btn-primary-custom">
                    <i class="fas fa-plus"></i> Nouvel Article
                </a>
            </div>
            
            <?php if(empty($articles)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> Aucun article disponible pour le moment.
                </div>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th style="width: 40%;">Titre</th>
                            <th style="width: 15%;">Auteur</th>
                            <th style="width: 12%;">Date</th>
                            <th style="width: 8%;">Vues</th>
                            <th style="width: 10%;">Statut</th>
                            <th style="width: 15%;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($articles as $article): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($article['titre']); ?></strong>
                                    <?php if(!empty($article['categorie'])): ?>
                                        <br><small class="text-muted"><i class="fas fa-tag"></i> <?php echo htmlspecialchars($article['categorie']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($article['auteur_name'] ?? 'Valorys'); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($article['created_at'])); ?></td>
                                <td>
                                    <span class="badge bg-info"><?php echo $article['vues'] ?? 0; ?></span>
                                </td>
                                <td>
                                    <?php 
                                    $status = $article['status'] ?? 'brouillon';
                                    $badgeClass = match($status) {
                                        'publié' => 'bg-success',
                                        'archive' => 'bg-secondary',
                                        default => 'bg-warning'
                                    };
                                    ?>
                                    <span class="badge <?php echo $badgeClass; ?>"><?php echo ucfirst($status); ?></span>
                                </td>
                                <td>
                                    <div class="action-btns">
                                        <a href="index.php?page=articles_admin&action=edit&id=<?php echo $article['id']; ?>" 
                                           class="btn-act btn-edit" title="Modifier">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button class="btn-act btn-del" onclick="confirmDelete(<?php echo $article['id']; ?>)" title="Supprimer">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
    function confirmDelete(id) {
        if(confirm('Êtes-vous sûr de vouloir supprimer cet article ?')) {
            window.location.href = 'index.php?page=articles_admin&action=delete&id=' + id;
        }
    }
    </script>
</body>
</html>

