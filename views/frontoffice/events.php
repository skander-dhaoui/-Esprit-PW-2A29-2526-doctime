<?php
$isLoggedIn = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Événements - Valorys</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f5f7fb; font-family: 'Segoe UI', sans-serif; }
        .navbar { background: #1a2035; }
        .navbar-brand, .nav-link { color: white !important; }
        .event-header {
            background: linear-gradient(135deg, #2A7FAA 0%, #4CAF50 100%);
            color: white; padding: 60px 0; text-align: center; margin-bottom: 40px;
        }
        .event-card {
            background: white; border-radius: 16px; padding: 0;
            margin-bottom: 25px; box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: transform 0.3s; overflow: hidden; position: relative;
        }
        .event-card:hover { transform: translateY(-5px); }
        .event-image { height: 180px; background-size: cover; background-position: center; }
        .event-body { padding: 20px; }
        .event-title { font-size: 1.15rem; font-weight: 700; margin-bottom: 8px; }
        .event-title a { color: #1a2035; text-decoration: none; }
        .event-title a:hover { color: #2A7FAA; }
        .event-date, .event-lieu { font-size: 13px; color: #6c757d; margin-bottom: 6px; }
        .event-date i, .event-lieu i { color: #2A7FAA; margin-right: 5px; }
        .event-excerpt { color: #555; font-size: 14px; margin-bottom: 15px; }
        .event-price { font-size: 16px; font-weight: bold; color: #2A7FAA; }
        .btn-register {
            background: linear-gradient(135deg, #2A7FAA, #4CAF50);
            color: white; border: none; border-radius: 25px;
            padding: 8px 20px; font-size: 13px; text-decoration: none; display: inline-block;
        }
        .btn-register:hover { opacity: 0.9; color: white; }
        /* Boutons CRUD positionnés en haut à droite de la carte */
        .admin-actions {
            position: absolute; top: 8px; right: 8px;
            z-index: 10; display: flex; gap: 4px;
        }
        .admin-actions .btn { padding: 4px 8px; font-size: 11px; border-radius: 6px; }
        .sidebar-card {
            background: white; border-radius: 16px; padding: 20px;
            margin-bottom: 20px; box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        .sidebar-card h5 {
            font-size: 16px; font-weight: 700; margin-bottom: 15px;
            border-left: 3px solid #4CAF50; padding-left: 10px;
        }
        footer { background: #1a2035; color: white; text-align: center; padding: 30px; margin-top: 50px; }
    </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container">
        <a class="navbar-brand" href="/valorys_Copie/index.php?page=accueil">
            <i class="fas fa-stethoscope me-2"></i>Valorys
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="/valorys_Copie/index.php?page=accueil">Accueil</a></li>
                <li class="nav-item"><a class="nav-link" href="/valorys_Copie/index.php?page=medecins">Médecins</a></li>
                <li class="nav-item"><a class="nav-link" href="/valorys_Copie/index.php?page=blog_public">Blog</a></li>
                <li class="nav-item"><a class="nav-link active" href="/valorys_Copie/index.php?page=evenements">Événements</a></li>
                <li class="nav-item"><a class="nav-link" href="/valorys_Copie/index.php?page=contact">Contact</a></li>
                <?php if ($isLoggedIn): ?>
                    <li class="nav-item"><a class="nav-link" href="/valorys_Copie/index.php?page=logout">Déconnexion</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="/valorys_Copie/index.php?page=login">Connexion</a></li>
                    <li class="nav-item"><a class="nav-link" href="/valorys_Copie/index.php?page=register">Inscription</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- EN-TÊTE -->
<div class="event-header">
    <div class="container">
        <h1><i class="fas fa-calendar-alt me-3"></i>Événements Valorys</h1>
        <p class="lead">Conférences, ateliers et rencontres médicales</p>
        <?php if ($isLoggedIn): ?>
            <!-- Bouton créer → route FRONT -->
            <a href="/valorys_Copie/index.php?page=evenement_create" class="btn btn-light mt-3">
                <i class="fas fa-plus me-2"></i>Créer un événement
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- FLASH MESSAGE -->
<div class="container">
    <?php if (isset($_SESSION['flash'])): ?>
        <?php $f = $_SESSION['flash']; unset($_SESSION['flash']); ?>
        <?php $bc = ['success'=>'success','error'=>'danger','warning'=>'warning','info'=>'info'][$f['type']] ?? 'secondary'; ?>
        <div class="alert alert-<?= $bc ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($f['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
</div>

<div class="container mb-5">
    <div class="row">

        <!-- COLONNE PRINCIPALE -->
        <div class="col-lg-8">

            <!-- Événements à venir -->
            <h3 class="mb-4"><i class="fas fa-clock me-2"></i>À venir</h3>

            <?php if (empty($upcomingEvents)): ?>
                <div class="alert alert-info">Aucun événement à venir pour le moment.</div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($upcomingEvents as $event): ?>
                    <div class="col-md-6">
                        <div class="event-card">

                            <!-- Boutons CRUD → routes FRONT (plus admin_evenements) -->
                            <?php if ($isLoggedIn): ?>
                            <div class="admin-actions">
                                <a href="/valorys_Copie/index.php?page=evenement_edit&id=<?= $event['id'] ?>"
                                   class="btn btn-warning btn-sm" title="Modifier">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="/valorys_Copie/index.php?page=evenement_delete&id=<?= $event['id'] ?>"
                                   class="btn btn-danger btn-sm" title="Supprimer"
                                   onclick="return confirm('Supprimer cet événement ?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                            <?php endif; ?>

                            <div class="event-image"
                                 style="background-image: url('<?= htmlspecialchars($event['image'] ?? 'https://via.placeholder.com/400x180?text=Event') ?>'); background-color: #e9ecef;"></div>

                            <div class="event-body">
                                <h3 class="event-title">
                                    <a href="/valorys_Copie/index.php?page=detail_evenement&slug=<?= $event['slug'] ?>">
                                        <?= htmlspecialchars($event['titre']) ?>
                                    </a>
                                </h3>
                                <div class="event-date">
                                    <i class="fas fa-calendar-alt"></i>
                                    <?= date('d/m/Y H:i', strtotime($event['date_debut'])) ?>
                                </div>
                                <div class="event-lieu">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <?= htmlspecialchars($event['lieu'] ?? 'En ligne') ?>
                                </div>
                                <div class="event-excerpt">
                                    <?= htmlspecialchars(substr($event['description'] ?? $event['contenu'] ?? '', 0, 100)) ?>...
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="event-price">
                                        <?= $event['prix'] > 0 ? $event['prix'] . ' €' : 'Gratuit' ?>
                                    </span>
                                    <a href="/valorys_Copie/index.php?page=detail_evenement&slug=<?= $event['slug'] ?>"
                                       class="btn-register">Voir détails</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Événements passés -->
            <?php if (!empty($pastEvents)): ?>
            <h3 class="mb-4 mt-5"><i class="fas fa-history me-2"></i>Passés</h3>
            <div class="row">
                <?php foreach ($pastEvents as $event): ?>
                <div class="col-md-6">
                    <div class="event-card opacity-75">

                        <?php if ($isLoggedIn): ?>
                        <div class="admin-actions">
                            <a href="/valorys_Copie/index.php?page=evenement_edit&id=<?= $event['id'] ?>"
                               class="btn btn-warning btn-sm" title="Modifier">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="/valorys_Copie/index.php?page=evenement_delete&id=<?= $event['id'] ?>"
                               class="btn btn-danger btn-sm" title="Supprimer"
                               onclick="return confirm('Supprimer cet événement ?')">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                        <?php endif; ?>

                        <div class="event-image"
                             style="background-image: url('<?= htmlspecialchars($event['image'] ?? 'https://via.placeholder.com/400x180?text=Event') ?>'); background-color: #e9ecef; opacity: 0.7;"></div>

                        <div class="event-body">
                            <h3 class="event-title"><?= htmlspecialchars($event['titre']) ?></h3>
                            <div class="event-date">
                                <i class="fas fa-calendar-alt"></i>
                                <?= date('d/m/Y H:i', strtotime($event['date_debut'])) ?>
                            </div>
                            <div class="event-lieu">
                                <i class="fas fa-map-marker-alt"></i>
                                <?= htmlspecialchars($event['lieu'] ?? 'En ligne') ?>
                            </div>
                            <div class="event-excerpt">
                                <?= htmlspecialchars(substr($event['description'] ?? $event['contenu'] ?? '', 0, 100)) ?>...
                            </div>
                            <a href="/valorys_Copie/index.php?page=detail_evenement&slug=<?= $event['slug'] ?>"
                               class="btn btn-outline-secondary btn-sm">Voir détails</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

        </div>

        <!-- SIDEBAR -->
        <div class="col-lg-4">
            <div class="sidebar-card">
                <h5><i class="fas fa-info-circle me-2"></i>À propos</h5>
                <p>Participez à nos événements médicaux : conférences, ateliers et formations continues.</p>
            </div>

            <?php if ($isLoggedIn): ?>
            <div class="sidebar-card">
                <h5><i class="fas fa-ticket-alt me-2"></i>Mes inscriptions</h5>
                <a href="/valorys_Copie/index.php?page=mes_inscriptions" class="btn btn-primary w-100">
                    Voir mes événements
                </a>
            </div>
            <div class="sidebar-card">
                <h5><i class="fas fa-plus-circle me-2"></i>Organiser</h5>
                <a href="/valorys_Copie/index.php?page=evenement_create" class="btn btn-success w-100">
                    <i class="fas fa-plus me-1"></i>Créer un événement
                </a>
            </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<footer>
    <div class="container">
        <p>&copy; 2024 Valorys - Tous droits réservés</p>
        <small>Plateforme médicale en ligne</small>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- CHATBOT FLOTTANT -->
<style>
    .chatbot-widget {
        position: fixed;
        bottom: 20px;
        right: 20px;
        width: 350px;
        max-height: 500px;
        background: white;
        border-radius: 12px;
        box-shadow: 0 5px 40px rgba(0,0,0,0.16);
        display: flex;
        flex-direction: column;
        font-family: 'Segoe UI', sans-serif;
        z-index: 9999;
        transition: all 0.3s ease;
    }

    .chatbot-widget.closed {
        max-height: 60px;
        width: 60px;
    }

    .chatbot-header {
        background: linear-gradient(135deg, #2A7FAA 0%, #4CAF50 100%);
        color: white;
        padding: 15px 20px;
        border-radius: 12px 12px 0 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        cursor: pointer;
    }

    .chatbot-header h3 {
        margin: 0;
        font-size: 16px;
        font-weight: 600;
    }

    .chatbot-close {
        background: none;
        border: none;
        color: white;
        font-size: 20px;
        cursor: pointer;
    }

    .chatbot-messages {
        flex: 1;
        overflow-y: auto;
        padding: 15px;
        display: flex;
        flex-direction: column;
        gap: 10px;
        max-height: 370px;
    }

    .chatbot-message {
        padding: 10px 12px;
        border-radius: 8px;
        font-size: 13px;
        line-height: 1.4;
    }

    .chatbot-message.bot {
        background: #f0f0f0;
        color: #333;
    }

    .chatbot-message.user {
        background: #2A7FAA;
        color: white;
        margin-left: auto;
        max-width: 80%;
    }

    .chatbot-input-area {
        padding: 12px;
        border-top: 1px solid #eee;
        display: flex;
        gap: 8px;
    }

    .chatbot-input {
        flex: 1;
        border: 1px solid #ddd;
        border-radius: 6px;
        padding: 8px 12px;
        font-size: 13px;
        outline: none;
    }

    .chatbot-input:focus {
        border-color: #2A7FAA;
        box-shadow: 0 0 0 3px rgba(42, 127, 170, 0.1);
    }

    .chatbot-send {
        background: #2A7FAA;
        color: white;
        border: none;
        border-radius: 6px;
        padding: 8px 15px;
        cursor: pointer;
        font-size: 14px;
        font-weight: 600;
    }

    .chatbot-send:hover {
        background: #1e5f80;
    }

    .chatbot-widget.closed .chatbot-messages,
    .chatbot-widget.closed .chatbot-input-area {
        display: none;
    }

    .chatbot-fab {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: linear-gradient(135deg, #2A7FAA 0%, #4CAF50 100%);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        cursor: pointer;
    }
</style>

<div class="chatbot-widget closed" id="chatbotWidget">
    <div class="chatbot-header" onclick="toggleChatbot()">
        <h3>💬 Assistant Événements</h3>
        <button class="chatbot-close" onclick="closeChatbot()">&times;</button>
    </div>
    <div class="chatbot-messages" id="chatbotMessages">
        <div class="chatbot-message bot">
            Bonjour! 👋 Je suis votre assistant pour les événements médicaux. Posez-moi vos questions !
        </div>
    </div>
    <div class="chatbot-input-area">
        <input type="text" class="chatbot-input" id="chatbotInput" placeholder="Votre question..." onkeypress="sendMessage(event)">
        <button class="chatbot-send" onclick="sendMessage()">Envoyer</button>
    </div>
</div>

<script>
const chatbotWidget = document.getElementById('chatbotWidget');
const chatbotMessages = document.getElementById('chatbotMessages');
const chatbotInput = document.getElementById('chatbotInput');

const responses = {
    'prix|coût|tarif': '💰 Nos événements coûtent entre 25 DT et 200 DT selon le type. Consultez les détails de chaque événement pour le prix exact.',
    'lieu|adresse|où': '📍 Nous organisons des événements à Hôpital Européen, Espace Santé, Centre de Conférences International et Palais des Congrès.',
    'date|quand|horaire': '📅 Nos événements se tiennent tout au long de l\'année 2026. Consultez la liste ci-dessus pour les dates précises.',
    'inscr|reservation|particip': '✅ Cliquez sur "Plus de détails" pour accéder au formulaire d\'inscription. Vous pouvez vous inscrire directement en ligne.',
    'cardio|cardiologie': '🫀 Notre conférence sur la cardiologie est prévue le 15/06/2026 à l\'Hôpital Européen. Tarif: 150 DT.',
    'stress|bien-être': '🧘 L\'atelier bien-être et gestion du stress aura lieu le 10/07/2026 à l\'Espace Santé. Tarif: 25 DT.',
    'pédiatr|pediatrie': '👶 Le congrès international de pédiatrie se tiendra du 12 au 14/11/2026 au Palais des Congrès. Tarif: 200 DT.',
    'formation|medecin': '👨‍⚕️ La formation continue pour médecins généralistes aura lieu du 20 au 22/09/2026. Tarif: 150 DT.',
    'sponsor|partenaire': '🤝 Nos sponsors platine soutiennent nos événements. Consultez la page des sponsors pour en savoir plus.',
    'contact|information': '📞 Vous pouvez nous contacter via la page "Contact" ou directement lors d\'un événement.',
    'default': '😊 Je ne suis pas sûr de votre question. Essayez des termes comme: prix, date, lieu, inscription, cardiologie, pédiatrie, bien-être, etc.'
};

function toggleChatbot() {
    chatbotWidget.classList.toggle('closed');
    if (!chatbotWidget.classList.contains('closed')) {
        chatbotInput.focus();
    }
}

function closeChatbot() {
    chatbotWidget.classList.add('closed');
}

function sendMessage(event) {
    if (event && event.key !== 'Enter') return;
    if (event) event.preventDefault();

    const message = chatbotInput.value.trim();
    if (!message) return;

    // Ajouter le message utilisateur
    addMessage(message, 'user');
    chatbotInput.value = '';

    // Générer une réponse
    setTimeout(() => {
        const response = getResponse(message);
        addMessage(response, 'bot');
    }, 500);
}

function addMessage(text, sender) {
    const messageDiv = document.createElement('div');
    messageDiv.className = `chatbot-message ${sender}`;
    messageDiv.textContent = text;
    chatbotMessages.appendChild(messageDiv);
    chatbotMessages.scrollTop = chatbotMessages.scrollHeight;
}

function getResponse(userMessage) {
    const lower = userMessage.toLowerCase();
    
    for (const [keyword, response] of Object.entries(responses)) {
        const keywords = keyword.split('|');
        for (const kw of keywords) {
            if (lower.includes(kw)) {
                return response;
            }
        }
    }
    
    return responses['default'];
}

// Afficher le chatbot au chargement
document.addEventListener('DOMContentLoaded', () => {
    // Le chatbot est fermé par défaut, cliquer sur le header pour l'ouvrir
    chatbotWidget.classList.add('closed');
});
</script>

</body>
</html>

