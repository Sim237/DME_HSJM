<?php
/**
 * SimCare+ — Dossier Médical Électronique (DME)
 * Copyright (c) 2024-2026 Franck Simeni. Tous droits réservés.
 * Développé pour la gestion hospitalière, et le bien être numérique des patients.
 *
 * Toute reproduction, modification ou distribution de ce logiciel,
 * en tout ou en partie, sans autorisation écrite préalable de l'auteur
 * est strictement interdite et constitue une contrefaçon.
 *
 * Protected under OAPI Agreement — Annexe VII · Berne Convention
 */

/* ============================================================================
FICHIER : app/views/consultations/formulaire/progress_bar.php
Barre de progression réutilisable pour les étapes de consultation
============================================================================ */

$patient          = $patient ?? [];
$nom_complet      = ($patient['prenom'] ?? '') . ' ' . ($patient['nom'] ?? 'Patient Inconnu');
$numero_dossier   = $patient['dossier_numero'] ?? '---';
$type_consultation = $_GET['type'] ?? ($consultation['type'] ?? 'EXTERNE');
$etape_actuelle   = isset($numero) ? (int)$numero : (int)($_GET['etape'] ?? 1);
$patient_id       = $patient['id'] ?? ($_GET['patient_id'] ?? 0);

$etapes = [
    1 => 'Anamnèse',
    2 => 'Examen',
    3 => 'Hypothèses',
    4 => 'Bilans',
    5 => 'Traitement',
    6 => 'Surveillance',
    7 => 'Suivi',
];

// Génère l'URL d'une étape (uniquement si déjà visitée)
$stepUrl = function(int $n) use ($patient_id, $type_consultation): ?string {
    if ($patient_id <= 0) return null;
    return BASE_URL . 'consultation/formulaire?patient_id=' . $patient_id
        . '&type=' . urlencode($type_consultation)
        . '&etape=' . $n;
};
?>

<?php
$_editConsultId = $_SESSION['consultation_temp']['consultation_id'] ?? null;
$_isEditMode    = !empty($_SESSION['consultation_temp']['_edit_mode']) && $_editConsultId;
if ($_isEditMode):
?>
<div class="alert d-flex align-items-center gap-3 mb-3 px-4 py-2"
     style="background:linear-gradient(135deg,#fef3c7,#fde68a);border:2px solid #f59e0b;border-radius:12px;box-shadow:0 2px 10px rgba(245,158,11,.12)">
    <i class="bi bi-pencil-square" style="font-size:1.4rem;color:#b45309;flex-shrink:0"></i>
    <div class="flex-grow-1">
        <span class="fw-bold" style="color:#92400e;font-size:.88rem">Mode Édition</span>
        <span class="ms-2 small" style="color:#b45309">Vous modifiez une consultation déjà enregistrée — Terminez à l'étape 7 pour sauvegarder.</span>
    </div>
    <a href="<?= BASE_URL ?>consultation/recapitulatif/<?= (int)$_editConsultId ?>"
       class="btn btn-sm flex-shrink-0"
       style="background:#fff;color:#92400e;border:1.5px solid #f59e0b;border-radius:20px;font-size:.75rem;white-space:nowrap">
        <i class="bi bi-arrow-left me-1"></i> Annuler
    </a>
</div>
<?php endif; ?>

<?php
// Bannière de restauration du brouillon (quand la session avait expiré)
$_br = $brouillon_restored ?? null;
if ($_br):
    $brTime = date('d/m/Y à H:i', strtotime($_br));
?>
<div id="brouillon-banner"
     style="background:linear-gradient(135deg,#ede9fe,#ddd6fe);border:2px solid #8b5cf6;border-radius:12px;
            padding:12px 18px;margin-bottom:14px;display:flex;align-items:center;gap:12px;
            box-shadow:0 2px 10px rgba(139,92,246,.12)">
    <i class="bi bi-cloud-arrow-up-fill" style="font-size:1.4rem;color:#7c3aed;flex-shrink:0"></i>
    <div class="flex-grow-1">
        <span class="fw-bold" style="color:#5b21b6;font-size:.88rem">Brouillon restauré</span>
        <span class="ms-2 small" style="color:#6d28d9">
            Vos données du <?= htmlspecialchars($brTime) ?> ont été récupérées automatiquement.
        </span>
    </div>
    <button type="button" onclick="this.closest('#brouillon-banner').remove()"
            style="background:none;border:none;cursor:pointer;color:#7c3aed;font-size:1.1rem;line-height:1;padding:2px 6px;"
            title="Fermer">×</button>
</div>
<!-- Marqueur pour le JS footer (sauvegarde → pill) -->
<span id="brouillon-restored-at" data-time="<?= htmlspecialchars($brTime) ?>" style="display:none"></span>
<?php endif; ?>

<!-- Indicateurs cercles numérotés -->
<div class="progress-indicator">
<?php foreach ($etapes as $n => $label):
    $isActive    = ($n === $etape_actuelle);
    $isCompleted = ($n < $etape_actuelle);
    $cls = $isActive ? 'active' : ($isCompleted ? 'completed' : '');
    $url = $stepUrl($n);
?>
    <a href="<?= htmlspecialchars($url) ?>" title="<?= htmlspecialchars($label) ?>"
       class="progress-step <?= $cls ?>"
       style="text-decoration:none; cursor:pointer;">
        <?= $n ?>
    </a>
<?php endforeach; ?>
</div>

<!-- Carte patient + onglets étapes -->
<div class="consultation-progress mb-4">
    <div class="progress-header d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">
            <i class="fas fa-user-md me-2"></i>
            Consultation - <?= htmlspecialchars($nom_complet) ?>
        </h4>
        <div>
            <span class="badge bg-secondary">N° <?= htmlspecialchars($numero_dossier) ?></span>
            <span class="badge bg-<?= strtoupper($type_consultation) == 'INTERNE' ? 'warning' : 'info' ?>">
                <?= htmlspecialchars(ucfirst(strtolower($type_consultation))) ?>
            </span>
        </div>
    </div>

    <div class="progress-steps d-flex justify-content-between small text-center gap-1 flex-wrap">
    <?php foreach ($etapes as $n => $label):
        $isActive    = ($n === $etape_actuelle);
        $isCompleted = ($n < $etape_actuelle);
        $url         = $stepUrl($n);

        if ($isActive):
            $style = 'background:#1e3a8a;color:#fff;border-color:#1e3a8a;';
            $cls   = 'fw-bold';
        elseif ($isCompleted):
            $style = 'background:#e0e7ff;color:#3730a3;border-color:#a5b4fc;';
            $cls   = 'fw-semibold';
        else:
            $style = 'background:#f1f5f9;color:#94a3b8;border-color:#e2e8f0;';
            $cls   = '';
        endif;
    ?>
        <a href="<?= htmlspecialchars($url) ?>"
           class="<?= $cls ?>"
           style="<?= $style ?> border:1.5px solid; border-radius:20px; padding:4px 12px;
                  text-decoration:none; white-space:nowrap; transition:opacity .15s;"
           onmouseover="this.style.opacity='.75'"
           onmouseout="this.style.opacity='1'"
           title="Aller à l'étape <?= $n ?>">
            <?= htmlspecialchars($label) ?>
        </a>
    <?php endforeach; ?>
    </div>
</div>
