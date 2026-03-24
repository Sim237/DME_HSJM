<style>
    .kit-card { border-left: 5px solid #198754; transition: 0.3s; cursor: pointer; }
    .kit-card.used { border-left-color: #6c757d; opacity: 0.6; }
    .facture-box { background: #f8f9fa; border: 2px dashed #0d6efd; padding: 20px; border-radius: 10px; }
    .price-total { font-size: 2.5rem; font-weight: 900; color: #0d6efd; }
</style>

<div class="container-fluid p-4">
    <div class="row g-4">
        <!-- GESTION DU MATÉRIEL (TRAÇABILITÉ) -->
        <div class="col-md-7">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white py-3">
                    <h5 class="fw-bold mb-0"><i class="bi bi-box-seam me-2"></i>Kits Stériles Utilisés</h5>
                </div>
                <div class="card-body">
                    <div class="input-group mb-4">
                        <span class="input-group-text"><i class="bi bi-qr-code-scan"></i></span>
                        <input type="text" id="scanKit" class="form-control" placeholder="Scanner le code du kit (ex: KIT-001)..." onchange="useKit(this.value)">
                    </div>

                    <div id="kitList" class="row g-3">
                        <!-- Exemple de Kit disponible -->
                        <?php foreach($kits as $k): ?>
                        <div class="col-md-6" id="kit-<?= $k['code_kit'] ?>">
                            <div class="card kit-card shadow-sm p-3 <?= $k['statut'] == 'UTILISE' ? 'used' : '' ?>">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h6 class="fw-bold mb-0"><?= $k['nom_kit'] ?></h6>
                                    <span class="badge bg-light text-dark border"><?= $k['code_kit'] ?></span>
                                </div>
                                <small class="text-muted">Stérile jusqu'au : <?= date('d/m/Y', strtotime($k['date_peremption'])) ?></small>
                                <button class="btn btn-sm btn-outline-success mt-2" onclick="useKit('<?= $k['code_kit'] ?>')">
                                    <?= $k['statut'] == 'UTILISE' ? 'Utilisé' : 'Marquer comme utilisé' ?>
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- FACTURATION DE L'ACTE -->
        <div class="col-md-5">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-primary text-white py-3">
                    <h5 class="fw-bold mb-0"><i class="bi bi-cash-coin me-2"></i>Facturation Bloc</h5>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <label class="small fw-bold">Choisir l'acte principal</label>
                        <select id="acteSelect" class="form-select form-select-lg" onchange="updateTotal(this)">
                            <option value="0" data-price="0">-- Sélectionner --</option>
                            <?php foreach($catalogue as $acte): ?>
                                <option value="<?= $acte['id'] ?>" data-price="<?= $acte['montant'] ?>">
                                    <?= $acte['nom_acte'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="facture-box text-center">
                        <h6 class="text-muted text-uppercase small">Montant à facturer</h6>
                        <div class="price-total"><span id="totalDisplay">0</span> <small class="fs-4">FCFA</small></div>
                        <hr>
                        <ul class="text-start small">
                            <li>Honoraires chirurgien inclus</li>
                            <li>Frais de salle et matériel stérile</li>
                            <li>Surveillance SSPI</li>
                        </ul>
                    </div>

                    <button class="btn btn-primary btn-lg w-100 mt-4 rounded-pill shadow" onclick="validerFacture()">
                        Générer la facture et Clôturer l'intervention
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function useKit(code) {
    // Simulation AJAX pour marquer le matériel comme utilisé
    const card = document.getElementById('kit-' + code);
    if(card) {
        card.querySelector('.kit-card').classList.add('used');
        card.querySelector('button').innerText = "Utilisé";
        card.querySelector('button').disabled = true;
    }
}

function updateTotal(select) {
    const price = select.options[select.selectedIndex].getAttribute('data-price');
    document.getElementById('totalDisplay').innerText = new Intl.NumberFormat().format(price);
}

function validerFacture() {
    const idActe = document.getElementById('acteSelect').value;
    if(idActe == "0") { alert("Veuillez sélectionner un acte !"); return; }

    if(confirm("Confirmer la facturation et la clôture du dossier ?")) {
        alert("Facture générée. Le dossier patient est mis à jour.");
        window.location.href = "<?= BASE_URL ?>bloc/dashboard";
    }
}
</script>