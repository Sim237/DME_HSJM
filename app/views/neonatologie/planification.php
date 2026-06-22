<?php
require_once __DIR__ . '/../layouts/header.php';
$nom     = strtoupper($patient['nom'] ?? '');
$prenom  = $patient['prenom'] ?? '';
$dossier = $patient['dossier_numero'] ?? '';
$sexe    = $patient['sexe'] ?? 'M';
$chambre = $patient['nom_chambre'] ?? '';
$lit     = $patient['nom_lit'] ?? '';
$age     = NeonatologyController::ageNeonat($patient['date_naissance'] ?? null);
$uNom    = strtoupper(trim(($_SESSION['user_nom'] ?? '') . ' ' . ($_SESSION['user_prenom'] ?? '')));

$priColors = ['HAUTE'=>['#fee2e2','#dc2626','#fca5a5'],'MOYENNE'=>['#fffbeb','#b45309','#fde68a'],'BASSE'=>['#f0fdf4','#15803d','#bbf7d0']];
$priLabels = ['HAUTE'=>'Haute','MOYENNE'=>'Moyenne','BASSE'=>'Basse'];
$statColors = ['EN_COURS'=>['#eff6ff','#1d4ed8'],'ATTEINT'=>['#f0fdf4','#15803d'],'NON_ATTEINT'=>['#fef2f2','#dc2626'],'REPORTE'=>['#f8fafc','#64748b']];
$statLabels = ['EN_COURS'=>'En cours','ATTEINT'=>'Atteint','NON_ATTEINT'=>'Non atteint','REPORTE'=>'Reporté'];
?>
<style>
.sidebar,nav.sidebar{display:none!important}
main,.col-md-10,.ms-sm-auto{margin-left:0!important;width:100%!important;flex:0 0 100%!important;max-width:100%!important;padding:0!important}
body{background:#f0fdf4;font-family:'Segoe UI',system-ui,sans-serif;color:#1e293b}

.pl-top{background:linear-gradient(135deg,#065f46,#059669 55%,#10b981);padding:14px 28px;
  display:flex;align-items:center;justify-content:space-between;
  position:sticky;top:0;z-index:100;box-shadow:0 4px 24px rgba(5,150,105,.3)}
.pl-title{font-size:1.05rem;font-weight:900;color:#fff;display:flex;align-items:center;gap:10px}
.pl-sub{font-size:.65rem;color:rgba(255,255,255,.8);font-weight:600;text-transform:uppercase;letter-spacing:1px;margin-top:2px}
.pl-btn{display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border-radius:10px;
  font-weight:700;font-size:.76rem;text-decoration:none;border:1px solid rgba(255,255,255,.35);
  background:rgba(255,255,255,.18);color:#fff;transition:all .18s;cursor:pointer}
.pl-btn:hover{background:rgba(255,255,255,.3);color:#fff}

.pl-wrap{padding:22px 28px;max-width:980px;margin:0 auto}

.pl-banner{background:#fff;border-radius:18px;border:1px solid #bbf7d0;
  box-shadow:0 4px 20px rgba(5,150,105,.08);padding:18px 24px;margin-bottom:20px;
  display:flex;align-items:center;gap:16px}
.pl-av{width:54px;height:54px;border-radius:16px;display:flex;align-items:center;
  justify-content:center;font-size:1.5rem;color:#fff;flex-shrink:0;
  background:linear-gradient(135deg,#059669,#10b981)}
.pl-pname{font-size:1.1rem;font-weight:900;color:#0f172a}
.pl-pmeta{font-size:.78rem;color:#64748b;margin-top:3px;display:flex;flex-wrap:wrap;gap:8px}
.pl-pmeta i{color:#059669}

.pl-panel{background:#fff;border-radius:18px;border:1px solid #bbf7d0;
  box-shadow:0 2px 12px rgba(5,150,105,.06);margin-bottom:18px;overflow:hidden}
.pl-ph{padding:14px 22px;border-bottom:2px solid #bbf7d0;background:linear-gradient(90deg,#f0fdf4,#fff);
  font-size:.8rem;font-weight:800;color:#065f46;text-transform:uppercase;letter-spacing:.6px;
  display:flex;align-items:center;gap:8px}

.pl-form-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;padding:20px 22px}
.pl-full{grid-column:1/-1}
.pl-lbl{font-size:.68rem;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.4px;margin-bottom:5px}
.pl-ctrl{width:100%;border:2px solid #e2e8f0;border-radius:11px;padding:9px 13px;
  font-size:.85rem;font-family:inherit;transition:.18s;background:#fff;resize:vertical}
.pl-ctrl:focus{border-color:#059669;outline:none;box-shadow:0 0 0 3px rgba(5,150,105,.1)}
.pl-submit{background:linear-gradient(135deg,#065f46,#059669);color:#fff;border:none;
  border-radius:14px;padding:13px 28px;font-weight:900;font-size:.9rem;cursor:pointer;
  display:inline-flex;align-items:center;gap:8px;transition:.2s;margin:0 22px 20px}
.pl-submit:hover{background:linear-gradient(135deg,#064e3b,#065f46);transform:translateY(-1px)}

.pl-flash{padding:12px 18px;border-radius:12px;margin-bottom:16px;font-size:.84rem;font-weight:600;display:flex;align-items:center;gap:10px}
.pl-flash.ok{background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0}
.pl-flash.err{background:#fef2f2;color:#dc2626;border:1px solid #fca5a5}

/* Plans cards */
.pl-cards{padding:16px 22px;display:flex;flex-direction:column;gap:14px}
.pl-card{border-radius:16px;border:2px solid #e2e8f0;overflow:hidden;transition:.2s}
.pl-card:hover{box-shadow:0 4px 16px rgba(0,0,0,.08)}
.pl-card-hd{display:flex;align-items:center;justify-content:space-between;padding:12px 18px;border-bottom:1px solid #f1f5f9}
.pl-card-body{padding:14px 18px;display:grid;grid-template-columns:1fr 1fr;gap:12px}
.pl-card-field{font-size:.82rem}
.pl-card-lbl{font-size:.64rem;color:#94a3b8;font-weight:700;text-transform:uppercase;margin-bottom:3px}
.pl-card-val{color:#1e293b;line-height:1.5}
.pl-card-full{grid-column:1/-1}
.pl-pri-badge{padding:3px 12px;border-radius:20px;font-size:.68rem;font-weight:800}
.pl-stat-badge{padding:3px 12px;border-radius:20px;font-size:.68rem;font-weight:800}
.pl-empty{padding:32px;text-align:center;color:#94a3b8;font-size:.85rem}
.pl-empty i{font-size:2rem;opacity:.2;display:block;margin-bottom:8px}
@media(max-width:700px){.pl-form-grid,.pl-card-body{grid-template-columns:1fr}}
</style>

<main>
<!-- ── Topbar ──────────────────────────────────────────── -->
<div class="pl-top">
  <div>
    <div class="pl-title"><i class="bi bi-calendar2-check-fill"></i> Planification des soins — BB <?= htmlspecialchars("$nom $prenom") ?></div>
    <div class="pl-sub">Unité de Néonatologie · HSJM · <?= htmlspecialchars($dossier) ?></div>
  </div>
  <div class="d-flex gap-2 flex-wrap">
    <a href="<?= BASE_URL ?>neonatologie/suivi/<?= (int)$patient['id'] ?>" class="pl-btn"><i class="bi bi-activity"></i> Suivi</a>
    <a href="<?= BASE_URL ?>neonatologie" class="pl-btn"><i class="bi bi-arrow-left"></i></a>
  </div>
</div>

<div class="pl-wrap">

  <?php if ($flash): ?>
  <div class="pl-flash <?= $flash['type'] ?>">
    <i class="bi bi-<?= $flash['type']==='ok'?'check-circle-fill':'exclamation-circle-fill' ?>"></i>
    <?= htmlspecialchars($flash['msg']) ?>
  </div>
  <?php endif ?>

  <!-- ── Bannière patient ─────────────────────── -->
  <div class="pl-banner">
    <div class="pl-av"><?= $sexe==='F'?'♀':'♂' ?></div>
    <div>
      <div class="pl-pname">BB <?= htmlspecialchars("$nom $prenom") ?></div>
      <div class="pl-pmeta">
        <span><i class="bi bi-file-medical"></i><?= htmlspecialchars($dossier) ?></span>
        <span><i class="bi bi-clock"></i><?= htmlspecialchars($age) ?></span>
        <?php if($chambre): ?><span><i class="bi bi-geo-alt-fill"></i><?= htmlspecialchars("$chambre · $lit") ?></span><?php endif ?>
        <span style="background:#dcfce7;color:#15803d;padding:2px 10px;border-radius:20px;font-weight:800;">
          <i class="bi bi-calendar2-check"></i> <?= count($plans) ?> plan(s)
        </span>
      </div>
    </div>
  </div>

  <!-- ── Formulaire nouveau plan ──────────────── -->
  <div class="pl-panel">
    <div class="pl-ph"><i class="bi bi-plus-circle-fill"></i> Nouveau plan de soin</div>
    <form method="POST" action="<?= BASE_URL ?>neonatologie/planification-sauvegarder">
      <input type="hidden" name="patient_id" value="<?= (int)$patient['id'] ?>">
      <div class="pl-form-grid">
        <div>
          <div class="pl-lbl">Date</div>
          <input type="date" name="date_planification" class="pl-ctrl" value="<?= date('Y-m-d') ?>">
        </div>
        <div>
          <div class="pl-lbl">Priorité</div>
          <select name="priorite" class="pl-ctrl">
            <option value="HAUTE">🔴 Haute</option>
            <option value="MOYENNE" selected>🟡 Moyenne</option>
            <option value="BASSE">🟢 Basse</option>
          </select>
        </div>
        <div class="pl-full">
          <div class="pl-lbl">Diagnostic infirmier</div>
          <textarea name="diagnostic_infirmier" class="pl-ctrl" rows="2" placeholder="Ex : Risque d'hypothermie lié à la prématurité — thermorégulation immature…"></textarea>
        </div>
        <div class="pl-full">
          <div class="pl-lbl">Objectif / Résultat attendu</div>
          <textarea name="objectif" class="pl-ctrl" rows="2" placeholder="Ex : Maintenir la température entre 36.5°C et 37.5°C sur 24h…"></textarea>
        </div>
        <div class="pl-full">
          <div class="pl-lbl">Interventions infirmières</div>
          <textarea name="interventions" class="pl-ctrl" rows="3" placeholder="Ex : Couveuse réglée à 34°C, température relevée toutes les 3h, bonnet et body, peau à peau si possible…"></textarea>
        </div>
        <div class="pl-full">
          <div class="pl-lbl">Critères d'évaluation</div>
          <textarea name="evaluation" class="pl-ctrl" rows="2" placeholder="Ex : Température stable ≥ 36.5°C à chaque relevé, pas de cyanose…"></textarea>
        </div>
        <div>
          <div class="pl-lbl">Date d'échéance</div>
          <input type="date" name="echeance" class="pl-ctrl" value="<?= date('Y-m-d', strtotime('+3 days')) ?>">
        </div>
        <div>
          <div class="pl-lbl">Statut initial</div>
          <select name="statut" class="pl-ctrl">
            <option value="EN_COURS">En cours</option>
            <option value="ATTEINT">Atteint</option>
            <option value="NON_ATTEINT">Non atteint</option>
            <option value="REPORTE">Reporté</option>
          </select>
        </div>
      </div>
      <button type="submit" class="pl-submit"><i class="bi bi-check-circle-fill"></i> Enregistrer le plan</button>
    </form>
  </div>

  <!-- ── Plans existants ──────────────────────── -->
  <div class="pl-panel">
    <div class="pl-ph"><i class="bi bi-list-check"></i> Plans de soins — <?= count($plans) ?></div>
    <?php if (empty($plans)): ?>
    <div class="pl-empty"><i class="bi bi-calendar2-x"></i>Aucun plan de soin enregistré</div>
    <?php else: ?>
    <div class="pl-cards">
      <?php foreach ($plans as $pl):
        $pc = $priColors[$pl['priorite']] ?? $priColors['MOYENNE'];
        $sc = $statColors[$pl['statut']] ?? $statColors['EN_COURS'];
        $echeance = $pl['echeance'] ? date('d/m/Y', strtotime($pl['echeance'])) : '—';
        $dpDate   = date('d/m/Y', strtotime($pl['date_planification']));
        $inf      = $pl['inf_nom'] ? htmlspecialchars(($pl['inf_prenom']??'').' '.$pl['inf_nom']) : '—';
        $depass   = $pl['echeance'] && strtotime($pl['echeance']) < time() && $pl['statut'] === 'EN_COURS';
      ?>
      <div class="pl-card" style="border-color:<?= $pc[2] ?>;background:<?= $pc[0] ?>08;">
        <div class="pl-card-hd" style="background:<?= $pc[0] ?>;">
          <div style="display:flex;align-items:center;gap:10px;">
            <span class="pl-pri-badge" style="background:<?= $pc[0] ?>;color:<?= $pc[1] ?>;border:1px solid <?= $pc[2] ?>;"><?= $priLabels[$pl['priorite']] ?></span>
            <span style="font-size:.82rem;font-weight:800;color:#1e293b;"><?= date('d/m/Y', strtotime($pl['date_planification'])) ?></span>
            <?php if ($depass): ?><span style="font-size:.68rem;background:#fee2e2;color:#dc2626;padding:2px 8px;border-radius:20px;font-weight:800;">⚠ Dépassé</span><?php endif ?>
          </div>
          <div style="display:flex;align-items:center;gap:8px;">
            <span class="pl-stat-badge" style="background:<?= $sc[0] ?>;color:<?= $sc[1] ?>;"><?= $statLabels[$pl['statut']] ?></span>
            <span style="font-size:.72rem;color:#64748b;">Échéance : <strong><?= $echeance ?></strong></span>
          </div>
        </div>
        <div class="pl-card-body">
          <?php foreach ([
            ['Diagnostic infirmier', $pl['diagnostic_infirmier'], true],
            ['Objectif / Résultat attendu', $pl['objectif'], true],
            ['Interventions', $pl['interventions'], true],
            ['Évaluation', $pl['evaluation'], false],
          ] as [$lbl, $val, $full]):
            if (!$val) continue; ?>
          <div class="pl-card-field <?= $full?'pl-card-full':'' ?>">
            <div class="pl-card-lbl"><?= $lbl ?></div>
            <div class="pl-card-val"><?= nl2br(htmlspecialchars($val)) ?></div>
          </div>
          <?php endforeach ?>
          <div class="pl-card-field" style="font-size:.75rem;color:#64748b;border-top:1px solid #f1f5f9;padding-top:8px;grid-column:1/-1;">
            <i class="bi bi-person-badge me-1"></i><?= $inf ?>
          </div>
        </div>
      </div>
      <?php endforeach ?>
    </div>
    <?php endif ?>
  </div>

</div>
</main>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
