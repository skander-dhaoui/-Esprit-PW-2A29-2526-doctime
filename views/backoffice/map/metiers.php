<?php
/**
 * IA Métiers créatifs — assistant + panneaux (référence type DocTime).
 */

if (!isset($pageTitle)) {
    $pageTitle = 'IA Métiers créatifs';
}
if (!isset($specialites)) {
    $specialites = [];
}
if (!isset($participantProfessions)) {
    $participantProfessions = [];
}
if (!isset($recentEventsForAssistant)) {
    $recentEventsForAssistant = [];
}

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    header('Location: index.php?page=login');
    exit;
}

$metiersExplorer = [
    ['icon' => 'fa-palette', 'title' => 'Designer médical', 'sub' => 'visuels & stands'],
    ['icon' => 'fa-share-alt', 'title' => 'Community manager santé', 'sub' => 'réseaux & contenus'],
    ['icon' => 'fa-microphone', 'title' => 'Modérateur de congrès', 'sub' => 'sessions & Q&R'],
    ['icon' => 'fa-chart-line', 'title' => 'Data analyst événementiel', 'sub' => 'KPI & reporting'],
    ['icon' => 'fa-video', 'title' => 'Vidéaste médical', 'sub' => 'captation & montage'],
    ['icon' => 'fa-language', 'title' => 'Traducteur médical', 'sub' => 'simultané & technique'],
    ['icon' => 'fa-hand-holding-usd', 'title' => 'Fundraiser / sponsor manager', 'sub' => 'partenariats'],
];

$chipTopics = ['Carrière', 'Compétences', 'TN Tunisie', 'Coordinateur', 'Autre'];
foreach ($specialites as $row) {
    $s = trim((string) ($row['specialite'] ?? ''));
    if ($s !== '') {
        $chipTopics[] = $s;
    }
}
$chipTopics = array_values(array_unique($chipTopics));
$chipTopics = array_slice($chipTopics, 0, 14);

$boBodyClass = 'page-metiers-adm';
require __DIR__ . '/../layout_header.php';

$ctxJson = json_encode(
    [
        'specialites'             => $specialites,
        'participantProfessions' => $participantProfessions,
        'recentEvents'           => $recentEventsForAssistant,
    ],
    JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
);
?>

<style>
    body.page-metiers-adm .topbar .badge-time {
        background: #15803d !important;
        background-image: none !important;
    }
    .metiers-ia-wrap { max-width: 1400px; margin: 0 auto; }
    .metiers-chat-card {
        border: 1px solid #e8ecf1;
        border-radius: 14px;
        background: #fff;
        box-shadow: 0 4px 24px rgba(30, 34, 53, 0.06);
        display: flex;
        flex-direction: column;
        min-height: 520px;
        max-height: calc(100vh - 220px);
    }
    .metiers-chat-head {
        padding: 14px 18px;
        border-bottom: 1px solid #eef1f5;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 10px;
    }
    .metiers-chat-head .assistant-label {
        font-weight: 700;
        color: #1e293b;
        font-size: 1rem;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .metiers-chat-head .dot-online {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #22c55e;
        box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.25);
    }
    .metiers-chat-body {
        flex: 1;
        overflow-y: auto;
        padding: 18px;
        background: linear-gradient(180deg, #fafbfc 0%, #fff 40%);
    }
    .metiers-msg {
        display: flex;
        gap: 12px;
        margin-bottom: 16px;
        align-items: flex-start;
    }
    .metiers-msg.user { flex-direction: row-reverse; }
    .metiers-msg .avatar-bot {
        width: 42px;
        height: 42px;
        border-radius: 12px;
        background: linear-gradient(135deg, #7c3aed, #a78bfa);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 1.1rem;
    }
    .metiers-msg .bubble {
        max-width: min(92%, 560px);
        padding: 14px 16px;
        border-radius: 14px;
        font-size: 0.92rem;
        line-height: 1.55;
        color: #334155;
    }
    .metiers-msg.assistant .bubble {
        background: #fff;
        border: 1px solid #e8ecf1;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    }
    .metiers-msg.user .bubble {
        background: linear-gradient(135deg, #ecfdf5, #d1fae5);
        border: 1px solid rgba(34, 197, 94, 0.25);
        color: #14532d;
    }
    .metiers-chips {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 14px;
        padding-top: 14px;
        border-top: 1px dashed #e2e8f0;
    }
    .metiers-chip {
        border: 1px solid #cbd5e1;
        background: #fff;
        color: #475569;
        font-size: 0.8rem;
        padding: 6px 12px;
        border-radius: 999px;
        cursor: pointer;
        transition: border-color 0.15s, background 0.15s;
    }
    .metiers-chip:hover {
        border-color: #22c55e;
        background: #f0fdf4;
        color: #166534;
    }
    .metiers-chat-foot {
        padding: 14px 18px;
        border-top: 1px solid #eef1f5;
        background: #fff;
        border-radius: 0 0 14px 14px;
    }
    .metiers-panel {
        border: 1px solid #e8ecf1;
        border-radius: 14px;
        background: #fff;
        box-shadow: 0 4px 24px rgba(30, 34, 53, 0.06);
        margin-bottom: 16px;
        overflow: hidden;
    }
    .metiers-panel .panel-h {
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: #64748b;
        padding: 12px 14px;
        border-bottom: 1px solid #f1f5f9;
        background: #fafbfc;
    }
    .metiers-panel .panel-b {
        padding: 14px;
        max-height: 220px;
        overflow-y: auto;
    }
    .pill-spec {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin: 4px;
        padding: 6px 11px;
        border-radius: 999px;
        font-size: 0.8rem;
        border: 1px solid rgba(124, 58, 237, 0.35);
        color: #6d28d9;
        background: rgba(124, 58, 237, 0.06);
    }
    .pill-spec strong { font-weight: 700; color: #5b21b6; }
    .pill-part {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin: 4px;
        padding: 6px 11px;
        border-radius: 999px;
        font-size: 0.8rem;
        border: 1px solid rgba(34, 197, 94, 0.35);
        color: #15803d;
        background: rgba(34, 197, 94, 0.08);
    }
    .pill-part strong { font-weight: 700; }
    .metiers-explore-item {
        display: flex;
        gap: 12px;
        align-items: flex-start;
        padding: 10px 0;
        border-bottom: 1px solid #f1f5f9;
    }
    .metiers-explore-item:last-child { border-bottom: 0; }
    .metiers-explore-item i {
        color: #22c55e;
        font-size: 1.1rem;
        margin-top: 3px;
        width: 1.25rem;
        text-align: center;
    }
    .metiers-explore-item .t { font-weight: 600; font-size: 0.88rem; color: #1e293b; }
    .metiers-explore-item .s { font-size: 0.78rem; color: #64748b; }
    @media (max-width: 991px) {
        .metiers-chat-card { max-height: none; min-height: 420px; }
    }
</style>

<div class="container-fluid metiers-ia-wrap pb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <a href="index.php?page=carte" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-map-marked-alt me-1"></i>Carte Tunisie
        </a>
        <a href="index.php?page=dashboard" class="small text-muted text-decoration-none">
            <i class="fas fa-arrow-left me-1"></i>Tableau de bord
        </a>
    </div>

    <div class="row g-4 align-items-start">
        <div class="col-lg-7">
            <div class="metiers-chat-card">
                <div class="metiers-chat-head">
                    <div class="assistant-label">
                        <span class="dot-online" aria-hidden="true"></span>
                        Assistant IA — conseiller métiers
                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 fw-normal ms-1">En ligne</span>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="metiersClearChat" title="Effacer la conversation">
                        <i class="fas fa-trash-alt me-1"></i>Effacer
                    </button>
                </div>
                <div class="metiers-chat-body" id="metiersChatBody">
                    <div class="metiers-msg assistant" data-welcome="1">
                        <div class="avatar-bot"><i class="fas fa-robot"></i></div>
                        <div class="bubble">
                            Bonjour ! Je suis votre assistant spécialisé en <strong>métiers créatifs médicaux</strong>.
                            Je m’appuie sur les événements et participations enregistrés sur <strong>Valorys</strong>.
                            Posez vos questions sur les métiers, compétences ou opportunités dans l’événementiel santé en Tunisie (TN).
                        </div>
                    </div>
                    <div class="metiers-chips" id="metiersSuggestChips">
                        <?php foreach ($chipTopics as $chip): ?>
                            <button type="button" class="metiers-chip" data-chip="<?= htmlspecialchars($chip, ENT_QUOTES, 'UTF-8') ?>">
                                <?php if (in_array($chip, ['Carrière', 'Compétences', 'Coordinateur'], true)): ?>
                                    <i class="fas fa-lightbulb text-warning me-1"></i>
                                <?php elseif ($chip === 'TN Tunisie'): ?>
                                    <i class="fas fa-flag me-1 text-success"></i>
                                <?php elseif ($chip === 'Autre'): ?>
                                    <i class="fas fa-ellipsis-h me-1 text-secondary"></i>
                                <?php else: ?>
                                    <i class="fas fa-stethoscope me-1 text-primary"></i>
                                <?php endif; ?>
                                <?= htmlspecialchars($chip, ENT_QUOTES, 'UTF-8') ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="metiers-chat-foot">
                    <form id="metiersChatForm" class="d-flex gap-2 flex-wrap align-items-stretch">
                        <label class="visually-hidden" for="metiersChatInput">Votre question</label>
                        <input type="text" class="form-control flex-grow-1" id="metiersChatInput"
                               placeholder="Posez une question sur les métiers médicaux / créatifs…" autocomplete="off">
                        <button type="submit" class="btn btn-success px-4">
                            <i class="fas fa-paper-plane me-1"></i>Envoyer
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="metiers-panel">
                <div class="panel-h">Spécialités dans la BDD</div>
                <div class="panel-b">
                    <?php if (empty($specialites)): ?>
                        <p class="text-muted small mb-0">Aucune catégorie renseignée sur les événements pour l’instant.</p>
                    <?php else: ?>
                        <?php foreach ($specialites as $row): ?>
                            <?php
                            $lab = (string) ($row['specialite'] ?? '');
                            $tot = (int) ($row['total'] ?? 0);
                            ?>
                            <span class="pill-spec"><?= htmlspecialchars($lab, ENT_QUOTES, 'UTF-8') ?> <strong><?= $tot ?></strong></span>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="metiers-panel">
                <div class="panel-h">Professions des participants</div>
                <div class="panel-b">
                    <?php if (empty($participantProfessions)): ?>
                        <p class="text-muted small mb-0">Pas encore de participation ou données agrégées.</p>
                    <?php else: ?>
                        <?php foreach ($participantProfessions as $row): ?>
                            <?php
                            $lab = (string) ($row['profession'] ?? '');
                            $tot = (int) ($row['total'] ?? 0);
                            ?>
                            <span class="pill-part"><?= htmlspecialchars($lab, ENT_QUOTES, 'UTF-8') ?> <strong><?= $tot ?></strong></span>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="metiers-panel mb-0">
                <div class="panel-h">Métiers à explorer</div>
                <div class="panel-b" style="max-height: none;">
                    <?php foreach ($metiersExplorer as $me): ?>
                        <div class="metiers-explore-item">
                            <i class="fas <?= htmlspecialchars($me['icon'], ENT_QUOTES, 'UTF-8') ?>"></i>
                            <div>
                                <div class="t"><?= htmlspecialchars($me['title'], ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="s"><?= htmlspecialchars($me['sub'], ENT_QUOTES, 'UTF-8') ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const METIERS_CTX = <?= $ctxJson !== false ? $ctxJson : '{}' ?>;

    function escapeHtml(s) {
        const d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }

    /** Escape puis **bold** puis retours ligne */
    function formatAssistantText(raw) {
        let h = escapeHtml(raw);
        h = h.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
        h = h.replace(/\n/g, '<br>');
        return h;
    }

    function appendMessage(kind, htmlInner) {
        const body = document.getElementById('metiersChatBody');
        const chips = document.getElementById('metiersSuggestChips');
        const wrap = document.createElement('div');
        wrap.className = 'metiers-msg ' + (kind === 'user' ? 'user' : 'assistant');
        if (kind === 'assistant') {
            wrap.innerHTML =
                '<div class="avatar-bot"><i class="fas fa-robot"></i></div>' +
                '<div class="bubble">' + htmlInner + '</div>';
        } else {
            wrap.innerHTML = '<div class="bubble">' + htmlInner + '</div>';
        }
        body.insertBefore(wrap, chips);
        body.scrollTop = body.scrollHeight;
    }

    function buildReply(q) {
        const text = (q || '').trim();
        const low = text.toLowerCase();
        const specs = METIERS_CTX.specialites || [];
        const parts = METIERS_CTX.participantProfessions || [];
        const events = METIERS_CTX.recentEvents || [];

        for (let i = 0; i < specs.length; i++) {
            const name = (specs[i].specialite || '').trim();
            if (name && low.includes(name.toLowerCase())) {
                const n = specs[i].total || 0;
                return 'Concernant **' + name + '** : la base compte **' + n + '** événement(s) dans cette catégorie. En Tunisie, l’événementiel santé autour de cette thématique inclut souvent congrès, sessions de formation continue et partenariats avec des sociétés savantes ou laboratoires.';
            }
        }

        if (/tunis|tunisie|\btn\b/.test(low)) {
            return 'Pour la **Tunisie (TN)** : les métiers créatifs autour de la santé se développent avec les salons médicaux, la communication digitale des établissements et la captation vidéo des journées scientifiques. Valorys centralise vos **événements** et **participants** pour suivre ces dynamiques.';
        }

        if (/carrière|emploi|formation|étudiant/.test(low)) {
            return 'Les parcours hybrides **santé + communication / design / data** sont demandés : community manager santé, chargé d’événements, médiateur scientifique, designer de supports pédagogiques. Explorez la liste **« Métiers à explorer »** à droite pour des pistes concrètes.';
        }

        if (/compétence|skill|savoir/.test(low)) {
            return 'Compétences souvent utiles : **organisation de chantiers événementiels**, **rédaction médicale** vulgarisée, **outil CRM / Excel**, **Anglais / français**, sens du **réseau** avec industriels et associations de patients.';
        }

        if (/coordinateur|coordination/.test(low)) {
            return 'Le **coordinateur événementiel santé** pilote planning, intervenants, stands et conformité (invitations, traçabilité). Il fait le lien entre directions médicales, marketing et prestataires techniques.';
        }

        if (/carte|map|géo/.test(low)) {
            return 'Pour une vue géographique des événements, ouvrez **Carte Tunisie** depuis le menu ou le lien en haut de cette page.';
        }

        if (/événement|evenement|congrès|salon/.test(low) && events.length) {
            let lines = events.slice(0, 4).map(function (e) {
                return '• **' + e.titre + '** (' + e.categorie + ')';
            }).join('\n');
            return 'Voici quelques **événements récents** en base :\n' + lines + '\n\nJe peux détailler une spécialité ou un profil participant si vous précisez votre question.';
        }

        if (/participant|inscrit|profession/.test(low) && parts.length) {
            let top = parts.slice(0, 5).map(function (p) {
                return '• **' + p.profession + '** : ' + p.total + ' inscription(s)';
            }).join('\n');
            return 'Aperçu des **profils participants** (agrégés) :\n' + top + '\n\nCes données viennent des comptes liés aux inscriptions événements.';
        }

        return 'Je peux vous orienter sur les **métiers créatifs** autour de la santé en Tunisie : communications médicales, organisation de congrès, design de stands, vidéo, traduction, fundraising… '
            + 'Indiquez une **spécialité** (voir les pastilles violettes à droite), un **type de métier**, ou le mot **TN** pour le contexte tunisien.';
    }

    document.getElementById('metiersChatForm').addEventListener('submit', function (ev) {
        ev.preventDefault();
        const input = document.getElementById('metiersChatInput');
        const q = input.value.trim();
        if (!q) return;
        appendMessage('user', escapeHtml(q));
        input.value = '';
        const reply = buildReply(q);
        setTimeout(function () {
            appendMessage('assistant', formatAssistantText(reply));
        }, 380);
    });

    document.getElementById('metiersSuggestChips').addEventListener('click', function (ev) {
        const btn = ev.target.closest('.metiers-chip');
        if (!btn) return;
        const v = btn.getAttribute('data-chip') || '';
        document.getElementById('metiersChatInput').value = v;
        document.getElementById('metiersChatForm').requestSubmit();
    });

    document.getElementById('metiersClearChat').addEventListener('click', function () {
        const body = document.getElementById('metiersChatBody');
        body.querySelectorAll('.metiers-msg:not([data-welcome])').forEach(function (el) { el.remove(); });
        body.scrollTop = 0;
    });
})();
</script>

<?php require __DIR__ . '/../layout_footer.php'; ?>
