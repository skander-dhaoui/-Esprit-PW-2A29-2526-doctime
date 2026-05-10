<?php
// Views: Assistant IA Métiers Créatifs

if (!isset($pageTitle)) $pageTitle = 'Métiers Créatifs – Assistant IA';
if (!isset($specialites)) $specialites = [];
if (!isset($professions)) $professions = [];

// Ensure user is authenticated and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: index.php?page=login');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/theme-mode.css">
    <style>
        .main-content { margin-left: 260px; padding-top: 80px; }
        @media (max-width: 768px) { .main-content { margin-left: 0; } }
    </style>
</head>
<body>
<?php require __DIR__ . '/../sidebar.php'; ?>
<?php require __DIR__ . '/../layout_header.php'; ?>
<div class="main-content">

<div class="container-fluid mt-4">
    <!-- Titre -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h2><i class="fas fa-brain me-2"></i><?= htmlspecialchars($pageTitle) ?></h2>
            <p class="text-muted">Analyse des métiers créatifs et professions en Tunisie</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="index.php?page=carte" class="btn btn-primary">
                <i class="fas fa-map me-2"></i>Retour à la Carte
            </a>
        </div>
    </div>

    <!-- Cartes intro -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card border-left border-primary">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="fas fa-graduation-cap text-primary me-2"></i>Spécialités
                    </h5>
                    <h3 class="text-primary"><?= count($specialites) ?></h3>
                    <small class="text-muted">domaines d'expertise référencés</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-left border-info">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="fas fa-briefcase text-info me-2"></i>Professions
                    </h5>
                    <h3 class="text-info"><?= count($professions) ?></h3>
                    <small class="text-muted">professions actives</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-left border-success">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="fas fa-network-wired text-success me-2"></i>Réseaux
                    </h5>
                    <h3 class="text-success">
                        <?= max(1, (int)(count($specialites) * count($professions) / 10)) ?>
                    </h3>
                    <small class="text-muted">connexions potentielles</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Spécialités -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0">
                        <i class="fas fa-list-check me-2"></i>Spécialités Médicales Disponibles
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($specialites)): ?>
                        <div class="row">
                            <?php foreach ($specialites as $spec): ?>
                                <div class="col-md-6 col-lg-4 mb-3">
                                    <div class="card h-100 border">
                                        <div class="card-body">
                                            <h6 class="card-title">
                                                <i class="fas fa-stethoscope text-primary me-2"></i>
                                                <?= htmlspecialchars($spec['specialite'] ?? 'N/A') ?>
                                            </h6>
                                            <p class="card-text">
                                                <span class="badge bg-primary">
                                                    <?= $spec['total'] ?? 0 ?> événement<?= ($spec['total'] ?? 0) > 1 ? 's' : '' ?>
                                                </span>
                                            </p>
                                            <small class="text-muted">
                                                Domaine d'expertise reconnu
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>Aucune spécialité référencée pour le moment.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Professions -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0">
                        <i class="fas fa-users me-2"></i>Top Professions Représentées (Top 20)
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($professions)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 5%;">#</th>
                                        <th style="width: 40%;">Profession</th>
                                        <th style="width: 15%;">Participants</th>
                                        <th style="width: 40%;">Graphique</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $maxTotal = max(array_column($professions, 'total'));
                                    $index = 1;
                                    foreach ($professions as $prof):
                                    ?>
                                        <tr>
                                            <td><strong><?= $index++ ?></strong></td>
                                            <td>
                                                <i class="fas fa-user-tie me-2 text-primary"></i>
                                                <?= htmlspecialchars($prof['profession'] ?? 'N/A') ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-info">
                                                    <?= $prof['total'] ?? 0 ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="progress" style="height: 25px;">
                                                    <div class="progress-bar bg-primary" 
                                                         style="width: <?= ((($prof['total'] ?? 0) / $maxTotal) * 100) ?>%; 
                                                                display: flex; 
                                                                align-items: center; 
                                                                justify-content: center;
                                                                color: white;
                                                                font-size: 0.8rem;"
                                                         role="progressbar">
                                                        <?= round((($prof['total'] ?? 0) / array_sum(array_column($professions, 'total'))) * 100) ?>%
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>Aucune profession référencée pour le moment.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Insights -->
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0">
                        <i class="fas fa-lightbulb text-warning me-2"></i>Insights & Tendances
                    </h5>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item">
                            <strong>Diversité des Professions</strong><br>
                            <small class="text-muted">
                                <?= count($professions) ?> métiers différents sont représentés dans les événements
                            </small>
                        </li>
                        <li class="list-group-item">
                            <strong>Domaines de Spécialisation</strong><br>
                            <small class="text-muted">
                                <?= count($specialites) ?> domaines d'expertise structurant le secteur médical
                            </small>
                        </li>
                        <li class="list-group-item">
                            <strong>Distribution Géographique</strong><br>
                            <small class="text-muted">
                                Les événements couvrent l'ensemble du territoire tunisien
                            </small>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0">
                        <i class="fas fa-robot text-success me-2"></i>Recommandations IA
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h6>Optimisation des Ressources</h6>
                        <p class="mb-0">
                            Sur la base de l'analyse des professions participantes, 
                            il est recommandé de renforcer les spécialisations sous-représentées.
                        </p>
                    </div>
                    <div class="alert alert-success">
                        <h6>Opportunités de Réseautage</h6>
                        <p class="mb-0">
                            Créer des événements de convergence entre les différents domaines 
                            pour favoriser l'innovation et la collaboration inter-métiers.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .card {
        border: 1px solid #e9ecef;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    }
    
    .card-header {
        border-bottom: 1px solid #e9ecef;
    }
    
    .badge {
        font-size: 0.75rem;
        padding: 0.35rem 0.65rem;
    }
    
    .border-left {
        border-left: 4px solid !important;
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/theme-mode.js"></script>
</div>
</body>
</html>
