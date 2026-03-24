<?php
require_once __DIR__ . '/../layouts/header.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/dme_hospital/config/database.php';

// Récupérer ordonnance
$ordonnance_id = $_GET['id'] ?? $id ?? 0;
$database = new Database();
$db = $database->getConnection();

$stmt = $db->prepare("
    SELECT o.*, c.patient_id, p.nom, p.prenom, p.dossier_numero,
           u.nom as medecin_nom, u.prenom as medecin_prenom
    FROM ordonnances_pharmacie o
    JOIN consultations c ON o.consultation_id = c.id
    JOIN patients p ON c.patient_id = p.id
    JOIN users u ON c.medecin_id = u.id
    WHERE o.id = ?
");
$stmt->execute([$ordonnance_id]);
$ordonnance = $stmt->fetch(PDO::FETCH_ASSOC);

// Récupérer médicaments
$stmt = $db->prepare("
    SELECT om.*, m.nom, m.quantite as stock_actuel, m.unite
    FROM ordonnance_medicaments om
    JOIN medicaments m ON om.medicament_id = m.id
    WHERE om.ordonnance_id = ?
");
$stmt->execute([$ordonnance_id]);
$medicaments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>
        
        <main class="col-md-10 ms-sm-auto px-md-4">
            <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><i class="bi bi-basket"></i> Délivrance Ordonnance</h1>
                <a href="<?= BASE_URL ?>pharmacie/ordonnances" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Retour
                </a>
            </div>

            <!-- Info Patient -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Patient: <?= htmlspecialchars($ordonnance['nom'] . ' ' . $ordonnance['prenom']) ?></h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Dossier:</strong> <?= htmlspecialchars($ordonnance['dossier_numero']) ?><br>
                            <strong>Médecin:</strong> Dr. <?= htmlspecialchars($ordonnance['medecin_nom'] . ' ' . $ordonnance['medecin_prenom']) ?>
                        </div>
                        <div class="col-md-6">
                            <strong>Date ordonnance:</strong> <?= date('d/m/Y H:i', strtotime($ordonnance['date_creation'])) ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Médicaments -->
            <form action="<?= BASE_URL ?>pharmacie/delivrer" method="POST">
                <input type="hidden" name="ordonnance_id" value="<?= $ordonnance_id ?>">
                
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="bi bi-capsule"></i> Médicaments à délivrer</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Médicament</th>
                                        <th>Posologie</th>
                                        <th>Durée</th>
                                        <th>Quantité prescrite</th>
                                        <th>Stock disponible</th>
                                        <th>Quantité à délivrer</th>
                                        <th>Statut</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($medicaments as $med): ?>
                                    <tr class="<?= $med['disponible'] ? '' : 'table-warning' ?>">
                                        <td class="fw-bold"><?= htmlspecialchars($med['nom']) ?></td>
                                        <td><?= htmlspecialchars($med['posologie']) ?></td>
                                        <td><?= htmlspecialchars($med['duree']) ?></td>
                                        <td><?= $med['quantite'] ?> <?= htmlspecialchars($med['unite']) ?></td>
                                        <td>
                                            <span class="badge <?= $med['stock_actuel'] > 0 ? 'bg-success' : 'bg-danger' ?>">
                                                <?= $med['stock_actuel'] ?> <?= htmlspecialchars($med['unite']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($med['stock_actuel'] > 0): ?>
                                                <input type="number" 
                                                       name="delivrer[<?= $med['id'] ?>]" 
                                                       class="form-control form-control-sm" 
                                                       value="<?= min($med['quantite'], $med['stock_actuel']) ?>"
                                                       max="<?= min($med['quantite'], $med['stock_actuel']) ?>"
                                                       min="0"
                                                       style="width: 100px;">
                                            <?php else: ?>
                                                <span class="text-danger">Indisponible</span>
                                                <input type="hidden" name="delivrer[<?= $med['id'] ?>]" value="0">
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!$med['disponible']): ?>
                                                <span class="badge bg-warning text-dark">
                                                    <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($med['message_stock']) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-success">Disponible</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="row">
                            <div class="col-md-8">
                                <label class="form-label">Notes pharmacien:</label>
                                <textarea name="notes" class="form-control" rows="2" placeholder="Commentaires, substitutions, conseils..."></textarea>
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button type="submit" class="btn btn-success btn-lg w-100">
                                    <i class="bi bi-check-circle"></i> Valider la délivrance
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </main>
    </div>
</div>

<script>
// Notification temps réel (simulation)
document.addEventListener('DOMContentLoaded', function() {
    // Afficher notification que l'ordonnance est en cours de traitement
    if (Notification.permission === 'granted') {
        new Notification('Nouvelle ordonnance en cours', {
            body: 'Patient: <?= htmlspecialchars($ordonnance['nom'] . ' ' . $ordonnance['prenom']) ?>',
            icon: '<?= BASE_URL ?>public/images/pharmacy-icon.png'
        });
    }
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>