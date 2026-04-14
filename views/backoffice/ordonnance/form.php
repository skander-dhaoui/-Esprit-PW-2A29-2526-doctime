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
    <title>Nouvelle ordonnance</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f0f2f5; font-family: 'Segoe UI', sans-serif; }
        .sidebar { width: 260px; background: #1a2035; color: white; position: fixed; height: 100%; }
        .main-content { margin-left: 260px; padding: 20px; }
        .card { border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .form-label { font-weight: 600; }
    </style>
</head>
<body>

<div class="sidebar p-3">
    <h4 class="text-center mb-4">Valorys Admin</h4>
    <hr>
    <a href="index.php?page=dashboard" class="text-white d-block py-2">📊 Dashboard</a>
    <a href="index.php?page=admin_rendezvous" class="text-white d-block py-2">📅 Rendez-vous</a>
    <a href="index.php?page=admin_disponibilite" class="text-white d-block py-2">⏰ Disponibilités</a>
    <a href="index.php?page=admin_ordonnance" class="text-white d-block py-2 bg-primary px-2 rounded">📋 Ordonnances</a>
    <a href="index.php?page=logout" class="text-white d-block py-2 mt-5">🚪 Déconnexion</a>
</div>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-prescription-bottle me-2"></i>Nouvelle ordonnance</h2>
        <a href="index.php?page=admin_ordonnance" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>Retour</a>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="index.php?page=admin_ordonnance&action=store">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Patient *</label>
                        <select name="patient_id" class="form-select" required>
                            <option value="">Sélectionner un patient</option>
                            <?php foreach ($patients as $patient): ?>
                                <option value="<?= $patient['id'] ?>"><?= htmlspecialchars($patient['prenom'] . ' ' . $patient['nom']) ?> (<?= $patient['email'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Médecin *</label>
                        <select name="medecin_id" class="form-select" required>
                            <option value="">Sélectionner un médecin</option>
                            <?php foreach ($medecins as $medecin): ?>
                                <option value="<?= $medecin['id'] ?>">Dr. <?= htmlspecialchars($medecin['prenom'] . ' ' . $medecin['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 mb-3">
                        <label class="form-label">Diagnostic *</label>
                        <textarea name="diagnostic" class="form-control" rows="3" required></textarea>
                    </div>
                    <div class="col-12 mb-3">
                        <label class="form-label">Prescription *</label>
                        <textarea name="contenu" class="form-control" rows="5" required></textarea>
                    </div>
                </div>
                <hr>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Créer l'ordonnance</button>
                <a href="index.php?page=admin_ordonnance" class="btn btn-secondary">Annuler</a>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>