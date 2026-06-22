<?php
/**
 * SimCare+ · Suivi complet du nouveau-né — Néonatologie
 */
require_once __DIR__ . '/../layouts/header.php';

$nom     = strtoupper($patient['nom'] ?? '');
$prenom  = $patient['prenom'] ?? '';
$sexe    = $patient['sexe'] ?? 'M';
$dossier = $patient['dossier_numero'] ?? '';
$age     = NeonatologyController::ageNeonat($patient['date_naissance'] ?? null);
$chambre = $patient['nom_chambre'] ?? '';
$lit     = $patient['nom_lit'] ?? '';
$medResp = trim(($patient['medecin_prenom'] ?? '') . ' ' . ($patient['medecin_nom'] ?? ''));
$naissance = $examens[0] ?? null;
$apgarColor = fn($v) => $v === null ? '#94a3b8' : ($v >= 7 ? '#16a34a' : ($v >= 4 ? '#f59e0b' : '#dc2626'));
$modeLabels  = ['VOIE_BASSE'=>'Voie basse','CESARIENNE'=>'Césarienne','INSTRUMENTAL'=>'Instrumental'];
$termeLabels = ['PREMATURE'=>'Prématuré','A_TERME'=>'À terme','POST_TERME'=>'Post-terme'];

$uNom    = strtoupper(trim(($_SESSION['user_nom'] ?? '') . ' ' . ($_SESSION['user_prenom'] ?? '')));
$serviceNom = $_SESSION['nom_service'] ?? 'Néonatologie';
?>
<style>
.sidebar,nav.sidebar{display:none!important}
main,.col-md-10,.ms-sm-auto{margin-left:0!important;width:100%!important;flex:0 0 100%!important;max-width:100%!important;padding:0!important}
body{background:#f0f7f9;font-family:'Segoe UI',system-ui,sans-serif;color:#1e293b}

/* ── Topbar ─────────────────────────────── */
.sv-top{background:linear-gradient(135deg,#0e7490,#06b6d4 60%,#22d3ee);padding:14px 28px;
  display:flex;align-items:center;justify-content:space-between;
  position:sticky;top:0;z-index:100;box-shadow:0 4px 24px rgba(6,182,212,.3)}
.sv-title{font-size:1.1rem;font-weight:900;color:#fff;display:flex;align-items:center;gap:10px}
.sv-sub{font-size:.66rem;color:rgba(255,255,255,.8);font-weight:600;text-transform:uppercase;letter-spacing:1px;margin-top:2px}
.sv-btn{display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border-radius:10px;
  font-weight:700;font-size:.76rem;text-decoration:none;border:1px solid rgba(255,255,255,.35);
  background:rgba(255,255,255,.18);color:#fff;transition:all .18s;cursor:pointer}
.sv-btn:hover{background:rgba(255,255,255,.3);color:#fff}
.sv-btn-print{background:rgba(255,255,255,.95);color:#0e7490;border-color:rgba(255,255,255,.9)}
.sv-btn-print:hover{background:#fff;color:#0891b2}

/* ── Layout ─────────────────────────────── */
.sv-wrap{padding:22px 28px;max-width:1120px;margin:0 auto}

/* ── Patient banner ─────────────────────── */
.sv-banner{background:#fff;border-radius:20px;border:1px solid #cffafe;
  box-shadow:0 4px 20px rgba(6,182,212,.1);padding:20px 26px;margin-bottom:20px;
  display:flex;align-items:center;gap:20px}
.sv-av{width:62px;height:62px;border-radius:18px;display:flex;align-items:center;
  justify-content:center;font-size:1.8rem;color:#fff;flex-shrink:0}
.sv-av.g{background:linear-gradient(135deg,#06b6d4,#22d3ee)}
.sv-av.f{background:linear-gradient(135deg,#ec4899,#f472b6)}
.sv-pname{font-size:1.25rem;font-weight:900;color:#0f172a}
.sv-pmeta{font-size:.78rem;color:#64748b;margin-top:3px;display:flex;flex-wrap:wrap;gap:8px;align-items:center}
.sv-pmeta i{color:#06b6d4}
.sv-chips{display:flex;flex-wrap:wrap;gap:6px;margin-top:10px}
.sv-chip{padding:3px 12px;border-radius:20px;font-size:.7rem;font-weight:700;
  background:#f0fdff;color:#0e7490;border:1px solid #a5f3fc;display:inline-flex;align-items:center;gap:4px}
.sv-chip.warn{background:#fef9c3;color:#854d0e;border-color:#fde047}

/* ── Flash ──────────────────────────────── */
.sv-flash{padding:12px 18px;border-radius:12px;margin-bottom:16px;font-size:.84rem;font-weight:600;display:flex;align-items:center;gap:10px}
.sv-flash.ok{background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0}
.sv-flash.err{background:#fef2f2;color:#dc2626;border:1px solid #fca5a5}

/* ── Panel ──────────────────────────────── */
.sv-panel{background:#fff;border-radius:18px;border:1px solid #e0f0f3;
  box-shadow:0 2px 12px rgba(6,182,212,.06);margin-bottom:18px;overflow:hidden}
.sv-ph{padding:14px 22px;border-bottom:2px solid #cffafe;
  background:linear-gradient(90deg,#f0fdff 0%,#fff 60%);
  display:flex;align-items:center;justify-content:space-between}
.sv-pt{font-size:.8rem;font-weight:800;color:#0e7490;text-transform:uppercase;
  letter-spacing:.6px;display:flex;align-items:center;gap:8px}
.sv-pbadge{padding:2px 10px;border-radius:20px;font-size:.68rem;font-weight:800;background:#0e7490;color:#fff}

/* ── Naissance grid ─────────────────────── */
.sv-birth{display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:10px;padding:16px 22px}
.sv-bi{background:linear-gradient(135deg,#f0fdff,#fff);border:1px solid #cffafe;
  border-radius:14px;padding:10px 14px;transition:.2s}
.sv-bi:hover{box-shadow:0 4px 12px rgba(6,182,212,.12);transform:translateY(-1px)}
.sv-bl{font-size:.64rem;color:#94a3b8;font-weight:700;text-transform:uppercase;letter-spacing:.5px}
.sv-bv{font-size:1.05rem;font-weight:900;color:#0e7490;margin-top:3px}

/* ── Timeline ───────────────────────────── */
.sv-tl{padding:8px 22px 18px}
.sv-ex{border-left:3px solid #06b6d4;margin:14px 0;padding:14px 18px;
  background:linear-gradient(90deg,#f0fdff,#f8fafc);border-radius:0 14px 14px 0;position:relative}
.sv-ex::before{content:'';width:11px;height:11px;border-radius:50%;
  background:#06b6d4;position:absolute;left:-7px;top:18px;box-shadow:0 0 0 3px #cffafe}
.sv-ex-date{font-size:.72rem;font-weight:800;color:#0e7490;margin-bottom:10px;display:flex;align-items:center;gap:8px}
.sv-vits{display:flex;flex-wrap:wrap;gap:8px;margin:8px 0}
.sv-vit{background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:6px 12px;text-align:center;min-width:68px;transition:.15s}
.sv-vit:hover{border-color:#06b6d4;box-shadow:0 2px 8px rgba(6,182,212,.1)}
.sv-vit .v{font-weight:900;font-size:.88rem;color:#0e7490}
.sv-vit .v.warn{color:#dc2626}
.sv-vit .l{font-size:.58rem;color:#94a3b8;text-transform:uppercase;font-weight:700;margin-top:1px}
.sv-diag{font-size:.82rem;color:#374151;margin-top:8px;line-height:1.6;padding:8px 12px;
  background:#fff;border-radius:10px;border-left:3px solid #e2e8f0}
.sv-diag strong{color:#0e7490}

/* ── Table ──────────────────────────────── */
.sv-tbl{width:100%;border-collapse:collapse}
.sv-tbl th{background:linear-gradient(90deg,#f0fdff,#f8fafc);color:#0e7490;font-size:.67rem;
  text-transform:uppercase;letter-spacing:.5px;font-weight:800;padding:10px 16px;text-align:left}
.sv-tbl td{padding:10px 16px;border-bottom:1px solid #f1f5f9;font-size:.82rem;vertical-align:top}
.sv-tbl tr:hover td{background:#f8fdff}

/* ── Soins infirmiers form ──────────────── */
.sv-soin-form{padding:18px 22px;border-bottom:2px solid #f0fdff}
.sv-form-grid{display:grid;grid-template-columns:120px 100px 1fr 1fr 1fr 160px;gap:10px;align-items:start}
.sv-form-lbl{font-size:.68rem;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.4px;margin-bottom:4px}
.sv-form-ctrl{width:100%;border:1.5px solid #e2e8f0;border-radius:10px;padding:8px 12px;
  font-size:.82rem;font-family:inherit;resize:vertical;transition:.18s;background:#fff}
.sv-form-ctrl:focus{border-color:#06b6d4;outline:none;box-shadow:0 0 0 3px rgba(6,182,212,.12)}
.sv-form-submit{background:linear-gradient(135deg,#0e7490,#06b6d4);color:#fff;border:none;
  border-radius:12px;padding:10px 18px;font-weight:800;font-size:.8rem;cursor:pointer;
  display:flex;align-items:center;gap:6px;width:100%;justify-content:center;transition:.2s;margin-top:18px}
.sv-form-submit:hover{background:linear-gradient(135deg,#0891b2,#0e7490);transform:translateY(-1px)}

/* ── Obs card ───────────────────────────── */
.sv-obs{padding:14px 22px;border-bottom:1px solid #f1f5f9}
.sv-obs:last-child{border-bottom:none}
.sv-obs-hd{font-size:.73rem;font-weight:800;color:#1d4ed8;margin-bottom:4px}
.sv-obs-motif{font-size:.86rem;font-weight:700;color:#1e293b;margin-bottom:4px}
.sv-obs-txt{font-size:.8rem;color:#475569;line-height:1.6}

/* ── Post-natale ─────────────────────────── */
.sv-pn{padding:18px 22px}
.sv-pn-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:12px;margin-top:12px}
.sv-pni{background:linear-gradient(135deg,#f0fdf4,#fff);border:1px solid #bbf7d0;border-radius:14px;padding:10px 14px}
.sv-pni-l{font-size:.64rem;color:#15803d;font-weight:700;text-transform:uppercase}
.sv-pni-v{font-size:.95rem;font-weight:900;color:#065f46;margin-top:2px}

.sv-empty{padding:32px;text-align:center;color:#94a3b8;font-size:.85rem}
.sv-empty i{font-size:2rem;opacity:.2;display:block;margin-bottom:8px}

/* ── Print styles ───────────────────────── */
@media print{
  .sv-top,.sv-banner .sv-chips,.sv-soin-form,.sv-btn,
  .no-print{display:none!important}
  body{background:#fff!important;font-size:11pt}
  .sv-wrap{padding:0;max-width:100%}
  .sv-panel{box-shadow:none!important;border:1px solid #ccc!important;border-radius:0!important;margin-bottom:12px!important}
  .sv-ph{background:#f5f5f5!important}

  /* Fiche de soins : header officiel */
  #print-fiche-header{display:block!important}
  /* Forcer la table soins visible en impression */
  #section-soins .sv-tbl{display:table!important}
}
#print-fiche-header{display:none}

@media (max-width:900px){
  .sv-form-grid{grid-template-columns:1fr 1fr}
}
/* ── Exécution soins planifiés ──────────────── */
.se-filter{display:flex;gap:8px;padding:14px 22px 0;flex-wrap:wrap}
.se-ftab{padding:5px 14px;border-radius:20px;font-size:.72rem;font-weight:800;border:1.5px solid #e2e8f0;
  background:#fff;color:#64748b;cursor:pointer;transition:.15s}
.se-ftab.active{background:#0e7490;color:#fff;border-color:#0e7490}
.se-list{padding:14px 22px 20px;display:flex;flex-direction:column;gap:10px}
.se-item{border-radius:14px;border:1.5px solid #e2e8f0;overflow:hidden;transition:.2s}
.se-item.realise{border-color:#bbf7d0;opacity:.75}
.se-item.retard{border-color:#fde68a}
.se-item-hd{display:flex;align-items:center;gap:12px;padding:10px 16px;background:#fafafa}
.se-type-badge{padding:3px 10px;border-radius:20px;font-size:.64rem;font-weight:800;white-space:nowrap}
.se-desc{flex:1;font-size:.84rem;font-weight:600;color:#1e293b}
.se-heure{font-size:.75rem;font-weight:800;color:#64748b;white-space:nowrap}
.se-stat{padding:3px 10px;border-radius:20px;font-size:.64rem;font-weight:800;white-space:nowrap}
.se-exec-btn{background:linear-gradient(135deg,#059669,#10b981);color:#fff;border:none;
  border-radius:10px;padding:5px 14px;font-size:.72rem;font-weight:800;cursor:pointer;
  display:flex;align-items:center;gap:5px;transition:.18s;white-space:nowrap}
.se-exec-btn:hover{background:linear-gradient(135deg,#047857,#059669);transform:translateY(-1px)}
.se-note-zone{padding:10px 16px;background:#f8fafc;border-top:1px solid #f1f5f9;
  display:none;align-items:center;gap:8px}
.se-note-input{flex:1;border:1.5px solid #e2e8f0;border-radius:10px;padding:7px 12px;
  font-size:.8rem;font-family:inherit;outline:none}
.se-note-input:focus{border-color:#059669}
.se-note-confirm{background:#059669;color:#fff;border:none;border-radius:10px;
  padding:7px 14px;font-size:.76rem;font-weight:800;cursor:pointer;white-space:nowrap}
.se-exec-info{font-size:.72rem;color:#15803d;display:flex;align-items:center;gap:5px}
/* ── Paramètres vitaux ──────────────────────── */
.charts-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;padding:16px 22px 20px}
.param-chart-card{background:linear-gradient(135deg,#fafbff,#fff);border:1px solid #e2e8f0;border-radius:16px;padding:14px 16px}
.param-chart-title{font-size:.74rem;font-weight:800;color:#374151;margin-bottom:10px;display:flex;align-items:center;gap:6px}
@media(max-width:720px){.charts-grid{grid-template-columns:1fr!important}}
/* ── Modal paramètres ───────────────────────── */
.sv-modal-overlay{position:fixed;inset:0;background:rgba(15,23,42,.62);z-index:9999;
  display:none;align-items:center;justify-content:center;backdrop-filter:blur(4px)}
.sv-modal-overlay.show{display:flex}
.sv-modal{background:#fff;border-radius:22px;width:min(540px,96vw);max-height:92vh;
  overflow-y:auto;box-shadow:0 30px 100px rgba(0,0,0,.28);animation:modalIn .24s cubic-bezier(.34,1.56,.64,1)}
@keyframes modalIn{from{opacity:0;transform:scale(.88) translateY(24px)}to{opacity:1;transform:none}}
.sv-modal-hd{padding:18px 22px 14px;border-bottom:1px solid #ede9fe;display:flex;align-items:center;
  justify-content:space-between;background:linear-gradient(90deg,#f5f3ff,#fff);border-radius:22px 22px 0 0}
.sv-modal-title{font-size:1.02rem;font-weight:900;color:#4c1d95;display:flex;align-items:center;gap:9px}
.sv-modal-close{background:none;border:none;font-size:1.6rem;color:#94a3b8;cursor:pointer;
  line-height:1;padding:2px 6px;transition:.15s;border-radius:8px}
.sv-modal-close:hover{color:#1e293b;background:#f1f5f9}
.sv-modal-body{padding:20px 22px}
.sv-modal-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.sv-modal-lbl{font-size:.69rem;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.4px;margin-bottom:5px}
.sv-modal-ctrl{width:100%;border:2px solid #e2e8f0;border-radius:12px;padding:10px 14px;
  font-size:.9rem;transition:.18s;font-family:inherit;background:#fff}
.sv-modal-ctrl:focus{border-color:#7c3aed;outline:none;box-shadow:0 0 0 3px rgba(124,58,237,.12)}
.sv-modal-hint{font-size:.63rem;color:#94a3b8;margin-top:3px}
.sv-modal-submit{width:100%;background:linear-gradient(135deg,#6d28d9,#7c3aed);color:#fff;
  border:none;border-radius:14px;padding:13px;font-weight:900;font-size:.9rem;cursor:pointer;
  margin-top:20px;transition:.22s;display:flex;align-items:center;justify-content:center;gap:8px}
.sv-modal-submit:hover{background:linear-gradient(135deg,#5b21b6,#6d28d9);transform:translateY(-1px);box-shadow:0 8px 24px rgba(109,40,217,.3)}
.sv-modal-submit:disabled{opacity:.55;cursor:default;transform:none;box-shadow:none}
/* ── Toast ──────────────────────────────────── */
.sv-toast{position:fixed;bottom:24px;right:24px;z-index:10000;padding:13px 20px;border-radius:14px;
  font-weight:700;font-size:.84rem;box-shadow:0 8px 32px rgba(0,0,0,.15);min-width:260px;
  display:none;align-items:center;gap:10px}
.sv-toast.ok{background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0}
.sv-toast.err{background:#fef2f2;color:#dc2626;border:1px solid #fca5a5}
</style>

<main>
<!-- ─── Topbar ─────────────────────────────────────────── -->
<div class="sv-top">
  <div>
    <div class="sv-title"><i class="bi bi-activity"></i> Suivi — <?= htmlspecialchars("BB $nom $prenom") ?></div>
    <div class="sv-sub">Unité de Néonatologie · HSJM · <?= htmlspecialchars($dossier) ?></div>
  </div>
  <div class="d-flex gap-2 flex-wrap align-items-center">
    <a href="<?= BASE_URL ?>neonatologie/examen/<?= (int)$patient['id'] ?>" class="sv-btn"><i class="bi bi-clipboard2-plus"></i> <span class="d-none d-md-inline">Examiner</span></a>
    <a href="<?= BASE_URL ?>neonatologie/gavage/<?= (int)$patient['id'] ?>" class="sv-btn"><i class="bi bi-droplet-fill"></i> <span class="d-none d-md-inline">Gavage</span></a>
    <a href="<?= BASE_URL ?>neonatologie/observation/<?= (int)$patient['id'] ?>" class="sv-btn"><i class="bi bi-journal-medical"></i> <span class="d-none d-md-inline">Observation</span></a>
    <a href="<?= BASE_URL ?>neonatologie/transfusion/<?= (int)$patient['id'] ?>" class="sv-btn" style="background:rgba(220,38,38,.22);border-color:rgba(252,165,165,.5);" title="Fiche transfusionnelle"><i class="bi bi-droplet-half"></i> <span class="d-none d-lg-inline">Transfusion</span></a>
    <a href="<?= BASE_URL ?>hospitalisation/planifier-soins/<?= (int)$patient['id'] ?>" class="sv-btn" style="background:rgba(5,150,105,.2);border-color:rgba(110,231,183,.5);" title="Planification des soins infirmiers"><i class="bi bi-calendar2-check"></i> <span class="d-none d-lg-inline">Planification</span></a>
    <?php if (!empty($patient['admission_id'])): ?>
    <a href="<?= BASE_URL ?>hospitalisation/executer-soins/<?= (int)$patient['admission_id'] ?>" class="sv-btn" style="background:rgba(8,145,178,.25);border-color:rgba(6,182,212,.5);" title="Exécuter les soins planifiés"><i class="bi bi-play-circle-fill"></i> <span class="d-none d-lg-inline">Exécuter soins</span></a>
    <?php endif ?>
    <button onclick="openParamsModal()" class="sv-btn" style="background:rgba(124,58,237,.3);border-color:rgba(167,139,250,.55);"><i class="bi bi-thermometer-half"></i> <span class="d-none d-md-inline">Prendre paramètres</span></button>
    <button onclick="window.print()" class="sv-btn sv-btn-print"><i class="bi bi-printer-fill"></i> <span class="d-none d-md-inline">Imprimer fiche</span></button>
    <a href="<?= BASE_URL ?>neonatologie" class="sv-btn"><i class="bi bi-arrow-left"></i></a>
  </div>
</div>


<div class="sv-wrap">

  <?php if ($flash): ?>
  <div class="sv-flash <?= $flash['type'] === 'ok' ? 'ok' : 'err' ?>">
    <i class="bi bi-<?= $flash['type'] === 'ok' ? 'check-circle-fill' : 'exclamation-circle-fill' ?>"></i>
    <?= htmlspecialchars($flash['msg']) ?>
  </div>
  <?php endif ?>

  <!-- ─── Patient banner ─────────────────── -->
  <div class="sv-banner">
    <div class="sv-av <?= $sexe === 'F' ? 'f' : 'g' ?>"><?= $sexe === 'F' ? '♀' : '♂' ?></div>
    <div style="flex:1;">
      <div class="sv-pname">BB <?= htmlspecialchars("$nom $prenom") ?></div>
      <div class="sv-pmeta">
        <span><i class="bi bi-file-medical"></i> <?= htmlspecialchars($dossier) ?></span>
        <span><i class="bi bi-clock"></i> <?= htmlspecialchars($age) ?></span>
        <?php if ($chambre): ?><span><i class="bi bi-geo-alt-fill"></i> <?= htmlspecialchars("$chambre · $lit") ?></span><?php endif ?>
        <?php if ($medResp): ?><span><i class="bi bi-person-badge"></i> Dr <?= htmlspecialchars($medResp) ?></span><?php endif ?>
      </div>
      <div class="sv-chips">
        <span class="sv-chip"><i class="bi bi-clipboard2-pulse"></i><?= count($examens) ?> examen(s)</span>
        <span class="sv-chip"><i class="bi bi-droplet"></i><?= count($gavages) ?> gavage(s)</span>
        <span class="sv-chip"><i class="bi bi-journal"></i><?= count($observations) ?> observation(s)</span>
        <span class="sv-chip"><i class="bi bi-heart-pulse"></i><?= count($soins_infirmiers) ?> soin(s) infirmier(s)</span>
        <span class="sv-chip" id="chipMesures" style="background:#f5f3ff;color:#6d28d9;border-color:#ddd6fe;"><i class="bi bi-thermometer-half"></i><?= count($parametres ?? []) ?> mesure(s)</span>
        <?php if ($post_natale): ?><span class="sv-chip" style="background:#f0fdf4;color:#15803d;border-color:#bbf7d0;"><i class="bi bi-check-circle"></i>Post-natale</span><?php endif ?>
      </div>
    </div>
  </div>

  <!-- ─── 0. Paramètres vitaux & Courbes ─────── -->
  <div class="sv-panel" id="section-parametres">
    <div class="sv-ph">
      <div class="sv-pt"><i class="bi bi-thermometer-half" style="color:#7c3aed;"></i> Paramètres vitaux &amp; Courbes d'évolution <span class="sv-pbadge ms-1" id="badgeParams" style="background:#7c3aed;"><?= count($parametres ?? []) ?></span></div>
      <button onclick="openParamsModal()" class="sv-btn no-print" style="background:linear-gradient(135deg,#6d28d9,#7c3aed);color:#fff;border-color:#7c3aed;font-size:.72rem;padding:6px 14px;"><i class="bi bi-plus-circle-fill"></i> Prendre paramètres</button>
    </div>
    <div class="charts-grid">
      <div class="param-chart-card">
        <div class="param-chart-title"><span style="font-size:.85rem;">🌡</span> Température (°C)</div>
        <canvas id="chartTemp" height="130"></canvas>
      </div>
      <div class="param-chart-card">
        <div class="param-chart-title"><span style="font-size:.85rem;">⚖</span> Poids (grammes)</div>
        <canvas id="chartPoids" height="130"></canvas>
      </div>
      <div class="param-chart-card">
        <div class="param-chart-title"><span style="font-size:.85rem;">🫁</span> Saturation SpO₂ (%)</div>
        <canvas id="chartSpo2" height="130"></canvas>
      </div>
      <div class="param-chart-card">
        <div class="param-chart-title"><span style="font-size:.85rem;">🩸</span> Glycémie capillaire (g/L)</div>
        <canvas id="chartGlycemie" height="130"></canvas>
      </div>
    </div>
    <div id="paramTableWrap" <?= empty($parametres) ? 'style="display:none"' : '' ?> style="overflow-x:auto;border-top:1px solid #f1f5f9;">
      <table class="sv-tbl">
        <thead><tr>
          <th>Date</th><th>Heure</th>
          <th style="color:#ef4444;">T° (°C)</th>
          <th style="color:#7c3aed;">Poids (g)</th>
          <th style="color:#2563eb;">SpO₂ (%)</th>
          <th style="color:#d97706;">Glycémie (g/L)</th>
          <th>Relevé par</th>
        </tr></thead>
        <tbody id="paramTableBody">
        <?php foreach (array_reverse($parametres ?? []) as $pv): ?>
        <tr>
          <td style="font-weight:700;color:#7c3aed;white-space:nowrap;"><?= date('d/m/Y', strtotime($pv['date_mesure'])) ?></td>
          <td style="white-space:nowrap;"><?= substr($pv['heure_mesure'],0,5) ?></td>
          <td><?php $t=$pv['temperature']; echo $t!==null?"<span style='font-weight:800;color:".($t<36.5||$t>37.5?'#dc2626':'#16a34a')."'>$t</span>":'—'; ?></td>
          <td><?= $pv['poids']!==null ? number_format((int)$pv['poids']).' g' : '—' ?></td>
          <td><?php $s=$pv['saturation']; echo $s!==null?"<span style='font-weight:800;color:".($s<90?'#dc2626':'#16a34a')."'>$s%</span>":'—'; ?></td>
          <td><?php $g=$pv['glycemie']; echo $g!==null?"<span style='font-weight:800;color:".($g<0.45||$g>1.26?'#dc2626':'#16a34a')."'>$g</span>":'—'; ?></td>
          <td style="font-size:.78rem;color:#64748b;"><?= $pv['user_nom'] ? htmlspecialchars(($pv['user_prenom']??'').' '.$pv['user_nom']) : '—' ?></td>
        </tr>
        <?php endforeach ?>
        </tbody>
      </table>
    </div>
    <div id="paramEmpty" <?= !empty($parametres) ? 'style="display:none"' : '' ?> class="sv-empty">
      <i class="bi bi-thermometer"></i>Aucune mesure — cliquez "Prendre paramètres" pour démarrer le suivi
    </div>
  </div>

  <!-- ─── 1. Données de naissance ─────────── -->
  <?php if ($naissance): ?>
  <div class="sv-panel">
    <div class="sv-ph">
      <div class="sv-pt"><i class="bi bi-baby"></i> Données de naissance</div>
      <span class="sv-pbadge"><?= date('d/m/Y', strtotime($naissance['date_consultation'])) ?></span>
    </div>
    <div class="sv-birth">
      <?php foreach ([
        ['Poids naiss.', $naissance['poids_naissance'] ? number_format($naissance['poids_naissance']).' g' : null],
        ['Taille', $naissance['taille_naissance'] ? $naissance['taille_naissance'].' cm' : null],
        ['PC', $naissance['perimetre_cranien'] ? $naissance['perimetre_cranien'].' cm' : null],
        ['Âge gest.', $naissance['age_gestationnel_sa'] ? $naissance['age_gestationnel_sa'].' SA' : null],
        ['Terme', isset($termeLabels[$naissance['terme']??'']) ? $termeLabels[$naissance['terme']] : ($naissance['terme']??null)],
        ['Mode acc.', isset($modeLabels[$naissance['mode_accouchement']??'']) ? $modeLabels[$naissance['mode_accouchement']] : ($naissance['mode_accouchement']??null)],
      ] as [$lbl,$val]): if(!$val) continue; ?>
      <div class="sv-bi"><div class="sv-bl"><?= $lbl ?></div><div class="sv-bv"><?= htmlspecialchars($val) ?></div></div>
      <?php endforeach ?>
      <?php foreach ([1,5,10] as $min): $v = $naissance["apgar_$min"] ?? null; ?>
      <div class="sv-bi">
        <div class="sv-bl">Apgar <?= $min ?> min</div>
        <div class="sv-bv" style="color:<?= $apgarColor($v) ?>"><?= $v !== null ? "$v/10" : '—' ?></div>
      </div>
      <?php endforeach ?>
    </div>
    <?php if ($naissance['mere_nom']): ?>
    <div style="padding:10px 22px 16px;border-top:1px solid #e0f0f3;font-size:.82rem;color:#374151;">
      <strong style="color:#0e7490;">Mère :</strong>
      <?= htmlspecialchars(strtoupper($naissance['mere_nom']).' '.($naissance['mere_prenom']??'')) ?>
      <?php if ($naissance['mere_telephone']): ?> · <?= htmlspecialchars($naissance['mere_telephone']) ?><?php endif ?>
      <?php if ($naissance['mere_age']): ?> · <?= $naissance['mere_age'] ?> ans<?php endif ?>
      <?php if ($naissance['mere_antecedents']): ?><br><span style="color:#94a3b8;font-size:.75rem;">ATCD : <?= htmlspecialchars($naissance['mere_antecedents']) ?></span><?php endif ?>
    </div>
    <?php endif ?>
  </div>
  <?php endif ?>

  <!-- ─── 2. Constantes & Examens ─────────── -->
  <div class="sv-panel">
    <div class="sv-ph">
      <div class="sv-pt"><i class="bi bi-heart-pulse" style="color:#dc2626;"></i> Constantes &amp; Examens <span class="sv-pbadge ms-1"><?= count($examens) ?></span></div>
    </div>
    <?php if (empty($examens)): ?>
    <div class="sv-empty"><i class="bi bi-clipboard2-x"></i>Aucun examen enregistré</div>
    <?php else: ?>
    <div class="sv-tl">
      <?php foreach ($examens as $ex): ?>
      <div class="sv-ex">
        <div class="sv-ex-date">
          <i class="bi bi-calendar-check"></i>
          <?= date('d/m/Y à H:i', strtotime($ex['date_consultation'])) ?>
          <?php if ($ex['med_nom']): ?><span style="background:#e0f0f3;padding:2px 8px;border-radius:10px;color:#0e7490;font-size:.68rem;">Dr <?= htmlspecialchars($ex['med_prenom'].' '.$ex['med_nom']) ?></span><?php endif ?>
        </div>
        <div class="sv-vits">
          <?php $vitals = [
            [$ex['poids_actuel'] ? number_format($ex['poids_actuel']).'g' : null, 'Poids', false],
            [$ex['temperature'] !== null && $ex['temperature'] !== '' ? $ex['temperature'].'°C' : null, 'T°', $ex['temperature'] > 37.5 || $ex['temperature'] < 36.5],
            [$ex['freq_cardiaque'] ? $ex['freq_cardiaque'] : null, 'FC/min', $ex['freq_cardiaque'] > 160 || $ex['freq_cardiaque'] < 100],
            [$ex['freq_respiratoire'] ? $ex['freq_respiratoire'] : null, 'FR/min', $ex['freq_respiratoire'] > 60 || $ex['freq_respiratoire'] < 30],
            [$ex['spo2'] ? $ex['spo2'].'%' : null, 'SpO₂', $ex['spo2'] < 90],
            [$ex['glycemie'] ? $ex['glycemie'] : null, 'Glyc.', $ex['glycemie'] < 0.45],
          ];
          foreach ($vitals as [$val,$lbl,$warn]): if(!$val) continue; ?>
          <div class="sv-vit"><div class="v <?= $warn?'warn':'' ?>"><?= htmlspecialchars((string)$val) ?></div><div class="l"><?= $lbl ?></div></div>
          <?php endforeach ?>
          <?php if ($ex['alimentation']): ?><div class="sv-vit"><div class="v" style="font-size:.72rem;"><?= htmlspecialchars($ex['alimentation']) ?></div><div class="l">Alim.</div></div><?php endif ?>
          <?php if ($ex['ictere']): ?><div class="sv-vit" style="border-color:#fde68a;"><div class="v warn">Ictère</div><div class="l">Kramer <?= $ex['ictere_kramer']??'?' ?></div></div><?php endif ?>
        </div>
        <?php foreach ([['Diagnostic',$ex['diagnostic']],['Traitement',$ex['traitement']],['Conduite à tenir',$ex['conduite_tenir']]] as [$lbl,$txt]): if(!$txt) continue; ?>
        <div class="sv-diag"><strong><?= $lbl ?> :</strong> <?= nl2br(htmlspecialchars($txt)) ?></div>
        <?php endforeach ?>
      </div>
      <?php endforeach ?>
    </div>
    <?php endif ?>
  </div>

  <!-- ─── 3. Fiche de soins infirmiers ─────── -->
  <div class="sv-panel" id="section-soins">
    <div class="sv-ph">
      <div class="sv-pt"><i class="bi bi-file-medical" style="color:#7c3aed;"></i> Fiche de soins infirmiers <span class="sv-pbadge ms-1" style="background:#7c3aed;"><?= count($soins_infirmiers) ?></span></div>
    </div>

    <!-- Formulaire d'ajout -->
    <div class="sv-soin-form no-print">
      <div style="font-size:.78rem;font-weight:800;color:#7c3aed;margin-bottom:12px;display:flex;align-items:center;gap:6px;"><i class="bi bi-plus-circle-fill"></i> Nouveau soin</div>
      <form method="POST" action="<?= BASE_URL ?>neonatologie/soins-ajouter">
        <input type="hidden" name="patient_id" value="<?= (int)$patient['id'] ?>">
        <div class="sv-form-grid">
          <div>
            <div class="sv-form-lbl">Date</div>
            <input type="date" name="date_soin" class="sv-form-ctrl" value="<?= date('Y-m-d') ?>">
          </div>
          <div>
            <div class="sv-form-lbl">Heure</div>
            <input type="time" name="heure_soin" class="sv-form-ctrl" value="<?= date('H:i') ?>">
          </div>
          <div>
            <div class="sv-form-lbl">Plaintes</div>
            <textarea name="plaintes" class="sv-form-ctrl" rows="2" placeholder="Agitation, pleurs, dyspnée…"></textarea>
          </div>
          <div>
            <div class="sv-form-lbl">Besoins spécifiques</div>
            <textarea name="besoins_specifiques" class="sv-form-ctrl" rows="2" placeholder="Oxygène, réchauffement, surveillance…"></textarea>
          </div>
          <div>
            <div class="sv-form-lbl">Interventions</div>
            <textarea name="interventions" class="sv-form-ctrl" rows="2" placeholder="Administration médicament, sonde, aspiration…"></textarea>
          </div>
          <div>
            <div class="sv-form-lbl">Émargement</div>
            <input type="text" name="emargement" class="sv-form-ctrl" value="<?= htmlspecialchars($uNom) ?>" placeholder="Nom de l'infirmier(e)">
            <button type="submit" class="sv-form-submit">
              <i class="bi bi-check-circle-fill"></i> Enregistrer
            </button>
          </div>
        </div>
      </form>
    </div>

    <!-- Tableau des soins enregistrés -->
    <!-- En-tête patient pour impression -->
    <div style="display:none;" class="print-patient-info">
      <table style="width:100%;border-collapse:collapse;border:1px solid #000;margin-bottom:4px;">
        <tr>
          <td style="border:1px solid #000;padding:4px 8px;font-size:9pt;"><strong>Noms :</strong> <?= htmlspecialchars($nom) ?></td>
          <td style="border:1px solid #000;padding:4px 8px;font-size:9pt;"><strong>Prénoms :</strong> <?= htmlspecialchars($prenom) ?></td>
          <td style="border:1px solid #000;padding:4px 8px;font-size:9pt;"><strong>Service :</strong> <?= htmlspecialchars($serviceNom) ?></td>
          <td style="border:1px solid #000;padding:4px 8px;font-size:9pt;"><strong>Chambre :</strong> <?= htmlspecialchars($chambre) ?></td>
          <td style="border:1px solid #000;padding:4px 8px;font-size:9pt;"><strong>Lit :</strong> <?= htmlspecialchars($lit) ?></td>
        </tr>
      </table>
    </div>

    <?php if (empty($soins_infirmiers)): ?>
    <div class="sv-empty"><i class="bi bi-file-earmark-x"></i>Aucun soin enregistré — utilisez le formulaire ci-dessus</div>
    <?php else: ?>
    <div style="overflow-x:auto;">
      <table class="sv-tbl" id="soins-table">
        <thead>
          <tr>
            <th style="width:90px;">Date</th>
            <th style="width:70px;">Heure</th>
            <th>Plaintes</th>
            <th>Besoins spécifiques</th>
            <th>Interventions</th>
            <th style="width:140px;">Émargement</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($soins_infirmiers as $s): ?>
        <tr>
          <td style="font-weight:700;color:#0e7490;white-space:nowrap;"><?= date('d/m/Y', strtotime($s['date_soin'])) ?></td>
          <td style="font-weight:700;white-space:nowrap;"><?= substr($s['heure_soin'],0,5) ?></td>
          <td><?= nl2br(htmlspecialchars($s['plaintes'] ?? '—')) ?></td>
          <td><?= nl2br(htmlspecialchars($s['besoins_specifiques'] ?? '—')) ?></td>
          <td><?= nl2br(htmlspecialchars($s['interventions'] ?? '—')) ?></td>
          <td style="font-size:.78rem;color:#64748b;">
            <?= htmlspecialchars($s['emargement'] ?? ($s['inf_prenom'].' '.$s['inf_nom'] ?: '—')) ?>
          </td>
        </tr>
        <?php endforeach ?>
        </tbody>
      </table>
    </div>
    <?php endif ?>
  </div>

  <?php $admId = $patient['admission_id'] ?? null; ?>

  <!-- ─── 4. Gavages ─────────────────────── -->
  <div class="sv-panel">
    <div class="sv-ph">
      <div class="sv-pt"><i class="bi bi-droplet-fill" style="color:#b45309;"></i> Gavages <span class="sv-pbadge ms-1" style="background:#b45309;"><?= count($gavages) ?></span></div>
      <a href="<?= BASE_URL ?>neonatologie/gavage/<?= (int)$patient['id'] ?>" class="sv-btn no-print" style="font-size:.72rem;padding:5px 12px;color:#fff;"><i class="bi bi-plus"></i> Ajouter</a>
    </div>
    <?php if (empty($gavages)): ?>
    <div class="sv-empty"><i class="bi bi-droplet"></i>Aucun gavage enregistré</div>
    <?php else: ?>
    <div style="overflow-x:auto;">
      <table class="sv-tbl">
        <thead><tr>
          <th>Date</th><th>Heure</th><th>Poids (kg)</th>
          <th>Lait maternel (ml)</th><th>Lait artificiel (ml)</th>
          <th>Résidus</th><th>Infirmier(e)</th>
        </tr></thead>
        <tbody>
        <?php foreach ($gavages as $g): ?>
        <tr>
          <td style="font-weight:700;color:#b45309;"><?= date('d/m/Y', strtotime($g['date_gavage'])) ?></td>
          <td><?= substr($g['heure_gavage'],0,5) ?></td>
          <td><?= $g['poids_jour'] !== null ? number_format((float)$g['poids_jour'],3) : '—' ?></td>
          <td><?= $g['lait_maternel_ml'] !== null ? $g['lait_maternel_ml'] : '—' ?></td>
          <td><?= $g['lait_artificiel_ml'] !== null ? $g['lait_artificiel_ml'] : '—' ?></td>
          <td><?= htmlspecialchars($g['residus'] ?? '—') ?></td>
          <td style="font-size:.78rem;"><?= $g['inf_nom'] ? htmlspecialchars($g['inf_prenom'].' '.$g['inf_nom']) : '—' ?></td>
        </tr>
        <?php endforeach ?>
        </tbody>
      </table>
    </div>
    <?php endif ?>
  </div>

  <!-- ─── 5. Observations médicales ────────── -->
  <div class="sv-panel">
    <div class="sv-ph">
      <div class="sv-pt"><i class="bi bi-journal-medical" style="color:#1d4ed8;"></i> Observations médicales <span class="sv-pbadge ms-1" style="background:#1d4ed8;"><?= count($observations) ?></span></div>
      <a href="<?= BASE_URL ?>neonatologie/observation/<?= (int)$patient['id'] ?>" class="sv-btn no-print" style="font-size:.72rem;padding:5px 12px;color:#fff;"><i class="bi bi-plus"></i> Ajouter</a>
    </div>
    <?php if (empty($observations)): ?>
    <div class="sv-empty"><i class="bi bi-journal-x"></i>Aucune observation enregistrée</div>
    <?php else: ?>
    <?php foreach ($observations as $obs): ?>
    <div class="sv-obs">
      <div class="sv-obs-hd">
        <i class="bi bi-calendar-check me-1"></i><?= date('d/m/Y à H:i', strtotime($obs['date_observation'])) ?>
        <?php if ($obs['med_nom']): ?> · Dr <?= htmlspecialchars($obs['med_prenom'].' '.$obs['med_nom']) ?><?php endif ?>
      </div>
      <?php if ($obs['motif']): ?><div class="sv-obs-motif"><?= htmlspecialchars($obs['motif']) ?></div><?php endif ?>
      <?php if ($obs['diagnostic_principal']): ?><div class="sv-obs-txt"><?= nl2br(htmlspecialchars($obs['diagnostic_principal'])) ?></div><?php endif ?>
    </div>
    <?php endforeach ?>
    <?php endif ?>
  </div>

  <!-- ─── 5b. Soins planifiés (exécution nursing) ─── -->
  <?php
    $nb_planifies = count($soins_executions);
    $nb_afaire    = count(array_filter($soins_executions, fn($s) => !in_array($s['statut'], ['REALISE','ANNULE'])));
    $nb_realises  = count(array_filter($soins_executions, fn($s) => $s['statut'] === 'REALISE'));

    $typeCols = [
      'MEDICAMENT'   => ['bg'=>'#ede9fe','c'=>'#6d28d9','lbl'=>'Médicament'],
      'PERFUSION'    => ['bg'=>'#dbeafe','c'=>'#1d4ed8','lbl'=>'Perfusion'],
      'PANSEMENT'    => ['bg'=>'#fef3c7','c'=>'#b45309','lbl'=>'Pansement'],
      'NURSING'      => ['bg'=>'#d1fae5','c'=>'#065f46','lbl'=>'Nursing'],
      'EXAMEN'       => ['bg'=>'#fce7f3','c'=>'#9d174d','lbl'=>'Examen'],
      'KINESITHERAPIE'=>['bg'=>'#e0f2fe','c'=>'#0369a1','lbl'=>'Kiné'],
      'AUTRE'        => ['bg'=>'#f3f4f6','c'=>'#374151','lbl'=>'Autre'],
    ];
    $statutCols = [
      'PLANIFIE' =>['bg'=>'#dbeafe','c'=>'#1d4ed8','lbl'=>'Planifié'],
      'EN_COURS' =>['bg'=>'#fef9c3','c'=>'#92400e','lbl'=>'En cours'],
      'REALISE'  =>['bg'=>'#d1fae5','c'=>'#065f46','lbl'=>'Réalisé'],
      'RETARD'   =>['bg'=>'#fee2e2','c'=>'#991b1b','lbl'=>'Retard'],
      'ANNULE'   =>['bg'=>'#f3f4f6','c'=>'#6b7280','lbl'=>'Annulé'],
    ];
  ?>
  <div class="sv-panel" id="section-soins-executes">
    <div class="sv-ph">
      <div class="sv-pt">
        <i class="bi bi-clipboard2-pulse-fill" style="color:#059669;"></i>
        Soins planifiés
        <span class="sv-pbadge ms-1" style="background:#059669;"><?= $nb_planifies ?></span>
        <?php if ($nb_afaire > 0): ?>
        <span class="sv-pbadge ms-1" style="background:#f59e0b;"><?= $nb_afaire ?> à faire</span>
        <?php endif ?>
      </div>
      <div style="display:flex;gap:6px;" class="no-print">
        <?php if ($admId ?? null): ?>
        <a href="<?= BASE_URL ?>hospitalisation/executer-soins/<?= (int)($admId ?? 0) ?>"
           class="sv-btn" style="font-size:.72rem;padding:5px 14px;color:#fff;
                  background:linear-gradient(135deg,#059669,#10b981);text-decoration:none;">
          <i class="bi bi-play-circle-fill me-1"></i>Exécuter
        </a>
        <?php endif ?>
        <a href="<?= BASE_URL ?>hospitalisation/planifier-soins/<?= (int)$patient['id'] ?>"
           class="sv-btn" style="font-size:.72rem;padding:5px 12px;color:#fff;text-decoration:none;">
          <i class="bi bi-plus"></i> Planifier
        </a>
      </div>
    </div>

    <?php if (empty($soins_executions)): ?>
    <div class="sv-empty"><i class="bi bi-calendar2-x"></i>Aucun soin planifié pour ce patient</div>
    <?php else: ?>

    <!-- Filtres -->
    <div class="se-filter no-print">
      <button class="se-ftab active" onclick="seFilter('tous',this)">Tous (<?= $nb_planifies ?>)</button>
      <button class="se-ftab" onclick="seFilter('afaire',this)">À faire (<?= $nb_afaire ?>)</button>
      <button class="se-ftab" onclick="seFilter('realise',this)">Réalisés (<?= $nb_realises ?>)</button>
    </div>

    <!-- Liste -->
    <div class="se-list" id="seList">
      <?php foreach ($soins_executions as $se):
        $tc     = $typeCols[$se['type_soin']] ?? $typeCols['AUTRE'];
        $sc     = $statutCols[$se['statut']]  ?? $statutCols['PLANIFIE'];
        $isRealise = $se['statut'] === 'REALISE';
        $isRetard  = !$isRealise && !empty($se['date_prevue']) && strtotime($se['date_prevue']) < time();
        $itemCls   = $isRealise ? 'realise' : ($isRetard ? 'retard' : '');
        $dateFmt   = !empty($se['date_prevue'])
                       ? date('d/m H:i', strtotime($se['date_prevue']))
                       : '—';
        $execInfo  = $isRealise
          ? (date('d/m/Y H:i', strtotime($se['date_realisee'] ?? 'now'))
             . (!empty($se['exec_nom']) ? ' · ' . htmlspecialchars($se['exec_prenom'].' '.$se['exec_nom']) : ''))
          : '';
        $afaire = !$isRealise && $se['statut'] !== 'ANNULE';
      ?>
      <div class="se-item <?= $itemCls ?>"
           data-filtre="<?= $isRealise ? 'realise' : 'afaire' ?>">
        <div class="se-item-hd">
          <!-- Type -->
          <span class="se-type-badge"
                style="background:<?= $tc['bg'] ?>;color:<?= $tc['c'] ?>;">
            <?= htmlspecialchars($tc['lbl']) ?>
          </span>
          <!-- Description -->
          <span class="se-desc">
            <?= htmlspecialchars($se['description'] ?? $se['type_soin']) ?>
            <?php if (!empty($se['condition_application'])): ?>
            <span style="display:inline-block;margin-left:6px;background:#fff7ed;color:#c2410c;
                         border:1px solid #fed7aa;border-radius:12px;padding:1px 8px;
                         font-size:.62rem;font-weight:800;vertical-align:middle;">
              <i class="bi bi-lightning-fill me-1"></i><?= htmlspecialchars($se['condition_application']) ?>
            </span>
            <?php endif ?>
          </span>
          <!-- Heure prévue -->
          <span class="se-heure"><i class="bi bi-clock me-1"></i><?= $dateFmt ?></span>
          <!-- Statut -->
          <span class="se-stat"
                style="background:<?= $isRetard ? '#fef3c7' : $sc['bg'] ?>;color:<?= $isRetard ? '#b45309' : $sc['c'] ?>;">
            <?= $isRetard ? 'Retard' : htmlspecialchars($sc['lbl']) ?>
          </span>
          <!-- Actions modifier / supprimer (PLANIFIE seulement) -->
          <?php if ($afaire && $se['statut'] === 'PLANIFIE'): ?>
          <button class="no-print" title="Modifier ce soin"
                  onclick="se5OpenEdit(<?= (int)$se['id'] ?>, '<?= addslashes($se['type_soin']) ?>', '<?= addslashes($se['description'] ?? '') ?>', '<?= !empty($se['date_prevue']) ? date('H:i', strtotime($se['date_prevue'])) : '' ?>', '<?= addslashes($se['condition_application'] ?? '') ?>')"
                  style="background:#f0f9ff;border:1.5px solid #bae6fd;color:#0369a1;border-radius:8px;
                         padding:4px 10px;font-size:.72rem;font-weight:700;cursor:pointer;white-space:nowrap;">
            <i class="bi bi-pencil-fill"></i>
          </button>
          <button class="no-print" title="Supprimer ce soin"
                  onclick="se5OpenDelete(<?= (int)$se['id'] ?>, '<?= addslashes($se['description'] ?? $se['type_soin']) ?>')"
                  style="background:#fef2f2;border:1.5px solid #fca5a5;color:#dc2626;border-radius:8px;
                         padding:4px 10px;font-size:.72rem;font-weight:700;cursor:pointer;white-space:nowrap;">
            <i class="bi bi-trash3-fill"></i>
          </button>
          <?php endif ?>
          <!-- Bouton marquer réalisé -->
          <?php if ($afaire): ?>
          <button class="se-exec-btn no-print"
                  onclick="seToggleNote(<?= (int)$se['id'] ?>)">
            <i class="bi bi-check2-circle"></i> Marquer réalisé
          </button>
          <?php elseif ($isRealise): ?>
          <span class="se-exec-info no-print">
            <i class="bi bi-check-circle-fill" style="color:#16a34a;"></i>
            <?= htmlspecialchars($execInfo) ?>
          </span>
          <?php endif ?>
        </div>

        <!-- Note planificateur -->
        <?php if (!empty($se['note_major'])): ?>
        <div style="padding:6px 16px 8px;font-size:.75rem;color:#64748b;background:#f8fafc;border-top:1px solid #f1f5f9;">
          <i class="bi bi-sticky me-1"></i><?= htmlspecialchars($se['note_major']) ?>
        </div>
        <?php endif ?>

        <!-- Note exécution déjà enregistrée -->
        <?php if (!empty($se['note_execution'])): ?>
        <div style="padding:6px 16px 8px;font-size:.75rem;color:#15803d;background:#f0fdf4;border-top:1px solid #bbf7d0;">
          <i class="bi bi-pencil me-1"></i><?= htmlspecialchars($se['note_execution']) ?>
        </div>
        <?php endif ?>

        <!-- Zone saisie note + confirmation (masquée par défaut) -->
        <?php if ($afaire): ?>
        <div class="se-note-zone" id="seNote_<?= (int)$se['id'] ?>">
          <input type="text" class="se-note-input"
                 id="seNoteInput_<?= (int)$se['id'] ?>"
                 placeholder="Note d'exécution (facultatif)…">
          <button class="se-note-confirm"
                  onclick="seExecuter(<?= (int)$se['id'] ?>, <?= (int)$patient['id'] ?>)">
            <i class="bi bi-check-lg me-1"></i>Confirmer
          </button>
        </div>
        <?php endif ?>
      </div>
      <?php endforeach ?>
    </div>

    <?php endif ?>
  </div>

  <!-- ═══ MODAL : Modifier soin ═══ -->
  <div id="se5ModalEdit" style="display:none;position:fixed;inset:0;z-index:9000;background:rgba(15,23,42,.55);
       align-items:center;justify-content:center;padding:16px;" class="no-print">
    <div style="background:#fff;border-radius:18px;width:100%;max-width:480px;box-shadow:0 24px 80px rgba(0,0,0,.22);overflow:hidden;">
      <div style="padding:18px 22px 14px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between;">
        <div style="font-size:.95rem;font-weight:900;color:#0f172a;"><i class="bi bi-pencil-fill me-2" style="color:#0369a1;"></i>Modifier le soin</div>
        <button onclick="se5CloseEdit()" style="background:none;border:none;font-size:1.2rem;color:#94a3b8;cursor:pointer;">&times;</button>
      </div>
      <div style="padding:20px 22px;">
        <input type="hidden" id="se5EditId">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px;">
          <div>
            <div style="font-size:.69rem;font-weight:800;color:#64748b;text-transform:uppercase;margin-bottom:5px;">Type</div>
            <select id="se5EditType" style="width:100%;border:1.5px solid #e2e8f0;border-radius:10px;padding:8px 10px;font-size:.83rem;">
              <option value="MEDICAMENT">Médicament</option>
              <option value="PERFUSION">Perfusion</option>
              <option value="PANSEMENT">Pansement</option>
              <option value="NURSING">Nursing</option>
              <option value="EXAMEN">Examen</option>
              <option value="KINESITHERAPIE">Kiné</option>
              <option value="AUTRE">Autre</option>
            </select>
          </div>
          <div>
            <div style="font-size:.69rem;font-weight:800;color:#64748b;text-transform:uppercase;margin-bottom:5px;">Heure prévue</div>
            <input type="time" id="se5EditHeure" style="width:100%;border:1.5px solid #e2e8f0;border-radius:10px;padding:8px 10px;font-size:.83rem;">
          </div>
        </div>
        <div style="margin-bottom:14px;">
          <div style="font-size:.69rem;font-weight:800;color:#64748b;text-transform:uppercase;margin-bottom:5px;">Description <span style="color:#ef4444;">*</span></div>
          <input type="text" id="se5EditDesc" placeholder="Ex: Gentamicine 2mg IV lente…"
                 style="width:100%;border:1.5px solid #e2e8f0;border-radius:10px;padding:9px 12px;font-size:.85rem;">
        </div>
        <div style="margin-bottom:18px;">
          <div style="font-size:.69rem;font-weight:800;color:#f97316;text-transform:uppercase;margin-bottom:5px;">
            <i class="bi bi-lightning-fill me-1"></i>Condition d'application <span style="color:#94a3b8;font-weight:600;text-transform:none;">(optionnel)</span>
          </div>
          <input type="text" id="se5EditCond" placeholder="Ex : si T° > 38°C, si glycémie < 0,45 g/L…"
                 style="width:100%;border:1.5px solid #fed7aa;border-radius:10px;padding:9px 12px;font-size:.85rem;background:#fff7ed;">
        </div>
        <div id="se5EditMsg" style="display:none;margin-bottom:10px;padding:8px 12px;border-radius:8px;font-size:.78rem;font-weight:700;"></div>
        <div style="display:flex;gap:10px;justify-content:flex-end;">
          <button onclick="se5CloseEdit()" type="button"
                  style="padding:9px 20px;border-radius:10px;border:1.5px solid #e2e8f0;background:#fff;font-size:.8rem;font-weight:700;color:#64748b;cursor:pointer;">
            Annuler
          </button>
          <button onclick="se5SaveEdit()" type="button" id="se5EditSaveBtn"
                  style="padding:9px 22px;border-radius:10px;border:none;background:linear-gradient(135deg,#0369a1,#0891b2);
                         color:#fff;font-size:.82rem;font-weight:800;cursor:pointer;display:flex;align-items:center;gap:7px;">
            <i class="bi bi-check2-circle"></i> Enregistrer
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- ═══ MODAL : Supprimer soin ═══ -->
  <div id="se5ModalDel" style="display:none;position:fixed;inset:0;z-index:9000;background:rgba(15,23,42,.55);
       align-items:center;justify-content:center;padding:16px;" class="no-print">
    <div style="background:#fff;border-radius:18px;width:100%;max-width:400px;box-shadow:0 24px 80px rgba(0,0,0,.22);overflow:hidden;">
      <div style="padding:18px 22px 14px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between;">
        <div style="font-size:.95rem;font-weight:900;color:#dc2626;"><i class="bi bi-trash3-fill me-2"></i>Supprimer le soin</div>
        <button onclick="se5CloseDel()" style="background:none;border:none;font-size:1.2rem;color:#94a3b8;cursor:pointer;">&times;</button>
      </div>
      <div style="padding:20px 22px;">
        <input type="hidden" id="se5DelId">
        <p style="font-size:.85rem;color:#475569;margin-bottom:14px;">
          Suppression de : <strong id="se5DelDesc" style="color:#0f172a;"></strong><br>
          <span style="font-size:.78rem;color:#94a3b8;">Action irréversible — uniquement pour les soins PLANIFIÉS.</span>
        </p>
        <div style="margin-bottom:16px;">
          <div style="font-size:.69rem;font-weight:800;color:#dc2626;text-transform:uppercase;margin-bottom:5px;">Motif de suppression <span style="color:#ef4444;">*</span></div>
          <input type="text" id="se5DelMotif" placeholder="Ex : doublon, erreur de saisie, annulation médicale…"
                 style="width:100%;border:1.5px solid #fca5a5;border-radius:10px;padding:9px 12px;font-size:.85rem;background:#fef2f2;">
        </div>
        <div id="se5DelMsg" style="display:none;margin-bottom:10px;padding:8px 12px;border-radius:8px;font-size:.78rem;font-weight:700;"></div>
        <div style="display:flex;gap:10px;justify-content:flex-end;">
          <button onclick="se5CloseDel()" type="button"
                  style="padding:9px 20px;border-radius:10px;border:1.5px solid #e2e8f0;background:#fff;font-size:.8rem;font-weight:700;color:#64748b;cursor:pointer;">
            Annuler
          </button>
          <button onclick="se5ConfirmDel()" type="button" id="se5DelBtn"
                  style="padding:9px 22px;border-radius:10px;border:none;background:linear-gradient(135deg,#dc2626,#ef4444);
                         color:#fff;font-size:.82rem;font-weight:800;cursor:pointer;display:flex;align-items:center;gap:7px;">
            <i class="bi bi-trash3-fill"></i> Supprimer
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- ─── 6. Post-natale ────────────────────── -->
  <div class="sv-panel">
    <div class="sv-ph">
      <div class="sv-pt"><i class="bi bi-clipboard2-check" style="color:#15803d;"></i> Consultation Post-natale</div>
      <a href="<?= BASE_URL ?>neonatologie/post-natale/<?= (int)$patient['id'] ?>" class="sv-btn no-print" style="font-size:.72rem;padding:5px 12px;color:#fff;">
        <i class="bi bi-<?= $post_natale ? 'pencil' : 'plus' ?>"></i> <?= $post_natale ? 'Modifier' : 'Créer' ?>
      </a>
    </div>
    <?php if (!$post_natale): ?>
    <div class="sv-empty"><i class="bi bi-clipboard2-x"></i>Aucune consultation post-natale enregistrée</div>
    <?php else: ?>
    <div class="sv-pn">
      <div style="font-size:.8rem;font-weight:700;color:#15803d;">
        <?= date('d/m/Y', strtotime($post_natale['date_examen'] ?? $post_natale['created_at'])) ?>
        <?php if ($post_natale['med_nom']): ?> · Dr <?= htmlspecialchars($post_natale['med_prenom'].' '.$post_natale['med_nom']) ?><?php endif ?>
        <?php if ($post_natale['decision']): ?> · <strong><?= htmlspecialchars($post_natale['decision']) ?></strong><?php endif ?>
      </div>
      <div class="sv-pn-grid">
        <?php if ($post_natale['poids_sortie']): ?>
        <div class="sv-pni"><div class="sv-pni-l">Poids sortie</div><div class="sv-pni-v"><?= number_format($post_natale['poids_sortie']) ?> g</div></div>
        <?php endif ?>
        <?php if ($post_natale['date_rdv1']): ?>
        <div class="sv-pni"><div class="sv-pni-l">RDV PMI</div><div class="sv-pni-v"><?= date('d/m/Y', strtotime($post_natale['date_rdv1'])) ?></div></div>
        <?php endif ?>
      </div>
      <?php if ($post_natale['conclusion']): ?>
      <div style="margin-top:12px;font-size:.83rem;color:#374151;line-height:1.6;padding:10px 14px;background:#f0fdf4;border-radius:10px;">
        <strong style="color:#15803d;">Conclusion :</strong> <?= nl2br(htmlspecialchars($post_natale['conclusion'])) ?>
      </div>
      <?php endif ?>
    </div>
    <?php endif ?>
  </div>

</div><!-- /sv-wrap -->

<!-- ── Modal paramètres ─────────────────────────────────── -->
<div class="sv-modal-overlay" id="modalParams" onclick="if(event.target===this)closeModal()">
  <div class="sv-modal">
    <div class="sv-modal-hd">
      <div class="sv-modal-title"><i class="bi bi-thermometer-half"></i> Prendre les paramètres</div>
      <button class="sv-modal-close" onclick="closeModal()">×</button>
    </div>
    <div class="sv-modal-body">
      <form id="formParams">
        <input type="hidden" name="patient_id" value="<?= (int)$patient['id'] ?>">
        <div class="sv-modal-grid" style="margin-bottom:14px;">
          <div>
            <div class="sv-modal-lbl">Date</div>
            <input type="date" name="date_mesure" class="sv-modal-ctrl" value="<?= date('Y-m-d') ?>">
          </div>
          <div>
            <div class="sv-modal-lbl">Heure</div>
            <input type="time" name="heure_mesure" class="sv-modal-ctrl" value="<?= date('H:i') ?>">
          </div>
        </div>
        <div class="sv-modal-grid">
          <div>
            <div class="sv-modal-lbl" style="color:#ef4444;">🌡 Température</div>
            <input type="number" name="temperature" class="sv-modal-ctrl" placeholder="36.5" step="0.1" min="30" max="43">
            <div class="sv-modal-hint">Normale : 36.5 – 37.5 °C</div>
          </div>
          <div>
            <div class="sv-modal-lbl" style="color:#7c3aed;">⚖ Poids</div>
            <input type="number" name="poids" class="sv-modal-ctrl" placeholder="1360" min="300" max="8000">
            <div class="sv-modal-hint">En grammes</div>
          </div>
          <div>
            <div class="sv-modal-lbl" style="color:#2563eb;">🫁 Saturation SpO₂</div>
            <input type="number" name="saturation" class="sv-modal-ctrl" placeholder="98" step="0.1" min="50" max="100">
            <div class="sv-modal-hint">Normale : ≥ 90 %</div>
          </div>
          <div>
            <div class="sv-modal-lbl" style="color:#d97706;">🩸 Glycémie capillaire</div>
            <input type="number" name="glycemie" class="sv-modal-ctrl" placeholder="0.80" step="0.01" min="0" max="5">
            <div class="sv-modal-hint">En g/L · Normale : 0.45 – 1.26</div>
          </div>
        </div>
        <button type="submit" class="sv-modal-submit" id="btnSubmitParams">
          <i class="bi bi-check-circle-fill"></i> Enregistrer les paramètres
        </button>
      </form>
    </div>
  </div>
</div>

<!-- ── Toast ────────────────────────────────────────────── -->
<div class="sv-toast" id="toastMsg"></div>

<!-- ── Chart.js ─────────────────────────────────────────── -->
<script src="<?= BASE_URL ?>public/js/chart.umd.min.js"></script>
<script>
// ── Données PHP ───────────────────────────────────────────
const PHP_PARAMS = <?= json_encode(array_values($parametres ?? []), JSON_UNESCAPED_UNICODE) ?>;
const BASE_URL_JS = '<?= BASE_URL ?>';

// ── Modal ─────────────────────────────────────────────────
function openParamsModal() {
  document.getElementById('modalParams').classList.add('show');
  const t = document.querySelector('[name=heure_mesure]');
  if (t) t.value = new Date().toTimeString().substring(0,5);
}
function closeModal() {
  document.getElementById('modalParams').classList.remove('show');
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });

// ── Toast ─────────────────────────────────────────────────
let _toastTmr;
function showToast(msg, type = 'ok') {
  const el = document.getElementById('toastMsg');
  el.className = `sv-toast ${type}`;
  el.innerHTML = `<i class="bi bi-${type==='ok'?'check-circle-fill':'exclamation-circle-fill'}"></i> ${msg}`;
  el.style.display = 'flex';
  clearTimeout(_toastTmr);
  _toastTmr = setTimeout(() => { el.style.display = 'none'; }, 3800);
}

// ── Chart.js plugin : lignes de référence ─────────────────
const refLinePlugin = {
  id: 'refLine',
  beforeDraw(chart) {
    const { ctx, chartArea, scales } = chart;
    if (!chartArea) return;
    (chart.config.options._refLines || []).forEach(({ y, color, label }) => {
      const yPx = scales.y.getPixelForValue(y);
      if (yPx < chartArea.top || yPx > chartArea.bottom) return;
      ctx.save();
      ctx.strokeStyle = color || 'rgba(220,38,38,.55)';
      ctx.lineWidth = 1.5;
      ctx.setLineDash([5, 4]);
      ctx.beginPath();
      ctx.moveTo(chartArea.left, yPx);
      ctx.lineTo(chartArea.right, yPx);
      ctx.stroke();
      if (label) {
        ctx.fillStyle = color || 'rgba(220,38,38,.9)';
        ctx.font = '9px Segoe UI,system-ui,sans-serif';
        ctx.fillText(label, chartArea.left + 4, yPx - 3);
      }
      ctx.restore();
    });
  }
};
Chart.register(refLinePlugin);

// ── Helpers ───────────────────────────────────────────────
function makeLabels(data) {
  return data.map(p => {
    const d = (p.date_mesure || '').substring(5).replace('-', '/');
    return d + ' ' + (p.heure_mesure || '').substring(0, 5);
  });
}
function makeVals(data, field) {
  return data.map(p => (p[field] !== null && p[field] !== undefined) ? parseFloat(p[field]) : null);
}

// ── Fabrique un chart ─────────────────────────────────────
function createChart(id, data, field, color, refLines, sugMin, sugMax) {
  const ctx = document.getElementById(id);
  if (!ctx) return null;
  const vals = makeVals(data, field);
  return new Chart(ctx, {
    type: 'line',
    data: {
      labels: makeLabels(data),
      datasets: [{
        data: vals,
        borderColor: color,
        backgroundColor: color.replace('1)', '0.08)'),
        fill: true,
        tension: 0.4,
        pointRadius: vals.map(v => v === null ? 0 : 5),
        pointHoverRadius: 7,
        pointBackgroundColor: color,
        spanGaps: true,
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: true,
      plugins: {
        legend: { display: false },
        tooltip: {
          callbacks: {
            title: items => {
              const p = data[items[0]?.dataIndex];
              if (!p) return '';
              return (p.date_mesure || '').split('-').reverse().join('/') + ' à ' + (p.heure_mesure || '').substring(0, 5);
            },
            afterBody: items => {
              const p = data[items[0]?.dataIndex];
              if (!p || !p.user_nom) return [];
              return ['Relevé par : ' + (p.user_prenom || '') + ' ' + p.user_nom];
            }
          }
        }
      },
      scales: {
        y: { suggestedMin: sugMin, suggestedMax: sugMax, ticks: { font: { size: 10 }, maxTicksLimit: 6 }, grid: { color: 'rgba(0,0,0,.05)' } },
        x: { ticks: { maxRotation: 45, font: { size: 9 }, maxTicksLimit: 8 }, grid: { display: false } }
      },
      _refLines: refLines
    },
    plugins: [refLinePlugin]
  });
}

// ── Init 4 charts ─────────────────────────────────────────
let chartTemp     = createChart('chartTemp',     PHP_PARAMS, 'temperature', 'rgba(239,68,68,1)',  [{y:36.5,color:'rgba(251,146,60,.7)',label:'36.5°'},{y:37.5,color:'rgba(251,146,60,.7)',label:'37.5°'}], 34, 41);
let chartPoids    = createChart('chartPoids',    PHP_PARAMS, 'poids',       'rgba(124,58,237,1)', [], null, null);
let chartSpo2     = createChart('chartSpo2',     PHP_PARAMS, 'saturation',  'rgba(37,99,235,1)',  [{y:90,color:'rgba(220,38,38,.6)',label:'90%'}], 70, 100);
let chartGlycemie = createChart('chartGlycemie', PHP_PARAMS, 'glycemie',    'rgba(217,119,6,1)',  [{y:0.45,color:'rgba(220,38,38,.6)',label:'0.45'},{y:1.26,color:'rgba(220,38,38,.6)',label:'1.26'}], 0, 2);

// ── Mise à jour des 4 charts ──────────────────────────────
function updateCharts(params) {
  const labs = makeLabels(params);
  [[chartTemp,'temperature'],[chartPoids,'poids'],[chartSpo2,'saturation'],[chartGlycemie,'glycemie']]
    .forEach(([chart, field]) => {
      if (!chart) return;
      const vals = makeVals(params, field);
      chart.data.labels = labs;
      chart.data.datasets[0].data = vals;
      chart.data.datasets[0].pointRadius = vals.map(v => v === null ? 0 : 5);
      chart.update('active');
    });
}

// ── Mise à jour du tableau ────────────────────────────────
function updateTable(params) {
  const tbody = document.getElementById('paramTableBody');
  const wrap  = document.getElementById('paramTableWrap');
  const empty = document.getElementById('paramEmpty');
  if (!tbody) return;
  const sorted = [...params].reverse();
  tbody.innerHTML = sorted.map(pv => {
    const t  = pv.temperature !== null ? parseFloat(pv.temperature) : null;
    const s  = pv.saturation  !== null ? parseFloat(pv.saturation)  : null;
    const g  = pv.glycemie    !== null ? parseFloat(pv.glycemie)    : null;
    const d  = (pv.date_mesure||'').split('-').reverse().join('/');
    const h  = (pv.heure_mesure||'').substring(0,5);
    const who = pv.user_nom ? (pv.user_prenom||'') + ' ' + pv.user_nom : '—';
    return `<tr>
      <td style="font-weight:700;color:#7c3aed;white-space:nowrap;">${d}</td>
      <td style="white-space:nowrap;">${h}</td>
      <td>${t!==null?`<span style="font-weight:800;color:${t<36.5||t>37.5?'#dc2626':'#16a34a'}">${t}</span>`:'—'}</td>
      <td>${pv.poids!==null?parseInt(pv.poids).toLocaleString('fr-FR')+' g':'—'}</td>
      <td>${s!==null?`<span style="font-weight:800;color:${s<90?'#dc2626':'#16a34a'}">${s}%</span>`:'—'}</td>
      <td>${g!==null?`<span style="font-weight:800;color:${g<0.45||g>1.26?'#dc2626':'#16a34a'}">${g}</span>`:'—'}</td>
      <td style="font-size:.78rem;color:#64748b;">${who}</td>
    </tr>`;
  }).join('');
  if (wrap)  wrap.style.display  = params.length ? '' : 'none';
  if (empty) empty.style.display = params.length ? 'none' : '';
}

// ── Soumission AJAX ───────────────────────────────────────
document.getElementById('formParams')?.addEventListener('submit', async e => {
  e.preventDefault();
  const btn = document.getElementById('btnSubmitParams');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Enregistrement…';
  try {
    const res  = await fetch(BASE_URL_JS + 'neonatologie/parametres-ajouter', {
      method : 'POST',
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      body   : new FormData(e.target)
    });
    const json = await res.json();
    if (json.ok) {
      closeModal();
      updateCharts(json.parametres);
      updateTable(json.parametres);
      // badges
      const n = json.parametres.length;
      const badge = document.getElementById('badgeParams');
      const chip  = document.getElementById('chipMesures');
      if (badge) badge.textContent = n;
      if (chip)  chip.innerHTML = `<i class="bi bi-thermometer-half"></i>${n} mesure(s)`;
      showToast('Paramètres enregistrés !', 'ok');
      // reset saisie
      ['temperature','poids','saturation','glycemie'].forEach(f => {
        const inp = e.target.querySelector(`[name="${f}"]`);
        if (inp) inp.value = '';
      });
    } else {
      showToast(json.msg || 'Erreur lors de l\'enregistrement.', 'err');
    }
  } catch {
    showToast('Erreur de connexion.', 'err');
  } finally {
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-check-circle-fill"></i> Enregistrer les paramètres';
  }
});

// ── Impression ────────────────────────────────────────────
window.addEventListener('beforeprint', () => {
  document.querySelectorAll('.print-patient-info').forEach(el => el.style.display = 'block');
});
window.addEventListener('afterprint', () => {
  document.querySelectorAll('.print-patient-info').forEach(el => el.style.display = 'none');
});

// ── Scroll to section via hash ────────────────────────────
const hashMap = { '#soins': 'section-soins', '#parametres': 'section-parametres', '#soins-executes': 'section-soins-executes' };
const target  = hashMap[location.hash];
if (target) document.getElementById(target)?.scrollIntoView({ behavior: 'smooth' });

// ── Modifier soin planifié ────────────────────────────────
function se5OpenEdit(id, type, desc, heure, cond) {
    document.getElementById('se5EditId').value    = id;
    document.getElementById('se5EditType').value  = type;
    document.getElementById('se5EditDesc').value  = desc;
    document.getElementById('se5EditHeure').value = heure;
    document.getElementById('se5EditCond').value  = cond;
    document.getElementById('se5EditMsg').style.display = 'none';
    document.getElementById('se5ModalEdit').style.display = 'flex';
}
function se5CloseEdit() {
    document.getElementById('se5ModalEdit').style.display = 'none';
}
async function se5SaveEdit() {
    const id   = document.getElementById('se5EditId').value;
    const desc = document.getElementById('se5EditDesc').value.trim();
    const heure= document.getElementById('se5EditHeure').value.trim();
    const type = document.getElementById('se5EditType').value;
    const cond = document.getElementById('se5EditCond').value.trim();
    if (!desc) { se5Msg('se5EditMsg','La description est obligatoire.', false); return; }
    if (!heure){ se5Msg('se5EditMsg','L\'heure est obligatoire.', false); return; }
    const btn = document.getElementById('se5EditSaveBtn');
    btn.disabled = true; btn.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>Enregistrement…';
    const fd = new FormData();
    fd.append('description', desc); fd.append('heure', heure);
    fd.append('type_soin', type);   fd.append('condition_application', cond);
    try {
        const res  = await fetch('<?= BASE_URL ?>hospitalisation/modifier-soin/' + id, { method:'POST', body:fd });
        const json = await res.json();
        if (json.success) {
            se5Msg('se5EditMsg', json.message, true);
            setTimeout(() => { se5CloseEdit(); location.reload(); }, 1200);
        } else {
            se5Msg('se5EditMsg', json.message || 'Erreur.', false);
        }
    } catch(e) { se5Msg('se5EditMsg','Erreur réseau.', false); }
    btn.disabled = false; btn.innerHTML = '<i class="bi bi-check2-circle"></i> Enregistrer';
}

// ── Supprimer soin planifié ───────────────────────────────
function se5OpenDelete(id, desc) {
    document.getElementById('se5DelId').value = id;
    document.getElementById('se5DelDesc').textContent = desc;
    document.getElementById('se5DelMotif').value = '';
    document.getElementById('se5DelMsg').style.display = 'none';
    document.getElementById('se5ModalDel').style.display = 'flex';
}
function se5CloseDel() {
    document.getElementById('se5ModalDel').style.display = 'none';
}
async function se5ConfirmDel() {
    const id    = document.getElementById('se5DelId').value;
    const motif = document.getElementById('se5DelMotif').value.trim();
    if (!motif) { se5Msg('se5DelMsg','Le motif est obligatoire.', false); return; }
    const btn = document.getElementById('se5DelBtn');
    btn.disabled = true; btn.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>Suppression…';
    try {
        const res  = await fetch('<?= BASE_URL ?>hospitalisation/supprimer-soin/' + id, {
            method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({motif})
        });
        const json = await res.json();
        if (json.success) {
            se5Msg('se5DelMsg','Soin supprimé.', true);
            setTimeout(() => { se5CloseDel(); location.reload(); }, 1000);
        } else {
            se5Msg('se5DelMsg', json.message || 'Erreur.', false);
        }
    } catch(e) { se5Msg('se5DelMsg','Erreur réseau.', false); }
    btn.disabled = false; btn.innerHTML = '<i class="bi bi-trash3-fill"></i> Supprimer';
}

function se5Msg(elId, txt, ok) {
    const el = document.getElementById(elId);
    el.style.display = 'block';
    el.style.background = ok ? '#f0fdf4' : '#fef2f2';
    el.style.color = ok ? '#15803d' : '#dc2626';
    el.style.border = '1px solid ' + (ok ? '#bbf7d0' : '#fca5a5');
    el.textContent = txt;
}
// Fermer modales au clic en dehors
document.getElementById('se5ModalEdit').addEventListener('click', e => { if(e.target===e.currentTarget) se5CloseEdit(); });
document.getElementById('se5ModalDel').addEventListener('click',  e => { if(e.target===e.currentTarget) se5CloseDel();  });

// ── Soins planifiés : filtre ───────────────────────────────
function seFilter(type, btn) {
    document.querySelectorAll('.se-ftab').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.querySelectorAll('#seList .se-item').forEach(el => {
        if (type === 'tous') { el.style.display = ''; return; }
        el.style.display = el.dataset.filtre === type ? '' : 'none';
    });
}

// ── Soins planifiés : afficher/masquer la zone note ───────
function seToggleNote(id) {
    const zone = document.getElementById('seNote_' + id);
    if (!zone) return;
    const isOpen = zone.style.display === 'flex';
    // Fermer toutes les autres zones
    document.querySelectorAll('.se-note-zone').forEach(z => z.style.display = 'none');
    zone.style.display = isOpen ? 'none' : 'flex';
    if (!isOpen) zone.querySelector('.se-note-input')?.focus();
}

// ── Soins planifiés : exécuter via AJAX ───────────────────
async function seExecuter(soinId, patientId) {
    const note  = document.getElementById('seNoteInput_' + soinId)?.value.trim() || '';
    const btn   = document.querySelector(`#seNote_${soinId} .se-note-confirm`);
    if (btn) { btn.disabled = true; btn.textContent = '…'; }

    try {
        const fd = new FormData();
        fd.append('soin_id',    soinId);
        fd.append('patient_id', patientId);
        fd.append('note',       note);
        const res  = await fetch('<?= BASE_URL ?>neonatologie/soin-executer', { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        const json = await res.json();

        if (json.ok) {
            const item = document.querySelector(`#seNote_${soinId}`)?.closest('.se-item');
            if (item) {
                item.classList.add('realise');
                item.dataset.filtre = 'realise';
                // Remplacer le bouton "Marquer réalisé" par le statut ✓
                const execBtn = item.querySelector('.se-exec-btn');
                if (execBtn) {
                    execBtn.outerHTML = `<span class="se-exec-info"><i class="bi bi-check-circle-fill" style="color:#16a34a;"></i> Réalisé à <?= date('H:i') ?></span>`;
                }
                // Changer le badge statut
                const statBadge = item.querySelector('.se-stat');
                if (statBadge) { statBadge.style.background='#d1fae5'; statBadge.style.color='#065f46'; statBadge.textContent='Réalisé'; }
                // Afficher la note si saisie
                if (note) {
                    const noteDiv = document.createElement('div');
                    noteDiv.style.cssText = 'padding:6px 16px 8px;font-size:.75rem;color:#15803d;background:#f0fdf4;border-top:1px solid #bbf7d0;';
                    noteDiv.innerHTML = `<i class="bi bi-pencil me-1"></i>${note}`;
                    item.appendChild(noteDiv);
                }
                // Masquer zone note
                document.getElementById('seNote_' + soinId).style.display = 'none';
                svToast('Soin marqué comme réalisé.', 'ok');
            }
        } else {
            svToast(json.msg || 'Erreur lors de l\'exécution.', 'err');
            if (btn) { btn.disabled = false; btn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Confirmer'; }
        }
    } catch(e) {
        svToast('Erreur réseau.', 'err');
        if (btn) { btn.disabled = false; btn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Confirmer'; }
    }
}
</script>
</main>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
