<?php $pageTitle = 'Assistant IA – Métiers Créatifs'; ?>
<?php require __DIR__ . '/../layout_header.php'; ?>

<style>
  /* ── Variables IA ── */
  :root {
    --ai-purple:  #7c3aed;
    --ai-violet:  #a855f7;
    --ai-teal:    #1db88e;
    --ai-blue:    #1a7fa8;
    --ai-gold:    #f59e0b;
  }

  /* Gradient header */
  .ai-header {
    background: linear-gradient(135deg, #1a7fa8 0%, #7c3aed 50%, #1db88e 100%);
    border-radius: 16px;
    padding: 2rem 2.5rem;
    color: #fff;
    position: relative;
    overflow: hidden;
    margin-bottom: 2rem;
  }
  .ai-header::before {
    content: '';
    position: absolute;
    top: -40px; right: -40px;
    width: 200px; height: 200px;
    border-radius: 50%;
    background: rgba(255,255,255,.08);
  }
  .ai-header h4 { font-weight: 800; font-size: 1.4rem; }
  .ai-header p  { opacity: .85; margin: 0; font-size: .9rem; }

  /* Chat zone */
  #chatBox {
    height: 420px;
    overflow-y: auto;
    padding: 1.2rem;
    background: #f8fafc;
    border-radius: 12px;
    border: 1.5px solid #e2e8f0;
    scroll-behavior: smooth;
  }

  .msg { display: flex; gap: .75rem; margin-bottom: 1rem; animation: fadeIn .3s ease; }
  .msg.user { flex-direction: row-reverse; }

  .msg-avatar {
    width: 36px; height: 36px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: .9rem; flex-shrink: 0;
  }
  .msg.ai   .msg-avatar { background: linear-gradient(135deg, var(--ai-blue), var(--ai-purple)); color: #fff; }
  .msg.user .msg-avatar { background: linear-gradient(135deg, var(--ai-teal), var(--ai-blue));  color: #fff; }

  .msg-bubble {
    max-width: 78%;
    padding: .75rem 1rem;
    border-radius: 14px;
    font-size: .87rem;
    line-height: 1.55;
  }
  .msg.ai   .msg-bubble { background: #fff; border: 1px solid #e2e8f0; color: #1e293b; border-bottom-left-radius: 4px; }
  .msg.user .msg-bubble { background: linear-gradient(135deg, var(--ai-blue), var(--ai-teal)); color: #fff; border-bottom-right-radius: 4px; }

  /* Typing indicator */
  .typing-indicator span {
    width: 7px; height: 7px; border-radius: 50%;
    background: var(--ai-purple);
    display: inline-block; margin: 0 2px;
    animation: bounce .9s infinite;
  }
  .typing-indicator span:nth-child(2) { animation-delay: .2s; }
  .typing-indicator span:nth-child(3) { animation-delay: .4s; }
  @keyframes bounce { 0%,60%,100%{transform:translateY(0)} 30%{transform:translateY(-8px)} }
  @keyframes fadeIn { from{opacity:0;transform:translateY(8px)} to{opacity:1;transform:none} }

  /* Suggestions rapides */
  .quick-btn {
    border-radius: 20px; font-size: .78rem; padding: 5px 14px;
    border: 1.5px solid var(--ai-purple); color: var(--ai-purple);
    background: #fff; cursor: pointer; transition: all .2s;
    white-space: nowrap;
  }
  .quick-btn:hover { background: var(--ai-purple); color: #fff; }

  /* Input zone */
  #userInput {
    border-radius: 12px; border: 1.5px solid #d1d5db; resize: none;
    font-size: .87rem; padding: .75rem 1rem;
    transition: border-color .2s, box-shadow .2s;
  }
  #userInput:focus { border-color: var(--ai-purple); box-shadow: 0 0 0 3px rgba(124,58,237,.12); outline: none; }

  #sendBtn {
    border-radius: 12px; padding: .75rem 1.5rem;
    background: linear-gradient(135deg, var(--ai-blue), var(--ai-purple));
    border: none; color: #fff; font-weight: 600;
    transition: opacity .2s, transform .1s;
  }
  #sendBtn:hover:not(:disabled) { opacity: .9; transform: translateY(-1px); }
  #sendBtn:disabled { opacity: .6; cursor: not-allowed; }

  /* Statistiques contextuelles */
  .context-card {
    background: #fff; border-radius: 10px; padding: .9rem 1.1rem;
    border: 1px solid #e2e8f0; font-size: .82rem;
    transition: box-shadow .2s;
  }
  .context-card:hover { box-shadow: 0 4px 16px rgba(124,58,237,.1); }
  .context-card h6 { font-size: .75rem; text-transform: uppercase; letter-spacing: .06em; color: #9ca3af; font-weight: 700; margin-bottom: .5rem; }

  .spec-badge {
    background: linear-gradient(135deg, #ede9fe, #ddd6fe);
    color: var(--ai-purple); border-radius: 20px;
    padding: 3px 10px; font-size: .72rem; font-weight: 600;
    display: inline-block; margin: 2px;
  }
  .prof-pill {
    background: #f0fdf4; color: #166534;
    border-radius: 20px; padding: 3px 10px;
    font-size: .72rem; font-weight: 600;
    display: inline-block; margin: 2px;
  }

  /* Carte métier IA générée */
  .metier-card {
    background: linear-gradient(135deg, #faf5ff, #ede9fe);
    border-radius: 12px; padding: 1rem 1.2rem;
    border: 1px solid #ddd6fe;
    animation: fadeIn .4s ease;
    margin-top: .5rem;
  }
  .metier-card h5 { color: var(--ai-purple); font-weight: 700; font-size: .95rem; }
  .metier-card ul { padding-left: 1.2rem; font-size: .83rem; color: #374151; }
  .metier-card .skill-tag {
    background: var(--ai-purple); color: #fff;
    border-radius: 4px; padding: 2px 8px; font-size: .7rem;
    display: inline-block; margin: 2px;
  }
</style>

<!-- ── Header ── -->
<div class="ai-header">
  <div class="d-flex align-items-center gap-3 mb-2">
    <div style="font-size:2rem">🤖</div>
    <div>
      <h4 class="mb-0">Assistant IA – Métiers Créatifs & Médicaux</h4>
      <p>Explorez les métiers liés aux événements · Obtenez des recommandations personnalisées par l'IA</p>
    </div>
  </div>
  <a href="?controller=map&action=carte" class="btn btn-sm btn-light rounded-pill mt-1">
    <i class="bi bi-map me-1"></i>Voir la Carte
  </a>
</div>

<div class="row g-4">

  <!-- ── Chat IA ── -->
  <div class="col-lg-8">
    <div class="card h-100">
      <div class="card-header py-3 d-flex align-items-center gap-2">
        <div style="width:10px;height:10px;border-radius:50%;background:#10b981;animation:bounce .9s infinite"></div>
        <h6 class="mb-0 fw-bold">Assistant IA <span class="text-muted fw-normal" style="font-size:.78rem">— Claude Sonnet</span></h6>
        <button class="btn btn-sm btn-light ms-auto rounded-pill" onclick="clearChat()">
          <i class="bi bi-trash me-1"></i>Effacer
        </button>
      </div>
      <div class="card-body p-3">

        <!-- Zone messages -->
        <div id="chatBox"></div>

        <!-- Suggestions rapides -->
        <div id="quickSuggestions" class="d-flex gap-2 flex-wrap mt-3 mb-3">
          <button class="quick-btn" onclick="quickAsk('Quels métiers créatifs sont liés à la cardiologie ?')">🫀 Cardiologie</button>
          <button class="quick-btn" onclick="quickAsk('Décris le métier de coordinateur événementiel médical')">📋 Coordinateur</button>
          <button class="quick-btn" onclick="quickAsk('Quelles compétences faut-il pour organiser un congrès médical ?')">🎓 Compétences</button>
          <button class="quick-btn" onclick="quickAsk('Quels sont les métiers émergents dans l\'événementiel médical en Tunisie ?')">🇹🇳 Tunisie</button>
          <button class="quick-btn" onclick="quickAsk('Donne-moi un plan de carrière pour devenir expert en événements médicaux')">📈 Carrière</button>
        </div>

        <!-- Input -->
        <div class="d-flex gap-2 align-items-end">
          <textarea id="userInput" class="form-control" rows="2"
            placeholder="Posez une question sur les métiers médicaux/créatifs…"
            onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();sendMessage();}"></textarea>
          <button id="sendBtn" onclick="sendMessage()">
            <i class="bi bi-send-fill me-1"></i>Envoyer
          </button>
        </div>

      </div>
    </div>
  </div>

  <!-- ── Contexte BDD ── -->
  <div class="col-lg-4">
    <div class="d-flex flex-column gap-3 h-100">

      <!-- Spécialités -->
      <div class="context-card">
        <h6><i class="bi bi-tag-fill me-1 text-purple" style="color:var(--ai-purple)"></i>Spécialités dans la BDD</h6>
        <div>
          <?php foreach($specialites as $s): ?>
          <span class="spec-badge"><?= htmlspecialchars($s['specialite']) ?> <sup><?= $s['total'] ?></sup></span>
          <?php endforeach; ?>
          <?php if(empty($specialites)): ?>
          <span class="text-muted" style="font-size:.78rem">Aucune spécialité enregistrée</span>
          <?php endif; ?>
        </div>
      </div>

      <!-- Professions participants -->
      <div class="context-card">
        <h6><i class="bi bi-person-badge-fill me-1" style="color:var(--ai-teal)"></i>Professions des Participants</h6>
        <div>
          <?php foreach(array_slice($professions,0,12) as $p): ?>
          <span class="prof-pill"><?= htmlspecialchars($p['profession']) ?></span>
          <?php endforeach; ?>
          <?php if(empty($professions)): ?>
          <span class="text-muted" style="font-size:.78rem">Aucune profession enregistrée</span>
          <?php endif; ?>
        </div>
      </div>

      <!-- Idées rapides -->
      <div class="context-card flex-grow-1">
        <h6><i class="bi bi-lightbulb-fill me-1" style="color:var(--ai-gold)"></i>Métiers à Explorer</h6>
        <div style="font-size:.8rem; color:#374151; line-height:1.7">
          <p class="mb-1">🎨 <strong>Designer médical</strong> — visuels & stands</p>
          <p class="mb-1">📱 <strong>Community manager santé</strong></p>
          <p class="mb-1">🎙️ <strong>Modérateur de congrès</strong></p>
          <p class="mb-1">📊 <strong>Data analyst événementiel</strong></p>
          <p class="mb-1">🎬 <strong>Vidéaste médical</strong></p>
          <p class="mb-1">🌐 <strong>Traducteur médical simultané</strong></p>
          <p class="mb-0">💼 <strong>Fundraiser / sponsor manager</strong></p>
        </div>
      </div>

    </div>
  </div>
</div>

<!-- ── Script IA ── -->
<script>
// Contexte BDD injecté dans le prompt système
const contexteBDD = {
  specialites: <?= json_encode(array_column($specialites, 'specialite'), JSON_UNESCAPED_UNICODE) ?>,
  professions:  <?= json_encode(array_column($professions,  'profession'),  JSON_UNESCAPED_UNICODE) ?>
};

const systemPrompt = `Tu es un assistant expert en métiers créatifs et médicaux pour la plateforme DocTime, une application de gestion d'événements médicaux en Tunisie.

Contexte de la base de données actuelle :
- Spécialités médicales des événements : ${contexteBDD.specialites.join(', ') || 'non spécifié'}
- Professions des participants : ${contexteBDD.professions.join(', ') || 'non spécifié'}

Tu aides les utilisateurs à :
1. Découvrir les métiers créatifs liés à l'événementiel médical
2. Comprendre les compétences requises pour chaque métier
3. Obtenir des conseils de carrière adaptés au contexte tunisien
4. Explorer les opportunités dans le domaine médico-événementiel

Réponds toujours en français, de façon concise, structurée et professionnelle.
Utilise des emojis pertinents pour rendre les réponses visuellement attrayantes.
Quand tu décris un métier, mentionne : titre, description, compétences clés, opportunités en Tunisie.`;

let conversationHistory = [];
let isTyping = false;

// ── Initialisation ─────────────────────────────────────────────────────────
window.addEventListener('load', () => {
  addMessage('ai', `👋 Bonjour ! Je suis votre assistant IA spécialisé en **métiers créatifs médicaux**.\n\nJe connais votre plateforme DocTime et les événements enregistrés. Posez-moi vos questions sur les métiers, compétences, ou opportunités de carrière dans l'événementiel médical en Tunisie ! 🇹🇳`);
});

// ── Envoi message ──────────────────────────────────────────────────────────
async function sendMessage() {
  const input = document.getElementById('userInput');
  const text  = input.value.trim();
  if (!text || isTyping) return;

  input.value = '';
  addMessage('user', text);
  conversationHistory.push({ role: 'user', content: text });

  setTyping(true);

  try {
    // Appel via proxy PHP (évite CORS + cache la clé API)
    const response = await fetch('index.php?controller=aiproxy&action=chat', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        system: systemPrompt,
        messages: conversationHistory
      })
    });

    if (!response.ok) {
      const errData = await response.json().catch(() => ({}));
      const errMsg = errData.error || ('Erreur HTTP ' + response.status);
      throw new Error(errMsg);
    }

    const data = await response.json();
    const reply = data.content?.[0]?.text || "Désolé, je n'ai pas pu générer une réponse.";

    conversationHistory.push({ role: 'assistant', content: reply });
    setTyping(false);
    addMessage('ai', reply);

  } catch (err) {
    setTyping(false);
    const msg = err.message.includes('Clé API') 
      ? '⚙️ Clé API non configurée. Ouvrez <code>controller/AiProxyController.php</code> et renseignez <code>$apiKey</code>.'
      : '❌ ' + (err.message || 'Erreur de connexion. Vérifiez votre serveur.');
    addMessage('ai', msg);
    console.error(err);
  }
}

function quickAsk(text) {
  document.getElementById('userInput').value = text;
  sendMessage();
}

// ── Affichage messages ─────────────────────────────────────────────────────
function addMessage(role, text) {
  const box = document.getElementById('chatBox');
  const div = document.createElement('div');
  div.className = `msg ${role}`;
  
  const avatar = role === 'ai'
    ? '<div class="msg-avatar"><i class="bi bi-robot"></i></div>'
    : '<div class="msg-avatar"><i class="bi bi-person-fill"></i></div>';

  // Markdown simple
  const formatted = text
    .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
    .replace(/\n/g, '<br>');

  div.innerHTML = `${avatar}<div class="msg-bubble">${formatted}</div>`;
  box.appendChild(div);
  box.scrollTop = box.scrollHeight;
}

function setTyping(active) {
  isTyping = active;
  const btn = document.getElementById('sendBtn');
  btn.disabled = active;

  const box = document.getElementById('chatBox');
  const existing = document.getElementById('typingIndicator');
  if (existing) existing.remove();

  if (active) {
    const div = document.createElement('div');
    div.className = 'msg ai';
    div.id = 'typingIndicator';
    div.innerHTML = `
      <div class="msg-avatar"><i class="bi bi-robot"></i></div>
      <div class="msg-bubble">
        <div class="typing-indicator">
          <span></span><span></span><span></span>
        </div>
      </div>`;
    box.appendChild(div);
    box.scrollTop = box.scrollHeight;
  }
}

function clearChat() {
  conversationHistory = [];
  const box = document.getElementById('chatBox');
  box.innerHTML = '';
  addMessage('ai', '🔄 Conversation réinitialisée. Comment puis-je vous aider ?');
}
</script>

<?php require __DIR__ . '/../layout_footer.php'; ?>
