<?php
/** Styles globaux (navbar gradient Valorys + composants) — inclus dans head des pages publiques */
?>
<style>
    :root {
        --primary:#2A7FAA;--primary-dark:#1e5f80;--primary-light:#e0f0f5;
        --secondary:#4CAF50;--secondary-dark:#3d8b40;
        --text-dark:#1a3a6b;--bg-light:#f0f6ff;--border:#d0e4f7;
        --shadow:0 4px 12px rgba(42,127,170,0.15);--shadow-lg:0 10px 30px rgba(42,127,170,0.2);
        --dt-teal:#1b9a84;--dt-teal-dark:#15806e;--dt-teal-active-bg:rgba(255,255,255,0.16);
    }
    body { font-family:"Segoe UI",Tahoma,Geneva,Verdana,sans-serif;background:var(--bg-light);color:var(--text-dark); }
    .navbar-custom { background:linear-gradient(135deg,#2A7FAA 0%,#4CAF50 100%);box-shadow:var(--shadow);padding:0.8rem 2rem; }
    .navbar-custom .navbar-brand { font-size:1.5rem;font-weight:700; }
    .navbar-custom .nav-link { color: rgba(255,255,255,.92) !important; }
    .navbar-custom .nav-link:hover { color: #fff !important; opacity: .95; }
    .navbar-custom .nav-link.active { font-weight: 700; }
    .dropdown-menu { border:none;border-radius:12px;box-shadow:var(--shadow-lg); }
    .dropdown-item { padding:0.75rem 1rem;transition:all 0.2s; }
    .dropdown-item:hover { background:var(--primary-light);color:var(--primary); }
    .btn-primary { background:linear-gradient(135deg,var(--primary) 0%,var(--secondary) 100%);border:none;border-radius:10px;font-weight:500;padding:0.6rem 1.2rem;transition:all 0.3s; }
    .btn-primary:hover { transform:translateY(-2px);box-shadow:0 8px 16px rgba(42,127,170,0.3); }
    .card { border:1px solid var(--border);border-radius:15px;transition:all 0.3s; }
    .card:hover { transform:translateY(-5px);box-shadow:var(--shadow-lg); }
    .card-header { background:linear-gradient(135deg,var(--primary-light) 0%,rgba(76,175,80,0.1) 100%);border-bottom:2px solid var(--border); }
    .table thead th { background:linear-gradient(135deg,var(--primary) 0%,var(--secondary) 100%);color:white;border:none;padding:1rem; }
    .table tbody tr:hover { background:var(--bg-light); }
    .form-control { border:1px solid var(--border);border-radius:8px;padding:0.6rem 1rem;transition:all 0.3s; }
    .form-control:focus { border-color:var(--primary);box-shadow:0 0 0 0.2rem rgba(42,127,170,0.1); }
    .avatar { width:48px;height:48px;border-radius:50%;background:linear-gradient(135deg,var(--primary) 0%,var(--secondary) 100%);display:flex;align-items:center;justify-content:center;color:white;font-weight:bold;font-size:1.2rem; }
    /* DocTime (événements / sponsors / inscriptions) */
    .navbar-doctime { background:var(--dt-teal) !important;box-shadow:0 2px 12px rgba(0,105,92,0.25);padding:0.65rem 0; }
    .navbar-doctime .navbar-brand { font-size:1.35rem;font-weight:700;letter-spacing:.02em; }
    .navbar-doctime .navbar-nav.mx-auto .nav-item { display:flex;align-items:center; }
    .navbar-doctime .nav-link { color: rgba(255,255,255,.95) !important;padding:.4rem .65rem .5rem !important;border-radius:10px;transition:background .15s; }
    .navbar-doctime .nav-link:hover { color:#fff !important;background:rgba(255,255,255,.08); }
    .navbar-doctime .nav-link.active { font-weight:700;background:var(--dt-teal-active-bg);box-shadow:inset 0 -3px 0 rgba(255,255,255,.95); }
    .navbar-doctime .theme-toggle { --theme-toggle-bg: rgba(255,255,255,.95);--theme-toggle-text: #0f172a;--theme-toggle-border: rgba(255,255,255,.35);--theme-toggle-shadow: 0 4px 14px rgba(0,0,0,.12); }
    .navbar-doctime .theme-toggle:hover { filter:brightness(1.03); }
    body.page-doctime-bg { background:#eef2f3 !important; }
    .dt-page-head-title { font-size:1.85rem;font-weight:700;color:#1e293b; }
    .dt-page-head-sub { color:#64748b;font-size:1rem; }
    .dt-toolbar-row { display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:1rem;margin-bottom:1.25rem; }
    .dt-toolbar-filters { display:flex;flex-wrap:wrap;gap:.5rem;align-items:center; }
    .dt-evt-search-form .form-control { min-width:220px;max-width:320px;border-radius:10px 0 0 10px;border-color:#cbd5e1; }
    .dt-evt-search-form .form-control:focus { border-color:var(--dt-teal);box-shadow:0 0 0 .15rem rgba(27,154,132,.2); }
    .dt-inscription-banner { background:#dbeafe;border-radius:14px;padding:1rem 1.25rem;display:flex;align-items:center;gap:1rem;flex-wrap:wrap;border:1px solid #bfdbfe; }
    .dt-btn-teal { background:var(--dt-teal);color:#fff !important;border:none;border-radius:10px;font-weight:600;padding:.55rem 1.15rem;transition:filter .2s,transform .15s; }
    .dt-btn-teal:hover { filter:brightness(1.06);color:#fff !important; transform:translateY(-1px); }
    .dt-filter-pill { border-radius:999px;padding:.45rem 1.1rem;font-weight:500;border:1px solid #cbd5e1;background:#fff;color:#334155; }
    .dt-filter-pill.active { background:var(--dt-teal);border-color:var(--dt-teal);color:#fff; }
    .dt-card-event { background:#fff;border-radius:14px;overflow:hidden;box-shadow:0 4px 18px rgba(15,23,42,.08);border:1px solid #e2e8f0;height:100%;display:flex;flex-direction:column; }
    .dt-card-event:hover { box-shadow:0 8px 28px rgba(15,23,42,.12); }
    .dt-card-event .dt-img-wrap { position:relative;height:190px;background:linear-gradient(135deg,#e0f2fe,#f1f5f9);background-size:cover;background-position:center; }
    .dt-badge-status { position:absolute;top:12px;right:12px;font-size:.72rem;font-weight:600;padding:.25rem .6rem;border-radius:8px; }
    .dt-badge-planif { background:#22c55e;color:#fff; }
    .dt-badge-encours { background:#3b82f6;color:#fff; }
    .dt-badge-term { background:#64748b;color:#fff; }
    .dt-badge-annul { background:#ef4444;color:#fff; }
    .dt-cat-pill { display:inline-block;background:#e0f2fe;color:#0369a1;font-size:.78rem;font-weight:600;padding:.2rem .65rem;border-radius:999px;margin:.75rem 1rem 0; }
    .dt-event-body { padding:0 1.15rem 1.15rem;flex:1;display:flex;flex-direction:column; }
    .dt-event-title { font-size:1.2rem;font-weight:700;color:#0f172a;margin:.6rem 0 .5rem; }
    .dt-event-desc { color:#64748b;font-size:.9rem;line-height:1.45;margin-bottom:.75rem;display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden; }
    .dt-meta-row { display:flex;align-items:flex-start;gap:.5rem;font-size:.88rem;color:#64748b;margin-bottom:.4rem; }
    .dt-meta-row i { width:1.1rem;margin-top:.15rem;color:#94a3b8; }
    .dt-btn-detail { display:flex;align-items:center;justify-content:center;gap:.35rem;width:100%;margin-top:auto;padding:.65rem;font-weight:600;border-radius:10px; }
    .dt-sponsor-card { background:#fff;border-radius:14px;padding:1.25rem;box-shadow:0 4px 18px rgba(15,23,42,.08);border:1px solid #e2e8f0;height:100%; }
    .dt-sponsor-head { display:flex;align-items:flex-start;gap:.75rem;margin-bottom:1rem; }
    .dt-sponsor-ico { width:46px;height:46px;border-radius:50%;background:#dcfce7;color:#16a34a;display:flex;align-items:center;justify-content:center;flex-shrink:0; }
    .dt-sponsor-name { font-weight:700;color:#0f172a;font-size:1.05rem;margin:0;line-height:1.25; }
    .dt-level-badge { font-size:.72rem;font-weight:700;padding:.2rem .55rem;border-radius:8px;white-space:nowrap; }
    .dt-level-plat { background:#22d3ee;color:#0c4a6e; }
    .dt-level-or { background:#f39c12;color:#fff; }
    .dt-level-argent { background:#95a5a6;color:#fff; }
    .dt-level-bronze { background:#a0522d;color:#fff; }
    .dt-detail-hero { background:var(--dt-teal);color:#fff;padding:40px;margin-bottom:30px;border-radius:12px; }
    .dt-detail-hero a.back-link { color:#fff;text-decoration:none; }
    .dt-detail-accent { color:var(--dt-teal); }
    .dt-detail-cta { background:var(--dt-teal);color:#fff;border-radius:12px;padding:25px;text-align:center; }
    .dt-detail-cta .dt-register-btn { background:#fff;color:var(--dt-teal);border:none;border-radius:25px;padding:12px 35px;font-size:16px;font-weight:600;cursor:pointer;margin-top:15px; }
    .dt-register-btn { background:#fff;color:var(--dt-teal) !important;border:none;border-radius:12px;padding:14px 22px;font-size:1rem;font-weight:700;cursor:pointer;box-shadow:0 4px 16px rgba(27,154,132,.28);transition:transform .15s,filter .15s; }
    .dt-register-btn:hover:not(:disabled) { filter:brightness(1.04);transform:translateY(-1px); }
    .dt-register-btn:disabled { opacity:.7;cursor:not-allowed; }
</style>
