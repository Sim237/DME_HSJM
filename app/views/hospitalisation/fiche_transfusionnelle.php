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
 require_once __DIR__ . '/../layouts/header.php'; ?>

<style>
    :root {
        --red-primary: #c0392b;
        --red-light: #fadbd8;
        --dark-bg: #212529;
    }

    body { background-color: #f4f7f9; font-family: 'Inter', sans-serif; }

    .paper-sheet {
        background: white;
        width: 100%;
        max-width: 1400px;
        margin: 0 auto;
        padding: 40px;
        box-shadow: 0 15px 50px rgba(0,0,0,0.1);
        min-height: 29.7cm;
    }

    .hospital-brand-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 3px solid var(--dark-bg);
        padding-bottom: 20px;
        margin-bottom: 25px;
    }

    .title-box {
        text-align: center;
        border: 4px double var(--red-primary);
        padding: 10px 35px;
        background: var(--red-light);
    }
    .title-box h1 { font-weight: 900; margin: 0; letter-spacing: 2px; font-size: 1.6rem; color: var(--red-primary); }
    .title-box small { font-size: 0.75rem; color: #555; font-weight: 600; letter-spacing: 1px; }

    .patient-dashboard {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 20px;
        background: var(--dark-bg);
        color: white;
        padding: 18px 24px;
        border-radius: 10px;
        margin-bottom: 25px;
    }
    .stat-item { border-left: 3px solid var(--red-primary); padding-left: 14px; }
    .stat-label { font-size: 0.68rem; color: #adb5bd; text-transform: uppercase; font-weight: 700; }
    .stat-value { font-size: 1rem; font-weight: 600; display: block; }

    /* Bloc groupage / indication */
    .info-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 25px;
    }
    .info-block {
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 16px;
        background: #fdfdfd;
    }
    .info-block h6 {
        font-size: 0.75rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: var(--red-primary);
        margin-bottom: 12px;
        border-bottom: 1px solid var(--red-light);
        padding-bottom: 6px;
    }
    .form-label-sm { font-size: 0.78rem; font-weight: 600; color: #495057; margin-bottom: 3px; }
    .form-control-sm-custom {
        border: none;
        border-bottom: 1px solid #ced4da;
        border-radius: 0;
        padding: 4px 2px;
        font-size: 0.85rem;
        width: 100%;
        background: transparent;
    }
    .form-control-sm-custom:focus { outline: none; border-color: var(--red-primary); box-shadow: none; }

    /* Tableau de transfusion */
    .table-container { overflow-x: auto; border-radius: 6px; box-shadow: 0 0 0 1px #dee2e6; }
    .trans-table { width: 100%; border-collapse: collapse; font-size: 0.82rem; }
    .trans-table th {
        background: var(--red-primary);
        color: white;
        padding: 10px 8px;
        border: 1px solid #c0392b;
        text-align: center;
        white-space: nowrap;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .trans-table td { border: 1px solid #dee2e6; padding: 0; }
    .trans-input {
        border: none; width: 100%; height: 42px;
        text-align: center; font-size: 0.82rem;
        background: transparent; transition: 0.2s;
    }
    .trans-input:focus { background: #fff5f5; outline: none; box-shadow: inset 0 0 0 2px var(--red-primary); }
    .trans-input.reaction-input { font-size: 0.75rem; }

    /* Badges groupe sanguin */
    .blood-badge {
        display: inline-block; font-weight: 900; font-size: 1.1rem;
        color: var(--red-primary); background: var(--red-light);
        padding: 2px 10px; border-radius: 6px;
    }

    /* Section observations finales */
    .obs-section { border: 1px solid #dee2e6; border-radius: 8px; padding: 16px; margin-top: 25px; }
    .obs-section h6 { font-size: 0.75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; color: var(--red-primary); margin-bottom: 10px; }
    .obs-textarea { width: 100%; border: none; border-bottom: 1px solid #ced4da; resize: none; font-size: 0.85rem; padding: 4px 2px; background: transparent; }
    .obs-textarea:focus { outline: none; border-color: var(--red-primary); }

    /* Boutons flottants */
    .action-fab { position: fixed; bottom: 30px; right: 30px; display: flex; flex-direction: column; gap: 10px; z-index: 1000; }
    .btn-fab {
        width: 52px; height: 52px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        box-shadow: 0 4px 12px rgba(0,0,0,0.2); transition: transform 0.2s; border: none;
    }
    .btn-fab:hover { transform: scale(1.1); }

    @media print {
        .no-print, .action-fab { display: none !important; }
        .paper-sheet { box-shadow: none; padding: 20px; }
        .patient-dashboard { background: white !important; color: black !important; border: 1px solid black; }
        .stat-value { color: black !important; }
        .stat-label { color: #555 !important; }
    }
</style>

<div class="container-fluid pb-5">
    <!-- BARRE D'ACTIONS TOP -->
    <div class="d-flex justify-content-between align-items-center py-3 px-4 bg-white border-bottom shadow-sm no-print sticky-top mb-3">
        <div class="d-flex gap-2">
            <a href="<?= BASE_URL ?>hospitalisation/suivi/<?= $patient['id'] ?>" class="btn btn-outline-dark btn-sm rounded-pill">
                <i class="bi bi-arrow-left"></i> Retour Suivi
            </a>
            <a href="<?= BASE_URL ?>dashboard" class="btn btn-outline-secondary btn-sm rounded-pill">
                <i class="bi bi-house-door"></i> Dashboard
            </a>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-dark btn-sm rounded-pill px-3" onclick="addRow()">
                <i class="bi bi-plus-circle me-1"></i> Ajouter ligne
            </button>
            <button class="btn btn-outline-primary btn-sm rounded-pill px-3" onclick="window.print()">
                <i class="bi bi-printer"></i> Imprimer
            </button>
            <button type="submit" form="formTrans" class="btn btn-danger btn-sm rounded-pill px-4 fw-bold">
                <i class="bi bi-cloud-upload"></i> Enregistrer
            </button>
        </div>
    </div>

    <div class="paper-sheet">
        <form id="formTrans" action="<?= BASE_URL ?>hospitalisation/save-transfusion" method="POST">
            <input type="hidden" name="patient_id" value="<?= $patient['id'] ?>">

            <!-- EN-TÊTE HÔPITAL -->
            <div class="hospital-brand-header">
                <div class="d-flex align-items-center">
                    <img src="<?= BASE_URL ?>public/images/logo_ordre_malte.png" style="height: 55px;" alt="Logo">
                    <div class="ms-3">
                        <div class="fw-bold text-primary" style="font-size: 0.85rem;">ORDRE DE MALTE</div>
                        <div class="small fw-bold">HÔPITAL SAINT-JEAN DE MALTE</div>
                        <div class="text-muted" style="font-size: 0.7rem;">NJOMBE - CAMEROUN</div>
                    </div>
                </div>

                <div class="title-box">
                    <h1>FICHE TRANSFUSIONNELLE</h1>
                    <small>SUIVI DES TRANSFUSIONS SANGUINES</small>
                </div>

                <div class="text-end small fw-bold text-muted">
                    <div>Date : <?= date('d/m/Y') ?></div>
                    <div>Réf. : FT-<?= str_pad($patient['id'], 5, '0', STR_PAD_LEFT) ?></div>
                </div>
            </div>

            <!-- DASHBOARD PATIENT -->
            <div class="patient-dashboard">
                <div class="stat-item">
                    <span class="stat-label">Patient</span>
                    <span class="stat-value text-uppercase"><?= htmlspecialchars($patient['nom'] . ' ' . $patient['prenom']) ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Âge / Sexe</span>
                    <span class="stat-value"><?= $age ?> ans / <?= htmlspecialchars($patient['sexe'] ?? 'M') ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">N° Dossier</span>
                    <span class="stat-value"><?= htmlspecialchars($patient['dossier_numero']) ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Service / Lit</span>
                    <span class="stat-value"><?= htmlspecialchars($patient['nom_service'] ?? '-') ?> / <?= htmlspecialchars($patient['nom_lit'] ?? '-') ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Groupe Sanguin</span>
                    <span class="stat-value">
                        <input type="text" name="groupe_sanguin" class="bg-transparent border-0 text-white fw-bold p-0"
                               style="width: 80px;" placeholder="ex: A+"
                               value="<?= htmlspecialchars($patient['groupe_sanguin'] ?? '') ?>">
                    </span>
                </div>
            </div>

            <!-- BLOC INFOS TRANSFUSION -->
            <div class="info-grid">
                <!-- Indication & Contexte clinique -->
                <div class="info-block">
                    <h6><i class="bi bi-clipboard-heart me-1"></i> Indication & Contexte Clinique</h6>
                    <div class="mb-3">
                        <label class="form-label-sm">Indication de la transfusion</label>
                        <select name="indication" class="form-select form-select-sm border-0 border-bottom rounded-0 bg-transparent">
                            <option value="">-- Sélectionner --</option>
                            <option value="Anémie sévère">Anémie sévère (Hb &lt; 7 g/dL)</option>
                            <option value="Hémorragie aiguë">Hémorragie aiguë</option>
                            <option value="Hémostase chirurgicale">Hémostase chirurgicale</option>
                            <option value="Thrombopénie sévère">Thrombopénie sévère</option>
                            <option value="Drépanocytose">Drépanocytose</option>
                            <option value="Chirurgie programmée">Chirurgie programmée</option>
                            <option value="Autre">Autre</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label-sm">Diagnostic principal</label>
                        <input type="text" name="diagnostic" class="form-control-sm-custom" placeholder="Saisir le diagnostic...">
                    </div>
                    <div class="mb-3">
                        <label class="form-label-sm">Taux d'hémoglobine (g/dL)</label>
                        <input type="number" step="0.1" name="taux_hb" class="form-control-sm-custom" placeholder="ex: 6.5">
                    </div>
                    <div>
                        <label class="form-label-sm">Médecin prescripteur</label>
                        <input type="text" name="medecin_prescripteur" class="form-control-sm-custom"
                               value="<?= htmlspecialchars($_SESSION['user_nom'] ?? '') ?>" placeholder="Nom du médecin">
                    </div>
                </div>

                <!-- Consentement & Bilan pré-transfusionnel -->
                <div class="info-block">
                    <h6><i class="bi bi-shield-check me-1"></i> Bilan Pré-Transfusionnel</h6>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label-sm">Groupe sanguin vérifié</label>
                            <select name="groupe_verifie" class="form-select form-select-sm border-0 border-bottom rounded-0 bg-transparent">
                                <option value="">--</option>
                                <option value="A+">A+</option><option value="A-">A-</option>
                                <option value="B+">B+</option><option value="B-">B-</option>
                                <option value="AB+">AB+</option><option value="AB-">AB-</option>
                                <option value="O+">O+</option><option value="O-">O-</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label-sm">Rhésus</label>
                            <select name="rhesus" class="form-select form-select-sm border-0 border-bottom rounded-0 bg-transparent">
                                <option value="">--</option>
                                <option value="Positif">Positif (+)</option>
                                <option value="Négatif">Négatif (-)</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label-sm">N° Bon de compatibilité</label>
                        <input type="text" name="num_compat" class="form-control-sm-custom" placeholder="Référence du bon...">
                    </div>
                    <div class="mb-3">
                        <label class="form-label-sm">Consentement éclairé</label>
                        <select name="consentement" class="form-select form-select-sm border-0 border-bottom rounded-0 bg-transparent">
                            <option value="Oui">Obtenu (Oui)</option>
                            <option value="Non">Non obtenu</option>
                            <option value="Urgence">Urgence — non applicable</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label-sm">RAI (Recherche Agglutinines Irrégulières)</label>
                        <select name="rai" class="form-select form-select-sm border-0 border-bottom rounded-0 bg-transparent">
                            <option value="">--</option>
                            <option value="Négatif">Négatif</option>
                            <option value="Positif">Positif</option>
                            <option value="Non fait">Non réalisé</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- TABLEAU DES TRANSFUSIONS -->
            <h6 class="fw-bold text-uppercase mb-2" style="font-size: 0.78rem; letter-spacing: 1px; color: var(--red-primary);">
                <i class="bi bi-table me-1"></i> Suivi des Poches Transfusées
            </h6>
            <div class="table-container">
                <table class="trans-table" id="transTable">
                    <thead>
                        <tr>
                            <th style="width: 80px;">DATE</th>
                            <th style="width: 65px;">HEURE DÉBUT</th>
                            <th style="width: 65px;">HEURE FIN</th>
                            <th style="width: 100px;">TYPE PRODUIT</th>
                            <th style="width: 80px;">N° POCHE</th>
                            <th style="width: 70px;">GROUPE</th>
                            <th style="width: 70px;">VOLUME (mL)</th>
                            <th style="width: 60px;">T° AVANT (°C)</th>
                            <th style="width: 60px;">TA AVANT</th>
                            <th style="width: 60px;">POULS</th>
                            <th style="width: 60px;">T° APRÈS</th>
                            <th style="width: 60px;">TA APRÈS</th>
                            <th>RÉACTION / INCIDENT</th>
                            <th style="width: 70px;">INFIRMIER</th>
                            <th class="no-print" style="width: 40px;"></th>
                        </tr>
                    </thead>
                    <tbody id="transBody">
                        <tr class="trans-row">
                            <td><input type="text" name="trans[date][]" class="trans-input" value="<?= date('d/m/Y') ?>"></td>
                            <td><input type="time" name="trans[heure_debut][]" class="trans-input text-danger" value="<?= date('H:i') ?>"></td>
                            <td><input type="time" name="trans[heure_fin][]" class="trans-input"></td>
                            <td>
                                <select name="trans[type_produit][]" class="trans-input" style="font-size: 0.75rem;">
                                    <option value="CGR">CGR (Culot GR)</option>
                                    <option value="PFC">PFC (Plasma Frais)</option>
                                    <option value="CPS">CPS (Plaquettes)</option>
                                    <option value="Sang total">Sang total</option>
                                    <option value="Albumine">Albumine</option>
                                    <option value="Autre">Autre</option>
                                </select>
                            </td>
                            <td><input type="text" name="trans[num_poche][]" class="trans-input" placeholder="Réf."></td>
                            <td>
                                <select name="trans[groupe][]" class="trans-input" style="font-size: 0.78rem;">
                                    <option value="">--</option>
                                    <option value="A+">A+</option><option value="A-">A-</option>
                                    <option value="B+">B+</option><option value="B-">B-</option>
                                    <option value="AB+">AB+</option><option value="AB-">AB-</option>
                                    <option value="O+">O+</option><option value="O-">O-</option>
                                </select>
                            </td>
                            <td><input type="number" name="trans[volume][]" class="trans-input" placeholder="250"></td>
                            <td><input type="number" step="0.1" name="trans[temp_avant][]" class="trans-input" placeholder="37.0"></td>
                            <td><input type="text" name="trans[ta_avant][]" class="trans-input" placeholder="120/80"></td>
                            <td><input type="number" name="trans[pouls][]" class="trans-input" placeholder="80"></td>
                            <td><input type="number" step="0.1" name="trans[temp_apres][]" class="trans-input"></td>
                            <td><input type="text" name="trans[ta_apres][]" class="trans-input"></td>
                            <td>
                                <select name="trans[reaction][]" class="trans-input reaction-input">
                                    <option value="Aucune">Aucune réaction</option>
                                    <option value="Frissons">Frissons</option>
                                    <option value="Fièvre">Fièvre</option>
                                    <option value="Urticaire">Urticaire / Prurit</option>
                                    <option value="Dyspnée">Dyspnée</option>
                                    <option value="Choc">Choc anaphylactique</option>
                                    <option value="Hémoglobinurie">Hémoglobinurie</option>
                                    <option value="Autre">Autre incident</option>
                                </select>
                            </td>
                            <td><input type="text" name="trans[infirmier][]" class="trans-input small text-uppercase"
                                       value="<?= htmlspecialchars($_SESSION['user_initiales'] ?? '') ?>"></td>
                            <td class="no-print text-center">
                                <button type="button" class="btn btn-link btn-sm text-danger p-1" onclick="removeRow(this)" title="Supprimer">
                                    <i class="bi bi-trash3-fill"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- OBSERVATIONS FINALES -->
            <div class="obs-section">
                <h6><i class="bi bi-journal-text me-1"></i> Observations Post-Transfusionnelles & Incidents</h6>
                <textarea name="observations_finales" class="obs-textarea" rows="4"
                          placeholder="Décrire tout incident ou réaction survenu pendant ou après la transfusion..."></textarea>
            </div>

            <!-- SIGNATURES -->
            <div class="row mt-4">
                <div class="col-4 text-center">
                    <div style="border-top: 1px solid #000; padding-top: 6px; margin-top: 40px;">
                        <small class="fw-bold text-uppercase" style="font-size: 0.72rem;">Médecin Prescripteur</small>
                    </div>
                </div>
                <div class="col-4 text-center">
                    <div style="border-top: 1px solid #000; padding-top: 6px; margin-top: 40px;">
                        <small class="fw-bold text-uppercase" style="font-size: 0.72rem;">Infirmier(e) Transfuseur</small>
                    </div>
                </div>
                <div class="col-4 text-center">
                    <div style="border-top: 1px solid #000; padding-top: 6px; margin-top: 40px;">
                        <small class="fw-bold text-uppercase" style="font-size: 0.72rem;">Biologiste / Pharmacien</small>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- BOUTON FLOTTANT -->
<div class="action-fab no-print">
    <button type="button" class="btn-fab btn-dark" onclick="addRow()" title="Ajouter une ligne (Alt+N)">
        <i class="bi bi-plus-lg fs-4 text-white"></i>
    </button>
</div>

<script>
function addRow() {
    const body = document.getElementById('transBody');
    const rows = body.getElementsByClassName('trans-row');
    const newRow = rows[0].cloneNode(true);

    // Reset inputs
    newRow.querySelectorAll('input').forEach(input => {
        if (input.name.includes('[heure_debut]')) {
            const now = new Date();
            input.value = now.getHours().toString().padStart(2, '0') + ':' + now.getMinutes().toString().padStart(2, '0');
        } else if (!input.name.includes('[date]') && !input.name.includes('[infirmier]')) {
            input.value = '';
        }
    });
    // Reset selects to first option except type_produit and groupe
    newRow.querySelectorAll('select[name*="[reaction]"]').forEach(sel => { sel.selectedIndex = 0; });

    body.appendChild(newRow);
    newRow.querySelector('input[name="trans[num_poche][]"]').focus();
}

function removeRow(btn) {
    const body = document.getElementById('transBody');
    if (body.rows.length > 1) {
        btn.closest('tr').remove();
    }
}

// Raccourci clavier Alt+N
document.addEventListener('keydown', e => { if (e.altKey && e.key === 'n') addRow(); });
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
