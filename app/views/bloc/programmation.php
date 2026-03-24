<!-- app/views/bloc/programmation.php -->
<div class="card shadow">
    <div class="card-header bg-dark text-white">Programmer l'intervention</div>
    <form action="<?= BASE_URL ?>bloc/confirmer-programmation" method="POST">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label>Choisir la Salle</label>
                    <select name="salle_id" class="form-select" required>
                        <?php foreach($salles_disponibles as $s): ?>
                            <option value="<?= $s['id'] ?>"><?= $s['nom_salle'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label>Date & Heure</label>
                    <input type="datetime-local" name="date_heure" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label>Aide Opératoire / Assistants</label>
                    <input type="text" name="assistants" class="form-control" placeholder="Noms des assistants">
                </div>
                <div class="col-md-12">
                    <label>Diagnostic Opératoire Définitif</label>
                    <textarea name="diag_op" class="form-control" rows="3"></textarea>
                </div>
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-success w-100">Valider la programmation du Bloc</button>
        </div>
    </form>
</div>