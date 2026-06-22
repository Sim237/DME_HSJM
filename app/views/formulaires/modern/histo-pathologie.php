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
.mf-topbar { position: sticky; top:0; z-index:200; background:white; border-bottom:1px solid #e2e8f0; padding:10px 20px; display:flex; justify-content:space-between; align-items:center; box-shadow:0 2px 8px rgba(0,0,0,.07); }
.mf-patient-banner { border-radius:16px; padding:20px 24px; color:white; margin-bottom:20px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; }
.mf-patient-banner .pat-info { font-size:.85rem; opacity:.85; }
.mf-patient-banner .pat-name { font-size:1.25rem; font-weight:800; }
.mf-patient-badge { background:rgba(255,255,255,.2); border-radius:8px; padding:6px 12px; font-size:.82rem; font-weight:600; }
.mf-section { background:white; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,.06); margin-bottom:16px; overflow:hidden; }
.mf-section-head { padding:12px 20px; font-weight:700; font-size:.85rem; text-transform:uppercase; letter-spacing:.5px; display:flex; align-items:center; gap:8px; border-bottom:2px solid; }
.mf-section-body { padding:20px; }
.form-label { font-weight:600; font-size:.8rem; color:#64748b; text-transform:uppercase; letter-spacing:.4px; margin-bottom:5px; }
.form-control, .form-select { border:1.5px solid #e2e8f0; border-radius:8px; font-size:.93rem; padding:10px 12px; transition:.2s; }
.form-control:focus, .form-select:focus { border-color:#d97706; box-shadow:0 0 0 3px rgba(217,119,6,.15); }
.info-badge { background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:10px 14px; font-size:.9rem; color:#334155; }
.readonly-field { background:#f8fafc; border:1.5px solid #e2e8f0; border-radius:8px; padding:10px 12px; color:#64748b; font-weight:600; }

/* Topbar buttons */
.btn-mf-back { display:inline-flex; align-items:center; gap:6px; padding:8px 16px; border-radius:8px; border:1.5px solid #e2e8f0; background:white; color:#475569; font-weight:600; font-size:.88rem; text-decoration:none; transition:.2s; }
.btn-mf-back:hover { background:#f8fafc; color:#1e293b; }
.btn-mf-print { display:inline-flex; align-items:center; gap:6px; padding:8px 16px; border-radius:8px; border:none; background:#f1f5f9; color:#475569; font-weight:600; font-size:.88rem; cursor:pointer; transition:.2s; }
.btn-mf-print:hover { background:#e2e8f0; }
.btn-mf-save { display:inline-flex; align-items:center; gap:6px; padding:8px 20px; border-radius:8px; border:none; background:#d97706; color:white; font-weight:700; font-size:.88rem; cursor:pointer; transition:.2s; }
.btn-mf-save:hover { background:#b45309; }
.topbar-title { font-weight:800; font-size:1rem; color:#1e293b; display:flex; align-items:center; gap:8px; }
.topbar-title i { color:#d97706; font-size:1.2rem; }

/* Exam checkbox cards */
.exam-card { border:2px solid #e2e8f0; border-radius:12px; padding:16px; cursor:pointer; transition:.2s; text-align:center; user-select:none; }
.exam-card:hover { border-color:#d97706; background:#fffbeb; }
.exam-card.selected { border-color:#d97706; background:#fffbeb; }
.exam-card .exam-icon { font-size:2rem; color:#92400e; margin-bottom:8px; display:block; }
.exam-card .exam-label { font-weight:700; font-size:.88rem; color:#1e293b; }
.exam-card input[type=checkbox] { display:none; }
.exam-card.selected .exam-icon { color:#d97706; }

@media print {
    .mf-topbar { display:none !important; }
    body { background:white !important; }
    .mf-wrap { padding:0; }
    .mf-section { box-shadow:none; border:1px solid #e2e8f0; }
    .mf-patient-banner { -webkit-print-color-adjust:exact; print-color-adjust:exact; }
}
</style>

<!-- STICKY TOPBAR -->
<div class="mf-topbar no-print">
    <a href="<?= BASE_URL ?>patients/dossier/<?= $patient['id'] ?>" class="btn-mf-back">
        <i class="bi bi-arrow-left"></i> Retour au dossier
    </a>
    <div class="topbar-title">
        <i class="bi bi-microscope"></i>
        Demande d'Histopathologie
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn-mf-print" onclick="window.print()">
            <i class="bi bi-printer"></i> Imprimer
        </button>
        <button type="submit" form="formHisto" class="btn-mf-save">
            <i class="bi bi-save2"></i> Enregistrer
        </button>
    </div>
</div>

<div class="mf-wrap">

    <!-- PATIENT BANNER -->
    <div class="mf-patient-banner" style="background:linear-gradient(135deg,#92400e,#d97706);">
        <div>
            <div class="pat-name">
                <i class="bi bi-person-circle me-2"></i>
                <?= htmlspecialchars($patient['nom']) ?> <?= htmlspecialchars($patient['prenom']) ?>
            </div>
            <div class="pat-info mt-1">
                <i class="bi bi-calendar3 me-1"></i><?= $age ?> ans
                &nbsp;|&nbsp; <i class="bi bi-gender-ambiguous me-1"></i><?= htmlspecialchars($patient['sexe'] ?? '') ?>
                <?php if (!empty($patient['telephone'])): ?>
                    &nbsp;|&nbsp; <i class="bi bi-telephone me-1"></i><?= htmlspecialchars($patient['telephone']) ?>
                <?php endif; ?>
            </div>
        </div>
        <div class="d-flex flex-column align-items-end gap-2">
            <div class="mf-patient-badge"><i class="bi bi-folder2-open me-1"></i><?= htmlspecialchars($patient['dossier_numero'] ?? '—') ?></div>
            <div class="mf-patient-badge"><i class="bi bi-calendar-check me-1"></i><?= date('d/m/Y') ?></div>
        </div>
    </div>

    <form id="formHisto" action="<?= BASE_URL ?>formulaire/sauvegarder/histo-pathologie" method="POST">
        <input type="hidden" name="patient_id" value="<?= $patient['id'] ?>">
        <?php if ($from_suivi): ?><input type="hidden" name="from_suivi" value="1"><?php endif; ?>
        <?php if (!empty($hosp_id)): ?><input type="hidden" name="hosp_id" value="<?= (int)$hosp_id ?>"><?php endif; ?>

        <!-- SECTION 1 : Praticien demandeur -->
        <div class="mf-section">
            <div class="mf-section-head" style="background:#fffbeb; color:#92400e; border-color:#d97706;">
                <i class="bi bi-person-badge"></i> Praticien demandeur
            </div>
            <div class="mf-section-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nom du praticien</label>
                        <input type="text" name="praticien_nom" class="form-control" value="<?= htmlspecialchars($medecin_nom) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Service</label>
                        <input type="text" name="service" class="form-control" placeholder="Service demandeur">
                    </div>
                </div>
            </div>
        </div>

        <!-- SECTION 2 : Renseignements sur le prélèvement -->
        <div class="mf-section">
            <div class="mf-section-head" style="background:#fffbeb; color:#92400e; border-color:#d97706;">
                <i class="bi bi-eyedropper"></i> Renseignements sur le prélèvement
            </div>
            <div class="mf-section-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nature du prélèvement <span class="text-danger">*</span></label>
                        <input type="text" name="nature_prelevement" class="form-control" required placeholder="Ex : biopsie cutanée, pièce opératoire…">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Localisation / siège</label>
                        <input type="text" name="localisation" class="form-control" placeholder="Ex : sein gauche, quadrant supéro-externe…">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Date du prélèvement</label>
                        <input type="date" name="date_prelevement" class="form-control" value="<?= date('Y-m-d') ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- SECTION 3 : Contexte clinique -->
        <div class="mf-section">
            <div class="mf-section-head" style="background:#fffbeb; color:#92400e; border-color:#d97706;">
                <i class="bi bi-clipboard2-pulse"></i> Contexte clinique
            </div>
            <div class="mf-section-body">
                <label class="form-label">Renseignements cliniques</label>
                <textarea name="contexte_clinique" class="form-control" rows="4" placeholder="Renseignements cliniques pertinents (symptômes, imagerie, traitement en cours…)"></textarea>
            </div>
        </div>

        <!-- SECTION 4 : Type d'examen demandé -->
        <div class="mf-section">
            <div class="mf-section-head" style="background:#fffbeb; color:#92400e; border-color:#d97706;">
                <i class="bi bi-microscope"></i> Type d'examen demandé
            </div>
            <div class="mf-section-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="exam-card" id="card-standard">
                            <input type="checkbox" name="examen_standard" value="1">
                            <i class="bi bi-vial exam-icon"></i>
                            <div class="exam-label">Examen standard</div>
                            <div class="text-muted mt-1" style="font-size:.78rem;">Histologie classique</div>
                        </label>
                    </div>
                    <div class="col-md-4">
                        <label class="exam-card" id="card-biopsie">
                            <input type="checkbox" name="biopsie_extemporanee" value="1">
                            <i class="bi bi-clock exam-icon"></i>
                            <div class="exam-label">Biopsie extemporanée</div>
                            <div class="text-muted mt-1" style="font-size:.78rem;">Analyse peropératoire</div>
                        </label>
                    </div>
                    <div class="col-md-4">
                        <label class="exam-card" id="card-cytoponction">
                            <input type="checkbox" name="cytoponction" value="1">
                            <i class="bi bi-needle exam-icon"></i>
                            <div class="exam-label">Cytoponction</div>
                            <div class="text-muted mt-1" style="font-size:.78rem;">Aspiration à l'aiguille fine</div>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- SECTION 5 : Antécédents -->
        <div class="mf-section">
            <div class="mf-section-head" style="background:#fffbeb; color:#92400e; border-color:#d97706;">
                <i class="bi bi-journal-medical"></i> Antécédents
            </div>
            <div class="mf-section-body">
                <label class="form-label">Antécédents pertinents</label>
                <textarea name="antecedents" class="form-control" rows="3" placeholder="Antécédents médicaux, chirurgicaux ou oncologiques pertinents…"></textarea>
            </div>
        </div>

    </form>
</div><!-- /.mf-wrap -->

<script>
// Checkbox card toggle
document.querySelectorAll('.exam-card input[type=checkbox]').forEach(cb => {
    cb.addEventListener('change', function() {
        this.closest('.exam-card').classList.toggle('selected', this.checked);
    });
});
</script>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
