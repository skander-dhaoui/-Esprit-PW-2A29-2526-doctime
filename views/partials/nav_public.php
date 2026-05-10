<?php
declare(strict_types=1);
/**
 * Barre de navigation publique unique — inclure après <body> (ou dans layout).
 * Variables optionnelles avant include :
 *   $navActive — valeur de $_GET['page'] pour surligner l’entrée active
 */
$navActive = $navActive ?? ($_GET['page'] ?? '');
$isLoggedIn = !empty($_SESSION['user_id']);
$userName   = htmlspecialchars($_SESSION['user_name'] ?? 'Compte', ENT_QUOTES, 'UTF-8');
$userRole   = $_SESSION['user_role'] ?? 'guest';

/** @param array<int,string>|string $pages */
function nav_pub_active(string $navActive, array|string $pages): string {
    $list = is_array($pages) ? $pages : [$pages];
    return in_array($navActive, $list, true) ? ' active' : '';
}
?>
<nav class="navbar navbar-expand-lg navbar-dark navbar-custom sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php?page=accueil"><i class="fas fa-hospital-user"></i> Valorys</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavMain" aria-controls="navbarNavMain" aria-expanded="false" aria-label="Menu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNavMain">
            <ul class="navbar-nav mx-auto">
                <li class="nav-item">
                    <a class="nav-link<?= nav_pub_active($navActive, 'accueil') ?>" href="index.php?page=accueil"><i class="fas fa-home me-1"></i>Accueil</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link<?= nav_pub_active($navActive, 'medecins') ?>" href="index.php?page=medecins"><i class="fas fa-user-md me-1"></i>Médecins</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link<?= nav_pub_active($navActive, ['blog_public', 'detail_article_public']) ?>" href="index.php?page=blog_public"><i class="fas fa-blog me-1"></i>Blog</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link<?= nav_pub_active($navActive, ['evenements', 'detail_evenement', 'evenement_form']) ?>" href="index.php?page=evenements"><i class="fas fa-calendar-alt me-1"></i>Événements</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link<?= nav_pub_active($navActive, 'sponsors') ?>" href="index.php?page=sponsors"><i class="fas fa-handshake me-1"></i>Sponsors</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link<?= nav_pub_active($navActive, ['parapharmacie', 'pharmacie', 'panier', 'mes_commandes']) ?>" href="index.php?page=parapharmacie"><i class="fas fa-pills me-1"></i>Parapharmacie</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link<?= nav_pub_active($navActive, 'contact') ?>" href="index.php?page=contact"><i class="fas fa-envelope me-1"></i>Contact</a>
                </li>
            </ul>
            <ul class="navbar-nav ms-auto">
                <?php if ($isLoggedIn): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                        <span class="avatar me-2" style="width:32px;height:32px;font-size:0.9rem;"><?= strtoupper(substr($userName, 0, 1)) ?></span><?= $userName ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item<?= nav_pub_active($navActive, ['mon_profil', 'profil']) ?>" href="index.php?page=mon_profil"><i class="fas fa-user me-2"></i>Mon profil</a></li>
                        <li><a class="dropdown-item<?= nav_pub_active($navActive, 'modifier_profil') ?>" href="index.php?page=modifier_profil"><i class="fas fa-edit me-2"></i>Modifier le profil</a></li>
                        <li><a class="dropdown-item<?= nav_pub_active($navActive, ['mes_rendez_vous', 'mes_rendezvous', 'prendre_rendezvous', 'prendre_rendez_vous', 'modifier_rendezvous', 'modifier_rendez_vous', 'medecin_disponibilites', 'patient_disponibilites', 'disponibilite', 'disponibilites', 'detail_rendez_vous', 'creer_rendezvous']) ?>" href="index.php?page=mes_rendez_vous"><i class="fas fa-calendar me-2"></i>Mes rendez-vous</a></li>
                        <li><a class="dropdown-item<?= nav_pub_active($navActive, ['mes_ordonnances', 'ordonnance', 'ordonnances', 'creer_ordonnance_rdv', 'modifier_ordonnance_rdv']) ?>" href="index.php?page=mes_ordonnances"><i class="fas fa-prescription me-2"></i>Mes ordonnances</a></li>
                        <li><a class="dropdown-item<?= nav_pub_active($navActive, 'mes_commandes') ?>" href="index.php?page=mes_commandes"><i class="fas fa-shopping-bag me-2"></i>Mes commandes</a></li>
                        <li><a class="dropdown-item<?= nav_pub_active($navActive, 'panier') ?>" href="index.php?page=panier"><i class="fas fa-cart-shopping me-2"></i>Mon panier</a></li>
                        <?php if ($userRole === 'admin'): ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="index.php?page=dashboard"><i class="fas fa-cog me-2"></i>Administration</a></li>
                        <?php endif; ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="index.php?page=logout"><i class="fas fa-sign-out-alt me-2"></i>Déconnexion</a></li>
                    </ul>
                </li>
                <?php else: ?>
                <li class="nav-item"><a class="nav-link" href="index.php?page=login"><i class="fas fa-sign-in-alt me-1"></i>Connexion</a></li>
                <li class="nav-item"><a class="nav-link btn btn-light ms-2" href="index.php?page=register"><i class="fas fa-user-plus me-1"></i>Inscription</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
