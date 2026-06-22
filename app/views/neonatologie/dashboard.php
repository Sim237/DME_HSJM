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
require_once __DIR__ . '/../../controllers/NeonatologyController.php';

$neonates       = $neonates       ?? [];
$examensRecents = $examensRecents ?? [];
$kpi            = $kpi            ?? ['hospitalises'=>0,'examens_jour'=>0,'examens_total'=>0,'alertes'=>0];
?>
<style>
    .sidebar, nav.sidebar { display:none !important; }
    main, .col-md-10, .ms-sm-auto { margin-left:0 !important; width:100% !important; flex:0 0 100% !important; max-width:100% !important; padding:0 !important; }
    body { background:#f0f7f9; font-family:'Segoe UI',system-ui,sans-serif; color:#1e293b; }

    /* Topbar */
    .neo-topbar {
        background: linear-gradient(135deg,#0e7490 0%,#06b6d4 60%,#22d3ee 100%);
        padding:16px 28px; display:flex; align-items:center; justify-content:space-between;
        position:sticky; top:0; z-index:100; box-shadow:0 4px 20px rgba(6,182,212,.3);
    }
    .neo-title { font-size:1.2rem; font-weight:800; color:#fff; letter-spacing:-.3px; display:flex; align-items:center; gap:10px; }
    .neo-subtitle { font-size:.7rem; color:rgba(255,255,255,.8); font-weight:600; text-transform:uppercase; letter-spacing:1.2px; }
    .neo-btn { display:inline-flex; align-items:center; gap:6px; padding:8px 16px; border-radius:10px; font-weight:700; font-size:.78rem; text-decoration:none; border:none; cursor:pointer; transition:all .18s; }
    .neo-btn-light { background:rgba(255,255,255,.2); color:#fff; border:1px solid rgba(255,255,255,.35); }
    .neo-btn-light:hover { background:rgba(255,255,255,.32); color:#fff; }

    .neo-main { padding:24px 28px; max-width:1200px; margin:0 auto; }

    /* KPI */
    .neo-kpi-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:16px; margin-bottom:24px; }
    .neo-kpi { background:#fff; border-radius:18px; padding:20px 22px; border:1px solid #e0f0f3; box-shadow:0 2px 12px rgba(6,182,212,.07); position:relative; overflow:hidden; }
    .neo-kpi::after { content:''; position:absolute; top:0; left:0; bottom:0; width:4px; border-radius:18px 0 0 18px; }
    .neo-kpi.k-cyan::after  { background:#06b6d4; } .neo-kpi.k-cyan  .neo-kpi-val { color:#0e7490; }
    .neo-kpi.k-green::after { background:#10b981; } .neo-kpi.k-green .neo-kpi-val { color:#059669; }
    .neo-kpi.k-blue::after  { background:#3b82f6; } .neo-kpi.k-blue  .neo-kpi-val { color:#2563eb; }
    .neo-kpi.k-amber::after { background:#f59e0b; } .neo-kpi.k-amber .neo-kpi-val { color:#d97706; }
    .neo-kpi-val { font-size:2.2rem; font-weight:900; line-height:1; }
    .neo-kpi-lbl { font-size:.72rem; color:#94a3b8; font-weight:700; text-transform:uppercase; letter-spacing:.7px; margin-top:4px; }
    .neo-kpi-ic { position:absolute; right:16px; top:50%; transform:translateY(-50%); font-size:2.4rem; opacity:.08; }

    /* Card panel */
    .neo-panel { background:#fff; border-radius:20px; border:1px solid #e0f0f3; box-shadow:0 2px 12px rgba(6,182,212,.06); margin-bottom:24px; overflow:hidden; }
    .neo-panel-head { padding:15px 22px; border-bottom:2px solid #cffafe; background:linear-gradient(90deg,#f0fdff,#fff); display:flex; align-items:center; justify-content:space-between; }
    .neo-panel-title { font-size:.82rem; font-weight:800; color:#0e7490; text-transform:uppercase; letter-spacing:.6px; display:flex; align-items:center; gap:8px; }

    /* Neonate cards */
    .neo-cards { display:grid; grid-template-columns:repeat(auto-fill,minmax(290px,1fr)); gap:16px; padding:18px 22px; }
    .neo-card { border:1px solid #e0f0f3; border-radius:16px; padding:16px; background:#fafdfe; transition:all .18s; position:relative; }
    .neo-card:hover { box-shadow:0 6px 18px rgba(6,182,212,.14); transform:translateY(-2px); }
    .neo-card.alerte { border-color:#fca5a5; background:#fff7f7; }
    .neo-card-head { display:flex; align-items:center; gap:11px; margin-bottom:12px; }
    .neo-avatar { width:46px; height:46px; border-radius:14px; display:flex; align-items:center; justify-content:center; font-size:1.3rem; flex-shrink:0; color:#fff; }
    .neo-avatar.g { background:linear-gradient(135deg,#06b6d4,#22d3ee); } /* garçon */
    .neo-avatar.f { background:linear-gradient(135deg,#ec4899,#f472b6); } /* fille */
    .neo-name { font-weight:800; font-size:.95rem; color:#0f172a; }
    .neo-meta { font-size:.72rem; color:#94a3b8; }
    .neo-vitals { display:grid; grid-template-columns:repeat(4,1fr); gap:6px; margin:10px 0; }
    .neo-vital { text-align:center; background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:6px 2px; }
    .neo-vital .v { font-weight:800; font-size:.85rem; color:#0e7490; }
    .neo-vital .v.warn { color:#dc2626; }
    .neo-vital .l { font-size:.6rem; color:#94a3b8; text-transform:uppercase; font-weight:700; }
    .neo-loc { font-size:.72rem; color:#475569; margin-bottom:10px; }
    .neo-card-actions { display:grid; grid-template-columns:repeat(3,1fr); gap:7px; margin-top:12px; }
    .neo-act {
      display:flex; flex-direction:column; align-items:center; justify-content:center;
      padding:9px 4px 7px; border-radius:14px; font-size:.63rem; font-weight:800;
      text-decoration:none; transition:all .18s; gap:5px; text-align:center;
      line-height:1.1; white-space:nowrap; cursor:pointer; border:1.5px solid transparent; }
    .neo-act i { font-size:1.1rem; display:block; }
    .neo-act:hover { transform:translateY(-2px); box-shadow:0 4px 14px rgba(0,0,0,.1); color:inherit; }
    .neo-act-exam { background:linear-gradient(135deg,#06b6d4,#22d3ee); color:#fff!important; border-color:transparent; }
    .neo-act-exam:hover { background:linear-gradient(135deg,#0891b2,#06b6d4); color:#fff!important; }
    .neo-act-file { background:#ecfeff; color:#0e7490!important; border-color:#a5f3fc; }
    .neo-act-file:hover { background:#cffafe; }
    .alerte-badge { position:absolute; top:12px; right:12px; background:#fee2e2; color:#dc2626; font-size:.62rem; font-weight:800; padding:2px 8px; border-radius:20px; border:1px solid #fca5a5; }

    .neo-empty { padding:40px 20px; text-align:center; color:#94a3b8; }
    .neo-empty i { font-size:2.6rem; opacity:.25; display:block; margin-bottom:10px; }

    /* search */
    .neo-search-wrap { position:relative; }
    #neoResults { position:absolute; top:100%; left:0; right:0; background:#fff; border:1px solid #cffafe; border-radius:12px; box-shadow:0 10px 30px rgba(6,182,212,.15); z-index:50; max-height:280px; overflow-y:auto; margin-top:4px; display:none; }
    #neoResults a { display:flex; align-items:center; gap:10px; padding:10px 14px; text-decoration:none; color:#1e293b; border-bottom:1px solid #f1f5f9; }
    #neoResults a:hover { background:#f0fdff; }

    /* table recents */
    .neo-table { width:100%; border-collapse:collapse; }
    .neo-table th { background:#f0fdff; color:#0e7490; font-size:.68rem; text-transform:uppercase; letter-spacing:.6px; font-weight:800; padding:10px 18px; text-align:left; }
    .neo-table td { padding:11px 18px; border-bottom:1px solid #f1f5f9; font-size:.83rem; }
    .neo-table tr:hover { background:#fafdfe; }
</style>

<main>
    <div class="neo-topbar">
        <div>
            <div class="neo-title"><i class="bi bi-heart-pulse-fill"></i> Unité de Néonatologie</div>
            <div class="neo-subtitle">Soins du nouveau-né &bull; HSJM</div>
        </div>
        <div class="d-flex gap-2 align-items-center flex-wrap">
            <!-- Accès rapides -->
            <button type="button" class="neo-btn neo-btn-light" onclick="neoFocusRecherche('examen')"
                    title="Rechercher un nouveau-né et démarrer un examen">
                <i class="bi bi-clipboard2-plus-fill"></i> <span class="neo-btn-txt">Nouvel examen</span>
            </button>
            <button type="button" class="neo-btn neo-btn-light" onclick="neoFocusRecherche('postnatale')"
                    style="background:rgba(240,253,244,.25);border-color:rgba(187,247,208,.6);"
                    title="Check-up de sortie de maternité">
                <i class="bi bi-clipboard2-check-fill"></i> <span class="neo-btn-txt">Post-natale</span>
            </button>
            <button type="button" class="neo-btn neo-btn-light" onclick="document.querySelector('.neo-plan-grid')?.closest('.neo-panel')?.scrollIntoView({behavior:'smooth'})"
                    title="Voir le plan d'occupation (berceaux & couveuses)">
                <i class="bi bi-grid-3x3-gap-fill"></i> <span class="neo-btn-txt">Berceaux</span>
            </button>
            <a href="<?= BASE_URL ?>pmi" class="neo-btn neo-btn-light" title="Protection Maternelle et Infantile">
                <i class="bi bi-people-fill"></i> <span class="neo-btn-txt">PMI</span>
            </a>
            <button type="button" class="neo-btn"
                    style="background:#065f46;color:#fff;border-color:#047857;"
                    data-bs-toggle="modal" data-bs-target="#modalCreerHospNeo"
                    title="Créer un nouveau patient et l'hospitaliser directement">
                <i class="bi bi-person-plus-fill"></i> <span class="neo-btn-txt">Créer &amp; Hospitaliser</span>
            </button>
            <a href="<?= BASE_URL ?>dashboard" class="neo-btn neo-btn-light"><i class="bi bi-arrow-left"></i> Retour</a>

            <!-- Séparateur -->
            <div style="width:1px;height:30px;background:rgba(255,255,255,.25);margin:0 4px;"></div>

            <!-- Utilisateur connecté -->
            <?php
            $uPrenom   = $_SESSION['user_prenom'] ?? '';
            $uNom      = $_SESSION['user_nom']    ?? '';
            $uRole     = strtoupper($_SESSION['user_role'] ?? $_SESSION['role'] ?? '');
            $uInitials = strtoupper(mb_substr($uPrenom,0,1) . mb_substr($uNom,0,1)) ?: '?';
            $roleLabels = [
                'INFIRMIER'            => 'Infirmier(e)',
                'MAJOR_INFIRMIER'      => 'Major infirmier',
                'INFIRMIER_CONSULTANT' => 'Inf. consultant',
                'INFIRMIER_ANESTHESISTE'=>'IADE',
                'MEDECIN'              => 'Médecin',
                'PEDIATRE'             => 'Pédiatre',
                'SAGE_FEMME'           => 'Sage-femme',
                'ADMIN'                => 'Administrateur',
                'ADMINISTRATEUR'       => 'Administrateur',
            ];
            $uRoleLabel = $roleLabels[$uRole] ?? ucfirst(strtolower(str_replace('_',' ',$uRole)));
            ?>
            <div class="neo-user-chip" title="Connecté en tant que <?= htmlspecialchars($uPrenom.' '.$uNom) ?>">
                <div class="neo-user-av"><?= $uInitials ?></div>
                <div class="neo-user-txt">
                    <div class="neo-user-name"><?= htmlspecialchars($uPrenom.' '.strtoupper($uNom)) ?></div>
                    <div class="neo-user-role"><?= htmlspecialchars($uRoleLabel) ?></div>
                </div>
            </div>

            <!-- Profil & Déconnexion -->
            <a href="<?= BASE_URL ?>logout" class="neo-btn"
               style="background:rgba(239,68,68,.18);color:#fecaca;border:1px solid rgba(239,68,68,.4);"
               title="Déconnexion"
               onclick="return confirm('Se déconnecter ?')">
                <i class="bi bi-box-arrow-right"></i>
            </a>
        </div>
    </div>

    <!-- Toast de bienvenue (affiché une seule fois par session) -->
    <?php
    $welcomeKey  = 'neo_welcome_shown_' . ($_SESSION['user_id'] ?? 0);
    $showWelcome = empty($_SESSION[$welcomeKey]);
    if ($showWelcome) $_SESSION[$welcomeKey] = true;
    ?>
    <div id="neoWelcomeToast" style="display:none;position:fixed;top:80px;right:24px;z-index:9999;
         background:linear-gradient(135deg,#0e7490,#06b6d4);color:#fff;border-radius:16px;
         padding:16px 22px;box-shadow:0 8px 32px rgba(6,182,212,.35);min-width:280px;max-width:360px;
         animation:neoSlideIn .4s ease;">
        <div style="display:flex;align-items:center;gap:12px;">
            <div style="width:42px;height:42px;border-radius:12px;background:rgba(255,255,255,.2);
                 display:flex;align-items:center;justify-content:center;font-size:1.4rem;flex-shrink:0;">
                🍼
            </div>
            <div>
                <div style="font-weight:800;font-size:.9rem;">Bonjour, <?= htmlspecialchars($uPrenom) ?> !</div>
                <div style="font-size:.75rem;opacity:.85;margin-top:2px;">
                    <?= htmlspecialchars($uRoleLabel) ?> · Unité de Néonatologie
                </div>
                <div style="font-size:.72rem;opacity:.7;margin-top:4px;">
                    <?= date('l d F Y', time()) ?>
                </div>
            </div>
            <button onclick="document.getElementById('neoWelcomeToast').style.display='none'"
                    style="background:none;border:none;color:#fff;font-size:1.1rem;opacity:.7;cursor:pointer;margin-left:auto;padding:0;">✕</button>
        </div>
    </div>
    <style>
    @keyframes neoSlideIn { from { opacity:0; transform:translateX(30px); } to { opacity:1; transform:none; } }
    .neo-user-chip {
        display:flex; align-items:center; gap:9px;
        background:rgba(255,255,255,.15); border:1px solid rgba(255,255,255,.3);
        border-radius:12px; padding:6px 12px 6px 7px; cursor:default;
    }
    .neo-user-av {
        width:32px; height:32px; border-radius:9px; background:rgba(255,255,255,.3);
        display:flex; align-items:center; justify-content:center;
        font-weight:900; font-size:.8rem; color:#fff; flex-shrink:0;
    }
    .neo-user-name { font-size:.78rem; font-weight:800; color:#fff; line-height:1.1; }
    .neo-user-role { font-size:.66rem; color:rgba(255,255,255,.75); font-weight:600; }
    </style>
    <?php if ($showWelcome): ?>
    <script>
    document.addEventListener('DOMContentLoaded', function(){
        const t = document.getElementById('neoWelcomeToast');
        if(t){ t.style.display='block'; setTimeout(()=>{ t.style.opacity='0'; t.style.transition='opacity .5s'; setTimeout(()=>t.style.display='none',500); }, 5000); }
    });
    </script>
    <?php endif; ?>
    <style>
        @media (max-width:900px) { .neo-btn-txt { display:none; } }
    </style>
    <script>
    /* Accès rapide : scrolle vers la recherche, la focalise et adapte la destination des résultats */
    let neoModeRecherche = 'examen'; // 'examen' | 'postnatale'
    function neoFocusRecherche(mode) {
        neoModeRecherche = mode || 'examen';
        const inp = document.getElementById('neoSearch');
        if (!inp) return;
        inp.closest('.neo-panel')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        setTimeout(() => inp.focus(), 350);
        inp.placeholder = neoModeRecherche === 'postnatale'
            ? 'Rechercher le nouveau-né pour sa consultation post-natale…'
            : 'Rechercher un nouveau-né (nom, n° dossier)…';
    }
    </script>

    <div class="neo-main">

        <!-- KPI -->
        <div class="neo-kpi-grid">
            <div class="neo-kpi k-cyan">
                <div class="neo-kpi-val"><?= (int)$kpi['hospitalises'] ?></div>
                <div class="neo-kpi-lbl">Nouveau-nés suivis</div>
                <i class="bi bi-clipboard2-heart neo-kpi-ic"></i>
            </div>
            <div class="neo-kpi k-green">
                <div class="neo-kpi-val"><?= (int)$kpi['examens_jour'] ?></div>
                <div class="neo-kpi-lbl">Examens aujourd'hui</div>
                <i class="bi bi-check2-circle neo-kpi-ic"></i>
            </div>
            <div class="neo-kpi k-blue">
                <div class="neo-kpi-val"><?= (int)$kpi['examens_total'] ?></div>
                <div class="neo-kpi-lbl">Examens au total</div>
                <i class="bi bi-journal-medical neo-kpi-ic"></i>
            </div>
            <div class="neo-kpi k-amber">
                <div class="neo-kpi-val"><?= (int)$kpi['alertes'] ?></div>
                <div class="neo-kpi-lbl">Alertes constantes</div>
                <i class="bi bi-exclamation-triangle neo-kpi-ic"></i>
            </div>
        </div>

        <!-- ══ PLAN D'OCCUPATION NÉONATOLOGIE ══ -->
        <?php $plan_occupation = $plan_occupation ?? []; ?>
        <?php if (!empty($plan_occupation)): ?>
        <style>
            .neo-plan-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(260px,1fr)); gap:14px; padding:18px 22px; }
            .neo-salle { border:1.5px solid #e0f0f3; border-radius:14px; overflow:hidden; background:#fbfeff; }
            .neo-salle-head { padding:10px 14px; font-size:.74rem; font-weight:800; text-transform:uppercase; letter-spacing:.4px; display:flex; align-items:center; justify-content:space-between; gap:8px; }
            .neo-salle-head .occ { font-size:.68rem; font-weight:700; background:rgba(255,255,255,.6); padding:2px 9px; border-radius:20px; }
            .neo-berceaux { display:flex; flex-wrap:wrap; gap:7px; padding:12px 14px; }
            .neo-berceau {
                display:flex; flex-direction:column; align-items:center; justify-content:center; gap:2px;
                min-width:64px; padding:7px 9px; border-radius:10px; font-size:.66rem; font-weight:700;
                border:1.5px solid; text-decoration:none; transition:transform .12s; cursor:default;
            }
            .neo-berceau i { font-size:1rem; }
            .neo-berceau.libre  { background:#f0fdf4; border-color:#bbf7d0; color:#15803d; }
            .neo-berceau.occupe { background:#fff7ed; border-color:#fdba74; color:#c2410c; cursor:pointer; }
            .neo-berceau.occupe:hover { transform:translateY(-2px); }
            .neo-berceau .occ-nom { font-size:.6rem; font-weight:800; max-width:80px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        </style>
        <div class="neo-panel">
            <div class="neo-panel-head">
                <span class="neo-panel-title"><i class="bi bi-grid-3x3-gap"></i> Plan d'occupation</span>
                <?php
                    $totLits = 0; $totOcc = 0;
                    foreach ($plan_occupation as $lits) {
                        foreach ($lits as $l) {
                            $totLits++;
                            $totOcc += count($l['occupants'] ?? (empty($l['patient_id']) ? [] : [1]));
                        }
                    }
                ?>
                <span style="font-size:.75rem;color:#94a3b8;font-weight:600;"><?= $totOcc ?> / <?= $totLits ?> occupé(s)</span>
            </div>
            <div class="neo-plan-grid">
                <?php
                $salleStyles = [
                    'Salle des berceaux A'       => ['#dbeafe', '#1d4ed8', 'bi-moon-stars'],
                    'Salle des berceaux B'       => ['#ede9fe', '#5b21b6', 'bi-moon-stars'],
                    'Salle Urgence Néonatologie' => ['#fee2e2', '#b91c1c', 'bi-exclamation-triangle-fill'],
                    'Salle des couveuses'        => ['#ffedd5', '#c2410c', 'bi-thermometer-sun'],
                ];
                foreach ($plan_occupation as $nomSalle => $lits):
                    [$bgS, $colS, $icoS] = $salleStyles[$nomSalle] ?? ['#f1f5f9', '#334155', 'bi-door-open'];
                    $occ = 0;
                    foreach ($lits as $l) { $occ += count($l['occupants'] ?? (empty($l['patient_id']) ? [] : [1])); }
                ?>
                <div class="neo-salle">
                    <div class="neo-salle-head" style="background:<?= $bgS ?>;color:<?= $colS ?>;">
                        <span><i class="bi <?= $icoS ?> me-1"></i><?= htmlspecialchars($nomSalle) ?></span>
                        <span class="occ"><?= $occ ?>/<?= count($lits) ?></span>
                    </div>
                    <div class="neo-berceaux">
                        <?php foreach ($lits as $l):
                            $estCouveuse = stripos($l['nom_lit'], 'couveuse') !== false;
                            $occupants   = $l['occupants'] ?? [];
                            $nbOcc       = count($occupants);
                            $libre       = $nbOcc === 0;
                            $court       = trim(str_ireplace(['Berceau ', 'Couveuse '], '', $l['nom_lit']));
                        ?>
                        <?php if ($libre): ?>
                        <div class="neo-berceau libre" title="<?= htmlspecialchars($l['nom_lit']) ?> — libre">
                            <i class="bi <?= $estCouveuse ? 'bi-box' : 'bi-basket2' ?>"></i>
                            <span><?= htmlspecialchars($court) ?></span>
                        </div>
                        <?php elseif ($estCouveuse): ?>
                        <div class="neo-berceau occupe" style="min-width:82px;align-items:flex-start;cursor:default;"
                             title="<?= htmlspecialchars($l['nom_lit']) ?> — <?= $nbOcc ?> bébé(s)">
                            <div style="display:flex;align-items:center;justify-content:space-between;width:100%;gap:3px;">
                                <span style="display:flex;align-items:center;gap:4px;">
                                    <i class="bi bi-box-fill"></i>
                                    <span><?= htmlspecialchars($court) ?></span>
                                </span>
                                <span style="font-size:.58rem;background:rgba(194,65,12,.15);border-radius:9px;padding:1px 6px;font-weight:800;"><?= $nbOcc ?>/3</span>
                            </div>
                            <?php foreach ($occupants as $occ): ?>
                            <a href="<?= BASE_URL ?>patients/dossier/<?= (int)$occ['patient_id'] ?>"
                               style="font-size:.59rem;font-weight:800;max-width:100%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:inherit;text-decoration:none;margin-top:2px;line-height:1.1;">
                                <?= htmlspecialchars(strtoupper($occ['occ_nom'] ?? '')) ?>
                            </a>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <div class="neo-berceau occupe" style="min-width:82px;align-items:flex-start;cursor:default;"
                             title="<?= htmlspecialchars($l['nom_lit']) ?> — <?= $nbOcc ?> bébé(s)">
                            <div style="display:flex;align-items:center;justify-content:space-between;width:100%;gap:3px;">
                                <span style="display:flex;align-items:center;gap:4px;">
                                    <i class="bi bi-basket2-fill"></i>
                                    <span><?= htmlspecialchars($court) ?></span>
                                </span>
                                <span style="font-size:.58rem;background:rgba(194,65,12,.15);border-radius:9px;padding:1px 6px;font-weight:800;"><?= $nbOcc ?>/3</span>
                            </div>
                            <?php foreach ($occupants as $occ): ?>
                            <a href="<?= BASE_URL ?>patients/dossier/<?= (int)$occ['patient_id'] ?>"
                               style="font-size:.59rem;font-weight:800;max-width:100%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:inherit;text-decoration:none;margin-top:2px;line-height:1.1;">
                                <?= htmlspecialchars(strtoupper($occ['occ_nom'] ?? '')) ?>
                            </a>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Recherche → démarrer un examen -->
        <div class="neo-panel">
            <div class="neo-panel-head">
                <span class="neo-panel-title"><i class="bi bi-search"></i> Nouvel examen néonatal</span>
            </div>
            <div style="padding:18px 22px;">
                <div class="neo-search-wrap">
                    <input type="text" id="neoSearch" class="form-control form-control-lg rounded-3"
                           placeholder="Rechercher un nouveau-né (nom, n° dossier)…" autocomplete="off"
                           style="border:1.5px solid #a5f3fc;">
                    <div id="neoResults"></div>
                </div>
                <small class="text-muted d-block mt-2"><i class="bi bi-info-circle me-1"></i>Tapez au moins 2 caractères pour rechercher le nouveau-né, puis cliquez pour lancer l'examen.</small>
            </div>
        </div>

        <!-- Nouveau-nés hospitalisés -->
        <div class="neo-panel">
            <div class="neo-panel-head">
                <span class="neo-panel-title"><i class="bi bi-clipboard2-pulse"></i> Nouveau-nés hospitalisés</span>
                <span style="font-size:.75rem;color:#94a3b8;font-weight:600;"><?= count($neonates) ?> lit(s) occupé(s)</span>
            </div>
            <?php if (empty($neonates)): ?>
                <div class="neo-empty">
                    <i class="bi bi-clipboard2-x"></i>
                    Aucun nouveau-né hospitalisé en néonatologie actuellement.
                </div>
            <?php else: ?>
                <div class="neo-cards">
                    <?php foreach ($neonates as $n):
                        $sexe = strtoupper($n['sexe'] ?? '');
                        $av   = $sexe === 'F' ? 'f' : 'g';
                        $ini  = strtoupper(mb_substr($n['prenom'] ?? '', 0, 1) . mb_substr($n['nom'] ?? '', 0, 1));
                        $spo2 = $n['dernier_spo2']; $temp = $n['derniere_temp']; $fc = $n['derniere_fc'];
                        $poids = $n['dernier_poids'];
                        $alerte = ($spo2 !== null && $spo2 < 90) || ($temp !== null && ($temp < 36 || $temp > 38));
                    ?>
                    <div class="neo-card <?= $alerte ? 'alerte' : '' ?>">
                        <?php if ($alerte): ?><span class="alerte-badge"><i class="bi bi-exclamation-triangle-fill"></i> Alerte</span><?php endif; ?>
                        <div class="neo-card-head">
                            <div class="neo-avatar <?= $av ?>"><i class="bi bi-<?= $av==='f'?'gender-female':'gender-male' ?>"></i></div>
                            <div>
                                <div class="neo-name"><?= htmlspecialchars(strtoupper($n['nom']).' '.$n['prenom']) ?></div>
                                <div class="neo-meta"><?= htmlspecialchars($n['dossier_numero'] ?? '') ?> · <?= NeonatologyController::ageNeonat($n['date_naissance']) ?></div>
                            </div>
                        </div>
                        <div class="neo-vitals">
                            <div class="neo-vital"><div class="v"><?= $poids !== null ? (int)$poids : '—' ?></div><div class="l">Poids g</div></div>
                            <div class="neo-vital"><div class="v <?= ($temp!==null && ($temp<36||$temp>38))?'warn':'' ?>"><?= $temp !== null ? $temp : '—' ?></div><div class="l">T° °C</div></div>
                            <div class="neo-vital"><div class="v"><?= $fc !== null ? (int)$fc : '—' ?></div><div class="l">FC bpm</div></div>
                            <div class="neo-vital"><div class="v <?= ($spo2!==null && $spo2<90)?'warn':'' ?>"><?= $spo2 !== null ? (int)$spo2 : '—' ?></div><div class="l">SpO2 %</div></div>
                        </div>
                        <div class="neo-loc">
                            <i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars(($n['nom_chambre'] ?? 'Chambre ?').' · '.($n['nom_lit'] ?? 'Lit ?')) ?>
                            <?php if (!empty($n['dernier_exam'])): ?>
                            <br><i class="bi bi-clock-history me-1"></i>Dernier examen : <?= date('d/m H:i', strtotime($n['dernier_exam'])) ?>
                            <?php endif; ?>
                        </div>
                        <div class="neo-card-actions">
                            <a href="<?= BASE_URL ?>neonatologie/examen/<?= (int)$n['id'] ?>" class="neo-act neo-act-exam" title="Examen néonatal">
                                <i class="bi bi-clipboard2-plus-fill"></i>Examiner
                            </a>
                            <a href="<?= BASE_URL ?>neonatologie/observation/<?= (int)$n['id'] ?>" class="neo-act" title="Observation médicale"
                               style="background:#eff6ff;color:#1d4ed8;border-color:#bfdbfe;">
                                <i class="bi bi-journal-medical"></i>Observer
                            </a>
                            <a href="<?= BASE_URL ?>neonatologie/gavage/<?= (int)$n['id'] ?>" class="neo-act" title="Fiche de gavage"
                               style="background:#fffbeb;color:#b45309;border-color:#fde68a;">
                                <i class="bi bi-droplet-fill"></i>Gavage
                            </a>
                            <a href="<?= BASE_URL ?>neonatologie/post-natale/<?= (int)$n['id'] ?>" class="neo-act" title="Consultation post-natale"
                               style="background:#f0fdf4;color:#15803d;border-color:#bbf7d0;">
                                <i class="bi bi-clipboard2-check-fill"></i>Post-nat.
                            </a>
                            <a href="<?= BASE_URL ?>neonatologie/suivi/<?= (int)$n['id'] ?>" class="neo-act" title="Suivi complet"
                               style="background:#f5f3ff;color:#6d28d9;border-color:#ddd6fe;">
                                <i class="bi bi-activity"></i>Suivi
                            </a>
                            <a href="<?= BASE_URL ?>patients/dossier/<?= (int)$n['id'] ?>" class="neo-act neo-act-file" title="Dossier patient">
                                <i class="bi bi-folder2-open"></i>Dossier
                            </a>
                            <button onclick="neoTransferer(<?= (int)$n['hosp_id'] ?>,'<?= htmlspecialchars(addslashes(strtoupper($n['nom']).' '.($n['prenom']??'')), ENT_QUOTES) ?>')"
                                    class="neo-act" title="Transférer vers un autre berceau / couveuse"
                                    style="background:#eff6ff;color:#1d4ed8;border-color:#bfdbfe;justify-content:center;gap:6px;cursor:pointer;border-radius:10px;">
                                <i class="bi bi-arrow-left-right"></i>Transférer
                            </button>
                            <button onclick="neoLiberer(<?= (int)$n['hosp_id'] ?>,'<?= htmlspecialchars(addslashes(strtoupper($n['nom']).' '.($n['prenom']??'')), ENT_QUOTES) ?>')"
                                    class="neo-act" title="Libérer le berceau / la couveuse"
                                    style="background:#fef2f2;color:#b91c1c;border-color:#fecaca;justify-content:center;gap:6px;cursor:pointer;border-radius:10px;">
                                <i class="bi bi-door-open-fill"></i>Libérer
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Examens récents -->
        <div class="neo-panel">
            <div class="neo-panel-head">
                <span class="neo-panel-title"><i class="bi bi-journal-text"></i> Examens néonatals récents</span>
            </div>
            <?php if (empty($examensRecents)): ?>
                <div class="neo-empty"><i class="bi bi-journal-x"></i>Aucun examen enregistré.</div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="neo-table">
                    <thead><tr><th>Date</th><th>Nouveau-né</th><th>Poids</th><th>Diagnostic</th><th>Médecin</th><th></th></tr></thead>
                    <tbody>
                        <?php foreach ($examensRecents as $e): ?>
                        <tr>
                            <td><?= date('d/m/Y H:i', strtotime($e['date_consultation'])) ?></td>
                            <td><strong><?= htmlspecialchars(strtoupper($e['nom']).' '.$e['prenom']) ?></strong><br><small class="text-muted"><?= htmlspecialchars($e['dossier_numero'] ?? '') ?></small></td>
                            <td><?= $e['poids_actuel'] !== null ? (int)$e['poids_actuel'].' g' : '—' ?></td>
                            <td style="max-width:260px;"><small><?= htmlspecialchars(mb_substr($e['diagnostic'] ?? '—', 0, 70)) ?></small></td>
                            <td><small><?= htmlspecialchars(trim(($e['medecin_prenom'] ?? '').' '.($e['medecin_nom'] ?? '')) ?: '—') ?></small></td>
                            <td><a href="<?= BASE_URL ?>neonatologie/voir/<?= (int)$e['id'] ?>" class="neo-act neo-act-file" style="padding:5px 12px;"><i class="bi bi-eye"></i> Voir</a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<!-- ══ MODAL TRANSFERT ══ -->
<div id="neoTransferModal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(15,23,42,.55);backdrop-filter:blur(4px);align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:18px;padding:28px 30px;width:min(420px,94vw);box-shadow:0 24px 60px rgba(0,0,0,.22);">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:18px;">
            <div style="width:38px;height:38px;border-radius:50%;background:#dbeafe;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="bi bi-arrow-left-right" style="color:#1d4ed8;font-size:1rem;"></i>
            </div>
            <div>
                <div style="font-weight:800;font-size:.95rem;color:#0f172a;">Transférer le nouveau-né</div>
                <div id="neoTransferNom" style="font-size:.78rem;color:#64748b;font-weight:600;"></div>
            </div>
        </div>
        <label style="font-size:.8rem;font-weight:700;color:#374151;display:block;margin-bottom:6px;">
            <i class="bi bi-hospital me-1"></i>Berceau / Couveuse de destination
        </label>
        <select id="neoTransferLitId" style="width:100%;padding:10px 14px;border:2px solid #e2e8f0;border-radius:10px;font-size:.88rem;color:#0f172a;outline:none;margin-bottom:18px;">
            <option value="">Chargement…</option>
        </select>
        <div style="display:flex;gap:10px;">
            <button onclick="document.getElementById('neoTransferModal').style.display='none'"
                    style="flex:1;padding:10px;border:2px solid #e2e8f0;border-radius:10px;background:#fff;color:#64748b;font-weight:700;font-size:.85rem;cursor:pointer;">
                Annuler
            </button>
            <button onclick="neoTransfererConfirmer()"
                    style="flex:2;padding:10px;border:none;border-radius:10px;background:#1d4ed8;color:#fff;font-weight:700;font-size:.85rem;cursor:pointer;">
                <i class="bi bi-check-lg me-1"></i>Confirmer le transfert
            </button>
        </div>
    </div>
</div>

<script>
const NEO_BASE    = '<?= BASE_URL ?>';
const NEO_CSRF    = '<?= CsrfService::getToken() ?>';
const NEO_SVC_ID  = <?= (int)($neonatId ?? 0) ?>;
let   _transferHospId = null;
let neoTimer = null;

function neoTransferer(hospId, nomBebe) {
    _transferHospId = hospId;
    document.getElementById('neoTransferNom').textContent = nomBebe;
    const sel = document.getElementById('neoTransferLitId');
    sel.innerHTML = '<option value="">Chargement…</option>';
    sel.disabled = true;
    fetch(NEO_BASE + 'hospitalisation/lits-disponibles?service_id=' + NEO_SVC_ID)
        .then(r => r.json())
        .then(lits => {
            sel.innerHTML = '<option value="">— Choisir un berceau / couveuse —</option>';
            if (Array.isArray(lits) && lits.length) {
                lits.forEach(l => {
                    const label = l.nom_chambre ? `${l.nom_lit}  (${l.nom_chambre})` : l.nom_lit;
                    sel.innerHTML += `<option value="${l.id}">${label.replace(/&/g,'&amp;').replace(/</g,'&lt;')}</option>`;
                });
            } else {
                sel.innerHTML += '<option value="" disabled>⚠ Aucun lit disponible</option>';
            }
            sel.disabled = false;
        })
        .catch(() => { sel.innerHTML = '<option value="">Erreur de chargement</option>'; sel.disabled = false; });
    document.getElementById('neoTransferModal').style.display = 'flex';
}

function neoTransfererConfirmer() {
    const litId = document.getElementById('neoTransferLitId').value;
    if (!litId) { alert('Veuillez choisir un berceau ou une couveuse de destination.'); return; }
    fetch(NEO_BASE + 'neonatologie/transferer', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: '_csrf_token=' + encodeURIComponent(NEO_CSRF)
            + '&hosp_id=' + _transferHospId
            + '&nouveau_lit_id=' + litId
    })
    .then(r => r.json())
    .then(d => {
        document.getElementById('neoTransferModal').style.display = 'none';
        if (d.ok) { location.reload(); }
        else { alert('Erreur : ' + (d.error || 'Impossible de transférer.')); }
    })
    .catch(() => alert('Erreur réseau. Veuillez réessayer.'));
}

function neoLiberer(hospId, nomBebe) {
    if (!confirm('Libérer le berceau / la couveuse de ' + nomBebe + ' ?\nCette action marque la sortie du nouveau-né.')) return;
    fetch(NEO_BASE + 'neonatologie/liberer', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: '_csrf_token=' + encodeURIComponent(NEO_CSRF) + '&hosp_id=' + hospId
    })
    .then(r => r.json())
    .then(d => {
        if (d.ok) { location.reload(); }
        else { alert('Erreur : ' + (d.error || 'Impossible de libérer le berceau.')); }
    })
    .catch(() => alert('Erreur réseau. Veuillez réessayer.'));
}

const neoInput = document.getElementById('neoSearch');
const neoBox   = document.getElementById('neoResults');

neoInput.addEventListener('input', function () {
    clearTimeout(neoTimer);
    const q = this.value.trim();
    if (q.length < 2) { neoBox.style.display = 'none'; return; }
    neoTimer = setTimeout(() => {
        fetch(NEO_BASE + 'consultation/search-patients?q=' + encodeURIComponent(q))
            .then(r => r.json())
            .then(data => {
                if (!data.length) {
                    neoBox.innerHTML = '<div style="padding:14px;color:#94a3b8;font-size:.85rem;text-align:center;">Aucun patient trouvé</div>';
                    neoBox.style.display = 'block'; return;
                }
                const modePN = (typeof neoModeRecherche !== 'undefined' && neoModeRecherche === 'postnatale');
                neoBox.innerHTML = data.map(p => {
                    const ini  = ((p.nom||'?')[0] + (p.prenom||'')[0]).toUpperCase();
                    const dest = modePN ? `${NEO_BASE}neonatologie/post-natale/${p.id}` : `${NEO_BASE}neonatologie/examen/${p.id}`;
                    const lblP = modePN
                        ? `<span style="color:#16a34a;font-weight:700;font-size:.78rem;"><i class="bi bi-clipboard2-check"></i> Post-natale</span>`
                        : `<span style="color:#06b6d4;font-weight:700;font-size:.78rem;"><i class="bi bi-clipboard2-plus"></i> Examiner</span>`;
                    const lblS = modePN
                        ? `<span onclick="event.preventDefault();event.stopPropagation();window.location='${NEO_BASE}neonatologie/examen/${p.id}';"
                              style="color:#06b6d4;font-weight:700;font-size:.78rem;background:#ecfeff;border:1px solid #a5f3fc;border-radius:18px;padding:4px 11px;white-space:nowrap;">
                              <i class="bi bi-clipboard2-plus"></i> Examen</span>`
                        : `<span onclick="event.preventDefault();event.stopPropagation();window.location='${NEO_BASE}neonatologie/post-natale/${p.id}';"
                              style="color:#16a34a;font-weight:700;font-size:.78rem;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:18px;padding:4px 11px;white-space:nowrap;"
                              title="Check-up de sortie de maternité">
                              <i class="bi bi-clipboard2-check"></i> Post-natale</span>`;
                    return `<a href="${dest}">
                        <span style="width:34px;height:34px;border-radius:10px;background:linear-gradient(135deg,#06b6d4,#22d3ee);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.75rem;">${ini}</span>
                        <span style="flex:1;"><strong>${(p.nom||'').toUpperCase()} ${p.prenom||''}</strong><br><small style="color:#94a3b8;">${p.dossier_numero||''} · ${p.date_naissance||''}</small></span>
                        ${lblP}
                        ${lblS}
                    </a>`;
                }).join('');
                neoBox.style.display = 'block';
            })
            .catch(() => { neoBox.style.display = 'none'; });
    }, 250);
});
document.addEventListener('click', e => { if (!e.target.closest('.neo-search-wrap')) neoBox.style.display = 'none'; });
</script>

<!-- ═══════════════════════════════════════════════════════════════
     MODAL — CRÉER & HOSPITALISER (Néonatologie)
════════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalCreerHospNeo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius:18px;overflow:hidden;max-height:90vh;display:flex;flex-direction:column;">

            <div class="modal-header border-0 p-4 flex-shrink-0"
                 style="background:linear-gradient(135deg,#065f46,#059669);">
                <div>
                    <h5 class="modal-title fw-bold text-white mb-0">
                        <i class="bi bi-person-hearts me-2"></i>Admission Nouveau-né
                    </h5>
                    <div class="text-white opacity-75 small mt-1">
                        Création du dossier et installation en unité néonatale
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <form id="formCreerHospNeo" novalidate style="display:flex;flex-direction:column;flex:1 1 auto;min-height:0;">
            <div class="modal-body p-0" style="overflow-y:auto;flex:1 1 auto;">

                <!-- A : Identité du nouveau-né -->
                <div class="px-4 pt-4 pb-3" style="border-bottom:1px solid #e2e8f0;">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center"
                             style="width:28px;height:28px;background:#065f46;flex-shrink:0;">
                            <span class="text-white fw-bold" style="font-size:.7rem;">A</span>
                        </div>
                        <span class="fw-bold text-uppercase" style="font-size:.72rem;letter-spacing:.6px;color:#065f46;">Identité du nouveau-né</span>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-5">
                            <label class="form-label fw-semibold small text-muted mb-1">
                                Nom <span class="text-danger">*</span>
                                <span class="fw-normal text-muted">(ex : NN de Mme KAMDEM)</span>
                            </label>
                            <input type="text" name="nom" class="form-control border-2 text-uppercase"
                                   style="border-radius:10px;" placeholder="NN de Mme …" autocomplete="off">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small text-muted mb-1">Prénom <span class="fw-normal text-muted">(optionnel)</span></label>
                            <input type="text" name="prenom" class="form-control border-2"
                                   style="border-radius:10px;" placeholder="—" autocomplete="off">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small text-muted mb-1">Sexe <span class="text-danger">*</span></label>
                            <select name="sexe" class="form-select border-2" style="border-radius:10px;">
                                <option value="">— Choisir —</option>
                                <option value="M">Masculin</option>
                                <option value="F">Féminin</option>
                                <option value="I">Indéterminé</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-muted mb-1">
                                <i class="bi bi-clock me-1"></i>Date &amp; heure de naissance <span class="text-danger">*</span>
                            </label>
                            <input type="datetime-local" name="date_naissance" id="neoNeDateNaissance"
                                   class="form-control border-2" style="border-radius:10px;"
                                   max="<?= date('Y-m-d\TH:i') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-muted mb-1">Type de prise en charge</label>
                            <select name="type_client" class="form-select border-2" style="border-radius:10px;">
                                <option value="PAYANT_COMPTANT" selected>Payant comptant</option>
                                <option value="ASSURANCE">Assurance</option>
                                <option value="BON_PRISE_EN_CHARGE">Bon de prise en charge</option>
                                <option value="FAMILLE_PHP">Famille PHP</option>
                                <option value="AGENTS_PHP">Agent PHP</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- B : Données de naissance -->
                <div class="px-4 pt-3 pb-3" style="border-bottom:1px solid #e2e8f0;">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center"
                             style="width:28px;height:28px;background:#065f46;flex-shrink:0;">
                            <span class="text-white fw-bold" style="font-size:.7rem;">B</span>
                        </div>
                        <span class="fw-bold text-uppercase" style="font-size:.72rem;letter-spacing:.6px;color:#065f46;">Données de naissance</span>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small text-muted mb-1">Terme <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" name="terme_sa" min="22" max="44"
                                       class="form-control border-2" style="border-radius:10px 0 0 10px;" placeholder="38">
                                <span class="input-group-text border-2" style="background:#f8fafc;border-radius:0 10px 10px 0;font-size:.78rem;color:#64748b;">SA</span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small text-muted mb-1">Poids de naissance <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" name="poids_naissance" min="300" max="6000"
                                       class="form-control border-2" style="border-radius:10px 0 0 10px;" placeholder="3200">
                                <span class="input-group-text border-2" style="background:#f8fafc;border-radius:0 10px 10px 0;font-size:.78rem;color:#64748b;">g</span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small text-muted mb-1">Apgar 1 min <span class="text-danger">*</span></label>
                            <select name="apgar_1" id="apgar1Sel" class="form-select border-2" style="border-radius:10px;">
                                <option value="">—</option>
                                <?php for ($i = 0; $i <= 10; $i++): ?>
                                <option value="<?= $i ?>"><?= $i ?>/10<?= $i >= 7 ? ' ✓' : ($i >= 4 ? ' ⚠' : ' ✗') ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small text-muted mb-1">Apgar 5 min</label>
                            <select name="apgar_5" id="apgar5Sel" class="form-select border-2" style="border-radius:10px;">
                                <option value="">—</option>
                                <?php for ($i = 0; $i <= 10; $i++): ?>
                                <option value="<?= $i ?>"><?= $i ?>/10<?= $i >= 7 ? ' ✓' : ($i >= 4 ? ' ⚠' : ' ✗') ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small text-muted mb-1">Apgar 10 min</label>
                            <select name="apgar_10" id="apgar10Sel" class="form-select border-2" style="border-radius:10px;">
                                <option value="">—</option>
                                <?php for ($i = 0; $i <= 10; $i++): ?>
                                <option value="<?= $i ?>"><?= $i ?>/10<?= $i >= 7 ? ' ✓' : ($i >= 4 ? ' ⚠' : ' ✗') ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <input type="hidden" name="apgar_indisponible" id="apgarIndispo" value="0">
                            <button type="button" id="apgarIndispoBtn"
                                    onclick="toggleApgarIndispo()"
                                    class="btn btn-sm fw-semibold w-100"
                                    style="border-radius:10px;border:1.5px solid #e2e8f0;background:#f8fafc;
                                           color:#64748b;font-size:.76rem;padding:9px 10px;">
                                <i class="bi bi-slash-circle me-1"></i>Apgar Indisponible
                            </button>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-muted mb-1">Type d'accouchement <span class="text-danger">*</span></label>
                            <select name="type_accouchement" class="form-select border-2" style="border-radius:10px;">
                                <option value="">— Choisir —</option>
                                <option value="VOIE_BASSE">Voie basse (spontanée)</option>
                                <option value="VOIE_BASSE">Siège (voie basse)</option>
                                <option value="CESARIENNE">Césarienne</option>
                                <option value="INSTRUMENTAL">Forceps</option>
                                <option value="INSTRUMENTAL">Ventouse</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- C : Affectation unité néonatale -->
                <div class="px-4 pt-3 pb-3" style="border-bottom:1px solid #e2e8f0;">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center"
                             style="width:28px;height:28px;background:#065f46;flex-shrink:0;">
                            <span class="text-white fw-bold" style="font-size:.7rem;">C</span>
                        </div>
                        <span class="fw-bold text-uppercase" style="font-size:.72rem;letter-spacing:.6px;color:#065f46;">Affectation — Unité Néonatale</span>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-5">
                            <label class="form-label fw-semibold small text-muted mb-1">
                                <i class="bi bi-building me-1"></i>Service
                            </label>
                            <div class="form-control border-2 d-flex align-items-center gap-2"
                                 style="border-radius:10px;background:#f0fdf4;color:#065f46;font-weight:600;">
                                <i class="bi bi-lock-fill" style="font-size:.75rem;"></i>
                                Néonatologie
                            </div>
                            <input type="hidden" name="service_id" value="<?= (int)($neonatId ?? 0) ?>">
                        </div>
                        <div class="col-md-7">
                            <label class="form-label fw-semibold small text-muted mb-1">
                                <i class="bi bi-hospital me-1"></i>Berceau / Couveuse <span class="fw-normal text-muted">(optionnel)</span>
                            </label>
                            <select id="neoNeLitId" name="lit_id" class="form-select border-2" style="border-radius:10px;" disabled>
                                <option value="">— Chargement… —</option>
                            </select>
                            <div id="neoNeLitsLoader" class="form-text d-none">
                                <span class="spinner-border spinner-border-sm me-1 text-success"></span>Chargement…
                            </div>
                        </div>
                    </div>
                </div>

                <!-- D : Prise en charge médicale -->
                <div class="px-4 pt-3 pb-4">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center"
                             style="width:28px;height:28px;background:#065f46;flex-shrink:0;">
                            <span class="text-white fw-bold" style="font-size:.7rem;">D</span>
                        </div>
                        <span class="fw-bold text-uppercase" style="font-size:.72rem;letter-spacing:.6px;color:#065f46;">Prise en charge médicale</span>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-muted mb-1">
                                <i class="bi bi-person-badge me-1"></i>Médecin responsable
                            </label>
                            <select name="medecin_id" class="form-select border-2" style="border-radius:10px;">
                                <option value="">— Sélectionner —</option>
                                <?php foreach (($medecins ?? []) as $m): ?>
                                <option value="<?= (int)$m['id'] ?>">Dr <?= htmlspecialchars($m['nom'] . ' ' . $m['prenom']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-muted mb-1">
                                <i class="bi bi-calendar-check me-1"></i>Date d'admission <span class="text-danger">*</span>
                            </label>
                            <input type="datetime-local" id="neoNeDateAdmission" name="date_admission"
                                   class="form-control border-2" style="border-radius:10px;"
                                   value="<?= date('Y-m-d\TH:i') ?>" max="<?= date('Y-m-d\TH:i') ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold small text-muted mb-1">
                                <i class="bi bi-clipboard2-pulse me-1"></i>Motif d'admission <span class="text-danger">*</span>
                            </label>
                            <textarea name="motif" class="form-control border-2" rows="2"
                                      style="border-radius:10px;resize:vertical;"
                                      placeholder="Ex : Détresse respiratoire néonatale, prématurité 34 SA…"></textarea>
                        </div>
                    </div>
                </div>

                <!-- E : Informations des parents -->
                <div class="px-4 pt-3 pb-4" style="border-top:1px solid #e2e8f0;">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center"
                             style="width:28px;height:28px;background:#065f46;flex-shrink:0;">
                            <span class="text-white fw-bold" style="font-size:.7rem;">E</span>
                        </div>
                        <span class="fw-bold text-uppercase" style="font-size:.72rem;letter-spacing:.6px;color:#065f46;">Informations des parents</span>
                    </div>

                    <!-- Mère -->
                    <div class="mb-3 p-3 rounded-3" style="background:#f0fdf4;border:1px solid #bbf7d0;">
                        <div class="fw-bold small mb-2" style="color:#065f46;">
                            <i class="bi bi-person-heart me-1"></i>Mère
                        </div>
                        <div class="row g-2">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold small text-muted mb-1">Nom <span class="text-danger">*</span></label>
                                <input type="text" name="mere_nom" class="form-control border-2 text-uppercase"
                                       style="border-radius:8px;" placeholder="Nom de famille" autocomplete="off">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold small text-muted mb-1">Prénom <span class="text-danger">*</span></label>
                                <input type="text" name="mere_prenom" class="form-control border-2"
                                       style="border-radius:8px;" placeholder="Prénom(s)" autocomplete="off">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-semibold small text-muted mb-1">Âge</label>
                                <div class="input-group">
                                    <input type="number" name="mere_age" min="12" max="65"
                                           class="form-control border-2" style="border-radius:8px 0 0 8px;" placeholder="28">
                                    <span class="input-group-text border-2" style="background:#f8fafc;border-radius:0 8px 8px 0;font-size:.75rem;">ans</span>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-semibold small text-muted mb-1">Groupe sang.</label>
                                <select name="mere_groupe_sanguin" class="form-select border-2" style="border-radius:8px;">
                                    <option value="">—</option>
                                    <?php foreach (['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $g): ?>
                                    <option value="<?= $g ?>"><?= $g ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold small text-muted mb-1">
                                    <i class="bi bi-telephone me-1"></i>Téléphone <span class="text-danger">*</span>
                                </label>
                                <input type="tel" name="mere_telephone" class="form-control border-2"
                                       style="border-radius:8px;" placeholder="6XX XXX XXX">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold small text-muted mb-1">Profession</label>
                                <input type="text" name="mere_profession" class="form-control border-2"
                                       style="border-radius:8px;" placeholder="Ex : Enseignante">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold small text-muted mb-1">Situation matrimoniale</label>
                                <select name="mere_situation" class="form-select border-2" style="border-radius:8px;">
                                    <option value="">— Choisir —</option>
                                    <option value="marie">Mariée</option>
                                    <option value="celibataire">Célibataire</option>
                                    <option value="divorce">Divorcée</option>
                                    <option value="veuf">Veuve</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold small text-muted mb-1">
                                    <i class="bi bi-geo-alt me-1"></i>Adresse / Quartier
                                </label>
                                <input type="text" name="mere_adresse" class="form-control border-2"
                                       style="border-radius:8px;" placeholder="Quartier, ville">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold small text-muted mb-1">
                                    <i class="bi bi-clipboard2-heart me-1"></i>Antécédents médicaux maternels pertinents
                                </label>
                                <textarea name="mere_antecedents" class="form-control border-2" rows="2"
                                          style="border-radius:8px;resize:vertical;"
                                          placeholder="Ex : HTA gravidique, diabète gestationnel, VIH+, paludisme…"></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Père -->
                    <div class="p-3 rounded-3" style="background:#eff6ff;border:1px solid #bfdbfe;">
                        <div class="fw-bold small mb-2" style="color:#1d4ed8;">
                            <i class="bi bi-person me-1"></i>Père <span class="fw-normal text-muted">(optionnel)</span>
                        </div>
                        <div class="row g-2">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold small text-muted mb-1">Nom</label>
                                <input type="text" name="pere_nom" class="form-control border-2 text-uppercase"
                                       style="border-radius:8px;" placeholder="Nom de famille" autocomplete="off">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold small text-muted mb-1">Prénom</label>
                                <input type="text" name="pere_prenom" class="form-control border-2"
                                       style="border-radius:8px;" placeholder="Prénom(s)" autocomplete="off">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold small text-muted mb-1">
                                    <i class="bi bi-telephone me-1"></i>Téléphone
                                </label>
                                <input type="tel" name="pere_telephone" class="form-control border-2"
                                       style="border-radius:8px;" placeholder="6XX XXX XXX">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small text-muted mb-1">Profession</label>
                                <input type="text" name="pere_profession" class="form-control border-2"
                                       style="border-radius:8px;" placeholder="Ex : Ingénieur">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small text-muted mb-1">Groupe sanguin</label>
                                <select name="pere_groupe_sanguin" class="form-select border-2" style="border-radius:8px;">
                                    <option value="">—</option>
                                    <?php foreach (['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $g): ?>
                                    <option value="<?= $g ?>"><?= $g ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

            </div><!-- /.modal-body -->

            <div class="modal-footer border-0 px-4 pb-4 pt-2 d-flex justify-content-between align-items-center flex-shrink-0"
                 style="background:#f8fafc;">
                <div id="neoNeErreur" class="text-danger small fw-semibold d-none">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i>
                    <span id="neoNeErreurMsg"></span>
                </div>
                <div class="ms-auto d-flex gap-2">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" id="neoNeBtnSubmit"
                            class="btn btn-success btn-lg rounded-pill fw-bold shadow px-5"
                            onclick="neoNeAdmettre()">
                        <i class="bi bi-person-hearts me-1"></i>ADMETTRE
                    </button>
                </div>
            </div>
            </form>
        </div>
    </div>
</div>

<script>
(function () {
    const BURL       = '<?= BASE_URL ?>';
    const NEO_SVC_ID = <?= (int)($neonatId ?? 0) ?>;

    function neoNeChargerLits() {
        if (!NEO_SVC_ID) return;
        const sel    = document.getElementById('neoNeLitId');
        const loader = document.getElementById('neoNeLitsLoader');
        sel.disabled = true;
        loader.classList.remove('d-none');
        fetch(BURL + 'hospitalisation/lits-disponibles?service_id=' + NEO_SVC_ID)
            .then(r => r.json())
            .then(lits => {
                loader.classList.add('d-none');
                sel.innerHTML = '<option value="">— Aucun lit assigné —</option>';
                if (Array.isArray(lits) && lits.length)
                    lits.forEach(l => {
                        const label = l.nom_chambre ? `${l.nom_lit}  (${l.nom_chambre})` : l.nom_lit;
                        sel.innerHTML += `<option value="${l.id}">${label.replace(/&/g,'&amp;').replace(/</g,'&lt;')}</option>`;
                    });
                else
                    sel.innerHTML += '<option value="" disabled>⚠ Aucun berceau/couveuse disponible</option>';
                sel.disabled = false;
            })
            .catch(() => { loader.classList.add('d-none'); sel.innerHTML = '<option value="">Erreur de chargement</option>'; sel.disabled = false; });
    }

    window.neoNeAdmettre = function() {
        const form   = document.getElementById('formCreerHospNeo');
        const errDiv = document.getElementById('neoNeErreur');
        const errMsg = document.getElementById('neoNeErreurMsg');
        const btn    = document.getElementById('neoNeBtnSubmit');
        const showErr = msg => { errMsg.textContent = msg; errDiv.classList.remove('d-none'); errDiv.scrollIntoView({behavior:'smooth',block:'nearest'}); };

        const nom         = form.querySelector('[name="nom"]').value.trim();
        const sexe        = form.querySelector('[name="sexe"]').value;
        const dateNaiss   = form.querySelector('[name="date_naissance"]').value;
        const termeSA     = form.querySelector('[name="terme_sa"]').value;
        const poids       = form.querySelector('[name="poids_naissance"]').value;
        const apgar1      = form.querySelector('[name="apgar_1"]').value;
        const typeAccouch = form.querySelector('[name="type_accouchement"]').value;
        const motif       = form.querySelector('[name="motif"]').value.trim();

        const mereNom    = form.querySelector('[name="mere_nom"]').value.trim();
        const merePrenom = form.querySelector('[name="mere_prenom"]').value.trim();
        const mereTel    = form.querySelector('[name="mere_telephone"]').value.trim();

        if (!nom)         { showErr('Le nom du nouveau-né est requis.');              return; }
        if (!sexe)        { showErr('Le sexe du nouveau-né est requis.');             return; }
        if (!dateNaiss)   { showErr('La date et heure de naissance sont requises.');  return; }
        if (!termeSA)     { showErr('Le terme (SA) est requis.');                     return; }
        if (!poids)       { showErr('Le poids de naissance est requis.');             return; }
        const apgarIndispo = form.querySelector('[name="apgar_indisponible"]')?.value === '1';
        if (!apgarIndispo && apgar1 === '') { showErr('Le score Apgar à 1 minute est requis (ou cochez "Apgar Indisponible").'); return; }
        if (!typeAccouch) { showErr("Le type d'accouchement est requis.");            return; }
        if (!motif)       { showErr("Le motif d'admission est requis.");              return; }
        if (!mereNom)     { showErr('Le nom de la mère est requis.');                 return; }
        if (!merePrenom)  { showErr('Le prénom de la mère est requis.');              return; }
        if (!mereTel)     { showErr('Le téléphone de la mère est requis.');           return; }

        errDiv.classList.add('d-none');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Enregistrement…';

        fetch(BURL + 'neonatologie/admettre-nouveau-ne', { method: 'POST', body: new FormData(form) })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    bootstrap.Modal.getInstance(document.getElementById('modalCreerHospNeo')).hide();
                    const t = document.createElement('div');
                    t.className = 'position-fixed bottom-0 end-0 p-3'; t.style.zIndex = 9999;
                    t.innerHTML = `<div class="toast show align-items-center text-white bg-success border-0 rounded-3 shadow" style="min-width:320px">
                        <div class="d-flex"><div class="toast-body fw-semibold"><i class="bi bi-check-circle-fill me-2"></i>${data.message.replace(/&/g,'&amp;').replace(/</g,'&lt;')}</div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" onclick="this.closest('.toast').remove()"></button></div></div>`;
                    document.body.appendChild(t);
                    setTimeout(() => location.reload(), 1800);
                } else {
                    showErr(data.message || "Erreur lors de l'enregistrement.");
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-person-hearts me-1"></i>ADMETTRE';
                }
            })
            .catch(() => {
                showErr('Erreur réseau. Veuillez réessayer.');
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-person-hearts me-1"></i>ADMETTRE';
            });
    };

    document.addEventListener('DOMContentLoaded', function () {
        const modal = document.getElementById('modalCreerHospNeo');
        if (!modal) return;
        modal.addEventListener('show.bs.modal', function () {
            document.getElementById('formCreerHospNeo').reset();
            document.getElementById('neoNeDateAdmission').value = new Date().toISOString().slice(0, 16);
            document.getElementById('neoNeErreur').classList.add('d-none');
            const btn = document.getElementById('neoNeBtnSubmit');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-person-hearts me-1"></i>ADMETTRE';
            neoNeChargerLits();
        });
    });
})();
</script>

<script>
function toggleApgarIndispo() {
    const btn    = document.getElementById('apgarIndispoBtn');
    const hidden = document.getElementById('apgarIndispo');
    const sels   = [document.getElementById('apgar1Sel'), document.getElementById('apgar5Sel'), document.getElementById('apgar10Sel')];
    const label1 = document.querySelector('label[for="apgar1Sel"], label:has(+ select#apgar1Sel)');
    const active = hidden.value === '1';

    if (active) {
        // Désactiver le mode indisponible
        hidden.value = '0';
        sels.forEach(s => { if(s){ s.disabled = false; s.style.opacity=''; s.style.background=''; } });
        btn.style.background   = '#f8fafc';
        btn.style.borderColor  = '#e2e8f0';
        btn.style.color        = '#64748b';
        btn.innerHTML = '<i class="bi bi-slash-circle me-1"></i>Apgar Indisponible';
        // Restaurer l'étoile rouge sur Apgar 1
        const lbl = document.querySelector('#apgar1Sel')?.closest('.col-md-3')?.querySelector('label');
        if (lbl && !lbl.querySelector('.apgar-req')) {
            lbl.insertAdjacentHTML('beforeend', '<span class="apgar-req text-danger"> *</span>');
        }
    } else {
        // Activer le mode indisponible
        hidden.value = '1';
        sels.forEach(s => { if(s){ s.value=''; s.disabled = true; s.style.opacity='.45'; s.style.background='#f1f5f9'; } });
        btn.style.background   = '#fef3c7';
        btn.style.borderColor  = '#fde68a';
        btn.style.color        = '#92400e';
        btn.innerHTML = '<i class="bi bi-slash-circle-fill me-1"></i>Apgar Indisponible ✓';
        // Retirer l'étoile rouge (plus obligatoire)
        const reqSpan = document.querySelector('#apgar1Sel')?.closest('.col-md-3')?.querySelector('.apgar-req');
        if (reqSpan) reqSpan.remove();
    }
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
