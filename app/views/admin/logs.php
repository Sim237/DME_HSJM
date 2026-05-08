<?php
require_once __DIR__ . '/../layouts/header.php';
$adminPage = 'logs';
require_once __DIR__ . '/partials/admin_nav.php';
?>

        <!-- TOPBAR -->
        <div class="admin-topbar">
            <div class="admin-topbar-title">
                Journaux d'Audit
                <small>Traçabilité complète des actions utilisateurs</small>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-secondary-subtle text-secondary border" style="font-size:.72rem;">
                    <?= number_format($total_rows) ?> entrées
                </span>
            </div>
        </div>

        <div class="admin-page-content">

            <!-- FILTRES -->
            <form method="GET" class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-3">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label fw-bold small mb-1">Action</label>
                            <select name="action" class="form-select form-select-sm rounded-3">
                                <option value="ALL" <?= $filters['action'] === 'ALL' ? 'selected' : '' ?>>Toutes les actions</option>
                                <?php foreach ($actions as $a): ?>
                                <option value="<?= $a ?>" <?= $filters['action'] === $a ? 'selected' : '' ?>><?= $a ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold small mb-1">Table / Module</label>
                            <select name="table" class="form-select form-select-sm rounded-3">
                                <option value="ALL" <?= $filters['table'] === 'ALL' ? 'selected' : '' ?>>Tous les modules</option>
                                <?php foreach ($tables as $t): ?>
                                <option value="<?= $t ?>" <?= $filters['table'] === $t ? 'selected' : '' ?>><?= $t ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold small mb-1">Utilisateur</label>
                            <select name="user_id" class="form-select form-select-sm rounded-3">
                                <option value="">Tous les utilisateurs</option>
                                <?php foreach ($users as $u): ?>
                                <option value="<?= $u['id'] ?>" <?= (string)$filters['user_id'] === (string)$u['id'] ? 'selected' : '' ?>><?= htmlspecialchars($u['full_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-sm rounded-3 px-4 flex-grow-1">
                                <i class="bi bi-funnel me-1"></i>Filtrer
                            </button>
                            <a href="<?= BASE_URL ?>admin/logs" class="btn btn-light btn-sm rounded-3 border">
                                <i class="bi bi-x-circle"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </form>

            <!-- TABLE -->
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" style="font-size:.82rem;">
                            <thead style="background:#f8fafc;">
                                <tr class="text-muted" style="font-size:.7rem; text-transform:uppercase; letter-spacing:.06em;">
                                    <th class="ps-4 py-3">Date / Heure</th>
                                    <th class="py-3">Utilisateur</th>
                                    <th class="py-3">Action</th>
                                    <th class="py-3">Table</th>
                                    <th class="py-3">Enreg. ID</th>
                                    <th class="py-3">Détails</th>
                                    <th class="py-3 pe-4">IP</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($logs)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-5 text-muted">
                                            <i class="bi bi-journal-x d-block fs-2 mb-2 opacity-25"></i>
                                            Aucun journal correspondant aux filtres.
                                        </td>
                                    </tr>
                                <?php else: foreach ($logs as $log):
                                    $actionColors = [
                                        'INSERT' => 'success', 'CREATE' => 'success',
                                        'UPDATE' => 'primary',
                                        'DELETE' => 'danger',
                                        'READ'   => 'secondary',
                                        'LOGIN'  => 'info', 'LOGOUT' => 'warning',
                                    ];
                                    $ac = $actionColors[$log['action']] ?? 'secondary';
                                ?>
                                    <tr>
                                        <td class="ps-4">
                                            <span class="fw-bold text-dark"><?= date('d/m/Y', strtotime($log['created_at'])) ?></span>
                                            <br><small class="text-muted"><?= date('H:i:s', strtotime($log['created_at'])) ?></small>
                                        </td>
                                        <td>
                                            <?php if ($log['nom']): ?>
                                                <span class="fw-bold"><?= htmlspecialchars($log['prenom'] . ' ' . $log['nom']) ?></span>
                                                <br><small class="text-muted">@<?= htmlspecialchars($log['username'] ?? '') ?></small>
                                            <?php else: ?>
                                                <span class="text-muted fst-italic">Système</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= $ac ?>-subtle text-<?= $ac ?> border border-<?= $ac ?>-subtle fw-bold" style="font-size:.7rem;">
                                                <?= $log['action'] ?>
                                            </span>
                                        </td>
                                        <td><code style="font-size:.75rem; color:#7c3aed;"><?= htmlspecialchars($log['table_name'] ?? '') ?></code></td>
                                        <td><small class="text-muted"><?= $log['record_id'] ? '#' . $log['record_id'] : '—' ?></small></td>
                                        <td style="max-width:220px;">
                                            <?php
                                            $detail = $log['new_values'] ?? $log['old_values'] ?? '';
                                            if ($detail) {
                                                $decoded = json_decode($detail, true);
                                                $text = is_array($decoded) ? implode(', ', array_map(fn($k,$v) => "$k: $v", array_keys($decoded), $decoded)) : $detail;
                                                echo '<small class="text-muted" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;display:block;" title="' . htmlspecialchars($text) . '">' . htmlspecialchars(mb_substr($text, 0, 60)) . (mb_strlen($text) > 60 ? '…' : '') . '</small>';
                                            } else {
                                                echo '<span class="text-muted">—</span>';
                                            }
                                            ?>
                                        </td>
                                        <td class="pe-4"><small class="text-muted font-monospace"><?= htmlspecialchars($log['ip_address'] ?? '—') ?></small></td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- PAGINATION -->
                <?php if ($total_pages > 1): ?>
                <div class="card-footer border-0 bg-transparent p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted">
                            Page <?= $page ?> sur <?= $total_pages ?> — <?= number_format($total_rows) ?> entrées au total
                        </small>
                        <nav>
                            <ul class="pagination pagination-sm mb-0">
                                <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link rounded-3 me-1" href="?page=<?= $page-1 ?>&action=<?= urlencode($filters['action']) ?>&table=<?= urlencode($filters['table']) ?>&user_id=<?= urlencode($filters['user_id']) ?>">
                                        <i class="bi bi-chevron-left"></i>
                                    </a>
                                </li>
                                <?php endif; ?>
                                <?php
                                $start = max(1, $page - 2);
                                $end   = min($total_pages, $page + 2);
                                for ($p = $start; $p <= $end; $p++):
                                ?>
                                <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                                    <a class="page-link rounded-3 me-1" href="?page=<?= $p ?>&action=<?= urlencode($filters['action']) ?>&table=<?= urlencode($filters['table']) ?>&user_id=<?= urlencode($filters['user_id']) ?>"><?= $p ?></a>
                                </li>
                                <?php endfor; ?>
                                <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link rounded-3" href="?page=<?= $page+1 ?>&action=<?= urlencode($filters['action']) ?>&table=<?= urlencode($filters['table']) ?>&user_id=<?= urlencode($filters['user_id']) ?>">
                                        <i class="bi bi-chevron-right"></i>
                                    </a>
                                </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    </div>
                </div>
                <?php endif; ?>
            </div>

        </div><!-- /admin-page-content -->
    </div><!-- /admin-main -->
</div><!-- /admin-shell -->

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
