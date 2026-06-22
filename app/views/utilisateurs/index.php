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

require_once __DIR__ . '/../layouts/header.php';
$adminPage = 'utilisateurs';
require_once __DIR__ . '/../admin/partials/admin_nav.php';
?>
<style>
/* Couleurs personnalisées pour les badges de rôles (non incluses par défaut dans Bootstrap) */
.bg-purple-subtle   { background-color: #ede9fe !important; }
.text-purple        { color: #6d28d9 !important; }
.border-purple-subtle { border-color: #c4b5fd !important; }

.bg-teal-subtle     { background-color: #ccfbf1 !important; }
.text-teal          { color: #0f766e !important; }
.border-teal-subtle { border-color: #5eead4 !important; }
</style>

        <!-- TOPBAR -->
        <div class="admin-topbar">
            <div class="admin-topbar-title">
                Personnel de l'Hôpital
                <small>Gestion des comptes utilisateurs</small>
            </div>
            <div class="adm-topbar-actions">
                <div class="adm-clock-pill">
                    <span class="adm-clock-dot"></span>
                    <span class="adm-clock-time">--:--:--</span>
                </div>
                <!-- Export Excel (dropdown : xlsx ou csv) -->
                <div class="dropdown">
                    <button class="adm-topbtn adm-topbtn-ghost dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-file-earmark-spreadsheet"></i> Exporter
                    </button>
                    <ul class="dropdown-menu shadow-sm rounded-3">
                        <li>
                            <a class="dropdown-item" href="<?= BASE_URL ?>utilisateurs/export">
                                <i class="bi bi-file-earmark-excel-fill me-2 text-success"></i>
                                Excel (.xlsx)
                                <small class="d-block text-muted">Format Microsoft Excel</small>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="<?= BASE_URL ?>utilisateurs/export?format=csv">
                                <i class="bi bi-filetype-csv me-2 text-primary"></i>
                                CSV (.csv)
                                <small class="d-block text-muted">Compatible avec tous les tableurs</small>
                            </a>
                        </li>
                    </ul>
                </div>
                <!-- Import Excel -->
                <button class="adm-topbtn adm-topbtn-ghost" onclick="ouvrirModalImport()">
                    <i class="bi bi-upload"></i> Importer
                </button>
                <!-- Nouveau utilisateur -->
                <button class="adm-topbtn adm-topbtn-ghost" onclick="openModal()" style="background:#1e40af;color:#fff;">
                    <i class="bi bi-person-plus-fill"></i> Nouvel Utilisateur
                </button>
            </div>
        </div>

        <!-- ═════════════════════════════════════════════════
             MODAL : IMPORT EXCEL
             ═════════════════════════════════════════════════ -->
        <div class="modal fade" id="modalImportExcel" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
              <div class="modal-header border-0 pb-0"
                   style="background:linear-gradient(135deg,#3b82f6,#60a5fa);border-radius:16px 16px 0 0;">
                <h5 class="modal-title text-white fw-bold">
                  <i class="bi bi-file-earmark-arrow-up-fill me-2"></i>
                  Importer des utilisateurs (Excel/CSV)
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
              </div>

              <form action="<?= BASE_URL ?>utilisateurs/import" method="POST"
                    enctype="multipart/form-data" id="formImportUsers">
                <div class="modal-body p-4">

                  <!-- Téléchargement du modèle -->
                  <div class="alert alert-info border-0 rounded-3 d-flex align-items-center"
                       style="background:#eff6ff;color:#1e40af;">
                    <i class="bi bi-lightbulb-fill me-3" style="font-size:1.4rem;"></i>
                    <div class="flex-grow-1">
                      <strong>Premier import ?</strong>
                      Téléchargez d'abord le modèle Excel pré-rempli et adaptez-le à vos données.
                    </div>
                    <a href="<?= BASE_URL ?>utilisateurs/modele-excel"
                       class="btn btn-sm btn-primary rounded-pill px-3">
                      <i class="bi bi-download me-1"></i>Modèle Excel
                    </a>
                  </div>

                  <!-- Zone de drop / sélection -->
                  <div class="mt-3">
                    <label class="form-label fw-semibold small">Fichier à importer</label>
                    <div id="dropZoneImport"
                         style="border:2px dashed #cbd5e1;border-radius:14px;padding:32px;text-align:center;
                                cursor:pointer;transition:all .2s;background:#f8fafc;"
                         onclick="document.getElementById('inputFichierImport').click()">
                      <i class="bi bi-cloud-arrow-up-fill" style="font-size:2.4rem;color:#94a3b8;"></i>
                      <div class="fw-bold mt-2" id="dropFichierLabel">
                        Cliquez ou glissez votre fichier ici
                      </div>
                      <small class="text-muted">Formats acceptés : .xlsx, .csv</small>
                    </div>
                    <input type="file" id="inputFichierImport" name="fichier"
                           accept=".xlsx,.csv,.txt" required style="display:none;"
                           onchange="afficherFichierImport()">
                  </div>

                  <!-- Règles d'import -->
                  <div class="mt-3 p-3 rounded-3" style="background:#fef9c3;font-size:.83rem;">
                    <strong>📋 Règles d'import :</strong>
                    <ul class="mb-0 mt-2" style="padding-left:18px;">
                      <li>Colonnes <strong>obligatoires</strong> : <code>Nom</code>, <code>Prenom</code>, <code>Username</code>, <code>Role</code>, <code>Service</code></li>
                      <li>Si la colonne <code>ID</code> est renseignée → l'utilisateur sera <strong>mis à jour</strong></li>
                      <li>Si la colonne <code>ID</code> est vide → un <strong>nouvel utilisateur</strong> sera créé</li>
                      <li>Si <code>Mot_de_passe</code> est vide pour une création → un mot de passe sera <strong>généré automatiquement</strong></li>
                      <li>Le <code>Service</code> est identifié par son nom (insensible à la casse) ou son ID numérique</li>
                    </ul>
                  </div>

                </div>
                <div class="modal-footer border-0 pt-0">
                  <button type="button" class="btn btn-outline-secondary rounded-pill px-4"
                          data-bs-dismiss="modal">Annuler</button>
                  <button type="submit" class="btn btn-primary rounded-pill px-5 fw-semibold"
                          id="btnSubmitImport" disabled>
                    <i class="bi bi-upload me-2"></i>Lancer l'import
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>

        <div class="admin-page-content">

            <!-- ── BARRE DE FILTRES ──────────────────────────────────────── -->
            <div class="d-flex align-items-center gap-2 flex-wrap mb-3">
                <!-- Recherche texte -->
                <div class="position-relative flex-grow-1" style="min-width:200px;max-width:320px;">
                    <i class="bi bi-search position-absolute" style="left:11px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:.85rem;"></i>
                    <input type="text" id="userSearch" placeholder="Nom, prénom, @login…"
                           class="form-control form-control-sm ps-4 rounded-3"
                           style="border:1.5px solid #e2e8f0;font-size:.83rem;"
                           oninput="filtrerUtilisateurs()">
                </div>

                <!-- Filtre service -->
                <select id="filterService" class="form-select form-select-sm rounded-3"
                        style="max-width:220px;border:1.5px solid #e2e8f0;font-size:.83rem;"
                        onchange="filtrerUtilisateurs()">
                    <option value="">Tous les services</option>
                    <?php foreach ($services as $svc): ?>
                    <option value="<?= (int)$svc['id'] ?>"><?= htmlspecialchars($svc['nom_service']) ?></option>
                    <?php endforeach; ?>
                </select>

                <!-- Filtre rôle -->
                <select id="filterRole" class="form-select form-select-sm rounded-3"
                        style="max-width:200px;border:1.5px solid #e2e8f0;font-size:.83rem;"
                        onchange="filtrerUtilisateurs()">
                    <option value="">Tous les rôles</option>
                    <?php
                    $rolesUniques = array_unique(array_column($users, 'role'));
                    sort($rolesUniques);
                    foreach ($rolesUniques as $r):
                    ?>
                    <option value="<?= htmlspecialchars($r) ?>"><?= htmlspecialchars($r) ?></option>
                    <?php endforeach; ?>
                </select>

                <!-- Filtre statut -->
                <select id="filterStatut" class="form-select form-select-sm rounded-3"
                        style="max-width:150px;border:1.5px solid #e2e8f0;font-size:.83rem;"
                        onchange="filtrerUtilisateurs()">
                    <option value="">Tous les statuts</option>
                    <option value="1">Actif</option>
                    <option value="0">Inactif</option>
                </select>

                <!-- Compteur -->
                <span id="userCount" class="ms-auto text-muted" style="font-size:.78rem;white-space:nowrap;"></span>

                <!-- Reset -->
                <button class="btn btn-sm btn-light border rounded-3" onclick="resetFiltres()" style="font-size:.78rem;">
                    <i class="bi bi-x-circle me-1"></i>Réinitialiser
                </button>
            </div>

            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" style="font-size:.85rem;">
                            <thead style="background:#f8fafc;">
                                <tr class="text-muted" style="font-size:.7rem; text-transform:uppercase; letter-spacing:.06em;">
                                    <th class="ps-4 py-3">Utilisateur</th>
                                    <th class="py-3">Rôle</th>
                                    <th class="py-3">Service</th>
                                    <th class="py-3">Authentification</th>
                                    <th class="py-3">Statut</th>
                                    <th class="py-3 text-end pe-4">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="tbodyUsers">
                                <?php foreach ($users as $user): ?>
                                <tr data-service="<?= (int)($user['service_id'] ?? 0) ?>"
                                    data-role="<?= htmlspecialchars($user['role']) ?>"
                                    data-statut="<?= $user['actif'] ? '1' : '0' ?>"
                                    data-search="<?= htmlspecialchars(strtolower($user['nom'] . ' ' . $user['prenom'] . ' ' . $user['username'] . ' ' . ($user['nom_service'] ?? ''))) ?>">
                                    <td class="ps-4">
                                        <div class="fw-bold"><?= htmlspecialchars($user['nom'] . ' ' . $user['prenom']) ?></div>
                                        <small class="text-muted">@<?= htmlspecialchars($user['username']) ?></small>
                                    </td>
                                    <td>
                                        <?php
                                        $roleColors = [
                                            'ADMIN'                  => 'danger',
                                            'MEDECIN'                => 'primary',
                                            'INFIRMIER'              => 'info',
                                            'INFIRMIER_CONSULTANT'   => 'info',
                                            'SECRETAIRE'             => 'secondary',
                                            'SECRETAIRE_SAU'         => 'danger',
                                            'SECRETAIRE_SPECIALISTE' => 'purple',
                                            'SECRETAIRE_LABO'        => 'info',
                                            'LABORANTIN'             => 'warning',
                                            'TECHNICIEN_LABO'        => 'warning',
                                            'PHARMACIEN'             => 'success',
                                            'DIRECTEUR'              => 'dark',
                                            'MAJOR'                  => 'dark',
                                            'PARAMETRES'             => 'info',
                                            'COORDONNATEUR_SOINS'    => 'teal',
                                        ];
                                        $rc = $roleColors[$user['role']] ?? 'secondary';
                                        ?>
                                        <span class="badge bg-<?= $rc ?>-subtle text-<?= $rc ?> border border-<?= $rc ?>-subtle fw-bold" style="font-size:.7rem;">
                                            <?= htmlspecialchars($user['role']) ?>
                                        </span>
                                    </td>
                                    <td><span class="fw-semibold text-primary" style="font-size:.83rem;"><?= htmlspecialchars($user['nom_service'] ?? 'Non assigné') ?></span></td>
                                    <td>
                                        <?php if ($user['signature_path']): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle me-1" style="font-size:.68rem;">
                                                <i class="bi bi-pen-fill me-1"></i>Signature
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($user['cachet_path']): ?>
                                            <span class="badge bg-info-subtle text-info border border-info-subtle" style="font-size:.68rem;">
                                                <i class="bi bi-patch-check-fill me-1"></i>Cachet
                                            </span>
                                        <?php endif; ?>
                                        <?php if (!$user['signature_path'] && !$user['cachet_path']): ?>
                                            <span class="text-muted" style="font-size:.78rem;">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge <?= $user['actif'] == 1 ? 'bg-success' : 'bg-danger' ?> rounded-pill">
                                            <?= $user['actif'] == 1 ? 'Actif' : 'Inactif' ?>
                                        </span>
                                        <?php if (!empty($user['must_change_password'])): ?>
                                        <span class="badge bg-warning text-dark rounded-pill ms-1"
                                              title="Doit changer son mot de passe à la prochaine connexion">
                                            <i class="bi bi-shield-lock-fill"></i> Chgt MDP
                                        </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-4">
                                        <div class="d-flex gap-1 justify-content-end">
                                            <button class="btn btn-sm btn-light border rounded-pill px-2"
                                                    onclick='editUser(<?= htmlspecialchars(json_encode($user), ENT_QUOTES) ?>)'
                                                    title="Modifier">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>
                                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                            <!-- Réinitialiser le mot de passe -->
                                            <button class="btn btn-sm btn-outline-warning rounded-pill px-2"
                                                    data-id="<?= $user['id'] ?>"
                                                    data-nom="<?= htmlspecialchars($user['nom'] . ' ' . $user['prenom']) ?>"
                                                    onclick="ouvrirResetMdp(this.dataset.id, this.dataset.nom)"
                                                    title="Réinitialiser le mot de passe">
                                                <i class="bi bi-key-fill"></i>
                                            </button>
                                            <!-- Forcer changement MDP -->
                                            <button class="btn btn-sm rounded-pill px-2 <?= !empty($user['must_change_password']) ? 'btn-warning' : 'btn-outline-secondary' ?>"
                                                    data-id="<?= $user['id'] ?>"
                                                    data-nom="<?= htmlspecialchars($user['nom'] . ' ' . $user['prenom']) ?>"
                                                    data-actif="<?= !empty($user['must_change_password']) ? '1' : '0' ?>"
                                                    onclick="forcerChangementMdp(this.dataset.id, this.dataset.nom, this.dataset.actif)"
                                                    title="<?= !empty($user['must_change_password']) ? 'Changement MDP déjà demandé — cliquer pour annuler' : 'Forcer le changement de mot de passe à la prochaine connexion' ?>">
                                                <i class="bi bi-shield-lock<?= !empty($user['must_change_password']) ? '-fill' : '' ?>"></i>
                                            </button>
                                            <!-- Changer de service -->
                                            <button class="btn btn-sm btn-outline-primary rounded-pill px-2"
                                                    data-id="<?= $user['id'] ?>"
                                                    data-nom="<?= htmlspecialchars($user['nom'] . ' ' . $user['prenom']) ?>"
                                                    data-service="<?= (int)($user['service_id'] ?? 0) ?>"
                                                    onclick="ouvrirChangerServiceUser(this.dataset.id, this.dataset.nom, this.dataset.service)"
                                                    title="Changer de service">
                                                <i class="bi bi-building-fill-gear"></i>
                                            </button>
                                            <?php if ($user['actif'] == 1): ?>
                                            <button class="btn btn-sm btn-outline-danger rounded-pill px-2"
                                                    data-id="<?= $user['id'] ?>"
                                                    data-nom="<?= htmlspecialchars($user['nom'] . ' ' . $user['prenom']) ?>"
                                                    onclick="deleteUser(this.dataset.id, this.dataset.nom)"
                                                    title="Désactiver">
                                                <i class="bi bi-person-x-fill"></i>
                                            </button>
                                            <?php else: ?>
                                            <button class="btn btn-sm btn-outline-success rounded-pill px-2"
                                                    data-id="<?= $user['id'] ?>"
                                                    data-nom="<?= htmlspecialchars($user['nom'] . ' ' . $user['prenom']) ?>"
                                                    onclick="reactivateUser(this.dataset.id, this.dataset.nom)"
                                                    title="Réactiver">
                                                <i class="bi bi-person-check-fill"></i>
                                            </button>
                                            <?php endif; ?>
                                            <button class="btn btn-sm btn-outline-dark rounded-pill px-2"
                                                    data-id="<?= $user['id'] ?>"
                                                    data-nom="<?= htmlspecialchars($user['nom'] . ' ' . $user['prenom']) ?>"
                                                    onclick="destroyUser(this.dataset.id, this.dataset.nom)"
                                                    title="Supprimer définitivement">
                                                <i class="bi bi-trash3-fill"></i>
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div><!-- /admin-page-content -->
    </div><!-- /admin-main -->
</div><!-- /admin-shell -->

<!-- ═══════════════════════════════════════════════
     MODAL — RÉINITIALISATION MOT DE PASSE
     ═══════════════════════════════════════════════ -->
<div class="modal fade" id="modalResetMdp" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:460px">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 py-3 px-4"
                 style="background:linear-gradient(135deg,#d97706,#f59e0b);border-radius:16px 16px 0 0">
                <div class="d-flex align-items-center gap-3 w-100">
                    <div style="width:40px;height:40px;background:rgba(255,255,255,.2);border-radius:10px;display:flex;align-items:center;justify-content:center">
                        <i class="bi bi-key-fill text-white fs-5"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-0 text-white" style="font-size:.95rem">Réinitialiser le mot de passe</h5>
                        <small class="text-white-50" style="font-size:.72rem">Un nouveau mot de passe temporaire sera généré</small>
                    </div>
                    <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal"></button>
                </div>
            </div>
            <div class="modal-body px-4 pt-4 pb-3">
                <!-- Avant réinitialisation -->
                <div id="resetMdpConfirm">
                    <div class="d-flex align-items-center gap-3 p-3 rounded-3 mb-3"
                         style="background:#fef9c3;border:1px solid #fde047;">
                        <i class="bi bi-exclamation-triangle-fill text-warning fs-5"></i>
                        <div style="font-size:.83rem;color:#78350f">
                            Le mot de passe actuel de <strong id="resetNomUser"></strong> sera
                            immédiatement remplacé. L'utilisateur devra utiliser le nouveau mot de passe
                            dès sa prochaine connexion.
                        </div>
                    </div>
                    <div id="resetErrMsg" class="alert alert-danger rounded-3 py-2 px-3 d-none" style="font-size:.8rem"></div>
                </div>
                <!-- Après réinitialisation : afficher le nouveau mdp -->
                <div id="resetMdpResult" style="display:none">
                    <div class="text-center mb-3">
                        <div style="width:56px;height:56px;background:#d1fae5;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 12px">
                            <i class="bi bi-check-lg text-success fs-4"></i>
                        </div>
                        <p style="font-size:.83rem;color:#374151;margin-bottom:12px">
                            Mot de passe réinitialisé pour <strong id="resetResultNom"></strong>
                        </p>
                    </div>
                    <div class="p-3 rounded-3 text-center" style="background:#f8fafc;border:2px dashed #cbd5e1">
                        <div style="font-size:.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px">
                            Identifiant &amp; Nouveau mot de passe
                        </div>
                        <div style="font-size:.83rem;color:#64748b;margin-bottom:8px">
                            Login : <code id="resetResultUsername" style="background:#e2e8f0;padding:2px 8px;border-radius:4px"></code>
                        </div>
                        <div id="resetResultPwd"
                             style="font-size:1.6rem;font-weight:900;letter-spacing:4px;color:#1e40af;font-family:monospace"></div>
                        <button onclick="copierMdp()" class="btn btn-sm btn-outline-primary rounded-pill mt-2 px-3"
                                style="font-size:.75rem">
                            <i class="bi bi-clipboard me-1"></i>Copier
                        </button>
                    </div>
                    <div class="alert alert-warning rounded-3 py-2 px-3 mt-3" style="font-size:.76rem">
                        <i class="bi bi-exclamation-circle me-1"></i>
                        Communiquez ce mot de passe à l'utilisateur en privé. Il ne sera plus visible après la fermeture de cette fenêtre.
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 px-4 pb-4 pt-2 gap-2">
                <button id="btnResetMdpConfirm" type="button"
                        onclick="confirmerResetMdp()"
                        class="btn btn-warning rounded-pill fw-bold flex-grow-1">
                    <i class="bi bi-key-fill me-1"></i>Générer un nouveau mot de passe
                </button>
                <button type="button" class="btn btn-outline-secondary rounded-pill"
                        data-bs-dismiss="modal" style="font-size:.82rem">Fermer</button>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════
     MODAL — CHANGER DE SERVICE (UTILISATEUR)
     ═══════════════════════════════════════════════ -->
<div class="modal fade" id="modalChangerServiceUser" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:480px">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 py-3 px-4"
                 style="background:linear-gradient(135deg,#1e40af,#3b82f6);border-radius:16px 16px 0 0">
                <div class="d-flex align-items-center gap-3 w-100">
                    <div style="width:40px;height:40px;background:rgba(255,255,255,.2);border-radius:10px;display:flex;align-items:center;justify-content:center">
                        <i class="bi bi-building-fill-gear text-white fs-5"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-0 text-white" style="font-size:.95rem">Muter vers un autre service</h5>
                        <small class="text-white-50" id="csUserSubtitle" style="font-size:.72rem"></small>
                    </div>
                    <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal"></button>
                </div>
            </div>
            <div class="modal-body px-4 pt-4 pb-3">
                <input type="hidden" id="csUserId">
                <!-- Service actuel -->
                <div class="mb-3 p-3 rounded-3" style="background:#f8fafc;border:1px solid #e2e8f0">
                    <div style="font-size:.68rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px">Service actuel</div>
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-building-fill text-primary" style="font-size:.9rem"></i>
                        <span id="csServiceActuel" style="font-weight:700;color:#0f172a;font-size:.87rem"></span>
                    </div>
                </div>
                <!-- Sélecteur nouveau service -->
                <div class="mb-3">
                    <label class="form-label fw-bold" style="font-size:.75rem;text-transform:uppercase;letter-spacing:.5px;color:#64748b">
                        <i class="bi bi-arrow-right me-1"></i>Nouveau service de destination
                    </label>
                    <select id="csNouveauService" class="form-select rounded-3">
                        <option value="">— Sélectionner un service —</option>
                        <?php foreach ($services as $svc): ?>
                        <option value="<?= (int)$svc['id'] ?>"><?= htmlspecialchars($svc['nom_service']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div id="csErrMsg" class="alert alert-danger rounded-3 py-2 px-3 d-none" style="font-size:.8rem"></div>
                <div id="csOkMsg" class="alert alert-success rounded-3 py-2 px-3 d-none" style="font-size:.8rem"></div>
            </div>
            <div class="modal-footer border-0 px-4 pb-4 pt-2 gap-2">
                <button id="btnCsConfirm" type="button" onclick="confirmerChangerService()"
                        class="btn btn-primary rounded-pill fw-bold flex-grow-1">
                    <i class="bi bi-building-fill-gear me-1"></i>Appliquer la mutation
                </button>
                <button type="button" class="btn btn-outline-secondary rounded-pill"
                        data-bs-dismiss="modal" style="font-size:.82rem">Annuler</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL UTILISATEUR -->
<div class="modal fade" id="userModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form id="formUser" enctype="multipart/form-data">
                <input type="hidden" name="id" id="userId">
                <div class="modal-header border-0 pb-0">
                    <h5 class="fw-bold" id="modalTitle">Configuration Utilisateur</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">NOM</label>
                            <input type="text" name="nom" id="nom" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">PRÉNOM</label>
                            <input type="text" name="prenom" id="prenom" class="form-control" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-bold small">ADRESSE EMAIL</label>
                            <input type="email" name="email" id="email" class="form-control" placeholder="exemple@hospital.com">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">LOGIN</label>
                            <input type="text" name="username" id="username" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">SERVICE <span id="serviceHint" class="text-muted fw-normal" style="font-size:.78rem;"></span></label>
                            <select name="service_id" id="service_id" class="form-select" required>
                                <option value="">-- Sélectionner --</option>
                                <?php foreach ($services as $s): ?>
                                <option value="<?= $s['id'] ?>"
                                        data-clinique="<?= ($s['categorie'] === 'CLINIQUE' || in_array($s['id'], [11, 16])) ? '1' : '0' ?>">
                                    <?= htmlspecialchars($s['nom_service']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">RÔLE</label>
                            <select name="role" id="role" class="form-select" required onchange="toggleSignatureFields(); filtrerServicesParRole()">
                                <option value="ADMIN">Administrateur</option>
                                <option value="DIRECTEUR">Directeur</option>
                                <option value="MEDECIN">Médecin</option>
                                <option value="INFIRMIER">Infirmier</option>
                                <option value="INFIRMIER_CONSULTANT">Infirmier Consultant</option>
                                <option value="INFIRMIER_ANESTHESISTE">Infirmier Anesthésiste (IADE)</option>
                                <option value="INFIRMIER_BLOC">Infirmier Bloc Opératoire (IBODE)</option>
                                <option value="MAJOR">Major</option>
                                <option value="SECRETAIRE">Secrétaire (Accueil)</option>
                                <option value="SECRETAIRE_SAU">Secrétaire SAU (Urgences)</option>
                                <option value="SECRETAIRE_SPECIALISTE">Secrétaire Spécialistes</option>
                                <option value="SECRETAIRE_LABO">Secrétaire de Laboratoire</option>
                                <option value="PARAMETRES">Infirmier — Paramètres</option>
                                <option value="LABORANTIN">Laborantin</option>
                                <option value="TECHNICIEN_LABO">Technicien de Laboratoire</option>
                                <option value="PHARMACIEN">Pharmacien</option>
                                <option value="COORDONNATEUR_SOINS">Coordonnateur des Soins</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">MOT DE PASSE</label>
                            <input type="password" name="password" id="password" class="form-control">
                            <small id="pwdHelp" class="text-muted d-none">Laisser vide = inchangé</small>
                        </div>
                        <div class="col-12 p-3 bg-light rounded-3" id="signatureZone">
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="small fw-bold">Signature (PNG)</label>
                                    <input type="file" name="signature" class="form-control form-control-sm">
                                </div>
                                <div class="col-md-6">
                                    <label class="small fw-bold">Cachet</label>
                                    <input type="file" name="cachet" class="form-control form-control-sm">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary px-5 shadow">Enregistrer l'utilisateur</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- TOAST -->
<div class="toast-container">
    <div id="adminToast" class="toast align-items-center border-0 text-white" role="alert">
        <div class="d-flex">
            <div class="toast-body fw-bold" id="adminToastMsg"></div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<script>
let modal;
document.addEventListener('DOMContentLoaded', function() {
    modal = new bootstrap.Modal(document.getElementById('userModal'));
});

function showToast(msg, ok = true) {
    const t = document.getElementById('adminToast');
    t.className = 'toast align-items-center border-0 text-white ' + (ok ? 'bg-success' : 'bg-danger');
    document.getElementById('adminToastMsg').textContent = msg;
    bootstrap.Toast.getOrCreateInstance(t).show();
}

function toggleSignatureFields() {
    const role = document.getElementById('role').value;
    document.getElementById('signatureZone').style.display =
        (role === 'MEDECIN' || role === 'ADMIN') ? 'block' : 'none';
}

// Filtrer les services selon le rôle sélectionné
// INFIRMIER / INFIRMIER_CONSULTANT / MAJOR / PARAMETRES → services cliniques uniquement
// COORDONNATEUR_SOINS → auto-sélection service Administration
function filtrerServicesParRole() {
    const role = document.getElementById('role').value;
    const rolesClinik = ['INFIRMIER','INFIRMIER_CONSULTANT','MAJOR','PARAMETRES'];
    const rolesAdmin   = ['ADMIN','DIRECTEUR','COORDONNATEUR_SOINS'];
    const hint   = document.getElementById('serviceHint');
    const select = document.getElementById('service_id');

    Array.from(select.options).forEach(opt => {
        if (!opt.value) return; // garder l'option vide
        if (rolesClinik.includes(role)) {
            const isClinique = opt.getAttribute('data-clinique') === '1';
            opt.style.display = isClinique ? '' : 'none';
            opt.disabled = !isClinique;
        } else {
            opt.style.display = '';
            opt.disabled = false;
        }
    });

    // Réinitialiser la sélection si elle est maintenant masquée
    if (select.options[select.selectedIndex]?.disabled) {
        select.value = '';
    }

    // Pour Coordonnateur des Soins → auto-sélectionner le service Administration
    if (role === 'COORDONNATEUR_SOINS' && !select.value) {
        Array.from(select.options).forEach(opt => {
            if (opt.text.toLowerCase().includes('admin')) {
                select.value = opt.value;
            }
        });
        hint.textContent = '(service Administration)';
    } else if (rolesClinik.includes(role)) {
        hint.textContent = '(services cliniques uniquement)';
    } else {
        hint.textContent = '';
    }
}

function openModal() {
    document.getElementById('formUser').reset();
    document.getElementById('userId').value = '';
    document.getElementById('modalTitle').innerText = 'Nouvel Utilisateur';
    document.getElementById('pwdHelp').classList.add('d-none');
    toggleSignatureFields();
    filtrerServicesParRole();
    modal.show();
}

function editUser(user) {
    document.getElementById('userId').value    = user.id;
    document.getElementById('nom').value       = user.nom;
    document.getElementById('prenom').value    = user.prenom;
    document.getElementById('username').value  = user.username;
    document.getElementById('email').value     = user.email || '';
    document.getElementById('role').value      = user.role;
    if (user.service_id) document.getElementById('service_id').value = user.service_id;
    document.getElementById('modalTitle').innerText = 'Modifier : ' + user.nom + ' ' + user.prenom;
    document.getElementById('pwdHelp').classList.remove('d-none');
    toggleSignatureFields();
    filtrerServicesParRole();
    modal.show();
}

document.getElementById('formUser').onsubmit = function(e) {
    e.preventDefault();
    fetch('<?= BASE_URL ?>utilisateurs/save', { method: 'POST', body: new FormData(this) })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showToast('Utilisateur enregistré avec succès.');
                setTimeout(() => location.reload(), 800);
            } else {
                showToast('Erreur : ' + (data.message || 'Inconnue'), false);
            }
        })
        .catch(() => showToast('Erreur technique de connexion', false));
};

function deleteUser(id, nom) {
    if (!confirm(`Désactiver le compte de "${nom}" ?\n\nL'utilisateur ne pourra plus se connecter.`)) return;
    const fd = new FormData();
    fd.append('id', id);
    fetch('<?= BASE_URL ?>utilisateurs/delete', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                showToast(`Compte de "${nom}" désactivé.`);
                setTimeout(() => location.reload(), 800);
            } else {
                showToast('Erreur : ' + (d.message || 'Inconnue'), false);
            }
        });
}

function reactivateUser(id, nom) {
    if (!confirm(`Réactiver le compte de "${nom}" ?\n\nL'utilisateur pourra à nouveau se connecter.`)) return;
    const fd = new FormData();
    fd.append('id', id);
    fetch('<?= BASE_URL ?>utilisateurs/reactivate', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                showToast(`Compte de "${nom}" réactivé.`);
                setTimeout(() => location.reload(), 800);
            } else {
                showToast('Erreur : ' + (d.message || 'Inconnue'), false);
            }
        });
}

function destroyUser(id, nom) {
    if (!confirm(`⚠️ SUPPRESSION DÉFINITIVE\n\nSupprimer "${nom}" de la base de données ?\n\nCette action est irréversible.`)) return;
    if (!confirm(`Confirmer une dernière fois la suppression définitive de "${nom}" ?`)) return;
    const fd = new FormData();
    fd.append('id', id);
    fetch('<?= BASE_URL ?>utilisateurs/destroy', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                showToast(`"${nom}" supprimé définitivement.`);
                setTimeout(() => location.reload(), 800);
            } else {
                showToast(d.message || 'Erreur de suppression', false);
            }
        });
}

// ── Forcer le changement de mot de passe ───────────────────────
function forcerChangementMdp(id, nom, actifStr) {
    const actif   = actifStr === '1';
    const action  = actif ? 'annuler la demande de changement de MDP' : 'forcer le changement de mot de passe';
    const msg     = actif
        ? `Annuler la demande de changement de mot de passe pour ${nom} ?`
        : `Forcer ${nom} à changer son mot de passe à sa prochaine connexion ?`;

    if (!confirm(msg)) return;

    const fd = new FormData();
    fd.append('id', id);

    fetch('<?= BASE_URL ?>utilisateurs/force-change-password', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                const label = actif ? 'Annulé.' : `${res.nom} devra changer son mot de passe à la prochaine connexion.`;
                showToast(label, 'success');
                setTimeout(() => location.reload(), 1200);
            } else {
                showToast(res.message || 'Erreur inconnue.', 'danger');
            }
        })
        .catch(() => showToast('Erreur réseau.', 'danger'));
}

// ── Réinitialisation mot de passe ──────────────────────────────
let _resetUserId = null;

function ouvrirResetMdp(id, nom) {
    _resetUserId = id;
    document.getElementById('resetNomUser').textContent = nom;
    document.getElementById('resetMdpConfirm').style.display = '';
    document.getElementById('resetMdpResult').style.display = 'none';
    document.getElementById('btnResetMdpConfirm').style.display = '';
    document.getElementById('resetErrMsg').classList.add('d-none');
    new bootstrap.Modal(document.getElementById('modalResetMdp')).show();
}

function confirmerResetMdp() {
    if (!_resetUserId) return;
    const btn = document.getElementById('btnResetMdpConfirm');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Génération…';

    const fd = new FormData();
    fd.append('id', _resetUserId);
    fetch('<?= BASE_URL ?>utilisateurs/reset-password', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => {
            if (!d.success) {
                document.getElementById('resetErrMsg').textContent = d.message || 'Erreur inconnue.';
                document.getElementById('resetErrMsg').classList.remove('d-none');
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-key-fill me-1"></i>Générer un nouveau mot de passe';
                return;
            }
            document.getElementById('resetMdpConfirm').style.display = 'none';
            document.getElementById('resetResultNom').textContent = d.nom;
            document.getElementById('resetResultUsername').textContent = d.username;
            document.getElementById('resetResultPwd').textContent = d.password;
            document.getElementById('resetMdpResult').style.display = '';
            btn.style.display = 'none';
        })
        .catch(() => {
            document.getElementById('resetErrMsg').textContent = 'Erreur réseau.';
            document.getElementById('resetErrMsg').classList.remove('d-none');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-key-fill me-1"></i>Générer un nouveau mot de passe';
        });
}

function copierMdp() {
    const pwd = document.getElementById('resetResultPwd').textContent;
    navigator.clipboard.writeText(pwd).then(() => showToast('Mot de passe copié dans le presse-papiers !'));
}

// ── Changement de service utilisateur ─────────────────────────
let _csServicesMap = <?php
    $svcMap = [];
    foreach ($services as $s) $svcMap[(int)$s['id']] = htmlspecialchars($s['nom_service']);
    echo json_encode($svcMap);
?>;

function ouvrirChangerServiceUser(id, nom, serviceId) {
    document.getElementById('csUserId').value = id;
    document.getElementById('csUserSubtitle').textContent = nom;
    document.getElementById('csServiceActuel').textContent = _csServicesMap[serviceId] || 'Non assigné';
    document.getElementById('csNouveauService').value = '';
    document.getElementById('csErrMsg').classList.add('d-none');
    document.getElementById('csOkMsg').classList.add('d-none');
    const btn = document.getElementById('btnCsConfirm');
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-building-fill-gear me-1"></i>Appliquer la mutation';
    new bootstrap.Modal(document.getElementById('modalChangerServiceUser')).show();
}

function confirmerChangerService() {
    const id  = document.getElementById('csUserId').value;
    const svc = document.getElementById('csNouveauService').value;
    const err = document.getElementById('csErrMsg');
    const ok  = document.getElementById('csOkMsg');
    err.classList.add('d-none');
    ok.classList.add('d-none');

    if (!svc) {
        err.textContent = 'Veuillez sélectionner un service de destination.';
        err.classList.remove('d-none');
        return;
    }

    const btn = document.getElementById('btnCsConfirm');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Application…';

    const fd = new FormData();
    fd.append('id', id);
    fd.append('service_id', svc);
    fetch('<?= BASE_URL ?>utilisateurs/change-service', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => {
            if (!d.success) {
                err.textContent = d.message || 'Erreur inconnue.';
                err.classList.remove('d-none');
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-building-fill-gear me-1"></i>Appliquer la mutation';
                return;
            }
            ok.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i>' + d.message;
            ok.classList.remove('d-none');
            btn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Mutation appliquée';
            setTimeout(() => location.reload(), 1200);
        })
        .catch(() => {
            err.textContent = 'Erreur réseau.';
            err.classList.remove('d-none');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-building-fill-gear me-1"></i>Appliquer la mutation';
        });
}

// ── Modal Import Excel ─────────────────────────────────────────
function ouvrirModalImport() {
    const modal = new bootstrap.Modal(document.getElementById('modalImportExcel'));
    // Reset le formulaire à chaque ouverture
    document.getElementById('formImportUsers').reset();
    document.getElementById('dropFichierLabel').innerHTML = 'Cliquez ou glissez votre fichier ici';
    document.getElementById('btnSubmitImport').disabled = true;
    modal.show();
}

function afficherFichierImport() {
    const input = document.getElementById('inputFichierImport');
    const label = document.getElementById('dropFichierLabel');
    const btn   = document.getElementById('btnSubmitImport');
    if (input.files.length > 0) {
        const f = input.files[0];
        const sizeKb = (f.size / 1024).toFixed(0);
        label.innerHTML = `<i class="bi bi-file-earmark-check-fill text-success me-1"></i>
            <strong>${f.name}</strong> <small class="text-muted">(${sizeKb} Ko)</small>`;
        btn.disabled = false;
    }
}

// ── Drag & drop pour la zone d'upload ──────────────────────────
document.addEventListener('DOMContentLoaded', function() {
    const dz = document.getElementById('dropZoneImport');
    if (!dz) return;
    ['dragenter', 'dragover'].forEach(ev => {
        dz.addEventListener(ev, e => {
            e.preventDefault(); e.stopPropagation();
            dz.style.borderColor = '#3b82f6';
            dz.style.background = '#eff6ff';
        });
    });
    ['dragleave', 'drop'].forEach(ev => {
        dz.addEventListener(ev, e => {
            e.preventDefault(); e.stopPropagation();
            dz.style.borderColor = '#cbd5e1';
            dz.style.background = '#f8fafc';
        });
    });
    dz.addEventListener('drop', e => {
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            document.getElementById('inputFichierImport').files = files;
            afficherFichierImport();
        }
    });
});

// Spinner pendant la soumission de l'import
document.getElementById('formImportUsers')?.addEventListener('submit', function() {
    const btn = document.getElementById('btnSubmitImport');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Import en cours…';
});

// ── Filtres utilisateurs ───────────────────────────────────────
function filtrerUtilisateurs() {
    const search  = document.getElementById('userSearch').value.toLowerCase().trim();
    const service = document.getElementById('filterService').value;
    const role    = document.getElementById('filterRole').value;
    const statut  = document.getElementById('filterStatut').value;

    const rows = document.querySelectorAll('#tbodyUsers tr');
    let visible = 0;

    rows.forEach(tr => {
        const matchSearch  = !search  || tr.dataset.search.includes(search);
        const matchService = !service || tr.dataset.service === service;
        const matchRole    = !role    || tr.dataset.role === role;
        const matchStatut  = !statut  || tr.dataset.statut === statut;

        const show = matchSearch && matchService && matchRole && matchStatut;
        tr.style.display = show ? '' : 'none';
        if (show) visible++;
    });

    const total = rows.length;
    document.getElementById('userCount').textContent =
        visible === total ? `${total} utilisateur${total > 1 ? 's' : ''}`
                          : `${visible} / ${total} utilisateur${total > 1 ? 's' : ''}`;
}

function resetFiltres() {
    document.getElementById('userSearch').value   = '';
    document.getElementById('filterService').value = '';
    document.getElementById('filterRole').value    = '';
    document.getElementById('filterStatut').value  = '';
    filtrerUtilisateurs();
}

// Init au chargement
document.addEventListener('DOMContentLoaded', filtrerUtilisateurs);
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
