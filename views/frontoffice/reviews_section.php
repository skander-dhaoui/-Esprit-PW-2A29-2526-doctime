<?php
// Vue des avis pour l'accueil
?>

<!-- SECTION AVIS -->
<div class="reviews-section mt-5 pt-5" style="border-top: 2px solid #e0e0e0;">
    <div class="mb-4">
        <h3 class="fw-bold mb-2">
            <i class="fas fa-star me-2" style="color: #ffc107;"></i>
            Avis de nos patients
        </h3>
        <p class="text-muted">Découvrez ce que nos patients pensent de nos services</p>
    </div>

    <!-- Stats -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="stat-box text-center" style="padding: 20px; background: #f8f9fa; border-radius: 12px;">
                <h4 style="color: #4CAF50; font-size: 32px;"><?= $stats['total'] ?? 0 ?></h4>
                <p class="text-muted small">Avis publiés</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-box text-center" style="padding: 20px; background: #f8f9fa; border-radius: 12px;">
                <h4 style="color: #ffc107; font-size: 32px;">
                    <i class="fas fa-star"></i> <?= $stats['average_rating'] ?? 0 ?>
                </h4>
                <p class="text-muted small">Note moyenne</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-box text-center" style="padding: 20px; background: #f8f9fa; border-radius: 12px;">
                <?php
                    $positiveCount = $stats['by_sentiment']['positive'] ?? 0;
                    $totalCount = $stats['total'] ?? 0;
                    $percentage = $totalCount > 0 ? ($positiveCount / $totalCount) * 100 : 0;
                ?>
                <h4 style="color: #28a745; font-size: 32px;"><?= round($percentage) ?>%</h4>
                <p class="text-muted small">Avis positifs</p>
            </div>
        </div>
    </div>

    <!-- Formulaire d'avis -->
    <?php if (!empty($_SESSION['user_id'])): ?>
    <div class="mt-4 mb-4">
        <div class="review-form-card" style="background: #f8f9fa; padding: 25px; border-radius: 12px; border-left: 4px solid #4CAF50;">
            <h5 class="mb-3 fw-bold">
                <i class="fas fa-pen-fancy me-2" style="color: #4CAF50;"></i>
                Partager votre avis
            </h5>

            <form id="reviewForm" method="POST">
                <!-- Titre -->
                <div class="mb-3">
                    <label for="reviewTitle" class="form-label fw-600 small">Titre *</label>
                    <input 
                        type="text" 
                        id="reviewTitle" 
                        name="title" 
                        class="form-control" 
                        placeholder="Résumez votre expérience en quelques mots"
                        maxlength="100"
                    >
                    <small class="text-muted">Max 100 caractères</small>
                </div>

                <!-- Rating -->
                <div class="mb-3">
                    <label class="form-label fw-600 small">Note *</label>
                    <div class="rating-picker" id="ratingPicker">
                        <input type="hidden" id="ratingInput" name="rating" value="5">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="fas fa-star rating-star" data-rating="<?= $i ?>" style="font-size: 24px; cursor: pointer; color: #ddd; margin-right: 8px; transition: all 0.2s;"></i>
                        <?php endfor; ?>
                    </div>
                </div>

                <!-- Contenu -->
                <div class="mb-3">
                    <label for="reviewContent" class="form-label fw-600 small d-flex justify-content-between align-items-center w-100">
                        <span>Votre avis *</span>
                        <button type="button" id="micBtn" class="btn btn-sm btn-outline-primary" style="padding: 2px 8px; border-radius: 20px;" title="Dicter vocalement">
                            <i class="fas fa-microphone"></i> Dicter
                        </button>
                    </label>
                    <textarea 
                        id="reviewContent" 
                        name="content" 
                        class="form-control" 
                        rows="4"
                        placeholder="Partagez vos impressions (ou dictez vocalement)..."
                        maxlength="2000"
                    ></textarea>
                    <div class="d-flex justify-content-between mt-2 align-items-center">
                        <small class="text-muted">Min 10, Max 2000 caractères</small>
                        <span id="sentimentDisplay" class="badge bg-secondary">Sentiment: En attente...</span>
                        <small id="charCount" class="text-muted">0/2000</small>
                    </div>
                </div>

                <!-- Emojis -->
                <div class="mb-3">
                    <label class="form-label fw-600 small">
                        <i class="fas fa-smile me-2" style="color: #FFD700;"></i>
                        Emojis (optionnel)
                    </label>
                    <div id="emojiPicker" class="emoji-picker" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(35px, 1fr)); gap: 6px; padding: 10px; background: white; border-radius: 8px; border: 1px solid #ddd;"></div>
                    <div id="selectedEmojis" class="mt-2"></div>
                </div>

                <!-- Boutons -->
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-success flex-grow-1">
                        <i class="fas fa-check me-2"></i>Publier l'avis
                    </button>
                    <button type="reset" class="btn btn-outline-secondary">
                        <i class="fas fa-redo me-2"></i>Réinitialiser
                    </button>
                </div>

                <!-- Messages d'erreur -->
                <div id="formErrors" class="alert alert-danger mt-3" style="display:none;"></div>

                <small class="d-block mt-3 text-muted">
                    <i class="fas fa-info-circle me-1"></i>
                    Les avis contenant du contenu inapproprié nécessiteront une modération.
                </small>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- Liste des avis -->
    <div class="mt-4">
        <h5 class="fw-bold mb-3">Derniers avis</h5>

        <?php if (empty($reviews)): ?>
            <div class="alert alert-info text-center">
                Aucun avis pour le moment. Soyez le premier!
            </div>
        <?php else: ?>
            <div class="reviews-list">
                <?php foreach ($reviews as $review): ?>
                <div class="review-card mb-3" style="background: #f8f9fa; padding: 20px; border-radius: 10px; border-left: 4px solid #4CAF50;">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <h6 class="mb-1 fw-bold"><?= htmlspecialchars($review['title']) ?></h6>
                            <p class="mb-0 text-muted small">
                                par <strong><?= htmlspecialchars($review['prenom'] . ' ' . $review['nom']) ?></strong>
                                le <?= date('d/m/Y', strtotime($review['created_at'])) ?>
                            </p>
                        </div>
                        <div class="text-end">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star" style="color: <?= $i <= $review['rating'] ? '#ffc107' : '#ddd' ?>; font-size: 14px;"></i>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <p class="mb-2" style="color: #333; line-height: 1.5; font-size: 14px;">
                        <?= nl2br(htmlspecialchars(substr($review['content'], 0, 150))) ?>...
                    </p>
                    <?php if (!empty($review['emojis'])): ?>
                    <div class="small">
                        <?php foreach ($review['emojis'] as $emoji): ?>
                            <span style="font-size: 18px; margin-right: 4px;"><?= $emoji ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.rating-star {
    transition: all 0.2s ease;
}

.rating-star:hover,
.rating-star.active {
    color: #ffc107 !important;
    transform: scale(1.2);
}

.emoji-picker span {
    font-size: 20px;
    cursor: pointer;
    transition: all 0.2s;
    padding: 6px;
    border-radius: 6px;
    text-align: center;
}

.emoji-picker span:hover {
    background: #e8e8e8;
    transform: scale(1.2);
}

.review-card {
    transition: all 0.2s ease;
}

.review-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}
</style>

<script>
// ===================== RATING PICKER =====================
document.querySelectorAll('.rating-star').forEach(star => {
    star.addEventListener('click', function() {
        const rating = this.dataset.rating;
        document.getElementById('ratingInput').value = rating;
        
        document.querySelectorAll('.rating-star').forEach((s, index) => {
            s.classList.toggle('active', index + 1 <= rating);
            s.style.color = index + 1 <= rating ? '#ffc107' : '#ddd';
        });
    });
});

// ===================== EMOJI PICKER =====================
const emojis = ['😀', '😂', '❤️', '👍', '🙌', '😍', '😎', '🤔', '👌', '💯', '✨', '🎉'];
const selectedEmojis = [];

const emojiList = document.querySelector('.emoji-picker');
emojis.forEach(emoji => {
    const span = document.createElement('span');
    span.textContent = emoji;
    span.addEventListener('click', function() {
        if (selectedEmojis.includes(emoji)) {
            selectedEmojis.splice(selectedEmojis.indexOf(emoji), 1);
            span.style.background = 'transparent';
        } else {
            selectedEmojis.push(emoji);
            span.style.background = '#e8f5e9';
        }
        updateSelectedEmojis();
    });
    emojiList.appendChild(span);
});

function updateSelectedEmojis() {
    document.getElementById('selectedEmojis').innerHTML = selectedEmojis.map(e => 
        `<span class="badge bg-success" style="font-size: 14px; padding: 6px 10px; margin-right: 5px;">${e}</span>`
    ).join('');
}

// ===================== SPEECH TO TEXT =====================
const micBtn = document.getElementById('micBtn');

if (micBtn && ('webkitSpeechRecognition' in window || 'SpeechRecognition' in window)) {
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    const recognition = new SpeechRecognition();
    recognition.lang = 'fr-FR';
    recognition.continuous = false;
    recognition.interimResults = false;

    let isRecording = false;

    micBtn.addEventListener('click', () => {
        if (isRecording) {
            recognition.stop();
        } else {
            recognition.start();
        }
    });

    recognition.onstart = function() {
        isRecording = true;
        micBtn.classList.replace('btn-outline-primary', 'btn-danger');
        micBtn.innerHTML = '<i class="fas fa-stop"></i> Arrêter';
    };

    recognition.onresult = function(event) {
        const transcript = event.results[0][0].transcript;
        const reviewContent = document.getElementById('reviewContent');
        const currentVal = reviewContent.value;
        reviewContent.value = currentVal ? currentVal + ' ' + transcript : transcript;
        reviewContent.dispatchEvent(new Event('input')); // trigger char count & sentiment update
    };

    recognition.onerror = function(event) {
        console.error('Speech recognition error', event.error);
        alert('Erreur lors de la dictée vocale.');
        isRecording = false;
        micBtn.classList.replace('btn-danger', 'btn-outline-primary');
        micBtn.innerHTML = '<i class="fas fa-microphone"></i> Dicter';
    };

    recognition.onend = function() {
        isRecording = false;
        micBtn.classList.replace('btn-danger', 'btn-outline-primary');
        micBtn.innerHTML = '<i class="fas fa-microphone"></i> Dicter';
    };
} else if (micBtn) {
    micBtn.style.display = 'none';
}

// ===================== FILTRE INSULTES ET SENTIMENT =====================
const badWords = ['merde', 'putain', 'con', 'connard', 'salope', 'bâtard', 'idiot', 'stupide', 'abruti', 'salaud', 'foutre', 'chier', 'gueule'];
const positiveWords = ['super', 'génial', 'bien', 'bon', 'excellent', 'parfait', 'merci', 'recommande', 'satisfait', 'top', 'incroyable', 'bravo', 'agréable', 'gentil', 'professionnel', 'rapide', 'efficace', 'magnifique', 'formidable'];
const negativeWords = ['nul', 'mauvais', 'pire', 'déçu', 'horrible', 'lent', 'catastrophe', 'éviter', 'froid', 'incompétent', 'désagréable', 'cher', 'arnaque', 'décevant', 'médiocre', 'terrible', 'honteux', 'malade'];

function containsBadWords(text) {
    if (!text) return false;
    const lowerText = text.toLowerCase();
    return badWords.some(word => {
        const regex = new RegExp(`\\b${word}\\b`, 'i');
        return regex.test(lowerText);
    });
}

function analyzeSentiment(text) {
    if (!text) return { label: 'En attente...', class: 'bg-secondary' };
    let score = 0;
    const words = text.toLowerCase().match(/[a-zà-ÿ]+/g) || [];
    words.forEach(word => {
        if (positiveWords.includes(word)) score++;
        if (negativeWords.includes(word)) score--;
    });
    
    if (score > 0) return { label: 'Positif 😊', class: 'bg-success' };
    if (score < 0) return { label: 'Négatif 😞', class: 'bg-danger' };
    if (text.trim().length > 0) return { label: 'Neutre 😐', class: 'bg-secondary' };
    return { label: 'En attente...', class: 'bg-secondary' };
}

// ===================== CHARACTER COUNT & LIVE UPDATE =====================
document.getElementById('reviewContent')?.addEventListener('input', function() {
    document.getElementById('charCount').textContent = this.value.length + '/2000';
    
    const text = this.value;
    
    // Bad words highlight
    if (containsBadWords(text)) {
        this.style.border = '2px solid #dc3545';
        this.style.backgroundColor = '#fff8f8';
    } else {
        this.style.border = '';
        this.style.backgroundColor = '';
    }

    // Sentiment update
    const sentiment = analyzeSentiment(text);
    const sentimentDisplay = document.getElementById('sentimentDisplay');
    if (sentimentDisplay) {
        sentimentDisplay.textContent = 'Sentiment: ' + sentiment.label;
        sentimentDisplay.className = 'badge ' + sentiment.class;
    }
});

// ===================== FORM SUBMISSION =====================
document.getElementById('reviewForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();

    const title = document.getElementById('reviewTitle').value;
    const content = document.getElementById('reviewContent').value;

    if (containsBadWords(title) || containsBadWords(content)) {
        const errorDiv = document.getElementById('formErrors');
        errorDiv.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>Votre avis contient des mots inappropriés ou des insultes. Veuillez corriger avant de soumettre.';
        errorDiv.style.display = 'block';
        return;
    }

    const formData = {
        title: title,
        rating: parseInt(document.getElementById('ratingInput').value),
        content: content,
        emojis: selectedEmojis
    };

    try {
        const response = await fetch('api/reviews.php?action=store', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(formData)
        });

        const result = await response.json();
        const errorDiv = document.getElementById('formErrors');

        if (result.success) {
            alert(result.message);
            location.reload();
        } else {
            if (result.errors) {
                errorDiv.innerHTML = Object.values(result.errors).join('<br>');
            } else {
                errorDiv.innerHTML = result.message || 'Une erreur est survenue';
            }
            errorDiv.style.display = 'block';
        }
    } catch (error) {
        document.getElementById('formErrors').innerHTML = 'Erreur: ' + error.message;
        document.getElementById('formErrors').style.display = 'block';
    }
});

// Initialiser le rating à 5 étoiles
document.querySelectorAll('.rating-star').forEach((s, i) => {
    if (i < 5) {
        s.classList.add('active');
        s.style.color = '#ffc107';
    }
});
</script>

