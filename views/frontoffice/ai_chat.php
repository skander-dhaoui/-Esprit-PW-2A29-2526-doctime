<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🤖 IA Métiers & Crédits - DocTime</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 30px 20px;
        }

        .container-main {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header-section {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .assistant-intro {
            display: flex;
            align-items: flex-start;
            gap: 20px;
            margin-bottom: 25px;
        }

        .assistant-avatar {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 30px;
            flex-shrink: 0;
        }

        .assistant-text h2 {
            margin: 0 0 5px 0;
            font-size: 1.3rem;
            color: #333;
            font-weight: 700;
        }

        .assistant-text p {
            margin: 0;
            color: #666;
            font-size: 0.95rem;
            line-height: 1.5;
        }

        .tags-section {
            margin-bottom: 30px;
        }

        .section-title {
            font-size: 0.9rem;
            font-weight: 700;
            color: #999;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .section-title i {
            color: #667eea;
        }

        .tags-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
        }

        .tag {
            background: #f5f5f5;
            border: 1.5px solid #e0e0e0;
            border-radius: 20px;
            padding: 8px 14px;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s;
            color: #555;
        }

        .tag:hover {
            border-color: #667eea;
            background: #f9f9ff;
            color: #667eea;
        }

        .section-divider {
            height: 1px;
            background: #e0e0e0;
            margin: 30px 0;
        }

        .professions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 10px;
            margin-bottom: 20px;
        }

        .profession-tag {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
            border: 1px solid rgba(102, 126, 234, 0.3);
            border-radius: 8px;
            padding: 10px 12px;
            text-align: center;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.3s;
            color: #667eea;
        }

        .profession-tag:hover {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.2) 0%, rgba(118, 75, 162, 0.2) 100%);
            border-color: rgba(102, 126, 234, 0.6);
        }

        .professions-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 12px;
            margin-bottom: 20px;
        }

        .profession-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            background: #f9f9f9;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 0.9rem;
            color: #333;
        }

        .profession-item:hover {
            background: #f0f0f0;
            padding-left: 16px;
        }

        .profession-item i {
            color: #667eea;
        }

        .main-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            align-items: start;
        }

        .chat-section {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            height: 500px;
        }

        .chat-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            text-align: center;
        }

        .chat-header h3 {
            margin: 0;
            font-size: 1.2rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .messages {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            background: #f9f9f9;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .message {
            display: flex;
            gap: 10px;
            animation: fadeIn 0.3s ease-in;
        }

        .message.user {
            justify-content: flex-end;
        }

        .message-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            flex-shrink: 0;
        }

        .message.assistant .message-avatar {
            background: #667eea;
            color: white;
        }

        .message.user .message-avatar {
            background: #e0e0e0;
            color: #667eea;
        }

        .message-content {
            max-width: 75%;
            padding: 10px 14px;
            border-radius: 12px;
            font-size: 0.9rem;
            line-height: 1.5;
        }

        .message.assistant .message-content {
            background: white;
            border: 1px solid #e0e0e0;
            color: #333;
            border-radius: 4px 12px 12px 12px;
        }

        .message.user .message-content {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 12px 4px 12px 12px;
        }

        .chat-input-section {
            padding: 15px;
            background: white;
            border-top: 1px solid #e0e0e0;
            display: flex;
            gap: 10px;
        }

        .chat-input-section input {
            flex: 1;
            border: 1.5px solid #e0e0e0;
            border-radius: 20px;
            padding: 10px 15px;
            font-size: 0.9rem;
            transition: border-color 0.3s;
        }

        .chat-input-section input:focus {
            outline: none;
            border-color: #667eea;
        }

        .btn-send-chat {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 50%;
            width: 38px;
            height: 38px;
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.2s;
            flex-shrink: 0;
        }

        .btn-send-chat:hover {
            transform: scale(1.05);
        }

        .btn-send-chat:disabled {
            opacity: 0.6;
        }

        .side-section {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .info-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }

        .info-card h4 {
            font-size: 1rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .info-card i {
            color: #667eea;
        }

        .info-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .info-item {
            font-size: 0.9rem;
            color: #666;
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 6px 0;
        }

        .info-item i {
            color: #4CAF50;
            font-size: 0.8rem;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        ::-webkit-scrollbar {
            width: 6px;
        }

        ::-webkit-scrollbar-track {
            background: transparent;
        }

        ::-webkit-scrollbar-thumb {
            background: #ccc;
            border-radius: 3px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #999;
        }

        @media (max-width: 991px) {
            .main-content {
                grid-template-columns: 1fr;
            }

            .professions-grid {
                grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
            }

            .professions-list {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            }
        }

        .loading-dots {
            display: flex;
            gap: 4px;
        }

        .loading-dots span {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #667eea;
            animation: bounce 1.4s infinite;
        }

        .loading-dots span:nth-child(2) {
            animation-delay: 0.2s;
        }

        .loading-dots span:nth-child(3) {
            animation-delay: 0.4s;
        }

        @keyframes bounce {
            0%, 80%, 100% {
                opacity: 0.3;
            }
            40% {
                opacity: 1;
            }
        }
    </style>
</head>
<body>
    <div class="container-main">
        <!-- Header Section -->
        <div class="header-section">
            <div class="assistant-intro">
                <div class="assistant-avatar">
                    <i class="fas fa-robot"></i>
                </div>
                <div class="assistant-text">
                    <h2>🤖 Assistant IA — Claude Sonnet</h2>
                    <p>👋 Bonjour ! Je suis votre assistant IA spécialisé en <strong>métiers créatifs médicaux</strong>.<br>
                    Je connais votre plateforme DocTime et les événements enregistrés. Posez-moi des questions sur les métiers, compétences, ou opportunités de carrière dans l'événementiel médical en Tunisie !</p>
                </div>
            </div>

            <!-- Spécialités Tags -->
            <div class="tags-section">
                <div class="section-title">
                    <i class="fas fa-stethoscope"></i> SPÉCIALITÉS DANS LE RÉSEAU
                </div>
                <div class="tags-container" id="specialitesTags">
                    <span class="tag" onclick="sendMessage('Parlez-moi de cardiologie')">Cardiologie</span>
                    <span class="tag" onclick="sendMessage('Coordinateur événementiel')">Coordinateur</span>
                    <span class="tag" onclick="sendMessage('Quelles sont les compétences clés?')">Compétences</span>
                    <span class="tag" onclick="sendMessage('Comment travailler en Tunisie?')">en Tunisie</span>
                    <span class="tag" onclick="sendMessage('Comment se développer une carrière?')">Carrière</span>
                </div>
            </div>

            <!-- Santé Tags -->
            <div class="tags-section">
                <div class="section-title">
                    <i class="fas fa-heart"></i> DOMAINES DE SANTÉ
                </div>
                <div class="tags-container" id="sante-tags">
                    <span class="tag" onclick="sendMessage('Parlez-moi de santé')">Santé</span>
                    <span class="tag" onclick="sendMessage('Spécialités en dermatologie')">Dermatologie</span>
                    <span class="tag" onclick="sendMessage('Médecine générale')">Médecine générale</span>
                    <span class="tag" onclick="sendMessage('Cardiologie')">Cardiologie</span>
                    <span class="tag" onclick="sendMessage('Oncologie')">Oncologie</span>
                </div>
            </div>

            <div class="section-divider"></div>

            <!-- Professions des participants -->
            <div class="tags-section">
                <div class="section-title">
                    <i class="fas fa-users"></i> PROFESSIONS DES PARTICIPANTS
                </div>
                <div class="professions-grid">
                    <span class="profession-tag" onclick="sendMessage('Quel métier pour etudia?')">etudia</span>
                    <span class="profession-tag" onclick="sendMessage('Parlez de etudient')">etudient</span>
                    <span class="profession-tag" onclick="sendMessage('Interne')">Interne</span>
                    <span class="profession-tag" onclick="sendMessage('Medecin cardiologue')">Medecin cardiologue</span>
                    <span class="profession-tag" onclick="sendMessage('Dermatologue')">Dermatologue</span>
                </div>
            </div>

            <div class="section-divider"></div>

            <!-- Métiers à explorer -->
            <div class="tags-section">
                <div class="section-title">
                    <i class="fas fa-briefcase"></i> MÉTIERS À EXPLORER
                </div>
                <div class="professions-list">
                    <div class="profession-item">
                        <i class="fas fa-palette"></i>
                        <span onclick="sendMessage('Designer médical')">Designer médical</span>
                    </div>
                    <div class="profession-item">
                        <i class="fas fa-users-cog"></i>
                        <span onclick="sendMessage('Community manager santé')">Community manager santé</span>
                    </div>
                    <div class="profession-item">
                        <i class="fas fa-chart-line"></i>
                        <span onclick="sendMessage('Data analyst événementiel')">Data analyst événementiel</span>
                    </div>
                    <div class="profession-item">
                        <i class="fas fa-video"></i>
                        <span onclick="sendMessage('Vidéaste médical')">Vidéaste médical</span>
                    </div>
                    <div class="profession-item">
                        <i class="fas fa-microphone"></i>
                        <span onclick="sendMessage('Traducteur médical simultané')">Traducteur médical simultané</span>
                    </div>
                    <div class="profession-item">
                        <i class="fas fa-hands-helping"></i>
                        <span onclick="sendMessage('Fundraiser sponsor manager')">Fundraiser / sponsor manager</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content: Chat + Side Info -->
        <div class="main-content">
            <!-- Chat Section -->
            <div class="chat-section">
                <div class="chat-header">
                    <h3><i class="fas fa-comments"></i> Conversation</h3>
                </div>
                <div class="messages" id="messagesContainer">
                    <div class="message assistant">
                        <div class="message-avatar">🤖</div>
                        <div class="message-content">
                            Bonjour ! Comment puis-je vous aider avec les métiers et formations médicales ? 😊
                        </div>
                    </div>
                </div>
                <div class="chat-input-section">
                    <input 
                        type="text" 
                        id="messageInput" 
                        placeholder="Posez une question sur les métiers médicaux/crédits..."
                        onkeypress="if(event.key==='Enter') sendMessage()"
                    >
                    <button class="btn-send-chat" id="sendBtn" onclick="sendMessage()">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            </div>

            <!-- Side Info -->
            <div class="side-section">
                <div class="info-card">
                    <h4><i class="fas fa-star"></i> Avantages</h4>
                    <div class="info-list">
                        <div class="info-item">
                            <i class="fas fa-check"></i>
                            <span>Métiers médico-créatifs</span>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-check"></i>
                            <span>Formations continues</span>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-check"></i>
                            <span>Partenariats sponsors</span>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-check"></i>
                            <span>Opportunités en Tunisie</span>
                        </div>
                    </div>
                </div>

                <div class="info-card">
                    <h4><i class="fas fa-lightbulb"></i> Conseils</h4>
                    <div class="info-list">
                        <div class="info-item">
                            <i class="fas fa-check"></i>
                            <span>Développez vos compétences</span>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-check"></i>
                            <span>Réseau professionnel</span>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-check"></i>
                            <span>Certification médicale</span>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-check"></i>
                            <span>Expérience événementielle</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const messagesContainer = document.getElementById('messagesContainer');
        const messageInput = document.getElementById('messageInput');
        const sendBtn = document.getElementById('sendBtn');

        let conversationHistory = [];

        async function sendMessage(text = null) {
            const userMessage = text || messageInput.value.trim();
            
            if (!userMessage) return;

            messageInput.value = '';
            sendBtn.disabled = true;

            // Add user message
            addMessage(userMessage, 'user');

            // Add to history
            conversationHistory.push({ role: 'user', content: userMessage });

            // Show typing indicator
            const typingDiv = document.createElement('div');
            typingDiv.className = 'message assistant';
            typingDiv.innerHTML = `
                <div class="message-avatar">🤖</div>
                <div class="message-content">
                    <div class="loading-dots">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                </div>
            `;
            messagesContainer.appendChild(typingDiv);
            scrollToBottom();

            try {
                const response = await fetch('index.php?page=api_chat', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ messages: conversationHistory })
                });

                const data = await response.json();
                typingDiv.remove();

                if (data.error) {
                    addMessage('❌ ' + data.error, 'assistant');
                } else if (data.content && data.content[0]?.text) {
                    const assistantMessage = data.content[0].text;
                    addMessage(assistantMessage, 'assistant');
                    conversationHistory.push({ 
                        role: 'assistant', 
                        content: assistantMessage 
                    });
                }
            } catch (err) {
                typingDiv.remove();
                addMessage('❌ Erreur réseau: ' + err.message, 'assistant');
            } finally {
                sendBtn.disabled = false;
                messageInput.focus();
            }
        }

        function addMessage(text, role) {
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${role}`;
            
            const avatar = role === 'user' ? '👤' : '🤖';
            const escapedText = escapeHtml(text);
            const formattedText = formatMarkdown(escapedText);

            messageDiv.innerHTML = `
                <div class="message-avatar">${avatar}</div>
                <div class="message-content">${formattedText}</div>
            `;
            
            messagesContainer.appendChild(messageDiv);
            scrollToBottom();
        }

        function formatMarkdown(text) {
            text = text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
            text = text.replace(/__(.*?)__/g, '<strong>$1</strong>');
            text = text.replace(/\*(.*?)\*/g, '<em>$1</em>');
            text = text.replace(/_(.*?)_/g, '<em>$1</em>');
            text = text.replace(/\n/g, '<br>');
            return text;
        }

        function escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, char => map[char]);
        }

        function scrollToBottom() {
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }

        window.addEventListener('load', () => messageInput.focus());
    </script>
</body>
</html>
