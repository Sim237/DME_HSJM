<!-- ================= MODALE : ENTRÉE DE STOCK (SHARED) ================= -->
<div class="modal fade" id="modalEntreeStock" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-primary text-white border-0">
                <h5 class="modal-title fw-bold"><i class="bi bi-cart-plus me-2"></i>Approvisionnement</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= BASE_URL ?>pharmacie/approvisionnement" method="POST">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">MÉDICAMENT À RÉAPPROVISIONNER</label>
                        <select name="medicament_id" class="form-select shadow-sm" required>
                            <option value="">-- Choisir le produit --</option>
                            <?php
                            // Récupération de la liste des médicaments pour le menu déroulant
                            $db = (new Database())->getConnection();
                            $all_meds = $db->query("SELECT id, nom, dosage FROM medicaments ORDER BY nom ASC")->fetchAll(PDO::FETCH_ASSOC);
                            foreach($all_meds as $med): ?>
                                <option value="<?= $med['id'] ?>"><?= htmlspecialchars($med['nom']) ?> (<?= htmlspecialchars($med['dosage']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">QUANTITÉ AJOUTÉE (UNITÉS)</label>
                        <input type="number" name="quantite_ajoutee" class="form-control shadow-sm" min="1" required placeholder="Ex: 50">
                    </div>
                </div>
                <div class="modal-footer border-0 p-4">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-5 shadow">Valider l'entrée</button>
                </div>
            </form>
        </div>
    </div>
</div>