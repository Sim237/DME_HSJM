<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logs Système - DME Hospital</title>
    <link rel="stylesheet" href="/public/css/style.css">
</head>
<body>
    <div class="logs-container">
        <header class="page-header">
            <h1>Logs Centralisés</h1>
            <a href="/admin/dashboard" class="btn btn-outline">← Retour Dashboard</a>
        </header>

        <!-- FILTRES -->
        <section class="filters-section">
            <form method="GET" class="filters-form">
                <div class="filter-group">
                    <label for="level">Niveau:</label>
                    <select name="level" id="level">
                        <option value="ALL" <?= $filters['level'] === 'ALL' ? 'selected' : '' ?>>Tous</option>
                        <option value="DEBUG" <?= $filters['level'] === 'DEBUG' ? 'selected' : '' ?>>Debug</option>
                        <option value="INFO" <?= $filters['level'] === 'INFO' ? 'selected' : '' ?>>Info</option>
                        <option value="WARNING" <?= $filters['level'] === 'WARNING' ? 'selected' : '' ?>>Warning</option>
                        <option value="ERROR" <?= $filters['level'] === 'ERROR' ? 'selected' : '' ?>>Error</option>
                        <option value="CRITICAL" <?= $filters['level'] === 'CRITICAL' ? 'selected' : '' ?>>Critical</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="module">Module:</label>
                    <select name="module" id="module">
                        <option value="ALL" <?= $filters['module'] === 'ALL' ? 'selected' : '' ?>>Tous</option>
                        <option value="AUTH" <?= $filters['module'] === 'AUTH' ? 'selected' : '' ?>>Authentification</option>
                        <option value="PATIENT" <?= $filters['module'] === 'PATIENT' ? 'selected' : '' ?>>Patients</option>
                        <option value="ADMIN" <?= $filters['module'] === 'ADMIN' ? 'selected' : '' ?>>Administration</option>
                        <option value="SYSTEM" <?= $filters['module'] === 'SYSTEM' ? 'selected' : '' ?>>Système</option>
                        <option value="DATABASE" <?= $filters['module'] === 'DATABASE' ? 'selected' : '' ?>>Base de données</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary">Filtrer</button>
                <button type="button" onclick="exportLogs()" class="btn btn-outline">Exporter</button>
            </form>
        </section>

        <!-- LOGS -->
        <section class="logs-section">
            <div class="logs-table-container">
                <table class="logs-table">
                    <thead>
                        <tr>
                            <th>Timestamp</th>
                            <th>Niveau</th>
                            <th>Module</th>
                            <th>Message</th>
                            <th>Utilisateur</th>
                            <th>IP</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <tr class="log-row level-<?= strtolower($log['level']) ?>">
                                <td class="timestamp">
                                    <?= date('d/m/Y H:i:s', strtotime($log['timestamp'])) ?>
                                </td>
                                <td class="level">
                                    <span class="level-badge level-<?= strtolower($log['level']) ?>">
                                        <?= $log['level'] ?>
                                    </span>
                                </td>
                                <td class="module"><?= htmlspecialchars($log['module']) ?></td>
                                <td class="message">
                                    <div class="message-preview">
                                        <?= htmlspecialchars(substr($log['message'], 0, 100)) ?>
                                        <?php if (strlen($log['message']) > 100): ?>
                                            <span class="message-more">...</span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (strlen($log['message']) > 100): ?>
                                        <div class="message-full" style="display: none;">
                                            <?= htmlspecialchars($log['message']) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="user">
                                    <?php if ($log['nom']): ?>
                                        <?= htmlspecialchars($log['prenom'] . ' ' . $log['nom']) ?>
                                    <?php else: ?>
                                        <span class="no-user">Système</span>
                                    <?php endif; ?>
                                </td>
                                <td class="ip"><?= htmlspecialchars($log['ip_address'] ?? '-') ?></td>
                                <td class="actions">
                                    <?php if (strlen($log['message']) > 100): ?>
                                        <button onclick="toggleMessage(this)" class="btn btn-sm">Voir plus</button>
                                    <?php endif; ?>
                                    <?php if ($log['context']): ?>
                                        <button onclick="showContext(<?= htmlspecialchars($log['context']) ?>)" class="btn btn-sm">Context</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- PAGINATION -->
        <section class="pagination-section">
            <div class="pagination">
                <?php if ($current_page > 1): ?>
                    <a href="?page=<?= $current_page - 1 ?>&level=<?= $filters['level'] ?>&module=<?= $filters['module'] ?>" class="btn btn-outline">← Précédent</a>
                <?php endif; ?>
                
                <span class="page-info">Page <?= $current_page ?></span>
                
                <a href="?page=<?= $current_page + 1 ?>&level=<?= $filters['level'] ?>&module=<?= $filters['module'] ?>" class="btn btn-outline">Suivant →</a>
            </div>
        </section>
    </div>

    <!-- MODAL CONTEXT -->
    <div id="contextModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Contexte du Log</h3>
                <button onclick="closeModal()" class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <pre id="contextContent"></pre>
            </div>
        </div>
    </div>

    <script>
        function toggleMessage(button) {
            const row = button.closest('tr');
            const preview = row.querySelector('.message-preview');
            const full = row.querySelector('.message-full');
            
            if (full.style.display === 'none') {
                preview.style.display = 'none';
                full.style.display = 'block';
                button.textContent = 'Voir moins';
            } else {
                preview.style.display = 'block';
                full.style.display = 'none';
                button.textContent = 'Voir plus';
            }
        }
        
        function showContext(context) {
            document.getElementById('contextContent').textContent = JSON.stringify(context, null, 2);
            document.getElementById('contextModal').style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('contextModal').style.display = 'none';
        }
        
        function exportLogs() {
            const params = new URLSearchParams(window.location.search);
            params.set('export', 'csv');
            window.location.href = '/admin/logs?' + params.toString();
        }
        
        // Fermer modal en cliquant à l'extérieur
        window.onclick = function(event) {
            const modal = document.getElementById('contextModal');
            if (event.target === modal) {
                closeModal();
            }
        }
        
        // Auto-refresh toutes les 30 secondes
        setInterval(() => {
            if (document.querySelector('select[name="level"]').value === 'ERROR' || 
                document.querySelector('select[name="level"]').value === 'CRITICAL') {
                location.reload();
            }
        }, 30000);
    </script>
</body>
</html>