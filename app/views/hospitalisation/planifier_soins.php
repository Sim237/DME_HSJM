<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<!-- Google Fonts & Icons -->
<!--<link rel="stylesheet" href="<?= BASE_URL ?>public/css/bootstrap-icons.css">-->

<style>
    :root {
        --hsjm-blue: #0d6efd;
        --hsjm-light-blue: #eff6ff;
        --text-dark: #1e293b;
        --bg-soft: #f8fafc;
    }

    body {
        background-color: var(--bg-soft);
        font-family: 'Plus Jakarta Sans', sans-serif;
    }

    .card-plan {
        border: none;
        border-radius: 20px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.03);
        background: white;
    }

    .header-fiche {
        background: white;
        border-bottom: 2px solid #f1f5f9;
        padding: 25px 30px;
        border-radius: 20px 20px 0 0;
    }

    /* Section Infos Patient Automatisée */
    .patient-info-banner {
        background-color: var(--hsjm-light-blue);
        border: 1px solid #dbeafe;
        border-radius: 16px;
        padding: 20px;
        margin-bottom: 30px;
    }

    .info-group label {
        font-size: 0.75rem;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 5px;
        display: block;
    }

    .form-control-static {
        background-color: white !important;
        border: 1px solid #e2e8f0;
        font-weight: 600;
        color: var(--text-dark);
        border-radius: 10px;
    }

    /* Grille des catégories de soins */
    .category-card {
        height: 100%;
        border: 1px solid #f1f5f9;
        border-radius: 16px;
        background: white;
        transition: all 0.3s ease;
        overflow: hidden;
    }

    .category-card:hover {
        border-color: var(--hsjm-blue);
        box-shadow: 0 10px 20px rgba(0,0,0,0.05);
    }

    .category-title {
        background: #f8fafc;
        padding: 12px 20px;
        border-bottom: 1px solid #f1f5f9;
        font-weight: 700;
        color: var(--text-dark);
        font-size: 0.85rem;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .category-title i { color: var(--hsjm-blue); font-size: 1.1rem; }

    .btn-add-line {
        font-size: 0.8rem;
        color: var(--hsjm-blue);
        font-weight: 700;
        text-decoration: none;
        padding: 5px 10px;
        border-radius: 8px;
        transition: 0.2s;
    }

    .btn-add-line:hover { background: var(--hsjm-light-blue); }

    .input-time-custom { max-width: 90px; font-weight: 700; text-align: center; border-right: none; border-radius: 10px 0 0 10px; }
    .input-desc-custom { border-radius: 0; }
    .input-cond-custom {
        max-width: 130px; border-radius: 0; font-size: .78rem;
        border-left: 2px dashed #fbbf24; background: #fffbeb; color: #92400e;
        font-style: italic;
    }
    .input-cond-custom::placeholder { color: #d97706; }

    /* Ligne rayée */
    .soin-ligne { position: relative; }
    .soin-ligne.barree { opacity: .5; }
    .soin-ligne.barree input { text-decoration: line-through; background: #fee2e2 !important; pointer-events: none; }
    .badge-corr { background:#ede9fe; color:#7c3aed; font-size:.65rem; font-weight:700;
        border-radius:20px; padding:1px 8px; white-space:nowrap; }

    /* Boutons inline */
    .btn-rayer-plan {
        border: none; background: none; color: #f87171; font-size: .7rem;
        padding: 0 6px; cursor: pointer; opacity: .7; transition: opacity .15s;
        flex-shrink: 0;
    }
    .btn-rayer-plan:hover { opacity: 1; }
    .btn-suppr { border-radius: 0 10px 10px 0; }

    .btn-save-plan {
        background: var(--hsjm-blue);
        color: white;
        padding: 12px 30px;
        border-radius: 12px;
        font-weight: 700;
        border: none;
        box-shadow: 0 4px 12px rgba(13, 110, 253, 0.2);
    }
</style>

<div class="container-fluid py-4 px-4">
    <form action="<?= BASE_URL ?>hospitalisation/save-plan" method="POST">
        <input type="hidden" name="patient_id" value="<?= $patient['id'] ?>">

        <div class="card card-plan">
            <!-- HEADER DE LA FICHE -->
            <div class="header-fiche d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-primary bg-opacity-10 p-3 rounded-circle">
                        <i class="bi bi-calendar2-check text-primary fs-4"></i>
                    </div>
                    <div>
                        <h4 class="fw-bold mb-0">Planification des Soins Quotidiens</h4>
                        <small class="text-muted">Établissement du protocole de soins pour les prochaines 24h</small>
                    </div>
                </div>
                <div class="d-flex gap-3 align-items-center">
                    <a href="<?= BASE_URL ?>dashboard" class="btn btn-light fw-semibold rounded-pill px-3">
                        <i class="bi bi-house me-1"></i>Tableau de bord
                    </a>
                    <a href="<?= BASE_URL ?>hospitalisation/suivi/<?= $patient['id'] ?>" class="btn btn-outline-secondary fw-semibold rounded-pill px-3">
                        <i class="bi bi-arrow-left me-1"></i>Suivi patient
                    </a>
                    <button type="submit" class="btn btn-save-plan shadow">
                        <i class="bi bi-check2-circle me-2"></i>Valider la planification
                    </button>
                </div>
            </div>

            <div class="card-body p-4">
                <!-- BANNIÈRE INFOS PATIENT (PRÉ-REMPLIE) -->
                <div class="patient-info-banner shadow-sm">
                    <div class="row g-4">
                        <div class="col-md-3">
                            <div class="info-group">
                                <label>Patient</label>
                                <div class="fw-bold text-dark fs-5"><?= htmlspecialchars($patient['nom'] . ' ' . $patient['prenom']) ?></div>
                                <small class="text-muted">N° Dossier: <?= $patient['dossier_numero'] ?></small>
                            </div>
                        </div>
                        <div class="col-md-1 border-start border-end border-2 border-white px-4">
                            <div class="info-group">
                                <label>Âge</label>
                                <div class="fw-bold fs-5"><?= $age ?> ans</div>
                            </div>
                        </div>

                        <!-- SERVICE (Récupéré de $loc) -->
                        <div class="col-md-3">
                            <div class="info-group">
                                <label>Service d'accueil</label>
                                <input type="text" name="service" class="form-control form-control-sm form-control-static"
                                       value="<?= htmlspecialchars($loc['nom_service'] ?? 'Non assigné') ?>" readonly>
                            </div>
                        </div>

                        <!-- CHAMBRE (Récupérée de $loc) -->
                        <div class="col-md-2">
                            <div class="info-group">
                                <label>Chambre</label>
                                <input type="text" name="chambre" class="form-control form-control-sm form-control-static text-center"
                                       value="<?= htmlspecialchars($loc['nom_chambre'] ?? '--') ?>" readonly>
                            </div>
                        </div>

                        <!-- LIT (Récupéré de $loc) -->
                        <div class="col-md-2">
                            <div class="info-group">
                                <label>Lit n°</label>
                                <input type="text" name="lit" class="form-control form-control-sm form-control-static text-center"
                                       value="<?= htmlspecialchars($loc['nom_lit'] ?? '--') ?>" readonly>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- GRILLE DES SOINS -->
                <div class="row g-4">
                    <?php
                    $categories = [
                        'PER_OS' => ['label' => 'MÉDICAMENTS PER OS', 'icon' => 'bi-capsule', 'placeholder' => 'ex: Paracétamol 1g...'],
                        'IV' => ['label' => 'VOIE INTRA-VEINEUSE (IV)', 'icon' => 'bi-droplet-half', 'placeholder' => 'ex: Perfusion G5%...'],
                        'IM' => ['label' => 'INTRA-MUSCULAIRE (IM)', 'icon' => 'bi-syringe', 'placeholder' => 'Description de l\'injection...'],
                        'SC' => ['label' => 'SOUS-CUTANÉE (SC)', 'icon' => 'bi-patch-check', 'placeholder' => 'ex: Insuline, Héparine...'],
                        'NURSING' => ['label' => 'SOINS DE NURSING', 'icon' => 'bi-heart-pulse', 'placeholder' => 'ex: Toilette, Change, Prévention escarres...'],
                        'ALIMENTATION' => ['label' => 'RÉGIME / ALIMENTATION', 'icon' => 'bi-egg-fried', 'placeholder' => 'ex: Sans sel, Mixé...'],
                        'SURVEILLANCE' => ['label' => 'SURVEILLANCE SPÉCIFIQUE', 'icon' => 'bi-eye', 'placeholder' => 'ex: Diurèse, Conscience...']
                    ];
                    foreach($categories as $key => $cat): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="category-card shadow-sm">
                                <div class="category-title">
                                    <i class="bi <?= $cat['icon'] ?>"></i><?= $cat['label'] ?>
                                </div>
                                <div class="p-3" id="container-<?= $key ?>">
                                    <!-- Ligne par défaut -->
                                    <div class="input-group mb-2 shadow-sm soin-ligne">
                                        <input type="time" name="soins[<?= $key ?>][heure][]" class="form-control form-control-sm input-time-custom">
                                        <input type="text" name="soins[<?= $key ?>][desc][]" class="form-control form-control-sm input-desc-custom" placeholder="<?= $cat['placeholder'] ?>">
                                        <input type="text" name="soins[<?= $key ?>][condition][]" class="form-control form-control-sm input-cond-custom" placeholder="⚡ si fièvre…" title="Condition d'application (optionnel)">
                                        <button type="button" class="btn btn-sm btn-outline-warning btn-rayer-plan px-2" onclick="rayerLigne(this)" title="Rayer cette ligne">
                                            <i class="bi bi-pencil-slash"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger btn-suppr px-2" onclick="this.closest('.soin-ligne').remove()" title="Supprimer">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="px-3 pb-3">
                                    <button type="button" class="btn btn-add-line" onclick="addRow('<?= $key ?>', '<?= addslashes($cat['placeholder']) ?>')">
                                        <i class="bi bi-plus-circle-dotted me-1"></i>Ajouter une ligne
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Footer info -->
            <div class="card-footer bg-light border-0 p-4 text-center">
                <p class="text-muted small mb-0">
                    <i class="bi bi-info-circle me-1"></i>
                    Après validation, ces soins apparaîtront dans la checklist quotidienne de l'infirmier(e) de service.
                </p>
            </div>
        </div>
    </form>
</div>

<script>
/* ── Ajouter une ligne de soin ── */
function addRow(cat, placeholder) {
    const container = document.getElementById('container-' + cat);
    const div = document.createElement('div');
    div.className = 'input-group mb-2 shadow-sm soin-ligne';
    div.innerHTML = `
        <input type="time" name="soins[${cat}][heure][]" class="form-control form-control-sm input-time-custom">
        <input type="text" name="soins[${cat}][desc][]" class="form-control form-control-sm input-desc-custom" placeholder="${placeholder}">
        <input type="text" name="soins[${cat}][condition][]" class="form-control form-control-sm input-cond-custom" placeholder="⚡ si fièvre…" title="Condition d'application (optionnel)">
        <button type="button" class="btn btn-sm btn-outline-warning btn-rayer-plan px-2" onclick="rayerLigne(this)" title="Rayer cette ligne">
            <i class="bi bi-pencil-slash"></i>
        </button>
        <button type="button" class="btn btn-sm btn-outline-danger btn-suppr px-2" onclick="this.closest('.soin-ligne').remove()" title="Supprimer">
            <i class="bi bi-trash"></i>
        </button>
    `;
    container.appendChild(div);
}

/* ── Rayer une ligne erronée et créer la correction ── */
function rayerLigne(btn) {
    const ligne = btn.closest('.soin-ligne');
    const container = ligne.parentElement;

    // Récupérer les valeurs avant de barrer
    const heure = ligne.querySelector('input[type=time]').value;
    const desc  = ligne.querySelector('.input-desc-custom').value;
    const cond  = ligne.querySelector('.input-cond-custom')?.value || '';

    // Barrer la ligne
    ligne.classList.add('barree');
    ligne.querySelectorAll('input').forEach(i => { i.disabled = true; i.removeAttribute('name'); });
    btn.remove(); // retirer le bouton rayer

    // Ajouter note visuelle
    const note = document.createElement('div');
    note.className = 'd-flex align-items-center gap-2 px-1 mb-1';
    note.innerHTML = `<span style="font-size:.65rem;color:#dc2626;font-weight:700;">
        <i class="bi bi-slash-circle me-1"></i>Ligne annulée (erreur de planification)
    </span>`;
    ligne.after(note);

    // Créer la ligne corrigée (pré-remplie)
    const cat = ligne.querySelector('input[type=time]')?.name?.match(/soins\[([^\]]+)\]/)?.[1]
             || container.id.replace('container-', '');

    const corrDiv = document.createElement('div');
    corrDiv.className = 'input-group mb-2 shadow-sm soin-ligne';
    corrDiv.innerHTML = `
        <span class="input-group-text badge-corr border-0 px-2" style="background:#ede9fe;color:#7c3aed;font-size:.7rem;border-radius:10px 0 0 10px;">CORR.</span>
        <input type="time" name="soins[${cat}][heure][]" class="form-control form-control-sm input-time-custom" value="${heure}" style="border-radius:0;">
        <input type="text" name="soins[${cat}][desc][]" class="form-control form-control-sm input-desc-custom" value="${desc}" placeholder="Description corrigée…">
        <input type="text" name="soins[${cat}][condition][]" class="form-control form-control-sm input-cond-custom" value="${cond}" placeholder="⚡ si fièvre…">
        <button type="button" class="btn btn-sm btn-outline-warning btn-rayer-plan px-2" onclick="rayerLigne(this)" title="Rayer">
            <i class="bi bi-pencil-slash"></i>
        </button>
        <button type="button" class="btn btn-sm btn-outline-danger btn-suppr px-2" onclick="this.closest('.soin-ligne').remove()" title="Supprimer">
            <i class="bi bi-trash"></i>
        </button>
    `;
    note.after(corrDiv);
    corrDiv.querySelector('.input-desc-custom').focus();
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>