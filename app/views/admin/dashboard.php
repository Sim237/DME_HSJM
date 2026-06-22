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

require_once __DIR__ . '/../layouts/header.php';
$adminPage = 'dashboard';
require_once __DIR__ . '/partials/admin_nav.php';

// Variables attendues depuis AdminDashboardController
$stats             = $stats             ?? [];
$activite_services = $activite_services ?? [];
$repartition_roles = $repartition_roles ?? [];
$recent_logs       = $recent_logs       ?? [];
$evol_7j           = $evol_7j           ?? [];
$system_status     = $system_status     ?? [];
?>
<style>
/* ─── KPI Grid ─── */
.adm-kpi-grid {
    display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:22px;
}
@media(max-width:1280px){.adm-kpi-grid{grid-template-columns:repeat(2,1fr);}}
@media(max-width:640px) {.adm-kpi-grid{grid-template-columns:1fr;}}

.adm-kpi {
    background:#fff;border-radius:18px;padding:20px 20px 16px;
    position:relative;overflow:hidden;
    box-shadow:0 2px 12px rgba(0,0,0,.06);
    transition:transform .2s,box-shadow .2s;cursor:default;
}
.adm-kpi:hover{transform:translateY(-3px);box-shadow:0 10px 28px rgba(0,0,0,.1);}
.adm-kpi-glow{position:absolute;top:-30px;right:-30px;width:100px;height:100px;border-radius:50%;opacity:.07;pointer-events:none;}
.adm-kpi-icon{width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.25rem;margin-bottom:14px;position:relative;z-index:1;}
.adm-kpi-value{font-size:1.9rem;font-weight:900;color:#0f172a;line-height:1;position:relative;z-index:1;font-variant-numeric:tabular-nums;}
.adm-kpi-label{font-size:.67rem;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:#94a3b8;margin-top:4px;position:relative;z-index:1;}
.adm-kpi-delta{position:absolute;top:16px;right:16px;font-size:.67rem;font-weight:700;padding:3px 8px;border-radius:20px;z-index:1;}
.adm-kpi-bar{height:3px;border-radius:3px;margin-top:12px;background:#f1f5f9;overflow:hidden;position:relative;z-index:1;}
.adm-kpi-bar-fill{height:100%;border-radius:3px;transition:width 1.2s cubic-bezier(.16,1,.3,1);}

/* ─── Grille principale ─── */
.adm-main-grid{display:grid;grid-template-columns:1fr 320px;gap:18px;align-items:start;}
@media(max-width:1100px){.adm-main-grid{grid-template-columns:1fr;}}

/* ─── Cards ─── */
.adm-card{background:#fff;border-radius:18px;box-shadow:0 2px 12px rgba(0,0,0,.06);overflow:hidden;margin-bottom:18px;}
.adm-card-head{padding:16px 20px 0;display:flex;align-items:center;justify-content:space-between;}
.adm-card-title{font-size:.87rem;font-weight:800;color:#0f172a;display:flex;align-items:center;gap:8px;}
.adm-card-icon{width:28px;height:28px;border-radius:7px;display:flex;align-items:center;justify-content:center;font-size:.78rem;}
.adm-card-body{padding:14px 20px 18px;}

/* ─── Timeline ─── */
.adm-tl-item{display:flex;gap:12px;padding:9px 0;border-bottom:1px solid #f8fafc;align-items:flex-start;}
.adm-tl-item:last-child{border-bottom:none;}
.adm-tl-dot{width:7px;height:7px;border-radius:50%;margin-top:6px;flex-shrink:0;}
.adm-tl-time{font-size:.67rem;font-weight:700;color:#94a3b8;min-width:34px;margin-top:2px;}
.adm-tl-user{font-size:.78rem;font-weight:700;color:#0f172a;}
.adm-tl-meta{font-size:.71rem;color:#64748b;margin-top:1px;}
.adm-tl-badge{margin-left:auto;flex-shrink:0;font-size:.6rem;font-weight:800;padding:2px 7px;border-radius:7px;text-transform:uppercase;}

/* ─── Panneau serveur ─── */
.adm-server{background:linear-gradient(160deg,#0a0f1e 0%,#0d1b3e 100%);border-radius:18px;padding:20px;color:#e2e8f0;box-shadow:0 4px 20px rgba(0,0,0,.15);}
.adm-server-title{font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:1.5px;color:#475569;margin-bottom:16px;display:flex;align-items:center;gap:7px;}
.adm-server-title::after{content:'';flex:1;height:1px;background:rgba(255,255,255,.06);}
.adm-gauge-wrap{margin-bottom:14px;}
.adm-gauge-label{display:flex;justify-content:space-between;font-size:.71rem;font-weight:600;margin-bottom:6px;color:#94a3b8;}
.adm-gauge-label strong{color:#e2e8f0;}
.adm-gauge-track{height:5px;border-radius:5px;background:rgba(255,255,255,.08);overflow:hidden;}
.adm-gauge-fill{height:100%;border-radius:5px;transition:width 1.5s cubic-bezier(.16,1,.3,1);}
.adm-status-chip{display:flex;align-items:center;justify-content:space-between;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.07);border-radius:9px;padding:9px 12px;margin-bottom:7px;font-size:.74rem;}
.adm-status-chip-label{color:#64748b;font-weight:500;}
.adm-status-ok{color:#4ade80;font-weight:700;}

/* ─── Quick actions ─── */
.adm-quick-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:12px;}
.adm-quick-btn{display:flex;align-items:center;gap:8px;padding:9px 11px;border-radius:10px;text-decoration:none;font-size:.73rem;font-weight:600;background:rgba(255,255,255,.05);color:#cbd5e1;border:1px solid rgba(255,255,255,.07);transition:all .18s;}
.adm-quick-btn:hover{background:rgba(255,255,255,.1);color:#fff;transform:translateY(-1px);}
.adm-quick-btn i{font-size:.95rem;}

/* ─── Module shortcuts ─── */
.adm-modules-grid{display:grid;grid-template-columns:repeat(5,1fr);gap:12px;margin-bottom:18px;}
@media(max-width:1100px){.adm-modules-grid{grid-template-columns:repeat(3,1fr);}}
@media(max-width:640px) {.adm-modules-grid{grid-template-columns:repeat(2,1fr);}}
.adm-module-card{background:#fff;border-radius:16px;padding:16px 12px;text-decoration:none;display:flex;flex-direction:column;align-items:center;gap:8px;text-align:center;box-shadow:0 2px 10px rgba(0,0,0,.05);border:1px solid #f1f5f9;transition:all .2s;}
.adm-module-card:hover{transform:translateY(-4px);box-shadow:0 10px 28px rgba(0,0,0,.1);border-color:transparent;}
.adm-module-icon{width:46px;height:46px;border-radius:13px;display:flex;align-items:center;justify-content:center;font-size:1.3rem;flex-shrink:0;}
.adm-module-name{font-size:.73rem;font-weight:700;color:#0f172a;line-height:1.3;}
.adm-module-sub{font-size:.63rem;color:#94a3b8;}

/* ─── Activité par service ─── */
.svc-bar-item{display:flex;align-items:center;gap:10px;padding:6px 0;border-bottom:1px solid #f8fafc;}
.svc-bar-item:last-child{border-bottom:none;}
.svc-bar-name{font-size:.78rem;font-weight:600;color:#374151;min-width:130px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.svc-bar-track{flex:1;height:7px;background:#f1f5f9;border-radius:7px;overflow:hidden;}
.svc-bar-fill{height:100%;border-radius:7px;transition:width 1s cubic-bezier(.16,1,.3,1);}
.svc-bar-count{font-size:.72rem;font-weight:700;color:#64748b;min-width:28px;text-align:right;}

/* ─── Rôles ─── */
.role-badge{display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border-radius:20px;font-size:.7rem;font-weight:700;margin:3px;}

/* ─── Live badge ─── */
.adm-live{display:inline-flex;align-items:center;gap:5px;font-size:.62rem;font-weight:700;color:#16a34a;background:#f0fdf4;padding:3px 9px;border-radius:20px;}
.adm-live-dot{width:5px;height:5px;border-radius:50%;background:#22c55e;animation:pulse-dot 1.5s ease-in-out infinite;}
@keyframes pulse-dot{0%,100%{opacity:1;transform:scale(1);}50%{opacity:.5;transform:scale(.8);}}

/* ─── Section 2e ligne KPI (temps réel) ─── */
.adm-kpi2-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:22px;}
@media(max-width:1280px){.adm-kpi2-grid{grid-template-columns:repeat(2,1fr);}}

.adm-kpi-sm{background:#fff;border-radius:14px;padding:14px 16px;display:flex;align-items:center;gap:12px;
    box-shadow:0 2px 10px rgba(0,0,0,.05);border:1px solid #f1f5f9;transition:transform .2s;}
.adm-kpi-sm:hover{transform:translateY(-2px);}
.adm-kpi-sm-icon{width:38px;height:38px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0;}
.adm-kpi-sm-val{font-size:1.5rem;font-weight:900;color:#0f172a;line-height:1;}
.adm-kpi-sm-lbl{font-size:.63rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#94a3b8;margin-top:2px;}
</style>

<!-- TOPBAR -->
<div class="admin-topbar">
    <div class="admin-topbar-title">
        Tableau de bord
        <small>Vue d'ensemble — <?= date('l d F Y') ?></small>
    </div>
    <div class="adm-topbar-actions">
        <div class="adm-live"><div class="adm-live-dot"></div>LIVE — <span id="liveTs">--:--:--</span></div>
        <div class="adm-clock-pill">
            <div class="adm-clock-dot"></div>
            <span id="liveClock" class="adm-clock-time">00:00:00</span>
        </div>
        <button class="adm-topbtn adm-topbtn-ghost" onclick="location.reload()" title="Rafraîchir">
            <i class="bi bi-arrow-clockwise"></i>
        </button>
        <button class="adm-topbtn adm-topbtn-ghost" onclick="ouvrirProfilModal()">
            <i class="bi bi-person-circle"></i>
            <?= htmlspecialchars($_SESSION['user_prenom'] ?? '') ?>
        </button>
    </div>
</div>

<div class="admin-page-content">

    <!-- ─── KPIs principaux (statiques) ─── -->
    <div class="adm-kpi-grid">

        <div class="adm-kpi" style="border-top:3px solid #3b82f6">
            <div class="adm-kpi-glow" style="background:#3b82f6"></div>
            <span class="adm-kpi-delta" style="background:#eff6ff;color:#1d4ed8">+<?= $stats['patients_ce_mois'] ?? 0 ?> ce mois</span>
            <div class="adm-kpi-icon" style="background:#eff6ff;color:#3b82f6"><i class="bi bi-people-fill"></i></div>
            <div class="adm-kpi-value count-up" data-target="<?= $stats['total_patients'] ?? 0 ?>">0</div>
            <div class="adm-kpi-label">Patients enregistrés</div>
            <div class="adm-kpi-bar"><div class="adm-kpi-bar-fill" style="width:70%;background:linear-gradient(90deg,#3b82f6,#6366f1)"></div></div>
        </div>

        <div class="adm-kpi" style="border-top:3px solid #0ea5e9">
            <div class="adm-kpi-glow" style="background:#0ea5e9"></div>
            <span class="adm-kpi-delta" style="background:#f0f9ff;color:#0369a1"><?= $stats['lits_occupes'] ?? 0 ?>/<?= $stats['lits_total'] ?? 0 ?> lits</span>
            <div class="adm-kpi-icon" style="background:#f0f9ff;color:#0ea5e9"><i class="bi bi-hospital-fill"></i></div>
            <div class="adm-kpi-value count-up" data-target="<?= $stats['hosp_actuelles'] ?? 0 ?>">0</div>
            <div class="adm-kpi-label">Hospitalisations actives</div>
            <div class="adm-kpi-bar"><div class="adm-kpi-bar-fill" style="width:<?= $stats['lits_total'] ? min(100,round($stats['lits_occupes']/$stats['lits_total']*100)) : 0 ?>%;background:linear-gradient(90deg,#0ea5e9,#38bdf8)"></div></div>
        </div>

        <div class="adm-kpi" style="border-top:3px solid #10b981">
            <div class="adm-kpi-glow" style="background:#10b981"></div>
            <span class="adm-kpi-delta" style="background:#f0fdf4;color:#15803d"><?= $stats['consultations_aujourd_hui'] ?? 0 ?> auj.</span>
            <div class="adm-kpi-icon" style="background:#f0fdf4;color:#10b981"><i class="bi bi-stethoscope"></i></div>
            <div class="adm-kpi-value count-up" data-target="<?= $stats['consultations_ce_mois'] ?? 0 ?>">0</div>
            <div class="adm-kpi-label">Consultations ce mois</div>
            <div class="adm-kpi-bar"><div class="adm-kpi-bar-fill" style="width:65%;background:linear-gradient(90deg,#10b981,#34d399)"></div></div>
        </div>

        <div class="adm-kpi" style="border-top:3px solid <?= ($stats['alertes_stock']??0)>0?'#ef4444':'#94a3b8' ?>">
            <div class="adm-kpi-glow" style="background:#ef4444"></div>
            <?php if (($stats['alertes_stock']??0)>0): ?>
            <span class="adm-kpi-delta" style="background:#fef2f2;color:#dc2626"><i class="bi bi-exclamation-circle-fill me-1"></i>Alerte</span>
            <?php endif; ?>
            <div class="adm-kpi-icon" style="background:#fef2f2;color:#ef4444"><i class="bi bi-exclamation-triangle-fill"></i></div>
            <div class="adm-kpi-value count-up" data-target="<?= $stats['alertes_stock'] ?? 0 ?>"
                 style="color:<?= ($stats['alertes_stock']??0)>0?'#dc2626':'#0f172a' ?>">0</div>
            <div class="adm-kpi-label">Alertes stock pharmacie</div>
            <div class="adm-kpi-bar"><div class="adm-kpi-bar-fill" style="width:<?= min(100,($stats['alertes_stock']??0)*8) ?>%;background:linear-gradient(90deg,#ef4444,#f97316)"></div></div>
        </div>

    </div>

    <!-- ─── KPIs temps réel (rafraîchis par AJAX) ─── -->
    <div class="adm-kpi2-grid">

        <div class="adm-kpi-sm">
            <div class="adm-kpi-sm-icon" style="background:#fef9c3;color:#ca8a04"><i class="bi bi-clock-history"></i></div>
            <div>
                <div class="adm-kpi-sm-val" id="liveAttente"><?= $stats['patients_attente'] ?? 0 ?></div>
                <div class="adm-kpi-sm-lbl">En attente maintenant</div>
            </div>
        </div>

        <div class="adm-kpi-sm">
            <div class="adm-kpi-sm-icon" style="background:#fef2f2;color:#dc2626"><i class="bi bi-lightning-charge-fill"></i></div>
            <div>
                <div class="adm-kpi-sm-val" id="liveUrgences"><?= $stats['urgences_en_cours'] ?? 0 ?></div>
                <div class="adm-kpi-sm-lbl">Urgences en cours</div>
            </div>
        </div>

        <div class="adm-kpi-sm">
            <div class="adm-kpi-sm-icon" style="background:#faf5ff;color:#7c3aed"><i class="bi bi-flask-fill"></i></div>
            <div>
                <div class="adm-kpi-sm-val" id="liveBilans"><?= $stats['bilans_en_attente'] ?? 0 ?></div>
                <div class="adm-kpi-sm-lbl">Bilans en attente</div>
            </div>
        </div>

        <div class="adm-kpi-sm">
            <div class="adm-kpi-sm-icon" style="background:#f0fdf4;color:#16a34a"><i class="bi bi-person-badge-fill"></i></div>
            <div>
                <div class="adm-kpi-sm-val"><?= $stats['users_actifs'] ?? 0 ?></div>
                <div class="adm-kpi-sm-lbl">Utilisateurs actifs</div>
            </div>
        </div>

        <div class="adm-kpi-sm" style="cursor:pointer;" onclick="location.href='<?= BASE_URL ?>bloc'">
            <div class="adm-kpi-sm-icon" style="background:#fff7ed;color:#ea580c"><i class="bi bi-scissors"></i></div>
            <div>
                <div class="adm-kpi-sm-val"><?= $stats['interventions_jour'] ?? 0 ?></div>
                <div class="adm-kpi-sm-lbl">Interventions aujourd'hui</div>
            </div>
        </div>

        <div class="adm-kpi-sm" style="cursor:pointer;" onclick="location.href='<?= BASE_URL ?>bloc/sspi-dashboard'">
            <div class="adm-kpi-sm-icon" style="background:#fef3c7;color:#d97706"><i class="bi bi-heart-pulse-fill"></i></div>
            <div>
                <div class="adm-kpi-sm-val"><?= $stats['sspi_en_cours'] ?? 0 ?></div>
                <div class="adm-kpi-sm-lbl">Patients en SSPI</div>
            </div>
        </div>

    </div>

    <!-- ─── Raccourcis modules ─── -->
    <div class="adm-modules-grid">
        <?php
        $modules = [
            ['href'=>'admin/patients',    'icon'=>'bi-person-vcard-fill', 'bg'=>'#eff6ff','color'=>'#2563eb','name'=>'Patients',      'sub'=>'Dossiers'],
            ['href'=>'utilisateurs',       'icon'=>'bi-people-fill',       'bg'=>'#f5f3ff','color'=>'#7c3aed','name'=>'Utilisateurs', 'sub'=>'Comptes'],
            ['href'=>'admin/services',    'icon'=>'bi-building-fill',     'bg'=>'#ecfdf5','color'=>'#059669','name'=>'Services',      'sub'=>'Org.'],
            ['href'=>'admin/pharmacie',   'icon'=>'bi-capsule-pill',      'bg'=>'#fff7ed','color'=>'#ea580c','name'=>'Pharmacie',     'sub'=>'Stock'],
            ['href'=>'admin/laboratoire', 'icon'=>'bi-flask-fill',        'bg'=>'#fefce8','color'=>'#ca8a04','name'=>'Labo',         'sub'=>'Examens'],
            ['href'=>'admin/permissions', 'icon'=>'bi-shield-fill-check', 'bg'=>'#fdf2f8','color'=>'#9d174d','name'=>'Permissions',  'sub'=>'Accès'],
            ['href'=>'admin/config',      'icon'=>'bi-gear-fill',         'bg'=>'#fff7ed','color'=>'#b45309','name'=>'Config',       'sub'=>'Système'],
            ['href'=>'admin/logs',        'icon'=>'bi-journal-code',      'bg'=>'#f8fafc','color'=>'#475569','name'=>'Audit',        'sub'=>'Journaux'],
            ['href'=>'admin/lits',        'icon'=>'bi-grid-3x3-gap-fill','bg'=>'#f0fdf4','color'=>'#16a34a','name'=>'Lits',         'sub'=>'Infra'],
            ['href'=>'directeur/patients','icon'=>'bi-bar-chart-fill',    'bg'=>'#eff6ff','color'=>'#1d4ed8','name'=>'Statistiques', 'sub'=>'Rapports'],
            ['href'=>'admin/bloc',         'icon'=>'bi-scissors',           'bg'=>'#fff7ed','color'=>'#ea580c','name'=>'Bloc opératoire','sub'=>'Salles & Interv.'],
            ['href'=>'bloc/sspi-dashboard','icon'=>'bi-heart-pulse-fill', 'bg'=>'#fef3c7','color'=>'#d97706','name'=>'SSPI / Anesthésie','sub'=>'Réveil'],
        ];
        foreach ($modules as $m):
        ?>
        <a href="<?= BASE_URL ?><?= $m['href'] ?>" class="adm-module-card">
            <div class="adm-module-icon" style="background:<?= $m['bg'] ?>;color:<?= $m['color'] ?>">
                <i class="bi <?= $m['icon'] ?>"></i>
            </div>
            <div class="adm-module-name"><?= $m['name'] ?></div>
            <div class="adm-module-sub"><?= $m['sub'] ?></div>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- ─── Grille principale ─── -->
    <div class="adm-main-grid">

        <!-- GAUCHE -->
        <div>

            <!-- Chart 7 derniers jours -->
            <div class="adm-card">
                <div class="adm-card-head">
                    <div class="adm-card-title">
                        <div class="adm-card-icon" style="background:#fef9c3;color:#ca8a04"><i class="bi bi-graph-up-arrow"></i></div>
                        Consultations — 7 derniers jours
                    </div>
                    <span style="font-size:.72rem;font-weight:600;color:#94a3b8"><?= $stats['consultations_aujourd_hui'] ?? 0 ?> aujourd'hui</span>
                </div>
                <div class="adm-card-body">
                    <div style="position:relative;height:200px;">
                        <canvas id="chartEvol"></canvas>
                    </div>
                </div>
            </div>

            <!-- Activité par service -->
            <?php if (!empty($activite_services)): ?>
            <div class="adm-card">
                <div class="adm-card-head">
                    <div class="adm-card-title">
                        <div class="adm-card-icon" style="background:#ecfdf5;color:#059669"><i class="bi bi-building-fill"></i></div>
                        Activité par service — mois courant
                    </div>
                </div>
                <div class="adm-card-body">
                    <?php
                    $maxNb = max(1, array_reduce($activite_services, fn($c,$s) => max($c, (int)$s['nb_consultations']), 0));
                    $colors = ['#3b82f6','#10b981','#f59e0b','#8b5cf6','#ef4444','#0ea5e9','#ec4899','#14b8a6'];
                    foreach ($activite_services as $i => $svc):
                        $pct = round((int)$svc['nb_consultations'] / $maxNb * 100);
                        $col = $colors[$i % count($colors)];
                    ?>
                    <div class="svc-bar-item">
                        <div class="svc-bar-name" title="<?= htmlspecialchars($svc['nom_service']) ?>">
                            <?= htmlspecialchars(mb_substr($svc['nom_service'],0,22)) ?>
                        </div>
                        <div class="svc-bar-track">
                            <div class="svc-bar-fill" style="width:0%;background:<?= $col ?>" data-target="<?= $pct ?>"></div>
                        </div>
                        <div class="svc-bar-count"><?= (int)$svc['nb_consultations'] ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Activité récente (audit) -->
            <div class="adm-card">
                <div class="adm-card-head">
                    <div class="adm-card-title">
                        <div class="adm-card-icon" style="background:#eff6ff;color:#3b82f6"><i class="bi bi-activity"></i></div>
                        Activités récentes
                    </div>
                    <a href="<?= BASE_URL ?>admin/logs" style="font-size:.74rem;font-weight:700;color:#3b82f6;text-decoration:none;">
                        Tout voir <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
                <div class="adm-card-body" style="padding-top:10px;">
                    <?php if (empty($recent_logs)): ?>
                    <div style="text-align:center;padding:30px;color:#94a3b8;font-size:.8rem;">
                        <i class="bi bi-inbox" style="font-size:1.8rem;display:block;margin-bottom:8px;opacity:.3"></i>
                        Aucune activité enregistrée.
                    </div>
                    <?php else: ?>
                    <?php
                    $actionConf = [
                        'INSERT' => ['dot'=>'#22c55e','bg'=>'#f0fdf4','txt'=>'#15803d','lbl'=>'Création'],
                        'UPDATE' => ['dot'=>'#3b82f6','bg'=>'#eff6ff','txt'=>'#1d4ed8','lbl'=>'Modif.'],
                        'DELETE' => ['dot'=>'#ef4444','bg'=>'#fef2f2','txt'=>'#dc2626','lbl'=>'Suppres.'],
                        'LOGIN'  => ['dot'=>'#8b5cf6','bg'=>'#f5f3ff','txt'=>'#6d28d9','lbl'=>'Connexion'],
                        'EXPORT' => ['dot'=>'#f59e0b','bg'=>'#fefce8','txt'=>'#92400e','lbl'=>'Export'],
                    ];
                    foreach ($recent_logs as $log):
                        $ac = $actionConf[$log['action']] ?? ['dot'=>'#94a3b8','bg'=>'#f8fafc','txt'=>'#475569','lbl'=>$log['action']];
                    ?>
                    <div class="adm-tl-item">
                        <div class="adm-tl-time"><?= date('H:i', strtotime($log['created_at'])) ?></div>
                        <div class="adm-tl-dot" style="background:<?= $ac['dot'] ?>;box-shadow:0 0 0 3px <?= $ac['bg'] ?>"></div>
                        <div style="flex:1;min-width:0">
                            <div class="adm-tl-user"><?= htmlspecialchars(trim($log['utilisateur'] ?? 'Système')) ?></div>
                            <div class="adm-tl-meta">
                                <?= htmlspecialchars($log['table_name'] ?? '') ?>
                                <?php if (!empty($log['description'])): ?>
                                · <em><?= htmlspecialchars(mb_substr($log['description'],0,55)) ?></em>
                                <?php endif; ?>
                            </div>
                        </div>
                        <span class="adm-tl-badge" style="background:<?= $ac['bg'] ?>;color:<?= $ac['txt'] ?>"><?= $ac['lbl'] ?></span>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

        </div>

        <!-- DROITE : Serveur + Rôles -->
        <div>

            <!-- Monitoring serveur -->
            <div class="adm-server" style="margin-bottom:18px;">
                <div class="adm-server-title"><i class="bi bi-cpu-fill" style="color:#38bdf8"></i>État du Serveur</div>

                <div class="adm-gauge-wrap">
                    <div class="adm-gauge-label">
                        <span>Charge CPU</span>
                        <strong><?= $system_status['CPU']['value'] ?? 0 ?>%</strong>
                    </div>
                    <div class="adm-gauge-track">
                        <div class="adm-gauge-fill" data-target="<?= $system_status['CPU']['value'] ?? 0 ?>"
                             style="width:0%;background:linear-gradient(90deg,#10b981,#34d399)"></div>
                    </div>
                </div>

                <div class="adm-gauge-wrap">
                    <div class="adm-gauge-label">
                        <span>Mémoire PHP</span>
                        <strong><?= $system_status['MEMORY']['value'] ?? 0 ?> MB</strong>
                    </div>
                    <div class="adm-gauge-track">
                        <div class="adm-gauge-fill"
                             style="width:<?= min(100, round(($system_status['MEMORY']['value']??0)/256*100)) ?>%;background:linear-gradient(90deg,#3b82f6,#6366f1)"></div>
                    </div>
                </div>

                <div class="adm-status-chip">
                    <span class="adm-status-chip-label"><i class="bi bi-database me-2"></i>Base de données</span>
                    <span class="adm-status-ok"><i class="bi bi-check-circle-fill me-1"></i>Connectée</span>
                </div>
                <div class="adm-status-chip">
                    <span class="adm-status-chip-label"><i class="bi bi-server me-2"></i>PHP <?= PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION ?></span>
                    <span class="adm-status-ok"><i class="bi bi-check-circle-fill me-1"></i>OK</span>
                </div>
                <div class="adm-status-chip" style="margin-bottom:0">
                    <span class="adm-status-chip-label"><i class="bi bi-gear me-2"></i>Config système</span>
                    <a href="<?= BASE_URL ?>admin/config" style="color:#fbbf24;font-weight:700;font-size:.72rem;text-decoration:none;">
                        <i class="bi bi-arrow-right me-1"></i>Gérer
                    </a>
                </div>

                <div style="margin-top:14px;">
                    <div class="adm-server-title" style="margin-bottom:10px"><i class="bi bi-lightning-fill" style="color:#fbbf24"></i>Accès rapides</div>
                    <div class="adm-quick-grid">
                        <a href="<?= BASE_URL ?>admin/config"      class="adm-quick-btn"><i class="bi bi-gear-fill" style="color:#fbbf24"></i>Config</a>
                        <a href="<?= BASE_URL ?>admin/permissions"  class="adm-quick-btn"><i class="bi bi-shield-fill-check" style="color:#f472b6"></i>Rôles</a>
                        <a href="<?= BASE_URL ?>admin/logs"         class="adm-quick-btn"><i class="bi bi-journal-code" style="color:#a78bfa"></i>Audit</a>
                        <a href="<?= BASE_URL ?>utilisateurs"        class="adm-quick-btn"><i class="bi bi-people-fill" style="color:#67e8f9"></i>Équipe</a>
                        <a href="<?= BASE_URL ?>admin/export-sql"   class="adm-quick-btn" title="Télécharger la base de données complète en SQL"><i class="bi bi-database-down" style="color:#34d399"></i>Export SQL</a>
                    </div>
                </div>
            </div>

            <!-- Répartition des rôles -->
            <?php if (!empty($repartition_roles)): ?>
            <div class="adm-card">
                <div class="adm-card-head">
                    <div class="adm-card-title">
                        <div class="adm-card-icon" style="background:#f5f3ff;color:#7c3aed"><i class="bi bi-person-badge-fill"></i></div>
                        Équipe — <?= $stats['users_actifs'] ?? 0 ?> actifs
                    </div>
                    <a href="<?= BASE_URL ?>utilisateurs" style="font-size:.74rem;font-weight:700;color:#7c3aed;text-decoration:none;">
                        Gérer <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
                <div class="adm-card-body" style="padding-top:10px;">
                    <?php
                    $roleColors = [
                        'MEDECIN'=>['#1e40af','#dbeafe'],'INFIRMIER'=>['#065f46','#d1fae5'],
                        'SECRETAIRE'=>['#92400e','#fef3c7'],'ADMIN'=>['#7c2d12','#fef2f2'],
                        'LABORANTIN'=>['#5b21b6','#f5f3ff'],'PHARMACIEN'=>['#9a3412','#fff7ed'],
                        'PARAMETRES'=>['#1d4ed8','#eff6ff'],'DIRECTEUR'=>['#374151','#f8fafc'],
                    ];
                    foreach ($repartition_roles as $r):
                        $rc = $roleColors[strtoupper($r['role'])] ?? ['#374151','#f8fafc'];
                    ?>
                    <div style="display:flex;align-items:center;justify-content:space-between;padding:5px 0;border-bottom:1px solid #f8fafc;">
                        <span style="font-size:.77rem;font-weight:700;color:<?= $rc[0] ?>;background:<?= $rc[1] ?>;padding:3px 10px;border-radius:20px;">
                            <?= htmlspecialchars($r['role']) ?>
                        </span>
                        <span style="font-size:.8rem;font-weight:800;color:#0f172a"><?= (int)$r['nb'] ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>

</div><!-- /admin-page-content -->
</div><!-- /admin-main -->
</div><!-- /admin-shell -->

<script src="<?= BASE_URL ?>public/js/chart.umd.min.js"></script>
<script>
// ── Horloge ───────────────────────────────────────────────────────────────────
(function tick(){
    const n=new Date(),p=v=>String(v).padStart(2,'0');
    const el=document.getElementById('liveClock');
    if(el) el.textContent=p(n.getHours())+':'+p(n.getMinutes())+':'+p(n.getSeconds());
    setTimeout(tick,1000);
})();

// ── Count-up KPI ──────────────────────────────────────────────────────────────
document.querySelectorAll('.count-up').forEach(el => {
    const target = parseInt(el.dataset.target)||0;
    if(!target){el.textContent='0';return;}
    let cur=0, step=Math.max(1,Math.ceil(target/40));
    const t=setInterval(()=>{
        cur=Math.min(cur+step,target);
        el.textContent=cur.toLocaleString('fr-FR');
        if(cur>=target)clearInterval(t);
    },28);
});

// ── Barres CPU ────────────────────────────────────────────────────────────────
document.querySelectorAll('.adm-gauge-fill[data-target]').forEach(b=>{
    const v=parseInt(b.dataset.target)||0;
    const col=v>80?'linear-gradient(90deg,#ef4444,#f97316)':v>60?'linear-gradient(90deg,#f59e0b,#fbbf24)':'linear-gradient(90deg,#10b981,#34d399)';
    b.style.background=col;
    requestAnimationFrame(()=>setTimeout(()=>b.style.width=v+'%',80));
});

// ── Barres activité par service ───────────────────────────────────────────────
document.querySelectorAll('.svc-bar-fill[data-target]').forEach(b=>{
    const v=parseInt(b.dataset.target)||0;
    requestAnimationFrame(()=>setTimeout(()=>b.style.width=v+'%',120));
});

// ── Chart évolution 7j ───────────────────────────────────────────────────────
(function(){
    const ctx=document.getElementById('chartEvol');
    if(!ctx||typeof Chart==='undefined')return;
    const raw = <?= json_encode(array_values($evol_7j)) ?>;
    const labels = raw.map(r=>r.jour);
    const data   = raw.map(r=>parseInt(r.nb)||0);
    new Chart(ctx,{
        type:'line',
        data:{
            labels,
            datasets:[{
                label:'Consultations',
                data,
                borderColor:'#3b82f6',
                backgroundColor:'rgba(59,130,246,.08)',
                borderWidth:2.5,
                pointBackgroundColor:'#3b82f6',
                pointRadius:4,
                tension:.35,
                fill:true,
            }]
        },
        options:{
            responsive:true,maintainAspectRatio:false,
            plugins:{legend:{display:false},tooltip:{mode:'index',intersect:false}},
            scales:{
                x:{grid:{display:false},ticks:{font:{size:11}}},
                y:{grid:{color:'#f1f5f9'},ticks:{font:{size:11}},beginAtZero:true,precision:0}
            }
        }
    });
})();

// ── Live KPI rafraîchi toutes les 30s ────────────────────────────────────────
(function liveRefresh(){
    fetch('<?= BASE_URL ?>admin/dashboard/live-kpi')
        .then(r=>r.json())
        .then(d=>{
            if(!d.success)return;
            const k=d.kpi;
            const set=(id,v)=>{const el=document.getElementById(id);if(el)el.textContent=v;};
            set('liveAttente',   k.patients_attente??'-');
            set('liveUrgences',  k.urgences_en_cours??'-');
            set('liveBilans',    k.bilans_en_attente??'-');
            set('liveTs',        d.ts||'');
        })
        .catch(()=>{});
    setTimeout(liveRefresh, 30000);
})();
</script>
