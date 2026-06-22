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
 require_once __DIR__ . '/../../layouts/header.php'; ?>

<style>
  body { background: #f0f4f8; }

  .mf-wrap { max-width: 900px; margin: 0 auto; padding: 20px 16px 80px; }

  .mf-topbar {
    position: sticky; top: 0; z-index: 100;
    background: white; border-bottom: 1px solid #e2e8f0;
    padding: 10px 20px; display: flex; justify-content: space-between;
    align-items: center; box-shadow: 0 2px 8px rgba(0,0,0,.07);
    margin-bottom: 20px;
  }
  .mf-topbar .mf-title { font-weight: 700; font-size: 1rem; color: #4338ca; display: flex; align-items: center; gap: 8px; }
  .mf-topbar .mf-actions { display: flex; gap: 8px; }

  .mf-patient-banner {
    border-radius: 16px; padding: 20px 24px; color: white; margin-bottom: 20px;
    background: linear-gradient(135deg, #4338ca, #6366f1);
    box-shadow: 0 4px 20px rgba(67,56,202,.28);
    display: flex; align-items: center; gap: 20px;
  }
  .mf-patient-banner .avatar {
    width: 58px; height: 58px; border-radius: 50%;
    background: rgba(255,255,255,.20); display: flex; align-items: center;
    justify-content: center; font-size: 1.7rem; flex-shrink: 0;
  }
  .mf-patient-banner .info h4 { margin: 0 0 6px; font-size: 1.18rem; font-weight: 700; }
  .mf-patient-banner .badges { display: flex; flex-wrap: wrap; gap: 8px; }
  .mf-patient-banner .badge-item {
    background: rgba(255,255,255,.22); border-radius: 20px;
    padding: 3px 13px; font-size: 0.82rem; font-weight: 600;
  }

  .mf-section { background: white; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,.06); margin-bottom: 16px; overflow: hidden; }
  .mf-section-head {
    padding: 12px 20px; font-weight: 700; font-size: .88rem;
    text-transform: uppercase; letter-spacing: .5px;
    display: flex; align-items: center; gap: 8px;
    background: #eef2ff; color: #4338ca; border-bottom: 1.5px solid #c7d2fe;
  }
  .mf-section-body { padding: 20px; }

  .form-label { font-weight: 600; font-size: .8rem; color: #64748b; text-transform: uppercase; letter-spacing: .4px; }
  .form-control, .form-select {
    border: 1.5px solid #e2e8f0; border-radius: 8px;
    font-size: .93rem; padding: 10px 12px;
  }
  .form-control:focus, .form-select:focus {
    border-color: #4338ca; box-shadow: 0 0 0 3px rgba(67,56,202,.18);
    outline: none;
  }

  /* Intervention toggle cards */
  .interv-toggle { display: flex; gap: 12px; margin-bottom: 4px; }
  .interv-card {
    flex: 1; border: 2px solid #e2e8f0; border-radius: 12px;
    padding: 14px 16px; cursor: pointer; transition: all .18s;
    display: flex; align-items: center; gap: 12px;
    background: #f8fafc;
  }
  .interv-card:hover { border-color: #818cf8; background: #eef2ff; }
  .interv-card input[type="radio"] { display: none; }
  .interv-card.selected { border-color: #4338ca; background: #eef2ff; box-shadow: 0 0 0 3px rgba(67,56,202,.12); }
  .interv-card .card-icon { font-size: 1.6rem; color: #6366f1; flex-shrink: 0; }
  .interv-card .card-text strong { display: block; font-size: .92rem; color: #1e1b4b; font-weight: 700; }
  .interv-card .card-text span { font-size: .8rem; color: #64748b; }

  /* Info clauses */
  .clause-list { display: flex; flex-direction: column; gap: 8px; }
  .clause-item {
    display: flex; align-items: center; gap: 12px;
    padding: 11px 14px; border-radius: 10px;
    border: 1.5px solid #e2e8f0; background: #f8fafc;
    cursor: pointer; transition: all .15s;
  }
  .clause-item:hover { background: #f0fdf4; border-color: #86efac; }
  .clause-item input[type="checkbox"] { display: none; }
  .clause-check-icon {
    width: 26px; height: 26px; border-radius: 50%;
    border: 2px solid #cbd5e1; display: flex; align-items: center;
    justify-content: center; flex-shrink: 0; transition: all .15s;
    font-size: 1rem;
  }
  .clause-item.checked .clause-check-icon {
    background: #22c55e; border-color: #22c55e; color: white;
  }
  .clause-item.checked { background: #f0fdf4; border-color: #86efac; }
  .clause-text { font-size: .92rem; color: #334155; font-weight: 500; }

  /* Cost input group */
  .input-group-text {
    background: #eef2ff; color: #4338ca; font-weight: 700;
    border: 1.5px solid #e2e8f0; border-radius: 0 8px 8px 0;
  }
  .input-group .form-control { border-radius: 8px 0 0 8px; }

  .btn-save-main {
    background: linear-gradient(135deg, #4338ca, #6366f1); color: white;
    border: none; padding: 10px 24px; border-radius: 9px; font-weight: 700;
    font-size: .95rem; display: inline-flex; align-items: center; gap: 8px;
    cursor: pointer; transition: opacity .15s;
  }
  .btn-save-main:hover { opacity: .88; }
  .btn-back-top {
    background: #f1f5f9; color: #475569; border: none;
    padding: 10px 18px; border-radius: 9px; font-weight: 600;
    font-size: .9rem; display: inline-flex; align-items: center; gap: 6px;
    cursor: pointer; transition: background .15s; text-decoration: none;
  }
  .btn-back-top:hover { background: #e2e8f0; color: #334155; }

  .extra-fields { display: none; margin-top: 16px; }
  .extra-fields.visible { display: block; }
</style>

<div class="mf-topbar">
  <div class="mf-title">
    <i class="bi bi-pen"></i>
    Consentement éclairé
  </div>
  <div class="mf-actions">
    <?php if ($from_suivi): ?>
      <a href="javascript:history.back()" class="btn-back-top">
        <i class="bi bi-arrow-left"></i> Retour
      </a>
    <?php endif; ?>
    <button type="submit" form="formConsentement" class="btn-save-main">
      <i class="bi bi-check2-circle"></i> Enregistrer
    </button>
  </div>
</div>

<div class="mf-wrap">

  <!-- Patient Banner -->
  <div class="mf-patient-banner">
    <div class="avatar">
      <i class="bi bi-person-fill"></i>
    </div>
    <div class="info">
      <h4><?= htmlspecialchars($patient['nom'] . ' ' . $patient['prenom']) ?></h4>
      <div class="badges">
        <span class="badge-item"><i class="bi bi-hash"></i> <?= htmlspecialchars($patient['dossier_numero']) ?></span>
        <span class="badge-item"><i class="bi bi-calendar3"></i> Né(e) le <?= htmlspecialchars($patient['date_naissance']) ?></span>
        <span class="badge-item"><i class="bi bi-gender-<?= $patient['sexe'] === 'M' ? 'male' : 'female' ?>"></i> <?= $patient['sexe'] === 'M' ? 'Masculin' : 'Féminin' ?></span>
        <span class="badge-item"><i class="bi bi-telephone"></i> <?= htmlspecialchars($patient['telephone']) ?></span>
      </div>
    </div>
  </div>

  <form id="formConsentement" action="#" method="POST" novalidate>
    <input type="hidden" name="patient_id" value="<?= $patient['id'] ?>">
    <input type="hidden" name="hosp_id" value="<?= $hosp_id ?>">
    <input type="hidden" name="type_intervention" id="type_intervention" value="hosp_interv">

    <!-- SECTION 1: Informations du praticien -->
    <div class="mf-section">
      <div class="mf-section-head">
        <i class="bi bi-person-badge"></i> Informations du praticien
      </div>
      <div class="mf-section-body">
        <div class="row g-3">
          <div class="col-md-7">
            <label class="form-label">Nom du praticien</label>
            <input type="text" class="form-control" name="medecin_nom" value="<?= htmlspecialchars($medecin_nom) ?>">
          </div>
          <div class="col-md-5">
            <label class="form-label">Date de l'entretien</label>
            <input type="date" class="form-control" name="date_entretien" value="<?= date('Y-m-d') ?>">
          </div>
        </div>
      </div>
    </div>

    <!-- SECTION 2: Identité du signataire -->
    <div class="mf-section">
      <div class="mf-section-head">
        <i class="bi bi-person"></i> Identité du signataire
      </div>
      <div class="mf-section-body">
        <div class="row g-3 align-items-end">
          <div class="col-md-7">
            <label class="form-label">Patient</label>
            <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($patient['nom'] . ' ' . $patient['prenom']) ?>" readonly>
          </div>
          <div class="col-md-5">
            <label class="form-label">Lieu de naissance</label>
            <input type="text" class="form-control" name="lieu_naissance" placeholder="Ville de naissance...">
          </div>
        </div>
      </div>
    </div>

    <!-- SECTION 3: Nature de l'intervention -->
    <div class="mf-section">
      <div class="mf-section-head">
        <i class="bi bi-hospital"></i> Nature de l'intervention
      </div>
      <div class="mf-section-body">
        <label class="form-label mb-3">Type d'intervention</label>
        <div class="interv-toggle">
          <label class="interv-card selected" id="card_hosp_interv">
            <input type="radio" name="_type_interv_ui" value="hosp_interv" checked>
            <div class="card-icon"><i class="bi bi-hospital-fill"></i></div>
            <div class="card-text">
              <strong>Hospitalisation + Intervention</strong>
              <span>Séjour hospitalier et acte chirurgical</span>
            </div>
          </label>
          <label class="interv-card" id="card_interv_seule">
            <input type="radio" name="_type_interv_ui" value="interv_seule">
            <div class="card-icon"><i class="bi bi-scissors"></i></div>
            <div class="card-text">
              <strong>Intervention seule</strong>
              <span>Acte chirurgical ambulatoire</span>
            </div>
          </label>
        </div>

        <!-- Fields for Hospitalisation + Intervention -->
        <div class="extra-fields visible" id="fields_hosp_interv">
          <div class="row g-3 mt-1">
            <div class="col-md-6">
              <label class="form-label">Date d'hospitalisation</label>
              <input type="date" class="form-control" name="date_hosp">
            </div>
            <div class="col-md-6">
              <label class="form-label">Date de l'intervention</label>
              <input type="date" class="form-control" name="date_interv">
            </div>
          </div>
        </div>

        <!-- Fields for Intervention seule -->
        <div class="extra-fields" id="fields_interv_seule">
          <div class="row g-3 mt-1">
            <div class="col-md-6">
              <label class="form-label">Date de l'intervention</label>
              <input type="date" class="form-control" name="date_interv_seule">
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- SECTION 4: Informations reçues -->
    <div class="mf-section">
      <div class="mf-section-head">
        <i class="bi bi-info-circle"></i> Informations reçues
      </div>
      <div class="mf-section-body">
        <p class="text-muted small mb-3">Le patient confirme avoir été informé sur les points suivants :</p>
        <div class="clause-list">

          <label class="clause-item checked" onclick="toggleClause(this)">
            <input type="checkbox" name="info_probleme" value="1" checked>
            <div class="clause-check-icon"><i class="bi bi-check"></i></div>
            <span class="clause-text">Informé(e) sur le problème de santé</span>
          </label>

          <label class="clause-item checked" onclick="toggleClause(this)">
            <input type="checkbox" name="info_evolution" value="1" checked>
            <div class="clause-check-icon"><i class="bi bi-check"></i></div>
            <span class="clause-text">Informé(e) de l'évolution sans traitement et des alternatives</span>
          </label>

          <label class="clause-item checked" onclick="toggleClause(this)">
            <input type="checkbox" name="info_nature" value="1" checked>
            <div class="clause-check-icon"><i class="bi bi-check"></i></div>
            <span class="clause-text">Informé(e) de la nature et des risques de l'intervention</span>
          </label>

          <label class="clause-item checked" onclick="toggleClause(this)">
            <input type="checkbox" name="info_imprevus" value="1" checked>
            <div class="clause-check-icon"><i class="bi bi-check"></i></div>
            <span class="clause-text">Informé(e) des actes complémentaires éventuels</span>
          </label>

          <label class="clause-item checked" onclick="toggleClause(this)">
            <input type="checkbox" name="info_cout" value="1" checked>
            <div class="clause-check-icon"><i class="bi bi-check"></i></div>
            <span class="clause-text">Informé(e) du coût estimatif</span>
          </label>

          <label class="clause-item checked" onclick="toggleClause(this)">
            <input type="checkbox" name="info_questions" value="1" checked>
            <div class="clause-check-icon"><i class="bi bi-check"></i></div>
            <span class="clause-text">Questions posées et réponses satisfaisantes obtenues</span>
          </label>

        </div>
      </div>
    </div>

    <!-- SECTION 5: Coût et signature -->
    <div class="mf-section">
      <div class="mf-section-head">
        <i class="bi bi-pen"></i> Coût et signature
      </div>
      <div class="mf-section-body">
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Coût estimatif</label>
            <div class="input-group">
              <input type="number" class="form-control" name="cout_estim" placeholder="0" min="0">
              <span class="input-group-text">FCFA</span>
            </div>
          </div>
          <div class="col-md-4">
            <label class="form-label">Date de signature</label>
            <input type="date" class="form-control" name="date_signature" value="<?= date('Y-m-d') ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label">Lieu</label>
            <input type="text" class="form-control" name="lieu_signature" value="Njombé">
          </div>
        </div>
      </div>
    </div>

  </form>
</div>

<script>
  // Intervention type toggle
  document.querySelectorAll('input[name="_type_interv_ui"]').forEach(function(radio) {
    radio.addEventListener('change', function() {
      var val = this.value;
      document.getElementById('type_intervention').value = val;

      document.querySelectorAll('.interv-card').forEach(function(c) { c.classList.remove('selected'); });
      this.closest('.interv-card').classList.add('selected');

      document.getElementById('fields_hosp_interv').classList.toggle('visible', val === 'hosp_interv');
      document.getElementById('fields_interv_seule').classList.toggle('visible', val === 'interv_seule');
    });
  });

  // Clause toggle
  function toggleClause(label) {
    var cb = label.querySelector('input[type="checkbox"]');
    // The browser toggles the checkbox before this fires via onclick on the label
    // We read the new state after the tick
    setTimeout(function() {
      if (cb.checked) {
        label.classList.add('checked');
      } else {
        label.classList.remove('checked');
      }
    }, 0);
  }
</script>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
