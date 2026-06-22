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
$examen = $examen ?? [];
$age    = $age    ?? '—';

$termeLbl = ['PREMATURE'=>'Prématuré','A_TERME'=>'À terme','POST_TERME'=>'Post-terme'];
$modeLbl  = ['VOIE_BASSE'=>'Voie basse','CESARIENNE'=>'Césarienne','INSTRUMENTAL'=>'Instrumental'];
$alimLbl  = ['SEIN'=>'Allaitement maternel','ARTIFICIEL'=>'Lait artificiel','MIXTE'=>'Mixte','SONDE'=>'Sonde gastrique','PERFUSION'=>'Perfusion'];
$row = function($label,$val,$unit='') { if($val===null||$val==='') return ''; return '<div class="vr"><span class="vl">'.htmlspecialchars($label).'</span><span class="vv">'.htmlspecialchars($val).' '.$unit.'</span></div>'; };
?>
<style>
    .sidebar, nav.sidebar { display:none !important; }
    main, .col-md-10, .ms-sm-auto { margin-left:0 !important; width:100% !important; flex:0 0 100% !important; max-width:100% !important; padding:0 !important; }
    body { background:#f0f7f9; font-family:'Segoe UI',system-ui,sans-serif; }
    .neo-topbar { background:linear-gradient(135deg,#0e7490,#06b6d4); padding:14px 28px; display:flex; align-items:center; justify-content:space-between; position:sticky; top:0; z-index:100; box-shadow:0 4px 18px rgba(6,182,212,.3); }
    .neo-topbar .t { font-size:1.05rem; font-weight:800; color:#fff; display:flex; align-items:center; gap:9px; }
    .neo-tb-btn { display:inline-flex; align-items:center; gap:6px; padding:8px 16px; border-radius:10px; font-weight:700; font-size:.8rem; text-decoration:none; border:none; cursor:pointer; }
    .neo-tb-light { background:rgba(255,255,255,.2); color:#fff; border:1px solid rgba(255,255,255,.35); }
    .neo-tb-save  { background:#fff; color:#0e7490; }
    .neo-wrap { max-width:920px; margin:0 auto; padding:24px 28px 60px; }
    .pat-card { background:linear-gradient(135deg,#06b6d4,#22d3ee); color:#fff; border-radius:16px; padding:16px 22px; margin-bottom:22px; box-shadow:0 4px 18px rgba(6,182,212,.25); }
    .pat-name { font-size:1.2rem; font-weight:800; }
    .pat-meta { font-size:.8rem; opacity:.88; margin-top:2px; }
    .neo-section { background:#fff; border-radius:16px; box-shadow:0 2px 12px rgba(6,182,212,.06); border:1px solid #e0f0f3; margin-bottom:16px; overflow:hidden; }
    .neo-sec-head { padding:12px 20px; font-weight:800; font-size:.8rem; text-transform:uppercase; letter-spacing:.5px; color:#0e7490; background:#f0fdff; border-bottom:2px solid #cffafe; display:flex; align-items:center; gap:8px; }
    .neo-sec-body { padding:8px 20px; }
    .vr { display:flex; justify-content:space-between; padding:9px 0; border-bottom:1px solid #f1f5f9; }
    .vr:last-child { border-bottom:none; }
    .vl { color:#64748b; font-size:.84rem; } .vv { font-weight:700; color:#0f172a; font-size:.86rem; }
    .vitals-row { display:grid; grid-template-columns:repeat(auto-fit,minmax(90px,1fr)); gap:10px; padding:14px 0; }
    .vital-box { text-align:center; background:#f0fdff; border:1px solid #cffafe; border-radius:12px; padding:10px 4px; }
    .vital-box .n { font-size:1.2rem; font-weight:900; color:#0e7490; } .vital-box .n.warn { color:#dc2626; }
    .vital-box .l { font-size:.62rem; color:#94a3b8; text-transform:uppercase; font-weight:700; }
    .txt-block { padding:10px 0; } .txt-block .l { font-size:.72rem; color:#94a3b8; text-transform:uppercase; font-weight:700; margin-bottom:3px; } .txt-block .t { color:#1e293b; font-size:.88rem; white-space:pre-wrap; }
    .chip-yes { background:#ecfeff; color:#0e7490; border:1px solid #a5f3fc; border-radius:20px; padding:3px 12px; font-size:.76rem; font-weight:700; }
    .chip-no  { background:#f1f5f9; color:#94a3b8; border-radius:20px; padding:3px 12px; font-size:.76rem; font-weight:600; }
    @media print { .neo-topbar { box-shadow:none; } .neo-tb-btn { display:none; } body { background:#fff; } }
</style>
<main>
    <div class="neo-topbar">
        <div class="t"><i class="bi bi-clipboard2-heart"></i> Compte-rendu — Examen néonatal</div>
        <div class="d-flex gap-2">
            <a href="<?= BASE_URL ?>neonatologie" class="neo-tb-btn neo-tb-light"><i class="bi bi-arrow-left"></i> Néonatologie</a>
            <button onclick="window.print()" class="neo-tb-btn neo-tb-save"><i class="bi bi-printer"></i> Imprimer</button>
        </div>
    </div>
    <div class="neo-wrap">
        <div class="pat-card">
            <div class="pat-name"><i class="bi bi-<?= strtoupper($examen['sexe']??'')==='F'?'gender-female':'gender-male' ?> me-2"></i><?= htmlspecialchars(strtoupper($examen['nom']??'').' '.($examen['prenom']??'')) ?></div>
            <div class="pat-meta">
                N° <?= htmlspecialchars($examen['dossier_numero'] ?? '—') ?> &bull; Âge : <?= htmlspecialchars($age) ?>
                &bull; Examen du <?= date('d/m/Y à H:i', strtotime($examen['date_consultation'])) ?>
                <?php if (!empty($examen['medecin_nom'])): ?>&bull; Dr <?= htmlspecialchars($examen['medecin_prenom'].' '.$examen['medecin_nom']) ?><?php endif; ?>
            </div>
        </div>

        <!-- Constantes -->
        <div class="neo-section">
            <div class="neo-sec-head"><i class="bi bi-activity"></i> Constantes à l'examen</div>
            <div class="neo-sec-body">
                <?php
                // Normes néonatales : T° 36.5–37.5 · FC 120–180 · FR 30–80 · SpO2 > 92 · Diurèse 0.5–1 cc/kg/h
                $hn = fn($v, $min, $max = null) => $v !== null && (($min !== null && $v < $min) || ($max !== null && $v > $max));
                $etatLbl = ['EVEILLE'=>'Éveillé / réactif','SOMNOLENT'=>'Somnolent','HYPOTONIQUE'=>'Hypotonique',
                            'LETHARGIQUE'=>'Léthargique','IRRITABLE'=>'Irritable / geignard','COMATEUX'=>'Comateux'];
                $etatVal  = $examen['etat_conscience'] ?? null;
                $etatWarn = $etatVal && $etatVal !== 'EVEILLE';
                ?>
                <div class="vitals-row">
                    <div class="vital-box"><div class="n"><?= $examen['poids_actuel']!==null?(int)$examen['poids_actuel']:'—' ?></div><div class="l">Poids g</div></div>
                    <div class="vital-box"><div class="n <?= $hn($examen['temperature'], 36.5, 37.5)?'warn':'' ?>"><?= $examen['temperature']??'—' ?></div><div class="l">T° °C<br><small style="font-weight:400;">36.5–37.5</small></div></div>
                    <div class="vital-box"><div class="n <?= $hn($examen['freq_cardiaque'], 120, 180)?'warn':'' ?>"><?= $examen['freq_cardiaque']!==null?(int)$examen['freq_cardiaque']:'—' ?></div><div class="l">FC bpm<br><small style="font-weight:400;">120–180</small></div></div>
                    <div class="vital-box"><div class="n <?= $hn($examen['freq_respiratoire'], 30, 80)?'warn':'' ?>"><?= $examen['freq_respiratoire']!==null?(int)$examen['freq_respiratoire']:'—' ?></div><div class="l">FR /min<br><small style="font-weight:400;">30–80</small></div></div>
                    <div class="vital-box"><div class="n <?= $hn($examen['spo2'], 92)?'warn':'' ?>"><?= $examen['spo2']!==null?(int)$examen['spo2']:'—' ?></div><div class="l">SpO2 %<br><small style="font-weight:400;">&gt; 92</small></div></div>
                    <div class="vital-box"><div class="n <?= $hn($examen['diurese'] ?? null, 0.5, 1)?'warn':'' ?>"><?= ($examen['diurese'] ?? null) !== null ? $examen['diurese'] : '—' ?></div><div class="l">Diurèse cc/kg/h<br><small style="font-weight:400;">0.5–1</small></div></div>
                    <div class="vital-box"><div class="n <?= $etatWarn?'warn':'' ?>" style="font-size:.9rem;line-height:1.2;padding-top:6px;"><?= $etatVal ? htmlspecialchars($etatLbl[$etatVal] ?? $etatVal) : '—' ?></div><div class="l">Conscience</div></div>
                    <div class="vital-box"><div class="n"><?= $examen['glycemie']??'—' ?></div><div class="l">Glyc g/L</div></div>
                </div>
            </div>
        </div>

        <!-- Naissance -->
        <div class="neo-section">
            <div class="neo-sec-head"><i class="bi bi-balloon-heart"></i> Données de naissance</div>
            <div class="neo-sec-body">
                <?= $row('Âge gestationnel', $examen['age_gestationnel_sa'], 'SA') ?>
                <?= $row('Terme', $termeLbl[$examen['terme']] ?? $examen['terme']) ?>
                <?= $row('Mode d\'accouchement', $modeLbl[$examen['mode_accouchement']] ?? $examen['mode_accouchement']) ?>
                <?= $row('Poids de naissance', $examen['poids_naissance'], 'g') ?>
                <?= $row('Taille', $examen['taille_naissance'], 'cm') ?>
                <?= $row('Périmètre crânien', $examen['perimetre_cranien'], 'cm') ?>
                <?php if ($examen['apgar_1']!==null || $examen['apgar_5']!==null || $examen['apgar_10']!==null): ?>
                <div class="vr"><span class="vl">Score d'Apgar</span><span class="vv"><?= ($examen['apgar_1']??'—') ?> / <?= ($examen['apgar_5']??'—') ?> / <?= ($examen['apgar_10']??'—') ?> <small style="color:#94a3b8;">(1'/5'/10')</small></span></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Alimentation & clinique -->
        <div class="neo-section">
            <div class="neo-sec-head"><i class="bi bi-search-heart"></i> Alimentation & examen clinique</div>
            <div class="neo-sec-body">
                <?= $row('Alimentation', $alimLbl[$examen['alimentation']] ?? $examen['alimentation']) ?>
                <?= $row('Tolérance alimentaire', $examen['tolerance_alimentaire']) ?>
                <div class="vr"><span class="vl">Ictère</span><span><?= $examen['ictere']? '<span class="chip-yes">Présent'.($examen['ictere_kramer']?' · Kramer '.$examen['ictere_kramer']:'').'</span>' : '<span class="chip-no">Absent</span>' ?></span></div>
                <div class="vr"><span class="vl">Réflexes archaïques</span><span class="vv">
                    <?= $examen['reflexe_succion']?'Succion ':'' ?><?= $examen['reflexe_moro']?'· Moro ':'' ?><?= $examen['reflexe_grasping']?'· Grasping':'' ?>
                    <?= (!$examen['reflexe_succion'] && !$examen['reflexe_moro'] && !$examen['reflexe_grasping'])?'—':'' ?>
                </span></div>
                <?= $row('Fontanelle antérieure', $examen['fontanelle']) ?>
                <?php foreach ([['examen_cardio','Cardio-vasculaire'],['examen_respiratoire','Respiratoire'],['examen_abdomen','Abdomen / ombilic'],['examen_neuro','Neurologique'],['examen_peau_ombilic','Peau & téguments']] as $ex):
                    if (!empty($examen[$ex[0]])): ?>
                    <div class="txt-block"><div class="l"><?= $ex[1] ?></div><div class="t"><?= htmlspecialchars($examen[$ex[0]]) ?></div></div>
                <?php endif; endforeach; ?>
            </div>
        </div>

        <!-- Synthèse -->
        <div class="neo-section">
            <div class="neo-sec-head"><i class="bi bi-clipboard2-check"></i> Synthèse & conduite</div>
            <div class="neo-sec-body">
                <?php foreach ([['diagnostic','Diagnostic'],['conduite_tenir','Conduite à tenir'],['traitement','Traitement'],['observations','Observations']] as $ex):
                    if (!empty($examen[$ex[0]])): ?>
                    <div class="txt-block"><div class="l"><?= $ex[1] ?></div><div class="t"><?= htmlspecialchars($examen[$ex[0]]) ?></div></div>
                <?php endif; endforeach; ?>
            </div>
        </div>
    </div>
</main>
