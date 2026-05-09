<?php
// views/backoffice/evenements/form.php
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../../../index.php?page=login');
    exit;
}

require_once __DIR__ . '/../../../models/Event.php';
require_once __DIR__ . '/../../../config/database.php';

$page_title = 'Ajouter/Éditer Événement';
$current_page = 'evenements';

$eventModel = new Event();
$id = $_GET['id'] ?? null;
$event = null;

if ($id && is_numeric($id)) {
    $event = $eventModel->getById((int)$id);
}

// Traiter la soumission du formulaire
$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'titre' => htmlspecialchars(trim($_POST['titre'] ?? ''), ENT_QUOTES, 'UTF-8'),
        'description' => htmlspecialchars(trim($_POST['description'] ?? ''), ENT_QUOTES, 'UTF-8'),
        'date_debut' => $_POST['date_debut'] ?? '',
        'date_fin' => $_POST['date_fin'] ?? '',
        'lieu' => htmlspecialchars(trim($_POST['lieu'] ?? ''), ENT_QUOTES, 'UTF-8'),
        'adresse' => htmlspecialchars(trim($_POST['adresse'] ?? ''), ENT_QUOTES, 'UTF-8'),
        'capacite_max' => (int)($_POST['capacite_max'] ?? 0),
        'prix' => (float)($_POST['prix'] ?? 0),
        'status' => $_POST['status'] ?? 'à venir',
        'sponsor_id' => $_POST['sponsor_id'] ?? null,
        'image' => $event['image'] ?? null
    ];

    // Valider les champs requis
    if (empty($data['titre']) || empty($data['date_debut'])) {
        $error = 'Le titre et la date de début sont obligatoires.';
    } else {
        // Traiter l'upload d'image si présent
        if (isset($_FILES['image']) && $_FILES['image']['size'] > 0) {
            $upload_dir = __DIR__ . '/../../../uploads/events/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $file_ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];

            if (in_array($file_ext, $allowed_ext)) {
                $file_name = uniqid() . '.' . $file_ext;
                $file_path = $upload_dir . $file_name;

                if (move_uploaded_file($_FILES['image']['tmp_name'], $file_path)) {
                    $data['image'] = '/uploads/events/' . $file_name;
                }
            } else {
                $error = 'Format d\'image invalide. Formats acceptés: JPG, PNG, GIF';
            }
        }

        if (!$error) {
            if ($id && is_numeric($id)) {
                // Mise à jour
                if ($eventModel->update((int)$id, $data)) {
                    $success = 'Événement mis à jour avec succès';
                    $event = $eventModel->getById((int)$id);
                } else {
                    $error = 'Erreur lors de la mise à jour';
                }
            } else {
                // Création
                $newId = $eventModel->create($data);
                if ($newId) {
                    header('Location: show.php?id=' . $newId . '&success=1');
                    exit;
                } else {
                    $error = 'Erreur lors de la création';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> - Valorys</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #f0f2f5; font-family: 'Segoe UI', sans-serif; display: flex; min-height: 100vh; }

        .sidebar {
            width: 260px;
            min-height: 100vh;
            background: #1a2035;
            color: white;
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 100;
        }

        .main-content {
            margin-left: 260px;
            flex: 1;
            padding: 30px;
            max-width: 100%;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .page-header h1 {
            font-size: 28px;
            font-weight: 700;
            color: #1a2035;
            margin: 0;
        }

        .btn-back {
            background: #6c757d;
            border: none;
            color: white;
            padding: 10px 24px;
            border-radius: 30px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: opacity 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-back:hover {
            opacity: 0.9;
            color: white;
        }

        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border: none;
            margin-bottom: 20px;
        }

        .card-header {
            background: linear-gradient(135deg, #2A7FAA, #4CAF50);
            border-radius: 12px 12px 0 0;
            padding: 20px;
            color: white;
        }

        .card-header h4 {
            margin: 0;
            font-size: 18px;
            font-weight: 700;
        }

        .card-body {
            padding: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #1a2035;
            font-size: 14px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            font-family: inherit;
            transition: border-color 0.2s;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #4CAF50;
            outline: none;
            box-shadow: 0 0 0 3px rgba(76,175,80,0.1);
        }

        .form-group input.is-invalid,
        .form-group select.is-invalid,
        .form-group textarea.is-invalid {
            border-color: #dc3545;
            box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.1);
        }

        .error-text {
            color: #dc3545;
            font-size: 13px;
            margin-top: 6px;
            font-weight: 500;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d1e7dd;
            color: #0f5132;
            border: 1px solid #badbcc;
        }

        .alert-error {
            background: #f8d7da;
            color: #842029;
            border: 1px solid #f5c2c7;
        }

        .image-preview {
            margin-top: 10px;
            max-width: 300px;
        }

        .image-preview img {
            max-width: 100%;
            border-radius: 6px;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 30px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #2A7FAA, #4CAF50);
            border: none;
            color: white;
            padding: 10px 24px;
            border-radius: 30px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: opacity 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-primary:hover {
            opacity: 0.9;
        }

        .btn-cancel {
            background: #6c757d;
            border: none;
            color: white;
            padding: 10px 24px;
            border-radius: 30px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: opacity 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-cancel:hover {
            opacity: 0.9;
        }

        .required {
            color: #dc3545;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <?php include __DIR__ . '/../sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <div class="page-header">
            <h1><i class="fas fa-calendar-plus"></i> <?= $id ? 'Éditer' : 'Ajouter' ?> un Événement</h1>
            <a href="index.php?page=evenements_admin" class="btn-back">
                <i class="fas fa-arrow-left"></i> Retour
            </a>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?= $success ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?= $error ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" id="eventForm" enctype="multipart/form-data" novalidate>
            <!-- Basic Info -->
            <div class="card">
                <div class="card-header">
                    <h4>Informations Générales</h4>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="titre">Titre <span class="required">*</span></label>
                        <input type="text" id="titre" name="titre" 
                            value="<?= htmlspecialchars($event['titre'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description"><?= htmlspecialchars($event['description'] ?? '') ?></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="date_debut">Date Début <span class="required">*</span></label>
                            <input type="datetime-local" id="date_debut" name="date_debut" 
                                value="<?= isset($event['date_debut']) ? date('Y-m-d\TH:i', strtotime($event['date_debut'])) : '' ?>">
                        </div>
                        <div class="form-group">
                            <label for="date_fin">Date Fin</label>
                            <input type="datetime-local" id="date_fin" name="date_fin" 
                                value="<?= isset($event['date_fin']) ? date('Y-m-d\TH:i', strtotime($event['date_fin'])) : '' ?>">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Location Info -->
            <div class="card">
                <div class="card-header">
                    <h4>Lieu et Adresse</h4>
                </div>
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="lieu">Lieu <span class="required">*</span></label>
                            <input type="text" id="lieu" name="lieu" 
                                value="<?= htmlspecialchars($event['lieu'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="adresse">Adresse <span class="required">*</span></label>
                            <input type="text" id="adresse" name="adresse" 
                                value="<?= htmlspecialchars($event['adresse'] ?? '') ?>">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Event Details -->
            <div class="card">
                <div class="card-header">
                    <h4>Détails de l'Événement</h4>
                </div>
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="capacite_max">Capacité Maximale</label>
                            <input type="number" id="capacite_max" name="capacite_max" min="0" 
                                value="<?= $event['capacite_max'] ?? '' ?>">
                        </div>
                        <div class="form-group">
                            <label for="prix">Prix (TND)</label>
                            <input type="number" id="prix" name="prix" step="0.01" min="0" 
                                value="<?= $event['prix'] ?? '' ?>">
                        </div>
                        <div class="form-group">
                            <label for="status">Statut</label>
                            <select id="status" name="status">
                                <option value="à venir" <?= ($event['status'] ?? '') === 'à venir' ? 'selected' : '' ?>>À venir</option>
                                <option value="terminé" <?= ($event['status'] ?? '') === 'terminé' ? 'selected' : '' ?>>Terminé</option>
                                <option value="annulé" <?= ($event['status'] ?? '') === 'annulé' ? 'selected' : '' ?>>Annulé</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="sponsor_id">Sponsor</label>
                        <select id="sponsor_id" name="sponsor_id">
                            <option value="">Aucun</option>
                            <!-- Sponsors will be populated from database if needed -->
                        </select>
                    </div>
                </div>
            </div>

            <!-- Image -->
            <div class="card">
                <div class="card-header">
                    <h4>Image</h4>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="image">Télécharger une image</label>
                        <input type="file" id="image" name="image" accept="image/*" onchange="previewImage(this)">
                    </div>
                    <?php if (!empty($event['image'])): ?>
                        <div class="image-preview">
                            <p style="font-size: 12px; color: #6c757d;">Image actuelle:</p>
                            <img src="<?= htmlspecialchars($event['image']) ?>" alt="Event Image">
                        </div>
                    <?php endif; ?>
                    <div id="imagePreview"></div>
                </div>
            </div>

            <div class="action-buttons">
                <button type="submit" class="btn-primary">
                    <i class="fas fa-save"></i> <?= $id ? 'Mettre à jour' : 'Créer' ?>
                </button>
                <a href="index.php?page=evenements_admin" class="btn-cancel">
                    <i class="fas fa-times"></i> Annuler
                </a>
            </div>
        </form>
    </div>

    <script>
        function previewImage(input) {
            const preview = document.getElementById('imagePreview');
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = '<div class="image-preview"><p style="font-size: 12px; color: #6c757d;">Aperçu:</p><img src="' + e.target.result + '" alt="Preview"></div>';
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        document.getElementById('eventForm').addEventListener('submit', function(e) {
            let valid = true;
            
            // Réinitialiser les messages d'erreur et styles
            document.querySelectorAll('.error-text').forEach(el => el.remove());
            document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));

            function showError(inputId, message) {
                const input = document.getElementById(inputId);
                input.classList.add('is-invalid');
                const errorDiv = document.createElement('div');
                errorDiv.className = 'error-text';
                errorDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + message;
                input.parentNode.appendChild(errorDiv);
                valid = false;
            }

            // Contrôle Titre
            const titre = document.getElementById('titre').value.trim();
            if (!titre) {
                showError('titre', 'Le titre est obligatoire.');
            } else if (titre.length < 3) {
                showError('titre', 'Le titre doit contenir au moins 3 caractères.');
            }

            // Contrôle Dates
            const dateDebut = document.getElementById('date_debut').value;
            const dateFin = document.getElementById('date_fin').value;
            if (!dateDebut) {
                showError('date_debut', 'La date de début est obligatoire.');
            }
            if (dateDebut && dateFin && dateFin < dateDebut) {
                showError('date_fin', 'La date de fin doit être ultérieure ou égale à la date de début.');
            }

            // Contrôle Lieu et Adresse
            const lieu = document.getElementById('lieu').value.trim();
            if (!lieu) {
                showError('lieu', 'Le lieu est obligatoire.');
            }
            const adresse = document.getElementById('adresse').value.trim();
            if (!adresse) {
                showError('adresse', 'L\'adresse est obligatoire.');
            }
            
            // Contrôle Capacité
            const capacite = document.getElementById('capacite_max').value;
            if (capacite !== '' && parseInt(capacite) < 0) {
                showError('capacite_max', 'La capacité maximale doit être un nombre positif.');
            }
            
            // Contrôle Prix
            const prix = document.getElementById('prix').value;
            if (prix !== '' && parseFloat(prix) < 0) {
                showError('prix', 'Le prix ne peut pas être négatif.');
            }

            if (!valid) {
                e.preventDefault(); // Empêcher l'envoi du formulaire si erreur
            }
        });
    </script>
</body>
</html>
