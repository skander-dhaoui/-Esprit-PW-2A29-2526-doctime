<?php
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
    <title>Détails de l'événement</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f0f2f5; font-family: 'Segoe UI', sans-serif; }
        .sidebar { width: 260px; background: #1a2035; color: white; position: fixed; height: 100%; }
        .main-content { margin-left: 260px; padding: 20px; }
        .card { border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .info-row { padding: 12px 0; border-bottom: 1px solid #eee; display: flex; }
        .info-label { width: 180px; font-weight: 600; color: #555; }
        .info-value { flex: 1; color: #333; }
        .badge-upcoming { background: #d4edda; color: #155724; padding: 5px 15px; border-radius: 20px; }
        .badge-past { background: #cfe2ff; color: #084298; padding: 5px 15px; border-radius: 20px; }
        .badge-cancelled { background: #f8d7da; color: #721c24; padding: 5px 15px; border-radius: 20px; }
    </style>
</head>
<body>

<div class="sidebar p-3">
    <h4 class="text-center mb-4">Valorys Admin</h4>
    <hr>
    <a href="index.php?page=dashboard" class="text-white d-block py-2">📊 Dashboard</a>
    <a href="index.php?page=admin_events" class="text-white d-block py-2 bg-primary px-2 rounded">📅 Événements</a>
    <a href="index.php?page=logout" class="text-white d-block py-2 mt-5">🚪 Déconnexion</a>
</div>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-calendar-day me-2"></i>Détails de l'événement</h2>
        <div>
            <a href="index.php?page=admin_events&action=edit&id=<?= $event['id'] ?>" class="btn btn-warning"><i class="fas fa-edit me-2"></i>Modifier</a>
            <a href="index.php?page=admin_events" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>Retour</a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 text-center">
                    <?php if ($event['image']): ?>
                        <img src="<?= htmlspecialchars($event['image']) ?>" style="max-width: 100%; border-radius: 12px;" alt="<?= htmlspecialchars($event['titre']) ?>">
                    <?php else: ?>
                        <div style="width: 100%; height: 200px; background: #e9ecef; border-radius: 12px; display: flex; align-items: center; justify-content: center;"><i class="fas fa-calendar-alt fa-4x text-muted"></i></div>
                    <?php endif; ?>
                </div>
                <div class="col-md-8">
                    <h3><?= htmlspecialchars($event['titre']) ?></h3>
                    <div class="mb-3">
                        <span class="<?= match($event['status']) { 'à venir' => 'badge-upcoming', 'terminé' => 'badge-past', 'annulé' => 'badge-cancelled', default => 'badge-secondary' } ?>"><?= $event['status'] ?></span>
                    </div>
                    <div class="info-row"><div class="info-label">Date de début</div><div class="info-value"><?= date('d/m/Y H:i', strtotime($event['date_debut'])) ?></div></div>
                    <div class="info-row"><div class="info-label">Date de fin</div><div class="info-value"><?= date('d/m/Y H:i', strtotime($event['date_fin'])) ?></div></div>
                    <div class="info-row"><div class="info-label">Lieu</div><div class="info-value"><?= htmlspecialchars($event['lieu'] ?? 'Non renseigné') ?></div></div>
                    <div class="info-row"><div class="info-label">Adresse</div><div class="info-value"><?= htmlspecialchars($event['adresse'] ?? 'Non renseignée') ?></div></div>
                    <div class="info-row"><div class="info-label">Capacité</div><div class="info-value"><?= $event['capacite_max'] ? $event['capacite_max'] . ' places' : 'Illimitée' ?></div></div>
                    <div class="info-row"><div class="info-label">Participants</div><div class="info-value"><strong><?= $event['nb_participants'] ?? 0 ?></strong> inscrits</div></div>
                    <div class="info-row"><div class="info-label">Prix</div><div class="info-value"><?= $event['prix'] > 0 ? $event['prix'] . ' €' : 'Gratuit' ?></div></div>
                </div>
            </div>
            <hr>
            <h5>Description</h5>
            <p><?= nl2br(htmlspecialchars($event['description'] ?? '')) ?></p>
            <h5>Contenu détaillé</h5>
            <div><?= nl2br(htmlspecialchars($event['contenu'] ?? '')) ?></div>
            
            <?php if (!empty($participants)): ?>
            <hr>
            <h5><i class="fas fa-users me-2"></i>Liste des participants (<?= count($participants) ?>)</h5>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr><th>Nom</th><th>Email</th><th>Téléphone</th><th>Date d'inscription</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($participants as $p): ?>
                        <tr>
                            <td><?= htmlspecialchars($p['prenom'] . ' ' . $p['nom']) ?></td>
                            <td><?= htmlspecialchars($p['email']) ?></td>
                            <td><?= htmlspecialchars($p['telephone'] ?? '-') ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($p['date_inscription'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>