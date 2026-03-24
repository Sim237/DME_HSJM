<!-- Exemple d'utilisation de la signature dans un document -->
<style>
.document-signature {
    margin-top: 40px;
    text-align: right;
}

.signature-block {
    display: inline-block;
    text-align: center;
    border: 1px solid #ddd;
    padding: 15px;
    border-radius: 8px;
    background: white;
}

.signature-image {
    width: 400px;
    height: 150px;
    object-fit: contain;
    border-bottom: 1px solid #333;
    margin-bottom: 10px;
}

.cachet-image {
    width: 100px;
    height: 100px;
    object-fit: contain;
    position: absolute;
    top: 10px;
    right: 10px;
}

.signature-info {
    font-size: 12px;
    color: #666;
    margin-top: 5px;
}
</style>

<?php
// Exemple d'utilisation dans une ordonnance
require_once __DIR__ . '/../../services/SignatureService.php';
$signatureService = new SignatureService();
$signature = $signatureService->getSignature($_SESSION['user_id']);

if ($signature):
?>
<div class="document-signature">
    <div class="signature-block" style="position: relative;">
        <?php if ($signature['cachet_image']): ?>
            <img src="<?= $signature['cachet_image'] ?>" class="cachet-image" alt="Cachet">
        <?php endif; ?>
        
        <img src="<?= $signature['signature_image'] ?>" class="signature-image" alt="Signature">
        
        <div class="signature-info">
            <strong>Dr. <?= htmlspecialchars($_SESSION['nom'] . ' ' . $_SESSION['prenom']) ?></strong><br>
            <?php if ($signature['specialite']): ?>
                <?= htmlspecialchars($signature['specialite']) ?><br>
            <?php endif; ?>
            <?php if ($signature['numero_ordre']): ?>
                N° Ordre: <?= htmlspecialchars($signature['numero_ordre']) ?>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>
