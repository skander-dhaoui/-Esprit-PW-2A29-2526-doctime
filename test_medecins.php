<?php
// Test pour vérifier les médecins en base de données
require_once __DIR__ . '/config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // Vérifier les utilisateurs de type médecin
    $stmt = $db->query("SELECT COUNT(*) as count FROM users WHERE role = 'medecin'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Nombre d'utilisateurs médecin: " . $result['count'] . "\n\n";
    
    // Lister les médecins avec leurs infos
    $stmt = $db->query("
        SELECT u.id as user_id, u.nom, u.prenom, u.email, u.telephone, u.statut, u.created_at,
               m.specialite, m.numero_ordre, m.annee_experience, m.consultation_prix, m.cabinet_adresse,
               m.statut_validation
        FROM users u
        LEFT JOIN medecins m ON u.id = m.user_id
        WHERE u.role = 'medecin'
        ORDER BY u.created_at DESC
    ");
    
    $medecins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($medecins)) {
        echo "❌ Aucun médecin trouvé en base de données\n";
        echo "\nPour tester, créons des médecins de test...\n";
    } else {
        echo "✓ Médecins trouvés: " . count($medecins) . "\n\n";
        foreach ($medecins as $medecin) {
            echo "ID: " . $medecin['user_id'] . " | " . $medecin['prenom'] . " " . $medecin['nom'] . " | " . $medecin['email'] . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage() . "\n";
}
?>
