<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<style>
    :root {
        --bloc-primary: #2c3e50;
        --bloc-success: #198754;
        --bloc-danger: #dc3545;
        --bloc-bg: #f4f7f6;
    }

    body { background-color: var(--bloc-bg); font-family: 'Inter', sans-serif; }

    .paper-sheet {
        background: white;
        width: 100%;
        max-width: 950px;
        margin: 20px auto;
        padding: 40px;
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        position: relative;
    }

    .header-logo { height: 60px; }

    .main-title {
        text-align: center;
        border-bottom: 3px solid var(--bloc-primary);
        margin: 20px 0 30px;
        padding-bottom: 10px;
        text-transform: uppercase;
        font-weight: 900;
        letter-spacing: 2px;
    }

    .section-box {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
        border-left: 5px solid var(--bloc-primary);
    }

    .section-title {
        font-weight: bold;
        color: var(--bloc-primary);
        margin-bottom: 15px;
        border-bottom: 1px solid #dee2e6;
        padding-bottom: 5px;
    }

    /* Style des options OUI/NON style bouton radio moderne */
    .on-toggle {
        display: flex;
        gap: 10px;
    }
    .btn-check:checked + .btn-outline-success { background-color: var(--bloc-success); color: white; }
    .btn-check:checked + .btn-outline-danger { background-color: var(--bloc-danger); color: white; }

    /* Grille des bilans */
    .bilan-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
        gap: 10px;
        background: white;
        padding: 15px;
        border-radius: 8px;
        border: 1px solid #eee;
    }

    .bilan-item {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 0.85rem;
        font-weight: 600;
    }

    .form-dotted {
        border: none;
        border-bottom: 1px dotted #000;
        background: transparent;
        font-weight: bold;
        color: #0d6efd;
        outline: none;
    }

    @media print {
        .no-print { display: none !important; }
        .paper-sheet { box-shadow: none; margin: 0; width: 100%; padding: 0; }
        .section-box { border: 1px solid #000; background: none; }
        body { background: white; }
    }
</style>

<div class="container-fluid pb-5">
    <!-- Barre d'outils -->
    <div class="d-flex justify-content-between align-items-center py-3 px-4 bg-white border-bottom shadow-sm no-print sticky-top mb-3">
        <a href="<?= BASE_URL ?>patients/dossier/<?= $patient['id'] ?>" class="btn btn-outline-secondary btn-sm rounded-pill">
            <i class="bi bi-arrow-left"></i> Retour au dossier
        </a>
        <div class="d-flex gap-2">
            <button class="btn btn-primary btn-sm rounded-pill px-3" onclick="window.print()"><i class="bi bi-printer"></i> Imprimer</button>
            <button type="submit" form="formBloc" class="btn btn-success btn-sm rounded-pill px-4 shadow">
                <i class="bi bi-shield-check"></i> Valider la Check-list
            </button>
        </div>
    </div>

    <div class="paper-sheet">
        <form id="formBloc" action="<?= BASE_URL ?>formulaire/sauvegarder/checklist-bloc" method="POST">
            <input type="hidden" name="patient_id" value="<?= $patient['id'] ?>">

            <div class="hospital-brand d-flex justify-content-between align-items-center">
                <img src="<?= BASE_URL ?>public/images/logo_ordre_malte.png" class="header-logo" alt="">
                <div class="text-end small fw-bold">
                    HÔPITAL SAINT-JEAN DE MALTE - NJOMBÉ<br>
                    BLOC OPÉRATOIRE
                </div>
            </div>

            <h2 class="main-title">ENTRÉE AU BLOC : CHECK LIST</h2>

            <!-- 1. IDENTIFICATION -->
            <div class="section-box">
                <div class="section-title">1. Identification du malade</div>
                <div class="row g-3">
                    <div class="col-md-12">
                        <strong>Noms Prénoms :</strong> <span class="text-primary fw-bold ms-2"><?= htmlspecialchars($patient['nom'].' '.$patient['prenom']) ?></span>
                    </div>
                    <div class="col-md-4">
                        <strong>Age :</strong> <span class="ms-2"><?= $age ?> ans</span>
                    </div>
                    <div class="col-md-4">
                        <strong>Sexe :</strong> <span class="ms-2"><?= $patient['sexe'] ?></span>
                    </div>
                    <div class="col-md-12 mt-2">
                        <strong>Diagnostic :</strong> <input type="text" name="diagnostic" class="form-dotted w-75" placeholder="...">
                    </div>
                </div>
            </div>

            <!-- 2. MODALITES PRATIQUES -->
            <div class="section-box">
                <div class="section-title">2. Modalités pratiques pour transfert au bloc</div>
                <table class="table table-borderless table-sm align-middle">
                    <tr>
                        <td style="width: 70%;">Présence du dossier médical complet</td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <input type="radio" class="btn-check" name="check_dossier" id="dos_o" value="O">
                                <label class="btn btn-outline-success" for="dos_o">OUI</label>
                                <input type="radio" class="btn-check" name="check_dossier" id="dos_n" value="N" checked>
                                <label class="btn btn-outline-danger" for="dos_n">NON</label>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>Service du bloc informé</td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <input type="radio" class="btn-check" name="check_informe" id="inf_o" value="O">
                                <label class="btn btn-outline-success" for="inf_o">OUI</label>
                                <input type="radio" class="btn-check" name="check_informe" id="inf_n" value="N" checked>
                                <label class="btn btn-outline-danger" for="inf_n">NON</label>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>Type de chirurgie</td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <input type="radio" class="btn-check" name="type_chir" id="chir_p" value="propre">
                                <label class="btn btn-outline-primary" for="chir_p">PROPRE</label>
                                <input type="radio" class="btn-check" name="type_chir" id="chir_s" value="sale">
                                <label class="btn btn-outline-warning" for="chir_s">SALE</label>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- 3. PREPARATION -->
            <div class="section-box">
                <div class="section-title">3. Préparation des opérés</div>
                <div class="row">
                    <div class="col-md-6 border-end">
                        <?php
                        $preps = [
                            'consentement' => 'Consentement éclairé signé',
                            'consult_anesth' => 'Consultation pré anesthésique',
                            'visite_anesth' => 'Visite pré anesthésique',
                            'a_jeun' => 'Patient à jeun (6h minimum)',
                            'cathe_vesical' => 'Cathétérisme vésical effectué',
                            'toilette' => 'Toilette du malade faite',
                            'site_op' => 'Préparation du site opératoire'
                        ];
                        foreach($preps as $key => $label): ?>
                        <div class="d-flex justify-content-between mb-2 pe-3">
                            <small><?= $label ?></small>
                            <div class="btn-group btn-group-sm" style="transform: scale(0.85);">
                                <input type="radio" class="btn-check" name="<?= $key ?>" id="<?= $key ?>_o" value="O">
                                <label class="btn btn-outline-success" for="<?= $key ?>_o">O</label>
                                <input type="radio" class="btn-check" name="<?= $key ?>" id="<?= $key ?>_n" value="N" checked>
                                <label class="btn btn-outline-danger" for="<?= $key ?>_n">N</label>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="col-md-6 ps-3">
                        <p class="fw-bold small mb-1">Préparation colique :</p>
                        <div class="sub-section ps-3">
                            <?php
                            $coliques = [
                                'lavemenent' => 'Lavement évacuateur',
                                'sng_irrig' => 'Irrigation via SNG',
                                'x_prep' => 'X-Prep'
                            ];
                            foreach($coliques as $key => $label): ?>
                            <div class="d-flex justify-content-between mb-2">
                                <small><?= $label ?></small>
                                <div class="btn-group btn-group-sm" style="transform: scale(0.8);">
                                    <input type="radio" class="btn-check" name="<?= $key ?>" id="<?= $key ?>_o" value="O">
                                    <label class="btn btn-outline-success" for="<?= $key ?>_o">O</label>
                                    <input type="radio" class="btn-check" name="<?= $key ?>" id="<?= $key ?>_n" value="N" checked>
                                    <label class="btn btn-outline-danger" for="<?= $key ?>_n">N</label>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 4. BILANS -->
            <div class="section-box">
                <div class="section-title">4. Bilans pré opératoires</div>
                <div class="bilan-grid">
                    <?php
                    $bilans = ['NFS', 'TP', 'TCK', 'GS-Rh', 'Urée', 'Créat', 'LAV', 'Ionogramme', 'Radiographies', 'Echographie', 'TDM'];
                    foreach($bilans as $b): ?>
                    <div class="bilan-item">
                        <input type="checkbox" name="bilans[]" value="<?= $b ?>" class="form-check-input">
                        <span><?= $b ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="mt-3 d-flex align-items-center">
                    <span class="me-3 fw-bold">Poches de sang :</span>
                    <div class="btn-group btn-group-sm">
                        <input type="radio" class="btn-check" name="poches" id="p_o" value="O">
                        <label class="btn btn-outline-success" for="p_o">DISPONIBLES</label>
                        <input type="radio" class="btn-check" name="poches" id="p_n" value="N" checked>
                        <label class="btn btn-outline-danger" for="p_n">NÉANT</label>
                    </div>
                </div>
            </div>

            <!-- 5. PARAMETRES -->
            <div class="section-box">
                <div class="section-title">5. Paramètres à l'entrée</div>
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="small fw-bold">T.A (mmHg)</label>
                        <input type="text" name="param_ta" class="form-control form-control-sm" placeholder="12/8">
                    </div>
                    <div class="col-md-3">
                        <label class="small fw-bold">Pouls (bpm)</label>
                        <input type="number" name="param_pouls" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-3">
                        <label class="small fw-bold">Température (°C)</label>
                        <input type="text" name="param_temp" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-3">
                        <label class="small fw-bold">Poids (kg)</label>
                        <input type="number" name="param_poids" class="form-control form-control-sm">
                    </div>
                </div>
            </div>

            <div class="mt-4 d-flex justify-content-between">
                <div class="text-muted small">Check-list Bloc opératoire - Hôpital Saint-Jean de Malte</div>
                <div class="text-center" style="width: 250px; border-top: 1px solid #000; padding-top: 5px;">
                    <strong>Signature Major/Infirmier</strong>
                </div>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>