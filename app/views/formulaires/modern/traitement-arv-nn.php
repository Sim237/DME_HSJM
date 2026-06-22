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

/* Sticky action bar */
.mf-topbar {
    position: sticky; top: 0; z-index: 200;
    background: white; border-bottom: 1px solid #e2e8f0;
    padding: 10px 20px; display: flex; justify-content: space-between;
    align-items: center; box-shadow: 0 2px 8px rgba(0,0,0,.07);
}

/* PTME confidential banner */
.ptme-alert {
    background: linear-gradient(90deg, #dc2626, #b91c1c);
    color: white; text-align: center; padding: 10px 20px;
    font-weight: 700; font-size: .82rem; letter-spacing: 1.5px;
    text-transform: uppercase; display: flex; align-items: center;
    justify-content: center; gap: 10px;
}

/* Patient banner */
.mf-patient-banner {
    background: linear-gradient(135deg, #0f766e, #0d9488);
    border-radius: 16px; padding: 20px 24px; color: white;
    margin: 20px 0; display: flex; justify-content: space-between;
    align-items: center; flex-wrap: wrap; gap: 12px;
}
.pat-name { font-size: 1.2rem; font-weight: 800; }
.pat-meta { font-size: .83rem; opacity: .88; margin-top: 4px; }
.pat-badge {
    background: rgba(255,255,255,.18); border-radius: 8px;
    padding: 5px 12px; font-size: .82rem; font-weight: 600;
}

/* Section cards */
.mf-section {
    background: white; border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,.06);
    margin-bottom: 16px; overflow: hidden;
}
.mf-section-head {
    background: #f0fdfa; padding: 12px 20px;
    font-weight: 700; font-size: .85rem; text-transform: uppercase;
    letter-spacing: .5px; display: flex; align-items: center; gap: 8px;
    border-bottom: 2px solid #0f766e; color: #0f766e;
}
.mf-section-body { padding: 20px; }

/* Form controls */
.form-label {
    font-weight: 600; font-size: .8rem; color: #64748b;
    text-transform: uppercase; letter-spacing: .4px; margin-bottom: 5px;
}
.form-control, .form-select {
    border: 1.5px solid #e2e8f0; border-radius: 8px;
    font-size: .93rem; padding: 10px 12px; transition: .2s;
}
.form-control:focus, .form-select:focus {
    border-color: #0f766e;
    box-shadow: 0 0 0 3px rgba(15,118,110,.15);
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
    border-color: #0f766e; background: #ccfbf1; color: #0f766e;
}

/* OUI/NON btn group */
.oui-non { display: inline-flex; border-radius: 8px; overflow: hidden; border: 1.5px solid #e2e8f0; }
.oui-non input[type=radio] { display: none; }
.oui-non label {
    padding: 8px 18px; font-weight: 700; font-size: .85rem;
    cursor: pointer; background: white; color: #64748b; transition: .15s;
}
.oui-non input[value="O"]:checked + label { background: #d1fae5; color: #065f46; }
.oui-non input[value="N"]:checked + label { background: #fee2e2; color: #991b1b; }

/* Hidden row helper */
.hidden-row { display: none; }

/* Action buttons */
.btn-back {
    display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px;
    border-radius: 8px; border: 1.5px solid #e2e8f0; background: white;
    color: #475569; font-weight: 600; font-size: .88rem; text-decoration: none; transition: .2s;
}
.btn-back:hover { background: #f8fafc; color: #1e293b; }
.btn-save {
    display: inline-flex; align-items: center; gap: 6px; padding: 9px 22px;
    border-radius: 8px; border: none; background: #0f766e; color: white;
    font-weight: 700; font-size: .9rem; cursor: pointer; transition: .2s;
}
.btn-save:hover { background: #0d9488; }
.topbar-title { font-weight: 800; font-size: .95rem; color: #1e293b; }

@media print {
    .mf-topbar, .ptme-alert { display: none !important; }
    body { background: white !important; }
    .mf-section { box-shadow: none; border: 1px solid #e2e8f0; }
    .mf-patient-banner { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
}
</style>

<!-- PTME Confidential Banner -->
<div class="ptme-alert">
    <i class="bi bi-shield-lock-fill"></i>
    CONFIDENTIEL — PRÉVENTION DE LA TRANSMISSION MÈRE-ENFANT (PTME)
    <i class="bi bi-shield-lock-fill"></i>
</div>

<!-- Sticky Topbar -->
<div class="mf-topbar">
    <div class="d-flex align-items-center gap-3">
        <?php if ($from_suivi): ?>
        <a href="<?= BASE_URL ?>hospitalisation/suivi/<?= (int)$patient['id'] ?>" class="btn-back">
            <i class="bi bi-arrow-left"></i> Retour
        </a>
        <?php else: ?>
        <a href="<?= BASE_URL ?>patients/dossier/<?= (int)$patient['id'] ?>" class="btn-back">
            <i class="bi bi-arrow-left"></i> Retour
        </a>
        <?php endif; ?>
        <div class="topbar-title">
            <i class="bi bi-capsule" style="color:#0f766e;font-size:1.1rem;"></i>
            Traitement ARV du Nouveau-Né
        </div>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill" onclick="window.print()">
            <i class="bi bi-printer"></i> Imprimer
        </button>
        <button type="submit" form="formPTME" class="btn-save">
            <i class="bi bi-save2-fill"></i> Enregistrer
        </button>
    </div>
</div>

<div class="mf-wrap">

    <!-- Patient Banner -->
    <div class="mf-patient-banner">
        <div>
            <div class="pat-name"><?= htmlspecialchars(($patient['nom'] ?? '') . ' ' . ($patient['prenom'] ?? '')) ?></div>
            <div class="pat-meta">
                Née le <?= !empty($patient['date_naissance']) ? date('d/m/Y', strtotime($patient['date_naissance'])) : '—' ?>
                &nbsp;·&nbsp; <?= $age ?> ans
                &nbsp;·&nbsp; <?= htmlspecialchars($patient['sexe'] ?? '') ?>
            </div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <?php if (!empty($patient['dossier_numero'])): ?>
            <span class="pat-badge"><i class="bi bi-folder2-open me-1"></i><?= htmlspecialchars($patient['dossier_numero']) ?></span>
            <?php endif; ?>
            <span class="pat-badge"><i class="bi bi-calendar3 me-1"></i><?= date('d/m/Y') ?></span>
            <span class="pat-badge" style="background:rgba(220,38,38,.25);border:1px solid rgba(255,255,255,.3);">
                <i class="bi bi-shield-lock-fill me-1"></i>PTME
            </span>
        </div>
    </div>

    <form id="formPTME" action="#" method="POST">
        <input type="hidden" name="patient_id" value="<?= (int)$patient['id'] ?>">
        <?php if ($hosp_id): ?>
        <input type="hidden" name="hosp_id" value="<?= (int)$hosp_id ?>">
        <?php endif; ?>

        <!-- ══ 1. NOUVEAU-NÉ ════════════════════════════════════════════ -->
        <div class="mf-section">
            <div class="mf-section-head">
                <i class="bi bi-person-fill"></i> Nouveau-né
            </div>
            <div class="mf-section-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Date de naissance</label>
                        <input type="date" name="date_naissance_nn" class="form-control"
                               value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Poids de naissance</label>
                        <div class="input-group">
                            <input type="number" name="poids_naissance" class="form-control"
                                   placeholder="ex : 3200" min="300" max="6000" step="10">
                            <span class="input-group-text text-muted small">g</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Terme</label>
                        <div class="input-group">
                            <input type="number" name="terme" class="form-control"
                                   placeholder="semaines" min="22" max="45">
                            <span class="input-group-text text-muted small">SA</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ══ 2. STATUT DE LA MÈRE ════════════════════════════════════ -->
        <div class="mf-section">
            <div class="mf-section-head">
                <i class="bi bi-person-heart"></i> Statut sérologique de la mère
            </div>
            <div class="mf-section-body">
                <div class="row g-3">
                    <div class="col-md-12">
                        <label class="form-label">Statut VIH maternel</label>
                        <div class="pill-group">
                            <div class="pill-radio">
                                <input type="radio" name="statut_mere" id="sm_pos" value="VIH+" checked>
                                <label for="sm_pos"><i class="bi bi-circle-fill text-danger"></i> VIH+</label>
                            </div>
                            <div class="pill-radio">
                                <input type="radio" name="statut_mere" id="sm_neg" value="VIH-">
                                <label for="sm_neg"><i class="bi bi-circle text-success"></i> VIH−</label>
                            </div>
                            <div class="pill-radio">
                                <input type="radio" name="statut_mere" id="sm_unk" value="Inconnu">
                                <label for="sm_unk"><i class="bi bi-question-circle"></i> Inconnu</label>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Charge virale maternelle (si connue)</label>
                        <input type="text" name="cv_mere" class="form-control"
                               placeholder="copies/mL ou Indétectable">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Traitement ARV de la mère</label>
                        <input type="text" name="traitement_mere" class="form-control"
                               placeholder="Protocole ARV maternel">
                    </div>
                </div>
            </div>
        </div>

        <!-- ══ 3. PROTOCOLE ARV NN ══════════════════════════════════════ -->
        <div class="mf-section">
            <div class="mf-section-head">
                <i class="bi bi-capsule"></i> Protocole ARV pour le nouveau-né
            </div>
            <div class="mf-section-body">
                <div class="row g-3">
                    <div class="col-md-12">
                        <label class="form-label">Protocole choisi</label>
                        <div class="pill-group">
                            <div class="pill-radio">
                                <input type="radio" name="protocole_arv" id="p_nvp" value="NVP seule"
                                       checked onchange="toggleAutreProtocole(this)">
                                <label for="p_nvp">NVP seule</label>
                            </div>
                            <div class="pill-radio">
                                <input type="radio" name="protocole_arv" id="p_azt" value="AZT+NVP"
                                       onchange="toggleAutreProtocole(this)">
                                <label for="p_azt">AZT + NVP</label>
                            </div>
                            <div class="pill-radio">
                                <input type="radio" name="protocole_arv" id="p_oth" value="Autre"
                                       onchange="toggleAutreProtocole(this)">
                                <label for="p_oth">Autre</label>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-12 hidden-row" id="protocole_autre_row">
                        <label class="form-label">Préciser le protocole</label>
                        <input type="text" name="protocole_autre" class="form-control"
                               placeholder="Décrivez le protocole alternatif">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Dose NVP</label>
                        <div class="input-group">
                            <input type="text" name="dose_nvp" class="form-control"
                                   placeholder="mg/kg ou mg/dose">
                            <span class="input-group-text text-muted small">mg</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Dose AZT</label>
                        <div class="input-group">
                            <input type="text" name="dose_azt" class="form-control"
                                   placeholder="mg/kg/jour">
                            <span class="input-group-text text-muted small">mg</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Date de début du traitement</label>
                        <input type="date" name="date_debut_traitement" class="form-control"
                               value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Durée du traitement</label>
                        <select name="duree_traitement" class="form-select">
                            <option value="4 semaines">4 semaines</option>
                            <option value="6 semaines">6 semaines</option>
                            <option value="12 semaines">12 semaines</option>
                            <option value="Selon protocole">Selon protocole national</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- ══ 4. COTRIMOXAZOLE ═════════════════════════════════════════ -->
        <div class="mf-section">
            <div class="mf-section-head">
                <i class="bi bi-shield-plus"></i> Prophylaxie au cotrimoxazole
            </div>
            <div class="mf-section-body">
                <div class="row g-3 align-items-center">
                    <div class="col-md-4">
                        <label class="form-label d-block">Cotrimoxazole prescrit</label>
                        <div class="oui-non">
                            <input type="radio" name="cotrimoxazole" id="co_n" value="N" checked
                                   onchange="toggleCotriDate(this)">
                            <label for="co_n">NON</label>
                            <input type="radio" name="cotrimoxazole" id="co_o" value="O"
                                   onchange="toggleCotriDate(this)">
                            <label for="co_o">OUI</label>
                        </div>
                    </div>
                    <div class="col-md-8 hidden-row" id="cotri_date_row">
                        <label class="form-label">Date de début du cotrimoxazole</label>
                        <input type="date" name="date_debut_cotri" class="form-control">
                    </div>
                </div>
            </div>
        </div>

        <!-- ══ 5. OBSERVATIONS ══════════════════════════════════════════ -->
        <div class="mf-section">
            <div class="mf-section-head">
                <i class="bi bi-journal-text"></i> Observations / Notes cliniques
            </div>
            <div class="mf-section-body">
                <label class="form-label">Observations</label>
                <textarea name="observations" class="form-control" rows="4"
                          placeholder="Toute information clinique complémentaire, réactions, recommandations de suivi..."></textarea>
            </div>
        </div>

    </form>
</div>

<script>
function toggleAutreProtocole(el) {
    const row = document.getElementById('protocole_autre_row');
    if (row) row.style.display = (el.value === 'Autre') ? 'block' : 'none';
}

function toggleCotriDate(el) {
    const row = document.getElementById('cotri_date_row');
    if (row) row.style.display = (el.value === 'O') ? 'flex' : 'none';
}
</script>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
