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
?>
<style>
:root{--r:#dc2626;--rl:#fef2f2;--rb:#fecaca;--b:#1d4ed8;--bl:#eff6ff;--bb:#bfdbfe;--g:#16a34a;--gl:#f0fdf4;--gb:#bbf7d0;--w:#fff;--bg:#f1f5f9;--bd:#e2e8f0;--t:#0f172a;--s:#64748b}
*{box-sizing:border-box}
body{background:var(--bg);font-family:'Inter',system-ui,sans-serif;color:var(--t);margin:0}
.fp{max-width:1080px;margin:0 auto;padding:24px 20px 80px}

/* topbar */
.ftop{background:var(--w);border-radius:16px;padding:18px 24px;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;box-shadow:0 2px 12px rgba(0,0,0,.06);border:1px solid var(--bd)}
.ftop-l{display:flex;align-items:center;gap:14px}
.ftop-ico{width:48px;height:48px;border-radius:14px;background:linear-gradient(135deg,var(--b),#6366f1);display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.3rem}
.ftop-title{font-size:1.05rem;font-weight:800;margin:0}
.ftop-sub{font-size:.75rem;color:var(--s);margin:1px 0 0}
.ftop-r{display:flex;gap:8px}
.btn-sm{background:#f8fafc;border:1px solid var(--bd);color:var(--s);border-radius:10px;padding:8px 16px;font-size:.8rem;font-weight:700;cursor:pointer;text-decoration:none;display:flex;align-items:center;gap:6px;transition:.15s}
.btn-sm:hover{background:#e2e8f0;color:var(--t)}

/* alert */
.al{border-radius:12px;padding:12px 18px;font-size:.83rem;font-weight:600;margin-bottom:16px;display:none;align-items:center;gap:10px;border:1.5px solid transparent}
.al.ok{background:var(--gl);border-color:var(--gb);color:var(--g)}
.al.err{background:var(--rl);border-color:var(--rb);color:var(--r)}

/* section */
.sec{background:var(--w);border-radius:16px;margin-bottom:16px;overflow:hidden;box-shadow:0 2px 10px rgba(0,0,0,.05);border:1px solid var(--bd)}
.sh{padding:14px 22px;border-bottom:2px solid var(--bd);display:flex;align-items:center;gap:10px}
.sn{width:28px;height:28px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:.72rem;font-weight:900;color:#fff;flex-shrink:0}
.nb{background:var(--b)}.nr{background:var(--r)}.ng{background:var(--g)}.nv{background:#7c3aed}.no{background:#d97706}.nt{background:#0f766e}.ni{background:#4338ca}
.st{font-size:.82rem;font-weight:800;text-transform:uppercase;letter-spacing:.4px}
.sb{padding:20px 22px}

/* patient banner */
.pbanner{background:linear-gradient(135deg,#1d4ed8,#6366f1);border-radius:12px;padding:16px 20px;color:#fff;display:flex;align-items:center;gap:18px;margin-bottom:18px}
.pav{width:52px;height:52px;border-radius:14px;background:rgba(255,255,255,.2);border:2px solid rgba(255,255,255,.35);display:flex;align-items:center;justify-content:center;font-size:1.2rem;font-weight:800;flex-shrink:0}
.pname{font-size:1.05rem;font-weight:800}
.pmeta{font-size:.77rem;opacity:.85;margin-top:2px}
.pchips{display:flex;gap:7px;flex-wrap:wrap;margin-top:8px}
.pchip{background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.3);border-radius:50px;padding:3px 10px;font-size:.7rem;font-weight:700}

/* grid */
.fg{display:grid;gap:14px;margin-bottom:14px}
.g1{grid-template-columns:1fr}
.g2{grid-template-columns:1fr 1fr}
.g3{grid-template-columns:1fr 1fr 1fr}
.g4{grid-template-columns:1fr 1fr 1fr 1fr}
.g5{grid-template-columns:repeat(5,1fr)}
@media(max-width:700px){.g2,.g3,.g4,.g5{grid-template-columns:1fr 1fr}}

/* field */
.fl label{font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.5px;color:var(--s);display:block;margin-bottom:5px}
.fl input,.fl select,.fl textarea{width:100%;padding:10px 13px;border:1.5px solid var(--bd);border-radius:10px;font-size:.88rem;color:var(--t);background:var(--w);outline:none;transition:border .15s,box-shadow .15s;font-family:inherit}
.fl input:focus,.fl select:focus,.fl textarea:focus{border-color:var(--b);box-shadow:0 0 0 3px rgba(29,78,216,.1)}
.fl textarea{resize:vertical;min-height:72px;line-height:1.5}
.fl input[readonly]{background:#f8fafc;color:#94a3b8}

/* sub-blocks */
.sub{border-radius:12px;padding:16px;border:1.5px solid}
.sub-b{background:var(--bl);border-color:var(--bb)}.sub-r{background:var(--rl);border-color:var(--rb)}.sub-g{background:var(--gl);border-color:var(--gb)}.sub-v{background:#faf5ff;border-color:#e9d5ff}.sub-o{background:#fffbeb;border-color:#fde68a}
.sub-t{font-size:.7rem;font-weight:800;text-transform:uppercase;letter-spacing:.5px;margin-bottom:12px;display:flex;align-items:center;gap:7px}
.sub-b .sub-t{color:var(--b)}.sub-r .sub-t{color:var(--r)}.sub-g .sub-t{color:var(--g)}.sub-v .sub-t{color:#6d28d9}.sub-o .sub-t{color:#92400e}

/* checkboxes */
.ckg{display:flex;flex-wrap:wrap;gap:8px}
.cki{display:flex;align-items:center;gap:7px;font-size:.82rem;background:var(--w);border:1.5px solid var(--bd);border-radius:8px;padding:6px 12px;cursor:pointer;transition:.15s;user-select:none}
.cki input{display:none}
.ckb{width:16px;height:16px;border-radius:4px;border:2px solid var(--bd);flex-shrink:0;display:flex;align-items:center;justify-content:center;transition:.15s}
.cki input:checked~.ckb{background:var(--r);border-color:var(--r)}
.cki input:checked~.ckb::after{content:'✓';font-size:.65rem;color:#fff;font-weight:900}
.cki:has(input:checked){border-color:var(--rb);background:var(--rl)}

/* vitaux */
.vrow{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:14px}
@media(max-width:700px){.vrow{grid-template-columns:repeat(2,1fr)}}
.vc{border-radius:12px;padding:12px 14px;border:1.5px solid var(--bd);background:var(--w);text-align:center;transition:border .15s}
.vc:focus-within{border-color:var(--b);box-shadow:0 0 0 3px rgba(29,78,216,.08)}
.vc-r{border-color:var(--rb)!important}.vc-r .vlb,.vc-r .vi{color:var(--r)}
.vc-b{border-color:var(--bb)!important}.vc-b .vlb,.vc-b .vi{color:var(--b)}
.vc-g{border-color:var(--gb)!important}.vc-g .vlb,.vc-g .vi{color:var(--g)}
.vlb{font-size:.62rem;font-weight:800;text-transform:uppercase;letter-spacing:.4px;color:var(--s);margin-bottom:6px}
.vi{width:100%;text-align:center;border:none;outline:none;font-size:1.2rem;font-weight:800;color:var(--t);background:transparent;font-family:inherit;padding:0}
.vun{font-size:.65rem;color:var(--s);margin-top:3px}

/* ASA */
.asa-r{display:flex;gap:8px;flex-wrap:wrap}
.asa-o{display:none}
.asa-l{padding:9px 18px;border-radius:10px;border:2px solid var(--bd);background:var(--w);font-size:.82rem;font-weight:700;cursor:pointer;transition:.15s;user-select:none}
.asa-o:checked+.asa-l{background:var(--b);border-color:var(--b);color:#fff;box-shadow:0 4px 12px rgba(29,78,216,.25)}
.asa-o:checked+.asa-l.asa-urgent{background:#dc2626!important;border-color:#dc2626!important;color:#fff!important;box-shadow:0 4px 16px rgba(220,38,38,.45)!important;animation:asa-pulse 1.4s ease-in-out infinite}
@keyframes asa-pulse{0%,100%{box-shadow:0 4px 16px rgba(220,38,38,.4)}50%{box-shadow:0 4px 28px rgba(220,38,38,.75)}}

/* Mallampati */
.mrow{display:flex;gap:8px}
.mo{display:none}
.ml{flex:1;padding:10px 6px;border-radius:10px;border:2px solid var(--bd);text-align:center;cursor:pointer;transition:.15s;font-size:.75rem;font-weight:700;user-select:none}
.ml .mn{font-size:1.25rem;font-weight:900;display:block}
.mo:checked+.ml{background:var(--r);border-color:var(--r);color:#fff}

/* footer */
.ffooter{display:flex;justify-content:space-between;align-items:center;background:var(--w);border-radius:16px;padding:16px 24px;margin-top:20px;box-shadow:0 2px 10px rgba(0,0,0,.05);border:1px solid var(--bd);position:sticky;bottom:16px;z-index:50}
.btn-draft{background:var(--bl);color:var(--b);border:2px solid var(--bb);border-radius:12px;padding:11px 22px;font-size:.88rem;font-weight:800;cursor:pointer;display:flex;align-items:center;gap:8px;transition:.15s}
.btn-draft:hover{background:var(--b);color:#fff}
.btn-final{background:linear-gradient(135deg,var(--g),#059669);color:#fff;border:none;border-radius:12px;padding:11px 26px;font-size:.88rem;font-weight:800;cursor:pointer;display:flex;align-items:center;gap:8px;box-shadow:0 4px 14px rgba(22,163,74,.3);transition:.15s}
.btn-final:hover{transform:translateY(-1px);box-shadow:0 6px 20px rgba(22,163,74,.35)}
.fin-badge{background:var(--gl);color:var(--g);border:1px solid var(--gb);border-radius:50px;padding:5px 14px;font-size:.75rem;font-weight:800;display:flex;align-items:center;gap:6px}
.spin{display:none;width:16px;height:16px;border:2px solid rgba(0,0,0,.1);border-top-color:var(--b);border-radius:50%;animation:sp .6s linear infinite;position:absolute;right:12px;top:50%;transform:translateY(-50%)}
@keyframes sp{to{transform:translateY(-50%) rotate(360deg)}}
@media print{.np{display:none!important}body{background:#fff}.ffooter{position:static}}
</style>

<div class="fp">

  <!-- Topbar -->
  <div class="ftop np">
    <div class="ftop-l">
      <div class="ftop-ico"><i class="bi bi-clipboard2-pulse-fill"></i></div>
      <div>
        <div class="ftop-title">Consultation Pré-Anesthésique</div>
        <div class="ftop-sub">HSJM · Fiche clinique — avant intervention chirurgicale</div>
      </div>
    </div>
    <div class="ftop-r">
      <?php if ($fiche && $fiche['statut']==='finalise'): ?>
        <span class="fin-badge"><i class="bi bi-check-circle-fill"></i>Finalisée</span>
      <?php endif; ?>
      <button class="btn-sm np" onclick="window.print()"><i class="bi bi-printer"></i>Imprimer</button>
      <a href="<?= BASE_URL ?>dashboard" class="btn-sm"><i class="bi bi-arrow-left"></i>Retour</a>
    </div>
  </div>

  <!-- Alert -->
  <div id="al" class="al"><i class="bi" id="alIco"></i><span id="alTxt"></span></div>

  <form id="ff">
    <?= CsrfService::field() ?>
    <input type="hidden" name="fiche_id"   value="<?= $fiche_id ?>">
    <input type="hidden" name="patient_id" id="hpid" value="<?= $patient_id ?>">

    <!-- ══ I. IDENTIFICATION ══ -->
    <div class="sec">
      <div class="sh"><div class="sn nb">I</div><div class="st">Identification du patient</div></div>
      <div class="sb">

        <!-- Sélecteur -->
        <div class="fg g1" style="margin-bottom:18px">
          <div class="fl">
            <label><i class="bi bi-search me-1"></i>Sélectionner un patient *</label>
            <div style="position:relative">
              <select id="sp" onchange="loadPatient(this.value)"
                style="border-color:var(--b);background:var(--bl);font-weight:700;padding-right:40px">
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

        <!-- Banner patient -->
        <div id="pb" class="pbanner" style="<?= $patient?'':'display:none' ?>">
          <div class="pav" id="pav">
            <?= $patient?strtoupper(substr($patient['nom'],0,1).substr($patient['prenom'],0,1)):'' ?>
          </div>
          <div style="flex:1">
            <div class="pname" id="pnm"><?= $patient?htmlspecialchars($patient['nom'].' '.$patient['prenom']):'' ?></div>
            <div class="pmeta" id="pmt"></div>
            <div class="pchips" id="pch"></div>
          </div>
        </div>

        <div class="fg g4">
          <div class="fl"><label>Âge</label><input type="text" name="age" id="fa" value="<?= $d('age') ?>" placeholder="35 ans"></div>
          <div class="fl"><label>Sexe</label>
            <select name="sexe" id="fs">
              <option value="">—</option>
              <option value="M" <?= ($data['sexe']??'')==='M'?'selected':'' ?>>Masculin</option>
              <option value="F" <?= ($data['sexe']??'')==='F'?'selected':'' ?>>Féminin</option>
            </select>
          </div>
          <div class="fl"><label>Groupe sanguin</label>
            <select name="gs" id="fgs">
              <option value="">—</option>
              <?php foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $g): ?>
              <option value="<?= $g ?>" <?= ($data['gs']??'')===$g?'selected':'' ?>><?= $g ?></option>
              <?php endforeach ?>
            </select>
          </div>
          <div class="fl"><label>Rh</label><input type="text" name="rh" id="frh" value="<?= $d('rh') ?>"></div>
        </div>
        <div class="fg g3">
          <div class="fl"><label>Profession</label><input type="text" name="profession" id="fpr" value="<?= $d('profession') ?>"></div>
          <div class="fl"><label>Téléphone</label><input type="text" name="telephone" id="ftel" value="<?= $d('telephone') ?>"></div>
          <div class="fl"><label>Service / Unité</label><input type="text" name="service" id="fsv" value="<?= $d('service',$patient['nom_service']??'') ?>"></div>
        </div>
        <div class="fg g3">
          <div class="fl"><label>Intervention prévue</label><input type="text" name="intervention" id="fint" value="<?= $d('intervention') ?>" placeholder="Ex: Appendicectomie"></div>
          <div class="fl"><label>Chirurgien principal</label><input type="text" name="chirurgien" id="fch" value="<?= $d('chirurgien') ?>"></div>
          <div class="fl"><label>Anesthésiste</label><input type="text" name="anesthesiste_nom" value="<?= $d('anesthesiste_nom',trim(($_SESSION['user_prenom']??'').' '.($_SESSION['user_nom']??''))) ?>"></div>
        </div>
        <div class="fg g2">
          <div class="fl"><label>Date de consultation</label><input type="date" name="date_consultation" value="<?= $d('date_consultation',date('Y-m-d')) ?>"></div>
          <div class="fl"><label>Adresse</label><input type="text" name="adresse" id="fad" value="<?= $d('adresse') ?>"></div>
        </div>
      </div>
    </div>

    <!-- ══ II. ANTÉCÉDENTS ══ -->
    <div class="sec">
      <div class="sh"><div class="sn nr">II</div><div class="st">Antécédents, Terrains & Mode de vie</div></div>
      <div class="sb">
        <div class="fg g2">
          <div class="fl"><label>Antécédents familiaux</label><textarea name="atcd_familiaux" id="faf" placeholder="HTA, diabète, cardiopathie familiale…"><?= $d('atcd_familiaux') ?></textarea></div>
          <div class="fl"><label>Antécédents personnels</label><textarea name="atcd_personnels" id="fap" placeholder="Maladies chroniques, hospitalisations…"><?= $d('atcd_personnels') ?></textarea></div>
        </div>

        <div class="sub sub-r" style="margin-bottom:14px">
          <div class="sub-t"><i class="bi bi-heart-pulse-fill"></i> Antécédents médicaux notables</div>
          <div class="ckg">
            <?php foreach(['HTA','Diabète','Glaucome','BPCO','Asthme','Cardiopathie','Insuff. rénale','Épilepsie','Gastrite','Drépanocytose'] as $it): ?>
            <label class="cki">
              <input type="checkbox" name="atcd_med[]" value="<?= $it ?>" <?= in_array($it,$data['atcd_med']??[])?'checked':'' ?>>
              <span class="ckb"></span><?= $it ?>
            </label>
            <?php endforeach ?>
          </div>
          <div class="fl" style="margin-top:10px">
            <input type="text" name="atcd_med_autres" placeholder="Autres antécédents médicaux…" value="<?= $d('atcd_med_autres') ?>">
          </div>
        </div>

        <div class="fg g2">
          <div class="sub sub-v">
            <div class="sub-t"><i class="bi bi-gender-female"></i> Gynéco-obstétricaux</div>
            <div class="fg g4">
              <div class="fl"><label>G</label><input type="text" name="gyneco_g" value="<?= $d('gyneco_g') ?>" placeholder="0"></div>
              <div class="fl"><label>P</label><input type="text" name="gyneco_p" value="<?= $d('gyneco_p') ?>" placeholder="0"></div>
              <div class="fl"><label>DDR</label><input type="date" name="gyneco_ddr" value="<?= $d('gyneco_ddr') ?>"></div>
              <div class="fl"><label>AG (sem.)</label><input type="text" name="gyneco_ag" value="<?= $d('gyneco_ag') ?>"></div>
            </div>
          </div>
          <div class="sub sub-o">
            <div class="sub-t"><i class="bi bi-exclamation-triangle-fill"></i> Toxicologiques</div>
            <div class="fg g2">
              <div class="fl"><label>Alcool</label><input type="text" name="alcool" placeholder="Fréquence / quantité" value="<?= $d('alcool') ?>"></div>
              <div class="fl"><label>Tabac</label><input type="text" name="tabac" placeholder="paquet/an" value="<?= $d('tabac') ?>"></div>
            </div>
            <div class="fl"><input type="text" name="toxiques_autres" placeholder="Autres (drogues, khat…)" value="<?= $d('toxiques_autres') ?>"></div>
          </div>
        </div>

        <div class="fg g2">
          <div class="sub sub-r">
            <div class="sub-t"><i class="bi bi-droplet-fill"></i> Immuno-allergiques & hématologiques</div>
            <div class="fg g4">
              <div class="fl"><label>Hb (g/dl)</label><input type="text" name="hb_pre" value="<?= $d('hb_pre') ?>"></div>
              <div class="fl"><label>AA/AS/SS</label><input type="text" name="drepano" value="<?= $d('drepano') ?>"></div>
              <div class="fl"><label>TP (%)</label><input type="text" name="tp" value="<?= $d('tp') ?>"></div>
              <div class="fl"><label>PLQ (×10³)</label><input type="text" name="plq" value="<?= $d('plq') ?>"></div>
            </div>
            <div class="fg g2" style="margin-top:10px">
              <div class="fl"><label>Transfusions antérieures</label><input type="text" name="transfusions" value="<?= $d('transfusions') ?>"></div>
              <div class="fl"><label>Anticoagulants / RAI</label><input type="text" name="anticoagulants" value="<?= $d('anticoagulants') ?>"></div>
            </div>
            <div class="fl" style="margin-top:10px">
              <label>⚠️ Allergies connues</label>
              <input type="text" id="fall" name="allergies" placeholder="Pénicilline, latex, iode, AINS…" value="<?= $d('allergies') ?>" style="border-color:var(--rb);background:var(--rl)">
            </div>
          </div>
          <div class="sub sub-b">
            <div class="sub-t"><i class="bi bi-scissors"></i> Antécédents chirurgicaux & anesthésiques</div>
            <div class="fl"><textarea name="atcd_chir_anesth" rows="5" placeholder="Type d'opération, date, anesthésie antérieure, complications éventuelles…"><?= $d('atcd_chir_anesth') ?></textarea></div>
          </div>
        </div>
      </div>
    </div>

    <!-- ══ III. BILAN ══ -->
    <div class="sec">
      <div class="sh"><div class="sn ng">III</div><div class="st">Bilan paraclinique</div></div>
      <div class="sb">
        <div class="sub sub-g" style="margin-bottom:14px">
          <div class="sub-t"><i class="bi bi-droplet-half"></i> Hémogramme complet (NFS)</div>
          <div class="fg g5">
            <div class="fl"><label>GB (×10³/µl)</label><input type="text" name="gb" value="<?= $d('gb') ?>"></div>
            <div class="fl"><label>GR (×10⁶/µl)</label><input type="text" name="gr" value="<?= $d('gr') ?>"></div>
            <div class="fl"><label>Hb (g/dl)</label><input type="text" name="hb" value="<?= $d('hb') ?>"></div>
            <div class="fl"><label>HT (%)</label><input type="text" name="ht" value="<?= $d('ht') ?>"></div>
            <div class="fl"><label>VGM (fl)</label><input type="text" name="vgm" value="<?= $d('vgm') ?>"></div>
          </div>
        </div>
        <div class="fg g2">
          <div class="sub sub-r">
            <div class="sub-t"><i class="bi bi-activity"></i> Hémostase</div>
            <div class="fg g3">
              <div class="fl"><label>TP (%)</label><input type="text" name="tp2" value="<?= $d('tp2') ?>"></div>
              <div class="fl"><label>TCA</label><input type="text" name="tck" value="<?= $d('tck') ?>"></div>
              <div class="fl"><label>PLQ (×10³)</label><input type="text" name="plq2" value="<?= $d('plq2') ?>"></div>
            </div>
          </div>
          <div class="sub sub-b">
            <div class="sub-t"><i class="bi bi-flask-fill"></i> Ionogramme & biochimie</div>
            <div class="fg g4">
              <div class="fl"><label>Na⁺ (mEq/L)</label><input type="text" name="na" value="<?= $d('na') ?>"></div>
              <div class="fl"><label>K⁺ (mEq/L)</label><input type="text" name="k" value="<?= $d('k') ?>"></div>
              <div class="fl"><label>Urée (g/L)</label><input type="text" name="uree" value="<?= $d('uree') ?>"></div>
              <div class="fl"><label>Créatinine</label><input type="text" name="creatinine" value="<?= $d('creatinine') ?>"></div>
            </div>
          </div>
        </div>
        <div class="fg g3">
          <div class="fl"><label>Glycémie (g/L)</label><input type="text" name="glycemie" value="<?= $d('glycemie') ?>"></div>
          <div class="fl"><label>Radiographie pulmonaire</label><input type="text" name="rx_pulm" value="<?= $d('rx_pulm') ?>" placeholder="Résultat / date"></div>
          <div class="fl"><label>ECG</label><input type="text" name="ecg" value="<?= $d('ecg') ?>" placeholder="Résultat / date"></div>
        </div>
        <div class="fl"><label>Autres examens complémentaires</label><input type="text" name="autres_bilans" value="<?= $d('autres_bilans') ?>" placeholder="Écho-cœur, scanner, EFR, fibroscopie…"></div>
      </div>
    </div>

    <!-- ══ IV. EXAMEN PHYSIQUE ══ -->
    <div class="sec">
      <div class="sh"><div class="sn nv">IV</div><div class="st">Examen Physique</div></div>
      <div class="sb">

        <!-- Constantes vitales -->
        <div style="margin-bottom:16px">
          <div style="font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.5px;color:var(--s);margin-bottom:10px">● Constantes vitales</div>
          <div class="vrow">
            <div class="vc vc-r"><div class="vlb">TA (mmHg)</div><input class="vi" type="text" name="ta" value="<?= $d('ta') ?>" placeholder="120/80"><div class="vun">sys / dia</div></div>
            <div class="vc vc-b"><div class="vlb">Pouls (/min)</div><input class="vi" type="text" name="pouls" value="<?= $d('pouls') ?>" placeholder="75"><div class="vun">BPM</div></div>
            <div class="vc vc-g"><div class="vlb">SpO₂ (%)</div><input class="vi" type="text" name="spo2" value="<?= $d('spo2') ?>" placeholder="98"><div class="vun">%</div></div>
            <div class="vc"><div class="vlb">FR (/min)</div><input class="vi" type="text" name="fr" value="<?= $d('fr') ?>" placeholder="16"><div class="vun">cycles/min</div></div>
            <div class="vc"><div class="vlb">T° (°C)</div><input class="vi" type="text" name="temp" value="<?= $d('temp') ?>" placeholder="37.0"><div class="vun">°C</div></div>
            <div class="vc"><div class="vlb">Poids (kg)</div><input class="vi" type="text" name="poids" id="vp" value="<?= $d('poids') ?>" placeholder="70" oninput="window.imc()"><div class="vun">kg</div></div>
            <div class="vc"><div class="vlb">Taille (cm)</div><input class="vi" type="text" name="taille" id="vt" value="<?= $d('taille') ?>" placeholder="170" oninput="window.imc()"><div class="vun">cm</div></div>
            <div class="vc vc-b"><div class="vlb">IMC (kg/m²)</div><input class="vi" type="text" name="imc" id="vi" value="<?= $d('imc') ?>" placeholder="—" readonly><div class="vun">auto-calculé</div></div>
          </div>
        </div>

        <!-- État général -->
        <div class="fg g2" style="margin-bottom:14px">
          <div class="fl"><label>Dyspnée (NYHA)</label>
            <select name="nyha">
              <option value="">— Classe —</option>
              <?php foreach(['I — Asymptomatique','II — Effort modéré','III — Effort minime','IV — Repos'] as $i=>$n): ?>
              <option value="<?=$i+1?>" <?=($data['nyha']??'')==(string)($i+1)?'selected':''?>><?=$n?></option>
              <?php endforeach ?>
            </select>
          </div>
          <div class="fl"><label>Conjonctives</label>
            <select name="conjonctives">
              <option value="">—</option>
              <option value="Colorées et roses" <?=($data['conjonctives']??'')==='Colorées et roses'?'selected':''?>>Colorées et roses (normal)</option>
              <option value="Pâles" <?=($data['conjonctives']??'')==='Pâles'?'selected':''?>>Pâles (anémie)</option>
              <option value="Ictériques" <?=($data['conjonctives']??'')==='Ictériques'?'selected':''?>>Ictériques</option>
            </select>
          </div>
        </div>
        <div class="fg g3">
          <div class="fl"><label>Cœur / Auscultation</label><textarea name="coeur" rows="2" placeholder="Bruits cardiaques normaux, souffle…"><?= $d('coeur') ?></textarea></div>
          <div class="fl"><label>Poumons / Auscultation</label><textarea name="poumons" rows="2" placeholder="MV conservé, râles, sibilants…"><?= $d('poumons') ?></textarea></div>
          <div class="fl"><label>Abdomen</label><textarea name="abdomen" rows="2" placeholder="Souple, défense, masse…"><?= $d('abdomen') ?></textarea></div>
        </div>
        <div class="fg g2">
          <div class="fl"><label>Membres</label><textarea name="membres" rows="2" placeholder="OMI, pouls distaux, varices…"><?= $d('membres') ?></textarea></div>
          <div class="fl"><label>État général</label><textarea name="etat_general" rows="2" placeholder="Bon état général, altéré, cachexie…"><?= $d('etat_general') ?></textarea></div>
        </div>

        <!-- Intubabilité -->
        <div class="sub sub-r">
          <div class="sub-t"><i class="bi bi-lungs-fill"></i> Évaluation des voies aériennes — Intubabilité</div>
          <div class="fg g4">
            <div class="fl"><label>Ouverture bouche (cm)</label><input type="text" name="ob" value="<?= $d('ob') ?>" placeholder="> 3.5 cm"></div>
            <div class="fl"><label>Distance thyro-mentonnière</label><input type="text" name="dtm" value="<?= $d('dtm') ?>" placeholder="> 6.5 cm"></div>
            <div class="fl"><label>État dentaire / prothèse</label><input type="text" name="dentaire" value="<?= $d('dentaire') ?>" placeholder="Normal, édentée…"></div>
            <div class="fl"><label>Sites ALR / État veineux</label><input type="text" name="alr_veineux" value="<?= $d('alr_veineux') ?>"></div>
          </div>
          <div class="fg g3" style="margin-top:10px">
            <div class="fl"><label>Langue</label><input type="text" name="langue" value="<?= $d('langue') ?>"></div>
            <div class="fl"><label>Nuque</label><input type="text" name="nuque" value="<?= $d('nuque') ?>"></div>
            <div class="fl"><label>Cou (mobilité)</label><input type="text" name="cou" value="<?= $d('cou') ?>"></div>
          </div>

          <div style="margin-top:16px;display:grid;grid-template-columns:1fr 1fr;gap:16px">
            <div>
              <div style="font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.5px;color:var(--r);margin-bottom:10px">Score de Mallampati</div>
              <div class="mrow">
                <?php foreach(['I','II','III','IV'] as $n): ?>
                <input type="radio" name="mallampati" value="<?=$n?>" id="mp<?=$n?>" class="mo" <?=($data['mallampati']??'')===$n?'checked':''?>>
                <label for="mp<?=$n?>" class="ml">
                  <span class="mn"><?=$n?></span>
                  <?=match($n){'I'=>'Luette visible','II'=>'Luette partielle','III'=>'Palais mou','IV'=>'Palais dur'}?>
                </label>
                <?php endforeach ?>
              </div>
            </div>
            <div>
              <div style="font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.5px;color:var(--r);margin-bottom:10px">Score de Cormack-Lehane</div>
              <div class="mrow">
                <?php foreach(['I','IIa','IIb','III','IV'] as $n): ?>
                <input type="radio" name="cormack" value="<?=$n?>" id="ck<?=$n?>" class="mo" <?=($data['cormack']??'')===$n?'checked':''?>>
                <label for="ck<?=$n?>" class="ml"><span class="mn" style="font-size:1rem"><?=$n?></span></label>
                <?php endforeach ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ══ V. RÉSUMÉ & CONCLUSION ══ -->
    <div class="sec">
      <div class="sh"><div class="sn nt">V</div><div class="st">Résumé & Conclusion anesthésique</div></div>
      <div class="sb">
        <div class="fl" style="margin-bottom:18px">
          <label>Résumé / Problèmes posés par ce patient</label>
          <textarea name="resume" rows="3" placeholder="Synthèse des éléments cliniques déterminants pour la prise en charge anesthésique…"><?= $d('resume') ?></textarea>
        </div>
        <div style="margin-bottom:16px">
          <div style="font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.5px;color:var(--s);margin-bottom:10px">Score ASA</div>
          <div class="asa-r">
            <?php foreach(['I','II','III','IV','V','VI'] as $n): ?>
            <input type="radio" name="asa" value="<?=$n?>" id="asa<?=$n?>" class="asa-o" onchange="updateAsaInfo()" <?=($data['asa']??'')===$n?'checked':''?>>
            <label for="asa<?=$n?>" class="asa-l">ASA <?=$n?></label>
            <?php endforeach ?>
          </div>
          <input type="hidden" name="asa_urgent" id="asa-urgent-input" value="<?= !empty($data['asa_urgent']) ? '1' : '0' ?>">
          <div id="asa-info-box" style="display:none;margin-top:10px;padding:10px 14px;border-radius:10px;border:1.5px solid #e5e7eb;transition:.2s;"></div>
        </div>
        <div class="fg g2">
          <div class="fl"><label>Score Altemeier (risque infectieux)</label>
            <select name="altemeier">
              <option value="">—</option>
              <option value="1" <?=($data['altemeier']??'')==='1'?'selected':''?>>I — Chirurgie propre</option>
              <option value="2" <?=($data['altemeier']??'')==='2'?'selected':''?>>II — Propre contaminé</option>
              <option value="3" <?=($data['altemeier']??'')==='3'?'selected':''?>>III — Contaminé</option>
              <option value="4" <?=($data['altemeier']??'')==='4'?'selected':''?>>IV — Sale / infecté</option>
            </select>
          </div>
          <div class="fl"><label>Technique anesthésique retenue</label>
            <select name="technique">
              <option value="">—</option>
              <?php foreach(['AG — Intubation oro-trachéale','AG — Masque laryngé','AG — Masque facial','ALR — Rachianesthésie','ALR — Péridurale','ALR — Bloc nerveux périphérique','Sédation consciente / ALIV'] as $t): ?>
              <option value="<?=$t?>" <?=($data['technique']??'')===$t?'selected':''?>><?=$t?></option>
              <?php endforeach ?>
            </select>
          </div>
        </div>
      </div>
    </div>

    <!-- ══ VI. PRÉPARATION ══ -->
    <div class="sec">
      <div class="sh"><div class="sn ni">VI</div><div class="st">Préparation & Visite pré-opératoire</div></div>
      <div class="sb">
        <div class="fg g2">
          <div class="sub sub-b">
            <div class="sub-t"><i class="bi bi-capsule-pill"></i> Médicaments & instructions</div>
            <div class="fl" style="margin-bottom:10px"><label>Transfusion pré-opératoire</label><input type="text" name="transf_preop" value="<?= $d('transf_preop') ?>"></div>
            <div class="fl" style="margin-bottom:10px"><label>Prémédication</label><input type="text" name="premedication" value="<?= $d('premedication') ?>" placeholder="Ex: Midazolam 7.5mg PO"></div>
            <div class="fl" style="margin-bottom:10px"><label>Jeûne pré-opératoire</label><input type="text" name="jeune_preop" value="<?= $d('jeune_preop') ?>" placeholder="Ex: depuis 22h00 la veille"></div>
            <div class="fl"><label>Autres instructions</label><textarea name="med_preop" rows="2"><?= $d('med_preop') ?></textarea></div>
          </div>
          <div class="sub sub-g">
            <div class="sub-t"><i class="bi bi-clipboard-check-fill"></i> Visite pré-opératoire</div>
            <div class="fl" style="margin-bottom:10px"><label>Observations lors de la visite</label><textarea name="evenements_visite" rows="3" placeholder="Anxiété, modification état clinique, douleur…"><?= $d('evenements_visite') ?></textarea></div>
            <div class="fl"><label>Évaluation de la préparation</label><textarea name="eval_preparation" rows="2" placeholder="Préparation adéquate / incomplète…"><?= $d('eval_preparation') ?></textarea></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Footer sticky -->
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
    // champs
    sf('fa',  p.age?`${p.age} ans`:'');
    sv('fs',  p.sexe);
    sv('fgs', p.groupe_sanguin);
    sf('frh', p.groupe_sanguin?(p.groupe_sanguin.includes('+')?'Rh+':'Rh-'):'');
    sf('fpr', p.profession);
    sf('ftel',p.telephone);
    sf('fsv', p.nom_service);
    sf('fad', p.adresse);
    sf('fall',p.allergies);
    sf('faf', p.antecedents_familiaux);
    sf('fap', p.antecedents_medicaux);
    if(i){sf('fint',i.type_intervention||'');sf('fch',i.chirurgien_nom?`Dr. ${i.chirurgien_prenom||''} ${i.chirurgien_nom}`.trim():'');}
    // banner
    const pb=document.getElementById('pb');
    pb.style.display='flex';
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

function imc(){
  const p = parseFloat(document.getElementById('vp')?.value);
  const t = parseFloat(document.getElementById('vt')?.value) / 100;
  const e = document.getElementById('vi');
  if(!e) return;
  if(p > 0 && t > 0){
    const val = (p / (t * t)).toFixed(1);
    e.value = val;
    // Coloration selon seuils OMS
    e.style.color = val < 18.5 ? '#d97706' : val < 25 ? '#16a34a' : val < 30 ? '#f59e0b' : '#dc2626';
    e.style.fontWeight = '700';
  } else {
    e.value = '';
    e.style.color = '';
  }
}

const ASA_INFO = {
  'I':  { label:'Pas de perturbation organique, physiologique, biochimique ou psychiatrique', color:'#16a34a', bg:'#f0fdf4', badge:'Risque minimal' },
  'II': { label:'Affection systémique légère à modérée, sans limitation fonctionnelle',       color:'#2563eb', bg:'#eff6ff', badge:'Risque faible'  },
  'III':{ label:'Affection systémique sévère avec limitation fonctionnelle (cardiopathie, IRC, BPCO, obésité morbide)', color:'#d97706', bg:'#fffbeb', badge:'Risque modéré' },
  'IV': { label:'Affection systémique sévère, menace vitale constante (IC grave, sepsis, CIVD)',    color:'#dc2626', bg:'#fef2f2', badge:'⚠️ Risque élevé' },
  'V':  { label:'Patient moribond — survie improbable sans l\'intervention chirurgicale', color:'#7f1d1d', bg:'#fef2f2', badge:'🔴 Risque critique' },
  'VI': { label:'Patient en mort cérébrale — prélèvement d\'organes', color:'#374151', bg:'#f9fafb', badge:'Mort cérébrale' },
};

let asaUrgent = false;

function updateAsaInfo(){
  const checked = document.querySelector('input[name="asa"]:checked');
  const box     = document.getElementById('asa-info-box');
  const urgInp  = document.getElementById('asa-urgent-input');

  // Sync état urgent dans champ caché
  if(urgInp) urgInp.value = asaUrgent ? '1' : '0';

  // Mettre à jour la classe visuelle sur le bouton sélectionné
  document.querySelectorAll('label.asa-l').forEach(l => l.classList.remove('asa-urgent'));
  if(checked && asaUrgent){
    const lbl = document.querySelector(`label[for="asa${checked.value}"]`);
    if(lbl) lbl.classList.add('asa-urgent');
  }

  if(!box) return;
  if(!checked){ box.style.display='none'; return; }

  const info = ASA_INFO[checked.value];
  if(!info){ box.style.display='none'; return; }
  box.style.display = 'block';

  if(asaUrgent){
    box.style.background   = '#fef2f2';
    box.style.borderColor  = '#dc2626';
    box.innerHTML = `
      <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px;">
        <span style="display:inline-flex;align-items:center;gap:5px;padding:3px 12px;border-radius:20px;font-size:.75rem;font-weight:800;background:#dc2626;color:#fff;letter-spacing:.3px;">
          🚨 URGENCE — ASA ${checked.value}
        </span>
      </div>
      <div style="font-size:.8rem;color:#7f1d1d;font-weight:600;line-height:1.5;">${info.label}</div>
      <div style="font-size:.72rem;color:#dc2626;margin-top:4px;font-weight:700;">Cliquer une 3ème fois pour annuler</div>`;
  } else {
    box.style.background  = info.bg;
    box.style.borderColor = info.color;
    box.innerHTML = `
      <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
        <span style="display:inline-block;padding:2px 10px;border-radius:20px;font-size:.72rem;font-weight:800;background:${info.color};color:#fff;">${info.badge}</span>
        <span style="font-size:.68rem;color:#6b7280;">— Cliquer à nouveau pour marquer URGENT</span>
      </div>
      <div style="font-size:.8rem;color:#374151;line-height:1.5;">${info.label}</div>`;
  }
}

function initAsaHandlers(){
  document.querySelectorAll('label.asa-l').forEach(label => {
    label.addEventListener('click', function(e){
      const input      = document.getElementById(this.getAttribute('for'));
      const wasChecked = input.checked;

      if(wasChecked && !asaUrgent){
        // 2ème clic : urgent
        e.preventDefault();
        asaUrgent = true;
        updateAsaInfo();
      } else if(wasChecked && asaUrgent){
        // 3ème clic : annuler
        e.preventDefault();
        input.checked = false;
        asaUrgent = false;
        updateAsaInfo();
      } else {
        // 1er clic sur un autre bouton
        asaUrgent = false;
        requestAnimationFrame(updateAsaInfo);
      }
    });
  });
}

async function save(st){
  if(!document.getElementById('hpid').value){alert2('Veuillez sélectionner un patient.',false);return;}
  const fd=new FormData(document.getElementById('ff'));
  fd.set('statut',st);
  try{
    const res=await(await fetch(`${B}anesthesie/sauvegarder-pre-anesthesique`,{method:'POST',body:fd})).json();
    if(res.success&&res.fiche_id)document.querySelector('[name=fiche_id]').value=res.fiche_id;
    alert2(res.message,res.success);
  }catch{alert2('Erreur réseau.',false);}
}

function alert2(msg,ok){
  const el=document.getElementById('al');
  el.className=`al ${ok?'ok':'err'}`;
  el.style.display='flex';
  document.getElementById('alIco').className=`bi ${ok?'bi-check-circle-fill':'bi-exclamation-circle-fill'}`;
  document.getElementById('alTxt').textContent=msg;
  el.scrollIntoView({behavior:'smooth',block:'nearest'});
  setTimeout(()=>el.style.display='none',4000);
}

function initPage(){
  imc();
  asaUrgent = (document.getElementById('asa-urgent-input')?.value === '1');
  initAsaHandlers();
  updateAsaInfo();
}
<?php if($patient_id&&!$fiche_id): ?>
window.addEventListener('DOMContentLoaded',()=>{ loadPatient(<?=$patient_id?>).then(initPage); });
<?php elseif($patient): ?>
window.addEventListener('DOMContentLoaded',()=>{
  document.getElementById('pb').style.display='flex';
  document.getElementById('pav').textContent='<?=strtoupper(substr($patient['nom'],0,1).substr($patient['prenom'],0,1))?>';
  document.getElementById('pnm').textContent='<?=htmlspecialchars($patient['nom'].' '.$patient['prenom'],ENT_JS)?>';
  initPage();
});
<?php else: ?>
window.addEventListener('DOMContentLoaded', initPage);
<?php endif ?>
</script>
<?php require_once __DIR__.'/../layouts/footer.php'; ?>
