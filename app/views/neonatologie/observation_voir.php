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
$patient  = $patient ?? [];
$age      = $age ?? '—';
$d        = $obs['_data'] ?? [];
$vr       = fn($k, $def='—') => htmlspecialchars(!empty($d[$k]) ? $d[$k] : $def);
$chkd     = fn($k) => !empty($d[$k]);
$date_obs = !empty($obs['date_observation']) ? date('d/m/Y H:i', strtotime($obs['date_observation'])) : '—';
$medecin  = trim(strtoupper($obs['medecin_nom'] ?? '') . ' ' . ($obs['medecin_prenom'] ?? ''));

// Score de Silverman
$silverFields = ['silv_batt_ailes','silv_tirage_sc','silv_tirage_ic','silv_entonnoir','silv_balancement'];
$silvScore = array_sum(array_map(fn($f) => (int)($d[$f] ?? 0), $silverFields));
?>
<style>
    .sidebar, nav.sidebar { display:none !important; }
    main, .col-md-10, .ms-sm-auto { margin-left:0 !important; width:100% !important; flex:0 0 100% !important; max-width:100% !important; padding:0 !important; }
    body { background:#f0f4f8; font-family:'Segoe UI',system-ui,sans-serif; }

    .obs-topbar { background:linear-gradient(135deg,#1d4ed8,#3b82f6); padding:11px 24px; display:flex; align-items:center; justify-content:space-between; position:sticky; top:0; z-index:100; box-shadow:0 3px 14px rgba(59,130,246,.3); }
    .obs-topbar .t { font-size:.95rem; font-weight:800; color:#fff; display:flex; align-items:center; gap:8px; }
    .obs-tb-btn { display:inline-flex; align-items:center; gap:5px; padding:6px 14px; border-radius:8px; font-weight:700; font-size:.76rem; text-decoration:none; border:none; cursor:pointer; }
    .btn-light-t  { background:rgba(255,255,255,.18); color:#fff; border:1px solid rgba(255,255,255,.3); }
    .btn-print-t  { background:#fff; color:#1d4ed8; }

    .obs-wrap { max-width:900px; margin:0 auto; padding:20px 22px 60px; }

    /* Entête d'impression */
    .print-header { background:#fff; border-radius:14px; padding:18px 22px; margin-bottom:18px; box-shadow:0 2px 10px rgba(59,130,246,.07); border:1px solid #dbeafe; display:flex; align-items:flex-start; gap:16px; }
    .print-logo-box { width:60px; height:60px; background:linear-gradient(135deg,#1d4ed8,#60a5fa); border-radius:14px; display:flex; align-items:center; justify-content:center; color:#fff; font-size:1.6rem; flex-shrink:0; }
    .print-facility { font-size:.82rem; color:#475569; line-height:1.4; }
    .print-facility strong { display:block; font-size:1.05rem; color:#1e293b; font-weight:800; }
    .print-title { text-align:right; flex:1; }
    .print-title h2 { font-size:1rem; font-weight:900; color:#1d4ed8; margin:0 0 4px; text-transform:uppercase; letter-spacing:.5px; }
    .print-title .meta { font-size:.75rem; color:#64748b; }

    .obs-section { background:#fff; border-radius:12px; box-shadow:0 2px 8px rgba(59,130,246,.05); border:1px solid #dbeafe; margin-bottom:14px; overflow:hidden; }
    .obs-sec-head { padding:9px 16px; font-weight:800; font-size:.75rem; text-transform:uppercase; letter-spacing:.5px; color:#1d4ed8; background:#eff6ff; border-bottom:2px solid #bfdbfe; display:flex; align-items:center; gap:7px; }
    .obs-sec-num  { background:#1d4ed8; color:#fff; border-radius:50%; width:18px; height:18px; display:inline-flex; align-items:center; justify-content:center; font-size:.65rem; font-weight:900; flex-shrink:0; }
    .obs-sec-body { padding:14px 16px; font-size:.84rem; color:#1e293b; }

    .obs-grid { display:grid; gap:10px; }
    .g2 { grid-template-columns:repeat(2,1fr); } .g3 { grid-template-columns:repeat(3,1fr); } .g4 { grid-template-columns:repeat(4,1fr); }
    @media(max-width:700px){ .g2,.g3,.g4 { grid-template-columns:1fr 1fr; } }

    .obs-field { }
    .obs-field .lbl { font-size:.68rem; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:.4px; margin-bottom:2px; }
    .obs-field .val { font-weight:600; color:#1e293b; }
    .obs-field .val.empty { color:#94a3b8; font-weight:400; font-style:italic; }

    .tag-yes { display:inline-flex; align-items:center; gap:4px; background:#dcfce7; color:#166534; border:1px solid #86efac; border-radius:8px; padding:3px 9px; font-size:.75rem; font-weight:700; margin:2px; }
    .tag-no  { display:inline-flex; align-items:center; gap:4px; background:#f1f5f9; color:#94a3b8; border:1px solid #e2e8f0; border-radius:8px; padding:3px 9px; font-size:.75rem; margin:2px; }

    .badge-diag { display:inline-block; background:linear-gradient(135deg,#1d4ed8,#3b82f6); color:#fff; border-radius:10px; padding:6px 16px; font-weight:800; font-size:.9rem; }
    .badge-diff  { display:inline-block; background:#eff6ff; color:#1d4ed8; border:1px solid #bfdbfe; border-radius:9px; padding:4px 12px; font-weight:600; font-size:.82rem; margin:2px; }

    .silv-row { display:flex; gap:8px; align-items:center; margin-bottom:4px; }
    .silv-score-badge { font-size:1.4rem; font-weight:900; color:#1d4ed8; }
    .silv-interp { padding:3px 10px; border-radius:8px; font-size:.78rem; font-weight:700; }

    .check-list { display:flex; flex-wrap:wrap; gap:6px; }

    .section-sub { font-size:.72rem; font-weight:700; color:#1d4ed8; text-transform:uppercase; letter-spacing:.4px; margin:10px 0 5px; border-bottom:1px solid #e2e8f0; padding-bottom:3px; }

    .sig-box { border:1.5px dashed #bfdbfe; border-radius:10px; padding:14px 18px; text-align:center; min-height:70px; font-size:.8rem; color:#64748b; }

    @media print {
        .obs-topbar { display:none !important; }
        body { background:#fff !important; }
        .obs-section, .print-header { box-shadow:none !important; border-color:#d1d5db !important; }
        .obs-wrap { padding:0; max-width:100%; }
        .obs-section { page-break-inside:avoid; }
    }
</style>

<main>
<!-- BARRE ACTIONS -->
<div class="obs-topbar">
    <div class="t"><i class="bi bi-journal-medical"></i> Observation médicale — <?= htmlspecialchars(strtoupper($patient['nom'] ?? '') . ' ' . ($patient['prenom'] ?? '')) ?></div>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>neonatologie/observation/<?= (int)$obs['patient_id'] ?>" class="obs-tb-btn btn-light-t"><i class="bi bi-pencil"></i> Nouvelle observation</a>
        <a href="<?= BASE_URL ?>neonatologie" class="obs-tb-btn btn-light-t"><i class="bi bi-arrow-left"></i> Retour</a>
        <button onclick="window.print()" class="obs-tb-btn btn-print-t"><i class="bi bi-printer-fill"></i> Imprimer</button>
    </div>
</div>

<div class="obs-wrap">

<!-- EN-TÊTE DOCUMENT -->
<div class="print-header">
    <div class="print-logo-box"><i class="bi bi-hospital-fill"></i></div>
    <div class="print-facility">
        <strong>Hôpital Saint-Jean de Malte (HSJM)</strong>
        Service de Néonatologie / Pédiatrie<br>
        Bangui — République Centrafricaine
    </div>
    <div class="print-title">
        <h2><i class="bi bi-journal-medical"></i> Observation médicale du nouveau-né</h2>
        <div class="meta">
            N° observation : <strong>#<?= (int)$obs['id'] ?></strong>
            &nbsp;&bull;&nbsp; Date : <strong><?= $date_obs ?></strong><br>
            Médecin : <strong><?= htmlspecialchars($medecin ?: 'Non précisé') ?></strong>
        </div>
    </div>
</div>

<!-- SECTION 1 — IDENTITÉ -->
<div class="obs-section">
    <div class="obs-sec-head"><span class="obs-sec-num">1</span> Identité du nouveau-né</div>
    <div class="obs-sec-body">
        <div class="obs-grid g4">
            <div class="obs-field"><div class="lbl">Nom complet</div><div class="val"><?= htmlspecialchars(strtoupper($patient['nom'] ?? '') . ' ' . ($patient['prenom'] ?? '')) ?></div></div>
            <div class="obs-field"><div class="lbl">Né(e) le</div><div class="val"><?= !empty($patient['date_naissance']) ? date('d/m/Y', strtotime($patient['date_naissance'])) : '—' ?></div></div>
            <div class="obs-field"><div class="lbl">Heure naissance</div><div class="val"><?= $vr('identite_heure_naissance') ?></div></div>
            <div class="obs-field"><div class="lbl">Âge</div><div class="val"><?= htmlspecialchars($age) ?></div></div>
            <div class="obs-field"><div class="lbl">Sexe</div><div class="val"><?= strtoupper($patient['sexe'] ?? '') === 'F' ? 'Féminin' : 'Masculin' ?></div></div>
            <div class="obs-field"><div class="lbl">N° dossier</div><div class="val"><?= htmlspecialchars($patient['dossier_numero'] ?? '—') ?></div></div>
            <div class="obs-field"><div class="lbl">Nom de la mère</div><div class="val"><?= $vr('identite_mere') ?></div></div>
            <div class="obs-field"><div class="lbl">Provenance</div><div class="val"><?= $vr('identite_provenance') ?></div></div>
        </div>
    </div>
</div>

<!-- SECTION 2 — MOTIF -->
<div class="obs-section">
    <div class="obs-sec-head"><span class="obs-sec-num">2</span> Motif de consultation</div>
    <div class="obs-sec-body">
        <div class="obs-grid g3">
            <?php foreach([1,2,3] as $i): $m = $d["motif_$i"] ?? ''; if($m): ?>
            <div class="obs-field"><div class="lbl">Motif <?= $i ?></div><div class="val"><?= htmlspecialchars($m) ?></div></div>
            <?php endif; endforeach; ?>
        </div>
    </div>
</div>

<!-- SECTION 3 — HMA -->
<?php if(!empty($d['hma'])): ?>
<div class="obs-section">
    <div class="obs-sec-head"><span class="obs-sec-num">3</span> Histoire de la maladie actuelle</div>
    <div class="obs-sec-body"><?= nl2br(htmlspecialchars($d['hma'])) ?></div>
</div>
<?php endif; ?>

<!-- SECTION 4 — ANTÉCÉDENTS FAMILIAUX -->
<div class="obs-section">
    <div class="obs-sec-head"><span class="obs-sec-num">4</span> Antécédents familiaux</div>
    <div class="obs-sec-body">
        <div class="section-sub">Mère</div>
        <div class="obs-grid g4">
            <div class="obs-field"><div class="lbl">Âge</div><div class="val"><?= $vr('mere_age') ?></div></div>
            <div class="obs-field"><div class="lbl">Gestité / Parité</div><div class="val">G<?= $vr('mere_gestite','?') ?> P<?= $vr('mere_parite','?') ?></div></div>
            <div class="obs-field"><div class="lbl">Groupe sanguin</div><div class="val"><?= $vr('mere_gs') ?></div></div>
            <div class="obs-field"><div class="lbl">Consanguinité</div><div class="val"><?= $vr('consanguinite') ?></div></div>
        </div>
        <?php if(!empty($d['mere_atcd_med'])): ?>
        <div class="obs-field mt-2"><div class="lbl">ATCD médicaux mère</div><div class="val"><?= nl2br(htmlspecialchars($d['mere_atcd_med'])) ?></div></div>
        <?php endif; ?>
        <?php if(!empty($d['mere_atcd_obst'])): ?>
        <div class="obs-field mt-1"><div class="lbl">ATCD obstétricaux</div><div class="val"><?= nl2br(htmlspecialchars($d['mere_atcd_obst'])) ?></div></div>
        <?php endif; ?>
        <div class="section-sub mt-2">Père</div>
        <div class="obs-grid g3">
            <div class="obs-field"><div class="lbl">Âge</div><div class="val"><?= $vr('pere_age') ?></div></div>
            <div class="obs-field"><div class="lbl">Groupe sanguin</div><div class="val"><?= $vr('pere_gs') ?></div></div>
            <div class="obs-field"><div class="lbl">ATCD pertinents</div><div class="val"><?= $vr('pere_atcd') ?></div></div>
        </div>
    </div>
</div>

<!-- SECTION 5 — GROSSESSE -->
<div class="obs-section">
    <div class="obs-sec-head"><span class="obs-sec-num">5</span> Déroulement de la grossesse</div>
    <div class="obs-sec-body">
        <div class="obs-grid g4">
            <div class="obs-field"><div class="lbl">DDR</div><div class="val"><?= !empty($d['grossesse_ddr']) ? date('d/m/Y', strtotime($d['grossesse_ddr'])) : '—' ?></div></div>
            <div class="obs-field"><div class="lbl">DPA</div><div class="val"><?= !empty($d['grossesse_dpa']) ? date('d/m/Y', strtotime($d['grossesse_dpa'])) : '—' ?></div></div>
            <div class="obs-field"><div class="lbl">Terme</div><div class="val"><?= $vr('grossesse_terme') ?> SA</div></div>
            <div class="obs-field"><div class="lbl">Nb. CPN</div><div class="val"><?= $vr('grossesse_nb_cpn') ?></div></div>
        </div>
        <div class="section-sub mt-2">Sérologies maternelles</div>
        <div class="check-list">
            <?php foreach(['serol_vih'=>'VIH','serol_syphilis'=>'Syphilis','serol_vhb'=>'Hépatite B','serol_rubeole'=>'Rubéole','serol_toxo'=>'Toxoplasmose'] as $k=>$lbl): ?>
            <span class="<?= $chkd($k) ? 'tag-yes' : 'tag-no' ?>"><?= $chkd($k) ? '✓' : '—' ?> <?= $lbl ?></span>
            <?php endforeach; ?>
        </div>
        <div class="obs-grid g3 mt-2">
            <div class="obs-field"><div class="lbl">Statut VIH mère</div><div class="val"><?= $vr('mere_vih_statut') ?></div></div>
            <div class="obs-field"><div class="lbl">Statut syphilis</div><div class="val"><?= $vr('mere_syphilis_statut') ?></div></div>
            <div class="obs-field"><div class="lbl">Hépatite B mère</div><div class="val"><?= $vr('mere_vhb_statut') ?></div></div>
        </div>
        <?php if(!empty($d['grossesse_pathologies'])): ?>
        <div class="obs-field mt-2"><div class="lbl">Pathologies grossesse</div><div class="val"><?= htmlspecialchars($d['grossesse_pathologies']) ?></div></div>
        <?php endif; ?>
    </div>
</div>

<!-- SECTION 6 — ACCOUCHEMENT -->
<div class="obs-section">
    <div class="obs-sec-head"><span class="obs-sec-num">6</span> Déroulement de l'accouchement</div>
    <div class="obs-sec-body">
        <div class="obs-grid g4">
            <div class="obs-field"><div class="lbl">Mode</div><div class="val"><?= $vr('acc_mode') ?></div></div>
            <div class="obs-field"><div class="lbl">Lieu</div><div class="val"><?= $vr('acc_lieu') ?></div></div>
            <div class="obs-field"><div class="lbl">Présentation</div><div class="val"><?= $vr('acc_presentation') ?></div></div>
            <div class="obs-field"><div class="lbl">Durée travail</div><div class="val"><?= $vr('acc_duree_travail') ?> h</div></div>
        </div>
        <div class="section-sub mt-2">Facteurs de risque</div>
        <div class="check-list">
            <?php foreach(['acc_fievre'=>'Fièvre mat.','acc_rpm'=>'RPM>18h','acc_chorioamniotite'=>'Chorioamniotite','acc_preeclampsie'=>'Pré-éclampsie','acc_sfa'=>'SFA','acc_circulaire'=>'Circulaire'] as $k=>$lbl): ?>
            <?php if($chkd($k)): ?><span class="tag-yes">✓ <?= $lbl ?></span><?php endif; ?>
            <?php endforeach; ?>
        </div>
        <div class="obs-grid g4 mt-2">
            <div class="obs-field"><div class="lbl">Liquide amniotique</div><div class="val"><?= $vr('acc_liquide') ?></div></div>
            <div class="obs-field"><div class="lbl">Apgar 1 min</div><div class="val"><?= $vr('acc_apgar1') ?>/10</div></div>
            <div class="obs-field"><div class="lbl">Apgar 5 min</div><div class="val"><?= $vr('acc_apgar5') ?>/10</div></div>
            <div class="obs-field"><div class="lbl">Réanimation</div><div class="val"><?= $vr('acc_reanimation') ?></div></div>
        </div>
    </div>
</div>

<!-- SECTION 7 — SOINS EN SALLE -->
<div class="obs-section">
    <div class="obs-sec-head"><span class="obs-sec-num">7</span> Soins au nouveau-né</div>
    <div class="obs-sec-body">
        <div class="check-list">
            <?php foreach(['soins_vitk1'=>'Vit K1','soins_collyre'=>'Collyre','soins_pev'=>'PEV','soins_vitd'=>'Vit D'] as $k=>$lbl): ?>
            <span class="<?= $chkd($k) ? 'tag-yes' : 'tag-no' ?>"><?= $chkd($k) ? '✓' : '—' ?> <?= $lbl ?></span>
            <?php endforeach; ?>
        </div>
        <div class="obs-grid g3 mt-2">
            <div class="obs-field"><div class="lbl">Alimentation</div><div class="val"><?= $vr('soins_alimentation') ?></div></div>
            <div class="obs-field"><div class="lbl">T° naissance</div><div class="val"><?= $vr('soins_temp_naissance') ?> °C</div></div>
            <div class="obs-field"><div class="lbl">Remarques</div><div class="val"><?= $vr('soins_remarques') ?></div></div>
        </div>
    </div>
</div>

<!-- SECTION 8 — PTME -->
<div class="obs-section">
    <div class="obs-sec-head"><span class="obs-sec-num">8</span> PTME</div>
    <div class="obs-sec-body">
        <div class="obs-grid g4">
            <div class="obs-field"><div class="lbl">ARV mère / grossesse</div><div class="val"><?= $vr('ptme_mere_arv') ?></div></div>
            <div class="obs-field"><div class="lbl">Protocole ARV mère</div><div class="val"><?= $vr('ptme_mere_protocole') ?></div></div>
            <div class="obs-field"><div class="lbl">ARV bébé</div><div class="val"><?= $vr('ptme_bebe_arv') ?></div></div>
            <div class="obs-field"><div class="lbl">Protocole ARV bébé</div><div class="val"><?= $vr('ptme_bebe_protocole') ?></div></div>
        </div>
        <div class="obs-grid g2 mt-2">
            <div class="obs-field"><div class="lbl">Allaitement</div><div class="val"><?= $vr('ptme_allaitement') ?></div></div>
            <div class="obs-field"><div class="lbl">N° fiche PTME</div><div class="val"><?= $vr('ptme_fiche_num') ?></div></div>
        </div>
    </div>
</div>

<!-- SECTION 9 — ENQUÊTE DES SYSTÈMES -->
<div class="obs-section">
    <div class="obs-sec-head"><span class="obs-sec-num">9</span> Enquête des systèmes</div>
    <div class="obs-sec-body">
        <?php
        $systSignes = [
            'Général'=>['sys_gen_fievre'=>'Fièvre','sys_gen_hypothermie'=>'Hypothermie','sys_gen_hypotonie'=>'Hypotonie','sys_gen_irritabilite'=>'Irritabilité','sys_gen_mauvaise_prise'=>'Mauvaise prise sein'],
            'Respiratoire'=>['sys_resp_detresse'=>'Détresse resp.','sys_resp_tirage'=>'Tirage','sys_resp_gemissement'=>'Gémissement','sys_resp_cyanose'=>'Cyanose','sys_resp_apnee'=>'Apnée','sys_resp_tachypnee'=>'Tachypnée'],
            'Cardio-vasc.'=>['sys_cv_tachycardie'=>'Tachycardie','sys_cv_bradycardie'=>'Bradycardie','sys_cv_souffle'=>'Souffle','sys_cv_marbrures'=>'Marbrures','sys_cv_tem_allonge'=>'TRC allongé'],
            'Digestif'=>['sys_dig_vomissements'=>'Vomissements','sys_dig_distension'=>'Distension','sys_dig_absence_selles'=>'Abs. selles','sys_dig_sang_selles'=>'Sang selles','sys_dig_ictere'=>'Ictère','sys_dig_hepatomegalie'=>'Hépato'],
            'Neurologique'=>['sys_neuro_convulsions'=>'Convulsions','sys_neuro_hypertonie'=>'Hypertonie','sys_neuro_fontanelle_bombe'=>'Fontanelle bombée','sys_neuro_cri_plaintif'=>'Cri plaintif','sys_neuro_tremblement'=>'Tremblement'],
            'Cutané'=>['sys_cut_ictere'=>'Ictère cut.','sys_cut_eruption'=>'Éruption','sys_cut_purpura'=>'Purpura','sys_cut_omphalite'=>'Omphalite'],
            'Urinaire'=>['sys_uri_anurie'=>'Anurie','sys_uri_hematurie'=>'Hématurie','sys_uri_oedemes'=>'Œdèmes'],
        ];
        foreach ($systSignes as $sys => $signes):
            $positifs = array_filter(array_map(fn($k,$l) => $chkd($k) ? $l : null, array_keys($signes), $signes));
            if ($positifs): ?>
        <div style="margin-bottom:6px;">
            <span style="font-size:.72rem;font-weight:700;color:#475569;text-transform:uppercase;margin-right:6px;"><?= $sys ?> :</span>
            <?php foreach ($positifs as $lbl): ?>
            <span class="tag-yes">✓ <?= $lbl ?></span>
            <?php endforeach; ?>
        </div>
        <?php endif; endforeach; ?>
        <?php if(!empty($d['sys_autres'])): ?>
        <div class="obs-field mt-2"><div class="lbl">Précisions</div><div class="val"><?= nl2br(htmlspecialchars($d['sys_autres'])) ?></div></div>
        <?php endif; ?>
    </div>
</div>

<!-- SECTION 10 — EXAMEN PHYSIQUE -->
<div class="obs-section">
    <div class="obs-sec-head"><span class="obs-sec-num">10</span> Examen physique</div>
    <div class="obs-sec-body">
        <div class="section-sub">Paramètres vitaux &amp; anthropométriques</div>
        <div class="obs-grid g4">
            <div class="obs-field"><div class="lbl">Poids actuel</div><div class="val"><?= $vr('ep_poids') ?> g</div></div>
            <div class="obs-field"><div class="lbl">Taille</div><div class="val"><?= $vr('ep_taille') ?> cm</div></div>
            <div class="obs-field"><div class="lbl">Périm. crânien</div><div class="val"><?= $vr('ep_pc') ?> cm</div></div>
            <div class="obs-field"><div class="lbl">Périm. thoracique</div><div class="val"><?= $vr('ep_pt') ?> cm</div></div>
            <div class="obs-field"><div class="lbl">Température</div><div class="val"><?= $vr('ep_temp') ?> °C</div></div>
            <div class="obs-field"><div class="lbl">FC</div><div class="val"><?= $vr('ep_fc') ?> bpm</div></div>
            <div class="obs-field"><div class="lbl">FR</div><div class="val"><?= $vr('ep_fr') ?> /min</div></div>
            <div class="obs-field"><div class="lbl">SpO₂</div><div class="val"><?= $vr('ep_spo2') ?> %</div></div>
            <div class="obs-field"><div class="lbl">Glycémie</div><div class="val"><?= $vr('ep_glycemie') ?> mmol/L</div></div>
            <div class="obs-field"><div class="lbl">Diurèse</div><div class="val"><?= $vr('ep_diurese') ?> cc/kg/h</div></div>
            <div class="obs-field"><div class="lbl">Ballard</div><div class="val"><?= $vr('ep_ballard') ?> SA</div></div>
            <div class="obs-field"><div class="lbl">Kramer</div><div class="val">Zone <?= $vr('ep_kramer','—') ?></div></div>
        </div>

        <div class="section-sub mt-2">Score de Silverman</div>
        <div class="silv-row">
            <span class="silv-score-badge"><?= $silvScore ?>/10</span>
            <?php
            $si = '';
            if ($silvScore === 0) { $si = 'Pas de détresse'; $sic='#166534'; $sib='#dcfce7'; }
            elseif ($silvScore <= 3) { $si='Détresse légère'; $sic='#92400e'; $sib='#fffbeb'; }
            elseif ($silvScore <= 5) { $si='Détresse modérée'; $sic='#9a3412'; $sib='#fff7ed'; }
            else { $si='Détresse sévère'; $sic='#991b1b'; $sib='#fef2f2'; }
            ?>
            <span class="silv-interp" style="background:<?= $sib ?>;color:<?= $sic ?>;"><?= $si ?></span>
            <span style="font-size:.75rem;color:#64748b;">
                (Ailes nez:<?= $d['silv_batt_ailes']??0 ?> / Sous-cost:<?= $d['silv_tirage_sc']??0 ?> / Inter-cost:<?= $d['silv_tirage_ic']??0 ?> / Entonnoir:<?= $d['silv_entonnoir']??0 ?> / Balancement:<?= $d['silv_balancement']??0 ?>)
            </span>
        </div>

        <div class="section-sub mt-2">Inspection &amp; auscultation</div>
        <div class="obs-grid g3">
            <div class="obs-field"><div class="lbl">État général</div><div class="val"><?= $vr('ep_etat_general') ?></div></div>
            <div class="obs-field"><div class="lbl">Tonus</div><div class="val"><?= $vr('ep_tonus') ?></div></div>
            <div class="obs-field"><div class="lbl">Coloration</div><div class="val"><?= $vr('ep_coloration') ?></div></div>
            <div class="obs-field"><div class="lbl">Fontanelle</div><div class="val"><?= $vr('ep_fontanelle') ?></div></div>
            <div class="obs-field"><div class="lbl">Clavicules</div><div class="val"><?= $vr('ep_clavicules') ?></div></div>
            <div class="obs-field"><div class="lbl">Foie (débord)</div><div class="val"><?= $vr('ep_foie') ?> cm</div></div>
        </div>
        <?php if(!empty($d['ep_pulm'])): ?>
        <div class="obs-field mt-2"><div class="lbl">Auscultation pulmonaire</div><div class="val"><?= nl2br(htmlspecialchars($d['ep_pulm'])) ?></div></div>
        <?php endif; ?>
        <?php if(!empty($d['ep_cardio'])): ?>
        <div class="obs-field mt-1"><div class="lbl">Auscultation cardiaque</div><div class="val"><?= nl2br(htmlspecialchars($d['ep_cardio'])) ?></div></div>
        <?php endif; ?>
        <?php if(!empty($d['ep_abdomen'])): ?>
        <div class="obs-field mt-1"><div class="lbl">Abdomen</div><div class="val"><?= nl2br(htmlspecialchars($d['ep_abdomen'])) ?></div></div>
        <?php endif; ?>

        <div class="section-sub mt-2">Réflexes archaïques</div>
        <div class="obs-grid g4">
            <div class="obs-field"><div class="lbl">Succion</div><div class="val"><?= $vr('ep_r_succion') ?></div></div>
            <div class="obs-field"><div class="lbl">Moro</div><div class="val"><?= $vr('ep_r_moro') ?></div></div>
            <div class="obs-field"><div class="lbl">Grasping</div><div class="val"><?= $vr('ep_r_grasping') ?></div></div>
            <div class="obs-field"><div class="lbl">Marche automatique</div><div class="val"><?= $vr('ep_r_marche') ?></div></div>
        </div>
        <?php if(!empty($d['ep_neuro_remarques'])): ?>
        <div class="obs-field mt-2"><div class="lbl">Remarques neurologiques</div><div class="val"><?= nl2br(htmlspecialchars($d['ep_neuro_remarques'])) ?></div></div>
        <?php endif; ?>
    </div>
</div>

<!-- SECTION 11 — RÉSUMÉ SYNDROMIQUE -->
<?php if(!empty($d['resume_syndromique'])): ?>
<div class="obs-section">
    <div class="obs-sec-head"><span class="obs-sec-num">11</span> Résumé syndromique</div>
    <div class="obs-sec-body"><?= nl2br(htmlspecialchars($d['resume_syndromique'])) ?></div>
</div>
<?php endif; ?>

<!-- SECTION 12 — DIAGNOSTICS -->
<div class="obs-section">
    <div class="obs-sec-head"><span class="obs-sec-num">12</span> Hypothèses diagnostiques</div>
    <div class="obs-sec-body">
        <div style="margin-bottom:10px;">
            <div class="lbl" style="font-size:.68rem;font-weight:700;color:#64748b;text-transform:uppercase;margin-bottom:5px;">Diagnostic retenu</div>
            <span class="badge-diag"><?= htmlspecialchars($obs['diagnostic_principal'] ?? $d['diag_positif'] ?? '—') ?></span>
        </div>
        <?php if(!empty($d['diag_diff_1']) || !empty($d['diag_diff_2'])): ?>
        <div class="lbl" style="font-size:.68rem;font-weight:700;color:#64748b;text-transform:uppercase;margin-bottom:5px;">Diagnostics différentiels</div>
        <?php foreach(['diag_diff_1','diag_diff_2'] as $k): if(!empty($d[$k])): ?>
        <span class="badge-diff"><?= htmlspecialchars($d[$k]) ?></span>
        <?php endif; endforeach; ?>
        <?php endif; ?>
        <?php if(!empty($d['diag_arguments'])): ?>
        <div class="obs-field mt-2"><div class="lbl">Arguments cliniques</div><div class="val"><?= nl2br(htmlspecialchars($d['diag_arguments'])) ?></div></div>
        <?php endif; ?>
    </div>
</div>

<!-- SECTION 13 — BILAN PARACLINIQUE -->
<div class="obs-section">
    <div class="obs-sec-head"><span class="obs-sec-num">13</span> Bilan paraclinique demandé</div>
    <div class="obs-sec-body">
        <?php
        $bilans = [
            'Diagnostique'=>['bilan_nfs'=>'NFS','bilan_crp'=>'CRP','bilan_hemoculture'=>'Hémoculture','bilan_ecbu'=>'ECBU','bilan_bilirubine'=>'Bilirubine','bilan_glycemie'=>'Glycémie','bilan_ionogramme'=>'Ionogramme','bilan_gds'=>'Gaz du sang','bilan_lactates'=>'Lactates','bilan_coag'=>'Coagulation'],
            'Étiologique'=>['bilan_vih_bebe'=>'VIH bébé','bilan_pcr_vih'=>'PCR VIH','bilan_syphilis_bebe'=>'Syphilis bébé','bilan_transaminases'=>'Transaminases','bilan_coombs'=>'Coombs direct','bilan_gs_bebe'=>'Groupe sg. bébé','bilan_lcs'=>'LCR','bilan_eeg'=>'EEG'],
            'Imagerie'=>['bilan_rtx'=>'Rx Thorax','bilan_rax'=>'Rx Abdomen','bilan_etf'=>'ETF','bilan_echo_card'=>'Écho cœur','bilan_echo_abd'=>'Écho abd.','bilan_echo_rein'=>'Écho rein'],
        ];
        foreach ($bilans as $cat => $items):
            $positifs = array_filter(array_map(fn($k,$l) => $chkd($k) ? $l : null, array_keys($items), $items));
            if ($positifs): ?>
        <div style="margin-bottom:8px;">
            <div class="section-sub"><?= $cat ?></div>
            <div class="check-list">
                <?php foreach($positifs as $lbl): ?>
                <span class="tag-yes">✓ <?= $lbl ?></span>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; endforeach; ?>
        <?php if(!empty($d['bilan_autres'])): ?>
        <div class="obs-field mt-1"><div class="lbl">Autres</div><div class="val"><?= htmlspecialchars($d['bilan_autres']) ?></div></div>
        <?php endif; ?>
        <?php if(!empty($d['bilan_resultats_dispo'])): ?>
        <div class="obs-field mt-2"><div class="lbl">Résultats disponibles</div><div class="val"><?= nl2br(htmlspecialchars($d['bilan_resultats_dispo'])) ?></div></div>
        <?php endif; ?>
    </div>
</div>

<!-- SECTION 14 — PRISE EN CHARGE -->
<div class="obs-section">
    <div class="obs-sec-head"><span class="obs-sec-num">14</span> Prise en charge</div>
    <div class="obs-sec-body">
        <?php if(!empty($d['pec_buts'])): ?>
        <div class="obs-field mb-2"><div class="lbl">Buts du traitement</div><div class="val"><?= htmlspecialchars($d['pec_buts']) ?></div></div>
        <?php endif; ?>
        <div class="section-sub">Moyens médicaux prescrits</div>
        <?php
        $pecItems=['pec_oxygene'=>'Oxygénothérapie','pec_vpp'=>'VPP','pec_cpap'=>'CPAP','pec_vm'=>'Ventilation mécanique','pec_phototherapie'=>'Photothérapie','pec_exsanguin'=>'Exsanguino-transfusion','pec_transfusion'=>'Transfusion','pec_antibio'=>'Antibiothérapie','pec_anticonv'=>'Anticonvulsivants','pec_cortico'=>'Corticoïdes','pec_surfactant'=>'Surfactant','pec_vitamines'=>'Vitamines'];
        $pecPos = array_filter(array_map(fn($k,$l) => $chkd($k) ? $l : null, array_keys($pecItems), $pecItems));
        if ($pecPos): ?>
        <div class="check-list mb-2">
            <?php foreach($pecPos as $lbl): ?><span class="tag-yes">✓ <?= $lbl ?></span><?php endforeach; ?>
        </div>
        <?php endif; ?>
        <?php if(!empty($d['pec_prescriptions'])): ?>
        <div class="obs-field mb-2"><div class="lbl">Prescriptions</div><div class="val" style="white-space:pre-line;background:#f8fafc;padding:10px 12px;border-radius:8px;border:1px solid #e2e8f0;"><?= htmlspecialchars($d['pec_prescriptions']) ?></div></div>
        <?php endif; ?>
        <div class="obs-grid g2">
            <div class="obs-field"><div class="lbl">Alimentation prescrite</div><div class="val"><?= $vr('pec_alimentation') ?></div></div>
            <?php if(!empty($d['pec_chirurgie'])): ?>
            <div class="obs-field"><div class="lbl">Chirurgie/orthopédie</div><div class="val"><?= nl2br(htmlspecialchars($d['pec_chirurgie'])) ?></div></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- SECTION 15 — SURVEILLANCE -->
<div class="obs-section">
    <div class="obs-sec-head"><span class="obs-sec-num">15</span> Surveillance</div>
    <div class="obs-sec-body">
        <?php
        $survItems=['surv_constantes_1h'=>'Constantes/1h','surv_constantes_3h'=>'Constantes/3h','surv_constantes_6h'=>'Constantes/6h','surv_monitoring'=>'Monitoring cardio-resp.','surv_oxymetre'=>'Oxymétrie continue','surv_glycemie'=>'Glycémie/6h','surv_diurese_h'=>'Diurèse horaire','surv_bilan_io'=>'Bilan E/S','surv_poids'=>'Poids quotidien','surv_bilirubine'=>'Bilirubine quotidienne','surv_nfs_controle'=>'NFS contrôle','surv_crp_controle'=>'CRP contrôle','surv_rtx_controle'=>'Rx contrôle'];
        $survPos = array_filter(array_map(fn($k,$l) => $chkd($k) ? $l : null, array_keys($survItems), $survItems));
        if ($survPos): ?>
        <div class="check-list mb-2">
            <?php foreach($survPos as $lbl): ?><span class="tag-yes">✓ <?= $lbl ?></span><?php endforeach; ?>
        </div>
        <?php endif; ?>
        <div class="obs-grid g2 mt-2">
            <?php if(!empty($d['surv_criteres_sortie'])): ?>
            <div class="obs-field"><div class="lbl">Critères sortie/transfert</div><div class="val"><?= nl2br(htmlspecialchars($d['surv_criteres_sortie'])) ?></div></div>
            <?php endif; ?>
            <?php if(!empty($d['surv_observations'])): ?>
            <div class="obs-field"><div class="lbl">Observations complémentaires</div><div class="val"><?= nl2br(htmlspecialchars($d['surv_observations'])) ?></div></div>
            <?php endif; ?>
        </div>
        <div class="obs-grid g2 mt-2">
            <div class="obs-field"><div class="lbl">Prochain contrôle</div><div class="val"><?= !empty($d['surv_rdv']) ? date('d/m/Y', strtotime($d['surv_rdv'])) : '—' ?></div></div>
            <div class="obs-field"><div class="lbl">Médecin référent</div><div class="val"><?= $vr('surv_medecin_ref') ?></div></div>
        </div>
    </div>
</div>

<!-- SIGNATURE -->
<div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;margin-top:20px;">
    <div class="sig-box">
        <div style="font-weight:700;color:#1e293b;margin-bottom:6px;">Médecin examinateur</div>
        <div style="font-size:.8rem;color:#1d4ed8;font-weight:600;"><?= htmlspecialchars($medecin ?: '—') ?></div>
        <div style="margin-top:30px;border-top:1px dashed #bfdbfe;padding-top:6px;font-size:.7rem;">Signature</div>
    </div>
    <div class="sig-box">
        <div style="font-weight:700;color:#1e293b;margin-bottom:6px;">Chef de service</div>
        <div style="margin-top:30px;border-top:1px dashed #bfdbfe;padding-top:6px;font-size:.7rem;">Signature &amp; cachet</div>
    </div>
    <div class="sig-box">
        <div style="font-weight:700;color:#1e293b;margin-bottom:6px;">Date</div>
        <div style="font-size:.85rem;color:#475569;"><?= $date_obs ?></div>
    </div>
</div>

</div><!-- /.obs-wrap -->
</main>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
