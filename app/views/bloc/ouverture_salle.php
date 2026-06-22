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
require_once __DIR__ . '/../../services/CsrfService.php';
$prog = $programmation ?? [];
$pat  = $patient       ?? [];
?>
<style>
body { background:#f0f4f8; font-family:'Inter','Segoe UI',sans-serif; color:#1e293b; }
.sidebar, nav.sidebar { display:none !important; }
main,.col-md-10,.ms-sm-auto { margin-left:0!important; width:100%!important; max-width:100%!important; flex:0 0 100%!important; padding:0!important; }

/* ── Top bar ── */
.os-top {
    background:#fff; padding:12px 26px;
    display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px;
    position:sticky; top:0; z-index:50;
    border-bottom:1px solid #e2e8f0; box-shadow:0 2px 14px rgba(0,0,0,.06);
}
.os-brand { display:flex; align-items:center; gap:11px; }
.os-brand .ico {
    width:40px; height:40px; border-radius:11px; flex-shrink:0;
    background:linear-gradient(135deg,#4f46e5,#7c3aed);
    display:flex; align-items:center; justify-content:center; color:#fff; font-size:1.1rem;
}
.os-brand .title { font-size:.98rem; font-weight:800; color:#1e293b; line-height:1.2; }
.os-brand .sub   { font-size:.69rem; color:#64748b; font-weight:500; }
.os-btn {
    padding:7px 14px; border-radius:9px; font-weight:600; font-size:.78rem;
    text-decoration:none; border:1.5px solid #e2e8f0; cursor:pointer;
    background:#fff; color:#475569; transition:all .15s;
    display:inline-flex; align-items:center; gap:6px;
}
.os-btn:hover { background:#f1f5f9; color:#1e293b; }
.os-btn.accent {
    background:linear-gradient(135deg,#4f46e5,#7c3aed);
    border-color:transparent; color:#fff; font-weight:700;
    box-shadow:0 4px 14px rgba(79,70,229,.35);
}
.os-btn.accent:hover { opacity:.88; color:#fff; }

/* ── Layout ── */
.wrap { max-width:920px; margin:0 auto; padding:24px 20px 80px; }

/* ── Patient card ── */
.pat-card {
    background:#fff; border-radius:16px; padding:14px 20px; margin-bottom:20px;
    border-left:5px solid #4f46e5; box-shadow:0 2px 12px rgba(0,0,0,.07);
    display:flex; align-items:center; gap:14px; flex-wrap:wrap;
}
.pat-card .avatar {
    width:44px; height:44px; border-radius:12px; flex-shrink:0;
    background:linear-gradient(135deg,#ede9fe,#ddd6fe);
    display:flex; align-items:center; justify-content:center;
    font-size:1.1rem; font-weight:800; color:#4f46e5;
}
.pat-card .nm   { font-size:1.04rem; font-weight:800; color:#1e293b; }
.pat-card .meta { font-size:.75rem; color:#64748b; margin-top:2px; }
.pat-card .badge-bloc {
    margin-left:auto; flex-shrink:0;
    background:#ede9fe; color:#4f46e5; border:1.5px solid #c4b5fd;
    border-radius:20px; padding:5px 13px; font-size:.72rem; font-weight:700; white-space:nowrap;
}

/* ── Section cards ── */
.s-card { background:#fff; border-radius:16px; border:1px solid #e2e8f0; box-shadow:0 2px 10px rgba(0,0,0,.05); margin-bottom:18px; overflow:hidden; }
.s-card-head {
    padding:12px 18px; border-bottom:1px solid #f1f5f9;
    display:flex; align-items:center; gap:8px;
    font-size:.78rem; font-weight:800; color:#374151;
    text-transform:uppercase; letter-spacing:.5px;
    background:linear-gradient(135deg,#fafafa,#f8fafc);
}
.s-card-body { padding:18px; }

/* ── Checklist table ── */
.check-table { width:100%; border-collapse:collapse; font-size:.82rem; }
.check-table th {
    background:#f8fafc; color:#64748b; font-size:.7rem; font-weight:700;
    text-transform:uppercase; letter-spacing:.4px; padding:8px 10px;
    border:1px solid #e2e8f0; text-align:center;
}
.check-table th.eq-col { text-align:left; min-width:180px; }
.check-table td { border:1px solid #e2e8f0; padding:7px 10px; vertical-align:middle; }
.check-table .category { background:#f0f4f8; font-weight:800; font-size:.78rem; color:#374151; padding:8px 10px; }
.check-table .sub-item { padding-left:22px; color:#475569; }

/* ── Radio OUI/NON ── */
.yn-group { display:flex; justify-content:center; gap:4px; }
.yn-lbl {
    display:inline-flex; align-items:center; justify-content:center;
    width:38px; height:28px; border-radius:7px; cursor:pointer;
    border:1.5px solid #e2e8f0; font-size:.72rem; font-weight:700;
    transition:all .15s; background:#fff; user-select:none;
}
.yn-lbl:has(input:checked) { border-color:transparent; }
.yn-lbl.oui:has(input:checked) { background:#dcfce7; color:#16a34a; border-color:#86efac; }
.yn-lbl.non:has(input:checked) { background:#fee2e2; color:#dc2626; border-color:#fca5a5; }
.yn-lbl input { position:absolute; opacity:0; width:0; height:0; }

/* ── Obs field ── */
.obs-input {
    width:100%; padding:4px 8px; border-radius:7px;
    border:1.5px solid #e2e8f0; font-size:.78rem; outline:none;
    transition:border-color .15s;
}
.obs-input:focus { border-color:#6366f1; }

/* ── Bottom bar ── */
.submit-bar {
    position:fixed; bottom:0; left:0; right:0;
    background:#fff; border-top:1px solid #e2e8f0;
    padding:12px 24px; display:flex; align-items:center; justify-content:space-between;
    box-shadow:0 -4px 20px rgba(0,0,0,.08); z-index:100;
}

/* ── Clock ── */
.os-clock {
    background:#f1f5f9; border-radius:10px; padding:5px 12px;
    font-family:'Courier New',monospace; font-size:.78rem; font-weight:800;
    color:#4f46e5; letter-spacing:2px;
}
</style>

<!-- TOP BAR -->
<div class="os-top">
    <div class="os-brand">
        <div class="ico"><i class="bi bi-clipboard2-check-fill"></i></div>
        <div>
            <div class="title">Ouverture de Salle</div>
            <div class="sub">Fiche de vérification avant intervention · HSJM</div>
        </div>
    </div>
    <div class="d-flex align-items-center gap-3">
        <div class="os-clock" id="osClock">--:--:--</div>
        <a href="<?= BASE_URL ?>bloc" class="os-btn"><i class="bi bi-arrow-left"></i> Retour</a>
    </div>
</div>

<div class="wrap">

    <!-- Patient -->
    <div class="pat-card">
        <div class="avatar"><?= strtoupper(substr($pat['nom']??'?',0,1).substr($pat['prenom']??'',0,1)) ?></div>
        <div>
            <div class="nm"><?= htmlspecialchars(strtoupper($pat['nom']??'').' '.($pat['prenom']??'')) ?></div>
            <div class="meta">
                <?= htmlspecialchars($pat['dossier_numero']??'') ?>
                &nbsp;·&nbsp; <?= htmlspecialchars($prog['salle_nom']??'') ?>
                &nbsp;·&nbsp; <?= htmlspecialchars(substr($prog['heure_debut']??'',0,5)) ?>
                &nbsp;·&nbsp; <?= htmlspecialchars($prog['diagnostique_op']??'') ?>
            </div>
        </div>
        <div class="badge-bloc"><i class="bi bi-clipboard2-check me-1"></i>CHECK-UP PRÉ-OP</div>
    </div>

    <form method="POST" action="<?= BASE_URL ?>bloc/ouverture-salle/sauvegarder" id="formOuverture">
        <?= CsrfService::field() ?>
        <input type="hidden" name="prog_id" value="<?= (int)($prog['id']??0) ?>">

        <!-- En-tête fiche -->
        <div class="s-card mb-4">
            <div class="s-card-head"><i class="bi bi-info-circle-fill"></i> Informations générales</div>
            <div class="s-card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-muted">Date</label>
                        <input type="date" name="date_fiche" class="form-control rounded-3" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-muted">Heure d'ouverture</label>
                        <input type="time" name="heure_ouverture" class="form-control rounded-3" value="<?= date('H:i') ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-muted">Salle d'opération</label>
                        <select name="salle_id" class="form-select rounded-3" id="selectSalle" onchange="majSalleIdentite(this)">
                            <?php foreach ($salles ?? [] as $s): ?>
                            <option value="<?= (int)$s['id'] ?>"
                                <?= (int)$s['id'] === (int)($prog['salle_id']??0) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($s['nom_salle']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="hidden" name="salle_identite" id="salleIdentite"
                               value="<?= htmlspecialchars($prog['salle_nom']??'') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted">Heure de mise en marche du groupe électrogène</label>
                        <input type="time" name="heure_groupe_on" class="form-control rounded-3">
                    </div>
                </div>
            </div>
        </div>

        <!-- Tableau checklist -->
        <div class="s-card">
            <div class="s-card-head"><i class="bi bi-check2-all"></i> Vérification des équipements</div>
            <div class="s-card-body p-0">
                <div class="table-responsive">
                <table class="check-table">
                    <thead>
                        <tr>
                            <th class="eq-col">Équipement</th>
                            <th colspan="2">Présence</th>
                            <th colspan="2">Fonctionnalité</th>
                            <th colspan="2">Propreté</th>
                            <th style="min-width:140px">Observations</th>
                        </tr>
                        <tr>
                            <th class="eq-col"></th>
                            <th>Oui</th><th>Non</th>
                            <th>Oui</th><th>Non</th>
                            <th>Oui</th><th>Non</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $items = [
                        ['cat', 'Éclairages'],
                        ['item', 'eclairage_ambiance',   'Ambiance'],
                        ['item', 'eclairage_scialytique','Scialytique'],
                        ['cat', 'Table d\'opération'],
                        ['item', 'table_mouvements',     'Mouvements'],
                        ['item', 'table_accessoires',    'Accessoires'],
                        ['cat', 'Bistouri électrique'],
                        ['item', 'bistouri_generateur',  'Générateur'],
                        ['item', 'bistouri_plaque',      'Plaque'],
                        ['item', 'bistouri_pedale',      'Pédale'],
                        ['item', 'bistouri_cordon',      'Cordon pédale'],
                        ['cat', 'Système aspiration'],
                        ['item', 'asp_generateur',       'Générateur'],
                        ['item', 'asp_bocaux',           'Bocaux'],
                        ['item', 'asp_accessoires',      'Accessoires reliant bocal au générateur'],
                        ['cat', 'Air'],
                        ['item', 'air_splits',           'Splits'],
                        ['cat', 'Matériel spécifique'],
                        ['item', 'mat_ampli',            'Ampli de brillance'],
                        ['item', 'mat_colone_video',     'Colonne vidéo'],
                        ['item', 'mat_negatoscope',      'Négatoscope'],
                    ];
                    foreach ($items as $row):
                        if ($row[0] === 'cat'): ?>
                        <tr><td colspan="8" class="category"><i class="bi bi-chevron-right me-1" style="font-size:.7rem;color:#6366f1"></i><?= htmlspecialchars($row[1]) ?></td></tr>
                        <?php else:
                            $k = $row[1]; $lbl = $row[2]; ?>
                        <tr>
                            <td class="sub-item">— <?= htmlspecialchars($lbl) ?></td>
                            <?php foreach (['presence','fonctionnalite','proprete'] as $aspect): ?>
                            <td><label class="yn-lbl oui"><input type="radio" name="<?= $k ?>_<?= $aspect ?>" value="oui" required> Oui</label></td>
                            <td><label class="yn-lbl non"><input type="radio" name="<?= $k ?>_<?= $aspect ?>" value="non"> Non</label></td>
                            <?php endforeach; ?>
                            <td><input type="text" class="obs-input" name="<?= $k ?>_obs" placeholder="—"></td>
                        </tr>
                        <?php endif; endforeach; ?>
                    </tbody>
                </table>
                </div>
            </div>
        </div>

        <!-- Fin de session -->
        <div class="s-card">
            <div class="s-card-head"><i class="bi bi-power"></i> Fin de session (à remplir en fin d'intervention)</div>
            <div class="s-card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-muted">Heure mise en arrêt groupe électrogène</label>
                        <input type="time" name="heure_groupe_off" class="form-control rounded-3">
                    </div>
                    <div class="col-md-8">
                        <label class="form-label small fw-bold text-muted">Heure et nom — transmission arrêt groupe électrogène</label>
                        <input type="text" name="transmis_arret" class="form-control rounded-3" placeholder="Ex: 18h30 — Infirmier EBODE Christophe">
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-bold text-muted">Nom et signature du responsable</label>
                        <input type="text" name="signature_responsable" class="form-control rounded-3" placeholder="Nom complet du responsable de la fiche">
                    </div>
                </div>
            </div>
        </div>

    </form>
</div>

<!-- BARRE DE VALIDATION FIXE -->
<div class="submit-bar">
    <div style="font-size:.82rem;color:#64748b;">
        <i class="bi bi-shield-fill-check text-success me-1"></i>
        Complétez le check-up avant de démarrer l'intervention
    </div>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>bloc" class="os-btn">Annuler</a>
        <button type="submit" form="formOuverture" class="os-btn accent" id="btnValider">
            <i class="bi bi-play-fill"></i> Valider et démarrer l'intervention
        </button>
    </div>
</div>

<script>
function majSalleIdentite(sel) {
    document.getElementById('salleIdentite').value = sel.options[sel.selectedIndex].text.trim();
}

(function tick(){
    const d=new Date(),p=n=>String(n).padStart(2,'0');
    document.getElementById('osClock').textContent=p(d.getHours())+':'+p(d.getMinutes())+':'+p(d.getSeconds());
    setTimeout(tick,1000);
})();

document.getElementById('formOuverture').addEventListener('submit', function(e) {
    const btn = document.getElementById('btnValider');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Démarrage…';
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
