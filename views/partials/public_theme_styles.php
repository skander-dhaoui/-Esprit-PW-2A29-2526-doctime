<?php
/** Styles globaux (navbar gradient Valorys + composants) — inclus dans head des pages publiques */
?>
<style>
    :root {
        --primary:#2A7FAA;--primary-dark:#1e5f80;--primary-light:#e0f0f5;
        --secondary:#4CAF50;--secondary-dark:#3d8b40;
        --text-dark:#1a3a6b;--bg-light:#f0f6ff;--border:#d0e4f7;
        --shadow:0 4px 12px rgba(42,127,170,0.15);--shadow-lg:0 10px 30px rgba(42,127,170,0.2);
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
</style>
