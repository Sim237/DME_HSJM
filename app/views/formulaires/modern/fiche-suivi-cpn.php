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
/* ══ Design System CPN ══════════════════════════════════════════ */
:root {
    --cpn-teal:    #0d9488;
    --cpn-teal-lt: #f0fdfa;
    --cpn-dark:    #1e293b;
    --cpn-muted:   #64748b;
    --border:      #e2e8f0;
}
body { background: #f0f4f8; }

/* ── Topbar ── */
.cpn-topbar {
    position: sticky; top: 0; z-index: 200;
    background: white; border-bottom: 1px solid var(--border);
    padding: 10px 20px; display: flex; justify-content: space-between;
    align-items: center; box-shadow: 0 2px 8px rgba(0,0,0,.07);
}
.btn-back {
    display:inline-flex;align-items:center;gap:6px;padding:8px 14px;
    border-radius:8px;border:1.5px solid var(--border);background:white;
    color:#475569;font-weight:600;font-size:.85rem;text-decoration:none;
}
.btn-back:hover { background:#f8fafc; }
.btn-save {
    display:inline-flex;align-items:center;gap:6px;padding:9px 20px;
    border-radius:8px;border:none;background:var(--cpn-teal);color:white;
    font-weight:700;font-size:.88rem;cursor:pointer;transition:.2s;
}
.btn-save:hover { background:#0f766e; }

/* ── Bannière patient ── */
.cpn-banner {
    background: linear-gradient(135deg, #0d9488, #0f766e);
    border-radius: 16px; padding: 16px 24px; color: white;
    margin-bottom: 20px; display: flex; justify-content: space-between;
    align-items: center; flex-wrap: wrap; gap: 12px;
}
.cpn-badge {
    background: rgba(255,255,255,.18); border-radius: 8px;
    padding: 5px 12px; font-size: .8rem; font-weight: 600;
}

/* ── Section card ── */
.cpn-section {
    background: white; border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,.06);
    margin-bottom: 16px; overflow: hidden;
}
.cpn-section-head {
    background: var(--cpn-teal-lt); padding: 11px 18px;
    font-weight: 700; font-size: .82rem; text-transform: uppercase;
    letter-spacing: .5px; display: flex; align-items: center; gap: 8px;
    border-bottom: 2px solid var(--cpn-teal); color: var(--cpn-teal);
    justify-content: space-between;
}
.cpn-section-body { padding: 0; overflow-x: auto; }

/* ── Tableau de saisie ── */
.cpn-table {
    border-collapse: collapse;
    width: 100%;
    min-width: 1100px;
    font-size: .78rem;
}
.cpn-table th {
    background: #f8fafc; border: 1px solid #cbd5e1;
    padding: 7px 6px; text-align: center;
    font-weight: 700; color: var(--cpn-muted);
    white-space: nowrap; position: sticky; top: 0;
}
.cpn-table th.th-main { color: var(--cpn-dark); background: #f1f5f9; }
.cpn-table td { border: 1px solid #cbd5e1; padding: 2px 3px; vertical-align: middle; }
.cpn-table .td-num {
    background: var(--cpn-teal-lt); color: var(--cpn-teal);
    font-weight: 800; text-align: center; font-size: .8rem;
    width: 32px;
}
.cpn-table input, .cpn-table textarea, .cpn-table select {
    width: 100%; border: none; outline: none;
    font-size: .78rem; background: transparent;
    padding: 4px 5px; color: var(--cpn-dark); font-weight: 600;
    resize: none; font-family: inherit;
}
.cpn-table input:focus, .cpn-table textarea:focus {
    background: #f0fdfa; border-radius: 4px;
}
.cpn-table .td-yn input[type=checkbox] { width: 16px; height: 16px; cursor: pointer; }
.cpn-table tr:hover > td { background: #f8fefd; }
.cpn-table tr.row-filled > td { background: #f0fdfa; }

/* Colonne supprimer */
.btn-del-row {
    width: 26px; height: 26px; border: none; border-radius: 6px;
    background: #fee2e2; color: #dc2626; cursor: pointer;
    font-size: .9rem; display: inline-flex; align-items: center;
    justify-content: center; transition: .15s; flex-shrink: 0;
}
.btn-del-row:hover { background: #dc2626; color: white; }

/* ── Infos générales ── */
.form-label { font-weight:600;font-size:.78rem;color:var(--cpn-muted);
    text-transform:uppercase;letter-spacing:.4px;margin-bottom:4px; }
.form-control,.form-select {
    border:1.5px solid var(--border);border-radius:8px;
    font-size:.9rem;padding:9px 12px;transition:.2s; }
.form-control:focus,.form-select:focus {
    border-color:var(--cpn-teal);
    box-shadow:0 0 0 3px rgba(13,148,136,.12);outline:none; }

/* ── Bouton ajouter ligne ── */
.btn-add-row {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 8px 18px; border-radius: 8px;
    border: 2px dashed var(--cpn-teal); background: var(--cpn-teal-lt);
    color: var(--cpn-teal); font-weight: 700; font-size: .82rem;
    cursor: pointer; transition: .2s; width: 100%;
    justify-content: center; margin-top: 4px;
}
.btn-add-row:hover { background: #ccfbf1; }

.cpn-wrap { max-width: 1400px; margin: 0 auto; padding: 18px 16px 100px; }

/* Indicateur rempli */
.fill-dot {
    width: 8px; height: 8px; border-radius: 50%;
    background: var(--cpn-teal); display: inline-block; margin-left: 6px;
}

@media print {
    .cpn-topbar { display: none !important; }
    body { background: white !important; }
}
</style>

<!-- ── Topbar ─────────────────────────────────────────────────── -->
<div class="cpn-topbar">
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
        <div style="font-weight:800;font-size:.95rem;color:var(--cpn-dark);">
            <i class="bi bi-table" style="color:var(--cpn-teal);"></i>
            Fiche de suivi femme enceinte (CPN)
        </div>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill"
                onclick="window.print()">
            <i class="bi bi-printer"></i> Imprimer
        </button>
        <button type="submit" form="formCPN" class="btn-save">
            <i class="bi bi-save2-fill"></i> Enregistrer
        </button>
    </div>
</div>

<div class="cpn-wrap">

<!-- ── Bannière patient ── -->
<div class="cpn-banner">
    <div>
        <div style="font-size:1.2rem;font-weight:800;">
            <?= htmlspecialchars(($patient['nom'] ?? '') . ' ' . ($patient['prenom'] ?? '')) ?>
        </div>
        <div style="font-size:.83rem;opacity:.88;margin-top:4px;">
            <?= $age ?> ans &nbsp;·&nbsp;
            Dossier : <?= htmlspecialchars($patient['dossier_numero'] ?? '—') ?>
        </div>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <span class="cpn-badge"><i class="bi bi-calendar3 me-1"></i><?= date('d/m/Y') ?></span>
        <span class="cpn-badge"><i class="bi bi-heart-pulse me-1"></i>CPN — Obstétrique</span>
    </div>
</div>

<form id="formCPN" action="#" method="POST">
    <input type="hidden" name="patient_id" value="<?= (int)$patient['id'] ?>">
    <?php if ($hosp_id): ?>
    <input type="hidden" name="hosp_id" value="<?= (int)$hosp_id ?>">
    <?php endif; ?>
    <input type="hidden" name="nb_rows" id="nb_rows" value="10">

    <!-- ── Infos générales ────────────────────────────────── -->
    <div class="cpn-section">
        <div class="cpn-section-head">
            <span><i class="bi bi-info-circle"></i> Informations générales</span>
        </div>
        <div class="p-4">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Date de la consultation / registre</label>
                    <input type="date" name="date_registre" class="form-control"
                           value="<?= date('Y-m-d') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Formation sanitaire</label>
                    <input type="text" name="formation_sanitaire" class="form-control"
                           value="Hôpital Saint-Jean de Malte">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Responsable / Sage-femme</label>
                    <input type="text" name="responsable" class="form-control"
                           value="<?= htmlspecialchars($medecin_nom ?? '') ?>">
                </div>
            </div>
        </div>
    </div>

    <!-- ── Tableau de suivi ───────────────────────────────── -->
    <div class="cpn-section">
        <div class="cpn-section-head">
            <span><i class="bi bi-table"></i> Registre des consultations prénatales</span>
            <span style="font-size:.73rem;color:var(--cpn-muted);font-weight:500;text-transform:none;">
                <i class="bi bi-info-circle"></i>
                Cliquez dans une cellule pour saisir · La ligne se colore automatiquement
            </span>
        </div>
        <div class="cpn-section-body">
            <table class="cpn-table" id="cpnTable">
                <thead>
                    <tr>
                        <th class="th-main" style="width:34px;">N°</th>
                        <th class="th-main" style="min-width:140px;">Nom et Prénoms</th>
                        <th style="min-width:90px;">DDN</th>
                        <th style="min-width:90px;">Poids / AG</th>
                        <th style="min-width:160px;">Signes et Symptômes</th>
                        <th style="min-width:130px;">Diagnostic</th>
                        <th style="min-width:130px;">Examens</th>
                        <th style="min-width:70px;">Syphilis / VIH</th>
                        <th style="min-width:50px;">TB</th>
                        <th style="min-width:160px;">Prescriptions</th>
                        <th style="min-width:60px;">LIA</th>
                        <th style="width:34px;"></th>
                    </tr>
                </thead>
                <tbody id="cpnBody">
                    <?php
                    /* Nom et DDN du patient courant — pré-remplis sur TOUTES les lignes
                       car ce formulaire suit UNE seule patiente (chaque ligne = une visite CPN) */
                    $patientNomComplet = trim(($patient['nom'] ?? '') . ' ' . ($patient['prenom'] ?? ''));
                    $patientDDN = '';
                    if (!empty($patient['date_naissance'])) {
                        try { $patientDDN = (new DateTime($patient['date_naissance']))->format('d/m/Y'); }
                        catch (\Throwable $e) { $patientDDN = $patient['date_naissance']; }
                    }
                    for ($i = 0; $i < 10; $i++):
                    ?>
                    <tr data-idx="<?= $i ?>" oninput="markRow(this)">
                        <td class="td-num"><?= $i + 1 ?></td>
                        <td><input type="text" name="r<?=$i?>_nom" placeholder="Nom prénom…"
                                   value="<?= htmlspecialchars($patientNomComplet) ?>"></td>
                        <td><input type="text" name="r<?=$i?>_ddn" placeholder="jj/mm/aaaa"
                                   value="<?= htmlspecialchars($patientDDN) ?>"></td>
                        <td><input type="text" name="r<?=$i?>_poids_ag" placeholder="kg / SA"></td>
                        <td><textarea name="r<?=$i?>_signes" rows="1" placeholder="Signes, symptômes…" style="min-height:30px;"></textarea></td>
                        <td><textarea name="r<?=$i?>_diag" rows="1" placeholder="Diagnostic…" style="min-height:30px;"></textarea></td>
                        <td><textarea name="r<?=$i?>_examens" rows="1" placeholder="Examens demandés…" style="min-height:30px;"></textarea></td>
                        <td class="td-yn" style="text-align:center;">
                            <select name="r<?=$i?>_syvh" style="font-size:.72rem;padding:3px;">
                                <option value="">—</option>
                                <option value="Neg">Négatif</option>
                                <option value="Pos">Positif</option>
                                <option value="ND">ND</option>
                            </select>
                        </td>
                        <td class="td-yn" style="text-align:center;">
                            <select name="r<?=$i?>_tb" style="font-size:.72rem;padding:3px;">
                                <option value="">—</option>
                                <option value="Neg">Neg</option>
                                <option value="Pos">Pos</option>
                                <option value="ND">ND</option>
                            </select>
                        </td>
                        <td><textarea name="r<?=$i?>_presc" rows="1" placeholder="Prescriptions…" style="min-height:30px;"></textarea></td>
                        <td><input type="text" name="r<?=$i?>_lia" placeholder="LIA"></td>
                        <td style="text-align:center;padding:4px;">
                            <button type="button" class="btn-del-row" onclick="deleteRow(this)" title="Supprimer cette ligne">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endfor; /* fin de la boucle des 10 lignes */ ?>
                </tbody>
            </table>
        </div>

        <!-- Ajouter une ligne -->
        <div class="p-3 pt-2">
            <button type="button" class="btn-add-row" onclick="addRow()">
                <i class="bi bi-plus-circle-fill"></i> Ajouter une ligne
            </button>
        </div>
    </div>

    <!-- ── Observations générales ── -->
    <div class="cpn-section">
        <div class="cpn-section-head">
            <span><i class="bi bi-journal-text"></i> Observations générales</span>
        </div>
        <div class="p-3">
            <textarea name="observations" class="form-control" rows="3"
                      placeholder="Remarques, observations de la séance CPN…"></textarea>
        </div>
    </div>

</form>
</div><!-- /cpn-wrap -->

<script>
/* ══════════════════════════════════════════════════════════
   REGISTRE CPN — Gestion dynamique des lignes
   ══════════════════════════════════════════════════════════
   Ce formulaire suit UNE seule patiente.
   Chaque ligne = une consultation prénatale (visite CPN).
   Le Nom et la DDN sont pré-remplis sur toutes les lignes.
   ══════════════════════════════════════════════════════════ */

/* Données de la patiente injectées depuis PHP */
const _patNom = <?= json_encode($patientNomComplet) ?>;
const _patDDN = <?= json_encode($patientDDN) ?>;

/*
 * _uidCounter : compteur UNIQUE pour les attributs `name` (jamais décrémenté).
 *   → Garantit qu'aucun champ n'écrase un autre après suppression/ajout.
 *
 * La numérotation VISUELLE (N°) est recalculée à chaque modification
 * via renumberRows() — elle reflète toujours la position réelle dans la liste.
 */
let _uidCounter = 10;   /* 10 lignes pré-créées en PHP (indices 0–9) */

/* ── Colorier une ligne quand elle contient des données ─── */
function markRow(tr) {
    const filled = [...tr.querySelectorAll('input, textarea, select')]
        .some(el => el.value.trim() !== '' && el.value !== '—');
    tr.classList.toggle('row-filled', filled);
}

/* ── Renuméroter la colonne N° selon la position réelle ─── */
function renumberRows() {
    document.querySelectorAll('#cpnBody tr').forEach((tr, idx) => {
        const td = tr.querySelector('.td-num');
        if (td) td.textContent = idx + 1;
    });
    /* Mettre à jour nb_rows pour la sauvegarde */
    document.getElementById('nb_rows').value =
        document.querySelectorAll('#cpnBody tr').length;
}

/* ── Ajouter une ligne ─────────────────────────────────── */
function addRow() {
    const uid = _uidCounter++;   /* UID unique pour les attributs name */

    const tr = document.createElement('tr');
    tr.dataset.idx = uid;
    tr.setAttribute('oninput', 'markRow(this)');

    tr.innerHTML = `
        <td class="td-num">?</td>
        <td><input type="text" name="r${uid}_nom" placeholder="Nom prénom…" value="${_patNom.replace(/"/g,'&quot;')}"></td>
        <td><input type="text" name="r${uid}_ddn" placeholder="jj/mm/aaaa" value="${_patDDN.replace(/"/g,'&quot;')}"></td>
        <td><input type="text" name="r${uid}_poids_ag" placeholder="kg / SA"></td>
        <td><textarea name="r${uid}_signes" rows="1"
                      placeholder="Signes, symptômes…" style="min-height:30px;"></textarea></td>
        <td><textarea name="r${uid}_diag" rows="1"
                      placeholder="Diagnostic…" style="min-height:30px;"></textarea></td>
        <td><textarea name="r${uid}_examens" rows="1"
                      placeholder="Examens demandés…" style="min-height:30px;"></textarea></td>
        <td class="td-yn" style="text-align:center;">
            <select name="r${uid}_syvh" style="font-size:.72rem;padding:3px;">
                <option value="">—</option>
                <option value="Neg">Négatif</option>
                <option value="Pos">Positif</option>
                <option value="ND">ND</option>
            </select>
        </td>
        <td class="td-yn" style="text-align:center;">
            <select name="r${uid}_tb" style="font-size:.72rem;padding:3px;">
                <option value="">—</option>
                <option value="Neg">Neg</option>
                <option value="Pos">Pos</option>
                <option value="ND">ND</option>
            </select>
        </td>
        <td><textarea name="r${uid}_presc" rows="1"
                      placeholder="Prescriptions…" style="min-height:30px;"></textarea></td>
        <td><input type="text" name="r${uid}_lia" placeholder="LIA"></td>
        <td style="text-align:center;padding:4px;">
            <button type="button" class="btn-del-row"
                    onclick="deleteRow(this)" title="Supprimer cette ligne">
                <i class="bi bi-x-lg"></i>
            </button>
        </td>`;

    document.getElementById('cpnBody').appendChild(tr);

    /* Renuméroter APRÈS avoir ajouté → N° correct immédiatement */
    renumberRows();

    /* Scroll + focus */
    tr.scrollIntoView({ behavior: 'smooth', block: 'center' });
    tr.querySelector('input').focus();
}

/* ── Supprimer une ligne ───────────────────────────────── */
function deleteRow(btn) {
    const allRows = document.querySelectorAll('#cpnBody tr');
    if (allRows.length <= 1) return;  /* conserver au moins 1 ligne */

    const tr = btn.closest('tr');
    tr.style.transition = 'opacity .2s, transform .2s';
    tr.style.opacity    = '0';
    tr.style.transform  = 'translateX(8px)';

    setTimeout(() => {
        tr.remove();
        renumberRows();   /* recalcul immédiat après suppression */
    }, 200);
}

/* ── Init : colorier les lignes déjà remplies ──────────── */
document.querySelectorAll('#cpnBody tr').forEach(tr => markRow(tr));
</script>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
