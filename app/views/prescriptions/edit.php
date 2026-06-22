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

$age = 'N/A';
if (!empty($prescription['date_naissance'])) {
    $age = date_diff(date_create($prescription['date_naissance']), date_create('now'))->y . ' ans';
}
if (!function_exists('getInitials')) {
    function getInitials($nom, $prenom) {
        return strtoupper(substr($nom ?? '', 0, 1) . substr($prenom ?? '', 0, 1));
    }
}
$isSigned = ($prescription['statut'] === 'SIGNEE');
?>
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

/* ── Patient banner ── */
.ord-patient-banner {
    margin: 24px 32px 0;
    border-radius: 16px; overflow: hidden;
    background: linear-gradient(110deg, #7c3aed 0%, #a855f7 60%, #c084fc 100%);
    padding: 20px 28px;
    display: flex; align-items: center; gap: 16px;
    box-shadow: 0 4px 24px rgba(124,58,237,.18);
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

/* ── Alert si déjà signé ── */
.resign-alert {
    margin: 14px 32px 0;
    background: #fffbeb; border: 1.5px solid #fde68a; border-radius: 12px;
    padding: 10px 16px; font-size: .82rem; color: #92400e;
    display: flex; align-items: center; gap: 10px;
}

/* ── Body grid ── */
.ord-body {
    display: grid; grid-template-columns: 1fr 480px;
    gap: 20px; padding: 20px 32px 40px;
    max-width: 1300px; margin: 0 auto;
}

/* ── Panel ── */
.ord-panel {
    background: #fff; border-radius: 16px;
    border: 1px solid #e8ecf3;
    box-shadow: 0 2px 12px rgba(0,0,0,.04);
    display: flex; flex-direction: column; overflow: hidden;
}
.ord-panel-header {
    padding: 16px 20px; border-bottom: 1px solid #f0f2f8;
    display: flex; align-items: center; gap: 10px;
}
.ord-panel-title { font-size: .88rem; font-weight: 700; color: #111827; }
.ord-panel-icon {
    width: 32px; height: 32px; border-radius: 8px;
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.icon-blue   { background: #eff6ff; color: #2563eb; }
.icon-purple { background: #f5f3ff; color: #7c3aed; }

/* ── Search ── */
.ord-search-wrap { padding: 14px 20px 10px; }
.ord-search {
    width: 100%; padding: 10px 16px 10px 40px;
    border: 1.5px solid #e2e8f0; border-radius: 10px;
    font-size: .85rem; color: #1e293b; background: #f8fafc;
    outline: none; transition: border .18s;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%2394a3b8' viewBox='0 0 16 16'%3E%3Cpath d='M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.099zm-5.242 1.656a5.5 5.5 0 1 1 0-11 5.5 5.5 0 0 1 0 11z'/%3E%3C/svg%3E");
    background-repeat: no-repeat; background-position: 12px center;
}
.ord-search:focus { border-color: #7c3aed; background: #fff; }

/* ── Drug list ── */
.ord-drug-list { flex: 1; overflow-y: auto; padding: 0 14px 14px; max-height: calc(100vh - 300px); }
.ord-drug-list::-webkit-scrollbar { width: 4px; }
.ord-drug-list::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }

.drug-card {
    display: flex; align-items: center; gap: 12px;
    padding: 11px 14px; margin-bottom: 6px;
    border: 1.5px solid #f1f3f9; border-radius: 10px;
    background: #fafbff; cursor: pointer; transition: all .16s; position: relative;
}
.drug-card:hover { border-color: #c4b5fd; background: #f5f3ff; transform: translateX(2px); }
.drug-card.already-added { opacity: .45; cursor: default; pointer-events: none; }
.drug-card.already-added::after {
    content: '✓ Ajouté'; position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
    font-size: .7rem; font-weight: 700; color: #16a34a;
}
.drug-card-icon { width: 36px; height: 36px; border-radius: 8px; background: #f5f3ff; display: flex; align-items: center; justify-content: center; color: #7c3aed; flex-shrink: 0; }
.drug-name { font-size: .83rem; font-weight: 700; color: #1e293b; }
.drug-sub  { font-size: .73rem; color: #64748b; margin-top: 1px; }
.stock-badge { margin-left: auto; flex-shrink: 0; padding: 2px 8px; border-radius: 20px; font-size: .68rem; font-weight: 700; }
.stock-ok  { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; }
.stock-low { background: #fff7ed; color: #c2410c; border: 1px solid #fed7aa; }

/* ── Right column ── */
.ord-right { display: flex; flex-direction: column; gap: 16px; }
.pres-list { flex: 1; padding: 14px; overflow-y: auto; max-height: 420px; }
.pres-list::-webkit-scrollbar { width: 4px; }
.pres-list::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
.pres-empty { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 40px 20px; color: #94a3b8; text-align: center; }

/* ── Prescription item ── */
.pres-item {
    background: #fafbff; border: 1.5px solid #e8ecf3;
    border-left: 4px solid #7c3aed; border-radius: 10px;
    padding: 12px 14px; margin-bottom: 10px;
    animation: slideIn .2s ease;
}
@keyframes slideIn { from { opacity:0; transform:translateY(-6px); } to { opacity:1; transform:none; } }
.pres-item-head { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 10px; }
.pres-item-name { font-size: .84rem; font-weight: 700; color: #1e293b; }
.pres-item-sub  { font-size: .72rem; color: #64748b; margin-top: 1px; }
.pres-remove-btn { width: 26px; height: 26px; border-radius: 6px; border: none; background: #fee2e2; color: #dc2626; cursor: pointer; display: flex; align-items: center; justify-content: center; flex-shrink: 0; transition: background .15s; }
.pres-remove-btn:hover { background: #fca5a5; }
.pres-fields { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
.pres-fields.has-qte { grid-template-columns: 1fr 1fr 130px; }
.qte-wrap { display: flex; align-items: center; border: 1.5px solid #e2e8f0; border-radius: 7px; overflow: hidden; background: #fff; transition: border-color .15s; }
.qte-wrap:focus-within { border-color: #7c3aed; }
.qte-wrap input[type="number"] { width: 46px; padding: 6px 8px; border: none; outline: none; font-size: .82rem; font-weight: 700; color: #1e293b; background: transparent; text-align: center; -moz-appearance: textfield; }
.qte-wrap input[type="number"]::-webkit-inner-spin-button, .qte-wrap input[type="number"]::-webkit-outer-spin-button { -webkit-appearance: none; }
.qte-wrap select { flex: 1; padding: 6px 6px; border: none; outline: none; font-size: .75rem; color: #475569; background: #f8fafc; cursor: pointer; border-left: 1px solid #e2e8f0; height: 100%; }
.qte-wrap select:focus { background: #f5f3ff; }
.pres-field label { font-size: .7rem; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: .5px; display: block; margin-bottom: 3px; }
.pres-field input { width: 100%; padding: 6px 10px; border-radius: 7px; border: 1.5px solid #e2e8f0; font-size: .78rem; color: #1e293b; outline: none; background: #fff; transition: border .15s; }
.pres-field input:focus { border-color: #7c3aed; }

/* ── Notes & submit ── */
.ord-notes-panel { padding: 16px; }
.ord-notes-label { font-size: .78rem; font-weight: 700; color: #374151; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 8px; }
.ord-notes-input { width: 100%; min-height: 80px; padding: 10px 14px; border: 1.5px solid #e2e8f0; border-radius: 10px; font-size: .82rem; color: #1e293b; resize: vertical; font-family: inherit; outline: none; transition: border .15s; }
.ord-notes-input:focus { border-color: #7c3aed; }
.ord-submit-panel { padding: 0 16px 16px; }
.btn-submit {
    width: 100%; padding: 13px; border-radius: 12px; border: none;
    background: linear-gradient(135deg, #5b21b6, #7c3aed);
    color: #fff; font-size: .9rem; font-weight: 700; cursor: pointer;
    display: flex; align-items: center; justify-content: center; gap: 8px;
    transition: all .18s; box-shadow: 0 4px 16px rgba(124,58,237,.25);
}
.btn-submit:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(124,58,237,.35); }
.btn-submit:disabled { background: #cbd5e1; box-shadow: none; cursor: not-allowed; }
.btn-cancel { width: 100%; padding: 10px; border-radius: 10px; border: 1.5px solid #e2e8f0; background: #fff; color: #64748b; font-size: .82rem; font-weight: 600; cursor: pointer; margin-top: 8px; transition: background .15s; text-decoration: none; display: block; text-align: center; }
.btn-cancel:hover { background: #f8fafc; }
.pres-count-badge { margin-left: auto; padding: 2px 9px; border-radius: 20px; font-size: .72rem; font-weight: 700; background: #f5f3ff; color: #5b21b6; }
</style>

<!-- ══ TOPBAR ═══════════════════════════════════════════════════════════════ -->
<div class="ord-topbar">
    <div class="ord-topbar-left">
        <a href="<?= BASE_URL ?>prescription/print?id=<?= (int)$prescription['id'] ?>" class="ord-back-btn">
            <i class="bi bi-arrow-left"></i> Annuler
        </a>
        <div>
            <div class="ord-page-title">Modifier l'Ordonnance N°<?= str_pad($prescription['id'], 6, '0', STR_PAD_LEFT) ?></div>
            <div class="ord-breadcrumb">
                <a href="<?= BASE_URL ?>patients/dossier/<?= (int)$prescription['patient_id'] ?>">Dossier</a> /
                <a href="<?= BASE_URL ?>prescription/print?id=<?= (int)$prescription['id'] ?>">Ordonnance</a> /
                Modification
            </div>
        </div>
    </div>
    <div style="font-size:.78rem;color:#94a3b8;"><i class="bi bi-calendar3 me-1"></i><?= date('d/m/Y') ?></div>
</div>

<?php if ($isSigned): ?>
<div class="resign-alert">
    <i class="bi bi-exclamation-triangle-fill" style="color:#f59e0b;font-size:1.1rem;flex-shrink:0;"></i>
    <span>Cette ordonnance a déjà été <strong>signée et transmise à la pharmacie</strong>. La modifier la remettra en statut <strong>"En attente"</strong> et nécessitera une nouvelle signature.</span>
</div>
<?php endif; ?>

<!-- ══ PATIENT BANNER ════════════════════════════════════════════════════════ -->
<div class="ord-patient-banner">
    <div class="ord-avatar"><?= getInitials($prescription['patient_nom'] ?? '', $prescription['patient_prenom'] ?? '') ?></div>
    <div>
        <div class="ord-patient-name">
            <?= htmlspecialchars(strtoupper($prescription['patient_nom'] ?? '') . ' ' . ($prescription['patient_prenom'] ?? '')) ?>
        </div>
        <div class="ord-patient-meta">
            N° <?= htmlspecialchars($prescription['dossier_numero'] ?? '') ?>
            <span class="ord-meta-pill"><?= $age ?></span>
            <span class="ord-meta-pill"><?= ($prescription['sexe'] ?? '') === 'M' ? 'Masculin' : 'Féminin' ?></span>
        </div>
    </div>
</div>

<!-- ══ MAIN ══════════════════════════════════════════════════════════════════ -->
<div class="ord-body">

    <!-- Catalogue médicaments -->
    <div class="ord-panel">
        <div class="ord-panel-header">
            <div class="ord-panel-icon icon-purple"><i class="bi bi-capsule-pill"></i></div>
            <span class="ord-panel-title">Catalogue de médicaments</span>
            <span style="margin-left:auto;font-size:.75rem;color:#94a3b8;"><?= count($medicaments) ?> disponibles</span>
        </div>
        <div class="ord-search-wrap">
            <input type="text" id="searchMedicament" class="ord-search" placeholder="Rechercher un médicament, dosage…">
        </div>
        <div class="ord-drug-list" id="drugList">
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
        </div>
    </div>

    <!-- Ordonnance modifiée -->
    <div class="ord-right">
        <form action="<?= BASE_URL ?>prescription/update/<?= (int)$prescription['id'] ?>" method="POST" id="prescriptionForm">
            <input type="hidden" name="medicaments" id="medicamentsInput" value="[]">

            <div class="ord-panel">
                <div class="ord-panel-header">
                    <div class="ord-panel-icon icon-purple"><i class="bi bi-pencil-square"></i></div>
                    <span class="ord-panel-title">Ordonnance modifiée</span>
                    <span class="pres-count-badge" id="presCountBadge" style="display:none;">0</span>
                </div>
                <div class="pres-list" id="prescribedMedicaments">
                    <div class="pres-empty" id="presEmpty">
                        <div style="font-size:2rem;opacity:.3;margin-bottom:8px;">💊</div>
                        <div style="font-size:.83rem;font-weight:600;color:#94a3b8;">Aucun médicament</div>
                    </div>
                </div>
            </div>

            <div class="ord-panel">
                <div class="ord-notes-panel">
                    <div class="ord-notes-label">Notes / Remarques</div>
                    <textarea class="ord-notes-input" name="notes"
                              placeholder="Conseils, précautions…"><?= htmlspecialchars($prescription['recommandations'] ?? $prescription['notes'] ?? '') ?></textarea>
                </div>
            </div>

            <div>
                <button type="submit" class="btn-submit" id="submitBtn" disabled>
                    <i class="bi bi-check-circle-fill"></i>
                    Enregistrer les modifications
                </button>
                <a href="<?= BASE_URL ?>prescription/print?id=<?= (int)$prescription['id'] ?>" class="btn-cancel">Annuler</a>
            </div>
        </form>
    </div>
</div>

<script>
// Lignes existantes pré-chargées
let medicamentList = <?= json_encode($lignesActuelles) ?>;

// Initialisation : marquer les cartes déjà présentes
document.addEventListener('DOMContentLoaded', function() {
    medicamentList.forEach(m => {
        const card = document.getElementById('drug-' + m.id);
        if (card) card.classList.add('already-added');
    });
    renderPrescription();
    updateInput();
});

function selectMedicament(med) {
    if (medicamentList.some(m => m.id === med.id)) return;
    medicamentList.push({ id: med.id, nom: med.nom, dosage: med.dosage, forme: med.forme, posologie: '', duree: '', quantite: 1, unite: 'boîte(s)' });
    const card = document.getElementById('drug-' + med.id);
    if (card) card.classList.add('already-added');
    renderPrescription();
    updateInput();
}

function removeMedicament(id) {
    medicamentList = medicamentList.filter(m => m.id !== id);
    const card = document.getElementById('drug-' + id);
    if (card) card.classList.remove('already-added');
    renderPrescription();
    updateInput();
}

function updateField(id, field, value) {
    const med = medicamentList.find(m => m.id === id);
    if (med) { med[field] = value; updateInput(); }
}

function renderPrescription() {
    const container = document.getElementById('prescribedMedicaments');
    const badge = document.getElementById('presCountBadge');
    const btn   = document.getElementById('submitBtn');
    const n = medicamentList.length;
    btn.disabled = (n === 0);
    badge.style.display = n ? 'inline-flex' : 'none';
    badge.textContent = n;

    if (n === 0) {
        container.innerHTML = `<div class="pres-empty" id="presEmpty">
            <div style="font-size:2rem;opacity:.3;margin-bottom:8px;">💊</div>
            <div style="font-size:.83rem;font-weight:600;color:#94a3b8;">Aucun médicament</div></div>`;
        return;
    }

    container.innerHTML = medicamentList.map(med => `
        <div class="pres-item">
            <div class="pres-item-head">
                <div>
                    <div class="pres-item-name">${med.nom}</div>
                    <div class="pres-item-sub">${med.dosage || ''} ${med.forme ? '· ' + med.forme : ''}</div>
                </div>
                <button type="button" class="pres-remove-btn" onclick="removeMedicament(${med.id})">
                    <i class="bi bi-x"></i>
                </button>
            </div>
            <div class="pres-fields has-qte">
                <div class="pres-field">
                    <label>Posologie</label>
                    <input type="text" placeholder="1 cp × 3/jour" value="${med.posologie || ''}"
                           oninput="updateField(${med.id}, 'posologie', this.value)">
                </div>
                <div class="pres-field">
                    <label>Durée</label>
                    <input type="text" placeholder="7 jours" value="${med.duree || ''}"
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
        </div>`).join('');
}

function updateInput() {
    document.getElementById('medicamentsInput').value = JSON.stringify(medicamentList);
}

document.getElementById('searchMedicament').addEventListener('input', function() {
    const q = this.value.toLowerCase().trim();
    document.querySelectorAll('.drug-card').forEach(c => {
        c.style.display = (!q || c.dataset.name.includes(q)) ? '' : 'none';
    });
});
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
