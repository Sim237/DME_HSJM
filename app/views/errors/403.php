<?php
/* ============================================================================
   PAGE 403 — ACCÈS REFUSÉ
   Variables optionnelles fournies par le code appelant :
     $error_cause   : string — raison précise du refus (affichée à l'utilisateur)
     $error_details : array  — lignes complémentaires ['label' => 'valeur']
============================================================================ */
if (!defined('BASE_URL')) { define('BASE_URL', '/'); }

$userRole    = $_SESSION['user_role']   ?? null;
$nomService  = $_SESSION['nom_service'] ?? null;
$userName    = trim(($_SESSION['user_prenom'] ?? '') . ' ' . ($_SESSION['user_nom'] ?? ''));
$urlDemandee = $_SERVER['REQUEST_URI'] ?? '';

$error_cause = $error_cause
    ?? ($userRole
        ? "Votre profil « " . $userRole . " » ne dispose pas des droits nécessaires pour consulter cette ressource."
        : "Vous n'êtes pas connecté ou votre session a expiré.");
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accès refusé — SimCare+</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 55%, #312e81 100%);
            overflow-x: hidden; overflow-y: auto;
            position: relative; padding: 32px 20px;
        }

        /* Halos décoratifs flottants (fixed : ne créent pas de scroll) */
        .halo {
            position: fixed; border-radius: 50%; filter: blur(80px);
            opacity: .35; animation: drift 14s ease-in-out infinite alternate;
            pointer-events: none;
        }
        .halo-1 { width: 420px; height: 420px; background: #6366f1; top: -120px; left: -100px; }
        .halo-2 { width: 360px; height: 360px; background: #ec4899; bottom: -100px; right: -80px; animation-delay: -7s; }
        @keyframes drift {
            from { transform: translate(0,0) scale(1); }
            to   { transform: translate(60px, 40px) scale(1.15); }
        }

        .card403 {
            position: relative; z-index: 2;
            width: 100%; max-width: 520px;
            background: rgba(255,255,255,.97);
            border-radius: 24px;
            box-shadow: 0 30px 80px rgba(0,0,0,.45);
            padding: 30px 36px 26px;
            text-align: center;
            animation: cardIn .55s cubic-bezier(.2,.9,.3,1.2) both;
        }
        @keyframes cardIn {
            from { opacity: 0; transform: translateY(40px) scale(.92); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }

        /* Cadenas animé */
        .lock-wrap {
            width: 72px; height: 72px; margin: 0 auto 16px;
            border-radius: 22px;
            background: linear-gradient(135deg, #fef2f2, #fee2e2);
            display: flex; align-items: center; justify-content: center;
            position: relative;
            animation: lockShake .6s ease .8s 1;
        }
        .lock-wrap::after {
            content: ''; position: absolute; inset: -8px;
            border-radius: 34px; border: 2px solid rgba(239,68,68,.25);
            animation: ringPulse 2s ease-out infinite;
        }
        @keyframes ringPulse {
            0%   { transform: scale(.9); opacity: 1; }
            100% { transform: scale(1.25); opacity: 0; }
        }
        @keyframes lockShake {
            0%,100% { transform: rotate(0); }
            20% { transform: rotate(-8deg); } 40% { transform: rotate(7deg); }
            60% { transform: rotate(-5deg); } 80% { transform: rotate(3deg); }
        }
        .lock-wrap i { font-size: 2rem; color: #dc2626; }

        .code403 {
            font-size: .8rem; font-weight: 800; letter-spacing: 4px;
            color: #ef4444; text-transform: uppercase; margin-bottom: 8px;
            animation: fadeUp .5s ease .25s both;
        }
        h1 {
            font-size: 1.45rem; font-weight: 800; color: #0f172a; margin-bottom: 12px;
            animation: fadeUp .5s ease .35s both;
        }
        .cause {
            font-size: .88rem; color: #475569; line-height: 1.6;
            background: #fff7ed; border: 1px solid #fed7aa;
            border-left: 4px solid #f97316;
            border-radius: 12px; padding: 12px 16px; text-align: left;
            margin-bottom: 16px;
            animation: fadeUp .5s ease .45s both;
        }
        .cause strong { color: #9a3412; }

        /* Détails contextuels */
        .details {
            display: flex; flex-direction: column; gap: 6px;
            margin-bottom: 20px;
            animation: fadeUp .5s ease .55s both;
        }
        .detail-row {
            display: flex; align-items: center; gap: 10px;
            background: #f8fafc; border: 1px solid #e2e8f0;
            border-radius: 10px; padding: 7px 14px;
            font-size: .8rem; color: #334155; text-align: left;
        }
        .detail-row i { color: #6366f1; font-size: .95rem; flex-shrink: 0; }
        .detail-row .lbl { color: #94a3b8; font-weight: 600; min-width: 110px; }
        .detail-row .val { font-weight: 700; word-break: break-all; }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(14px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* Boutons */
        .actions {
            display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;
            animation: fadeUp .5s ease .65s both;
        }
        .btn403 {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 12px 22px; border-radius: 12px;
            font-size: .88rem; font-weight: 700; text-decoration: none;
            transition: transform .15s, box-shadow .15s; cursor: pointer; border: none;
        }
        .btn403:hover { transform: translateY(-2px); }
        .btn-primary403 {
            background: linear-gradient(135deg, #3b82f6, #6366f1); color: #fff;
            box-shadow: 0 6px 18px rgba(99,102,241,.35);
        }
        .btn-primary403:hover { box-shadow: 0 10px 26px rgba(99,102,241,.45); }
        .btn-ghost403 {
            background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0;
        }
        .btn-ghost403:hover { background: #e2e8f0; }

        .hint {
            margin-top: 16px; font-size: .73rem; color: #94a3b8;
            animation: fadeUp .5s ease .75s both;
        }
        .hint i { color: #cbd5e1; }

        @media (max-width: 480px) {
            .card403 { padding: 32px 22px 28px; }
            h1 { font-size: 1.35rem; }
            .detail-row .lbl { min-width: 90px; }
        }
    </style>
</head>
<body>
    <div class="halo halo-1"></div>
    <div class="halo halo-2"></div>

    <div class="card403">
        <div class="lock-wrap"><i class="bi bi-shield-lock-fill"></i></div>

        <div class="code403">Erreur 403 · Accès refusé</div>
        <h1>Oups, cette porte est fermée<?= $userName ? ', ' . htmlspecialchars(explode(' ', $userName)[0]) : '' ?> !</h1>

        <div class="cause">
            <strong><i class="bi bi-info-circle-fill me-1"></i> Pourquoi ?</strong><br>
            <?= htmlspecialchars($error_cause) ?>
        </div>

        <div class="details">
            <?php if ($userRole): ?>
            <div class="detail-row">
                <i class="bi bi-person-badge-fill"></i>
                <span class="lbl">Votre profil</span>
                <span class="val"><?= htmlspecialchars($userRole) ?></span>
            </div>
            <?php endif; ?>
            <?php if ($nomService): ?>
            <div class="detail-row">
                <i class="bi bi-building-fill"></i>
                <span class="lbl">Votre service</span>
                <span class="val"><?= htmlspecialchars($nomService) ?></span>
            </div>
            <?php endif; ?>
            <?php if ($urlDemandee): ?>
            <div class="detail-row">
                <i class="bi bi-link-45deg"></i>
                <span class="lbl">Page demandée</span>
                <span class="val"><?= htmlspecialchars($urlDemandee) ?></span>
            </div>
            <?php endif; ?>
            <?php foreach (($error_details ?? []) as $lbl => $val): if ($val === '' || $val === null) continue; ?>
            <div class="detail-row">
                <i class="bi bi-chevron-right"></i>
                <span class="lbl"><?= htmlspecialchars($lbl) ?></span>
                <span class="val"><?= htmlspecialchars($val) ?></span>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="actions">
            <button type="button" class="btn403 btn-ghost403" onclick="history.back()">
                <i class="bi bi-arrow-left"></i> Page précédente
            </button>
            <a href="<?= BASE_URL ?>dashboard" class="btn403 btn-primary403">
                <i class="bi bi-house-fill"></i> Tableau de bord
            </a>
        </div>

        <div class="hint">
            <i class="bi bi-life-preserver"></i>
            Si vous pensez qu'il s'agit d'une erreur, contactez l'administrateur du système.
        </div>
    </div>
</body>
</html>
