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
 require_once __DIR__ . '/../layouts/header.php'; ?>

<style>
body { background: #f0f4f8; }

.lsign-wrap { max-width: 900px; margin: 28px auto; padding: 0 16px 80px; }

/* En-tête page */
.lsign-topbar {
    background: linear-gradient(135deg, #0f172a, #1e3a5f);
    border-radius: 16px; padding: 22px 28px;
    display: flex; justify-content: space-between; align-items: center;
    margin-bottom: 20px; color: white;
    box-shadow: 0 4px 20px rgba(15,23,42,.3);
}
.lsign-topbar h5 { margin: 0; font-size: 1.1rem; font-weight: 800; }
.lsign-topbar .sub { opacity: .7; font-size: .82rem; margin-top: 3px; }
.lsign-count {
    background: rgba(255,255,255,.15);
    border: 1.5px solid rgba(255,255,255,.3);
    border-radius: 10px; padding: 8px 18px;
    font-size: 1.4rem; font-weight: 900;
    text-align: center; line-height: 1.2;
}
.lsign-count small { font-size: .72rem; font-weight: 600; opacity: .8; display: block; }

/* Filtres */
.lsign-filters {
    display: flex; gap: 8px; margin-bottom: 16px; flex-wrap: wrap;
}
.lsign-filter-btn {
    padding: 6px 16px; border-radius: 20px;
    border: 1.5px solid #e2e8f0; background: white;
    color: #64748b; font-size: .8rem; font-weight: 600;
    cursor: pointer; transition: .15s;
}
.lsign-filter-btn.active { background: #0d9488; border-color: #0d9488; color: white; }

/* Carte formulaire */
.fs-card {
    background: white; border-radius: 14px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 2px 8px rgba(0,0,0,.05);
    margin-bottom: 12px; overflow: hidden;
    transition: box-shadow .2s;
}
.fs-card:hover { box-shadow: 0 4px 18px rgba(0,0,0,.1); }

.fs-card-header {
    display: flex; align-items: center; gap: 14px;
    padding: 14px 18px; border-bottom: 1px solid #f1f5f9;
}
.fs-icon {
    width: 46px; height: 46px; border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.2rem; flex-shrink: 0;
    background: #f0fdfa; color: #0d9488;
}
.fs-icon.urgence { background: #fef2f2; color: #dc2626; }
.fs-title { font-weight: 800; font-size: .95rem; color: #1e293b; }
.fs-meta { font-size: .78rem; color: #64748b; margin-top: 3px; display: flex; gap: 12px; flex-wrap: wrap; }
.fs-meta span { display: inline-flex; align-items: center; gap: 4px; }

.fs-badge {
    font-size: .68rem; padding: 3px 10px; border-radius: 12px;
    font-weight: 700; white-space: nowrap;
}
.badge-soumis { background: #dbeafe; color: #1d4ed8; }
.badge-vu     { background: #fef9c3; color: #92400e; }
.badge-signe  { background: #dcfce7; color: #15803d; }
.badge-refuse { background: #fee2e2; color: #dc2626; }

/* Actions */
.fs-card-actions {
    display: flex; align-items: center; gap: 8px;
    padding: 10px 18px; background: #f8fafc;
    justify-content: flex-end; flex-wrap: wrap;
}
.btn-fs {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 7px 16px; border-radius: 8px;
    font-size: .8rem; font-weight: 700; cursor: pointer;
    border: none; transition: .15s; text-decoration: none;
}
.btn-voir    { background: #e0f2fe; color: #0369a1; }
.btn-voir:hover { background: #bae6fd; color: #0369a1; }
.btn-signer  { background: #0d9488; color: white; }
.btn-signer:hover { background: #0f766e; color: white; }
.btn-refuser { background: #f1f5f9; color: #64748b; }
.btn-refuser:hover { background: #fee2e2; color: #dc2626; }

/* État vide */
.lsign-empty {
    text-align: center; padding: 60px 20px;
    color: #94a3b8; background: white;
    border-radius: 14px; border: 1px dashed #e2e8f0;
}
.lsign-empty i { font-size: 3rem; opacity: .25; display: block; margin-bottom: 12px; }

/* Toast confirmation */
.lsign-toast {
    position: fixed; bottom: 24px; right: 24px;
    background: #0d9488; color: white;
    padding: 12px 20px; border-radius: 10px;
    font-size: .88rem; font-weight: 600;
    box-shadow: 0 4px 20px rgba(0,0,0,.2);
    display: none; z-index: 9999;
    animation: slideIn .3s ease;
}
@keyframes slideIn { from { opacity:0; transform:translateY(20px); } to { opacity:1; transform:translateY(0); } }

/* Note refus */
.note-refus {
    margin: 0 18px 10px; padding: 8px 12px;
    background: #fef2f2; border-radius: 8px;
    border-left: 3px solid #dc2626;
    font-size: .8rem; color: #7f1d1d; display: none;
}
.note-refus.show { display: block; }
</style>

<div class="lsign-wrap">

    <!-- Topbar -->
    <div class="lsign-topbar">
        <div>
            <h5><i class="bi bi-file-earmark-check-fill me-2"></i>Formulaires à signer</h5>
            <div class="sub">Soumis par les infirmiers — En attente de votre validation</div>
        </div>
        <div class="lsign-count">
            <?= count($formulaires) ?>
            <small>en attente</small>
        </div>
    </div>

    <!-- Filtres -->
    <?php if (!empty($formulaires)): ?>
    <div class="lsign-filters">
        <button class="lsign-filter-btn active" onclick="filtrer('tous', this)">
            <i class="bi bi-list-ul me-1"></i> Tous (<?= count($formulaires) ?>)
        </button>
        <button class="lsign-filter-btn" onclick="filtrer('SOUMIS', this)">
            <i class="bi bi-circle-fill me-1" style="font-size:.6rem;color:#1d4ed8;"></i>
            Nouveaux (<?= count(array_filter($formulaires, fn($f) => $f['statut'] === 'SOUMIS')) ?>)
        </button>
        <button class="lsign-filter-btn" onclick="filtrer('VU', this)">
            <i class="bi bi-circle-fill me-1" style="font-size:.6rem;color:#92400e;"></i>
            Vus (<?= count(array_filter($formulaires, fn($f) => $f['statut'] === 'VU')) ?>)
        </button>
    </div>
    <?php endif; ?>

    <!-- Liste -->
    <?php if (empty($formulaires)): ?>
    <div class="lsign-empty">
        <i class="bi bi-check2-circle"></i>
        <div style="font-size:1rem;font-weight:700;color:#475569;margin-bottom:6px;">Aucun formulaire en attente</div>
        <div class="small">Tous les formulaires soumis ont été traités.</div>
    </div>

    <?php else: ?>
    <?php foreach ($formulaires as $f):
        $statut     = $f['statut'];
        $badgeCls   = match($statut) { 'SOUMIS'=>'badge-soumis','VU'=>'badge-vu','SIGNE'=>'badge-signe','REFUSE'=>'badge-refuse', default=>'badge-vu' };
        $labStatut  = match($statut) { 'SOUMIS'=>'Nouveau','VU'=>'Vu','SIGNE'=>'Signé','REFUSE'=>'Refusé', default=>$statut };
        $isNew      = $statut === 'SOUMIS';
        $dataId     = (int)($f['formulaire_data_id'] ?? 0);
        $slug       = $f['type_formulaire'];

        // URL "Voir" :
        // - Formulaires en attente (SOUMIS/VU) → page apercu-signer (iframe A4 + pad signature)
        // - Formulaires traités (SIGNE/REFUSE)  → vue print directe
        $voirUrl = in_array($statut, ['SOUMIS','VU'])
            ? BASE_URL . 'formulaire/apercu-signer/' . $f['id']
            : ($dataId
                ? BASE_URL . 'formulaire/imprimer/' . $slug . '/' . $dataId
                : BASE_URL . 'formulaire/creer/'    . $slug . '/' . $f['patient_id']);
    ?>
    <div class="fs-card" id="fscard-<?= $f['id'] ?>" data-statut="<?= $statut ?>">
        <div class="fs-card-header">
            <!-- Icône -->
            <div class="fs-icon <?= $isNew ? 'urgence' : '' ?>">
                <i class="bi bi-file-earmark-<?= $isNew ? 'text-fill' : 'check-fill' ?>"></i>
            </div>
            <!-- Infos -->
            <div class="flex-grow-1">
                <div class="fs-title">
                    <?= htmlspecialchars($f['titre']) ?>
                    <?php if ($isNew): ?>
                    <span style="background:#dbeafe;color:#1d4ed8;font-size:.65rem;padding:2px 8px;border-radius:10px;font-weight:700;margin-left:6px;vertical-align:middle;">NOUVEAU</span>
                    <?php endif; ?>
                </div>
                <div class="fs-meta">
                    <span><i class="bi bi-person-fill"></i>
                        <?= htmlspecialchars($f['patient_nom'] . ' ' . $f['patient_prenom']) ?>
                        <?php if (!empty($f['dossier_numero'])): ?>
                        <span style="color:#94a3b8;">(<?= htmlspecialchars($f['dossier_numero']) ?>)</span>
                        <?php endif; ?>
                    </span>
                    <span><i class="bi bi-nurse"></i>
                        Inf. <?= htmlspecialchars($f['infirmier_nom'] . ' ' . $f['infirmier_prenom']) ?>
                    </span>
                    <span><i class="bi bi-clock"></i>
                        <?= date('d/m/Y à H:i', strtotime($f['date_soumission'])) ?>
                    </span>
                    <?php if (!empty($f['nom_service'])): ?>
                    <span><i class="bi bi-building"></i><?= htmlspecialchars($f['nom_service']) ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <!-- Badge -->
            <span class="fs-badge <?= $badgeCls ?>" id="fsbadge-<?= $f['id'] ?>"><?= $labStatut ?></span>
        </div>

        <!-- Note de refus (cachée par défaut) -->
        <div class="note-refus" id="note-<?= $f['id'] ?>"></div>

        <!-- Actions -->
        <?php if (in_array($statut, ['SOUMIS', 'VU'])): ?>
        <div class="fs-card-actions" id="fsactions-<?= $f['id'] ?>">
            <!-- Voir + Signer (page dédiée) -->
            <a href="<?= $voirUrl ?>" class="btn-fs btn-signer" style="background:#0f172a;">
                <i class="bi bi-eye-fill"></i> Voir & Signer
            </a>
            <!-- Refus rapide sans voir le document -->
            <button class="btn-fs btn-refuser" onclick="actionFormulaire(<?= $f['id'] ?>, 'refuser', '<?= htmlspecialchars($f['titre'], ENT_QUOTES) ?>')">
                <i class="bi bi-x-lg"></i> Refuser
            </button>
        </div>
        <?php else: ?>
        <div class="fs-card-actions">
            <a href="<?= $voirUrl ?>" target="_blank" class="btn-fs btn-voir">
                <i class="bi bi-eye"></i> Voir
            </a>
            <?php if (!empty($f['note_medecin'])): ?>
            <span style="font-size:.8rem;color:#64748b;font-style:italic;">
                Note : <?= htmlspecialchars($f['note_medecin']) ?>
            </span>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>

</div><!-- /lsign-wrap -->

<!-- Toast -->
<div class="lsign-toast" id="lsignToast"></div>

<script>
/* ── Filtrage ────────────────────────────────────────────────────── */
function filtrer(statut, btn) {
    document.querySelectorAll('.lsign-filter-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.querySelectorAll('.fs-card').forEach(card => {
        card.style.display = (statut === 'tous' || card.dataset.statut === statut) ? 'block' : 'none';
    });
}

/* ── Action signer / refuser ─────────────────────────────────────── */
function actionFormulaire(id, action, titre) {
    let note = '';
    if (action === 'refuser') {
        note = prompt('Motif du refus pour « ' + titre + ' » (optionnel) :') ?? '';
        if (note === null) return; // annulé
    }

    const card    = document.getElementById('fscard-'    + id);
    const badge   = document.getElementById('fsbadge-'   + id);
    const actions = document.getElementById('fsactions-' + id);
    const noteEl  = document.getElementById('note-'      + id);

    fetch('<?= BASE_URL ?>formulaire/action-signer', {
        method : 'POST',
        headers: { 'Content-Type': 'application/json' },
        body   : JSON.stringify({ id, action, note })
    })
    .then(r => r.json())
    .then(d => {
        if (!d.success) { alert(d.message || 'Erreur'); return; }

        if (action === 'signer') {
            badge.className = 'fs-badge badge-signe';
            badge.textContent = 'Signé';
            card.dataset.statut = 'SIGNE';
            showToast('<i class="bi bi-check-circle-fill me-2"></i>Formulaire « ' + titre + ' » signé.');
        } else {
            badge.className = 'fs-badge badge-refuse';
            badge.textContent = 'Refusé';
            card.dataset.statut = 'REFUSE';
            if (note) {
                noteEl.textContent = '⚠ Motif du refus : ' + note;
                noteEl.classList.add('show');
            }
            showToast('<i class="bi bi-x-circle-fill me-2"></i>Formulaire refusé.');
        }

        // Masquer les boutons d'action après refus rapide
        if (actions) actions.innerHTML = `
            <span style="font-size:.78rem;color:#94a3b8;font-style:italic;">
                <i class="bi bi-check2 me-1"></i>Traité
            </span>`;

        // Animer la carte
        card.style.transition = 'opacity .4s';
        card.style.opacity = '.55';

        // Mettre à jour le compteur
        updateCount();
    })
    .catch(() => alert('Erreur réseau.'));
}

function updateCount() {
    const enAttente = document.querySelectorAll('.fs-card[data-statut="SOUMIS"], .fs-card[data-statut="VU"]').length;
    document.querySelector('.lsign-count').innerHTML = enAttente + '<small>en attente</small>';
}

function showToast(msg) {
    const t = document.getElementById('lsignToast');
    t.innerHTML = msg;
    t.style.display = 'block';
    setTimeout(() => { t.style.display = 'none'; }, 3000);
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
