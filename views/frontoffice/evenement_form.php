<?php
// Protection : connexion requise
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php?page=login');
    exit;
}

$isEdit  = isset($event) && !empty($event['id']);
$formAction = $isEdit
    ? 'index.php?page=evenement_edit&id=' . $event['id']
    : 'index.php?page=evenement_create';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $isEdit ? 'Modifier' : 'Créer' ?> un événement - Valorys</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f5f7fb; font-family: 'Segoe UI', sans-serif; }
        .navbar { background: #1a2035; }
        .navbar-brand, .nav-link { color: white !important; }
        .page-header {
            background: linear-gradient(135deg, #2A7FAA 0%, #4CAF50 100%);
            color: white; padding: 40px 0; text-align: center; margin-bottom: 40px;
        }
        .card { border-radius: 16px; box-shadow: 0 5px 15px rgba(0,0,0,0.08); border: none; }
        .card-header-custom {
            background: linear-gradient(135deg, #2A7FAA, #4CAF50);
            color: white; border-radius: 16px 16px 0 0 !important; padding: 20px 25px;
        }
        .form-label { font-weight: 600; color: #1a2035; }
        .form-control:focus, .form-select:focus {
            border-color: #4CAF50;
            box-shadow: 0 0 0 3px rgba(76,175,80,0.15);
        }
        .btn-save {
            background: linear-gradient(135deg, #2A7FAA, #4CAF50);
            color: white; border: none; border-radius: 25px;
            padding: 10px 30px; font-weight: 600;
        }
        .btn-save:hover { opacity: 0.9; color: white; }
        .btn-cancel { border-radius: 25px; padding: 10px 25px; }
        .required-star { color: #dc3545; }
        footer { background: #1a2035; color: white; text-align: center; padding: 30px; margin-top: 50px; }
    </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container">
        <a class="navbar-brand" href="index.php?page=accueil">
            <i class="fas fa-stethoscope me-2"></i>Valorys
        </a>
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
                <li class="nav-item"><a class="nav-link" href="index.php?page=logout">
                    <i class="fas fa-sign-out-alt me-1"></i>Déconnexion
                </a></li>
            </ul>
        </div>
    </div>
</nav>

<!-- EN-TÊTE -->
<div class="page-header">
    <div class="container">
        <h1>
            <i class="fas fa-<?= $isEdit ? 'edit' : 'plus-circle' ?> me-3"></i>
            <?= $isEdit ? 'Modifier l\'événement' : 'Créer un événement' ?>
        </h1>
        <p class="lead mb-0">
            <?= $isEdit ? htmlspecialchars($event['titre']) : 'Remplissez les informations ci-dessous' ?>
        </p>
    </div>
</div>

<div class="container mb-5">

    <!-- Messages flash -->
    <?php if (isset($_SESSION['flash'])): ?>
        <?php $f = $_SESSION['flash']; unset($_SESSION['flash']); ?>
        <?php $bc = ['success'=>'success','error'=>'danger','warning'=>'warning','info'=>'info'][$f['type']] ?? 'secondary'; ?>
        <div class="alert alert-<?= $bc ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($f['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row justify-content-center">
        <div class="col-lg-9">

            <div class="card">
                <div class="card-header-custom">
                    <h5 class="mb-0">
                        <i class="fas fa-calendar-alt me-2"></i>
                        Informations de l'événement
                    </h5>
                </div>
                <div class="card-body p-4">

                    <form method="POST" action="<?= $formAction ?>" id="eventForm">

                        <!-- TITRE + IMAGE -->
                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label class="form-label">
                                    Titre <span class="required-star">*</span>
                                </label>
                                <input type="text" name="titre" class="form-control"
                                       value="<?= htmlspecialchars($event['titre'] ?? '') ?>"
                                       placeholder="Nom de l'événement" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Image (URL)</label>
                                <input type="url" name="image" class="form-control"
                                       value="<?= htmlspecialchars($event['image'] ?? '') ?>"
                                       placeholder="https://...">
                            </div>
                        </div>

                        <!-- DATES -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    Date de début <span class="required-star">*</span>
                                </label>
                                <input type="datetime-local" name="date_debut" class="form-control"
                                       value="<?= isset($event['date_debut']) ? date('Y-m-d\TH:i', strtotime($event['date_debut'])) : '' ?>"
                                       required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    Date de fin <span class="required-star">*</span>
                                </label>
                                <input type="datetime-local" name="date_fin" class="form-control"
                                       value="<?= isset($event['date_fin']) ? date('Y-m-d\TH:i', strtotime($event['date_fin'])) : '' ?>"
                                       required>
                            </div>
                        </div>

                        <!-- LIEU -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Lieu</label>
                                <input type="text" name="lieu" class="form-control"
                                       value="<?= htmlspecialchars($event['lieu'] ?? '') ?>"
                                       placeholder="Salle, bâtiment...">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Adresse complète</label>
                                <input type="text" name="adresse" class="form-control"
                                       value="<?= htmlspecialchars($event['adresse'] ?? '') ?>"
                                       placeholder="Rue, ville, code postal">
                            </div>
                        </div>

                        <!-- CAPACITÉ / PRIX / STATUT -->
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Capacité maximale</label>
                                <input type="number" name="capacite_max" class="form-control"
                                       value="<?= htmlspecialchars($event['capacite_max'] ?? '0') ?>"
                                       min="0" placeholder="0 = illimité">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Prix (€)</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-euro-sign"></i></span>
                                    <input type="number" name="prix" class="form-control"
                                           step="0.01" min="0"
                                           value="<?= htmlspecialchars($event['prix'] ?? '0') ?>">
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Statut</label>
                                <select name="status" class="form-select">
                                    <option value="à venir"  <?= (($event['status'] ?? '') === 'à venir')  ? 'selected' : '' ?>>À venir</option>
                                    <option value="terminé"  <?= (($event['status'] ?? '') === 'terminé')  ? 'selected' : '' ?>>Terminé</option>
                                    <option value="annulé"   <?= (($event['status'] ?? '') === 'annulé')   ? 'selected' : '' ?>>Annulé</option>
                                </select>
                            </div>
                        </div>

                        <!-- DESCRIPTION -->
                        <div class="mb-3">
                            <label class="form-label">Description courte</label>
                            <textarea name="description" class="form-control" rows="3"
                                      placeholder="Résumé affiché dans les listes..."><?= htmlspecialchars($event['description'] ?? '') ?></textarea>
                        </div>

                        <!-- CONTENU -->
                        <div class="mb-4">
                            <label class="form-label">Contenu détaillé</label>
                            <textarea name="contenu" class="form-control" rows="6"
                                      placeholder="Programme, intervenants, informations pratiques..."><?= htmlspecialchars($event['contenu'] ?? '') ?></textarea>
                        </div>

                        <!-- BOUTONS -->
                        <hr>
                        <div class="d-flex gap-2 justify-content-end">
                            <a href="index.php?page=evenements" class="btn btn-outline-secondary btn-cancel">
                                <i class="fas fa-times me-1"></i>Annuler
                            </a>
                            <button type="submit" class="btn btn-save">
                                <i class="fas fa-save me-2"></i>
                                <?= $isEdit ? 'Mettre à jour' : 'Créer l\'événement' ?>
                            </button>
                        </div>

                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<footer>
    <div class="container">
        <p class="mb-0">&copy; 2024 Valorys - Tous droits réservés</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('eventForm').addEventListener('submit', function(e) {
    const dateDebut = document.querySelector('input[name="date_debut"]').value;
    const dateFin   = document.querySelector('input[name="date_fin"]').value;

    if (dateDebut && dateFin && dateDebut > dateFin) {
        e.preventDefault();
        alert('La date de début doit être antérieure à la date de fin.');
    }
});
</script>
</body>
</html>