-- Migration: Création table des avis/reviews
-- Date: 2026-05-02

CREATE TABLE IF NOT EXISTS reviews (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    sentiment VARCHAR(50) DEFAULT NULL COMMENT 'positive, neutral, negative',
    sentiment_score FLOAT DEFAULT NULL COMMENT 'Score entre -1 et 1',
    is_approved BOOLEAN DEFAULT FALSE COMMENT 'Modération avant publication',
    has_profanity BOOLEAN DEFAULT FALSE COMMENT 'Contient du contenu inapproprié',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_sentiment (sentiment),
    INDEX idx_created (created_at),
    INDEX idx_approved (is_approved)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table pour les emojis utilisés dans les avis
CREATE TABLE IF NOT EXISTS review_emojis (
    id INT PRIMARY KEY AUTO_INCREMENT,
    review_id INT NOT NULL,
    emoji VARCHAR(10) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (review_id) REFERENCES reviews(id) ON DELETE CASCADE,
    INDEX idx_review (review_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table pour les réponses aux avis (modérateur)
CREATE TABLE IF NOT EXISTS review_replies (
    id INT PRIMARY KEY AUTO_INCREMENT,
    review_id INT NOT NULL,
    moderator_id INT NOT NULL,
    reply_text TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (review_id) REFERENCES reviews(id) ON DELETE CASCADE,
    FOREIGN KEY (moderator_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_review (review_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
