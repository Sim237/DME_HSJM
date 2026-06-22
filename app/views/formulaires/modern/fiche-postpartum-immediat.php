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
 require_once __DIR__ . '/../../layouts/header.php'; ?>
<style>
/* ═══════════════════════════════════════════════════════════
   FICHE DE SURVEILLANCE POST PARTUM IMMÉDIAT — Design System
   ═══════════════════════════════════════════════════════════ */
:root {
    --pp-violet:    #7c3aed;
    --pp-violet-lt: #f5f3ff;
    --pp-violet-dk: #5b21b6;
    --pp-blue:      #1d4ed8;
    --pp-blue-lt:   #eff6ff;
    --pp-dark:      #1e293b;
    --pp-muted:     #64748b;
    --border:       #e2e8f0;
}
body { background: #f0f4f8; font-family: 'Inter', sans-serif; }

/* ── Topbar ── */
.pp-topbar {
    position: sticky; top: 0; z-index: 200;
    background: white; border-bottom: 1px solid var(--border);
    padding: 10px 20px; display: flex; justify-content: space-between;
    align-items: center; box-shadow: 0 2px 8px rgba(0,0,0,.07);
}
.btn-back {
    display:inline-flex;align-items:center;gap:6px;padding:8px 14px;
    border-radius:8px;border:1.5px solid var(--border);background:white;
    color:#475569;font-weight:600;font-size:.85rem;text-decoration:none;transition:.2s;
}
.btn-back:hover{background:#f8fafc;color:var(--pp-dark);}
.btn-save {
    display:inline-flex;align-items:center;gap:6px;padding:9px 20px;
    border-radius:8px;border:none;background:var(--pp-violet);color:white;
    font-weight:700;font-size:.88rem;cursor:pointer;transition:.2s;
}
.btn-save:hover{background:var(--pp-violet-dk);}

/* ── Patient banner ── */
.pp-banner {
    background: linear-gradient(135deg, #7c3aed, #a855f7);
    border-radius:16px;padding:18px 24px;color:white;
    margin-bottom:20px;display:flex;justify-content:space-between;
    align-items:center;flex-wrap:wrap;gap:12px;
}
.pp-badge {
    background:rgba(255,255,255,.18);border-radius:8px;
    padding:5px 12px;font-size:.8rem;font-weight:600;
}

/* ── Card ── */
.pp-card {
    background:white;border-radius:12px;
    box-shadow:0 2px 8px rgba(0,0,0,.06);
    margin-bottom:20px;overflow:hidden;
}
.pp-card-head {
    background:var(--pp-violet-lt);padding:11px 18px;
    font-weight:700;font-size:.82rem;text-transform:uppercase;
    letter-spacing:.5px;display:flex;align-items:center;gap:8px;
    border-bottom:2px solid var(--pp-violet);color:var(--pp-violet);
}
.pp-card-body { padding:18px; }

/* ── Form controls ── */
.form-label{font-weight:600;font-size:.78rem;color:var(--pp-muted);
    text-transform:uppercase;letter-spacing:.4px;margin-bottom:4px;}
.form-control,.form-select{
    border:1.5px solid var(--border);border-radius:8px;
    font-size:.9rem;padding:9px 12px;transition:.2s;width:100%;
}
.form-control:focus,.form-select:focus{
    border-color:var(--pp-violet);
    box-shadow:0 0 0 3px rgba(124,58,237,.12);outline:none;
}

/* ── Tableau de surveillance principal ── */
.surv-wrap { overflow-x:auto; }
.surv-table {
    border-collapse:collapse;
    min-width:1200px;
    width:100%;
    font-size:.76rem;
}
.surv-table th {
    background:#f8fafc;border:1px solid #cbd5e1;
    padding:5px 4px;text-align:center;font-weight:700;
    color:var(--pp-dark);white-space:nowrap;
}
.surv-table th.th-group {
    font-size:.7rem;color:var(--pp-muted);text-transform:uppercase;
    letter-spacing:.3px;
}
.surv-table td { border:1px solid #cbd5e1;padding:2px 2px;vertical-align:middle; }
.surv-table td.row-label {
    background:#f8fafc;font-weight:600;font-size:.74rem;
    color:var(--pp-dark);padding:5px 8px;white-space:nowrap;
    min-width:150px;
}
/* Groupe MÈRE */
.surv-table tr.group-mere td.group-label {
    background:var(--pp-violet-lt);color:var(--pp-violet);
    font-weight:900;font-size:.72rem;text-transform:uppercase;
    letter-spacing:.4px;writing-mode:vertical-rl;text-orientation:mixed;
    transform:rotate(180deg);text-align:center;padding:8px 4px;
    border:1px solid #cbd5e1;
}
/* Groupe NNE */
.surv-table tr.group-nne td.group-label {
    background:var(--pp-blue-lt);color:var(--pp-blue);
    font-weight:900;font-size:.72rem;text-transform:uppercase;
    letter-spacing:.4px;writing-mode:vertical-rl;text-orientation:mixed;
    transform:rotate(180deg);text-align:center;padding:8px 4px;
    border:1px solid #cbd5e1;
}
.surv-table .group-mere td.row-label { background:#fdf4ff;border-left:3px solid var(--pp-violet); }
.surv-table .group-nne td.row-label  { background:#eff6ff;border-left:3px solid var(--pp-blue); }

.surv-table input[type=text] {
    width:100%;border:none;outline:none;
    text-align:center;font-size:.78rem;
    background:transparent;padding:3px 2px;
    color:var(--pp-dark);font-weight:600;
    min-width:60px;
}
.surv-table input[type=text]:focus { background:#faf5ff;border-radius:3px; }
.surv-table .group-nne input[type=text]:focus { background:#eff6ff; }

/* Colonne transfert */
.surv-table td.col-transfert {
    background:#fefce8;padding:4px;
}
.surv-table td.col-transfert input[type=text]:focus { background:#fefce8; }

/* Colonne observations */
.surv-table td.col-obs {
    min-width:120px;padding:4px;
}

/* En-têtes groupes de colonnes */
.surv-table th.col-heure1 { background:#f3e8ff;color:#6d28d9; }
.surv-table th.col-heure23 { background:#dbeafe;color:#1d4ed8; }
.surv-table th.col-heure46 { background:#d1fae5;color:#059669; }
.surv-table th.col-transfert-h { background:#fef9c3;color:#92400e; }
.surv-table th.col-obs-h { background:#f1f5f9;color:var(--pp-dark); }

.pp-wrap { max-width:1400px;margin:0 auto;padding:18px 16px 100px; }
</style>

<!-- ── Topbar ─────────────────────────────────────────────────── -->
<div class="pp-topbar">
    <div class="d-flex align-items-center gap-3">
        <?php if ($from_suivi): ?>
        <a href="<?= BASE_URL ?>hospitalisation/suivi/<?= (int)$patient['id'] ?>" class="btn-back">
            <i class="bi bi-arrow-left"></i> Retour
        </a>
        <?php else: ?>
        <a href="<?= BASE_URL ?>patients/dossier/<?= (int)$patient['id'] ?>" class="btn-back">
            <i class="bi bi-arrow-left"></i> Retour
        </a>
        <?php endif; ?>
        <div style="font-weight:800;font-size:.95rem;color:var(--pp-dark);">
            <i class="bi bi-clipboard2-heart" style="color:var(--pp-violet);"></i>
            Fiche de Surveillance Post Partum Immédiat
        </div>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill" onclick="window.print()">
            <i class="bi bi-printer"></i> Imprimer
        </button>
        <button type="submit" form="formPP" class="btn-save">
            <i class="bi bi-save2-fill"></i> Enregistrer
        </button>
    </div>
</div>

<div class="pp-wrap">

<!-- ── Bannière patient ─────────────────────────────────────── -->
<div class="pp-banner">
    <div>
        <div style="font-size:1.2rem;font-weight:800;">
            <?= htmlspecialchars(($patient['nom'] ?? '') . ' ' . ($patient['prenom'] ?? '')) ?>
        </div>
        <div style="font-size:.83rem;opacity:.88;margin-top:4px;">
            <?= $age ?> ans &nbsp;·&nbsp; Dossier : <?= htmlspecialchars($patient['dossier_numero'] ?? '—') ?>
        </div>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <span class="pp-badge"><i class="bi bi-calendar3 me-1"></i><?= date('d/m/Y') ?></span>
        <span class="pp-badge"><i class="bi bi-heart-pulse me-1"></i>Maternité</span>
    </div>
</div>

<form id="formPP" action="#" method="POST">
    <input type="hidden" name="patient_id" value="<?= (int)$patient['id'] ?>">
    <?php if ($hosp_id): ?><input type="hidden" name="hosp_id" value="<?= (int)$hosp_id ?>"><?php endif; ?>

    <!-- ── Date ── -->
    <div class="pp-card">
        <div class="pp-card-head">
            <i class="bi bi-calendar-event"></i> Informations générales
        </div>
        <div class="pp-card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Date de surveillance</label>
                    <input type="date" name="date_surveillance" class="form-control"
                           value="<?= htmlspecialchars($formData['date_surveillance'] ?? date('Y-m-d')) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Heure d'accouchement</label>
                    <input type="time" name="heure_accouchement" class="form-control"
                           value="<?= htmlspecialchars($formData['heure_accouchement'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Sage-femme / Infirmière</label>
                    <input type="text" name="sage_femme" class="form-control"
                           value="<?= htmlspecialchars($formData['sage_femme'] ?? ($medecin_nom ?? '')) ?>"
                           placeholder="Nom de la sage-femme">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Nom de la patiente</label>
                    <input type="text" name="nom_patiente" class="form-control"
                           value="<?= htmlspecialchars($formData['nom_patiente'] ?? (($patient['nom'] ?? '') . ' ' . ($patient['prenom'] ?? ''))) ?>"
                           readonly>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Tableau principal ─────────────────────────────────── -->
    <div class="pp-card">
        <div class="pp-card-head">
            <i class="bi bi-table"></i> Tableau de surveillance — Salle de travail
        </div>
        <div class="pp-card-body p-0">
            <div class="surv-wrap">
            <table class="surv-table">
                <thead>
                    <!-- Ligne 1 : groupes de colonnes -->
                    <tr>
                        <th colspan="2" rowspan="2" style="min-width:150px;">Paramètre</th>
                        <th colspan="4" class="th-group col-heure1">1ère heure</th>
                        <th colspan="4" class="th-group col-heure23">2ème – 3ème heure</th>
                        <th colspan="3" class="th-group col-heure46">4ème – 6ème heure</th>
                        <th rowspan="2" class="th-group col-transfert-h" style="min-width:70px;">
                            TRANSFERT<br>SUITE DE COUCHES
                        </th>
                        <th rowspan="2" class="th-group col-obs-h" style="min-width:130px;">Observations</th>
                    </tr>
                    <!-- Ligne 2 : temps précis -->
                    <tr>
                        <th class="col-heure1">15 min</th>
                        <th class="col-heure1">30 min</th>
                        <th class="col-heure1">45 min</th>
                        <th class="col-heure1">60 min</th>
                        <th class="col-heure23">1h30</th>
                        <th class="col-heure23">2h</th>
                        <th class="col-heure23">2h30</th>
                        <th class="col-heure23">3h</th>
                        <th class="col-heure46">4h</th>
                        <th class="col-heure46">5h</th>
                        <th class="col-heure46">6h</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                /* ── Colonnes de temps ── */
                $cols = ['15min','30min','45min','60min','1h30','2h','2h30','3h','4h','5h','6h'];

                /* ── Lignes MÈRE ── */
                $mereLignes = [
                    ['État général',     'mere_etat_general'],
                    ['État des conjonctives', 'mere_conjonctives'],
                    ['Température',          'mere_temperature'],
                    ['TA (tension artérielle)', 'mere_ta'],
                    ['Pouls',                'mere_pouls'],
                    ['Globe de sécurité',   'mere_globe'],
                    ['Saignement',          'mere_saignement'],
                    ['Mise au sein',        'mere_mise_au_sein'],
                ];

                /* ── Lignes NNE ── */
                $nneLignes = [
                    ['Collyre',    'nne_collyre'],
                    ['Coloration', 'nne_coloration'],
                    ['Respiration','nne_respiration'],
                    ['Réactivité', 'nne_reactivite'],
                    ['Succion',    'nne_succion'],
                    ['Vit K',      'nne_vitk'],
                    ['Émargement', 'nne_emargement'],
                ];

                $totalMere = count($mereLignes);
                $totalNne  = count($nneLignes);

                /* Rendu d'une ligne */
                $renderRow = function(array $ligne, string $group, int $rowIdx, int $total, string $labelClass) use ($cols, $formData): string {
                    [$label, $key] = $ligne;
                    $out = '<tr class="group-' . $group . '">';
                    /* Cellule de groupe verticale uniquement sur la 1ère ligne */
                    if ($rowIdx === 0) {
                        $groupLabel = strtoupper($group === 'mere' ? 'Mère' : 'NNE');
                        $out .= '<td class="group-label" rowspan="' . $total . '">' . $groupLabel . '</td>';
                    }
                    $out .= '<td class="row-label ' . $labelClass . '">' . htmlspecialchars($label) . '</td>';
                    foreach ($cols as $col) {
                        $name = $key . '_' . $col;
                        $val  = htmlspecialchars($formData[$name] ?? '');
                        $out .= '<td><input type="text" name="' . $name . '" value="' . $val . '"></td>';
                    }
                    /* Colonne transfert */
                    $tKey = $key . '_transfert';
                    $tVal = htmlspecialchars($formData[$tKey] ?? '');
                    $out .= '<td class="col-transfert"><input type="text" name="' . $tKey . '" value="' . $tVal . '"></td>';
                    /* Colonne observations */
                    $oKey = $key . '_obs';
                    $oVal = htmlspecialchars($formData[$oKey] ?? '');
                    $out .= '<td class="col-obs"><input type="text" name="' . $oKey . '" value="' . $oVal . '"></td>';
                    $out .= '</tr>';
                    return $out;
                };

                foreach ($mereLignes as $i => $ligne) {
                    echo $renderRow($ligne, 'mere', $i, $totalMere, '');
                }
                foreach ($nneLignes as $i => $ligne) {
                    echo $renderRow($ligne, 'nne', $i, $totalNne, '');
                }
                ?>
                </tbody>
            </table>
            </div>
        </div>
    </div>

    <!-- ── Observations générales ── -->
    <div class="pp-card">
        <div class="pp-card-head">
            <i class="bi bi-journal-text"></i> Observations générales
        </div>
        <div class="pp-card-body">
            <textarea name="observations_generales" class="form-control" rows="3"
                      placeholder="Observations, incidents, conduite à tenir..."
            ><?= htmlspecialchars($formData['observations_generales'] ?? '') ?></textarea>
        </div>
    </div>

</form>
</div><!-- /pp-wrap -->

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
