<?php
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'medecin') {
    header('Location: index.php?page=login');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer une ordonnance - Valorys</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <?php include __DIR__ . '/../../partials/public_theme_styles.php'; ?>
    <style>
        body { background: #f5f7fb; font-family: 'Segoe UI', sans-serif; }
        .card { border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .card-header { background: linear-gradient(135deg, #2A7FAA, #4CAF50); color: white; border-radius: 12px 12px 0 0 !important; }
        .btn-primary { background: linear-gradient(135deg, #2A7FAA, #4CAF50); border: none; }
        .btn-primary:hover { opacity: 0.9; }
    </style>
</head>
<body>

<?php $navActive = $_GET['page'] ?? ''; include __DIR__ . '/../../partials/nav_public.php'; ?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0"><i class="fas fa-prescription-bottle me-2"></i>Nouvelle ordonnance</h4>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['flash'])): ?>
                        <div class="alert alert-<?= $_SESSION['flash']['type'] === 'error' ? 'danger' : 'success' ?> alert-dismissible fade show">
                            <?= $_SESSION['flash']['message'] ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['flash']); ?>
                    <?php endif; ?>

                    <form method="POST" action="index.php?page=medecin_ordonnances&action=store">
                        <div class="mb-3">
                            <label class="form-label">Patient *</label>
                            <select name="patient_id" class="form-select" required>
                                <option value="">Sélectionner un patient</option>
                                <?php if (isset($patients) && !empty($patients)): ?>
                                    <?php foreach ($patients as $patient): ?>
                                        <option value="<?= $patient['id'] ?>"><?= htmlspecialchars($patient['prenom'] . ' ' . $patient['nom']) ?> (<?= $patient['email'] ?>)</option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Diagnostic *</label>
                            <textarea name="diagnostic" class="form-control" rows="3" placeholder="Diagnostic du patient..." required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Prescription *</label>
                            <textarea name="contenu" class="form-control" rows="5" placeholder="Liste des médicaments et posologies..." required></textarea>
                        </div>
                        <?php if (isset($rdv_id) && $rdv_id): ?>
                            <input type="hidden" name="rdv_id" value="<?= $rdv_id ?>">
                        <?php endif; ?>
                        <hr>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Créer l'ordonnance
                            </button>
                            <a href="index.php?page=medecin_ordonnances" class="btn btn-secondary">Annuler</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
