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
    <title>Créer un rendez-vous - Valorys</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f5f7fb; font-family: 'Segoe UI', sans-serif; }
        .navbar { background: #1a2035; }
        .navbar-brand, .nav-link { color: white !important; }
        .card { border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .card-header { background: linear-gradient(135deg, #2A7FAA, #4CAF50); color: white; border-radius: 12px 12px 0 0 !important; }
        .btn-primary { background: linear-gradient(135deg, #2A7FAA, #4CAF50); border: none; }
        .btn-primary:hover { opacity: 0.9; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container">
        <a class="navbar-brand" href="index.php?page=accueil"><i class="fas fa-stethoscope me-2"></i>Valorys</a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="index.php?page=accueil">Accueil</a></li>
                <li class="nav-item"><a class="nav-link active" href="index.php?page=medecin_rendezvous">Mes RDV</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php?page=medecin_disponibilites">Disponibilités</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php?page=medecin_ordonnances">Ordonnances</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php?page=mon_profil">Profil</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php?page=logout">Déconnexion</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0"><i class="fas fa-plus me-2"></i>Nouveau rendez-vous</h4>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['flash'])): ?>
                        <div class="alert alert-<?= $_SESSION['flash']['type'] === 'error' ? 'danger' : 'success' ?> alert-dismissible fade show">
                            <?= $_SESSION['flash']['message'] ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['flash']); ?>
                    <?php endif; ?>

                    <form method="POST" action="index.php?page=medecin_rendezvous&action=store">
                        <div class="mb-3">
                            <label class="form-label">Patient *</label>
                            <select name="patient_id" class="form-select" required>
                                <option value="">Sélectionner un patient</option>
                                <?php if (isset($patients) && !empty($patients)): ?>
                                    <?php foreach ($patients as $patient): ?>
                                        <option value="<?= $patient['id'] ?>"><?= htmlspecialchars($patient['prenom'] . ' ' . $patient['nom']) ?> (<?= $patient['email'] ?>)</option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="" disabled>Aucun patient trouvé</option>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Date *</label>
                            <input type="date" name="date_rendezvous" id="date_rdv" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Heure *</label>
                            <select name="heure_rendezvous" id="heure_rdv" class="form-select" required>
                                <option value="">Sélectionner une heure</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Motif</label>
                            <textarea name="motif" class="form-control" rows="3" placeholder="Motif de la consultation..."></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Statut</label>
                            <select name="statut" class="form-select">
                                <option value="en_attente">En attente</option>
                                <option value="confirmé">Confirmé</option>
                            </select>
                        </div>
                        <hr>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Créer le rendez-vous
                            </button>
                            <a href="index.php?page=medecin_rendezvous" class="btn btn-secondary">Annuler</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Charger les créneaux disponibles
const dateInput = document.getElementById('date_rdv');
const heureSelect = document.getElementById('heure_rdv');
const medecinId = <?= $_SESSION['user_id'] ?>;

function loadSlots() {
    const date = dateInput.value;
    if (date) {
        fetch(`index.php?page=api_slots&medecin_id=${medecinId}&date=${date}`)
            .then(res => res.json())
            .then(data => {
                heureSelect.innerHTML = '<option value="">Sélectionner une heure</option>';
                if (data.slots && data.slots.length) {
                    data.slots.forEach(slot => {
                        const option = document.createElement('option');
                        option.value = slot;
                        option.textContent = slot;
                        heureSelect.appendChild(option);
                    });
                } else {
                    const option = document.createElement('option');
                    option.textContent = 'Aucun créneau disponible';
                    option.disabled = true;
                    heureSelect.appendChild(option);
                }
            })
            .catch(err => console.error('Erreur:', err));
    }
}

dateInput.addEventListener('change', loadSlots);
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>// update
