# Système de Signature Électronique

## Fonctionnalités

### 1. Gestion des Signatures
- **Dessiner une signature** : Utiliser le canvas pour dessiner à la main
- **Scanner/Upload** : Télécharger une signature scannée (redimensionnée automatiquement à 400x150px)
- **Cachet professionnel** : Upload du cachet (redimensionné à 200x200px)
- **Informations professionnelles** : N° d'ordre et spécialité

### 2. Intégration dans les Formulaires
- Lors de la création d'un utilisateur avec le rôle "MEDECIN", un champ signature apparaît
- La signature est automatiquement redimensionnée et sauvegardée
- Modification possible via le profil utilisateur

### 3. Utilisation dans les Documents

#### Dans une ordonnance ou certificat :
```php
<?php
require_once __DIR__ . '/../../services/SignatureService.php';
$signatureService = new SignatureService();
$signature = $signatureService->getSignature($medecin_id);

if ($signature):
?>
<div class="document-signature">
    <img src="<?= $signature['signature_image'] ?>" style="width: 400px; height: 150px;">
    <p>Dr. <?= $medecin_nom ?></p>
    <p><?= $signature['specialite'] ?></p>
    <p>N° Ordre: <?= $signature['numero_ordre'] ?></p>
</div>
<?php endif; ?>
```

## Routes Disponibles
- `/profil/signature` : Page de gestion de signature
- `/profil/save-signature` : API pour sauvegarder la signature

## Base de Données
- Table `medecin_signatures` : Stocke les signatures et cachets
- Table `documents_signes` : Traçabilité des documents signés

## Redimensionnement Automatique
- Signatures : 400x150px (optimisé pour documents A4)
- Cachets : 200x200px
- Format : PNG avec fond blanc
- Compression : Optimisée pour réduire la taille

## Sécurité
- Hash SHA256 pour chaque document signé
- Traçabilité IP et timestamp
- Signatures stockées en base64 dans la base de données
