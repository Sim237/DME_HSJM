<?php
require_once __DIR__ . '/../layouts/header.php';

// --- Fonctions et Initialisation ---
if (!function_exists('getInitials')) {
    function getInitials($nom, $prenom) {
        return strtoupper(substr($nom ?? '', 0, 1) . substr($prenom ?? '', 0, 1));
    }
}

// Sécurisation des données patient
$patient        = $patient        ?? [];
$parametres     = $parametres     ?? null;
$consultations  = $consultations  ?? [];
$bilans         = $bilans         ?? [];
$history        = $history        ?? [];
$comptes_rendus = $comptes_rendus ?? [];
$prescriptions  = $prescriptions  ?? [];
$bilans_demandes = $bilans_demandes ?? [];
$bilans_imagerie = $bilans_imagerie ?? [];

// Calcul de l'âge
$age = 'N/A';
$ageNumerique = 999; // adulte par défaut
if (!empty($patient['date_naissance'])) {
    $diff = date_diff(date_create($patient['date_naissance']), date_create('today'));
    $ageNumerique = (int)$diff->y;
    $age = $ageNumerique . ' ans';
}
$isEnfant = ($ageNumerique <= 15);
?>

<!-- IMPORT DES ICONES BOOTSTRAP -->
<!--<link rel="stylesheet" href="<?= BASE_URL ?>public/css/bootstrap-icons.css">-->

<!-- STYLE MODERNE DÉDIÉ AU DOSSIER -->
<style>
    body { background-color: #f4f7f6; }
    .profile-card {
        border: none; border-radius: 16px; background: #fff;
        box-shadow: 0 4px 20px rgba(0,0,0,0.05); overflow: hidden;
        position: sticky; top: 20px;
    }
    .profile-header-bg { background: linear-gradient(135deg, #0d6efd, #0099ff); height: 100px; }
    .avatar-circle {
        width: 100px; height: 100px; border-radius: 50%; background: #fff;
        border: 4px solid #fff; display: flex; align-items: center; justify-content: center;
        font-size: 2.5rem; font-weight: 800; color: #0d6efd;
        margin: -50px auto 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }
    .vital-box {
        background: #fff; border-radius: 12px; padding: 15px;
        border-left: 5px solid #ccc; box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        height: 100%; transition: transform 0.2s;
    }
    .vital-box:hover { transform: translateY(-3px); }
    .vital-box.temp { border-color: #dc3545; }
    .vital-box.tension { border-color: #0d6efd; }
    .vital-box.pouls { border-color: #198754; }
    .vital-box.poids { border-color: #ffc107; }

    .vital-label { font-size: 0.8rem; font-weight: 700; color: #6c757d; text-transform: uppercase; display: block; margin-bottom: 5px; }
    .vital-value { font-size: 1.5rem; font-weight: 700; color: #212529; }

    .nav-tabs .nav-link { border: none; color: #6c757d; font-weight: 600; padding: 12px 20px; }
    .nav-tabs .nav-link.active { color: #0d6efd; border-bottom: 3px solid #0d6efd; background-color: #fff; }
    .tab-content { background: #fff; padding: 25px; border-radius: 0 0 12px 12px; border: 1px solid #dee2e6; border-top: none; }

    .info-row { display: flex; align-items: center; padding: 10px 0; border-bottom: 1px solid #f0f0f0; }
    .info-icon { width: 35px; height: 35px; background: #eff6ff; color: #0d6efd; border-radius: 8px; display: flex; align-items: center; justify-content: center; margin-right: 15px; }

    .table-danger-light { background-color: #fff5f5 !important; }
    .text-anormal { color: #dc3545; font-weight: 800; }
</style>

<div class="container-fluid p-4">

    <!-- BARRE D'ACTIONS SUPÉRIEURE -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-0">Dossier Médical</h2>
            <p class="text-muted mb-0">Patient : <?= htmlspecialchars($patient['nom'] . ' ' . $patient['prenom']) ?></p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="javascript:history.back()" class="btn btn-outline-secondary btn-sm shadow-sm"><i class="bi bi-arrow-left"></i> Retour</a>

            <form action="<?= BASE_URL ?>consultation/commencer" method="POST" style="display: inline;">
                <input type="hidden" name="patient_id" value="<?= $patient['id'] ?>">
                <button type="submit" class="btn btn-success btn-sm shadow-sm"><i class="bi bi-plus-lg"></i> Nouvelle Consultation</button>
            </form>

            <?php if (in_array($_SESSION['user_role'], ['MEDECIN', 'CHIRURGIEN', 'ADMIN'])): ?>
                <button type="button" class="btn btn-dark btn-sm shadow-sm" onclick="transmettreAnesthesie(<?= $patient['id'] ?>)">
                    <i class="bi bi-scissors me-1"></i> A Opérer
                </button>
                <?php if (($patient['statut'] ?? '') !== 'HOSPITALISE'): ?>
                    <button class="btn btn-primary btn-sm rounded-pill" data-bs-toggle="modal" data-bs-target="#modalAdmission">
                        <i class="bi bi-box-arrow-in-right"></i> Admettre sur un Lit
                    </button>
                <?php else: ?>
                    <button class="btn btn-warning btn-sm rounded-pill" onclick="libererLit(<?= $patient['id'] ?>)">
                        <i class="bi bi-box-arrow-left"></i> Décharger du Lit
                    </button>
                <?php endif; ?>
            <?php endif; ?>

            <button type="button" class="btn btn-danger btn-sm shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTransfusion"><i class="bi bi-droplet-fill"></i> A Transfuser</button>
            <button type="button" class="btn btn-info btn-sm text-white shadow-sm" data-bs-toggle="modal" data-bs-target="#modalBilan"><i class="bi bi-flask"></i> Demander Bilans</button>
            <button class="btn btn-info btn-sm shadow-sm text-white" data-bs-toggle="modal" data-bs-target="#modalPartager">
    <i class="bi bi-share"></i> Partager le dossier
</button>
            <a href="<?= BASE_URL ?>hospitalisation/planifier-soins/<?= $patient['id'] ?>" class="btn btn-primary btn-sm shadow-sm"><i class="bi bi-calendar-check"></i> Planifier Soins</a>
            <button type="button" class="btn btn-dark btn-sm shadow-sm" data-bs-toggle="modal" data-bs-target="#modalListeFormulaires"><i class="bi bi-file-earmark-text"></i> Mes formulaires</button>
        </div>
    </div>

    <div class="row g-4">
        <!-- COLONNE GAUCHE (3/12) : PROFIL -->
        <div class="col-lg-3">
            <div class="profile-card">
                <div class="profile-header-bg"></div>
                <div class="avatar-circle"><?= getInitials($patient['nom'], $patient['prenom']) ?></div>
                <div class="px-4 pb-4">
                    <div class="text-center mb-4">
                        <h4 class="fw-bold mb-1"><?= htmlspecialchars($patient['nom'] . ' ' . $patient['prenom']) ?></h4>
                        <span class="badge bg-light text-primary border">ID: <?= htmlspecialchars($patient['dossier_numero']) ?></span>
                    </div>
                    <div class="info-row"><div class="info-icon"><i class="bi bi-cake2"></i></div><div><small class="text-muted d-block">Âge</small><strong><?= $age ?></strong></div></div>
                    <div class="info-row"><div class="info-icon"><i class="bi bi-gender-ambiguous"></i></div><div><small class="text-muted d-block">Sexe</small><strong><?= ($patient['sexe'] ?? '') === 'M' ? 'Masculin' : 'Féminin' ?></strong></div></div>
                    <div class="info-row"><div class="info-icon text-danger"><i class="bi bi-droplet-fill"></i></div><div><small class="text-muted d-block">Groupe Sanguin</small><strong class="text-danger"><?= $patient['groupe_sanguin'] ?: 'Inconnu' ?></strong></div></div>
                    <div class="d-grid mt-4">
                        <button type="button" class="btn btn-primary rounded-pill btn-sm shadow-sm"
                                data-bs-toggle="modal" data-bs-target="#modalConstantes">
                            <i class="bi bi-activity"></i> Saisir Constantes
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- COLONNE DROITE (9/12) : CLINIQUE -->
        <div class="col-lg-9">

            <!-- 1. WIDGETS CONSTANTES -->
            <div class="row g-3 mb-4">
                <div class="col-md-3 col-6">
                    <div class="vital-box temp shadow-sm">
                        <span class="vital-label">Température</span>
                        <div class="vital-value text-danger" id="vt-temp"><?= $parametres['temperature'] ?? '--' ?> <small>°C</small></div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="vital-box tension shadow-sm">
                        <span class="vital-label">Tension</span>
                        <div class="vital-value text-primary" id="vt-tension">
                            <?= (isset($parametres['pression_arterielle_systolique']) && $parametres['pression_arterielle_systolique'] > 0) ? $parametres['pression_arterielle_systolique'].'/'.$parametres['pression_arterielle_diastolique'] : '--/--' ?>
                            <small class="fs-6">mmHg</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="vital-box pouls shadow-sm">
                        <span class="vital-label">Pouls</span>
                        <div class="vital-value text-success" id="vt-pouls"><?= $parametres['frequence_cardiaque'] ?? '--' ?> <small>bpm</small></div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="vital-box poids shadow-sm">
                        <span class="vital-label">Poids</span>
                        <div class="vital-value text-dark" id="vt-poids"><?= $parametres['poids'] ?? '--' ?> <small>kg</small></div>
                    </div>
                </div>
            </div>

            <!-- 2. ONGLETS -->
            <ul class="nav nav-tabs" id="myTab" role="tablist">
                <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-consultations" type="button"><i class="bi bi-journal-text me-2"></i>Consultations</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-antecedents" type="button"><i class="bi bi-clock-history me-2"></i>Antécédents</button></li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-prescriptions" type="button">
                        <i class="bi bi-capsule me-2"></i>Médicaments
                        <?php if (!empty($prescriptions)): ?>
                            <span class="badge bg-warning text-dark rounded-pill ms-1"><?= count($prescriptions) ?></span>
                        <?php endif; ?>
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-bilans-demandes" type="button">
                        <i class="bi bi-flask me-2"></i>Bilans
                        <?php if (!empty($bilans_demandes)): ?>
                            <span class="badge bg-info text-white rounded-pill ms-1"><?= count($bilans_demandes) ?></span>
                        <?php endif; ?>
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-imagerie" type="button">
                        <i class="bi bi-image me-2"></i>Radiologie
                        <?php if (!empty($bilans_imagerie)): ?>
                            <span class="badge bg-dark rounded-pill ms-1"><?= count($bilans_imagerie) ?></span>
                        <?php endif; ?>
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-documents" type="button">
                        <i class="bi bi-file-earmark-medical me-2"></i>Documents
                        <?php if (!empty($comptes_rendus)): ?>
                            <span class="badge bg-primary rounded-pill ms-1"><?= count($comptes_rendus) ?></span>
                        <?php endif; ?>
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="myTabContent">
                <!-- CONTENU CONSULTATIONS -->
                <div class="tab-pane fade show active" id="tab-consultations">
                    <?php if(!empty($consultations)): foreach($consultations as $c): ?>
                        <div class="card mb-3 border-0 shadow-sm border-start border-primary border-4">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="fw-bold text-primary mb-1"><?= htmlspecialchars($c['motif_consultation']) ?></h6>
                                        <p class="small mb-1"><strong>Diagnostic :</strong> <?= htmlspecialchars($c['diagnostic_principal']) ?></p>
                                        <small class="text-muted">Dr. <?= htmlspecialchars($c['medecin_nom']) ?></small>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-light text-dark border mb-2"><?= date('d/m/Y', strtotime($c['date_consultation'])) ?></span><br>
                                        <a href="<?= BASE_URL ?>consultation/recapitulatif/<?= $c['id'] ?>" class="btn btn-sm btn-outline-primary px-3 rounded-pill">Détails</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; else: ?>
                        <p class="text-center py-4 text-muted">Aucune consultation.</p>
                    <?php endif; ?>
                </div>

                <!-- CONTENU ANTÉCÉDENTS -->
                <div class="tab-pane fade" id="tab-antecedents">
                    <div class="p-3 bg-light rounded border mb-3">
                        <h6 class="fw-bold text-danger"><i class="bi bi-exclamation-triangle-fill"></i> Allergies</h6>
                        <p class="mb-0 small"><?= nl2br(htmlspecialchars($patient['allergies'] ?: 'Néant')) ?></p>
                    </div>
                    <div class="p-3 bg-light rounded border">
                        <h6 class="fw-bold text-primary"><i class="bi bi-info-circle-fill"></i> Antécédents Médicaux</h6>
                        <p class="mb-0 small"><?= nl2br(htmlspecialchars($patient['antecedents_medicaux'] ?: 'Aucun')) ?></p>
                    </div>
                </div>

                <!-- CONTENU MÉDICAMENTS PRESCRITS -->
                <div class="tab-pane fade" id="tab-prescriptions">
                    <?php if (!empty($prescriptions)):
                        // Grouper par ordonnance
                        $grouped = [];
                        foreach ($prescriptions as $p) {
                            $key = $p['prescription_id'];
                            if (!isset($grouped[$key])) {
                                $grouped[$key] = [
                                    'date'     => $p['date_prescription'],
                                    'numero'   => $p['numero_ordonnance'],
                                    'statut'   => $p['statut_prescription'],
                                    'medecin'  => $p['medecin_nom'],
                                    'lignes'   => []
                                ];
                            }
                            $grouped[$key]['lignes'][] = $p;
                        }
                    ?>
                        <?php foreach ($grouped as $pres): ?>
                            <div class="card mb-3 border-0 shadow-sm border-start border-warning border-4">
                                <div class="card-body pb-2">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div>
                                            <span class="fw-bold text-warning"><i class="bi bi-receipt me-1"></i>Ordonnance</span>
                                            <small class="text-muted ms-2"><?= htmlspecialchars($pres['numero'] ?? '') ?></small>
                                        </div>
                                        <div class="text-end">
                                            <span class="badge bg-light text-dark border"><?= date('d/m/Y', strtotime($pres['date'])) ?></span>
                                            <?php
                                                $sc = match($pres['statut'] ?? '') {
                                                    'EN_ATTENTE'  => 'bg-warning text-dark',
                                                    'PARTIEL'     => 'bg-info text-white',
                                                    'SERVIE'      => 'bg-success',
                                                    'ANNULEE'     => 'bg-danger',
                                                    default       => 'bg-secondary',
                                                };
                                            ?>
                                            <span class="badge <?= $sc ?> ms-1"><?= $pres['statut'] ?? '—' ?></span>
                                        </div>
                                    </div>
                                    <small class="text-muted d-block mb-2">Dr. <?= htmlspecialchars($pres['medecin'] ?? '') ?></small>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover mb-0">
                                            <thead class="bg-light">
                                                <tr class="small text-muted text-uppercase">
                                                    <th>Médicament</th>
                                                    <th>Posologie</th>
                                                    <th>Voie</th>
                                                    <th>Fréquence</th>
                                                    <th>Durée</th>
                                                    <th>Qté</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($pres['lignes'] as $l): ?>
                                                    <tr>
                                                        <td class="fw-bold"><?= htmlspecialchars($l['medicament_nom']) ?>
                                                            <small class="text-muted d-block"><?= htmlspecialchars($l['forme'] . ' ' . $l['dosage']) ?></small>
                                                        </td>
                                                        <td><small><?= htmlspecialchars($l['posologie'] ?? '—') ?></small></td>
                                                        <td><small><?= htmlspecialchars($l['voie'] ?? '—') ?></small></td>
                                                        <td><small><?= htmlspecialchars($l['frequence'] ?? '—') ?></small></td>
                                                        <td><small><?= htmlspecialchars($l['duree'] ?? '—') ?></small></td>
                                                        <td><small><?= $l['quantite'] ?? '—' ?></small></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-capsule fs-1 d-block mb-2 opacity-25"></i>
                            Aucune prescription enregistrée.
                        </div>
                    <?php endif; ?>
                </div>

                <!-- CONTENU BILANS DEMANDÉS + RÉSULTATS -->
                <div class="tab-pane fade" id="tab-bilans-demandes">
                    <?php if (!empty($bilans_demandes)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle border-0">
                                <thead class="bg-light">
                                    <tr class="small text-uppercase text-muted">
                                        <th>Examen</th>
                                        <th>Demandé le</th>
                                        <th>Médecin</th>
                                        <th class="text-center">Statut</th>
                                        <th>Résultat</th>
                                        <th class="text-center">Interprétation</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($bilans_demandes as $b):
                                        $hasResult = !empty($b['date_resultat']);
                                        $isAnormal  = ($hasResult && $b['anormal'] == 1);
                                    ?>
                                        <tr class="<?= $isAnormal ? 'table-danger bg-opacity-10' : '' ?>">
                                            <td>
                                                <div class="fw-bold"><?= htmlspecialchars($b['nom_examen'] ?? 'Examen') ?></div>
                                                <small class="text-muted"><?= htmlspecialchars($b['categorie'] ?? '') ?></small>
                                            </td>
                                            <td><small><?= date('d/m/Y', strtotime($b['date_creation'])) ?></small></td>
                                            <td><small>Dr. <?= htmlspecialchars($b['medecin_nom'] . ' ' . $b['medecin_prenom']) ?></small></td>
                                            <td class="text-center">
                                                <?php
                                                    $statuts = [
                                                        'EN_ATTENTE'            => ['bg-warning text-dark', 'En attente'],
                                                        'PRELEVEMENTS_EFFECTUES'=> ['bg-info text-white',   'Prélevé'],
                                                        'EN_ANALYSE'            => ['bg-primary',            'En analyse'],
                                                        'RESULTATS_PRETS'       => ['bg-success',            'Résultat prêt'],
                                                        'VALIDES'               => ['bg-success',            'Validé'],
                                                    ];
                                                    [$scls, $stxt] = $statuts[$b['statut']] ?? ['bg-secondary', $b['statut']];
                                                ?>
                                                <span class="badge rounded-pill <?= $scls ?>"><?= $stxt ?></span>
                                            </td>
                                            <td>
                                                <?php if ($hasResult && $b['valeur_numerique'] !== null): ?>
                                                    <span class="fs-6 fw-bold <?= $isAnormal ? 'text-danger' : 'text-success' ?>">
                                                        <?= $b['valeur_numerique'] ?> <small><?= htmlspecialchars($b['unite'] ?? '') ?></small>
                                                    </span>
                                                    <br><small class="text-muted">Norme : <?= $b['valeur_normale_min'] ?>–<?= $b['valeur_normale_max'] ?></small>
                                                <?php elseif ($hasResult && !empty($b['resultat'])): ?>
                                                    <small><?= htmlspecialchars(substr($b['resultat'], 0, 80)) ?></small>
                                                <?php else: ?>
                                                    <small class="text-muted">—</small>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($hasResult): ?>
                                                    <span class="badge rounded-pill bg-<?= $isAnormal ? 'danger' : 'success' ?>">
                                                        <?= $isAnormal ? 'ANORMAL' : 'NORMAL' ?>
                                                    </span>
                                                <?php else: ?>
                                                    <small class="text-muted">En attente</small>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-flask fs-1 d-block mb-2 opacity-25"></i>
                            Aucun bilan demandé pour ce patient.
                        </div>
                    <?php endif; ?>
                </div>

                <!-- CONTENU RADIOLOGIE / IMAGERIE -->
                <div class="tab-pane fade" id="tab-imagerie">
                    <?php if (!empty($bilans_imagerie)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle border-0">
                                <thead class="bg-light">
                                    <tr class="small text-uppercase text-muted">
                                        <th>Examen</th>
                                        <th>Demandé le</th>
                                        <th>Médecin</th>
                                        <th class="text-center">Urgence</th>
                                        <th class="text-center">Statut</th>
                                        <th>Interprétation / Résultat</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($bilans_imagerie as $img): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-bold"><?= htmlspecialchars($img['nom_examen'] ?? '—') ?></div>
                                                <?php if (!empty($img['description'])): ?>
                                                    <small class="text-muted"><?= htmlspecialchars(substr($img['description'], 0, 60)) ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td><small><?= date('d/m/Y', strtotime($img['date_creation'])) ?></small></td>
                                            <td><small>Dr. <?= htmlspecialchars(($img['medecin_nom'] ?? '') . ' ' . ($img['medecin_prenom'] ?? '')) ?></small></td>
                                            <td class="text-center">
                                                <?php if (($img['urgence'] ?? '') === 'URGENT'): ?>
                                                    <span class="badge rounded-pill bg-danger">Urgent 🚨</span>
                                                <?php else: ?>
                                                    <span class="badge rounded-pill bg-secondary">Normal</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <?php
                                                    $imgStatuts = [
                                                        'EN_ATTENTE'    => ['bg-warning text-dark', 'En attente'],
                                                        'EN_COURS'      => ['bg-info text-white',   'En cours'],
                                                        'REALISE'       => ['bg-primary',            'Réalisé'],
                                                        'INTERPRETE'    => ['bg-success',            'Interprété'],
                                                        'VALIDE'        => ['bg-success',            'Validé'],
                                                    ];
                                                    [$icls, $itxt] = $imgStatuts[$img['statut'] ?? ''] ?? ['bg-secondary', $img['statut'] ?? '—'];
                                                ?>
                                                <span class="badge rounded-pill <?= $icls ?>"><?= $itxt ?></span>
                                            </td>
                                            <td>
                                                <?php if (!empty($img['interpretation'])): ?>
                                                    <small><?= htmlspecialchars(substr($img['interpretation'], 0, 100)) ?></small>
                                                <?php elseif (!empty($img['resultat'])): ?>
                                                    <small><?= htmlspecialchars(substr($img['resultat'], 0, 100)) ?></small>
                                                <?php else: ?>
                                                    <small class="text-muted">En attente de résultat</small>
                                                <?php endif; ?>
                                                <?php if (!empty($img['fichier_preview'])): ?>
                                                    <br><a href="<?= BASE_URL ?>imagerie/viewer/<?= $img['id'] ?>" target="_blank" class="btn btn-xs btn-outline-dark mt-1 px-2 py-0 small rounded-pill"><i class="bi bi-eye me-1"></i>Voir</a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-image fs-1 d-block mb-2 opacity-25"></i>
                            Aucun bilan de radiologie/imagerie pour ce patient.
                        </div>
                    <?php endif; ?>
                </div>

                <!-- CONTENU MES DOCUMENTS -->
                <div class="tab-pane fade" id="tab-documents">
                    <?php if (!empty($comptes_rendus)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle border-0">
                                <thead class="bg-light">
                                    <tr class="small text-uppercase text-muted">
                                        <th>Type de document</th>
                                        <th>Date d'entrée</th>
                                        <th>Date de sortie</th>
                                        <th>Médecin</th>
                                        <th class="text-center">Statut</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($comptes_rendus as $crh): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="bg-primary bg-opacity-10 text-primary rounded p-2 me-2">
                                                        <i class="bi bi-file-earmark-text"></i>
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold">Compte-rendu d'hospitalisation</div>
                                                        <small class="text-muted">Créé le <?= date('d/m/Y', strtotime($crh['created_at'])) ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><small><?= $crh['date_entree'] ? date('d/m/Y', strtotime($crh['date_entree'])) : '—' ?></small></td>
                                            <td><small><?= $crh['date_sortie'] ? date('d/m/Y', strtotime($crh['date_sortie'])) : '—' ?></small></td>
                                            <td><small>Dr. <?= htmlspecialchars($crh['medecin_nom'] . ' ' . $crh['medecin_prenom']) ?></small></td>
                                            <td class="text-center">
                                                <?php if ($crh['signe']): ?>
                                                    <span class="badge rounded-pill bg-success">
                                                        <i class="bi bi-patch-check-fill me-1"></i>Signé
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge rounded-pill bg-warning text-dark">Non signé</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end">
                                                <a href="<?= BASE_URL ?>formulaire/voir-crh/<?= $crh['id'] ?>"
                                                   class="btn btn-sm btn-outline-primary rounded-pill px-3 me-1"
                                                   target="_blank">
                                                    <i class="bi bi-eye me-1"></i>Consulter
                                                </a>
                                                <a href="<?= BASE_URL ?>formulaire/voir-crh/<?= $crh['id'] ?>"
                                                   class="btn btn-sm btn-outline-secondary rounded-pill px-3"
                                                   onclick="setTimeout(() => window.print(), 500); return false;"
                                                   target="_blank">
                                                    <i class="bi bi-printer me-1"></i>Imprimer
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-file-earmark-x fs-1 d-block mb-2 opacity-25"></i>
                            Aucun document disponible pour ce patient.
                        </div>
                    <?php endif; ?>
                </div>

                <!-- CONTENU BILANS -->
                <div class="tab-pane fade" id="tab-bilans">
                    <?php if (!empty($bilans)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle border-0">
                                <thead class="bg-light">
                                    <tr class="small text-uppercase text-muted">
                                        <th>Date & Examen</th>
                                        <th>Résultat</th>
                                        <th>Valeurs de Réf.</th>
                                        <th class="text-center">Statut</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($bilans as $b):
                                        $isAnormal = ($b['anormal'] == 1);
                                    ?>
                                        <tr class="<?= $isAnormal ? 'table-danger-light' : '' ?>">
                                            <td>
                                                <div class="fw-bold"><?= htmlspecialchars($b['nom_examen']) ?></div>
                                                <small class="text-muted">le <?= date('d/m/Y', strtotime($b['date_resultat'])) ?></small>
                                            </td>
                                            <td><span class="fs-5 <?= $isAnormal ? 'text-danger fw-bold' : '' ?>"><?= $b['valeur_numerique'] ?> <small class="fs-6 text-muted"><?= $b['unite'] ?></small></span></td>
                                            <td><small class="text-muted">Norme: <?= $b['valeur_normale_min'] ?> - <?= $b['valeur_normale_max'] ?></small></td>
                                            <td class="text-center">
                                                <span class="badge rounded-pill bg-<?= $isAnormal ? 'danger' : 'success' ?>">
                                                    <?= $isAnormal ? 'ANORMAL' : 'NORMAL' ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5 text-muted">Aucun résultat disponible.</div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 3. HISTORIQUE DES SOINS EFFECTUÉS (PARFAITEMENT CADRÉ ICI) -->
            <div class="mt-4 animate__animated animate__fadeIn">
                <div class="d-flex align-items-center mb-3">
                    <div class="bg-primary bg-opacity-10 p-2 rounded-3 me-3">
                        <i class="bi bi-clock-history text-primary fs-5"></i>
                    </div>
                    <h6 class="fw-bold text-dark mb-0">Historique des Soins Effectués</h6>
                </div>

                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" style="width: 100%;">
                            <thead style="background-color: #f8fafc;">
                                <tr class="text-muted" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;">
                                    <th class="ps-4 py-3" style="width: 25%;">Date & Heure</th>
                                    <th style="width: 40%;">Soin Effectué</th>
                                    <th style="width: 15%;">Catégorie</th>
                                    <th class="pe-4" style="width: 20%;">Intervenant</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($history)): ?>
                                    <tr><td colspan="4" class="text-center py-5 text-muted small italic">Aucun soin enregistré.</td></tr>
                                <?php else: foreach($history as $h):
                                    $initials = strtoupper(substr($h['infirmier_nom'] ?? 'ST', 0, 2));
                                ?>
                                <tr class="border-bottom-0">
                                    <td class="ps-4">
                                        <div class="fw-bold text-dark" style="font-size: 0.85rem;"><?= date('d/m/Y', strtotime($h['date_execution'])) ?></div>
                                        <div class="text-muted small">à <?= date('H:i', strtotime($h['date_execution'])) ?></div>
                                    </td>
                                    <td><div class="fw-semibold" style="color: #334155; font-size: 0.9rem;"><?= htmlspecialchars($h['soin_description']) ?></div></td>
                                    <td><span class="badge rounded-pill px-3 py-2 bg-light text-primary border"><?= $h['categorie'] ?></span></td>
                                    <td class="pe-4">
                                        <div class="d-flex align-items-center">
                                            <div class="bg-light rounded-circle d-flex align-items-center justify-content-center me-2 border" style="width: 28px; height: 28px; font-size: 0.65rem; font-weight: 700;"><?= $initials ?></div>
                                            <span class="small fw-medium"><?= htmlspecialchars($h['infirmier_nom'] ?? 'Staff') ?></span>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div> <!-- Fin col-lg-9 -->
    </div> <!-- Fin row -->
</div> <!-- Fin container -->

<!-- ================= MODALES D'ACTION ================= -->

<!-- MODALE DEMANDER BILAN (DYNAMIQUE LABO/RADIO) -->
<div class="modal fade" id="modalBilan" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 p-4">
                <h5 class="modal-title fw-bold text-primary"><i class="bi bi-plus-circle-dotted me-2"></i>Nouvelle Demande d'Examen</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 pt-0">
                <ul class="nav nav-pills mb-4 bg-light p-1 rounded-3" id="pills-tab-bilan" role="tablist">
                    <li class="nav-item flex-fill"><button class="nav-link active w-100 rounded-3 fw-bold" data-bs-toggle="pill" data-bs-target="#tab-labo" type="button">LABORATOIRE</button></li>
                    <li class="nav-item flex-fill"><button class="nav-link w-100 rounded-3 fw-bold" data-bs-toggle="pill" data-bs-target="#tab-radio" type="button">RADIOLOGIE</button></li>
                </ul>
                <div class="tab-content border-0 p-0 shadow-none">
                    <div class="tab-pane fade show active" id="tab-labo">
                        <form id="formBilanLabo">
                            <input type="hidden" name="patient_id" value="<?= $patient['id'] ?>">
                            <input type="hidden" name="type_bilan" value="laboratoire">
                            <div class="row g-3">
                                <div class="col-md-8">
                                    <label class="small fw-bold">CHOIX DE L'EXAMEN</label>
                                    <select class="form-select border-2" name="examen_id" required>
                                        <option value="1">NFS (Hématologie complète)</option>
                                        <option value="9">Glycémie (veineuse)</option>
                                        <option value="2">Bilan Infectieux (CRP/VS)</option>
                                    </select>
                                </div>
                                <div class="col-md-4"><label class="small fw-bold">URGENCE</label><select class="form-select border-2" name="urgence"><option value="NORMAL">Normal</option><option value="URGENT">Urgent 🚨</option></select></div>
                                <div class="col-12"><label class="small fw-bold">OBSERVATIONS</label><textarea class="form-control border-2" name="observations" rows="2"></textarea></div>
                                <button type="submit" class="btn btn-primary w-100 py-3 rounded-pill fw-bold mt-3">ENVOYER AU LABORATOIRE</button>
                            </div>
                        </form>
                    </div>
                    <div class="tab-pane fade" id="tab-radio">
                        <form id="formBilanRadio">
                            <input type="hidden" name="patient_id" value="<?= $patient['id'] ?>">
                            <input type="hidden" name="type_bilan" value="imagerie">
                            <div class="row g-3">
                                <div class="col-md-6"><label class="small fw-bold">MODALITÉ</label><select class="form-select border-2" name="type_imagerie" required><option value="radiographie">Radiographie</option><option value="echographie">Échographie</option><option value="scanner">Scanner</option></select></div>
                                <div class="col-md-6"><label class="small fw-bold">ZONE À EXAMINER</label><input type="text" name="partie_code" class="form-control border-2" placeholder="Ex: Thorax..." required></div>
                                <div class="col-12"><label class="small fw-bold">RENSEIGNEMENTS</label><textarea class="form-control border-2" name="observations" rows="2"></textarea></div>
                                <button type="submit" class="btn btn-dark w-100 py-3 rounded-pill fw-bold mt-3">TRANSMETTRE À L'IMAGERIE</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODALE TRANSFUSION -->
<div class="modal fade" id="modalTransfusion" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="bi bi-droplet-half"></i> Demande de Transfusion</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formTransfusion">
                <input type="hidden" name="patient_id" value="<?= $patient['id'] ?>">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="small fw-bold">Groupe</label>
                            <select name="groupe" id="trans_groupe" class="form-select" required>
                                <?php foreach(['A','B','AB','O'] as $g) echo "<option value='$g' ".($patient['groupe_sanguin']==$g?'selected':'').">$g</option>"; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="small fw-bold">Rhésus</label>
                            <select name="rhesus" id="trans_rhesus" class="form-select" required onchange="checkBloodStock()">
                                <option value="+">+</option><option value="-">-</option>
                            </select>
                        </div>
                    </div>
                    <div id="stockStatusBox" class="mt-3 alert d-none"></div>
                </div>
                <div class="modal-footer"><button type="submit" class="btn btn-danger w-100" id="btnSubmitTrans">Lancer la demande</button></div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalPartager" tabindex="-1">
    <div class="modal-dialog">
        <form action="<?= BASE_URL ?>patients/partager-dossier" method="POST" class="modal-content">
            <input type="hidden" name="patient_id" value="<?= $patient['id'] ?>">
            <div class="modal-header"><h5>Partager le dossier</h5></div>
            <div class="modal-body">
                <select name="service_id" id="selService" class="form-select mb-2" onchange="loadUsers()" required>
    <option value="">Choisir un service</option>
    <?php
    $db = (new Database())->getConnection();
    $services = $db->query("SELECT id, nom_service FROM services ORDER BY nom_service ASC")->fetchAll(PDO::FETCH_ASSOC);
    foreach($services as $s): ?>
        <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['nom_service']) ?></option>
    <?php endforeach; ?>
</select>
                <select name="role_cible" id="selRole" class="form-select mb-2" onchange="loadUsers()">
                    <option value="">Choisir rôle</option>
                    <option value="MEDECIN">Médecin</option>
                    <option value="INFIRMIER">Infirmier</option>
                </select>
                <select name="destinataire_id" id="selUser" class="form-select" required>
                    <option value="">Sélectionner la personne...</option>
                </select>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">Partager le dossier</button>
            </div>
        </form>
    </div>
</div>

<!-- MODALE LISTE FORMULAIRES -->
<div class="modal fade" id="modalListeFormulaires" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white"><h5 class="modal-title"><i class="bi bi-file-earmark-text"></i> Formulaires</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <div class="modal-body p-4">
                <div class="list-group list-group-flush">
                    <a href="<?= BASE_URL ?>formulaire/creer/bulletin-examens/<?= $patient['id'] ?>" class="list-group-item list-group-item-action">Bulletin d'examens</a>
                    <a href="<?= BASE_URL ?>formulaire/creer/certificat-hospitalisation/<?= $patient['id'] ?>" class="list-group-item list-group-item-action">Certificat d'hospitalisation</a>
                    <a href="<?= BASE_URL ?>hospitalisation/observations-evolution/<?= $patient['id'] ?>" class="list-group-item list-group-item-action fw-bold">Observations / Évolution</a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- SCRIPTS JAVASCRIPT -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Initialisation des onglets Bootstrap
    var triggerTabList = [].slice.call(document.querySelectorAll('#myTab button'))
    triggerTabList.forEach(function (triggerEl) {
        var tabTrigger = new bootstrap.Tab(triggerEl)
        triggerEl.addEventListener('click', function (event) {
            event.preventDefault(); tabTrigger.show();
        })
    });

    // 2. ACTIVATION DES FORMULAIRES DE BILAN (La partie qui manquait)
    console.log("Initialisation des formulaires de bilan...");
    setupAjaxBilan('formBilanLabo');
    setupAjaxBilan('formBilanRadio');
});

// Fonction universelle pour envoyer les demandes (Labo et Radio)
function setupAjaxBilan(formId) {
    const form = document.getElementById(formId);
    if (!form) {
        console.warn("Formulaire introuvable : " + formId);
        return;
    }

    form.addEventListener('submit', function(e) {
        e.preventDefault(); // Empêche le rechargement de la page

        const btn = this.querySelector('button[type="submit"]');
        const originalText = btn.innerHTML;

        // Feedback visuel
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Transmission...';

        const formData = new FormData(this);

        fetch('<?= BASE_URL ?>bilan/save', {
            method: 'POST',
            body: formData
        })
        .then(res => {
            // On vérifie si la réponse est bien du JSON
            const contentType = res.headers.get("content-type");
            if (contentType && contentType.indexOf("application/json") !== -1) {
                return res.json();
            } else {
                return res.text().then(text => { throw new Error("Réponse serveur non-JSON : " + text) });
            }
        })
        .then(data => {
            if (data.success) {
                alert("✅ " + data.message);
                location.reload(); // Rafraîchit pour voir la demande dans le suivi
            } else {
                alert("❌ Erreur : " + data.message);
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        })
        .catch(err => {
            console.error("Erreur Fetch :", err);
            alert("Erreur technique de communication avec le serveur.");
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
    });
}

// 3. Vérification Stock Banque de Sang
function checkBloodStock() {
    const g = document.getElementById('trans_groupe').value;
    const r = document.getElementById('trans_rhesus').value;
    const box = document.getElementById('stockStatusBox');
    if (!g || !r) return;

    fetch('<?= BASE_URL ?>banque-sang/check-stock', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `groupe=${g}&rhesus=${r}`
    })
    .then(res => res.json())
    .then(data => {
        box.classList.remove('d-none', 'alert-success', 'alert-warning');
        if (data.status === 'available') {
            box.innerHTML = `Sang disponible (${data.dispo} poches).`;
            box.classList.add('alert-success');
        } else {
            box.innerHTML = `Stock insuffisant. Alerte famille requise.`;
            box.classList.add('alert-warning');
        }
    });
}

function loadUsers() {
    const service = document.getElementById('selService').value;
    const role = document.getElementById('selRole').value;
    const selectUser = document.getElementById('selUser');

    // Réinitialiser
    selectUser.innerHTML = '<option value="">Sélectionner la personne...</option>';

    if(service && role) {
        // Afficher un petit chargement
        selectUser.innerHTML = '<option value="">Chargement...</option>';

        fetch(`<?= BASE_URL ?>api/get-users?service=${service}&role=${role}`)
        .then(response => response.json())
        .then(users => {
            selectUser.innerHTML = '<option value="">Sélectionner la personne...</option>';
            users.forEach(user => {
                selectUser.innerHTML += `<option value="${user.id}">${user.nom} ${user.prenom}</option>`;
            });
        })
        .catch(err => {
            console.error('Erreur:', err);
            selectUser.innerHTML = '<option value="">Erreur de chargement</option>';
        });
    }
}
</script>

<!-- ══ MODAL CONSTANTES (Adaptatif Enfant / Adulte) ═══════════════════════ -->
<?php
$cstGradient = $isEnfant
    ? 'linear-gradient(135deg,#059669,#10b981)'   // vert pédiatrie
    : 'linear-gradient(135deg,#0d6efd,#0099ff)';  // bleu adulte
$cstBtnClass = $isEnfant ? 'btn-success' : 'btn-primary';
$cstIcon     = $isEnfant ? 'bi-balloon-heart'     : 'bi-activity';
$cstLabel    = $isEnfant
    ? 'Pédiatrie &nbsp;<span class="badge bg-white text-success fw-bold">' . $ageNumerique . ' ans</span>'
    : 'Adulte &nbsp;<span class="badge bg-white text-primary fw-bold">' . $ageNumerique . ' ans</span>';
?>
<div class="modal fade" id="modalConstantes" tabindex="-1" aria-labelledby="modalConstantesLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered <?= $isEnfant ? 'modal-md' : 'modal-lg' ?>">
    <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">

      <!-- En-tête coloré selon profil -->
      <div class="modal-header text-white py-3" style="background:<?= $cstGradient ?>;">
        <div>
          <h5 class="modal-title fw-bold mb-0" id="modalConstantesLabel">
            <i class="bi <?= $cstIcon ?> me-2"></i>Constantes
            — <?= $cstLabel ?>
          </h5>
          <small class="opacity-75">
            <?= htmlspecialchars($patient['nom'] . ' ' . $patient['prenom']) ?>
            &nbsp;·&nbsp;<?= htmlspecialchars($patient['dossier_numero']) ?>
          </small>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <!-- Corps -->
      <div class="modal-body px-4 py-4">
        <div id="cst-alert" class="alert d-none mb-3" role="alert"></div>

        <?php if ($isEnfant): ?>
        <!-- ─── FORMULAIRE PÉDIATRIQUE ─── -->
        <div class="alert alert-success border-0 d-flex align-items-center gap-2 mb-4 py-2" style="background:#f0fdf4;">
          <i class="bi bi-info-circle-fill text-success"></i>
          <small class="text-success fw-semibold">Formulaire pédiatrique — Poids, Taille et Température uniquement</small>
        </div>
        <form id="formConstantes" novalidate>
          <input type="hidden" name="patient_id" value="<?= (int)$patient['id'] ?>">
          <input type="hidden" name="profil" value="ENFANT">

          <div class="row g-4">
            <!-- Poids -->
            <div class="col-6">
              <label class="form-label fw-semibold text-success">
                <i class="bi bi-person-standing me-1"></i>Poids <small class="text-muted">(kg)</small>
              </label>
              <input type="number" step="0.1" min="1" max="150" name="poids" id="cst-poids"
                     class="form-control form-control-lg text-center fw-bold"
                     placeholder="ex : 18.5"
                     style="border:2px solid #10b981;border-radius:12px;">
            </div>
            <!-- Taille -->
            <div class="col-6">
              <label class="form-label fw-semibold text-success">
                <i class="bi bi-rulers me-1"></i>Taille <small class="text-muted">(cm)</small>
              </label>
              <input type="number" min="30" max="200" name="taille" id="cst-taille"
                     class="form-control form-control-lg text-center fw-bold"
                     placeholder="ex : 110"
                     style="border:2px solid #10b981;border-radius:12px;">
            </div>
            <!-- Température (pleine largeur) -->
            <div class="col-12">
              <label class="form-label fw-semibold text-danger">
                <i class="bi bi-thermometer-half me-1"></i>Température <small class="text-muted">(°C)</small>
              </label>
              <div class="position-relative">
                <input type="number" step="0.1" min="34" max="43" name="temperature" id="cst-temp"
                       class="form-control form-control-lg text-center fw-bold"
                       placeholder="ex : 37.5"
                       style="border:2px solid #ef4444;border-radius:12px;font-size:1.5rem;height:70px;">
                <span class="position-absolute top-50 end-0 translate-middle-y me-3 text-muted fs-5 fw-bold pe-none">°C</span>
              </div>
              <!-- Indicateur visuel température -->
              <div id="cst-temp-badge" class="text-center mt-2"></div>
            </div>
          </div>
        </form>

        <?php else: ?>
        <!-- ─── FORMULAIRE ADULTE ─── -->
        <form id="formConstantes" novalidate>
          <input type="hidden" name="patient_id" value="<?= (int)$patient['id'] ?>">
          <input type="hidden" name="profil" value="ADULTE">

          <!-- Anthropométrie -->
          <p class="text-muted fw-semibold small text-uppercase mb-2">
            <i class="bi bi-person-lines-fill me-1"></i>Anthropométrie
          </p>
          <div class="row g-3 mb-4">
            <div class="col-md-6">
              <label class="form-label fw-semibold">Poids <small class="text-muted">(kg)</small></label>
              <div class="input-group">
                <span class="input-group-text bg-light"><i class="bi bi-person-standing text-primary"></i></span>
                <input type="number" step="0.1" min="1" max="300" name="poids" id="cst-poids"
                       class="form-control" placeholder="ex : 70.5">
              </div>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Taille <small class="text-muted">(cm)</small></label>
              <div class="input-group">
                <span class="input-group-text bg-light"><i class="bi bi-rulers text-primary"></i></span>
                <input type="number" min="100" max="250" name="taille" id="cst-taille"
                       class="form-control" placeholder="ex : 175">
              </div>
            </div>
          </div>

          <!-- Constantes vitales -->
          <p class="text-muted fw-semibold small text-uppercase mb-2">
            <i class="bi bi-heart-pulse me-1"></i>Constantes Vitales
          </p>
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label fw-semibold">Température <small class="text-muted">(°C)</small></label>
              <div class="input-group">
                <span class="input-group-text bg-light"><i class="bi bi-thermometer-half text-danger"></i></span>
                <input type="number" step="0.1" min="32" max="43" name="temperature" id="cst-temp"
                       class="form-control" placeholder="ex : 37.2">
              </div>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Pouls <small class="text-muted">(bpm)</small></label>
              <div class="input-group">
                <span class="input-group-text bg-light"><i class="bi bi-heart-pulse text-danger"></i></span>
                <input type="number" min="20" max="300" name="frequence_cardiaque" id="cst-pouls"
                       class="form-control" placeholder="ex : 80">
              </div>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Saturation O₂ <small class="text-muted">(%)</small></label>
              <div class="input-group">
                <span class="input-group-text bg-light"><i class="bi bi-lungs text-primary"></i></span>
                <input type="number" min="50" max="100" name="spo2" id="cst-spo2"
                       class="form-control" placeholder="ex : 98">
              </div>
            </div>
            <div class="col-12">
              <label class="form-label fw-semibold">Tension Artérielle <small class="text-muted">(mmHg)</small></label>
              <div class="d-flex align-items-center gap-2">
                <div class="input-group" style="max-width:200px;">
                  <span class="input-group-text bg-light fw-bold" style="font-size:11px;">SYS</span>
                  <input type="number" min="50" max="250" name="tension_sys" id="cst-sys"
                         class="form-control" placeholder="ex : 120">
                </div>
                <span class="fw-bold text-muted fs-5">/</span>
                <div class="input-group" style="max-width:200px;">
                  <span class="input-group-text bg-light fw-bold" style="font-size:11px;">DIA</span>
                  <input type="number" min="30" max="180" name="tension_dia" id="cst-dia"
                         class="form-control" placeholder="ex : 80">
                </div>
              </div>
            </div>
          </div>
        </form>
        <?php endif; ?>
      </div>

      <!-- Pied -->
      <div class="modal-footer px-4 py-3 border-top">
        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Annuler</button>
        <button type="button" class="<?= $cstBtnClass ?> btn rounded-pill px-5 fw-bold" id="btnSauvegarderConstantes">
          <i class="bi bi-save2 me-2"></i>Enregistrer
        </button>
      </div>

    </div>
  </div>
</div>

<script>
(function () {
    const isEnfant = <?= $isEnfant ? 'true' : 'false' ?>;

    // ── Indicateur température pédiatrique ──────────────────────────────────
    const inputTemp = document.getElementById('cst-temp');
    if (isEnfant && inputTemp) {
        inputTemp.addEventListener('input', function () {
            const v = parseFloat(this.value);
            const badge = document.getElementById('cst-temp-badge');
            if (!badge || isNaN(v)) { if(badge) badge.innerHTML=''; return; }
            let html = '', cls = '';
            if      (v < 36.0)              { cls = 'text-bg-info';    html = '🥶 Hypothermie'; }
            else if (v >= 36.0 && v < 37.5) { cls = 'text-bg-success'; html = '✅ Normale'; }
            else if (v >= 37.5 && v < 38.5) { cls = 'text-bg-warning'; html = '⚠️ Fébricule'; }
            else                            { cls = 'text-bg-danger';  html = '🔥 Fièvre'; }
            badge.innerHTML = `<span class="badge ${cls} fs-6 px-3 py-2">${html} — ${v} °C</span>`;
        });
    }

    // ── Soumission AJAX ──────────────────────────────────────────────────────
    document.getElementById('btnSauvegarderConstantes').addEventListener('click', function () {
        const btn  = this;
        const form = document.getElementById('formConstantes');
        const alrt = document.getElementById('cst-alert');

        alrt.className = 'alert d-none';
        btn.disabled   = true;
        btn.innerHTML  = '<span class="spinner-border spinner-border-sm me-2"></span>Enregistrement…';

        fetch('<?= BASE_URL ?>patients/save-constantes', {
            method: 'POST',
            body: new FormData(form)
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                const p = d.data;
                // Mise à jour widgets dossier
                if (p.temperature != null)
                    document.getElementById('vt-temp').innerHTML  = p.temperature + ' <small>°C</small>';
                if (p.poids != null)
                    document.getElementById('vt-poids').innerHTML = p.poids + ' <small>kg</small>';
                if (!isEnfant) {
                    if (p.pouls != null)
                        document.getElementById('vt-pouls').innerHTML   = p.pouls + ' <small>bpm</small>';
                    if (p.sys != null || p.dia != null)
                        document.getElementById('vt-tension').innerHTML =
                            (p.sys||'--') + '/' + (p.dia||'--') + ' <small class="fs-6">mmHg</small>';
                }

                alrt.textContent = '✓ Constantes enregistrées avec succès.';
                alrt.className   = 'alert alert-success';

                setTimeout(() => {
                    bootstrap.Modal.getInstance(document.getElementById('modalConstantes')).hide();
                    alrt.className = 'alert d-none';
                    form.reset();
                    if (isEnfant) {
                        const b = document.getElementById('cst-temp-badge');
                        if (b) b.innerHTML = '';
                    }
                }, 1400);
            } else {
                alrt.textContent = 'Erreur : ' + (d.message || 'Echec de l\'enregistrement.');
                alrt.className   = 'alert alert-danger';
            }
        })
        .catch(() => {
            alrt.textContent = 'Erreur réseau. Veuillez réessayer.';
            alrt.className   = 'alert alert-danger';
        })
        .finally(() => {
            btn.disabled  = false;
            btn.innerHTML = '<i class="bi bi-save2 me-2"></i>Enregistrer';
        });
    });
})();
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>