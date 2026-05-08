<?php
if (!isset($pageTitle)) $pageTitle = 'IA Métiers Créatifs';
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: index.php?page=login');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> – DocTime</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/theme-mode.css">
    <style>
        .main-content { margin-left: 260px; padding-top: 80px; padding-bottom: 30px; }
        @media (max-width: 768px) { .main-content { margin-left: 0; } }

        /* ── AI Chat styles ── */
        .ai-wrapper { max-width: 1200px; margin: 0 auto; padding: 0 20px; }

        .header-section {
            background: white;
            border-radius: 15px;
            padding: 28px;
            margin-bottom: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,.07);
        }
        .assistant-intro { display: flex; align-items: flex-start; gap: 18px; margin-bottom: 22px; }
        .assistant-avatar {
            width: 56px; height: 56px;
            background: linear-gradient(135deg,#667eea,#764ba2);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            color: white; font-size: 26px; flex-shrink: 0;
        }
        .assistant-text h2 { margin: 0 0 4px; font-size: 1.2rem; color: #333; font-weight: 700; }
        .assistant-text p  { margin: 0; color: #666; font-size: .9rem; line-height: 1.55; }

        .section-title {
            font-size: .82rem; font-weight: 700; color: #999;
            text-transform: uppercase; letter-spacing: .5px;
            margin-bottom: 10px; display: flex; align-items: center; gap: 8px;
        }
        .section-title i { color: #667eea; }

        .tags-section { margin-bottom: 18px; }
        .tags-container { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 14px; }
        .tag {
            background: #f5f5f5; border: 1.5px solid #e0e0e0;
            border-radius: 20px; padding: 7px 13px;
            font-size: .85rem; cursor: pointer; transition: all .25s; color: #555;
        }
        .tag:hover { border-color: #667eea; background: #f9f9ff; color: #667eea; }

        .section-divider { height: 1px; background: #eee; margin: 18px 0; }

        .professions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(110px,1fr));
            gap: 8px; margin-bottom: 14px;
        }
        .profession-tag {
            background: linear-gradient(135deg,rgba(102,126,234,.1),rgba(118,75,162,.1));
            border: 1px solid rgba(102,126,234,.3);
            border-radius: 8px; padding: 9px 10px;
            text-align: center; font-size: .82rem;
            cursor: pointer; transition: all .25s; color: #667eea;
        }
        .profession-tag:hover {
            background: linear-gradient(135deg,rgba(102,126,234,.2),rgba(118,75,162,.2));
            border-color: rgba(102,126,234,.6);
        }

        .professions-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(190px,1fr));
            gap: 10px; margin-bottom: 14px;
        }
        .profession-item {
            display: flex; align-items: center; gap: 9px;
            padding: 9px 12px; background: #f9f9f9;
            border-radius: 8px; cursor: pointer;
            transition: all .25s; font-size: .88rem; color: #333;
        }
        .profession-item:hover { background: #f0f0f0; padding-left: 16px; }
        .profession-item i { color: #667eea; }

        /* ── Chat layout ── */
        .main-content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px; align-items: start;
        }
        @media(max-width:991px){ .main-content-grid{ grid-template-columns:1fr; } }

        .chat-section {
            background: white; border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,.08);
            overflow: hidden; display: flex; flex-direction: column;
            height: 500px;
        }
        .chat-header {
            background: linear-gradient(135deg,#667eea,#764ba2);
            color: white; padding: 18px 20px; text-align: center;
        }
        .chat-header h3 {
            margin: 0; font-size: 1.1rem; font-weight: 700;
            display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        .messages {
            flex: 1; overflow-y: auto; padding: 18px;
            background: #f9f9f9; display: flex; flex-direction: column; gap: 11px;
        }
        .message { display: flex; gap: 9px; animation: fadeIn .3s ease-in; }
        .message.user { justify-content: flex-end; }
        .message-avatar {
            width: 30px; height: 30px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 15px; flex-shrink: 0;
        }
        .message.assistant .message-avatar { background: #667eea; color: white; }
        .message.user      .message-avatar { background: #e0e0e0; color: #667eea; }
        .message-content {
            max-width: 75%; padding: 9px 13px;
            border-radius: 12px; font-size: .88rem; line-height: 1.5;
        }
        .message.assistant .message-content {
            background: white; border: 1px solid #e0e0e0; color: #333;
            border-radius: 4px 12px 12px 12px;
        }
        .message.user .message-content {
            background: linear-gradient(135deg,#667eea,#764ba2);
            color: white; border-radius: 12px 4px 12px 12px;
        }
        .chat-input-section {
            padding: 13px 15px; background: white;
            border-top: 1px solid #e0e0e0; display: flex; gap: 9px;
        }
        .chat-input-section input {
            flex: 1; border: 1.5px solid #e0e0e0; border-radius: 20px;
            padding: 9px 14px; font-size: .88rem; transition: border-color .3s;
        }
        .chat-input-section input:focus { outline: none; border-color: #667eea; }
        .btn-send-chat {
            background: linear-gradient(135deg,#667eea,#764ba2);
            border: none; border-radius: 50%;
            width: 36px; height: 36px; color: white;
            cursor: pointer; display: flex; align-items: center; justify-content: center;
            transition: transform .2s; flex-shrink: 0;
        }
        .btn-send-chat:hover { transform: scale(1.08); }
        .btn-send-chat:disabled { opacity: .6; }

        /* Side cards */
        .side-section { display: flex; flex-direction: column; gap: 18px; }
        .info-card {
            background: white; border-radius: 12px;
            padding: 18px; box-shadow: 0 4px 12px rgba(0,0,0,.07);
        }
        .info-card h4 {
            font-size: .95rem; font-weight: 700; color: #333;
            margin-bottom: 10px; display: flex; align-items: center; gap: 8px;
        }
        .info-card h4 i { color: #667eea; }
        .info-list { display: flex; flex-direction: column; gap: 7px; }
        .info-item {
            font-size: .88rem; color: #666;
            display: flex; align-items: center; gap: 8px; padding: 5px 0;
        }
        .info-item i { color: #4CAF50; font-size: .78rem; }

        /* Loading dots */
        .loading-dots { display: flex; gap: 4px; }
        .loading-dots span {
            width: 6px; height: 6px; border-radius: 50%;
            background: #667eea; animation: bounce 1.4s infinite;
        }
        .loading-dots span:nth-child(2) { animation-delay: .2s; }
        .loading-dots span:nth-child(3) { animation-delay: .4s; }

        @keyframes bounce { 0%,80%,100%{opacity:.3} 40%{opacity:1} }
        @keyframes fadeIn { from{opacity:0;transform:translateY(8px)} to{opacity:1;transform:translateY(0)} }

        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-thumb { background: #ccc; border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: #999; }
    </style>
</head>
<body>
<?php require __DIR__ . '/../sidebar.php'; ?>
<?php require __DIR__ . '/../layout_header.php'; ?>

<div class="main-content">
<div class="ai-wrapper">

    <!-- Header intro -->
    <div class="header-section">
        <div class="assistant-intro">
            <div class="assistant-avatar"><i class="fas fa-robot"></i></div>
            <div class="assistant-text">
                <h2>🤖 Assistant IA — Claude Sonnet</h2>
                <p>👋 Bonjour ! Je suis votre assistant IA spécialisé en <strong>métiers créatifs médicaux</strong>.<br>
                Je connais votre plateforme DocTime et les événements enregistrés. Posez-moi des questions sur les métiers, compétences, ou opportunités de carrière dans l'événementiel médical en Tunisie !</p>
            </div>
        </div>

        <!-- Spécialités -->
        <div class="tags-section">
            <div class="section-title"><i class="fas fa-stethoscope"></i> SPÉCIALITÉS DANS LE RÉSEAU</div>
            <div class="tags-container">
                <span class="tag" onclick="sendMessage('Parlez-moi de cardiologie')">Cardiologie</span>
                <span class="tag" onclick="sendMessage('Coordinateur événementiel')">Coordinateur</span>
                <span class="tag" onclick="sendMessage('Quelles sont les compétences clés?')">Compétences</span>
                <span class="tag" onclick="sendMessage('Comment travailler en Tunisie?')">en Tunisie</span>
                <span class="tag" onclick="sendMessage('Comment se développer une carrière?')">Carrière</span>
            </div>
        </div>

        <!-- Santé -->
        <div class="tags-section">
            <div class="section-title"><i class="fas fa-heart"></i> DOMAINES DE SANTÉ</div>
            <div class="tags-container">
                <span class="tag" onclick="sendMessage('Autre domaine de santé')">Autre</span>
                <span class="tag" onclick="sendMessage('Spécialités en dermatologie')">Dermatologie</span>
                <span class="tag" onclick="sendMessage('Médecine générale')">Médecine générale</span>
                <span class="tag" onclick="sendMessage('Cardiologie')">Cardiologie</span>
                <span class="tag" onclick="sendMessage('Oncologie')">Oncologie</span>
            </div>
        </div>

        <div class="section-divider"></div>

        <!-- Professions BDD -->
        <div class="tags-section">
            <div class="section-title"><i class="fas fa-users"></i> PROFESSIONS DES PARTICIPANTS</div>
            <div class="professions-grid">
                <?php if (!empty($professions)): ?>
                    <?php foreach ($professions as $p): ?>
                        <span class="profession-tag" onclick="sendMessage('Parlez du métier: <?= htmlspecialchars(addslashes($p['profession'] ?? '')) ?>')">
                            <?= htmlspecialchars($p['profession'] ?? 'N/A') ?>
                        </span>
                    <?php endforeach; ?>
                <?php else: ?>
                    <span class="profession-tag" onclick="sendMessage('Quel métier pour etudiant?')">etudiant</span>
                    <span class="profession-tag" onclick="sendMessage('Parlez de etudiant')">etudiant</span>
                    <span class="profession-tag" onclick="sendMessage('Interne')">Interne</span>
                    <span class="profession-tag" onclick="sendMessage('Medecin cardiologue')">Medecin cardiologue</span>
                    <span class="profession-tag" onclick="sendMessage('Dermatologue')">Dermatologue</span>
                <?php endif; ?>
            </div>
        </div>

        <div class="section-divider"></div>

        <!-- Métiers à explorer -->
        <div class="tags-section">
            <div class="section-title"><i class="fas fa-briefcase"></i> MÉTIERS À EXPLORER</div>
            <div class="professions-list">
                <div class="profession-item"><i class="fas fa-palette"></i><span onclick="sendMessage('Designer médical')">Designer médical — visuels &amp; stands</span></div>
                <div class="profession-item"><i class="fas fa-users-cog"></i><span onclick="sendMessage('Community manager santé')">Community manager santé</span></div>
                <div class="profession-item"><i class="fas fa-microphone"></i><span onclick="sendMessage('Modérateur de congrès')">Modérateur de congrès</span></div>
                <div class="profession-item"><i class="fas fa-chart-line"></i><span onclick="sendMessage('Data analyst événementiel')">Data analyst événementiel</span></div>
                <div class="profession-item"><i class="fas fa-video"></i><span onclick="sendMessage('Vidéaste médical')">Vidéaste médical</span></div>
                <div class="profession-item"><i class="fas fa-language"></i><span onclick="sendMessage('Traducteur médical simultané')">Traducteur médical simultané</span></div>
                <div class="profession-item"><i class="fas fa-hands-helping"></i><span onclick="sendMessage('Fundraiser sponsor manager')">Fundraiser / sponsor manager</span></div>
            </div>
        </div>
    </div>

    <!-- Chat + Side panel -->
    <div class="main-content-grid">

        <!-- Chat -->
        <div class="chat-section">
            <div class="chat-header">
                <h3><i class="fas fa-comments"></i> Conversation</h3>
            </div>
            <div class="messages" id="messagesContainer">
                <div class="message assistant">
                    <div class="message-avatar">🤖</div>
                    <div class="message-content">Bonjour ! Comment puis-je vous aider avec les métiers et formations médicales ? 😊</div>
                </div>
            </div>
            <div class="chat-input-section">
                <input type="text" id="messageInput"
                    placeholder="Posez une question sur les métiers médicaux/créatifs..."
                    onkeypress="if(event.key==='Enter') sendMessage()">
                <button class="btn-send-chat" id="sendBtn" onclick="sendMessage()">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </div>

        <!-- Side info -->
        <div class="side-section">
            <?php if (!empty($specialites)): ?>
            <div class="info-card">
                <h4><i class="fas fa-star"></i> Spécialités dans la BDD</h4>
                <div class="tags-container">
                    <?php foreach ($specialites as $s): ?>
                        <span class="tag" onclick="sendMessage('Spécialité: <?= htmlspecialchars(addslashes($s['specialite'] ?? '')) ?>')">
                            <?= htmlspecialchars($s['specialite'] ?? '') ?>
                            <sup style="color:#667eea;font-size:.7rem"><?= $s['total'] ?? '' ?></sup>
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($professions)): ?>
            <div class="info-card">
                <h4><i class="fas fa-users"></i> Professions des participants</h4>
                <div class="tags-container">
                    <?php foreach ($professions as $p): ?>
                        <span class="tag" onclick="sendMessage('Métier: <?= htmlspecialchars(addslashes($p['profession'] ?? '')) ?>')">
                            <?= htmlspecialchars($p['profession'] ?? '') ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="info-card">
                <h4><i class="fas fa-lightbulb"></i> Métiers à Explorer</h4>
                <div class="info-list">
                    <div class="info-item"><i class="fas fa-circle"></i><span onclick="sendMessage('Designer médical')" style="cursor:pointer"><strong>Designer médical</strong> — visuels &amp; stands</span></div>
                    <div class="info-item"><i class="fas fa-square"></i><span onclick="sendMessage('Community manager santé')" style="cursor:pointer"><strong>Community manager santé</strong></span></div>
                    <div class="info-item"><i class="fas fa-circle"></i><span onclick="sendMessage('Modérateur de congrès')" style="cursor:pointer"><strong>Modérateur de congrès</strong></span></div>
                    <div class="info-item"><i class="fas fa-table"></i><span onclick="sendMessage('Data analyst événementiel')" style="cursor:pointer"><strong>Data analyst événementiel</strong></span></div>
                    <div class="info-item"><i class="fas fa-video"></i><span onclick="sendMessage('Vidéaste médical')" style="cursor:pointer"><strong>Vidéaste médical</strong></span></div>
                    <div class="info-item"><i class="fas fa-circle" style="color:#f59e0b"></i><span onclick="sendMessage('Traducteur médical simultané')" style="cursor:pointer"><strong>Traducteur médical simultané</strong></span></div>
                    <div class="info-item"><i class="fas fa-box"></i><span onclick="sendMessage('Fundraiser sponsor manager')" style="cursor:pointer"><strong>Fundraiser / sponsor manager</strong></span></div>
                </div>
            </div>
        </div>

    </div>
</div><!-- /.ai-wrapper -->
</div><!-- /.main-content -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/theme-mode.js"></script>
<script>
    const messagesContainer = document.getElementById('messagesContainer');
    const messageInput      = document.getElementById('messageInput');
    const sendBtn           = document.getElementById('sendBtn');
    let conversationHistory = [];

    async function sendMessage(text = null) {
        const userMessage = text || messageInput.value.trim();
        if (!userMessage) return;
        messageInput.value = '';
        sendBtn.disabled = true;
        addMessage(userMessage, 'user');
        conversationHistory.push({ role: 'user', content: userMessage });

        const typingDiv = document.createElement('div');
        typingDiv.className = 'message assistant';
        typingDiv.innerHTML = `<div class="message-avatar">🤖</div>
            <div class="message-content"><div class="loading-dots"><span></span><span></span><span></span></div></div>`;
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
                const msg = data.content[0].text;
                addMessage(msg, 'assistant');
                conversationHistory.push({ role: 'assistant', content: msg });
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
        const div = document.createElement('div');
        div.className = `message ${role}`;
        const avatar = role === 'user' ? '👤' : '🤖';
        div.innerHTML = `<div class="message-avatar">${avatar}</div>
            <div class="message-content">${formatMarkdown(escapeHtml(text))}</div>`;
        messagesContainer.appendChild(div);
        scrollToBottom();
    }

    function formatMarkdown(t) {
        return t.replace(/\*\*(.*?)\*\*/g,'<strong>$1</strong>')
                .replace(/__(.*?)__/g,'<strong>$1</strong>')
                .replace(/\*(.*?)\*/g,'<em>$1</em>')
                .replace(/_(.*?)_/g,'<em>$1</em>')
                .replace(/\n/g,'<br>');
    }

    function escapeHtml(t) {
        return t.replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));
    }

    function scrollToBottom() { messagesContainer.scrollTop = messagesContainer.scrollHeight; }
    window.addEventListener('load', () => messageInput.focus());
</script>
</body>
</html>
