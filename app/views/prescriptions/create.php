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

$patient     = $patient     ?? [];
$medicaments = $medicaments ?? [];
$patient_id  = $_GET['patient_id'] ?? null;

$age = 'N/A';
if (!empty($patient['date_naissance'])) {
    $age = date_diff(date_create($patient['date_naissance']), date_create('now'))->y . ' ans';
}

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
<title>Nouvelle Ordonnance</title>
<style>
/* ── Reset & base ─────────────────────────────────────────────────────── */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Inter', system-ui, sans-serif; background: #f4f6fb; min-height: 100vh; }

/* ── Topbar ───────────────────────────────────────────────────────────── */
.ord-topbar {
    position: sticky; top: 0; z-index: 100;
    display: flex; align-items: center; justify-content: space-between;
    padding: 0 32px; height: 60px;
    background: #fff;
    border-bottom: 1px solid #e8ecf3;
    box-shadow: 0 1px 8px rgba(0,0,0,.06);
}
.ord-topbar-left { display: flex; align-items: center; gap: 12px; }
.ord-back-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 6px 14px; border-radius: 8px; font-size: .82rem; font-weight: 600;
    background: #f1f3f9; border: none; color: #374151; cursor: pointer;
    text-decoration: none; transition: background .15s;
}
.ord-back-btn:hover { background: #e2e6f0; }
.ord-page-title { font-size: 1rem; font-weight: 700; color: #111827; }
.ord-breadcrumb { font-size: .78rem; color: #6b7280; }
.ord-breadcrumb a { color: #3b82f6; text-decoration: none; }

/* ── Patient banner ───────────────────────────────────────────────────── */
.ord-patient-banner {
    margin: 24px 32px 0;
    border-radius: 16px; overflow: hidden;
    background: linear-gradient(110deg, #1d4ed8 0%, #2563eb 45%, #06b6d4 100%);
    padding: 20px 28px;
    display: flex; align-items: center; gap: 16px;
    box-shadow: 0 4px 24px rgba(37,99,235,.18);
}
.ord-avatar {
    width: 52px; height: 52px; border-radius: 50%;
    background: rgba(255,255,255,.2); border: 2px solid rgba(255,255,255,.35);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.25rem; font-weight: 800; color: #fff; flex-shrink: 0;
}
.ord-patient-name { font-size: 1.1rem; font-weight: 800; color: #fff; margin-bottom: 2px; }
.ord-patient-meta { font-size: .78rem; color: rgba(255,255,255,.8); }
.ord-meta-pill {
    display: inline-flex; align-items: center; gap: 4px;
    background: rgba(255,255,255,.15); border: 1px solid rgba(255,255,255,.2);
    border-radius: 20px; padding: 2px 10px; font-size: .72rem; font-weight: 600;
    color: rgba(255,255,255,.9); margin-left: 8px;
}

/* ── Main grid ────────────────────────────────────────────────────────── */
.ord-body {
    display: grid;
    grid-template-columns: 1fr 480px;
    gap: 20px;
    padding: 20px 32px 40px;
    max-width: 1300px;
    margin: 0 auto;
}

/* ── Panel générique ──────────────────────────────────────────────────── */
.ord-panel {
    background: #fff;
    border-radius: 16px;
    border: 1px solid #e8ecf3;
    box-shadow: 0 2px 12px rgba(0,0,0,.04);
    display: flex;
    flex-direction: column;
    overflow: hidden;
}
.ord-panel-header {
    padding: 16px 20px;
    border-bottom: 1px solid #f0f2f8;
    display: flex; align-items: center; gap: 10px;
}
.ord-panel-title {
    font-size: .88rem; font-weight: 700; color: #111827; letter-spacing: .2px;
}
.ord-panel-icon {
    width: 32px; height: 32px; border-radius: 8px;
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.icon-blue  { background: #eff6ff; color: #2563eb; }
.icon-green { background: #f0fdf4; color: #16a34a; }

/* ── Search box ───────────────────────────────────────────────────────── */
.ord-search-wrap { padding: 14px 20px 10px; }
.ord-search {
    width: 100%; padding: 10px 16px 10px 40px;
    border: 1.5px solid #e2e8f0; border-radius: 10px;
    font-size: .85rem; color: #1e293b; background: #f8fafc;
    outline: none; transition: border .18s, background .18s;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%2394a3b8' viewBox='0 0 16 16'%3E%3Cpath d='M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.099zm-5.242 1.656a5.5 5.5 0 1 1 0-11 5.5 5.5 0 0 1 0 11z'/%3E%3C/svg%3E");
    background-repeat: no-repeat; background-position: 12px center;
}
.ord-search:focus { border-color: #3b82f6; background: #fff; }

/* ── Drug list ────────────────────────────────────────────────────────── */
.ord-drug-list {
    flex: 1; overflow-y: auto; padding: 0 14px 14px;
    max-height: calc(100vh - 300px);
}
.ord-drug-list::-webkit-scrollbar { width: 4px; }
.ord-drug-list::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }

.drug-card {
    display: flex; align-items: center; gap: 12px;
    padding: 11px 14px; margin-bottom: 6px;
    border: 1.5px solid #f1f3f9; border-radius: 10px;
    background: #fafbff; cursor: pointer;
    transition: all .16s;
    position: relative;
}
.drug-card:hover { border-color: #93c5fd; background: #eff6ff; transform: translateX(2px); }
.drug-card.already-added { opacity: .45; cursor: default; pointer-events: none; }
.drug-card.already-added::after {
    content: '✓ Ajouté'; position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
    font-size: .7rem; font-weight: 700; color: #16a34a;
}
.drug-card-icon {
    width: 36px; height: 36px; border-radius: 8px; background: #eff6ff;
    display: flex; align-items: center; justify-content: center;
    color: #2563eb; font-size: .95rem; flex-shrink: 0;
}
.drug-name { font-size: .83rem; font-weight: 700; color: #1e293b; }
.drug-sub  { font-size: .73rem; color: #64748b; margin-top: 1px; }
.stock-badge {
    margin-left: auto; flex-shrink: 0;
    padding: 2px 8px; border-radius: 20px; font-size: .68rem; font-weight: 700;
}
.stock-ok  { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; }
.stock-low { background: #fff7ed; color: #c2410c; border: 1px solid #fed7aa; }

/* ── Prescription panel ───────────────────────────────────────────────── */
.ord-right { display: flex; flex-direction: column; gap: 16px; }

.pres-list { flex: 1; padding: 14px; overflow-y: auto; max-height: 400px; }
.pres-list::-webkit-scrollbar { width: 4px; }
.pres-list::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }

.pres-empty {
    display: flex; flex-direction: column; align-items: center;
    justify-content: center; padding: 40px 20px; color: #94a3b8; text-align: center;
}
.pres-empty-icon { font-size: 2.5rem; opacity: .35; margin-bottom: 10px; }

/* ── Prescription item ────────────────────────────────────────────────── */
.pres-item {
    background: #fafbff;
    border: 1.5px solid #e8ecf3;
    border-left: 4px solid #3b82f6;
    border-radius: 10px;
    padding: 12px 14px;
    margin-bottom: 10px;
    position: relative;
    animation: slideIn .2s ease;
}
@keyframes slideIn { from { opacity:0; transform: translateY(-6px); } to { opacity:1; transform:none; } }
.pres-item-head {
    display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 10px;
}
.pres-item-name { font-size: .84rem; font-weight: 700; color: #1e293b; }
.pres-item-sub  { font-size: .72rem; color: #64748b; margin-top: 1px; }
.pres-remove-btn {
    width: 26px; height: 26px; border-radius: 6px; border: none;
    background: #fee2e2; color: #dc2626; cursor: pointer;
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
    transition: background .15s;
}
.pres-remove-btn:hover { background: #fca5a5; }
.pres-fields { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
/* Grille 3 colonnes quand le champ quantité est présent */
.pres-fields.has-qte { grid-template-columns: 1fr 1fr 130px; }

/* Champ quantité : nombre + unité côte à côte */
.qte-wrap {
    display: flex; align-items: center;
    border: 1.5px solid #e2e8f0; border-radius: 7px;
    overflow: hidden; background: #fff;
    transition: border-color .15s;
}
.qte-wrap:focus-within { border-color: #3b82f6; }
.qte-wrap input[type="number"] {
    width: 46px; padding: 6px 8px;
    border: none; outline: none;
    font-size: .82rem; font-weight: 700; color: #1e293b;
    background: transparent; text-align: center;
    -moz-appearance: textfield;
}
.qte-wrap input[type="number"]::-webkit-inner-spin-button,
.qte-wrap input[type="number"]::-webkit-outer-spin-button { -webkit-appearance: none; }
.qte-wrap .qte-sep {
    width: 1px; height: 18px; background: #e2e8f0; flex-shrink: 0;
}
.qte-wrap select {
    flex: 1; padding: 6px 6px;
    border: none; outline: none;
    font-size: .75rem; color: #475569;
    background: #f8fafc; cursor: pointer;
    border-left: 1px solid #e2e8f0;
    height: 100%;
}
.qte-wrap select:focus { background: #eff6ff; }
.pres-field label { font-size: .7rem; font-weight: 600; color: #64748b;
                    text-transform: uppercase; letter-spacing: .5px; display: block; margin-bottom: 3px; }
.pres-field input {
    width: 100%; padding: 6px 10px; border-radius: 7px;
    border: 1.5px solid #e2e8f0; font-size: .78rem; color: #1e293b;
    outline: none; background: #fff; transition: border .15s;
}
.pres-field input:focus { border-color: #3b82f6; }

/* ── Notes & submit ───────────────────────────────────────────────────── */
.ord-notes-panel { padding: 16px; }
.ord-notes-label { font-size: .78rem; font-weight: 700; color: #374151;
                   text-transform: uppercase; letter-spacing: .5px; margin-bottom: 8px; }
.ord-notes-input {
    width: 100%; min-height: 80px; padding: 10px 14px;
    border: 1.5px solid #e2e8f0; border-radius: 10px;
    font-size: .82rem; color: #1e293b; resize: vertical;
    font-family: inherit; outline: none; transition: border .15s;
}
.ord-notes-input:focus { border-color: #3b82f6; }

.ord-submit-panel { padding: 0 16px 16px; }
.btn-submit {
    width: 100%; padding: 13px; border-radius: 12px; border: none;
    background: linear-gradient(135deg, #1d4ed8, #2563eb);
    color: #fff; font-size: .9rem; font-weight: 700; cursor: pointer;
    display: flex; align-items: center; justify-content: center; gap: 8px;
    transition: all .18s; box-shadow: 0 4px 16px rgba(37,99,235,.25);
}
.btn-submit:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(37,99,235,.32); }
.btn-submit:disabled { background: #cbd5e1; box-shadow: none; cursor: not-allowed; }
.btn-cancel {
    width: 100%; padding: 10px; border-radius: 10px; border: 1.5px solid #e2e8f0;
    background: #fff; color: #64748b; font-size: .82rem; font-weight: 600;
    cursor: pointer; margin-top: 8px; transition: background .15s;
}
.btn-cancel:hover { background: #f8fafc; }

/* ── Counter badge ────────────────────────────────────────────────────── */
.pres-count-badge {
    margin-left: auto; padding: 2px 9px; border-radius: 20px;
    font-size: .72rem; font-weight: 700;
    background: #eff6ff; color: #1d4ed8;
}

/* ── Hors stock ───────────────────────────────────────────────────────── */
.hors-stock-zone {
    border-top: 1px dashed #e2e8f0;
    padding: 12px 14px 14px;
    background: #fdfaf7;
}
.btn-hors-stock {
    width: 100%; padding: 9px 14px;
    border: 1.5px dashed #f59e0b; border-radius: 10px;
    background: #fffbeb; color: #92400e;
    font-size: .8rem; font-weight: 700;
    cursor: pointer; display: flex; align-items: center;
    justify-content: center; gap: 7px; transition: all .15s;
}
.btn-hors-stock:hover { background: #fef3c7; border-color: #d97706; }
.hors-stock-form {
    display: none;
    margin-top: 10px;
    background: #fff; border: 1.5px solid #fde68a;
    border-radius: 10px; padding: 12px;
    animation: slideIn .18s ease;
}
.hors-stock-form.open { display: block; }
.hs-row { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 8px; }
.hs-field { display: flex; flex-direction: column; gap: 3px; }
.hs-field label { font-size: .68rem; font-weight: 700; color: #92400e;
                  text-transform: uppercase; letter-spacing: .5px; }
.hs-field input {
    padding: 7px 10px; border-radius: 8px;
    border: 1.5px solid #fde68a; font-size: .78rem; color: #1e293b;
    outline: none; background: #fffdf5;
    transition: border .15s;
}
.hs-field input:focus { border-color: #f59e0b; background: #fff; }
.btn-hs-add {
    width: 100%; padding: 8px; border-radius: 8px; border: none;
    background: #f59e0b; color: #fff; font-size: .8rem; font-weight: 700;
    cursor: pointer; display: flex; align-items: center;
    justify-content: center; gap: 6px; transition: background .15s;
}
.btn-hs-add:hover { background: #d97706; }
.pres-item.hors-stock-item { border-left-color: #f59e0b; background: #fffcf5; }
.hs-badge {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 1px 7px; border-radius: 20px; font-size: .66rem; font-weight: 700;
    background: #fef3c7; color: #92400e; border: 1px solid #fde68a;
    margin-left: 6px; vertical-align: middle;
}
</style>

<!-- ══ TOPBAR ═══════════════════════════════════════════════════════════════ -->
<div class="ord-topbar">
    <div class="ord-topbar-left">
        <a href="javascript:history.back()" class="ord-back-btn">
            <i class="bi bi-arrow-left"></i> Retour
        </a>
        <div>
            <div class="ord-page-title">Nouvelle Ordonnance</div>
            <div class="ord-breadcrumb">
                <a href="<?= BASE_URL ?>patients">Patients</a> /
                <a href="<?= BASE_URL ?>patients/dossier/<?= (int)$patient_id ?>">Dossier</a> /
                Ordonnance
            </div>
        </div>
    </div>
    <div style="font-size:.78rem;color:#94a3b8;">
        <i class="bi bi-calendar3 me-1"></i><?= date('d/m/Y') ?>
    </div>
</div>

<?php if ($patient): ?>

<!-- ══ PATIENT BANNER ════════════════════════════════════════════════════════ -->
<div class="ord-patient-banner">
    <div class="ord-avatar"><?= getInitials($patient['nom'], $patient['prenom']) ?></div>
    <div>
        <div class="ord-patient-name">
            <?= htmlspecialchars(strtoupper($patient['nom']) . ' ' . $patient['prenom']) ?>
        </div>
        <div class="ord-patient-meta">
            N° Dossier : <strong><?= htmlspecialchars($patient['dossier_numero']) ?></strong>
            <span class="ord-meta-pill"><i class="bi bi-person me-1"></i><?= $age ?></span>
            <span class="ord-meta-pill">
                <i class="bi bi-gender-<?= $patient['sexe'] === 'M' ? 'male' : 'female' ?> me-1"></i>
                <?= $patient['sexe'] === 'M' ? 'Masculin' : 'Féminin' ?>
            </span>
        </div>
    </div>
</div>

<!-- ══ MAIN ══════════════════════════════════════════════════════════════════ -->
<div class="ord-body">

    <!-- ── Colonne gauche : catalogue médicaments ─────────────────────────── -->
    <div class="ord-panel">
        <div class="ord-panel-header">
            <div class="ord-panel-icon icon-blue">
                <i class="bi bi-capsule-pill"></i>
            </div>
            <span class="ord-panel-title">Catalogue de médicaments</span>
            <span style="margin-left:auto;font-size:.75rem;color:#94a3b8;">
                <?= count($medicaments) ?> disponibles
            </span>
        </div>

        <div class="ord-search-wrap">
            <input type="text" id="searchMedicament" class="ord-search"
                   placeholder="Rechercher un médicament, dosage…">
        </div>

        <div class="ord-drug-list" id="drugList">
            <?php if (count($medicaments) > 0): ?>
                <?php foreach ($medicaments as $med): ?>
                <div class="drug-card" id="drug-<?= $med['id'] ?>"
                     onclick="selectMedicament(<?= htmlspecialchars(json_encode($med)) ?>)"
                     data-name="<?= strtolower(htmlspecialchars($med['nom'] . ' ' . $med['dosage'] . ' ' . $med['forme'])) ?>">
                    <div class="drug-card-icon"><i class="bi bi-capsule"></i></div>
                    <div class="flex-grow-1">
                        <div class="drug-name"><?= htmlspecialchars($med['nom']) ?></div>
                        <div class="drug-sub"><?= htmlspecialchars($med['dosage'] . ' · ' . $med['forme']) ?></div>
                    </div>
                    <span class="stock-badge <?= $med['quantite'] > 5 ? 'stock-ok' : 'stock-low' ?>">
                        Stock&nbsp;<?= (int)$med['quantite'] ?>
                    </span>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="text-align:center;padding:40px 20px;color:#94a3b8;font-size:.84rem;">
                    <i class="bi bi-box-seam" style="font-size:2rem;opacity:.4;display:block;margin-bottom:8px;"></i>
                    Aucun médicament en stock
                </div>
            <?php endif; ?>
        </div>

        <!-- ── Zone Hors Stock ─────────────────────────────────────────── -->
        <div class="hors-stock-zone">
            <button type="button" class="btn-hors-stock" onclick="toggleHorsStockForm()">
                <i class="bi bi-pencil-square"></i>
                Prescrire un médicament hors stock
            </button>
            <div class="hors-stock-form" id="horsStockForm">
                <div class="hs-row">
                    <div class="hs-field" style="grid-column:1/-1;">
                        <label>Nom du médicament <span style="color:#dc2626;">*</span></label>
                        <input type="text" id="hsNom" placeholder="Ex : Amoxicilline, Paracétamol…"
                               onkeydown="if(event.key==='Enter'){event.preventDefault();ajouterHorsStock();}">
                    </div>
                    <div class="hs-field">
                        <label>Dosage</label>
                        <input type="text" id="hsDosage" placeholder="500 mg">
                    </div>
                    <div class="hs-field">
                        <label>Forme</label>
                        <input type="text" id="hsForme" placeholder="Comprimé, Sirop…">
                    </div>
                </div>
                <button type="button" class="btn-hs-add" onclick="ajouterHorsStock()">
                    <i class="bi bi-plus-circle-fill"></i> Ajouter à l'ordonnance
                </button>
                <div id="hsError" style="display:none;margin-top:6px;font-size:.75rem;color:#dc2626;font-weight:600;"></div>
            </div>
        </div>
    </div>

    <!-- ── Colonne droite : ordonnance ────────────────────────────────────── -->
    <div class="ord-right">
        <form action="<?= BASE_URL ?>prescription/save" method="POST" id="prescriptionForm">
            <input type="hidden" name="patient_id" value="<?= htmlspecialchars($patient_id) ?>">
            <input type="hidden" name="medicaments" id="medicamentsInput" value="[]">

            <!-- Liste des médicaments prescrits -->
            <div class="ord-panel">
                <div class="ord-panel-header">
                    <div class="ord-panel-icon icon-green">
                        <i class="bi bi-file-medical"></i>
                    </div>
                    <span class="ord-panel-title">Ordonnance</span>
                    <span class="pres-count-badge" id="presCountBadge" style="display:none;">0</span>
                </div>

                <div class="pres-list" id="prescribedMedicaments">
                    <div class="pres-empty" id="presEmpty">
                        <div class="pres-empty-icon"><i class="bi bi-clipboard2-pulse"></i></div>
                        <div style="font-size:.83rem;font-weight:600;color:#94a3b8;">
                            Aucun médicament ajouté
                        </div>
                        <div style="font-size:.75rem;color:#cbd5e1;margin-top:4px;">
                            Cliquez sur un médicament à gauche
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notes -->
            <div class="ord-panel">
                <div class="ord-notes-panel">
                    <div class="ord-notes-label">Notes / Remarques</div>
                    <textarea class="ord-notes-input" name="notes"
                              placeholder="Conseils, précautions, posologie globale, observations…"></textarea>
                </div>
            </div>

            <!-- Boutons -->
            <div>
                <button type="submit" class="btn-submit" id="submitBtn" disabled>
                    <i class="bi bi-check-circle-fill"></i>
                    Valider l'Ordonnance
                </button>
                <button type="button" class="btn-cancel" onclick="history.back()">Annuler</button>
            </div>
        </form>
    </div>

</div>

<?php else: ?>
<div style="margin:40px 32px;">
    <div class="alert alert-danger">
        <i class="bi bi-exclamation-triangle me-2"></i>Patient introuvable.
    </div>
</div>
<?php endif; ?>

<script>
let medicamentList = [];
let _hsIdCounter   = -1; // IDs temporaires négatifs pour les médicaments hors stock

// ── Catalogue ────────────────────────────────────────────────────────────
function selectMedicament(med) {
    if (medicamentList.some(m => m.id === med.id)) return;

    medicamentList.push({
        id:         med.id,
        nom:        med.nom,
        dosage:     med.dosage,
        forme:      med.forme,
        posologie:  '',
        duree:      '',
        quantite:   1,
        unite:      'boîte(s)',
        hors_stock: false,
    });

    const card = document.getElementById('drug-' + med.id);
    if (card) card.classList.add('already-added');

    renderPrescription();
    updateInput();
}

// ── Hors stock ───────────────────────────────────────────────────────────
function toggleHorsStockForm() {
    const form = document.getElementById('horsStockForm');
    form.classList.toggle('open');
    if (form.classList.contains('open')) document.getElementById('hsNom').focus();
}

function ajouterHorsStock() {
    const nomEl    = document.getElementById('hsNom');
    const dosageEl = document.getElementById('hsDosage');
    const formeEl  = document.getElementById('hsForme');
    const errEl    = document.getElementById('hsError');

    const nom    = nomEl.value.trim();
    const dosage = dosageEl.value.trim();
    const forme  = formeEl.value.trim();

    if (!nom) {
        errEl.textContent = 'Le nom du médicament est obligatoire.';
        errEl.style.display = 'block';
        nomEl.focus();
        return;
    }
    errEl.style.display = 'none';

    const tempId = _hsIdCounter--;
    medicamentList.push({
        id:         tempId,        // négatif = hors stock
        nom:        nom,
        dosage:     dosage || '—',
        forme:      forme  || '—',
        posologie:  '',
        duree:      '',
        quantite:   1,
        unite:      'boîte(s)',
        hors_stock: true,
    });

    // Réinitialiser le formulaire
    nomEl.value = '';
    dosageEl.value = '';
    formeEl.value = '';

    renderPrescription();
    updateInput();
}

function removeMedicament(id) {
    id = parseInt(id); // s'assurer que c'est un nombre
    medicamentList = medicamentList.filter(m => m.id !== id);
    // Démarquer la card catalogue (seulement pour les médicaments en stock)
    if (id > 0) {
        const card = document.getElementById('drug-' + id);
        if (card) card.classList.remove('already-added');
    }
    renderPrescription();
    updateInput();
}

function updateField(id, field, value) {
    id = parseInt(id);
    const med = medicamentList.find(m => m.id === id);
    if (med) { med[field] = value; updateInput(); }
}

function renderPrescription() {
    const container = document.getElementById('prescribedMedicaments');
    const empty     = document.getElementById('presEmpty');
    const badge     = document.getElementById('presCountBadge');
    const btn       = document.getElementById('submitBtn');
    const n         = medicamentList.length;

    btn.disabled = (n === 0);
    badge.style.display = n ? 'inline-flex' : 'none';
    badge.textContent   = n;

    if (n === 0) {
        if (!document.getElementById('presEmpty')) {
            container.innerHTML = `
                <div class="pres-empty" id="presEmpty">
                    <div class="pres-empty-icon"><i class="bi bi-clipboard2-pulse"></i></div>
                    <div style="font-size:.83rem;font-weight:600;color:#94a3b8;">Aucun médicament ajouté</div>
                    <div style="font-size:.75rem;color:#cbd5e1;margin-top:4px;">Cliquez sur un médicament à gauche</div>
                </div>`;
        }
        return;
    }

    // Retirer l'empty state
    const oldEmpty = document.getElementById('presEmpty');
    if (oldEmpty) oldEmpty.remove();

    // Reconstruire tous les items
    container.innerHTML = medicamentList.map(med => {
        const isHS = med.hors_stock;
        const hsBadge = isHS
            ? `<span class="hs-badge"><i class="bi bi-exclamation-circle-fill"></i> Hors stock</span>`
            : '';
        return `
        <div class="pres-item${isHS ? ' hors-stock-item' : ''}">
            <div class="pres-item-head">
                <div>
                    <div class="pres-item-name">${escH(med.nom)}${hsBadge}</div>
                    <div class="pres-item-sub">${escH(med.dosage)} · ${escH(med.forme)}</div>
                </div>
                <button type="button" class="pres-remove-btn" onclick="removeMedicament(${med.id})" title="Retirer">
                    <i class="bi bi-x"></i>
                </button>
            </div>
            <div class="pres-fields has-qte">
                <div class="pres-field">
                    <label>Posologie</label>
                    <input type="text" placeholder="1 cp × 3/jour"
                           value="${escH(med.posologie)}"
                           oninput="updateField(${med.id}, 'posologie', this.value)">
                </div>
                <div class="pres-field">
                    <label>Durée</label>
                    <input type="text" placeholder="7 jours"
                           value="${escH(med.duree)}"
                           oninput="updateField(${med.id}, 'duree', this.value)">
                </div>
                <div class="pres-field">
                    <label>Quantité</label>
                    <div class="qte-wrap">
                        <input type="number" min="1" max="99"
                               value="${med.quantite || 1}"
                               oninput="updateField(${med.id}, 'quantite', parseInt(this.value)||1)">
                        <select onchange="updateField(${med.id}, 'unite', this.value)">
                            ${['boîte(s)','flacon(s)','sachet(s)','ampoule(s)','tube(s)','spray','comprimé(s)']
                                .map(u => `<option value="${u}"${(med.unite||'boîte(s)')=== u?' selected':''}>${u}</option>`)
                                .join('')}
                        </select>
                    </div>
                </div>
            </div>
        </div>`;
    }).join('');
}

function updateInput() {
    document.getElementById('medicamentsInput').value = JSON.stringify(medicamentList);
}

// Recherche live dans le catalogue
document.getElementById('searchMedicament').addEventListener('input', function () {
    const q = this.value.toLowerCase().trim();
    document.querySelectorAll('.drug-card').forEach(card => {
        card.style.display = (!q || card.dataset.name.includes(q)) ? '' : 'none';
    });
    // Mettre en évidence le bouton hors-stock si aucun résultat en stock
    const visible = [...document.querySelectorAll('.drug-card')].filter(c => c.style.display !== 'none').length;
    const btn = document.querySelector('.btn-hors-stock');
    if (btn) btn.style.borderStyle = (q && visible === 0) ? 'solid' : 'dashed';
});

function escH(s) {
    if (s === undefined || s === null) return '';
    return String(s).replace(/[&<>"']/g, c =>
        ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));
}
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
