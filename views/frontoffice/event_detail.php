<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($event['titre']) ?> - Valorys</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f5f7fb; font-family: 'Segoe UI', sans-serif; }
        .navbar { background: #1a2035; }
        .navbar-brand, .nav-link { color: white !important; }
        .event-header { background: linear-gradient(135deg, #2A7FAA 0%, #4CAF50 100%); color: white; padding: 60px 0; margin-bottom: 40px; }
        .event-content { background: white; border-radius: 16px; padding: 35px; box-shadow: 0 5px 15px rgba(0,0,0,0.08); margin-bottom: 30px; }
        .event-title { font-size: 2rem; font-weight: 700; margin-bottom: 20px; }
        .event-meta { font-size: 14px; color: #6c757d; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #eee; }
        .event-meta i { color: #2A7FAA; width: 25px; }
        .event-description { line-height: 1.8; color: #333; margin-bottom: 30px; }
        .sidebar-card { background: white; border-radius: 16px; padding: 20px; margin-bottom: 20px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .price-badge { font-size: 28px; font-weight: bold; color: #2A7FAA; }
        .btn-register { background: linear-gradient(135deg, #2A7FAA, #4CAF50); color: white; border: none; border-radius: 25px; padding: 12px 30px; font-size: 16px; width: 100%; }
        .btn-register:hover { opacity: 0.9; color: white; }
        .btn-unregister { background: #dc3545; color: white; border: none; border-radius: 25px; padding: 12px 30px; font-size: 16px; width: 100%; }
        .btn-unregister:hover { background: #c82333; color: white; }
        .alert-flash { position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px; animation: slideIn 0.3s ease-out; }
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        footer { background: #1a2035; color: white; text-align: center; padding: 30px; margin-top: 50px; }
    </style>
</head>
<body>

<!-- Affichage des messages flash -->
<?php if (isset($_SESSION['flash'])): ?>
    <div class="alert alert-flash alert-<?= $_SESSION['flash']['type'] === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
        <i class="fas fa-<?= $_SESSION['flash']['type'] === 'success' ? 'check-circle' : 'exclamation-circle' ?> me-2"></i>
        <?= htmlspecialchars($_SESSION['flash']['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['flash']); ?>
<?php endif; ?>

<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container">
        <a class="navbar-brand" href="index.php?page=accueil"><i class="fas fa-stethoscope me-2"></i>Valorys</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="index.php?page=accueil">Accueil</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php?page=medecins">Médecins</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php?page=blog_public">Blog</a></li>
                <li class="nav-item"><a class="nav-link active" href="index.php?page=evenements">Événements</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php?page=contact">Contact</a></li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php?page=dashboard" style="color:#ffc107 !important;font-weight:bold;">
                            <i class="fas fa-cogs"></i> Backoffice
                        </a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item"><a class="nav-link" href="index.php?page=logout">Déconnexion</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="index.php?page=login">Connexion</a></li>
                    <li class="nav-item"><a class="nav-link" href="index.php?page=register">Inscription</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<div class="event-header">
    <div class="container">
        <a href="index.php?page=evenements" class="btn btn-light mb-3">
            <i class="fas fa-arrow-left me-2"></i>Retour aux événements
        </a>
        <h1 class="event-title"><?= htmlspecialchars($event['titre']) ?></h1>
    </div>
</div>

<div class="container mb-5">
    <div class="row">
        <div class="col-lg-8">
            <div class="event-content">
                <div class="event-meta">
                    <div class="mb-2">
                        <i class="fas fa-calendar-alt"></i> 
                        <strong>Date :</strong> <?= date('d/m/Y H:i', strtotime($event['date_debut'])) ?> - <?= date('d/m/Y H:i', strtotime($event['date_fin'])) ?>
                    </div>
                    <div class="mb-2">
                        <i class="fas fa-map-marker-alt"></i> 
                        <strong>Lieu :</strong> <?= htmlspecialchars($event['lieu'] ?? 'En ligne') ?>
                        <?php if (!empty($event['adresse'])): ?>
                            <br><small class="text-muted ms-4"><?= htmlspecialchars($event['adresse']) ?></small>
                        <?php endif; ?>
                    </div>
                    <div class="mb-2">
                        <i class="fas fa-users"></i> 
                        <strong>Participants :</strong> <?= $event['nb_participants'] ?? 0 ?> / <?= $event['capacite_max'] ?> places
                        <?php if (($event['capacite_max'] - ($event['nb_participants'] ?? 0)) > 0): ?>
                            <span class="badge bg-success ms-2">
                                <i class="fas fa-check"></i> <?= $event['capacite_max'] - ($event['nb_participants'] ?? 0) ?> places restantes
                            </span>
                        <?php else: ?>
                            <span class="badge bg-danger ms-2">
                                <i class="fas fa-times"></i> Complet
                            </span>
                        <?php endif; ?>
                    </div>
                    <?php if ($event['prix'] > 0): ?>
                        <div class="mb-2">
                            <i class="fas fa-tag"></i> 
                            <strong>Prix :</strong> <?= number_format($event['prix'], 2, ',', ' ') ?> €
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="event-description">
                    <h5>Description</h5>
                    <?= nl2br(htmlspecialchars($event['description'] ?? $event['contenu'] ?? 'Aucune description disponible.')) ?>
                </div>
                
                <?php if ($event['status'] === 'terminé'): ?>
                    <div class="alert alert-secondary">
                        <i class="fas fa-check-circle me-2"></i>
                        Cet événement est terminé. Merci à tous les participants !
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="sidebar-card text-center">
                <?php if ($event['image']): ?>
                    <img src="<?= htmlspecialchars($event['image']) ?>" class="img-fluid rounded mb-3" alt="<?= htmlspecialchars($event['titre']) ?>" style="max-height: 200px; width: auto; object-fit: cover;">
                <?php else: ?>
                    <div class="bg-light rounded mb-3 p-4">
                        <i class="fas fa-calendar-alt fa-4x text-muted"></i>
                    </div>
                <?php endif; ?>
                
                <div class="price-badge">
                    <?php if ($event['prix'] > 0): ?>
                        <?= number_format($event['prix'], 2, ',', ' ') ?> €
                    <?php else: ?>
                        Gratuit
                    <?php endif; ?>
                </div>
                <p class="text-muted mt-2">par personne</p>
                
                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php if ($event['status'] === 'à venir'): ?>
                        <?php if ($isParticipant): ?>
                            <form method="POST" action="index.php?page=evenement_desinscrire" onsubmit="return confirm('Êtes-vous sûr de vouloir annuler votre inscription ?')">
                                <input type="hidden" name="event_id" value="<?= $event['id'] ?>">
                                <button type="submit" class="btn-unregister">
                                    <i class="fas fa-times me-2"></i>Se désinscrire
                                </button>
                            </form>
                            <div class="alert alert-info mt-3 mb-0">
                                <i class="fas fa-check-circle me-2"></i>
                                Vous êtes déjà inscrit à cet événement.
                            </div>
                        <?php else: ?>
                            <form method="POST" action="index.php?page=evenement_inscrire">
                                <input type="hidden" name="event_id" value="<?= $event['id'] ?>">
                                <button type="submit" class="btn-register" <?= ($event['places_restantes'] ?? 0) <= 0 ? 'disabled' : '' ?>>
                                    <i class="fas fa-check-circle me-2"></i>
                                    <?= ($event['places_restantes'] ?? 0) <= 0 ? 'Complet' : "S'inscrire" ?>
                                </button>
                            </form>
                            <?php if (($event['places_restantes'] ?? 0) <= 0): ?>
                                <div class="alert alert-danger mt-3 mb-0">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    Désolé, il n'y a plus de places disponibles.
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="alert alert-secondary mb-0">
                            <i class="fas fa-calendar-check me-2"></i>
                            Événement terminé
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="alert alert-info mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        <a href="index.php?page=login" class="alert-link">Connectez-vous</a> pour vous inscrire à cet événement.
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if (isset($_SESSION['user_id']) && $event['status'] === 'à venir' && $isParticipant): ?>
            <div class="sidebar-card">
                <h5><i class="fas fa-info-circle me-2"></i>Informations pratiques</h5>
                <ul class="list-unstyled mb-0">
                    <li class="mb-2"><i class="fas fa-envelope me-2 text-primary"></i> Une confirmation a été envoyée par email</li>
                    <li class="mb-2"><i class="fas fa-mobile-alt me-2 text-primary"></i> Présentez-vous 15 minutes avant le début</li>
                    <li><i class="fas fa-id-card me-2 text-primary"></i> Munissez-vous de votre pièce d'identité</li>
                </ul>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<footer>
    <div class="container">
        <p>&copy; <?= date('Y') ?> Valorys - Tous droits réservés</p>
        <small>Plateforme médicale en ligne</small>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Auto-hide flash messages after 5 seconds
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert-flash');
        alerts.forEach(function(alert) {
            alert.classList.add('fade');
            setTimeout(function() {
                alert.remove();
            }, 500);
        });
    }, 5000);
</script>
</body>
</html>