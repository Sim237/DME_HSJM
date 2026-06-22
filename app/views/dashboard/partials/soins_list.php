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
 if (empty($soins_du_jour)): ?>
    <div class="text-center py-3 text-muted small">Aucun soin planifié pour le moment.</div>
<?php else: foreach ($soins_du_jour as $s): ?>
    <div class="task-item shadow-sm <?= $s['execute'] ? 'opacity-50' : '' ?>"
         style="border-left: 4px solid <?= $s['execute'] ? '#6c757d' : '#0d6efd' ?>;">
        <div>
            <span class="badge <?= $s['execute'] ? 'bg-secondary' : 'bg-primary' ?> me-2">
                <?= substr($s['heure'], 0, 5) ?>
            </span>
            <small class="fw-bold text-dark">
                <?= htmlspecialchars($s['soin_description']) ?>
                <span class="text-muted">| Lit <?= $s['nom_lit'] ?? '--' ?></span>
            </small>
            <div class="small text-muted" style="font-size: 0.7rem; margin-left: 55px;">
                Patient: <?= strtoupper($s['nom']) ?>
            </div>
        </div>
        <div class="form-check">
            <input type="checkbox" class="form-check-input"
                   onchange="validerSoinAction(<?= $s['id'] ?>, this)"
                   <?= $s['execute'] ? 'checked disabled' : '' ?>>
        </div>
    </div>
<?php endforeach; endif; ?>