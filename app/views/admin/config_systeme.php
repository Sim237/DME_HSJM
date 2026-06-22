<?php
// app/views/admin/config_systeme.php
// Variables attendues : $configs (array groupé), $groupeLabels (array)
$configs      = $configs      ?? [];
$groupeLabels = $groupeLabels ?? [];
?>
<style>
.config-group-card {
    background:#fff;border-radius:16px;box-shadow:0 1px 4px rgba(0,0,0,.06);
    border:1px solid #e2e8f0;margin-bottom:24px;overflow:hidden;
}
.config-group-header {
    display:flex;align-items:center;gap:12px;padding:16px 20px;
    border-bottom:1px solid #f1f5f9;cursor:pointer;user-select:none;
    transition:background .15s;
}
.config-group-header:hover { background:#f8fafc; }
.config-group-icon {
    width:38px;height:38px;border-radius:10px;
    display:flex;align-items:center;justify-content:center;font-size:1.1rem;
    flex-shrink:0;
}
.config-group-body { padding:20px; }
.config-row {
    display:grid;grid-template-columns:240px 1fr 36px;gap:12px;align-items:center;
    padding:10px 0;border-bottom:1px solid #f1f5f9;
}
.config-row:last-child { border-bottom:none;padding-bottom:0; }
.config-label { font-size:.82rem;font-weight:700;color:#374151; }
.config-desc  { font-size:.7rem;color:#94a3b8;margin-top:2px; }
.config-input {
    border:1.5px solid #e2e8f0;border-radius:8px;padding:7px 12px;
    font-size:.83rem;width:100%;transition:.15s;background:#fff;
}
.config-input:focus { border-color:#3b82f6;outline:none;box-shadow:0 0 0 3px #bfdbfe44; }
.config-save-btn {
    width:32px;height:32px;border-radius:8px;border:1.5px solid #e2e8f0;
    background:#fff;color:#64748b;cursor:pointer;display:flex;align-items:center;
    justify-content:center;transition:.15s;font-size:.9rem;flex-shrink:0;
}
.config-save-btn:hover { background:#eff6ff;border-color:#3b82f6;color:#1e40af; }
.config-save-btn.saved  { background:#d1fae5;border-color:#6ee7b7;color:#059669; }
.save-groupe-btn {
    background:#1e40af;color:#fff;border:none;border-radius:10px;
    padding:9px 20px;font-size:.82rem;font-weight:700;cursor:pointer;
    display:flex;align-items:center;gap:6px;transition:.15s;margin-top:16px;
}
.save-groupe-btn:hover { background:#1d4ed8; }
.config-toggle { position:relative;display:inline-flex;align-items:center; }
.config-toggle input[type=checkbox] { width:42px;height:22px;accent-color:#1e40af;cursor:pointer; }
.toast-cfg {
    position:fixed;bottom:24px;right:24px;z-index:99999;
    background:#0f172a;color:#fff;padding:12px 20px;border-radius:12px;
    font-size:.82rem;font-weight:600;box-shadow:0 8px 24px rgba(0,0,0,.3);
    opacity:0;transform:translateY(10px);transition:.3s;pointer-events:none;
}
.toast-cfg.show { opacity:1;transform:translateY(0); }
</style>

<!-- TOPBAR -->
<div class="admin-topbar">
    <div class="admin-topbar-title">
        Configuration Système
        <small>Paramètres globaux de la plateforme SimCare+</small>
    </div>
    <div class="adm-topbar-actions">
        <div class="adm-clock-pill">
            <span class="adm-clock-dot"></span>
            <span class="adm-clock-time">--:--:--</span>
        </div>
        <button class="adm-topbtn adm-topbtn-ghost" onclick="toutSauvegarder()">
            <i class="bi bi-floppy-fill"></i> Tout sauvegarder
        </button>
    </div>
</div>

<div class="admin-page-content">

    <!-- Bannière info -->
    <div class="d-flex align-items-start gap-3 p-3 rounded-3 mb-4"
         style="background:#eff6ff;border:1px solid #bfdbfe;">
        <i class="bi bi-info-circle-fill text-primary mt-1"></i>
        <div style="font-size:.82rem;color:#1e40af">
            Ces paramètres configurent le comportement global de SimCare+. Chaque modification est enregistrée dans les journaux d'audit.
            Certains paramètres (couleurs, nom de l'établissement) sont pris en compte immédiatement.
        </div>
    </div>

    <?php foreach ($configs as $groupe => $items):
        $gl    = $groupeLabels[$groupe] ?? ['label' => ucfirst($groupe), 'icon' => 'bi-gear', 'color' => '#64748b'];
        $gid   = 'group-' . $groupe;
    ?>
    <div class="config-group-card">
        <!-- En-tête du groupe (accordéon) -->
        <div class="config-group-header" onclick="toggleGroupe('<?= $gid ?>')">
            <div class="config-group-icon"
                 style="background:<?= $gl['color'] ?>18;color:<?= $gl['color'] ?>">
                <i class="bi <?= $gl['icon'] ?>"></i>
            </div>
            <div class="flex-grow-1">
                <div style="font-weight:800;color:#0f172a;font-size:.9rem"><?= $gl['label'] ?></div>
                <div style="font-size:.72rem;color:#94a3b8"><?= count($items) ?> paramètre<?= count($items) > 1 ? 's' : '' ?></div>
            </div>
            <i class="bi bi-chevron-down" id="chev-<?= $gid ?>"
               style="color:#94a3b8;transition:.2s;font-size:.9rem"></i>
        </div>

        <!-- Corps -->
        <div class="config-group-body" id="<?= $gid ?>">
            <?php foreach ($items as $cfg): ?>
            <div class="config-row">
                <div>
                    <div class="config-label"><?= htmlspecialchars($cfg['libelle'] ?? $cfg['cle']) ?></div>
                    <?php if (!empty($cfg['description'])): ?>
                    <div class="config-desc"><?= htmlspecialchars($cfg['description']) ?></div>
                    <?php endif; ?>
                </div>
                <div>
                    <?php $val = htmlspecialchars($cfg['valeur'] ?? ''); ?>
                    <?php if ($cfg['type_valeur'] === 'boolean'): ?>
                    <div class="config-toggle">
                        <input type="checkbox" class="config-input-field"
                               data-cle="<?= $cfg['cle'] ?>"
                               <?= ($cfg['valeur'] == '1') ? 'checked' : '' ?>
                               onchange="sauvegarderParam(this.dataset.cle, this.checked ? '1' : '0', this)">
                    </div>
                    <?php elseif ($cfg['type_valeur'] === 'text'): ?>
                    <textarea class="config-input config-input-field" rows="2"
                              data-cle="<?= $cfg['cle'] ?>"
                              style="resize:vertical"><?= $val ?></textarea>
                    <?php elseif ($cfg['type_valeur'] === 'color'): ?>
                    <div class="d-flex align-items-center gap-2">
                        <input type="color" class="config-input-field"
                               data-cle="<?= $cfg['cle'] ?>"
                               value="<?= $val ?>"
                               style="width:50px;height:36px;padding:2px;border-radius:8px;border:1.5px solid #e2e8f0;cursor:pointer;">
                        <input type="text" class="config-input config-input-field"
                               data-cle="<?= $cfg['cle'] ?>-text"
                               value="<?= $val ?>"
                               style="width:110px;font-family:monospace"
                               maxlength="7"
                               oninput="syncColor(this)">
                    </div>
                    <?php else: ?>
                    <input type="<?= $cfg['type_valeur'] === 'integer' ? 'number' : ($cfg['type_valeur'] === 'email' ? 'email' : 'text') ?>"
                           class="config-input config-input-field"
                           data-cle="<?= $cfg['cle'] ?>"
                           value="<?= $val ?>">
                    <?php endif; ?>
                </div>
                <button class="config-save-btn" title="Sauvegarder ce paramètre"
                        onclick="sauvegarderDepuisBouton('<?= $cfg['cle'] ?>', this, '<?= $cfg['type_valeur'] ?>')">
                    <i class="bi bi-floppy"></i>
                </button>
            </div>
            <?php endforeach; ?>

            <button class="save-groupe-btn" onclick="sauvegarderGroupe('<?= $groupe ?>', this)">
                <i class="bi bi-floppy-fill"></i>
                Sauvegarder « <?= htmlspecialchars($gl['label']) ?> »
            </button>
        </div>
    </div>
    <?php endforeach; ?>

    <!-- ══════════════════════════════════════════════════════════
         CARTE SAUVEGARDE & RESTAURATION
         ══════════════════════════════════════════════════════════ -->
    <div class="config-group-card" style="border-color:#fca5a5;">
        <div class="config-group-header" onclick="toggleGroupe('group-backup')" style="background:#fff5f5;">
            <div class="config-group-icon" style="background:#fee2e220;color:#dc2626;">
                <i class="bi bi-database-fill-gear"></i>
            </div>
            <div class="flex-grow-1">
                <div style="font-weight:800;color:#0f172a;font-size:.9rem;">Sauvegarde &amp; Restauration</div>
                <div style="font-size:.72rem;color:#94a3b8;">Export et import complet de la base de données</div>
            </div>
            <i class="bi bi-chevron-down" id="chev-group-backup" style="color:#94a3b8;transition:.2s;font-size:.9rem;"></i>
        </div>
        <div class="config-group-body" id="group-backup">

            <!-- Export -->
            <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 0;border-bottom:1px solid #f1f5f9;flex-wrap:wrap;gap:12px;">
                <div>
                    <div class="config-label"><i class="bi bi-download me-1 text-primary"></i>Exporter la base de données</div>
                    <div class="config-desc">Télécharge un fichier .sql complet (toutes les tables + données). Conservez ce fichier en lieu sûr.</div>
                </div>
                <a href="<?= BASE_URL ?>admin/export-sql"
                   class="save-groupe-btn"
                   style="background:#1e40af;text-decoration:none;margin-top:0;">
                    <i class="bi bi-download"></i> Télécharger la sauvegarde
                </a>
            </div>

            <!-- Restauration -->
            <div style="padding:14px 0 0;">
                <div class="config-label"><i class="bi bi-upload me-1 text-danger"></i>Restaurer depuis un fichier .sql</div>
                <div class="config-desc mb-3">
                    Importe et exécute un fichier SQL exporté depuis cette application.
                    <strong style="color:#dc2626;">Cette opération remplace les données existantes — irréversible sans sauvegarde préalable.</strong>
                </div>

                <!-- Zone de dépôt -->
                <div id="restoreDropZone"
                     onclick="document.getElementById('sqlFileInput').click()"
                     ondragover="event.preventDefault();this.style.borderColor='#dc2626';this.style.background='#fff5f5';"
                     ondragleave="this.style.borderColor='#fca5a5';this.style.background='#fef2f2';"
                     ondrop="handleRestoreDrop(event)"
                     style="border:2px dashed #fca5a5;border-radius:12px;background:#fef2f2;
                            padding:28px 20px;text-align:center;cursor:pointer;transition:.2s;margin-bottom:14px;">
                    <i class="bi bi-file-earmark-code" style="font-size:2rem;color:#dc2626;display:block;margin-bottom:8px;"></i>
                    <div style="font-size:.85rem;font-weight:700;color:#dc2626;" id="restoreFileName">
                        Cliquer ou déposer un fichier .sql ici
                    </div>
                    <div style="font-size:.72rem;color:#94a3b8;margin-top:4px;">Max 100 Mo · Uniquement les fichiers exportés par cette application</div>
                    <input type="file" id="sqlFileInput" accept=".sql" style="display:none;" onchange="onRestoreFileSelected(this)">
                </div>

                <!-- Barre de progression -->
                <div id="restoreProgress" style="display:none;margin-bottom:12px;">
                    <div style="height:6px;background:#fee2e2;border-radius:4px;overflow:hidden;">
                        <div id="restoreBar" style="height:100%;width:0;background:linear-gradient(90deg,#dc2626,#f87171);border-radius:4px;transition:width .4s;"></div>
                    </div>
                    <div id="restoreProgressLabel" style="font-size:.72rem;color:#64748b;margin-top:4px;text-align:center;"></div>
                </div>

                <!-- Résultat -->
                <div id="restoreResult" style="display:none;padding:10px 14px;border-radius:10px;font-size:.82rem;font-weight:600;margin-bottom:12px;"></div>

                <!-- Bouton restaurer -->
                <button id="restoreBtn" onclick="demanderConfirmationRestauration()" disabled
                        style="background:#dc2626;color:#fff;border:none;border-radius:10px;
                               padding:10px 22px;font-size:.82rem;font-weight:700;cursor:pointer;
                               display:inline-flex;align-items:center;gap:7px;opacity:.45;transition:.2s;">
                    <i class="bi bi-arrow-counterclockwise"></i> Restaurer la base de données
                </button>
            </div>
        </div>
    </div>


    <!-- ══════════════════════════════════════════════════════════
         CARTE PLANIFICATION DES SAUVEGARDES AUTOMATIQUES
         ══════════════════════════════════════════════════════════ -->
    <div class="config-group-card" style="border-color:#a5b4fc;">
        <div class="config-group-header" onclick="toggleGroupe('group-autobackup')" style="background:#f5f3ff;">
            <div class="config-group-icon" style="background:#ede9fe;color:#7c3aed;">
                <i class="bi bi-clock-history"></i>
            </div>
            <div class="flex-grow-1">
                <div style="font-weight:800;color:#0f172a;font-size:.9rem;">Sauvegardes automatiques</div>
                <div style="font-size:.72rem;color:#94a3b8;">Planification, historique et téléchargement des sauvegardes</div>
            </div>
            <i class="bi bi-chevron-down" id="chev-group-autobackup" style="color:#94a3b8;transition:.2s;font-size:.9rem;"></i>
        </div>
        <div class="config-group-body" id="group-autobackup">

            <!-- Chargement -->
            <div id="backupLoading" style="text-align:center;padding:20px;color:#94a3b8;font-size:.82rem;">
                <span class="spinner-border spinner-border-sm me-2"></span>Chargement de la planification…
            </div>

            <div id="backupPanel" style="display:none;">

                <!-- Activation -->
                <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 0;border-bottom:1px solid #f1f5f9;">
                    <div>
                        <div class="config-label"><i class="bi bi-toggle-on me-1 text-purple"></i>Activer les sauvegardes automatiques</div>
                        <div class="config-desc">Le système créera des sauvegardes selon le planning défini ci-dessous.</div>
                    </div>
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;flex-shrink:0;">
                        <input type="checkbox" id="bkActif" style="width:42px;height:22px;accent-color:#7c3aed;cursor:pointer;">
                        <span id="bkActifLabel" style="font-size:.75rem;font-weight:700;color:#7c3aed;">Activé</span>
                    </label>
                </div>

                <!-- Fréquence & Heure -->
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;padding:16px 0;border-bottom:1px solid #f1f5f9;">
                    <div>
                        <label style="font-size:.75rem;font-weight:700;color:#374151;display:block;margin-bottom:6px;">Fréquence</label>
                        <select id="bkFrequence" class="config-input" onchange="bkAfficherOptions()">
                            <option value="QUOTIDIEN">Quotidien</option>
                            <option value="HEBDOMADAIRE">Hebdomadaire</option>
                            <option value="MENSUEL">Mensuel</option>
                        </select>
                    </div>
                    <div>
                        <label style="font-size:.75rem;font-weight:700;color:#374151;display:block;margin-bottom:6px;">Heure d'exécution</label>
                        <select id="bkHeure" class="config-input">
                            <?php for ($h = 0; $h < 24; $h++): ?>
                            <option value="<?= $h ?>"><?= str_pad($h, 2, '0', STR_PAD_LEFT) ?>h00</option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div>
                        <label style="font-size:.75rem;font-weight:700;color:#374151;display:block;margin-bottom:6px;">Rétention (nb de fichiers)</label>
                        <input type="number" id="bkRetention" class="config-input" min="1" max="30" value="7">
                    </div>
                </div>

                <!-- Jour semaine (hebdo) -->
                <div id="bkOptionSemaine" style="padding:12px 0;border-bottom:1px solid #f1f5f9;display:none;">
                    <label style="font-size:.75rem;font-weight:700;color:#374151;display:block;margin-bottom:8px;">Jour de la semaine</label>
                    <div style="display:flex;gap:8px;flex-wrap:wrap;">
                        <?php $jours = ['Lun','Mar','Mer','Jeu','Ven','Sam','Dim'];
                        foreach ($jours as $i => $j): ?>
                        <label style="cursor:pointer;">
                            <input type="radio" name="bkJourSem" value="<?= $i+1 ?>" <?= $i===0?'checked':'' ?> style="accent-color:#7c3aed;">
                            <span style="font-size:.8rem;font-weight:600;color:#374151;margin-left:4px;"><?= $j ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Jour mois (mensuel) -->
                <div id="bkOptionMois" style="padding:12px 0;border-bottom:1px solid #f1f5f9;display:none;">
                    <label style="font-size:.75rem;font-weight:700;color:#374151;display:block;margin-bottom:6px;">Jour du mois (1–28)</label>
                    <input type="number" id="bkJourMois" class="config-input" min="1" max="28" value="1" style="max-width:120px;">
                </div>

                <!-- Info dernière sauvegarde -->
                <div id="bkDerniereInfo" style="padding:12px 0;border-bottom:1px solid #f1f5f9;font-size:.8rem;color:#64748b;"></div>

                <!-- Boutons action -->
                <div style="display:flex;gap:10px;flex-wrap:wrap;padding:16px 0;border-bottom:1px solid #f1f5f9;">
                    <button onclick="bkSauvegarderPlan()" class="save-groupe-btn" style="background:#7c3aed;margin-top:0;">
                        <i class="bi bi-floppy-fill"></i> Enregistrer la planification
                    </button>
                    <button onclick="bkExecuterMaintenant()" class="save-groupe-btn" style="background:#0284c7;margin-top:0;" id="bkRunBtn">
                        <i class="bi bi-play-circle-fill"></i> Sauvegarder maintenant
                    </button>
                </div>

                <!-- Résultat exécution -->
                <div id="bkExecResult" style="display:none;padding:10px 14px;border-radius:10px;font-size:.82rem;font-weight:600;margin-top:12px;"></div>

                <!-- Liste des sauvegardes existantes -->
                <div style="margin-top:16px;">
                    <div style="font-weight:700;font-size:.85rem;color:#0f172a;margin-bottom:10px;">
                        <i class="bi bi-archive-fill me-1 text-purple"></i>Sauvegardes disponibles
                    </div>
                    <div id="bkListe">
                        <div style="color:#94a3b8;font-size:.8rem;text-align:center;padding:16px;">Aucune sauvegarde trouvée</div>
                    </div>
                </div>

                <!-- Instruction cron -->
                <div style="margin-top:16px;background:#f0fdf4;border:1px solid #86efac;border-radius:10px;padding:14px;">
                    <div style="font-size:.78rem;font-weight:700;color:#166534;margin-bottom:6px;">
                        <i class="bi bi-terminal-fill me-1"></i>Installation du cron (une seule fois sur le serveur)
                    </div>
                    <code style="font-size:.72rem;color:#166534;background:#dcfce7;padding:8px 12px;border-radius:6px;display:block;word-break:break-all;">
                        (crontab -l 2>/dev/null; echo "0 * * * * php /var/www/html/dme/scripts/cron_backup.php >> /var/www/html/dme/storage/dme_backup.log 2>&1") | crontab -
                    </code>
                    <div style="font-size:.7rem;color:#4ade80;margin-top:6px;">Cette commande s'exécute une fois et programme le vérificateur horaire.</div>
                </div>

            </div><!-- /backupPanel -->
        </div>
    </div>

</div><!-- /admin-page-content -->

<!-- Modal confirmation restauration -->
<div id="modalRestoreConfirm"
     style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(15,23,42,.6);
            backdrop-filter:blur(4px);align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:20px;width:100%;max-width:460px;margin:16px;
                box-shadow:0 24px 60px rgba(0,0,0,.25);overflow:hidden;animation:profSlideIn .2s ease;">
        <!-- En-tête danger -->
        <div style="background:linear-gradient(135deg,#dc2626,#ef4444);padding:18px 22px;display:flex;align-items:center;gap:12px;">
            <div style="width:42px;height:42px;border-radius:12px;background:rgba(255,255,255,.2);
                        display:flex;align-items:center;justify-content:center;font-size:1.3rem;color:#fff;flex-shrink:0;">
                <i class="bi bi-exclamation-triangle-fill"></i>
            </div>
            <div>
                <div style="font-size:.95rem;font-weight:800;color:#fff;">Confirmer la restauration</div>
                <div style="font-size:.72rem;color:rgba(255,255,255,.8);">Action irréversible sans sauvegarde préalable</div>
            </div>
        </div>
        <!-- Corps -->
        <div style="padding:22px 24px;">
            <p style="font-size:.85rem;color:#374151;line-height:1.6;margin:0 0 12px;">
                Vous êtes sur le point de <strong>remplacer toutes les données</strong> de la base
                <code style="background:#f1f5f9;padding:1px 6px;border-radius:5px;">dme_hospital</code>
                par le contenu du fichier :
            </p>
            <div id="confirmFileName"
                 style="background:#fef2f2;border:1px solid #fca5a5;border-radius:8px;
                        padding:8px 14px;font-size:.82rem;font-weight:700;color:#dc2626;margin-bottom:16px;">
            </div>
            <p style="font-size:.8rem;color:#6b7280;margin:0 0 20px;">
                ⚠️ Assurez-vous d'avoir exporté une sauvegarde récente avant de continuer.
                Cette opération ne peut pas être annulée.
            </p>
            <!-- Saisie de confirmation -->
            <label style="font-size:.75rem;font-weight:700;color:#374151;display:block;margin-bottom:6px;">
                Tapez <code>RESTAURER</code> pour confirmer :
            </label>
            <input id="confirmInput" type="text" autocomplete="off"
                   style="width:100%;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:9px;
                          font-size:.9rem;font-weight:700;outline:none;box-sizing:border-box;letter-spacing:.5px;"
                   onfocus="this.style.borderColor='#dc2626'"
                   onblur="this.style.borderColor='#e2e8f0'"
                   oninput="document.getElementById('confirmOkBtn').disabled=(this.value!=='RESTAURER');"
                   placeholder="RESTAURER">
            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:18px;">
                <button onclick="fermerModalRestore()"
                        style="padding:9px 20px;border:1.5px solid #e2e8f0;border-radius:10px;
                               background:#fff;color:#64748b;font-weight:600;font-size:.82rem;cursor:pointer;">
                    Annuler
                </button>
                <button id="confirmOkBtn" onclick="lancerRestauration()" disabled
                        style="padding:9px 22px;border:none;border-radius:10px;background:#dc2626;
                               color:#fff;font-weight:700;font-size:.82rem;cursor:pointer;opacity:.4;transition:.2s;">
                    <i class="bi bi-arrow-counterclockwise"></i> Confirmer la restauration
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Toast notification -->
<div id="toastConfig" class="toast-cfg"></div>

<script>
function toggleGroupe(id) {
    const body = document.getElementById(id);
    const chev = document.getElementById('chev-' + id);
    const open = body.style.display !== 'none';
    body.style.display = open ? 'none' : '';
    if (chev) chev.style.transform = open ? 'rotate(-90deg)' : '';
}

function getInputValue(cle, type) {
    if (type === 'boolean') {
        const el = document.querySelector(`[data-cle="${cle}"][type=checkbox]`);
        return el ? (el.checked ? '1' : '0') : '0';
    }
    if (type === 'color') {
        const el = document.querySelector(`[data-cle="${cle}"][type=color]`);
        return el ? el.value : '';
    }
    const el = document.querySelector(`[data-cle="${cle}"]:not([type=checkbox]):not([type=color])`);
    return el ? el.value.trim() : '';
}

function sauvegarderParam(cle, valeur, triggerEl) {
    const fd = new FormData();
    fd.append('cle', cle);
    fd.append('valeur', valeur);
    fetch('<?= BASE_URL ?>admin/config/save', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => {
            showToastCfg(d.success ? '✓ ' + (d.libelle || cle) + ' sauvegardé' : '✗ ' + (d.message || 'Erreur'), d.success);
        });
}

function sauvegarderDepuisBouton(cle, btn, type) {
    const valeur = getInputValue(cle, type);
    btn.classList.remove('saved');
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
    const fd = new FormData();
    fd.append('cle', cle);
    fd.append('valeur', valeur);
    fetch('<?= BASE_URL ?>admin/config/save', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => {
            btn.innerHTML = '<i class="bi bi-floppy"></i>';
            if (d.success) {
                btn.classList.add('saved');
                showToastCfg('✓ ' + (d.libelle || cle) + ' sauvegardé');
                setTimeout(() => btn.classList.remove('saved'), 2500);
            } else {
                showToastCfg('✗ ' + (d.message || 'Erreur'), false);
            }
        });
}

function sauvegarderGroupe(groupe, btn) {
    const body   = document.getElementById('group-' + groupe);
    const inputs = body.querySelectorAll('.config-input-field[data-cle]');
    const params = {};

    inputs.forEach(el => {
        if (el.dataset.cle.endsWith('-text')) return; // skip color text mirrors
        if (el.type === 'checkbox') {
            params[el.dataset.cle] = el.checked ? '1' : '0';
        } else if (el.type === 'color') {
            params[el.dataset.cle] = el.value;
        } else {
            params[el.dataset.cle] = el.value.trim();
        }
    });

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Sauvegarde…';

    const fd = new FormData();
    Object.entries(params).forEach(([k, v]) => fd.append('params[' + k + ']', v));
    fetch('<?= BASE_URL ?>admin/config/save-groupe', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => {
            btn.disabled = false;
            if (d.success) {
                btn.innerHTML = '<i class="bi bi-check-lg me-2"></i>Sauvegardé !';
                showToastCfg('✓ ' + d.nb + ' paramètre(s) sauvegardé(s)');
                setTimeout(() => {
                    btn.innerHTML = '<i class="bi bi-floppy-fill me-2"></i>Sauvegarder « ' + groupe + ' »';
                }, 2500);
            } else {
                btn.innerHTML = '<i class="bi bi-floppy-fill me-2"></i>Sauvegarder « ' + groupe + ' »';
                showToastCfg('✗ ' + (d.message || 'Erreur'), false);
            }
        });
}

function toutSauvegarder() {
    document.querySelectorAll('.save-groupe-btn').forEach(btn => btn.click());
}

function syncColor(textInput) {
    const cle = textInput.dataset.cle.replace('-text', '');
    const picker = document.querySelector(`[data-cle="${cle}"][type=color]`);
    if (picker && /^#[0-9a-fA-F]{6}$/.test(textInput.value)) {
        picker.value = textInput.value;
    }
}

function showToastCfg(msg, success = true) {
    const t = document.getElementById('toastConfig');
    t.textContent = msg;
    t.style.background = success ? '#0f172a' : '#dc2626';
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 2800);
}

// Horloge admin
(function clock() {
    const el = document.querySelector('.adm-clock-time');
    if (el) el.textContent = new Date().toLocaleTimeString('fr-FR');
    setTimeout(clock, 1000);
})();

/* ══════════════════════════════════════════════════════════════
   RESTAURATION SQL
   ══════════════════════════════════════════════════════════════ */
let _restoreFile = null;

function onRestoreFileSelected(input) {
    if (!input.files || !input.files[0]) return;
    _restoreFile = input.files[0];
    afficherFichierRestore(_restoreFile);
}

function handleRestoreDrop(e) {
    e.preventDefault();
    const zone = document.getElementById('restoreDropZone');
    zone.style.borderColor = '#fca5a5';
    zone.style.background  = '#fef2f2';
    const f = e.dataTransfer.files[0];
    if (!f) return;
    if (!f.name.toLowerCase().endsWith('.sql')) {
        afficherResultatRestore('Seuls les fichiers .sql sont acceptés.', false);
        return;
    }
    _restoreFile = f;
    afficherFichierRestore(f);
}

function afficherFichierRestore(f) {
    const label = document.getElementById('restoreFileName');
    const btn   = document.getElementById('restoreBtn');
    const sizeMo = (f.size / 1024 / 1024).toFixed(2);
    label.innerHTML = `<i class="bi bi-file-earmark-code me-1"></i>${f.name} <span style="font-weight:400;color:#6b7280;">(${sizeMo} Mo)</span>`;
    label.style.color = '#15803d';
    btn.disabled  = false;
    btn.style.opacity = '1';
    document.getElementById('restoreResult').style.display = 'none';
}

function demanderConfirmationRestauration() {
    if (!_restoreFile) return;
    document.getElementById('confirmFileName').textContent = _restoreFile.name;
    document.getElementById('confirmInput').value = '';
    document.getElementById('confirmOkBtn').disabled = true;
    document.getElementById('confirmOkBtn').style.opacity = '.4';
    const m = document.getElementById('modalRestoreConfirm');
    m.style.display = 'flex';
}

function fermerModalRestore() {
    document.getElementById('modalRestoreConfirm').style.display = 'none';
}

document.getElementById('modalRestoreConfirm').addEventListener('click', function(e) {
    if (e.target === this) fermerModalRestore();
});

// Activer le bouton de confirmation quand le texte est correct
document.getElementById('confirmOkBtn').addEventListener('click', function() {
    this.style.opacity = '1';
});
document.getElementById('confirmInput')?.addEventListener('input', function() {
    const ok = this.value === 'RESTAURER';
    const btn = document.getElementById('confirmOkBtn');
    btn.disabled     = !ok;
    btn.style.opacity = ok ? '1' : '.4';
});

function lancerRestauration() {
    if (!_restoreFile) return;
    fermerModalRestore();

    // Afficher la progression
    const progress = document.getElementById('restoreProgress');
    const bar      = document.getElementById('restoreBar');
    const label    = document.getElementById('restoreProgressLabel');
    const result   = document.getElementById('restoreResult');
    const btn      = document.getElementById('restoreBtn');

    result.style.display  = 'none';
    progress.style.display = 'block';
    btn.disabled = true;
    btn.style.opacity = '.5';

    // Animation indéterminée
    let pct = 0;
    const anim = setInterval(() => {
        pct = pct < 85 ? pct + (Math.random() * 4) : pct;
        bar.style.width = pct + '%';
        label.textContent = 'Exécution en cours… ' + Math.round(pct) + '%';
    }, 300);

    const fd = new FormData();
    fd.append('sql_file', _restoreFile);
    fd.append('_csrf_token', '<?= htmlspecialchars(CsrfService::getToken()) ?>');

    fetch('<?= BASE_URL ?>admin/restaurer-sql', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: fd
    })
        .then(r => r.json())
        .then(data => {
            clearInterval(anim);
            bar.style.width = '100%';
            label.textContent = data.success ? 'Terminé.' : 'Échec.';
            setTimeout(() => { progress.style.display = 'none'; }, 800);

            afficherResultatRestore(
                data.message + (data.executed ? ` (${data.executed} instructions)` : ''),
                data.success
            );

            btn.disabled     = !data.success ? false : true;
            btn.style.opacity = '1';
            _restoreFile = null;
            document.getElementById('restoreFileName').innerHTML = 'Cliquer ou déposer un fichier .sql ici';
            document.getElementById('restoreFileName').style.color = '#dc2626';
            document.getElementById('sqlFileInput').value = '';

            if (data.success) {
                showToastCfg('✓ Restauration réussie — ' + (data.filename || ''));
                // Recharger après 3 s pour refléter les nouvelles données
                setTimeout(() => window.location.reload(), 3000);
            }
        })
        .catch(err => {
            clearInterval(anim);
            progress.style.display = 'none';
            afficherResultatRestore('Erreur réseau ou serveur : ' + err.message, false);
            btn.disabled = false;
            btn.style.opacity = '1';
        });
}

function afficherResultatRestore(msg, ok) {
    const el = document.getElementById('restoreResult');
    el.style.display    = 'block';
    el.style.background = ok ? '#f0fdf4' : '#fef2f2';
    el.style.color      = ok ? '#166534' : '#991b1b';
    el.style.border     = ok ? '1px solid #86efac' : '1px solid #fca5a5';
    el.innerHTML = (ok ? '<i class="bi bi-check-circle-fill me-1"></i>' : '<i class="bi bi-x-circle-fill me-1"></i>') + msg;
}

// ── Sauvegardes automatiques ─────────────────────────────────────────────
function bkAfficherOptions() {
    const f = document.getElementById('bkFrequence').value;
    document.getElementById('bkOptionSemaine').style.display = f === 'HEBDOMADAIRE' ? '' : 'none';
    document.getElementById('bkOptionMois').style.display    = f === 'MENSUEL'      ? '' : 'none';
}

function bkCharger() {
    fetch('<?= BASE_URL ?>admin/backup/config')
        .then(r => r.json())
        .then(d => {
            if (!d.success) return;
            const p = d.plan;
            document.getElementById('bkActif').checked = p.actif == 1;
            document.getElementById('bkActifLabel').textContent = p.actif == 1 ? 'Activé' : 'Désactivé';
            document.getElementById('bkFrequence').value = p.frequence || 'QUOTIDIEN';
            document.getElementById('bkHeure').value = p.heure ?? 2;
            document.getElementById('bkRetention').value = p.retention || 7;
            if (p.jour_semaine) {
                const radio = document.querySelector(`input[name="bkJourSem"][value="${p.jour_semaine}"]`);
                if (radio) radio.checked = true;
            }
            if (p.jour_mois) document.getElementById('bkJourMois').value = p.jour_mois;
            bkAfficherOptions();
            const info = document.getElementById('bkDerniereInfo');
            info.textContent = p.derniere_sauvegarde
                ? 'Dernière sauvegarde : ' + p.derniere_sauvegarde
                : 'Aucune sauvegarde automatique effectuée.';
            document.getElementById('bkActif').onchange = function() {
                document.getElementById('bkActifLabel').textContent = this.checked ? 'Activé' : 'Désactivé';
            };
            bkAfficherListe(d.backups);
            document.getElementById('backupLoading').style.display = 'none';
            document.getElementById('backupPanel').style.display = '';
        })
        .catch(() => {
            document.getElementById('backupLoading').textContent = 'Erreur de chargement.';
        });
}

function bkAfficherListe(backups) {
    const el = document.getElementById('bkListe');
    if (!backups || !backups.length) {
        el.innerHTML = '<div style="color:#94a3b8;font-size:.8rem;text-align:center;padding:16px;">Aucune sauvegarde disponible</div>';
        return;
    }
    el.innerHTML = backups.map(b => `
        <div style="display:flex;align-items:center;justify-content:space-between;padding:9px 12px;
                    background:#f8fafc;border-radius:8px;margin-bottom:6px;flex-wrap:wrap;gap:8px;">
            <div>
                <div style="font-size:.8rem;font-weight:700;color:#0f172a;font-family:monospace;">${b.nom}</div>
                <div style="font-size:.7rem;color:#94a3b8;">${b.date} &bull; ${b.taille}</div>
            </div>
            <div style="display:flex;gap:6px;">
                <a href="<?= BASE_URL ?>admin/backup/telecharger?nom=${encodeURIComponent(b.nom)}"
                   style="padding:5px 12px;background:#1e40af;color:#fff;border-radius:7px;font-size:.75rem;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:4px;">
                    <i class="bi bi-download"></i> Télécharger
                </a>
                <button onclick="bkSupprimer('${b.nom}', this)"
                        style="padding:5px 12px;background:#fff;border:1.5px solid #fca5a5;color:#dc2626;border-radius:7px;font-size:.75rem;font-weight:700;cursor:pointer;">
                    <i class="bi bi-trash3"></i>
                </button>
            </div>
        </div>`).join('');
}

function bkSauvegarderPlan() {
    const fd = new FormData();
    fd.append('_csrf_token', '<?= \CsrfService::getToken() ?>');
    fd.append('actif', document.getElementById('bkActif').checked ? '1' : '0');
    fd.append('frequence', document.getElementById('bkFrequence').value);
    fd.append('heure', document.getElementById('bkHeure').value);
    fd.append('retention', document.getElementById('bkRetention').value);
    const semRadio = document.querySelector('input[name="bkJourSem"]:checked');
    fd.append('jour_semaine', semRadio ? semRadio.value : '1');
    fd.append('jour_mois', document.getElementById('bkJourMois').value);
    fetch('<?= BASE_URL ?>admin/backup/planifier', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => showToastCfg(d.success ? '✓ Planification enregistrée' : '✗ ' + d.message, d.success));
}

function bkExecuterMaintenant() {
    const btn = document.getElementById('bkRunBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>En cours…';
    const res = document.getElementById('bkExecResult');
    res.style.display = 'none';
    const fd = new FormData();
    fd.append('_csrf_token', '<?= \CsrfService::getToken() ?>');
    fetch('<?= BASE_URL ?>admin/backup/executer', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-play-circle-fill"></i> Sauvegarder maintenant';
            res.style.display = '';
            res.style.background = d.success ? '#d1fae5' : '#fee2e2';
            res.style.border = '1px solid ' + (d.success ? '#6ee7b7' : '#fca5a5');
            res.style.color = d.success ? '#065f46' : '#991b1b';
            res.innerHTML = d.success
                ? '<i class="bi bi-check-circle-fill me-1"></i>' + d.message + ' &bull; ' + d.fichier + ' (' + d.taille + ')'
                : '<i class="bi bi-x-circle-fill me-1"></i>' + d.message;
            if (d.success) bkCharger();
        })
        .catch(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-play-circle-fill"></i> Sauvegarder maintenant';
        });
}

function bkSupprimer(nom, btn) {
    if (!confirm('Supprimer cette sauvegarde ?')) return;
    const fd = new FormData();
    fd.append('_csrf_token', '<?= \CsrfService::getToken() ?>');
    fd.append('nom', nom);
    fetch('<?= BASE_URL ?>admin/backup/supprimer', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => { if (d.success) { bkCharger(); showToastCfg('✓ Sauvegarde supprimée'); } });
}

// Charger quand l'accordéon backup est ouvert
document.querySelector('[onclick="toggleGroupe(\'group-autobackup\')"]').addEventListener('click', function() {
    if (document.getElementById('backupPanel').style.display === 'none' &&
        document.getElementById('backupLoading').style.display !== 'none') {
        bkCharger();
    }
});
</script>
