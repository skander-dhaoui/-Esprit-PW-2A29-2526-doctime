<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DocTime – Valorys</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <?php include __DIR__ . '/../partials/public_theme_styles.php'; ?>
    <style>
        :root { --green: #1a7fa8; --green-end: #1db88e; --green-light: #e0f4f8; }
        footer { background: linear-gradient(135deg,#0d4f6b,#0e7a5c); color: rgba(255,255,255,.75); }
        .card { border: none; box-shadow: 0 2px 12px rgba(0,0,0,.08); border-radius: 14px; }
        .badge-specialite { background: var(--green-light); color: var(--green); font-weight: 600; font-size: .75rem; }
        .btn-green { background: linear-gradient(90deg,var(--green),var(--green-end)); color: #fff; border: none; border-radius: 8px; }
        .btn-green:hover { opacity: .88; color: #fff; }
        .invalid-feedback { font-size: .8rem; }
        .is-invalid { border-color: #dc3545 !important; }
    </style>
</head>
<body>

<?php
if (!isset($navActive)) {
    $navActive = $_GET['page'] ?? '';
}
include __DIR__ . '/../partials/nav_public.php';
?>

