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

if (!defined('BASE_URL')) define('BASE_URL', '/dme_hospital/');
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../../services/CsrfService.php';
$fiche_id = (int)($fiche['id'] ?? 0);
$d = fn(string $k, string $def = '') => htmlspecialchars($data[$k] ?? $def);

$cols_time = ['T0','T+15','T+30','T+45','T+60','T+75','T+90','T+105','T+120','T+135','T+150','T+165','T+180','T+195','T+210','T+225'];
$signes = [
    'Pouls (/min)'  => 'pouls',
    'TA sys (mmHg)' => 'ta_sys',
    'TA dia (mmHg)' => 'ta_dia',
    'SaO₂ (%)'      => 'sao2',
    'T° (°C)'       => 'temp',
    'EtCO₂ (mmHg)'  => 'etco2',
];
?>
<style>
:root{--r:#dc2626;--rl:#fef2f2;--rb:#fecaca;--b:#1d4ed8;--bl:#eff6ff;--bb:#bfdbfe;--g:#16a34a;--gl:#f0fdf4;--gb:#bbf7d0;--w:#fff;--bg:#f1f5f9;--bd:#e2e8f0;--t:#0f172a;--s:#64748b;--v:#7c3aed;--vl:#faf5ff;--vb:#e9d5ff;--o:#d97706;--ol:#fffbeb;--ob:#fde68a}
*{box-sizing:border-box}
body{background:var(--bg);font-family:'Inter',system-ui,sans-serif;color:var(--t);margin:0}
.fp{max-width:1120px;margin:0 auto;padding:24px 20px 80px}
.ftop{background:var(--w);border-radius:16px;padding:18px 24px;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;box-shadow:0 2px 12px rgba(0,0,0,.06);border:1px solid var(--bd)}
.ftop-l{display:flex;align-items:center;gap:14px}
.ftop-ico{width:48px;height:48px;border-radius:14px;background:linear-gradient(135deg,#7c3aed,#4f46e5);display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.3rem}
.ftop-title{font-size:1.05rem;font-weight:800;margin:0}
.ftop-sub{font-size:.75rem;color:var(--s);margin:1px 0 0}
.ftop-r{display:flex;gap:8px;align-items:center}
.btn-sm{background:#f8fafc;border:1px solid var(--bd);color:var(--s);border-radius:10px;padding:8px 16px;font-size:.8rem;font-weight:700;cursor:pointer;text-decoration:none;display:flex;align-items:center;gap:6px;transition:.15s}
.btn-sm:hover{background:#e2e8f0;color:var(--t)}
.al{border-radius:12px;padding:12px 18px;font-size:.83rem;font-weight:600;margin-bottom:16px;display:none;align-items:center;gap:10px;border:1.5px solid}
.al.ok{background:var(--gl);border-color:var(--gb);color:var(--g)}.al.err{background:var(--rl);border-color:var(--rb);color:var(--r)}
.sec{background:var(--w);border-radius:16px;margin-bottom:16px;overflow:hidden;box-shadow:0 2px 10px rgba(0,0,0,.05);border:1px solid var(--bd)}
.sh{padding:14px 22px;border-bottom:2px solid var(--bd);display:flex;align-items:center;gap:10px}
.sn{width:28px;height:28px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:.72rem;font-weight:900;color:#fff;flex-shrink:0}
.nb{background:var(--b)}.nr{background:var(--r)}.ng{background:var(--g)}.nv{background:var(--v)}.no{background:var(--o)}.nt{background:#0f766e}
.st{font-size:.82rem;font-weight:800;text-transform:uppercase;letter-spacing:.4px}
.sb{padding:20px 22px}
/* patient banner */
.pbanner{background:linear-gradient(135deg,#7c3aed,#4f46e5);border-radius:12px;padding:16px 20px;color:#fff;display:flex;align-items:center;gap:18px;margin-bottom:18px}
.pav{width:52px;height:52px;border-radius:14px;background:rgba(255,255,255,.2);border:2px solid rgba(255,255,255,.35);display:flex;align-items:center;justify-content:center;font-size:1.2rem;font-weight:800;flex-shrink:0}
.pname{font-size:1.05rem;font-weight:800}.pmeta{font-size:.77rem;opacity:.85;margin-top:2px}
.pchips{display:flex;gap:7px;flex-wrap:wrap;margin-top:8px}
.pchip{background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.3);border-radius:50px;padding:3px 10px;font-size:.7rem;font-weight:700}
/* grid */
.fg{display:grid;gap:14px;margin-bottom:14px}
.g1{grid-template-columns:1fr}.g2{grid-template-columns:1fr 1fr}.g3{grid-template-columns:1fr 1fr 1fr}.g4{grid-template-columns:1fr 1fr 1fr 1fr}.g5{grid-template-columns:repeat(5,1fr)}
@media(max-width:700px){.g2,.g3,.g4,.g5{grid-template-columns:1fr 1fr}}
.fl label{font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.5px;color:var(--s);display:block;margin-bottom:5px}
.fl input,.fl select,.fl textarea{width:100%;padding:10px 13px;border:1.5px solid var(--bd);border-radius:10px;font-size:.88rem;color:var(--t);background:var(--w);outline:none;transition:border .15s,box-shadow .15s;font-family:inherit}
.fl input:focus,.fl select:focus,.fl textarea:focus{border-color:var(--b);box-shadow:0 0 0 3px rgba(29,78,216,.1)}
.fl textarea{resize:vertical;min-height:72px;line-height:1.5}
.fl input[readonly]{background:#f8fafc;color:#94a3b8}
/* sub-blocks */
.sub{border-radius:12px;padding:16px;border:1.5px solid;margin-bottom:12px}
.sub-b{background:var(--bl);border-color:var(--bb)}.sub-r{background:var(--rl);border-color:var(--rb)}.sub-g{background:var(--gl);border-color:var(--gb)}.sub-v{background:var(--vl);border-color:var(--vb)}.sub-o{background:var(--ol);border-color:var(--ob)}
.sub-t{font-size:.7rem;font-weight:800;text-transform:uppercase;letter-spacing:.5px;margin-bottom:12px;display:flex;align-items:center;gap:7px}
.sub-b .sub-t{color:var(--b)}.sub-r .sub-t{color:var(--r)}.sub-g .sub-t{color:var(--g)}.sub-v .sub-t{color:var(--v)}.sub-o .sub-t{color:var(--o)}
/* vitaux cards */
.vrow{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:14px}
.vc{border-radius:12px;padding:12px 14px;border:1.5px solid var(--bd);background:var(--w);text-align:center;transition:border .15s}
.vc:focus-within{border-color:var(--b);box-shadow:0 0 0 3px rgba(29,78,216,.08)}
.vc-r{border-color:var(--rb)!important}.vc-r .vlb,.vc-r .vi{color:var(--r)}
.vc-b{border-color:var(--bb)!important}.vc-b .vlb,.vc-b .vi{color:var(--b)}
.vc-g{border-color:var(--gb)!important}.vc-g .vlb,.vc-g .vi{color:var(--g)}
.vc-v{border-color:var(--vb)!important}.vc-v .vlb,.vc-v .vi{color:var(--v)}
.vlb{font-size:.62rem;font-weight:800;text-transform:uppercase;letter-spacing:.4px;color:var(--s);margin-bottom:6px}
.vi{width:100%;text-align:center;border:none;outline:none;font-size:1.2rem;font-weight:800;color:var(--t);background:transparent;font-family:inherit;padding:0}
.vun{font-size:.65rem;color:var(--s);margin-top:3px}
/* grille peropératoire */
.gop-wrap{overflow-x:auto;border-radius:12px;border:1.5px solid var(--bd)}
.gop{border-collapse:collapse;font-size:.72rem;min-width:900px;width:100%}
.gop th{background:#f8fafc;padding:8px 10px;font-weight:800;text-align:center;color:var(--s);border:1px solid var(--bd);white-space:nowrap;font-size:.68rem;text-transform:uppercase;letter-spacing:.3px}
.gop td{border:1px solid var(--bd);padding:4px}
.gop .rl{background:linear-gradient(135deg,var(--vl),#ede9fe);font-weight:800;font-size:.72rem;color:var(--v);padding:8px 12px;white-space:nowrap;min-width:130px}
.gop input{width:100%;border:none;background:transparent;text-align:center;font-size:.78rem;font-weight:700;color:var(--t);outline:none;padding:4px;font-family:inherit}
.gop input:focus{background:var(--bl);border-radius:6px}
.gop .th-time input{width:54px;border:none;background:transparent;text-align:center;font-size:.7rem;font-weight:800;color:var(--s);outline:none;font-family:inherit}
/* Aldrete */
.ald-row{display:flex;gap:10px;flex-wrap:wrap}
.ald-item{flex:1;min-width:100px;background:var(--w);border:1.5px solid var(--bd);border-radius:12px;padding:12px;text-align:center}
.ald-item label{font-size:.65rem;font-weight:800;text-transform:uppercase;letter-spacing:.3px;color:var(--s);display:block;margin-bottom:8px}
.ald-sel{width:100%;border:1.5px solid var(--bd);border-radius:8px;padding:7px;font-size:.88rem;font-weight:800;text-align:center;outline:none;cursor:pointer}
.ald-sel:focus{border-color:var(--b)}
.ald-total{background:linear-gradient(135deg,var(--b),#6366f1);color:#fff;border-radius:12px;padding:12px;text-align:center;font-size:2rem;font-weight:900;min-width:80px}
/* footer */
.ffooter{display:flex;justify-content:space-between;align-items:center;background:var(--w);border-radius:16px;padding:16px 24px;margin-top:20px;box-shadow:0 2px 10px rgba(0,0,0,.05);border:1px solid var(--bd);position:sticky;bottom:16px;z-index:50}
.btn-draft{background:var(--vl);color:var(--v);border:2px solid var(--vb);border-radius:12px;padding:11px 22px;font-size:.88rem;font-weight:800;cursor:pointer;display:flex;align-items:center;gap:8px;transition:.15s}
.btn-draft:hover{background:var(--v);color:#fff}
.btn-final{background:linear-gradient(135deg,var(--g),#059669);color:#fff;border:none;border-radius:12px;padding:11px 26px;font-size:.88rem;font-weight:800;cursor:pointer;display:flex;align-items:center;gap:8px;box-shadow:0 4px 14px rgba(22,163,74,.3);transition:.15s}
.btn-final:hover{transform:translateY(-1px)}
.fin-badge{background:var(--gl);color:var(--g);border:1px solid var(--gb);border-radius:50px;padding:5px 14px;font-size:.75rem;font-weight:800;display:flex;align-items:center;gap:6px}
.spin{display:none;width:16px;height:16px;border:2px solid rgba(0,0,0,.1);border-top-color:var(--v);border-radius:50%;animation:sp .6s linear infinite;position:absolute;right:12px;top:50%;transform:translateY(-50%)}
@keyframes sp{to{transform:translateY(-50%) rotate(360deg)}}
@media print{.np{display:none!important}body{background:#fff}.ffooter{position:static}}
</style>

<div class="fp">
  <!-- Topbar -->
  <div class="ftop np">
    <div class="ftop-l">
      <div class="ftop-ico"><i class="bi bi-activity"></i></div>
      <div>
        <div class="ftop-title">Phase Peropératoire — Anesthésie</div>
        <div class="ftop-sub">HSJM · Surveillance et gestion intraopératoire</div>
      </div>
    </div>
    <div class="ftop-r">
      <?php if($fiche&&$fiche['statut']==='finalise'): ?><span class="fin-badge"><i class="bi bi-check-circle-fill"></i>Finalisée</span><?php endif ?>
      <button class="btn-sm np" onclick="window.print()"><i class="bi bi-printer"></i>Imprimer</button>
      <a href="<?= BASE_URL ?>dashboard" class="btn-sm"><i class="bi bi-arrow-left"></i>Retour</a>
    </div>
  </div>

  <div id="al" class="al"><i class="bi" id="alIco"></i><span id="alTxt"></span></div>

  <form id="ff">
    <?= CsrfService::field() ?>
    <input type="hidden" name="fiche_id"   value="<?= $fiche_id ?>">
    <input type="hidden" name="patient_id" id="hpid" value="<?= $patient_id ?>">

    <!-- ══ I. IDENTIFICATION ══ -->
    <div class="sec">
      <div class="sh"><div class="sn nv">I</div><div class="st">Identification du patient & équipe</div></div>
      <div class="sb">
        <div class="fg g1" style="margin-bottom:18px">
          <div class="fl">
            <label><i class="bi bi-search me-1"></i>Sélectionner un patient *</label>
            <div style="position:relative">
              <select id="sp" onchange="loadPatient(this.value)"
                style="border-color:var(--v);background:var(--vl);font-weight:700;padding-right:40px">
                <option value="">— Choisir un patient —</option>
                <?php foreach($patients as $p): ?>
                <option value="<?= $p['id'] ?>" <?= $patient_id==$p['id']?'selected':'' ?>>
                  <?= htmlspecialchars($p['nom'].' '.$p['prenom']) ?> · <?= htmlspecialchars($p['dossier_numero']) ?>
                </option>
                <?php endforeach ?>
              </select>
              <div class="spin" id="spin"></div>
            </div>
          </div>
        </div>

        <!-- Banner -->
        <div id="pb" class="pbanner" style="<?= $patient?'':'display:none' ?>">
          <div class="pav" id="pav"><?= $patient?strtoupper(substr($patient['nom'],0,1).substr($patient['prenom'],0,1)):'' ?></div>
          <div style="flex:1">
            <div class="pname" id="pnm"><?= $patient?htmlspecialchars($patient['nom'].' '.$patient['prenom']):'' ?></div>
            <div class="pmeta" id="pmt"></div>
            <div class="pchips" id="pch"></div>
          </div>
        </div>

        <div class="fg g5">
          <div class="fl"><label>Âge</label><input type="text" name="age" id="fa" value="<?= $d('age') ?>" placeholder="35 ans"></div>
          <div class="fl"><label>Poids (kg)</label><input type="text" name="poids" id="fpo" value="<?= $d('poids') ?>"></div>
          <div class="fl"><label>Taille (cm)</label><input type="text" name="taille" id="fta" value="<?= $d('taille') ?>"></div>
          <div class="fl"><label>Groupe sanguin</label><input type="text" name="gs" id="fgs" value="<?= $d('gs') ?>"></div>
          <div class="fl"><label>Score ASA</label>
            <select name="asa" id="fasa">
              <option value="">—</option>
              <?php foreach(['I','II','III','IV','V','VI'] as $n): ?>
              <option value="<?=$n?>" <?=($data['asa']??'')===$n?'selected':''?>>ASA <?=$n?></option>
              <?php endforeach ?>
            </select>
          </div>
        </div>
        <div class="fg g4">
          <div class="fl"><label>Chirurgien principal</label><input type="text" name="chirurgien" id="fch" value="<?= $d('chirurgien') ?>"></div>
          <div class="fl"><label>Chirurgien aide</label><input type="text" name="chirurgien_aide" value="<?= $d('chirurgien_aide') ?>"></div>
          <div class="fl"><label>Anesthésiste</label><input type="text" name="anesthesiste_nom" value="<?= $d('anesthesiste_nom',trim(($_SESSION['user_prenom']??'').' '.($_SESSION['user_nom']??''))) ?>"></div>
          <div class="fl"><label>Salle d'opération</label><input type="text" name="salle" id="fsalle" value="<?= $d('salle') ?>"></div>
        </div>
        <div class="fg g3">
          <div class="fl"><label>Diagnostic opératoire</label><input type="text" name="diagnostic" id="fdiag" value="<?= $d('diagnostic') ?>"></div>
          <div class="fl"><label>Acte chirurgical</label><input type="text" name="acte" id="facte" value="<?= $d('acte') ?>"></div>
          <div class="fl"><label>Type d'anesthésie</label>
            <select name="type_anesth">
              <option value="">—</option>
              <?php foreach(['AG — Intubation oro-trachéale','AG — Masque laryngé','AG — Masque facial','ALR — Rachianesthésie','ALR — Péridurale','ALR — Bloc nerveux','Sédation / ALIV'] as $t): ?>
              <option value="<?=$t?>" <?=($data['type_anesth']??'')===$t?'selected':''?>><?=$t?></option>
              <?php endforeach ?>
            </select>
          </div>
        </div>
        <div class="fg g4">
          <div class="fl"><label>Date intervention</label><input type="date" name="date_intervention" value="<?= $d('date_intervention',date('Y-m-d')) ?>"></div>
          <div class="fl"><label>Début chirurgie</label><input type="time" name="heure_debut_chir" value="<?= $d('heure_debut_chir') ?>"></div>
          <div class="fl"><label>Fin chirurgie</label><input type="time" name="heure_fin_chir" value="<?= $d('heure_fin_chir') ?>"></div>
          <div class="fl"><label>Durée chirurgie</label><input type="text" name="duree_chir" value="<?= $d('duree_chir') ?>" placeholder="ex: 1h30"></div>
        </div>
        <div class="fg g4">
          <div class="fl"><label>Début anesthésie</label><input type="time" name="heure_debut_anesth" value="<?= $d('heure_debut_anesth') ?>"></div>
          <div class="fl"><label>Fin anesthésie</label><input type="time" name="heure_fin_anesth" value="<?= $d('heure_fin_anesth') ?>"></div>
          <div class="fl"><label>Durée anesthésie</label><input type="text" name="duree_anesth" value="<?= $d('duree_anesth') ?>" placeholder="ex: 2h00"></div>
          <div class="fl"><label>Score CORMACK</label>
            <select name="cormack">
              <option value="">—</option>
              <?php foreach(['I','IIa','IIb','III','IV'] as $n): ?>
              <option value="<?=$n?>" <?=($data['cormack']??'')===$n?'selected':''?>><?=$n?></option>
              <?php endforeach ?>
            </select>
          </div>
        </div>
      </div>
    </div>

    <!-- ══ II. GRILLE PEROPÉRATOIRE ══ -->
    <div class="sec">
      <div class="sh"><div class="sn nr">II</div><div class="st">Surveillance des constantes peropératoires</div></div>
      <div class="sb">
        <p style="font-size:.75rem;color:var(--s);margin:0 0 12px">Saisir les valeurs relevées — modifier les en-têtes de colonne pour ajuster les horaires réels.</p>
        <div class="gop-wrap">
          <table class="gop">
            <thead>
              <tr>
                <th style="min-width:135px;background:linear-gradient(135deg,var(--vl),#ede9fe);color:var(--v)">Constante vitale</th>
                <?php foreach($cols_time as $i=>$t): ?>
                <th class="th-time"><input type="text" name="time_col_<?=$i?>" value="<?= htmlspecialchars($data['time_col_'.$i]??$t) ?>"></th>
                <?php endforeach ?>
              </tr>
            </thead>
            <tbody>
              <?php foreach($signes as $label=>$key): ?>
              <tr>
                <td class="rl"><?=$label?></td>
                <?php foreach($cols_time as $i=>$_): ?>
                <td><input type="text" name="vital_<?=$key?>_<?=$i?>" value="<?= htmlspecialchars($data['vital_'.$key.'_'.$i]??'') ?>"></td>
                <?php endforeach ?>
              </tr>
              <?php endforeach ?>
              <?php for($r=1;$r<=3;$r++): ?>
              <tr>
                <td class="rl" style="background:var(--gl)">
                  <input type="text" name="vital_custom_label_<?=$r?>" placeholder="Autre paramètre…" value="<?= $d('vital_custom_label_'.$r) ?>" style="width:110px;background:transparent;border:none;font-size:.72rem;color:var(--g);font-weight:800">
                </td>
                <?php foreach($cols_time as $i=>$_): ?>
                <td><input type="text" name="vital_custom_<?=$r?>_<?=$i?>" value="<?= htmlspecialchars($data['vital_custom_'.$r.'_'.$i]??'') ?>"></td>
                <?php endforeach ?>
              </tr>
              <?php endfor ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ══ III. GESTION INTRAOPÉRATOIRE ══ -->
    <div class="sec">
      <div class="sh"><div class="sn nb">III</div><div class="st">Gestion intraopératoire</div></div>
      <div class="sb">
        <div class="fg g2">
          <div>
            <div class="sub sub-v">
              <div class="sub-t"><i class="bi bi-capsule-pill"></i> Induction & entretien</div>
              <div class="fl" style="margin-bottom:10px"><label>Prémédication</label><textarea name="premedication" rows="2" placeholder="Ex: Midazolam 5mg IV"><?= $d('premedication') ?></textarea></div>
              <div class="fl" style="margin-bottom:10px"><label>Induction</label><textarea name="induction" rows="2" placeholder="Ex: Propofol 150mg + Fentanyl 100µg"><?= $d('induction') ?></textarea></div>
              <div class="fl" style="margin-bottom:10px"><label>Halogénés / Entretien</label><input type="text" name="halogenes" value="<?= $d('halogenes') ?>" placeholder="Ex: Sevoflurane 2%"></div>
              <div class="fl" style="margin-bottom:10px"><label>Curares</label><input type="text" name="curare" value="<?= $d('curare') ?>" placeholder="Ex: Succinylcholine 100mg"></div>
              <div class="fl"><label>Antibioprophylaxie</label><input type="text" name="antibio" value="<?= $d('antibio') ?>" placeholder="Ex: Céfazoline 2g IV"></div>
            </div>
          </div>
          <div>
            <div class="sub sub-b">
              <div class="sub-t"><i class="bi bi-wind"></i> Ventilation</div>
              <div class="fg g2">
                <div class="fl"><label>Mode</label><input type="text" name="ventilation" value="<?= $d('ventilation') ?>" placeholder="IPPV, SIMV…"></div>
                <div class="fl"><label>VT (ml)</label><input type="text" name="vt" value="<?= $d('vt') ?>" placeholder="450"></div>
              </div>
              <div class="fg g3">
                <div class="fl"><label>FR (/min)</label><input type="text" name="fr_vent" value="<?= $d('fr_vent') ?>" placeholder="14"></div>
                <div class="fl"><label>FiO₂ (%)</label><input type="text" name="fio2" value="<?= $d('fio2') ?>" placeholder="50"></div>
                <div class="fl"><label>PEEP (cmH₂O)</label><input type="text" name="peep" value="<?= $d('peep') ?>" placeholder="5"></div>
              </div>
            </div>
            <div class="sub sub-b" style="margin-top:0">
              <div class="sub-t"><i class="bi bi-arrow-left-right"></i> Voies veineuses & Cathéters</div>
              <div class="fl"><textarea name="voies_veineuses" rows="2" placeholder="VVP G18 MS, KTC sous-clavier droit…"><?= $d('voies_veineuses') ?></textarea></div>
            </div>
          </div>
        </div>

        <!-- Fluides -->
        <div class="sub sub-g">
          <div class="sub-t"><i class="bi bi-droplet-fill"></i> Solutés administrés</div>
          <div class="fg g4">
            <div class="fl"><label>SS 0,9% (ml)</label><input type="text" name="ss09" value="<?= $d('ss09') ?>"></div>
            <div class="fl"><label>Ringer Lactate (ml)</label><input type="text" name="ringer" value="<?= $d('ringer') ?>"></div>
            <div class="fl"><label>G5% (ml)</label><input type="text" name="g5" value="<?= $d('g5') ?>"></div>
            <div class="fl"><label>Colloïde (ml)</label><input type="text" name="colloide" value="<?= $d('colloide') ?>"></div>
          </div>
        </div>

        <!-- Sang -->
        <div class="sub sub-r">
          <div class="sub-t"><i class="bi bi-droplet-half"></i> Produits sanguins & pertes</div>
          <div class="fg g4">
            <div class="fl"><label>CGR (unités)</label><input type="text" name="cgr" value="<?= $d('cgr') ?>"></div>
            <div class="fl"><label>PFC (unités)</label><input type="text" name="pfc" value="<?= $d('pfc') ?>"></div>
            <div class="fl"><label>CPA (unités)</label><input type="text" name="cpa" value="<?= $d('cpa') ?>"></div>
            <div class="fl"><label>Pertes sanguines (ml)</label><input type="text" name="pertes_sang" value="<?= $d('pertes_sang') ?>"></div>
          </div>
        </div>

        <div class="fg g2">
          <div class="fl"><label>Diurèse totale (ml)</label><input type="text" name="diurese" value="<?= $d('diurese') ?>"></div>
          <div class="fl"><label>Drains posés</label><input type="text" name="drains" value="<?= $d('drains') ?>" placeholder="Type, localisation…"></div>
        </div>
      </div>
    </div>

    <!-- ══ IV. INCIDENTS & SSPI ══ -->
    <div class="sec">
      <div class="sh"><div class="sn ng">IV</div><div class="st">Incidents, Accidents & Transfert SSPI</div></div>
      <div class="sb">
        <div class="fg g2">
          <div>
            <div class="sub sub-r">
              <div class="sub-t"><i class="bi bi-exclamation-triangle-fill"></i> Incidents / Accidents peropératoires</div>
              <div class="fl"><textarea name="incidents" rows="5" placeholder="Laryngospasme, hypotension réfractaire, allergie peropératoire, saignement excessif… (laisser vide si RAS)"><?= $d('incidents') ?></textarea></div>
            </div>
          </div>
          <div>
            <div class="sub sub-b" style="margin-bottom:12px">
              <div class="sub-t"><i class="bi bi-heart-pulse-fill"></i> Score d'Aldrete (SSPI)</div>
              <div class="ald-row">
                <?php foreach(['activite'=>'Activité','respiration'=>'Respiration','conscience'=>'Conscience','coloration'=>'Coloration','circulation'=>'Circulation'] as $k=>$lbl): ?>
                <div class="ald-item">
                  <label><?=$lbl?></label>
                  <select class="ald-sel" name="aldrete_<?=$k?>" onchange="calcAldrete()">
                    <option value="">—</option>
                    <option value="0" <?=($data['aldrete_'.$k]??'')==='0'?'selected':''?>>0</option>
                    <option value="1" <?=($data['aldrete_'.$k]??'')==='1'?'selected':''?>>1</option>
                    <option value="2" <?=($data['aldrete_'.$k]??'')==='2'?'selected':''?>>2</option>
                  </select>
                </div>
                <?php endforeach ?>
                <div class="ald-total">
                  <div style="font-size:.62rem;font-weight:700;opacity:.7;text-transform:uppercase;margin-bottom:4px">Total</div>
                  <div id="aldTot"><?= $d('aldrete_total','0') ?></div>
                  <input type="hidden" name="aldrete_total" id="aldTotH" value="<?= $d('aldrete_total') ?>">
                  <div style="font-size:.65rem;opacity:.7">/10</div>
                </div>
              </div>
            </div>
            <div class="sub sub-g">
              <div class="sub-t"><i class="bi bi-arrow-right-circle-fill"></i> Transfert SSPI</div>
              <div class="fl" style="margin-bottom:10px"><label>Destination de transfert</label><input type="text" name="transfert_sspi" value="<?= $d('transfert_sspi') ?>" placeholder="Ex: SSPI, Réanimation, Service chirurgie"></div>
              <div class="fg g2">
                <div class="fl"><label>Heure entrée SSPI</label><input type="time" name="heure_entree_sspi" value="<?= $d('heure_entree_sspi') ?>"></div>
                <div class="fl"><label>Heure sortie SSPI</label><input type="time" name="heure_sortie_sspi" value="<?= $d('heure_sortie_sspi') ?>"></div>
              </div>
            </div>
          </div>
        </div>
        <div class="fl"><label>Observations & remarques finales</label><textarea name="observations" rows="3" placeholder="Compte rendu d'anesthésie, consignes post-op…"><?= $d('observations') ?></textarea></div>
      </div>
    </div>

    <!-- Footer -->
    <div class="ffooter np">
      <button type="button" class="btn-sm" onclick="window.print()"><i class="bi bi-printer"></i>Imprimer</button>
      <div style="display:flex;gap:10px">
        <button type="button" class="btn-draft" onclick="save('brouillon')"><i class="bi bi-save2"></i>Brouillon</button>
        <button type="button" class="btn-final" onclick="save('finalise')"><i class="bi bi-check-circle-fill"></i>Finaliser</button>
      </div>
    </div>
  </form>
</div>

<script>
const B='<?= BASE_URL ?>';

async function loadPatient(id){
  if(!id)return;
  document.getElementById('hpid').value=id;
  document.getElementById('spin').style.display='block';
  try{
    const res=await(await fetch(`${B}anesthesie/patient-json/${id}`)).json();
    if(!res.success)return;
    const p=res.patient,i=res.intervention;
    sf('fa',  p.age?`${p.age} ans`:'');
    sf('fpo', p.poids||'');
    sf('fgs', p.groupe_sanguin||'');
    if(i){
      sf('fch',   i.chirurgien_nom?`Dr. ${i.chirurgien_prenom||''} ${i.chirurgien_nom}`.trim():'');
      sf('fdiag', i.type_intervention||'');
      sf('facte', i.type_intervention||'');
      sf('fsalle',i.nom_salle||'');
    }
    // banner
    document.getElementById('pb').style.display='flex';
    document.getElementById('pav').textContent=(p.nom.charAt(0)+p.prenom.charAt(0)).toUpperCase();
    document.getElementById('pnm').textContent=`${p.nom} ${p.prenom}`;
    document.getElementById('pmt').textContent=[p.age?`${p.age} ans`:'',p.sexe==='M'?'Masculin':'Féminin',p.dossier_numero].filter(Boolean).join(' · ');
    const chips=[];
    if(p.groupe_sanguin)chips.push(`🩸 ${p.groupe_sanguin}`);
    if(p.allergies)chips.push(`⚠️ ${p.allergies.substring(0,28)}`);
    if(i)chips.push(`🔪 ${(i.type_intervention||'').substring(0,24)}`);
    document.getElementById('pch').innerHTML=chips.map(c=>`<span class="pchip">${c}</span>`).join('');
  }finally{document.getElementById('spin').style.display='none';}
}

function sf(id,val){const e=document.getElementById(id);if(e&&!e.value&&val)e.value=val;}

function calcAldrete(){
  const keys=['activite','respiration','conscience','coloration','circulation'];
  let t=0;
  keys.forEach(k=>{const v=parseInt(document.querySelector(`[name="aldrete_${k}"]`)?.value||'0');if(!isNaN(v))t+=v;});
  document.getElementById('aldTot').textContent=t;
  document.getElementById('aldTotH').value=t;
}

async function save(st){
  if(!document.getElementById('hpid').value){alert2('Veuillez sélectionner un patient.',false);return;}
  const fd=new FormData(document.getElementById('ff'));fd.set('statut',st);
  try{
    const res=await(await fetch(`${B}anesthesie/sauvegarder-per-operatoire`,{method:'POST',body:fd})).json();
    if(res.success&&res.fiche_id)document.querySelector('[name=fiche_id]').value=res.fiche_id;
    alert2(res.message,res.success);
  }catch{alert2('Erreur réseau.',false);}
}

function alert2(msg,ok){
  const el=document.getElementById('al');
  el.className=`al ${ok?'ok':'err'}`;el.style.display='flex';
  document.getElementById('alIco').className=`bi ${ok?'bi-check-circle-fill':'bi-exclamation-circle-fill'}`;
  document.getElementById('alTxt').textContent=msg;
  el.scrollIntoView({behavior:'smooth',block:'nearest'});
  setTimeout(()=>el.style.display='none',4000);
}

<?php if($patient_id&&!$fiche_id): ?>
window.addEventListener('DOMContentLoaded',()=>loadPatient(<?=$patient_id?>));
<?php elseif($patient): ?>
window.addEventListener('DOMContentLoaded',()=>{
  document.getElementById('pb').style.display='flex';
  document.getElementById('pav').textContent='<?=strtoupper(substr($patient['nom'],0,1).substr($patient['prenom'],0,1))?>';
  document.getElementById('pnm').textContent='<?=htmlspecialchars($patient['nom'].' '.$patient['prenom'],ENT_JS)?>';
});
<?php endif ?>
</script>
<?php require_once __DIR__.'/../layouts/footer.php'; ?>
