<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<style>
    /* Configuration de la page type "Papier" */
    .paper-sheet {
        background-color: white;
        width: 21cm;
        min-height: 27cm;
        padding: 1.5cm;
        margin: 20px auto;
        box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        color: #000;
        position: relative;
    }

    /* Style des entrées en pointillés (effet papier) */
    .form-dotted {
        border: none;
        border-bottom: 1px dotted #444;
        background: transparent;
        padding: 0 5px;
        outline: none;
        font-weight: 600;
        color: #0d6efd; /* Bleu pour distinguer la saisie */
    }

    /* En-tête */
    .hospital-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        border-bottom: 2px solid #333;
        padding-bottom: 15px;
    }
    .logo-area { display: flex; align-items: center; gap: 15px; }
    .logo-area img { height: 75px; }
    .hospital-name h5 { font-weight: 800; margin: 0; color: #333; letter-spacing: 1px; }

    .dept-checkboxes {
        border: 1.5px solid #000;
        padding: 10px;
        border-radius: 4px;
        font-size: 0.85rem;
        background: #f8f9fa;
    }

    /* Titre */
    .form-title {
        text-align: center;
        margin: 25px 0;
    }
    .form-title h2 {
        font-weight: 900;
        text-decoration: underline;
        text-underline-offset: 8px;
        font-size: 2rem;
    }

    /* Bloc Info Patient */
    .patient-info-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
        margin-bottom: 25px;
        line-height: 1.8;
    }

    /* Grille de contenu (2 colonnes) */
    .main-content-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        border: 2px solid #000;
        min-height: 650px;
    }
    .grid-col {
        padding: 15px;
        display: flex;
        flex-direction: column;
    }
    .grid-col-left { border-right: 2px solid #000; background: #fff; }
    .grid-col-right { background: #fdfdfd; }

    .col-header {
        text-align: center;
        font-weight: bold;
        text-decoration: underline;
        margin-bottom: 20px;
        text-transform: uppercase;
        font-size: 0.9rem;
    }

    /* Zones de saisie */
    .exam-input-list { flex-grow: 0; }
    .exam-item { margin-bottom: 12px; display: flex; align-items: center; }
    .clinical-note {
        border: 1px solid #ced4da;
        border-radius: 4px;
        padding: 10px;
        flex-grow: 1;
        font-size: 0.9rem;
        margin-top: 10px;
    }
    .result-area {
        border: 1px dashed #6c757d;
        flex-grow: 1;
        padding: 15px;
        background: white;
        font-family: 'Courier New', Courier, monospace;
    }

    .signature-box {
        margin-top: 20px;
        padding-top: 10px;
        border-top: 1px solid #eee;
    }

    /* Boutons flottants en haut */
    .action-bar {
        position: sticky;
        top: 0;
        z-index: 1000;
        background: rgba(255,255,255,0.9);
        backdrop-filter: blur(5px);
    }

    @media print {
        .no-print, .action-bar { display: none !important; }
        .paper-sheet { margin: 0; box-shadow: none; width: 100%; padding: 1cm; }
        body { background: white; }
        .form-dotted { color: black; border-bottom: 1px solid #000; }
        .clinical-note, .result-area { border-color: #000; }
    }
</style>

<div class="container-fluid bg-light pb-5">

    <!-- BARRE D'ACTIONS -->
    <div class="action-bar py-3 px-4 border-bottom shadow-sm">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <a href="<?= BASE_URL ?>patients/dossier/<?= $patient['id'] ?>" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left"></i> Retour au Dossier
                </a>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-primary" onclick="window.print()">
                    <i class="bi bi-printer me-2"></i> Imprimer le Bulletin
                </button>
                <button type="submit" form="formBulletin" class="btn btn-success px-4">
                    <i class="bi bi-check2-circle me-2"></i> Enregistrer
                </button>
            </div>
        </div>
    </div>

    <!-- FEUILLE DE SOIN -->
    <div class="paper-sheet">
        <form id="formBulletin" action="<?= BASE_URL ?>formulaire/sauvegarder/bulletin-examens" method="POST">
            <input type="hidden" name="patient_id" value="<?= $patient['id'] ?>">

            <!-- EN-TÊTE -->
            <div class="hospital-header">
                <div class="logo-area">
                    <!-- Assurez-vous que le chemin du logo est correct -->
                    <img src="<?= BASE_URL ?>public/images/logo_ordre_malte.png" alt="Logo">
                    <div class="hospital-name">
                        <h5>ORDRE DE MALTE</h5>
                        <div class="small fw-bold">HÔPITAL SAINT-JEAN DE MALTE</div>
                        <div class="small text-muted">B.P.: 56 NJOMBE Tél.: (237) 697 09 29 92</div>
                    </div>
                </div>
                <div class="dept-checkboxes">
                    <div class="form-check">
                        <input type="checkbox" name="radio" class="form-check-input" id="checkRadio">
                        <label class="form-check-label fw-bold" for="checkRadio">RADIOLOGIE</label>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="echo" class="form-check-input" id="checkEcho">
                        <label class="form-check-label fw-bold" for="checkEcho">ECHOGRAPHIE</label>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="labo" class="form-check-input" id="checkLabo">
                        <label class="form-check-label fw-bold" for="checkLabo">LABORATOIRE</label>
                    </div>
                </div>
            </div>

            <div class="form-title">
                <h2>BULLETIN D'EXAMENS</h2>
            </div>

            <!-- INFO PATIENT -->
            <div class="patient-info-grid">
                <div>
                    Nom et Prénom : <input type="text" class="form-dotted" style="width: 70%;" value="<?= htmlspecialchars($patient['nom'].' '.$patient['prenom']) ?>" readonly>
                </div>
                <div>
                    Profession : <input type="text" name="profession" class="form-dotted" style="width: 70%;" placeholder="Cliquer pour saisir...">
                </div>
                <div>
                    Âge : <input type="text" class="form-dotted" style="width: 50px;" value="<?= $age ?>" readonly> ans
                    &nbsp;&nbsp; Sexe : <input type="text" class="form-dotted" style="width: 100px;" value="<?= $patient['sexe'] == 'M' ? 'Masculin' : 'Féminin' ?>" readonly>
                </div>
                <div>
                    Service : <input type="text" name="service" class="form-dotted" style="width: 40%;" value="Consultation Extr.">
                    Chambre : <input type="text" name="chambre" class="form-dotted" style="width: 50px;">
                    Lit : <input type="text" name="lit" class="form-dotted" style="width: 50px;">
                </div>
            </div>

            <!-- GRILLE PRINCIPALE -->
            <div class="main-content-grid">

                <!-- COLONNE GAUCHE (MÉDECIN) -->
                <div class="grid-col grid-col-left">
                    <div class="col-header">Type d'examens demandés</div>

                    <div class="exam-input-list">
                        <?php for($i=1; $i<=10; $i++): ?>
                        <div class="exam-item">
                            <span class="text-muted small me-2"><?= $i ?>.</span>
                            <input type="text" name="examens[]" class="form-dotted w-100">
                        </div>
                        <?php endfor; ?>
                    </div>

                    <div class="mt-auto">
                        <label class="fw-bold small">Renseignements cliniques :</label>
                        <textarea name="renseignements" class="form-control clinical-note" rows="4" placeholder="Saisir les observations cliniques..."></textarea>

                        <div class="signature-box">
                            <div class="d-flex justify-content-between align-items-end">
                                <div class="small">Date: <input type="text" class="form-dotted" value="<?= date('d/m/Y') ?>" style="width: 90px;"></div>
                                <div class="text-center small">
                                    <div class="mb-4">Signature</div>
                                    <div class="text-muted" style="font-size: 0.7rem;">Dr. <?= $_SESSION['user_nom'] ?? 'Nom du Médecin' ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- COLONNE DROITE (LABO / RADIO) -->
                <div class="grid-col grid-col-right">
                    <div class="col-header">Résultats / Intervention</div>

                    <textarea name="resultats" class="form-control result-area" placeholder="Zone réservée au compte-rendu des résultats..."></textarea>

                    <div class="signature-box mt-auto">
                        <div class="d-flex justify-content-between align-items-end">
                            <div class="small">Date: <input type="text" class="form-dotted" placeholder="../../...." style="width: 90px;"></div>
                            <div class="text-center small">
                                <div class="mb-4">Signature</div>
                                <div style="height: 15px;"></div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <div class="mt-4 text-center text-muted" style="font-size: 0.7rem;">
                DME Hospital - Système de Gestion de l'Hôpital Saint-Jean de Malte
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>