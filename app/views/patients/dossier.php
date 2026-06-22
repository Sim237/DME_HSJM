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

require_once __DIR__ . '/../layouts/header.php';

// --- Fonctions et Initialisation ---
if (!function_exists('getInitials')) {
    function getInitials($nom, $prenom) {
        return strtoupper(substr($nom ?? '', 0, 1) . substr($prenom ?? '', 0, 1));
    }
}

// Sécurisation des données patient
$patient              = $patient              ?? [];
$parametres           = $parametres           ?? null;
$parametres_vitaux    = $parametres_vitaux    ?? [];
$consultations        = $consultations        ?? [];
$bilans               = $bilans               ?? [];
$history              = $history              ?? [];
$comptes_rendus       = $comptes_rendus       ?? [];
$prescriptions        = $prescriptions        ?? [];
$bilans_demandes      = $bilans_demandes      ?? [];
$bilans_imagerie      = $bilans_imagerie      ?? [];
$hospitalisation      = $hospitalisation      ?? null;
$historique_hospit    = $historique_hospit    ?? [];

// Calcul de l'âge
$age = 'N/A';
$ageNumerique = 999;
if (!empty($patient['date_naissance'])) {
    $diff = date_diff(date_create($patient['date_naissance']), date_create('today'));
    $ageNumerique = (int)$diff->y;
    $age = $ageNumerique . ' ans';
}
$isEnfant = ($ageNumerique <= 15);

// ── Alertes critiques sur les vitaux ──────────────────────────────────────────
$alertes_vitaux = [];
$temp_val  = (float)($parametres['temperature']           ?? 0);
$sys_val   = (int)  ($parametres['pression_arterielle_systolique']   ?? 0);
$dia_val   = (int)  ($parametres['pression_arterielle_diastolique']  ?? 0);
$pouls_val = (int)  ($parametres['frequence_cardiaque']   ?? 0);
$spo2_val  = (float)($parametres['saturation_oxygene']    ?? 0);

if ($temp_val  >= 40)                           $alertes_vitaux[] = ['niveau'=>'CRITIQUE', 'msg'=>"Hyperthermie critique : {$temp_val}°C", 'icon'=>'bi-thermometer-sun', 'color'=>'#dc2626'];
elseif ($temp_val >= 38.5 && $temp_val < 40)   $alertes_vitaux[] = ['niveau'=>'ATTENTION', 'msg'=>"Fièvre : {$temp_val}°C", 'icon'=>'bi-thermometer-half', 'color'=>'#d97706'];
elseif ($temp_val > 0 && $temp_val < 35.5)     $alertes_vitaux[] = ['niveau'=>'ATTENTION', 'msg'=>"Hypothermie : {$temp_val}°C", 'icon'=>'bi-thermometer-snow', 'color'=>'#0284c7'];

if ($sys_val  >= 180 || $dia_val >= 120)        $alertes_vitaux[] = ['niveau'=>'CRITIQUE', 'msg'=>"HTA sévère : {$sys_val}/{$dia_val} mmHg", 'icon'=>'bi-heart-pulse-fill', 'color'=>'#dc2626'];
elseif ($sys_val >= 140 || $dia_val >= 90)      $alertes_vitaux[] = ['niveau'=>'ATTENTION', 'msg'=>"Hypertension : {$sys_val}/{$dia_val} mmHg", 'icon'=>'bi-heart-pulse', 'color'=>'#d97706'];
elseif ($sys_val > 0 && $sys_val < 90)          $alertes_vitaux[] = ['niveau'=>'CRITIQUE', 'msg'=>"Hypotension : {$sys_val}/{$dia_val} mmHg", 'icon'=>'bi-heart-pulse', 'color'=>'#dc2626'];

if ($pouls_val > 120)                           $alertes_vitaux[] = ['niveau'=>'ATTENTION', 'msg'=>"Tachycardie : {$pouls_val} bpm", 'icon'=>'bi-activity', 'color'=>'#d97706'];
elseif ($pouls_val > 0 && $pouls_val < 50)      $alertes_vitaux[] = ['niveau'=>'ATTENTION', 'msg'=>"Bradycardie : {$pouls_val} bpm", 'icon'=>'bi-activity', 'color'=>'#0284c7'];

if ($spo2_val > 0 && $spo2_val < 90)            $alertes_vitaux[] = ['niveau'=>'CRITIQUE', 'msg'=>"Désaturation critique : SpO₂ {$spo2_val}%", 'icon'=>'bi-lungs-fill', 'color'=>'#dc2626'];
elseif ($spo2_val > 0 && $spo2_val < 94)        $alertes_vitaux[] = ['niveau'=>'ATTENTION', 'msg'=>"SpO₂ basse : {$spo2_val}%", 'icon'=>'bi-lungs', 'color'=>'#d97706'];

$fr_alert   = (int)($parametres['frequence_respiratoire'] ?? 0);
$glyc_alert = (float)($parametres['glycemie'] ?? 0);
if ($fr_alert > 25)                             $alertes_vitaux[] = ['niveau'=>'ATTENTION', 'msg'=>"Tachypnée : {$fr_alert} c/min", 'icon'=>'bi-wind', 'color'=>'#d97706'];
elseif ($fr_alert > 0 && $fr_alert < 10)        $alertes_vitaux[] = ['niveau'=>'ATTENTION', 'msg'=>"Bradypnée : {$fr_alert} c/min", 'icon'=>'bi-wind', 'color'=>'#0891b2'];
if ($glyc_alert > 0 && $glyc_alert > 1.26)     $alertes_vitaux[] = ['niveau'=>'ATTENTION', 'msg'=>"Hyperglycémie : {$glyc_alert} g/L", 'icon'=>'bi-droplet-fill', 'color'=>'#d97706'];
elseif ($glyc_alert > 0 && $glyc_alert < 0.70) $alertes_vitaux[] = ['niveau'=>'CRITIQUE', 'msg'=>"Hypoglycémie : {$glyc_alert} g/L", 'icon'=>'bi-droplet', 'color'=>'#dc2626'];

// ── Données JSON pour les graphiques ──────────────────────────────────────────
$vitaux_json = [];
foreach (array_reverse($parametres_vitaux) as $v) {
    $vitaux_json[] = [
        'date'  => date('d/m H:i', strtotime($v['date_mesure'] ?? '')),
        'temp'  => (float)($v['temperature'] ?? 0)                          ?: null,
        'sys'   => (int)  ($v['pression_arterielle_systolique'] ?? 0)        ?: null,
        'dia'   => (int)  ($v['pression_arterielle_diastolique'] ?? 0)       ?: null,
        'pouls' => (int)  ($v['frequence_cardiaque'] ?? 0)                   ?: null,
        'spo2'  => (float)($v['saturation_oxygene'] ?? 0)                    ?: null,
        'poids' => (float)($v['poids'] ?? 0)                                 ?: null,
    ];
}
?>

<!-- IMPORT DES ICONES BOOTSTRAP -->
<!--<link rel="stylesheet" href="<?= BASE_URL ?>public/css/bootstrap-icons.css">-->

<!-- STYLES DOSSIER -->
<style>
    body { background: #f0f4f8; }

    /* ── BANNIÈRE PATIENT ── */
    .dossier-banner {
        background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 60%, #1e40af 100%);
        border-radius: 20px; padding: 24px 28px; margin-bottom: 20px;
        color: #fff; position: relative; overflow: hidden;
    }
    .dossier-banner::before {
        content: ''; position: absolute; right: -60px; top: -60px;
        width: 250px; height: 250px; border-radius: 50%;
        background: rgba(255,255,255,.04);
        pointer-events: none;
    }
    .dossier-avatar {
        width: 72px; height: 72px; border-radius: 50%;
        background: rgba(255,255,255,.15); border: 2px solid rgba(255,255,255,.3);
        display: flex; align-items: center; justify-content: center;
        font-size: 1.7rem; font-weight: 800; color: #fff; flex-shrink: 0;
    }
    .dossier-nom { font-size: 1.35rem; font-weight: 800; margin-bottom: 4px; }
    .dossier-pill {
        display: inline-flex; align-items: center; gap: 5px;
        background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.2);
        border-radius: 20px; padding: 3px 12px; font-size: .73rem; font-weight: 600;
    }

    /* ── CONSTANTES ── */
    .vital-grid {
        display: grid; grid-template-columns: repeat(4,1fr); gap: 14px; margin-bottom: 18px;
    }
    .vital-card {
        background: #fff; border-radius: 14px; padding: 14px 16px;
        box-shadow: 0 2px 8px rgba(0,0,0,.06); border-top: 3px solid #e2e8f0;
        transition: transform .15s;
    }
    .vital-card:hover { transform: translateY(-2px); }
    .vital-card.vc-temp   { border-color: #ef4444; }
    .vital-card.vc-ta     { border-color: #3b82f6; }
    .vital-card.vc-pouls  { border-color: #10b981; }
    .vital-card.vc-poids  { border-color: #f59e0b; }
    .vital-card.vc-spo2   { border-color: #8b5cf6; }
    .vc-label { font-size: .68rem; font-weight: 700; text-transform: uppercase; letter-spacing: .6px; color: #94a3b8; margin-bottom: 4px; }
    .vc-val   { font-size: 1.35rem; font-weight: 800; color: #1e293b; line-height: 1.1; }
    .vc-unit  { font-size: .75rem; font-weight: 500; color: #64748b; }
    /* Grille 5 colonnes (première rangée) → responsive */
    @media (max-width: 1200px) {
        [style*="grid-template-columns:repeat(5,1fr)"] {
            grid-template-columns: repeat(3,1fr) !important;
        }
    }
    @media (max-width: 768px) {
        [style*="grid-template-columns:repeat(5,1fr)"],
        [style*="grid-template-columns:repeat(4,1fr)"] {
            grid-template-columns: repeat(2,1fr) !important;
        }
    }

    /* ── ACTIONS ── */
    .action-bar {
        display: flex; flex-wrap: wrap; gap: 8px; align-items: center; margin-top: 16px;
    }
    .ab-btn {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 7px 14px; border-radius: 10px; font-size: .78rem; font-weight: 700;
        border: none; cursor: pointer; text-decoration: none; white-space: nowrap;
        transition: all .15s;
    }
    .ab-btn:hover { opacity: .88; transform: translateY(-1px); }
    .ab-primary  { background: #2563eb; color: #fff; }
    .ab-success  { background: #16a34a; color: #fff; }
    .ab-warning  { background: #d97706; color: #fff; }
    .ab-danger   { background: #dc2626; color: #fff; }
    .ab-purple   { background: #7c3aed; color: #fff; }
    .ab-dark     { background: #1e293b; color: #fff; }
    .ab-teal     { background: #0891b2; color: #fff; }
    .ab-ghost    { background: rgba(255,255,255,.12); color: #fff; border: 1px solid rgba(255,255,255,.2); }
    .ab-ghost:hover { background: rgba(255,255,255,.22); color: #fff; }
    .ab-sep { width: 1px; height: 28px; background: rgba(255,255,255,.2); margin: 0 2px; }

    /* ── ONGLETS ── */
    .nav-tabs { border-bottom: 2px solid #e2e8f0; }
    .nav-tabs .nav-link { border: none; color: #64748b; font-weight: 600; padding: 11px 18px; font-size: .88rem; }
    .nav-tabs .nav-link.active { color: #2563eb; border-bottom: 2px solid #2563eb; background: transparent; margin-bottom: -2px; }
    .tab-content { background: #fff; padding: 24px; border-radius: 0 0 14px 14px; border: 1px solid #e2e8f0; border-top: none; }

    .table-danger-light { background-color: #fff5f5 !important; }
    .text-anormal { color: #dc3545; font-weight: 800; }

    /* ── INFO CHIPS ── */
    .ab-info-chip {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 6px 12px; border-radius: 10px; font-size: .74rem; font-weight: 700;
        white-space: nowrap; border: 1px solid transparent; line-height: 1.2;
        cursor: default; user-select: none;
    }
    .ab-info-chip i { font-size: .82rem; flex-shrink: 0; }
    .ab-chip-detail {
        font-weight: 500; opacity: .8; font-size: .69rem;
        border-left: 1px solid rgba(255,255,255,.3); padding-left: 6px; margin-left: 2px;
    }
    .ab-chip-badge {
        background: rgba(0,0,0,.18); border-radius: 20px;
        padding: 1px 7px; font-size: .66rem; font-weight: 700;
        border: 1px solid rgba(255,255,255,.2);
    }
    .ab-chip-danger  { background: rgba(220,38,38,.30); color: #fca5a5; border-color: rgba(220,38,38,.4); }
    .ab-chip-blue    { background: rgba(37,99,235,.28); color: #93c5fd; border-color: rgba(37,99,235,.38); }
    .ab-chip-amber   { background: rgba(217,119,6,.28);  color: #fcd34d; border-color: rgba(217,119,6,.38); }
    .ab-chip-teal    { background: rgba(8,145,178,.28);  color: #67e8f9; border-color: rgba(8,145,178,.38); }
    .ab-chip-neutral { background: rgba(255,255,255,.08); color: rgba(255,255,255,.55); border-color: rgba(255,255,255,.15); }
</style>

<?php
$statut_patient = $patient['statut'] ?? 'AMBULATOIRE';
$statut_colors = ['HOSPITALISE'=>'#2563eb','AMBULATOIRE'=>'#16a34a','SORTIE'=>'#64748b','URGENCE'=>'#dc2626'];
$statut_color = $statut_colors[$statut_patient] ?? '#64748b';
$sys = $parametres['pression_arterielle_systolique'] ?? null;
$dia = $parametres['pression_arterielle_diastolique'] ?? null;
$ta_display = ($sys && $dia && $sys > 0) ? "$sys/$dia" : '--/--';
?>

<div class="container-fluid px-4 pt-3 pb-2">

    <!-- ══ BANNIÈRE PATIENT ══ -->
    <div class="dossier-banner">
        <div class="d-flex align-items-center gap-3 mb-3">
            <!-- Avatar -->
            <div class="dossier-avatar"><?= getInitials($patient['nom'], $patient['prenom']) ?></div>

            <!-- Identité -->
            <div class="flex-grow-1">
                <div class="dossier-nom"><?= htmlspecialchars(strtoupper($patient['nom']) . ' ' . $patient['prenom']) ?></div>
                <div class="d-flex flex-wrap gap-2 mt-1">
                    <span class="dossier-pill"><i class="bi bi-card-text"></i> <?= htmlspecialchars($patient['dossier_numero']) ?></span>
                    <span class="dossier-pill"><i class="bi bi-calendar3"></i> <?= $age ?></span>
                    <span class="dossier-pill"><i class="bi bi-gender-ambiguous"></i> <?= ($patient['sexe']??'') === 'M' ? 'Masculin' : 'Féminin' ?></span>
                    <span class="dossier-pill" style="background:rgba(239,68,68,.25);border-color:rgba(239,68,68,.4)">
                        <i class="bi bi-droplet-fill text-danger"></i> <?= $patient['groupe_sanguin'] ?: '?' ?>
                    </span>
                    <span class="dossier-pill" style="background:rgba(255,255,255,.2);border-color:<?= $statut_color ?>;color:#fff">
                        <?= $statut_patient ?>
                    </span>
                </div>
            </div>

            <!-- Bouton MODIFIER INFOS (médecins, infirmiers, secrétaire, admin) -->
            <?php
            $userRole = strtoupper($_SESSION['user_role'] ?? '');
            $peutModifierInfos = in_array($userRole, [
                'MEDECIN','CHIRURGIEN','GENERALISTE',
                'INFIRMIER','INFIRMIER_CONSULTANT','MAJOR_INFIRMIER','MAJOR',
                'ADMIN','ADMINISTRATEUR','DIRECTEUR','SECRETAIRE'
            ]);
            if ($peutModifierInfos):
            ?>
            <button class="ab-btn" data-bs-toggle="modal" data-bs-target="#modalModifInfosPatient"
                    style="background:rgba(255,255,255,.18);color:#fff;border:1.5px solid rgba(255,255,255,.4);">
                <i class="bi bi-pencil-square"></i> Modifier infos
            </button>
            <?php endif; ?>

            <!-- Retour -->
            <a href="<?= BASE_URL ?>dashboard" class="ab-btn ab-ghost ms-auto">
                <i class="bi bi-arrow-left"></i> Retour
            </a>
        </div>

        <!-- ── BOUTONS D'ACTIONS ── -->
        <div class="action-bar">
            <!-- Consultation -->
            <?php
            $patientStatut = $patient['statut'] ?? '';
            $isHospitalise = ($patientStatut === 'HOSPITALISE');
            $specialiteMedecin = strtolower(trim($_SESSION['specialite'] ?? ''));
            // Fallback pour les sessions ouvertes avant le fix Auth
            if ($specialiteMedecin === '' && !empty($_SESSION['user_id'])) {
                try {
                    $dbSp = (new Database())->getConnection();
                    $stmtSp = $dbSp->prepare("SELECT specialite FROM users WHERE id = ? LIMIT 1");
                    $stmtSp->execute([$_SESSION['user_id']]);
                    $specialiteMedecin = strtolower(trim($stmtSp->fetchColumn() ?: ''));
                    $_SESSION['specialite'] = $specialiteMedecin;
                } catch (\Throwable $e) { $specialiteMedecin = ''; }
            }
            $nomServiceSession = strtolower(trim($_SESSION['nom_service'] ?? ''));
            $isGyneco = str_contains($specialiteMedecin, 'gynec')
                     || str_contains($specialiteMedecin, 'gynéc')
                     || str_contains($specialiteMedecin, 'obstet')
                     || str_contains($specialiteMedecin, 'materni')
                     || str_contains($nomServiceSession, 'matern')
                     || str_contains($nomServiceSession, 'gynec')
                     || str_contains($nomServiceSession, 'gynéc')
                     || in_array(strtoupper($_SESSION['user_role'] ?? ''), [
                            'GYNECOLOGIE', 'GYNECO_OBS', 'SAGE_FEMME', 'GYNECO'
                        ]);
            ?>
            <button type="button" class="ab-btn ab-success"
                    onclick="ouvrirModalConsultation()">
                <i class="bi bi-plus-circle-fill"></i> Nouvelle Consultation
            </button>

            <?php if (in_array($_SESSION['user_role'], ['MEDECIN','CHIRURGIEN','ADMIN','GENERALISTE'])): ?>
            <a href="<?= BASE_URL ?>prescription/create?patient_id=<?= (int)$patient['id'] ?>" class="ab-btn ab-warning">
                <i class="bi bi-capsule-pill"></i> Ordonnance
            </a>
            <?php endif; ?>

            <button class="ab-btn ab-teal" data-bs-toggle="modal" data-bs-target="#modalBilan">
                <i class="bi bi-flask-fill"></i> Demander Bilans
            </button>

            <div class="ab-sep"></div>

            <?php if (in_array($_SESSION['user_role'], ['MEDECIN','CHIRURGIEN','ADMIN'])): ?>
            <?php if (($patient['statut'] ?? '') !== 'HOSPITALISE'): ?>
                <button class="ab-btn ab-primary" data-bs-toggle="modal" data-bs-target="#modalAdmission">
                    <i class="bi bi-hospital"></i> Admettre sur Lit
                </button>
            <?php else: ?>
                <button class="ab-btn ab-warning" onclick="libererLit(<?= $patient['id'] ?>)">
                    <i class="bi bi-box-arrow-left"></i> Décharger du Lit
                </button>
            <?php endif; ?>

            <button class="ab-btn ab-dark" onclick="transmettreAnesthesie(<?= $patient['id'] ?>)">
                <i class="bi bi-scissors"></i> Bloc Opératoire
            </button>
            <?php endif; ?>

            <a href="<?= BASE_URL ?>hospitalisation/planifier-soins/<?= $patient['id'] ?>" class="ab-btn ab-purple">
                <i class="bi bi-calendar-check-fill"></i> Planifier Soins
            </a>

            <?php
            /* ── Informations rapides ─────────────────────────────────── */
            $allergies       = trim($patient['allergies'] ?? '');
            // Compteur global = consultations standards + pédiatriques
            $nbConsultations = $nbConsultationsGlobal ?? count($consultations);
            $derniereConsult = !empty($consultations) ? ($consultations[0]['date_consultation'] ?? null) : null;
            $nbPrescriptions = count($prescriptions);
            $nbBilans        = count($bilans_demandes ?? []);
            $nbBilansPending = 0;
            foreach ($bilans_demandes ?? [] as $b) {
                if (in_array($b['statut_demande'] ?? '', ['en_attente','EN_ATTENTE','PENDING','pending'])) $nbBilansPending++;
            }
            ?>
            <div class="ab-sep"></div>

            <!-- Allergies -->
            <?php if ($allergies): ?>
            <div class="ab-info-chip ab-chip-danger" title="<?= htmlspecialchars($allergies) ?>">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <span>Allergie</span>
                <span class="ab-chip-detail"><?= mb_strimwidth(htmlspecialchars($allergies), 0, 28, '…') ?></span>
            </div>
            <?php else: ?>
            <div class="ab-info-chip ab-chip-neutral" title="Aucune allergie connue">
                <i class="bi bi-shield-check"></i>
                <span>Aucune allergie</span>
            </div>
            <?php endif; ?>

            <!-- Dernière consultation -->
            <div class="ab-info-chip ab-chip-blue" title="Dernière consultation">
                <i class="bi bi-journal-text"></i>
                <span><?= $nbConsultations ?> consultation<?= $nbConsultations > 1 ? 's' : '' ?></span>
                <?php if ($derniereConsult): ?>
                <span class="ab-chip-detail"><?= date('d/m/Y', strtotime($derniereConsult)) ?></span>
                <?php endif; ?>
            </div>

            <!-- Prescriptions -->
            <div class="ab-info-chip <?= $nbPrescriptions > 0 ? 'ab-chip-amber' : 'ab-chip-neutral' ?>" title="Prescriptions médicamenteuses">
                <i class="bi bi-capsule"></i>
                <span><?= $nbPrescriptions ?> prescription<?= $nbPrescriptions > 1 ? 's' : '' ?></span>
            </div>

            <!-- Bilans -->
            <div class="ab-info-chip <?= $nbBilansPending > 0 ? 'ab-chip-teal' : 'ab-chip-neutral' ?>" title="Bilans demandés">
                <i class="bi bi-flask"></i>
                <span><?= $nbBilans ?> bilan<?= $nbBilans > 1 ? 's' : '' ?></span>
                <?php if ($nbBilansPending > 0): ?>
                <span class="ab-chip-badge"><?= $nbBilansPending ?> en attente</span>
                <?php endif; ?>
            </div>

            <?php if (in_array($_SESSION['user_role'] ?? '', ['MEDECIN','CHIRURGIEN','ADMIN','INFIRMIER_CONSULTANT','GENERALISTE'])): ?>
            <button class="ab-btn ab-ghost" style="color:#7c3aed;border-color:#7c3aed;" data-bs-toggle="modal" data-bs-target="#modalSpecialiste">
                <i class="bi bi-person-badge-fill"></i> Voir Spécialiste
            </button>
            <?php endif; ?>

            <button class="ab-btn ab-ghost" data-bs-toggle="modal" data-bs-target="#modalPartager">
                <i class="bi bi-share-fill"></i> Partager
            </button>

            <a href="<?= BASE_URL ?>patients/imprimer-recap/<?= (int)$patient['id'] ?>"
               target="_blank"
               class="ab-btn ab-ghost"
               style="color:#0f766e;border-color:#0f766e;"
               title="Imprimer le récapitulatif complet du dossier (A4 paysage)">
                <i class="bi bi-printer-fill"></i> Récapitulatif complet
            </a>
            <button type="button"
                    onclick="window.print()"
                    class="ab-btn ab-ghost no-print"
                    style="color:#475569;border-color:#cbd5e1;"
                    title="Imprimer cette page">
                <i class="bi bi-printer me-1"></i> Imprimer la page
            </button>
        </div>
    </div>

    <!-- ══ ALERTES VITAUX CRITIQUES ══ -->
    <?php if (!empty($alertes_vitaux)): ?>
    <div style="display:flex;flex-direction:column;gap:6px;margin-bottom:12px;">
        <?php foreach ($alertes_vitaux as $al): ?>
        <div style="display:flex;align-items:center;gap:10px;padding:10px 16px;border-radius:12px;
                    background:<?= $al['color'] ?>18;border:1.5px solid <?= $al['color'] ?>44;">
            <div style="width:32px;height:32px;border-radius:50%;background:<?= $al['color'] ?>22;
                        display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="bi <?= $al['icon'] ?>" style="color:<?= $al['color'] ?>;font-size:1rem"></i>
            </div>
            <div style="flex:1">
                <span style="font-weight:800;color:<?= $al['color'] ?>;font-size:.83rem">
                    <?= $al['niveau'] === 'CRITIQUE' ? '⚠ ALERTE CRITIQUE — ' : '⚡ Attention — ' ?>
                </span>
                <span style="font-size:.83rem;color:#374151"><?= htmlspecialchars($al['msg']) ?></span>
            </div>
            <span style="font-size:.68rem;font-weight:700;padding:3px 8px;border-radius:20px;
                         background:<?= $al['color'] ?>;color:#fff;">
                <?= $al['niveau'] ?>
            </span>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- ══ CONSTANTES VITALES ══ -->
    <?php
    // Calcul IMC
    $imc = null; $imc_label = '';
    $poids_v  = (float)($parametres['poids']  ?? 0);
    $taille_v = (float)($parametres['taille'] ?? 0);
    if ($poids_v > 0 && $taille_v > 0) {
        $taille_m = $taille_v / 100;
        $imc = round($poids_v / ($taille_m * $taille_m), 1);
        $imc_label = $imc < 18.5 ? 'Insuffisance' : ($imc < 25 ? 'Normal' : ($imc < 30 ? 'Surpoids' : 'Obésité'));
    }
    $spo2_display = $parametres['saturation_oxygene'] ?? null;
    $glyc_display = isset($parametres['glycemie']) ? (float)$parametres['glycemie'] : null;
    $fr_display   = isset($parametres['frequence_respiratoire']) ? (int)$parametres['frequence_respiratoire'] : null;
    ?>
    <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:10px;margin-bottom:8px;">
        <?php
        // ── Couleurs avec seuils cliniques ─────────────────────────────────
        $tc  = ($temp_val >= 40)  ? '#dc2626'
             : ($temp_val >= 38.5 ? '#d97706'
             : ($temp_val > 0 && $temp_val < 35.5 ? '#0284c7' : '#374151'));

        // Systolique
        $sysc = ($sys_val >= 180) ? '#dc2626'
              : ($sys_val >= 140  ? '#d97706'
              : ($sys_val > 0 && $sys_val < 90 ? '#dc2626' : '#1d4ed8'));
        $sys_label = ($sys_val >= 180) ? 'Urgence HTA'
                   : ($sys_val >= 140  ? 'HTA'
                   : ($sys_val > 0 && $sys_val < 90 ? 'Hypotension' : ''));

        // Diastolique
        $diac = ($dia_val >= 120) ? '#dc2626'
              : ($dia_val >= 90   ? '#d97706'
              : ($dia_val > 0 && $dia_val < 60 ? '#dc2626' : '#6366f1'));
        $dia_label = ($dia_val >= 120) ? 'HTA sévère'
                   : ($dia_val >= 90   ? 'HTA'
                   : ($dia_val > 0 && $dia_val < 60 ? 'Hypotension' : ''));

        $pc  = ($pouls_val > 120 || ($pouls_val > 0 && $pouls_val < 50)) ? '#d97706' : '#16a34a';
        $sc  = ($spo2_val > 0 && $spo2_val < 90) ? '#dc2626' : (($spo2_val > 0 && $spo2_val < 94) ? '#d97706' : '#0284c7');
        $frc = ($fr_display && ($fr_display > 25 || $fr_display < 10)) ? '#d97706' : '#0891b2';
        $gc  = ($glyc_display && ($glyc_display > 1.26 || $glyc_display < 0.70)) ? '#dc2626' : '#7c3aed';
        ?>

        <!-- Température -->
        <div class="vital-card vc-temp" style="border-top:3px solid <?= $tc ?>">
            <div class="vc-label"><i class="bi bi-thermometer-half me-1"></i>Température</div>
            <div class="vc-val" id="vt-temp" style="color:<?= $tc ?>"><?= $temp_val ?: '--' ?><span class="vc-unit"> °C</span></div>
        </div>

        <!-- Systolique -->
        <div class="vital-card vc-ta" style="border-top:3px solid <?= $sysc ?>">
            <div class="vc-label"><i class="bi bi-heart-pulse me-1"></i>Systolique</div>
            <div class="vc-val" id="vt-sys" style="color:<?= $sysc ?>">
                <?= $sys_val ?: '--' ?><span class="vc-unit"> mmHg</span>
            </div>
            <?php if ($sys_label): ?>
            <div style="font-size:.65rem;font-weight:700;margin-top:2px;color:<?= $sysc ?>"><?= $sys_label ?></div>
            <?php endif; ?>
        </div>

        <!-- Diastolique -->
        <div class="vital-card" style="border-top:3px solid <?= $diac ?>">
            <div class="vc-label"><i class="bi bi-heart me-1"></i>Diastolique</div>
            <div class="vc-val" id="vt-dia" style="color:<?= $diac ?>">
                <?= $dia_val ?: '--' ?><span class="vc-unit"> mmHg</span>
            </div>
            <?php if ($dia_label): ?>
            <div style="font-size:.65rem;font-weight:700;margin-top:2px;color:<?= $diac ?>"><?= $dia_label ?></div>
            <?php endif; ?>
        </div>

        <!-- Fréquence Cardiaque -->
        <div class="vital-card vc-pouls" style="border-top:3px solid <?= $pc ?>">
            <div class="vc-label"><i class="bi bi-activity me-1"></i>Fréq. Cardiaque</div>
            <div class="vc-val" id="vt-pouls" style="color:<?= $pc ?>"><?= $pouls_val ?: '--' ?><span class="vc-unit"> bpm</span></div>
        </div>

        <!-- SpO₂ -->
        <div class="vital-card" style="border-top:3px solid <?= $sc ?>">
            <div class="vc-label"><i class="bi bi-lungs me-1"></i>SpO₂</div>
            <div class="vc-val" style="color:<?= $sc ?>"><?= $spo2_val ?: '--' ?><span class="vc-unit"> %</span></div>
        </div>
    </div>
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:12px;">
        <!-- Poids -->
        <div class="vital-card vc-poids" style="border-top:3px solid #64748b">
            <div class="vc-label"><i class="bi bi-person me-1"></i>Poids</div>
            <div class="vc-val text-dark" id="vt-poids"><?= $poids_v ?: '--' ?><span class="vc-unit"> kg</span></div>
        </div>
        <!-- IMC -->
        <div class="vital-card" style="border-top:3px solid #8b5cf6">
            <div class="vc-label"><i class="bi bi-calculator me-1"></i>IMC</div>
            <div class="vc-val" style="color:#7c3aed"><?= $imc ?? '--' ?></div>
            <?php if ($imc_label): ?>
            <div style="font-size:.65rem;color:#94a3b8;font-weight:600;margin-top:2px"><?= $imc_label ?></div>
            <?php endif; ?>
        </div>
        <!-- Fréquence Respiratoire -->
        <div class="vital-card" style="border-top:3px solid <?= $frc ?>">
            <div class="vc-label"><i class="bi bi-wind me-1"></i>Fréq. Respiratoire</div>
            <div class="vc-val" style="color:<?= $frc ?>">
                <?= $fr_display ?: '--' ?>
                <?php if ($fr_display): ?><span class="vc-unit"> c/min</span><?php endif; ?>
            </div>
            <?php if ($fr_display && ($fr_display > 25 || $fr_display < 10)): ?>
            <div style="font-size:.65rem;color:#d97706;font-weight:700;margin-top:2px">
                <?= $fr_display > 25 ? 'Tachypnée' : 'Bradypnée' ?>
            </div>
            <?php endif; ?>
        </div>
        <!-- Glycémie Capillaire -->
        <div class="vital-card" style="border-top:3px solid <?= $gc ?>">
            <div class="vc-label"><i class="bi bi-droplet me-1"></i>Glycémie cap.</div>
            <div class="vc-val" style="color:<?= $gc ?>">
                <?= $glyc_display ? number_format($glyc_display, 2) : '--' ?>
                <?php if ($glyc_display): ?><span class="vc-unit"> g/L</span><?php endif; ?>
            </div>
            <?php if ($glyc_display): ?>
            <div style="font-size:.65rem;color:<?= $gc ?>;font-weight:700;margin-top:2px">
                <?= $glyc_display > 1.26 ? 'Hyperglycémie' : ($glyc_display < 0.70 ? 'Hypoglycémie' : 'Normal') ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bouton courbes + graphique -->
    <?php if (!empty($parametres_vitaux) && count($parametres_vitaux) > 1): ?>
    <div style="margin-bottom:12px;">
        <button onclick="toggleCourbesVitaux()" id="btnCourbes"
                style="background:#fff;border:1.5px solid #e2e8f0;border-radius:10px;padding:7px 16px;
                       font-size:.78rem;font-weight:700;color:#374151;cursor:pointer;display:flex;align-items:center;gap:6px;">
            <i class="bi bi-graph-up" style="color:#3b82f6"></i>
            Courbes d'évolution (<?= count($parametres_vitaux) ?> mesures)
            <i class="bi bi-chevron-down" id="iconCourbes" style="font-size:.7rem;transition:.2s"></i>
        </button>
        <div id="panelCourbes" style="display:none;margin-top:10px;background:#fff;border-radius:14px;
             padding:16px 20px;border:1px solid #e2e8f0;box-shadow:0 2px 8px rgba(0,0,0,.05)">
            <div style="display:flex;gap:8px;margin-bottom:10px;flex-wrap:wrap">
                <?php foreach([
                    'temp'   => ['Température', '#ef4444'],
                    'ta'     => ['TA Sys+Dia',  '#7c3aed'],
                    'sys'    => ['Systolique',  '#3b82f6'],
                    'dia'    => ['Diastolique', '#6366f1'],
                    'pouls'  => ['Pouls',       '#16a34a'],
                    'spo2'   => ['SpO₂',        '#8b5cf6'],
                ] as $k=>[$lbl,$col]): ?>
                <button onclick="changerCourbe('<?= $k ?>')"
                        id="btn-courbe-<?= $k ?>"
                        style="background:<?= $col ?>18;border:1.5px solid <?= $col ?>44;color:<?= $col ?>;
                               border-radius:20px;padding:3px 12px;font-size:.72rem;font-weight:700;cursor:pointer;">
                    <?= $lbl ?>
                </button>
                <?php endforeach; ?>
            </div>
            <div style="position:relative;height:180px;">
                <canvas id="chartVitaux"></canvas>
            </div>
            <div style="font-size:.68rem;color:#94a3b8;margin-top:6px;text-align:center">
                Dernières <?= count($parametres_vitaux) ?> mesures · <?= count($parametres_vitaux) > 0 ? date('d/m/Y', strtotime($parametres_vitaux[count($parametres_vitaux)-1]['date_mesure'] ?? '')) . ' → ' . date('d/m/Y', strtotime($parametres_vitaux[0]['date_mesure'] ?? '')) : '' ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ══ ONGLETS (pleine largeur) ══ -->
    <div class="bg-white rounded-3 shadow-sm" style="border:1px solid #e2e8f0">

            <!-- 2. ONGLETS -->
            <ul class="nav nav-tabs" id="myTab" role="tablist">
                <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-consultations" type="button"><i class="bi bi-journal-text me-2"></i>Consultations</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-antecedents" type="button"><i class="bi bi-clock-history me-2"></i>Antécédents</button></li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-prescriptions" type="button">
                        <i class="bi bi-capsule me-2"></i>Médicaments
                        <?php if (!empty($prescriptions)): ?>
                            <span class="badge bg-warning text-dark rounded-pill ms-1"><?= count($prescriptions) ?></span>
                        <?php endif; ?>
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-bilans-demandes" type="button">
                        <i class="bi bi-flask me-2"></i>Bilans
                        <?php if (!empty($bilans_demandes)): ?>
                            <span class="badge bg-info text-white rounded-pill ms-1"><?= count($bilans_demandes) ?></span>
                        <?php endif; ?>
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-documents" type="button">
                        <i class="bi bi-file-earmark-medical me-2"></i>Documents
                        <?php if (!empty($comptes_rendus)): ?>
                            <span class="badge bg-primary rounded-pill ms-1"><?= count($comptes_rendus) ?></span>
                        <?php endif; ?>
                    </button>
                </li>
                <?php
                // Onglet Pédiatrie visible si des consultations pédiatriques existent ou service pédiatrie
                $nomSvcDossier = strtolower($_SESSION['nom_service'] ?? '');
                $isPedService = stripos($nomSvcDossier, 'pédiatrie') !== false || stripos($nomSvcDossier, 'pediatrie') !== false
                             || stripos($nomSvcDossier, 'néonatologie') !== false || stripos($nomSvcDossier, 'neonatologie') !== false
                             || ($_SESSION['role'] ?? '') === 'ADMIN';
                if ($isPedService):
                ?>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-ped" type="button">
                        <i class="bi bi-heart-pulse-fill me-2 text-primary"></i>Pédiatrie
                    </button>
                </li>
                <?php endif; ?>
                <!-- Onglet Hospitalisation -->
                <?php if ($hospitalisation): ?>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-hospitalisation" type="button">
                        <i class="bi bi-hospital-fill me-2 text-info"></i>Hospitalisation
                        <?php if (($hospitalisation['statut'] ?? '') === 'en_cours'): ?>
                        <span class="badge bg-success rounded-pill ms-1" style="font-size:.6rem">En cours</span>
                        <?php endif; ?>
                    </button>
                </li>
                <?php endif; ?>
                <!-- Onglet Timeline -->
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-timeline" type="button">
                        <i class="bi bi-clock-history me-2" style="color:#8b5cf6"></i>Timeline
                    </button>
                </li>
                <?php if (!empty($partage_info)): ?>
                <li class="nav-item">
                    <button class="nav-link d-flex align-items-center gap-1" data-bs-toggle="tab" data-bs-target="#tab-avis-partage" type="button" id="btn-tab-avis-partage">
                        <i class="bi bi-share-fill text-teal"></i>
                        <span>Avis</span>
                        <?php if (empty($partage_info['avis_medecin'])): ?>
                            <span class="badge rounded-pill ms-1" style="background:#0d9488;font-size:.65rem">À donner</span>
                        <?php else: ?>
                            <span class="badge bg-success rounded-pill ms-1" style="font-size:.65rem"><i class="bi bi-check-lg"></i></span>
                        <?php endif; ?>
                    </button>
                </li>
                <?php endif; ?>
            </ul>

            <div class="tab-content" id="myTabContent">
                <!-- CONTENU CONSULTATIONS -->
                <div class="tab-pane fade show active" id="tab-consultations">
                    <?php if(!empty($consultations)): foreach($consultations as $c):
                        $isInf = strtolower($c['type_consultation'] ?? '') === 'infirmiere';
                        $borderColor = $isInf ? '#0d9488' : '#3b82f6';
                        $typeLabel   = $isInf ? 'Consultation infirmière' : 'Consultation médicale';
                        $typeBg      = $isInf ? '#f0fdfa' : '#eff6ff';
                        $typeColor   = $isInf ? '#0d9488' : '#1e40af';
                        $auteurLabel = $isInf
                            ? 'Inf. '.htmlspecialchars($c['medecin_nom'] ?? '—')
                            : 'Dr. '.htmlspecialchars($c['medecin_nom'] ?? '—');
                    ?>
                        <div class="card mb-3 border-0 shadow-sm" id="consult-card-<?= $c['id'] ?>"
                             style="border-left:4px solid <?= $borderColor ?> !important;">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start gap-3">
                                    <div style="flex:1;min-width:0;">
                                        <!-- Badge type -->
                                        <div class="mb-1">
                                            <span style="display:inline-flex;align-items:center;gap:.3rem;
                                                         background:<?= $typeBg ?>;color:<?= $typeColor ?>;
                                                         border-radius:20px;padding:.15rem .65rem;
                                                         font-size:.68rem;font-weight:700;">
                                                <i class="bi <?= $isInf ? 'bi-clipboard2-pulse-fill' : 'bi-stethoscope' ?>"></i>
                                                <?= $typeLabel ?>
                                            </span>
                                            <?php if (!empty($c['devenir']) && $c['devenir'] !== 'EN_COURS'): ?>
                                            <?php
                                            $devenirLabel = ['SORTIE'=>'Sorti','HOSPITALISATION'=>'Hospitalisé','OBSERVATION'=>'En observation'][$c['devenir']] ?? $c['devenir'];
                                            $devenirColor = ['SORTIE'=>'#16a34a','HOSPITALISATION'=>'#1e40af','OBSERVATION'=>'#d97706'][$c['devenir']] ?? '#64748b';
                                            ?>
                                            <span style="display:inline-flex;align-items:center;gap:.3rem;
                                                         background:<?= $devenirColor ?>15;color:<?= $devenirColor ?>;
                                                         border-radius:20px;padding:.15rem .65rem;margin-left:4px;
                                                         font-size:.68rem;font-weight:700;">
                                                <?= $devenirLabel ?>
                                            </span>
                                            <?php endif; ?>
                                        </div>
                                        <h6 class="fw-bold mb-1" style="color:<?= $typeColor ?>;">
                                            <?= htmlspecialchars($c['motif_consultation'] ?: '(sans motif)') ?>
                                        </h6>
                                        <?php if (!empty($c['diagnostic_principal'])): ?>
                                        <p class="small mb-1 text-muted">
                                            <strong>Diagnostic :</strong> <?= htmlspecialchars($c['diagnostic_principal']) ?>
                                        </p>
                                        <?php endif; ?>
                                        <small class="text-muted">
                                            <i class="bi bi-person-badge me-1"></i><?= $auteurLabel ?>
                                        </small>
                                    </div>
                                    <div class="text-end flex-shrink-0">
                                        <span class="badge bg-light text-dark border mb-2">
                                            <?= date('d/m/Y H:i', strtotime($c['date_consultation'])) ?>
                                        </span><br>
                                        <div class="d-flex align-items-center gap-1 justify-content-end mt-1">
                                            <?php if (!$isInf): ?>
                                            <a href="<?= BASE_URL ?>consultation/recapitulatif/<?= $c['id'] ?>"
                                               class="btn btn-sm btn-outline-primary px-3 rounded-pill">
                                                Détails
                                            </a>
                                            <?php else: ?>
                                            <a href="<?= BASE_URL ?>consultation/recapitulatif/<?= $c['id'] ?>"
                                               class="btn btn-sm rounded-pill px-3"
                                               style="background:#f0fdfa;color:#0d9488;border:1px solid #99f6e4;">
                                                <i class="bi bi-eye me-1"></i>Voir
                                            </a>
                                            <?php endif; ?>
                                            <?php
                                            $peutSupprimer = in_array(strtoupper($_SESSION['user_role'] ?? ''), ['ADMIN','ADMINISTRATEUR','DIRECTEUR','MEDECIN_CHEF'])
                                                             || (int)($_SESSION['user_id'] ?? 0) === (int)($c['medecin_id'] ?? -1);
                                            if ($peutSupprimer): ?>
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-danger px-3 rounded-pill"
                                                    data-consult-id="<?= (int)$c['id'] ?>"
                                                    data-consult-motif="<?= htmlspecialchars($c['motif_consultation'] ?? '', ENT_QUOTES) ?>"
                                                    onclick="supprimerConsultation(this.dataset.consultId, this.dataset.consultMotif)">
                                                <i class="bi bi-trash3"></i>
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; else: ?>
                        <p class="text-center py-4 text-muted">Aucune consultation.</p>
                    <?php endif; ?>

                    <!-- Pagination consultations -->
                    <?php if (!empty($totalConsultPages) && $totalConsultPages > 1): ?>
                    <nav class="mt-3">
                        <ul class="pagination pagination-sm justify-content-center mb-0">
                            <li class="page-item <?= ($consultPage <= 1) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?consult_page=<?= $consultPage - 1 ?>#tab-consultations">
                                    <i class="bi bi-chevron-left"></i>
                                </a>
                            </li>
                            <?php for ($cp = 1; $cp <= $totalConsultPages; $cp++): ?>
                                <li class="page-item <?= ($cp === $consultPage) ? 'active' : '' ?>">
                                    <a class="page-link" href="?consult_page=<?= $cp ?>#tab-consultations"><?= $cp ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?= ($consultPage >= $totalConsultPages) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?consult_page=<?= $consultPage + 1 ?>#tab-consultations">
                                    <i class="bi bi-chevron-right"></i>
                                </a>
                            </li>
                        </ul>
                        <p class="text-center text-muted small mt-1">
                            Page <?= $consultPage ?> / <?= $totalConsultPages ?>
                            (<?= $totalConsultations ?> consultation<?= $totalConsultations > 1 ? 's' : '' ?> au total)
                        </p>
                    </nav>
                    <?php endif; ?>

                    <!-- Toast confirmation suppression -->
                    <div id="toastSuppression"
                         style="display:none;position:fixed;bottom:1.5rem;right:1.5rem;z-index:9999;
                                min-width:320px;background:#1e293b;color:#fff;border-radius:14px;
                                padding:1rem 1.4rem;box-shadow:0 8px 30px rgba(0,0,0,.3);
                                font-size:.875rem;font-weight:600;align-items:center;gap:.7rem;">
                        <i class="bi bi-check-circle-fill text-success" style="font-size:1.1rem;"></i>
                        <span id="toastMsg">Consultation supprimée.</span>
                    </div>

                    <script>
                    function supprimerConsultation(id, motif) {
                        if (!confirm(
                            '⚠️ Supprimer cette consultation ?\n\n' +
                            '"' + motif + '"\n\n' +
                            'Cette action est irréversible et supprimera également\n' +
                            'les ordonnances et demandes de bilans associées.'
                        )) return;

                        fetch('<?= BASE_URL ?>consultation/supprimer/' + id, {
                            method: 'POST',
                            headers: { 'X-Requested-With': 'XMLHttpRequest' }
                        })
                        .then(r => r.json())
                        .then(d => {
                            if (d.success) {
                                // Masquer la carte avec animation
                                const card = document.getElementById('consult-card-' + id);
                                if (card) {
                                    card.style.transition = 'opacity .35s, transform .35s';
                                    card.style.opacity = '0';
                                    card.style.transform = 'translateX(30px)';
                                    setTimeout(() => card.remove(), 370);
                                }
                                // Afficher toast
                                const toast = document.getElementById('toastSuppression');
                                document.getElementById('toastMsg').textContent = d.message;
                                toast.style.display = 'flex';
                                setTimeout(() => { toast.style.display = 'none'; }, 3000);
                            } else {
                                alert('❌ ' + (d.message || 'Erreur lors de la suppression.'));
                            }
                        })
                        .catch(() => alert('Erreur réseau. Veuillez réessayer.'));
                    }
                    </script>
                </div>

                <!-- CONTENU ANTÉCÉDENTS + ALLERGIES ÉDITABLES -->
                <div class="tab-pane fade" id="tab-antecedents">
                    <?php
                    $canEditAtcd = in_array(strtoupper($_SESSION['user_role']??''), ['MEDECIN','ADMIN','INFIRMIER_CONSULTANT','GENERALISTE','CHIRURGIEN']);
                    $atcdSections = [
                        'allergies'               => ['Allergies & contre-indications','#ef4444','bi-exclamation-triangle-fill','Saisir les allergies connues, intolérances médicamenteuses…'],
                        'antecedents_medicaux'    => ['Antécédents Médicaux','#3b82f6','bi-heart-pulse','HTA, diabète, cardiopathie, pathologies chroniques…'],
                        'antecedents_chirurgicaux'=> ['Antécédents Chirurgicaux','#f59e0b','bi-scissors','Interventions chirurgicales, dates approximatives…'],
                        'antecedents_familiaux'   => ['Antécédents Familiaux','#8b5cf6','bi-people','Maladies héréditaires, antécédents parentaux…'],
                    ];
                    foreach ($atcdSections as $champ => [$titre, $color, $icon, $placeholder]):
                        $valeur = $patient[$champ] ?? '';
                    ?>
                    <div style="background:#fff;border-radius:14px;border:1px solid #e2e8f0;border-left:4px solid <?= $color ?>;
                                padding:14px 16px;margin-bottom:12px;" id="atcd-block-<?= $champ ?>">
                        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
                            <h6 style="font-weight:800;color:<?= $color ?>;margin:0;font-size:.85rem">
                                <i class="bi <?= $icon ?> me-2"></i><?= $titre ?>
                            </h6>
                            <?php if ($canEditAtcd): ?>
                            <button onclick="editAtcd('<?= $champ ?>')" id="btn-edit-<?= $champ ?>"
                                    style="background:none;border:1.5px solid <?= $color ?>44;border-radius:8px;padding:3px 10px;
                                           font-size:.7rem;font-weight:700;color:<?= $color ?>;cursor:pointer;">
                                <i class="bi bi-pencil me-1"></i>Modifier
                            </button>
                            <?php endif; ?>
                        </div>
                        <!-- Affichage lecture -->
                        <div id="atcd-view-<?= $champ ?>" style="font-size:.84rem;color:<?= $valeur ? '#374151' : '#94a3b8' ?>">
                            <?= $valeur ? nl2br(htmlspecialchars($valeur)) : '<em>'.$placeholder.'</em>' ?>
                        </div>
                        <!-- Formulaire édition (caché par défaut) -->
                        <?php if ($canEditAtcd): ?>
                        <div id="atcd-edit-<?= $champ ?>" style="display:none;margin-top:8px;">
                            <textarea id="atcd-ta-<?= $champ ?>" rows="3"
                                      style="width:100%;border:1.5px solid <?= $color ?>44;border-radius:8px;padding:8px 12px;
                                             font-size:.83rem;resize:vertical;background:#fff;"
                                      placeholder="<?= $placeholder ?>"><?= htmlspecialchars($valeur) ?></textarea>
                            <div style="display:flex;gap:6px;margin-top:6px">
                                <button onclick="sauvegarderAtcd('<?= $champ ?>')"
                                        style="background:<?= $color ?>;color:#fff;border:none;border-radius:8px;padding:5px 14px;
                                               font-size:.76rem;font-weight:700;cursor:pointer;">
                                    <i class="bi bi-floppy-fill me-1"></i>Sauvegarder
                                </button>
                                <button onclick="annulerEditAtcd('<?= $champ ?>')"
                                        style="background:#f1f5f9;color:#64748b;border:none;border-radius:8px;padding:5px 12px;
                                               font-size:.76rem;cursor:pointer;">
                                    Annuler
                                </button>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>

                    <script>
                    function editAtcd(champ) {
                        document.getElementById('atcd-view-'+champ).style.display='none';
                        document.getElementById('atcd-edit-'+champ).style.display='block';
                        document.getElementById('btn-edit-'+champ).style.display='none';
                    }
                    function annulerEditAtcd(champ) {
                        document.getElementById('atcd-view-'+champ).style.display='';
                        document.getElementById('atcd-edit-'+champ).style.display='none';
                        document.getElementById('btn-edit-'+champ).style.display='';
                    }
                    function sauvegarderAtcd(champ) {
                        const val = document.getElementById('atcd-ta-'+champ).value;
                        const fd = new FormData();
                        fd.append('patient_id', '<?= (int)$patient['id'] ?>');
                        fd.append('champ', champ);
                        fd.append('valeur', val);
                        fetch('<?= BASE_URL ?>patients/update-allergies', { method:'POST', body:fd })
                            .then(r=>r.json())
                            .then(d=>{
                                if (d.success) {
                                    const view = document.getElementById('atcd-view-'+champ);
                                    view.style.color = val ? '#374151' : '#94a3b8';
                                    view.innerHTML = val ? val.replace(/\n/g,'<br>') : '<em>Non renseigné</em>';
                                    annulerEditAtcd(champ);
                                    // Rafraîchir badge allergie si champ=allergies
                                    if (champ === 'allergies') {
                                        document.querySelectorAll('.ab-chip-danger .ab-chip-detail, .ab-chip-neutral span').forEach(el=>{
                                            el.textContent = val ? val.substring(0,28)+(val.length>28?'…':'') : 'Aucune allergie';
                                        });
                                    }
                                } else {
                                    alert('Erreur : ' + (d.message||'Inconnue'));
                                }
                            });
                    }
                    </script>
                </div>

                <!-- CONTENU MÉDICAMENTS PRESCRITS -->
                <div class="tab-pane fade" id="tab-prescriptions">
                    <?php if (!empty($prescriptions)):
                        // Grouper par ordonnance
                        $grouped = [];
                        foreach ($prescriptions as $p) {
                            $key = $p['prescription_id'];
                            if (!isset($grouped[$key])) {
                                $grouped[$key] = [
                                    'date'             => $p['date_prescription'],
                                    'numero'           => $p['numero_ordonnance'] ?? null,
                                    'statut'           => $p['statut_prescription'],
                                    'type_ordonnance'  => $p['type_ordonnance'] ?? 'NORMALE',
                                    'medecin'          => trim(($p['medecin_prenom'] ?? '').' '.($p['medecin_nom'] ?? '')),
                                    'infirmier'        => trim(($p['infirmier_prenom'] ?? '').' '.($p['infirmier_nom'] ?? '')),
                                    'lignes'           => []
                                ];
                            }
                            $grouped[$key]['lignes'][] = $p;
                        }
                    ?>
                        <?php foreach ($grouped as $pres):
                            $isParOrdre = ($pres['type_ordonnance'] === 'PAR_ORDRE');
                            $borderColor = $isParOrdre ? '#f59e0b' : '#f59e0b';
                            $headerBg    = $isParOrdre ? '#fffbeb' : '#fff';
                        ?>
                            <div class="card mb-3 border-0 shadow-sm"
                                 style="border-left:4px solid <?= $borderColor ?> !important;border-left-style:solid !important;">
                                <div class="card-body pb-2" style="background:<?= $headerBg ?>0a;border-radius:inherit;">
                                    <!-- En-tête ordonnance -->
                                    <div class="d-flex justify-content-between align-items-start mb-2 flex-wrap gap-2">
                                        <div>
                                            <span class="fw-bold" style="color:#b45309;">
                                                <i class="bi bi-receipt me-1"></i>Ordonnance
                                            </span>
                                            <?php if ($isParOrdre): ?>
                                                <span class="badge ms-1 rounded-pill"
                                                      style="background:#fef3c7;color:#92400e;border:1px solid #fcd34d;font-size:.65rem;font-weight:700;">
                                                    P/O
                                                </span>
                                            <?php endif; ?>
                                            <?php if (!empty($pres['numero'])): ?>
                                                <small class="text-muted ms-1"><?= htmlspecialchars($pres['numero']) ?></small>
                                            <?php endif; ?>
                                        </div>
                                        <div class="d-flex align-items-center gap-1 flex-wrap">
                                            <span class="badge bg-light text-dark border" style="font-size:.72rem;">
                                                <?= date('d/m/Y', strtotime($pres['date'])) ?>
                                            </span>
                                            <?php
                                                $sc = match($pres['statut'] ?? '') {
                                                    'EN_ATTENTE' => 'background:#fef3c7;color:#92400e;border:1px solid #fcd34d',
                                                    'SIGNEE'     => 'background:#d1fae5;color:#065f46;border:1px solid #6ee7b7',
                                                    'TERMINEE'   => 'background:#d1fae5;color:#065f46;border:1px solid #6ee7b7',
                                                    'PARTIEL'    => 'background:#dbeafe;color:#1e40af;border:1px solid #93c5fd',
                                                    'ANNULEE'    => 'background:#fee2e2;color:#991b1b;border:1px solid #fca5a5',
                                                    default      => 'background:#f1f5f9;color:#475569;border:1px solid #e2e8f0',
                                                };
                                            ?>
                                            <span class="badge rounded-pill" style="<?= $sc ?>;font-size:.65rem;font-weight:700;">
                                                <?= htmlspecialchars($pres['statut'] ?? '—') ?>
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Prescripteur -->
                                    <div class="mb-2" style="font-size:.78rem;">
                                        <?php if ($isParOrdre && !empty($pres['infirmier'])): ?>
                                            <span class="text-muted">
                                                <i class="bi bi-person-fill me-1"></i>
                                                Inf. <strong><?= htmlspecialchars($pres['infirmier']) ?></strong>
                                                &nbsp;—&nbsp; Signature : Dr. <?= htmlspecialchars($pres['medecin'] ?: '—') ?>
                                            </span>
                                        <?php elseif (!empty($pres['medecin'])): ?>
                                            <span class="text-muted">
                                                <i class="bi bi-person-fill me-1"></i>
                                                Dr. <?= htmlspecialchars($pres['medecin']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Tableau médicaments -->
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover mb-0" style="font-size:.82rem;">
                                            <thead style="background:#f8fafc;">
                                                <tr class="text-muted" style="font-size:.68rem;text-transform:uppercase;letter-spacing:.4px;">
                                                    <th class="ps-2">Médicament</th>
                                                    <th>Posologie</th>
                                                    <th>Voie</th>
                                                    <th>Fréquence</th>
                                                    <th>Durée</th>
                                                    <th class="text-end pe-2">Qté</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($pres['lignes'] as $l): ?>
                                                    <tr style="border-top:1px solid #f1f5f9;">
                                                        <td class="ps-2 py-2">
                                                            <div class="fw-bold" style="color:#1e293b;">
                                                                <?= htmlspecialchars($l['medicament_nom']) ?>
                                                            </div>
                                                            <?php $detail = trim(($l['forme'] ?? '').' '.($l['dosage'] ?? '')); ?>
                                                            <?php if ($detail): ?>
                                                                <small class="text-muted"><?= htmlspecialchars($detail) ?></small>
                                                            <?php endif; ?>
                                                            <?php if (!empty($l['hors_stock'])): ?>
                                                                <span class="badge rounded-pill ms-1"
                                                                      style="background:#fff7ed;color:#c2410c;border:1px solid #fed7aa;font-size:.6rem;">
                                                                    ⚠ Hors stock
                                                                </span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td class="py-2"><?= htmlspecialchars($l['posologie'] ?? '—') ?></td>
                                                        <td class="py-2"><?= htmlspecialchars($l['voie'] ?? '—') ?></td>
                                                        <td class="py-2"><?= htmlspecialchars($l['frequence'] ?? '—') ?></td>
                                                        <td class="py-2"><?= htmlspecialchars($l['duree'] ?? '—') ?></td>
                                                        <td class="text-end pe-2 py-2 fw-bold" style="color:#1d4ed8;">
                                                            <?= $l['quantite'] ?? '—' ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-capsule fs-1 d-block mb-2 opacity-25"></i>
                            Aucune prescription enregistrée.
                        </div>
                    <?php endif; ?>
                </div>

                <!-- CONTENU BILANS DEMANDÉS + RÉSULTATS -->
                <div class="tab-pane fade" id="tab-bilans-demandes">
                <style>
                    .com-bilan { background:#f0fdf4; border-left:3px solid #16a34a; border-radius:0 8px 8px 0; padding:8px 12px; margin-top:6px; }
                    .com-bilan .com-dr { font-size:.72rem; color:#64748b; font-weight:700; }
                    .com-bilan .com-txt { font-size:.82rem; color:#1e293b; margin-top:2px; }
                    .bilan-section-title { font-size:.78rem; font-weight:800; text-transform:uppercase; letter-spacing:.8px; padding:8px 0 6px; margin-bottom:0; }
                </style>
                <?php
                    $statuts_map = [
                        // Statuts labo
                        'EN_ATTENTE'             => ['bg-warning text-dark', 'En attente'],
                        'DISPATCHE'              => ['bg-warning text-dark', 'En attente'],
                        'dispatche'              => ['bg-warning text-dark', 'En attente'],
                        'PRELEVEMENTS_EFFECTUES' => ['bg-info text-white',   'Prélevé'],
                        'EN_ANALYSE'             => ['bg-primary',           'En analyse'],
                        'RESULTATS_PRETS'        => ['bg-success',           'Résultat prêt'],
                        'VALIDES'                => ['bg-success',           'Validé'],
                        'VALIDE'                 => ['bg-success',           'Validé'],
                        'SOUMIS'                 => ['bg-info text-white',   'Soumis'],
                        'INTERPRETE'             => ['bg-success',           'Interprété'],
                        'TERMINE'                => ['bg-secondary',         'Terminé'],
                        // Statuts imagerie
                        'EN_COURS'               => ['bg-primary',           'En cours'],
                        'REALISE'                => ['bg-success',           'Réalisé'],
                        'ANNULE'                 => ['bg-danger',            'Annulé'],
                    ];
                    $hasBilans = !empty($bilans_demandes) || !empty($bilans_imagerie);
                ?>
                <?php if (!$hasBilans): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-flask fs-1 d-block mb-2 opacity-25"></i>
                        Aucun bilan demandé pour ce patient.
                    </div>
                <?php else: ?>

                    <?php if (!empty($bilans_demandes)): ?>
                    <!-- SECTION LABORATOIRE -->
                    <div class="d-flex align-items-center gap-2 mb-2 mt-1">
                        <i class="bi bi-droplet-fill text-primary"></i>
                        <span class="bilan-section-title text-primary">Laboratoire</span>
                        <span class="badge bg-primary rounded-pill"><?= count($bilans_demandes) ?></span>
                    </div>
                    <div class="table-responsive mb-4">
                        <table class="table table-hover align-middle border-0">
                            <thead class="bg-light">
                                <tr class="small text-uppercase text-muted">
                                    <th>Examen</th>
                                    <th>Demandé le</th>
                                    <th>Médecin</th>
                                    <th class="text-center">Statut</th>
                                    <th>Résultat</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                    $statuts_avec_resultats = ['RESULTATS_PRETS','VALIDES','SOUMIS','VALIDE','INTERPRETE'];
                                ?>
                                <?php foreach ($bilans_demandes as $b):
                                    $hasResult = !empty($b['date_resultat']);
                                    [$scls, $stxt] = $statuts_map[$b['statut']] ?? ['bg-secondary', $b['statut']];
                                    $resultatDispo = in_array($b['statut'], $statuts_avec_resultats);
                                ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold"><?= htmlspecialchars($b['nom_examen'] ?? 'Examen') ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($b['categorie'] ?? '') ?></small>
                                    </td>
                                    <td><small><?= date('d/m/Y', strtotime($b['date_creation'])) ?></small></td>
                                    <td><small>Dr. <?= htmlspecialchars(trim(($b['medecin_nom'] ?? '') . ' ' . ($b['medecin_prenom'] ?? ''))) ?></small></td>
                                    <td class="text-center"><span class="badge rounded-pill <?= $scls ?>"><?= $stxt ?></span></td>
                                    <td>
                                        <?php if ($hasResult && !empty($b['resultat'])): ?>
                                            <small><?= htmlspecialchars(substr($b['resultat'], 0, 100)) ?></small>
                                        <?php elseif ($hasResult && !empty($b['interpretation'])): ?>
                                            <small><?= htmlspecialchars(substr($b['interpretation'], 0, 100)) ?></small>
                                        <?php else: ?>
                                            <small class="text-muted">—</small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-2">
                                        <?php if ($resultatDispo): ?>
                                            <a href="<?= BASE_URL ?>laboratoire/imprimer-resultats/<?= (int)$b['id'] ?>"
                                               class="btn btn-sm btn-outline-success"
                                               title="Voir les résultats"
                                               target="_blank">
                                                <i class="bi bi-eye me-1"></i>Voir résultats
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($bilans_imagerie)): ?>
                    <!-- SECTION IMAGERIE / RADIOLOGIE -->
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="bi bi-image text-purple" style="color:#8b5cf6"></i>
                        <span class="bilan-section-title" style="color:#8b5cf6">Imagerie & Radiologie</span>
                        <span class="badge rounded-pill" style="background:#8b5cf6"><?= count($bilans_imagerie) ?></span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle border-0">
                            <thead class="bg-light">
                                <tr class="small text-uppercase text-muted">
                                    <th>Examen</th>
                                    <th>Demandé le</th>
                                    <th>Médecin</th>
                                    <th class="text-center">Statut</th>
                                    <th>Résultat / Interprétation</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bilans_imagerie as $b):
                                    $hasResult = !empty($b['date_resultat']) || !empty($b['resultat']);
                                    [$scls, $stxt] = $statuts_map[$b['statut']] ?? ['bg-secondary', $b['statut']];
                                    $typeIcon = match($b['type_examen'] ?? '') {
                                        'scanner'       => 'bi-layers',
                                        'irm'           => 'bi-magnet',
                                        'echographie'   => 'bi-soundwave',
                                        'mammographie'  => 'bi-heart',
                                        'radiographie'  => 'bi-film',
                                        default         => 'bi-image',
                                    };
                                ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold">
                                            <i class="bi <?= $typeIcon ?> me-1" style="color:#8b5cf6"></i>
                                            <?= htmlspecialchars(ucfirst($b['type_examen'] ?? 'Imagerie')) ?>
                                        </div>
                                        <small class="text-muted"><?= htmlspecialchars($b['partie_corps'] ?? '') ?>
                                            <?= $b['avec_contraste'] ? ' <span class="badge bg-secondary ms-1" style="font-size:.65rem">Contraste</span>' : '' ?>
                                        </small>
                                        <?php if (!empty($b['description'])): ?>
                                            <small class="d-block text-muted fst-italic"><?= htmlspecialchars(substr($b['description'], 0, 60)) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><small><?= date('d/m/Y', strtotime($b['date_creation'])) ?></small></td>
                                    <td><small>Dr. <?= htmlspecialchars(trim(($b['medecin_nom'] ?? '') . ' ' . ($b['medecin_prenom'] ?? ''))) ?></small></td>
                                    <td class="text-center"><span class="badge rounded-pill <?= $scls ?>"><?= $stxt ?></span></td>
                                    <td>
                                        <?php if (!empty($b['resultat'])): ?>
                                            <small><?= htmlspecialchars(substr($b['resultat'], 0, 100)) ?></small>
                                        <?php elseif (!empty($b['interpretation'])): ?>
                                            <small><?= htmlspecialchars(substr($b['interpretation'], 0, 100)) ?></small>
                                        <?php else: ?>
                                            <small class="text-muted">—</small>
                                        <?php endif; ?>
                                        <?php if (!empty($b['fichier_dicom']) || !empty($b['fichier_preview'])): ?>
                                            <a href="<?= BASE_URL ?>imagerie/viewer/<?= $b['id'] ?>?from=patients/dossier/<?= $patient['id'] ?>"
                                               class="btn btn-sm btn-outline-primary py-0 px-2 ms-1"
                                               style="font-size:.72rem"
                                               title="Visualiser l'image / fichier DICOM">
                                                <i class="bi bi-eye-fill me-1"></i>Voir le fichier
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>

                <?php endif; ?>
                </div>

                <!-- CONTENU MES DOCUMENTS -->
                <div class="tab-pane fade" id="tab-documents">
                    <?php if (!empty($comptes_rendus)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle border-0">
                                <thead class="bg-light">
                                    <tr class="small text-uppercase text-muted">
                                        <th>Type de document</th>
                                        <th>Date d'entrée</th>
                                        <th>Date de sortie</th>
                                        <th>Médecin</th>
                                        <th class="text-center">Statut</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($comptes_rendus as $crh): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="bg-primary bg-opacity-10 text-primary rounded p-2 me-2">
                                                        <i class="bi bi-file-earmark-text"></i>
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold">Compte-rendu d'hospitalisation</div>
                                                        <small class="text-muted">Créé le <?= date('d/m/Y', strtotime($crh['created_at'])) ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><small><?= $crh['date_entree'] ? date('d/m/Y', strtotime($crh['date_entree'])) : '—' ?></small></td>
                                            <td><small><?= $crh['date_sortie'] ? date('d/m/Y', strtotime($crh['date_sortie'])) : '—' ?></small></td>
                                            <td><small>Dr. <?= htmlspecialchars($crh['medecin_nom'] . ' ' . $crh['medecin_prenom']) ?></small></td>
                                            <td class="text-center">
                                                <?php if ($crh['signe']): ?>
                                                    <span class="badge rounded-pill bg-success">
                                                        <i class="bi bi-patch-check-fill me-1"></i>Signé
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge rounded-pill bg-warning text-dark">Non signé</span>
                                                <?php endif; ?>
                                            </td>
                                                            <td class="text-end">
                                                <a href="<?= BASE_URL ?>formulaire/voir-crh/<?= $crh['id'] ?>"
                                                   class="btn btn-sm btn-outline-primary rounded-pill px-3 me-1"
                                                   target="_blank">
                                                    <i class="bi bi-eye me-1"></i>Consulter
                                                </a>
                                                <a href="<?= BASE_URL ?>formulaire/voir-crh/<?= $crh['id'] ?>"
                                                   class="btn btn-sm btn-outline-secondary rounded-pill px-3"
                                                   onclick="setTimeout(() => window.print(), 500); return false;"
                                                   target="_blank">
                                                    <i class="bi bi-printer me-1"></i>Imprimer
                                                </a>
                                                <?php if ($_SESSION['user_role'] === 'ADMIN'): ?>
                                                <button type="button"
                                                        class="btn btn-sm btn-outline-danger rounded-pill px-2 ms-1"
                                                        title="Supprimer ce compte-rendu"
                                                        onclick="supprimerCRH(<?= $crh['id'] ?>, this)">
                                                    <i class="bi bi-trash3-fill"></i>
                                                </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-file-earmark-x fs-1 d-block mb-2 opacity-25"></i>
                            Aucun compte-rendu d'hospitalisation disponible.
                        </div>
                    <?php endif; ?>

                    <!-- Documents uploadés (radios, PDF, etc.) -->
                    <?php if (!empty($documents)): ?>
                    <div class="mt-4">
                        <h6 class="fw-bold text-muted mb-3 small text-uppercase">
                            <i class="bi bi-paperclip me-2"></i>Fichiers joints (<?= count($documents) ?>)
                        </h6>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle border-0">
                                <thead class="bg-light">
                                    <tr class="small text-uppercase text-muted">
                                        <th>Fichier</th>
                                        <th>Catégorie</th>
                                        <th>Description</th>
                                        <th>Date</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($documents as $doc):
                                        $ext = strtolower(pathinfo($doc['nom_fichier'], PATHINFO_EXTENSION));
                                        $icon = in_array($ext, ['pdf']) ? 'bi-file-earmark-pdf text-danger'
                                              : (in_array($ext, ['jpg','jpeg','png','gif','webp']) ? 'bi-file-earmark-image text-success'
                                              : 'bi-file-earmark text-secondary');
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <i class="bi <?= $icon ?> fs-4 me-2"></i>
                                                <div>
                                                    <div class="fw-semibold" style="font-size:.85rem;"><?= htmlspecialchars($doc['nom_fichier']) ?></div>
                                                    <small class="text-muted"><?= strtoupper($ext) ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><span class="badge bg-secondary-subtle text-secondary rounded-pill"><?= htmlspecialchars($doc['categorie'] ?? '—') ?></span></td>
                                        <td><small class="text-muted"><?= htmlspecialchars(mb_strimwidth($doc['description'] ?? '', 0, 60, '…')) ?></small></td>
                                        <td><small><?= date('d/m/Y', strtotime($doc['date_upload'])) ?></small></td>
                                        <td class="text-end">
                                            <a href="<?= BASE_URL ?>public/uploads/documents/<?= htmlspecialchars($doc['chemin_fichier']) ?>"
                                               class="btn btn-sm btn-outline-primary rounded-pill px-3 me-1"
                                               target="_blank">
                                                <i class="bi bi-eye me-1"></i>Voir
                                            </a>
                                            <?php if ($_SESSION['user_role'] === 'ADMIN'): ?>
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-danger rounded-pill px-2"
                                                    title="Supprimer ce document"
                                                    onclick="supprimerDocument(<?= $doc['id'] ?>, this)">
                                                <i class="bi bi-trash3-fill"></i>
                                            </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- CONTENU CONSULTATIONS PÉDIATRIQUES -->
                <?php if ($isPedService): ?>
                <div class="tab-pane fade" id="tab-ped">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold mb-0"><i class="bi bi-heart-pulse-fill text-primary me-2"></i>Consultations Pédiatriques</h6>
                        <a href="<?= BASE_URL ?>consultation-ped/formulaire/<?= $patient['id'] ?>?step=1"
                           class="btn btn-sm btn-primary rounded-pill px-3">
                            <i class="bi bi-plus-circle me-1"></i>Nouvelle consultation pédiatrique
                        </a>
                    </div>
                    <?php
                    // Charger les consultations pédiatriques pour ce patient
                    $db_tmp = (new Database())->getConnection();
                    $stmtPed = $db_tmp->prepare("
                        SELECT cp.id, cp.date_consultation, cp.heure_consultation, cp.motif_consultation,
                               cp.diag_positif, cp.statut,
                               u.nom AS medecin_nom, u.prenom AS medecin_prenom
                        FROM consultations_pediatriques cp
                        JOIN users u ON cp.medecin_id = u.id
                        WHERE cp.patient_id = ?
                        ORDER BY cp.date_consultation DESC, cp.heure_consultation DESC
                    ");
                    $stmtPed->execute([$patient['id']]);
                    $consultsPed = $stmtPed->fetchAll(PDO::FETCH_ASSOC);
                    ?>
                    <?php if (!empty($consultsPed)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle border-0">
                            <thead class="bg-light">
                                <tr class="small text-uppercase text-muted">
                                    <th>Date</th>
                                    <th>Motif</th>
                                    <th>Diagnostic</th>
                                    <th>Médecin</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($consultsPed as $cp): ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-primary bg-opacity-10 text-primary fw-semibold">
                                            <?= date('d/m/Y', strtotime($cp['date_consultation'])) ?>
                                        </span>
                                    </td>
                                    <td><small><?= htmlspecialchars(mb_strimwidth($cp['motif_consultation'] ?? '—', 0, 60, '...')) ?></small></td>
                                    <td><small class="text-success fw-semibold"><?= htmlspecialchars(mb_strimwidth($cp['diag_positif'] ?? '—', 0, 50, '...')) ?></small></td>
                                    <td><small>Dr. <?= htmlspecialchars($cp['medecin_prenom'] . ' ' . $cp['medecin_nom']) ?></small></td>
                                    <td class="text-end">
                                        <a href="<?= BASE_URL ?>consultation-ped/voir/<?= $cp['id'] ?>"
                                           class="btn btn-sm btn-outline-primary rounded-pill px-3" target="_blank">
                                            <i class="bi bi-eye me-1"></i>Voir
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-heart-pulse fs-1 d-block mb-2 opacity-25"></i>
                        Aucune consultation pédiatrique enregistrée.<br>
                        <a href="<?= BASE_URL ?>consultation-ped/formulaire/<?= $patient['id'] ?>?step=1"
                           class="btn btn-primary mt-3 rounded-pill px-4">
                            <i class="bi bi-plus-circle me-1"></i>Démarrer une consultation
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- CONTENU BILANS -->
                <div class="tab-pane fade" id="tab-bilans">
                    <?php if (!empty($bilans)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle border-0">
                                <thead class="bg-light">
                                    <tr class="small text-uppercase text-muted">
                                        <th>Date & Examen</th>
                                        <th>Résultat</th>
                                        <th>Valeurs de Réf.</th>
                                        <th class="text-center">Statut</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($bilans as $b):
                                        $isAnormal = ($b['anormal'] == 1);
                                    ?>
                                        <tr class="<?= $isAnormal ? 'table-danger-light' : '' ?>">
                                            <td>
                                                <div class="fw-bold"><?= htmlspecialchars($b['nom_examen']) ?></div>
                                                <small class="text-muted">le <?= date('d/m/Y', strtotime($b['date_resultat'])) ?></small>
                                            </td>
                                            <td><span class="fs-5 <?= $isAnormal ? 'text-danger fw-bold' : '' ?>"><?= $b['valeur_numerique'] ?> <small class="fs-6 text-muted"><?= $b['unite'] ?></small></span></td>
                                            <td><small class="text-muted">Norme: <?= $b['valeur_normale_min'] ?> - <?= $b['valeur_normale_max'] ?></small></td>
                                            <td class="text-center">
                                                <span class="badge rounded-pill bg-<?= $isAnormal ? 'danger' : 'success' ?>">
                                                    <?= $isAnormal ? 'ANORMAL' : 'NORMAL' ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5 text-muted">Aucun résultat disponible.</div>
                    <?php endif; ?>
                </div>

                <!-- ══ ONGLET AVIS PARTAGÉ (visible uniquement si dossier partagé actif) ══ -->
                <?php if (!empty($partage_info)): ?>
                <div class="tab-pane fade" id="tab-avis-partage">
                    <div class="p-4">

                        <!-- Bandeau d'info partage -->
                        <div class="alert d-flex align-items-center gap-3 mb-4 rounded-3 border-0"
                             style="background:linear-gradient(135deg,#f0fdfa,#ccfbf1);border-left:4px solid #0d9488 !important;border-left-style:solid !important;">
                            <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                                 style="width:42px;height:42px;background:#0d9488;">
                                <i class="bi bi-share-fill text-white fs-5"></i>
                            </div>
                            <div>
                                <div class="fw-bold text-dark">Dossier partagé par
                                    Dr. <?= htmlspecialchars(($partage_info['expediteur_prenom'] ?? '').' '.($partage_info['expediteur_nom'] ?? '')) ?>
                                    <?php if (!empty($partage_info['expediteur_specialite'])): ?>
                                        <span class="text-muted fw-normal">(<?= htmlspecialchars($partage_info['expediteur_specialite']) ?>)</span>
                                    <?php endif; ?>
                                </div>
                                <small class="text-muted">
                                    <i class="bi bi-clock me-1"></i>Partagé le <?= date('d/m/Y à H:i', strtotime($partage_info['date_partage'])) ?>
                                    &nbsp;·&nbsp;
                                    <i class="bi bi-hourglass-split me-1"></i>Expire le <?= date('d/m/Y à H:i', strtotime($partage_info['date_expiration'])) ?>
                                </small>
                            </div>
                        </div>

                        <!-- Avis existant (lecture) si déjà renseigné -->
                        <?php if (!empty($partage_info['avis_medecin'])): ?>
                        <div class="card border-0 shadow-sm rounded-3 mb-4" style="border-left:4px solid #0d9488 !important;border-left-style:solid !important;">
                            <div class="card-header py-2 px-3" style="background:#f0fdfa;border-bottom:1px solid #ccfbf1;">
                                <span class="fw-bold small text-teal" style="color:#0d9488"><i class="bi bi-check-circle-fill me-1"></i>Avis déjà enregistré</span>
                            </div>
                            <div class="card-body p-3">
                                <p class="mb-0" style="white-space:pre-wrap;line-height:1.7"><?= nl2br(htmlspecialchars($partage_info['avis_medecin'])) ?></p>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Formulaire de saisie / modification de l'avis -->
                        <div class="card border-0 shadow-sm rounded-3">
                            <div class="card-header py-2 px-3 fw-bold small" style="background:#f8fafc;">
                                <i class="bi bi-pencil-square me-1 text-primary"></i>
                                <?= empty($partage_info['avis_medecin']) ? 'Donner mon avis médical' : 'Modifier mon avis' ?>
                            </div>
                            <div class="card-body p-3">
                                <form method="POST" action="<?= BASE_URL ?>patients/save-avis-partage">
                                    <input type="hidden" name="partage_id"  value="<?= $partage_info['id'] ?>">
                                    <input type="hidden" name="patient_id"  value="<?= $patient['id'] ?>">
                                    <div class="mb-3">
                                        <label class="form-label small fw-semibold text-muted">Votre avis / conclusions</label>
                                        <textarea name="avis_medecin" class="form-control rounded-3"
                                                  rows="6"
                                                  placeholder="Rédigez votre avis médical, conclusions diagnostiques, recommandations thérapeutiques…"
                                                  style="font-size:.92rem;line-height:1.7;resize:vertical"
                                        ><?= htmlspecialchars($partage_info['avis_medecin'] ?? '') ?></textarea>
                                    </div>
                                    <div class="d-flex justify-content-end gap-2">
                                        <button type="submit" class="btn btn-sm px-4 text-white fw-bold rounded-pill"
                                                style="background:#0d9488">
                                            <i class="bi bi-send me-1"></i>
                                            <?= empty($partage_info['avis_medecin']) ? 'Envoyer l\'avis' : 'Mettre à jour' ?>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                    </div>
                </div>
                <?php endif; ?>

                <!-- ── ONGLET HOSPITALISATION (déplacé ici : dans le tab-content principal) ── -->
                <?php if ($hospitalisation): ?>
                <div class="tab-pane fade" id="tab-hospitalisation">
                    <?php
                    $hosStatut  = $hospitalisation['statut'] ?? '';
                    $hosEnCours = ($hosStatut === 'en_cours');
                    $hosAdmis   = !empty($hospitalisation['date_admission']) ? date('d/m/Y H:i', strtotime($hospitalisation['date_admission'])) : '--';
                    $hosSortie  = !empty($hospitalisation['date_sortie_effective']) ? date('d/m/Y H:i', strtotime($hospitalisation['date_sortie_effective'])) : null;
                    $dureeJours = '';
                    if (!empty($hospitalisation['date_admission'])) {
                        $fin = !empty($hospitalisation['date_sortie_effective']) ? new DateTime($hospitalisation['date_sortie_effective']) : new DateTime();
                        $dureeJours = (new DateTime($hospitalisation['date_admission']))->diff($fin)->days . ' jour(s)';
                    }
                    ?>
                    <?php if ($hosEnCours): ?>
                    <div style="background:linear-gradient(135deg,#1e40af,#3b82f6);border-radius:14px;padding:16px 20px;margin-bottom:18px;color:#fff;display:flex;align-items:center;gap:12px;">
                        <div style="width:44px;height:44px;background:rgba(255,255,255,.2);border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="bi bi-hospital-fill" style="font-size:1.3rem"></i>
                        </div>
                        <div>
                            <div style="font-weight:800;font-size:.95rem">Hospitalisé depuis <?= $dureeJours ?></div>
                            <div style="font-size:.78rem;opacity:.8">Admis le <?= $hosAdmis ?></div>
                        </div>
                        <div style="margin-left:auto;text-align:right">
                            <div style="font-size:.72rem;opacity:.7">Service</div>
                            <div style="font-weight:700"><?= htmlspecialchars($hospitalisation['nom_service'] ?? '--') ?></div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:18px;">
                    <?php
                    $hosInfos = [
                        ['Chambre',       $hospitalisation['nom_chambre']??'--',  'bi-door-open-fill',     '#1e40af'],
                        ['Lit',           $hospitalisation['nom_lit']??'--',      'bi-hospital',           '#059669'],
                        ['Service',       $hospitalisation['nom_service']??'--',  'bi-building-fill',      '#7c3aed'],
                        ['Médecin resp.', trim(($hospitalisation['medecin_resp_prenom']??'').' '.($hospitalisation['medecin_resp_nom']??'')) ?: '--', 'bi-person-badge-fill','#0284c7'],
                        ['Admission',     $hosAdmis,                              'bi-calendar-plus',      '#d97706'],
                        ['Durée',         $dureeJours,                            'bi-hourglass-split',    '#64748b'],
                    ];
                    foreach ($hosInfos as [$lbl,$val,$ico,$col]):
                    ?>
                    <div style="background:#f8fafc;border-radius:12px;padding:14px 16px;border:1px solid #e2e8f0;">
                        <div style="font-size:.68rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;margin-bottom:5px">
                            <i class="bi <?= $ico ?>" style="color:<?= $col ?>"></i> <?= $lbl ?>
                        </div>
                        <div style="font-weight:700;color:#0f172a;font-size:.87rem"><?= htmlspecialchars($val) ?></div>
                    </div>
                    <?php endforeach; ?>
                    </div>

                    <?php if (!empty($hospitalisation['motif_hospitalisation'])): ?>
                    <div style="background:#eff6ff;border-radius:12px;padding:14px 16px;border-left:4px solid #3b82f6;margin-bottom:12px;">
                        <div style="font-size:.72rem;font-weight:700;color:#1e40af;text-transform:uppercase;margin-bottom:4px">Motif d'hospitalisation</div>
                        <div style="font-size:.85rem;color:#374151"><?= nl2br(htmlspecialchars($hospitalisation['motif_hospitalisation'])) ?></div>
                    </div>
                    <?php endif; ?>

                    <?php if (!$hosEnCours && $hosSortie): ?>
                    <div style="background:#f0fdf4;border-radius:12px;padding:14px 16px;border-left:4px solid #22c55e;margin-bottom:12px;">
                        <div style="font-size:.72rem;font-weight:700;color:#15803d;text-transform:uppercase;margin-bottom:4px">Date de sortie</div>
                        <div style="font-size:.85rem;color:#374151"><?= $hosSortie ?></div>
                    </div>
                    <?php endif; ?>

                    <?php if (count($historique_hospit) > 1): ?>
                    <div style="font-size:.75rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;margin-top:8px">
                        <i class="bi bi-clock-history me-1"></i>Historique (<?= count($historique_hospit) ?> hospitalisations)
                    </div>
                    <?php foreach ($historique_hospit as $i => $h): if ($i===0) continue; ?>
                    <div style="display:flex;align-items:center;gap:10px;padding:8px 12px;border-radius:10px;background:#f8fafc;border:1px solid #e2e8f0;margin-bottom:6px;">
                        <i class="bi bi-hospital text-muted" style="font-size:.95rem;flex-shrink:0"></i>
                        <div style="flex:1">
                            <div style="font-size:.79rem;font-weight:700;color:#374151"><?= htmlspecialchars($h['nom_service']??'Service') ?></div>
                            <div style="font-size:.71rem;color:#94a3b8"><?= !empty($h['date_admission'])?date('d/m/Y',strtotime($h['date_admission'])):'--' ?> → <?= !empty($h['date_sortie_effective'])?date('d/m/Y',strtotime($h['date_sortie_effective'])):'En cours' ?></div>
                        </div>
                        <span style="font-size:.67rem;font-weight:700;padding:3px 8px;border-radius:20px;background:<?= ($h['statut']??'')==='en_cours'?'#d1fae5':'#f1f5f9' ?>;color:<?= ($h['statut']??'')==='en_cours'?'#065f46':'#475569' ?>">
                            <?= ($h['statut']??'')==='en_cours'?'En cours':'Sorti' ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- ── ONGLET TIMELINE (dans le tab-content principal) ── -->
                <div class="tab-pane fade" id="tab-timeline">
                    <?php
                    $tl = [];
                    foreach ($consultations as $c) { $tl[] = ['date'=>$c['date_consultation'],'type'=>'Consultation','icon'=>'bi-stethoscope','color'=>'#1e40af','bg'=>'#eff6ff','titre'=>$c['motif_consultation']?:'(sans motif)','sous'=>'Dr. '.($c['medecin_nom']??'—').(!empty($c['diagnostic_principal'])?(' · '.$c['diagnostic_principal']):''),'extra'=>mb_substr($c['plan_traitement']??'',0,80)]; }
                    foreach ($bilans_demandes as $b) { $tl[] = ['date'=>$b['date_creation'],'type'=>'Bilan','icon'=>'bi-flask-fill','color'=>'#0284c7','bg'=>'#f0f9ff','titre'=>$b['nom_examen']?:'Bilan labo','sous'=>'Dr. '.($b['medecin_nom']??'—'),'extra'=>'']; }
                    foreach ($bilans_imagerie as $bi) { $tl[] = ['date'=>$bi['date_creation'],'type'=>'Imagerie','icon'=>'bi-radioactive','color'=>'#7c3aed','bg'=>'#f5f3ff','titre'=>$bi['nom_examen']?:'Imagerie','sous'=>'Dr. '.($bi['medecin_nom']??'—'),'extra'=>mb_substr($bi['resultat']??'',0,60)]; }
                    foreach ($prescriptions as $pr) { $tl[] = ['date'=>$pr['date_prescription'],'type'=>'Prescription','icon'=>'bi-capsule-pill','color'=>'#d97706','bg'=>'#fffbeb','titre'=>$pr['medicament_nom']??'Prescription','sous'=>($pr['medecin_nom']??'').(!empty($pr['posologie'])?(' · '.$pr['posologie']):''),'extra'=>'']; }
                    foreach ($historique_hospit as $h) {
                        $tl[] = ['date'=>$h['date_admission'],'type'=>'Hospitalisation','icon'=>'bi-hospital-fill','color'=>'#059669','bg'=>'#f0fdf4','titre'=>'Hospitalisation — '.($h['nom_service']??''),'sous'=>($h['nom_chambre']??'').(!empty($h['nom_lit'])?(' / '.$h['nom_lit']):''),'extra'=>mb_substr($h['motif_hospitalisation']??'',0,60)];
                        if (!empty($h['date_sortie_effective'])) $tl[] = ['date'=>$h['date_sortie_effective'],'type'=>'Sortie','icon'=>'bi-box-arrow-right','color'=>'#64748b','bg'=>'#f8fafc','titre'=>'Sortie — '.($h['nom_service']??''),'sous'=>'Durée : '.((new DateTime($h['date_admission']))->diff(new DateTime($h['date_sortie_effective']))->days).' jour(s)','extra'=>''];
                    }
                    usort($tl, fn($a,$b) => strtotime($b['date'])<=>strtotime($a['date']));
                    ?>
                    <?php if (empty($tl)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-clock-history" style="font-size:2.5rem;opacity:.2;display:block;margin-bottom:12px"></i>
                        Aucun événement enregistré.
                    </div>
                    <?php else: ?>
                    <div style="position:relative;padding-left:28px;">
                        <div style="position:absolute;left:11px;top:0;bottom:0;width:2px;background:linear-gradient(to bottom,#3b82f6,#e2e8f0);border-radius:2px"></div>
                        <?php foreach ($tl as $ev): ?>
                        <div style="position:relative;margin-bottom:14px;">
                            <div style="position:absolute;left:-24px;top:10px;width:13px;height:13px;border-radius:50%;background:<?= $ev['color'] ?>;border:2px solid #fff;box-shadow:0 0 0 2px <?= $ev['color'] ?>44;"></div>
                            <div style="background:#fff;border-radius:12px;padding:11px 15px;border:1px solid #e2e8f0;border-left:3px solid <?= $ev['color'] ?>;">
                                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:10px;">
                                    <div style="display:flex;align-items:flex-start;gap:9px;flex:1;min-width:0">
                                        <div style="width:30px;height:30px;border-radius:8px;background:<?= $ev['bg'] ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                            <i class="bi <?= $ev['icon'] ?>" style="color:<?= $ev['color'] ?>;font-size:.85rem"></i>
                                        </div>
                                        <div style="min-width:0">
                                            <div style="font-weight:700;font-size:.82rem;color:#0f172a"><?= htmlspecialchars(mb_substr($ev['titre'],0,60)) ?></div>
                                            <?php if (!empty($ev['sous'])): ?><div style="font-size:.72rem;color:#64748b"><?= htmlspecialchars(mb_substr($ev['sous'],0,70)) ?></div><?php endif; ?>
                                            <?php if (!empty($ev['extra'])): ?><div style="font-size:.71rem;color:#94a3b8;font-style:italic"><?= htmlspecialchars($ev['extra']) ?>…</div><?php endif; ?>
                                        </div>
                                    </div>
                                    <div style="flex-shrink:0;text-align:right">
                                        <div style="font-size:.7rem;font-weight:700;color:#94a3b8"><?= date('d/m/Y', strtotime($ev['date'])) ?></div>
                                        <div style="font-size:.66rem;color:#cbd5e1"><?= date('H:i', strtotime($ev['date'])) ?></div>
                                        <span style="font-size:.6rem;font-weight:700;padding:2px 7px;border-radius:10px;background:<?= $ev['bg'] ?>;color:<?= $ev['color'] ?>;margin-top:3px;display:inline-block"><?= $ev['type'] ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

            </div><!-- /tab-content-principal -->

            <!-- 3. HISTORIQUE DES SOINS EFFECTUÉS — groupé par date -->
            <div class="mt-4 animate__animated animate__fadeIn">
                <div class="d-flex align-items-center mb-3">
                    <div class="bg-primary bg-opacity-10 p-2 rounded-3 me-3">
                        <i class="bi bi-clock-history text-primary fs-5"></i>
                    </div>
                    <h6 class="fw-bold text-dark mb-0">Historique des Soins Effectués</h6>
                    <?php if(!empty($history)): ?>
                    <span class="ms-2 badge rounded-pill bg-primary bg-opacity-10 text-primary"
                          style="font-size:.72rem"><?= count($history) ?> soin<?= count($history)>1?'s':'' ?></span>
                    <?php endif; ?>
                </div>

                <?php if(empty($history)): ?>
                <div class="card border-0 shadow-sm rounded-4 p-4 text-center text-muted small fst-italic">
                    <i class="bi bi-check2-all d-block mb-2" style="font-size:2rem;opacity:.35"></i>
                    Aucun soin enregistré pour ce patient.
                </div>
                <?php else:
                    // Grouper les soins par date (clé = Y-m-d)
                    $soinsParDate = [];
                    foreach ($history as $h) {
                        $dateKey = date('Y-m-d', strtotime($h['date_execution']));
                        $soinsParDate[$dateKey][] = $h;
                    }
                    // Trier par date décroissante
                    krsort($soinsParDate);
                    $accordionId = 'accordionSoins';
                    $idx = 0;
                ?>
                <div class="accordion accordion-flush rounded-4 overflow-hidden shadow-sm border" id="<?= $accordionId ?>">
                <?php foreach($soinsParDate as $dateKey => $soins):
                    $collapseId = 'soins-' . str_replace('-', '', $dateKey);
                    $isFirst    = ($idx === 0);
                    $nbSoins    = count($soins);
                    $dateFr     = date('l d F Y', strtotime($dateKey));
                    // Traduction rapide du jour en français
                    $jours = ['Monday'=>'Lundi','Tuesday'=>'Mardi','Wednesday'=>'Mercredi',
                              'Thursday'=>'Jeudi','Friday'=>'Vendredi','Saturday'=>'Samedi','Sunday'=>'Dimanche'];
                    $mois  = ['January'=>'janvier','February'=>'février','March'=>'mars','April'=>'avril',
                              'May'=>'mai','June'=>'juin','July'=>'juillet','August'=>'août',
                              'September'=>'septembre','October'=>'octobre','November'=>'novembre','December'=>'décembre'];
                    foreach ($jours as $en => $fr) $dateFr = str_replace($en, $fr, $dateFr);
                    foreach ($mois  as $en => $fr) $dateFr = str_replace($en, $fr, $dateFr);
                    $idx++;
                ?>
                <div class="accordion-item border-0 <?= $isFirst ? '' : 'border-top' ?>"
                     style="border-color:#f1f5f9!important">
                    <h2 class="accordion-header">
                        <button class="accordion-button <?= $isFirst ? '' : 'collapsed' ?> py-3 px-4 fw-semibold"
                                type="button"
                                data-bs-toggle="collapse"
                                data-bs-target="#<?= $collapseId ?>"
                                style="font-size:.88rem;background:<?= $isFirst ? '#eff6ff' : '#f8fafc' ?>;color:#1e293b;">
                            <div class="d-flex align-items-center gap-3 w-100">
                                <div class="d-flex align-items-center justify-content-center rounded-3 flex-shrink-0"
                                     style="width:38px;height:38px;background:<?= $isFirst ? '#3b82f6' : '#94a3b8' ?>;color:#fff;font-size:.72rem;font-weight:800;line-height:1;text-align:center">
                                    <?= date('d', strtotime($dateKey)) ?><br>
                                    <span style="font-size:.58rem;font-weight:600"><?= strtoupper(substr(array_values($mois)[date('n', strtotime($dateKey))-1], 0, 3)) ?></span>
                                </div>
                                <div class="flex-grow-1">
                                    <div style="font-size:.88rem;font-weight:700;color:#1e293b"><?= $dateFr ?></div>
                                    <div style="font-size:.72rem;color:#64748b;font-weight:400">
                                        <?= $nbSoins ?> soin<?= $nbSoins>1?'s':'' ?> effectué<?= $nbSoins>1?'s':'' ?>
                                    </div>
                                </div>
                                <?php if($isFirst): ?>
                                <span class="badge rounded-pill me-3" style="background:#dbeafe;color:#1d4ed8;font-size:.65rem">Dernier</span>
                                <?php endif; ?>
                            </div>
                        </button>
                    </h2>
                    <div id="<?= $collapseId ?>" class="accordion-collapse collapse <?= $isFirst ? 'show' : '' ?>">
                        <div class="accordion-body p-0">
                            <table class="table table-hover align-middle mb-0" style="font-size:.83rem">
                                <thead style="background:#f8fafc">
                                    <tr class="text-muted" style="font-size:.7rem;text-transform:uppercase;letter-spacing:.5px">
                                        <th class="ps-4 py-2" style="width:15%">Heure</th>
                                        <th style="width:40%">Soin effectué</th>
                                        <th style="width:20%">Catégorie</th>
                                        <th class="pe-4" style="width:25%">Intervenant</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach($soins as $s):
                                    $infNom    = trim(($s['infirmier_prenom'] ?? '').' '.($s['infirmier_nom'] ?? '')) ?: 'Staff';
                                    $initials  = strtoupper(substr($s['infirmier_prenom'] ?? ($s['infirmier_nom'] ?? 'ST'), 0, 1) . substr($s['infirmier_nom'] ?? '', 0, 1));
                                    // Description : priorité à soin_description, fallback sur categorie
                                    $soinLabel = trim($s['soin_description'] ?? '');
                                    if ($soinLabel === '' || $soinLabel === ($s['categorie'] ?? '')) {
                                        $soinLabel = $s['categorie'] ?? '—';
                                    }
                                    // Couleurs par voie
                                    $catColors = ['IV'=>'#0e7490','IM'=>'#7c3aed','SC'=>'#0369a1','PO'=>'#15803d',
                                                  'PER_OS'=>'#15803d','SURVEILLANCE'=>'#475569','Pansement'=>'#be123c'];
                                    $catColor  = $catColors[$s['categorie'] ?? ''] ?? '#3b82f6';
                                ?>
                                <tr style="border-top:1px solid #f1f5f9">
                                    <td class="ps-4 py-3">
                                        <div class="fw-bold" style="color:#3b82f6;font-size:.9rem">
                                            <?= date('H:i', strtotime($s['date_execution'])) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold" style="color:#1e293b;font-size:.85rem">
                                            <?= htmlspecialchars($soinLabel) ?>
                                        </div>
                                        <?php if(!empty($s['note_execution'])): ?>
                                        <div class="text-muted mt-1" style="font-size:.73rem">
                                            <i class="bi bi-chat-left-text me-1"></i><?= htmlspecialchars(mb_substr($s['note_execution'], 0, 100)) ?>
                                        </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge rounded-pill px-3 py-1"
                                              style="font-size:.68rem;font-weight:700;background:<?= $catColor ?>18;color:<?= $catColor ?>;border:1px solid <?= $catColor ?>44;">
                                            <?= htmlspecialchars($s['categorie'] ?? '—') ?>
                                        </span>
                                    </td>
                                    <td class="pe-4">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="rounded-circle d-flex align-items-center justify-content-center"
                                                 style="width:28px;height:28px;font-size:.62rem;font-weight:800;background:#dbeafe;color:#1d4ed8;flex-shrink:0">
                                                <?= htmlspecialchars($initials ?: 'ST') ?>
                                            </div>
                                            <span style="font-size:.82rem;font-weight:600;color:#334155">
                                                <?= htmlspecialchars($infNom) ?>
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

        </div> <!-- Fin col-lg-9 -->
    </div> <!-- Fin row -->
</div> <!-- Fin container -->

<!-- ================= MODALES D'ACTION ================= -->

<!-- MODALE DEMANDER BILAN (CATALOGUE COMPLET LABO + RADIOLOGIE) -->
<div class="modal fade" id="modalBilan" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 px-4 pt-4 pb-2">
                <h5 class="modal-title fw-bold"><i class="bi bi-plus-circle-dotted me-2 text-primary"></i>Nouvelle Demande d'Examen</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body px-4 pb-4">

                <!-- Onglets Labo / Radiologie -->
                <ul class="nav nav-pills mb-3 bg-light p-1 rounded-3" role="tablist">
                    <li class="nav-item flex-fill"><button class="nav-link active w-100 rounded-3 fw-semibold" data-bs-toggle="pill" data-bs-target="#mb-tab-labo" type="button"><i class="bi bi-flask me-1"></i>LABORATOIRE</button></li>
                    <li class="nav-item flex-fill"><button class="nav-link w-100 rounded-3 fw-semibold" data-bs-toggle="pill" data-bs-target="#mb-tab-radio" type="button"><i class="bi bi-radioactive me-1"></i>RADIOLOGIE / IMAGERIE</button></li>
                </ul>

                <div class="tab-content">

                    <!-- ── ONGLET LABO ── -->
                    <div class="tab-pane fade show active" id="mb-tab-labo">

                        <!-- Sélection examen -->
                        <div class="card border mb-3">
                            <div class="card-header bg-light fw-semibold small text-uppercase">Choisir un examen</div>
                            <div class="card-body">
                                <div class="row g-2 align-items-end">
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold mb-1">Catégorie</label>
                                        <select class="form-select form-select-sm" id="mb-categorie" onchange="mbChargerExamens()">
                                            <option value="">Toutes catégories</option>
                                        </select>
                                    </div>
                                    <div class="col-md-5">
                                        <label class="form-label small fw-bold mb-1">Examen <span class="text-danger">*</span></label>
                                        <select class="form-select form-select-sm" id="mb-examen" onchange="mbAfficherInfoExamen()">
                                            <option value="">— Sélectionner —</option>
                                        </select>
                                        <div id="mb-info-examen" class="mt-1"></div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="mb-urgent-labo">
                                            <label class="form-check-label fw-bold text-danger small" for="mb-urgent-labo">URGENT</label>
                                        </div>
                                    </div>
                                    <div class="col-md-1">
                                        <button type="button" class="btn btn-primary btn-sm w-100" onclick="mbAjouterLabo()">
                                            <i class="bi bi-plus-lg"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="col-12 mt-2">
                                    <label class="form-label small fw-bold mb-1">Instructions particulières</label>
                                    <input type="text" class="form-control form-control-sm" id="mb-instructions-labo" placeholder="Optionnel…">
                                </div>
                            </div>
                        </div>

                        <!-- Table prévisualisation labo -->
                        <div class="table-responsive mb-3">
                            <table class="table table-sm table-bordered mb-0">
                                <thead class="table-info">
                                    <tr><th>Examen</th><th>Catégorie</th><th>Prélèvement</th><th>Délai</th><th>Urgence</th><th class="text-center">Retirer</th></tr>
                                </thead>
                                <tbody id="mb-tbody-labo">
                                    <tr id="mb-labo-empty"><td colspan="6" class="text-center text-muted py-3"><i class="bi bi-flask me-2"></i>Aucun examen ajouté</td></tr>
                                </tbody>
                            </table>
                        </div>

                        <button type="button" class="btn btn-primary w-100 py-2 rounded-pill fw-bold" id="mb-btn-envoyer-labo" onclick="mbEnvoyerLabo()" disabled>
                            <i class="bi bi-send-fill me-2"></i>Envoyer au Laboratoire
                        </button>
                    </div>

                    <!-- ── ONGLET RADIO ── -->
                    <div class="tab-pane fade" id="mb-tab-radio">

                        <!-- Sélection imagerie -->
                        <div class="card border mb-3">
                            <div class="card-header bg-light fw-semibold small text-uppercase">Configurer la demande</div>
                            <div class="card-body">
                                <div class="row g-2">
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold mb-1">Modalité <span class="text-danger">*</span></label>
                                        <select class="form-select form-select-sm" id="mb-modalite" onchange="mbUpdatePartiesCorps()">
                                            <option value="">— Choisir —</option>
                                            <option value="radiographie">🩻 Radiographie</option>
                                            <option value="echographie">🔊 Échographie</option>
                                            <option value="scanner">💻 Scanner (TDM)</option>
                                            <option value="irm">🧲 IRM</option>
                                            <option value="mammographie">🔬 Mammographie</option>
                                            <option value="autre">📋 Autre</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold mb-1">Partie du corps <span class="text-danger">*</span></label>
                                        <select class="form-select form-select-sm" id="mb-partie-corps">
                                            <option value="">— Sélectionner la modalité d'abord —</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small fw-bold mb-1">Urgence</label>
                                        <select class="form-select form-select-sm" id="mb-urgence-radio">
                                            <option value="NORMAL">Normal</option>
                                            <option value="URGENT">🔴 URGENT</option>
                                            <option value="TRES_URGENT">🚨 TRÈS URGENT</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2 d-flex align-items-end">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="mb-contraste">
                                            <label class="form-check-label small fw-semibold" for="mb-contraste">+ Contraste</label>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small fw-bold mb-1">Indication clinique <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control form-control-sm" id="mb-indication" placeholder="Ex: suspicion de fracture, douleur thoracique…">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small fw-bold mb-1">Instructions particulières</label>
                                        <input type="text" class="form-control form-control-sm" id="mb-instructions-radio" placeholder="Optionnel…">
                                    </div>
                                    <div id="mb-info-contraste" class="col-12"></div>
                                    <div class="col-12 text-end">
                                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="mbAjouterRadio()">
                                            <i class="bi bi-plus-lg me-1"></i>Ajouter à la liste
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Table prévisualisation radio -->
                        <div class="table-responsive mb-3">
                            <table class="table table-sm table-bordered mb-0">
                                <thead class="table-primary">
                                    <tr><th>Modalité</th><th>Partie du corps</th><th>Urgence</th><th>Indication</th><th class="text-center">Retirer</th></tr>
                                </thead>
                                <tbody id="mb-tbody-radio">
                                    <tr id="mb-radio-empty"><td colspan="5" class="text-center text-muted py-3"><i class="bi bi-radioactive me-2"></i>Aucune demande ajoutée</td></tr>
                                </tbody>
                            </table>
                        </div>

                        <button type="button" class="btn btn-dark w-100 py-2 rounded-pill fw-bold" id="mb-btn-envoyer-radio" onclick="mbEnvoyerRadio()" disabled>
                            <i class="bi bi-send-fill me-2"></i>Transmettre en Radiologie
                        </button>
                    </div>


                </div><!-- /tab-content -->
            </div>
        </div>
    </div>
</div>

<!-- MODALE TRANSFUSION -->
<div class="modal fade" id="modalTransfusion" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="bi bi-droplet-half"></i> Demande de Transfusion</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formTransfusion">
                <input type="hidden" name="patient_id" value="<?= $patient['id'] ?>">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="small fw-bold">Groupe</label>
                            <select name="groupe" id="trans_groupe" class="form-select" required>
                                <?php foreach(['A','B','AB','O'] as $g) echo "<option value='$g' ".($patient['groupe_sanguin']==$g?'selected':'').">$g</option>"; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="small fw-bold">Rhésus</label>
                            <select name="rhesus" id="trans_rhesus" class="form-select" required onchange="checkBloodStock()">
                                <option value="+">+</option><option value="-">-</option>
                            </select>
                        </div>
                    </div>
                    <div id="stockStatusBox" class="mt-3 alert d-none"></div>
                </div>
                <div class="modal-footer"><button type="submit" class="btn btn-danger w-100" id="btnSubmitTrans">Lancer la demande</button></div>
            </form>
        </div>
    </div>
</div>

<!-- ══ MODAL ADMETTRE AU LIT ════════════════════════════════════════════════ -->
<div class="modal fade" id="modalAdmission" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header text-white py-3" style="background:linear-gradient(135deg,#1d4ed8,#3b82f6);">
                <div>
                    <h5 class="modal-title fw-bold mb-0"><i class="bi bi-hospital me-2"></i>Admettre au Lit</h5>
                    <small class="opacity-75"><?= htmlspecialchars(($patient['nom'] ?? '') . ' ' . ($patient['prenom'] ?? '')) ?> — <?= htmlspecialchars($patient['dossier_numero'] ?? '') ?></small>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div id="adm-alert" class="d-none mb-3"></div>

                <!-- Chargement des lits -->
                <div id="adm-loading" class="text-center py-4">
                    <div class="spinner-border text-primary"></div>
                    <p class="mt-2 text-muted">Chargement des lits disponibles…</p>
                </div>

                <div id="adm-form" class="d-none">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">Lit disponible <span class="text-danger">*</span></label>
                            <select class="form-select" id="adm-lit-select">
                                <option value="">— Choisir un lit —</option>
                            </select>
                            <div id="adm-lit-info" class="mt-1 small text-muted"></div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Motif d'hospitalisation</label>
                            <textarea class="form-control" id="adm-motif" rows="3"
                                      placeholder="Diagnostic principal, raison de l'hospitalisation…"></textarea>
                        </div>
                    </div>
                </div>

                <div id="adm-no-lits" class="d-none alert alert-warning">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    Aucun lit disponible actuellement. Veuillez contacter le service concerné.
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                <button class="btn btn-primary fw-bold px-4" id="adm-btn-submit" onclick="soumettreAdmission()" disabled>
                    <i class="bi bi-hospital me-1"></i>Confirmer l'admission
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ══ MODAL VOIR SPÉCIALISTE ═══════════════════════════════════════════════ -->
<div class="modal fade" id="modalSpecialiste" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header text-white" style="background:linear-gradient(135deg,#5b21b6,#7c3aed);">
                <h5 class="modal-title"><i class="bi bi-person-badge-fill me-2"></i>Référer vers un Spécialiste</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Quota alert -->
                <div id="specQuotaAlert" class="d-none mb-3"></div>

                <form id="formRdvSpec" onsubmit="soumettreRdvSpec(event)">
                    <input type="hidden" name="patient_id" value="<?= (int)$patient['id'] ?>">
                    <input type="hidden" name="source" value="PRESCRIPTION">

                    <div class="row g-3">
                        <!-- Spécialiste -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Spécialiste <span class="text-danger">*</span></label>
                            <select name="specialiste_id" id="specSelect" class="form-select" required onchange="checkQuotaSpec()">
                                <option value="">— Choisir un spécialiste —</option>
                                <?php
                                // Fetch active specialists for the dropdown
                                try {
                                    if (!class_exists('SpecialisteController')) {
                                        require_once __DIR__ . '/../../controllers/SpecialisteController.php';
                                    }
                                    $dbSpec = (new Database())->getConnection();
                                    $stmtSpec = $dbSpec->query("SELECT id, specialite, nom, prenom FROM specialistes WHERE actif=1 ORDER BY specialite");
                                    $allSpec = $stmtSpec->fetchAll(PDO::FETCH_ASSOC);
                                    foreach ($allSpec as $sp): ?>
                                    <option value="<?= $sp['id'] ?>">
                                        <?= htmlspecialchars(SpecialisteController::LABELS[$sp['specialite']] ?? $sp['specialite']) ?>
                                        — Dr <?= htmlspecialchars($sp['nom'] . ' ' . $sp['prenom']) ?>
                                    </option>
                                    <?php endforeach;
                                } catch (Exception $e) { echo '<option value="">Erreur de chargement</option>'; }
                                ?>
                            </select>
                        </div>

                        <!-- Date -->
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Date RDV <span class="text-danger">*</span></label>
                            <input type="date" name="date_rdv" id="specDate" class="form-control" required
                                   value="<?= date('Y-m-d') ?>" onchange="checkQuotaSpec()">
                        </div>

                        <!-- Heure -->
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Heure <span class="text-muted small">(optionnel)</span></label>
                            <input type="time" name="heure_rdv" class="form-control">
                        </div>

                        <!-- Motif -->
                        <div class="col-12">
                            <label class="form-label fw-semibold">Motif / Raison de la référence <span class="text-danger">*</span></label>
                            <textarea name="motif" class="form-control" rows="3" required
                                      placeholder="Décrire les symptômes, examens réalisés et raison de la référence…"></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                <button class="btn text-white fw-bold px-4" style="background:#7c3aed;"
                        onclick="soumettreRdvSpec(event)" id="btnSubmitSpec">
                    <i class="bi bi-calendar-check me-1"></i>Créer le RDV
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalPartager" tabindex="-1">
    <div class="modal-dialog">
        <form action="<?= BASE_URL ?>patients/partager-dossier" method="POST" class="modal-content">
            <input type="hidden" name="patient_id" value="<?= $patient['id'] ?>">
            <div class="modal-header"><h5>Partager le dossier</h5></div>
            <div class="modal-body">
                <select name="service_id" id="selService" class="form-select mb-2" onchange="loadUsers()" required>
    <option value="">Choisir un service</option>
    <?php
    $db = (new Database())->getConnection();
    $services = $db->query("SELECT id, nom_service FROM services ORDER BY nom_service ASC")->fetchAll(PDO::FETCH_ASSOC);
    foreach($services as $s): ?>
        <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['nom_service']) ?></option>
    <?php endforeach; ?>
</select>
                <select name="role_cible" id="selRole" class="form-select mb-2" onchange="loadUsers()">
                    <option value="">Choisir rôle</option>
                    <option value="MEDECIN">Médecin</option>
                    <option value="INFIRMIER">Infirmier</option>
                </select>
                <select name="destinataire_id" id="selUser" class="form-select" required>
                    <option value="">Sélectionner la personne...</option>
                </select>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">Partager le dossier</button>
            </div>
        </form>
    </div>
</div>

<!-- MODALE LISTE FORMULAIRES -->
<div class="modal fade" id="modalListeFormulaires" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white"><h5 class="modal-title"><i class="bi bi-file-earmark-text"></i> Formulaires</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <div class="modal-body p-4">
                <div class="list-group list-group-flush">
                    <a href="<?= BASE_URL ?>formulaire/creer/bulletin-examens/<?= $patient['id'] ?>" class="list-group-item list-group-item-action">Bulletin d'examens</a>
                    <a href="<?= BASE_URL ?>formulaire/creer/certificat-hospitalisation/<?= $patient['id'] ?>" class="list-group-item list-group-item-action">Certificat d'hospitalisation</a>
                    <a href="<?= BASE_URL ?>hospitalisation/observations-evolution/<?= $patient['id'] ?>" class="list-group-item list-group-item-action fw-bold">Observations / Évolution</a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- SCRIPTS JAVASCRIPT -->
<script>
// ── Suppression CRH (admin) ──────────────────────────────────────────────────
function supprimerCRH(id, btn) {
    if (!confirm('Supprimer définitivement ce compte-rendu d\'hospitalisation ?\nCette action est irréversible.')) return;
    btn.disabled = true;
    fetch('<?= BASE_URL ?>admin/patients/delete-crh/' + id, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            btn.closest('tr').style.transition = 'opacity .4s';
            btn.closest('tr').style.opacity = '0';
            setTimeout(() => btn.closest('tr').remove(), 400);
        } else {
            alert(data.message || 'Erreur lors de la suppression.');
            btn.disabled = false;
        }
    })
    .catch(() => { alert('Erreur de communication.'); btn.disabled = false; });
}

// ── Suppression document uploadé (admin) ─────────────────────────────────────
function supprimerDocument(id, btn) {
    if (!confirm('Supprimer définitivement ce fichier ?\nCette action est irréversible.')) return;
    btn.disabled = true;
    fetch('<?= BASE_URL ?>admin/patients/delete-document/' + id, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            btn.closest('tr').style.transition = 'opacity .4s';
            btn.closest('tr').style.opacity = '0';
            setTimeout(() => btn.closest('tr').remove(), 400);
        } else {
            alert(data.message || 'Erreur lors de la suppression.');
            btn.disabled = false;
        }
    })
    .catch(() => { alert('Erreur de communication.'); btn.disabled = false; });
}

document.addEventListener('DOMContentLoaded', function() {
    // 1. Initialisation des onglets Bootstrap
    var triggerTabList = [].slice.call(document.querySelectorAll('#myTab button'))
    triggerTabList.forEach(function (triggerEl) {
        var tabTrigger = new bootstrap.Tab(triggerEl)
        triggerEl.addEventListener('click', function (event) {
            event.preventDefault(); tabTrigger.show();
        })
    });

    // 2. Ouvrir l'onglet "Avis partagé" si l'URL ou le hash l'indique
    const urlParams = new URLSearchParams(window.location.search);
    const successParam = urlParams.get('success');
    if (window.location.hash === '#tab-avis-partage' || successParam === 'avis_sauvegarde') {
        const btnAvis = document.getElementById('btn-tab-avis-partage');
        if (btnAvis) {
            bootstrap.Tab.getOrCreateInstance(btnAvis).show();
            btnAvis.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }
    // Toast de confirmation après sauvegarde avis
    if (successParam === 'avis_sauvegarde') {
        const toast = document.createElement('div');
        toast.className = 'position-fixed bottom-0 end-0 p-3';
        toast.style.zIndex = 9999;
        toast.innerHTML = `<div class="toast show align-items-center text-white border-0" style="background:#0d9488" role="alert">
            <div class="d-flex">
                <div class="toast-body"><i class="bi bi-check-circle-fill me-2"></i>Avis enregistré avec succès.</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>`;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 4000);
    }

    // 3. Chargement du catalogue d'examens labo
    mbInitLabo();
});

// ══════════════════════════════════════════════════════════════════════════
// MODAL BILAN — LABORATOIRE (catalogue dynamique)
// ══════════════════════════════════════════════════════════════════════════
let mbExamensDisponibles = [];
let mbListeLabo = [];
let mbListeRadio = [];

const mbPatientId = <?= (int)($patient['id'] ?? 0) ?>;

function mbInitLabo() {
    fetch('<?= BASE_URL ?>laboratoire/examens-disponibles')
        .then(r => r.json())
        .then(examens => {
            mbExamensDisponibles = examens;
            const catSel = document.getElementById('mb-categorie');
            const cats = [...new Set(examens.map(e => e.categorie).filter(Boolean))].sort();
            cats.forEach(cat => {
                const o = document.createElement('option');
                o.value = cat; o.textContent = cat;
                catSel.appendChild(o);
            });
            mbChargerExamens();
        })
        .catch(console.error);
}

function mbChargerExamens() {
    const cat = document.getElementById('mb-categorie').value;
    const sel = document.getElementById('mb-examen');
    sel.innerHTML = '<option value="">— Sélectionner —</option>';
    const liste = cat ? mbExamensDisponibles.filter(e => e.categorie === cat) : mbExamensDisponibles;
    liste.forEach(ex => {
        const o = document.createElement('option');
        o.value = ex.id;
        o.textContent = `${ex.nom} (${ex.delai_rendu_heures}h)`;
        o.dataset.ex = JSON.stringify(ex);
        sel.appendChild(o);
    });
    document.getElementById('mb-info-examen').innerHTML = '';
}

function mbAfficherInfoExamen() {
    const sel = document.getElementById('mb-examen');
    const opt = sel.selectedOptions[0];
    const div = document.getElementById('mb-info-examen');
    if (!opt || !opt.value) { div.innerHTML = ''; return; }
    const ex = JSON.parse(opt.dataset.ex);
    div.innerHTML = `<span class="badge bg-info text-dark">${ex.type_prelevement}</span>
        <span class="badge bg-secondary ms-1">${ex.delai_rendu_heures}h</span>
        ${ex.a_jeun_requis ? '<span class="badge bg-warning text-dark ms-1">À jeun</span>' : ''}`;
}

function mbAjouterLabo() {
    const sel = document.getElementById('mb-examen');
    const opt = sel.selectedOptions[0];
    if (!opt || !opt.value) { alert('Veuillez sélectionner un examen.'); return; }
    const ex = JSON.parse(opt.dataset.ex);
    mbListeLabo.push({
        id: ex.id, nom: ex.nom, categorie: ex.categorie,
        type_prelevement: ex.type_prelevement, delai: ex.delai_rendu_heures,
        urgent: document.getElementById('mb-urgent-labo').checked,
        instructions: document.getElementById('mb-instructions-labo').value.trim()
    });
    mbRenderLabo();
    sel.value = '';
    document.getElementById('mb-urgent-labo').checked = false;
    document.getElementById('mb-instructions-labo').value = '';
    document.getElementById('mb-info-examen').innerHTML = '';
}

function mbRenderLabo() {
    const tbody = document.getElementById('mb-tbody-labo');
    const empty = document.getElementById('mb-labo-empty');
    const btn   = document.getElementById('mb-btn-envoyer-labo');
    empty.style.display = mbListeLabo.length ? 'none' : '';
    btn.disabled = !mbListeLabo.length;
    const rows = mbListeLabo.map((ex, i) => `
        <tr>
            <td class="fw-semibold">${ex.nom}</td>
            <td><span class="badge bg-secondary">${ex.categorie || '—'}</span></td>
            <td>${ex.type_prelevement || '—'}</td>
            <td>${ex.delai}h</td>
            <td>${ex.urgent ? '<span class="badge bg-danger">URGENT</span>' : '<span class="badge bg-success">Normal</span>'}</td>
            <td class="text-center"><button class="btn btn-sm btn-outline-danger" onclick="mbRetirerLabo(${i})"><i class="bi bi-trash"></i></button></td>
        </tr>`).join('');
    tbody.innerHTML = rows + `<tr id="mb-labo-empty" style="display:${mbListeLabo.length ? 'none' : ''}"><td colspan="6" class="text-center text-muted py-3"><i class="bi bi-flask me-2"></i>Aucun examen ajouté</td></tr>`;
}

function mbRetirerLabo(i) { mbListeLabo.splice(i, 1); mbRenderLabo(); }

function mbEnvoyerLabo() {
    if (!mbListeLabo.length) return;
    const btn = document.getElementById('mb-btn-envoyer-labo');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Envoi…';
    fetch('<?= BASE_URL ?>laboratoire/creer-demande-consultation', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ patient_id: mbPatientId, examens: mbListeLabo })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            mbListeLabo = [];
            mbRenderLabo();
            bootstrap.Modal.getInstance(document.getElementById('modalBilan')).hide();
            mbShowToast('Demande labo transmise avec succès !', 'success');
        } else {
            alert('Erreur : ' + (data.message || 'Inconnue'));
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-send-fill me-2"></i>Envoyer au Laboratoire';
        }
    })
    .catch(() => { alert('Erreur réseau.'); btn.disabled = false; btn.innerHTML = '<i class="bi bi-send-fill me-2"></i>Envoyer au Laboratoire'; });
}

// ══════════════════════════════════════════════════════════════════════════
// MODAL BILAN — RADIOLOGIE (parties du corps dynamiques)
// ══════════════════════════════════════════════════════════════════════════
const mbPartiesCorps = {
    radiographie: ['Thorax','Abdomen','Crâne','Rachis cervical','Rachis dorsal','Rachis lombaire','Bassin','Épaule droite','Épaule gauche','Bras droit','Bras gauche','Avant-bras droit','Avant-bras gauche','Main droite','Main gauche','Cuisse droite','Cuisse gauche','Jambe droite','Jambe gauche','Pied droit','Pied gauche','Cheville droite','Cheville gauche','Genou droit','Genou gauche'],
    echographie: ['Abdomen total','Pelvis','Obstétricale','Thyroïde','Sein droit','Sein gauche','Rein droit','Rein gauche','Foie','Vésicule biliaire','Rate','Prostate','Col utérin','Tendons','Paroi abdominale'],
    scanner: ['Crâne','Thorax','Abdomen-Pelvis','Thorax-Abdomen-Pelvis','Rachis cervical','Rachis dorsal','Rachis lombaire','Bassin','Membre supérieur droit','Membre supérieur gauche','Membre inférieur droit','Membre inférieur gauche','Corps entier'],
    irm: ['Crâne','Rachis cervical','Rachis dorsal','Rachis lombaire','Genou droit','Genou gauche','Épaule droite','Épaule gauche','Hanche droite','Hanche gauche','Poignet droit','Poignet gauche','Pelvis','Sein droit','Sein gauche','Abdomen','Corps entier'],
    mammographie: ['Sein droit','Sein gauche','Bilatéral'],
    autre: ['À préciser dans les instructions']
};
const mbIconeModalite = { radiographie:'🩻', echographie:'🔊', scanner:'💻', irm:'🧲', mammographie:'🔬', autre:'📋' };

function mbUpdatePartiesCorps() {
    const modalite = document.getElementById('mb-modalite').value;
    const sel = document.getElementById('mb-partie-corps');
    sel.innerHTML = '<option value="">— Choisir —</option>';
    (mbPartiesCorps[modalite] || []).forEach(p => {
        const o = document.createElement('option'); o.value = p; o.textContent = p; sel.appendChild(o);
    });
    const div = document.getElementById('mb-info-contraste');
    div.innerHTML = (modalite === 'scanner' || modalite === 'irm')
        ? '<div class="alert alert-warning py-2 small mb-0"><i class="bi bi-info-circle me-1"></i>Vérifiez la créatinine et les allergies si injection de contraste prévue.</div>'
        : '';
}

function mbAjouterRadio() {
    const modalite   = document.getElementById('mb-modalite').value;
    const partie     = document.getElementById('mb-partie-corps').value;
    const indication = document.getElementById('mb-indication').value.trim();
    if (!modalite || !partie || !indication) { alert('Modalité, partie du corps et indication clinique sont obligatoires.'); return; }
    mbListeRadio.push({
        modalite, partie_corps: partie,
        urgence: document.getElementById('mb-urgence-radio').value,
        avec_contraste: document.getElementById('mb-contraste').checked,
        indication,
        instructions: document.getElementById('mb-instructions-radio').value.trim()
    });
    mbRenderRadio();
    document.getElementById('mb-modalite').value = '';
    document.getElementById('mb-partie-corps').innerHTML = '<option value="">— Sélectionner la modalité d\'abord —</option>';
    document.getElementById('mb-indication').value = '';
    document.getElementById('mb-instructions-radio').value = '';
    document.getElementById('mb-contraste').checked = false;
    document.getElementById('mb-info-contraste').innerHTML = '';
}

function mbRenderRadio() {
    const tbody = document.getElementById('mb-tbody-radio');
    const empty = document.getElementById('mb-radio-empty');
    const btn   = document.getElementById('mb-btn-envoyer-radio');
    empty.style.display = mbListeRadio.length ? 'none' : '';
    btn.disabled = !mbListeRadio.length;
    const ub = { NORMAL:'bg-success', URGENT:'bg-danger', TRES_URGENT:'bg-dark' };
    const rows = mbListeRadio.map((d, i) => `
        <tr>
            <td class="fw-semibold">${mbIconeModalite[d.modalite]||'📋'} ${d.modalite.charAt(0).toUpperCase()+d.modalite.slice(1)}</td>
            <td>${d.partie_corps}${d.avec_contraste ? ' <span class="badge bg-info text-dark ms-1">+Contraste</span>' : ''}</td>
            <td><span class="badge ${ub[d.urgence]||'bg-secondary'}">${d.urgence}</span></td>
            <td class="small text-muted">${d.indication.substring(0,60)}${d.indication.length>60?'…':''}</td>
            <td class="text-center"><button class="btn btn-sm btn-outline-danger" onclick="mbRetirerRadio(${i})"><i class="bi bi-trash"></i></button></td>
        </tr>`).join('');
    tbody.innerHTML = rows + `<tr id="mb-radio-empty" style="display:${mbListeRadio.length ? 'none' : ''}"><td colspan="5" class="text-center text-muted py-3"><i class="bi bi-radioactive me-2"></i>Aucune demande ajoutée</td></tr>`;
}

function mbRetirerRadio(i) { mbListeRadio.splice(i, 1); mbRenderRadio(); }

function mbEnvoyerRadio() {
    if (!mbListeRadio.length) return;
    const btn = document.getElementById('mb-btn-envoyer-radio');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Envoi…';
    fetch('<?= BASE_URL ?>imagerie/creer-demande-consultation', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ patient_id: mbPatientId, medecin_id: <?= (int)($_SESSION['user_id'] ?? 0) ?>, demandes: mbListeRadio })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const nbSent = mbListeRadio.length;
            mbListeRadio = [];
            mbRenderRadio();
            bootstrap.Modal.getInstance(document.getElementById('modalBilan')).hide();
            mbShowToast(`${data.count || nbSent} demande(s) envoyée(s) en radiologie !`, 'success');
        } else {
            alert('Erreur : ' + (data.message || 'Inconnue'));
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-send-fill me-2"></i>Transmettre en Radiologie';
        }
    })
    .catch(() => { alert('Erreur réseau.'); btn.disabled = false; btn.innerHTML = '<i class="bi bi-send-fill me-2"></i>Transmettre en Radiologie'; });
}

function mbShowToast(msg, type = 'success') {
    const t = document.createElement('div');
    t.className = 'position-fixed bottom-0 end-0 p-3'; t.style.zIndex = 9999;
    t.innerHTML = `<div class="toast show align-items-center text-white bg-${type} border-0 shadow-lg" role="alert">
        <div class="d-flex"><div class="toast-body fw-bold"><i class="bi bi-check-circle-fill me-2"></i>${msg}</div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" onclick="this.closest('.position-fixed').remove()"></button></div></div>`;
    document.body.appendChild(t);
    setTimeout(() => t.remove(), 5000);
}

// ══════════════════════════════════════════════════════════════════════════
// MODAL ADMISSION — Admettre au lit
// ══════════════════════════════════════════════════════════════════════════
document.getElementById('modalAdmission')?.addEventListener('show.bs.modal', function () {
    const loading = document.getElementById('adm-loading');
    const form    = document.getElementById('adm-form');
    const noLits  = document.getElementById('adm-no-lits');
    const sel     = document.getElementById('adm-lit-select');
    const btn     = document.getElementById('adm-btn-submit');

    loading.classList.remove('d-none');
    form.classList.add('d-none');
    noLits.classList.add('d-none');
    btn.disabled = true;
    sel.innerHTML = '<option value="">— Choisir un lit —</option>';

    fetch('<?= BASE_URL ?>lits/disponibles')
        .then(r => r.json())
        .then(lits => {
            loading.classList.add('d-none');
            if (!lits.length) { noLits.classList.remove('d-none'); return; }
            form.classList.remove('d-none');
            let curSvc = null;
            lits.forEach(lit => {
                if (lit.nom_service !== curSvc) {
                    const grp = document.createElement('optgroup');
                    grp.label = `🏥 ${lit.nom_service}`;
                    sel.appendChild(grp); curSvc = lit.nom_service;
                }
                const o = document.createElement('option');
                o.value = lit.id;
                o.textContent = `${lit.nom_chambre} — ${lit.nom_lit}`;
                o.dataset.svc = lit.nom_service;
                sel.appendChild(o);
            });
        })
        .catch(() => { loading.classList.add('d-none'); noLits.classList.remove('d-none'); });
});

document.getElementById('adm-lit-select')?.addEventListener('change', function () {
    const btn = document.getElementById('adm-btn-submit');
    const info = document.getElementById('adm-lit-info');
    btn.disabled = !this.value;
    if (this.selectedOptions[0] && this.value) {
        info.textContent = `Service : ${this.selectedOptions[0].dataset.svc}`;
    } else {
        info.textContent = '';
    }
});

function soumettreAdmission() {
    const litId   = document.getElementById('adm-lit-select').value;
    const motif   = document.getElementById('adm-motif').value.trim();
    const btn     = document.getElementById('adm-btn-submit');
    const alertEl = document.getElementById('adm-alert');

    if (!litId) {
        alertEl.className = 'alert alert-warning';
        alertEl.textContent = 'Veuillez sélectionner un lit.';
        alertEl.classList.remove('d-none');
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Admission en cours…';

    const fd = new FormData();
    fd.append('patient_id', mbPatientId);
    fd.append('lit_id', litId);
    fd.append('motif_hospitalisation', motif);

    fetch('<?= BASE_URL ?>lits/confirmer-admission', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('modalAdmission')).hide();
                mbShowToast('Patient admis au lit avec succès !', 'success');
                setTimeout(() => location.reload(), 2000);
            } else {
                alertEl.className = 'alert alert-danger';
                alertEl.textContent = 'Erreur : ' + (data.message || 'Inconnue');
                alertEl.classList.remove('d-none');
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-hospital me-1"></i>Confirmer l\'admission';
            }
        })
        .catch(() => {
            alertEl.className = 'alert alert-danger';
            alertEl.textContent = 'Erreur de communication avec le serveur.';
            alertEl.classList.remove('d-none');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-hospital me-1"></i>Confirmer l\'admission';
        });
}

// ── Libérer le lit (Décharger du lit) ────────────────────────────────────────
function libererLit(patientId) {
    if (!confirm('Confirmer la décharge du patient ?\nLe lit sera libéré et le statut du patient mis à jour.')) return;

    // Récupérer le lit_id depuis la DB
    fetch('<?= BASE_URL ?>lits/get-lit-patient?patient_id=' + patientId)
        .then(r => r.json())
        .then(data => {
            if (!data.lit_id) {
                alert('Impossible de trouver le lit du patient. Vérifiez son dossier d\'hospitalisation.');
                return;
            }
            const fd = new FormData();
            fd.append('patient_id', patientId);
            fd.append('lit_id', data.lit_id);
            return fetch('<?= BASE_URL ?>lits/decharger', { method: 'POST', body: fd });
        })
        .then(r => r ? r.json() : null)
        .then(data => {
            if (!data) return;
            if (data.success) {
                mbShowToast('Patient déchargé avec succès.', 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                alert('Erreur : ' + (data.message || 'Inconnue'));
            }
        })
        .catch(() => alert('Erreur de communication avec le serveur.'));
}

// 3. Vérification Stock Banque de Sang
function checkBloodStock() {
    const g = document.getElementById('trans_groupe').value;
    const r = document.getElementById('trans_rhesus').value;
    const box = document.getElementById('stockStatusBox');
    if (!g || !r) return;

    fetch('<?= BASE_URL ?>banque-sang/check-stock', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `groupe=${g}&rhesus=${r}`
    })
    .then(res => res.json())
    .then(data => {
        box.classList.remove('d-none', 'alert-success', 'alert-warning');
        if (data.status === 'available') {
            box.innerHTML = `Sang disponible (${data.dispo} poches).`;
            box.classList.add('alert-success');
        } else {
            box.innerHTML = `Stock insuffisant. Alerte famille requise.`;
            box.classList.add('alert-warning');
        }
    });
}

// ── RDV Spécialiste ──────────────────────────────────────────────────────────
function checkQuotaSpec() {
    const specId = document.getElementById('specSelect').value;
    const date   = document.getElementById('specDate').value;
    const box    = document.getElementById('specQuotaAlert');
    if (!specId || !date) { box.className = 'd-none mb-3'; return; }

    fetch('<?= BASE_URL ?>specialiste/check-quota?specialiste_id=' + specId + '&date=' + date)
        .then(r => r.json())
        .then(data => {
            box.classList.remove('d-none','alert-success','alert-warning','alert-danger');
            if (data.complet) {
                box.innerHTML = '<i class="bi bi-exclamation-triangle-fill me-2"></i><strong>Quota atteint</strong> (' + data.count + '/' + data.quota_max + ' patients). Le RDV sera quand même enregistré en tant que prescription médicale.';
                box.classList.add('alert','alert-warning');
            } else if (data.count >= data.quota_min) {
                box.innerHTML = '<i class="bi bi-info-circle-fill me-2"></i>Quota proche : ' + data.count + '/' + data.quota_max + ' patients.';
                box.classList.add('alert','alert-info');
            } else {
                box.innerHTML = '<i class="bi bi-check-circle-fill me-2"></i>Places disponibles : ' + (data.quota_max - data.count) + ' restantes (' + data.count + '/' + data.quota_max + ').';
                box.classList.add('alert','alert-success');
            }
        })
        .catch(() => { box.className = 'd-none mb-3'; });
}

function soumettreRdvSpec(e) {
    e.preventDefault();
    const form = document.getElementById('formRdvSpec');
    if (!form.checkValidity()) { form.reportValidity(); return; }
    const btn = document.getElementById('btnSubmitSpec');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Enregistrement…';

    fetch('<?= BASE_URL ?>specialiste/store-rdv', {
        method: 'POST',
        body: new FormData(form),
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('modalSpecialiste')).hide();
            // Toast succès
            const t = document.createElement('div');
            t.className = 'position-fixed bottom-0 end-0 p-3'; t.style.zIndex = 9999;
            t.innerHTML = `<div class="toast show align-items-center text-white bg-success border-0 shadow-lg" role="alert">
                <div class="d-flex"><div class="toast-body fw-bold"><i class="bi bi-check-circle-fill me-2"></i>${data.message || 'RDV créé avec succès.'}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div></div>`;
            document.body.appendChild(t);
            setTimeout(() => t.remove(), 4000);
        } else {
            alert('Erreur : ' + (data.message || 'Impossible de créer le RDV.'));
        }
    })
    .catch(() => alert('Erreur de communication avec le serveur.'))
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-calendar-check me-1"></i>Créer le RDV';
    });
}

function loadUsers() {
    const service = document.getElementById('selService').value;
    const role = document.getElementById('selRole').value;
    const selectUser = document.getElementById('selUser');

    // Réinitialiser
    selectUser.innerHTML = '<option value="">Sélectionner la personne...</option>';

    if(service && role) {
        // Afficher un petit chargement
        selectUser.innerHTML = '<option value="">Chargement...</option>';

        fetch(`<?= BASE_URL ?>api/get-users?service=${service}&role=${role}`)
        .then(response => response.json())
        .then(users => {
            selectUser.innerHTML = '<option value="">Sélectionner la personne...</option>';
            users.forEach(user => {
                selectUser.innerHTML += `<option value="${user.id}">${user.nom} ${user.prenom}</option>`;
            });
        })
        .catch(err => {
            console.error('Erreur:', err);
            selectUser.innerHTML = '<option value="">Erreur de chargement</option>';
        });
    }
}
</script>

<!-- ══ MODAL CONSTANTES (Adaptatif Enfant / Adulte) ═══════════════════ -->
<?php
$isEnfant    = isset($isEnfant) ? $isEnfant : false;
$cstGradient = $isEnfant
    ? 'linear-gradient(135deg,#059669,#10b981)'
    : 'linear-gradient(135deg,#0d6efd,#0099ff)';
$cstBtnClass = $isEnfant ? 'btn-success' : 'btn-primary';
$cstIcon     = $isEnfant ? 'bi-balloon-heart' : 'bi-activity';
$cstProfilLabel = $isEnfant
    ? 'Pédiatrie <span class="badge bg-white text-success fw-bold ms-1">' . $ageNumerique . ' ans</span>'
    : 'Adulte <span class="badge bg-white text-primary fw-bold ms-1">' . $ageNumerique . ' ans</span>';
?>
<div class="modal fade" id="modalConstantes" tabindex="-1" aria-labelledby="modalConstantesLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered <?= $isEnfant ? 'modal-md' : 'modal-lg' ?>">
    <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">

      <!-- En-tête -->
      <div class="modal-header text-white py-3" style="background:<?= $cstGradient ?>;">
        <div>
          <h5 class="modal-title fw-bold mb-0" id="modalConstantesLabel">
            <i class="bi <?= $cstIcon ?> me-2"></i>Constantes — <?= $cstProfilLabel ?>
          </h5>
          <small class="opacity-75">
            <?= htmlspecialchars(($patient['nom'] ?? '') . ' ' . ($patient['prenom'] ?? '')) ?>
            &nbsp;·&nbsp;<?= htmlspecialchars($patient['dossier_numero'] ?? '') ?>
          </small>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <!-- Corps -->
      <div class="modal-body px-4 py-4">
        <div id="cst-alert" class="alert d-none mb-3" role="alert"></div>

        <?php if ($isEnfant): ?>
        <!-- ── FORMULAIRE PÉDIATRIQUE ── -->
        <div class="alert border-0 d-flex align-items-center gap-2 mb-4 py-2"
             style="background:#f0fdf4;color:#166534;">
          <i class="bi bi-info-circle-fill"></i>
          <small class="fw-semibold">Protocole pédiatrique — Poids · Taille · Température</small>
        </div>
        <form id="formConstantes" novalidate>
          <input type="hidden" name="patient_id" value="<?= (int)($patient['id'] ?? 0) ?>">
          <input type="hidden" name="profil" value="ENFANT">
          <div class="row g-4">
            <div class="col-6">
              <label class="form-label fw-semibold" style="color:#059669;">
                <i class="bi bi-person-standing me-1"></i>Poids <small class="text-muted">(kg)</small>
              </label>
              <input type="number" step="0.1" min="1" max="150" name="poids" id="cst-poids"
                     class="form-control form-control-lg text-center fw-bold"
                     placeholder="ex : 18.5"
                     style="border:2px solid #10b981;border-radius:12px;">
            </div>
            <div class="col-6">
              <label class="form-label fw-semibold" style="color:#059669;">
                <i class="bi bi-rulers me-1"></i>Taille <small class="text-muted">(cm)</small>
              </label>
              <input type="number" min="30" max="200" name="taille" id="cst-taille"
                     class="form-control form-control-lg text-center fw-bold"
                     placeholder="ex : 110"
                     style="border:2px solid #10b981;border-radius:12px;">
            </div>
            <div class="col-12">
              <label class="form-label fw-semibold text-danger">
                <i class="bi bi-thermometer-half me-1"></i>Température <small class="text-muted">(°C)</small>
              </label>
              <div class="position-relative">
                <input type="number" step="0.1" min="34" max="43" name="temperature" id="cst-temp"
                       class="form-control text-center fw-bold"
                       placeholder="ex : 37.5"
                       style="border:2px solid #ef4444;border-radius:12px;font-size:2rem;height:80px;">
                <span class="position-absolute top-50 end-0 translate-middle-y me-3 text-muted fs-4 fw-bold pe-none">°C</span>
              </div>
              <div id="cst-temp-badge" class="text-center mt-2" style="min-height:34px;"></div>
            </div>
          </div>
        </form>

        <?php else: ?>
        <!-- ── FORMULAIRE ADULTE ── -->
        <form id="formConstantes" novalidate>
          <input type="hidden" name="patient_id" value="<?= (int)($patient['id'] ?? 0) ?>">
          <input type="hidden" name="profil" value="ADULTE">

          <p class="text-muted fw-semibold small text-uppercase mb-2">
            <i class="bi bi-person-lines-fill me-1"></i>Anthropométrie
          </p>
          <div class="row g-3 mb-4">
            <div class="col-md-6">
              <label class="form-label fw-semibold">Poids <small class="text-muted">(kg)</small></label>
              <div class="input-group">
                <span class="input-group-text bg-light"><i class="bi bi-person-standing text-primary"></i></span>
                <input type="number" step="0.1" min="1" max="300" name="poids" id="cst-poids"
                       class="form-control" placeholder="ex : 70.5">
              </div>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Taille <small class="text-muted">(cm)</small></label>
              <div class="input-group">
                <span class="input-group-text bg-light"><i class="bi bi-rulers text-primary"></i></span>
                <input type="number" min="100" max="250" name="taille" id="cst-taille"
                       class="form-control" placeholder="ex : 175">
              </div>
            </div>
          </div>

          <p class="text-muted fw-semibold small text-uppercase mb-2">
            <i class="bi bi-heart-pulse me-1"></i>Constantes Vitales
          </p>
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label fw-semibold">Température <small class="text-muted">(°C)</small></label>
              <div class="input-group">
                <span class="input-group-text bg-light"><i class="bi bi-thermometer-half text-danger"></i></span>
                <input type="number" step="0.1" min="32" max="43" name="temperature" id="cst-temp"
                       class="form-control" placeholder="ex : 37.2">
              </div>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Pouls <small class="text-muted">(bpm)</small></label>
              <div class="input-group">
                <span class="input-group-text bg-light"><i class="bi bi-heart-pulse text-danger"></i></span>
                <input type="number" min="20" max="300" name="frequence_cardiaque" id="cst-pouls"
                       class="form-control" placeholder="ex : 80">
              </div>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Saturation O₂ <small class="text-muted">(%)</small></label>
              <div class="input-group">
                <span class="input-group-text bg-light"><i class="bi bi-lungs text-primary"></i></span>
                <input type="number" min="50" max="100" name="spo2" id="cst-spo2"
                       class="form-control" placeholder="ex : 98">
              </div>
            </div>
            <div class="col-12">
              <label class="form-label fw-semibold">Tension Artérielle <small class="text-muted">(mmHg)</small></label>
              <div class="d-flex align-items-center gap-2 flex-wrap">
                <div class="input-group" style="max-width:200px;">
                  <span class="input-group-text bg-light fw-bold" style="font-size:11px;">SYS</span>
                  <input type="number" min="50" max="250" name="tension_sys" id="cst-sys"
                         class="form-control" placeholder="ex : 120">
                </div>
                <span class="fw-bold text-muted fs-5">/</span>
                <div class="input-group" style="max-width:200px;">
                  <span class="input-group-text bg-light fw-bold" style="font-size:11px;">DIA</span>
                  <input type="number" min="30" max="180" name="tension_dia" id="cst-dia"
                         class="form-control" placeholder="ex : 80">
                </div>
              </div>
            </div>
          </div>
        </form>
        <?php endif; ?>
      </div>

      <!-- Pied -->
      <div class="modal-footer px-4 py-3 border-top">
        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Annuler</button>
        <button type="button" class="btn <?= $cstBtnClass ?> rounded-pill px-5 fw-bold"
                id="btnSauvegarderConstantes">
          <i class="bi bi-save2 me-2"></i>Enregistrer
        </button>
      </div>

    </div>
  </div>
</div>

<script>
(function () {
    const isEnfant = <?= $isEnfant ? 'true' : 'false' ?>;

    /* ── indicateur température (pédiatrie) ──────────────────────────── */
    const inputTemp = document.getElementById('cst-temp');
    if (isEnfant && inputTemp) {
        inputTemp.addEventListener('input', function () {
            const v     = parseFloat(this.value);
            const badge = document.getElementById('cst-temp-badge');
            if (!badge) return;
            if (isNaN(v) || this.value === '') { badge.innerHTML = ''; return; }
            let cls, label;
            if      (v < 36.0)              { cls = 'text-bg-info';    label = '🥶 Hypothermie'; }
            else if (v < 37.5)              { cls = 'text-bg-success'; label = '✅ Normale'; }
            else if (v < 38.5)              { cls = 'text-bg-warning'; label = '⚠️ Fébricule'; }
            else                            { cls = 'text-bg-danger';  label = '🔥 Fièvre'; }
            badge.innerHTML = `<span class="badge ${cls} fs-6 px-3 py-2">${label} — ${v} °C</span>`;
        });
    }

    /* ── soumission AJAX ─────────────────────────────────────────────── */
    document.getElementById('btnSauvegarderConstantes').addEventListener('click', function () {
        const btn  = this;
        const form = document.getElementById('formConstantes');
        const alrt = document.getElementById('cst-alert');

        alrt.className = 'alert d-none';
        btn.disabled   = true;
        btn.innerHTML  = '<span class="spinner-border spinner-border-sm me-2"></span>Enregistrement…';

        fetch('<?= BASE_URL ?>patients/save-constantes', {
            method : 'POST',
            body   : new FormData(form)
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                const p = d.data;
                /* mise à jour widgets */
                if (p.temperature != null)
                    document.getElementById('vt-temp').innerHTML    = p.temperature + '<span class="vc-unit"> °C</span>';
                if (p.poids != null)
                    document.getElementById('vt-poids').innerHTML   = p.poids + '<span class="vc-unit"> kg</span>';
                if (!isEnfant) {
                    if (p.pouls != null)
                        document.getElementById('vt-pouls').innerHTML = p.pouls + '<span class="vc-unit"> bpm</span>';
                    if (p.sys != null || p.dia != null)
                        document.getElementById('vt-tension').innerHTML =
                            (p.sys||'--') + '/' + (p.dia||'--') + '<span class="vc-unit"> mmHg</span>';
                }

                alrt.textContent = '✓ Constantes enregistrées avec succès.';
                alrt.className   = 'alert alert-success';

                setTimeout(() => {
                    bootstrap.Modal.getInstance(document.getElementById('modalConstantes')).hide();
                    alrt.className = 'alert d-none';
                    form.reset();
                    const b = document.getElementById('cst-temp-badge');
                    if (b) b.innerHTML = '';
                }, 1400);
            } else {
                alrt.textContent = '⚠ ' + (d.message || 'Echec de l\'enregistrement.');
                alrt.className   = 'alert alert-danger';
            }
        })
        .catch(() => {
            alrt.textContent = 'Erreur réseau. Veuillez réessayer.';
            alrt.className   = 'alert alert-danger';
        })
        .finally(() => {
            btn.disabled  = false;
            btn.innerHTML = '<i class="bi bi-save2 me-2"></i>Enregistrer';
        });
    });
})();
</script>

<?php if ($peutModifierInfos ?? false): ?>
<!-- ═══════════════════════════════════════════════════
     MODAL : MODIFIER INFOS PERSONNELLES PATIENT
═══════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalModifInfosPatient" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg" style="border-radius:16px;overflow:hidden;">
            <div class="modal-header border-0 p-4"
                 style="background:linear-gradient(135deg,#3b82f6,#1d4ed8);">
                <div>
                    <h5 class="modal-title fw-bold text-white mb-0">
                        <i class="bi bi-pencil-square me-2"></i>Modifier les informations
                    </h5>
                    <small class="text-white opacity-75">
                        <?= htmlspecialchars(strtoupper($patient['nom']) . ' ' . $patient['prenom']) ?>
                        — Dossier <?= htmlspecialchars($patient['dossier_numero'] ?? '') ?>
                    </small>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="alert alert-info border-0 rounded-3 mb-3" style="font-size:.85rem;">
                    <i class="bi bi-info-circle-fill me-2"></i>
                    Les informations modifiées ici sont <strong>administratives uniquement</strong>.
                    Les données médicales (antécédents, diagnostics…) doivent être modifiées via les consultations.
                </div>

                <form id="formModifInfos">
                    <input type="hidden" name="patient_id" value="<?= (int)$patient['id'] ?>">

                    <!-- Identité -->
                    <h6 class="fw-bold text-primary mb-3 pb-2 border-bottom">
                        <i class="bi bi-person-fill me-1"></i>Identité
                    </h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Nom <span class="text-danger">*</span></label>
                            <input type="text" name="nom" class="form-control" required
                                   value="<?= htmlspecialchars($patient['nom'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Prénom <span class="text-danger">*</span></label>
                            <input type="text" name="prenom" class="form-control" required
                                   value="<?= htmlspecialchars($patient['prenom'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Date de naissance</label>
                            <input type="date" name="date_naissance" class="form-control"
                                   value="<?= htmlspecialchars($patient['date_naissance'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Sexe</label>
                            <select name="sexe" class="form-select">
                                <option value="M" <?= ($patient['sexe'] ?? '') === 'M' ? 'selected' : '' ?>>Masculin</option>
                                <option value="F" <?= ($patient['sexe'] ?? '') === 'F' ? 'selected' : '' ?>>Féminin</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Groupe sanguin</label>
                            <select name="groupe_sanguin" class="form-select">
                                <option value="">— Non renseigné —</option>
                                <?php foreach (['O+','O-','A+','A-','B+','B-','AB+','AB-'] as $g): ?>
                                <option value="<?= $g ?>" <?= ($patient['groupe_sanguin'] ?? '') === $g ? 'selected' : '' ?>><?= $g ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Profession</label>
                            <input type="text" name="profession" class="form-control"
                                   value="<?= htmlspecialchars($patient['profession'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Situation matrimoniale</label>
                            <select name="situation_matrimoniale" class="form-select">
                                <option value="">— Non renseignée —</option>
                                <?php foreach (['Célibataire','Marié(e)','Divorcé(e)','Veuf(ve)','Concubinage'] as $s): ?>
                                <option value="<?= $s ?>" <?= ($patient['situation_matrimoniale'] ?? '') === $s ? 'selected' : '' ?>><?= $s ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Coordonnées -->
                    <h6 class="fw-bold text-primary mb-3 pb-2 border-bottom">
                        <i class="bi bi-telephone-fill me-1"></i>Coordonnées
                    </h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Téléphone</label>
                            <input type="tel" name="telephone" class="form-control"
                                   value="<?= htmlspecialchars($patient['telephone'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Email</label>
                            <input type="email" name="email" class="form-control"
                                   value="<?= htmlspecialchars($patient['email'] ?? '') ?>">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small fw-semibold">Adresse</label>
                            <input type="text" name="adresse" class="form-control"
                                   value="<?= htmlspecialchars($patient['adresse'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Ville / Quartier</label>
                            <input type="text" name="ville" class="form-control"
                                   value="<?= htmlspecialchars($patient['ville'] ?? '') ?>">
                        </div>
                    </div>

                    <!-- Contact d'urgence -->
                    <h6 class="fw-bold text-primary mb-3 pb-2 border-bottom">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i>Personne à contacter en urgence
                    </h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Nom du contact</label>
                            <input type="text" name="contact_nom" class="form-control"
                                   value="<?= htmlspecialchars($patient['contact_nom'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Téléphone du contact</label>
                            <input type="tel" name="contact_telephone" class="form-control"
                                   value="<?= htmlspecialchars($patient['contact_telephone'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Téléphone d'urgence (alternatif)</label>
                            <input type="tel" name="telephone_urgence" class="form-control"
                                   value="<?= htmlspecialchars($patient['telephone_urgence'] ?? '') ?>">
                        </div>
                    </div>

                    <!-- Type de prise en charge -->
                    <h6 class="fw-bold text-primary mb-3 pb-2 border-bottom">
                        <i class="bi bi-shield-fill me-1"></i>Type de prise en charge
                    </h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Type client</label>
                            <select name="type_client" class="form-select">
                                <option value="">— Non défini —</option>
                                <?php foreach ([
                                    'PAYANT_COMPTANT'     => 'Payant comptant',
                                    'ASSURANCE'           => 'Assurance',
                                    'BON_PRISE_EN_CHARGE' => 'Bon de prise en charge',
                                    'AGENTS_PHP'          => 'Agent PHP',
                                    'FAMILLE_PHP'         => 'Famille PHP',
                                    'GRATUIT'             => 'Gratuit',
                                ] as $k => $lbl): ?>
                                <option value="<?= $k ?>" <?= ($patient['type_client'] ?? '') === $k ? 'selected' : '' ?>><?= $lbl ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Nom de l'assurance</label>
                            <input type="text" name="nom_assurance" class="form-control"
                                   value="<?= htmlspecialchars($patient['nom_assurance'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">N° d'assuré</label>
                            <input type="text" name="numero_assure" class="form-control"
                                   value="<?= htmlspecialchars($patient['numero_assure'] ?? '') ?>">
                        </div>
                        <?php if (in_array('matricule_agent', array_keys($patient ?? []))): ?>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Matricule agent (si Agent PHP)</label>
                            <input type="text" name="matricule_agent" class="form-control"
                                   value="<?= htmlspecialchars($patient['matricule_agent'] ?? '') ?>">
                        </div>
                        <?php endif; ?>
                        <?php if (array_key_exists('numero_compte_sage', $patient ?? [])): ?>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">
                                <i class="bi bi-receipt me-1 text-success"></i>N° Compte SAGE
                                <span class="text-muted" style="font-size:.75rem;">(facturation)</span>
                            </label>
                            <input type="text" name="numero_compte_sage" class="form-control"
                                   placeholder="Ex: CLI-00123"
                                   value="<?= htmlspecialchars($patient['numero_compte_sage'] ?? '') ?>">
                        </div>
                        <?php endif; ?>
                    </div>

                    <div id="modifInfosResult" class="mt-3" style="display:none;"></div>
                </form>
            </div>
            <div class="modal-footer border-0 px-4 pb-4 pt-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Annuler</button>
                <button type="button" id="btnConfirmerModifInfos"
                        class="btn rounded-pill px-4 fw-semibold text-white"
                        style="background:linear-gradient(135deg,#3b82f6,#1d4ed8);border:none;"
                        onclick="confirmerModifInfos()">
                    <i class="bi bi-check-lg me-1"></i> Enregistrer
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function confirmerModifInfos() {
    const form = document.getElementById('formModifInfos');
    const fd = new FormData(form);
    const patientId = fd.get('patient_id');
    const result = document.getElementById('modifInfosResult');
    const btn = document.getElementById('btnConfirmerModifInfos');

    // Validation min : nom + prénom obligatoires
    if (!fd.get('nom').trim() || !fd.get('prenom').trim()) {
        result.style.display = 'block';
        result.innerHTML = '<div class="alert alert-danger mb-0">Le nom et le prénom sont obligatoires.</div>';
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Enregistrement…';
    result.style.display = 'none';

    fetch('<?= BASE_URL ?>patients/update-info/' + patientId, {
        method: 'POST',
        body: fd,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        result.style.display = 'block';
        if (data.success) {
            result.innerHTML = '<div class="alert alert-success mb-0">' + data.message + '</div>';
            setTimeout(() => location.reload(), 1200);
        } else {
            result.innerHTML = '<div class="alert alert-danger mb-0">❌ ' + (data.message || 'Erreur') + '</div>';
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check-lg me-1"></i> Enregistrer';
        }
    })
    .catch(() => {
        result.style.display = 'block';
        result.innerHTML = '<div class="alert alert-danger mb-0">Erreur réseau</div>';
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-lg me-1"></i> Enregistrer';
    });
}
</script>
<?php endif; ?>

<!-- ══════════════════════════════════════════════════════════════
     MODAL — Nouvelle Consultation (choix du type + démarrage)
     ══════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalNouvelleConsultation" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content rounded-4 border-0 shadow-lg">
      <div class="modal-header border-0 pb-0" style="background:linear-gradient(135deg,#1b5e20,#2e7d32);border-radius:16px 16px 0 0;">
        <div class="d-flex align-items-center gap-2 py-1">
          <div style="width:36px;height:36px;background:rgba(255,255,255,.18);border-radius:10px;display:flex;align-items:center;justify-content:center;">
            <i class="bi bi-plus-circle-fill text-white fs-5"></i>
          </div>
          <div>
            <div class="text-white fw-700" style="font-size:.95rem;font-weight:700;">Nouvelle Consultation</div>
            <div class="text-white opacity-75" style="font-size:.75rem;">
              <?= htmlspecialchars($patient['nom'] . ' ' . $patient['prenom']) ?>
            </div>
          </div>
        </div>
        <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
        <?php if ($isGyneco): ?>
        <!-- ── Médecin gynécologue : accès direct au formulaire spécialisé ── -->
        <p class="text-muted mb-3" style="font-size:.85rem;">
            <i class="bi bi-info-circle me-1"></i>
            Choisissez le type de consultation :
        </p>
        <div class="d-grid gap-2">
          <!-- Consultation gynécologique (formulaire spécialisé) -->
          <a href="<?= BASE_URL ?>formulaire/creer/consultation-gyneco/<?= $patient['id'] ?>"
             class="btn rounded-3 text-start p-3"
             style="background:linear-gradient(135deg,#fce7f3,#fbcfe8);border:1.5px solid #f472b6;text-decoration:none;">
            <div class="fw-700" style="font-size:.9rem;color:#be185d;">
              <i class="bi bi-heart-pulse-fill me-2"></i>Consultation gynécologique
            </div>
            <div class="mt-1" style="font-size:.75rem;color:#9d174d;">Formulaire gynécologie / obstétrique complet</div>
          </a>

          <!-- Consultation standard (si besoin d'une visite générale) -->
          <form action="<?= BASE_URL ?>consultation/commencer" method="POST">
            <input type="hidden" name="patient_id" value="<?= $patient['id'] ?>">
            <input type="hidden" name="type_consultation" value="<?= $isHospitalise ? 'INTERNE' : 'EXTERNE' ?>">
            <button type="submit" class="btn btn-outline-secondary rounded-3 text-start p-3 w-100">
              <div class="fw-700" style="font-size:.9rem;">
                <i class="bi bi-file-earmark-text me-2"></i>Consultation générale
              </div>
              <div class="text-muted mt-1" style="font-size:.75rem;">Formulaire standard (7 étapes)</div>
            </button>
          </form>
        </div>

        <?php else: ?>
        <!-- ── Médecin généraliste / autre spécialité ── -->
        <form action="<?= BASE_URL ?>consultation/commencer" method="POST" id="formNouvelleConsult">
          <input type="hidden" name="patient_id" value="<?= $patient['id'] ?>">
          <input type="hidden" name="type_consultation" id="inputTypeConsult" value="">

          <p class="text-muted mb-3" style="font-size:.85rem;">
            <i class="bi bi-info-circle me-1"></i>
            Choisissez le type de consultation à créer :
          </p>

          <div class="d-grid gap-2">
            <!-- Interne (hospitalisé) -->
            <button type="button"
                    class="btn btn-outline-primary rounded-3 text-start p-3 type-consult-btn"
                    data-type="INTERNE"
                    <?= $isHospitalise ? 'style="border-color:#1565c0;background:#eff6ff;"' : '' ?>>
              <div class="fw-700" style="font-size:.9rem;">
                <i class="bi bi-hospital-fill me-2 text-primary"></i>Interne — patient hospitalisé
              </div>
              <div class="text-muted mt-1" style="font-size:.75rem;">Visite de chambre, suivi hospitalier</div>
            </button>

            <!-- Externe (ambulatoire) -->
            <button type="button"
                    class="btn btn-outline-success rounded-3 text-start p-3 type-consult-btn"
                    data-type="EXTERNE"
                    <?= !$isHospitalise ? 'style="border-color:#2e7d32;background:#f0fdf4;"' : '' ?>>
              <div class="fw-700" style="font-size:.9rem;">
                <i class="bi bi-person-walking me-2 text-success"></i>Externe — consultation ambulatoire
              </div>
              <div class="text-muted mt-1" style="font-size:.75rem;">Patient venu en consultation, non hospitalisé</div>
            </button>
          </div>
        </form>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<script>
function ouvrirModalConsultation() {
    const modal = new bootstrap.Modal(document.getElementById('modalNouvelleConsultation'));
    modal.show();

    // En mode généraliste, pré-sélectionner le type selon le statut du patient
    const inputType = document.getElementById('inputTypeConsult');
    if (inputType) {
        const isHospitalise = <?= json_encode($isHospitalise) ?>;
        inputType.value = isHospitalise ? 'INTERNE' : 'EXTERNE';
    }
}

document.querySelectorAll('.type-consult-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const type = this.dataset.type;
        const inputType = document.getElementById('inputTypeConsult');
        if (inputType) inputType.value = type;
        const form = document.getElementById('formNouvelleConsult');
        if (form) form.submit();
    });
});
</script>

<!-- ── CHART.JS + COURBES VITAUX ──────────────────────────────────────────── -->
<script src="<?= BASE_URL ?>public/js/chart.umd.min.js"></script>
<script>
const _VITAUX_DATA = <?= json_encode($vitaux_json) ?>;
let _chartVitaux = null;
let _courbeActive = 'temp';

const _COURBE_CFG = {
    temp:  { label:'Température (°C)',  color:'#ef4444', yMin:35,  yMax:42  },
    sys:   { label:'Systolique (mmHg)', color:'#3b82f6', yMin:60,  yMax:220 },
    dia:   { label:'Diastolique (mmHg)',color:'#6366f1', yMin:40,  yMax:140 },
    ta:    { label:'TA (mmHg)',         color:'#7c3aed', yMin:40,  yMax:220, multi: true },
    pouls: { label:'Pouls (bpm)',       color:'#16a34a', yMin:30,  yMax:180 },
    spo2:  { label:'SpO₂ (%)',         color:'#8b5cf6', yMin:80,  yMax:100 },
};

function toggleCourbesVitaux() {
    const panel = document.getElementById('panelCourbes');
    const icon  = document.getElementById('iconCourbes');
    const open  = panel.style.display !== 'none';
    panel.style.display = open ? 'none' : 'block';
    icon.style.transform = open ? '' : 'rotate(180deg)';
    if (!open && !_chartVitaux) {
        setTimeout(() => initChartVitaux('temp'), 50);
    }
}

function changerCourbe(key) {
    _courbeActive = key;
    document.querySelectorAll('[id^=btn-courbe-]').forEach(b => b.style.opacity = '0.6');
    document.getElementById('btn-courbe-' + key).style.opacity = '1';
    initChartVitaux(key);
}

function initChartVitaux(key) {
    const cfg    = _COURBE_CFG[key];
    const labels = _VITAUX_DATA.map(v => v.date);

    if (_chartVitaux) { _chartVitaux.destroy(); _chartVitaux = null; }

    const ctx = document.getElementById('chartVitaux');
    if (!ctx || typeof Chart === 'undefined') return;

    // ── Mode multi : TA = Systolique + Diastolique sur le même graphique ──
    let datasets;
    if (cfg.multi) {
        datasets = [
            {
                label: 'Systolique (mmHg)',
                data: _VITAUX_DATA.map(v => v.sys),
                borderColor: '#3b82f6',
                backgroundColor: '#3b82f614',
                borderWidth: 2.5,
                pointBackgroundColor: '#3b82f6',
                pointRadius: 4, tension: .3, fill: false, spanGaps: true,
            },
            {
                label: 'Diastolique (mmHg)',
                data: _VITAUX_DATA.map(v => v.dia),
                borderColor: '#6366f1',
                backgroundColor: '#6366f114',
                borderWidth: 2,
                borderDash: [5, 3],
                pointBackgroundColor: '#6366f1',
                pointRadius: 3, tension: .3, fill: true, spanGaps: true,
            }
        ];
    } else {
        datasets = [{
            label: cfg.label,
            data: _VITAUX_DATA.map(v => v[key]),
            borderColor: cfg.color,
            backgroundColor: cfg.color + '14',
            borderWidth: 2.5,
            pointBackgroundColor: cfg.color,
            pointRadius: 4, tension: .3, fill: true, spanGaps: true,
        }];
    }

    _chartVitaux = new Chart(ctx, {
        type: 'line',
        data: { labels, datasets },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: {
                legend: { display: cfg.multi, position: 'top', labels: { font: { size: 11 }, boxWidth: 14 } },
                tooltip: {
                    callbacks: {
                        label: c => (cfg.multi
                            ? c.dataset.label + ': ' + (c.parsed.y ?? '--') + ' mmHg'
                            : cfg.label.split('(')[0].trim() + ': ' + (c.parsed.y ?? '--'))
                    }
                }
            },
            scales: {
                x: { grid: { display: false }, ticks: { font: { size: 10 } } },
                y: {
                    min: cfg.yMin, max: cfg.yMax,
                    grid: { color: '#f1f5f9' },
                    ticks: { font: { size: 10 } },
                }
            }
        }
    });
    // Surbrillance bouton actif
    document.querySelectorAll('[id^=btn-courbe-]').forEach(b => b.style.opacity = '0.5');
    const activeBtn = document.getElementById('btn-courbe-' + key);
    if (activeBtn) { activeBtn.style.opacity = '1'; activeBtn.style.fontWeight = '800'; }
}
</script>

<!-- ══ MODAL TRANSMETTRE AU BLOC ══ -->
<div class="modal fade" id="modalBlocDemande" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width:440px">
    <div class="modal-content border-0 shadow-lg" style="border-radius:22px;overflow:hidden;">
      <div class="modal-header border-0" style="background:linear-gradient(135deg,#1e1b4b,#4f46e5,#7c3aed);padding:22px 26px 18px;">
        <div>
          <h5 class="modal-title text-white fw-bold mb-0"><i class="bi bi-scissors me-2"></i>Transmettre au Bloc Opératoire</h5>
          <p class="text-white mb-0" style="opacity:.7;font-size:.78rem;margin-top:3px;">La demande sera ajoutée à la file d'attente du bloc</p>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body px-4 py-3">
        <!-- Patient -->
        <div class="mb-3 p-3 rounded-3" style="background:#f8fafc;border:1.5px solid #e2e8f0;">
          <div style="font-size:.72rem;font-weight:800;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px;">Patient</div>
          <div id="blocPatientName" style="font-weight:800;font-size:.95rem;color:#0f172a;"></div>
          <div id="blocPatientDossier" style="font-size:.75rem;color:#64748b;"></div>
        </div>
        <!-- Anesthésiste (optionnel) -->
        <div class="mb-3">
          <label class="small fw-bold text-muted mb-1">Anesthésiste (optionnel)</label>
          <select id="blocAnesthId" class="form-select rounded-3">
            <option value="">— À désigner ultérieurement —</option>
            <?php
            try {
                if (!isset($db)) { require_once __DIR__ . '/../../config/database.php'; $db = (new Database())->getConnection(); }
                $anesths = $db->query("SELECT u.id, u.nom, u.prenom FROM users u
                    JOIN services s ON s.id = u.service_id
                    WHERE s.nom_service LIKE '%anesthés%' OR s.nom_service LIKE '%anesthes%' OR s.nom_service LIKE '%Bloc%'
                    ORDER BY u.nom")->fetchAll(PDO::FETCH_ASSOC);
                foreach ($anesths as $a) {
                    echo '<option value="'.(int)$a['id'].'">Dr. '.htmlspecialchars($a['prenom'].' '.$a['nom']).'</option>';
                }
            } catch (\Throwable $e) { /* silencieux */ }
            ?>
          </select>
        </div>
        <!-- Alerte -->
        <div style="background:#fef3c7;border:1.5px solid #fde68a;border-radius:12px;padding:10px 14px;font-size:.8rem;color:#92400e;display:flex;align-items:center;gap:8px;">
          <i class="bi bi-exclamation-triangle-fill"></i>
          Le statut du patient passera à <strong>À OPÉRER</strong>. Cette action est enregistrée dans l'audit.
        </div>
        <!-- Message retour -->
        <div id="blocMsg" class="mt-2" style="display:none;font-size:.83rem;font-weight:700;padding:8px 14px;border-radius:10px;"></div>
      </div>
      <div class="modal-footer border-0 px-4 pb-4 pt-0">
        <button type="button" class="btn btn-light rounded-pill px-4 fw-semibold" data-bs-dismiss="modal">Annuler</button>
        <button type="button" id="btnBlocConfirm" class="btn text-white rounded-pill px-5 fw-bold"
                style="background:linear-gradient(135deg,#1e1b4b,#6366f1);box-shadow:0 4px 14px rgba(79,70,229,.4);"
                onclick="confirmerBlocDemande()">
            <i class="bi bi-send-fill me-1"></i>Transmettre
        </button>
      </div>
    </div>
  </div>
</div>

<script>
let _blocPatientId = null;

function transmettreAnesthesie(patientId) {
    _blocPatientId = patientId;
    // Remplir les infos patient depuis les données de la page
    const nom    = <?= json_encode(strtoupper($patient['nom'] ?? '')) ?>;
    const prenom = <?= json_encode($patient['prenom'] ?? '') ?>;
    const doss   = <?= json_encode($patient['dossier_numero'] ?? '') ?>;
    document.getElementById('blocPatientName').textContent    = nom + ' ' + prenom;
    document.getElementById('blocPatientDossier').textContent = doss ? 'Dossier : ' + doss : '';
    document.getElementById('blocMsg').style.display = 'none';
    document.getElementById('btnBlocConfirm').disabled = false;
    document.getElementById('btnBlocConfirm').innerHTML = '<i class="bi bi-send-fill me-1"></i>Transmettre';
    new bootstrap.Modal(document.getElementById('modalBlocDemande')).show();
}

function confirmerBlocDemande() {
    if (!_blocPatientId) return;
    const btn  = document.getElementById('btnBlocConfirm');
    const msg  = document.getElementById('blocMsg');
    const anesth = document.getElementById('blocAnesthId').value;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Envoi…';

    const fd = new FormData();
    fd.append('patient_id',      _blocPatientId);
    fd.append('_csrf_token',     '<?= CsrfService::getToken() ?>');
    if (anesth) fd.append('anesthesiste_id', anesth);

    fetch('<?= BASE_URL ?>bloc/transmettre-demande', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                msg.style.cssText = 'display:block;background:#dcfce7;color:#166534;border:1px solid #86efac;';
                msg.innerHTML = '<i class="bi bi-check-circle-fill me-2"></i>Patient transmis au bloc avec succès !';
                btn.innerHTML = '<i class="bi bi-check2-circle me-1"></i>Transmis';
                setTimeout(() => {
                    bootstrap.Modal.getInstance(document.getElementById('modalBlocDemande')).hide();
                    location.reload();
                }, 1800);
            } else {
                msg.style.cssText = 'display:block;background:#fee2e2;color:#991b1b;border:1px solid #fca5a5;';
                msg.innerHTML = '<i class="bi bi-x-circle-fill me-2"></i>' + (d.message || 'Erreur lors de la transmission.');
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-send-fill me-1"></i>Transmettre';
            }
        })
        .catch(() => {
            msg.style.cssText = 'display:block;background:#fee2e2;color:#991b1b;border:1px solid #fca5a5;';
            msg.innerHTML = '<i class="bi bi-x-circle-fill me-2"></i>Erreur réseau.';
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-send-fill me-1"></i>Transmettre';
        });
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>