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
<link rel="stylesheet" href="<?= BASE_URL ?>public/css/bootstrap-icons.css">

<?php
/* ── Services autorisés pour le changement temporaire ── */
$_servicesAutorisesChgtUrg = [];
try {
    $_stmtSrvChgtUrg = $this->db->query("
        SELECT id, nom_service FROM services
        WHERE LOWER(nom_service) LIKE '%urgence%'
           OR (LOWER(nom_service) LIKE '%consult%' AND LOWER(nom_service) LIKE '%ext%')
        ORDER BY nom_service ASC
    ");
    $_servicesAutorisesChgtUrg = $_stmtSrvChgtUrg->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $_eUrg) { /* non bloquant */ }

$_estServiceTemporaireUrg = !empty($_SESSION['service_id_origine']);
$_nomServiceOrigineUrg    = $_SESSION['nom_service_origine'] ?? '';

// Initialisations sécurisées
$patients_assignes   = $patients_assignes   ?? [];
$patients_hospitalises = $patients_hospitalises ?? [];
$resultats_prets     = $resultats_prets     ?? [];
$resultats_imagerie  = $resultats_imagerie  ?? [];
$mes_rdv             = $mes_rdv             ?? [];
$patients_consultes   = $patients_consultes   ?? [];
$patients_crh_pending = $patients_crh_pending ?? [];
$suivi_bilans         = $suivi_bilans         ?? [];
$admissions          = $admissions          ?? [];
$stats               = $stats               ?? ['P1'=>0,'P2'=>0,'P3'=>0,'waiting_med'=>0];
$dossiers_recus      = $dossiers_recus      ?? [];
$dossiers_envoyes    = $dossiers_envoyes    ?? [];
$medecins_liste      = $medecins_liste      ?? [];
$nb_partages_non_lus = $nb_partages_non_lus ?? 0;

// Compteurs badge
$nbAttente    = count($patients_assignes);
$nbHosp       = count($patients_hospitalises);
$nbBilans     = count($resultats_prets);
$nbRdv        = count($mes_rdv);
$nbUrgences   = count($admissions);
$nbConsultes  = count($patients_consultes);
$nbCrh        = count($patients_crh_pending);
?>

<style>
.sidebar { display: none !important; }
#wrapper, .main-wrapper { margin: 0 !important; padding: 0 !important; width: 100% !important; }
main { margin-left: 0 !important; width: 100% !important; min-height: 100vh; background: #f1f5f9; font-family: 'Segoe UI', Roboto, sans-serif; }

/* ── HEADER COCKPIT ── */
.cockpit-header {
    background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 60%, #1e40af 100%);
    height: 68px;
    display: flex; justify-content: space-between; align-items: center;
    padding: 0 24px; color: white;
    box-shadow: 0 4px 20px rgba(0,0,0,0.3);
    position: sticky; top: 0; z-index: 1050;
}
#digital-clock { font-family: monospace; font-size: 1.9rem; font-weight: bold; color: #00ff88; text-shadow: 0 0 10px rgba(0,255,136,0.35); }
.btn-new-adm { background: #dc2626; color: #fff; border: none; padding: 9px 18px; border-radius: 50px; font-weight: 700; font-size: .82rem; display: flex; align-items: center; gap: 7px; cursor: pointer; text-decoration: none; }
.btn-logout-c { width: 40px; height: 40px; background: rgba(255,255,255,.15); color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; text-decoration: none; border: 1px solid rgba(255,255,255,.25); }

/* ── STATS ── */
.stats-bar { display: flex; gap: 16px; padding: 18px 24px; background: #fff; border-bottom: 1px solid #e2e8f0; flex-wrap: wrap; }
.stat-box { flex: 1; min-width: 130px; background: #f8fafc; border-radius: 12px; padding: 14px 16px; display: flex; align-items: center; gap: 12px; border-bottom: 4px solid #cbd5e1; }
.stat-box.p1 { border-bottom-color: #ef4444; } .stat-box.p2 { border-bottom-color: #f97316; }
.stat-box.wait { border-bottom-color: #3b82f6; } .stat-box.p3 { border-bottom-color: #22c55e; }
.stat-box.urg { border-bottom-color: #ef4444; }
.stat-icon { width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; flex-shrink: 0; }
.stat-num { font-size: 1.8rem; font-weight: 800; line-height: 1; color: #1e293b; }
.stat-lbl { font-size: 0.68rem; font-weight: 700; color: #64748b; text-transform: uppercase; }

/* ── ONGLETS ── */
.main-tabs { background: #fff; border-bottom: 2px solid #e2e8f0; position: sticky; top: 68px; z-index: 1040; overflow-x: auto; }
.main-tabs .nav-link {
    color: #64748b; font-weight: 700; font-size: .82rem;
    padding: 13px 18px; border: none; border-radius: 0;
    border-bottom: 3px solid transparent; white-space: nowrap;
    display: inline-flex; align-items: center; gap: 7px; transition: all .2s;
}
.main-tabs .nav-link:hover { color: #1e40af; background: #f8fafc; }
.main-tabs .nav-link.active { color: #1e40af; border-bottom-color: #1e40af; }
.tab-badge { background: #ef4444; color: #fff; border-radius: 20px; padding: 1px 7px; font-size: .66rem; font-weight: 800; }
.tab-badge.orange { background: #f97316; } .tab-badge.blue { background: #3b82f6; } .tab-badge.green { background: #22c55e; }
.tab-pane-body { padding: 22px 24px; min-height: calc(100vh - 180px); }

/* ── TABLE URGENCES ── */
.table-urg { width: 100%; border-collapse: collapse; }
.table-urg th { background: #f8f9fa; color: #1e40af; padding: 13px 14px; font-size: .78rem; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; font-weight: 800; }
.table-urg td { padding: 13px 14px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
.table-urg tr:hover td { background: #f8fafc; }
.vitals-row { display: flex; gap: 8px; flex-wrap: wrap; }
.v-block { background: #f8fafc; padding: 4px 10px; border-radius: 6px; text-align: center; min-width: 65px; border: 1px solid #e2e8f0; }
.v-block strong { display: block; font-size: .92rem; color: #1e293b; }
.v-block small { font-size: .58rem; color: #94a3b8; text-transform: uppercase; font-weight: 700; }
.triage-pill { display: inline-block; padding: 3px 12px; border-radius: 20px; font-size: .72rem; font-weight: 800; color: #fff; }
.triage-1{background:#ef4444}.triage-2{background:#f97316}.triage-3{background:#eab308;color:#1e293b}.triage-4{background:#22c55e}.triage-5{background:#3b82f6}
.btn-examine { background: #1e40af; color: #fff; padding: 7px 14px; border-radius: 8px; text-decoration: none; font-weight: 700; font-size: .78rem; white-space: nowrap; display: inline-flex; align-items: center; gap: 5px; }
.btn-examine:hover { background: #1d3a9e; color: #fff; }

/* ── CARTES SALLE D'ATTENTE ── */
.patient-wait-card { background: #fff; border-radius: 12px; padding: 14px 16px; border: 1px solid #e2e8f0; box-shadow: 0 2px 6px rgba(0,0,0,.04); margin-bottom: 10px; display: flex; align-items: center; gap: 14px; transition: .15s; }
.patient-wait-card:hover { box-shadow: 0 6px 18px rgba(0,0,0,.08); transform: translateY(-1px); }
.pw-avatar { width: 44px; height: 44px; border-radius: 50%; background: linear-gradient(135deg, #1e40af, #3b82f6); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: .9rem; flex-shrink: 0; }
.ticket-pill { background: #f1f5f9; color: #1e40af; border-radius: 8px; padding: 3px 10px; font-weight: 800; font-size: .78rem; }

/* ── HOSPITALISÉS ── */
.hosp-row { background: #fff; border-radius: 10px; padding: 12px 16px; border: 1px solid #e2e8f0; margin-bottom: 8px; display: flex; align-items: center; gap: 12px; transition: .15s; }
.hosp-row:hover { box-shadow: 0 4px 14px rgba(0,0,0,.07); }
.service-pill { background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; border-radius: 8px; padding: 2px 9px; font-size: .68rem; font-weight: 700; }

/* ── BILANS ── */
.bilan-row { display: flex; align-items: center; gap: 12px; padding: 12px 16px; background: #fff; border-radius: 10px; border: 1px solid #e2e8f0; margin-bottom: 8px; }
.bilan-type { width: 48px; height: 48px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0; }

/* ── RDV ── */
.rdv-card { background: #fff; border-radius: 12px; padding: 14px 18px; border-left: 5px solid #3b82f6; box-shadow: 0 2px 8px rgba(0,0,0,.04); margin-bottom: 10px; display: flex; align-items: center; gap: 14px; }

/* ── EMPTY STATE ── */
.empty-state { text-align: center; padding: 60px 20px; color: #94a3b8; }
.empty-state i { font-size: 2.8rem; display: block; margin-bottom: 10px; }

/* ── MODAL SUIVI RAPIDE ── */
.v-block { background: #f8fafc; padding: 6px 12px; border-radius: 8px; text-align: center; min-width: 68px; border: 1px solid #e2e8f0; }
.v-block strong { display: block; font-size: .92rem; color: #1e293b; font-weight: 800; }
.v-block small  { font-size: .58rem; color: #94a3b8; text-transform: uppercase; font-weight: 700; }
</style>

<!-- ══ HEADER ══ -->
<div class="cockpit-header">
    <div style="display:flex;align-items:center;gap:14px">
        <div style="background:rgba(255,255,255,.15);padding:7px;border-radius:8px;">
            <img src="<?= BASE_URL ?>public/images/logo_ordre_malte.png" style="height:36px"
                 onerror="this.onerror=null; this.style.display='none'; this.insertAdjacentHTML('afterend', '<div style=\'width:36px;height:36px;border-radius:8px;background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;font-weight:900;color:#fff;font-size:1rem\'>H</div>');">
        </div>
        <div>
            <div style="font-weight:800;font-size:1rem;letter-spacing:.5px">COCKPIT <span style="color:#fbbf24">MÉDECIN URGENCES</span></div>
            <div style="font-size:.68rem;opacity:.7"><?= htmlspecialchars($_SESSION['nom_complet'] ?? $_SESSION['user_nom'] ?? '') ?></div>
        </div>
    </div>
    <!-- ── BARRE DE RECHERCHE GLOBALE ── -->
    <div id="globalSearchWrap" style="position:relative;flex:1;max-width:420px;margin:0 20px;">
        <div style="display:flex;align-items:center;background:rgba(255,255,255,.1);
                    border:1.5px solid rgba(255,255,255,.22);border-radius:50px;
                    padding:0 14px;gap:8px;transition:all .2s;"
             id="globalSearchBar">
            <i class="bi bi-search" style="color:rgba(255,255,255,.6);font-size:.85rem;flex-shrink:0;"></i>
            <input type="text" id="globalSearchInput"
                   placeholder="Rechercher un patient…"
                   autocomplete="off"
                   style="background:none;border:none;outline:none;color:#fff;
                          font-size:.82rem;font-weight:600;width:100%;
                          placeholder-color:rgba(255,255,255,.5);"
                   oninput="rechercheGlobale(this.value)"
                   onfocus="ouvrirRechercheGlobale()"
                   onkeydown="navRechercheGlobale(event)">
            <span id="globalSearchCount"
                  style="font-size:.65rem;color:rgba(255,255,255,.5);white-space:nowrap;display:none;"></span>
            <button onclick="fermerRechercheGlobale()"
                    id="globalSearchClear"
                    style="background:rgba(255,255,255,.15);border:none;border-radius:50%;
                           width:22px;height:22px;display:none;align-items:center;
                           justify-content:center;color:#fff;cursor:pointer;font-size:.7rem;
                           flex-shrink:0;"
                    title="Effacer">✕</button>
        </div>
        <!-- Dropdown résultats -->
        <div id="globalSearchDropdown"
             style="display:none;position:absolute;top:calc(100% + 8px);left:0;right:0;
                    background:#fff;border-radius:14px;box-shadow:0 12px 40px rgba(0,0,0,.22);
                    max-height:420px;overflow-y:auto;z-index:9999;border:1px solid #e2e8f0;">
        </div>
    </div>
    <div id="digital-clock">00:00:00</div>
    <div style="display:flex;align-items:center;gap:6px">
        <!-- Séparateur groupes -->
        <div style="display:flex;align-items:center;gap:4px;background:rgba(255,255,255,.08);
                    border:1px solid rgba(255,255,255,.14);border-radius:50px;padding:4px 6px;">
            <!-- Patient connu -->
            <button onclick="ouvrirModalPatientConnu()" title="Patient connu — réadmettre"
                    style="background:none;border:none;width:34px;height:34px;border-radius:50%;
                           display:flex;align-items:center;justify-content:center;cursor:pointer;
                           color:#7dd3fc;font-size:1rem;transition:.15s;"
                    onmouseover="this.style.background='rgba(56,189,248,.2)'"
                    onmouseout="this.style.background='none'">
                <i class="bi bi-person-check-fill"></i>
            </button>
            <!-- Nouveau patient -->
            <button onclick="ouvrirNouveauPatientUrg()" title="Nouveau patient"
                    style="background:none;border:none;width:34px;height:34px;border-radius:50%;
                           display:flex;align-items:center;justify-content:center;cursor:pointer;
                           color:rgba(255,255,255,.85);font-size:1rem;transition:.15s;"
                    onmouseover="this.style.background='rgba(255,255,255,.15)'"
                    onmouseout="this.style.background='none'">
                <i class="bi bi-person-plus-fill"></i>
            </button>
            <!-- Ordonnance -->
            <button onclick="ouvrirOrdonnanceUrg()" title="Ordonnance rapide"
                    style="background:none;border:none;width:34px;height:34px;border-radius:50%;
                           display:flex;align-items:center;justify-content:center;cursor:pointer;
                           color:#c4b5fd;font-size:1rem;transition:.15s;"
                    onmouseover="this.style.background='rgba(167,139,250,.2)'"
                    onmouseout="this.style.background='none'">
                <i class="bi bi-prescription2"></i>
            </button>
            <!-- Externes -->
            <a href="<?= BASE_URL ?>suivi-externe" title="Patients externes"
                    style="background:none;border:none;width:34px;height:34px;border-radius:50%;
                           display:flex;align-items:center;justify-content:center;cursor:pointer;
                           color:#93c5fd;font-size:1rem;text-decoration:none;transition:.15s;"
                    onmouseover="this.style.background='rgba(59,130,246,.2)'"
                    onmouseout="this.style.background='none'">
                <i class="bi bi-person-lines-fill"></i>
            </a>
        </div>
        <!-- Changer de service -->
        <button id="btnChangerServiceUrg" onclick="ouvrirChangerServiceUrg()" title="Changer temporairement de service"
                style="background:<?= $_estServiceTemporaireUrg ? 'rgba(251,191,36,.25)' : 'rgba(255,255,255,.08)' ?>;
                       color:<?= $_estServiceTemporaireUrg ? '#fbbf24' : 'rgba(255,255,255,.7)' ?>;
                       border:1px solid <?= $_estServiceTemporaireUrg ? 'rgba(251,191,36,.5)' : 'rgba(255,255,255,.14)' ?>;
                       padding:7px 13px;border-radius:50px;font-weight:700;font-size:.75rem;
                       display:flex;align-items:center;gap:6px;cursor:pointer;white-space:nowrap;">
            <i class="bi bi-shuffle"></i>
            <span id="btnChangerServiceUrgLabel">
                <?= $_estServiceTemporaireUrg ? htmlspecialchars($_SESSION['nom_service'] ?? 'Service') . ' ●' : 'Changer service' ?>
            </span>
        </button>
        <!-- Nouvelle admission -->
        <button class="btn-new-adm" data-bs-toggle="modal" data-bs-target="#modalFastAdmission">
            <i class="bi bi-plus-circle-fill"></i><span class="btn-label"> NOUVELLE ADMISSION</span>
        </button>
        <a href="<?= BASE_URL ?>logout" class="btn-logout-c" title="Déconnexion"><i class="bi bi-power fs-5"></i></a>
    </div>
</div>

<!-- ══ BARRE DE STATS ══ -->
<div class="stats-bar">
    <div class="stat-box p1">
        <div class="stat-icon" style="background:#fef2f2;color:#ef4444"><i class="bi bi-exclamation-octagon-fill"></i></div>
        <div><div class="stat-num"><?= $stats['P1'] ?></div><div class="stat-lbl">P1 – Déchocage</div></div>
    </div>
    <div class="stat-box p2">
        <div class="stat-icon" style="background:#fff7ed;color:#f97316"><i class="bi bi-lightning-charge-fill"></i></div>
        <div><div class="stat-num"><?= $stats['P2'] ?></div><div class="stat-lbl">P2 – Urgences</div></div>
    </div>
    <div class="stat-box wait">
        <div class="stat-icon" style="background:#eff6ff;color:#3b82f6"><i class="bi bi-person-badge-fill"></i></div>
        <div><div class="stat-num"><?= $stats['waiting_med'] ?></div><div class="stat-lbl">Attente Médecin</div></div>
    </div>
    <div class="stat-box p3">
        <div class="stat-icon" style="background:#f0fdf4;color:#22c55e"><i class="bi bi-shield-check"></i></div>
        <div><div class="stat-num"><?= $stats['P3'] ?></div><div class="stat-lbl">P3 – Stables</div></div>
    </div>
    <div class="stat-box" style="border-bottom-color:#8b5cf6">
        <div class="stat-icon" style="background:#f5f3ff;color:#8b5cf6"><i class="bi bi-people-fill"></i></div>
        <div><div class="stat-num"><?= $nbAttente ?></div><div class="stat-lbl">En attente consult.</div></div>
    </div>
    <div class="stat-box" style="border-bottom-color:#0891b2">
        <div class="stat-icon" style="background:#ecfeff;color:#0891b2"><i class="bi bi-hospital-fill"></i></div>
        <div><div class="stat-num"><?= $nbHosp ?></div><div class="stat-lbl">Hospitalisés</div></div>
    </div>
    <div class="stat-box" style="border-bottom-color:#d97706">
        <div class="stat-icon" style="background:#fffbeb;color:#d97706"><i class="bi bi-flask-fill"></i></div>
        <div><div class="stat-num"><?= $nbBilans ?></div><div class="stat-lbl">Résultats labo</div></div>
    </div>
    <div class="stat-box" style="border-bottom-color:#64748b">
        <div class="stat-icon" style="background:#f8fafc;color:#64748b"><i class="bi bi-calendar2-check-fill"></i></div>
        <div><div class="stat-num"><?= $nbRdv ?></div><div class="stat-lbl">RDV aujourd'hui</div></div>
    </div>
    <div class="stat-box" style="border-bottom-color:#10b981;cursor:pointer"
         onclick="document.querySelector('[data-bs-target=\'#tabPartages\']').click()">
        <div class="stat-icon" style="background:#ecfdf5;color:#10b981"><i class="bi bi-share-fill"></i></div>
        <div>
            <div class="stat-num"><?= count($dossiers_recus) ?></div>
            <div class="stat-lbl">Dossiers partagés
                <?php if($nb_partages_non_lus > 0): ?>
                    <span style="background:#ef4444;color:#fff;border-radius:10px;padding:1px 6px;font-size:.6rem;font-weight:800;margin-left:3px"><?= $nb_partages_non_lus ?> new</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($_GET['erreur'])): ?>
<div class="alert alert-warning alert-dismissible fade show mx-3 mt-3 rounded-3" style="font-size:.85rem;">
    <?php if ($_GET['erreur'] === 'patient_deja_pris'): ?>
        <i class="bi bi-person-x-fill me-2"></i>
        Ce patient a déjà été pris en charge par un autre médecin.
    <?php else: ?>
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        Une erreur est survenue. Veuillez réessayer.
    <?php endif; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- ══ ONGLETS ══ -->
<ul class="nav main-tabs" id="cockpitTabs">
    <li class="nav-item">
        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabUrgences" type="button">
            <i class="bi bi-lightning-charge-fill text-danger"></i> Cockpit Urgences
            <?php if($nbUrgences>0): ?><span class="tab-badge"><?= $nbUrgences ?></span><?php endif; ?>
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabAttente" type="button">
            <i class="bi bi-people-fill" style="color:#8b5cf6"></i> Salle d'Attente
            <?php if($nbAttente>0): ?><span class="tab-badge" style="background:#8b5cf6"><?= $nbAttente ?></span><?php endif; ?>
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabHospitalises" type="button">
            <i class="bi bi-hospital-fill text-info"></i> Hospitalisés
            <?php if($nbHosp>0): ?><span class="tab-badge blue"><?= $nbHosp ?></span><?php endif; ?>
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabConsultes" type="button">
            <i class="bi bi-person-check-fill" style="color:#0891b2"></i> Patients Consultés
            <?php if($nbConsultes>0): ?><span class="tab-badge blue"><?= $nbConsultes ?></span><?php endif; ?>
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabCrh" type="button">
            <i class="bi bi-file-earmark-medical-fill" style="color:#7c3aed"></i> Comptes-rendus
            <?php if($nbCrh>0): ?><span class="tab-badge" style="background:#7c3aed"><?= $nbCrh ?></span><?php endif; ?>
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabBilans" type="button">
            <i class="bi bi-flask-fill" style="color:#d97706"></i> Bilans / Résultats
            <?php if($nbBilans>0): ?><span class="tab-badge orange"><?= $nbBilans ?></span><?php endif; ?>
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabAgenda" type="button">
            <i class="bi bi-calendar2-check-fill text-success"></i> Agenda du Jour
            <?php if($nbRdv>0): ?><span class="tab-badge green"><?= $nbRdv ?></span><?php endif; ?>
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabPartages" type="button">
            <i class="bi bi-share-fill" style="color:#10b981"></i> Dossiers Partagés
            <?php if($nb_partages_non_lus>0): ?><span class="tab-badge" style="background:#10b981"><?= $nb_partages_non_lus ?></span><?php endif; ?>
        </button>
    </li>
</ul>

<div class="tab-content">

<!-- ════ ONGLET 1 : COCKPIT URGENCES ════ -->
<div class="tab-pane fade show active tab-pane-body" id="tabUrgences">

    <!-- ── Barre de filtres ── -->
    <div class="d-flex flex-wrap gap-2 align-items-center mb-3 p-3 rounded-3"
         style="background:#f8fafc;border:1px solid #e2e8f0;">

        <!-- Recherche nom / N° dossier -->
        <div class="input-group" style="max-width:280px;">
            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
            <input type="text" id="urgSearchInput" class="form-control border-start-0 ps-0"
                   placeholder="Nom, prénom, N° dossier…"
                   oninput="filtrerUrgencesDebounce()">
        </div>

        <!-- Filtre par date -->
        <div class="input-group" style="max-width:200px;">
            <span class="input-group-text bg-white border-end-0"><i class="bi bi-calendar3 text-muted"></i></span>
            <input type="date" id="urgDateInput" class="form-control border-start-0"
                   value="<?= date('Y-m-d') ?>"
                   onchange="filtrerUrgences()">
        </div>

        <!-- Filtres triage rapides -->
        <div class="d-flex gap-1">
            <button class="btn btn-sm btn-outline-secondary rounded-pill px-3 urg-triage-btn active" data-triage="">Tous</button>
            <button class="btn btn-sm btn-outline-danger   rounded-pill px-2 urg-triage-btn" data-triage="1">P1</button>
            <button class="btn btn-sm btn-outline-warning  rounded-pill px-2 urg-triage-btn" data-triage="2">P2</button>
            <button class="btn btn-sm btn-outline-primary  rounded-pill px-2 urg-triage-btn" data-triage="3">P3</button>
            <button class="btn btn-sm btn-outline-success  rounded-pill px-2 urg-triage-btn" data-triage="4">P4</button>
            <button class="btn btn-sm btn-outline-secondary rounded-pill px-2 urg-triage-btn" data-triage="5">P5</button>
        </div>

        <!-- Bouton réinitialiser -->
        <button class="btn btn-sm btn-light rounded-pill border ms-auto" onclick="resetFiltresUrgences()">
            <i class="bi bi-x-circle me-1"></i>Réinitialiser
        </button>

        <!-- Compteur résultats -->
        <span id="urgCount" class="text-muted small"></span>
    </div>

    <?php if(empty($admissions)): ?>
    <div class="empty-state">
        <i class="bi bi-person-add"></i>
        <h5>Aucun patient admis aux urgences</h5>
        <p>Cliquez sur "Nouvelle Admission" pour admettre un patient.</p>
    </div>
    <?php else: ?>
    <div style="background:#fff;border-radius:14px;box-shadow:0 4px 20px rgba(0,0,0,.05);overflow:hidden;border:1px solid #e2e8f0">
    <table class="table-urg" id="tableUrgences">
        <thead>
            <tr>
                <th>Triage</th>
                <th>Patient</th>
                <th>Constantes / Monitorage</th>
                <th style="text-align:center">Bilans</th>
                <th>Durée</th>
                <th style="text-align:right">Action</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach($admissions as $adm):
            $niv      = $adm['niveau_triage'] ?? '3';
            $age      = !empty($adm['date_naissance']) ? date_diff(date_create($adm['date_naissance']), date_create('now'))->y.'ans' : '?';
            $duree    = date_diff(date_create($adm['heure_arrivee']), date_create('now'));
            $dureeStr = ($duree->h>0 ? $duree->h.'h ' : '').$duree->i.'min';
            $dateArr  = date('Y-m-d', strtotime($adm['heure_arrivee']));
            $nomSearch= strtolower($adm['nom'].' '.$adm['prenom'].' '.($adm['dossier_numero']??''));
        ?>
        <tr data-triage="<?= $niv ?>"
            data-date="<?= $dateArr ?>"
            data-search="<?= htmlspecialchars($nomSearch, ENT_QUOTES) ?>">
            <td><span class="triage-pill triage-<?= $niv ?>">P<?= $niv ?></span></td>
            <td>
                <div class="fw-bold text-dark"><?= htmlspecialchars(strtoupper($adm['nom']).' '.$adm['prenom']) ?></div>
                <small class="text-muted"><?= $age ?> • <?= $adm['dossier_numero'] ?></small>
            </td>
            <td>
                <div class="vitals-row">
                    <div class="v-block"><small>GCS</small><strong><?= $adm['score_glasgow']??'--' ?></strong></div>
                    <div class="v-block"><small>TA</small><strong><?= ($adm['tension_sys']&&$adm['tension_dia'])?$adm['tension_sys'].'/'.$adm['tension_dia']:'--' ?></strong></div>
                    <div class="v-block"><small>FC</small><strong class="text-danger"><?= $adm['pouls']??'--' ?></strong></div>
                    <div class="v-block"><small>SpO2</small><strong class="text-info"><?= $adm['spo2'] ? $adm['spo2'].'%' : '--' ?></strong></div>
                    <div class="v-block"><small>T°</small><strong class="text-warning"><?= $adm['temperature'] ? $adm['temperature'].'°' : '--' ?></strong></div>
                </div>
                <div class="mt-1 small text-muted" style="font-size:.72rem"><?= htmlspecialchars(mb_substr($adm['motif_plainte']??$adm['motif_admission']??'',0,60)) ?></div>
            </td>
            <td style="text-align:center">
                <?php if(($adm['nb_bilans_dispo']??0)>0): ?>
                <span class="badge bg-success rounded-pill"><i class="bi bi-flask-fill"></i> <?= $adm['nb_bilans_dispo'] ?></span>
                <?php else: ?><i class="bi bi-clock-history text-muted"></i><?php endif; ?>
            </td>
            <td><i class="bi bi-stopwatch me-1 text-muted"></i><span class="fw-bold small"><?= $dureeStr ?></span></td>
            <td style="text-align:right">
                <div class="d-flex gap-2 justify-content-end">
                    <a href="<?= BASE_URL ?>urgences/triage/<?= $adm['id'] ?>" class="btn btn-outline-secondary btn-sm rounded-pill" style="font-size:.72rem">
                        <i class="bi bi-clipboard2-pulse"></i> Triage
                    </a>
                    <a href="<?= BASE_URL ?>patients/dossier/<?= $adm['patient_id'] ?>" target="_blank" class="btn btn-outline-info btn-sm rounded-pill" style="font-size:.72rem" title="Ouvrir le dossier médical">
                        <i class="bi bi-folder2-open"></i> Dossier
                    </a>
                    <a href="<?= BASE_URL ?>consultation/formulaire?patient_id=<?= $adm['patient_id'] ?>&type=EXTERNE&etape=1" class="btn-examine">
                        <i class="bi bi-stethoscope"></i> Examiner
                    </a>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <!-- Message affiché quand aucune ligne ne correspond aux filtres -->
    <div id="urgNoResult" class="text-center py-5 d-none" style="color:#94a3b8;">
        <i class="bi bi-funnel" style="font-size:2rem;"></i>
        <p class="mt-2 mb-0">Aucun patient ne correspond aux filtres sélectionnés.</p>
        <button class="btn btn-sm btn-outline-secondary mt-2 rounded-pill" onclick="resetFiltresUrgences()">
            <i class="bi bi-x-circle me-1"></i>Effacer les filtres
        </button>
    </div>
    </div>
    <?php endif; ?>
</div>

<!-- ════ ONGLET 2 : SALLE D'ATTENTE ════ -->
<div class="tab-pane fade tab-pane-body" id="tabAttente">

    <!-- ── Barre de filtres ── -->
    <div class="d-flex flex-wrap gap-2 align-items-center mb-3 p-3 rounded-3"
         style="background:#f8fafc;border:1px solid #e2e8f0;">

        <!-- Recherche -->
        <div class="input-group" style="max-width:300px;">
            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
            <input type="text" id="attenteSearchInput" class="form-control border-start-0 ps-0"
                   placeholder="Nom, N° dossier, motif…"
                   oninput="filtrerAttente()">
        </div>

        <!-- Filtre par date -->
        <div class="input-group" style="max-width:200px;">
            <span class="input-group-text bg-white border-end-0"><i class="bi bi-calendar3 text-muted"></i></span>
            <input type="date" id="attenteDateInput" class="form-control border-start-0"
                   value="<?= date('Y-m-d') ?>"
                   onchange="filtrerAttente()">
        </div>

        <!-- Réinitialiser -->
        <button class="btn btn-sm btn-light rounded-pill border ms-auto" onclick="resetFiltresAttente()">
            <i class="bi bi-x-circle me-1"></i>Réinitialiser
        </button>

        <!-- Compteur -->
        <span id="attenteCount" class="text-muted small"></span>
    </div>

    <?php if(empty($patients_assignes)): ?>
    <div class="empty-state"><i class="bi bi-check-circle-fill text-success"></i><h5 class="text-success">Aucun patient en attente de consultation</h5></div>
    <?php else: ?>
    <div class="row g-2" id="attenteGrid">
    <?php foreach($patients_assignes as $p):
        $ini       = strtoupper(substr($p['nom'],0,1).substr($p['prenom']??'',0,1));
        $age       = !empty($p['date_naissance']) ? date_diff(date_create($p['date_naissance']),date_create('now'))->y.'ans' : '?';
        $dateRef   = !empty($p['updated_at']) ? date('Y-m-d', strtotime($p['updated_at']))
                   : (!empty($p['created_at']) ? date('Y-m-d', strtotime($p['created_at'])) : date('Y-m-d'));
        $searchStr = strtolower($p['nom'].' '.($p['prenom']??'').' '.($p['dossier_numero']??'').' '.($p['motif_plainte']??''));
    ?>
    <div class="col-12 col-xl-6 attente-card-wrap"
         data-date="<?= $dateRef ?>"
         data-search="<?= htmlspecialchars($searchStr, ENT_QUOTES) ?>">
    <div class="patient-wait-card">
        <div class="pw-avatar"><?= $ini ?></div>
        <div class="flex-grow-1">
            <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                <?php if(!empty($p['numero_ordre'])): ?><span class="ticket-pill">#<?= $p['numero_ordre'] ?></span><?php endif; ?>
                <strong><?= htmlspecialchars(strtoupper($p['nom']).' '.($p['prenom']??'')) ?></strong>
                <small class="text-muted"><?= $age ?> • <?= htmlspecialchars($p['dossier_numero']??'') ?></small>
            </div>
            <?php if(!empty($p['motif_plainte'])): ?>
            <div class="small text-secondary" style="font-size:.75rem"><i class="bi bi-chat-dots"></i> <?= htmlspecialchars(mb_substr($p['motif_plainte'],0,80)) ?></div>
            <?php endif; ?>
            <div class="small text-muted mt-1" style="font-size:.7rem">
                <i class="bi bi-clock me-1"></i>
                <?= !empty($p['updated_at']) ? date('d/m/Y H:i', strtotime($p['updated_at'])) : 'Heure inconnue' ?>
            </div>
        </div>
        <div class="d-flex gap-2 flex-shrink-0">
            <?php if (empty($p['medecin_id'])): ?>
            <span class="badge bg-warning text-dark align-self-center" style="font-size:.65rem;">
                <i class="bi bi-broadcast-pin me-1"></i>File commune
            </span>
            <?php endif; ?>
            <a href="<?= BASE_URL ?>urgences/prendre-en-charge/<?= $p['id'] ?>"
               class="btn btn-primary btn-sm rounded-pill">
                <i class="bi bi-stethoscope"></i> Consulter
            </a>
            <a href="<?= BASE_URL ?>patients/dossier/<?= $p['id'] ?>"
               class="btn btn-outline-secondary btn-sm rounded-pill">
                <i class="bi bi-folder2-open"></i>
            </a>
        </div>
    </div>
    </div>
    <?php endforeach; ?>
    </div>

    <!-- Message aucun résultat -->
    <div id="attenteNoResult" class="text-center py-5 d-none" style="color:#94a3b8;">
        <i class="bi bi-funnel" style="font-size:2rem;"></i>
        <p class="mt-2 mb-0">Aucun patient ne correspond aux filtres.</p>
        <button class="btn btn-sm btn-outline-secondary mt-2 rounded-pill" onclick="resetFiltresAttente()">
            <i class="bi bi-x-circle me-1"></i>Effacer les filtres
        </button>
    </div>
    <?php endif; ?>
</div>

<!-- ════ ONGLET 3 : HOSPITALISÉS ════ -->
<div class="tab-pane fade tab-pane-body" id="tabHospitalises">

    <!-- ── Barre de filtres Hospitalisés ── -->
    <div class="d-flex align-items-center gap-2 flex-wrap mb-3 p-3 bg-white rounded-3 shadow-sm border" style="border-color:#e2e8f0!important">
        <span class="fw-bold small text-uppercase" style="color:#0891b2;letter-spacing:.5px;white-space:nowrap">
            <i class="bi bi-funnel-fill me-1"></i>Filtres :
        </span>
        <div class="input-group" style="max-width:280px;">
            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
            <input type="text" id="hospSearchInput" class="form-control border-start-0 ps-0"
                   placeholder="Nom, N° dossier, chambre…"
                   oninput="filtrerHospitalises()">
        </div>
        <div class="input-group" style="max-width:200px;">
            <span class="input-group-text bg-white border-end-0"><i class="bi bi-calendar3 text-muted"></i></span>
            <input type="date" id="hospDateInput" class="form-control border-start-0"
                   title="Filtrer par date d'admission"
                   onchange="filtrerHospitalises()">
        </div>
        <button class="btn btn-sm btn-light rounded-pill border ms-auto" onclick="resetFiltresHospitalises()">
            <i class="bi bi-x-circle me-1"></i>Réinitialiser
        </button>
        <span id="hospCount" class="text-muted small"></span>
    </div>

    <?php if(empty($patients_hospitalises)): ?>
    <div class="empty-state"><i class="bi bi-hospital"></i><h5>Aucun patient hospitalisé</h5></div>
    <?php else:
        // Regrouper par service
        $hospBySrv = [];
        foreach($patients_hospitalises as $ph) {
            $srv = $ph['nom_service'] ?? 'Non assigné';
            $hospBySrv[$srv][] = $ph;
        }
        foreach($hospBySrv as $srv => $pats):
    ?>
    <div class="hosp-group mb-4">
        <h6 class="fw-bold text-uppercase mb-2 hosp-group-title" style="font-size:.78rem;letter-spacing:1px;color:#64748b">
            <i class="bi bi-building-fill-check me-1"></i><?= htmlspecialchars($srv) ?>
            <span class="badge bg-primary ms-1" style="font-size:.62rem"><?= count($pats) ?></span>
        </h6>
        <?php foreach($pats as $ph):
            $ini = strtoupper(substr($ph['nom'],0,1).substr($ph['prenom'],0,1));
            $dureeHosp = date_diff(date_create($ph['date_admission']),date_create('now'))->days;
            $dateAdmRef = !empty($ph['date_admission']) ? date('Y-m-d', strtotime($ph['date_admission'])) : '';
            $hospSearch = strtolower(($ph['nom']??'').' '.($ph['prenom']??'').' '.($ph['dossier_numero']??'').' '.($ph['nom_service']??'').' '.($ph['nom_chambre']??'').' '.($ph['nom_lit']??''));
        ?>
        <div class="hosp-row"
             data-date="<?= $dateAdmRef ?>"
             data-search="<?= htmlspecialchars($hospSearch, ENT_QUOTES) ?>">
            <div class="pw-avatar" style="background:linear-gradient(135deg,#0891b2,#06b6d4)"><?= $ini ?></div>
            <div class="flex-grow-1">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <strong class="small"><?= htmlspecialchars(strtoupper($ph['nom']).' '.$ph['prenom']) ?></strong>
                    <span class="service-pill"><?= htmlspecialchars($ph['nom_service']??'') ?></span>
                    <?php if(!empty($ph['nom_chambre'])): ?>
                    <span class="badge bg-light text-dark border" style="font-size:.65rem"><i class="bi bi-door-closed"></i> <?= htmlspecialchars($ph['nom_chambre'].' — '.($ph['nom_lit']??'')) ?></span>
                    <?php endif; ?>
                    <small class="text-muted" style="font-size:.7rem">J+<?= $dureeHosp ?></small>
                </div>
            </div>
            <div class="d-flex gap-2 flex-shrink-0">
                <a href="<?= BASE_URL ?>hospitalisation/reevaluation/<?= $ph['patient_id']??$ph['id'] ?>" class="btn btn-success btn-sm rounded-pill fw-bold" style="font-size:.72rem">
                    <i class="bi bi-clipboard2-pulse-fill"></i> Réévaluer
                </a>
                <button type="button"
                        class="btn btn-sm rounded-pill fw-bold text-white"
                        style="font-size:.72rem;background:linear-gradient(135deg,#f59e0b,#d97706);border:none"
                        onclick="ouvrirReevaluations(<?= (int)($ph['patient_id']??$ph['id']) ?>,
                            '<?= htmlspecialchars(strtoupper($ph['nom']).' '.$ph['prenom'], ENT_QUOTES) ?>')">
                    <i class="bi bi-journal-medical me-1"></i>Réévaluations
                </button>
                <button type="button"
                        class="btn btn-sm rounded-pill fw-bold text-white"
                        style="font-size:.72rem;background:linear-gradient(135deg,#7c3aed,#6d28d9);border:none"
                        onclick="ouvrirSuiviRapide(<?= (int)($ph['patient_id']??$ph['id']) ?>,
                            '<?= htmlspecialchars(strtoupper($ph['nom']).' '.$ph['prenom'], ENT_QUOTES) ?>')">
                    <i class="bi bi-clipboard2-heart-fill me-1"></i>Voir suivi
                </button>
                <a href="<?= BASE_URL ?>patients/dossier/<?= $ph['patient_id']??$ph['id'] ?>" class="btn btn-outline-primary btn-sm rounded-pill" style="font-size:.72rem">
                    <i class="bi bi-folder2-open"></i>
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endforeach; ?>

    <!-- Message aucun résultat -->
    <div id="hospNoResult" class="text-center py-5 d-none" style="color:#94a3b8;">
        <i class="bi bi-funnel" style="font-size:2rem;"></i>
        <p class="mt-2 mb-0">Aucun patient hospitalisé ne correspond aux filtres.</p>
        <button class="btn btn-sm btn-outline-secondary mt-2 rounded-pill" onclick="resetFiltresHospitalises()">
            <i class="bi bi-x-circle me-1"></i>Effacer les filtres
        </button>
    </div>
    <?php endif; ?>
</div>

<!-- ════ ONGLET 4 : PATIENTS CONSULTÉS ════ -->
<?php
// Variables passées par le contrôleur (avec valeurs par défaut si vue appelée sans contrôleur)
$periodeConsultes   = $periodeConsultes   ?? 24;
$periodesAutorisees = $periodesAutorisees ?? [
    24=>'24h', 48=>'48h', 72=>'72h', 168=>'7 jours', 336=>'14 jours', 720=>'30 jours'
];
// URL de base sans le paramètre periode_consultes
$urlBase = strtok($_SERVER['REQUEST_URI'] ?? '', '?');
?>
<div class="tab-pane fade tab-pane-body" id="tabConsultes">

    <!-- ── Barre filtres ── -->
    <div class="d-flex align-items-center gap-2 flex-wrap mb-3 p-3 bg-white rounded-3 shadow-sm border"
         style="border-color:#e2e8f0!important">
        <span class="fw-bold small text-uppercase" style="color:#0891b2;letter-spacing:.5px;white-space:nowrap">
            <i class="bi bi-funnel-fill me-1"></i>Période :
        </span>
        <?php foreach($periodesAutorisees as $h => $lbl): ?>
        <a href="<?= $urlBase ?>?periode_consultes=<?= $h ?>#tabConsultes"
           class="btn btn-sm rounded-pill fw-semibold <?= $periodeConsultes===$h ? 'btn-primary' : 'btn-outline-secondary' ?>"
           style="font-size:.75rem">
            <?= $lbl ?>
        </a>
        <?php endforeach; ?>
        <div class="input-group ms-auto" style="max-width:185px;">
            <span class="input-group-text bg-light border-0"><i class="bi bi-calendar3 text-muted" style="font-size:.8rem"></i></span>
            <input type="date" id="consulDateInput"
                   class="form-control form-control-sm border-0 bg-light shadow-sm"
                   style="font-size:.8rem"
                   onchange="filtrerConsultes()">
        </div>
        <input type="text" id="searchConsultes"
               class="form-control form-control-sm rounded-pill border-0 bg-light shadow-sm"
               style="min-width:190px;font-size:.8rem" placeholder="🔍 Rechercher…"
               oninput="filtrerConsultes()">
        <button class="btn btn-sm btn-light border rounded-pill" onclick="resetFiltresConsultes()" title="Effacer filtres">
            <i class="bi bi-x-circle"></i>
        </button>
        <span id="consulCount" class="text-muted small" style="white-space:nowrap"></span>
    </div>

<?php if(empty($patients_consultes)): ?>
    <div class="empty-state">
        <i class="bi bi-person-check-fill text-info"></i>
        <h5>Aucune consultation sur cette période</h5>
        <p class="small text-muted">Élargissez la période ou vérifiez que des consultations ont été enregistrées.</p>
    </div>
<?php else: ?>

    <div class="d-flex align-items-center mb-3">
        <h6 class="fw-bold text-uppercase mb-0" style="font-size:.78rem;letter-spacing:1px;color:#0891b2">
            <i class="bi bi-person-check-fill me-1"></i>
            <?= $nbConsultes ?> patient<?= $nbConsultes>1?'s':'' ?> — dernières <?= $periodesAutorisees[$periodeConsultes] ?>
            <?php
                $totalConsultations = array_sum(array_column($patients_consultes, 'nb_consultations'));
                if ($totalConsultations > $nbConsultes):
            ?>
            <span class="ms-2 text-muted fw-normal" style="font-size:.73rem;letter-spacing:0">(<?= $totalConsultations ?> consultations au total)</span>
            <?php endif; ?>
        </h6>
    </div>

    <div id="listeConsultes">
    <?php foreach($patients_consultes as $c):
        $ini   = strtoupper(substr($c['nom'],0,1).substr($c['prenom']??'',0,1));
        $age   = !empty($c['date_naissance']) ? date_diff(date_create($c['date_naissance']),date_create('now'))->y.'ans' : '?';
        $statHosp  = $c['statut_hosp']        ?? '';
        $statActuel = $c['statut_hosp_actuel'] ?? '';
        // Hospitalisé = enregistrement hospitalisations en cours, ou statut HOSPITALISE
        $estHosp      = ($statActuel === 'en_cours') || ($statHosp === 'HOSPITALISE');
        // En attente d'un lit (décision prise mais pas encore de lit assigné)
        $enAttenteHosp = !$estHosp && ($statHosp === 'A_HOSPITALISER');
    ?>
    <?php $consulDateRef = !empty($c['date_consultation']) ? date('Y-m-d', strtotime($c['date_consultation'])) : date('Y-m-d'); ?>
    <div class="patient-wait-card consulte-item"
         data-search="<?= strtolower(htmlspecialchars($c['nom'].' '.$c['prenom'].' '.$c['dossier_numero'])) ?>"
         data-date="<?= $consulDateRef ?>">
        <div class="pw-avatar" style="background:linear-gradient(135deg,#0891b2,#22d3ee)"><?= $ini ?></div>
        <div class="flex-grow-1">
            <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                <strong class="small"><?= htmlspecialchars(strtoupper($c['nom']).' '.($c['prenom']??'')) ?></strong>
                <small class="text-muted"><?= $age ?> • <?= htmlspecialchars($c['dossier_numero']??'') ?></small>
                <?php if(($c['nb_consultations'] ?? 1) > 1): ?>
                <span class="badge rounded-pill" style="background:#ecfdf5;color:#059669;font-size:.6rem">
                    <i class="bi bi-arrow-repeat me-1"></i><?= (int)$c['nb_consultations'] ?> consultations
                </span>
                <?php endif; ?>
                <?php if($estHosp): ?>
                <span class="badge bg-success" style="font-size:.6rem"><i class="bi bi-hospital-fill me-1"></i>Hospitalisé</span>
                <?php elseif($enAttenteHosp): ?>
                <span class="badge bg-warning text-dark" style="font-size:.6rem"><i class="bi bi-clock-fill me-1"></i>En attente d'hospit.</span>
                <?php endif; ?>
            </div>
            <?php if(!empty($c['motif'])): ?>
            <div class="small text-secondary" style="font-size:.73rem"><i class="bi bi-chat-dots me-1"></i><?= htmlspecialchars(mb_substr($c['motif'],0,80)) ?></div>
            <?php endif; ?>
            <div class="text-muted mt-1" style="font-size:.68rem">
                <i class="bi bi-clock me-1"></i><?= date('d/m/Y H:i', strtotime($c['date_consultation'])) ?>
            </div>
        </div>
        <div class="d-flex gap-2 flex-shrink-0">
            <?php if($estHosp): ?>
            <a href="<?= BASE_URL ?>hospitalisation/suivi/<?= $c['patient_id'] ?>"
               class="btn btn-info btn-sm rounded-pill text-white" style="font-size:.72rem">
                <i class="bi bi-activity me-1"></i>Suivi
            </a>
            <?php elseif($enAttenteHosp): ?>
            <span class="btn btn-secondary btn-sm rounded-pill disabled" style="font-size:.72rem;opacity:.65">
                <i class="bi bi-hourglass-split me-1"></i>En attente lit
            </span>
            <?php else: ?>
            <button class="btn btn-warning btn-sm rounded-pill"
                    style="font-size:.72rem"
                    onclick="ouvrirHospitalisation(<?= (int)$c['patient_id'] ?>, <?= (int)$c['consultation_id'] ?>,
                        '<?= htmlspecialchars(addslashes(strtoupper($c['nom']).' '.($c['prenom']??''))) ?>')">
                <i class="bi bi-house-heart-fill me-1"></i>Hospitaliser
            </button>
            <?php endif; ?>
            <a href="<?= BASE_URL ?>patients/dossier/<?= $c['patient_id'] ?>"
               class="btn btn-outline-secondary btn-sm rounded-pill" style="font-size:.72rem">
                <i class="bi bi-folder2-open"></i>
            </a>
        </div>
    </div>
    <?php endforeach; ?>
    </div>

    <!-- Message aucun résultat -->
    <div id="consulNoResult" class="text-center py-5 d-none" style="color:#94a3b8;">
        <i class="bi bi-funnel" style="font-size:2rem;"></i>
        <p class="mt-2 mb-0">Aucun patient consulté ne correspond aux filtres.</p>
        <button class="btn btn-sm btn-outline-secondary mt-2 rounded-pill" onclick="resetFiltresConsultes()">
            <i class="bi bi-x-circle me-1"></i>Effacer les filtres
        </button>
    </div>

<?php endif; ?>
</div>

<!-- ════ ONGLET CRH : COMPTES-RENDUS D'HOSPITALISATION ════ -->
<div class="tab-pane fade tab-pane-body" id="tabCrh">

<?php if(empty($patients_crh_pending)): ?>
    <div class="empty-state">
        <i class="bi bi-file-earmark-check-fill text-success" style="font-size:3rem;opacity:.5"></i>
        <h5 class="mt-3">Aucun compte-rendu en attente</h5>
        <p class="small text-muted">Tous vos patients hospitalisés ont un compte-rendu rédigé.</p>
    </div>
<?php else: ?>
    <div class="d-flex align-items-center mb-3">
        <h6 class="fw-bold text-uppercase mb-0" style="font-size:.78rem;letter-spacing:1px;color:#7c3aed">
            <i class="bi bi-file-earmark-medical-fill me-1"></i>
            <?= $nbCrh ?> compte<?= $nbCrh>1?'s-rendus':'rendu' ?> en attente
        </h6>
    </div>

    <?php foreach($patients_crh_pending as $crh):
        $ini = strtoupper(substr($crh['nom'],0,1).substr($crh['prenom']??'',0,1));
        $age = !empty($crh['date_naissance']) ? date_diff(date_create($crh['date_naissance']),date_create('now'))->y.'ans' : '?';
        $dureeHosp = '';
        if (!empty($crh['date_admission'])) {
            $dateEntree = new DateTime($crh['date_admission']);
            $dateSortie = !empty($crh['date_sortie_effective']) ? new DateTime($crh['date_sortie_effective']) : new DateTime();
            $dureeHosp = $dateEntree->diff($dateSortie)->days . ' j';
        }
    ?>
    <div class="patient-wait-card mb-2">
        <div class="pw-avatar" style="background:linear-gradient(135deg,#7c3aed,#a78bfa)"><?= $ini ?></div>
        <div class="flex-grow-1">
            <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                <strong class="small"><?= htmlspecialchars(strtoupper($crh['nom']).' '.($crh['prenom']??'')) ?></strong>
                <small class="text-muted"><?= $age ?> • <?= htmlspecialchars($crh['dossier_numero']??'') ?></small>
                <span class="badge rounded-pill" style="background:#f3e8ff;color:#7c3aed;font-size:.6rem">
                    <i class="bi bi-hospital me-1"></i><?= htmlspecialchars($crh['nom_service']) ?>
                </span>
                <?php if($dureeHosp): ?>
                <span class="badge rounded-pill bg-light text-secondary" style="font-size:.6rem">
                    <i class="bi bi-calendar3 me-1"></i><?= $dureeHosp ?>
                </span>
                <?php endif; ?>
            </div>
            <div class="d-flex gap-3 flex-wrap" style="font-size:.7rem;color:#64748b">
                <span><i class="bi bi-box-arrow-in-right me-1 text-success"></i>
                    Entrée : <?= !empty($crh['date_admission']) ? date('d/m/Y', strtotime($crh['date_admission'])) : '—' ?>
                </span>
                <span><i class="bi bi-box-arrow-right me-1 text-danger"></i>
                    Sortie : <?= !empty($crh['date_sortie_effective']) ? date('d/m/Y', strtotime($crh['date_sortie_effective'])) : '—' ?>
                </span>
                <?php if(!empty($crh['motif_hospitalisation'])): ?>
                <span><i class="bi bi-chat-dots me-1"></i><?= htmlspecialchars(mb_substr($crh['motif_hospitalisation'],0,60)) ?></span>
                <?php endif; ?>
            </div>
        </div>
        <div class="d-flex gap-2 flex-shrink-0">
            <a href="<?= BASE_URL ?>formulaire/crh/<?= (int)$crh['hosp_id'] ?>"
               class="btn btn-sm rounded-pill fw-bold text-white"
               style="background:linear-gradient(135deg,#7c3aed,#6d28d9);font-size:.72rem;border:none">
                <i class="bi bi-pencil-square me-1"></i>Rédiger le CRH
            </a>
            <a href="<?= BASE_URL ?>patients/dossier/<?= (int)$crh['patient_id'] ?>"
               class="btn btn-outline-secondary btn-sm rounded-pill" style="font-size:.72rem">
                <i class="bi bi-folder2-open"></i>
            </a>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>
</div>

<!-- ════ ONGLET 5 : BILANS / RÉSULTATS ════ -->
<div class="tab-pane fade tab-pane-body" id="tabBilans">

<?php
$aucunContenu = empty($resultats_prets) && empty($suivi_bilans) && empty($resultats_imagerie);
?>

    <!-- ── Barre de filtres Bilans ── -->
    <div class="d-flex align-items-center gap-2 flex-wrap mb-3 p-3 bg-white rounded-3 shadow-sm border" style="border-color:#e2e8f0!important">
        <span class="fw-bold small text-uppercase" style="color:#d97706;letter-spacing:.5px;white-space:nowrap">
            <i class="bi bi-funnel-fill me-1"></i>Filtres :
        </span>
        <div class="input-group" style="max-width:280px;">
            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
            <input type="text" id="bilansSearchInput" class="form-control border-start-0 ps-0"
                   placeholder="Nom, N° dossier, examen…"
                   oninput="filtrerBilans()">
        </div>
        <div class="input-group" style="max-width:200px;">
            <span class="input-group-text bg-white border-end-0"><i class="bi bi-calendar3 text-muted"></i></span>
            <input type="date" id="bilansDateInput" class="form-control border-start-0"
                   onchange="filtrerBilans()">
        </div>
        <button class="btn btn-sm btn-light rounded-pill border ms-auto" onclick="resetFiltresBilans()">
            <i class="bi bi-x-circle me-1"></i>Réinitialiser
        </button>
        <span id="bilansCount" class="text-muted small"></span>
    </div>

<?php if($aucunContenu): ?>
    <div class="empty-state">
        <i class="bi bi-check2-all text-success"></i>
        <h5 class="text-success">Aucun résultat en attente</h5>
        <p class="small text-muted">Les résultats de biologie et d'imagerie apparaîtront ici dès qu'ils seront disponibles.</p>
    </div>
<?php else: ?>

    <!-- Section Biologie -->
    <div class="bilan-section">
    <?php if(!empty($resultats_prets)): ?>
    <h6 class="fw-bold text-uppercase mb-2" style="font-size:.75rem;letter-spacing:1px;color:#d97706;">
        <i class="bi bi-flask-fill me-1"></i>Résultats de biologie disponibles
        <span class="badge bg-warning text-dark rounded-pill ms-1"><?= count($resultats_prets) ?></span>
    </h6>
    <?php foreach($resultats_prets as $r):
        $bilanDate   = !empty($r['date_resultat']) ? date('Y-m-d', strtotime($r['date_resultat'])) : date('Y-m-d');
        $bilanSearch = strtolower(($r['nom']??'').' '.($r['prenom']??'').' '.($r['dossier_numero']??'').' '.($r['nom_examen']??''));
    ?>
    <div class="bilan-row <?= !empty($r['anormal']) ? 'border-start border-danger border-3' : '' ?>"
         data-date="<?= $bilanDate ?>"
         data-search="<?= htmlspecialchars($bilanSearch, ENT_QUOTES) ?>">
        <div class="bilan-type" style="background:#fffbeb;color:#d97706"><i class="bi bi-flask-fill"></i></div>
        <div class="flex-grow-1">
            <div class="fw-bold small">
                <?= htmlspecialchars(strtoupper($r['nom']).' '.$r['prenom']) ?>
                <span class="text-muted fw-normal">#<?= htmlspecialchars($r['dossier_numero']??'') ?></span>
                <?php if(!empty($r['anormal'])): ?>
                <span class="badge bg-danger ms-1" style="font-size:.6rem;">⚠ Anormal</span>
                <?php endif; ?>
            </div>
            <div class="small text-muted"><?= htmlspecialchars($r['nom_examen']??'Bilan') ?></div>
            <?php if(!empty($r['interpretation'])): ?>
            <div class="small text-danger fw-semibold mt-1">
                <i class="bi bi-exclamation-triangle-fill me-1"></i><?= htmlspecialchars(mb_substr($r['interpretation'],0,100)) ?>
            </div>
            <?php endif; ?>
        </div>
        <div class="text-end me-2">
            <div class="text-muted" style="font-size:.68rem;"><?= date('d/m H:i', strtotime($r['date_resultat'])) ?></div>
        </div>
        <?php if (!empty($r['demande_id'])): ?>
        <a href="<?= BASE_URL ?>laboratoire/imprimer-resultats/<?= (int)$r['demande_id'] ?>"
           target="_blank"
           class="btn btn-sm btn-success rounded-pill me-1" style="font-size:.72rem">
            <i class="bi bi-file-earmark-text me-1"></i>Résultats
        </a>
        <?php endif; ?>
        <a href="<?= BASE_URL ?>patients/dossier/<?= $r['patient_id'] ?? '' ?>"
           class="btn btn-sm btn-warning rounded-pill text-dark" style="font-size:.72rem">
            <i class="bi bi-folder2-open me-1"></i>Dossier
        </a>
    </div>
    <?php endforeach; ?>
    <?php else: ?>
    <p class="text-muted small fst-italic mb-3 ps-1">
        <i class="bi bi-check2-all text-success me-1"></i>Aucun résultat de biologie disponible.
    </p>
    <?php endif; ?>
    </div><!-- /bilan-section biologie -->

    <!-- Section Bilans en cours -->
    <?php if(!empty($suivi_bilans)): ?>
    <div class="bilan-section">
    <h6 class="fw-bold text-uppercase mt-3 mb-2" style="font-size:.75rem;letter-spacing:1px;color:#64748b;">
        <i class="bi bi-hourglass-split me-1"></i>Bilans en cours
        <span class="badge bg-secondary rounded-pill ms-1"><?= count($suivi_bilans) ?></span>
    </h6>
    <?php foreach($suivi_bilans as $b):
        $suiviDate   = !empty($b['date_creation']) ? date('Y-m-d', strtotime($b['date_creation'])) : date('Y-m-d');
        $suiviSearch = strtolower(($b['patient_nom']??$b['nom']??'').' '.($b['patient_prenom']??$b['prenom']??'').' '.($b['dossier_numero']??'').' '.($b['label']??$b['nom_examen']??''));
    ?>
    <div class="bilan-row"
         data-date="<?= $suiviDate ?>"
         data-search="<?= htmlspecialchars($suiviSearch, ENT_QUOTES) ?>">
        <div class="bilan-type" style="background:#f1f5f9;color:#94a3b8"><i class="bi bi-hourglass-split"></i></div>
        <div class="flex-grow-1">
            <div class="fw-bold small">
                <?= htmlspecialchars(($b['patient_nom'] ?? $b['nom'] ?? '?').' '.($b['patient_prenom'] ?? $b['prenom'] ?? '')) ?>
                <span class="text-muted fw-normal">#<?= htmlspecialchars($b['dossier_numero']??'') ?></span>
            </div>
            <div class="small text-muted"><?= htmlspecialchars($b['label'] ?? $b['nom_examen'] ?? 'Bilan') ?></div>
        </div>
        <span class="badge bg-secondary-subtle text-secondary rounded-pill" style="font-size:.65rem;">
            <i class="bi bi-clock me-1"></i>En attente
        </span>
        <div class="text-muted ms-2" style="font-size:.68rem;"><?= date('d/m H:i', strtotime($b['date_creation'])) ?></div>
    </div>
    <?php endforeach; ?>
    </div><!-- /bilan-section en cours -->
    <?php endif; ?>

    <!-- Section Imagerie -->
    <?php if(!empty($resultats_imagerie)): ?>
    <div class="bilan-section">
    <h6 class="fw-bold text-uppercase mt-3 mb-2" style="font-size:.75rem;letter-spacing:1px;color:#7c3aed;">
        <i class="bi bi-camera-fill me-1"></i>Imagerie à interpréter
        <span class="badge rounded-pill ms-1" style="background:#7c3aed;"><?= count($resultats_imagerie) ?></span>
    </h6>
    <?php foreach($resultats_imagerie as $img):
        $imgDate   = !empty($img['date_creation']) ? date('Y-m-d', strtotime($img['date_creation'])) : date('Y-m-d');
        $imgSearch = strtolower(($img['nom']??'').' '.($img['prenom']??'').' '.($img['dossier_numero']??'').' '.($img['type_examen']??'').' '.($img['partie_corps']??''));
    ?>
    <div class="bilan-row"
         data-date="<?= $imgDate ?>"
         data-search="<?= htmlspecialchars($imgSearch, ENT_QUOTES) ?>">
        <div class="bilan-type" style="background:#f5f3ff;color:#7c3aed"><i class="bi bi-camera-fill"></i></div>
        <div class="flex-grow-1">
            <div class="fw-bold small">
                <?= htmlspecialchars(strtoupper($img['nom']).' '.$img['prenom']) ?>
                <span class="text-muted fw-normal">#<?= htmlspecialchars($img['dossier_numero']??'') ?></span>
            </div>
            <div class="small text-muted">
                <?= htmlspecialchars($img['type_examen'] ?? '') ?>
                <?php if(!empty($img['partie_corps'])): ?> — <?= htmlspecialchars($img['partie_corps']) ?><?php endif; ?>
            </div>
        </div>
        <div class="text-muted me-2" style="font-size:.68rem;"><?= date('d/m H:i', strtotime($img['date_creation'])) ?></div>
        <button class="btn btn-sm btn-primary rounded-pill" style="font-size:.72rem;"
                onclick="ouvrirInterpretation(<?= $img['id'] ?>,
                    '<?= htmlspecialchars(addslashes(strtoupper($img['nom']).' '.$img['prenom'])) ?>',
                    '<?= htmlspecialchars(addslashes($img['type_examen'] ?? '')) ?>')">
            <i class="bi bi-pencil-fill me-1"></i>Interpréter
        </button>
        <a href="<?= BASE_URL ?>imagerie/viewer/<?= $img['id'] ?>?from=urgences"
           class="btn btn-sm btn-outline-secondary rounded-pill ms-1" style="font-size:.72rem;">
            <i class="bi bi-eye-fill"></i>
        </a>
    </div>
    <?php endforeach; ?>
    </div><!-- /bilan-section imagerie -->
    <?php endif; ?>

    <!-- Message aucun résultat filtre -->
    <div id="bilansNoResult" class="text-center py-5 d-none" style="color:#94a3b8;">
        <i class="bi bi-funnel" style="font-size:2rem;"></i>
        <p class="mt-2 mb-0">Aucun bilan ne correspond aux filtres.</p>
        <button class="btn btn-sm btn-outline-secondary mt-2 rounded-pill" onclick="resetFiltresBilans()">
            <i class="bi bi-x-circle me-1"></i>Effacer les filtres
        </button>
    </div>

<?php endif; ?>
</div>

<!-- ══ MODAL CHANGER DE SERVICE ══ -->
<div class="modal fade" id="modalChangerServiceUrg" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:480px">
        <div class="modal-content border-0 shadow-lg" style="border-radius:20px;overflow:hidden">

            <!-- En-tête -->
            <div class="modal-header border-0 py-3 px-4"
                 style="background:linear-gradient(135deg,#0f172a 0%,#1e40af 100%)">
                <div class="d-flex align-items-center gap-3 w-100">
                    <div style="width:42px;height:42px;background:rgba(255,255,255,.2);border-radius:12px;
                                display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="bi bi-shuffle text-white" style="font-size:1.1rem"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-0 text-white" style="font-size:.95rem">Changer de service</h5>
                        <small style="color:rgba(255,255,255,.7);font-size:.72rem">Basculement temporaire — votre service d'origine est conservé</small>
                    </div>
                    <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal"></button>
                </div>
            </div>

            <div class="modal-body px-4 pt-4 pb-2">

                <!-- Alerte service temporaire actif -->
                <?php if ($_estServiceTemporaireUrg): ?>
                <div class="alert border-0 rounded-3 mb-3 d-flex align-items-center gap-2 py-2 px-3"
                     style="background:#fff7ed;color:#c2410c;font-size:.82rem;">
                    <i class="bi bi-arrow-left-right fw-bold"></i>
                    <div>
                        <strong>Service temporaire actif :</strong>
                        <?= htmlspecialchars($_SESSION['nom_service'] ?? '') ?><br>
                        <small style="opacity:.75">Service d'origine : <strong><?= htmlspecialchars($_nomServiceOrigineUrg) ?></strong></small>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Service actuel -->
                <div class="mb-3 p-3 rounded-3" style="background:#f8fafc;border:1px solid #e2e8f0;">
                    <div style="font-size:.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px">Service actuel</div>
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-building-fill text-primary" style="font-size:.9rem"></i>
                        <span style="font-weight:700;color:#0f172a;font-size:.87rem">
                            <?= htmlspecialchars($_SESSION['nom_service'] ?? '') ?>
                        </span>
                    </div>
                </div>

                <!-- Services disponibles -->
                <div style="font-size:.7rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px">
                    <i class="bi bi-list-ul me-1"></i>Choisir un service de destination
                </div>

                <?php if (empty($_servicesAutorisesChgtUrg)): ?>
                <div class="text-center py-3 text-muted" style="font-size:.82rem">
                    <i class="bi bi-exclamation-triangle me-1"></i>Aucun service disponible.
                </div>
                <?php else: ?>
                <div class="d-flex flex-column gap-2 mb-3">
                    <?php foreach ($_servicesAutorisesChgtUrg as $_svcU): ?>
                    <?php
                        $_isUrgenceU = stripos($_svcU['nom_service'], 'urgence') !== false;
                        $_isCurrentU = (int)$_svcU['id'] === (int)($_SESSION['service_id'] ?? 0);
                        $_iconU  = $_isUrgenceU ? 'bi-hospital-fill' : 'bi-person-badge-fill';
                        $_colorU = $_isUrgenceU ? '#dc2626' : '#1e40af';
                        $_bgU    = $_isUrgenceU ? '#fef2f2' : '#eff6ff';
                        $_borderU= $_isUrgenceU ? '#fca5a5' : '#bfdbfe';
                    ?>
                    <button onclick="changerVersServiceUrg(<?= (int)$_svcU['id'] ?>, '<?= addslashes(htmlspecialchars($_svcU['nom_service'])) ?>')"
                            class="btn text-start d-flex align-items-center gap-3 rounded-3 service-chgt-btn-urg"
                            style="background:<?= $_bgU ?>;border:1.5px solid <?= $_isCurrentU ? $_colorU : $_borderU ?>;padding:10px 14px;transition:.15s;<?= $_isCurrentU ? 'opacity:.5;cursor:not-allowed;' : '' ?>"
                            <?= $_isCurrentU ? 'disabled title="Vous êtes déjà dans ce service"' : '' ?>>
                        <div style="width:36px;height:36px;background:<?= $_colorU ?>22;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="bi <?= $_iconU ?>" style="color:<?= $_colorU ?>;font-size:.95rem"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div style="font-weight:700;color:#0f172a;font-size:.85rem"><?= htmlspecialchars($_svcU['nom_service']) ?></div>
                            <div style="font-size:.7rem;color:#94a3b8">
                                <?= $_isUrgenceU ? 'Cockpit urgences — tous les cas' : 'Consultations programmées' ?>
                            </div>
                        </div>
                        <?php if ($_isCurrentU): ?>
                        <i class="bi bi-check-circle-fill" style="color:<?= $_colorU ?>;font-size:1rem;flex-shrink:0"></i>
                        <?php else: ?>
                        <i class="bi bi-arrow-right" style="color:#94a3b8;font-size:.9rem;flex-shrink:0"></i>
                        <?php endif; ?>
                    </button>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <div id="changerServiceUrgErr" class="alert alert-danger rounded-3 py-2 px-3 d-none" style="font-size:.8rem"></div>
            </div>

            <div class="modal-footer border-0 px-4 pb-4 pt-2 d-flex gap-2">
                <button id="btnRetourServiceOrigineUrg" type="button"
                        onclick="retournerServiceOrigineUrg()"
                        class="btn btn-outline-warning rounded-pill fw-bold flex-grow-1"
                        style="font-size:.82rem;<?= !$_estServiceTemporaireUrg ? 'display:none!important;' : '' ?>">
                    <i class="bi bi-house-fill me-1"></i>
                    Retour : <strong><?= htmlspecialchars($_nomServiceOrigineUrg ?: 'Mon service') ?></strong>
                </button>
                <button type="button" class="btn btn-outline-secondary rounded-pill <?= $_estServiceTemporaireUrg ? '' : 'flex-grow-1' ?>"
                        data-bs-dismiss="modal" style="font-size:.82rem">Fermer</button>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    let _modalCSUrg = null;

    function getModalCSUrg() {
        if (!_modalCSUrg) {
            _modalCSUrg = new bootstrap.Modal(document.getElementById('modalChangerServiceUrg'));
        }
        return _modalCSUrg;
    }

    window.ouvrirChangerServiceUrg = function() {
        document.getElementById('changerServiceUrgErr').classList.add('d-none');
        getModalCSUrg().show();
    };

    window.changerVersServiceUrg = function(serviceId, nomService) {
        const err = document.getElementById('changerServiceUrgErr');
        err.classList.add('d-none');
        document.querySelectorAll('.service-chgt-btn-urg').forEach(b => b.disabled = true);

        fetch(BASE_URL + 'dashboard/changer-service', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'service_id=' + encodeURIComponent(serviceId)
        })
        .then(r => r.json())
        .then(data => {
            if (!data.success) {
                err.textContent = data.message || 'Erreur lors du changement de service.';
                err.classList.remove('d-none');
                document.querySelectorAll('.service-chgt-btn-urg').forEach(b => b.disabled = false);
                return;
            }
            getModalCSUrg().hide();
            // Toujours passer par /dashboard pour que le routage par service s'applique
            setTimeout(() => { location.href = BASE_URL + 'dashboard'; }, 300);
        })
        .catch(() => {
            err.textContent = 'Erreur réseau.';
            err.classList.remove('d-none');
            document.querySelectorAll('.service-chgt-btn-urg').forEach(b => b.disabled = false);
        });
    };

    window.retournerServiceOrigineUrg = function() {
        const err = document.getElementById('changerServiceUrgErr');
        err.classList.add('d-none');

        fetch(BASE_URL + 'dashboard/retourner-service-origine', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: ''
        })
        .then(r => r.json())
        .then(data => {
            if (!data.success) {
                err.textContent = data.message || 'Erreur.';
                err.classList.remove('d-none');
                return;
            }
            getModalCSUrg().hide();
            setTimeout(() => { location.href = BASE_URL + 'dashboard'; }, 300);
        })
        .catch(() => {
            err.textContent = 'Erreur réseau.';
            err.classList.remove('d-none');
        });
    };
})();
</script>

<!-- ══ MODAL INTERPRÉTATION IMAGERIE ══ -->
<div class="modal fade" id="modalInterpretation" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0" style="background:linear-gradient(135deg,#7c3aed,#6d28d9);border-radius:1rem 1rem 0 0;">
                <div>
                    <h5 class="modal-title fw-bold text-white mb-0">
                        <i class="bi bi-camera-fill me-2"></i>Interpréter l'imagerie
                    </h5>
                    <small class="text-white opacity-75" id="interpPatientLabel"></small>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" id="interpImagerieId">
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Compte-rendu / Interprétation <span class="text-danger">*</span></label>
                    <textarea id="interpTexte" class="form-control rounded-3" rows="4"
                              placeholder="Décrivez les findings, observations, anomalies observées…"></textarea>
                </div>
                <div class="mb-2">
                    <label class="form-label fw-semibold small">Conclusion</label>
                    <input type="text" id="interpConclusion" class="form-control rounded-3"
                           placeholder="Ex: Pas d'anomalie détectée / Pneumonie lobaire droite…">
                </div>
            </div>
            <div class="modal-footer border-0 px-4 pb-4">
                <button type="button" class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary rounded-pill px-4 fw-bold" onclick="sauvegarderInterpretation()">
                    <i class="bi bi-check-circle me-1"></i>Valider l'interprétation
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function ouvrirInterpretation(id, patient, typeExamen) {
    document.getElementById('interpImagerieId').value = id;
    document.getElementById('interpPatientLabel').textContent = patient + ' — ' + typeExamen;
    document.getElementById('interpTexte').value = '';
    document.getElementById('interpConclusion').value = '';
    new bootstrap.Modal(document.getElementById('modalInterpretation')).show();
}

function sauvegarderInterpretation() {
    const id           = document.getElementById('interpImagerieId').value;
    const interpretation = document.getElementById('interpTexte').value.trim();
    const conclusion   = document.getElementById('interpConclusion').value.trim();

    if (!interpretation) {
        document.getElementById('interpTexte').classList.add('is-invalid');
        return;
    }
    document.getElementById('interpTexte').classList.remove('is-invalid');

    const fd = new FormData();
    fd.append('imagerie_id',   id);
    fd.append('interpretation', interpretation);
    fd.append('conclusion',    conclusion);

    fetch('<?= BASE_URL ?>imagerie/save-interpretation', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => {
            bootstrap.Modal.getInstance(document.getElementById('modalInterpretation')).hide();
            if (d.success) {
                // Retirer la ligne de la liste
                location.reload();
            } else {
                alert('Erreur lors de la sauvegarde.');
            }
        })
        .catch(() => alert('Erreur réseau.'));
}
</script>

<!-- ════ ONGLET 6 : AGENDA DU JOUR ════ -->
<div class="tab-pane fade tab-pane-body" id="tabAgenda">
    <?php if(empty($mes_rdv)): ?>
    <div class="empty-state"><i class="bi bi-calendar2-x"></i><h5>Aucun rendez-vous aujourd'hui</h5></div>
    <?php else: ?>
    <?php foreach($mes_rdv as $rdv):
        $rdvColors = ['CONFIRME'=>'3b82f6','EN_ATTENTE'=>'f59e0b','EN_COURS'=>'22c55e'];
        $rdvColor = $rdvColors[$rdv['statut']] ?? '94a3b8';
        $ini = strtoupper(substr($rdv['nom'],0,1).substr($rdv['prenom']??'',0,1));
    ?>
    <div class="rdv-card" style="border-left-color:#<?= $rdvColor ?>">
        <div class="pw-avatar" style="background:linear-gradient(135deg,#<?= $rdvColor ?>,#<?= $rdvColor ?>99)"><?= $ini ?></div>
        <div class="flex-grow-1">
            <div class="fw-bold"><?= htmlspecialchars(strtoupper($rdv['nom']).' '.($rdv['prenom']??'')) ?></div>
            <div class="small text-muted">#<?= htmlspecialchars($rdv['dossier_numero']??'') ?> • <?= htmlspecialchars($rdv['motif']??'RDV') ?></div>
        </div>
        <div class="text-end">
            <div class="fw-bold" style="color:#<?= $rdvColor ?>"><?= date('H:i', strtotime($rdv['date_debut'])) ?></div>
            <span class="badge" style="background:#<?= $rdvColor ?>;font-size:.62rem"><?= $rdv['statut'] ?></span>
        </div>
        <a href="<?= BASE_URL ?>consultation/formulaire?patient_id=<?= $rdv['patient_id'] ?>&type=EXTERNE&etape=1" class="btn btn-primary btn-sm rounded-pill ms-2" style="font-size:.72rem">
            <i class="bi bi-stethoscope"></i>
        </a>
    </div>
    <?php endforeach; ?>

    <?php endif; ?>
</div>

<!-- ════ ONGLET : DOSSIERS PARTAGÉS ════ -->
<div class="tab-pane fade tab-pane-body" id="tabPartages">

    <!-- ── En-tête avec bouton partager ── -->
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
        <div>
            <h5 class="fw-bold mb-1" style="color:#0f172a">
                <i class="bi bi-share-fill me-2" style="color:#10b981"></i>Dossiers Partagés
            </h5>
            <p class="text-muted small mb-0">Consultations partagées entre médecins — valables 24h</p>
        </div>
        <button class="btn fw-bold text-white rounded-pill px-4"
                style="background:linear-gradient(135deg,#10b981,#059669);border:none"
                onclick="ouvrirModalPartage()">
            <i class="bi bi-plus-circle-fill me-2"></i>Partager un dossier
        </button>
    </div>

    <!-- ── Sous-onglets Reçus / Envoyés ── -->
    <ul class="nav nav-pills gap-2 mb-4" id="partageSubTabs">
        <li class="nav-item">
            <button class="nav-link active fw-semibold rounded-pill px-4"
                    style="font-size:.82rem" data-bs-toggle="pill" data-bs-target="#pTabRecus">
                <i class="bi bi-inbox-fill me-1"></i>Reçus
                <?php if(count($dossiers_recus)>0): ?>
                    <span class="badge ms-1 rounded-pill" style="background:#10b981"><?= count($dossiers_recus) ?></span>
                <?php endif; ?>
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link fw-semibold rounded-pill px-4"
                    style="font-size:.82rem" data-bs-toggle="pill" data-bs-target="#pTabEnvoyes">
                <i class="bi bi-send-fill me-1"></i>Envoyés
                <?php if(count($dossiers_envoyes)>0): ?>
                    <span class="badge ms-1 rounded-pill bg-secondary"><?= count($dossiers_envoyes) ?></span>
                <?php endif; ?>
            </button>
        </li>
    </ul>

    <div class="tab-content">

        <!-- ─── SOUS-ONGLET : REÇUS ─── -->
        <div class="tab-pane fade show active" id="pTabRecus">
            <?php if(empty($dossiers_recus)): ?>
            <div class="empty-state">
                <i class="bi bi-inbox" style="color:#10b981;opacity:.4"></i>
                <p class="mt-2 mb-0">Aucun dossier partagé avec vous en ce moment.</p>
            </div>
            <?php else: ?>
            <div class="row g-3">
            <?php foreach($dossiers_recus as $dr):
                $isNew = $dr['statut'] === 'en_attente';
                $expDate = new DateTime($dr['date_expiration']);
                $now = new DateTime();
                $diff = $now->diff($expDate);
                $expireStr = $diff->h > 0 ? "expire dans {$diff->h}h{$diff->i}m" : "expire dans {$diff->i} min";
                $initiales = strtoupper(substr($dr['nom'],0,1).substr($dr['prenom'],0,1));
            ?>
            <div class="col-lg-6">
                <div class="p-3 rounded-3 border h-100 position-relative"
                     style="background:#fff;border-color:<?= $isNew ? '#10b981' : '#e2e8f0' ?>!important;
                            box-shadow:<?= $isNew ? '0 0 0 2px rgba(16,185,129,.18)' : 'none' ?>">
                    <?php if($isNew): ?>
                    <span class="position-absolute top-0 end-0 badge rounded-pill m-2"
                          style="background:#10b981;font-size:.62rem">NOUVEAU</span>
                    <?php endif; ?>

                    <!-- Patient -->
                    <div class="d-flex align-items-center gap-3 mb-2">
                        <div class="pw-avatar" style="background:linear-gradient(135deg,#10b981,#059669);width:42px;height:42px;font-size:.82rem">
                            <?= $initiales ?>
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-bold" style="font-size:.9rem"><?= htmlspecialchars(strtoupper($dr['nom']).' '.$dr['prenom']) ?></div>
                            <div class="text-muted" style="font-size:.72rem">
                                <i class="bi bi-file-earmark-medical me-1"></i><?= htmlspecialchars($dr['dossier_numero'] ?? '') ?>
                                <?php
                                if(!empty($dr['date_naissance'])){
                                    $age = (int)(new DateTime())->diff(new DateTime($dr['date_naissance']))->y;
                                    echo " · {$age} ans";
                                }
                                ?>
                            </div>
                        </div>
                    </div>

                    <!-- Expéditeur + date -->
                    <div class="d-flex gap-2 flex-wrap mb-2">
                        <span class="badge rounded-pill" style="background:#eff6ff;color:#1e40af;font-size:.68rem">
                            <i class="bi bi-person-badge-fill me-1"></i>De Dr <?= htmlspecialchars($dr['exp_prenom'].' '.$dr['exp_nom']) ?>
                        </span>
                        <?php if(!empty($dr['nom_service'])): ?>
                        <span class="badge rounded-pill" style="background:#f5f3ff;color:#7c3aed;font-size:.68rem">
                            <i class="bi bi-building-fill me-1"></i><?= htmlspecialchars($dr['nom_service']) ?>
                        </span>
                        <?php endif; ?>
                        <span class="badge rounded-pill" style="background:#fef9c3;color:#854d0e;font-size:.68rem">
                            <i class="bi bi-clock me-1"></i><?= $expireStr ?>
                        </span>
                    </div>

                    <!-- Avis de l'expéditeur -->
                    <?php if(!empty($dr['avis_medecin'])): ?>
                    <div class="p-2 rounded-2 mb-2" style="background:#f0fdf4;border-left:3px solid #10b981;font-size:.8rem">
                        <i class="bi bi-chat-quote-fill me-1" style="color:#10b981"></i><?= htmlspecialchars($dr['avis_medecin']) ?>
                    </div>
                    <?php endif; ?>

                    <!-- Actions -->
                    <div class="d-flex gap-2 mt-2">
                        <a href="<?= BASE_URL ?>patients/dossier/<?= (int)$dr['patient_id'] ?>"
                           class="btn btn-sm rounded-pill fw-bold text-white flex-grow-1"
                           style="background:linear-gradient(135deg,#1e40af,#3b82f6);font-size:.72rem"
                           onclick="marquerPartageVu(<?= (int)$dr['partage_id'] ?>)">
                            <i class="bi bi-folder2-open me-1"></i>Consulter le dossier
                        </a>
                        <a href="<?= BASE_URL ?>urgences/consultation/<?= (int)$dr['patient_id'] ?>"
                           class="btn btn-sm rounded-pill btn-outline-success fw-bold"
                           style="font-size:.72rem"
                           onclick="marquerPartageVu(<?= (int)$dr['partage_id'] ?>)">
                            <i class="bi bi-clipboard2-pulse"></i>
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div><!-- /pTabRecus -->

        <!-- ─── SOUS-ONGLET : ENVOYÉS ─── -->
        <div class="tab-pane fade" id="pTabEnvoyes">
            <?php if(empty($dossiers_envoyes)): ?>
            <div class="empty-state">
                <i class="bi bi-send" style="color:#64748b;opacity:.4"></i>
                <p class="mt-2 mb-0">Vous n'avez partagé aucun dossier récemment.</p>
            </div>
            <?php else: ?>
            <div class="row g-3">
            <?php foreach($dossiers_envoyes as $de):
                $isVu = $de['statut'] === 'vu';
                $expDate = new DateTime($de['date_expiration']);
                $now2 = new DateTime();
                $diff2 = $now2->diff($expDate);
                $expStr2 = $diff2->h > 0 ? "expire dans {$diff2->h}h{$diff2->i}m" : "expire dans {$diff2->i} min";
                $ini2 = strtoupper(substr($de['nom'],0,1).substr($de['prenom'],0,1));
            ?>
            <div class="col-lg-6">
                <div class="p-3 rounded-3 border h-100"
                     style="background:#fff;border-color:#e2e8f0!important">
                    <div class="d-flex align-items-center gap-3 mb-2">
                        <div class="pw-avatar" style="background:linear-gradient(135deg,#64748b,#475569);width:42px;height:42px;font-size:.82rem">
                            <?= $ini2 ?>
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-bold" style="font-size:.9rem"><?= htmlspecialchars(strtoupper($de['nom']).' '.$de['prenom']) ?></div>
                            <div class="text-muted" style="font-size:.72rem"><i class="bi bi-file-earmark-medical me-1"></i><?= htmlspecialchars($de['dossier_numero'] ?? '') ?></div>
                        </div>
                        <span class="badge rounded-pill" style="background:<?= $isVu ? '#dcfce7' : '#fef3c7' ?>;color:<?= $isVu ? '#166534' : '#92400e' ?>;font-size:.65rem">
                            <?= $isVu ? '<i class="bi bi-check2-circle me-1"></i>Consulté' : '<i class="bi bi-hourglass-split me-1"></i>En attente' ?>
                        </span>
                    </div>
                    <div class="d-flex gap-2 flex-wrap mb-2">
                        <span class="badge rounded-pill" style="background:#eff6ff;color:#1e40af;font-size:.68rem">
                            <i class="bi bi-person-badge-fill me-1"></i>Pour Dr <?= htmlspecialchars($de['dest_prenom'].' '.$de['dest_nom']) ?>
                        </span>
                        <?php if(!empty($de['nom_service'])): ?>
                        <span class="badge rounded-pill" style="background:#f5f3ff;color:#7c3aed;font-size:.68rem">
                            <i class="bi bi-building-fill me-1"></i><?= htmlspecialchars($de['nom_service']) ?>
                        </span>
                        <?php endif; ?>
                        <span class="badge rounded-pill" style="background:#fef9c3;color:#854d0e;font-size:.68rem">
                            <i class="bi bi-clock me-1"></i><?= $expStr2 ?>
                        </span>
                    </div>
                    <?php if(!empty($de['avis_medecin'])): ?>
                    <div class="p-2 rounded-2" style="background:#f8fafc;border-left:3px solid #94a3b8;font-size:.78rem">
                        <i class="bi bi-chat-quote me-1 text-muted"></i><?= htmlspecialchars($de['avis_medecin']) ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div><!-- /pTabEnvoyes -->

    </div><!-- /pill-content -->

</div><!-- /tabPartages -->

</div><!-- /tab-content -->

<!-- ══════════════════════════════════════════════════════
     MODAL SUIVI RAPIDE INFIRMIER — Cockpit médecin urgences
══════════════════════════════════════════════════════ -->
<!-- ════ MODAL PARTAGER UN DOSSIER ════ -->
<div class="modal fade" id="modalPartagerDossier" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius:20px;overflow:hidden">

            <div class="modal-header border-0 px-4 pt-4 pb-3"
                 style="background:linear-gradient(135deg,#10b981,#059669)">
                <div>
                    <h5 class="modal-title fw-bold text-white mb-0">
                        <i class="bi bi-share-fill me-2"></i>Partager un dossier patient
                    </h5>
                    <small class="text-white opacity-75">Le médecin destinataire recevra un accès de 24h</small>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body p-4">
                <form id="formPartagerDossier" onsubmit="soumettrePartage(event)">

                    <!-- Recherche patient -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-uppercase" style="color:#374151;letter-spacing:.5px">
                            <i class="bi bi-person-fill me-1 text-success"></i>Patient
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" id="partagePatientSearch" class="form-control border-start-0 ps-0 rounded-end"
                                   placeholder="Rechercher par nom, prénom ou N° dossier…"
                                   oninput="rechercherPatientPartage(this.value)"
                                   autocomplete="off">
                        </div>
                        <input type="hidden" id="partagePatientId" name="patient_id">
                        <!-- Suggestions -->
                        <div id="partagePatientSuggestions" class="border rounded-3 bg-white shadow-sm mt-1 d-none"
                             style="max-height:180px;overflow-y:auto;position:relative;z-index:10"></div>
                        <!-- Sélectionné -->
                        <div id="partagePatientSelected" class="d-none mt-2 p-2 rounded-3 d-flex align-items-center gap-2"
                             style="background:#f0fdf4;border:1px solid #10b981">
                            <i class="bi bi-person-check-fill text-success"></i>
                            <span id="partagePatientLabel" class="fw-semibold small"></span>
                            <button type="button" class="btn-close btn-sm ms-auto" style="font-size:.6rem"
                                    onclick="resetPatientPartage()"></button>
                        </div>
                    </div>

                    <!-- Médecin destinataire -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-uppercase" style="color:#374151;letter-spacing:.5px">
                            <i class="bi bi-person-badge-fill me-1 text-primary"></i>Médecin destinataire
                        </label>
                        <select name="destinataire_id" id="partageDestinataire" class="form-select rounded-3" required>
                            <option value="">— Choisir un médecin —</option>
                            <?php foreach($medecins_liste as $med): ?>
                            <option value="<?= (int)$med['id'] ?>"
                                    data-service="<?= (int)($med['service_id'] ?? 0) ?>">
                                Dr <?= htmlspecialchars($med['prenom'].' '.$med['nom']) ?>
                                <?= !empty($med['nom_service']) ? '· '.$med['nom_service'] : '' ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="hidden" name="service_id" id="partageServiceId">
                    </div>

                    <!-- Avis / note (optionnel) -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold small text-uppercase" style="color:#374151;letter-spacing:.5px">
                            <i class="bi bi-chat-text-fill me-1" style="color:#10b981"></i>Note / Avis (optionnel)
                        </label>
                        <textarea name="avis_medecin" id="partageAvis" class="form-control rounded-3"
                                  rows="3" placeholder="Motif du partage, orientation, question clinique…"
                                  style="resize:none"></textarea>
                    </div>

                    <!-- Alerte résultat -->
                    <div id="partageAlert" class="d-none mb-3 alert rounded-3 py-2 px-3" style="font-size:.84rem"></div>

                    <div class="d-flex gap-2 justify-content-end">
                        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" id="partageSubmitBtn"
                                class="btn fw-bold text-white rounded-pill px-5"
                                style="background:linear-gradient(135deg,#10b981,#059669);border:none">
                            <i class="bi bi-share-fill me-2"></i>Partager
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>

<!-- ════ MODAL RÉÉVALUATIONS MÉDICALES ════ -->
<div class="modal fade" id="modalReevaluations" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg" style="border-radius:20px;overflow:hidden">

            <!-- Header -->
            <div class="modal-header border-0 px-4 pt-4 pb-3"
                 style="background:linear-gradient(135deg,#f59e0b,#d97706)">
                <div>
                    <h5 class="modal-title fw-bold text-white mb-0">
                        <i class="bi bi-journal-medical me-2"></i>Réévaluations médicales
                    </h5>
                    <small class="text-white opacity-75" id="rrPatientLabel"></small>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <a id="rrLienComplet" href="#" class="btn btn-sm btn-light rounded-pill fw-semibold"
                       style="font-size:.72rem" target="_blank">
                        <i class="bi bi-box-arrow-up-right me-1"></i>Page complète
                    </a>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
            </div>

            <!-- Body -->
            <div class="modal-body p-0">

                <!-- Loader -->
                <div id="rrLoader" class="text-center py-5">
                    <div class="spinner-border text-warning" role="status"></div>
                    <p class="text-muted mt-2 small">Chargement des réévaluations…</p>
                </div>

                <!-- Contenu -->
                <div id="rrContent" class="d-none p-4"></div>

                <!-- Erreur -->
                <div id="rrError" class="d-none text-center py-5 text-danger">
                    <i class="bi bi-exclamation-triangle-fill fs-2"></i>
                    <p class="mt-2 small" id="rrErrorMsg">Erreur de chargement.</p>
                </div>

            </div><!-- /modal-body -->
        </div>
    </div>
</div>

<div class="modal fade" id="modalSuiviRapide" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg" style="border-radius:20px;overflow:hidden">

            <!-- Header -->
            <div class="modal-header border-0 px-4 pt-4 pb-3"
                 style="background:linear-gradient(135deg,#7c3aed,#6d28d9)">
                <div>
                    <h5 class="modal-title fw-bold text-white mb-0">
                        <i class="bi bi-clipboard2-heart-fill me-2"></i>Suivi infirmier
                    </h5>
                    <small class="text-white opacity-75" id="srPatientLabel"></small>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <a id="srLienComplet" href="#" class="btn btn-sm btn-light rounded-pill fw-semibold"
                       style="font-size:.72rem" target="_blank">
                        <i class="bi bi-box-arrow-up-right me-1"></i>Page complète
                    </a>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
            </div>

            <!-- Body -->
            <div class="modal-body p-0" id="srBody">

                <!-- Squelette de chargement -->
                <div id="srLoader" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="text-muted mt-2 small">Chargement des données infirmières…</p>
                </div>

                <!-- Contenu chargé dynamiquement -->
                <div id="srContent" class="d-none">

                    <!-- Bandeau constantes -->
                    <div id="srConstantes" class="px-4 pt-3 pb-2"
                         style="background:#f8fafc;border-bottom:1px solid #e2e8f0"></div>

                    <!-- Compteurs soins du jour -->
                    <div id="srStatsSoins" class="px-4 py-3"
                         style="border-bottom:1px solid #e2e8f0"></div>

                    <!-- Liste soins -->
                    <div class="px-4 py-3">
                        <h6 class="fw-bold text-uppercase mb-2"
                            style="font-size:.72rem;letter-spacing:1px;color:#7c3aed">
                            <i class="bi bi-list-check me-1"></i>Soins du jour
                        </h6>
                        <div id="srListeSoins"></div>
                    </div>

                    <!-- Notes infirmières -->
                    <div class="px-4 pb-4" id="srNotesWrap">
                        <h6 class="fw-bold text-uppercase mb-2"
                            style="font-size:.72rem;letter-spacing:1px;color:#0891b2">
                            <i class="bi bi-chat-text-fill me-1"></i>Notes / Observations (48h)
                        </h6>
                        <div id="srListeNotes"></div>
                    </div>

                </div><!-- /srContent -->

                <!-- État erreur -->
                <div id="srError" class="d-none text-center py-5 text-danger">
                    <i class="bi bi-exclamation-triangle-fill fs-2"></i>
                    <p class="mt-2 small" id="srErrorMsg">Erreur de chargement.</p>
                </div>

            </div><!-- /modal-body -->

        </div>
    </div>
</div>

<script>
// ── SUIVI RAPIDE INFIRMIER ──────────────────────────────────────────────────
const _BURL_SR = '<?= BASE_URL ?>';

function ouvrirSuiviRapide(patientId, nomPatient) {
    // Réinitialiser
    document.getElementById('srPatientLabel').textContent = nomPatient;
    document.getElementById('srLienComplet').href = _BURL_SR + 'hospitalisation/suivi/' + patientId;
    document.getElementById('srLoader').classList.remove('d-none');
    document.getElementById('srContent').classList.add('d-none');
    document.getElementById('srError').classList.add('d-none');

    new bootstrap.Modal(document.getElementById('modalSuiviRapide')).show();

    fetch(_BURL_SR + 'hospitalisation/suivi-rapide/' + patientId, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        document.getElementById('srLoader').classList.add('d-none');
        if (!data.success) {
            document.getElementById('srErrorMsg').textContent = data.message || 'Erreur inconnue.';
            document.getElementById('srError').classList.remove('d-none');
            return;
        }
        _srRenderConstantes(data.constantes);
        _srRenderStats(data.stats);
        _srRenderSoins(data.soins);
        _srRenderNotes(data.notes);
        document.getElementById('srContent').classList.remove('d-none');
    })
    .catch(err => {
        document.getElementById('srLoader').classList.add('d-none');
        document.getElementById('srErrorMsg').textContent = 'Erreur réseau : ' + err.message;
        document.getElementById('srError').classList.remove('d-none');
    });
}

function _srRenderConstantes(c) {
    const el = document.getElementById('srConstantes');
    if (!c) {
        el.innerHTML = '<p class="text-muted small mb-0"><i class="bi bi-dash-circle me-1"></i>Aucune constante enregistrée.</p>';
        return;
    }
    const fmt = (v, u) => v ? `<strong>${v}</strong><small class="text-muted ms-1">${u}</small>` : '<span class="text-muted">—</span>';
    const dateStr = c.date_mesure ? new Date(c.date_mesure).toLocaleString('fr-FR',{day:'2-digit',month:'2-digit',hour:'2-digit',minute:'2-digit'}) : '';
    el.innerHTML = `
        <div class="d-flex align-items-center gap-1 flex-wrap mb-1">
            <span class="fw-bold text-uppercase" style="font-size:.7rem;color:#64748b;letter-spacing:1px">
                <i class="bi bi-activity me-1 text-danger"></i>Dernières constantes
            </span>
            <span class="text-muted ms-auto" style="font-size:.68rem">${dateStr}</span>
        </div>
        <div class="d-flex gap-3 flex-wrap">
            <div class="v-block">${fmt(c.tension_systolique && c.tension_diastolique ? c.tension_systolique+'/'+c.tension_diastolique : null,'mmHg')}<small class="d-block" style="font-size:.58rem;color:#94a3b8">TA</small></div>
            <div class="v-block">${fmt(c.pouls,'bpm')}<small class="d-block" style="font-size:.58rem;color:#94a3b8">FC</small></div>
            <div class="v-block">${fmt(c.temperature ? c.temperature+'°C' : null,'')}<small class="d-block" style="font-size:.58rem;color:#94a3b8">T°</small></div>
            <div class="v-block">${fmt(c.saturation_o2 ? c.saturation_o2+'%' : null,'')}<small class="d-block" style="font-size:.58rem;color:#94a3b8">SpO2</small></div>
            <div class="v-block">${fmt(c.frequence_respiratoire,'c/min')}<small class="d-block" style="font-size:.58rem;color:#94a3b8">FR</small></div>
            ${c.glycemie ? `<div class="v-block">${fmt(c.glycemie,'g/L')}<small class="d-block" style="font-size:.58rem;color:#94a3b8">Glycémie</small></div>` : ''}
            ${c.poids ? `<div class="v-block">${fmt(c.poids,'kg')}<small class="d-block" style="font-size:.58rem;color:#94a3b8">Poids</small></div>` : ''}
        </div>
    `;
}

function _srRenderStats(s) {
    const el = document.getElementById('srStatsSoins');
    if (!s || s.total === 0) {
        el.innerHTML = '<p class="text-muted small mb-0"><i class="bi bi-calendar2-x me-1"></i>Aucun soin planifié aujourd\'hui.</p>';
        return;
    }
    const pct = s.total > 0 ? Math.round(s.realises / s.total * 100) : 0;
    const barColor = pct >= 80 ? '#22c55e' : pct >= 40 ? '#f59e0b' : '#ef4444';
    el.innerHTML = `
        <div class="d-flex align-items-center gap-3 flex-wrap mb-2">
            <span class="fw-bold small text-uppercase" style="color:#7c3aed;letter-spacing:.5px">
                <i class="bi bi-clipboard2-check-fill me-1"></i>Soins du jour · ${s.total} au total
            </span>
            <span class="ms-auto fw-bold" style="color:${barColor};font-size:.9rem">${pct}% réalisés</span>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <span class="badge rounded-pill" style="background:#dcfce7;color:#16a34a;font-size:.7rem">
                <i class="bi bi-check-circle-fill me-1"></i>${s.realises} réalisé${s.realises>1?'s':''}
            </span>
            <span class="badge rounded-pill" style="background:#f1f5f9;color:#64748b;font-size:.7rem">
                <i class="bi bi-clock me-1"></i>${s.planifies} planifié${s.planifies>1?'s':''}
            </span>
            ${s.retards > 0 ? `<span class="badge rounded-pill" style="background:#fef3c7;color:#d97706;font-size:.7rem"><i class="bi bi-exclamation-triangle-fill me-1"></i>${s.retards} en retard</span>` : ''}
            ${s.annules > 0 ? `<span class="badge rounded-pill" style="background:#fee2e2;color:#dc2626;font-size:.7rem"><i class="bi bi-x-circle-fill me-1"></i>${s.annules} annulé${s.annules>1?'s':''}</span>` : ''}
        </div>
        <div class="mt-2" style="height:6px;background:#e2e8f0;border-radius:3px;overflow:hidden">
            <div style="height:100%;width:${pct}%;background:${barColor};border-radius:3px;transition:width .4s"></div>
        </div>
    `;
}

function _srRenderSoins(soins) {
    const el = document.getElementById('srListeSoins');
    if (!soins || soins.length === 0) {
        el.innerHTML = '<p class="text-muted small">Aucun soin enregistré aujourd\'hui.</p>';
        return;
    }
    const statutCfg = {
        'REALISE' : { bg:'#dcfce7', color:'#16a34a', icon:'bi-check-circle-fill' },
        'PLANIFIE': { bg:'#eff6ff', color:'#2563eb', icon:'bi-clock'             },
        'RETARD'  : { bg:'#fef3c7', color:'#d97706', icon:'bi-exclamation-triangle-fill' },
        'ANNULE'  : { bg:'#fee2e2', color:'#dc2626', icon:'bi-x-circle-fill'      },
        'SUPPRIME': { bg:'#f1f5f9', color:'#94a3b8', icon:'bi-trash3'             },
    };
    el.innerHTML = soins.map(s => {
        const cfg   = statutCfg[s.statut] || statutCfg['PLANIFIE'];
        const heureRef = s.date_realisee || s.date_prevue || null;
        const heure = heureRef
            ? new Date(heureRef).toLocaleTimeString('fr-FR',{hour:'2-digit',minute:'2-digit'})
            : '';
        return `
        <div class="d-flex align-items-center gap-2 py-2" style="border-bottom:1px solid #f1f5f9">
            <span style="width:22px;height:22px;border-radius:50%;background:${cfg.bg};color:${cfg.color};
                         display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:.75rem">
                <i class="bi ${cfg.icon}"></i>
            </span>
            <div class="flex-grow-1">
                <span class="small fw-semibold">${s.intitule_soin || '—'}</span>
                ${s.note_major ? `<div class="text-muted" style="font-size:.7rem">${s.note_major}</div>` : ''}
            </div>
            <div class="text-end flex-shrink-0">
                ${heure ? `<div class="fw-bold" style="font-size:.7rem;color:#64748b">${heure}</div>` : ''}
                ${s.infirmier_nom ? `<div class="text-muted" style="font-size:.65rem">${s.infirmier_nom}</div>` : ''}
            </div>
        </div>`;
    }).join('');
}

function _srRenderNotes(notes) {
    const wrap = document.getElementById('srNotesWrap');
    const el   = document.getElementById('srListeNotes');
    if (!notes || notes.length === 0) {
        wrap.style.display = 'none';
        return;
    }
    wrap.style.display = '';
    el.innerHTML = notes.map(n => {
        const dt = n.created_at ? new Date(n.created_at).toLocaleString('fr-FR',{day:'2-digit',month:'2-digit',hour:'2-digit',minute:'2-digit'}) : '';
        return `
        <div class="p-2 rounded-3 mb-2" style="background:#f0f9ff;border-left:3px solid #0891b2">
            <div class="small">${n.texte || ''}</div>
            <div class="text-muted mt-1" style="font-size:.65rem">
                ${n.auteur ? '<i class="bi bi-person-fill me-1"></i>' + n.auteur + ' · ' : ''}${dt}
            </div>
        </div>`;
    }).join('');
}

// ── DOSSIERS PARTAGÉS ─────────────────────────────────────────────────────────
let _partagePatientData = null;
let _partageSearchTimer = null;

function ouvrirModalPartage() {
    resetPatientPartage();
    document.getElementById('partageDestinataire').value = '';
    document.getElementById('partageAvis').value = '';
    document.getElementById('partageAlert').classList.add('d-none');
    document.getElementById('partageSubmitBtn').disabled = false;
    new bootstrap.Modal(document.getElementById('modalPartagerDossier')).show();
}

function rechercherPatientPartage(q) {
    clearTimeout(_partageSearchTimer);
    const sugg = document.getElementById('partagePatientSuggestions');
    if (q.length < 2) { sugg.classList.add('d-none'); return; }
    _partageSearchTimer = setTimeout(() => {
        fetch(_BURL_SR + 'consultation/search-patients?q=' + encodeURIComponent(q), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            const patients = Array.isArray(data) ? data : (data.patients || []);
            if (!patients.length) { sugg.innerHTML = '<div class="p-2 text-muted small">Aucun résultat</div>'; }
            else {
                sugg.innerHTML = patients.map(p => `
                    <div class="p-2 border-bottom small d-flex align-items-center gap-2"
                         style="cursor:pointer" onclick="selectionnerPatientPartage(${p.id},'${(p.nom||'').replace(/'/g,"\\'")} ${(p.prenom||'').replace(/'/g,"\\'")}','${(p.dossier_numero||'').replace(/'/g,"\\'")}')">
                        <i class="bi bi-person-fill text-success"></i>
                        <span class="fw-semibold">${p.nom || ''} ${p.prenom || ''}</span>
                        <span class="text-muted ms-auto">${p.dossier_numero || ''}</span>
                    </div>`).join('');
            }
            sugg.classList.remove('d-none');
        })
        .catch(() => { sugg.classList.add('d-none'); });
    }, 300);
}

function selectionnerPatientPartage(id, nom, dossier) {
    _partagePatientData = { id, nom, dossier };
    document.getElementById('partagePatientId').value = id;
    document.getElementById('partagePatientSearch').value = '';
    document.getElementById('partagePatientLabel').textContent = nom + ' — ' + dossier;
    document.getElementById('partagePatientSelected').classList.remove('d-none');
    document.getElementById('partagePatientSuggestions').classList.add('d-none');
}

function resetPatientPartage() {
    _partagePatientData = null;
    document.getElementById('partagePatientId').value = '';
    document.getElementById('partagePatientSearch').value = '';
    document.getElementById('partagePatientSelected').classList.add('d-none');
    document.getElementById('partagePatientSuggestions').classList.add('d-none');
}

// Synchroniser service_id avec le médecin sélectionné
document.addEventListener('DOMContentLoaded', () => {
    const sel = document.getElementById('partageDestinataire');
    if (sel) {
        sel.addEventListener('change', () => {
            const opt = sel.options[sel.selectedIndex];
            document.getElementById('partageServiceId').value = opt ? opt.dataset.service || '' : '';
        });
    }
});

function soumettrePartage(e) {
    e.preventDefault();
    const patientId = document.getElementById('partagePatientId').value;
    const alert = document.getElementById('partageAlert');
    if (!patientId) {
        alert.className = 'alert alert-warning rounded-3 py-2 px-3 mb-3';
        alert.innerHTML = '<i class="bi bi-exclamation-triangle-fill me-1"></i>Veuillez sélectionner un patient.';
        alert.classList.remove('d-none');
        return;
    }
    const btn = document.getElementById('partageSubmitBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Envoi…';

    const fd = new FormData(document.getElementById('formPartagerDossier'));
    fetch(_BURL_SR + 'urgences/partager-dossier', {
        method: 'POST', body: fd,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert.className = 'alert alert-success rounded-3 py-2 px-3 mb-3';
            alert.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i>Dossier partagé avec succès ! Le médecin a maintenant accès pendant 24h.';
            alert.classList.remove('d-none');
            btn.innerHTML = '<i class="bi bi-check2 me-2"></i>Partagé !';
            setTimeout(() => {
                bootstrap.Modal.getInstance(document.getElementById('modalPartagerDossier'))?.hide();
                // Recharger l'onglet partagés en rafraîchissant la page
                window.location.href = window.location.pathname + '#tabPartages';
                window.location.reload();
            }, 1500);
        } else {
            alert.className = 'alert alert-danger rounded-3 py-2 px-3 mb-3';
            alert.innerHTML = '<i class="bi bi-x-circle-fill me-1"></i>' + (data.message || 'Erreur lors du partage.');
            alert.classList.remove('d-none');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-share-fill me-2"></i>Partager';
        }
    })
    .catch(() => {
        alert.className = 'alert alert-danger rounded-3 py-2 px-3 mb-3';
        alert.innerHTML = '<i class="bi bi-x-circle-fill me-1"></i>Erreur réseau.';
        alert.classList.remove('d-none');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-share-fill me-2"></i>Partager';
    });
}

function marquerPartageVu(partageId) {
    if (!partageId) return;
    fetch(_BURL_SR + 'urgences/partage-vu/' + partageId, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    }).catch(() => {});
}

// ── RÉÉVALUATIONS MÉDICALES RAPIDE ────────────────────────────────────────────
function ouvrirReevaluations(patientId, nomPatient) {
    document.getElementById('rrPatientLabel').textContent = nomPatient;
    document.getElementById('rrLienComplet').href = _BURL_SR + 'hospitalisation/suivi/' + patientId + '#revMed';
    document.getElementById('rrLoader').classList.remove('d-none');
    document.getElementById('rrContent').classList.add('d-none');
    document.getElementById('rrError').classList.add('d-none');

    new bootstrap.Modal(document.getElementById('modalReevaluations')).show();

    fetch(_BURL_SR + 'hospitalisation/reevaluations-rapide/' + patientId, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        document.getElementById('rrLoader').classList.add('d-none');
        if (!data.success) {
            document.getElementById('rrErrorMsg').textContent = data.message || 'Erreur inconnue.';
            document.getElementById('rrError').classList.remove('d-none');
            return;
        }
        _rrRender(data.reevaluations);
        document.getElementById('rrContent').classList.remove('d-none');
    })
    .catch(err => {
        document.getElementById('rrLoader').classList.add('d-none');
        document.getElementById('rrErrorMsg').textContent = 'Erreur réseau : ' + err.message;
        document.getElementById('rrError').classList.remove('d-none');
    });
}

function _rrRender(revs) {
    const el = document.getElementById('rrContent');
    if (!revs || revs.length === 0) {
        el.innerHTML = `
            <div class="text-center py-5 text-muted">
                <i class="bi bi-journal-x" style="font-size:2.5rem;color:#d97706;opacity:.4"></i>
                <p class="mt-3 mb-0 small">Aucune réévaluation médicale enregistrée.</p>
            </div>`;
        return;
    }

    const EVOL_STYLE = {
        'amelioration': { bg:'#dcfce7', border:'#22c55e', icon:'bi-arrow-up-circle-fill', color:'#16a34a', label:'Amélioration' },
        'stable':       { bg:'#fefce8', border:'#eab308', icon:'bi-dash-circle-fill',     color:'#ca8a04', label:'Stable' },
        'aggravation':  { bg:'#fee2e2', border:'#ef4444', icon:'bi-arrow-down-circle-fill',color:'#dc2626', label:'Aggravation' },
    };

    el.innerHTML = revs.map((r, idx) => {
        const dateStr = r.date_reevaluation
            ? new Date(r.date_reevaluation + 'T' + (r.heure_reevaluation || '00:00'))
                .toLocaleDateString('fr-FR', {weekday:'short', day:'2-digit', month:'long', year:'numeric'})
            : '';
        const heureStr = r.heure_reevaluation ? r.heure_reevaluation.slice(0,5) : '';
        const ev = EVOL_STYLE[r.evolution_globale] || { bg:'#f1f5f9', border:'#94a3b8', icon:'bi-circle', color:'#64748b', label: r.evolution_globale || '—' };
        const medecinStr = [r.medecin_prenom, r.medecin_nom].filter(Boolean).join(' ');

        // Bilans
        const bilansHtml = r.bilans && r.bilans.length ? `
            <div class="mt-2">
                <div class="fw-semibold mb-1" style="font-size:.68rem;text-transform:uppercase;letter-spacing:.8px;color:#0891b2">
                    <i class="bi bi-flask-fill me-1"></i>Bilans demandés (${r.bilans.length})
                </div>
                <div class="d-flex flex-wrap gap-1">
                    ${r.bilans.map(b => `<span class="badge rounded-pill" style="background:#e0f2fe;color:#0c4a6e;font-size:.65rem;font-weight:500">
                        ${b.urgence === 'urgent' ? '<i class="bi bi-lightning-fill text-danger me-1"></i>' : ''}${b.intitule || b.type}
                    </span>`).join('')}
                </div>
            </div>` : '';

        // Médicaments
        const medsHtml = r.medicaments && r.medicaments.length ? `
            <div class="mt-2">
                <div class="fw-semibold mb-1" style="font-size:.68rem;text-transform:uppercase;letter-spacing:.8px;color:#7c3aed">
                    <i class="bi bi-capsule-pill me-1"></i>Traitements (${r.medicaments.length})
                </div>
                <div class="d-flex flex-wrap gap-1">
                    ${r.medicaments.map(m => `<span class="badge rounded-pill" style="background:#ede9fe;color:#4c1d95;font-size:.65rem;font-weight:500">
                        ${m.nom_medicament}${m.posologie ? ' — '+m.posologie : ''}
                    </span>`).join('')}
                </div>
            </div>` : '';

        return `
        <div class="mb-3 rounded-3 border overflow-hidden" style="border-color:${ev.border}!important">
            <!-- En-tête réévaluation -->
            <div class="px-3 py-2 d-flex align-items-center gap-2 flex-wrap"
                 style="background:${ev.bg};border-bottom:1px solid ${ev.border}">
                <i class="bi ${ev.icon}" style="color:${ev.color};font-size:1rem"></i>
                <span class="fw-bold" style="color:${ev.color};font-size:.82rem">${ev.label}</span>
                <span class="ms-auto text-muted small">
                    <i class="bi bi-calendar3 me-1"></i>${dateStr}${heureStr ? ' · '+heureStr : ''}
                </span>
                ${medecinStr ? `<span class="badge rounded-pill bg-white text-dark border" style="font-size:.65rem"><i class="bi bi-person-badge-fill me-1 text-warning"></i>Dr ${medecinStr}</span>` : ''}
            </div>
            <!-- Corps -->
            <div class="px-3 py-2" style="background:#fff">
                ${r.plaintes_jour ? `<p class="mb-1 small"><span class="fw-semibold text-danger"><i class="bi bi-chat-left-text-fill me-1"></i>Plaintes :</span> ${r.plaintes_jour}</p>` : ''}
                ${r.diagnostic_jour ? `<p class="mb-1 small"><span class="fw-semibold text-dark"><i class="bi bi-clipboard2-check me-1"></i>Diagnostic du jour :</span> ${r.diagnostic_jour}${r.code_cim10 ? ' <span class="text-muted">('+r.code_cim10+')</span>' : ''}</p>` : ''}
                ${r.note_evolution ? `<p class="mb-1 small text-secondary"><i class="bi bi-pencil-fill me-1 text-muted"></i>${r.note_evolution}</p>` : ''}
                ${r.conduite_tenir ? `<p class="mb-1 small"><span class="fw-semibold text-info"><i class="bi bi-arrow-right-circle-fill me-1"></i>Conduite à tenir :</span> ${r.conduite_tenir}</p>` : ''}
                ${bilansHtml}
                ${medsHtml}
            </div>
        </div>`;
    }).join('');
}
</script>

<!-- ══ MODAL HOSPITALISATION DEPUIS CONSULTATION ══ -->
<div class="modal fade" id="modalHospConsulte" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius:20px">
            <div class="modal-header border-0 p-4" style="background:linear-gradient(135deg,#0f172a,#1e40af);border-radius:20px 20px 0 0">
                <div>
                    <h5 class="fw-bold text-white mb-0"><i class="bi bi-house-heart-fill me-2"></i>Demande d'hospitalisation</h5>
                    <small class="text-white opacity-75" id="hospPatientLabel"></small>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" id="hospConsultId">
                <input type="hidden" id="hospPatientId">

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small text-uppercase">Type d'hospitalisation</label>
                        <select id="hospDecision" class="form-select rounded-3 border-0 bg-light">
                            <option value="hospitalisation_urgente">🚨 Urgente</option>
                            <option value="hospitalisation_programmee" selected>📋 Programmée</option>
                            <option value="hospitalisation_recommandee">💡 Recommandée</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small text-uppercase">Service de destination</label>
                        <select id="hospServiceId" class="form-select rounded-3 border-0 bg-light" onchange="chargerLitsHosp(this.value)">
                            <option value="">— Sélectionner un service —</option>
                            <?php
                            try {
                                $srvStmt = (new Database())->getConnection()->query(
                                    "SELECT id, nom_service FROM services
                                     WHERE categorie = 'CLINIQUE'
                                        OR nom_service LIKE '%médecine%'
                                        OR nom_service LIKE '%chirurgie%'
                                        OR nom_service LIKE '%maternité%'
                                        OR nom_service LIKE '%pédiatrie%'
                                        OR nom_service LIKE '%urgence%'
                                        OR nom_service LIKE '%réanimation%'
                                     ORDER BY nom_service"
                                );
                                foreach ($srvStmt->fetchAll(PDO::FETCH_ASSOC) as $srv): ?>
                                <option value="<?= $srv['id'] ?>"><?= htmlspecialchars($srv['nom_service']) ?></option>
                                <?php endforeach;
                            } catch(Exception $e) {
                                // Si colonne categorie absente, on affiche tous les services
                                try {
                                    $srvStmt2 = (new Database())->getConnection()->query(
                                        "SELECT id, nom_service FROM services ORDER BY nom_service LIMIT 30"
                                    );
                                    foreach ($srvStmt2->fetchAll(PDO::FETCH_ASSOC) as $srv): ?>
                                    <option value="<?= $srv['id'] ?>"><?= htmlspecialchars($srv['nom_service']) ?></option>
                                    <?php endforeach;
                                } catch(Exception $e2) {}
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small text-uppercase">Lit (optionnel)</label>
                        <select id="hospLitId" class="form-select rounded-3 border-0 bg-light">
                            <option value="">— Choisir d'abord un service —</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small text-uppercase">Justification / Motif</label>
                        <input type="text" id="hospJustification" class="form-control rounded-3 border-0 bg-light"
                               placeholder="Ex: Pneumonie grave nécessitant oxygénothérapie…">
                    </div>
                </div>

                <div id="hospAlerteLits" class="alert alert-warning rounded-3 mt-3 d-none" style="font-size:.83rem">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i>
                    Aucun lit disponible dans ce service. Le patient sera mis en liste d'attente.
                </div>
            </div>
            <div class="modal-footer border-0 px-4 pb-4">
                <button type="button" class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-warning rounded-pill px-4 fw-bold" onclick="confirmerHospitalisation()">
                    <i class="bi bi-house-heart-fill me-1"></i>Confirmer l'hospitalisation
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Toast succès/erreur hospit -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index:9999">
    <div id="toastHosp" class="toast align-items-center border-0 rounded-3" role="alert" aria-live="assertive">
        <div class="d-flex">
            <div class="toast-body fw-semibold" id="toastHospMsg"></div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<!-- ══ MODAL ADMISSION RAPIDE ══ -->
<div class="modal fade" id="modalFastAdmission" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:20px;border:none;box-shadow:0 25px 50px rgba(0,0,0,.2)">
            <div class="modal-header border-0 p-4">
                <h5 class="fw-bold"><i class="bi bi-person-plus-fill text-danger me-2"></i>Admission Urgence Rapide</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= BASE_URL ?>urgences/save-single" method="POST">
                <div class="modal-body p-4 pt-0">
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-uppercase text-primary">Nom du Patient</label>
                        <input type="text" name="nom" class="form-control form-control-lg bg-light border-0 rounded-3" placeholder="Nom ou description" required>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-bold small text-uppercase text-primary">Sexe</label>
                            <select name="sexe" class="form-select bg-light border-0 rounded-3">
                                <option value="M">Masculin</option><option value="F">Féminin</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold small text-uppercase text-primary">Âge approx.</label>
                            <input type="number" name="age_approx" class="form-control bg-light border-0 rounded-3" placeholder="Ex: 35">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-uppercase text-primary">Motif / Plainte</label>
                        <textarea name="motif" class="form-control bg-light border-0 rounded-3" rows="2" placeholder="Symptômes observés..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="submit" class="btn btn-danger btn-lg w-100 fw-bold rounded-pill shadow">ADMETTRE IMMÉDIATEMENT</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════
     MODAL — NOUVEAU PATIENT (urgences)
════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalNouveauPatientUrg" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg" style="border-radius:16px;overflow:hidden;">
            <div class="modal-header border-0"
                 style="background:linear-gradient(135deg,#1e40af,#3b82f6);border-radius:16px 16px 0 0;padding:18px 24px 14px;">
                <div>
                    <h5 class="modal-title text-white fw-bold mb-0">
                        <i class="bi bi-person-plus-fill me-2"></i>Créer un nouveau patient
                    </h5>
                    <p class="mb-0 mt-1" style="font-size:.75rem;color:rgba(255,255,255,.7);">Dossier créé immédiatement — compléter les infos depuis le dossier</p>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formNouveauPatientUrg">
                <input type="hidden" name="force_creation" id="urgForceCreation" value="0">
                <div class="modal-body p-4">

                    <!-- Zone alerte doublons (injectée dynamiquement) -->
                    <div id="urgDoublonZone" style="display:none;margin-bottom:14px;"></div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Nom <span class="text-danger">*</span></label>
                            <input type="text" name="nom" id="urgNom" class="form-control" required placeholder="Nom de famille"
                                   oninput="urgDefsearchDoublon()">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Prénom <span class="text-danger">*</span></label>
                            <input type="text" name="prenom" id="urgPrenom" class="form-control" required placeholder="Prénom"
                                   oninput="urgDefsearchDoublon()">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Date de naissance</label>
                            <input type="date" name="date_naissance" id="urgDdn" class="form-control"
                                   onchange="urgDefsearchDoublon()">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Sexe</label>
                            <select name="sexe" class="form-select">
                                <option value="M">Masculin</option>
                                <option value="F">Féminin</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Téléphone</label>
                            <input type="tel" name="telephone" id="urgTel" class="form-control" placeholder="6XX XXX XXX"
                                   oninput="urgDefsearchDoublon()">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Groupe sanguin</label>
                            <select name="groupe_sanguin" class="form-select">
                                <option value="">Inconnu</option>
                                <?php foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $g): ?>
                                <option value="<?= $g ?>"><?= $g ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Prise en charge</label>
                            <select name="type_client" id="urgTypeClient" class="form-select"
                                    onchange="urgToggleCircuitFields()">
                                <option value="PAYANT_COMPTANT">💵 Payant comptant</option>
                                <option value="BON_PRISE_EN_CHARGE">📋 Bon prise en charge</option>
                                <option value="ASSURANCE">🛡️ Assurance</option>
                                <option value="FAMILLE_PHP">🏠 Famille PHP</option>
                                <option value="AGENTS_PHP">🪪 Agent PHP</option>
                            </select>
                        </div>
                    </div>

                    <!-- Champs dynamiques par circuit -->
                    <div id="urgCircuitFields" style="display:none;margin-top:12px;">
                        <div class="row g-2 p-3 rounded-3" id="urgCircuitFieldsInner"
                             style="border:1.5px solid #e2e8f0;background:#f8fafc;">
                            <!-- Injecté dynamiquement -->
                        </div>
                    </div>

                    <div class="row g-3" style="margin-top:0px;display:none;" id="_urg_dummy_row">
                    </div>
                    <div class="alert alert-info border-0 rounded-3 py-2 small mt-3 mb-0">
                        <i class="bi bi-info-circle-fill me-2"></i>
                        Le dossier sera créé et ouvert immédiatement.
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" id="btnCreerPatientUrg" class="btn btn-primary rounded-pill px-5 fw-semibold">
                        <i class="bi bi-person-plus-fill me-2"></i>Créer &amp; Ouvrir le dossier
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════
     MODAL — CHOISIR PATIENT POUR ORDONNANCE (urgences)
════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalOrdonnanceUrg" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:560px;">
        <div class="modal-content border-0 shadow-lg" style="border-radius:18px;overflow:hidden;">
            <div class="modal-header border-0"
                 style="background:linear-gradient(135deg,#4c1d95,#7c3aed);border-radius:18px 18px 0 0;padding:20px 24px 16px;">
                <div>
                    <h5 class="modal-title text-white fw-bold mb-0" style="font-size:1rem;">
                        <i class="bi bi-prescription2 me-2"></i>Nouvelle ordonnance
                    </h5>
                    <p class="mb-0 mt-1" style="font-size:.75rem;color:rgba(255,255,255,.7);">
                        Choisissez le patient pour qui rédiger l'ordonnance
                    </p>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="position-relative mb-1">
                    <i class="bi bi-search position-absolute"
                       style="left:13px;top:50%;transform:translateY(-50%);color:#7c3aed;font-size:.9rem;pointer-events:none;"></i>
                    <input type="text" id="ordoUrgInput" class="form-control ps-4 fw-semibold"
                           placeholder="Nom, prénom ou numéro de dossier…"
                           autocomplete="off"
                           style="border-radius:10px;border:2px solid #ddd6fe;font-size:.85rem;">
                </div>
                <p class="text-muted mb-2" style="font-size:.7rem;padding-left:4px;">
                    <i class="bi bi-info-circle me-1"></i>
                    Les patients d'un autre service sont affichés mais non sélectionnables.
                </p>
                <div id="ordoUrgResultats" style="max-height:380px;overflow-y:auto;border-radius:10px;">
                    <div class="text-center py-5 text-muted" style="font-size:.82rem;">
                        <i class="bi bi-person-lines-fill d-block mb-2" style="font-size:2.2rem;opacity:.2;"></i>
                        Commencez à saisir pour chercher
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// ── Horloge ──
(function() {
    const el = document.getElementById('digital-clock');
    function tick() { if(el) el.textContent = new Date().toLocaleTimeString('fr-FR'); }
    tick(); setInterval(tick, 1000);
})();

// ── Restauration de l'onglet actif après rechargement (filtre période) ──
(function() {
    const hash = location.hash; // ex: "#tabConsultes"
    if (hash) {
        const target = document.querySelector('[data-bs-target="' + hash + '"]');
        if (target) {
            // Désactiver l'onglet actif par défaut
            document.querySelectorAll('.main-tabs .nav-link.active').forEach(el => {
                el.classList.remove('active');
                const pane = document.querySelector(el.getAttribute('data-bs-target'));
                if (pane) { pane.classList.remove('show','active'); }
            });
            // Activer l'onglet cible
            target.classList.add('active');
            const pane = document.querySelector(hash);
            if (pane) { pane.classList.add('show','active'); }
        }
    }
})();

// ── Actualisation intelligente (3 min, pause si modal ouvert ou saisie active) ──
<?php if($periodeConsultes === 24): ?>
(function() {
    const INTERVAL = 3 * 60 * 1000;
    let timer = null;
    function scheduleRefresh() {
        clearTimeout(timer);
        timer = setTimeout(() => {
            const modalOpen  = document.querySelectorAll('.modal.show').length > 0;
            const inputFocus = ['INPUT','TEXTAREA','SELECT'].includes(document.activeElement?.tagName);
            if (modalOpen || inputFocus) { timer = setTimeout(scheduleRefresh, 30000); return; }
            location.reload();
        }, INTERVAL);
    }
    ['keydown','mousedown','touchstart'].forEach(ev =>
        document.addEventListener(ev, () => scheduleRefresh(), { passive: true })
    );
    scheduleRefresh();
})();
<?php endif; ?>

/* ─── HOSPITALISÉS ─── */
function filtrerHospitalises() {
    const q    = (document.getElementById('hospSearchInput')?.value || '').toLowerCase().trim();
    const date = (document.getElementById('hospDateInput')?.value   || '').trim();
    const rows = document.querySelectorAll('#tabHospitalises .hosp-row');
    let visible = 0;
    rows.forEach(row => {
        const matchSearch = !q    || (row.dataset.search || '').includes(q);
        const matchDate   = !date || row.dataset.date === date;
        const show = matchSearch && matchDate;
        row.style.display = show ? '' : 'none';
        if (show) visible++;
    });
    // Masquer les groupes service entièrement vides
    document.querySelectorAll('#tabHospitalises .hosp-group').forEach(grp => {
        const anyVis = [...grp.querySelectorAll('.hosp-row')].some(r => r.style.display !== 'none');
        grp.style.display = anyVis ? '' : 'none';
    });
    const cnt = document.getElementById('hospCount');
    if (cnt) cnt.textContent = visible + ' patient' + (visible > 1 ? 's' : '');
    const noRes = document.getElementById('hospNoResult');
    if (noRes) noRes.classList.toggle('d-none', visible > 0 || rows.length === 0);
}
function resetFiltresHospitalises() {
    const si = document.getElementById('hospSearchInput');
    const di = document.getElementById('hospDateInput');
    if (si) si.value = '';
    if (di) di.value = '';
    filtrerHospitalises();
}
document.addEventListener('DOMContentLoaded', filtrerHospitalises);

/* ─── PATIENTS CONSULTÉS ─── */
function filtrerConsultes() {
    const q    = (document.getElementById('searchConsultes')?.value  || '').toLowerCase().trim();
    const date = (document.getElementById('consulDateInput')?.value   || '').trim();
    const items = document.querySelectorAll('.consulte-item');
    let visible = 0;
    items.forEach(el => {
        const matchSearch = !q    || (el.dataset.search || '').includes(q);
        const matchDate   = !date || el.dataset.date === date;
        const show = matchSearch && matchDate;
        el.style.display = show ? '' : 'none';
        if (show) visible++;
    });
    const cnt = document.getElementById('consulCount');
    if (cnt) cnt.textContent = visible + ' patient' + (visible > 1 ? 's' : '');
    const noRes = document.getElementById('consulNoResult');
    if (noRes) noRes.classList.toggle('d-none', visible > 0 || items.length === 0);
}
function resetFiltresConsultes() {
    const si = document.getElementById('searchConsultes');
    const di = document.getElementById('consulDateInput');
    if (si) si.value = '';
    if (di) di.value = '';
    filtrerConsultes();
}

/* ─── BILANS / RÉSULTATS ─── */
function filtrerBilans() {
    const q    = (document.getElementById('bilansSearchInput')?.value || '').toLowerCase().trim();
    const date = (document.getElementById('bilansDateInput')?.value   || '').trim();
    const rows = document.querySelectorAll('#tabBilans .bilan-row');
    let visible = 0;
    rows.forEach(row => {
        const matchSearch = !q    || (row.dataset.search || '').includes(q);
        const matchDate   = !date || row.dataset.date === date;
        const show = matchSearch && matchDate;
        row.style.display = show ? '' : 'none';
        if (show) visible++;
    });
    // Masquer les sections entièrement vides (h6 + rows)
    document.querySelectorAll('#tabBilans .bilan-section').forEach(sec => {
        const anyVis = [...sec.querySelectorAll('.bilan-row')].some(r => r.style.display !== 'none');
        sec.style.display = anyVis ? '' : 'none';
    });
    const cnt = document.getElementById('bilansCount');
    if (cnt) cnt.textContent = visible + ' résultat' + (visible > 1 ? 's' : '');
    const noRes = document.getElementById('bilansNoResult');
    if (noRes) noRes.classList.toggle('d-none', visible > 0 || rows.length === 0);
}
function resetFiltresBilans() {
    const si = document.getElementById('bilansSearchInput');
    const di = document.getElementById('bilansDateInput');
    if (si) si.value = '';
    if (di) di.value = '';
    filtrerBilans();
}

// ── Modal hospitalisation ──
function ouvrirHospitalisation(patientId, consultId, nomPatient) {
    document.getElementById('hospPatientId').value  = patientId;
    document.getElementById('hospConsultId').value  = consultId;
    document.getElementById('hospPatientLabel').textContent = nomPatient;
    document.getElementById('hospServiceId').value  = '';
    document.getElementById('hospLitId').innerHTML  = '<option value="">— Choisir d\'abord un service —</option>';
    document.getElementById('hospJustification').value = '';
    document.getElementById('hospAlerteLits').classList.add('d-none');
    new bootstrap.Modal(document.getElementById('modalHospConsulte')).show();
}

function chargerLitsHosp(serviceId) {
    const sel  = document.getElementById('hospLitId');
    const alert = document.getElementById('hospAlerteLits');
    sel.innerHTML = '<option value="">Chargement…</option>';
    alert.classList.add('d-none');
    if (!serviceId) {
        sel.innerHTML = '<option value="">— Choisir d\'abord un service —</option>';
        return;
    }
    fetch('<?= BASE_URL ?>hospitalisation/lits-disponibles?service_id=' + serviceId)
        .then(r => r.json())
        .then(lits => {
            if (lits.length === 0) {
                sel.innerHTML = '<option value="">Aucun lit disponible</option>';
                alert.classList.remove('d-none');
            } else {
                sel.innerHTML = '<option value="">— Sélectionner un lit (optionnel) —</option>';
                lits.forEach(l => {
                    sel.innerHTML += `<option value="${l.id}">${l.nom_chambre ? l.nom_chambre+' — ' : ''}${l.nom_lit}</option>`;
                });
            }
        })
        .catch(() => { sel.innerHTML = '<option value="">Erreur de chargement</option>'; });
}

function confirmerHospitalisation() {
    const consultId     = document.getElementById('hospConsultId').value;
    const decision      = document.getElementById('hospDecision').value;
    const serviceId     = document.getElementById('hospServiceId').value;
    const litId         = document.getElementById('hospLitId').value;
    const justification = document.getElementById('hospJustification').value.trim();

    if (!serviceId) {
        document.getElementById('hospServiceId').classList.add('is-invalid');
        return;
    }
    document.getElementById('hospServiceId').classList.remove('is-invalid');

    const btn = document.querySelector('#modalHospConsulte .btn-warning');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Traitement…';

    fetch('<?= BASE_URL ?>consultation/decision-hospitalisation', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            consultation_id: consultId,
            decision:        decision,
            service_id:      serviceId || null,
            lit_id:          litId     || null,
            justification:   justification
        })
    })
    .then(r => {
        if (!r.ok) return r.text().then(t => { throw new Error('HTTP ' + r.status + ' — ' + t.substring(0, 200)); });
        return r.json();
    })
    .then(d => {
        bootstrap.Modal.getInstance(document.getElementById('modalHospConsulte')).hide();
        const toast    = document.getElementById('toastHosp');
        const toastMsg = document.getElementById('toastHospMsg');
        if (d.success || d.redirect) {
            toast.classList.remove('bg-danger');
            toast.classList.add('bg-success', 'text-white');
            toastMsg.textContent = '✅ Hospitalisation enregistrée avec succès.';
            setTimeout(() => location.reload(), 1800);
        } else {
            toast.classList.remove('bg-success');
            toast.classList.add('bg-danger', 'text-white');
            toastMsg.textContent = '❌ ' + (d.message || 'Erreur lors de l\'hospitalisation.');
            new bootstrap.Toast(toast, {delay: 6000}).show();
        }
        if (d.success || d.redirect) new bootstrap.Toast(toast, {delay: 3000}).show();
    })
    .catch(err => {
        bootstrap.Modal.getInstance(document.getElementById('modalHospConsulte'))?.hide();
        const toast    = document.getElementById('toastHosp');
        const toastMsg = document.getElementById('toastHospMsg');
        toast.classList.remove('bg-success');
        toast.classList.add('bg-danger', 'text-white');
        toastMsg.textContent = '❌ Erreur : ' + err.message;
        new bootstrap.Toast(toast, {delay: 8000}).show();
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-house-heart-fill me-1"></i>Confirmer l\'hospitalisation';
    });
}

// ══════════════════════════════════════════════════════════
//  FILTRAGE COCKPIT URGENCES
// ══════════════════════════════════════════════════════════
let _urgTriageActif = ''; // '' = tous

// Boutons triage : sélection exclusive
document.querySelectorAll('.urg-triage-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.urg-triage-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        _urgTriageActif = btn.dataset.triage;
        filtrerUrgences();
    });
});

let _urgDebTimer = null;
function filtrerUrgencesDebounce() {
    clearTimeout(_urgDebTimer);
    _urgDebTimer = setTimeout(filtrerUrgences, 220);
}
function filtrerUrgences() {
    const q     = (document.getElementById('urgSearchInput')?.value || '').toLowerCase().trim();
    const date  = (document.getElementById('urgDateInput')?.value   || '').trim();
    const triage = _urgTriageActif;

    const rows  = document.querySelectorAll('#tableUrgences tbody tr');
    let visible = 0;

    rows.forEach(row => {
        const matchSearch = !q     || row.dataset.search.includes(q);
        const matchDate   = !date  || row.dataset.date === date;
        const matchTriage = !triage|| row.dataset.triage === triage;

        const show = matchSearch && matchDate && matchTriage;
        row.style.display = show ? '' : 'none';
        if (show) visible++;
    });

    // Compteur
    const cnt = document.getElementById('urgCount');
    if (cnt) cnt.textContent = visible + ' patient' + (visible > 1 ? 's' : '');

    // Message "aucun résultat"
    const noRes = document.getElementById('urgNoResult');
    if (noRes) noRes.classList.toggle('d-none', visible > 0 || rows.length === 0);
}

function resetFiltresUrgences() {
    document.getElementById('urgSearchInput').value = '';
    document.getElementById('urgDateInput').value   = '<?= date('Y-m-d') ?>';
    _urgTriageActif = '';
    document.querySelectorAll('.urg-triage-btn').forEach(b => b.classList.remove('active'));
    document.querySelector('.urg-triage-btn[data-triage=""]')?.classList.add('active');
    filtrerUrgences();
}

// Appliquer le filtre date du jour au chargement
document.addEventListener('DOMContentLoaded', filtrerUrgences);

/* ─── SALLE D'ATTENTE : filtre search + date ─── */
function filtrerAttente() {
    const q    = (document.getElementById('attenteSearchInput')?.value || '').toLowerCase().trim();
    const date = (document.getElementById('attenteDateInput')?.value   || '').trim();
    const cards = document.querySelectorAll('.attente-card-wrap');
    let visible = 0;

    cards.forEach(card => {
        const matchSearch = !q    || (card.dataset.search || '').includes(q);
        const matchDate   = !date || card.dataset.date === date;
        const show = matchSearch && matchDate;
        card.style.display = show ? '' : 'none';
        if (show) visible++;
    });

    // Compteur
    const cnt = document.getElementById('attenteCount');
    if (cnt) cnt.textContent = visible + ' patient' + (visible > 1 ? 's' : '');

    // Message "aucun résultat"
    const noRes = document.getElementById('attenteNoResult');
    if (noRes) noRes.classList.toggle('d-none', visible > 0 || cards.length === 0);
}

function resetFiltresAttente() {
    const si = document.getElementById('attenteSearchInput');
    const di = document.getElementById('attenteDateInput');
    if (si) si.value = '';
    if (di) di.value = '<?= date('Y-m-d') ?>';
    filtrerAttente();
}

// Appliquer le filtre date du jour au chargement (Salle d'Attente)
document.addEventListener('DOMContentLoaded', filtrerAttente);

// ══════════════════════════════════════════════════════════
//  RECHERCHE GLOBALE DYNAMIQUE — tous onglets
// ══════════════════════════════════════════════════════════
(function() {

    // ── Construire l'index de tous les patients depuis le DOM ──
    function buildIndex() {
        const idx = [];

        // Cockpit Urgences
        document.querySelectorAll('#tableUrgences tbody tr[data-search]').forEach(tr => {
            idx.push({
                search  : tr.dataset.search || '',
                nom     : tr.querySelector('td:nth-child(2) .fw-bold')?.textContent?.trim() || '',
                dossier : tr.querySelector('td:nth-child(2) small')?.textContent?.trim() || '',
                triage  : tr.querySelector('.badge-triage, [class*="triage"]')?.textContent?.trim() || '',
                onglet  : 'cockpit',
                label   : 'Cockpit Urgences',
                color   : '#ef4444',
                rowId   : tr.id || '',
                action  : () => {
                    document.querySelector('[data-tab="cockpit"]')?.click();
                    setTimeout(() => highlightRow(tr), 200);
                }
            });
        });

        // Salle d'Attente
        document.querySelectorAll('.patient-salle-attente[data-search], .salle-row[data-search], #tabSalleAttente tr[data-search]').forEach(el => {
            const nom = el.querySelector('.fw-bold, [class*="nom"]')?.textContent?.trim() || el.dataset.search?.split(' ').slice(0,2).join(' ') || '';
            idx.push({
                search  : el.dataset.search || '',
                nom     : nom,
                dossier : '',
                triage  : el.querySelector('[class*="triage"], [class*="priority"]')?.textContent?.trim() || '',
                onglet  : 'attente',
                label   : 'Salle d\'Attente',
                color   : '#f59e0b',
                rowId   : el.id || '',
                action  : () => {
                    document.querySelector('[data-tab="attente"]')?.click();
                    setTimeout(() => highlightRow(el), 200);
                }
            });
        });

        // Hospitalisés
        document.querySelectorAll('#tabHospitalises .hosp-row[data-search]').forEach(row => {
            const nom = row.querySelector('.fw-bold')?.textContent?.trim() || '';
            idx.push({
                search  : row.dataset.search || '',
                nom     : nom,
                dossier : row.querySelector('[class*="dossier"], .text-muted')?.textContent?.trim() || '',
                triage  : '',
                onglet  : 'hosp',
                label   : 'Hospitalisés',
                color   : '#8b5cf6',
                rowId   : row.id || '',
                action  : () => {
                    document.querySelector('[data-tab="hosp"]')?.click();
                    setTimeout(() => highlightRow(row), 200);
                }
            });
        });

        // Patients consultés
        document.querySelectorAll('.consulte-item[data-search]').forEach(el => {
            const nom = el.querySelector('.fw-bold')?.textContent?.trim() || '';
            idx.push({
                search  : el.dataset.search || '',
                nom     : nom,
                dossier : '',
                triage  : '',
                onglet  : 'consultes',
                label   : 'Consultés',
                color   : '#10b981',
                rowId   : el.id || '',
                action  : () => {
                    document.querySelector('[data-tab="consultes"]')?.click();
                    setTimeout(() => highlightRow(el), 200);
                }
            });
        });

        return idx;
    }

    function highlightRow(el) {
        if (!el) return;
        el.scrollIntoView({ behavior:'smooth', block:'center' });
        el.style.transition = 'background .3s';
        el.style.background = '#fef9c3';
        setTimeout(() => {
            el.style.background = '';
        }, 2500);
    }

    // ── Rendu d'un résultat ──
    function renderResult(item, q, idx) {
        const nom  = item.nom || item.search.split(' ').slice(0,3).join(' ');
        const high = (txt) => txt.replace(new RegExp('(' + q.replace(/[.*+?^${}()|[\]\\]/g,'\\$&') + ')', 'gi'),
                                          '<mark style="background:#fef9c3;padding:0 2px;border-radius:3px;">$1</mark>');
        const d = document.createElement('div');
        d.style.cssText = 'display:flex;align-items:center;gap:10px;padding:10px 14px;cursor:pointer;border-bottom:1px solid #f1f5f9;transition:background .1s;';
        d.dataset.idx = idx;
        d.innerHTML = `
            <span style="background:${item.color}20;color:${item.color};border:1px solid ${item.color}40;
                         border-radius:6px;padding:2px 8px;font-size:.65rem;font-weight:800;white-space:nowrap;
                         min-width:80px;text-align:center;">${item.label}</span>
            <div style="flex:1;min-width:0;">
                <div style="font-size:.82rem;font-weight:700;color:#1e293b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                    ${high(nom)}
                </div>
                ${item.dossier ? `<div style="font-size:.68rem;color:#94a3b8;">${high(item.dossier)}</div>` : ''}
            </div>
            ${item.triage ? `<span style="background:${item.color};color:#fff;border-radius:4px;padding:1px 7px;font-size:.65rem;font-weight:800;">${item.triage}</span>` : ''}
            <i class="bi bi-arrow-right-circle" style="color:#cbd5e1;font-size:.85rem;flex-shrink:0;"></i>`;
        d.addEventListener('mouseover', () => d.style.background = '#f8fafc');
        d.addEventListener('mouseout',  () => d.style.background = '');
        d.addEventListener('click', () => {
            item.action();
            fermerRechercheGlobale();
            document.getElementById('globalSearchInput').value = nom;
        });
        return d;
    }

    let _gsTimer = null;
    let _gsIndex = [];
    let _gsActiveIdx = -1;
    let _gsResults = [];

    window.ouvrirRechercheGlobale = function() {
        _gsIndex = buildIndex();
        const bar = document.getElementById('globalSearchBar');
        if (bar) {
            bar.style.background = 'rgba(255,255,255,.18)';
            bar.style.borderColor = 'rgba(255,255,255,.55)';
            bar.style.boxShadow   = '0 0 0 3px rgba(255,255,255,.12)';
        }
    };

    window.fermerRechercheGlobale = function() {
        document.getElementById('globalSearchInput').value = '';
        document.getElementById('globalSearchDropdown').style.display = 'none';
        document.getElementById('globalSearchCount').style.display = 'none';
        document.getElementById('globalSearchClear').style.display = 'none';
        const bar = document.getElementById('globalSearchBar');
        if (bar) {
            bar.style.background = '';
            bar.style.borderColor = '';
            bar.style.boxShadow   = '';
        }
        _gsActiveIdx = -1; _gsResults = [];
    };

    window.rechercheGlobale = function(q) {
        clearTimeout(_gsTimer);
        const drop  = document.getElementById('globalSearchDropdown');
        const count = document.getElementById('globalSearchCount');
        const clear = document.getElementById('globalSearchClear');
        clear.style.display = q ? 'flex' : 'none';

        if (!q || q.trim().length < 2) {
            drop.style.display = 'none';
            count.style.display = 'none';
            _gsResults = []; _gsActiveIdx = -1;
            return;
        }

        _gsTimer = setTimeout(() => {
            const ql = q.trim().toLowerCase();
            if (_gsIndex.length === 0) _gsIndex = buildIndex();

            _gsResults = _gsIndex.filter(item =>
                (item.search || '').includes(ql) ||
                (item.nom    || '').toLowerCase().includes(ql) ||
                (item.dossier|| '').toLowerCase().includes(ql)
            ).slice(0, 20);

            drop.innerHTML = '';
            _gsActiveIdx   = -1;

            if (_gsResults.length === 0) {
                drop.innerHTML = `
                    <div style="padding:20px;text-align:center;color:#94a3b8;">
                        <i class="bi bi-person-x" style="font-size:1.5rem;display:block;margin-bottom:6px;"></i>
                        Aucun patient trouvé pour "<strong>${q}</strong>"
                    </div>`;
            } else {
                // Grouper par onglet
                const byOnglet = {};
                _gsResults.forEach((item, i) => {
                    if (!byOnglet[item.label]) byOnglet[item.label] = [];
                    byOnglet[item.label].push({item, i});
                });

                Object.entries(byOnglet).forEach(([label, entries]) => {
                    const header = document.createElement('div');
                    header.style.cssText = 'padding:6px 14px 3px;font-size:.65rem;font-weight:800;text-transform:uppercase;letter-spacing:.5px;color:#94a3b8;background:#f8fafc;';
                    header.textContent = label + ' (' + entries.length + ')';
                    drop.appendChild(header);
                    entries.forEach(({item, i}) => drop.appendChild(renderResult(item, q, i)));
                });
            }

            count.textContent  = _gsResults.length + ' résultat' + (_gsResults.length !== 1 ? 's' : '');
            count.style.display = 'inline';
            drop.style.display  = 'block';
        }, 150);
    };

    window.navRechercheGlobale = function(e) {
        const drop = document.getElementById('globalSearchDropdown');
        const items = drop.querySelectorAll('[data-idx]');
        if (e.key === 'Escape') { fermerRechercheGlobale(); return; }
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            _gsActiveIdx = Math.min(_gsActiveIdx + 1, items.length - 1);
            items.forEach((el, i) => el.style.background = i === _gsActiveIdx ? '#f0f9ff' : '');
            items[_gsActiveIdx]?.scrollIntoView({ block:'nearest' });
        }
        if (e.key === 'ArrowUp') {
            e.preventDefault();
            _gsActiveIdx = Math.max(_gsActiveIdx - 1, 0);
            items.forEach((el, i) => el.style.background = i === _gsActiveIdx ? '#f0f9ff' : '');
            items[_gsActiveIdx]?.scrollIntoView({ block:'nearest' });
        }
        if (e.key === 'Enter' && _gsActiveIdx >= 0 && _gsResults[_gsActiveIdx]) {
            _gsResults[_gsActiveIdx].action();
            fermerRechercheGlobale();
        }
    };

    // Fermer en cliquant à l'extérieur
    document.addEventListener('click', e => {
        const wrap = document.getElementById('globalSearchWrap');
        if (wrap && !wrap.contains(e.target)) {
            document.getElementById('globalSearchDropdown').style.display = 'none';
        }
    });

})(); // fin Recherche Globale

// ══════════════════════════════════════════════════════════
//  NOUVEAU PATIENT & ORDONNANCE (cockpit urgences)
// ══════════════════════════════════════════════════════════
(function() {
    const SESSION_SERVICE_ID_URG = <?= (int)($_SESSION['service_id'] ?? 0) ?>;
    let _modalNPU, _modalOrdoU;

    document.addEventListener('DOMContentLoaded', function() {
        _modalNPU   = new bootstrap.Modal(document.getElementById('modalNouveauPatientUrg'));
        _modalOrdoU = new bootstrap.Modal(document.getElementById('modalOrdonnanceUrg'));

        // ── Création rapide patient ─────────────────────────────
        document.getElementById('formNouveauPatientUrg').addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = document.getElementById('btnCreerPatientUrg');
            soumettreCréationPatientUrg(this, btn, _modalNPU);
        });

        document.getElementById('modalNouveauPatientUrg').addEventListener('hidden.bs.modal', function() {
            document.getElementById('formNouveauPatientUrg').reset();
            const old = document.querySelector('#formNouveauPatientUrg .doublon-alert');
            if (old) old.remove();
            const btn = document.getElementById('btnCreerPatientUrg');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-person-plus-fill me-2"></i>Créer & Ouvrir le dossier';
        });

        // ── Ordonnance : recherche patient ─────────────────────
        document.getElementById('modalOrdonnanceUrg').addEventListener('shown.bs.modal', function() {
            const inp = document.getElementById('ordoUrgInput');
            inp.value = '';
            document.getElementById('ordoUrgResultats').innerHTML =
                '<div class="text-center py-5 text-muted" style="font-size:.82rem;"><i class="bi bi-person-lines-fill d-block mb-2" style="font-size:2.2rem;opacity:.2;"></i>Commencez à saisir pour chercher</div>';
            inp.focus();
        });

        let _ordoUrgTimer = null;
        document.getElementById('ordoUrgInput').addEventListener('input', function() {
            clearTimeout(_ordoUrgTimer);
            const q = this.value.trim();
            const zone = document.getElementById('ordoUrgResultats');
            if (q.length < 2) {
                zone.innerHTML = '<div class="text-center py-5 text-muted" style="font-size:.82rem;"><i class="bi bi-person-lines-fill d-block mb-2" style="font-size:2.2rem;opacity:.2;"></i>Commencez à saisir pour chercher</div>';
                return;
            }
            zone.innerHTML = '<div class="text-center py-4 text-muted small"><span class="spinner-border spinner-border-sm me-2" style="color:#7c3aed;"></span>Recherche…</div>';
            _ordoUrgTimer = setTimeout(() => {
                fetch('<?= BASE_URL ?>patients/recherche?q=' + encodeURIComponent(q), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => r.json())
                .then(data => {
                    if (!data.success || !data.patients.length) {
                        zone.innerHTML = '<div class="text-center py-4 text-muted small"><i class="bi bi-person-x d-block mb-1" style="font-size:2rem;opacity:.3;"></i>Aucun patient trouvé</div>';
                        return;
                    }
                    let html = '';
                    data.patients.forEach(p => {
                        const ini = (p.nom.charAt(0) + (p.prenom.charAt(0) || '')).toUpperCase();
                        const meme = !SESSION_SERVICE_ID_URG || parseInt(p.service_id) === SESSION_SERVICE_ID_URG;
                        const serviceLabel = p.nom_service ? escHU(p.nom_service) : 'Service inconnu';
                        if (meme) {
                            html += `<a href="<?= BASE_URL ?>prescription/create?patient_id=${p.id}"
                                style="display:flex;align-items:center;gap:12px;padding:11px 14px;border-radius:10px;
                                       cursor:pointer;text-decoration:none;color:#1e293b;border:1.5px solid transparent;
                                       transition:all .15s;margin-bottom:4px;background:#fff;"
                                onmouseover="this.style.background='#faf5ff';this.style.borderColor='#c4b5fd';"
                                onmouseout="this.style.background='#fff';this.style.borderColor='transparent';">
                                <div style="width:40px;height:40px;border-radius:10px;flex-shrink:0;
                                            background:linear-gradient(135deg,#4c1d95,#7c3aed);
                                            display:flex;align-items:center;justify-content:center;
                                            color:#fff;font-size:.78rem;font-weight:800;">${ini}</div>
                                <div style="flex:1;min-width:0;">
                                    <div style="font-weight:700;font-size:.88rem;">${escHU(p.nom)} ${escHU(p.prenom)}</div>
                                    <div style="font-size:.72rem;color:#94a3b8;">${escHU(p.dossier_numero)} · ${p.date_naissance || '?'}</div>
                                </div>
                                <span style="font-size:.68rem;font-weight:700;padding:2px 9px;border-radius:20px;
                                             background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0;flex-shrink:0;">
                                    <i class="bi bi-check-circle-fill me-1"></i>Mon service
                                </span>
                            </a>`;
                        } else {
                            html += `<div style="display:flex;align-items:center;gap:12px;padding:11px 14px;border-radius:10px;
                                          opacity:.42;cursor:not-allowed;margin-bottom:4px;background:#f8fafc;
                                          border:1.5px solid #e2e8f0;" title="Patient rattaché à : ${serviceLabel}">
                                <div style="width:40px;height:40px;border-radius:10px;flex-shrink:0;background:#e2e8f0;
                                            display:flex;align-items:center;justify-content:center;
                                            color:#94a3b8;font-size:.78rem;font-weight:800;">${ini}</div>
                                <div style="flex:1;min-width:0;">
                                    <div style="font-weight:700;font-size:.88rem;color:#64748b;">${escHU(p.nom)} ${escHU(p.prenom)}</div>
                                    <div style="font-size:.72rem;color:#94a3b8;">${escHU(p.dossier_numero)} · ${p.date_naissance || '?'}</div>
                                </div>
                                <span style="font-size:.66rem;font-weight:700;padding:2px 9px;border-radius:20px;
                                             background:#f1f5f9;color:#94a3b8;border:1px solid #e2e8f0;flex-shrink:0;">
                                    <i class="bi bi-lock-fill me-1"></i>${serviceLabel}
                                </span>
                            </div>`;
                        }
                    });
                    zone.innerHTML = html;
                })
                .catch(() => { zone.innerHTML = '<p class="text-danger text-center small p-3">Erreur réseau.</p>'; });
            }, 300);
        });
    });

    window.ouvrirNouveauPatientUrg = function() {
        // Réinitialiser la zone doublons à chaque ouverture
        document.getElementById('urgDoublonZone').style.display = 'none';
        document.getElementById('urgDoublonZone').innerHTML = '';
        document.getElementById('urgForceCreation').value = '0';
        _modalNPU.show();
    };
    window.ouvrirOrdonnanceUrg = function() { _modalOrdoU.show(); };

    // ── Recherche doublons temps réel (debounce 500ms) ────────────
    let _urgDoublonTimer = null;
    window.urgDefsearchDoublon = function() {
        clearTimeout(_urgDoublonTimer);
        _urgDoublonTimer = setTimeout(urgCheckDoublon, 500);
    };

    function urgCheckDoublon() {
        const nom    = (document.getElementById('urgNom')?.value    || '').trim();
        const prenom = (document.getElementById('urgPrenom')?.value || '').trim();
        const ddn    = document.getElementById('urgDdn')?.value     || '';
        const tel    = (document.getElementById('urgTel')?.value    || '').trim();
        const zone   = document.getElementById('urgDoublonZone');

        if (nom.length < 2) { zone.style.display = 'none'; zone.innerHTML = ''; return; }

        const params = new URLSearchParams({ nom, prenom });
        if (ddn)  params.append('date_naissance', ddn);
        if (tel)  params.append('telephone', tel);

        fetch('<?= BASE_URL ?>patients/verifier-doublon?' + params.toString())
            .then(r => r.json())
            .then(data => {
                if (!data.has_warning || data.doublons.length === 0) {
                    zone.style.display = 'none'; zone.innerHTML = ''; return;
                }
                const isCritical = data.has_critical;
                const bg    = isCritical ? '#fef2f2' : '#fffbeb';
                const border= isCritical ? '#fecaca' : '#fde68a';
                const color = isCritical ? '#dc2626'  : '#d97706';
                const icon  = isCritical ? '🔴' : '⚠️';

                let html = `<div style="background:${bg};border:1.5px solid ${border};
                                        border-radius:12px;padding:12px 14px;">
                    <div style="font-size:.82rem;font-weight:700;color:${color};margin-bottom:8px;">
                        ${icon} ${isCritical
                            ? 'Patient existant détecté — vérifiez avant de créer'
                            : 'Patient(s) similaire(s) — confirmation requise'}
                    </div>
                    <div style="display:flex;flex-direction:column;gap:6px;">`;

                data.doublons.slice(0,4).forEach(d => {
                    const age = d.age || '—';
                    html += `<div style="background:#fff;border:1px solid #e2e8f0;border-radius:8px;
                                         padding:8px 12px;display:flex;align-items:center;gap:10px;cursor:pointer;"
                                  onclick="urgUtiliserPatient(${d.id}, '${escH(d.nom)} ${escH(d.prenom)}')">
                        <span style="background:${d.niveau_color}18;color:${d.niveau_color};
                                     border:1px solid ${d.niveau_color}40;border-radius:6px;
                                     padding:2px 8px;font-size:.65rem;font-weight:800;white-space:nowrap;">
                            ${d.niveau_label}
                        </span>
                        <div style="flex:1;">
                            <div style="font-size:.82rem;font-weight:700;color:#1e293b;">
                                ${escH(d.nom)} ${escH(d.prenom)}
                            </div>
                            <div style="font-size:.7rem;color:#64748b;">
                                ${escH(d.dossier_numero)} · ${age}
                                ${d.telephone ? ' · ' + escH(d.telephone) : ''}
                                · ${d.nb_consultations} consultation(s)
                            </div>
                        </div>
                        <span style="font-size:.7rem;background:#dbeafe;color:#1d4ed8;
                                     border-radius:6px;padding:2px 8px;font-weight:700;white-space:nowrap;">
                            ← Utiliser
                        </span>
                    </div>`;
                });

                html += `</div>
                    <div style="margin-top:10px;display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                        <button type="button" onclick="urgForceCreate()"
                                style="background:${isCritical ? '#fee2e2' : '#fef3c7'};
                                       color:${color};border:1px solid ${border};
                                       border-radius:8px;padding:5px 14px;font-size:.75rem;
                                       font-weight:700;cursor:pointer;">
                            ${isCritical ? '⚠️ Créer quand même' : '✓ Confirmer la création'}
                        </button>
                        <span style="font-size:.7rem;color:#94a3b8;">
                            ou cliquez sur un patient pour l'utiliser directement
                        </span>
                    </div>
                </div>`;

                zone.innerHTML     = html;
                zone.style.display = 'block';
                // Bloquer le bouton si critique
                document.getElementById('btnCreerPatientUrg').disabled = isCritical;
            })
            .catch(() => { zone.style.display = 'none'; });
    }

    // ── Champs dynamiques par circuit (modal nouveau patient urgences) ────
    window.urgToggleCircuitFields = function() {
        const tc     = document.getElementById('urgTypeClient')?.value || '';
        const wrap   = document.getElementById('urgCircuitFields');
        const inner  = document.getElementById('urgCircuitFieldsInner');
        if (!wrap || !inner) return;

        inner.innerHTML = '';

        if (tc === 'ASSURANCE') {
            wrap.style.display = 'block';
            inner.style.cssText = 'border-color:#bae6fd;background:#eff6ff;';
            inner.innerHTML = `
                <div class="col-12" style="font-size:.72rem;font-weight:700;color:#0891b2;margin-bottom:4px;">
                    <i class="bi bi-shield-check me-1"></i>Informations assurance (obligatoires)
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Nom de l'assurance <span class="text-danger">*</span></label>
                    <input type="text" name="nom_assurance" class="form-control form-control-sm" required placeholder="ex: CNPS, Allianz...">
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">N° assuré <span class="text-danger">*</span></label>
                    <input type="text" name="numero_assure" class="form-control form-control-sm" required placeholder="ex: ASS-2024-00123">
                </div>`;
        } else if (tc === 'BON_PRISE_EN_CHARGE') {
            wrap.style.display = 'block';
            inner.style.cssText = 'border-color:#93c5fd;background:#eff6ff;';
            inner.innerHTML = `
                <div class="col-12" style="font-size:.72rem;font-weight:700;color:#1d4ed8;margin-bottom:4px;">
                    <i class="bi bi-file-medical me-1"></i>Bon de prise en charge (obligatoire)
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">N° du bon <span class="text-danger">*</span></label>
                    <input type="text" name="numero_bon" class="form-control form-control-sm" required placeholder="ex: BON-2026-00123">
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Organisme émetteur <span class="text-danger">*</span></label>
                    <input type="text" name="organisme_payeur" class="form-control form-control-sm" required placeholder="ex: Ministère de la Santé">
                </div>`;
        } else {
            wrap.style.display = 'none';
        }
    };

    // Réinitialiser à l'ouverture
    const _origOuvrirNPU = window.ouvrirNouveauPatientUrg;
    window.ouvrirNouveauPatientUrg = function() {
        const tc = document.getElementById('urgTypeClient');
        if (tc) tc.value = 'PAYANT_COMPTANT';
        urgToggleCircuitFields();
        _origOuvrirNPU();
    };

    window.urgForceCreate = function() {
        document.getElementById('urgForceCreation').value = '1';
        document.getElementById('btnCreerPatientUrg').disabled = false;
        document.getElementById('urgDoublonZone').style.display = 'none';
    };

    window.urgUtiliserPatient = function(patientId, nomComplet) {
        // Ferme la modal de création et réadmet le patient existant
        _modalNPU.hide();
        if (typeof ouvrirModalPatientConnu === 'function') {
            // Pré-remplir la recherche "Patient connu" avec l'ID
            ouvrirModalPatientConnu();
            setTimeout(() => {
                const inp = document.querySelector('#modalPatientConnu input[type=text], #modalPatientConnu input[name*=search]');
                if (inp) { inp.value = nomComplet; inp.dispatchEvent(new Event('input')); }
            }, 400);
        } else {
            // Fallback : ouvrir directement le dossier
            window.open('<?= BASE_URL ?>patients/dossier/' + patientId, '_blank');
        }
    };

    function escH(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

    // ── Création avec gestion doublons ───────────────────────────
    function soumettreCréationPatientUrg(form, btn, modal) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Vérification…';
        const fd = new FormData(form);
        fd.delete('force');
        fetch('<?= BASE_URL ?>patients/creer-rapide', {
            method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) { modal.hide(); window.location.href = data.redirect; return; }
            if (data.duplicate_warning) {
                afficherDoublonsUrg(data.doublons, form, btn, modal);
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-person-plus-fill me-2"></i>Créer & Ouvrir le dossier';
                return;
            }
            alert('❌ ' + (data.message || 'Erreur.'));
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-person-plus-fill me-2"></i>Créer & Ouvrir le dossier';
        })
        .catch(() => {
            alert('Erreur réseau.');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-person-plus-fill me-2"></i>Créer & Ouvrir le dossier';
        });
    }

    function afficherDoublonsUrg(doublons, form, btn, modal) {
        const old = form.querySelector('.doublon-alert');
        if (old) old.remove();
        let html = `<div class="doublon-alert" style="
            background:#fff7ed;border:1.5px solid #f97316;border-radius:12px;
            padding:14px 16px;margin-bottom:12px;font-size:.82rem;">
            <div style="font-weight:700;color:#c2410c;margin-bottom:8px;">
                <i class="bi bi-exclamation-triangle-fill me-1"></i>
                Doublon détecté — ${doublons.length} patient(s) avec ce nom existent déjà
            </div>
            <div style="display:flex;flex-direction:column;gap:6px;margin-bottom:10px;">`;
        doublons.forEach(p => {
            const ddn = p.date_naissance && p.date_naissance !== '1900-01-01'
                ? new Date(p.date_naissance).toLocaleDateString('fr-FR') : '—';
            html += `<div style="display:flex;align-items:center;justify-content:space-between;
                              background:#fff;border-radius:8px;padding:8px 12px;border:1px solid #fed7aa;gap:8px;">
                <span style="font-weight:700;">${escHU(p.nom)} ${escHU(p.prenom)}</span>
                <span style="color:#64748b;font-size:.75rem;">${escHU(p.dossier_numero)} · ${ddn}</span>
                <a href="<?= BASE_URL ?>patients/dossier/${p.id}" target="_blank"
                   style="background:#f97316;color:#fff;padding:3px 10px;border-radius:20px;
                          font-size:.72rem;font-weight:700;text-decoration:none;white-space:nowrap;">Ouvrir</a>
            </div>`;
        });
        html += `</div>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <button type="button" style="background:#f1f5f9;color:#374151;border:1px solid #e2e8f0;
                        padding:5px 14px;border-radius:20px;font-size:.78rem;font-weight:600;cursor:pointer;"
                        onclick="this.closest('.doublon-alert').remove();">
                    <i class="bi bi-arrow-left me-1"></i>Modifier
                </button>
                <button type="button" style="background:#f97316;color:#fff;border:none;
                        padding:5px 14px;border-radius:20px;font-size:.78rem;font-weight:700;cursor:pointer;"
                        onclick="forcerCreationUrg(this.closest('form'), '${btn.id}', modal)">
                    <i class="bi bi-person-plus-fill me-1"></i>Créer quand même
                </button>
            </div>
        </div>`;
        form.insertAdjacentHTML('afterbegin', html);
    }

    function forcerCreationUrg(form, btnId, modal) {
        const fd = new FormData(form);
        fd.append('force', '1');
        const btn = document.getElementById(btnId);
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Création…';
        fetch('<?= BASE_URL ?>patients/creer-rapide', {
            method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) { modal.hide(); window.location.href = data.redirect; }
            else { alert('❌ ' + (data.message || 'Erreur.')); btn.disabled = false; }
        })
        .catch(() => { alert('Erreur réseau.'); btn.disabled = false; });
    }

    function escHU(s) {
        if (!s) return '';
        return String(s).replace(/[&<>"']/g, c =>
            ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));
    }
})();
</script>

<!-- ══════════════════════════════════════════════════════════════════
     MODAL — RÉADMISSION PATIENT CONNU AUX URGENCES
══════════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalPatientConnu" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg" style="border-radius:18px;overflow:hidden">

            <!-- En-tête -->
            <div class="modal-header border-0 p-4"
                 style="background:linear-gradient(135deg,#0369a1,#0284c7)">
                <div>
                    <h5 class="modal-title fw-bold text-white mb-0">
                        <i class="bi bi-person-check-fill me-2"></i>Admettre un patient connu
                    </h5>
                    <div class="text-white opacity-75 small mt-1">
                        Rechercher un patient déjà enregistré — accès à tout son historique
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body p-0">

                <!-- ══ PHASE 1 : RECHERCHE ══ -->
                <div id="pcPhase1" class="p-4">

                    <!-- Barre de recherche -->
                    <div class="input-group mb-3 shadow-sm">
                        <span class="input-group-text bg-sky-50 border-0"
                              style="background:#f0f9ff">
                            <i class="bi bi-search text-primary fs-5"></i>
                        </span>
                        <input type="text" id="pcSearchInput"
                               class="form-control border-0 py-3"
                               style="background:#f0f9ff;font-size:.95rem"
                               placeholder="Nom, prénom, N° dossier ou téléphone…"
                               autocomplete="off"
                               oninput="pcRechercherDebounce()">
                    </div>

                    <!-- Résultats -->
                    <div id="pcSearchResults">
                        <div class="text-center text-muted py-5" style="font-size:.9rem">
                            <i class="bi bi-person-lines-fill display-4 opacity-25 d-block mb-2"></i>
                            Saisir au moins 2 caractères pour rechercher
                        </div>
                    </div>
                </div>

                <!-- ══ PHASE 2 : DOSSIER + ADMISSION ══ -->
                <div id="pcPhase2" class="d-none">

                    <!-- Retour -->
                    <div class="px-4 pt-3 pb-0">
                        <button class="btn btn-sm btn-outline-secondary rounded-pill px-3"
                                onclick="pcRetourRecherche()">
                            <i class="bi bi-arrow-left me-1"></i>Retour à la recherche
                        </button>
                    </div>

                    <!-- Carte patient -->
                    <div id="pcPatientCard" class="mx-4 mt-3 p-3 rounded-3"
                         style="background:#f0f9ff;border:1.5px solid #bae6fd">
                        <!-- rempli par JS -->
                    </div>

                    <!-- Antécédents importants -->
                    <div id="pcAntecedents" class="mx-4 mt-2 d-none">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="badge bg-danger">⚠ Antécédents</span>
                        </div>
                        <div id="pcAntecedentsBody" class="d-flex flex-wrap gap-1"></div>
                    </div>

                    <!-- Historique en accordéon -->
                    <div class="accordion accordion-flush mx-4 mt-3" id="pcHistoAccordion">

                        <!-- Consultations -->
                        <div class="accordion-item border rounded-3 mb-2">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed rounded-3 fw-bold"
                                        style="font-size:.85rem"
                                        type="button" data-bs-toggle="collapse"
                                        data-bs-target="#pcCollapseConsults">
                                    <i class="bi bi-clipboard2-pulse text-primary me-2"></i>
                                    Consultations précédentes
                                    <span class="badge bg-primary ms-2" id="pcConsultBadge">0</span>
                                </button>
                            </h2>
                            <div id="pcCollapseConsults" class="accordion-collapse collapse"
                                 data-bs-parent="#pcHistoAccordion">
                                <div class="accordion-body p-2" id="pcConsultsList">
                                    <p class="text-muted small mb-0">Aucune consultation trouvée.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Hospitalisations -->
                        <div class="accordion-item border rounded-3 mb-2">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed rounded-3 fw-bold"
                                        style="font-size:.85rem"
                                        type="button" data-bs-toggle="collapse"
                                        data-bs-target="#pcCollapseHosps">
                                    <i class="bi bi-hospital text-warning me-2"></i>
                                    Hospitalisations
                                    <span class="badge bg-warning text-dark ms-2" id="pcHospBadge">0</span>
                                </button>
                            </h2>
                            <div id="pcCollapseHosps" class="accordion-collapse collapse"
                                 data-bs-parent="#pcHistoAccordion">
                                <div class="accordion-body p-2" id="pcHospsList">
                                    <p class="text-muted small mb-0">Aucune hospitalisation trouvée.</p>
                                </div>
                            </div>
                        </div>

                    </div><!-- /accordion -->

                    <!-- Formulaire admission -->
                    <form id="pcAdmissionForm" class="mx-4 mt-3 mb-4" onsubmit="pcSoumettre(event)">
                        <input type="hidden" id="pcPatientId" name="patient_id" value="">

                        <div class="mb-3">
                            <label class="form-label fw-bold text-primary small">
                                <i class="bi bi-chat-square-text me-1"></i>MOTIF DE CONSULTATION
                            </label>
                            <textarea name="motif" id="pcMotif" class="form-control border-2"
                                      rows="2" required
                                      placeholder="Motif de ce retour aux urgences…"></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold text-primary small">
                                <i class="bi bi-activity me-1"></i>NIVEAU DE TRIAGE
                            </label>
                            <div class="d-flex gap-2 flex-wrap">
                                <?php
                                $triageOpts = [
                                    1 => ['P1','VITAL','danger'],
                                    2 => ['P2','URGENT','warning'],
                                    3 => ['P3','STABLE','primary'],
                                    4 => ['P4','MINEUR','success'],
                                    5 => ['P5','SURVEILLANCE','secondary'],
                                ];
                                foreach ($triageOpts as $n => [$code, $label, $color]):
                                ?>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio"
                                           name="niveau_triage" id="pcTriage<?= $n ?>"
                                           value="<?= $n ?>"
                                           <?= $n === 3 ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="pcTriage<?= $n ?>">
                                        <span class="badge bg-<?= $color ?> text-<?= $color === 'warning' ? 'dark' : 'white' ?>">
                                            <?= $code ?>
                                        </span>
                                        <span class="small ms-1"><?= $label ?></span>
                                    </label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <button type="submit" id="pcSubmitBtn"
                                class="btn btn-primary btn-lg w-100 rounded-pill fw-bold shadow">
                            <i class="bi bi-plus-circle-fill me-2"></i>
                            ADMETTRE AUX URGENCES
                        </button>
                    </form>

                </div><!-- /Phase 2 -->
            </div><!-- /modal-body -->
        </div>
    </div>
</div>

<script>
/* ── Modal Patient Connu ─────────────────────────────────────── */
(function () {
    const BASE = '<?= BASE_URL ?>';
    let _pcTimer  = null;
    let _selectedPatientId = null;

    window.ouvrirModalPatientConnu = function () {
        pcReset();
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalPatientConnu')).show();
        setTimeout(() => document.getElementById('pcSearchInput')?.focus(), 350);
    };

    window.pcRechercherDebounce = function () {
        clearTimeout(_pcTimer);
        _pcTimer = setTimeout(pcRechercher, 380);
    };

    function pcRechercher() {
        const q = document.getElementById('pcSearchInput').value.trim();
        const resultsEl = document.getElementById('pcSearchResults');
        if (q.length < 2) {
            resultsEl.innerHTML = `<div class="text-center text-muted py-4 small">
                <i class="bi bi-person-lines-fill display-4 opacity-25 d-block mb-2"></i>
                Saisir au moins 2 caractères</div>`;
            return;
        }
        resultsEl.innerHTML = `<div class="text-center py-4">
            <span class="spinner-border spinner-border-sm text-primary me-2"></span>Recherche…</div>`;

        fetch(BASE + 'urgences/rechercher-patient-connu?q=' + encodeURIComponent(q))
            .then(r => r.json())
            .then(data => {
                if (!data.results || data.results.length === 0) {
                    resultsEl.innerHTML = `<div class="text-center text-muted py-4 small">
                        <i class="bi bi-person-x display-3 opacity-25 d-block mb-2"></i>
                        Aucun patient trouvé pour <strong>${escPC(q)}</strong></div>`;
                    return;
                }
                resultsEl.innerHTML = data.results.map(p => {
                    const lastVisit = p.derniere_consultation
                        ? `<span class="badge bg-light text-muted border ms-1">
                               <i class="bi bi-calendar3 me-1"></i>${fmtDate(p.derniere_consultation)}</span>`
                        : '';
                    const hospBadge = p.nb_hospitalisations > 0
                        ? `<span class="badge bg-warning text-dark border ms-1">
                               <i class="bi bi-hospital me-1"></i>${p.nb_hospitalisations} hosp.</span>`
                        : '';
                    const sexIcon = p.sexe === 'F' ? '♀' : '♂';
                    const sexColor = p.sexe === 'F' ? '#be185d' : '#1d4ed8';
                    return `<div class="pc-result-item d-flex align-items-center gap-3 p-3 mb-1
                                        rounded-3 border bg-white"
                                 style="cursor:pointer;transition:.15s"
                                 onmouseover="this.style.background='#f0f9ff'"
                                 onmouseout="this.style.background='#fff'"
                                 onclick="pcSelectionner(${p.id})">
                        <div class="pc-avatar d-flex align-items-center justify-content-center fw-bold"
                             style="width:42px;height:42px;border-radius:50%;background:#e0f2fe;
                                    color:${sexColor};font-size:1.2rem;flex-shrink:0">
                            ${sexIcon}
                        </div>
                        <div class="flex-grow-1 min-width-0">
                            <div class="fw-bold text-dark">${escPC(p.nom)} ${escPC(p.prenom)}</div>
                            <div class="small text-muted">
                                <span class="font-monospace">${escPC(p.dossier_numero)}</span>
                                ${p.age ? ' · ' + p.age + ' ans' : ''}
                                ${p.telephone ? ' · <i class="bi bi-telephone-fill"></i> ' + escPC(p.telephone) : ''}
                            </div>
                            <div class="mt-1">
                                <span class="badge bg-light text-muted border">
                                    <i class="bi bi-clipboard2 me-1"></i>${p.nb_consultations} consult.
                                </span>
                                ${hospBadge}${lastVisit}
                            </div>
                        </div>
                        <i class="bi bi-chevron-right text-muted"></i>
                    </div>`;
                }).join('');
            })
            .catch(() => {
                resultsEl.innerHTML = `<div class="alert alert-danger small">Erreur réseau.</div>`;
            });
    }

    window.pcSelectionner = function (patientId) {
        _selectedPatientId = patientId;
        document.getElementById('pcPatientId').value = patientId;
        document.getElementById('pcPhase1').classList.add('d-none');
        document.getElementById('pcPhase2').classList.remove('d-none');
        document.getElementById('pcPatientCard').innerHTML = `
            <div class="text-center py-3">
                <span class="spinner-border text-primary"></span>
                <div class="small text-muted mt-2">Chargement du dossier…</div>
            </div>`;

        fetch(BASE + 'urgences/historique-patient/' + patientId)
            .then(r => r.json())
            .then(data => {
                if (!data.success) throw new Error(data.message || 'Erreur');
                pcRendreHistorique(data);
            })
            .catch(err => {
                document.getElementById('pcPatientCard').innerHTML =
                    `<div class="alert alert-danger small">${escPC(err.message)}</div>`;
            });
    };

    function pcRendreHistorique(data) {
        const p = data.patient;
        const age = p.age ? p.age + ' ans' : '—';
        const sexLabel = p.sexe === 'F' ? '♀ Féminin' : '♂ Masculin';
        const sexColor = p.sexe === 'F' ? '#be185d' : '#1d4ed8';

        // Carte patient
        document.getElementById('pcPatientCard').innerHTML = `
            <div class="d-flex align-items-center gap-3">
                <div style="width:54px;height:54px;border-radius:50%;background:#e0f2fe;
                            display:flex;align-items:center;justify-content:center;
                            font-size:1.5rem;color:${sexColor};font-weight:800;flex-shrink:0">
                    ${p.sexe === 'F' ? '♀' : '♂'}
                </div>
                <div class="flex-grow-1">
                    <div class="fw-bold" style="font-size:1.1rem;color:#0c4a6e">
                        ${escPC(p.nom)} ${escPC(p.prenom)}
                    </div>
                    <div class="small text-muted">
                        <span class="font-monospace">${escPC(p.dossier_numero)}</span>
                        · ${age} · ${sexLabel}
                    </div>
                    ${p.telephone ? `<div class="small text-muted mt-1">
                        <i class="bi bi-telephone-fill text-primary me-1"></i>${escPC(p.telephone)}</div>` : ''}
                </div>
                <a href="<?= BASE_URL ?>patients/${p.id}/dossier" target="_blank"
                   class="btn btn-sm btn-outline-primary rounded-pill px-3">
                    <i class="bi bi-folder2-open me-1"></i>Dossier
                </a>
            </div>`;

        // Antécédents
        const antWrap  = document.getElementById('pcAntecedents');
        const antBody  = document.getElementById('pcAntecedentsBody');
        if (data.antecedents && data.antecedents.length > 0) {
            antBody.innerHTML = data.antecedents.map(a =>
                `<span class="badge rounded-pill border border-danger text-danger small">
                    ${escPC(a.type)} : ${escPC(a.description)}</span>`
            ).join('');
            antWrap.classList.remove('d-none');
        } else {
            antWrap.classList.add('d-none');
        }

        // Consultations
        document.getElementById('pcConsultBadge').textContent = data.consultations.length;
        if (data.consultations.length > 0) {
            document.getElementById('pcConsultsList').innerHTML = data.consultations.map(c =>
                `<div class="d-flex align-items-start gap-2 py-2 border-bottom">
                    <div class="badge bg-primary mt-1" style="font-size:.65rem;min-width:72px">
                        ${fmtDate(c.date_consultation)}
                    </div>
                    <div class="small">
                        <div class="fw-semibold text-dark">${escPC(c.diagnostic)}</div>
                        <div class="text-muted">${escPC(c.motif)}</div>
                        <div class="text-muted"><i class="bi bi-person-badge me-1"></i>Dr. ${escPC(c.medecin_nom)}</div>
                    </div>
                </div>`
            ).join('');
        }

        // Hospitalisations
        document.getElementById('pcHospBadge').textContent = data.hospitalisations.length;
        if (data.hospitalisations.length > 0) {
            document.getElementById('pcHospsList').innerHTML = data.hospitalisations.map(h => {
                const entree = fmtDate(h.date_admission);
                const sortie = h.date_sortie_effective ? fmtDate(h.date_sortie_effective) : '<em>en cours</em>';
                const statutBadge = h.statut === 'en_cours'
                    ? '<span class="badge bg-warning text-dark">En cours</span>'
                    : '<span class="badge bg-success">Sorti</span>';
                return `<div class="d-flex align-items-start gap-2 py-2 border-bottom">
                    <div class="small">
                        <div class="fw-semibold text-dark">${escPC(h.service)} ${statutBadge}</div>
                        <div class="text-muted">Entrée : ${entree} — Sortie : ${sortie}</div>
                        <div class="text-muted">${escPC(h.motif)}</div>
                    </div>
                </div>`;
            }).join('');
        }
    }

    window.pcRetourRecherche = function () {
        document.getElementById('pcPhase2').classList.add('d-none');
        document.getElementById('pcPhase1').classList.remove('d-none');
        _selectedPatientId = null;
        document.getElementById('pcPatientId').value = '';
    };

    window.pcSoumettre = function (e) {
        e.preventDefault();
        const form   = document.getElementById('pcAdmissionForm');
        const btn    = document.getElementById('pcSubmitBtn');
        const fd     = new FormData(form);

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Admission…';

        fetch(BASE + 'urgences/readmettre-patient', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalPatientConnu')).hide();
                    // Afficher une notification de succès brève avant reload
                    const toast = document.createElement('div');
                    toast.className = 'position-fixed top-0 start-50 translate-middle-x mt-3 alert alert-success shadow fw-bold z-3';
                    toast.style.cssText = 'border-radius:16px;z-index:9999;min-width:320px;text-align:center';
                    toast.innerHTML = `<i class="bi bi-check-circle-fill me-2"></i>
                        ${escPC(data.patient?.nom || '')} ${escPC(data.patient?.prenom || '')}
                        admis(e) avec succès !`;
                    document.body.appendChild(toast);
                    setTimeout(() => location.reload(), 1400);
                } else {
                    alert('❌ ' + (data.message || 'Erreur inconnue.'));
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-plus-circle-fill me-2"></i>ADMETTRE AUX URGENCES';
                }
            })
            .catch(() => {
                alert('❌ Erreur réseau.');
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-plus-circle-fill me-2"></i>ADMETTRE AUX URGENCES';
            });
    };

    function pcReset() {
        document.getElementById('pcSearchInput').value = '';
        document.getElementById('pcSearchResults').innerHTML = `
            <div class="text-center text-muted py-5" style="font-size:.9rem">
                <i class="bi bi-person-lines-fill display-4 opacity-25 d-block mb-2"></i>
                Saisir au moins 2 caractères pour rechercher</div>`;
        document.getElementById('pcPhase2').classList.add('d-none');
        document.getElementById('pcPhase1').classList.remove('d-none');
        document.getElementById('pcPatientId').value = '';
        document.getElementById('pcMotif').value = '';
        document.querySelector('input[name="niveau_triage"][value="3"]').checked = true;
        _selectedPatientId = null;
    }

    function fmtDate(s) {
        if (!s) return '—';
        const d = new Date(s);
        if (isNaN(d)) return s;
        return d.toLocaleDateString('fr-FR', { day:'2-digit', month:'2-digit', year:'numeric' });
    }

    function escPC(s) {
        if (!s) return '';
        return String(s).replace(/[&<>"']/g, c =>
            ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));
    }
})();
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
