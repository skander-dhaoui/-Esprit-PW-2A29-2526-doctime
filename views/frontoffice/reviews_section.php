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
                            <p class="mb-0 text-muted small">
                                <strong><?= htmlspecialchars($review['prenom'] . ' ' . $review['nom']) ?></strong>
                                le <?= date('d/m/Y', strtotime($review['created_at'])) ?>
                            </p>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <?php 
                                $sentimentLabel = '';
                                $sentimentIcon = '';
                                $sentimentColor = '';
                                
                                if ($review['sentiment'] == 'positive') {
                                    $sentimentLabel = 'Positif';
                                    $sentimentIcon = '😊';
                                    $sentimentColor = '#28a745';
                                } elseif ($review['sentiment'] == 'negative') {
                                    $sentimentLabel = 'Négatif';
                                    $sentimentIcon = '😞';
                                    $sentimentColor = '#dc3545';
                                } else {
                                    $sentimentLabel = 'Neutre';
                                    $sentimentIcon = '😐';
                                    $sentimentColor = '#6c757d';
                                }
                            ?>
                            <span class="badge" style="background-color: <?= $sentimentColor ?>; font-size: 11px; padding: 5px 8px;">
                                <?= $sentimentIcon ?> <?= $sentimentLabel ?>
                            </span>
                            <div class="text-end">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="fas fa-star" style="color: <?= $i <= $review['rating'] ? '#ffc107' : '#ddd' ?>; font-size: 14px;"></i>
                                <?php endfor; ?>
                            </div>
                        </div>
                    </div>
                    <p class="mb-2" style="color: #333; line-height: 1.5; font-size: 14px;">
                        <?= nl2br(htmlspecialchars(substr($review['content'], 0, 150))) ?>...
                    </p>
                    <?php if (!empty($review['emojis'])): ?>
                    <div class="small mb-2">
                        <?php foreach ($review['emojis'] as $emoji): ?>
                            <span style="font-size: 18px; margin-right: 4px;"><?= $emoji ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Boutons d'action -->
                    <?php if (!empty($_SESSION['user_id']) && $_SESSION['user_id'] == $review['user_id']): ?>
                    <div class="mt-3 d-flex gap-2" style="border-top: 1px solid #e0e0e0; padding-top: 12px;">
                        <button class="btn btn-sm btn-outline-primary edit-review-btn" data-review-id="<?= $review['id'] ?>" title="Modifier l'avis" style="border-radius: 6px;">
                            <i class="fas fa-edit"></i> Modifier
                        </button>
                        <button class="btn btn-sm btn-outline-danger delete-review-btn" data-review-id="<?= $review['id'] ?>" title="Supprimer l'avis" style="border-radius: 6px;">
                            <i class="fas fa-trash"></i> Supprimer
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Modal de suppression personnalisée -->
    <div id="deleteConfirmModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
        <div style="background: white; border-radius: 12px; max-width: 400px; width: 90%; padding: 30px; box-shadow: 0 10px 40px rgba(0,0,0,0.15); animation: slideInUp 0.3s ease;">
            <div style="text-align: center; margin-bottom: 20px;">
                <i class="fas fa-exclamation-triangle" style="font-size: 48px; color: #dc3545; margin-bottom: 15px;"></i>
                <h4 style="margin: 0; color: #333; font-weight: 600;">Supprimer cet avis?</h4>
            </div>
            
            <p style="color: #666; text-align: center; margin-bottom: 20px; line-height: 1.5;">
                Êtes-vous sûr de vouloir supprimer cet avis? 
                <br><strong>Cette action est irréversible.</strong>
            </p>
            
            <div style="display: flex; gap: 10px;">
                <button id="deleteConfirmCancel" style="flex: 1; padding: 10px 15px; border: 1px solid #ddd; background: #f8f9fa; color: #333; border-radius: 6px; cursor: pointer; font-weight: 500; transition: all 0.2s;">
                    <i class="fas fa-times me-1"></i> Annuler
                </button>
                <button id="deleteConfirmOk" style="flex: 1; padding: 10px 15px; background: #dc3545; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 500; transition: all 0.2s;">
                    <i class="fas fa-trash me-1"></i> Supprimer
                </button>
            </div>
        </div>
    </div>
</div>

<style>
@keyframes slideInUp {
    from {
        transform: translateY(30px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

#deleteConfirmModal > div {
    display: flex;
    flex-direction: column;
}

#deleteConfirmCancel:hover {
    background: #e9ecef !important;
}

#deleteConfirmOk:hover {
    background: #c82333 !important;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
}

#deleteConfirmOk:active {
    transform: translateY(0);
}

/* ============ FORM STYLING ============ */
.review-form-card {
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    transition: all 0.3s ease;
}

.review-form-card:hover {
    box-shadow: 0 4px 16px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

.review-form-card .form-control,
.review-form-card .form-control:focus {
    border-color: #ddd;
    border-radius: 8px;
    padding: 10px 12px;
    font-size: 14px;
    transition: all 0.2s ease;
}

.review-form-card .form-control:focus {
    border-color: #4CAF50;
    box-shadow: 0 0 0 0.2rem rgba(76, 175, 80, 0.15);
}

.review-form-card textarea {
    resize: vertical;
    min-height: 120px;
    font-family: inherit;
}

/* ============ RATING STARS ============ */
.rating-picker {
    display: flex;
    gap: 4px;
    margin: 10px 0;
}

.rating-star {
    cursor: pointer;
    transition: all 0.15s ease;
    text-shadow: 0 1px 2px rgba(0,0,0,0.05);
    display: inline-block;
}

.rating-star:hover {
    color: #ffc107 !important;
    transform: scale(1.3);
    filter: drop-shadow(0 2px 4px rgba(255, 193, 7, 0.3));
}

.rating-star.active {
    color: #ffc107 !important;
}

/* ============ EMOJI PICKER ============ */
.emoji-picker {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(40px, 1fr));
    gap: 8px;
    padding: 15px;
    background: #fff;
    border-radius: 8px;
    border: 2px solid #f0f0f0;
    transition: all 0.2s ease;
}

.emoji-picker:hover {
    border-color: #4CAF50;
}

.emoji-picker span {
    font-size: 24px;
    cursor: pointer;
    transition: all 0.2s ease;
    padding: 8px;
    border-radius: 6px;
    text-align: center;
    user-select: none;
    display: flex;
    align-items: center;
    justify-content: center;
}

.emoji-picker span:hover {
    background: #f0f8f4;
    transform: scale(1.2);
}

.emoji-picker span.selected,
.emoji-picker span[style*="background: rgb(232, 245, 233)"] {
    background: #e8f5e9;
    border: 1px solid #4CAF50;
}

/* ============ BUTTONS ============ */
.review-form-card .btn {
    border-radius: 6px;
    padding: 10px 16px;
    font-weight: 500;
    transition: all 0.2s ease;
}

.review-form-card .btn-success {
    background: #4CAF50;
    border-color: #4CAF50;
}

.review-form-card .btn-success:hover {
    background: #45a049;
    border-color: #45a049;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(76, 175, 80, 0.3);
}

.review-form-card .btn-success:disabled {
    background: #cccccc;
    border-color: #cccccc;
}

.review-form-card .btn-outline-secondary {
    color: #999;
    border-color: #ddd;
}

.review-form-card .btn-outline-secondary:hover {
    background: #f0f0f0;
    border-color: #bbb;
    color: #666;
}

#micBtn {
    border-radius: 20px;
    font-size: 12px;
    padding: 4px 12px !important;
}

/* ============ LABELS & HELP TEXT ============ */
.review-form-card .form-label {
    color: #333;
    font-weight: 600;
    font-size: 13px;
    letter-spacing: 0.5px;
    text-transform: uppercase;
    margin-bottom: 8px;
}

.review-form-card small {
    font-size: 12px;
}

/* ============ BADGES & ALERTS ============ */
.badge {
    font-weight: 600;
    padding: 6px 10px;
    border-radius: 4px;
}

#sentimentDisplay {
    font-size: 12px;
    padding: 4px 8px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.alert-danger {
    background: #ffebee;
    border: none;
    color: #c62828;
    border-radius: 8px;
    padding: 12px 16px;
    font-size: 13px;
}

/* ============ REVIEWS LIST ============ */
.review-card {
    transition: all 0.3s ease;
    background: #f8f9fa;
    border-radius: 10px;
    border-left: 4px solid #4CAF50;
}

.review-card:hover {
    box-shadow: 0 4px 16px rgba(0,0,0,0.08);
    transform: translateX(4px);
}

.review-card h6 {
    color: #333;
    font-size: 15px;
    line-height: 1.4;
}

.review-card .text-muted {
    font-size: 12px;
    color: #999 !important;
}

/* ============ RESPONSIVE ============ */
@media (max-width: 768px) {
    .review-form-card {
        padding: 20px !important;
    }
    
    .emoji-picker {
        grid-template-columns: repeat(auto-fill, minmax(35px, 1fr));
    }
    
    .rating-picker {
        gap: 2px;
    }
    
    .rating-star {
        font-size: 20px !important;
    }
}

@media (max-width: 576px) {
    .review-form-card {
        padding: 15px !important;
        border-left: none;
        border-top: 4px solid #4CAF50;
    }
    
    .review-form-card h5 {
        font-size: 16px;
    }
    
    .review-form-card .btn {
        padding: 8px 12px;
        font-size: 13px;
    }
}

</style>

<script>
"use strict";

// =============== DONNÉES ===========
const emojis = ['😀', '😂', '❤️', '👍', '🙌', '😍', '😎', '🤔', '👌', '💯', '✨', '🎉'];
const badWords = ['merde', 'putain', 'con', 'connard', 'salope', 'bâtard', 'idiot', 'stupide', 'abruti', 'salaud', 'foutre', 'chier', 'gueule'];
const positiveWords = ['super', 'superbe', 'génial', 'bien', 'bon', 'excellent', 'parfait', 'merci', 'recommande', 'satisfait', 'top', 'incroyable', 'bravo', 'agréable', 'gentil', 'professionnel', 'rapide', 'efficace', 'magnifique', 'formidable', 'intuitive', 'intuitif', 'intéressant', 'sympathique'];
const negativeWords = ['nul', 'mauvais', 'pire', 'déçu', 'horrible', 'lent', 'catastrophe', 'éviter', 'froid', 'incompétent', 'désagréable', 'cher', 'arnaque', 'décevant', 'médiocre', 'terrible', 'honteux'];

let selectedEmojis = [];
let isSubmitting = false;

// =============== RATING PICKER ===========
document.querySelectorAll('.rating-star').forEach(star => {
    star.addEventListener('click', function() {
        const rating = parseInt(this.dataset.rating);
        document.getElementById('ratingInput').value = rating;
        
        document.querySelectorAll('.rating-star').forEach((s, i) => {
            if (i + 1 <= rating) {
                s.classList.add('active');
                s.style.color = '#ffc107';
            } else {
                s.classList.remove('active');
                s.style.color = '#ddd';
            }
        });
    });
    // Hover effect
    star.addEventListener('mouseover', function() {
        const rating = parseInt(this.dataset.rating);
        document.querySelectorAll('.rating-star').forEach((s, i) => {
            s.style.color = (i + 1 <= rating) ? '#ffc107' : '#ddd';
        });
    });
});

document.querySelector('#ratingPicker')?.addEventListener('mouseleave', function() {
    const rating = parseInt(document.getElementById('ratingInput').value);
    document.querySelectorAll('.rating-star').forEach((s, i) => {
        s.style.color = (i + 1 <= rating) ? '#ffc107' : '#ddd';
    });
});

// =============== EMOJI PICKER ===========
const emojiPicker = document.querySelector('.emoji-picker');
if (emojiPicker) {
    emojis.forEach(emoji => {
        const span = document.createElement('span');
        span.textContent = emoji;
        span.style.cursor = 'pointer';
        span.addEventListener('click', function() {
            if (selectedEmojis.includes(emoji)) {
                selectedEmojis = selectedEmojis.filter(e => e !== emoji);
                this.style.background = 'transparent';
            } else {
                selectedEmojis.push(emoji);
                this.style.background = '#e8f5e9';
            }
            updateSelectedEmojis();
        });
        emojiPicker.appendChild(span);
    });
}

function updateSelectedEmojis() {
    const container = document.getElementById('selectedEmojis');
    if (container) {
        container.innerHTML = selectedEmojis.map(e => 
            `<span class="badge bg-success" style="font-size: 14px; padding: 6px 10px; margin-right: 5px;">${e}</span>`
        ).join('');
    }
}

// =============== UTILITAIRES ===========
function levenshteinDistance(str1, str2) {
    const m = str1.length, n = str2.length;
    const dp = Array(n + 1).fill(0).map(() => Array(m + 1).fill(0));
    for (let i = 0; i <= m; i++) dp[0][i] = i;
    for (let j = 0; j <= n; j++) dp[j][0] = j;
    for (let i = 1; i <= n; i++) {
        for (let j = 1; j <= m; j++) {
            if (str1[j - 1] === str2[i - 1]) {
                dp[i][j] = dp[i - 1][j - 1];
            } else {
                dp[i][j] = 1 + Math.min(dp[i - 1][j], dp[i][j - 1], dp[i - 1][j - 1]);
            }
        }
    }
    return dp[n][m];
}

function containsBadWords(text) {
    if (!text) return false;
    const lower = text.toLowerCase();
    const words = lower.split(/[\s\.,!?;:\-0-9]+/).filter(w => w.length > 3);
    
    for (const badWord of badWords) {
        // 1. Détection exacte
        if (lower.includes(badWord)) return true;
        
        // 2. Distance Levenshtein (typos proches)
        for (const w of words) {
            const dist = levenshteinDistance(badWord, w);
            // putain vs putin, puttin → distance 1 ou 2
            if (dist <= 1 && w.length >= 4) return true;
        }
        
        // 3. Variantes avec substitutions (a→0, e→3, i→1, o→0)
        const pattern = badWord
            .replace(/a/g, '[a0@â]')
            .replace(/e/g, '[e3é]')
            .replace(/i/g, '[i1!|ï]')
            .replace(/o/g, '[o0ô]');
        const regex = new RegExp(pattern, 'gi');
        if (regex.test(lower)) return true;
    }
    
    return false;
}

function analyzeSentiment(text) {
    if (!text) return { label: 'En attente...', class: 'bg-secondary' };
    let score = 0;
    const words = text.toLowerCase().split(/[\s\.,!?;:-]+/);
    
    // Détecter les négations
    for (let i = 0; i < words.length; i++) {
        const word = words[i];
        const nextWord = i < words.length - 1 ? words[i + 1] : '';
        const isNegation = word === 'pas' || word === 'ne' || word === 'n' || word === "n'";
        
        if (isNegation && nextWord) {
            // Si le mot suivant est positif, inverser
            if (positiveWords.includes(nextWord)) {
                score -= 4;  // Pas bon = négatif
                i++;  // Sauter le mot suivant
            } else if (negativeWords.includes(nextWord)) {
                score += 2;  // Pas mauvais = positif
                i++;  // Sauter le mot suivant
            }
        } else if (!isNegation) {
            // Analyse normale seulement si pas une négation
            if (positiveWords.includes(word)) score += 2;
            if (negativeWords.includes(word)) score -= 2;
        }
    }
    
    if (score > 2) return { label: 'Positif 😊', class: 'bg-success' };
    if (score < -2) return { label: 'Négatif 😞', class: 'bg-danger' };
    return text.trim() ? { label: 'Neutre 😐', class: 'bg-secondary' } : { label: 'En attente...', class: 'bg-secondary' };
}

// =============== CHARACTER COUNT ===========
const contentField = document.getElementById('reviewContent');
if (contentField) {
    contentField.addEventListener('input', function() {
        // Counter
        const counter = document.getElementById('charCount');
        if (counter) counter.textContent = `${this.value.length}/2000`;
        
        // Bad words warning
        if (containsBadWords(this.value)) {
            this.style.border = '2px solid #dc3545';
            this.style.backgroundColor = '#fff8f8';
        } else {
            this.style.border = '';
            this.style.backgroundColor = '';
        }
        
        // Sentiment
        const sentiment = analyzeSentiment(this.value);
        const display = document.getElementById('sentimentDisplay');
        if (display) {
            display.textContent = 'Sentiment: ' + sentiment.label;
            display.className = 'badge ' + sentiment.class;
        }
    });
}

// =============== SPEECH TO TEXT ===========
const micBtn = document.getElementById('micBtn');
if (micBtn && (window.SpeechRecognition || window.webkitSpeechRecognition)) {
    const Recognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    const recognition = new Recognition();
    recognition.lang = 'fr-FR';
    
    let isRecording = false;
    
    micBtn.addEventListener('click', (e) => {
        e.preventDefault();
        if (isRecording) {
            recognition.stop();
        } else {
            recognition.start();
        }
    });
    
    recognition.onstart = () => {
        isRecording = true;
        micBtn.classList.add('btn-danger');
        micBtn.classList.remove('btn-outline-primary');
        micBtn.innerHTML = '<i class="fas fa-stop"></i> Arrêter';
    };
    
    recognition.onresult = (event) => {
        let transcript = '';
        for (let i = event.resultIndex; i < event.results.length; i++) {
            transcript += event.results[i][0].transcript;
        }
        const field = document.getElementById('reviewContent');
        field.value = (field.value ? field.value + ' ' : '') + transcript;
        field.dispatchEvent(new Event('input'));
    };
    
    recognition.onerror = (event) => {
        console.error('Speech error:', event.error);
        isRecording = false;
        micBtn.classList.remove('btn-danger');
        micBtn.classList.add('btn-outline-primary');
        micBtn.innerHTML = '<i class="fas fa-microphone"></i> Dicter';
    };
    
    recognition.onend = () => {
        isRecording = false;
        micBtn.classList.remove('btn-danger');
        micBtn.classList.add('btn-outline-primary');
        micBtn.innerHTML = '<i class="fas fa-microphone"></i> Dicter';
    };
}

// =============== FORM SUBMISSION ===========
const form = document.getElementById('reviewForm');
if (form) {
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        if (isSubmitting) return;
        isSubmitting = true;
        
        const btn = this.querySelector('button[type="submit"]');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Traitement...';
        btn.disabled = true;
        
        try {
            const content = document.getElementById('reviewContent').value.trim();
            const rating = parseInt(document.getElementById('ratingInput').value);
            
            // Validation
            if (!content || content.length < 10) {
                throw new Error('Contenu: minimum 10 caractères');
            }
            if (rating < 1 || rating > 5) {
                throw new Error('Sélectionnez une note');
            }
            
            const payload = {
                rating: rating,
                content: content,
                emojis: selectedEmojis
            };
            
            // Check if editing existing review
            const editId = btn.getAttribute('data-edit-id');
            const actionUrl = editId ? 'api/reviews.php?action=update' : 'api/reviews.php?action=store';
            if (editId) {
                payload.id = parseInt(editId);
            }
            
            const response = await fetch(actionUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            
            const data = await response.json();
            const errorDiv = document.getElementById('formErrors');
            
            if (data.success) {
                errorDiv.style.display = 'none';
                const isUpdate = btn.getAttribute('data-edit-id');
                const message = isUpdate ? 'Votre avis a été mis à jour avec succès.' : 'Votre avis a été publié avec succès.';
                
                // Affichage d'une modale de succès stylisée
                const successDiv = document.createElement('div');
                successDiv.style.cssText = 'position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 30px 40px; border-radius: 12px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); text-align: center; z-index: 10000; animation: slideInUp 0.3s ease; min-width: 320px;';
                successDiv.innerHTML = `
                    <i class="fas fa-check-circle" style="font-size: 50px; color: #4CAF50; margin-bottom: 15px; display: block; animation: scaleIn 0.5s ease;"></i>
                    <h4 style="color: #333; margin-bottom: 10px; font-weight: 600;">Super !</h4>
                    <p style="color: #666; margin: 0; font-size: 15px;">${message}</p>
                `;
                document.body.appendChild(successDiv);
                
                // Ajouter l'animation keyframes si elle n'existe pas
                if (!document.getElementById('scaleInKeyframes')) {
                    const style = document.createElement('style');
                    style.id = 'scaleInKeyframes';
                    style.innerHTML = '@keyframes scaleIn { 0% { transform: scale(0); } 70% { transform: scale(1.2); } 100% { transform: scale(1); } }';
                    document.head.appendChild(style);
                }
                
                // Reset form
                form.reset();
                btn.removeAttribute('data-edit-id');
                btn.textContent = 'Publier mon avis';
                
                // Reset rating stars
                document.querySelectorAll('.rating-star').forEach((s, i) => {
                    if (i < 5) {
                        s.classList.add('active');
                        s.style.color = '#ffc107';
                    }
                });
                
                setTimeout(() => location.reload(), 1500);
            } else {
                if (data.message && data.message.includes('⛔')) {
                    // Affichage personnalisé pour le blocage d'insultes
                    errorDiv.className = 'alert mt-3';
                    errorDiv.style.cssText = 'background: #fff8f8; border: 1px solid #f5c6cb; border-left: 5px solid #dc3545; border-radius: 8px; padding: 15px; display: block; box-shadow: 0 4px 12px rgba(220,53,69,0.1);';
                    errorDiv.innerHTML = `
                        <div class="d-flex align-items-center gap-3">
                            <i class="fas fa-hand-paper" style="font-size: 32px; color: #dc3545;"></i>
                            <div>
                                <h6 style="margin: 0 0 5px 0; color: #dc3545; font-weight: 700; text-transform: uppercase; font-size: 14px;">Modération Automatique</h6>
                                <p style="margin: 0; color: #333; font-size: 14px;">Votre avis n'a pas pu être publié car notre système a détecté un <strong>langage inapproprié</strong> ou offensant.</p>
                                <p style="margin: 3px 0 0 0; color: #666; font-size: 12px;"><i>Veuillez modifier votre texte pour rester courtois et respectueux.</i></p>
                            </div>
                        </div>
                    `;
                } else {
                    // Affichage générique pour les autres erreurs
                    errorDiv.className = 'alert alert-danger mt-3';
                    errorDiv.style.cssText = 'display: block;';
                    errorDiv.innerHTML = `<i class="fas fa-exclamation-triangle me-2"></i> <strong>Attention :</strong> ${data.message || 'Veuillez vérifier votre avis'}`;
                    if (data.errors) {
                        errorDiv.innerHTML += '<hr class="my-2"><ul class="mb-0" style="font-size:13px; padding-left:20px;">' + 
                            Object.values(data.errors).map(err => `<li>${err}</li>`).join('') + 
                            '</ul>';
                    }
                }
                window.scrollTo({ top: form.offsetTop - 100, behavior: 'smooth' });
            }
        } catch (error) {
            const errorDiv = document.getElementById('formErrors');
            errorDiv.innerHTML = `<strong>❌ Erreur:</strong> ${error.message}`;
            errorDiv.style.display = 'block';
        } finally {
            isSubmitting = false;
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    });
}

// =============== DELETE REVIEW ===========
let pendingDeleteId = null;

document.querySelectorAll('.delete-review-btn').forEach(btn => {
    btn.addEventListener('click', async function() {
        const reviewId = this.getAttribute('data-review-id');
        pendingDeleteId = reviewId;
        
        // Show modal
        const modal = document.getElementById('deleteConfirmModal');
        modal.style.display = 'flex';
        
        // Focus on delete button
        document.getElementById('deleteConfirmOk').focus();
    });
});

// Modal cancel button
document.getElementById('deleteConfirmCancel')?.addEventListener('click', function() {
    document.getElementById('deleteConfirmModal').style.display = 'none';
    pendingDeleteId = null;
});

// Modal confirm delete button
document.getElementById('deleteConfirmOk')?.addEventListener('click', async function() {
    if (!pendingDeleteId) return;
    
    const reviewId = pendingDeleteId;
    const modal = document.getElementById('deleteConfirmModal');
    const btn = document.getElementById('deleteConfirmOk');
    const originalText = btn.innerHTML;
    
    try {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Suppression...';
        
        const response = await fetch('/valorys_Copie/api/reviews.php?action=delete', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: reviewId })
        });
        
        const data = await response.json();
        
        if (data.success) {
            modal.style.display = 'none';
            // Show success animation
            const successDiv = document.createElement('div');
            successDiv.style.cssText = 'position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 30px; border-radius: 12px; box-shadow: 0 10px 40px rgba(0,0,0,0.15); text-align: center; z-index: 10000; animation: slideInUp 0.3s ease;';
            successDiv.innerHTML = '<i class="fas fa-check-circle" style="font-size: 48px; color: #28a745; margin-bottom: 15px; display: block;"></i><h4 style="color: #333; margin-bottom: 10px;">Avis supprimé!</h4><p style="color: #666; margin: 0;">Votre avis a été supprimé avec succès.</p>';
            document.body.appendChild(successDiv);
            
            setTimeout(() => location.reload(), 1500);
        } else {
            btn.disabled = false;
            btn.innerHTML = originalText;
            alert('❌ Erreur: ' + (data.message || 'Impossible de supprimer l\'avis'));
        }
    } catch (error) {
        btn.disabled = false;
        btn.innerHTML = originalText;
        alert('❌ Erreur: ' + error.message);
    }
});

// Close modal on escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        document.getElementById('deleteConfirmModal').style.display = 'none';
        pendingDeleteId = null;
    }
});

// =============== EDIT REVIEW ===========
document.querySelectorAll('.edit-review-btn').forEach(btn => {
    btn.addEventListener('click', async function() {
        const reviewId = this.getAttribute('data-review-id');
        
        try {
            const response = await fetch('/valorys_Copie/api/reviews.php?action=get&id=' + reviewId);
            const data = await response.json();
            
            if (data.success) {
                // Scroll to form
                const form = document.getElementById('reviewForm');
                form.scrollIntoView({ behavior: 'smooth' });
                
                // Pre-fill form
                setTimeout(() => {
                    document.getElementById('reviewContent').value = data.review.content;
                    document.getElementById('ratingInput').value = data.review.rating;
                    
                    // Update stars
                    document.querySelectorAll('.rating-star').forEach((star, idx) => {
                        if (idx < data.review.rating) {
                            star.classList.add('active');
                            star.style.color = '#ffc107';
                        } else {
                            star.classList.remove('active');
                            star.style.color = '#ddd';
                        }
                    });
                    
                    // Update form to indicate edit mode
                    const btn = form.querySelector('button[type="submit"]');
                    btn.setAttribute('data-edit-id', reviewId);
                    btn.textContent = 'Mettre à jour l\'avis';
                    
                    // Trigger input event to update sentiment
                    document.getElementById('reviewContent').dispatchEvent(new Event('input'));
                }, 300);
            } else {
                alert('❌ Erreur: ' + (data.message || 'Impossible de charger l\'avis'));
            }
        } catch (error) {
            alert('❌ Erreur: ' + error.message);
        }
    });
});

// =============== INIT ===========
// Initialize 5-star rating
document.querySelectorAll('.rating-star').forEach((s, i) => {
    if (i < 5) {
        s.classList.add('active');
        s.style.color = '#ffc107';
    }
});

console.log('✅ Review form initialized');
</script>

