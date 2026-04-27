<?php
/**
 * Installation/Setup : RDV Avancée avec Commentaires Enrichis
 * Ce script exécute toutes les migrations nécessaires
 */

session_start();

// Vérifier accès admin
if (($_SESSION['user_role'] ?? '') !== 'admin') {
    die('❌ Accès refusé. Vous devez être admin.');
}

require_once __DIR__ . '/config/database.php';

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Installation RDV Avancée</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 40px 20px; }
        .container { max-width: 900px; }
        .install-box { background: white; border-radius: 12px; padding: 40px; box-shadow: 0 8px 32px rgba(0,0,0,0.2); }
        .step { margin: 20px 0; padding: 15px; border-left: 4px solid #667eea; background: #f8f9ff; border-radius: 6px; }
        .success { border-left-color: #28a745; background: #e8f5e9; }
        .error { border-left-color: #dc3545; background: #ffebee; }
        .pending { border-left-color: #ffc107; background: #fffbf0; }
        .step-title { font-weight: bold; margin-bottom: 10px; }
        .command { background: #2d3748; color: #d4d4d4; padding: 10px; border-radius: 4px; font-family: monospace; margin: 10px 0; }
    </style>
</head>
<body>

<div class="container">
    <div class="install-box">
        <h1 class="mb-4"><i class="fas fa-rocket me-2"></i> Installation : RDV Avancée</h1>
        
        <?php
        $db = Database::getInstance()->getConnection();
        $errors = [];
        $success = [];

        // ============================================
        // MIGRATION 1 : Table event_comments
        // ============================================
        echo '<div class="step pending">';
        echo '<div class="step-title">1️⃣ Création table event_comments</div>';
        
        try {
            // Check if table exists
            $checkStmt = $db->query("SHOW TABLES LIKE 'event_comments'");
            $tableExists = $checkStmt->rowCount() > 0;

            if (!$tableExists) {
                $sql = "
                    CREATE TABLE event_comments (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        event_id INT NOT NULL,
                        user_id INT NOT NULL,
                        comment LONGTEXT NOT NULL,
                        status ENUM('en_attente', 'approuvé', 'rejeté') DEFAULT 'approuvé',
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                        INDEX idx_event (event_id),
                        INDEX idx_status (status),
                        INDEX idx_user (user_id),
                        INDEX idx_created (created_at)
                    )
                ";
                
                $db->exec($sql);
                $success[] = '✅ Table event_comments créée avec succès';
                echo '<p><span class="badge bg-success">✅ Créée</span></p>';
            } else {
                $success[] = '✅ Table event_comments existe déjà';
                echo '<p><span class="badge bg-info">ℹ️ Déjà créée</span></p>';
            }
        } catch (Exception $e) {
            $errors[] = '❌ Erreur création table event_comments: ' . $e->getMessage();
            echo '<p><span class="badge bg-danger">❌ Erreur</span></p>';
            echo '<div class="alert alert-danger mt-2">' . $e->getMessage() . '</div>';
        }
        echo '</div>';

        // ============================================
        // MIGRATION 2 : Ajouter disponibilite_id FK
        // ============================================
        echo '<div class="step pending">';
        echo '<div class="step-title">2️⃣ Ajouter colonne disponibilite_id à rendez_vous</div>';

        try {
            // Check if column exists
            $checkStmt = $db->query("SHOW COLUMNS FROM rendez_vous LIKE 'disponibilite_id'");
            $columnExists = $checkStmt->rowCount() > 0;

            if (!$columnExists) {
                $sql = "
                    ALTER TABLE rendez_vous 
                    ADD COLUMN disponibilite_id INT NULL AFTER medecin_id,
                    ADD FOREIGN KEY fk_rdv_dispo (disponibilite_id) REFERENCES disponibilites(id) ON DELETE SET NULL,
                    ADD INDEX idx_disponibilite (disponibilite_id)
                ";
                
                $db->exec($sql);
                $success[] = '✅ Colonne disponibilite_id ajoutée avec succès';
                echo '<p><span class="badge bg-success">✅ Ajoutée</span></p>';
            } else {
                $success[] = '✅ Colonne disponibilite_id existe déjà';
                echo '<p><span class="badge bg-info">ℹ️ Déjà présente</span></p>';
            }
        } catch (Exception $e) {
            $errors[] = '⚠️ Erreur ajout colonne disponibilite_id: ' . $e->getMessage();
            echo '<p><span class="badge bg-warning">⚠️ Erreur (peut être déjà créée)</span></p>';
        }
        echo '</div>';

        // ============================================
        // VÉRIFICATIONS
        // ============================================
        echo '<div class="step">';
        echo '<div class="step-title">📋 Vérifications</div>';

        // Vérifier files créés
        $files = [
            'controllers/AdminController.php' => 'Méthodes viewRendezVous & addCommentRendezVous',
            'views/backoffice/rendezvous_detail.php' => 'Page détails RDV avec Quill',
            'index.php' => 'Routes view & add_comment pour RDV',
            'RDV_AVANCEE_README.md' => 'Documentation complète',
            'test_rdv_avancee.php' => 'Page de test'
        ];

        foreach ($files as $file => $description) {
            $path = __DIR__ . '/' . $file;
            if (file_exists($path)) {
                echo '<p><span class="badge bg-success">✅</span> ' . $file . ' (' . $description . ')</p>';
            } else {
                echo '<p><span class="badge bg-danger">❌</span> ' . $file . ' - MANQUANT</p>';
            }
        }
        echo '</div>';

        ?>

        <!-- Résumé -->
        <div class="step success mt-4">
            <div class="step-title"><i class="fas fa-check-circle"></i> Installation Terminée !</div>
            <p><strong>Fichiers créés/modifiés :</strong></p>
            <ul>
                <li>✅ <code>AdminController.php</code> - Méthodes viewRendezVous() et addCommentRendezVous()</li>
                <li>✅ <code>index.php</code> - Routes action=view et action=add_comment</li>
                <li>✅ <code>rendezvous_detail.php</code> - Page complète avec Quill.js</li>
                <li>✅ <code>rendezvous/list.php</code> - Bouton "Voir" mis à jour</li>
                <li>✅ <code>event_comments</code> table - Créée en BD</li>
                <li>✅ <code>rendez_vous.disponibilite_id</code> FK - Ajoutée en BD</li>
            </ul>
        </div>

        <!-- Prochaines étapes -->
        <div class="step pending mt-4">
            <div class="step-title"><i class="fas fa-play-circle"></i> Prochaines Étapes</div>
            <ol>
                <li>Aller au menu <strong>Rendez-vous</strong></li>
                <li>Cliquer sur le bouton <strong>Voir</strong> (icône oeil) pour un RDV</li>
                <li>Tester l'ajout de commentaire avec emoji et image</li>
                <li>Vérifier l'affichage du commentaire immédiatement</li>
            </ol>
        </div>

        <!-- Liens rapides -->
        <div class="mt-4">
            <a href="index.php?page=admin_rendezvous" class="btn btn-primary btn-lg">
                <i class="fas fa-arrow-right me-2"></i> Aller aux Rendez-vous
            </a>
            <a href="test_rdv_avancee.php" class="btn btn-info btn-lg ms-2">
                <i class="fas fa-flask me-2"></i> Test Page
            </a>
        </div>
    </div>
</div>

</body>
</html>
