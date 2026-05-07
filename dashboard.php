<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header('Location: connexion.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - DOCtime</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#">DOCtime</a>
            <div class="text-white">
                Bienvenue, <?= htmlspecialchars($_SESSION['user_name']) ?> (<?= $_SESSION['user_role'] ?>)
                <a href="logout.php" class="btn btn-danger btn-sm ms-3">Déconnexion</a>
            </div>
        </div>
    </nav>
    <div class="container mt-4">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h4 class="mb-0">Tableau de bord</h4>
            </div>
            <div class="card-body">
                <p>Vous êtes connecté avec succès !</p>
                <p>Votre rôle : <strong><?= $_SESSION['user_role'] ?></strong></p>
                <p>ID utilisateur : <?= $_SESSION['user_id'] ?></p>
            </div>
        </div>
    </div>
</body>
</html>