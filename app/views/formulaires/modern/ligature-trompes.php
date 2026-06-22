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
.form-control:focus, .form-select:focus { border-color:#be185d; box-shadow:0 0 0 3px rgba(190,24,93,.15); }
.info-badge { background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:10px 14px; font-size:.9rem; color:#334155; }
.readonly-field { background:#f8fafc; border:1.5px solid #e2e8f0; border-radius:8px; padding:10px 12px; color:#64748b; font-weight:600; }

/* Topbar buttons */
.btn-mf-back { display:inline-flex; align-items:center; gap:6px; padding:8px 16px; border-radius:8px; border:1.5px solid #e2e8f0; background:white; color:#475569; font-weight:600; font-size:.88rem; text-decoration:none; transition:.2s; }
.btn-mf-back:hover { background:#f8fafc; color:#1e293b; }
.btn-mf-print { display:inline-flex; align-items:center; gap:6px; padding:8px 16px; border-radius:8px; border:none; background:#f1f5f9; color:#475569; font-weight:600; font-size:.88rem; cursor:pointer; transition:.2s; }
.btn-mf-print:hover { background:#e2e8f0; }
.btn-mf-save { display:inline-flex; align-items:center; gap:6px; padding:8px 20px; border-radius:8px; border:none; background:#be185d; color:white; font-weight:700; font-size:.88rem; cursor:pointer; transition:.2s; }
.btn-mf-save:hover { background:#9d174d; }
.topbar-title { font-weight:800; font-size:1rem; color:#1e293b; display:flex; align-items:center; gap:8px; }
.topbar-title i { color:#be185d; font-size:1.2rem; }

/* Consent confirmation cards */
.consent-card { border:2px solid #e2e8f0; border-radius:12px; padding:16px 20px; cursor:pointer; transition:.2s; display:flex; align-items:flex-start; gap:14px; margin-bottom:10px; }
.consent-card:hover { border-color:#be185d; background:#fdf2f8; }
.consent-card.confirmed { border-color:#059669; background:#f0fdf4; }
.consent-card input[type=checkbox] { display:none; }
.consent-check-icon { width:28px; height:28px; min-width:28px; border-radius:50%; border:2px solid #cbd5e1; display:flex; align-items:center; justify-content:center; transition:.2s; }
.consent-card.confirmed .consent-check-icon { background:#059669; border-color:#059669; color:white; }
.consent-check-icon i { font-size:.9rem; }
.consent-card-text { font-size:.92rem; color:#334155; font-weight:500; line-height:1.5; }

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
        <i class="bi bi-shield-lock"></i>
        Consentement — Ligature des Trompes
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn-mf-print" onclick="window.print()">
            <i class="bi bi-printer"></i> Imprimer
        </button>
        <button type="submit" form="formLigature" class="btn-mf-save">
            <i class="bi bi-save2"></i> Enregistrer
        </button>
    </div>
</div>

<div class="mf-wrap">

    <!-- PATIENT BANNER -->
    <div class="mf-patient-banner" style="background:linear-gradient(135deg,#be185d,#ec4899);">
        <div>
            <div class="pat-name">
                <i class="bi bi-person-circle me-2"></i>
                <?= htmlspecialchars($patient['nom']) ?> <?= htmlspecialchars($patient['prenom']) ?>
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

    <!-- Gravity notice -->
    <div class="alert d-flex align-items-start gap-3 mb-4" style="background:#fff0f3; border:2px solid #be185d; border-radius:12px; padding:16px 20px;">
        <i class="bi bi-shield-exclamation" style="color:#be185d; font-size:1.6rem; margin-top:2px;"></i>
        <div>
            <div style="font-weight:800; color:#be185d; font-size:.9rem; text-transform:uppercase; letter-spacing:.5px; margin-bottom:4px;">Document médico-légal</div>
            <div style="color:#64748b; font-size:.88rem; line-height:1.5;">Ce formulaire est un acte de consentement éclairé à une intervention chirurgicale définitive. Il doit être complété après information complète de la patiente et conservé dans le dossier médical.</div>
        </div>
    </div>

    <form id="formLigature" action="<?= BASE_URL ?>formulaire/sauvegarder/ligature-trompes" method="POST">
        <input type="hidden" name="patient_id" value="<?= $patient['id'] ?>">
        <?php if ($from_suivi): ?><input type="hidden" name="from_suivi" value="1"><?php endif; ?>
        <?php if (!empty($hosp_id)): ?><input type="hidden" name="hosp_id" value="<?= (int)$hosp_id ?>"><?php endif; ?>

        <!-- SECTION 1 : Praticien -->
        <div class="mf-section">
            <div class="mf-section-head" style="background:#fdf2f8; color:#be185d; border-color:#be185d;">
                <i class="bi bi-person-badge"></i> Praticien
            </div>
            <div class="mf-section-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nom du praticien</label>
                        <input type="text" name="praticien_nom" class="form-control" value="<?= htmlspecialchars($medecin_nom) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Date de la consultation</label>
                        <input type="date" name="date_consultation" class="form-control" value="<?= date('Y-m-d') ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- SECTION 2 : Identité de la patiente -->
        <div class="mf-section">
            <div class="mf-section-head" style="background:#fdf2f8; color:#be185d; border-color:#be185d;">
                <i class="bi bi-person-heart"></i> Identité de la patiente
            </div>
            <div class="mf-section-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nom</label>
                        <div class="readonly-field"><?= htmlspecialchars($patient['nom']) ?></div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Prénom</label>
                        <div class="readonly-field"><?= htmlspecialchars($patient['prenom']) ?></div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Date de naissance</label>
                        <div class="readonly-field"><?= !empty($patient['date_naissance']) ? date('d/m/Y', strtotime($patient['date_naissance'])) : '—' ?></div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Lieu de naissance</label>
                        <input type="text" name="lieu_naissance" class="form-control" placeholder="Ville ou localité de naissance">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Nombre d'enfants vivants</label>
                        <input type="number" name="nb_enfants" class="form-control" min="0" placeholder="0">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Date du dernier accouchement</label>
                        <input type="date" name="date_dernier_accouchement" class="form-control">
                    </div>
                </div>
            </div>
        </div>

        <!-- SECTION 3 : Intervention prévue -->
        <div class="mf-section">
            <div class="mf-section-head" style="background:#fdf2f8; color:#be185d; border-color:#be185d;">
                <i class="bi bi-calendar-event"></i> Intervention prévue
            </div>
            <div class="mf-section-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Date prévue de l'intervention</label>
                        <input type="date" name="date_intervention" class="form-control">
                    </div>
                    <div class="col-12">
                        <div class="info-badge d-flex align-items-start gap-3 mt-2" style="background:#fdf2f8; border-color:#fbc5d8;">
                            <i class="bi bi-info-circle-fill" style="color:#be185d; font-size:1.1rem; margin-top:1px;"></i>
                            <span style="font-size:.88rem; color:#6b2244; line-height:1.5;">La ligature des trompes est une intervention de stérilisation tubaire définitive. Elle consiste en l'occlusion chirurgicale des trompes de Fallope afin d'empêcher la fécondation de manière permanente.</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- SECTION 4 : Informations reçues -->
        <div class="mf-section">
            <div class="mf-section-head" style="background:#fdf2f8; color:#be185d; border-color:#be185d;">
                <i class="bi bi-info-circle"></i> Informations reçues
            </div>
            <div class="mf-section-body">
                <p class="text-muted mb-3" style="font-size:.88rem;">La patiente confirme avoir reçu et compris les informations suivantes :</p>

                <label class="consent-card confirmed" id="card-complications">
                    <input type="checkbox" name="info_complications" value="1" checked>
                    <div class="consent-check-icon"><i class="bi bi-check-lg"></i></div>
                    <div class="consent-card-text">J'ai été informée des risques et complications possibles de cette intervention (hémorragie, infection, lésions des organes voisins, anesthésie…)</div>
                </label>

                <label class="consent-card confirmed" id="card-reversibilite">
                    <input type="checkbox" name="info_reversibilite" value="1" checked>
                    <div class="consent-check-icon"><i class="bi bi-check-lg"></i></div>
                    <div class="consent-card-text">J'ai été informée du caractère <strong>définitif (irréversible)</strong> de la ligature des trompes et que les chances de grossesse après tentative de restauration sont très faibles</div>
                </label>

                <label class="consent-card confirmed" id="card-alternatives">
                    <input type="checkbox" name="info_alternatives" value="1" checked>
                    <div class="consent-check-icon"><i class="bi bi-check-lg"></i></div>
                    <div class="consent-card-text">J'ai été informée des méthodes alternatives de contraception (DIU, implant, pilule, contraceptif masculin…) et je choisis librement cette intervention</div>
                </label>
            </div>
        </div>

        <!-- SECTION 5 : Consentement et signature -->
        <div class="mf-section">
            <div class="mf-section-head" style="background:#fdf2f8; color:#be185d; border-color:#be185d;">
                <i class="bi bi-pen"></i> Consentement et signature
            </div>
            <div class="mf-section-body">
                <div class="alert d-flex align-items-start gap-3 mb-4" style="background:#fff7ed; border:1.5px solid #f59e0b; border-radius:10px; padding:14px 18px;">
                    <i class="bi bi-exclamation-triangle-fill" style="color:#f59e0b; font-size:1.1rem; margin-top:1px;"></i>
                    <div style="font-size:.88rem; color:#78350f; line-height:1.5;">
                        <strong>Ce consentement est libre et éclairé.</strong> Vous pouvez le retirer à tout moment avant l'intervention, sans que cela affecte la qualité des soins qui vous seront prodigués.
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Date de signature</label>
                        <input type="date" name="date_signature" class="form-control" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Lieu</label>
                        <input type="text" name="lieu_signature" class="form-control" value="Njombé">
                    </div>
                    <div class="col-12">
                        <div class="row g-4 mt-2">
                            <div class="col-md-6">
                                <div style="border:1.5px solid #e2e8f0; border-radius:10px; padding:20px; text-align:center; min-height:120px;">
                                    <div style="font-size:.8rem; font-weight:700; color:#64748b; text-transform:uppercase; margin-bottom:12px;">Signature de la patiente</div>
                                    <div style="border-bottom:1.5px solid #cbd5e1; margin:0 20px 8px;"></div>
                                    <div style="font-size:.78rem; color:#94a3b8;"><?= htmlspecialchars($patient['nom'].' '.$patient['prenom']) ?></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div style="border:1.5px solid #e2e8f0; border-radius:10px; padding:20px; text-align:center; min-height:120px;">
                                    <div style="font-size:.8rem; font-weight:700; color:#64748b; text-transform:uppercase; margin-bottom:12px;">Signature du praticien</div>
                                    <div style="border-bottom:1.5px solid #cbd5e1; margin:0 20px 8px;"></div>
                                    <div style="font-size:.78rem; color:#94a3b8;"><?= htmlspecialchars($medecin_nom) ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </form>
</div><!-- /.mf-wrap -->

<script>
// Consent card toggle
document.querySelectorAll('.consent-card').forEach(card => {
    card.addEventListener('click', function() {
        const cb = this.querySelector('input[type=checkbox]');
        cb.checked = !cb.checked;
        this.classList.toggle('confirmed', cb.checked);
    });
});
</script>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
