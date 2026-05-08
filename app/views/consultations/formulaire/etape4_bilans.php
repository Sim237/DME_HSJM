<?php
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
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Catégorie</label>
                            <select class="form-select" id="categorieExamen" onchange="chargerExamensCategorie()">
                                <option value="">Toutes les catégories</option>
                                <option value="HEMATOLOGIE">Hématologie</option>
                                <option value="BIOCHIMIE">Biochimie</option>
                                <option value="IMMUNOLOGIE">Immunologie</option>
                                <option value="MICROBIOLOGIE">Microbiologie</option>
                                <option value="PARASITOLOGIE">Parasitologie</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Examen <span class="text-danger">*</span></label>
                            <select class="form-select" name="examen_id" id="examen_id" required onchange="afficherInfoExamen()">
                                <option value="">Sélectionner un examen...</option>
                            </select>
                            <div id="infoExamen" class="mt-2"></div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="urgent" id="urgent">
                                <label class="form-check-label text-danger fw-bold" for="urgent">
                                    <i class="fas fa-exclamation-triangle"></i> URGENT
                                </label>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="a_jeun" id="a_jeun">
                                <label class="form-check-label" for="a_jeun">
                                    <i class="fas fa-clock"></i> À jeun requis
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Instructions particulières</label>
                        <textarea name="instructions" class="form-control" rows="2" placeholder="Instructions spéciales pour le laboratoire..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-info">Ajouter l'examen</button>
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
                            <select class="form-select" id="partieCorpsSelect" name="partie_corps" required>
                                <option value="">— Sélectionner d'abord la modalité —</option>
                            </select>
                        </div>

                        <!-- Urgence -->
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Niveau d'urgence</label>
                            <select class="form-select" name="urgence" id="urgenceImagerie">
                                <option value="NORMAL">Normal</option>
                                <option value="URGENT">🔴 URGENT</option>
                                <option value="TRES_URGENT">🚨 TRÈS URGENT</option>
                            </select>
                        </div>

                        <!-- Côté -->
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Côté / Latéralité</label>
                            <select class="form-select" name="cote">
                                <option value="">Non applicable</option>
                                <option value="droit">Droit</option>
                                <option value="gauche">Gauche</option>
                                <option value="bilateral">Bilatéral</option>
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

document.addEventListener('DOMContentLoaded', function() {
    chargerExamensDisponibles();
});

function chargerExamensDisponibles() {
    fetch('<?= BASE_URL ?>laboratoire/examens-disponibles')
        .then(r => r.json())
        .then(examens => {
            examensDisponibles = examens;
            chargerExamensCategorie();
        })
        .catch(console.error);
}

function chargerExamensCategorie() {
    const categorie = document.getElementById('categorieExamen').value;
    const select = document.getElementById('examen_id');
    select.innerHTML = '<option value="">Sélectionner un examen...</option>';
    const examens = categorie ? examensDisponibles.filter(e => e.categorie === categorie) : examensDisponibles;
    examens.forEach(examen => {
        const option = document.createElement('option');
        option.value = examen.id;
        option.textContent = `${examen.nom} (${examen.delai_rendu_heures}h)`;
        option.dataset.examen = JSON.stringify(examen);
        select.appendChild(option);
    });
}

function afficherInfoExamen() {
    const select = document.getElementById('examen_id');
    const option = select.selectedOptions[0];
    const infoDiv = document.getElementById('infoExamen');
    if (!option || !option.value) { infoDiv.innerHTML = ''; return; }
    const examen = JSON.parse(option.dataset.examen);
    let html = `<div class="alert alert-info py-2">`;
    html += `<strong>Type prélèvement:</strong> ${examen.type_prelevement}<br>`;
    html += `<strong>Délai:</strong> ${examen.delai_rendu_heures}h`;
    if (examen.a_jeun_requis) html += ` <span class="badge bg-warning text-dark">A jeun requis</span>`;
    html += `</div>`;
    infoDiv.innerHTML = html;
    document.getElementById('a_jeun').checked = examen.a_jeun_requis;
}

function ajouterExamenToListe(event) {
    event.preventDefault();
    const form = event.target;
    const select = document.getElementById('examen_id');
    const option = select.selectedOptions[0];
    if (!option || !option.value) return;
    const examen = JSON.parse(option.dataset.examen);
    const nouvelExamen = {
        id: examen.id, nom: examen.nom, categorie: examen.categorie,
        type_prelevement: examen.type_prelevement, delai_rendu_heures: examen.delai_rendu_heures,
        urgent: form.urgent.checked, a_jeun: form.a_jeun.checked, instructions: form.instructions.value
    };
    examensLaboratoire.push(nouvelExamen);
    afficherListeExamens();
    bootstrap.Modal.getInstance(document.getElementById('modalDemandeExamen')).hide();
    form.reset();
    document.getElementById('infoExamen').innerHTML = '';
}

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
            <td>${examen.urgent ? '<span class="badge bg-danger">URGENT</span>' : '<span class="badge bg-success">Normal</span>'}${examen.a_jeun ? '<br><small class="text-warning">A jeun</small>' : ''}</td>
            <td><span class="badge bg-warning">En attente</span></td>
            <td class="text-center"><button type="button" class="btn btn-sm btn-danger" onclick="retirerExamen(${index})"><i class="fas fa-trash"></i></button></td>
        </tr>
        <input type="hidden" name="examens[${index}][examen_id]" value="${examen.id}">
        <input type="hidden" name="examens[${index}][urgent]" value="${examen.urgent}">
        <input type="hidden" name="examens[${index}][a_jeun]" value="${examen.a_jeun}">
        <input type="hidden" name="examens[${index}][instructions]" value="${examen.instructions}">
    `).join('');
}

function retirerExamen(index) {
    examensLaboratoire.splice(index, 1);
    afficherListeExamens();
}

function envoyerAuLaboratoire() {
    if (examensLaboratoire.length === 0) { alert('Veuillez d\'abord ajouter au moins un examen.'); return; }
    const patientId = document.querySelector('input[name="patient_id"]').value;
    const btn = document.getElementById('btnEnvoyerLabo');
    btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Envoi...';
    fetch('<?= BASE_URL ?>laboratoire/creer-demande-consultation', {
        method: 'POST', headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ patient_id: patientId, examens: examensLaboratoire })
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
    radiographie: ['Thorax','Abdomen','Crâne','Rachis cervical','Rachis dorsal','Rachis lombaire','Bassin','Épaule droite','Épaule gauche','Bras droit','Bras gauche','Avant-bras droit','Avant-bras gauche','Main droite','Main gauche','Cuisse droite','Cuisse gauche','Jambe droite','Jambe gauche','Pied droit','Pied gauche','Cheville droite','Cheville gauche','Genou droit','Genou gauche'],
    echographie: ['Abdomen total','Pelvis','Obstétricale','Thyroïde','Sein droit','Sein gauche','Rein droit','Rein gauche','Foie','Vésicule biliaire','Rate','Prostate','Col utérin','Tendons','Paroi abdominale'],
    scanner: ['Crâne','Thorax','Abdomen-Pelvis','Thorax-Abdomen-Pelvis','Rachis cervical','Rachis dorsal','Rachis lombaire','Bassin','Membre supérieur droit','Membre supérieur gauche','Membre inférieur droit','Membre inférieur gauche','Corps entier'],
    irm: ['Crâne','Rachis cervical','Rachis dorsal','Rachis lombaire','Genou droit','Genou gauche','Épaule droite','Épaule gauche','Hanche droite','Hanche gauche','Poignet droit','Poignet gauche','Pelvis','Sein droit','Sein gauche','Abdomen','Corps entier'],
    mammographie: ['Sein droit','Sein gauche','Bilatéral'],
    autre: ['À préciser dans les instructions']
};

const iconeModalite = {
    radiographie: '🩻', echographie: '🔊', scanner: '💻', irm: '🧲', mammographie: '🔬', autre: '📋'
};

function updatePartieCorp() {
    const modalite = document.getElementById('modaliteImagerie').value;
    const select   = document.getElementById('partieCorpsSelect');
    select.innerHTML = '<option value="">— Choisir la partie —</option>';
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

function ajouterImagerieToListe(event) {
    event.preventDefault();
    const form     = event.target;
    const modalite = form.modalite.value;
    const partie   = form.partie_corps.value;
    const indication = form.indication.value.trim();

    if (!modalite || !partie || !indication) {
        alert('Veuillez renseigner la modalité, la partie du corps et l\'indication clinique.'); return;
    }

    const demande = {
        modalite,
        partie_corps: partie,
        urgence: form.urgence.value,
        cote: form.cote.value,
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
            <td>${d.partie_corps}${d.cote ? ' <span class="text-muted small">('+d.cote+')</span>' : ''}${d.avec_contraste ? ' <span class="badge bg-info text-dark ms-1">+Contraste</span>' : ''}</td>
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