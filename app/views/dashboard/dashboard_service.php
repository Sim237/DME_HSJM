<?php
/**
 * SimCare+ — Dossier Médical Électronique (DME)
 * Copyright (c) 2024-2026 Franck Simeni. Tous droits réservés.
 * Développé pour la gestion hospitalière, et le bien être numérique des patients.
 *
 * Toute reproduction, modification ou distribution de ce logiciel,
 * en tout ou en partie, sans autorisation écrite préalable de l'auteur
 * est strictement interdite et constitue une contrefaçon.
 *
 * Protected under OAPI Agreement — Annexe VII · Berne Convention
 */
 require_once __DIR__ . '/../layouts/header.php'; ?>
<div class="container-fluid bg-light" style="min-height: 100vh;">
    <div class="row">
        <?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>
        <main class="col-md-10 ms-sm-auto px-md-4">
            <div class="py-5 text-center">
                <i class="bi bi-hospital display-1 text-primary opacity-25"></i>
                <h2 class="mt-3">Bienvenue au Service <?= $stats['nom_service'] ?></h2>
                <p class="text-muted">Utilisez le menu à gauche pour gérer vos patients et consultations.</p>
            </div>
        </main>
    </div>
</div>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>