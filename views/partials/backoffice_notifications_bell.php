<?php
declare(strict_types=1);
/** Cloche notifications admin — inclure dans la topbar back-office */
if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    return;
}
$boAdminNotifCount = 0;
$boAdminNotifsPreview = [];
try {
    require_once __DIR__ . '/../../models/AdminNotification.php';
    $___an = new AdminNotification();
    $boAdminNotifCount = $___an->countUnread();
    $boAdminNotifsPreview = $___an->getAll(15);
} catch (Throwable $e) {
    /* table absente */
}
?>
<div class="dropdown me-2">
    <button class="btn btn-sm btn-outline-secondary position-relative rounded-pill px-3" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Notifications administrateur">
        <i class="fas fa-bell"></i>
        <?php if ($boAdminNotifCount > 0): ?>
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:0.65rem;"><?= (int) $boAdminNotifCount ?></span>
        <?php endif; ?>
    </button>
    <ul class="dropdown-menu dropdown-menu-end shadow border-0" style="min-width: 300px; max-height: 380px; overflow-y: auto;">
        <li class="px-3 py-2 border-bottom small fw-semibold text-muted">Alertes patients (blog)</li>
        <?php if (empty($boAdminNotifsPreview)): ?>
            <li><span class="dropdown-item-text small text-muted py-3">Aucune notification</span></li>
        <?php else: ?>
            <?php foreach ($boAdminNotifsPreview as $n): ?>
                <?php
                $nid = (int) ($n['id'] ?? 0);
                $rid = isset($n['reference_id']) ? (int) $n['reference_id'] : 0;
                $view = $rid > 0
                    ? ('index.php?page=detail_article_public&id=' . $rid)
                    : 'index.php?page=articles_admin';
                $oneClick = 'index.php?mark_notif_read=' . $nid . '&notif_redirect=' . rawurlencode($view);
                ?>
                <li>
                    <a class="dropdown-item small py-2 <?= empty($n['is_read']) ? 'fw-semibold' : '' ?>" href="<?= htmlspecialchars($oneClick, ENT_QUOTES, 'UTF-8') ?>">
                        <span class="d-block text-dark"><?= htmlspecialchars((string) ($n['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="d-block text-muted" style="font-size:0.8rem;"><?= htmlspecialchars(mb_substr((string) ($n['message'] ?? ''), 0, 140), ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="badge bg-light text-secondary border mt-1" style="font-size:0.7rem;"><?= date('d/m/Y H:i', strtotime((string) ($n['created_at'] ?? 'now'))) ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        <?php endif; ?>
        <li class="border-top mt-1 pt-2 px-2">
            <a class="btn btn-sm btn-outline-secondary w-100 rounded-pill" href="index.php?page=dashboard">Tableau de bord</a>
        </li>
    </ul>
</div>
