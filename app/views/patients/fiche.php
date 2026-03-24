<?php
require_once __DIR__ . '/../../layouts/header.php';
$patient = $patient ?? null;
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__ . '/../../layouts/sidebar.php'; ?>
        
        <main class="col-md-10 ms-sm-auto px-md-4">
            <?php if ($patient): ?>
            
            <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom no-print">
                <h1 class="h2"><i class="bi bi-file-person"></i> Fiche Patient</h1>
                <div>
                    <button onclick="window.print()" class="btn btn-secondary me-2">
                        <i class="bi bi-printer"></i> Imprimer
                    </button>
                    <a href="index.php?page=patient" class="btn btn-primary">
                        <i class="bi bi-arrow-left"></i> Retour
                    </a>
                </div>
            </div>
            
            <!-- Fiche imprimable -->
            <div class="card">
                <div class="card-header bg-primary text-white text-center">
                    <h3 class="mb-0">FICHE PATIENT</h3>
                    <p class="mb-0">DME HOSPITAL</p>
                </div>
                <div class="card-body">
                    
                    <!-- Photo et QR Code (optionnel) -->
                    <div class="row mb-4">
                        <div class="col-md-8">
                            <h4 class="text-primary">Informations Personnelles</h4>
                        </div>
                        <div class="col-md-4 text-end">
                            <div class="border p-3 d-inline-block">
                                <strong>Dossier N°</strong><br>
                                <h3 class="mb-0 text-primary"><?= htmlspecialchars($patient['dossier_numero']) ?></h3>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Identité -->
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <strong>Nom:</strong><br>
                            <span class="fs-5"><?= htmlspecialchars($patient['nom']) ?></span>
                        </div>
                        <div class="col-md-4">
                            <strong>Prénom:</strong><br>
                            <span class="fs-5"><?= htmlspecialchars($patient['prenom']) ?></span>
                        </div>
                        <div class="col-md-4">
                            <strong>Sexe:</strong><br>
                            <span class="fs-5"><?= $patient['sexe'] === 'M' ? 'Masculin' : 'Féminin' ?></span>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <strong>Date de naissance:</strong><br>
                            <?= date('d/m/Y', strtotime($patient['date_naissance'])) ?>
                        </div>
                        <div class="col-md-4">
                            <strong>Âge:</strong><br>
                            <?= date_diff(date_create($patient['date_naissance']), date_create('now'))->y ?> ans
                        </div>
                        <div class="col-md-4">
                            <strong>Groupe sanguin:</strong><br>
                            <span class="badge bg-danger fs-6"><?= htmlspecialchars($patient['groupe_sanguin'] ?? 'Non renseigné') ?></span>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <!-- Contact -->
                    <h5 class="text-primary mb-3">Coordonnées</h5>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <strong>Téléphone:</strong><br>
                            <?= htmlspecialchars($patient['telephone'] ?? '-') ?>
                        </div>
                        <div class="col-md-8">
                            <strong>Email:</strong><br>
                            <?= htmlspecialchars($patient['email'] ?? '-') ?>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <strong>Adresse:</strong><br>
                        <?= nl2br(htmlspecialchars($patient['adresse'] ?? '-')) ?>
                    </div>
                    
                    <hr>
                    
                    <!-- Personne à contacter -->
                    <h5 class="text-primary mb-3">Personne à Contacter en Cas d'Urgence</h5>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Nom:</strong><br>
                            <?= htmlspecialchars($patient['contact_nom'] ?? '-') ?>
                        </div>
                        <div class="col-md-6">
                            <strong>Téléphone:</strong><br>
                            <?= htmlspecialchars($patient['contact_telephone'] ?? '-') ?>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <!-- Antécédents -->
                    <h5 class="text-primary mb-3">Antécédents Médicaux</h5>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <strong class="text-danger">Allergies Connues:</strong>
                            <div class="border p-2 bg-light mt-2">
                                <?= nl2br(htmlspecialchars($patient['allergies'] ?? 'Aucune allergie connue')) ?>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>Antécédents Médicaux:</strong>
                            <div class="border p-2 bg-light mt-2">
                                <?= nl2br(htmlspecialchars($patient['antecedents_medicaux'] ?? 'Aucun')) ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <strong>Antécédents Chirurgicaux:</strong>
                            <div class="border p-2 bg-light mt-2">
                                <?= nl2br(htmlspecialchars($patient['antecedents_chirurgicaux'] ?? 'Aucun')) ?>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>Antécédents Familiaux:</strong>
                            <div class="border p-2 bg-light mt-2">
                                <?= nl2br(htmlspecialchars($patient['antecedents_familiaux'] ?? 'Aucun')) ?>
                            </div>
                        </div>
                    </div>
                    
                    <hr class="no-print">
                    
                    <!-- Date d'impression -->
                    <div class="text-end text-muted">
                        <small>Fiche générée le <?= date('d/m/Y à H:i') ?></small>
                    </div>
                </div>
            </div>
            
            <?php else: ?>
            <div class="alert alert-warning mt-5">
                <i class="bi bi-exclamation-triangle"></i> Patient non trouvé.
            </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<style>
@media print {
    .no-print, .sidebar, nav, .border-bottom {
        display: none !important;
    }
    .card {
        border: none !important;
        box-shadow: none !important;
    }
    body {
        background: white !important;
    }
}
</style>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>