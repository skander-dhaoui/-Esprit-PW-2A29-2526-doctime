-- Ajouter une table pour les commentaires d'événements
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
