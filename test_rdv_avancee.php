<?php
/**
 * Test : RDV Avancée avec commentaires enrichis
 */

session_start();
require_once __DIR__ . '/config/database.php';

$_SESSION['user_id'] = 1;
$_SESSION['user_name'] = 'Test Admin';
$_SESSION['user_role'] = 'admin';

try {
    $db = Database::getInstance()->getConnection();
    
    // Get one RDV
    $stmt = $db->query("
        SELECT rv.id, rv.date_rendezvous, 
               u_patient.prenom as patient_prenom, u_patient.nom as patient_nom,
               u_medecin.prenom as medecin_prenom, u_medecin.nom as medecin_nom
        FROM rendez_vous rv
        JOIN users u_patient ON rv.patient_id = u_patient.id
        JOIN users u_medecin ON rv.medecin_id = u_medecin.id
        LIMIT 1
    ");
    $rdv = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $rdv = null;
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Test RDV Avancée</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f0f4f8; padding: 40px 20px; }
        .container { max-width: 900px; }
        .test-box { background: white; border-radius: 12px; padding: 24px; box-shadow: 0 1px 6px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .status-ok { background: #e8f5e9; color: #2e7d32; border: 1px solid #a5d6a7; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
    </style>
</head>
<body>

<div class="container">
    <h1>✨ Test : RDV Avancée avec Commentaires</h1>

    <div class="status-ok">
        <strong>✓ Fonctionnalités Ajoutées :</strong><br>
        <i class="fas fa-check me-2"></i> Page de détails complète des RDV<br>
        <i class="fas fa-check me-2"></i> Commentaires enrichis (texte + images + emoji)<br>
        <i class="fas fa-check me-2"></i> Liaison RDV ↔ Disponibilité<br>
        <i class="fas fa-check me-2"></i> Éditeur Quill.js pour RDV
    </div>

    <div class="test-box">
        <h3>📋 Structure Ajoutée</h3>
        <ul>
            <li><strong>Table event_comments :</strong> Stocke les commentaires des RDV/Événements</li>
            <li><strong>FK rendez_vous → disponibilites :</strong> Lie chaque RDV à une disponibilité</li>
            <li><strong>Méthode viewRendezVous() :</strong> Affiche détails complets du RDV</li>
            <li><strong>Méthode addCommentRendezVous() :</strong> Ajoute commentaire enrichi</li>
        </ul>
    </div>

    <div class="test-box">
        <h3>🔗 Routes Disponibles</h3>
        <ul>
            <li><strong>Voir RDV :</strong> <code>index.php?page=admin_rendezvous&action=view&id=ID</code></li>
            <li><strong>Ajouter commentaire :</strong> <code>index.php?page=admin_rendezvous&action=add_comment&id=ID</code></li>
        </ul>
    </div>

    <?php if ($rdv): ?>
    <div class="test-box">
        <h3>🧪 Tester Maintenant</h3>
        <p><strong>RDV Disponible :</strong></p>
        <ul>
            <li>📅 Date : <?= date('d/m/Y', strtotime($rdv['date_rendezvous'])) ?></li>
            <li>👤 Patient : <?= htmlspecialchars(($rdv['patient_prenom'] ?? '') . ' ' . ($rdv['patient_nom'] ?? '')) ?></li>
            <li>👨‍⚕️ Médecin : <?= htmlspecialchars(($rdv['medecin_prenom'] ?? '') . ' ' . ($rdv['medecin_nom'] ?? '')) ?></li>
        </ul>
        
        <a href="index.php?page=admin_rendezvous&action=view&id=<?= $rdv['id'] ?>" target="_blank" class="btn btn-primary btn-lg">
            <i class="fas fa-external-link-alt me-2"></i> Voir les détails du RDV
        </a>
    </div>

    <div class="test-box">
        <h3>📝 Fonctionnalités de la Page Détails</h3>
        <ul>
            <li>✅ <strong>Infos RDV :</strong> Patient, médecin, date, heure, statut, motif</li>
            <li>✅ <strong>Disponibilité liée :</strong> Affichage de la disponibilité du médecin</li>
            <li>✅ <strong>Commentaires enrichis :</strong> Quill.js avec emojis et images</li>
            <li>✅ <strong>Boutons d'action :</strong> Modifier, Supprimer, Retour</li>
            <li>✅ <strong>Logs d'action :</strong> Chaque commentaire est enregistré</li>
        </ul>
    </div>

    <?php else: ?>
    <div class="test-box alert alert-warning">
        <i class="fas fa-exclamation-triangle me-2"></i>
        Aucun RDV trouvé dans la base de données. Créez d'abord un RDV.
    </div>
    <?php endif; ?>

    <div class="test-box">
        <h3>🗄️ Migration SQL à Exécuter</h3>
        <p>Exécutez cette migration pour ajouter la table des commentaires :</p>
        <pre style="background: #f5f5f5; padding: 12px; border-radius: 6px; font-size: 12px">
-- Créer la table event_comments
CREATE TABLE IF NOT EXISTS event_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    user_id INT NOT NULL,
    comment LONGTEXT NOT NULL,
    status ENUM('en_attente', 'approuvé', 'rejeté') DEFAULT 'approuvé',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_event (event_id),
    INDEX idx_status (status),
    INDEX idx_user (user_id)
);

-- Ajouter colonne disponibilite_id à rendez_vous
ALTER TABLE rendez_vous 
ADD COLUMN disponibilite_id INT NULL AFTER medecin_id,
ADD FOREIGN KEY (disponibilite_id) REFERENCES disponibilites(id) ON DELETE SET NULL,
ADD INDEX idx_disponibilite (disponibilite_id);</pre>
    </div>

    <div class="test-box">
        <h3>🎯 Flux Complet</h3>
        <ol>
            <li>Admin clique sur "Voir" dans la liste des RDV</li>
            <li>Page affiche tous les détails du RDV</li>
            <li>Admin voit la section "Commentaires"</li>
            <li>Admin clique sur "Emoji" ou "Image" pour ajouter du contenu enrichi</li>
            <li>Admin tape son commentaire avec formatage</li>
            <li>Clique "Publier le commentaire"</li>
            <li>Commentaire s'ajoute à la liste et est enregistré en DB</li>
        </ol>
    </div>
</div>

</body>
</html>
