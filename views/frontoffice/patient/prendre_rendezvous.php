<?php
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'patient') {
    header('Location: index.php?page=login');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prendre rendez-vous - Valorys</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f5f7fb; font-family: 'Segoe UI', sans-serif; }
        .navbar { background: #1a2035; }
        .navbar-brand { color: white !important; }
        .nav-link { color: rgba(255,255,255,0.8) !important; }
        .nav-link:hover { color: white !important; }
        .card { border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .card-header { background: linear-gradient(135deg, #2A7FAA, #4CAF50); color: white; border-radius: 12px 12px 0 0 !important; }
        .form-label { font-weight: 600; }
        .btn-submit { background: linear-gradient(135deg, #2A7FAA, #4CAF50); color: white; border: none; padding: 12px 30px; border-radius: 25px; }
        .btn-submit:hover { opacity: 0.9; }
        footer { background: #1a2035; color: white; text-align: center; padding: 30px; margin-top: 50px; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container">
        <a class="navbar-brand" href="index.php?page=accueil"><i class="fas fa-stethoscope me-2"></i>Valorys</a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="index.php?page=accueil">Accueil</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php?page=medecins">Médecins</a></li>
                <li class="nav-item"><a class="nav-link active" href="index.php?page=prendre_rendezvous">Prendre RDV</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php?page=mes_rendezvous">Mes RDV</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php?page=mes_ordonnances">Ordonnances</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php?page=mon_profil">Profil</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php?page=logout">Déconnexion</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0"><i class="fas fa-calendar-plus me-2"></i>Prendre un rendez-vous</h4>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['flash'])): ?>
                        <div class="alert alert-<?= $_SESSION['flash']['type'] === 'error' ? 'danger' : 'success' ?> alert-dismissible fade show">
                            <?= $_SESSION['flash']['message'] ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['flash']); ?>
                    <?php endif; ?>

<form method="POST" action="index.php?page=prendre_rendez_vous">
                        <div class="mb-3">
                            <label class="form-label">Médecin *</label>
                            <select name="medecin_id" id="medecin_id" class="form-select" required>
                                <option value="">Sélectionner un médecin</option>
                                <?php foreach ($medecins as $medecin): ?>
                                    <option value="<?= $medecin['id'] ?>" <?= ($medecinId == $medecin['id']) ? 'selected' : '' ?>>
                                        Dr. <?= htmlspecialchars($medecin['prenom'] . ' ' . $medecin['nom']) ?> - <?= $medecin['specialite'] ?? 'Généraliste' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Date *</label>
                            <input type="date" name="date_rendezvous" id="date_rendezvous" class="form-control" value="<?= htmlspecialchars($date) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Heure *</label>
                            <select name="heure_rendezvous" id="heure_rendezvous" class="form-select" required>
                                <option value="">Sélectionner une heure</option>
                                <?php foreach ($slots as $slot): ?>
                                    <option value="<?= $slot ?>"><?= $slot ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Motif</label>
                            <textarea name="motif" class="form-control" rows="3" placeholder="Décrivez brièvement le motif de votre consultation..."></textarea>
                        </div>

                        <hr>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn-submit">
                                <i class="fas fa-check-circle me-2"></i>Confirmer le rendez-vous
                            </button>
                            <a href="index.php?page=mes_rendezvous" class="btn btn-secondary">Annuler</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<footer>
    <div class="container">
        <p>&copy; 2024 Valorys - Tous droits réservés</p>
        <small>Plateforme médicale en ligne</small>
    </div>
</footer>

<script>
// Charger les créneaux disponibles dynamiquement
const medecinSelect = document.getElementById('medecin_id');
const dateInput = document.getElementById('date_rendezvous');
const heureSelect = document.getElementById('heure_rendezvous');

function loadSlots() {
    const medecinId = medecinSelect.value;
    const date = dateInput.value;
    
    if (medecinId && date) {
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

medecinSelect.addEventListener('change', loadSlots);
dateInput.addEventListener('change', loadSlots);
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>// update
