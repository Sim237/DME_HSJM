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

$jours = ['J0','J1','J2','J3','J4','J5','J6'];
$lignes = [
    ['label'=>'Personnel soignant',      'key'=>'personnel',       'cat'=>'info'],
    ['label'=>'Liquides entrée (ml)',     'key'=>'liq_entree',      'cat'=>'bleu'],
    ['label'=>'Liquides sortie (ml)',     'key'=>'liq_sortie',      'cat'=>'bleu'],
    ['label'=>'Transfusion',             'key'=>'transfusion',      'cat'=>'rouge'],
    ['label'=>'Analgésie',               'key'=>'analgesie',        'cat'=>'vert'],
    ['label'=>'Prévention MTE / UDS',    'key'=>'prev_mte',         'cat'=>'vert'],
    ['label'=>'SaO₂ (%)',                'key'=>'sao2',             'cat'=>'bleu'],
    ['label'=>'O₂ (l/min)',              'key'=>'o2',               'cat'=>'bleu'],
    ['label'=>'FR (/min)',               'key'=>'fr',               'cat'=>'bleu'],
    ['label'=>'Pouls (/min)',            'key'=>'pouls',            'cat'=>'rouge'],
    ['label'=>'TA (mmHg)',               'key'=>'ta',               'cat'=>'rouge'],
    ['label'=>'Diurèse (ml)',            'key'=>'diurese',          'cat'=>'vert'],
    ['label'=>'Drains (ml)',             'key'=>'drains',           'cat'=>'vert'],
    ['label'=>'SNG (ml)',               'key'=>'sng',              'cat'=>'vert'],
    ['label'=>'Conscience (score)',      'key'=>'conscience',       'cat'=>'violet'],
    ['label'=>'Douleur (EVA /10)',       'key'=>'douleur',          'cat'=>'rouge'],
    ['label'=>'Nausées / Vomissements', 'key'=>'nv',               'cat'=>'orange'],
    ['label'=>'Prurit',                  'key'=>'prurit',           'cat'=>'orange'],
    ['label'=>'T°C',                     'key'=>'temperature',      'cat'=>'rouge'],
    ['label'=>'Glycémie',               'key'=>'glycemie',         'cat'=>'orange'],
    ['label'=>'Cathéters',              'key'=>'catheters',        'cat'=>'info'],
    ['label'=>'Vasopresseurs',           'key'=>'vasopresseurs',    'cat'=>'rouge'],
    ['label'=>'Antibiotiques',           'key'=>'antibiotiques',    'cat'=>'vert'],
    ['label'=>'Examens demandés',        'key'=>'examens',          'cat'=>'info'],
];
$catStyle = [
    'rouge'  => 'background:#fef2f2;color:#dc2626',
    'bleu'   => 'background:#eff6ff;color:#1d4ed8',
    'vert'   => 'background:#f0fdf4;color:#16a34a',
    'violet' => 'background:#faf5ff;color:#7c3aed',
    'orange' => 'background:#fffbeb;color:#d97706',
    'info'   => 'background:#f8fafc;color:#475569',
];
?>
<style>
:root{--r:#dc2626;--rl:#fef2f2;--rb:#fecaca;--b:#1d4ed8;--bl:#eff6ff;--bb:#bfdbfe;--g:#16a34a;--gl:#f0fdf4;--gb:#bbf7d0;--w:#fff;--bg:#f1f5f9;--bd:#e2e8f0;--t:#0f172a;--s:#64748b;--v:#7c3aed;--vl:#faf5ff;--vb:#e9d5ff;--o:#d97706;--ol:#fffbeb;--ob:#fde68a;--tl:#0f766e}
*{box-sizing:border-box}
body{background:var(--bg);font-family:'Inter',system-ui,sans-serif;color:var(--t);margin:0}
.fp{max-width:1120px;margin:0 auto;padding:24px 20px 80px}
.ftop{background:var(--w);border-radius:16px;padding:18px 24px;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;box-shadow:0 2px 12px rgba(0,0,0,.06);border:1px solid var(--bd)}
.ftop-l{display:flex;align-items:center;gap:14px}
.ftop-ico{width:48px;height:48px;border-radius:14px;background:linear-gradient(135deg,#0f766e,#059669);display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.3rem}
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
.nb{background:var(--b)}.nr{background:var(--r)}.ng{background:var(--g)}.nv{background:var(--v)}.nt{background:var(--tl)}
.st{font-size:.82rem;font-weight:800;text-transform:uppercase;letter-spacing:.4px}
.sb{padding:20px 22px}
.pbanner{background:linear-gradient(135deg,#0f766e,#059669);border-radius:12px;padding:16px 20px;color:#fff;display:flex;align-items:center;gap:18px;margin-bottom:18px}
.pav{width:52px;height:52px;border-radius:14px;background:rgba(255,255,255,.2);border:2px solid rgba(255,255,255,.35);display:flex;align-items:center;justify-content:center;font-size:1.2rem;font-weight:800;flex-shrink:0}
.pname{font-size:1.05rem;font-weight:800}.pmeta{font-size:.77rem;opacity:.85;margin-top:2px}
.pchips{display:flex;gap:7px;flex-wrap:wrap;margin-top:8px}
.pchip{background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.3);border-radius:50px;padding:3px 10px;font-size:.7rem;font-weight:700}
.fg{display:grid;gap:14px;margin-bottom:14px}
.g1{grid-template-columns:1fr}.g2{grid-template-columns:1fr 1fr}.g3{grid-template-columns:1fr 1fr 1fr}.g4{grid-template-columns:1fr 1fr 1fr 1fr}
@media(max-width:700px){.g2,.g3,.g4{grid-template-columns:1fr 1fr}}
.fl label{font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.5px;color:var(--s);display:block;margin-bottom:5px}
.fl input,.fl select,.fl textarea{width:100%;padding:10px 13px;border:1.5px solid var(--bd);border-radius:10px;font-size:.88rem;color:var(--t);background:var(--w);outline:none;transition:border .15s,box-shadow .15s;font-family:inherit}
.fl input:focus,.fl select:focus,.fl textarea:focus{border-color:var(--g);box-shadow:0 0 0 3px rgba(22,163,74,.1)}
.fl textarea{resize:vertical;min-height:72px;line-height:1.5}
.sub{border-radius:12px;padding:16px;border:1.5px solid;margin-bottom:0}
.sub-b{background:var(--bl);border-color:var(--bb)}.sub-r{background:var(--rl);border-color:var(--rb)}.sub-g{background:var(--gl);border-color:var(--gb)}.sub-v{background:var(--vl);border-color:var(--vb)}
.sub-t{font-size:.7rem;font-weight:800;text-transform:uppercase;letter-spacing:.5px;margin-bottom:12px;display:flex;align-items:center;gap:7px}
.sub-b .sub-t{color:var(--b)}.sub-r .sub-t{color:var(--r)}.sub-g .sub-t{color:var(--g)}.sub-v .sub-t{color:var(--v)}
/* bilan cards */
.bilan-card{border-radius:14px;padding:18px;border:1.5px solid var(--bd);background:var(--w)}
.bilan-title{font-size:.72rem;font-weight:800;text-transform:uppercase;letter-spacing:.5px;margin-bottom:14px;display:flex;align-items:center;gap:6px}
.bc-entree .bilan-title{color:var(--b)}.bc-sortie .bilan-title{color:var(--tl)}.bc-sejour .bilan-title{color:var(--v)}
.bc-entree{border-color:var(--bb);background:var(--bl)}.bc-sortie{border-color:var(--gb);background:var(--gl)}.bc-sejour{border-color:var(--vb);background:var(--vl)}
/* grille J0-J6 */
.grid-wrap{overflow-x:auto;border-radius:12px;border:1.5px solid var(--bd)}
.grid-tbl{border-collapse:collapse;font-size:.72rem;min-width:700px;width:100%}
.grid-tbl th{padding:9px 14px;font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.3px;color:var(--s);border:1px solid var(--bd);text-align:center;white-space:nowrap}
.grid-tbl td{border:1px solid var(--bd);padding:0}
.grid-tbl .rl{padding:8px 14px;font-weight:700;font-size:.73rem;white-space:nowrap;min-width:170px}
.grid-tbl input{width:100%;border:none;background:transparent;text-align:center;font-size:.78rem;font-weight:600;color:var(--t);outline:none;padding:7px 4px;font-family:inherit}
.grid-tbl input:focus{background:#f0fdf4}
.grid-tbl th.jh{background:linear-gradient(135deg,#f0fdf4,#dcfce7);color:var(--g);font-size:.8rem}
/* footer */
.ffooter{display:flex;justify-content:space-between;align-items:center;background:var(--w);border-radius:16px;padding:16px 24px;margin-top:20px;box-shadow:0 2px 10px rgba(0,0,0,.05);border:1px solid var(--bd);position:sticky;bottom:16px;z-index:50}
.btn-draft{background:var(--gl);color:var(--g);border:2px solid var(--gb);border-radius:12px;padding:11px 22px;font-size:.88rem;font-weight:800;cursor:pointer;display:flex;align-items:center;gap:8px;transition:.15s}
.btn-draft:hover{background:var(--g);color:#fff}
.btn-final{background:linear-gradient(135deg,var(--tl),#059669);color:#fff;border:none;border-radius:12px;padding:11px 26px;font-size:.88rem;font-weight:800;cursor:pointer;display:flex;align-items:center;gap:8px;box-shadow:0 4px 14px rgba(15,118,110,.3);transition:.15s}
.btn-final:hover{transform:translateY(-1px)}
.fin-badge{background:var(--gl);color:var(--g);border:1px solid var(--gb);border-radius:50px;padding:5px 14px;font-size:.75rem;font-weight:800;display:flex;align-items:center;gap:6px}
.spin{display:none;width:16px;height:16px;border:2px solid rgba(0,0,0,.1);border-top-color:var(--g);border-radius:50%;animation:sp .6s linear infinite;position:absolute;right:12px;top:50%;transform:translateY(-50%)}
@keyframes sp{to{transform:translateY(-50%) rotate(360deg)}}
@media print{.np{display:none!important}body{background:#fff}.ffooter{position:static}}
</style>

<div class="fp">
  <div class="ftop np">
    <div class="ftop-l">
      <div class="ftop-ico"><i class="bi bi-bandaid-fill"></i></div>
      <div>
        <div class="ftop-title">Soins & Surveillance Post-opératoire</div>
        <div class="ftop-sub">HSJM · Suivi post-anesthésique — SSPI & unité de soins</div>
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
      <div class="sh"><div class="sn ng">I</div><div class="st">Identification du patient</div></div>
      <div class="sb">
        <div class="fg g1" style="margin-bottom:18px">
          <div class="fl">
            <label><i class="bi bi-search me-1"></i>Sélectionner un patient *</label>
            <div style="position:relative">
              <select id="sp" onchange="loadPatient(this.value)"
                style="border-color:var(--g);background:var(--gl);font-weight:700;padding-right:40px">
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

        <div id="pb" class="pbanner" style="<?= $patient?'':'display:none' ?>">
          <div class="pav" id="pav"><?= $patient?strtoupper(substr($patient['nom'],0,1).substr($patient['prenom'],0,1)):'' ?></div>
          <div style="flex:1">
            <div class="pname" id="pnm"><?= $patient?htmlspecialchars($patient['nom'].' '.$patient['prenom']):'' ?></div>
            <div class="pmeta" id="pmt"></div>
            <div class="pchips" id="pch"></div>
          </div>
        </div>

        <div class="fg g4">
          <div class="fl"><label>Âge</label><input type="text" name="age" id="fa" value="<?= $d('age') ?>" placeholder="35 ans"></div>
          <div class="fl"><label>Sexe</label>
            <select name="sexe" id="fsx">
              <option value="">—</option>
              <option value="M" <?=($data['sexe']??'')==='M'?'selected':''?>>Masculin</option>
              <option value="F" <?=($data['sexe']??'')==='F'?'selected':''?>>Féminin</option>
            </select>
          </div>
          <div class="fl"><label>Poids (kg)</label><input type="text" name="poids" id="fpo" value="<?= $d('poids') ?>"></div>
          <div class="fl"><label>Provenance</label>
            <select name="provenance">
              <option value="">—</option>
              <?php foreach(['Bloc opératoire','Réanimation','SSPI','Urgences','Service','Consultation externe'] as $pv): ?>
              <option value="<?=$pv?>" <?=($data['provenance']??'')===$pv?'selected':''?>><?=$pv?></option>
              <?php endforeach ?>
            </select>
          </div>
        </div>
        <div class="fg g3">
          <div class="fl"><label>Diagnostic interventionnel</label><input type="text" name="diagnostic_intervention" id="fdi" value="<?= $d('diagnostic_intervention') ?>"></div>
          <div class="fl"><label>Diagnostic post-op</label><input type="text" name="diagnostic_postop" value="<?= $d('diagnostic_postop') ?>"></div>
          <div class="fl"><label>Type d'anesthésie reçue</label><input type="text" name="type_anesth" value="<?= $d('type_anesth') ?>" placeholder="AG intubation, rachianesthésie…"></div>
        </div>
        <div class="fg g4">
          <div class="fl"><label>Date entrée</label><input type="date" name="date_entree" value="<?= $d('date_entree',date('Y-m-d')) ?>"></div>
          <div class="fl"><label>Heure entrée</label><input type="time" name="heure_entree" value="<?= $d('heure_entree') ?>"></div>
          <div class="fl"><label>Date sortie</label><input type="date" name="date_sortie" value="<?= $d('date_sortie') ?>"></div>
          <div class="fl"><label>Heure sortie</label><input type="time" name="heure_sortie" value="<?= $d('heure_sortie') ?>"></div>
        </div>
      </div>
    </div>

    <!-- ══ II. BILANS ══ -->
    <div class="sec">
      <div class="sh"><div class="sn nb">II</div><div class="st">Bilan à l'entrée · à la sortie · du séjour</div></div>
      <div class="sb">
        <div class="fg g3">
          <!-- Entrée -->
          <div class="bilan-card bc-entree">
            <div class="bilan-title"><i class="bi bi-arrow-down-circle-fill"></i> À l'entrée SSPI</div>
            <div class="fg g2">
              <div class="fl"><label>TA</label><input type="text" name="be_ta" value="<?= $d('be_ta') ?>" placeholder="120/80"></div>
              <div class="fl"><label>Pouls</label><input type="text" name="be_pouls" value="<?= $d('be_pouls') ?>" placeholder="75"></div>
            </div>
            <div class="fg g2">
              <div class="fl"><label>SaO₂ (%)</label><input type="text" name="be_sao2" value="<?= $d('be_sao2') ?>" placeholder="98"></div>
              <div class="fl"><label>T°C</label><input type="text" name="be_temp" value="<?= $d('be_temp') ?>" placeholder="36.8"></div>
            </div>
            <div class="fl"><label>Conscience / EVA</label><input type="text" name="be_conscience" value="<?= $d('be_conscience') ?>" placeholder="Éveillé, EVA 2/10…"></div>
          </div>
          <!-- Sortie -->
          <div class="bilan-card bc-sortie">
            <div class="bilan-title"><i class="bi bi-arrow-up-circle-fill"></i> À la sortie</div>
            <div class="fg g2">
              <div class="fl"><label>TA</label><input type="text" name="bs_ta" value="<?= $d('bs_ta') ?>" placeholder="120/80"></div>
              <div class="fl"><label>Pouls</label><input type="text" name="bs_pouls" value="<?= $d('bs_pouls') ?>" placeholder="75"></div>
            </div>
            <div class="fg g2">
              <div class="fl"><label>SaO₂ (%)</label><input type="text" name="bs_sao2" value="<?= $d('bs_sao2') ?>" placeholder="98"></div>
              <div class="fl"><label>T°C</label><input type="text" name="bs_temp" value="<?= $d('bs_temp') ?>" placeholder="36.8"></div>
            </div>
            <div class="fl"><label>Score Aldrete final</label><input type="text" name="aldrete_final" value="<?= $d('aldrete_final') ?>" placeholder="/10"></div>
          </div>
          <!-- Séjour -->
          <div class="bilan-card bc-sejour">
            <div class="bilan-title"><i class="bi bi-bar-chart-fill"></i> Bilan du séjour</div>
            <div class="fl" style="margin-bottom:10px"><label>Entrées totales (ml)</label><input type="text" name="sejour_entrees" value="<?= $d('sejour_entrees') ?>"></div>
            <div class="fl" style="margin-bottom:10px"><label>Sorties totales (ml)</label><input type="text" name="sejour_sorties" value="<?= $d('sejour_sorties') ?>"></div>
            <div class="fl" style="margin-bottom:10px"><label>Balance (ml)</label><input type="text" name="sejour_balance" value="<?= $d('sejour_balance') ?>"></div>
            <div class="fl"><label>Évolution générale</label><textarea name="evolution" rows="2" placeholder="Favorable, complications…"><?= $d('evolution') ?></textarea></div>
          </div>
        </div>
      </div>
    </div>

    <!-- ══ III. GRILLE QUOTIDIENNE ══ -->
    <div class="sec">
      <div class="sh">
        <div class="sn ng">III</div>
        <div class="st">Grille de surveillance quotidienne — J0 à J6</div>
        <span style="margin-left:auto;font-size:.72rem;color:var(--s)">Remplissez chaque colonne jour par jour</span>
      </div>
      <div class="sb">
        <div class="grid-wrap">
          <table class="grid-tbl">
            <thead>
              <tr>
                <th style="min-width:170px;text-align:left;background:#f8fafc">Paramètre surveillé</th>
                <?php foreach($jours as $j): ?>
                <th class="jh"><?=$j?></th>
                <?php endforeach ?>
              </tr>
            </thead>
            <tbody>
              <?php foreach($lignes as $lg):
                $style = $catStyle[$lg['cat']] ?? '';
              ?>
              <tr>
                <td class="rl" style="<?=$style?>"><?= $lg['label'] ?></td>
                <?php foreach($jours as $ji=>$j): ?>
                <td><input type="text" name="grid_<?=$lg['key']?>_<?=$ji?>" value="<?= htmlspecialchars($data['grid_'.$lg['key'].'_'.$ji]??'') ?>"></td>
                <?php endforeach ?>
              </tr>
              <?php endforeach ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ══ IV. DESTINATION & OBSERVATIONS ══ -->
    <div class="sec">
      <div class="sh"><div class="sn nv">IV</div><div class="st">Destination & Observations finales</div></div>
      <div class="sb">
        <div class="fg g3">
          <div class="fl"><label>Destination à la sortie</label>
            <select name="destination">
              <option value="">—</option>
              <?php foreach(['Service de chirurgie','Réanimation','USI','Retour à domicile','Transfert externe','Décès'] as $dest): ?>
              <option value="<?=$dest?>" <?=($data['destination']??'')===$dest?'selected':''?>><?=$dest?></option>
              <?php endforeach ?>
            </select>
          </div>
          <div class="fl"><label>Service de destination</label><input type="text" name="service_destination" value="<?= $d('service_destination') ?>"></div>
          <div class="fl"><label>Durée de séjour (heures)</label><input type="text" name="duree_sejour" value="<?= $d('duree_sejour') ?>"></div>
        </div>
        <div class="fg g2">
          <div>
            <div class="sub sub-r">
              <div class="sub-t"><i class="bi bi-exclamation-triangle-fill"></i> Complications post-opératoires</div>
              <div class="fl"><textarea name="complications" rows="4" placeholder="Infection plaie, NVPO persistants, hématome, déhiscence, bronchospasme, fistule… (laisser vide si aucune)"><?= $d('complications') ?></textarea></div>
            </div>
          </div>
          <div>
            <div class="sub sub-g">
              <div class="sub-t"><i class="bi bi-clipboard-check-fill"></i> Compte rendu & résumé</div>
              <div class="fl" style="margin-bottom:10px"><textarea name="compte_rendu" rows="3" placeholder="Résumé du séjour en SSPI / unité de soins post-op…"><?= $d('compte_rendu') ?></textarea></div>
              <div class="fg g2">
                <div class="fl"><label>Médecin référent</label><input type="text" name="medecin_referent" value="<?= $d('medecin_referent') ?>"></div>
                <div class="fl"><label>Anesthésiste responsable</label><input type="text" name="anesthesiste_nom" value="<?= $d('anesthesiste_nom',trim(($_SESSION['user_prenom']??'').' '.($_SESSION['user_nom']??''))) ?>"></div>
              </div>
            </div>
          </div>
        </div>
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
    sv('fsx', p.sexe);
    sf('fpo', '');
    if(i)sf('fdi',i.type_intervention||'');
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
function sv(id,val){const e=document.getElementById(id);if(e&&val)e.value=val;}

async function save(st){
  if(!document.getElementById('hpid').value){alert2('Veuillez sélectionner un patient.',false);return;}
  const fd=new FormData(document.getElementById('ff'));fd.set('statut',st);
  try{
    const res=await(await fetch(`${B}anesthesie/sauvegarder-surveillance-post-op`,{method:'POST',body:fd})).json();
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
