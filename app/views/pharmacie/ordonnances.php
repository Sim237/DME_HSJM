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

<div class="app-wrapper">
    <?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>

    <main class="main-content w-100 p-4">
        <h2 class="fw-bold mb-4">Ordonnances à traiter</h2>

        <div class="card border-0 shadow-sm rounded-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light py-3">
                        <tr class="small text-uppercase">
                            <th class="ps-4">Patient</th>
                            <th>Type Client</th>
                            <th>Service</th>
                            <th>Prescripteur</th>
                            <th>Date/Heure</th>
                            <th class="text-center">Signature</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Configuration des badges par type de client
                        $clientStyles = [
                            'PAYANT_COMPTANT'     => ['label' => 'Payant',       'bg' => '#dbeafe', 'fg' => '#1e40af', 'icon' => 'cash-coin'],
                            'ASSURANCE'           => ['label' => 'Assurance',    'bg' => '#e0e7ff', 'fg' => '#3730a3', 'icon' => 'shield-check'],
                            'BON_PRISE_EN_CHARGE' => ['label' => 'BPC',          'bg' => '#fef3c7', 'fg' => '#92400e', 'icon' => 'file-earmark-medical'],
                            'AGENTS_PHP'          => ['label' => 'Agent PHP',    'bg' => '#d1fae5', 'fg' => '#065f46', 'icon' => 'person-badge'],
                            'FAMILLE_PHP'         => ['label' => 'Famille PHP',  'bg' => '#dcfce7', 'fg' => '#166534', 'icon' => 'people-fill'],
                            'GRATUIT'             => ['label' => 'Gratuit',      'bg' => '#f3e8ff', 'fg' => '#6b21a8', 'icon' => 'gift'],
                        ];
                        ?>
                        <?php foreach($pending_orders as $ord):
                            $tc = $ord['type_client'] ?? '';
                            $style = $clientStyles[$tc] ?? ['label' => $tc ?: 'Non défini', 'bg' => '#f1f5f9', 'fg' => '#64748b', 'icon' => 'question-circle'];
                        ?>
                        <tr>
                            <td class="ps-4">
                                <div class="fw-bold"><?= htmlspecialchars($ord['patient_nom'] . ' ' . $ord['patient_prenom']) ?></div>
                                <small class="text-muted"><?= htmlspecialchars($ord['dossier_numero'] ?? $ord['qr_code_token'] ?? 'N/A') ?></small>
                            </td>
                            <td>
                                <span class="badge fw-bold rounded-pill px-2 py-1"
                                      style="background:<?= $style['bg'] ?>;color:<?= $style['fg'] ?>;font-size:.72rem;">
                                    <i class="bi bi-<?= $style['icon'] ?> me-1"></i><?= htmlspecialchars($style['label']) ?>
                                </span>
                                <?php if (!empty($ord['circuit']) && $ord['circuit'] !== 'STANDARD'): ?>
                                <div class="text-muted mt-1" style="font-size:.66rem;">
                                    <i class="bi bi-diagram-2 me-1"></i><?= htmlspecialchars($ord['circuit']) ?>
                                </div>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge bg-info-subtle text-info"><?= htmlspecialchars($ord['nom_service'] ?? 'Médecine') ?></span></td>
                            <td>Dr. <?= htmlspecialchars($ord['medecin_nom']) ?></td>
                            <td class="small"><?= date('d/m/Y H:i', strtotime($ord['date_creation'])) ?></td>
                            <td class="text-center">
                                <?php if (!empty($ord['signature_hash'])): ?>
                                <span class="badge bg-success-subtle text-success border border-success-subtle fw-bold" style="font-size:.7rem;">
                                    <i class="bi bi-patch-check-fill me-1"></i>Signée
                                </span>
                                <?php else: ?>
                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle fw-bold" style="font-size:.7rem;">
                                    <i class="bi bi-clock me-1"></i>En attente
                                </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-4">
                                <div class="d-flex gap-2 justify-content-end">
                                    <a href="<?= BASE_URL ?>prescription/print?id=<?= $ord['id'] ?>" target="_blank"
                                       class="btn btn-outline-secondary btn-sm rounded-pill px-3"
                                       title="Visualiser l'ordonnance signée">
                                        <i class="bi bi-eye me-1"></i>Voir
                                    </a>
                                    <a href="<?= BASE_URL ?>pharmacie/traitement/<?= $ord['id'] ?>" class="btn btn-primary btn-sm rounded-pill px-3 fw-bold">
                                        <i class="bi bi-basket me-1"></i>Traiter
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>