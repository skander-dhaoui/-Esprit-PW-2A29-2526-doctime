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
    <title>Mes ordonnances - Espace Médecin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f5f7fb; font-family: 'Segoe UI', sans-serif; }
        .navbar { background: #1a2035; }
        .card { border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .ordonnance-card { border-left: 4px solid #2A7FAA; transition: transform 0.2s; }
        .ordonnance-card:hover { transform: translateY(-3px); }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container">
        <a class="navbar-brand" href="index.php?page=accueil"><i class="fas fa-stethoscope me-2"></i>Valorys</a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="index.php?page=accueil">Accueil</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php?page=mes_rendez_vous">Mes RDV</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php?page=disponibilites">Disponibilités</a></li>
                <li class="nav-item"><a class="nav-link active" href="index.php?page=ordonnances">Ordonnances</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php?page=mon_profil">Profil</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php?page=logout">Déconnexion</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-prescription-bottle me-2"></i>Mes ordonnances</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createOrdonnanceModal">
            <i class="fas fa-plus me-2"></i>Nouvelle ordonnance
        </button>
    </div>

    <?php if (isset($_SESSION['flash'])): ?>
        <div class="alert alert-<?= $_SESSION['flash']['type'] === 'error' ? 'danger' : 'success' ?> alert-dismissible fade show">
            <?= $_SESSION['flash']['message'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>

    <div class="row">
        <?php if (empty($ordonnances)): ?>
            <div class="col-12">
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle fa-2x mb-2 d-block"></i>
                    <p>Aucune ordonnance émise.</p>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createOrdonnanceModal">
                        Créer une ordonnance
                    </button>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($ordonnances as $ord): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card ordonnance-card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <h5 class="card-title text-primary mb-0">
                                <i class="fas fa-file-medical me-2"></i><?= $ord['numero_ordonnance'] ?>
                            </h5>
                            <span class="badge <?= $ord['status'] === 'active' ? 'bg-success' : 'bg-secondary' ?>">
                                <?= $ord['status'] === 'active' ? 'Active' : 'Expirée' ?>
                            </span>
                        </div>
                        <p class="card-text">
                            <small class="text-muted">
                                <i class="fas fa-user me-1"></i><?= htmlspecialchars($ord['patient_prenom'] . ' ' . $ord['patient_nom']) ?><br>
                                <i class="fas fa-calendar me-1"></i><?= date('d/m/Y', strtotime($ord['date_ordonnance'])) ?>
                            </small>
                        </p>
                        <p class="card-text"><?= htmlspecialchars(substr($ord['diagnostic'] ?? '', 0, 80)) ?>...</p>
                        <div class="mt-3">
                            <button class="btn btn-sm btn-outline-primary" onclick="showDetails(<?= $ord['id'] ?>)">
                                <i class="fas fa-eye me-1"></i>Voir détails
                            </button>
                            <button class="btn btn-sm btn-outline-secondary" onclick="downloadPDF(<?= $ord['id'] ?>)">
                                <i class="fas fa-download me-1"></i>PDF
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Création Ordonnance -->
<div class="modal fade" id="createOrdonnanceModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-prescription-bottle me-2"></i>Nouvelle ordonnance</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="index.php?page=ordonnances&action=store">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Patient *</label>
                        <select name="patient_id" class="form-select" required>
                            <option value="">Sélectionner un patient</option>
                            <?php foreach ($patients as $patient): ?>
                                <option value="<?= $patient['id'] ?>"><?= htmlspecialchars($patient['prenom'] . ' ' . $patient['nom']) ?></option>
                            <?php endforeach; ?>
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
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Créer l'ordonnance</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Détails Ordonnance -->
<div class="modal fade" id="ordonnanceModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-file-medical me-2"></i>Détails de l'ordonnance</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="ordonnanceDetails">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary"></div>
                    <p class="mt-2">Chargement...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                <button type="button" class="btn btn-primary" id="downloadPdfBtn">Télécharger PDF</button>
            </div>
        </div>
    </div>
</div>

<script>
function showDetails(id) {
    fetch(`index.php?page=api_ordonnance&id=${id}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const ord = data.ordonnance;
                document.getElementById('ordonnanceDetails').innerHTML = `
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Numéro :</strong> ${ord.numero_ordonnance}</p>
                            <p><strong>Patient :</strong> ${ord.patient_prenom} ${ord.patient_nom}</p>
                            <p><strong>Date :</strong> ${new Date(ord.date_ordonnance).toLocaleDateString('fr-FR')}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Statut :</strong> <span class="badge ${ord.status === 'active' ? 'bg-success' : 'bg-secondary'}">${ord.status}</span></p>
                        </div>
                    </div>
                    <hr>
                    <h6>Diagnostic</h6>
                    <p>${ord.diagnostic || 'Non spécifié'}</p>
                    <h6>Prescription</h6>
                    <p>${ord.contenu || 'Non spécifié'}</p>
                `;
                document.getElementById('downloadPdfBtn').onclick = () => downloadPDF(ord.id);
                new bootstrap.Modal(document.getElementById('ordonnanceModal')).show();
            }
        });
}

function downloadPDF(id) {
    window.open(`index.php?page=download_ordonnance&id=${id}`, '_blank');
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>