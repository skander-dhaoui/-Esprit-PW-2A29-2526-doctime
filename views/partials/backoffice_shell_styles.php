<?php
/** Styles shell admin — une seule charte (sidebar + zone principale). */
if (defined('BO_BACKOFFICE_SHELL_STYLES')) {
    return;
}
define('BO_BACKOFFICE_SHELL_STYLES', true);
?>
<style id="bo-shell-styles">
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body.bo-shell-body {
        background: #f4f6f9;
        font-family: 'Segoe UI', system-ui, sans-serif;
        min-height: 100vh;
    }
    .bo-admin-wrap {
        display: flex;
        min-height: 100vh;
    }
    .sidebar {
        width: 260px;
        height: 100vh;
        max-height: 100vh;
        min-height: 0;
        background: #1e2235;
        color: #f1f5f9;
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
        scrollbar-color: rgba(255, 255, 255, 0.28) transparent;
        border-right: 1px solid rgba(255, 255, 255, 0.06);
        box-shadow: 4px 0 24px rgba(0, 0, 0, 0.18);
    }
    .sidebar::-webkit-scrollbar {
        width: 8px;
    }
    .sidebar::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.28);
        border-radius: 4px;
    }
    .sidebar::-webkit-scrollbar-track {
        background: transparent;
    }
    .sidebar-brand {
        padding: 22px 18px;
        text-align: center;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        flex-shrink: 0;
        position: sticky;
        top: 0;
        z-index: 3;
        background: #1e2235;
    }
    .brand-icon {
        width: 52px;
        height: 52px;
        background: #131620;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 10px;
        font-size: 22px;
        color: #22c55e;
        box-shadow: inset 0 0 0 1px rgba(34, 197, 94, 0.35), 0 4px 14px rgba(0, 0, 0, 0.25);
    }
    .sidebar-brand h4 {
        font-size: 17px;
        font-weight: 700;
        margin: 0;
        color: #ffffff;
    }
    .sidebar-brand small {
        color: #94a3b8;
        font-size: 11px;
    }
    .sidebar-nav {
        padding: 14px 0 28px;
        flex: 1 1 auto;
    }
    .sidebar-nav a {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 11px 17px 11px 20px;
        color: rgba(248, 250, 252, 0.9);
        text-decoration: none;
        font-size: 14px;
        font-weight: 500;
        transition: background .2s, color .2s;
        border-left: 3px solid transparent;
        border-radius: 0 10px 10px 0;
        margin-right: 10px;
    }
    .sidebar-nav a:hover {
        background: rgba(255, 255, 255, 0.06);
        background-image: none;
        color: #ffffff;
    }
    .sidebar-nav a.active {
        background: rgba(34, 197, 94, 0.14);
        background-image: none;
        color: #ffffff;
        border-left-color: #22c55e;
        font-weight: 600;
    }
    .sidebar-nav a i {
        width: 22px;
        text-align: center;
        font-size: 15px;
    }
    .nav-divider {
        height: 1px;
        background: rgba(255, 255, 255, 0.07);
        margin: 10px 18px;
    }
    .nav-section-label {
        padding: 14px 20px 6px;
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: .12em;
        color: #64748b;
        font-weight: 700;
    }
    .main-content {
        margin-left: 260px;
        flex: 1;
        padding: 24px 26px;
        min-height: 100vh;
        width: calc(100% - 260px);
    }
    .main {
        margin-left: 260px;
        padding: 24px 28px;
        min-height: 100vh;
    }
    .page-header {
        background: #fff;
        border-radius: 12px;
        padding: 16px 22px;
        margin-bottom: 22px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        box-shadow: 0 1px 6px rgba(0,0,0,0.06);
    }
    .page-header h4 {
        font-size: 17px;
        font-weight: 700;
        color: #1a2035;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .page-header h4 i { color: #4CAF50; }
    .content-card {
        background: #fff;
        border-radius: 12px;
        padding: 22px;
        box-shadow: 0 1px 6px rgba(0,0,0,0.06);
    }
    .admin-avatar {
        width: 40px;
        height: 40px;
        background: #4CAF50;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 15px;
        font-weight: 700;
        text-decoration: none;
    }
    @media (max-width: 991px) {
        .sidebar { width: 72px; }
        .sidebar-brand h4, .sidebar-brand small, .sidebar-nav a span, .nav-section-label { display: none; }
        .sidebar-nav a { justify-content: center; padding: 12px; }
        .sidebar-nav a i { margin: 0; }
        .main-content, .main { margin-left: 72px; width: calc(100% - 72px); }
    }
</style>
