<?php
require_once __DIR__ . '/config/database.php';

// Données de sponsors exemples
$sampleSponsors = [
    [
        'nom' => 'Tunisian Telecom',
        'logo' => 'uploads/sponsors/tunisian_telecom.png',
        'site_web' => 'https://www.tunisietelecom.tn',
        'description' => 'Leader des télécommunications en Tunisie, sponsor principal de nos événements médicaux',
        'niveau' => 'platinium',
        'montant' => 15000.00,
        'actif' => 1
    ],
    [
        'nom' => 'BIAT Bank',
        'logo' => 'uploads/sponsors/biat_bank.png',
        'site_web' => 'https://www.biat.com.tn',
        'description' => 'Banque Internationale Arabe de Tunisie, partenaire financier des événements de santé',
        'niveau' => 'gold',
        'montant' => 10000.00,
        'actif' => 1
    ],
    [
        'nom' => 'Société Tunisienne des Industries Pharmaceutiques',
        'logo' => 'uploads/sponsors/stip.png',
        'site_web' => 'https://www.stip.com.tn',
        'description' => 'Fabricant tunisien de médicaments et produits pharmaceutiques',
        'niveau' => 'gold',
        'montant' => 8500.00,
        'actif' => 1
    ],
    [
        'nom' => 'Clinique El Manar',
        'logo' => 'uploads/sponsors/clinique_manar.png',
        'site_web' => 'https://www.cliniquelmanar.tn',
        'description' => 'Centre médical spécialisé dans les traitements de pointe',
        'niveau' => 'silver',
        'montant' => 6000.00,
        'actif' => 1
    ],
    [
        'nom' => 'Pharmacie Centrale',
        'logo' => 'uploads/sponsors/pharmacie_centrale.png',
        'site_web' => 'https://www.pharmaciecentrale.tn',
        'description' => 'Distributeur principal de médicaments et équipements médicaux',
        'niveau' => 'silver',
        'montant' => 5500.00,
        'actif' => 1
    ],
    [
        'nom' => 'MediTech Solutions',
        'logo' => 'uploads/sponsors/meditech.png',
        'site_web' => 'https://www.meditech.tn',
        'description' => 'Fournisseur de solutions technologiques pour le secteur médical',
        'niveau' => 'bronze',
        'montant' => 3000.00,
        'actif' => 1
    ],
    [
        'nom' => 'Laboratoires Medlab',
        'logo' => 'uploads/sponsors/medlab.png',
        'site_web' => 'https://www.medlab.tn',
        'description' => 'Laboratoires d\'analyses médicales et de recherche',
        'niveau' => 'bronze',
        'montant' => 2500.00,
        'actif' => 1
    ],
    [
        'nom' => 'Assurance STAR',
        'logo' => 'uploads/sponsors/assurance_star.png',
        'site_web' => 'https://www.assurance-star.tn',
        'description' => 'Compagnie d\'assurance santé et prévoyance médicale',
        'niveau' => 'bronze',
        'montant' => 2000.00,
        'actif' => 1
    ]
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajout Sponsors Exemples</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h4 class="mb-0">Ajout de Sponsors Exemples</h4>
                    </div>
                    <div class="card-body">
                        <?php
                        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                            try {
                                $pdo = Database::getInstance()->getConnection();
                                
                                echo "<div class='alert alert-info'>Début de l'ajout des sponsors exemples...</div>";
                                
                                $insertCount = 0;
                                $updateCount = 0;
                                
                                foreach ($sampleSponsors as $sponsor) {
                                    // Vérifier si le sponsor existe déjà
                                    $stmt = $pdo->prepare("SELECT id FROM sponsors WHERE nom = ?");
                                    $stmt->execute([$sponsor['nom']]);
                                    $existing = $stmt->fetch();
                                    
                                    if ($existing) {
                                        // Mettre à jour le sponsor existant
                                        $stmt = $pdo->prepare("UPDATE sponsors SET logo = ?, site_web = ?, description = ?, niveau = ?, montant = ?, actif = ? WHERE nom = ?");
                                        $stmt->execute([
                                            $sponsor['logo'],
                                            $sponsor['site_web'],
                                            $sponsor['description'],
                                            $sponsor['niveau'],
                                            $sponsor['montant'],
                                            $sponsor['actif'],
                                            $sponsor['nom']
                                        ]);
                                        $updateCount++;
                                        echo "<div class='alert alert-warning'>✓ Sponsor '" . $sponsor['nom'] . "' mis à jour.</div>";
                                    } else {
                                        // Insérer un nouveau sponsor
                                        $stmt = $pdo->prepare("INSERT INTO sponsors (nom, logo, site_web, description, niveau, montant, actif) VALUES (?, ?, ?, ?, ?, ?, ?)");
                                        $stmt->execute([
                                            $sponsor['nom'],
                                            $sponsor['logo'],
                                            $sponsor['site_web'],
                                            $sponsor['description'],
                                            $sponsor['niveau'],
                                            $sponsor['montant'],
                                            $sponsor['actif']
                                        ]);
                                        $insertCount++;
                                        echo "<div class='alert alert-success'>✓ Sponsor '" . $sponsor['nom'] . "' ajouté avec succès.</div>";
                                    }
                                }
                                
                                echo "<div class='alert alert-success'><strong>Opération terminée!</strong><br>";
                                echo "Sponsors insérés: $insertCount<br>";
                                echo "Sponsors mis à jour: $updateCount</div>";
                                
                                // Afficher les statistiques
                                $totalSponsors = $pdo->query("SELECT COUNT(*) FROM sponsors")->fetchColumn();
                                $totalMontant = $pdo->query("SELECT COALESCE(SUM(montant), 0) FROM sponsors")->fetchColumn();
                                
                                echo "<div class='alert alert-info'>";
                                echo "<strong>Statistiques actuelles:</strong><br>";
                                echo "Total sponsors: $totalSponsors<br>";
                                echo "Montant total: " . number_format($totalMontant, 2, ',', ' ') . " TND";
                                echo "</div>";
                                
                                echo "<div class='mt-3'>";
                                echo "<a href='views/backoffice/dashboard.php' class='btn btn-primary'>Voir le tableau de bord</a> ";
                                echo "<a href='index.php' class='btn btn-secondary'>Retour à l'accueil</a>";
                                echo "</div>";
                                
                            } catch (Exception $e) {
                                echo "<div class='alert alert-danger'><strong>Erreur:</strong> " . $e->getMessage() . "</div>";
                            }
                        } else {
                        ?>
                        <div class="alert alert-info">
                            <h5>Cette opération va ajouter <?php echo count($sampleSponsors); ?> sponsors exemples:</h5>
                            <ul>
                                <?php foreach ($sampleSponsors as $sponsor): ?>
                                    <li><strong><?php echo $sponsor['nom']; ?></strong> - 
                                        <?php echo ucfirst($sponsor['niveau']); ?> - 
                                        <?php echo number_format($sponsor['montant'], 2, ',', ' '); ?> TND</li>
                                <?php endforeach; ?>
                            </ul>
                            <p class="mb-0">Les sponsors existants avec le même nom seront mis à jour.</p>
                        </div>
                        
                        <form method="POST">
                            <button type="submit" class="btn btn-success btn-lg w-100">Ajouter les sponsors exemples</button>
                        </form>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
