<style>
    .aldrete-btn { width: 45px; height: 45px; border-radius: 10px; border: 2px solid #ddd; background: #fff; font-weight: bold; }
    .aldrete-btn.active { background: #0d6efd; color: #fff; border-color: #0d6efd; }
    .score-display { font-size: 3rem; font-weight: 900; color: #0d6efd; }
</style>

<div class="container-fluid p-4">
    <h3 class="fw-bold mb-4">Surveillance Salle de Réveil (SSPI)</h3>
    <div class="row">
        <!-- Calculateur de Score -->
        <div class="col-md-7">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body">
                    <h5 class="fw-bold border-bottom pb-2">Calcul du Score d'Aldrete</h5>

                    <?php
                    $criteres = [
                        'motricite' => ['Label' => 'Motricité', 'options' => ['Immobile', 'Bouge 2 membres', 'Bouge 4 membres']],
                        'respiration' => ['Label' => 'Respiration', 'options' => ['Apnée', 'Dyspnée/Limitée', 'Respire profondément']],
                        'circulation' => ['Label' => 'Circulation (TA)', 'options' => ['+/- 50% base', '+/- 20-50%', '+/- 20%']],
                        'conscience' => ['Label' => 'Conscience', 'options' => ['Nulle', 'Réveille à l\'appel', 'Réveil complet']],
                        'saturation' => ['Label' => 'Saturation O2', 'options' => ['< 90% avec O2', '> 90% avec O2', '> 92% air ambiant']]
                    ];
                    foreach($criteres as $key => $data): ?>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div><strong class="small"><?= $data['Label'] ?></strong></div>
                            <div class="btn-group" role="group">
                                <?php foreach($data['options'] as $val => $text): ?>
                                    <button type="button" class="btn btn-outline-secondary btn-sm px-3"
                                            onclick="setScore('<?= $key ?>', <?= $val ?>, this)" title="<?= $text ?>"><?= $val ?></button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- État de sortie -->
        <div class="col-md-5">
            <div class="card border-0 shadow-sm rounded-4 text-center p-4">
                <h6 class="text-muted">SCORE TOTAL</h6>
                <div class="score-display" id="totalScore">0</div>
                <div id="statusMessage" class="alert alert-warning mt-3 small">Sortie non autorisée (Requis : 9/10)</div>
                <button id="btnDischarge" class="btn btn-success btn-lg w-100 mt-4 rounded-pill d-none">
                    <i class="bi bi-box-arrow-right"></i> Autoriser retour en service
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let scores = { motricite:0, respiration:0, circulation:0, conscience:0, saturation:0 };

function setScore(key, value, btn) {
    // UI Update
    Array.from(btn.parentElement.children).forEach(b => b.classList.remove('active', 'btn-primary'));
    btn.classList.add('active', 'btn-primary');

    // Logic Update
    scores[key] = value;
    let total = Object.values(scores).reduce((a, b) => a + b, 0);
    document.getElementById('totalScore').innerText = total;

    // Validation
    const btnExit = document.getElementById('btnDischarge');
    const msg = document.getElementById('statusMessage');
    if (total >= 9) {
        btnExit.classList.remove('d-none');
        msg.className = "alert alert-success mt-3 small";
        msg.innerText = "Prêt pour le transfert en service.";
    } else {
        btnExit.classList.add('d-none');
        msg.className = "alert alert-warning mt-3 small";
        msg.innerText = "Surveillance continue requise.";
    }
}
</script>