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

// Initialisation des variables
$patient = $patient ?? [];
$consultation = $consultation_data ?? [];
$examens = $examens ?? []; // Pour éviter l'erreur si vide
$historique_examens = $historique_examens ?? [];
$type_consultation = $_GET['type'] ?? $consultation['type'] ?? 'EXTERNE';

include __DIR__ . '/../../layouts/header.php';
?>

<div class="container-fluid">
    <div class="row">


        <main class="col-12 px-md-4 consultation-form" style="margin-left: 0 !important;">
            <?php
                $numero = 4;
                include __DIR__ . '/progress_bar.php';
            ?>

            <form action="<?php echo BASE_URL; ?>consultation/sauvegarder" method="POST">

                <!-- === CHAMPS CACHÉS INDISPENSABLES === -->
                <input type="hidden" name="etape_actuelle" value="4">
                <input type="hidden" name="patient_id" value="<?php echo $patient['id']; ?>">
                <input type="hidden" name="type" value="<?php echo htmlspecialchars($type_consultation); ?>">
                <input type="hidden" name="consultation_id" id="hidden_consultation_id" value="<?php echo (int)($consultation_data['consultation_id'] ?? 0) ?: ''; ?>">
                <!-- ==================================== -->

                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-flask me-2"></i> EXAMENS PARACLINIQUES</h5>
                    </div>
                    <div class="card-body">

                        <!-- Champ texte libre pour les examens (Sauvegarde simple) -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">Notes sur les examens à demander</label>
                            <textarea class="form-control" name="examens_paracliniques" rows="4"
                                placeholder="Décrivez ici les examens à réaliser..."><?php echo htmlspecialchars($consultation['examens_paracliniques'] ?? ''); ?></textarea>
                        </div>

                        <!-- Gestion structurée des examens (Optionnel, nécessite JS) -->
                        <div class="mb-4 p-3 bg-light rounded border">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="mb-0"><i class="fas fa-list me-2"></i> Examens de Laboratoire</h6>
                                <div>
                                    <button type="button" class="btn btn-primary btn-sm me-2" data-bs-toggle="modal" data-bs-target="#modalDemandeExamen">
                                        <i class="fas fa-plus me-1"></i> Ajouter un Examen
                                    </button>
                                    <button type="button" class="btn btn-success btn-sm" onclick="envoyerAuLaboratoire()" id="btnEnvoyerLabo" style="display:none;">
                                        <i class="fas fa-paper-plane me-1"></i> Envoyer au Laboratoire
                                    </button>
                                </div>
                            </div>

                            <!-- Alertes disponibilité -->
                            <div id="alertesLabo"></div>

                            <div class="table-responsive">
                                <table class="table table-bordered mb-0 bg-white" id="tableExamens">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Examen</th>
                                            <th>Catégorie</th>
                                            <th>Prélèvement</th>
                                            <th>Délai</th>
                                            <th>Urgence</th>
                                            <th>Statut</th>
                                            <th class="text-center">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="listeExamens">
                                        <!-- Examens ajoutés dynamiquement -->
                                    </tbody>
                                </table>
                                <div id="emptyStateExamens" class="text-center text-muted py-3">
                                    <i class="fas fa-flask mb-2 fs-4 text-secondary"></i><br>
                                    Aucun examen ajouté.<br>
                                    <small>Utilisez le bouton "Ajouter un Examen" pour prescrire.</small>
                                </div>
                            </div>
                        </div>

                        <!-- ═══════════════════════════════════════════════════════════ -->
                        <!-- BLOC TESTS DE DIAGNOSTIC RAPIDE (TDR) — réalisés sur place -->
                        <!-- ═══════════════════════════════════════════════════════════ -->
                        <?php
                        // Charger les TDR existants si on édite une consultation
                        $tdrExistants = [];
                        if (!empty($consultation['tests_tdr'])) {
                            $decoded = json_decode($consultation['tests_tdr'], true);
                            if (is_array($decoded)) $tdrExistants = $decoded;
                        }
                        ?>
                        <div class="mb-4 p-3 rounded border" style="background:#fff7ed; border-color:#fdba74 !important;">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="mb-0">
                                    <i class="fas fa-vial me-2" style="color:#ea580c;"></i>
                                    Tests de Diagnostic Rapide (TDR) — réalisés sur place
                                </h6>
                                <button type="button" class="btn btn-sm" style="background:#ea580c;color:#fff;font-weight:600;"
                                        onclick="ouvrirAjoutTdr()">
                                    <i class="fas fa-plus me-1"></i> Ajouter un TDR
                                </button>
                            </div>

                            <!-- Champ caché : JSON sérialisé envoyé au backend -->
                            <input type="hidden" name="tests_tdr" id="testsTdrJson"
                                   value='<?= htmlspecialchars(json_encode($tdrExistants, JSON_UNESCAPED_UNICODE), ENT_QUOTES) ?>'>

                            <!-- Liste des TDR ajoutés -->
                            <div id="listeTdr">
                                <!-- rempli dynamiquement par JS au chargement + ajouts -->
                            </div>

                            <div id="emptyTdr" class="text-center text-muted py-3" style="display:<?= empty($tdrExistants) ? 'block' : 'none' ?>;">
                                <i class="fas fa-vial fs-4 text-secondary mb-2"></i><br>
                                <small>Aucun TDR enregistré pour cette consultation.<br>
                                Cliquez sur <strong>"Ajouter un TDR"</strong> pour entrer un résultat (paludisme, glycémie, COVID…).</small>
                            </div>
                        </div>

                        <!-- Modal d'ajout TDR -->
                        <div class="modal fade" id="modalAjoutTdr" tabindex="-1" aria-hidden="true">
                          <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content border-0 shadow-lg rounded-4">
                              <div class="modal-header border-0 pb-0"
                                   style="background:linear-gradient(135deg,#ea580c,#f97316);border-radius:16px 16px 0 0;">
                                <h5 class="modal-title text-white fw-bold">
                                  <i class="fas fa-vial me-2"></i>Ajouter un Test de Diagnostic Rapide
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                              </div>
                              <div class="modal-body p-4">
                                <div class="mb-3">
                                  <label class="form-label fw-semibold small">Type de TDR <span class="text-danger">*</span></label>
                                  <select id="tdrType" class="form-select">
                                    <option value="">— Sélectionner —</option>
                                    <option value="PALUDISME">Paludisme (TDR Pf/Pv)</option>
                                    <option value="GLYCEMIE">Glycémie capillaire</option>
                                    <option value="COVID">COVID-19 (Antigénique)</option>
                                    <option value="DENGUE">Dengue (NS1/IgM/IgG)</option>
                                    <option value="VIH">VIH (test rapide)</option>
                                    <option value="HEPATITE_B">Hépatite B (Ag HBs)</option>
                                    <option value="HEPATITE_C">Hépatite C (Ac VHC)</option>
                                    <option value="SYPHILIS">Syphilis (TPHA/RPR)</option>
                                    <option value="GROUPE_SANGUIN">Groupe sanguin</option>
                                    <option value="GROSSESSE">Grossesse (β-hCG urinaire)</option>
                                    <option value="STREPTO_A">Strepto A (gorge)</option>
                                    <option value="GRIPPE">Grippe A/B</option>
                                    <option value="UROLOGIE">Bandelette urinaire</option>
                                    <option value="HEMOGLOBINE">Hémoglobine (Hemocue)</option>
                                    <option value="AUTRE">Autre…</option>
                                  </select>
                                </div>
                                <div id="tdrTypeAutreWrap" class="mb-3" style="display:none;">
                                  <label class="form-label small">Précisez le type</label>
                                  <input type="text" id="tdrTypeAutre" class="form-control"
                                         placeholder="Ex: Légionellose">
                                </div>
                                <div class="row g-3">
                                  <div class="col-md-6">
                                    <label class="form-label fw-semibold small">Résultat <span class="text-danger">*</span></label>
                                    <select id="tdrResultat" class="form-select">
                                      <option value="">— Sélectionner —</option>
                                      <option value="POSITIF">🟥 Positif</option>
                                      <option value="NEGATIF">🟩 Négatif</option>
                                      <option value="DOUTEUX">🟨 Douteux / À refaire</option>
                                      <option value="VALEUR">📊 Valeur numérique</option>
                                    </select>
                                  </div>
                                  <div class="col-md-6">
                                    <label class="form-label small">Valeur (si numérique) / Détail</label>
                                    <input type="text" id="tdrValeur" class="form-control"
                                           placeholder="Ex: 1.45 g/L, Pf+, etc.">
                                  </div>
                                </div>
                                <div class="row g-3 mt-1">
                                  <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Heure</label>
                                    <input type="time" id="tdrHeure" class="form-control"
                                           value="<?= date('H:i') ?>">
                                  </div>
                                  <div class="col-md-8">
                                    <label class="form-label small">Note (optionnelle)</label>
                                    <input type="text" id="tdrNote" class="form-control"
                                           placeholder="Ex: à jeun, lecture à 15min…">
                                  </div>
                                </div>
                                <div id="tdrErreur" class="alert alert-danger mt-3 mb-0"
                                     style="display:none;font-size:.85rem;"></div>
                              </div>
                              <div class="modal-footer border-0 pt-0">
                                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Annuler</button>
                                <button type="button" id="btnAjouterTdr" class="btn rounded-pill px-4 fw-semibold text-white"
                                        style="background:linear-gradient(135deg,#ea580c,#f97316);border:none;"
                                        onclick="confirmerAjoutTdr()">
                                  <i class="fas fa-plus me-1"></i> Ajouter
                                </button>
                              </div>
                            </div>
                          </div>
                        </div>

                        <script>
                        // ════════════════════════════════════════════════════════════
                        //  GESTION DES TDR (Tests de Diagnostic Rapide)
                        // ════════════════════════════════════════════════════════════
                        let listeTdr = [];
                        const TDR_LABELS = {
                            'PALUDISME':'Paludisme', 'GLYCEMIE':'Glycémie', 'COVID':'COVID-19',
                            'DENGUE':'Dengue', 'VIH':'VIH', 'HEPATITE_B':'Hépatite B',
                            'HEPATITE_C':'Hépatite C', 'SYPHILIS':'Syphilis',
                            'GROUPE_SANGUIN':'Groupe sanguin', 'GROSSESSE':'Grossesse',
                            'STREPTO_A':'Strepto A', 'GRIPPE':'Grippe',
                            'UROLOGIE':'Bandelette urinaire', 'HEMOGLOBINE':'Hémoglobine',
                            'AUTRE':'Autre'
                        };
                        const RESULTAT_STYLES = {
                            'POSITIF':  {bg:'#fee2e2', fg:'#991b1b', icon:'🟥', label:'Positif'},
                            'NEGATIF':  {bg:'#dcfce7', fg:'#166534', icon:'🟩', label:'Négatif'},
                            'DOUTEUX':  {bg:'#fef3c7', fg:'#92400e', icon:'🟨', label:'Douteux'},
                            'VALEUR':   {bg:'#dbeafe', fg:'#1e40af', icon:'📊', label:'Valeur'},
                        };

                        function ouvrirAjoutTdr() {
                            // Bootstrap 5 requiert que le modal soit enfant direct du <body>.
                            // S'il est imbriqué dans le formulaire, le backdrop et les événements
                            // mouseenter/mouseleave des containers parents provoquent un clignotement.
                            const modalEl = document.getElementById('modalAjoutTdr');
                            if (modalEl && modalEl.parentElement !== document.body) {
                                document.body.appendChild(modalEl);
                            }

                            document.getElementById('tdrType').value = '';
                            document.getElementById('tdrTypeAutre').value = '';
                            document.getElementById('tdrTypeAutreWrap').style.display = 'none';
                            document.getElementById('tdrResultat').value = '';
                            document.getElementById('tdrValeur').value = '';
                            document.getElementById('tdrNote').value = '';
                            document.getElementById('tdrErreur').style.display = 'none';
                            const now = new Date();
                            document.getElementById('tdrHeure').value =
                                String(now.getHours()).padStart(2,'0') + ':' + String(now.getMinutes()).padStart(2,'0');
                            bootstrap.Modal.getOrCreateInstance(modalEl).show();
                        }

                        // Toggle "Autre" type
                        document.getElementById('tdrType')?.addEventListener('change', function() {
                            document.getElementById('tdrTypeAutreWrap').style.display =
                                this.value === 'AUTRE' ? 'block' : 'none';
                        });

                        function confirmerAjoutTdr() {
                            const type    = document.getElementById('tdrType').value;
                            const typeAutre = document.getElementById('tdrTypeAutre').value.trim();
                            const resultat = document.getElementById('tdrResultat').value;
                            const valeur   = document.getElementById('tdrValeur').value.trim();
                            const heure    = document.getElementById('tdrHeure').value;
                            const note     = document.getElementById('tdrNote').value.trim();
                            const errBox   = document.getElementById('tdrErreur');

                            if (!type) {
                                errBox.textContent = 'Veuillez sélectionner un type de TDR.';
                                errBox.style.display = 'block'; return;
                            }
                            if (type === 'AUTRE' && !typeAutre) {
                                errBox.textContent = 'Veuillez préciser le type de TDR.';
                                errBox.style.display = 'block'; return;
                            }
                            if (!resultat) {
                                errBox.textContent = 'Veuillez sélectionner le résultat.';
                                errBox.style.display = 'block'; return;
                            }
                            if (resultat === 'VALEUR' && !valeur) {
                                errBox.textContent = 'Précisez la valeur numérique.';
                                errBox.style.display = 'block'; return;
                            }

                            listeTdr.push({
                                type: type === 'AUTRE' ? typeAutre : type,
                                type_label: type === 'AUTRE' ? typeAutre : TDR_LABELS[type] || type,
                                resultat, valeur, heure, note,
                                date: new Date().toISOString().substring(0,10)
                            });
                            rafraichirListeTdr();
                            bootstrap.Modal.getInstance(document.getElementById('modalAjoutTdr'))?.hide();
                        }

                        function rafraichirListeTdr() {
                            const ctn = document.getElementById('listeTdr');
                            const empty = document.getElementById('emptyTdr');
                            const hidden = document.getElementById('testsTdrJson');

                            if (listeTdr.length === 0) {
                                ctn.innerHTML = '';
                                empty.style.display = 'block';
                                hidden.value = '[]';
                                return;
                            }
                            empty.style.display = 'none';

                            let html = '<div class="d-flex flex-column gap-2">';
                            listeTdr.forEach((t, idx) => {
                                const s = RESULTAT_STYLES[t.resultat] || {bg:'#f1f5f9', fg:'#64748b', icon:'❓', label:t.resultat};
                                const labelType = t.type_label || TDR_LABELS[t.type] || t.type;
                                html += `
                                <div class="d-flex align-items-center gap-3 p-2 rounded-3 border" style="background:#fff;border-color:#fcd9b6 !important;">
                                    <div style="font-size:1.4rem;">${s.icon}</div>
                                    <div class="flex-grow-1">
                                        <div class="fw-bold" style="color:#1e293b;">${escapeHtml(labelType)}</div>
                                        <div class="d-flex flex-wrap gap-2 mt-1" style="font-size:.78rem;">
                                            <span style="background:${s.bg};color:${s.fg};padding:2px 9px;border-radius:11px;font-weight:700;">
                                                ${s.label}${t.valeur ? ' — ' + escapeHtml(t.valeur) : ''}
                                            </span>
                                            ${t.heure ? '<span class="text-muted"><i class="bi bi-clock me-1"></i>' + escapeHtml(t.heure) + '</span>' : ''}
                                            ${t.note ? '<span class="text-muted fst-italic">— ' + escapeHtml(t.note) + '</span>' : ''}
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-2"
                                            onclick="supprimerTdr(${idx})" title="Retirer ce TDR">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>`;
                            });
                            html += '</div>';
                            ctn.innerHTML = html;

                            // Sérialiser pour envoi backend
                            hidden.value = JSON.stringify(listeTdr);
                        }

                        function supprimerTdr(idx) {
                            listeTdr.splice(idx, 1);
                            rafraichirListeTdr();
                        }

                        function escapeHtml(s) {
                            return String(s||'').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));
                        }

                        // Initialisation : charger les TDR existants si édition
                        (function initTdr() {
                            try {
                                const initial = document.getElementById('testsTdrJson').value;
                                if (initial && initial !== '[]') {
                                    const arr = JSON.parse(initial);
                                    if (Array.isArray(arr)) {
                                        listeTdr = arr;
                                        rafraichirListeTdr();
                                    }
                                }
                            } catch (e) { /* ignorer */ }
                        })();
                        </script>

                        <!-- Historique des examens du patient -->
                        <?php if(!empty($historique_examens)): ?>
                        <div class="mb-4">
                            <h6 class="border-bottom pb-2"><i class="fas fa-history me-2"></i> Historique des Examens</h6>
                            <div class="accordion" id="accordionHistorique">
                                <?php foreach($historique_examens as $index => $hist): ?>
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button"
                                                data-bs-toggle="collapse"
                                                data-bs-target="#collapse<?php echo $index; ?>">
                                            <?php echo htmlspecialchars($hist['nom_examen']); ?> -
                                            <?php echo date('d/m/Y', strtotime($hist['date_demande'])); ?>
                                        </button>
                                    </h2>
                                    <div id="collapse<?php echo $index; ?>"
                                         class="accordion-collapse collapse"
                                         data-bs-parent="#accordionHistorique">
                                        <div class="accordion-body">
                                            <?php echo $hist['resultat'] ? nl2br(htmlspecialchars($hist['resultat'])) : '<span class="text-muted">En attente</span>'; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- ═══════════════════════════════════════════════════════════ -->
                        <!-- BLOC IMAGERIE MÉDICALE / RADIOLOGIE                        -->
                        <!-- ═══════════════════════════════════════════════════════════ -->
                        <div class="mb-4 p-3 rounded border" style="background:#f0f7ff; border-color:#93c5fd !important;">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="mb-0 fw-bold text-primary">
                                    <i class="fas fa-x-ray me-2"></i> Demandes d'Imagerie / Radiologie
                                </h6>
                                <div class="d-flex gap-2">
                                    <button type="button"
                                            class="btn btn-primary btn-sm"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modalDemandeImagerie">
                                        <i class="fas fa-plus me-1"></i> Demande bilan Imagerie
                                    </button>
                                    <button type="button"
                                            class="btn btn-danger btn-sm fw-bold"
                                            id="btnEnvoyerRadio"
                                            onclick="envoyerEnRadiologie()"
                                            style="display:none;">
                                        <i class="fas fa-paper-plane me-1"></i> Envoyer en Radiologie
                                    </button>
                                </div>
                            </div>

                            <!-- Alertes -->
                            <div id="alertesRadio"></div>

                            <!-- Tableau prévisualisation imagerie -->
                            <div class="table-responsive">
                                <table class="table table-bordered mb-0 bg-white" id="tableImagerie">
                                    <thead class="table-primary">
                                        <tr>
                                            <th>Modalité</th>
                                            <th>Partie du corps</th>
                                            <th>Urgence</th>
                                            <th>Instructions</th>
                                            <th>Statut</th>
                                            <th class="text-center">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="listeImagerie">
                                        <!-- Rempli dynamiquement -->
                                    </tbody>
                                </table>
                                <div id="emptyStateImagerie" class="text-center text-muted py-3">
                                    <i class="fas fa-x-ray mb-2 fs-4 text-secondary"></i><br>
                                    Aucune demande d'imagerie.<br>
                                    <small>Cliquez sur "Demande bilan Imagerie" pour prescrire.</small>
                                </div>
                            </div>
                        </div>
                    </div><!-- /card-body -->
                </div><!-- /card -->

                <!-- Boutons de navigation -->
                <div class="card shadow-sm mb-5">
                    <div class="card-body d-flex justify-content-between">
                        <a href="<?php echo BASE_URL; ?>consultation/formulaire?patient_id=<?php echo $patient['id']; ?>&type=<?php echo $type_consultation; ?>&etape=3" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i> Précédent
                        </a>
                        <button type="submit" class="btn btn-info text-white px-4">
                            Suivant <i class="fas fa-arrow-right ms-2"></i>
                        </button>
                    </div>
                </div>
            </form>
        </main>
    </div>
</div>

<!-- Modal Demande d'Examen (Visuel pour l'instant) -->
<div class="modal fade" id="modalDemandeExamen" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="fas fa-flask me-2"></i> Demander un Examen</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formAjoutExamen" onsubmit="ajouterExamenToListe(event)">
                <div class="modal-body">
                    <div class="row g-3">

                        <!-- Filtres : catégorie + recherche -->
                        <div class="col-md-5">
                            <label class="form-label fw-semibold small text-uppercase text-muted">Catégorie</label>
                            <select class="form-select" id="categorieExamen" onchange="chargerExamensCategorie()">
                                <option value="">Toutes les catégories</option>
                            </select>
                        </div>
                        <div class="col-md-7">
                            <label class="form-label fw-semibold small text-uppercase text-muted">Recherche rapide</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                                <input type="text" class="form-control border-start-0" id="searchExamenCons"
                                       placeholder="Tapez pour filtrer les examens…"
                                       oninput="chargerExamensCategorie()" autocomplete="off">
                                <button type="button" class="btn btn-outline-secondary"
                                        onclick="document.getElementById('searchExamenCons').value='';chargerExamensCategorie()"
                                        title="Effacer">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Liste des examens avec cases à cocher -->
                        <div class="col-12">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <label class="form-label fw-semibold small text-uppercase text-muted mb-0">
                                    Sélectionnez un ou plusieurs examens <span class="text-danger">*</span>
                                </label>
                                <span id="consExamenCount" class="badge bg-info text-white" style="font-size:.7rem">0 examen(s)</span>
                            </div>
                            <div id="consExamensList"
                                 style="max-height:230px;overflow-y:auto;border:1.5px solid #dee2e6;border-radius:.5rem;padding:4px;">
                                <p class="text-muted small text-center py-4 mb-0">
                                    <i class="bi bi-hourglass-split me-1"></i>Chargement des examens…
                                </p>
                            </div>
                            <!-- Zone de sélection en cours : tags supprimables -->
                            <div id="consSelectionZone" style="display:none;margin-top:8px;
                                 background:#f0f9ff;border:1.5px solid #bae6fd;border-radius:.5rem;padding:8px 10px;">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <small class="fw-bold text-primary" style="font-size:.72rem;">
                                        <i class="bi bi-check2-circle me-1"></i>SÉLECTION EN COURS
                                    </small>
                                    <button type="button" class="btn btn-link btn-sm p-0 text-danger"
                                            style="font-size:.7rem;" onclick="consDeselectAll()">
                                        Tout effacer
                                    </button>
                                </div>
                                <div id="consSelectionTags" class="d-flex flex-wrap gap-1"></div>
                            </div>
                        </div>

                        <!-- Options transversales -->
                        <div class="col-md-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="urgent" id="urgent">
                                <label class="form-check-label text-danger fw-bold" for="urgent">
                                    <i class="fas fa-exclamation-triangle"></i> URGENT
                                </label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="a_jeun" id="a_jeun">
                                <label class="form-check-label" for="a_jeun">
                                    <i class="fas fa-clock"></i> À jeun requis
                                </label>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold small text-uppercase text-muted">Instructions particulières</label>
                            <textarea name="instructions" class="form-control" rows="2"
                                      placeholder="Instructions spéciales pour le laboratoire…"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-info fw-bold">
                        <i class="bi bi-plus-lg me-1"></i>Ajouter les examens sélectionnés
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════ -->
<!-- MODAL DEMANDE D'IMAGERIE / RADIOLOGIE                      -->
<!-- ═══════════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalDemandeImagerie" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header text-white" style="background:#1d4ed8;">
                <h5 class="modal-title"><i class="fas fa-x-ray me-2"></i> Demande d'Imagerie Médicale</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formAjoutImagerie" onsubmit="ajouterImagerieToListe(event)">
                <div class="modal-body">

                    <div class="row g-3">
                        <!-- Modalité -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Modalité <span class="text-danger">*</span></label>
                            <select class="form-select" id="modaliteImagerie" name="modalite" required onchange="updatePartieCorp()">
                                <option value="">— Choisir la modalité —</option>
                                <option value="radiographie">🩻 Radiographie</option>
                                <option value="echographie">🔊 Échographie</option>
                                <option value="scanner">💻 Scanner (TDM)</option>
                                <option value="irm">🧲 IRM</option>
                                <option value="mammographie">🔬 Mammographie</option>
                                <option value="autre">📋 Autre</option>
                            </select>
                        </div>

                        <!-- Partie du corps -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Partie du corps / Région <span class="text-danger">*</span></label>
                            <select class="form-select" id="partieCorpsSelect" name="partie_corps" required onchange="updateExamens()">
                                <option value="">— Sélectionner d'abord la modalité —</option>
                            </select>
                        </div>

                        <!-- Examen spécifique -->
                        <div class="col-12">
                            <label class="form-label fw-bold">Examen <span class="text-danger">*</span></label>
                            <select class="form-select" id="examenSelect" name="examen" required>
                                <option value="">— Sélectionner d'abord la partie du corps —</option>
                            </select>
                        </div>

                        <!-- Urgence -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Niveau d'urgence</label>
                            <select class="form-select" name="urgence" id="urgenceImagerie">
                                <option value="NORMAL">Normal</option>
                                <option value="URGENT">🔴 URGENT</option>
                                <option value="TRES_URGENT">🚨 TRÈS URGENT</option>
                            </select>
                        </div>

                        <!-- Incidence -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Incidence</label>
                            <select class="form-select" name="incidence">
                                <option value="">Non précisée</option>
                                <option value="Face">Face</option>
                                <option value="Profil">Profil</option>
                                <option value="Face + Profil">Face + Profil</option>
                                <option value="Oblique">Oblique</option>
                            </select>
                        </div>

                        <!-- Injection de contraste -->
                        <div class="col-md-4 d-flex align-items-end">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="avecContraste" name="avec_contraste" value="1">
                                <label class="form-check-label fw-bold" for="avecContraste">
                                    💉 Avec produit de contraste
                                </label>
                            </div>
                        </div>

                        <!-- Indication clinique -->
                        <div class="col-12">
                            <label class="form-label fw-bold">Indication clinique <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="indication" id="indicationImagerie" rows="2"
                                      required
                                      placeholder="Ex : Douleur thoracique, suspicion de fracture, contrôle post-opératoire..."></textarea>
                        </div>

                        <!-- Instructions -->
                        <div class="col-12">
                            <label class="form-label fw-bold">Instructions particulières</label>
                            <textarea class="form-control" name="instructions" rows="2"
                                      placeholder="Informations complémentaires pour le radiologue..."></textarea>
                        </div>
                    </div>

                    <!-- Aperçu info -->
                    <div id="infoImagerie" class="mt-3"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i> Ajouter à la liste
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
/* ─── LABO (code existant conservé) ─── */
let examensLaboratoire = [];
let examensDisponibles = [];
// Set persistant des IDs d'examens cochés — survit aux re-rendus de la liste filtrée
const consSelectedIds = new Set();

document.addEventListener('DOMContentLoaded', function() {
    chargerExamensDisponibles();
});

function chargerExamensDisponibles() {
    fetch('<?= BASE_URL ?>laboratoire/examens-disponibles')
        .then(r => r.json())
        .then(examens => {
            examensDisponibles = examens;
            // Remplir dynamiquement le select des catégories
            const catSelect = document.getElementById('categorieExamen');
            const categories = [...new Set(examens.map(e => e.categorie).filter(Boolean))].sort();
            categories.forEach(cat => {
                const opt = document.createElement('option');
                opt.value = cat;
                opt.textContent = cat;
                catSelect.appendChild(opt);
            });
            chargerExamensCategorie();
        })
        .catch(console.error);
}

function chargerExamensCategorie() {
    const cat    = document.getElementById('categorieExamen').value;
    const search = (document.getElementById('searchExamenCons')?.value || '').toLowerCase().trim();
    const list   = document.getElementById('consExamensList');

    let filtered = cat ? examensDisponibles.filter(e => e.categorie === cat) : examensDisponibles;
    if (search) filtered = filtered.filter(e => e.nom.toLowerCase().includes(search));

    const countBadge = document.getElementById('consExamenCount');
    if (countBadge) countBadge.textContent = filtered.length + ' examen' + (filtered.length > 1 ? 's' : '');

    if (filtered.length === 0) {
        list.innerHTML = '<p class="text-muted small text-center py-4 mb-0"><i class="bi bi-search me-1"></i>Aucun examen trouvé</p>';
        consMajCompteurSelection();
        return;
    }

    list.innerHTML = filtered.map(ex => {
        const dataStr  = JSON.stringify(ex).replace(/"/g, '&quot;');
        const aJeun    = ex.a_jeun_requis
            ? '<span class="badge bg-warning text-dark ms-1" style="font-size:.6rem">⏰ À jeun</span>' : '';
        // Restaurer l'état coché depuis le Set persistant (survit au re-rendu)
        const isChecked = consSelectedIds.has(String(ex.id));
        const rowBg     = isChecked ? 'background:#eff6ff;' : '';
        return `
        <div class="cons-exam-row d-flex align-items-start gap-2 px-2 py-2"
             style="border-bottom:1px solid #f1f5f9;cursor:pointer;transition:background .12s;border-radius:6px;${rowBg}"
             onclick="consToggleRow(this)">
            <input class="form-check-input flex-shrink-0 cons-exam-check" type="checkbox"
                   id="consEx_${ex.id}" value="${ex.id}"
                   data-examen="${dataStr}"
                   style="margin-top:3px;cursor:pointer"
                   ${isChecked ? 'checked' : ''}
                   onclick="event.stopPropagation();consToggleSelection(this);consRowHighlight(this.closest('.cons-exam-row'),this.checked);consMajCompteurSelection()">
            <label class="mb-0 w-100" for="consEx_${ex.id}" style="cursor:pointer;pointer-events:none">
                <div class="fw-semibold" style="font-size:.85rem">${ex.nom}</div>
                <div class="text-muted d-flex align-items-center flex-wrap gap-1" style="font-size:.72rem">
                    <span class="badge bg-secondary" style="font-size:.62rem">${ex.categorie || '—'}</span>
                    <span>${ex.type_prelevement || ''}</span>
                    <span>•</span>
                    <span>Délai : <strong>${ex.delai_rendu_heures}h</strong></span>
                    ${aJeun}
                </div>
            </label>
        </div>`;
    }).join('');
    consMajCompteurSelection();
}

// Met à jour le Set persistant quand une checkbox est cochée/décochée
function consToggleSelection(cb) {
    if (cb.checked) {
        consSelectedIds.add(String(cb.value));
    } else {
        consSelectedIds.delete(String(cb.value));
    }
}

function consToggleRow(row) {
    const cb = row.querySelector('.cons-exam-check');
    if (!cb) return;
    cb.checked = !cb.checked;
    consToggleSelection(cb);
    consRowHighlight(row, cb.checked);
    consMajCompteurSelection();
}

function consRowHighlight(row, checked) {
    row.style.background = checked ? '#eff6ff' : '';
}

function consMajCompteurSelection() {
    const n   = consSelectedIds.size;
    const lbl = document.getElementById('consSelectedCount');
    if (lbl) lbl.textContent = n > 0 ? `✔ ${n} examen${n > 1 ? 's' : ''} sélectionné${n > 1 ? 's' : ''}` : '';

    // Zone de tags : affiche chaque examen sélectionné avec un bouton ×
    const zone = document.getElementById('consSelectionZone');
    const tags = document.getElementById('consSelectionTags');
    if (!zone || !tags) return;

    if (n === 0) {
        zone.style.display = 'none';
        tags.innerHTML = '';
        return;
    }
    zone.style.display = 'block';
    tags.innerHTML = '';
    consSelectedIds.forEach(id => {
        const ex = examensDisponibles.find(e => String(e.id) === String(id));
        if (!ex) return;
        const tag = document.createElement('span');
        tag.style.cssText = 'display:inline-flex;align-items:center;gap:4px;background:#0ea5e9;color:#fff;'
                          + 'padding:2px 8px 2px 10px;border-radius:20px;font-size:.72rem;font-weight:600;';
        tag.innerHTML = `${ex.nom}
            <button type="button"
                    onclick="consDeselectionnerId('${id}')"
                    style="background:none;border:none;color:#fff;padding:0;margin-left:2px;
                           font-size:.8rem;line-height:1;cursor:pointer;opacity:.85;"
                    title="Retirer">✕</button>`;
        tags.appendChild(tag);
    });
}

// Désélectionner un examen depuis son tag (sans rebuild de la liste)
function consDeselectionnerId(id) {
    consSelectedIds.delete(String(id));
    // Décocher la checkbox si elle est visible dans la liste filtrée
    const cb = document.getElementById('consEx_' + id);
    if (cb) {
        cb.checked = false;
        consRowHighlight(cb.closest('.cons-exam-row'), false);
    }
    consMajCompteurSelection();
}

function consDeselectAll() {
    document.querySelectorAll('.cons-exam-check:checked').forEach(cb => {
        cb.checked = false;
        consRowHighlight(cb.closest('.cons-exam-row'), false);
    });
    consSelectedIds.clear();
    consMajCompteurSelection();
}

function ajouterExamenToListe(event) {
    event.preventDefault();
    const form = event.target;

    // Utiliser le Set persistant (source de vérité) — les checkboxes DOM
    // peuvent être filtrées/masquées, mais le Set contient TOUS les IDs cochés
    if (consSelectedIds.size === 0) {
        const list = document.getElementById('consExamensList');
        list.style.border = '1.5px solid #dc2626';
        list.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        setTimeout(() => { list.style.border = '1.5px solid #dee2e6'; }, 1800);
        return;
    }
    const isUrgent     = form.urgent.checked;
    const isAJeun      = form.a_jeun.checked;
    const instructions = form.instructions.value;

    // Récupérer les données complètes depuis examensDisponibles par ID
    consSelectedIds.forEach(id => {
        const ex = examensDisponibles.find(e => String(e.id) === String(id));
        if (ex && !examensLaboratoire.find(x => x.id === ex.id)) {
            examensLaboratoire.push({
                id: ex.id, nom: ex.nom, categorie: ex.categorie,
                type_prelevement: ex.type_prelevement, delai_rendu_heures: ex.delai_rendu_heures,
                urgent: isUrgent, a_jeun: isAJeun, instructions
            });
        }
    });

    afficherListeExamens();
    bootstrap.Modal.getInstance(document.getElementById('modalDemandeExamen')).hide();
    form.reset();
    // Vider le Set après confirmation — réinitialise pour la prochaine ouverture
    consSelectedIds.clear();
    consMajCompteurSelection();
}

// Reset du modal à l'ouverture
document.getElementById('modalDemandeExamen')?.addEventListener('show.bs.modal', () => {
    document.getElementById('searchExamenCons').value = '';
    document.getElementById('categorieExamen').value  = '';
    consSelectedIds.clear();
    chargerExamensCategorie();
});

function afficherListeExamens() {
    const tbody = document.getElementById('listeExamens');
    const emptyState = document.getElementById('emptyStateExamens');
    const btnEnvoyer = document.getElementById('btnEnvoyerLabo');
    if (examensLaboratoire.length === 0) {
        tbody.innerHTML = ''; emptyState.style.display = 'block'; btnEnvoyer.style.display = 'none'; return;
    }
    emptyState.style.display = 'none';
    btnEnvoyer.style.display = 'inline-block';
    tbody.innerHTML = examensLaboratoire.map((examen, index) => `
        <tr>
            <td class="fw-bold">${examen.nom}</td>
            <td><span class="badge bg-secondary">${examen.categorie}</span></td>
            <td>${examen.type_prelevement}</td>
            <td>${examen.delai_rendu_heures}h</td>
            <td>${examen.urgent ? '<span class="badge bg-danger">URGENT</span>' : '<span class="badge bg-success">Normal</span>'}${examen.a_jeun ? '<br><small class="text-warning">À jeun</small>' : ''}</td>
            <td><span class="badge bg-warning">En attente</span></td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-danger" onclick="retirerExamen(${index})"><i class="fas fa-trash"></i></button>
                <input type="hidden" name="examens[${index}][examen_id]" value="${examen.id}">
                <input type="hidden" name="examens[${index}][urgent]" value="${examen.urgent ? 1 : 0}">
                <input type="hidden" name="examens[${index}][a_jeun]" value="${examen.a_jeun ? 1 : 0}">
                <input type="hidden" name="examens[${index}][instructions]" value="${examen.instructions ?? ''}">
            </td>
        </tr>
    `).join('');
}

function retirerExamen(index) {
    examensLaboratoire.splice(index, 1);
    afficherListeExamens();
}

function envoyerAuLaboratoire() {
    if (examensLaboratoire.length === 0) { alert('Veuillez d\'abord ajouter au moins un examen.'); return; }
    const patientId       = document.querySelector('input[name="patient_id"]').value;
    const consultationId  = document.getElementById('hidden_consultation_id')?.value || null;
    const btn = document.getElementById('btnEnvoyerLabo');
    btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Envoi...';
    fetch('<?= BASE_URL ?>laboratoire/creer-demande-consultation', {
        method: 'POST', headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ patient_id: patientId, consultation_id: consultationId || null, examens: examensLaboratoire })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showToast('✅ Demande labo envoyée avec succès !', 'success');
            examensLaboratoire = []; afficherListeExamens();
            btn.innerHTML = '<i class="fas fa-check me-1"></i> Envoyé';
        } else {
            alert('❌ Erreur : ' + data.message);
            btn.disabled = false; btn.innerHTML = '<i class="fas fa-paper-plane me-1"></i> Envoyer au Laboratoire';
        }
    });
}

/* ─── IMAGERIE ─── */
let demandesImagerie = [];

// Parties du corps par modalité
const partiesCorps = {
    radiographie: ['Thorax','Abdomen','Crâne','Rachis cervical','Rachis dorsal','Rachis lombaire','Bassin','Clavicule droite','Clavicule gauche','Épaule droite','Épaule gauche','Bras droit','Bras gauche','Avant-bras droit','Avant-bras gauche','Main droite','Main gauche','Cuisse droite','Cuisse gauche','Jambe droite','Jambe gauche','Pied droit','Pied gauche','Cheville droite','Cheville gauche','Genou droit','Genou gauche'],
    echographie: ['Abdomen + Pelvis','Abdomen total','FAST (Urgences / Trauma)','Pelvis','Obstétricale','Thyroïde','Sein droit','Sein gauche','Rein droit','Rein gauche','Foie','Vésicule biliaire','Rate','Prostate','Col utérin','Tendons','Paroi abdominale'],
    scanner: ['Crâne','Thorax','Abdomen-Pelvis','Thorax-Abdomen-Pelvis','Rachis cervical','Rachis dorsal','Rachis lombaire','Bassin','Membre supérieur droit','Membre supérieur gauche','Membre inférieur droit','Membre inférieur gauche','Corps entier'],
    irm: ['Crâne','Rachis cervical','Rachis dorsal','Rachis lombaire','Genou droit','Genou gauche','Épaule droite','Épaule gauche','Hanche droite','Hanche gauche','Poignet droit','Poignet gauche','Pelvis','Sein droit','Sein gauche','Abdomen','Corps entier'],
    mammographie: ['Sein droit','Sein gauche','Bilatéral'],
    autre: ['À préciser dans les instructions']
};

// Examens spécifiques par modalité et partie du corps
const examensParPartie = {
    radiographie: {
        'Thorax': ['Radiographie thoracique de face','Radiographie thoracique de profil','Radiographie thoracique face + profil'],
        'Abdomen': ['Abdomen sans préparation (ASP) debout','ASP couché','ASP debout + couché'],
        'Crâne': ['Radiographie du crâne de face','Radiographie du crâne de profil','Radiographie du crâne face + profil'],
        'Rachis cervical': ['Rachis cervical de face','Rachis cervical de profil','Rachis cervical face + profil + obliques','Rachis cervical dynamique (flexion/extension)'],
        'Rachis dorsal': ['Rachis dorsal de face','Rachis dorsal de profil','Rachis dorsal face + profil'],
        'Rachis lombaire': ['Rachis lombaire de face','Rachis lombaire de profil','Rachis lombaire face + profil + obliques','Rachis lombo-sacré'],
        'Bassin': ['Bassin de face','Hanche de face','Cotyle de face'],
        'Clavicule droite': ['Radiographie clavicule droite de face','Radiographie clavicule droite face + oblique'],
        'Clavicule gauche': ['Radiographie clavicule gauche de face','Radiographie clavicule gauche face + oblique'],
        'Épaule droite': ['Épaule droite de face','Épaule droite de profil','Épaule droite face + profil'],
        'Épaule gauche': ['Épaule gauche de face','Épaule gauche de profil','Épaule gauche face + profil'],
        'Bras droit': ['Humérus droit de face','Humérus droit de profil','Humérus droit face + profil'],
        'Bras gauche': ['Humérus gauche de face','Humérus gauche de profil','Humérus gauche face + profil'],
        'Avant-bras droit': ['Avant-bras droit de face','Avant-bras droit de profil','Avant-bras droit face + profil'],
        'Avant-bras gauche': ['Avant-bras gauche de face','Avant-bras gauche de profil','Avant-bras gauche face + profil'],
        'Main droite': ['Main droite de face','Poignet droit de face','Poignet droit face + profil','Main droite face + profil'],
        'Main gauche': ['Main gauche de face','Poignet gauche de face','Poignet gauche face + profil','Main gauche face + profil'],
        'Cuisse droite': ['Fémur droit de face','Fémur droit de profil','Fémur droit face + profil'],
        'Cuisse gauche': ['Fémur gauche de face','Fémur gauche de profil','Fémur gauche face + profil'],
        'Jambe droite': ['Jambe droite de face','Jambe droite de profil','Jambe droite face + profil'],
        'Jambe gauche': ['Jambe gauche de face','Jambe gauche de profil','Jambe gauche face + profil'],
        'Pied droit': ['Pied droit de face','Pied droit de profil','Pied droit face + profil + oblique'],
        'Pied gauche': ['Pied gauche de face','Pied gauche de profil','Pied gauche face + profil + oblique'],
        'Cheville droite': ['Cheville droite de face','Cheville droite de profil','Cheville droite face + profil'],
        'Cheville gauche': ['Cheville gauche de face','Cheville gauche de profil','Cheville gauche face + profil'],
        'Genou droit': ['Genou droit de face','Genou droit de profil','Genou droit face + profil','Genou droit en charge (schuss)'],
        'Genou gauche': ['Genou gauche de face','Genou gauche de profil','Genou gauche face + profil','Genou gauche en charge (schuss)'],
    },
    echographie: {
        'Abdomen + Pelvis': ['Échographie abdomino-pelvienne','Échographie abdomino-pelvienne complète'],
        'Abdomen total': ['Échographie abdominale complète','Échographie hépato-biliaire','Échographie rénale bilatérale','Échographie hépato-bilio-pancréatique'],
        'FAST (Urgences / Trauma)': ['Échographie FAST (Focused Assessment with Sonography in Trauma)'],
        'Pelvis': ['Échographie pelvienne sus-pubienne','Échographie pelvienne endovaginale','Échographie utéro-ovarienne'],
        'Obstétricale': ['Échographie obstétricale 1er trimestre (datation)','Échographie obstétricale 2ème trimestre (morphologie)','Échographie obstétricale 3ème trimestre (croissance)','Monitoring / Bien-être fœtal'],
        'Thyroïde': ['Échographie thyroïdienne','Échographie thyroïde + parathyroïdes','Échographie thyroïde + ganglions cervicaux'],
        'Sein droit': ['Échographie mammaire droite','Échographie mammaire droite + ganglions axillaires'],
        'Sein gauche': ['Échographie mammaire gauche','Échographie mammaire gauche + ganglions axillaires'],
        'Rein droit': ['Échographie rénale droite','Échographie rein droit + voies urinaires'],
        'Rein gauche': ['Échographie rénale gauche','Échographie rein gauche + voies urinaires'],
        'Foie': ['Échographie hépatique','Échographie hépatique + Doppler portal','Fibroscan (élastométrie hépatique)'],
        'Vésicule biliaire': ['Échographie vésicule biliaire','Échographie bilio-pancréatique'],
        'Rate': ['Échographie splénique','Échographie splénique + Doppler'],
        'Prostate': ['Échographie prostatique sus-pubienne','Échographie prostatique endorectale (TRUS)'],
        'Col utérin': ['Échographie col utérin (longueur cervicale)','Échographie cervico-vaginale'],
        'Tendons': ['Échographie tendineuse ciblée','Échographie Doppler tendineuse'],
        'Paroi abdominale': ['Échographie pariétale abdominale','Échographie hernie inguinale','Échographie hernie ombilicale'],
    },
    scanner: {
        'Crâne': ['Scanner cérébral sans injection','Scanner cérébral avec injection','Scanner cérébral sans + avec injection','Angio-scanner cérébral (AVC/anévrisme)'],
        'Thorax': ['Scanner thoracique sans injection','Scanner thoracique avec injection','Angio-scanner thoracique (embolie pulmonaire)','Scanner thoracique haute résolution (HRCT)'],
        'Abdomen-Pelvis': ['Scanner abdomino-pelvien sans injection','Scanner abdomino-pelvien avec injection','Scanner abdomino-pelvien sans + avec injection','Uro-scanner (lithiase / voies urinaires)'],
        'Thorax-Abdomen-Pelvis': ['Scanner TAP sans injection','Scanner TAP avec injection','Scanner TAP sans + avec injection (bilan extension)'],
        'Rachis cervical': ['Scanner rachis cervical','Scanner rachis cervical avec injection','Scanner disques cervicaux'],
        'Rachis dorsal': ['Scanner rachis dorsal','Scanner rachis dorsal avec injection'],
        'Rachis lombaire': ['Scanner rachis lombaire','Scanner disques lombaires','Scanner canal lombaire'],
        'Bassin': ['Scanner pelvien sans injection','Scanner pelvien avec injection','Scanner des hanches'],
        'Membre supérieur droit': ['Scanner poignet droit','Scanner coude droit','Scanner épaule droite','Scanner membre supérieur droit'],
        'Membre supérieur gauche': ['Scanner poignet gauche','Scanner coude gauche','Scanner épaule gauche','Scanner membre supérieur gauche'],
        'Membre inférieur droit': ['Scanner genou droit','Scanner cheville droite','Scanner pied droit','Scanner membre inférieur droit'],
        'Membre inférieur gauche': ['Scanner genou gauche','Scanner cheville gauche','Scanner pied gauche','Scanner membre inférieur gauche'],
        'Corps entier': ['Scanner corps entier sans injection','Scanner corps entier avec injection'],
    },
    irm: {
        'Crâne': ['IRM cérébrale sans injection','IRM cérébrale avec injection','IRM cérébrale sans + avec injection','Angio-IRM cérébrale','IRM cérébrale + hypophyse'],
        'Rachis cervical': ['IRM rachis cervical','IRM rachis cervical avec injection','IRM médullaire cervicale'],
        'Rachis dorsal': ['IRM rachis dorsal','IRM rachis dorsal avec injection','IRM médullaire dorsale'],
        'Rachis lombaire': ['IRM rachis lombaire','IRM rachis lombaire avec injection','IRM lombo-sacrée'],
        'Genou droit': ['IRM genou droit','IRM genou droit avec injection (arthro-IRM)'],
        'Genou gauche': ['IRM genou gauche','IRM genou gauche avec injection (arthro-IRM)'],
        'Épaule droite': ['IRM épaule droite','IRM épaule droite avec injection','Arthro-IRM épaule droite'],
        'Épaule gauche': ['IRM épaule gauche','IRM épaule gauche avec injection','Arthro-IRM épaule gauche'],
        'Hanche droite': ['IRM hanche droite','IRM hanche droite avec injection','Arthro-IRM hanche droite'],
        'Hanche gauche': ['IRM hanche gauche','IRM hanche gauche avec injection','Arthro-IRM hanche gauche'],
        'Poignet droit': ['IRM poignet droit','IRM poignet droit avec injection'],
        'Poignet gauche': ['IRM poignet gauche','IRM poignet gauche avec injection'],
        'Pelvis': ['IRM pelvienne','IRM pelvienne avec injection','IRM utéro-ovarienne','IRM prostatique multiparamétrique'],
        'Sein droit': ['IRM mammaire droite avec injection'],
        'Sein gauche': ['IRM mammaire gauche avec injection'],
        'Abdomen': ['IRM abdominale','IRM hépatique','Cholangio-IRM (MRCP)','IRM abdominale avec injection'],
        'Corps entier': ['IRM corps entier','IRM corps entier avec injection (bilan extension)'],
    },
    mammographie: {
        'Sein droit': ['Mammographie sein droit standard (face + oblique)','Mammographie sein droit + clichés complémentaires','Mammographie sein droit + échographie'],
        'Sein gauche': ['Mammographie sein gauche standard (face + oblique)','Mammographie sein gauche + clichés complémentaires','Mammographie sein gauche + échographie'],
        'Bilatéral': ['Mammographie bilatérale standard','Mammographie bilatérale + clichés complémentaires','Mammographie bilatérale + échographie bilatérale'],
    },
    autre: {
        'À préciser dans les instructions': ['Examen à préciser dans les instructions'],
    },
};

const iconeModalite = {
    radiographie: '🩻', echographie: '🔊', scanner: '💻', irm: '🧲', mammographie: '🔬', autre: '📋'
};

function updatePartieCorp() {
    const modalite = document.getElementById('modaliteImagerie').value;
    const select   = document.getElementById('partieCorpsSelect');
    select.innerHTML = '<option value="">— Choisir la partie —</option>';
    document.getElementById('examenSelect').innerHTML = '<option value="">— Sélectionner d\'abord la partie du corps —</option>';
    if (!modalite) return;
    (partiesCorps[modalite] || []).forEach(p => {
        const o = document.createElement('option');
        o.value = p; o.textContent = p;
        select.appendChild(o);
    });
    // Infos contraste
    const infoDiv = document.getElementById('infoImagerie');
    if (modalite === 'scanner' || modalite === 'irm') {
        infoDiv.innerHTML = `<div class="alert alert-warning py-2 small"><i class="fas fa-info-circle me-1"></i> Pour le <strong>${modalite.toUpperCase()}</strong>, vérifiez la créatinine et les allergies au produit de contraste si injection prévue.</div>`;
    } else {
        infoDiv.innerHTML = '';
    }
}

function updateExamens() {
    const modalite = document.getElementById('modaliteImagerie').value;
    const partie   = document.getElementById('partieCorpsSelect').value;
    const select   = document.getElementById('examenSelect');
    select.innerHTML = '<option value="">— Choisir l\'examen —</option>';
    if (!modalite || !partie) return;
    const liste = (examensParPartie[modalite] || {})[partie] || [];
    liste.forEach(e => {
        const o = document.createElement('option');
        o.value = e; o.textContent = e;
        select.appendChild(o);
    });
    if (liste.length === 1) select.value = liste[0];
}

function ajouterImagerieToListe(event) {
    event.preventDefault();
    const form     = event.target;
    const modalite = form.modalite.value;
    const partie   = form.partie_corps.value;
    const indication = form.indication.value.trim();

    const examen = form.examen.value;

    if (!modalite || !partie || !examen || !indication) {
        alert('Veuillez renseigner la modalité, la partie du corps, l\'examen et l\'indication clinique.'); return;
    }

    const demande = {
        modalite,
        partie_corps: partie,
        examen,
        urgence: form.urgence.value,
        incidence: form.incidence.value,
        avec_contraste: form.avec_contraste.checked,
        indication,
        instructions: form.instructions.value
    };

    demandesImagerie.push(demande);
    afficherListeImagerie();
    bootstrap.Modal.getInstance(document.getElementById('modalDemandeImagerie')).hide();
    form.reset();
    document.getElementById('infoImagerie').innerHTML = '';
    document.getElementById('partieCorpsSelect').innerHTML = '<option value="">— Sélectionner d\'abord la modalité —</option>';
    document.getElementById('examenSelect').innerHTML = '<option value="">— Sélectionner d\'abord la partie du corps —</option>';
}

function afficherListeImagerie() {
    const tbody    = document.getElementById('listeImagerie');
    const empty    = document.getElementById('emptyStateImagerie');
    const btnRadio = document.getElementById('btnEnvoyerRadio');

    if (demandesImagerie.length === 0) {
        tbody.innerHTML = ''; empty.style.display = 'block'; btnRadio.style.display = 'none'; return;
    }
    empty.style.display  = 'none';
    btnRadio.style.display = 'inline-block';

    const urgenceBadge = { NORMAL: 'bg-success', URGENT: 'bg-danger', TRES_URGENT: 'bg-dark' };

    tbody.innerHTML = demandesImagerie.map((d, i) => `
        <tr>
            <td class="fw-bold">${iconeModalite[d.modalite] || '📋'} ${d.modalite.charAt(0).toUpperCase() + d.modalite.slice(1)}</td>
            <td>
                <div class="fw-semibold">${d.examen}</div>
                <div class="text-muted small">${d.partie_corps}${d.incidence ? ' · '+d.incidence : ''}${d.avec_contraste ? ' <span class="badge bg-info text-dark ms-1" style="font-size:.65rem">+Contraste</span>' : ''}</div>
            </td>
            <td><span class="badge ${urgenceBadge[d.urgence] || 'bg-secondary'}">${d.urgence}</span></td>
            <td class="small text-muted">${d.indication.substring(0,60)}${d.indication.length>60?'…':''}</td>
            <td><span class="badge bg-warning text-dark">En attente</span></td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="retirerImagerie(${i})" title="Supprimer">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>
    `).join('');
}

function retirerImagerie(index) {
    demandesImagerie.splice(index, 1);
    afficherListeImagerie();
}

function envoyerEnRadiologie() {
    if (demandesImagerie.length === 0) { alert('Aucune demande d\'imagerie à envoyer.'); return; }

    const patientId     = document.querySelector('input[name="patient_id"]').value;
    const consultId     = document.querySelector('input[name="consultation_id"]')?.value || '';
    const btn           = document.getElementById('btnEnvoyerRadio');

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Envoi...';

    fetch('<?= BASE_URL ?>imagerie/creer-demande-consultation', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            patient_id:      patientId,
            consultation_id: consultId,
            medecin_id:      '<?= $_SESSION["user_id"] ?? "" ?>',
            demandes:        demandesImagerie
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showToast(`✅ ${data.count || demandesImagerie.length} demande(s) envoyée(s) en radiologie !`, 'success');
            demandesImagerie = [];
            afficherListeImagerie();
            btn.innerHTML = '<i class="fas fa-check me-1"></i> Envoyé';
        } else {
            showToast('❌ Erreur : ' + (data.message || 'Inconnue'), 'danger');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-paper-plane me-1"></i> Envoyer en Radiologie';
        }
    })
    .catch(() => {
        showToast('❌ Erreur réseau. Vérifiez la connexion.', 'danger');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-paper-plane me-1"></i> Envoyer en Radiologie';
    });
}

/* ─── Toast utilitaire ─── */
function showToast(msg, type = 'success') {
    const container = document.getElementById('toastContainer') || (() => {
        const d = document.createElement('div');
        d.id = 'toastContainer';
        d.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        d.style.zIndex = 9999;
        document.body.appendChild(d);
        return d;
    })();

    const id   = 'toast_' + Date.now();
    const html = `
        <div id="${id}" class="toast align-items-center text-white bg-${type} border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body fw-bold">${msg}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>`;
    container.insertAdjacentHTML('beforeend', html);
    const el = document.getElementById(id);
    new bootstrap.Toast(el, { delay: 4000 }).show();
    el.addEventListener('hidden.bs.toast', () => el.remove());
}
</script>

<?php include __DIR__ . '/../../layouts/footer.php'; ?>