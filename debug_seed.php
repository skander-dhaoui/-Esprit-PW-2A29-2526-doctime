<?php
require_once __DIR__ . '/config/database.php';
$db = Database::getInstance();

echo "Début du seeding des événements...\n";

// S'assurer qu'au moins un utilisateur existe pour servir de créateur et de participant
$users = $db->query("SELECT id FROM users LIMIT 10");
if (empty($users)) {
    // Créer un utilisateur fictif
    $db->execute("INSERT INTO users (nom, prenom, email, mot_de_passe, role) VALUES ('Admin', 'Test', 'admin@test.com', 'pwd123', 'admin')");
    $users = $db->query("SELECT id FROM users");
}

$userIds = array_column($users, 'id');
$adminId = $userIds[0];

// Spécialités et types
$types = ['Cardiologie', 'Dermatologie', 'Médecine générale', 'Chirurgie', 'Pédiatrie'];

// Titres
$titles = [
    'Congrès annuel de Cardiologie',
    'Symposium sur les maladies de peau',
    'Atelier pratique : Sutures avancées',
    'Conférence sur la santé publique',
    'Webinaire : Urgences pédiatriques',
    'Formation continue en médecine générale',
    'Séminaire sur les avancées chirurgicales'
];

$insertedEvents = [];

// Insérer 7 événements
foreach ($titles as $i => $title) {
    $prix = rand(0, 1) ? rand(50, 500) : 0; // 50% de chance d'être gratuit (0)
    $capacite = rand(20, 200);
    $type = $types[array_rand($types)];
    $slug = "event-test-" . time() . "-$i";
    
    $db->execute("
        INSERT INTO events (titre, slug, description, type, date_debut, date_fin, capacite_max, prix, status, createur_id, createur_type, created_at)
        VALUES (:titre, :slug, :desc, :type, DATE_ADD(NOW(), INTERVAL :jours DAY), DATE_ADD(NOW(), INTERVAL :jours+1 DAY), :cap, :prix, 'à venir', :cid, 'admin', NOW())
    ", [
        ':titre' => $title,
        ':slug' => $slug,
        ':desc' => "Description pour " . $title,
        ':type' => $type,
        ':jours' => rand(-10, 30),
        ':cap' => $capacite,
        ':prix' => $prix,
        ':cid' => $adminId
    ]);
    
    $insertedEvents[] = $db->lastInsertId();
}

echo "7 événements créés avec succès.\n";

// Insérer des participations
echo "Génération des participations...\n";
foreach ($insertedEvents as $eventId) {
    // Nombre de participants aléatoire
    $numParticipants = rand(0, min(15, count($userIds) * 3)); 
    
    if ($numParticipants > 0) {
        $statusOptions = ['confirmé', 'confirmé', 'en attente', 'confirmé']; // Plus de chances d'être confirmé
        
        // Comme on a peut-être qu'un seul utilisateur, on va simuler son inscription multiple juste pour tester le total inscrits
        // En vrai (COUNT(*)), même si le user est le même c'est la ligne qui compte pour nos graphiques.
        for ($j = 0; $j < $numParticipants; $j++) {
            $uId = $userIds[array_rand($userIds)];
            $statut = $statusOptions[array_rand($statusOptions)];
            
            // On ignore les erreurs d'unicité event_id/user_id si elles sont définies dans la base avec IGNORE
            try {
                // On met une date aléatoire dans les 5 derniers jours
                $db->execute("
                    INSERT INTO participations (event_id, user_id, statut, date_inscription)
                    VALUES (:eid, :uid, :statut, DATE_SUB(NOW(), INTERVAL :jours DAY))
                ", [
                    ':eid' => $eventId,
                    ':uid' => $uId,
                    ':statut' => $statut,
                    ':jours' => rand(0, 5)
                ]);
            } catch (Exception $e) {
                // Ignorer si l'utilisateur est déjà inscrit à cet événement
            }
        }
    }
}

echo "Génération des données terminée ! Vous pouvez maintenant rafraîchir la page des événements.\n";
