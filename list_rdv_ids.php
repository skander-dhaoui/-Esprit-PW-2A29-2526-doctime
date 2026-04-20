<?php
require_once __DIR__ . '/config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    $stmt = $db->query("SELECT id, date_rendezvous, motif FROM rendez_vous LIMIT 5");
    $rdvs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>📋 RDV Disponibles</h2>";
    echo "<pre>";
    print_r($rdvs);
    echo "</pre>";
    
    if (!empty($rdvs)) {
        $first_id = $rdvs[0]['id'];
        echo "<p><a href='test_view_rdv.php?id=$first_id'>Test avec id=$first_id</a></p>";
    }
    
} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage();
}
?>
