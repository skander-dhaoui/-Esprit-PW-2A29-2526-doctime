<?php
require_once __DIR__ . '/config/database.php';

// Test script pour vérifier que les données s'affichent correctement
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Dashboard - Sponsors</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-12">
                <h2 class="mb-4">Test - Données des Sponsors</h2>
            </div>
        </div>
        
        <?php
        try {
            $pdo = Database::getInstance()->getConnection();
            
            // Vérifier si la colonne montant existe
            $columns = $pdo->query("SHOW COLUMNS FROM sponsors")->fetchAll(PDO::FETCH_COLUMN);
            $hasMontant = in_array('montant', $columns);
            
            if (!$hasMontant) {
                echo '<div class="alert alert-danger">';
                echo '<h5><i class="bi bi-exclamation-triangle"></i> Erreur: La colonne "montant" n\'existe pas!</h5>';
                echo '<p>Veuillez d\'abord exécuter la migration: <a href="run_migration.php" class="btn btn-primary">Exécuter la migration</a></p>';
                echo '</div>';
            } else {
                echo '<div class="alert alert-success"><i class="bi bi-check-circle"></i> La colonne "montant" existe bien.</div>';
            }
            
            // Statistiques
            $totalSponsors = $pdo->query("SELECT COUNT(*) FROM sponsors")->fetchColumn();
            $totalMontant = $pdo->query("SELECT COALESCE(SUM(montant), 0) FROM sponsors")->fetchColumn();
            
            echo '<div class="row mb-4">';
            echo '<div class="col-md-6">';
            echo '<div class="card bg-primary text-white">';
            echo '<div class="card-body">';
            echo '<h5>Total Sponsors</h5>';
            echo '<h2>' . $totalSponsors . '</h2>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
            echo '<div class="col-md-6">';
            echo '<div class="card bg-success text-white">';
            echo '<div class="card-body">';
            echo '<h5>Montant Total (TND)</h5>';
            echo '<h2>' . number_format($totalMontant, 2, ',', ' ') . '</h2>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
            
            // Liste des sponsors
            $sponsors = $pdo->query("SELECT * FROM sponsors ORDER BY montant DESC")->fetchAll();
            
            if (count($sponsors) > 0) {
                echo '<div class="card">';
                echo '<div class="card-header">';
                echo '<h5><i class="bi bi-building"></i> Liste des Sponsors</h5>';
                echo '</div>';
                echo '<div class="card-body">';
                echo '<div class="table-responsive">';
                echo '<table class="table table-striped">';
                echo '<thead>';
                echo '<tr>';
                echo '<th>Nom</th>';
                echo '<th>Niveau</th>';
                echo '<th>Montant (TND)</th>';
                echo '<th>Site Web</th>';
                echo '<th>Actif</th>';
                echo '</tr>';
                echo '</thead>';
                echo '<tbody>';
                
                foreach ($sponsors as $sponsor) {
                    echo '<tr>';
                    echo '<td><strong>' . htmlspecialchars($sponsor['nom']) . '</strong></td>';
                    echo '<td><span class="badge bg-' . 
                         ($sponsor['niveau'] == 'platinium' ? 'primary' : 
                          ($sponsor['niveau'] == 'gold' ? 'warning' : 
                           ($sponsor['niveau'] == 'silver' ? 'secondary' : 'light text-dark'))) . '">' . 
                         ucfirst($sponsor['niveau']) . '</span></td>';
                    echo '<td>' . number_format($sponsor['montant'], 2, ',', ' ') . '</td>';
                    echo '<td>' . ($sponsor['site_web'] ? '<a href="' . htmlspecialchars($sponsor['site_web']) . '" target="_blank">' . htmlspecialchars($sponsor['site_web']) . '</a>' : '-') . '</td>';
                    echo '<td>' . ($sponsor['actif'] ? '<i class="bi bi-check-circle text-success"></i>' : '<i class="bi bi-x-circle text-danger"></i>') . '</td>';
                    echo '</tr>';
                }
                
                echo '</tbody>';
                echo '</table>';
                echo '</div>';
                echo '</div>';
                echo '</div>';
            } else {
                echo '<div class="alert alert-warning">';
                echo '<h5><i class="bi bi-info-circle"></i> Aucun sponsor trouvé</h5>';
                echo '<p>Ajoutez des sponsors exemples: <a href="add_sample_sponsors.php" class="btn btn-success">Ajouter des sponsors</a></p>';
                echo '</div>';
            }
            
            // Lien vers le dashboard
            echo '<div class="mt-4 text-center">';
            echo '<a href="views/backoffice/dashboard.php" class="btn btn-primary btn-lg"><i class="bi bi-speedometer2"></i> Voir le Tableau de Bord</a>';
            echo '</div>';
            
        } catch (Exception $e) {
            echo '<div class="alert alert-danger">';
            echo '<h5><i class="bi bi-exclamation-triangle"></i> Erreur de base de données</h5>';
            echo '<p>' . $e->getMessage() . '</p>';
            echo '</div>';
        }
        ?>
    </div>
</body>
</html>
