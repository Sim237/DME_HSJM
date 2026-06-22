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

// etape1_anamnese.php
$patient = $patient ?? [];
$consultation = $consultation_data ?? [];
$type_consultation = $_GET['type'] ?? $consultation['type'] ?? 'EXTERNE';

include __DIR__ . '/../../layouts/header.php';
?>

<div class="container-fluid">
    <div class="row">

        <main class="col-12 px-md-4 consultation-form" style="margin-left: 0 !important;">

            <?php
                $numero = 1;
                include __DIR__ . '/progress_bar.php';
            ?>

            <form action="<?php echo BASE_URL; ?>consultation/sauvegarder" method="POST" id="formAnamnese">

                <input type="hidden" name="etape_actuelle" value="1">
                <input type="hidden" name="patient_id" value="<?php echo $patient['id']; ?>">
                <input type="hidden" name="type" value="<?php echo htmlspecialchars($type_consultation); ?>">

                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-clipboard-check me-2"></i> ANAMNÈSE</h5>
                    </div>
                    <div class="card-body">

                        <!-- Motif de Consultation -->
                        <div class="form-section">
                            <div class="form-section-header">
                                <div class="form-section-icon"><i class="bi bi-chat-right-text"></i></div>
                                <div><h6 class="form-section-title">Motif de Consultation</h6></div>
                            </div>
                            <div class="form-group-modern">
                                <textarea class="form-control-modern textarea-modern" name="motif_consultation" required placeholder="Décrivez le motif principal..."><?php echo htmlspecialchars($consultation['motif_consultation'] ?? ''); ?></textarea>
                            </div>
                        </div>

                        <!-- Enquête Systémique -->
                        <div class="form-section">
                            <div class="form-section-header">
                                <div class="form-section-icon"><i class="bi bi-heart-pulse"></i></div>
                                <div><h6 class="form-section-title">Enquête Systémique</h6></div>
                            </div>
                            <div class="form-group-modern">
                                <div class="d-flex align-items-center gap-3 mb-2">
                                    <label class="fw-bold mb-0">Système principal :</label>
                                    <span id="sysBadge" class="badge bg-primary" style="font-size:.75rem;"></span>
                                </div>
                                <select id="systeme_principal" name="systeme_principal" class="form-control-modern mb-3" style="max-width: 300px;">
                                    <?php
                                        $sys_sel = $consultation['systeme_principal'] ?? '';
                                        $systemes = ['Respiratoire','Cardio-vasculaire','Digestif','Urinaire','Neurologique','Ostéo-articulaire','Endocrinien','Hématologique','Dermatologique','Autre'];
                                        foreach ($systemes as $s) echo "<option value=\"$s\" ".($s == $sys_sel ? 'selected':'').">$s</option>";
                                    ?>
                                </select>

                                <label class="mb-2 fw-bold">Symptômes associés :</label>
                                <div id="symptomes_list" class="symptome-grid mb-3"></div>
                                <input type="hidden" id="symptomes_systemiques" name="symptomes_systemiques" value="<?php echo htmlspecialchars($consultation['symptomes_systemiques'] ?? ''); ?>">

                                <!-- Zone Autre Symtôme -->
                                <button type="button" class="btn btn-outline-secondary btn-sm mb-2" onclick="document.getElementById('zone_autre_symptome').style.display='block'">
                                    <i class="bi bi-plus-circle"></i> Ajouter un symptôme non listé
                                </button>
                                <div id="zone_autre_symptome" style="display: <?= !empty($consultation['commentaires_systemiques']) ? 'block' : 'none' ?>;">
                                    <textarea class="form-control-modern textarea-modern" name="commentaires_systemiques" placeholder="Précisez le symptôme..."><?php echo htmlspecialchars($consultation['commentaires_systemiques'] ?? ''); ?></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- ═══════════════════════════════════════════ -->
                        <!-- ANTÉCÉDENTS MÉDICAUX                        -->
                        <!-- ═══════════════════════════════════════════ -->
                        <?php
                        $isFemme = ($patient['sexe'] ?? '') === 'F';
                        $gynecoSaved = [];
                        if (!empty($consultation['atcd_gyneco'])) {
                            $gynecoSaved = json_decode($consultation['atcd_gyneco'], true) ?: [];
                        }
                        $gv = function(string $k) use ($gynecoSaved): string {
                            return htmlspecialchars($gynecoSaved[$k] ?? '');
                        };
                        ?>
                        <div class="form-section">
                            <div class="form-section-header">
                                <div class="form-section-icon" style="background:#fef3c7; color:#b45309;"><i class="bi bi-journal-medical"></i></div>
                                <div>
                                    <h6 class="form-section-title">Antécédents Médicaux</h6>
                                    <small class="text-muted">Données issues du dossier patient — modifiables si mise à jour</small>
                                </div>
                            </div>

                            <div class="form-group-modern">

                                <!-- Grille des 4+1 types d'antécédents -->
                                <div class="atcd-grid" id="atcdGrid">

                                    <!-- MÉDICAUX -->
                                    <div class="atcd-card" id="atcd-medical">
                                        <div class="atcd-card-header" style="border-left:4px solid #3b82f6;">
                                            <span class="atcd-icon" style="background:#dbeafe; color:#1d4ed8;"><i class="bi bi-heart-pulse"></i></span>
                                            <div>
                                                <div class="atcd-title">Médicaux</div>
                                                <div class="atcd-sub">Maladies chroniques, hospitalisations</div>
                                            </div>
                                            <button type="button" class="btn-atcd-edit" onclick="toggleAtcd('medical')">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                        </div>
                                        <div class="atcd-body" id="view-medical">
                                            <div class="atcd-chips" id="chips-medical"></div>
                                            <p class="atcd-empty text-muted small" id="empty-medical">Aucun antécédent médical renseigné</p>
                                        </div>
                                        <div class="atcd-edit-zone d-none" id="edit-medical">
                                            <div class="atcd-quick-tags mb-2" id="tags-medical"></div>
                                            <textarea class="form-control-modern textarea-modern"
                                                      name="atcd_medicaux"
                                                      id="textarea-medical"
                                                      rows="3"
                                                      placeholder="Diabète, HTA, cardiopathie, asthme..."><?php echo htmlspecialchars($patient['antecedents_medicaux'] ?? $consultation['atcd_medicaux'] ?? ''); ?></textarea>
                                        </div>
                                    </div>

                                    <!-- CHIRURGICAUX -->
                                    <div class="atcd-card" id="atcd-chir">
                                        <div class="atcd-card-header" style="border-left:4px solid #10b981;">
                                            <span class="atcd-icon" style="background:#d1fae5; color:#065f46;"><i class="bi bi-scissors"></i></span>
                                            <div>
                                                <div class="atcd-title">Chirurgicaux</div>
                                                <div class="atcd-sub">Opérations, interventions</div>
                                            </div>
                                            <button type="button" class="btn-atcd-edit" onclick="toggleAtcd('chir')">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                        </div>
                                        <div class="atcd-body" id="view-chir">
                                            <div class="atcd-chips" id="chips-chir"></div>
                                            <p class="atcd-empty text-muted small" id="empty-chir">Aucune intervention chirurgicale</p>
                                        </div>
                                        <div class="atcd-edit-zone d-none" id="edit-chir">
                                            <div class="atcd-quick-tags mb-2" id="tags-chir"></div>
                                            <textarea class="form-control-modern textarea-modern"
                                                      name="atcd_chirurgicaux"
                                                      id="textarea-chir"
                                                      rows="3"
                                                      placeholder="Appendicectomie, césarienne, hernie..."><?php echo htmlspecialchars($patient['antecedents_chirurgicaux'] ?? $consultation['atcd_chirurgicaux'] ?? ''); ?></textarea>
                                        </div>
                                    </div>

                                    <!-- FAMILIAUX -->
                                    <div class="atcd-card" id="atcd-fam">
                                        <div class="atcd-card-header" style="border-left:4px solid #8b5cf6;">
                                            <span class="atcd-icon" style="background:#ede9fe; color:#5b21b6;"><i class="bi bi-people"></i></span>
                                            <div>
                                                <div class="atcd-title">Familiaux</div>
                                                <div class="atcd-sub">Maladies héréditaires, génétiques</div>
                                            </div>
                                            <button type="button" class="btn-atcd-edit" onclick="toggleAtcd('fam')">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                        </div>
                                        <div class="atcd-body" id="view-fam">
                                            <div class="atcd-chips" id="chips-fam"></div>
                                            <p class="atcd-empty text-muted small" id="empty-fam">Aucun antécédent familial renseigné</p>
                                        </div>
                                        <div class="atcd-edit-zone d-none" id="edit-fam">
                                            <div class="atcd-quick-tags mb-2" id="tags-fam"></div>
                                            <textarea class="form-control-modern textarea-modern"
                                                      name="atcd_familiaux"
                                                      id="textarea-fam"
                                                      rows="3"
                                                      placeholder="Cancer familial, diabète, HTA, cardiopathies..."><?php echo htmlspecialchars($patient['antecedents_familiaux'] ?? $consultation['atcd_familiaux'] ?? ''); ?></textarea>
                                        </div>
                                    </div>

                                    <!-- ALLERGIES -->
                                    <div class="atcd-card" id="atcd-allergie">
                                        <div class="atcd-card-header" style="border-left:4px solid #ef4444;">
                                            <span class="atcd-icon" style="background:#fee2e2; color:#b91c1c;"><i class="bi bi-exclamation-triangle-fill"></i></span>
                                            <div>
                                                <div class="atcd-title">Allergies</div>
                                                <div class="atcd-sub">Médicaments, aliments, substances</div>
                                            </div>
                                            <button type="button" class="btn-atcd-edit" onclick="toggleAtcd('allergie')">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                        </div>
                                        <div class="atcd-body" id="view-allergie">
                                            <div class="atcd-chips" id="chips-allergie"></div>
                                            <p class="atcd-empty text-muted small" id="empty-allergie">Aucune allergie connue</p>
                                        </div>
                                        <div class="atcd-edit-zone d-none" id="edit-allergie">
                                            <div class="atcd-quick-tags mb-2" id="tags-allergie"></div>
                                            <textarea class="form-control-modern textarea-modern"
                                                      name="atcd_allergies"
                                                      id="textarea-allergie"
                                                      rows="2"
                                                      placeholder="Pénicilline, AINS, arachides..."><?php echo htmlspecialchars($patient['allergies'] ?? $consultation['atcd_allergies'] ?? ''); ?></textarea>
                                        </div>
                                    </div>

                                    <!-- TOXICOLOGIQUES -->
                                    <div class="atcd-card" id="atcd-toxico">
                                        <div class="atcd-card-header" style="border-left:4px solid #f97316;">
                                            <span class="atcd-icon" style="background:#ffedd5; color:#c2410c;"><i class="bi bi-exclamation-octagon"></i></span>
                                            <div>
                                                <div class="atcd-title">Toxicologiques</div>
                                                <div class="atcd-sub">Tabac, alcool, substances</div>
                                            </div>
                                            <button type="button" class="btn-atcd-edit" onclick="toggleAtcd('toxico')">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                        </div>
                                        <div class="atcd-body" id="view-toxico">
                                            <div class="atcd-chips" id="chips-toxico"></div>
                                            <p class="atcd-empty text-muted small" id="empty-toxico">Aucun antécédent toxicologique renseigné</p>
                                        </div>
                                        <div class="atcd-edit-zone d-none" id="edit-toxico">
                                            <div class="atcd-quick-tags mb-2" id="tags-toxico"></div>
                                            <textarea class="form-control-modern textarea-modern"
                                                      name="atcd_toxicologiques"
                                                      id="textarea-toxico"
                                                      rows="2"
                                                      placeholder="Tabac (10 PA), alcool occasionnel, cannabis..."><?php echo htmlspecialchars($consultation['atcd_toxicologiques'] ?? ''); ?></textarea>
                                        </div>
                                    </div>

                                    <!-- MÉDICAMENTEUX -->
                                    <div class="atcd-card" id="atcd-medicament">
                                        <div class="atcd-card-header" style="border-left:4px solid #14b8a6;">
                                            <span class="atcd-icon" style="background:#ccfbf1; color:#0f766e;"><i class="bi bi-capsule"></i></span>
                                            <div>
                                                <div class="atcd-title">Médicamenteux</div>
                                                <div class="atcd-sub">Traitements en cours ou habituels</div>
                                            </div>
                                            <button type="button" class="btn-atcd-edit" onclick="toggleAtcd('medicament')">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                        </div>
                                        <div class="atcd-body" id="view-medicament">
                                            <div class="atcd-chips" id="chips-medicament"></div>
                                            <p class="atcd-empty text-muted small" id="empty-medicament">Aucun antécédent médicamenteux renseigné</p>
                                        </div>
                                        <div class="atcd-edit-zone d-none" id="edit-medicament">
                                            <div class="atcd-quick-tags mb-2" id="tags-medicament"></div>
                                            <textarea class="form-control-modern textarea-modern"
                                                      name="atcd_medicamenteux"
                                                      id="textarea-medicament"
                                                      rows="2"
                                                      placeholder="Metformine, antihypertenseurs, corticoïdes au long cours..."><?php echo htmlspecialchars($consultation['atcd_medicamenteux'] ?? ''); ?></textarea>
                                        </div>
                                    </div>

                                    <!-- GYNÉCOLOGIQUES (patientes uniquement) -->
                                    <?php if ($isFemme): ?>
                                    <div class="atcd-card atcd-card-full" id="atcd-gyneco">
                                        <div class="atcd-card-header" style="border-left:4px solid #ec4899;">
                                            <span class="atcd-icon" style="background:#fce7f3; color:#be185d;"><i class="bi bi-gender-female"></i></span>
                                            <div>
                                                <div class="atcd-title">Gynécologiques</div>
                                                <div class="atcd-sub">DDR, gestité, parité, contraception</div>
                                            </div>
                                            <button type="button" class="btn-atcd-edit" onclick="toggleAtcd('gyneco')">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                        </div>
                                        <div class="atcd-body" id="view-gyneco">
                                            <?php
                                            $gynecoChips = [];
                                            if ($gv('ddr'))       $gynecoChips[] = 'DDR : ' . date('d/m/Y', strtotime($gynecoSaved['ddr']));
                                            if ($gv('gestite') !== '')  $gynecoChips[] = 'G' . $gv('gestite');
                                            if ($gv('parite') !== '')   $gynecoChips[] = 'P' . $gv('parite');
                                            if ($gv('cycle'))     $gynecoChips[] = $gv('cycle');
                                            if ($gv('contraception') && $gv('contraception') !== 'Aucune') $gynecoChips[] = $gv('contraception');
                                            if ($gv('grossesse_actuelle') === 'Oui') $gynecoChips[] = 'Grossesse en cours' . ($gv('terme_sa') ? ' — ' . $gv('terme_sa') . ' SA' : '');
                                            ?>
                                            <?php if (!empty($gynecoChips)): ?>
                                            <div class="atcd-chips">
                                                <?php foreach ($gynecoChips as $chip): ?>
                                                <span class="atcd-chip" style="background:#fdf2f8;color:#9d174d;border-color:#f9a8d4;"><?= $chip ?></span>
                                                <?php endforeach; ?>
                                            </div>
                                            <?php else: ?>
                                            <p class="atcd-empty text-muted small" id="empty-gyneco">Aucun antécédent gynécologique renseigné</p>
                                            <?php endif; ?>
                                        </div>
                                        <div class="atcd-edit-zone d-none" id="edit-gyneco">
                                            <input type="hidden" name="atcd_gyneco" id="atcd_gyneco_json"
                                                   value="<?= htmlspecialchars($consultation['atcd_gyneco'] ?? '') ?>">
                                            <div class="gyneco-grid">
                                                <div class="gyneco-field">
                                                    <label class="gyneco-label">Date des dernières règles (DDR)</label>
                                                    <input type="date" class="form-control gyneco-input" id="gyneco_ddr" value="<?= $gv('ddr') ?>">
                                                </div>
                                                <div class="gyneco-field">
                                                    <label class="gyneco-label">Ménarche (1ères règles)</label>
                                                    <div class="input-group input-group-sm">
                                                        <input type="number" class="form-control gyneco-input" id="gyneco_menarche" placeholder="Ex: 13" min="8" max="20" value="<?= $gv('menarche') ?>">
                                                        <span class="input-group-text">ans</span>
                                                    </div>
                                                </div>
                                                <div class="gyneco-field">
                                                    <label class="gyneco-label">Type de cycle</label>
                                                    <select class="form-select gyneco-input" id="gyneco_cycle">
                                                        <option value="">— Choisir —</option>
                                                        <?php
                                                        $cycleVal = $gynecoSaved['cycle'] ?? '';
                                                        foreach (['Régulier','Irrégulier','Absent (aménorrhée)','Ménopause'] as $o) {
                                                            echo '<option value="'.htmlspecialchars($o).'"'.($o===$cycleVal?' selected':'').'>'.htmlspecialchars($o).'</option>';
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                                <div class="gyneco-field">
                                                    <label class="gyneco-label">Durée des règles</label>
                                                    <div class="input-group input-group-sm">
                                                        <input type="number" class="form-control gyneco-input" id="gyneco_duree_regles" placeholder="Ex: 5" min="1" max="15" value="<?= $gv('duree_regles') ?>">
                                                        <span class="input-group-text">jours</span>
                                                    </div>
                                                </div>
                                                <div class="gyneco-field">
                                                    <label class="gyneco-label">Gestité (G)</label>
                                                    <input type="number" class="form-control gyneco-input" id="gyneco_gestite" placeholder="Nbre grossesses" min="0" max="20" value="<?= $gv('gestite') ?>">
                                                </div>
                                                <div class="gyneco-field">
                                                    <label class="gyneco-label">Parité (P)</label>
                                                    <input type="number" class="form-control gyneco-input" id="gyneco_parite" placeholder="Nbre accouchements" min="0" max="20" value="<?= $gv('parite') ?>">
                                                </div>
                                                <div class="gyneco-field">
                                                    <label class="gyneco-label">Avortements / FC</label>
                                                    <input type="number" class="form-control gyneco-input" id="gyneco_avortements" placeholder="Spontanés + provoqués" min="0" max="20" value="<?= $gv('avortements') ?>">
                                                </div>
                                                <div class="gyneco-field">
                                                    <label class="gyneco-label">Grossesse actuelle ?</label>
                                                    <select class="form-select gyneco-input" id="gyneco_grossesse_actuelle" onchange="toggleTermeSA()">
                                                        <option value="">— Choisir —</option>
                                                        <?php
                                                        $gaVal = $gynecoSaved['grossesse_actuelle'] ?? '';
                                                        foreach (['Non','Oui'] as $o) {
                                                            echo '<option value="'.$o.'"'.($o===$gaVal?' selected':'').'>'.$o.'</option>';
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                                <div class="gyneco-field" id="field-terme-sa" style="<?= ($gynecoSaved['grossesse_actuelle'] ?? '') === 'Oui' ? '' : 'display:none' ?>">
                                                    <label class="gyneco-label">Terme (SA)</label>
                                                    <div class="input-group input-group-sm">
                                                        <input type="number" class="form-control gyneco-input" id="gyneco_terme_sa" placeholder="Ex: 28" min="4" max="44" value="<?= $gv('terme_sa') ?>">
                                                        <span class="input-group-text">SA</span>
                                                    </div>
                                                </div>
                                                <div class="gyneco-field">
                                                    <label class="gyneco-label">Contraception</label>
                                                    <select class="form-select gyneco-input" id="gyneco_contraception">
                                                        <option value="">— Choisir —</option>
                                                        <?php
                                                        $cVal = $gynecoSaved['contraception'] ?? '';
                                                        foreach (['Aucune','Pilule','Préservatif','DIU (stérilet)','Implant','Injection','Naturelle','Autre'] as $o) {
                                                            echo '<option value="'.$o.'"'.($o===$cVal?' selected':'').'>'.$o.'</option>';
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                                <div class="gyneco-field">
                                                    <label class="gyneco-label">Allaitement en cours ?</label>
                                                    <select class="form-select gyneco-input" id="gyneco_allaitement">
                                                        <option value="">— Choisir —</option>
                                                        <?php
                                                        $aVal = $gynecoSaved['allaitement'] ?? '';
                                                        foreach (['Non','Oui'] as $o) {
                                                            echo '<option value="'.$o.'"'.($o===$aVal?' selected':'').'>'.$o.'</option>';
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                                <div class="gyneco-field">
                                                    <label class="gyneco-label">Dernière consultation gynéco</label>
                                                    <input type="date" class="form-control gyneco-input" id="gyneco_derniere_gyneco" value="<?= $gv('derniere_gyneco') ?>">
                                                </div>
                                                <div class="gyneco-field">
                                                    <label class="gyneco-label">Dernier frottis cervical</label>
                                                    <input type="date" class="form-control gyneco-input" id="gyneco_derniere_frottis" value="<?= $gv('derniere_frottis') ?>">
                                                </div>
                                                <div class="gyneco-field">
                                                    <label class="gyneco-label">Dernière mammographie</label>
                                                    <input type="date" class="form-control gyneco-input" id="gyneco_mammographie" value="<?= $gv('mammographie') ?>">
                                                </div>
                                            </div>
                                            <div class="mt-3">
                                                <label class="gyneco-label">Notes gynécologiques</label>
                                                <textarea class="form-control-modern textarea-modern gyneco-input" id="gyneco_notes"
                                                          rows="2" placeholder="Remarques, antécédents gynéco particuliers..."><?= $gv('notes') ?></textarea>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>

                                </div><!-- /atcd-grid -->

                        <!-- Automédication -->
                        <div class="form-section">
                            <div class="form-section-header">
                                <div class="form-section-icon"><i class="bi bi-capsule"></i></div>
                                <div><h6 class="form-section-title">Automédication</h6></div>
                            </div>
                            <div class="form-group-modern">
                                <label class="fw-bold mb-2">Le patient a-t-il pris des médicaments avant de venir ?</label>
                                <select id="select_automedication" class="form-control-modern mb-3" style="max-width: 300px;" onchange="toggleAutomedication()">
                                    <option value="non" <?= empty($consultation['automedication']) ? 'selected' : '' ?>>Non</option>
                                    <option value="oui" <?= !empty($consultation['automedication']) ? 'selected' : '' ?>>Oui</option>
                                </select>

                                <div id="zone_automedication" style="display: <?= !empty($consultation['automedication']) ? 'block' : 'none' ?>;">
                                    <label class="fw-bold mb-2">Préciser les médicaments :</label>
                                    <textarea class="form-control-modern textarea-modern" name="automedication" placeholder="Listez les médicaments pris..."><?php echo htmlspecialchars($consultation['automedication'] ?? ''); ?></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Histoire de la maladie -->
                        <div class="form-section">
                            <div class="form-section-header">
                                <div class="form-section-icon"><i class="bi bi-file-earmark-medical"></i></div>
                                <div><h6 class="form-section-title">Histoire de la Maladie</h6></div>
                            </div>
                            <div class="form-group-modern">
                                <textarea class="form-control-modern textarea-modern" name="histoire_maladie" placeholder="Évolution et chronologie..."><?php echo htmlspecialchars($consultation['histoire_maladie'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <a href="<?php echo BASE_URL; ?>" class="btn-secondary-modern"><i class="bi bi-x-lg"></i> Annuler</a>
                    <button type="submit" class="btn-primary-modern">Suivant <i class="bi bi-arrow-right"></i></button>
                </div>
            </form>
        </main>
    </div>
</div>

<style>
    .symptome-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }
    .symptome-item { border: 1px solid #e2e8f0; border-radius: 8px; padding: 8px 12px; cursor: pointer; display: flex; align-items: center; gap: 8px; font-size: 0.85rem; background: #f8fafc; transition: 0.2s; }
    .symptome-item.selected { background: #dbeafe; border-color: #2563eb; color: #1e40af; }
    .check-icon { width: 16px; height: 16px; border: 1px solid #cbd5e1; border-radius: 4px; display: flex; align-items: center; justify-content: center; }
    .symptome-item.selected .check-icon { background: #2563eb; border-color: #2563eb; color: white; }

    /* ── Antécédents grid ── */
    .atcd-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 8px; }
    @media (max-width: 768px) { .atcd-grid { grid-template-columns: 1fr; } }

    .atcd-card {
        border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden;
        background: #fff; box-shadow: 0 2px 6px rgba(0,0,0,.04);
        transition: box-shadow .2s;
    }
    .atcd-card:hover { box-shadow: 0 4px 14px rgba(0,0,0,.08); }

    .atcd-card-header {
        display: flex; align-items: center; gap: 12px;
        padding: 12px 14px; background: #f8fafc;
        border-bottom: 1px solid #f1f5f9;
    }
    .atcd-icon {
        width: 36px; height: 36px; border-radius: 9px; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center; font-size: 1rem;
    }
    .atcd-title { font-weight: 700; font-size: .88rem; color: #1e293b; }
    .atcd-sub   { font-size: .72rem; color: #94a3b8; }

    .btn-atcd-edit {
        margin-left: auto; background: none; border: 1px solid #e2e8f0;
        border-radius: 7px; padding: 4px 10px; color: #64748b;
        font-size: .78rem; cursor: pointer; transition: .15s;
    }
    .btn-atcd-edit:hover { background: #f1f5f9; color: #1e293b; }

    .atcd-body { padding: 10px 14px; min-height: 44px; }
    .atcd-chips { display: flex; flex-wrap: wrap; gap: 6px; }
    .atcd-chip {
        background: #f1f5f9; border: 1px solid #e2e8f0; border-radius: 20px;
        padding: 3px 10px; font-size: .78rem; color: #334155;
    }
    .atcd-empty { margin: 0; font-style: italic; }

    .atcd-edit-zone { padding: 10px 14px 14px; border-top: 1px dashed #e2e8f0; background: #fffbf5; }
    .atcd-quick-tags { display: flex; flex-wrap: wrap; gap: 6px; }
    .atcd-quick-tag {
        background: #fff; border: 1px solid #cbd5e1; border-radius: 20px;
        padding: 2px 10px; font-size: .75rem; color: #475569; cursor: pointer;
        transition: .15s;
    }
    .atcd-quick-tag:hover { background: #2563eb; color: #fff; border-color: #2563eb; }

    /* ── Antécédents Gynécologiques ── */
    .gyneco-header { background: linear-gradient(135deg, #fdf2f8, #fce7f3) !important; border-bottom: 1px solid #f9a8d4; }
    .gyneco-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 14px;
        margin-bottom: 4px;
    }
    @media (max-width: 1200px) { .gyneco-grid { grid-template-columns: repeat(3, 1fr); } }
    @media (max-width: 900px)  { .gyneco-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 576px)  { .gyneco-grid { grid-template-columns: 1fr; } }
    .gyneco-field { display: flex; flex-direction: column; gap: 4px; }
    .gyneco-label {
        font-size: .75rem; font-weight: 700;
        color: #9d174d; text-transform: uppercase; letter-spacing: .4px;
    }
    .gyneco-input { font-size: .88rem; }
    .atcd-card-full { grid-column: 1 / -1; }
    .atcd-card-full .gyneco-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
    @media (max-width: 900px) { .atcd-card-full .gyneco-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 576px) { .atcd-card-full .gyneco-grid { grid-template-columns: 1fr; } }
</style>

<script>
    function toggleAutomedication() {
        const select = document.getElementById('select_automedication');
        const zone = document.getElementById('zone_automedication');
        zone.style.display = (select.value === 'oui') ? 'block' : 'none';
        if(select.value === 'non') document.querySelector('[name="automedication"]').value = '';
    }

    (function(){
        const symptomesBySysteme = {
            'Respiratoire': ['Toux', 'Dyspnée', 'Douleur thoracique', 'Hémoptysie', 'Bruits anormaux', 'Sibilances', 'Expectorations'],
            'Cardio-vasculaire': ['Douleur thoracique', 'Palpitations', 'Œdème', 'Syncope', 'Fatigue', 'Dyspnée effort'],
            'Digestif': ['Nausées', 'Vomissements', 'Diarrhée', 'Constipation', 'Douleur abd.', 'Ballonnements'],
            'Urinaire': ['Dysurie', 'Pollakiurie', 'Hématurie', 'Douleur lombaire', 'Brûlures'],
            'Neurologique': ['Céphalée', 'Vertiges', 'Troubles parole', 'Faiblesse', 'Engourdissements', 'Convulsions'],
            'Ostéo-articulaire': ['Arthralgies', 'Myalgies', 'Raideur', 'Gonflement'],
            'Endocrinien': ['Polyurie', 'Polydipsie', 'Intolérance chaud/froid', 'Perte poids'],
            'Hématologique': ['Pâleur', 'Adénopathie', 'Hémorragies', 'Fatigue'],
            'Dermatologique': ['Éruption', 'Prurit', 'Ulcère', 'Sécheresse'],
            'Autre': ['Fièvre', 'Sudation nocturne', 'Asthénie']
        };

        const selectSysteme   = document.getElementById('systeme_principal');
        const symptomesList   = document.getElementById('symptomes_list');
        const symptomesHidden = document.getElementById('symptomes_systemiques');

        /* Map systemeName → Set(symptômes sélectionnés) — persiste tous les systèmes */
        const selectionsParSysteme = new Map();

        /* Charger les données initiales depuis le champ hidden */
        (function chargerInitial() {
            const raw = (symptomesHidden.value || '').trim();
            if (!raw) return;
            if (raw.includes('|') || raw.includes(':')) {
                /* Format multi-système : "Respiratoire: Toux, Dyspnée | Cardio-vasculaire: Palpitations" */
                raw.split('|').forEach(part => {
                    const [sys, ...rest] = part.split(':');
                    const sysTrimmed = sys.trim();
                    const symptoms = rest.join(':').split(',').map(s => s.trim()).filter(Boolean);
                    if (sysTrimmed && symptoms.length) selectionsParSysteme.set(sysTrimmed, new Set(symptoms));
                });
            } else {
                /* Format ancien (liste plate) : attribuer au système actuellement sélectionné */
                const sys = selectSysteme.value;
                const symptoms = raw.split(',').map(s => s.trim()).filter(Boolean);
                if (symptoms.length) selectionsParSysteme.set(sys, new Set(symptoms));
            }
        })();

        function updateHiddenField() {
            const parts = [];
            selectionsParSysteme.forEach((sel, sys) => {
                if (sel.size > 0) parts.push(`${sys}: ${[...sel].join(', ')}`);
            });
            symptomesHidden.value = parts.join(' | ');
        }

        function buildSymptomes() {
            symptomesList.innerHTML = '';
            const choice   = selectSysteme.value;
            const symptomes = symptomesBySysteme[choice] || [];
            const currentSel = selectionsParSysteme.get(choice) || new Set();

            symptomes.forEach(s => {
                const checked = currentSel.has(s);
                const item = document.createElement('div');
                item.className = 'symptome-item' + (checked ? ' selected' : '');
                item.dataset.value = s;
                item.innerHTML = `<i class="bi bi-check check-icon"></i> ${s}`;
                item.onclick = function() {
                    this.classList.toggle('selected');
                    /* Mettre à jour la Map pour ce système */
                    const sel = new Set(
                        Array.from(document.querySelectorAll('.symptome-item.selected'))
                            .map(i => i.dataset.value)
                    );
                    if (sel.size > 0) selectionsParSysteme.set(choice, sel);
                    else selectionsParSysteme.delete(choice);
                    updateHiddenField();
                };
                symptomesList.appendChild(item);
            });

            /* Badge compteur par système */
            const nbSel = (selectionsParSysteme.get(choice) || new Set()).size;
            const badge = document.getElementById('sysBadge');
            if (badge) badge.textContent = nbSel > 0 ? `${nbSel} sélectionné${nbSel>1?'s':''}` : '';
        }

        selectSysteme.addEventListener('change', buildSymptomes);
        buildSymptomes();
    })();

    // ═══════════════════════════════════════════
    // GESTION ANTÉCÉDENTS MÉDICAUX
    // ═══════════════════════════════════════════
    (function initAtcd() {
        const quickTags = {
            medical:    ['Diabète', 'HTA', 'Asthme', 'Cardiopathie', 'Drépanocytose', 'Tuberculose', 'VIH', 'Épilepsie', 'IRC'],
            chir:       ['Appendicectomie', 'Césarienne', 'Hernie', 'Cholécystectomie', 'Laparotomie', 'Amputations'],
            fam:        ['Diabète familial', 'HTA familiale', 'Cancer', 'Cardiopathie', 'Drépanocytose', 'Mucoviscidose'],
            allergie:   ['Pénicilline', 'AINS', 'Aspirine', 'Sulfamides', 'Arachides', 'Latex', 'Iode'],
            toxico:     ['Tabac', 'Alcool', 'Cannabis', 'Tramadol', 'Chicha', 'Sevré (tabac)', 'Sevré (alcool)'],
            medicament: ['Antihypertenseurs', 'Metformine', 'Insuline', 'Corticoïdes', 'AINS au long cours', 'Anticoagulants', 'ARV', 'Contraceptifs']
        };

        function renderChips(key) {
            const txt   = document.getElementById('textarea-' + key)?.value || '';
            const chips = document.getElementById('chips-'   + key);
            const empty = document.getElementById('empty-'   + key);
            if (!chips) return;
            if (!txt.trim()) {
                chips.innerHTML = '';
                if (empty) empty.style.display = 'block';
                return;
            }
            if (empty) empty.style.display = 'none';
            chips.innerHTML = txt.split(/[,;\n]+/).filter(s => s.trim())
                .map(s => `<span class="atcd-chip">${s.trim()}</span>`)
                .join('');
        }

        function buildQuickTags(key) {
            const container = document.getElementById('tags-' + key);
            if (!container) return;
            container.innerHTML = (quickTags[key] || []).map(tag =>
                `<span class="atcd-quick-tag" onclick="appendTag('${key}','${tag}')">${tag}</span>`
            ).join('');
        }

        ['medical','chir','fam','allergie','toxico','medicament'].forEach(k => {
            renderChips(k);
            buildQuickTags(k);
            const ta = document.getElementById('textarea-' + k);
            if (ta) ta.addEventListener('input', () => renderChips(k));
        });
    })();

    function toggleAtcd(key) {
        const edit  = document.getElementById('edit-'  + key);
        const view  = document.getElementById('view-'  + key);
        const isOpen = !edit.classList.contains('d-none');
        if (isOpen) {
            edit.classList.add('d-none');
            view.classList.remove('d-none');
            // Gynéco : pas de textarea — la vue est gérée côté PHP au chargement
            if (key !== 'gyneco') {
                const txt   = document.getElementById('textarea-' + key)?.value || '';
                const chips = document.getElementById('chips-'   + key);
                const empty = document.getElementById('empty-'   + key);
                if (!txt.trim()) {
                    chips.innerHTML = '';
                    if (empty) empty.style.display = 'block';
                } else {
                    if (empty) empty.style.display = 'none';
                    chips.innerHTML = txt.split(/[,;\n]+/).filter(s => s.trim())
                        .map(s => `<span class="atcd-chip">${s.trim()}</span>`).join('');
                }
            }
        } else {
            edit.classList.remove('d-none');
            view.classList.add('d-none');
            if (key !== 'gyneco') document.getElementById('textarea-' + key)?.focus();
        }
    }

    function appendTag(key, tag) {
        const ta = document.getElementById('textarea-' + key);
        if (!ta) return;
        const val = ta.value.trim();
        ta.value = val ? val + ', ' + tag : tag;
        ta.dispatchEvent(new Event('input'));
    }

    function toggleTermeSA() {
        const sel   = document.getElementById('gyneco_grossesse_actuelle');
        const field = document.getElementById('field-terme-sa');
        if (field) field.style.display = (sel && sel.value === 'Oui') ? '' : 'none';
    }

    // Sérialise les champs gynéco dans le hidden JSON avant envoi
    document.getElementById('formAnamnese').addEventListener('submit', function() {
        var keys = ['ddr','menarche','cycle','duree_regles','gestite','parite','avortements',
                    'grossesse_actuelle','terme_sa','contraception','allaitement',
                    'derniere_gyneco','derniere_frottis','mammographie','notes'];
        var data = {};
        keys.forEach(function(k) {
            var el = document.getElementById('gyneco_' + k);
            if (el) { var v = el.value.trim(); if (v) data[k] = v; }
        });
        document.getElementById('atcd_gyneco_json').value =
            Object.keys(data).length ? JSON.stringify(data) : '';
    });

</script>

<?php include __DIR__ . '/../../layouts/footer.php'; ?>