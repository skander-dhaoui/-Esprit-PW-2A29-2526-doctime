<?php
// Layout principal unifié pour les pages admin.
$pageTitle = $pageTitle ?? ($page_title ?? 'Administration');

// Déterminer la classe de rôle pour le styling
$userRole = $_SESSION['user_role'] ?? '';
$roleClass = '';
if ($userRole === 'medecin') {
    $roleClass = 'role-medecin';
} elseif ($userRole === 'patient') {
    $roleClass = 'role-patient';
} elseif ($userRole === 'admin') {
    $roleClass = 'role-admin';
}
?>
<div class="main-content <?= $roleClass ?>">
<?php require __DIR__ . '/layout_header.php'; ?>

<?php if (isset($_SESSION['flash'])): ?>
    <div class="alert alert-<?= $_SESSION['flash']['type'] === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show">
        <?= htmlspecialchars($_SESSION['flash']['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['flash']); ?>
<?php endif; ?>

<?php include $contentFile; ?>
<?php include __DIR__ . '/components/confirm_modal.php'; ?>

<?php require __DIR__ . '/layout_footer.php'; ?>
</div> ?>

