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
    <title><?= isset($rdv) ? 'Modifier' : 'Ajouter' ?> un rendez-vous - Valorys</title>
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

<!-- Sidebar -->
<div class="sidebar p-3">
    <h4 class="text-center mb-4">Valorys Admin</h4>
    <hr class="bg-light">
    <a href="index.php?page=dashboard" class="text-white d-block py-2">📊 Dashboard</a>
    <a href="index.php?page=admin_rendezvous" class="text-white d-block py-2 bg-primary px-2 rounded">📅 Rendez-vous</a>
    <a href="index.php?page=logout" class="text-white d-block py-2 mt-5">🚪 Déconnexion</a>
</div>

<!-- Main Content -->
<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-<?= isset($rdv) ? 'edit' : 'plus' ?> me-2"></i><?= isset($rdv) ? 'Modifier' : 'Ajouter' ?> un rendez-vous</h2>
        <a href="index.php?page=admin_rendezvous" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Retour
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="index.php?page=admin_rendezvous&action=<?= isset($rdv) ? 'update&id=' . $rdv['id'] : 'store' ?>">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Patient *</label>
                        <select name="patient_id" class="form-select" required>
                            <option value="">Sélectionner un patient</option>
                            <?php foreach ($patients as $patient): ?>
                                <option value="<?= $patient['id'] ?>" <?= (isset($rdv) && $rdv['patient_id'] == $patient['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($patient['prenom'] . ' ' . $patient['nom']) ?> (<?= $patient['email'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Médecin *</label>
                        <select name="medecin_id" id="medecin_id" class="form-select" required>
                            <option value="">Sélectionner un médecin</option>
                            <?php foreach ($medecins as $medecin): ?>
                                <option value="<?= $medecin['id'] ?>" <?= (isset($rdv) && $rdv['medecin_id'] == $medecin['id']) ? 'selected' : '' ?>>
                                    Dr. <?= htmlspecialchars($medecin['prenom'] . ' ' . $medecin['nom']) ?> - <?= $medecin['specialite'] ?? 'Généraliste' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Date *</label>
                        <input type="date" name="date_rendezvous" class="form-control" value="<?= isset($rdv) ? $rdv['date_rendezvous'] : '' ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Heure *</label>
                        <input type="time" name="heure_rendezvous" class="form-control" value="<?= isset($rdv) ? $rdv['heure_rendezvous'] : '' ?>" required>
                    </div>
                    <div class="col-12 mb-3">
                        <label class="form-label">Motif</label>
                        <textarea name="motif" class="form-control" rows="3"><?= isset($rdv) ? htmlspecialchars($rdv['motif']) : '' ?></textarea>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Statut</label>
                        <select name="statut" class="form-select">
                            <option value="en_attente" <?= (isset($rdv) && $rdv['statut'] === 'en_attente') ? 'selected' : '' ?>>En attente</option>
                            <option value="confirmé" <?= (isset($rdv) && $rdv['statut'] === 'confirmé') ? 'selected' : '' ?>>Confirmé</option>
                            <option value="terminé" <?= (isset($rdv) && $rdv['statut'] === 'terminé') ? 'selected' : '' ?>>Terminé</option>
                            <option value="annulé" <?= (isset($rdv) && $rdv['statut'] === 'annulé') ? 'selected' : '' ?>>Annulé</option>
                        </select>
                    </div>
                </div>

                <hr>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i><?= isset($rdv) ? 'Mettre à jour' : 'Créer' ?>
                    </button>
                    <a href="index.php?page=admin_rendezvous" class="btn btn-secondary">Annuler</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Charger les créneaux disponibles quand le médecin change
document.getElementById('medecin_id')?.addEventListener('change', function() {
    const medecinId = this.value;
    const date = document.querySelector('input[name="date_rendezvous"]').value;
    if (medecinId && date) {
        fetch(`index.php?page=api_slots&medecin_id=${medecinId}&date=${date}`)
            .then(res => res.json())
            .then(data => {
                const heureSelect = document.querySelector('input[name="heure_rendezvous"]');
                if (data.slots && data.slots.length) {
                    // Créer un datalist pour suggérer les heures
                    let datalist = document.getElementById('slotsList');
                    if (!datalist) {
                        datalist = document.createElement('datalist');
                        datalist.id = 'slotsList';
                        heureSelect.setAttribute('list', 'slotsList');
                        document.body.appendChild(datalist);
                    }
                    datalist.innerHTML = data.slots.map(slot => `<option value="${slot}">`).join('');
                }
            });
    }
});
</script>
</body>
</html>