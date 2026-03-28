<?php
// etape1_anamnese.php
$patient = $patient ?? [];
$consultation = $consultation_data ?? [];
$type_consultation = $_GET['type'] ?? $consultation['type'] ?? 'EXTERNE';

include __DIR__ . '/../../layouts/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/../../layouts/sidebar.php'; ?>

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
</script>

<?php include __DIR__ . '/../../layouts/footer.php'; ?>