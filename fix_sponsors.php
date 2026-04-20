<?php
echo "<h1>🔧 Correction automatique des sponsors</h1>";
echo "<pre>";

try {
    $pdo = new PDO('mysql:host=localhost;charset=utf8', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Vérifier/créer la base
    $stmt = $pdo->query('SHOW DATABASES LIKE "doctime_db"');
    if (!$stmt->fetch()) {
        echo "🔄 Création de la base de données...\n";
        $pdo->exec("CREATE DATABASE doctime_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    }
    $pdo->exec("USE doctime_db");

    // Vérifier la table sponsors
    $stmt = $pdo->query('SHOW TABLES LIKE "sponsors"');
    if (!$stmt->fetch()) {
        echo "🔄 Création de la table sponsors...\n";
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS sponsors (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nom VARCHAR(100) NOT NULL,
                logo VARCHAR(255),
                site_web VARCHAR(255),
                description TEXT,
                niveau ENUM('platinium', 'gold', 'silver', 'bronze') DEFAULT 'bronze',
                actif BOOLEAN DEFAULT TRUE,
                email VARCHAR(255),
                telephone VARCHAR(20),
                secteur VARCHAR(100),
                budget DECIMAL(15,2),
                statut ENUM('actif', 'inactif', 'archive') DEFAULT 'actif',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_niveau (niveau)
            )
        ");
    }

    // Vérifier si les sponsors existent
    $stmt = $pdo->query('SELECT COUNT(*) FROM sponsors');
    $count = $stmt->fetchColumn();

    if ($count == 0) {
        echo "🔄 Insertion des sponsors manquants...\n";
        $pdo->exec("
            INSERT INTO sponsors (nom, email, telephone, secteur, budget, description, logo, site_web, statut, niveau, created_at, updated_at) VALUES
            ('BioLab Tunisie', 'biolabtn@gmail.com', '73456789', 'Argent', 5000, 'Laboratoire d\'analyses médicales', NULL, NULL, 'actif', 'gold', NOW(), NOW()),
            ('Delice Holding', 'delicetunisie@gmail.com', '71454118', 'Platine', 10000, 'Groupe pharmaceutique tunisien', NULL, NULL, 'actif', 'platinium', NOW(), NOW()),
            ('MedTech Solutions', 'info@medtech.tn', '72345678', 'Platine', 8000, 'Solutions technologiques médicales', NULL, 'https://medtech.tn', 'actif', 'platinium', NOW(), NOW()),
            ('PharmaCorp', 'contact@pharmacorp.com', '71234567', 'Or', 3000, 'Distributeur de médicaments', NULL, 'https://pharmacorp.com', 'actif', 'gold', NOW(), NOW()),
            ('Validation', 'validation@gmail.com', '73556773', 'Bronze', 1500, 'Cabinet de validation', NULL, NULL, 'actif', 'bronze', NOW(), NOW())
        ");
        echo "✅ 5 sponsors insérés\n";
    } else {
        echo "✅ $count sponsors déjà présents\n";
    }

    // Vérifier les événements
    $stmt = $pdo->query('SELECT COUNT(*) FROM events');
    $eventsCount = $stmt->fetchColumn();

    if ($eventsCount == 0) {
        echo "🔄 Insertion des événements manquants...\n";
        $pdo->exec("
            INSERT INTO events (titre, slug, description, date_debut, date_fin, lieu, adresse, capacite_max, places_restantes, prix, status, created_at, updated_at) VALUES
            ('Congrès de Cardiologie', 'congres-cardiologie', 'Une journée dédiée aux avancées en cardiologie.', '2026-07-20 09:00:00', '2026-07-20 17:00:00', 'Centre de Conférences Tunis', 'Rue de la Santé, Tunis', 120, 120, 180.00, 'à venir', NOW(), NOW()),
            ('Atelier Dermatologie & Peau', 'atelier-dermatologie-peau', 'Atelier pratique sur le diagnostic et les traitements de la peau.', '2026-08-14 10:00:00', '2026-08-14 15:00:00', 'Institut Dermatologique', 'Avenue Habib Bourguiba, La Marsa', 80, 80, 120.00, 'à venir', NOW(), NOW()),
            ('Forum Esthétique Médicale', 'forum-esthetique-medicale', 'Forum professionnel sur les innovations en médecine esthétique.', '2026-09-05 09:30:00', '2026-09-05 16:30:00', 'Palais des Expositions', 'Sousse, Tunisie', 100, 100, 150.00, 'à venir', NOW(), NOW())
        ");
        echo "✅ 3 événements insérés\n";
    } else {
        echo "✅ $eventsCount événements déjà présents\n";
    }

    // Lier les événements aux sponsors
    echo "🔄 Liaison événements-sponsors...\n";
    $pdo->exec("UPDATE events SET sponsor_id = 3 WHERE titre LIKE '%Cardiologie%' AND sponsor_id IS NULL");
    $pdo->exec("UPDATE events SET sponsor_id = 4 WHERE titre LIKE '%Dermatologie%' AND sponsor_id IS NULL");
    $pdo->exec("UPDATE events SET sponsor_id = 1 WHERE titre LIKE '%Esthétique%' AND sponsor_id IS NULL");

    // Vérification finale
    $stmt = $pdo->query('SELECT COUNT(*) FROM sponsors WHERE statut = "actif"');
    $activeSponsors = $stmt->fetchColumn();

    $stmt = $pdo->query('SELECT COUNT(*) FROM events WHERE sponsor_id IS NOT NULL');
    $eventsWithSponsors = $stmt->fetchColumn();

    echo "\n✅ CORRECTION TERMINÉE\n";
    echo "📊 Sponsors actifs: $activeSponsors\n";
    echo "📊 Événements avec sponsors: $eventsWithSponsors\n";

} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
}

echo "</pre>";
echo "<hr>";
echo "<h3>🎉 Sponsors corrigés!</h3>";
echo "<p><a href='index.php?page=sponsors'>Voir les sponsors</a> | <a href='index.php?page=evenements'>Voir les événements</a></p>";
?>