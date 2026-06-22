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
 require_once __DIR__ . '/../layouts/header.php'; ?>
<style>
/* ─── Reset sidebar pour cette page ─── */
.sidebar, nav.sidebar { display: none !important; }
.main-content, .app-wrapper > *:not(.sidebar) { margin-left: 0 !important; }

/* ─── Layout ─── */
body { background: #f0f4f8; }
.pharma-shell {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}

/* ─── Topbar ─── */
.pharma-topbar {
    background: #fff;
    border-bottom: 1px solid #e5eaf2;
    padding: 14px 28px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: sticky;
    top: 0;
    z-index: 100;
}
.pharma-topbar .logo-area { display: flex; align-items: center; gap: 12px; }
.pharma-topbar .app-name  { font-weight: 800; font-size: .95rem; color: #1e293b; }
.pharma-topbar .page-title { font-size: 1rem; font-weight: 700; color: #1e293b; }
.pharma-topbar .ord-badge {
    background: #fff7ed;
    color: #c2410c;
    border: 1.5px solid #fed7aa;
    border-radius: 20px;
    font-size: .72rem;
    font-weight: 800;
    padding: 3px 12px;
    letter-spacing: .4px;
}

/* ─── Contenu ─── */
.pharma-body { flex: 1; padding: 28px 32px; max-width: 1100px; margin: 0 auto; width: 100%; }

/* ─── Patient card ─── */
.patient-card {
    background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%);
    color: #fff;
    border-radius: 16px;
    padding: 18px 24px;
    margin-bottom: 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 4px 20px rgba(30,64,175,.25);
}
.patient-card .pname { font-size: 1.15rem; font-weight: 800; letter-spacing: .3px; }
.patient-card .pmeta { font-size: .78rem; opacity: .78; margin-top: 3px; }
.patient-card .ord-num { text-align: right; }
.patient-card .ord-num span { font-size: 1.4rem; font-weight: 800; }

/* ─── Colonnes ─── */
.pharma-grid { display: grid; grid-template-columns: 1fr 320px; gap: 20px; }
@media (max-width: 820px) { .pharma-grid { grid-template-columns: 1fr; } }

/* ─── Tableau médicaments ─── */
.meds-card {
    background: #fff;
    border-radius: 14px;
    box-shadow: 0 2px 12px rgba(0,0,0,.06);
    overflow: hidden;
}
.meds-card-header {
    background: #f8fafc;
    padding: 12px 20px;
    border-bottom: 1px solid #e9eef5;
    font-size: .72rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .08em;
    color: #64748b;
    display: grid;
    grid-template-columns: 36px 2fr 2fr 1fr 110px 110px 180px;
    gap: 8px;
}
.med-row {
    display: grid;
    grid-template-columns: 36px 2fr 2fr 1fr 110px 110px 180px;
    gap: 8px;
    padding: 14px 20px;
    border-bottom: 1px solid #f1f5f9;
    align-items: center;
    transition: background .12s;
}
.med-row.med-uncheck { background: #fef2f2 !important; }
.med-row.med-uncheck .med-name { color: #991b1b; text-decoration: line-through; }
.med-row.med-uncheck .med-poso,
.med-row.med-uncheck .med-dur { color: #b91c1c; opacity: .7; }
.med-checkbox {
    width: 22px; height: 22px;
    accent-color: #16a34a;
    cursor: pointer;
    transition: transform .15s;
}
.med-checkbox:hover { transform: scale(1.1); }
.med-checkbox:disabled { cursor: not-allowed; opacity: .4; }
.checklist-banner {
    background: linear-gradient(135deg, #ecfdf5, #d1fae5);
    border: 1.5px solid #86efac;
    border-radius: 10px;
    padding: 10px 14px;
    margin: 0 20px 10px;
    font-size: .78rem;
    color: #065f46;
    display: flex;
    align-items: center;
    gap: 10px;
}
.checklist-counter {
    background: #fff;
    color: #0f172a;
    padding: 3px 9px;
    border-radius: 12px;
    font-weight: 800;
    font-size: .72rem;
    border: 1.5px solid #86efac;
}
.checklist-counter.has-uncheck { color: #991b1b; border-color: #fca5a5; background: #fef2f2; }
.med-row:last-child { border-bottom: none; }
.med-row:hover { background: #f8faff; }
.med-row.ligne-annulee { opacity:.55; background:#fafafa; }
.med-row.ligne-annulee .med-name,
.med-row.ligne-annulee .med-poso,
.med-row.ligne-annulee .med-dur { text-decoration: line-through; color:#94a3b8; }
.med-row.ligne-remplacante { background:#f0fdf4; border-left:3px solid #22c55e; }
.med-name { font-weight: 700; font-size: .9rem; color: #1e293b; }
.med-sub  { font-size: .72rem; color: #94a3b8; margin-top: 2px; }
.med-poso { font-size: .85rem; color: #475569; }
.med-dur  { font-size: .82rem; color: #64748b; }
.stock-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: .72rem;
    font-weight: 800;
    padding: 4px 10px;
    border-radius: 20px;
    letter-spacing: .3px;
}
.stock-ok      { background: #dcfce7; color: #15803d; }
.stock-low     { background: #fef9c3; color: #854d0e; }
.stock-out     { background: #fee2e2; color: #dc2626; }
.stock-na      { background: #f1f5f9; color: #94a3b8; }

/* ─── Formulaire édition inline ─── */
.edit-inline-form {
    display:none;
    grid-column: 1 / -1;
    background:#f0f9ff;
    border:1.5px solid #bae6fd;
    border-radius:10px;
    padding:14px 18px;
    margin: 4px 20px 10px;
}
.edit-inline-form.open { display:block; }
.edit-inline-form .row > div { margin-bottom: 8px; }
.edit-inline-form input, .edit-inline-form textarea {
    border-radius:8px;border:1.5px solid #bae6fd;
    padding:7px 12px;font-size:.85rem;width:100%;
}
.btn-edit-ligne {
    background:none;border:none;cursor:pointer;
    color:#6366f1;font-size:1rem;padding:2px 6px;
    border-radius:6px;transition:background .12s;
}
.btn-edit-ligne:hover { background:#eff6ff; }

/* ─── Bouton Rupture ─── */
.btn-rupture {
    display:inline-flex;align-items:center;gap:4px;
    border:none;cursor:pointer;font-size:.72rem;font-weight:700;
    padding:4px 11px;border-radius:20px;transition:all .15s;
    white-space:nowrap;
}
.btn-rupture.rupture-off {
    background:#fff7ed;color:#c2410c;border:1.5px solid #fed7aa;
}
.btn-rupture.rupture-off:hover { background:#ffedd5;border-color:#f97316; }
.btn-rupture.rupture-on  {
    background:#f0fdf4;color:#15803d;border:1.5px solid #86efac;
}
.btn-rupture.rupture-on:hover  { background:#dcfce7; }

/* ─── Ligne en rupture ─── */
.med-row.en-rupture {
    background: #fff7f7 !important;
    border-left: 3px solid #fca5a5;
}
.med-row.en-rupture .med-name { color:#b91c1c !important; text-decoration:line-through; }
.badge-rupture-ligne {
    display:inline-flex;align-items:center;gap:4px;
    background:#fee2e2;color:#dc2626;border:1px solid #fca5a5;
    font-size:.65rem;font-weight:800;padding:2px 8px;border-radius:20px;
    margin-top:3px;
}

@keyframes toastIn { from{transform:translateY(16px);opacity:0} to{transform:translateY(0);opacity:1} }

/* ─── Panneau droit ─── */
.side-panel { display: flex; flex-direction: column; gap: 16px; }

.allergy-card {
    background: #fff;
    border-radius: 14px;
    box-shadow: 0 2px 12px rgba(0,0,0,.06);
    padding: 16px 18px;
    border-left: 4px solid #ef4444;
}
.allergy-card.safe { border-left-color: #10b981; }
.allergy-title { font-size: .78rem; font-weight: 800; text-transform: uppercase; letter-spacing: .06em; color: #ef4444; margin-bottom: 6px; }
.allergy-card.safe .allergy-title { color: #059669; }

.validate-card {
    background: linear-gradient(145deg, #0f172a, #1e293b);
    border-radius: 14px;
    box-shadow: 0 6px 24px rgba(0,0,0,.18);
    padding: 22px 20px;
    color: #fff;
}
.validate-card h5 { font-weight: 800; font-size: .95rem; margin-bottom: 8px; }
.validate-card p  { font-size: .78rem; opacity: .65; line-height: 1.6; margin-bottom: 18px; }

.btn-deliver {
    width: 100%;
    background: #fff;
    color: #0f172a;
    border: none;
    border-radius: 10px;
    padding: 13px;
    font-weight: 800;
    font-size: .88rem;
    letter-spacing: .3px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    cursor: pointer;
    transition: all .18s;
    box-shadow: 0 2px 8px rgba(0,0,0,.12);
}
.btn-deliver:hover:not(:disabled) { background: #f0fdf4; transform: translateY(-1px); box-shadow: 0 4px 16px rgba(0,0,0,.18); }
.btn-deliver:disabled { opacity: .55; cursor: not-allowed; }
.btn-deliver .icon-check { width: 20px; height: 20px; background: #10b981; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.btn-cancel { display: block; text-align: center; color: rgba(255,255,255,.5); font-size: .78rem; margin-top: 12px; text-decoration: none; }
.btn-cancel:hover { color: rgba(255,255,255,.85); }

/* ─── Toast ─── */
.pharma-toast {
    position: fixed;
    bottom: 28px; right: 28px;
    background: #0f172a;
    color: #fff;
    padding: 14px 20px;
    border-radius: 12px;
    font-size: .84rem;
    font-weight: 600;
    box-shadow: 0 8px 32px rgba(0,0,0,.25);
    z-index: 9999;
    display: none;
    align-items: center;
    gap: 10px;
    max-width: 320px;
}
.pharma-toast.show { display: flex; }
.pharma-toast.success { border-left: 4px solid #10b981; }
.pharma-toast.error   { border-left: 4px solid #ef4444; }
</style>

<div class="pharma-shell">

    <!-- ── TOPBAR ─────────────────────────────── -->
    <div class="pharma-topbar">
        <div class="logo-area">
            <a href="<?= BASE_URL ?>pharmacie" style="color:inherit;text-decoration:none;">
                <svg width="28" height="28" viewBox="0 0 28 28" fill="none">
                    <rect width="28" height="28" rx="8" fill="#1e40af"/>
                    <path d="M9 8h10M14 8v12M9 14h10" stroke="#fff" stroke-width="2.2" stroke-linecap="round"/>
                </svg>
            </a>
            <span class="app-name">Pharmacie DME</span>
        </div>
        <div class="page-title">Préparation Ordonnance</div>
        <div>
            <span class="ord-badge">
                <svg width="12" height="12" viewBox="0 0 12 12" fill="currentColor" style="margin-right:4px;vertical-align:-1px;">
                    <path d="M6 1a5 5 0 100 10A5 5 0 006 1zm0 1.5A3.5 3.5 0 119.5 6 3.5 3.5 0 016 2.5zm-.5 1.5v3l2.5 1.5.4-.7-2.1-1.2V4H5.5z"/>
                </svg>
                VÉRIFICATION SÉCURITÉ
            </span>
        </div>
    </div>

    <!-- ── CORPS ──────────────────────────────── -->
    <div class="pharma-body">

        <!-- Patient Header -->
        <?php
        $ordStatut = $ordonnance['statut'] ?? 'EN_ATTENTE';
        $isSigned  = ($ordStatut === 'SIGNEE' || $ordStatut === 'TERMINEE');
        ?>
        <div class="patient-card">
            <div>
                <div class="pname"><?= strtoupper(htmlspecialchars($ordonnance['patient_nom'])) ?> <?= htmlspecialchars($ordonnance['patient_prenom']) ?></div>
                <div class="pmeta">
                    Dossier : <?= htmlspecialchars($ordonnance['dossier_numero']) ?>
                    <?php if (!empty($ordonnance['date_naissance'])): ?>
                    &nbsp;·&nbsp; <?= date_diff(date_create($ordonnance['date_naissance']), date_create('today'))->y ?> ans
                    <?php endif; ?>
                    &nbsp;·&nbsp; Émis le <?= date('d/m/Y', strtotime($ordonnance['date_creation'])) ?>
                    &nbsp;·&nbsp; Dr <?= htmlspecialchars($ordonnance['medecin_prenom'] . ' ' . $ordonnance['medecin_nom']) ?>
                </div>
            </div>
            <div class="ord-num" style="text-align:right;">
                <div style="font-size:.72rem;opacity:.6;font-weight:600;">ORDONNANCE</div>
                <span>#<?= str_pad($ordonnance['id'], 4, '0', STR_PAD_LEFT) ?></span>
                <div style="margin-top:6px;">
                    <?php if ($isSigned): ?>
                    <span style="background:#d1fae5;color:#065f46;font-size:.65rem;font-weight:800;padding:3px 10px;border-radius:20px;letter-spacing:.4px;">
                        ✓ SIGNÉE
                    </span>
                    <?php else: ?>
                    <span style="background:#fef3c7;color:#92400e;font-size:.65rem;font-weight:800;padding:3px 10px;border-radius:20px;letter-spacing:.4px;">
                        ⚠ NON SIGNÉE
                    </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Grille -->
        <div class="pharma-grid">

            <!-- ── Médicaments ── -->
            <div class="meds-card">
                <?php if ($ordStatut !== 'TERMINEE' && !empty($lignes)): ?>
                <!-- Bandeau d'instruction checklist -->
                <div class="checklist-banner" style="margin-top:12px;">
                    <i class="bi bi-check2-square" style="font-size:1.1rem;"></i>
                    <span style="flex:1;">
                        <strong>Cochez les médicaments effectivement délivrés</strong> au patient.
                        Les lignes décochées seront marquées comme <strong>non délivrées</strong>.
                    </span>
                    <span class="checklist-counter" id="checklistCounter">— / —</span>
                </div>
                <?php endif; ?>

                <div class="meds-card-header">
                    <span style="text-align:center;" title="Cocher = délivré">
                        <?php if ($ordStatut !== 'TERMINEE'): ?>
                        <input type="checkbox" id="checkAllMeds" class="med-checkbox" checked
                               title="Tout cocher / décocher"
                               onchange="toggleAllMeds(this.checked)">
                        <?php else: ?>
                        ✓
                        <?php endif; ?>
                    </span>
                    <span>Médicament</span>
                    <span>Posologie</span>
                    <span>Durée</span>
                    <span style="text-align:right;color:#7c3aed;">Prix SAGE</span>
                    <span style="text-align:center;">Stock</span>
                    <span style="text-align:center;">Actions</span>
                </div>
                <?php if (empty($lignes)): ?>
                <div style="text-align:center;padding:40px;color:#94a3b8;">
                    <svg width="36" height="36" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="opacity:.3;margin-bottom:8px;display:block;margin-left:auto;margin-right:auto;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Aucun médicament enregistré
                </div>
                <?php else: foreach ($lignes as $l):
                    $nomMed     = htmlspecialchars($l['nom_medicament'] ?: ($l['designation_stock'] ?? '—'));
                    $hasStock   = !empty($l['medicament_id']);
                    $qte        = (int)($l['stock_actuel'] ?? 0);
                    $seuil      = (int)($l['seuil_alerte']  ?? 10);
                    $annulee    = !empty($l['ligne_annulee']);
                    $remplacante= !empty($l['remplace_ligne_id']);
                    $enRupture  = !$annulee && !empty($l['hors_stock']);
                    if (!$hasStock)          { $sc = 'stock-na'; $sl = '— N/A'; }
                    elseif ($qte <= 0)       { $sc = 'stock-out'; $sl = '0 dispo.'; }
                    elseif ($qte <= $seuil)  { $sc = 'stock-low'; $sl = $qte . ' dispo.'; }
                    else                     { $sc = 'stock-ok';  $sl = $qte . ' dispo.'; }
                    $rowCls = $annulee ? 'ligne-annulee' : ($remplacante ? 'ligne-remplacante' : '');
                    if ($enRupture) $rowCls .= ' en-rupture';
                    // Pour la checklist : pré-cocher si stock disponible et ligne valide
                    $estDelivrable = !$annulee && $ordStatut !== 'TERMINEE';
                    // État existant si déjà délivré (pour mode lecture après délivrance)
                    $dejaDelivre   = isset($l['delivre']) ? (int)$l['delivre'] : null;
                ?>
                <div class="med-row <?= $rowCls ?>" data-ligne-id="<?= (int)$l['id'] ?>">
                    <!-- ✅ Checkbox délivrance -->
                    <div style="text-align:center;">
                        <?php if ($annulee): ?>
                            <span style="color:#94a3b8;font-size:.7rem;">—</span>
                        <?php elseif ($ordStatut === 'TERMINEE'): ?>
                            <?php if ($dejaDelivre === 1): ?>
                                <i class="bi bi-check-circle-fill" style="color:#16a34a;font-size:1.1rem;" title="Délivré"></i>
                            <?php elseif ($dejaDelivre === 0): ?>
                                <i class="bi bi-x-circle-fill" style="color:#dc2626;font-size:1.1rem;" title="Non délivré"></i>
                            <?php else: ?>
                                <i class="bi bi-dash-circle" style="color:#94a3b8;" title="Non renseigné"></i>
                            <?php endif; ?>
                        <?php else: ?>
                            <input type="checkbox" class="med-checkbox med-line-cb"
                                   data-ligne-id="<?= (int)$l['id'] ?>"
                                   <?= $enRupture ? '' : 'checked' ?>
                                   <?= $enRupture ? 'disabled title="Médicament en rupture de stock"' : '' ?>
                                   onchange="updateChecklistCounter(); toggleRowVisual(this);">
                        <?php endif; ?>
                    </div>

                    <div>
                        <?php
                        // Calcul de la "version" de la ligne en suivant la chaîne remplace_ligne_id
                        // (ex: ligne originale = v1, première modif = v2, deuxième modif = v3, etc.)
                        $versionNumber = 1;
                        if ($remplacante) {
                            $tmpId = $l['remplace_ligne_id'];
                            // Construire un index rapide des lignes par ID pour suivre la chaîne
                            static $lignesById = null;
                            if ($lignesById === null) {
                                $lignesById = [];
                                foreach ($lignes as $li) { $lignesById[(int)$li['id']] = $li; }
                            }
                            $versionNumber = 2;
                            $seen = [];
                            while ($tmpId && isset($lignesById[(int)$tmpId])
                                   && !empty($lignesById[(int)$tmpId]['remplace_ligne_id'])
                                   && !isset($seen[(int)$tmpId])) {
                                $seen[(int)$tmpId] = true;
                                $versionNumber++;
                                $tmpId = $lignesById[(int)$tmpId]['remplace_ligne_id'];
                            }
                        }
                        ?>
                        <?php if ($remplacante): ?>
                        <span style="font-size:.65rem;font-weight:800;background:#dcfce7;color:#15803d;padding:2px 7px;border-radius:20px;margin-bottom:4px;display:inline-block;"
                              title="Cette ligne est la version <?= $versionNumber ?> (après <?= $versionNumber - 1 ?> modification<?= $versionNumber > 2 ? 's' : '' ?>)">
                            ✓ Modifié v<?= $versionNumber ?>
                        </span><br>
                        <?php endif; ?>
                        <?php if ($annulee): ?>
                        <span style="font-size:.65rem;font-weight:800;background:#fef3c7;color:#92400e;padding:2px 7px;border-radius:20px;margin-bottom:4px;display:inline-block;">
                            Remplacé
                        </span><br>
                        <?php endif; ?>
                        <div class="med-name"><?= $nomMed ?></div>
                        <?php
                        $qtePrescrire = (int)($l['quantite'] ?? 0);
                        if ($qtePrescrire > 0): ?>
                        <span style="display:inline-flex;align-items:center;gap:4px;
                                     background:#eff6ff;color:#1d4ed8;
                                     border:1px solid #bfdbfe;border-radius:20px;
                                     font-size:.65rem;font-weight:700;
                                     padding:2px 8px;margin-top:3px;">
                            <i class="bi bi-box-seam-fill" style="font-size:.6rem;"></i>
                            Qté&nbsp;:&nbsp;<?= $qtePrescrire ?>
                        </span>
                        <?php endif; ?>
                        <?php if (!empty($l['sage_ref'])): ?>
                        <span class="med-sub" style="background:#ede9fe;color:#6d28d9;border-radius:4px;padding:1px 6px;font-size:.63rem;font-weight:700;font-family:monospace;">
                            <i class="bi bi-link-45deg" style="font-style:normal;">⇌</i> <?= htmlspecialchars($l['sage_ref']) ?>
                        </span>
                        <?php endif; ?>
                        <?php if ($enRupture): ?>
                        <span class="badge-rupture-ligne">
                            <i class="bi bi-slash-circle-fill"></i> En rupture
                        </span>
                        <?php elseif (!$hasStock): ?>
                        <span class="med-sub" style="background:#f1f5f9;border-radius:4px;padding:1px 6px;font-size:.65rem;font-weight:700;">Hors stock</span>
                        <?php endif; ?>
                        <?php if (!empty($l['note_pharmacien'])): ?>
                        <div class="med-sub"><i class="bi bi-chat-text me-1"></i><?= htmlspecialchars($l['note_pharmacien']) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="med-poso"><?= htmlspecialchars($l['posologie'] ?? '—') ?></div>
                    <div class="med-dur"><?= htmlspecialchars($l['duree'] ?? '—') ?></div>
                    <!-- Prix SAGE -->
                    <div style="text-align:right;">
                        <?php if (!empty($l['prix_sage']) && $l['prix_sage'] > 0): ?>
                        <span style="font-size:.88rem;font-weight:800;color:#7c3aed;">
                            <?= number_format((float)$l['prix_sage'], 0, ',', ' ') ?>
                            <small style="font-size:.65rem;font-weight:600;opacity:.7;">F</small>
                        </span>
                        <?php if (!empty($l['sage_unite'])): ?>
                        <div style="font-size:.63rem;color:#94a3b8;">/<?= htmlspecialchars($l['sage_unite']) ?></div>
                        <?php endif; ?>
                        <?php else: ?>
                        <span style="color:#cbd5e1;font-size:.75rem;">—</span>
                        <?php endif; ?>
                    </div>
                    <div style="text-align:center;">
                        <span class="stock-badge <?= $sc ?>">
                            <?php if ($sc === 'stock-ok'): ?><span style="width:6px;height:6px;background:#15803d;border-radius:50%;display:inline-block;"></span><?php endif; ?>
                            <?php if ($sc === 'stock-low'): ?><span style="width:6px;height:6px;background:#854d0e;border-radius:50%;display:inline-block;"></span><?php endif; ?>
                            <?php if ($sc === 'stock-out'): ?><span style="width:6px;height:6px;background:#dc2626;border-radius:50%;display:inline-block;"></span><?php endif; ?>
                            <?= $sl ?>
                        </span>
                    </div>
                    <!-- Colonne Actions : Rupture + Modifier -->
                    <div style="display:flex;align-items:center;gap:6px;justify-content:center;flex-wrap:wrap;">
                        <?php if (!$annulee && $ordStatut !== 'TERMINEE'): ?>
                        <!-- Bouton Rupture / Lever rupture -->
                        <button type="button"
                                class="btn-rupture <?= $enRupture ? 'rupture-on' : 'rupture-off' ?>"
                                id="btnRupture<?= (int)$l['id'] ?>"
                                onclick="toggleRupture(<?= (int)$l['id'] ?>, <?= $enRupture ? 1 : 0 ?>)"
                                title="<?= $enRupture ? 'Lever la rupture de stock' : 'Déclarer en rupture de stock' ?>">
                            <?php if ($enRupture): ?>
                                <i class="bi bi-check-circle"></i> Lever la rupture
                            <?php else: ?>
                                <i class="bi bi-slash-circle"></i> Rupture
                            <?php endif; ?>
                        </button>
                        <!-- Bouton modifier -->
                        <button type="button" class="btn-edit-ligne"
                                title="<?= $remplacante ? 'Modifier à nouveau cette ligne (version ' . $versionNumber . ')' : 'Modifier cette ligne' ?>"
                                onclick="toggleEditForm(<?= (int)$l['id'] ?>, <?= htmlspecialchars(json_encode([
                                    'nom'      => $l['nom_medicament'],
                                    'posologie'=> $l['posologie'] ?? '',
                                    'duree'    => $l['duree'] ?? '',
                                ])) ?>)">
                            <i class="bi bi-pencil-square"></i>
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                <!-- Formulaire d'édition inline -->
                <?php if (!$annulee && $ordStatut !== 'TERMINEE'): ?>
                <div class="edit-inline-form" id="editForm<?= (int)$l['id'] ?>">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-4">
                            <label style="font-size:.72rem;font-weight:700;color:#0369a1;">MÉDICAMENT</label>
                            <input type="text" id="editNom<?= (int)$l['id'] ?>" placeholder="Nom médicament">
                        </div>
                        <div class="col-md-3">
                            <label style="font-size:.72rem;font-weight:700;color:#0369a1;">POSOLOGIE</label>
                            <input type="text" id="editPoso<?= (int)$l['id'] ?>" placeholder="1 cp matin/soir">
                        </div>
                        <div class="col-md-2">
                            <label style="font-size:.72rem;font-weight:700;color:#0369a1;">DURÉE</label>
                            <input type="text" id="editDuree<?= (int)$l['id'] ?>" placeholder="7 jours">
                        </div>
                        <div class="col-md-3">
                            <label style="font-size:.72rem;font-weight:700;color:#0369a1;">NOTE (optionnel)</label>
                            <input type="text" id="editNote<?= (int)$l['id'] ?>" placeholder="ex: rupture stock, substitut…">
                        </div>
                        <div class="col-12 d-flex gap-2 mt-2">
                            <button type="button" class="btn btn-sm btn-primary rounded-pill px-4"
                                    onclick="sauvegarderModifLigne(<?= (int)$l['id'] ?>, <?= (int)$ordonnance['id'] ?>)">
                                <i class="bi bi-check2 me-1"></i>Enregistrer la modification
                            </button>
                            <button type="button" class="btn btn-sm btn-light rounded-pill px-3"
                                    onclick="toggleEditForm(<?= (int)$l['id'] ?>)">Annuler</button>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                <?php endforeach; endif; ?>

                <?php if (!empty($lignes) && ($totalSage ?? 0) > 0): ?>
                <!-- ── Total SAGE ────────────────────── -->
                <div style="background:linear-gradient(135deg,#f5f3ff,#ede9fe);border-top:2px solid #ddd6fe;padding:14px 20px;display:flex;align-items:center;justify-content:space-between;">
                    <div style="display:flex;align-items:center;gap:10px;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#7c3aed" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
                        <div>
                            <div style="font-size:.72rem;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:#6d28d9;">Montant SAGE 100</div>
                            <div style="font-size:.7rem;color:#7c3aed;opacity:.75;"><?= count(array_filter($lignes, fn($l) => !empty($l['prix_sage']) && $l['prix_sage'] > 0)) ?> médicament(s) valorisés</div>
                        </div>
                    </div>
                    <div style="text-align:right;">
                        <div style="font-size:1.35rem;font-weight:900;color:#5b21b6;letter-spacing:-.5px;">
                            <?= number_format($totalSage, 0, ',', ' ') ?> <span style="font-size:.75rem;font-weight:700;opacity:.7;">FCFA</span>
                        </div>
                        <div style="font-size:.65rem;color:#7c3aed;opacity:.65;">Prix catalogue SAGE (hors remises)</div>
                    </div>
                </div>
                <?php endif; ?>

            </div>

            <!-- ── Panneau droit ── -->
            <div class="side-panel">

                <!-- Allergies -->
                <?php $hasAllergy = !empty($ordonnance['allergies']); ?>
                <div class="allergy-card <?= $hasAllergy ? '' : 'safe' ?>">
                    <div class="allergy-title">
                        <?= $hasAllergy ? '⚠ Alertes Allergies' : '✓ Allergies' ?>
                    </div>
                    <?php if ($hasAllergy): ?>
                    <div style="font-size:.85rem;font-weight:700;color:#1e293b;line-height:1.5;">
                        <?= nl2br(htmlspecialchars($ordonnance['allergies'])) ?>
                    </div>
                    <?php else: ?>
                    <div style="font-size:.83rem;color:#059669;font-weight:600;">Aucune allergie connue pour ce patient.</div>
                    <?php endif; ?>
                </div>

                <!-- Voir ordonnance -->
                <div style="background:#fff;border-radius:14px;box-shadow:0 2px 12px rgba(0,0,0,.06);padding:14px 18px;">
                    <div style="font-size:.72rem;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:#6366f1;margin-bottom:8px;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:-2px;margin-right:4px;"><path d="M9 12h6m-6 4h6M5 8h14M3 4h18v16H3z"/></svg>
                        Document original
                    </div>
                    <a href="<?= BASE_URL ?>consultation/imprimer-ordonnance/<?= (int)$ordonnance['id'] ?>"
                       target="_blank"
                       style="display:block;text-align:center;background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:10px;padding:10px;font-size:.82rem;font-weight:700;color:#1e293b;text-decoration:none;transition:background .15s;"
                       onmouseover="this.style.background='#eff6ff'" onmouseout="this.style.background='#f8fafc'">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#6366f1" stroke-width="2.5" style="vertical-align:-2px;margin-right:5px;"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        Voir l'ordonnance <?= $isSigned ? 'signée' : '' ?>
                    </a>
                </div>

                <!-- Validation -->
                <div class="validate-card">
                    <?php if (($totalSage ?? 0) > 0): ?>
                    <div style="background:rgba(124,58,237,.18);border:1px solid rgba(124,58,237,.35);border-radius:10px;padding:12px 14px;margin-bottom:14px;">
                        <div style="font-size:.65rem;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:#c4b5fd;margin-bottom:4px;">
                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="#c4b5fd" stroke-width="2.5" style="vertical-align:-1px;margin-right:3px;"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
                            Facturation SAGE 100
                        </div>
                        <div style="font-size:1.2rem;font-weight:900;color:#e9d5ff;letter-spacing:-.3px;">
                            <?= number_format($totalSage ?? 0, 0, ',', ' ') ?> <span style="font-size:.7rem;font-weight:600;opacity:.7;">FCFA</span>
                        </div>
                        <div style="font-size:.65rem;color:#c4b5fd;opacity:.75;margin-top:2px;">Prix catalogue — à saisir dans SAGE 100</div>
                    </div>
                    <?php endif; ?>
                    <?php if ($ordStatut === 'TERMINEE'): ?>
                        <h5 style="color:#6ee7b7;">✓ Ordonnance délivrée</h5>
                        <div style="background:rgba(16,185,129,.15);border-radius:8px;padding:10px 14px;margin-bottom:14px;font-size:.82rem;color:#6ee7b7;font-weight:600;border:1px solid rgba(16,185,129,.25);">
                            <i class="bi bi-check-circle-fill me-2"></i>
                            Cette ordonnance a déjà été traitée et les médicaments délivrés.
                            <?php if (!empty($ordonnance['date_traitement'])): ?>
                            <br><span style="opacity:.7;">le <?= date('d/m/Y à H:i', strtotime($ordonnance['date_traitement'])) ?></span>
                            <?php endif; ?>
                        </div>
                        <p style="font-size:.78rem;">Consultation en mode lecture seule — aucune modification possible.</p>
                        <a href="<?= BASE_URL ?>pharmacie" style="display:block;text-align:center;background:rgba(255,255,255,.08);color:#94a3b8;border-radius:10px;padding:12px;font-size:.85rem;font-weight:700;text-decoration:none;margin-top:8px;">
                            <i class="bi bi-arrow-left me-2"></i>Retour au dashboard
                        </a>
                    <?php else: ?>
                        <h5>Validation Finale</h5>
                        <?php if (!$isSigned): ?>
                        <div style="background:#fef3c7;border-radius:8px;padding:8px 12px;margin-bottom:12px;font-size:.78rem;color:#92400e;font-weight:600;">
                            ⚠ Ordonnance non signée par le médecin. La délivrance reste possible mais nécessite votre confirmation.
                        </div>
                        <?php endif; ?>
                        <p>En cliquant sur le bouton, vous confirmez que vous avez préparé les médicaments et vérifié les interactions.</p>
                        <button class="btn-deliver" id="btnDeliver" onclick="confirmDeliver(<?= (int)$ordonnance['id'] ?>, <?= $isSigned ? 'true' : 'false' ?>)">
                            <span class="icon-check">
                                <svg width="11" height="11" viewBox="0 0 12 12" fill="white">
                                    <path d="M2 6l3 3 5-5" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                                </svg>
                            </span>
                            Délivrer les médicaments
                        </button>
                        <a href="<?= BASE_URL ?>pharmacie" class="btn-cancel">Annuler et revenir</a>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- Toast notification -->
<div class="pharma-toast" id="pharmaToast"></div>

<script>
// ── Rupture de stock ──────────────────────────────────────────────────────────
function toggleRupture(ligneId, currentState) {
    const base = '<?= BASE_URL ?>';
    const btn  = document.getElementById('btnRupture' + ligneId);
    const row  = document.querySelector('.med-row[data-ligne-id="' + ligneId + '"]');
    const cb   = row?.querySelector('.med-line-cb');

    btn.disabled = true;
    btn.style.opacity = '.5';

    const fd = new FormData();
    fd.append('ligne_id',      ligneId);
    fd.append('hors_stock',    currentState ? 0 : 1);

    fetch(base + 'pharmacie/declarer-rupture', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if (!res.success) { alert(res.message || 'Erreur'); btn.disabled = false; btn.style.opacity = ''; return; }

            const enRupture = !!res.hors_stock;

            // ── Mise à jour visuelle du bouton ──
            btn.className = 'btn-rupture ' + (enRupture ? 'rupture-on' : 'rupture-off');
            btn.setAttribute('onclick', 'toggleRupture(' + ligneId + ', ' + (enRupture ? 1 : 0) + ')');
            btn.title = enRupture ? 'Lever la rupture de stock' : 'Déclarer en rupture de stock';
            btn.innerHTML = enRupture
                ? '<i class="bi bi-check-circle"></i> Lever la rupture'
                : '<i class="bi bi-slash-circle"></i> Rupture';

            // ── Mise à jour de la ligne ──
            if (row) {
                if (enRupture) {
                    row.classList.add('en-rupture');
                } else {
                    row.classList.remove('en-rupture');
                }
                // Chercher ou créer le badge rupture
                const badgeExist = row.querySelector('.badge-rupture-ligne');
                const medNameEl  = row.querySelector('.med-name');
                if (enRupture) {
                    if (!badgeExist && medNameEl) {
                        const badge = document.createElement('span');
                        badge.className = 'badge-rupture-ligne';
                        badge.innerHTML = '<i class="bi bi-slash-circle-fill"></i> En rupture';
                        medNameEl.insertAdjacentElement('afterend', badge);
                    }
                } else {
                    badgeExist?.remove();
                }
            }

            // ── Checkbox ──
            if (cb) {
                cb.disabled = enRupture;
                cb.checked  = !enRupture;
                if (!enRupture) cb.dispatchEvent(new Event('change'));
                else updateChecklistCounter();
            }

            // ── Toast ──
            const msg = enRupture
                ? 'Médicament déclaré en rupture — non délivré.'
                : 'Rupture levée — médicament à nouveau délivrable.';
            showToast(msg, enRupture ? 'warn' : 'ok');

            btn.disabled = false; btn.style.opacity = '';
        })
        .catch(() => { alert('Erreur réseau.'); btn.disabled = false; btn.style.opacity = ''; });
}

function showToast(msg, type) {
    const d = document.createElement('div');
    const colors = { ok: '#15803d', warn: '#c2410c', err: '#dc2626' };
    d.style.cssText = `position:fixed;bottom:24px;right:24px;background:${colors[type]||'#1e293b'};color:#fff;
        padding:11px 20px;border-radius:12px;font-size:.82rem;font-weight:700;z-index:9999;
        box-shadow:0 4px 20px rgba(0,0,0,.25);animation:toastIn .2s ease;`;
    d.textContent = msg;
    document.body.appendChild(d);
    setTimeout(() => d.remove(), 3500);
}

// ── Édition inline de ligne médicament ────────────────────────────────────────
function toggleEditForm(id, data) {
    const form = document.getElementById('editForm' + id);
    if (!form) return;
    const isOpen = form.classList.contains('open');
    // Fermer tous les autres formulaires ouverts
    document.querySelectorAll('.edit-inline-form.open').forEach(f => f.classList.remove('open'));
    if (!isOpen && data) {
        document.getElementById('editNom'  + id).value = data.nom       || '';
        document.getElementById('editPoso' + id).value = data.posologie || '';
        document.getElementById('editDuree'+ id).value = data.duree     || '';
        document.getElementById('editNote' + id).value = '';
        form.classList.add('open');
    }
}

function sauvegarderModifLigne(ligneId, ordonnanceId) {
    const nom      = document.getElementById('editNom'  + ligneId).value.trim();
    const posologie= document.getElementById('editPoso' + ligneId).value.trim();
    const duree    = document.getElementById('editDuree'+ ligneId).value.trim();
    const note     = document.getElementById('editNote' + ligneId).value.trim();

    if (!nom) { alert('Le nom du médicament est requis.'); return; }

    const fd = new FormData();
    fd.append('nom_medicament', nom);
    fd.append('posologie',      posologie);
    fd.append('duree',          duree);
    fd.append('note_pharmacien',note);
    fd.append('ordonnance_id',  ordonnanceId);

    fetch(BASE_URL + 'pharmacie/modifier-ligne/' + ligneId, {
        method: 'POST', body: fd,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(d => {
        showToast(d.message, d.success ? 'success' : 'error');
        if (d.success) setTimeout(() => location.reload(), 900);
    })
    .catch(() => showToast('Erreur réseau.', 'error'));
}

function showToast(msg, type = 'success') {
    const t = document.getElementById('pharmaToast');
    t.className = 'pharma-toast show ' + type;
    t.innerHTML = (type === 'success'
        ? '<svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="#10b981" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>'
        : '<svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="#ef4444" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>'
    ) + msg;
    setTimeout(() => t.classList.remove('show'), 3500);
}

// ── Checklist : tout cocher/décocher ─────────────────────────────────────────
function toggleAllMeds(checked) {
    document.querySelectorAll('.med-line-cb').forEach(cb => {
        if (!cb.disabled) {
            cb.checked = checked;
            toggleRowVisual(cb);
        }
    });
    updateChecklistCounter();
}

// ── Mise à jour visuelle de la ligne (style "non délivré" si décochée) ──────
function toggleRowVisual(cb) {
    const row = cb.closest('.med-row');
    if (!row) return;
    if (cb.checked) row.classList.remove('med-uncheck');
    else            row.classList.add('med-uncheck');
}

// ── Compteur "X / Y délivrés" ────────────────────────────────────────────────
function updateChecklistCounter() {
    const all = document.querySelectorAll('.med-line-cb');
    const checked = document.querySelectorAll('.med-line-cb:checked');
    const counter = document.getElementById('checklistCounter');
    if (!counter) return;
    counter.textContent = checked.length + ' / ' + all.length + ' délivrés';
    counter.classList.toggle('has-uncheck', checked.length < all.length);

    // Mettre à jour la "tout cocher" intelligemment
    const cbAll = document.getElementById('checkAllMeds');
    if (cbAll) {
        cbAll.checked = (checked.length === all.length && all.length > 0);
        cbAll.indeterminate = (checked.length > 0 && checked.length < all.length);
    }
}
// Initialisation au chargement
document.addEventListener('DOMContentLoaded', () => {
    updateChecklistCounter();
});

// ── Bouton "Délivrer" (avec tracking des lignes cochées/décochées) ──────────
function confirmDeliver(id, isSigned) {
    // Récupérer les IDs des lignes cochées et non cochées
    const lignesDelivrees = [];
    const lignesNonDelivrees = [];
    document.querySelectorAll('.med-line-cb').forEach(cb => {
        const lId = cb.dataset.ligneId;
        if (cb.checked) lignesDelivrees.push(lId);
        else            lignesNonDelivrees.push(lId);
    });

    if (lignesDelivrees.length === 0) {
        if (!confirm('⚠ Aucun médicament n\'est coché.\n\nVoulez-vous vraiment clôturer cette ordonnance avec TOUS les médicaments marqués comme non délivrés ?')) return;
    }

    let msg;
    if (lignesNonDelivrees.length > 0) {
        msg = `${lignesDelivrees.length} médicament(s) seront délivrés et ${lignesNonDelivrees.length} marqué(s) non délivré(s).\n\nConfirmer ?`;
    } else if (isSigned) {
        msg = 'Valider la sortie de stock et clôturer cette ordonnance ?';
    } else {
        msg = 'Cette ordonnance n\'est pas encore signée par le médecin. Confirmer quand même la délivrance ?';
    }
    if (!confirm(msg)) return;

    const btn = document.getElementById('btnDeliver');
    btn.disabled = true;
    btn.innerHTML = '<span style="width:18px;height:18px;border:2.5px solid #1e293b;border-top-color:transparent;border-radius:50%;display:inline-block;animation:spin .7s linear infinite;"></span> Traitement en cours…';

    const fd = new FormData();
    fd.append('id', id);
    lignesDelivrees.forEach(lid => fd.append('lignes_delivrees[]', lid));
    lignesNonDelivrees.forEach(lid => fd.append('lignes_non_delivrees[]', lid));

    fetch('<?= BASE_URL ?>pharmacie/delivrer', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showToast('Ordonnance traitée avec succès.');
                setTimeout(() => { window.location.href = '<?= BASE_URL ?>pharmacie'; }, 1200);
            } else {
                showToast(data.message || 'Une erreur est survenue.', 'error');
                btn.disabled = false;
                btn.innerHTML = '<span class="icon-check"><svg width="11" height="11" viewBox="0 0 12 12" fill="white"><path d="M2 6l3 3 5-5" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"/></svg></span> Délivrer les médicaments';
            }
        })
        .catch(() => {
            showToast('Erreur réseau.', 'error');
            btn.disabled = false;
        });
}
</script>
<style>
@keyframes spin { to { transform: rotate(360deg); } }
</style>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
