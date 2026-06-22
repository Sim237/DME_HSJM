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
$patient = $patient ?? [];
$age     = $age ?? '—';
$obs     = $obs ?? null;
$d       = $obs['_data'] ?? [];

$v   = fn($k, $def='')  => htmlspecialchars($d[$k] ?? $obs[$k] ?? $def);
$vr  = fn($k, $def='')  => htmlspecialchars($d[$k] ?? $def);         // data JSON only
$sel = fn($k, $opt)     => (($d[$k] ?? '') === $opt) ? 'selected' : '';
$chk = fn($k)           => !empty($d[$k]) ? 'checked' : '';
$rad = fn($k, $val)     => (($d[$k] ?? '') === $val)  ? 'checked' : '';
?>
<style>
    .sidebar, nav.sidebar { display:none !important; }
    main, .col-md-10, .ms-sm-auto { margin-left:0 !important; width:100% !important; flex:0 0 100% !important; max-width:100% !important; padding:0 !important; }
    body { background:#f0f4f8; font-family:'Segoe UI',system-ui,sans-serif; }

    .neo-topbar { background:linear-gradient(135deg,#1d4ed8,#3b82f6); padding:13px 26px; display:flex; align-items:center; justify-content:space-between; position:sticky; top:0; z-index:100; box-shadow:0 4px 18px rgba(59,130,246,.3); }
    .neo-topbar .t { font-size:1rem; font-weight:800; color:#fff; display:flex; align-items:center; gap:8px; }
    .neo-tb-btn { display:inline-flex; align-items:center; gap:6px; padding:7px 15px; border-radius:9px; font-weight:700; font-size:.78rem; text-decoration:none; border:none; cursor:pointer; }
    .neo-tb-light { background:rgba(255,255,255,.18); color:#fff; border:1px solid rgba(255,255,255,.35); }
    .neo-tb-save  { background:#fff; color:#1d4ed8; }
    .neo-tb-save:hover { background:#eff6ff; }

    .neo-form { max-width:1060px; margin:0 auto; padding:22px 24px 70px; }

    .pat-card { background:linear-gradient(135deg,#3b82f6,#60a5fa); color:#fff; border-radius:14px; padding:15px 20px; margin-bottom:20px; display:flex; align-items:center; gap:13px; box-shadow:0 4px 18px rgba(59,130,246,.22); }
    .pat-av { width:48px; height:48px; border-radius:13px; background:rgba(255,255,255,.22); display:flex; align-items:center; justify-content:center; font-size:1.4rem; }
    .pat-name { font-size:1.1rem; font-weight:800; }
    .pat-meta { font-size:.78rem; opacity:.85; }

    .neo-section { background:#fff; border-radius:14px; box-shadow:0 2px 10px rgba(59,130,246,.06); border:1px solid #dbeafe; margin-bottom:16px; overflow:hidden; }
    .neo-sec-head { padding:11px 18px; font-weight:800; font-size:.78rem; text-transform:uppercase; letter-spacing:.5px; color:#1d4ed8; background:#eff6ff; border-bottom:2px solid #bfdbfe; display:flex; align-items:center; gap:8px; }
    .neo-sec-num  { background:#1d4ed8; color:#fff; border-radius:50%; width:20px; height:20px; display:inline-flex; align-items:center; justify-content:center; font-size:.7rem; font-weight:900; flex-shrink:0; }
    .neo-sec-body { padding:16px 18px; }

    .neo-label { font-size:.7rem; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:.4px; margin-bottom:4px; display:block; }
    .neo-input, .neo-select, .neo-textarea { width:100%; padding:8px 11px; border:1.5px solid #e2e8f0; border-radius:9px; font-size:.86rem; color:#1e293b; outline:none; background:#fff; transition:border .15s; }
    .neo-input:focus, .neo-select:focus, .neo-textarea:focus { border-color:#3b82f6; box-shadow:0 0 0 3px rgba(59,130,246,.1); }
    .neo-textarea { resize:vertical; min-height:60px; }
    .neo-grid { display:grid; gap:12px; }
    .g2 { grid-template-columns:repeat(2,1fr); } .g3 { grid-template-columns:repeat(3,1fr); } .g4 { grid-template-columns:repeat(4,1fr); }
    @media(max-width:720px){ .g2,.g3,.g4 { grid-template-columns:1fr 1fr; } }

    .neo-checks { display:flex; flex-wrap:wrap; gap:8px; margin-top:4px; }
    .neo-check { display:flex; align-items:center; gap:6px; background:#f8fafc; border:1.5px solid #e2e8f0; border-radius:9px; padding:6px 12px; cursor:pointer; font-size:.82rem; font-weight:600; color:#334155; transition:all .15s; }
    .neo-check input { width:15px; height:15px; accent-color:#3b82f6; }
    .neo-check:has(input:checked) { background:#eff6ff; border-color:#3b82f6; color:#1d4ed8; }

    .neo-suffix { position:relative; }
    .neo-suffix .u { position:absolute; right:10px; top:50%; transform:translateY(-50%); font-size:.7rem; color:#94a3b8; font-weight:700; pointer-events:none; }
    .neo-suffix .neo-input { padding-right:38px; }

    .rad-group { display:flex; gap:10px; flex-wrap:wrap; }
    .rad-btn { display:flex; align-items:center; gap:5px; background:#f8fafc; border:1.5px solid #e2e8f0; border-radius:9px; padding:6px 14px; cursor:pointer; font-size:.82rem; font-weight:600; color:#475569; }
    .rad-btn input { accent-color:#3b82f6; }
    .rad-btn:has(input:checked) { background:#eff6ff; border-color:#3b82f6; color:#1d4ed8; }

    .sys-table { width:100%; border-collapse:collapse; font-size:.82rem; }
    .sys-table th { background:#eff6ff; color:#1d4ed8; font-weight:700; padding:7px 10px; text-align:left; border:1px solid #bfdbfe; font-size:.72rem; text-transform:uppercase; }
    .sys-table td { padding:6px 10px; border:1px solid #e2e8f0; vertical-align:top; }
    .sys-table td:first-child { font-weight:700; color:#1e293b; white-space:nowrap; width:160px; background:#f8fafc; }

    .silverman-grid { display:grid; grid-template-columns:repeat(5,1fr); gap:8px; }
    .silverman-item label { font-size:.7rem; font-weight:700; color:#475569; display:block; margin-bottom:3px; }
    .silverman-item input { width:100%; padding:7px; border:1.5px solid #e2e8f0; border-radius:8px; text-align:center; font-weight:700; font-size:.9rem; }

    .alert-success { background:#ecfdf5; border:1px solid #6ee7b7; border-radius:10px; padding:10px 16px; color:#065f46; font-size:.85rem; margin-bottom:16px; }

    .section-title-big { font-size:.9rem; font-weight:800; color:#1e293b; margin:14px 0 8px; display:flex; align-items:center; gap:7px; }
    .section-title-big::after { content:''; flex:1; height:1px; background:#e2e8f0; }
</style>

<main>
<?php if (!empty($_GET['error'])): ?>
<div style="background:#fef2f2;border:1px solid #fca5a5;border-radius:10px;padding:10px 16px;color:#991b1b;margin:12px 24px;font-size:.85rem;">
    <i class="bi bi-exclamation-circle"></i> Erreur : <?= htmlspecialchars($_GET['error']) ?>
</div>
<?php endif; ?>

<form method="POST" action="<?= BASE_URL ?>neonatologie/observation-sauvegarder" id="obsForm">
<input type="hidden" name="patient_id" value="<?= (int)($patient['id'] ?? 0) ?>">
<?php if (!empty($_SESSION['csrf_token'])): ?>
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
<?php endif; ?>

<!-- TOPBAR -->
<div class="neo-topbar">
    <div class="t"><i class="bi bi-journal-medical"></i> Observation médicale du nouveau-né</div>
    <div class="d-flex gap-2 align-items-center">
        <span style="color:rgba(255,255,255,.7);font-size:.75rem;display:flex;align-items:center;gap:5px;">
            <i class="bi bi-person-fill"></i>
            <?= htmlspecialchars(strtoupper($patient['nom'] ?? '') . ' ' . ($patient['prenom'] ?? '')) ?>
        </span>
        <a href="<?= BASE_URL ?>neonatologie" class="neo-tb-btn neo-tb-light"><i class="bi bi-x-lg"></i> Annuler</a>
        <button type="submit" class="neo-tb-btn neo-tb-save"><i class="bi bi-floppy-fill"></i> Enregistrer</button>
    </div>
</div>

<div class="neo-form">

<!-- PATIENT CARD -->
<div class="pat-card">
    <div class="pat-av"><i class="bi bi-<?= strtoupper($patient['sexe'] ?? '') === 'F' ? 'gender-female' : 'gender-male' ?>"></i></div>
    <div style="flex:1">
        <div class="pat-name"><?= htmlspecialchars(strtoupper($patient['nom'] ?? '') . ' ' . ($patient['prenom'] ?? '')) ?></div>
        <div class="pat-meta">
            N° <?= htmlspecialchars($patient['dossier_numero'] ?? '—') ?>
            &bull; Âge : <?= htmlspecialchars($age) ?>
            &bull; <?= strtoupper($patient['sexe'] ?? '') === 'F' ? 'Fille' : 'Garçon' ?>
            &bull; Né(e) le <?= !empty($patient['date_naissance']) ? date('d/m/Y', strtotime($patient['date_naissance'])) : '—' ?>
        </div>
    </div>
    <div style="font-size:.75rem;color:rgba(255,255,255,.7)">
        <?= date('d/m/Y H:i') ?>
    </div>
</div>

<!-- ════════════════════════════════════════════════════════════
     SECTION 1 — IDENTITÉ DU NOUVEAU-NÉ
     ════════════════════════════════════════════════════════════ -->
<div class="neo-section">
    <div class="neo-sec-head"><span class="neo-sec-num">1</span> Identité du nouveau-né</div>
    <div class="neo-sec-body">
        <div class="neo-grid g3">
            <div><label class="neo-label">Nom complet</label><input type="text" name="identite_nom" class="neo-input" value="<?= htmlspecialchars(strtoupper($patient['nom'] ?? '') . ' ' . ($patient['prenom'] ?? '')) ?>" readonly style="background:#f8fafc;"></div>
            <div><label class="neo-label">Date de naissance</label><input type="date" name="identite_ddn" class="neo-input" value="<?= htmlspecialchars($patient['date_naissance'] ?? '') ?>"></div>
            <div><label class="neo-label">Heure de naissance</label><input type="time" name="identite_heure_naissance" class="neo-input" value="<?= $vr('identite_heure_naissance') ?>"></div>
        </div>
        <div class="neo-grid g4 mt-3">
            <div><label class="neo-label">Sexe</label>
                <select name="identite_sexe" class="neo-select">
                    <option value="">—</option>
                    <option value="M" <?= $sel('identite_sexe','M') ?>>Masculin</option>
                    <option value="F" <?= $sel('identite_sexe','F') ?>>Féminin</option>
                    <option value="I" <?= $sel('identite_sexe','I') ?>>Indéterminé</option>
                </select>
            </div>
            <div><label class="neo-label">N° dossier</label><input type="text" name="identite_dossier" class="neo-input" value="<?= htmlspecialchars($patient['dossier_numero'] ?? '') ?>" readonly style="background:#f8fafc;"></div>
            <div><label class="neo-label">Nom de la mère</label><input type="text" name="identite_mere" class="neo-input" value="<?= $vr('identite_mere') ?>" placeholder="Nom de la mère"></div>
            <div><label class="neo-label">Provenance</label>
                <select name="identite_provenance" class="neo-select">
                    <option value="">—</option>
                    <option value="MATERNITE" <?= $sel('identite_provenance','MATERNITE') ?>>Maternité interne</option>
                    <option value="EXTERNE" <?= $sel('identite_provenance','EXTERNE') ?>>Référé externe</option>
                    <option value="DOMICILE" <?= $sel('identite_provenance','DOMICILE') ?>>Accouchement à domicile</option>
                </select>
            </div>
        </div>
    </div>
</div>

<!-- ════════════════════════════════════════════════════════════
     SECTION 2 — MOTIF DE CONSULTATION / RÉFÉRENCE
     ════════════════════════════════════════════════════════════ -->
<div class="neo-section">
    <div class="neo-sec-head"><span class="neo-sec-num">2</span> Motif de consultation / référence <small style="font-weight:400;text-transform:none;opacity:.7">(3 max)</small></div>
    <div class="neo-sec-body">
        <div class="neo-grid g3">
            <div><label class="neo-label">Motif 1 *</label><input type="text" name="motif_1" class="neo-input" value="<?= $vr('motif_1') ?>" required placeholder="Motif principal"></div>
            <div><label class="neo-label">Motif 2</label><input type="text" name="motif_2" class="neo-input" value="<?= $vr('motif_2') ?>" placeholder="Motif secondaire"></div>
            <div><label class="neo-label">Motif 3</label><input type="text" name="motif_3" class="neo-input" value="<?= $vr('motif_3') ?>" placeholder="Autre motif"></div>
        </div>
    </div>
</div>

<!-- ════════════════════════════════════════════════════════════
     SECTION 3 — HISTOIRE DE LA MALADIE ACTUELLE (HMA)
     ════════════════════════════════════════════════════════════ -->
<div class="neo-section">
    <div class="neo-sec-head"><span class="neo-sec-num">3</span> Histoire de la maladie actuelle (HMA)</div>
    <div class="neo-sec-body">
        <div><label class="neo-label">Résumé anamnestique</label>
            <textarea name="hma" class="neo-textarea" style="min-height:90px;" placeholder="Décrivez le début, l'évolution, les signes associés, les traitements reçus..."><?= $vr('hma') ?></textarea>
        </div>
    </div>
</div>

<!-- ════════════════════════════════════════════════════════════
     SECTION 4 — ANTÉCÉDENTS FAMILIAUX
     ════════════════════════════════════════════════════════════ -->
<div class="neo-section">
    <div class="neo-sec-head"><span class="neo-sec-num">4</span> Antécédents familiaux</div>
    <div class="neo-sec-body">
        <div class="section-title-big"><i class="bi bi-person-fill" style="color:#1d4ed8"></i> Mère</div>
        <div class="neo-grid g4">
            <div><label class="neo-label">Âge</label><div class="neo-suffix"><input type="number" name="mere_age" class="neo-input" value="<?= $vr('mere_age') ?>" placeholder="ans"><span class="u">ans</span></div></div>
            <div><label class="neo-label">Gestité</label><input type="number" name="mere_gestite" class="neo-input" value="<?= $vr('mere_gestite') ?>" placeholder="G?"></div>
            <div><label class="neo-label">Parité</label><input type="number" name="mere_parite" class="neo-input" value="<?= $vr('mere_parite') ?>" placeholder="P?"></div>
            <div><label class="neo-label">Groupe sanguin</label><input type="text" name="mere_gs" class="neo-input" value="<?= $vr('mere_gs') ?>" placeholder="A+, O−..."></div>
        </div>
        <div class="neo-grid g2 mt-3">
            <div><label class="neo-label">Antécédents médicaux mère</label><textarea name="mere_atcd_med" class="neo-textarea" placeholder="Diabète, HTA, VIH, tuberculose..."><?= $vr('mere_atcd_med') ?></textarea></div>
            <div><label class="neo-label">Antécédents obstétricaux</label><textarea name="mere_atcd_obst" class="neo-textarea" placeholder="Fausses couches, mort-nés, prématurités..."><?= $vr('mere_atcd_obst') ?></textarea></div>
        </div>

        <div class="section-title-big mt-3"><i class="bi bi-person" style="color:#1d4ed8"></i> Père</div>
        <div class="neo-grid g3">
            <div><label class="neo-label">Âge</label><div class="neo-suffix"><input type="number" name="pere_age" class="neo-input" value="<?= $vr('pere_age') ?>" placeholder="ans"><span class="u">ans</span></div></div>
            <div><label class="neo-label">Groupe sanguin</label><input type="text" name="pere_gs" class="neo-input" value="<?= $vr('pere_gs') ?>" placeholder="A+, O−..."></div>
            <div><label class="neo-label">Antécédents pertinents</label><input type="text" name="pere_atcd" class="neo-input" value="<?= $vr('pere_atcd') ?>" placeholder="Maladies héréditaires..."></div>
        </div>
        <div class="neo-grid g2 mt-3">
            <div><label class="neo-label">Consanguinité</label>
                <div class="rad-group">
                    <?php foreach(['Non'=>'non','Oui 1er degré'=>'1deg','Oui 2e degré'=>'2deg'] as $lbl=>$val): ?>
                    <label class="rad-btn"><input type="radio" name="consanguinite" value="<?= $val ?>" <?= $rad('consanguinite',$val) ?>><?= $lbl ?></label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div><label class="neo-label">Fratrie — problèmes similaires</label><input type="text" name="fratrie_atcd" class="neo-input" value="<?= $vr('fratrie_atcd') ?>" placeholder="ex. ictère néonatal, décès en bas âge..."></div>
        </div>
    </div>
</div>

<!-- ════════════════════════════════════════════════════════════
     SECTION 5 — DÉROULEMENT DE LA GROSSESSE
     ════════════════════════════════════════════════════════════ -->
<div class="neo-section">
    <div class="neo-sec-head"><span class="neo-sec-num">5</span> Déroulement de la grossesse</div>
    <div class="neo-sec-body">
        <div class="neo-grid g4">
            <div><label class="neo-label">DDR</label><input type="date" name="grossesse_ddr" class="neo-input" value="<?= $vr('grossesse_ddr') ?>"></div>
            <div><label class="neo-label">DPA</label><input type="date" name="grossesse_dpa" class="neo-input" value="<?= $vr('grossesse_dpa') ?>"></div>
            <div><label class="neo-label">Terme (SA)</label><div class="neo-suffix"><input type="number" step="0.1" name="grossesse_terme" class="neo-input" value="<?= $vr('grossesse_terme') ?>" placeholder="38.5"><span class="u">SA</span></div></div>
            <div><label class="neo-label">Nb. CPN</label><input type="number" name="grossesse_nb_cpn" class="neo-input" value="<?= $vr('grossesse_nb_cpn') ?>" placeholder="0"></div>
        </div>

        <div class="section-title-big mt-3"><i class="bi bi-shield-plus" style="color:#1d4ed8;font-size:.85rem"></i> Sérologies maternelles</div>
        <div class="neo-checks">
            <?php foreach(['VIH'=>'serol_vih','Syphilis'=>'serol_syphilis','Hépatite B'=>'serol_vhb','Rubéole'=>'serol_rubeole','Toxoplasmose'=>'serol_toxo'] as $lbl=>$nm): ?>
            <label class="neo-check"><input type="checkbox" name="<?= $nm ?>" value="1" <?= $chk($nm) ?>><?= $lbl ?></label>
            <?php endforeach; ?>
        </div>
        <div class="neo-grid g3 mt-2">
            <div><label class="neo-label">Statut VIH mère</label>
                <select name="mere_vih_statut" class="neo-select">
                    <option value="">Non précisé</option>
                    <option value="NEGATIF" <?= $sel('mere_vih_statut','NEGATIF') ?>>Négatif</option>
                    <option value="POSITIF" <?= $sel('mere_vih_statut','POSITIF') ?>>Positif</option>
                    <option value="INCONNU" <?= $sel('mere_vih_statut','INCONNU') ?>>Inconnu</option>
                </select>
            </div>
            <div><label class="neo-label">Statut syphilis mère</label>
                <select name="mere_syphilis_statut" class="neo-select">
                    <option value="">Non précisé</option>
                    <option value="NEGATIF" <?= $sel('mere_syphilis_statut','NEGATIF') ?>>Négatif</option>
                    <option value="POSITIF" <?= $sel('mere_syphilis_statut','POSITIF') ?>>Positif</option>
                    <option value="TRAITE" <?= $sel('mere_syphilis_statut','TRAITE') ?>>Traité</option>
                </select>
            </div>
            <div><label class="neo-label">Statut hépatite B mère</label>
                <select name="mere_vhb_statut" class="neo-select">
                    <option value="">Non précisé</option>
                    <option value="NEGATIF" <?= $sel('mere_vhb_statut','NEGATIF') ?>>Négatif</option>
                    <option value="PORTEUR" <?= $sel('mere_vhb_statut','PORTEUR') ?>>Porteur</option>
                    <option value="VACCINE" <?= $sel('mere_vhb_statut','VACCINE') ?>>Vaccinée</option>
                </select>
            </div>
        </div>

        <div class="section-title-big mt-3"><i class="bi bi-capsule-pill" style="color:#1d4ed8;font-size:.85rem"></i> Prophylaxies & suppléments</div>
        <div class="neo-checks">
            <?php foreach(['Fer + Folates'=>'prop_fer_folates','Acide folique'=>'prop_ac_folique','Mebendazole (2e T)'=>'prop_mebendazole','TPI (SP)'=>'prop_tpi','MILDA reçue'=>'prop_milda'] as $lbl=>$nm): ?>
            <label class="neo-check"><input type="checkbox" name="<?= $nm ?>" value="1" <?= $chk($nm) ?>><?= $lbl ?></label>
            <?php endforeach; ?>
        </div>

        <div class="section-title-big mt-3"><i class="bi bi-camera" style="color:#1d4ed8;font-size:.85rem"></i> Échographies réalisées</div>
        <div class="neo-grid g3">
            <div><label class="neo-label">Nb. échographies</label><input type="number" name="echo_nb" class="neo-input" value="<?= $vr('echo_nb') ?>" placeholder="0"></div>
            <div><label class="neo-label">Résultat(s) notable(s)</label><input type="text" name="echo_resultats" class="neo-input" value="<?= $vr('echo_resultats') ?>" placeholder="Normal, malformation détectée..."></div>
            <div><label class="neo-label">Pathologies grossesse</label><input type="text" name="grossesse_pathologies" class="neo-input" value="<?= $vr('grossesse_pathologies') ?>" placeholder="HTA, diabète gestation., paludisme..."></div>
        </div>
    </div>
</div>

<!-- ════════════════════════════════════════════════════════════
     SECTION 6 — DÉROULEMENT DE L'ACCOUCHEMENT
     ════════════════════════════════════════════════════════════ -->
<div class="neo-section">
    <div class="neo-sec-head"><span class="neo-sec-num">6</span> Déroulement de l'accouchement</div>
    <div class="neo-sec-body">
        <div class="neo-grid g4">
            <div><label class="neo-label">Mode d'accouchement</label>
                <select name="acc_mode" class="neo-select">
                    <option value="">—</option>
                    <option value="VB" <?= $sel('acc_mode','VB') ?>>Voie basse spontanée</option>
                    <option value="INSTRUMENTAL" <?= $sel('acc_mode','INSTRUMENTAL') ?>>Instrumental (forceps/ventouse)</option>
                    <option value="CESARIENNE" <?= $sel('acc_mode','CESARIENNE') ?>>Césarienne</option>
                    <option value="SIEGE" <?= $sel('acc_mode','SIEGE') ?>>Siège</option>
                </select>
            </div>
            <div><label class="neo-label">Lieu</label>
                <select name="acc_lieu" class="neo-select">
                    <option value="">—</option>
                    <option value="HSJM" <?= $sel('acc_lieu','HSJM') ?>>HSJM</option>
                    <option value="AUTRE_STRUCTURE" <?= $sel('acc_lieu','AUTRE_STRUCTURE') ?>>Autre structure</option>
                    <option value="DOMICILE" <?= $sel('acc_lieu','DOMICILE') ?>>Domicile</option>
                </select>
            </div>
            <div><label class="neo-label">Durée du travail</label><div class="neo-suffix"><input type="number" step="0.5" name="acc_duree_travail" class="neo-input" value="<?= $vr('acc_duree_travail') ?>" placeholder="6"><span class="u">h</span></div></div>
            <div><label class="neo-label">Présentation</label>
                <select name="acc_presentation" class="neo-select">
                    <option value="">—</option>
                    <option value="CEPHALIQUE" <?= $sel('acc_presentation','CEPHALIQUE') ?>>Céphalique</option>
                    <option value="SIEGE" <?= $sel('acc_presentation','SIEGE') ?>>Siège</option>
                    <option value="TRANSVERSE" <?= $sel('acc_presentation','TRANSVERSE') ?>>Transverse</option>
                    <option value="FACE" <?= $sel('acc_presentation','FACE') ?>>Face</option>
                </select>
            </div>
        </div>

        <div class="section-title-big mt-3"><i class="bi bi-thermometer-half" style="color:#1d4ed8;font-size:.85rem"></i> Facteurs de risque per-partum</div>
        <div class="neo-checks">
            <?php foreach([
                'Fièvre maternelle (≥38°C)'=>'acc_fievre','RPM > 18h'=>'acc_rpm','Chorioamniotite'=>'acc_chorioamniotite',
                'Pré-éclampsie'=>'acc_preeclampsie','SFA'=>'acc_sfa','Circulaire du cordon'=>'acc_circulaire'
            ] as $lbl=>$nm): ?>
            <label class="neo-check"><input type="checkbox" name="<?= $nm ?>" value="1" <?= $chk($nm) ?>><?= $lbl ?></label>
            <?php endforeach; ?>
        </div>

        <div class="neo-grid g3 mt-3">
            <div><label class="neo-label">Liquide amniotique</label>
                <select name="acc_liquide" class="neo-select">
                    <option value="">—</option>
                    <option value="CLAIR" <?= $sel('acc_liquide','CLAIR') ?>>Clair</option>
                    <option value="TEINTÉ" <?= $sel('acc_liquide','TEINTÉ') ?>>Teinté</option>
                    <option value="MÉCONIAL" <?= $sel('acc_liquide','MÉCONIAL') ?>>Méconial</option>
                    <option value="PURULENT" <?= $sel('acc_liquide','PURULENT') ?>>Purulent</option>
                    <option value="OLIGOAMNIOS" <?= $sel('acc_liquide','OLIGOAMNIOS') ?>>Oligoamnios</option>
                </select>
            </div>
            <div><label class="neo-label">Score d'Apgar à 1 min</label><input type="number" min="0" max="10" name="acc_apgar1" class="neo-input" value="<?= $vr('acc_apgar1') ?>" placeholder="/10"></div>
            <div><label class="neo-label">Score d'Apgar à 5 min</label><input type="number" min="0" max="10" name="acc_apgar5" class="neo-input" value="<?= $vr('acc_apgar5') ?>" placeholder="/10"></div>
        </div>
        <div class="neo-grid g2 mt-2">
            <div><label class="neo-label">Réanimation en salle de naissance</label>
                <div class="rad-group">
                    <?php foreach(['Non'=>'non','Stimulation'=>'stimulation','VPP masque'=>'vpp','Intubation'=>'intubation','MCE'=>'mce'] as $lbl=>$val): ?>
                    <label class="rad-btn"><input type="radio" name="acc_reanimation" value="<?= $val ?>" <?= $rad('acc_reanimation',$val) ?>><?= $lbl ?></label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div><label class="neo-label">Durée RPM (si présente)</label><div class="neo-suffix"><input type="number" name="acc_rpm_duree" class="neo-input" value="<?= $vr('acc_rpm_duree') ?>" placeholder="0"><span class="u">h</span></div></div>
        </div>
    </div>
</div>

<!-- ════════════════════════════════════════════════════════════
     SECTION 7 — SOINS AU NOUVEAU-NÉ EN SALLE DE NAISSANCE
     ════════════════════════════════════════════════════════════ -->
<div class="neo-section">
    <div class="neo-sec-head"><span class="neo-sec-num">7</span> Soins au nouveau-né en salle de naissance</div>
    <div class="neo-sec-body">
        <div class="neo-checks">
            <?php foreach([
                'Vit K1 donnée'=>'soins_vitk1','Collyre donné'=>'soins_collyre',
                'PEV démarré'=>'soins_pev','Vit D donnée'=>'soins_vitd'
            ] as $lbl=>$nm): ?>
            <label class="neo-check"><input type="checkbox" name="<?= $nm ?>" value="1" <?= $chk($nm) ?>><?= $lbl ?></label>
            <?php endforeach; ?>
        </div>
        <div class="neo-grid g3 mt-3">
            <div><label class="neo-label">Alimentation mise en place</label>
                <select name="soins_alimentation" class="neo-select">
                    <option value="">—</option>
                    <option value="SEIN_EXCLUSIF" <?= $sel('soins_alimentation','SEIN_EXCLUSIF') ?>>Allaitement maternel exclusif</option>
                    <option value="ARTIFICIEL" <?= $sel('soins_alimentation','ARTIFICIEL') ?>>Artificiel</option>
                    <option value="MIXTE" <?= $sel('soins_alimentation','MIXTE') ?>>Mixte</option>
                    <option value="GAVAGE" <?= $sel('soins_alimentation','GAVAGE') ?>>Gavage</option>
                    <option value="NPE" <?= $sel('soins_alimentation','NPE') ?>>NPE (en attente)</option>
                </select>
            </div>
            <div><label class="neo-label">T° à la naissance</label><div class="neo-suffix"><input type="number" step="0.1" name="soins_temp_naissance" class="neo-input" value="<?= $vr('soins_temp_naissance') ?>" placeholder="37.0"><span class="u">°C</span></div></div>
            <div><label class="neo-label">Remarques soins</label><input type="text" name="soins_remarques" class="neo-input" value="<?= $vr('soins_remarques') ?>" placeholder="Observations complémentaires..."></div>
        </div>
    </div>
</div>

<!-- ════════════════════════════════════════════════════════════
     SECTION 8 — PTME (ARV mère et bébé)
     ════════════════════════════════════════════════════════════ -->
<div class="neo-section">
    <div class="neo-sec-head"><span class="neo-sec-num">8</span> PTME — Prévention de la Transmission Mère-Enfant</div>
    <div class="neo-sec-body">
        <div class="neo-grid g2">
            <div>
                <div class="section-title-big"><i class="bi bi-person-fill" style="color:#1d4ed8;font-size:.8rem"></i> Mère VIH+</div>
                <div class="neo-grid g2">
                    <div><label class="neo-label">ARV pendant grossesse</label>
                        <div class="rad-group">
                            <label class="rad-btn"><input type="radio" name="ptme_mere_arv" value="oui" <?= $rad('ptme_mere_arv','oui') ?>>Oui</label>
                            <label class="rad-btn"><input type="radio" name="ptme_mere_arv" value="non" <?= $rad('ptme_mere_arv','non') ?>>Non</label>
                        </div>
                    </div>
                    <div><label class="neo-label">Protocole ARV mère</label><input type="text" name="ptme_mere_protocole" class="neo-input" value="<?= $vr('ptme_mere_protocole') ?>" placeholder="TDF+3TC+EFV..."></div>
                </div>
                <div class="mt-2"><label class="neo-label">Charge virale mère (si connue)</label><input type="text" name="ptme_cv_mere" class="neo-input" value="<?= $vr('ptme_cv_mere') ?>" placeholder="copies/mL ou indétectable"></div>
            </div>
            <div>
                <div class="section-title-big"><i class="bi bi-gender-male" style="color:#1d4ed8;font-size:.8rem"></i> Nouveau-né</div>
                <div class="neo-grid g2">
                    <div><label class="neo-label">ARV prophylaxie bébé</label>
                        <div class="rad-group">
                            <label class="rad-btn"><input type="radio" name="ptme_bebe_arv" value="oui" <?= $rad('ptme_bebe_arv','oui') ?>>Oui</label>
                            <label class="rad-btn"><input type="radio" name="ptme_bebe_arv" value="non" <?= $rad('ptme_bebe_arv','non') ?>>Non</label>
                        </div>
                    </div>
                    <div><label class="neo-label">Protocole ARV bébé</label><input type="text" name="ptme_bebe_protocole" class="neo-input" value="<?= $vr('ptme_bebe_protocole') ?>" placeholder="NVP, AZT 6sem..."></div>
                </div>
                <div class="mt-2"><label class="neo-label">Allaitement</label>
                    <select name="ptme_allaitement" class="neo-select">
                        <option value="">—</option>
                        <option value="SUBSTITUT" <?= $sel('ptme_allaitement','SUBSTITUT') ?>>Substitut (lait artificiel)</option>
                        <option value="SEIN" <?= $sel('ptme_allaitement','SEIN') ?>>Allaitement maternel sous ARV</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="mt-2"><label class="neo-label">N° fiche PTME</label><input type="text" name="ptme_fiche_num" class="neo-input" value="<?= $vr('ptme_fiche_num') ?>" placeholder="N° registre PTME" style="max-width:300px;"></div>
    </div>
</div>

<!-- ════════════════════════════════════════════════════════════
     SECTION 9 — ENQUÊTE DES SYSTÈMES
     ════════════════════════════════════════════════════════════ -->
<div class="neo-section">
    <div class="neo-sec-head"><span class="neo-sec-num">9</span> Enquête des systèmes</div>
    <div class="neo-sec-body">
        <table class="sys-table">
            <thead>
                <tr>
                    <th>Système</th>
                    <th>Signes / Symptômes — cocher si présent</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $systemes = [
                'Général' => [
                    ['sys_gen_fievre','Fièvre'],['sys_gen_hypothermie','Hypothermie'],
                    ['sys_gen_hypotonie','Hypotonie'],['sys_gen_irritabilite','Irritabilité'],
                    ['sys_gen_mauvaise_prise','Mauvaise prise du sein'],
                ],
                'Respiratoire' => [
                    ['sys_resp_detresse','Détresse respiratoire'],['sys_resp_tirage','Tirage'],
                    ['sys_resp_gemissement','Gémissement'],['sys_resp_cyanose','Cyanose'],
                    ['sys_resp_apnee','Apnée'],['sys_resp_tachypnee','Tachypnée'],
                ],
                'Cardio-vasculaire' => [
                    ['sys_cv_tachycardie','Tachycardie'],['sys_cv_bradycardie','Bradycardie'],
                    ['sys_cv_souffle','Souffle cardiaque'],['sys_cv_marbrures','Marbrures'],
                    ['sys_cv_tem_allonge','TRC allongé'],
                ],
                'Digestif' => [
                    ['sys_dig_vomissements','Vomissements'],['sys_dig_distension','Distension abdominale'],
                    ['sys_dig_absence_selles','Absence de selles'],['sys_dig_sang_selles','Sang dans selles'],
                    ['sys_dig_ictere','Ictère'],['sys_dig_hepatomegalie','Hépatomégalie'],
                ],
                'Neurologique' => [
                    ['sys_neuro_convulsions','Convulsions'],['sys_neuro_hypertonie','Hypertonie'],
                    ['sys_neuro_fontanelle_bombe','Fontanelle bombée'],
                    ['sys_neuro_cri_plaintif','Cri plaintif'],['sys_neuro_tremblement','Tremblement'],
                ],
                'Cutané' => [
                    ['sys_cut_ictere','Ictère cutané'],['sys_cut_eruption','Éruption cutanée'],
                    ['sys_cut_purpura','Purpura / pétéchies'],['sys_cut_omphalite','Omphalite'],
                    ['sys_cut_cyanose_periph','Cyanose périphérique'],
                ],
                'Urinaire' => [
                    ['sys_uri_anurie','Anurie / oliguries'],['sys_uri_hematurie','Hématurie'],
                    ['sys_uri_oedemes','Œdèmes'],
                ],
            ];
            foreach ($systemes as $sys => $signes): ?>
            <tr>
                <td><?= htmlspecialchars($sys) ?></td>
                <td>
                    <div class="neo-checks" style="margin:0;">
                    <?php foreach ($signes as [$nm, $lbl]): ?>
                        <label class="neo-check" style="padding:4px 10px;font-size:.79rem;">
                            <input type="checkbox" name="<?= $nm ?>" value="1" <?= $chk($nm) ?>>
                            <?= $lbl ?>
                        </label>
                    <?php endforeach; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <div class="mt-3"><label class="neo-label">Précisions / autres signes</label>
            <textarea name="sys_autres" class="neo-textarea" placeholder="Détails complémentaires sur l'enquête des systèmes..."><?= $vr('sys_autres') ?></textarea>
        </div>
    </div>
</div>

<!-- ════════════════════════════════════════════════════════════
     SECTION 10 — EXAMEN PHYSIQUE
     ════════════════════════════════════════════════════════════ -->
<div class="neo-section">
    <div class="neo-sec-head"><span class="neo-sec-num">10</span> Examen physique</div>
    <div class="neo-sec-body">

        <!-- 10a : Paramètres vitaux & anthropométriques -->
        <div class="section-title-big"><i class="bi bi-activity" style="color:#1d4ed8;font-size:.85rem"></i> Paramètres vitaux &amp; anthropométriques</div>
        <div class="neo-grid g4">
            <div><label class="neo-label">Poids actuel</label><div class="neo-suffix"><input type="number" name="ep_poids" class="neo-input" value="<?= $vr('ep_poids') ?>" placeholder="3200"><span class="u">g</span></div></div>
            <div><label class="neo-label">Taille</label><div class="neo-suffix"><input type="number" step="0.1" name="ep_taille" class="neo-input" value="<?= $vr('ep_taille') ?>" placeholder="49"><span class="u">cm</span></div></div>
            <div><label class="neo-label">Périmètre crânien</label><div class="neo-suffix"><input type="number" step="0.1" name="ep_pc" class="neo-input" value="<?= $vr('ep_pc') ?>" placeholder="34"><span class="u">cm</span></div></div>
            <div><label class="neo-label">Périm. thoracique</label><div class="neo-suffix"><input type="number" step="0.1" name="ep_pt" class="neo-input" value="<?= $vr('ep_pt') ?>" placeholder="32"><span class="u">cm</span></div></div>
        </div>
        <div class="neo-grid g4 mt-2">
            <div><label class="neo-label">Température</label><div class="neo-suffix"><input type="number" step="0.1" name="ep_temp" class="neo-input" value="<?= $vr('ep_temp') ?>" placeholder="37.0"><span class="u">°C</span></div></div>
            <div><label class="neo-label">Fréq. cardiaque</label><div class="neo-suffix"><input type="number" name="ep_fc" class="neo-input" value="<?= $vr('ep_fc') ?>" placeholder="130"><span class="u">bpm</span></div></div>
            <div><label class="neo-label">Fréq. respiratoire</label><div class="neo-suffix"><input type="number" name="ep_fr" class="neo-input" value="<?= $vr('ep_fr') ?>" placeholder="45"><span class="u">/min</span></div></div>
            <div><label class="neo-label">SpO₂</label><div class="neo-suffix"><input type="number" name="ep_spo2" class="neo-input" value="<?= $vr('ep_spo2') ?>" placeholder="96"><span class="u">%</span></div></div>
        </div>
        <div class="neo-grid g3 mt-2">
            <div><label class="neo-label">Glycémie</label><div class="neo-suffix"><input type="number" step="0.1" name="ep_glycemie" class="neo-input" value="<?= $vr('ep_glycemie') ?>" placeholder="4.5"><span class="u">mmol/L</span></div></div>
            <div><label class="neo-label">Diurèse</label><div class="neo-suffix"><input type="number" step="0.1" name="ep_diurese" class="neo-input" value="<?= $vr('ep_diurese') ?>" placeholder="1.0"><span class="u">cc/kg/h</span></div></div>
            <div><label class="neo-label">Âge gestationnel évalué (Ballard)</label><div class="neo-suffix"><input type="number" step="0.5" name="ep_ballard" class="neo-input" value="<?= $vr('ep_ballard') ?>" placeholder="38"><span class="u">SA</span></div></div>
        </div>

        <!-- 10b : Inspection générale -->
        <div class="section-title-big mt-3"><i class="bi bi-eye" style="color:#1d4ed8;font-size:.85rem"></i> Inspection générale</div>
        <div class="neo-grid g3">
            <div><label class="neo-label">État général</label>
                <select name="ep_etat_general" class="neo-select">
                    <option value="">—</option>
                    <option value="BON" <?= $sel('ep_etat_general','BON') ?>>Bon état général</option>
                    <option value="MOYEN" <?= $sel('ep_etat_general','MOYEN') ?>>État général moyen</option>
                    <option value="MAUVAIS" <?= $sel('ep_etat_general','MAUVAIS') ?>>Mauvais état général</option>
                </select>
            </div>
            <div><label class="neo-label">Tonus</label>
                <select name="ep_tonus" class="neo-select">
                    <option value="">—</option>
                    <option value="NORMAL" <?= $sel('ep_tonus','NORMAL') ?>>Normal</option>
                    <option value="HYPOTONIE" <?= $sel('ep_tonus','HYPOTONIE') ?>>Hypotonie</option>
                    <option value="HYPERTONIE" <?= $sel('ep_tonus','HYPERTONIE') ?>>Hypertonie</option>
                </select>
            </div>
            <div><label class="neo-label">Coloration</label>
                <select name="ep_coloration" class="neo-select">
                    <option value="">—</option>
                    <option value="ROSE" <?= $sel('ep_coloration','ROSE') ?>>Rose (normale)</option>
                    <option value="PALE" <?= $sel('ep_coloration','PALE') ?>>Pâle</option>
                    <option value="ICTERIQUE" <?= $sel('ep_coloration','ICTERIQUE') ?>>Ictérique</option>
                    <option value="CYANOSE" <?= $sel('ep_coloration','CYANOSE') ?>>Cyanosé</option>
                    <option value="GRIS" <?= $sel('ep_coloration','GRIS') ?>>Gris (état de choc)</option>
                </select>
            </div>
        </div>

        <!-- 10c : Tête et face -->
        <div class="section-title-big mt-3"><i class="bi bi-emoji-neutral" style="color:#1d4ed8;font-size:.85rem"></i> Tête &amp; face</div>
        <div class="neo-grid g3">
            <div><label class="neo-label">Fontanelle antérieure</label>
                <select name="ep_fontanelle" class="neo-select">
                    <option value="">—</option>
                    <option value="NORMALE" <?= $sel('ep_fontanelle','NORMALE') ?>>Normale (plane, souple)</option>
                    <option value="BOMBEE" <?= $sel('ep_fontanelle','BOMBEE') ?>>Bombée</option>
                    <option value="DEPRIMEE" <?= $sel('ep_fontanelle','DEPRIMEE') ?>>Déprimée</option>
                    <option value="CLOSE" <?= $sel('ep_fontanelle','CLOSE') ?>>Close</option>
                </select>
            </div>
            <div><label class="neo-label">Crâne / boîte crânienne</label><input type="text" name="ep_crane" class="neo-input" value="<?= $vr('ep_crane') ?>" placeholder="Asymétrie, bosses, modelage..."></div>
            <div><label class="neo-label">Yeux / oreilles / palais</label><input type="text" name="ep_orl" class="neo-input" value="<?= $vr('ep_orl') ?>" placeholder="Anomalies, réflexe photomoteur..."></div>
        </div>

        <!-- 10d : Cou et clavicules -->
        <div class="section-title-big mt-3"><i class="bi bi-body-text" style="color:#1d4ed8;font-size:.85rem"></i> Cou &amp; clavicules</div>
        <div class="neo-grid g2">
            <div><label class="neo-label">Cou</label><input type="text" name="ep_cou" class="neo-input" value="<?= $vr('ep_cou') ?>" placeholder="Masse, mobilité, torticolis..."></div>
            <div><label class="neo-label">Clavicules</label>
                <select name="ep_clavicules" class="neo-select">
                    <option value="">—</option>
                    <option value="INTACTES" <?= $sel('ep_clavicules','INTACTES') ?>>Intactes</option>
                    <option value="FRACTURE_D" <?= $sel('ep_clavicules','FRACTURE_D') ?>>Fracture droite</option>
                    <option value="FRACTURE_G" <?= $sel('ep_clavicules','FRACTURE_G') ?>>Fracture gauche</option>
                    <option value="FRACTURE_BILAT" <?= $sel('ep_clavicules','FRACTURE_BILAT') ?>>Fracture bilatérale</option>
                </select>
            </div>
        </div>

        <!-- 10e : Thorax / Score de Silverman -->
        <div class="section-title-big mt-3"><i class="bi bi-lungs" style="color:#1d4ed8;font-size:.85rem"></i> Thorax &amp; Score de Silverman</div>
        <div class="silverman-grid">
            <?php
            $silverItems = [
                'Battement ailes du nez' => 'silv_batt_ailes',
                'Tirage sous-costal'      => 'silv_tirage_sc',
                'Tirage intercostal'      => 'silv_tirage_ic',
                'Entonnoir xiphoïdien'    => 'silv_entonnoir',
                'Balancement thoraco-abd' => 'silv_balancement',
            ];
            foreach ($silverItems as $lbl => $nm): ?>
            <div class="silverman-item">
                <label><?= $lbl ?></label>
                <input type="number" min="0" max="2" name="<?= $nm ?>" class="neo-input" value="<?= $vr($nm,'0') ?>" placeholder="0–2"
                    oninput="updateSilverman()">
            </div>
            <?php endforeach; ?>
        </div>
        <div class="mt-2" style="display:flex;align-items:center;gap:10px;">
            <span class="neo-label" style="margin:0;">Score de Silverman total :</span>
            <span id="silvermanScore" style="font-size:1.3rem;font-weight:900;color:#1d4ed8;">0</span>
            <span id="silvermanInterp" style="font-size:.82rem;font-weight:600;padding:3px 10px;border-radius:8px;background:#eff6ff;color:#1d4ed8;"></span>
        </div>
        <div class="neo-grid g2 mt-3">
            <div><label class="neo-label">Auscultation pulmonaire</label><textarea name="ep_pulm" class="neo-textarea" placeholder="Murmure vésiculaire, râles, bruits surajoutés..."><?= $vr('ep_pulm') ?></textarea></div>
            <div><label class="neo-label">Auscultation cardiaque</label><textarea name="ep_cardio" class="neo-textarea" placeholder="Bruits du cœur, souffle, rythme..."><?= $vr('ep_cardio') ?></textarea></div>
        </div>

        <!-- 10f : Abdomen / Périnée -->
        <div class="section-title-big mt-3"><i class="bi bi-radioactive" style="color:#1d4ed8;font-size:.85rem"></i> Abdomen &amp; périnée</div>
        <div class="neo-grid g3">
            <div><label class="neo-label">Abdomen</label><textarea name="ep_abdomen" class="neo-textarea" placeholder="Souple, ballonné, défense, masse, ombilic..."><?= $vr('ep_abdomen') ?></textarea></div>
            <div>
                <div><label class="neo-label">Foie (bord inférieur)</label><div class="neo-suffix"><input type="number" step="0.5" name="ep_foie" class="neo-input" value="<?= $vr('ep_foie') ?>" placeholder="0"><span class="u">cm</span></div></div>
                <div class="mt-2"><label class="neo-label">Rate</label>
                    <select name="ep_rate" class="neo-select">
                        <option value="">—</option>
                        <option value="NON_PALP" <?= $sel('ep_rate','NON_PALP') ?>>Non palpable</option>
                        <option value="PALP" <?= $sel('ep_rate','PALP') ?>>Palpable</option>
                        <option value="SPLENO" <?= $sel('ep_rate','SPLENO') ?>>Splénomégalie</option>
                    </select>
                </div>
            </div>
            <div><label class="neo-label">Périnée / Organes génitaux</label><textarea name="ep_perinee" class="neo-textarea" placeholder="Malformation, ambiguïté sexuelle, hypospadias, hernie inguinale..."><?= $vr('ep_perinee') ?></textarea></div>
        </div>

        <!-- 10g : Appareil locomoteur -->
        <div class="section-title-big mt-3"><i class="bi bi-person-walking" style="color:#1d4ed8;font-size:.85rem"></i> Appareil locomoteur</div>
        <div class="neo-grid g2">
            <div><label class="neo-label">Membres supérieurs</label><input type="text" name="ep_ms" class="neo-input" value="<?= $vr('ep_ms') ?>" placeholder="Symétrie, mobilité, fractures..."></div>
            <div><label class="neo-label">Membres inférieurs</label><input type="text" name="ep_mi" class="neo-input" value="<?= $vr('ep_mi') ?>" placeholder="Hanches (Barlow/Ortolani), pied bot..."></div>
        </div>
        <div class="neo-checks mt-2">
            <?php foreach(['Barlow +'=>'ep_barlow','Ortolani +'=>'ep_ortolani','Pied bot'=>'ep_piedbot','Anomalie morphologique autre'=>'ep_anomalie_morph'] as $lbl=>$nm): ?>
            <label class="neo-check"><input type="checkbox" name="<?= $nm ?>" value="1" <?= $chk($nm) ?>><?= $lbl ?></label>
            <?php endforeach; ?>
        </div>

        <!-- 10h : Examen neurologique / EREGIE -->
        <div class="section-title-big mt-3"><i class="bi bi-lightning-charge" style="color:#1d4ed8;font-size:.85rem"></i> Examen neurologique (EREGIE)</div>
        <div class="neo-grid g3">
            <div><label class="neo-label">Réflexe de succion</label>
                <select name="ep_r_succion" class="neo-select">
                    <option value="">—</option>
                    <option value="PRESENT" <?= $sel('ep_r_succion','PRESENT') ?>>Présent et vigoureux</option>
                    <option value="FAIBLE" <?= $sel('ep_r_succion','FAIBLE') ?>>Faible / Insuffisant</option>
                    <option value="ABSENT" <?= $sel('ep_r_succion','ABSENT') ?>>Absent</option>
                </select>
            </div>
            <div><label class="neo-label">Réflexe de Moro</label>
                <select name="ep_r_moro" class="neo-select">
                    <option value="">—</option>
                    <option value="PRESENT" <?= $sel('ep_r_moro','PRESENT') ?>>Présent et symétrique</option>
                    <option value="ASYMETRIQUE" <?= $sel('ep_r_moro','ASYMETRIQUE') ?>>Asymétrique</option>
                    <option value="ABSENT" <?= $sel('ep_r_moro','ABSENT') ?>>Absent</option>
                </select>
            </div>
            <div><label class="neo-label">Réflexe de grasping</label>
                <select name="ep_r_grasping" class="neo-select">
                    <option value="">—</option>
                    <option value="PRESENT" <?= $sel('ep_r_grasping','PRESENT') ?>>Présent</option>
                    <option value="FAIBLE" <?= $sel('ep_r_grasping','FAIBLE') ?>>Faible</option>
                    <option value="ABSENT" <?= $sel('ep_r_grasping','ABSENT') ?>>Absent</option>
                </select>
            </div>
        </div>
        <div class="neo-grid g2 mt-2">
            <div><label class="neo-label">Marche automatique / points cardinaux</label>
                <div class="rad-group">
                    <label class="rad-btn"><input type="radio" name="ep_r_marche" value="PRESENT" <?= $rad('ep_r_marche','PRESENT') ?>>Présent</label>
                    <label class="rad-btn"><input type="radio" name="ep_r_marche" value="ABSENT" <?= $rad('ep_r_marche','ABSENT') ?>>Absent</label>
                </div>
            </div>
            <div><label class="neo-label">Ictère — Zones de Kramer</label>
                <select name="ep_kramer" class="neo-select">
                    <option value="">—</option>
                    <option value="0" <?= $sel('ep_kramer','0') ?>>Absent (0)</option>
                    <option value="1" <?= $sel('ep_kramer','1') ?>>Zone 1 (visage)</option>
                    <option value="2" <?= $sel('ep_kramer','2') ?>>Zone 2 (jusqu'au nombril)</option>
                    <option value="3" <?= $sel('ep_kramer','3') ?>>Zone 3 (jusqu'aux genoux)</option>
                    <option value="4" <?= $sel('ep_kramer','4') ?>>Zone 4 (paumes et plantes)</option>
                    <option value="5" <?= $sel('ep_kramer','5') ?>>Zone 5 (extrémités)</option>
                </select>
            </div>
        </div>
        <div class="mt-2"><label class="neo-label">Remarques neurologiques</label>
            <textarea name="ep_neuro_remarques" class="neo-textarea" placeholder="Hypertonie, hypotonie axiale, asymétrie des réflexes, mouvements anormaux..."><?= $vr('ep_neuro_remarques') ?></textarea>
        </div>
    </div>
</div>

<!-- ════════════════════════════════════════════════════════════
     SECTION 11 — RÉSUMÉ SYNDROMIQUE
     ════════════════════════════════════════════════════════════ -->
<div class="neo-section">
    <div class="neo-sec-head"><span class="neo-sec-num">11</span> Résumé syndromique</div>
    <div class="neo-sec-body">
        <div><label class="neo-label">Résumé des syndromes identifiés</label>
            <textarea name="resume_syndromique" class="neo-textarea" style="min-height:80px;" placeholder="Ex : Nouveau-né à terme, détresse respiratoire néonatale sévère (Silverman 6), ictère Kramer III, sepsis néonatal suspect..."><?= $vr('resume_syndromique') ?></textarea>
        </div>
    </div>
</div>

<!-- ════════════════════════════════════════════════════════════
     SECTION 12 — HYPOTHÈSES DIAGNOSTIQUES
     ════════════════════════════════════════════════════════════ -->
<div class="neo-section">
    <div class="neo-sec-head"><span class="neo-sec-num">12</span> Hypothèses diagnostiques</div>
    <div class="neo-sec-body">
        <div class="neo-grid g1" style="gap:12px;">
            <div><label class="neo-label">Diagnostic positif retenu *</label>
                <input type="text" name="diag_positif" class="neo-input" value="<?= $vr('diag_positif') ?>" placeholder="Diagnostic principal retenu" required style="font-size:.95rem;font-weight:700;">
            </div>
        </div>
        <div class="neo-grid g2 mt-3">
            <div><label class="neo-label">Diagnostic différentiel 1</label><input type="text" name="diag_diff_1" class="neo-input" value="<?= $vr('diag_diff_1') ?>" placeholder="Alternative diagnostique"></div>
            <div><label class="neo-label">Diagnostic différentiel 2</label><input type="text" name="diag_diff_2" class="neo-input" value="<?= $vr('diag_diff_2') ?>" placeholder="Autre alternative"></div>
        </div>
        <div class="mt-2"><label class="neo-label">Arguments cliniques pour le diagnostic retenu</label>
            <textarea name="diag_arguments" class="neo-textarea" placeholder="Éléments cliniques, para-cliniques et anamnestiques retenus..."><?= $vr('diag_arguments') ?></textarea>
        </div>
    </div>
</div>

<!-- ════════════════════════════════════════════════════════════
     SECTION 13 — BILAN PARACLINIQUE
     ════════════════════════════════════════════════════════════ -->
<div class="neo-section">
    <div class="neo-sec-head"><span class="neo-sec-num">13</span> Bilan paraclinique demandé</div>
    <div class="neo-sec-body">
        <div class="section-title-big"><i class="bi bi-flask" style="color:#1d4ed8;font-size:.85rem"></i> Bilan diagnostique</div>
        <div class="neo-checks">
            <?php foreach([
                'NFS / Hémogramme'=>'bilan_nfs','CRP'=>'bilan_crp','Hémoculture'=>'bilan_hemoculture',
                'ECBU'=>'bilan_ecbu','BBS (bilan bilirubine)'=>'bilan_bilirubine',
                'Glycémie capillaire'=>'bilan_glycemie','Ionogramme sanguin'=>'bilan_ionogramme',
                'Gaz du sang'=>'bilan_gds','Lactates'=>'bilan_lactates','Coagulation (TP/TCA)'=>'bilan_coag'
            ] as $lbl=>$nm): ?>
            <label class="neo-check"><input type="checkbox" name="<?= $nm ?>" value="1" <?= $chk($nm) ?>><?= $lbl ?></label>
            <?php endforeach; ?>
        </div>

        <div class="section-title-big mt-3"><i class="bi bi-search-heart" style="color:#1d4ed8;font-size:.85rem"></i> Bilan étiologique</div>
        <div class="neo-checks">
            <?php foreach([
                'Sérologie VIH bébé'=>'bilan_vih_bebe','PCR VIH bébé'=>'bilan_pcr_vih',
                'TPHA / VDRL bébé'=>'bilan_syphilis_bebe','Transaminases (ASAT/ALAT)'=>'bilan_transaminases',
                'Coombs direct'=>'bilan_coombs','Groupe sanguin bébé'=>'bilan_gs_bebe',
                'LCR (PL)'=>'bilan_lcs','EEG'=>'bilan_eeg'
            ] as $lbl=>$nm): ?>
            <label class="neo-check"><input type="checkbox" name="<?= $nm ?>" value="1" <?= $chk($nm) ?>><?= $lbl ?></label>
            <?php endforeach; ?>
        </div>

        <div class="section-title-big mt-3"><i class="bi bi-camera-video" style="color:#1d4ed8;font-size:.85rem"></i> Imagerie / Autres</div>
        <div class="neo-checks">
            <?php foreach([
                'Radiographie thorax'=>'bilan_rtx','Radiographie abdomen'=>'bilan_rax',
                'Échographie trans-fontanellaire (ETF)'=>'bilan_etf','Échographie cardiaque'=>'bilan_echo_card',
                'Échographie abdominale'=>'bilan_echo_abd','Échographie rénale'=>'bilan_echo_rein',
            ] as $lbl=>$nm): ?>
            <label class="neo-check"><input type="checkbox" name="<?= $nm ?>" value="1" <?= $chk($nm) ?>><?= $lbl ?></label>
            <?php endforeach; ?>
        </div>
        <div class="mt-3"><label class="neo-label">Autres examens demandés</label>
            <input type="text" name="bilan_autres" class="neo-input" value="<?= $vr('bilan_autres') ?>" placeholder="Préciser...">
        </div>
        <div class="mt-2"><label class="neo-label">Résultats disponibles à ce jour</label>
            <textarea name="bilan_resultats_dispo" class="neo-textarea" placeholder="Résultats déjà obtenus..."><?= $vr('bilan_resultats_dispo') ?></textarea>
        </div>
    </div>
</div>

<!-- ════════════════════════════════════════════════════════════
     SECTION 14 — PRISE EN CHARGE
     ════════════════════════════════════════════════════════════ -->
<div class="neo-section">
    <div class="neo-sec-head"><span class="neo-sec-num">14</span> Prise en charge</div>
    <div class="neo-sec-body">
        <div><label class="neo-label">Buts du traitement</label>
            <input type="text" name="pec_buts" class="neo-input" value="<?= $vr('pec_buts') ?>" placeholder="Réanimation, stabilisation, traitement étiologique, prévention complications...">
        </div>

        <div class="section-title-big mt-3"><i class="bi bi-capsule" style="color:#1d4ed8;font-size:.85rem"></i> Moyens médicaux</div>
        <div class="neo-checks mb-2">
            <?php foreach([
                'Oxygénothérapie'=>'pec_oxygene','VPP'=>'pec_vpp','CPAP'=>'pec_cpap','Ventilation mécanique'=>'pec_vm',
                'Photothérapie'=>'pec_phototherapie','Exsanguino-transfusion'=>'pec_exsanguin',
                'Transfusion'=>'pec_transfusion','Antibiothérapie'=>'pec_antibio',
                'Anticonvulsivants'=>'pec_anticonv','Corticoïdes'=>'pec_cortico',
                'Surfactant'=>'pec_surfactant','Vitamines / suppléments'=>'pec_vitamines',
            ] as $lbl=>$nm): ?>
            <label class="neo-check"><input type="checkbox" name="<?= $nm ?>" value="1" <?= $chk($nm) ?>><?= $lbl ?></label>
            <?php endforeach; ?>
        </div>
        <div><label class="neo-label">Ordonnance / Prescriptions</label>
            <textarea name="pec_prescriptions" class="neo-textarea" style="min-height:80px;" placeholder="Médicaments, posologies, voie, durée..."><?= $vr('pec_prescriptions') ?></textarea>
        </div>

        <div class="section-title-big mt-3"><i class="bi bi-bandaid" style="color:#1d4ed8;font-size:.85rem"></i> Moyens non médicamenteux</div>
        <div class="neo-grid g2">
            <div><label class="neo-label">Nursing / soins de base</label>
                <div class="neo-checks">
                    <?php foreach(['Thermorégulation'=>'pec_thermo','Positionnement'=>'pec_positionnement','Soins ombilic'=>'pec_soins_ombilic','Méthode Kangourou'=>'pec_kangourou'] as $lbl=>$nm): ?>
                    <label class="neo-check" style="font-size:.79rem;padding:5px 10px;"><input type="checkbox" name="<?= $nm ?>" value="1" <?= $chk($nm) ?>><?= $lbl ?></label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div><label class="neo-label">Alimentation prescrite</label>
                <select name="pec_alimentation" class="neo-select">
                    <option value="">—</option>
                    <option value="SEIN_EXCLUSIF" <?= $sel('pec_alimentation','SEIN_EXCLUSIF') ?>>Allaitement maternel exclusif</option>
                    <option value="ARTIFICIEL" <?= $sel('pec_alimentation','ARTIFICIEL') ?>>Lait artificiel</option>
                    <option value="MIXTE" <?= $sel('pec_alimentation','MIXTE') ?>>Mixte</option>
                    <option value="GAVAGE" <?= $sel('pec_alimentation','GAVAGE') ?>>Gavage</option>
                    <option value="NPT" <?= $sel('pec_alimentation','NPT') ?>>NPT (nutrition parentérale totale)</option>
                    <option value="NPO" <?= $sel('pec_alimentation','NPO') ?>>NPO (nil per os)</option>
                </select>
            </div>
        </div>

        <div class="section-title-big mt-3"><i class="bi bi-scissors" style="color:#1d4ed8;font-size:.85rem"></i> Chirurgie / orthopédie</div>
        <div><label class="neo-label">Geste chirurgical / orthopédique envisagé</label>
            <textarea name="pec_chirurgie" class="neo-textarea" placeholder="Indication, technique, timing..."><?= $vr('pec_chirurgie') ?></textarea>
        </div>
    </div>
</div>

<!-- ════════════════════════════════════════════════════════════
     SECTION 15 — SURVEILLANCE
     ════════════════════════════════════════════════════════════ -->
<div class="neo-section">
    <div class="neo-sec-head"><span class="neo-sec-num">15</span> Surveillance</div>
    <div class="neo-sec-body">
        <div class="neo-checks mb-3">
            <?php foreach([
                'Constantes toutes les heures'=>'surv_constantes_1h',
                'Constantes toutes les 3h'=>'surv_constantes_3h',
                'Constantes toutes les 6h'=>'surv_constantes_6h',
                'Monitoring cardio-respiratoire'=>'surv_monitoring',
                'Oxymétrie en continu'=>'surv_oxymetre',
                'Glycémie capillaire / 6h'=>'surv_glycemie',
                'Diurèse horaire'=>'surv_diurese_h',
                'Bilan entrées / sorties'=>'surv_bilan_io',
                'Courbe de poids quotidienne'=>'surv_poids',
                'Bilirubine quotidienne'=>'surv_bilirubine',
                'NFS de contrôle'=>'surv_nfs_controle',
                'CRP de contrôle'=>'surv_crp_controle',
                'Radiographie de contrôle'=>'surv_rtx_controle',
            ] as $lbl=>$nm): ?>
            <label class="neo-check"><input type="checkbox" name="<?= $nm ?>" value="1" <?= $chk($nm) ?>><?= $lbl ?></label>
            <?php endforeach; ?>
        </div>
        <div class="neo-grid g2">
            <div><label class="neo-label">Critères de sortie / transfert</label>
                <textarea name="surv_criteres_sortie" class="neo-textarea" placeholder="Conditions requises pour la sortie ou le transfert..."><?= $vr('surv_criteres_sortie') ?></textarea>
            </div>
            <div><label class="neo-label">Observations complémentaires</label>
                <textarea name="surv_observations" class="neo-textarea" placeholder="Notes, remarques, informations aux parents..."><?= $vr('surv_observations') ?></textarea>
            </div>
        </div>
        <div class="neo-grid g2 mt-2">
            <div><label class="neo-label">Date du prochain contrôle</label><input type="date" name="surv_rdv" class="neo-input" value="<?= $vr('surv_rdv') ?>"></div>
            <div><label class="neo-label">Médecin référent</label><input type="text" name="surv_medecin_ref" class="neo-input" value="<?= $vr('surv_medecin_ref') ?>" placeholder="Nom du médecin référent"></div>
        </div>
    </div>
</div>

<!-- Bouton de sauvegarde bas de page -->
<div style="display:flex;justify-content:flex-end;gap:10px;padding-top:10px;">
    <a href="<?= BASE_URL ?>neonatologie" class="btn btn-outline-secondary">
        <i class="bi bi-x-lg"></i> Annuler
    </a>
    <button type="submit" class="btn btn-primary" style="background:#1d4ed8;border-color:#1d4ed8;padding:10px 28px;font-weight:700;">
        <i class="bi bi-floppy-fill"></i> Enregistrer l'observation
    </button>
</div>

</div><!-- /.neo-form -->
</form>
</main>

<script>
/* Score de Silverman dynamique */
function updateSilverman() {
    const fields = ['silv_batt_ailes','silv_tirage_sc','silv_tirage_ic','silv_entonnoir','silv_balancement'];
    let total = 0;
    fields.forEach(f => { total += parseInt(document.querySelector('[name="'+f+'"]')?.value || 0); });
    const el = document.getElementById('silvermanScore');
    const interp = document.getElementById('silvermanInterp');
    if (el) el.textContent = total;
    if (interp) {
        if (total === 0) { interp.textContent = 'Pas de détresse'; interp.style.background='#f0fdf4'; interp.style.color='#166534'; }
        else if (total <= 3) { interp.textContent = 'Détresse légère'; interp.style.background='#fffbeb'; interp.style.color='#92400e'; }
        else if (total <= 5) { interp.textContent = 'Détresse modérée'; interp.style.background='#fff7ed'; interp.style.color='#9a3412'; }
        else { interp.textContent = 'Détresse sévère'; interp.style.background='#fef2f2'; interp.style.color='#991b1b'; }
    }
}
// Init Silverman au chargement
document.addEventListener('DOMContentLoaded', updateSilverman);
</script>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
