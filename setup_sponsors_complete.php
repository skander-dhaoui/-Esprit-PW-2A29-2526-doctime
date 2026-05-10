<?php
require_once __DIR__ . '/config/database.php';

// Script complet pour configurer les sponsors
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuration Complète des Sponsors</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card">
                    <div class="card-header bg-dark text-white">
                        <h4 class="mb-0"><i class="bi bi-gear-fill"></i> Configuration Complète des Sponsors</h4>
                    </div>
                    <div class="card-body">
                        <?php
                        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                            try {
                                $pdo = Database::getInstance()->getConnection();
                                
                                echo "<div class='alert alert-info'><h5><i class='bi bi-arrow-clockwise'></i> Début de la configuration...</h5></div>";
                                
                                // ÉTAPE 1: Vérifier et ajouter la colonne montant
                                echo "<div class='mb-3'><strong>Étape 1: Vérification de la colonne montant</strong></div>";
                                $checkColumn = $pdo->query("SHOW COLUMNS FROM sponsors LIKE 'montant'")->fetch();
                                
                                if ($checkColumn) {
                                    echo "<div class='alert alert-warning'>✓ La colonne 'montant' existe déjà.</div>";
                                } else {
                                    $pdo->exec("ALTER TABLE sponsors ADD COLUMN montant DECIMAL(10,2) DEFAULT 0 AFTER niveau");
                                    $pdo->exec("ALTER TABLE sponsors ADD INDEX idx_montant (montant)");
                                    echo "<div class='alert alert-success'>✓ Colonne 'montant' ajoutée avec succès.</div>";
                                }
                                
                                // ÉTAPE 2: Ajouter les sponsors exemples
                                echo "<div class='mb-3'><strong>Étape 2: Ajout des sponsors exemples</strong></div>";
                                
                                $sampleSponsors = [
                                    [
                                        'nom' => 'Tunisian Telecom',
                                        'site_web' => 'https://www.tunisietelecom.tn',
                                        'description' => 'Leader des télécommunications en Tunisie',
                                        'niveau' => 'platinium',
                                        'montant' => 15000.00
                                    ],
                                    [
                                        'nom' => 'BIAT Bank',
                                        'site_web' => 'https://www.biat.com.tn',
                                        'description' => 'Banque Internationale Arabe de Tunisie',
                                        'niveau' => 'gold',
                                        'montant' => 10000.00
                                    ],
                                    [
                                        'nom' => 'Société Tunisienne des Industries Pharmaceutiques',
                                        'site_web' => 'https://www.stip.com.tn',
                                        'description' => 'Fabricant tunisien de médicaments',
                                        'niveau' => 'gold',
                                        'montant' => 8500.00
                                    ],
                                    [
                                        'nom' => 'Clinique El Manar',
                                        'site_web' => 'https://www.cliniquelmanar.tn',
                                        'description' => 'Centre médical spécialisé',
                                        'niveau' => 'silver',
                                        'montant' => 6000.00
                                    ],
                                    [
                                        'nom' => 'Pharmacie Centrale',
                                        'site_web' => 'https://www.pharmaciecentrale.tn',
                                        'description' => 'Distributeur de médicaments et équipements',
                                        'niveau' => 'silver',
                                        'montant' => 5500.00
                                    ],
                                    [
                                        'nom' => 'MediTech Solutions',
                                        'site_web' => 'https://www.meditech.tn',
                                        'description' => 'Solutions technologiques médicales',
                                        'niveau' => 'bronze',
                                        'montant' => 3000.00
                                    ],
                                    [
                                        'nom' => 'Laboratoires Medlab',
                                        'site_web' => 'https://www.medlab.tn',
                                        'description' => 'Laboratoires d\'analyses médicales',
                                        'niveau' => 'bronze',
                                        'montant' => 2500.00
                                    ],
                                    [
                                        'nom' => 'Assurance STAR',
                                        'site_web' => 'https://www.assurance-star.tn',
                                        'description' => 'Compagnie d\'assurance santé',
                                        'niveau' => 'bronze',
                                        'montant' => 2000.00
                                    ]
                                ];
                                
                                $insertCount = 0;
                                $updateCount = 0;
                                
                                foreach ($sampleSponsors as $sponsor) {
                                    $stmt = $pdo->prepare("SELECT id FROM sponsors WHERE nom = ?");
                                    $stmt->execute([$sponsor['nom']]);
                                    $existing = $stmt->fetch();
                                    
                                    if ($existing) {
                                        $stmt = $pdo->prepare("UPDATE sponsors SET site_web = ?, description = ?, niveau = ?, montant = ?, actif = 1 WHERE nom = ?");
                                        $stmt->execute([
                                            $sponsor['site_web'],
                                            $sponsor['description'],
                                            $sponsor['niveau'],
                                            $sponsor['montant'],
                                            $sponsor['nom']
                                        ]);
                                        $updateCount++;
                                    } else {
                                        $stmt = $pdo->prepare("INSERT INTO sponsors (nom, site_web, description, niveau, montant, actif) VALUES (?, ?, ?, ?, ?, 1)");
                                        $stmt->execute([
                                            $sponsor['nom'],
                                            $sponsor['site_web'],
                                            $sponsor['description'],
                                            $sponsor['niveau'],
                                            $sponsor['montant']
                                        ]);
                                        $insertCount++;
                                    }
                                }
                                
                                echo "<div class='alert alert-success'>✓ $insertCount sponsors ajoutés, $updateCount mis à jour</div>";
                                
                                // ÉTAPE 3: Vérification finale
                                echo "<div class='mb-3'><strong>Étape 3: Vérification finale</strong></div>";
                                $totalSponsors = $pdo->query("SELECT COUNT(*) FROM sponsors")->fetchColumn();
                                $totalMontant = $pdo->query("SELECT COALESCE(SUM(montant), 0) FROM sponsors")->fetchColumn();
                                
                                echo "<div class='alert alert-info'>";
                                echo "<strong>Statistiques finales:</strong><br>";
                                echo "Total sponsors: <strong>$totalSponsors</strong><br>";
                                echo "Montant total: <strong>" . number_format($totalMontant, 2, ',', ' ') . " TND</strong>";
                                echo "</div>";
                                
                                echo "<div class='alert alert-success'><h5><i class='bi bi-check-circle'></i> Configuration terminée avec succès!</h5></div>";
                                
                                echo "<div class='mt-4 text-center'>";
                                echo "<a href='views/backoffice/dashboard.php' class='btn btn-primary btn-lg me-2'><i class='bi bi-speedometer2'></i> Voir le Tableau de Bord</a>";
                                echo "<a href='test_dashboard.php' class='btn btn-success btn-lg'><i class='bi bi-eye'></i> Test Dashboard</a>";
                                echo "</div>";
                                
                            } catch (Exception $e) {
                                echo "<div class='alert alert-danger'><h5><i class='bi bi-exclamation-triangle'></i> Erreur:</h5> " . $e->getMessage() . "</div>";
                            }
                        } else {
                        ?>
                        <div class="alert alert-info">
                            <h5><i class="bi bi-info-circle"></i> Ce script va effectuer les opérations suivantes:</h5>
                            <ul>
                                <li>Ajouter la colonne <code>montant</code> à la table <code>sponsors</code> si elle n'existe pas</li>
                                <li>Insérer 8 sponsors tunisiens avec des montants réalistes</li>
                                <li>Appliquer les montants par niveau:
                                    <ul>
                                        <li>Platinium: 15,000 TND</li>
                                        <li>Gold: 8,500-10,000 TND</li>
                                        <li>Silver: 5,500-6,000 TND</li>
                                        <li>Bronze: 2,000-3,000 TND</li>
                                    </ul>
                                </li>
                                <li>Vérifier que tout fonctionne correctement</li>
                            </ul>
                        </div>
                        
                        <form method="POST">
                            <button type="submit" class="btn btn-success btn-lg w-100">
                                <i class="bi bi-play-fill"></i> Lancer la configuration complète
                            </button>
                        </form>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
