<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<style>
    .blood-card { border: none; border-radius: 15px; transition: all 0.3s; background: #fff; border-bottom: 3px solid transparent; }
    .blood-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
    .bg-gradient-red { background: linear-gradient(135deg, #d32f2f, #ef5350); color: white; }
    .stock-bar { height: 8px; border-radius: 5px; background: #eee; margin-top: 10px; overflow: hidden; }
    .stock-progress { height: 100%; transition: width 1s ease-in-out; }
    .stat-icon { width: 45px; height: 45px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }
    .card-critique { border-bottom: 3px solid #dc3545 !important; background: #fff5f5; }
</style>

<div class="container-fluid bg-light pb-5" style="min-height: 100vh;">
    <div class="row">
        <?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>

        <main class="col-md-10 ms-sm-auto px-md-4">
            <div class="d-flex justify-content-between align-items-center py-4">
                <div>
                    <h1 class="h3 fw-bold mb-0">Banque de Sang</h1>
                    <p class="text-muted small">Hôpital Saint-Jean de Malte - Gestion des ressources</p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-danger shadow-sm rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#modalNouveauDon"><i class="bi bi-plus-circle me-2"></i> Nouveau Don</button>
                    <button class="btn btn-dark shadow-sm rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#modalSortieStock"><i class="bi bi-arrow-left-right me-2"></i> Sortie Stock</button>
                </div>
            </div>

            <!-- STATS DU HAUT -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card blood-card p-3 shadow-sm text-center">
                        <small class="text-muted text-uppercase fw-bold" style="font-size: 0.65rem;">Stock Total</small>
                        <h3 class="fw-bold mb-0"><?= array_sum(array_column($stock, 'quantite_poches')) ?> <small class="fs-6 fw-normal">poches</small></h3>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card blood-card p-3 shadow-sm text-center">
                        <small class="text-muted text-uppercase fw-bold" style="font-size: 0.65rem;">Dons (Mois)</small>
                        <h3 class="fw-bold mb-0 text-primary">+<?= count($donneurs) ?></h3>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card blood-card p-3 shadow-sm text-center">
                        <small class="text-muted text-uppercase fw-bold" style="font-size: 0.65rem;">Conso. Mensuelle</small>
                        <h3 class="fw-bold mb-0 text-warning"><?= $conso_totale ?></h3>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card blood-card p-3 shadow-sm bg-gradient-red border-0">
                        <small class="text-uppercase fw-bold opacity-75" style="font-size: 0.65rem;">Critique</small>
                        <h5 class="fw-bold mb-0 small"><?php $c = []; foreach($stock as $s) if($s['quantite_poches'] < 2) $c[] = $s['groupe_sanguin'].$s['rhesus']; echo implode('/', $c) ?: 'RAS'; ?></h5>
                    </div>
                </div>
            </div>

            <!-- SECTION : DEMANDES DE TRANSFUSION (Important, affiché en premier) -->
            <div class="card border-0 shadow-sm rounded-4 mb-4 border-start border-danger border-5">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0 text-danger"><i class="bi bi-bell-fill me-2"></i>Demandes de Transfusion en attente</h6>
                    <span class="badge bg-danger"><?= count($demandes_transfusion) ?></span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light small">
                            <tr>
                                <th>PATIENT</th>
                                <th class="text-center">GROUPE</th>
                                <th class="text-center">QUANTITÉ</th>
                                <th>INFOS DONNEUR FAMILLE</th>
                                <th class="text-end pe-4">ACTION</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($demandes_transfusion as $req): ?>
                            <tr>
                                <td class="ps-3"><strong><?= $req['nom'] ?> <?= $req['prenom'] ?></strong><br><small class="text-muted"><?= $req['dossier_numero'] ?></small></td>
                                <td class="text-center"><span class="badge bg-danger fs-6"><?= $req['groupe_requis'] ?><?= $req['rhesus_requis'] ?></span></td>
                                <td class="text-center fw-bold"><?= $req['quantite_demandee'] ?> poche(s)</td>
                                <td><small class="text-muted"><?= $req['notes_famille'] ?: 'Non renseigné' ?></small></td>
                                <td class="text-end pe-4"><button class="btn btn-success btn-sm rounded-start-pill px-3" onclick="deliverRequest(<?= $req['id'] ?>)">
    <i class="bi bi-check-lg"></i> Délivrer
</button>
                                <button class="btn btn-warning btn-sm rounded-end-pill px-3" onclick="markUnavailable(<?= $req['id'] ?>)">Indisponible</button>
                              </td>
                            </tr>
                            <?php endforeach; if(empty($demandes_transfusion)) echo "<tr><td colspan='5' class='text-center py-4 text-muted small italic'>Aucune demande en cours</td></tr>"; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- DISPONIBILITÉ PAR GROUPE -->
            <div class="row g-3 mb-4">
                <?php foreach($stock as $s):
                    $percent = min(100, ($s['quantite_poches'] / 10) * 100);
                    $color = ($s['quantite_poches'] < 3) ? '#dc3545' : '#198754';
                ?>
                <div class="col-md-3">
                    <div class="card blood-card shadow-sm <?= ($s['quantite_poches'] < 3) ? 'card-critique' : '' ?>">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <h2 class="fw-bold mb-0"><?= $s['groupe_sanguin'].$s['rhesus'] ?></h2>
                                <span class="badge" style="background: <?= $color ?>20; color: <?= $color ?>;"><?= $s['quantite_poches'] ?> poches</span>
                            </div>
                            <div class="stock-bar"><div class="stock-progress" style="width: <?= $percent ?>%; background: <?= $color ?>;"></div></div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- REGISTRE ET GRAPHIQUE -->
            <div class="row">
                <div class="col-md-8">
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-header bg-white py-3 fw-bold">Derniers Donneurs Enregistrés</div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead><tr class="bg-light small"><th>CODE</th><th>IDENTITÉ</th><th>GROUPE</th><th>VILLE</th><th>STATUT</th></tr></thead>
                                <tbody>
                                    <?php foreach($donneurs as $d): ?>
                                    <tr>
                                        <td class="fw-bold text-primary small"><?= $d['code_donneur'] ?></td>
                                        <td><?= $d['is_anonyme'] ? 'Anonyme' : $d['nom'].' '.$d['prenom'] ?></td>
                                        <td><span class="badge bg-danger"><?= $d['groupe_sanguin'].$d['rhesus'] ?></span></td>
                                        <td class="small"><?= $d['ville'] ?></td>
                                        <td><span class="badge bg-success-subtle text-success">APTE</span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-header bg-white py-3 fw-bold">Provenance des Dons</div>
                        <div class="card-body"><canvas id="bloodDonorChart"></canvas></div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- MODALES -->
<div class="modal fade" id="modalNouveauDon" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-danger text-white border-0"><h5 class="modal-title fw-bold">Nouveau Don</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <form action="<?= BASE_URL ?>banque-sang/enregistrer-don" method="POST">
                <div class="modal-body p-4">
                    <div class="form-check form-switch p-3 bg-light rounded-3 mb-3">
                        <input class="form-check-input" type="checkbox" name="is_anonyme" id="is_anonyme" onchange="toggleDonorFields(this)">
                        <label class="form-check-label fw-bold">Donneur Anonyme</label>
                    </div>
                    <div id="donor_fields" class="row g-3">
                        <div class="col-md-6"><label class="small fw-bold">Nom</label><input type="text" name="nom" class="form-control"></div>
                        <div class="col-md-6"><label class="small fw-bold">Prénom</label><input type="text" name="prenom" class="form-control"></div>
                    </div>
                    <div class="row g-3 mt-1">
                        <div class="col-md-3"><label class="small fw-bold">Groupe</label><select name="groupe" class="form-select"><option>A</option><option>B</option><option>AB</option><option>O</option></select></div>
                        <div class="col-md-3"><label class="small fw-bold">Rhésus</label><select name="rhesus" class="form-select"><option>+</option><option>-</option></select></div>
                        <div class="col-md-3"><label class="small fw-bold">Qté</label><input type="number" name="quantite" class="form-control" value="1"></div>
                        <div class="col-md-3"><label class="small fw-bold">Source</label><select name="source" class="form-select"><option value="DONNEUR_VOLONTAIRE">Volontaire</option><option value="FAMILLE">Famille</option></select></div>
                    </div>
                    <div class="row g-3 mt-1"><div class="col-md-6"><label class="small fw-bold">Tel</label><input type="text" name="telephone" class="form-control"></div><div class="col-md-6"><label class="small fw-bold">Ville</label><input type="text" name="ville" class="form-control" value="Njombé"></div></div>
                </div>
                <div class="modal-footer border-0"><button type="submit" class="btn btn-danger w-100 py-2">Enregistrer le Don</button></div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalSortieStock" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-dark text-white border-0"><h5 class="modal-title fw-bold">Sortie de Stock</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <form action="<?= BASE_URL ?>banque-sang/sortie-stock" method="POST">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="small fw-bold">Groupe</label><select name="groupe" class="form-select"><option>A</option><option>B</option><option>AB</option><option>O</option></select></div>
                        <div class="col-md-6"><label class="small fw-bold">Rhésus</label><select name="rhesus" class="form-select"><option>+</option><option>-</option></select></div>
                        <div class="col-md-6"><label class="small fw-bold">Qté</label><input type="number" name="quantite" class="form-control" value="1"></div>
                        <div class="col-md-6"><label class="small fw-bold">Motif</label><select name="motif" class="form-select"><option value="PATIENT_TRANSFUSION">Transfusion</option><option value="PERIME">Périmé</option></select></div>
                    </div>
                </div>
                <div class="modal-footer border-0"><button type="submit" class="btn btn-dark w-100 py-2">Enregistrer Sortie</button></div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('bloodDonorChart');
    if (ctx) {
        new Chart(ctx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['Volontaires', 'Famille', 'Personnel'],
                datasets: [{
                    data: [
                        <?= $stats_source['DONNEUR_VOLONTAIRE'] ?>,
                        <?= $stats_source['FAMILLE'] ?>,
                        <?= $stats_source['PERSONNEL'] ?>
                    ],
                    backgroundColor: ['#d32f2f', '#1976d2', '#455a64'],
                    borderWidth: 0
                }]
            },
            options: { cutout: '75%', plugins: { legend: { position: 'bottom' } } }
        });
    }
});

function toggleDonorFields(checkbox) {
    const fields = document.getElementById('donor_fields');
    fields.style.opacity = checkbox.checked ? '0.3' : '1';
    fields.querySelectorAll('input').forEach(i => i.disabled = checkbox.checked);
}

function markUnavailable(requestId) {
    if(!confirm("Signaler cette poche comme indisponible ? Le médecin recevra une alerte immédiate.")) return;

    const formData = new FormData();
    formData.append('id', requestId);

    fetch('<?= BASE_URL ?>banque-sang/indisponible', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            let message = `✅ Alerte transmise au dossier du patient.\n\n`;

            if (data.donors.length > 0) {
                message += `LISTE DES DONNEURS ${data.group} À CONTACTER :\n`;
                data.donors.forEach(d => {
                    message += `- ${d.nom} ${d.prenom} : ${d.telephone}\n`;
                });
            } else {
                message += `⚠️ Aucun donneur du groupe ${data.group} n'est enregistré dans le système actuellement.`;
            }

            alert(message);
            location.reload();
        }
    })
    .catch(err => alert("Erreur lors de la communication avec le serveur."));
}
function deliverRequest(requestId) {
    if(!confirm("Confirmer la délivrance des poches ? Le stock sera mis à jour immédiatement.")) return;

    const formData = new FormData();
    formData.append('id', requestId);

    fetch('<?= BASE_URL ?>banque-sang/delivrer', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            alert("✅ " + data.message);
            location.reload(); // Rafraîchit pour voir le stock baisser
        } else {
            alert("❌ Erreur : " + data.message);
        }
    })
    .catch(err => alert("Erreur réseau."));
}

</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>