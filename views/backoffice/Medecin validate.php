<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validation Médecin - MediConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f4f6f9; font-family: 'Segoe UI', sans-serif; }
        .sidebar { position: fixed; top: 0; left: 0; width: 260px; height: 100%; background: #1e2a3e; color: white; z-index: 100; }
        .sidebar-header { padding: 25px 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-header i { font-size: 40px; color: #4CAF50; }
        .sidebar-header h3 { margin: 10px 0 0; font-size: 20px; }
        .sidebar-menu a { display: block; padding: 12px 25px; color: rgba(255,255,255,0.7); text-decoration: none; transition: all 0.3s; }
        .sidebar-menu a:hover, .sidebar-menu a.active { background: rgba(255,255,255,0.1); color: white; border-left: 4px solid #4CAF50; }
        .sidebar-menu i { width: 25px; margin-right: 10px; }
        .main-content { margin-left: 260px; padding: 25px; }
        .navbar-top { background: white; border-radius: 12px; padding: 15px 25px; margin-bottom: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: center; }
        .content-card { background: white; border-radius: 15px; padding: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .info-row { display: flex; padding: 12px 0; border-bottom: 1px solid #eee; }
        .info-label { width: 200px; font-weight: 600; color: #666; }
        .info-value { flex: 1; color: #333; }
        .badge-attente { background: #fff3cd; color: #856404; padding: 8px 15px; border-radius: 20px; }
        .badge-valide  { background: #d4edda; color: #155724; padding: 8px 15px; border-radius: 20px; }
        .badge-refuse  { background: #f8d7da; color: #721c24; padding: 8px 15px; border-radius: 20px; }
        .btn-validate { background: #4CAF50; color: white; border-radius: 10px; padding: 12px 30px; border: none; cursor: pointer; }
        .btn-reject   { background: #dc3545; color: white; border-radius: 10px; padding: 12px 30px; border: none; cursor: pointer; }
        .btn-validate:hover { background: #388E3C; }
        .btn-reject:hover   { background: #c82333; }
        .section-title { font-size: 15px; font-weight: bold; margin: 20px 0 15px; color: #2A7FAA; border-left: 4px solid #4CAF50; padding-left: 10px; }
    </style>
</head>
<body>
<div class="sidebar">
    <div class="sidebar-header">
        <i class="fas fa-stethoscope"></i>
        <h3>MediConnect</h3>
        <small>Back Office</small>
    </div>
    <div class="sidebar-menu">
        <a href="index.php?page=dashboard"><i class="fas fa-tachometer-alt"></i> Tableau de bord</a>
        <a href="index.php?page=users"><i class="fas fa-users"></i> Utilisateurs</a>
        <a href="index.php?page=medecins" class="active"><i class="fas fa-user-md"></i> Médecins</a>
        <a href="index.php?page=logout"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
    </div>
</div>

<div class="main-content">
    <div class="navbar-top">
        <h4><i class="fas fa-user-check me-2"></i> Validation du médecin</h4>
        <a href="index.php?page=medecins" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i> Retour
        </a>
    </div>

    <div class="content-card">
        <!-- En-tête médecin -->
        <div class="text-center mb-4">
            <div style="width:80px;height:80px;background:#2A7FAA;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;color:white;font-size:32px;">
                <i class="fas fa-user-md"></i>
            </div>
            <h3 class="mt-3">Dr. <?= htmlspecialchars($medecin['prenom'] . ' ' . $medecin['nom']) ?></h3>
            <?php if ($medecin['validation'] === 'en_attente'): ?>
                <span class="badge-attente"><i class="fas fa-clock me-1"></i> En attente de validation</span>
            <?php elseif ($medecin['validation'] === 'valide'): ?>
                <span class="badge-valide"><i class="fas fa-check-circle me-1"></i> Validé</span>
            <?php else: ?>
                <span class="badge-refuse"><i class="fas fa-times-circle me-1"></i> Refusé</span>
            <?php endif; ?>
        </div>

        <div class="row">
            <!-- Infos personnelles -->
            <div class="col-md-6">
                <div class="section-title"><i class="fas fa-user-circle me-2"></i>Informations personnelles</div>
                <div class="info-row">
                    <div class="info-label">Nom complet</div>
                    <div class="info-value">Dr. <?= htmlspecialchars($medecin['prenom'] . ' ' . $medecin['nom']) ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Email</div>
                    <div class="info-value"><?= htmlspecialchars($medecin['email']) ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Téléphone</div>
                    <div class="info-value"><?= htmlspecialchars($medecin['telephone']) ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Inscrit le</div>
                    <div class="info-value"><?= date('d/m/Y', strtotime($medecin['created_at'])) ?></div>
                </div>
            </div>

            <!-- Infos professionnelles -->
            <div class="col-md-6">
                <div class="section-title"><i class="fas fa-stethoscope me-2"></i>Informations professionnelles</div>
                <div class="info-row">
                    <div class="info-label">Spécialité</div>
                    <div class="info-value"><strong><?= htmlspecialchars($medecin['specialite']) ?></strong></div>
                </div>
                <div class="info-row">
                    <div class="info-label">N° d'inscription ordre</div>
                    <div class="info-value"><?= htmlspecialchars($medecin['numero_ordre']) ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Tarif consultation</div>
                    <div class="info-value"><?= htmlspecialchars($medecin['tarif']) ?> DT</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Années d'expérience</div>
                    <div class="info-value"><?= htmlspecialchars($medecin['experience']) ?> ans</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Adresse cabinet</div>
                    <div class="info-value"><?= htmlspecialchars($medecin['adresse_cabinet'] ?? 'Non renseignée') ?></div>
                </div>
            </div>
        </div>

        <!-- Boutons d'action -->
        <?php if ($medecin['validation'] === 'en_attente'): ?>
        <div class="d-flex gap-3 mt-4 justify-content-center">
            <a href="index.php?page=medecins&action=approve&id=<?= $medecin['id'] ?>"
               class="btn-validate"
               onclick="return confirm('Valider et activer ce médecin ?')">
                <i class="fas fa-check-circle me-2"></i> Valider et activer
            </a>
            <a href="index.php?page=medecins&action=reject&id=<?= $medecin['id'] ?>"
               class="btn-reject"
               onclick="return confirm('Refuser cette demande ?')">
                <i class="fas fa-times-circle me-2"></i> Rejeter
            </a>
        </div>
        <?php else: ?>
        <div class="text-center mt-4">
            <a href="index.php?page=medecins" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i> Retour à la liste
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>