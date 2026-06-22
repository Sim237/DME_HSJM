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

/*
 * Partiel réutilisable : sélecteur de médecin
 * ─────────────────────────────────────────────
 * Variables à définir AVANT d'inclure ce fichier :
 *   $__ms_field  (string)  nom de l'input, ex: 'soussigne'           [requis]
 *   $__ms_label  (string)  label affiché, ex: 'Médecin signataire'   [requis]
 *   $__ms_value  (string)  valeur actuelle (formData ou medecin_nom)  [requis]
 *   $__ms_id_field (string) nom du champ caché pour l'id médecin      [optionnel]
 *   $__ms_placeholder (string) placeholder si champ texte libre       [optionnel]
 *
 * Variables globales attendues (injectées par FormulaireController) :
 *   $current_user_is_medecin (bool)
 *   $medecins_service        (array)
 *   $current_user_id         (int)
 */
$__ms_field       = $__ms_field       ?? 'medecin_nom';
$__ms_label       = $__ms_label       ?? 'Médecin traitant';
$__ms_value       = $__ms_value       ?? ($medecin_nom ?? '');
$__ms_id_field    = $__ms_id_field    ?? '';
$__ms_placeholder = $__ms_placeholder ?? 'Nom du médecin';
$__ms_is_med      = $current_user_is_medecin ?? false;
$__ms_list        = $medecins_service ?? [];
$__ms_uid         = $current_user_id  ?? 0;
?>

<?php if ($__ms_is_med): ?>
    <?php /* Médecin connecté → lecture seule, pré-rempli */ ?>
    <?php if ($__ms_id_field): ?>
    <input type="hidden" name="<?= htmlspecialchars($__ms_id_field) ?>"
           value="<?= (int)$__ms_uid ?>">
    <?php endif; ?>
    <div class="medecin-readonly-wrap">
        <input type="text"
               class="form-control medecin-readonly-input"
               name="<?= htmlspecialchars($__ms_field) ?>"
               value="<?= htmlspecialchars($__ms_value) ?>"
               readonly>
        <span class="medecin-readonly-badge">
            <i class="bi bi-shield-check-fill"></i> Vous
        </span>
    </div>

<?php elseif (!empty($__ms_list)): ?>
    <?php /* Non-médecin + liste disponible → select */ ?>
    <select class="form-select medecin-select-input"
            name="<?= htmlspecialchars($__ms_field) ?>"
            onchange="<?= $__ms_id_field ? 'document.getElementById(\''.htmlspecialchars($__ms_id_field.'_hidden').'\').value=this.options[this.selectedIndex].dataset.id||\'\'':'void(0)' ?>">
        <option value="">— Sélectionner un médecin —</option>
        <?php foreach ($__ms_list as $__m): ?>
        <option value="<?= htmlspecialchars($__m['nom_complet']) ?>"
                data-id="<?= (int)($__m['id'] ?? 0) ?>"
                <?= ($__ms_value === $__m['nom_complet']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($__m['nom_complet']) ?>
            <?php if (!empty($__m['specialite'])): ?>
                — <?= htmlspecialchars($__m['specialite']) ?>
            <?php endif; ?>
        </option>
        <?php endforeach; ?>
    </select>
    <?php if ($__ms_id_field): ?>
    <input type="hidden"
           id="<?= htmlspecialchars($__ms_id_field.'_hidden') ?>"
           name="<?= htmlspecialchars($__ms_id_field) ?>"
           value="<?= htmlspecialchars($formData[$__ms_id_field] ?? '') ?>">
    <?php endif; ?>

<?php else: ?>
    <?php /* Fallback : champ texte libre */ ?>
    <input type="text"
           class="form-control"
           name="<?= htmlspecialchars($__ms_field) ?>"
           value="<?= htmlspecialchars($__ms_value) ?>"
           placeholder="<?= htmlspecialchars($__ms_placeholder) ?>">
<?php endif; ?>

<style>
.medecin-readonly-wrap {
    position: relative; display: flex; align-items: center;
}
.medecin-readonly-input {
    background: #f0fdf4 !important;
    border-color: #86efac !important;
    color: #166534 !important;
    padding-right: 90px !important;
    font-weight: 600 !important;
}
.medecin-readonly-badge {
    position: absolute; right: 10px;
    background: #dcfce7; color: #16a34a;
    border-radius: 20px; padding: 2px 10px;
    font-size: .72rem; font-weight: 700;
    white-space: nowrap; pointer-events: none;
}
.medecin-select-input {
    border: 1.5px solid #e2e8f0 !important;
    border-radius: 8px !important;
}
.medecin-select-input:focus {
    border-color: #3b82f6 !important;
    box-shadow: 0 0 0 3px rgba(59,130,246,.15) !important;
}
</style>
