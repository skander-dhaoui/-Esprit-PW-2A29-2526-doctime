<?php
// views/backoffice/article_detail.php
// Variables expected: $article (array), $isEdit (bool)

if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($article) || !$article) {
    header('Location: index.php?page=articles_admin');
    exit;
}

// Ensure CSRF token exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$id = $article['id'] ?? 0;
$title = htmlspecialchars($article['titre'] ?? 'Article');
$author = htmlspecialchars(($article['prenom'] ?? '') . ' ' . ($article['nom'] ?? ''));
$status = $article['status'] ?? 'brouillon';
$content = $article['contenu'] ?? '';
$createdAt = date('d/m/Y H:i', strtotime($article['created_at'] ?? 'now'));
$views = (int)($article['vues'] ?? 0);
$categorie = htmlspecialchars($article['categorie'] ?? 'Sans catégorie');
$tags = htmlspecialchars($article['tags'] ?? '');
$comments = $comments ?? [];
$commentCount = $commentCount ?? 0;

// Parse JSON content if it's Quill format
$contentHtml = $content;
if (json_validate($content)) {
    $quillContent = json_decode($content, true);
    $contentHtml = renderQuillContent($quillContent);
} else {
    $contentHtml = nl2br(htmlspecialchars($content));
}

function json_validate($string) {
    json_decode($string);
    return json_last_error() === JSON_ERROR_NONE;
}

function renderQuillContent($quillDelta) {
    if (!is_array($quillDelta) || !isset($quillDelta['ops'])) {
        return '<p>Contenu non disponible</p>';
    }
    
    $html = '';
    foreach ($quillDelta['ops'] as $op) {
        if (isset($op['insert'])) {
            $insert = $op['insert'];
            $attributes = $op['attributes'] ?? [];
            
            if (is_string($insert)) {
                $text = htmlspecialchars($insert);
                
                if ($attributes['bold'] ?? false) $text = "<strong>$text</strong>";
                if ($attributes['italic'] ?? false) $text = "<em>$text</em>";
                if ($attributes['underline'] ?? false) $text = "<u>$text</u>";
                if ($attributes['strike'] ?? false) $text = "<s>$text</s>";
                
                if (isset($attributes['header'])) {
                    $level = $attributes['header'];
                    $text = "<h$level>$text</h$level>";
                }
                if ($attributes['blockquote'] ?? false) $text = "<blockquote>$text</blockquote>";
                if ($attributes['code-block'] ?? false) $text = "<pre><code>$text</code></pre>";
                if ($attributes['list'] ?? false) $text = "<li>$text</li>";
                
                $html .= $text;
            } elseif (is_array($insert) && isset($insert['image'])) {
                $html .= "<img src='" . htmlspecialchars($insert['image']) . "' style='max-width:100%;height:auto;border-radius:8px;margin:12px 0'>";
            }
        }
    }
    
    return $html;
}

$badgeClass = match($status) {
    'publié'  => 'bg-success',
    'archive' => 'bg-secondary',
    default   => 'bg-warning',
};

$badgeText = match($status) {
    'publié'  => '✅ Publié',
    'archive' => '📦 Archivé',
    default   => '📝 Brouillon',
};
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?> - MediConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #f0f2f5; font-family: 'Segoe UI', sans-serif; display: flex; min-height: 100vh; }
        .main-content { margin-left: 260px; flex: 1; padding: 25px; }
        .page-header {
            background: white; border-radius: 12px; padding: 18px 25px; margin-bottom: 25px;
            display: flex; align-items: center; justify-content: space-between;
            box-shadow: 0 1px 6px rgba(0,0,0,0.06);
        }
        .page-header h4 { font-size: 18px; font-weight: 700; color: #1a2035; margin: 0; display: flex; align-items: center; gap: 10px; }
        .page-header h4 i { color: #0fa99b; }
        .content-card { background: white; border-radius: 12px; padding: 28px; box-shadow: 0 1px 6px rgba(0,0,0,0.06); margin-bottom: 20px; }
        
        .article-title { font-size: 32px; font-weight: 700; color: #1a2035; margin-bottom: 18px; line-height: 1.3; }
        .article-meta {
            display: flex;
            gap: 24px;
            flex-wrap: wrap;
            padding: 16px 0;
            border-top: 1px solid #e2e8f0;
            border-bottom: 1px solid #e2e8f0;
            margin-bottom: 24px;
        }
        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: #475569;
        }
        .meta-item i { font-size: 16px; color: #0fa99b; }
        .meta-label { font-weight: 600; color: #1a2035; }
        .badge { padding: 8px 14px; font-size: 13px; font-weight: 600; }
        
        .article-content {
            font-size: 16px;
            line-height: 1.8;
            color: #334155;
        }
        .article-content h1, .article-content h2, .article-content h3 {
            margin: 24px 0 16px;
            color: #1a2035;
            font-weight: 700;
        }
        .article-content p { margin-bottom: 16px; }
        .article-content img { max-width: 100%; height: auto; border-radius: 8px; margin: 20px 0; }
        .article-content blockquote {
            border-left: 4px solid #0fa99b;
            padding: 12px 16px;
            background: #f0f9f8;
            margin: 16px 0;
            font-style: italic;
            color: #475569;
        }
        .article-content code { background: #f1f5f9; padding: 2px 6px; border-radius: 4px; font-family: 'Courier New', monospace; }
        .article-content pre {
            background: #f1f5f9;
            padding: 16px;
            border-radius: 8px;
            overflow-x: auto;
            margin: 16px 0;
        }
        
        .tags-container { margin-top: 24px; padding-top: 24px; border-top: 1px solid #e2e8f0; }
        .tag { display: inline-block; background: #e0f7f4; color: #0fa99b; padding: 6px 12px; border-radius: 20px; font-size: 12px; margin-right: 8px; margin-bottom: 8px; font-weight: 600; }
        
        /* Styles pour les commentaires enrichis */
        .comment-content { font-size: 14px; line-height: 1.6; }
        .comment-content h1, .comment-content h2, .comment-content h3 { margin: 12px 0 8px; color: #1a2035; font-weight: 700; }
        .comment-content p { margin-bottom: 8px; }
        .comment-content img { max-width: 100%; height: auto; border-radius: 6px; margin: 10px 0; }
        .comment-content blockquote { border-left: 3px solid #0fa99b; padding: 8px 12px; background: #f0f9f8; margin: 8px 0; font-style: italic; color: #475569; font-size: 13px; }
        .comment-content code { background: #f1f5f9; padding: 2px 5px; border-radius: 3px; font-family: 'Courier New', monospace; font-size: 12px; }
        .comment-content ul, .comment-content ol { margin: 8px 0 8px 20px; }
        
        .action-buttons {
            display: flex;
            gap: 12px;
            margin-top: 24px;
            padding-top: 24px;
            border-top: 1px solid #e2e8f0;
        }
        .btn-custom {
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }
        .btn-edit { background: #e3f0ff; color: #1565c0; }
        .btn-edit:hover { background: #bbdefb; }
        .btn-delete { background: #fdecea; color: #c62828; }
        .btn-delete:hover { background: #ffcdd2; }
        .btn-back { background: #e2e8f0; color: #334155; }
        .btn-back:hover { background: #cbd5e1; }
        
        .sidebar { position: fixed; top: 0; left: 0; width: 260px; height: 100vh; background: #1a2035; }
    </style>
</head>
<body>

<?php include __DIR__ . '/sidebar.php'; ?>

<div class="main-content">
    <div class="page-header">
        <h4><i class="fas fa-eye"></i> Détails de l'article</h4>
        <a href="index.php?page=articles_admin" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i> Retour
        </a>
    </div>

    <div class="content-card">
        <h1 class="article-title"><?= $title ?></h1>

        <div class="article-meta">
            <div class="meta-item">
                <i class="fas fa-user-circle"></i>
                <span class="meta-label">Auteur:</span>
                <span><?= $author ?: 'Inconnu' ?></span>
            </div>
            <div class="meta-item">
                <i class="fas fa-calendar"></i>
                <span class="meta-label">Date:</span>
                <span><?= $createdAt ?></span>
            </div>
            <div class="meta-item">
                <i class="fas fa-eye"></i>
                <span class="meta-label">Vues:</span>
                <span class="badge bg-info"><?= $views ?></span>
            </div>
            <div class="meta-item">
                <i class="fas fa-tag"></i>
                <span class="meta-label">Catégorie:</span>
                <span><?= $categorie ?></span>
            </div>
            <div class="meta-item">
                <i class="fas fa-flag"></i>
                <span class="meta-label">Statut:</span>
                <span class="badge <?= $badgeClass ?>"><?= $badgeText ?></span>
            </div>
        </div>

        <div class="article-content">
            <?= $contentHtml ?>
        </div>

        <?php if ($tags): ?>
            <div class="tags-container">
                <strong style="display: block; margin-bottom: 12px; color: #1a2035;">📌 Tags</strong>
                <?php foreach (array_map('trim', explode(',', $tags)) as $tag): ?>
                    <span class="tag"><?= htmlspecialchars($tag) ?></span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="action-buttons">
            <a href="index.php?page=articles_admin&action=edit&id=<?= $id ?>" class="btn-custom btn-edit">
                <i class="fas fa-edit"></i> Modifier
            </a>
            <a href="index.php?page=articles_admin&action=delete&id=<?= $id ?>" class="btn-custom btn-delete" onclick="return confirm('Supprimer définitivement cet article ?')">
                <i class="fas fa-trash"></i> Supprimer
            </a>
            <a href="index.php?page=articles_admin" class="btn-custom btn-back">
                <i class="fas fa-arrow-left"></i> Retour à la liste
            </a>
        </div>
    </div>

    <!-- SECTION COMMENTAIRES -->
    <div class="content-card" style="margin-top: 32px;">
        <h3 style="font-size:20px;font-weight:700;color:#1a2035;margin-bottom:24px;display:flex;align-items:center;gap:10px">
            <i class="fas fa-comments" style="color:#0fa99b"></i>
            Commentaires (<?= $commentCount ?? 0 ?>)
        </h3>

        <!-- Formulaire d'ajout de commentaire -->
        <?php if ($isLoggedIn = !empty($_SESSION['user_id'])): ?>
        <div style="background:#f8f9fa;border:1px solid #e2e8f0;border-radius:8px;padding:20px;margin-bottom:24px">
            <h4 style="font-size:14px;font-weight:600;color:#1a2035;margin-bottom:14px">
                <i class="fas fa-pen-to-square me-2" style="color:#0fa99b"></i>Ajouter un commentaire
            </h4>
            
            <form method="POST" action="index.php?page=articles_admin&action=add_comment&id=<?= $id ?>" id="commentForm" style="display:flex;flex-direction:column;gap:12px">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                <input type="hidden" name="comment" id="commentField" value="">
                
                <!-- Boutons outils -->
                <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
                    <button type="button" id="commentEmojiBtn" class="btn btn-sm btn-outline-secondary" style="border-radius:6px;padding:6px 12px;font-size:14px">
                        <i class="fas fa-smile me-1"></i>Emoji
                    </button>
                    <button type="button" id="commentImgBtn" class="btn btn-sm btn-outline-secondary" style="border-radius:6px;padding:6px 12px;font-size:14px">
                        <i class="fas fa-image me-1"></i>Image
                    </button>
                    <input type="file" id="commentImgUpload" accept="image/*" style="display:none">
                    <small class="text-muted">📝 Texte • 🖼️ Images • 😊 Emoji</small>
                </div>
                
                <!-- Emoji Picker pour commentaires -->
                <div id="commentEmojiPicker" style="display:none;background:#f8f9fa;border:1px solid #dee2e6;border-radius:8px;padding:12px;max-height:300px;overflow-y:auto">
                    <div id="commentEmojiGrid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(30px,1fr));gap:5px"></div>
                </div>
                
                <!-- Image Preview pour commentaires -->
                <div id="commentImgPreview" style="display:none">
                    <img id="commentImgPreviewImg" src="" style="max-width:100%;max-height:200px;border-radius:8px;border:1px solid #dee2e6">
                    <button type="button" id="removeCommentImg" class="btn btn-sm btn-danger mt-2">
                        <i class="fas fa-trash me-1"></i>Supprimer
                    </button>
                </div>
                
                <!-- Quill Editor pour commentaires -->
                <div id="commentEditor" style="background:white;border:1.5px solid #e0e6ed;border-radius:8px;min-height:150px"></div>
                
                <button type="submit" style="background:linear-gradient(135deg,#2A7FAA,#4CAF50);color:white;border:none;border-radius:8px;padding:10px 20px;font-weight:600;font-size:14px;cursor:pointer;display:inline-flex;align-items:center;gap:8px;align-self:flex-start;transition:all 0.2s">
                    <i class="fas fa-paper-plane"></i> Publier le commentaire
                </button>
            </form>
        </div>
        <?php else: ?>
        <div style="background:#fff3cd;border:1px solid #ffc107;border-radius:8px;padding:14px;margin-bottom:24px;color:#856404">
            <i class="fas fa-info-circle me-2"></i>
            <a href="index.php?page=login" style="color:#0d6efd;text-decoration:none;font-weight:600">Connectez-vous</a> pour ajouter un commentaire
        </div>
        <?php endif; ?>

        <!-- Liste des commentaires approuvés -->
        <?php if (!empty($comments)): ?>
        <div style="display:flex;flex-direction:column;gap:16px">
            <?php foreach ($comments as $comment): ?>
            <div style="border:1px solid #e2e8f0;border-radius:8px;padding:16px;background:#fafbfc">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:10px">
                    <div>
                        <strong style="color:#1a2035;font-size:14px;font-weight:600">
                            <?= htmlspecialchars($comment['user_name'] ?? 'Anonyme') ?>
                        </strong>
                        <small style="color:#64748b;display:block;margin-top:3px">
                            <i class="fas fa-calendar-alt me-1"></i>
                            <?= date('d/m/Y H:i', strtotime($comment['created_at'] ?? 'now')) ?>
                        </small>
                    </div>
                    <?php if ($comment['status'] === 'approuvé'): ?>
                    <span style="background:#e8f5e9;color:#2e7d32;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:600">
                        ✓ Approuvé
                    </span>
                    <?php endif; ?>
                </div>
                <div style="color:#334155;line-height:1.6;margin:0;font-size:14px" class="comment-content">
                    <?php
                        $commentContent = $comment['replay'] ?? '';
                        // Si c'est du JSON (contenu enrichi Quill)
                        if (json_validate($commentContent)) {
                            $quillContent = json_decode($commentContent, true);
                            echo renderQuillContent($quillContent);
                        } else {
                            // Sinon, afficher comme texte brut avec nl2br
                            echo nl2br(htmlspecialchars($commentContent));
                        }
                    ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div style="text-align:center;padding:40px 20px;color:#64748b">
            <i class="fas fa-comments fa-2x mb-3 d-block" style="opacity:0.3"></i>
            <p>Aucun commentaire pour le moment. Soyez le premier à commenter ! 💬</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- Quill Editor -->
<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">

<script>
// === INITIALIZE QUILL EDITOR FOR COMMENTS ===
const commentQuill = new Quill('#commentEditor', {
    theme: 'snow',
    placeholder: 'Écrivez votre commentaire ici…',
    modules: {
        toolbar: [
            ['bold', 'italic', 'underline'],
            ['blockquote'],
            [{ 'list': 'ordered'}, { 'list': 'bullet' }],
            ['link', 'image'],
            ['clean']
        ]
    }
});

// === EMOJI PICKER FOR COMMENTS ===
const commentEmojis = ['😀', '😂', '🤣', '😃', '😄', '😁', '😆', '😅', '🤭', '😉', '😊', '😇', '🙂', '🙃', '😉', '😌', '😍', '🥰', '😘', '😗', '😚', '😙', '🥲', '😋', '😛', '😜', '🤪', '😝', '🤑', '🤗', '❤️', '🧡', '💛', '💚', '💙', '💜', '🖤', '🤍', '👍', '👎', '👋', '👊', '✊', '🔥', '⭐', '✨'];

const commentEmojiBtn = document.getElementById('commentEmojiBtn');
const commentEmojiPicker = document.getElementById('commentEmojiPicker');
const commentEmojiGrid = document.getElementById('commentEmojiGrid');

// Generate emoji grid for comments
commentEmojis.forEach(emoji => {
    const span = document.createElement('span');
    span.textContent = emoji;
    span.style.cursor = 'pointer';
    span.style.fontSize = '24px';
    span.style.padding = '8px';
    span.style.borderRadius = '4px';
    span.style.transition = 'all 0.2s';
    span.onmouseover = () => span.style.background = '#e0e0e0';
    span.onmouseout = () => span.style.background = 'transparent';
    span.onclick = () => {
        commentQuill.insertText(commentQuill.getSelection()?.index || commentQuill.getLength(), emoji);
        commentQuill.setSelection(commentQuill.getSelection().index + emoji.length);
    };
    commentEmojiGrid.appendChild(span);
});

// Toggle emoji picker
commentEmojiBtn.addEventListener('click', (e) => {
    e.preventDefault();
    commentEmojiPicker.style.display = commentEmojiPicker.style.display === 'none' ? 'grid' : 'none';
});

// === IMAGE UPLOAD FOR COMMENTS ===
const commentImgBtn = document.getElementById('commentImgBtn');
const commentImgUpload = document.getElementById('commentImgUpload');
const commentImgPreview = document.getElementById('commentImgPreview');
const commentImgPreviewImg = document.getElementById('commentImgPreviewImg');
const removeCommentImg = document.getElementById('removeCommentImg');

commentImgBtn.addEventListener('click', (e) => {
    e.preventDefault();
    commentImgUpload.click();
});

commentImgUpload.addEventListener('change', (e) => {
    const file = e.target.files[0];
    if (!file) return;

    if (file.size > 2 * 1024 * 1024) {
        alert('L\'image doit faire moins de 2MB');
        return;
    }

    const reader = new FileReader();
    reader.onload = (event) => {
        const imageDataUrl = event.target.result;
        const index = commentQuill.getSelection()?.index || commentQuill.getLength();
        commentQuill.insertEmbed(index, 'image', imageDataUrl);
        commentQuill.setSelection(index + 1);
        
        commentImgPreviewImg.src = imageDataUrl;
        commentImgPreview.style.display = 'block';
    };
    reader.readAsDataURL(file);
});

removeCommentImg.addEventListener('click', (e) => {
    e.preventDefault();
    commentImgPreview.style.display = 'none';
    commentImgPreviewImg.src = '';
    commentImgUpload.value = '';
});

// === FORM SUBMISSION ===
document.getElementById('commentForm').addEventListener('submit', function(e) {
    const commentText = commentQuill.getText().trim();
    if (!commentText || commentText.length < 3) {
        e.preventDefault();
        alert('Le commentaire doit faire au moins 3 caractères.');
        return;
    }
    
    // Save content to hidden field before submission
    document.getElementById('commentField').value = JSON.stringify(commentQuill.getContents());
});

// Initialize tooltips
new bootstrap.Tooltip(document.body, { selector: '[data-bs-toggle="tooltip"]' });
</script>

</body>
</html>
