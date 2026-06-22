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
$patient              = $patient ?? null;
$demandes_entrantes   = $demandes_entrantes ?? [];
$programme_operatoire = $programme_operatoire ?? [];
$age                  = $age ?? 'N/A';
$userName           = trim(($_SESSION['user_prenom'] ?? '') . ' ' . ($_SESSION['user_nom'] ?? ''));
$userRole           = $_SESSION['user_role'] ?? '';
$roleLabel = match($userRole) {
    'ANESTHESISTE'         => 'Médecin Anesthésiste',
    'INFIRMIER_ANESTHESISTE' => 'Infirmier Anesthésiste (IADE)',
    'INFIRMIER'            => 'Infirmier(e) de garde',
    'MAJOR_INFIRMIER'      => 'Major Infirmier',
    default                => $userRole
};
?>
<style>
* { box-sizing: border-box; }
body {
    background: #f5f7fb;
    font-family: 'Inter', system-ui, -apple-system, sans-serif;
    color: #1e293b;
    overflow-x: hidden;
}

/* ── Layout ── */
.anes-shell { display: flex; flex-direction: column; min-height: 100vh; padding: 0; }

/* ── Topbar ── */
.anes-topbar {
    background: #fff;
    border-bottom: 1px solid #e8edf5;
    padding: 0 32px;
    height: 64px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: sticky;
    top: 0;
    z-index: 100;
    box-shadow: 0 1px 12px rgba(0,0,0,.06);
}
.anes-brand { display: flex; align-items: center; gap: 14px; }
.anes-brand img { height: 38px; }
.anes-brand-text h6 { font-size: .85rem; font-weight: 800; margin: 0; color: #0f172a; }
.anes-brand-text small { font-size: .72rem; color: #94a3b8; }

.anes-topbar-center {
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    color: #fff;
    border-radius: 50px;
    padding: 6px 22px;
    font-size: 1.25rem;
    font-weight: 900;
    letter-spacing: 2px;
    font-variant-numeric: tabular-nums;
    min-width: 130px;
    text-align: center;
    box-shadow: 0 4px 15px rgba(99,102,241,.3);
}

.anes-topbar-right { display: flex; align-items: center; gap: 16px; }
.user-chip {
    display: flex; align-items: center; gap: 10px;
    background: #f8fafc; border: 1px solid #e2e8f0;
    border-radius: 50px; padding: 6px 16px 6px 6px;
}
.user-avatar {
    width: 34px; height: 34px; border-radius: 50%;
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-weight: 800; font-size: .8rem;
}
.user-chip-info { line-height: 1.2; }
.user-chip-name { font-size: .78rem; font-weight: 700; color: #1e293b; }
.user-chip-role { font-size: .68rem; color: #94a3b8; }
.btn-logout {
    width: 36px; height: 36px; border-radius: 50%;
    border: 1.5px solid #fee2e2; background: #fff5f5;
    color: #ef4444; display: flex; align-items: center; justify-content: center;
    cursor: pointer; transition: .15s; font-size: .95rem;
}
.btn-logout:hover { background: #ef4444; color: #fff; }

/* ── Main content ── */
.anes-body { padding: 28px 32px; flex: 1; }

/* ── Date greeting ── */
.anes-greeting { margin-bottom: 24px; }
.anes-greeting h2 { font-size: 1.3rem; font-weight: 800; margin: 0; color: #0f172a; }
.anes-greeting p { font-size: .82rem; color: #94a3b8; margin: 2px 0 0; }

/* ── Stat chips ── */
.stat-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 28px; }
.stat-chip {
    background: #fff;
    border-radius: 18px;
    padding: 20px 22px;
    display: flex; align-items: center; gap: 16px;
    box-shadow: 0 2px 12px rgba(0,0,0,.05);
    border: 1px solid #f1f5f9;
    transition: transform .2s, box-shadow .2s;
}
.stat-chip:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(0,0,0,.08); }
.stat-icon {
    width: 48px; height: 48px; border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.3rem; flex-shrink: 0;
}
.icon-purple { background: #ede9fe; color: #7c3aed; }
.icon-blue   { background: #dbeafe; color: #2563eb; }
.icon-green  { background: #dcfce7; color: #16a34a; }
.icon-amber  { background: #fef3c7; color: #d97706; }
.stat-label  { font-size: .7rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: .5px; }
.stat-value  { font-size: 1.6rem; font-weight: 900; color: #0f172a; line-height: 1; margin-top: 2px; }

/* ── Main grid ── */
.anes-grid { display: grid; grid-template-columns: 1fr 340px; gap: 20px; margin-bottom: 24px; }

/* ── Section card ── */
.sec-card {
    background: #fff;
    border-radius: 20px;
    box-shadow: 0 2px 12px rgba(0,0,0,.05);
    border: 1px solid #f1f5f9;
    overflow: hidden;
}
.sec-head {
    padding: 16px 22px;
    display: flex; align-items: center; justify-content: space-between;
    border-bottom: 1px solid #f1f5f9;
}
.sec-head-title {
    font-size: .8rem; font-weight: 800; color: #1e293b;
    text-transform: uppercase; letter-spacing: .5px;
    display: flex; align-items: center; gap: 8px;
}
.sec-dot { width: 8px; height: 8px; border-radius: 50%; }
.dot-live { background: #10b981; box-shadow: 0 0 0 3px rgba(16,185,129,.2); animation: pulse 2s infinite; }
.dot-wait { background: #f59e0b; }
.sec-body { padding: 20px 22px; }

/* ── Patient hero (si patient en cours) ── */
.patient-hero {
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #a78bfa 100%);
    border-radius: 16px;
    padding: 22px;
    color: #fff;
    position: relative;
    overflow: hidden;
    margin-bottom: 16px;
}
.patient-hero::before {
    content: '';
    position: absolute; top: -30px; right: -30px;
    width: 130px; height: 130px;
    background: rgba(255,255,255,.08);
    border-radius: 50%;
}
.patient-hero::after {
    content: '';
    position: absolute; bottom: -20px; right: 40px;
    width: 80px; height: 80px;
    background: rgba(255,255,255,.05);
    border-radius: 50%;
}
.patient-hero-name { font-size: 1.15rem; font-weight: 800; margin-bottom: 2px; }
.patient-hero-meta { font-size: .78rem; opacity: .8; }
.patient-hero-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 14px; }
.patient-hero-item { background: rgba(255,255,255,.15); border-radius: 10px; padding: 8px 12px; }
.phi-label { font-size: .65rem; font-weight: 700; opacity: .7; text-transform: uppercase; letter-spacing: .4px; }
.phi-val { font-size: .88rem; font-weight: 800; margin-top: 2px; }
.live-badge {
    display: inline-flex; align-items: center; gap: 5px;
    background: rgba(255,255,255,.2); border-radius: 50px;
    padding: 3px 10px; font-size: .7rem; font-weight: 700;
    margin-top: 10px;
}

/* ── Vitals ── */
.vitals-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.vital-card {
    border-radius: 14px;
    padding: 14px 16px;
    position: relative;
    overflow: hidden;
}
.vital-card.vc-green  { background: #f0fdf4; border: 1.5px solid #bbf7d0; }
.vital-card.vc-blue   { background: #eff6ff; border: 1.5px solid #bfdbfe; }
.vital-card.vc-amber  { background: #fffbeb; border: 1.5px solid #fde68a; }
.vital-card.vc-rose   { background: #fff1f2; border: 1.5px solid #fecdd3; }
.vital-lbl  { font-size: .67rem; font-weight: 700; text-transform: uppercase; letter-spacing: .4px; opacity: .7; }
.vital-val  { font-size: 1.9rem; font-weight: 900; line-height: 1.1; margin: 4px 0 2px; }
.vital-unit { font-size: .7rem; font-weight: 600; opacity: .6; }
.vc-green .vital-lbl, .vc-green .vital-val { color: #166534; }
.vc-blue  .vital-lbl, .vc-blue  .vital-val { color: #1d4ed8; }
.vc-amber .vital-lbl, .vc-amber .vital-val { color: #92400e; }
.vc-rose  .vital-lbl, .vc-rose  .vital-val { color: #9f1239; }

/* ── Actions rapides (droite) ── */
.action-btn {
    width: 100%; border: none; border-radius: 14px;
    padding: 14px 18px;
    font-size: .82rem; font-weight: 800;
    cursor: pointer; transition: .2s;
    display: flex; align-items: center; gap: 10px;
    margin-bottom: 10px;
    text-align: left;
}
.ab-purple { background: #ede9fe; color: #6d28d9; }
.ab-purple:hover { background: #6d28d9; color: #fff; transform: translateX(3px); }
.ab-blue   { background: #dbeafe; color: #1d4ed8; }
.ab-blue:hover   { background: #1d4ed8; color: #fff; transform: translateX(3px); }
.ab-green  { background: #dcfce7; color: #15803d; }
.ab-green:hover  { background: #15803d; color: #fff; transform: translateX(3px); }

.fluid-card {
    background: linear-gradient(135deg, #0f172a, #1e3a5f);
    border-radius: 14px; color: #fff; padding: 16px 18px;
    margin-bottom: 10px;
}
.fluid-row { display: flex; justify-content: space-between; align-items: center; padding: 5px 0; font-size: .82rem; }
.fluid-row.total { border-top: 1px solid rgba(255,255,255,.15); margin-top: 6px; padding-top: 10px; font-weight: 800; font-size: .9rem; }
.fluid-pos { color: #6ee7b7; }
.fluid-neg { color: #fca5a5; }

/* ── Empty state ── */
.empty-state {
    text-align: center; padding: 40px 20px;
}
.empty-icon { font-size: 3.5rem; opacity: .15; margin-bottom: 14px; }
.empty-title { font-size: .95rem; font-weight: 700; color: #64748b; margin-bottom: 4px; }
.empty-sub { font-size: .78rem; color: #94a3b8; }

/* ── Waiting list ── */
.wait-table { width: 100%; border-collapse: collapse; }
.wait-table th {
    font-size: .68rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: .5px; color: #94a3b8; padding: 10px 16px;
    text-align: left; border-bottom: 1px solid #f1f5f9;
}
.wait-table td { padding: 12px 16px; border-bottom: 1px solid #f8fafc; vertical-align: middle; }
.wait-table tr:last-child td { border-bottom: none; }
.wait-table tr:hover td { background: #f8faff; }
.patient-name { font-weight: 700; font-size: .85rem; color: #1e293b; }
.patient-id   { font-size: .72rem; color: #94a3b8; margin-top: 1px; }
.chir-badge {
    background: #f1f5f9; border: 1px solid #e2e8f0;
    border-radius: 50px; padding: 3px 10px;
    font-size: .72rem; font-weight: 600; color: #475569;
    display: inline-flex; align-items: center; gap: 5px;
}
.date-text { font-size: .78rem; color: #64748b; }
.btn-prepare {
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    color: #fff; border: none; border-radius: 50px;
    padding: 6px 16px; font-size: .75rem; font-weight: 700;
    cursor: pointer; transition: .2s; white-space: nowrap;
}
.btn-prepare:hover { box-shadow: 0 4px 12px rgba(99,102,241,.4); transform: translateY(-1px); }

/* ── Programme opératoire ── */
.prog-table { width: 100%; border-collapse: collapse; }
.prog-table th {
    font-size: .68rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: .5px; color: #94a3b8; padding: 10px 16px;
    text-align: left; border-bottom: 1px solid #f1f5f9;
}
.prog-table td { padding: 11px 16px; border-bottom: 1px solid #f8fafc; vertical-align: middle; }
.prog-table tr:last-child td { border-bottom: none; }
.prog-table tr:hover td { background: #fafbff; }
.statut-badge {
    border-radius: 50px; padding: 3px 10px;
    font-size: .68rem; font-weight: 800; display: inline-block;
}
.sb-prog  { background: #eff6ff; color: #1d4ed8; }
.sb-cours { background: #dcfce7; color: #15803d; }
.sb-fin   { background: #f1f5f9; color: #64748b; }
.sb-ann   { background: #fef2f2; color: #b91c1c; }
.heure-badge {
    background: #f1f5f9; border-radius: 8px;
    padding: 4px 8px; font-size: .75rem; font-weight: 700;
    color: #475569; font-variant-numeric: tabular-nums;
}

/* ── Fiches chirurgie ── */
.fiches-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
.fiche-card {
    border-radius: 18px; padding: 24px; color: #fff;
    display: flex; flex-direction: column; justify-content: space-between;
    min-height: 180px; position: relative; overflow: hidden;
    transition: transform .2s, box-shadow .2s;
}
.fiche-card:hover { transform: translateY(-4px); box-shadow: 0 16px 40px rgba(0,0,0,.15); }
.fc-1 { background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 100%); }
.fc-2 { background: linear-gradient(135deg, #5b21b6 0%, #7c3aed 100%); }
.fc-3 { background: linear-gradient(135deg, #064e3b 0%, #059669 100%); }
.fiche-card::after {
    content: ''; position: absolute;
    top: -20px; right: -20px;
    width: 100px; height: 100px;
    background: rgba(255,255,255,.08); border-radius: 50%;
}
.fiche-icon-wrap {
    width: 44px; height: 44px; border-radius: 12px;
    background: rgba(255,255,255,.18);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.2rem; margin-bottom: 14px;
}
.fiche-title { font-size: .9rem; font-weight: 800; margin-bottom: 5px; }
.fiche-desc  { font-size: .72rem; opacity: .8; line-height: 1.5; margin: 0; }
.fiche-btn {
    display: inline-flex; align-items: center; gap: 6px;
    background: rgba(255,255,255,.2); border-radius: 50px;
    padding: 7px 14px; font-size: .75rem; font-weight: 700;
    color: #fff; text-decoration: none; margin-top: 16px;
    border: 1px solid rgba(255,255,255,.25);
    transition: .15s; width: fit-content;
}
.fiche-btn:hover { background: rgba(255,255,255,.35); color: #fff; }

/* ── Animations ── */
@keyframes pulse {
    0%,100% { box-shadow: 0 0 0 3px rgba(16,185,129,.2); }
    50%      { box-shadow: 0 0 0 6px rgba(16,185,129,.1); }
}
@keyframes fadeUp {
    from { opacity: 0; transform: translateY(16px); }
    to   { opacity: 1; transform: translateY(0); }
}
.fade-up { animation: fadeUp .4s ease both; }
.delay-1 { animation-delay: .05s; }
.delay-2 { animation-delay: .1s; }
.delay-3 { animation-delay: .15s; }
.delay-4 { animation-delay: .2s; }

@media print { .no-print { display:none!important; } }
</style>

<div class="anes-shell">

    <!-- ═══════════ TOPBAR ═══════════ -->
    <header class="anes-topbar no-print">
        <div class="anes-brand">
            <img src="<?= BASE_URL ?>public/images/logo_ordre_malte.png" alt="HSJM">
            <div class="anes-brand-text">
                <h6>Anesthesia Cockpit</h6>
                <small>Hôpital Saint-Jean de Malte · Njombé</small>
            </div>
        </div>

        <div class="anes-topbar-center" id="liveClock">--:--:--</div>

        <div class="anes-topbar-right">
            <div class="user-chip">
                <div class="user-avatar"><?= strtoupper(substr($userName, 0, 2)) ?></div>
                <div class="user-chip-info">
                    <div class="user-chip-name"><?= htmlspecialchars($userName) ?></div>
                    <div class="user-chip-role"><?= $roleLabel ?></div>
                </div>
            </div>
            <a href="<?= BASE_URL ?>logout" class="btn-logout" title="Déconnexion">
                <i class="bi bi-power"></i>
            </a>
        </div>
    </header>

    <!-- ═══════════ BODY ═══════════ -->
    <main class="anes-body">

        <!-- Greeting -->
        <div class="anes-greeting fade-up">
            <h2>Bonjour, <?= htmlspecialchars(explode(' ', $userName)[0]) ?> 👋</h2>
            <p><?= date('l d F Y') ?> · Vue temps réel du service d'anesthésie</p>
        </div>

        <!-- Stats -->
        <div class="stat-row">
            <div class="stat-chip fade-up delay-1">
                <div class="stat-icon icon-purple"><i class="bi bi-person-fill-check"></i></div>
                <div>
                    <div class="stat-label">Patient en salle</div>
                    <div class="stat-value"><?= $patient ? '1' : '0' ?></div>
                </div>
            </div>
            <div class="stat-chip fade-up delay-2">
                <div class="stat-icon icon-amber"><i class="bi bi-hourglass-split"></i></div>
                <div>
                    <div class="stat-label">En attente</div>
                    <div class="stat-value"><?= count($demandes_entrantes) ?></div>
                </div>
            </div>
            <div class="stat-chip fade-up delay-3">
                <div class="stat-icon icon-blue"><i class="bi bi-clipboard2-pulse-fill"></i></div>
                <div>
                    <div class="stat-label">Fiches disponibles</div>
                    <div class="stat-value">3</div>
                </div>
            </div>
            <div class="stat-chip fade-up delay-4">
                <div class="stat-icon icon-green"><i class="bi bi-calendar-check"></i></div>
                <div>
                    <div class="stat-label">Date</div>
                    <div class="stat-value" style="font-size:1rem;margin-top:4px;"><?= date('d/m/Y') ?></div>
                </div>
            </div>
        </div>

        <!-- Accès rapide SSPI -->
        <div style="margin-bottom:20px;" class="fade-up delay-1">
            <a href="<?= BASE_URL ?>bloc/sspi-dashboard" style="
                display:inline-flex; align-items:center; gap:10px;
                background:linear-gradient(135deg,#0f766e,#059669);
                color:#fff; text-decoration:none; border-radius:14px;
                padding:12px 22px; font-size:.85rem; font-weight:800;
                box-shadow:0 4px 14px rgba(5,150,105,.3);
                transition:.2s;
            " onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform=''">
                <i class="bi bi-heart-pulse-fill fs-5"></i>
                Accéder au tableau de bord SSPI
                <i class="bi bi-arrow-right"></i>
            </a>
        </div>

        <!-- Programme opératoire -->
        <div class="sec-card fade-up delay-2" style="margin-bottom:20px;">
            <div class="sec-head">
                <span class="sec-head-title">
                    <i class="bi bi-calendar2-week-fill" style="color:#6366f1;"></i>
                    Programme opératoire
                    <span style="font-size:.68rem;background:#ede9fe;color:#6d28d9;border-radius:50px;padding:2px 8px;font-weight:700;">
                        Aujourd'hui + 3 jours
                    </span>
                </span>
                <span style="font-size:.72rem;color:#94a3b8;"><?= date('d/m/Y') ?></span>
            </div>
            <?php if (empty($programme_operatoire)): ?>
            <div class="empty-state" style="padding:30px;">
                <div class="empty-icon" style="font-size:2.5rem;"><i class="bi bi-calendar-x"></i></div>
                <div class="empty-title">Aucune intervention programmée</div>
                <div class="empty-sub">Les prochaines 72h sont libres dans le planning bloc.</div>
            </div>
            <?php else: ?>
            <div style="overflow-x:auto;">
                <table class="prog-table">
                    <thead>
                        <tr>
                            <th>Date / Heure</th>
                            <th>Patient</th>
                            <th>Intervention</th>
                            <th>Chirurgien</th>
                            <th>Salle</th>
                            <th>Durée</th>
                            <th>Statut</th>
                            <th style="text-align:right;">Fiche</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($programme_operatoire as $prog):
                            $dt = new DateTime($prog['date_prevue']);
                            $isToday = $dt->format('Y-m-d') === date('Y-m-d');
                            $statut  = strtoupper($prog['statut'] ?? '');
                            $statCls = match($statut) {
                                'EN_COURS'  => 'sb-cours',
                                'TERMINE'   => 'sb-fin',
                                'ANNULE'    => 'sb-ann',
                                default     => 'sb-prog',
                            };
                            $statLabel = match($statut) {
                                'PROGRAMME' => 'Programmé',
                                'EN_COURS'  => 'En cours',
                                'TERMINE'   => 'Terminé',
                                'ANNULE'    => 'Annulé',
                                default     => $prog['statut'],
                            };
                            $isMine = !empty($prog['is_mine']);
                        ?>
                        <tr style="<?= $statut === 'EN_COURS' ? 'background:#f0fdf4;' : ($isMine ? 'background:#faf5ff;' : '') ?>">
                            <td>
                                <div class="heure-badge"><?= $dt->format('H:i') ?></div>
                                <div style="font-size:.7rem;color:<?= $isToday ? '#6366f1' : '#94a3b8' ?>;font-weight:700;margin-top:3px;">
                                    <?= $isToday ? "Aujourd'hui" : $dt->format('d/m') ?>
                                </div>
                            </td>
                            <td>
                                <div style="display:flex;align-items:center;gap:6px;">
                                    <div>
                                        <div class="patient-name"><?= htmlspecialchars($prog['nom'].' '.$prog['prenom']) ?></div>
                                        <div class="patient-id"><?= htmlspecialchars($prog['dossier_numero']) ?> · <?= $prog['age_patient'] ?>a</div>
                                    </div>
                                    <?php if ($isMine): ?>
                                    <span style="background:#ede9fe;color:#6d28d9;font-size:.62rem;font-weight:800;padding:2px 7px;border-radius:20px;white-space:nowrap;flex-shrink:0;">
                                        <i class="bi bi-star-fill me-1"></i>Mon patient
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td style="font-size:.82rem;color:#1e293b;max-width:180px;"><?= htmlspecialchars($prog['type_intervention'] ?? '—') ?></td>
                            <td><span class="chir-badge"><i class="bi bi-person-fill" style="color:#6366f1;"></i>Dr. <?= htmlspecialchars($prog['chirurgien_nom'] ?? '—') ?></span></td>
                            <td style="font-size:.8rem;font-weight:700;color:#475569;"><?= htmlspecialchars($prog['nom_salle'] ?? '—') ?></td>
                            <td style="font-size:.78rem;color:#64748b;">—</td>
                            <td><span class="statut-badge <?= $statCls ?>"><?= $statLabel ?></span></td>
                            <td style="text-align:right;">
                                <a href="<?= BASE_URL ?>anesthesie/pre-anesthesique/<?= $prog['patient_id'] ?? '' ?>"
                                   style="background:#ede9fe;color:#6d28d9;border:none;border-radius:8px;padding:5px 10px;font-size:.72rem;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:4px;">
                                    <i class="bi bi-clipboard2-plus"></i> Fiche
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <!-- Monitoring + Actions -->
        <div class="anes-grid fade-up delay-2">

            <!-- Gauche : monitoring patient -->
            <div>
                <div class="sec-card">
                    <div class="sec-head">
                        <span class="sec-head-title">
                            <span class="sec-dot <?= $patient ? 'dot-live' : '' ?>" style="<?= $patient ? '' : 'background:#e2e8f0;' ?>"></span>
                            Monitoring patient
                        </span>
                        <?php if ($patient): ?>
                            <span style="font-size:.7rem;font-weight:700;color:#10b981;background:#dcfce7;border-radius:50px;padding:3px 10px;">● LIVE</span>
                        <?php endif; ?>
                    </div>
                    <div class="sec-body">
                        <?php if ($patient): ?>
                            <!-- Patient en cours -->
                            <div class="patient-hero">
                                <div class="patient-hero-name"><?= htmlspecialchars($patient['nom'] . ' ' . $patient['prenom']) ?></div>
                                <div class="patient-hero-meta"><?= $age ?> ans · <?= htmlspecialchars($patient['sexe'] ?? '') ?></div>
                                <div class="live-badge"><span style="width:6px;height:6px;background:#6ee7b7;border-radius:50%;"></span>En cours d'opération</div>
                                <div class="patient-hero-grid">
                                    <div class="patient-hero-item">
                                        <div class="phi-label">Salle</div>
                                        <div class="phi-val"><?= htmlspecialchars($patient['nom_salle'] ?? '—') ?></div>
                                    </div>
                                    <div class="patient-hero-item">
                                        <div class="phi-label">Allergies</div>
                                        <div class="phi-val" style="font-size:.78rem;"><?= htmlspecialchars($patient['allergies'] ?? 'Aucune signalée') ?></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Constantes simulées -->
                            <div class="vitals-grid">
                                <div class="vital-card vc-green">
                                    <div class="vital-lbl">Fréq. cardiaque</div>
                                    <div class="vital-val" id="fc-val">74</div>
                                    <div class="vital-unit">BPM</div>
                                </div>
                                <div class="vital-card vc-blue">
                                    <div class="vital-lbl">Saturation O₂</div>
                                    <div class="vital-val" id="spo2-val">98</div>
                                    <div class="vital-unit">% SpO₂</div>
                                </div>
                                <div class="vital-card vc-amber">
                                    <div class="vital-lbl">Pression artérielle</div>
                                    <div class="vital-val" style="font-size:1.35rem;">118/76</div>
                                    <div class="vital-unit">mmHg · PAM 90</div>
                                </div>
                                <div class="vital-card vc-rose">
                                    <div class="vital-lbl">EtCO₂</div>
                                    <div class="vital-val" id="etco2-val">35</div>
                                    <div class="vital-unit">mmHg</div>
                                </div>
                            </div>

                        <?php else: ?>
                            <div class="empty-state">
                                <div class="empty-icon"><i class="bi bi-person-badge"></i></div>
                                <div class="empty-title">Aucun patient en salle</div>
                                <div class="empty-sub">Sélectionnez une demande ci-dessous pour démarrer le monitorage.</div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Droite : fluides + actions rapides -->
            <div style="display:flex;flex-direction:column;gap:14px;">

                <!-- Fluides -->
                <div class="fluid-card">
                    <div style="font-size:.72rem;font-weight:800;text-transform:uppercase;letter-spacing:.5px;opacity:.6;margin-bottom:10px;">
                        <i class="bi bi-droplet-fill me-1"></i> Bilan des fluides
                    </div>
                    <div class="fluid-row"><span>Apports</span><span class="fluid-pos">+1 200 ml</span></div>
                    <div class="fluid-row"><span>Pertes estimées</span><span class="fluid-neg">−350 ml</span></div>
                    <div class="fluid-row total"><span>BALANCE</span><span class="fluid-pos">+850 ml</span></div>
                </div>

                <!-- Actions -->
                <div class="sec-card">
                    <div class="sec-head">
                        <span class="sec-head-title"><i class="bi bi-lightning-fill text-warning"></i> Actions rapides</span>
                    </div>
                    <div class="sec-body" style="padding:14px 16px;">
                        <a href="<?= BASE_URL ?>anesthesie/pre-anesthesique" class="action-btn ab-purple" style="text-decoration:none;">
                            <i class="bi bi-stethoscope fs-5"></i>
                            <div>
                                <div>Consultation pré-op</div>
                                <div style="font-size:.68rem;opacity:.7;font-weight:500;">Nouvelle fiche</div>
                            </div>
                        </a>
                        <a href="<?= BASE_URL ?>anesthesie/per-operatoire" class="action-btn ab-blue" style="text-decoration:none;">
                            <i class="bi bi-activity fs-5"></i>
                            <div>
                                <div>Fiche peropératoire</div>
                                <div style="font-size:.68rem;opacity:.7;font-weight:500;">Surveillance intraop</div>
                            </div>
                        </a>
                        <a href="<?= BASE_URL ?>anesthesie/surveillance-post-op" class="action-btn ab-green" style="text-decoration:none;">
                            <i class="bi bi-bandaid-fill fs-5"></i>
                            <div>
                                <div>Surveillance post-op</div>
                                <div style="font-size:.68rem;opacity:.7;font-weight:500;">Grille J0–J6</div>
                            </div>
                        </a>
                        <a href="<?= BASE_URL ?>anesthesie/ouverture-salle" class="action-btn" style="text-decoration:none;background:linear-gradient(135deg,#0891b2,#0e7490);color:#fff;">
                            <i class="bi bi-clipboard2-check-fill fs-5"></i>
                            <div>
                                <div>Ouverture de salle</div>
                                <div style="font-size:.68rem;opacity:.7;font-weight:500;">Check-list anesthésie</div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══════════ LISTE D'ATTENTE ═══════════ -->
        <div class="sec-card fade-up delay-3" style="margin-bottom:24px;">
            <div class="sec-head">
                <span class="sec-head-title">
                    <span class="sec-dot dot-wait"></span>
                    Demandes d'anesthésie en attente
                </span>
                <span style="background:#fef3c7;color:#92400e;border-radius:50px;padding:4px 12px;font-size:.72rem;font-weight:800;">
                    <?= count($demandes_entrantes) ?> patient<?= count($demandes_entrantes) > 1 ? 's' : '' ?>
                </span>
            </div>
            <div style="overflow-x:auto;">
                <table class="wait-table">
                    <thead>
                        <tr>
                            <th>Identité patient</th>
                            <th>Chirurgien prescripteur</th>
                            <th>Date de la demande</th>
                            <th style="text-align:right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($demandes_entrantes)): ?>
                        <tr>
                            <td colspan="4">
                                <div class="empty-state" style="padding:30px;">
                                    <div class="empty-icon" style="font-size:2.5rem;"><i class="bi bi-inbox"></i></div>
                                    <div class="empty-title">Aucun dossier en attente</div>
                                    <div class="empty-sub">Toutes les demandes ont été traitées.</div>
                                </div>
                            </td>
                        </tr>
                        <?php else: foreach ($demandes_entrantes as $req):
                            $reqMine = !empty($req['is_mine']); ?>
                        <tr style="<?= $reqMine ? 'background:#faf5ff;' : '' ?>">
                            <td>
                                <div style="display:flex;align-items:center;gap:6px;">
                                    <div>
                                        <div class="patient-name"><?= htmlspecialchars($req['nom'] . ' ' . $req['prenom']) ?></div>
                                        <div class="patient-id">ID: <?= htmlspecialchars($req['dossier_numero']) ?></div>
                                    </div>
                                    <?php if ($reqMine): ?>
                                    <span style="background:#ede9fe;color:#6d28d9;font-size:.62rem;font-weight:800;padding:2px 7px;border-radius:20px;white-space:nowrap;flex-shrink:0;">
                                        <i class="bi bi-star-fill me-1"></i>Mon patient
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <span class="chir-badge">
                                    <i class="bi bi-person-fill" style="color:#6366f1;"></i>
                                    Dr. <?= htmlspecialchars($req['chirurgien_nom'] ?? 'Non assigné') ?>
                                </span>
                            </td>
                            <td><span class="date-text"><?= date('d/m/Y à H:i', strtotime($req['date_demande'])) ?></span></td>
                            <td style="text-align:right;">
                                <a href="<?= BASE_URL ?>anesthesie/pre-anesthesique/<?= $req['patient_id'] ?>" class="btn-prepare">
                                    <i class="bi bi-pencil-square"></i> Préparer
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ═══════════ FICHES CHIRURGIE ═══════════ -->
        <div class="sec-card fade-up delay-4">
            <div class="sec-head">
                <span class="sec-head-title">
                    <i class="bi bi-clipboard2-pulse-fill" style="color:#6366f1;"></i>
                    Fiches Chirurgie &amp; Anesthésie
                </span>
                <span style="font-size:.72rem;color:#94a3b8;">Formulaires cliniques officiels</span>
            </div>
            <div class="sec-body">
                <div class="fiches-grid">
                    <div class="fiche-card fc-1">
                        <div>
                            <div class="fiche-icon-wrap"><i class="bi bi-stethoscope"></i></div>
                            <div class="fiche-title">Consultation Pré-Anesthésique</div>
                            <p class="fiche-desc">Antécédents, bilan, examen physique, Mallampati, score ASA et technique anesthésique.</p>
                        </div>
                        <a href="<?= BASE_URL ?>anesthesie/pre-anesthesique" class="fiche-btn">
                            <i class="bi bi-plus-circle"></i> Nouvelle fiche
                        </a>
                    </div>
                    <div class="fiche-card fc-2">
                        <div>
                            <div class="fiche-icon-wrap"><i class="bi bi-activity"></i></div>
                            <div class="fiche-title">Phase Peropératoire</div>
                            <p class="fiche-desc">Grille des constantes par 15 min, fluides, sang, ventilation, incidents et score Aldrete.</p>
                        </div>
                        <a href="<?= BASE_URL ?>anesthesie/per-operatoire" class="fiche-btn">
                            <i class="bi bi-plus-circle"></i> Nouvelle fiche
                        </a>
                    </div>
                    <div class="fiche-card fc-3">
                        <div>
                            <div class="fiche-icon-wrap"><i class="bi bi-bandaid-fill"></i></div>
                            <div class="fiche-title">Soins &amp; Surveillance Post-op</div>
                            <p class="fiche-desc">Grille quotidienne J0–J6 : constantes, analgésie, conscience, douleur et destination.</p>
                        </div>
                        <a href="<?= BASE_URL ?>anesthesie/surveillance-post-op" class="fiche-btn">
                            <i class="bi bi-plus-circle"></i> Nouvelle fiche
                        </a>
                    </div>
                </div>
            </div>
        </div>

    </main>
</div>

<script>
// Horloge
function updateClock() {
    const now = new Date();
    document.getElementById('liveClock').textContent =
        now.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
}
updateClock();
setInterval(updateClock, 1000);

<?php if ($patient): ?>
// Simulation légère des constantes (±1 BPM toutes les 3s)
setInterval(() => {
    const fc = document.getElementById('fc-val');
    if (fc) fc.textContent = Math.max(55, Math.min(120, parseInt(fc.textContent) + (Math.random() > .5 ? 1 : -1)));
    const et = document.getElementById('etco2-val');
    if (et) et.textContent = Math.max(28, Math.min(45, parseInt(et.textContent) + (Math.random() > .6 ? 1 : -1)));
}, 3000);
<?php endif; ?>
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
