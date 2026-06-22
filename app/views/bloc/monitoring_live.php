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
require_once __DIR__ . '/../../services/CsrfService.php';
$i = $intervention ?? [];
$recent_vitals = $recent_vitals ?? [];
$age = '';
if (!empty($i['date_naissance'])) { try { $age = (new DateTime($i['date_naissance']))->diff(new DateTime())->y.' ans'; } catch (Exception $e) {} }
?>
<style>
    body { background:#0f172a; font-family:'Inter','Segoe UI',sans-serif; color:#e2e8f0; }
    .sidebar, nav.sidebar { display:none !important; }
    main,.col-md-10,.ms-sm-auto{ margin-left:0!important;width:100%!important;max-width:100%!important;flex:0 0 100%!important;padding:0!important; }
    .ck-top { background:#1e293b; padding:14px 26px; display:flex; align-items:center; justify-content:space-between; border-bottom:1px solid #334155; position:sticky; top:0; z-index:50; }
    .ck-top .t { font-weight:800; color:#fff; display:flex; align-items:center; gap:10px; }
    .ck-top .t .live { background:#dc2626; color:#fff; font-size:.62rem; font-weight:800; padding:2px 9px; border-radius:20px; animation:bl 1.5s infinite; }
    @keyframes bl{50%{opacity:.5}}
    .ck-btn { padding:8px 16px; border-radius:10px; font-weight:700; font-size:.8rem; text-decoration:none; border:none; cursor:pointer; }
    .ck-back { background:#334155; color:#fff; }
    .ck-main { max-width:1150px; margin:0 auto; padding:22px 26px 60px; }
    .pat { background:#1e293b; border-radius:16px; padding:16px 22px; margin-bottom:20px; display:flex; gap:14px; flex-wrap:wrap; align-items:center; border:1px solid #334155; }
    .pat .nm { font-size:1.25rem; font-weight:800; color:#fff; }
    .pat .meta { font-size:.8rem; color:#94a3b8; }
    .pat .tag { background:#334155; color:#cbd5e1; border-radius:20px; padding:3px 12px; font-size:.74rem; font-weight:600; }
    .grid { display:grid; grid-template-columns:1.1fr 1fr; gap:20px; }
    @media(max-width:900px){ .grid{ grid-template-columns:1fr; } }
    .panel { background:#1e293b; border-radius:16px; border:1px solid #334155; overflow:hidden; }
    .panel h6 { margin:0; padding:13px 18px; font-size:.78rem; text-transform:uppercase; letter-spacing:.5px; font-weight:800; color:#cbd5e1; border-bottom:1px solid #334155; }
    .panel .bd { padding:16px 18px; }
    .vit-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:10px; }
    .vit { background:#0f172a; border:1px solid #334155; border-radius:12px; padding:10px; text-align:center; }
    .vit label { font-size:.62rem; color:#94a3b8; text-transform:uppercase; font-weight:700; display:block; margin-bottom:4px; }
    .vit input { width:100%; background:transparent; border:none; color:#fff; font-size:1.3rem; font-weight:800; text-align:center; outline:none; }
    .vit input::-webkit-outer-spin-button,.vit input::-webkit-inner-spin-button{-webkit-appearance:none;margin:0}
    .btn-save-vit { width:100%; margin-top:14px; background:#3b82f6; color:#fff; border:none; border-radius:10px; padding:11px; font-weight:700; cursor:pointer; }
    .btn-save-vit:hover{ background:#2563eb; }
    .vit-table { width:100%; border-collapse:collapse; font-size:.8rem; }
    .vit-table th { color:#64748b; font-size:.64rem; text-transform:uppercase; text-align:left; padding:7px 8px; border-bottom:1px solid #334155; }
    .vit-table td { padding:7px 8px; border-bottom:1px solid #1e293b; color:#cbd5e1; }
    .cro label { font-size:.72rem; color:#94a3b8; font-weight:700; display:block; margin:8px 0 4px; }
    .cro input,.cro textarea,.cro select { width:100%; background:#0f172a; border:1px solid #334155; border-radius:9px; padding:9px 11px; color:#fff; font-size:.86rem; outline:none; }
    .cro textarea{ resize:vertical; min-height:70px; }
    .btn-cro { width:100%; margin-top:14px; background:linear-gradient(135deg,#16a34a,#15803d); color:#fff; border:none; border-radius:10px; padding:12px; font-weight:800; cursor:pointer; }
</style>

<div class="ck-top">
    <div class="t"><span class="live">● EN COURS</span> <i class="bi bi-activity"></i> Cockpit per-opératoire</div>
    <a href="<?= BASE_URL ?>bloc" class="ck-btn ck-back"><i class="bi bi-arrow-left me-1"></i>Bloc</a>
</div>

<div class="ck-main">
    <div class="pat">
        <div style="flex:1;min-width:200px;">
            <div class="nm"><?= htmlspecialchars(strtoupper($i['patient_nom']??'').' '.($i['patient_prenom']??'')) ?></div>
            <div class="meta"><?= htmlspecialchars($i['dossier_numero']??'') ?> <?= $age?'· '.$age:'' ?> · <?= ($i['sexe']??'')==='F'?'Féminin':'Masculin' ?></div>
        </div>
        <span class="tag"><i class="bi bi-door-closed me-1"></i><?= htmlspecialchars($i['nom_salle']??'') ?></span>
        <span class="tag"><i class="bi bi-person-badge me-1"></i>Dr <?= htmlspecialchars($i['chirurgien']??'—') ?></span>
        <?php if(!empty($i['anesthesiste'])): ?><span class="tag"><i class="bi bi-lungs me-1"></i><?= htmlspecialchars($i['anesthesiste']) ?></span><?php endif; ?>
        <span class="tag" style="background:#3730a3;color:#c7d2fe;"><?= htmlspecialchars(mb_strimwidth($i['diagnostique_op']??'—',0,40,'…')) ?></span>
    </div>

    <div class="grid">
        <!-- Constantes -->
        <div class="panel">
            <h6><i class="bi bi-heart-pulse me-1"></i>Constantes per-opératoires</h6>
            <div class="bd">
                <div class="vit-grid">
                    <div class="vit"><label>FC (bpm)</label><input type="number" id="v_bpm" placeholder="—"></div>
                    <div class="vit"><label>SpO₂ (%)</label><input type="number" id="v_spo2" placeholder="—"></div>
                    <div class="vit"><label>FR (/min)</label><input type="number" id="v_fr" placeholder="—"></div>
                    <div class="vit"><label>TA sys</label><input type="number" id="v_tas" placeholder="—"></div>
                    <div class="vit"><label>TA dia</label><input type="number" id="v_tad" placeholder="—"></div>
                    <div class="vit"><label>T° (°C)</label><input type="number" step="0.1" id="v_temp" placeholder="—"></div>
                </div>
                <button class="btn-save-vit" onclick="saveVit()"><i class="bi bi-plus-circle me-1"></i>Enregistrer le relevé</button>
                <div id="vitMsg" style="font-size:.78rem;margin-top:8px;"></div>
                <table class="vit-table mt-3">
                    <thead><tr><th>Heure</th><th>FC</th><th>SpO₂</th><th>TA</th><th>FR</th><th>T°</th></tr></thead>
                    <tbody id="vitBody">
                        <?php foreach ($recent_vitals as $v): ?>
                        <tr>
                            <td><?= date('H:i', strtotime($v['heure_relevé'] ?? 'now')) ?></td>
                            <td><?= $v['bpm']??'—' ?></td><td><?= $v['spo2']??'—' ?></td>
                            <td><?= ($v['ta_sys']??'')!==''?($v['ta_sys'].'/'.($v['ta_dia']??'')):'—' ?></td>
                            <td><?= $v['fr']??'—' ?></td><td><?= $v['temp']??'—' ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Compte-rendu opératoire -->
        <div class="panel">
            <h6><i class="bi bi-clipboard2-check me-1"></i>Compte-rendu opératoire (CRO)</h6>
            <div class="bd cro">
                <form action="<?= BASE_URL ?>bloc/cro" method="POST" onsubmit="return confirm('Clôturer l\'acte chirurgical et envoyer le patient en salle de réveil ?');">
                    <?= CsrfService::field() ?>
                    <input type="hidden" name="programmation_id" value="<?= (int)($i['id']??0) ?>">
                    <label>Type d'intervention réalisée</label>
                    <input type="text" name="type_intervention" placeholder="ex : Appendicectomie" required>
                    <label>Compte-rendu</label>
                    <textarea name="compte_rendu" placeholder="Déroulé de l'intervention, gestes réalisés…" required></textarea>
                    <label>Complications</label>
                    <input type="text" name="complications" placeholder="Aucune / décrire">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                        <div><label>Pertes sanguines (mL)</label><input type="number" name="pertes_sanguines" placeholder="0"></div>
                        <div><label>Drainage</label><input type="text" name="drainage" placeholder="Aucun / type"></div>
                    </div>
                    <label>Heure de fin</label>
                    <input type="time" name="heure_fin" value="<?= date('H:i') ?>">
                    <button type="submit" class="btn-cro"><i class="bi bi-check-circle-fill me-1"></i>Clôturer &amp; envoyer en réveil</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
const BB='<?= BASE_URL ?>', PROG_ID=<?= (int)($i['id']??0) ?>, CSRF='<?= CsrfService::getToken() ?>';
function saveVit(){
    const g=id=>document.getElementById(id).value;
    if(!g('v_bpm')){ document.getElementById('vitMsg').innerHTML='<span style="color:#fca5a5">FC obligatoire.</span>'; return; }
    const fd=new FormData();
    fd.append('bpm',g('v_bpm')); fd.append('spo2',g('v_spo2')); fd.append('fr',g('v_fr'));
    fd.append('ta_sys',g('v_tas')); fd.append('ta_dia',g('v_tad')); fd.append('temp',g('v_temp'));
    fd.append('_csrf_token',CSRF);
    fetch(BB+'bloc/monitoring/'+PROG_ID,{method:'POST',body:fd}).then(r=>r.json()).then(j=>{
        if(j.success){
            const now=new Date().toTimeString().slice(0,5);
            const ta=(g('v_tas')&&g('v_tad'))?g('v_tas')+'/'+g('v_tad'):'—';
            const row=`<tr><td>${now}</td><td>${g('v_bpm')||'—'}</td><td>${g('v_spo2')||'—'}</td><td>${ta}</td><td>${g('v_fr')||'—'}</td><td>${g('v_temp')||'—'}</td></tr>`;
            document.getElementById('vitBody').insertAdjacentHTML('afterbegin',row);
            ['v_bpm','v_spo2','v_fr','v_tas','v_tad','v_temp'].forEach(id=>document.getElementById(id).value='');
            document.getElementById('vitMsg').innerHTML='<span style="color:#86efac">Relevé enregistré.</span>';
        } else document.getElementById('vitMsg').innerHTML='<span style="color:#fca5a5">'+(j.message||'Erreur')+'</span>';
    }).catch(()=>document.getElementById('vitMsg').innerHTML='<span style="color:#fca5a5">Erreur réseau.</span>');
}
</script>
