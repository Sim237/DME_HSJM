<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<style>
/* ── BASE ── */
.sidebar { display:none!important; }
main { margin-left:0!important; width:100%!important; }
body { background:#0f172a; font-family:'Inter',system-ui,sans-serif; }

/* ── TOPBAR ── */
.dir-topbar {
    background:linear-gradient(135deg,#0f172a,#1e293b);
    border-bottom:1px solid rgba(255,255,255,.06);
    padding:14px 32px;
    position:sticky; top:0; z-index:100;
}

/* ── PAGE ── */
.dir-content { padding:28px 32px; }

/* ── FILTER CARD ── */
.filter-card { background:#1e293b; border:1px solid rgba(255,255,255,.06); border-radius:16px; padding:24px; margin-bottom:24px; }
.filter-label { font-size:.68rem; font-weight:800; text-transform:uppercase; letter-spacing:1.5px; color:#64748b; margin-bottom:6px; }
.form-control-dark, .form-select-dark {
    background:#0f172a; border:1px solid rgba(255,255,255,.1); color:#e2e8f0;
    border-radius:10px; padding:9px 14px; font-size:.83rem; width:100%;
}
.form-control-dark:focus, .form-select-dark:focus { border-color:#3b82f6; outline:none; box-shadow:0 0 0 3px rgba(59,130,246,.15); }
.form-control-dark::placeholder { color:#475569; }
.form-select-dark option { background:#0f172a; color:#e2e8f0; }

/* ── STATS CHIPS ── */
.stat-chip { background:#1e293b; border:1px solid rgba(255,255,255,.07); border-radius:14px; padding:14px 20px; text-align:center; }
.stat-chip .num { font-size:1.6rem; font-weight:900; color:#f1f5f9; line-height:1; }
.stat-chip .lbl { font-size:.68rem; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:#64748b; margin-top:4px; }

/* ── TABLE ── */
.results-card { background:#1e293b; border:1px solid rgba(255,255,255,.06); border-radius:16px; overflow:hidden; }
.results-table { width:100%; border-collapse:collapse; font-size:.82rem; }
.results-table thead tr { background:#0f172a; }
.results-table thead th { padding:14px 18px; color:#64748b; font-weight:700; text-transform:uppercase; font-size:.67rem; letter-spacing:.07em; border:none; white-space:nowrap; }
.results-table tbody tr { border-bottom:1px solid rgba(255,255,255,.04); transition:background .15s; }
.results-table tbody tr:hover { background:rgba(255,255,255,.03); }
.results-table tbody td { padding:14px 18px; color:#cbd5e1; vertical-align:middle; }

/* ── BADGES ── */
.badge-type { display:inline-block; padding:3px 9px; border-radius:20px; font-size:.67rem; font-weight:800; }
.bt-payant     { background:rgba(16,185,129,.15);  color:#10b981; }
.bt-bon        { background:rgba(59,130,246,.15);  color:#3b82f6; }
.bt-assurance  { background:rgba(6,182,212,.15);   color:#06b6d4; }
.bt-famille    { background:rgba(124,58,237,.15);  color:#a78bfa; }
.bt-agents     { background:rgba(139,92,246,.15);  color:#c4b5fd; }

.badge-circuit-php { background:rgba(124,58,237,.2); color:#c4b5fd; border:1px solid rgba(124,58,237,.3); }
.badge-circuit-std { background:rgba(100,116,139,.15); color:#94a3b8; }

/* ── PAGINATION ── */
.pager { display:flex; gap:6px; justify-content:center; padding:20px; }
.pager a, .pager span { background:#0f172a; border:1px solid rgba(255,255,255,.07); color:#94a3b8; padding:7px 14px; border-radius:8px; font-size:.82rem; text-decoration:none; font-weight:600; }
.pager a:hover { border-color:#3b82f6; color:#3b82f6; }
.pager .active { background:#1e40af; color:white; border-color:#1e40af; }
</style>

<!-- TOPBAR -->
<div class="dir-topbar">
    <div class="d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-3">
            <a href="<?= BASE_URL ?>dashboard" class="btn btn-sm btn-outline-secondary rounded-pill px-3" style="border-color:rgba(255,255,255,.15); color:#94a3b8;">
                <i class="bi bi-arrow-left me-1"></i>Dashboard
            </a>
            <div>
                <div style="font-size:1rem; font-weight:800; color:#f1f5f9;">Analyse des Patients</div>
                <div style="font-size:.72rem; color:#64748b;">Direction Générale — Vue analytique</div>
            </div>
        </div>
        <div style="font-size:.78rem; color:#64748b;"><?= date('d/m/Y H:i') ?></div>
    </div>
</div>

<div class="dir-content">

    <!-- ── RÉPARTITION PAR TYPE CLIENT ── -->
    <div class="row g-3 mb-4">
        <?php
        $typeLabels = [
            'PAYANT_COMPTANT'    => ['Payant Comptant',    'bt-payant',    'cash-coin'],
            'BON_PRISE_EN_CHARGE'=> ['Bon P.E.C.',         'bt-bon',       'file-medical'],
            'ASSURANCE'          => ['Assurance',          'bt-assurance', 'shield-check'],
            'FAMILLE_PHP'        => ['Famille PHP',        'bt-famille',   'house-heart'],
            'AGENTS_PHP'         => ['Agents PHP',         'bt-agents',    'person-badge'],
        ];
        $repByType = [];
        foreach ($repartition as $r) $repByType[$r['type_client']] = (int)$r['nb'];
        $totalPatients = array_sum($repByType);
        foreach ($typeLabels as $key => [$label, $cls, $icon]):
            $nb = $repByType[$key] ?? 0;
        ?>
        <div class="col-6 col-md-2">
            <a href="?<?= http_build_query(array_merge($filters, ['type_client' => $key, 'page' => 1])) ?>"
               class="stat-chip d-block text-decoration-none <?= $filters['type_client'] === $key ? 'border-primary' : '' ?>">
                <div class="num <?= $cls ?>"><?= $nb ?></div>
                <div class="lbl"><?= $label ?></div>
            </a>
        </div>
        <?php endforeach; ?>
        <div class="col-6 col-md-2">
            <a href="?<?= http_build_query(array_merge($filters, ['type_client' => '', 'circuit' => '', 'page' => 1])) ?>"
               class="stat-chip d-block text-decoration-none">
                <div class="num" style="color:#f1f5f9;"><?= $totalPatients ?></div>
                <div class="lbl">Total</div>
            </a>
        </div>
    </div>

    <!-- ── FILTRES ── -->
    <div class="filter-card">
        <form method="GET" action="">
            <div class="row g-3 align-items-end">
                <div class="col-md-2">
                    <div class="filter-label">Date début</div>
                    <input type="date" name="date_debut" class="form-control-dark" value="<?= htmlspecialchars($filters['date_debut']) ?>">
                </div>
                <div class="col-md-2">
                    <div class="filter-label">Date fin</div>
                    <input type="date" name="date_fin" class="form-control-dark" value="<?= htmlspecialchars($filters['date_fin']) ?>">
                </div>
                <div class="col-md-2">
                    <div class="filter-label">Type de client</div>
                    <select name="type_client" class="form-select-dark">
                        <option value="">Tous</option>
                        <option value="PAYANT_COMPTANT"     <?= $filters['type_client']==='PAYANT_COMPTANT'?'selected':'' ?>>Payant Comptant</option>
                        <option value="BON_PRISE_EN_CHARGE" <?= $filters['type_client']==='BON_PRISE_EN_CHARGE'?'selected':'' ?>>Bon P.E.C.</option>
                        <option value="ASSURANCE"           <?= $filters['type_client']==='ASSURANCE'?'selected':'' ?>>Assurance</option>
                        <option value="FAMILLE_PHP"         <?= $filters['type_client']==='FAMILLE_PHP'?'selected':'' ?>>Famille PHP</option>
                        <option value="AGENTS_PHP"          <?= $filters['type_client']==='AGENTS_PHP'?'selected':'' ?>>Agents PHP</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <div class="filter-label">Circuit</div>
                    <select name="circuit" class="form-select-dark">
                        <option value="">Tous circuits</option>
                        <option value="STANDARD" <?= $filters['circuit']==='STANDARD'?'selected':'' ?>>Standard</option>
                        <option value="PHP"      <?= $filters['circuit']==='PHP'?'selected':'' ?>>PHP</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <div class="filter-label">Service</div>
                    <select name="service_id" class="form-select-dark">
                        <option value="">Tous services</option>
                        <?php foreach($services as $sv): ?>
                        <option value="<?= $sv['id'] ?>" <?= $filters['service_id'] == $sv['id'] ? 'selected' : '' ?>><?= htmlspecialchars($sv['nom_service']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <div class="filter-label">Sexe</div>
                    <select name="sexe" class="form-select-dark">
                        <option value="">Tous</option>
                        <option value="M" <?= $filters['sexe']==='M'?'selected':'' ?>>Masculin</option>
                        <option value="F" <?= $filters['sexe']==='F'?'selected':'' ?>>Féminin</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <div class="filter-label">Pathologie / Diagnostic</div>
                    <input type="text" name="pathologie" class="form-control-dark" placeholder="CIM10, mot-clé diagnostic…" value="<?= htmlspecialchars($filters['pathologie']) ?>">
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary rounded-3 fw-bold flex-grow-1" style="font-size:.83rem;">
                        <i class="bi bi-funnel-fill me-1"></i>Filtrer
                    </button>
                    <a href="?" class="btn rounded-3 fw-bold" style="background:rgba(255,255,255,.06); color:#94a3b8; font-size:.83rem;" title="Réinitialiser">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- ── RÉSULTATS ── -->
    <div class="results-card">
        <div class="d-flex align-items-center justify-content-between px-4 py-3" style="border-bottom:1px solid rgba(255,255,255,.06);">
            <div style="color:#94a3b8; font-size:.82rem; font-weight:600;">
                <span style="color:#f1f5f9; font-weight:800;"><?= number_format($total_rows) ?></span> patient(s) trouvé(s)
                — Page <?= $page ?> / <?= $total_pages ?>
            </div>
            <div style="font-size:.72rem; color:#475569;">
                Période : <?= date('d/m/Y', strtotime($filters['date_debut'])) ?> → <?= date('d/m/Y', strtotime($filters['date_fin'])) ?>
            </div>
        </div>

        <div class="table-responsive">
            <table class="results-table">
                <thead>
                    <tr>
                        <th>Dossier</th>
                        <th>Patient</th>
                        <th>Sexe / Âge</th>
                        <th>Type client</th>
                        <th>Circuit</th>
                        <th>Service</th>
                        <th>Pathologie</th>
                        <th>Enregistré le</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($patients)): ?>
                    <tr><td colspan="9" class="text-center py-5" style="color:#475569;">
                        <i class="bi bi-search" style="font-size:2rem; opacity:.3;"></i><br>
                        <span style="font-size:.85rem;">Aucun patient ne correspond aux critères.</span>
                    </td></tr>
                    <?php else: foreach($patients as $p):
                        $age = $p['date_naissance'] ? floor((time() - strtotime($p['date_naissance'])) / 31557600) : null;
                        $typeCls = [
                            'PAYANT_COMPTANT'    => 'bt-payant',
                            'BON_PRISE_EN_CHARGE'=> 'bt-bon',
                            'ASSURANCE'          => 'bt-assurance',
                            'FAMILLE_PHP'        => 'bt-famille',
                            'AGENTS_PHP'         => 'bt-agents',
                        ][$p['type_client'] ?? ''] ?? '';
                        $typeLbl = [
                            'PAYANT_COMPTANT'    => 'Payant',
                            'BON_PRISE_EN_CHARGE'=> 'Bon P.E.C.',
                            'ASSURANCE'          => 'Assurance',
                            'FAMILLE_PHP'        => 'Famille PHP',
                            'AGENTS_PHP'         => 'Agents PHP',
                        ][$p['type_client'] ?? ''] ?? ($p['type_client'] ?? '—');
                    ?>
                    <tr>
                        <td><span style="font-family:monospace; color:#94a3b8; font-size:.78rem;"><?= htmlspecialchars($p['dossier_numero']) ?></span></td>
                        <td>
                            <div style="font-weight:700; color:#f1f5f9;"><?= strtoupper(htmlspecialchars($p['nom'])) ?> <?= htmlspecialchars($p['prenom']) ?></div>
                        </td>
                        <td style="color:#94a3b8;">
                            <?= $p['sexe'] === 'M' ? '<i class="bi bi-gender-male text-info"></i>' : '<i class="bi bi-gender-female text-danger"></i>' ?>
                            <?= $age ? " {$age} ans" : '' ?>
                        </td>
                        <td><span class="badge-type <?= $typeCls ?>"><?= $typeLbl ?></span></td>
                        <td>
                            <span class="badge-type <?= $p['circuit'] === 'PHP' ? 'badge-circuit-php' : 'badge-circuit-std' ?>">
                                <?= htmlspecialchars($p['circuit'] ?? 'STANDARD') ?>
                            </span>
                        </td>
                        <td style="color:#94a3b8; font-size:.78rem;"><?= htmlspecialchars($p['nom_service'] ?? '—') ?></td>
                        <td>
                            <?php if(!empty($p['pathologie_principale'])): ?>
                            <span title="<?= htmlspecialchars($p['pathologie_principale']) ?>" style="cursor:help; font-size:.78rem; color:#94a3b8;">
                                <?= htmlspecialchars(substr($p['pathologie_principale'], 0, 28)) ?>…
                            </span>
                            <?php else: ?>
                            <span style="color:#334155;">—</span>
                            <?php endif; ?>
                        </td>
                        <td style="color:#64748b; font-size:.75rem;"><?= date('d/m/Y', strtotime($p['created_at'])) ?></td>
                        <td>
                            <a href="<?= BASE_URL ?>patients/dossier/<?= (int)$p['id'] ?>"
                               class="btn btn-sm rounded-2 fw-bold"
                               style="background:rgba(59,130,246,.15); color:#3b82f6; font-size:.72rem; border:none;">
                                <i class="bi bi-folder2-open me-1"></i>Dossier
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

        <!-- PAGINATION -->
        <?php if($total_pages > 1): ?>
        <div class="pager">
            <?php
            $baseUrl = '?' . http_build_query(array_merge($filters, ['page' => 1]));
            if ($page > 1):
            ?>
            <a href="?<?= http_build_query(array_merge($filters, ['page' => $page - 1])) ?>">&lsaquo; Préc.</a>
            <?php endif;
            $start = max(1, $page - 2);
            $end   = min($total_pages, $page + 2);
            for ($i = $start; $i <= $end; $i++): ?>
            <?php if ($i === $page): ?>
            <span class="active"><?= $i ?></span>
            <?php else: ?>
            <a href="?<?= http_build_query(array_merge($filters, ['page' => $i])) ?>"><?= $i ?></a>
            <?php endif; ?>
            <?php endfor;
            if ($page < $total_pages): ?>
            <a href="?<?= http_build_query(array_merge($filters, ['page' => $page + 1])) ?>">Suiv. &rsaquo;</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
