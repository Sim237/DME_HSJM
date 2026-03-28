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
        <?php include __DIR__ . '/../../layouts/sidebar.php'; ?>

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
                    </div>
                </div>

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

<script>
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

    const examens = categorie ?
        examensDisponibles.filter(e => e.categorie === categorie) :
        examensDisponibles;

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

    if (!option || !option.value) {
        infoDiv.innerHTML = '';
        return;
    }

    const examen = JSON.parse(option.dataset.examen);

    let html = `<div class="alert alert-info py-2">`;
    html += `<strong>Type prélèvement:</strong> ${examen.type_prelevement}<br>`;
    html += `<strong>Délai:</strong> ${examen.delai_rendu_heures}h`;
    if (examen.a_jeun_requis) {
        html += ` <span class="badge bg-warning text-dark">A jeun requis</span>`;
    }
    html += `</div>`;

    infoDiv.innerHTML = html;

    // Cocher automatiquement "A jeun" si requis
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
        id: examen.id,
        nom: examen.nom,
        categorie: examen.categorie,
        type_prelevement: examen.type_prelevement,
        delai_rendu_heures: examen.delai_rendu_heures,
        urgent: form.urgent.checked,
        a_jeun: form.a_jeun.checked,
        instructions: form.instructions.value
    };

    examensLaboratoire.push(nouvelExamen);
    afficherListeExamens();

    // Fermer modal et reset form
    bootstrap.Modal.getInstance(document.getElementById('modalDemandeExamen')).hide();
    form.reset();
    document.getElementById('infoExamen').innerHTML = '';
}

function afficherListeExamens() {
    const tbody = document.getElementById('listeExamens');
    const emptyState = document.getElementById('emptyStateExamens');
    const btnEnvoyer = document.getElementById('btnEnvoyerLabo');

    if (examensLaboratoire.length === 0) {
        tbody.innerHTML = '';
        emptyState.style.display = 'block';
        btnEnvoyer.style.display = 'none';
        return;
    }

    emptyState.style.display = 'none';
    btnEnvoyer.style.display = 'inline-block';
    tbody.innerHTML = examensLaboratoire.map((examen, index) => `
        <tr>
            <td class="fw-bold">${examen.nom}</td>
            <td><span class="badge bg-secondary">${examen.categorie}</span></td>
            <td>${examen.type_prelevement}</td>
            <td>${examen.delai_rendu_heures}h</td>
            <td>
                ${examen.urgent ? '<span class="badge bg-danger">URGENT</span>' : '<span class="badge bg-success">Normal</span>'}
                ${examen.a_jeun ? '<br><small class="text-warning">A jeun</small>' : ''}
            </td>
            <td><span class="badge bg-warning">En attente</span></td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-danger" onclick="retirerExamen(${index})">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
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
    if (examensLaboratoire.length === 0) {
        alert('Veuillez d\'abord ajouter au moins un examen.');
        return;
    }

    const patientId = document.querySelector('input[name="patient_id"]').value;
    const btn = document.getElementById('btnEnvoyerLabo');
    btn.disabled = true;
    btn.innerHTML = 'Envoi...';

    fetch('<?= BASE_URL ?>laboratoire/creer-demande-consultation', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ patient_id: patientId, examens: examensLaboratoire })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            // Pas de window.location.href ici
            alert('✅ Demande envoyée ! Elle apparaîtra dans votre suivi de bilans.');
            examensLaboratoire = [];
            afficherListeExamens();
            btn.innerHTML = '<i class="bi bi-send"></i> Envoyé';
        } else {
            alert('❌ Erreur : ' + data.message);
        }
    });
}
</script>

<?php include __DIR__ . '/../../layouts/footer.php'; ?>