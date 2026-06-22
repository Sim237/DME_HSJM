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
.mf-wrap { max-width: 900px; margin: 0 auto; padding: 0 16px 100px; }

/* Sticky topbar */
.mf-topbar {
    position: sticky; top: 0; z-index: 200;
    background: white; border-bottom: 1px solid #e2e8f0;
    padding: 10px 20px; display: flex; justify-content: space-between;
    align-items: center; box-shadow: 0 2px 8px rgba(0,0,0,.07);
}
.mf-topbar .mf-title {
    font-weight: 800; font-size: .97rem; color: #d97706;
    display: flex; align-items: center; gap: 8px;
}

/* Patient banner */
.mf-patient-banner {
    border-radius: 16px; padding: 20px 24px; color: white; margin: 20px 0;
    background: linear-gradient(135deg, #b45309, #f59e0b);
    box-shadow: 0 4px 20px rgba(245,158,11,.28);
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
    padding: 3px 13px; font-size: .82rem; font-weight: 600;
}

/* Section cards */
.mf-section {
    background: white; border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,.06);
    margin-bottom: 16px; overflow: hidden;
}
.mf-section-head {
    padding: 12px 20px; font-weight: 700; font-size: .85rem;
    text-transform: uppercase; letter-spacing: .5px;
    display: flex; align-items: center; gap: 8px;
    background: #fffbeb; color: #92400e;
    border-bottom: 2px solid #f59e0b;
}
.mf-section-body { padding: 20px; }

/* Form controls */
.form-label {
    font-weight: 600; font-size: .8rem; color: #64748b;
    text-transform: uppercase; letter-spacing: .4px; margin-bottom: 5px;
    display: block;
}
.form-control, .form-select {
    border: 1.5px solid #e2e8f0; border-radius: 8px;
    font-size: .93rem; padding: 10px 12px; transition: .2s;
    width: 100%;
}
.form-control:focus, .form-select:focus {
    border-color: #f59e0b;
    box-shadow: 0 0 0 3px rgba(245,158,11,.18);
    outline: none;
}

/* Radio pill buttons */
.pill-group { display: flex; gap: 8px; flex-wrap: wrap; }
.pill-radio { position: relative; }
.pill-radio input[type=radio] { position: absolute; opacity: 0; width: 0; height: 0; }
.pill-radio label {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 8px 16px; border-radius: 20px; cursor: pointer;
    font-weight: 600; font-size: .88rem;
    border: 2px solid #e2e8f0; background: white; color: #64748b;
    transition: .2s; user-select: none;
}
.pill-radio input:checked + label {
    border-color: #f59e0b; background: #fef3c7; color: #92400e;
}

/* Positif / Négatif coloring */
.pill-radio input[value="positif"]:checked + label {
    border-color: #dc2626; background: #fee2e2; color: #991b1b;
}
.pill-radio input[value="negatif"]:checked + label {
    border-color: #16a34a; background: #dcfce7; color: #15803d;
}

/* Accouchement radios */
.accouche-group { display: flex; gap: 12px; flex-wrap: wrap; }
.accouche-card {
    flex: 1; min-width: 140px;
    border: 2px solid #e2e8f0; border-radius: 12px;
    padding: 14px 16px; cursor: pointer; transition: all .18s;
    display: flex; align-items: center; gap: 12px;
    background: #f8fafc;
}
.accouche-card:hover { border-color: #fcd34d; background: #fffbeb; }
.accouche-card input[type="radio"] { display: none; }
.accouche-card.selected { border-color: #f59e0b; background: #fffbeb; box-shadow: 0 0 0 3px rgba(245,158,11,.15); }
.accouche-card .card-icon { font-size: 1.6rem; color: #d97706; flex-shrink: 0; }
.accouche-card .card-text strong { display: block; font-size: .9rem; color: #1e1b4b; font-weight: 700; }
.accouche-card .card-text span { font-size: .78rem; color: #64748b; }

/* Buttons */
.btn-back {
    display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px;
    border-radius: 8px; border: 1.5px solid #e2e8f0; background: white;
    color: #475569; font-weight: 600; font-size: .88rem; text-decoration: none; transition: .2s;
}
.btn-back:hover { background: #f8fafc; color: #1e293b; }
.btn-save {
    display: inline-flex; align-items: center; gap: 6px; padding: 9px 22px;
    border-radius: 8px; border: none; background: #f59e0b; color: white;
    font-weight: 700; font-size: .9rem; cursor: pointer; transition: .2s;
}
.btn-save:hover { background: #d97706; }

@media print {
    .mf-topbar { display: none !important; }
    body { background: white !important; }
    .mf-section { box-shadow: none; border: 1px solid #e2e8f0; }
}
</style>

<!-- Topbar -->
<div class="mf-topbar">
    <div class="mf-title">
        <i class="bi bi-heart-pulse"></i>
        Info Mère, Travail &amp; Enfant
    </div>
    <div class="d-flex gap-2">
        <?php if ($from_suivi): ?>
        <a href="javascript:history.back()" class="btn-back">
            <i class="bi bi-arrow-left"></i> Retour
        </a>
        <?php endif; ?>
        <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill" onclick="window.print()">
            <i class="bi bi-printer"></i> Imprimer
        </button>
        <button type="submit" form="formMTE" class="btn-save">
            <i class="bi bi-save2-fill"></i> Enregistrer
        </button>
    </div>
</div>

<div class="mf-wrap">

    <!-- Patient Banner -->
    <div class="mf-patient-banner">
        <div class="avatar">
            <i class="bi bi-person-heart"></i>
        </div>
        <div class="info">
            <h4><?= htmlspecialchars(($patient['nom'] ?? '') . ' ' . ($patient['prenom'] ?? '')) ?></h4>
            <div class="badges">
                <span class="badge-item"><i class="bi bi-hash"></i> <?= htmlspecialchars($patient['dossier_numero'] ?? '') ?></span>
                <span class="badge-item"><i class="bi bi-calendar3"></i> <?= $age ?> ans</span>
                <span class="badge-item"><i class="bi bi-gender-female"></i> Féminin</span>
                <span class="badge-item"><i class="bi bi-calendar2-event"></i> <?= date('d/m/Y') ?></span>
            </div>
        </div>
    </div>

    <form id="formMTE" action="#" method="POST" novalidate>
        <input type="hidden" name="patient_id" value="<?= (int)($patient['id'] ?? 0) ?>">
        <?php if ($hosp_id): ?>
        <input type="hidden" name="hosp_id" value="<?= (int)$hosp_id ?>">
        <?php endif; ?>

        <!-- DIAGNOSTIC TOP -->
        <div class="mf-section">
            <div class="mf-section-head">
                <i class="bi bi-clipboard2-pulse"></i> Diagnostic
            </div>
            <div class="mf-section-body">
                <label class="form-label">Diagnostic (à l'admission)</label>
                <input type="text" name="diagnostic_top" class="form-control"
                       placeholder="Diagnostic clinique à l'entrée..."
                       value="<?= htmlspecialchars($formData['diagnostic_top'] ?? '') ?>">
            </div>
        </div>

        <!-- SECTION 1 : Informations générales -->
        <div class="mf-section">
            <div class="mf-section-head">
                <i class="bi bi-person-lines-fill"></i> Informations générales
            </div>
            <div class="mf-section-body">
                <div class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label">Nom de la mère</label>
                        <input type="text" name="mere_nom" class="form-control"
                               value="<?= htmlspecialchars($formData['mere_nom'] ?? (($patient['nom'] ?? '') . ' ' . ($patient['prenom'] ?? ''))) ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Âge</label>
                        <input type="number" name="mere_age" class="form-control"
                               min="10" max="65"
                               value="<?= htmlspecialchars((string)($formData['mere_age'] ?? $age)) ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">G (Gestité)</label>
                        <input type="number" name="gestite" class="form-control"
                               min="1" max="20" placeholder="0"
                               value="<?= htmlspecialchars($formData['gestite'] ?? '') ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">P (Parité)</label>
                        <input type="number" name="parite" class="form-control"
                               min="0" max="20" placeholder="0"
                               value="<?= htmlspecialchars($formData['parite'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Âge de grossesse (SA)</label>
                        <input type="number" name="age_grossesse" class="form-control"
                               min="20" max="45" placeholder="semaines"
                               value="<?= htmlspecialchars($formData['age_grossesse'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">BDCF (bat. cœur fœtal)</label>
                        <input type="text" name="bdcf" class="form-control"
                               placeholder="ex : 140 bpm, présents..."
                               value="<?= htmlspecialchars($formData['bdcf'] ?? '') ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- SECTION 2 : Sérologies -->
        <div class="mf-section">
            <div class="mf-section-head">
                <i class="bi bi-droplet"></i> Sérologies
            </div>
            <div class="mf-section-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">LAV (VIH)</label>
                        <div class="pill-group">
                            <div class="pill-radio">
                                <input type="radio" name="lav" id="lav_pos" value="positif"
                                    <?= ($formData['lav'] ?? '') === 'positif' ? 'checked' : '' ?>>
                                <label for="lav_pos"><i class="bi bi-circle-fill text-danger"></i> Positif</label>
                            </div>
                            <div class="pill-radio">
                                <input type="radio" name="lav" id="lav_neg" value="negatif"
                                    <?= ($formData['lav'] ?? '') === 'negatif' ? 'checked' : '' ?>>
                                <label for="lav_neg"><i class="bi bi-circle text-success"></i> Négatif</label>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">AgHBs (Hépatite B)</label>
                        <div class="pill-group">
                            <div class="pill-radio">
                                <input type="radio" name="aghbs" id="aghbs_pos" value="positif"
                                    <?= ($formData['aghbs'] ?? '') === 'positif' ? 'checked' : '' ?>>
                                <label for="aghbs_pos"><i class="bi bi-circle-fill text-danger"></i> Positif</label>
                            </div>
                            <div class="pill-radio">
                                <input type="radio" name="aghbs" id="aghbs_neg" value="negatif"
                                    <?= ($formData['aghbs'] ?? '') === 'negatif' ? 'checked' : '' ?>>
                                <label for="aghbs_neg"><i class="bi bi-circle text-success"></i> Négatif</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- SECTION 3 : État des membranes -->
        <div class="mf-section">
            <div class="mf-section-head">
                <i class="bi bi-water"></i> État des membranes &amp; Liquide amniotique
            </div>
            <div class="mf-section-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">État des membranes</label>
                        <div class="pill-group">
                            <div class="pill-radio">
                                <input type="radio" name="membranes" id="mb_intact" value="intact"
                                    <?= ($formData['membranes'] ?? '') === 'intact' ? 'checked' : '' ?>>
                                <label for="mb_intact">Intact</label>
                            </div>
                            <div class="pill-radio">
                                <input type="radio" name="membranes" id="mb_r12" value="rompu_moins12h"
                                    <?= ($formData['membranes'] ?? '') === 'rompu_moins12h' ? 'checked' : '' ?>>
                                <label for="mb_r12">Rompu &lt; 12 h</label>
                            </div>
                            <div class="pill-radio">
                                <input type="radio" name="membranes" id="mb_r6" value="rompu_moins6h"
                                    <?= ($formData['membranes'] ?? '') === 'rompu_moins6h' ? 'checked' : '' ?>>
                                <label for="mb_r6">Rompu &lt; 6 h</label>
                            </div>
                            <div class="pill-radio">
                                <input type="radio" name="membranes" id="mb_r24" value="rompu_plus24h"
                                    <?= ($formData['membranes'] ?? '') === 'rompu_plus24h' ? 'checked' : '' ?>>
                                <label for="mb_r24">Rompu &gt; 24 h</label>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">LA (Liquide amniotique)</label>
                        <div class="pill-group">
                            <div class="pill-radio">
                                <input type="radio" name="liquide_amniotique" id="la_clair" value="clair"
                                    <?= ($formData['liquide_amniotique'] ?? '') === 'clair' ? 'checked' : '' ?>>
                                <label for="la_clair">Clair</label>
                            </div>
                            <div class="pill-radio">
                                <input type="radio" name="liquide_amniotique" id="la_jau" value="jaunatre"
                                    <?= ($formData['liquide_amniotique'] ?? '') === 'jaunatre' ? 'checked' : '' ?>>
                                <label for="la_jau">Jaunâtre</label>
                            </div>
                            <div class="pill-radio">
                                <input type="radio" name="liquide_amniotique" id="la_ver" value="verdatre"
                                    <?= ($formData['liquide_amniotique'] ?? '') === 'verdatre' ? 'checked' : '' ?>>
                                <label for="la_ver">Verdâtre</label>
                            </div>
                            <div class="pill-radio">
                                <input type="radio" name="liquide_amniotique" id="la_mec" value="meconial"
                                    <?= ($formData['liquide_amniotique'] ?? '') === 'meconial' ? 'checked' : '' ?>>
                                <label for="la_mec">Méconial</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- SECTION 4 : Accouchement -->
        <div class="mf-section">
            <div class="mf-section-head">
                <i class="bi bi-hospital"></i> Mode d'accouchement
            </div>
            <div class="mf-section-body">
                <label class="form-label mb-3">Mode d'accouchement prévu / réalisé</label>
                <div class="accouche-group">
                    <label class="accouche-card <?= ($formData['accouchement'] ?? '') === 'vb' ? 'selected' : '' ?>" id="card_vb">
                        <input type="radio" name="accouchement" value="vb"
                            <?= ($formData['accouchement'] ?? '') === 'vb' ? 'checked' : '' ?>>
                        <div class="card-icon"><i class="bi bi-person-standing"></i></div>
                        <div class="card-text">
                            <strong>Voie basse (VB)</strong>
                            <span>Accouchement naturel</span>
                        </div>
                    </label>
                    <label class="accouche-card <?= ($formData['accouchement'] ?? '') === 'cs' ? 'selected' : '' ?>" id="card_cs">
                        <input type="radio" name="accouchement" value="cs"
                            <?= ($formData['accouchement'] ?? '') === 'cs' ? 'checked' : '' ?>>
                        <div class="card-icon"><i class="bi bi-scissors"></i></div>
                        <div class="card-text">
                            <strong>Césarienne (CS)</strong>
                            <span>Intervention chirurgicale</span>
                        </div>
                    </label>
                </div>
            </div>
        </div>

        <!-- DIAGNOSTIC FINAL -->
        <div class="mf-section">
            <div class="mf-section-head">
                <i class="bi bi-journal-medical"></i> Diagnostic final
            </div>
            <div class="mf-section-body">
                <label class="form-label">Diagnostic final / Conclusion</label>
                <textarea name="diagnostic_bottom" class="form-control" rows="4"
                          placeholder="Synthèse clinique, conclusions, recommandations..."><?= htmlspecialchars($formData['diagnostic_bottom'] ?? '') ?></textarea>
            </div>
        </div>

    </form>
</div>

<script>
// Accouchement card toggle
document.querySelectorAll('input[name="accouchement"]').forEach(function(radio) {
    radio.addEventListener('change', function() {
        document.querySelectorAll('.accouche-card').forEach(function(c) { c.classList.remove('selected'); });
        this.closest('.accouche-card').classList.add('selected');
    });
});
</script>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
