<?php
require_once __DIR__ . '/../layouts/header.php';

// --- Fonctions et Initialisation ---
if (!function_exists('getInitials')) {
    function getInitials($nom, $prenom) {
        return strtoupper(substr($nom ?? '', 0, 1) . substr($prenom ?? '', 0, 1));
    }
}

// Sécurisation des données patient (reçues du contrôleur)
$patient = $patient ?? [];
$parametres = $parametres ?? null;
$consultations = $consultations ?? [];
$bilans = $bilans ?? []; // Nouvelle variable contenant les résultats de labo

// Calcul de l'âge
$age = 'N/A';
if (!empty($patient['date_naissance'])) {
    $age = date_diff(date_create($patient['date_naissance']), date_create('now'))->y . ' ans';
}
?>

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

    /* Style spécifique résultats anormaux */
    .table-danger-light { background-color: #fff5f5 !important; }
    .text-anormal { color: #dc3545; font-weight: 800; }

     /* Conteneur du tableau */
    .history-card {
        background: #ffffff;
        border-radius: 20px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        overflow: hidden;
    }

    /* Style du tableau Soft */
    .table-soft {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }

    .table-soft thead th {
        background: #f8fafc;
        color: #64748b;
        font-weight: 700;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 1px;
        padding: 16px 24px;
        border-bottom: 1px solid #e2e8f0;
    }

    .table-soft tbody tr {
        transition: all 0.2s ease;
    }

    .table-soft tbody tr:hover {
        background-color: #f1f5f9;
    }

    .table-soft td {
        padding: 18px 24px;
        vertical-align: middle;
        border-bottom: 1px solid #f1f5f9;
        color: #334155;
    }

    /* Formatage de la date */
    .date-cell {
        display: flex;
        align-items: center;
        gap: 10px;
        font-weight: 600;
        color: #1e293b;
    }
    .date-icon {
        color: #94a3b8;
        font-size: 1.1rem;
    }

    /* Badges de Catégorie Dynamiques */
    .badge-care {
        padding: 6px 12px;
        border-radius: 8px;
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0.5px;
    }

    /* Couleurs par type de soin */
    .cat-IV { background: #e0f2fe; color: #0369a1; }       /* Bleu pour intraveineux */
    .cat-PER_OS { background: #dcfce7; color: #15803d; }   /* Vert pour oral */
    .cat-IM { background: #f3e8ff; color: #7e22ce; }       /* Violet pour intramusculaire */
    .cat-NURSING { background: #fef3c7; color: #b45309; }  /* Ambre pour nursing */

    /* Style intervenant */
    .nurse-cell {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 0.85rem;
        font-weight: 500;
    }
    .nurse-avatar {
        width: 28px;
        height: 28px;
        background: #e2e8f0;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.7rem;
        color: #475569;
        font-weight: 700;
    }
</style>

<div class="container-fluid p-4">

    <!-- BARRE D'ACTIONS SUPÉRIEURE -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-0">Dossier Médical</h2>
            <p class="text-muted mb-0">Patient : <?= htmlspecialchars($patient['nom'] . ' ' . $patient['prenom']) ?></p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="<?= BASE_URL ?>dashboard" class="btn btn-outline-secondary btn-sm shadow-sm"><i class="bi bi-arrow-left"></i> Retour</a>

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
            <a href="<?= BASE_URL ?>hospitalisation/planifier-soins/<?= $patient['id'] ?>" class="btn btn-primary btn-sm shadow-sm"><i class="bi bi-calendar-check"></i> Planifier Soins</a>
            <button type="button" class="btn btn-dark btn-sm shadow-sm" data-bs-toggle="modal" data-bs-target="#modalListeFormulaires"><i class="bi bi-file-earmark-text"></i> Mes formulaires</button>
        </div>
    </div>

    <div class="row g-4">
        <!-- COLONNE GAUCHE : PROFIL PATIENT -->
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
                    <div class="info-row"><div class="info-icon text-danger"><i class="bi bi-droplet-fill"></i></div><div><small class="text-muted d-block">Groupe Sanguin</small><strong class="text-danger"><?= $patient['groupe_sanguin'] ?: 'Non renseigné' ?></strong></div></div>
                    <div class="d-grid mt-4">
                        <a href="<?= BASE_URL ?>patients/mesures/<?= $patient['id'] ?>" class="btn btn-primary rounded-pill btn-sm shadow-sm"><i class="bi bi-activity"></i> Prise de Constantes</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- COLONNE DROITE : DONNÉES CLINIQUES -->
        <div class="col-lg-9">

            <!-- 1. WIDGETS DES DERNIÈRES CONSTANTES -->
            <div class="row g-3 mb-4">
                <div class="col-md-3 col-6">
                    <div class="vital-box temp shadow-sm">
                        <span class="vital-label"><i class="bi bi-thermometer-half"></i> Température</span>
                        <div class="vital-value text-danger"><?= isset($parametres['temperature']) ? htmlspecialchars($parametres['temperature']) : '--' ?> <small>°C</small></div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="vital-box tension shadow-sm">
                        <span class="vital-label"><i class="bi bi-heart-pulse"></i> Tension</span>
                        <div class="vital-value text-primary">
                            <?= (isset($parametres['pression_arterielle_systolique']) && $parametres['pression_arterielle_systolique'] > 0) ? $parametres['pression_arterielle_systolique'].'/'.$parametres['pression_arterielle_diastolique'] : '--/--' ?>
                            <small class="fs-6">mmHg</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="vital-box pouls shadow-sm">
                        <span class="vital-label"><i class="bi bi-activity"></i> Pouls</span>
                        <div class="vital-value text-success"><?= $parametres['frequence_cardiaque'] ?? '--' ?> <small>bpm</small></div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="vital-box poids shadow-sm">
                        <span class="vital-label"><i class="bi bi-speedometer2"></i> Poids</span>
                        <div class="vital-value text-dark"><?= $parametres['poids'] ?? '--' ?> <small>kg</small></div>
                    </div>
                </div>
            </div>

            <!-- 2. SYSTÈME D'ONGLETS -->
            <ul class="nav nav-tabs" id="myTab" role="tablist">
                <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-consultations" type="button"><i class="bi bi-journal-text me-2"></i>Consultations</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-antecedents" type="button"><i class="bi bi-clock-history me-2"></i>Antécédents</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-bilans" type="button"><i class="bi bi-flask me-2"></i>Derniers Bilans</button></li>
            </ul>

            <div class="tab-content" id="myTabContent">
                <!-- CONTENU : CONSULTATIONS -->
                <div class="tab-pane fade show active" id="tab-consultations">
                    <?php if(!empty($consultations)): foreach($consultations as $c): ?>
                        <div class="card mb-3 border-0 shadow-sm border-start border-primary border-4">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="fw-bold text-primary mb-1"><?= htmlspecialchars($c['motif_consultation'] ?? 'Consultation') ?></h6>
                                        <p class="small text-dark mb-1"><strong>Diagnostic :</strong> <?= htmlspecialchars($c['diagnostic_principal'] ?? 'N/A') ?></p>
                                        <small class="text-muted">Dr. <?= htmlspecialchars($c['medecin_nom'] ?? 'Inconnu') ?></small>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-light text-dark border mb-2"><?= date('d/m/Y', strtotime($c['date_consultation'])) ?></span><br>
                                        <a href="<?= BASE_URL ?>consultation/recapitulatif/<?= $c['id'] ?>" class="btn btn-sm btn-outline-primary px-3 rounded-pill">Détails</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; else: ?>
                        <div class="text-center py-5 text-muted"><i class="bi bi-folder2-open display-4 opacity-25"></i><p class="mt-2">Aucune consultation enregistrée.</p></div>
                    <?php endif; ?>
                </div>

                <!-- CONTENU : ANTÉCÉDENTS -->
                <div class="tab-pane fade" id="tab-antecedents">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded border mb-3">
                                <h6 class="fw-bold text-danger"><i class="bi bi-exclamation-triangle-fill"></i> Allergies Connues</h6>
                                <p class="mb-0 small"><?= nl2br(htmlspecialchars($patient['allergies'] ?: 'Néant')) ?></p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded border mb-3">
                                <h6 class="fw-bold text-primary"><i class="bi bi-heart-pulse-fill"></i> Antécédents Médicaux</h6>
                                <p class="mb-0 small"><?= nl2br(htmlspecialchars($patient['antecedents_medicaux'] ?: 'Rien à signaler')) ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- CONTENU : DERNIERS BILANS (NOUVEAU) -->
                <div class="tab-pane fade" id="tab-bilans">
                    <?php if (!empty($bilans)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle border-0">
                                <thead class="bg-light">
                                    <tr class="small text-uppercase text-muted">
                                        <th>Date & Examen</th>
                                        <th>Résultat</th>
                                        <th>Valeurs de Réf.</th>
                                        <th>Prescripteur</th>
                                        <th class="text-center">Interprétation</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($bilans as $b):
                                        $isAnormal = ($b['anormal'] == 1);
                                        $rowClass = $isAnormal ? 'table-danger-light' : '';
                                        $textClass = $isAnormal ? 'text-anormal' : 'text-dark';
                                    ?>
                                        <tr class="<?= $rowClass ?>">
                                            <td>
                                                <div class="fw-bold"><?= htmlspecialchars($b['nom_examen']) ?></div>
                                                <small class="text-muted">Demandé le <?= date('d/m/Y', strtotime($b['date_demande'])) ?></small>
                                            </td>
                                            <td>
                                                <span class="fs-5 <?= $textClass ?>">
                                                    <?= $b['valeur_numerique'] ?>
                                                    <small class="fs-6"><?= $b['unite'] ?></small>
                                                </span>
                                            </td>
                                            <td>
                                                <small class="text-muted d-block">
                                                    Min: <?= $b['valeur_normale_min'] ?> / Max: <?= $b['valeur_normale_max'] ?>
                                                </small>
                                            </td>
                                            <td>
                                                <small>Dr. <?= htmlspecialchars($b['medecin_prescripteur'] ?? 'N/A') ?></small>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($isAnormal): ?>
                                                    <span class="badge bg-danger rounded-pill"><i class="bi bi-exclamation-triangle-fill"></i> ANORMAL</span>
                                                <?php else: ?>
                                                    <span class="badge bg-success rounded-pill">NORMAL</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php if(!empty($b['interpretation'])): ?>
                                        <tr class="<?= $rowClass ?> border-0">
                                            <td colspan="5" class="pt-0">
                                                <div class="p-2 bg-white rounded border small text-muted">
                                                    <i class="bi bi-chat-left-text me-2"></i><strong>Note :</strong> <?= htmlspecialchars($b['interpretation']) ?>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-flask display-1 opacity-25"></i>
                            <h5 class="mt-3">Aucun bilan de laboratoire</h5>
                            <p class="small">Les résultats validés apparaîtront ici.</p>
                            <button class="btn btn-info btn-sm text-white rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#modalBilan">Demander un bilan</button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ================= MODALES D'ACTION (FUSIONNÉES) ================= -->

<!-- 1. MODALE DEMANDER BILAN -->
<div class="modal fade" id="modalBilan" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="bi bi-flask me-2"></i> Demander un Bilan</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <ul class="nav nav-pills mb-4 justify-content-center" id="pills-tab-bilan" role="tablist">
                    <li class="nav-item"><button class="nav-link active btn-sm" data-bs-toggle="pill" data-bs-target="#pills-labo" type="button">Laboratoire</button></li>
                    <li class="nav-item"><button class="nav-link btn-sm" data-bs-toggle="pill" data-bs-target="#pills-radio" type="button">Imagerie</button></li>
                </ul>
                <div class="tab-content border-0 p-0 shadow-none">
                    <div class="tab-pane fade show active" id="pills-labo">
                        <form id="formBilanLabo">
                            <input type="hidden" name="patient_id" value="<?= $patient['id'] ?>">
                            <input type="hidden" name="type_bilan" value="laboratoire">
                            <label class="small fw-bold">Examen :</label>
                            <select class="form-select mb-3" name="examen_id" required>
                                <option value="1">NFS (Hématologie)</option>
                                <option value="2">Glycémie à jeun</option>
                                <option value="3">Bilan Rénal (Urée/Créat)</option>
                                <option value="4">Bilan Hépatique</option>
                            </select>
                            <button type="submit" class="btn btn-info w-100 text-white btn-sm shadow-sm">Envoyer au Laboratoire</button>
                        </form>
                    </div>
                    <div class="tab-pane fade" id="pills-radio">
                        <form id="formBilanRadio">
                            <input type="hidden" name="patient_id" value="<?= $patient['id'] ?>">
                            <input type="hidden" name="type_bilan" value="imagerie">
                            <label class="small fw-bold">Partie du corps :</label>
                            <input type="text" name="partie_code" class="form-control mb-3" placeholder="Ex: Thorax, Genou..." required>
                            <button type="submit" class="btn btn-primary w-100 btn-sm shadow-sm">Demander l'Imagerie</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="mt-4">
    <h6 class="fw-bold text-dark mb-3">
        <i class="bi bi-clock-history text-primary me-2"></i>Historique des Soins Effectués
    </h6>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive"> <!-- Empêche le tableau de dépasser -->
            <table class="table table-hover align-middle mb-0" style="min-width: 700px;">
                <thead class="bg-light">
                    <tr class="small text-uppercase text-muted" style="font-size: 0.7rem; letter-spacing: 1px;">
                        <th class="ps-4">Date & Heure</th>
                        <th>Soin Effectué</th>
                        <th>Catégorie</th>
                        <th class="pe-4">Intervenant</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Sécurité : Si $history n'est pas passé par le contrôleur, on crée un tableau vide
                    $history = $history ?? [];

                    if(empty($history)): ?>
                        <tr>
                            <td colspan="4" class="text-center py-5 text-muted small italic">
                                Aucun soin enregistré dans l'historique.
                            </td>
                        </tr>
                    <?php else: foreach($history as $h):
                        $initials = strtoupper(substr($h['infirmier_nom'] ?? 'IN', 0, 2));
                        $catClass = 'cat-' . str_replace(' ', '_', $h['categorie']);
                    ?>
                    <tr style="font-size: 0.9rem;">
                        <td class="ps-4">
                            <span class="fw-bold"><?= date('d/m/Y', strtotime($h['date_execution'])) ?></span>
                            <small class="text-muted">à <?= date('H:i', strtotime($h['date_execution'])) ?></small>
                        </td>
                        <td>
                            <span class="fw-semibold text-dark"><?= htmlspecialchars($h['soin_description']) ?></span>
                        </td>
                        <td>
                            <span class="badge rounded-pill px-3 py-2 <?= $catClass ?>" style="font-size: 0.65rem; background: #eef2ff; color: #4f46e5; border: 1px solid #e0e7ff;">
                                <?= $h['categorie'] ?>
                            </span>
                        </td>
                        <td class="pe-4">
                            <div class="d-flex align-items-center">
                                <div class="rounded-circle bg-secondary bg-opacity-10 d-flex align-items-center justify-content-center me-2" style="width: 25px; height: 25px; font-size: 0.6rem; font-weight: 800;">
                                    <?= $initials ?>
                                </div>
                                <span class="small"><?= htmlspecialchars($h['infirmier_nom'] ?? 'Inconnu') ?></span>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- 2. MODALE TRANSFUSION -->
<div class="modal fade" id="modalTransfusion" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="bi bi-droplet-half me-2"></i> Demande de Transfusion</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formTransfusion">
                <input type="hidden" name="patient_id" value="<?= $patient['id'] ?>">
                <div class="modal-body p-4">
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="small fw-bold">Groupe Sanguin</label>
                            <select name="groupe" id="trans_groupe" class="form-select" required>
                                <option value="">-- Choisir --</option>
                                <?php foreach(['A','B','AB','O'] as $g): ?>
                                    <option value="<?= $g ?>" <?= ($patient['groupe_sanguin'] ?? '') == $g ? 'selected' : '' ?>><?= $g ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="small fw-bold">Rhésus</label>
                            <select name="rhesus" id="trans_rhesus" class="form-select" required onchange="checkBloodStock()">
                                <option value="">-- Choisir --</option>
                                <option value="+">+</option>
                                <option value="-">-</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="small fw-bold">Poches demandées</label>
                            <input type="number" name="quantite" id="trans_qte" class="form-control" value="1" min="1" onchange="checkBloodStock()">
                        </div>
                    </div>
                    <div id="stockStatusBox" class="alert d-none mb-3"></div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-danger px-4" id="btnSubmitTrans">Transmettre la demande</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- SCRIPTS JAVASCRIPT -->
<script>
// 1. Initialisation des onglets Bootstrap
document.addEventListener('DOMContentLoaded', function() {
    var triggerTabList = [].slice.call(document.querySelectorAll('#myTab button'))
    triggerTabList.forEach(function (triggerEl) {
        var tabTrigger = new bootstrap.Tab(triggerEl)
        triggerEl.addEventListener('click', function (event) {
            event.preventDefault(); tabTrigger.show();
        })
    });
});

// 2. Vérification Stock Banque de Sang (AJAX)
function checkBloodStock() {
    const groupe = document.getElementById('trans_groupe').value;
    const rhesus = document.getElementById('trans_rhesus').value;
    const qte = document.getElementById('trans_qte').value;
    const statusBox = document.getElementById('stockStatusBox');

    if (!groupe || !rhesus) return;

    const formData = new FormData();
    formData.append('groupe', groupe);
    formData.append('rhesus', rhesus);
    formData.append('quantite', qte);

    fetch('<?= BASE_URL ?>banque-sang/check-stock', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
        statusBox.classList.remove('d-none', 'alert-success', 'alert-warning', 'alert-danger');
        if (data.status === 'available') {
            statusBox.innerHTML = `<i class="bi bi-check-circle-fill"></i> Sang disponible (${data.dispo} poches).`;
            statusBox.classList.add('alert-success');
        } else {
            statusBox.innerHTML = `<i class="bi bi-exclamation-triangle-fill"></i> Stock insuffisant (${data.dispo} poches). Alerte famille requise.`;
            statusBox.classList.add('alert-warning');
        }
    });
}

// 3. Envoi AJAX des formulaires de Bilan
function setupAjaxForm(formId) {
    const form = document.getElementById(formId);
    if(!form) return;
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const btn = this.querySelector('button[type="submit"]');
        btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Envoi...';
        fetch('<?= BASE_URL ?>bilan/save', { method: 'POST', body: new FormData(this) })
        .then(res => res.json())
        .then(data => {
            if(data.success) { alert("✅ Demande envoyée avec succès !"); location.reload(); }
            else { alert("❌ Erreur : " + data.message); btn.disabled = false; btn.innerHTML = 'Réessayer'; }
        });
    });
}
setupAjaxForm('formBilanLabo');
setupAjaxForm('formBilanRadio');
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>