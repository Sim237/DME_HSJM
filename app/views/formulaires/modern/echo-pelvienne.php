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
/* ===== DESIGN SYSTEM ===== */
body { background: #f0f4f8; }

.modern-form-wrapper {
    max-width: 900px;
    margin: 0 auto;
    padding: 20px 16px 80px;
}

/* Sticky action bar */
.form-topbar {
    position: sticky;
    top: 0;
    z-index: 100;
    background: white;
    border-bottom: 1px solid #e2e8f0;
    padding: 12px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 2px 8px rgba(0,0,0,.08);
}

/* Patient banner */
.patient-banner {
    background: linear-gradient(135deg, #9333ea, #c026d3);
    border-radius: 16px;
    padding: 20px 24px;
    color: white;
    margin-bottom: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.patient-banner .patient-name {
    font-size: 1.25rem;
    font-weight: 800;
    letter-spacing: .3px;
}
.patient-banner .patient-meta {
    font-size: .85rem;
    opacity: .88;
    margin-top: 4px;
}
.patient-banner .banner-right {
    text-align: right;
    font-size: .82rem;
    opacity: .9;
}
.patient-banner .banner-right span {
    display: block;
    font-weight: 700;
    font-size: 1rem;
    margin-top: 2px;
    opacity: 1;
}

/* Section cards */
.form-section {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,.06);
    margin-bottom: 16px;
    overflow: hidden;
}
.form-section-header {
    background: #faf5ff;
    padding: 12px 20px;
    font-weight: 700;
    font-size: .9rem;
    color: #9333ea;
    display: flex;
    align-items: center;
    gap: 8px;
    text-transform: uppercase;
    letter-spacing: .5px;
    border-bottom: 2px solid #9333ea;
}
.form-section-body { padding: 20px; }

/* Override Bootstrap form-control */
.form-control, .form-select {
    border-radius: 8px;
    border: 1.5px solid #e2e8f0;
    padding: 10px 12px;
    font-size: .93rem;
    transition: border-color .2s;
}
.form-control:focus, .form-select:focus {
    border-color: #9333ea;
    box-shadow: 0 0 0 3px rgba(147,51,234,.15);
}
.form-label {
    font-weight: 600;
    font-size: .82rem;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: .4px;
    margin-bottom: 6px;
}

/* Input with unit */
.input-unit-group {
    display: flex;
    align-items: center;
}
.input-unit-group .form-control {
    border-radius: 8px 0 0 8px;
}
.input-unit-badge {
    padding: 10px 14px;
    background: #faf5ff;
    border: 1.5px solid #e2e8f0;
    border-left: none;
    border-radius: 0 8px 8px 0;
    font-size: .85rem;
    font-weight: 700;
    color: #9333ea;
    white-space: nowrap;
}

/* OUI/NON toggle */
.yn-group {
    display: flex;
    gap: 8px;
}
.yn-btn {
    flex: 1;
    padding: 10px;
    border-radius: 8px;
    border: 2px solid #e2e8f0;
    background: white;
    font-weight: 700;
    font-size: .85rem;
    cursor: pointer;
    transition: .2s;
    text-align: center;
}
.yn-btn.oui.active {
    background: #d1fae5;
    border-color: #059669;
    color: #065f46;
}
.yn-btn.non.active {
    background: #fee2e2;
    border-color: #dc2626;
    color: #991b1b;
}
.yn-btn:hover:not(.active) {
    background: #f8fafc;
    border-color: #cbd5e1;
}

/* Ovaire sub-grid */
.ovaire-card {
    background: #faf5ff;
    border: 1.5px solid #e9d5ff;
    border-radius: 10px;
    padding: 16px;
}
.ovaire-card-title {
    font-size: .8rem;
    font-weight: 800;
    color: #9333ea;
    text-transform: uppercase;
    letter-spacing: .5px;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 6px;
}

/* Topbar buttons */
.btn-topbar-back {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    border-radius: 8px;
    border: 1.5px solid #e2e8f0;
    background: white;
    color: #475569;
    font-weight: 600;
    font-size: .88rem;
    text-decoration: none;
    transition: .2s;
}
.btn-topbar-back:hover { background: #f8fafc; color: #1e293b; }
.btn-topbar-print {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    border-radius: 8px;
    border: none;
    background: #f1f5f9;
    color: #475569;
    font-weight: 600;
    font-size: .88rem;
    cursor: pointer;
    transition: .2s;
}
.btn-topbar-print:hover { background: #e2e8f0; }
.btn-topbar-save {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 20px;
    border-radius: 8px;
    border: none;
    background: #9333ea;
    color: white;
    font-weight: 700;
    font-size: .88rem;
    cursor: pointer;
    transition: .2s;
}
.btn-topbar-save:hover { background: #7e22ce; }

.topbar-title {
    font-weight: 800;
    font-size: 1rem;
    color: #1e293b;
    display: flex;
    align-items: center;
    gap: 8px;
}
.topbar-title i { color: #9333ea; font-size: 1.2rem; }

@media print {
    .form-topbar { display: none !important; }
    body { background: white !important; }
    .modern-form-wrapper { padding: 0; }
    .form-section { box-shadow: none; border: 1px solid #e2e8f0; }
    .patient-banner { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .ovaire-card { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
}
</style>

<!-- STICKY TOPBAR -->
<div class="form-topbar no-print">
    <a href="<?= BASE_URL ?>patients/dossier/<?= $patient['id'] ?>" class="btn-topbar-back">
        <i class="bi bi-arrow-left"></i> Retour au dossier
    </a>
    <div class="topbar-title">
        <i class="bi bi-activity"></i>
        Échographie Pelvienne
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn-topbar-print" onclick="window.print()">
            <i class="bi bi-printer"></i> Imprimer
        </button>
        <button type="submit" form="formEchoPelv" class="btn-topbar-save">
            <i class="bi bi-save2"></i> Enregistrer
        </button>
    </div>
</div>

<!-- MAIN WRAPPER -->
<div class="modern-form-wrapper">

    <!-- PATIENT BANNER -->
    <div class="patient-banner">
        <div>
            <div class="patient-name">
                <i class="bi bi-person-circle me-2"></i>
                <?= htmlspecialchars($patient['nom']) ?> <?= htmlspecialchars($patient['prenom']) ?>
            </div>
            <div class="patient-meta">
                <i class="bi bi-calendar3 me-1"></i><?= $age ?> ans &nbsp;|&nbsp;
                <i class="bi bi-gender-ambiguous me-1"></i><?= htmlspecialchars($patient['sexe'] ?? '') ?>
                <?php if (!empty($patient['telephone'])): ?>
                    &nbsp;|&nbsp; <i class="bi bi-telephone me-1"></i><?= htmlspecialchars($patient['telephone']) ?>
                <?php endif; ?>
            </div>
        </div>
        <div class="banner-right">
            <div style="opacity:.8; font-size:.78rem;">N° Dossier</div>
            <span><?= htmlspecialchars($patient['dossier_numero'] ?? '—') ?></span>
            <div style="opacity:.8; font-size:.78rem; margin-top:8px;">Date</div>
            <span><?= date('d/m/Y') ?></span>
        </div>
    </div>

    <!-- FORM -->
    <form id="formEchoPelv" action="<?= BASE_URL ?>formulaire/sauvegarder/echo-pelvienne" method="POST">
        <input type="hidden" name="patient_id" value="<?= $patient['id'] ?>">
        <?php if ($from_suivi): ?>
            <input type="hidden" name="from_suivi" value="1">
        <?php endif; ?>
        <?php if (!empty($hosp_id)): ?>
            <input type="hidden" name="hosp_id" value="<?= (int)$hosp_id ?>">
        <?php endif; ?>

        <!-- SECTION 1 : Renseignements cliniques -->
        <div class="form-section">
            <div class="form-section-header">
                <i class="bi bi-clipboard2-pulse"></i>
                Renseignements cliniques
            </div>
            <div class="form-section-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Date d'examen</label>
                        <input type="date" name="date_examen" class="form-control" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Indication</label>
                        <input type="text" name="indication" class="form-control" placeholder="Motif de l'examen…">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Médecin traitant</label>
                        <input type="text" name="medecin" class="form-control" value="<?= htmlspecialchars($medecin_nom) ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- SECTION 2 : Utérus -->
        <div class="form-section">
            <div class="form-section-header">
                <i class="bi bi-app"></i>
                Utérus
            </div>
            <div class="form-section-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Position</label>
                        <select name="uterus_position" class="form-select">
                            <option value="" disabled selected>— Sélectionner —</option>
                            <option value="Antéversé">Antéversé</option>
                            <option value="Rétroversé">Rétroversé</option>
                            <option value="Médian">Médian</option>
                            <option value="Latéro-dévié">Latéro-dévié</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Dimensions (L × l × ep)</label>
                        <input type="text" name="uterus_dim" class="form-control" placeholder="ex : 7 x 5 x 4 cm">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Épaisseur de l'endomètre</label>
                        <div class="input-unit-group">
                            <input type="text" name="endometre" class="form-control" placeholder="ex : 8">
                            <span class="input-unit-badge">mm</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Myomètre</label>
                        <input type="text" name="myometre" class="form-control" placeholder="Homogène / Hétérogène…">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Col utérin</label>
                        <input type="text" name="col" class="form-control" placeholder="Aspect, longueur…">
                    </div>
                </div>
            </div>
        </div>

        <!-- SECTION 3 : Ovaires -->
        <div class="form-section">
            <div class="form-section-header">
                <i class="bi bi-circle-half"></i>
                Ovaires
            </div>
            <div class="form-section-body">
                <div class="row g-3">
                    <!-- Ovaire droit -->
                    <div class="col-md-6">
                        <div class="ovaire-card">
                            <div class="ovaire-card-title">
                                <i class="bi bi-arrow-right-circle-fill"></i>
                                Ovaire droit
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Dimensions</label>
                                <input type="text" name="ovaire_droit" class="form-control" placeholder="ex : 3 x 2 x 2 cm">
                            </div>
                            <div>
                                <label class="form-label">Aspect</label>
                                <input type="text" name="ovaire_droit_aspect" class="form-control" placeholder="Normal / Kystique…">
                            </div>
                        </div>
                    </div>
                    <!-- Ovaire gauche -->
                    <div class="col-md-6">
                        <div class="ovaire-card">
                            <div class="ovaire-card-title">
                                <i class="bi bi-arrow-left-circle-fill"></i>
                                Ovaire gauche
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Dimensions</label>
                                <input type="text" name="ovaire_gauche" class="form-control" placeholder="ex : 3 x 2 x 2 cm">
                            </div>
                            <div>
                                <label class="form-label">Aspect</label>
                                <input type="text" name="ovaire_gauche_aspect" class="form-control" placeholder="Normal / Kystique…">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- SECTION 4 : Autres constatations -->
        <div class="form-section">
            <div class="form-section-header">
                <i class="bi bi-search"></i>
                Autres constatations
            </div>
            <div class="form-section-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Épanchement</label>
                        <!-- Hidden input that holds the actual value -->
                        <input type="hidden" name="epanchement" id="epanchementVal" value="N">
                        <div class="yn-group" id="epanchementGroup">
                            <button type="button" class="yn-btn oui" id="btnOui"
                                onclick="setYN('O')">
                                <i class="bi bi-check-lg me-1"></i> OUI
                            </button>
                            <button type="button" class="yn-btn non active" id="btnNon"
                                onclick="setYN('N')">
                                <i class="bi bi-x-lg me-1"></i> NON
                            </button>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Anomalies</label>
                        <textarea name="anomalies" class="form-control" rows="3" placeholder="Décrire toute anomalie constatée…"></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- SECTION 5 : Conclusion -->
        <div class="form-section">
            <div class="form-section-header">
                <i class="bi bi-check-square"></i>
                Conclusion
            </div>
            <div class="form-section-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Conclusion</label>
                        <textarea name="conclusion" class="form-control" rows="5" placeholder="Conclusion de l'examen…"></textarea>
                    </div>
                </div>
            </div>
        </div>

    </form>
</div><!-- /.modern-form-wrapper -->

<script>
/**
 * OUI / NON toggle for the Épanchement field.
 * Sets the hidden input value and toggles visual state on both buttons.
 */
function setYN(val) {
    document.getElementById('epanchementVal').value = val;
    var btnOui = document.getElementById('btnOui');
    var btnNon = document.getElementById('btnNon');
    if (val === 'O') {
        btnOui.classList.add('active');
        btnNon.classList.remove('active');
    } else {
        btnNon.classList.add('active');
        btnOui.classList.remove('active');
    }
}
</script>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
