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
  .mf-topbar .mf-title { font-weight: 700; font-size: 1rem; color: #1d4ed8; display: flex; align-items: center; gap: 8px; }
  .mf-topbar .mf-actions { display: flex; gap: 8px; }

  .mf-patient-banner {
    border-radius: 16px; padding: 20px 24px; color: white; margin-bottom: 20px;
    background: linear-gradient(135deg, #1d4ed8, #3b82f6);
    box-shadow: 0 4px 20px rgba(29,78,216,.28);
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
    background: #eff6ff; color: #1d4ed8; border-bottom: 1.5px solid #bfdbfe;
  }
  .mf-section-body { padding: 20px; }

  .form-label { font-weight: 600; font-size: .8rem; color: #64748b; text-transform: uppercase; letter-spacing: .4px; }
  .form-control, .form-select {
    border: 1.5px solid #e2e8f0; border-radius: 8px;
    font-size: .93rem; padding: 10px 12px;
  }
  .form-control:focus, .form-select:focus {
    border-color: #1d4ed8; box-shadow: 0 0 0 3px rgba(29,78,216,.18);
    outline: none;
  }
  textarea.form-control { resize: vertical; min-height: 100px; line-height: 1.7; }

  /* Filiation toggle pills */
  .fil-pills { display: flex; gap: 10px; flex-wrap: wrap; }
  .fil-pill {
    display: flex; align-items: center; gap: 8px;
    padding: 8px 18px; border-radius: 25px;
    border: 2px solid #e2e8f0; background: #f8fafc;
    cursor: pointer; transition: all .18s; user-select: none;
  }
  .fil-pill:hover { border-color: #93c5fd; background: #eff6ff; }
  .fil-pill input[type="checkbox"] { display: none; }
  .fil-pill .pill-icon { font-size: 1.1rem; color: #94a3b8; transition: color .15s; }
  .fil-pill .pill-text { font-size: .9rem; font-weight: 600; color: #475569; transition: color .15s; }
  .fil-pill.active {
    border-color: #1d4ed8; background: #eff6ff;
    box-shadow: 0 0 0 3px rgba(29,78,216,.12);
  }
  .fil-pill.active .pill-icon { color: #1d4ed8; }
  .fil-pill.active .pill-text { color: #1d4ed8; }

  /* Prestation rows */
  .prestation-row {
    display: flex; align-items: center; gap: 16px;
    padding: 14px 16px; border-radius: 10px;
    border: 1.5px solid #e2e8f0; background: #f8fafc;
    margin-bottom: 10px; flex-wrap: wrap;
  }
  .prestation-row:hover { border-color: #93c5fd; background: #eff6ff; }
  .prestation-row .prest-icon { font-size: 1.3rem; color: #1d4ed8; flex-shrink: 0; }
  .prestation-row .prest-label { font-weight: 700; font-size: .9rem; color: #1e3a8a; min-width: 180px; }
  .prestation-row .prest-date { flex: 1; min-width: 180px; }
  .prestation-row .prest-date label { font-size: .75rem; color: #64748b; font-weight: 600; text-transform: uppercase; letter-spacing: .4px; }

  /* Patient readonly card */
  .patient-readonly {
    background: #eff6ff; border: 1.5px solid #bfdbfe;
    border-radius: 10px; padding: 14px 16px; display: flex; flex-wrap: wrap; gap: 12px 24px;
  }
  .patient-readonly .pr-item { display: flex; align-items: center; gap: 6px; font-size: .9rem; color: #1e3a8a; }
  .patient-readonly .pr-item i { color: #1d4ed8; }

  .btn-save-main {
    background: linear-gradient(135deg, #1d4ed8, #3b82f6); color: white;
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

  /* Section assure highlight */
  .field-required .form-control { border-color: #bfdbfe; }
</style>

<div class="mf-topbar">
  <div class="mf-title">
    <i class="bi bi-shield-check"></i>
    Fiche de Prise en Charge
  </div>
  <div class="mf-actions">
    <?php if ($from_suivi): ?>
      <a href="javascript:history.back()" class="btn-back-top">
        <i class="bi bi-arrow-left"></i> Retour
      </a>
    <?php endif; ?>
    <button type="submit" form="formPEC" class="btn-save-main">
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
        <span class="badge-item"><i class="bi bi-calendar3"></i> <?= $age ?> ans</span>
        <span class="badge-item"><i class="bi bi-gender-<?= $patient['sexe'] === 'M' ? 'male' : 'female' ?>"></i> <?= $patient['sexe'] === 'M' ? 'Masculin' : 'Féminin' ?></span>
        <span class="badge-item"><i class="bi bi-stethoscope"></i> <?= htmlspecialchars($medecin_nom) ?></span>
      </div>
    </div>
  </div>

  <form id="formPEC" action="#" method="POST" novalidate>
    <input type="hidden" name="patient_id" value="<?= $patient['id'] ?>">
    <input type="hidden" name="hosp_id" value="<?= $hosp_id ?>">

    <!-- SECTION 1: L'assuré -->
    <div class="mf-section">
      <div class="mf-section-head">
        <i class="bi bi-person-fill-check"></i> L'assuré
      </div>
      <div class="mf-section-body">
        <div class="row g-3 mb-3">
          <div class="col-12 field-required">
            <label class="form-label">Nom de l'assuré <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="nom_assure" required placeholder="Nom complet de l'assuré...">
          </div>
          <div class="col-md-4">
            <label class="form-label">Entreprise</label>
            <input type="text" class="form-control" name="entreprise" placeholder="Nom de l'entreprise...">
          </div>
          <div class="col-md-4">
            <label class="form-label">Secteur</label>
            <input type="text" class="form-control" name="secteur" placeholder="Secteur d'activité...">
          </div>
          <div class="col-md-4">
            <label class="form-label">Matricule</label>
            <input type="text" class="form-control" name="matricule" placeholder="Numéro matricule...">
          </div>
        </div>

        <label class="form-label mb-2">Lien de filiation</label>
        <div class="fil-pills" id="filPills">
          <label class="fil-pill" id="pill_salarie">
            <input type="checkbox" name="fil_salarie" value="1" id="cb_salarie">
            <i class="bi bi-briefcase-fill pill-icon"></i>
            <span class="pill-text">Salarié</span>
          </label>
          <label class="fil-pill" id="pill_conjoint">
            <input type="checkbox" name="fil_conjoint" value="1" id="cb_conjoint">
            <i class="bi bi-people-fill pill-icon"></i>
            <span class="pill-text">Conjoint(e)</span>
          </label>
        </div>
      </div>
    </div>

    <!-- SECTION 2: Le malade -->
    <div class="mf-section">
      <div class="mf-section-head">
        <i class="bi bi-person-heart"></i> Le malade
      </div>
      <div class="mf-section-body">
        <div class="patient-readonly">
          <div class="pr-item">
            <i class="bi bi-person-fill"></i>
            <strong><?= htmlspecialchars($patient['nom'] . ' ' . $patient['prenom']) ?></strong>
          </div>
          <div class="pr-item">
            <i class="bi bi-calendar3"></i>
            Né(e) le <?= htmlspecialchars($patient['date_naissance']) ?>
          </div>
          <div class="pr-item">
            <i class="bi bi-gender-<?= $patient['sexe'] === 'M' ? 'male' : 'female' ?>"></i>
            <?= $patient['sexe'] === 'M' ? 'Masculin' : 'Féminin' ?>
          </div>
          <div class="pr-item">
            <i class="bi bi-hash"></i>
            Dossier <?= htmlspecialchars($patient['dossier_numero']) ?>
          </div>
        </div>
      </div>
    </div>

    <!-- SECTION 3: Prestations -->
    <div class="mf-section">
      <div class="mf-section-head">
        <i class="bi bi-calendar-check"></i> Prestations
      </div>
      <div class="mf-section-body">

        <div class="prestation-row">
          <i class="bi bi-hospital prest-icon"></i>
          <span class="prest-label">Consultation externe</span>
          <div class="prest-date">
            <label>Date</label>
            <input type="date" class="form-control" name="date_soins_externe">
          </div>
        </div>

        <div class="prestation-row">
          <i class="bi bi-building-fill-add prest-icon"></i>
          <span class="prest-label">Hospitalisation</span>
          <div class="prest-date">
            <label>Date d'entrée</label>
            <input type="date" class="form-control" name="date_hosp">
          </div>
        </div>

      </div>
    </div>

    <!-- SECTION 4: Diagnostic -->
    <div class="mf-section">
      <div class="mf-section-head">
        <i class="bi bi-clipboard2-pulse"></i> Diagnostic
      </div>
      <div class="mf-section-body">
        <label class="form-label">Diagnostic d'entrée</label>
        <textarea class="form-control" name="diagnostic" rows="4"
          placeholder="Diagnostic principal, diagnostics associés..."></textarea>
      </div>
    </div>

    <!-- SECTION 5: Signature -->
    <div class="mf-section">
      <div class="mf-section-head">
        <i class="bi bi-pen"></i> Signature
      </div>
      <div class="mf-section-body">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Date</label>
            <input type="date" class="form-control" name="date_signature" value="<?= date('Y-m-d') ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label">Lieu</label>
            <input type="text" class="form-control" name="lieu_sign" value="Njombé">
          </div>
        </div>
      </div>
    </div>

  </form>
</div>

<script>
  // Filiation pill toggle
  document.querySelectorAll('.fil-pill').forEach(function(pill) {
    pill.addEventListener('click', function() {
      var cb = this.querySelector('input[type="checkbox"]');
      // Toggle fires after click, so we read new state on next tick
      setTimeout(function() {
        if (cb.checked) {
          pill.classList.add('active');
        } else {
          pill.classList.remove('active');
        }
      }, 0);
    });
  });
</script>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
