<?php include __DIR__ . '/../layouts/header.php'; ?>
<?php include __DIR__ . '/../layouts/sidebar.php'; ?>

<div class="main-content">
    <!-- Dashboard Header -->
    <div class="dashboard-header">
        <div class="dashboard-title-section">
            <h2>Tableau de Bord</h2>
            <p>Vue d'ensemble de l'activité hospitalière</p>
        </div>
        <div class="dashboard-user-section">
            <div class="user-status-badge">
                En ligne
            </div>
            <div class="notification-bell">
                <i class="fas fa-bell"></i>
                <span class="notification-count">3</span>
            </div>
            <div class="user-info-display">
                <i class="fas fa-user-circle"></i>
                <span><?= $_SESSION['user_prenom'] ?? 'Utilisateur' ?> <?= $_SESSION['user_nom'] ?? '' ?></span>
            </div>
        </div>
    </div>
    
    <div class="container-fluid p-4">
        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card-modern variant-primary">
                <div class="stat-number"><?= $stats['total_patients'] ?? '245' ?></div>
                <div class="stat-label">Patients Total</div>
                <div class="stat-detail">
                    <i class="fas fa-arrow-up"></i>
                    +12 ce mois
                </div>
                <i class="fas fa-users stat-icon"></i>
            </div>
            
            <div class="stat-card-modern variant-success">
                <div class="stat-number"><?= $stats['consultations_jour'] ?? '23' ?></div>
                <div class="stat-label">Consultations</div>
                <div class="stat-detail">
                    <i class="fas fa-arrow-up"></i>
                    +5 depuis hier
                </div>
                <i class="fas fa-stethoscope stat-icon"></i>
            </div>
            
            <div class="stat-card-modern variant-danger">
                <div class="stat-number"><?= $stats['patients_hospitalises'] ?? '15' ?></div>
                <div class="stat-label">Hospitalisés</div>
                <div class="stat-detail">
                    <i class="fas fa-bed"></i>
                    <?= $stats['lits_occupes'] ?? '12' ?> lits occupés
                </div>
                <i class="fas fa-hospital-user stat-icon"></i>
            </div>
            
            <div class="stat-card-modern variant-warning">
                <div class="stat-number"><?= $stats['examens_attente'] ?? '8' ?></div>
                <div class="stat-label">Examens</div>
                <div class="stat-detail">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?= $stats['examens_urgent'] ?? '2' ?> urgents
                </div>
                <i class="fas fa-flask stat-icon"></i>
            </div>
        </div>
        
        <!-- Main Content Grid -->
        <div class="row g-4">
            <!-- Quick Actions -->
            <div class="col-md-4">
                <div class="quick-actions-card">
                    <div class="quick-actions-header">
                        <h5><i class="fas fa-bolt"></i> Actions Rapides</h5>
                    </div>
                    <div class="quick-actions-body">
                        <div class="actions-grid">
                            <a href="<?= BASE_URL ?>consultation" class="action-btn btn-primary-action">
                                <i class="fas fa-stethoscope"></i>
                                Nouvelle Consultation
                            </a>
                            <a href="<?= BASE_URL ?>patients/nouveau" class="action-btn btn-primary-action">
                                <i class="fas fa-user-plus"></i>
                                Nouveau Patient
                            </a>
                            <a href="<?= BASE_URL ?>laboratoire" class="action-btn btn-danger-action">
                                <i class="fas fa-flask"></i>
                                Laboratoire
                            </a>
                            <a href="<?= BASE_URL ?>modules/chat" class="action-btn btn-danger-action">
                                <i class="fas fa-comments"></i>
                                Messages
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Activity -->
            <div class="col-md-8">
                <div class="activity-card">
                    <div class="activity-header">
                        <h5>
                            <i class="fas fa-clock"></i>
                            Activité Récente
                        </h5>
                    </div>
                    <div class="activity-table-container">
                        <table class="activity-table">
                            <thead>
                                <tr>
                                    <th>Heure</th>
                                    <th>Patient</th>
                                    <th>Action</th>
                                    <th>Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td data-label="Heure">14:30</td>
                                    <td data-label="Patient" class="patient-name">DUPONT Marie</td>
                                    <td data-label="Action">Consultation</td>
                                    <td data-label="Statut">
                                        <span class="status-badge status-completed">Terminée</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td data-label="Heure">14:15</td>
                                    <td data-label="Patient" class="patient-name">BERNARD Paul</td>
                                    <td data-label="Action">Examen</td>
                                    <td data-label="Statut">
                                        <span class="status-badge status-in-progress">En cours</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td data-label="Heure">13:45</td>
                                    <td data-label="Patient" class="patient-name">LAMBERT Sophie</td>
                                    <td data-label="Action">Admission</td>
                                    <td data-label="Statut">
                                        <span class="status-badge status-planned">Planifiée</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td data-label="Heure">13:20</td>
                                    <td data-label="Patient" class="patient-name">MARTIN Jean</td>
                                    <td data-label="Action">Urgence</td>
                                    <td data-label="Statut">
                                        <span class="status-badge status-urgent">Urgent</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td data-label="Heure">12:55</td>
                                    <td data-label="Patient" class="patient-name">ROUSSEAU Claire</td>
                                    <td data-label="Action">Consultation</td>
                                    <td data-label="Statut">
                                        <span class="status-badge status-completed">Terminée</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
