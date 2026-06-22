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
  .btn-back  { background: #f1f5f9; color: #475569; }
  .btn-save  { background: #475569; color: #fff; }
  .btn-print { background: #f1f5f9; color: #475569; }

  /* Patient banner */
  .patient-banner {
    background: linear-gradient(135deg, #475569, #64748b);
    border-radius: 16px; padding: 22px 28px; color: #fff;
    margin-bottom: 24px; display: flex; align-items: center; gap: 20px;
    box-shadow: 0 4px 20px rgba(71,85,105,.30);
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
  .patient-banner .legal-tag {
    margin-left: auto; background: rgba(255,255,255,.15);
    border: 2px solid rgba(255,255,255,.40); border-radius: 8px;
    padding: 6px 14px; font-size: 0.8rem; font-weight: 700;
    letter-spacing: .05em; text-transform: uppercase; flex-shrink: 0;
  }

  /* Section cards */
  .form-section {
    background: #fff; border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,.07);
    margin-bottom: 20px; overflow: hidden;
  }
  .form-section-header {
    background: #f8fafc; padding: 14px 22px;
    font-size: 0.95rem; font-weight: 700;
    color: #475569; display: flex; align-items: center; gap: 8px;
    border-bottom: 1.5px solid #e2e8f0;
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
    border-color: #475569 !important;
    box-shadow: 0 0 0 3px rgba(71,85,105,.12) !important;
  }
  .form-control[readonly] { background: #f8fafc; color: #64748b; }

  /* Genre de mort radio options */
  .genre-grid {
    display: grid; grid-template-columns: 1fr 1fr;
    gap: 12px; margin-top: 8px;
  }
  .genre-option {
    display: flex; align-items: center; gap: 12px;
    padding: 14px 18px; border-radius: 10px;
    border: 2px solid #e2e8f0; cursor: pointer;
    background: #fff; transition: all .18s;
  }
  .genre-option:hover { border-color: #475569; background: #f8fafc; }
  .genre-option input[type="radio"] { display: none; }
  .genre-option .radio-circle {
    width: 20px; height: 20px; border-radius: 50%;
    border: 2px solid #cbd5e1; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    transition: all .15s;
  }
  .genre-option input[type="radio"]:checked ~ .radio-circle {
    border-color: #475569; background: #475569;
  }
  .genre-option input[type="radio"]:checked ~ .radio-circle::after {
    content: ''; width: 8px; height: 8px; border-radius: 50%;
    background: #fff; display: block;
  }
  .genre-option input[type="radio"]:checked ~ .option-label { color: #1e293b; font-weight: 700; }
  .genre-option.selected { border-color: #475569; background: #f1f5f9; }
  .option-label { font-size: 0.92rem; color: #475569; font-weight: 600; }
  .option-sublabel { font-size: 0.76rem; color: #94a3b8; display: block; margin-top: 2px; }

  /* Info box for bilingual text */
  .bilingual-note {
    background: #f8fafc; border-left: 4px solid #475569;
    border-radius: 8px; padding: 16px 20px; margin-top: 8px;
    font-size: 0.88rem; color: #475569; line-height: 1.7;
  }
  .bilingual-note .fr { font-style: italic; }
  .bilingual-note .en { color: #64748b; font-size: 0.83rem; margin-top: 4px; }
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
    <button type="button" class="btn-action btn-save" form="formMort" id="btnSaveMort">
      <i class="bi bi-floppy"></i> Enregistrer
    </button>
  </div>

  <!-- Patient banner -->
  <div class="patient-banner">
    <div class="avatar"><i class="bi bi-file-medical"></i></div>
    <div class="info">
      <h4><?= htmlspecialchars($patient['prenom'] . ' ' . $patient['nom']) ?></h4>
      <div class="badges">
        <span class="badge-item"><i class="bi bi-folder2"></i> Dossier N° <?= htmlspecialchars($patient['dossier_numero']) ?></span>
        <span class="badge-item"><i class="bi bi-calendar3"></i> Né(e) le <?= htmlspecialchars($patient['date_naissance']) ?></span>
        <span class="badge-item"><i class="bi bi-hourglass-split"></i> <?= (int)$age ?> ans</span>
        <span class="badge-item"><i class="bi bi-gender-<?= strtolower($patient['sexe'] ?? 'ambiguous') ?>"></i>
          <?= ($patient['sexe'] === 'M') ? 'Masculin' : (($patient['sexe'] === 'F') ? 'Féminin' : htmlspecialchars($patient['sexe'] ?? '')) ?></span>
      </div>
    </div>
    <div class="legal-tag"><i class="bi bi-shield-check"></i> Document officiel</div>
  </div>

  <!-- Form -->
  <form id="formMort" method="post" action="#">

    <input type="hidden" name="patient_id" value="<?= (int)$patient['id'] ?>">
    <input type="hidden" name="hosp_id"    value="<?= (int)$hosp_id ?>">

    <!-- Section 1 : Praticien & Service -->
    <div class="form-section">
      <div class="form-section-header">
        <i class="bi bi-person-badge"></i> Praticien &amp; Service
      </div>
      <div class="form-section-body">
        <div class="row g-3">
          <div class="col-md-7">
            <label class="form-label">Je soussigné (Médecin signataire)</label>
            <?php
              $__ms_field   = 'soussigne';
              $__ms_label   = 'Médecin signataire';
              $__ms_value   = $formData['soussigne'] ?? $medecin_nom;
              $__ms_id_field = 'medecin_id';
              include __DIR__ . '/_medecin_select.php';
            ?>
          </div>
          <div class="col-md-5">
            <label class="form-label">Service médical</label>
            <input type="text" class="form-control rounded-3" name="service"
                   value="<?= htmlspecialchars($formData['service'] ?? '') ?>"
                   placeholder="Ex : Médecine générale">
          </div>
        </div>
      </div>
    </div>

    <!-- Section 2 : Informations du décès -->
    <div class="form-section">
      <div class="form-section-header">
        <i class="bi bi-clipboard2-pulse"></i> Informations du décès
      </div>
      <div class="form-section-body">
        <div class="row g-3">

          <div class="col-12">
            <label class="form-label">Nom complet du défunt</label>
            <input type="text" class="form-control rounded-3" name="defunt_nom"
                   value="<?= htmlspecialchars($formData['defunt_nom'] ?? ($patient['prenom'] . ' ' . $patient['nom'])) ?>">
          </div>

          <div class="col-md-6">
            <label class="form-label">Date du décès</label>
            <input type="date" class="form-control rounded-3" name="date_deces"
                   value="<?= htmlspecialchars($formData['date_deces'] ?? '') ?>">
          </div>

          <div class="col-md-6">
            <label class="form-label">Heure du décès</label>
            <input type="time" class="form-control rounded-3" name="heure_deces"
                   value="<?= htmlspecialchars($formData['heure_deces'] ?? '') ?>">
          </div>

          <div class="col-12">
            <label class="form-label">Cause principale du décès</label>
            <textarea class="form-control rounded-3" name="cause_deces" rows="3"
                      placeholder="Décrivez la cause principale du décès..."><?= htmlspecialchars($formData['cause_deces'] ?? '') ?></textarea>
          </div>

          <div class="col-12">
            <label class="form-label">Diagnostic éventuel</label>
            <textarea class="form-control rounded-3" name="diagnostic" rows="4"
                      placeholder="Diagnostic ou observations complémentaires..."><?= htmlspecialchars($formData['diagnostic'] ?? '') ?></textarea>
          </div>

        </div>
      </div>
    </div>

    <!-- Section 3 : Genre de mort -->
    <div class="form-section">
      <div class="form-section-header">
        <i class="bi bi-list-check"></i> Genre de mort
      </div>
      <div class="form-section-body">
        <label class="form-label">Nature du décès</label>
        <div class="genre-grid">

          <label class="genre-option <?= (($formData['genre_mort'] ?? '') === 'affection_naturelle') ? 'selected' : '' ?>">
            <input type="radio" name="genre_mort" value="affection_naturelle"
                   <?= (($formData['genre_mort'] ?? '') === 'affection_naturelle') ? 'checked' : '' ?>>
            <div class="radio-circle"></div>
            <div class="option-label">
              Affection naturelle
              <span class="option-sublabel">Natural cause of death</span>
            </div>
          </label>

          <label class="genre-option <?= (($formData['genre_mort'] ?? '') === 'mort_suspecte') ? 'selected' : '' ?>">
            <input type="radio" name="genre_mort" value="mort_suspecte"
                   <?= (($formData['genre_mort'] ?? '') === 'mort_suspecte') ? 'checked' : '' ?>>
            <div class="radio-circle"></div>
            <div class="option-label">
              Mort suspecte
              <span class="option-sublabel">Suspicious death</span>
            </div>
          </label>

          <label class="genre-option <?= (($formData['genre_mort'] ?? '') === 'mort_violente') ? 'selected' : '' ?>">
            <input type="radio" name="genre_mort" value="mort_violente"
                   <?= (($formData['genre_mort'] ?? '') === 'mort_violente') ? 'checked' : '' ?>>
            <div class="radio-circle"></div>
            <div class="option-label">
              Mort violente
              <span class="option-sublabel">Violent death</span>
            </div>
          </label>

          <label class="genre-option <?= (($formData['genre_mort'] ?? '') === 'mort_inconnue') ? 'selected' : '' ?>">
            <input type="radio" name="genre_mort" value="mort_inconnue"
                   <?= (($formData['genre_mort'] ?? '') === 'mort_inconnue') ? 'checked' : '' ?>>
            <div class="radio-circle"></div>
            <div class="option-label">
              Cause inconnue
              <span class="option-sublabel">Unknown cause of death</span>
            </div>
          </label>

        </div>
      </div>
    </div>

    <!-- Section 4 : Note bilingue -->
    <div class="form-section">
      <div class="form-section-header">
        <i class="bi bi-translate"></i> Texte officiel / Official statement
      </div>
      <div class="form-section-body">
        <div class="bilingual-note">
          <div class="fr">Son corps (ne) présente (pas) un danger pour la population et peut être transporté à son lieu d'origine conformément aux lois en vigueur.</div>
          <div class="en">His corps is (not) free from any obvious contamination for the population hence can be transported to place of origin.</div>
          <div class="fr" style="margin-top:10px;">En foi de quoi le présent certificat est délivré pour servir et valoir ce que de droit.</div>
          <div class="en">His serves as a testimonial.</div>
        </div>
      </div>
    </div>

    <!-- Section 5 : Lieu, date & Signature -->
    <div class="form-section">
      <div class="form-section-header">
        <i class="bi bi-pen"></i> Lieu, date et signature du médecin
      </div>
      <div class="form-section-body">
        <div class="row g-3 mb-4">
          <div class="col-md-6">
            <label class="form-label">Lieu</label>
            <input type="text" class="form-control rounded-3" name="lieu_signature"
                   value="<?= htmlspecialchars($formData['lieu_signature'] ?? 'Njombé') ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label">Date de signature</label>
            <input type="date" class="form-control rounded-3" name="date_signature"
                   value="<?= htmlspecialchars($formData['date_signature'] ?? date('Y-m-d')) ?>">
          </div>
        </div>
        <?php
          $__sp_field    = 'signature_medecin';
          $__sp_label    = 'Signature du médecin';
          $__sp_subname  = $formData['soussigne'] ?? $medecin_nom;
          $__sp_existing = $formData['signature_medecin'] ?? '';
          $__sp_color    = '#475569';
          include __DIR__ . '/_signature_pad.php';
        ?>
      </div>
    </div>

  </form>
</div>

<script>
/* Highlight selected genre-option on JS click (for visual feedback) */
document.querySelectorAll('.genre-option input[type="radio"]').forEach(function(radio) {
  radio.addEventListener('change', function() {
    document.querySelectorAll('.genre-option').forEach(function(opt) {
      opt.classList.remove('selected');
    });
    if (this.checked) {
      this.closest('.genre-option').classList.add('selected');
    }
  });
});
</script>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
