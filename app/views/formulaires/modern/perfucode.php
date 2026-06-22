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
.mf-wrap { max-width: 860px; margin: 0 auto; padding: 0 16px 100px; }

/* Sticky topbar */
.mf-topbar {
    position: sticky; top: 0; z-index: 200;
    background: white; border-bottom: 1px solid #e2e8f0;
    padding: 10px 20px; display: flex; justify-content: space-between;
    align-items: center; box-shadow: 0 2px 8px rgba(0,0,0,.07);
}
.mf-topbar .mf-title {
    font-weight: 800; font-size: .97rem; color: #0ea5e9;
    display: flex; align-items: center; gap: 8px;
}
.mf-topbar .mf-actions { display: flex; gap: 8px; }

/* Patient banner */
.mf-patient-banner {
    border-radius: 16px; padding: 20px 24px; color: white; margin: 20px 0;
    background: linear-gradient(135deg, #0284c7, #0ea5e9);
    box-shadow: 0 4px 20px rgba(14,165,233,.28);
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
    background: #f0f9ff; color: #0369a1;
    border-bottom: 2px solid #0ea5e9;
}
.mf-section-body { padding: 20px; }

/* Form controls */
.form-label {
    font-weight: 600; font-size: .8rem; color: #64748b;
    text-transform: uppercase; letter-spacing: .4px; margin-bottom: 5px;
    display: block;
}
.form-control {
    border: 1.5px solid #e2e8f0; border-radius: 8px;
    font-size: .93rem; padding: 10px 12px; transition: .2s;
    width: 100%;
}
.form-control:focus {
    border-color: #0ea5e9;
    box-shadow: 0 0 0 3px rgba(14,165,233,.18);
    outline: none;
}

/* Lignes de perfusion */
.ligne-perfucode {
    display: flex; align-items: center; gap: 12px; margin-bottom: 10px;
}
.ligne-num {
    flex-shrink: 0; width: 28px; height: 28px; border-radius: 50%;
    background: #0ea5e9; color: white; font-weight: 800; font-size: .88rem;
    display: flex; align-items: center; justify-content: center;
}
.ligne-perfucode .form-control { flex: 1; }

/* Buttons */
.btn-back {
    display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px;
    border-radius: 8px; border: 1.5px solid #e2e8f0; background: white;
    color: #475569; font-weight: 600; font-size: .88rem; text-decoration: none; transition: .2s;
}
.btn-back:hover { background: #f8fafc; color: #1e293b; }
.btn-save {
    display: inline-flex; align-items: center; gap: 6px; padding: 9px 22px;
    border-radius: 8px; border: none; background: #0ea5e9; color: white;
    font-weight: 700; font-size: .9rem; cursor: pointer; transition: .2s;
}
.btn-save:hover { background: #0284c7; }

@media print {
    .mf-topbar { display: none !important; }
    body { background: white !important; }
    .mf-section { box-shadow: none; border: 1px solid #e2e8f0; }
}
</style>

<!-- Topbar -->
<div class="mf-topbar">
    <div class="mf-title">
        <i class="bi bi-droplet-half"></i>
        Perfucode — Prescription de perfusion
    </div>
    <div class="mf-actions">
        <?php if ($from_suivi): ?>
        <a href="javascript:history.back()" class="btn-back">
            <i class="bi bi-arrow-left"></i> Retour
        </a>
        <?php endif; ?>
        <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill" onclick="window.print()">
            <i class="bi bi-printer"></i> Imprimer
        </button>
        <button type="submit" form="formPerfu" class="btn-save">
            <i class="bi bi-save2-fill"></i> Enregistrer
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
            <h4><?= htmlspecialchars(($patient['nom'] ?? '') . ' ' . ($patient['prenom'] ?? '')) ?></h4>
            <div class="badges">
                <span class="badge-item"><i class="bi bi-hash"></i> <?= htmlspecialchars($patient['dossier_numero'] ?? '') ?></span>
                <?php if (!empty($patient['date_naissance'])): ?>
                <span class="badge-item"><i class="bi bi-calendar3"></i> <?= $age ?> ans</span>
                <?php endif; ?>
                <span class="badge-item"><i class="bi bi-gender-<?= ($patient['sexe'] ?? '') === 'M' ? 'male' : 'female' ?>"></i>
                    <?= ($patient['sexe'] ?? '') === 'M' ? 'Masculin' : 'Féminin' ?>
                </span>
                <span class="badge-item"><i class="bi bi-calendar2-event"></i> <?= date('d/m/Y') ?></span>
            </div>
        </div>
    </div>

    <form id="formPerfu" action="#" method="POST" novalidate>
        <input type="hidden" name="patient_id" value="<?= (int)($patient['id'] ?? 0) ?>">
        <?php if ($hosp_id): ?>
        <input type="hidden" name="hosp_id" value="<?= (int)$hosp_id ?>">
        <?php endif; ?>

        <!-- SECTION 1 : Informations générales -->
        <div class="mf-section">
            <div class="mf-section-head">
                <i class="bi bi-info-circle"></i> Informations générales
            </div>
            <div class="mf-section-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Date</label>
                        <input type="date" name="date" class="form-control"
                               value="<?= htmlspecialchars($formData['date'] ?? date('Y-m-d')) ?>">
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Nom du patient</label>
                        <input type="text" name="patient_nom" class="form-control"
                               value="<?= htmlspecialchars($formData['patient_nom'] ?? (($patient['nom'] ?? '') . ' ' . ($patient['prenom'] ?? ''))) ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- SECTION 2 : Médication principale -->
        <div class="mf-section">
            <div class="mf-section-head">
                <i class="bi bi-capsule"></i> Médication principale
            </div>
            <div class="mf-section-body">
                <label class="form-label">Médication / Soluté principal</label>
                <input type="text" name="medication" class="form-control"
                       placeholder="ex : Sérum glucosé 5%, NaCl 9‰, Ringer Lactate..."
                       value="<?= htmlspecialchars($formData['medication'] ?? '') ?>">
            </div>
        </div>

        <!-- SECTION 3 : Lignes de perfusion -->
        <div class="mf-section">
            <div class="mf-section-head">
                <i class="bi bi-list-ol"></i> Lignes de perfusion
            </div>
            <div class="mf-section-body">
                <p class="text-muted small mb-3">Saisissez le contenu de chaque ligne de la carte de perfusion.</p>

                <?php for ($i = 1; $i <= 5; $i++): ?>
                <div class="ligne-perfucode">
                    <div class="ligne-num"><?= $i ?></div>
                    <input type="text" name="ligne_<?= $i ?>" class="form-control"
                           placeholder="Ligne <?= $i ?>..."
                           value="<?= htmlspecialchars($formData['ligne_' . $i] ?? '') ?>">
                </div>
                <?php endfor; ?>
            </div>
        </div>

        <!-- SECTION 4 : Horaires et infirmier -->
        <div class="mf-section">
            <div class="mf-section-head">
                <i class="bi bi-clock"></i> Horaires &amp; Responsable
            </div>
            <div class="mf-section-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Heure de début</label>
                        <input type="time" name="debut_heure" class="form-control"
                               value="<?= htmlspecialchars($formData['debut_heure'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Heure de fin</label>
                        <input type="time" name="fin_heure" class="form-control"
                               value="<?= htmlspecialchars($formData['fin_heure'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Infirmier(ère)</label>
                        <input type="text" name="infirmier" class="form-control"
                               placeholder="Nom complet..."
                               value="<?= htmlspecialchars($formData['infirmier'] ?? $medecin_nom) ?>">
                    </div>
                </div>
            </div>
        </div>

    </form>
</div>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
