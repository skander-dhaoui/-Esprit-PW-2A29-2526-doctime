<?php
// views/backoffice/sidebar.php
$current_page   = $_GET['page'] ?? 'dashboard';
$current_action = $_GET['action'] ?? '';
$carteMetiersActive = ($current_page === 'carte' && $current_action === 'metiers');
$carteOnlyActive    = ($current_page === 'carte' && $current_action !== 'metiers');
?>
<div class="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon"><i class="fas fa-stethoscope"></i></div>
        <h4>MediConnect</h4>
        <small>Back Office</small>
    </div>
    <nav class="sidebar-nav">
        <a href="index.php?page=dashboard" class="<?= $current_page === 'dashboard' ? 'active' : '' ?>">
            <i class="fas fa-th-large"></i> Tableau de bord
        </a>

        <div class="nav-section-label">Carte, métiers &amp; événements</div>
        <a href="index.php?page=carte" class="<?= $carteOnlyActive ? 'active' : '' ?>">
            <i class="fas fa-map-marked-alt"></i> Carte Tunisie
        </a>
        <a href="index.php?page=carte&action=metiers" class="<?= $carteMetiersActive ? 'active' : '' ?>">
            <i class="fas fa-robot"></i> IA Métiers créatifs
        </a>
        <a href="index.php?page=evenements_admin" class="<?= $current_page === 'evenements_admin' ? 'active' : '' ?>">
            <i class="fas fa-calendar-day"></i> Événements
        </a>
        <a href="index.php?page=sponsors_admin" class="<?= $current_page === 'sponsors_admin' ? 'active' : '' ?>">
            <i class="fas fa-handshake"></i> Sponsors
        </a>
        <a href="index.php?page=participations" class="<?= $current_page === 'participations' ? 'active' : '' ?>">
            <i class="fas fa-users-line"></i> Participations
        </a>

        <div class="nav-section-label">Gestion</div>
        <a href="index.php?page=users" class="<?= $current_page === 'users' ? 'active' : '' ?>">
            <i class="fas fa-users"></i> Utilisateurs
        </a>
        <a href="index.php?page=medecins_admin" class="<?= $current_page === 'medecins_admin' ? 'active' : '' ?>">
            <i class="fas fa-user-md"></i> Médecins
        </a>
        <a href="index.php?page=patients" class="<?= $current_page === 'patients' ? 'active' : '' ?>">
            <i class="fas fa-user-injured"></i> Patients
        </a>

        <div class="nav-section-label">Gestion blog</div>
        <a href="index.php?page=articles_admin" class="<?= ($current_page === 'articles_admin' || $current_page === 'blog') ? 'active' : '' ?>">
            <i class="fas fa-blog"></i> Blog &amp; articles
        </a>

        <div class="nav-section-label">Gestion rendez-vous</div>
        <a href="index.php?page=rendez_vous_admin" class="<?= $current_page === 'rendez_vous_admin' ? 'active' : '' ?>">
            <i class="fas fa-calendar-check"></i> Rendez-vous
        </a>
        <a href="index.php?page=ordonnances" class="<?= $current_page === 'ordonnances' ? 'active' : '' ?>">
            <i class="fas fa-prescription-bottle"></i> Ordonnances
        </a>

        <div class="nav-section-label">Gestion parapharmacie</div>
        <a href="index.php?page=produits_admin" class="<?= $current_page === 'produits_admin' ? 'active' : '' ?>">
            <i class="fas fa-box"></i> Produits
        </a>
        <a href="index.php?page=categories_admin" class="<?= $current_page === 'categories_admin' ? 'active' : '' ?>">
            <i class="fas fa-tags"></i> Catégories
        </a>
        <div class="nav-divider"></div>
        <a href="index.php?page=stats" class="<?= $current_page === 'stats' ? 'active' : '' ?>">
            <i class="fas fa-chart-line"></i> Statistiques
        </a>
        <a href="index.php?page=evenements_admin&action=advanced" class="<?= ($current_page === 'evenements_admin' && ($_GET['action'] ?? '') === 'advanced') ? 'active' : '' ?>">
            <i class="fas fa-chart-bar"></i> Événements Avancé
        </a>
        <a href="index.php?page=logs" class="<?= $current_page === 'logs' ? 'active' : '' ?>">
            <i class="fas fa-history"></i> Historique
        </a>
        <a href="index.php?page=settings" class="<?= $current_page === 'settings' ? 'active' : '' ?>">
            <i class="fas fa-cog"></i> Paramètres
        </a>
        <div class="nav-divider"></div>
        <a href="index.php?page=logout">
            <i class="fas fa-sign-out-alt"></i> Déconnexion
        </a>
    </nav>
</div>

<style>
.sidebar {
    width: 260px;
    height: 100vh;
    max-height: 100vh;
    background: #1a2035;
    color: white;
    display: flex;
    flex-direction: column;
    position: fixed;
    top: 0;
    left: 0;
    z-index: 100;
    overflow-y: auto;
    overflow-x: hidden;
    overscroll-behavior: contain;
    scrollbar-width: thin;
    scrollbar-color: rgba(255,255,255,0.28) transparent;
}

.sidebar-brand {
    padding: 25px 20px;
    text-align: center;
    border-bottom: 1px solid rgba(255,255,255,0.08);
    flex-shrink: 0;
    position: sticky;
    top: 0;
    z-index: 2;
    background: #1a2035;
}

.brand-icon {
    width: 55px;
    height: 55px;
    background: rgba(255,255,255,0.1);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 12px;
    font-size: 24px;
    color: #4CAF50;
}

.sidebar-brand h4 {
    font-size: 18px;
    font-weight: 700;
    margin: 0;
    color: white;
}

.sidebar-brand small {
    color: rgba(255,255,255,0.5);
    font-size: 11px;
}

.sidebar-nav {
    padding: 20px 0 28px;
    flex: 1 1 auto;
}

.sidebar::-webkit-scrollbar {
    width: 8px;
}

.sidebar::-webkit-scrollbar-thumb {
    background: rgba(255,255,255,0.28);
    border-radius: 4px;
}

.sidebar::-webkit-scrollbar-track {
    background: transparent;
}

.sidebar-nav a {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 22px;
    color: rgba(255,255,255,0.7);
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.2s;
    border-left: 3px solid transparent;
}

.sidebar-nav a:hover {
    background: rgba(255,255,255,0.07);
    color: white;
}

.sidebar-nav a.active {
    background: rgba(255,255,255,0.1);
    color: white;
    border-left-color: #4CAF50;
}

.sidebar-nav a i {
    width: 20px;
    text-align: center;
    font-size: 16px;
}

.nav-divider {
    height: 1px;
    background: rgba(255,255,255,0.07);
    margin: 10px 22px;
}

.nav-section-label {
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: rgba(255,255,255,0.38);
    padding: 14px 22px 6px;
    margin: 0;
}

.main-content {
    margin-left: 260px;
    flex: 1;
    padding: 25px;
    min-height: 100vh;
}

.page-header {
    background: white;
    border-radius: 12px;
    padding: 18px 25px;
    margin-bottom: 25px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    box-shadow: 0 1px 6px rgba(0,0,0,0.06);
}

.page-header h4 {
    font-size: 18px;
    font-weight: 700;
    color: #1a2035;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.page-header h4 i {
    color: #4CAF50;
}

.admin-avatar {
    width: 40px;
    height: 40px;
    background: #4CAF50;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 16px;
    font-weight: bold;
    text-decoration: none;
}

.admin-avatar:hover {
    background: #2A7FAA;
    color: white;
}

.content-card {
    background: white;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 1px 6px rgba(0,0,0,0.06);
}

.card-title-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 20px;
}

.card-title-row h5 {
    font-size: 16px;
    font-weight: 600;
    color: #1a2035;
    margin: 0;
}

.flash-box {
    border-radius: 10px;
    padding: 12px 16px;
    margin-bottom: 20px;
    font-size: 14px;
}

.flash-success {
    background: #e8f5e9;
    color: #2e7d32;
}

.flash-error {
    background: #fdecea;
    color: #c62828;
}
</style>