<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<?php
// Variables fournies par FormulaireController::creerBulletinExamens
// Déclarations défensives (l'analyseur statique ne voit pas la portée du contrôleur)
$patient          = $patient          ?? [];
$age              = $age              ?? 0;
$type             = $_GET['type']     ?? ($type ?? 'labo');
$consultation_id  = !empty($_GET['consultation_id'])
                  ? (int)$_GET['consultation_id']
                  : ($consultation_id ?? null);
$examens_demandes       = $examens_demandes ?? [];
$debug_error_imagerie   = $debug_error_imagerie ?? null;

// Détecter les modalités imagerie présentes pour les cases à cocher
$hasRadio = false;
$hasEcho  = false;
if ($type === 'imagerie') {
    foreach ($examens_demandes as $e) {
        $mod = strtolower($e['modalite'] ?? '');
        if (in_array($mod, ['echographie'])) $hasEcho  = true;
        else                                  $hasRadio = true;
    }
    if (!$hasRadio && !$hasEcho) { $hasRadio = true; $hasEcho = true; }
}

$titre_type = $type === 'labo' ? 'LABORATOIRE' : 'IMAGERIE MÉDICALE';
?>

<style>
    .paper-sheet {
        background-color: white;
        width: 14.8cm;
        min-height: 21cm;
        padding: 1cm;
        margin: 20px auto;
        box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        color: #000;
        position: relative;
    }
    .form-dotted {
        border: none;
        border-bottom: 1px dotted #444;
        background: transparent;
        padding: 0 5px;
        outline: none;
        font-weight: 600;
        color: #0d6efd;
    }
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
    .form-title { text-align: center; margin: 25px 0; }
    .form-title h2 {
        font-weight: 900;
        text-decoration: underline;
        text-underline-offset: 8px;
        font-size: 1.4rem;
    }
    .form-title .subtitle {
        font-size: 0.95rem;
        font-weight: 700;
        color: <?= $type === 'labo' ? '#0891b2' : '#7c3aed' ?>;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-top: 4px;
    }
    .patient-info-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
        margin-bottom: 25px;
        line-height: 1.8;
    }
    .main-content-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        border: 2px solid #000;
        min-height: 380px;
    }
    .grid-col { padding: 15px; display: flex; flex-direction: column; }
    .grid-col-left  { border-right: 2px solid #000; background: #fff; }
    .grid-col-right { background: #fdfdfd; }
    .col-header {
        text-align: center;
        font-weight: bold;
        text-decoration: underline;
        margin-bottom: 20px;
        text-transform: uppercase;
        font-size: 0.9rem;
    }
    .exam-input-list { flex-grow: 0; }
    .exam-item { margin-bottom: 12px; display: flex; align-items: center; }
    .exam-badge {
        font-size: 0.65rem;
        padding: 1px 6px;
        border-radius: 10px;
        font-weight: 700;
        margin-left: 6px;
        white-space: nowrap;
    }
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
    .action-bar {
        position: sticky;
        top: 0;
        z-index: 1000;
        background: rgba(255,255,255,0.9);
        backdrop-filter: blur(5px);
    }
    @media print {
        @page { size: A5 portrait; margin: 0; }
        .no-print, .action-bar { display: none !important; }
        .paper-sheet { margin: 0; box-shadow: none; width: 100%; padding: 8mm 10mm; }
        body { background: white; }
        .form-dotted { color: black; border-bottom: 1px solid #000; }
        .clinical-note, .result-area { border-color: #000; }
    }
</style>

<div class="container-fluid bg-light pb-5">

    <!-- BARRE D'ACTIONS -->
    <div class="action-bar py-3 px-4 border-bottom shadow-sm no-print">
        <div class="d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-3">
                <?php if ($consultation_id): ?>
                <a href="<?= BASE_URL ?>consultation/recapitulatif/<?= $consultation_id ?>"
                   class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left"></i> Retour au Récapitulatif
                </a>
                <?php else: ?>
                <a href="<?= BASE_URL ?>patients/dossier/<?= $patient['id'] ?>"
                   class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left"></i> Retour au Dossier
                </a>
                <?php endif; ?>
                <span class="badge rounded-pill px-3 py-2"
                      style="background:<?= $type === 'labo' ? '#0891b2' : '#7c3aed' ?>;font-size:.8rem;">
                    <?= $titre_type ?>
                    <?= count($examens_demandes) > 0
                        ? '— ' . count($examens_demandes) . ' examen' . (count($examens_demandes) > 1 ? 's' : '')
                        : '' ?>
                </span>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-primary" onclick="window.print()">
                    <i class="bi bi-printer me-2"></i> Imprimer
                </button>
                <button type="submit" form="formBulletin" class="btn btn-success px-4 fw-bold">
                    <i class="bi bi-check2-circle me-2"></i> Enregistrer &amp; Signer
                </button>
            </div>
        </div>
    </div>

    <!-- FEUILLE DE SOIN (format A4) -->
    <div class="paper-sheet">
        <form id="formBulletin"
              action="<?= BASE_URL ?>formulaire/sauvegarder/bulletin-examens"
              method="POST">

            <input type="hidden" name="patient_id"      value="<?= (int)$patient['id'] ?>">
            <input type="hidden" name="consultation_id" value="<?= (int)$consultation_id ?>">
            <input type="hidden" name="type_bulletin"   value="<?= htmlspecialchars($type) ?>">

            <!-- EN-TÊTE HÔPITAL -->
            <div class="hospital-header">
                <div class="logo-area">
                    <img src="<?= BASE_URL ?>public/images/logo_ordre_malte.png" alt="Logo" onerror="this.style.display='none'">
                    <div class="hospital-name">
                        <h5>ORDRE DE MALTE</h5>
                        <div class="small fw-bold">HÔPITAL SAINT-JEAN DE MALTE</div>
                        <div class="small text-muted">B.P.: 56 NJOMBE — Tél.: (237) 697 09 29 92</div>
                    </div>
                </div>

                <!-- Cases à cocher département -->
                <div class="dept-checkboxes">
                    <div class="form-check">
                        <input type="checkbox" name="radio" class="form-check-input" id="checkRadio"
                               <?= $hasRadio ? 'checked' : '' ?>>
                        <label class="form-check-label fw-bold" for="checkRadio">RADIOLOGIE</label>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="echo" class="form-check-input" id="checkEcho"
                               <?= $hasEcho ? 'checked' : '' ?>>
                        <label class="form-check-label fw-bold" for="checkEcho">ECHOGRAPHIE</label>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="labo" class="form-check-input" id="checkLabo"
                               <?= $type === 'labo' ? 'checked' : '' ?>>
                        <label class="form-check-label fw-bold" for="checkLabo">LABORATOIRE</label>
                    </div>
                </div>
            </div>

            <!-- TITRE -->
            <div class="form-title">
                <h2>BULLETIN D'EXAMENS</h2>
                <div class="subtitle"><?= $titre_type ?></div>
            </div>

            <!-- INFO PATIENT -->
            <div class="patient-info-grid">
                <div>
                    Nom et Prénom :
                    <input type="text" class="form-dotted" style="width:70%;"
                           value="<?= htmlspecialchars($patient['nom'] . ' ' . $patient['prenom']) ?>"
                           readonly>
                </div>
                <div>
                    Profession :
                    <input type="text" name="profession" class="form-dotted"
                           style="width:70%;" placeholder="Cliquer pour saisir...">
                </div>
                <div>
                    Âge :
                    <input type="text" class="form-dotted" style="width:50px;"
                           value="<?= $age ?>" readonly> ans
                    &nbsp;&nbsp; Sexe :
                    <input type="text" class="form-dotted" style="width:100px;"
                           value="<?= $patient['sexe'] == 'M' ? 'Masculin' : 'Féminin' ?>"
                           readonly>
                </div>
                <div>
                    Service :
                    <input type="text" name="service" class="form-dotted"
                           style="width:40%;" value="Consultation Extr.">
                    Chambre :
                    <input type="text" name="chambre" class="form-dotted" style="width:50px;">
                    Lit :
                    <input type="text" name="lit" class="form-dotted" style="width:50px;">
                </div>
            </div>

            <!-- GRILLE PRINCIPALE -->
            <div class="main-content-grid">

                <!-- DEBUG TEMPORAIRE — À SUPPRIMER APRÈS CORRECTION -->
                <?php if (!empty($debug_error_imagerie)): ?>
                <div style="background:#fee2e2;color:#991b1b;padding:10px;margin-bottom:10px;border-radius:8px;font-size:.8rem;font-family:monospace;">
                    ⚠️ Erreur SQL : <?= htmlspecialchars($debug_error_imagerie) ?>
                </div>
                <?php endif; ?>
                <?php if ($type === 'imagerie' && defined('DEBUG_MODE') && DEBUG_MODE): ?>
                <div style="background:#dbeafe;color:#1e40af;padding:10px;margin-bottom:10px;border-radius:8px;font-size:.75rem;font-family:monospace;">
                    consultation_id=<?= $consultation_id ?> | examens trouvés=<?= count($examens_demandes) ?>
                    <?php foreach($examens_demandes as $e): ?>
                    <br>→ <?= htmlspecialchars(json_encode($e)) ?>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                <!-- FIN DEBUG -->

                <!-- COLONNE GAUCHE : Examens demandés (médecin) -->
                <div class="grid-col grid-col-left">
                    <div class="col-header">Examens demandés</div>

                    <div class="exam-input-list">
                        <?php
                        $examIndex = 0;
                        foreach ($examens_demandes as $examen):
                            if ($examIndex >= 10) break;
                            // 'urgent' (labo) ou 'urgence' (imagerie : URGENT / TRES_URGENT)
                            $isUrgent = !empty($examen['urgent']) && $examen['urgent']
                                     || in_array(strtoupper($examen['urgence'] ?? ''), ['URGENT', 'TRES_URGENT']);
                        ?>
                        <div class="exam-item">
                            <span class="text-muted small me-2"><?= $examIndex + 1 ?>.</span>
                            <input type="text" name="examens[]" class="form-dotted"
                                   style="flex:1;"
                                   value="<?= htmlspecialchars($examen['nom_examen'] ?? '') ?>">
                            <?php if ($isUrgent): ?>
                            <span class="exam-badge" style="background:#fee2e2;color:#991b1b;">URGENT</span>
                            <?php endif; ?>
                            <?php if (!empty($examen['a_jeun'])): ?>
                            <span class="exam-badge" style="background:#fef3c7;color:#92400e;">À jeun</span>
                            <?php endif; ?>
                        </div>
                        <?php
                            $examIndex++;
                        endforeach;

                        // Compléter jusqu'à 10 lignes
                        for ($i = $examIndex; $i < 10; $i++):
                        ?>
                        <div class="exam-item">
                            <span class="text-muted small me-2"><?= $i + 1 ?>.</span>
                            <input type="text" name="examens[]" class="form-dotted w-100">
                        </div>
                        <?php endfor; ?>
                    </div>

                    <div class="mt-auto">
                        <label class="fw-bold small">Renseignements cliniques :</label>
                        <textarea name="renseignements" class="form-control clinical-note" rows="4"
                                  placeholder="Observations cliniques, hypothèse diagnostique..."></textarea>

                        <div class="signature-box">
                            <div class="d-flex justify-content-between align-items-end">
                                <div class="small">
                                    Date :
                                    <input type="text" class="form-dotted"
                                           value="<?= date('d/m/Y') ?>"
                                           style="width:90px;" readonly>
                                </div>
                                <div class="text-center small">
                                    <div class="mb-4">Signature &amp; Cachet</div>
                                    <div class="text-muted" style="font-size:0.7rem;">
                                        Dr. <?= htmlspecialchars(trim(($_SESSION['user_prenom'] ?? '') . ' ' . ($_SESSION['user_nom'] ?? 'Médecin'))) ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div><!-- /col-left -->

                <!-- COLONNE DROITE : Résultats (labo/radio) -->
                <div class="grid-col grid-col-right">
                    <div class="col-header">Résultats / Compte-rendu</div>

                    <textarea name="resultats" class="form-control result-area"
                              placeholder="Zone réservée au laboratoire / service d'imagerie pour les résultats..."></textarea>

                    <div class="signature-box mt-auto">
                        <div class="d-flex justify-content-between align-items-end">
                            <div class="small">
                                Date :
                                <input type="text" class="form-dotted"
                                       placeholder="../../...." style="width:90px;">
                            </div>
                            <div class="text-center small">
                                <div class="mb-4">Signature &amp; Cachet</div>
                                <div style="height:15px;"></div>
                            </div>
                        </div>
                    </div>
                </div><!-- /col-right -->

            </div><!-- /main-content-grid -->

            <div class="mt-4 text-center text-muted" style="font-size:0.7rem;">
                SimCare+ — Système de Gestion de l'Hôpital Saint-Jean de Malte
            </div>
        </form>
    </div><!-- /paper-sheet -->
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
