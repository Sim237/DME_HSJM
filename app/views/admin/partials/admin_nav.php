<?php
// Détermine la page active
$adminPage = $adminPage ?? '';
?>
<style>
.sidebar { display: none !important; }
main, .main-admin { margin-left: 0 !important; }
body { background: #f8fafc; font-family: 'Inter', system-ui, sans-serif; }

.admin-shell { display: flex; min-height: 100vh; }

.admin-sidenav {
    width: 240px; flex-shrink: 0;
    background: #0f172a;
    position: fixed; top: 0; left: 0; bottom: 0;
    display: flex; flex-direction: column;
    z-index: 100;
    overflow-y: auto;
}
.admin-sidenav-logo {
    padding: 20px 20px 16px;
    border-bottom: 1px solid rgba(255,255,255,0.06);
    display: flex; align-items: center; gap: 10px;
}
.admin-sidenav-logo img { height: 34px; }
.admin-sidenav-logo span { font-size: 0.8rem; font-weight: 700; color: #94a3b8; line-height: 1.2; }

.admin-nav-section { padding: 16px 12px 4px; font-size: 0.62rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1.5px; color: #475569; }

.admin-nav-item {
    display: flex; align-items: center; gap: 10px;
    padding: 10px 16px; margin: 2px 8px;
    border-radius: 10px; color: #94a3b8;
    text-decoration: none; font-size: 0.82rem; font-weight: 500;
    transition: all 0.15s;
}
.admin-nav-item:hover { background: rgba(255,255,255,0.06); color: #e2e8f0; }
.admin-nav-item.active { background: #1e40af; color: #fff; font-weight: 700; }
.admin-nav-item i { font-size: 1rem; width: 20px; text-align: center; }

.admin-nav-divider { margin: 8px 16px; border-color: rgba(255,255,255,0.06); }

.admin-main { flex: 1; margin-left: 240px; display: flex; flex-direction: column; min-height: 100vh; }

.admin-topbar {
    background: white; border-bottom: 1px solid #e2e8f0;
    padding: 16px 32px;
    display: flex; align-items: center; justify-content: space-between;
    position: sticky; top: 0; z-index: 50;
}
.admin-topbar-title { font-size: 1.1rem; font-weight: 800; color: #0f172a; }
.admin-topbar-title small { font-size: 0.72rem; font-weight: 500; color: #94a3b8; display: block; margin-top: 1px; }

.admin-page-content { flex: 1; padding: 28px 32px; }

/* Toasts */
.toast-container { position: fixed; bottom: 24px; right: 24px; z-index: 9999; }
</style>

<div class="admin-shell">

    <!-- SIDENAV -->
    <nav class="admin-sidenav">
        <div class="admin-sidenav-logo">
            <img src="<?= BASE_URL ?>public/images/logo_ordre_malte.png" alt="Logo">
            <span>Administration<br>HSJM</span>
        </div>

        <div class="admin-nav-section">Tableau de bord</div>
        <a href="<?= BASE_URL ?>dashboard" class="admin-nav-item <?= $adminPage === 'dashboard' ? 'active' : '' ?>">
            <i class="bi bi-speedometer2"></i> Vue d'ensemble
        </a>
        <a href="<?= BASE_URL ?>utilisateurs" class="admin-nav-item <?= $adminPage === 'utilisateurs' ? 'active' : '' ?>">
            <i class="bi bi-people-fill"></i> Utilisateurs
        </a>

        <hr class="admin-nav-divider">
        <div class="admin-nav-section">Infrastructure</div>
        <a href="<?= BASE_URL ?>admin/services" class="admin-nav-item <?= $adminPage === 'services' ? 'active' : '' ?>">
            <i class="bi bi-building"></i> Services
        </a>
        <a href="<?= BASE_URL ?>admin/lits" class="admin-nav-item <?= $adminPage === 'lits' ? 'active' : '' ?>">
            <i class="bi bi-grid-3x3-gap"></i> Chambres & Lits
        </a>

        <hr class="admin-nav-divider">
        <div class="admin-nav-section">Médico-technique</div>
        <a href="<?= BASE_URL ?>admin/pharmacie" class="admin-nav-item <?= $adminPage === 'pharmacie' ? 'active' : '' ?>">
            <i class="bi bi-capsule-pill"></i> Pharmacie & Stock
        </a>
        <a href="<?= BASE_URL ?>admin/laboratoire" class="admin-nav-item <?= $adminPage === 'laboratoire' ? 'active' : '' ?>">
            <i class="bi bi-flask-fill"></i> Laboratoire
        </a>

        <hr class="admin-nav-divider">
        <div class="admin-nav-section">Sécurité</div>
        <a href="<?= BASE_URL ?>admin/permissions" class="admin-nav-item <?= $adminPage === 'permissions' ? 'active' : '' ?>">
            <i class="bi bi-shield-lock-fill"></i> Rôles & Permissions
        </a>

        <hr class="admin-nav-divider">
        <div class="admin-nav-section">Système</div>
        <a href="<?= BASE_URL ?>admin/logs" class="admin-nav-item <?= $adminPage === 'logs' ? 'active' : '' ?>">
            <i class="bi bi-journal-text"></i> Journaux d'Audit
        </a>

        <div class="mt-auto p-3">
            <a href="<?= BASE_URL ?>logout" class="btn btn-sm btn-outline-danger w-100 rounded-pill">
                <i class="bi bi-power me-1"></i> Déconnexion
            </a>
            <div class="text-center mt-2" style="font-size:0.62rem; color:#334155;">
                <?= htmlspecialchars($_SESSION['user_nom'] ?? '') ?><br>
                <?= date('d/m/Y') ?>
            </div>
        </div>
    </nav>

    <!-- MAIN -->
    <div class="admin-main">
