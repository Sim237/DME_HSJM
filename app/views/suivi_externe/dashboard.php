<?php
/**
 * SimCare+ — Vue : Suivi Patients Externes
 */
$filtreCourant = $filtre ?? 'tous';
$searchVal     = htmlspecialchars($search ?? '');

$statutLabel = [
    'PROVISOIRE' => ['label' => 'Provisoire',  'cls' => 'se-badge-prov'],
    'CONFIRME'   => ['label' => 'Confirmé',    'cls' => 'se-badge-conf'],
    'REVISE'     => ['label' => 'Révisé',      'cls' => 'se-badge-rev'],
    'ANNULE'     => ['label' => 'Annulé',      'cls' => 'se-badge-ann'],
];

$bilanStatutLabel = [
    'EN_ATTENTE' => ['label' => 'En attente',  'cls' => 'bg-warning text-dark'],
    'EN_COURS'   => ['label' => 'En cours',    'cls' => 'bg-info text-white'],
    'VALIDES'    => ['label' => 'Disponibles', 'cls' => 'bg-success text-white'],
    'TERMINE'    => ['label' => 'Terminé',     'cls' => 'bg-secondary text-white'],
];
?>
<style>
.se-page{padding:1.5rem;}
.se-header{display:flex;align-items:center;gap:1rem;margin-bottom:1.5rem;flex-wrap:wrap;}
.se-title{font-size:1.5rem;font-weight:700;color:#0f172a;margin:0;}
.se-subtitle{color:#64748b;font-size:.875rem;}
.se-stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:1rem;margin-bottom:1.5rem;}
.se-stat-card{background:#fff;border-radius:12px;padding:1rem 1.25rem;box-shadow:0 1px 4px rgba(0,0,0,.08);border-left:4px solid transparent;cursor:pointer;transition:box-shadow .15s,transform .1s;}
.se-stat-card:hover{box-shadow:0 4px 12px rgba(0,0,0,.12);transform:translateY(-1px);}
.se-stat-card.active{box-shadow:0 0 0 2px currentColor;}
.se-stat-card.c-tous   {border-color:#6366f1;}
.se-stat-card.c-attente{border-color:#f59e0b;}
.se-stat-card.c-dispo  {border-color:#10b981;}
.se-stat-card.c-conf   {border-color:#3b82f6;}
.se-stat-card.c-rev    {border-color:#ef4444;}
.se-stat-card.c-sans   {border-color:#94a3b8;}
.se-stat-num{font-size:1.75rem;font-weight:800;line-height:1;}
.se-stat-lbl{font-size:.75rem;color:#64748b;margin-top:.25rem;}
.se-toolbar{display:flex;align-items:center;gap:.75rem;margin-bottom:1rem;flex-wrap:wrap;}
.se-search{flex:1;min-width:220px;padding:.5rem .875rem;border:1.5px solid #e2e8f0;border-radius:8px;font-size:.875rem;outline:none;}
.se-search:focus{border-color:#6366f1;}
.se-table-wrap{background:#fff;border-radius:12px;box-shadow:0 1px 4px rgba(0,0,0,.08);overflow:hidden;}
.se-table{width:100%;border-collapse:collapse;font-size:.875rem;}
.se-table th{background:#f8fafc;padding:.65rem 1rem;text-align:left;font-weight:600;color:#475569;border-bottom:1px solid #e2e8f0;white-space:nowrap;}
.se-table td{padding:.65rem 1rem;border-bottom:1px solid #f1f5f9;vertical-align:middle;}
.se-table tr:last-child td{border-bottom:none;}
.se-table tr:hover td{background:#fafafa;}
.se-badge-prov{background:#fef3c7;color:#92400e;padding:.2rem .6rem;border-radius:999px;font-size:.75rem;font-weight:600;}
.se-badge-conf{background:#d1fae5;color:#065f46;padding:.2rem .6rem;border-radius:999px;font-size:.75rem;font-weight:600;}
.se-badge-rev{background:#fee2e2;color:#991b1b;padding:.2rem .6rem;border-radius:999px;font-size:.75rem;font-weight:600;}
.se-badge-ann{background:#f1f5f9;color:#475569;padding:.2rem .6rem;border-radius:999px;font-size:.75rem;font-weight:600;}
.se-btn{display:inline-flex;align-items:center;gap:.35rem;padding:.35rem .75rem;border-radius:7px;border:none;cursor:pointer;font-size:.8rem;font-weight:600;transition:opacity .15s;}
.se-btn:hover{opacity:.85;}
.se-btn-green{background:#10b981;color:#fff;}
.se-btn-blue {background:#3b82f6;color:#fff;}
.se-btn-amber{background:#f59e0b;color:#fff;}
.se-btn-red  {background:#ef4444;color:#fff;}
.se-btn-ghost{background:#f1f5f9;color:#475569;}
.se-empty{text-align:center;padding:3rem 1rem;color:#94a3b8;}
/* Modals */
.se-modal-backdrop{display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1050;align-items:center;justify-content:center;}
.se-modal-backdrop.open{display:flex;}
.se-modal{background:#fff;border-radius:14px;padding:1.75rem;width:min(560px,96vw);max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,.2);}
.se-modal-title{font-size:1.1rem;font-weight:700;color:#0f172a;margin-bottom:1.25rem;}
.se-field{margin-bottom:1rem;}
.se-label{display:block;font-size:.8rem;font-weight:600;color:#475569;margin-bottom:.35rem;}
.se-input,.se-textarea,.se-select{width:100%;padding:.5rem .75rem;border:1.5px solid #e2e8f0;border-radius:8px;font-size:.875rem;outline:none;transition:border-color .15s;}
.se-input:focus,.se-textarea:focus,.se-select:focus{border-color:#6366f1;}
.se-textarea{resize:vertical;min-height:80px;}
.se-modal-actions{display:flex;gap:.75rem;justify-content:flex-end;margin-top:1.25rem;}
.se-msg{padding:.65rem 1rem;border-radius:8px;font-size:.85rem;margin-top:.75rem;display:none;}
.se-msg.ok{background:#d1fae5;color:#065f46;}
.se-msg.err{background:#fee2e2;color:#991b1b;}
.se-rdv-badge{background:#fef3c7;color:#92400e;padding:.15rem .5rem;border-radius:999px;font-size:.72rem;font-weight:600;}
</style>

<div class="se-page">
  <!-- En-tête -->
  <div class="se-header">
    <div>
      <h1 class="se-title"><i class="bi bi-person-lines-fill me-2" style="color:#6366f1;"></i>Suivi Patients Externes</h1>
      <p class="se-subtitle">Consultations ambulatoires — diagnostic, bilan, rendez-vous</p>
    </div>
    <div style="flex:1;"></div>
    <button class="se-btn se-btn-ghost" onclick="history.back()" style="font-size:.85rem;padding:.5rem 1rem;gap:.5rem;">
      <i class="bi bi-arrow-left"></i> Retour
    </button>
  </div>

  <!-- Cartes stats / filtres -->
  <div class="se-stats">
    <?php
    $cards = [
      ['filtre' => 'tous',          'cls' => 'c-tous',    'num' => $stats['total']           ?? 0, 'lbl' => 'Total'],
      ['filtre' => 'attente_bilan', 'cls' => 'c-attente', 'num' => $stats['en_attente_bilan'] ?? 0, 'lbl' => 'Attente bilan'],
      ['filtre' => 'bilan_dispo',   'cls' => 'c-dispo',   'num' => $stats['bilan_disponible'] ?? 0, 'lbl' => 'Bilan dispo'],
      ['filtre' => 'confirme',      'cls' => 'c-conf',    'num' => $stats['confirmes']        ?? 0, 'lbl' => 'Confirmés'],
      ['filtre' => 'revise',        'cls' => 'c-rev',     'num' => $stats['revises']          ?? 0, 'lbl' => 'Révisés'],
      ['filtre' => 'sans_bilan',    'cls' => 'c-sans',    'num' => $stats['sans_bilan']       ?? 0, 'lbl' => 'Sans bilan'],
    ];
    foreach ($cards as $c): ?>
    <div class="se-stat-card <?= $c['cls'] ?> <?= $filtreCourant === $c['filtre'] ? 'active' : '' ?>"
         onclick="seFiltre('<?= $c['filtre'] ?>')">
      <div class="se-stat-num"><?= (int)$c['num'] ?></div>
      <div class="se-stat-lbl"><?= $c['lbl'] ?></div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Barre outils -->
  <div class="se-toolbar">
    <form id="seSearchForm" method="GET" action="<?= BASE_URL ?>suivi-externe" style="display:flex;gap:.5rem;flex:1;">
      <input type="hidden" name="filtre" value="<?= htmlspecialchars($filtreCourant) ?>">
      <input class="se-search" type="text" name="q" placeholder="Rechercher patient (nom, prénom, dossier)…" value="<?= $searchVal ?>">
      <button type="submit" class="se-btn se-btn-blue"><i class="bi bi-search"></i></button>
    </form>
  </div>

  <!-- Tableau -->
  <div class="se-table-wrap">
    <?php if (empty($patients_externes)): ?>
    <div class="se-empty">
      <i class="bi bi-inbox" style="font-size:2.5rem;display:block;margin-bottom:.5rem;"></i>
      Aucun patient externe trouvé pour ce filtre.
    </div>
    <?php else: ?>
    <table class="se-table">
      <thead>
        <tr>
          <th>Patient</th>
          <th>Date consultation</th>
          <th>Médecin</th>
          <th>Diagnostic</th>
          <th>Statut diag.</th>
          <th>Bilan</th>
          <th>RDV conditionnel</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($patients_externes as $row):
        $sd   = $statutLabel[$row['statut_diagnostic']] ?? ['label' => $row['statut_diagnostic'], 'cls' => ''];
        $bs   = $bilanStatutLabel[$row['bilan_statut'] ?? ''] ?? null;
        $age  = $row['date_naissance'] ? (int)((time() - strtotime($row['date_naissance'])) / 31557600) : null;
        $dateConsult = $row['date_consultation'] ? date('d/m/Y', strtotime($row['date_consultation'])) : '—';
      ?>
      <tr>
        <td>
          <div style="font-weight:600;"><?= htmlspecialchars($row['nom'] . ' ' . $row['prenom']) ?></div>
          <div style="color:#64748b;font-size:.78rem;"><?= htmlspecialchars($row['dossier_numero']) ?><?= $age !== null ? ' · ' . $age . ' ans' : '' ?></div>
        </td>
        <td><?= $dateConsult ?></td>
        <td style="color:#475569;"><?= htmlspecialchars($row['medecin_nom'] ?? '—') ?></td>
        <td style="max-width:200px;">
          <div style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:190px;" title="<?= htmlspecialchars($row['diagnostic_principal'] ?? '') ?>">
            <?= htmlspecialchars(mb_strimwidth($row['diagnostic_principal'] ?? '—', 0, 60, '…')) ?>
          </div>
          <?php if (!empty($row['notes_revision_diag'])): ?>
          <div style="color:#94a3b8;font-size:.75rem;margin-top:.2rem;">Note: <?= htmlspecialchars(mb_strimwidth($row['notes_revision_diag'], 0, 50, '…')) ?></div>
          <?php endif; ?>
        </td>
        <td><span class="<?= $sd['cls'] ?>"><?= $sd['label'] ?></span></td>
        <td>
          <?php if ($row['bilan_id']): ?>
            <?php if ($bs): ?>
              <span class="badge <?= $bs['cls'] ?>" style="font-size:.72rem;"><?= $bs['label'] ?></span>
            <?php endif; ?>
            <?php if (!empty($row['nb_examens_attente']) && $row['nb_examens_attente'] > 0): ?>
              <div style="color:#f59e0b;font-size:.75rem;margin-top:.2rem;"><?= (int)$row['nb_examens_attente'] ?> examen(s) en attente</div>
            <?php endif; ?>
            <?php if ($row['bilan_statut'] === 'VALIDES' || $row['bilan_statut'] === 'TERMINE'): ?>
              <div style="color:#10b981;font-size:.75rem;margin-top:.2rem;"><i class="bi bi-check-circle-fill"></i> Résultats disponibles</div>
            <?php endif; ?>
          <?php else: ?>
            <span style="color:#94a3b8;font-size:.8rem;">Aucun bilan</span>
          <?php endif; ?>
        </td>
        <td>
          <?php if ($row['rdv_id']): ?>
            <span class="se-rdv-badge"><i class="bi bi-calendar-check"></i>
              <?= $row['rdv_date'] ? date('d/m/Y H:i', strtotime($row['rdv_date'])) : '—' ?>
            </span>
            <div style="color:#94a3b8;font-size:.72rem;margin-top:.2rem;">En attente confirmation auto</div>
          <?php elseif ($row['bilan_id'] && !in_array($row['bilan_statut'] ?? '', ['VALIDES','TERMINE'])): ?>
            <button class="se-btn se-btn-amber" style="font-size:.75rem;"
                    onclick="seOpenRdv(<?= (int)$row['patient_id'] ?>, <?= (int)$row['medecin_id'] ?>, <?= (int)($row['bilan_id'] ?? 0) ?>, '<?= htmlspecialchars(addslashes($row['nom'] . ' ' . $row['prenom'])) ?>')">
              <i class="bi bi-calendar-plus"></i> Planifier RDV
            </button>
          <?php else: ?>
            <span style="color:#94a3b8;font-size:.8rem;">—</span>
          <?php endif; ?>
        </td>
        <td>
          <div style="display:flex;gap:.35rem;flex-wrap:wrap;">
            <?php if ($row['statut_diagnostic'] === 'PROVISOIRE'): ?>
            <button class="se-btn se-btn-green"
                    onclick="seOpenConfirm(<?= (int)$row['consultation_id'] ?>, '<?= htmlspecialchars(addslashes($row['diagnostic_principal'] ?? '')) ?>')">
              <i class="bi bi-check-lg"></i> Confirmer
            </button>
            <button class="se-btn se-btn-blue"
                    onclick="seOpenReviser(<?= (int)$row['consultation_id'] ?>, '<?= htmlspecialchars(addslashes($row['diagnostic_principal'] ?? '')) ?>')">
              <i class="bi bi-pencil"></i> Réviser
            </button>
            <?php else: ?>
            <button class="se-btn se-btn-ghost"
                    onclick="seOpenReviser(<?= (int)$row['consultation_id'] ?>, '<?= htmlspecialchars(addslashes($row['diagnostic_principal'] ?? '')) ?>')">
              <i class="bi bi-pencil"></i> Modifier
            </button>
            <?php endif; ?>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</div>

<!-- ══════════════════════════════════════════════════════════════════
     MODAL CONFIRMER DIAGNOSTIC
══════════════════════════════════════════════════════════════════ -->
<div class="se-modal-backdrop" id="seConfirmModal">
  <div class="se-modal">
    <div class="se-modal-title"><i class="bi bi-check-circle-fill text-success me-2"></i>Confirmer le diagnostic</div>
    <div class="se-field">
      <label class="se-label">Diagnostic actuel</label>
      <div id="seConfirmDiag" style="padding:.5rem .75rem;background:#f8fafc;border-radius:8px;font-size:.875rem;color:#334155;"></div>
    </div>
    <div class="se-field">
      <label class="se-label">Note de confirmation (optionnel)</label>
      <textarea id="seConfirmNote" class="se-textarea" placeholder="Observations complémentaires…"></textarea>
    </div>
    <div class="se-msg" id="seConfirmMsg"></div>
    <div class="se-modal-actions">
      <button class="se-btn se-btn-ghost" onclick="seCloseConfirm()">Annuler</button>
      <button class="se-btn se-btn-green" onclick="seDoConfirm()"><i class="bi bi-check-lg"></i> Confirmer</button>
    </div>
  </div>
</div>

<!-- ══════════════════════════════════════════════════════════════════
     MODAL RÉVISER DIAGNOSTIC
══════════════════════════════════════════════════════════════════ -->
<div class="se-modal-backdrop" id="seReviserModal">
  <div class="se-modal">
    <div class="se-modal-title"><i class="bi bi-pencil-square text-primary me-2"></i>Réviser le diagnostic</div>
    <div class="se-field">
      <label class="se-label">Diagnostic actuel</label>
      <div id="seReviserDiagActuel" style="padding:.5rem .75rem;background:#f8fafc;border-radius:8px;font-size:.875rem;color:#334155;"></div>
    </div>
    <div class="se-field">
      <label class="se-label">Nouveau diagnostic <span class="text-danger">*</span></label>
      <textarea id="seReviserNouveauDiag" class="se-textarea" placeholder="Saisir le nouveau diagnostic…"></textarea>
    </div>
    <div class="se-field">
      <label class="se-label">Type de révision</label>
      <select id="seReviserStatut" class="se-select">
        <option value="REVISE">Révisé (diagnostic modifié)</option>
        <option value="ANNULE">Annulé (diagnostic infirmé)</option>
      </select>
    </div>
    <div class="se-field">
      <label class="se-label">Motif / Note</label>
      <textarea id="seReviserNote" class="se-textarea" placeholder="Motif de la révision, observations des résultats…"></textarea>
    </div>
    <div class="se-msg" id="seReviserMsg"></div>
    <div class="se-modal-actions">
      <button class="se-btn se-btn-ghost" onclick="seCloseReviser()">Annuler</button>
      <button class="se-btn se-btn-blue" onclick="seDoReviser()"><i class="bi bi-check-lg"></i> Enregistrer</button>
    </div>
  </div>
</div>

<!-- ══════════════════════════════════════════════════════════════════
     MODAL RDV CONDITIONNEL
══════════════════════════════════════════════════════════════════ -->
<div class="se-modal-backdrop" id="seRdvModal">
  <div class="se-modal">
    <div class="se-modal-title"><i class="bi bi-calendar-plus-fill text-warning me-2"></i>Planifier un RDV conditionnel</div>
    <p style="font-size:.85rem;color:#64748b;margin-bottom:1rem;">
      Ce rendez-vous sera automatiquement confirmé dès que les résultats du bilan seront disponibles.
    </p>
    <div class="se-field">
      <label class="se-label">Patient</label>
      <div id="seRdvPatientName" style="padding:.5rem .75rem;background:#f8fafc;border-radius:8px;font-size:.875rem;color:#334155;"></div>
    </div>
    <div class="se-field">
      <label class="se-label">Médecin</label>
      <select id="seRdvMedecin" class="se-select">
        <?php foreach ($medecins as $m): ?>
        <option value="<?= (int)$m['id'] ?>"><?= htmlspecialchars($m['nom_complet']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="se-field">
      <label class="se-label">Titre du RDV</label>
      <input id="seRdvTitre" class="se-input" type="text" value="Consultation résultats de bilan">
    </div>
    <div class="se-field">
      <label class="se-label">Date et heure proposées <span class="text-danger">*</span></label>
      <input id="seRdvDate" class="se-input" type="datetime-local">
    </div>
    <div class="se-field">
      <label class="se-label">Salle (optionnel)</label>
      <input id="seRdvSalle" class="se-input" type="text" placeholder="Ex: Consultation A">
    </div>
    <div class="se-msg" id="seRdvMsg"></div>
    <div class="se-modal-actions">
      <button class="se-btn se-btn-ghost" onclick="seCloseRdv()">Annuler</button>
      <button class="se-btn se-btn-amber" onclick="seDoRdv()"><i class="bi bi-calendar-check"></i> Planifier</button>
    </div>
  </div>
</div>

<script>
const SE_BASE = '<?= BASE_URL ?>';

// ── Navigation filtres ────────────────────────────────────────────
function seFiltre(f) {
  const q = document.querySelector('.se-search')?.value || '';
  window.location.href = SE_BASE + 'suivi-externe?filtre=' + f + (q ? '&q=' + encodeURIComponent(q) : '');
}

// ── CONFIRMER ────────────────────────────────────────────────────
let seConfirmId = 0;
function seOpenConfirm(id, diag) {
  seConfirmId = id;
  document.getElementById('seConfirmDiag').textContent = diag || '(non renseigné)';
  document.getElementById('seConfirmNote').value = '';
  seMsg('seConfirmMsg', '', '');
  document.getElementById('seConfirmModal').classList.add('open');
}
function seCloseConfirm() { document.getElementById('seConfirmModal').classList.remove('open'); }

function seDoConfirm() {
  const note = document.getElementById('seConfirmNote').value;
  seFetch(SE_BASE + 'suivi-externe/confirmer-diagnostic', {
    consultation_id: seConfirmId, note
  }, 'seConfirmMsg', () => { setTimeout(() => location.reload(), 900); });
}

// ── RÉVISER ──────────────────────────────────────────────────────
let seReviserId = 0;
function seOpenReviser(id, diag) {
  seReviserId = id;
  document.getElementById('seReviserDiagActuel').textContent = diag || '(non renseigné)';
  document.getElementById('seReviserNouveauDiag').value = diag || '';
  document.getElementById('seReviserNote').value = '';
  document.getElementById('seReviserStatut').value = 'REVISE';
  seMsg('seReviserMsg', '', '');
  document.getElementById('seReviserModal').classList.add('open');
}
function seCloseReviser() { document.getElementById('seReviserModal').classList.remove('open'); }

function seDoReviser() {
  const nouveau = document.getElementById('seReviserNouveauDiag').value.trim();
  if (!nouveau) { seMsg('seReviserMsg', 'Le nouveau diagnostic est obligatoire.', 'err'); return; }
  seFetch(SE_BASE + 'suivi-externe/reviser-diagnostic', {
    consultation_id: seReviserId,
    nouveau_diagnostic: nouveau,
    statut: document.getElementById('seReviserStatut').value,
    note: document.getElementById('seReviserNote').value,
  }, 'seReviserMsg', () => { setTimeout(() => location.reload(), 900); });
}

// ── RDV CONDITIONNEL ─────────────────────────────────────────────
let seRdvPatId = 0, seRdvMedId = 0, seRdvBilanId = 0;
function seOpenRdv(patId, medId, bilanId, patName) {
  seRdvPatId   = patId;
  seRdvMedId   = medId;
  seRdvBilanId = bilanId;
  document.getElementById('seRdvPatientName').textContent = patName;
  const sel = document.getElementById('seRdvMedecin');
  if (medId && sel) {
    for (let o of sel.options) { if (parseInt(o.value) === medId) { o.selected = true; break; } }
  }
  // Proposer demain matin 9h
  const d = new Date(); d.setDate(d.getDate() + 1); d.setHours(9, 0, 0, 0);
  document.getElementById('seRdvDate').value = d.toISOString().slice(0, 16);
  seMsg('seRdvMsg', '', '');
  document.getElementById('seRdvModal').classList.add('open');
}
function seCloseRdv() { document.getElementById('seRdvModal').classList.remove('open'); }

function seDoRdv() {
  const dateVal = document.getElementById('seRdvDate').value;
  if (!dateVal) { seMsg('seRdvMsg', 'La date est obligatoire.', 'err'); return; }
  seFetch(SE_BASE + 'suivi-externe/planifier-rdv-bilan', {
    patient_id:  seRdvPatId,
    medecin_id:  document.getElementById('seRdvMedecin').value,
    bilan_id:    seRdvBilanId,
    titre:       document.getElementById('seRdvTitre').value || 'Consultation résultats de bilan',
    date_debut:  dateVal.replace('T', ' ') + ':00',
    salle:       document.getElementById('seRdvSalle').value,
  }, 'seRdvMsg', () => { setTimeout(() => location.reload(), 900); });
}

// ── Utilitaires ───────────────────────────────────────────────────
function seMsg(id, txt, type) {
  const el = document.getElementById(id);
  if (!el) return;
  el.textContent = txt;
  el.className = 'se-msg' + (type ? ' ' + type : '');
  el.style.display = txt ? 'block' : 'none';
}

function seFetch(url, data, msgId, onSuccess) {
  const form = new FormData();
  for (const [k, v] of Object.entries(data)) form.append(k, v);
  fetch(url, { method: 'POST', body: form })
    .then(r => r.json())
    .then(j => {
      seMsg(msgId, j.message || (j.success ? 'OK' : 'Erreur'), j.success ? 'ok' : 'err');
      if (j.success && onSuccess) onSuccess(j);
    })
    .catch(() => seMsg(msgId, 'Erreur réseau.', 'err'));
}

// Fermer modal en cliquant sur le fond
document.querySelectorAll('.se-modal-backdrop').forEach(b => {
  b.addEventListener('click', e => { if (e.target === b) b.classList.remove('open'); });
});
</script>
