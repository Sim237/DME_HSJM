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
body { background: #f0f4f8; font-family: 'Inter', sans-serif; }
.mf-wrap { max-width: 900px; margin: 0 auto; padding: 20px 16px 100px; }

.mf-topbar {
  position: sticky; top: 0; z-index: 200;
  background: white; border-bottom: 1px solid #e2e8f0;
  padding: 10px 20px; display: flex; justify-content: space-between;
  align-items: center; box-shadow: 0 2px 8px rgba(0,0,0,.07);
}
.topbar-title { font-weight: 800; font-size: 1rem; color: #1e293b; display: flex; align-items: center; gap: 8px; }
.topbar-title i { color: #8b5cf6; font-size: 1.2rem; }

.btn-mf-back {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 8px 16px; border-radius: 8px; border: 1.5px solid #e2e8f0;
  background: white; color: #475569; font-weight: 600; font-size: .88rem;
  text-decoration: none; transition: .2s;
}
.btn-mf-back:hover { background: #f8fafc; color: #1e293b; }
.btn-mf-print {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 8px 16px; border-radius: 8px; border: none;
  background: #f1f5f9; color: #475569; font-weight: 600; font-size: .88rem;
  cursor: pointer; transition: .2s;
}
.btn-mf-print:hover { background: #e2e8f0; }
.btn-mf-save {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 8px 20px; border-radius: 8px; border: none;
  background: #8b5cf6; color: white; font-weight: 700; font-size: .88rem;
  cursor: pointer; transition: .2s;
}
.btn-mf-save:hover { background: #7c3aed; }

/* Patient banner */
.mf-patient-banner {
  border-radius: 16px; padding: 20px 24px; color: white; margin-bottom: 20px;
  background: linear-gradient(135deg, #7c3aed, #8b5cf6);
  box-shadow: 0 4px 20px rgba(139,92,246,.30);
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

/* Sections */
.mf-section {
  background: white; border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0,0,0,.06); margin-bottom: 16px; overflow: hidden;
}
.mf-section-head {
  padding: 12px 20px; font-weight: 700; font-size: .85rem;
  text-transform: uppercase; letter-spacing: .5px;
  display: flex; align-items: center; gap: 8px;
  background: #f5f3ff; color: #7c3aed; border-bottom: 2px solid #ddd6fe;
}
.mf-section-body { padding: 20px; }

.form-label { font-weight: 600; font-size: .8rem; color: #64748b; text-transform: uppercase; letter-spacing: .4px; margin-bottom: 5px; }
.form-control, .form-select {
  border: 1.5px solid #e2e8f0; border-radius: 8px;
  font-size: .93rem; padding: 10px 12px; transition: .2s;
}
.form-control:focus, .form-select:focus {
  border-color: #8b5cf6; box-shadow: 0 0 0 3px rgba(139,92,246,.18); outline: none;
}
.form-control[readonly] { background: #f8fafc; color: #64748b; }

/* Pre-printed text block */
.preprint-block {
  background: #fafaf9;
  border: 1px solid #d4d4c8;
  border-radius: 8px;
  padding: 20px 24px;
  font-family: "Georgia", "Times New Roman", serif;
  font-size: .95rem;
  line-height: 1.8;
  color: #1a1a1a;
  white-space: pre-wrap;
  user-select: none;
  pointer-events: none;
}
.preprint-block .preprint-label {
  font-family: 'Inter', sans-serif;
  font-size: .72rem; font-weight: 700; color: #8b5cf6;
  text-transform: uppercase; letter-spacing: .6px;
  margin-bottom: 14px; display: block;
}

/* Consent accept box */
.accept-card {
  border: 2px solid #e2e8f0; border-radius: 12px;
  padding: 16px 20px; cursor: pointer; transition: .2s;
  display: flex; align-items: center; gap: 14px;
}
.accept-card:hover { border-color: #8b5cf6; background: #f5f3ff; }
.accept-card.accepted { border-color: #059669; background: #f0fdf4; }
.accept-card input[type=checkbox] { display: none; }
.accept-check-icon {
  width: 28px; height: 28px; min-width: 28px; border-radius: 50%;
  border: 2px solid #cbd5e1; display: flex; align-items: center;
  justify-content: center; transition: .2s; font-size: .9rem;
}
.accept-card.accepted .accept-check-icon { background: #059669; border-color: #059669; color: white; }
.accept-card-text { font-size: .95rem; color: #334155; font-weight: 600; line-height: 1.5; }

/* Signature placeholder */
.sig-placeholder {
  height: 80px; border: 1.5px dashed #cbd5e1;
  border-radius: 8px; background: #f8fafc;
  display: flex; align-items: center; justify-content: center;
  color: #94a3b8; font-size: .82rem; font-style: italic;
}

@media print {
  .mf-topbar { display: none !important; }
  body { background: white !important; }
  .mf-wrap { padding: 0; }
  .mf-section { box-shadow: none; border: 1px solid #e2e8f0; }
  .mf-patient-banner { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
}
</style>

<!-- STICKY TOPBAR -->
<div class="mf-topbar">
  <a href="<?= BASE_URL ?>patients/dossier/<?= $patient['id'] ?>" class="btn-mf-back">
    <i class="bi bi-arrow-left"></i> Retour au dossier
  </a>
  <div class="topbar-title">
    <i class="bi bi-file-medical"></i>
    Analgésie Péridurale — Consentement éclairé
  </div>
  <div class="d-flex gap-2">
    <button type="button" class="btn-mf-print" onclick="window.print()">
      <i class="bi bi-printer"></i> Imprimer
    </button>
    <button type="submit" form="formPeri" class="btn-mf-save">
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
        <span class="badge-item"><i class="bi bi-gender-<?= $patient['sexe'] === 'M' ? 'male' : 'female' ?>"></i>
          <?= $patient['sexe'] === 'M' ? 'Masculin' : 'Féminin' ?>
        </span>
      </div>
    </div>
  </div>

  <form id="formPeri" action="<?= BASE_URL ?>formulaire/sauvegarder/consentement-peridurale" method="POST" novalidate>
    <input type="hidden" name="patient_id" value="<?= $patient['id'] ?>">
    <input type="hidden" name="hosp_id" value="<?= $hosp_id ?>">

    <!-- SECTION 1 : Texte pré-imprimé -->
    <div class="mf-section">
      <div class="mf-section-head">
        <i class="bi bi-file-text"></i>
        Informations sur la péridurale (texte officiel)
      </div>
      <div class="mf-section-body">
        <div class="preprint-block">
<span class="preprint-label">Texte pré-imprimé — Lecture seule</span>Madame,

Lors de l'accouchement, il est devenu habituel de pouvoir bénéficier d'une technique qui permet d'accoucher sans douleur. Cette technique, que l'on appelle péridurale, agit localement par l'administration d'anesthésiques, dont le dosage est adapté tout au long de l'accouchement.

Nous tenons néanmoins à vous informer des risques et inconvénients qui peuvent survenir lors d'une anesthésie péridurale :
  • Des maux de tête passagers ;
  • Des maux de dos passagers et/ou des douleurs dans une jambe, des troubles de la sensibilité ou de la force des jambes s'améliorant rapidement ou persistant à long terme ;
  • La survenue d'une collection de sang dans le dos ;
  • Une infection pouvant évoluer vers une méningite ou une paraplégie (paralysie des membres inférieurs).

Ces problèmes très rares peuvent survenir même lorsque toutes les précautions ont été prises.

Merci d'avoir choisi l'Hôpital Saint Jean de Malte pour réaliser votre accouchement. Nous nous engageons à tout mettre en œuvre pour vous assurer la sécurité médicale de cet événement.

Suite à l'entretien d'information que j'ai eu et aux réponses qui ont été apportées à mes questions, j'accepte, après réflexion,</div>
      </div>
    </div>

    <!-- SECTION 2 : Identité de la patiente -->
    <div class="mf-section">
      <div class="mf-section-head">
        <i class="bi bi-person"></i>
        Identité de la patiente
      </div>
      <div class="mf-section-body">
        <div class="row g-3">
          <div class="col-md-8">
            <label class="form-label">Nom et prénom de la patiente</label>
            <input type="text" class="form-control" name="patient_nom"
              value="<?= htmlspecialchars($patient['nom'] . ' ' . $patient['prenom']) ?>"
              readonly>
          </div>
          <div class="col-md-4">
            <label class="form-label">Date de signature</label>
            <input type="date" class="form-control" name="date_signature" value="<?= date('Y-m-d') ?>">
          </div>
          <div class="col-md-8">
            <label class="form-label">À Njombé le… (lieu et date)</label>
            <input type="text" class="form-control" name="lieu_date"
              placeholder="Ex : Njombé, le 16 mai 2026"
              value="<?= htmlspecialchars($formData['lieu_date'] ?? '') ?>">
          </div>
        </div>
      </div>
    </div>

    <!-- SECTION 3 : Acceptation et signatures -->
    <div class="mf-section">
      <div class="mf-section-head">
        <i class="bi bi-pen"></i>
        Acceptation &amp; Signatures
      </div>
      <div class="mf-section-body">

        <!-- Checkbox acceptation -->
        <p class="text-muted small mb-3">La patiente confirme son accord libre et éclairé :</p>
        <label class="accept-card<?= !empty($formData['accepte']) ? ' accepted' : '' ?>" id="acceptCard">
          <input type="checkbox" name="accepte" value="1" id="cbAccepte"
            <?= !empty($formData['accepte']) ? 'checked' : '' ?>>
          <div class="accept-check-icon" id="acceptIcon">
            <i class="bi bi-check"></i>
          </div>
          <span class="accept-card-text">J'accepte de recevoir la péridurale</span>
        </label>

        <!-- Médecin responsable -->
        <div class="mb-4">
          <label class="form-label">Médecin responsable</label>
          <?php
            $__ms_field    = 'medecin_peridurale';
            $__ms_label    = 'Médecin / Anesthésiste';
            $__ms_value    = $formData['medecin_peridurale'] ?? $medecin_nom;
            $__ms_id_field = 'medecin_id';
            include __DIR__ . '/_medecin_select.php';
          ?>
        </div>

        <!-- Grille signatures -->
        <div class="row g-4 mt-2">
          <div class="col-md-6">
            <?php
              $__sp_field    = 'signature_medecin';
              $__sp_label    = 'Signature du médecin';
              $__sp_subname  = $formData['medecin_peridurale'] ?? $medecin_nom;
              $__sp_existing = $formData['signature_medecin'] ?? '';
              $__sp_color    = '#7c3aed';
              include __DIR__ . '/_signature_pad.php';
            ?>
          </div>
          <div class="col-md-6">
            <?php
              $__sp_field    = 'signature_patiente';
              $__sp_label    = 'Signature de la patiente';
              $__sp_subname  = $patient['nom'] . ' ' . $patient['prenom'];
              $__sp_existing = $formData['signature_patiente'] ?? '';
              $__sp_color    = '#8b5cf6';
              include __DIR__ . '/_signature_pad.php';
            ?>
          </div>
        </div>

      </div>
    </div>

  </form>
</div>

<script>
(function() {
  var card  = document.getElementById('acceptCard');
  var cb    = document.getElementById('cbAccepte');
  var icon  = document.getElementById('acceptIcon');

  function syncCard() {
    if (cb.checked) {
      card.classList.add('accepted');
      icon.innerHTML = '<i class="bi bi-check"></i>';
    } else {
      card.classList.remove('accepted');
      icon.innerHTML = '';
    }
  }

  card.addEventListener('click', function() {
    // Let the browser toggle cb first, then sync
    setTimeout(syncCard, 0);
  });

  // Initial sync (in case formData rehydrates the checked state)
  syncCard();
})();
</script>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
