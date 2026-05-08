<?php
require_once __DIR__ . '/config/database.php';

// Simple interface pour exécuter la migration
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Migration - Ajout Montant Sponsors</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">Migration Sponsors - Ajout Colonne Montant</h4>
                    </div>
                    <div class="card-body">
                        <?php
                        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                            try {
                                $pdo = Database::getInstance()->getConnection();
                                
                                echo "<div class='alert alert-info'>Début de la migration...</div>";
                                
                                // Vérifier si la colonne montant existe déjà
                                $checkColumn = $pdo->query("SHOW COLUMNS FROM sponsors LIKE 'montant'")->fetch();
                                
                                if ($checkColumn) {
                                    echo "<div class='alert alert-warning'>La colonne 'montant' existe déjà dans la table sponsors.</div>";
                                } else {
                                    // Ajouter la colonne montant
                                    $pdo->exec("ALTER TABLE sponsors ADD COLUMN montant DECIMAL(10,2) DEFAULT 0 AFTER niveau");
                                    echo "<div class='alert alert-success'>✓ Colonne 'montant' ajoutée avec succès.</div>";
                                    
                                    // Ajouter un index sur la colonne montant
                                    $pdo->exec("ALTER TABLE sponsors ADD INDEX idx_montant (montant)");
                                    echo "<div class='alert alert-success'>✓ Index sur la colonne 'montant' ajouté.</div>";
                                }
                                
                                // Mettre à jour les sponsors existants avec des montants par défaut selon leur niveau
                                $result1 = $pdo->exec("UPDATE sponsors SET montant = 10000.00 WHERE niveau = 'platinium' AND montant = 0");
                                $result2 = $pdo->exec("UPDATE sponsors SET montant = 7500.00 WHERE niveau = 'gold' AND montant = 0");
                                $result3 = $pdo->exec("UPDATE sponsors SET montant = 5000.00 WHERE niveau = 'silver' AND montant = 0");
                                $result4 = $pdo->exec("UPDATE sponsors SET montant = 2500.00 WHERE niveau = 'bronze' AND montant = 0");
                                
                                echo "<div class='alert alert-success'>✓ Montants par défaut appliqués selon les niveaux de sponsors.</div>";
                                echo "<div class='alert alert-success'><strong>Migration terminée avec succès!</strong></div>";
                                
                                echo "<div class='mt-3'>";
                                echo "<a href='index.php' class='btn btn-primary'>Retour à l'accueil</a> ";
                                echo "<a href='views/backoffice/dashboard.php' class='btn btn-success'>Voir le tableau de bord</a>";
                                echo "</div>";
                                
                            } catch (Exception $e) {
                                echo "<div class='alert alert-danger'><strong>Erreur lors de la migration:</strong> " . $e->getMessage() . "</div>";
                            }
                        } else {
                        ?>
                        <div class="alert alert-info">
                            <h5>Cette migration va:</h5>
                            <ul>
                                <li>Ajouter une colonne <code>montant</code> à la table <code>sponsors</code></li>
                                <li>Ajouter un index sur cette colonne pour optimiser les performances</li>
                                <li>Appliquer des montants par défaut selon le niveau de sponsor:
                                    <ul>
                                        <li>Platinium: 10,000 TND</li>
                                        <li>Gold: 7,500 TND</li>
                                        <li>Silver: 5,000 TND</li>
                                        <li>Bronze: 2,500 TND</li>
                                    </ul>
                                </li>
                            </ul>
                        </div>
                        
                        <form method="POST">
                            <button type="submit" class="btn btn-primary btn-lg w-100">Exécuter la migration</button>
                        </form>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
