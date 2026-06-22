<?php
require_once __DIR__ . '/../layouts/header.php';
$nom     = strtoupper($patient['nom'] ?? '');
$prenom  = $patient['prenom'] ?? '';
$dossier = $patient['dossier_numero'] ?? '';
$sexe    = $patient['sexe'] ?? 'M';
$chambre = $patient['nom_chambre'] ?? '';
$lit     = $patient['nom_lit'] ?? '';
$age     = NeonatologyController::ageNeonat($patient['date_naissance'] ?? null);
$uNom    = strtoupper(trim(($_SESSION['user_nom'] ?? '') . ' ' . ($_SESSION['user_prenom'] ?? '')));

$produitsLabels = [
    'CULOT_GR'   => 'Culot globulaire (CGR)',
    'PFC'        => 'Plasma frais congelé (PFC)',
    'PLAQUETTES' => 'Concentré de plaquettes',
    'SANG_TOTAL' => 'Sang total',
    'ALBUMINE'   => 'Albumine',
    'AUTRE'      => 'Autre',
];
?>
<style>
.sidebar,nav.sidebar{display:none!important}
main,.col-md-10,.ms-sm-auto{margin-left:0!important;width:100%!important;flex:0 0 100%!important;max-width:100%!important;padding:0!important}
body{background:#fdf2f2;font-family:'Segoe UI',system-ui,sans-serif;color:#1e293b}

.tr-top{background:linear-gradient(135deg,#b91c1c,#dc2626 55%,#ef4444);padding:14px 28px;
  display:flex;align-items:center;justify-content:space-between;
  position:sticky;top:0;z-index:100;box-shadow:0 4px 24px rgba(185,28,28,.35)}
.tr-title{font-size:1.05rem;font-weight:900;color:#fff;display:flex;align-items:center;gap:10px}
.tr-sub{font-size:.65rem;color:rgba(255,255,255,.8);font-weight:600;text-transform:uppercase;letter-spacing:1px;margin-top:2px}
.tr-btn{display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border-radius:10px;
  font-weight:700;font-size:.76rem;text-decoration:none;border:1px solid rgba(255,255,255,.35);
  background:rgba(255,255,255,.18);color:#fff;transition:all .18s;cursor:pointer}
.tr-btn:hover{background:rgba(255,255,255,.3);color:#fff}

.tr-wrap{padding:22px 28px;max-width:960px;margin:0 auto}

.tr-banner{background:#fff;border-radius:18px;border:1px solid #fee2e2;
  box-shadow:0 4px 20px rgba(185,28,28,.08);padding:18px 24px;margin-bottom:20px;
  display:flex;align-items:center;gap:16px}
.tr-av{width:54px;height:54px;border-radius:16px;display:flex;align-items:center;
  justify-content:center;font-size:1.5rem;color:#fff;flex-shrink:0;
  background:linear-gradient(135deg,#dc2626,#ef4444)}
.tr-pname{font-size:1.1rem;font-weight:900;color:#0f172a}
.tr-pmeta{font-size:.78rem;color:#64748b;margin-top:3px;display:flex;flex-wrap:wrap;gap:8px}
.tr-pmeta i{color:#dc2626}

.tr-panel{background:#fff;border-radius:18px;border:1px solid #fee2e2;
  box-shadow:0 2px 12px rgba(185,28,28,.06);margin-bottom:18px;overflow:hidden}
.tr-ph{padding:14px 22px;border-bottom:2px solid #fee2e2;background:linear-gradient(90deg,#fff5f5,#fff);
  font-size:.8rem;font-weight:800;color:#b91c1c;text-transform:uppercase;letter-spacing:.6px;
  display:flex;align-items:center;gap:8px}

.tr-form-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:14px;padding:20px 22px}
.tr-lbl{font-size:.68rem;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.4px;margin-bottom:5px}
.tr-ctrl{width:100%;border:2px solid #e2e8f0;border-radius:11px;padding:9px 13px;
  font-size:.85rem;font-family:inherit;transition:.18s;background:#fff}
.tr-ctrl:focus{border-color:#dc2626;outline:none;box-shadow:0 0 0 3px rgba(220,38,38,.1)}
.tr-ctrl-full{grid-column:1/-1}
.tr-vitals-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;padding:0 22px 20px}
.tr-section-lbl{padding:12px 22px 6px;font-size:.72rem;font-weight:800;
  text-transform:uppercase;letter-spacing:.5px}
.tr-section-lbl.pre{color:#b45309}
.tr-section-lbl.post{color:#0e7490}
.tr-vital-card{border:2px solid #e2e8f0;border-radius:12px;padding:10px 12px}
.tr-vital-card.pre{border-color:#fde68a;background:#fffbeb}
.tr-vital-card.post{border-color:#a5f3fc;background:#ecfeff}
.tr-vital-lbl{font-size:.62rem;font-weight:800;color:#94a3b8;text-transform:uppercase;margin-bottom:4px}
.tr-vital-input{width:100%;border:none;background:transparent;font-size:.95rem;font-weight:800;color:#0f172a;outline:none}

.tr-submit{background:linear-gradient(135deg,#b91c1c,#dc2626);color:#fff;border:none;
  border-radius:14px;padding:13px 28px;font-weight:900;font-size:.9rem;cursor:pointer;
  display:inline-flex;align-items:center;gap:8px;transition:.2s;margin:0 22px 20px}
.tr-submit:hover{background:linear-gradient(135deg,#991b1b,#b91c1c);transform:translateY(-1px)}

.tr-flash{padding:12px 18px;border-radius:12px;margin-bottom:16px;font-size:.84rem;font-weight:600;display:flex;align-items:center;gap:10px}
.tr-flash.ok{background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0}
.tr-flash.err{background:#fef2f2;color:#dc2626;border:1px solid #fca5a5}

.tr-tbl{width:100%;border-collapse:collapse}
.tr-tbl th{background:linear-gradient(90deg,#fff5f5,#fff);color:#b91c1c;font-size:.67rem;
  text-transform:uppercase;letter-spacing:.5px;font-weight:800;padding:10px 16px;text-align:left}
.tr-tbl td{padding:10px 16px;border-bottom:1px solid #f1f5f9;font-size:.82rem;vertical-align:middle}
.tr-tbl tr:hover td{background:#fff5f5}
.tr-badge{padding:3px 10px;border-radius:20px;font-size:.68rem;font-weight:800}
.tr-empty{padding:32px;text-align:center;color:#94a3b8;font-size:.85rem}
.tr-empty i{font-size:2rem;opacity:.2;display:block;margin-bottom:8px}

/* médecins select */
.tr-medecins{grid-column:1/-1}
</style>

<main>
<!-- ── Topbar ──────────────────────────────────────────── -->
<div class="tr-top">
  <div>
    <div class="tr-title"><i class="bi bi-droplet-half"></i> Fiche transfusionnelle — BB <?= htmlspecialchars("$nom $prenom") ?></div>
    <div class="tr-sub">Unité de Néonatologie · HSJM · <?= htmlspecialchars($dossier) ?></div>
  </div>
  <div class="d-flex gap-2 flex-wrap">
    <a href="<?= BASE_URL ?>neonatologie/suivi/<?= (int)$patient['id'] ?>" class="tr-btn"><i class="bi bi-activity"></i> Suivi</a>
    <a href="<?= BASE_URL ?>neonatologie" class="tr-btn"><i class="bi bi-arrow-left"></i></a>
  </div>
</div>

<div class="tr-wrap">

  <?php if ($flash): ?>
  <div class="tr-flash <?= $flash['type'] ?>">
    <i class="bi bi-<?= $flash['type']==='ok'?'check-circle-fill':'exclamation-circle-fill' ?>"></i>
    <?= htmlspecialchars($flash['msg']) ?>
  </div>
  <?php endif ?>

  <!-- ── Bannière patient ─────────────────────── -->
  <div class="tr-banner">
    <div class="tr-av"><?= $sexe==='F'?'♀':'♂' ?></div>
    <div>
      <div class="tr-pname">BB <?= htmlspecialchars("$nom $prenom") ?></div>
      <div class="tr-pmeta">
        <span><i class="bi bi-file-medical"></i><?= htmlspecialchars($dossier) ?></span>
        <span><i class="bi bi-clock"></i><?= htmlspecialchars($age) ?></span>
        <?php if($chambre): ?><span><i class="bi bi-geo-alt-fill"></i><?= htmlspecialchars("$chambre · $lit") ?></span><?php endif ?>
        <span style="background:#fee2e2;color:#b91c1c;padding:2px 10px;border-radius:20px;font-weight:800;">
          <i class="bi bi-droplet-half"></i> <?= count($transfusions) ?> transfusion(s)
        </span>
      </div>
    </div>
  </div>

  <!-- ── Formulaire ───────────────────────────── -->
  <div class="tr-panel">
    <div class="tr-ph"><i class="bi bi-plus-circle-fill"></i> Nouvelle transfusion</div>
    <form method="POST" action="<?= BASE_URL ?>neonatologie/transfusion-sauvegarder">
      <input type="hidden" name="patient_id" value="<?= (int)$patient['id'] ?>">

      <!-- Identité / produit -->
      <div class="tr-form-grid">
        <div>
          <div class="tr-lbl">Date &amp; heure</div>
          <input type="datetime-local" name="date_transfusion" class="tr-ctrl" value="<?= date('Y-m-d\TH:i') ?>">
        </div>
        <div>
          <div class="tr-lbl">Produit sanguin</div>
          <select name="produit_sanguin" class="tr-ctrl">
            <option value="">— Choisir —</option>
            <?php foreach ($produitsLabels as $v=>$l): ?>
            <option value="<?= $v ?>"><?= $l ?></option>
            <?php endforeach ?>
          </select>
        </div>
        <div>
          <div class="tr-lbl">Volume (mL)</div>
          <input type="number" name="volume_ml" class="tr-ctrl" placeholder="ex: 20" min="0">
        </div>
        <div>
          <div class="tr-lbl">N° poche / lot</div>
          <input type="text" name="numero_poche" class="tr-ctrl" placeholder="ex: P2024-00123">
        </div>
        <div>
          <div class="tr-lbl">Groupe patient</div>
          <select name="groupe_patient" class="tr-ctrl">
            <option value="">—</option>
            <?php foreach(['A','B','AB','O'] as $g): ?>
            <option><?= $g ?></option>
            <?php endforeach ?>
          </select>
        </div>
        <div>
          <div class="tr-lbl">Rh patient</div>
          <select name="rhesus_patient" class="tr-ctrl">
            <option value="">—</option>
            <option value="POS">Positif (+)</option>
            <option value="NEG">Négatif (−)</option>
          </select>
        </div>
        <div>
          <div class="tr-lbl">Groupe donneur</div>
          <select name="groupe_donneur" class="tr-ctrl">
            <option value="">—</option>
            <?php foreach(['A','B','AB','O'] as $g): ?>
            <option><?= $g ?></option>
            <?php endforeach ?>
          </select>
        </div>
        <div>
          <div class="tr-lbl">Rh donneur</div>
          <select name="rhesus_donneur" class="tr-ctrl">
            <option value="">—</option>
            <option value="POS">Positif (+)</option>
            <option value="NEG">Négatif (−)</option>
          </select>
        </div>
        <div class="tr-ctrl-full">
          <div class="tr-lbl">Indication / Motif</div>
          <textarea name="indication" class="tr-ctrl" rows="2" placeholder="Ex : Anémie sévère Hb &lt; 8 g/dL, détresse respiratoire…"></textarea>
        </div>
      </div>

      <!-- Constantes pré-transfusion -->
      <div class="tr-section-lbl pre"><i class="bi bi-arrow-up-circle-fill"></i> Constantes pré-transfusion</div>
      <div class="tr-vitals-grid">
        <?php foreach([['pre_temp','T° (°C)','36.5'],['pre_fc','FC (bpm)','120'],['pre_fr','FR (/min)','40'],['pre_spo2','SpO₂ (%)','98']] as [$n,$l,$ph]): ?>
        <div class="tr-vital-card pre">
          <div class="tr-vital-lbl"><?= $l ?></div>
          <input type="number" name="<?= $n ?>" class="tr-vital-input" placeholder="<?= $ph ?>" step="0.1">
        </div>
        <?php endforeach ?>
      </div>

      <!-- Constantes post-transfusion -->
      <div class="tr-section-lbl post"><i class="bi bi-arrow-down-circle-fill"></i> Constantes post-transfusion</div>
      <div class="tr-vitals-grid">
        <?php foreach([['post_temp','T° (°C)','—'],['post_fc','FC (bpm)','—'],['post_fr','FR (/min)','—'],['post_spo2','SpO₂ (%)','—']] as [$n,$l,$ph]): ?>
        <div class="tr-vital-card post">
          <div class="tr-vital-lbl"><?= $l ?></div>
          <input type="number" name="<?= $n ?>" class="tr-vital-input" placeholder="<?= $ph ?>" step="0.1">
        </div>
        <?php endforeach ?>
      </div>

      <!-- Réactions + signature -->
      <div class="tr-form-grid">
        <div class="tr-ctrl-full">
          <div class="tr-lbl">Réactions / Incidents transfusionnels</div>
          <textarea name="reactions" class="tr-ctrl" rows="2" placeholder="Aucune réaction — ou décrire : fièvre, frissons, urticaire, dyspnée…"></textarea>
        </div>
        <div>
          <div class="tr-lbl">Émargement infirmier</div>
          <input type="text" name="emargement" class="tr-ctrl" value="<?= htmlspecialchars($uNom) ?>">
        </div>
      </div>

      <button type="submit" class="tr-submit"><i class="bi bi-check-circle-fill"></i> Enregistrer la transfusion</button>
    </form>
  </div>

  <!-- ── Historique ────────────────────────────── -->
  <div class="tr-panel">
    <div class="tr-ph"><i class="bi bi-clock-history"></i> Historique — <?= count($transfusions) ?> transfusion(s)</div>
    <?php if (empty($transfusions)): ?>
    <div class="tr-empty"><i class="bi bi-droplet"></i>Aucune transfusion enregistrée</div>
    <?php else: ?>
    <div style="overflow-x:auto;">
      <table class="tr-tbl">
        <thead><tr>
          <th>Date</th><th>Produit</th><th>Vol. mL</th>
          <th>Patient</th><th>Donneur</th>
          <th>Pré T°/FC/SpO₂</th><th>Post T°/FC/SpO₂</th>
          <th>Réactions</th><th>Infirmier(e)</th>
        </tr></thead>
        <tbody>
        <?php foreach ($transfusions as $tr): ?>
        <tr>
          <td style="white-space:nowrap;font-weight:700;color:#b91c1c;"><?= date('d/m/Y H:i', strtotime($tr['date_transfusion'])) ?></td>
          <td><?= htmlspecialchars($produitsLabels[$tr['produit_sanguin'] ?? ''] ?? ($tr['produit_sanguin'] ?? '—')) ?></td>
          <td style="font-weight:800;"><?= $tr['volume_ml'] ? $tr['volume_ml'].' mL' : '—' ?></td>
          <td style="white-space:nowrap;"><?= htmlspecialchars(($tr['groupe_patient']??'?').' '.($tr['rhesus_patient']==='POS'?'+':($tr['rhesus_patient']==='NEG'?'−':'?'))) ?></td>
          <td style="white-space:nowrap;"><?= htmlspecialchars(($tr['groupe_donneur']??'?').' '.($tr['rhesus_donneur']==='POS'?'+':($tr['rhesus_donneur']==='NEG'?'−':'?'))) ?></td>
          <td style="font-size:.78rem;"><?= $tr['pre_temp']??'—' ?> / <?= $tr['pre_fc']??'—' ?> / <?= $tr['pre_spo2']??'—' ?></td>
          <td style="font-size:.78rem;"><?= $tr['post_temp']??'—' ?> / <?= $tr['post_fc']??'—' ?> / <?= $tr['post_spo2']??'—' ?></td>
          <td style="font-size:.78rem;max-width:140px;"><?= $tr['reactions'] ? htmlspecialchars(mb_substr($tr['reactions'],0,60)) : '<span style="color:#16a34a;">Aucune</span>' ?></td>
          <td style="font-size:.78rem;"><?= $tr['inf_nom'] ? htmlspecialchars(($tr['inf_prenom']??'').' '.$tr['inf_nom']) : htmlspecialchars($tr['emargement']??'—') ?></td>
        </tr>
        <?php endforeach ?>
        </tbody>
      </table>
    </div>
    <?php endif ?>
  </div>

</div>
</main>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
