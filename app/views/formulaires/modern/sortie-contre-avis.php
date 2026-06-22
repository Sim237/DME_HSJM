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
  .btn-save { background: #dc2626; color: #fff; }
  .btn-print { background: #f1f5f9; color: #475569; }

  /* Patient banner */
  .patient-banner {
    background: linear-gradient(135deg, #dc2626, #ef4444);
    border-radius: 16px; padding: 22px 28px; color: #fff;
    margin-bottom: 24px; display: flex; align-items: center; gap: 20px;
    box-shadow: 0 4px 20px rgba(220,38,38,.30);
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
    background: #fff5f5; padding: 14px 22px;
    font-size: 0.95rem; font-weight: 700;
    color: #dc2626; display: flex; align-items: center; gap: 8px;
    border-bottom: 1.5px solid #fee2e2;
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
    border-color: #dc2626 !important;
    box-shadow: 0 0 0 3px rgba(220,38,38,.12) !important;
  }
  .form-control[readonly] { background: #f8fafc; color: #64748b; }

  /* Decision toggle buttons */
  .decision-btn {
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    padding: 16px 24px; border-radius: 12px; border: 2px solid #e2e8f0;
    cursor: pointer; background: #fff; font-size: 0.9rem; font-weight: 600;
    color: #475569; transition: all .18s; line-height: 1.4; text-align: center;
    min-width: 180px;
  }
  .decision-btn i { font-size: 1.6rem; margin-bottom: 6px; }
  .decision-btn:hover { border-color: #dc2626; background: #fff5f5; color: #dc2626; }
  .decision-btn.active {
    border-color: #dc2626; background: #fee2e2;
    color: #991b1b; font-weight: 700;
  }

  /* Warning box */
  .warning-legal {
    background: #fff5f5; border: 2px solid #fca5a5;
    border-radius: 12px; padding: 20px 24px;
  }
  .warning-legal .warning-icon {
    font-size: 2rem; color: #dc2626; margin-bottom: 10px;
  }
  .warning-legal p {
    color: #7f1d1d; font-weight: 600; font-size: 0.97rem;
    line-height: 1.7; margin-bottom: 16px;
  }
  .warning-legal .form-check-label {
    font-weight: 700; color: #991b1b; font-size: 0.95rem;
  }
  .form-check-input:checked { background-color: #dc2626; border-color: #dc2626; }
  .form-check-input:focus { box-shadow: 0 0 0 3px rgba(220,38,38,.18); }

  /* Patient readonly in decision section */
  .patient-readonly-box {
    background: #f8fafc; border: 1.5px solid #e2e8f0;
    border-radius: 10px; padding: 12px 18px;
    display: flex; align-items: center; gap: 10px;
  }
  .patient-readonly-box .name { font-weight: 700; color: #1e293b; font-size: 1rem; }
  .patient-readonly-box .sub { font-size: 0.83rem; color: #64748b; }
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
    <button type="button" class="btn-action btn-save" form="formSCA" id="btnSaveSCA">
      <i class="bi bi-floppy"></i> Enregistrer
    </button>
  </div>

  <!-- Patient banner -->
  <div class="patient-banner">
    <div class="avatar"><i class="bi bi-exclamation-triangle"></i></div>
    <div class="info">
      <h4><?= htmlspecialchars($patient['prenom'] . ' ' . $patient['nom']) ?></h4>
      <div class="badges">
        <span class="badge-item"><i class="bi bi-folder2"></i> Dossier N° <?= htmlspecialchars($patient['dossier_numero']) ?></span>
        <span class="badge-item"><i class="bi bi-calendar3"></i> Né(e) le <?= htmlspecialchars($patient['date_naissance']) ?></span>
        <span class="badge-item"><i class="bi bi-hourglass-split"></i> <?= (int)$age ?> ans</span>
      </div>
    </div>
    <div class="legal-tag"><i class="bi bi-shield-exclamation"></i> Document légal</div>
  </div>

  <!-- Form -->
  <form id="formSCA" method="post" action="#">

    <input type="hidden" name="patient_id" value="<?= (int)$patient['id'] ?>">
    <input type="hidden" name="hosp_id" value="<?= (int)$hosp_id ?>">

    <!-- Section 1: Identification du signataire -->
    <div class="form-section">
      <div class="form-section-header">
        <i class="bi bi-person-vcard"></i> Identification du signataire
      </div>
      <div class="form-section-body">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Nom(s) et Prénom(s) <span class="text-danger">*</span></label>
            <input type="text" class="form-control rounded-3" name="signataire_nom" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Qualité / Lien avec le patient</label>
            <input type="text" class="form-control rounded-3" name="lien_parente"
                   placeholder="Ex: Lui-même, Père, Épouse...">
          </div>
          <div class="col-12">
            <label class="form-label">Adresse complète</label>
            <input type="text" class="form-control rounded-3" name="adresse">
          </div>
          <div class="col-md-6">
            <label class="form-label">CNI N°</label>
            <input type="text" class="form-control rounded-3" name="cni_num">
          </div>
          <div class="col-md-6">
            <label class="form-label">Délivrée le</label>
            <input type="date" class="form-control rounded-3" name="cni_date">
          </div>
        </div>
      </div>
    </div>

    <!-- Section 2: Décision -->
    <div class="form-section">
      <div class="form-section-header">
        <i class="bi bi-arrow-right-circle"></i> Décision
      </div>
      <div class="form-section-body">
        <div class="row g-3">
          <div class="col-12">
            <label class="form-label">Le signataire :</label>
            <div style="display:flex; gap:12px; margin-top:8px; flex-wrap:wrap;">
              <button type="button" class="decision-btn active" data-val="sortir" onclick="setDecision(this)">
                <i class="bi bi-person-walking"></i>
                DÉCIDE DE SORTIR
              </button>
              <button type="button" class="decision-btn" data-val="faire_sortir" onclick="setDecision(this)">
                <i class="bi bi-people"></i>
                DÉCIDE DE FAIRE SORTIR
              </button>
            </div>
            <input type="hidden" name="choix_action" id="choix_action" value="sortir">
          </div>
          <div class="col-12">
            <label class="form-label">Patient concerné</label>
            <div class="patient-readonly-box">
              <i class="bi bi-person-circle" style="font-size:1.4rem; color:#94a3b8;"></i>
              <div>
                <div class="name"><?= htmlspecialchars($patient['prenom'] . ' ' . $patient['nom']) ?></div>
                <div class="sub">Dossier N° <?= htmlspecialchars($patient['dossier_numero']) ?> &mdash; <?= (int)$age ?> ans</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Section 3: Avertissement médical -->
    <div class="form-section">
      <div class="form-section-header" style="background:#fff5f5; color:#dc2626;">
        <i class="bi bi-exclamation-triangle-fill"></i> Avertissement médical
      </div>
      <div class="form-section-body">
        <div class="warning-legal">
          <div class="warning-icon"><i class="bi bi-shield-exclamation"></i></div>
          <p>
            Sur ma propre initiative et contre l'avis formel du médecin traitant,
            je dégage l'établissement de toute responsabilité quant aux conséquences
            pouvant découler de cette décision.
          </p>
          <div class="form-check">
            <input class="form-check-input" type="checkbox"
                   name="lu_compris" value="1" id="chkLuCompris" required>
            <label class="form-check-label" for="chkLuCompris">
              J'ai lu et compris cet avertissement
            </label>
          </div>
        </div>
      </div>
    </div>

    <!-- Section 4: Date et signature -->
    <div class="form-section">
      <div class="form-section-header">
        <i class="bi bi-calendar-check"></i> Date et signature
      </div>
      <div class="form-section-body">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Date</label>
            <input type="date" class="form-control rounded-3" name="date_signature"
                   value="<?= date('Y-m-d') ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label">Lieu</label>
            <input type="text" class="form-control rounded-3" name="lieu_signature"
                   value="Njombé">
          </div>
        </div>
      </div>
    </div>

  </form>
</div>

<script>
function setDecision(btn) {
  document.querySelectorAll('.decision-btn').forEach(function(b) {
    b.classList.remove('active');
  });
  btn.classList.add('active');
  document.getElementById('choix_action').value = btn.dataset.val;
}
</script>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
