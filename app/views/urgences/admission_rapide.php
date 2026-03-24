<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container-fluid p-4">
    <div class="alert bg-danger text-white border-0 shadow rounded-4 p-4 mb-4">
        <h2 class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill"></i> MODE AFLUX MASSIF ACTIVÉ</h2>
        <p class="mb-0 opacity-75">Enregistrement simplifié pour régulation immédiate des victimes.</p>
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light small fw-bold">
                    <tr>
                        <th class="ps-4">Identité</th><th width="100">Sexe</th><th width="100">Âge</th><th>Priorité</th><th width="50"></th>
                    </tr>
                </thead>
                <tbody id="bulkBody">
                    <tr class="bulk-row">
                        <td class="ps-4"><input type="text" class="form-control form-control-sm nom" placeholder="Nom du patient ou description (ex: Inconnu Homme Bleu)"></td>
                        <td><select class="form-select form-select-sm sexe"><option value="M">M</option><option value="F">F</option></select></td>
                        <td><input type="number" class="form-control form-control-sm age" placeholder="Années"></td>
                        <td>
                            <select class="form-select form-select-sm priorite">
                                <option value="P1-VITAL">P1 - VITAL (Rouge)</option>
                                <option value="P2-URGENT">P2 - URGENT (Orange)</option>
                                <option value="P3-STABLE" selected>P3 - STABLE (Vert)</option>
                            </select>
                        </td>
                        <td><button class="btn btn-link text-danger" onclick="this.closest('tr').remove()"><i class="bi bi-trash"></i></button></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white p-4 border-0 d-flex justify-content-between">
            <button class="btn btn-dark rounded-pill px-4" onclick="addBulkRow()"><i class="bi bi-plus-lg"></i> Ajouter un patient</button>
            <button class="btn btn-success btn-lg rounded-pill px-5 shadow" onclick="submitBulk()">Lancer les admissions</button>
        </div>
    </div>
</div>

<script>
function addBulkRow() {
    const body = document.getElementById('bulkBody');
    const newRow = body.querySelector('.bulk-row').cloneNode(true);
    newRow.querySelector('input').value = "";
    body.appendChild(newRow);
}

function submitBulk() {
    const data = [];
    document.querySelectorAll('.bulk-row').forEach(row => {
        data.push({
            nom: row.querySelector('.nom').value,
            sexe: row.querySelector('.sexe').value,
            age_approx: row.querySelector('.age').value,
            priorite: row.querySelector('.priorite').value,
            mode: 'SEUL'
        });
    });

    const fd = new FormData();
    fd.append('patients', JSON.stringify(data));

    fetch('<?= BASE_URL ?>urgences/save-massive', { method: 'POST', body: fd })
    .then(res => res.json())
    .then(res => { if(res.success) window.location.href = '<?= BASE_URL ?>urgences'; });
}
</script>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>