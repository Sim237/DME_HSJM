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

/* ============================================================
   dashboard_secretariat.php
   Interface secrétariat du Laboratoire
   Rôle : SECRETAIRE_LABO (+ LABORANTIN / ADMIN en lecture)
============================================================ */
require_once __DIR__ . '/../layouts/header.php';

$demandes_secretariat  = $demandes_secretariat  ?? [];
$historique_facturation = $historique_facturation ?? [];
$nb_total_jour         = $nb_total_jour          ?? 0;
$nb_non_facture        = $nb_non_facture         ?? 0;
$nb_facture_jour       = $nb_facture_jour        ?? 0;

// Helpers
function _ageCalc($dob) {
    if (!$dob) return '—';
    return (new DateTime())->diff(new DateTime($dob))->y . ' ans';
}
function _typeClientBadge($type) {
    $map = [
        'PAYANT_COMPTANT'     => ['label' => 'Payant',      'class' => 'tc-payant'],
        'ASSURANCE'           => ['label' => 'Assurance',   'class' => 'tc-assurance'],
        'BON_PRISE_EN_CHARGE' => ['label' => 'BPC',         'class' => 'tc-bpc'],
        'AGENTS_PHP'          => ['label' => 'Agent PHP',   'class' => 'tc-agents'],
        'FAMILLE_PHP'         => ['label' => 'Famille PHP', 'class' => 'tc-famille'],
    ];
    $info = $map[$type] ?? ['label' => ($type ?: '—'), 'class' => 'tc-default'];
    return '<span class="badge-client ' . ($info['class'] ?? 'tc-default') . '">' . htmlspecialchars($info['label'] ?? '—') . '</span>';
}
function _modePaiement($mode) {
    $map = ['ESPECES' => '💵 Espèces', 'ASSURANCE' => '🏥 Assurance',
            'BON_PRISE_EN_CHARGE' => '📋 BPC', 'VIREMENT' => '🏦 Virement'];
    return $map[$mode] ?? htmlspecialchars($mode ?: '—');
}
?>
<style>
/* ── Mode cockpit plein écran ── */
.sidebar, nav.sidebar { display: none !important; }
main, .main-content   { margin-left: 0 !important; width: 100% !important; }
body { background: #f5f3ff; font-family: 'Plus Jakarta Sans', sans-serif; color: #1e293b; }

/* ── Header violet ── */
.sec-header {
    background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
    color: #fff; padding: 12px 30px;
    box-shadow: 0 4px 18px rgba(79,70,229,.35);
    position: sticky; top: 0; z-index: 200;
}
#sec-clock {
    font-family: 'Courier New', monospace; font-size: 2rem; font-weight: bold;
    color: #a5f3fc; text-shadow: 0 0 14px rgba(165,243,252,.5); letter-spacing: 3px;
}

/* ── KPIs ── */
.kpi-wrap { display: flex; gap: 16px; padding: 20px 28px 8px; flex-wrap: wrap; }
.kpi-card {
    flex: 1; min-width: 180px; background: #fff; border-radius: 18px;
    padding: 18px 22px; box-shadow: 0 4px 14px rgba(0,0,0,.06);
    display: flex; align-items: center; gap: 16px;
    border-bottom: 5px solid #e2e8f0; transition: transform .2s;
}
.kpi-card:hover { transform: translateY(-3px); }
.kpi-icon {
    width: 52px; height: 52px; border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.5rem; flex-shrink: 0;
}
.kpi-label { font-size: .72rem; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; }
.kpi-value { font-size: 1.9rem; font-weight: 800; line-height: 1.1; color: #0f172a; }
.kpi-total   { border-color: #6366f1; } .kpi-total .kpi-icon   { background: #eef2ff; color: #6366f1; }
.kpi-attente { border-color: #f59e0b; } .kpi-attente .kpi-icon { background: #fffbeb; color: #f59e0b; }
.kpi-facture { border-color: #10b981; } .kpi-facture .kpi-icon { background: #ecfdf5; color: #10b981; }

/* ── Tabs ── */
.sec-tabs {
    display: flex; gap: 4px; padding: 16px 28px 0;
    border-bottom: 2px solid #e2e8f0; background: #f5f3ff;
}
.sec-tab-btn {
    padding: 9px 24px; border: none; background: transparent;
    border-radius: 12px 12px 0 0; font-weight: 700; font-size: .85rem;
    color: #64748b; cursor: pointer; transition: .18s;
}
.sec-tab-btn.active {
    background: #fff; color: #4f46e5;
    border-bottom: 3px solid #4f46e5; margin-bottom: -2px;
}
.sec-tab-pane { display: none; padding: 22px 28px; }
.sec-tab-pane.active { display: block; }

/* ── Cards demande ── */
.dem-card {
    background: #fff; border-radius: 16px;
    box-shadow: 0 3px 14px rgba(0,0,0,.06);
    border-left: 5px solid #6366f1;
    margin-bottom: 16px; overflow: hidden;
    transition: box-shadow .2s, border-color .2s;
}
.dem-card:hover { box-shadow: 0 6px 22px rgba(79,70,229,.12); }
.dem-card.urgent-card   { border-left-color: #ef4444; }
.dem-card.facture-card  { border-left-color: #10b981; }
.dem-card-header {
    padding: 13px 20px; background: #f8f7ff;
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: 10px;
}
.dem-card.urgent-card  .dem-card-header { background: #fff0f0; }
.dem-card.facture-card .dem-card-header { background: #f0fdf4; }
.dem-card-body { padding: 16px 20px; }

/* ── Grille infos patient ── */
.patient-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(175px, 1fr));
    gap: 12px;
}
.pinfo-label { font-size: .68rem; font-weight: 700; text-transform: uppercase; color: #94a3b8; letter-spacing: .5px; }
.pinfo-value { font-size: .88rem; font-weight: 600; color: #1e293b; word-break: break-word; }

/* ── Examens ── */
.exam-badge {
    display: inline-block; background: #eef2ff; color: #4f46e5;
    border-radius: 6px; padding: 3px 10px; font-size: .74rem;
    font-weight: 600; margin: 2px;
}

/* ── Statut facturation ── */
.badge-facture     { background: #dcfce7; color: #166534; font-weight: 700; border-radius: 20px; padding: 4px 14px; font-size: .79rem; }
.badge-non-facture { background: #fef9c3; color: #854d0e; font-weight: 700; border-radius: 20px; padding: 4px 14px; font-size: .79rem; }

/* ── Type client ── */
.badge-client { border-radius: 6px; padding: 2px 8px; font-size: .72rem; font-weight: 600; }
.tc-payant    { background: #e0f2fe; color: #0369a1; }
.tc-assurance { background: #f3e8ff; color: #7c3aed; }
.tc-bpc       { background: #fff7ed; color: #c2410c; }
.tc-agents    { background: #fce7f3; color: #9d174d; }
.tc-famille   { background: #ecfdf5; color: #065f46; }
.tc-default   { background: #f1f5f9; color: #475569; }

/* ── Boutons action ── */
.btn-facturer {
    background: #4f46e5; color: #fff; border: none;
    border-radius: 8px; padding: 7px 18px;
    font-size: .82rem; font-weight: 700; cursor: pointer; transition: .18s;
}
.btn-facturer:hover { background: #4338ca; transform: translateY(-1px); }
.btn-annuler-fact {
    background: transparent; color: #ef4444; border: 2px solid #ef4444;
    border-radius: 8px; padding: 5px 14px;
    font-size: .8rem; font-weight: 700; cursor: pointer; transition: .18s;
}
.btn-annuler-fact:hover { background: #fef2f2; }

/* ── Table historique ── */
.hist-table { width: 100%; border-collapse: collapse; }
.hist-table th {
    font-size: .7rem; text-transform: uppercase; color: #64748b;
    font-weight: 700; padding: 10px 14px;
    border-bottom: 2px solid #e2e8f0; letter-spacing: .5px;
    white-space: nowrap;
}
.hist-table td { padding: 11px 14px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
.hist-table tr:hover td { background: #f8f7ff; }

/* ── Filtre barre ── */
.filter-bar { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; margin-bottom: 18px; }
.filter-bar .form-control,
.filter-bar .form-select { border-radius: 10px; border: 1.5px solid #e2e8f0; font-size: .88rem; }

/* ── Empty state ── */
.empty-state { padding: 60px 20px; text-align: center; color: #94a3b8; }
.empty-state i { font-size: 3.2rem; opacity: .2; display: block; margin-bottom: 14px; }

/* ── Toast ── */
.toast-sec {
    position: fixed; bottom: 24px; right: 24px; z-index: 9999;
    min-width: 300px; animation: slideIn .3s ease;
}
@keyframes slideIn { from { opacity:0; transform:translateY(20px); } to { opacity:1; transform:translateY(0); } }
</style>

<main>

<!-- ══════════════════════════════════════════
     HEADER COCKPIT
══════════════════════════════════════════ -->
<div class="sec-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div class="d-flex align-items-center gap-3">
        <div class="bg-white p-2 rounded-circle shadow-sm">
            <img src="<?= BASE_URL ?>public/images/logo_ordre_malte.png" style="height:35px;" alt="Logo">
        </div>
        <div>
            <h4 class="mb-0 fw-bold">SECRÉTARIAT <span style="color:#a5f3fc;">LABORATOIRE</span></h4>
            <small class="opacity-75 fw-semibold">
                Facturation des examens biologiques • HSJM
            </small>
        </div>
    </div>

    <div id="sec-clock">00:00:00</div>

    <div class="d-flex gap-2 align-items-center">
        <span class="text-white opacity-75 small fw-semibold">
            <i class="bi bi-person-circle me-1"></i>
            <?= htmlspecialchars(($_SESSION['user_nom'] ?? '') . ' ' . ($_SESSION['user_prenom'] ?? '')) ?>
        </span>
        <a href="<?= BASE_URL ?>logout" class="btn btn-danger rounded-circle p-2 shadow-sm" title="Déconnexion">
            <i class="bi bi-power"></i>
        </a>
    </div>
</div>

<!-- ══════════════════════════════════════════
     KPIs
══════════════════════════════════════════ -->
<div class="kpi-wrap">
    <div class="kpi-card kpi-total">
        <div class="kpi-icon"><i class="bi bi-file-medical"></i></div>
        <div>
            <div class="kpi-label">Demandes aujourd'hui</div>
            <div class="kpi-value"><?= $nb_total_jour ?></div>
        </div>
    </div>
    <div class="kpi-card kpi-attente">
        <div class="kpi-icon"><i class="bi bi-hourglass-split"></i></div>
        <div>
            <div class="kpi-label">En attente de paiement</div>
            <div class="kpi-value" style="color:#f59e0b;"><?= $nb_non_facture ?></div>
        </div>
    </div>
    <div class="kpi-card kpi-facture">
        <div class="kpi-icon"><i class="bi bi-check-circle-fill"></i></div>
        <div>
            <div class="kpi-label">Facturées aujourd'hui</div>
            <div class="kpi-value" style="color:#10b981;"><?= $nb_facture_jour ?></div>
        </div>
    </div>
    <div class="kpi-card" style="border-color:#6366f1;">
        <div class="kpi-icon" style="background:#eef2ff;color:#6366f1;"><i class="bi bi-list-check"></i></div>
        <div>
            <div class="kpi-label">Total demandes en cours</div>
            <div class="kpi-value"><?= count($demandes_secretariat) ?></div>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════
     TABS
══════════════════════════════════════════ -->
<div class="sec-tabs">
    <button class="sec-tab-btn active" id="tab-attente-btn" onclick="switchSecTab('attente')">
        <i class="bi bi-clock me-1"></i>Demandes en cours
        <?php if ($nb_non_facture > 0): ?>
        <span class="badge bg-warning text-dark ms-1" style="font-size:.7rem;"><?= $nb_non_facture ?></span>
        <?php endif; ?>
    </button>
    <button class="sec-tab-btn" id="tab-historique-btn" onclick="switchSecTab('historique')">
        <i class="bi bi-clock-history me-1"></i>Historique de facturation
    </button>
</div>

<!-- ══════════════════════════════════════════
     TAB 1 : DEMANDES EN COURS
══════════════════════════════════════════ -->
<div class="sec-tab-pane active" id="tab-attente">

    <!-- Barre de filtre -->
    <div class="filter-bar">
        <input type="text" id="recherchePatientSec" class="form-control"
               style="max-width:300px;" placeholder="🔍 Nom du patient ou N° dossier…"
               oninput="filtrerDemandesSec()">
        <select id="filtreClientType" class="form-select" style="max-width:220px;"
                onchange="filtrerDemandesSec()">
            <option value="">Tous les types</option>
            <option value="PAYANT_COMPTANT">Payant</option>
            <option value="ASSURANCE">Assurance</option>
            <option value="BON_PRISE_EN_CHARGE">BPC</option>
            <option value="AGENTS_PHP">Agent PHP</option>
            <option value="FAMILLE_PHP">Famille PHP</option>
        </select>
        <select id="filtreFacturation" class="form-select" style="max-width:200px;"
                onchange="filtrerDemandesSec()">
            <option value="">Tous les paiements</option>
            <option value="NON_FACTURE">Non facturé uniquement</option>
            <option value="FACTURE">Facturé uniquement</option>
        </select>
        <button class="btn btn-sm btn-outline-secondary ms-auto" onclick="location.reload()">
            <i class="bi bi-arrow-clockwise"></i> Actualiser
        </button>
    </div>

    <!-- Liste des demandes -->
    <div id="listeDemandes">
    <?php if (empty($demandes_secretariat)): ?>
        <div class="empty-state">
            <i class="bi bi-inbox"></i>
            <div class="fw-semibold fs-5">Aucune demande en cours</div>
            <div class="small mt-1 text-muted">
                Les demandes d'examens envoyées au laboratoire apparaîtront ici.
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($demandes_secretariat as $d):
            $isUrgent  = (int)($d['nb_urgents'] ?? 0) > 0;
            $isFacture = ($d['statut_facturation'] ?? 'NON_FACTURE') === 'FACTURE';
            $nomP      = htmlspecialchars($d['nom'] . ' ' . $d['prenom']);
            $sexeIcon  = ($d['sexe'] ?? '') === 'M' ? '♂' : '♀';
            $age       = _ageCalc($d['date_naissance'] ?? null);
            $examens   = array_filter(array_map('trim', explode(',', $d['liste_examens'] ?? '')));
        ?>
        <div class="dem-card <?= $isUrgent ? 'urgent-card' : '' ?> <?= $isFacture ? 'facture-card' : '' ?>"
             data-facturation="<?= htmlspecialchars($d['statut_facturation'] ?? 'NON_FACTURE') ?>"
             data-type="<?= htmlspecialchars($d['type_client'] ?? '') ?>"
             data-patient="<?= strtolower(htmlspecialchars($d['nom'] . ' ' . $d['prenom'])) ?>"
             data-dossier="<?= strtolower(htmlspecialchars($d['dossier_numero'] ?? '')) ?>"
             id="dem-card-<?= (int)$d['id'] ?>">

            <!-- En-tête -->
            <div class="dem-card-header">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <?php if ($isUrgent): ?>
                    <span class="badge bg-danger"><i class="bi bi-exclamation-triangle me-1"></i>URGENT</span>
                    <?php endif; ?>
                    <strong class="fs-6"><?= $nomP ?></strong>
                    <span class="text-muted small"><?= $sexeIcon ?> <?= $age ?></span>
                    <?= _typeClientBadge($d['type_client'] ?? '') ?>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="text-muted small">
                        <i class="bi bi-calendar2 me-1"></i>
                        <?= date('d/m/Y H:i', strtotime($d['date_creation'])) ?>
                    </span>
                    <?php if ($isFacture): ?>
                        <span class="badge-facture">
                            <i class="bi bi-check-circle-fill me-1"></i>Payé
                            <?php if (!empty($d['mode_paiement_labo'])): ?>
                            — <?= _modePaiement($d['mode_paiement_labo']) ?>
                            <?php endif; ?>
                        </span>
                    <?php else: ?>
                        <span class="badge-non-facture">
                            <i class="bi bi-clock me-1"></i>Non facturé
                        </span>
                    <?php endif; ?>
                    <span class="badge bg-secondary-subtle text-secondary small">#<?= (int)$d['id'] ?></span>
                </div>
            </div>

            <!-- Corps -->
            <div class="dem-card-body">
                <div class="row g-3 align-items-start">

                    <!-- Informations personnelles du patient -->
                    <div class="col-xl-7">
                        <div class="patient-grid">
                            <div>
                                <div class="pinfo-label"><i class="bi bi-telephone me-1"></i>Téléphone</div>
                                <div class="pinfo-value"><?= htmlspecialchars($d['telephone'] ?? '—') ?></div>
                            </div>
                            <div>
                                <div class="pinfo-label"><i class="bi bi-file-earmark-text me-1"></i>N° Dossier</div>
                                <div class="pinfo-value"><?= htmlspecialchars($d['dossier_numero'] ?? '—') ?></div>
                            </div>
                            <div>
                                <div class="pinfo-label"><i class="bi bi-credit-card me-1"></i>N° Assuré / Sage</div>
                                <div class="pinfo-value"><?= htmlspecialchars($d['numero_assure'] ?? '—') ?></div>
                            </div>
                            <div>
                                <div class="pinfo-label"><i class="bi bi-geo-alt me-1"></i>Adresse</div>
                                <div class="pinfo-value" style="font-size:.82rem;">
                                    <?= htmlspecialchars($d['adresse'] ?? '—') ?>
                                </div>
                            </div>
                            <?php if (!empty($d['medecin_nom'])): ?>
                            <div>
                                <div class="pinfo-label"><i class="bi bi-person-badge me-1"></i>Médecin prescripteur</div>
                                <div class="pinfo-value">
                                    Dr. <?= htmlspecialchars($d['medecin_nom'] . ' ' . $d['medecin_prenom']) ?>
                                </div>
                            </div>
                            <?php endif; ?>
                            <?php if ($isFacture && !empty($d['sec_nom'])): ?>
                            <div>
                                <div class="pinfo-label"><i class="bi bi-person-check me-1"></i>Facturé par</div>
                                <div class="pinfo-value text-success">
                                    <?= htmlspecialchars($d['sec_nom'] . ' ' . $d['sec_prenom']) ?>
                                    <?php if (!empty($d['date_facturation'])): ?>
                                    <small class="text-muted d-block">
                                        <?= date('d/m/Y H:i', strtotime($d['date_facturation'])) ?>
                                    </small>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Examens + bouton action -->
                    <div class="col-xl-5">
                        <div class="pinfo-label mb-2">
                            <i class="bi bi-flask me-1"></i>
                            Examens demandés
                            <span class="badge bg-info-subtle text-info ms-1"><?= count($examens) ?></span>
                        </div>
                        <div class="mb-3">
                            <?php foreach ($examens as $ex): ?>
                            <span class="exam-badge"><?= htmlspecialchars($ex) ?></span>
                            <?php endforeach; ?>
                            <?php if (empty($examens)): ?>
                            <span class="text-muted small">—</span>
                            <?php endif; ?>
                        </div>

                        <div class="d-flex gap-2 justify-content-end flex-wrap mt-3">
                            <?php if (!$isFacture): ?>
                            <button class="btn-facturer"
                                    onclick="ouvrirModalFacturer(
                                        <?= (int)$d['id'] ?>,
                                        '<?= addslashes($nomP) ?>',
                                        '<?= addslashes(implode(', ', $examens)) ?>'
                                    )">
                                <i class="bi bi-receipt me-1"></i> Facturer
                            </button>
                            <?php else: ?>
                            <button class="btn-annuler-fact"
                                    onclick="annulerFacturation(<?= (int)$d['id'] ?>)">
                                <i class="bi bi-x-circle me-1"></i> Annuler paiement
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>

                </div><!-- /.row -->
            </div><!-- /.dem-card-body -->
        </div><!-- /.dem-card -->
        <?php endforeach; ?>
    <?php endif; ?>
    </div><!-- /#listeDemandes -->
</div><!-- /.sec-tab-pane #tab-attente -->

<!-- ══════════════════════════════════════════
     TAB 2 : HISTORIQUE DE FACTURATION
══════════════════════════════════════════ -->
<div class="sec-tab-pane" id="tab-historique">

    <!-- Filtres -->
    <form method="GET" action="<?= BASE_URL ?>laboratoire/secretariat" class="filter-bar mb-4">
        <input type="hidden" name="tab" value="historique">

        <!-- Recherche par nom ou N° dossier -->
        <input type="text" name="dossier" class="form-control" style="max-width:220px;"
               placeholder="🔍 N° dossier ou nom…"
               value="<?= htmlspecialchars($_GET['dossier'] ?? '') ?>">

        <label class="small fw-semibold mb-0">Du</label>
        <input type="date" name="debut" class="form-control" style="max-width:160px;"
               value="<?= htmlspecialchars($_GET['debut'] ?? date('Y-m-d', strtotime('-30 days'))) ?>">
        <label class="small fw-semibold mb-0">au</label>
        <input type="date" name="fin" class="form-control" style="max-width:160px;"
               value="<?= htmlspecialchars($_GET['fin'] ?? date('Y-m-d')) ?>">

        <select name="statut_facturation" class="form-select" style="max-width:190px;">
            <option value="">Tous</option>
            <option value="FACTURE"     <?= (($_GET['statut_facturation'] ?? '') === 'FACTURE')     ? 'selected' : '' ?>>Facturé</option>
            <option value="NON_FACTURE" <?= (($_GET['statut_facturation'] ?? '') === 'NON_FACTURE') ? 'selected' : '' ?>>Non facturé</option>
        </select>
        <button type="submit" class="btn btn-sm fw-bold px-4"
                style="background:#4f46e5;color:#fff;border-radius:8px;">
            <i class="bi bi-funnel me-1"></i>Filtrer
        </button>
        <?php if (!empty($_GET['dossier'])): ?>
        <a href="<?= BASE_URL ?>laboratoire/secretariat?tab=historique" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-x me-1"></i>Effacer
        </a>
        <?php endif; ?>
    </form>

    <?php if (empty($historique_facturation)): ?>
    <div class="empty-state">
        <i class="bi bi-clock-history"></i>
        <div class="fw-semibold fs-5">Aucun enregistrement trouvé</div>
        <div class="small mt-1 text-muted">Modifiez les filtres de date pour élargir la recherche.</div>
    </div>
    <?php else: ?>
    <div class="bg-white rounded-3 shadow-sm overflow-hidden">
        <div class="table-responsive">
            <table class="hist-table">
                <thead>
                    <tr>
                        <th>N°</th>
                        <th>Patient</th>
                        <th>Type client</th>
                        <th>N° Assuré</th>
                        <th>Examens</th>
                        <th>Date demande</th>
                        <th>Statut paiement</th>
                        <th>Mode paiement</th>
                        <th>Secrétaire</th>
                        <th>Date facturation</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($historique_facturation as $h): ?>
                <tr>
                    <td>
                        <span class="badge bg-secondary-subtle text-secondary fw-bold">#<?= (int)$h['id'] ?></span>
                    </td>
                    <td>
                        <div class="fw-bold"><?= htmlspecialchars($h['nom'] . ' ' . $h['prenom']) ?></div>
                        <small class="text-muted"><?= htmlspecialchars($h['dossier_numero']) ?></small>
                    </td>
                    <td><?= _typeClientBadge($h['type_client'] ?? '') ?></td>
                    <td>
                        <small class="text-muted"><?= htmlspecialchars($h['numero_assure'] ?? '—') ?></small>
                    </td>
                    <td style="max-width:220px;">
                        <small class="text-muted" title="<?= htmlspecialchars($h['liste_examens'] ?? '') ?>">
                            <?php
                            $exStr = $h['liste_examens'] ?? '—';
                            echo htmlspecialchars(strlen($exStr) > 80 ? substr($exStr, 0, 77) . '…' : $exStr);
                            ?>
                        </small>
                    </td>
                    <td>
                        <small><?= date('d/m/Y H:i', strtotime($h['date_creation'])) ?></small>
                    </td>
                    <td>
                        <?php if (($h['statut_facturation'] ?? '') === 'FACTURE'): ?>
                            <span class="badge-facture"><i class="bi bi-check-circle-fill me-1"></i>Payé</span>
                        <?php else: ?>
                            <span class="badge-non-facture"><i class="bi bi-clock me-1"></i>Non facturé</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?= _modePaiement($h['mode_paiement_labo'] ?? '') ?>
                    </td>
                    <td>
                        <small><?= !empty($h['sec_nom']) ? htmlspecialchars($h['sec_nom'] . ' ' . $h['sec_prenom']) : '—' ?></small>
                    </td>
                    <td>
                        <small><?= !empty($h['date_facturation']) ? date('d/m/Y H:i', strtotime($h['date_facturation'])) : '—' ?></small>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div><!-- /.sec-tab-pane #tab-historique -->

<!-- ══════════════════════════════════════════
     MODAL FACTURATION
══════════════════════════════════════════ -->
<div class="modal fade" id="modalFacturer" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4" style="overflow:hidden;">

            <!-- En-tête -->
            <div class="modal-header border-0 px-4 pt-4 pb-2"
                 style="background:linear-gradient(135deg,#4f46e5,#7c3aed);">
                <div>
                    <h5 class="fw-bold mb-0 text-white">
                        <i class="bi bi-receipt me-2"></i>Facturation d'examen
                    </h5>
                    <small class="text-white opacity-75" id="modalFactPatient">—</small>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body px-4 pb-4">

                <!-- Checklist des examens — cocher ceux PAYÉS par le patient -->
                <div class="mb-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <label class="pinfo-label mb-0">
                            <i class="bi bi-check2-square me-1"></i>Examens à facturer
                        </label>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3"
                                    onclick="cocherTousExamens(true)" style="font-size:.72rem;">
                                <i class="bi bi-check-all me-1"></i>Tout cocher
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3"
                                    onclick="cocherTousExamens(false)" style="font-size:.72rem;">
                                <i class="bi bi-x me-1"></i>Tout décocher
                            </button>
                        </div>
                    </div>
                    <small class="text-muted d-block mb-2" style="font-size:.78rem;">
                        Cochez uniquement les examens que le patient a effectivement payés.
                        Les autres pourront être facturés plus tard.
                    </small>
                    <div id="modalFactExamensListe"
                         style="max-height:280px; overflow-y:auto; border:1px solid #e2e8f0; border-radius:10px; padding:6px;">
                        <div class="text-center py-4 text-muted" style="font-size:.85rem;">
                            <span class="spinner-border spinner-border-sm me-2"></span>Chargement des examens…
                        </div>
                    </div>
                    <!-- Récapitulatif total -->
                    <div class="d-flex justify-content-between align-items-center mt-2 px-2"
                         style="font-size:.85rem;color:#475569;">
                        <span id="factCheckCounter">0 / 0 examens cochés</span>
                        <strong id="factTotalMontant" style="color:#16a34a;">— FCFA</strong>
                    </div>
                </div>

                <hr class="my-3" style="opacity:.15;">


                <!-- Mode de paiement -->
                <div class="mb-3">
                    <label class="pinfo-label mb-2 d-block">
                        Mode de paiement <span class="text-danger">*</span>
                    </label>

                    <div class="d-flex flex-column gap-2">
                        <label class="d-flex align-items-center gap-2 p-3 border rounded-3 cursor-pointer"
                               style="cursor:pointer;" id="lbl-especes">
                            <input class="form-check-input mt-0" type="radio" name="modePaiement"
                                   value="ESPECES" id="modeEspeces" checked>
                            <span class="fw-semibold">💵 Espèces</span>
                        </label>
                        <label class="d-flex align-items-center gap-2 p-3 border rounded-3"
                               style="cursor:pointer;" id="lbl-assurance">
                            <input class="form-check-input mt-0" type="radio" name="modePaiement"
                                   value="ASSURANCE" id="modeAssurance">
                            <span class="fw-semibold">🏥 Assurance</span>
                        </label>
                        <label class="d-flex align-items-center gap-2 p-3 border rounded-3"
                               style="cursor:pointer;" id="lbl-bpc">
                            <input class="form-check-input mt-0" type="radio" name="modePaiement"
                                   value="BON_PRISE_EN_CHARGE" id="modeBPC">
                            <span class="fw-semibold">📋 Bon de prise en charge</span>
                        </label>
                        <label class="d-flex align-items-center gap-2 p-3 border rounded-3"
                               style="cursor:pointer;" id="lbl-virement">
                            <input class="form-check-input mt-0" type="radio" name="modePaiement"
                                   value="VIREMENT" id="modeVirement">
                            <span class="fw-semibold">🏦 Virement / Mobile Money</span>
                        </label>
                    </div>
                </div>

                <!-- Boutons -->
                <div class="d-flex gap-2 mt-4">
                    <button type="button" class="btn btn-light rounded-pill px-4"
                            data-bs-dismiss="modal">Annuler</button>
                    <button type="button" id="btnConfirmerFacturation"
                            class="btn rounded-pill px-4 flex-grow-1 fw-bold text-white"
                            style="background:#4f46e5;"
                            onclick="confirmerFacturation()">
                        <i class="bi bi-check2 me-1"></i> Confirmer le paiement
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════
     JAVASCRIPT
══════════════════════════════════════════ -->
<script>
const BASE_SEC = '<?= BASE_URL ?>';
let factDemandeId = null;

/* ── Horloge temps réel ────────────────────── */
(function tickClock() {
    const now = new Date();
    const p = n => String(n).padStart(2, '0');
    document.getElementById('sec-clock').textContent =
        p(now.getHours()) + ':' + p(now.getMinutes()) + ':' + p(now.getSeconds());
    setTimeout(tickClock, 1000);
})();

/* ── Navigation par onglets ────────────────── */
function switchSecTab(tab) {
    document.querySelectorAll('.sec-tab-pane').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.sec-tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + tab).classList.add('active');
    document.getElementById('tab-' + tab + '-btn').classList.add('active');
}
<?php if (($_GET['tab'] ?? '') === 'historique'): ?>
document.addEventListener('DOMContentLoaded', () => switchSecTab('historique'));
<?php endif; ?>

/* ── Filtrage en temps réel ────────────────── */
function filtrerDemandesSec() {
    const q       = document.getElementById('recherchePatientSec').value.toLowerCase().trim();
    const fact    = document.getElementById('filtreFacturation').value;
    const type    = document.getElementById('filtreClientType').value;
    document.querySelectorAll('.dem-card').forEach(card => {
        const nomMatch    = (card.dataset.patient || '').includes(q);
        const dossierMatch= (card.dataset.dossier  || '').includes(q);
        const factMatch   = !fact || card.dataset.facturation === fact;
        const typeMatch   = !type || card.dataset.type === type;
        card.style.display = ((nomMatch || dossierMatch) && factMatch && typeMatch) ? '' : 'none';
    });
}

/* ── Ouverture du modal facturation ─────────── */
function ouvrirModalFacturer(demandeId, nomPatient, examens) {
    factDemandeId = demandeId;
    document.getElementById('modalFactPatient').textContent = nomPatient;
    document.getElementById('modeEspeces').checked = true;
    const btn = document.getElementById('btnConfirmerFacturation');
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-check2 me-1"></i> Confirmer le paiement';

    // Réinitialiser la liste
    document.getElementById('modalFactExamensListe').innerHTML =
        '<div class="text-center py-4 text-muted"><span class="spinner-border spinner-border-sm me-2"></span>Chargement des examens…</div>';
    document.getElementById('factCheckCounter').textContent = '0 / 0 examens cochés';
    document.getElementById('factTotalMontant').textContent = '— FCFA';

    // Afficher le modal immédiatement
    new bootstrap.Modal(document.getElementById('modalFacturer')).show();

    // Charger la liste des examens
    fetch(BASE_SEC + 'laboratoire/examens-demande/' + demandeId, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) {
            document.getElementById('modalFactExamensListe').innerHTML =
                '<div class="alert alert-danger m-2 mb-0">' + (data.message || 'Erreur') + '</div>';
            return;
        }
        if (!data.migration_ok) {
            document.getElementById('modalFactExamensListe').innerHTML =
                '<div class="alert alert-warning m-2 mb-0">' +
                '⚠ Migration non exécutée. Lancez <code>run_migration_facturation_par_examen.php</code></div>';
            return;
        }
        rendrerExamensCheckList(data.items || []);
    })
    .catch(() => {
        document.getElementById('modalFactExamensListe').innerHTML =
            '<div class="alert alert-danger m-2 mb-0">Erreur réseau lors du chargement</div>';
    });
}

/* ── Rendu de la checklist des examens ─────── */
function rendrerExamensCheckList(items) {
    const cont = document.getElementById('modalFactExamensListe');
    if (!items.length) {
        cont.innerHTML = '<div class="text-center py-3 text-muted">Aucun examen pour cette demande.</div>';
        return;
    }
    let html = '';
    items.forEach(ex => {
        const isFacture = (ex.facture == 1);
        const tarif = parseFloat(ex.tarif || 0) || 0;
        const tarifFmt = tarif > 0 ? Math.round(tarif).toLocaleString('fr-FR').replace(/,/g,' ') + ' FCFA' : '—';
        const urgent = ex.urgent == 1 ? '<span class="badge rounded-pill ms-2" style="background:#fee2e2;color:#dc2626;font-size:.6rem;">⚡ URG</span>' : '';
        html += `
        <label class="d-flex align-items-center gap-2 px-2 py-2 rounded-2 mb-1"
               style="cursor:pointer; ${isFacture ? 'background:#f0fdf4;' : ''} transition:background .15s;"
               onmouseover="this.style.background='#f8fafc';"
               onmouseout="this.style.background='${isFacture ? '#f0fdf4' : ''}';">
            <input type="checkbox" class="form-check-input fact-ex-check"
                   data-id="${ex.id}" data-tarif="${tarif}"
                   ${isFacture ? 'checked' : ''}
                   onchange="majCompteurExamens()">
            <div class="flex-grow-1" style="min-width:0;">
                <div class="fw-semibold" style="font-size:.86rem;color:#1e293b;">
                    ${escHtml(ex.nom_examen || 'Examen')}
                    ${urgent}
                    ${isFacture ? '<span class="badge rounded-pill ms-2" style="background:#dcfce7;color:#16a34a;font-size:.6rem;">✓ Facturé</span>' : ''}
                </div>
                <small class="text-muted">${escHtml(ex.categorie || '')}</small>
            </div>
            <div class="text-end fw-bold" style="font-size:.82rem;color:#475569;min-width:90px;">${tarifFmt}</div>
        </label>`;
    });
    cont.innerHTML = html;
    majCompteurExamens();
}

function escHtml(s) {
    return String(s ?? '').replace(/[&<>"']/g, c =>
        ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));
}

/* ── Met à jour le compteur et le total montant ── */
function majCompteurExamens() {
    const all = document.querySelectorAll('.fact-ex-check');
    const checked = document.querySelectorAll('.fact-ex-check:checked');
    let total = 0;
    checked.forEach(cb => total += parseFloat(cb.dataset.tarif || 0));
    document.getElementById('factCheckCounter').textContent =
        checked.length + ' / ' + all.length + ' examens cochés';
    document.getElementById('factTotalMontant').textContent =
        Math.round(total).toLocaleString('fr-FR').replace(/,/g,' ') + ' FCFA';
}

/* ── Cocher / décocher tout ── */
function cocherTousExamens(coche) {
    document.querySelectorAll('.fact-ex-check').forEach(cb => cb.checked = coche);
    majCompteurExamens();
}

/* ── Confirmation de la facturation ─────────── */
function confirmerFacturation() {
    const mode = document.querySelector('input[name="modePaiement"]:checked')?.value;
    if (!mode) { alert('Veuillez sélectionner un mode de paiement.'); return; }

    const examensIds = Array.from(document.querySelectorAll('.fact-ex-check:checked'))
                            .map(cb => parseInt(cb.dataset.id, 10));

    // Confirmation si aucun coché (équivaut à annuler la facturation)
    if (examensIds.length === 0) {
        if (!confirm('Aucun examen coché. Cela annulera toute facturation de cette demande. Continuer ?')) return;
    }
    // Confirmation si facturation partielle
    const totalCb = document.querySelectorAll('.fact-ex-check').length;
    if (examensIds.length > 0 && examensIds.length < totalCb) {
        if (!confirm(`Vous facturez ${examensIds.length} examen(s) sur ${totalCb}. Les autres seront marqués "non facturés" et pourront être facturés plus tard. Continuer ?`)) return;
    }

    const btn = document.getElementById('btnConfirmerFacturation');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Enregistrement…';

    fetch(BASE_SEC + 'laboratoire/facturer-examens', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            demande_id: factDemandeId,
            examens_ids: examensIds,
            mode_paiement: mode
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('modalFacturer')).hide();
            _toast(data.message, 'success');
            setTimeout(() => location.reload(), 1300);
        } else {
            alert('Erreur : ' + (data.message || 'Impossible de facturer.'));
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check2 me-1"></i> Confirmer le paiement';
        }
    })
    .catch(() => {
        alert('Erreur réseau. Vérifiez votre connexion.');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check2 me-1"></i> Confirmer le paiement';
    });
}

/* ── Annuler la facturation ─────────────────── */
function annulerFacturation(demandeId) {
    if (!confirm('Confirmer l\'annulation de la facturation de cette demande ?')) return;

    fetch(BASE_SEC + 'laboratoire/facturer/' + demandeId, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ action: 'NON_FACTURE' })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            _toast('Facturation annulée.', 'warning');
            setTimeout(() => location.reload(), 1200);
        } else {
            alert('Erreur : ' + (data.message || ''));
        }
    })
    .catch(() => alert('Erreur réseau.'));
}

/* ── Toast notification ─────────────────────── */
function _toast(message, type = 'success') {
    const colors = {
        success: { bg: '#10b981', icon: '✅' },
        warning: { bg: '#f59e0b', icon: '⚠️' },
        error:   { bg: '#ef4444', icon: '❌' }
    };
    const c = colors[type] || colors.success;
    const el = document.createElement('div');
    el.className = 'toast-sec';
    el.innerHTML = `<div class="d-flex align-items-center gap-2 py-3 px-4 rounded-3 text-white fw-semibold shadow"
                         style="background:${c.bg};">
        ${c.icon} ${message}
    </div>`;
    document.body.appendChild(el);
    setTimeout(() => { el.style.opacity = '0'; el.style.transition = 'opacity .4s'; }, 2600);
    setTimeout(() => el.remove(), 3000);
}
</script>

</main>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
