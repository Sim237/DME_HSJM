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
  .modern-wrapper { max-width: 900px; margin: 0 auto; padding: 20px 16px 80px; }

  /* Sticky action bar */
  .action-bar {
    position: sticky; top: 0; z-index: 100;
    background: #fff; box-shadow: 0 2px 12px rgba(0,0,0,.10);
    padding: 10px 20px; display: flex; align-items: center; gap: 10px;
    margin-bottom: 24px; border-radius: 0 0 12px 12px;
  }
  .action-bar .spacer { flex: 1; }
  .btn-action {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 8px 18px; border-radius: 8px; font-weight: 600;
    font-size: 0.93rem; border: none; cursor: pointer; transition: opacity .15s;
  }
  .btn-action:hover { opacity: .88; }
  .btn-back { background: #f1f5f9; color: #475569; }
  .btn-save { background: #db2777; color: #fff; }
  .btn-print { background: #f1f5f9; color: #475569; }

  /* Patient banner */
  .patient-banner {
    background: linear-gradient(135deg, #db2777, #ec4899);
    border-radius: 16px; padding: 22px 28px; color: #fff;
    margin-bottom: 24px; display: flex; align-items: center; gap: 20px;
    box-shadow: 0 4px 20px rgba(219,39,119,.30);
  }
  .patient-banner .avatar {
    width: 60px; height: 60px; border-radius: 50%;
    background: rgba(255,255,255,.20); display: flex; align-items: center;
    justify-content: center; font-size: 1.8rem; flex-shrink: 0;
  }
  .patient-banner .info h4 { margin: 0 0 6px; font-size: 1.25rem; font-weight: 700; }
  .patient-banner .badges { display: flex; flex-wrap: wrap; gap: 8px; }
  .patient-banner .badge-item {
    background: rgba(255,255,255,.22); border-radius: 20px;
    padding: 4px 14px; font-size: 0.83rem; font-weight: 600;
  }

  /* Section cards */
  .form-section {
    background: #fff; border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,.07);
    margin-bottom: 20px; overflow: hidden;
  }
  .form-section-header {
    background: #fdf2f8; padding: 14px 22px;
    font-size: 0.95rem; font-weight: 700;
    color: #db2777; display: flex; align-items: center; gap: 8px;
    border-bottom: 1.5px solid #fce7f3;
  }
  .form-section-body { padding: 22px; }

  /* Form controls */
  .form-label {
    text-transform: uppercase; font-size: 0.82rem;
    font-weight: 600; color: #64748b; margin-bottom: 6px;
  }
  .form-control {
    border: 1.5px solid #e2e8f0 !important;
    border-radius: .6rem !important;
  }
  .form-control:focus {
    border-color: #db2777 !important;
    box-shadow: 0 0 0 3px rgba(219,39,119,.12) !important;
  }
  .form-control[readonly] { background: #f8fafc; color: #64748b; }
</style>

<div class="modern-wrapper">

  <!-- Action bar -->
  <div class="action-bar">
    <?php if ($from_suivi): ?>
      <button type="button" class="btn-action btn-back" onclick="history.back()">
        <i class="bi bi-arrow-left"></i> Retour
      </button>
    <?php endif; ?>
    <span class="spacer"></span>
    <button type="button" class="btn-action btn-print" onclick="window.print()">
      <i class="bi bi-printer"></i> Imprimer
    </button>
    <button type="button" class="btn-action btn-save" form="formGrossesse" id="btnSaveGrossesse">
      <i class="bi bi-floppy"></i> Enregistrer
    </button>
  </div>

  <!-- Patient banner -->
  <div class="patient-banner">
    <div class="avatar"><i class="bi bi-person-heart"></i></div>
    <div class="info">
      <h4><?= htmlspecialchars($patient['prenom'] . ' ' . $patient['nom']) ?></h4>
      <div class="badges">
        <span class="badge-item"><i class="bi bi-folder2"></i> Dossier N° <?= htmlspecialchars($patient['dossier_numero']) ?></span>
        <span class="badge-item"><i class="bi bi-calendar3"></i> Né(e) le <?= htmlspecialchars($patient['date_naissance']) ?></span>
        <span class="badge-item"><i class="bi bi-hourglass-split"></i> <?= (int)$age ?> ans</span>
        <?php if (!empty($patient['telephone'])): ?>
          <span class="badge-item"><i class="bi bi-telephone"></i> <?= htmlspecialchars($patient['telephone']) ?></span>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Form -->
  <form id="formGrossesse" method="post" action="#">

    <input type="hidden" name="patient_id" value="<?= (int)$patient['id'] ?>">
    <input type="hidden" name="hosp_id" value="<?= (int)$hosp_id ?>">

    <!-- Section 1: Praticien -->
    <div class="form-section">
      <div class="form-section-header">
        <i class="bi bi-person-badge"></i> Informations du praticien
      </div>
      <div class="form-section-body">
        <div class="row g-3">
          <div class="col-md-7">
            <label class="form-label">Nom du praticien</label>
            <input type="text" class="form-control rounded-3" name="praticien_nom"
                   value="<?= htmlspecialchars($medecin_nom) ?>">
          </div>
          <div class="col-md-5">
            <label class="form-label">Date de l'examen</label>
            <input type="date" class="form-control rounded-3" name="date_examen"
                   value="<?= date('Y-m-d') ?>">
          </div>
        </div>
      </div>
    </div>

    <!-- Section 2: Patiente (read-only) -->
    <div class="form-section">
      <div class="form-section-header">
        <i class="bi bi-person-heart"></i> Patiente
      </div>
      <div class="form-section-body">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Nom &amp; Prénom</label>
            <input type="text" class="form-control rounded-3" readonly
                   value="<?= htmlspecialchars($patient['prenom'] . ' ' . $patient['nom']) ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label">Date de naissance</label>
            <input type="text" class="form-control rounded-3" readonly
                   value="<?= htmlspecialchars($patient['date_naissance']) ?>">
          </div>
          <div class="col-md-2">
            <label class="form-label">Âge</label>
            <input type="text" class="form-control rounded-3" readonly
                   value="<?= (int)$age ?> ans">
          </div>
        </div>
      </div>
    </div>

    <!-- Section 3: Résultat de l'examen -->
    <div class="form-section">
      <div class="form-section-header">
        <i class="bi bi-clipboard2-heart"></i> Résultat de l'examen
      </div>
      <div class="form-section-body">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Durée de grossesse</label>
            <input type="text" class="form-control rounded-3" name="duree_grossesse"
                   placeholder="ex: 24 semaines d'aménorrhée">
          </div>
          <div class="col-md-6">
            <label class="form-label">Date probable d'accouchement</label>
            <input type="date" class="form-control rounded-3" name="date_accouchement_prevue">
          </div>
        </div>
      </div>
    </div>

    <!-- Section 4: Signature -->
    <div class="form-section">
      <div class="form-section-header">
        <i class="bi bi-pen"></i> Signature
      </div>
      <div class="form-section-body">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Lieu</label>
            <input type="text" class="form-control rounded-3" name="lieu_certificat"
                   value="Njombé">
          </div>
          <div class="col-md-6">
            <label class="form-label">Date du certificat</label>
            <input type="date" class="form-control rounded-3" name="date_certificat"
                   value="<?= date('Y-m-d') ?>">
          </div>
        </div>
      </div>
    </div>

  </form>
</div>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
