<?php
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
                                <label class="fw-bold mb-2">Système principal :</label>
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
                                <div id="zone_autre_symptome" style="display: none;">
                                    <textarea class="form-control-modern textarea-modern" name="commentaires_systemiques" placeholder="Précisez le symptôme..."><?php echo htmlspecialchars($consultation['commentaires_systemiques'] ?? ''); ?></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- ═══════════════════════════════════════════ -->
                        <!-- ANTÉCÉDENTS MÉDICAUX                        -->
                        <!-- ═══════════════════════════════════════════ -->
                        <div class="form-section">
                            <div class="form-section-header">
                                <div class="form-section-icon" style="background:#fef3c7; color:#b45309;"><i class="bi bi-journal-medical"></i></div>
                                <div>
                                    <h6 class="form-section-title">Antécédents Médicaux</h6>
                                    <small class="text-muted">Données issues du dossier patient — modifiables si mise à jour</small>
                                </div>
                            </div>

                            <div class="form-group-modern">

                                <!-- Grille des 4 types d'antécédents -->
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

                                </div><!-- /atcd-grid -->
                            </div>
                        </div>

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
                                <textarea class="form-control-modern textarea-modern" name="histoire_maladie" required placeholder="Évolution et chronologie..."><?php echo htmlspecialchars($consultation['histoire_maladie'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <a href="<?php echo BASE_URL; ?>consultation" class="btn-secondary-modern"><i class="bi bi-x-lg"></i> Annuler</a>
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

        const selectSysteme = document.getElementById('systeme_principal');
        const symptomesList = document.getElementById('symptomes_list');
        const symptomesHidden = document.getElementById('symptomes_systemiques');

        function buildSymptomes() {
            symptomesList.innerHTML = '';
            const choice = selectSysteme.value;
            const symptomes = symptomesBySysteme[choice] || [];
            const selectedSymptoms = (symptomesHidden.value || '').split(',').map(s => s.trim()).filter(Boolean);

            symptomes.forEach(s => {
                const checked = selectedSymptoms.includes(s);
                const item = document.createElement('div');
                item.className = 'symptome-item' + (checked ? ' selected' : '');
                item.dataset.value = s;
                item.innerHTML = `<i class="bi bi-check check-icon"></i> ${s}`;
                item.onclick = function() {
                    this.classList.toggle('selected');
                    const allSelected = Array.from(document.querySelectorAll('.symptome-item.selected')).map(i => i.dataset.value);
                    symptomesHidden.value = allSelected.join(', ');
                };
                symptomesList.appendChild(item);
            });
        }

        selectSysteme.addEventListener('change', buildSymptomes);
        buildSymptomes();
    })();

    // ═══════════════════════════════════════════
    // GESTION ANTÉCÉDENTS MÉDICAUX
    // ═══════════════════════════════════════════
    (function initAtcd() {
        const quickTags = {
            medical:  ['Diabète', 'HTA', 'Asthme', 'Cardiopathie', 'Drépanocytose', 'Tuberculose', 'VIH', 'Épilepsie', 'IRC'],
            chir:     ['Appendicectomie', 'Césarienne', 'Hernie', 'Cholécystectomie', 'Laparotomie', 'Amputations'],
            fam:      ['Diabète familial', 'HTA familiale', 'Cancer', 'Cardiopathie', 'Drépanocytose', 'Mucoviscidose'],
            allergie: ['Pénicilline', 'AINS', 'Aspirine', 'Sulfamides', 'Arachides', 'Latex', 'Iode']
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

        ['medical','chir','fam','allergie'].forEach(k => {
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
        } else {
            edit.classList.remove('d-none');
            view.classList.add('d-none');
            document.getElementById('textarea-' + key)?.focus();
        }
    }

    function appendTag(key, tag) {
        const ta = document.getElementById('textarea-' + key);
        if (!ta) return;
        const val = ta.value.trim();
        ta.value = val ? val + ', ' + tag : tag;
        ta.dispatchEvent(new Event('input'));
    }

</script>

<?php include __DIR__ . '/../../layouts/footer.php'; ?>