-- Correction blog : remplace l’ancienne table `replies` par `reply` (comme models/Reply.php).
-- À exécuter dans phpMyAdmin sur `doctime_db` SI vous avez encore l’erreur
-- "Table 'doctime_db.reply' doesn't exist" après une ancienne importation.

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS replies;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE IF NOT EXISTS reply (
    id_reply INT AUTO_INCREMENT PRIMARY KEY,
    id_article INT NOT NULL,
    user_id INT NULL,
    type_reply VARCHAR(32) NOT NULL DEFAULT 'text',
    contenu_text TEXT NULL,
    emoji VARCHAR(64) NULL,
    photo VARCHAR(512) NULL,
    auteur VARCHAR(255) NULL,
    date_reply TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_article) REFERENCES articles(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_article (id_article),
    INDEX idx_date (date_reply),
    INDEX idx_user (user_id)
);
