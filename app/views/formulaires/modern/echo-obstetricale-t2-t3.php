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
    background: linear-gradient(135deg, #0891b2, #06b6d4);
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
    background: #ecfeff;
    padding: 12px 20px;
    font-weight: 700;
    font-size: .9rem;
    color: #0891b2;
    display: flex;
    align-items: center;
    gap: 8px;
    text-transform: uppercase;
    letter-spacing: .5px;
    border-bottom: 2px solid #0891b2;
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
    border-color: #0891b2;
    box-shadow: 0 0 0 3px rgba(8,145,178,.15);
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
    color: #0891b2;
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

/* Input with unit */
.input-unit-group {
    display: flex;
    align-items: center;
    gap: 0;
}
.input-unit-group .form-control {
    border-radius: 8px 0 0 8px;
}
.input-unit-badge {
    padding: 10px 14px;
    background: #ecfeff;
    border: 1.5px solid #e2e8f0;
    border-left: none;
    border-radius: 0 8px 8px 0;
    font-size: .85rem;
    font-weight: 700;
    color: #0891b2;
    white-space: nowrap;
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
    background: #0891b2;
    color: white;
    font-weight: 700;
    font-size: .88rem;
    cursor: pointer;
    transition: .2s;
}
.btn-topbar-save:hover { background: #0e7490; }

.topbar-title {
    font-weight: 800;
    font-size: 1rem;
    color: #1e293b;
    display: flex;
    align-items: center;
    gap: 8px;
}
.topbar-title i { color: #0891b2; font-size: 1.2rem; }

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
        Échographie Obstétricale — 2<sup>e</sup>/3<sup>e</sup> Trimestre
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn-topbar-print" onclick="window.print()">
            <i class="bi bi-printer"></i> Imprimer
        </button>
        <button type="submit" form="formEchoT2" class="btn-topbar-save">
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
    <form id="formEchoT2" action="<?= BASE_URL ?>formulaire/sauvegarder/echo-obstetricale-t2-t3" method="POST">
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
                        <label class="form-label">DDR — Date des dernières règles</label>
                        <input type="date" name="ddr" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">DPA — Date probable d'accouchement</label>
                        <input type="date" name="dpa" class="form-control">
                    </div>
                    <div class="col-12">
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

        <!-- SECTION 2 : Fœtus -->
        <div class="form-section">
            <div class="form-section-header">
                <i class="bi bi-person-hearts"></i>
                Fœtus
            </div>
            <div class="form-section-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nombre</label>
                        <input type="text" name="f_nombre" class="form-control" placeholder="ex : Unique, Gémellaire…">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Présentation</label>
                        <input type="text" name="f_pres" class="form-control" placeholder="ex : Céphalique, Siège…">
                    </div>

                    <!-- Vitalité -->
                    <div class="col-12 mt-1">
                        <div class="form-label mb-2" style="color:#0891b2; border-bottom:1px solid #ecfeff; padding-bottom:4px;">Vitalité</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">MAF (Mouvements actifs)</label>
                        <input type="text" name="v_maf" class="form-control" placeholder="Présents / Absents">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">BDCF (Battements)</label>
                        <input type="text" name="v_bdcf" class="form-control" placeholder="bpm">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Tonus fœtal</label>
                        <input type="text" name="v_tonus" class="form-control" placeholder="Normal / Diminué">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Activité cardiaque</label>
                        <input type="text" name="v_cardio" class="form-control" placeholder="Description…">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Respiration</label>
                        <input type="text" name="v_resp" class="form-control" placeholder="Description…">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Déglutition</label>
                        <input type="text" name="v_deglu" class="form-control" placeholder="Description…">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Score de Manning</label>
                        <input type="text" name="v_manning" class="form-control" placeholder="/10">
                    </div>
                </div>
            </div>
        </div>

        <!-- SECTION 3 : Biométrie -->
        <div class="form-section">
            <div class="form-section-header">
                <i class="bi bi-rulers"></i>
                Biométrie
            </div>
            <div class="form-section-body">
                <div class="row g-3">
                    <div class="col-4">
                        <div class="measure-box">
                            <div class="measure-label">BIP</div>
                            <input type="text" name="b_bip" class="measure-input" placeholder="—">
                            <div class="measure-unit">mm</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="measure-box">
                            <div class="measure-label">LF</div>
                            <input type="text" name="b_lf" class="measure-input" placeholder="—">
                            <div class="measure-unit">mm</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="measure-box">
                            <div class="measure-label">CA</div>
                            <input type="text" name="b_ca" class="measure-input" placeholder="—">
                            <div class="measure-unit">mm</div>
                        </div>
                    </div>
                    <div class="col-12 mt-2">
                        <label class="form-label">Estimation du poids du fœtus</label>
                        <div class="input-unit-group">
                            <input type="text" name="est_poids" class="form-control" placeholder="ex : 2 450">
                            <span class="input-unit-badge">grammes</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- SECTION 4 : Morphologie -->
        <div class="form-section">
            <div class="form-section-header">
                <i class="bi bi-body-text"></i>
                Morphologie
            </div>
            <div class="form-section-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">SNC (Système nerveux central)</label>
                        <input type="text" name="m_snc" class="form-control" placeholder="Normal / Anomalie…">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Colonne vertébrale</label>
                        <input type="text" name="m_colonne" class="form-control" placeholder="Normal / Anomalie…">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Cœur — 4 cavités</label>
                        <input type="text" name="m_coeur" class="form-control" placeholder="Normal / Anomalie…">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Vessie</label>
                        <input type="text" name="m_vessie" class="form-control" placeholder="Normal / Anomalie…">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Thorax</label>
                        <input type="text" name="m_thorax" class="form-control" placeholder="Normal / Anomalie…">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Abdomen</label>
                        <input type="text" name="m_abdomen" class="form-control" placeholder="Normal / Anomalie…">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Reins</label>
                        <input type="text" name="m_reins" class="form-control" placeholder="Normal / Anomalie…">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">4 membres</label>
                        <input type="text" name="m_membres" class="form-control" placeholder="Normal / Anomalie…">
                    </div>
                </div>
            </div>
        </div>

        <!-- SECTION 5 : Liquide amniotique & Placenta -->
        <div class="form-section">
            <div class="form-section-header">
                <i class="bi bi-water"></i>
                Liquide amniotique &amp; Placenta
            </div>
            <div class="form-section-body">
                <div class="row g-3">
                    <div class="col-12 mb-1">
                        <div class="form-label" style="color:#0891b2;">Liquide amniotique</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">GCV (Grande citerne verticale)</label>
                        <input type="text" name="liq_gcv" class="form-control" placeholder="cm">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Index de Phelan</label>
                        <input type="text" name="liq_phelam" class="form-control" placeholder="cm">
                    </div>

                    <div class="col-12 mt-2 mb-1">
                        <div class="form-label" style="color:#0891b2;">Placenta</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Insertion</label>
                        <input type="text" name="plac_ins" class="form-control" placeholder="Fundique, Antérieure…">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Rapport avec le col</label>
                        <input type="text" name="plac_col" class="form-control" placeholder="Description…">
                    </div>

                    <div class="col-12 mt-2 mb-1">
                        <div class="form-label" style="color:#0891b2;">Cordon</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">État</label>
                        <input type="text" name="cord_etat" class="form-control" placeholder="Normal / Anomalie…">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Nombre de vaisseaux</label>
                        <input type="text" name="cord_vaisseaux" class="form-control" placeholder="2 artères + 1 veine…">
                    </div>
                </div>
            </div>
        </div>

        <!-- SECTION 6 : Doppler -->
        <div class="form-section">
            <div class="form-section-header">
                <i class="bi bi-broadcast"></i>
                Doppler
            </div>
            <div class="form-section-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Artère utérine</label>
                        <input type="text" name="dop_ut" class="form-control" placeholder="IP, IR, normale / anomalie…">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Artère ombilicale</label>
                        <input type="text" name="dop_omb" class="form-control" placeholder="IP, IR, normale / anomalie…">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Artère cérébrale moyenne</label>
                        <input type="text" name="dop_cer" class="form-control" placeholder="IP, IR, normale / anomalie…">
                    </div>
                </div>
            </div>
        </div>

        <!-- SECTION 7 : Utérus -->
        <div class="form-section">
            <div class="form-section-header">
                <i class="bi bi-app"></i>
                Utérus
            </div>
            <div class="form-section-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Corps utérin</label>
                        <input type="text" name="ut_corps" class="form-control" placeholder="Description…">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Endomètre</label>
                        <input type="text" name="ut_endo" class="form-control" placeholder="épaisseur, aspect…">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Aspect du col</label>
                        <input type="text" name="ut_col" class="form-control" placeholder="Fermé, ouvert, longueur cervicale…">
                    </div>
                </div>
            </div>
        </div>

        <!-- SECTION 8 : Conclusion -->
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

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
