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
  .mf-topbar .mf-title { font-weight: 700; font-size: 1rem; color: #0c4a6e; display: flex; align-items: center; gap: 8px; }
  .mf-topbar .mf-actions { display: flex; gap: 8px; }

  .mf-patient-banner {
    border-radius: 16px; padding: 20px 24px; color: white; margin-bottom: 20px;
    background: linear-gradient(135deg, #0c4a6e, #0369a1);
    box-shadow: 0 4px 20px rgba(3,105,161,.30);
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
    background: #f0f9ff; color: #0369a1; border-bottom: 1.5px solid #bae6fd;
  }
  .mf-section-body { padding: 20px; }

  .form-label { font-weight: 600; font-size: .8rem; color: #64748b; text-transform: uppercase; letter-spacing: .4px; }
  .form-control, .form-select {
    border: 1.5px solid #e2e8f0; border-radius: 8px;
    font-size: .93rem; padding: 10px 12px;
  }
  .form-control:focus, .form-select:focus {
    border-color: #0369a1; box-shadow: 0 0 0 3px rgba(3,105,161,.18);
    outline: none;
  }

  /* Checklist rows */
  .check-row {
    display: flex; align-items: center; justify-content: space-between;
    padding: 10px 14px; border-radius: 8px; margin-bottom: 6px;
    background: #f8fafc; border: 1px solid #e2e8f0; gap: 12px;
  }
  .check-row:hover { background: #f0f9ff; border-color: #bae6fd; }
  .check-row .check-label { font-size: .92rem; color: #334155; font-weight: 500; flex: 1; }

  /* Bilan checkboxes */
  .bilan-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; }
  .bilan-item {
    display: flex; align-items: center; gap: 8px;
    background: #f8fafc; border: 1.5px solid #e2e8f0;
    border-radius: 8px; padding: 8px 12px; cursor: pointer;
    transition: all .15s;
  }
  .bilan-item:hover { background: #f0f9ff; border-color: #0369a1; }
  .bilan-item input[type="checkbox"]:checked + span { color: #0369a1; font-weight: 600; }
  .bilan-item input[type="checkbox"] { accent-color: #0369a1; width: 16px; height: 16px; cursor: pointer; }
  .bilan-item span { font-size: .88rem; color: #334155; }

  /* Vitals boxes */
  .vital-box { text-align: center; }
  .vital-box .vital-icon { font-size: 1.5rem; color: #0369a1; margin-bottom: 4px; }
  .vital-box .form-control { text-align: center; font-size: 1rem; font-weight: 600; color: #0c4a6e; }

  /* Subsection */
  .sub-head {
    font-size: .8rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: .5px; color: #64748b; margin: 16px 0 10px;
    display: flex; align-items: center; gap: 6px;
  }
  .sub-head::after { content: ''; flex: 1; height: 1px; background: #e2e8f0; }

  .btn-save-main {
    background: linear-gradient(135deg, #0c4a6e, #0369a1); color: white;
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
</style>

<div class="mf-topbar">
  <div class="mf-title">
    <i class="bi bi-clipboard2-check"></i>
    Checklist Pré-opératoire Bloc
  </div>
  <div class="mf-actions">
    <?php if ($from_suivi): ?>
      <a href="javascript:history.back()" class="btn-back-top">
        <i class="bi bi-arrow-left"></i> Retour
      </a>
    <?php endif; ?>
    <button type="submit" form="formBloc" class="btn-save-main">
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

  <form id="formBloc" action="#" method="POST" novalidate>
    <input type="hidden" name="patient_id" value="<?= $patient['id'] ?>">
    <input type="hidden" name="hosp_id" value="<?= $hosp_id ?>">

    <!-- SECTION 1: Identification -->
    <div class="mf-section">
      <div class="mf-section-head">
        <i class="bi bi-person-badge"></i> Identification
      </div>
      <div class="mf-section-body">
        <div class="mb-0">
          <label class="form-label">Diagnostic</label>
          <input type="text" class="form-control" name="diagnostic" required placeholder="Diagnostic pré-opératoire...">
        </div>
      </div>
    </div>

    <!-- SECTION 2: Modalités pratiques -->
    <div class="mf-section">
      <div class="mf-section-head">
        <i class="bi bi-clipboard-check"></i> Modalités pratiques
      </div>
      <div class="mf-section-body">

        <div class="check-row">
          <span class="check-label">Présence du dossier médical complet</span>
          <div class="btn-group btn-group-sm">
            <input type="radio" class="btn-check" name="check_dossier" id="cd_o" value="O">
            <label class="btn btn-outline-success" for="cd_o">OUI</label>
            <input type="radio" class="btn-check" name="check_dossier" id="cd_n" value="N" checked>
            <label class="btn btn-outline-danger" for="cd_n">NON</label>
          </div>
        </div>

        <div class="check-row">
          <span class="check-label">Service du bloc informé</span>
          <div class="btn-group btn-group-sm">
            <input type="radio" class="btn-check" name="check_informe" id="ci_o" value="O">
            <label class="btn btn-outline-success" for="ci_o">OUI</label>
            <input type="radio" class="btn-check" name="check_informe" id="ci_n" value="N" checked>
            <label class="btn btn-outline-danger" for="ci_n">NON</label>
          </div>
        </div>

        <div class="check-row">
          <span class="check-label">Type de chirurgie</span>
          <div class="btn-group btn-group-sm">
            <input type="radio" class="btn-check" name="type_chir" id="tc_propre" value="propre">
            <label class="btn btn-outline-success" for="tc_propre">Propre</label>
            <input type="radio" class="btn-check" name="type_chir" id="tc_sale" value="sale" checked>
            <label class="btn btn-outline-danger" for="tc_sale">Sale</label>
          </div>
        </div>

      </div>
    </div>

    <!-- SECTION 3: Préparation des opérés -->
    <div class="mf-section">
      <div class="mf-section-head">
        <i class="bi bi-clipboard2-pulse"></i> Préparation des opérés
      </div>
      <div class="mf-section-body">

        <?php
        $prep_items = [
          ['name' => 'consentement',   'label' => 'Consentement éclairé signé',         'oid' => 'con_o',   'nid' => 'con_n'],
          ['name' => 'consult_anesth', 'label' => 'Consultation pré-anesthésique',       'oid' => 'ca_o',    'nid' => 'ca_n'],
          ['name' => 'visite_anesth',  'label' => 'Visite pré-anesthésique',             'oid' => 'va_o',    'nid' => 'va_n'],
          ['name' => 'a_jeun',         'label' => 'Patient à jeun (6h minimum)',          'oid' => 'aj_o',    'nid' => 'aj_n'],
          ['name' => 'cathe_vesical',  'label' => 'Cathétérisme vésical',                'oid' => 'cv_o',    'nid' => 'cv_n'],
          ['name' => 'toilette',       'label' => 'Toilette du malade',                  'oid' => 'toi_o',   'nid' => 'toi_n'],
          ['name' => 'site_op',        'label' => 'Préparation du site opératoire',      'oid' => 'so_o',    'nid' => 'so_n'],
        ];
        foreach ($prep_items as $item): ?>
        <div class="check-row">
          <span class="check-label"><?= $item['label'] ?></span>
          <div class="btn-group btn-group-sm">
            <input type="radio" class="btn-check" name="<?= $item['name'] ?>" id="<?= $item['oid'] ?>" value="O">
            <label class="btn btn-outline-success" for="<?= $item['oid'] ?>">OUI</label>
            <input type="radio" class="btn-check" name="<?= $item['name'] ?>" id="<?= $item['nid'] ?>" value="N" checked>
            <label class="btn btn-outline-danger" for="<?= $item['nid'] ?>">NON</label>
          </div>
        </div>
        <?php endforeach; ?>

        <div class="sub-head"><i class="bi bi-funnel"></i> Préparation colique</div>

        <?php
        $colique_items = [
          ['name' => 'lavemenent', 'label' => 'Lavement évacuateur',  'oid' => 'lav_o',  'nid' => 'lav_n'],
          ['name' => 'sng_irrig',  'label' => 'Irrigation via SNG',   'oid' => 'sng_o',  'nid' => 'sng_n'],
          ['name' => 'x_prep',     'label' => 'X-Prep',               'oid' => 'xp_o',   'nid' => 'xp_n'],
        ];
        foreach ($colique_items as $item): ?>
        <div class="check-row">
          <span class="check-label"><?= $item['label'] ?></span>
          <div class="btn-group btn-group-sm">
            <input type="radio" class="btn-check" name="<?= $item['name'] ?>" id="<?= $item['oid'] ?>" value="O">
            <label class="btn btn-outline-success" for="<?= $item['oid'] ?>">OUI</label>
            <input type="radio" class="btn-check" name="<?= $item['name'] ?>" id="<?= $item['nid'] ?>" value="N" checked>
            <label class="btn btn-outline-danger" for="<?= $item['nid'] ?>">NON</label>
          </div>
        </div>
        <?php endforeach; ?>

      </div>
    </div>

    <!-- SECTION 4: Bilans pré-opératoires -->
    <div class="mf-section">
      <div class="mf-section-head">
        <i class="bi bi-droplet-half"></i> Bilans pré-opératoires
      </div>
      <div class="mf-section-body">

        <label class="form-label mb-3">Examens demandés</label>
        <div class="bilan-grid mb-4">
          <?php
          $bilans = ['NFS','TP','TCK','GS-Rh','Urée','Créat','LAV','Ionogramme','Radiographies','Echographie','TDM'];
          foreach ($bilans as $b): ?>
          <label class="bilan-item">
            <input type="checkbox" name="bilans[]" value="<?= $b ?>">
            <span><?= $b ?></span>
          </label>
          <?php endforeach; ?>
        </div>

        <div class="check-row">
          <span class="check-label"><i class="bi bi-droplet-fill text-danger me-1"></i> Poches de sang</span>
          <div class="btn-group btn-group-sm">
            <input type="radio" class="btn-check" name="poches" id="poc_o" value="O">
            <label class="btn btn-outline-success" for="poc_o">Disponibles</label>
            <input type="radio" class="btn-check" name="poches" id="poc_n" value="N" checked>
            <label class="btn btn-outline-danger" for="poc_n">Néant</label>
          </div>
        </div>

      </div>
    </div>

    <!-- SECTION 5: Paramètres vitaux -->
    <div class="mf-section">
      <div class="mf-section-head">
        <i class="bi bi-heart-pulse"></i> Paramètres vitaux à l'entrée
      </div>
      <div class="mf-section-body">
        <div class="row g-3">
          <div class="col-6 col-md-3">
            <div class="vital-box">
              <div class="vital-icon"><i class="bi bi-activity"></i></div>
              <label class="form-label">Tension artérielle</label>
              <input type="text" class="form-control" name="param_ta" placeholder="ex: 12/8 cmHg">
            </div>
          </div>
          <div class="col-6 col-md-3">
            <div class="vital-box">
              <div class="vital-icon"><i class="bi bi-heart-pulse-fill"></i></div>
              <label class="form-label">Pouls</label>
              <input type="number" class="form-control" name="param_pouls" placeholder="bpm">
            </div>
          </div>
          <div class="col-6 col-md-3">
            <div class="vital-box">
              <div class="vital-icon"><i class="bi bi-thermometer-half"></i></div>
              <label class="form-label">Température</label>
              <input type="text" class="form-control" name="param_temp" placeholder="°C">
            </div>
          </div>
          <div class="col-6 col-md-3">
            <div class="vital-box">
              <div class="vital-icon"><i class="bi bi-box-seam"></i></div>
              <label class="form-label">Poids</label>
              <input type="number" class="form-control" name="param_poids" placeholder="kg">
            </div>
          </div>
        </div>
      </div>
    </div>

  </form>
</div>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
