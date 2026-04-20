<?php
session_start();
require_once __DIR__ . '/config/database.php';

$_SESSION['user_id'] = 1;
$_SESSION['user_role'] = 'admin';

$id = $_GET['id'] ?? 8;  // Default to id=8 qui existe

echo "<h2>🧪 Test viewRendezVous(id=$id)</h2>";

try {
    $db = Database::getInstance()->getConnection();
    
    // Test 1: RDV existe?
    echo "<h3>1. Vérifier RDV existe</h3>";
    $stmt = $db->prepare("SELECT id FROM rendez_vous WHERE id = ?");
    $stmt->execute([$id]);
    $rdv_check = $stmt->fetch();
    echo $rdv_check ? "✅ RDV trouvé\n" : "❌ RDV NOT FOUND\n";
    
    // Test 2: Query complète
    echo "<h3>2. Récupérer RDV complet</h3>";
    $stmt = $db->prepare("
        SELECT rv.*,
               u_patient.prenom AS patient_prenom, u_patient.nom AS patient_nom, 
               u_patient.email AS patient_email,
               u_medecin.prenom AS medecin_prenom, u_medecin.nom AS medecin_nom,
               u_medecin.email AS medecin_email,
               m.specialite
        FROM rendez_vous rv
        JOIN users u_patient ON rv.patient_id = u_patient.id
        JOIN users u_medecin ON rv.medecin_id = u_medecin.id
        LEFT JOIN medecins m ON rv.medecin_id = m.user_id
        WHERE rv.id = ?
    ");
    $stmt->execute([$id]);
    $rdv = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($rdv) {
        echo "✅ RDV chargé:\n";
        echo "<pre>" . print_r($rdv, true) . "</pre>";
    } else {
        echo "❌ Erreur SQL (JOIN?)\n";
    }
    
    // Test 3: Comments table exists?
    echo "<h3>3. Vérifier table event_comments</h3>";
    $check = $db->query("SHOW TABLES LIKE 'event_comments'");
    echo $check->rowCount() > 0 ? "✅ Table existe\n" : "❌ Table NOT FOUND\n";
    
    // Test 4: Comments
    if ($check->rowCount() > 0) {
        echo "<h3>4. Récupérer commentaires</h3>";
        $commentsStmt = $db->prepare("
            SELECT ec.*, u.nom, u.prenom, 
                   CONCAT(u.prenom, ' ', u.nom) as user_name
            FROM event_comments ec 
            LEFT JOIN users u ON ec.user_id = u.id 
            WHERE ec.event_id = ? AND ec.status = 'approuvé'
            ORDER BY ec.created_at DESC
        ");
        $commentsStmt->execute([$id]);
        $comments = $commentsStmt->fetchAll(PDO::FETCH_ASSOC);
        echo "✅ " . count($comments) . " commentaires trouvés\n";
    }
    
    // Test 5: View file exists?
    echo "<h3>5. Vérifier fichier vue</h3>";
    $viewPath = __DIR__ . '/views/backoffice/rendezvous_detail.php';
    echo file_exists($viewPath) ? "✅ Fichier existe\n" : "❌ Fichier NOT FOUND\n";
    
    echo "<hr><p><a href='index.php?page=admin_rendezvous&action=view&id=$id'>Essayer la page directe</a></p>";
    
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage();
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
