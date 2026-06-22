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
.form-control:focus, .form-select:focus { border-color:#0369a1; box-shadow:0 0 0 3px rgba(3,105,161,.15); }
.info-badge { background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:10px 14px; font-size:.9rem; color:#334155; }
.readonly-field { background:#f8fafc; border:1.5px solid #e2e8f0; border-radius:8px; padding:10px 12px; color:#64748b; font-weight:600; }

/* Topbar buttons */
.btn-mf-back { display:inline-flex; align-items:center; gap:6px; padding:8px 16px; border-radius:8px; border:1.5px solid #e2e8f0; background:white; color:#475569; font-weight:600; font-size:.88rem; text-decoration:none; transition:.2s; }
.btn-mf-back:hover { background:#f8fafc; color:#1e293b; }
.btn-mf-print { display:inline-flex; align-items:center; gap:6px; padding:8px 16px; border-radius:8px; border:none; background:#f1f5f9; color:#475569; font-weight:600; font-size:.88rem; cursor:pointer; transition:.2s; }
.btn-mf-print:hover { background:#e2e8f0; }
.btn-mf-save { display:inline-flex; align-items:center; gap:6px; padding:8px 20px; border-radius:8px; border:none; background:#0369a1; color:white; font-weight:700; font-size:.88rem; cursor:pointer; transition:.2s; }
.btn-mf-save:hover { background:#075985; }
.topbar-title { font-weight:800; font-size:1rem; color:#1e293b; display:flex; align-items:center; gap:8px; }
.topbar-title i { color:#0369a1; font-size:1.2rem; }

/* Gender styled radio buttons */
.gender-btn-group { display:flex; gap:10px; }
.gender-btn { flex:1; border:2px solid #e2e8f0; border-radius:10px; padding:12px 10px; text-align:center; cursor:pointer; transition:.2s; font-weight:700; font-size:.9rem; }
.gender-btn:hover { border-color:#0369a1; }
.gender-btn input[type=radio] { display:none; }
.gender-btn.active-m { border-color:#0369a1; background:#eff6ff; color:#0369a1; }
.gender-btn.active-f { border-color:#be185d; background:#fdf2f8; color:#be185d; }
.gender-btn i { display:block; font-size:1.5rem; margin-bottom:4px; }

/* Measurement boxes */
.measure-box { background:#f0f9ff; border:1.5px solid #bae6fd; border-radius:12px; padding:16px; text-align:center; }
.measure-label { font-size:.72rem; font-weight:800; color:#0369a1; text-transform:uppercase; letter-spacing:.5px; margin-bottom:8px; }
.measure-input { font-size:1.4rem; font-weight:800; color:#0369a1; text-align:center; border:none; background:transparent; width:100%; outline:none; border-bottom:2px solid #bae6fd; }
.measure-unit { font-size:.75rem; color:#64748b; margin-top:5px; font-weight:600; }

/* Pill radio buttons */
.pill-group { display:flex; flex-wrap:wrap; gap:8px; }
.pill-btn { border:2px solid #e2e8f0; border-radius:50px; padding:8px 18px; cursor:pointer; transition:.2s; font-weight:600; font-size:.88rem; color:#475569; display:flex; align-items:center; gap:6px; }
.pill-btn input[type=radio] { display:none; }
.pill-btn:hover { border-color:#0369a1; color:#0369a1; }
.pill-btn.pill-active { border-color:#0369a1; background:#eff6ff; color:#0369a1; }
.pill-btn.pill-green { border-color:#059669; background:#f0fdf4; color:#059669; }
.pill-btn.pill-orange { border-color:#d97706; background:#fffbeb; color:#d97706; }
.pill-btn.pill-red { border-color:#dc2626; background:#fef2f2; color:#dc2626; }

/* APGAR inputs */
.apgar-box { border:2px solid #e2e8f0; border-radius:12px; padding:16px; text-align:center; transition:.2s; }
.apgar-label { font-size:.75rem; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:.5px; margin-bottom:10px; }
.apgar-input { font-size:2rem; font-weight:900; text-align:center; border:none; background:transparent; width:100%; outline:none; transition:.2s; }
.apgar-range { font-size:.75rem; color:#94a3b8; margin-top:4px; }

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
        <i class="bi bi-heart-pulse"></i>
        Paramètres du Nouveau-Né
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn-mf-print" onclick="window.print()">
            <i class="bi bi-printer"></i> Imprimer
        </button>
        <button type="submit" form="formNN" class="btn-mf-save">
            <i class="bi bi-save2"></i> Enregistrer
        </button>
    </div>
</div>

<div class="mf-wrap">

    <!-- PATIENT BANNER (mère) -->
    <div class="mf-patient-banner" style="background:linear-gradient(135deg,#0369a1,#0ea5e9);">
        <div>
            <div class="pat-name">
                <i class="bi bi-person-heart me-2"></i>
                Mère : <?= htmlspecialchars($patient['nom']) ?> <?= htmlspecialchars($patient['prenom']) ?>
            </div>
            <div class="pat-info mt-1">
                <i class="bi bi-calendar3 me-1"></i><?= $age ?> ans
                &nbsp;|&nbsp; <i class="bi bi-gender-female me-1"></i>Féminin
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

    <form id="formNN" action="<?= BASE_URL ?>formulaire/sauvegarder/parametres-nouveau-ne" method="POST">
        <input type="hidden" name="patient_id" value="<?= $patient['id'] ?>">
        <?php if ($from_suivi): ?><input type="hidden" name="from_suivi" value="1"><?php endif; ?>
        <?php if (!empty($hosp_id)): ?><input type="hidden" name="hosp_id" value="<?= (int)$hosp_id ?>"><?php endif; ?>

        <!-- SECTION 1 : Identité du nouveau-né -->
        <div class="mf-section">
            <div class="mf-section-head" style="background:#f0f9ff; color:#0369a1; border-color:#0369a1;">
                <i class="bi bi-person-fill"></i> Identité du nouveau-né
            </div>
            <div class="mf-section-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Date de naissance <span class="text-danger">*</span></label>
                        <input type="date" name="date_naissance_nn" class="form-control" required value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Heure de naissance</label>
                        <input type="time" name="heure_naissance" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Terme (semaines d'aménorrhée)</label>
                        <input type="number" name="terme" class="form-control" min="22" max="45" placeholder="semaines">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Sexe</label>
                        <div class="gender-btn-group">
                            <label class="gender-btn" id="gender-m">
                                <input type="radio" name="sexe_nn" value="M" onchange="updateGender(this)">
                                <i class="bi bi-gender-male"></i>
                                Masculin
                            </label>
                            <label class="gender-btn" id="gender-f">
                                <input type="radio" name="sexe_nn" value="F" onchange="updateGender(this)">
                                <i class="bi bi-gender-female"></i>
                                Féminin
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- SECTION 2 : Mère -->
        <div class="mf-section">
            <div class="mf-section-head" style="background:#f0f9ff; color:#0369a1; border-color:#0369a1;">
                <i class="bi bi-person-heart"></i> Mère
            </div>
            <div class="mf-section-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nom / Prénom de la mère</label>
                        <div class="readonly-field"><?= htmlspecialchars($patient['nom'].' '.$patient['prenom']) ?></div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Groupe sanguin (mère)</label>
                        <select name="groupe_sanguin_mere" class="form-select">
                            <option value="">— Sélectionner —</option>
                            <?php foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $g): ?>
                                <option value="<?= $g ?>"><?= $g ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- SECTION 3 : Anthropométrie -->
        <div class="mf-section">
            <div class="mf-section-head" style="background:#f0f9ff; color:#0369a1; border-color:#0369a1;">
                <i class="bi bi-rulers"></i> Anthropométrie
            </div>
            <div class="mf-section-body">
                <div class="row g-3">
                    <div class="col-6 col-md-3">
                        <div class="measure-box">
                            <div class="measure-label">Poids</div>
                            <input type="number" name="poids_naissance" class="measure-input" placeholder="—" step="1">
                            <div class="measure-unit">grammes</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="measure-box">
                            <div class="measure-label">Taille</div>
                            <input type="number" name="taille" class="measure-input" placeholder="—" step="0.1">
                            <div class="measure-unit">cm</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="measure-box">
                            <div class="measure-label">Périm. crânien</div>
                            <input type="number" name="perimetre_cranien" class="measure-input" placeholder="—" step="0.1">
                            <div class="measure-unit">cm</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="measure-box">
                            <div class="measure-label">Périm. thoracique</div>
                            <input type="number" name="perimetre_thoracique" class="measure-input" placeholder="—" step="0.1">
                            <div class="measure-unit">cm</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- SECTION 4 : Score d'APGAR -->
        <div class="mf-section">
            <div class="mf-section-head" style="background:#f0f9ff; color:#0369a1; border-color:#0369a1;">
                <i class="bi bi-stars"></i> Score d'APGAR
            </div>
            <div class="mf-section-body">
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <div class="apgar-box" id="apgar1-box">
                            <div class="apgar-label"><i class="bi bi-1-circle me-1"></i>APGAR à 1 minute</div>
                            <input type="number" name="apgar_1min" id="apgar1" class="apgar-input" min="0" max="10" placeholder="—">
                            <div class="apgar-range">Score de 0 à 10</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="apgar-box" id="apgar5-box">
                            <div class="apgar-label"><i class="bi bi-5-circle me-1"></i>APGAR à 5 minutes</div>
                            <input type="number" name="apgar_5min" id="apgar5" class="apgar-input" min="0" max="10" placeholder="—">
                            <div class="apgar-range">Score de 0 à 10</div>
                        </div>
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-3" style="font-size:.8rem; color:#64748b;">
                    <span><span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#059669;margin-right:4px;"></span>7–10 : Normal</span>
                    <span><span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#d97706;margin-right:4px;"></span>4–6 : Souffrance modérée</span>
                    <span><span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#dc2626;margin-right:4px;"></span>0–3 : Souffrance sévère</span>
                </div>
            </div>
        </div>

        <!-- SECTION 5 : Accouchement -->
        <div class="mf-section">
            <div class="mf-section-head" style="background:#f0f9ff; color:#0369a1; border-color:#0369a1;">
                <i class="bi bi-hospital"></i> Accouchement
            </div>
            <div class="mf-section-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Type d'accouchement</label>
                        <div class="pill-group mt-1" id="type-acc-group">
                            <label class="pill-btn">
                                <input type="radio" name="type_accouchement" value="voie_basse" onchange="setPill(this,'type-acc-group')">
                                <i class="bi bi-heart-pulse"></i> Voie basse
                            </label>
                            <label class="pill-btn">
                                <input type="radio" name="type_accouchement" value="cesarienne" onchange="setPill(this,'type-acc-group')">
                                <i class="bi bi-scissors"></i> Césarienne
                            </label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">État général</label>
                        <div class="pill-group mt-1" id="etat-gen-group">
                            <label class="pill-btn" data-variant="green">
                                <input type="radio" name="etat_general" value="bon" onchange="setPillColor(this)">
                                <i class="bi bi-emoji-smile"></i> Bon
                            </label>
                            <label class="pill-btn" data-variant="orange">
                                <input type="radio" name="etat_general" value="moyen" onchange="setPillColor(this)">
                                <i class="bi bi-emoji-neutral"></i> Moyen
                            </label>
                            <label class="pill-btn" data-variant="red">
                                <input type="radio" name="etat_general" value="mauvais" onchange="setPillColor(this)">
                                <i class="bi bi-emoji-frown"></i> Mauvais
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- SECTION 6 : Données biologiques -->
        <div class="mf-section">
            <div class="mf-section-head" style="background:#f0f9ff; color:#0369a1; border-color:#0369a1;">
                <i class="bi bi-droplet-half"></i> Données biologiques
            </div>
            <div class="mf-section-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Groupe sanguin (nouveau-né)</label>
                        <select name="groupe_sanguin_nn" class="form-select">
                            <option value="">— Sélectionner —</option>
                            <?php foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $g): ?>
                                <option value="<?= $g ?>"><?= $g ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Rhésus</label>
                        <div class="pill-group mt-1" id="rhesus-group">
                            <label class="pill-btn">
                                <input type="radio" name="rhesus_nn" value="positif" onchange="setPill(this,'rhesus-group')">
                                <i class="bi bi-plus-circle"></i> Positif
                            </label>
                            <label class="pill-btn">
                                <input type="radio" name="rhesus_nn" value="negatif" onchange="setPill(this,'rhesus-group')">
                                <i class="bi bi-dash-circle"></i> Négatif
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- SECTION 7 : Complications / Observations -->
        <div class="mf-section">
            <div class="mf-section-head" style="background:#f0f9ff; color:#0369a1; border-color:#0369a1;">
                <i class="bi bi-exclamation-triangle"></i> Complications / Observations
            </div>
            <div class="mf-section-body">
                <label class="form-label">Complications éventuelles et observations cliniques</label>
                <textarea name="complications" class="form-control" rows="3" placeholder="Détresser respiratoire, asphyxie, malformations, observations particulières…"></textarea>
            </div>
        </div>

    </form>
</div><!-- /.mf-wrap -->

<script>
// Gender buttons
function updateGender(radio) {
    document.getElementById('gender-m').classList.remove('active-m');
    document.getElementById('gender-f').classList.remove('active-f');
    if (radio.value === 'M') document.getElementById('gender-m').classList.add('active-m');
    else document.getElementById('gender-f').classList.add('active-f');
}

// Generic pill group (blue)
function setPill(radio, groupId) {
    document.querySelectorAll('#' + groupId + ' .pill-btn').forEach(p => p.classList.remove('pill-active'));
    radio.closest('.pill-btn').classList.add('pill-active');
}

// Color-coded pill (état général)
function setPillColor(radio) {
    const grp = radio.closest('.pill-group');
    grp.querySelectorAll('.pill-btn').forEach(p => p.classList.remove('pill-active','pill-green','pill-orange','pill-red'));
    const variant = radio.closest('.pill-btn').dataset.variant;
    radio.closest('.pill-btn').classList.add('pill-' + variant);
}

// APGAR color coding
document.querySelectorAll('[name="apgar_1min"],[name="apgar_5min"]').forEach(el => {
    el.addEventListener('input', function() {
        const v = parseInt(this.value);
        this.style.borderColor = v >= 7 ? '#059669' : v >= 4 ? '#d97706' : '#dc2626';
        this.style.color = v >= 7 ? '#059669' : v >= 4 ? '#d97706' : '#dc2626';
    });
});
</script>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
