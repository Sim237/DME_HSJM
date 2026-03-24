<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<style>
    .app-wrapper { display: flex; width: 100%; min-height: 100vh; background-color: #f1f5f9; }
    .main-content { flex-grow: 1; padding: 2rem; }
    .room-card { background: white; border-radius: 20px; padding: 20px; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.04); border-top: 4px solid #cbd5e1; }
    .bed-icon { width: 55px; height: 55px; border-radius: 12px; display: flex; flex-direction: column; align-items: center; justify-content: center; cursor: pointer; position: relative; transition: 0.2s; border: 2px solid transparent; }
    .status-available { background: #f0fdf4; color: #16a34a; border-color: #bbf7d0; }
    .status-occupied { background: #fef2f2; color: #dc2626; border-color: #fecdd3; }
    .status-cleaning { background: #fffbeb; color: #d97706; border-color: #fef3c7; }
    .gender-indicator { position: absolute; top: -5px; right: -5px; font-size: 0.7rem; padding: 2px 5px; border-radius: 50%; color: white; }
    .bed-name { font-size: 0.65rem; font-weight: 800; margin-top: 3px; }
</style>

<div class="app-wrapper">
    <?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>

    <main class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold text-dark">Plan d'Occupation des Lits</h2>
            <div class="d-flex gap-3 text-center bg-white p-2 rounded-pill shadow-sm px-4">
                <div class="border-end pe-3"><strong><?= $stats['occupes'] ?></strong> <small class="text-danger">Occupés</small></div>
                <div><strong><?= $stats['libres'] ?></strong> <small class="text-success">Libres</small></div>
            </div>
        </div>

        <?php foreach ($plan as $serviceName => $chambres): ?>
            <div class="mb-5 animate__animated animate__fadeIn">
                <h5 class="fw-bold text-dark mb-4 px-2 text-uppercase">Service <?= htmlspecialchars($serviceName) ?></h5>
                <div class="row g-4">
                    <?php foreach ($chambres as $nomChambre => $lits): ?>
                        <div class="col-md-6 col-lg-4 col-xl-3">
                            <div class="room-card">
                                <h6 class="fw-bold text-secondary mb-3 border-bottom pb-2">Chambre <?= htmlspecialchars($nomChambre) ?></h6>
                                <div class="d-flex flex-wrap gap-3">
                                    <?php foreach ($lits as $lit):
                                        $s = strtoupper($lit['statut']);
                                        $class = ($s == 'OCCUPE') ? 'status-occupied' : (($s == 'NETTOYAGE') ? 'status-cleaning' : 'status-available');
                                    ?>
                                        <div class="bed-icon <?= $class ?>" onclick='openBedAction(<?= json_encode($lit) ?>)'>
                                            <?php if($s == 'OCCUPE'): ?>
                                                <span class="gender-indicator <?= $lit['sexe'] == 'M' ? 'bg-primary' : 'bg-danger' ?>"><i class="bi bi-person-fill"></i></span>
                                            <?php endif; ?>
                                            <i class="bi bi-bed-front fs-4"></i>
                                            <span class="bed-name"><?= $lit['nom_lit'] ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </main>
</div>

<!-- MODALE D'ACTION -->
<div class="modal fade" id="modalBedAction" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div id="modalHeader" class="modal-header text-white border-0">
                <h6 class="modal-title fw-bold" id="actionLitTitle">Action Lit</h6>
            </div>
            <div class="modal-body p-4 text-center">
                <div id="patientArea" class="d-none">
                    <p class="small text-muted mb-1">Occupé par :</p>
                    <h5 id="pNameDisplay" class="fw-bold text-dark mb-4">---</h5>
                    <div class="d-grid gap-2">
                        <button class="btn btn-warning rounded-pill fw-bold" onclick="executeDecharge()">Décharger (Sortie)</button>
                        <a id="btnBillet" href="#" target="_blank" class="btn btn-outline-dark rounded-pill btn-sm">Billet de Sortie</a>
                    </div>
                </div>
                <div id="admissionArea" class="d-none">
                    <p class="small text-muted">Lit disponible pour admission</p>
                    <input type="text" id="pSearch" class="form-control mb-2" placeholder="Rechercher patient..." onkeyup="searchP(this.value)">
                    <div id="resP" class="list-group"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let currentLit = null;
const modal = new bootstrap.Modal(document.getElementById('modalBedAction'));

function openBedAction(lit) {
    currentLit = lit;
    document.getElementById('actionLitTitle').innerText = "Lit " + lit.nom_lit;
    document.getElementById('patientArea').classList.add('d-none');
    document.getElementById('admissionArea').classList.add('d-none');

    if(lit.statut === 'OCCUPE') {
        document.getElementById('modalHeader').className = "modal-header bg-danger text-white border-0";
        document.getElementById('patientArea').classList.remove('d-none');
        document.getElementById('pNameDisplay').innerText = lit.nom + " " + lit.prenom;
        document.getElementById('btnBillet').href = `<?= BASE_URL ?>lits/billet-sortie/${lit.patient_id}`;
    } else {
        document.getElementById('modalHeader').className = "modal-header bg-success text-white border-0";
        document.getElementById('admissionArea').classList.remove('d-none');
    }
    modal.show();
}

function searchP(q) {
    if(q.length < 2) return;
    fetch(`<?= BASE_URL ?>lits/get-patients-admissibles?q=${q}`).then(r => r.json()).then(data => {
        let html = "";
        data.forEach(p => { html += `<button class="list-group-item list-group-item-action small" onclick="admit(${p.id})">${p.nom} ${p.prenom}</button>`; });
        document.getElementById('resP').innerHTML = html;
    });
}

function admit(pid) {
    const fd = new FormData(); fd.append('patient_id', pid); fd.append('lit_id', currentLit.id);
    fetch('<?= BASE_URL ?>lits/confirmer-admission', {method:'POST', body:fd}).then(() => location.reload());
}

function executeDecharge() {
    if(!confirm("Libérer ce lit et générer le billet de sortie ?")) return;
    const fd = new FormData(); fd.append('patient_id', currentLit.patient_id); fd.append('lit_id', currentLit.id);
    fetch('<?= BASE_URL ?>lits/decharger', {method:'POST', body:fd})
    .then(r => r.json())
    .then(data => {
        if(data.success) {
            window.open(`<?= BASE_URL ?>lits/billet-sortie/${currentLit.patient_id}`, '_blank');
            location.reload();
        }
    });
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>