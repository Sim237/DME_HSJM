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

$patient          = $patient          ?? null;
$patients_service = $patients_service ?? [];
$medecins         = $medecins         ?? [];
$medicaments      = $medicaments      ?? [];
$patient_id_pre   = $_GET['patient_id'] ?? null;
$error            = $_GET['error']      ?? null;

$infirmierNom = trim(($_SESSION['user_prenom'] ?? '') . ' ' . ($_SESSION['user_nom'] ?? ''));

if (!function_exists('getInitials')) {
    function getInitials($nom, $prenom) {
        return strtoupper(substr($nom ?? '', 0, 1) . substr($prenom ?? '', 0, 1));
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Ordonnance Par Ordre</title>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Inter', system-ui, sans-serif; background: #f4f6fb; min-height: 100vh; }

/* ── Topbar ── */
.ord-topbar {
    position: sticky; top: 0; z-index: 100;
    display: flex; align-items: center; justify-content: space-between;
    padding: 0 32px; height: 60px;
    background: #fff; border-bottom: 1px solid #e8ecf3;
    box-shadow: 0 1px 8px rgba(0,0,0,.06);
}
.ord-back-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 6px 14px; border-radius: 8px; font-size: .82rem; font-weight: 600;
    background: #f1f3f9; border: none; color: #374151; cursor: pointer;
    text-decoration: none; transition: background .15s;
}
.ord-back-btn:hover { background: #e2e6f0; }
.po-badge {
    display: inline-flex; align-items: center; gap: 6px;
    background: #fef9c3; border: 1.5px solid #fde68a; color: #854d0e;
    border-radius: 20px; padding: 4px 14px; font-size: .8rem; font-weight: 700;
}

/* ── Prescripteur banner ── */
.prescripteur-banner {
    margin: 18px 32px 0;
    background: linear-gradient(110deg, #1e293b, #334155);
    border-radius: 14px; padding: 14px 24px;
    display: flex; align-items: center; gap: 16px;
    color: #fff;
}
.prescripteur-avatar {
    width: 42px; height: 42px; border-radius: 10px;
    background: rgba(255,255,255,.15); border: 2px solid rgba(255,255,255,.25);
    display: flex; align-items: center; justify-content: center;
    font-weight: 800; font-size: 1rem; flex-shrink: 0;
}
.prescripteur-info { font-size: .8rem; opacity: .75; }

/* ── Patient banner ── */
.ord-patient-banner {
    margin: 14px 32px 0;
    background: linear-gradient(110deg, #1d4ed8, #2563eb 50%, #06b6d4);
    border-radius: 14px; padding: 18px 24px;
    display: flex; align-items: center; gap: 16px;
    box-shadow: 0 4px 24px rgba(37,99,235,.18); color: #fff;
}
.ord-avatar {
    width: 48px; height: 48px; border-radius: 50%;
    background: rgba(255,255,255,.2); border: 2px solid rgba(255,255,255,.3);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.2rem; font-weight: 800; flex-shrink: 0;
}
.ord-patient-name { font-size: 1.05rem; font-weight: 800; }
.ord-patient-meta { font-size: .77rem; opacity: .85; }
.ord-meta-pill {
    display: inline-flex; align-items: center; gap: 4px;
    background: rgba(255,255,255,.15); border: 1px solid rgba(255,255,255,.2);
    border-radius: 20px; padding: 2px 10px; font-size: .7rem; font-weight: 600;
    margin-left: 8px;
}

/* ── Main grid ── */
.ord-body {
    display: grid; grid-template-columns: 1fr 480px; gap: 20px;
    padding: 18px 32px 40px; max-width: 1300px; margin: 0 auto;
}

/* ── Panel ── */
.ord-panel {
    background: #fff; border-radius: 16px; border: 1px solid #e8ecf3;
    box-shadow: 0 2px 12px rgba(0,0,0,.04);
    display: flex; flex-direction: column; overflow: hidden;
}
.ord-panel-header {
    padding: 14px 20px; border-bottom: 1px solid #f0f2f8;
    display: flex; align-items: center; gap: 10px;
}
.ord-panel-title { font-size: .88rem; font-weight: 700; color: #111827; }
.ord-panel-icon {
    width: 32px; height: 32px; border-radius: 8px;
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.icon-amber { background: #fef9c3; color: #92400e; }
.icon-blue  { background: #eff6ff; color: #2563eb; }
.icon-green { background: #f0fdf4; color: #16a34a; }

/* ── Sélections ── */
.select-form { padding: 16px 20px; display: flex; flex-direction: column; gap: 14px; }
.form-label { font-size: .75rem; font-weight: 700; color: #64748b;
              text-transform: uppercase; letter-spacing: .6px; margin-bottom: 5px; display: block; }
.form-select-styled, .form-input-styled {
    width: 100%; padding: 9px 14px; border-radius: 10px;
    border: 1.5px solid #e2e8f0; font-size: .85rem; color: #1e293b;
    outline: none; transition: border .15s; background: #fafbff;
}
.form-select-styled:focus, .form-input-styled:focus { border-color: #3b82f6; background: #fff; }
.medecin-sig-badge {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 2px 8px; border-radius: 20px; font-size: .68rem; font-weight: 700;
    margin-left: 6px;
}
.sig-ok  { background: #d1fae5; color: #065f46; }
.sig-none{ background: #fee2e2; color: #991b1b; }

/* ── Drug list ── */
.ord-search-wrap { padding: 12px 18px 8px; }
.ord-search {
    width: 100%; padding: 9px 14px 9px 38px; border: 1.5px solid #e2e8f0;
    border-radius: 10px; font-size: .84rem; outline: none;
    background: #f8fafc url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%2394a3b8' viewBox='0 0 16 16'%3E%3Cpath d='M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.099zm-5.242 1.656a5.5 5.5 0 1 1 0-11 5.5 5.5 0 0 1 0 11z'/%3E%3C/svg%3E") no-repeat 11px center;
    transition: border .15s, background .15s;
}
.ord-search:focus { border-color: #3b82f6; background-color: #fff; }
.ord-drug-list { flex: 1; overflow-y: auto; padding: 0 12px 12px; max-height: calc(100vh - 400px); }
.ord-drug-list::-webkit-scrollbar { width: 4px; }
.ord-drug-list::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
.drug-card {
    display: flex; align-items: center; gap: 10px; padding: 10px 12px; margin-bottom: 5px;
    border: 1.5px solid #f1f3f9; border-radius: 10px; background: #fafbff;
    cursor: pointer; transition: all .15s; position: relative;
}
.drug-card:hover { border-color: #93c5fd; background: #eff6ff; transform: translateX(2px); }
.drug-card.already-added { opacity: .45; cursor: default; pointer-events: none; }
.drug-card.already-added::after {
    content: '✓ Ajouté'; position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
    font-size: .68rem; font-weight: 700; color: #16a34a;
}
.drug-card-icon {
    width: 34px; height: 34px; border-radius: 8px; background: #eff6ff;
    display: flex; align-items: center; justify-content: center; color: #2563eb; flex-shrink: 0;
}
.drug-name { font-size: .82rem; font-weight: 700; color: #1e293b; }
.drug-sub  { font-size: .71rem; color: #64748b; margin-top: 1px; }
.stock-badge { margin-left: auto; flex-shrink: 0; padding: 2px 8px; border-radius: 20px; font-size: .67rem; font-weight: 700; }
.stock-ok   { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; }
.stock-low  { background: #fff7ed; color: #c2410c; border: 1px solid #fed7aa; }
.stock-none { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }

/* ── Hors-stock input section ── */
.hs-section {
    margin: 0 12px 12px; padding: 12px 14px;
    border: 1.5px dashed #fbbf24; border-radius: 12px;
    background: #fffbeb;
}
.hs-section-title {
    font-size: .72rem; font-weight: 700; color: #92400e;
    text-transform: uppercase; letter-spacing: .5px; margin-bottom: 9px;
    display: flex; align-items: center; gap: 6px;
}
.hs-input-row { display: flex; gap: 7px; }
.hs-input {
    flex: 1; padding: 7px 11px; border: 1.5px solid #fcd34d; border-radius: 9px;
    font-size: .82rem; color: #1e293b; outline: none; background: #fff;
    transition: border .15s;
}
.hs-input:focus { border-color: #f59e0b; }
.hs-add-btn {
    padding: 7px 14px; border: none; border-radius: 9px;
    background: #f59e0b; color: #fff; font-size: .8rem; font-weight: 700;
    cursor: pointer; transition: background .15s; white-space: nowrap;
}
.hs-add-btn:hover { background: #d97706; }
/* Hors-stock badge dans la liste ordonnance */
.pres-item.hs-item { border-left-color: #f59e0b; background: #fffbeb; }
.badge-hs {
    display: inline-flex; align-items: center; gap: 3px;
    background: #fef9c3; color: #92400e; border: 1px solid #fde68a;
    border-radius: 20px; padding: 1px 8px; font-size: .65rem; font-weight: 700;
    margin-left: 6px; vertical-align: middle;
}

/* ── Prescription panel ── */
.ord-right { display: flex; flex-direction: column; gap: 14px; }
.pres-list { flex: 1; padding: 12px; overflow-y: auto; max-height: 380px; }
.pres-list::-webkit-scrollbar { width: 4px; }
.pres-list::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
.pres-empty {
    display: flex; flex-direction: column; align-items: center;
    justify-content: center; padding: 36px 20px; color: #94a3b8; text-align: center;
}
.pres-item {
    background: #fafbff; border: 1.5px solid #e8ecf3; border-left: 4px solid #f59e0b;
    border-radius: 10px; padding: 11px 13px; margin-bottom: 9px;
}
.pres-item-head { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 9px; }
.pres-item-name { font-size: .83rem; font-weight: 700; color: #1e293b; }
.pres-item-sub  { font-size: .71rem; color: #64748b; margin-top: 1px; }
.pres-remove-btn {
    width: 24px; height: 24px; border-radius: 6px; border: none;
    background: #fee2e2; color: #dc2626; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
}
.pres-fields { display: grid; grid-template-columns: 1fr 1fr; gap: 7px; }
.pres-field label { font-size: .68rem; font-weight: 600; color: #64748b;
                    text-transform: uppercase; letter-spacing: .4px; display: block; margin-bottom: 2px; }
.pres-field input {
    width: 100%; padding: 5px 9px; border-radius: 7px;
    border: 1.5px solid #e2e8f0; font-size: .77rem; color: #1e293b; outline: none;
    transition: border .15s;
}
.pres-field input:focus { border-color: #f59e0b; }
.pres-count-badge {
    margin-left: auto; padding: 2px 9px; border-radius: 20px;
    font-size: .72rem; font-weight: 700; background: #fef9c3; color: #92400e;
}

/* ── Notes & submit ── */
.ord-notes-panel { padding: 14px; }
.ord-notes-label { font-size: .76rem; font-weight: 700; color: #374151;
                   text-transform: uppercase; letter-spacing: .5px; margin-bottom: 7px; }
.ord-notes-input {
    width: 100%; min-height: 72px; padding: 9px 13px;
    border: 1.5px solid #e2e8f0; border-radius: 10px;
    font-size: .82rem; color: #1e293b; resize: vertical; font-family: inherit; outline: none;
}
.ord-notes-input:focus { border-color: #f59e0b; }
.btn-submit-po {
    width: 100%; padding: 13px; border-radius: 12px; border: none;
    background: linear-gradient(135deg, #d97706, #f59e0b);
    color: #fff; font-size: .9rem; font-weight: 700; cursor: pointer;
    display: flex; align-items: center; justify-content: center; gap: 8px;
    transition: all .18s; box-shadow: 0 4px 16px rgba(217,119,6,.25);
}
.btn-submit-po:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(217,119,6,.35); }
.btn-submit-po:disabled { background: #cbd5e1; box-shadow: none; cursor: not-allowed; }
.btn-cancel {
    width: 100%; padding: 10px; border-radius: 10px; border: 1.5px solid #e2e8f0;
    background: #fff; color: #64748b; font-size: .82rem; font-weight: 600; cursor: pointer; margin-top: 7px;
}
</style>

<!-- ── Topbar ── -->
<div class="ord-topbar">
    <div style="display:flex;align-items:center;gap:12px;">
        <a href="javascript:history.back()" class="ord-back-btn">
            <i class="bi bi-arrow-left"></i> Retour
        </a>
        <div>
            <div style="font-size:1rem;font-weight:700;color:#111827;">Ordonnance Par Ordre</div>
            <div style="font-size:.75rem;color:#6b7280;">Prescription exécutée par ordre médical verbal</div>
        </div>
    </div>
    <div class="po-badge">
        <i class="bi bi-person-badge-fill"></i>
        Infirmier prescripteur — <?= htmlspecialchars($infirmierNom) ?>
    </div>
</div>

<?php if ($error): ?>
<div class="alert alert-danger mx-4 mt-3" style="margin:16px 32px 0;">
    <i class="bi bi-exclamation-triangle me-2"></i>
    <?= $error === 'invalid_data' ? 'Données incomplètes. Veuillez choisir un patient, un médecin et au moins un médicament.' : 'Erreur lors de l\'enregistrement. Veuillez réessayer.' ?>
</div>
<?php endif; ?>

<!-- ── Prescripteur ── -->
<div class="prescripteur-banner">
    <div class="prescripteur-avatar"><?= strtoupper(substr($_SESSION['user_prenom'] ?? 'I', 0, 1) . substr($_SESSION['user_nom'] ?? '', 0, 1)) ?></div>
    <div>
        <div style="font-weight:800;font-size:.95rem;">Inf. <?= htmlspecialchars($infirmierNom) ?></div>
        <div class="prescripteur-info"><?= htmlspecialchars($_SESSION['nom_service'] ?? 'Service') ?> — L'ordonnance portera la signature du médecin désigné avec la mention <strong style="color:#fde68a;">P/O</strong></div>
    </div>
</div>

<!-- ── Patient banner (dynamique) ── -->
<div id="patientBanner" class="ord-patient-banner" style="<?= $patient ? '' : 'display:none;' ?>">
    <div class="ord-avatar" id="patientAvatar">–</div>
    <div>
        <div class="ord-patient-name" id="patientName"><?= $patient ? htmlspecialchars(strtoupper($patient['nom']).' '.$patient['prenom']) : '' ?></div>
        <div class="ord-patient-meta" id="patientMeta">
            <?php if ($patient): ?>
            N° <?= htmlspecialchars($patient['dossier_numero']) ?>
            <?php if (!empty($patient['date_naissance'])): ?>
            <span class="ord-meta-pill"><?= date_diff(date_create($patient['date_naissance']), date_create('now'))->y ?> ans</span>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ── Main ── -->
<div class="ord-body">

    <!-- Colonne gauche : sélections + catalogue -->
    <div style="display:flex;flex-direction:column;gap:14px;">

        <!-- Panel sélections -->
        <div class="ord-panel">
            <div class="ord-panel-header">
                <div class="ord-panel-icon icon-amber"><i class="bi bi-person-badge-fill"></i></div>
                <span class="ord-panel-title">Paramètres de la prescription par ordre</span>
            </div>
            <div class="select-form">
                <!-- Patient -->
                <div>
                    <label class="form-label">
                        <i class="bi bi-person-fill me-1"></i>Patient <span style="color:#ef4444;">*</span>
                    </label>
                    <select class="form-select-styled" id="selectPatient" onchange="onPatientChange(this)">
                        <option value="">— Choisir un patient hospitalisé —</option>
                        <?php foreach ($patients_service as $ps): ?>
                        <option value="<?= $ps['id'] ?>"
                                data-nom="<?= htmlspecialchars(strtoupper($ps['nom']).' '.$ps['prenom']) ?>"
                                data-dossier="<?= htmlspecialchars($ps['dossier_numero']) ?>"
                                data-lit="<?= htmlspecialchars(($ps['nom_chambre'] ?? '').' — '.($ps['nom_lit'] ?? '')) ?>"
                                <?= ($patient_id_pre && $patient_id_pre == $ps['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($ps['nom'].' '.$ps['prenom']) ?>
                            <?php if ($ps['nom_chambre']): ?> (<?= htmlspecialchars($ps['nom_chambre'].' — '.$ps['nom_lit']) ?>)<?php endif; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (empty($patients_service)): ?>
                    <p class="mt-1" style="font-size:.75rem;color:#f43f5e;">
                        <i class="bi bi-info-circle me-1"></i>Aucun patient hospitalisé dans votre service actuellement.
                    </p>
                    <?php endif; ?>
                </div>

                <!-- Médecin signataire -->
                <div>
                    <label class="form-label">
                        <i class="bi bi-stethoscope me-1"></i>Médecin signataire <span style="color:#ef4444;">*</span>
                    </label>
                    <select class="form-select-styled" id="selectMedecin">
                        <option value="">— Choisir le médecin dont la signature sera apposée —</option>
                        <?php foreach ($medecins as $med): ?>
                        <option value="<?= $med['id'] ?>">
                            Dr. <?= htmlspecialchars($med['nom'].' '.$med['prenom']) ?>
                            <?php if ($med['specialite']): ?> — <?= htmlspecialchars($med['specialite']) ?><?php endif; ?>
                            <?= $med['has_signature'] ? ' ✓ Signature enregistrée' : ' ⚠ Pas de signature' ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="mt-1" style="font-size:.72rem;color:#64748b;">
                        <i class="bi bi-info-circle me-1"></i>
                        La signature et le cachet du médecin sélectionné apparaîtront sur l'ordonnance avec la mention «&nbsp;P/O — <?= htmlspecialchars($infirmierNom) ?>&nbsp;».
                    </p>
                </div>
            </div>
        </div>

        <!-- Catalogue médicaments -->
        <div class="ord-panel" style="flex:1;">
            <div class="ord-panel-header">
                <div class="ord-panel-icon icon-blue"><i class="bi bi-capsule-pill"></i></div>
                <span class="ord-panel-title">Catalogue de médicaments</span>
                <span style="margin-left:auto;font-size:.73rem;color:#94a3b8;"><?= count($medicaments) ?> disponibles</span>
            </div>
            <div class="ord-search-wrap">
                <input type="text" id="searchMedicament" class="ord-search" placeholder="Rechercher un médicament…">
            </div>
            <div class="ord-drug-list" id="drugList">
                <?php if ($medicaments): ?>
                <?php foreach ($medicaments as $med): ?>
                <div class="drug-card" id="drug-<?= $med['id'] ?>"
                     onclick="selectMedicament(<?= htmlspecialchars(json_encode($med)) ?>)"
                     data-name="<?= strtolower(htmlspecialchars($med['nom'].' '.$med['dosage'].' '.$med['forme'])) ?>">
                    <div class="drug-card-icon"><i class="bi bi-capsule"></i></div>
                    <div style="flex:1;min-width:0;">
                        <div class="drug-name"><?= htmlspecialchars($med['nom']) ?></div>
                        <div class="drug-sub"><?= htmlspecialchars($med['dosage'].' · '.$med['forme']) ?></div>
                    </div>
                    <span class="stock-badge <?= (int)$med['quantite'] > 5 ? 'stock-ok' : 'stock-low' ?>">
                        Stock&nbsp;<?= (int)$med['quantite'] ?>
                    </span>
                </div>
                <?php endforeach; ?>
                <?php else: ?>
                <div style="text-align:center;padding:24px 40px 12px;color:#94a3b8;font-size:.83rem;">
                    <i class="bi bi-box-seam" style="font-size:2rem;opacity:.4;display:block;margin-bottom:8px;"></i>
                    Aucun médicament en stock
                </div>
                <?php endif; ?>
            </div>

            <!-- ── Section hors stock ── -->
            <div class="hs-section">
                <div class="hs-section-title">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    Médicament hors stock pharmacie
                </div>
                <p style="font-size:.72rem;color:#78350f;margin-bottom:8px;line-height:1.4;">
                    Le médicament n'est pas disponible dans le catalogue ? Saisissez son nom ci-dessous.<br>
                    Il sera indiqué <strong>« Hors stock »</strong> sur l'ordonnance.
                </p>
                <div class="hs-input-row">
                    <input type="text" id="hsNomInput" class="hs-input"
                           placeholder="Nom du médicament, forme, dosage…"
                           onkeydown="if(event.key==='Enter'){event.preventDefault();ajouterHorsStock();}">
                    <button type="button" class="hs-add-btn" onclick="ajouterHorsStock()">
                        <i class="bi bi-plus-lg me-1"></i>Ajouter
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Colonne droite : ordonnance -->
    <div class="ord-right">
        <form action="<?= BASE_URL ?>prescription/par-ordre-save" method="POST" id="prescriptionForm" onsubmit="return beforeSubmit()">
            <input type="hidden" name="patient_id"  id="patientIdInput"  value="<?= (int)($patient_id_pre ?? 0) ?>">
            <input type="hidden" name="medecin_id"  id="medecinIdInput"  value="">
            <input type="hidden" name="medicaments" id="medicamentsInput" value="[]">

            <!-- Liste médicaments prescrits -->
            <div class="ord-panel">
                <div class="ord-panel-header">
                    <div class="ord-panel-icon icon-green"><i class="bi bi-file-medical"></i></div>
                    <span class="ord-panel-title">Ordonnance (par ordre)</span>
                    <span class="pres-count-badge" id="presCountBadge" style="display:none;">0</span>
                </div>
                <div class="pres-list" id="prescribedMedicaments">
                    <div class="pres-empty" id="presEmpty">
                        <div style="font-size:2.2rem;opacity:.25;margin-bottom:8px;"><i class="bi bi-clipboard2-pulse"></i></div>
                        <div style="font-size:.82rem;font-weight:600;color:#94a3b8;">Aucun médicament ajouté</div>
                        <div style="font-size:.73rem;color:#cbd5e1;margin-top:4px;">Cliquez sur un médicament à gauche</div>
                    </div>
                </div>
            </div>

            <!-- Notes -->
            <div class="ord-panel">
                <div class="ord-notes-panel">
                    <div class="ord-notes-label"><i class="bi bi-journal-text me-1"></i>Notes / Recommandations</div>
                    <textarea class="ord-notes-input" name="notes" placeholder="Conseils, précautions, posologie globale…"></textarea>
                </div>
            </div>

            <!-- Résumé par ordre -->
            <div class="ord-panel" style="padding:14px 18px;background:#fef9c3;border-color:#fde68a;">
                <div style="font-size:.8rem;font-weight:700;color:#92400e;margin-bottom:4px;">
                    <i class="bi bi-info-circle me-1"></i>Mention légale par ordre
                </div>
                <div style="font-size:.75rem;color:#78350f;line-height:1.5;">
                    Cette ordonnance sera établie par ordre verbal du médecin sélectionné.<br>
                    Elle portera la mention : <strong>« P/O — Inf. <?= htmlspecialchars($infirmierNom) ?> »</strong>
                    avec la signature et le cachet du médecin désigné.
                </div>
            </div>

            <!-- Boutons -->
            <div>
                <button type="submit" class="btn-submit-po" id="submitBtn" disabled>
                    <i class="bi bi-pen-fill"></i>
                    Créer l'ordonnance par ordre
                </button>
                <button type="button" class="btn-cancel" onclick="history.back()">Annuler</button>
            </div>
        </form>
    </div>
</div>

<script>
let medicamentList = [];  // Chaque entrée : { _key, id, nom, dosage, forme, posologie, duree, quantite, hors_stock }
let _keyCounter = 0;

/* ── Patient change ── */
function onPatientChange(sel) {
    const opt = sel.selectedOptions[0];
    const pid = sel.value;
    document.getElementById('patientIdInput').value = pid;
    const banner = document.getElementById('patientBanner');
    if (!pid) { banner.style.display = 'none'; return; }
    banner.style.display = 'flex';
    const nom = opt.dataset.nom || '';
    document.getElementById('patientName').textContent = nom;
    document.getElementById('patientAvatar').textContent = nom.substring(0,2).toUpperCase();
    document.getElementById('patientMeta').innerHTML =
        'N°&nbsp;' + (opt.dataset.dossier || '') +
        (opt.dataset.lit ? ' &nbsp;·&nbsp; <span style="background:rgba(255,255,255,.15);border-radius:12px;padding:2px 10px;font-size:.7rem;">' + opt.dataset.lit + '</span>' : '');
}

/* ── Sync selects ── */
document.getElementById('selectMedecin').addEventListener('change', function() {
    document.getElementById('medecinIdInput').value = this.value;
    checkSubmit();
});
document.getElementById('selectPatient').addEventListener('change', checkSubmit);

function checkSubmit() {
    const ok = !!document.getElementById('patientIdInput').value
            && !!document.getElementById('medecinIdInput').value
            && medicamentList.length > 0;
    document.getElementById('submitBtn').disabled = !ok;
}

function beforeSubmit() {
    if (!document.getElementById('patientIdInput').value ||
        !document.getElementById('medecinIdInput').value ||
        !medicamentList.length) {
        alert('Veuillez choisir un patient, un médecin et au moins un médicament.');
        return false;
    }
    return true;
}

/* ── Ajouter depuis le catalogue ── */
function selectMedicament(med) {
    if (medicamentList.some(m => m.id === med.id && !m.hors_stock)) return;
    const entry = {
        _key: ++_keyCounter,
        id: med.id, nom: med.nom,
        dosage: med.dosage || '', forme: med.forme || '',
        posologie: '', duree: '', quantite: 1, hors_stock: 0
    };
    medicamentList.push(entry);
    document.getElementById('drug-' + med.id)?.classList.add('already-added');
    renderPrescription(); updateInput();
}

/* ── Ajouter hors stock ── */
function ajouterHorsStock() {
    const inp = document.getElementById('hsNomInput');
    const nom = (inp.value || '').trim();
    if (!nom) { inp.focus(); return; }
    const entry = {
        _key: ++_keyCounter,
        id: null, nom: nom,
        dosage: '', forme: '',
        posologie: '', duree: '', quantite: 1, hors_stock: 1
    };
    medicamentList.push(entry);
    inp.value = '';
    renderPrescription(); updateInput();
    inp.focus();
}

/* ── Retirer un médicament ── */
function removeMedicament(key) {
    const med = medicamentList.find(m => m._key === key);
    if (!med) return;
    medicamentList = medicamentList.filter(m => m._key !== key);
    if (!med.hors_stock && med.id) {
        document.getElementById('drug-' + med.id)?.classList.remove('already-added');
    }
    renderPrescription(); updateInput();
}

/* ── Mettre à jour un champ ── */
function updateField(key, field, value) {
    const med = medicamentList.find(m => m._key === key);
    if (med) { med[field] = value; updateInput(); }
}

/* ── Rendu de l'ordonnance ── */
function renderPrescription() {
    const container = document.getElementById('prescribedMedicaments');
    const badge = document.getElementById('presCountBadge');
    const n = medicamentList.length;
    badge.style.display = n ? 'inline-flex' : 'none';
    badge.textContent = n;
    checkSubmit();
    if (n === 0) {
        container.innerHTML = `<div class="pres-empty" id="presEmpty">
            <div style="font-size:2.2rem;opacity:.25;margin-bottom:8px;"><i class="bi bi-clipboard2-pulse"></i></div>
            <div style="font-size:.82rem;font-weight:600;color:#94a3b8;">Aucun médicament ajouté</div>
            <div style="font-size:.73rem;color:#cbd5e1;margin-top:4px;">Cliquez sur un médicament à gauche</div>
        </div>`;
        return;
    }
    container.innerHTML = medicamentList.map(med => {
        const isHs = !!med.hors_stock;
        const subLine = isHs
            ? `<span class="badge-hs"><i class="bi bi-exclamation-triangle-fill"></i> Hors stock</span>`
            : `<span style="font-size:.71rem;color:#64748b;">${med.dosage}${med.forme ? ' · ' + med.forme : ''}</span>`;
        return `
        <div class="pres-item${isHs ? ' hs-item' : ''}">
            <div class="pres-item-head">
                <div>
                    <div class="pres-item-name">${escHtml(med.nom)}</div>
                    <div class="pres-item-sub">${subLine}</div>
                </div>
                <button type="button" class="pres-remove-btn" onclick="removeMedicament(${med._key})" title="Retirer">
                    <i class="bi bi-x"></i>
                </button>
            </div>
            <div class="pres-fields">
                <div class="pres-field">
                    <label>Posologie</label>
                    <input type="text" placeholder="1 cp × 3/j" value="${escHtml(med.posologie)}"
                           oninput="updateField(${med._key}, 'posologie', this.value)">
                </div>
                <div class="pres-field">
                    <label>Durée</label>
                    <input type="text" placeholder="7 jours" value="${escHtml(med.duree)}"
                           oninput="updateField(${med._key}, 'duree', this.value)">
                </div>
            </div>
        </div>`;
    }).join('');
}

function escHtml(str) {
    const d = document.createElement('div'); d.textContent = str || ''; return d.innerHTML;
}

function updateInput() {
    // On envoie uniquement les champs utiles au serveur (sans _key)
    const toSend = medicamentList.map(({ _key, dosage, forme, ...rest }) => rest);
    document.getElementById('medicamentsInput').value = JSON.stringify(toSend);
}

/* ── Recherche dans le catalogue ── */
document.getElementById('searchMedicament').addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('.drug-card').forEach(c => {
        c.style.display = (!q || c.dataset.name.includes(q)) ? '' : 'none';
    });
});

/* ── Init si patient pré-sélectionné ── */
(function() {
    const sel = document.getElementById('selectPatient');
    if (sel.value) onPatientChange(sel);
})();
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
