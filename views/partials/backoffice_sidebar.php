<?php
// Navigation admin unique — liens relatifs à la racine du site (index.php).
if (!function_exists('bo_nav_active')) {
    /**
     * @param string[] $pages Valeurs de $_GET['page'] qui activent le lien
     */
    function bo_nav_active(array $pages): string {
        $p = $_GET['page'] ?? '';
        return in_array($p, $pages, true) ? ' active' : '';
    }
}
if (!function_exists('bo_nav_carte')) {
    /** Carte : page=carte sans action */
    function bo_nav_carte(bool $metiers): string {
        $p = $_GET['page'] ?? '';
        $a = $_GET['action'] ?? '';
        if ($p !== 'carte') {
            return '';
        }
        if ($metiers) {
            return ($a === 'metiers') ? ' active' : '';
        }
        return ($a === '' || $a === 'index') ? ' active' : '';
    }
}
?>
<div class="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon"><i class="fas fa-heart-pulse"></i></div>
        <h4>Valorys</h4>
        <small>Administration</small>
    </div>
    <nav class="sidebar-nav">
        <a class="<?= trim(bo_nav_active(['dashboard'])) ?>" href="index.php?page=dashboard"><i class="fas fa-th-large"></i><span>Tableau de bord</span></a>

        <div class="nav-section-label">Gestion</div>
        <a class="<?= trim(bo_nav_active(['users'])) ?>" href="index.php?page=users"><i class="fas fa-users"></i><span>Utilisateurs</span></a>
        <a class="<?= trim(bo_nav_active(['medecins_admin'])) ?>" href="index.php?page=medecins_admin"><i class="fas fa-user-md"></i><span>Médecins</span></a>
        <a class="<?= trim(bo_nav_active(['patients'])) ?>" href="index.php?page=patients"><i class="fas fa-user-injured"></i><span>Patients</span></a>
        <a class="<?= trim(bo_nav_active(['rendez_vous_admin', 'admin_rendezvous'])) ?>" href="index.php?page=rendez_vous_admin"><i class="fas fa-calendar-check"></i><span>Rendez-vous</span></a>
        <a class="<?= trim(bo_nav_active(['disponibilites_admin'])) ?>" href="index.php?page=disponibilites_admin"><i class="fas fa-clock"></i><span>Disponibilités</span></a>
        <a class="<?= trim(bo_nav_active(['ordonnances', 'ordonnance'])) ?>" href="index.php?page=ordonnances"><i class="fas fa-prescription-bottle"></i><span>Ordonnances</span></a>

        <div class="nav-section-label">Parapharmacie</div>
        <a class="<?= trim(bo_nav_active(['produits_admin'])) ?>" href="index.php?page=produits_admin"><i class="fas fa-pills"></i><span>Produits</span></a>
        <a class="<?= trim(bo_nav_active(['categories_admin'])) ?>" href="index.php?page=categories_admin"><i class="fas fa-tags"></i><span>Catégories</span></a>
        <a class="<?= trim(bo_nav_active(['commandes_admin'])) ?>" href="index.php?page=commandes_admin"><i class="fas fa-shopping-cart"></i><span>Commandes</span></a>

        <div class="nav-section-label">Contenu</div>
        <a class="<?= trim(bo_nav_active(['blog'])) ?>" href="index.php?page=blog"><i class="fas fa-newspaper"></i><span>Articles</span></a>
        <a class="<?= trim(bo_nav_active(['evenements_admin'])) ?>" href="index.php?page=evenements_admin"><i class="fas fa-calendar-day"></i><span>Événements</span></a>
        <a class="<?= trim(bo_nav_active(['sponsors_admin'])) ?>" href="index.php?page=sponsors_admin"><i class="fas fa-handshake"></i><span>Sponsors</span></a>
        <a class="<?= trim(bo_nav_active(['participations'])) ?>" href="index.php?page=participations"><i class="fas fa-users-line"></i><span>Participations</span></a>

        <div class="nav-section-label">Carte &amp; IA</div>
        <a class="<?= trim(bo_nav_carte(false)) ?>" href="index.php?page=carte"><i class="fas fa-map-marked-alt"></i><span>Carte Tunisie</span></a>
        <a class="<?= trim(bo_nav_carte(true)) ?>" href="index.php?page=carte&action=metiers"><i class="fas fa-brain"></i><span>IA métiers</span></a>

        <div class="nav-divider"></div>
        <a class="<?= trim(bo_nav_active(['stats'])) ?>" href="index.php?page=stats"><i class="fas fa-chart-line"></i><span>Statistiques</span></a>
        <a class="<?= trim(bo_nav_active(['logs'])) ?>" href="index.php?page=logs"><i class="fas fa-history"></i><span>Historique / Logs</span></a>
        <a class="<?= trim(bo_nav_active(['settings'])) ?>" href="index.php?page=settings"><i class="fas fa-cog"></i><span>Paramètres</span></a>

        <div class="nav-divider"></div>
        <a href="index.php?page=accueil" target="_blank" rel="noopener"><i class="fas fa-globe"></i><span>Voir le site</span></a>
        <a href="index.php?page=logout"><i class="fas fa-sign-out-alt"></i><span>Déconnexion</span></a>
    </nav>
</div>
