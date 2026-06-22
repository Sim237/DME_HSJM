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
    background: linear-gradient(135deg, #7c3aed, #a855f7);
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
    background: #f5f3ff;
    padding: 12px 20px;
    font-weight: 700;
    font-size: .9rem;
    color: #7c3aed;
    display: flex;
    align-items: center;
    gap: 8px;
    text-transform: uppercase;
    letter-spacing: .5px;
    border-bottom: 2px solid #7c3aed;
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
    border-color: #7c3aed;
    box-shadow: 0 0 0 3px rgba(124,58,237,.15);
}
.form-label {
    font-weight: 600;
    font-size: .82rem;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: .4px;
    margin-bottom: 6px;
}

/* Measurement boxes */
.measure-box {
    background: #f8fafc;
    border: 1.5px solid #e2e8f0;
    border-radius: 10px;
    padding: 14px;
    text-align: center;
}
.measure-label {
    font-size: .75rem;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    margin-bottom: 6px;
}
.measure-input {
    font-size: 1.3rem;
    font-weight: 800;
    color: #7c3aed;
    text-align: center;
    border: none;
    background: transparent;
    width: 100%;
    outline: none;
    border-bottom: 2px solid #e2e8f0;
}
.measure-unit {
    font-size: .75rem;
    color: #94a3b8;
    margin-top: 4px;
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
    background: #7c3aed;
    color: white;
    font-weight: 700;
    font-size: .88rem;
    cursor: pointer;
    transition: .2s;
}
.btn-topbar-save:hover { background: #6d28d9; }

.topbar-title {
    font-weight: 800;
    font-size: 1rem;
    color: #1e293b;
    display: flex;
    align-items: center;
    gap: 8px;
}
.topbar-title i { color: #7c3aed; font-size: 1.2rem; }

@media print {
    .form-topbar { display: none !important; }
    body { background: white !important; }
    .modern-form-wrapper { padding: 0; }
    .form-section { box-shadow: none; border: 1px solid #e2e8f0; }
    .patient-banner { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
}
</style>

<!-- STICKY TOPBAR -->
<div class="form-topbar no-print">
    <a href="<?= BASE_URL ?>patients/dossier/<?= $patient['id'] ?>" class="btn-topbar-back">
        <i class="bi bi-arrow-left"></i> Retour au dossier
    </a>
    <div class="topbar-title">
        <i class="bi bi-activity"></i>
        Échographie Obstétricale — 1<sup>er</sup> Trimestre
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn-topbar-print" onclick="window.print()">
            <i class="bi bi-printer"></i> Imprimer
        </button>
        <button type="submit" form="formEcho" class="btn-topbar-save">
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
    <form id="formEcho" action="<?= BASE_URL ?>formulaire/sauvegarder/echo-obstetricale" method="POST">
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
                    <div class="col-12">
                        <label class="form-label">Gestité — Parité</label>
                        <input type="text" name="gestite" class="form-control" placeholder="ex : G3 P2">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Date des dernières règles (DDR)</label>
                        <input type="date" name="ddr" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Date probable d'accouchement (DPA)</label>
                        <input type="date" name="dpa" class="form-control">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Indications</label>
                        <input type="text" name="indications" class="form-control" placeholder="Motif de l'examen…">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Médecin traitant</label>
                        <input type="text" name="medecin" class="form-control" value="<?= htmlspecialchars($medecin_nom) ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- SECTION 2 : Résultats 1er Trimestre -->
        <div class="form-section">
            <div class="form-section-header">
                <i class="bi bi-search-heart"></i>
                Résultats — 1<sup>er</sup> Trimestre
            </div>
            <div class="form-section-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Sac ovulaire</label>
                        <input type="text" name="sac" class="form-control" placeholder="Description…">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Couronne trophoblastique</label>
                        <input type="text" name="couronne" class="form-control" placeholder="Description…">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Embryon</label>
                        <input type="text" name="embryon" class="form-control" placeholder="Description…">
                    </div>

                    <!-- Biométrie -->
                    <div class="col-12 mt-2">
                        <div class="form-label mb-3">Biométrie</div>
                        <div class="row g-3">
                            <div class="col-4">
                                <div class="measure-box">
                                    <div class="measure-label">LCC</div>
                                    <input type="text" name="lcc" class="measure-input" placeholder="—">
                                    <div class="measure-unit">mm</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="measure-box">
                                    <div class="measure-label">BIP</div>
                                    <input type="text" name="bip" class="measure-input" placeholder="—">
                                    <div class="measure-unit">mm</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="measure-box">
                                    <div class="measure-label">LF</div>
                                    <input type="text" name="lf" class="measure-input" placeholder="—">
                                    <div class="measure-unit">mm</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 mt-2">
                        <label class="form-label">Annexes</label>
                        <textarea name="annexes" class="form-control" rows="3" placeholder="Observations sur les annexes…"></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- SECTION 3 : Conclusion -->
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
                    <div class="col-md-6">
                        <label class="form-label">Date de la prochaine échographie</label>
                        <input type="date" name="date_prochaine_echo" class="form-control">
                    </div>
                </div>
            </div>
        </div>

    </form>
</div><!-- /.modern-form-wrapper -->

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
