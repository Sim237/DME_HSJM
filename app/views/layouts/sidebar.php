<?php
$userRole = $_SESSION['user_role'] ?? '';
$serviceId = $_SESSION['service_id'] ?? 0;
$userName = $_SESSION['user_nom'] ?? 'Utilisateur';

if (!function_exists('isActive')) {
    function isActive($path) {
        $current = $_SERVER['REQUEST_URI'];
        // Retourne la classe active si le chemin est trouvé dans l'URL
        return (strpos($current, $path) !== false || ($path == 'dashboard' && $current == BASE_URL)) ? 'active' : '';
    }
}

// Détection du service Anesthésie (ID 6 ou 11 selon votre base)
$isAnesthesie = ($serviceId == 6 || $serviceId == 11 || $userRole == 'ADMIN');
?>

<?php
// On récupère l'URL actuelle
$current_url = $_SERVER['REQUEST_URI'];

// Si l'URL contient "consultation/formulaire", on ne charge PAS la sidebar
if (strpos($current_url, 'consultation/formulaire') !== false) {
    return; // Arrête l'exécution du fichier ici
}
?>

<?php
$userRole = $_SESSION['user_role'] ?? '';
$nomService = strtolower($_SESSION['nom_service'] ?? '');
$isUrgence = (strpos($nomService, 'urgences') !== false);

// Si c'est un infirmier ou le service des urgences, on ne rend rien
if ($userRole === 'INFIRMIER' || $isUrgence) {
    return;
}?>

<style>
    /* Structure de la Sidebar */
    .sidebar {
        min-width: 260px;
        max-width: 260px;
        background-color: #1a1d23;
        min-height: 100vh;
        color: white;
        display: flex;
        flex-direction: column;
        box-shadow: 4px 0 10px rgba(0,0,0,0.2);
    }

    /* Profil Utilisateur */
    .sidebar-header-modern {
        padding: 20px;
        background: rgba(0,0,0,0.2);
        border-bottom: 1px solid rgba(255,255,255,0.05);
    }
    .app-logo { display: flex; align-items: center; gap: 10px; margin-bottom: 15px; }
    .app-logo i { font-size: 1.5rem; color: #3b82f6; }
    .app-logo h4 { margin: 0; font-size: 1.1rem; font-weight: 800; letter-spacing: 1px; }

    .user-profile-card {
        display: flex; align-items: center; gap: 12px;
        background: rgba(255,255,255,0.05);
        padding: 10px; border-radius: 12px;
    }
    .user-avatar {
        width: 35px; height: 35px;
        background: #3b82f6; border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        font-weight: bold; font-size: 0.9rem;
    }
    .user-info p { margin: 0; font-size: 0.85rem; font-weight: 600; }
    .user-info .user-role { font-size: 0.7rem; padding: 2px 8px; border-radius: 4px; }

    /* Titres de sections */
    .nav-section { margin-bottom: 15px; }
    .nav-title {
        padding: 15px 20px 5px;
        font-size: 0.65rem;
        text-transform: uppercase;
        color: #6b7280;
        font-weight: 800;
        letter-spacing: 1.2px;
    }

    /* Liens de navigation */
    .nav-link-custom {
        display: flex;
        align-items: center;
        padding: 10px 20px;
        color: #d1d5db;
        text-decoration: none !important;
        font-size: 0.9rem;
        transition: 0.2s;
        border-left: 4px solid transparent;
    }
    .nav-link-custom:hover {
        background: rgba(255,255,255,0.05);
        color: white;
    }
    .nav-link-custom.active {
        background: rgba(59, 130, 246, 0.1);
        color: #60a5fa;
        border-left-color: #3b82f6;
        font-weight: 600;
    }
    .nav-link-custom i { width: 25px; font-size: 1.1rem; }

    /* Couleurs des icônes */
    .text-warning { color: #facc15 !important; }
    .text-danger { color: #f87171 !important; }
    .text-pink { color: #f472b6 !important; }
    .text-cyan { color: #22d3ee !important; }
    .text-magenta { color: #d946ef !important; }
    .text-info { color: #3b82f6 !important; }
    .text-success { color: #22c55e !important; }

    .mt-auto { margin-top: auto; }
</style>

<div class="sidebar">
    <div class="sidebar-header-modern">
        <div class="app-logo">
            <i class="bi bi-hospital"></i>
            <h4>Hôpital DME</h4>
        </div>
        <div class="user-profile-card border border-secondary border-opacity-25">
            <div class="user-avatar"><?= strtoupper(substr($userName, 0, 2)) ?></div>
            <div class="user-info">
                <p class="user-name text-truncate" style="max-width: 150px;"><?= htmlspecialchars($userName) ?></p>
                <span class="user-role bg-primary text-white"><?= $userRole ?></span>
            </div>
        </div>
    </div>

    <div class="nav-container" style="overflow-y: auto;">
        <!-- SECTION PRINCIPALE -->
        <div class="nav-section">
            <div class="nav-title">Principal</div>
            <a href="<?= BASE_URL ?>" class="nav-link-custom <?= isActive('dashboard') ?>">
                <i class="bi bi-grid-1x2-fill me-2 text-info"></i> Dashboard
            </a>
        </div>

        <!-- SECTION PATIENTS -->
        <div class="nav-section">
            <div class="nav-title">Patients</div>
            <a href="<?= BASE_URL ?>patients" class="nav-link-custom <?= isActive('patients') ?>">
                <i class="bi bi-people-fill text-info me-2"></i> Patients
            </a>
            <a href="<?= BASE_URL ?>consultation" class="nav-link-custom <?= isActive('consultation') ?>">
                <i class="bi bi-stethoscope text-success me-2"></i> Consultations
            </a>
        </div>

        <!-- SECTION MON SERVICE -->
        <div class="nav-section">
            <div class="nav-title">Mon Service</div>

            <?php if ($userRole === 'ADMIN' || $userRole === 'LABORANTIN'): ?>
                <a href="<?= BASE_URL ?>laboratoire" class="nav-link-custom <?= isActive('laboratoire') ?>">
                    <i class="bi bi-flask-fill text-warning me-2"></i> Laboratoire
                </a>
                <a href="<?= BASE_URL ?>banque-sang" class="nav-link-custom <?= isActive('banque-sang') ?>">
                    <i class="bi bi-droplet-fill text-danger me-2"></i> Banque de Sang
                </a>
            <?php endif; ?>

            <?php if ($userRole === 'ADMIN' || $userRole === 'PHARMACIEN'): ?>
                <a href="<?= BASE_URL ?>pharmacie" class="nav-link-custom <?= isActive('pharmacie') ?>">
                    <i class="bi bi-capsule text-pink me-2"></i> Pharmacie
                </a>
            <?php endif; ?>

            <?php if ($isAnesthesie): ?>
                <a href="<?= BASE_URL ?>anesthesie" class="nav-link-custom <?= isActive('anesthesie') ?>">
                    <i class="bi bi-mask text-cyan me-2"></i> Anesthésie
                </a>
                <a href="<?= BASE_URL ?>bloc" class="nav-link-custom <?= isActive('bloc') ?>">
                    <i class="bi bi-scissors text-magenta me-2"></i> Bloc Opératoire
                </a>
            <?php endif; ?>
        </div>

        <div class="nav-section">
    <div class="nav-title">Hospitalisation</div>
    <a href="<?= BASE_URL ?>lits" class="nav-link <?= isActive('lits') ?>">
        <i class="fas fa-bed"></i>
        Gestion des Lits
    </a>
</div>

        <!-- SECTION COMMUNICATION -->
        <div class="nav-section">
            <div class="nav-title">Communication</div>
            <a href="<?= BASE_URL ?>telemedecine" class="nav-link-custom <?= isActive('telemedecine') ?>">
                <i class="bi bi-camera-video-fill me-2" style="color: #fca5a5;"></i> Télémédecine
            </a>
            <a href="<?= BASE_URL ?>modules/chat" class="nav-link-custom <?= isActive('chat') ?>">
                <i class="bi bi-chat-dots-fill me-2" style="color: #38bdf8;"></i> Chat Médical
            </a>
            <a href="<?= BASE_URL ?>modules/famille" class="nav-link-custom <?= isActive('famille') ?>">
                <i class="bi bi-people-fill me-2" style="color: #a78bfa;"></i> Famille
            </a>
        </div>

        <!-- GESTION -->
        <?php if ($userRole === 'ADMIN'): ?>
        <div class="nav-section">
            <div class="nav-title">Gestion</div>
            <a href="<?= BASE_URL ?>utilisateurs" class="nav-link-custom <?= isActive('utilisateurs') ?>">
                <i class="bi bi-person-gear me-2"></i> Personnel
            </a>
            <a href="<?= BASE_URL ?>statistiques" class="nav-link-custom <?= isActive('statistiques') ?>">
                <i class="bi bi-bar-chart-line me-2"></i> Statistiques
            </a>
        </div>
        <?php endif; ?>
    </div>

    <div class="nav-section mt-auto border-top border-secondary border-opacity-10">
        <a href="<?= BASE_URL ?>logout" class="nav-link-custom text-danger">
            <i class="bi bi-box-arrow-left"></i> <span>Déconnexion</span>
        </a>
    </div>
</div>