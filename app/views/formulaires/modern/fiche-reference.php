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
  .mf-topbar .mf-title { font-weight: 700; font-size: 1rem; color: #0d9488; display: flex; align-items: center; gap: 8px; }
  .mf-topbar .mf-actions { display: flex; gap: 8px; }

  .mf-patient-banner {
    border-radius: 16px; padding: 20px 24px; color: white; margin-bottom: 20px;
    background: linear-gradient(135deg, #0d9488, #14b8a6);
    box-shadow: 0 4px 20px rgba(13,148,136,.28);
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
    background: #f0fdfa; color: #0d9488; border-bottom: 1.5px solid #99f6e4;
  }
  .mf-section-body { padding: 20px; }

  .form-label { font-weight: 600; font-size: .8rem; color: #64748b; text-transform: uppercase; letter-spacing: .4px; }
  .form-control, .form-select {
    border: 1.5px solid #e2e8f0; border-radius: 8px;
    font-size: .93rem; padding: 10px 12px;
  }
  .form-control:focus, .form-select:focus {
    border-color: #0d9488; box-shadow: 0 0 0 3px rgba(13,148,136,.18);
    outline: none;
  }
  textarea.form-control { resize: vertical; min-height: 140px; line-height: 1.7; }

  /* Destination highlight */
  .destination-input {
    border-color: #0d9488 !important;
    background: #f0fdfa;
    font-weight: 600; font-size: 1rem !important;
  }

  /* Info read-only strip */
  .readonly-info {
    background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 10px;
    padding: 14px 16px; display: flex; flex-wrap: wrap; gap: 12px 24px;
    margin-bottom: 16px;
  }
  .readonly-info .ri-item { display: flex; align-items: center; gap: 6px; font-size: .9rem; color: #334155; }
  .readonly-info .ri-item i { color: #0d9488; }
  .readonly-info .ri-item strong { font-weight: 700; }

  .btn-save-main {
    background: linear-gradient(135deg, #0d9488, #14b8a6); color: white;
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

  /* Urgency contact card */
  .urgency-card {
    background: #fff7ed; border: 1.5px solid #fed7aa;
    border-radius: 10px; padding: 16px;
  }

  /* Destination visual */
  .dest-wrap { position: relative; }
  .dest-wrap i.dest-icon {
    position: absolute; left: 12px; top: 50%; transform: translateY(-50%);
    font-size: 1.1rem; color: #0d9488; pointer-events: none;
  }
  .dest-wrap .form-control { padding-left: 38px; }
</style>

<div class="mf-topbar">
  <div class="mf-title">
    <i class="bi bi-arrow-up-right-circle"></i>
    Fiche de Référence
  </div>
  <div class="mf-actions">
    <?php if ($from_suivi): ?>
      <a href="javascript:history.back()" class="btn-back-top">
        <i class="bi bi-arrow-left"></i> Retour
      </a>
    <?php endif; ?>
    <button type="submit" form="formReference" class="btn-save-main">
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

  <form id="formReference" action="#" method="POST" novalidate>
    <input type="hidden" name="patient_id" value="<?= $patient['id'] ?>">
    <input type="hidden" name="hosp_id" value="<?= $hosp_id ?>">

    <!-- SECTION 1: Patient -->
    <div class="mf-section">
      <div class="mf-section-head">
        <i class="bi bi-person"></i> Patient
      </div>
      <div class="mf-section-body">
        <div class="readonly-info">
          <div class="ri-item"><i class="bi bi-person-fill"></i> <strong><?= htmlspecialchars($patient['nom'] . ' ' . $patient['prenom']) ?></strong></div>
          <div class="ri-item"><i class="bi bi-calendar3"></i> Né(e) le <?= htmlspecialchars($patient['date_naissance']) ?> (<?= $age ?> ans)</div>
          <div class="ri-item"><i class="bi bi-gender-<?= $patient['sexe'] === 'M' ? 'male' : 'female' ?>"></i> <?= $patient['sexe'] === 'M' ? 'Masculin' : 'Féminin' ?></div>
        </div>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Téléphone</label>
            <input type="text" class="form-control" name="tel_patient" value="<?= htmlspecialchars($patient['telephone']) ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label">Résidence / Adresse</label>
            <input type="text" class="form-control" name="residence" value="<?= htmlspecialchars($patient['adresse'] ?? '') ?>">
          </div>
        </div>
      </div>
    </div>

    <!-- SECTION 2: Contact d'urgence -->
    <div class="mf-section">
      <div class="mf-section-head">
        <i class="bi bi-telephone-fill"></i> Contact d'urgence
      </div>
      <div class="mf-section-body">
        <div class="urgency-card">
          <div class="row g-3">
            <div class="col-md-7">
              <label class="form-label">Personne à prévenir</label>
              <input type="text" class="form-control" name="contact_urgence" placeholder="Nom et prénom...">
            </div>
            <div class="col-md-5">
              <label class="form-label">Téléphone</label>
              <input type="text" class="form-control" name="tel_urgence" placeholder="Numéro de contact...">
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- SECTION 3: Motif de référence -->
    <div class="mf-section">
      <div class="mf-section-head">
        <i class="bi bi-arrow-up-right-circle"></i> Motif de référence
      </div>
      <div class="mf-section-body">
        <label class="form-label">Description clinique et raison du transfert</label>
        <textarea class="form-control" name="motif" rows="6"
          placeholder="Description clinique, examens effectués, soins administrés, raison du transfert..."></textarea>
      </div>
    </div>

    <!-- SECTION 4: Destination -->
    <div class="mf-section">
      <div class="mf-section-head">
        <i class="bi bi-geo-alt"></i> Destination
      </div>
      <div class="mf-section-body">
        <label class="form-label">Lieu / Formation sanitaire d'accueil</label>
        <div class="dest-wrap">
          <i class="bi bi-hospital dest-icon"></i>
          <input type="text" class="form-control destination-input" name="lieu_reference" placeholder="Nom de l'hôpital d'accueil">
        </div>
      </div>
    </div>

    <!-- SECTION 5: Référent -->
    <div class="mf-section">
      <div class="mf-section-head">
        <i class="bi bi-pen"></i> Référent
      </div>
      <div class="mf-section-body">
        <div class="row g-3">
          <div class="col-md-5">
            <label class="form-label">Nom et signature du référent</label>
            <input type="text" class="form-control" name="referent_nom" value="<?= htmlspecialchars($medecin_nom) ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label">Date</label>
            <input type="date" class="form-control" name="date_ref" value="<?= date('Y-m-d') ?>">
          </div>
          <div class="col-md-3">
            <label class="form-label">Heure</label>
            <input type="time" class="form-control" name="heure_ref" value="<?= date('H:i') ?>">
          </div>
        </div>
      </div>
    </div>

  </form>
</div>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
