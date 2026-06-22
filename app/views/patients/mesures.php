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
$context = $context ?? '';
?>

<?php if ($context === 'urgences'): ?>
<!-- ══════════════════════════════════════════════════════════════════════════
     PRISE DE PARAMÈTRES — CONTEXTE URGENCES (layout cockpit, sans sidebar)
══════════════════════════════════════════════════════════════════════════ -->
<style>
body { background: #f0f4f9; margin: 0; }
main { margin-left: 0 !important; width: 100% !important; }

/* ── Header bande ── */
.pm-hdr {
  position: sticky; top: 0; z-index: 50;
  background: #fff; border-bottom: 1px solid #e2e8f0;
  padding: 14px 24px; display: flex; align-items: center; gap: 16px;
  box-shadow: 0 1px 4px rgba(15,23,42,.06);
}
.pm-logo {
  width: 42px; height: 42px; border-radius: 11px; flex-shrink: 0;
  background: linear-gradient(135deg, #dc2626, #2563eb);
  display: flex; align-items: center; justify-content: center;
  color: #fff; font-size: 19px;
}
.pm-hdr h1 { margin: 0; font-size: 1rem; font-weight: 800; color: #0f172a; }
.pm-hdr .sub { font-size: .78rem; color: #64748b; font-weight: 600; margin-top: 1px; }
.pm-hdr .spacer { flex: 1; }
.pm-patient-pill {
  display: flex; align-items: center; gap: 10px;
  background: #f8fafc; border: 1.5px solid #e2e8f0;
  border-radius: 50px; padding: 7px 16px 7px 10px;
}
.pm-patient-pill .av {
  width: 32px; height: 32px; border-radius: 50%;
  background: linear-gradient(135deg, #1e40af, #2563eb);
  color: #fff; display: flex; align-items: center; justify-content: center;
  font-weight: 800; font-size: .85rem;
}
.pm-patient-pill .name { font-weight: 800; font-size: .88rem; color: #1e293b; }
.pm-patient-pill .dossier { font-size: .72rem; color: #64748b; }
.pm-back {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 8px 14px; border-radius: 9px;
  border: 1.5px solid #e2e8f0; background: #fff;
  font-size: .82rem; font-weight: 700; color: #475569;
  text-decoration: none; transition: all .15s;
}
.pm-back:hover { border-color: #2563eb; color: #2563eb; }

/* ── Layout principal ── */
.pm-wrap { max-width: 900px; margin: 0 auto; padding: 28px 20px 60px; }

/* ── Bandeau triage ── */
.pm-triage-row {
  display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 20px;
}
.triage-pill {
  flex: 1; min-width: 110px; cursor: pointer;
  border: 2px solid transparent; border-radius: 12px; padding: 11px 10px;
  text-align: center; transition: all .15s; position: relative;
}
.triage-pill input[type="radio"] { position: absolute; opacity: 0; pointer-events: none; }
.triage-pill .tp-label { font-size: .78rem; font-weight: 800; text-transform: uppercase; letter-spacing: .04em; }
.triage-pill .tp-sub { font-size: .68rem; font-weight: 600; margin-top: 2px; opacity: .75; }
.triage-pill.p1 { background: #fff1f2; border-color: #fecdd3; color: #be123c; }
.triage-pill.p1.sel { background: #e11d48; color: #fff; border-color: #e11d48; box-shadow: 0 4px 14px rgba(225,29,72,.3); }
.triage-pill.p2 { background: #fff7ed; border-color: #fed7aa; color: #c2410c; }
.triage-pill.p2.sel { background: #ea580c; color: #fff; border-color: #ea580c; box-shadow: 0 4px 14px rgba(234,88,12,.3); }
.triage-pill.p3 { background: #fefce8; border-color: #fde68a; color: #a16207; }
.triage-pill.p3.sel { background: #ca8a04; color: #fff; border-color: #ca8a04; box-shadow: 0 4px 14px rgba(202,138,4,.3); }
.triage-pill.p4 { background: #f0fdf4; border-color: #bbf7d0; color: #15803d; }
.triage-pill.p4.sel { background: #16a34a; color: #fff; border-color: #16a34a; box-shadow: 0 4px 14px rgba(22,163,74,.3); }
.triage-pill.p5 { background: #eff6ff; border-color: #bfdbfe; color: #1d4ed8; }
.triage-pill.p5.sel { background: #2563eb; color: #fff; border-color: #2563eb; box-shadow: 0 4px 14px rgba(37,99,235,.3); }

/* ── Sections ── */
.pm-card {
  background: #fff; border-radius: 18px; border: 1px solid #e8edf4;
  box-shadow: 0 2px 10px rgba(15,23,42,.04); margin-bottom: 18px; overflow: hidden;
}
.pm-card-hdr {
  padding: 14px 20px; border-bottom: 1px solid #f1f5f9;
  display: flex; align-items: center; gap: 10px;
}
.pm-card-hdr .ic {
  width: 34px; height: 34px; border-radius: 9px;
  display: flex; align-items: center; justify-content: center; font-size: .95rem;
}
.pm-card-hdr h2 { margin: 0; font-size: .88rem; font-weight: 800; color: #0f172a; }
.pm-card-body { padding: 20px; }

/* ── Champs vitaux ── */
.pm-field { display: flex; flex-direction: column; }
.pm-label {
  font-size: .72rem; font-weight: 700; color: #64748b;
  text-transform: uppercase; letter-spacing: .05em; margin-bottom: 6px;
}
.pm-input {
  background: #f8fafc; border: 1.5px solid #e2e8f0;
  border-radius: 11px; padding: 11px 14px;
  font-size: .95rem; font-weight: 700; color: #1e293b;
  font-family: inherit; width: 100%; transition: all .2s; outline: none;
}
.pm-input:focus { border-color: #2563eb; background: #fff; box-shadow: 0 0 0 3px rgba(37,99,235,.1); }
.pm-input::placeholder { color: #94a3b8; font-weight: 500; }
.pm-input-group { display: flex; align-items: center; gap: 6px; }
.pm-input-group .pm-input { flex: 1; }
.pm-input-group .sep { font-weight: 800; color: #94a3b8; font-size: 1.1rem; flex-shrink: 0; }
.pm-unit { font-size: .72rem; color: #94a3b8; font-weight: 600; margin-top: 4px; }

/* ── NEWS2 badge ── */
.news2-display {
  background: linear-gradient(135deg, #0f172a, #1e293b);
  border-radius: 14px; padding: 18px 22px;
  display: flex; align-items: center; gap: 16px; color: #fff;
}
.news2-score {
  font-size: 3rem; font-weight: 900; font-family: monospace;
  line-height: 1; min-width: 60px; text-align: center;
}
.news2-label { font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; opacity: .6; }
.news2-risk { font-size: .92rem; font-weight: 800; margin-top: 4px; }
.news2-detail { font-size: .75rem; opacity: .6; margin-top: 2px; }

/* ── Boutons orientation ── */
.orientation-section { margin-top: 24px; }
.orientation-section h3 {
  font-size: .8rem; font-weight: 700; color: #64748b;
  text-transform: uppercase; letter-spacing: .06em; margin-bottom: 14px;
  display: flex; align-items: center; gap: 8px;
}
.orientation-section h3::after {
  content: ''; flex: 1; height: 1px; background: #e2e8f0;
}
.orientation-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
@media (max-width: 600px) { .orientation-grid { grid-template-columns: 1fr; } }

.btn-orient {
  display: flex; align-items: center; gap: 14px; padding: 16px 18px;
  border-radius: 15px; border: none; font-family: inherit;
  cursor: pointer; transition: all .18s; text-align: left; width: 100%;
}
.btn-orient .oi {
  width: 44px; height: 44px; border-radius: 12px; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center; font-size: 1.15rem;
}
.btn-orient .ol .t { font-size: .92rem; font-weight: 800; display: block; }
.btn-orient .ol .s { font-size: .75rem; font-weight: 500; opacity: .72; display: block; margin-top: 3px; }
.btn-orient .arr { margin-left: auto; font-size: .85rem; opacity: .4; flex-shrink: 0; }

.btn-salle-att { background: #eff6ff; color: #1e40af; border: 1.5px solid #bfdbfe; }
.btn-salle-att:hover { background: #dbeafe; border-color: #93c5fd; transform: translateY(-2px); box-shadow: 0 6px 20px rgba(37,99,235,.18); color: #1e40af; }
.btn-salle-att .oi { background: #dbeafe; color: #2563eb; }

.btn-consult-dir { background: linear-gradient(135deg, #065f46, #059669); color: #fff; }
.btn-consult-dir:hover { filter: brightness(1.08); transform: translateY(-2px); box-shadow: 0 6px 20px rgba(5,150,105,.35); color: #fff; }
.btn-consult-dir .oi { background: rgba(255,255,255,.2); color: #fff; }
.btn-consult-dir .ol .s { opacity: .82; }
</style>

<!-- ── Header ── -->
<div class="pm-hdr">
  <div class="pm-logo"><i class="bi bi-heart-pulse-fill"></i></div>
  <div>
    <h1>Prise de Paramètres Vitaux</h1>
    <div class="sub">Urgences · Infirmier</div>
  </div>
  <div class="spacer"></div>
  <!-- Pill patient -->
  <div class="pm-patient-pill">
    <div class="av"><?= strtoupper(substr($patient['prenom'] ?? 'P', 0, 1)) ?></div>
    <div>
      <div class="name"><?= strtoupper(htmlspecialchars($patient['nom'] ?? '')) ?> <?= htmlspecialchars($patient['prenom'] ?? '') ?></div>
      <div class="dossier"><?= htmlspecialchars($patient['dossier_numero'] ?? '') ?></div>
    </div>
  </div>
  <a href="<?= BASE_URL ?>urgences" class="pm-back"><i class="bi bi-arrow-left"></i> Urgences</a>
</div>

<!-- ── Corps ── -->
<div class="pm-wrap">

  <?php if (!empty($rdvAujourdhui)): ?>
  <?php
    require_once __DIR__ . '/../../controllers/SpecialisteController.php';
    $specLabel = SpecialisteController::LABELS[$rdvAujourdhui['specialite']] ?? $rdvAujourdhui['specialite'];
    $quotaOk   = ($rdvAujourdhui['quota_actuel'] ?? 0) < ($rdvAujourdhui['quota_max'] ?? 99);
  ?>
  <div class="d-flex align-items-center gap-3 mb-4 p-3 rounded-3" style="background:#f5f3ff;border:1.5px solid #ddd6fe;">
    <div style="width:40px;height:40px;border-radius:10px;background:linear-gradient(135deg,#5b21b6,#7c3aed);display:flex;align-items:center;justify-content:center;color:#fff;font-size:1rem;flex-shrink:0;">
      <i class="bi bi-person-badge-fill"></i></div>
    <div class="flex-grow-1">
      <div class="fw-bold" style="color:#5b21b6;font-size:.85rem;">RDV Spécialiste aujourd'hui</div>
      <div class="fw-semibold small"><?= htmlspecialchars($specLabel) ?> — Dr <?= htmlspecialchars($rdvAujourdhui['spec_nom'].' '.$rdvAujourdhui['spec_prenom']) ?></div>
    </div>
    <?php if ($quotaOk): ?>
    <button class="btn btn-sm fw-bold text-white px-3" style="background:#7c3aed;border-radius:9px;"
            onclick="orienterSpecialiste(<?= (int)$rdvAujourdhui['id'] ?>, <?= (int)$patient['id'] ?>, this)">
      <i class="bi bi-arrow-right-circle-fill me-1"></i>Orienter
    </button>
    <?php else: ?>
    <span class="badge text-danger" style="background:#fef2f2;border:1px solid #fecaca;font-size:.72rem;">Quota atteint</span>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <form action="<?= BASE_URL ?>patients/save-mesures" method="POST" id="pmForm">
    <input type="hidden" name="patient_id" value="<?= (int)$patient['id'] ?>">
    <input type="hidden" name="context" value="urgences">
    <input type="hidden" name="redirect_after" id="redirectAfter" value="salle_attente">

    <!-- ── Niveau de triage ── -->
    <div class="pm-card">
      <div class="pm-card-hdr">
        <div class="ic" style="background:#fff1f2;color:#dc2626;"><i class="bi bi-lightning-charge-fill"></i></div>
        <h2>Niveau de triage</h2>
      </div>
      <div class="pm-card-body">
        <div class="pm-triage-row" id="triageRow">
          <?php
          $niveaux = [
            1 => ['cls'=>'p1','label'=>'P1','sub'=>'Vital'],
            2 => ['cls'=>'p2','label'=>'P2','sub'=>'Urgent'],
            3 => ['cls'=>'p3','label'=>'P3','sub'=>'Stable','default'=>true],
            4 => ['cls'=>'p4','label'=>'P4','sub'=>'Mineur'],
            5 => ['cls'=>'p5','label'=>'P5','sub'=>'Surveillance'],
          ];
          foreach ($niveaux as $n => $nv):
          ?>
          <label class="triage-pill <?= $nv['cls'] ?><?= !empty($nv['default']) ? ' sel' : '' ?>" onclick="selectTriage(<?= $n ?>, this)">
            <input type="radio" name="niveau_triage" value="<?= $n ?>" <?= !empty($nv['default']) ? 'checked' : '' ?>>
            <div class="tp-label"><?= $nv['label'] ?></div>
            <div class="tp-sub"><?= $nv['sub'] ?></div>
          </label>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- ── Motif ── -->
    <div class="pm-card">
      <div class="pm-card-hdr">
        <div class="ic" style="background:#fffbeb;color:#d97706;"><i class="bi bi-chat-text-fill"></i></div>
        <h2>Motif de venue aux urgences</h2>
      </div>
      <div class="pm-card-body">
        <input type="text" name="motif_admission" class="pm-input"
               placeholder="Ex : Douleur thoracique, traumatisme crânien, fièvre élevée…">
      </div>
    </div>

    <!-- ── Paramètres vitaux ── -->
    <div class="pm-card">
      <div class="pm-card-hdr">
        <div class="ic" style="background:#eff6ff;color:#2563eb;"><i class="bi bi-activity"></i></div>
        <h2>Paramètres vitaux</h2>
      </div>
      <div class="pm-card-body">
        <div class="row g-3">
          <!-- Température -->
          <div class="col-md-4 col-6">
            <div class="pm-field">
              <label class="pm-label"><i class="bi bi-thermometer-half text-danger me-1"></i>Température</label>
              <input type="number" step="0.1" min="34" max="43" name="temperature" id="pmTemp"
                     class="pm-input" placeholder="37.0" oninput="calcNEWS2()">
              <span class="pm-unit">°C</span>
            </div>
          </div>
          <!-- Pouls -->
          <div class="col-md-4 col-6">
            <div class="pm-field">
              <label class="pm-label"><i class="bi bi-heart-pulse text-danger me-1"></i>Pouls</label>
              <input type="number" min="20" max="250" name="frequence_cardiaque" id="pmPouls"
                     class="pm-input" placeholder="80" oninput="calcNEWS2()">
              <span class="pm-unit">bpm</span>
            </div>
          </div>
          <!-- SpO2 -->
          <div class="col-md-4 col-6">
            <div class="pm-field">
              <label class="pm-label"><i class="bi bi-lungs text-primary me-1"></i>SpO₂</label>
              <input type="number" min="50" max="100" name="spo2" id="pmSpo2"
                     class="pm-input" placeholder="98" oninput="calcNEWS2()">
              <span class="pm-unit">%</span>
            </div>
          </div>
          <!-- Tension -->
          <div class="col-md-4 col-6">
            <div class="pm-field">
              <label class="pm-label"><i class="bi bi-droplet-fill text-danger me-1"></i>Tension artérielle</label>
              <div class="pm-input-group">
                <input type="number" min="50" max="300" name="tension_sys" id="pmSys"
                       class="pm-input" placeholder="120" oninput="calcNEWS2()">
                <span class="sep">/</span>
                <input type="number" min="20" max="200" name="tension_dia"
                       class="pm-input" placeholder="80">
              </div>
              <span class="pm-unit">mmHg — Sys / Dia</span>
            </div>
          </div>
          <!-- Poids -->
          <div class="col-md-4 col-6">
            <div class="pm-field">
              <label class="pm-label"><i class="bi bi-person-standing me-1"></i>Poids</label>
              <input type="number" step="0.1" min="1" max="300" name="poids"
                     class="pm-input" placeholder="70">
              <span class="pm-unit">kg</span>
            </div>
          </div>
          <!-- Taille -->
          <div class="col-md-4 col-6">
            <div class="pm-field">
              <label class="pm-label"><i class="bi bi-rulers me-1"></i>Taille</label>
              <input type="number" min="30" max="250" name="taille"
                     class="pm-input" placeholder="170">
              <span class="pm-unit">cm</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ── NEWS2 ── -->
    <div class="pm-card">
      <div class="pm-card-hdr">
        <div class="ic" style="background:#0f172a;color:#60a5fa;"><i class="bi bi-graph-up-arrow"></i></div>
        <h2>Score NEWS2 (automatique)</h2>
      </div>
      <div class="pm-card-body">
        <div class="news2-display" id="news2Display">
          <div>
            <div class="news2-score" id="news2Score">—</div>
            <div class="news2-label">NEWS2</div>
          </div>
          <div>
            <div class="news2-risk" id="news2Risk">Renseignez les vitaux</div>
            <div class="news2-detail" id="news2Detail">Température · Pouls · SpO₂ · Tension systolique</div>
          </div>
        </div>
      </div>
    </div>

    <!-- ── Orientation ── -->
    <div class="orientation-section">
      <h3>Orientation du patient</h3>
      <div class="orientation-grid">
        <button type="submit" class="btn-orient btn-salle-att"
                onclick="document.getElementById('redirectAfter').value='salle_attente'">
          <span class="oi"><i class="bi bi-people-fill"></i></span>
          <span class="ol">
            <span class="t">Salle d'attente urgences</span>
            <span class="s">Visible par tous les médecins urgentistes</span>
          </span>
          <i class="bi bi-chevron-right arr"></i>
        </button>
        <button type="submit" class="btn-orient btn-consult-dir"
                onclick="document.getElementById('redirectAfter').value='consultation_directe'">
          <span class="oi"><i class="bi bi-stethoscope"></i></span>
          <span class="ol">
            <span class="t">Consultation directe</span>
            <span class="s">Ouvrir la consultation infirmière maintenant</span>
          </span>
          <i class="bi bi-chevron-right arr"></i>
        </button>
      </div>
    </div>

  </form>
</div>

<script>
/* ── Sélection triage ── */
function selectTriage(n, el) {
  document.querySelectorAll('.triage-pill').forEach(p => p.classList.remove('sel'));
  el.classList.add('sel');
  el.querySelector('input[type="radio"]').checked = true;
}

/* ── Calcul NEWS2 simplifié ── */
function calcNEWS2() {
  const t   = parseFloat(document.getElementById('pmTemp')?.value);
  const p   = parseInt(document.getElementById('pmPouls')?.value);
  const spo = parseInt(document.getElementById('pmSpo2')?.value);
  const sys = parseInt(document.getElementById('pmSys')?.value);

  const missing = [t,p,spo,sys].filter(isNaN).length;
  if (missing >= 3) {
    document.getElementById('news2Score').textContent = '—';
    document.getElementById('news2Risk').textContent  = 'Renseignez les vitaux';
    document.getElementById('news2Detail').textContent = 'Température · Pouls · SpO₂ · Tension systolique';
    return;
  }

  let score = 0;
  // Température
  if (!isNaN(t)) {
    if (t <= 35.0)            score += 3;
    else if (t <= 36.0)       score += 1;
    else if (t <= 38.0)       score += 0;
    else if (t <= 39.0)       score += 1;
    else                      score += 2;
  }
  // Pouls
  if (!isNaN(p)) {
    if (p <= 40)              score += 3;
    else if (p <= 50)         score += 1;
    else if (p <= 90)         score += 0;
    else if (p <= 110)        score += 1;
    else if (p <= 130)        score += 2;
    else                      score += 3;
  }
  // SpO2
  if (!isNaN(spo)) {
    if (spo <= 91)            score += 3;
    else if (spo <= 93)       score += 2;
    else if (spo <= 95)       score += 1;
    else                      score += 0;
  }
  // Tension systolique
  if (!isNaN(sys)) {
    if (sys <= 90)            score += 3;
    else if (sys <= 100)      score += 2;
    else if (sys <= 110)      score += 1;
    else if (sys <= 219)      score += 0;
    else                      score += 3;
  }

  document.getElementById('news2Score').textContent = score;

  let risk, detail, bg;
  if (score === 0)          { risk = 'Risque faible';       detail = 'Surveillance standard';     bg = 'linear-gradient(135deg,#064e3b,#065f46)'; }
  else if (score <= 4)      { risk = 'Risque faible';       detail = 'Surveillance toutes 4-6h';  bg = 'linear-gradient(135deg,#064e3b,#065f46)'; }
  else if (score <= 6)      { risk = 'Risque modéré';       detail = 'Surveillance horaire — alert médecin'; bg = 'linear-gradient(135deg,#78350f,#92400e)'; }
  else                      { risk = 'Risque élevé 🚨';     detail = 'Surveillance continue — urgence';      bg = 'linear-gradient(135deg,#7f1d1d,#991b1b)'; }

  document.getElementById('news2Risk').textContent   = risk;
  document.getElementById('news2Detail').textContent = detail;
  document.getElementById('news2Display').style.background = bg;
}

<?php if (!empty($rdvAujourdhui)): ?>
function orienterSpecialiste(rdvId, patientId, btn) {
  if (!confirm('Orienter ce patient vers le spécialiste ?')) return;
  btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
  fetch('<?= BASE_URL ?>specialiste/orienter/' + rdvId, {
    method: 'POST',
    headers: {'Content-Type':'application/x-www-form-urlencoded','X-Requested-With':'XMLHttpRequest'},
    body: 'patient_id=' + patientId
  }).then(r => r.json()).then(data => {
    if (data.success) btn.closest('[style]').remove();
    else { alert('Erreur : ' + (data.message || 'Impossible d\'orienter.')); btn.disabled = false; btn.innerHTML = '<i class="bi bi-arrow-right-circle-fill me-1"></i>Orienter'; }
  }).catch(() => { btn.disabled = false; btn.innerHTML = '<i class="bi bi-arrow-right-circle-fill me-1"></i>Orienter'; });
}
<?php endif; ?>
</script>

<?php else: ?>
<!-- ══════════════════════════════════════════════════════════════════════════
     PRISE DE PARAMÈTRES — CONTEXTE STANDARD (layout avec sidebar)
══════════════════════════════════════════════════════════════════════════ -->
<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>

        <main class="col-md-10 ms-sm-auto px-md-4">
            <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><i class="bi bi-speedometer2"></i> Prise de Paramètres</h1>
                <a href="javascript:history.back()" class="btn btn-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Retour
                </a>
            </div>

            <?php if (!empty($rdvAujourdhui)): ?>
            <?php
                require_once __DIR__ . '/../../controllers/SpecialisteController.php';
                $specLabel = SpecialisteController::LABELS[$rdvAujourdhui['specialite']] ?? $rdvAujourdhui['specialite'];
                $quotaOk   = ($rdvAujourdhui['quota_actuel'] ?? 0) < ($rdvAujourdhui['quota_max'] ?? 99);
            ?>
            <div class="alert border-0 shadow-sm mb-4 p-0 overflow-hidden" style="border-radius:14px;">
                <div class="d-flex align-items-stretch">
                    <div class="d-flex align-items-center justify-content-center px-4 text-white" style="background:linear-gradient(135deg,#5b21b6,#7c3aed);min-width:72px;">
                        <i class="bi bi-person-badge-fill fs-2"></i>
                    </div>
                    <div class="flex-grow-1 p-3">
                        <div class="fw-bold mb-1" style="color:#5b21b6;">RDV Spécialiste aujourd'hui</div>
                        <div class="fs-6 fw-semibold"><?= htmlspecialchars($specLabel) ?> — Dr <?= htmlspecialchars($rdvAujourdhui['spec_nom'] . ' ' . $rdvAujourdhui['spec_prenom']) ?></div>
                        <div class="text-muted small mt-1">
                            <?php if ($rdvAujourdhui['heure_rdv']): ?>
                                <i class="bi bi-clock me-1"></i><?= date('H:i', strtotime($rdvAujourdhui['heure_rdv'])) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="d-flex align-items-center px-3">
                        <?php if ($quotaOk): ?>
                        <button class="btn btn-sm text-white fw-bold px-3" style="background:#7c3aed;border-radius:10px;"
                                onclick="orienterSpecialiste(<?= (int)$rdvAujourdhui['id'] ?>, <?= (int)$patient['id'] ?>, this)">
                            <i class="bi bi-arrow-right-circle-fill me-1"></i>Orienter
                        </button>
                        <?php else: ?>
                        <span class="badge bg-danger-subtle text-danger">Quota atteint</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card shadow">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">
                                Patient : <?= htmlspecialchars($patient['nom'] . ' ' . $patient['prenom']) ?>
                                <span class="badge bg-light text-dark float-end"><?= htmlspecialchars($patient['dossier_numero']) ?></span>
                            </h5>
                        </div>
                        <div class="card-body">
                            <form action="<?php echo BASE_URL; ?>patients/save-mesures" method="POST">
                                <input type="hidden" name="patient_id" value="<?= $patient['id'] ?>">
                                <input type="hidden" name="context" value="">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Poids (kg)</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-person-standing"></i></span>
                                            <input type="number" step="0.1" name="poids" class="form-control" placeholder="ex: 70.5">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Taille (cm)</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-rulers"></i></span>
                                            <input type="number" name="taille" class="form-control" placeholder="ex: 175">
                                        </div>
                                    </div>
                                    <div class="col-12"><hr></div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">Température (°C)</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-thermometer-half text-danger"></i></span>
                                            <input type="number" step="0.1" name="temperature" class="form-control" placeholder="ex: 37.2">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">Pouls (bpm)</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-heart-pulse text-danger"></i></span>
                                            <input type="number" name="frequence_cardiaque" class="form-control" placeholder="ex: 80">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">Saturation O2 (%)</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-lungs text-primary"></i></span>
                                            <input type="number" name="spo2" class="form-control" placeholder="ex: 98">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Tension Systolique</label>
                                        <input type="number" name="tension_sys" class="form-control" placeholder="ex: 120">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Tension Diastolique</label>
                                        <input type="number" name="tension_dia" class="form-control" placeholder="ex: 80">
                                    </div>
                                </div>
                                <div class="mt-4 d-grid">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="bi bi-save"></i> Enregistrer les Paramètres
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
<?php if (!empty($rdvAujourdhui)): ?>
function orienterSpecialiste(rdvId, patientId, btn) {
    if (!confirm('Orienter ce patient vers le spécialiste ?\nSon statut passera à "Attente Spécialiste".')) return;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
    fetch('<?= BASE_URL ?>specialiste/orienter/' + rdvId, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded','X-Requested-With': 'XMLHttpRequest'},
        body: 'patient_id=' + patientId
    }).then(r => r.json()).then(data => {
        if (data.success) {
            btn.closest('.alert').remove();
        } else {
            alert('Erreur : ' + (data.message || 'Impossible d\'orienter.'));
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-arrow-right-circle-fill me-1"></i>Orienter';
        }
    }).catch(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-arrow-right-circle-fill me-1"></i>Orienter';
    });
}
<?php endif; ?>
</script>

<?php endif; ?>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
