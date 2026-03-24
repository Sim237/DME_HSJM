<div class="container py-4">
    <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
        <div class="card-header bg-dark text-white p-3">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-file-earmark-medical me-2"></i>COMPTE-RENDU OPÉRATOIRE</h5>
                <span class="badge bg-primary">Patient : <?= $op['nom'] ?></span>
            </div>
        </div>
        <form action="<?= BASE_URL ?>bloc/save-cro" method="POST">
            <input type="hidden" name="prog_id" value="<?= $op['id'] ?>">
            <div class="card-body bg-light">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="fw-bold mb-2">Description de l'intervention</label>
                        <div class="position-relative">
                            <textarea name="cro_text" id="cro_text" class="form-control shadow-sm" rows="12" placeholder="Décrivez l'acte opératoire..."></textarea>
                            <!-- Bouton Dictée Vocale -->
                            <button type="button" class="btn btn-outline-primary btn-sm position-absolute bottom-0 end-0 m-2" onclick="startDictation()">
                                <i class="bi bi-mic-fill"></i> Dictée Vocale
                            </button>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 bg-white rounded shadow-sm">
                            <h6 class="fw-bold border-bottom pb-2">Détails Techniques</h6>
                            <div class="mb-3">
                                <label class="small">Type d'acte (CIM-10)</label>
                                <input type="text" name="type_acte" class="form-control form-control-sm">
                            </div>
                            <div class="mb-3">
                                <label class="small">Pertes sanguines (ml)</label>
                                <input type="number" name="pertes" class="form-control form-control-sm">
                            </div>
                            <div class="mb-3">
                                <label class="small">Drainage</label>
                                <select name="drain" class="form-select form-select-sm">
                                    <option>Aucun</option><option>Redon</option><option>Lame</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="small">Heure de fin</label>
                                <input type="time" name="heure_fin" class="form-control form-control-sm" value="<?= date('H:i') ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer text-end">
                <button type="submit" class="btn btn-dark px-5 rounded-pill">Finaliser le dossier opératoire</button>
            </div>
        </form>
    </div>
</div>

<script>
function startDictation() {
    if ('webkitSpeechRecognition' in window) {
        const recognition = new webkitSpeechRecognition();
        recognition.lang = 'fr-FR';
        recognition.onresult = (e) => {
            document.getElementById('cro_text').value += e.results[0][0].transcript + ' ';
        };
        recognition.start();
    } else { alert("Dictée non supportée sur ce navigateur"); }
}
</script>