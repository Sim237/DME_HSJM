<?php
/* ============================================================================
FICHIER : app/views/consultations/formulaire/progress_bar.php
Barre de progression réutilisable pour les étapes de consultation
============================================================================ */

// 1. Sécurisation des données : On s'assure que les variables existent
$patient = $patient ?? [];
$nom_complet = ($patient['prenom'] ?? '') . ' ' . ($patient['nom'] ?? 'Patient Inconnu');
$numero_dossier = $patient['dossier_numero'] ?? '---';

// 2. Récupération du type de consultation (souvent dans l'URL ou dans le brouillon)
$type_consultation = $_GET['type'] ?? ($consultation['type'] ?? 'EXTERNE');

// 3. Détermination de l'étape actuelle
// On regarde si la variable $numero est passée par l'include, sinon on regarde l'URL, sinon 1
$etape_actuelle = isset($numero) ? $numero : ($_GET['etape'] ?? 1);
$pourcentage = ($etape_actuelle / 7) * 100;
?>

<div class="progress-indicator">
    <div class="progress-step <?php echo $etape_actuelle >= 1 ? ($etape_actuelle == 1 ? 'active' : 'completed') : ''; ?>">1</div>
    <div class="progress-step <?php echo $etape_actuelle >= 2 ? ($etape_actuelle == 2 ? 'active' : 'completed') : ''; ?>">2</div>
    <div class="progress-step <?php echo $etape_actuelle >= 3 ? ($etape_actuelle == 3 ? 'active' : 'completed') : ''; ?>">3</div>
    <div class="progress-step <?php echo $etape_actuelle >= 4 ? ($etape_actuelle == 4 ? 'active' : 'completed') : ''; ?>">4</div>
    <div class="progress-step <?php echo $etape_actuelle >= 5 ? ($etape_actuelle == 5 ? 'active' : 'completed') : ''; ?>">5</div>
    <div class="progress-step <?php echo $etape_actuelle >= 6 ? ($etape_actuelle == 6 ? 'active' : 'completed') : ''; ?>">6</div>
    <div class="progress-step <?php echo $etape_actuelle >= 7 ? ($etape_actuelle == 7 ? 'active' : 'completed') : ''; ?>">7</div>
</div>

<div class="consultation-progress mb-4">
    <div class="progress-header d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">
            <i class="fas fa-user-md me-2"></i>
            Consultation - <?php echo htmlspecialchars($nom_complet); ?>
        </h4>
        <div>
            <span class="badge bg-secondary">N° <?php echo htmlspecialchars($numero_dossier); ?></span>
            <span class="badge bg-<?php echo strtoupper($type_consultation) == 'INTERNE' ? 'warning' : 'info'; ?>">
                <?php echo htmlspecialchars(ucfirst(strtolower($type_consultation))); ?>
            </span>
        </div>
    </div>
    
    <div class="progress-steps d-flex justify-content-between small text-center">
        <div class="<?php echo $etape_actuelle >= 1 ? 'fw-bold text-primary' : 'text-muted'; ?>">Anamnèse</div>
        <div class="<?php echo $etape_actuelle >= 2 ? 'fw-bold text-primary' : 'text-muted'; ?>">Examen</div>
        <div class="<?php echo $etape_actuelle >= 3 ? 'fw-bold text-primary' : 'text-muted'; ?>">Hypothèses</div>
        <div class="<?php echo $etape_actuelle >= 4 ? 'fw-bold text-primary' : 'text-muted'; ?>">Bilans</div>
        <div class="<?php echo $etape_actuelle >= 5 ? 'fw-bold text-primary' : 'text-muted'; ?>">Traitement</div>
        <div class="<?php echo $etape_actuelle >= 6 ? 'fw-bold text-primary' : 'text-muted'; ?>">Surveillance</div>
        <div class="<?php echo $etape_actuelle >= 7 ? 'fw-bold text-primary' : 'text-muted'; ?>">Suivi</div>
    </div>
</div>