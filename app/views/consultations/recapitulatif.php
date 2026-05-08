<?php
require_once __DIR__ . '/../layouts/header.php';

$consultation         = $consultation         ?? [];
$patient              = $patient              ?? [];
$antecedents          = $antecedents          ?? [];
$examens_list         = $examens_list         ?? [];
$prescription         = $prescription         ?? null;
$medicaments_prescrits = $medicaments_prescrits ?? [];

$age = !empty($patient['date_naissance'])
    ? date_diff(date_create($patient['date_naissance']), date_create('today'))->y . ' ans'
    : 'N/A';

$medecin = trim(($consultation['medecin_prenom'] ?? '') . ' ' . ($consultation['medecin_nom'] ?? ''));

// helper : afficher une valeur ou un tiret
$val = fn($v) => !empty(trim((string)$v)) ? htmlspecialchars($v) : '<span class="text-muted fst-italic">—</span>';
?>

<style>
    :root { --prim: #1a4a8e; --ok: #10b981; --warn: #f59e0b; --danger: #ef4444; --bg: #f4f7f9; }
    body { background: var(--bg); }
    .sidebar { display: none !important; }
    main, .col-md-10, .ms-sm-auto { margin-left: 0 !important; width: 100% !important;
        flex: 0 0 100% !important; max-width: 100% !important; padding: 0 !important; }

    /* Barre d'action fixe */
    .top-bar { background: white; border-bottom: 1px solid #e2e8f0; padding: 10px 0;
        position: sticky; top: 0; z-index: 1000; box-shadow: 0 2px 10px rgba(0,0,0,0.04); }

    .recap-page { max-width: 1050px; margin: 0 auto; padding: 24px 20px; }

    /* Cartes section */
    .r-card { background: white; border-radius: 18px; border: 1px solid #e2e8f0;
        box-shadow: 0 4px 15px rgba(0,0,0,0.03); margin-bottom: 22px; overflow: hidden; }
    .r-card-header { padding: 13px 22px; font-weight: 800; font-size: 0.72rem;
        text-transform: uppercase; letter-spacing: 1px; display: flex; align-items: center; gap: 9px;
        background: #f8fafc; border-bottom: 1px solid #e2e8f0; }
    .r-card-body { padding: 20px 22px; }

    /* Paramètres vitaux */
    .vital-pill { background: white; border-radius: 14px; padding: 14px 10px; text-align: center;
        border: 1px solid #e2e8f0; }
    .vital-value { font-size: 1.15rem; font-weight: 800; color: #1e293b; display: block; }
    .vital-label { font-size: 0.62rem; font-weight: 700; color: #64748b; text-transform: uppercase; }

    /* Médicaments */
    .med-row { border-bottom: 1px solid #f0f4f8; padding: 12px 0; display: flex; gap: 14px; align-items: flex-start; }
    .med-row:last-child { border-bottom: none; }
    .med-num { width: 26px; height: 26px; background: var(--prim); color: white; border-radius: 50%;
        display: flex; align-items: center; justify-content: center; font-size: 0.7rem; font-weight: 800; flex-shrink: 0; margin-top: 2px; }
    .med-name { font-weight: 700; font-size: 0.9rem; color: #1e293b; }
    .med-detail { font-size: 0.8rem; color: #64748b; margin-top: 3px; }

    /* Examen badge */
    .exam-row { display: flex; justify-content: space-between; align-items: center;
        padding: 9px 0; border-bottom: 1px solid #f0f4f8; font-size: 0.85rem; }
    .exam-row:last-child { border-bottom: none; }

    /* Antécédents */
    .atcd-tag { display: inline-block; background: #f1f5f9; color: #475569;
        border-radius: 8px; padding: 3px 10px; font-size: 0.78rem; margin: 3px 3px 3px 0; }

    /* Orientation */
    .decision-box { border-radius: 12px; padding: 18px; border-left: 5px solid transparent; }
    .decision-hospitalisation_urgente      { background: #fff5f5; border-color: var(--danger); }
    .decision-hospitalisation_recommandee  { background: #fffaf0; border-color: var(--warn); }
    .decision-suivi_ambulatoire            { background: #f0fff4; border-color: var(--ok); }

    @media print {
        .no-print { display: none !important; }
        .top-bar  { display: none !important; }
        .recap-page { max-width: 100%; padding: 0; }
        .r-card { box-shadow: none !important; page-break-inside: avoid; }
    }
</style>

<!-- BARRE D'ACTION -->
<div class="top-bar no-print">
    <div class="container d-flex justify-content-between align-items-center" style="max-width:1050px;">
        <div class="d-flex align-items-center gap-3">
            <img src="<?= BASE_URL ?>public/images/simcare_plus_logo.svg" style="height:32px;width:auto;" alt="">
            <div>
                <h6 class="mb-0 fw-bold text-dark">Synthèse de Consultation</h6>
                <small class="text-muted">Dr <?= htmlspecialchars($medecin) ?> &bull; <?= date('d/m/Y', strtotime($consultation['date_consultation'])) ?></small>
            </div>
        </div>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                <i class="bi bi-printer me-1"></i>Imprimer
            </button>
            <?php if (!empty($prescription)): ?>
            <a href="<?= BASE_URL ?>consultation/imprimer-ordonnance/<?= $prescription['id'] ?>"
               class="btn btn-outline-primary btn-sm rounded-pill px-3" target="_blank">
                <i class="bi bi-file-medical me-1"></i>Ordonnance
            </a>
            <?php endif; ?>
            <a href="<?= BASE_URL ?>consultation/cloturer/<?= $consultation['id'] ?>"
               class="btn btn-success btn-sm rounded-pill px-4 fw-bold shadow">
                <i class="bi bi-check-lg me-1"></i>TERMINER
            </a>
        </div>
    </div>
</div>

<div class="recap-page">

    <!-- ══ BANDEAU PATIENT ══════════════════════════════════════════ -->
    <div class="r-card mb-4" style="border-left: 6px solid var(--prim);">
        <div class="r-card-body">
            <div class="row align-items-center">
                <div class="col-sm-8">
                    <small class="text-muted fw-bold" style="font-size:.7rem;">
                        DOSSIER <?= htmlspecialchars($patient['dossier_numero'] ?? '') ?>
                    </small>
                    <h3 class="fw-bold text-dark mb-1">
                        <?= htmlspecialchars(strtoupper($patient['nom'] ?? '') . ' ' . ($patient['prenom'] ?? '')) ?>
                    </h3>
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <span class="badge rounded-pill <?= ($consultation['type_consultation'] ?? '') === 'interne' ? 'bg-warning text-dark' : 'bg-info text-white' ?>">
                            <?= ($consultation['type_consultation'] ?? '') === 'interne' ? 'Hospitalisé' : 'Ambulatoire' ?>
                        </span>
                        <span class="text-muted small"><i class="bi bi-person me-1"></i><?= $age ?> &bull; <?= $patient['sexe'] === 'F' ? 'Féminin' : 'Masculin' ?></span>
                        <?php if (!empty($consultation['diagnostic_principal'])): ?>
                        <span class="fw-bold text-danger small"><i class="bi bi-bullseye me-1"></i><?= htmlspecialchars($consultation['diagnostic_principal']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-sm-4 text-md-end mt-3 mt-md-0">
                    <div class="text-muted small">Consultation du</div>
                    <div class="fw-bold"><?= date('d/m/Y à H:i', strtotime($consultation['date_consultation'])) ?></div>
                    <div class="text-muted small mt-1"><i class="bi bi-person-badge me-1"></i>Dr <?= htmlspecialchars($medecin) ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- ══ PARAMÈTRES VITAUX ════════════════════════════════════════ -->
    <?php
    $hasVitals = !empty($consultation['temperature']) || !empty($consultation['tension_arterielle'])
              || !empty($consultation['frequence_cardiaque']) || !empty($consultation['poids']);
    ?>
    <?php if ($hasVitals): ?>
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="vital-pill">
                <span class="vital-label">Température</span>
                <span class="vital-value text-danger"><?= !empty($consultation['temperature']) ? $consultation['temperature'] . '°C' : '--' ?></span>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="vital-pill">
                <span class="vital-label">Tension</span>
                <span class="vital-value text-primary"><?= htmlspecialchars($consultation['tension_arterielle'] ?? '--/--') ?></span>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="vital-pill">
                <span class="vital-label">Fréq. Cardiaque</span>
                <span class="vital-value text-success"><?= !empty($consultation['frequence_cardiaque']) ? $consultation['frequence_cardiaque'] . ' bpm' : '--' ?></span>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="vital-pill">
                <span class="vital-label">Poids</span>
                <span class="vital-value text-dark"><?= !empty($consultation['poids']) ? $consultation['poids'] . ' kg' : '--' ?></span>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="row g-4">

        <!-- ═══ COLONNE GAUCHE ═══════════════════════════════════════ -->
        <div class="col-lg-6">

            <!-- 1. ANAMNÈSE -->
            <div class="r-card">
                <div class="r-card-header text-info">
                    <i class="bi bi-chat-left-text-fill"></i> Anamnèse
                </div>
                <div class="r-card-body">
                    <?php if (!empty($consultation['motif_consultation'])): ?>
                    <div class="mb-3">
                        <div class="text-muted small fw-bold text-uppercase mb-1" style="font-size:.65rem;">Motif de consultation</div>
                        <p class="mb-0 fw-bold"><?= htmlspecialchars($consultation['motif_consultation']) ?></p>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($consultation['histoire_maladie'])): ?>
                    <div class="mb-3">
                        <div class="text-muted small fw-bold text-uppercase mb-1" style="font-size:.65rem;">Histoire de la maladie</div>
                        <p class="mb-0"><?= nl2br(htmlspecialchars($consultation['histoire_maladie'])) ?></p>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($consultation['automedication'])): ?>
                    <div class="mb-3">
                        <div class="text-muted small fw-bold text-uppercase mb-1" style="font-size:.65rem;">Automédication</div>
                        <p class="mb-0 text-warning"><?= nl2br(htmlspecialchars($consultation['automedication'])) ?></p>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($antecedents)): ?>
                    <div class="mb-0">
                        <div class="text-muted small fw-bold text-uppercase mb-2" style="font-size:.65rem;">Antécédents</div>
                        <?php foreach ($antecedents as $type => $items): ?>
                            <div class="mb-2">
                                <span class="badge bg-secondary-subtle text-secondary rounded-pill mb-1">
                                    <?= ucfirst(str_replace('_', ' ', $type)) ?>
                                </span>
                                <div>
                                <?php foreach ($items as $a): ?>
                                    <span class="atcd-tag"><?= htmlspecialchars($a['description']) ?></span>
                                <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 2. EXAMEN PHYSIQUE -->
            <div class="r-card">
                <div class="r-card-header text-warning">
                    <i class="bi bi-person-check-fill"></i> Examen Physique
                </div>
                <div class="r-card-body">
                    <?php if (!empty($consultation['examen_physique'])): ?>
                    <p class="mb-3"><?= nl2br(htmlspecialchars($consultation['examen_physique'])) ?></p>
                    <?php endif; ?>

                    <?php if (!empty($consultation['resume_syndromique'])): ?>
                    <div class="bg-light rounded-3 p-3">
                        <div class="text-muted small fw-bold text-uppercase mb-1" style="font-size:.65rem;">Résumé Syndromique</div>
                        <p class="mb-0 fst-italic"><?= nl2br(htmlspecialchars($consultation['resume_syndromique'])) ?></p>
                    </div>
                    <?php endif; ?>

                    <?php if (empty($consultation['examen_physique']) && empty($consultation['resume_syndromique'])): ?>
                    <p class="text-muted fst-italic mb-0">Aucun examen physique enregistré.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 3. SURVEILLANCE -->
            <?php if (!empty($consultation['surveillance'])): ?>
            <div class="r-card">
                <div class="r-card-header" style="color:#7c3aed;">
                    <i class="bi bi-eye-fill"></i> Plan de Surveillance
                </div>
                <div class="r-card-body">
                    <p class="mb-0"><?= nl2br(htmlspecialchars($consultation['surveillance'])) ?></p>
                </div>
            </div>
            <?php endif; ?>

            <!-- 4. SUIVI & RDV -->
            <?php if (!empty($consultation['notes_suivi']) || !empty($consultation['date_suivi'])): ?>
            <div class="r-card">
                <div class="r-card-header text-success">
                    <i class="bi bi-calendar-check-fill"></i> Suivi &amp; Prochain Rendez-vous
                </div>
                <div class="r-card-body">
                    <?php if (!empty($consultation['date_suivi'])): ?>
                    <div class="d-flex align-items-center gap-3 mb-3 bg-success-subtle p-3 rounded-3">
                        <i class="bi bi-calendar-event-fill text-success fs-4"></i>
                        <div>
                            <div class="fw-bold"><?= date('d/m/Y', strtotime($consultation['date_suivi'])) ?></div>
                            <small class="text-muted">Date du prochain rendez-vous</small>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($consultation['notes_suivi'])): ?>
                    <div class="text-muted small fw-bold text-uppercase mb-1" style="font-size:.65rem;">Notes de suivi</div>
                    <p class="mb-0"><?= nl2br(htmlspecialchars($consultation['notes_suivi'])) ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

        </div>

        <!-- ═══ COLONNE DROITE ═══════════════════════════════════════ -->
        <div class="col-lg-6">

            <!-- 5. DIAGNOSTIC -->
            <div class="r-card" style="border-top: 4px solid var(--danger);">
                <div class="r-card-header text-danger">
                    <i class="bi bi-bullseye"></i> Diagnostic
                </div>
                <div class="r-card-body">
                    <?php if (!empty($consultation['diagnostic_principal'])): ?>
                    <div class="mb-3">
                        <div class="text-muted small fw-bold text-uppercase mb-1" style="font-size:.65rem;">Diagnostic principal</div>
                        <h5 class="fw-bold text-danger mb-0"><?= htmlspecialchars($consultation['diagnostic_principal']) ?></h5>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($consultation['hypotheses_diagnostiques'])): ?>
                    <div class="mb-3">
                        <div class="text-muted small fw-bold text-uppercase mb-1" style="font-size:.65rem;">Hypothèses diagnostiques</div>
                        <p class="mb-0 small"><?= nl2br(htmlspecialchars($consultation['hypotheses_diagnostiques'])) ?></p>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($consultation['diagnostics_differentiels'])): ?>
                    <div class="mb-0">
                        <div class="text-muted small fw-bold text-uppercase mb-1" style="font-size:.65rem;">Diagnostics différentiels</div>
                        <p class="mb-0 small"><?= nl2br(htmlspecialchars($consultation['diagnostics_differentiels'])) ?></p>
                    </div>
                    <?php endif; ?>

                    <?php if (empty($consultation['diagnostic_principal']) && empty($consultation['hypotheses_diagnostiques'])): ?>
                    <p class="text-muted fst-italic mb-0">Aucun diagnostic enregistré.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 6. BILANS DEMANDÉS -->
            <?php if (!empty($examens_list) || !empty($consultation['examens_paracliniques'])): ?>
            <div class="r-card">
                <div class="r-card-header text-warning">
                    <i class="bi bi-flask-fill"></i> Examens Paracliniques
                </div>
                <div class="r-card-body">
                    <?php if (!empty($consultation['examens_paracliniques'])): ?>
                    <div class="mb-3 text-muted small"><?= nl2br(htmlspecialchars($consultation['examens_paracliniques'])) ?></div>
                    <?php endif; ?>

                    <?php if (!empty($examens_list)): ?>
                    <?php foreach ($examens_list as $ex): ?>
                    <div class="exam-row">
                        <span>
                            <span class="fw-bold"><?= htmlspecialchars($ex['noms_detail'] ?: ($ex['type_examen'] ?? 'Bilan')) ?></span>
                            <?php if (!empty($ex['type_examen']) && !empty($ex['noms_detail'])): ?>
                            <small class="text-muted ms-1">(<?= htmlspecialchars($ex['type_examen']) ?>)</small>
                            <?php endif; ?>
                            <?php if (!empty($ex['urgence'])): ?>
                            <span class="badge bg-danger ms-2" style="font-size:.6rem;">URGENT</span>
                            <?php endif; ?>
                        </span>
                        <span class="badge rounded-pill <?= match($ex['statut'] ?? '') {
                            'TERMINE' => 'bg-success',
                            'EN_COURS' => 'bg-warning text-dark',
                            default => 'bg-secondary'
                        } ?>">
                            <?= match($ex['statut'] ?? '') {
                                'TERMINE'  => 'Résultat disponible',
                                'EN_COURS' => 'En cours',
                                'ANNULE'   => 'Annulé',
                                default    => 'En attente'
                            } ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- 7. TRAITEMENT & ORDONNANCE -->
            <div class="r-card">
                <div class="r-card-header text-success">
                    <i class="bi bi-capsule-pill"></i> Traitement Prescrit
                </div>
                <div class="r-card-body">
                    <?php if (!empty($consultation['plan_traitement'])): ?>
                    <div class="mb-3">
                        <div class="text-muted small fw-bold text-uppercase mb-1" style="font-size:.65rem;">Plan thérapeutique</div>
                        <p class="mb-0 small"><?= nl2br(htmlspecialchars($consultation['plan_traitement'])) ?></p>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($medicaments_prescrits)): ?>
                    <div class="mb-3">
                        <div class="text-muted small fw-bold text-uppercase mb-2" style="font-size:.65rem;">
                            Ordonnance
                            <span class="fw-normal ms-2 text-primary">N° <?= str_pad($prescription['id'], 6, '0', STR_PAD_LEFT) ?></span>
                        </div>
                        <?php foreach ($medicaments_prescrits as $i => $med): ?>
                        <div class="med-row">
                            <div class="med-num"><?= $i + 1 ?></div>
                            <div>
                                <div class="med-name"><?= htmlspecialchars($med['nom_medicament']) ?>
                                    <?php if (!empty($med['dosage'])): ?>
                                    <span class="text-muted fw-normal" style="font-size:.8rem;"><?= htmlspecialchars($med['dosage']) ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="med-detail d-flex flex-wrap gap-2">
                                    <?php if (!empty($med['posologie'])): ?>
                                    <span><i class="bi bi-clock me-1"></i><?= htmlspecialchars($med['posologie']) ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($med['duree'])): ?>
                                    <span><i class="bi bi-calendar me-1"></i><?= htmlspecialchars($med['duree']) ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($med['voie'] ?? $med['forme'] ?? '')): ?>
                                    <span class="badge bg-light text-dark border"><?= htmlspecialchars($med['voie'] ?? $med['forme']) ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($med['quantite'])): ?>
                                    <span><i class="bi bi-boxes me-1"></i>Qté : <?= htmlspecialchars($med['quantite']) ?></span>
                                    <?php endif; ?>
                                </div>
                                <?php if (!empty($med['message_stock'] ?? '')): ?>
                                <div class="mt-1 text-warning small fst-italic">
                                    <i class="bi bi-info-circle me-1"></i><?= htmlspecialchars($med['message_stock']) ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php elseif (empty($consultation['plan_traitement'])): ?>
                    <p class="text-muted fst-italic mb-0">Aucun traitement prescrit.</p>
                    <?php endif; ?>

                    <?php if (!empty($consultation['traitement_non_medicamenteux'])): ?>
                    <div class="mt-3 pt-3 border-top">
                        <div class="text-muted small fw-bold text-uppercase mb-1" style="font-size:.65rem;">Traitement non médicamenteux</div>
                        <p class="mb-0 small"><?= nl2br(htmlspecialchars($consultation['traitement_non_medicamenteux'])) ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 8. ORIENTATION PATIENT -->
            <?php $analyse = $criteres_hosp ?? null; ?>
            <div class="r-card">
                <div class="r-card-header text-dark">
                    <i class="bi bi-hospital-fill"></i> Orientation Patient
                </div>
                <div class="r-card-body">
                    <?php if ($analyse && !empty($analyse['recommandation'])): ?>
                    <div class="decision-box decision-<?= $analyse['recommandation']['niveau'] ?? 'suivi_ambulatoire' ?> mb-3">
                        <h6 class="fw-bold mb-1"><?= htmlspecialchars($analyse['recommandation']['message'] ?? '') ?></h6>
                        <small class="text-muted">Score de risque : <?= $analyse['score_risque'] ?? 0 ?>/10</small>
                    </div>
                    <?php endif; ?>

                    <div class="d-flex flex-wrap justify-content-center gap-2 no-print">
                        <button class="btn btn-danger btn-sm rounded-pill px-3 fw-bold"
                                onclick="ouvrirModalHospitalisation('HOSPITALISATION','Hospitalisation urgente')">
                            <i class="bi bi-hospital me-1"></i>Hospitaliser
                        </button>
                        <button class="btn btn-info btn-sm rounded-pill px-3 fw-bold text-white"
                                onclick="ouvrirModalTransfert()">
                            <i class="bi bi-arrow-left-right me-1"></i>Transfert
                        </button>
                        <button class="btn btn-success btn-sm rounded-pill px-3 fw-bold"
                                onclick="ouvrirModalDecision('SORTIE','Sortie ambulatoire')">
                            <i class="bi bi-house-heart me-1"></i>Sortie ambulatoire
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Modal Décision simple (Sortie / confirmation) -->
<div class="modal fade" id="modalDecision" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius:20px;">
            <div class="modal-header border-0 p-4 pb-0">
                <h5 class="fw-bold" id="modalDecisionTitre">Confirmer l'orientation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div id="msgDecision" class="alert alert-light border mb-3 small py-2"></div>
                <label class="form-label small fw-bold text-muted text-uppercase">Motif / Justification</label>
                <textarea id="justification" class="form-control" rows="3"
                          style="background:#f8fafc;border:2px solid #e2e8f0;border-radius:12px;"
                          placeholder="Raison médicale..."></textarea>
                <div class="d-flex justify-content-center gap-3 mt-4">
                    <button class="btn btn-light rounded-pill px-4 fw-bold" data-bs-dismiss="modal">Annuler</button>
                    <button class="btn btn-primary rounded-pill px-5 shadow fw-bold" onclick="confirmerDecision()">Confirmer</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Hospitalisation avec sélection service + lit -->
<div class="modal fade" id="modalHospitalisation" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius:20px;">
            <div class="modal-header border-0 p-4 pb-0">
                <h5 class="fw-bold"><i class="bi bi-hospital me-2 text-danger"></i>Décision d'hospitalisation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="alert alert-warning small py-2 mb-3" id="alerteHosp" style="display:none;"></div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted text-uppercase">Service de destination</label>
                        <select id="hospServiceId" class="form-select" onchange="chargerLitsService(this.value)" style="border-radius:12px;">
                            <option value="">— Sélectionner —</option>
                            <?php foreach ($services ?? [] as $s): ?>
                            <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted text-uppercase">Lit prévu</label>
                        <select id="hospLitId" class="form-select" style="border-radius:12px;">
                            <option value="">— Choisir un service d'abord —</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-bold text-muted text-uppercase">Motif / Justification médicale</label>
                        <textarea id="hospJustification" class="form-control" rows="3"
                                  style="background:#f8fafc;border:2px solid #e2e8f0;border-radius:12px;"
                                  placeholder="Raison clinique de l'hospitalisation..."></textarea>
                    </div>
                </div>
                <div class="d-flex justify-content-center gap-3 mt-4">
                    <button class="btn btn-light rounded-pill px-4 fw-bold" data-bs-dismiss="modal">Annuler</button>
                    <button class="btn btn-danger rounded-pill px-5 shadow fw-bold" onclick="confirmerHospitalisation()">
                        <i class="bi bi-hospital me-1"></i>Confirmer l'hospitalisation
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Transfert inter-service -->
<div class="modal fade" id="modalTransfert" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius:20px;">
            <div class="modal-header border-0 p-4 pb-0">
                <h5 class="fw-bold"><i class="bi bi-arrow-left-right me-2 text-info"></i>Transfert inter-service</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted text-uppercase">Service de destination</label>
                        <select id="trfServiceId" class="form-select" onchange="chargerLitsTransfert(this.value)" style="border-radius:12px;">
                            <option value="">— Sélectionner —</option>
                            <?php foreach ($services ?? [] as $s): ?>
                            <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted text-uppercase">Lit de destination</label>
                        <select id="trfLitId" class="form-select" style="border-radius:12px;">
                            <option value="">— Choisir un service d'abord —</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-bold text-muted text-uppercase">Motif du transfert</label>
                        <textarea id="trfMotif" class="form-control" rows="2"
                                  style="background:#f8fafc;border:2px solid #e2e8f0;border-radius:12px;"
                                  placeholder="Raison du transfert..."></textarea>
                    </div>
                </div>
                <div class="d-flex justify-content-center gap-3 mt-4">
                    <button class="btn btn-light rounded-pill px-4 fw-bold" data-bs-dismiss="modal">Annuler</button>
                    <button class="btn btn-info text-white rounded-pill px-5 shadow fw-bold" onclick="confirmerTransfert()">
                        <i class="bi bi-arrow-left-right me-1"></i>Confirmer le transfert
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const LITS_DISPO = <?= json_encode($lits_dispo ?? []) ?>;
const CONSULTATION_ID = <?= (int)($consultation['id'] ?? 0) ?>;
const PATIENT_ID = <?= (int)($patient['id'] ?? 0) ?>;
const BASE = '<?= BASE_URL ?>';

let decisionEnCours = '', decisionLabel = '';

/* ── Sortie simple ── */
function ouvrirModalDecision(decision, label) {
    decisionEnCours = decision; decisionLabel = label;
    document.getElementById('modalDecisionTitre').textContent = label;
    document.getElementById('msgDecision').innerHTML = 'Confirmer : <strong>' + label + '</strong>';
    document.getElementById('justification').value = '';
    new bootstrap.Modal(document.getElementById('modalDecision')).show();
}

function confirmerDecision() {
    fetch(BASE + 'consultation/decision-hospitalisation', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({consultation_id: CONSULTATION_ID, decision: decisionEnCours,
                              justification: document.getElementById('justification').value})
    }).then(r => r.json()).then(d => {
        if (d.success) window.location.href = d.redirect || (BASE + 'dashboard');
        else alert('Erreur : ' + (d.message || 'inconnue'));
    });
}

/* ── Hospitalisation ── */
function ouvrirModalHospitalisation(decision, label) {
    document.getElementById('hospJustification').value = '';
    document.getElementById('hospServiceId').value = '';
    document.getElementById('hospLitId').innerHTML = '<option value="">— Choisir un service d\'abord —</option>';
    new bootstrap.Modal(document.getElementById('modalHospitalisation')).show();
}

function chargerLitsService(serviceId) {
    const sel = document.getElementById('hospLitId');
    sel.innerHTML = '<option value="">— Sélectionner un lit —</option>';
    const lits = LITS_DISPO[serviceId] || [];
    if (lits.length === 0) {
        sel.innerHTML = '<option value="">Aucun lit disponible</option>';
        return;
    }
    lits.forEach(l => {
        const opt = document.createElement('option');
        opt.value = l.id; opt.textContent = 'Lit ' + l.nom_lit;
        sel.appendChild(opt);
    });
}

function confirmerHospitalisation() {
    const serviceId = document.getElementById('hospServiceId').value;
    const litId     = document.getElementById('hospLitId').value;
    const motif     = document.getElementById('hospJustification').value;
    if (!serviceId) { alert('Veuillez sélectionner un service.'); return; }
    fetch(BASE + 'consultation/decision-hospitalisation', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({consultation_id: CONSULTATION_ID, decision: 'HOSPITALISATION',
                              service_id: serviceId, lit_id: litId, justification: motif})
    }).then(r => r.json()).then(d => {
        if (d.success) {
            bootstrap.Modal.getInstance(document.getElementById('modalHospitalisation'))?.hide();
            window.location.href = d.redirect || (BASE + 'dashboard');
        } else alert('Erreur : ' + (d.message || 'inconnue'));
    });
}

/* ── Transfert ── */
function ouvrirModalTransfert() {
    document.getElementById('trfMotif').value = '';
    document.getElementById('trfServiceId').value = '';
    document.getElementById('trfLitId').innerHTML = '<option value="">— Choisir un service d\'abord —</option>';
    new bootstrap.Modal(document.getElementById('modalTransfert')).show();
}

function chargerLitsTransfert(serviceId) {
    const sel = document.getElementById('trfLitId');
    sel.innerHTML = '<option value="">— Sélectionner un lit —</option>';
    const lits = LITS_DISPO[serviceId] || [];
    if (lits.length === 0) { sel.innerHTML = '<option value="">Aucun lit disponible</option>'; return; }
    lits.forEach(l => {
        const opt = document.createElement('option');
        opt.value = l.id; opt.textContent = 'Lit ' + l.nom_lit;
        sel.appendChild(opt);
    });
}

function confirmerTransfert() {
    const serviceId = document.getElementById('trfServiceId').value;
    const litId     = document.getElementById('trfLitId').value;
    const motif     = document.getElementById('trfMotif').value;
    if (!serviceId || !litId) { alert('Veuillez sélectionner un service et un lit.'); return; }
    const fd = new FormData();
    fd.append('patient_id', PATIENT_ID);
    fd.append('service_id', serviceId);
    fd.append('lit_id', litId);
    fd.append('motif', motif);
    fetch(BASE + 'consultation/transferer-patient', {method: 'POST', body: fd})
        .then(r => r.json()).then(d => {
            if (d.success) {
                bootstrap.Modal.getInstance(document.getElementById('modalTransfert'))?.hide();
                alert(d.message);
                window.location.href = d.redirect || (BASE + 'dashboard');
            } else alert('Erreur : ' + (d.message || 'inconnue'));
        });
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
