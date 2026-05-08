<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<style>
/* ─── Reset sidebar pour cette page ─── */
.sidebar, nav.sidebar { display: none !important; }
.main-content, .app-wrapper > *:not(.sidebar) { margin-left: 0 !important; }

/* ─── Layout ─── */
body { background: #f0f4f8; }
.pharma-shell {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}

/* ─── Topbar ─── */
.pharma-topbar {
    background: #fff;
    border-bottom: 1px solid #e5eaf2;
    padding: 14px 28px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: sticky;
    top: 0;
    z-index: 100;
}
.pharma-topbar .logo-area { display: flex; align-items: center; gap: 12px; }
.pharma-topbar .app-name  { font-weight: 800; font-size: .95rem; color: #1e293b; }
.pharma-topbar .page-title { font-size: 1rem; font-weight: 700; color: #1e293b; }
.pharma-topbar .ord-badge {
    background: #fff7ed;
    color: #c2410c;
    border: 1.5px solid #fed7aa;
    border-radius: 20px;
    font-size: .72rem;
    font-weight: 800;
    padding: 3px 12px;
    letter-spacing: .4px;
}

/* ─── Contenu ─── */
.pharma-body { flex: 1; padding: 28px 32px; max-width: 1100px; margin: 0 auto; width: 100%; }

/* ─── Patient card ─── */
.patient-card {
    background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%);
    color: #fff;
    border-radius: 16px;
    padding: 18px 24px;
    margin-bottom: 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 4px 20px rgba(30,64,175,.25);
}
.patient-card .pname { font-size: 1.15rem; font-weight: 800; letter-spacing: .3px; }
.patient-card .pmeta { font-size: .78rem; opacity: .78; margin-top: 3px; }
.patient-card .ord-num { text-align: right; }
.patient-card .ord-num span { font-size: 1.4rem; font-weight: 800; }

/* ─── Colonnes ─── */
.pharma-grid { display: grid; grid-template-columns: 1fr 320px; gap: 20px; }
@media (max-width: 820px) { .pharma-grid { grid-template-columns: 1fr; } }

/* ─── Tableau médicaments ─── */
.meds-card {
    background: #fff;
    border-radius: 14px;
    box-shadow: 0 2px 12px rgba(0,0,0,.06);
    overflow: hidden;
}
.meds-card-header {
    background: #f8fafc;
    padding: 12px 20px;
    border-bottom: 1px solid #e9eef5;
    font-size: .72rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .08em;
    color: #64748b;
    display: grid;
    grid-template-columns: 2fr 2fr 1fr 120px;
    gap: 8px;
}
.med-row {
    display: grid;
    grid-template-columns: 2fr 2fr 1fr 120px;
    gap: 8px;
    padding: 14px 20px;
    border-bottom: 1px solid #f1f5f9;
    align-items: center;
    transition: background .12s;
}
.med-row:last-child { border-bottom: none; }
.med-row:hover { background: #f8faff; }
.med-name { font-weight: 700; font-size: .9rem; color: #1e293b; }
.med-sub  { font-size: .72rem; color: #94a3b8; margin-top: 2px; }
.med-poso { font-size: .85rem; color: #475569; }
.med-dur  { font-size: .82rem; color: #64748b; }
.stock-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: .72rem;
    font-weight: 800;
    padding: 4px 10px;
    border-radius: 20px;
    letter-spacing: .3px;
}
.stock-ok      { background: #dcfce7; color: #15803d; }
.stock-low     { background: #fef9c3; color: #854d0e; }
.stock-out     { background: #fee2e2; color: #dc2626; }
.stock-na      { background: #f1f5f9; color: #94a3b8; }

/* ─── Panneau droit ─── */
.side-panel { display: flex; flex-direction: column; gap: 16px; }

.allergy-card {
    background: #fff;
    border-radius: 14px;
    box-shadow: 0 2px 12px rgba(0,0,0,.06);
    padding: 16px 18px;
    border-left: 4px solid #ef4444;
}
.allergy-card.safe { border-left-color: #10b981; }
.allergy-title { font-size: .78rem; font-weight: 800; text-transform: uppercase; letter-spacing: .06em; color: #ef4444; margin-bottom: 6px; }
.allergy-card.safe .allergy-title { color: #059669; }

.validate-card {
    background: linear-gradient(145deg, #0f172a, #1e293b);
    border-radius: 14px;
    box-shadow: 0 6px 24px rgba(0,0,0,.18);
    padding: 22px 20px;
    color: #fff;
}
.validate-card h5 { font-weight: 800; font-size: .95rem; margin-bottom: 8px; }
.validate-card p  { font-size: .78rem; opacity: .65; line-height: 1.6; margin-bottom: 18px; }

.btn-deliver {
    width: 100%;
    background: #fff;
    color: #0f172a;
    border: none;
    border-radius: 10px;
    padding: 13px;
    font-weight: 800;
    font-size: .88rem;
    letter-spacing: .3px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    cursor: pointer;
    transition: all .18s;
    box-shadow: 0 2px 8px rgba(0,0,0,.12);
}
.btn-deliver:hover:not(:disabled) { background: #f0fdf4; transform: translateY(-1px); box-shadow: 0 4px 16px rgba(0,0,0,.18); }
.btn-deliver:disabled { opacity: .55; cursor: not-allowed; }
.btn-deliver .icon-check { width: 20px; height: 20px; background: #10b981; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.btn-cancel { display: block; text-align: center; color: rgba(255,255,255,.5); font-size: .78rem; margin-top: 12px; text-decoration: none; }
.btn-cancel:hover { color: rgba(255,255,255,.85); }

/* ─── Toast ─── */
.pharma-toast {
    position: fixed;
    bottom: 28px; right: 28px;
    background: #0f172a;
    color: #fff;
    padding: 14px 20px;
    border-radius: 12px;
    font-size: .84rem;
    font-weight: 600;
    box-shadow: 0 8px 32px rgba(0,0,0,.25);
    z-index: 9999;
    display: none;
    align-items: center;
    gap: 10px;
    max-width: 320px;
}
.pharma-toast.show { display: flex; }
.pharma-toast.success { border-left: 4px solid #10b981; }
.pharma-toast.error   { border-left: 4px solid #ef4444; }
</style>

<div class="pharma-shell">

    <!-- ── TOPBAR ─────────────────────────────── -->
    <div class="pharma-topbar">
        <div class="logo-area">
            <a href="<?= BASE_URL ?>pharmacie" style="color:inherit;text-decoration:none;">
                <svg width="28" height="28" viewBox="0 0 28 28" fill="none">
                    <rect width="28" height="28" rx="8" fill="#1e40af"/>
                    <path d="M9 8h10M14 8v12M9 14h10" stroke="#fff" stroke-width="2.2" stroke-linecap="round"/>
                </svg>
            </a>
            <span class="app-name">Pharmacie DME</span>
        </div>
        <div class="page-title">Préparation Ordonnance</div>
        <div>
            <span class="ord-badge">
                <svg width="12" height="12" viewBox="0 0 12 12" fill="currentColor" style="margin-right:4px;vertical-align:-1px;">
                    <path d="M6 1a5 5 0 100 10A5 5 0 006 1zm0 1.5A3.5 3.5 0 119.5 6 3.5 3.5 0 016 2.5zm-.5 1.5v3l2.5 1.5.4-.7-2.1-1.2V4H5.5z"/>
                </svg>
                VÉRIFICATION SÉCURITÉ
            </span>
        </div>
    </div>

    <!-- ── CORPS ──────────────────────────────── -->
    <div class="pharma-body">

        <!-- Patient Header -->
        <div class="patient-card">
            <div>
                <div class="pname"><?= strtoupper(htmlspecialchars($ordonnance['patient_nom'])) ?> <?= htmlspecialchars($ordonnance['patient_prenom']) ?></div>
                <div class="pmeta">
                    Dossier : <?= htmlspecialchars($ordonnance['dossier_numero']) ?>
                    <?php if (!empty($ordonnance['date_naissance'])): ?>
                    &nbsp;·&nbsp; <?= date_diff(date_create($ordonnance['date_naissance']), date_create('today'))->y ?> ans
                    <?php endif; ?>
                    &nbsp;·&nbsp; Émis le <?= date('d/m/Y', strtotime($ordonnance['date_creation'])) ?>
                </div>
            </div>
            <div class="ord-num">
                <div style="font-size:.72rem;opacity:.6;font-weight:600;">ORDONNANCE</div>
                <span>#<?= str_pad($ordonnance['id'], 4, '0', STR_PAD_LEFT) ?></span>
            </div>
        </div>

        <!-- Grille -->
        <div class="pharma-grid">

            <!-- ── Médicaments ── -->
            <div class="meds-card">
                <div class="meds-card-header">
                    <span>Médicament</span>
                    <span>Posologie</span>
                    <span>Durée</span>
                    <span style="text-align:center;">Stock</span>
                </div>
                <?php if (empty($lignes)): ?>
                <div style="text-align:center;padding:40px;color:#94a3b8;">
                    <svg width="36" height="36" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="opacity:.3;margin-bottom:8px;display:block;margin-left:auto;margin-right:auto;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Aucun médicament enregistré
                </div>
                <?php else: foreach ($lignes as $l):
                    $nomMed   = htmlspecialchars($l['nom_medicament'] ?: ($l['designation_stock'] ?? '—'));
                    $hasStock = !empty($l['medicament_id']);
                    $qte      = (int)($l['stock_actuel'] ?? 0);
                    $seuil    = (int)($l['seuil_alerte']  ?? 10);
                    if (!$hasStock)          { $sc = 'stock-na'; $sl = '— N/A'; }
                    elseif ($qte <= 0)       { $sc = 'stock-out'; $sl = '0 dispo.'; }
                    elseif ($qte <= $seuil)  { $sc = 'stock-low'; $sl = $qte . ' dispo.'; }
                    else                     { $sc = 'stock-ok';  $sl = $qte . ' dispo.'; }
                ?>
                <div class="med-row">
                    <div>
                        <div class="med-name"><?= $nomMed ?></div>
                        <?php if (!$hasStock): ?>
                        <span class="med-sub" style="background:#f1f5f9;border-radius:4px;padding:1px 6px;font-size:.65rem;font-weight:700;">Hors stock</span>
                        <?php endif; ?>
                    </div>
                    <div class="med-poso"><?= htmlspecialchars($l['posologie'] ?? '—') ?></div>
                    <div class="med-dur"><?= htmlspecialchars($l['duree'] ?? '—') ?></div>
                    <div style="text-align:center;">
                        <span class="stock-badge <?= $sc ?>">
                            <?php if ($sc === 'stock-ok'): ?><span style="width:6px;height:6px;background:#15803d;border-radius:50%;display:inline-block;"></span><?php endif; ?>
                            <?php if ($sc === 'stock-low'): ?><span style="width:6px;height:6px;background:#854d0e;border-radius:50%;display:inline-block;"></span><?php endif; ?>
                            <?php if ($sc === 'stock-out'): ?><span style="width:6px;height:6px;background:#dc2626;border-radius:50%;display:inline-block;"></span><?php endif; ?>
                            <?= $sl ?>
                        </span>
                    </div>
                </div>
                <?php endforeach; endif; ?>
            </div>

            <!-- ── Panneau droit ── -->
            <div class="side-panel">

                <!-- Allergies -->
                <?php $hasAllergy = !empty($ordonnance['allergies']); ?>
                <div class="allergy-card <?= $hasAllergy ? '' : 'safe' ?>">
                    <div class="allergy-title">
                        <?= $hasAllergy ? '⚠ Alertes Allergies' : '✓ Allergies' ?>
                    </div>
                    <?php if ($hasAllergy): ?>
                    <div style="font-size:.85rem;font-weight:700;color:#1e293b;line-height:1.5;">
                        <?= nl2br(htmlspecialchars($ordonnance['allergies'])) ?>
                    </div>
                    <?php else: ?>
                    <div style="font-size:.83rem;color:#059669;font-weight:600;">Aucune allergie connue pour ce patient.</div>
                    <?php endif; ?>
                </div>

                <!-- Validation -->
                <div class="validate-card">
                    <h5>Validation Finale</h5>
                    <p>En cliquant sur le bouton, vous confirmez que vous avez préparé les médicaments et vérifié les interactions.</p>
                    <button class="btn-deliver" id="btnDeliver" onclick="confirmDeliver(<?= (int)$ordonnance['id'] ?>)">
                        <span class="icon-check">
                            <svg width="11" height="11" viewBox="0 0 12 12" fill="white">
                                <path d="M2 6l3 3 5-5" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                            </svg>
                        </span>
                        Délivrer les médicaments
                    </button>
                    <a href="<?= BASE_URL ?>pharmacie" class="btn-cancel">Annuler et revenir</a>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- Toast notification -->
<div class="pharma-toast" id="pharmaToast"></div>

<script>
function showToast(msg, type = 'success') {
    const t = document.getElementById('pharmaToast');
    t.className = 'pharma-toast show ' + type;
    t.innerHTML = (type === 'success'
        ? '<svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="#10b981" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>'
        : '<svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="#ef4444" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>'
    ) + msg;
    setTimeout(() => t.classList.remove('show'), 3500);
}

function confirmDeliver(id) {
    if (!confirm('Valider la sortie de stock et clôturer cette ordonnance ?')) return;

    const btn = document.getElementById('btnDeliver');
    btn.disabled = true;
    btn.innerHTML = '<span style="width:18px;height:18px;border:2.5px solid #1e293b;border-top-color:transparent;border-radius:50%;display:inline-block;animation:spin .7s linear infinite;"></span> Traitement en cours…';

    const fd = new FormData();
    fd.append('id', id);

    fetch('<?= BASE_URL ?>pharmacie/delivrer', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showToast('Ordonnance traitée avec succès.');
                setTimeout(() => { window.location.href = '<?= BASE_URL ?>pharmacie'; }, 1200);
            } else {
                showToast(data.message || 'Une erreur est survenue.', 'error');
                btn.disabled = false;
                btn.innerHTML = '<span class="icon-check"><svg width="11" height="11" viewBox="0 0 12 12" fill="white"><path d="M2 6l3 3 5-5" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"/></svg></span> Délivrer les médicaments';
            }
        })
        .catch(() => {
            showToast('Erreur réseau.', 'error');
            btn.disabled = false;
        });
}
</script>
<style>
@keyframes spin { to { transform: rotate(360deg); } }
</style>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
