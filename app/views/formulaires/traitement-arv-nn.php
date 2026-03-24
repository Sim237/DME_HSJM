<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<style>
    :root {
        --ptme-primary: #f57c00;
        --ptme-secondary: #fff3e0;
        --medical-blue: #0d6efd;
        --success-green: #198754;
    }

    body { background-color: #f0f2f5; font-family: 'Inter', sans-serif; }

    .main-card {
        background: white;
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.08);
        border: none;
        margin: 20px auto;
        max-width: 1100px;
        overflow: hidden;
    }

    /* En-tête stylisé */
    .header-gradient {
        background: linear-gradient(135deg, var(--ptme-primary), #ff9800);
        padding: 30px;
        color: white;
    }

    .hospital-info-badge {
        background: rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(5px);
        padding: 10px 20px;
        border-radius: 50px;
        font-size: 0.85rem;
    }

    /* Sections de saisie */
    .section-container {
        padding: 25px;
        border-bottom: 1px solid #eee;
    }

    .section-title {
        color: var(--ptme-primary);
        font-weight: 800;
        text-transform: uppercase;
        font-size: 0.9rem;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    /* Cartes Médicaments */
    .drug-card {
        background: var(--ptme-secondary);
        border-radius: 12px;
        padding: 15px;
        border-left: 4px solid var(--ptme-primary);
        margin-bottom: 10px;
    }

    /* Tableau de suivi interactif */
    .tracking-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0 8px;
    }

    .tracking-table th {
        font-size: 0.75rem;
        text-transform: uppercase;
        color: #6c757d;
        padding: 10px;
        text-align: center;
    }

    .log-row {
        background: #fff;
        box-shadow: 0 2px 5px rgba(0,0,0,0.02);
        transition: transform 0.2s;
    }

    .log-row:hover { transform: scale(1.01); background: #fafafa; }

    .log-row td {
        padding: 15px;
        border-top: 1px solid #eee;
        border-bottom: 1px solid #eee;
        text-align: center;
    }

    .log-row td:first-child { border-left: 1px solid #eee; border-radius: 10px 0 0 10px; }
    .log-row td:last-child { border-right: 1px solid #eee; border-radius: 0 10px 10px 0; }

    /* Boutons de validation rapide */
    .btn-administer {
        border-radius: 50px;
        font-size: 0.75rem;
        font-weight: 700;
        padding: 5px 15px;
        transition: 0.3s;
    }

    .status-done {
        background: #e8f5e9;
        color: #2e7d32;
        border-radius: 50px;
        padding: 4px 12px;
        font-size: 0.8rem;
        font-weight: bold;
    }

    .input-flat {
        border: none;
        border-bottom: 1px dashed #ccc;
        background: transparent;
        font-weight: bold;
        padding: 2px 5px;
    }

    .input-flat:focus { outline: none; border-bottom-color: var(--ptme-primary); color: var(--ptme-primary); }

    @media print {
        .no-print { display: none !important; }
        .main-card { box-shadow: none; max-width: 100%; margin: 0; }
        .header-gradient { background: white !important; color: black !important; border-bottom: 2px solid #000; }
        .btn-administer { display: none; }
    }
</style>

<div class="container-fluid pb-5">
    <!-- Barre d'outils -->
    <div class="d-flex justify-content-between align-items-center py-3 px-4 bg-white border-bottom shadow-sm no-print sticky-top mb-3">
        <a href="<?= BASE_URL ?>patients/dossier/<?= $patient['id'] ?>" class="btn btn-outline-secondary btn-sm rounded-pill">
            <i class="bi bi-arrow-left"></i> Dossier Mère
        </a>
        <div class="d-flex gap-2">
            <button class="btn btn-primary btn-sm rounded-pill" onclick="window.print()"><i class="bi bi-printer"></i> Imprimer</button>
            <button type="submit" form="formPTME" class="btn btn-success btn-sm rounded-pill px-4 shadow">
                <i class="bi bi-cloud-check"></i> Enregistrer la fiche
            </button>
        </div>
    </div>

    <div class="main-card">
        <form id="formPTME" action="<?= BASE_URL ?>formulaire/sauvegarder/traitement-arv-nn" method="POST">
            <input type="hidden" name="patient_mere_id" value="<?= $patient['id'] ?>">

            <!-- HEADER -->
            <div class="header-gradient">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h2 class="fw-bold mb-1">TRAITEMENT ARV NOUVEAU-NÉ</h2>
                        <div class="hospital-info-badge">
                            <i class="bi bi-hospital"></i> HSJM NJOMBÉ • PROTOCOLE PTME
                        </div>
                    </div>
                    <div class="text-end">
                        <div class="small opacity-75 text-uppercase">Mère</div>
                        <h5 class="fw-bold"><?= htmlspecialchars($patient['nom'].' '.$patient['prenom']) ?></h5>
                    </div>
                </div>
            </div>

            <!-- SECTION 1 : HISTORIQUE MATERNEL -->
            <div class="section-container">
                <div class="section-title"><i class="bi bi-shield-check"></i> Antécédents Thérapeutiques de la Mère</div>
                <div class="row">
                    <div class="col-md-6">
                        <label class="small text-muted d-block">ARV reçu 1</label>
                        <input type="text" name="arv_mere_1" class="form-control border-0 bg-light" placeholder="Nom du médicament...">
                    </div>
                    <div class="col-md-6">
                        <label class="small text-muted d-block">ARV reçu 2</label>
                        <input type="text" name="arv_mere_2" class="form-control border-0 bg-light" placeholder="Nom du médicament...">
                    </div>
                </div>
            </div>

            <!-- SECTION 2 : PARAMÈTRES DU BÉBÉ -->
            <div class="section-container bg-light">
                <div class="section-title"><i class="bi bi-baby"></i> Informations du Nouveau-né</div>
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="drug-card">
                            <label class="small fw-bold">Poids de Naissance</label>
                            <div class="input-group input-group-sm">
                                <input type="number" name="poids" class="form-control border-0" placeholder="0000">
                                <span class="input-group-text border-0 bg-white">g</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="drug-card" style="border-left-color: var(--medical-blue);">
                            <label class="small fw-bold">Névirapine (Viramune)</label>
                            <input type="text" name="nev_dose" class="form-control form-control-sm border-0" placeholder="Dose (ml/mg)">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="drug-card" style="border-left-color: var(--medical-blue);">
                            <label class="small fw-bold">Zidovudine (Zidovir)</label>
                            <input type="text" name="zid_dose" class="form-control form-control-sm border-0" placeholder="Dose (ml/mg)">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="drug-card" style="border-left-color: #6a1b9a;">
                            <label class="small fw-bold">Date de Naissance</label>
                            <input type="date" name="dob_nn" class="form-control form-control-sm border-0" value="<?= date('Y-m-d') ?>">
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECTION 3 : REGISTRE D'ADMINISTRATION -->
            <div class="section-container">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="section-title mb-0"><i class="bi bi-calendar-check"></i> Suivi des Prises Quotidiennes</div>
                    <button type="button" class="btn btn-dark btn-sm rounded-pill px-3" onclick="addNewLog()">
                        <i class="bi bi-plus-lg"></i> Nouveau Jour
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="tracking-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Matin (08h)</th>
                                <th>Soir (20h)</th>
                                <th class="no-print">Action</th>
                            </tr>
                        </thead>
                        <tbody id="logBody">
                            <tr class="log-row">
                                <td><input type="text" name="log[date][]" class="input-flat text-center" value="<?= date('d/m') ?>" style="width: 60px;"></td>
                                <td>
                                    <div class="d-flex flex-column align-items-center gap-1">
                                        <input type="text" name="log[matin_qte][]" class="input-flat text-center mb-1" placeholder="Qté" style="width: 80px;">
                                        <button type="button" class="btn btn-outline-primary btn-administer" onclick="quickSign(this)">Valider 08h</button>
                                        <input type="hidden" name="log[matin_staff][]" class="staff-input">
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex flex-column align-items-center gap-1">
                                        <input type="text" name="log[soir_qte][]" class="input-flat text-center mb-1" placeholder="Qté" style="width: 80px;">
                                        <button type="button" class="btn btn-outline-primary btn-administer" onclick="quickSign(this)">Valider 20h</button>
                                        <input type="hidden" name="log[soir_staff][]" class="staff-input">
                                    </div>
                                </td>
                                <td class="no-print">
                                    <button type="button" class="btn btn-link text-danger" onclick="this.closest('tr').remove()"><i class="bi bi-trash"></i></button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    // Ajoute un nouveau jour de traitement
    function addNewLog() {
        const body = document.getElementById('logBody');
        const rows = body.getElementsByClassName('log-row');
        const newRow = rows[rows.length - 1].cloneNode(true);

        // Reset les valeurs
        newRow.querySelectorAll('input').forEach(i => i.value = '');
        newRow.querySelectorAll('.btn-administer').forEach(b => {
            b.style.display = 'inline-block';
            b.classList.remove('btn-success');
            b.classList.add('btn-outline-primary');
            b.innerHTML = b.innerHTML.includes('08h') ? 'Valider 08h' : 'Valider 20h';
        });
        newRow.querySelectorAll('.status-done').forEach(s => s.remove());

        body.appendChild(newRow);
    }

    // Validation rapide par l'infirmier
    function quickSign(btn) {
        const staffName = "<?= $_SESSION['user_initiales'] ?? 'Inf.' ?>";
        const now = new Date();
        const timeStr = now.getHours() + ":" + now.getMinutes().toString().padStart(2, '0');

        const container = btn.parentElement;
        const hiddenStaff = container.querySelector('.staff-input');

        // Simuler la signature
        hiddenStaff.value = staffName + " @ " + timeStr;

        // Changer l'apparence du bouton
        btn.style.display = 'none';
        const badge = document.createElement('div');
        badge.className = 'status-done';
        badge.innerHTML = `<i class="bi bi-check-all"></i> ${staffName} à ${timeStr}`;
        container.appendChild(badge);
    }
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>